<?php
/**
 * MiskStone ERP — BOM Feasibility Check
 * GET: ?production_id=XXX
 * Returns JSON with material requirements, stock levels, and feasibility status.
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

$prodId = trim($_GET['production_id'] ?? '');
if (empty($prodId)) {
    echo json_encode(['error' => 'Production ID required.']);
    exit;
}

try {
    // Get batch info
    $batch = $pdo->prepare("SELECT po.*, im.item_name FROM production_orders po JOIN item_master im ON po.item_id = im.item_id WHERE po.production_id = ?");
    $batch->execute([$prodId]);
    $batch = $batch->fetch(PDO::FETCH_ASSOC);

    if (!$batch) {
        echo json_encode(['error' => 'Batch not found.']);
        exit;
    }

    // Find recipe — use recipe_id if set, otherwise find by finished_item_id
    if (!empty($batch['recipe_id'])) {
        $recipe = $pdo->prepare("SELECT * FROM recipes WHERE recipe_id = ?");
        $recipe->execute([$batch['recipe_id']]);
    } else {
        $recipe = $pdo->prepare("SELECT * FROM recipes WHERE finished_item_id = ? LIMIT 1");
        $recipe->execute([$batch['item_id']]);
    }
    $recipe = $recipe->fetch(PDO::FETCH_ASSOC);

    if (!$recipe) {
        echo json_encode(['error' => 'No recipe linked to this batch. Please assign a recipe first.']);
        exit;
    }

    $mixRuns = max(1, (float)$batch['target_quantity']);
    $scaleFactor = $mixRuns / max(1, (float)$recipe['base_yield_qty']);

    // Get ingredients
    $ingStmt = $pdo->prepare("
        SELECT ri.raw_material_id, ri.quantity_required, im.item_name, im.base_uom, im.standard_cost
        FROM recipe_ingredients ri
        JOIN item_master im ON ri.raw_material_id = im.item_id
        WHERE ri.recipe_id = ?
        ORDER BY im.item_name
    ");
    $ingStmt->execute([$recipe['recipe_id']]);
    $ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);

    $materials = [];
    $totalCost = 0;
    $feasible = true;

    foreach ($ingredients as $ing) {
        $required = round($ing['quantity_required'] * $scaleFactor, 3);

        // Current stock
        $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
        $stockStmt->execute([$ing['raw_material_id']]);
        $currentStock = (float)$stockStmt->fetchColumn();

        $cost = $required * $ing['standard_cost'];
        $totalCost += $cost;

        if ($currentStock < $required) {
            $feasible = false;
        }

        $materials[] = [
            'item_id'       => $ing['raw_material_id'],
            'item_name'     => $ing['item_name'],
            'uom'           => $ing['base_uom'],
            'per_mix'       => (float)$ing['quantity_required'],
            'required'      => $required,
            'current_stock' => $currentStock,
            'unit_cost'     => (float)$ing['standard_cost'],
            'cost'          => $cost,
        ];
    }

    echo json_encode([
        'production_id' => $prodId,
        'recipe_id'     => $recipe['recipe_id'],
        'recipe_name'   => $recipe['recipe_name'],
        'mix_runs'      => $mixRuns,
        'feasible'      => $feasible,
        'total_cost'    => $totalCost,
        'materials'     => $materials,
    ]);

} catch (Exception $e) {
    error_log("BOM Check Error: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
