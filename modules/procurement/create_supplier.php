<?php
/**
 * AURA ERP — Create Supplier
 * Inserts a new supplier into the suppliers table.
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

$supplier_name = trim($_POST['supplier_name'] ?? '');
$preferred_currency = trim($_POST['preferred_currency'] ?? 'JOD');
$valid_currencies = ['JOD', 'USD', 'EUR', 'GBP', 'SAR', 'AED'];

if (empty($supplier_name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Supplier name is required.']);
    exit;
}

if (!in_array($preferred_currency, $valid_currencies, true)) {
    $preferred_currency = 'JOD';
}

try {
    $stmt = $pdo->prepare("INSERT INTO suppliers (supplier_name, preferred_currency) VALUES (?, ?)");
    $stmt->execute([$supplier_name, $preferred_currency]);

    $user_id = $_SESSION['user_id'] ?? 1;
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'CREATE_SUPPLIER', ?, 'Success')");
    $logStmt->execute([$user_id, "Created supplier: $supplier_name"]);

    echo json_encode(['success' => true, 'message' => 'Supplier created successfully.']);
} catch (Exception $e) {
    error_log("Create supplier error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
