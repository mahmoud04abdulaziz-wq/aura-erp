<?php
/**
 * MiskStone ERP — Update Application Status
 * POST handler for HR managers to move applicants through the pipeline.
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db_connect.php';
require_once dirname(__DIR__) . '/auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$applicationId = intval($_POST['application_id'] ?? 0);
$newStatus     = trim($_POST['status'] ?? '');

$validStatuses = ['Applied', 'Reviewed', 'Interview', 'Hired', 'Rejected'];

if (!$applicationId || !in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid application or status.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE job_applications SET status = ? WHERE application_id = ?");
    $stmt->execute([$newStatus, $applicationId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Application not found.']);
        exit;
    }

    // If hired, log a notification
    if ($newStatus === 'Hired') {
        $app = $pdo->prepare("SELECT ja.full_name, jp.title FROM job_applications ja JOIN job_postings jp ON ja.posting_id = jp.posting_id WHERE ja.application_id = ?");
        $app->execute([$applicationId]);
        $info = $app->fetch();

        if ($info) {
            require_once dirname(__DIR__, 2) . '/includes/notifications.php';
            addNotification($pdo, "🎉 New Hire", "{$info['full_name']} has been hired for '{$info['title']}'", 'hr');
        }
    }

    echo json_encode(['success' => true, 'message' => "Status updated to {$newStatus}."]);
} catch (PDOException $e) {
    error_log("Update application error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
