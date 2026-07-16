<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('timesheets.view', 'view');

$pageTitle  = 'Correction Requests';
$activeMenu = 'timesheets';

$status = $_GET['status'] ?? 'pending';
$page   = max(1,(int)($_GET['page'] ?? 1));
$perPage= 25;

$isHR = canApprove('timesheets.approve');

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isHR && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $crId     = (int)($_POST['cr_id'] ?? 0);
    $crAction = $_POST['cr_action'] ?? '';
    $remarks  = trim($_POST['remarks'] ?? '');

    if ($crId && in_array($crAction,['approve','reject'])) {
        $crStmt = db()->prepare("SELECT * FROM correction_requests WHERE id=?");
        $crStmt->execute([$crId]); $cr = $crStmt->fetch();

        if ($cr && $cr['status'] === 'pending') {
            $newStatus = $crAction === 'approve' ? 'approved' : 'rejected';
            db()->prepare("UPDATE correction_requests SET status=?, hr_remarks=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")
                ->execute([$newStatus, $remarks, $_SESSION['user_id'], $crId]);

            if ($crAction === 'approve' && $cr['attendance_id']) {
                // Fetch current attendance record
                $attStmt = db()->prepare("SELECT * FROM attendance WHERE id=?");
                $attStmt->execute([$cr['attendance_id']]);
                $attRec = $attStmt->fetch();

                if ($attRec) {
                    // Merge corrected times over current values
                    $newSignIn   = $cr['requested_sign_in']   ?: $attRec['sign_in'];
                    $newSignOut  = $cr['requested_sign_out']  ?: $attRec['sign_out'];
                    $newBreakOut = $cr['requested_break_out'] ?: $attRec['break_out'];
                    $newBreakIn  = $cr['requested_break_in']  ?: $attRec['break_in'];

                    // Recalculate derived fields
                    $breakMins = 0;
                    if ($newBreakOut && $newBreakIn) {
                        $breakMins = max(0, (int)((strtotime(date('Y-m-d').' '.$newBreakIn) - strtotime(date('Y-m-d').' '.$newBreakOut)) / 60));
                    }
                    $totalHours = ($newSignIn && $newSignOut) ? calculateHours($newSignIn, $newSignOut, $breakMins) : 0;
                    $settings   = getAttendanceSettings();
                    $normalHours= min($totalHours, $settings['standard_hours']);
                    $otHours    = max(0, $totalHours - $settings['overtime_threshold']);
                    $isLate     = $newSignIn ? isLate($newSignIn, $settings['work_start'], $settings['grace_period']) : false;
                    $lateMins   = 0;
                    if ($isLate && $newSignIn) {
                        $lateMins = max(0, (int)((strtotime(date('Y-m-d').' '.$newSignIn) - strtotime(date('Y-m-d').' '.$settings['work_start']) - $settings['grace_period'] * 60) / 60));
                    }

                    db()->prepare("UPDATE attendance SET
                        sign_in=?, sign_out=?, break_out=?, break_in=?,
                        break_duration_minutes=?, total_hours_worked=?, normal_hours=?,
                        overtime_hours=?, is_late=?, late_minutes=?,
                        is_manually_adjusted=1, adjustment_reason=?,
                        adjusted_by=?, adjusted_at=NOW(),
                        is_approved=0
                        WHERE id=?")
                        ->execute([
                            $newSignIn, $newSignOut, $newBreakOut, $newBreakIn,
                            $breakMins, $totalHours, $normalHours,
                            $otHours, $isLate ? 1 : 0, $lateMins,
                            'Correction approved: ' . $cr['description'],
                            $_SESSION['user_id'],
                            $cr['attendance_id']
                        ]);

                    // Update or create OT record if overtime changed
                    if ($otHours > 0) {
                        $otEx = db()->prepare("SELECT id FROM overtime_records WHERE attendance_id=?");
                        $otEx->execute([$cr['attendance_id']]);
                        if ($otEx->fetch()) {
                            db()->prepare("UPDATE overtime_records SET suggested_hours=?, status='pending' WHERE attendance_id=?")
                                ->execute([$otHours, $cr['attendance_id']]);
                        } else {
                            db()->prepare("INSERT INTO overtime_records (attendance_id,employee_id,overtime_date,suggested_hours,status) VALUES (?,?,?,?,'pending')")
                                ->execute([$cr['attendance_id'], $cr['employee_id'], $cr['request_date'], $otHours]);
                        }
                    }
                }
            }

            auditLog('timesheets','correction_'.$crAction,$crId,null,null,$remarks);
            setFlash('success', 'Correction request ' . $newStatus . '.');
        }
        header('Location: ' . APP_URL . '/modules/timesheets/corrections.php?status='.$status); exit;
    }
}

