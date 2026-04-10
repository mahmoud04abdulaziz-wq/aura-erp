<?php
/**
 * AURA ERP — Main Application Router
 * Protected entry point. Requires authentication.
 * Routes to the correct module view based on ?view= parameter.
 */
require_once __DIR__ . '/modules/auth/session_guard.php';
requireLogin();

// --- VIEW ROUTING ---
$view = $_GET['view'] ?? 'dashboard';

// Map of view IDs to [pageTitle, requiredPermission, viewFile]
$viewMap = [
    'dashboard' => ['Dashboard', 'dashboard', __DIR__ . '/views/dashboard.php'],
    'orders' => ['Orders', 'crm', __DIR__ . '/views/orders.php'],
    'kanban' => ['Orders Progress', 'manufacturing', __DIR__ . '/views/kanban.php'],
    'production' => ['Production', 'manufacturing', __DIR__ . '/views/production.php'],
    'inventory' => ['Inventory', 'inventory', __DIR__ . '/views/inventory.php'],
    'procurement' => ['Procurement', 'procurement', __DIR__ . '/views/procurement.php'],
    'hr' => ['HR', 'hr', __DIR__ . '/views/hr.php'],
    'accounting' => ['Accounting', 'finance', __DIR__ . '/views/accounting.php'],
    'invoices' => ['Invoices', 'finance', __DIR__ . '/views/invoices.php'],
    'analytics' => ['Analytics', 'dashboard', __DIR__ . '/views/analytics.php'],
    'reports' => ['Reports', 'dashboard', __DIR__ . '/views/reports.php'],
    'admin' => ['User Accounts', 'admin', __DIR__ . '/views/admin.php'],
    'settings' => ['Settings', 'dashboard', __DIR__ . '/views/settings.php'],
];

// Validate view exists
if (!isset($viewMap[$view])) {
    $view = 'dashboard';
}

[$pageTitle, $requiredPermission, $viewFile] = $viewMap[$view];

// Check permission for this view
if ($view !== 'dashboard') {
    guard($requiredPermission);
}

// --- RENDER ---
require_once __DIR__ . '/includes/header.php';
?>

<!-- Sidebar -->
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<!-- Main Content -->
<main class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div id="content-area">
        <?php
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            // Module under construction placeholder
            ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:65vh;text-align:center;padding:3rem;">
                <i class="fa-solid fa-hammer" style="font-size:5rem;color:var(--accent-primary);margin-bottom:2rem;opacity:0.5;"></i>
                <h1 style="margin-bottom:0.75rem;color:var(--text-primary);font-size:2rem;"><?= htmlspecialchars($pageTitle) ?> Module</h1>
                <p style="color:var(--text-secondary);max-width:500px;font-size:1.1rem;line-height:1.6;">
                    This module is currently under development.<br>It will be available once the backend logic is implemented.
                </p>
            </div>
            <?php
        }
        ?>
    </div>

    <div style="height: 50vh;"></div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>