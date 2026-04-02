<?php
/**
 * AURA ERP — Delete Invoice/Quotation
 * Removes a quotation. Only Draft/Rejected invoices can be deleted.
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

if ($quote_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invoice ID is required.']);
    exit;
}

try {
    // Only allow deletion of Draft or Rejected invoices
    $check = $pdo->prepare("SELECT status FROM quotations WHERE quote_id = ?");
    $check->execute([$quote_id]);
    $inv = $check->fetch();

    if (!$inv) {
        http_response_code(404);
        echo json_encode(['error' => 'Invoice not found.']);
        exit;
    }

    if (!in_array($inv['status'], ['Draft', 'Rejected'], true)) {
        http_response_code(400);
        echo json_encode(['error' => "Cannot delete invoice with status '{$inv['status']}'. Only Draft or Rejected invoices can be removed."]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM quotations WHERE quote_id = ?");
    $stmt->execute([$quote_id]);

    $user_id = $_SESSION['user_id'] ?? 1;
    $inv_display = 'INV-' . date('Y') . '-' . str_pad($quote_id, 3, '0', STR_PAD_LEFT);
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'DELETE_INVOICE', ?, 'Success')");
    $logStmt->execute([$user_id, "Deleted invoice $inv_display"]);

    echo json_encode(['success' => true, 'message' => "Invoice $inv_display deleted."]);
} catch (Exception $e) {
    error_log("Delete invoice error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
