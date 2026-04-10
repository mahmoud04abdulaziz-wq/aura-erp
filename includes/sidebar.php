<?php
/**
 * AURA ERP — Sidebar
 * Dynamic sidebar that only shows nav items the user has permission to access.
 */

// Current view from query param
$activeView = $_GET['view'] ?? 'dashboard';

// Module nav configuration: viewId => [icon, label, permission]
$navModules = [
    'dashboard' => ['fa-solid fa-chart-line', 'Dashboard', 'dashboard'],
    'orders' => ['fa-solid fa-layer-group', 'Orders', 'crm'],
    'kanban' => ['fa-solid fa-table-columns', 'Active Batches', 'manufacturing'],
    'production' => ['fa-solid fa-shapes', 'Production', 'manufacturing'],
    'inventory' => ['fa-solid fa-warehouse', 'Inventory', 'inventory'],
    'procurement' => ['fa-solid fa-truck-fast', 'Procurement', 'procurement'],
    'hr' => ['fa-solid fa-users', 'HR', 'hr'],
    'accounting' => ['fa-solid fa-file-invoice-dollar', 'Accounting', 'finance'],
    'invoices' => ['fa-solid fa-file-invoice', 'Invoices', 'finance'],
    'analytics' => ['fa-solid fa-chart-pie', 'Analytics', 'dashboard'],
    'reports' => ['fa-solid fa-print', 'Reports', 'dashboard'],
    'admin' => ['fa-solid fa-user-shield', 'User Accounts', 'admin'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand">
            <i class="fa-solid fa-gem mobile-icon"></i>
            <span class="brand-text">AURA</span>
        </div>
        <button id="sidebar-close" class="mobile-only"><i class="fa-solid fa-times"></i></button>
    </div>

    <nav class="sidebar-nav">
        <ul id="nav-list">
            <?php foreach ($navModules as $viewId => [$icon, $label, $permission]): ?>
                <?php if (hasPermission($permission)): ?>
                    <li data-view="<?= $viewId ?>" <?= ($activeView === $viewId) ? ' class="active"' : '' ?>>
                        <a href="<?= BASE_URL ?>/app.php?view=<?= $viewId ?>">
                            <i class="<?= $icon ?>"></i>
                            <span><?= htmlspecialchars($label) ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/app.php?view=settings"><i class="fa-solid fa-gear"></i> <span>Settings</span></a>
    </div>
</aside>