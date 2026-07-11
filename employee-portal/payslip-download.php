<?php
/**
 * Employee Portal — Payslip Download (Print-to-PDF)
 * Opens a clean standalone page and auto-triggers the print dialog.
 * Employees choose "Save as PDF" in their print dialog.
 */
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
epRequireLogin();

$emp   = epCurrentEmployee();
$empId = (int)($emp['id'] ?? 0);
$psId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$psId) {
    header('Location: ' . EP_URL . '/payslips.php');
    exit;
}

// Fetch payslip — must belong to this employee
$stmt = db()->prepare(
    "SELECT ps.*,
            e.first_name, e.last_name, e.employee_number,
            d.name  AS dept_name,
            p.title AS position_title
     FROM payslips ps
     JOIN employees  e ON e.id = ps.employee_id
     LEFT JOIN departments d ON d.id = e.department_id
     LEFT JOIN positions   p ON p.id = e.position_id
     WHERE ps.id = ? AND ps.employee_id = ?
     LIMIT 1"
);
$stmt->execute([$psId, $empId]);
$ps = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ps) {
    header('Location: ' . EP_URL . '/payslips.php');
    exit;
}

// Payslip line items
$iStmt = db()->prepare(
    "SELECT * FROM payslip_items WHERE payslip_id = ? ORDER BY item_type, sort_order"
);
$iStmt->execute([$psId]);
$items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

// Company settings (logo, name, address)
$settings = getCompanySettings();

$months = ['','January','February','March','April','May','June',
           'July','August','September','October','November','December'];
$period = $months[(int)$ps['period_month']] . ' ' . $ps['period_year'];
$empName = trim(($ps['first_name'] ?? '') . ' ' . ($ps['last_name'] ?? ''));
$psRef   = 'PS-' . str_pad($psId, 6, '0', STR_PAD_LEFT);
$cur     = CURRENCY_SYMBOL;

$earnings   = array_filter($items, fn($i) => $i['item_type'] === 'earning');
$deductions = array_filter($items, fn($i) => $i['item_type'] === 'deduction');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payslip — <?= htmlspecialchars($period) ?> — <?= htmlspecialchars($empName) ?></title>
<style>
/* ── Reset ───────────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

/* ── Screen styles ───────────────────────────────────────────── */
body{
    font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;
    font-size:13px;
    color:#111827;
    background:#6B7280;
    display:flex;
    flex-direction:column;
    align-items:center;
    min-height:100vh;
    padding:32px 16px;
    gap:16px;
}

