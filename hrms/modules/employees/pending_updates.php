<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('employees.approve_updates', 'approve');

$pageTitle  = 'Pending Profile Updates';
$activeMenu = 'employees';

// Canonical allowed field names (DB columns)
$ALLOWED_FIELDS = [
    'phone', 'personal_email', 'residential_address', 'city', 'country', 'marital_status',
    'emergency_contact_name', 'emergency_contact_relation', 'emergency_contact_phone',
    'nok_name', 'nok_relation', 'nok_phone',
    'bank_name', 'bank_account_number', 'bank_branch_code', 'bank_account_type',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action   = $_POST['action']    ?? '';
    $updateId = (int)($_POST['update_id'] ?? 0);
    $reason   = trim($_POST['reason'] ?? '');

    if ($updateId && in_array($action, ['approve','reject'])) {
        $stmt = db()->prepare("SELECT * FROM employee_pending_updates WHERE id=? AND status='pending'");
        $stmt->execute([$updateId]);
        $update = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($update) {
            $fieldName = $update['field_name'];
            $empId     = $update['employee_id'];

            if ($action === 'approve') {
                // Validate field is in the allowed list
                if (in_array($fieldName, $ALLOWED_FIELDS)) {
                    db()->prepare("UPDATE employees SET `$fieldName`=?, updated_by=?, updated_at=NOW() WHERE id=?")
                        ->execute([$update['new_value'], $_SESSION['user_id'], $empId]);
                }
                db()->prepare("UPDATE employee_pending_updates SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")
                    ->execute([$_SESSION['user_id'], $updateId]);
                auditLog('employees','approve_profile_update',$empId,
                    json_encode(['field'=>$fieldName,'old'=>$update['old_value']]),
                    json_encode(['field'=>$fieldName,'new'=>$update['new_value']]),
                    $reason ?: 'Approved by ' . $_SESSION['user_name']);
                setFlash('success', 'Field "'.e($update['field_label']).'" approved and applied to employee record.');
            } else {
                db()->prepare("UPDATE employee_pending_updates SET status='rejected', rejection_reason=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")
                    ->execute([$reason ?: 'Rejected', $_SESSION['user_id'], $updateId]);
                auditLog('employees','reject_profile_update',$empId,
                    json_encode(['field'=>$fieldName]),null,$reason ?: 'Rejected');
                setFlash('info', 'Field "'.e($update['field_label']).'" rejected.');
            }
        }
    } elseif ($_POST['action'] === 'approve_all_employee') {
        // Approve all pending updates for one employee
        $empId = (int)($_POST['employee_id'] ?? 0);
        if ($empId) {
            $stmt = db()->prepare("SELECT * FROM employee_pending_updates WHERE employee_id=? AND status='pending'");
            $stmt->execute([$empId]);
            $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($all as $u) {
                if (in_array($u['field_name'], $ALLOWED_FIELDS)) {
                    db()->prepare("UPDATE employees SET `{$u['field_name']}`=?, updated_by=?, updated_at=NOW() WHERE id=?")
                        ->execute([$u['new_value'], $_SESSION['user_id'], $empId]);
                    db()->prepare("UPDATE employee_pending_updates SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")
                        ->execute([$_SESSION['user_id'], $u['id']]);
                }
            }
            auditLog('employees','approve_all_profile_updates',$empId,null,null,'Bulk approved '.count($all).' field(s)');
            setFlash('success', count($all).' update(s) approved and applied.');
        }
    }
    header('Location: ' . APP_URL . '/modules/employees/pending_updates.php'); exit;
}

// Load all pending updates grouped by employee
$filterStatus = $_GET['status'] ?? 'pending';
$allowedStatuses = ['pending','approved','rejected'];
if (!in_array($filterStatus, $allowedStatuses)) $filterStatus = 'pending';

