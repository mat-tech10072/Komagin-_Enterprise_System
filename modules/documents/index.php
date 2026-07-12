<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('documents.view', 'view');

$pageTitle  = 'Documents';
$activeMenu = 'documents';

$category = $_GET['category'] ?? '';
$empId    = (int)($_GET['emp'] ?? 0);
$deptId   = (int)($_GET['dept'] ?? 0);
$search   = trim($_GET['search'] ?? '');
$page     = max(1,(int)($_GET['page'] ?? 1));
$perPage  = 30;

$where  = ['ed.is_deleted = 0'];
$params = [];

if ($category) { $where[] = 'ed.category = ?'; $params[] = $category; }
if ($empId)    { $where[] = 'ed.employee_id = ?'; $params[] = $empId; }
if ($deptId)   { $where[] = 'e.department_id = ?'; $params[] = $deptId; }
if ($search)   {
    $where[] = '(e.first_name LIKE ? OR e.last_name LIKE ? OR ed.document_name LIKE ?)';
    $s = "%$search%"; $params = array_merge($params,[$s,$s,$s]);
}

$whereSQL = implode(' AND ', $where);
$countStmt = db()->prepare("SELECT COUNT(*) FROM employee_documents ed JOIN employees e ON ed.employee_id=e.id WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total,$perPage,$page);

$stmt = db()->prepare("SELECT ed.*, e.first_name, e.last_name, e.employee_number, d.name as dept
    FROM employee_documents ed
    JOIN employees e ON ed.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE $whereSQL ORDER BY ed.uploaded_at DESC LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$docs = $stmt->fetchAll();

$departments = getDepartments();
$categories  = ['id_document','certificate','contract','medical','warning_letter','promotion_letter','leave_document','resignation','clearance','payslip','other'];

// Expiring soon
$expiringSoon = db()->query("SELECT ed.document_name, e.first_name, e.last_name, e.employee_number, ed.expiry_date
    FROM employee_documents ed JOIN employees e ON ed.employee_id=e.id
    WHERE ed.is_deleted=0 AND ed.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY ed.expiry_date LIMIT 5")->fetchAll();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Document Management</h1>
        <p class="page-subtitle"><?= $total ?> documents</p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/modules/documents/upload.php" class="btn btn-primary btn-sm">Upload Document</a>
    </div>
</div>

<?php if (!empty($expiringSoon)): ?>
<div class="alert alert-warning">
    <strong>Documents Expiring Soon (30 days):</strong>
    <?php foreach ($expiringSoon as $d): ?>
        <?= e($d['first_name'].' '.$d['last_name']) ?> — <?= e($d['document_name']) ?> (<?= formatDate($d['expiry_date']) ?>)<?php if ($d !== end($expiringSoon)) echo '; '; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <input type="text" class="form-control" name="search" placeholder="Search employee or document…" value="<?= e($search) ?>" style="max-width:220px;">
            <select class="form-select" name="category" style="width:auto;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= $category===$cat?'selected':''?>><?= ucwords(str_replace('_',' ',$cat)) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="dept" style="width:auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptId==$d['id']?'selected':''?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);"><?= $pagination['offset']+1 ?>–<?= min($pagination['offset']+$perPage,$total) ?> of <?= $total ?></div>
    </div>

    <div class="table-wrapper" style="border:none;border-radius:0;">
        <?php if (empty($docs)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No documents found</div>
            <div class="empty-state-desc">Upload your first document to get started.</div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Document</th><th>Employee</th><th>Category</th><th>Expiry</th><th>Verified</th><th>Uploaded</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($docs as $doc): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:0.78rem;"><?= e($doc['document_name']) ?></div>
                    <div style="font-size:0.65rem;color:var(--text-muted);"><?= strtoupper(pathinfo($doc['file_path'],PATHINFO_EXTENSION)) ?></div>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $doc['employee_id'] ?>&tab=documents" style="font-size:0.75rem;font-weight:600;color:var(--text);">
                        <?= e($doc['first_name'].' '.$doc['last_name']) ?>
                    </a>
                    <div class="emp-num"><?= e($doc['employee_number']) ?></div>
                </td>
                <td><span class="badge badge-secondary"><?= ucwords(str_replace('_',' ',$doc['category'])) ?></span></td>
                <td style="font-size:0.75rem;">
                    <?php if ($doc['expiry_date']): ?>
                        <?php $expired = strtotime($doc['expiry_date']) < time(); ?>
                        <span style="color:<?= $expired ? 'var(--danger)' : 'var(--text)' ?>;"><?= formatDate($doc['expiry_date']) ?></span>
                        <?php if ($expired): ?><span class="badge badge-danger">Expired</span><?php endif; ?>
                    <?php else: ?>
                        <span style="color:var(--text-muted);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= $doc['is_verified'] ? '<span class="badge badge-success">Verified</span>' : '<span class="badge badge-warning">Pending</span>' ?>
                </td>
                <td style="font-size:0.72rem;color:var(--text-muted);"><?= formatDate($doc['uploaded_at']) ?></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= APP_URL ?>/<?= e($doc['file_path']) ?>" class="btn btn-ghost btn-sm" target="_blank">View</a>
                        <?php if (!$doc['is_verified'] && canApprove('documents.verify')): ?>
                        <form method="POST" action="<?= APP_URL ?>/modules/documents/verify.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--success);">Verify</button>
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
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
