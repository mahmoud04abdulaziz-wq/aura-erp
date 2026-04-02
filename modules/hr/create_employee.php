<?php
/**
 * AURA ERP — Create Employee (HR)
 * Adds an employee record. Does NOT create a system account.
 * Account provisioning is handled separately by IT/Admin.
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $departmentId = $_POST['department_id'] ?? null;
    $hireDate = $_POST['hire_date'] ?? null;
    $roleId = $_POST['role_id'] ?? null;
    $status = $_POST['verification_status'] ?? 'Pending';

    // Validate status
    if (!in_array($status, ['Pending', 'Approved', 'Rejected'])) {
        $status = 'Pending';
    }

    if (!$firstName || !$lastName || !$email || !$departmentId || !$hireDate || !$roleId) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO employees (first_name, last_name, email, department_id, hire_date, role_id, verification_status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$firstName, $lastName, $email, $departmentId, $hireDate, $roleId, $status]);
        $newId = $pdo->lastInsertId();

        echo json_encode(['success' => true, 'employee_id' => $newId]);
    } catch (PDOException $e) {
        error_log("Create Employee Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error while creating employee']);
    }
}
