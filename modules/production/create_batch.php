<?php
/**
 * MiskStone ERP — Create Production Batch
 * Now accepts recipe_id and derives item_id from the recipe's finished_item_id.
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipeId = $_POST['recipe_id'] ?? '';
    $machineId = $_POST['machine_id'] ?? null;
    $targetQty = $_POST['target_quantity'] ?? 1;
    $operatorId = $_POST['operator_user_id'] ?? $_SESSION['user_id'];
    $prodDate = $_POST['production_date'] ?? date('Y-m-d');
    $soId = $_POST['so_id'] ?? null;

    // Support legacy item_id-based creation too
    $itemId = $_POST['item_id'] ?? '';

    if (empty($recipeId) && empty($itemId)) {
        echo json_encode(['success' => false, 'error' => 'Recipe or Item ID required.']);
        exit;
    }
    if ($targetQty <= 0) {
        echo json_encode(['success' => false, 'error' => 'Target quantity must be > 0.']);
        exit;
    }

    try {
        // If recipe_id provided, get the finished_item_id from the recipe
        if (!empty($recipeId)) {
            $recipe = $pdo->prepare("SELECT finished_item_id FROM recipes WHERE recipe_id = ?");
            $recipe->execute([$recipeId]);
            $row = $recipe->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                echo json_encode(['success' => false, 'error' => 'Recipe not found.']);
                exit;
            }
            $itemId = $row['finished_item_id'];
        }

        // Generate batch ID
        $dateSuffix = date('ymd');
        $randomSuffix = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $productionId = "MX-{$dateSuffix}-{$randomSuffix}";

        $stmt = $pdo->prepare("
            INSERT INTO production_orders 
            (production_id, item_id, recipe_id, so_id, machine_id, production_date, target_quantity, operator_user_id, status, qa_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Planned', 'Pending')
        ");
        $stmt->execute([$productionId, $itemId, $recipeId ?: null, $soId, $machineId, $prodDate, $targetQty, $operatorId]);

        // Log
        $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'CREATE_BATCH', ?, 'Success')")
            ->execute([$_SESSION['user_id'] ?? 1, "Created batch {$productionId} (Recipe #{$recipeId})"]);

        echo json_encode(['success' => true, 'production_id' => $productionId]);
    } catch (PDOException $e) {
        error_log("Create Batch Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
