<?php
require_once __DIR__ . '/_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>28800,'path'=>'/','httponly'=>true,'samesite'=>'Strict']);
    session_start();
}

if (!empty($_SESSION['ep_employee_id']) && !empty($_SESSION['ep_policy_agreed'])) {
    header('Location: ' . EP_URL . '/dashboard.php');
    exit;
}
// Temp employee already logged in
if (!empty($_SESSION['ep_is_temp']) && !empty($_SESSION['ep_temp_employee_id'])) {
    header('Location: ' . EP_URL . '/temp_portal.php');
    exit;
}

$error  = '';
$reason = $_GET['reason'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_number = trim($_POST['employee_number'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!$emp_number || !$password) {
        $error = 'Employee number and password are required.';
    } else {
        $stmt = db()->prepare("SELECT id, employee_number, first_name, last_name, portal_password, portal_active
            FROM employees WHERE employee_number = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$emp_number]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            // Fallback: check temp_employees table
            $stmtTmp = db()->prepare("SELECT id, employee_number, first_name, last_name, portal_password, portal_active
                FROM temp_employees WHERE employee_number = ? AND status = 'active' LIMIT 1");
            $stmtTmp->execute([$emp_number]);
            $tempEmp = $stmtTmp->fetch(PDO::FETCH_ASSOC);

            if (!$tempEmp) {
                $error = 'Employee number not found or account inactive.';
            } elseif (!$tempEmp['portal_active']) {
                $error = 'Portal access has been disabled for this account. Contact HR.';
            } elseif (!$tempEmp['portal_password']) {
                $error = 'No portal password set. Please contact HR to activate your portal account.';
            } elseif (!password_verify($password, $tempEmp['portal_password'])) {
                $error = 'Incorrect password.';
            } else {
                // Temp employee login success
                $_SESSION['ep_is_temp']          = true;
                $_SESSION['ep_temp_employee_id'] = $tempEmp['id'];
                $_SESSION['ep_employee_name']    = $tempEmp['first_name'] . ' ' . $tempEmp['last_name'];
                $_SESSION['ep_employee_num']     = $tempEmp['employee_number'];
                $_SESSION['ep_login_time']       = time();
                $_SESSION['ep_last_activity']    = time();
                db()->prepare("UPDATE temp_employees SET portal_last_login=NOW() WHERE id=?")->execute([$tempEmp['id']]);
                header('Location: ' . EP_URL . '/temp_portal.php');
                exit;
            }
        } elseif (!$emp['portal_active']) {
            $error = 'Portal access has been disabled for this account. Contact HR.';
        } elseif (!$emp['portal_password']) {
            $error = 'No portal password set. Please contact HR to activate your portal account.';
        } elseif (!password_verify($password, $emp['portal_password'])) {
            $error = 'Incorrect password.';
        } else {
            // Permanent employee login — store session, but NOT policy_agreed yet
            $_SESSION['ep_employee_id']   = $emp['id'];
            $_SESSION['ep_employee_name'] = $emp['first_name'] . ' ' . $emp['last_name'];
            $_SESSION['ep_employee_num']  = $emp['employee_number'];
            $_SESSION['ep_policy_agreed'] = false;
            $_SESSION['ep_login_time']    = time();
            $_SESSION['ep_last_activity'] = time();
            $_SESSION['ep_last_regen']    = time();

            db()->prepare("UPDATE employees SET portal_last_login=NOW() WHERE id=?")->execute([$emp['id']]);

            header('Location: ' . EP_URL . '/policy.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Employee Portal — Komagin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= EP_URL ?>/assets/portal.css">
</head>
<body>
<div class="ep-login-page">
    <div class="ep-login-box">
        <div class="ep-login-header">
            <?php
            $epLoginSettings = getCompanySettings();
            $epLoginLogoPath = $epLoginSettings['company_logo'] ?? null;
            $epLoginLogoSrc  = !empty($epLoginLogoPath) ? APP_URL . '/' . $epLoginLogoPath : null;
            ?>
            <?php if ($epLoginLogoSrc): ?>
            <img src="<?= htmlspecialchars($epLoginLogoSrc) ?>"
                 alt="Komagin"
                 style="height:64px;width:auto;margin-bottom:14px;display:block;margin-left:auto;margin-right:auto;max-width:none;">
            <?php else: ?>
            <div class="ep-login-logo">KOMAGIN</div>
            <?php endif; ?>
            <div class="ep-login-sub">Employee Self-Service Portal</div>
        </div>
        <div class="ep-login-form">
            <?php if ($reason === 'timeout'): ?>
                <div class="alert alert-warning">Session timed out. Please log in again.</div>
            <?php elseif ($reason === 'policy_expired'): ?>
                <div class="alert alert-danger">Policy agreement window expired. Please log in and agree to the policy within 5 minutes.</div>
            <?php elseif ($reason === 'logout'): ?>
                <div class="alert alert-info">You have been logged out successfully.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Employee Number</label>
                    <input type="text" name="employee_number" class="form-control"
                           placeholder="e.g. KOM-EMP-2024-0001"
                           value="<?= htmlspecialchars($_POST['employee_number'] ?? '') ?>"
                           required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Enter your portal password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Sign In
                </button>
            </form>
            <p style="font-size:0.72rem;color:#64748B;text-align:center;margin-top:20px">
                Forgot your password? Contact your HR department.
            </p>
        </div>
    </div>
</div>
</body>
</html>



