<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('payroll.view', 'view');

$pageTitle  = 'Payroll Dashboard';
$activeMenu = 'payroll';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = max(1, min(12, $month));

// Payroll run for this period
$runStmt = db()->prepare("SELECT * FROM payroll_runs WHERE period_month=? AND period_year=? LIMIT 1");
$runStmt->execute([$month,$year]);
$run = $runStmt->fetch(PDO::FETCH_ASSOC);

// Once an official run exists for this period, scope summaries to payslips
// linked to that run so stray/unlinked payslips (e.g. never attached to a
// run) don't pollute dashboard totals. No run yet -> fall back to the
// period-wide view so payslips can still be drafted before a run exists.
if ($run) {
    $periodWhere  = 'payroll_run_id = ?';
    $periodParams = [$run['id']];
} else {
    $periodWhere  = 'period_month = ? AND period_year = ?';
    $periodParams = [$month, $year];
}

// Summary for selected period
$totalGross = db()->prepare("SELECT SUM(gross_salary) FROM payslips WHERE $periodWhere");
$totalGross->execute($periodParams);
$sumGross = (float)$totalGross->fetchColumn();

$totalNet = db()->prepare("SELECT SUM(net_salary) FROM payslips WHERE $periodWhere");
$totalNet->execute($periodParams);
$sumNet = (float)$totalNet->fetchColumn();

$totalDed = db()->prepare("SELECT SUM(total_deductions) FROM payslips WHERE $periodWhere");
$totalDed->execute($periodParams);
$sumDed = (float)$totalDed->fetchColumn();

$empCount = db()->prepare("SELECT COUNT(*) FROM payslips WHERE $periodWhere");
$empCount->execute($periodParams);
$countEmp = (int)$empCount->fetchColumn();

