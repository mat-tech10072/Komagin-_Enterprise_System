<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('timesheets.approve_ot', 'view');

$pageTitle  = 'Overtime Approvals';
$activeMenu = 'timesheets';

$statusFilter = $_GET['status'] ?? 'pending';
$deptFilter   = (int)($_GET['dept'] ?? 0);
$monthFilter  = $_GET['month'] ?? date('Y-m');
$page         = max(1,(int)($_GET['page'] ?? 1));
$perPage      = 25;

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action   = $_POST['action'] ?? '';
    $otId     = (int)($_POST['ot_id'] ?? 0);
    $reason   = trim($_POST['reason'] ?? '');

    if ($otId && in_array($action,['approve','reject'])) {
        // The page-level gate above only checks can_view — approving/rejecting
        // overtime is a distinct, higher-privilege action and must be checked
        // explicitly here rather than assumed from page access (KOM-010/H-04).
        requirePermission('timesheets.approve_ot', 'approve');
        $otStmt = db()->prepare("SELECT * FROM overtime_records WHERE id=?");
        $otStmt->execute([$otId]);
        $ot = $otStmt->fetch();

        if ($ot) {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            // On approval, copy suggested_hours → approved_hours
            $approvedHours = $action === 'approve' ? $ot['suggested_hours'] : 0;
            db()->prepare("UPDATE overtime_records SET status=?, approved_hours=?, reviewed_by=?, reviewed_at=NOW(), hr_remarks=? WHERE id=?")
                ->execute([$newStatus, $approvedHours, $_SESSION['user_id'], $reason ?: null, $otId]);

            auditLog('timesheets','overtime_'.$action,$otId,
                json_encode(['status'=>$ot['status']]),
                json_encode(['status'=>$newStatus,'reason'=>$reason]),
                $reason);

            setFlash('success','Overtime record '.ucfirst($newStatus).'.');
        }
    }
    header('Location: ' . APP_URL . '/modules/timesheets/overtime.php?status='.$statusFilter);
    exit;
}

$where  = ['1=1'];
$params = [];

if ($statusFilter !== 'all') { $where[] = 'ot.status = ?'; $params[] = $statusFilter; }
if ($deptFilter) { $where[] = 'e.department_id = ?'; $params[] = $deptFilter; }
if ($monthFilter) {
    [$y,$m] = explode('-',$monthFilter);
    $where[] = 'YEAR(ot.overtime_date)=? AND MONTH(ot.overtime_date)=?';
    $params[] = (int)$y; $params[] = (int)$m;
}

$whereSQL = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM overtime_records ot
    JOIN employees e ON ot.employee_id=e.id
    LEFT JOIN attendance a ON ot.attendance_id=a.id
    WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total,$perPage,$page);

