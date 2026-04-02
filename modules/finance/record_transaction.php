<?php
/**
 * AURA ERP — Record Financial Transaction
 * Inserts a new income/expense entry into the finance_ledger table.
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

$transaction_type = trim($_POST['transaction_type'] ?? '');
$category = trim($_POST['category'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$transaction_date = trim($_POST['transaction_date'] ?? '');
$reference_id = trim($_POST['reference_id'] ?? '');

$valid_types = ['Income', 'Expense'];

if (!in_array($transaction_type, $valid_types, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid transaction type. Must be Income or Expense.']);
    exit;
}

if (empty($category) || $amount <= 0 || empty($transaction_date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Category, amount (>0), and date are required.']);
    exit;
}

try {
    // Generate unique transaction ID: TXN-YYMMDD-XXXX
    $dateStr = date('ymd', strtotime($transaction_date));
    $countStmt = $pdo->query("SELECT COUNT(*) FROM finance_ledger WHERE transaction_id LIKE 'TXN-$dateStr%'");
    $count = $countStmt->fetchColumn() + 1;
    $txn_id = "TXN-$dateStr-" . str_pad($count, 4, '0', STR_PAD_LEFT);

    $user_id = $_SESSION['user_id'] ?? 1;

    // Calculate running balance
    $totalIncome = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM finance_ledger WHERE transaction_type = 'Income'")->fetchColumn();
    $totalExpenses = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM finance_ledger WHERE transaction_type = 'Expense'")->fetchColumn();
    $currentBalance = $totalIncome - $totalExpenses;
    $newBalance = $transaction_type === 'Income' ? $currentBalance + $amount : $currentBalance - $amount;

    $stmt = $pdo->prepare(
        "INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by, balance_after_transaction)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$txn_id, $transaction_date, $transaction_type, $category, $amount, $reference_id ?: null, $user_id, $newBalance]);

    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'RECORD_TRANSACTION', ?, 'Success')");
    $logStmt->execute([$user_id, "Recorded $transaction_type of $$amount in category '$category' (ID: $txn_id)"]);

    echo json_encode(['success' => true, 'message' => "Transaction $txn_id recorded.", 'transaction_id' => $txn_id]);
} catch (Exception $e) {
    error_log("Record transaction error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
