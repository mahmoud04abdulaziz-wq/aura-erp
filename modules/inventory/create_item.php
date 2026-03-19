<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../../config/db.php';

$item_id = $_POST['item_id'] ?? '';
$item_name = $_POST['item_name'] ?? '';
$category = $_POST['category'] ?? '';
$base_uom = $_POST['base_uom'] ?? '';
$standard_cost = (float)($_POST['standard_cost'] ?? 0);
$min_stock_level = (float)($_POST['min_stock_level'] ?? 0);

if (!$item_id || !$item_name || !$category || !$base_uom) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO item_master 
        (item_id, item_name, category, default_supplier_id, base_uom, standard_cost, min_stock_level)
        VALUES (?, ?, ?, NULL, ?, ?, ?)
    ");
    $stmt->execute([
        $item_id,
        $item_name,
        $category,
        $base_uom,
        $standard_cost,
        $min_stock_level
    ]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Handling duplicate entry error securely
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'error' => "Item ID '{$item_id}' already exists."]);
    } else {
        error_log("Create Item Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}
?>
