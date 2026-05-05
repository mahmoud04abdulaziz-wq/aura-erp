<?php
/**
 * MiskStone ERP — Update Production Batch
 * Handles stage transitions with automated actions:
 *
 * Planned → Mixing:  Deducts raw materials, generates BOM bill for Finance
 * Mixing → Curing:   Status change only
 * Curing → Completed: Credits ALL finished goods to inventory
 * Any → Failed:       Logs failure (no deductions/credits)
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$prodId = $_POST['production_id'] ?? '';
$status = $_POST['status'] ?? null;
$qaStatus = $_POST['qa_status'] ?? null;
$actualYield = $_POST['actual_yield'] ?? null;

if (empty($prodId)) {
    echo json_encode(['success' => false, 'error' => 'Production ID is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get current batch info
    $batchInfo = $pdo->prepare("SELECT po.*, im.item_name, im.standard_cost FROM production_orders po JOIN item_master im ON po.item_id = im.item_id WHERE po.production_id = ?");
    $batchInfo->execute([$prodId]);
    $batch = $batchInfo->fetch(PDO::FETCH_ASSOC);

    if (!$batch) {
        throw new Exception("Production batch not found.");
    }

    $userId = $_SESSION['user_id'] ?? 1;

    // Build dynamic update
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
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'No changes requested']);
        exit;
    }

    $params[] = $prodId;
    $pdo->prepare("UPDATE production_orders SET " . implode(', ', $updates) . " WHERE production_id = ?")->execute($params);

    // Log action
    $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'UPDATE_BATCH', ?, 'Success')")
        ->execute([$userId, "Updated batch {$prodId} → {$status}"]);

    require_once __DIR__ . '/../../includes/notifications.php';

    // ============================================================
    // STAGE: Planned → Mixing (DEDUCT RAW MATERIALS + BOM BILL)
    // ============================================================
    if ($status === 'Mixing' && $batch['status'] === 'Planned') {
        $itemId = $batch['item_id'];
        $targetQty = $batch['target_quantity'];
        $batchLabel = "Mix {$prodId} ({$batch['item_name']})";

        // Find recipe
        if (!empty($batch['recipe_id'])) {
            $recipe = $pdo->prepare("SELECT * FROM recipes WHERE recipe_id = ?");
            $recipe->execute([$batch['recipe_id']]);
        } else {
            $recipe = $pdo->prepare("SELECT * FROM recipes WHERE finished_item_id = ? LIMIT 1");
            $recipe->execute([$itemId]);
        }
        $recipe = $recipe->fetch(PDO::FETCH_ASSOC);

        if ($recipe) {
            $scaleFactor = $targetQty / max(1, (float)$recipe['base_yield_qty']);
            $totalBomCost = 0;

            // Create BOM bill record
            $pdo->prepare("INSERT INTO bom_bills (production_id, recipe_id, mix_runs, total_material_cost, generated_by) VALUES (?, ?, ?, 0, ?)")
                ->execute([$prodId, $recipe['recipe_id'], (int)$targetQty, $userId]);
            $billId = $pdo->lastInsertId();

            // Get ingredients and deduct
            $ingStmt = $pdo->prepare("
                SELECT ri.raw_material_id, ri.quantity_required, im.item_name, im.standard_cost
                FROM recipe_ingredients ri
                JOIN item_master im ON ri.raw_material_id = im.item_id
                WHERE ri.recipe_id = ?
            ");
            $ingStmt->execute([$recipe['recipe_id']]);

            foreach ($ingStmt->fetchAll(PDO::FETCH_ASSOC) as $ing) {
                $consumeQty = round($ing['quantity_required'] * $scaleFactor, 3);

                // Stock validation
                $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change),0) FROM inventory_ledger WHERE item_id = ?");
                $stockStmt->execute([$ing['raw_material_id']]);
                $currentStock = (float)$stockStmt->fetchColumn();

                if ($consumeQty > $currentStock) {
                    $consumeQty = max(0, $currentStock);
                    addNotification($pdo, "⚠️ Stock Shortage", "{$ing['item_name']} insufficient for {$batchLabel}. Used remaining {$consumeQty}.", 'inventory');
                }

                $lineCost = $consumeQty * $ing['standard_cost'];
                $totalBomCost += $lineCost;

                if ($consumeQty > 0) {
                    // Deduct from inventory
                    $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Consumed', ?, ?, ?)")
                        ->execute([$ing['raw_material_id'], -$consumeQty, $batchLabel, $userId]);

                    // Record BOM bill line
                    $pdo->prepare("INSERT INTO bom_bill_lines (bill_id, item_id, quantity_consumed, unit_cost) VALUES (?, ?, ?, ?)")
                        ->execute([$billId, $ing['raw_material_id'], $consumeQty, $ing['standard_cost']]);
                }
            }

            // Update BOM bill total
            $pdo->prepare("UPDATE bom_bills SET total_material_cost = ? WHERE bill_id = ?")->execute([$totalBomCost, $billId]);

            // Notify
            $costStr = number_format($totalBomCost, 2);
            addNotification($pdo, "🔥 Mixing Started", "{$batchLabel}: Raw materials consumed. BOM cost: {$costStr} JOD", 'production');
            addNotification($pdo, "📊 BOM Bill Generated", "Bill #{$billId} for {$batchLabel} — {$costStr} JOD. Review in Accounting.", 'finance');
            addNotification($pdo, "📦 Inventory Deducted", "Materials consumed for {$batchLabel}.", 'inventory');
        }
    }

    // ============================================================
    // STAGE: Curing → Completed (CREDIT FINISHED GOODS)
    // ============================================================
    if ($status === 'Completed' && in_array($batch['status'], ['Curing', 'Mixing'])) {
        $itemId = $batch['item_id'];
        $targetQty = $batch['target_quantity'];
        $batchLabel = "Mix {$prodId} ({$batch['item_name']})";

        // Find recipe
        if (!empty($batch['recipe_id'])) {
            $recipe = $pdo->prepare("SELECT * FROM recipes WHERE recipe_id = ?");
            $recipe->execute([$batch['recipe_id']]);
        } else {
            $recipe = $pdo->prepare("SELECT * FROM recipes WHERE finished_item_id = ? LIMIT 1");
            $recipe->execute([$itemId]);
        }
        $recipe = $recipe->fetch(PDO::FETCH_ASSOC);

        if ($recipe) {
            $scaleFactor = $targetQty / max(1, (float)$recipe['base_yield_qty']);

            // Credit ALL finished goods from mix_outputs
            $outputsStmt = $pdo->prepare("
                SELECT mo.item_id, mo.output_quantity, im.item_name 
                FROM mix_outputs mo
                JOIN item_master im ON mo.item_id = im.item_id
                WHERE mo.recipe_id = ?
            ");
            $outputsStmt->execute([$recipe['recipe_id']]);
            $outputs = $outputsStmt->fetchAll(PDO::FETCH_ASSOC);

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

            // Fallback: single FG if no mix_outputs
            if (empty($outputs)) {
                $yieldQty = $actualYield ?? $batch['actual_yield'] ?? $targetQty;
                $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Produced', ?, ?, ?)")
                    ->execute([$itemId, $yieldQty, $batchLabel, $userId]);
                $totalFGProduced = $yieldQty;
                $fgSummary[] = "{$yieldQty}× {$batch['item_name']}";
            }

            // Record BOM expense in finance
            $bomBill = $pdo->prepare("SELECT total_material_cost FROM bom_bills WHERE production_id = ? ORDER BY bill_id DESC LIMIT 1");
            $bomBill->execute([$prodId]);
            $bomCost = (float)($bomBill->fetchColumn() ?: 0);

            if ($bomCost > 0) {
                $txId = 'BOM-' . date('ymd') . '-' . substr(md5($prodId), 0, 6);
                $pdo->prepare("INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), 'Expense', 'Production Materials (BOM)', ?, ?, ?)")
                    ->execute([$txId, $bomCost, $batchLabel, $userId]);
            }

            // Notify
            $fgList = implode(', ', $fgSummary);
            addNotification($pdo, "✅ Batch Completed", "{$batchLabel}: {$totalFGProduced} FG produced ({$fgList}).", 'production');
            addNotification($pdo, "📦 FG Inventory Updated", "Mix outputs credited: {$fgList}", 'inventory');

            // Update linked sales order
            if (!empty($batch['so_id'])) {
                $pdo->prepare("UPDATE sales_orders SET order_status = 'Pending Delivery' WHERE so_id = ?")->execute([$batch['so_id']]);
                addNotification($pdo, "📦 Order Ready", "Production for {$batch['so_id']} is complete. Ready for delivery.", 'sales');
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Update Batch Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
