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
