<?php
require_once __DIR__ . '/db.php';
// check_auth.php - Authentication middleware for Komagin Limited Admin Panel

if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = __DIR__ . '/sessions';
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }
    session_start();
}

define('SESSION_TIMEOUT', 7200); // 2 hours in seconds
define('SESSION_WARNING_TIME', 600); // 10 minutes before timeout

function isAuthenticated() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    if (isset($_SESSION['admin_last_activity'])) {
        $inactiveTime = time() - $_SESSION['admin_last_activity'];
        
        if ($inactiveTime > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    $_SESSION['admin_last_activity'] = time();
    return true;
}

function refreshSession() {
    $_SESSION['admin_last_activity'] = time();
}

function getSessionInfo() {
    if (!isset($_SESSION['admin_logged_in'])) {
        return null;
    }
    
    return [
        'logged_in' => $_SESSION['admin_logged_in'],
        'username' => $_SESSION['admin_username'] ?? 'Unknown',
        'user_id' => $_SESSION['admin_id'] ?? null,
        'role' => $_SESSION['admin_role'] ?? 'admin',
        'user_role' => $_SESSION['user_role'] ?? ($_SESSION['admin_role'] ?? 'admin'),
        'session_start' => date('Y-m-d H:i:s', $_SESSION['admin_session_start'] ?? time()),
        'last_activity' => date('Y-m-d H:i:s', $_SESSION['admin_last_activity'] ?? time())
    ];
}

function requireAdmin() {
    if (!isAuthenticated()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Authentication required', 'redirect' => 'auth.php']);
            exit;
        } else {
            header('Location: auth.php?error=session_timeout');
            exit;
        }
    }
    
    refreshSession();
}

function requireWebAdmin() {
    requireAdmin();
    $role = $_SESSION['user_role'] ?? ($_SESSION['admin_role'] ?? '');
    if ($role !== 'admin') {
        header('Location: index.php?error=role');
        exit;
    }
}

function requireRole($role) {
    requireAdmin();
    $currentRole = $_SESSION['user_role'] ?? ($_SESSION['admin_role'] ?? '');
    if ($currentRole !== $role && $currentRole !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
        exit;
    }
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CSRF Protection - Generate token if not exists
if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCsrfToken() {
    return $_SESSION['csrf_token'] ?? '';
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting for API requests
function checkRateLimit($key, $limit = 100, $window = 3600) {
    if (!isset($_SESSION['rate_limit_' . $key])) {
        $_SESSION['rate_limit_' . $key] = ['count' => 1, 'reset' => time() + $window];
        return true;
    }
    
    $data = $_SESSION['rate_limit_' . $key];
    if (time() > $data['reset']) {
        $_SESSION['rate_limit_' . $key] = ['count' => 1, 'reset' => time() + $window];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limit_' . $key]['count']++;
    return true;
}

// Log admin activity
function logAdminActivity($action, $details = '') {
    $logFile = __DIR__ . '/logs/admin_activity.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $username = $_SESSION['admin_username'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] User: {$username} | IP: {$ip} | Action: {$action} | Details: {$details}" . PHP_EOL;
    
    error_log($logEntry, 3, $logFile);
}

// Check if this is an API request
if (php_sapi_name() !== 'cli' && (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
    // This is an AJAX request, just check authentication without redirecting
    if (!isAuthenticated()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
} elseif (!isAuthenticated() && !str_contains($_SERVER['SCRIPT_NAME'], 'auth.php')) {
    // Not an AJAX request and not on auth page, redirect to login
    header('Location: auth.php?error=session_timeout');
    exit;
}

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Session security: bind to IP address (optional, uncomment for extra security)
// if (isset($_SESSION['admin_ip']) && $_SESSION['admin_ip'] !== $_SERVER['REMOTE_ADDR']) {
//     session_unset();
//     session_destroy();
//     header('Location: auth.php?error=session_hijack');
//     exit;
// }
// $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'];
?>