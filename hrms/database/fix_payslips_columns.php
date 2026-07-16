<?php
// One-time fix: adds missing columns to payslips table.
// Delete this file after running.
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

$columns = [
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `basic_salary`       DECIMAL(12,2) DEFAULT 0    AFTER `gross_salary`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `total_deductions`   DECIMAL(12,2) DEFAULT 0    AFTER `net_salary`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `tax_amount`         DECIMAL(12,2) DEFAULT 0    AFTER `total_deductions`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `uif_employee`       DECIMAL(12,2) DEFAULT 0    AFTER `tax_amount`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `uif_employer`       DECIMAL(12,2) DEFAULT 0    AFTER `uif_employee`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `other_deductions`   DECIMAL(12,2) DEFAULT 0    AFTER `uif_employer`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `total_employer_cost` DECIMAL(12,2) DEFAULT 0   AFTER `other_deductions`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `overtime_hours`     DECIMAL(6,2)  DEFAULT 0    AFTER `total_employer_cost`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `overtime_amount`    DECIMAL(12,2) DEFAULT 0    AFTER `overtime_hours`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `leave_days_taken`   DECIMAL(5,1)  DEFAULT 0    AFTER `overtime_amount`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `payroll_run_id`     INT UNSIGNED  NULL         AFTER `period_year`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `notes`              TEXT NULL                  AFTER `leave_days_taken`",
    "ALTER TABLE `payslips` ADD COLUMN IF NOT EXISTS `status`             ENUM('draft','finalized','sent') DEFAULT 'draft' AFTER `notes`",
];

$results = [];
foreach ($columns as $sql) {
    preg_match('/ADD COLUMN IF NOT EXISTS `(\w+)`/', $sql, $m);
    $colName = $m[1] ?? $sql;
    try {
        db()->exec($sql);
        $results[] = ['ok', "Column `$colName` — OK"];
    } catch (Exception $e) {
        $results[] = ['err', "Column `$colName` — " . $e->getMessage()];
    }
}

// Verify by listing current payslips columns
$cols = db()->query("SHOW COLUMNS FROM `payslips`")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html><head><title>Fix Payslips Columns</title>
<style>body{font-family:sans-serif;padding:32px;background:#F8FAFC;font-size:14px}
.ok{color:#16A34A}.err{color:#DC2626}
table{border-collapse:collapse;margin-top:12px}
td,th{padding:7px 14px;border:1px solid #E2E8F0}th{background:#F1F5F9}</style>
</head><body>
<h2>Payslips Column Fix</h2>
<?php foreach($results as [$type,$msg]): ?>
<p class="<?=$type?>"><?=$type==='ok'?'✅':'❌'?> <?=htmlspecialchars($msg)?></p>
<?php endforeach; ?>
<h3>Current payslips columns</h3>
<table><tr><th>Column</th><th>Type</th><th>Default</th></tr>
<?php foreach($cols as $c): ?>
<tr><td><?=$c['Field']?></td><td><?=$c['Type']?></td><td><?=$c['Default']??'NULL'?></td></tr>
<?php endforeach; ?>
</table>
<p style="margin-top:24px;color:#64748B">Delete this file after verifying.</p>
</body></html>
