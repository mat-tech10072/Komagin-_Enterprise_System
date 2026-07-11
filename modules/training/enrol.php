<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('training.enrol', 'create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/training/index.php'); exit;
}

$programId = (int)($_POST['program_id'] ?? 0);
$empId     = (int)($_POST['employee_id'] ?? 0);

if (!$programId || !$empId) { setFlash('error','Program and employee are required.'); header('Location: ' . APP_URL . '/modules/training/index.php'); exit; }

$existing = db()->prepare("SELECT id FROM training_attendance WHERE program_id=? AND employee_id=?");
$existing->execute([$programId, $empId]);
if ($existing->fetch()) { setFlash('warning','Employee already enrolled in this program.'); header('Location: ' . APP_URL . '/modules/training/index.php'); exit; }

db()->prepare("INSERT INTO training_attendance (program_id, employee_id, created_by) VALUES (?,?,?)")
    ->execute([$programId, $empId, $_SESSION['user_id']]);

auditLog('training','enrol',null,null,json_encode(['program'=>$programId,'employee'=>$empId]));
setFlash('success','Employee enrolled successfully.');
header('Location: ' . APP_URL . '/modules/training/index.php');
exit;
