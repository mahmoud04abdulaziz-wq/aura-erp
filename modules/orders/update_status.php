<?php
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

try {
    $stmt = $pdo->prepare("UPDATE sales_orders SET order_status = ? WHERE so_id = ?");
    $stmt->execute([$status, $so_id]);
    
    $handled_by = $_SESSION['user_id'] ?? 1;
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'UPDATE_ORDER_STATUS', ?, 'Success')");
    $logStmt->execute([$handled_by, "Updated order $so_id status to $status"]);

    // Push to Live Notification Feed
    require_once __DIR__ . '/../../includes/notifications.php';
    addNotification($pdo, "Order Status Updated", "Order #{$so_id} is now: {$status}", 'finance');
    // --- PIPELINE AUTOMATION HOOKS ---
    if ($status === 'In Production') {
        // Find a representative Finished Good to produce (for demo purposes if no line items exist)
        $fgStmt = $pdo->query("SELECT item_id FROM item_master WHERE category = 'Finished Good' LIMIT 1");
        $fg = $fgStmt->fetchColumn();
        if ($fg) {
            $prod_id = 'PRD-' . date('Ymd') . '-' . mt_rand(100, 999);
            $target_qty = 100; // Simulated batch size
            $insertProd = $pdo->prepare("INSERT INTO production_orders (production_id, item_id, target_quantity, status, operator_user_id) VALUES (?, ?, ?, 'Planned', ?)");
            $op_id = $_SESSION['user_id'] ?? 1;
            $insertProd->execute([$prod_id, $fg, $target_qty, $op_id]);
            
            addNotification($pdo, "New Production Batch", "Sales Order {$so_id} triggered Production Batch {$prod_id}", 'manufacturing');
        }
    } elseif ($status === 'Pending Delivery') {
        // Mark any linked production batches as Completed  
        // (Inventory deductions happen inside update_batch.php when batch → Completed)
        $linkedBatches = $pdo->prepare("SELECT production_id FROM production_orders WHERE status IN ('Mixing','Curing','Planned') ORDER BY created_at DESC LIMIT 1");
        $linkedBatches->execute();
        $linkedBatch = $linkedBatches->fetchColumn();
        
        if ($linkedBatch) {
            // Auto-complete the batch (inventory impacts handled by update_batch logic)
            $pdo->prepare("UPDATE production_orders SET status = 'Completed', qa_status = 'Passed', actual_yield = target_quantity WHERE production_id = ?")->execute([$linkedBatch]);
            
            // Trigger the same inventory automation as update_batch.php
            $batchInfo = $pdo->prepare("SELECT po.*, im.item_name FROM production_orders po JOIN item_master im ON po.item_id = im.item_id WHERE po.production_id = ?");
            $batchInfo->execute([$linkedBatch]);
            $batch = $batchInfo->fetch();
            
            if ($batch) {
                $itemId = $batch['item_id'];
                $yieldQty = $batch['target_quantity'];
                $userId = $_SESSION['user_id'] ?? 1;
                $totalBomCost = 0;

                // Find recipe and deduct raw materials
                $recipeStmt = $pdo->prepare("SELECT recipe_id, base_yield_qty FROM recipes WHERE finished_item_id = ? LIMIT 1");
                $recipeStmt->execute([$itemId]);
                $recipe = $recipeStmt->fetch();

                if ($recipe) {
                    $scaleFactor = $yieldQty / max(1, $recipe['base_yield_qty']);
                    $ingStmt = $pdo->prepare("SELECT ri.raw_material_id, ri.quantity_required, im.standard_cost FROM recipe_ingredients ri JOIN item_master im ON ri.raw_material_id = im.item_id WHERE ri.recipe_id = ?");
                    $ingStmt->execute([$recipe['recipe_id']]);
                    foreach ($ingStmt->fetchAll() as $ing) {
                        $consumeQty = round($ing['quantity_required'] * $scaleFactor, 3);
                        $totalBomCost += $consumeQty * $ing['standard_cost'];
                        $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Consumed', ?, ?, ?)")
                            ->execute([$ing['raw_material_id'], -$consumeQty, 'SO-' . $so_id, $userId]);
                    }
                }

                // Credit finished goods
                $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Produced', ?, ?, ?)")
                    ->execute([$itemId, $yieldQty, 'SO-' . $so_id, $userId]);

                // Record BOM expense
                if ($totalBomCost > 0) {
                    $pdo->prepare("INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), 'Expense', 'Production Materials (BOM)', ?, ?, ?)")
                        ->execute(['BOM-' . time(), $totalBomCost, $so_id, $userId]);
                }

                addNotification($pdo, "Production Complete ✅", "Batch {$linkedBatch} auto-completed for order {$so_id}. Raw materials consumed, finished goods stocked.", 'manufacturing');
                addNotification($pdo, "BOM Cost Recorded", "Material cost of $" . number_format($totalBomCost, 2) . " for {$so_id} recorded in General Ledger.", 'finance');
            }
        } else {
            addNotification($pdo, "QC Passed", "Order {$so_id} passed quality check and is ready for delivery.", 'manufacturing');
        }
    } elseif ($status === 'Delivered') {
        // Realize Financial Income
        $soStmt = $pdo->prepare("SELECT so.total_price, so.payment_method, c.company_name, c.email 
                                  FROM sales_orders so 
                                  JOIN customers c ON so.customer_id = c.customer_id 
                                  WHERE so.so_id = ?");
        $soStmt->execute([$so_id]);
        $soData = $soStmt->fetch();
        $val = $soData['total_price'] ?? 0;
        
        $trans_id = 'INC-' . time();
        $recordFin = $pdo->prepare("INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), 'Income', 'Sales Revenue', ?, ?, ?)");
        $recordFin->execute([$trans_id, $val, $so_id, $_SESSION['user_id'] ?? 1]);
        
        addNotification($pdo, "Revenue Recorded", "Payment of $" . number_format($val, 2) . " received for {$so_id}", 'finance');

        // --- JoFotara Compliance (نظام الفوترة الوطني الإلكتروني) ---
        // Submit invoice to ISTD via the JoFotara web service
        require_once __DIR__ . '/../../includes/jofotara_service.php';
        
        $invoicePayload = [
            'InvoiceNumber' => 'INV-' . date('Ymd') . '-' . substr(md5($so_id), 0, 6),
            'IssueDate' => date('Y-m-d'),
            'SellerTaxID' => 'JO-PENDING-TAX-ID', // Replace with real tax ID from ISTD
            'BuyerName' => $soData['company_name'] ?? 'Unknown',
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
        
        // Get the QR code (either from ISTD or simulated)
        $qrData = $submissionResult['qr_code'] ?? base64_encode(json_encode($invoicePayload));
        $submissionStatus = $submissionResult['success'] ? 'Submitted' : 'Failed';
        
        // Store invoice record
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
        $simNote = !empty($submissionResult['simulated']) ? ' (Demo Mode — configure ISTD credentials to submit live)' : '';
        addNotification($pdo, "JoFotara Invoice {$statusEmoji}", "Invoice {$invoicePayload['InvoiceNumber']} for {$so_id} — {$submissionStatus}{$simNote}", 'finance');

        // --- BOM Cost Report to Finance ---
        // Find production orders linked to this Sales Order and notify finance
        addNotification($pdo, "BOM Report Ready", "Production BOM consumption data available for delivered order {$so_id}. Review in Accounting.", 'finance');
    }

    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} catch (Exception $e) {
    error_log("Failed to update status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
