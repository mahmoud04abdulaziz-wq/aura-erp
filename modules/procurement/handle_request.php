<?php
/**
 * MiskStone ERP — Handle Material Request (Accept/Decline/Bulk Accept)
 * Actions: accept, decline, accept_bulk
 * On accept: auto-finds best supplier via supplier_items → creates multi-line PO
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

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'] ?? 1;

try {
    $pdo->beginTransaction();
    require_once __DIR__ . '/../../includes/notifications.php';

    if ($action === 'accept') {
        // Single request accept
        $reqId = $_POST['request_id'] ?? '';
        $supplierId = $_POST['supplier_id'] ?? '';
        if (empty($reqId) || empty($supplierId)) throw new Exception('Request ID and Supplier required.');

        $req = $pdo->prepare("SELECT mr.*, im.item_name, im.standard_cost FROM material_requests mr JOIN item_master im ON mr.item_id = im.item_id WHERE mr.request_id = ?");
        $req->execute([$reqId]);
        $req = $req->fetch(PDO::FETCH_ASSOC);
        if (!$req) throw new Exception('Request not found.');

        // Create PO
        $poId = 'PO-' . date('ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $unitPrice = $req['unit_price'] ?: $req['standard_cost'];
        $totalAmount = $req['quantity_requested'] * $unitPrice;

        $supplier = $pdo->prepare("SELECT supplier_name, preferred_currency FROM suppliers WHERE supplier_id = ?");
        $supplier->execute([$supplierId]);
        $supplierRow = $supplier->fetch(PDO::FETCH_ASSOC);
        $currency = $supplierRow['preferred_currency'] ?? 'JOD';

        $pdo->prepare("INSERT INTO purchase_orders (po_id, supplier_id, order_date, total_amount, currency, delivery_location, order_status, payment_status, created_by) VALUES (?, ?, CURRENT_DATE(), ?, ?, 'Main Warehouse', 'Pending', 'Pending', ?)")
            ->execute([$poId, $supplierId, $totalAmount, $currency, $userId]);

        $pdo->prepare("INSERT INTO po_lines (po_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)")
            ->execute([$poId, $req['item_id'], $req['quantity_requested'], $unitPrice]);

        // Update request
        $pdo->prepare("UPDATE material_requests SET status = 'Accepted', handled_by = ?, po_id = ?, resolved_at = CURRENT_TIMESTAMP WHERE request_id = ?")
            ->execute([$userId, $poId, $reqId]);

        addNotification($pdo, "✅ Request Accepted", "Material request #{$reqId} accepted. PO {$poId} created for {$req['item_name']}.", 'procurement');

        $pdo->commit();
        echo json_encode(['success' => true, 'po_id' => $poId]);

    } elseif ($action === 'decline') {
        $reqId = $_POST['request_id'] ?? '';
        $reason = $_POST['decline_reason'] ?? 'No reason given';
        if (empty($reqId)) throw new Exception('Request ID required.');

        $pdo->prepare("UPDATE material_requests SET status = 'Declined', handled_by = ?, decline_reason = ?, resolved_at = CURRENT_TIMESTAMP WHERE request_id = ?")
            ->execute([$userId, $reason, $reqId]);

        addNotification($pdo, "❌ Request Declined", "Material request #{$reqId} declined: {$reason}", 'manufacturing');

        $pdo->commit();
        echo json_encode(['success' => true]);

    } elseif ($action === 'accept_bulk') {
        // Accept multiple requests at once, group by best supplier
        $requestIds = $_POST['request_ids'] ?? [];
        if (empty($requestIds)) throw new Exception('No requests selected.');
        if (is_string($requestIds)) $requestIds = explode(',', $requestIds);

        // Get all requests
        $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
        $reqStmt = $pdo->prepare("SELECT mr.*, im.item_name, im.standard_cost FROM material_requests mr JOIN item_master im ON mr.item_id = im.item_id WHERE mr.request_id IN ({$placeholders}) AND mr.status = 'Requested'");
        $reqStmt->execute($requestIds);
        $requests = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($requests)) throw new Exception('No pending requests found.');

        // Group materials by preferred supplier
        $supplierGroups = []; // supplier_id => [{item_id, qty, price}]
        foreach ($requests as $req) {
            // Find preferred supplier for this item
            $prefStmt = $pdo->prepare("SELECT si.supplier_id, s.supplier_name, s.preferred_currency, si.unit_price FROM supplier_items si JOIN suppliers s ON si.supplier_id = s.supplier_id WHERE si.item_id = ? ORDER BY si.is_preferred DESC, si.unit_price ASC LIMIT 1");
            $prefStmt->execute([$req['item_id']]);
            $prefSupplier = $prefStmt->fetch(PDO::FETCH_ASSOC);

            if ($prefSupplier) {
                $suppId = $prefSupplier['supplier_id'];
                $unitPrice = $prefSupplier['unit_price'] ?: $req['unit_price'] ?: $req['standard_cost'];
            } else {
                // Fallback: use first supplier
                $suppId = 1;
                $unitPrice = $req['unit_price'] ?: $req['standard_cost'];
            }

            if (!isset($supplierGroups[$suppId])) {
                $supplierGroups[$suppId] = ['supplier' => $prefSupplier, 'items' => []];
            }
            $supplierGroups[$suppId]['items'][] = [
                'request_id' => $req['request_id'],
                'item_id'    => $req['item_id'],
                'item_name'  => $req['item_name'],
                'quantity'   => $req['quantity_requested'],
                'unit_price' => $unitPrice,
            ];
        }

        // Create one PO per supplier group
        $createdPOs = [];
        foreach ($supplierGroups as $suppId => $group) {
            $poId = 'PO-' . date('ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            $currency = $group['supplier']['preferred_currency'] ?? 'JOD';

            $totalAmount = 0;
            foreach ($group['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            // Insert PO header
            $pdo->prepare("INSERT INTO purchase_orders (po_id, supplier_id, order_date, total_amount, currency, delivery_location, order_status, payment_status, created_by) VALUES (?, ?, CURRENT_DATE(), ?, ?, 'Main Warehouse', 'Pending', 'Pending', ?)")
                ->execute([$poId, $suppId, $totalAmount, $currency, $userId]);

            // Insert PO lines
            $lineStmt = $pdo->prepare("INSERT INTO po_lines (po_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            foreach ($group['items'] as $item) {
                $lineStmt->execute([$poId, $item['item_id'], $item['quantity'], $item['unit_price']]);

                // Update the material request
                $pdo->prepare("UPDATE material_requests SET status = 'Accepted', handled_by = ?, po_id = ?, resolved_at = CURRENT_TIMESTAMP WHERE request_id = ?")
                    ->execute([$userId, $poId, $item['request_id']]);
            }

            $supplierName = $group['supplier']['supplier_name'] ?? "Supplier #{$suppId}";
            $createdPOs[] = $poId;
            addNotification($pdo, "📋 Bulk PO Created", "PO {$poId} for {$supplierName} — " . count($group['items']) . " materials, {$currency} " . number_format($totalAmount, 2), 'procurement');
        }

        addNotification($pdo, "✅ Bulk Requests Accepted", count($requests) . " material requests accepted. " . count($createdPOs) . " POs created.", 'manufacturing');

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'po_ids' => $createdPOs,
            'message' => count($requests) . " requests accepted. " . count($createdPOs) . " PO(s) created.",
        ]);

    } else {
        throw new Exception('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Handle Request Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
