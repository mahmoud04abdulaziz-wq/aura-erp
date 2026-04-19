<?php
/**
 * AURA ERP — Data Fix Script
 * 1. Seed mix_outputs so each recipe produces multiple FG items
 * 2. Fix raw material inventory levels (ensure positive, healthy stock)
 * 3. Fix finance ledger (ensure net-positive economy)
 * 4. Sync finished goods stock
 * 5. Add supplier contact info
 */
require_once __DIR__ . '/config/db_connect.php';

echo "=== AURA ERP Data Fix ===" . PHP_EOL;

// ──────────────────────────────────────────────
// 1. SEED MIX OUTPUTS (One mix → Many FG items)
// ──────────────────────────────────────────────
echo PHP_EOL . "--- 1. Seeding Mix Outputs ---" . PHP_EOL;

// Clear existing and re-seed for clean state
$pdo->exec("DELETE FROM mix_outputs");

$mixOutputs = [
    // Recipe 1: Standard Artificial Stone Mix
    1 => [
        ['FG-001', 7],   // Marble Slab 120x60
        ['FG-004', 5],   // Kitchen Countertop Slab
        ['FG-ST01', 10], // Patterned Tile
        ['FG-ST04', 8],  // Plain Tile
        ['FG-ST03', 6],  // Sand Tile
        ['FG-SC03', 4],  // Cornice 25
    ],
    // Recipe 2: Granite Texture Mix
    2 => [
        ['FG-002', 13],  // Granite Tile 60x60
        ['FG-005', 4],   // Wall Cladding Tile
        ['FG-ST02', 6],  // Sesame Tile
        ['FG-SCC1', 5],  // Corner 15
        ['FG-ST05', 7],  // Tabiza Tile
        ['FG-SC04', 3],  // Cornice 20
    ],
    // Recipe 3: Decorative Pattern Mix
    3 => [
        ['FG-003', 6],   // Decorative Stone Panel
        ['FG-006', 3],   // Bathroom Vanity Top
        ['FG-SC01', 8],  // Stair Cornice
        ['FG-COL1', 5],  // Solid Column
        ['FG-C01', 4],   // Crown 50
        ['FG-C03', 4],   // Base 50
    ],
];

$insertOutput = $pdo->prepare("INSERT INTO mix_outputs (recipe_id, item_id, output_quantity) VALUES (?, ?, ?)");
foreach ($mixOutputs as $recipeId => $outputs) {
    foreach ($outputs as [$itemId, $qty]) {
        try {
            $insertOutput->execute([$recipeId, $itemId, $qty]);
            echo "  ✅ Recipe $recipeId → $itemId × $qty" . PHP_EOL;
        } catch (PDOException $e) {
            echo "  ❌ Recipe $recipeId → $itemId: " . $e->getMessage() . PHP_EOL;
        }
    }
}

// Also update recipes.base_yield_qty to reflect the total output per mix run
$pdo->exec("UPDATE recipes SET base_yield_qty = 1 WHERE recipe_id IN (1,2,3)");
echo "  ✅ Updated base_yield_qty to 1 (outputs are per single mix run)" . PHP_EOL;

// ──────────────────────────────────────────────
// 2. FIX RAW MATERIAL INVENTORY LEVELS
// ──────────────────────────────────────────────
echo PHP_EOL . "--- 2. Fixing Raw Material Inventory ---" . PHP_EOL;

$rawMaterials = $pdo->query("SELECT item_id, item_name, min_stock_level FROM item_master WHERE category = 'Raw Material'")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rawMaterials as $rm) {
    // Get current stock from inventory_ledger
    $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
    $stockStmt->execute([$rm['item_id']]);
    $currentStock = (float)$stockStmt->fetchColumn();

    // Target: at least 2× min_stock_level, or 2000 if no min set
    $minLevel = (float)$rm['min_stock_level'];
    $targetStock = max($minLevel * 3, 2000);

    if ($currentStock < $targetStock) {
        $needed = $targetStock - $currentStock;
        $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by)
            VALUES (?, 1, 'Receipt', ?, 'INIT-STOCK-FIX', 1)")
            ->execute([$rm['item_id'], $needed]);
        echo "  ✅ {$rm['item_name']}: added $needed (was $currentStock → now $targetStock)" . PHP_EOL;
    } else {
        echo "  ⏭️  {$rm['item_name']}: stock OK ($currentStock)" . PHP_EOL;
    }
}

// ──────────────────────────────────────────────
// 3. FIX FINANCE LEDGER (net-positive economy)
// ──────────────────────────────────────────────
echo PHP_EOL . "--- 3. Fixing Finance Ledger ---" . PHP_EOL;

