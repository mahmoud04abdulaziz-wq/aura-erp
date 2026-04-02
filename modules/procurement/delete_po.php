<?php
/**
 * AURA ERP — Cancel/Delete Purchase Order
 * Sets PO order_status to Cancelled (soft delete for audit trail).
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
    $stmt = $pdo->prepare("UPDATE purchase_orders SET order_status = 'Cancelled' WHERE po_id = ?");
    $stmt->execute([$po_id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Purchase Order not found.']);
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? 1;
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'CANCEL_PO', ?, 'Success')");
    $logStmt->execute([$user_id, "Cancelled PO $po_id"]);

    echo json_encode(['success' => true, 'message' => "PO $po_id cancelled."]);
} catch (Exception $e) {
    error_log("Cancel PO error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
