<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('employees.status', 'edit');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$emp = getEmployee($id);
if (!$emp) { setFlash('error','Employee not found.'); header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$pageTitle  = 'Change Status';
$activeMenu = 'employees';
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $newStatus = $_POST['new_status'] ?? '';
        $reason    = trim($_POST['reason'] ?? '');
        $exitDate  = $_POST['exit_date'] ?? null;

        $validStatuses = ['active','probation','suspended','on_leave','resigned','terminated','deceased','archived'];
        if (!in_array($newStatus, $validStatuses)) $errors[] = 'Invalid status.';
        if (empty($reason)) $errors[] = 'Reason is required.';

        if (empty($errors)) {
            $oldStatus = $emp['status'];

            db()->prepare("UPDATE employees SET status=?, status_reason=?, exit_date=?, updated_by=? WHERE id=?")
                ->execute([$newStatus, $reason, $exitDate ?: null, $_SESSION['user_id'], $id]);

            db()->prepare("INSERT INTO employee_status_history (employee_id, old_status, new_status, reason, changed_by) VALUES (?,?,?,?,?)")
                ->execute([$id, $oldStatus, $newStatus, $reason, $_SESSION['user_id']]);

            // Disable user account if exiting
            if (in_array($newStatus, ['resigned','terminated','deceased','archived'])) {
                db()->prepare("UPDATE users SET is_active=0 WHERE employee_id=?")->execute([$id]);
            }

            auditLog('employees','status_change',$id,
                json_encode(['status'=>$oldStatus]),
                json_encode(['status'=>$newStatus,'reason'=>$reason]),
                $reason);

            setFlash('success', "Employee status changed to " . ucfirst($newStatus) . ".");
            header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$id);
            exit;
        }
    }
}

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/index.php">Employees</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $id ?>"><?= e($emp['first_name'].' '.$emp['last_name']) ?></a></li>
                <li class="breadcrumb-item active">Change Status</li>
            </ol>
        </nav>
        <h1 class="page-title">Change Employee Status</h1>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach($errors as $e) echo e($e).'<br>'; ?></div>
<?php endif; ?>

<div style="max-width:560px;">
    <div class="card" style="margin-bottom:16px;">
        <div class="card-body" style="display:flex;align-items:center;gap:14px;padding:14px 20px;">
            <div class="emp-avatar" style="width:44px;height:44px;font-size:1rem;">
                <?= strtoupper(substr($emp['first_name'],0,1)) ?>
            </div>
            <div>
                <div style="font-weight:700;"><?= e($emp['first_name'].' '.$emp['last_name']) ?></div>
                <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($emp['employee_number']) ?></div>
            </div>
            <div style="margin-left:auto;">
                Current: <?= employeeStatusBadge($emp['status']) ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Change Status</span></div>
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">New Status <span class="required">*</span></label>
                    <select class="form-select" name="new_status" required>
                        <option value="">Select status</option>
                        <?php
                        $statuses = ['active'=>'Active','probation'=>'On Probation','suspended'=>'Suspended',
                                    'on_leave'=>'On Leave','resigned'=>'Resigned','terminated'=>'Terminated',
                                    'deceased'=>'Deceased','archived'=>'Archived'];
                        foreach ($statuses as $sv=>$sl): ?>
                            <?php if ($sv !== $emp['status']): ?>
                            <option value="<?= $sv ?>"><?= $sl ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason <span class="required">*</span></label>
                    <textarea class="form-control" name="reason" rows="3" required
                              placeholder="Explain the reason for this status change…"></textarea>
                </div>
                <div class="form-group" id="exitDateGroup" style="display:none;">
                    <label class="form-label">Exit Date</label>
                    <input type="date" class="form-control" name="exit_date">
                </div>
            </div>
            <div class="card-footer" style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">Save Status Change</button>
                <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('select[name="new_status"]').addEventListener('change', function() {
    const exitGroup = document.getElementById('exitDateGroup');
    const exitStatuses = ['resigned','terminated','deceased'];
    exitGroup.style.display = exitStatuses.includes(this.value) ? 'block' : 'none';
});
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