$totalIncome = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM finance_ledger WHERE transaction_type = 'Income'")->fetchColumn();
$totalExpense = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM finance_ledger WHERE transaction_type = 'Expense'")->fetchColumn();
$net = $totalIncome - $totalExpense;

echo "  Current: Income=$totalIncome, Expense=$totalExpense, Net=$net" . PHP_EOL;

if ($net < 5000) {
    // Add some initial capital and sales revenue entries
    $entries = [
        ['INC-CAPITAL-001', 'Income', 'Initial Capital', 25000, 'Company Investment'],
        ['INC-SALES-001', 'Income', 'Sales Revenue', 8500, 'Batch Sales Q1'],
        ['INC-SALES-002', 'Income', 'Sales Revenue', 6200, 'Batch Sales Q2'],
        ['INC-SALES-003', 'Income', 'Sales Revenue', 4800, 'Export Order March'],
    ];

    $insertLedger = $pdo->prepare("INSERT IGNORE INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), ?, ?, ?, ?, 1)");
    foreach ($entries as [$txId, $type, $cat, $amount, $ref]) {
        try {
            $insertLedger->execute([$txId, $type, $cat, $amount, $ref]);
            echo "  ✅ Added $type: $cat \$$amount" . PHP_EOL;
        } catch (PDOException $e) {
            echo "  ⏭️  $txId already exists, skipping" . PHP_EOL;
        }
    }

    $newIncome = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM finance_ledger WHERE transaction_type = 'Income'")->fetchColumn();
    $newNet = $newIncome - $totalExpense;
    echo "  Updated: Income=$newIncome, Net=$newNet" . PHP_EOL;
} else {
    echo "  ⏭️  Economy already positive (Net: \$$net)" . PHP_EOL;
}

// ──────────────────────────────────────────────
// 4. SYNC FINISHED GOODS STOCK
// ──────────────────────────────────────────────
echo PHP_EOL . "--- 4. Syncing Finished Goods Stock ---" . PHP_EOL;

$fgItems = $pdo->query("SELECT item_id, item_name FROM item_master WHERE category = 'Finished Good'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($fgItems as $fg) {
    $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
    $stockStmt->execute([$fg['item_id']]);
    $ledgerStock = (float)$stockStmt->fetchColumn();

    // Ensure inventory_finished_goods row exists and is synced
    $existsStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_finished_goods WHERE item_id = ?");
    $existsStmt->execute([$fg['item_id']]);

    if ($existsStmt->fetchColumn() > 0) {
        $pdo->prepare("UPDATE inventory_finished_goods SET quantity_in_stock = ? WHERE item_id = ?")->execute([max(0, $ledgerStock), $fg['item_id']]);
    } else {
        $pdo->prepare("INSERT INTO inventory_finished_goods (item_id, item_name, category, quantity_in_stock, unit_cost) VALUES (?, ?, 'Finished Good', ?, 0)")
            ->execute([$fg['item_id'], $fg['item_name'], max(0, $ledgerStock)]);
    }
}
echo "  ✅ Synced " . count($fgItems) . " finished goods with inventory_ledger" . PHP_EOL;

// ──────────────────────────────────────────────
// 5. ADD SUPPLIER CONTACT INFO
// ──────────────────────────────────────────────
echo PHP_EOL . "--- 5. Adding Supplier Contact Info ---" . PHP_EOL;

$supplierUpdates = [
    [1, 'Ahmad Al-Rashid', '+962-79-555-1001', 'ahmad@rashidcement.jo', 'Industrial Zone, Zarqa', 'White Cement, Grey Cement'],
    [2, 'Mohammad Jabari', '+962-79-555-1002', 'info@jabari-minerals.jo', 'Fuheis, Balqa', 'Quartz Aggregate, Marble Chips, Crushed Limestone, Sand'],
    [3, 'Sara Kasim', '+962-79-555-1003', 'sara@chemicals-jo.com', 'Sahab Industrial Area', 'Polyester Resin, Iron Oxide Pigments, Silicon Sealant, SMF Additive'],
];

$updateSupplier = $pdo->prepare("UPDATE suppliers SET contact_person=?, phone=?, email=?, address=?, supplies_items=? WHERE supplier_id=?");
foreach ($supplierUpdates as [$id, $contact, $phone, $email, $addr, $items]) {
    try {
        $updateSupplier->execute([$contact, $phone, $email, $addr, $items, $id]);
        echo "  ✅ Supplier $id: $contact" . PHP_EOL;
    } catch (PDOException $e) {
        echo "  ❌ Supplier $id: " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "=== Data Fix Complete ===" . PHP_EOL;
