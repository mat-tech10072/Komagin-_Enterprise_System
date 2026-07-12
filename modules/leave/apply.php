<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';
require_once dirname(dirname(__DIR__)) . '/config/ApprovalEngine.php';

// Previously this page only called requireLogin() — any authenticated user,
// including roles with zero leave-related grants at all (payroll, finance,
// recruitment, training, kiosk), could reach it. leave.apply is a seeded
// permission (granted to employee/supervisor/hr_officer/hr_manager) that was
// never actually checked anywhere in code until now.
requirePermission('leave.apply', 'create');

$pageTitle  = 'Apply for Leave';
$activeMenu = 'leave';
$errors     = [];

$empId = (int)($_GET['emp'] ?? 0);

// Whether the current user may pick an arbitrary employee to apply on behalf
// of, versus only their own linked record, is a deliberate business rule
// (HR staff manage leave org-wide; supervisors/employees only their own) —
// not a gap in the permission matrix, so it stays a role check rather than
// a permission slug. See Permission Consistency Report for this reasoning.
$isHR = in_array($_SESSION['user_role'], ['super_admin','hr_manager','hr_officer']);
if (!$isHR) {
    $empId = (int)($_SESSION['employee_id'] ?? 0);
    if (!$empId) {
        setFlash('error','No employee profile linked to your account.');
        header('Location: ' . APP_URL . '/modules/leave/index.php'); exit;
    }
}

$emp = $empId ? getEmployee($empId) : null;
$leaveTypes = getLeaveTypes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $leaveEmpId   = $isHR ? (int)($_POST['employee_id'] ?? 0) : $empId;
        $leaveTypeId  = (int)($_POST['leave_type_id'] ?? 0);
        $startDate    = $_POST['start_date'] ?? '';
        $endDate      = $_POST['end_date'] ?? '';
        $reason       = trim($_POST['reason'] ?? '');
        $isHalfDay    = isset($_POST['is_half_day']) ? 1 : 0;

        if (!$leaveEmpId) $errors[] = 'Employee is required.';
        if (!$leaveTypeId) $errors[] = 'Leave type is required.';
        if (!$startDate || !$endDate) $errors[] = 'Date range is required.';
        if (!$reason) $errors[] = 'Reason is required.';

        if (empty($errors) && $startDate > $endDate) $errors[] = 'End date must be on or after start date.';

        if (empty($errors)) {
            // Calculate days (excluding weekends)
            $days = 0;
            if ($isHalfDay) {
                $days = 0.5;
            } else {
                $d = new DateTime($startDate);
                $endD = new DateTime($endDate);
                while ($d <= $endD) {
                    $dow = (int)$d->format('N');
                    if ($dow < 6) $days++;
                    $d->modify('+1 day');
                }
            }

            if ($days <= 0) { $errors[] = 'No working days in the selected range.'; }
        }

        if (empty($errors)) {
            // Check leave balance
            $bal = db()->prepare("SELECT * FROM leave_balances WHERE employee_id=? AND leave_type_id=? AND year=?");
            $bal->execute([$leaveEmpId, $leaveTypeId, date('Y')]);
            $balance = $bal->fetch();

            // Get leave type for paid/unpaid check
            $lt = db()->prepare("SELECT * FROM leave_types WHERE id=?");
            $lt->execute([$leaveTypeId]);
            $leaveType = $lt->fetch();

            if ($balance && $leaveType['is_paid'] && $days > $balance['remaining_days']) {
                $errors[] = "Insufficient leave balance. Available: {$balance['remaining_days']} days, Requested: {$days} days.";
            }
            $leaveYear = date('Y');

            if (empty($errors)) {
                // Check for overlapping applications
                $overlap = db()->prepare("SELECT id FROM leave_applications WHERE employee_id=? AND status NOT IN ('rejected','cancelled')
                    AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?) OR (? BETWEEN start_date AND end_date))");
                $overlap->execute([$leaveEmpId,$startDate,$endDate,$startDate,$endDate,$startDate]);
                if ($overlap->fetch()) $errors[] = 'An overlapping leave application already exists.';
            }
        }

        if (empty($errors)) {
            // Create application
            db()->prepare("INSERT INTO leave_applications
                (employee_id, leave_type_id, start_date, end_date, total_days, reason, status)
                VALUES (?,?,?,?,?,?,'pending')")
                ->execute([$leaveEmpId, $leaveTypeId, $startDate, $endDate, $days, $reason]);

            $newId = db()->lastInsertId();

            // Reserve pending days from balance
            if ($balance) {
                db()->prepare("UPDATE leave_balances SET pending_days=pending_days+?, remaining_days=remaining_days-? WHERE id=?")
                    ->execute([$days, $days, $balance['id']]);
            }

            auditLog('leave','apply',$newId,null,json_encode(['employee'=>$leaveEmpId,'type'=>$leaveTypeId,'days'=>$days,'start'=>$startDate,'end'=>$endDate]),$reason);

            // Create approval workflow
            try {
                $empForWf = getEmployee($leaveEmpId);
                $wfEngine = new ApprovalEngine(db());
                $wfTitle  = "Leave Application: ".($empForWf['first_name']??'').' '.($empForWf['last_name']??'')." — {$days} day(s) from {$startDate}";
                $wfEngine->create('leave', (int)$newId, 'leave_applications', $wfTitle, $_SESSION['user_id'], $leaveEmpId, 'normal', $endDate);
            } catch (Exception $wfEx) {
                error_log('Leave workflow creation failed: '.$wfEx->getMessage());
            }

            // Notify HR managers. KOM-007: this previously passed an array
            // as the $role argument (string-typed, no coercion possible)
            // with the remaining arguments shifted out of order — every
            // submission threw an uncaught TypeError at this line, after
            // the application record and balance reservation had already
            // committed successfully. notifyRole() takes one role at a
            // time, so call it once per role.
            $applicantName = ($empForWf['first_name'] ?? '') . ' ' . ($empForWf['last_name'] ?? '');
            foreach (['hr_manager','super_admin'] as $notifyRoleName) {
                notifyRole($notifyRoleName, 'warning', 'New Leave Application',
                    trim($applicantName) . " submitted a {$days}-day leave application requiring approval.",
                    APP_URL . '/modules/leave/index.php');
            }

            setFlash('success','Leave application submitted successfully.');
            header('Location: ' . APP_URL . '/modules/leave/index.php');
            exit;
        }
    }
}

