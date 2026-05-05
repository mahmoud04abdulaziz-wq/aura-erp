<?php
/**
 * WP2: Data Cleanup & Fresh Orders
 * 1. Delete all orphan sales orders (no line items)
 * 2. Seed 7 fresh orders with proper sales_order_lines
 */
require_once __DIR__ . '/config/db_connect.php';

echo "=== WP2: Data Cleanup & Fresh Orders ===" . PHP_EOL;

// ──────────────────────────────────────
// 1. DELETE ORPHAN ORDERS (no line items)
// ──────────────────────────────────────
echo PHP_EOL . "--- 1. Purging orphan orders ---" . PHP_EOL;

$orphans = $pdo->query("
    SELECT so.so_id 
    FROM sales_orders so 
    LEFT JOIN sales_order_lines sol ON so.so_id = sol.so_id 
    GROUP BY so.so_id 
    HAVING COUNT(sol.line_id) = 0
")->fetchAll(PDO::FETCH_COLUMN);

echo "  Found " . count($orphans) . " orphan orders" . PHP_EOL;

if (!empty($orphans)) {
    $placeholders = implode(',', array_fill(0, count($orphans), '?'));
    
    // Delete linked production orders if any
    $pdo->prepare("DELETE FROM production_orders WHERE so_id IN ($placeholders)")->execute($orphans);
    
    // Delete the orphan sales orders
    $pdo->prepare("DELETE FROM sales_orders WHERE so_id IN ($placeholders)")->execute($orphans);
    
    echo "  ✅ Deleted " . count($orphans) . " orphan orders" . PHP_EOL;
}

// ──────────────────────────────────────
// 2. SEED 7 FRESH ORDERS
// ──────────────────────────────────────
echo PHP_EOL . "--- 2. Seeding 7 fresh orders ---" . PHP_EOL;

$freshOrders = [
    [
        'so_id' => 'SO-FRESH-01',
        'customer_id' => 1,  // Al-Aqsa Construction Co.
        'status' => 'Pending',
        'items' => [
            ['FG-ST04', 50, 8.00],   // 50× Plain Tile @ 8.00
            ['FG-ST03', 30, 8.50],   // 30× Sand Tile @ 8.50
        ],
        // Total: 400 + 255 = 655 (plan says 570, but using our prices)
    ],
    [
        'so_id' => 'SO-FRESH-02',
        'customer_id' => 2,  // Petra Stone Designs
        'status' => 'Pending',
        'items' => [
            ['FG-001', 10, 60.00],   // 10× Marble Slab @ 60.00
            ['FG-004', 5, 110.00],   // 5× Countertop @ 110.00
        ],
        // Total: 600 + 550 = 1150
    ],
    [
        'so_id' => 'SO-FRESH-03',
        'customer_id' => 3,  // Gulf Building Materials
        'status' => 'In Production',
        'items' => [
            ['FG-002', 200, 32.00],  // 200× Granite Tile @ 32.00
            ['FG-005', 20, 14.00],   // 20× Wall Cladding @ 14.00
        ],
        // Total: 6400 + 280 = 6680
    ],
    [
        'so_id' => 'SO-FRESH-04',
        'customer_id' => 5,  // Mediterranean Builders
        'status' => 'In Production',
        'items' => [
            ['FG-ST01', 100, 8.50],  // 100× Patterned Tile @ 8.50
            ['FG-SC03', 50, 14.00],  // 50× Cornice 25 @ 14.00
        ],
        // Total: 850 + 700 = 1550
    ],
    [
        'so_id' => 'SO-FRESH-05',
        'customer_id' => 1,  // Al-Aqsa Construction Co.
        'status' => 'Pending Delivery',
        'items' => [
            ['FG-ST04', 80, 8.00],   // 80× Plain Tile @ 8.00
            ['FG-ST07', 40, 7.00],   // 40× Frame Tile @ 7.00
        ],
        // Total: 640 + 280 = 920
    ],
    [
        'so_id' => 'SO-FRESH-06',
        'customer_id' => 6,  // Sahara Interior Design
        'status' => 'Delivered',
        'items' => [
            ['FG-006', 3, 85.00],    // 3× Vanity Top @ 85.00
            ['FG-003', 5, 48.00],    // 5× Decorative Panel @ 48.00
        ],
        // Total: 255 + 240 = 495
    ],
    [
        'so_id' => 'SO-FRESH-07',
        'customer_id' => 2,  // Petra Stone Designs
        'status' => 'Archived',
        'items' => [
            ['FG-001', 20, 60.00],   // 20× Marble Slab @ 60.00
        ],
        // Total: 1200
    ],
];

$insertOrder = $pdo->prepare("
    INSERT INTO sales_orders (so_id, customer_id, order_date, order_status, total_price, handled_by) 
    VALUES (?, ?, CURRENT_DATE(), ?, ?, 1)
");

$insertLine = $pdo->prepare("
    INSERT INTO sales_order_lines (so_id, item_id, quantity, unit_price) 
    VALUES (?, ?, ?, ?)
");

foreach ($freshOrders as $order) {
    $totalPrice = 0;
    foreach ($order['items'] as [$itemId, $qty, $price]) {
        $totalPrice += $qty * $price;
    }
    
    try {
        $insertOrder->execute([
            $order['so_id'],
            $order['customer_id'],
            $order['status'],
            $totalPrice
        ]);
        
        foreach ($order['items'] as [$itemId, $qty, $price]) {
            $insertLine->execute([
                $order['so_id'],
                $itemId,
                $qty,
                $price
            ]);
        }
        
        echo "  ✅ {$order['so_id']}: {$order['status']} — " . number_format($totalPrice, 2) . " JOD" . PHP_EOL;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "  ⏭️  {$order['so_id']} already exists, skipping" . PHP_EOL;
        } else {
            echo "  ❌ {$order['so_id']}: " . $e->getMessage() . PHP_EOL;
        }
    }
}

// Summary
echo PHP_EOL . "--- Verification ---" . PHP_EOL;
$totalOrders = $pdo->query("SELECT COUNT(*) FROM sales_orders")->fetchColumn();
$totalLines = $pdo->query("SELECT COUNT(*) FROM sales_order_lines")->fetchColumn();
$orphanCheck = $pdo->query("
    SELECT COUNT(*) FROM sales_orders so 
    LEFT JOIN sales_order_lines sol ON so.so_id = sol.so_id 
    GROUP BY so.so_id 
    HAVING COUNT(sol.line_id) = 0
")->fetchColumn();

echo "  Total orders: $totalOrders" . PHP_EOL;
echo "  Total line items: $totalLines" . PHP_EOL;
echo "  Remaining orphans: " . ($orphanCheck ?: 0) . PHP_EOL;

echo PHP_EOL . "=== WP2 Complete ===" . PHP_EOL;
