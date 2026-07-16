<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('leave.view', 'view');

$pageTitle  = 'Leave Management';
$activeMenu = 'leave';

$status  = $_GET['status'] ?? '';
$empId   = (int)($_GET['emp'] ?? 0);
$deptId  = (int)($_GET['dept'] ?? 0);
$typeId  = (int)($_GET['type'] ?? 0);
$month   = $_GET['month'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

// Build query
$where  = ['1=1'];
$params = [];
if ($status) { $where[] = 'la.status = ?'; $params[] = $status; }
if ($empId)  { $where[] = 'la.employee_id = ?'; $params[] = $empId; }
if ($deptId) { $where[] = 'e.department_id = ?'; $params[] = $deptId; }
if ($typeId) { $where[] = 'la.leave_type_id = ?'; $params[] = $typeId; }
if ($month)  { $where[] = "DATE_FORMAT(la.start_date,'%Y-%m') = ?"; $params[] = $month; }

$whereSQL = implode(' AND ', $where);
$countStmt = db()->prepare("SELECT COUNT(*) FROM leave_applications la JOIN employees e ON la.employee_id=e.id WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

$stmt = db()->prepare("SELECT la.*, lt.name as leave_type_name,
    e.first_name, e.last_name, e.employee_number, d.name as dept_name
    FROM leave_applications la
    JOIN employees e ON la.employee_id = e.id
    JOIN leave_types lt ON la.leave_type_id = lt.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE $whereSQL
    ORDER BY la.created_at DESC
    LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$applications = $stmt->fetchAll();

$departments = getDepartments();
$leaveTypes  = getLeaveTypes();

// Counts per status
$counts = db()->query("SELECT status, COUNT(*) as cnt FROM leave_applications GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Leave Management</h1>
        <p class="page-subtitle">Manage and approve employee leave applications</p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/modules/leave/apply.php" class="btn btn-primary btn-sm">New Application</a>
        <a href="<?= APP_URL ?>/modules/leave/types.php" class="btn btn-secondary btn-sm">Leave Types</a>
    </div>
</div>

<!-- Status Tabs -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="?" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-secondary' ?>">All (<?= array_sum($counts) ?>)</a>
    <a href="?status=pending" class="btn btn-sm <?= $status==='pending' ? 'btn-warning' : 'btn-secondary' ?>">Pending (<?= $counts['pending'] ?? 0 ?>)</a>
    <a href="?status=approved" class="btn btn-sm <?= $status==='approved' ? 'btn-success' : 'btn-secondary' ?>">Approved (<?= $counts['approved'] ?? 0 ?>)</a>
    <a href="?status=rejected" class="btn btn-sm <?= $status==='rejected' ? 'btn-danger' : 'btn-secondary' ?>">Rejected (<?= $counts['rejected'] ?? 0 ?>)</a>
    <a href="?status=cancelled" class="btn btn-sm btn-secondary">Cancelled (<?= $counts['cancelled'] ?? 0 ?>)</a>
</div>

<div class="card">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <select class="form-select" name="dept">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptId==$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="type">
                <option value="">All Leave Types</option>
                <?php foreach ($leaveTypes as $lt): ?>
                    <option value="<?= $lt['id'] ?>" <?= $typeId==$lt['id'] ? 'selected' : '' ?>><?= e($lt['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="month" class="form-control" name="month" value="<?= e($month) ?>">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);">
            <?= $pagination['offset']+1 ?>–<?= min($pagination['offset']+$perPage,$total) ?> of <?= $total ?>
        </div>
    </div>

    <div class="table-wrapper" style="border:none;border-radius:0;">
        <?php if (empty($applications)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No leave applications found</div>
            <div class="empty-state-desc">Try adjusting your filters.</div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Applied</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $la): ?>
            <tr>
                <td>
                    <div class="emp-row-info">
                        <div class="emp-avatar" style="width:26px;height:26px;font-size:0.65rem;">
                            <?= strtoupper(substr($la['first_name'],0,1)) ?>
                        </div>
                        <div>
                            <div class="emp-name" style="font-size:0.78rem;"><?= e($la['first_name'].' '.$la['last_name']) ?></div>
                            <div class="emp-num"><?= e($la['dept_name'] ?? '—') ?></div>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-info"><?= e($la['leave_type_name']) ?></span></td>
                <td style="font-size:0.75rem;"><?= formatDate($la['start_date']) ?></td>
                <td style="font-size:0.75rem;"><?= formatDate($la['end_date']) ?></td>
                <td style="font-weight:600;"><?= $la['total_days'] ?></td>
                <td><?= leaveStatusBadge($la['status']) ?></td>
                <td style="font-size:0.72rem;color:var(--text-muted);"><?= formatDate($la['created_at']) ?></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= APP_URL ?>/modules/leave/view.php?id=<?= $la['id'] ?>" class="btn btn-ghost btn-sm btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <?php if ($la['status'] === 'pending' && canApprove('leave.approve')): ?>
                        <a href="<?= APP_URL ?>/modules/leave/approve.php?id=<?= $la['id'] ?>&action=approve" class="btn btn-ghost btn-sm btn-icon" title="Approve">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        </a>
                        <a href="<?= APP_URL ?>/modules/leave/approve.php?id=<?= $la['id'] ?>&action=reject" class="btn btn-ghost btn-sm btn-icon" title="Reject">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer" style="display:flex;justify-content:flex-end;">
        <ul class="pagination">
            <?php for ($i=max(1,$page-2);$i<=min($pagination['total_pages'],$page+2);$i++): ?>
            <li class="page-item <?= $i===$page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&dept=<?= $deptId ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
