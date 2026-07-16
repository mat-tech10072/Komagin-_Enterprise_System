<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('employees.delete', 'delete');

$id = (int)($_GET['id'] ?? $_POST['employee_id'] ?? 0);
if (!$id) {
    setFlash('error', 'No employee specified.');
    header('Location: ' . APP_URL . '/modules/employees/index.php'); exit;
}

$emp = getEmployee($id);
if (!$emp) {
    setFlash('error', 'Employee not found.');
    header('Location: ' . APP_URL . '/modules/employees/index.php'); exit;
}

// Prevent deleting yourself
if (isset($_SESSION['employee_id']) && (int)$_SESSION['employee_id'] === $id) {
    setFlash('error', 'You cannot delete your own employee record.');
    header('Location: ' . APP_URL . '/modules/employees/view.php?id=' . $id); exit;
}

// ── Gather deletion impact summary ────────────────────────────────────────
$impact = [];
$checks = [
    'attendance'          => ['table'=>'attendance',           'label'=>'Attendance records'],
    'leave'               => ['table'=>'leave_applications',   'label'=>'Leave applications'],
    'payslips'            => ['table'=>'payslips',             'label'=>'Payslips'],
    'leave_balances'      => ['table'=>'leave_balances',       'label'=>'Leave balances'],
    'overtime'            => ['table'=>'overtime_records',     'label'=>'Overtime records'],
    'timesheets'          => ['table'=>'correction_requests',  'label'=>'Timesheet corrections'],
    'performance'         => ['table'=>'performance_reviews',  'label'=>'Performance reviews'],
    'disciplinary'        => ['table'=>'disciplinary_records', 'label'=>'Disciplinary records'],
    'grievances'          => ['table'=>'grievance_records',    'label'=>'Grievances'],
    'documents'           => ['table'=>'employee_documents',   'label'=>'Documents'],
    'generated_docs'      => ['table'=>'generated_documents',  'label'=>'Generated documents'],
    'deductions'          => ['table'=>'payroll_deductions',   'label'=>'Payroll deductions'],
    'savings'             => ['table'=>'employee_savings',     'label'=>'Savings records'],
    'assets'              => ['table'=>'asset_assignments',    'label'=>'Asset assignments'],
    'training'            => ['table'=>'training_attendance',  'label'=>'Training records'],
    'onboarding'          => ['table'=>'onboarding_checklists','label'=>'Onboarding tasks'],
    'dependents'          => ['table'=>'employee_dependents',  'label'=>'Dependents'],
    'qualifications'      => ['table'=>'employee_qualifications','label'=>'Qualifications'],
    'skills'              => ['table'=>'employee_skills',      'label'=>'Skills'],
    'work_history'        => ['table'=>'employee_work_history','label'=>'Work history'],
    'requests'            => ['table'=>'employee_requests',    'label'=>'HR requests'],
    'status_history'      => ['table'=>'employee_status_history','label'=>'Status history'],
    'pending_updates'     => ['table'=>'employee_pending_updates','label'=>'Pending profile updates'],
];

foreach ($checks as $key => $info) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM `{$info['table']}` WHERE employee_id=?");
    $stmt->execute([$id]);
    $count = (int)$stmt->fetchColumn();
    if ($count > 0) {
        $impact[] = ['label' => $info['label'], 'count' => $count];
    }
}

// Check linked user account
$userStmt = db()->prepare("SELECT id, username FROM users WHERE employee_id=?");
$userStmt->execute([$id]); $linkedUser = $userStmt->fetch();

// ── Execute delete on POST confirmation ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.'); header('Location: ' . APP_URL . '/modules/employees/index.php'); exit;
    }
    if ($_POST['confirm_name'] !== $emp['employee_number']) {
        setFlash('error', 'Employee number did not match. Deletion cancelled.');
        header('Location: ' . APP_URL . '/modules/employees/delete.php?id=' . $id); exit;
    }

    // Audit log BEFORE deletion (record cannot exist after)
    auditLog('employees', 'hard_delete', $id,
        json_encode([
            'employee_number' => $emp['employee_number'],
            'name'            => $emp['first_name'] . ' ' . $emp['last_name'],
            'department'      => $emp['department_name'] ?? '—',
            'status'          => $emp['status'],
            'start_date'      => $emp['start_date'],
        ]),
        null,
        'Employee permanently deleted by ' . $_SESSION['user_name'] . '. ' . count($impact) . ' related record sets removed.'
    );

    // Delete linked user account if requested
    if (!empty($_POST['delete_user']) && $linkedUser) {
        db()->prepare("DELETE FROM users WHERE id=?")->execute([$linkedUser['id']]);
    } else if ($linkedUser) {
        // Delink only — keep login account but unlink from employee
        db()->prepare("UPDATE users SET employee_id=NULL WHERE id=?")->execute([$linkedUser['id']]);
    }

    // Hard delete — cascades to all 26 child tables automatically
    db()->prepare("DELETE FROM employees WHERE id=?")->execute([$id]);

    setFlash('success',
        'Employee ' . $emp['employee_number'] . ' (' . $emp['first_name'] . ' ' . $emp['last_name'] . ') ' .
        'has been permanently deleted along with all associated records.'
    );
    header('Location: ' . APP_URL . '/modules/employees/index.php'); exit;
}

