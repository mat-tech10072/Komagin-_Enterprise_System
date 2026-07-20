<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('payroll.reports', 'view');

$pageTitle  = 'Payroll Reports';
$activeMenu = 'payroll_reports';

$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
if (!isValidPayrollYear($year)) { $year = (int)date('Y'); }
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0; // 0 = all months

// Same run-scoping rule as getPayrollPeriodSummary() in config/functions.php
// (kept in sync manually here since this query aggregates a whole year of
// mixed run/no-run months in one grouped statement, rather than one period
// at a time): a payslip counts toward its period's totals when either —
//   (a) that period's run has left draft/processing (finalized/published)
//       and the payslip is linked to it, or
//   (b) the period has no run yet, or its run is still draft/processing,
//       and the payslip is unlinked.
// Originally this only checked "(pr.id IS NULL OR ps.payroll_run_id =
// pr.id)", which correctly excluded a stray unlinked payslip once a run
// was finalized, but incorrectly excluded genuine draft payslips for a
// period whose run had just been created and not yet finalized (nothing
// is linked to a run until run_finalize.php's backfill runs) — found
// while designing the payroll dashboard hardening, not a change to any
// previously-tested behaviour. payroll_runs has a UNIQUE(period_month,
// period_year) key so this join never duplicates rows.
$runScope = "LEFT JOIN payroll_runs pr ON pr.period_month=ps.period_month AND pr.period_year=ps.period_year
    WHERE ps.period_year=? AND (
        (ps.payroll_run_id IS NULL AND (pr.id IS NULL OR pr.status NOT IN ('finalized','published')))
        OR (pr.status IN ('finalized','published') AND ps.payroll_run_id = pr.id)
    )";

