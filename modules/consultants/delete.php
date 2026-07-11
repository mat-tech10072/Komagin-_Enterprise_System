<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('consultants.delete', 'delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    header('Location: ' . APP_URL . '/modules/consultants/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/consultants/index.php'); exit; }

$stmt = db()->prepare("SELECT consultant_number, first_name, last_name FROM consultants WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$con = $stmt->fetch();

if (!$con) {
    setFlash('error', 'Consultant not found.');
    header('Location: ' . APP_URL . '/modules/consultants/index.php');
    exit;
}

db()->prepare("DELETE FROM consultants WHERE id=?")->execute([$id]);

setFlash('success', 'Consultant ' . $con['consultant_number'] . ' (' . $con['first_name'] . ' ' . $con['last_name'] . ') deleted.');
header('Location: ' . APP_URL . '/modules/consultants/index.php');
exit;
