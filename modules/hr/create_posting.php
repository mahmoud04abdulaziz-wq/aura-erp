<?php
/**
 * MiskStone ERP — Create / Update Job Posting
 * POST handler for HR managers to manage job listings.
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

$postingId      = intval($_POST['posting_id'] ?? 0);
$title          = trim($_POST['title'] ?? '');
$departmentId   = intval($_POST['department_id'] ?? 0);
$description    = trim($_POST['description'] ?? '');
$requirements   = trim($_POST['requirements'] ?? '');
$employmentType = trim($_POST['employment_type'] ?? 'Full-Time');
$status         = trim($_POST['status'] ?? 'Open');

if ($postingId <= 0 && (empty($title) || !$departmentId || empty($description))) {
    echo json_encode(['success' => false, 'error' => 'Title, department, and description are required for new postings.']);
    exit;
}

try {
    if ($postingId > 0) {
        // Dynamic Update for existing posting
        $updates = [];
        $params  = [];
        
        $fields = [
            'title'           => $title,
            'department_id'   => $departmentId,
            'description'     => $description,
            'requirements'    => $requirements,
            'employment_type' => $employmentType,
            'status'          => $status
        ];

        foreach ($fields as $col => $val) {
            // Only update if field is provided (not empty for required ones, or specifically set)
            // For status, we always want to allow it.
            if ($col === 'status' || !empty($val)) {
                $updates[] = "$col = ?";
                $params[]  = $val;
            }
        }

        if (empty($updates)) {
            echo json_encode(['success' => true, 'message' => 'No changes made.']);
            exit;
        }

        $params[] = $postingId;
        $stmt = $pdo->prepare("UPDATE job_postings SET " . implode(', ', $updates) . " WHERE posting_id = ?");
        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'Job posting updated.']);
    } else {
        // Create new posting
        $stmt = $pdo->prepare("
            INSERT INTO job_postings (title, department_id, description, requirements, employment_type, status, posted_date)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE())
        ");
        $stmt->execute([$title, $departmentId, $description, $requirements, $employmentType, $status]);
        echo json_encode(['success' => true, 'message' => 'Job posting created.', 'posting_id' => $pdo->lastInsertId()]);
    }
} catch (PDOException $e) {
    error_log("Create posting error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
}