.toolbar{
    display:flex;
    gap:10px;
    align-items:center;
    background:#fff;
    border-radius:10px;
    padding:10px 16px;
    box-shadow:0 2px 8px rgba(0,0,0,.15);
    width:100%;
    max-width:720px;
    flex-wrap:wrap;
}
.toolbar strong{font-size:13px;color:#111827;flex:1}
.btn-dl{
    display:inline-flex;align-items:center;gap:6px;
    padding:7px 14px;border-radius:6px;border:none;cursor:pointer;
    font-size:12px;font-weight:600;text-decoration:none;
}
.btn-dl--primary{background:#0F172A;color:#fff;}
.btn-dl--primary:hover{background:#1e293b}
.btn-dl--ghost{background:transparent;color:#374151;border:1px solid #D1D5DB}
.btn-dl--ghost:hover{background:#F9FAFB}
.btn-dl svg{flex-shrink:0}

/* ── Paper ───────────────────────────────────────────────────── */
.paper{
    background:#fff;
    width:100%;
    max-width:720px;
    border-radius:10px;
    box-shadow:0 4px 24px rgba(0,0,0,.18);
    overflow:hidden;
}

/* ── Payslip header ─────────────────────────────────────────── */
.ps-head{
    background:#0F172A;
    color:#fff;
    padding:24px 28px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
}
.ps-head-company{font-size:1.15rem;font-weight:800;letter-spacing:-.3px}
.ps-head-sub{font-size:.72rem;opacity:.55;margin-top:3px}
.ps-head-right{text-align:right}
.ps-head-period{font-size:.9rem;font-weight:700}
.ps-head-ref{font-size:.68rem;opacity:.5;margin-top:3px}
.status-chip{
    display:inline-block;padding:2px 8px;border-radius:20px;
    font-size:.65rem;font-weight:700;text-transform:uppercase;
    margin-top:6px;
}
.status-sent    {background:#15803D;color:#fff}
.status-finalized{background:#1D4ED8;color:#fff}
.status-draft   {background:rgba(255,255,255,.2);color:#fff}

/* ── Employee info row ──────────────────────────────────────── */
.ps-employee{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:4px 16px;
    padding:18px 28px;
    border-bottom:1px solid #E5E7EB;
    background:#F9FAFB;
}
.ps-field-label{font-size:.63rem;text-transform:uppercase;font-weight:600;color:#9CA3AF;margin-bottom:2px}
.ps-field-value{font-size:.82rem;font-weight:500;color:#111827}

/* ── Body ────────────────────────────────────────────────────── */
.ps-body{padding:20px 28px}

/* ── Section heading ────────────────────────────────────────── */
.ps-section-label{
    font-size:.65rem;font-weight:700;text-transform:uppercase;
    letter-spacing:.07em;color:#9CA3AF;
    padding-bottom:6px;border-bottom:2px solid #E5E7EB;
    margin-bottom:8px;margin-top:20px;
}
.ps-section-label:first-child{margin-top:0}

/* ── Line items ─────────────────────────────────────────────── */
.ps-row{
    display:flex;justify-content:space-between;align-items:center;
    padding:7px 0;border-bottom:1px solid #F3F4F6;
    font-size:.82rem;
}
.ps-row:last-child{border-bottom:none}
.ps-row-desc{color:#374151}
.ps-row-amount{font-weight:600;color:#111827}
.ps-row-amount.deduction{color:#DC2626}

/* ── Subtotal ───────────────────────────────────────────────── */
.ps-subtotal{
    display:flex;justify-content:space-between;align-items:center;
    padding:10px 0;font-weight:700;font-size:.9rem;
    border-top:2px solid #E5E7EB;margin-top:4px;
}
.ps-subtotal.deduction-total{color:#DC2626}

/* ── Net pay ─────────────────────────────────────────────────── */
.ps-netpay{
    margin-top:20px;
    background:#0F172A;
    border-radius:8px;
    padding:18px 24px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.ps-netpay-label{color:rgba(255,255,255,.7);font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em}
.ps-netpay-amount{color:#4ADE80;font-size:1.45rem;font-weight:800}

/* ── Notes ───────────────────────────────────────────────────── */
.ps-notes{
    margin-top:14px;background:#FFFBEB;border:1px solid #FEF08A;
    border-radius:6px;padding:10px 14px;font-size:.78rem;color:#713F12;
}

/* ── Footer ─────────────────────────────────────────────────── */
.ps-footer{
    padding:14px 28px;border-top:1px solid #E5E7EB;
    background:#F9FAFB;display:flex;justify-content:space-between;
    align-items:center;font-size:.68rem;color:#9CA3AF;
}

/* ── Print styles ────────────────────────────────────────────── */
@media print{
    @page{size:A4 portrait;margin:12mm 14mm}

    body{
        background:#fff !important;
        padding:0 !important;
        display:block !important;
    }

    .toolbar{display:none !important}

    .paper{
        box-shadow:none !important;
        border-radius:0 !important;
        max-width:none !important;
        width:100% !important;
    }

    .ps-head,.ps-employee,.ps-body,.ps-footer{break-inside:avoid}
    .ps-row{break-inside:avoid}
}
</style>
</head>
<body>

<!-- ── Toolbar (hidden on print) ───────────────────────────── -->
<div class="toolbar">
    <strong>Payslip — <?= htmlspecialchars($period) ?></strong>
    <button onclick="window.print()" class="btn-dl btn-dl--primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download PDF
    </button>
    <a href="<?= EP_URL ?>/payslips.php?id=<?= $psId ?>" class="btn-dl btn-dl--ghost">
        ← Back
    </a>
</div>

<!-- ── Payslip paper ─────────────────────────────────────────── -->
<div class="paper">

    <!-- Header -->
    <div class="ps-head">
        <div>
            <?php if (!empty($settings['company_logo'])): ?>
                <img src="<?= htmlspecialchars(APP_URL . '/' . $settings['company_logo']) ?>"
                     alt="Logo" style="height:36px;margin-bottom:6px;display:block;filter:brightness(0) invert(1)">
            <?php endif; ?>
            <div class="ps-head-company"><?= htmlspecialchars($settings['company_name'] ?? 'KOMAGIN') ?></div>
            <div class="ps-head-sub"><?= htmlspecialchars($settings['company_address'] ?? '') ?></div>
        </div>
        <div class="ps-head-right">
            <div class="ps-head-period"><?= htmlspecialchars($period) ?></div>
            <div class="ps-head-ref"><?= htmlspecialchars($psRef) ?></div>
            <?php
            $chipClass = match($ps['status']) {
                'sent'      => 'status-sent',
                'finalized' => 'status-finalized',
                default     => 'status-draft',
            };
            ?>
            <div><span class="status-chip <?= $chipClass ?>"><?= ucfirst($ps['status']) ?></span></div>
        </div>
    </div>

    <!-- Employee info -->
    <div class="ps-employee">
        <?php
        $fields = [
            'Employee'    => $empName,
            'Employee No' => $ps['employee_number'] ?? '',
            'Department'  => $ps['dept_name']       ?? '—',
            'Position'    => $ps['position_title']  ?? '—',
        ];
        foreach ($fields as $label => $value):
        ?>
        <div>
            <div class="ps-field-label"><?= $label ?></div>
            <div class="ps-field-value"><?= htmlspecialchars($value) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Body -->
    <div class="ps-body">

        <!-- Earnings -->
        <div class="ps-section-label">Earnings</div>

        <div class="ps-row">
            <span class="ps-row-desc">Basic Salary</span>
            <span class="ps-row-amount"><?= $cur ?> <?= number_format((float)($ps['basic_salary'] ?? 0), 2) ?></span>
        </div>

        <?php if (!empty($ps['overtime_amount']) && $ps['overtime_amount'] > 0): ?>
        <div class="ps-row">
            <span class="ps-row-desc">Overtime (<?= (float)$ps['overtime_hours'] ?> hrs)</span>
            <span class="ps-row-amount"><?= $cur ?> <?= number_format((float)$ps['overtime_amount'], 2) ?></span>
        </div>
        <?php endif; ?>

        <?php foreach ($earnings as $item): ?>
        <div class="ps-row">
            <span class="ps-row-desc"><?= htmlspecialchars($item['description']) ?></span>
            <span class="ps-row-amount"><?= $cur ?> <?= number_format((float)$item['amount'], 2) ?></span>
        </div>
        <?php endforeach; ?>

        <div class="ps-subtotal">
            <span>Gross Pay</span>
            <span><?= $cur ?> <?= number_format((float)($ps['gross_salary'] ?? 0), 2) ?></span>
        </div>

        <!-- Deductions -->
        <div class="ps-section-label">Deductions</div>

        <?php if (!empty($ps['tax_amount']) && $ps['tax_amount'] > 0): ?>
        <div class="ps-row">
            <span class="ps-row-desc">Income Tax (PAYE)</span>
            <span class="ps-row-amount deduction">— <?= $cur ?> <?= number_format((float)$ps['tax_amount'], 2) ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($ps['uif_employee']) && $ps['uif_employee'] > 0): ?>
        <div class="ps-row">
            <span class="ps-row-desc">UIF (Employee)</span>
            <span class="ps-row-amount deduction">— <?= $cur ?> <?= number_format((float)$ps['uif_employee'], 2) ?></span>
        </div>
        <?php endif; ?>

        <?php foreach ($deductions as $item): ?>
        <div class="ps-row">
            <span class="ps-row-desc"><?= htmlspecialchars($item['description']) ?></span>
            <span class="ps-row-amount deduction">— <?= $cur ?> <?= number_format((float)$item['amount'], 2) ?></span>
        </div>
        <?php endforeach; ?>

        <div class="ps-subtotal deduction-total">
            <span>Total Deductions</span>
            <span>— <?= $cur ?> <?= number_format((float)($ps['total_deductions'] ?? 0), 2) ?></span>
        </div>

        <!-- Net Pay -->
        <div class="ps-netpay">
            <div class="ps-netpay-label">NET PAY</div>
            <div class="ps-netpay-amount"><?= $cur ?> <?= number_format((float)($ps['net_salary'] ?? 0), 2) ?></div>
        </div>

        <?php if (!empty($ps['notes'])): ?>
        <div class="ps-notes"><strong>Note:</strong> <?= htmlspecialchars($ps['notes']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="ps-footer">
        <span>Generated <?= date('d M Y, H:i') ?></span>
        <span><?= htmlspecialchars($settings['company_name'] ?? 'Komagin') ?> — Confidential</span>
        <span><?= htmlspecialchars($psRef) ?></span>
    </div>
</div>

<script>
// Auto-open print dialog when loaded via the Download button
if (window.opener || document.referrer.includes('payslips.php')) {
    window.onload = () => setTimeout(() => window.print(), 400);
}
</script>
</body>
</html>
