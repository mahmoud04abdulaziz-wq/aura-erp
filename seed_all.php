<?php
/**
 * AURA ERP — Comprehensive Data Seeder
 * Seeds ALL modules with realistic stone factory data.
 * Run: C:\xampp\php\php.exe seed_all.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_exception_handler(function($e) {
    file_put_contents(__DIR__ . '/seed_error.log', $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "FATAL: " . $e->getMessage() . PHP_EOL;
    exit(1);
});
require_once __DIR__ . '/config/db_connect.php';

echo "=== AURA ERP Full Database Seeder ===" . PHP_EOL;

// =============================================
// 1. FOUNDATION TABLES: Roles, Departments, Permissions
// =============================================
echo PHP_EOL . "--- Foundation Tables ---" . PHP_EOL;

// Roles
$roles = [
    [1, 'Executive Board', 'Full system access — C-suite and partners'],
    [2, 'Production Manager', 'Manages manufacturing and floor operations'],
    [3, 'Sales Engineer', 'Handles CRM, orders, and quotations'],
    [4, 'Procurement Officer', 'Manages suppliers and purchase orders'],
    [5, 'HR Manager', 'Human Resources administration'],
    [6, 'Inventory Manager', 'Warehouse control, safety stock, and material registration'],
    [7, 'IT Administrator', 'System maintenance, user provisioning, and security'],
    [8, 'Finance Manager', 'General ledger, payroll, and financial compliance'],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO roles (role_id, role_name, description) VALUES (?, ?, ?)");
foreach ($roles as $r) $stmt->execute($r);
echo "  ✓ Roles seeded" . PHP_EOL;

// Departments
$departments = [
    [1, 'Administration'],
    [2, 'Production'],
    [3, 'Sales & Marketing'],
    [4, 'Procurement & Logistics'],
    [5, 'Finance'],
    [6, 'Information Technology'],
    [7, 'Warehouse & Inventory'],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO departments (department_id, department_name) VALUES (?, ?)");
foreach ($departments as $d) $stmt->execute($d);
echo "  ✓ Departments seeded" . PHP_EOL;

// Permissions
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
foreach ($permissions as $p) $stmt->execute($p);

// Admin permission
$pdo->exec("INSERT IGNORE INTO permissions (module_access, description) VALUES ('admin', 'Access to User Account Management panel')");
$adminPermId = $pdo->query("SELECT permission_id FROM permissions WHERE module_access = 'admin'")->fetchColumn();
echo "  ✓ Permissions seeded" . PHP_EOL;

// Role-Permissions mapping
$rolePerms = [
    // Executive Board → ALL
    [1,1],[1,2],[1,3],[1,4],[1,5],[1,6],[1,7],[1,8],
    // Production Manager → dashboard, manufacturing, inventory (read)
    [2,1],[2,4],[2,5],
    // Sales Engineer → dashboard, crm, inventory (read), finance (limited via code)
    [3,1],[3,8],[3,5],[3,7],
    // Procurement Officer → dashboard, procurement, inventory
    [4,1],[4,6],[4,5],
    // HR Manager → dashboard, hr
    [5,1],[5,3],
    // Inventory Manager → dashboard, inventory
    [6,1],[6,5],
    // IT Administrator → dashboard, auth, admin
    [7,1],[7,2],
    // Finance Manager → dashboard, finance
    [8,1],[8,7],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
foreach ($rolePerms as $rp) $stmt->execute($rp);
// Admin permission for Executive Board and IT Administrator
if ($adminPermId) {
    $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, $adminPermId)");
    $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (7, $adminPermId)");
}
echo "  ✓ Role-Permissions mapped" . PHP_EOL;

// =============================================
// 2. EMPLOYEES
// =============================================
echo PHP_EOL . "--- Employees ---" . PHP_EOL;

// Add role_id column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE employees ADD COLUMN role_id INT DEFAULT NULL AFTER department_id");
    $pdo->exec("ALTER TABLE employees ADD CONSTRAINT fk_employees_role FOREIGN KEY (role_id) REFERENCES roles(role_id)");
} catch (Exception $e) {
    // Column already exists — ignore
}

$employees = [
    [1, 1, 1, 'Salem', 'Hijazi', 'salem.h@company.com', 'Approved', '2022-01-15', 0.95, 500.00, 25.00],
    [2, 1, 1, 'Tayseer', 'Khatib', 'tayseer.k@company.com', 'Approved', '2022-03-01', 0.90, 450.00, 20.00],
    [3, 2, 2, 'Omar', 'Abu Saleh', 'omar.a@company.com', 'Approved', '2023-02-10', 0.88, 400.00, 22.00],
    [4, 3, 3, 'Lina', 'Darwish', 'lina.d@company.com', 'Approved', '2023-05-20', 0.92, 350.00, 18.00],
    [5, 4, 4, 'Khaled', 'Nasser', 'khaled.n@company.com', 'Approved', '2023-06-01', 0.85, 380.00, 20.00],
    [6, 2, 2, 'Fadi', 'Masri', 'fadi.m@company.com', 'Approved', '2023-08-15', 0.80, 350.00, 18.00],
    [7, 3, 3, 'Rania', 'Haddad', 'rania.h@company.com', 'Approved', '2024-01-10', 0.78, 300.00, 15.00],
    [8, 5, 5, 'Huda', 'Awad', 'huda.hr@company.com', 'Approved', '2020-01-01', 0.91, 420.00, 20.00],
    [9, 2, 2, 'Nabil', 'Qasem', 'nabil.q@company.com', 'Pending', '2026-03-25', 0.00, 300.00, 15.00],
    [10, 4, 4, 'Sara', 'Younis', 'sara.y@company.com', 'Pending', '2026-04-01', 0.00, 320.00, 16.00],
    [11, 1, 1, 'Ahmad', 'Barakat', 'ahmad.b@company.com', 'Approved', '2021-11-05', 0.87, 500.00, 25.00],
    [12, 2, 2, 'Mazen', 'Tawfiq', 'mazen.t@company.com', 'Approved', '2024-06-15', 0.82, 350.00, 18.00],
    [13, 7, 6, 'Tariq', 'Mansour', 'tariq.m@company.com', 'Approved', '2022-06-01', 0.89, 400.00, 20.00],
    [14, 6, 7, 'Yazan', 'Othman', 'yazan.it@company.com', 'Approved', '2021-09-15', 0.93, 450.00, 22.00],
    [15, 5, 8, 'Nour', 'Sabbagh', 'nour.fin@company.com', 'Approved', '2022-04-10', 0.90, 430.00, 21.00],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO employees 
    (employee_id, department_id, role_id, first_name, last_name, email, verification_status, hire_date, performance_score, base_allowances, overtime_rate) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($employees as $e) $stmt->execute($e);
echo "  ✓ " . count($employees) . " employees created" . PHP_EOL;

// =============================================
// 3. USER ACCOUNTS (login credentials)
// =============================================
echo PHP_EOL . "--- User Accounts ---" . PHP_EOL;

$defaultHash = password_hash('Admin@123', PASSWORD_BCRYPT);

$users = [
    [1, 1, 1, 'salem.h@company.com'],      // Executive Board (CEO)
    [2, 2, 1, 'tayseer.k@company.com'],     // Executive Board
    [3, 3, 2, 'omar.a@company.com'],        // Production Manager
    [4, 4, 3, 'lina.d@company.com'],        // Sales Engineer
    [5, 5, 4, 'khaled.n@company.com'],      // Procurement Officer
    [6, 8, 5, 'huda.hr@company.com'],       // HR Manager
    [7, 11, 1, 'ahmad.b@company.com'],      // Executive Board
    [8, 13, 6, 'tariq.m@company.com'],      // Inventory Manager
    [9, 14, 7, 'yazan.it@company.com'],     // IT Administrator
    [10, 15, 8, 'nour.fin@company.com'],    // Finance Manager
];

$stmt = $pdo->prepare("INSERT IGNORE INTO users (user_id, employee_id, role_id, email, password_hash) VALUES (?, ?, ?, ?, ?)");
foreach ($users as $u) {
    $stmt->execute([$u[0], $u[1], $u[2], $u[3], $defaultHash]);
}
echo "  ✓ " . count($users) . " user accounts created (password: Admin@123)" . PHP_EOL;

// =============================================
// 4. CUSTOMERS
// =============================================
echo PHP_EOL . "--- Customers ---" . PHP_EOL;

$customers = [
    [1, 'Al-Aqsa Construction Co.', 'Mohammad Al-Khatib', '+962-79-555-0101', 'info@alaqsa-const.jo', 'Converted', 'Amman, Jordan'],
    [2, 'Petra Stone Designs', 'Layla Hammoud', '+962-79-555-0202', 'sales@petrastone.jo', 'Converted', 'Aqaba, Jordan'],
    [3, 'Gulf Building Materials', 'Abdullah Al-Rashid', '+971-50-555-0303', 'procurement@gulfbm.ae', 'Converted', 'Dubai, UAE'],
    [4, 'Royal Marble & Granite', 'Samer Khalil', '+962-79-555-0404', 'samer@royalmarble.jo', 'Quoted', 'Irbid, Jordan'],
    [5, 'Mediterranean Builders', 'Elena Papadopoulos', '+30-210-555-0505', 'elena@medbuild.gr', 'Contacted', 'Athens, Greece'],
    [6, 'Sahara Interior Design', 'Fatima Benkhadra', '+212-66-555-0606', 'fatima@sahara-id.ma', 'New', 'Casablanca, Morocco'],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO customers (customer_id, company_name, contact_person, phone_number, email, lead_status, default_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($customers as $c) $stmt->execute($c);
echo "  ✓ " . count($customers) . " customers created" . PHP_EOL;

// =============================================
// 5. SUPPLIERS
// =============================================
echo PHP_EOL . "--- Suppliers ---" . PHP_EOL;

$suppliers = [
    [1, 'Jordan Quarry Corp', 'JOD'],
    [2, 'Turkish Marble Exports', 'USD'],
    [3, 'Egyptian Aggregate Supply', 'USD'],
    [4, 'Saudi Chemical Solutions', 'SAR'],
    [5, 'Italian Stone Masters', 'EUR'],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO suppliers (supplier_id, supplier_name, preferred_currency) VALUES (?, ?, ?)");
foreach ($suppliers as $s) $stmt->execute($s);
echo "  ✓ " . count($suppliers) . " suppliers created" . PHP_EOL;

// =============================================
// 6. WAREHOUSES
// =============================================
echo PHP_EOL . "--- Warehouses ---" . PHP_EOL;

$warehouses = [
    [1, 'Main Warehouse'],
    [2, 'Raw Material Store'],
    [3, 'Finished Goods Store'],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO warehouses (warehouse_id, warehouse_name) VALUES (?, ?)");
foreach ($warehouses as $w) $stmt->execute($w);
echo "  ✓ Warehouses seeded" . PHP_EOL;

// =============================================
// 7. ITEM MASTER (Raw Materials + Finished Goods)
// =============================================
echo PHP_EOL . "--- Item Master ---" . PHP_EOL;

$items = [
    // Raw Materials
    ['RM-001', 'White Cement', 'Raw Material', 'kg', 0.85, 500.000],
    ['RM-002', 'Grey Cement', 'Raw Material', 'kg', 0.60, 800.000],
    ['RM-003', 'Crushed Limestone', 'Raw Material', 'kg', 0.25, 1000.000],
    ['RM-004', 'Quartz Aggregate', 'Raw Material', 'kg', 1.20, 300.000],
    ['RM-005', 'Marble Chips', 'Raw Material', 'kg', 2.50, 200.000],
    ['RM-006', 'Iron Oxide Pigment (Red)', 'Raw Material', 'kg', 8.00, 50.000],
    ['RM-007', 'Iron Oxide Pigment (Yellow)', 'Raw Material', 'kg', 7.50, 50.000],
    ['RM-008', 'Polyester Resin', 'Raw Material', 'litre', 12.00, 100.000],
    ['RM-009', 'Fiberglass Mesh', 'Raw Material', 'sqm', 3.50, 150.000],
    ['RM-010', 'Silicon Sealant', 'Raw Material', 'tube', 5.00, 80.000],
    // Finished Goods
    ['FG-001', 'Artificial Marble Slab 120x60', 'Finished Good', 'pcs', 45.00, 20.000],
    ['FG-002', 'Artificial Granite Tile 60x60', 'Finished Good', 'pcs', 35.00, 25.000],
    ['FG-003', 'Decorative Stone Panel 100x50', 'Finished Good', 'pcs', 65.00, 15.000],
    ['FG-004', 'Kitchen Countertop Slab 200x60', 'Finished Good', 'pcs', 120.00, 10.000],
    ['FG-005', 'Wall Cladding Tile 30x30', 'Finished Good', 'pcs', 15.00, 50.000],
    ['FG-006', 'Bathroom Vanity Top', 'Finished Good', 'pcs', 85.00, 8.000],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO item_master (item_id, item_name, category, base_uom, standard_cost, min_stock_level) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($items as $i) $stmt->execute($i);
echo "  ✓ " . count($items) . " items registered" . PHP_EOL;

// =============================================
// 8. RECIPES (Bill of Materials)
// =============================================
echo PHP_EOL . "--- Recipes ---" . PHP_EOL;

$pdo->exec("INSERT IGNORE INTO recipes (recipe_id, finished_item_id, recipe_name, base_yield_qty, curing_time_hours) VALUES
    (1, 'FG-001', 'Marble Slab Standard Mix', 10.000, 24),
    (2, 'FG-002', 'Granite Tile Mix', 15.000, 18),
    (3, 'FG-003', 'Decorative Panel Mix', 8.000, 36)
");

$pdo->exec("INSERT IGNORE INTO recipe_ingredients (recipe_id, raw_material_id, quantity_required) VALUES
    (1, 'RM-001', 50.000), (1, 'RM-003', 80.000), (1, 'RM-005', 30.000), (1, 'RM-008', 5.000),
    (2, 'RM-002', 60.000), (2, 'RM-004', 40.000), (2, 'RM-006', 2.000),
    (3, 'RM-001', 30.000), (3, 'RM-005', 50.000), (3, 'RM-007', 3.000), (3, 'RM-009', 10.000)
");
echo "  ✓ 3 recipes with ingredients seeded" . PHP_EOL;

// =============================================
// 9. INVENTORY LEDGER (stock movements)
// =============================================
echo PHP_EOL . "--- Inventory Ledger ---" . PHP_EOL;

// Drop corrupted table if exists (error 1932: data files missing)
try { $pdo->exec("DROP TABLE IF EXISTS inventory_ledger"); } catch (Exception $e) { /* ignore */ }

