<?php
/**
 * AURA ERP — Toggle Account Status (IT/Admin)
 * Suspend or reactivate a user account.
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $userId = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'suspend' or 'activate'

    if (!$userId || !in_array($action, ['suspend', 'activate'])) {
        echo json_encode(['success' => false, 'error' => 'Missing or invalid parameters']);
        exit;
    }

    // Prevent self-suspension
    if ($userId == $_SESSION['user_id'] && $action === 'suspend') {
        echo json_encode(['success' => false, 'error' => 'You cannot suspend your own account']);
        exit;
    }

    try {
        $newStatus = $action === 'suspend' ? 'Suspended' : 'Active';
        $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE user_id = ?");
        $stmt->execute([$newStatus, $userId]);

        // Log the action
        $logStmt = $pdo->prepare(
            "INSERT INTO system_logs (user_id, action_type, description, status) 
             VALUES (?, 'ACCOUNT_STATUS_CHANGE', ?, 'Success')"
        );
        $logStmt->execute([
            $_SESSION['user_id'],
            "Changed account status to {$newStatus} for user ID: {$userId}"
        ]);

        echo json_encode(['success' => true, 'message' => "Account {$newStatus} successfully"]);
    } catch (PDOException $e) {
        error_log("Toggle Account Status Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error while updating account status']);
    }
}
