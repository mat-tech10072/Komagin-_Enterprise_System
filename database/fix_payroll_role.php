<?php
// One-time fix: adds payroll_officer to role ENUM and corrects the payroll user row.
// Delete this file after running.
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

$results = [];

try {
    db()->exec("ALTER TABLE `users`
        MODIFY COLUMN `role` ENUM('super_admin','hr_manager','hr_officer','supervisor','employee','finance_viewer','payroll_officer')
        NOT NULL DEFAULT 'employee'");
    $results[] = ['ok', 'ENUM updated — payroll_officer added to users.role'];
} catch (Exception $e) {
    $results[] = ['err', 'ENUM alter failed: ' . $e->getMessage()];
}

try {
    $n = db()->exec("UPDATE `users` SET `role`='payroll_officer' WHERE `username`='payroll' AND (`role`='' OR `role`='employee')");
    $results[] = ['ok', "Role corrected for payroll user ($n row(s) updated)"];
} catch (Exception $e) {
    $results[] = ['err', 'Role update failed: ' . $e->getMessage()];
}

// Verify
$stmt = db()->query("SELECT username, role, is_active FROM users WHERE username IN ('superadmin','payroll')");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html><head><title>Fix Payroll Role</title>
<style>body{font-family:sans-serif;padding:32px;background:#F8FAFC} .ok{color:#16A34A} .err{color:#DC2626}
table{border-collapse:collapse;margin-top:16px} td,th{padding:8px 14px;border:1px solid #E2E8F0;font-size:14px}
</style></head><body>
<h2>Payroll Role Fix</h2>
<?php foreach($results as [$type,$msg]): ?>
<p class="<?=$type?>"><?= $type==='ok'?'✅':'❌' ?> <?= htmlspecialchars($msg) ?></p>
<?php endforeach; ?>
<h3>Users verification</h3>
<table><tr><th>Username</th><th>Role</th><th>Active</th></tr>
<?php foreach($users as $u): ?>
<tr><td><?=$u['username']?></td><td><strong><?=$u['role']?></strong></td><td><?=$u['is_active']?'Yes':'No'?></td></tr>
<?php endforeach; ?>
</table>
<p style="margin-top:24px;color:#64748B;font-size:13px">Delete this file after verifying the results.</p>
</body></html>
