<?php
require_once __DIR__ . '/_config.php';
require_once dirname(__DIR__) . '/auth/session_common.php';

bootstrapSession('cp_', 28800);

// Already logged in
if (!empty($_SESSION['cp_consultant_id'])) {
    header('Location: ' . CP_URL . '/dashboard.php');
    exit;
}

$error  = '';
$reason = $_GET['reason'] ?? '';
$csrf   = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (portalLoginBlocked('consultant_portal')) {
        $error = 'Too many failed attempts from this location. Please try again in 15 minutes.';
    } else {
    $conNumber = strtoupper(trim($_POST['consultant_number'] ?? ''));
    $password  = $_POST['password'] ?? '';

    if (!$conNumber || !$password) {
        $error = 'Consultant number and password are required.';
    } else {
        $stmt = db()->prepare("SELECT id, consultant_number, first_name, last_name, type, portal_password, portal_active
            FROM consultants WHERE consultant_number = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$conNumber]);
        $con = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$con) {
            $error = 'Consultant number not found or account inactive.';
            recordPortalLoginFailure('consultant_portal', $conNumber);
        } elseif (!$con['portal_active']) {
            $error = 'Portal access is not enabled for this account. Please contact HR.';
            recordPortalLoginFailure('consultant_portal', $conNumber);
        } elseif (!$con['portal_password']) {
            $error = 'No portal password set. Please contact HR to activate your portal account.';
            recordPortalLoginFailure('consultant_portal', $conNumber);
        } elseif (!password_verify($password, $con['portal_password'])) {
            $error = 'Incorrect password.';
            recordPortalLoginFailure('consultant_portal', $conNumber);
        } else {
            // Regenerate the session ID before writing any session data
            // (fixation defense), shared with every other auth surface.
            regenerateSessionOnLogin('cp_');
            $_SESSION['cp_consultant_id'] = $con['id'];
            $_SESSION['cp_type']          = $con['type'];
            $_SESSION['cp_name']          = trim($con['first_name'] . ' ' . $con['last_name']);
            $_SESSION['cp_number']        = $con['consultant_number'];
            $_SESSION['cp_login_time']    = time();
            $_SESSION['cp_last_activity'] = time();

            db()->prepare("UPDATE consultants SET portal_last_login = NOW() WHERE id = ?")->execute([$con['id']]);

            header('Location: ' . CP_URL . '/dashboard.php');
            exit;
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Consultant Portal — Komagin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= CP_URL ?>/assets/cp.css">
</head>
<body>
<div class="cp-login-page">
    <div class="cp-login-box">
        <div class="cp-login-header">
            <?php
            $loginSettings = getCompanySettings();
            $loginLogoPath = $loginSettings['company_logo'] ?? null;
            $loginLogoSrc  = !empty($loginLogoPath) ? APP_URL . '/' . $loginLogoPath : null;
            ?>
            <?php if ($loginLogoSrc): ?>
            <img src="<?= htmlspecialchars($loginLogoSrc) ?>" alt="Komagin"
                 style="height:56px;width:auto;margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">
            <?php else: ?>
            <div class="cp-login-logo">KOMAGIN</div>
            <?php endif; ?>
            <div class="cp-login-sub">Consultant Self-Service Portal</div>
        </div>

        <?php if ($reason === 'timeout'): ?>
            <div class="alert alert-warning">Session timed out. Please log in again.</div>
        <?php elseif ($reason === 'logout'): ?>
            <div class="alert alert-info">You have been logged out successfully.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label class="form-label">Consultant Number</label>
                <input type="text" name="consultant_number" class="form-control"
                       placeholder="e.g. KOM-CON-2026-0001"
                       value="<?= htmlspecialchars(strtoupper($_POST['consultant_number'] ?? '')) ?>"
                       required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Enter your portal password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Sign In
            </button>
        </form>
        <p style="font-size:0.72rem;color:#64748B;text-align:center;margin-top:20px;">
            Forgot your password? Contact your HR department.
        </p>
    </div>
</div>
</body>
</html>
