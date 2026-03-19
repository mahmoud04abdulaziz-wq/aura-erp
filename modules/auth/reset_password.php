<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if current user is an Admin (1) or HR Manager (5).
    $currentRoleId = $_SESSION['role_id'] ?? 0;
    if ($currentRoleId != 1 && $currentRoleId != 5) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized action. HR or Admin privileges required.']);
        exit;
    }

    $targetEmployeeId = $_POST['employee_id'] ?? null;

    if (!$targetEmployeeId) {
        echo json_encode(['success' => false, 'error' => 'Target employee ID is required.']);
        exit;
    }

    try {
        // Find if this employee has a user account
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE employee_id = ?");
        $stmt->execute([$targetEmployeeId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            echo json_encode(['success' => false, 'error' => 'This employee does not have a linked user account to reset.']);
            exit;
        }

        // Reset password to default 'Welcome123!'
        $defaultHash = password_hash('Welcome123!', PASSWORD_BCRYPT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE employee_id = ?");
        $updateStmt->execute([$defaultHash, $targetEmployeeId]);

        echo json_encode(['success' => true, 'message' => 'Password reset to Welcome123!']);
    } catch (PDOException $e) {
        error_log("Reset Password Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error while resetting password.']);
    }
}
