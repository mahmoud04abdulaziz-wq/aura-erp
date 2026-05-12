<?php
/**
 * AURA ERP — Data Fix & Seed Script
 * 1. Delete orphan orders (no line items)
 * 2. Add lead_time_days to suppliers
 * 3. Restock raw materials for testing
 * 4. Create fresh orders with proper line items
 * 5. Auto-archive completed mixes older than 7 days
 */
require_once __DIR__ . '/config/db_connect.php';

echo "=== AURA ERP: Data Fix & Seed ===\n\n";

// ───────────────────────────────────────────
// 1. DELETE ORPHAN ORDERS (no line items)
// ───────────────────────────────────────────
echo "[1] Cleaning orphan orders...\n";

// Find orders with zero line items
$orphans = $pdo->query("
    SELECT so.so_id, so.order_status, so.total_price
    FROM sales_orders so
    LEFT JOIN sales_order_lines sol ON so.so_id = sol.so_id
    WHERE sol.line_id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

echo "    Found " . count($orphans) . " orphan orders.\n";

foreach ($orphans as $o) {
    // Delete related production orders first (FK constraint)
    $pdo->exec("DELETE FROM production_orders WHERE so_id = '{$o['so_id']}'");
    // Delete the order itself
    $pdo->exec("DELETE FROM sales_orders WHERE so_id = '{$o['so_id']}'");
    echo "    ✓ Deleted: {$o['so_id']} (was {$o['order_status']}, {$o['total_price']} JOD)\n";
}

// Also clean orders that DO have line items but are Delivered/Archived (keep pipeline clean)
// Don't delete these - just report
$delivered = $pdo->query("SELECT COUNT(*) FROM sales_orders WHERE order_status IN ('Delivered','Archived')")->fetchColumn();
echo "    ℹ {$delivered} delivered/archived orders kept for history.\n\n";

// ───────────────────────────────────────────
// 2. ADD LEAD TIME TO SUPPLIERS
// ───────────────────────────────────────────
echo "[2] Adding lead_time_days to suppliers...\n";

try {
    $pdo->exec("ALTER TABLE suppliers ADD COLUMN lead_time_days INT DEFAULT 7 AFTER address");
    echo "    ✓ Column added.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "    ℹ Column already exists.\n";
    } else {
        echo "    ✗ Error: " . $e->getMessage() . "\n";
    }
}

// Set differentiated lead times
$leadTimes = [
    1 => ['name' => 'Jordan Quarry Corp',         'days' => 3],   // Local supplier - fast
    2 => ['name' => 'Turkish Marble Exports',       'days' => 14],  // International - slow
    3 => ['name' => 'Egyptian Aggregate Supply',    'days' => 7],   // Regional - medium
    4 => ['name' => 'Saudi Chemical Solutions',     'days' => 10],  // Regional - medium-slow
    5 => ['name' => 'Italian Stone Masters',        'days' => 21],  // Europe - slowest
];

$updateLead = $pdo->prepare("UPDATE suppliers SET lead_time_days = ? WHERE supplier_id = ?");
foreach ($leadTimes as $id => $info) {
    $updateLead->execute([$info['days'], $id]);
    echo "    ✓ {$info['name']}: {$info['days']} days lead time\n";
}
echo "\n";

// ───────────────────────────────────────────
// 3. RESTOCK RAW MATERIALS FOR TESTING
// ───────────────────────────────────────────
echo "[3] Restocking raw materials...\n";

// Raw materials are tracked in item_master via min_stock_level
// But actual stock is in a different mechanism - let's check
// The BOM system uses bom_bills to track consumption
// For testing, we need to ensure recipe_ingredients can be fulfilled

// Check current stock via inventory approach - the system tracks via bom_bills
// Let's reset/set reasonable stock levels
$rawMaterials = [
    'RM-001' => ['name' => 'White Cement',           'stock' => 5000, 'min' => 500],
    'RM-002' => ['name' => 'Grey Cement',             'stock' => 5000, 'min' => 500],
    'RM-003' => ['name' => 'Crushed Limestone',       'stock' => 3000, 'min' => 300],
    'RM-004' => ['name' => 'Quartz Aggregate',        'stock' => 8000, 'min' => 800],
    'RM-005' => ['name' => 'Marble Chips',            'stock' => 2000, 'min' => 200],
    'RM-006' => ['name' => 'Iron Oxide Pigment (Red)','stock' => 500,  'min' => 50],
    'RM-007' => ['name' => 'Iron Oxide Pigment (Yellow)', 'stock' => 500, 'min' => 50],
    'RM-008' => ['name' => 'Polyester Resin',         'stock' => 1000, 'min' => 100],
    'RM-009' => ['name' => 'Fiberglass Mesh',         'stock' => 500,  'min' => 50],
    'RM-010' => ['name' => 'Silicon Sealant',         'stock' => 300,  'min' => 30],
    'RM-011' => ['name' => 'Sand',                    'stock' => 10000,'min' => 1000],
    'RM-012' => ['name' => 'Water',                   'stock' => 50000,'min' => 5000],
    'RM-013' => ['name' => 'SMF Additive',            'stock' => 200,  'min' => 20],
    'RM-014' => ['name' => 'Pigment Mix',             'stock' => 300,  'min' => 30],
];

