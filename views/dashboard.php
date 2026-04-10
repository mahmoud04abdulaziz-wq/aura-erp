<?php
/**
 * AURA ERP — Role-Based Dashboard Router
 * Dynamically loads the customized command center for the logged-in user.
 */

$role = $_SESSION['role_name'] ?? 'Executive Board';

echo '<div class="view-section active" style="display:block;">';

if ($role === 'Production Manager') {
    require_once __DIR__ . '/dashboards/production.php';
} elseif ($role === 'Sales Engineer') {
    require_once __DIR__ . '/dashboards/sales.php';
} elseif ($role === 'Procurement Officer') {
    require_once __DIR__ . '/dashboards/procurement.php';
} elseif ($role === 'HR Manager') {
    require_once __DIR__ . '/dashboards/hr.php';
} else {
    // Executive Board & Default Fallback
    require_once __DIR__ . '/dashboards/executive.php';
}

echo '</div>';
?>