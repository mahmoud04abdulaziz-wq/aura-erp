<?php
/**
 * MiskStone ERP — Create Purchase Order (Multi-Line)
 * Accepts: supplier_id, delivery_location, lines[{item_id, quantity, unit_price}]
 * Creates a PO header + po_lines. NO expense recorded here — only on receipt.
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

$supplierId = $_POST['supplier_id'] ?? '';
$deliveryLocation = $_POST['delivery_location'] ?? 'Main Warehouse';
$lines = $_POST['lines'] ?? [];

// Legacy single-item support
$legacyItemId = $_POST['item_id'] ?? '';
$legacyQty = $_POST['requested_quantity'] ?? 0;

if (empty($supplierId)) {
    echo json_encode(['error' => 'Supplier is required.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $userId = $_SESSION['user_id'] ?? 1;

    // Get supplier info
    $supplier = $pdo->prepare("SELECT supplier_name, preferred_currency FROM suppliers WHERE supplier_id = ?");
    $supplier->execute([$supplierId]);
    $supplierRow = $supplier->fetch(PDO::FETCH_ASSOC);
    if (!$supplierRow) throw new Exception('Supplier not found.');

    $currency = $supplierRow['preferred_currency'] ?? 'JOD';

    // Generate PO ID
    $poId = 'PO-' . date('ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

    // Handle legacy single-item format
    if (empty($lines) && !empty($legacyItemId) && $legacyQty > 0) {
        $itemCost = $pdo->prepare("SELECT standard_cost FROM item_master WHERE item_id = ?");
        $itemCost->execute([$legacyItemId]);
        $unitPrice = (float)$itemCost->fetchColumn();
        $lines = [['item_id' => $legacyItemId, 'quantity' => $legacyQty, 'unit_price' => $unitPrice]];
    }

    if (empty($lines)) {
        throw new Exception('At least one line item is required.');
    }

    // Calculate total
    $totalAmount = 0;
    foreach ($lines as $line) {
        $totalAmount += (float)$line['quantity'] * (float)$line['unit_price'];
    }

    // Insert PO header
    $pdo->prepare("
        INSERT INTO purchase_orders (po_id, supplier_id, order_date, total_amount, currency, delivery_location, order_status, payment_status, created_by)
        VALUES (?, ?, CURRENT_DATE(), ?, ?, ?, 'Pending', 'Pending', ?)
    ")->execute([$poId, $supplierId, $totalAmount, $currency, $deliveryLocation, $userId]);

    // Insert PO lines
    $lineStmt = $pdo->prepare("INSERT INTO po_lines (po_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    foreach ($lines as $line) {
        $lineStmt->execute([$poId, $line['item_id'], $line['quantity'], $line['unit_price']]);
    }

    // Log
    $lineCount = count($lines);
    $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'CREATE_PO', ?, 'Success')")
        ->execute([$userId, "Created PO {$poId} with {$lineCount} lines — {$currency} " . number_format($totalAmount, 2)]);

    // Notify
    require_once __DIR__ . '/../../includes/notifications.php';
    addNotification($pdo, "📋 PO Created", "Purchase Order {$poId} for {$supplierRow['supplier_name']} — {$currency} " . number_format($totalAmount, 2) . " ({$lineCount} items).", 'procurement');

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'po_id' => $poId,
        'total' => $totalAmount,
        'lines' => $lineCount,
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Create PO Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
