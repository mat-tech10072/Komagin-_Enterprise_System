<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';
epRequireLogin();

$emp   = epCurrentEmployee();
$empId = $emp['id'];

$stmt = db()->prepare("SELECT * FROM employee_savings WHERE employee_id=? ORDER BY savings_type");
$stmt->execute([$empId]);
$savings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = [
    'pension'     => 'Pension Fund',
    'provident'   => 'Provident Fund',
    'medical_aid' => 'Medical Aid',
    'funeral'     => 'Funeral Cover',
    'savings'     => 'Savings Plan',
    'other'       => 'Other Fund',
];
$typeColors = [
    'pension'   => '#1D4ED8',
    'provident' => '#16A34A',
    'medical_aid'=> '#0284C7',
    'funeral'   => '#D97706',
    'savings'   => '#7C3AED',
    'other'     => '#64748B',
];

$totalEmployee = array_sum(array_column($savings, 'total_employee_contrib'));
$totalEmployer = array_sum(array_column($savings, 'total_employer_contrib'));
$totalBalance  = array_sum(array_column($savings, 'current_balance'));

epLayoutStart('Savings & Benefits', 'savings');
?>

<div class="page-header">
    <div>
        <div class="page-title">Savings &amp; Benefits</div>
        <div class="page-sub">Live progress of your registered funds and contributions</div>
    </div>
</div>

<!-- Summary KPIs -->
<div class="ep-kpi-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="ep-kpi green">
        <div class="ep-kpi-label">Total Balance</div>
        <div class="ep-kpi-value"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($totalBalance, 2) ?></div>
        <div class="ep-kpi-sub">All funds combined</div>
    </div>
    <div class="ep-kpi info">
        <div class="ep-kpi-label">Your Contributions</div>
        <div class="ep-kpi-value"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($totalEmployee, 2) ?></div>
        <div class="ep-kpi-sub">Total employee contributions</div>
    </div>
    <div class="ep-kpi amber">
        <div class="ep-kpi-label">Employer Contributions</div>
        <div class="ep-kpi-value"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($totalEmployer, 2) ?></div>
        <div class="ep-kpi-sub">Total employer contributions</div>
    </div>
</div>

<?php if ($savings): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <?php foreach ($savings as $sv): ?>
    <?php
    $pct = $sv['target_amount'] > 0
        ? min(100, ($sv['current_balance'] / $sv['target_amount']) * 100)
        : 0;
    $color = $typeColors[$sv['savings_type']] ?? '#1D4ED8';
    $label = $typeLabels[$sv['savings_type']] ?? 'Fund';
    ?>
    <div class="savings-card">
        <div class="savings-header" style="background:linear-gradient(135deg,<?= $color ?> 0%,<?= $color ?>cc 100%)">
            <div class="savings-title"><?= $label ?><?= $sv['fund_name'] ? ' — ' . htmlspecialchars($sv['fund_name']) : '' ?></div>
            <div class="savings-amount"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($sv['current_balance'], 2) ?></div>
            <?php if ($sv['target_amount'] > 0): ?>
            <div style="font-size:0.72rem;opacity:.75;margin-top:4px">Target: R <?= number_format($sv['target_amount'],2) ?></div>
            <?php endif; ?>
        </div>
        <div class="savings-body">
            <!-- Progress bar -->
            <?php if ($sv['target_amount'] > 0): ?>
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                    <span style="font-size:0.7rem;color:var(--text-muted)">Progress to target</span>
                    <span style="font-size:0.7rem;font-weight:700"><?= number_format($pct,1) ?>%</span>
                </div>
                <div class="progress-wrap">
                    <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                </div>
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div>
                    <div style="font-size:0.65rem;text-transform:uppercase;font-weight:600;color:var(--text-muted)">Your Rate</div>
                    <div style="font-size:0.82rem;font-weight:600"><?= $sv['employee_rate_pct'] ?>% / <?= $sv['monthly_employee_contrib'] > 0 ? HRMS_CURRENCY_SYMBOL . " " . number_format($sv['monthly_employee_contrib'],2).'/mo' : '—' ?></div>
                </div>
                <div>
                    <div style="font-size:0.65rem;text-transform:uppercase;font-weight:600;color:var(--text-muted)">Employer Rate</div>
                    <div style="font-size:0.82rem;font-weight:600"><?= $sv['employer_rate_pct'] ?>% / <?= $sv['monthly_employer_contrib'] > 0 ? HRMS_CURRENCY_SYMBOL . " " . number_format($sv['monthly_employer_contrib'],2).'/mo' : '—' ?></div>
                </div>
                <div>
                    <div style="font-size:0.65rem;text-transform:uppercase;font-weight:600;color:var(--text-muted)">Total Yours</div>
                    <div style="font-size:0.82rem;font-weight:600"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($sv['total_employee_contrib'],2) ?></div>
                </div>
                <div>
                    <div style="font-size:0.65rem;text-transform:uppercase;font-weight:600;color:var(--text-muted)">Total Employer</div>
                    <div style="font-size:0.82rem;font-weight:600"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($sv['total_employer_contrib'],2) ?></div>
                </div>
                <?php if ($sv['start_date']): ?>
                <div>
                    <div style="font-size:0.65rem;text-transform:uppercase;font-weight:600;color:var(--text-muted)">Start Date</div>
                    <div style="font-size:0.82rem;font-weight:600"><?= date('d M Y', strtotime($sv['start_date'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($sv['projected_end_date']): ?>
                <div>
                    <div style="font-size:0.65rem;text-transform:uppercase;font-weight:600;color:var(--text-muted)">Projected End</div>
                    <div style="font-size:0.82rem;font-weight:600"><?= date('d M Y', strtotime($sv['projected_end_date'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($sv['notes']): ?>
            <div style="margin-top:10px;font-size:0.72rem;color:var(--text-muted);padding-top:10px;border-top:1px solid var(--border)">
                <?= htmlspecialchars($sv['notes']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<div class="card">
    <div class="empty-state">
        <div class="empty-state-icon">💰</div>
        <div class="empty-state-title">No savings records found</div>
        <div class="empty-state-desc">Your pension, provident, and benefit contributions will appear here once they are set up by payroll.</div>
        <div style="margin-top:16px">
            <a href="<?= EP_URL ?>/hub.php?type=salary_query&new=1" class="btn btn-primary btn-sm">Enquire via Hub</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php epLayoutEnd(); ?>

