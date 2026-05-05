<?php
/**
 * AURA ERP — Database Connection
 * Uses PDO to connect to the aura_stone_erp database on XAMPP MySQL.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'aura_stone_erp');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("System error. Please contact the administrator.");
}

// CLI test mode
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    echo "✓ Connection successful. Server: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL;
}
