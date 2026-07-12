<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('training.manage', 'edit');

// KOM-008 (continued): no code anywhere ever updated training_attendance
// after the initial enrolment row was created — there was no way to mark
// completion at all. This is the missing "Attendance/Completion" step in
// the charter's training lifecycle (Assignment -> Attendance -> Completion
// -> Certification -> Reporting).
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/training/index.php?tab=attendance'); exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/training/index.php?tab=attendance'); exit; }

$stmt = db()->prepare("SELECT id FROM training_attendance WHERE id=?");
$stmt->execute([$id]);
if (!$stmt->fetch()) { setFlash('error','Enrolment record not found.'); header('Location: ' . APP_URL . '/modules/training/index.php?tab=attendance'); exit; }

db()->prepare("UPDATE training_attendance SET attended=1 WHERE id=?")->execute([$id]);
auditLog('training','mark_attended',$id,json_encode(['attended'=>0]),json_encode(['attended'=>1]),'Marked as attended');

setFlash('success','Attendance recorded.');
header('Location: ' . APP_URL . '/modules/training/index.php?tab=attendance');
exit;
