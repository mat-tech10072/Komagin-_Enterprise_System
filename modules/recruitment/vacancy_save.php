<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('recruitment.manage', 'create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/recruitment/index.php'); exit;
}

$title = trim($_POST['job_title'] ?? '');
if (!$title) { setFlash('error','Job title required.'); header('Location: ' . APP_URL . '/modules/recruitment/index.php'); exit; }

db()->prepare("INSERT INTO recruitment_vacancies (job_title, department_id, employment_type, description, requirements, deadline, status, created_by)
    VALUES (?,?,?,?,?,?,?,?)")
    ->execute([
        $title,
        (int)($_POST['department_id'] ?? 0) ?: null,
        $_POST['employment_type'] ?? 'full_time',
        trim($_POST['description'] ?? '') ?: null,
        trim($_POST['requirements'] ?? '') ?: null,
        $_POST['deadline'] ?: null,
        'open',
        $_SESSION['user_id']
    ]);

auditLog('recruitment','create_vacancy',null,null,json_encode(['title'=>$title]));
setFlash('success',"Vacancy '{$title}' posted.");
header('Location: ' . APP_URL . '/modules/recruitment/index.php');
exit;
