<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('payroll.savings', 'view');

$pageTitle  = 'Savings Management';
$activeMenu = 'payroll_savings';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $empId    = (int)($_POST['employee_id']??0);
            $type     = $_POST['savings_type'] ?? '';
            $fund     = trim($_POST['fund_name']??'');
            $target   = (float)($_POST['target_amount']??0);
            $balance  = (float)($_POST['current_balance']??0);
            $empRate  = (float)($_POST['employee_rate_pct']??0);
            $empRateR = (float)($_POST['monthly_employee_contrib']??0);
            $erRate   = (float)($_POST['employer_rate_pct']??0);
            $erRateR  = (float)($_POST['monthly_employer_contrib']??0);
            $totEmp   = (float)($_POST['total_employee_contrib']??0);
            $totEr    = (float)($_POST['total_employer_contrib']??0);
            $start    = $_POST['start_date'] ?: null;
            $projEnd  = $_POST['projected_end_date'] ?: null;
            $notes    = trim($_POST['notes']??'');

            $allowed = ['pension','provident','medical_aid','funeral','savings','other'];
            if (!$empId || !in_array($type,$allowed)) {
                $error = 'Employee and savings type are required.';
            } else {
                $editId = (int)($_POST['edit_id']??0);
                if ($editId) {
                    requirePermission('payroll.savings', 'edit');
                    db()->prepare("UPDATE employee_savings SET savings_type=?,fund_name=?,target_amount=?,current_balance=?,
                        employee_rate_pct=?,employer_rate_pct=?,monthly_employee_contrib=?,monthly_employer_contrib=?,
                        total_employee_contrib=?,total_employer_contrib=?,start_date=?,projected_end_date=?,notes=?
                        WHERE id=? AND employee_id=?")->execute([
                        $type,$fund,$target,$balance,$empRate,$erRate,$empRateR,$erRateR,
                        $totEmp,$totEr,$start,$projEnd,$notes,$editId,$empId
                    ]);
                    auditLog('payroll_savings','update',$editId);
                    $success = 'Savings record updated.';
                } else {
                    requirePermission('payroll.savings', 'create');
                    db()->prepare("INSERT INTO employee_savings
                        (employee_id,savings_type,fund_name,target_amount,current_balance,employee_rate_pct,
                         employer_rate_pct,monthly_employee_contrib,monthly_employer_contrib,
                         total_employee_contrib,total_employer_contrib,start_date,projected_end_date,notes,created_by)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                        ->execute([$empId,$type,$fund,$target,$balance,$empRate,$erRate,$empRateR,$erRateR,
                                   $totEmp,$totEr,$start,$projEnd,$notes,$_SESSION['user_id']]);
                    auditLog('payroll_savings','create',(int)db()->lastInsertId());
                    $success = 'Savings record added.';
                }
            }
        } elseif ($action === 'update_balance') {
            requirePermission('payroll.savings', 'edit');
            $id  = (int)($_POST['id']??0);
            $bal = (float)($_POST['current_balance']??0);
            $emp = (float)($_POST['total_employee_contrib']??0);
            $er  = (float)($_POST['total_employer_contrib']??0);
            db()->prepare("UPDATE employee_savings SET current_balance=?,total_employee_contrib=?,
                total_employer_contrib=? WHERE id=?")->execute([$bal,$emp,$er,$id]);
            auditLog('payroll_savings','update_balance',$id);
            $success = 'Balance updated.';
        } elseif ($action === 'delete') {
            // Deleting a savings/pension record is destructive and must be gated on
            // can_delete specifically — the matrix intentionally denies this to
            // payroll_officer/payroll_manager even though they can view/create/edit.
            requirePermission('payroll.savings', 'delete');
            $id = (int)($_POST['id']??0);
            db()->prepare("DELETE FROM employee_savings WHERE id=?")->execute([$id]);
            auditLog('payroll_savings','delete',$id);
            $success = 'Record deleted.';
        }
    }
}

$filterEmp  = isset($_GET['emp'])  ? (int)$_GET['emp']  : 0;
$filterType = $_GET['type'] ?? '';

$where  = 'WHERE 1';
$params = [];
if ($filterEmp)  { $where .= ' AND es.employee_id=?'; $params[] = $filterEmp; }
if ($filterType) { $where .= ' AND es.savings_type=?'; $params[] = $filterType; }

