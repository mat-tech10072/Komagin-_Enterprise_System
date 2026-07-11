<?php
require_once dirname(__DIR__) . '/config/config.php';

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
// Enable secure cookies when running over HTTPS or in production
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || defined('APP_ENV') && APP_ENV === 'production') {
    ini_set('session.cookie_secure', 1);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically
if (!isset($_SESSION['last_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['last_regenerated'] = time();
} elseif (time() - $_SESSION['last_regenerated'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regenerated'] = time();
}

// Session timeout check
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    header('Location: ' . APP_URL . '/auth/login.php?reason=timeout');
    exit;
}
$_SESSION['last_activity'] = time();
