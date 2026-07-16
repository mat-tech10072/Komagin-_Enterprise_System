<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('kiosk.manage', 'view');

$pageTitle  = 'Kiosk Management';
$activeMenu = 'attendance';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'open_kiosk') {
        $sessionId    = (int)($_POST['session_id'] ?? 0);
        $schedOpen    = $_POST['scheduled_open']  ?: null;
        $schedClose   = $_POST['scheduled_close'] ?: null;
        $notes        = trim($_POST['notes'] ?? '');

        if ($sessionId) {
            db()->prepare("UPDATE kiosk_sessions SET status='open', opened_by=?, opened_at=NOW(),
                closed_by=NULL, closed_at=NULL, scheduled_open=?, scheduled_close=?, notes=?, updated_at=NOW()
                WHERE id=?")
                ->execute([$_SESSION['user_id'], $schedOpen, $schedClose, $notes, $sessionId]);
            db()->prepare("INSERT INTO kiosk_audit (kiosk_session_id, action, result, ip_address)
                VALUES (?,?,?,?)")
                ->execute([$sessionId, 'kiosk_opened', 'success', $_SERVER['REMOTE_ADDR'] ?? null]);
            auditLog('kiosk', 'open', $sessionId, null, null, "Kiosk opened by {$_SESSION['user_name']}");
            setFlash('success', 'Kiosk opened successfully.');
        }

    } elseif ($action === 'close_kiosk') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        if ($sessionId) {
            db()->prepare("UPDATE kiosk_sessions SET status='closed', closed_by=?, closed_at=NOW(), updated_at=NOW() WHERE id=?")
                ->execute([$_SESSION['user_id'], $sessionId]);
            db()->prepare("INSERT INTO kiosk_audit (kiosk_session_id, action, result, ip_address)
                VALUES (?,?,?,?)")
                ->execute([$sessionId, 'kiosk_closed', 'success', $_SERVER['REMOTE_ADDR'] ?? null]);
            auditLog('kiosk', 'close', $sessionId, null, null, "Kiosk closed by {$_SESSION['user_name']}");
            setFlash('success', 'Kiosk closed.');
        }

    } elseif ($action === 'add_location') {
        $loc = trim($_POST['location_name'] ?? '');
        if ($loc) {
            $token = bin2hex(random_bytes(16));
            db()->prepare("INSERT INTO kiosk_sessions (kiosk_token, location_name, status) VALUES (?, ?, 'closed')")
                ->execute([$token, $loc]);
            auditLog('kiosk', 'add_location', (int)db()->lastInsertId(), null, $loc);
            setFlash('success', "Location \"$loc\" added.");
        }

    } elseif ($action === 'delete_location') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        // Only delete if closed and no audit records
        $check = db()->prepare("SELECT COUNT(*) FROM kiosk_audit WHERE kiosk_session_id=?");
        $check->execute([$sessionId]);
        if ($check->fetchColumn() == 0) {
            db()->prepare("DELETE FROM kiosk_sessions WHERE id=? AND status='closed'")->execute([$sessionId]);
            setFlash('success', 'Location removed.');
        } else {
            setFlash('error', 'Cannot delete a location with audit history. Close it instead.');
        }
    }

    header('Location: ' . APP_URL . '/modules/attendance/kiosk_manage.php'); exit;
}

