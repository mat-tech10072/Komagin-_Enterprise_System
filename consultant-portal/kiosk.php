<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';

cpRequireLogin();
cpRequireType('time_based');

$con = cpCurrentConsultant();
$cid = $con['id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $now    = date('Y-m-d H:i:s');
    $today  = date('Y-m-d');

    $row = db()->prepare("SELECT * FROM consultant_attendance WHERE consultant_id = ? AND work_date = ? LIMIT 1");
    $row->execute([$cid, $today]);
    $row = $row->fetch(PDO::FETCH_ASSOC);

    if ($action === 'clock_in' && !$row) {
        db()->prepare("INSERT INTO consultant_attendance (consultant_id, work_date, clock_in) VALUES (?, ?, ?)")->execute([$cid, $today, $now]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Clocked in at ' . date('H:i', strtotime($now)) . '.'];

    } elseif ($action === 'break_out' && $row && !$row['break_start'] && !$row['clock_out']) {
        db()->prepare("UPDATE consultant_attendance SET break_start = ? WHERE id = ?")->execute([$now, $row['id']]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Break started at ' . date('H:i', strtotime($now)) . '.'];

    } elseif ($action === 'break_in' && $row && $row['break_start'] && !$row['break_end'] && !$row['clock_out']) {
        db()->prepare("UPDATE consultant_attendance SET break_end = ? WHERE id = ?")->execute([$now, $row['id']]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Back from break at ' . date('H:i', strtotime($now)) . '.'];

    } elseif ($action === 'clock_out' && $row && $row['clock_in'] && !$row['clock_out']) {
        // Compute hours worked (excluding break if applicable)
        $breakSecs = 0;
        if ($row['break_start'] && $row['break_end']) {
            $breakSecs = strtotime($row['break_end']) - strtotime($row['break_start']);
        }
        $totalSecs  = strtotime($now) - strtotime($row['clock_in']) - $breakSecs;
        $hoursWorked = round($totalSecs / 3600, 2);
        db()->prepare("UPDATE consultant_attendance SET clock_out = ?, total_hours = ? WHERE id = ?")->execute([$now, $hoursWorked, $row['id']]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Clocked out at ' . date('H:i', strtotime($now)) . '. Hours worked: ' . $hoursWorked . 'h.'];
    }

    header('Location: ' . CP_URL . '/kiosk.php');
    exit;
}

// Fetch today's record
$today = date('Y-m-d');
$stmt  = db()->prepare("SELECT * FROM consultant_attendance WHERE consultant_id = ? AND work_date = ? LIMIT 1");
$stmt->execute([$cid, $today]);
$todayRow = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine state: 0=not started, 1=working, 2=on break, 3=returned from break, 4=done
$state = 0;
if ($todayRow) {
    if ($todayRow['clock_out'])                                           $state = 4;
    elseif ($todayRow['break_end'])                                       $state = 3;
    elseif ($todayRow['break_start'] && !$todayRow['break_end'])          $state = 2;
    elseif ($todayRow['clock_in'])                                        $state = 1;
}

// Recent attendance (last 14 days, excluding today)
$recent = db()->prepare("SELECT * FROM consultant_attendance WHERE consultant_id = ? AND work_date != CURDATE() ORDER BY work_date DESC LIMIT 14");
$recent->execute([$cid]);
$recentRows = $recent->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Clock In / Out';
cpLayoutStart($pageTitle, 'kiosk');
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    <!-- Kiosk Panel -->
    <div class="card">
        <div class="card-header"><span class="card-title">Today — <?= date('l, d F Y') ?></span></div>
        <div class="card-body">
            <div class="cp-kiosk-state">
                <?php if ($state === 0): ?>
                <div class="cp-kiosk-icon idle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="cp-kiosk-status">Not Started</div>
                <div class="cp-kiosk-sub">You have not clocked in today.</div>
                <div class="cp-kiosk-actions">
                    <form method="POST">
                        <input type="hidden" name="action" value="clock_in">
                        <button type="submit" class="cp-kiosk-btn clock-in">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                            Clock In
                        </button>
                    </form>
                </div>

                <?php elseif ($state === 1): ?>
                <div class="cp-kiosk-icon working">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="cp-kiosk-status" style="color:#16A34A;">Currently Working</div>
                <div class="cp-kiosk-sub">Clocked in at <?= date('H:i', strtotime($todayRow['clock_in'])) ?></div>
                <div class="cp-kiosk-actions">
                    <form method="POST">
                        <input type="hidden" name="action" value="break_out">
                        <button type="submit" class="cp-kiosk-btn break-out">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                            Take Break
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="clock_out">
                        <button type="submit" class="cp-kiosk-btn clock-out" onclick="return confirm('Clock out now?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Clock Out
                        </button>
                    </form>
                </div>

                <?php elseif ($state === 2): ?>
                <div class="cp-kiosk-icon on-break">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="1.5"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                </div>
                <div class="cp-kiosk-status" style="color:#D97706;">On Break</div>
                <div class="cp-kiosk-sub">Break started at <?= date('H:i', strtotime($todayRow['break_start'])) ?></div>
                <div class="cp-kiosk-actions">
                    <form method="POST">
                        <input type="hidden" name="action" value="break_in">
                        <button type="submit" class="cp-kiosk-btn break-in">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            End Break
                        </button>
                    </form>
                </div>

                <?php elseif ($state === 3): ?>
                <div class="cp-kiosk-icon working">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="cp-kiosk-status" style="color:#16A34A;">Back from Break</div>
                <div class="cp-kiosk-sub">Back at <?= date('H:i', strtotime($todayRow['break_end'])) ?></div>
                <div class="cp-kiosk-actions">
                    <form method="POST">
                        <input type="hidden" name="action" value="clock_out">
                        <button type="submit" class="cp-kiosk-btn clock-out" onclick="return confirm('Clock out now?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Clock Out
                        </button>
                    </form>
                </div>

                <?php elseif ($state === 4): ?>
                <div class="cp-kiosk-icon done">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#0284C7" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="cp-kiosk-status" style="color:#0284C7;">Shift Complete</div>
                <div class="cp-kiosk-sub">Clocked out at <?= date('H:i', strtotime($todayRow['clock_out'])) ?>
                    &mdash; <?= $todayRow['total_hours'] ? $todayRow['total_hours'] . 'h worked' : '' ?></div>
                <?php endif; ?>
            </div>

            <!-- Today's Timeline -->
            <?php if ($todayRow): ?>
            <div style="margin-top:24px;border-top:1px solid var(--cp-border);padding-top:20px;">
                <div style="font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:14px;">Today's Timeline</div>
                <ul class="cp-timeline">
                    <?php if ($todayRow['clock_in']): ?>
                    <li><span class="tl-time"><?= date('H:i', strtotime($todayRow['clock_in'])) ?></span> <span class="tl-label">— Clocked In</span></li>
                    <?php endif; ?>
                    <?php if ($todayRow['break_start']): ?>
                    <li><span class="tl-time"><?= date('H:i', strtotime($todayRow['break_start'])) ?></span> <span class="tl-label">— Break Started</span></li>
                    <?php endif; ?>
                    <?php if ($todayRow['break_end']): ?>
                    <li><span class="tl-time"><?= date('H:i', strtotime($todayRow['break_end'])) ?></span> <span class="tl-label">— Returned from Break</span></li>
                    <?php endif; ?>
                    <?php if ($todayRow['clock_out']): ?>
                    <li><span class="tl-time"><?= date('H:i', strtotime($todayRow['clock_out'])) ?></span> <span class="tl-label">— Clocked Out (<?= $todayRow['total_hours'] ?>h)</span></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="card">
        <div class="card-header"><span class="card-title">Recent Attendance</span></div>
        <?php if ($recentRows): ?>
        <div class="table-wrap">
            <table class="cp-table">
                <thead>
                    <tr><th>Date</th><th>In</th><th>Out</th><th>Hrs</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentRows as $r): ?>
                    <tr>
                        <td style="white-space:nowrap;"><?= date('d M', strtotime($r['clock_in'])) ?></td>
                        <td><?= date('H:i', strtotime($r['clock_in'])) ?></td>
                        <td><?= $r['clock_out'] ? date('H:i', strtotime($r['clock_out'])) : '<span style="color:#64748B">—</span>' ?></td>
                        <td><?= $r['hours_worked'] ? $r['hours_worked'] : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card-body" style="color:var(--cp-text-muted);font-size:0.82rem;">No previous attendance records.</div>
        <?php endif; ?>
    </div>
</div>

<?php cpLayoutEnd(); ?>
