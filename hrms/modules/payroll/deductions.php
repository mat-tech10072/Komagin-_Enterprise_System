<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('payroll.deductions', 'view');

$pageTitle  = 'Payroll Deductions';
$activeMenu = 'payroll_deductions';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            requirePermission('payroll.deductions', 'create');
            $empId      = (int)($_POST['employee_id']??0);
            $type       = $_POST['deduction_type'] ?? '';
            $desc       = trim($_POST['description']??'');
            $isPct      = isset($_POST['is_percentage']) ? 1 : 0;
            $amount     = (float)($_POST['amount']??0);
            $pct        = (float)($_POST['percentage']??0);
            $empContrib = (float)($_POST['employer_contribution']??0);
            $empPct     = (float)($_POST['employer_percentage']??0);
            $isRecur    = isset($_POST['is_recurring']) ? 1 : 0;
            $effFrom    = $_POST['effective_from'] ?? date('Y-m-d');
            $effTo      = $_POST['effective_to'] ?: null;
            $notes      = trim($_POST['notes']??'');

            $allowed = ['tax','uif','pension','provident','medical_aid','loan','garnishee','other'];
            if (!$empId || !$type || !$desc || !in_array($type,$allowed)) {
                $error = 'Required fields missing or invalid.';
            } else {
                db()->prepare("INSERT INTO payroll_deductions
                    (employee_id,deduction_type,description,is_percentage,amount,percentage,
                     employer_contribution,employer_percentage,is_recurring,effective_from,effective_to,notes,created_by)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$empId,$type,$desc,$isPct,$amount,$pct,$empContrib,$empPct,
                               $isRecur,$effFrom,$effTo,$notes,$_SESSION['user_id']]);
                auditLog('payroll_deductions','create',(int)db()->lastInsertId(),null,null,"Added {$type} deduction for emp {$empId}");
                $success = 'Deduction added.';
            }
        } elseif ($action === 'toggle') {
            requirePermission('payroll.deductions', 'edit');
            $id = (int)($_POST['id']??0);
            db()->prepare("UPDATE payroll_deductions SET is_active = NOT is_active WHERE id=?")->execute([$id]);
            auditLog('payroll_deductions','toggle',$id);
            $success = 'Deduction status toggled.';
        } elseif ($action === 'delete') {
            // Deleting a deduction (garnishee, loan, pension) is destructive and must be
            // gated on can_delete specifically — the matrix intentionally denies this to
            // payroll_officer/payroll_manager even though they can view/create/edit.
            requirePermission('payroll.deductions', 'delete');
            $id = (int)($_POST['id']??0);
            db()->prepare("DELETE FROM payroll_deductions WHERE id=?")->execute([$id]);
            auditLog('payroll_deductions','delete',$id);
            $success = 'Deduction removed.';
        }
    }
}

$filterEmp  = isset($_GET['emp'])  ? (int)$_GET['emp']  : 0;
$filterType = $_GET['type'] ?? '';

$where  = 'WHERE 1';
$params = [];
if ($filterEmp)  { $where .= ' AND pd.employee_id=?'; $params[] = $filterEmp; }
if ($filterType) { $where .= ' AND pd.deduction_type=?'; $params[] = $filterType; }

