<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('timesheets.edit', 'edit');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/timesheets/index.php'); exit; }

$stmt = db()->prepare("SELECT a.*, e.first_name, e.last_name, e.employee_number, d.name as dept FROM attendance a JOIN employees e ON a.employee_id=e.id LEFT JOIN departments d ON e.department_id=d.id WHERE a.id=?");
$stmt->execute([$id]);
$att = $stmt->fetch();
if (!$att) { setFlash('error','Record not found.'); header('Location: ' . APP_URL . '/modules/timesheets/index.php'); exit; }

$pageTitle  = 'Edit Timesheet';
$activeMenu = 'timesheets';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } elseif ($att['is_locked'] && $_SESSION['user_role'] !== 'super_admin' && $_SESSION['user_role'] !== 'hr_manager') {
        $errors[] = 'This timesheet is locked. Only HR Manager or Super Admin can edit locked records.';
    } else {
        $reason = trim($_POST['reason'] ?? '');
        if (empty($reason)) $errors[] = 'A reason for the change is required.';

        if (empty($errors)) {
            $fields = ['sign_in','break_out','break_in','sign_out'];
            $newVals = [];
            foreach ($fields as $f) {
                $val = trim($_POST[$f] ?? '');
                $newVals[$f] = $val ?: null;
            }

            // Recalculate hours
            $breakMins = 0;
            if ($newVals['break_out'] && $newVals['break_in']) {
                $b1 = strtotime(date('Y-m-d').' '.$newVals['break_out']);
                $b2 = strtotime(date('Y-m-d').' '.$newVals['break_in']);
                $breakMins = max(0, (int)(($b2-$b1)/60));
            }

            $totalHours = 0;
            if ($newVals['sign_in'] && $newVals['sign_out']) {
                $totalHours = calculateHours($newVals['sign_in'], $newVals['sign_out'], $breakMins);
            }

            $settings = getAttendanceSettings();
            $normalHours = min($totalHours, $settings['standard_hours']);
            $otHours = max(0, $totalHours - $settings['overtime_threshold']);

            $isLate = $newVals['sign_in'] ? isLate($newVals['sign_in'], $settings['work_start'], $settings['grace_period']) : false;
            $lateMins = 0;
            if ($isLate && $newVals['sign_in']) {
                $workStart = strtotime(date('Y-m-d').' '.$settings['work_start']);
                $signIn    = strtotime(date('Y-m-d').' '.$newVals['sign_in']);
                $lateMins  = max(0, (int)(($signIn - $workStart - $settings['grace_period']*60)/60));
            }

            // Build old value for audit
            $oldVal = json_encode([
                'sign_in'  => $att['sign_in'],
                'break_out'=> $att['break_out'],
                'break_in' => $att['break_in'],
                'sign_out' => $att['sign_out'],
            ]);
            $newVal = json_encode($newVals);

            $hrRemarks = trim($_POST['hr_remarks'] ?? '');
            db()->prepare("UPDATE attendance SET
                sign_in=?, break_out=?, break_in=?, sign_out=?,
                break_duration_minutes=?, total_hours_worked=?, normal_hours=?,
                overtime_hours=?, is_late=?, late_minutes=?,
                is_manually_adjusted=1, adjustment_reason=?, hr_remarks=?,
                adjusted_by=?, adjusted_at=NOW(), is_approved=0
                WHERE id=?")
                ->execute([
                    $newVals['sign_in'], $newVals['break_out'],
                    $newVals['break_in'], $newVals['sign_out'],
                    $breakMins, $totalHours, $normalHours,
                    $otHours, $isLate ? 1 : 0, $lateMins,
                    $reason, $hrRemarks ?: null,
                    $_SESSION['user_id'], $id
                ]);

            // Update OT record if needed
            if ($otHours > 0) {
                $existing = db()->prepare("SELECT id FROM overtime_records WHERE attendance_id=?");
                $existing->execute([$id]);
                if ($existing->fetch()) {
                    db()->prepare("UPDATE overtime_records SET suggested_hours=? WHERE attendance_id=?")
                        ->execute([$otHours, $id]);
                } else {
                    db()->prepare("INSERT INTO overtime_records (attendance_id, employee_id, overtime_date, suggested_hours, status) VALUES (?,?,?,?,'pending')")
                        ->execute([$id, $att['employee_id'], $att['attendance_date'], $otHours]);
                }
            }

            auditLog('timesheets', 'edit', $id, $oldVal, $newVal, $reason);

            setFlash('success', 'Timesheet updated successfully.');
            header('Location: ' . APP_URL . '/modules/timesheets/index.php');
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
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/timesheets/index.php">Timesheets</a></li>
                <li class="breadcrumb-item active">Edit Record</li>
            </ol>
        </nav>
        <h1 class="page-title">Edit Timesheet</h1>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($att['is_locked']): ?>
<div class="alert alert-warning">
    <strong>Locked Record</strong> — This timesheet is locked. Changes require HR Manager or Super Admin authority.
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">
    <div>
        <!-- Employee Info -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="display:flex;align-items:center;gap:14px;padding:14px 20px;">
                <div class="emp-avatar" style="width:40px;height:40px;font-size:0.9rem;">
                    <?= strtoupper(substr($att['first_name'],0,1)) ?>
                </div>
                <div>
                    <div style="font-weight:700;"><?= e($att['first_name'].' '.$att['last_name']) ?></div>
                    <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($att['employee_number']) ?> · <?= e($att['dept'] ?? '—') ?></div>
                </div>
                <div style="margin-left:auto;text-align:right;">
                    <div style="font-weight:700;"><?= formatDate($att['attendance_date']) ?></div>
                    <?= attendanceStatusBadge($att['status']) ?>
                </div>
            </div>
        </div>

        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Clock Times</span></div>
                <div class="card-body">
                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group">
                            <label class="form-label">Sign In</label>
                            <input type="time" class="form-control" name="sign_in"
                                   value="<?= e($att['sign_in'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Break Out</label>
                            <input type="time" class="form-control" name="break_out"
                                   value="<?= e($att['break_out'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Break In</label>
                            <input type="time" class="form-control" name="break_in"
                                   value="<?= e($att['break_in'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sign Out</label>
                            <input type="time" class="form-control" name="sign_out"
                                   value="<?= e($att['sign_out'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">HR Remarks & Reason</span></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:16px;">
                        <label class="form-label">Reason for Change <span class="required">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required
                                  placeholder="Required — explain why this timesheet is being edited…"><?= e($_POST['reason'] ?? $att['adjustment_reason'] ?? '') ?></textarea>
                        <div class="form-hint">This reason is stored in the audit log permanently.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">HR Remarks (optional)</label>
                        <textarea class="form-control" name="hr_remarks" rows="2"><?= e($_POST['hr_remarks'] ?? $att['hr_remarks'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?= APP_URL ?>/modules/timesheets/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Side Panel -->
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Current Record</span></div>
            <div class="card-body">
                <?php $rows = [
                    ['Sign In', formatTime($att['sign_in'])],
                    ['Break Out', formatTime($att['break_out'])],
                    ['Break In', formatTime($att['break_in'])],
                    ['Sign Out', formatTime($att['sign_out'])],
                    ['Break Duration', minutesToHoursMinutes((int)$att['break_duration_minutes'])],
                    ['Total Hours', ($att['total_hours_worked'] ?? '0').' hrs'],
                    ['Overtime', ($att['overtime_hours'] ?? '0').' hrs'],
                    ['Late', $att['is_late'] ? 'Yes ('.$att['late_minutes'].' min)' : 'No'],
                ]; ?>
                <?php foreach ($rows as [$label,$val]): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light);font-size:0.75rem;">
                    <span style="color:var(--text-secondary);"><?= e($label) ?></span>
                    <span style="font-weight:500;"><?= e($val) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="background:var(--warning-bg);border-color:#FDE68A;">
            <div class="card-body" style="padding:14px;">
                <div style="font-size:0.72rem;font-weight:700;color:#92400E;margin-bottom:6px;">Important Rules</div>
                <ul style="font-size:0.72rem;color:#78350F;padding-left:16px;margin:0;line-height:1.8;">
                    <li>A reason is mandatory for all edits</li>
                    <li>All changes are logged in audit trail</li>
                    <li>Approved record will be reset on edit</li>
                    <li>Locked records require HR Manager</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