$stmt = db()->prepare("SELECT ot.*, e.first_name, e.last_name, e.employee_number, a.attendance_date as date,
    d.name as dept_name, u.username as approver_name
    FROM overtime_records ot
    JOIN employees e ON ot.employee_id=e.id
    JOIN attendance a ON ot.attendance_id=a.id
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN users u ON ot.reviewed_by=u.id
    WHERE $whereSQL
    ORDER BY a.attendance_date DESC, ot.created_at DESC
    LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$records = $stmt->fetchAll();

// Summary stats
$statsStmt = db()->query("SELECT status, COUNT(*) as cnt, COALESCE(SUM(suggested_hours),0) as total_hours
    FROM overtime_records GROUP BY status");
$stats = [];
foreach ($statsStmt->fetchAll() as $row) $stats[$row['status']] = $row;

$departments = getDepartments();
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Overtime Approvals</h1>
        <p class="page-subtitle">Review and approve overtime records</p>
    </div>
</div>

<!-- Stats -->
<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="kpi-card kpi-warning">
        <div class="kpi-card-label">Pending</div>
        <div class="kpi-card-value"><?= $stats['pending']['cnt'] ?? 0 ?></div>
        <div class="kpi-card-sub"><?= round($stats['pending']['total_hours'] ?? 0,1) ?>h</div>
    </div>
    <div class="kpi-card kpi-success">
        <div class="kpi-card-label">Approved</div>
        <div class="kpi-card-value"><?= $stats['approved']['cnt'] ?? 0 ?></div>
        <div class="kpi-card-sub"><?= round($stats['approved']['total_hours'] ?? 0,1) ?>h</div>
    </div>
    <div class="kpi-card kpi-danger">
        <div class="kpi-card-label">Rejected</div>
        <div class="kpi-card-value"><?= $stats['rejected']['cnt'] ?? 0 ?></div>
        <div class="kpi-card-sub"><?= round($stats['rejected']['total_hours'] ?? 0,1) ?>h</div>
    </div>
    <div class="kpi-card kpi-info">
        <div class="kpi-card-label">Total Hours (All)</div>
        <div class="kpi-card-value"><?= round(array_sum(array_column($stats,'total_hours')),1) ?></div>
        <div class="kpi-card-sub">hours overtime</div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <select class="form-select" name="status" style="width:auto;" onchange="this.form.submit()">
                <option value="pending" <?= $statusFilter==='pending'?'selected':''?>>Pending</option>
                <option value="approved" <?= $statusFilter==='approved'?'selected':''?>>Approved</option>
                <option value="rejected" <?= $statusFilter==='rejected'?'selected':''?>>Rejected</option>
                <option value="all" <?= $statusFilter==='all'?'selected':''?>>All</option>
            </select>
            <input type="month" class="form-control" name="month" value="<?= e($monthFilter) ?>" style="width:auto;">
            <select class="form-select" name="dept" style="width:auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':''?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);"><?= $total ?> records</div>
    </div>

    <div class="table-wrapper" style="border:none;">
        <?php if (empty($records)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No overtime records found</div>
            <div class="empty-state-desc">Overtime is automatically suggested when employees work beyond the threshold.</div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Dept</th>
                    <th>OT Hours</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Approved By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $ot): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:0.78rem;"><?= e($ot['first_name'].' '.$ot['last_name']) ?></div>
                    <div class="emp-num"><?= e($ot['employee_number']) ?></div>
                </td>
                <td style="font-size:0.75rem;"><?= formatDate($ot['date']) ?></td>
                <td style="font-size:0.72rem;"><?= e($ot['dept_name'] ?? '—') ?></td>
                <td>
                    <span style="font-weight:700;font-size:0.9rem;"><?= number_format($ot['suggested_hours'],1) ?></span>
                    <span style="font-size:0.65rem;color:var(--text-muted);">hrs</span>
                </td>
                <td>
                    <?php $types=['standard'=>'Standard','saturday'=>'Saturday','sunday'=>'Sunday','public_holiday'=>'P. Holiday'];
                    $otType = $ot['overtime_type'] ?? ''; ?>
                    <span class="badge badge-secondary"><?= $types[$otType] ?? ($otType ? ucfirst($otType) : '—') ?></span>
                </td>
                <td>
                    <?php $sc=['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'];?>
                    <span class="badge <?= $sc[$ot['status']] ?? 'badge-secondary' ?>"><?= ucfirst($ot['status']) ?></span>
                </td>
                <td style="font-size:0.72rem;">
                    <?= $ot['approver_name'] ? e($ot['approver_name']) : '—' ?>
                </td>
                <td>
                    <?php if ($ot['status'] === 'pending'): ?>
                    <div class="table-actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="ot_id" value="<?= $ot['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--success);"
                                    onclick="return confirm('Approve this overtime record?')">Approve</button>
                        </form>
                        <button class="btn btn-ghost btn-sm" style="color:var(--danger);"
                                onclick="openRejectModal(<?= $ot['id'] ?>)">Reject</button>
                    </div>
                    <?php else: ?>
                    <span style="font-size:0.72rem;color:var(--text-muted);"><?= $ot['reviewed_at'] ? formatDate($ot['reviewed_at']) : '—' ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Reject Overtime</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="ot_id" id="rejectOtId" value="">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Reason for Rejection <span class="required">*</span></label>
                    <textarea class="form-control" name="reason" rows="3" required
                              placeholder="Explain why this overtime is being rejected…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Reject Overtime</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(otId) {
    document.getElementById('rejectOtId').value = otId;
    openModal('rejectModal');
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
