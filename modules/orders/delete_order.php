<?php
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

global $pdo;

$so_id = trim($_POST['so_id'] ?? '');

if (empty($so_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM sales_orders WHERE so_id = ?");
    $stmt->execute([$so_id]);
    
    $handled_by = $_SESSION['user_id'] ?? 1;
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'DELETE_ORDER', ?, 'Success')");
    $logStmt->execute([$handled_by, "Deleted order $so_id"]);
    
    echo json_encode(['success' => true, 'message' => 'Order deleted successfully.']);
} catch (Exception $e) {
    error_log("Failed to delete order: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Cannot delete order (may be referenced elsewhere).']);
}
