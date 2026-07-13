<?php
/**
 * Phase 5, Stage 5.8 (KOM-090, KOM-058): supervisor/HR-entered digital
 * attendance capture for temporary employees. Reuses timesheet.php's
 * project/site/week selection so the two pages present the same
 * employee list for the same week; this page persists hours worked
 * per employee per day instead of only rendering a blank printable
 * grid. Kiosk self-service intentionally stays out of scope — the
 * kiosk has no identity-verification path for temp employees, and
 * building one is not authorized this phase.
 */
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('temp_employees.edit', 'edit');

$activeMenu = 'temp_employees';

$projectId = (int)($_GET['project'] ?? $_POST['project'] ?? 0);
$siteId    = (int)($_GET['site']    ?? $_POST['site']    ?? 0);
$fromRaw   = trim($_GET['from']     ?? $_POST['from']    ?? '');

if ($fromRaw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromRaw)) {
    $fromTs = strtotime($fromRaw);
} else {
    $fromTs = strtotime('monday this week');
}
$dow    = (int)date('N', $fromTs);
$fromTs -= ($dow - 1) * 86400;
$from   = date('Y-m-d', $fromTs);

$days = [];
for ($i = 0; $i < 7; $i++) {
    $ts     = $fromTs + $i * 86400;
    $days[] = ['ts' => $ts, 'short' => date('D', $ts), 'date' => date('d M', $ts), 'ymd' => date('Y-m-d', $ts)];
}

// ── Save handler ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
        header('Location: ' . APP_URL . '/modules/temp_employees/attendance_entry.php?project=' . $projectId . '&site=' . $siteId . '&from=' . $from);
        exit;
    }

    $hours = $_POST['hours'] ?? [];
    $saved = 0;
    foreach ($hours as $empId => $byDate) {
        $empId = (int)$empId;
        if (!$empId || !is_array($byDate)) continue;
        foreach ($byDate as $ymd => $val) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) continue;
            $val = trim((string)$val);
            if ($val === '') continue;
            if (!is_numeric($val)) continue;
            $val = round((float)$val, 1);
            if ($val < 0) $val = 0.0;
            if ($val > 24) $val = 24.0;

            db()->prepare("INSERT INTO temp_attendance (employee_id, attendance_date, hours_worked, entered_by)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE hours_worked = VALUES(hours_worked), entered_by = VALUES(entered_by)")
                ->execute([$empId, $ymd, $val, $_SESSION['user_id']]);
            $saved++;
        }
    }

    auditLog('temp_employees', 'attendance_entry', $projectId,
        null, json_encode(['week' => $from, 'cells_saved' => $saved]),
        "Entered/updated attendance for week of $from ($saved cell(s))");

    setFlash('success', "Attendance saved for the week of " . date('d M Y', $fromTs) . " ($saved entr" . ($saved === 1 ? 'y' : 'ies') . ").");
    header('Location: ' . APP_URL . '/modules/temp_employees/attendance_entry.php?project=' . $projectId . '&site=' . $siteId . '&from=' . $from);
    exit;
}

// ── Projects / sites ─────────────────────────────────────────────
$projects = db()->query("SELECT id, name, code FROM temp_projects ORDER BY name")->fetchAll();

$projectRow      = null;
$sitesForProject = [];
if ($projectId) {
    $stP = db()->prepare("SELECT id, name, code FROM temp_projects WHERE id = ?");
    $stP->execute([$projectId]);
    $projectRow = $stP->fetch();

    $stS = db()->prepare("SELECT id, name FROM temp_sites WHERE project_id = ? ORDER BY name");
    $stS->execute([$projectId]);
    $sitesForProject = $stS->fetchAll();
}

