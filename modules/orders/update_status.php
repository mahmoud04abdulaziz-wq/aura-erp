<?php
/**
 * MiskStone ERP — Order Status Pipeline Handler
 * 
 * Automates the full pipeline when an order is dragged on the Kanban board:
 *   Pending → In Production:    Creates production batch
 *   In Production → QC/Delivery: Runs mix, deducts raw materials, credits FG, records BOM expense
 *   QC/Delivery → Delivered:     Records income, generates JoFotara tax invoice + QR
 * 
 * KEY FEATURES:
 *   - Stock validation: never allows negative inventory
 *   - Company names in all references (not just SO IDs)
 *   - Mix-based production: 1 mix → multiple finished products
 */
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

global $pdo;

$so_id = trim($_POST['so_id'] ?? '');
$status = trim($_POST['status'] ?? '');
$valid_statuses = ['Pending', 'In Production', 'Pending Delivery', 'Delivered'];

if (empty($so_id) || !in_array($status, $valid_statuses, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID or status value.']);
    exit;
}

// Helper: get current stock for a raw material
function getCurrentStock(PDO $pdo, string $itemId): float {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
    $stmt->execute([$itemId]);
    return (float)$stmt->fetchColumn();
}

try {
    // Fetch order + company info upfront (used in all hooks)
    $soStmt = $pdo->prepare("SELECT so.*, c.company_name, c.email 
                              FROM sales_orders so 
                              JOIN customers c ON so.customer_id = c.customer_id 
                              WHERE so.so_id = ?");
    $soStmt->execute([$so_id]);
    $soData = $soStmt->fetch();
    $companyName = $soData['company_name'] ?? 'Unknown';
    $refLabel = "{$so_id} ({$companyName})"; // e.g. "SO-260409-5299 (Adam's construction)"

    // Update the status
    $stmt = $pdo->prepare("UPDATE sales_orders SET order_status = ? WHERE so_id = ?");
    $stmt->execute([$status, $so_id]);
    
    $userId = $_SESSION['user_id'] ?? 1;
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'UPDATE_ORDER_STATUS', ?, 'Success')");
    $logStmt->execute([$userId, "Updated order {$refLabel} to {$status}"]);

    require_once __DIR__ . '/../../includes/notifications.php';
    addNotification($pdo, "Order Status Updated", "Order {$refLabel} → {$status}", 'finance');

    // =============================================================
    // PIPELINE HOOK: IN PRODUCTION
    // =============================================================
    if ($status === 'In Production') {
        // Create a production batch (mix run)
        $prod_id = 'MX-' . date('Ymd') . '-' . mt_rand(100, 999);
        
        // Find the first recipe (mix) to use
        $recipeStmt = $pdo->query("SELECT recipe_id, recipe_name FROM recipes LIMIT 1");
        $recipe = $recipeStmt->fetch();
        
        if ($recipe) {
            // Check if recipes has finished_item_id column (legacy) or not (mix model)
            $colCheck = $pdo->query("SHOW COLUMNS FROM recipes LIKE 'finished_item_id'");
            if ($colCheck->rowCount() > 0) {
                // Legacy: get finished_item_id from recipe
                $fgStmt = $pdo->prepare("SELECT finished_item_id FROM recipes WHERE recipe_id = ?");
                $fgStmt->execute([$recipe['recipe_id']]);
                $fg = $fgStmt->fetchColumn();
            } else {
                $fg = null;
            }
            
            // If we have a FG, use it; otherwise fall back to first FG
            if (!$fg) {
                $fg = $pdo->query("SELECT item_id FROM item_master WHERE category = 'Finished Good' LIMIT 1")->fetchColumn();
            }
            
            if ($fg) {
                $insertProd = $pdo->prepare("INSERT INTO production_orders (production_id, item_id, target_quantity, status, operator_user_id) VALUES (?, ?, 100, 'Planned', ?)");
                $insertProd->execute([$prod_id, $fg, $userId]);
            }
        }
        
        addNotification($pdo, "🏭 Mix Batch Started", "Order {$refLabel} triggered production Mix {$prod_id}", 'manufacturing');
    
    // =============================================================
    // PIPELINE HOOK: PENDING DELIVERY (Production Complete)
    // =============================================================
    } elseif ($status === 'Pending Delivery') {
        $linkedBatch = $pdo->prepare("SELECT production_id FROM production_orders WHERE status IN ('Mixing','Curing','Planned') ORDER BY created_at DESC LIMIT 1");
        $linkedBatch->execute();
        $batchId = $linkedBatch->fetchColumn();
        
        if ($batchId) {
            $pdo->prepare("UPDATE production_orders SET status = 'Completed', qa_status = 'Passed', actual_yield = target_quantity WHERE production_id = ?")
                ->execute([$batchId]);
            
            // Get the batch item info
            $batchInfo = $pdo->prepare("SELECT po.*, im.item_name FROM production_orders po JOIN item_master im ON po.item_id = im.item_id WHERE po.production_id = ?");
            $batchInfo->execute([$batchId]);
            $batch = $batchInfo->fetch();
            
            if ($batch) {
                $itemId = $batch['item_id'];
                $yieldQty = $batch['target_quantity'];
                $totalBomCost = 0;

                // Find recipe
                // Check legacy column first
                $colCheck = $pdo->query("SHOW COLUMNS FROM recipes LIKE 'finished_item_id'");
                if ($colCheck->rowCount() > 0) {
                    $recipeStmt = $pdo->prepare("SELECT recipe_id, base_yield_qty FROM recipes WHERE finished_item_id = ? LIMIT 1");
                    $recipeStmt->execute([$itemId]);
                } else {
                    $recipeStmt = $pdo->query("SELECT recipe_id, base_yield_qty FROM recipes LIMIT 1");
                }
                $recipe = $recipeStmt->fetch();

                if ($recipe) {
                    $scaleFactor = $yieldQty / max(1, (float)$recipe['base_yield_qty']);
                    $ingStmt = $pdo->prepare("SELECT ri.raw_material_id, ri.quantity_required, im.standard_cost, im.item_name FROM recipe_ingredients ri JOIN item_master im ON ri.raw_material_id = im.item_id WHERE ri.recipe_id = ?");
                    $ingStmt->execute([$recipe['recipe_id']]);
                    
                    foreach ($ingStmt->fetchAll() as $ing) {
                        $consumeQty = round($ing['quantity_required'] * $scaleFactor, 3);
                        
                        // STOCK VALIDATION: clamp to available stock
                        $currentStock = getCurrentStock($pdo, $ing['raw_material_id']);
                        if ($consumeQty > $currentStock) {
                            $consumeQty = max(0, $currentStock); // never go negative
                            addNotification($pdo, "⚠️ Stock Shortage", "{$ing['item_name']} insufficient for full batch ({$refLabel}). Used remaining {$consumeQty} units.", 'inventory');
                        }
                        
                        if ($consumeQty > 0) {
                            $totalBomCost += $consumeQty * $ing['standard_cost'];
                            $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Consumed', ?, ?, ?)")
                                ->execute([$ing['raw_material_id'], -$consumeQty, $refLabel, $userId]);
                        }
                    }
                }

                // Credit finished goods
                $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Produced', ?, ?, ?)")
                    ->execute([$itemId, $yieldQty, $refLabel, $userId]);
                
                // Check for mix_outputs table (multi-product model)
                try {
                    $moCheck = $pdo->query("SELECT COUNT(*) FROM mix_outputs")->fetchColumn();
                    if ($moCheck > 0 && $recipe) {
                        $moStmt = $pdo->prepare("SELECT mo.item_id, mo.quantity_per_mix, im.item_name FROM mix_outputs mo JOIN item_master im ON mo.item_id = im.item_id WHERE mo.recipe_id = ?");
                        $moStmt->execute([$recipe['recipe_id']]);
                        foreach ($moStmt->fetchAll() as $out) {
                            $outQty = round($out['quantity_per_mix'] * $scaleFactor, 3);
                            $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Produced', ?, ?, ?)")
                                ->execute([$out['item_id'], $outQty, $refLabel, $userId]);
                        }
                    }
                } catch (Exception $e) {
                    // mix_outputs may not exist yet
                }

                // Record BOM expense with company name
                if ($totalBomCost > 0) {
                    $pdo->prepare("INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), 'Expense', 'Production Materials (BOM)', ?, ?, ?)")
                        ->execute(['BOM-' . time(), $totalBomCost, $refLabel, $userId]);
                }

                addNotification($pdo, "✅ Production Complete", "Mix {$batchId} completed for {$refLabel}. Raw materials consumed, finished goods stocked.", 'manufacturing');
                addNotification($pdo, "💰 BOM Cost Recorded", "Material cost $" . number_format($totalBomCost, 2) . " for {$refLabel}", 'finance');
            }
        } else {
            addNotification($pdo, "QC Passed", "{$refLabel} passed quality check, ready for delivery.", 'manufacturing');
        }
    
    // =============================================================
    // PIPELINE HOOK: DELIVERED (Revenue + JoFotara Invoice)
    // =============================================================
    } elseif ($status === 'Delivered') {
        $val = $soData['total_price'] ?? 0;
        
        // Record income with company name
        $trans_id = 'INC-' . time();
        $pdo->prepare("INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), 'Income', 'Sales Revenue', ?, ?, ?)")
            ->execute([$trans_id, $val, $refLabel, $userId]);
        
        addNotification($pdo, "💵 Revenue Recorded", "Payment $" . number_format($val, 2) . " from {$companyName} ({$so_id})", 'finance');

        // --- JoFotara Compliance ---
        require_once __DIR__ . '/../../includes/jofotara_service.php';
        
        $invoicePayload = [
            'InvoiceNumber' => 'INV-' . date('Ymd') . '-' . substr(md5($so_id), 0, 6),
            'IssueDate' => date('Y-m-d'),
            'SellerTaxID' => 'JO-PENDING-TAX-ID',
            'BuyerName' => $companyName,
            'BuyerEmail' => $soData['email'] ?? '',
            'TotalAmount' => $val,
            'TaxRate' => 0.16,
            'TaxAmount' => round($val * 0.16, 2),
            'GrandTotal' => round($val * 1.16, 2),
            'Currency' => 'JOD',
            'PaymentMethod' => $soData['payment_method'] ?? 'Bank Transfer',
            'ReferenceOrderID' => $so_id,
        ];
        
        $joFotara = new JoFotaraService($pdo);
        $submissionResult = $joFotara->submitInvoice($invoicePayload);
        
        $qrData = $submissionResult['qr_code'] ?? base64_encode(json_encode($invoicePayload));
        $submissionStatus = $submissionResult['success'] ? 'Submitted' : 'Failed';
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS invoices_jo (
            invoice_id VARCHAR(50) PRIMARY KEY,
            so_id VARCHAR(50) NOT NULL,
            payload JSON NOT NULL,
            qr_code TEXT,
            submission_status VARCHAR(20) DEFAULT 'Pending',
            istd_ref VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $invStmt = $pdo->prepare("INSERT IGNORE INTO invoices_jo (invoice_id, so_id, payload, qr_code, submission_status, istd_ref) VALUES (?, ?, ?, ?, ?, ?)");
        $invStmt->execute([
            $invoicePayload['InvoiceNumber'], $so_id, 
            json_encode($invoicePayload), $qrData,
            $submissionStatus, $submissionResult['submission_id'] ?? null
        ]);
        
        $statusEmoji = $submissionResult['success'] ? '✅' : '⚠️';
        $simNote = !empty($submissionResult['simulated']) ? ' (Demo Mode)' : '';
        addNotification($pdo, "🧾 JoFotara {$statusEmoji}", "Invoice {$invoicePayload['InvoiceNumber']} for {$companyName}{$simNote}", 'finance');
        addNotification($pdo, "📋 BOM Report Ready", "BOM data for {$refLabel} available in Accounting.", 'finance');
    }

    echo json_encode(['success' => true, 'message' => "Status updated to {$status} for {$companyName}."]);
} catch (Exception $e) {
    error_log("Failed to update status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
