<?php
/**
 * AURA ERP — Capital Injection & Procurement History
 * Injects 170K JOD capital and creates realistic procurement orders
 * for professor demo tour.
 */
require_once __DIR__ . '/config/db_connect.php';

echo "=== Capital & Procurement Seed ===\n\n";

// ───────────────────────────────────────────
// 1. INJECT 170K JOD CAPITAL
// ───────────────────────────────────────────
echo "[1] Injecting capital...\n";

// Get current balance
$currentBalance = $pdo->query("
    SELECT COALESCE(SUM(CASE WHEN transaction_type='Income' THEN amount ELSE -amount END), 0)
    FROM finance_ledger
")->fetchColumn();
echo "    Current balance: " . number_format($currentBalance, 2) . " JOD\n";

// Inject investor capital
$capitalId = 'CAP-260501-001';
$stmt = $pdo->prepare("
    INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, balance_after_transaction)
    VALUES (?, ?, 'Income', ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE amount = VALUES(amount), balance_after_transaction = VALUES(balance_after_transaction)
");
$newBalance = $currentBalance + 170000;
$stmt->execute([$capitalId, '2026-01-15', 'Initial Capital Investment', 170000, 'Owner Equity', $newBalance]);
echo "    ✓ Injected 170,000 JOD (Initial Capital Investment)\n";

// Add a few historical income entries for realism
$incomeEntries = [
    ['INC-260201-001', '2026-02-10', 'Sales Revenue',   12500, 'Q1 Sales Batch 1'],
    ['INC-260215-001', '2026-02-25', 'Sales Revenue',    8750, 'Q1 Sales Batch 2'],
    ['INC-260310-001', '2026-03-15', 'Sales Revenue',   15200, 'Export Order — Dubai'],
    ['INC-260325-001', '2026-03-28', 'Sales Revenue',    9800, 'Local Contractor Orders'],
    ['INC-260410-001', '2026-04-12', 'Sales Revenue',   22400, 'Q2 Sales — Petra Stone'],
];

$runningBalance = $newBalance;
foreach ($incomeEntries as $entry) {
    $runningBalance += $entry[3];
    try {
        $stmt->execute([$entry[0], $entry[1], $entry[2], $entry[3], $entry[4], $runningBalance]);
        echo "    ✓ {$entry[0]}: +" . number_format($entry[3]) . " JOD ({$entry[2]})\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "    ℹ {$entry[0]} exists, updated.\n";
        }
    }
}

// Recalculate final balance
$finalBalance = $pdo->query("
    SELECT COALESCE(SUM(CASE WHEN transaction_type='Income' THEN amount ELSE -amount END), 0)
    FROM finance_ledger
")->fetchColumn();
echo "    💰 New balance: " . number_format($finalBalance, 2) . " JOD\n\n";

// ───────────────────────────────────────────
// 2. CREATE REALISTIC PROCUREMENT ORDERS
// ───────────────────────────────────────────
echo "[2] Creating procurement orders...\n";

// supplier_items linkage: which supplier sells what
$supplierMaterials = [
    1 => ['RM-003', 'RM-004', 'RM-005', 'RM-011'],  // Jordan Quarry: aggregates, sand
    2 => ['RM-005', 'RM-008'],                         // Turkish Marble: marble chips, resin
    3 => ['RM-003', 'RM-004', 'RM-011'],               // Egyptian Aggregate: aggregates, sand
    4 => ['RM-006', 'RM-007', 'RM-010', 'RM-013', 'RM-014'], // Saudi Chemical: pigments, additives
    5 => ['RM-001', 'RM-002', 'RM-009'],               // Italian Stone: cement, fiberglass
];

$materialPrices = [
    'RM-001' => 0.85,  'RM-002' => 0.60,  'RM-003' => 0.45,
    'RM-004' => 1.20,  'RM-005' => 2.50,  'RM-006' => 8.00,
    'RM-007' => 7.50,  'RM-008' => 12.00, 'RM-009' => 5.00,
    'RM-010' => 15.00, 'RM-011' => 0.15,  'RM-012' => 0.01,
    'RM-013' => 5.00,  'RM-014' => 3.50,
];

// Historical received POs (show procurement history)
$historicalPOs = [
    [
        'po_id' => 'PO-260115-001', 'supplier_id' => 1, 'date' => '2026-01-15',
        'status' => 'Received', 'payment' => 'Paid',
        'lines' => [
            ['RM-003', 2000, 0.45],  // Crushed Limestone
            ['RM-004', 3000, 1.20],  // Quartz Aggregate
            ['RM-011', 5000, 0.15],  // Sand
        ]
    ],
    [
        'po_id' => 'PO-260120-001', 'supplier_id' => 5, 'date' => '2026-01-20',
        'status' => 'Received', 'payment' => 'Paid',
        'lines' => [
            ['RM-001', 3000, 0.85],  // White Cement
            ['RM-002', 3000, 0.60],  // Grey Cement
        ]
    ],
    [
        'po_id' => 'PO-260210-001', 'supplier_id' => 4, 'date' => '2026-02-10',
        'status' => 'Received', 'payment' => 'Paid',
        'lines' => [
            ['RM-006', 200, 8.00],   // Red Pigment
            ['RM-007', 200, 7.50],   // Yellow Pigment
            ['RM-013', 100, 5.00],   // SMF Additive
            ['RM-014', 150, 3.50],   // Pigment Mix
        ]
    ],
    [
        'po_id' => 'PO-260305-002', 'supplier_id' => 2, 'date' => '2026-03-05',
        'status' => 'Received', 'payment' => 'Paid',
        'lines' => [
            ['RM-005', 1000, 2.50],  // Marble Chips
            ['RM-008', 500, 12.00],  // Polyester Resin
        ]
    ],
    // Current pending POs (for demo - shows active procurement)
    [
        'po_id' => 'PO-260503-001', 'supplier_id' => 1, 'date' => '2026-05-03',
        'status' => 'Pending', 'payment' => 'Pending',
        'lines' => [
            ['RM-004', 2000, 1.20],  // Quartz Aggregate
            ['RM-011', 3000, 0.15],  // Sand
        ]
    ],
    [
        'po_id' => 'PO-260504-001', 'supplier_id' => 4, 'date' => '2026-05-04',
        'status' => 'Pending', 'payment' => 'Pending',
        'lines' => [
            ['RM-006', 100, 8.00],   // Red Pigment
            ['RM-013', 50, 5.00],    // SMF Additive
            ['RM-014', 80, 3.50],    // Pigment Mix
        ]
    ],
    [
        'po_id' => 'PO-260505-001', 'supplier_id' => 5, 'date' => '2026-05-05',
        'status' => 'Pending', 'payment' => 'Pending',
        'lines' => [
            ['RM-001', 2000, 0.85],  // White Cement
            ['RM-009', 200, 5.00],   // Fiberglass Mesh
        ]
    ],
];

$insertPO = $pdo->prepare("
    INSERT INTO purchase_orders (po_id, supplier_id, order_date, total_amount, currency, delivery_location, order_status, payment_status, created_by)
    VALUES (?, ?, ?, ?, 'JOD', 'Raw Material Warehouse', ?, ?, 5)
    ON DUPLICATE KEY UPDATE total_amount = VALUES(total_amount), order_status = VALUES(order_status)
");

$insertPOLine = $pdo->prepare("
    INSERT INTO po_lines (po_id, item_id, quantity, unit_price)
    VALUES (?, ?, ?, ?)
");

foreach ($historicalPOs as $po) {
    $total = 0;
    foreach ($po['lines'] as $line) {
        $total += $line[1] * $line[2];
    }

    try {
        $insertPO->execute([
            $po['po_id'], $po['supplier_id'], $po['date'],
            $total, $po['status'], $po['payment']
        ]);

        // Check if lines already exist
        $existingLines = $pdo->query("SELECT COUNT(*) FROM po_lines WHERE po_id = '{$po['po_id']}'")->fetchColumn();
        if ($existingLines == 0) {
            foreach ($po['lines'] as $line) {
                $insertPOLine->execute([$po['po_id'], $line[0], $line[1], $line[2]]);
            }
        }

        $lineCount = count($po['lines']);
        $statusIcon = $po['status'] === 'Received' ? '📦' : '⏳';
        echo "    {$statusIcon} {$po['po_id']} — " . number_format($total, 2) . " JOD ({$lineCount} materials, {$po['status']})\n";
    } catch (PDOException $e) {
        echo "    ℹ {$po['po_id']}: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// ───────────────────────────────────────────
// 3. ALSO RESTOCK FINISHED GOODS FOR DEMO
// ───────────────────────────────────────────
echo "[3] Restocking finished goods for demo...\n";

$finishedGoods = [
    'FG-001' => 51,  'FG-002' => 30,  'FG-003' => 20,
    'FG-004' => 12,  'FG-005' => 150, 'FG-006' => 18,
    'FG-C01' => 200, 'FG-C02' => 180, 'FG-C03' => 200,
    'FG-C04' => 180, 'FG-COL1' => 60, 'FG-COL2' => 45,
];

$updateFG = $pdo->prepare("UPDATE inventory_finished_goods SET quantity_in_stock = ? WHERE item_id = ?");
foreach ($finishedGoods as $id => $qty) {
    $updateFG->execute([$qty, $id]);
}
echo "    ✓ Restocked " . count($finishedGoods) . " finished goods.\n\n";

// ───────────────────────────────────────────
// 4. FINAL BALANCE CHECK
// ───────────────────────────────────────────
echo "=== FINAL STATE ===\n";
$balance = $pdo->query("SELECT COALESCE(SUM(CASE WHEN transaction_type='Income' THEN amount ELSE -amount END), 0) FROM finance_ledger")->fetchColumn();
$pendingPO = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE order_status = 'Pending'")->fetchColumn();
$receivedPO = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE order_status = 'Received'")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM sales_orders WHERE order_status = 'Pending'")->fetchColumn();
$totalLineItems = $pdo->query("SELECT COUNT(*) FROM sales_order_lines")->fetchColumn();

echo "  💰 Balance: " . number_format($balance, 2) . " JOD\n";
echo "  📦 Received POs: {$receivedPO}\n";
echo "  ⏳ Pending POs: {$pendingPO}\n";
echo "  🛒 Pending Sales Orders: {$pendingOrders}\n";
echo "  📋 Total Line Items: {$totalLineItems}\n";
echo "\n=== Ready for professor demo! ===\n";
