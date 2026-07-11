<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('timesheets.view');

$pageTitle  = 'Timesheets';
$activeMenu = 'timesheets';

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-d');
$deptId   = (int)($_GET['dept'] ?? 0);
$search   = trim($_GET['search'] ?? '');
$status   = $_GET['status'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 30;

$where  = ['a.attendance_date BETWEEN ? AND ?'];
$params = [$dateFrom, $dateTo];

if ($deptId) { $where[] = 'e.department_id = ?'; $params[] = $deptId; }
if ($search) {
    $where[] = '(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_number LIKE ?)';
    $s = "%$search%"; $params = array_merge($params, [$s,$s,$s]);
}
if ($status === 'approved') { $where[] = 'a.is_approved = 1'; }
elseif ($status === 'pending') { $where[] = 'a.is_approved = 0'; }
elseif ($status === 'locked')  { $where[] = 'a.is_locked = 1'; }

$whereSQL = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

$stmt = db()->prepare("SELECT a.*, e.first_name, e.last_name, e.employee_number, d.name as dept_name
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE $whereSQL
    ORDER BY a.attendance_date DESC, e.last_name
    LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$records = $stmt->fetchAll();

$departments = getDepartments();

// Summary stats
$summaryStmt = db()->prepare("SELECT
    COUNT(*) as total,
    SUM(is_absent) as absent,
    SUM(is_late) as late,
    COALESCE(SUM(total_hours_worked),0) as total_hours,
    COALESCE(SUM(overtime_hours),0) as total_ot,
    SUM(is_approved) as approved
    FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE $whereSQL");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Timesheets</h1>
        <p class="page-subtitle">Review, edit, and approve employee timesheets</p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/modules/timesheets/corrections.php" class="btn btn-secondary btn-sm">Correction Requests</a>
        <a href="<?= APP_URL ?>/modules/timesheets/overtime.php" class="btn btn-secondary btn-sm">Overtime</a>
        <a href="<?= APP_URL ?>/modules/reports/timesheets.php" class="btn btn-secondary btn-sm">Export</a>
    </div>
</div>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:24px;">
    <?php $stats = [
        ['Total Records',  nf($summary['total'])],
        ['Total Hours',    nf($summary['total_hours'],1)],
        ['Overtime Hours', nf($summary['total_ot'],1)],
        ['Absent Days',    nf($summary['absent'])],
        ['Late Records',   nf($summary['late'])],
        ['Approved',       nf($summary['approved'])],
    ]; foreach ($stats as [$label,$val]): ?>
    <div class="kpi-card">
        <div class="kpi-card-label"><?= $label ?></div>
        <div class="kpi-card-value" style="font-size:1.4rem;"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <!-- Filters -->
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <input type="date" class="form-control" name="from" value="<?= e($dateFrom) ?>">
            <span style="font-size:0.72rem;color:var(--text-muted);">to</span>
            <input type="date" class="form-control" name="to" value="<?= e($dateTo) ?>">
            <select class="form-select" name="dept" style="width:auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptId==$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="status" style="width:auto;">
                <option value="">All Status</option>
                <option value="pending" <?= $status==='pending' ? 'selected' : '' ?>>Pending Approval</option>
                <option value="approved" <?= $status==='approved' ? 'selected' : '' ?>>Approved</option>
                <option value="locked" <?= $status==='locked' ? 'selected' : '' ?>>Locked</option>
            </select>
            <input type="text" class="form-control" name="search" placeholder="Search employee…" value="<?= e($search) ?>" style="max-width:180px;">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);">
            <?= $pagination['offset']+1 ?>–<?= min($pagination['offset']+$perPage,$total) ?> of <?= $total ?>
        </div>
    </div>

    <!-- Table -->
    <div class="table-wrapper" style="border:none;border-radius:0;">
        <?php if (empty($records)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No timesheet records found</div>
            <div class="empty-state-desc">Adjust your date range or filters.</div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Sign In</th>
                    <th>Break</th>
                    <th>Sign Out</th>
                    <th>Hours</th>
                    <th>OT</th>
                    <th>Flags</th>
                    <th>Approved</th>
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
                            <div class="emp-name" style="font-size:0.78rem;"><?= e($r['first_name'].' '.$r['last_name']) ?></div>
                            <div class="emp-num"><?= e($r['employee_number']) ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-size:0.75rem;"><?= formatDate($r['attendance_date']) ?></td>
                <td style="font-size:0.75rem;"><?= formatTime($r['sign_in']) ?></td>
                <td style="font-size:0.75rem;">
                    <?php if ($r['break_out'] && $r['break_in']): ?>
                        <?= minutesToHoursMinutes((int)$r['break_duration_minutes']) ?>
                    <?php elseif ($r['break_out']): ?>
                        <span class="badge badge-warning">Out</span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td style="font-size:0.75rem;"><?= formatTime($r['sign_out']) ?></td>
                <td style="font-size:0.75rem;font-weight:600;"><?= $r['total_hours_worked'] ?? '—' ?></td>
                <td style="font-size:0.75rem;">
                    <?= $r['overtime_hours'] > 0 ? '<span style="color:var(--warning);font-weight:600;">'.$r['overtime_hours'].'</span>' : '—' ?>
                </td>
                <td>
                    <?php if ($r['is_late']): ?><span class="badge badge-warning">Late</span> <?php endif; ?>
                    <?php if ($r['is_early_departure']): ?><span class="badge badge-warning">Early</span> <?php endif; ?>
                    <?php if ($r['is_absent']): ?><span class="badge badge-danger">Absent</span> <?php endif; ?>
                    <?php if ($r['is_manually_adjusted']): ?><span class="badge badge-info">Edited</span> <?php endif; ?>
                    <?php if (!$r['sign_out'] && $r['sign_in'] && $r['attendance_date'] < date('Y-m-d')): ?>
                        <span class="badge badge-danger">No Out</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($r['is_locked']): ?>
                        <span class="badge badge-secondary">Locked</span>
                    <?php elseif ($r['is_approved']): ?>
                        <span class="badge badge-success">Approved</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= APP_URL ?>/modules/timesheets/edit.php?id=<?= $r['id'] ?>"
                           class="btn btn-ghost btn-sm btn-icon" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <?php if (!$r['is_approved'] && !$r['is_locked']): ?>
                        <form method="POST" action="<?= APP_URL ?>/modules/timesheets/approve.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-ghost btn-sm btn-icon" title="Approve">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:0.72rem;color:var(--text-muted);">Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></span>
        <ul class="pagination">
            <?php $qs = http_build_query(['from'=>$dateFrom,'to'=>$dateTo,'dept'=>$deptId,'status'=>$status,'search'=>$search]); ?>
            <li class="page-item <?= !$pagination['has_prev'] ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page-1 ?>&<?= $qs ?>">← Prev</a>
            </li>
            <?php for ($i=max(1,$page-2);$i<=min($pagination['total_pages'],$page+2);$i++): ?>
            <li class="page-item <?= $i===$page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&<?= $qs ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page+1 ?>&<?= $qs ?>">Next →</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