// Load all kiosk sessions / locations
$sessions = db()->query("SELECT ks.*,
    u1.username as opened_by_name,
    u2.username as closed_by_name
    FROM kiosk_sessions ks
    LEFT JOIN users u1 ON ks.opened_by=u1.id
    LEFT JOIN users u2 ON ks.closed_by=u2.id
    ORDER BY ks.id")->fetchAll(PDO::FETCH_ASSOC);

// Today's kiosk activity
$today = date('Y-m-d');
$todayActivity = db()->prepare("SELECT ka.*, e.first_name, e.last_name, e.employee_number
    FROM kiosk_audit ka
    LEFT JOIN employees e ON ka.employee_id=e.id
    WHERE DATE(ka.recorded_at)=?
    ORDER BY ka.recorded_at DESC LIMIT 50");
$todayActivity->execute([$today]);
$activity = $todayActivity->fetchAll(PDO::FETCH_ASSOC);

// Today attendance summary
$todaySummary = db()->prepare("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN sign_in IS NOT NULL THEN 1 ELSE 0 END) as signed_in,
    SUM(CASE WHEN sign_out IS NOT NULL THEN 1 ELSE 0 END) as signed_out,
    SUM(CASE WHEN is_late=1 THEN 1 ELSE 0 END) as late,
    SUM(CASE WHEN break_out IS NOT NULL AND break_in IS NULL THEN 1 ELSE 0 END) as on_break
    FROM attendance WHERE attendance_date=?");
$todaySummary->execute([$today]);
$summary = $todaySummary->fetch(PDO::FETCH_ASSOC);

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/attendance/index.php">Attendance</a></li>
                <li class="breadcrumb-item active">Kiosk Management</li>
            </ol>
        </nav>
        <h1 class="page-title">Kiosk Management</h1>
        <p class="page-subtitle">Control attendance kiosk availability and monitor activity</p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/modules/attendance/kiosk.php" target="_blank" class="btn btn-secondary btn-sm">
            Open Kiosk Screen
        </a>
        <button class="btn btn-primary btn-sm" data-modal-open="addLocationModal">Add Location</button>
    </div>
</div>

<!-- Today Summary -->
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:24px;">
    <?php $kpis = [
        ['Total Today', $summary['total']     ?? 0],
        ['Signed In',   $summary['signed_in']  ?? 0],
        ['Signed Out',  $summary['signed_out'] ?? 0],
        ['Late',        $summary['late']        ?? 0],
        ['On Break',    $summary['on_break']    ?? 0],
    ]; ?>
    <?php foreach ($kpis as [$label, $val]): ?>
    <div class="kpi-card">
        <div class="kpi-card-label"><?= $label ?></div>
        <div class="kpi-card-value" style="font-size:1.6rem;"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Kiosk Locations -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <span class="card-title">Kiosk Locations</span>
        <span style="font-size:0.72rem;color:var(--text-muted);"><?= count($sessions) ?> location(s)</span>
    </div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Kiosk Link</th>
                    <th>Status</th>
                    <th>Opened By</th>
                    <th>Opened At</th>
                    <th>Scheduled</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sessions as $s): ?>
            <?php $kioskUrl = APP_URL . '/modules/attendance/kiosk.php?t=' . $s['kiosk_token']; ?>
            <tr>
                <td style="font-weight:600;font-size:0.82rem;"><?= e($s['location_name']) ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <code style="font-size:0.68rem;color:var(--text-muted);background:var(--bg);padding:3px 7px;border-radius:4px;border:1px solid var(--border);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;" title="<?= e($kioskUrl) ?>">
                            ...kiosk.php?t=<?= substr($s['kiosk_token'], 0, 8) ?>…
                        </code>
                        <button type="button"
                            onclick="copyKioskLink('<?= e($kioskUrl) ?>', this)"
                            title="Copy link"
                            style="background:none;border:1px solid var(--border);border-radius:4px;padding:3px 7px;cursor:pointer;color:var(--text-muted);font-size:0.7rem;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            Copy
                        </button>
                        <a href="<?= e($kioskUrl) ?>" target="_blank"
                            title="Open kiosk in new tab"
                            style="border:1px solid var(--border);border-radius:4px;padding:3px 7px;color:var(--text-muted);font-size:0.7rem;display:inline-flex;align-items:center;gap:4px;text-decoration:none;white-space:nowrap;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            Open
                        </a>
                    </div>
                </td>
                <td>
                    <?php if ($s['status'] === 'open'): ?>
                        <span class="badge badge-success">Open</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Closed</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.78rem;"><?= e($s['opened_by_name'] ?? '—') ?></td>
                <td style="font-size:0.78rem;"><?= $s['opened_at'] ? formatDateTime($s['opened_at']) : '—' ?></td>
                <td style="font-size:0.75rem;">
                    <?php if ($s['scheduled_open'] || $s['scheduled_close']): ?>
                        <?= $s['scheduled_open'] ? date('h:i A', strtotime($s['scheduled_open'])) : '—' ?>
                        → <?= $s['scheduled_close'] ? date('h:i A', strtotime($s['scheduled_close'])) : '—' ?>
                    <?php else: ?>
                        <span style="color:var(--text-muted);">Manual</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center;">
                    <?php if ($s['status'] === 'closed'): ?>
                        <button class="btn btn-success btn-sm"
                            onclick="openKiosk(<?= $s['id'] ?>, '<?= e($s['location_name']) ?>')">
                            Open
                        </button>
                        <form method="POST" style="display:inline;"
                            onsubmit="return confirm('Delete the &quot;<?= e($s['location_name']) ?>&quot; kiosk location? This cannot be undone.')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="delete_location">
                            <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete location"
                                style="padding:5px 8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="close_kiosk">
                            <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('Close the <?= e($s['location_name']) ?> kiosk?')">
                                Close
                            </button>
                        </form>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($sessions)): ?>
            <tr><td colspan="7" class="empty-state">No locations configured. Add a location to get started.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Today's Activity -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Today's Kiosk Activity</span>
        <span style="font-size:0.72rem;color:var(--text-muted);"><?= date('d M Y') ?> · Last 50 events</span>
    </div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Employee</th>
                    <th>Action</th>
                    <th>Result</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($activity as $a): ?>
            <tr>
                <td style="font-size:0.75rem;white-space:nowrap;font-family:monospace;"><?= date('H:i:s', strtotime($a['recorded_at'])) ?></td>
                <td style="font-size:0.78rem;">
                    <?php if ($a['first_name']): ?>
                        <div style="font-weight:600;"><?= e($a['first_name'].' '.$a['last_name']) ?></div>
                        <div class="emp-num"><?= e($a['employee_number']) ?></div>
                    <?php elseif (in_array($a['action'],['kiosk_opened','kiosk_closed'])): ?>
                        <span style="font-size:0.72rem;color:var(--text-muted);">Admin action</span>
                    <?php else: ?>
                        <span class="badge badge-danger" style="font-size:0.65rem;">Failed auth</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $aColors = [
                        'sign_in'      => ['success','Sign In'],
                        'break_out'    => ['warning','Break Out'],
                        'break_in'     => ['info','Break In'],
                        'sign_out'     => ['secondary','Sign Out'],
                        'failed_auth'  => ['danger','Failed Auth'],
                        'kiosk_opened' => ['success','Kiosk Opened'],
                        'kiosk_closed' => ['danger','Kiosk Closed'],
                    ]; $aInfo = $aColors[$a['action']] ?? ['secondary', ucfirst($a['action'])]; ?>
                    <span class="badge badge-<?= $aInfo[0] ?>"><?= $aInfo[1] ?></span>
                </td>
                <td>
                    <span class="badge badge-<?= $a['result']==='success'?'success':'danger' ?>">
                        <?= ucfirst($a['result']) ?>
                    </span>
                    <?php if ($a['error_message']): ?>
                        <div style="font-size:0.65rem;color:var(--danger);margin-top:2px;"><?= e($a['error_message']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.72rem;font-family:monospace;color:var(--text-muted);"><?= e($a['ip_address'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($activity)): ?>
            <tr><td colspan="5" class="empty-state">No kiosk activity recorded today.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Open Kiosk Modal -->
<div class="modal-overlay" id="openKioskModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title" id="openKioskTitle">Open Kiosk</h5>
            <button class="modal-close" data-modal-close="openKioskModal">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="open_kiosk">
            <input type="hidden" name="session_id" id="openSessionId" value="">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Scheduled Open Time <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                        <input type="time" class="form-control" name="scheduled_open">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Scheduled Close Time <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                        <input type="time" class="form-control" name="scheduled_close">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <input type="text" class="form-control" name="notes" placeholder="e.g. Normal working day">
                </div>
                <div class="alert alert-info" style="font-size:0.78rem;">
                    Opening the kiosk will allow employees to sign in and out. Make sure the kiosk screen is accessible at <strong><?= APP_URL ?>/modules/attendance/kiosk.php</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="openKioskModal">Cancel</button>
                <button type="submit" class="btn btn-success">Open Kiosk</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Location Modal -->
<div class="modal-overlay" id="addLocationModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Add Kiosk Location</h5>
            <button class="modal-close" data-modal-close="addLocationModal">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="add_location">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Location Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="location_name" required placeholder="e.g. Head Office, Workshop, Branch 2">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="addLocationModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Location</button>
            </div>
        </form>
    </div>
</div>

<script>
function openKiosk(sessionId, locationName) {
    document.getElementById('openSessionId').value = sessionId;
    document.getElementById('openKioskTitle').textContent = 'Open Kiosk — ' + locationName;
    document.getElementById('openKioskModal').classList.add('active');
}

function copyKioskLink(url, btn) {
    navigator.clipboard.writeText(url).then(function() {
        const orig = btn.innerHTML;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Copied!';
        btn.style.color = 'var(--success)';
        btn.style.borderColor = 'var(--success)';
        setTimeout(function() {
            btn.innerHTML = orig;
            btn.style.color = '';
            btn.style.borderColor = '';
        }, 2000);
    }).catch(function() {
        // Fallback for older browsers
        const ta = document.createElement('textarea');
        ta.value = url;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        btn.textContent = 'Copied!';
        setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
    });
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
