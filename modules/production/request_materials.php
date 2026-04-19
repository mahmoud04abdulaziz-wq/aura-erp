<?php
/**
 * MiskStone ERP — Request Materials Endpoint
 * POST handler for Production to submit material requests.
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

$itemId = trim($_POST['item_id'] ?? '');
$quantity = floatval($_POST['quantity'] ?? 0);
$unitPrice = floatval($_POST['unit_price'] ?? 0);
$urgency = $_POST['urgency'] ?? 'Normal';
$reason = trim($_POST['reason'] ?? '');

if (empty($itemId) || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Item and quantity are required.']);
    exit;
}

// Validate urgency
if (!in_array($urgency, ['Normal', 'High', 'Critical'])) {
    $urgency = 'Normal';
}

try {
    $userId = $_SESSION['user_id'] ?? 1;

    $stmt = $pdo->prepare("INSERT INTO material_requests (requested_by, item_id, quantity_requested, unit_price, reason, urgency) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $itemId, $quantity, $unitPrice, $reason, $urgency]);

    // Notify procurement
    require_once __DIR__ . '/../../includes/notifications.php';
    $itemName = $pdo->prepare("SELECT item_name FROM item_master WHERE item_id = ?");
    $itemName->execute([$itemId]);
    $name = $itemName->fetchColumn();

    addNotification($pdo, "📦 Material Request", "Production needs {$quantity} of {$name} (urgency: {$urgency})", 'procurement');

    // Log
    $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'MATERIAL_REQUEST', ?, 'Success')")
        ->execute([$userId, "Requested {$quantity} of {$name}"]);

    echo json_encode(['success' => true, 'message' => 'Request submitted successfully.']);

} catch (PDOException $e) {
    error_log("Material Request error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
