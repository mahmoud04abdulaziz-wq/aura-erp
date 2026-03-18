<?php
/**
 * AURA ERP — Database Seed Script
 * Run once: C:\xampp\php\php.exe seed.php
 */
require_once __DIR__ . '/config/db_connect.php';

echo "=== AURA ERP Database Seeder ===" . PHP_EOL;

// 1. Seed Permissions
echo "Seeding permissions..." . PHP_EOL;
$permissions = [
    [1, 'dashboard', 'Dashboard access'],
    [2, 'auth', 'Authentication & user management'],
    [3, 'hr', 'Human Resources module'],
    [4, 'manufacturing', 'Production & batch management'],
    [5, 'inventory', 'Inventory & warehouse'],
    [6, 'procurement', 'Purchase orders & suppliers'],
    [7, 'finance', 'Accounting & ledger'],
    [8, 'crm', 'Sales pipeline & customers'],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO permissions (permission_id, module_access, description) VALUES (?, ?, ?)");
foreach ($permissions as $p) {
    $stmt->execute($p);
}
echo "  ✓ " . count($permissions) . " permissions inserted." . PHP_EOL;

// 2. Seed Role-Permissions
echo "Seeding role_permissions..." . PHP_EOL;
$rolePerms = [
    // Executive Board (1) → all
    [1, 1],
    [1, 2],
    [1, 3],
    [1, 4],
    [1, 5],
    [1, 6],
    [1, 7],
    [1, 8],
    // Production Manager (2) → dashboard, manufacturing, inventory
    [2, 1],
    [2, 4],
    [2, 5],
    // Sales Engineer (3) → dashboard, crm, inventory
    [3, 1],
    [3, 8],
    [3, 5],
    // Procurement Officer (4) → dashboard, procurement, inventory
    [4, 1],
    [4, 6],
    [4, 5],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
foreach ($rolePerms as $rp) {
    $stmt->execute($rp);
}
echo "  ✓ " . count($rolePerms) . " role_permissions inserted." . PHP_EOL;

// 3. Seed Chart of Accounts
echo "Seeding chart_of_accounts..." . PHP_EOL;
$accounts = [
    [1000, 'Cash', 'Asset'],
    [1100, 'Accounts Receivable', 'Asset'],
    [1200, 'Inventory - Raw', 'Asset'],
    [1300, 'Inventory - Finished', 'Asset'],
    [2000, 'Accounts Payable', 'Liability'],
    [2100, 'Accrued Wages', 'Liability'],
    [3000, "Owner's Equity", 'Equity'],
    [4000, 'Sales Revenue', 'Revenue'],
    [4100, 'Other Income', 'Revenue'],
    [5000, 'Cost of Goods Sold', 'Expense'],
    [5100, 'Wages Expense', 'Expense'],
    [5200, 'Materials Expense', 'Expense'],
    [5300, 'Overhead Expense', 'Expense'],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO chart_of_accounts (account_id, account_name, account_type) VALUES (?, ?, ?)");
foreach ($accounts as $a) {
    $stmt->execute($a);
}
echo "  ✓ " . count($accounts) . " accounts inserted." . PHP_EOL;

// 4. Set test password for Salem (Admin@123)
echo "Setting test password for salem.h@company.com..." . PHP_EOL;
$hash = password_hash('Admin@123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
$stmt->execute([$hash, 'salem.h@company.com']);
echo "  ✓ Password set to Admin@123 (bcrypt hashed)." . PHP_EOL;

// Also set for Tayseer
$hash2 = password_hash('Admin@123', PASSWORD_BCRYPT);
$stmt->execute([$hash2, 'tayseer.k@company.com']);
echo "  ✓ Password set for tayseer.k@company.com too." . PHP_EOL;

// 5. Seed warehouses if empty
$whCount = $pdo->query("SELECT COUNT(*) FROM warehouses")->fetchColumn();
if ($whCount == 0) {
    echo "Seeding warehouses..." . PHP_EOL;
    $pdo->exec("INSERT INTO warehouses (warehouse_name) VALUES ('Main Warehouse'), ('Raw Material Store'), ('Finished Goods Store')");
    echo "  ✓ 3 warehouses inserted." . PHP_EOL;
}

echo PHP_EOL . "=== Seeding Complete ===" . PHP_EOL;
