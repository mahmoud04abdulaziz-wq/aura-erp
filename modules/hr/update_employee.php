<?php
/**
 * AURA ERP — Update Employee (HR)
 * Updates employee record. If employee has an account, syncs email & role to users table.
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $empId = $_POST['employee_id'] ?? null;
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $departmentId = $_POST['department_id'] ?? null;
    $roleId = $_POST['role_id'] ?? null;

    if (!$empId || !$firstName || !$lastName || !$email || !$departmentId || !$roleId) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    try {
        // Update the employee record (including role_id on the employees table)
        $stmt = $pdo->prepare("
            UPDATE employees 
            SET first_name = ?, last_name = ?, email = ?, department_id = ?, role_id = ?
            WHERE employee_id = ?
        ");
        $stmt->execute([$firstName, $lastName, $email, $departmentId, $roleId, $empId]);

        // If this employee has a user account, keep it in sync
        $userStmt = $pdo->prepare("
            UPDATE users 
            SET email = ?, role_id = ?
            WHERE employee_id = ?
        ");
        $userStmt->execute([$email, $roleId, $empId]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Update Employee Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error while updating employee']);
    }
}
