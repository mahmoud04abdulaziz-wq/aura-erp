<?php
/**
 * AURA ERP — Application Config
 */

define('APP_NAME', 'AURA Stone ERP');
define('APP_ROOT', dirname(__DIR__));
define('BASE_URL', '/grad-project-prototype');

// Session config
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language switch — must happen before any output
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['lang']);
    if (!empty($params)) {
        $redirect .= '?' . http_build_query($params);
    }
    header("Location: $redirect");
    exit;
}