$pageTitle  = 'Delete Employee — ' . $emp['first_name'] . ' ' . $emp['last_name'];
$activeMenu = 'employees';
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/index.php">Employees</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $id ?>"><?= e($emp['first_name'].' '.$emp['last_name']) ?></a></li>
                <li class="breadcrumb-item active">Permanent Delete</li>
            </ol>
        </nav>
        <h1 class="page-title" style="color:var(--danger);">Permanently Delete Employee</h1>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">Cancel — Keep Employee</a>
    </div>
</div>

<!-- Warning Banner -->
<div class="alert alert-danger" style="display:flex;gap:14px;align-items:flex-start;margin-bottom:20px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <div>
        <strong>This action is permanent and cannot be undone.</strong><br>
        All records listed below will be permanently erased from the database. There is no recovery option.
        If you want to keep the records but mark this employee as inactive, use
        <a href="<?= APP_URL ?>/modules/employees/status.php?id=<?= $id ?>" style="color:var(--danger);font-weight:600;">Change Status → Archived</a> instead.
    </div>
</div>

<!-- Employee Card -->
<div class="card" style="margin-bottom:20px;border:2px solid var(--danger);">
    <div class="card-body" style="display:flex;align-items:center;gap:16px;padding:16px 20px;">
        <div style="width:52px;height:52px;border-radius:50%;background:var(--danger-bg);border:2px solid var(--danger);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;color:var(--danger);flex-shrink:0;">
            <?= strtoupper(substr($emp['first_name'],0,1).substr($emp['last_name'],0,1)) ?>
        </div>
        <div style="flex:1;">
            <div style="font-size:1rem;font-weight:700;"><?= e($emp['first_name'].' '.$emp['last_name']) ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted);">
                <?= e($emp['position_title'] ?? '—') ?> · <?= e($emp['department_name'] ?? '—') ?>
            </div>
            <code style="font-size:0.75rem;background:var(--bg);padding:2px 8px;border-radius:4px;border:1px solid var(--border);margin-top:4px;display:inline-block;">
                <?= e($emp['employee_number']) ?>
            </code>
        </div>
        <div style="text-align:right;">
            <?= employeeStatusBadge($emp['status']) ?>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px;">Since <?= formatDate($emp['start_date']) ?></div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

    <!-- What will be deleted -->
    <div class="card">
        <div class="card-header" style="background:var(--danger-bg);">
            <span class="card-title" style="color:var(--danger);">Records that will be permanently deleted</span>
        </div>
        <div class="card-body" style="padding:12px 16px;">
            <?php if (empty($impact)): ?>
            <p style="font-size:0.82rem;color:var(--text-muted);">No associated records found. Only the employee profile will be deleted.</p>
            <?php else: ?>
            <?php foreach ($impact as $item): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border-light);font-size:0.80rem;">
                <span style="color:var(--text);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2.5" style="vertical-align:middle;margin-right:5px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    <?= e($item['label']) ?>
                </span>
                <span class="badge badge-danger" style="font-size:0.65rem;"><?= $item['count'] ?></span>
            </div>
            <?php endforeach; ?>
            <div style="margin-top:10px;padding:8px;background:var(--danger-bg);border-radius:5px;font-size:0.75rem;color:var(--danger);">
                <strong><?= count($impact) ?> record type(s)</strong> will be erased permanently.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Linked user account -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Linked Login Account</span>
        </div>
        <div class="card-body">
            <?php if ($linkedUser): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--warning-bg);border-radius:6px;border:1px solid #FDE68A;margin-bottom:12px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <div>
                    <div style="font-size:0.82rem;font-weight:600;">Username: <code><?= e($linkedUser['username']) ?></code></div>
                    <div style="font-size:0.72rem;color:var(--text-muted);">This account can still log in unless deleted.</div>
                </div>
            </div>
            <p style="font-size:0.80rem;color:var(--text-secondary);margin-bottom:8px;">What should happen to this login account?</p>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:0.80rem;">
                    <input type="radio" name="_user_action" value="delink" form="deleteForm" checked style="margin-top:2px;">
                    <span><strong>Keep account, remove employee link</strong><br>
                    <span style="color:var(--text-muted);font-size:0.72rem;">Username remains but has no employee profile.</span></span>
                </label>
                <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:0.80rem;">
                    <input type="radio" name="_user_action" value="delete" form="deleteForm" style="margin-top:2px;" id="deleteUserRadio">
                    <span><strong style="color:var(--danger);">Also delete the login account</strong><br>
                    <span style="color:var(--text-muted);font-size:0.72rem;">Username will no longer be able to log in.</span></span>
                </label>
            </div>
            <?php else: ?>
            <div style="padding:16px;text-align:center;color:var(--text-muted);font-size:0.82rem;">
                No linked login account found.<br>Only the employee record will be deleted.
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Confirmation Form -->
<div class="card" style="border:2px solid var(--danger);">
    <div class="card-header" style="background:var(--danger-bg);">
        <span class="card-title" style="color:var(--danger);">Confirm Permanent Deletion</span>
    </div>
    <form method="POST" id="deleteForm" onsubmit="return confirmDelete()">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="employee_id" value="<?= $id ?>">
        <input type="hidden" name="delete_user" id="deleteUserHidden" value="0">

        <div class="card-body">
            <p style="font-size:0.85rem;margin-bottom:16px;">
                To confirm deletion, type the employee number exactly as shown below:
            </p>
            <div style="font-size:1.1rem;font-weight:700;font-family:monospace;color:var(--danger);background:var(--danger-bg);padding:10px 16px;border-radius:6px;border:1px dashed var(--danger);margin-bottom:16px;letter-spacing:.05em;">
                <?= e($emp['employee_number']) ?>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" style="font-weight:600;">Type employee number to confirm <span class="required">*</span></label>
                <input type="text" name="confirm_name" id="confirmInput"
                    class="form-control"
                    placeholder="<?= e($emp['employee_number']) ?>"
                    autocomplete="off" required
                    style="font-family:monospace;font-size:1rem;letter-spacing:.05em;border:2px solid var(--border);"
                    oninput="checkMatch(this)">
                <div id="matchHint" style="font-size:0.72rem;margin-top:5px;color:var(--text-muted);">Type the employee number above to unlock deletion.</div>
            </div>
        </div>
        <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;background:var(--danger-bg);">
            <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $id ?>" class="btn btn-secondary">
                Cancel — Keep Employee
            </a>
            <button type="submit" id="deleteBtn" class="btn btn-danger" disabled style="opacity:0.4;cursor:not-allowed;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:5px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                Permanently Delete <?= e($emp['first_name'].' '.$emp['last_name']) ?>
            </button>
        </div>
    </form>
