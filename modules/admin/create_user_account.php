<?php
/**
 * AURA ERP — Create User Account (IT/Admin)
 * Provisions a system account for an existing employee.
 * Uses the role_id already set by HR on the employees table.
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $empId = $_POST['employee_id'] ?? null;

    if (!$empId) {
        echo json_encode(['success' => false, 'error' => 'Missing employee ID']);
        exit;
    }

    try {
        // Check if employee already has an account
        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE employee_id = ?");
        $checkStmt->execute([$empId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'This employee already has a system account']);
            exit;
        }

        // Get employee details (email and role_id set by HR)
        $empStmt = $pdo->prepare("SELECT email, role_id FROM employees WHERE employee_id = ?");
        $empStmt->execute([$empId]);
        $emp = $empStmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            echo json_encode(['success' => false, 'error' => 'Employee not found']);
            exit;
        }

        if (!$emp['role_id']) {
            echo json_encode(['success' => false, 'error' => 'Employee has no role assigned. HR must assign a role first.']);
            exit;
        }

        // Create user account with default password
        $hash = password_hash('Welcome123!', PASSWORD_BCRYPT);
        $userStmt = $pdo->prepare("
            INSERT INTO users (email, password_hash, role_id, employee_id, account_status, mfa_enabled)
            VALUES (?, ?, ?, ?, 'Active', 1)
        ");
        $userStmt->execute([$emp['email'], $hash, $emp['role_id'], $empId]);

        // Log the action
        $logStmt = $pdo->prepare(
            "INSERT INTO system_logs (user_id, action_type, description, status) 
             VALUES (?, 'ACCOUNT_PROVISIONED', ?, 'Success')"
        );
        $logStmt->execute([
            $_SESSION['user_id'],
            "Provisioned system account for employee ID: {$empId}"
        ]);

        echo json_encode(['success' => true, 'message' => 'Account created successfully. Default password: Welcome123!']);
    } catch (PDOException $e) {
        error_log("Create User Account Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error while creating account']);
    }
}
