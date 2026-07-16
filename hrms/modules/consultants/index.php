<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('consultants.view', 'view');

$activeMenu = 'consultants';

// ── Export handler (before header) ──────────────────────────
$export = $_GET['export'] ?? '';
if ($export && canExport('consultants.view')) {
    $typeFilter   = $_GET['type']   ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $search       = trim($_GET['q'] ?? '');
    $where  = 'WHERE 1';
    $params = [];
    if ($typeFilter)   { $where .= ' AND c.type=?';   $params[] = $typeFilter; }
    if ($statusFilter) { $where .= ' AND c.status=?'; $params[] = $statusFilter; }
    if ($search) {
        $where .= ' AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.consultant_number LIKE ? OR c.company LIKE ?)';
        $like = "%$search%";
        array_push($params, $like, $like, $like, $like);
    }
    $stmt = db()->prepare("SELECT c.consultant_number, c.first_name, c.last_name, c.type, c.company, c.position_title, c.department, c.start_date, c.end_date, c.status, c.daily_rate, c.hourly_rate, c.contract_value FROM consultants c $where ORDER BY c.id");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($export === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="consultants_' . date('Ymd') . '.xls"');
        echo "\xEF\xBB\xBF";
        echo implode("\t", ['Number','First Name','Last Name','Type','Company','Position','Department','Start Date','End Date','Status','Daily Rate','Hourly Rate','Contract Value']) . "\n";
        foreach ($rows as $r) {
            echo implode("\t", [
                $r['consultant_number'], $r['first_name'], $r['last_name'],
                ucfirst(str_replace('_',' ',$r['type'])), $r['company'] ?? '', $r['position_title'] ?? '',
                $r['department'] ?? '', $r['start_date'] ?? '', $r['end_date'] ?? '',
                ucfirst($r['status']), $r['daily_rate'] ?? '', $r['hourly_rate'] ?? '', $r['contract_value'] ?? ''
            ]) . "\n";
        }
        exit;
    }
    if ($export === 'pdf') {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Consultants Export</title>
        <style>body{font-family:Arial,sans-serif;font-size:11px;}table{width:100%;border-collapse:collapse;}
        th,td{border:1px solid #ccc;padding:4px 6px;} th{background:#f0f0f0;}</style></head><body>
        <h2>Consultants Register — ' . date('d M Y') . '</h2><table>
        <thead><tr><th>Number</th><th>Name</th><th>Type</th><th>Company</th><th>Position</th><th>Status</th><th>Start</th><th>End</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td>' . e($r['consultant_number']) . '</td><td>' . e($r['first_name'] . ' ' . $r['last_name']) . '</td>
            <td>' . ucfirst(str_replace('_',' ',$r['type'])) . '</td><td>' . e($r['company'] ?? '') . '</td>
            <td>' . e($r['position_title'] ?? '') . '</td><td>' . ucfirst($r['status']) . '</td>
            <td>' . ($r['start_date'] ? date('d M Y', strtotime($r['start_date'])) : '') . '</td>
            <td>' . ($r['end_date'] ? date('d M Y', strtotime($r['end_date'])) : '') . '</td></tr>';
        }
        echo '</tbody></table></body></html>';
        echo '<script>window.onload=function(){window.print();}</script>';
        exit;
    }
}

// ── Filters ──────────────────────────────────────────────────
$typeFilter   = $_GET['type']   ?? '';
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');

$where  = 'WHERE 1';
$params = [];
if ($typeFilter)   { $where .= ' AND c.type=?';   $params[] = $typeFilter; }
if ($statusFilter) { $where .= ' AND c.status=?'; $params[] = $statusFilter; }
if ($search) {
    $where .= ' AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.consultant_number LIKE ? OR c.company LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

// ── Stats ─────────────────────────────────────────────────────
$counts = db()->query("SELECT
    COUNT(*) AS total,
    SUM(type='time_based') AS time_based,
    SUM(type='output_based') AS output_based,
    SUM(status='active') AS active
FROM consultants")->fetch(PDO::FETCH_ASSOC);

// ── List ──────────────────────────────────────────────────────
$stmt = db()->prepare("SELECT c.* FROM consultants c $where ORDER BY c.id DESC");
$stmt->execute($params);
$consultants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash     = getFlash();
$pageTitle = 'Consultants';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Consultants</h1>
        <p class="page-subtitle">Manage time-based and output-based consultants</p>
    </div>
    <div class="page-actions">
        <?php if (canExport('consultants.view')): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'pdf'])) ?>" class="btn btn-secondary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            PDF
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'excel'])) ?>" class="btn btn-secondary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            Excel
        </a>
        <?php endif; ?>
        <?php if (canCreate('consultants.create')): ?>
        <a href="<?= APP_URL ?>/modules/consultants/add.php" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Consultant
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
    <?= e($flash['message']) ?><button type="button" class="btn-close" onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="kpi-card kpi-primary">
        <div class="kpi-card-label">Total Consultants</div>
        <div class="kpi-card-value"><?= $counts['total'] ?></div>
    </div>
    <div class="kpi-card kpi-info">
        <div class="kpi-card-label">Time-Based</div>
        <div class="kpi-card-value"><?= $counts['time_based'] ?? 0 ?></div>
    </div>
    <div class="kpi-card kpi-warning">
        <div class="kpi-card-label">Output-Based</div>
        <div class="kpi-card-value"><?= $counts['output_based'] ?? 0 ?></div>
    </div>
    <div class="kpi-card kpi-success">
        <div class="kpi-card-label">Active</div>
        <div class="kpi-card-value"><?= $counts['active'] ?? 0 ?></div>
    </div>
