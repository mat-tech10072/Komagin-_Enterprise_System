<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('disciplinary.view', 'view');

$pageTitle  = 'Disciplinary Records';
$activeMenu = 'disciplinary';

$activeTab  = $_GET['tab'] ?? 'disciplinary';
$deptFilter = (int)($_GET['dept'] ?? 0);
$page       = max(1,(int)($_GET['page'] ?? 1));
$perPage    = 25;

$where  = ['1=1'];
$params = [];
if ($deptFilter) { $where[] = 'e.department_id=?'; $params[] = $deptFilter; }
$whereSQL = implode(' AND ', $where);

if ($activeTab === 'disciplinary') {
    $countStmt = db()->prepare("SELECT COUNT(*) FROM disciplinary_records dr JOIN employees e ON dr.employee_id=e.id WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pagination = paginate($total,$perPage,$page);

    $stmt = db()->prepare("SELECT dr.*, e.first_name, e.last_name, e.employee_number, d.name as dept_name
        FROM disciplinary_records dr JOIN employees e ON dr.employee_id=e.id
        LEFT JOIN departments d ON e.department_id=d.id
        WHERE $whereSQL ORDER BY dr.incident_date DESC LIMIT $perPage OFFSET {$pagination['offset']}");
    $stmt->execute($params);
    $records = $stmt->fetchAll();
} else {
    $countStmt = db()->prepare("SELECT COUNT(*) FROM grievance_records gr JOIN employees e ON gr.employee_id=e.id WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pagination = paginate($total,$perPage,$page);

    $stmt = db()->prepare("SELECT gr.*, e.first_name, e.last_name, e.employee_number
        FROM grievance_records gr JOIN employees e ON gr.employee_id=e.id
        WHERE $whereSQL ORDER BY gr.created_at DESC LIMIT $perPage OFFSET {$pagination['offset']}");
    $stmt->execute($params);
    $records = $stmt->fetchAll();
}

$dTotal = (int)db()->query("SELECT COUNT(*) FROM disciplinary_records")->fetchColumn();
$gTotal = (int)db()->query("SELECT COUNT(*) FROM grievance_records")->fetchColumn();
$departments = getDepartments();
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Disciplinary & Grievances</h1>
        <p class="page-subtitle">Incident records and grievance tracking</p>
    </div>
    <div class="page-actions">
        <?php if ($activeTab === 'disciplinary'): ?>
        <button class="btn btn-primary btn-sm" data-modal-open="addDisciplinaryModal">Add Disciplinary Record</button>
        <?php else: ?>
        <button class="btn btn-primary btn-sm" data-modal-open="addGrievanceModal">Log Grievance</button>
        <?php endif; ?>
    </div>
</div>

<div class="tab-nav">
    <a href="?tab=disciplinary" class="tab-item <?= $activeTab==='disciplinary'?'active':''?>">Disciplinary (<?= $dTotal ?>)</a>
    <a href="?tab=grievances" class="tab-item <?= $activeTab==='grievances'?'active':''?>">Grievances (<?= $gTotal ?>)</a>
</div>

<div class="card">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
            <select class="form-select" name="dept" style="width:auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':''?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);"><?= $total ?> records</div>
    </div>

    <div class="table-wrapper" style="border:none;">
        <?php if (empty($records)): ?>
        <div class="empty-state"><div class="empty-state-title">No records found</div></div>
        <?php elseif ($activeTab === 'disciplinary'): ?>
        <table class="table">
            <thead><tr><th>Employee</th><th>Dept</th><th>Incident Type</th><th>Incident Date</th><th>Action Taken</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($records as $rec): ?>
            <tr>
                <td>
                    <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $rec['employee_id'] ?>" style="font-weight:600;color:var(--text);font-size:0.78rem;">
                        <?= e($rec['first_name'].' '.$rec['last_name']) ?>
                    </a>
                    <div class="emp-num"><?= e($rec['employee_number']) ?></div>
                </td>
                <td style="font-size:0.72rem;"><?= e($rec['dept_name'] ?? '—') ?></td>
                <td>
                    <?php $sc=['verbal_warning'=>'badge-info','written_warning'=>'badge-warning','final_warning'=>'badge-danger','suspension'=>'badge-danger','dismissal'=>'badge-danger'];?>
                    <span class="badge <?= $sc[$rec['case_type'] ?? ''] ?? 'badge-secondary' ?>"><?= ucwords(str_replace('_',' ',$rec['case_type'] ?? '—')) ?></span>
                </td>
                <td style="font-size:0.75rem;"><?= formatDate($rec['incident_date']) ?></td>
                <td style="font-size:0.75rem;"><?= e($rec['action_taken'] ? mb_strimwidth($rec['action_taken'],0,50,'…') : '—') ?></td>
                <td>
                    <?php $sc=['open'=>'badge-warning','investigating'=>'badge-info','closed'=>'badge-secondary','appealed'=>'badge-warning'];?>
                    <span class="badge <?= $sc[$rec['status']] ?? 'badge-secondary' ?>"><?= ucfirst($rec['status']) ?></span>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/modules/disciplinary/view.php?id=<?= $rec['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:0.68rem;">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Employee</th><th>Subject</th><th>Date Filed</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($records as $rec): ?>
            <tr>
                <td>
                    <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $rec['employee_id'] ?>" style="font-weight:600;color:var(--text);font-size:0.78rem;">
                        <?= e($rec['first_name'].' '.$rec['last_name']) ?>
                    </a>
                    <div class="emp-num"><?= e($rec['employee_number']) ?></div>
                </td>
                <td style="font-size:0.75rem;"><?= e(mb_strimwidth($rec['subject'] ?? '',0,60,'…')) ?></td>
                <td style="font-size:0.75rem;"><?= formatDate($rec['created_at']) ?></td>
                <td>
                    <?php $sc=['open'=>'badge-warning','in_progress'=>'badge-info','resolved'=>'badge-success','closed'=>'badge-secondary'];?>
                    <span class="badge <?= $sc[$rec['status']] ?? 'badge-secondary' ?>"><?= ucfirst(str_replace('_',' ',$rec['status'])) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Disciplinary Modal -->
<div class="modal-overlay" id="addDisciplinaryModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">Add Disciplinary Record</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/disciplinary/save.php" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="record_type" value="disciplinary">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Employee <span class="required">*</span></label>
                        <select class="form-select" name="employee_id" required>
                            <option value="">Select employee</option>
                            <?php
                            $allEmps = db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as n FROM employees WHERE status NOT IN ('archived','resigned','terminated') ORDER BY first_name")->fetchAll();
                            foreach ($allEmps as $ae): ?>
                                <option value="<?= $ae['id'] ?>"><?= e($ae['n']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Incident Type <span class="required">*</span></label>
                        <select class="form-select" name="incident_type" required>
                            <option value="verbal_warning">Verbal Warning</option>
                            <option value="written_warning">Written Warning</option>
                            <option value="final_warning">Final Written Warning</option>
                            <option value="suspension">Suspension</option>
                            <option value="dismissal">Dismissal</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Incident Date <span class="required">*</span></label>
                    <input type="date" class="form-control" name="incident_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description <span class="required">*</span></label>
                    <textarea class="form-control" name="description" rows="3" required></textarea>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Action Taken</label>
                    <textarea class="form-control" name="action_taken" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Save Record</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Grievance Modal -->
<div class="modal-overlay" id="addGrievanceModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">Log Grievance</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/disciplinary/save.php" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="record_type" value="grievance">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Employee <span class="required">*</span></label>
                    <select class="form-select" name="employee_id" required>
                        <option value="">Select employee</option>
                        <?php foreach ($allEmps as $ae): ?>
                            <option value="<?= $ae['id'] ?>"><?= e($ae['n']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject <span class="required">*</span></label>
                    <input type="text" class="form-control" name="subject" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Details</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Log Grievance</button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
