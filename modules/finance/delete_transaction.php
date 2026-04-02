<?php
/**
 * AURA ERP — Delete Financial Transaction
 * Removes a transaction entry from finance_ledger.
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

$transaction_id = trim($_POST['transaction_id'] ?? '');

if (empty($transaction_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Transaction ID is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM finance_ledger WHERE transaction_id = ?");
    $stmt->execute([$transaction_id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found.']);
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? 1;
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'DELETE_TRANSACTION', ?, 'Success')");
    $logStmt->execute([$user_id, "Deleted transaction $transaction_id"]);

    echo json_encode(['success' => true, 'message' => 'Transaction deleted.']);
} catch (Exception $e) {
    error_log("Delete transaction error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