// Recent payslips
$recentWhere = $run ? 'ps.payroll_run_id = ?' : 'ps.period_month = ? AND ps.period_year = ?';
$recent = db()->prepare("SELECT ps.*, e.first_name, e.last_name, e.employee_number
    FROM payslips ps JOIN employees e ON ps.employee_id=e.id
    WHERE $recentWhere
    ORDER BY e.last_name LIMIT 15");
$recent->execute($periodParams);
$recentSlips = $recent->fetchAll(PDO::FETCH_ASSOC);

$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper">
<div class="content-header">
    <div>
        <h1 class="page-title">Payroll Dashboard</h1>
        <p class="page-sub">Manage payroll runs, payslips, deductions &amp; savings</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <form method="GET" style="display:flex;gap:6px">
            <select name="month" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?=$m?>" <?=$m===$month?'selected':''?>><?=$monthNames[$m]?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                <option value="<?=$y?>" <?=$y===$year?'selected':''?>><?=$y?></option>
                <?php endfor; ?>
            </select>
        </form>
        <a href="<?= APP_URL ?>/modules/payroll/payslips.php" class="btn btn-primary btn-sm">
            Manage Payslips
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
    <div class="stat-card">
        <div class="stat-label">Employees on Payroll</div>
        <div class="stat-value"><?= $countEmp ?></div>
        <div class="stat-sub"><?= $monthNames[$month] . ' ' . $year ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Gross</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?> <?= number_format($sumGross,2) ?></div>
        <div class="stat-sub">Before deductions</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Deductions</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?> <?= number_format($sumDed,2) ?></div>
        <div class="stat-sub">Tax + UIF + other</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Net Pay</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?> <?= number_format($sumNet,2) ?></div>
        <div class="stat-sub">Take-home total</div>
    </div>
</div>

<!-- Payroll Run Status -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">Payroll Run — <?= $monthNames[$month] . ' ' . $year ?></span>
        <?php if (!$run): ?>
        <form method="POST" action="<?= APP_URL ?>/modules/payroll/run_save.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="period_month" value="<?= $month ?>">
            <input type="hidden" name="period_year"  value="<?= $year ?>">
            <button type="submit" class="btn btn-primary btn-sm">Create Run</button>
        </form>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($run): ?>
        <?php
        $runColors=['draft'=>'badge-secondary','processing'=>'badge-warning','finalized'=>'badge-info','published'=>'badge-success'];
        ?>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <span class="badge <?= $runColors[$run['status']] ?? 'badge-secondary' ?>" style="font-size:0.78rem;padding:4px 12px">
                <?= ucfirst($run['status']) ?>
            </span>
            <span style="font-size:0.82rem">Employees: <strong><?= $run['employee_count'] ?></strong></span>
            <span style="font-size:0.82rem">Gross: <strong><?= CURRENCY_SYMBOL ?> <?= number_format($run['total_gross'],2) ?></strong></span>
            <span style="font-size:0.82rem">Net: <strong><?= CURRENCY_SYMBOL ?> <?= number_format($run['total_net'],2) ?></strong></span>
            <?php if ($run['finalized_at']): ?>
            <span style="font-size:0.78rem;color:var(--text-muted)">Finalized: <?= date('d M Y', strtotime($run['finalized_at'])) ?></span>
            <?php endif; ?>
            <?php if (in_array($run['status'],['draft','processing'])): ?>
            <form method="POST" action="<?= APP_URL ?>/modules/payroll/run_finalize.php" style="margin-left:auto">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm"
                    onclick="return confirm('Finalize this payroll run? This will mark all payslips as finalized.')">
                    Finalize Run
                </button>
            </form>
            <?php elseif ($run['status']==='finalized'): ?>
            <form method="POST" action="<?= APP_URL ?>/modules/payroll/run_publish.php" style="margin-left:auto">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                <button type="submit" class="btn btn-primary btn-sm"
                    onclick="return confirm('Publish payslips? Employees will see them in the portal.')">
                    Publish to Portal
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p style="font-size:0.82rem;color:var(--text-muted)">No payroll run created for this period yet. Create one to track the payroll process.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Payslips Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Payslips — <?= $monthNames[$month] . ' ' . $year ?></span>
        <a href="<?= APP_URL ?>/modules/payroll/payslips.php?month=<?=$month?>&year=<?=$year?>" class="btn btn-ghost btn-sm">All →</a>
    </div>
    <?php if ($recentSlips): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Emp No.</th>
                    <th>Gross</th>
                    <th>Deductions</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentSlips as $ps): ?>
                <?php $sc=['draft'=>'badge-secondary','finalized'=>'badge-info','sent'=>'badge-success']; ?>
                <tr>
                    <td><?= e($ps['first_name'].' '.$ps['last_name']) ?></td>
                    <td class="text-muted" style="font-family:monospace;font-size:0.72rem"><?= e($ps['employee_number']) ?></td>
                    <td><?= CURRENCY_SYMBOL ?> <?= number_format($ps['gross_salary'],2) ?></td>
                    <td><?= CURRENCY_SYMBOL ?> <?= number_format($ps['total_deductions']??0,2) ?></td>
                    <td><strong><?= CURRENCY_SYMBOL ?> <?= number_format($ps['net_salary'],2) ?></strong></td>
                    <td><span class="badge <?= $sc[$ps['status']] ?? 'badge-secondary' ?>"><?= ucfirst($ps['status']) ?></span></td>
                    <td>
                        <a href="<?= APP_URL ?>/modules/payroll/payslips.php?edit=<?=$ps['id']?>" class="btn btn-ghost btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">📋</div>
        <div>No payslips for <?= $monthNames[$month] . ' ' . $year ?></div>
        <a href="<?= APP_URL ?>/modules/payroll/payslips.php?new=1&month=<?=$month?>&year=<?=$year?>" class="btn btn-primary btn-sm" style="margin-top:12px">
            Create Payslip
        </a>
    </div>
    <?php endif; ?>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