$stmt = db()->prepare("SELECT pd.*, e.first_name, e.last_name, e.employee_number
    FROM payroll_deductions pd JOIN employees e ON pd.employee_id=e.id
    $where ORDER BY e.last_name, pd.deduction_type, pd.created_at DESC");
$stmt->execute($params);
$deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$employees = db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as label
    FROM employees WHERE status='active' ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = ['tax'=>'Income Tax','uif'=>'UIF','pension'=>'Pension','provident'=>'Provident Fund',
    'medical_aid'=>'Medical Aid','loan'=>'Loan','garnishee'=>'Garnishee','other'=>'Other'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper">
<div class="content-header">
    <div>
        <h1 class="page-title">Payroll Deductions</h1>
        <p class="page-sub">Manage employee deductions, contributions and recurring payments</p>
    </div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('dedForm').style.display=''">
        + Add Deduction
    </button>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<!-- Add Form -->
<div id="dedForm" style="display:none;margin-bottom:16px">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Add Deduction</span>
            <button class="btn btn-ghost btn-sm" onclick="this.closest('#dedForm').style.display='none'">Cancel</button>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="create">
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">— Select —</option>
                            <?php foreach($employees as $emp): ?>
                            <option value="<?=$emp['id']?>"><?= e($emp['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="deduction_type" class="form-control" required>
                            <?php foreach($typeLabels as $v=>$l): ?>
                            <option value="<?=$v?>"><?=$l?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" required placeholder="e.g. PAYE Tax">
                    </div>
                </div>
                <div class="form-row form-row-4">
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_percentage"> Percentage-based
                        </label>
                        <input type="number" name="percentage" class="form-control" step="0.01" min="0" max="100" placeholder="% rate">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fixed Amount (<?= HRMS_CURRENCY_CODE ?>)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employer Contribution (<?= HRMS_CURRENCY_CODE ?>)</label>
                        <input type="number" name="employer_contribution" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employer Rate (%)</label>
                        <input type="number" name="employer_percentage" class="form-control" step="0.01" min="0" max="100" placeholder="0.00">
                    </div>
                </div>
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Effective From</label>
                        <input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Effective To (leave blank if ongoing)</label>
                        <input type="date" name="effective_to" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_recurring" checked> Recurring monthly
                        </label>
                        <div class="form-text">Uncheck for one-off deduction</div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Deduction</button>
            </form>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card-filters">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <select name="emp" class="form-control form-control-sm">
            <option value="">All Employees</option>
            <?php foreach($employees as $emp): ?>
            <option value="<?=$emp['id']?>" <?=$filterEmp==$emp['id']?'selected':''?>><?= e($emp['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="form-control form-control-sm">
            <option value="">All Types</option>
            <?php foreach($typeLabels as $v=>$l): ?>
            <option value="<?=$v?>" <?=$filterType===$v?'selected':''?>><?=$l?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="?" class="btn btn-secondary btn-sm">Reset</a>
    </form>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Deductions (<?= count($deductions) ?>)</span></div>
    <?php if ($deductions): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Employee Deduction</th>
                    <th>Employer Contribution</th>
                    <th>Recurring</th>
                    <th>Effective</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($deductions as $d): ?>
                <tr style="opacity:<?= $d['is_active']?1:.5 ?>">
                    <td>
                        <div style="font-weight:600"><?= e($d['first_name'].' '.$d['last_name']) ?></div>
                        <div style="font-size:0.7rem;color:var(--text-muted)"><?= e($d['employee_number']) ?></div>
                    </td>
                    <td><?= $typeLabels[$d['deduction_type']]??$d['deduction_type'] ?></td>
                    <td><?= e($d['description']) ?></td>
                    <td>
                        <?php if ($d['is_percentage']&&$d['percentage']>0): ?>
                            <?= $d['percentage'] ?>%
                        <?php endif; ?>
                        <?php if ($d['amount']>0): ?>
                            R <?= number_format($d['amount'],2) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($d['employer_percentage']>0): ?>
                            <?= $d['employer_percentage'] ?>%
                        <?php endif; ?>
                        <?php if ($d['employer_contribution']>0): ?>
                            R <?= number_format($d['employer_contribution'],2) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $d['is_recurring'] ? 'Monthly' : 'One-off' ?></td>
                    <td style="font-size:0.72rem">
                        From: <?= date('d M Y',strtotime($d['effective_from'])) ?><br>
                        To: <?= $d['effective_to']?date('d M Y',strtotime($d['effective_to'])):'Ongoing' ?>
                    </td>
                    <td>
                        <span class="badge <?= $d['is_active']?'badge-success':'badge-secondary' ?>">
                            <?= $d['is_active']?'Active':'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?=$d['id']?>">
                            <button type="submit" class="btn btn-ghost btn-sm">
                                <?= $d['is_active']?'Disable':'Enable' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?=$d['id']?>">
                            <button type="submit" class="btn btn-ghost btn-sm text-danger"
                                onclick="return confirm('Delete this deduction permanently?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><div>No deductions found</div></div>
    <?php endif; ?>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

