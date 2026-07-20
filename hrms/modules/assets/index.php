<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('assets.view', 'view');

$pageTitle  = 'Asset Management';
$activeMenu = 'assets';

$statusFilter = $_GET['status'] ?? '';
$page         = max(1,(int)($_GET['page'] ?? 1));
$perPage      = 25;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    requirePermission('assets.manage', 'create');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_asset') {
        $desc     = trim($_POST['description'] ?? '');
        $type     = $_POST['asset_type'] ?? 'other';
        $serial   = trim($_POST['serial_number'] ?? '');
        $model    = trim($_POST['make_model'] ?? '');
        $value    = $_POST['purchase_value'] ?? null;
        $date     = $_POST['purchase_date'] ?? null;
        $notes    = trim($_POST['notes'] ?? '');
        if ($desc) {
            db()->prepare("INSERT INTO company_assets (description, asset_type, serial_number, make_model, purchase_value, purchase_date, notes)
                VALUES (?,?,?,?,?,?,?)")
                ->execute([$desc, $type, $serial ?: null, $model ?: null, $value ?: null, $date ?: null, $notes ?: null]);
            auditLog('assets','add_asset',null,null,json_encode(['desc'=>$desc,'type'=>$type,'serial'=>$serial]));
            setFlash('success','Asset added.');
        }
    } elseif ($action === 'assign_asset') {
        $assetId = (int)($_POST['asset_id'] ?? 0);
        $empId   = (int)($_POST['employee_id'] ?? 0);
        $date    = $_POST['issued_date'] ?? date('Y-m-d');
        $notes   = trim($_POST['notes'] ?? '');
        if ($assetId && $empId) {
            db()->prepare("UPDATE company_assets SET is_available=0 WHERE id=?")->execute([$assetId]);
            db()->prepare("INSERT INTO asset_assignments (asset_id, employee_id, issued_date, notes, issued_by) VALUES (?,?,?,?,?)")
                ->execute([$assetId, $empId, $date, $notes ?: null, $_SESSION['user_id']]);
            auditLog('assets','assign',$assetId,null,json_encode(['employee'=>$empId]));
            setFlash('success','Asset assigned successfully.');
        }
    } elseif ($action === 'return_asset') {
        $assignId = (int)($_POST['assign_id'] ?? 0);
        $assetId  = (int)($_POST['asset_id'] ?? 0);
        if ($assignId && $assetId) {
            db()->prepare("UPDATE asset_assignments SET returned_date=CURDATE(), is_returned=1 WHERE id=?")->execute([$assignId]);
            db()->prepare("UPDATE company_assets SET is_available=1 WHERE id=?")->execute([$assetId]);
            auditLog('assets','return',$assetId,null,null);
            setFlash('success','Asset returned.');
        }
    }
    header('Location: ' . APP_URL . '/modules/assets/index.php'); exit;
}

$where  = ['1=1'];
$params = [];
if ($statusFilter === 'available') { $where[] = 'ca.is_available=1'; }
elseif ($statusFilter === 'assigned') { $where[] = 'ca.is_available=0'; }
$whereSQL = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM company_assets ca WHERE $whereSQL");
$countStmt->execute($params);
$totalAssets = (int)$countStmt->fetchColumn();
$pagination = paginate($totalAssets,$perPage,$page);

