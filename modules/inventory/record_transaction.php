<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../../config/db.php';

$item_id = $_POST['item_id'] ?? '';
$transaction_type = $_POST['transaction_type'] ?? '';
$quantity_change = (float)($_POST['quantity_change'] ?? 0);
$recorded_by = $_SESSION['user_id'];
// For a production app, the warehouse ID would be selected via a dropdown.
// Defaulting to Main Warehouse if it exists, or NULL for prototyping.
$warehouse_id = NULL;

if (!$item_id || !$transaction_type || $quantity_change == 0) {
    echo json_encode(['success' => false, 'error' => 'Missing valid item ID, type, or quantity']);
    exit;
}

try {
    // 1. Verify item exists first
    $stmt = $pdo->prepare("SELECT item_id FROM item_master WHERE item_id = ?");
    $stmt->execute([$item_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => "Item ID '{$item_id}' not found in master list."]);
        exit;
    }

    // 2. Insert into ledger
    $stmt = $pdo->prepare("
        INSERT INTO inventory_ledger 
        (item_id, warehouse_id, transaction_type, quantity_change, reference_doc, recorded_by, timestamp)
        VALUES (?, ?, ?, ?, 'Manual Log', ?, NOW())
    ");
    
    $stmt->execute([
        $item_id,
        $warehouse_id,
        $transaction_type,
        $quantity_change,
        $recorded_by
    ]);

    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Log Transaction Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database exception occurred.']);
}
?>
