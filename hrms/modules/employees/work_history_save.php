<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('employees.edit', 'edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.'); header('Location: ' . APP_URL . '/modules/employees/index.php'); exit;
}

$empId    = (int)($_POST['employee_id'] ?? 0);
$employer = trim($_POST['employer_name'] ?? '');
$position = trim($_POST['position_held'] ?? '');
$start    = $_POST['start_date'] ?: null;
$end      = $_POST['end_date'] ?: null;
$reason   = trim($_POST['reason_for_leaving'] ?? '');
$refName  = trim($_POST['reference_name'] ?? '');
$refPhone = trim($_POST['reference_phone'] ?? '');

if (!$empId || !$employer) {
    setFlash('error', 'Employer name is required.'); header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$empId.'&tab=work_history'); exit;
}

db()->prepare("INSERT INTO employee_work_history (employee_id, employer_name, position_held, start_date, end_date, reason_for_leaving, reference_name, reference_phone)
    VALUES (?,?,?,?,?,?,?,?)")
    ->execute([$empId, $employer, $position ?: null, $start, $end, $reason ?: null, $refName ?: null, $refPhone ?: null]);

auditLog('employees', 'add_work_history', $empId, null, json_encode(['employer'=>$employer,'position'=>$position]));
setFlash('success', "Work history record added.");
header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$empId.'&tab=work_history');
exit;
