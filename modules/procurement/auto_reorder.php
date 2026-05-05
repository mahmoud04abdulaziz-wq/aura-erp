<?php
/**
 * MiskStone ERP — Auto-Reorder Engine
 * Scans raw materials below ROP threshold and auto-generates Material Requests.
 * Prevents duplicates by skipping items with pending requests or pending POs.
 */
require_once dirname(__DIR__, 2) . '/config/db_connect.php';
require_once dirname(__DIR__) . '/auth/session_guard.php';
requireLogin();

require_once dirname(__DIR__, 2) . '/includes/math_models.php';
require_once dirname(__DIR__, 2) . '/includes/notifications.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

$userId = $_SESSION['user_id'] ?? 1;

try {
    // Step 1: Calculate ROP for all raw materials
    $alerts = calculateReorderPoints($pdo);

    // Step 2: Find items that need reorder (Warning or Critical)
    $needsReorder = array_filter($alerts, fn($a) => $a['status'] !== 'Safe');

    if (empty($needsReorder)) {
        echo json_encode(['success' => true, 'message' => 'All raw materials are above reorder point. No action needed.', 'created' => 0]);
        exit;
    }

    // Step 3: Get items with existing pending material requests
    $pendingReqs = $pdo->query("
        SELECT item_id FROM material_requests WHERE status = 'Requested'
    ")->fetchAll(PDO::FETCH_COLUMN);

    // Step 4: Get items with existing pending purchase orders
    $pendingPOs = $pdo->query("
        SELECT item_id FROM purchase_orders WHERE order_status = 'Pending' AND item_id IS NOT NULL
    ")->fetchAll(PDO::FETCH_COLUMN);

    $skipItems = array_unique(array_merge($pendingReqs, $pendingPOs));

    // Step 5: Generate material requests for items that need reorder
    $insertStmt = $pdo->prepare("
        INSERT INTO material_requests (requested_by, item_id, quantity_requested, unit_price, reason, urgency, status)
        VALUES (?, ?, ?, ?, ?, ?, 'Requested')
    ");

    $created = 0;
    $skipped = 0;
    $details = [];

    foreach ($needsReorder as $item) {
        $itemId = $item['item_id'];

        // Skip if already has a pending request or PO
        if (in_array($itemId, $skipItems)) {
            $skipped++;
            continue;
        }

        // Calculate recommended order quantity:
        // Enough to bring stock back to 2× ROP level
        $targetStock = $item['calculated_rop'] * 2;
        $orderQty = max(1, round($targetStock - $item['current_stock'], 0));

        // Get unit cost from item_master
        $costStmt = $pdo->prepare("SELECT standard_cost FROM item_master WHERE item_id = ?");
        $costStmt->execute([$itemId]);
        $unitCost = (float)($costStmt->fetchColumn() ?: 0);

        // Set urgency based on ROP status
        $urgency = $item['status'] === 'Critical' ? 'Critical' : 'High';

        $reason = "Auto-Reorder: {$item['item_name']} is at {$item['current_stock']} {$item['uom']} " .
                  "(ROP: {$item['calculated_rop']}). Status: {$item['status']}.";

        $insertStmt->execute([
            $userId,
            $itemId,
            $orderQty,
            $unitCost,
            $reason,
            $urgency
        ]);

        $created++;
        $details[] = "{$item['item_name']} ×{$orderQty} {$item['uom']} ({$urgency})";
    }

    // Step 6: Push notifications
    if ($created > 0) {
        $detailStr = implode(', ', array_slice($details, 0, 3));
        if (count($details) > 3) $detailStr .= ' +' . (count($details) - 3) . ' more';

        addNotification($pdo, "🔄 Auto-Reorder Triggered",
            "{$created} material request(s) generated: {$detailStr}",
            'inventory', null);

        addNotification($pdo, "📋 Procurement Action Required",
            "{$created} auto-generated material request(s) awaiting approval. Review in Material Requests.",
            'system', null);
    }

    $message = "{$created} material request(s) created.";
    if ($skipped > 0) $message .= " {$skipped} skipped (already have pending requests/POs).";

    echo json_encode([
        'success' => true,
        'message' => $message,
        'created' => $created,
        'skipped' => $skipped,
        'details' => $details
    ]);

} catch (Exception $e) {
    error_log("Auto-reorder error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
