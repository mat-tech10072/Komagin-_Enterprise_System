<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('attendance.view', 'view');

$pageTitle  = 'Attendance';
$activeMenu = 'attendance';

$today    = date('Y-m-d');
$viewDate = $_GET['date'] ?? $today;
$deptId   = (int)($_GET['dept'] ?? 0);
$search   = trim($_GET['search'] ?? '');
$page     = max(1,(int)($_GET['page'] ?? 1));
$perPage  = 30;

$where  = ['a.attendance_date = ?'];
$params = [$viewDate];
if ($deptId) { $where[] = 'e.department_id = ?'; $params[] = $deptId; }
if ($search) {
    $where[] = '(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_number LIKE ?)';
    $s = "%$search%"; $params = array_merge($params,[$s,$s,$s]);
}
$whereSQL = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total,$perPage,$page);

$stmt = db()->prepare("SELECT a.*, e.first_name, e.last_name, e.employee_number, d.name as dept_name
    FROM attendance a JOIN employees e ON a.employee_id=e.id
    LEFT JOIN departments d ON e.department_id=d.id
    WHERE $whereSQL ORDER BY e.last_name LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$records = $stmt->fetchAll();

$departments = getDepartments();

// Day summary
$sumStmt = db()->prepare("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN a.status='present' OR a.sign_in IS NOT NULL THEN 1 ELSE 0 END) as present,
    SUM(is_absent) as absent, SUM(is_late) as late, SUM(is_on_leave) as on_leave,
    COUNT(CASE WHEN a.sign_in IS NOT NULL AND a.sign_out IS NULL THEN 1 END) as still_in
    FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE a.attendance_date=?");
$sumStmt->execute([$viewDate]); $daySum = $sumStmt->fetch();

// KOM-098: is_absent defaults to 0 and is never written anywhere (a row
// only ever gets created on kiosk sign-in), so SUM(is_absent) above is
// structurally guaranteed to be 0 for every day. Recomputed as
// active/probation employees (respecting the same department filter as
// the rest of this page) who have no attendance row at all for this date.
$absentWhere = ["e.status IN ('active','probation')"];
if ($deptId) { $absentWhere[] = 'e.department_id = ?'; }
$absentStmt = db()->prepare("SELECT COUNT(*) FROM employees e
    WHERE " . implode(' AND ', $absentWhere) . "
    AND NOT EXISTS (SELECT 1 FROM attendance a WHERE a.employee_id = e.id AND a.attendance_date = ?)");
$absentStmt->execute($deptId ? [$deptId, $viewDate] : [$viewDate]);
// Phase 5, Stage 5.3: on a non-working day (weekend/holiday), "absent"
// is deliberately 0 rather than the full headcount — no one is expected
// to be present on a day off.
$daySum['absent'] = isWorkingDay($viewDate) ? (int)$absentStmt->fetchColumn() : 0;
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Attendance</h1>
        <p class="page-subtitle"><?= formatDate($viewDate, 'l, d F Y') ?></p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/modules/attendance/kiosk.php" class="btn btn-primary btn-sm" target="_blank">Open Kiosk</a>
        <a href="<?= APP_URL ?>/modules/timesheets/index.php" class="btn btn-secondary btn-sm">Timesheets</a>
    </div>
</div>

<!-- Date Nav -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="?date=<?= date('Y-m-d',strtotime($viewDate.' -1 day')) ?>" class="btn btn-secondary btn-sm">← Prev</a>
    <form method="GET" style="display:flex;align-items:center;gap:8px;">
        <input type="date" class="form-control" name="date" value="<?= e($viewDate) ?>" style="width:auto;">
        <button type="submit" class="btn btn-secondary btn-sm">Go</button>
    </form>
    <a href="?date=<?= date('Y-m-d',strtotime($viewDate.' +1 day')) ?>" class="btn btn-secondary btn-sm">Next →</a>
    <?php if ($viewDate !== $today): ?>
        <a href="?date=<?= $today ?>" class="btn btn-primary btn-sm">Today</a>
    <?php endif; ?>
</div>

<!-- Day Summary -->
<div class="kpi-grid" style="grid-template-columns:repeat(6,1fr);margin-bottom:24px;">
    <?php $stats = [
        ['Present', $daySum['present'] ?? 0, 'kpi-success'],
        ['Absent', $daySum['absent'] ?? 0, 'kpi-danger'],
        ['Late', $daySum['late'] ?? 0, 'kpi-warning'],
        ['On Leave', $daySum['on_leave'] ?? 0, 'kpi-info'],
        ['Still In', $daySum['still_in'] ?? 0, 'kpi-primary'],
        ['Total', $daySum['total'] ?? 0, ''],
    ]; ?>
    <?php foreach ($stats as [$label,$val,$cls]): ?>
    <div class="kpi-card <?= $cls ?>">
        <div class="kpi-card-label"><?= $label ?></div>
        <div class="kpi-card-value" style="font-size:1.5rem;"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <input type="hidden" name="date" value="<?= e($viewDate) ?>">
            <select class="form-select" name="dept" style="width:auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptId==$d['id']?'selected':'' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" class="form-control" name="search" placeholder="Search employee…" value="<?= e($search) ?>" style="max-width:200px;">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-wrapper" style="border:none;border-radius:0;">
        <?php if (empty($records)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No attendance records for <?= formatDate($viewDate) ?></div>
            <div class="empty-state-desc">No employees have clocked in yet.</div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Dept</th>
                    <th>Sign In</th>
                    <th>Break</th>
                    <th>Sign Out</th>
                    <th>Hours</th>
                    <th>OT</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td>
                    <div class="emp-row-info">
                        <div class="emp-avatar" style="width:26px;height:26px;font-size:0.65rem;">
                            <?= strtoupper(substr($r['first_name'],0,1)) ?>
                        </div>
                        <div>
                            <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $r['employee_id'] ?>" style="font-size:0.78rem;font-weight:600;color:var(--text);">
                                <?= e($r['first_name'].' '.$r['last_name']) ?>
                            </a>
                            <div class="emp-num"><?= e($r['employee_number']) ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-size:0.72rem;color:var(--text-secondary);"><?= e($r['dept_name']??'—') ?></td>
                <td style="font-size:0.75rem;">
                    <?= formatTime($r['sign_in']) ?>
                    <?php if ($r['is_late']): ?><br><span style="font-size:0.65rem;color:var(--warning);">+<?= $r['late_minutes'] ?>m late</span><?php endif; ?>
                </td>
                <td style="font-size:0.75rem;">
                    <?php if ($r['break_out'] && $r['break_in']): ?>
                        <?= minutesToHoursMinutes((int)$r['break_duration_minutes']) ?>
                    <?php elseif ($r['break_out'] && !$r['break_in']): ?>
                        <span class="badge badge-warning">On Break</span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td style="font-size:0.75rem;"><?= formatTime($r['sign_out']) ?></td>
                <td style="font-weight:600;font-size:0.78rem;"><?= $r['total_hours_worked'] ?? '—' ?></td>
                <td style="font-size:0.75px;">
                    <?= $r['overtime_hours'] > 0 ? '<span style="color:var(--warning);font-weight:600;">'.$r['overtime_hours'].'h</span>' : '—' ?>
                </td>
                <td><?= attendanceStatusBadge($r['status']) ?></td>
                <td>
                    <a href="<?= APP_URL ?>/modules/timesheets/edit.php?id=<?= $r['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
