<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('archive.view', 'view');

$pageTitle  = 'Quarterly Archive';
$activeMenu = 'archive';

$year    = (int)($_GET['year'] ?? date('Y'));
$quarter = (int)($_GET['q'] ?? ceil(date('n')/3));
if ($quarter < 1) { $quarter = 4; $year--; }
if ($quarter > 4) { $quarter = 1; $year++; }

$qMonths = [1=>[1,2,3], 2=>[4,5,6], 3=>[7,8,9], 4=>[10,11,12]];
$months  = $qMonths[$quarter];
$qStart  = sprintf('%04d-%02d-01', $year, $months[0]);
$qEnd    = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, end($months))));
$qLabel  = "Q{$quarter} ".sprintf('%04d', $year);
$qNames  = [1=>'Q1 (Jan–Mar)', 2=>'Q2 (Apr–Jun)', 3=>'Q3 (Jul–Sep)', 4=>'Q4 (Oct–Dec)'];

$prevQ = $quarter - 1 <= 0 ? 4 : $quarter - 1;
$prevY = $quarter - 1 <= 0 ? $year - 1 : $year;
$nextQ = $quarter + 1 > 4  ? 1 : $quarter + 1;
$nextY = $quarter + 1 > 4  ? $year + 1 : $year;

// Quarterly stats
$attendanceStats = db()->prepare("SELECT
    COUNT(*) as total_records,
    SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
    SUM(CASE WHEN is_late=1 THEN 1 ELSE 0 END) as late,
    0 as total_ot
    FROM attendance WHERE attendance_date BETWEEN ? AND ?");
$attendanceStats->execute([$qStart, $qEnd]);
$attStats = $attendanceStats->fetch();

// Overtime hours come from overtime_records, not attendance
$otStmt = db()->prepare("SELECT COALESCE(SUM(approved_hours),0) as total_ot FROM overtime_records WHERE overtime_date BETWEEN ? AND ? AND status='approved'");
$otStmt->execute([$qStart, $qEnd]);
$attStats['total_ot'] = $otStmt->fetchColumn();

$leaveStats = db()->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total_days),0) as total_days
    FROM leave_applications WHERE status='approved' AND start_date BETWEEN ? AND ?");
$leaveStats->execute([$qStart, $qEnd]);
$lvStats = $leaveStats->fetch();

$headcountStmt = db()->query("SELECT
    SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status='probation' THEN 1 ELSE 0 END) as probation,
    SUM(CASE WHEN status IN ('resigned','terminated') THEN 1 ELSE 0 END) as exits
    FROM employees WHERE status != 'archived'");
$hcStats = $headcountStmt->fetch();

// Monthly breakdown
$monthlyBreakdown = [];
foreach ($months as $m) {
    $mStart = sprintf('%04d-%02d-01', $year, $m);
    $mEnd   = date('Y-m-t', strtotime($mStart));
    $mStmt  = db()->prepare("SELECT
        SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN is_late=1 THEN 1 ELSE 0 END) as late
        FROM attendance WHERE attendance_date BETWEEN ? AND ?");
    $mStmt->execute([$mStart, $mEnd]);
    $monthlyBreakdown[$m] = $mStmt->fetch();
}

