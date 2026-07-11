<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('reports.view', 'view');

$pageTitle  = 'Executive Analytics';
$activeMenu = 'reports';

$year  = (int)($_GET['year'] ?? date('Y'));

// ── CSV Export ─────────────────────────────────────────────────────────────
$export = $_GET['export'] ?? '';
if ($export === 'headcount_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="headcount_'.$year.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['Department','Active','Probation','On Leave','Suspended','Total']);
    $rows = db()->prepare("SELECT d.name, e.status, COUNT(*) as cnt
        FROM employees e JOIN departments d ON e.department_id=d.id
        WHERE e.status NOT IN ('archived','terminated','resigned','deceased')
        GROUP BY d.id, d.name, e.status ORDER BY d.name");
    $rows->execute();
    $byDept = [];
    foreach ($rows->fetchAll() as $r) { $byDept[$r['name']][$r['status']] = $r['cnt']; }
    foreach ($byDept as $dept => $statuses) {
        $a=$statuses['active']??0; $p=$statuses['probation']??0;
        $l=$statuses['on_leave']??0; $s=$statuses['suspended']??0;
        fputcsv($out,[$dept,$a,$p,$l,$s,$a+$p+$l+$s]);
    }
    fclose($out); exit;
}

if ($export === 'turnover_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="turnover_'.$year.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['Month','New Hires','Exits','Net Change']);
    $hires = db()->prepare("SELECT MONTH(start_date) as m, COUNT(*) as cnt FROM employees WHERE YEAR(start_date)=? GROUP BY MONTH(start_date)");
    $hires->execute([$year]); $hiresArr = array_column($hires->fetchAll(),'cnt','m');
    $exits = db()->prepare("SELECT MONTH(exit_date) as m, COUNT(*) as cnt FROM employees WHERE YEAR(exit_date)=? GROUP BY MONTH(exit_date)");
    $exits->execute([$year]); $exitsArr = array_column($exits->fetchAll(),'cnt','m');
    $months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    for ($m=1;$m<=12;$m++) {
        $h=$hiresArr[$m]??0; $e=$exitsArr[$m]??0;
        fputcsv($out,[$months[$m],$h,$e,$h-$e]);
    }
    fclose($out); exit;
}

// ── Analytics Data ─────────────────────────────────────────────────────────

