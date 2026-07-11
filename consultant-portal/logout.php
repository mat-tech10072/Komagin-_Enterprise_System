<?php
require_once __DIR__ . '/_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

foreach (['cp_consultant_id','cp_type','cp_name','cp_number','cp_login_time','cp_last_activity','cp_last_regen'] as $k) {
    unset($_SESSION[$k]);
}

header('Location: ' . CP_URL . '/login.php?reason=logout');
exit;
