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
$title    = trim($_POST['title'] ?? '');
$type     = $_POST['qualification_type'] ?? '';
$inst     = trim($_POST['institution'] ?? '');
$field    = trim($_POST['field_of_study'] ?? '');
$year     = $_POST['year_obtained'] ? (int)$_POST['year_obtained'] : null;
$grade    = trim($_POST['grade_result'] ?? '');

$validTypes = ['matric','diploma','degree','honours','masters','phd','trade_cert','professional_cert','other'];

if (!$empId || !$title || !in_array($type, $validTypes)) {
    setFlash('error', 'Required fields missing.'); header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$empId.'&tab=qualifications'); exit;
}

$certFile = null;
if (!empty($_FILES['certificate']['name'])) {
    $upload = uploadFile($_FILES['certificate'], 'qualifications', ALLOWED_DOC_TYPES);
    if ($upload['success']) $certFile = $upload['path'];
}

db()->prepare("INSERT INTO employee_qualifications (employee_id, qualification_type, title, institution, field_of_study, year_obtained, grade_result, certificate_file)
    VALUES (?,?,?,?,?,?,?,?)")
    ->execute([$empId, $type, $title, $inst ?: null, $field ?: null, $year, $grade ?: null, $certFile]);

auditLog('employees', 'add_qualification', $empId, null, json_encode(['title'=>$title,'type'=>$type,'year'=>$year]));
setFlash('success', "Qualification \"$title\" added.");
header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$empId.'&tab=qualifications');
exit;
