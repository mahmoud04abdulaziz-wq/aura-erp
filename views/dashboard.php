<?php
/**
 * AURA ERP — Role-Based Dashboard Router
 * Dynamically loads the customized command center for the logged-in user.
 * CEO/Executive Board can view any department via ?dept= parameter.
 */

$role = $_SESSION['role_name'] ?? 'Executive Board';

// CEO override: allow Executive Board to view any department dashboard
$deptOverride = $_GET['dept'] ?? null;
$isCEO = ($role === 'Executive Board');

if ($isCEO && $deptOverride) {
    $dashboardMap = [
        'sales'       => 'sales.php',
        'production'  => 'production.php',
        'procurement' => 'procurement.php',
        'hr'          => 'hr.php',
        'executive'   => 'executive.php',
    ];
    $file = $dashboardMap[$deptOverride] ?? 'executive.php';
} else {
    // Standard role-based routing
    $roleMap = [
        'Production Manager' => 'production.php',
        'Sales Engineer'     => 'sales.php',
        'Procurement Officer'=> 'procurement.php',
        'HR Manager'         => 'hr.php',
    ];
    $file = $roleMap[$role] ?? 'executive.php';
}

echo '<div class="view-section active" style="display:block;">';

// Show back button if CEO is viewing another department
if ($isCEO && $deptOverride && $deptOverride !== 'executive') {
    echo '<div style="margin-bottom: 1rem;">';
    echo '<a href="' . BASE_URL . '/app.php?view=dashboard" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: var(--bg-secondary); border: 1px solid var(--border-primary); border-radius: 8px; text-decoration: none; color: var(--text-secondary); font-size: 0.85rem; font-weight: 600; transition: 0.2s;">';
    echo '<i class="fa-solid fa-arrow-left"></i> Back to Executive Dashboard';
    echo '</a></div>';
}

require_once __DIR__ . '/dashboards/' . $file;

echo '</div>';
?>