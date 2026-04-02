<?php
/**
 * AURA ERP — Update Invoice/Quotation Status
 * Changes the status of a quotation through its lifecycle.
 * Valid transitions: Draft→Sent, Sent→Accepted, Sent→Rejected
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

$quote_id = intval($_POST['quote_id'] ?? 0);
$new_status = trim($_POST['status'] ?? '');
$valid_statuses = ['Draft', 'Sent', 'Accepted', 'Rejected'];

if ($quote_id <= 0 || !in_array($new_status, $valid_statuses, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid quote ID or status.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE quotations SET status = ? WHERE quote_id = ?");
    $stmt->execute([$new_status, $quote_id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invoice not found.']);
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? 1;
    $inv_display = 'INV-' . date('Y') . '-' . str_pad($quote_id, 3, '0', STR_PAD_LEFT);
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'UPDATE_INVOICE_STATUS', ?, 'Success')");
    $logStmt->execute([$user_id, "Updated invoice $inv_display status to $new_status"]);

    echo json_encode(['success' => true, 'message' => "Invoice $inv_display status updated to $new_status."]);
} catch (Exception $e) {
    error_log("Update invoice status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
