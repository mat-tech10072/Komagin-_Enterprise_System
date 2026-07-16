<?php
/**
 * Komagin Limited — Database Migration Script
 *
 * Imports komagin_export.sql into the configured database.
 * Run ONCE after uploading to cPanel, then DELETE both this file
 * and komagin_export.sql from the server.
 *
 * Steps:
 *   1. Set DB credentials in .htaccess (SetEnv lines) or edit db_config.php
 *   2. Create the database + user in cPanel MySQL Databases
 *   3. Visit: https://yourdomain.com/admin/migrate.php
 *   4. Delete this file and komagin_export.sql from the server
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // we handle output ourselves

require_once __DIR__ . '/db_config.php';

$sqlFile = __DIR__ . '/komagin_export.sql';

// ── helpers ────────────────────────────────────────────────────────────────
function row(string $type, string $msg): void {
    $icon  = ['ok' => '&#10003;', 'fail' => '&#10007;', 'warn' => '&#9888;', 'info' => '&#9432;'][$type] ?? '&#9432;';
    $color = ['ok' => '#16a34a', 'fail' => '#dc2626', 'warn' => '#b45309', 'info' => '#2563eb'][$type] ?? '#111';
    echo "<li style='color:{$color};line-height:2'>{$icon} {$msg}</li>\n";
    ob_flush(); flush();
}

// ── pre-flight checks ──────────────────────────────────────────────────────
$errors = [];
if (!file_exists($sqlFile))  $errors[] = 'komagin_export.sql not found next to this file.';
if (DB_USER === 'root' && DB_HOST === '127.0.0.1')
    $errors[] = 'Still using XAMPP defaults. Set DB credentials in .htaccess or db_config.php first.';

// ── connect to MySQL ───────────────────────────────────────────────────────
$pdo = null;
if (!$errors) {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        $errors[] = 'Cannot connect to MySQL: ' . $e->getMessage();
    }
}

// ── create database if missing ─────────────────────────────────────────────
if ($pdo && !$errors) {
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
    } catch (PDOException $e) {
        $errors[] = 'Cannot create/select database: ' . $e->getMessage();
    }
}

// ── parse and run SQL statements ───────────────────────────────────────────
$results = [];
if ($pdo && !$errors) {
    $sql     = file_get_contents($sqlFile);
    // Strip comments and split on statement delimiter
    $sql     = preg_replace('/^--.*$/m', '', $sql);
    $sql     = preg_replace('#/\*.*?\*/#s', '', $sql);
    $stmts   = array_filter(array_map('trim', explode(';', $sql)));

    $ok = $fail = 0;
    foreach ($stmts as $stmt) {
        if ($stmt === '') continue;
        try {
            $pdo->exec($stmt);
            $ok++;
        } catch (PDOException $e) {
            // Skip "table already exists" — makes re-running safe
            if (stripos($e->getMessage(), 'already exists') !== false) {
                $ok++;
            } else {
                $fail++;
                $results[] = ['warn', 'Statement warning: ' . htmlspecialchars($e->getMessage())];
            }
        }
    }
    // ── schema patches for existing tables ───────────────────────────────────
    try {
        $pdo->exec("ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) DEFAULT 'PENDING' AFTER `category`");
        $pdo->exec("ALTER TABLE `projects` ADD INDEX IF NOT EXISTS `idx_projects_status` (`status`)");
        $results[] = ['ok', 'Schema patch: projects.status column added.'];
    } catch (PDOException $e) {
        $results[] = ['warn', 'Schema patch skipped: ' . htmlspecialchars($e->getMessage())];
    }

    // ── ensure project_categories table ──────────────────────────────────────
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `project_categories` (
            id          VARCHAR(50)  NOT NULL,
            name        VARCHAR(255) NOT NULL,
            slug        VARCHAR(100) NOT NULL,
            description TEXT         DEFAULT NULL,
            sort_order  INT          DEFAULT 0,
            created_at  DATETIME     DEFAULT NULL,
            updated_at  DATETIME     DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_project_categories_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach ([
            ['projcat_subdivision',    'Subdivision',    'subdivision',    'Residential and land subdivision projects',       1],
            ['projcat_building',       'Building',       'building',       'Commercial and residential building construction', 2],
            ['projcat_infrastructure', 'Infrastructure', 'infrastructure', 'Roads, utilities, and civil infrastructure',       3],
        ] as [$cid, $cname, $cslug, $cdesc, $cord]) {
            $pdo->prepare("INSERT IGNORE INTO project_categories (id,name,slug,description,sort_order,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())")
                ->execute([$cid, $cname, $cslug, $cdesc, $cord]);
        }
        $results[] = ['ok', 'Schema patch: project_categories table created/seeded.'];
    } catch (PDOException $e) {
        $results[] = ['warn', 'project_categories patch skipped: ' . htmlspecialchars($e->getMessage())];
    }

    $results[] = $fail === 0
        ? ['ok',   "Import complete — {$ok} statement(s) executed successfully."]
        : ['warn', "Import finished with {$fail} warning(s) and {$ok} success(es). See details above."];
}

