<?php
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
        $stmt = $pdo->prepare("DELETE FROM employees WHERE employee_id = ?");
        $stmt->execute([$empId]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Delete Employee Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error while deleting employee. Ensure they are not linked to critical records.']);
    }
}
