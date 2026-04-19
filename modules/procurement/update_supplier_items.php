<?php
/** MiskStone ERP — Update Supplier Items */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

$supplierId = $_POST['supplier_id'] ?? '';
$itemIds = $_POST['item_ids'] ?? [];

if (empty($supplierId)) {
    echo json_encode(['error' => 'Supplier ID required.']);
    exit;
}

try {
    // Clear existing mappings
    $pdo->prepare("DELETE FROM supplier_items WHERE supplier_id = ?")->execute([$supplierId]);

    // Insert new mappings
    if (!empty($itemIds)) {
        $stmt = $pdo->prepare("INSERT INTO supplier_items (supplier_id, item_id, is_preferred) VALUES (?, ?, 1)");
        foreach ($itemIds as $itemId) {
            $stmt->execute([$supplierId, $itemId]);
        }
    }

    echo json_encode(['success' => true, 'count' => count($itemIds)]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
