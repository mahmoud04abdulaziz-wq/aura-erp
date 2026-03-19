<?php
/**
 * AURA ERP — Footer Include
 */
?>
<!-- Profile Sidebar/Modal -->
<div class="profile-panel" id="profile-panel">
    <div class="profile-header">
        <h3>User Profile</h3>
        <button id="profile-close"><i class="fa-solid fa-times"></i></button>
    </div>
    <div class="profile-body">
        <div class="profile-avatar-large">
            <?= getUserInitials() ?>
        </div>
        <h4>
            <?= htmlspecialchars(getCurrentUserName()) ?>
        </h4>
        <p>
            <?= htmlspecialchars($_SESSION['role_name'] ?? '') ?>
        </p>
        <hr>
        <ul class="profile-menu">
            <li><a href="#" onclick="openChangePasswordModal(event)">Change Password</a></li>
            <li><a href="<?= BASE_URL ?>/index.php?view=settings">Account Settings</a></li>
            <li><a href="<?= BASE_URL ?>/modules/auth/logout.php">Logout</a></li>
        </ul>
    </div>
</div>
</div>

<!-- Generic Modal -->
<div id="generic-modal" class="modal-overlay">
    <div class="modal-content" id="modal-content-body">
        <!-- Injected Content -->
    </div>
</div>

<script>
function openChangePasswordModal(e) {
    if (e) e.preventDefault();
    if (typeof toggleProfile === 'function') toggleProfile();
    
    const modal = document.getElementById('generic-modal');
    if (!modal) return;
    
    let contentContainer = document.getElementById('modal-content-body');
    if (!contentContainer) {
        contentContainer = modal;
    }
    
    const content = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="margin:0;">Change Password</h2>
            <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
        </div>
        <form onsubmit="submitChangePassword(event)">
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:0.5rem;">Current Password *</label>
                <input type="password" name="old_password" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:0.5rem;">New Password *</label>
                <input type="password" name="new_password" required minlength="8" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
            </div>
            <div style="margin-bottom:1.5rem;">
                <label style="display:block; margin-bottom:0.5rem;">Confirm New Password *</label>
                <input type="password" name="confirm_password" required minlength="8" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
            </div>
            <button type="submit" style="padding:0.75rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:500;">
                Update Password
            </button>
        </form>
    `;
    
    // In this app, generic modal content goes inside #modal-content-body if it exists, otherwise we overwrite the whole modal.
    // We'll replace the inner HTML of the modal content directly to match hr.php style
    modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:400px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
    modal.classList.add('active');
}

async function submitChangePassword(e) {
    e.preventDefault();
    try {
        const fd = new FormData(e.target);
        const res = await fetch('<?= BASE_URL ?>/modules/auth/change_password.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert('Password changed successfully!');
            document.getElementById('generic-modal').classList.remove('active');
        } else {
            alert('Error: ' + data.error);
        }
    } catch (err) {
        alert('Network error while changing password.');
    }
}
</script>

<!-- App JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>

</html>