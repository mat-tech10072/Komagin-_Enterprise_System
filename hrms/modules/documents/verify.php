<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('documents.verify', 'approve');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/documents/index.php'); exit;
}

$docId = (int)($_POST['doc_id'] ?? 0);
if (!$docId) { header('Location: ' . APP_URL . '/modules/documents/index.php'); exit; }

$doc = db()->prepare("SELECT * FROM employee_documents WHERE id=? AND is_deleted=0");
$doc->execute([$docId]);
$document = $doc->fetch();

if (!$document) {
    setFlash('error','Document not found.');
    header('Location: ' . APP_URL . '/modules/documents/index.php'); exit;
}

db()->prepare("UPDATE employee_documents SET is_verified=1, verified_by=?, verified_at=NOW() WHERE id=?")
    ->execute([$_SESSION['user_id'], $docId]);

auditLog('documents','verify',$docId,json_encode(['verified'=>0]),json_encode(['verified'=>1,'by'=>$_SESSION['user_id']]));

setFlash('success','Document verified successfully.');
header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$document['employee_id'].'&tab=documents');
exit;
