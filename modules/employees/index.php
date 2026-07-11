<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('employees.view', 'view');

$pageTitle  = 'Employees';
$activeMenu = 'employees';

// Filters
$search   = trim($_GET['search'] ?? '');
$status   = $_GET['status'] ?? '';
$deptId   = (int)($_GET['dept'] ?? 0);
$empType  = $_GET['type'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 25;

// Build query
$where  = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_number LIKE ? OR e.email LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}
if ($status) {
    $where[] = "e.status = ?";
    $params[] = $status;
}
if ($deptId) {
    $where[] = "e.department_id = ?";
    $params[] = $deptId;
}
if ($empType) {
    $where[] = "e.employment_type = ?";
    $params[] = $empType;
}

$whereSQL = implode(' AND ', $where);

// Count
$countStmt = db()->prepare("SELECT COUNT(*) FROM employees e WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $perPage, $page);

// Fetch
$stmt = db()->prepare("SELECT e.*, d.name as dept_name, p.title as pos_title
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions p ON e.position_id = p.id
    WHERE $whereSQL
    ORDER BY e.created_at DESC
    LIMIT {$perPage} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$employees = $stmt->fetchAll();

$departments = getDepartments();

// Stats
$statusCounts = db()->query("SELECT status, COUNT(*) as cnt FROM employees GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Employees</h1>
        <p class="page-subtitle"><?= number_format($total) ?> total records</p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/modules/employees/add.php" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Employee
        </a>
        <a href="<?= APP_URL ?>/modules/employees/pending_updates.php" class="btn btn-secondary btn-sm">
            Profile Updates
        </a>
        <a href="<?= APP_URL ?>/modules/reports/employees.php" class="btn btn-secondary btn-sm">
            Export
        </a>
    </div>
</div>

<!-- Status Quick Filters -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
    <a href="?" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-secondary' ?>">All (<?= $total ?>)</a>
    <a href="?status=active" class="btn btn-sm <?= $status==='active' ? 'btn-success' : 'btn-secondary' ?>">Active (<?= $statusCounts['active'] ?? 0 ?>)</a>
    <a href="?status=probation" class="btn btn-sm <?= $status==='probation' ? 'btn-warning' : 'btn-secondary' ?>">Probation (<?= $statusCounts['probation'] ?? 0 ?>)</a>
    <a href="?status=on_leave" class="btn btn-sm <?= $status==='on_leave' ? 'btn-secondary' : 'btn-secondary' ?>">On Leave (<?= $statusCounts['on_leave'] ?? 0 ?>)</a>
    <a href="?status=resigned" class="btn btn-sm btn-secondary">Resigned (<?= $statusCounts['resigned'] ?? 0 ?>)</a>
    <a href="?status=terminated" class="btn btn-sm btn-secondary">Terminated (<?= $statusCounts['terminated'] ?? 0 ?>)</a>
</div>

<!-- Employees Table -->
<div class="card">
    <!-- Filters -->
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <input type="text" class="form-control" name="search" placeholder="Search name, number, email…"
                   value="<?= e($search) ?>" style="max-width:220px;">
            <select class="form-select" name="dept" style="width:auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="type" style="width:auto;">
                <option value="">All Types</option>
                <option value="full_time" <?= $empType==='full_time' ? 'selected' : '' ?>>Full Time</option>
                <option value="part_time" <?= $empType==='part_time' ? 'selected' : '' ?>>Part Time</option>
                <option value="contract" <?= $empType==='contract' ? 'selected' : '' ?>>Contract</option>
                <option value="casual" <?= $empType==='casual' ? 'selected' : '' ?>>Casual</option>
                <option value="intern" <?= $empType==='intern' ? 'selected' : '' ?>>Intern</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if ($search || $deptId || $empType): ?>
                <a href="?<?= $status ? 'status='.$status : '' ?>" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
        </form>

        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);">
            <?= $pagination['offset'] + 1 ?>–<?= min($pagination['offset'] + $perPage, $total) ?> of <?= $total ?>
        </div>
    </div>

    <!-- Table -->
    <div class="table-wrapper" style="border:none;border-radius:0;">
        <?php if (empty($employees)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div class="empty-state-title">No employees found</div>
            <div class="empty-state-desc">Try adjusting your filters or add a new employee.</div>
            <a href="<?= APP_URL ?>/modules/employees/add.php" class="btn btn-primary btn-sm">Add Employee</a>
        </div>
        <?php else: ?>
        <table class="table" id="employeeTable">
            <thead>
                <tr>
                    <th data-sort>Employee</th>
                    <th data-sort>Emp. Number</th>
                    <th data-sort>Department</th>
                    <th data-sort>Position</th>
                    <th data-sort>Type</th>
                    <th data-sort>Start Date</th>
                    <th data-sort>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                <tr>
                    <td>
                        <div class="emp-row-info">
                            <div class="emp-avatar">
                                <?php if (!empty($emp['photo'])): ?>
                                    <img src="<?= APP_URL ?>/<?= e($emp['photo']) ?>" alt="">
                                <?php else: ?>
                                    <?= strtoupper(substr($emp['first_name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="emp-name">
                                    <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $emp['id'] ?>" style="color:var(--text);text-decoration:none;">
                                        <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                    </a>
                                </div>
                                <div class="emp-num"><?= e($emp['email'] ?? '—') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><code style="font-size:0.72rem;background:var(--bg);padding:2px 6px;border-radius:4px;border:1px solid var(--border);"><?= e($emp['employee_number']) ?></code></td>
                    <td style="color:var(--text-secondary);"><?= e($emp['dept_name'] ?? '—') ?></td>
                    <td style="color:var(--text-secondary);"><?= e($emp['pos_title'] ?? '—') ?></td>
                    <td><span style="font-size:0.72rem;color:var(--text-secondary);"><?= ucfirst(str_replace('_',' ',$emp['employment_type'])) ?></span></td>
                    <td style="font-size:0.75rem;"><?= formatDate($emp['start_date']) ?></td>
                    <td><?= employeeStatusBadge($emp['status']) ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $emp['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="View">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <?php if (canEdit('employees.edit')): ?>
                            <a href="<?= APP_URL ?>/modules/employees/edit.php?id=<?= $emp['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if (canDelete('employees.delete')): ?>
                            <a href="<?= APP_URL ?>/modules/employees/delete.php?id=<?= $emp['id'] ?>"
                               class="btn btn-ghost btn-sm btn-icon"
                               title="Permanently Delete"
                               style="color:var(--danger);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
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

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:0.72rem;color:var(--text-muted);">
            Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
        </span>
        <ul class="pagination">
            <li class="page-item <?= !$pagination['has_prev'] ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>&search=<?= urlencode($search) ?>&dept=<?= $deptId ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($empType) ?>">
                    ← Prev
                </a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($pagination['total_pages'], $page + 2); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&dept=<?= $deptId ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($empType) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>&search=<?= urlencode($search) ?>&dept=<?= $deptId ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($empType) ?>">
                    Next →
                </a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