$pdo->exec("CREATE TABLE IF NOT EXISTS inventory_ledger (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id VARCHAR(50) NOT NULL,
    warehouse_id INT NOT NULL,
    transaction_type ENUM('Receipt','Dispatch','Consumed','Produced','Scrap','Adjustment') NOT NULL,
    quantity_change DECIMAL(12,3) NOT NULL,
    reference_id VARCHAR(50) DEFAULT NULL,
    recorded_by INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES item_master(item_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    FOREIGN KEY (recorded_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$invCheck = $pdo->query("SELECT COUNT(*) FROM inventory_ledger")->fetchColumn();
if ($invCheck == 0) {
    $pdo->exec("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by, timestamp) VALUES
        ('RM-001', 2, 'Receipt', 2000.000, 'PO-260301-001', 1, '2026-03-01 09:00:00'),
        ('RM-002', 2, 'Receipt', 3000.000, 'PO-260301-001', 1, '2026-03-01 09:05:00'),
        ('RM-003', 2, 'Receipt', 5000.000, 'PO-260301-002', 1, '2026-03-02 10:00:00'),
        ('RM-004', 2, 'Receipt', 1200.000, 'PO-260301-002', 1, '2026-03-02 10:05:00'),
        ('RM-005', 2, 'Receipt', 800.000, 'PO-260305-001', 5, '2026-03-05 08:30:00'),
        ('RM-006', 2, 'Receipt', 100.000, 'PO-260305-001', 5, '2026-03-05 08:35:00'),
        ('RM-007', 2, 'Receipt', 100.000, 'PO-260305-001', 5, '2026-03-05 08:36:00'),
        ('RM-008', 2, 'Receipt', 200.000, 'PO-260310-001', 5, '2026-03-10 11:00:00'),
        ('RM-009', 2, 'Receipt', 300.000, 'PO-260310-001', 5, '2026-03-10 11:05:00'),
        ('RM-010', 2, 'Receipt', 150.000, 'PO-260310-001', 5, '2026-03-10 11:10:00'),

        ('RM-001', 2, 'Consumed', -500.000, 'PROD-260315-001', 3, '2026-03-15 07:00:00'),
        ('RM-003', 2, 'Consumed', -800.000, 'PROD-260315-001', 3, '2026-03-15 07:05:00'),
        ('RM-005', 2, 'Consumed', -300.000, 'PROD-260315-001', 3, '2026-03-15 07:10:00'),
        ('RM-002', 2, 'Consumed', -600.000, 'PROD-260318-001', 3, '2026-03-18 07:00:00'),
        ('RM-004', 2, 'Consumed', -400.000, 'PROD-260318-001', 3, '2026-03-18 07:05:00'),

        ('FG-001', 3, 'Produced', 100.000, 'PROD-260315-001', 3, '2026-03-16 16:00:00'),
        ('FG-002', 3, 'Produced', 150.000, 'PROD-260318-001', 3, '2026-03-19 16:00:00'),
        ('FG-003', 3, 'Produced', 60.000, 'PROD-260320-001', 3, '2026-03-21 16:00:00'),
        ('FG-004', 3, 'Produced', 25.000, 'PROD-260325-001', 3, '2026-03-26 16:00:00'),
        ('FG-005', 3, 'Produced', 200.000, 'PROD-260328-001', 3, '2026-03-29 16:00:00'),

        ('FG-001', 3, 'Dispatch', -30.000, 'SO-260320-001', 4, '2026-03-20 14:00:00'),
        ('FG-002', 3, 'Dispatch', -50.000, 'SO-260322-001', 4, '2026-03-22 14:00:00'),
        ('FG-005', 3, 'Dispatch', -80.000, 'SO-260328-001', 4, '2026-03-28 14:00:00'),

        ('RM-001', 2, 'Receipt', 1000.000, 'PO-260328-001', 5, '2026-03-28 09:00:00'),
        ('RM-003', 2, 'Receipt', 2000.000, 'PO-260328-001', 5, '2026-03-28 09:05:00')
    ");
    echo "  ✓ 25 inventory transactions recorded" . PHP_EOL;
} else {
    echo "  - Inventory ledger already has data, skipping" . PHP_EOL;
}

// =============================================
// 10. INVENTORY FINISHED GOODS (summary view)
// =============================================
echo PHP_EOL . "--- Finished Goods Summary ---" . PHP_EOL;

$pdo->exec("INSERT IGNORE INTO inventory_finished_goods (item_id, item_name, stone_measurement, category, quantity_in_stock, warehouse_location, unit_cost) VALUES
    ('FG-001', 'Artificial Marble Slab 120x60', '120cm x 60cm x 2cm', 'Marble', 70, 'Finished Goods Store', 45.00),
    ('FG-002', 'Artificial Granite Tile 60x60', '60cm x 60cm x 1.5cm', 'Granite', 100, 'Finished Goods Store', 35.00),
    ('FG-003', 'Decorative Stone Panel 100x50', '100cm x 50cm x 3cm', 'Decorative', 60, 'Finished Goods Store', 65.00),
    ('FG-004', 'Kitchen Countertop Slab 200x60', '200cm x 60cm x 3cm', 'Countertop', 25, 'Finished Goods Store', 120.00),
    ('FG-005', 'Wall Cladding Tile 30x30', '30cm x 30cm x 1cm', 'Cladding', 120, 'Finished Goods Store', 15.00),
    ('FG-006', 'Bathroom Vanity Top', '90cm x 55cm x 2cm', 'Vanity', 8, 'Finished Goods Store', 85.00)
");
echo "  ✓ Finished goods summary updated" . PHP_EOL;

// =============================================
// 11. SALES ORDERS
// =============================================
echo PHP_EOL . "--- Sales Orders ---" . PHP_EOL;

$salesOrders = [
    ['SO-260301-0001', null, 1, '2026-03-01', 4500.00, 'Amman, Jordan', 'Delivered', 4, 'Bank Transfer'],
    ['SO-260305-0001', null, 2, '2026-03-05', 7800.00, 'Aqaba, Jordan', 'Delivered', 4, 'Credit Card'],
    ['SO-260310-0001', null, 3, '2026-03-10', 15200.00, 'Dubai, UAE', 'Pending Delivery', 4, 'Bank Transfer'],
    ['SO-260315-0001', null, 1, '2026-03-15', 3200.00, 'Amman, Jordan', 'In Production', 4, 'Cash'],
    ['SO-260320-0001', null, 4, '2026-03-20', 6500.00, 'Irbid, Jordan', 'In Production', 4, 'Credit Card'],
    ['SO-260325-0001', null, 2, '2026-03-25', 9100.00, 'Aqaba, Jordan', 'Pending', 4, 'Bank Transfer'],
    ['SO-260328-0001', null, 3, '2026-03-28', 22500.00, 'Dubai, UAE', 'Pending', 4, 'Bank Transfer'],
    ['SO-260401-0001', null, 5, '2026-04-01', 1800.00, 'Athens, Greece', 'Pending', 4, 'Credit Card'],
    ['SO-260402-0001', null, 1, '2026-04-02', 5400.00, 'Amman, Jordan', 'Pending', 4, 'Cash'],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO sales_orders (so_id, quote_id, customer_id, order_date, total_price, delivery_location, order_status, handled_by, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($salesOrders as $so) $stmt->execute($so);
echo "  ✓ " . count($salesOrders) . " sales orders created" . PHP_EOL;

// =============================================
// 12. PURCHASE ORDERS
// =============================================
echo PHP_EOL . "--- Purchase Orders ---" . PHP_EOL;

$purchaseOrders = [
    ['PO-260301-001', 1, '2026-03-01', 8500.00, 'JOD', 'Main Warehouse', 'Received', 'Paid', 5],
    ['PO-260305-001', 2, '2026-03-05', 12300.00, 'USD', 'Raw Material Store', 'Received', 'Paid', 5],
    ['PO-260310-001', 3, '2026-03-10', 6700.00, 'USD', 'Raw Material Store', 'Received', 'Paid', 5],
    ['PO-260318-001', 4, '2026-03-18', 4200.00, 'SAR', 'Raw Material Store', 'Received', 'Pending', 5],
    ['PO-260325-001', 1, '2026-03-25', 9800.00, 'JOD', 'Main Warehouse', 'Pending', 'Pending', 5],
    ['PO-260328-001', 5, '2026-03-28', 18500.00, 'EUR', 'Raw Material Store', 'Pending', 'Pending', 5],
    ['PO-260401-001', 2, '2026-04-01', 7600.00, 'USD', 'Raw Material Store', 'Pending', 'Pending', 5],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO purchase_orders (po_id, supplier_id, order_date, total_amount, currency, delivery_location, order_status, payment_status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($purchaseOrders as $po) $stmt->execute($po);
echo "  ✓ " . count($purchaseOrders) . " purchase orders created" . PHP_EOL;

// =============================================
// 13. PRODUCTION ORDERS
// =============================================
echo PHP_EOL . "--- Production Orders ---" . PHP_EOL;

$prodOrders = [
    ['PROD-260315-001', 'FG-001', 'PRESS-01', '2026-03-15', 100.000, 100.000, 'Completed', 'Passed', 3, 1],
    ['PROD-260318-001', 'FG-002', 'PRESS-02', '2026-03-18', 150.000, 150.000, 'Completed', 'Passed', 3, 1],
    ['PROD-260320-001', 'FG-003', 'PRESS-01', '2026-03-20', 80.000, 60.000, 'Completed', 'Passed', 3, 1],
    ['PROD-260325-001', 'FG-004', 'PRESS-03', '2026-03-25', 30.000, 25.000, 'Completed', 'Rework', 3, null],
    ['PROD-260328-001', 'FG-005', 'PRESS-02', '2026-03-28', 250.000, 200.000, 'Curing', 'Pending', 3, null],
    ['PROD-260401-001', 'FG-001', 'PRESS-01', '2026-04-01', 120.000, 40.000, 'Mixing', 'Pending', 3, null],
    ['PROD-260402-001', 'FG-006', 'PRESS-03', '2026-04-02', 20.000, 0.000, 'Planned', 'Pending', 3, null],
    ['PROD-260402-002', 'FG-002', 'PRESS-02', '2026-04-02', 200.000, 0.000, 'Planned', 'Pending', 3, null],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO production_orders 
    (production_id, item_id, machine_id, production_date, target_quantity, actual_yield, status, qa_status, operator_user_id, inspected_by) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($prodOrders as $po) $stmt->execute($po);
echo "  ✓ " . count($prodOrders) . " production orders created" . PHP_EOL;

// =============================================
// 14. CHART OF ACCOUNTS + FINANCE LEDGER
// =============================================
echo PHP_EOL . "--- Chart of Accounts & Finance ---" . PHP_EOL;

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
foreach ($accounts as $a) $stmt->execute($a);

// Finance Ledger transactions
$finCheck = $pdo->query("SELECT COUNT(*) FROM finance_ledger")->fetchColumn();
if ($finCheck == 0) {
    $finTxns = [
        // Income from delivered sales orders
        ['FIN-260301-001', '2026-03-05', 'Income', 'Sales Revenue', 4500.00, 'SO-260301-0001', 1],
        ['FIN-260305-001', '2026-03-10', 'Income', 'Sales Revenue', 7800.00, 'SO-260305-0001', 1],
        // Expenses — material purchases (paid POs)
        ['FIN-260301-002', '2026-03-01', 'Expense', 'Raw Materials', 8500.00, 'PO-260301-001', 1],
        ['FIN-260305-002', '2026-03-05', 'Expense', 'Raw Materials', 12300.00, 'PO-260305-001', 1],
        ['FIN-260310-002', '2026-03-10', 'Expense', 'Raw Materials', 6700.00, 'PO-260310-001', 1],
        // Operating expenses
        ['FIN-260315-001', '2026-03-15', 'Expense', 'Utilities', 1250.00, null, 1],
        ['FIN-260320-001', '2026-03-20', 'Expense', 'Payroll', 8500.00, null, 1],
        ['FIN-260325-001', '2026-03-25', 'Expense', 'Equipment', 3200.00, null, 1],
        ['FIN-260328-001', '2026-03-28', 'Expense', 'Logistics', 950.00, null, 1],
        // More income
        ['FIN-260330-001', '2026-03-30', 'Income', 'Service Income', 2500.00, null, 1],
        ['FIN-260401-001', '2026-04-01', 'Income', 'Sales Revenue', 15200.00, 'SO-260310-0001', 1],
        ['FIN-260402-001', '2026-04-02', 'Expense', 'Maintenance', 780.00, null, 1],
        ['FIN-260402-002', '2026-04-02', 'Expense', 'Marketing', 1500.00, null, 1],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($finTxns as $ft) $stmt->execute($ft);
    echo "  ✓ " . count($finTxns) . " financial transactions recorded" . PHP_EOL;
} else {
    echo "  - Finance ledger already has data, skipping" . PHP_EOL;
}

// =============================================
// 15. QUOTATIONS
// =============================================
echo PHP_EOL . "--- Quotations ---" . PHP_EOL;

$quotations = [
    [1, 4, '2026-04-15', 8200.00, 'Sent', 4],
    [2, 5, '2026-04-20', 12500.00, 'Draft', 4],
    [3, 6, '2026-04-10', 3800.00, 'Sent', 4],
];
$stmt = $pdo->prepare("INSERT IGNORE INTO quotations (quote_id, customer_id, valid_until, total_amount, status, prepared_by) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($quotations as $q) $stmt->execute($q);

// Quotation lines
$pdo->exec("INSERT IGNORE INTO quotation_lines (quote_id, item_id, quantity, unit_price) VALUES
    (1, 'FG-001', 50.000, 55.00),
    (1, 'FG-005', 200.000, 20.50),
    (2, 'FG-003', 80.000, 75.00),
    (2, 'FG-004', 30.000, 145.00),
    (3, 'FG-002', 60.000, 42.00),
    (3, 'FG-005', 100.000, 17.00)
");
echo "  ✓ 3 quotations with line items created" . PHP_EOL;

// =============================================
// 16. LEAVE TICKETS (HR workflow)
// =============================================
echo PHP_EOL . "--- Leave Tickets ---" . PHP_EOL;

$pdo->exec("CREATE TABLE IF NOT EXISTS leave_tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    manager_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    hr_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$leaveCheck = $pdo->query("SELECT COUNT(*) FROM leave_tickets")->fetchColumn();
if ($leaveCheck == 0) {
    $pdo->exec("INSERT INTO leave_tickets (employee_id, start_date, end_date, reason, manager_status, hr_status) VALUES
        (3, '2026-04-05', '2026-04-07', 'Family emergency — need 3 days off', 'Approved', 'Approved'),
        (4, '2026-04-10', '2026-04-12', 'Annual vacation — visiting family abroad', 'Approved', 'Pending'),
        (6, '2026-04-08', '2026-04-08', 'Medical appointment', 'Pending', 'Pending'),
        (7, '2026-04-15', '2026-04-18', 'Personal travel', 'Rejected', 'Pending'),
        (12, '2026-04-20', '2026-04-22', 'Wedding celebration', 'Pending', 'Pending')
    ");
    echo "  ✓ 5 leave tickets created (various statuses)" . PHP_EOL;
} else {
    echo "  - Leave tickets already have data, skipping" . PHP_EOL;
}

// =============================================
// 17. SYSTEM LOGS (activity trail)
// =============================================
echo PHP_EOL . "--- System Logs ---" . PHP_EOL;

$logCheck = $pdo->query("SELECT COUNT(*) FROM system_logs")->fetchColumn();
if ($logCheck < 5) {
    $pdo->exec("INSERT INTO system_logs (user_id, action_type, description, status, timestamp) VALUES
        (1, 'Login', 'Salem Hijazi logged in from 192.168.1.10', 'Success', '2026-04-02 08:00:00'),
        (3, 'Production', 'Started batch PROD-260401-001 on PRESS-01', 'Success', '2026-04-01 07:15:00'),
        (4, 'Order Created', 'New sales order SO-260402-0001 for Al-Aqsa Construction', 'Success', '2026-04-02 09:30:00'),
        (5, 'Purchase Order', 'Created PO-260401-001 for Turkish Marble Exports', 'Success', '2026-04-01 10:00:00'),
        (1, 'Finance', 'Recorded income FIN-260401-001 ($15,200.00)', 'Success', '2026-04-01 14:30:00'),
        (6, 'HR Action', 'Approved leave ticket LVE-1 for Omar Abu Saleh', 'Success', '2026-04-01 11:00:00'),
        (3, 'Production', 'Batch PROD-260328-001 moved to Curing stage', 'Success', '2026-03-29 08:00:00'),
        (1, 'Login', 'Failed login attempt for admin@company.com', 'Failure', '2026-04-02 07:55:00'),
        (5, 'Inventory', 'Received 1000kg White Cement into Raw Material Store', 'Success', '2026-03-28 09:00:00'),
        (4, 'Order Updated', 'SO-260310-0001 status changed to Pending Delivery', 'Success', '2026-03-30 16:00:00')
    ");
    echo "  ✓ 10 system log entries created" . PHP_EOL;
} else {
    echo "  - System logs already have data, skipping" . PHP_EOL;
}

// =============================================
// DONE
// =============================================
echo PHP_EOL . "=========================================" . PHP_EOL;
echo "  ALL MODULES SEEDED SUCCESSFULLY!" . PHP_EOL;
echo "=========================================" . PHP_EOL;
echo PHP_EOL . "Login credentials:" . PHP_EOL;
echo "  Email: salem.h@company.com" . PHP_EOL;
echo "  Password: Admin@123" . PHP_EOL;
echo "  (All users share the same password)" . PHP_EOL;
