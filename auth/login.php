<?php
require_once dirname(__DIR__) . '/auth/session.php';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $dest = ($_SESSION['user_role'] ?? '') === 'payroll_officer'
        ? APP_URL . '/modules/payroll/index.php'
        : APP_URL . '/dashboard.php';
    header('Location: ' . $dest);
    exit;
}

$error = '';
$reason = $_GET['reason'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter your username and password.';
        } else {
            try {
                $stmt = db()->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();

                if ($user) {
                    // Check if account is locked
                    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                        $lockMins = ceil((strtotime($user['locked_until']) - time()) / 60);
                        $error = "Account temporarily locked. Try again in $lockMins minute(s).";
                    } elseif (password_verify($password, $user['password_hash'])) {
                        // Success — regenerate the session ID before writing any
                        // session data, closing the pre-auth session (fixation
                        // defense); also stamps the rotation timestamp so
                        // bootstrapSession() on the very next page load doesn't
                        // redundantly rotate a second time.
                        regenerateSessionOnLogin('');
                        $_SESSION['user_id']    = $user['id'];
                        $_SESSION['user_role']  = $user['role'];
                        $_SESSION['user_name']  = $user['username'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['employee_id'] = $user['employee_id'];
                        $_SESSION['last_activity'] = time();
                        // Phase 5, Stage 5.5: lets auth/session.php detect a
                        // password change that happened after this session
                        // was established (e.g. via a self-service reset)
                        // and force re-login — the effective equivalent of
                        // "invalidate other sessions" without needing a
                        // full session registry, which doesn't exist in
                        // this codebase's default file-based session setup.
                        $_SESSION['login_time'] = time();

                        // Reset login attempts
                        db()->prepare("UPDATE users SET login_attempts=0, locked_until=NULL, last_login=NOW() WHERE id=?")->execute([$user['id']]);

                        // Audit log
                        auditLog('auth', 'login', $user['id'], null, null, 'User logged in');

                        // Role-based redirect after login
                        $roleRedirects = [
                            'payroll_officer' => APP_URL . '/modules/payroll/index.php',
                            'payroll_manager' => APP_URL . '/modules/payroll/index.php',
                            'kiosk_terminal'  => APP_URL . '/modules/attendance/kiosk.php',
                        ];
                        $defaultRedirect = $roleRedirects[$user['role']] ?? APP_URL . '/dashboard.php';
                        $redirect = $_SESSION['redirect_after_login'] ?? $defaultRedirect;
                        unset($_SESSION['redirect_after_login']);

                        if ($user['must_change_password']) {
                            header('Location: ' . APP_URL . '/auth/change_password.php');
                        } else {
                            header('Location: ' . $redirect);
                        }
                        exit;
                    } else {
                        // Wrong password
                        $attempts = $user['login_attempts'] + 1;
                        $lockUntil = null;
                        if ($attempts >= 5) {
                            $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        }
                        db()->prepare("UPDATE users SET login_attempts=?, locked_until=? WHERE id=?")
                            ->execute([$attempts, $lockUntil, $user['id']]);

                        $remaining = max(0, 5 - $attempts);
                        $error = $remaining > 0
                            ? "Invalid credentials. $remaining attempt(s) remaining."
                            : "Too many failed attempts. Account locked for 15 minutes.";
                    }
                } else {
                    $error = 'Invalid username or password.';
                }
            } catch (PDOException $e) {
                $error = 'System error. Please try again.';
                error_log('Login error: ' . $e->getMessage());
            }
        }
    }
}

