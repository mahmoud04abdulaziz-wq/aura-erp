<?php
/**
 * AURA ERP — Create Purchase Order
 * Inserts a new PO into purchase_orders table with auto-generated ID.
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

$supplier_id = intval($_POST['supplier_id'] ?? 0);
$order_date = trim($_POST['order_date'] ?? date('Y-m-d'));
$item_id = trim($_POST['item_id'] ?? '');
$requested_quantity = floatval($_POST['requested_quantity'] ?? 0);
$currency = trim($_POST['currency'] ?? 'JOD');
$delivery_location = trim($_POST['delivery_location'] ?? 'Main Warehouse');

if ($supplier_id <= 0 || empty($item_id) || $requested_quantity <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Supplier, item, and quantity are required.']);
    exit;
}

try {
    // Lookup cost
    $stmt = $pdo->prepare("SELECT standard_cost FROM item_master WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $standard_cost = $stmt->fetchColumn() ?: 0;
    
    $total_amount = $standard_cost * $requested_quantity;
    // Generate unique PO ID: PO-YYMMDD-XXXX
    $dateStr = date('ymd', strtotime($order_date));
    $countStmt = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE po_id LIKE 'PO-$dateStr%'");
    $count = $countStmt->fetchColumn() + 1;
    $po_id = "PO-$dateStr-" . str_pad($count, 4, '0', STR_PAD_LEFT);

    $user_id = $_SESSION['user_id'] ?? 1;

    $stmt = $pdo->prepare(
        "INSERT INTO purchase_orders (po_id, supplier_id, order_date, total_amount, currency, delivery_location, order_status, payment_status, created_by, item_id, requested_quantity)
         VALUES (?, ?, ?, ?, ?, ?, 'Pending', 'Pending', ?, ?, ?)"
    );
    $stmt->execute([$po_id, $supplier_id, $order_date, $total_amount, $currency, $delivery_location, $user_id, $item_id, $requested_quantity]);

    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'CREATE_PO', ?, 'Success')");
    $logStmt->execute([$user_id, "Created PO $po_id for supplier ID $supplier_id, amount $total_amount $currency"]);

    echo json_encode(['success' => true, 'message' => "Purchase Order $po_id created.", 'po_id' => $po_id]);
} catch (Exception $e) {
    error_log("Create PO error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