$stmt = db()->prepare("SELECT epu.*,
    e.first_name, e.last_name, e.employee_number, e.photo
    FROM employee_pending_updates epu
    JOIN employees e ON epu.employee_id=e.id
    WHERE epu.status=?
    ORDER BY epu.employee_id, epu.submitted_at ASC");
$stmt->execute([$filterStatus]);
$updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by employee
$byEmployee = [];
foreach ($updates as $u) {
    $byEmployee[$u['employee_id']][] = $u;
}

$counts = db()->query("SELECT status, COUNT(*) as cnt FROM employee_pending_updates GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/index.php">Employees</a></li>
                <li class="breadcrumb-item active">Pending Profile Updates</li>
            </ol>
        </nav>
        <h1 class="page-title">Pending Profile Updates</h1>
        <p class="page-subtitle">Review and approve employee self-service profile change requests</p>
    </div>
</div>

<!-- Status Filter Tabs -->
<div class="tab-nav" style="margin-bottom:20px;">
    <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $s=>$l): ?>
    <a href="?status=<?= $s ?>" class="tab-item <?= $filterStatus===$s?'active':'' ?>">
        <?= $l ?>
        <?php if ($s==='pending' && ($counts['pending']??0)>0): ?>
        <span class="badge badge-warning" style="margin-left:4px;font-size:0.6rem;"><?= $counts['pending'] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($byEmployee)): ?>
<div class="card">
    <div class="empty-state" style="padding:48px;">
        <div class="empty-state-title">No <?= $filterStatus ?> updates</div>
        <div class="empty-state-desc">
            <?php if ($filterStatus === 'pending'): ?>
            No employees have submitted profile update requests that are awaiting review.
            <?php else: ?>
            No <?= $filterStatus ?> profile updates found.
            <?php endif; ?>
        </div>
    </div>
</div>
<?php else: ?>

<?php foreach ($byEmployee as $empId => $fields): ?>
<?php $emp = $fields[0]; // employee info from first row ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:38px;height:38px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;color:var(--primary);flex-shrink:0;">
                <?= strtoupper(substr($emp['first_name'],0,1)) ?>
            </div>
            <div>
                <div style="font-weight:700;font-size:0.88rem;"><?= e($emp['first_name'].' '.$emp['last_name']) ?></div>
                <div class="emp-num"><?= e($emp['employee_number']) ?></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span style="font-size:0.72rem;color:var(--text-muted);"><?= count($fields) ?> field(s)</span>
            <?php if ($filterStatus === 'pending'): ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="approve_all_employee">
                <input type="hidden" name="employee_id" value="<?= $empId ?>">
                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve all <?= count($fields) ?> update(s) for this employee?')">
                    Approve All
                </button>
            </form>
            <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $empId ?>" class="btn btn-secondary btn-sm">View Profile</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Current Value</th>
                    <th>Requested Value</th>
                    <th>Submitted</th>
                    <?php if ($filterStatus === 'pending'): ?><th style="width:200px;">Actions</th><?php endif; ?>
                    <?php if ($filterStatus !== 'pending'): ?><th>Review</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fields as $field): ?>
            <tr>
                <td style="font-weight:600;font-size:0.82rem;"><?= e($field['field_label']) ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);"><?= e($field['old_value'] ?: '—') ?></td>
                <td style="font-size:0.82rem;font-weight:500;color:var(--primary);"><?= e($field['new_value']) ?></td>
                <td style="font-size:0.72rem;"><?= formatDateTime($field['submitted_at']) ?></td>
                <?php if ($filterStatus === 'pending'): ?>
                <td>
                    <form method="POST" style="display:inline-flex;gap:4px;align-items:center;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="update_id" value="<?= $field['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success btn-sm" style="font-size:0.68rem;">Approve</button>
                    </form>
                    <form method="POST" style="display:inline-flex;gap:4px;align-items:center;margin-left:4px;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="update_id" value="<?= $field['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="reason" value="Not approved">
                        <button type="submit" class="btn btn-danger btn-sm" style="font-size:0.68rem;">Reject</button>
                    </form>
                </td>
                <?php endif; ?>
                <?php if ($filterStatus !== 'pending'): ?>
                <td>
                    <span class="badge badge-<?= $field['status']==='approved'?'success':'danger' ?>">
                        <?= ucfirst($field['status']) ?>
                    </span>
                    <?php if ($field['rejection_reason']): ?>
                    <div style="font-size:0.68rem;color:var(--text-muted);margin-top:2px;"><?= e($field['rejection_reason']) ?></div>
                    <?php endif; ?>
                    <div style="font-size:0.68rem;color:var(--text-muted);"><?= formatDateTime($field['reviewed_at']) ?></div>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endforeach; ?>
<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
