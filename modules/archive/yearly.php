<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('archive.view');

$pageTitle  = 'Yearly Archive';
$activeMenu = 'archive';

$year = (int)($_GET['year'] ?? date('Y'));
$yStart = sprintf('%04d-01-01', $year);
$yEnd   = sprintf('%04d-12-31', $year);

$prevYear = $year - 1;
$nextYear = $year + 1;

// Annual attendance summary
$attStats = db()->prepare("SELECT
    COUNT(*) as total_records,
    SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
    SUM(CASE WHEN status='on_leave' THEN 1 ELSE 0 END) as on_leave,
    SUM(CASE WHEN is_late=1 THEN 1 ELSE 0 END) as late,
    COALESCE(SUM(total_hours_worked),0) as total_hours
    FROM attendance WHERE attendance_date BETWEEN ? AND ?");
$attStats->execute([$yStart, $yEnd]);
$att = $attStats->fetch();

$otTotalStmt = db()->prepare("SELECT COALESCE(SUM(approved_hours),0) FROM overtime_records WHERE YEAR(overtime_date)=? AND status='approved'");
$otTotalStmt->execute([$year]);
$att['total_ot'] = $otTotalStmt->fetchColumn();

// Leave summary by type
$leaveByType = db()->prepare("SELECT lt.name, COUNT(la.id) as apps, COALESCE(SUM(la.total_days),0) as days
    FROM leave_applications la
    JOIN leave_types lt ON la.leave_type_id=lt.id
    WHERE la.status='approved' AND la.start_date BETWEEN ? AND ?
    GROUP BY lt.id, lt.name ORDER BY days DESC");
$leaveByType->execute([$yStart, $yEnd]);
$leaveTypes = $leaveByType->fetchAll();

// Headcount changes
$joined = db()->prepare("SELECT COUNT(*) FROM employees WHERE YEAR(start_date)=?")->execute([$year]) ? null : null;
$joinedStmt = db()->prepare("SELECT COUNT(*) FROM employees WHERE YEAR(start_date)=?");
$joinedStmt->execute([$year]);
$totalJoined = (int)$joinedStmt->fetchColumn();

$exitStmt = db()->prepare("SELECT COUNT(*) FROM employees WHERE YEAR(exit_date)=?");
$exitStmt->execute([$year]);
$totalExited = (int)$exitStmt->fetchColumn();

$activeNow = (int)db()->query("SELECT COUNT(*) FROM employees WHERE status IN ('active','probation')")->fetchColumn();

// Monthly trend
$monthlyTrend = [];
for ($m = 1; $m <= 12; $m++) {
    $ms = sprintf('%04d-%02d-01', $year, $m);
    $me = date('Y-m-t', strtotime($ms));
    $stmt = db()->prepare("SELECT
        SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
        0 as ot
        FROM attendance WHERE attendance_date BETWEEN ? AND ?");
    $stmt->execute([$ms, $me]);
    $monthlyTrend[$m] = $stmt->fetch();
}

// Top OT earners
$topOT = db()->prepare("SELECT e.first_name, e.last_name, e.employee_number, COALESCE(SUM(ot.approved_hours),0) as total_ot
    FROM overtime_records ot JOIN employees e ON ot.employee_id=e.id
    WHERE ot.status='approved'
    AND YEAR((SELECT a.attendance_date FROM attendance a WHERE a.id=ot.attendance_id))=?
    GROUP BY e.id ORDER BY total_ot DESC LIMIT 5");
$topOT->execute([$year]);
$topOTEmployees = $topOT->fetchAll();

// Dept attendance rates
$deptAttendance = db()->prepare("SELECT d.name, COUNT(a.id) as total,
    SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present
    FROM attendance a
    JOIN employees e ON a.employee_id=e.id
    JOIN departments d ON e.department_id=d.id
    WHERE a.attendance_date BETWEEN ? AND ?
    GROUP BY d.id, d.name ORDER BY present DESC");
$deptAttendance->execute([$yStart, $yEnd]);
$deptStats = $deptAttendance->fetchAll();

$archiveRecords = db()->prepare("SELECT ar.*, u.username as created_by_user
    FROM archive_records ar LEFT JOIN users u ON ar.generated_by=u.id
    WHERE ar.year=? AND ar.archive_type='yearly'
    ORDER BY ar.generated_at DESC");
$archiveRecords->execute([$year]);
$archives = $archiveRecords->fetchAll();

$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Yearly Archive</h1>
        <p class="page-subtitle">Annual summary — <?= $year ?></p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary btn-sm" data-modal-open="generateArchiveModal">Generate Annual Record</button>
    </div>
</div>

<!-- Year Navigation -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="?year=<?= $prevYear ?>" class="btn btn-secondary btn-sm">&larr; <?= $prevYear ?></a>
    <strong style="font-size:1.1rem;"><?= $year ?></strong>
    <?php if ($nextYear <= (int)date('Y')): ?>
    <a href="?year=<?= $nextYear ?>" class="btn btn-secondary btn-sm"><?= $nextYear ?> &rarr;</a>
    <?php else: ?>
    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;"><?= $nextYear ?> &rarr;</span>
    <?php endif; ?>
</div>

<!-- KPI Cards -->
<div class="kpi-grid" style="grid-template-columns:repeat(6,1fr);margin-bottom:20px;">
    <div class="kpi-card kpi-success">
        <div class="kpi-card-label">Total Present</div>
        <div class="kpi-card-value"><?= nf($att['present']) ?></div>
    </div>
    <div class="kpi-card kpi-danger">
        <div class="kpi-card-label">Total Absent</div>
        <div class="kpi-card-value"><?= nf($att['absent']) ?></div>
    </div>
    <div class="kpi-card kpi-warning">
        <div class="kpi-card-label">Late Arrivals</div>
        <div class="kpi-card-value"><?= nf($att['late']) ?></div>
    </div>
    <div class="kpi-card kpi-info">
        <div class="kpi-card-label">OT Hours</div>
        <div class="kpi-card-value"><?= nf($att['total_ot']) ?></div>
    </div>
    <div class="kpi-card kpi-primary">
        <div class="kpi-card-label">New Hires</div>
        <div class="kpi-card-value"><?= $totalJoined ?></div>
    </div>
    <div class="kpi-card kpi-danger">
        <div class="kpi-card-label">Exits</div>
        <div class="kpi-card-value"><?= $totalExited ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <!-- Monthly Trend Table -->
    <div class="card">
        <div class="card-header"><span class="card-title">Monthly Attendance Trend</span></div>
        <div class="table-wrapper" style="border:none;">
            <table class="table">
                <thead><tr><th>Month</th><th>Present</th><th>Absent</th><th>OT hrs</th><th>Rate</th></tr></thead>
                <tbody>
                <?php foreach ($monthlyTrend as $m => $row):
                    $t = $row['present'] + $row['absent'];
                    $r = $t ? round(($row['present']/$t)*100) : 0;
                ?>
                <tr>
                    <td style="font-weight:600;"><?= $monthNames[$m-1] ?></td>
                    <td><?= $row['present'] ?></td>
                    <td><?= $row['absent'] ?></td>
                    <td><?= nf($row['ot'],1) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:50px;height:5px;background:var(--bg);border-radius:3px;overflow:hidden;">
                                <div style="width:<?= $r ?>%;height:100%;background:<?= $r>=80?'var(--success)':($r>=60?'var(--warning)':'var(--danger)') ?>;border-radius:3px;"></div>
                            </div>
                            <span style="font-size:0.7rem;"><?= $r ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Leave Summary -->
    <div class="card">
        <div class="card-header"><span class="card-title">Leave Summary by Type</span></div>
        <div class="table-wrapper" style="border:none;">
            <?php if (empty($leaveTypes)): ?>
            <div class="empty-state"><div class="empty-state-title" style="font-size:0.8rem;">No approved leave this year</div></div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Leave Type</th><th>Applications</th><th>Days Taken</th></tr></thead>
                <tbody>
                <?php foreach ($leaveTypes as $lt): ?>
                <tr>
                    <td style="font-weight:600;"><?= e($lt['name']) ?></td>
                    <td><?= $lt['apps'] ?></td>
                    <td><?= nf($lt['days'],1) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <!-- Dept attendance -->
    <div class="card">
        <div class="card-header"><span class="card-title">Attendance Rate by Department</span></div>
        <div class="table-wrapper" style="border:none;">
            <?php if (empty($deptStats)): ?>
            <div class="empty-state"><div class="empty-state-title" style="font-size:0.8rem;">No data</div></div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Department</th><th>Present</th><th>Total</th><th>Rate</th></tr></thead>
                <tbody>
                <?php foreach ($deptStats as $ds):
                    $r = $ds['total'] ? round(($ds['present']/$ds['total'])*100) : 0;
                ?>
                <tr>
                    <td style="font-weight:600;"><?= e($ds['name']) ?></td>
                    <td><?= $ds['present'] ?></td>
                    <td><?= $ds['total'] ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:60px;height:5px;background:var(--bg);border-radius:3px;overflow:hidden;">
                                <div style="width:<?= $r ?>%;height:100%;background:var(--success);border-radius:3px;"></div>
                            </div>
                            <span style="font-size:0.72rem;"><?= $r ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top OT -->
    <div class="card">
        <div class="card-header"><span class="card-title">Top OT Earners (Approved)</span></div>
        <div class="table-wrapper" style="border:none;">
            <?php if (empty($topOTEmployees)): ?>
            <div class="empty-state"><div class="empty-state-title" style="font-size:0.8rem;">No approved OT</div></div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Employee</th><th>OT Hours</th></tr></thead>
                <tbody>
                <?php foreach ($topOTEmployees as $e): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:0.78rem;"><?= e($e['first_name'].' '.$e['last_name']) ?></div>
                        <div class="emp-num"><?= e($e['employee_number']) ?></div>
                    </td>
                    <td style="font-weight:700;"><?= nf($e['total_ot'],1) ?>h</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Archive Records -->
<div class="card">
    <div class="card-header"><span class="card-title">Annual Archive Records</span></div>
    <div class="table-wrapper" style="border:none;">
        <?php if (empty($archives)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No annual archive records yet</div>
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
            <h5 class="modal-title">Generate Annual Archive — <?= $year ?></h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/archive/save.php" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="period_type" value="yearly">
            <input type="hidden" name="period_year" value="<?= $year ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Document Type</label>
                    <select class="form-select" name="document_type">
                        <option value="hr_summary">HR Summary</option>
                        <option value="attendance">Attendance</option>
                        <option value="leave_report">Leave Report</option>
                        <option value="employee_list">Employee List</option>
                        <option value="training_summary">Training Summary</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" placeholder="e.g. Annual <?= $year ?> HR Summary">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="description" rows="2"></textarea>
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
