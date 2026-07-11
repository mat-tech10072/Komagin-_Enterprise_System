<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('payroll.run', 'create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/payroll/index.php');
    exit;
}

$month = (int)($_POST['period_month'] ?? date('n'));
$year  = (int)($_POST['period_year']  ?? date('Y'));

// Check not already exists
$exists = db()->prepare("SELECT id FROM payroll_runs WHERE period_month=? AND period_year=? LIMIT 1");
$exists->execute([$month,$year]);
if ($exists->fetch()) {
    header('Location: ' . APP_URL . '/modules/payroll/index.php?month='.$month.'&year='.$year.'&err=run_exists');
    exit;
}

// Get totals from payslips
$totals = db()->prepare("SELECT COUNT(*) as cnt, SUM(gross_salary) as gross,
    SUM(net_salary) as net, SUM(total_deductions) as ded
    FROM payslips WHERE period_month=? AND period_year=?");
$totals->execute([$month,$year]);
$t = $totals->fetch(PDO::FETCH_ASSOC);

db()->prepare("INSERT INTO payroll_runs (period_month,period_year,status,total_gross,total_net,total_deductions,employee_count,processed_by)
    VALUES (?,?,'draft',?,?,?,?,?)")
    ->execute([$month,$year,$t['gross']??0,$t['net']??0,$t['ded']??0,$t['cnt']??0,$_SESSION['user_id']]);

auditLog('payroll_runs','create',(int)db()->lastInsertId(),null,null,"Created payroll run {$month}/{$year}");
header('Location: ' . APP_URL . '/modules/payroll/index.php?month='.$month.'&year='.$year.'&success=run_created');
exit;
