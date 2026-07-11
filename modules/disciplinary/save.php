<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('disciplinary.manage', 'create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/disciplinary/index.php'); exit;
}

$empId      = (int)($_POST['employee_id'] ?? 0);
$recordType = $_POST['record_type'] ?? 'disciplinary';

if (!$empId) { setFlash('error','Employee required.'); header('Location: ' . APP_URL . '/modules/disciplinary/index.php'); exit; }

if ($recordType === 'disciplinary') {
    $incidentType = $_POST['incident_type'] ?? 'verbal_warning';
    $incidentDate = $_POST['incident_date'] ?? date('Y-m-d');
    $description  = trim($_POST['description'] ?? '');
    $actionTaken  = trim($_POST['action_taken'] ?? '');

    db()->prepare("INSERT INTO disciplinary_records (employee_id, incident_type, incident_date, description, action_taken, issued_by)
        VALUES (?,?,?,?,?,?)")
        ->execute([$empId, $incidentType, $incidentDate, $description, $actionTaken ?: null, $_SESSION['user_id']]);

    auditLog('disciplinary','create',$empId,null,json_encode(['type'=>$incidentType,'date'=>$incidentDate]));
    setFlash('success','Disciplinary record added.');
} else {
    $subject     = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');

    db()->prepare("INSERT INTO grievance_records (employee_id, subject, description, submitted_by)
        VALUES (?,?,?,?)")
        ->execute([$empId, $subject, $description ?: null, $_SESSION['user_id']]);

    auditLog('disciplinary','log_grievance',$empId,null,json_encode(['subject'=>$subject]));
    setFlash('success','Grievance logged successfully.');
}

header('Location: ' . APP_URL . '/modules/disciplinary/index.php?tab=' . ($recordType==='grievance'?'grievances':'disciplinary'));
exit;
