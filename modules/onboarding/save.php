<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('onboarding.manage', 'edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/onboarding/index.php'); exit;
}

$empId = (int)($_POST['employee_id'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if (!$empId) { setFlash('error','Employee required.'); header('Location: ' . APP_URL . '/modules/onboarding/index.php'); exit; }

// Check if checklist exists
$existing = db()->prepare("SELECT id FROM onboarding_checklists WHERE employee_id=? AND status!='completed'");
$existing->execute([$empId]);
if ($existing->fetch()) { setFlash('warning','An active checklist already exists for this employee.'); header('Location: ' . APP_URL . '/modules/onboarding/index.php'); exit; }

db()->prepare("INSERT INTO onboarding_checklists (employee_id, notes, created_by) VALUES (?,?,?)")
    ->execute([$empId, $notes ?: null, $_SESSION['user_id']]);

auditLog('onboarding','create_checklist',null,null,json_encode(['employee_id'=>$empId]));
setFlash('success','Onboarding checklist created.');
header('Location: ' . APP_URL . '/modules/onboarding/index.php');
exit;
