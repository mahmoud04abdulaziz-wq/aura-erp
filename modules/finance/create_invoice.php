<?php
/**
 * AURA ERP — Create Invoice/Quotation
 * Inserts a new quotation into the quotations table.
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

$customer_id = intval($_POST['customer_id'] ?? 0);
$total_amount = floatval($_POST['total_amount'] ?? 0);
$valid_until = trim($_POST['valid_until'] ?? '');

if ($customer_id <= 0 || $total_amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Customer and amount are required.']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'] ?? 1;

    $stmt = $pdo->prepare(
        "INSERT INTO quotations (customer_id, total_amount, valid_until, status, prepared_by, created_at)
         VALUES (?, ?, ?, 'Draft', ?, NOW())"
    );
    $stmt->execute([
        $customer_id,
        $total_amount,
        !empty($valid_until) ? $valid_until : null,
        $user_id
    ]);

    $quote_id = $pdo->lastInsertId();
    $inv_display = 'INV-' . date('Y') . '-' . str_pad($quote_id, 3, '0', STR_PAD_LEFT);

    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'CREATE_INVOICE', ?, 'Success')");
    $logStmt->execute([$user_id, "Created invoice $inv_display for customer ID $customer_id, amount $$total_amount"]);

    echo json_encode(['success' => true, 'message' => "Invoice $inv_display created.", 'quote_id' => $quote_id]);
} catch (Exception $e) {
    error_log("Create invoice error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
