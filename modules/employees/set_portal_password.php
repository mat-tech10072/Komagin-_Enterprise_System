<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requirePermission('employees.portal_password', 'edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/employees/index.php');
    exit;
}

$empId  = (int)($_POST['employee_id'] ?? 0);
$pass   = $_POST['new_password']     ?? '';
$confirm= $_POST['confirm_password'] ?? '';

if (!$empId) {
    setFlash('error','Invalid employee.');
    header('Location: ' . APP_URL . '/modules/employees/index.php');
    exit;
}

if (strlen($pass) < 8) {
    setFlash('error','Password must be at least 8 characters.');
    header('Location: ' . APP_URL . '/modules/employees/view.php?id=' . $empId);
    exit;
}

if ($pass !== $confirm) {
    setFlash('error','Passwords do not match.');
    header('Location: ' . APP_URL . '/modules/employees/view.php?id=' . $empId);
    exit;
}

$hash = password_hash($pass, PASSWORD_BCRYPT);
db()->prepare("UPDATE employees SET portal_password=?, portal_active=1,
    portal_policy_agreed=0, portal_policy_agreed_at=NULL
    WHERE id=?")->execute([$hash, $empId]);

auditLog('employees','portal_password_set',$empId,null,null,
    "Portal password set for employee {$empId}");

setFlash('success','Portal password set successfully. The employee can now log in with their Employee Number and this password.');
header('Location: ' . APP_URL . '/modules/employees/view.php?id=' . $empId);
exit;
