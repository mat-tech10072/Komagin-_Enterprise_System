<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('payroll.finalize', 'approve');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/payroll/index.php');
    exit;
}

$runId = (int)($_POST['run_id'] ?? 0);
$stmt  = db()->prepare("SELECT * FROM payroll_runs WHERE id=? LIMIT 1");
$stmt->execute([$runId]);
$run = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$run || !in_array($run['status'],['draft','processing'])) {
    header('Location: ' . APP_URL . '/modules/payroll/index.php');
    exit;
}

// Backfill: attach any draft payslip for this period that was never linked
// to a run (e.g. created manually after the run was set up) so the run
// becomes authoritative over it. Already-finalized/sent unlinked payslips
// are deliberately left alone — reclassifying settled data isn't safe to
// do silently; those need an explicit reviewed repair instead.
db()->prepare("UPDATE payslips SET payroll_run_id=? WHERE period_month=? AND period_year=? AND payroll_run_id IS NULL AND status='draft'")
    ->execute([$runId, $run['period_month'], $run['period_year']]);

// Re-calculate totals from payslips actually linked to this run
$totals = db()->prepare("SELECT COUNT(*) as cnt, SUM(gross_salary) as gross,
    SUM(net_salary) as net, SUM(total_deductions) as ded
    FROM payslips WHERE payroll_run_id=?");
$totals->execute([$runId]);
$t = $totals->fetch(PDO::FETCH_ASSOC);

// KOM-030: same check-then-act race as run_publish.php — make the status
// transition itself the atomic guard against a concurrent finalize request
// recalculating and overwriting totals from a second, possibly different
// snapshot.
$claimed = db()->prepare("UPDATE payroll_runs SET status='finalized',total_gross=?,total_net=?,
    total_deductions=?,employee_count=?,finalized_at=NOW() WHERE id=? AND status IN ('draft','processing')");
$claimed->execute([$t['gross']??0,$t['net']??0,$t['ded']??0,$t['cnt']??0,$runId]);
if ($claimed->rowCount() === 0) {
    header('Location: ' . APP_URL . '/modules/payroll/index.php?month='.$run['period_month'].'&year='.$run['period_year'].'&success=finalized');
    exit;
}

// Mark payslips belonging to this run as finalized
db()->prepare("UPDATE payslips SET status='finalized' WHERE payroll_run_id=? AND status='draft'")
    ->execute([$runId]);

auditLog('payroll_runs','finalize',$runId,null,null,"Finalized payroll run {$run['period_month']}/{$run['period_year']}");
header('Location: ' . APP_URL . '/modules/payroll/index.php?month='.$run['period_month'].'&year='.$run['period_year'].'&success=finalized');
exit;
