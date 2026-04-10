<?php
/**
 * MiskStone ERP — Settings View
 * User profile + Bug Report / Maintenance Request form.
 */

$userName = getCurrentUserName();
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['role_name'] ?? 'Unknown';

// Handle bug report submission
$bugSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bug_action'])) {
    $bugTitle = trim($_POST['bug_title'] ?? '');
    $bugDesc = trim($_POST['bug_description'] ?? '');
    $bugPriority = $_POST['bug_priority'] ?? 'Medium';
    
    if (!empty($bugTitle) && !empty($bugDesc)) {
        try {
            // Create bug_reports table if needed
            $pdo->exec("CREATE TABLE IF NOT EXISTS bug_reports (
                report_id INT AUTO_INCREMENT PRIMARY KEY,
                reported_by INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                priority ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
                status ENUM('Open','In Progress','Resolved','Closed') DEFAULT 'Open',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reported_by) REFERENCES users(user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $stmt = $pdo->prepare("INSERT INTO bug_reports (reported_by, title, description, priority) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $bugTitle, $bugDesc, $bugPriority]);
            
            // Notify IT Admin
            require_once __DIR__ . '/../includes/notifications.php';
            addNotification($pdo, "Bug Report", "New ticket: {$bugTitle} (Priority: {$bugPriority})", 'admin');
            
            $bugSuccess = 'Your bug report has been submitted to the IT department.';
        } catch (Exception $e) {
            error_log("Bug report error: " . $e->getMessage());
        }
    }
}
?>

<div style="max-width:800px;">

<!-- Profile Card -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3>Profile Settings</h3>
    </div>

    <div style="padding:1rem 0;">
        <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:2rem; padding-bottom:2rem; border-bottom:1px solid var(--border-color);">
            <div class="profile-avatar-large" style="width:80px; height:80px; font-size:2rem; margin:0;">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
            <div>
                <h2 style="margin:0 0 0.25rem;"><?= htmlspecialchars($userName) ?></h2>
                <p style="color:var(--text-secondary); margin:0;"><?= htmlspecialchars($userEmail) ?></p>
                <span class="badge in-progress" style="margin-top:0.5rem; display:inline-block;"><?= htmlspecialchars($userRole) ?></span>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <div>
                <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:var(--text-primary);">Full Name</label>
                <input type="text" value="<?= htmlspecialchars($userName) ?>" disabled
                    style="width:100%; padding:0.75rem 1rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); font-size:0.95rem;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:var(--text-primary);">Email</label>
                <input type="email" value="<?= htmlspecialchars($userEmail) ?>" disabled
                    style="width:100%; padding:0.75rem 1rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); font-size:0.95rem;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:var(--text-primary);">Role</label>
                <input type="text" value="<?= htmlspecialchars($userRole) ?>" disabled
                    style="width:100%; padding:0.75rem 1rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); font-size:0.95rem;">
            </div>
        </div>

        <div style="margin-top:2rem; display:flex; gap:1rem;">
            <a href="<?= BASE_URL ?>/modules/auth/logout.php"
                style="padding:0.75rem 1.5rem; background:var(--danger); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>
</div>

<!-- Bug Report / Maintenance Request -->
<div class="card">
    <div class="card-header">
        <h3><i class="fa-solid fa-bug" style="color:#ef4444; margin-right:0.5rem;"></i>Report a Bug / Maintenance Request</h3>
    </div>
    
    <?php if ($bugSuccess): ?>
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#166534; padding:1rem; border-radius:8px; margin-bottom:1rem;">
            <i class="fa-solid fa-check-circle" style="margin-right:0.5rem;"></i><?= htmlspecialchars($bugSuccess) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="bug_action" value="submit">
        
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-weight:600; margin-bottom:0.5rem;">Issue Title *</label>
            <input type="text" name="bug_title" required placeholder="e.g. Dashboard not loading inventory data"
                style="width:100%; padding:0.75rem 1rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary); font-size:0.95rem;">
        </div>
        
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-weight:600; margin-bottom:0.5rem;">Priority</label>
            <select name="bug_priority" style="width:100%; padding:0.75rem 1rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary); font-size:0.95rem;">
                <option value="Low">Low — Cosmetic / Minor</option>
                <option value="Medium" selected>Medium — Feature not working correctly</option>
                <option value="High">High — Blocking core workflow</option>
                <option value="Critical">Critical — System down / Data loss</option>
            </select>
        </div>

        <div style="margin-bottom:1.5rem;">
            <label style="display:block; font-weight:600; margin-bottom:0.5rem;">Description *</label>
            <textarea name="bug_description" rows="4" required 
                placeholder="Describe the issue in detail: what you were doing, what you expected, and what happened instead."
                style="width:100%; padding:0.75rem 1rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary); font-size:0.95rem; resize:vertical;"></textarea>
        </div>

        <button type="submit" style="padding:0.75rem 1.5rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:1rem;">
            <i class="fa-solid fa-paper-plane" style="margin-right:0.5rem;"></i>Submit Report
        </button>
    </form>
</div>

</div>