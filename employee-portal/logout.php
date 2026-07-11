<?php
require_once __DIR__ . '/_config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$reason = $_GET['reason'] ?? 'logout';
session_destroy();
header('Location: ' . EP_URL . '/login.php?reason=' . urlencode($reason));
exit;
