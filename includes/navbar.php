<?php
/**
 * AURA ERP — Top Navigation Bar
 */
?>
<header class="top-bar">
    <div class="left-section">
        <button id="sidebar-toggle" class="icon-btn"><i class="fa-solid fa-bars"></i></button>
        <h2 class="page-title">
            <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
        </h2>
    </div>

    <div class="right-section">
        <div class="role-badge"
            style="margin-right: 1.5rem; display: flex; align-items: center; gap: 0.5rem; background: rgba(99,102,241,0.1); padding: 0.35rem 0.75rem; border-radius: 6px;">
            <i class="fa-solid fa-id-badge" style="color: var(--accent-primary, #6366f1); font-size: 0.85rem;"></i>
            <span style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 600;">
                <?= htmlspecialchars($_SESSION['role_name'] ?? 'Guest') ?>
            </span>
        </div>

        <div class="user-profile-widget" onclick="toggleProfile()">
            <div class="avatar">
                <?= getUserInitials() ?>
            </div>
            <span class="user-name">
                <?= htmlspecialchars(getCurrentUserName()) ?>
            </span>
        </div>

        <div class="header-actions">
            <?php 
                require_once __DIR__ . '/notification_logic.php';
                $alerts = getSystemNotifications($pdo);
                $alertCount = count($alerts);
            ?>
            <div class="notification-wrapper" style="position:relative;">
                <button class="icon-btn" onclick="toggleNotifications(event)">
                    <i class="fa-regular fa-bell"></i>
                    <?php if ($alertCount > 0): ?>
                        <span class="notification-badge"><?= $alertCount ?></span>
                    <?php endif; ?>
                </button>
                <div id="notification-dropdown" class="notification-dropdown">
                    <div class="notif-header">
                        <h4>System Alerts</h4>
                        <span class="badge gray"><?= $alertCount ?> New</span>
                    </div>
                    <div class="notif-list">
                        <?php if ($alertCount === 0): ?>
                            <div class="notif-empty">All systems clear. No alerts.</div>
                        <?php else: ?>
                            <?php foreach ($alerts as $a): ?>
                                <div class="notif-item">
                                    <div class="notif-icon" style="background:<?= $a['color'] ?>15; color:<?= $a['color'] ?>;">
                                        <i class="<?= $a['icon'] ?>"></i>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title"><?= htmlspecialchars($a['title']) ?></div>
                                        <div class="notif-message"><?= htmlspecialchars($a['message']) ?></div>
                                        <div class="notif-time"><?= htmlspecialchars($a['time']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <button class="icon-btn"><i class="fa-regular fa-envelope"></i></button>
        </div>
    </div>
</header>