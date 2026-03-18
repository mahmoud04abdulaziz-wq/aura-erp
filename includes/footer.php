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

<!-- App JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>

</html>