// Monthly summary for the year
$monthlyStmt = db()->prepare("SELECT ps.period_month as period_month, COUNT(*) as emp_count,
    SUM(ps.gross_salary) as total_gross, SUM(ps.net_salary) as total_net,
    SUM(ps.total_deductions) as total_ded, SUM(ps.tax_amount) as total_tax,
    SUM(ps.uif_employee) as total_uif
    FROM payslips ps $runScope
    GROUP BY ps.period_month ORDER BY ps.period_month");
$monthlyStmt->execute([$year]);
$monthly = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

// Annual totals
$annualStmt = db()->prepare("SELECT COUNT(DISTINCT ps.employee_id) as unique_employees,
    SUM(ps.gross_salary) as total_gross, SUM(ps.net_salary) as total_net,
    SUM(ps.total_deductions) as total_ded, SUM(ps.tax_amount) as total_tax,
    SUM(ps.uif_employee) as total_uif, SUM(ps.uif_employer) as total_uif_er,
    SUM(ps.overtime_amount) as total_ot
    FROM payslips ps $runScope");
$annualStmt->execute([$year]);
$annual = $annualStmt->fetch(PDO::FETCH_ASSOC);

// Deduction type summary
$dedStmt = db()->prepare("SELECT pd.deduction_type, COUNT(*) as count,
    SUM(pd.amount) as total_amount
    FROM payroll_deductions pd WHERE pd.is_active=1
    GROUP BY pd.deduction_type ORDER BY total_amount DESC");
$dedStmt->execute();
$dedSummary = $dedStmt->fetchAll(PDO::FETCH_ASSOC);

// Top earners for year
$topEarners = db()->prepare("SELECT e.first_name, e.last_name, e.employee_number,
    d.name as dept_name, SUM(ps.net_salary) as annual_net, SUM(ps.gross_salary) as annual_gross
    FROM payslips ps JOIN employees e ON ps.employee_id=e.id
    LEFT JOIN departments d ON e.department_id=d.id
    $runScope
    GROUP BY ps.employee_id ORDER BY annual_gross DESC LIMIT 10");
$topEarners->execute([$year]);
$topEarnersList = $topEarners->fetchAll(PDO::FETCH_ASSOC);

$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$typeLabels = ['tax'=>'Income Tax','uif'=>'UIF','pension'=>'Pension','provident'=>'Provident Fund',
    'medical_aid'=>'Medical Aid','loan'=>'Loan','garnishee'=>'Garnishee','other'=>'Other'];

// CSV export
if (isset($_GET['export']) && $_GET['export']==='csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payroll_report_'.$year.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['Month','Employees','Gross','Net Pay','Deductions','Tax','UIF']);
    foreach ($monthly as $m) {
        fputcsv($out,[
            $monthNames[$m['period_month']].' '.$year,
            $m['emp_count'],
            nf($m['total_gross'],2),
            nf($m['total_net'],2),
            nf($m['total_ded'],2),
            number_format($m['total_tax']??0,2),
            number_format($m['total_uif']??0,2),
        ]);
    }
    fclose($out);
    exit;
}

// Phase 6 hardening: this page renders real financial totals — must never
// be served stale from a browser back/forward cache or an intermediate
// proxy. See sendNoStorePayrollHeaders()'s doc comment in
// config/functions.php for why this is not applied globally in header.php.
sendNoStorePayrollHeaders();

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper">
<div class="content-header">
    <div>
        <h1 class="page-title">Payroll Reports</h1>
        <p class="page-sub">Annual and monthly payroll summaries</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <form method="GET" style="display:flex;gap:6px">
            <select name="year" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php for($y=date('Y');$y>=date('Y')-5;$y--): ?>
                <option value="<?=$y?>" <?=$y===$year?'selected':''?>><?=$y?></option>
                <?php endfor; ?>
            </select>
        </form>
        <a href="?year=<?=$year?>&export=csv" class="btn btn-secondary btn-sm">
            Export CSV
        </a>
    </div>
</div>

<!-- Annual KPIs -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
    <div class="stat-card">
        <div class="stat-label">Employees Paid</div>
        <div class="stat-value"><?= $annual['unique_employees'] ?? 0 ?></div>
        <div class="stat-sub">Unique in <?= $year ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Gross (<?=$year?>)</div>
        <div class="stat-value"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($annual['total_gross']??0,0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Deductions</div>
        <div class="stat-value"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($annual['total_ded']??0,0) ?></div>
        <div class="stat-sub">Tax + UIF + other</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Net Pay</div>
        <div class="stat-value"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($annual['total_net']??0,0) ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
    <!-- Monthly Breakdown -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Monthly Breakdown — <?=$year?></span>
        </div>
        <?php if ($monthly): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Employees</th>
                        <th>Gross</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Tax</th>
                        <th>UIF</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $runGross=0; $runNet=0;
                    foreach($monthly as $m):
                        $runGross += $m['total_gross'];
                        $runNet   += $m['total_net'];
                    ?>
                    <tr>
                        <td><strong><?= $monthNames[$m['period_month']] ?></strong></td>
                        <td><?= $m['emp_count'] ?></td>
                        <td><?= HRMS_CURRENCY_SYMBOL ?> <?= nf($m['total_gross'],2) ?></td>
                        <td><?= HRMS_CURRENCY_SYMBOL ?> <?= nf($m['total_ded'],2) ?></td>
                        <td><strong><?= HRMS_CURRENCY_SYMBOL ?> <?= nf($m['total_net'],2) ?></strong></td>
                        <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($m['total_tax']??0,2) ?></td>
                        <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($m['total_uif']??0,2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:var(--bg);font-weight:700">
                        <td>TOTAL</td>
                        <td>—</td>
                        <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($annual['total_gross']??0,2) ?></td>
                        <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($annual['total_ded']??0,2) ?></td>
                        <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($annual['total_net']??0,2) ?></td>
                        <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($annual['total_tax']??0,2) ?></td>
                        <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($annual['total_uif']??0,2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><div>No payroll data for <?=$year?></div></div>
        <?php endif; ?>
    </div>

    <!-- Deduction Summary -->
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Active Deduction Types</span></div>
            <div class="card-body" style="padding:0">
                <?php foreach($dedSummary as $d): ?>
                <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between">
                    <span style="font-size:0.78rem"><?= $typeLabels[$d['deduction_type']]??$d['deduction_type'] ?></span>
                    <div style="text-align:right">
                        <div style="font-size:0.78rem;font-weight:600"><?= HRMS_CURRENCY_SYMBOL ?> <?= nf($d['total_amount'],2) ?></div>
                        <div style="font-size:0.67rem;color:var(--text-muted)"><?= $d['count'] ?> records</div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (!$dedSummary): ?>
                <div style="padding:16px;font-size:0.78rem;color:var(--text-muted)">No active deductions</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Savings Summary -->
        <?php
        $savSummary = db()->query("SELECT savings_type, COUNT(*) as count, SUM(current_balance) as total
            FROM employee_savings GROUP BY savings_type ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
        $typeLabelsS = ['pension'=>'Pension','provident'=>'Provident','medical_aid'=>'Medical Aid',
            'funeral'=>'Funeral','savings'=>'Savings','other'=>'Other'];
        ?>
        <div class="card">
            <div class="card-header"><span class="card-title">Savings Funds Overview</span></div>
            <div class="card-body" style="padding:0">
                <?php foreach($savSummary as $s): ?>
                <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between">
                    <span style="font-size:0.78rem"><?= $typeLabelsS[$s['savings_type']]??$s['savings_type'] ?></span>
                    <div style="text-align:right">
                        <div style="font-size:0.78rem;font-weight:600"><?= HRMS_CURRENCY_SYMBOL ?> <?= nf($s['total'],2) ?></div>
                        <div style="font-size:0.67rem;color:var(--text-muted)"><?= $s['count'] ?> employees</div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (!$savSummary): ?>
                <div style="padding:16px;font-size:0.78rem;color:var(--text-muted)">No savings records</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Earners -->
<?php if ($topEarnersList): ?>
<div class="card" style="margin-top:16px">
    <div class="card-header"><span class="card-title">Top Earners — <?=$year?> (Gross)</span></div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>Employee</th><th>Department</th><th>Annual Gross</th><th>Annual Net</th></tr>
            </thead>
            <tbody>
                <?php foreach($topEarnersList as $t): ?>
                <tr>
                    <td>
                        <div style="font-weight:600"><?= e($t['first_name'].' '.$t['last_name']) ?></div>
                        <div style="font-size:0.7rem;color:var(--text-muted)"><?= e($t['employee_number']) ?></div>
                    </td>
                    <td><?= e($t['dept_name']??'—') ?></td>
                    <td><?= HRMS_CURRENCY_SYMBOL ?> <?= nf($t['annual_gross'],2) ?></td>
                    <td><strong><?= HRMS_CURRENCY_SYMBOL ?> <?= nf($t['annual_net'],2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

