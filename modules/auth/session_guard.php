<?php
/**
 * AURA ERP — Session Guard (RBAC)
 * Include this at the top of every protected page.
 * 
 * Usage: 
 *   require_once __DIR__ . '/session_guard.php';
 *   guard('manufacturing'); // pass module name to check permission
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db_connect.php';

/**
 * Check if user is authenticated. Redirect to login if not.
 */
function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
}

/**
 * Check if the logged-in user has permission for a given module.
 * Logs unauthorized access attempts to system_logs.
 */
function guard(string $module): void
{
    requireLogin();

    $permissions = $_SESSION['permissions'] ?? [];

    if (!in_array($module, $permissions, true)) {
        // Log unauthorized access attempt
        global $pdo;
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO system_logs (user_id, action_type, description, status) 
                 VALUES (?, 'UNAUTHORIZED_ACCESS', ?, 'Failure')"
            );
            $stmt->execute([
                $_SESSION['user_id'],
                "Attempted to access /{$module} without permission. Role: " . ($_SESSION['role_name'] ?? 'Unknown')
            ]);
        } catch (Exception $e) {
            error_log("Failed to log unauthorized access: " . $e->getMessage());
        }

        // Show access denied
        http_response_code(403);
        include dirname(__DIR__, 2) . '/includes/header.php';
        echo '<div class="access-denied-container" style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center;">
                <i class="fa-solid fa-shield-halved" style="font-size:4rem;color:var(--accent-danger,#e74c3c);margin-bottom:1.5rem;"></i>
                <h1 style="margin-bottom:0.5rem;">Access Denied</h1>
                <p style="color:var(--text-secondary);margin-bottom:2rem;">You do not have permission to access the <strong>' . htmlspecialchars($module) . '</strong> module.</p>
                <a href="' . BASE_URL . '/index.php" class="btn btn-primary" style="padding:0.75rem 2rem;background:var(--accent-primary,#6366f1);color:#fff;border-radius:8px;text-decoration:none;">Back to Dashboard</a>
              </div>';
        include dirname(__DIR__, 2) . '/includes/footer.php';
        exit;
    }
}

/**
 * Get the current user's display name.
 */
function getCurrentUserName(): string
{
    return $_SESSION['employee_name'] ?? 'User';
}

/**
 * Get the current user's initials (for avatar).
 */
function getUserInitials(): string
{
    $name = getCurrentUserName();
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return substr($initials, 0, 2);
}

/**
 * Check if user has a specific permission (without blocking).
 */
function hasPermission(string $module): bool
{
    $permissions = $_SESSION['permissions'] ?? [];
    return in_array($module, $permissions, true);
}
