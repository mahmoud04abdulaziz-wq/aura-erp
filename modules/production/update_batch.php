<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prodId = $_POST['production_id'] ?? '';
    $status = $_POST['status'] ?? null;
    $qaStatus = $_POST['qa_status'] ?? null;
    $actualYield = $_POST['actual_yield'] ?? null;

    if (empty($prodId)) {
        echo json_encode(['success' => false, 'error' => 'Production ID is required']);
        exit;
    }

    try {
        // Build dynamic update query based on provided fields
        $updates = [];
        $params = [];

        if ($status !== null) {
            $updates[] = "status = ?";
            $params[] = $status;
        }
        if ($qaStatus !== null) {
            $updates[] = "qa_status = ?";
            $params[] = $qaStatus;
        }
        if ($actualYield !== null && $actualYield >= 0) {
            $updates[] = "actual_yield = ?";
            $params[] = $actualYield;
        }

        if (empty($updates)) {
            echo json_encode(['success' => true, 'message' => 'No changes requested']);
            exit;
        }

        $params[] = $prodId;
        
        $sql = "UPDATE production_orders SET " . implode(', ', $updates) . " WHERE production_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Log action
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'UPDATE_BATCH', ?, 'Success')");
        $logStmt->execute([$_SESSION['user_id'] ?? 1, "Updated Production Batch $prodId status"]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Update Batch Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
}
