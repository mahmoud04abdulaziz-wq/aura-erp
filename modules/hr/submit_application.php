<?php
/**
 * MiskStone — Submit Job Application (Public Endpoint)
 * Handles CV upload and stores the application in the database.
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in to apply.']);
    exit;
}

$postingId   = intval($_POST['posting_id'] ?? 0);
$fullName    = trim($_POST['full_name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$coverLetter = trim($_POST['cover_letter'] ?? '');

// Validate required fields
if (!$postingId || empty($fullName) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Please fill out all required fields.']);
    exit;
}

// Validate posting exists and is open
$stmt = $pdo->prepare("SELECT posting_id, title FROM job_postings WHERE posting_id = ? AND status = 'Open'");
$stmt->execute([$postingId]);
$posting = $stmt->fetch();
if (!$posting) {
    echo json_encode(['success' => false, 'error' => 'This position is no longer accepting applications.']);
    exit;
}

// Check for duplicate application
$dupeStmt = $pdo->prepare("SELECT application_id FROM job_applications WHERE posting_id = ? AND email = ?");
$dupeStmt->execute([$postingId, $email]);
if ($dupeStmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'You have already applied for this position.']);
    exit;
}

// Handle CV Upload
$cvPath = null;
if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['cv_file'];
    
    // Validate file type (PDF only)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if ($mimeType !== 'application/pdf') {
        echo json_encode(['success' => false, 'error' => 'Only PDF files are accepted for CV uploads.']);
        exit;
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'CV file must be smaller than 5MB.']);
        exit;
    }

    // Generate safe filename
    $uploadDir = dirname(__DIR__, 2) . '/assets/uploads/cvs/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fullName);
    $filename = 'CV_' . $safeName . '_' . date('Ymd_His') . '.pdf';
    $destPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        $cvPath = 'assets/uploads/cvs/' . $filename;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload CV file. Please try again.']);
        exit;
    }
}

// Insert application
try {
    $insertStmt = $pdo->prepare("
        INSERT INTO job_applications (posting_id, full_name, email, phone, cv_path, cover_letter, status)
        VALUES (?, ?, ?, ?, ?, ?, 'Applied')
    ");
    $insertStmt->execute([$postingId, $fullName, $email, $phone, $cvPath, $coverLetter]);

    // Notify HR
    require_once dirname(__DIR__, 2) . '/includes/notifications.php';
    addNotification($pdo, "📄 New Job Application", "{$fullName} applied for '{$posting['title']}'", 'hr');

    echo json_encode([
        'success' => true,
        'message' => 'Your application has been submitted successfully! We will review it and contact you soon.'
    ]);
} catch (PDOException $e) {
    error_log("Job application error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
