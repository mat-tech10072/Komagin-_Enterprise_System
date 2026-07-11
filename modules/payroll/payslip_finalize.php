<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('payroll.finalize', 'publish');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/payroll/payslips.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
db()->prepare("UPDATE payslips SET status='finalized' WHERE id=? AND status='draft'")->execute([$id]);
header('Location: ' . APP_URL . '/modules/payroll/payslips.php?success=finalized');
exit;
