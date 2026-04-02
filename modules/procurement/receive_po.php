<?php
/**
 * AURA ERP — Receive Purchase Order
 * Updates PO order_status from Pending to Received.
 */
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

global $pdo;

$po_id = trim($_POST['po_id'] ?? '');

if (empty($po_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'PO ID is required.']);
    exit;
}

try {
    // Verify PO exists and is Pending
    $check = $pdo->prepare("SELECT order_status FROM purchase_orders WHERE po_id = ?");
    $check->execute([$po_id]);
    $po = $check->fetch();

    if (!$po) {
        http_response_code(404);
        echo json_encode(['error' => 'Purchase Order not found.']);
        exit;
    }

    if ($po['order_status'] !== 'Pending') {
        http_response_code(400);
        echo json_encode(['error' => "Cannot receive PO — current status is '{$po['order_status']}'."]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE purchase_orders SET order_status = 'Received' WHERE po_id = ?");
    $stmt->execute([$po_id]);

    $user_id = $_SESSION['user_id'] ?? 1;
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'RECEIVE_PO', ?, 'Success')");
    $logStmt->execute([$user_id, "Marked PO $po_id as Received"]);

    echo json_encode(['success' => true, 'message' => "PO $po_id marked as Received."]);
} catch (Exception $e) {
    error_log("Receive PO error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
