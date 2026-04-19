<?php
/**
 * MiskStone ERP — Receive Purchase Order
 * When PO is received:
 *  1. Credits ALL line items to inventory
 *  2. Records total as Expense in finance_ledger
 *  3. Marks PO as Received + Paid
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

$poId = trim($_POST['po_id'] ?? '');
if (empty($poId)) {
    echo json_encode(['error' => 'PO ID required.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $userId = $_SESSION['user_id'] ?? 1;

    // Verify PO exists and is Pending
    $po = $pdo->prepare("SELECT po.*, s.supplier_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.supplier_id WHERE po.po_id = ?");
    $po->execute([$poId]);
    $po = $po->fetch(PDO::FETCH_ASSOC);

    if (!$po) throw new Exception("Purchase Order not found.");
    if ($po['order_status'] !== 'Pending') throw new Exception("PO is already '{$po['order_status']}'.");

    // Get po_lines
    $lines = $pdo->prepare("SELECT pl.*, im.item_name FROM po_lines pl JOIN item_master im ON pl.item_id = im.item_id WHERE pl.po_id = ?");
    $lines->execute([$poId]);
    $lineItems = $lines->fetchAll(PDO::FETCH_ASSOC);

    $totalCredited = 0;
    $itemsSummary = [];

    if (!empty($lineItems)) {
        // Multi-line PO: credit each line item
        foreach ($lineItems as $line) {
            $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Received', ?, ?, ?)")
                ->execute([$line['item_id'], $line['quantity'], "PO: {$poId}", $userId]);
            $totalCredited += $line['quantity'];
            $itemsSummary[] = "{$line['quantity']}× {$line['item_name']}";
        }
    } elseif (!empty($po['item_id']) && $po['requested_quantity'] > 0) {
        // Legacy single-item PO
        $pdo->prepare("INSERT INTO inventory_ledger (item_id, warehouse_id, transaction_type, quantity_change, reference_id, recorded_by) VALUES (?, 1, 'Received', ?, ?, ?)")
            ->execute([$po['item_id'], $po['requested_quantity'], "PO: {$poId}", $userId]);
        $totalCredited = $po['requested_quantity'];
        $itemsSummary[] = "{$po['requested_quantity']} units";
    }

    // Record expense in Finance
    $amount = (float)$po['total_amount'];
    if ($amount > 0) {
        $txId = 'PROC-' . date('ymd') . '-' . substr(md5($poId), 0, 6);
        $pdo->prepare("INSERT INTO finance_ledger (transaction_id, transaction_date, transaction_type, category, amount, reference_id, recorded_by) VALUES (?, CURRENT_DATE(), 'Expense', 'Procurement Order', ?, ?, ?)")
            ->execute([$txId, $amount, "PO {$poId} — {$po['supplier_name']}", $userId]);
    }

    // Update PO status
    $pdo->prepare("UPDATE purchase_orders SET order_status = 'Received', payment_status = 'Paid' WHERE po_id = ?")->execute([$poId]);

    // Notifications
    require_once __DIR__ . '/../../includes/notifications.php';
    $itemsList = implode(', ', $itemsSummary);
    $currency = $po['currency'] ?: 'JOD';
    addNotification($pdo, "📦 PO Received", "PO {$poId} from {$po['supplier_name']} received. {$itemsList} added to inventory.", 'procurement');
    addNotification($pdo, "📦 Inventory Updated", "Materials received from PO {$poId}: {$itemsList}", 'inventory');
    addNotification($pdo, "💰 Expense Recorded", "Procurement expense: {$currency} " . number_format($amount, 2) . " for PO {$poId}.", 'finance');

    // Log
    $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'RECEIVE_PO', ?, 'Success')")
        ->execute([$userId, "Received PO {$poId} — {$currency} " . number_format($amount, 2)]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "PO {$poId} received! {$totalCredited} units added to inventory. Expense of {$currency} " . number_format($amount, 2) . " recorded.",
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Receive PO Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
