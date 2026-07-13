<?php
require_once __DIR__ . '/sql_split.php';

/**
 * Phase 3 — Automated Clean-Install Verification Script
 *
 * Creates a genuinely empty test database, runs the exact same install
 * sequence database/install.php uses, then verifies structure and data
 * completeness. This is the CLI-runnable counterpart to install.php's
 * web form — used for Stage 3.8 of the Phase 3 charter and safe to
 * re-run any time to confirm the repository can still rebuild a working
 * system from nothing.
 *
 * Usage: php verify_clean_install.php [--keep]
 *   --keep   don't drop the test database when finished (for manual inspection)
 */

$testDb = 'komagin_hr_phase3_clean_test';
$keep = in_array('--keep', $argv ?? []);

$host = 'localhost'; $user = 'root'; $pass = '';

$results = ['pass' => 0, 'fail' => 0, 'log' => []];
function record(array &$results, bool $ok, string $msg): void {
    $results[$ok ? 'pass' : 'fail']++;
    $results['log'][] = ($ok ? "PASS" : "FAIL") . " | $msg";
    echo ($ok ? "PASS" : "FAIL") . " | $msg\n";
}

try {
    $root = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    fwrite(STDERR, "Cannot connect to MySQL: " . $e->getMessage() . "\n");
    exit(1);
}

echo "=== Stage 3.8: Clean Install Test ===\n";
echo "Test database: $testDb\n\n";

