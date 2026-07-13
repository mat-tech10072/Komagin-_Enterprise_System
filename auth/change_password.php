<?php
require_once dirname(__DIR__) . '/auth/session.php';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $stmt = db()->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!password_verify($current, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
                db()->prepare("UPDATE users SET password_hash=?, password_changed_at=NOW(), must_change_password=0 WHERE id=?")
                    ->execute([$hash, $_SESSION['user_id']]);
                // Phase 5, Stage 5.5: password_changed_at forces every
                // OTHER active session on this account to re-login on its
                // next request (auth/session.php compares it against each
                // session's own login_time) — but this session already
                // just re-proved identity via the correct current_password
                // above, so bump its own login_time past the new
                // password_changed_at rather than also logging itself out.
                $_SESSION['login_time'] = time();
                auditLog('auth', 'password_change', $_SESSION['user_id']);
                header('Location: ' . APP_URL . '/dashboard.php?msg=password_changed');
                exit;
            }
        }
    }
}

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password – Komagin HR</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family:'Inter',sans-serif; background:#F8FAFC; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .card { max-width:440px; width:100%; border:1px solid #E2E8F0; border-radius:12px; padding:36px; background:#fff; }
        h5 { font-size:1.1rem; font-weight:700; color:#0F172A; }
        .form-control { height:40px; font-size:0.85rem; border:1px solid #CBD5E1; border-radius:8px; }
        .form-control:focus { border-color:#1D4ED8; box-shadow:0 0 0 3px rgba(29,78,216,0.1); }
        .btn-primary { background:#1D4ED8; border:none; height:40px; border-radius:8px; font-size:0.85rem; font-weight:600; }
    </style>
</head>
<body>
<div class="card">
    <h5 class="mb-1">Change Password</h5>
    <p class="text-muted small mb-4">You must update your password before continuing.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger small"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">New Password</label>
            <input type="password" name="new_password" class="form-control" minlength="8" required>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-semibold">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Update Password</button>
    </form>
</div>
</body>
</html>
