<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('recruitment.review', 'approve');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error','Invalid request.'); header('Location: '.APP_URL.'/modules/recruitment/index.php?tab=applications'); exit;
}

$appId      = (int)($_POST['application_id'] ?? 0);
$newStatus  = $_POST['new_status'] ?? '';
$notes      = trim($_POST['hr_remarks'] ?? '');
$interviewDate = $_POST['interview_date'] ?: null;

$validStatuses = ['submitted','reviewing','shortlisted','interview_scheduled','interviewed','selected','rejected','withdrawn'];

if (!$appId || !in_array($newStatus, $validStatuses)) {
    setFlash('error','Invalid application or status.'); header('Location: '.APP_URL.'/modules/recruitment/index.php?tab=applications'); exit;
}

$app = db()->prepare("SELECT ra.*, rv.job_title FROM recruitment_applications ra JOIN recruitment_vacancies rv ON ra.vacancy_id=rv.id WHERE ra.id=?");
$app->execute([$appId]); $app = $app->fetch();
if (!$app) { setFlash('error','Application not found.'); header('Location: '.APP_URL.'/modules/recruitment/index.php?tab=applications'); exit; }

$oldStatus = $app['status'];
db()->prepare("UPDATE recruitment_applications SET status=?, hr_remarks=?, interview_date=?, reviewed_by=? WHERE id=?")
    ->execute([$newStatus, $notes ?: null, $interviewDate, $_SESSION['user_id'], $appId]);

auditLog('recruitment','update_application_status',$appId,
    json_encode(['status'=>$oldStatus]),
    json_encode(['status'=>$newStatus,'notes'=>$notes]),
    "Pipeline stage: $oldStatus → $newStatus");

$name = $app['first_name'].' '.$app['last_name'];
setFlash('success', "Application for $name updated to: ".ucwords(str_replace('_',' ',$newStatus)).".");
header('Location: '.APP_URL.'/modules/recruitment/index.php?tab=applications'); exit;
