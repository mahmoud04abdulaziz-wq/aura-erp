<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$userId || !$oldPassword || !$newPassword || !$confirmPassword) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'error' => 'New passwords do not match.']);
        exit;
    }

    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters.']);
        exit;
    }

    try {
        // Fetch current password hash
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            echo json_encode(['success' => false, 'error' => 'Incorrect old password.']);
            exit;
        }

        // Update password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $updateStmt->execute([$newHash, $userId]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Change Password Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error while changing password.']);
    }
}
