<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('leave.approve', 'approve');

$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['approve','reject'])) {
    header('Location: ' . APP_URL . '/modules/leave/index.php');
    exit;
}

$stmt = db()->prepare("SELECT la.*, e.first_name, e.last_name, e.employee_number, e.id as emp_id
    FROM leave_applications la JOIN employees e ON la.employee_id=e.id WHERE la.id=?");
$stmt->execute([$id]);
$la = $stmt->fetch();

if (!$la || $la['status'] !== 'pending') {
    setFlash('error', 'Leave application not found or already processed.');
    header('Location: ' . APP_URL . '/modules/leave/index.php');
    exit;
}

$errors = [];
$pageTitle  = ($action === 'approve' ? 'Approve' : 'Reject') . ' Leave';
$activeMenu = 'leave';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $remarks = trim($_POST['remarks'] ?? '');
        if ($action === 'reject' && empty($remarks)) {
            $errors[] = 'Rejection reason is required.';
        }

        if (empty($errors)) {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';

            db()->prepare("UPDATE leave_applications SET
                status=?, hr_status=?, hr_reviewed_by=?, hr_reviewed_at=NOW(), hr_remarks=?
                WHERE id=?")
                ->execute([$newStatus, $newStatus, $_SESSION['user_id'], $remarks ?: null, $id]);

            // Update leave balance if approved
            if ($action === 'approve') {
                db()->prepare("UPDATE leave_balances SET
                    used_days = used_days + ?,
                    pending_days = GREATEST(0, pending_days - ?),
                    remaining_days = GREATEST(0, remaining_days - ?)
                    WHERE employee_id=? AND leave_type_id=? AND year=YEAR(?)")
                    ->execute([$la['total_days'],$la['total_days'],$la['total_days'],
                               $la['employee_id'],$la['leave_type_id'],$la['start_date']]);

                // Update employee status if needed
                $today = date('Y-m-d');
                if ($la['start_date'] <= $today && $la['end_date'] >= $today) {
                    db()->prepare("UPDATE employees SET status='on_leave' WHERE id=?")
                        ->execute([$la['employee_id']]);
                }

                // Mark attendance as on leave
                $dates = [];
                $d = strtotime($la['start_date']);
                $end = strtotime($la['end_date']);
                while ($d <= $end) {
                    $dates[] = date('Y-m-d', $d);
                    $d = strtotime('+1 day', $d);
                }
                foreach ($dates as $date) {
                    $existing = db()->prepare("SELECT id FROM attendance WHERE employee_id=? AND attendance_date=?");
                    $existing->execute([$la['employee_id'], $date]);
                    if ($existing->fetch()) {
                        db()->prepare("UPDATE attendance SET status='on_leave', is_on_leave=1 WHERE employee_id=? AND attendance_date=?")
                            ->execute([$la['employee_id'], $date]);
                    } else {
                        db()->prepare("INSERT INTO attendance (employee_id, employee_number, attendance_date, status, is_on_leave) VALUES (?,?,?,'on_leave',1)")
                            ->execute([$la['employee_id'], $la['employee_number'], $date]);
                    }
                }
            } else {
                // Rejected — remove pending from balance
                db()->prepare("UPDATE leave_balances SET pending_days = GREATEST(0, pending_days - ?)
                    WHERE employee_id=? AND leave_type_id=? AND year=YEAR(?)")
                    ->execute([$la['total_days'], $la['employee_id'], $la['leave_type_id'], $la['start_date']]);
            }

            // Find employee's user account to notify
            $userStmt = db()->prepare("SELECT id FROM users WHERE employee_id=?");
            $userStmt->execute([$la['employee_id']]);
            $empUser = $userStmt->fetch();
            if ($empUser) {
                createNotification($empUser['id'],
                    $action === 'approve' ? 'success' : 'danger',
                    'Leave ' . ucfirst($newStatus),
                    "Your {$la['total_days']}-day leave from " . formatDate($la['start_date']) . " has been {$newStatus}." . ($remarks ? " Remarks: $remarks" : ''),
                    APP_URL . '/modules/leave/index.php?emp=' . $la['employee_id']
                );
            }

            auditLog('leave', $action, $id,
                json_encode(['status' => $la['status']]),
                json_encode(['status' => $newStatus, 'remarks' => $remarks]),
                $action . ' leave application');

            setFlash('success', "Leave application {$newStatus} for {$la['first_name']} {$la['last_name']}.");
            header('Location: ' . APP_URL . '/modules/leave/index.php?status=pending');
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
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/leave/index.php">Leave</a></li>
                <li class="breadcrumb-item active"><?= $action === 'approve' ? 'Approve' : 'Reject' ?> Leave</li>
            </ol>
        </nav>
        <h1 class="page-title"><?= $action === 'approve' ? 'Approve Leave' : 'Reject Leave' ?></h1>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach($errors as $e) echo e($e).'<br>'; ?></div>
<?php endif; ?>

<div style="max-width:600px;">
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><span class="card-title">Leave Application Details</span></div>
        <div class="card-body">
            <?php
            $stmt2 = db()->prepare("SELECT lt.name FROM leave_types lt JOIN leave_applications la ON la.leave_type_id=lt.id WHERE la.id=?");
            $stmt2->execute([$id]); $lt = $stmt2->fetch();
            $rows = [
                ['Employee', $la['first_name'].' '.$la['last_name'].' ('.$la['employee_number'].')'],
                ['Leave Type', $lt['name'] ?? '—'],
                ['From', formatDate($la['start_date'])],
                ['To', formatDate($la['end_date'])],
                ['Total Days', $la['total_days'].' days'],
                ['Reason', $la['reason'] ?? '—'],
            ]; ?>
            <?php foreach ($rows as [$label,$val]): ?>
            <div style="display:flex;gap:16px;padding:7px 0;border-bottom:1px solid var(--border-light);font-size:0.78rem;">
                <span style="min-width:100px;color:var(--text-secondary);flex-shrink:0;"><?= e($label) ?></span>
                <span style="font-weight:500;"><?= e($val) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <form method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="card">
            <div class="card-header"><span class="card-title"><?= $action === 'approve' ? 'Approve' : 'Reject' ?> with Remarks</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">
                        HR Remarks <?= $action === 'reject' ? '<span class="required">*</span>' : '' ?>
                    </label>
                    <textarea class="form-control" name="remarks" rows="3"
                              <?= $action === 'reject' ? 'required' : '' ?>
                              placeholder="<?= $action === 'reject' ? 'Reason for rejection (required)' : 'Optional notes or conditions' ?>"><?= e($_POST['remarks'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex;gap:8px;">
                <?php if ($action === 'approve'): ?>
                    <button type="submit" class="btn btn-success">Approve Leave</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-danger">Reject Leave</button>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/modules/leave/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
