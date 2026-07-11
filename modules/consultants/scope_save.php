<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('consultants.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    header('Location: ' . APP_URL . '/modules/consultants/index.php');
    exit;
}

$action       = $_POST['action']       ?? '';
$consultantId = (int)($_POST['consultant_id'] ?? 0);
$scopeId      = (int)($_POST['scope_id']      ?? 0);
$redirect     = APP_URL . '/modules/consultants/view.php?id=' . $consultantId;

if ($action === 'add' && $consultantId) {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = in_array($_POST['priority']??'', ['low','normal','high','urgent']) ? $_POST['priority'] : 'normal';
    $dueDate     = $_POST['due_date'] ?: null;

    if (!$title) { setFlash('error','Title is required.'); header('Location: '.$redirect); exit; }

    $maxOrder = db()->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM consultant_scopes WHERE consultant_id=?");
    $maxOrder->execute([$consultantId]);
    $order = (int)$maxOrder->fetchColumn();

    db()->prepare("INSERT INTO consultant_scopes (consultant_id, title, description, priority, due_date, sort_order) VALUES (?,?,?,?,?,?)")
        ->execute([$consultantId, $title, $description ?: null, $priority, $dueDate, $order]);

    setFlash('success', 'Scope item added.');
}

elseif ($action === 'edit' && $scopeId && $consultantId) {
    $title        = trim($_POST['title']        ?? '');
    $description  = trim($_POST['description']  ?? '');
    $priority     = in_array($_POST['priority']??'', ['low','normal','high','urgent']) ? $_POST['priority'] : 'normal';
    $status       = in_array($_POST['status']??'', ['pending','in_progress','completed','on_hold']) ? $_POST['status'] : 'pending';
    $completion   = min(100, max(0, (int)($_POST['completion_pct'] ?? 0)));
    $dueDate      = $_POST['due_date'] ?: null;
    $hrNotes      = trim($_POST['hr_notes']     ?? '');

    if (!$title) { setFlash('error','Title is required.'); header('Location: '.$redirect); exit; }

    $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
    if ($status === 'completed') $completion = 100;

    db()->prepare("UPDATE consultant_scopes SET title=?, description=?, priority=?, status=?, completion_pct=?, due_date=?, hr_notes=?, completed_at=? WHERE id=? AND consultant_id=?")
        ->execute([$title, $description ?: null, $priority, $status, $completion, $dueDate, $hrNotes ?: null, $completedAt, $scopeId, $consultantId]);

    setFlash('success', 'Scope item updated.');
}

elseif ($action === 'delete' && $scopeId && $consultantId) {
    if (!canDelete('consultants.delete')) { setFlash('error','Permission denied.'); header('Location: '.$redirect); exit; }
    db()->prepare("DELETE FROM consultant_scopes WHERE id=? AND consultant_id=?")->execute([$scopeId, $consultantId]);
    setFlash('success', 'Scope item deleted.');
}

header('Location: ' . $redirect);
exit;
