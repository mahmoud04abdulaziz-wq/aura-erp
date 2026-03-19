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

$company_name = trim($_POST['company_name'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$email = trim($_POST['email'] ?? '');
$default_address = trim($_POST['default_address'] ?? '');

if (empty($company_name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Company Name is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO customers (company_name, contact_person, phone_number, email, default_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$company_name, $contact_person, $phone_number, $email, $default_address]);
    $newId = $pdo->lastInsertId();
    
    echo json_encode(['success' => true, 'message' => 'Customer created successfully.', 'customer_id' => $newId, 'company_name' => $company_name]);
} catch (Exception $e) {
    error_log("Failed to create customer: " . $e->getMessage());
    http_response_code(500);
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(['error' => 'A customer with this company name already exists.']);
    } else {
        echo json_encode(['error' => 'Database error occurred.']);
    }
}
