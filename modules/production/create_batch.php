<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = $_POST['item_id'] ?? '';
    $machineId = $_POST['machine_id'] ?? null;
    $targetQty = $_POST['target_quantity'] ?? 0;
    $operatorId = $_POST['operator_user_id'] ?? $_SESSION['user_id'];
    $prodDate = $_POST['production_date'] ?? date('Y-m-d');

    if (empty($itemId) || $targetQty <= 0) {
        echo json_encode(['success' => false, 'error' => 'Valid Item and Target Quantity are required']);
        exit;
    }

    try {
        // Generate a unique batch ID: BATCH-YYMMDD-XXXX
        $dateSuffix = date('ymd');
        $randomSuffix = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $productionId = "BATCH-$dateSuffix-$randomSuffix";

        $stmt = $pdo->prepare("
            INSERT INTO production_orders 
            (production_id, item_id, machine_id, production_date, target_quantity, operator_user_id, status, qa_status)
            VALUES (?, ?, ?, ?, ?, ?, 'Planned', 'Pending')
        ");
        
        $stmt->execute([$productionId, $itemId, $machineId, $prodDate, $targetQty, $operatorId]);
        
        // Log action
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'CREATE_BATCH', ?, 'Success')");
        $logStmt->execute([$_SESSION['user_id'] ?? 1, "Created Production Batch $productionId for Item $itemId"]);

        echo json_encode(['success' => true, 'production_id' => $productionId]);
    } catch (PDOException $e) {
        error_log("Create Batch Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
}