</div>

<script>
const EXPECTED = '<?= e($emp['employee_number']) ?>';

function checkMatch(input) {
    const btn  = document.getElementById('deleteBtn');
    const hint = document.getElementById('matchHint');
    const match = input.value.trim() === EXPECTED;
    btn.disabled = !match;
    btn.style.opacity = match ? '1' : '0.4';
    btn.style.cursor  = match ? 'pointer' : 'not-allowed';
    if (match) {
        input.style.borderColor = 'var(--danger)';
        hint.textContent = '✓ Match confirmed — delete button is now active.';
        hint.style.color = 'var(--danger)';
        hint.style.fontWeight = '600';
    } else {
        input.style.borderColor = 'var(--border)';
        hint.textContent = 'Type the employee number above to unlock deletion.';
        hint.style.color = 'var(--text-muted)';
        hint.style.fontWeight = 'normal';
    }
}

// Sync radio → hidden field
document.querySelectorAll('input[name="_user_action"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('deleteUserHidden').value = r.value === 'delete' ? '1' : '0';
    });
});

function confirmDelete() {
    const name = '<?= e($emp['first_name'].' '.$emp['last_name']) ?>';
    return confirm(
        'FINAL WARNING\n\n' +
        'You are about to permanently delete:\n' +
        name + ' (<?= e($emp['employee_number']) ?>)\n\n' +
        'This cannot be recovered. Are you absolutely sure?'
    );
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
