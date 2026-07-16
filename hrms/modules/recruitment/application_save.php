<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('recruitment.manage', 'create');

// KOM-0xx: no code path anywhere created a recruitment_applications row —
// modules/recruitment/index.php only ever listed/filtered applications and
// application_update.php only ever changed an existing one's status. The
// only place an application row was ever created was demo seed data. The
// recruitment pipeline's first step ("Application") had no way to actually
// happen. This is the missing "Add Application" entry point.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/recruitment/index.php?tab=applications'); exit;
}

$vacancyId = (int)($_POST['vacancy_id'] ?? 0);
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');

$errors = [];
if (!$vacancyId) $errors[] = 'Vacancy is required.';
if (!$firstName) $errors[] = 'First name is required.';
if (!$lastName)  $errors[] = 'Last name is required.';
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

if (empty($errors)) {
    $vac = db()->prepare("SELECT id, job_title FROM recruitment_vacancies WHERE id=?");
    $vac->execute([$vacancyId]);
    $vac = $vac->fetch();
    if (!$vac) $errors[] = 'Selected vacancy not found.';
}

// Duplicate-application guard: same email applying to the same vacancy
// more than once. Deliberately scoped per-vacancy, not globally — a
// candidate legitimately applying to two different open roles is not a
// duplicate.
if (empty($errors)) {
    $dup = db()->prepare("SELECT id FROM recruitment_applications WHERE vacancy_id=? AND email=?");
    $dup->execute([$vacancyId, $email]);
    if ($dup->fetch()) {
        $errors[] = "An application from {$email} already exists for this vacancy.";
    }
}

if (!empty($errors)) {
    setFlash('error', implode(' ', $errors));
    header('Location: ' . APP_URL . '/modules/recruitment/index.php?tab=applications'); exit;
}

$cvFile = null;
if (!empty($_FILES['cv_file']['name'])) {
    $upload = uploadFile($_FILES['cv_file'], 'recruitment', ALLOWED_DOC_TYPES);
    if ($upload['success']) {
        $cvFile = $upload['path'];
    } else {
        setFlash('error', 'CV upload failed: ' . $upload['error']);
        header('Location: ' . APP_URL . '/modules/recruitment/index.php?tab=applications'); exit;
    }
}

$applicationNumber = 'APP-' . date('Y') . '-' . str_pad((string)(db()->query("SELECT COUNT(*) FROM recruitment_applications")->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);

db()->prepare("INSERT INTO recruitment_applications
    (vacancy_id, application_number, first_name, last_name, email, phone, current_position, current_employer,
     years_experience, qualifications, cover_letter, cv_file, status)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'submitted')")
    ->execute([
        $vacancyId, $applicationNumber, $firstName, $lastName, $email, $phone ?: null,
        trim($_POST['current_position'] ?? '') ?: null,
        trim($_POST['current_employer'] ?? '') ?: null,
        (int)($_POST['years_experience'] ?? 0),
        trim($_POST['qualifications'] ?? '') ?: null,
        trim($_POST['cover_letter'] ?? '') ?: null,
        $cvFile,
    ]);

$newId = (int)db()->lastInsertId();
auditLog('recruitment', 'create_application', $newId, null,
    json_encode(['vacancy_id' => $vacancyId, 'name' => "$firstName $lastName", 'email' => $email]),
    "New application for {$vac['job_title']}");

setFlash('success', "Application from {$firstName} {$lastName} recorded for {$vac['job_title']}.");
header('Location: ' . APP_URL . '/modules/recruitment/index.php?tab=applications');
exit;
