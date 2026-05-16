<?php
/**
 * MiskStone ERP — Process Delivery
 * Atomic 4-step delivery: stock check → deduct FG → record revenue → mark delivered
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

$soId = trim($_POST['so_id'] ?? '');
if (empty($soId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Sales Order ID is required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Verify the order exists and isn't already delivered
    $orderStmt = $pdo->prepare("SELECT so.*, c.company_name FROM sales_orders so JOIN customers c ON so.customer_id = c.customer_id WHERE so.so_id = ?");
    $orderStmt->execute([$soId]);
    $order = $orderStmt->fetch();

    if (!$order) {
        throw new Exception("Sales Order not found.");
    }
    if (in_array($order['order_status'], ['Delivered', 'Archived'])) {
        throw new Exception("Order is already '{$order['order_status']}'.");
    }

    // 2. Get line items
    $linesStmt = $pdo->prepare("SELECT sol.*, im.item_name FROM sales_order_lines sol JOIN item_master im ON sol.item_id = im.item_id WHERE sol.so_id = ?");
    $linesStmt->execute([$soId]);
    $lines = $linesStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($lines)) {
        throw new Exception("No line items found for this order.");
    }

    $userId = $_SESSION['user_id'] ?? 1;
    $totalRevenue = 0;

    // 3. Stock validation + deduction for each line item
    foreach ($lines as $line) {
        $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
        $stockStmt->execute([$line['item_id']]);
        $currentStock = (float)$stockStmt->fetchColumn();

        if ($currentStock < $line['quantity']) {
            throw new Exception("Insufficient stock for {$line['item_name']}: need {$line['quantity']}, have {$currentStock}.");
        }

        // Deduct from inventory
        $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 3, 'Dispatch', ?, ?, ?)")
            ->execute([$line['item_id'], -$line['quantity'], "Delivery: {$soId}", $userId]);

        $totalRevenue += $line['quantity'] * $line['unit_price'];
    }

    // Use order total_price if it's higher (includes any adjustments)
    if ($order['total_price'] > $totalRevenue) {
        $totalRevenue = $order['total_price'];
    }

    // 4. Record revenue in finance ledger
    $txId = 'REV-' . date('ymd') . '-' . substr(md5($soId), 0, 6);
    $pdo->prepare("INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), 'Income', 'Sales Revenue', ?, ?, ?)")
        ->execute([$txId, $totalRevenue, "Delivery {$soId} — {$order['company_name']}", $userId]);

    // 5. Update order status to Delivered + record delivery timestamp
    $pdo->prepare("UPDATE sales_orders SET order_status = 'Delivered', delivered_at = NOW() WHERE so_id = ?")->execute([$soId]);

    // 6. Notifications
    require_once __DIR__ . '/../../includes/notifications.php';
    addNotification($pdo, "🚛 Order Delivered", "Sales Order {$soId} for {$order['company_name']} dispatched. Revenue: {$totalRevenue} JOD", 'sales');
    addNotification($pdo, "📦 Inventory Dispatched", "Finished goods dispatched for {$soId}.", 'inventory');
    addNotification($pdo, "💰 Revenue Recorded", "+{$totalRevenue} JOD from {$order['company_name']} ({$soId}).", 'finance');

    // 7. System log
    $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'DELIVERY', ?, 'Success')")
        ->execute([$userId, "Dispatched {$soId} — {$order['company_name']} — {$totalRevenue} JOD"]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Order {$soId} delivered! Revenue {$totalRevenue} JOD recorded.",
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Process Delivery error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
