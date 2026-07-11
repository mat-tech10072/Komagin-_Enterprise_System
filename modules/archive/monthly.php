<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('archive.view');

$pageTitle  = 'Monthly Archive';
$activeMenu = 'archive_monthly';

$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));
$type  = $_GET['doc_type'] ?? '';
$deptId= (int)($_GET['dept'] ?? 0);

$where  = ['ar.archive_type = "monthly"', 'ar.year = ?', 'ar.month = ?'];
$params = [$year, $month];
if ($type)  { $where[] = 'ar.document_type = ?'; $params[] = $type; }
if ($deptId){ $where[] = 'ar.department_id = ?'; $params[] = $deptId; }

$whereSQL = implode(' AND ', $where);
$archives = db()->prepare("SELECT ar.*, d.name as dept_name, u.username as generated_by_name
    FROM archive_records ar
    LEFT JOIN departments d ON ar.department_id = d.id
    LEFT JOIN users u ON ar.generated_by = u.id
    WHERE $whereSQL ORDER BY ar.generated_at DESC");
$archives->execute($params);
$archives = $archives->fetchAll();

$departments = getDepartments();
$months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$docTypes = ['timesheets','attendance','leave_report','overtime_report','payroll_support','hr_summary'];

// Handle generate archive action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    requirePermission('archive.generate', 'create');
    $genType = $_POST['gen_type'] ?? '';
    $genMonth = (int)($_POST['gen_month'] ?? $month);
    $genYear  = (int)($_POST['gen_year']  ?? $year);

    if ($genType) {
        // Create archive record
        $title = ucfirst(str_replace('_',' ',$genType)) . ' — ' . $months[$genMonth] . ' ' . $genYear;
        db()->prepare("INSERT INTO archive_records (archive_type, year, month, document_type, title, generated_by) VALUES ('monthly',?,?,?,?,?)")
            ->execute([$genYear, $genMonth, $genType, $title, $_SESSION['user_id']]);
        auditLog('archive','generate_monthly',null,null,json_encode(['type'=>$genType,'month'=>$genMonth,'year'=>$genYear]));
        setFlash('success', "Archive record created: $title");
        header('Location: ' . APP_URL . '/modules/archive/monthly.php?year='.$genYear.'&month='.$genMonth);
        exit;
    }
}

$csrf = generateCsrfToken();
?>
<?php
$headerInclude = dirname(dirname(__DIR__)) . '/includes/header.php';
include $headerInclude;
?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Archive</li>
                <li class="breadcrumb-item active">Monthly</li>
            </ol>
        </nav>
        <h1 class="page-title">Monthly Archive</h1>
        <p class="page-subtitle"><?= $months[$month] ?> <?= $year ?></p>
    </div>
    <?php if (canCreate('archive.generate')): ?>
    <div class="page-actions">
        <button class="btn btn-primary btn-sm" data-modal-open="generateModal">
            Generate Archive
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Date Navigation -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <?php
    $prevMonth = $month > 1 ? $month - 1 : 12;
    $prevYear  = $month > 1 ? $year : $year - 1;
    $nextMonth = $month < 12 ? $month + 1 : 1;
    $nextYear  = $month < 12 ? $year : $year + 1;
    ?>
    <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-secondary btn-sm">← <?= $months[$prevMonth] ?></a>
    <form method="GET" style="display:flex;gap:8px;">
        <select class="form-select" name="year" onchange="this.form.submit()">
            <?php for($y=date('Y');$y>=date('Y')-5;$y--): ?>
                <option value="<?= $y ?>" <?= $year==$y?'selected':''?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <select class="form-select" name="month" onchange="this.form.submit()">
            <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $month==$m?'selected':''?>><?= $months[$m] ?></option>
            <?php endfor; ?>
        </select>
    </form>
    <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-secondary btn-sm"><?= $months[$nextMonth] ?> →</a>
</div>

<!-- Archive Summary Cards -->
<div class="kpi-grid" style="grid-template-columns:repeat(<?= count($docTypes) ?>,1fr);margin-bottom:24px;">
    <?php foreach ($docTypes as $dt): ?>
    <?php $cnt = db()->prepare("SELECT COUNT(*) FROM archive_records WHERE archive_type='monthly' AND year=? AND month=? AND document_type=?");
          $cnt->execute([$year,$month,$dt]); $cnt = (int)$cnt->fetchColumn(); ?>
    <div class="kpi-card <?= $cnt > 0 ? 'kpi-success' : '' ?>">
        <div class="kpi-card-label"><?= ucwords(str_replace('_',' ',$dt)) ?></div>
        <div class="kpi-card-value" style="font-size:1.5rem;"><?= $cnt ?></div>
        <div class="kpi-card-trend"><?= $cnt > 0 ? 'Archived' : 'Pending' ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Archive Records — <?= $months[$month] ?> <?= $year ?></span>
        <span style="font-size:0.72rem;color:var(--text-muted);"><?= count($archives) ?> documents</span>
    </div>
    <div class="table-wrapper" style="border:none;">
        <?php if (empty($archives)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No archive records for <?= $months[$month] ?> <?= $year ?></div>
            <div class="empty-state-desc">Generate archive records at month end to store finalized HR data.</div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Document</th><th>Type</th><th>Department</th><th>Generated By</th><th>Date</th><th>Locked</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($archives as $ar): ?>
            <tr>
                <td style="font-weight:600;"><?= e($ar['title']) ?></td>
                <td><span class="badge badge-info"><?= e(ucwords(str_replace('_',' ',$ar['document_type']))) ?></span></td>
                <td style="font-size:0.72rem;"><?= e($ar['dept_name'] ?? 'All Departments') ?></td>
                <td style="font-size:0.72rem;"><?= e($ar['generated_by_name'] ?? 'System') ?></td>
                <td style="font-size:0.72rem;"><?= formatDateTime($ar['generated_at']) ?></td>
                <td>
                    <?php if ($ar['is_locked']): ?>
                        <span class="badge badge-danger">Locked</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Open</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <?php if ($ar['file_path']): ?>
                            <a href="<?= APP_URL ?>/<?= e($ar['file_path']) ?>" class="btn btn-ghost btn-sm">Download</a>
                        <?php endif; ?>
                        <?php if (!$ar['is_locked'] && in_array($_SESSION['user_role'],['super_admin','hr_manager'])): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="lock_id" value="<?= $ar['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" data-confirm="Lock this archive record?">Lock</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Generate Archive Modal -->
<div class="modal-overlay" id="generateModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h5 class="modal-title">Generate Archive Record</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <select class="form-select" name="gen_year">
                            <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                                <option value="<?= $y ?>" <?= $year==$y?'selected':''?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Month</label>
                        <select class="form-select" name="gen_month">
                            <?php for($m=1;$m<=12;$m++): ?>
                                <option value="<?= $m ?>" <?= $month==$m?'selected':''?>><?= $months[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Document Type</label>
                    <select class="form-select" name="gen_type" required>
                        <option value="">Select type</option>
                        <?php foreach ($docTypes as $dt): ?>
                            <option value="<?= $dt ?>"><?= ucwords(str_replace('_',' ',$dt)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>
    </div>
</div>

<?php
$footerInclude = dirname(dirname(__DIR__)) . '/includes/footer.php';
include $footerInclude;
?>
