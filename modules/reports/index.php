<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('reports.view', 'view');

$pageTitle  = 'Reports & Analytics';
$activeMenu = 'reports';

$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));
$deptId= (int)($_GET['dept']  ?? 0);
$type  = $_GET['type'] ?? 'attendance';
$today = date('Y-m-d');

$departments = getDepartments();
$thisMonth = sprintf('%04d-%02d', $year, $month);

// Summary data based on type
$reportData = [];

if ($type === 'attendance') {
    // Phase 5, Stage 5.3: previously driven by `attendance a JOIN employees`
    // — an employee with zero attendance rows all month (i.e. 100%
    // absenteeism) never appeared in this report at all, and absent_days
    // came from the dead is_absent column (KOM-098: always 0). Now driven
    // from `employees` with a LEFT JOIN, so a never-clocked-in employee
    // still appears; absent_days is computed from the real working
    // calendar (working days in the period so far, minus days present),
    // not from the schema's unused is_absent flag.
    $periodStart = sprintf('%04d-%02d-01', $year, $month);
    $periodEnd   = date('Y-m-t', strtotime($periodStart));
    if ($periodEnd > $today) { $periodEnd = $today; } // can't be absent on a day that hasn't happened yet
    $workingDaysInPeriod = $periodStart <= $periodEnd ? countWorkingDays($periodStart, $periodEnd) : 0;

    $where  = ['e.status NOT IN (\'archived\')'];
    $params = [$thisMonth];
    if ($deptId) { $where[] = 'e.department_id = ?'; $params[] = $deptId; }
    $whereSQL = implode(' AND ', $where);

    $stmt = db()->prepare("SELECT
        e.employee_number, CONCAT(e.first_name,' ',e.last_name) as name, d.name as dept,
        COUNT(a.id) as total_days,
        SUM(CASE WHEN a.sign_in IS NOT NULL THEN 1 ELSE 0 END) as present_days,
        SUM(a.is_late) as late_days,
        SUM(a.is_on_leave) as leave_days,
        COALESCE(SUM(a.total_hours_worked),0) as total_hours,
        COALESCE(SUM(a.overtime_hours),0) as ot_hours
        FROM employees e
        LEFT JOIN attendance a ON a.employee_id = e.id AND DATE_FORMAT(a.attendance_date,'%Y-%m') = ?
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE $whereSQL
        GROUP BY e.id, e.employee_number, e.first_name, e.last_name, d.name
        ORDER BY e.last_name");
    $stmt->execute(array_merge([$thisMonth], $deptId ? [$deptId] : []));
    $reportData = $stmt->fetchAll();
    foreach ($reportData as &$row) {
        $row['present_days'] = (int)($row['present_days'] ?? 0);
        $row['absent_days']  = max(0, $workingDaysInPeriod - $row['present_days']);
    }
    unset($row);

} elseif ($type === 'employees') {
    $where  = ['e.status NOT IN (\'archived\')'];
    $params = [];
    if ($deptId) { $where[] = 'e.department_id = ?'; $params[] = $deptId; }
    $whereSQL = implode(' AND ', $where);

    $stmt = db()->prepare("SELECT e.employee_number, CONCAT(e.first_name,' ',e.last_name) as name,
        d.name as dept, p.title as position, e.employment_type, e.status,
        e.start_date, e.contract_end_date, e.email, e.phone
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions p ON e.position_id = p.id
        WHERE $whereSQL ORDER BY e.last_name");
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();

} elseif ($type === 'leave') {
    $where  = ["DATE_FORMAT(la.start_date,'%Y-%m') = ?"];
    $params = [$thisMonth];
    if ($deptId) { $where[] = 'e.department_id = ?'; $params[] = $deptId; }
    $whereSQL = implode(' AND ', $where);

    $stmt = db()->prepare("SELECT la.*, lt.name as leave_type,
        e.employee_number, CONCAT(e.first_name,' ',e.last_name) as name, d.name as dept
        FROM leave_applications la
        JOIN employees e ON la.employee_id = e.id
        JOIN leave_types lt ON la.leave_type_id = lt.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE $whereSQL ORDER BY la.start_date");
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();

} elseif ($type === 'overtime') {
    $stmt = db()->prepare("SELECT ot.*, a.attendance_date,
        e.employee_number, CONCAT(e.first_name,' ',e.last_name) as name, d.name as dept
        FROM overtime_records ot
        JOIN attendance a ON ot.attendance_id = a.id
        JOIN employees e ON ot.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE DATE_FORMAT(a.attendance_date,'%Y-%m') = ?
        AND (? = 0 OR e.department_id = ?)
        ORDER BY a.attendance_date");
    $stmt->execute([$thisMonth, $deptId, $deptId]);
    $reportData = $stmt->fetchAll();
}

// ── CSV Export (KOM-028: link existed but nothing ever read $_GET['export']) ──
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . $type . '_' . $thisMonth . '.csv"');
    $out = fopen('php://output', 'w');
    if ($type === 'attendance') {
        fputcsv($out, ['Employee No.','Name','Department','Present Days','Absent Days','Late Days','Leave Days','Total Hours','Overtime Hours']);
        foreach ($reportData as $r) {
            fputcsv($out, [$r['employee_number'],$r['name'],$r['dept'],$r['present_days'],$r['absent_days'],$r['late_days'],$r['leave_days'],$r['total_hours'],$r['ot_hours']]);
        }
    } elseif ($type === 'employees') {
        fputcsv($out, ['Employee No.','Name','Department','Position','Employment Type','Status','Start Date','Contract End','Email','Phone']);
        foreach ($reportData as $r) {
            fputcsv($out, [$r['employee_number'],$r['name'],$r['dept'],$r['position'],$r['employment_type'],$r['status'],$r['start_date'],$r['contract_end_date'],$r['email'],$r['phone']]);
        }
    } elseif ($type === 'leave') {
        fputcsv($out, ['Employee No.','Name','Department','Leave Type','Start Date','End Date','Total Days','Status']);
        foreach ($reportData as $r) {
            fputcsv($out, [$r['employee_number'],$r['name'],$r['dept'],$r['leave_type'],$r['start_date'],$r['end_date'],$r['total_days'],$r['status']]);
        }
    } elseif ($type === 'overtime') {
        fputcsv($out, ['Employee No.','Name','Department','Date','Suggested Hours','Approved Hours','Status']);
        foreach ($reportData as $r) {
            fputcsv($out, [$r['employee_number'],$r['name'],$r['dept'],$r['attendance_date'],$r['suggested_hours'],$r['approved_hours'],$r['status']]);
        }
    }
    fclose($out);
    exit;
}

$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Reports & Analytics</h1>
        <p class="page-subtitle"><?= $months[$month-1] ?> <?= $year ?></p>
    </div>
    <div class="page-actions">
        <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary btn-sm">Export CSV</a>
    </div>
</div>

<!-- Report Type Tabs -->
<div class="tab-nav">
    <?php $rtabs = [['attendance','Attendance'],['employees','Employees'],['leave','Leave'],['overtime','Overtime']]; ?>
    <?php foreach ($rtabs as [$k,$l]): ?>
        <a href="?type=<?= $k ?>&year=<?= $year ?>&month=<?= $month ?>&dept=<?= $deptId ?>" class="tab-item <?= $type===$k?'active':'' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:16px;">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <select class="form-select" name="year" style="width:auto;">
                <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
                    <option value="<?= $y ?>" <?= $year==$y?'selected':''?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select class="form-select" name="month" style="width:auto;">
                <?php foreach ($months as $mi=>$mn): ?>
                    <option value="<?= $mi+1 ?>" <?= $month==$mi+1?'selected':''?>><?= $mn ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="dept" style="width:auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptId==$d['id']?'selected':''?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Generate</button>
        </form>
        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);"><?= count($reportData) ?> records</div>
    </div>
</div>

<?php if (empty($reportData)): ?>
<div class="card">
    <div class="empty-state">
        <div class="empty-state-title">No data for this period</div>
        <div class="empty-state-desc">Try selecting a different month or department.</div>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-wrapper" style="border:none;">
        <?php if ($type === 'attendance'): ?>
        <table class="table">
            <thead><tr><th>Employee</th><th>Dept</th><th>Present</th><th>Absent</th><th>Late</th><th>On Leave</th><th>Total Hours</th><th>OT Hours</th></tr></thead>
            <tbody>
            <?php foreach ($reportData as $r): ?>
            <tr>
                <td>
                    <div class="emp-name"><?= e($r['name']) ?></div>
                    <div class="emp-num"><?= e($r['employee_number']) ?></div>
                </td>
                <td style="font-size:0.72rem;"><?= e($r['dept']??'—') ?></td>
                <td><span style="color:var(--success);font-weight:600;"><?= $r['present_days'] ?></span></td>
                <td><span style="color:var(--danger);font-weight:600;"><?= $r['absent_days'] ?></span></td>
                <td><span style="color:var(--warning);"><?= $r['late_days'] ?></span></td>
                <td><?= $r['leave_days'] ?></td>
                <td style="font-weight:600;"><?= number_format($r['total_hours'],1) ?></td>
                <td><?= $r['ot_hours'] > 0 ? '<span style="color:var(--warning);">'.number_format($r['ot_hours'],1).'</span>' : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php elseif ($type === 'employees'): ?>
        <table class="table">
            <thead><tr><th>Number</th><th>Name</th><th>Dept</th><th>Position</th><th>Type</th><th>Status</th><th>Start Date</th></tr></thead>
            <tbody>
            <?php foreach ($reportData as $r): ?>
            <tr>
                <td><code style="font-size:0.72rem;"><?= e($r['employee_number']) ?></code></td>
                <td><?= e($r['name']) ?></td>
                <td style="font-size:0.72rem;"><?= e($r['dept']??'—') ?></td>
                <td style="font-size:0.72rem;"><?= e($r['position']??'—') ?></td>
                <td style="font-size:0.72rem;"><?= ucfirst(str_replace('_',' ',$r['employment_type'])) ?></td>
                <td><?= employeeStatusBadge($r['status']) ?></td>
                <td style="font-size:0.72rem;"><?= formatDate($r['start_date']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php elseif ($type === 'leave'): ?>
        <table class="table">
            <thead><tr><th>Employee</th><th>Dept</th><th>Leave Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($reportData as $r): ?>
            <tr>
                <td>
                    <div class="emp-name"><?= e($r['name']) ?></div>
                    <div class="emp-num"><?= e($r['employee_number']) ?></div>
                </td>
                <td style="font-size:0.72rem;"><?= e($r['dept']??'—') ?></td>
                <td><span class="badge badge-info"><?= e($r['leave_type']) ?></span></td>
                <td style="font-size:0.75rem;"><?= formatDate($r['start_date']) ?></td>
                <td style="font-size:0.75rem;"><?= formatDate($r['end_date']) ?></td>
                <td style="font-weight:600;"><?= $r['total_days'] ?></td>
                <td><?= leaveStatusBadge($r['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php elseif ($type === 'overtime'): ?>
        <table class="table">
            <thead><tr><th>Employee</th><th>Date</th><th>Suggested</th><th>Approved</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($reportData as $r): ?>
            <tr>
                <td>
                    <div class="emp-name"><?= e($r['name']) ?></div>
                    <div class="emp-num"><?= e($r['employee_number']) ?></div>
                </td>
                <td><?= formatDate($r['attendance_date']) ?></td>
                <td><?= $r['suggested_hours'] ?> hrs</td>
                <td><strong><?= $r['approved_hours'] ?: '—' ?></strong></td>
                <td><?= leaveStatusBadge($r['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
