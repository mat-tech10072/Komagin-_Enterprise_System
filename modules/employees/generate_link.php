<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('employees.update_links', 'share');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/employees/index.php'); exit;
}

$empId   = (int)($_POST['employee_id'] ?? 0);
$expires = $_POST['expires'] ?? date('Y-m-d', strtotime('+7 days'));

if (!$empId) { setFlash('error','Employee required.'); header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$emp = getEmployee($empId);
if (!$emp) { setFlash('error','Employee not found.'); header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

// Generate secure token
$token = bin2hex(random_bytes(32));
$hash  = hash('sha256', $token);

// Deactivate any existing active links for this employee
db()->prepare("UPDATE employee_update_links SET is_active=0 WHERE employee_id=? AND is_active=1")
    ->execute([$empId]);

db()->prepare("INSERT INTO employee_update_links (employee_id, token, expires_at, created_by, is_active) VALUES (?,?,?,?,1)")
    ->execute([$empId, $hash, $expires.' 23:59:59', $_SESSION['user_id']]);

$linkId = db()->lastInsertId();
$link   = APP_URL . '/self-service/update.php?token=' . $token;

auditLog('employees','generate_update_link',$empId,null,json_encode(['expires'=>$expires]));

// Show generated link
$pageTitle  = 'Self-Service Update Link';
$activeMenu = 'employees';
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/index.php">Employees</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $empId ?>"><?= e($emp['first_name'].' '.$emp['last_name']) ?></a></li>
                <li class="breadcrumb-item active">Self-Service Link</li>
            </ol>
        </nav>
        <h1 class="page-title">Self-Service Update Link Generated</h1>
    </div>
</div>

<div style="max-width:560px;">
    <div class="card">
        <div class="card-header" style="background:#F0FDF4;border-bottom:1px solid #BBF7D0;">
            <span class="card-title" style="color:#166534;">Link Generated Successfully</span>
        </div>
        <div class="card-body">
            <p style="font-size:0.85rem;margin-bottom:16px;">
                Share this link with <strong><?= e($emp['first_name'].' '.$emp['last_name']) ?></strong> so they can update their own profile details. The link expires on <strong><?= formatDate($expires) ?></strong>.
            </p>
            <div style="background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:12px;margin-bottom:16px;font-family:monospace;font-size:0.78rem;word-break:break-all;">
                <?= e($link) ?>
            </div>
            <button class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText('<?= e($link) ?>').then(()=>alert('Copied to clipboard!'))">
                Copy Link
            </button>
            <div class="alert alert-warning" style="margin-top:16px;font-size:0.78rem;">
                This link can only be used once. Once submitted, HR must approve the changes before they are applied.
            </div>
        </div>
        <div class="card-footer">
            <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $empId ?>" class="btn btn-primary btn-sm">Back to Employee Profile</a>
        </div>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
