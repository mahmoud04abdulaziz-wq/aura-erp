<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prodId = $_POST['production_id'] ?? '';

    if (empty($prodId)) {
        echo json_encode(['success' => false, 'error' => 'Production ID is required']);
        exit;
    }

    try {
        // Only allow deleting Planned or Failed batches, completed batches shouldn't be deleted easily usually, but for prototyping we'll just delete.
        $stmt = $pdo->prepare("DELETE FROM production_orders WHERE production_id = ?");
        $stmt->execute([$prodId]);
        
        // Log action
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'DELETE_BATCH', ?, 'Success')");
        $logStmt->execute([$_SESSION['user_id'] ?? 1, "Deleted Production Batch $prodId"]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Delete Batch Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
}
