<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('temp_employees.view', 'view');

$activeMenu = 'temp_employees';

// ── Export handlers (run before any HTML output) ──────────────
$export = $_GET['export'] ?? '';

if ($export === 'excel' || $export === 'pdf') {
    // Build full data query (no pagination limit for exports)
    $exportWhere  = ['1=1'];
    $exportParams = [];
    $exportSearch    = trim($_GET['search'] ?? '');
    $exportProjectId = (int)($_GET['project'] ?? 0);
    $exportSiteId    = (int)($_GET['site'] ?? 0);
    $exportStatus    = $_GET['status'] ?? '';

    if ($exportSearch !== '') {
        $exportWhere[] = '(te.first_name LIKE ? OR te.last_name LIKE ? OR te.employee_number LIKE ? OR te.email LIKE ? OR te.position_title LIKE ?)';
        $s = '%' . $exportSearch . '%';
        array_push($exportParams, $s, $s, $s, $s, $s);
    }
    if ($exportProjectId) { $exportWhere[] = 'te.project_id = ?'; $exportParams[] = $exportProjectId; }
    if ($exportSiteId)    { $exportWhere[] = 'te.site_id = ?';    $exportParams[] = $exportSiteId; }
    if ($exportStatus)    { $exportWhere[] = 'te.status = ?';     $exportParams[] = $exportStatus; }

    $exportSQL = implode(' AND ', $exportWhere);
    $stmtExport = db()->prepare("
        SELECT te.employee_number, te.first_name, te.last_name, te.phone, te.email,
               te.position_title, tp.code AS project_code, tp.name AS project_name,
               ts.name AS site_name, te.start_date, te.end_date, te.status, te.daily_rate, te.rate_type, te.notes
        FROM temp_employees te
        LEFT JOIN temp_projects tp ON tp.id = te.project_id
        LEFT JOIN temp_sites    ts ON ts.id = te.site_id
        WHERE $exportSQL
        ORDER BY te.created_at DESC
    ");
    $stmtExport->execute($exportParams);
    $exportRows = $stmtExport->fetchAll();

    if ($export === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="temp_employees_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $cols = ['Employee Number','First Name','Last Name','Phone','Email','Position',
                 'Project Code','Project Name','Site','Start Date','End Date','Status','Rate Type','Rate (K)','Notes'];
        echo implode("\t", $cols) . "\n";
        foreach ($exportRows as $r) {
            echo implode("\t", [
                $r['employee_number'], $r['first_name'], $r['last_name'],
                $r['phone'] ?? '', $r['email'] ?? '', $r['position_title'] ?? '',
                $r['project_code'] ?? '', $r['project_name'] ?? '', $r['site_name'] ?? '',
                $r['start_date'] ?? '', $r['end_date'] ?? '', $r['status'],
                ucfirst($r['rate_type'] ?? 'daily'),
                $r['daily_rate'] ?? '', $r['notes'] ?? ''
            ]) . "\n";
        }
        exit;
    }

    if ($export === 'pdf') {
        $companySettings = getCompanySettings();
        $companyName = $companySettings['company_name'] ?? 'Komagin HR';
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Temporary Employees — <?= date('d M Y') ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 10px; color: #111; padding: 20px; }
  .print-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; border-bottom: 2px solid #023852; padding-bottom: 10px; }
  .print-header h1 { font-size: 15px; color: #023852; }
  .print-header .meta { font-size: 9px; color: #555; text-align: right; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th { background: #023852; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; }
  td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
  tr:nth-child(even) td { background: #f8fafc; }
  .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 8px; font-weight: 600; text-transform: uppercase; }
  .badge-active { background: #dcfce7; color: #166534; }
  .badge-completed { background: #f1f5f9; color: #475569; }
  .badge-terminated { background: #fee2e2; color: #991b1b; }
  .print-footer { margin-top: 20px; font-size: 8px; color: #94a3b8; text-align: center; }
  @media print { body { padding: 10px; } @page { margin: 10mm; size: A4 landscape; } }
</style>
</head>
<body>
<div class="print-header">
  <div>
    <h1><?= htmlspecialchars($companyName) ?></h1>
    <div style="font-size:11px;font-weight:600;margin-top:3px;">Temporary Employees Report</div>
    <?php if ($exportStatus || $exportSearch || $exportProjectId): ?>
    <div style="font-size:9px;color:#555;margin-top:4px;">
      Filtered:
      <?= $exportSearch ? 'Search: ' . htmlspecialchars($exportSearch) . '  ' : '' ?>
      <?= $exportStatus ? 'Status: ' . htmlspecialchars(ucfirst($exportStatus)) : '' ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="meta">
    <div>Generated: <?= date('d M Y, H:i') ?></div>
    <div>Total Records: <?= count($exportRows) ?></div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>Emp. Number</th>
      <th>Name</th>
      <th>Position</th>
      <th>Project</th>
      <th>Site</th>
      <th>Start Date</th>
      <th>End Date</th>
      <th>Rate</th>
      <th>Status</th>
      <th>Phone</th>
      <th>Email</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($exportRows as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['employee_number']) ?></td>
      <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
      <td><?= htmlspecialchars($r['position_title'] ?? '') ?></td>
      <td><?= htmlspecialchars(($r['project_code'] ? $r['project_code'] . ' ' : '') . ($r['project_name'] ?? '')) ?></td>
      <td><?= htmlspecialchars($r['site_name'] ?? '') ?></td>
      <td><?= $r['start_date'] ? date('d M Y', strtotime($r['start_date'])) : '' ?></td>
      <td><?= $r['end_date']   ? date('d M Y', strtotime($r['end_date']))   : '' ?></td>
      <td><?= $r['daily_rate'] !== null ? 'K ' . number_format((float)$r['daily_rate'], 2) . ' /' . ($r['rate_type'] === 'hourly' ? 'hr' : 'day') : '' ?></td>
      <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
      <td><?= htmlspecialchars($r['phone'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<div class="print-footer"><?= htmlspecialchars($companyName) ?> — Temporary Employees Report — <?= date('d M Y') ?> — Confidential</div>
<script>window.onload = function(){ window.print(); };</script>
</body>
</html>
        <?php
        exit;
    }
}

// ── Filters ──────────────────────────────────────────────────
$search    = trim($_GET['search'] ?? '');
$projectId = (int)($_GET['project'] ?? 0);
$siteId    = (int)($_GET['site'] ?? 0);
$status    = $_GET['status'] ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 25;
$offset    = ($page - 1) * $perPage;

// ── Projects & Sites for filters ─────────────────────────────
$projects = db()->query("SELECT id, name, code FROM temp_projects ORDER BY name")->fetchAll();
$allSites = db()->query("SELECT id, project_id, name FROM temp_sites ORDER BY name")->fetchAll();

// Sites filtered by selected project for dropdown
$sitesForFilter = $projectId
    ? array_filter($allSites, fn($s) => (int)$s['project_id'] === $projectId)
    : $allSites;

// ── Query ─────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(te.first_name LIKE ? OR te.last_name LIKE ? OR te.employee_number LIKE ? OR te.email LIKE ? OR te.position_title LIKE ?)';
    $s = '%' . $search . '%';
    array_push($params, $s, $s, $s, $s, $s);
}
if ($projectId) { $where[] = 'te.project_id = ?'; $params[] = $projectId; }
if ($siteId)    { $where[] = 'te.site_id = ?';    $params[] = $siteId; }
if ($status)    { $where[] = 'te.status = ?';     $params[] = $status; }

$whereSQL = implode(' AND ', $where);

$stmtCount = db()->prepare("SELECT COUNT(*) FROM temp_employees te WHERE $whereSQL");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$stmtRows = db()->prepare("
    SELECT te.*,
           tp.name AS project_name, tp.code AS project_code,
           ts.name AS site_name
    FROM temp_employees te
    LEFT JOIN temp_projects tp ON tp.id = te.project_id
    LEFT JOIN temp_sites    ts ON ts.id = te.site_id
    WHERE $whereSQL
    ORDER BY te.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmtRows->execute($params);
$rows = $stmtRows->fetchAll();

$totalPages = max(1, (int)ceil($total / $perPage));

// ── Summary counts ────────────────────────────────────────────
$counts = db()->query("SELECT status, COUNT(*) as cnt FROM temp_employees GROUP BY status")
               ->fetchAll(PDO::FETCH_KEY_PAIR);

// ── Pagination helper ─────────────────────────────────────────
function buildPageUrl(int $p): string {
    $q = $_GET;
    $q['page'] = $p;
    return '?' . http_build_query($q);
}

$pageTitle = 'Temporary Employees';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<!-- Page header -->
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h1 class="page-title mb-0">Temporary Employees</h1>
            <p class="text-muted small mb-0">Contract &amp; project-based workforce by project and site</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <?php if (canExport('temp_employees.view')): ?>
            <a href="?<?= http_build_query(array_merge(array_filter(['search'=>$search,'project'=>$projectId?:null,'site'=>$siteId?:null,'status'=>$status]), ['export'=>'excel'])) ?>"
               class="btn btn-secondary btn-sm" title="Export to Excel">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Excel
            </a>
            <a href="?<?= http_build_query(array_merge(array_filter(['search'=>$search,'project'=>$projectId?:null,'site'=>$siteId?:null,'status'=>$status]), ['export'=>'pdf'])) ?>"
               class="btn btn-secondary btn-sm" target="_blank" title="Export to PDF">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                PDF
            </a>
            <a href="<?= APP_URL ?>/modules/temp_employees/timesheet.php<?= $projectId ? '?project=' . $projectId . ($siteId ? '&site=' . $siteId : '') : '' ?>"
               class="btn btn-outline-secondary btn-sm" title="Download weekly timesheet">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Timesheet
            </a>
            <?php endif; ?>
            <?php if (canCreate('temp_employees.create')): ?>
            <a href="<?= APP_URL ?>/modules/temp_employees/add.php" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Temporary Employee
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
        <div class="kpi-card kpi-primary">
            <div class="kpi-card-label">Total Records</div>
            <div class="kpi-card-value"><?= $total ?></div>
        </div>
        <div class="kpi-card kpi-success">
            <div class="kpi-card-label">Active</div>
            <div class="kpi-card-value"><?= $counts['active'] ?? 0 ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card-label">Completed</div>
            <div class="kpi-card-value"><?= $counts['completed'] ?? 0 ?></div>
        </div>
        <div class="kpi-card kpi-danger">
            <div class="kpi-card-label">Terminated</div>
            <div class="kpi-card-value"><?= $counts['terminated'] ?? 0 ?></div>
        </div>
    </div>

    <!-- Flash messages -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search name, number, email, position…"
                           value="<?= e($search) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <select name="project" class="form-select form-select-sm" id="projectFilter">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $projectId == $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['code']) ?> — <?= e($p['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="site" class="form-select form-select-sm" id="siteFilter">
                        <option value="">All Sites</option>
                        <?php foreach ($allSites as $s): ?>
                        <option value="<?= $s['id'] ?>"
                            data-project="<?= $s['project_id'] ?>"
                            <?= $siteId == $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active"     <?= $status === 'active'     ? 'selected' : '' ?>>Active</option>
                        <option value="completed"  <?= $status === 'completed'  ? 'selected' : '' ?>>Completed</option>
                        <option value="terminated" <?= $status === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <a href="?" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($rows)): ?>
            <div class="text-center py-5 text-muted">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-3 d-block mx-auto opacity-50"><circle cx="12" cy="7" r="4"/><path d="M5.5 20a6.5 6.5 0 0 1 13 0"/></svg>
                <p class="mb-0">No temporary employees found.</p>
                <?php if (canCreate('temp_employees.create')): ?>
                <a href="<?= APP_URL ?>/modules/temp_employees/add.php" class="btn btn-primary btn-sm mt-3">Add First Temp Employee</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:0.84rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Employee</th>
                            <th>Number</th>
                            <th>Project</th>
                            <th>Site</th>
                            <th>Position</th>
                            <th>Contract</th>
                            <th>Status</th>
                            <th>Portal</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $emp): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--primary,#023852);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:600;flex-shrink:0;">
                                    <?= strtoupper(substr($emp['first_name'],0,1) . substr($emp['last_name'],0,1)) ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem;"><?= e($emp['email'] ?? '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><code style="font-size:0.76rem;"><?= e($emp['employee_number']) ?></code></td>
                        <td>
                            <?php if ($emp['project_name']): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:0.72rem;"><?= e($emp['project_code']) ?></span>
                            <div class="text-muted" style="font-size:0.73rem;"><?= e($emp['project_name']) ?></div>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= e($emp['site_name'] ?? '—') ?>
                        </td>
                        <td><?= e($emp['position_title'] ?? '—') ?></td>
                        <td style="white-space:nowrap;">
                            <div style="font-size:0.76rem;"><?= $emp['start_date'] ? date('d M Y', strtotime($emp['start_date'])) : '—' ?></div>
                            <?php if ($emp['end_date']): ?>
                            <div class="text-muted" style="font-size:0.73rem;">to <?= date('d M Y', strtotime($emp['end_date'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $badgeClass = match($emp['status']) {
                                'active'     => 'success',
                                'completed'  => 'secondary',
                                'terminated' => 'danger',
                                default      => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badgeClass ?> bg-opacity-15 text-<?= $badgeClass ?> border border-<?= $badgeClass ?>" style="font-size:0.72rem;">
                                <?= ucfirst($emp['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($emp['portal_active']): ?>
                            <span class="badge bg-success bg-opacity-15 text-success border border-success" style="font-size:0.71rem;">Enabled</span>
                            <?php else: ?>
                            <span class="badge bg-light text-muted border" style="font-size:0.71rem;">Off</span>
                            <?php endif; ?>
                            <?php
                            $am = $emp['attendance_method'] ?? 'kiosk';
                            [$amLabel, $amColor] = match($am) {
                                'kiosk'     => ['Kiosk', 'primary'],
                                'timesheet' => ['Timesheet', 'warning'],
                                'both'      => ['Both', 'success'],
                                default     => ['Kiosk', 'primary'],
                            };
                            ?>
                            <br><span class="badge bg-<?= $amColor ?> bg-opacity-10 text-<?= $amColor ?>" style="font-size:0.66rem;margin-top:2px;"><?= $amLabel ?></span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="<?= APP_URL ?>/modules/temp_employees/view.php?id=<?= $emp['id'] ?>"
                                   class="btn btn-outline-secondary btn-sm" title="View" style="padding:3px 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <?php if (canEdit('temp_employees.edit')): ?>
                                <a href="<?= APP_URL ?>/modules/temp_employees/edit.php?id=<?= $emp['id'] ?>"
                                   class="btn btn-outline-primary btn-sm" title="Edit" style="padding:3px 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <?php endif; ?>
                                <?php if (canDelete('temp_employees.delete')): ?>
                                <a href="<?= APP_URL ?>/modules/temp_employees/delete.php?id=<?= $emp['id'] ?>"
                                   class="btn btn-danger btn-sm" title="Delete" style="padding:3px 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" style="font-size:0.8rem;">
                <span class="text-muted">Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?></span>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= buildPageUrl($page - 1) ?>">‹</a></li>
                    <?php endif; ?>
                    <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= buildPageUrl($p) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="<?= buildPageUrl($page + 1) ?>">›</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<script>
// Filter sites by selected project
document.getElementById('projectFilter').addEventListener('change', function() {
    const pid = this.value;
    const sel = document.getElementById('siteFilter');
    Array.from(sel.options).forEach(opt => {
        if (!opt.value) return; // keep "All Sites"
        opt.hidden = pid && opt.dataset.project !== pid;
    });
    // reset site if it no longer belongs to selected project
    if (pid && sel.value && sel.options[sel.selectedIndex].dataset.project !== pid) {
        sel.value = '';
    }
});
// Apply on load to handle pre-selected project
document.getElementById('projectFilter').dispatchEvent(new Event('change'));
</script>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
