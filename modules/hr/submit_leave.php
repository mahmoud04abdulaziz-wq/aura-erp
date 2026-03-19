<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $empId = $_POST['employee_id'] ?? null;
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $reason = trim($_POST['reason'] ?? '');

    if (!$empId || !$startDate || !$endDate || !$reason) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO leave_tickets (employee_id, start_date, end_date, reason)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$empId, $startDate, $endDate, $reason]);

        echo json_encode(['success' => true, 'ticket_id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        error_log("Submit Leave Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error while submitting leave ticket']);
    }
}
