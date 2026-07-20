<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('payroll.payslips', 'view');

$pageTitle  = 'Payslip Management';
$activeMenu = 'payroll_payslips';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

$success = '';
$error   = '';

// Save / update payslip
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $empId   = (int)($_POST['employee_id'] ?? 0);
        $pMonth  = (int)($_POST['period_month'] ?? $month);
        $pYear   = (int)($_POST['period_year']  ?? $year);
        $gross   = (float)($_POST['gross_salary'] ?? 0);
        $basic   = (float)($_POST['basic_salary'] ?? $gross);
        $otHrs   = (float)($_POST['overtime_hours'] ?? 0);
        $otAmt   = (float)($_POST['overtime_amount'] ?? 0);
        $tax     = (float)($_POST['tax_amount'] ?? 0);
        $uifEmp  = (float)($_POST['uif_employee'] ?? 0);
        $uifEmr  = (float)($_POST['uif_employer'] ?? 0);
        $othDed  = (float)($_POST['other_deductions'] ?? 0);
        $totalDed= $tax + $uifEmp + $othDed;
        $net     = $gross - $totalDed;
        $notes   = trim($_POST['notes'] ?? '');
        $editId  = (int)($_POST['edit_id'] ?? 0);

        if (!$empId) {
            $error = 'Select an employee.';
        } elseif ($gross <= 0) {
            $error = 'Gross salary must be greater than 0.';
        } else {
            if ($editId) {
                // KOM-016: the update branch had no status guard at all (a
                // finalized or already-sent payslip could be silently
                // rewritten after the fact) and never recorded an audit
                // entry, unlike the create branch just below. Both fixed
                // together — block edits once the payslip has left draft,
                // and log the change when it's still allowed.
                $curStmt = db()->prepare("SELECT status, gross_salary, basic_salary, net_salary FROM payslips WHERE id=? AND employee_id=?");
                $curStmt->execute([$editId, $empId]);
                $current = $curStmt->fetch(PDO::FETCH_ASSOC);

                if (!$current) {
                    $error = 'Payslip not found.';
                } elseif (in_array($current['status'], ['finalized', 'sent'], true)) {
                    $error = 'This payslip has been ' . $current['status'] . ' and can no longer be edited.';
                } else {
                    db()->prepare("UPDATE payslips SET gross_salary=?,basic_salary=?,net_salary=?,total_deductions=?,
                        tax_amount=?,uif_employee=?,uif_employer=?,other_deductions=?,
                        overtime_hours=?,overtime_amount=?,notes=?
                        WHERE id=? AND employee_id=?")->execute([
                        $gross,$basic,$net,$totalDed,$tax,$uifEmp,$uifEmr,$othDed,
                        $otHrs,$otAmt,$notes,$editId,$empId
                    ]);
                    auditLog('payslips', 'update', $editId,
                        json_encode(['gross_salary' => $current['gross_salary'], 'basic_salary' => $current['basic_salary'], 'net_salary' => $current['net_salary']]),
                        json_encode(['gross_salary' => $gross, 'basic_salary' => $basic, 'net_salary' => $net]),
                        "Updated draft payslip for emp {$empId}");
                    $success = 'Payslip updated.';
                }
            } else {
                db()->prepare("INSERT INTO payslips (employee_id,period_month,period_year,gross_salary,basic_salary,
                    net_salary,total_deductions,tax_amount,uif_employee,uif_employer,other_deductions,
                    overtime_hours,overtime_amount,notes,status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'draft')")->execute([
                    $empId,$pMonth,$pYear,$gross,$basic,$net,$totalDed,$tax,$uifEmp,$uifEmr,
                    $othDed,$otHrs,$otAmt,$notes
                ]);
                auditLog('payslips','create',(int)db()->lastInsertId(),null,null,"Created payslip {$pMonth}/{$pYear} for emp {$empId}");
                $success = 'Payslip created.';
            }
        }
    }
}

// Edit mode
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editSlip = null;
if ($editId) {
    $stmt = db()->prepare("SELECT * FROM payslips WHERE id=? LIMIT 1");
    $stmt->execute([$editId]);
    $editSlip = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editSlip) { $month=$editSlip['period_month']; $year=$editSlip['period_year']; }
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$filterDept   = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;

$where  = 'WHERE ps.period_month=? AND ps.period_year=?';
$params = [$month, $year];
if ($filterStatus) { $where .= ' AND ps.status=?'; $params[] = $filterStatus; }
if ($filterDept)   { $where .= ' AND e.department_id=?'; $params[] = $filterDept; }

