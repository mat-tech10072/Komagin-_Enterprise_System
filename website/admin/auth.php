<?php
// auth.php - Login page for Komagin Limited Web Admin & Communications Panel
// NOTE: db.php is NOT required here — the login form is pure HTML.
// The actual DB query happens in admin.php when the JS submits the form.
$sessionDir = __DIR__ . '/sessions';
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    session_save_path($sessionDir);
}
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: auth.php?message=logged_out');
    exit;
}

$message = '';
$messageType = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'logged_out':
            $message = 'You have been successfully logged out.';
            $messageType = 'success';
            break;
        case 'session_expired':
            $message = 'Your session has expired. Please login again.';
            $messageType = 'info';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_credentials':
            $message = 'Invalid username or password';
            $messageType = 'error';
            break;
        case 'session_timeout':
            $message = 'Your session has timed out. Please login again.';
            $messageType = 'info';
            break;
        case 'auth':
            $message = 'Session expired. Please login again.';
            $messageType = 'info';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Komagin Limited - Web Admin Login</title>
    <link rel="stylesheet" href="../assets/vendor/google-fonts/fonts.css?v=20260521-offline-exact">
    <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css?v=20260521-offline-exact">
    <link rel="stylesheet" href="login.css?v=20260521-offline-exact">
    <link rel="icon" type="image/png" href="../images/logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #1A3A5C 0%, #0F2B45 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(232,163,23,0.08) 0%, transparent 50%),
                repeating-linear-gradient(45deg, rgba(232,163,23,0.03) 0px, rgba(232,163,23,0.03) 2px, transparent 2px, transparent 8px);
            pointer-events: none;
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            position: relative;
            z-index: 1;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            background: linear-gradient(135deg, #1A3A5C 0%, #2A4A6C 100%);
            color: white;
            padding: 35px 30px 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(232,163,23,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .login-header .logo {
            width: 90px;
            height: 90px;
            margin: 0 auto 15px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .login-header .logo img {
            width: 65px;
            height: 65px;
            object-fit: contain;
        }
        
        .login-header h1 {
            font-size: 26px;
            margin-bottom: 5px;
            font-family: 'Oswald', sans-serif;
            position: relative;
            z-index: 1;
            letter-spacing: -0.5px;
        }
        
        .login-header h1 span {
            color: #E8A317;
        }
        
        .login-header p {
            opacity: 0.85;
            font-size: 13px;
            position: relative;
            z-index: 1;
        }
        
        .login-form {
            padding: 35px 30px;
        }
        
        .form-group {
            margin-bottom: 22px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1A2632;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
        }
        
        .form-group label i {
            color: #1A3A5C;
            margin-right: 6px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #1A3A5C;
            font-size: 15px;
        }
        
        .input-wrapper .toggle-password {
            left: auto;
            right: 14px;
            cursor: pointer;
            color: #6C757D;
            transition: color 0.3s;
        }
        
        .input-wrapper .toggle-password:hover {
            color: #1A3A5C;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 1px solid #E9ECEF;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #F8F9FA;
            min-height: 52px;
            font-family: 'Inter', sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1A3A5C;
            box-shadow: 0 0 0 3px rgba(26,58,92,0.1);
            background: white;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #1A3A5C;
            cursor: pointer;
        }
        
        .remember-me label {
            color: #6C757D;
            font-size: 13px;
            cursor: pointer;
            margin-bottom: 0;
        }
        
        .forgot-password {
            color: #1A3A5C;
            font-size: 13px;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .forgot-password:hover {
            color: #E8A317;
            text-decoration: underline;
        }
        
        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1A3A5C 0%, #2A4A6C 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
        }
        
        .login-btn:hover {
            background: linear-gradient(135deg, #0F2B45 0%, #1A3A5C 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26,58,92,0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            animation: fadeIn 0.3s ease-in-out;
            font-size: 13px;
        }
        
        .message.show {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.error {
            background: #FEF3F2;
            border: 1px solid #FEE2E2;
            color: #DC2626;
        }
        
        .message.success {
            background: #ECFDF5;
            border: 1px solid #D1FAE5;
            color: #059669;
        }
        
        .message.info {
            background: #EFF6FF;
            border: 1px solid #DBEAFE;
            color: #2563EB;
        }
        
        .default-credentials {
            margin-top: 25px;
            padding: 18px;
            background: #F8F9FA;
            border-radius: 12px;
            font-size: 12px;
            color: #6C757D;
            border: 1px solid #E9ECEF;
            position: relative;
            overflow: hidden;
        }
        
        .default-credentials::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1A3A5C, #E8A317, #1A3A5C);
        }
        
        .default-credentials h4 {
            margin-bottom: 12px;
            color: #1A3A5C;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .default-credentials p {
            margin: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }
        
        .default-credentials p i {
            color: #1A3A5C;
            width: 16px;
        }
        
        .admin-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #E9ECEF;
        }
        
        .admin-link a {
            color: #1A3A5C;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }
        
        .admin-link a:hover {
            color: #E8A317;
            text-decoration: underline;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }
            .login-header {
                padding: 25px 20px;
            }
            .login-header .logo {
                width: 75px;
                height: 75px;
            }
            .login-header .logo img {
                width: 55px;
                height: 55px;
            }
            .login-header h1 {
                font-size: 22px;
            }
            .login-form {
                padding: 25px 20px;
            }
        }
    </style>
    <link rel="stylesheet" href="../assets/ui-enterprise.css?v=20260514-ui-modern-5">
</head>
<body class="komagin-login ui-login">
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="../images/logo.png" alt="Komagin Limited Logo">
            </div>
            <h1>KOMAGIN <span>LIMITED</span></h1>
            <p>Web Admin & Communications Panel</p>
        </div>
        
        <div class="login-form">
            <?php if ($message): ?>
            <div id="message" class="message <?php echo $messageType; ?> show">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <?php endif; ?>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required autocomplete="username" placeholder="Enter your username">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me for 7 days</label>
                    </div>
                    <a href="#" class="forgot-password" id="forgotPassword"><i class="fas fa-question-circle"></i> Forgot Password?</a>
                </div>
                
                <button type="submit" class="login-btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login to Web Admin</span>
                </button>
            </form>
            
            <div class="default-credentials">
                <h4><i class="fas fa-shield-alt"></i> Secure Access</h4>
                <p>This panel is for authorised Web Admin and Communications users only.</p>
                <div class="note" style="margin-top: 10px; font-size: 11px; color: #999;">
                    <i class="fas fa-exclamation-triangle"></i> Contact the System Administrator if you need access or a password reset.
                </div>
            </div>
            
            <div class="admin-link portal-switch">
                <p>Authorised Komagin personnel only</p>
                <div class="portal-links">
                    <a href="../../P02/public/"><i class="fas fa-user-tie"></i> HR Admin</a>
                    <a href="../index.html" target="_blank"><i class="fas fa-globe"></i> Website</a>
                </div>
            </div>
        </div>
    </div>

    <div id="helpModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); z-index: 10000; max-width: 90%; width: 400px;">
        <h3 style="color: #1A3A5C; margin-bottom: 15px; font-family: 'Oswald', sans-serif;">Need Help?</h3>
        <p style="margin-bottom: 15px; color: #4A5568;">For assistance, please contact the system administrator:</p>
        <p style="margin-bottom: 8px;"><strong>WhatsApp:</strong> <a href="https://wa.me/67579190716" target="_blank" style="color: #1A3A5C;">+675 7919 0716</a></p>
        <p style="margin-bottom: 15px;"><strong>Email:</strong> <a href="mailto:admin@komagin.com" style="color: #1A3A5C;">admin@komagin.com</a></p>
        <hr style="margin: 15px 0; border: none; border-top: 1px solid #E9ECEF;">
        <p style="margin-bottom: 15px;">This panel uses managed EMS credentials. If you cannot sign in, contact the System Administrator for access support.</p>
        <button onclick="document.getElementById('helpModal').style.display='none'" style="margin-top: 10px; padding: 12px 20px; background: linear-gradient(135deg, #1A3A5C 0%, #2A4A6C 100%); color: white; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600;">Close</button>
    </div>

    <!-- Iconify removed — Font Awesome 6 handles all icons -->
    <script>
        const loginForm = document.getElementById('loginForm');
        const togglePassword = document.getElementById('togglePassword');
        const loginBtn = document.getElementById('loginBtn');
        const messageDiv = document.getElementById('message');
        
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('forgotPassword').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('helpModal').style.display = 'block';
        });
        
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('helpModal');
            if (e.target === modal) modal.style.display = 'none';
        });
        
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            
            if (!username || !password) {
                showMessage('Please enter username and password', 'error');
                return;
            }
            
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            
            try {
                const response = await fetch('admin.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password, remember })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Login successful! Redirecting...', 'success');
                    if (remember) {
                        localStorage.setItem('admin_remember_login', 'true');
                        localStorage.setItem('admin_username', username);
                    }
                    sessionStorage.setItem('admin_logged_in', 'true');
                    sessionStorage.setItem('admin_username', username);
                    sessionStorage.setItem('admin_login_time', Date.now().toString());
                    setTimeout(() => { window.location.href = 'admin.php'; }, 1000);
                } else {
                    showMessage(result.error || 'Login failed', 'error');
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Login to Web Admin</span>';
                }
            } catch (error) {
                showMessage('Network error. Please try again.', 'error');
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Login to Web Admin</span>';
            }
        });
        
        function showMessage(text, type) {
            let msgDiv = messageDiv;
            if (!msgDiv) {
                msgDiv = document.createElement('div');
                msgDiv.id = 'message';
                document.querySelector('.login-form').insertBefore(msgDiv, loginForm);
            }
            let icon = type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : 'info-circle');
            msgDiv.innerHTML = `<i class="fas fa-${icon}"></i><span>${text}</span>`;
            msgDiv.className = `message ${type} show`;
            setTimeout(() => { msgDiv.classList.remove('show'); }, 5000);
        }
        
        const remembered = localStorage.getItem('admin_remember_login');
        if (remembered === 'true') {
            const savedUsername = localStorage.getItem('admin_username');
            if (savedUsername) document.getElementById('username').value = savedUsername;
            document.getElementById('remember').checked = true;
        }
        
        // Check for existing session
        const isLoggedIn = sessionStorage.getItem('admin_logged_in');
        if (isLoggedIn === 'true') {
            const loginTime = parseInt(sessionStorage.getItem('admin_login_time'));
            const currentTime = Date.now();
            const hoursSinceLogin = (currentTime - loginTime) / (1000 * 60 * 60);
            if (hoursSinceLogin < 2) {
                window.location.href = 'admin.php';
            } else {
                sessionStorage.removeItem('admin_logged_in');
                sessionStorage.removeItem('admin_username');
                sessionStorage.removeItem('admin_login_time');
            }
        }
    </script>
</body>
</html>
