<?php
/**
 * KOMAGIN HR - DATABASE INSTALLER
 * Run this once to set up the database.
 * Delete or restrict access after installation.
 */
$step = $_GET['step'] ?? 'start';
$errors = [];
$messages = [];

if ($step === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? 'localhost');
    $user = trim($_POST['user'] ?? 'root');
    $pass = trim($_POST['pass'] ?? '');
    $dbname = trim($_POST['dbname'] ?? 'komagin_hr');

    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $messages[] = "Connected to MySQL successfully.";

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        $messages[] = "Database '$dbname' created/selected.";

        $schema = file_get_contents(__DIR__ . '/schema.sql');
        // Remove the first two lines (CREATE DATABASE & USE) since we already handled it
        $lines = explode("\n", $schema);
        $filtered = array_filter($lines, function($line) {
            $trim = trim($line);
            return !(str_starts_with($trim, 'CREATE DATABASE') || str_starts_with($trim, 'USE `'));
        });
        $schema = implode("\n", $filtered);

        $statements = array_filter(
            array_map('trim', explode(';', $schema)),
            fn($s) => !empty($s) && $s !== '--'
        );

        $tableCount = 0;
        foreach ($statements as $stmt) {
            if (!empty(trim($stmt))) {
                $pdo->exec($stmt);
                if (stripos($stmt, 'CREATE TABLE') !== false) $tableCount++;
            }
        }
        $messages[] = "Database schema installed. $tableCount tables created.";

        // Update config file with user settings
        $configPath = dirname(__DIR__) . '/config/config.php';
        $config = file_get_contents($configPath);
        $config = preg_replace("/define\('DB_HOST',\s*'[^']*'\)/", "define('DB_HOST', '$host')", $config);
        $config = preg_replace("/define\('DB_USER',\s*'[^']*'\)/", "define('DB_USER', '$user')", $config);
        $config = preg_replace("/define\('DB_PASS',\s*'[^']*'\)/", "define('DB_PASS', '$pass')", $config);
        $config = preg_replace("/define\('DB_NAME',\s*'[^']*'\)/", "define('DB_NAME', '$dbname')", $config);
        file_put_contents($configPath, $config);
        $messages[] = "Configuration file updated.";

        $step = 'done';

    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
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
        .installer-card { max-width: 540px; margin: 80px auto; }
        .brand { color: #1D4ED8; font-weight: 700; font-size: 1.4rem; }
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
            <div class="alert alert-info small mt-3">
                <strong>Step 2 — Apply v2 Migration:</strong><br>
                To enable the Employee Portal and Payroll Officer features, also run
                <code>/database/migration_v2.sql</code> in phpMyAdmin or MySQL CLI.<br>
                This adds portal columns to employees and creates: payroll_deductions, employee_savings, payslip_items, payroll_runs, employee_requests tables.
            </div>
            <div class="alert alert-warning small">
                <strong>Security Note:</strong> Delete or restrict access to <code>/database/install.php</code> after installation.
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
            <p class="text-muted small mb-4">Enter your MySQL connection details below.</p>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>

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
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Database Name</label>
                    <input type="text" class="form-control" name="dbname" value="komagin_hr" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Install Database</button>
            </form>

            <div class="mt-3 p-3 bg-light rounded small">
                <strong>Default Login After Install:</strong><br>
                Username: <code>superadmin</code><br>
                Password: <code>Admin@123</code>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
