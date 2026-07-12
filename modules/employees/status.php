<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';
require_once dirname(dirname(__DIR__)) . '/config/ApprovalEngine.php';

requireLogin();
requirePermission('employees.status', 'edit');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$emp = getEmployee($id);
if (!$emp) { setFlash('error','Employee not found.'); header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$pageTitle  = 'Change Status';
$activeMenu = 'employees';
$errors     = [];

// KOM-073: which direct transitions are legal. super_admin may override —
// a deliberate escape hatch for genuine data-entry corrections, since a
// wrong guess here must not be able to permanently trap a real record.
// Every other role is held to this matrix.
$allowedTransitions = [
    'active'     => ['probation','suspended','on_leave','resigned','terminated','deceased','archived'],
    'probation'  => ['active','suspended','on_leave','resigned','terminated','deceased'],
    'suspended'  => ['active','on_leave','resigned','terminated','deceased'],
    'on_leave'   => ['active','suspended','resigned','terminated','deceased'],
    'resigned'   => ['archived','active','probation'],   // archived = normal path; active/probation = rehire
    'terminated' => ['archived','active','probation'],   // archived = normal path; active/probation = rehire/reversal
    'deceased'   => ['archived'],                          // terminal except for records closure
    'archived'   => ['active','probation'],                // reactivation/rehire only
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $newStatus = $_POST['new_status'] ?? '';
        $reason    = trim($_POST['reason'] ?? '');
        $exitDate  = $_POST['exit_date'] ?? null;
        $oldStatus = $emp['status'];

        $validStatuses          = ['active','probation','suspended','on_leave','resigned','terminated','deceased','archived'];
        $accountDisableStatuses = ['resigned','terminated','deceased','archived'];
        $exitDateRequiredStatuses = ['resigned','terminated','deceased']; // matches the form's own exit-date field visibility
        if (!in_array($newStatus, $validStatuses)) $errors[] = 'Invalid status.';
        if (empty($reason)) $errors[] = 'Reason is required.';
        if (in_array($newStatus, $exitDateRequiredStatuses) && empty($exitDate)) $errors[] = 'Exit date is required for this status.';
        if ($_SESSION['user_role'] !== 'super_admin'
            && !in_array($newStatus, $allowedTransitions[$oldStatus] ?? [], true)) {
            $errors[] = "Cannot change status directly from '" . ucfirst($oldStatus) . "' to '" . ucfirst($newStatus) . "'.";
        }

        // KOM-072: termination is the one status transition the schema
        // explicitly models as an approval-gated workflow type
        // (approval_workflows.workflow_type includes 'termination', with an
        // hr_manager review stage) — route it through ApprovalEngine
        // instead of applying it immediately. Every other status change
        // remains instant, matching what the schema actually defines.
        if (empty($errors) && $newStatus === 'terminated') {
            try {
                $wfEngine = new ApprovalEngine(db());
                $wfTitle  = "Termination Request: {$emp['first_name']} {$emp['last_name']} ({$emp['employee_number']})";
                $wfEngine->create('termination', $id, 'employees', $wfTitle, $_SESSION['user_id'], $id, 'high', $exitDate ?: null,
                    json_encode(['new_status'=>'terminated','reason'=>$reason,'exit_date'=>$exitDate ?: null]));

                auditLog('employees','termination_requested',$id,
                    json_encode(['status'=>$oldStatus]),
                    json_encode(['requested_status'=>'terminated','reason'=>$reason]),
                    $reason);

                notifyRole('hr_manager', 'approval', 'Termination Request Awaiting Approval',
                    "{$emp['first_name']} {$emp['last_name']} ({$emp['employee_number']}) has a pending termination request.",
                    APP_URL . '/modules/approvals/index.php');

                setFlash('success', 'Termination request submitted for HR Manager approval. The employee\'s status will not change until approved.');
                header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$id);
                exit;
            } catch (Exception $wfEx) {
                error_log('Termination workflow creation failed: '.$wfEx->getMessage());
                $errors[] = 'Could not submit termination request. Please try again.';
            }
        }

        if (empty($errors) && $newStatus !== 'terminated') {
            db()->prepare("UPDATE employees SET status=?, status_reason=?, exit_date=?, updated_by=? WHERE id=?")
                ->execute([$newStatus, $reason, $exitDate ?: null, $_SESSION['user_id'], $id]);

            db()->prepare("INSERT INTO employee_status_history (employee_id, old_status, new_status, reason, changed_by) VALUES (?,?,?,?,?)")
                ->execute([$id, $oldStatus, $newStatus, $reason, $_SESSION['user_id']]);

            // Disable the linked user account on exit; re-enable it if the
            // employee is brought back to an active/probation status (e.g.
            // a rehire reactivated via status change rather than a new
            // record) — previously this only ever disabled, never restored,
            // silently leaving a reactivated employee's account locked out.
            if (in_array($newStatus, $accountDisableStatuses)) {
                db()->prepare("UPDATE users SET is_active=0 WHERE employee_id=?")->execute([$id]);
            } elseif (in_array($newStatus, ['active','probation']) && in_array($oldStatus, $accountDisableStatuses)) {
                db()->prepare("UPDATE users SET is_active=1 WHERE employee_id=?")->execute([$id]);
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
                                    'on_leave'=>'On Leave','resigned'=>'Resigned','terminated'=>'Terminated (requires HR Manager approval)',
                                    'deceased'=>'Deceased','archived'=>'Archived'];
                        $isSuperAdmin = ($_SESSION['user_role'] ?? '') === 'super_admin';
                        $legalNext    = $allowedTransitions[$emp['status']] ?? [];
                        foreach ($statuses as $sv=>$sl): ?>
                            <?php if ($sv !== $emp['status'] && ($isSuperAdmin || in_array($sv, $legalNext, true))): ?>
                            <option value="<?= $sv ?>"><?= $sl ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($isSuperAdmin): ?>
                        <optgroup label="Override (super_admin only — bypasses normal transition rules)">
                            <?php foreach ($statuses as $sv=>$sl): ?>
                                <?php if ($sv !== $emp['status'] && !in_array($sv, $legalNext, true)): ?>
                                <option value="<?= $sv ?>"><?= $sl ?> (override)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
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
