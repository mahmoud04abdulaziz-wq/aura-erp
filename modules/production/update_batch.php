<?php
/**
 * MiskStone ERP — Update Production Batch
 * Handles status changes, QA updates, and yield logging.
 * 
 * KEY AUTOMATION: When status → "Completed":
 *   1. Deducts raw materials from inventory based on ACTUAL recipe ingredients
 *   2. Credits finished goods into inventory
 *   3. Records a BOM cost expense in the finance ledger
 *   4. Notifies Finance + Inventory
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prodId = $_POST['production_id'] ?? '';
    $status = $_POST['status'] ?? null;
    $qaStatus = $_POST['qa_status'] ?? null;
    $actualYield = $_POST['actual_yield'] ?? null;

    if (empty($prodId)) {
        echo json_encode(['success' => false, 'error' => 'Production ID is required']);
        exit;
    }

    try {
        // Get current batch info BEFORE update
        $batchInfo = $pdo->prepare("SELECT po.*, im.item_name FROM production_orders po JOIN item_master im ON po.item_id = im.item_id WHERE po.production_id = ?");
        $batchInfo->execute([$prodId]);
        $batch = $batchInfo->fetch();

        // Build dynamic update query
        $updates = [];
        $params = [];

        if ($status !== null) {
            $updates[] = "status = ?";
            $params[] = $status;
        }
        if ($qaStatus !== null) {
            $updates[] = "qa_status = ?";
            $params[] = $qaStatus;
        }
        if ($actualYield !== null && $actualYield >= 0) {
            $updates[] = "actual_yield = ?";
            $params[] = $actualYield;
        }

        if (empty($updates)) {
            echo json_encode(['success' => true, 'message' => 'No changes requested']);
            exit;
        }

        $params[] = $prodId;
        $sql = "UPDATE production_orders SET " . implode(', ', $updates) . " WHERE production_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Log action
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'UPDATE_BATCH', ?, 'Success')");
        $logStmt->execute([$_SESSION['user_id'] ?? 1, "Updated Production Batch $prodId"]);

        // ============================================================
        // AUTOMATION: When batch is marked "Completed"
        // ============================================================
        if ($status === 'Completed' && $batch) {
            require_once __DIR__ . '/../../includes/notifications.php';
            
            $itemId = $batch['item_id'];
            $targetQty = $batch['target_quantity'];
            $userId = $_SESSION['user_id'] ?? 1;
            $batchLabel = "Mix {$prodId} ({$batch['item_name']})";

            // 1. FIND THE RECIPE
            $colCheck = $pdo->query("SHOW COLUMNS FROM recipes LIKE 'finished_item_id'");
            if ($colCheck->rowCount() > 0) {
                $recipeStmt = $pdo->prepare("SELECT recipe_id, base_yield_qty FROM recipes WHERE finished_item_id = ? LIMIT 1");
                $recipeStmt->execute([$itemId]);
            } else {
                $recipeStmt = $pdo->query("SELECT recipe_id, base_yield_qty FROM recipes LIMIT 1");
            }
            $recipe = $recipeStmt->fetch();

            $totalBomCost = 0;
            $scaleFactor = $targetQty / max(1, (float)($recipe['base_yield_qty'] ?? 1));

            if ($recipe) {
                // 2. DEDUCT RAW MATERIALS with STOCK VALIDATION
                $ingStmt = $pdo->prepare("
                    SELECT ri.raw_material_id, ri.quantity_required, im.item_name, im.standard_cost
                    FROM recipe_ingredients ri
                    JOIN item_master im ON ri.raw_material_id = im.item_id
                    WHERE ri.recipe_id = ?
                ");
                $ingStmt->execute([$recipe['recipe_id']]);

                foreach ($ingStmt->fetchAll() as $ing) {
                    $consumeQty = round($ing['quantity_required'] * $scaleFactor, 3);
                    
                    // STOCK VALIDATION: get current stock and clamp
                    $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change),0) FROM inventory_ledger WHERE item_id = ?");
                    $stockStmt->execute([$ing['raw_material_id']]);
                    $currentStock = (float)$stockStmt->fetchColumn();
                    
                    if ($consumeQty > $currentStock) {
                        $consumeQty = max(0, $currentStock);
                        addNotification($pdo, "⚠️ Stock Shortage", "{$ing['item_name']} insufficient for {$batchLabel}. Used remaining {$consumeQty}.", 'inventory');
                    }
                    
                    if ($consumeQty > 0) {
                        $totalBomCost += $consumeQty * $ing['standard_cost'];
                        $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Consumed', ?, ?, ?)")
                            ->execute([$ing['raw_material_id'], -$consumeQty, $batchLabel, $userId]);
                    }
                }

                // 3. CREDIT ALL FINISHED GOODS from mix_outputs (Mix-to-Many model)
                $outputsStmt = $pdo->prepare("
                    SELECT mo.item_id, mo.output_quantity, im.item_name 
                    FROM mix_outputs mo
                    JOIN item_master im ON mo.item_id = im.item_id
                    WHERE mo.recipe_id = ?
                ");
                $outputsStmt->execute([$recipe['recipe_id']]);
                $outputs = $outputsStmt->fetchAll();

                $totalFGProduced = 0;
                $fgSummary = [];
                foreach ($outputs as $out) {
                    $producedQty = round($out['output_quantity'] * $scaleFactor, 0);
                    if ($producedQty > 0) {
                        $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Produced', ?, ?, ?)")
                            ->execute([$out['item_id'], $producedQty, $batchLabel, $userId]);
                        $totalFGProduced += $producedQty;
                        $fgSummary[] = "{$producedQty}× {$out['item_name']}";
                    }
                }

                // Fallback: if no mix_outputs found, credit the single original FG
                if (empty($outputs)) {
                    $yieldQty = $actualYield ?? $batch['actual_yield'] ?? $targetQty;
                    $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Produced', ?, ?, ?)")
                        ->execute([$itemId, $yieldQty, $batchLabel, $userId]);
                    $totalFGProduced = $yieldQty;
                    $fgSummary[] = "{$yieldQty}× {$batch['item_name']}";
                }
            }

            // 4. RECORD BOM EXPENSE
            if ($totalBomCost > 0) {
                $pdo->prepare("INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), 'Expense', 'Production Materials (BOM)', ?, ?, ?)")
                    ->execute(['BOM-' . time(), $totalBomCost, $batchLabel, $userId]);
            }

            // 5. NOTIFY with detailed FG output list
            $costStr = number_format($totalBomCost, 2);
            $fgList = implode(', ', $fgSummary);
            addNotification($pdo, "✅ Batch Completed", "{$batchLabel}: {$totalFGProduced} total FG produced ({$fgList}). BOM cost: \${$costStr}", 'manufacturing');
            addNotification($pdo, "📦 Inventory Updated", "Mix outputs credited: {$fgList}", 'inventory');
            
            // 6. UPDATE LINKED SALES ORDER
            if (!empty($batch['so_id'])) {
                $pdo->prepare("UPDATE sales_orders SET order_status = 'Completed' WHERE so_id = ?")
                    ->execute([$batch['so_id']]);
                addNotification($pdo, "📦 Order Ready", "Production for Sales Order {$batch['so_id']} is finished. Ready for delivery.", 'sales');
            }
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Update Batch Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