$stmt = db()->prepare("SELECT ps.*, e.first_name, e.last_name, e.employee_number, d.name as dept_name
    FROM payslips ps
    JOIN employees e ON ps.employee_id=e.id
    LEFT JOIN departments d ON e.department_id=d.id
    $where ORDER BY e.last_name");
$stmt->execute($params);
$slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$employees  = db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as label
    FROM employees WHERE status='active' ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);
$departments = db()->query("SELECT id, name FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$showForm = isset($_GET['new']) || $editId;

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper">
<div class="content-header">
    <div>
        <h1 class="page-title">Payslip Management</h1>
        <p class="page-sub">Create, edit and manage employee payslips</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="<?= APP_URL ?>/modules/payroll/index.php" class="btn btn-secondary btn-sm">← Dashboard</a>
        <?php if (!$showForm): ?>
        <a href="?new=1&month=<?=$month?>&year=<?=$year?>" class="btn btn-primary btn-sm">+ New Payslip</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if ($showForm): ?>
<!-- Form -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title"><?= $editSlip ? 'Edit Payslip' : 'New Payslip' ?></span>
        <a href="?" class="btn btn-ghost btn-sm">Cancel</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <?php if ($editSlip): ?>
            <input type="hidden" name="edit_id" value="<?= $editSlip['id'] ?>">
            <?php endif; ?>
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-control" required <?= $editSlip?'disabled':'' ?>>
                        <option value="">— Select Employee —</option>
                        <?php foreach($employees as $e): ?>
                        <option value="<?=$e['id']?>" <?=($editSlip&&$editSlip['employee_id']==$e['id'])?'selected':''?>>
                            <?= htmlspecialchars($e['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($editSlip): ?><input type="hidden" name="employee_id" value="<?=$editSlip['employee_id']?>"><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Period Month</label>
                    <select name="period_month" class="form-control">
                        <?php for($m=1;$m<=12;$m++): ?>
                        <option value="<?=$m?>" <?=($editSlip?$editSlip['period_month']:$month)===$m?'selected':''?>><?=$monthNames[$m]?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Period Year</label>
                    <input type="number" name="period_year" class="form-control" min="2020" max="2030"
                           value="<?= $editSlip?$editSlip['period_year']:$year ?>">
                </div>
            </div>
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label">Basic Salary (<?= HRMS_CURRENCY_CODE ?>)</label>
                    <input type="number" name="basic_salary" class="form-control" step="0.01" min="0"
                           value="<?= $editSlip?$editSlip['basic_salary']:'' ?>" id="basicSalary">
                </div>
                <div class="form-group">
                    <label class="form-label">Overtime Hours</label>
                    <input type="number" name="overtime_hours" class="form-control" step="0.5" min="0"
                           value="<?= $editSlip?$editSlip['overtime_hours']:0 ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Overtime Amount (<?= HRMS_CURRENCY_CODE ?>)</label>
                    <input type="number" name="overtime_amount" class="form-control" step="0.01" min="0"
                           value="<?= $editSlip?$editSlip['overtime_amount']:0 ?>" id="otAmt">
                </div>
            </div>
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label">Gross Salary (<?= HRMS_CURRENCY_CODE ?>) <span class="text-danger">*</span></label>
                    <input type="number" name="gross_salary" class="form-control" step="0.01" min="0" required
                           value="<?= $editSlip?$editSlip['gross_salary']:'' ?>" id="grossSalary">
                    <small class="form-text text-muted">Auto-calculated or enter manually</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Income Tax / PAYE (<?= HRMS_CURRENCY_CODE ?>)</label>
                    <input type="number" name="tax_amount" class="form-control" step="0.01" min="0"
                           value="<?= $editSlip?$editSlip['tax_amount']:0 ?>" id="taxAmt">
                </div>
                <div class="form-group">
                    <label class="form-label">Employee Contribution (<?= HRMS_CURRENCY_CODE ?>)</label>
                    <input type="number" name="uif_employee" class="form-control" step="0.01" min="0"
                           value="<?= $editSlip?$editSlip['uif_employee']:0 ?>" id="uifEmp">
                </div>
            </div>
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label">Employer Contribution (<?= HRMS_CURRENCY_CODE ?>)</label>
                    <input type="number" name="uif_employer" class="form-control" step="0.01" min="0"
                           value="<?= $editSlip?$editSlip['uif_employer']:0 ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Other Deductions (<?= HRMS_CURRENCY_CODE ?>)</label>
                    <input type="number" name="other_deductions" class="form-control" step="0.01" min="0"
                           value="<?= $editSlip?$editSlip['other_deductions']:0 ?>" id="othDed">
                </div>
                <div class="form-group">
                    <label class="form-label">Net Pay (<?= HRMS_CURRENCY_CODE ?>)</label>
                    <input type="number" name="net_display" class="form-control" step="0.01" readonly
                           id="netPay" style="background:var(--bg);font-weight:700">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= $editSlip?e($editSlip['notes']??''):'' ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <?= $editSlip ? 'Update Payslip' : 'Create Payslip' ?>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card-filters">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <select name="month" class="form-control form-control-sm">
            <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?=$m?>" <?=$m===$month?'selected':''?>><?=$monthNames[$m]?></option>
            <?php endfor; ?>
        </select>
        <select name="year" class="form-control form-control-sm">
            <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
            <option value="<?=$y?>" <?=$y===$year?'selected':''?>><?=$y?></option>
            <?php endfor; ?>
        </select>
        <select name="status" class="form-control form-control-sm">
            <option value="">All Status</option>
            <option value="draft" <?=$filterStatus==='draft'?'selected':''?>>Draft</option>
            <option value="finalized" <?=$filterStatus==='finalized'?'selected':''?>>Finalized</option>
            <option value="sent" <?=$filterStatus==='sent'?'selected':''?>>Sent</option>
        </select>
        <select name="dept" class="form-control form-control-sm">
            <option value="">All Departments</option>
            <?php foreach($departments as $d): ?>
            <option value="<?=$d['id']?>" <?=$filterDept===$d['id']?'selected':''?>><?= e($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="?" class="btn btn-secondary btn-sm">Reset</a>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Payslips — <?= $monthNames[$month] . ' ' . $year ?> (<?= count($slips) ?>)</span>
    </div>
    <?php if ($slips): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Gross</th>
                    <th>Tax</th>
                    <th>UIF</th>
                    <th>Other Ded.</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($slips as $ps):
                    $sc=['draft'=>'badge-secondary','finalized'=>'badge-info','sent'=>'badge-success'];
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600"><?= e($ps['first_name'].' '.$ps['last_name']) ?></div>
                        <div style="font-size:0.7rem;color:var(--text-muted)"><?= e($ps['employee_number']) ?></div>
                    </td>
                    <td><?= e($ps['dept_name']??'—') ?></td>
                    <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($ps['gross_salary'],2) ?></td>
                    <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($ps['tax_amount']??0,2) ?></td>
                    <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format(($ps['uif_employee']??0)+($ps['uif_employer']??0),2) ?></td>
                    <td><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($ps['other_deductions']??0,2) ?></td>
                    <td><strong><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($ps['net_salary'],2) ?></strong></td>
                    <td><span class="badge <?= $sc[$ps['status']]??'badge-secondary' ?>"><?= ucfirst($ps['status']) ?></span></td>
                    <td>
                        <a href="?edit=<?=$ps['id']?>" class="btn btn-ghost btn-sm">Edit</a>
                        <?php if ($ps['status']==='draft'): ?>
                        <form method="POST" action="<?= APP_URL ?>/modules/payroll/payslip_finalize.php" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="id" value="<?= $ps['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" onclick="return confirm('Finalize this payslip?')">Finalize</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div>No payslips for <?= $monthNames[$month] . ' ' . $year ?></div>
        <a href="?new=1&month=<?=$month?>&year=<?=$year?>" class="btn btn-primary btn-sm" style="margin-top:8px">Create First Payslip</a>
    </div>
    <?php endif; ?>
</div>
</div>

<script>
function calcNet() {
    const gross = parseFloat(document.getElementById('grossSalary')?.value)||0;
    const tax   = parseFloat(document.getElementById('taxAmt')?.value)||0;
    const uif   = parseFloat(document.getElementById('uifEmp')?.value)||0;
    const oth   = parseFloat(document.getElementById('othDed')?.value)||0;
    const net   = gross - tax - uif - oth;
    const np = document.getElementById('netPay');
    if (np) np.value = net.toFixed(2);
}
function calcGross() {
    const basic = parseFloat(document.getElementById('basicSalary')?.value)||0;
    const ot    = parseFloat(document.getElementById('otAmt')?.value)||0;
    const g = document.getElementById('grossSalary');
    if (g && !g.value) g.value = (basic+ot).toFixed(2);
    calcNet();
}
['basicSalary','otAmt','grossSalary','taxAmt','uifEmp','othDed'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', id==='grossSalary'?calcNet:calcGross);
});
calcNet();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

