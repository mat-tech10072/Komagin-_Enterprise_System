<?php
require_once __DIR__ . '/sql_split.php';

/**
 * KOMAGIN HR — DATABASE INSTALLER
 *
 * Phase 3 rewrite: previously this ran database/schema.sql only, then
 * told the operator to manually run migration_v2.sql via phpMyAdmin, and
 * never mentioned phase1/5/6/8/9/10 at all — a fresh install following
 * only this installer's own instructions ended up with no permission
 * matrix, no branding tables' seed data, no document template library,
 * and no temp-employee/consultant module support (KOM-024).
 *
 * It now runs the complete, correct sequence for a fresh install in one
 * pass, stopping immediately and reporting clearly if any step fails —
 * see database/README.md for what each step does and why the order
 * matters.
 *
 * Run this once to set up the database. Delete or restrict access after
 * installation.
 */
$step = $_GET['step'] ?? 'start';
$errors = [];
$messages = [];
$stepLog = [];

// Fresh-install sequence — schema.sql now contains the COMPLETE current
// table structure (see docs/remediation/Database/06-phase3-canonical-database-model.md),
// so migration_v2.sql and phase10's CREATE/ALTER statements are redundant
// here and deliberately skipped; only the files carrying essential SEED
// DATA (permissions, templates, categories) that schema.sql intentionally
// does not include run after it. phase7_test_data.sql (demo data) is
// deliberately NOT run by default — see the "Load Demo Data" checkbox.
//
// Phase 6, Stage 6.1: phase11_schema_reconciliation.sql and
// phase12_workflow_integrity_fixes.sql are correctly NOT in this list —
// both explicitly document themselves as upgrade-path-only for
// databases older than Phase 3/Phase 4, and everything they'd otherwise
// add (all 11 tables, personal_email, etc.) is already confirmed present
// natively in schema.sql. phase13_workflow_completeness_automation.sql
// is the one exception: its CREATE TABLE/ALTER statements are likewise
// already redundant with schema.sql, but it also carries the one seed
// row schema.sql intentionally excludes — the default
// work_calendar_settings row (id=1) every "Absent Today"/"Absent"
// calculation across Dashboard, Reports, and Attendance depends on
// (see config/functions.php's getWorkCalendarSettings()). Without it, a
// fresh install would create the table but never seed that row. Placed
// after phase8 since temp_attendance has a FK to temp_employees.
const INSTALL_SEQUENCE = [
    ['file' => 'schema.sql',                          'label' => 'Core database structure (60 tables)'],
    ['file' => 'seeds/001_baseline_admin.sql',         'label' => 'Default super_admin account'],
    ['file' => 'seeds/002_doc_categories.sql',         'label' => 'Document template categories'],
    ['file' => 'seeds/003_departments_positions.sql',  'label' => 'Departments & positions'],
    ['file' => 'phase1_permissions.sql',               'label' => 'Core permission matrix (79 permissions)'],
    ['file' => 'phase5_branding_theme.sql',            'label' => 'Branding & email permissions'],
    ['file' => 'phase6_templates.sql',                 'label' => 'Document template library (47 templates)'],
    ['file' => 'phase8_temp_employees.sql',            'label' => 'Temporary employees module & permissions'],
    ['file' => 'phase9_consultants.sql',               'label' => 'Consultants module & permissions'],
    ['file' => 'phase10_authorization_framework.sql',  'label' => 'Activity Log & Approvals permissions'],
    ['file' => 'phase13_workflow_completeness_automation.sql', 'label' => 'Working-calendar default row, scheduler/password-reset/notification tables (Phase 5)'],
];

function runSqlFile(PDO $pdo, string $path, string $label, array &$log): bool {
    if (!file_exists($path)) {
        $log[] = ['fail', "$label — file not found: $path"];
        return false;
    }
    $sql = file_get_contents($path);
    // Strip CREATE DATABASE / USE lines — the installer already selected the DB.
    $lines = explode("\n", $sql);
    $lines = array_filter($lines, function ($line) {
        $t = trim($line);
        return !(str_starts_with($t, 'CREATE DATABASE') || str_starts_with($t, 'USE '));
    });
    $sql = implode("\n", $lines);

    $statements = splitSqlStatements($sql);

    $count = 0;
    try {
        foreach ($statements as $stmt) {
            if ($stmt === '') continue;
            // query()+closeCursor() rather than exec(): some files (e.g.
            // phase1_permissions.sql) end with a diagnostic SELECT meant for
            // a human running the file manually. exec() on a SELECT leaves
            // its result set open and blocks every subsequent query on the
            // same connection; query()->closeCursor() handles both DDL/DML
            // and an incidental SELECT correctly.
            $pdo->query($stmt)->closeCursor();
            $count++;
        }
    } catch (PDOException $e) {
        $log[] = ['fail', "$label — FAILED after $count statement(s): " . $e->getMessage()];
        return false; // stop on failure, per Phase 3 charter — do not continue past a broken step
    }
    $log[] = ['ok', "$label — $count statement(s) executed"];
    return true;
}

