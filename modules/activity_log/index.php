<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requirePermission('activity_log.view', 'view');

$pageTitle  = 'Activity Logs';
$activeMenu = 'activity_log';

$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo   = $_GET['to']   ?? date('Y-m-d');
$tab      = $_GET['tab']  ?? 'admin';

$dfFull = $dateFrom . ' 00:00:00';
$dtFull = $dateTo   . ' 23:59:59';

// ── Admin / HR users ──────────────────────────────────────────────────────
$adminRoles = ['super_admin','hr_manager','hr_officer','hrofficer','payroll_manager',
               'payroll_officer','finance_viewer','recruitment_officer','training_officer','supervisor'];
$adminRoleIn = implode(',', array_fill(0, count($adminRoles), '?'));

$adminStmt = db()->prepare("
    SELECT u.id, u.username, u.first_name, u.last_name, u.role, u.email,
           u.is_active, u.last_login,
           COALESCE(e.first_name, u.first_name) AS disp_first,
           COALESCE(e.last_name,  u.last_name)  AS disp_last,
           COALESCE(e.photo, u.profile_photo)   AS photo,
           COUNT(a.id)      AS action_count,
           MAX(a.created_at) AS last_activity
    FROM users u
    LEFT JOIN employees e ON e.id = u.employee_id
    LEFT JOIN audit_logs a ON a.user_id = u.id
        AND a.created_at BETWEEN ? AND ?
    WHERE u.role IN ($adminRoleIn)
    GROUP BY u.id
    ORDER BY last_activity DESC, u.username ASC
");
$adminStmt->execute(array_merge([$dfFull, $dtFull], $adminRoles));
$adminUsers = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

$adminTotal = db()->prepare("SELECT COUNT(*) FROM audit_logs a
    JOIN users u ON u.id = a.user_id WHERE u.role IN ($adminRoleIn)
    AND a.created_at BETWEEN ? AND ?");
$adminTotal->execute(array_merge($adminRoles, [$dfFull, $dtFull]));
$adminTotalCount = (int)$adminTotal->fetchColumn();

// ── Employees ─────────────────────────────────────────────────────────────
$empStmt = db()->prepare("
    SELECT e.id, e.employee_number, e.first_name, e.last_name, e.email,
           e.status, e.photo, e.portal_last_login,
           u.id AS user_id, u.username,
           COUNT(a.id)       AS action_count,
           MAX(a.created_at) AS last_activity
    FROM employees e
    LEFT JOIN users u ON u.employee_id = e.id
    LEFT JOIN audit_logs a ON a.user_id = u.id
        AND a.created_at BETWEEN ? AND ?
    WHERE e.status NOT IN ('archived')
    GROUP BY e.id
    ORDER BY last_activity DESC, e.last_name ASC
");
$empStmt->execute([$dfFull, $dtFull]);
$employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);

$empTotalStmt = db()->prepare("SELECT COUNT(*) FROM audit_logs a
    JOIN users u ON u.id = a.user_id
    JOIN employees e ON e.id = u.employee_id
    WHERE a.created_at BETWEEN ? AND ?");
$empTotalStmt->execute([$dfFull, $dtFull]);
$empTotalCount = (int)$empTotalStmt->fetchColumn();

// ── Temp Employees ────────────────────────────────────────────────────────
$tempStmt = db()->query("
    SELECT te.id, te.employee_number, te.first_name, te.last_name,
           te.email, te.status, te.portal_last_login,
           te.start_date, te.end_date, te.position_title
    FROM temp_employees te
    ORDER BY te.portal_last_login DESC, te.last_name ASC
");
$tempEmployees = $tempStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Consultants ───────────────────────────────────────────────────────────
$conStmt = db()->prepare("
    SELECT c.id, c.consultant_number, c.first_name, c.last_name,
           c.email, c.type, c.status, c.portal_last_login,
           c.start_date, c.end_date, c.position_title,
           COUNT(ca.id)       AS action_count,
           MAX(ca.updated_at) AS last_activity
    FROM consultants c
    LEFT JOIN consultant_attendance ca ON ca.consultant_id = c.id
        AND ca.work_date BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY last_activity DESC, c.last_name ASC
");
$conStmt->execute([$dateFrom, $dateTo]);
$consultants = $conStmt->fetchAll(PDO::FETCH_ASSOC);

$conTotalStmt = db()->prepare("SELECT COUNT(*) FROM consultant_attendance ca
    WHERE ca.work_date BETWEEN ? AND ?");
$conTotalStmt->execute([$dateFrom, $dateTo]);
$conTotalCount = (int)$conTotalStmt->fetchColumn();

// ── Role label map ────────────────────────────────────────────────────────
$roleLabels = [
    'super_admin'         => 'Super Admin',
    'hr_manager'          => 'HR Manager',
    'hr_officer'          => 'HR Officer',
    'hrofficer'           => 'HR Officer',
    'supervisor'          => 'Supervisor',
    'payroll_manager'     => 'Payroll Manager',
    'payroll_officer'     => 'Payroll Officer',
    'finance_viewer'      => 'Finance Viewer',
    'recruitment_officer' => 'Recruitment Officer',
    'training_officer'    => 'Training Officer',
];
$roleBadge = [
    'super_admin'     => 'badge-danger',
    'hr_manager'      => 'badge-primary',
    'hr_officer'      => 'badge-info',
    'hrofficer'       => 'badge-info',
    'supervisor'      => 'badge-warning',
    'payroll_manager' => 'badge-success',
    'payroll_officer' => 'badge-success',
];

function statusDot(string $status): string {
    $map = ['active'=>'#22c55e','completed'=>'#6b7280','terminated'=>'#ef4444',
            'probation'=>'#f59e0b','suspended'=>'#f59e0b','on_leave'=>'#3b82f6','resigned'=>'#6b7280'];
    $c = $map[$status] ?? '#94a3b8';
    return "<span style='display:inline-block;width:7px;height:7px;border-radius:50%;background:{$c};margin-right:5px;'></span>";
}

$dlBase = APP_URL . '/modules/activity_log/download.php';
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Activity Logs</h1>
        <p class="page-subtitle">Complete system activity for all users — for legal and compliance reference</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <a href="<?= $dlBase ?>?export=category&type=all&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>"
           class="btn btn-primary btn-sm" title="Download all activity across all categories as CSV">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export All
        </a>
    </div>
</div>

<!-- Date Filter -->
<form method="GET" style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap;">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <label style="font-size:0.78rem;font-weight:600;color:var(--text-muted);">Date Range</label>
    <input type="date" class="form-control" name="from" value="<?= e($dateFrom) ?>" style="width:auto;">
    <span style="font-size:0.72rem;color:var(--text-muted);">to</span>
    <input type="date" class="form-control" name="to" value="<?= e($dateTo) ?>" style="width:auto;">
    <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
    <a href="?tab=<?= e($tab) ?>" class="btn btn-secondary btn-sm" style="opacity:.7;">Reset</a>
    <span style="font-size:0.7rem;color:var(--text-muted);margin-left:4px;"><?= e($dateFrom) ?> → <?= e($dateTo) ?></span>
</form>

<!-- Summary Stats -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
    <?php
    $summaryCards = [
        ['Admin / HR',        $adminTotalCount, count($adminUsers),   'badge-danger',   'admin'],
        ['Employees',         $empTotalCount,   count($employees),    'badge-primary',  'employees'],
        ['Temp Employees',    count($tempEmployees), count($tempEmployees), 'badge-warning', 'temp'],
        ['Consultants',       $conTotalCount,   count($consultants),  'badge-info',     'consultants'],
    ];
    foreach ($summaryCards as [$label, $actions, $people, $badge, $tabKey]):
    ?>
    <a href="?tab=<?= $tabKey ?>&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>"
       style="text-decoration:none;">
        <div class="card" style="padding:14px 16px;<?= $tab===$tabKey ? 'border:2px solid var(--primary);' : '' ?>">
            <div style="font-size:0.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:6px;"><?= $label ?></div>
            <div style="font-size:1.4rem;font-weight:700;color:var(--text);"><?= number_format($actions) ?></div>
            <div style="font-size:0.7rem;color:var(--text-muted);margin-top:2px;"><?= $actions === 1 ? 'action' : 'actions' ?> · <?= $people ?> <?= $people === 1 ? 'person' : 'people' ?></div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Tab Nav -->
<div style="display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px;">
    <?php
    $tabs = [
        'admin'       => 'Admin / HR Users',
        'employees'   => 'Employees',
        'temp'        => 'Temp Employees',
        'consultants' => 'Consultants',
    ];
    foreach ($tabs as $key => $label):
    ?>
    <a href="?tab=<?= $key ?>&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>"
       style="padding:9px 18px;font-size:0.78rem;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $tab===$key ? 'var(--primary)' : 'transparent' ?>;color:<?= $tab===$key ? 'var(--primary)' : 'var(--text-muted)' ?>;margin-bottom:-2px;">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<?php // ══════════════════════════════════════════════════════════ ADMIN TAB
if ($tab === 'admin'): ?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <span class="card-title">Admin &amp; HR Users (<?= count($adminUsers) ?>)</span>
        <a href="<?= $dlBase ?>?export=category&type=admin&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>"
           class="btn btn-secondary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download All Admin Logs
        </a>
    </div>
    <?php if (empty($adminUsers)): ?>
    <div class="empty-state"><div class="empty-state-title">No admin users found</div></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr>
                <th>User</th>
                <th>Role</th>
                <th>Email</th>
                <th style="text-align:right;">Actions (period)</th>
                <th>Last Activity</th>
                <th>Last Login</th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($adminUsers as $u):
                $name = trim(($u['disp_first'] ?? '') . ' ' . ($u['disp_last'] ?? '')) ?: $u['username'];
                $init = strtoupper(substr($u['disp_first'] ?: $u['username'], 0, 1) . substr($u['disp_last'] ?? '', 0, 1));
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <?php if ($u['photo']): ?>
                        <img src="<?= APP_URL ?>/<?= e($u['photo']) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                        <?php else: ?>
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;flex-shrink:0;"><?= e($init) ?></div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600;font-size:0.82rem;"><?= e($name) ?></div>
                            <div style="font-size:0.68rem;color:var(--text-muted);">@<?= e($u['username']) ?></div>
                        </div>
                    </div>
                </td>
                <td><span class="badge <?= $roleBadge[$u['role']] ?? 'badge-secondary' ?>"><?= $roleLabels[$u['role']] ?? ucwords(str_replace('_',' ',$u['role'])) ?></span></td>
                <td style="font-size:0.78rem;"><?= e($u['email']) ?></td>
                <td style="text-align:right;font-weight:700;font-size:0.88rem;"><?= number_format((int)$u['action_count']) ?></td>
                <td style="font-size:0.75rem;"><?= $u['last_activity'] ? formatDateTime($u['last_activity']) : '<span style="color:var(--text-muted);">No activity</span>' ?></td>
                <td style="font-size:0.75rem;"><?= $u['last_login'] ? formatDateTime($u['last_login']) : '<span style="color:var(--text-muted);">Never</span>' ?></td>
                <td style="white-space:nowrap;">
                    <a href="user.php?type=admin&id=<?= $u['id'] ?>&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="btn btn-secondary btn-sm" title="View activity">View</a>
                    <a href="<?= $dlBase ?>?export=user&type=admin&id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm" title="Download CSV" style="padding:4px 7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php // ════════════════════════════════════════════════════════ EMPLOYEES TAB
elseif ($tab === 'employees'): ?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <span class="card-title">Employees (<?= count($employees) ?>)</span>
        <a href="<?= $dlBase ?>?export=category&type=employees&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>"
           class="btn btn-secondary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download All Employee Logs
        </a>
    </div>
    <?php if (empty($employees)): ?>
    <div class="empty-state"><div class="empty-state-title">No employees found</div></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr>
                <th>Employee</th>
                <th>Employee #</th>
                <th>Status</th>
                <th>Portal Account</th>
                <th style="text-align:right;">Logged Actions</th>
                <th>Last Portal Login</th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($employees as $emp):
                $name = trim($emp['first_name'] . ' ' . $emp['last_name']);
                $init = strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1));
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <?php if ($emp['photo']): ?>
                        <img src="<?= APP_URL ?>/<?= e($emp['photo']) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                        <?php else: ?>
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;flex-shrink:0;"><?= e($init) ?></div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600;font-size:0.82rem;"><?= e($name) ?></div>
                            <?php if ($emp['email']): ?><div style="font-size:0.68rem;color:var(--text-muted);"><?= e($emp['email']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="font-family:monospace;font-size:0.75rem;"><?= e($emp['employee_number']) ?></td>
                <td><?= statusDot($emp['status']) ?><span style="font-size:0.75rem;"><?= ucfirst(str_replace('_',' ',$emp['status'])) ?></span></td>
                <td style="font-size:0.75rem;">
                    <?php if ($emp['user_id']): ?>
                    <span class="badge badge-success">Yes · @<?= e($emp['username']) ?></span>
                    <?php else: ?>
                    <span style="color:var(--text-muted);">Portal only</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;font-weight:700;font-size:0.88rem;"><?= number_format((int)$emp['action_count']) ?></td>
                <td style="font-size:0.75rem;"><?= $emp['portal_last_login'] ? formatDateTime($emp['portal_last_login']) : '<span style="color:var(--text-muted);">Never</span>' ?></td>
                <td style="white-space:nowrap;">
                    <a href="user.php?type=employee&id=<?= $emp['id'] ?>&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="btn btn-secondary btn-sm">View</a>
                    <a href="<?= $dlBase ?>?export=user&type=employee&id=<?= $emp['id'] ?>" class="btn btn-secondary btn-sm" title="Download CSV" style="padding:4px 7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php // ══════════════════════════════════════════════════════ TEMP EMPLOYEES TAB
elseif ($tab === 'temp'): ?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <span class="card-title">Temporary Employees (<?= count($tempEmployees) ?>)</span>
        <a href="<?= $dlBase ?>?export=category&type=temp"
           class="btn btn-secondary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download All Temp Logs
        </a>
    </div>
    <?php if (empty($tempEmployees)): ?>
    <div class="empty-state"><div class="empty-state-title">No temporary employees found</div></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr>
                <th>Name</th>
                <th>Employee #</th>
                <th>Position</th>
                <th>Status</th>
                <th>Contract Period</th>
                <th>Last Portal Login</th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($tempEmployees as $te):
                $name = trim($te['first_name'] . ' ' . $te['last_name']);
                $init = strtoupper(substr($te['first_name'], 0, 1) . substr($te['last_name'], 0, 1));
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--warning);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;flex-shrink:0;"><?= e($init) ?></div>
                        <div>
                            <div style="font-weight:600;font-size:0.82rem;"><?= e($name) ?></div>
                            <?php if ($te['email']): ?><div style="font-size:0.68rem;color:var(--text-muted);"><?= e($te['email']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="font-family:monospace;font-size:0.75rem;"><?= e($te['employee_number']) ?></td>
                <td style="font-size:0.75rem;"><?= e($te['position_title'] ?? '—') ?></td>
                <td><?= statusDot($te['status']) ?><span style="font-size:0.75rem;"><?= ucfirst($te['status']) ?></span></td>
                <td style="font-size:0.75rem;">
                    <?= $te['start_date'] ? date('d M Y', strtotime($te['start_date'])) : '—' ?>
                    <?= $te['end_date'] ? ' → ' . date('d M Y', strtotime($te['end_date'])) : '' ?>
                </td>
                <td style="font-size:0.75rem;"><?= $te['portal_last_login'] ? formatDateTime($te['portal_last_login']) : '<span style="color:var(--text-muted);">Never</span>' ?></td>
                <td style="white-space:nowrap;">
                    <a href="user.php?type=temp&id=<?= $te['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                    <a href="<?= $dlBase ?>?export=user&type=temp&id=<?= $te['id'] ?>" class="btn btn-secondary btn-sm" title="Download CSV" style="padding:4px 7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php // ══════════════════════════════════════════════════════ CONSULTANTS TAB
elseif ($tab === 'consultants'): ?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <span class="card-title">Consultants (<?= count($consultants) ?>)</span>
        <a href="<?= $dlBase ?>?export=category&type=consultants&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>"
           class="btn btn-secondary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download All Consultant Logs
        </a>
    </div>
    <?php if (empty($consultants)): ?>
    <div class="empty-state"><div class="empty-state-title">No consultants found</div></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr>
                <th>Consultant</th>
                <th>Number</th>
                <th>Type</th>
                <th>Status</th>
                <th style="text-align:right;">Sessions (period)</th>
                <th>Last Activity</th>
                <th>Last Portal Login</th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($consultants as $c):
                $name = trim($c['first_name'] . ' ' . $c['last_name']);
                $init = strtoupper(substr($c['first_name'], 0, 1) . substr($c['last_name'], 0, 1));
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:#0F766E;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;flex-shrink:0;"><?= e($init) ?></div>
                        <div>
                            <div style="font-weight:600;font-size:0.82rem;"><?= e($name) ?></div>
                            <?php if ($c['email']): ?><div style="font-size:0.68rem;color:var(--text-muted);"><?= e($c['email']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="font-family:monospace;font-size:0.75rem;"><?= e($c['consultant_number']) ?></td>
                <td><span class="badge <?= $c['type']==='time_based' ? 'badge-primary' : 'badge-info' ?>"><?= $c['type']==='time_based' ? 'Time-Based' : 'Output-Based' ?></span></td>
                <td><?= statusDot($c['status']) ?><span style="font-size:0.75rem;"><?= ucfirst($c['status']) ?></span></td>
                <td style="text-align:right;font-weight:700;font-size:0.88rem;"><?= number_format((int)$c['action_count']) ?></td>
                <td style="font-size:0.75rem;"><?= $c['last_activity'] ? formatDateTime($c['last_activity']) : '<span style="color:var(--text-muted);">No activity</span>' ?></td>
                <td style="font-size:0.75rem;"><?= $c['portal_last_login'] ? formatDateTime($c['portal_last_login']) : '<span style="color:var(--text-muted);">Never</span>' ?></td>
                <td style="white-space:nowrap;">
                    <a href="user.php?type=consultant&id=<?= $c['id'] ?>&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>" class="btn btn-secondary btn-sm">View</a>
                    <a href="<?= $dlBase ?>?export=user&type=consultant&id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" title="Download CSV" style="padding:4px 7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
