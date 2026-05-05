<?php
/**
 * Phase 3 Migration — Careers & Hiring Pipeline
 * Creates job_postings and job_applications tables.
 * Seeds sample job postings for demo.
 */
require_once __DIR__ . '/config/db_connect.php';

echo "=== Phase 3: Careers & Hiring Pipeline Migration ===\n\n";

// ── 1. Create job_postings table ──
echo "[1] Creating job_postings table... ";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS job_postings (
        posting_id      INT AUTO_INCREMENT PRIMARY KEY,
        title           VARCHAR(150) NOT NULL,
        department_id   INT NOT NULL,
        description     TEXT NOT NULL,
        requirements    TEXT,
        employment_type ENUM('Full-Time','Part-Time','Contract','Internship') DEFAULT 'Full-Time',
        status          ENUM('Open','Closed') DEFAULT 'Open',
        posted_date     DATE DEFAULT (CURRENT_DATE),
        closing_date    DATE DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (department_id) REFERENCES departments(department_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
echo "OK\n";

// ── 2. Create job_applications table ──
echo "[2] Creating job_applications table... ";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS job_applications (
        application_id  INT AUTO_INCREMENT PRIMARY KEY,
        posting_id      INT NOT NULL,
        full_name       VARCHAR(150) NOT NULL,
        email           VARCHAR(150) NOT NULL,
        phone           VARCHAR(30) DEFAULT NULL,
        cv_path         VARCHAR(255) DEFAULT NULL,
        cover_letter    TEXT DEFAULT NULL,
        status          ENUM('Applied','Reviewed','Interview','Hired','Rejected') DEFAULT 'Applied',
        applied_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (posting_id) REFERENCES job_postings(posting_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
echo "OK\n";

// ── 3. Create uploads directory ──
echo "[3] Creating CV uploads directory... ";
$uploadDir = __DIR__ . '/assets/uploads/cvs';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "Created\n";
} else {
    echo "Already exists\n";
}

// ── 4. Seed sample job postings ──
echo "[4] Seeding sample job postings...\n";

$departments = $pdo->query("SELECT department_id, department_name FROM departments")->fetchAll(PDO::FETCH_KEY_PAIR);

// Find department IDs by name (case-insensitive partial match)
function findDeptId(array $depts, string $keyword): int {
    foreach ($depts as $id => $name) {
        if (stripos($name, $keyword) !== false) return $id;
    }
    // Return first department as fallback
    return array_key_first($depts);
}

$postings = [
    [
        'title' => 'Production Line Operator',
        'dept_keyword' => 'production',
        'description' => 'Join our manufacturing team to operate vibro-compression machinery, monitor curing processes, and ensure quality output of artificial stone products. You will work with recipes and mix formulas under the guidance of senior technicians.',
        'requirements' => "• High school diploma or vocational certificate\n• 1+ year experience in manufacturing or construction\n• Ability to lift heavy materials (up to 25kg)\n• Willingness to work in shifts\n• Basic understanding of safety protocols",
        'type' => 'Full-Time'
    ],
    [
        'title' => 'Sales Representative',
        'dept_keyword' => 'sales',
        'description' => 'Drive revenue growth by managing client relationships across Jordan and the Middle East. You will handle incoming web leads, prepare quotations, coordinate with the production team on order fulfillment, and build long-term partnerships with contractors and interior design firms.',
        'requirements' => "• Bachelor's degree in Business, Marketing, or related field\n• 2+ years B2B sales experience (construction/building materials preferred)\n• Fluent in Arabic and English\n• Excellent negotiation and communication skills\n• Valid driver's license",
        'type' => 'Full-Time'
    ],
    [
        'title' => 'Quality Control Inspector',
        'dept_keyword' => 'production',
        'description' => 'Ensure every piece leaving the factory meets MiskStone quality standards. You will inspect finished products for dimensional accuracy, surface finish, color consistency, and structural integrity. You will also maintain QA records and flag defective batches.',
        'requirements' => "• Diploma in Engineering or Quality Management\n• Experience with measurement tools (calipers, levels)\n• Strong attention to detail\n• Ability to write inspection reports\n• Knowledge of ISO quality standards is a plus",
        'type' => 'Full-Time'
    ],
    [
        'title' => 'Accounting Intern',
        'dept_keyword' => 'finance',
        'description' => 'Gain hands-on experience in our Finance department. You will assist with invoice processing, ledger entries, bank reconciliation, and monthly financial reports. This is a great opportunity for fresh graduates to build real-world accounting skills in a manufacturing ERP environment.',
        'requirements' => "• Currently pursuing or recently completed a degree in Accounting/Finance\n• Familiarity with basic accounting principles\n• Proficiency in Microsoft Excel\n• Organized and detail-oriented\n• Eager to learn ERP systems",
        'type' => 'Internship'
    ],
];

$existingCount = $pdo->query("SELECT COUNT(*) FROM job_postings")->fetchColumn();
if ($existingCount == 0) {
    $insertStmt = $pdo->prepare("
        INSERT INTO job_postings (title, department_id, description, requirements, employment_type, status, posted_date)
        VALUES (?, ?, ?, ?, ?, 'Open', CURRENT_DATE())
    ");

    foreach ($postings as $p) {
        $deptId = findDeptId($departments, $p['dept_keyword']);
        $insertStmt->execute([
            $p['title'],
            $deptId,
            $p['description'],
            $p['requirements'],
            $p['type']
        ]);
        echo "   ✓ {$p['title']} (Dept: {$departments[$deptId]})\n";
    }
} else {
    echo "   Skipped — {$existingCount} postings already exist.\n";
}

echo "\n=== Phase 3 Migration Complete! ===\n";
