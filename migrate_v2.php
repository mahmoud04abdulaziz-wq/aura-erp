<?php
/**
 * AURA ERP — Migration v2
 * Creates: mix_outputs, web_order_items, sales_order_lines, material_requests
 * Extends: suppliers (contact fields)
 * Migrates: existing recipe→FG mappings into mix_outputs
 */
require_once __DIR__ . '/config/db_connect.php';

echo "=== AURA ERP Migration v2 ===" . PHP_EOL;

$tables = [

    // 1. Mix Outputs — one recipe → many finished goods
    "mix_outputs" => "CREATE TABLE IF NOT EXISTS mix_outputs (
        output_id INT AUTO_INCREMENT PRIMARY KEY,
        recipe_id INT NOT NULL,
        item_id VARCHAR(50) NOT NULL,
        output_quantity DECIMAL(12,3) NOT NULL,
        FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES item_master(item_id) ON UPDATE CASCADE,
        UNIQUE KEY unique_recipe_item (recipe_id, item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 2. Web Order Items — structured cart data from storefront
    "web_order_items" => "CREATE TABLE IF NOT EXISTS web_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        item_id VARCHAR(50) NOT NULL,
        item_name VARCHAR(150) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES item_master(item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 3. Sales Order Lines — structured line items for accepted orders
    "sales_order_lines" => "CREATE TABLE IF NOT EXISTS sales_order_lines (
        line_id INT AUTO_INCREMENT PRIMARY KEY,
        so_id VARCHAR(50) NOT NULL,
        item_id VARCHAR(50) NOT NULL,
        quantity DECIMAL(12,3) NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL,
        FOREIGN KEY (so_id) REFERENCES sales_orders(so_id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES item_master(item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 4. Material Requests — Production → Procurement cross-role requests
    "material_requests" => "CREATE TABLE IF NOT EXISTS material_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        requested_by INT NOT NULL,
        item_id VARCHAR(50) NOT NULL,
        quantity_requested DECIMAL(12,3) NOT NULL,
        unit_price DECIMAL(12,2) DEFAULT 0.00,
        reason TEXT,
        urgency ENUM('Normal','High','Critical') DEFAULT 'Normal',
        status ENUM('Requested','Accepted','Declined') DEFAULT 'Requested',
        handled_by INT DEFAULT NULL,
        decline_reason TEXT DEFAULT NULL,
        po_id VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        FOREIGN KEY (requested_by) REFERENCES users(user_id),
        FOREIGN KEY (item_id) REFERENCES item_master(item_id),
        FOREIGN KEY (handled_by) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        echo "  ✅ Table '$name' — OK" . PHP_EOL;
    } catch (PDOException $e) {
        echo "  ❌ Table '$name' — " . $e->getMessage() . PHP_EOL;
    }
}

// 5. Extend suppliers table with contact fields
echo PHP_EOL . "--- Extending suppliers table ---" . PHP_EOL;
$alterations = [
    "ALTER TABLE suppliers ADD COLUMN contact_person VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE suppliers ADD COLUMN phone VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE suppliers ADD COLUMN email VARCHAR(150) DEFAULT NULL",
    "ALTER TABLE suppliers ADD COLUMN address VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE suppliers ADD COLUMN supplies_items TEXT DEFAULT NULL",
];

foreach ($alterations as $sql) {
    try {
        $pdo->exec($sql);
        echo "  ✅ " . substr($sql, 0, 60) . "..." . PHP_EOL;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  ⏭️  Column already exists, skipping." . PHP_EOL;
        } else {
            echo "  ❌ " . $e->getMessage() . PHP_EOL;
        }
    }
}

// 6. Migrate existing recipe→FG into mix_outputs (if not already done)
echo PHP_EOL . "--- Migrating existing recipes to mix_outputs ---" . PHP_EOL;
$existingOutputs = $pdo->query("SELECT COUNT(*) FROM mix_outputs")->fetchColumn();
if ($existingOutputs == 0) {
    $recipes = $pdo->query("SELECT recipe_id, finished_item_id, base_yield_qty FROM recipes")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recipes as $r) {
        try {
            $pdo->prepare("INSERT INTO mix_outputs (recipe_id, item_id, output_quantity) VALUES (?, ?, ?)")
                ->execute([$r['recipe_id'], $r['finished_item_id'], $r['base_yield_qty']]);
            echo "  ✅ Recipe {$r['recipe_id']} → {$r['finished_item_id']} (qty: {$r['base_yield_qty']})" . PHP_EOL;
        } catch (PDOException $e) {
            echo "  ❌ Recipe {$r['recipe_id']}: " . $e->getMessage() . PHP_EOL;
        }
    }
} else {
    echo "  ⏭️  mix_outputs already has data ($existingOutputs rows), skipping migration." . PHP_EOL;
}

echo PHP_EOL . "=== Migration v2 Complete ===" . PHP_EOL;
