<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('onboarding.view', 'view');

$pageTitle  = 'Onboarding';
$activeMenu = 'onboarding';

$deptFilter = (int)($_GET['dept'] ?? 0);
$page       = max(1,(int)($_GET['page'] ?? 1));
$perPage    = 25;

// Employees who started within last 90 days or still on probation
$where  = ['(e.status="probation" OR e.start_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY))'];
$params = [];
if ($deptFilter) { $where[] = 'e.department_id=?'; $params[] = $deptFilter; }
$whereSQL = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM employees e WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total,$perPage,$page);

$stmt = db()->prepare("SELECT e.*, d.name as dept_name, p.title as position_title
    FROM employees e
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN positions p ON e.position_id=p.id
    WHERE $whereSQL ORDER BY e.start_date DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $pagination['offset']]));
$newHires = $stmt->fetchAll();

// Onboarding checklists
$checklists = db()->query("SELECT oc.*, e.first_name, e.last_name, e.employee_number
    FROM onboarding_checklists oc JOIN employees e ON oc.employee_id=e.id
    WHERE oc.is_completed = 0 ORDER BY oc.created_at DESC LIMIT 20")->fetchAll();

$departments = getDepartments();
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Onboarding</h1>
        <p class="page-subtitle">New hires and onboarding checklists</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary btn-sm" data-modal-open="newChecklistModal">Create Checklist</button>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;">
    <!-- New Hires -->
    <div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">New Hires (last 90 days / probation)</span>
                <span style="font-size:0.72rem;color:var(--text-muted);"><?= $total ?> employees</span>
            </div>
            <div class="filters-bar" style="border-top:none;">
                <form method="GET" style="display:contents;">
                    <select class="form-select" name="dept" style="width:auto;" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':''?>><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="table-wrapper" style="border:none;">
                <?php if (empty($newHires)): ?>
                <div class="empty-state"><div class="empty-state-title">No recent hires</div></div>
                <?php else: ?>
                <table class="table">
                    <thead><tr><th>Employee</th><th>Dept</th><th>Start Date</th><th>Probation End</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($newHires as $emp): ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $emp['id'] ?>" style="font-weight:600;color:var(--text);font-size:0.78rem;">
                                <?= e($emp['first_name'].' '.$emp['last_name']) ?>
                            </a>
                            <div class="emp-num"><?= e($emp['employee_number']) ?></div>
                        </td>
                        <td style="font-size:0.72rem;"><?= e($emp['dept_name'] ?? '—') ?></td>
                        <td style="font-size:0.75rem;"><?= formatDate($emp['start_date']) ?></td>
                        <td style="font-size:0.75rem;">
                            <?php if ($emp['probation_end']): ?>
                                <?php $daysLeft = (int)ceil((strtotime($emp['probation_end'])-time())/(86400)); ?>
                                <?= formatDate($emp['probation_end']) ?>
                                <?php if ($daysLeft <= 14 && $daysLeft > 0): ?>
                                    <span class="badge badge-warning"><?= $daysLeft ?>d</span>
                                <?php elseif ($daysLeft <= 0): ?>
                                    <span class="badge badge-danger">Due</span>
                                <?php endif; ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?= employeeStatusBadge($emp['status']) ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $emp['id'] ?>" class="btn btn-ghost btn-sm">Profile</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Active Checklists -->
    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Active Checklists</span></div>
            <?php if (empty($checklists)): ?>
            <div class="empty-state" style="padding:24px;"><div class="empty-state-title" style="font-size:0.8rem;">No active checklists</div></div>
            <?php else: ?>
            <div style="padding:0;">
                <?php foreach ($checklists as $cl): ?>
                <div style="padding:12px 16px;border-bottom:1px solid var(--border);">
                    <div style="font-weight:600;font-size:0.78rem;"><?= e($cl['first_name'].' '.$cl['last_name']) ?></div>
                    <div class="emp-num" style="margin-bottom:6px;"><?= e($cl['employee_number']) ?></div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="flex:1;height:5px;background:var(--bg);border-radius:3px;overflow:hidden;">
                            <div style="width:<?= $cl['completion_percentage'] ?? 0 ?>%;height:100%;background:var(--success);border-radius:3px;"></div>
                        </div>
                        <span style="font-size:0.65rem;color:var(--text-muted);"><?= $cl['completion_percentage'] ?? 0 ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- New Checklist Modal -->
<div class="modal-overlay" id="newChecklistModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Create Onboarding Checklist</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/onboarding/save.php" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Employee <span class="required">*</span></label>
                    <select class="form-select" name="employee_id" required>
                        <option value="">Select employee</option>
                        <?php
                        $allEmps = db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as n FROM employees WHERE status IN ('active','probation') ORDER BY first_name")->fetchAll();
                        foreach ($allEmps as $ae): ?>
                            <option value="<?= $ae['id'] ?>"><?= e($ae['n']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Create Checklist</button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
