<?php
/**
 * AURA ERP — Update PO Payment Status
 * Marks a Purchase Order as Paid.
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
    $stmt = $pdo->prepare("UPDATE purchase_orders SET payment_status = 'Paid' WHERE po_id = ?");
    $stmt->execute([$po_id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Purchase Order not found.']);
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? 1;
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'UPDATE_PO_PAYMENT', ?, 'Success')");
    $logStmt->execute([$user_id, "Marked PO $po_id payment as Paid"]);

    echo json_encode(['success' => true, 'message' => "PO $po_id marked as Paid."]);
} catch (Exception $e) {
    error_log("Update PO payment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