// Non-HR employee submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isHR && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $empId = (int)($_SESSION['employee_id'] ?? 0);
    if ($empId) {
        $reqDate  = $_POST['request_date'] ?? date('Y-m-d');
        $reqType  = $_POST['request_type'] ?? 'other';
        $desc     = trim($_POST['description'] ?? '');
        $rSignIn  = trim($_POST['req_sign_in']   ?? '') ?: null;
        $rSignOut = trim($_POST['req_sign_out']  ?? '') ?: null;
        $rBrkOut  = trim($_POST['req_break_out'] ?? '') ?: null;
        $rBrkIn   = trim($_POST['req_break_in']  ?? '') ?: null;

        if ($desc) {
            // Find attendance record for that date if it exists
            $attLookup = db()->prepare("SELECT id FROM attendance WHERE employee_id=? AND attendance_date=?");
            $attLookup->execute([$empId, $reqDate]);
            $attId = $attLookup->fetchColumn() ?: null;

            db()->prepare("INSERT INTO correction_requests
                (employee_id, attendance_id, request_date, request_type, description,
                 requested_sign_in, requested_sign_out, requested_break_out, requested_break_in)
                VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$empId, $attId, $reqDate, $reqType, $desc, $rSignIn, $rSignOut, $rBrkOut, $rBrkIn]);

            auditLog('timesheets', 'correction_submit', $empId, null, null, $desc);
            setFlash('success', 'Correction request submitted. HR will review it shortly.');
        } else {
            setFlash('error', 'Description is required.');
        }
    }
    header('Location: ' . APP_URL . '/modules/timesheets/corrections.php?status=pending'); exit;
}

$where  = ['1=1'];
$params = [];

if (!$isHR) {
    $where[] = 'cr.employee_id = ?';
    $params[] = $_SESSION['employee_id'] ?? 0;
}
if ($status) { $where[] = 'cr.status = ?'; $params[] = $status; }

