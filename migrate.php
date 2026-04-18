<?php
require 'config/db_connect.php';
try {
    $pdo->exec('ALTER TABLE purchase_orders ADD COLUMN item_id VARCHAR(50) DEFAULT NULL');
    $pdo->exec('ALTER TABLE purchase_orders ADD COLUMN requested_quantity DECIMAL(12,3) DEFAULT NULL');
    echo "Success\n";
} catch(Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Already migrated\n";
    } else {
        echo $e->getMessage();
    }
}
