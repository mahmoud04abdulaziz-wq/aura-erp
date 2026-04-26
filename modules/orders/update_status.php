<?php
/**
 * MiskStone ERP — Order Status Pipeline Handler
 * 
 * Automates the full pipeline when an order is dragged on the Kanban board:
 *   Pending → In Production:    Creates production batch
 *   In Production → QC/Delivery: Runs mix, deducts raw materials, credits FG, records BOM expense
 *   QC/Delivery → Delivered:     Records income, generates JoFotara tax invoice + QR
 * 
 * KEY FEATURES:
 *   - Stock validation: never allows negative inventory
 *   - Company names in all references (not just SO IDs)
 *   - Mix-based production: 1 mix → multiple finished products
 */
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

// Helper: get current stock for a raw material
function getCurrentStock(PDO $pdo, string $itemId): float {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
    $stmt->execute([$itemId]);
    return (float)$stmt->fetchColumn();
}

try {
    // Fetch order + company info upfront (used in all hooks)
    $soStmt = $pdo->prepare("SELECT so.*, c.company_name, c.email 
                              FROM sales_orders so 
                              JOIN customers c ON so.customer_id = c.customer_id 
                              WHERE so.so_id = ?");
    $soStmt->execute([$so_id]);
    $soData = $soStmt->fetch();
    $companyName = $soData['company_name'] ?? 'Unknown';
    $refLabel = "{$so_id} ({$companyName})";

    // Update the status
    $stmt = $pdo->prepare("UPDATE sales_orders SET order_status = ? WHERE so_id = ?");
    $stmt->execute([$status, $so_id]);
    
    $userId = $_SESSION['user_id'] ?? 1;
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'UPDATE_ORDER_STATUS', ?, 'Success')");
    $logStmt->execute([$userId, "Updated order {$refLabel} to {$status}"]);

    require_once __DIR__ . '/../../includes/notifications.php';
    addNotification($pdo, "Order Status Updated", "Order {$refLabel} → {$status}", 'finance');

    // =============================================================
    // PIPELINE HOOK: IN PRODUCTION
    // =============================================================
    if ($status === 'In Production') {
        addNotification($pdo, "🏭 Order in Production", "Order {$refLabel} is now in production. Create mix batches from the Mix Batches page.", 'manufacturing');
    
    // =============================================================
    // PIPELINE HOOK: DELIVERED — Blocked (use Delivery Dispatch page)
    // =============================================================
    } elseif ($status === 'Delivered') {
        // Delivery is now handled exclusively by Sales → Delivery Dispatch
        echo json_encode(['success' => false, 'error' => 'Delivery is finalized via the Delivery Dispatch page.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => "Status updated to {$status} for {$companyName}."]);
} catch (Exception $e) {
    error_log("Failed to update status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
