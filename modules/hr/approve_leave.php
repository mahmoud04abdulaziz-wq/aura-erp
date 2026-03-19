<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ticketId = $_POST['ticket_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'approve' or 'reject'
    $roleId = $_SESSION['role_id'] ?? 0;

    if (!$ticketId || !in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }

    $newStatus = ($action === 'approve') ? 'Approved' : 'Rejected';

    try {
        if ($roleId == 2) {
            // Stage 1: Production Manager
            $stmt = $pdo->prepare("UPDATE leave_tickets SET manager_status = ? WHERE ticket_id = ?");
            $stmt->execute([$newStatus, $ticketId]);
            echo json_encode(['success' => true, 'stage' => 1]);
        } elseif ($roleId == 5 || $roleId == 1) {
            // Stage 2: HR Manager or Admin
            $stmt = $pdo->prepare("UPDATE leave_tickets SET hr_status = ? WHERE ticket_id = ?");
            $stmt->execute([$newStatus, $ticketId]);
            echo json_encode(['success' => true, 'stage' => 2]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User does not have permission to approve tickets']);
        }
    } catch (PDOException $e) {
        error_log("Approve Leave Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}