$archiveRecords = db()->prepare("SELECT ar.*, u.username as created_by_user
    FROM archive_records ar LEFT JOIN users u ON ar.generated_by=u.id
    WHERE ar.year=? AND ar.archive_type='quarterly' AND ar.quarter=?
    ORDER BY ar.generated_at DESC");
$archiveRecords->execute([$year, $quarter]);
$archives = $archiveRecords->fetchAll();

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quarterly Archive</h1>
        <p class="page-subtitle"><?= $qNames[$quarter] ?> <?= $year ?></p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary btn-sm" data-modal-open="generateArchiveModal">Generate Archive Record</button>
    </div>
</div>

<!-- Quarter Navigation -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="?q=<?= $prevQ ?>&year=<?= $prevY ?>" class="btn btn-secondary btn-sm">&larr; <?= "Q{$prevQ} {$prevY}" ?></a>
    <strong style="font-size:1rem;"><?= $qLabel ?> &mdash; <?= date('d M', strtotime($qStart)) ?> to <?= date('d M Y', strtotime($qEnd)) ?></strong>
    <a href="?q=<?= $nextQ ?>&year=<?= $nextY ?>" class="btn btn-secondary btn-sm"><?= "Q{$nextQ} {$nextY}" ?> &rarr;</a>
    <div style="margin-left:auto;display:flex;gap:8px;">
        <?php for ($q=1;$q<=4;$q++): ?>
        <a href="?q=<?= $q ?>&year=<?= $year ?>" class="btn btn-sm <?= $q===$quarter?'btn-primary':'btn-secondary' ?>"><?= "Q{$q}" ?></a>
        <?php endfor; ?>
    </div>
</div>

<!-- KPI Summary -->
<div class="kpi-grid" style="grid-template-columns:repeat(6,1fr);margin-bottom:20px;">
    <div class="kpi-card kpi-success">
        <div class="kpi-card-label">Total Present</div>
        <div class="kpi-card-value"><?= nf($attStats['present']) ?></div>
        <div class="kpi-card-sub">attendance days</div>
    </div>
    <div class="kpi-card kpi-danger">
        <div class="kpi-card-label">Total Absent</div>
        <div class="kpi-card-value"><?= nf($attStats['absent']) ?></div>
        <div class="kpi-card-sub">absence days</div>
    </div>
    <div class="kpi-card kpi-warning">
        <div class="kpi-card-label">Late Arrivals</div>
        <div class="kpi-card-value"><?= nf($attStats['late']) ?></div>
    </div>
    <div class="kpi-card kpi-info">
        <div class="kpi-card-label">OT Hours</div>
        <div class="kpi-card-value"><?= nf($attStats['total_ot'],1) ?></div>
    </div>
    <div class="kpi-card kpi-primary">
        <div class="kpi-card-label">Leave Apps</div>
        <div class="kpi-card-value"><?= nf($lvStats['total']) ?></div>
        <div class="kpi-card-sub"><?= nf($lvStats['total_days'],1) ?> days</div>
    </div>
    <div class="kpi-card kpi-success">
        <div class="kpi-card-label">Active Staff</div>
        <div class="kpi-card-value"><?= nf($hcStats['active'] + $hcStats['probation']) ?></div>
        <div class="kpi-card-sub"><?= $hcStats['exits'] ?> exits</div>
    </div>
</div>

<!-- Monthly Breakdown -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Monthly Breakdown — <?= $qLabel ?></span></div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead><tr><th>Month</th><th>Present Days</th><th>Absent Days</th><th>Late</th><th>Attendance Rate</th></tr></thead>
            <tbody>
            <?php $monthNames=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; ?>
            <?php foreach ($months as $m): ?>
            <?php $mb = $monthlyBreakdown[$m]; $tot = ($mb['present']+$mb['absent']); $rate = $tot ? round(($mb['present']/$tot)*100) : 0; ?>
            <tr>
                <td style="font-weight:600;"><?= $monthNames[$m-1] ?> <?= $year ?></td>
                <td><span class="badge badge-success"><?= $mb['present'] ?></span></td>
                <td><span class="badge badge-danger"><?= $mb['absent'] ?></span></td>
                <td><span class="badge badge-warning"><?= $mb['late'] ?></span></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="flex:1;height:6px;background:var(--bg);border-radius:3px;overflow:hidden;">
                            <div style="width:<?= $rate ?>%;height:100%;background:var(--success);border-radius:3px;"></div>
                        </div>
                        <span style="font-size:0.75rem;font-weight:600;"><?= $rate ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Archive Records -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Archive Records (<?= $qLabel ?>)</span>
        <span style="font-size:0.72rem;color:var(--text-muted);"><?= count($archives) ?> records</span>
    </div>
    <div class="table-wrapper" style="border:none;">
        <?php if (empty($archives)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No archive records generated yet</div>
            <div class="empty-state-desc">Generate an archive record to permanently store this quarter's data snapshot.</div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Type</th><th>Description</th><th>Generated By</th><th>Date</th><th>Locked</th></tr></thead>
            <tbody>
            <?php foreach ($archives as $ar): ?>
            <tr>
                <td><span class="badge badge-secondary"><?= ucwords(str_replace('_',' ',$ar['record_type'])) ?></span></td>
                <td style="font-size:0.75rem;"><?= e($ar['description'] ?? '—') ?></td>
                <td style="font-size:0.72rem;"><?= e($ar['created_by_user'] ?? '—') ?></td>
                <td style="font-size:0.72rem;"><?= formatDateTime($ar['generated_at']) ?></td>
                <td><?= $ar['is_locked'] ? '<span class="badge badge-danger">Locked</span>' : '<span class="badge badge-secondary">Open</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Generate Archive Modal -->
<div class="modal-overlay" id="generateArchiveModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Generate Quarterly Archive — <?= $qLabel ?></h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/archive/save.php" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="period_type" value="quarterly">
            <input type="hidden" name="period_year" value="<?= $year ?>">
            <input type="hidden" name="period_quarter" value="<?= $quarter ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Document Type</label>
                    <select class="form-select" name="document_type">
                        <option value="attendance">Attendance</option>
                        <option value="leave_report">Leave Report</option>
                        <option value="overtime_report">Overtime Report</option>
                        <option value="hr_summary">HR Summary</option>
                        <option value="employee_list">Employee List</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" placeholder="e.g. Q<?= $quarter ?> <?= $year ?> Attendance Summary">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="description" rows="2" placeholder="Optional notes…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
