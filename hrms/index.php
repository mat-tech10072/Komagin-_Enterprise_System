<?php
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
} else {
    header('Location: ' . APP_URL . '/auth/login.php');
}
exit;