// Check if raw_material_stock table exists or if stock is tracked elsewhere
$tables = $pdo->query("SHOW TABLES LIKE 'raw_material_stock'")->fetchAll();
if (empty($tables)) {
    // Stock is tracked via item_master min_stock_level and the BOM consumption
    // Let's create a simple stock tracking table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS raw_material_stock (
            item_id VARCHAR(50) PRIMARY KEY,
            current_stock DECIMAL(12,3) DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "    ✓ Created raw_material_stock table.\n";
}

$upsertStock = $pdo->prepare("
    INSERT INTO raw_material_stock (item_id, current_stock) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE current_stock = VALUES(current_stock)
");
$updateMin = $pdo->prepare("UPDATE item_master SET min_stock_level = ? WHERE item_id = ?");

foreach ($rawMaterials as $id => $info) {
    $upsertStock->execute([$id, $info['stock']]);
    $updateMin->execute([$info['min'], $id]);
    echo "    ✓ {$info['name']}: {$info['stock']} units (min: {$info['min']})\n";
}
echo "\n";

// ───────────────────────────────────────────
// 4. CREATE FRESH ORDERS WITH LINE ITEMS
// ───────────────────────────────────────────
echo "[4] Creating fresh test orders with line items...\n";

// Get employee IDs for handled_by
$salesEmployee = $pdo->query("
    SELECT e.employee_id FROM employees e
    JOIN roles r ON e.role_id = r.role_id
    WHERE r.role_name = 'Sales'
    LIMIT 1
")->fetchColumn() ?: 1;

$newOrders = [
    [
        'so_id' => 'SO-260505-1001',
        'customer_id' => 1,
        'date' => '2026-05-05',
        'status' => 'Pending',
        'delivery' => 'Amman, Jordan',
        'payment' => 'Bank Transfer',
        'items' => [
            ['item_id' => 'FG-001', 'qty' => 50, 'price' => 65.25],   // Marble Slab
            ['item_id' => 'FG-005', 'qty' => 200, 'price' => 21.75],  // Wall Cladding
        ]
    ],
    [
        'so_id' => 'SO-260505-1002',
        'customer_id' => 2,
        'date' => '2026-05-05',
        'status' => 'Pending',
        'delivery' => 'Aqaba, Jordan',
        'payment' => 'Credit Terms (30)',
        'items' => [
            ['item_id' => 'FG-002', 'qty' => 100, 'price' => 50.75],  // Granite Tile
            ['item_id' => 'FG-003', 'qty' => 30, 'price' => 94.25],   // Decorative Panel
        ]
    ],
    [
        'so_id' => 'SO-260505-1003',
        'customer_id' => 3,
        'date' => '2026-05-05',
        'status' => 'Pending',
        'delivery' => 'Dubai, UAE',
        'payment' => 'Wire Transfer',
        'items' => [
            ['item_id' => 'FG-004', 'qty' => 20, 'price' => 174.00],  // Kitchen Countertop
            ['item_id' => 'FG-006', 'qty' => 15, 'price' => 123.25],  // Bathroom Vanity
            ['item_id' => 'FG-001', 'qty' => 40, 'price' => 65.25],   // Marble Slab
        ]
    ],
    [
        'so_id' => 'SO-260505-1004',
        'customer_id' => 5,
        'date' => '2026-05-05',
        'status' => 'Pending',
        'delivery' => 'Athens, Greece',
        'payment' => 'Letter of Credit',
        'items' => [
            ['item_id' => 'FG-C01', 'qty' => 500, 'price' => 29.00],  // Crown 50
            ['item_id' => 'FG-C02', 'qty' => 500, 'price' => 26.10],  // Crown 40
            ['item_id' => 'FG-SC01', 'qty' => 300, 'price' => 17.40], // Stair Cornice
        ]
    ],
    [
        'so_id' => 'SO-260505-1005',
        'customer_id' => 1,
        'date' => '2026-05-05',
        'status' => 'Pending',
        'delivery' => 'Irbid, Jordan',
        'payment' => 'Cash on Delivery',
        'items' => [
            ['item_id' => 'FG-ST01', 'qty' => 1000, 'price' => 12.33],  // Patterned Tile
            ['item_id' => 'FG-ST04', 'qty' => 800, 'price' => 11.60],   // Plain Tile
        ]
    ],
];

$insertOrder = $pdo->prepare("
    INSERT INTO sales_orders (so_id, customer_id, order_date, total_price, delivery_location, order_status, handled_by, payment_method)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$insertLine = $pdo->prepare("
    INSERT INTO sales_order_lines (so_id, item_id, quantity, unit_price)
    VALUES (?, ?, ?, ?)
");

foreach ($newOrders as $order) {
    $total = 0;
    foreach ($order['items'] as $item) {
        $total += $item['qty'] * $item['price'];
    }

    try {
        $insertOrder->execute([
            $order['so_id'],
            $order['customer_id'],
            $order['date'],
            $total,
            $order['delivery'],
            $order['status'],
            $salesEmployee,
            $order['payment']
        ]);

        foreach ($order['items'] as $item) {
            $insertLine->execute([
                $order['so_id'],
                $item['item_id'],
                $item['qty'],
                $item['price']
            ]);
        }

        $lineCount = count($order['items']);
        echo "    ✓ {$order['so_id']} — " . number_format($total, 2) . " JOD ({$lineCount} items)\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "    ℹ {$order['so_id']} already exists, skipping.\n";
        } else {
            echo "    ✗ {$order['so_id']}: " . $e->getMessage() . "\n";
        }
    }
}
echo "\n";

// ───────────────────────────────────────────
// 5. AUTO-ARCHIVE COMPLETED MIXES > 7 DAYS
// ───────────────────────────────────────────
echo "[5] Auto-archiving old completed production batches...\n";

// Archive production orders that have been Completed for > 7 days
$archiveResult = $pdo->exec("
    UPDATE production_orders
    SET status = 'Completed', qa_status = 'Passed'
    WHERE status = 'Completed'
      AND qa_status = 'Passed'
      AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
");
// Note: There's no 'Archived' status in production_orders enum
// Let's check what statuses are available
$statusEnum = $pdo->query("SHOW COLUMNS FROM production_orders LIKE 'status'")->fetch();
echo "    Production status options: {$statusEnum['Type']}\n";

// Count old completed batches
$oldCompleted = $pdo->query("
    SELECT COUNT(*) FROM production_orders
    WHERE status = 'Completed' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetchColumn();
echo "    Found {$oldCompleted} completed batches older than 7 days.\n";

// Since there's no 'Archived' status, let's add it
try {
    $pdo->exec("ALTER TABLE production_orders MODIFY COLUMN status ENUM('Planned','Mixing','Curing','Completed','Failed','Archived') DEFAULT 'Planned'");
    echo "    ✓ Added 'Archived' status to production_orders.\n";

    // Now archive old completed ones
    $archived = $pdo->exec("
        UPDATE production_orders
        SET status = 'Archived'
        WHERE status = 'Completed'
          AND qa_status = 'Passed'
          AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    echo "    ✓ Archived {$archived} old completed batches.\n";
} catch (PDOException $e) {
    echo "    ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ───────────────────────────────────────────
// 6. SUMMARY
// ───────────────────────────────────────────
echo "=== SUMMARY ===\n";
$orderCount = $pdo->query("SELECT COUNT(*) FROM sales_orders WHERE order_status NOT IN ('Delivered','Archived')")->fetchColumn();
$lineCount = $pdo->query("SELECT COUNT(*) FROM sales_order_lines")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM sales_orders WHERE order_status = 'Pending'")->fetchColumn();
$prodCount = $pdo->query("SELECT COUNT(*) FROM production_orders WHERE status NOT IN ('Completed','Archived','Failed')")->fetchColumn();
$recipeCount = $pdo->query("SELECT COUNT(*) FROM recipes")->fetchColumn();

echo "  Active orders: {$orderCount}\n";
echo "  Total line items: {$lineCount}\n";
echo "  Pending (ready for production): {$pendingOrders}\n";
echo "  Active production batches: {$prodCount}\n";
echo "  Recipes available: {$recipeCount}\n";
echo "\n=== Done! System ready for production testing. ===\n";
