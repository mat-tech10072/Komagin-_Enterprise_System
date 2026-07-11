<?php
require_once __DIR__ . '/_config.php';
require_once dirname(__DIR__) . '/auth/session_common.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$reason = $_GET['reason'] ?? 'logout';
destroySessionCompletely();
header('Location: ' . EP_URL . '/login.php?reason=' . urlencode($reason));
exit;
