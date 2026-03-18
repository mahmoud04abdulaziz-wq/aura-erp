<?php
/**
 * AURA ERP — Settings View
 * Displays user profile settings and system info.
 */

$userName = getCurrentUserName();
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['role_name'] ?? 'Unknown';
?>

<div class="card" style="max-width:700px;">
    <div class="card-header">
        <h3>Profile Settings</h3>
    </div>

    <div style="padding:1rem 0;">
        <!-- Profile Info -->
        <div
            style="display:flex; align-items:center; gap:1.5rem; margin-bottom:2rem; padding-bottom:2rem; border-bottom:1px solid var(--border-color);">
            <div class="profile-avatar-large" style="width:80px; height:80px; font-size:2rem; margin:0;">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
            <div>
                <h2 style="margin:0 0 0.25rem;">
                    <?= htmlspecialchars($userName) ?>
                </h2>
                <p style="color:var(--text-secondary); margin:0;">
                    <?= htmlspecialchars($userEmail) ?>
                </p>
                <span class="badge in-progress" style="margin-top:0.5rem; display:inline-block;">
                    <?= htmlspecialchars($userRole) ?>
                </span>
            </div>
        </div>

        <!-- Settings Form -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <div>
                <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:var(--text-primary);">Full
                    Name</label>
                <input type="text" value="<?= htmlspecialchars($userName) ?>" disabled
                    style="width:100%; padding:0.75rem 1rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); font-size:0.95rem;">
            </div>
            <div>
                <label
                    style="display:block; font-weight:600; margin-bottom:0.5rem; color:var(--text-primary);">Email</label>
                <input type="email" value="<?= htmlspecialchars($userEmail) ?>" disabled
                    style="width:100%; padding:0.75rem 1rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); font-size:0.95rem;">
            </div>
            <div>
                <label
                    style="display:block; font-weight:600; margin-bottom:0.5rem; color:var(--text-primary);">Role</label>
                <input type="text" value="<?= htmlspecialchars($userRole) ?>" disabled
                    style="width:100%; padding:0.75rem 1rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); font-size:0.95rem;">
            </div>
        </div>

        <!-- Quick Links -->
        <div style="margin-top:2rem; display:flex; gap:1rem;">
            <a href="<?= BASE_URL ?>/modules/auth/logout.php"
                style="padding:0.75rem 1.5rem; background:var(--danger); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>
</div>