// ── verify key tables ──────────────────────────────────────────────────────
$checks = [];
if ($pdo && !$errors) {
    foreach (['users','settings','projects','services','hire_items','documents','csr_items'] as $t) {
        try {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
            $checks[] = ['ok', "Table <code>{$t}</code> — {$count} row(s)"];
        } catch (PDOException $e) {
            $checks[] = ['fail', "Table <code>{$t}</code> missing or unreadable"];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Komagin — Database Migration</title>
<style>
  body { font-family:Arial,sans-serif; background:#f3f4f6; margin:0; padding:30px; color:#111; }
  .card { background:#fff; border-radius:12px; padding:30px 40px; max-width:820px; margin:0 auto 24px; box-shadow:0 4px 20px rgba(0,0,0,.09); }
  h1 { color:#1A3A5C; margin-bottom:4px; font-size:21px; }
  h2 { color:#1A3A5C; font-size:15px; margin:22px 0 8px; border-bottom:1px solid #e5e7eb; padding-bottom:5px; }
  ul { list-style:none; padding:0; margin:0; font-size:14px; }
  code { background:#f1f5f9; border-radius:4px; padding:1px 5px; font-size:13px; }
  .err  { background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:16px 20px; color:#991b1b; font-size:14px; }
  .warn-box { background:#fefce8; border:1px solid #fde047; border-radius:8px; padding:16px 20px; margin-top:16px; font-size:14px; color:#713f12; }
  .ok-box   { background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:16px 20px; margin-top:16px; font-size:14px; }
  .links { margin-top:20px; display:flex; gap:12px; flex-wrap:wrap; }
  .links a { display:inline-block; padding:9px 20px; border-radius:8px; background:#1A3A5C; color:#fff; text-decoration:none; font-size:14px; font-weight:600; }
  .links a.gold { background:#E8A317; }
</style>
</head>
<body>
<div class="card">
<h1>Komagin Limited — Database Migration</h1>
<p style="color:#6b7280;margin-top:0;font-size:13px">
  Importing <code>komagin_export.sql</code> into
  <strong><?= htmlspecialchars(DB_NAME) ?></strong>
  on <strong><?= htmlspecialchars(DB_HOST) ?></strong>
  as user <strong><?= htmlspecialchars(DB_USER) ?></strong>
</p>

<?php if ($errors): ?>
<div class="err">
  <strong>&#10007; Cannot proceed:</strong><br><br>
  <ul style="list-style:disc;padding-left:20px;margin-top:4px">
    <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
  </ul>
  <br>
  <strong>Fix:</strong> Set the correct DB credentials:
  <ol style="margin-top:6px;padding-left:20px">
    <li>Open <code>.htaccess</code> in the site root on the server</li>
    <li>Uncomment the four <code>SetEnv DB_*</code> lines and fill in your cPanel MySQL values</li>
    <li>Reload this page</li>
  </ol>
</div>

<?php else: ?>

<h2>1. Import</h2>
<ul>
<?php foreach ($results as [$t, $m]) row($t, $m); ?>
</ul>

<h2>2. Table Verification</h2>
<ul>
<?php foreach ($checks as [$t, $m]) row($t, $m); ?>
</ul>

<div class="ok-box">
  &#10003; Migration complete. Your site is ready.
</div>

<div class="warn-box">
  <strong>&#9888; Security:</strong> Delete <code>admin/migrate.php</code> and
  <code>admin/komagin_export.sql</code> from the server now — they are no longer needed
  and contain sensitive data.
</div>

<div class="links">
  <a href="auth.php">Admin Login</a>
  <a href="../index.html" class="gold" target="_blank">View Website</a>
</div>

<?php endif; ?>

</div>
</body>
</html>
