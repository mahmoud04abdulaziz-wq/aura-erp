<?php
/**
 * MiskStone ERP — Sidebar
 * Dynamic sidebar with collapsible parent-child nav groups.
 * Only shows items the user has permission to access.
 */

$activeView = $_GET['view'] ?? 'dashboard';

// Module navigation with parent-child groups
// Format: 'viewId' => [icon, label, permission]
// Group headers: '__group_name' => [null, 'Group Label', null]
$navModules = [
    'dashboard' => ['fa-solid fa-chart-line', 'Dashboard', 'dashboard'],

    // ─── Sales ───
    '__sales' => [null, 'SALES', null],
    'sales_inbox' => ['fa-solid fa-inbox', 'Order Inbox', 'crm'],
    'orders' => ['fa-solid fa-layer-group', 'Orders', 'crm'],
    'sales_delivery' => ['fa-solid fa-truck-ramp-box', 'Delivery Dispatch', 'crm'],
    'order_archive' => ['fa-solid fa-box-archive', 'Archive', 'crm'],

    // ─── Production ───
    '__production' => [null, 'PRODUCTION', null],
    'prod_orders' => ['fa-solid fa-clipboard-list', 'Production Orders', 'manufacturing'],
    'mix_batches' => ['fa-solid fa-industry', 'Mix Batches', 'manufacturing'],
    'prod_mixing' => ['fa-solid fa-blender', 'Mixing Station', 'manufacturing'],
    'prod_curing' => ['fa-solid fa-hourglass-half', 'Curing Station', 'manufacturing'],
    'prod_finish' => ['fa-solid fa-flag-checkered', 'Finish Production', 'manufacturing'],
    'mix_recipes' => ['fa-solid fa-flask', 'Mix Recipes', 'manufacturing'],
    'prod_material_requests' => ['fa-solid fa-boxes-packing', 'Request Materials', 'manufacturing'],
    'kanban' => ['fa-solid fa-table-columns', 'Order Pipeline', 'manufacturing'],

    // ─── Procurement ───
    '__procurement' => [null, 'PROCUREMENT', null],
    'proc_home' => ['fa-solid fa-truck-fast', 'Procurement Home', 'procurement'],
    'proc_suppliers' => ['fa-solid fa-building', 'Suppliers', 'procurement'],
    'proc_create' => ['fa-solid fa-file-circle-plus', 'Create PO', 'procurement'],
    'proc_track' => ['fa-solid fa-list-check', 'Track Orders', 'procurement'],
    'proc_receive' => ['fa-solid fa-box-open', 'Receive Orders', 'procurement'],
    'proc_requests' => ['fa-solid fa-envelope-open-text', 'Incoming Requests', 'procurement'],
    'proc_rop' => ['fa-solid fa-chart-line', 'ROP Alerts', 'procurement'],

    // ─── Other ───
    '__other' => [null, 'MANAGEMENT', null],
    'inventory' => ['fa-solid fa-warehouse', 'Inventory', 'inventory'],
    'hr' => ['fa-solid fa-users', 'HR', 'hr'],
    'accounting' => ['fa-solid fa-file-invoice-dollar', 'Accounting', 'finance'],
    'invoices' => ['fa-solid fa-file-invoice', 'Invoices', 'finance'],
    'bi' => ['fa-solid fa-brain', 'Business Intelligence', 'dashboard'],
    'analytics' => ['fa-solid fa-calculator', 'Math Models', 'dashboard'],
    'reports' => ['fa-solid fa-print', 'Reports', 'dashboard'],
    'admin' => ['fa-solid fa-user-shield', 'User Accounts', 'admin'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand">
            <i class="fa-solid fa-gem mobile-icon"></i>
            <span class="brand-text">MiskStone</span>
        </div>
        <button id="sidebar-close" class="mobile-only"><i class="fa-solid fa-times"></i></button>
    </div>

    <nav class="sidebar-nav">
        <ul id="nav-list">
            <?php foreach ($navModules as $viewId => [$icon, $label, $permission]): ?>
                <?php if (str_starts_with($viewId, '__')): ?>
                    <?php
                    // Check if group has any visible children
                    $groupKey = $viewId;
                    $hasVisibleChild = false;
                    $foundGroup = false;
                    foreach ($navModules as $checkId => $checkItem) {
                        if ($checkId === $groupKey) { $foundGroup = true; continue; }
                        if ($foundGroup && str_starts_with($checkId, '__')) break; // next group
                        if ($foundGroup && $checkItem[2] !== null && hasPermission($checkItem[2])) {
                            $hasVisibleChild = true;
                            break;
                        }
                    }
                    if ($hasVisibleChild):
                    ?>
                    <li class="nav-group-label">
                        <span><?= htmlspecialchars($label) ?></span>
                    </li>
                    <?php endif; ?>
                <?php elseif (hasPermission($permission)): ?>
                    <li data-view="<?= $viewId ?>"<?= ($activeView === $viewId) ? ' class="active"' : '' ?>>
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