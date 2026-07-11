<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('temp_employees.delete', 'delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/temp_employees/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/temp_employees/index.php'); exit; }

$stmt = db()->prepare("SELECT employee_number, first_name, last_name FROM temp_employees WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) {
    setFlash('error', 'Temporary employee not found.');
    header('Location: ' . APP_URL . '/modules/temp_employees/index.php');
    exit;
}

db()->prepare("DELETE FROM temp_employees WHERE id = ?")->execute([$id]);

auditLog('temp_employees', 'delete', $_SESSION['user_id'], 'temp_employees', $id,
    "Deleted temp employee {$emp['employee_number']} — {$emp['first_name']} {$emp['last_name']}");

setFlash('success', "{$emp['first_name']} {$emp['last_name']} ({$emp['employee_number']}) has been deleted.");
header('Location: ' . APP_URL . '/modules/temp_employees/index.php');
exit;
