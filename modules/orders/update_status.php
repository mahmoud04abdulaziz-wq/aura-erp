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

$so_id = trim($_POST['so_id'] ?? '');
$status = trim($_POST['status'] ?? '');
$valid_statuses = ['Pending', 'In Production', 'Pending Delivery', 'Delivered'];

if (empty($so_id) || !in_array($status, $valid_statuses, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID or status value.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE sales_orders SET order_status = ? WHERE so_id = ?");
    $stmt->execute([$status, $so_id]);
    
    $handled_by = $_SESSION['user_id'] ?? 1;
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'UPDATE_ORDER_STATUS', ?, 'Success')");
    $logStmt->execute([$handled_by, "Updated order $so_id status to $status"]);

    // Push to Live Notification Feed
    require_once __DIR__ . '/../../includes/notifications.php';
    addNotification($pdo, "Order Status Updated", "Order #{$so_id} is now: {$status}", 'finance');
    // --- PIPELINE AUTOMATION HOOKS ---
    if ($status === 'In Production') {
        // Find a representative Finished Good to produce (for demo purposes if no line items exist)
        $fgStmt = $pdo->query("SELECT item_id FROM item_master WHERE category = 'Finished Good' LIMIT 1");
        $fg = $fgStmt->fetchColumn();
        if ($fg) {
            $prod_id = 'PRD-' . date('Ymd') . '-' . mt_rand(100, 999);
            $target_qty = 100; // Simulated batch size
            $insertProd = $pdo->prepare("INSERT INTO production_orders (production_id, item_id, target_quantity, status, operator_user_id) VALUES (?, ?, ?, 'Planned', ?)");
            $op_id = $_SESSION['user_id'] ?? 1;
            $insertProd->execute([$prod_id, $fg, $target_qty, $op_id]);
            
            addNotification($pdo, "New Production Batch", "Sales Order {$so_id} triggered Production Batch {$prod_id}", 'manufacturing');
        }
    } elseif ($status === 'Pending Delivery') {
        // --- PIPELINE AUTOMATION HOOK (Automate Warehouse) ---
        // Deduct Raw Materials and Add Finished Goods based on assuming 1 batch of target FG.
        // For the sake of the demo, we will insert a ledger entry for the parent finished good.
        $fgStmt = $pdo->query("SELECT item_id FROM item_master WHERE category = 'Finished Good' LIMIT 1");
        $fg = $fgStmt->fetchColumn();
        if ($fg) {
            $ledger = $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Produced', 100, ?, ?)");
            $ledger->execute([$fg, "SO-BATCH-" . $so_id, $_SESSION['user_id'] ?? 1]);
            
            // Deduct an arbitrary raw material to prove automation
            $rmStmt = $pdo->query("SELECT item_id FROM item_master WHERE category = 'Raw Material' LIMIT 1");
            $rm = $rmStmt->fetchColumn();
            if ($rm) {
               $ledger = $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Consumed', -300, ?, ?)");
               $ledger->execute([$rm, "SO-BATCH-" . $so_id, $_SESSION['user_id'] ?? 1]);
            }
            
            addNotification($pdo, "Inventory Updated", "Batch completed for {$so_id}. Stock levels automatically updated.", 'inventory');
        }
    } elseif ($status === 'Delivered') {
        // Realize Financial Income
        $soStmt = $pdo->prepare("SELECT total_price FROM sales_orders WHERE so_id = ?");
        $soStmt->execute([$so_id]);
        $val = $soStmt->fetchColumn();
        
        $trans_id = 'INC-' . time();
        $recordFin = $pdo->prepare("INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), 'Income', 'Sales Revenue', ?, ?, ?)");
        $recordFin->execute([$trans_id, $val, $so_id, $_SESSION['user_id'] ?? 1]);
        
        addNotification($pdo, "Revenue Recorded", "Payment of $" . number_format($val, 2) . " received for {$so_id}", 'finance');
    }

    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} catch (Exception $e) {
    error_log("Failed to update status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred.']);
}
