<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('leave.types', 'view');

$pageTitle  = 'Leave Types';
$activeMenu = 'leave';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name     = trim($_POST['name'] ?? '');
        $maxDays  = (int)($_POST['max_days'] ?? 0);
        $isPaid   = isset($_POST['is_paid']) ? 1 : 0;
        $isActive = 1;
        if ($name) {
            db()->prepare("INSERT INTO leave_types (name, max_days_per_year, is_paid, is_active) VALUES (?,?,?,1)")
                ->execute([$name, $maxDays ?: null, $isPaid]);
            setFlash('success',"Leave type '{$name}' added.");
        }
    } elseif ($action === 'toggle') {
        $ltId = (int)($_POST['lt_id'] ?? 0);
        db()->prepare("UPDATE leave_types SET is_active = NOT is_active WHERE id=?")->execute([$ltId]);
        setFlash('success','Leave type status updated.');
    }
    header('Location: ' . APP_URL . '/modules/leave/types.php'); exit;
}

$leaveTypes = db()->query("SELECT * FROM leave_types ORDER BY is_active DESC, name")->fetchAll();
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/leave/index.php">Leave</a></li>
                <li class="breadcrumb-item active">Leave Types</li>
            </ol>
        </nav>
        <h1 class="page-title">Leave Types</h1>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary btn-sm" data-modal-open="addTypeModal">Add Leave Type</button>
    </div>
</div>

<div class="card" style="max-width:640px;">
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead><tr><th>Leave Type</th><th>Max Days/Year</th><th>Paid</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($leaveTypes as $lt): ?>
            <tr>
                <td style="font-weight:600;"><?= e($lt['name']) ?></td>
                <td><?= $lt['max_days_per_year'] ?? '—' ?></td>
                <td><?= $lt['is_paid'] ? '<span class="badge badge-success">Paid</span>' : '<span class="badge badge-warning">Unpaid</span>' ?></td>
                <td><?= $lt['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>' ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="lt_id" value="<?= $lt['id'] ?>">
                        <button type="submit" class="btn btn-ghost btn-sm">
                            <?= $lt['is_active'] ? 'Disable' : 'Enable' ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Leave Type Modal -->
<div class="modal-overlay" id="addTypeModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Add Leave Type</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Leave Type Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="name" required placeholder="e.g. Family Responsibility Leave">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Days Per Year</label>
                    <input type="number" class="form-control" name="max_days" min="1" max="365" placeholder="Leave blank for unlimited">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_paid" checked>
                        <span class="form-label" style="margin:0;">Paid Leave</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Leave Type</button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
