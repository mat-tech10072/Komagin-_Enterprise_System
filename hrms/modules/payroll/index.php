<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('payroll.view', 'view');

$pageTitle  = 'Payroll Dashboard';
$activeMenu = 'payroll';

// Selected-period validation (Phase 4 hardening): month/year are always
// resolved to a single valid, in-range pair before anything else on this
// page reads them, and every KPI card, the payroll-run card, and the
// payslip list all read from these same two variables — never a
// separately re-parsed/re-validated copy.
$rawMonth = $_GET['month'] ?? date('n');
$rawYear  = $_GET['year']  ?? date('Y');
$periodCheck = normalizePayrollPeriod($rawMonth, $rawYear);
if ($periodCheck['message'] !== null && !isset($_GET['notice'])) {
    // Redirect to the canonical, corrected URL rather than silently
    // rendering a guessed period — keeps the address bar truthful and
    // avoids repeating the same correction on every subsequent reload.
    setFlash('warning', $periodCheck['message']);
    header('Location: ' . APP_URL . '/modules/payroll/index.php?month=' . $periodCheck['month'] . '&year=' . $periodCheck['year'] . '&notice=period_corrected');
    exit;
}
$month = $periodCheck['month'];
$year  = $periodCheck['year'];

// Authoritative aggregation (Phase 2/3 hardening): one query, one WHERE
// scope, one result row for all four KPI values — see
// getPayrollPeriodSummary()'s doc comment in config/functions.php for the
// exact run/no-run scoping rule and why it was chosen. normalizePayrollSummary()
// inside it guarantees a zero-count result can never carry non-zero totals.
$summary = getPayrollPeriodSummary($month, $year);
$run                  = $summary['run'];
$payrollEmployeeCount = $summary['payslip_count'];
$payrollTotalGross    = $summary['total_gross'];
$payrollTotalDed      = $summary['total_deductions'];
$payrollTotalNet      = $summary['total_net'];

// Runtime diagnostics (Phase 5): one non-sensitive log line per view,
// plus a comparison-only naive total that would previously have been
// used, purely so a future divergence is visible in logs immediately.
logPayrollDashboardDiagnostics($month, $year, $summary, getPayrollPeriodFallbackForDiagnostics($month, $year));

// Recent payslips — reuses the SAME mode/run decision as the summary
// above (never a separately-computed scope) so the list can never
// disagree with the KPI cards about which rows belong to this period.
if ($summary['mode'] === 'payroll_run') {
    $recentWhere  = 'ps.payroll_run_id = ?';
    $recentParams = [(int)$run['id']];
} else {
    $recentWhere  = 'ps.period_month = ? AND ps.period_year = ? AND ps.payroll_run_id IS NULL';
    $recentParams = [$month, $year];
}
$recent = db()->prepare("SELECT ps.*, e.first_name, e.last_name, e.employee_number
    FROM payslips ps JOIN employees e ON ps.employee_id=e.id
    WHERE $recentWhere
    ORDER BY e.last_name LIMIT 15");
$recent->execute($recentParams);
$recentSlips = $recent->fetchAll(PDO::FETCH_ASSOC);

$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

// Phase 6: authenticated payroll pages render real financial totals —
// must never be served from a browser back/forward cache or an
// intermediate proxy after the underlying data changes. See
// sendNoStorePayrollHeaders()'s doc comment for why this is not global.
sendNoStorePayrollHeaders();

include __DIR__ . '/../../includes/header.php';

// Phase 5 (optional admin-only panel): double-gated — an explicit
// environment flag AND super_admin — so this never appears by accident
// in front of a payroll_officer/payroll_manager or on an unconfigured
// production box. Shows the same non-sensitive fields already logged
// above; never renders employee names, bank/tax data, or raw query text.
$showPayrollDiagnostics = getenv('PAYROLL_DIAGNOSTICS') === '1'
    && (($_SESSION['user_role'] ?? '') === 'super_admin');
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

<?php if (isset($_GET['notice']) && $_GET['notice'] === 'period_corrected'): $periodNotice = getFlash(); if ($periodNotice): ?>
<div class="alert alert-warning"><?= e($periodNotice['message']) ?></div>
<?php endif; endif; ?>

<?php if ($summary['warning']): ?>
<div class="alert alert-warning"><?= e($summary['warning']) ?></div>
<?php endif; ?>

<?php if ($showPayrollDiagnostics): ?>
<div class="alert alert-info" style="font-family:monospace;font-size:0.72rem;line-height:1.6;">
    <strong>Payroll diagnostics</strong> (super_admin + PAYROLL_DIAGNOSTICS=1 only)<br>
    build=<?= e(BUILD_ID) ?> |
    period=<?= e(sprintf('%04d-%02d', $year, $month)) ?> |
    run_id=<?= e((string)($run['id'] ?? 'null')) ?> |
    run_status=<?= e($run['status'] ?? 'none') ?> |
    mode=<?= e($summary['mode']) ?> |
    payslip_count=<?= (int)$summary['payslip_count'] ?> |
    invariant=<?= $summary['warning'] ? 'WARNING' : 'ok' ?>
</div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
    <div class="stat-card">
        <div class="stat-label">Employees on Payroll</div>
        <div class="stat-value"><?= $payrollEmployeeCount ?></div>
        <div class="stat-sub"><?= $monthNames[$month] . ' ' . $year ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Gross</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?> <?= number_format($payrollTotalGross,2) ?></div>
        <div class="stat-sub">Before deductions</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Deductions</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?> <?= number_format($payrollTotalDed,2) ?></div>
        <div class="stat-sub">Tax + UIF + other</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Net Pay</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?> <?= number_format($payrollTotalNet,2) ?></div>
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

