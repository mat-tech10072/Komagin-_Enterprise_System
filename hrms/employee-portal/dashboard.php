<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';
epRequireLogin();

$emp    = epCurrentEmployee();
$empId  = $emp['id'];
$month  = date('n');
$year   = date('Y');

// Latest payslip
$ps = db()->prepare("SELECT period_month, period_year, gross_salary, net_salary, status
    FROM payslips WHERE employee_id=? ORDER BY period_year DESC, period_month DESC LIMIT 1");
$ps->execute([$empId]);
$latestPayslip = $ps->fetch(PDO::FETCH_ASSOC);

// Leave balance summary
$lb = db()->prepare("SELECT SUM(entitled_days) as total_entitled, SUM(remaining_days) as total_remaining
    FROM leave_balances WHERE employee_id=? AND year=?");
$lb->execute([$empId, $year]);
$leaveSummary = $lb->fetch(PDO::FETCH_ASSOC);

// Open requests count
$rq = db()->prepare("SELECT COUNT(*) FROM employee_requests WHERE employee_id=? AND status IN ('open','in_progress')");
$rq->execute([$empId]);
$openRequests = $rq->fetchColumn();

// Total savings
$sv = db()->prepare("SELECT SUM(current_balance) as total FROM employee_savings WHERE employee_id=?");
$sv->execute([$empId]);
$totalSavings = $sv->fetchColumn() ?? 0;

// Recent requests
$recentReqs = db()->prepare("SELECT id, subject, request_type, status, created_at
    FROM employee_requests WHERE employee_id=? ORDER BY created_at DESC LIMIT 5");
$recentReqs->execute([$empId]);
$recentRequests = $recentReqs->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved',
    'closed'=>'Closed','rejected'=>'Rejected'
];
$reqTypeLabels = [
    'leave_query'=>'Leave Query','payslip_query'=>'Payslip Query',
    'employment_certificate'=>'Employment Certificate','bank_update'=>'Bank Update',
    'salary_query'=>'Salary Query','training_request'=>'Training Request',
    'general_query'=>'General Query','grievance'=>'Grievance',
    'payroll_query'=>'Payroll Query','document_request'=>'Document Request'
];

epLayoutStart('Dashboard', 'dashboard');
?>

<div class="ep-kpi-grid">
    <div class="ep-kpi green">
        <div class="ep-kpi-label">Net Pay (Latest)</div>
        <div class="ep-kpi-value"><?= HRMS_CURRENCY_SYMBOL ?> <?= $latestPayslip ? number_format($latestPayslip['net_salary'],2) : '—' ?></div>
        <div class="ep-kpi-sub">
            <?= $latestPayslip ? date('F Y', mktime(0,0,0,$latestPayslip['period_month'],1,$latestPayslip['period_year'])) : 'No payslip yet' ?>
        </div>
    </div>
    <div class="ep-kpi amber">
        <div class="ep-kpi-label">Leave Remaining</div>
        <div class="ep-kpi-value"><?= number_format($leaveSummary['total_remaining'] ?? 0, 1) ?> days</div>
        <div class="ep-kpi-sub">of <?= number_format($leaveSummary['total_entitled'] ?? 0, 0) ?> days entitled <?= $year ?></div>
    </div>
    <div class="ep-kpi info">
        <div class="ep-kpi-label">Savings Balance</div>
        <div class="ep-kpi-value"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($totalSavings, 2) ?></div>
        <div class="ep-kpi-sub">All funds combined</div>
    </div>
    <div class="ep-kpi" style="border-left-color:<?= $openRequests>0 ? '#D97706' : '#16A34A' ?>">
        <div class="ep-kpi-label">Open Requests</div>
        <div class="ep-kpi-value"><?= $openRequests ?></div>
        <div class="ep-kpi-sub">Pending HR response</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <!-- Employment Summary -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">My Employment</span>
            <a href="<?= EP_URL ?>/employment.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding:0">
            <div style="padding:12px 20px;border-bottom:1px solid var(--border)">
                <div class="detail-label">Full Name</div>
                <div class="detail-value"><?= htmlspecialchars(trim(($emp['first_name']??'').' '.($emp['last_name']??''))) ?></div>
            </div>
            <div style="padding:12px 20px;border-bottom:1px solid var(--border)">
                <div class="detail-label">Employee Number</div>
                <div class="detail-value" style="font-family:monospace"><?= htmlspecialchars($emp['employee_number']??'') ?></div>
            </div>
            <div style="padding:12px 20px;border-bottom:1px solid var(--border)">
                <div class="detail-label">Department</div>
                <div class="detail-value"><?= htmlspecialchars($emp['dept_name']??'—') ?></div>
            </div>
            <div style="padding:12px 20px;border-bottom:1px solid var(--border)">
                <div class="detail-label">Position</div>
                <div class="detail-value"><?= htmlspecialchars($emp['position_title']??'—') ?></div>
            </div>
            <div style="padding:12px 20px">
                <div class="detail-label">Start Date</div>
                <div class="detail-value"><?= !empty($emp['start_date']) ? date('d M Y', strtotime($emp['start_date'])) : 'Not recorded' ?></div>
            </div>
        </div>
    </div>

    <!-- Recent Requests -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Recent Requests</span>
            <a href="<?= EP_URL ?>/hub.php" class="btn btn-ghost btn-sm">All Requests</a>
        </div>
        <div class="card-body" style="padding:0">
            <?php if ($recentRequests): ?>
                <?php foreach ($recentRequests as $req): ?>
                <div style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:8px">
                    <div>
                        <div style="font-size:0.78rem;font-weight:600"><?= htmlspecialchars($req['subject']) ?></div>
                        <div style="font-size:0.67rem;color:var(--text-muted)"><?= $reqTypeLabels[$req['request_type']] ?? $req['request_type'] ?></div>
                    </div>
                    <span class="badge status-<?= $req['status'] ?>"><?= $statusLabels[$req['status']] ?? $req['status'] ?></span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💬</div>
                    <div class="empty-state-title">No requests yet</div>
                    <div class="empty-state-desc">Submit a request via the Hub</div>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer" style="text-align:right">
            <a href="<?= EP_URL ?>/hub.php?new=1" class="btn btn-primary btn-sm">
                + New Request
            </a>
        </div>
    </div>
</div>

<?php epLayoutEnd(); ?>

