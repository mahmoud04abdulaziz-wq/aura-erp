<?php
/**
 * MiskStone ERP — Archive Order
 * Manually archive an order, or auto-archive old Delivered orders.
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

$action = $_POST['action'] ?? 'archive_single';
$userId = $_SESSION['user_id'] ?? 1;

try {
    if ($action === 'archive_single') {
        // Archive a single order
        $soId = trim($_POST['so_id'] ?? '');
        if (empty($soId)) {
            throw new Exception('Order ID required.');
        }
        $stmt = $pdo->prepare("UPDATE sales_orders SET order_status = 'Archived' WHERE so_id = ? AND order_status IN ('Delivered', 'Completed')");
        $stmt->execute([$soId]);

        if ($stmt->rowCount() > 0) {
            $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'ARCHIVE', ?, 'Success')")
                ->execute([$userId, "Manually archived {$soId}"]);
            echo json_encode(['success' => true, 'message' => "Order {$soId} archived."]);
        } else {
            echo json_encode(['error' => 'Order not found or not eligible for archiving.']);
        }

    } elseif ($action === 'auto_archive') {
        // Auto-archive all Delivered orders older than 24 hours
        $stmt = $pdo->prepare("
            UPDATE sales_orders 
            SET order_status = 'Archived' 
            WHERE order_status = 'Delivered' 
              AND order_date < DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)
        ");
        $stmt->execute();
        $count = $stmt->rowCount();

        if ($count > 0) {
            $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'AUTO_ARCHIVE', ?, 'Success')")
                ->execute([$userId, "Auto-archived {$count} old delivered orders"]);
        }

        echo json_encode(['success' => true, 'archived' => $count, 'message' => "{$count} orders auto-archived."]);
    }
} catch (Exception $e) {
    error_log("Archive error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
