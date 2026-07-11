<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();

if ($_SESSION['user_role'] !== 'super_admin') {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$type     = $_GET['type'] ?? '';
$id       = (int)($_GET['id']   ?? 0);
$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-90 days'));
$dateTo   = $_GET['to']   ?? date('Y-m-d');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 50;

if (!$id || !in_array($type, ['admin','employee','temp','consultant'])) {
    header('Location: index.php');
    exit;
}

$dfFull = $dateFrom . ' 00:00:00';
$dtFull = $dateTo   . ' 23:59:59';

$subject  = [];
$logs     = [];
$total    = 0;
$backTab  = $type === 'temp' ? 'temp' : $type . 's';
if ($type === 'admin') $backTab = 'admin';

$actionColors = [
    'login'=>'success','logout'=>'secondary','create'=>'primary','edit'=>'warning',
    'update'=>'warning','delete'=>'danger','approve'=>'success','reject'=>'danger',
    'password_change'=>'warning','update_profile'=>'info','change_password'=>'warning',
    'clock_in'=>'success','break_start'=>'warning','break_end'=>'info','clock_out'=>'secondary',
];

// ── Load subject + logs by type ───────────────────────────────────────────
if ($type === 'admin') {
    $row = db()->prepare("SELECT u.*,
        COALESCE(e.first_name, u.first_name) AS disp_first,
        COALESCE(e.last_name,  u.last_name)  AS disp_last,
        COALESCE(e.photo, u.profile_photo)   AS photo,
        e.employee_number
        FROM users u LEFT JOIN employees e ON e.id = u.employee_id WHERE u.id = ?");
    $row->execute([$id]);
    $subject = $row->fetch(PDO::FETCH_ASSOC);
    if (!$subject) { header('Location: index.php'); exit; }

    $countStmt = db()->prepare("SELECT COUNT(*) FROM audit_logs WHERE user_id=? AND created_at BETWEEN ? AND ?");
    $countStmt->execute([$id, $dfFull, $dtFull]);
    $total = (int)$countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $stmt = db()->prepare("SELECT * FROM audit_logs WHERE user_id=? AND created_at BETWEEN ? AND ?
        ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute([$id, $dfFull, $dtFull]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pageTitle = 'Activity: ' . (trim(($subject['disp_first']??'').' '.($subject['disp_last']??'')) ?: $subject['username']);

} elseif ($type === 'employee') {
    $row = db()->prepare("SELECT e.*,
        u.id AS user_id, u.username, u.role, u.last_login
        FROM employees e LEFT JOIN users u ON u.employee_id = e.id WHERE e.id = ?");
    $row->execute([$id]);
    $subject = $row->fetch(PDO::FETCH_ASSOC);
    if (!$subject) { header('Location: index.php'); exit; }

    if ($subject['user_id']) {
        $countStmt = db()->prepare("SELECT COUNT(*) FROM audit_logs WHERE user_id=? AND created_at BETWEEN ? AND ?");
        $countStmt->execute([$subject['user_id'], $dfFull, $dtFull]);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = db()->prepare("SELECT * FROM audit_logs WHERE user_id=? AND created_at BETWEEN ? AND ?
            ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute([$subject['user_id'], $dfFull, $dtFull]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $pageTitle = 'Activity: ' . trim($subject['first_name'] . ' ' . $subject['last_name']);

} elseif ($type === 'temp') {
    $row = db()->prepare("SELECT * FROM temp_employees WHERE id = ?");
    $row->execute([$id]);
    $subject = $row->fetch(PDO::FETCH_ASSOC);
    if (!$subject) { header('Location: index.php'); exit; }

    $pageTitle = 'Activity: ' . trim($subject['first_name'] . ' ' . $subject['last_name']);

} elseif ($type === 'consultant') {
    $row = db()->prepare("SELECT * FROM consultants WHERE id = ?");
    $row->execute([$id]);
    $subject = $row->fetch(PDO::FETCH_ASSOC);
    if (!$subject) { header('Location: index.php'); exit; }

    $countStmt = db()->prepare("SELECT COUNT(*) FROM consultant_attendance WHERE consultant_id=? AND work_date BETWEEN ? AND ?");
    $countStmt->execute([$id, $dateFrom, $dateTo]);
    $total = (int)$countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $stmt = db()->prepare("SELECT * FROM consultant_attendance WHERE consultant_id=? AND work_date BETWEEN ? AND ?
        ORDER BY work_date DESC, id DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute([$id, $dateFrom, $dateTo]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pageTitle = 'Activity: ' . trim($subject['first_name'] . ' ' . $subject['last_name']);
}

$pagination = paginate($total, $perPage, $page);

$roleLabels = [
    'super_admin'=>'Super Admin','hr_manager'=>'HR Manager','hr_officer'=>'HR Officer',
    'hrofficer'=>'HR Officer','supervisor'=>'Supervisor','employee'=>'Employee',
    'payroll_manager'=>'Payroll Manager','payroll_officer'=>'Payroll Officer',
    'finance_viewer'=>'Finance Viewer','recruitment_officer'=>'Recruitment Officer',
    'training_officer'=>'Training Officer',
];

$activeMenu = 'activity_log';
$dlBase = APP_URL . '/modules/activity_log/download.php';
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="index.php?tab=<?= e($backTab) ?>&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="btn btn-secondary btn-sm">← Back</a>
        <div>
            <h1 class="page-title"><?= e($pageTitle) ?></h1>
            <p class="page-subtitle">
                <?php if ($type === 'admin'): ?>
                    <?= $roleLabels[$subject['role']] ?? ucwords(str_replace('_',' ',$subject['role'])) ?> · <?= e($subject['email']) ?>
                <?php elseif ($type === 'employee'): ?>
                    Employee · <?= e($subject['employee_number']) ?> · <?= e($subject['email'] ?? '') ?>
                <?php elseif ($type === 'temp'): ?>
                    Temp Employee · <?= e($subject['employee_number']) ?> · <?= e($subject['position_title'] ?? '') ?>
                <?php elseif ($type === 'consultant'): ?>
                    <?= $subject['type']==='time_based'?'Time-Based':'Output-Based' ?> Consultant · <?= e($subject['consultant_number']) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <a href="<?= $dlBase ?>?export=user&type=<?= urlencode($type) ?>&id=<?= $id ?>" class="btn btn-primary btn-sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download CSV
    </a>
</div>

<!-- Date Filter -->
<form method="GET" style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap;">
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <input type="hidden" name="id"   value="<?= $id ?>">
    <label style="font-size:0.78rem;font-weight:600;color:var(--text-muted);">Date Range</label>
    <input type="date" class="form-control" name="from" value="<?= e($dateFrom) ?>" style="width:auto;">
    <span style="font-size:0.72rem;color:var(--text-muted);">to</span>
    <input type="date" class="form-control" name="to" value="<?= e($dateTo) ?>" style="width:auto;">
    <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
    <span style="font-size:0.7rem;color:var(--text-muted);margin-left:4px;"><?= $total ?> records found</span>
</form>

<!-- Profile Card + Stats -->
<div style="display:grid;grid-template-columns:260px 1fr;gap:16px;margin-bottom:20px;align-items:start;">
    <div class="card" style="padding:20px;">
        <?php
        if ($type === 'admin'):
            $dispName = trim(($subject['disp_first']??'').(' '.($subject['disp_last']??''))) ?: $subject['username'];
            $photo    = $subject['photo'] ?? null;
            $init     = strtoupper(substr($subject['disp_first']??$subject['username'],0,1).substr($subject['disp_last']??'',0,1));
        elseif ($type === 'employee'):
            $dispName = trim($subject['first_name'].' '.$subject['last_name']);
            $photo    = $subject['photo'] ?? null;
            $init     = strtoupper(substr($subject['first_name'],0,1).substr($subject['last_name'],0,1));
        elseif ($type === 'temp'):
            $dispName = trim($subject['first_name'].' '.$subject['last_name']);
            $photo    = null;
            $init     = strtoupper(substr($subject['first_name'],0,1).substr($subject['last_name'],0,1));
        else:
            $dispName = trim($subject['first_name'].' '.$subject['last_name']);
            $photo    = null;
            $init     = strtoupper(substr($subject['first_name'],0,1).substr($subject['last_name'],0,1));
        endif;
        ?>
        <div style="text-align:center;margin-bottom:14px;">
            <?php if ($photo): ?>
            <img src="<?= APP_URL ?>/<?= e($photo) ?>" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
            <?php else: ?>
            <div style="width:64px;height:64px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;margin:0 auto;"><?= e($init) ?></div>
            <?php endif; ?>
            <div style="margin-top:10px;font-weight:700;font-size:0.9rem;"><?= e($dispName) ?></div>
        </div>
        <table style="width:100%;font-size:0.74rem;border-collapse:collapse;">
            <?php if ($type === 'admin'): ?>
            <tr><td style="color:var(--text-muted);padding:4px 0;width:40%">Role</td><td style="font-weight:600;"><?= $roleLabels[$subject['role']] ?? $subject['role'] ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Username</td><td>@<?= e($subject['username']) ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Email</td><td><?= e($subject['email']) ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Status</td><td><?= $subject['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>' ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Last Login</td><td><?= $subject['last_login'] ? formatDateTime($subject['last_login']) : '—' ?></td></tr>
            <?php elseif ($type === 'employee'): ?>
            <tr><td style="color:var(--text-muted);padding:4px 0;width:40%">Emp #</td><td style="font-family:monospace;"><?= e($subject['employee_number']) ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Email</td><td><?= e($subject['email'] ?? '—') ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Status</td><td><?= ucfirst(str_replace('_',' ',$subject['status'])) ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Portal Login</td><td><?= $subject['portal_last_login'] ? formatDateTime($subject['portal_last_login']) : '—' ?></td></tr>
            <?php if ($subject['user_id']): ?>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Account</td><td>@<?= e($subject['username']) ?></td></tr>
            <?php endif; ?>
            <?php elseif ($type === 'temp'): ?>
            <tr><td style="color:var(--text-muted);padding:4px 0;width:40%">Emp #</td><td style="font-family:monospace;"><?= e($subject['employee_number']) ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Position</td><td><?= e($subject['position_title'] ?? '—') ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Status</td><td><?= ucfirst($subject['status']) ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Start</td><td><?= $subject['start_date'] ? date('d M Y', strtotime($subject['start_date'])) : '—' ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">End</td><td><?= $subject['end_date'] ? date('d M Y', strtotime($subject['end_date'])) : '—' ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Portal Login</td><td><?= $subject['portal_last_login'] ? formatDateTime($subject['portal_last_login']) : '—' ?></td></tr>
            <?php elseif ($type === 'consultant'): ?>
            <tr><td style="color:var(--text-muted);padding:4px 0;width:40%">Con #</td><td style="font-family:monospace;"><?= e($subject['consultant_number']) ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Type</td><td><?= $subject['type']==='time_based'?'Time-Based':'Output-Based' ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Status</td><td><?= ucfirst($subject['status']) ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Start</td><td><?= $subject['start_date'] ? date('d M Y', strtotime($subject['start_date'])) : '—' ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">End</td><td><?= $subject['end_date'] ? date('d M Y', strtotime($subject['end_date'])) : '—' ?></td></tr>
            <tr><td style="color:var(--text-muted);padding:4px 0;">Portal Login</td><td><?= $subject['portal_last_login'] ? formatDateTime($subject['portal_last_login']) : '—' ?></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Activity Timeline -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <?= $type === 'consultant' ? 'Attendance Records' : 'Action Log' ?>
                <span style="font-size:0.7rem;font-weight:400;color:var(--text-muted);margin-left:8px;"><?= number_format($total) ?> total</span>
            </span>
        </div>

        <?php if ($type === 'temp'): ?>
        <!-- Temp employees: no audit_log, show record summary -->
        <div class="card-body">
            <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.7;">
                <p>Temporary employees do not have a tracked action log in the system. Their record reflects portal access and employment dates.</p>
                <?php if ($subject['portal_last_login']): ?>
                <p><strong>Last portal access:</strong> <?= formatDateTime($subject['portal_last_login']) ?></p>
                <?php else: ?>
                <p>This employee has never accessed the employee portal.</p>
                <?php endif; ?>
                <?php if ($subject['portal_active']): ?>
                <p><span class="badge badge-success">Portal Active</span></p>
                <?php else: ?>
                <p><span class="badge badge-secondary">Portal Inactive</span></p>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif ($type === 'employee' && !$subject['user_id']): ?>
        <div class="card-body">
            <div style="font-size:0.82rem;color:var(--text-muted);">
                <p>This employee does not have a linked system user account. No action log entries are available.</p>
                <?php if ($subject['portal_last_login']): ?>
                <p><strong>Last portal login:</strong> <?= formatDateTime($subject['portal_last_login']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif (empty($logs)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No activity in this period</div>
            <div class="empty-state-desc">Try expanding the date range.</div>
        </div>
        <?php elseif ($type === 'consultant'): ?>
        <!-- Consultant: show attendance records -->
        <div class="table-wrapper">
            <table class="table">
                <thead><tr>
                    <th>Work Date</th>
                    <th>Clock In</th>
                    <th>Break Out</th>
                    <th>Break In</th>
                    <th>Clock Out</th>
                    <th style="text-align:right;">Hours</th>
                    <th>Notes</th>
                </tr></thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="font-weight:600;font-size:0.78rem;"><?= date('D, d M Y', strtotime($log['work_date'])) ?></td>
                    <td style="font-size:0.75rem;"><?= $log['clock_in']    ? date('H:i', strtotime($log['clock_in']))    : '—' ?></td>
                    <td style="font-size:0.75rem;"><?= $log['break_start'] ? date('H:i', strtotime($log['break_start'])) : '—' ?></td>
                    <td style="font-size:0.75rem;"><?= $log['break_end']   ? date('H:i', strtotime($log['break_end']))   : '—' ?></td>
                    <td style="font-size:0.75rem;"><?= $log['clock_out']   ? date('H:i', strtotime($log['clock_out']))   : '<span style="color:var(--warning);">In progress</span>' ?></td>
                    <td style="text-align:right;font-weight:600;font-size:0.78rem;"><?= $log['total_hours'] !== null ? number_format((float)$log['total_hours'], 2) . ' h' : '—' ?></td>
                    <td style="font-size:0.72rem;color:var(--text-muted);"><?= e($log['notes'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <!-- Admin / Employee: show audit_logs timeline -->
        <div class="timeline" style="padding:16px 20px;">
            <?php foreach ($logs as $log):
                $aColor = $actionColors[$log['action']] ?? 'primary';
            ?>
            <div class="timeline-item">
                <div class="timeline-dot" style="background:var(--<?= $aColor ?>);"></div>
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
                    <div style="flex:1;">
                        <div class="timeline-meta">
                            <?= formatDateTime($log['created_at']) ?>
                            <?php if ($log['ip_address']): ?>
                            <span style="opacity:.6;font-size:0.66rem;"> · IP: <?= e($log['ip_address']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-title" style="margin-top:3px;">
                            <span class="badge badge-<?= $aColor ?>"><?= e(ucfirst(str_replace('_',' ',$log['action']))) ?></span>
                            <span style="font-size:0.76rem;color:var(--text-muted);margin-left:6px;"><?= e(ucfirst($log['module'])) ?></span>
                            <?php if ($log['record_id']): ?>
                            <span style="font-size:0.68rem;color:var(--text-muted);"> · #<?= $log['record_id'] ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($log['reason']): ?>
                        <div class="timeline-desc"><?= e($log['reason']) ?></div>
                        <?php endif; ?>
                        <?php if ($log['old_value'] || $log['new_value']): ?>
                        <div style="display:flex;gap:8px;margin-top:5px;flex-wrap:wrap;">
                            <?php if ($log['old_value']): ?>
                            <span class="diff-old" style="font-size:0.68rem;">Before: <?= e(mb_strimwidth($log['old_value'], 0, 120, '…')) ?></span>
                            <?php endif; ?>
                            <?php if ($log['new_value']): ?>
                            <span class="diff-new" style="font-size:0.68rem;">After: <?= e(mb_strimwidth($log['new_value'], 0, 120, '…')) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:0.72rem;color:var(--text-muted);">Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></span>
            <ul class="pagination">
                <?php for ($i = max(1,$page-2); $i <= min($pagination['total_pages'],$page+2); $i++): ?>
                <li class="page-item <?= $i===$page?'active':'' ?>">
                    <a class="page-link" href="?type=<?= urlencode($type) ?>&id=<?= $id ?>&from=<?= $dateFrom ?>&to=<?= $dateTo ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