// ── Employees + existing attendance for the week ─────────────────
$employees = [];
$existing  = []; // [employee_id][ymd] => hours_worked
if ($projectId) {
    $where  = ['te.project_id = ?', "te.status = 'active'"];
    $params = [$projectId];
    if ($siteId) { $where[] = 'te.site_id = ?'; $params[] = $siteId; }
    $whereSQL = implode(' AND ', $where);

    $stE = db()->prepare("
        SELECT te.id, te.employee_number, te.first_name, te.last_name,
               te.position_title, ts.name AS site_name
        FROM temp_employees te
        LEFT JOIN temp_sites ts ON ts.id = te.site_id
        WHERE $whereSQL
        ORDER BY ts.name, te.last_name, te.first_name
    ");
    $stE->execute($params);
    $employees = $stE->fetchAll();

    if ($employees) {
        $empIds = array_column($employees, 'id');
        $in     = implode(',', array_fill(0, count($empIds), '?'));
        $to     = date('Y-m-d', $fromTs + 6 * 86400);
        $stA = db()->prepare("SELECT employee_id, attendance_date, hours_worked FROM temp_attendance
            WHERE employee_id IN ($in) AND attendance_date BETWEEN ? AND ?");
        $stA->execute(array_merge($empIds, [$from, $to]));
        foreach ($stA->fetchAll() as $row) {
            $existing[$row['employee_id']][$row['attendance_date']] = $row['hours_worked'];
        }
    }
}

$csrf = generateCsrfToken();
$pageTitle = 'Enter Attendance';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Enter Attendance</h1>
        <p class="text-muted small mb-0">Supervisor/HR-entered digital attendance for temporary employees</p>
    </div>
    <a href="<?= APP_URL ?>/modules/temp_employees/index.php" class="btn btn-outline-secondary btn-sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Temp Employees
    </a>
</div>

<?= renderFlash() ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm fw-semibold">Project <span class="text-danger">*</span></label>
                <select name="project" class="form-select form-select-sm" required onchange="this.form.submit()">
                    <option value="">— Select Project —</option>
                    <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $projectId == $p['id'] ? 'selected' : '' ?>><?= e($p['code']) ?> — <?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($sitesForProject): ?>
            <div class="col-12 col-md-2">
                <label class="form-label form-label-sm fw-semibold">Site</label>
                <select name="site" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Sites</option>
                    <?php foreach ($sitesForProject as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $siteId == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="site" value="">
            <?php endif; ?>
            <div class="col-12 col-md-2">
                <label class="form-label form-label-sm fw-semibold">Week of</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>" onchange="this.form.submit()">
            </div>
            <noscript><div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Go</button></div></noscript>
        </form>
    </div>
</div>

<?php if ($projectId && $employees): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="?project=<?= $projectId ?>&site=<?= $siteId ?>&from=<?= date('Y-m-d', $fromTs - 7 * 86400) ?>" class="btn btn-outline-secondary btn-sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Prev Week
    </a>
    <span class="fw-semibold" style="font-size:0.88rem;"><?= date('D d M Y', $fromTs) ?> &nbsp;–&nbsp; <?= date('D d M Y', $fromTs + 6 * 86400) ?></span>
    <a href="?project=<?= $projectId ?>&site=<?= $siteId ?>&from=<?= date('Y-m-d', $fromTs + 7 * 86400) ?>" class="btn btn-outline-secondary btn-sm">
        Next Week
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
</div>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="project" value="<?= $projectId ?>">
    <input type="hidden" name="site" value="<?= $siteId ?>">
    <input type="hidden" name="from" value="<?= $from ?>">

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div style="overflow-x:auto;">
            <table class="table mb-0" style="min-width:900px;font-size:0.8rem;">
                <thead>
                    <tr style="background:#023852;">
                        <th style="color:#fff;min-width:160px;">Employee</th>
                        <?php foreach ($days as $d): ?>
                        <th style="color:#fff;text-align:center;min-width:70px;"><?= $d['short'] ?><br><span style="font-weight:400;font-size:0.68rem;"><?= $d['date'] ?></span></th>
                        <?php endforeach; ?>
                        <th style="color:#fff;text-align:center;min-width:70px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $lastSite = '__NONE__'; foreach ($employees as $emp):
                        $siteName = $emp['site_name'] ?? 'Unassigned';
                        if ($siteName !== $lastSite): $lastSite = $siteName; ?>
                    <tr><td colspan="9" style="background:#eef2f7;font-weight:600;color:#023852;font-size:0.75rem;">Site: <?= e($siteName) ?></td></tr>
                    <?php endif; ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                            <div class="text-muted" style="font-size:0.7rem;"><?= e($emp['employee_number']) ?><?= $emp['position_title'] ? ' · ' . e($emp['position_title']) : '' ?></div>
                        </td>
                        <?php $rowTotal = 0; foreach ($days as $d):
                            $val = $existing[$emp['id']][$d['ymd']] ?? '';
                            $rowTotal += (float)$val;
                        ?>
                        <td style="text-align:center;">
                            <input type="number" class="form-control form-control-sm hours-input" style="width:60px;margin:0 auto;text-align:center;"
                                   name="hours[<?= $emp['id'] ?>][<?= $d['ymd'] ?>]" value="<?= $val !== '' ? e((string)$val) : '' ?>"
                                   min="0" max="24" step="0.5" placeholder="0">
                        </td>
                        <?php endforeach; ?>
                        <td style="text-align:center;font-weight:600;" class="row-total"><?= $rowTotal > 0 ? number_format($rowTotal, 1) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Save Attendance</button>
        <span class="text-muted small ms-2">Leave a cell blank to skip it. Existing values load automatically for this week.</span>
    </div>
</form>

<?php elseif ($projectId && !$employees): ?>
<div class="alert alert-info">No active employees found for this project<?= $siteId ? ' / site' : '' ?>.</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <p class="text-muted mb-1 fw-semibold">Select a project to enter attendance</p>
        <p class="text-muted small">Choose the project above, optionally filter by site and week.</p>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
