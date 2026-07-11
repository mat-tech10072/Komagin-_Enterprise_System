<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';

cpRequireLogin();

$con  = cpCurrentConsultant();
$type = $_SESSION['cp_type'] ?? '';
$name = $_SESSION['cp_name'] ?? '';

$stats = [];

if ($type === 'time_based') {
    // Total days worked (distinct days with clock_in)
    $s = db()->prepare("SELECT
        COUNT(*) as total_days,
        SUM(CASE WHEN work_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as days_30,
        SUM(total_hours) as total_hours
        FROM consultant_attendance WHERE consultant_id = ? AND clock_out IS NOT NULL");
    $s->execute([$con['id']]);
    $agg = $s->fetch(PDO::FETCH_ASSOC);

    // Today's record
    $today = db()->prepare("SELECT * FROM consultant_attendance WHERE consultant_id = ? AND work_date = CURDATE() LIMIT 1");
    $today->execute([$con['id']]);
    $todayRow = $today->fetch(PDO::FETCH_ASSOC);

    $stats = [
        ['Days Worked (Total)', (int)($agg['total_days'] ?? 0), ''],
        ['Days This Month',     (int)($agg['days_30'] ?? 0),   'last 30 days'],
        ['Total Hours',         number_format((float)($agg['total_hours'] ?? 0), 1), 'hrs logged'],
    ];

} elseif ($type === 'output_based') {
    $s = db()->prepare("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as done,
        SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) as in_prog,
        AVG(completion_pct) as avg_pct
        FROM consultant_scopes WHERE consultant_id = ?");
    $s->execute([$con['id']]);
    $agg = $s->fetch(PDO::FETCH_ASSOC);

    $stats = [
        ['Total Scope Items',   (int)($agg['total'] ?? 0),   ''],
        ['Completed',           (int)($agg['done'] ?? 0),    'items done'],
        ['In Progress',         (int)($agg['in_prog'] ?? 0), 'items active'],
        ['Overall Progress',    round((float)($agg['avg_pct'] ?? 0)) . '%', 'avg completion'],
    ];
}

$pageTitle = 'Dashboard';
cpLayoutStart($pageTitle, 'dashboard');
?>

<?php
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<div style="margin-bottom:24px;">
    <h2 style="font-size:1.3rem;font-weight:700;color:var(--cp-text);margin-bottom:4px;">
        Welcome back, <?= htmlspecialchars(explode(' ', $name)[0]) ?>
    </h2>
    <p style="font-size:0.82rem;color:var(--cp-text-muted);">
        <?= date('l, d F Y') ?> &mdash;
        <?= $type === 'time_based' ? 'Time-Based Consultant' : 'Output-Based Consultant' ?>
    </p>
</div>

<!-- KPI Cards -->
<div class="cp-kpi-grid">
    <?php foreach ($stats as [$label, $value, $sub]): ?>
    <div class="cp-kpi">
        <div class="cp-kpi-label"><?= htmlspecialchars($label) ?></div>
        <div class="cp-kpi-value"><?= htmlspecialchars($value) ?></div>
        <?php if ($sub): ?><div class="cp-kpi-sub"><?= htmlspecialchars($sub) ?></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- Quick Action Card -->
<?php if ($type === 'time_based'): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Today's Attendance</span></div>
    <div class="card-body">
        <?php if (!empty($todayRow)): ?>
        <div style="display:flex;gap:24px;flex-wrap:wrap;margin-bottom:16px;">
            <div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:3px;">Clock In</div>
                <div style="font-weight:700;color:var(--cp-text);"><?= $todayRow['clock_in'] ? date('H:i', strtotime($todayRow['clock_in'])) : '—' ?></div>
            </div>
            <?php if ($todayRow['break_start']): ?>
            <div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:3px;">Break Out</div>
                <div style="font-weight:700;color:var(--cp-text);"><?= date('H:i', strtotime($todayRow['break_start'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($todayRow['break_end']): ?>
            <div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:3px;">Break In</div>
                <div style="font-weight:700;color:var(--cp-text);"><?= date('H:i', strtotime($todayRow['break_end'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($todayRow['clock_out']): ?>
            <div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:3px;">Clock Out</div>
                <div style="font-weight:700;color:var(--cp-text);"><?= date('H:i', strtotime($todayRow['clock_out'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($todayRow['clock_out']): ?>
            <span class="badge badge-success">Shift Complete</span>
        <?php elseif ($todayRow['break_start'] && !$todayRow['break_end']): ?>
            <span class="badge badge-warning">On Break</span>
        <?php else: ?>
            <span class="badge badge-primary">Currently Working</span>
        <?php endif; ?>
        <?php else: ?>
        <p style="font-size:0.82rem;color:var(--cp-text-muted);margin-bottom:12px;">You have not clocked in today.</p>
        <?php endif; ?>
        <div style="margin-top:14px;">
            <a href="<?= CP_URL ?>/kiosk.php" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Go to Kiosk
            </a>
        </div>
    </div>
</div>

<?php elseif ($type === 'output_based'): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">My Scope of Work</span></div>
    <div class="card-body">
        <?php
        $items = db()->prepare("SELECT * FROM consultant_scopes WHERE consultant_id = ? ORDER BY sort_order, id LIMIT 5");
        $items->execute([$con['id']]);
        $items = $items->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if ($items): ?>
        <?php foreach ($items as $item): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--cp-border);">
            <?php if ($item['status'] === 'completed'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?php elseif ($item['status'] === 'in_progress'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0F766E" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
            <?php endif; ?>
            <div style="flex:1;min-width:0;">
                <div style="font-size:0.82rem;font-weight:600;color:var(--cp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($item['title']) ?></div>
            </div>
            <div style="font-size:0.72rem;color:var(--cp-text-muted);"><?= $item['completion_pct'] ?>%</div>
        </div>
        <?php endforeach; ?>
        <div style="margin-top:14px;">
            <a href="<?= CP_URL ?>/scope.php" class="btn btn-primary btn-sm">View All Scope Items</a>
        </div>
        <?php else: ?>
        <p style="font-size:0.82rem;color:var(--cp-text-muted);">No scope items have been assigned yet. Please check back later or contact your HR manager.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Contract Info Card -->
<div class="card">
    <div class="card-header"><span class="card-title">Contract Information</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;">
            <div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:3px;">Consultant Number</div>
                <div style="font-weight:600;font-family:monospace;"><?= htmlspecialchars($con['consultant_number'] ?? '') ?></div>
            </div>
            <div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:3px;">Type</div>
                <div><?= $type === 'time_based' ? '<span class="badge badge-primary">Time-Based</span>' : '<span class="badge badge-info">Output-Based</span>' ?></div>
            </div>
            <?php if ($con['position_title']): ?>
            <div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:3px;">Role</div>
                <div style="font-size:0.84rem;"><?= htmlspecialchars($con['position_title']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($con['start_date']): ?>
            <div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:3px;">Contract Start</div>
                <div style="font-size:0.84rem;"><?= date('d M Y', strtotime($con['start_date'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($con['end_date']): ?>
            <div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:3px;">Contract End</div>
                <div style="font-size:0.84rem;"><?= date('d M Y', strtotime($con['end_date'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php cpLayoutEnd(); ?>