if ($step === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? 'localhost');
    $user = trim($_POST['user'] ?? 'root');
    $pass = trim($_POST['pass'] ?? '');
    $dbname = trim($_POST['dbname'] ?? 'komagin_hr');
    $loadDemo = isset($_POST['load_demo']);

    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $messages[] = "Connected to MySQL successfully.";

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        $messages[] = "Database '$dbname' created/selected.";

        $sequence = INSTALL_SEQUENCE;
        if ($loadDemo) {
            $sequence[] = ['file' => 'phase7_test_data.sql', 'label' => 'Demo/test data (NOT for production)'];
        }

        $allOk = true;
        foreach ($sequence as $entry) {
            $ok = runSqlFile($pdo, __DIR__ . '/' . $entry['file'], $entry['label'], $stepLog);
            if (!$ok) { $allOk = false; break; }
        }

        if ($allOk) {
            // Record this install in schema_migrations so a future upgrade
            // run knows the baseline state it's starting from.
            $stmt = $pdo->prepare("INSERT IGNORE INTO schema_migrations (migration_name, status) VALUES (?, 'success')");
            foreach ($sequence as $entry) {
                $stmt->execute([$entry['file']]);
            }

            // Update config file with user-supplied connection settings
            $configPath = dirname(__DIR__) . '/config/config.php';
            $config = file_get_contents($configPath);
            $config = preg_replace("/define\('DB_HOST',\s*'[^']*'\)/", "define('DB_HOST', '$host')", $config);
            $config = preg_replace("/define\('DB_USER',\s*'[^']*'\)/", "define('DB_USER', '$user')", $config);
            $config = preg_replace("/define\('DB_PASS',\s*'[^']*'\)/", "define('DB_PASS', '$pass')", $config);
            $config = preg_replace("/define\('DB_NAME',\s*'[^']*'\)/", "define('DB_NAME', '$dbname')", $config);
            file_put_contents($configPath, $config);
            $messages[] = "Configuration file updated.";

            $step = 'done';
        } else {
            $errors[] = "Installation stopped — one step failed. No further steps were run. See the log below for exactly which statement failed; earlier steps already committed are NOT automatically rolled back (MySQL DDL auto-commits per statement), so re-running after fixing the cause is safe — every step uses IF NOT EXISTS / INSERT IGNORE and will skip what already succeeded.";
        }

    } catch (PDOException $e) {
        $errors[] = "Database connection error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komagin HR – Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #F8FAFC; font-family: 'Inter', sans-serif; }
        .installer-card { max-width: 600px; margin: 60px auto; }
        .brand { color: #1D4ED8; font-weight: 700; font-size: 1.4rem; }
        .step-log { font-size: 0.78rem; font-family: monospace; max-height: 260px; overflow-y: auto; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="installer-card">
    <div class="text-center mb-4">
        <div class="brand">KOMAGIN HR</div>
        <p class="text-muted small">Management System Installer</p>
    </div>

    <?php if ($step === 'done'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <div style="width:64px;height:64px;background:#DCFCE7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h5 class="fw-600">Installation Complete!</h5>
            </div>
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-success py-2 small">✓ <?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
            <div class="step-log bg-light rounded p-2 mb-3">
                <?php foreach ($stepLog as [$type, $msg]): ?>
                <div class="<?= $type === 'ok' ? 'text-success' : 'text-danger' ?>"><?= $type === 'ok' ? '✓' : '✗' ?> <?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>
            </div>
            <div class="alert alert-warning small">
                <strong>Security Note:</strong> Delete or restrict access to <code>/database/install.php</code> after installation. The default admin account is forced to change its password on first login.
            </div>
            <div class="text-center mt-3">
                <a href="../auth/login.php" class="btn btn-primary">Go to Login →</a>
                &nbsp;
                <a href="../employee-portal/login.php" class="btn btn-outline-secondary btn-sm">Employee Portal →</a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="fw-semibold mb-1">Database Setup</h5>
            <p class="text-muted small mb-3">Enter your MySQL connection details below. This will run the complete install sequence — structure, permissions, and the document template library — in one step.</p>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
            <?php if ($stepLog): ?>
            <div class="step-log bg-light rounded p-2 mb-3">
                <?php foreach ($stepLog as [$type, $msg]): ?>
                <div class="<?= $type === 'ok' ? 'text-success' : 'text-danger' ?>"><?= $type === 'ok' ? '✓' : '✗' ?> <?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="?step=install">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">MySQL Host</label>
                    <input type="text" class="form-control" name="host" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">MySQL Username</label>
                    <input type="text" class="form-control" name="user" value="root" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">MySQL Password</label>
                    <input type="password" class="form-control" name="pass" placeholder="Leave blank if none">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Database Name</label>
                    <input type="text" class="form-control" name="dbname" value="komagin_hr" required>
                </div>
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" name="load_demo" id="loadDemo">
                    <label class="form-check-label small" for="loadDemo">Also load demo/test data (development only — do not use in production)</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Install Database</button>
            </form>

            <div class="mt-3 p-3 bg-light rounded small">
                <strong>Default Login After Install:</strong><br>
                Username: <code>superadmin</code><br>
                Password: <code>Admin@123</code> (must be changed immediately on first login)
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