$whereSQL = implode(' AND ', $where);
$countStmt = db()->prepare("SELECT COUNT(*) FROM correction_requests cr WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total,$perPage,$page);

$stmt = db()->prepare("SELECT cr.*, e.first_name, e.last_name, e.employee_number, d.name as dept
    FROM correction_requests cr
    JOIN employees e ON cr.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE $whereSQL ORDER BY cr.created_at DESC
    LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$requests = $stmt->fetchAll();

$typeLabels = [
    'forgot_sign_in' => 'Forgot Sign In',
    'forgot_sign_out' => 'Forgot Sign Out',
    'forgot_break_out' => 'Forgot Break Out',
    'forgot_break_in' => 'Forgot Break In',
    'wrong_time' => 'Wrong Time',
    'overtime_not_captured' => 'OT Not Captured',
    'other' => 'Other',
];

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Timesheet Correction Requests</h1>
        <p class="page-subtitle">Review and process employee correction requests</p>
    </div>
    <?php if (!$isHR): ?>
    <div class="page-actions">
        <button class="btn btn-primary btn-sm" data-modal-open="newCorrectionModal">Submit Request</button>
    </div>
    <?php endif; ?>
</div>

<!-- Status Tabs -->
<div style="display:flex;gap:8px;margin-bottom:16px;">
    <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $s=>$l): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s ? 'btn-primary' : 'btn-secondary' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-wrapper" style="border:none;">
        <?php if (empty($requests)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No correction requests</div>
            <div class="empty-state-desc">No <?= $status ?> requests found.</div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <?php if ($isHR): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $cr): ?>
            <tr>
                <td>
                    <div class="emp-name"><?= e($cr['first_name'].' '.$cr['last_name']) ?></div>
                    <div class="emp-num"><?= e($cr['employee_number']) ?></div>
                </td>
                <td style="font-size:0.75rem;"><?= formatDate($cr['request_date']) ?></td>
                <td><span class="badge badge-info"><?= e($typeLabels[$cr['request_type']] ?? $cr['request_type']) ?></span></td>
                <td style="font-size:0.75rem;max-width:200px;"><?= e(mb_strimwidth($cr['description'],0,60,'…')) ?></td>
                <td><?= leaveStatusBadge($cr['status']) ?></td>
                <td style="font-size:0.72rem;color:var(--text-muted);"><?= formatDate($cr['created_at']) ?></td>
                <?php if ($isHR && $cr['status'] === 'pending'): ?>
                <td>
                    <button class="btn btn-ghost btn-sm" style="color:var(--success);" onclick="reviewRequest(<?= $cr['id'] ?>,'approve')">Approve</button>
                    <button class="btn btn-ghost btn-sm" style="color:var(--danger);" onclick="reviewRequest(<?= $cr['id'] ?>,'reject')">Reject</button>
                </td>
                <?php elseif ($isHR): ?>
                <td style="font-size:0.72rem;color:var(--text-muted);"><?= e($cr['hr_remarks'] ?? '—') ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Review Modal -->
<div class="modal-overlay" id="reviewModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h5 class="modal-title" id="reviewTitle">Review Request</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="cr_id" id="reviewCrId">
            <input type="hidden" name="cr_action" id="reviewCrAction">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">HR Remarks</label>
                    <textarea class="form-control" name="remarks" rows="3" placeholder="Optional remarks…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" id="reviewSubmitBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function reviewRequest(id, action) {
    document.getElementById('reviewCrId').value = id;
    document.getElementById('reviewCrAction').value = action;
    document.getElementById('reviewTitle').textContent = action === 'approve' ? 'Approve Request' : 'Reject Request';
    const btn = document.getElementById('reviewSubmitBtn');
    btn.textContent = action === 'approve' ? 'Approve' : 'Reject';
    btn.className = 'btn ' + (action === 'approve' ? 'btn-success' : 'btn-danger');
    window.openModal('reviewModal');
}
</script>

<?php if (!$isHR): ?>
<!-- Submit Correction Request Modal (employee) -->
<div class="modal-overlay" id="newCorrectionModal">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <h5 class="modal-title">Submit Correction Request</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-body">
                <div class="form-row" style="margin-bottom:16px;">
                    <div class="form-group">
                        <label class="form-label">Date to Correct <span class="required">*</span></label>
                        <input type="date" class="form-control" name="request_date"
                               value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Request Type <span class="required">*</span></label>
                        <select class="form-select" name="request_type" required>
                            <?php foreach ($typeLabels as $val => $label): ?>
                            <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Description <span class="required">*</span></label>
                    <textarea class="form-control" name="description" rows="3" required
                              placeholder="Explain what happened and what the correct time(s) should be…"></textarea>
                </div>
                <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:10px;">
                    Optionally enter the corrected times below (leave blank if not applicable):
                </div>
                <div class="form-row" style="margin-bottom:10px;">
                    <div class="form-group">
                        <label class="form-label" style="font-size:0.72rem;">Sign In</label>
                        <input type="time" class="form-control" name="req_sign_in">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size:0.72rem;">Break Out</label>
                        <input type="time" class="form-control" name="req_break_out">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" style="font-size:0.72rem;">Break In</label>
                        <input type="time" class="form-control" name="req_break_in">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size:0.72rem;">Sign Out</label>
                        <input type="time" class="form-control" name="req_sign_out">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
