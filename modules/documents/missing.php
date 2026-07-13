<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('documents.missing', 'view');

$pageTitle  = 'Missing Documents';
$activeMenu = 'documents';

$deptFilter = (int)($_GET['dept'] ?? 0);

$requiredDocs = ['id_document' => 'ID Document', 'contract' => 'Employment Contract', 'bank_document' => 'Bank Document'];

$where  = ['e.status IN ("active","probation")'];
$params = [];
if ($deptFilter) { $where[] = 'e.department_id=?'; $params[] = $deptFilter; }
$whereSQL = implode(' AND ', $where);

$stmt = db()->prepare("SELECT e.*, d.name as dept_name
    FROM employees e LEFT JOIN departments d ON e.department_id=d.id
    WHERE $whereSQL ORDER BY e.first_name");
$stmt->execute($params);
$employees = $stmt->fetchAll();

// KOM-048: previously one SELECT per employee (N+1) — a single query
// for all filtered employees' document categories, grouped in PHP.
$existingByEmp = [];
if ($employees) {
    $empIds = array_column($employees, 'id');
    $in = implode(',', array_fill(0, count($empIds), '?'));
    $docStmt = db()->prepare("SELECT DISTINCT employee_id, category FROM employee_documents WHERE employee_id IN ($in) AND is_deleted=0");
    $docStmt->execute($empIds);
    foreach ($docStmt->fetchAll() as $row) {
        $existingByEmp[$row['employee_id']][] = $row['category'];
    }
}

$missingList = [];
foreach ($employees as $emp) {
    $existing = $existingByEmp[$emp['id']] ?? [];

    $missing = [];
    foreach ($requiredDocs as $cat => $label) {
        if (!in_array($cat, $existing)) {
            $missing[] = $label;
        }
    }

    if (!empty($missing)) {
        $missingList[] = ['emp' => $emp, 'missing' => $missing];
    }
}

$departments = getDepartments();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/documents/index.php">Documents</a></li>
                <li class="breadcrumb-item active">Missing Documents</li>
            </ol>
        </nav>
        <h1 class="page-title">Missing Documents</h1>
        <p class="page-subtitle"><?= count($missingList) ?> employees with missing required documents</p>
    </div>
</div>

<div class="card">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <select class="form-select" name="dept" style="width:auto;" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':''?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);">
            Required: <?= implode(', ', array_values($requiredDocs)) ?>
        </div>
    </div>

    <div class="table-wrapper" style="border:none;">
        <?php if (empty($missingList)): ?>
        <div class="empty-state">
            <div class="empty-state-title">All employees have required documents</div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Employee</th><th>Department</th><th>Missing Documents</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($missingList as $item): ?>
            <tr>
                <td>
                    <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $item['emp']['id'] ?>&tab=documents" style="font-weight:600;color:var(--text);font-size:0.78rem;">
                        <?= e($item['emp']['first_name'].' '.$item['emp']['last_name']) ?>
                    </a>
                    <div class="emp-num"><?= e($item['emp']['employee_number']) ?></div>
                </td>
                <td style="font-size:0.72rem;"><?= e($item['emp']['dept_name'] ?? '—') ?></td>
                <td>
                    <?php foreach ($item['missing'] as $miss): ?>
                        <span class="badge badge-danger" style="margin-right:2px;"><?= e($miss) ?></span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/modules/documents/upload.php?emp=<?= $item['emp']['id'] ?>" class="btn btn-ghost btn-sm">Upload</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
