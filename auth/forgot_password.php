<?php
require_once dirname(__DIR__) . '/auth/session.php';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

// Phase 5, Stage 5.5 (KOM-041): self-service password recovery, Admin
// surface only — Employee/Consultant/Temp Portal keep the current
// admin-assisted-only model (user decision, 2026-07-13).

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error   = '';
$sent    = false;
$csrf    = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (passwordResetRequestBlocked()) {
        // Same generic message as success — do not reveal that a rate
        // limit exists or was hit, which would itself be a signal.
        $sent = true;
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        if ($identifier === '') {
            $error = 'Please enter your username or email.';
        } else {
            $stmt = db()->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Always the same response whether or not an account was
            // found — the one thing that must never leak here is
            // account existence (a classic enumeration vector).
            $sent = true;
            recordPasswordResetRequest($identifier, (bool)$user);

            if ($user) {
                // Invalidate any prior unused tokens for this user —
                // only the most recently requested link should ever work.
                db()->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL")
                    ->execute([$user['id']]);

                // Cryptographically secure token; only its sha256 hash
                // is ever stored, matching how CSRF/self-service tokens
                // are already handled elsewhere in this codebase.
                $rawToken  = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

                db()->prepare("INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, requested_ip) VALUES (?,?,?,?)")
                    ->execute([$user['id'], $tokenHash, $expiresAt, $ip]);

                $resetLink = APP_URL . '/auth/reset_password.php?token=' . $rawToken;
                $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username'];
                $bodyHtml = '<p>Hi ' . htmlspecialchars($name) . ',</p>'
                    . '<p>A password reset was requested for your Komagin HR account. If this was you, click the link below to set a new password. This link expires in 1 hour and can only be used once.</p>'
                    . '<p><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a></p>'
                    . '<p>If you did not request this, you can safely ignore this email — your password will not be changed.</p>';

                // Phase 6, Stage 6.8: the reset link's token is the same
                // secret password_reset_tokens.token_hash exists to
                // protect at rest — email_logs must not become a second,
                // unhashed copy of it. Log a redacted body instead of the
                // real one; the real $bodyHtml (with the working link) is
                // still what actually gets sent.
                $redactedBodyHtml = '<p>Hi ' . htmlspecialchars($name) . ',</p>'
                    . '<p>A password reset was requested for your Komagin HR account. If this was you, click the link below to set a new password. This link expires in 1 hour and can only be used once.</p>'
                    . '<p>[reset link redacted from log — token is single-use and expires in 1 hour]</p>'
                    . '<p>If you did not request this, you can safely ignore this email — your password will not be changed.</p>';

                sendEmail($user['email'], 'Reset your Komagin HR password', $bodyHtml, [], 'password_reset', null, $user['id'], 'users', $redactedBodyHtml);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — Komagin HR</title>
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
    <h1>Forgot your password?</h1>
    <p class="sub">Enter your username or email and we'll send you a link to reset it.</p>

    <?php if ($sent): ?>
        <div class="alert alert-success">If an account matches, a password reset link has been sent to the email on file. It expires in 1 hour.</div>
    <?php else: ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <label class="form-label">Username or Email</label>
            <input type="text" class="form-control" name="identifier" autocomplete="username" autofocus required>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>
    <?php endif; ?>

    <a href="<?= APP_URL ?>/auth/login.php" class="back-link">&larr; Back to Sign In</a>
</div>
</body>
</html>
