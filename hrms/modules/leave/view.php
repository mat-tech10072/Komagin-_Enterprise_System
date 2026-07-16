<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('leave.view', 'view');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/leave/index.php'); exit; }

$stmt = db()->prepare("SELECT la.*, lt.name as leave_type_name, lt.is_paid,
    e.first_name, e.last_name, e.employee_number, e.email,
    d.name as dept_name
    FROM leave_applications la
    JOIN leave_types lt ON la.leave_type_id=lt.id
    JOIN employees e ON la.employee_id=e.id
    LEFT JOIN departments d ON e.department_id=d.id
    WHERE la.id=?");
$stmt->execute([$id]);
$leave = $stmt->fetch();

if (!$leave) { setFlash('error','Leave application not found.'); header('Location: ' . APP_URL . '/modules/leave/index.php'); exit; }

// Access control: employee can only view own
if ($_SESSION['user_role'] === 'employee') {
    $empId = (int)($_SESSION['employee_id'] ?? 0);
    if ($leave['employee_id'] !== $empId) {
        setFlash('error','Access denied.');
        header('Location: ' . APP_URL . '/modules/leave/index.php'); exit;
    }
}

$isHR = canApprove('leave.approve');
$pageTitle  = 'Leave Application';
$activeMenu = 'leave';

// Balance
$bal = db()->prepare("SELECT * FROM leave_balances WHERE employee_id=? AND leave_type_id=? AND year=?");
$bal->execute([$leave['employee_id'], $leave['leave_type_id'], date('Y',strtotime($leave['start_date']))]);
$balance = $bal->fetch();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/leave/index.php">Leave</a></li>
                <li class="breadcrumb-item active">Application #<?= $id ?></li>
            </ol>
        </nav>
        <h1 class="page-title">Leave Application</h1>
    </div>
    <div class="page-actions">
        <?php if ($isHR && $leave['status'] === 'pending'): ?>
        <?php // KOM-009: these used to be inline POST forms sending
        // leave_id/action fields, but approve.php's actual contract is a
        // GET-loaded confirmation page (id/action query params) that
        // itself POSTs back to approve.php with the required remarks —
        // the field-name/method mismatch meant every click here silently
        // redirected back to the list with no action taken. Linking to
        // approve.php's real entry point instead also correctly routes
        // "Reject" through its required-remarks form, rather than the
        // hardcoded placeholder reason the old inline form silently sent. ?>
        <a href="<?= APP_URL ?>/modules/leave/approve.php?id=<?= $id ?>&action=reject" class="btn btn-danger btn-sm">Reject</a>
        <a href="<?= APP_URL ?>/modules/leave/approve.php?id=<?= $id ?>&action=approve" class="btn btn-success btn-sm">Approve</a>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 280px;gap:16px;">
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <span class="card-title">Application Details</span>
                <?= leaveStatusBadge($leave['status']) ?>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div>
                        <div style="font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Leave Type</div>
                        <div style="font-weight:600;"><?= e($leave['leave_type_name']) ?> <?= $leave['is_paid']?'<span class="badge badge-success">Paid</span>':'<span class="badge badge-warning">Unpaid</span>' ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Days Requested</div>
                        <div style="font-weight:700;font-size:1.1rem;"><?= $leave['total_days'] ?> day<?= $leave['total_days'] != 1 ? 's' : '' ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Start Date</div>
                        <div style="font-weight:600;"><?= formatDate($leave['start_date']) ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">End Date</div>
                        <div style="font-weight:600;"><?= formatDate($leave['end_date']) ?></div>
                    </div>
                </div>
                <hr style="border-color:var(--border);margin:16px 0;">
                <div>
                    <div style="font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Reason</div>
                    <p style="margin:0;"><?= nl2br(e($leave['reason'])) ?></p>
                </div>
                <?php if ($leave['hr_remarks'] && $leave['status'] === 'rejected'): ?>
                <div style="margin-top:16px;padding:12px 14px;background:#FEF2F2;border:1px solid #FECACA;border-radius:6px;">
                    <div style="font-size:0.7rem;font-weight:600;color:var(--danger);margin-bottom:4px;">REJECTION REASON</div>
                    <p style="margin:0;font-size:0.78rem;"><?= nl2br(e($leave['hr_remarks'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div>
        <!-- Employee Card -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Employee</span></div>
            <div class="card-body" style="padding:16px;">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                    <div class="emp-avatar" style="width:40px;height:40px;font-size:1rem;"><?= strtoupper(substr($leave['first_name'],0,1)) ?></div>
                    <div>
                        <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $leave['employee_id'] ?>" style="font-weight:700;color:var(--text);">
                            <?= e($leave['first_name'].' '.$leave['last_name']) ?>
                        </a>
                        <div class="emp-num"><?= e($leave['employee_number']) ?></div>
                    </div>
                </div>
                <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($leave['dept_name'] ?? '—') ?></div>
            </div>
        </div>

        <!-- Balance Card -->
        <?php if ($balance): ?>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Leave Balance <?= date('Y',strtotime($leave['start_date'])) ?></span></div>
            <div class="card-body" style="padding:16px;">
                <?php $used = $balance['used_days']; $total = $balance['entitled_days']; $pct = $total ? round(($used/$total)*100) : 0; ?>
                <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:8px;"><?= e($leave['leave_type_name']) ?></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:0.78rem;">
                    <span>Used: <strong><?= $used ?></strong></span>
                    <span>Entitled: <strong><?= $total ?></strong></span>
                </div>
                <div style="height:6px;background:var(--bg);border-radius:3px;overflow:hidden;">
                    <div style="width:<?= $pct ?>%;height:100%;background:<?= $pct>=80?'var(--danger)':'var(--success)' ?>;border-radius:3px;"></div>
                </div>
                <div style="margin-top:8px;font-size:0.72rem;color:var(--text-muted);">
                    Remaining: <strong><?= $balance['remaining_days'] ?></strong> days
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="card">
            <div class="card-header"><span class="card-title">Application Timeline</span></div>
            <div class="card-body" style="padding:16px;">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-dot" style="background:var(--primary);"></div>
                        <div>
                            <div style="font-weight:600;font-size:0.78rem;">Submitted</div>
                            <div style="font-size:0.7rem;color:var(--text-muted);"><?= formatDateTime($leave['created_at']) ?></div>
                        </div>
                    </div>
                    <?php if (in_array($leave['status'],['approved','rejected'])): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="background:<?= $leave['status']==='approved'?'var(--success)':'var(--danger)' ?>;"></div>
                        <div>
                            <div style="font-weight:600;font-size:0.78rem;"><?= ucfirst($leave['status']) ?></div>
                            <div style="font-size:0.7rem;color:var(--text-muted);"><?= $leave['hr_reviewed_at'] ? formatDateTime($leave['hr_reviewed_at']) : 'N/A' ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
