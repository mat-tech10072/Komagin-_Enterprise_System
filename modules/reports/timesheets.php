<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('reports.timesheets');

$pageTitle  = 'Timesheet Report';
$activeMenu = 'reports';

$monthFilter = $_GET['month'] ?? date('Y-m');
$deptFilter  = (int)($_GET['dept'] ?? 0);
$empFilter   = (int)($_GET['emp'] ?? 0);
$export      = $_GET['export'] ?? '';

[$y, $m] = explode('-', $monthFilter);
$dateStart = "{$y}-{$m}-01";
$dateEnd   = date('Y-m-t', strtotime($dateStart));

$where  = ['a.attendance_date BETWEEN ? AND ?'];
$params = [$dateStart, $dateEnd];

if ($deptFilter) { $where[] = 'e.department_id=?'; $params[] = $deptFilter; }
if ($empFilter)  { $where[] = 'a.employee_id=?'; $params[] = $empFilter; }

$whereSQL = implode(' AND ', $where);

$stmt = db()->prepare("SELECT a.*,
    a.total_hours_worked AS total_hours,
    a.overtime_hours AS ot_hours,
    e.first_name, e.last_name, e.employee_number, d.name as dept_name
    FROM attendance a
    JOIN employees e ON a.employee_id=e.id
    LEFT JOIN departments d ON e.department_id=d.id
    WHERE $whereSQL
    ORDER BY e.first_name, a.attendance_date");
$stmt->execute($params);
$records = $stmt->fetchAll();

// Summary per employee
$empSummary = [];
foreach ($records as $r) {
    $eid = $r['employee_id'];
    if (!isset($empSummary[$eid])) {
        $empSummary[$eid] = ['name'=>$r['first_name'].' '.$r['last_name'],'number'=>$r['employee_number'],'dept'=>$r['dept_name'],'present'=>0,'absent'=>0,'late'=>0,'total_hours'=>0,'ot_hours'=>0];
    }
    if ($r['status'] === 'present') $empSummary[$eid]['present']++;
    if ($r['status'] === 'absent')  $empSummary[$eid]['absent']++;
    if ($r['is_late'])              $empSummary[$eid]['late']++;
    $empSummary[$eid]['total_hours'] += $r['total_hours'] ?? 0;
    $empSummary[$eid]['ot_hours']    += $r['ot_hours'] ?? 0;
}

if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="timesheet_report_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['Date','Employee #','Name','Department','Sign In','Break Out','Break In','Sign Out','Total Hours','OT Hours','Status','Late','Edited']);
    foreach ($records as $r) {
        fputcsv($out, [
            $r['attendance_date'], $r['employee_number'], $r['first_name'].' '.$r['last_name'], $r['dept_name'],
            $r['sign_in'], $r['break_out'], $r['break_in'], $r['sign_out'],
            $r['total_hours'], $r['ot_hours'], $r['status'],
            $r['is_late'] ? 'Yes' : 'No', $r['is_edited'] ? 'Yes' : 'No'
        ]);
    }
    fclose($out); exit;
}

$departments = getDepartments();
$employees = db()->query("SELECT id, CONCAT(first_name,' ',last_name) as name FROM employees WHERE status != 'archived' ORDER BY first_name")->fetchAll();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/reports/index.php">Reports</a></li>
                <li class="breadcrumb-item active">Timesheet Report</li>
            </ol>
        </nav>
        <h1 class="page-title">Timesheet Report</h1>
        <p class="page-subtitle"><?= date('F Y', strtotime($dateStart)) ?> — <?= count($records) ?> records</p>
    </div>
    <div class="page-actions">
        <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>" class="btn btn-secondary btn-sm">Export CSV</a>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <input type="month" class="form-control" name="month" value="<?= e($monthFilter) ?>" style="width:auto;">
            <select class="form-select" name="dept" style="width:auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':''?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="emp" style="width:auto;">
                <option value="">All Employees</option>
                <?php foreach ($employees as $em): ?>
                    <option value="<?= $em['id'] ?>" <?= $empFilter==$em['id']?'selected':''?>><?= e($em['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
        </form>
    </div>
</div>

<!-- Summary per employee -->
<?php if (!empty($empSummary)): ?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><span class="card-title">Employee Summary</span></div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead><tr><th>Employee</th><th>Dept</th><th>Present</th><th>Absent</th><th>Late</th><th>Total Hrs</th><th>OT Hrs</th></tr></thead>
            <tbody>
            <?php foreach ($empSummary as $es): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:0.78rem;"><?= e($es['name']) ?></div>
                    <div class="emp-num"><?= e($es['number']) ?></div>
                </td>
                <td style="font-size:0.72rem;"><?= e($es['dept'] ?? '—') ?></td>
                <td><span class="badge badge-success"><?= $es['present'] ?></span></td>
                <td><span class="badge badge-danger"><?= $es['absent'] ?></span></td>
                <td><span class="badge badge-warning"><?= $es['late'] ?></span></td>
                <td style="font-weight:600;"><?= number_format($es['total_hours'],1) ?>h</td>
                <td style="color:var(--warning);"><?= number_format($es['ot_hours'],1) ?>h</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Records -->
<div class="card">
    <div class="card-header"><span class="card-title">Detailed Records</span></div>
    <div class="table-wrapper" style="border:none;">
        <?php if (empty($records)): ?>
        <div class="empty-state"><div class="empty-state-title">No records for this period</div></div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th><th>Employee</th><th>Dept</th>
                    <th>Sign In</th><th>Sign Out</th>
                    <th>Hrs</th><th>OT</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td style="font-size:0.75rem;"><?= formatDate($r['attendance_date']) ?></td>
                <td>
                    <div style="font-weight:600;font-size:0.75rem;"><?= e($r['first_name'].' '.$r['last_name']) ?></div>
                    <div class="emp-num"><?= e($r['employee_number']) ?></div>
                </td>
                <td style="font-size:0.72rem;"><?= e($r['dept_name'] ?? '—') ?></td>
                <td style="font-size:0.75rem;">
                    <?= $r['sign_in'] ? formatTime($r['sign_in']) : '—' ?>
                    <?php if ($r['is_late']): ?><span class="badge badge-warning" style="font-size:0.6rem;">L</span><?php endif; ?>
                </td>
                <td style="font-size:0.75rem;"><?= $r['sign_out'] ? formatTime($r['sign_out']) : '—' ?></td>
                <td style="font-weight:600;"><?= $r['total_hours'] ? number_format($r['total_hours'],1).'h' : '—' ?></td>
                <td style="color:var(--warning);"><?= $r['ot_hours'] > 0 ? number_format($r['ot_hours'],1).'h' : '—' ?></td>
                <td><?= attendanceStatusBadge($r['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
