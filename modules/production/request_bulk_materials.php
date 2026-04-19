<?php
/**
 * MiskStone ERP — Bulk Material Request from BOM Shortage
 * POST: production_id
 * Runs BOM check, creates material_requests for all shortages at once.
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$prodId = trim($_POST['production_id'] ?? '');
if (empty($prodId)) {
    echo json_encode(['error' => 'Production ID required.']);
    exit;
}

try {
    $userId = $_SESSION['user_id'] ?? 1;

    // Get batch
    $batch = $pdo->prepare("SELECT * FROM production_orders WHERE production_id = ?");
    $batch->execute([$prodId]);
    $batch = $batch->fetch(PDO::FETCH_ASSOC);
    if (!$batch) throw new Exception('Batch not found.');

    // Find recipe
    if (!empty($batch['recipe_id'])) {
        $recipe = $pdo->prepare("SELECT * FROM recipes WHERE recipe_id = ?");
        $recipe->execute([$batch['recipe_id']]);
    } else {
        $recipe = $pdo->prepare("SELECT * FROM recipes WHERE finished_item_id = ? LIMIT 1");
        $recipe->execute([$batch['item_id']]);
    }
    $recipe = $recipe->fetch(PDO::FETCH_ASSOC);
    if (!$recipe) throw new Exception('No recipe found.');

    $scaleFactor = max(1, (float)$batch['target_quantity']) / max(1, (float)$recipe['base_yield_qty']);

    // Get ingredients and check shortages
    $ingStmt = $pdo->prepare("
        SELECT ri.raw_material_id, ri.quantity_required, im.item_name, im.standard_cost
        FROM recipe_ingredients ri
        JOIN item_master im ON ri.raw_material_id = im.item_id
        WHERE ri.recipe_id = ?
    ");
    $ingStmt->execute([$recipe['recipe_id']]);

    $requestCount = 0;
    $insertStmt = $pdo->prepare("
        INSERT INTO material_requests (requested_by, item_id, quantity_requested, unit_price, reason, urgency)
        VALUES (?, ?, ?, ?, ?, 'High')
    ");

    foreach ($ingStmt->fetchAll(PDO::FETCH_ASSOC) as $ing) {
        $required = round($ing['quantity_required'] * $scaleFactor, 3);

        $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
        $stockStmt->execute([$ing['raw_material_id']]);
        $currentStock = (float)$stockStmt->fetchColumn();

        $shortfall = $required - $currentStock;
        if ($shortfall > 0) {
            // Add 20% buffer to the request
            $requestQty = round($shortfall * 1.2, 3);
            $reason = "BOM shortage for batch {$prodId} ({$recipe['recipe_name']}). Need {$required}, have {$currentStock}.";

            $insertStmt->execute([$userId, $ing['raw_material_id'], $requestQty, $ing['standard_cost'], $reason]);
            $requestCount++;
        }
    }

    if ($requestCount > 0) {
        // Notify procurement
        require_once __DIR__ . '/../../includes/notifications.php';
        addNotification($pdo, "📦 Bulk Material Request", "{$requestCount} materials needed for batch {$prodId}. Review in Incoming Requests.", 'procurement');

        // Log
        $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'BULK_MATERIAL_REQUEST', ?, 'Success')")
            ->execute([$userId, "Requested {$requestCount} materials for batch {$prodId}"]);
    }

    echo json_encode([
        'success' => true,
        'requests_created' => $requestCount,
        'message' => "{$requestCount} material request(s) sent to Procurement."
    ]);

} catch (Exception $e) {
    error_log("Bulk Material Request Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
