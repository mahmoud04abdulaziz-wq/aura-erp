<?php
/**
 * MiskStone ERP — Fill Raw Materials for BOM Testing
 * Seeds sufficient raw material inventory and verifies BOM configuration.
 */
require_once __DIR__ . '/config/db_connect.php';

echo "=== MiskStone — Raw Material Fill & BOM Verification ===" . PHP_EOL . PHP_EOL;

// 1. Get all raw materials
$rawMaterials = $pdo->query("SELECT item_id, item_name, standard_cost, base_uom FROM item_master WHERE category = 'Raw Material'")->fetchAll(PDO::FETCH_ASSOC);

echo "--- Raw Materials Found: " . count($rawMaterials) . " ---" . PHP_EOL;

foreach ($rawMaterials as $rm) {
    // Current stock
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
    $stmt->execute([$rm['item_id']]);
    $currentStock = (float)$stmt->fetchColumn();
    
    // Target: at least 5,000 units of each raw material
    $target = 5000;
    $needed = $target - $currentStock;
    
    echo "  {$rm['item_name']} ({$rm['item_id']}): Current={$currentStock} {$rm['base_uom']}";
    
    if ($needed > 0) {
        $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Adjustment', ?, 'Initial Stock Fill', 1)")
            ->execute([$rm['item_id'], $needed]);
        echo " → Added {$needed} → Now {$target}" . PHP_EOL;
    } else {
        echo " ✅ Already sufficient" . PHP_EOL;
    }
}

// 2. Verify Recipes have ingredients
echo PHP_EOL . "--- BOM / Recipe Verification ---" . PHP_EOL;
$recipes = $pdo->query("SELECT recipe_id, recipe_name, base_yield_qty FROM recipes")->fetchAll(PDO::FETCH_ASSOC);

foreach ($recipes as $recipe) {
    echo PHP_EOL . "  Recipe #{$recipe['recipe_id']}: {$recipe['recipe_name']}" . PHP_EOL;
    echo "    Base Yield: {$recipe['base_yield_qty']} mix run(s)" . PHP_EOL;
    
    // Ingredients
    $ingStmt = $pdo->prepare("
        SELECT ri.raw_material_id, ri.quantity_required, im.item_name, im.base_uom 
        FROM recipe_ingredients ri 
        JOIN item_master im ON ri.raw_material_id = im.item_id 
        WHERE ri.recipe_id = ?
    ");
    $ingStmt->execute([$recipe['recipe_id']]);
    $ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "    Inputs: " . count($ingredients) . " raw materials" . PHP_EOL;
    foreach ($ingredients as $ing) {
        echo "      ↓ {$ing['item_name']}: {$ing['quantity_required']} {$ing['base_uom']} per mix" . PHP_EOL;
    }
    
    // Outputs (mix_outputs)
    $outStmt = $pdo->prepare("
        SELECT mo.item_id, mo.output_quantity, im.item_name
        FROM mix_outputs mo
        JOIN item_master im ON mo.item_id = im.item_id
        WHERE mo.recipe_id = ?
        ORDER BY mo.output_quantity DESC
    ");
    $outStmt->execute([$recipe['recipe_id']]);
    $outputs = $outStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "    Outputs: " . count($outputs) . " finished goods" . PHP_EOL;
    $totalOutput = 0;
    foreach ($outputs as $out) {
        echo "      ↑ {$out['output_quantity']}× {$out['item_name']}" . PHP_EOL;
        $totalOutput += $out['output_quantity'];
    }
    echo "    Total FG per mix run: {$totalOutput} units" . PHP_EOL;
}

// 3. Verify FG Stock
echo PHP_EOL . "--- Finished Goods Stock ---" . PHP_EOL;
$fgItems = $pdo->query("SELECT item_id, item_name FROM item_master WHERE category = 'Finished Good'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($fgItems as $fg) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
    $stmt->execute([$fg['item_id']]);
    $stock = (float)$stmt->fetchColumn();
    echo "  {$fg['item_name']} ({$fg['item_id']}): {$stock} units" . PHP_EOL;
}

// 4. Finance check
echo PHP_EOL . "--- Finance Ledger ---" . PHP_EOL;
$income = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM finance_ledger WHERE transaction_type = 'Income'")->fetchColumn();
$expense = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM finance_ledger WHERE transaction_type = 'Expense'")->fetchColumn();
echo "  Income: \$" . number_format($income, 2) . PHP_EOL;
echo "  Expense: \$" . number_format($expense, 2) . PHP_EOL;
echo "  Net: \$" . number_format($income - $expense, 2) . PHP_EOL;

echo PHP_EOL . "=== Done! Raw materials filled for BOM testing ===" . PHP_EOL;
