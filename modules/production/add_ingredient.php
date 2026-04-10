<?php
/**
 * MiskStone ERP — Add Ingredient to Recipe
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db_connect.php';
header('Content-Type: application/json');

$recipeId = intval($_POST['recipe_id'] ?? 0);
$rawMaterialId = trim($_POST['raw_material_id'] ?? '');
$qty = floatval($_POST['quantity_required'] ?? 0);

if ($recipeId <= 0 || empty($rawMaterialId) || $qty <= 0) {
    echo json_encode(['error' => 'Recipe ID, raw material, and quantity are required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, raw_material_id, quantity_required) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity_required = ?");
    $stmt->execute([$recipeId, $rawMaterialId, $qty, $qty]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Add ingredient error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