</div>

<!-- Type Tabs -->
<div class="tab-nav" style="margin-bottom:0;">
    <a href="?<?= http_build_query(array_merge($_GET, ['type'=>''])) ?>" class="tab-item <?= !$typeFilter ? 'active' : '' ?>">All</a>
    <a href="?<?= http_build_query(array_merge($_GET, ['type'=>'time_based'])) ?>" class="tab-item <?= $typeFilter==='time_based' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Time-Based
    </a>
    <a href="?<?= http_build_query(array_merge($_GET, ['type'=>'output_based'])) ?>" class="tab-item <?= $typeFilter==='output_based' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Output-Based
    </a>
</div>

<!-- Table -->
<div class="card" style="border-top-left-radius:0;border-top-right-radius:0;">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <input type="hidden" name="type" value="<?= e($typeFilter) ?>">
            <input type="text" class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search name, number, company…" style="min-width:220px;">
            <select class="form-select" name="status">
                <option value="">All Statuses</option>
                <?php foreach (['active'=>'Active','completed'=>'Completed','terminated'=>'Terminated'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $statusFilter===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm" type="submit">Filter</button>
            <a href="?type=<?= e($typeFilter) ?>" class="btn btn-secondary btn-sm">Clear</a>
        </form>
    </div>

    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Company</th>
                    <th>Position</th>
                    <th>Contract Period</th>
                    <th>Status</th>
                    <th>Portal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$consultants): ?>
                <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--text-muted);">No consultants found.</td></tr>
            <?php endif; ?>
            <?php foreach ($consultants as $c): ?>
            <?php
                $typeColor = $c['type'] === 'time_based' ? 'info' : 'warning';
                $typeLabel = $c['type'] === 'time_based' ? 'Time-Based' : 'Output-Based';
                $sColor = match($c['status']) { 'active'=>'success','completed'=>'secondary','terminated'=>'danger', default=>'secondary' };
            ?>
            <tr>
                <td><code style="font-size:0.75rem;"><?= e($c['consultant_number']) ?></code></td>
                <td>
                    <a href="<?= APP_URL ?>/modules/consultants/view.php?id=<?= $c['id'] ?>" style="font-weight:600;color:var(--text);">
                        <?= e($c['first_name'] . ' ' . $c['last_name']) ?>
                    </a>
                </td>
                <td><span class="badge" style="background:var(--<?=$typeColor?>-bg);color:var(--<?=$typeColor?>);border:1px solid var(--<?=$typeColor?>);font-size:0.7rem;"><?= $typeLabel ?></span></td>
                <td style="font-size:0.82rem;"><?= e($c['company'] ?? '—') ?></td>
                <td style="font-size:0.82rem;"><?= e($c['position_title'] ?? '—') ?></td>
                <td style="font-size:0.78rem;color:var(--text-secondary);">
                    <?= $c['start_date'] ? date('d M Y', strtotime($c['start_date'])) : '—' ?>
                    <?= $c['end_date'] ? ' – ' . date('d M Y', strtotime($c['end_date'])) : '' ?>
                </td>
                <td><span class="badge" style="background:var(--<?=$sColor?>-bg);color:var(--<?=$sColor?>);border:1px solid var(--<?=$sColor?>);font-size:0.7rem;"><?= ucfirst($c['status']) ?></span></td>
                <td>
                    <?php if ($c['portal_active']): ?>
                    <span style="color:var(--success);font-size:0.78rem;">● Active</span>
                    <?php else: ?>
                    <span style="color:var(--text-muted);font-size:0.78rem;">— Off</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= APP_URL ?>/modules/consultants/view.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                        <?php if (canEdit('consultants.edit')): ?>
                        <a href="<?= APP_URL ?>/modules/consultants/edit.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                        <?php endif; ?>
                        <?php if (canDelete('consultants.delete')): ?>
                        <a href="<?= APP_URL ?>/modules/consultants/delete.php?id=<?= $c['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
