<?php
/**
 * MiskStone ERP — Migration v3
 * Creates: supplier_items, po_lines, bom_bills
 * Seeds: supplier_items with all 14 raw materials mapped to suppliers
 */
require_once __DIR__ . '/config/db_connect.php';

echo "=== MiskStone — Migration v3: BOM Integration ===" . PHP_EOL;

// 1. supplier_items — many-to-many linking table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS supplier_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        item_id VARCHAR(50) NOT NULL,
        unit_price DECIMAL(12,2) DEFAULT NULL COMMENT 'Supplier-specific price per unit',
        lead_time_days INT DEFAULT 7 COMMENT 'Typical delivery time in days',
        is_preferred TINYINT(1) DEFAULT 0 COMMENT 'Preferred supplier for this item',
        UNIQUE KEY uq_supplier_item (supplier_id, item_id),
        INDEX idx_supplier (supplier_id),
        INDEX idx_item (item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "✅ supplier_items table created" . PHP_EOL;

// 2. po_lines — multi-item purchase orders
$pdo->exec("
    CREATE TABLE IF NOT EXISTS po_lines (
        line_id INT AUTO_INCREMENT PRIMARY KEY,
        po_id VARCHAR(50) NOT NULL,
        item_id VARCHAR(50) NOT NULL,
        quantity DECIMAL(12,3) NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
        line_total DECIMAL(15,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
        INDEX idx_po (po_id),
        INDEX idx_item (item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "✅ po_lines table created" . PHP_EOL;

// 3. bom_bills — BOM cost tracking linked to production batches
$pdo->exec("
    CREATE TABLE IF NOT EXISTS bom_bills (
        bill_id INT AUTO_INCREMENT PRIMARY KEY,
        production_id VARCHAR(50) NOT NULL,
        recipe_id INT NOT NULL,
        mix_runs INT DEFAULT 1,
        total_material_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
        status ENUM('Generated','Sent to Finance','Acknowledged') DEFAULT 'Generated',
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        generated_by INT DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        INDEX idx_prod (production_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "✅ bom_bills table created" . PHP_EOL;

// 4. bom_bill_lines — detail of what was consumed
$pdo->exec("
    CREATE TABLE IF NOT EXISTS bom_bill_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bill_id INT NOT NULL,
        item_id VARCHAR(50) NOT NULL,
        quantity_consumed DECIMAL(12,3) NOT NULL,
        unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
        line_cost DECIMAL(15,2) GENERATED ALWAYS AS (quantity_consumed * unit_cost) STORED,
        INDEX idx_bill (bill_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "✅ bom_bill_lines table created" . PHP_EOL;


// 5. Seed supplier_items — map all 14 raw materials to suppliers
echo PHP_EOL . "--- Seeding supplier_items ---" . PHP_EOL;

$mappings = [
    // Supplier 1: Jordan Quarry Corp
    ['supplier_id' => 1, 'item_id' => 'RM-001', 'unit_price' => 6.80, 'is_preferred' => 1], // White Cement
    ['supplier_id' => 1, 'item_id' => 'RM-002', 'unit_price' => 5.50, 'is_preferred' => 1], // Grey Cement
    ['supplier_id' => 1, 'item_id' => 'RM-012', 'unit_price' => 0.08, 'is_preferred' => 1], // Water

    // Supplier 2: Turkish Marble Exports
    ['supplier_id' => 2, 'item_id' => 'RM-004', 'unit_price' => 9.60, 'is_preferred' => 1], // Quartz Aggregate
    ['supplier_id' => 2, 'item_id' => 'RM-005', 'unit_price' => 12.00, 'is_preferred' => 1], // Marble Chips
    ['supplier_id' => 2, 'item_id' => 'RM-003', 'unit_price' => 4.20, 'is_preferred' => 1], // Crushed Limestone
    ['supplier_id' => 2, 'item_id' => 'RM-011', 'unit_price' => 1.20, 'is_preferred' => 1], // Sand

    // Supplier 3: Egyptian Aggregate Supply
    ['supplier_id' => 3, 'item_id' => 'RM-008', 'unit_price' => 18.50, 'is_preferred' => 1], // Polyester Resin
    ['supplier_id' => 3, 'item_id' => 'RM-006', 'unit_price' => 22.00, 'is_preferred' => 1], // Iron Oxide (Red)
    ['supplier_id' => 3, 'item_id' => 'RM-007', 'unit_price' => 24.00, 'is_preferred' => 1], // Iron Oxide (Yellow)
    ['supplier_id' => 3, 'item_id' => 'RM-010', 'unit_price' => 8.50, 'is_preferred' => 1], // Silicon Sealant
    ['supplier_id' => 3, 'item_id' => 'RM-013', 'unit_price' => 40.00, 'is_preferred' => 1], // SMF Additive
    ['supplier_id' => 3, 'item_id' => 'RM-009', 'unit_price' => 15.00, 'is_preferred' => 1], // Fiberglass Mesh

    // Supplier 4: Saudi Chemical Solutions
    ['supplier_id' => 4, 'item_id' => 'RM-014', 'unit_price' => 28.00, 'is_preferred' => 1], // Pigment Mix
    ['supplier_id' => 4, 'item_id' => 'RM-013', 'unit_price' => 42.00, 'is_preferred' => 0], // SMF Additive (alt)
    ['supplier_id' => 4, 'item_id' => 'RM-010', 'unit_price' => 9.00, 'is_preferred' => 0],  // Silicon Sealant (alt)

    // Supplier 5: Italian Stone Masters
    ['supplier_id' => 5, 'item_id' => 'RM-005', 'unit_price' => 14.50, 'is_preferred' => 0], // Marble Chips (alt, premium)
    ['supplier_id' => 5, 'item_id' => 'RM-004', 'unit_price' => 11.00, 'is_preferred' => 0], // Quartz Aggregate (alt)
    ['supplier_id' => 5, 'item_id' => 'RM-003', 'unit_price' => 5.00, 'is_preferred' => 0],  // Crushed Limestone (alt)
];

$insertStmt = $pdo->prepare("INSERT IGNORE INTO supplier_items (supplier_id, item_id, unit_price, is_preferred) VALUES (?, ?, ?, ?)");
$count = 0;
foreach ($mappings as $m) {
    $insertStmt->execute([$m['supplier_id'], $m['item_id'], $m['unit_price'], $m['is_preferred']]);
    if ($insertStmt->rowCount() > 0) $count++;
}
echo "  Inserted {$count} supplier-item mappings" . PHP_EOL;

// 6. Update production_orders status enum to include all stages
// Check current enum values
$currentEnum = $pdo->query("SHOW COLUMNS FROM production_orders LIKE 'status'")->fetch();
echo PHP_EOL . "Current production_orders.status: {$currentEnum['Type']}" . PHP_EOL;

// The enum already has Planned, Mixing, Curing, Completed, Failed — verify
if (strpos($currentEnum['Type'], 'Mixing') !== false) {
    echo "✅ Status enum already has detailed stages" . PHP_EOL;
} else {
    $pdo->exec("ALTER TABLE production_orders MODIFY status ENUM('Planned','Mixing','Curing','Completed','Failed') DEFAULT 'Planned'");
    echo "✅ Updated status enum to detailed stages" . PHP_EOL;
}

// 7. Add recipe_id to production_orders if missing
$hasRecipeCol = $pdo->query("SHOW COLUMNS FROM production_orders LIKE 'recipe_id'")->rowCount();
if ($hasRecipeCol === 0) {
    $pdo->exec("ALTER TABLE production_orders ADD COLUMN recipe_id INT DEFAULT NULL AFTER item_id");
    echo "✅ Added recipe_id column to production_orders" . PHP_EOL;
} else {
    echo "✅ recipe_id column already exists" . PHP_EOL;
}

// 8. Add so_id to production_orders if missing (link to sales order)
$hasSoCol = $pdo->query("SHOW COLUMNS FROM production_orders LIKE 'so_id'")->rowCount();
if ($hasSoCol === 0) {
    $pdo->exec("ALTER TABLE production_orders ADD COLUMN so_id VARCHAR(50) DEFAULT NULL AFTER recipe_id");
    echo "✅ Added so_id column to production_orders" . PHP_EOL;
} else {
    echo "✅ so_id column already exists" . PHP_EOL;
}

// Summary
echo PHP_EOL . "=== Migration v3 Complete ===" . PHP_EOL;
echo "Tables: supplier_items, po_lines, bom_bills, bom_bill_lines" . PHP_EOL;
echo "Supplier-item mappings seeded" . PHP_EOL;
echo "Production stages verified" . PHP_EOL;
