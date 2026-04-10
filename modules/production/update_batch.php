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
            $yieldQty = $actualYield ?? $batch['actual_yield'] ?? $batch['target_quantity'];
            $targetQty = $batch['target_quantity'];
            $userId = $_SESSION['user_id'] ?? 1;

            // 1. FIND THE RECIPE for this finished good
            $recipeStmt = $pdo->prepare("SELECT recipe_id, base_yield_qty FROM recipes WHERE finished_item_id = ? LIMIT 1");
            $recipeStmt->execute([$itemId]);
            $recipe = $recipeStmt->fetch();

            $totalBomCost = 0;

            if ($recipe) {
                $scaleFactor = $targetQty / max(1, $recipe['base_yield_qty']);

                // 2. DEDUCT RAW MATERIALS based on recipe ingredients
                $ingStmt = $pdo->prepare("
                    SELECT ri.raw_material_id, ri.quantity_required, im.item_name, im.standard_cost
                    FROM recipe_ingredients ri
                    JOIN item_master im ON ri.raw_material_id = im.item_id
                    WHERE ri.recipe_id = ?
                ");
                $ingStmt->execute([$recipe['recipe_id']]);
                $ingredients = $ingStmt->fetchAll();

                foreach ($ingredients as $ing) {
                    $consumeQty = round($ing['quantity_required'] * $scaleFactor, 3);
                    $materialCost = $consumeQty * $ing['standard_cost'];
                    $totalBomCost += $materialCost;

                    // Deduct from inventory ledger
                    $deduct = $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Consumed', ?, ?, ?)");
                    $deduct->execute([$ing['raw_material_id'], -$consumeQty, 'PRD-' . $prodId, $userId]);
                }
            }

            // 3. CREDIT FINISHED GOODS into inventory
            $creditFG = $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Produced', ?, ?, ?)");
            $creditFG->execute([$itemId, $yieldQty, 'PRD-' . $prodId, $userId]);

            // 4. RECORD BOM EXPENSE in finance ledger
            if ($totalBomCost > 0) {
                $bomTransId = 'BOM-' . time();
                $pdo->prepare("INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), 'Expense', 'Production Materials (BOM)', ?, ?, ?)")
                    ->execute([$bomTransId, $totalBomCost, $prodId, $userId]);
            }

            // 5. NOTIFY stakeholders
            $costStr = number_format($totalBomCost, 2);
            addNotification($pdo, "Batch Completed ✅", "Batch {$prodId} completed. {$yieldQty} units of {$batch['item_name']} produced. BOM cost: \${$costStr}", 'manufacturing');
            addNotification($pdo, "Inventory Updated", "Finished goods +{$yieldQty}. Raw materials consumed per recipe. Review in Inventory.", 'inventory');
            addNotification($pdo, "BOM Expense Recorded", "Production BOM cost of \${$costStr} for batch {$prodId} recorded in General Ledger.", 'finance');
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Update Batch Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