$root->exec("DROP DATABASE IF EXISTS `$testDb`");
$root->exec("CREATE DATABASE `$testDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$root->exec("USE `$testDb`");
record($results, true, "Empty test database created");

// Phase 6, Stage 6.1: kept in sync with database/install.php's own
// INSTALL_SEQUENCE — see that file's comment for why phase11/phase12 are
// correctly excluded (upgrade-path-only) and phase13 is included (the
// one migration file carrying fresh-install-needed seed data, the
// default work_calendar_settings row, that schema.sql intentionally
// doesn't include).
$sequence = [
    ['file' => 'schema.sql',                          'label' => 'Core database structure'],
    ['file' => 'seeds/001_baseline_admin.sql',         'label' => 'Default super_admin account'],
    ['file' => 'seeds/002_doc_categories.sql',         'label' => 'Document template categories'],
    ['file' => 'seeds/003_departments_positions.sql',  'label' => 'Departments & positions'],
    ['file' => 'phase1_permissions.sql',               'label' => 'Core permission matrix'],
    ['file' => 'phase5_branding_theme.sql',            'label' => 'Branding & email permissions'],
    ['file' => 'phase6_templates.sql',                 'label' => 'Document template library'],
    ['file' => 'phase8_temp_employees.sql',            'label' => 'Temporary employees module'],
    ['file' => 'phase9_consultants.sql',               'label' => 'Consultants module'],
    ['file' => 'phase10_authorization_framework.sql',  'label' => 'Activity Log & Approvals permissions'],
    ['file' => 'phase13_workflow_completeness_automation.sql', 'label' => 'Working-calendar default row, scheduler/password-reset/notification tables'],
];

foreach ($sequence as $entry) {
    $path = __DIR__ . '/' . $entry['file'];
    if (!file_exists($path)) { record($results, false, "{$entry['label']} — file not found"); continue; }
    $sql = file_get_contents($path);
    $lines = array_filter(explode("\n", $sql), function ($l) {
        $t = trim($l);
        return !(str_starts_with($t, 'CREATE DATABASE') || str_starts_with($t, 'USE '));
    });
    $sql = implode("\n", $lines);
    $statements = splitSqlStatements($sql);
    $count = 0;
    try {
        foreach ($statements as $stmt) { $root->query($stmt)->closeCursor(); $count++; }
        record($results, true, "{$entry['label']} ({$entry['file']}) — $count statements OK");
    } catch (PDOException $e) {
        record($results, false, "{$entry['label']} ({$entry['file']}) — FAILED at statement $count: " . $e->getMessage());
    }
}

echo "\n=== Structural Verification ===\n";

// 1. Table count matches live (67, including schema_migrations — was 60
// before Phase 6, Stage 6.1 added phase13's 7 tables to this sequence)
$tableCount = (int)$root->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$testDb'")->fetchColumn();
record($results, $tableCount === 67, "Table count is 67 (got $tableCount)");

// 2. Every table the live database has also exists here
$liveTables = file(__DIR__ . '/../docs/remediation/Database/fingerprints/live_table_list.txt', FILE_IGNORE_NEW_LINES) ?: [];
if ($liveTables) {
    $testTables = $root->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_diff($liveTables, $testTables);
    record($results, empty($missing), "Every live-DB table exists in the clean install" . ($missing ? (" — MISSING: " . implode(', ', $missing)) : ''));
}

// 3. Critical columns proven-used by application code
$criticalColumns = [
    'payslips' => ['basic_salary', 'total_deductions', 'payroll_run_id', 'status'],
    'users' => ['first_name', 'last_name', 'job_title', 'phone'],
    'role_permissions' => ['can_approve', 'can_export', 'can_publish', 'can_share'],
    'temp_employees' => ['rate_type', 'attendance_method'],
];
foreach ($criticalColumns as $table => $cols) {
    $actual = $root->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_diff($cols, $actual);
    record($results, empty($missing), "$table has all previously-missing columns" . ($missing ? (" — MISSING: " . implode(', ', $missing)) : ''));
}

// 4. Seed data sanity
$permCount = (int)$root->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
record($results, $permCount >= 90, "Permission matrix seeded ($permCount permissions, expect >= 90)");

$templateCount = (int)$root->query("SELECT COUNT(*) FROM doc_templates")->fetchColumn();
record($results, $templateCount === 47, "Document template library seeded (got $templateCount, expect 47)");

$catCount = (int)$root->query("SELECT COUNT(*) FROM doc_categories")->fetchColumn();
record($results, $catCount === 10, "Document categories seeded (got $catCount, expect 10)");

$deptCount = (int)$root->query("SELECT COUNT(*) FROM departments")->fetchColumn();
record($results, $deptCount === 11, "Departments seeded (got $deptCount, expect 11)");

$posCount = (int)$root->query("SELECT COUNT(*) FROM positions")->fetchColumn();
record($results, $posCount === 23, "Positions seeded (got $posCount, expect 23)");

$adminCount = (int)$root->query("SELECT COUNT(*) FROM users WHERE role='super_admin'")->fetchColumn();
record($results, $adminCount === 1, "Default super_admin account created (got $adminCount)");

$mustChange = (int)$root->query("SELECT must_change_password FROM users WHERE username='superadmin'")->fetchColumn();
record($results, $mustChange === 1, "Default super_admin is forced to change password on first login");

// 5. hr_officer typo fix present at the seed level (not just live-patched)
$typoCount = (int)$root->query("SELECT COUNT(*) FROM role_permissions WHERE role='hrofficer'")->fetchColumn();
record($results, $typoCount === 0, "No 'hrofficer' (typo) rows exist in a fresh install (got $typoCount)");

$hrOfficerTempEmp = (int)$root->query("
    SELECT COUNT(*) FROM role_permissions rp JOIN permissions p ON rp.permission_id=p.id
    WHERE rp.role='hr_officer' AND p.module='temp_employees'")->fetchColumn();
record($results, $hrOfficerTempEmp === 4, "hr_officer correctly granted temp_employees permissions on fresh install (got $hrOfficerTempEmp, expect 4)");

// 6. Foreign key integrity — no orphaned/broken constraints (would have failed at CREATE time, but confirm count)
$fkCount = (int)$root->query("
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA='$testDb' AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetchColumn();
record($results, $fkCount > 40, "Foreign key constraints present ($fkCount total)");

echo "\n=== Application Smoke Test (PDO queries mimicking real page loads) ===\n";
try {
    // Mimic dashboard.php's core queries
    $root->query("SELECT COUNT(*) FROM employees WHERE status NOT IN ('archived','deceased')")->fetchColumn();
    $root->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 8")->fetchAll();
    record($results, true, "Dashboard-equivalent queries execute without error");
} catch (PDOException $e) {
    record($results, false, "Dashboard-equivalent queries failed: " . $e->getMessage());
}

try {
    // Mimic modules/consultants, modules/temp_employees, modules/approvals list queries
    $root->query("SELECT * FROM consultants LIMIT 1")->fetchAll();
    $root->query("SELECT * FROM temp_employees LIMIT 1")->fetchAll();
    $root->query("SELECT aw.*, a_s.stage_name FROM approval_workflows aw
        JOIN approval_stages a_s ON a_s.workflow_id=aw.id AND a_s.stage_number=aw.current_stage LIMIT 1")->fetchAll();
    $root->query("SELECT * FROM generated_documents LIMIT 1")->fetchAll();
    $root->query("SELECT * FROM kiosk_sessions LIMIT 1")->fetchAll();
    record($results, true, "Consultants/Temp Employees/Approvals/Documents/Kiosk queries execute without error");
} catch (PDOException $e) {
    record($results, false, "Module queries failed: " . $e->getMessage());
}

echo "\n======================================\n";
echo "TOTAL: {$results['pass']} passed, {$results['fail']} failed\n";

if (!$keep) {
    $root->exec("DROP DATABASE `$testDb`");
    echo "Test database dropped.\n";
} else {
    echo "Test database '$testDb' kept for inspection (--keep flag).\n";
}

exit($results['fail'] > 0 ? 1 : 0);