$stmt = db()->prepare("SELECT es.*, e.first_name, e.last_name, e.employee_number
    FROM employee_savings es JOIN employees e ON es.employee_id=e.id
    $where ORDER BY e.last_name, es.savings_type");
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$employees = db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as label
    FROM employees WHERE status='active' ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = ['pension'=>'Pension Fund','provident'=>'Provident Fund','medical_aid'=>'Medical Aid',
    'funeral'=>'Funeral Cover','savings'=>'Savings Plan','other'=>'Other Fund'];

// Totals
$totBalance = array_sum(array_column($records,'current_balance'));
$totEmpC    = array_sum(array_column($records,'total_employee_contrib'));
$totErC     = array_sum(array_column($records,'total_employer_contrib'));

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper">
<div class="content-header">
    <div>
        <h1 class="page-title">Savings &amp; Benefits Management</h1>
        <p class="page-sub">Manage pension, provident, medical and savings fund contributions</p>
    </div>
    <button class="btn btn-primary btn-sm" id="showFormBtn">+ Add Savings Record</button>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<!-- KPIs -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat-card">
        <div class="stat-label">Total Fund Balance</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?> <?= nf($totBalance,2) ?></div>
        <div class="stat-sub"><?= count($records) ?> active records</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Employee Contributions</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?> <?= nf($totEmpC,2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Employer Contributions</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?> <?= nf($totErC,2) ?></div>
    </div>
</div>

<!-- Add/Edit Form -->
<div id="savForm" style="display:none;margin-bottom:16px">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Add Savings Record</span>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('savForm').style.display='none'">Cancel</button>
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
                        <label class="form-label">Fund Type <span class="text-danger">*</span></label>
                        <select name="savings_type" class="form-control" required>
                            <?php foreach($typeLabels as $v=>$l): ?>
                            <option value="<?=$v?>"><?=$l?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fund Name</label>
                        <input type="text" name="fund_name" class="form-control" placeholder="e.g. ABC Pension Fund">
                    </div>
                </div>
                <div class="form-row form-row-4">
                    <div class="form-group">
                        <label class="form-label">Employee Rate (%)</label>
                        <input type="number" name="employee_rate_pct" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Monthly Employee (<?= CURRENCY_CODE ?>)</label>
                        <input type="number" name="monthly_employee_contrib" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employer Rate (%)</label>
                        <input type="number" name="employer_rate_pct" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Monthly Employer (<?= CURRENCY_CODE ?>)</label>
                        <input type="number" name="monthly_employer_contrib" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="form-row form-row-4">
                    <div class="form-group">
                        <label class="form-label">Total Employee Contrib (<?= CURRENCY_CODE ?>)</label>
                        <input type="number" name="total_employee_contrib" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Employer Contrib (<?= CURRENCY_CODE ?>)</label>
                        <input type="number" name="total_employer_contrib" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Balance (<?= CURRENCY_CODE ?>)</label>
                        <input type="number" name="current_balance" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Target Amount (<?= CURRENCY_CODE ?>)</label>
                        <input type="number" name="target_amount" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Projected End Date</label>
                        <input type="date" name="projected_end_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Record</button>
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
    <div class="card-header"><span class="card-title">Savings Records (<?= count($records) ?>)</span></div>
    <?php if ($records): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Fund Type</th>
                    <th>Fund Name</th>
                    <th>Emp Rate</th>
                    <th>Employer Rate</th>
                    <th>Monthly (Emp)</th>
                    <th>Monthly (Er)</th>
                    <th>Balance</th>
                    <th>Progress</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($records as $r): ?>
                <?php $pct = $r['target_amount']>0 ? min(100,($r['current_balance']/$r['target_amount'])*100) : 0; ?>
                <tr>
                    <td>
                        <div style="font-weight:600"><?= e($r['first_name'].' '.$r['last_name']) ?></div>
                        <div style="font-size:0.7rem;color:var(--text-muted)"><?= e($r['employee_number']) ?></div>
                    </td>
                    <td><?= $typeLabels[$r['savings_type']]??$r['savings_type'] ?></td>
                    <td><?= e($r['fund_name']??'—') ?></td>
                    <td><?= $r['employee_rate_pct'] ?>%</td>
                    <td><?= $r['employer_rate_pct'] ?>%</td>
                    <td><?= CURRENCY_SYMBOL ?> <?= nf($r['monthly_employee_contrib'],2) ?></td>
                    <td><?= CURRENCY_SYMBOL ?> <?= nf($r['monthly_employer_contrib'],2) ?></td>
                    <td><strong><?= CURRENCY_SYMBOL ?> <?= nf($r['current_balance'],2) ?></strong></td>
                    <td style="min-width:120px">
                        <?php if ($r['target_amount']>0): ?>
                        <div style="font-size:0.65rem;margin-bottom:3px"><?= number_format($pct,1) ?>%</div>
                        <div style="background:var(--bg);border-radius:999px;height:6px;overflow:hidden">
                            <div style="height:100%;background:var(--success);width:<?=$pct?>%;border-radius:999px"></div>
                        </div>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <!-- Quick balance update form -->
                        <button class="btn btn-ghost btn-sm" onclick="toggleBalForm(<?=$r['id']?>)">Update Balance</button>
                        <form method="POST" id="balForm<?=$r['id']?>" style="display:none;margin-top:6px">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="update_balance">
                            <input type="hidden" name="id" value="<?=$r['id']?>">
                            <input type="number" name="current_balance" class="form-control form-control-sm" step="0.01"
                                   value="<?=$r['current_balance']?>" placeholder="Balance">
                            <input type="number" name="total_employee_contrib" class="form-control form-control-sm" step="0.01"
                                   value="<?=$r['total_employee_contrib']?>" placeholder="Total emp">
                            <input type="number" name="total_employer_contrib" class="form-control form-control-sm" step="0.01"
                                   value="<?=$r['total_employer_contrib']?>" placeholder="Total employer">
                            <button type="submit" class="btn btn-success btn-sm" style="margin-top:4px">Save</button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?=$r['id']?>">
                            <button type="submit" class="btn btn-ghost btn-sm text-danger"
                                onclick="return confirm('Delete this record?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><div>No savings records found</div></div>
    <?php endif; ?>
</div>
</div>

<script>
document.getElementById('showFormBtn').addEventListener('click',function(){
    document.getElementById('savForm').style.display='';
});
function toggleBalForm(id){
    const f=document.getElementById('balForm'+id);
    f.style.display=f.style.display===''?'none':'';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