// 1. Headcount by department
$headcount = db()->prepare("SELECT d.name as dept, e.status, COUNT(*) as cnt
    FROM employees e JOIN departments d ON e.department_id=d.id
    WHERE e.status NOT IN ('archived','terminated','resigned','deceased')
    GROUP BY d.id, d.name, e.status ORDER BY d.name, e.status");
$headcount->execute();
$hcByDept = [];
$totalActive = 0;
foreach ($headcount->fetchAll() as $r) {
    $hcByDept[$r['dept']][$r['status']] = $r['cnt'];
    if (in_array($r['status'],['active','probation'])) $totalActive += $r['cnt'];
}

// 2. Turnover trend (monthly new hires vs exits, current year)
$hiresQ = db()->prepare("SELECT MONTH(start_date) as m, COUNT(*) as cnt FROM employees WHERE YEAR(start_date)=? GROUP BY MONTH(start_date)");
$hiresQ->execute([$year]); $hiresArr = array_column($hiresQ->fetchAll(),'cnt','m');
$exitsQ = db()->prepare("SELECT MONTH(exit_date) as m, COUNT(*) as cnt FROM employees WHERE YEAR(exit_date)=? GROUP BY MONTH(exit_date)");
$exitsQ->execute([$year]); $exitsArr = array_column($exitsQ->fetchAll(),'cnt','m');

// 3. Leave utilisation by department
$leaveUtil = db()->prepare("SELECT d.name as dept,
    SUM(lb.entitled_days) as entitled,
    SUM(lb.used_days) as used,
    SUM(lb.remaining_days) as remaining
    FROM leave_balances lb
    JOIN employees e ON lb.employee_id=e.id
    JOIN departments d ON e.department_id=d.id
    WHERE lb.year=?
    GROUP BY d.id, d.name ORDER BY d.name");
$leaveUtil->execute([$year]); $leaveData = $leaveUtil->fetchAll();

// 4. Attendance KPIs (current month)
$attMonth = db()->prepare("SELECT
    COUNT(*) as total_records,
    SUM(is_absent) as total_absent,
    SUM(CASE WHEN sign_in IS NOT NULL AND is_absent=0 THEN 1 ELSE 0 END) as total_present,
    SUM(is_late) as total_late,
    ROUND(AVG(total_hours_worked),2) as avg_hours,
    SUM(overtime_hours) as total_ot
    FROM attendance WHERE MONTH(attendance_date)=? AND YEAR(attendance_date)=?");
$attMonth->execute([date('n'), $year]); $attKpi = $attMonth->fetch();

// 5. Payroll summary
$payrollSum = db()->query("SELECT
    SUM(gross_salary) as total_gross,
    SUM(net_salary) as total_net,
    SUM(total_deductions) as total_ded,
    COUNT(DISTINCT employee_id) as emp_count
    FROM payslips WHERE period_year=".intval($year))->fetch();

// 6. Recruitment pipeline
$recPipeline = db()->query("SELECT status, COUNT(*) as cnt FROM recruitment_applications GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// 7. Training stats
$trainStats = db()->query("SELECT
    COUNT(DISTINCT tp.id) as programs,
    COUNT(ta.id) as enrollments,
    SUM(ta.attended) as completed
    FROM training_programs tp LEFT JOIN training_attendance ta ON ta.training_id=tp.id
    WHERE YEAR(IFNULL(tp.end_date,NOW()))=".intval($year))->fetch();

// 8. Document generation stats
$docStats = db()->query("SELECT status, COUNT(*) as cnt FROM generated_documents GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// ── Months for charts ──────────────────────────────────────────────────────
$monthNames = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$_hiresJson = json_encode(array_map(fn($m)=>$hiresArr[$m]??0, range(1,12)));
$_exitsJson = json_encode(array_map(fn($m)=>$exitsArr[$m]??0, range(1,12)));
$_monthsJson = json_encode(array_slice($monthNames,1));
$_deptLabels = json_encode(array_keys($hcByDept));
$_deptActive = json_encode(array_map(fn($s)=>($s['active']??0)+($s['probation']??0), $hcByDept));

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/reports/index.php">Reports</a></li>
                <li class="breadcrumb-item active">Executive Analytics</li>
            </ol>
        </nav>
        <h1 class="page-title">Executive Analytics Dashboard</h1>
        <p class="page-subtitle">Full-organisation workforce intelligence — <?= $year ?></p>
    </div>
    <div class="page-actions">
        <select onchange="window.location=this.value" style="padding:6px 12px;border-radius:6px;border:1px solid var(--border);font-size:0.78rem;background:var(--bg-card);">
            <?php for ($y=date('Y');$y>=date('Y')-4;$y--): ?>
            <option value="?year=<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <a href="?year=<?= $year ?>&export=headcount_csv" class="btn btn-secondary btn-sm">Headcount CSV</a>
        <a href="?year=<?= $year ?>&export=turnover_csv" class="btn btn-secondary btn-sm">Turnover CSV</a>
    </div>
</div>

<!-- Top KPIs -->
<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:12px;margin-bottom:24px;">
    <?php $kpis = [
        ['Active Staff',     $totalActive],
        ['Attendance Rate',  $attKpi['total_records']?round(($attKpi['total_present']/$attKpi['total_records'])*100).'%':'—'],
        ['Avg Hours/Day',    $attKpi['avg_hours'] ? number_format($attKpi['avg_hours'],1).'h' : '—'],
        ['Total OT (Month)', $attKpi['total_ot'] ? number_format($attKpi['total_ot'],1).'h' : '0h'],
        ['YTD Payroll',      canViewSalaryData() ? ($payrollSum['total_gross'] ? CURRENCY_SYMBOL . " " . number_format($payrollSum['total_gross'],0) : '—') : maskSalary(0)],
        ['Open Vacancies',   $recPipeline['open'] ?? 0],
        ['Docs Generated',   array_sum($docStats)],
    ]; ?>
    <?php foreach ($kpis as [$l,$v]): ?>
    <div class="kpi-card">
        <div class="kpi-card-label"><?= $l ?></div>
        <div class="kpi-card-value" style="font-size:1.3rem;"><?= $v ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">

    <!-- Headcount by Department -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Headcount by Department</span>
        </div>
        <div style="padding:16px;">
            <canvas id="deptChart" height="180"></canvas>
        </div>
    </div>

    <!-- Turnover Trend -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Recruitment Trend <?= $year ?></span>
        </div>
        <div style="padding:16px;">
            <canvas id="turnoverChart" height="180"></canvas>
        </div>
    </div>

</div>

<!-- Detailed Tables Row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">

    <!-- Headcount Table -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Headcount Breakdown</span>
            <span style="font-size:0.72rem;color:var(--text-muted);">Active + Probation | Excludes exits</span>
        </div>
        <div class="table-wrapper" style="border:none;">
            <table class="table">
                <thead><tr><th>Department</th><th>Active</th><th>Probation</th><th>Leave</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($hcByDept as $dept => $statuses): ?>
                <?php $tot = array_sum($statuses); ?>
                <tr>
                    <td style="font-weight:600;font-size:0.8rem;"><?= e($dept) ?></td>
                    <td style="text-align:center;"><?= $statuses['active'] ?? 0 ?></td>
                    <td style="text-align:center;color:var(--warning);"><?= $statuses['probation'] ?? 0 ?></td>
                    <td style="text-align:center;color:var(--info);"><?= $statuses['on_leave'] ?? 0 ?></td>
                    <td style="text-align:center;font-weight:700;"><?= $tot ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($hcByDept)): ?>
                <tr><td colspan="5" class="empty-state">No employee data</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Leave Utilisation -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Leave Utilisation <?= $year ?></span>
        </div>
        <div class="table-wrapper" style="border:none;">
            <table class="table">
                <thead><tr><th>Department</th><th>Entitled</th><th>Used</th><th>Remaining</th><th>Usage %</th></tr></thead>
                <tbody>
                <?php foreach ($leaveData as $ld):
                    $pct = $ld['entitled'] > 0 ? round($ld['used']/$ld['entitled']*100) : 0; ?>
                <tr>
                    <td style="font-weight:600;font-size:0.8rem;"><?= e($ld['dept']) ?></td>
                    <td style="text-align:center;"><?= nf($ld['entitled']) ?></td>
                    <td style="text-align:center;"><?= nf($ld['used']) ?></td>
                    <td style="text-align:center;"><?= nf($ld['remaining']) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="flex:1;height:5px;background:var(--bg);border-radius:3px;overflow:hidden;">
                                <div style="width:<?= $pct ?>%;height:100%;background:<?= $pct>=80?'var(--danger)':($pct>=50?'var(--warning)':'var(--success)') ?>;border-radius:3px;"></div>
                            </div>
                            <span style="font-size:0.7rem;font-weight:600;min-width:30px;"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($leaveData)): ?>
                <tr><td colspan="5" class="empty-state">No leave data for <?= $year ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Third Row -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px;">

    <!-- Payroll Summary -->
    <div class="card">
        <div class="card-header"><span class="card-title">Payroll Summary <?= $year ?></span></div>
        <div class="card-body">
            <?php
            // Aggregate payroll cost is still payroll-sensitive data even though no
            // single employee's salary is named — gate it the same way individual
            // salary fields are gated everywhere else in the app (payroll.view).
            $canPay = canViewSalaryData();
            $payMeta = [
                ['Total Gross',       $canPay ? CURRENCY_SYMBOL . " " . nf($payrollSum['total_gross']??0,2) : maskSalary(0)],
                ['Total Net',         $canPay ? CURRENCY_SYMBOL . " " . nf($payrollSum['total_net']??0,2)   : maskSalary(0)],
                ['Total Deductions',  $canPay ? CURRENCY_SYMBOL . " " . nf($payrollSum['total_ded']??0,2)   : maskSalary(0)],
                ['Employees on Payroll', nf($payrollSum['emp_count']??0)],
            ]; ?>
            <?php foreach ($payMeta as [$l,$v]): ?>
            <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-light);font-size:0.78rem;">
                <span style="color:var(--text-muted);"><?= $l ?></span>
                <span style="font-weight:600;"><?= $v ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recruitment Pipeline -->
    <div class="card">
        <div class="card-header"><span class="card-title">Recruitment Pipeline</span></div>
        <div class="card-body">
            <?php $pipelineLabels = ['submitted'=>'Submitted','reviewing'=>'Reviewing','shortlisted'=>'Shortlisted','interview_scheduled'=>'Interviews','selected'=>'Selected','rejected'=>'Rejected']; ?>
            <?php foreach ($pipelineLabels as $k=>$l): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border-light);font-size:0.78rem;">
                <span style="color:var(--text-muted);"><?= $l ?></span>
                <span class="badge badge-secondary"><?= $recPipeline[$k]??0 ?></span>
            </div>
            <?php endforeach; ?>
            <div style="margin-top:8px;font-size:0.72rem;color:var(--text-muted);">Total applications: <?= array_sum($recPipeline) ?></div>
        </div>
    </div>

    <!-- Training + Documents -->
    <div>
        <div class="card" style="margin-bottom:12px;">
            <div class="card-header"><span class="card-title">Training <?= $year ?></span></div>
            <div class="card-body">
                <?php $trainMeta = [
                    ['Programs',    $trainStats['programs']??0],
                    ['Enrollments', $trainStats['enrollments']??0],
                    ['Completed',   $trainStats['completed']??0],
                ]; ?>
                <?php foreach ($trainMeta as [$l,$v]): ?>
                <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:0.78rem;">
                    <span style="color:var(--text-muted);"><?= $l ?></span>
                    <span style="font-weight:600;"><?= $v ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><span class="card-title">Documents Generated</span></div>
            <div class="card-body">
                <?php $docLabels = ['draft'=>'Drafts','pending_approval'=>'Pending','approved'=>'Approved','issued'=>'Issued','rejected'=>'Rejected']; ?>
                <?php foreach ($docLabels as $k=>$l): ?>
                <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.78rem;">
                    <span style="color:var(--text-muted);"><?= $l ?></span>
                    <span style="font-weight:600;"><?= $docStats[$k]??0 ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php
$_deptColors = '["#1D4ED8","#22C55E","#F59E0B","#EF4444","#3B82F6","#8B5CF6","#EC4899"]';
?>
<script>
// Department headcount chart
new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: <?= $_deptLabels ?>,
        datasets: [{
            label: 'Headcount',
            data: <?= $_deptActive ?>,
            backgroundColor: <?= $_deptColors ?>.slice(0, <?= json_encode(count($hcByDept)) ?>),
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10, family:'Inter' } } },
            y: { beginAtZero: true, grid: { color:'#F1F5F9' }, ticks: { font: { size: 10, family:'Inter' } } }
        }
    }
});

// Turnover trend chart
new Chart(document.getElementById('turnoverChart'), {
    type: 'line',
    data: {
        labels: <?= $_monthsJson ?>,
        datasets: [
            {
                label: 'New Hires',
                data: <?= $_hiresJson ?>,
                borderColor: '#22C55E', backgroundColor: 'rgba(34,197,94,.08)',
                tension: 0.3, fill: true, pointRadius: 4,
            },
            {
                label: 'Exits',
                data: <?= $_exitsJson ?>,
                borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,.08)',
                tension: 0.3, fill: true, pointRadius: 4,
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position:'top', labels:{ font:{ size:10, family:'Inter' }, boxWidth:10 } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10, family:'Inter' } } },
            y: { beginAtZero: true, grid: { color:'#F1F5F9' }, ticks: { stepSize: 1, font: { size:10, family:'Inter' } } }
        }
    }
});
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>

