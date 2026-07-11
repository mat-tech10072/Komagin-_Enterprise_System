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
$fullName = trim($_POST['full_name'] ?? '');
$rel      = $_POST['relationship'] ?? '';
$dob      = $_POST['date_of_birth'] ?: null;
$gender   = $_POST['gender'] ?: null;
$natId    = trim($_POST['national_id'] ?? '');
$benePct  = $_POST['beneficiary_percentage'] !== '' ? (float)$_POST['beneficiary_percentage'] : null;
$isBene   = $benePct !== null ? 1 : 0;

$validRels = ['spouse','child','parent','sibling','other'];

if (!$empId || !$fullName || !in_array($rel, $validRels)) {
    setFlash('error', 'Required fields missing.'); header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$empId.'&tab=dependents'); exit;
}

$emp = getEmployee($empId);
if (!$emp) { setFlash('error', 'Employee not found.'); header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

db()->prepare("INSERT INTO employee_dependents (employee_id, full_name, relationship, date_of_birth, gender, national_id, is_beneficiary, beneficiary_percentage)
    VALUES (?,?,?,?,?,?,?,?)")
    ->execute([$empId, $fullName, $rel, $dob, $gender, $natId ?: null, $isBene, $benePct]);

auditLog('employees', 'add_dependent', $empId, null, json_encode(['name'=>$fullName,'relationship'=>$rel]));
setFlash('success', "Dependent \"$fullName\" added.");
header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$empId.'&tab=dependents');
exit;
