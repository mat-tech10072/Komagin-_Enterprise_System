<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';
epRequireLogin();

$emp   = epCurrentEmployee();
$empId = $emp['id'];

// View a single payslip
$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$payslip = null;
$psItems = [];

if ($viewId) {
    $stmt = db()->prepare("SELECT * FROM payslips WHERE id=? AND employee_id=? LIMIT 1");
    $stmt->execute([$viewId, $empId]);
    $payslip = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($payslip) {
        $itemStmt = db()->prepare("SELECT * FROM payslip_items WHERE payslip_id=? ORDER BY item_type, sort_order");
        $itemStmt->execute([$viewId]);
        $psItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// All payslips list
$allStmt = db()->prepare("SELECT id, period_month, period_year, gross_salary, net_salary, total_deductions, status
    FROM payslips WHERE employee_id=? ORDER BY period_year DESC, period_month DESC");
$allStmt->execute([$empId]);
$allPayslips = $allStmt->fetchAll(PDO::FETCH_ASSOC);

$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

epLayoutStart($payslip ? 'Pay Slip Detail' : 'Pay Slips', 'payslips');
?>

<?php if ($payslip): ?>
<!-- Single Payslip View -->
<div class="page-header">
    <div>
        <div class="page-title">Pay Slip — <?= $monthNames[$payslip['period_month']] . ' ' . $payslip['period_year'] ?></div>
        <div class="page-sub">Payslip #<?= str_pad($payslip['id'],6,'0',STR_PAD_LEFT) ?></div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="<?= EP_URL ?>/payslips.php" class="btn btn-secondary btn-sm">← Back</a>
        <button onclick="window.print()" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print
        </button>
        <a href="<?= EP_URL ?>/payslip-download.php?id=<?= $viewId ?>" target="_blank" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download PDF
        </a>
    </div>
</div>

<div class="card" style="max-width:700px">
    <div style="padding:20px 24px;background:#0F172A;color:#fff;border-radius:8px 8px 0 0">
        <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div style="font-size:1.1rem;font-weight:800">KOMAGIN</div>
                <div style="font-size:0.72rem;opacity:.6">Payslip — <?= $monthNames[$payslip['period_month']] . ' ' . $payslip['period_year'] ?></div>
            </div>
            <?php $statusColors=['draft'=>'badge-secondary','finalized'=>'badge-info','sent'=>'badge-success']; ?>
            <span class="badge <?= $statusColors[$payslip['status']] ?? 'badge-secondary' ?>">
                <?= ucfirst($payslip['status']) ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border)">
            <?php
            $headerInfo = [
                'Employee'   => htmlspecialchars(($emp['first_name']??'').' '.($emp['last_name']??'')),
                'Employee No.' => htmlspecialchars($emp['employee_number']??''),
                'Department' => htmlspecialchars($emp['dept_name']??'—'),
                'Position'   => htmlspecialchars($emp['position_title']??'—'),
            ];
            foreach ($headerInfo as $k=>$v):
            ?>
            <div>
                <div style="font-size:0.65rem;text-transform:uppercase;font-weight:600;color:var(--text-muted)"><?= $k ?></div>
                <div style="font-size:0.82rem;font-weight:500"><?= $v ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Earnings -->
        <div style="margin-bottom:16px">
            <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:8px">Earnings</div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                <span style="font-size:0.82rem">Basic Salary</span>
                <span style="font-size:0.82rem;font-weight:600"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($payslip['basic_salary']??0, 2) ?></span>
            </div>
            <?php if ($payslip['overtime_amount'] > 0): ?>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                <span style="font-size:0.82rem">Overtime (<?= $payslip['overtime_hours'] ?> hrs)</span>
                <span style="font-size:0.82rem;font-weight:600"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($payslip['overtime_amount'], 2) ?></span>
            </div>
            <?php endif; ?>
            <?php foreach ($psItems as $item): if ($item['item_type']!=='earning') continue; ?>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                <span style="font-size:0.82rem"><?= htmlspecialchars($item['description']) ?></span>
                <span style="font-size:0.82rem;font-weight:600"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($item['amount'], 2) ?></span>
            </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:10px 0;font-weight:700">
                <span>Gross Pay</span>
                <span><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($payslip['gross_salary']??0, 2) ?></span>
            </div>
        </div>

        <!-- Deductions -->
        <div style="margin-bottom:16px">
            <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:8px">Deductions</div>
            <?php if ($payslip['tax_amount'] > 0): ?>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                <span style="font-size:0.82rem">Income Tax (PAYE)</span>
                <span style="font-size:0.82rem;color:var(--danger)">— R <?= number_format($payslip['tax_amount'], 2) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($payslip['uif_employee'] > 0): ?>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                <span style="font-size:0.82rem">UIF (Employee)</span>
                <span style="font-size:0.82rem;color:var(--danger)">— R <?= number_format($payslip['uif_employee'], 2) ?></span>
            </div>
            <?php endif; ?>
            <?php foreach ($psItems as $item): if ($item['item_type']!=='deduction') continue; ?>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                <span style="font-size:0.82rem"><?= htmlspecialchars($item['description']) ?></span>
                <span style="font-size:0.82rem;color:var(--danger)">— R <?= number_format($item['amount'], 2) ?></span>
            </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:10px 0;font-weight:700;color:var(--danger)">
                <span>Total Deductions</span>
                <span>— R <?= number_format($payslip['total_deductions']??0, 2) ?></span>
            </div>
        </div>

        <!-- Net Pay -->
        <div style="background:var(--bg);border-radius:8px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:1rem;font-weight:700">NET PAY</span>
            <span style="font-size:1.4rem;font-weight:800;color:var(--success)"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($payslip['net_salary']??0, 2) ?></span>
        </div>

        <?php if ($payslip['notes']): ?>
        <div class="alert alert-info" style="margin-top:16px">
            <strong>Note:</strong> <?= htmlspecialchars($payslip['notes']) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Payslips List -->
<div class="page-header">
    <div>
        <div class="page-title">Pay Slips &amp; Pay Records</div>
        <div class="page-sub">Your complete payroll history</div>
    </div>
    <a href="<?= EP_URL ?>/hub.php?type=payslip_query&new=1" class="btn btn-ghost btn-sm">
        Query a Payslip
    </a>
</div>

<?php if ($allPayslips): ?>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
    <?php foreach ($allPayslips as $ps): ?>
    <div style="position:relative">
        <a href="?id=<?= $ps['id'] ?>" style="text-decoration:none;color:inherit;display:block">
            <div class="payslip-card">
                <div style="padding:14px 16px;background:<?= $ps['status']==='sent' ? 'var(--primary)' : 'var(--bg)' ?>;color:<?= $ps['status']==='sent' ? '#fff' : 'var(--text)' ?>">
                    <div class="payslip-period"><?= $monthNames[$ps['period_month']] . ' ' . $ps['period_year'] ?></div>
                    <?php $sc=['draft'=>'badge-secondary','finalized'=>'badge-info','sent'=>'badge-success']; ?>
                    <span class="badge <?= $sc[$ps['status']] ?? 'badge-secondary' ?>" style="margin-top:4px"><?= ucfirst($ps['status']) ?></span>
                </div>
                <div style="padding:14px 16px">
                    <div style="font-size:0.67rem;color:var(--text-muted);margin-bottom:2px">Gross</div>
                    <div style="font-size:0.82rem;font-weight:600"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($ps['gross_salary'],2) ?></div>
                    <div style="font-size:0.67rem;color:var(--text-muted);margin-top:8px;margin-bottom:2px">Net Pay</div>
                    <div class="payslip-net"><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($ps['net_salary'],2) ?></div>
                </div>
            </div>
        </a>
        <!-- Download icon — positioned over card, does not trigger the card link -->
        <a href="<?= EP_URL ?>/payslip-download.php?id=<?= $ps['id'] ?>"
           target="_blank"
           title="Download PDF"
           onclick="event.stopPropagation()"
           style="position:absolute;top:10px;right:10px;z-index:2;
                  display:flex;align-items:center;justify-content:center;
                  width:28px;height:28px;border-radius:6px;
                  background:rgba(0,0,0,0.18);color:#fff;
                  text-decoration:none;transition:background .15s">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">📄</div>
            <div class="empty-state-title">No payslips available</div>
            <div class="empty-state-desc">Your payslips will appear here once payroll has been processed.</div>
        </div>
    </div>
<?php endif; ?>
<?php endif; ?>

<?php epLayoutEnd(); ?>