$employees = $isHR ? db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as name FROM employees WHERE status IN ('active','probation','on_leave') ORDER BY first_name")->fetchAll() : [];
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/leave/index.php">Leave</a></li>
                <li class="breadcrumb-item active">Apply</li>
            </ol>
        </nav>
        <h1 class="page-title">Leave Application</h1>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach($errors as $err) echo e($err).'<br>'; ?></div>
<?php endif; ?>

<div style="max-width:640px;">
    <form method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Leave Details</span></div>
            <div class="card-body">
                <?php if ($isHR): ?>
                <div class="form-group">
                    <label class="form-label">Employee <span class="required">*</span></label>
                    <select class="form-select" name="employee_id" required id="empSelect" onchange="loadBalance()">
                        <option value="">Select employee</option>
                        <?php foreach ($employees as $em): ?>
                            <option value="<?= $em['id'] ?>" <?= $empId==$em['id']?'selected':''?>><?= e($em['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="employee_id" value="<?= $empId ?>">
                <?php if ($emp): ?>
                <div style="background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:12px 14px;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
                    <div class="emp-avatar" style="width:36px;height:36px;font-size:0.85rem;"><?= strtoupper(substr($emp['first_name'],0,1)) ?></div>
                    <div>
                        <div style="font-weight:700;"><?= e($emp['first_name'].' '.$emp['last_name']) ?></div>
                        <div class="emp-num"><?= e($emp['employee_number']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Leave Type <span class="required">*</span></label>
                    <select class="form-select" name="leave_type_id" required id="leaveTypeSelect" onchange="loadBalance()">
                        <option value="">Select leave type</option>
                        <?php foreach ($leaveTypes as $lt): ?>
                            <option value="<?= $lt['id'] ?>"><?= e($lt['name']) ?> <?= $lt['is_paid']?'(Paid)':'(Unpaid)' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Balance indicator -->
                <div id="balanceInfo" style="display:none;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:10px 14px;margin-bottom:16px;font-size:0.78rem;">
                    <span id="balanceText"></span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date <span class="required">*</span></label>
                        <input type="date" class="form-control" name="start_date" required id="startDate"
                               min="<?= date('Y-m-d') ?>" onchange="calcDays()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date <span class="required">*</span></label>
                        <input type="date" class="form-control" name="end_date" required id="endDate"
                               min="<?= date('Y-m-d') ?>" onchange="calcDays()">
                    </div>
                </div>

                <div id="daysCalc" style="display:none;padding:10px 14px;background:var(--bg-card);border:1px solid var(--border);border-radius:6px;margin-bottom:16px;">
                    Working days: <strong id="daysCount">0</strong>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_half_day" id="halfDayCheck" onchange="calcDays()">
                        <span class="form-label" style="margin:0;">Half Day</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Reason <span class="required">*</span></label>
                    <textarea class="form-control" name="reason" rows="3" required
                              placeholder="Briefly describe the reason for leave…"></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">Submit Application</button>
                <a href="<?= APP_URL ?>/modules/leave/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
function calcDays() {
    const start = document.getElementById('startDate').value;
    const end   = document.getElementById('endDate').value;
    const half  = document.getElementById('halfDayCheck').checked;
    const div   = document.getElementById('daysCalc');
    const countEl = document.getElementById('daysCount');
    if (!start || !end) { div.style.display='none'; return; }

    if (half) {
        countEl.textContent = '0.5';
        div.style.display = 'block';
        return;
    }

    let count = 0;
    let d = new Date(start);
    const e = new Date(end);
    while (d <= e) {
        const dow = d.getDay();
        if (dow !== 0 && dow !== 6) count++;
        d.setDate(d.getDate()+1);
    }
    countEl.textContent = count;
    div.style.display = 'block';
}

function loadBalance() {
    const empSel  = document.getElementById('empSelect');
    const ltSel   = document.getElementById('leaveTypeSelect');
    const empId   = empSel ? empSel.value : <?= $empId ?: 'null' ?>;
    const ltId    = ltSel ? ltSel.value : '';
    const info    = document.getElementById('balanceInfo');
    if (!empId || !ltId) { info.style.display='none'; return; }

    fetch(`<?= APP_URL ?>/api/leave_balance.php?emp=${empId}&lt=${ltId}`)
        .then(r=>r.json())
        .then(data => {
            if (data.found) {
                document.getElementById('balanceText').innerHTML =
                    `Balance: <strong>${data.remaining}</strong> days remaining of ${data.total_days} (${data.used} used, ${data.pending} pending)`;
                info.style.display = 'block';
            } else {
                document.getElementById('balanceText').textContent = 'No balance record found for this year.';
                info.style.display = 'block';
            }
        }).catch(()=>{ info.style.display='none'; });
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
