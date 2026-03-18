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
            <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>
            <button class="icon-btn"><i class="fa-regular fa-envelope"></i></button>
        </div>
    </div>
</header>