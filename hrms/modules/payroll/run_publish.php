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

// KOM-030: the SELECT check above and this UPDATE were two separate
// statements with no lock between them — two concurrent publish requests
// (double-click, two admin tabs) could both pass the check before either
// commits, then both proceed to the email-sending block below and send
// every employee's payslip twice. Making the status transition itself the
// atomic guard (UPDATE ... WHERE status='finalized', check rowCount) means
// only the request that actually wins the race proceeds past this point;
// MySQL/InnoDB guarantees this single-row UPDATE is atomic against
// concurrent transactions without needing an explicit lock or transaction
// block.
$claimed = db()->prepare("UPDATE payroll_runs SET status='published' WHERE id=? AND status='finalized'");
$claimed->execute([$runId]);
if ($claimed->rowCount() === 0) {
    // Another concurrent request already published this run — do not
    // resend emails or duplicate the audit trail.
    header('Location: ' . APP_URL . '/modules/payroll/index.php?month='.$run['period_month'].'&year='.$run['period_year'].'&success=published');
    exit;
}

// Mark payslips belonging to this run as sent so employees can see them.
// Scoped to payroll_run_id (not period-wide) so a stray/unlinked payslip
// for the same period is never swept into this run's publish/email batch.
db()->prepare("UPDATE payslips SET status='sent' WHERE payroll_run_id=? AND status='finalized'")
    ->execute([$runId]);

auditLog('payroll_runs','publish',$runId,null,null,"Published payroll run {$run['period_month']}/{$run['period_year']} to portal");

// ── Email payslips to employees if notification is configured ─────────
$cfg = getEmailSettings();
if (!empty($cfg['payslip_notify']) && $cfg['payslip_notify'] == '1' && !empty($cfg['smtp_host'])) {
    $slips = db()->prepare("SELECT id FROM payslips WHERE payroll_run_id=? AND status='sent'");
    $slips->execute([$runId]);
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
