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
$order_id = $_POST['order_id'] ?? 'Unknown';

require_once __DIR__ . '/../../includes/notifications.php';

try {
    addNotification($pdo, "URGENT: Material Shortage", "Production for {$order_id} cannot clear Feasibility Check. Action required.", 'procurement');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