$stmt = db()->prepare("SELECT ca.*,
    (SELECT CONCAT(e.first_name,' ',e.last_name) FROM asset_assignments aa JOIN employees e ON aa.employee_id=e.id WHERE aa.asset_id=ca.id AND aa.is_returned=0 ORDER BY aa.issued_date DESC LIMIT 1) as assigned_to,
    (SELECT aa.id FROM asset_assignments aa WHERE aa.asset_id=ca.id AND aa.is_returned=0 ORDER BY aa.issued_date DESC LIMIT 1) as current_assignment_id
    FROM company_assets ca WHERE $whereSQL ORDER BY ca.created_at DESC LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$assets = $stmt->fetchAll();

$totalAll      = (int)db()->query("SELECT COUNT(*) FROM company_assets")->fetchColumn();
$totalAvail    = (int)db()->query("SELECT COUNT(*) FROM company_assets WHERE is_available=1")->fetchColumn();
$totalAssigned = (int)db()->query("SELECT COUNT(*) FROM company_assets WHERE is_available=0")->fetchColumn();

$availableAssets = db()->query("SELECT id, description, asset_type, serial_number FROM company_assets WHERE is_available=1 ORDER BY description")->fetchAll();
$employees = db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as name FROM employees WHERE status IN ('active','probation') ORDER BY first_name")->fetchAll();
$isHR = canCreate('assets.manage');
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Asset Management</h1>
        <p class="page-subtitle">Company assets and assignments</p>
    </div>
    <?php if ($isHR): ?>
    <div class="page-actions">
        <button class="btn btn-secondary btn-sm" data-modal-open="assignModal">Assign Asset</button>
        <button class="btn btn-primary btn-sm" data-modal-open="addAssetModal">Add Asset</button>
    </div>
    <?php endif; ?>
</div>

<!-- Stats -->
<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
    <div class="kpi-card kpi-primary"><div class="kpi-card-label">Total Assets</div><div class="kpi-card-value"><?= $totalAll ?></div></div>
    <div class="kpi-card kpi-success"><div class="kpi-card-label">Available</div><div class="kpi-card-value"><?= $totalAvail ?></div></div>
    <div class="kpi-card kpi-warning"><div class="kpi-card-label">Assigned</div><div class="kpi-card-value"><?= $totalAssigned ?></div></div>
</div>

<div class="card">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <select class="form-select" name="status" style="width:auto;" onchange="this.form.submit()">
                <option value="">All Assets</option>
                <option value="available" <?= $statusFilter==='available'?'selected':''?>>Available</option>
                <option value="assigned" <?= $statusFilter==='assigned'?'selected':''?>>Assigned</option>
            </select>
        </form>
        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);"><?= $totalAssets ?> assets</div>
    </div>

    <div class="table-wrapper" style="border:none;">
        <?php if (empty($assets)): ?>
        <div class="empty-state"><div class="empty-state-title">No assets found</div></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Description</th><th>Type</th><th>Serial #</th><th>Make/Model</th><th>Value</th><th>Availability</th><th>Assigned To</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($assets as $a): ?>
            <tr>
                <td style="font-weight:600;"><?= e($a['description']) ?></td>
                <td style="font-size:0.72rem;"><?= ucfirst($a['asset_type']) ?></td>
                <td class="emp-num"><?= e($a['serial_number'] ?? '—') ?></td>
                <td style="font-size:0.72rem;"><?= e($a['make_model'] ?? '—') ?></td>
                <td style="font-size:0.75rem;"><?= $a['purchase_value'] ? HRMS_CURRENCY_SYMBOL . " " . number_format($a['purchase_value'],2) : '—' ?></td>
                <td><?= $a['is_available'] ? '<span class="badge badge-success">Available</span>' : '<span class="badge badge-warning">Assigned</span>' ?></td>
                <td style="font-size:0.75rem;"><?= e($a['assigned_to'] ?? '—') ?></td>
                <td>
                    <?php if ($isHR): ?>
                    <?php if ($a['is_available']): ?>
                    <button class="btn btn-ghost btn-sm" onclick="openQuickAssign(<?= $a['id'] ?>, '<?= e($a['description']) ?>')">Assign</button>
                    <?php elseif ($a['current_assignment_id']): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Mark as returned?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="return_asset">
                        <input type="hidden" name="asset_id" value="<?= $a['id'] ?>">
                        <input type="hidden" name="assign_id" value="<?= $a['current_assignment_id'] ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--success);">Return</button>
                    </form>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($isHR): ?>
<!-- Add Asset Modal -->
<div class="modal-overlay" id="addAssetModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Add New Asset</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="add_asset">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Description <span class="required">*</span></label>
                    <input type="text" class="form-control" name="description" required placeholder="e.g. Dell Latitude 5520 Laptop">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Asset Type</label>
                        <select class="form-select" name="asset_type">
                            <option value="laptop">Laptop</option>
                            <option value="phone">Phone</option>
                            <option value="vehicle">Vehicle</option>
                            <option value="ppe">PPE</option>
                            <option value="tools">Tools</option>
                            <option value="id_card">ID Card</option>
                            <option value="uniform">Uniform</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Serial Number</label>
                        <input type="text" class="form-control" name="serial_number">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Make / Model</label>
                    <input type="text" class="form-control" name="make_model" placeholder="e.g. Dell Latitude 5520">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Purchase Value (<?= HRMS_CURRENCY_CODE ?>)</label>
                        <input type="number" class="form-control" name="purchase_value" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" class="form-control" name="purchase_date">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Asset Modal -->
<div class="modal-overlay" id="assignModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Assign Asset to Employee</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="assign_asset">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Asset <span class="required">*</span></label>
                    <select class="form-select" name="asset_id" id="assignAssetSelect" required>
                        <option value="">Select available asset</option>
                        <?php foreach ($availableAssets as $av): ?>
                            <option value="<?= $av['id'] ?>"><?= e($av['description']) ?> — <?= ucfirst($av['asset_type']) ?> <?= $av['serial_number']?'('.$av['serial_number'].')':'' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Employee <span class="required">*</span></label>
                    <select class="form-select" name="employee_id" required>
                        <option value="">Select employee</option>
                        <?php foreach ($employees as $em): ?>
                            <option value="<?= $em['id'] ?>"><?= e($em['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Assign Date</label>
                        <input type="date" class="form-control" name="issued_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input type="text" class="form-control" name="notes" placeholder="Condition, accessories…">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Assign Asset</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openQuickAssign(assetId, assetName) {
    document.getElementById('assignAssetSelect').value = assetId;
    openModal('assignModal');
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>

