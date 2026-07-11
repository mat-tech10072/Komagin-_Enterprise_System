<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('timesheets.approve');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/timesheets/index.php');
    exit;
}

$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['approve','lock','unlock'])) {
    setFlash('error','Invalid action.');
    header('Location: ' . APP_URL . '/modules/timesheets/index.php'); exit;
}

$stmt = db()->prepare("SELECT * FROM attendance WHERE id=?");
$stmt->execute([$id]); $att = $stmt->fetch();
if (!$att) { setFlash('error','Record not found.'); header('Location: ' . APP_URL . '/modules/timesheets/index.php'); exit; }

if ($action === 'approve') {
    if ($att['is_locked']) { setFlash('error','Cannot approve locked record.'); header('Location: ' . APP_URL . '/modules/timesheets/index.php'); exit; }
    db()->prepare("UPDATE attendance SET is_approved=1, approved_by=?, approved_at=NOW() WHERE id=?")
        ->execute([$_SESSION['user_id'], $id]);
    auditLog('timesheets','approve',$id,null,null,'Timesheet approved');
    setFlash('success','Timesheet approved.');

} elseif ($action === 'lock') {
    requirePermission('timesheets.approve', 'approve');
    if (!$att['is_approved']) { setFlash('error','Approve timesheet before locking.'); header('Location: ' . APP_URL . '/modules/timesheets/index.php'); exit; }
    db()->prepare("UPDATE attendance SET is_locked=1 WHERE id=?")->execute([$id]);
    auditLog('timesheets','lock',$id,null,null,'Timesheet locked');
    setFlash('success','Timesheet locked.');

} elseif ($action === 'unlock') {
    requirePermission('timesheets.approve', 'approve');
    db()->prepare("UPDATE attendance SET is_locked=0 WHERE id=?")->execute([$id]);
    auditLog('timesheets','unlock',$id,null,null,'Timesheet unlocked');
    setFlash('success','Timesheet unlocked.');
}

header('Location: ' . APP_URL . '/modules/timesheets/index.php');
exit;
