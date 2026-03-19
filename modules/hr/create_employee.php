<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic permission check (assuming role_id 5 is HR or role_id 1 is Admin, OR simply relying on module-level auth which we can simulate)

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $departmentId = $_POST['department_id'] ?? null;
    $hireDate = $_POST['hire_date'] ?? null;
    $roleId = $_POST['role_id'] ?? null;

    if (!$firstName || !$lastName || !$email || !$departmentId || !$hireDate || !$roleId) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO employees (first_name, last_name, email, department_id, hire_date, verification_status)
            VALUES (?, ?, ?, ?, ?, 'Approved')
        ");
        
        $stmt->execute([$firstName, $lastName, $email, $departmentId, $hireDate]);
        $newId = $pdo->lastInsertId();

        // Automatically create user account
        $hash = password_hash('Welcome123!', PASSWORD_BCRYPT);
        $userStmt = $pdo->prepare("
            INSERT INTO users (email, password_hash, role_id, employee_id)
            VALUES (?, ?, ?, ?)
        ");
        $userStmt->execute([$email, $hash, $roleId, $newId]);

        echo json_encode(['success' => true, 'employee_id' => $newId]);
    } catch (PDOException $e) {
        error_log("Create Employee Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error while creating employee']);
    }
}
