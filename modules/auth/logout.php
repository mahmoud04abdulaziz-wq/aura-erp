<?php
/**
 * AURA ERP — Logout
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db_connect.php';

// Log logout action before destroying session
$isCustomer = ($_SESSION['role_name'] ?? '') === 'Customer';
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'LOGOUT', 'User logged out', 'Success')"
        );
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log("Logout log failed: " . $e->getMessage());
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();

if ($isCustomer) {
    header('Location: ' . BASE_URL . '/');
} else {
    header('Location: ' . BASE_URL . '/modules/auth/employee_login.php');
}
exit;
