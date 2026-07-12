<?php
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

requireLogin();

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';

$today = date('Y-m-d');
$thisMonth = date('Y-m');
$thisYear  = date('Y');

// ============================================================
// KPI DATA
// ============================================================
try {
    $totalEmp     = db()->query("SELECT COUNT(*) FROM employees WHERE status NOT IN ('archived','deceased')")->fetchColumn();
    $activeEmp    = db()->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();
    $onLeave      = db()->query("SELECT COUNT(*) FROM employees WHERE status = 'on_leave'")->fetchColumn();
    $onProbation  = db()->query("SELECT COUNT(*) FROM employees WHERE status = 'probation'")->fetchColumn();

    // Today attendance
    $clockedIn    = db()->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = '$today' AND sign_in IS NOT NULL AND sign_out IS NULL")->fetchColumn();
    $lateToday    = db()->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = '$today' AND is_late = 1")->fetchColumn();
    // KOM-098: attendance.is_absent defaults to 0 and is never written by any
    // code path (a row only ever gets created on kiosk sign-in) — so "WHERE
    // is_absent=1" was structurally guaranteed to return 0 every single day,
    // permanently. "Absent Today" now means what its own sub-label ("Not
    // clocked in") already said: active/probation employees with no
    // attendance row at all today.
    $absentToday  = db()->query("SELECT COUNT(*) FROM employees e
                                 WHERE e.status IN ('active','probation')
                                 AND NOT EXISTS (SELECT 1 FROM attendance a WHERE a.employee_id = e.id AND a.attendance_date = '$today')")->fetchColumn();

    // Pending approvals
    $pendingLeave   = db()->query("SELECT COUNT(*) FROM leave_applications WHERE status = 'pending'")->fetchColumn();
    $pendingCorrect = db()->query("SELECT COUNT(*) FROM correction_requests WHERE status = 'pending'")->fetchColumn();
    $pendingUpdates = db()->query("SELECT COUNT(*) FROM employee_pending_updates WHERE status = 'pending'")->fetchColumn();
    $pendingOT      = db()->query("SELECT COUNT(*) FROM overtime_records WHERE status = 'pending'")->fetchColumn();
    $pendingRecruitment = db()->query("SELECT COUNT(*) FROM recruitment_applications WHERE status = 'submitted'")->fetchColumn();

    // KOM-099: the "Pending Approvals" card lists 5 rows (Leave, Timesheet
    // Corrections, Overtime, Profile Updates, New Applications) but this
    // total previously summed only 4 of them, omitting $pendingRecruitment —
    // the header badge and the card's own visible rows disagreed with each
    // other (e.g. "4 pending" header over 5 rows that actually summed to 5).
    $totalPending = $pendingLeave + $pendingCorrect + $pendingUpdates + $pendingOT + $pendingRecruitment;

    // Monthly summary
    $monthlyOT = db()->query("SELECT COALESCE(SUM(approved_hours),0) FROM overtime_records WHERE DATE_FORMAT(overtime_date,'%Y-%m') = '$thisMonth' AND status = 'approved'")->fetchColumn();

    // Open vacancies
    $openVacancies = db()->query("SELECT COUNT(*) FROM recruitment_vacancies WHERE status = 'open'")->fetchColumn();

    // Monthly attendance trend (last 7 days)
    // KOM-098: "absent" previously filtered on the dead is_absent column
    // (always 0 — see the Absent Today fix above) and was therefore always
    // 0 for every day of this chart. "present" here means "has an
    // attendance row" (the is_absent=0 filter it used to carry was always
    // true anyway, so dropping it changes nothing); "absent" is now the
    // same active/probation-minus-present logic used for Absent Today.
    $activeForTrend = (int)db()->query("SELECT COUNT(*) FROM employees WHERE status IN ('active','probation')")->fetchColumn();
    $trendData = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $present = db()->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date = ?");
        $present->execute([$d]);
        $presentCount = (int)$present->fetchColumn();
        $trendData[] = [
            'date'    => date('D d', strtotime($d)),
            'present' => $presentCount,
            'absent'  => max(0, $activeForTrend - $presentCount),
        ];
    }

    // Department distribution
    $deptData = db()->query("SELECT d.name, COUNT(e.id) as cnt FROM employees e
                              JOIN departments d ON e.department_id = d.id
                              WHERE e.status = 'active'
                              GROUP BY d.id, d.name ORDER BY cnt DESC LIMIT 7")->fetchAll();

    // Leave trend by type this month
    $leaveByType = db()->query("SELECT lt.name, COUNT(la.id) as cnt
                                FROM leave_applications la
                                JOIN leave_types lt ON la.leave_type_id = lt.id
                                WHERE DATE_FORMAT(la.start_date,'%Y-%m') = '$thisMonth'
                                GROUP BY lt.id, lt.name")->fetchAll();

    // Alerts
    // Contract expiring in 30 days
    $contractExpiring = db()->query("SELECT e.employee_number, CONCAT(e.first_name,' ',e.last_name) as name,
                                     e.contract_end_date, d.name as dept
                                     FROM employees e
                                     LEFT JOIN departments d ON e.department_id = d.id
                                     WHERE e.status IN ('active','probation')
                                     AND e.contract_end_date BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 30 DAY)
                                     ORDER BY e.contract_end_date LIMIT 5")->fetchAll();

    // Probation expiring in 14 days
    $probationExpiring = db()->query("SELECT employee_number, CONCAT(first_name,' ',last_name) as name,
                                      probation_end FROM employees
                                      WHERE status = 'probation'
                                      AND probation_end BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 14 DAY)
                                      ORDER BY probation_end LIMIT 5")->fetchAll();

    // Missing documents (employees without ID document)
    $missingDocs = db()->query("SELECT COUNT(*) FROM employees e
                                WHERE e.status = 'active'
                                AND NOT EXISTS (SELECT 1 FROM employee_documents d WHERE d.employee_id = e.id AND d.category = 'id_document' AND d.is_deleted = 0)")->fetchColumn();

    // Recent activity (audit logs) — this widget is visible to every logged-in
    // user regardless of role (the page itself is only gated by requireLogin()),
    // but audit_logs rows can contain sensitive old/new-value diffs (salary
    // changes, bank detail edits, role changes) and IP addresses. Only query
    // it for roles that actually hold audit/activity-log viewing rights;
    // everyone else simply doesn't get the widget's data at all.
    $recentActivity = (canView('audit.view') || canView('activity_log.view'))
        ? db()->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 8")->fetchAll()
        : [];

    // Recent employees
    $recentEmployees = db()->query("SELECT e.*, d.name as dept FROM employees e
                                    LEFT JOIN departments d ON e.department_id = d.id
                                    ORDER BY e.created_at DESC LIMIT 5")->fetchAll();

    // Monthly attendance %
    $monthPresent = db()->query("SELECT COUNT(*) FROM attendance WHERE DATE_FORMAT(attendance_date,'%Y-%m')='$thisMonth' AND is_absent=0")->fetchColumn();
    $monthTotal   = db()->query("SELECT COUNT(*) FROM attendance WHERE DATE_FORMAT(attendance_date,'%Y-%m')='$thisMonth'")->fetchColumn();
    $attendanceRate = $monthTotal > 0 ? round(($monthPresent / $monthTotal) * 100) : 0;

} catch (PDOException $e) {
    // Database not yet installed — show install prompt
    $dbError = true;
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<?php if (isset($dbError)): ?>
<div class="alert alert-warning" style="margin:0 0 24px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    Database not installed. Please <a href="<?= APP_URL ?>/database/install.php">run the installer</a> first.
</div>
<?php else: ?>

<!-- Flash Message -->
<?= renderFlash() ?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">HR Dashboard</h1>
        <p class="page-subtitle">Overview for <?= date('l, d F Y') ?></p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/modules/employees/add.php" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Employee
        </a>
        <a href="<?= APP_URL ?>/modules/attendance/kiosk.php" class="btn btn-secondary btn-sm" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Open Kiosk
        </a>
        <a href="<?= APP_URL ?>/modules/reports/index.php" class="btn btn-secondary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Reports
        </a>
    </div>
</div>

<!-- KPI CARDS ROW 1 -->
<div class="kpi-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));">
    <div class="kpi-card kpi-primary">
        <div class="kpi-card-label">Total Employees</div>
        <div class="kpi-card-value"><?= number_format($totalEmp) ?></div>
        <div class="kpi-card-trend">All staff records</div>
        <div class="kpi-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
    </div>
    <div class="kpi-card kpi-success">
        <div class="kpi-card-label">Active</div>
        <div class="kpi-card-value"><?= number_format($activeEmp) ?></div>
        <div class="kpi-card-trend">Currently employed</div>
        <div class="kpi-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
    </div>
    <div class="kpi-card kpi-info">
        <div class="kpi-card-label">On Leave</div>
        <div class="kpi-card-value"><?= number_format($onLeave) ?></div>
        <div class="kpi-card-trend">Away from work</div>
        <div class="kpi-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
    </div>
    <div class="kpi-card kpi-warning">
        <div class="kpi-card-label">On Probation</div>
        <div class="kpi-card-value"><?= number_format($onProbation) ?></div>
        <div class="kpi-card-trend">Probationary period</div>
        <div class="kpi-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
    </div>
    <div class="kpi-card kpi-success">
        <div class="kpi-card-label">Clocked In Today</div>
        <div class="kpi-card-value"><?= number_format($clockedIn) ?></div>
        <div class="kpi-card-trend">Currently at work</div>
        <div class="kpi-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
    </div>
    <div class="kpi-card kpi-warning">
        <div class="kpi-card-label">Late Today</div>
        <div class="kpi-card-value"><?= number_format($lateToday) ?></div>
        <div class="kpi-card-trend">Exceeded grace period</div>
    </div>
    <div class="kpi-card kpi-danger">
        <div class="kpi-card-label">Absent Today</div>
        <div class="kpi-card-value"><?= number_format($absentToday) ?></div>
        <div class="kpi-card-trend">Not clocked in</div>
    </div>
    <div class="kpi-card kpi-warning">
        <div class="kpi-card-label">Pending Approvals</div>
        <div class="kpi-card-value"><?= number_format($totalPending) ?></div>
        <div class="kpi-card-trend">Awaiting HR action</div>
    </div>
    <div class="kpi-card kpi-primary">
        <div class="kpi-card-label">Open Vacancies</div>
        <div class="kpi-card-value"><?= number_format($openVacancies) ?></div>
        <div class="kpi-card-trend">Active job postings</div>
    </div>
    <div class="kpi-card kpi-info">
        <div class="kpi-card-label">Monthly OT (hrs)</div>
        <div class="kpi-card-value"><?= number_format($monthlyOT, 1) ?></div>
        <div class="kpi-card-trend"><?= date('F Y') ?></div>
    </div>
</div>

<!-- CHARTS + ALERTS ROW -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;">

    <!-- Attendance Trend (7 days) -->
    <div class="card" style="grid-column:span 2;">
        <div class="card-header">
            <span class="card-title">Attendance Trend — Last 7 Days</span>
            <span style="font-size:0.72rem;color:var(--text-muted);"><?= date('d M') ?> ← 7 days</span>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height:200px;">
                <canvas id="attendanceTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Department Distribution -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Staff by Department</span>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height:200px;">
                <canvas id="deptDistChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- PENDING APPROVALS + RECENT EMPLOYEES -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">

    <!-- Pending Approvals -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Pending Approvals</span>
            <span class="badge badge-warning"><?= $totalPending ?> pending</span>
        </div>
        <div class="card-body p-0">
            <table class="table">
                <tbody>
                    <tr>
                        <td>
                            <div style="font-size:0.8rem;font-weight:600;">Leave Requests</div>
                            <div style="font-size:0.7rem;color:var(--text-muted);">Awaiting HR approval</div>
                        </td>
                        <td style="text-align:right;">
                            <span class="badge badge-warning"><?= $pendingLeave ?></span>
                        </td>
                        <td>
                            <a href="<?= APP_URL ?>/modules/leave/index.php?status=pending" class="btn btn-ghost btn-sm">Review</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="font-size:0.8rem;font-weight:600;">Timesheet Corrections</div>
                            <div style="font-size:0.7rem;color:var(--text-muted);">Employee requests</div>
                        </td>
                        <td style="text-align:right;">
                            <span class="badge badge-warning"><?= $pendingCorrect ?></span>
                        </td>
                        <td>
                            <a href="<?= APP_URL ?>/modules/timesheets/corrections.php" class="btn btn-ghost btn-sm">Review</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="font-size:0.8rem;font-weight:600;">Overtime Approvals</div>
                            <div style="font-size:0.7rem;color:var(--text-muted);">Suggested overtime</div>
                        </td>
                        <td style="text-align:right;">
                            <span class="badge badge-warning"><?= $pendingOT ?></span>
                        </td>
                        <td>
                            <a href="<?= APP_URL ?>/modules/timesheets/overtime.php" class="btn btn-ghost btn-sm">Review</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="font-size:0.8rem;font-weight:600;">Profile Updates</div>
                            <div style="font-size:0.7rem;color:var(--text-muted);">Employee changes</div>
                        </td>
                        <td style="text-align:right;">
                            <span class="badge badge-warning"><?= $pendingUpdates ?></span>
                        </td>
                        <td>
                            <a href="<?= APP_URL ?>/modules/employees/pending_updates.php" class="btn btn-ghost btn-sm">Review</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div style="font-size:0.8rem;font-weight:600;">New Applications</div>
                            <div style="font-size:0.7rem;color:var(--text-muted);">Recruitment inbox</div>
                        </td>
                        <td style="text-align:right;">
                            <span class="badge badge-info"><?= $pendingRecruitment ?></span>
                        </td>
                        <td>
                            <a href="<?= APP_URL ?>/modules/recruitment/applications.php" class="btn btn-ghost btn-sm">Review</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Employees -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Recently Added Employees</span>
            <a href="<?= APP_URL ?>/modules/employees/index.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentEmployees)): ?>
            <div class="empty-state">
                <div class="empty-state-title">No employees yet</div>
                <div class="empty-state-desc">Add your first employee to get started.</div>
                <a href="<?= APP_URL ?>/modules/employees/add.php" class="btn btn-primary btn-sm">Add Employee</a>
            </div>
            <?php else: ?>
            <table class="table">
                <tbody>
                    <?php foreach ($recentEmployees as $emp): ?>
                    <tr>
                        <td>
                            <div class="emp-row-info">
                                <div class="emp-avatar">
                                    <?php if (!empty($emp['photo'])): ?>
                                        <img src="<?= APP_URL ?>/<?= e($emp['photo']) ?>" alt="">
                                    <?php else: ?>
                                        <?= strtoupper(substr($emp['first_name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="emp-name"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                                    <div class="emp-num"><?= e($emp['employee_number']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-secondary);font-size:0.72rem;"><?= e($emp['dept'] ?? '—') ?></td>
                        <td><?= employeeStatusBadge($emp['status']) ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $emp['id'] ?>" class="btn btn-ghost btn-sm btn-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ALERTS + ACTIVITY -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">

    <!-- HR Alerts -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">HR Alerts</span>
        </div>
        <div class="card-body" style="padding:12px 16px;">

            <?php if (!empty($contractExpiring)): ?>
            <div style="margin-bottom:12px;">
                <div style="font-size:0.72rem;font-weight:700;color:var(--danger);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;vertical-align:middle;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Contract Expiry (30 days)
                </div>
                <?php foreach ($contractExpiring as $c): ?>
                <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--border-light);font-size:0.75rem;">
                    <span><?= e($c['name']) ?> <span style="color:var(--text-muted);">(<?= e($c['employee_number']) ?>)</span></span>
                    <span style="color:var(--danger);font-weight:600;"><?= formatDate($c['contract_end_date']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($probationExpiring)): ?>
            <div style="margin-bottom:12px;">
                <div style="font-size:0.72rem;font-weight:700;color:var(--warning);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">
                    Probation Expiry (14 days)
                </div>
                <?php foreach ($probationExpiring as $p): ?>
                <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--border-light);font-size:0.75rem;">
                    <span><?= e($p['name']) ?> <span style="color:var(--text-muted);">(<?= e($p['employee_number']) ?>)</span></span>
                    <span style="color:var(--warning);font-weight:600;"><?= formatDate($p['probation_end']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($missingDocs > 0): ?>
            <div class="alert alert-warning" style="margin:0;font-size:0.75rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                <strong><?= $missingDocs ?> employees</strong> are missing ID documents.
                <a href="<?= APP_URL ?>/modules/documents/missing.php">View</a>
            </div>
            <?php endif; ?>

            <?php if (empty($contractExpiring) && empty($probationExpiring) && !$missingDocs): ?>
            <div class="empty-state" style="padding:24px;">
                <div class="empty-state-title">No active alerts</div>
                <div class="empty-state-desc">All records are in order.</div>
            </div>
            <?php endif; ?>

            <!-- Attendance Rate -->
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                    <span style="font-size:0.75rem;font-weight:600;">Monthly Attendance Rate</span>
                    <span style="font-size:0.75rem;font-weight:700;color:<?= $attendanceRate >= 90 ? 'var(--success)' : ($attendanceRate >= 75 ? 'var(--warning)' : 'var(--danger)') ?>;"><?= $attendanceRate ?>%</span>
                </div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill <?= $attendanceRate >= 90 ? 'success' : ($attendanceRate >= 75 ? 'warning' : 'danger') ?>" style="width:<?= $attendanceRate ?>%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Recent Activity</span>
            <a href="<?= APP_URL ?>/modules/audit/index.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentActivity)): ?>
            <div class="empty-state" style="padding:16px;">
                <div class="empty-state-desc">No activity recorded yet.</div>
            </div>
            <?php else: ?>
            <div class="timeline">
                <?php foreach ($recentActivity as $log): ?>
                <div class="timeline-item">
                    <div class="timeline-dot" style="background:<?= $log['action'] === 'login' ? 'var(--success)' : ($log['action'] === 'delete' ? 'var(--danger)' : 'var(--primary)') ?>;"></div>
                    <div class="timeline-meta"><?= e($log['user_name']) ?> · <?= formatDateTime($log['created_at']) ?></div>
                    <div class="timeline-title"><?= e(ucfirst(str_replace('_',' ',$log['action']))) ?> in <?= e(ucfirst($log['module'])) ?></div>
                    <?php if ($log['reason']): ?>
                    <div class="timeline-desc"><?= e(mb_strimwidth($log['reason'], 0, 60, '…')) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; // end dbError check ?>

<?php
$_trendJson = json_encode($trendData ?? []);
$_deptJson  = json_encode($deptData  ?? []);
$extraScripts = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attendance Trend Chart
    const trendCtx = document.getElementById('attendanceTrendChart');
    if (trendCtx) {
        const trendData = $_trendJson;
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: trendData.map(d => d.date),
                datasets: [
                    {
                        label: 'Present',
                        data: trendData.map(d => d.present),
                        backgroundColor: '#22C55E',
                        borderRadius: 3,
                        borderSkipped: false,
                    },
                    {
                        label: 'Absent',
                        data: trendData.map(d => d.absent),
                        backgroundColor: '#EF4444',
                        borderRadius: 3,
                        borderSkipped: false,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 10, family: 'Inter' }, padding: 12, boxWidth: 10 } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10, family: 'Inter' } } },
                    y: { grid: { color: '#F1F5F9' }, ticks: { font: { size: 10, family: 'Inter' } }, beginAtZero: true }
                }
            }
        });
    }

    // Department Distribution Doughnut
    const deptCtx = document.getElementById('deptDistChart');
    if (deptCtx) {
        const deptData = $_deptJson;
        const colors = ['#1D4ED8','#22C55E','#F59E0B','#EF4444','#3B82F6','#8B5CF6','#EC4899'];
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: deptData.map(d => d.name),
                datasets: [{
                    data: deptData.map(d => d.cnt),
                    backgroundColor: colors.slice(0, deptData.length),
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { size: 10, family: 'Inter' }, padding: 8, boxWidth: 10 }
                    }
                },
                cutout: '70%'
            }
        });
    }
});
</script>
JS;

include __DIR__ . '/includes/footer.php';
?>
