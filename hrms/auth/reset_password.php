<?php
require_once dirname(__DIR__) . '/auth/session.php';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$rawToken = $_GET['token'] ?? $_POST['token'] ?? '';
$error    = '';
$success  = false;

function findValidResetToken(string $rawToken): ?array {
    if ($rawToken === '' || strlen($rawToken) !== 64) return null;
    $tokenHash = hash('sha256', $rawToken);
    $stmt = db()->prepare("SELECT prt.*, u.username, u.email FROM password_reset_tokens prt
        JOIN users u ON u.id = prt.user_id
        WHERE prt.token_hash = ? AND prt.used_at IS NULL AND prt.expires_at > NOW() AND u.is_active = 1
        LIMIT 1");
    $stmt->execute([$tokenHash]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// One generic message for every failure mode (not found, expired,
// already used) — deliberately not distinguishing which, so the page
// can't be used as an oracle to probe token validity.
$tokenRow = findValidResetToken($rawToken);
$tokenValid = $tokenRow !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (!$tokenValid) {
        $error = 'This link is invalid or has expired. Please request a new one.';
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirm     = $_POST['confirm_password'] ?? '';
        if (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($newPassword !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            db()->prepare("UPDATE users SET password_hash=?, password_changed_at=NOW(), must_change_password=0, login_attempts=0, locked_until=NULL WHERE id=?")
                ->execute([$hash, $tokenRow['user_id']]);
            // One-time use: mark this token consumed immediately.
            db()->prepare("UPDATE password_reset_tokens SET used_at=NOW() WHERE id=?")
                ->execute([$tokenRow['id']]);
            auditLog('auth', 'password_reset_completed', $tokenRow['user_id'], null, null, 'Password reset via self-service link');
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — Komagin HR</title>
<style>
* { box-sizing: border-box; }
body { font-family: 'Inter', Arial, sans-serif; background: #F1F5F9; min-height: 100vh; margin: 0; display: flex; align-items: center; justify-content: center; }
.card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 36px; width: 100%; max-width: 380px; }
.card h1 { font-size: 1.25rem; margin: 0 0 8px; color: #0F172A; }
.card p.sub { font-size: 0.85rem; color: #64748B; margin: 0 0 20px; }
.form-label { font-size: 0.8rem; font-weight: 600; color: #334155; display: block; margin-bottom: 6px; }
.form-control { width: 100%; padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; margin-bottom: 16px; }
.btn { width: 100%; padding: 11px; background: #023852; color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; }
.btn:hover { background: #012a3d; }
.alert { padding: 12px 14px; border-radius: 8px; font-size: 0.82rem; margin-bottom: 16px; }
.alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
.alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
.back-link { display: block; text-align: center; margin-top: 16px; font-size: 0.82rem; color: #023852; text-decoration: none; }
</style>
</head>
<body>
<div class="card">
    <?php if ($success): ?>
        <h1>Password updated</h1>
        <div class="alert alert-success">Your password has been reset. Any other active sessions on this account have been signed out. You can now sign in with your new password.</div>
        <a href="<?= APP_URL ?>/auth/login.php" class="back-link">&rarr; Sign In</a>
    <?php elseif (!$tokenValid): ?>
        <h1>Link invalid or expired</h1>
        <div class="alert alert-error">This password reset link is invalid or has expired. Please request a new one.</div>
        <a href="<?= APP_URL ?>/auth/forgot_password.php" class="back-link">&larr; Request a new link</a>
    <?php else: ?>
        <h1>Set a new password</h1>
        <p class="sub">Choose a new password for your account.</p>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken) ?>">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" autocomplete="new-password" required minlength="8">
            <label class="form-label">Confirm Password</label>
            <input type="password" class="form-control" name="confirm_password" autocomplete="new-password" required minlength="8">
            <button type="submit" class="btn">Reset Password</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
