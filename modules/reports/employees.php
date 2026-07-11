<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('reports.employees', 'view');

$pageTitle  = 'Employee Report';
$activeMenu = 'reports';

$deptFilter   = (int)($_GET['dept'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$typeFilter   = $_GET['type'] ?? '';
$export       = $_GET['export'] ?? '';

$where  = ['e.status != "archived"'];
$params = [];

if ($deptFilter)   { $where[] = 'e.department_id=?'; $params[] = $deptFilter; }
if ($statusFilter) { $where[] = 'e.status=?'; $params[] = $statusFilter; }
if ($typeFilter)   { $where[] = 'e.employment_type=?'; $params[] = $typeFilter; }

$whereSQL = implode(' AND ', $where);

$stmt = db()->prepare("SELECT e.*, d.name as dept_name, p.title as position_title
    FROM employees e
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN positions p ON e.position_id=p.id
    WHERE $whereSQL ORDER BY e.first_name, e.last_name");
$stmt->execute($params);
$employees = $stmt->fetchAll();

// CSV Export
if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="employees_report_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['Employee Number','First Name','Last Name','Department','Position','Employment Type','Status','Start Date','Email','Phone','National ID']);
    foreach ($employees as $emp) {
        fputcsv($out, [
            $emp['employee_number'], $emp['first_name'], $emp['last_name'],
            $emp['dept_name'] ?? '', $emp['position_title'] ?? '',
            $emp['employment_type'], $emp['status'], $emp['start_date'],
            $emp['email'] ?? '', $emp['phone'] ?? '', $emp['national_id'] ?? ''
        ]);
    }
    fclose($out); exit;
}

$departments = getDepartments();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/reports/index.php">Reports</a></li>
                <li class="breadcrumb-item active">Employee Report</li>
            </ol>
        </nav>
        <h1 class="page-title">Employee Master Report</h1>
        <p class="page-subtitle"><?= count($employees) ?> employees</p>
    </div>
    <div class="page-actions">
        <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>" class="btn btn-secondary btn-sm">Export CSV</a>
    </div>
</div>

<div class="card">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <select class="form-select" name="dept" style="width:auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':''?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="status" style="width:auto;">
                <option value="">All Status</option>
                <?php foreach (['active','probation','on_leave','suspended','resigned','terminated'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':''?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="type" style="width:auto;">
                <option value="">All Types</option>
                <?php foreach (['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= $typeFilter===$v?'selected':''?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <a href="?" class="btn btn-ghost btn-sm">Reset</a>
        </form>
    </div>

    <div class="table-wrapper" style="border:none;">
        <?php if (empty($employees)): ?>
        <div class="empty-state"><div class="empty-state-title">No employees match the filters</div></div>
        <?php else: ?>
        <table class="table" id="reportTable">
            <thead>
                <tr>
                    <th>Employee #</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Type</th>
                    <th>Start Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $emp): ?>
            <tr>
                <td class="emp-num"><?= e($emp['employee_number']) ?></td>
                <td>
                    <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $emp['id'] ?>" style="font-weight:600;color:var(--text);font-size:0.78rem;">
                        <?= e($emp['first_name'].' '.$emp['last_name']) ?>
                    </a>
                    <div style="font-size:0.65rem;color:var(--text-muted);"><?= e($emp['email'] ?? '') ?></div>
                </td>
                <td style="font-size:0.75rem;"><?= e($emp['dept_name'] ?? '—') ?></td>
                <td style="font-size:0.75rem;"><?= e($emp['position_title'] ?? '—') ?></td>
                <td><span class="badge badge-secondary"><?= ucfirst(str_replace('_',' ',$emp['employment_type'])) ?></span></td>
                <td style="font-size:0.75rem;"><?= formatDate($emp['start_date']) ?></td>
                <td><?= employeeStatusBadge($emp['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
