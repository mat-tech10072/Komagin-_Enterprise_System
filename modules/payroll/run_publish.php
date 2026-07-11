<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('payroll.finalize', 'publish');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/payroll/index.php');
    exit;
}

$runId = (int)($_POST['run_id'] ?? 0);
$stmt  = db()->prepare("SELECT * FROM payroll_runs WHERE id=? LIMIT 1");
$stmt->execute([$runId]);
$run = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$run || $run['status'] !== 'finalized') {
    header('Location: ' . APP_URL . '/modules/payroll/index.php');
    exit;
}

db()->prepare("UPDATE payroll_runs SET status='published' WHERE id=?")->execute([$runId]);

// Mark payslips as sent so employees can see them
db()->prepare("UPDATE payslips SET status='sent' WHERE period_month=? AND period_year=? AND status='finalized'")
    ->execute([$run['period_month'],$run['period_year']]);

auditLog('payroll_runs','publish',$runId,null,null,"Published payroll run {$run['period_month']}/{$run['period_year']} to portal");

// ── Email payslips to employees if notification is configured ─────────
$cfg = getEmailSettings();
if (!empty($cfg['payslip_notify']) && $cfg['payslip_notify'] == '1' && !empty($cfg['smtp_host'])) {
    $slips = db()->prepare("SELECT id FROM payslips WHERE period_month=? AND period_year=? AND status='sent'");
    $slips->execute([$run['period_month'], $run['period_year']]);
    $sent = 0; $failed = 0;
    foreach ($slips->fetchAll(PDO::FETCH_COLUMN) as $psId) {
        $result = sendPayslipEmail((int)$psId);
        $result['success'] ? $sent++ : $failed++;
    }
    auditLog('payroll_runs','email_payslips',$runId,null,null,"Emailed $sent payslips, $failed failed");
    setFlash('info', "$sent payslip email(s) sent" . ($failed ? ", $failed failed (check Email Logs)" : "."));
}

header('Location: ' . APP_URL . '/modules/payroll/index.php?month='.$run['period_month'].'&year='.$run['period_year'].'&success=published');
exit;
