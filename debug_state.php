<?php
require_once __DIR__ . '/config/db_connect.php';

echo "=== RAW MATERIAL COSTS ===" . PHP_EOL;
$raws = $pdo->query("SELECT item_id, item_name, standard_cost, base_uom FROM item_master WHERE category = 'Raw Material' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($raws as $r) echo "  {$r['item_name']} | \${$r['standard_cost']}/{$r['base_uom']}" . PHP_EOL;

echo PHP_EOL . "=== RECIPES & THEIR INGREDIENTS ===" . PHP_EOL;
$recipes = $pdo->query("
    SELECT r.recipe_id, r.recipe_name, r.output_qty, r.output_uom, r.curing_time_hours,
           fg.item_name as fg_name, fg.item_id as fg_item_id
    FROM recipes r
    LEFT JOIN item_master fg ON r.output_item_id = fg.item_id
    ORDER BY r.recipe_name
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($recipes as $rec) {
    echo PHP_EOL . "  [{$rec['recipe_id']}] {$rec['recipe_name']}" . PHP_EOL;
    echo "    Output: {$rec['output_qty']} {$rec['output_uom']} of {$rec['fg_name']}" . PHP_EOL;
    echo "    Curing: {$rec['curing_time_hours']}h" . PHP_EOL;
    
    $ings = $pdo->prepare("
        SELECT ri.quantity, ri.uom, im.item_name, im.standard_cost
        FROM recipe_ingredients ri
        JOIN item_master im ON ri.item_id = im.item_id
        WHERE ri.recipe_id = ?
    ");
    $ings->execute([$rec['recipe_id']]);
    $ingredients = $ings->fetchAll(PDO::FETCH_ASSOC);
    
    $totalCost = 0;
    foreach ($ingredients as $ing) {
        $lineCost = $ing['quantity'] * $ing['standard_cost'];
        $totalCost += $lineCost;
        echo "    - {$ing['item_name']}: {$ing['quantity']} {$ing['uom']} × \${$ing['standard_cost']} = \${$lineCost}" . PHP_EOL;
    }
    echo "    TOTAL COST: \${$totalCost}" . PHP_EOL;
    if ($rec['output_qty'] > 0) {
        $costPerUnit = round($totalCost / $rec['output_qty'], 2);
        echo "    COST PER UNIT: \${$costPerUnit}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== FINISHED GOODS SUMMARY ===" . PHP_EOL;
$fgs = $pdo->query("SELECT item_id, item_name, standard_cost FROM item_master WHERE category = 'Finished Good' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($fgs as $fg) echo "  {$fg['item_id']} | {$fg['item_name']} | current std_cost: \${$fg['standard_cost']}" . PHP_EOL;

echo PHP_EOL . "=== RECIPE OUTPUT MAPPING (which recipes make which FG) ===" . PHP_EOL;
$map = $pdo->query("
    SELECT fg.item_name as fg_name, r.recipe_name, r.output_qty, r.recipe_id
    FROM recipes r
    JOIN item_master fg ON r.output_item_id = fg.item_id
    ORDER BY fg.item_name, r.recipe_name
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($map as $m) echo "  {$m['fg_name']} ← {$m['recipe_name']} (yields {$m['output_qty']})" . PHP_EOL;
