<?php
/**
 * MiskStone ERP — Handle Material Request
 * Accept: auto-creates PO and links to the request.
 * Decline: records reason.
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$requestId = intval($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$requestId || !in_array($action, ['accept', 'decline'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

try {
    $userId = $_SESSION['user_id'] ?? 1;

    // Get request details
    $reqStmt = $pdo->prepare("SELECT mr.*, im.item_name FROM material_requests mr JOIN item_master im ON mr.item_id = im.item_id WHERE mr.request_id = ?");
    $reqStmt->execute([$requestId]);
    $req = $reqStmt->fetch();

    if (!$req || $req['status'] !== 'Requested') {
        echo json_encode(['error' => 'Request not found or already handled.']);
        exit;
    }

    if ($action === 'accept') {
        $supplierId = intval($_POST['supplier_id'] ?? 1);
        $totalAmount = $req['quantity_requested'] * $req['unit_price'];

        // Create PO
        $poId = 'PO-' . date('ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO purchase_orders (po_id, supplier_id, order_date, total_amount, currency, payment_status, order_status, delivery_location, item_id, requested_quantity) VALUES (?, ?, CURRENT_DATE(), ?, 'JOD', 'Unpaid', 'Pending', 'Main Warehouse', ?, ?)")
            ->execute([$poId, $supplierId, $totalAmount, $req['item_id'], $req['quantity_requested']]);

        // Update request
        $pdo->prepare("UPDATE material_requests SET status = 'Accepted', handled_by = ?, po_id = ?, resolved_at = CURRENT_TIMESTAMP WHERE request_id = ?")
            ->execute([$userId, $poId, $requestId]);

        require_once __DIR__ . '/../../includes/notifications.php';
        addNotification($pdo, "✅ Request Accepted", "Material request #{$requestId} for {$req['item_name']} accepted. PO: {$poId}", 'procurement');
        addNotification($pdo, "📦 Request Approved", "Your request for {$req['quantity_requested']} of {$req['item_name']} was approved (PO: {$poId})", 'manufacturing');

        echo json_encode(['success' => true, 'po_id' => $poId, 'message' => "Request accepted. PO {$poId} created."]);

    } elseif ($action === 'decline') {
        $reason = trim($_POST['decline_reason'] ?? 'No reason given');

        $pdo->prepare("UPDATE material_requests SET status = 'Declined', handled_by = ?, decline_reason = ?, resolved_at = CURRENT_TIMESTAMP WHERE request_id = ?")
            ->execute([$userId, $reason, $requestId]);

        require_once __DIR__ . '/../../includes/notifications.php';
        addNotification($pdo, "❌ Request Declined", "Material request #{$requestId} for {$req['item_name']} was declined: {$reason}", 'manufacturing');

        echo json_encode(['success' => true, 'message' => "Request declined."]);
    }

} catch (PDOException $e) {
    error_log("Handle Request error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
