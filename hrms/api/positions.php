<?php
require_once dirname(__DIR__) . '/auth/session.php';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$deptId = (int)($_GET['department_id'] ?? 0);
$positions = getPositions($deptId);
echo json_encode($positions);
