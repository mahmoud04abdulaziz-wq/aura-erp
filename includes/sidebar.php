<?php
/**
 * MiskStone ERP — Sidebar
 * Dynamic sidebar with collapsible parent-child nav groups.
 * Only shows items the user has permission to access.
 */
require_once __DIR__ . '/../config/lang.php';

$activeView = $_GET['view'] ?? 'dashboard';

// Module navigation with parent-child groups
// Format: 'viewId' => [icon, label, permission]
// Group headers: '__group_name' => [null, 'Group Label', null]
$navModules = [
    'dashboard' => ['fa-solid fa-chart-line', t('sidebar_dashboard'), 'dashboard'],

    // ─── Sales ───
    '__sales' => [null, t('sidebar_sales'), null],
    'sales_inbox' => ['fa-solid fa-inbox', t('sidebar_order_inbox'), 'crm'],
    'orders' => ['fa-solid fa-layer-group', t('sidebar_orders'), 'crm'],
    'sales_delivery' => ['fa-solid fa-truck-ramp-box', t('sidebar_delivery_dispatch'), 'crm'],
    'order_archive' => ['fa-solid fa-box-archive', t('sidebar_archive'), 'crm'],

    // ─── Production ───
    '__production' => [null, t('sidebar_production'), null],
    'prod_orders' => ['fa-solid fa-clipboard-list', t('sidebar_production_orders'), 'manufacturing'],
    'mix_batches' => ['fa-solid fa-industry', t('sidebar_mix_batches'), 'manufacturing'],
    'prod_mixing' => ['fa-solid fa-blender', t('sidebar_mixing_station'), 'manufacturing'],
    'prod_curing' => ['fa-solid fa-hourglass-half', t('sidebar_curing_station'), 'manufacturing'],
    'prod_finish' => ['fa-solid fa-flag-checkered', t('sidebar_finish_production'), 'manufacturing'],
    'mix_recipes' => ['fa-solid fa-flask', t('sidebar_mix_recipes'), 'manufacturing'],
    'prod_material_requests' => ['fa-solid fa-boxes-packing', t('sidebar_request_materials'), 'manufacturing'],
    'kanban' => ['fa-solid fa-table-columns', t('sidebar_order_pipeline'), 'manufacturing'],

    // ─── Procurement ───
    '__procurement' => [null, t('sidebar_procurement_group'), null],
    'proc_home' => ['fa-solid fa-truck-fast', t('sidebar_procurement'), 'procurement'],
    'proc_suppliers' => ['fa-solid fa-building', t('sidebar_suppliers'), 'procurement'],
    'proc_create' => ['fa-solid fa-file-circle-plus', t('sidebar_create_po'), 'procurement'],
    'proc_track' => ['fa-solid fa-list-check', t('sidebar_track_orders'), 'procurement'],
    'proc_receive' => ['fa-solid fa-box-open', t('sidebar_receive_orders'), 'procurement'],
    'proc_requests' => ['fa-solid fa-envelope-open-text', t('sidebar_incoming_requests'), 'procurement'],
    'proc_rop' => ['fa-solid fa-chart-line', t('sidebar_rop_alerts'), 'procurement'],

    // ─── Other ───
    '__other' => [null, t('sidebar_management'), null],
    'inventory' => ['fa-solid fa-warehouse', t('sidebar_inventory'), 'inventory'],
    'hr' => ['fa-solid fa-users', t('sidebar_hr'), 'hr'],
    'hr_careers' => ['fa-solid fa-briefcase', t('sidebar_careers'), 'hr'],
    'accounting' => ['fa-solid fa-file-invoice-dollar', t('sidebar_accounting'), 'finance'],
    'invoices' => ['fa-solid fa-file-invoice', t('sidebar_invoices'), 'finance'],
    'bi' => ['fa-solid fa-brain', t('sidebar_bi'), 'dashboard'],
    'analytics' => ['fa-solid fa-calculator', t('sidebar_math_models'), 'dashboard'],
    'reports' => ['fa-solid fa-print', t('sidebar_reports'), 'dashboard'],
    'admin' => ['fa-solid fa-user-shield', t('sidebar_user_accounts'), 'admin'],
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
        <a href="<?= BASE_URL ?>/app.php?view=settings"><i class="fa-solid fa-gear"></i> <span><?= t('settings') ?></span></a>
    </div>
</aside>