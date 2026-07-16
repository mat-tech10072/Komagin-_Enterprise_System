<?php
require_once dirname(__DIR__) . '/auth/session.php';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

requireLogin();
header('Content-Type: application/json');

$empId = (int)($_GET['emp'] ?? 0);
$ltId  = (int)($_GET['lt'] ?? 0);

if (!$empId || !$ltId) { echo json_encode(['found'=>false]); exit; }

$stmt = db()->prepare("SELECT lb.*, lt.name as type_name, lt.is_paid
    FROM leave_balances lb JOIN leave_types lt ON lb.leave_type_id=lt.id
    WHERE lb.employee_id=? AND lb.leave_type_id=? AND lb.year=?");
$stmt->execute([$empId, $ltId, date('Y')]);
$bal = $stmt->fetch();

if ($bal) {
    echo json_encode([
        'found'       => true,
        'total_days'  => $bal['entitled_days'],
        'remaining'   => $bal['remaining_days'],
        'used'        => $bal['used_days'],
        'pending'     => $bal['pending_days'],
        'is_paid'     => $bal['is_paid'],
    ]);
} else {
    echo json_encode(['found'=>false]);
}
