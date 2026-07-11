<?php
require_once dirname(__DIR__) . '/auth/session.php';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

if (isLoggedIn()) {
    auditLog('auth', 'logout', $_SESSION['user_id'] ?? null, null, null, 'User logged out');
}

destroySessionCompletely();

header('Location: ' . APP_URL . '/auth/login.php?reason=logout');
exit;