$csrf = generateCsrfToken();
// Load theme for login page colors
$loginSettings = getCompanySettings();
$loginTheme    = json_decode($loginSettings['theme_settings'] ?? '{}', true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Komagin HR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 960px;
            min-height: 560px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .login-panel-left {
            width: 45%;
            background: linear-gradient(145deg, <?= htmlspecialchars($loginTheme["login_bg"] ?? "#023852") ?> 0%, <?= htmlspecialchars($loginTheme["login_bg_dark"] ?? "#012a3d") ?> 100%);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
        }
        .login-panel-right {
            width: 55%;
            background: #fff;
            padding: 48px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .brand-name {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .brand-sub { font-size: 0.78rem; opacity: 0.75; font-weight: 400; }
        .feature-list { list-style: none; padding: 0; margin: 0; }
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.82rem;
            opacity: 0.85;
            margin-bottom: 12px;
        }
        .feature-list li::before {
            content: '✓';
            width: 18px;
            height: 18px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            flex-shrink: 0;
        }
        .login-title { font-size: 1.5rem; font-weight: 700; color: #0F172A; letter-spacing: -0.02em; margin-bottom: 4px; }
        .login-sub { font-size: 0.82rem; color: #64748B; margin-bottom: 28px; }
        .form-label { font-size: 0.78rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-control {
            height: 40px;
            font-size: 0.85rem;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            padding: 8px 12px;
            color: #0F172A;
        }
        .form-control:focus {
            border-color: <?= htmlspecialchars($loginTheme["primary"] ?? "#023852") ?>;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
            outline: none;
        }
        .btn-login {
            height: 40px;
            background: <?= htmlspecialchars($loginTheme["primary"] ?? "#023852") ?>;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-login:hover { background: #012a3d; }
        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.8rem;
            margin-bottom: 16px;
        }
        .alert-info {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: #012a3d;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.8rem;
            margin-bottom: 16px;
        }
        .input-wrapper { position: relative; }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94A3B8;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }
        .copy-note { font-size: 0.72rem; color: #94A3B8; text-align: center; margin-top: 24px; }
        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; max-width: 440px; border-radius: 12px; }
            .login-panel-left { width: 100%; padding: 32px; min-height: auto; }
            .login-panel-right { width: 100%; padding: 32px; }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-panel-left">
        <div>
            <?php
            // Always pull from DB — same source as sidebar
            $loginSettings = getCompanySettings();
            $loginLogoPath = $loginSettings['company_logo'] ?? null;
            $loginLogoSrc  = !empty($loginLogoPath) ? APP_URL . '/' . $loginLogoPath : null;
            $loginCompany  = $loginSettings['company_name'] ?? 'Komagin HR';
            ?>
            <?php if ($loginLogoSrc): ?>
            <img src="<?= htmlspecialchars($loginLogoSrc) ?>" alt="<?= htmlspecialchars($loginCompany) ?>"
                 style="height:64px;width:auto;display:block;margin-bottom:16px;max-width:none;">
            <?php endif; ?>
            <div class="brand-name"><?= htmlspecialchars($loginCompany) ?></div>
            <div class="brand-sub">Management System</div>
        </div>
        <div>
            <p style="font-size:1rem; font-weight:600; margin-bottom:16px; opacity:0.95;">
                Enterprise HR — built for performance.
            </p>
            <ul class="feature-list">
                <li>Employee lifecycle management</li>
                <li>Attendance &amp; timesheet tracking</li>
                <li>Leave management &amp; approvals</li>
                <li>Recruitment &amp; onboarding</li>
                <li>Documents &amp; compliance</li>
                <li>Reports &amp; analytics</li>
                <li>Role-based access control</li>
                <li>Audit trail &amp; data security</li>
            </ul>
        </div>
        <div style="font-size:0.72rem; opacity:0.5;">© <?= date('Y') ?> Komagin Limited</div>
    </div>

    <div class="login-panel-right">
        <div>
            <h1 class="login-title">Welcome back</h1>
            <p class="login-sub">Sign in to your HR account to continue.</p>

            <?php if ($reason === 'timeout'): ?>
                <div class="alert-info">Your session expired. Please sign in again.</div>
            <?php endif; ?>
            <?php if ($reason === 'logout'): ?>
                <div class="alert-info">You have been logged out successfully.</div>
            <?php endif; ?>
            <?php if ($reason === 'password_changed'): ?>
                <div class="alert-info">Your password was changed. Please sign in again.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="mb-3">
                    <label class="form-label">Username or Email</label>
                    <input type="text" class="form-control" name="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Enter username or email" autocomplete="username" autofocus required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password" class="form-control" name="password" id="passwordInput"
                               placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div style="text-align:right;margin-top:6px;">
                        <a href="<?= APP_URL ?>/auth/forgot_password.php" style="font-size:0.78rem;color:#023852;text-decoration:none;">Forgot password?</a>
                    </div>
                </div>

                <button type="submit" class="btn-login">Sign In</button>
            </form>

            <p class="copy-note">Komagin HR Management System v1.0</p>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>





