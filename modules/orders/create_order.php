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

$customer_id = trim($_POST['customer_id'] ?? '');
$order_date = trim($_POST['order_date'] ?? date('Y-m-d'));
$total_price = floatval($_POST['total_price'] ?? 0);
$delivery_location = trim($_POST['delivery_location'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');
$order_status = 'Pending';
$handled_by = $_SESSION['user_id'] ?? null;

if (empty($customer_id) || empty($total_price) || !$handled_by) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields (Customer, Total Price, or Session issue).']);
    exit;
}

// Generate unique SO ID (e.g., SO-260319-XXXX)
$so_id = 'SO-' . date('ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

try {
    $stmt = $pdo->prepare("INSERT INTO sales_orders (so_id, customer_id, order_date, total_price, delivery_location, order_status, handled_by, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$so_id, $customer_id, $order_date, $total_price, $delivery_location, $order_status, $handled_by, $payment_method]);
    
    // Log action
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'CREATE_ORDER', ?, 'Success')");
    $logStmt->execute([$handled_by, "Created new sales order: $so_id"]);
    
    echo json_encode(['success' => true, 'message' => 'Order created successfully.', 'so_id' => $so_id]);
} catch (Exception $e) {
    error_log("Failed to create order: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
