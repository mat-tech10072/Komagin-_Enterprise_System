<?php
require_once __DIR__ . '/_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['cp_consultant_id'])) {
    header('Location: ' . CP_URL . '/dashboard.php');
} else {
    header('Location: ' . CP_URL . '/login.php');
}
exit;
