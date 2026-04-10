<?php
/**
 * MiskStone ERP — Create New Recipe
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db_connect.php';
header('Content-Type: application/json');

$name = trim($_POST['recipe_name'] ?? '');
$fgId = trim($_POST['finished_item_id'] ?? '');
$yield = floatval($_POST['base_yield_qty'] ?? 0);
$curing = intval($_POST['curing_time_hours'] ?? 24);

if (empty($name) || empty($fgId) || $yield <= 0) {
    echo json_encode(['error' => 'Recipe name, finished good, and yield quantity are required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO recipes (finished_item_id, recipe_name, base_yield_qty, curing_time_hours) VALUES (?, ?, ?, ?)");
    $stmt->execute([$fgId, $name, $yield, $curing]);
    echo json_encode(['success' => true, 'recipe_id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    error_log("Create recipe error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
