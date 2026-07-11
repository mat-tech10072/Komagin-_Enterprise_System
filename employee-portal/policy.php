<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';

// Must be logged in but not yet agreed to policy
if (empty($_SESSION['ep_employee_id'])) {
    header('Location: ' . EP_URL . '/login.php');
    exit;
}
if (!empty($_SESSION['ep_policy_agreed'])) {
    header('Location: ' . EP_URL . '/dashboard.php');
    exit;
}

// Enforce 5-minute window from login time
$loginTime     = $_SESSION['ep_login_time'] ?? time();
$elapsed       = time() - $loginTime;
$limit         = 300; // 5 minutes
$remaining     = max(0, $limit - $elapsed);

if ($elapsed > $limit) {
    session_destroy();
    header('Location: ' . EP_URL . '/login.php?reason=policy_expired');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agree'])) {
    if ($elapsed > $limit) {
        session_destroy();
        header('Location: ' . EP_URL . '/login.php?reason=policy_expired');
        exit;
    }
    $_SESSION['ep_policy_agreed'] = true;
    db()->prepare("UPDATE employees SET portal_policy_agreed=1, portal_policy_agreed_at=NOW() WHERE id=?")
        ->execute([$_SESSION['ep_employee_id']]);
    header('Location: ' . EP_URL . '/dashboard.php');
    exit;
}

$empName = $_SESSION['ep_employee_name'] ?? 'Employee';
$empNum  = $_SESSION['ep_employee_num']  ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Portal Policy — Komagin Employee Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= EP_URL ?>/assets/portal.css">
<style>
body { background: linear-gradient(135deg,#0F172A 0%,#1D4ED8 100%); }
.policy-wrap { padding: 40px 16px; }
.policy-header { text-align:center; margin-bottom:24px; }
.policy-header h1 { color:#fff; font-size:1.4rem; font-weight:800; }
.policy-header p  { color:rgba(255,255,255,.6); font-size:0.8rem; margin-top:4px; }
.timer-bar { height:4px; background:rgba(255,255,255,.2); border-radius:2px; overflow:hidden; margin-bottom:24px; }
.timer-bar-fill { height:100%; background:#DC2626; transition: width 1s linear; }
</style>
</head>
<body>
<div class="policy-wrap">
    <div class="policy-header">
        <h1>KOMAGIN Employee Portal</h1>
        <p>Welcome, <?= htmlspecialchars($empName) ?> &mdash; you must agree to the Portal Usage Policy before continuing.</p>
    </div>

    <div class="timer-bar">
        <div class="timer-bar-fill" id="timerBar" style="width:100%"></div>
    </div>

    <div class="policy-box" style="max-width:720px;margin:0 auto">
        <div style="padding:14px 24px;background:#0F172A;color:#fff;display:flex;align-items:center;justify-content:space-between;border-radius:8px 8px 0 0">
            <span style="font-weight:700;font-size:0.88rem">Employee Portal Usage Policy</span>
            <span class="policy-timer" id="timerDisplay">5:00 remaining</span>
        </div>
        <div class="policy-body">
            <h3>1. Purpose</h3>
            <p>This Employee Self-Service Portal ("Portal") is provided by Komagin HR Management exclusively for employees to access their personal employment information, payroll records, savings progress, and to submit HR requests.</p>

            <h3>2. Authorised Use</h3>
            <p>Access to this Portal is restricted to current, active employees of Komagin. You may only view and manage your own information. Sharing your login credentials with any other person is strictly prohibited.</p>
            <p>The Portal may be used to: view employment details, access payslips and pay history, monitor savings and fund contributions, and submit requests to the HR department.</p>

            <h3>3. Confidentiality</h3>
            <p>All information accessible through this Portal — including salary figures, deduction amounts, and savings balances — is strictly confidential. You agree not to disclose, share, or replicate this information to any third party except where required by law.</p>

            <h3>4. Security Responsibilities</h3>
            <p>You are responsible for maintaining the confidentiality of your portal password. You must immediately notify HR if you suspect unauthorised access to your account. Komagin accepts no liability for losses arising from your failure to maintain password security.</p>
            <p>Sessions are automatically terminated after 8 hours of inactivity or when you log out.</p>

            <h3>5. Data Accuracy</h3>
            <p>While Komagin endeavours to ensure the accuracy of information presented on this Portal, the data is for informational purposes only. Payslips and savings figures are subject to final payroll sign-off. Discrepancies should be reported via the Hub module.</p>

            <h3>6. Monitoring</h3>
            <p>Your use of this Portal is logged for security and audit purposes. Komagin reserves the right to monitor portal access to ensure compliance with this policy and applicable laws.</p>

            <h3>7. Misuse</h3>
            <p>Any attempt to access another employee's information, manipulate data, or circumvent security controls will result in immediate account suspension and may lead to disciplinary action up to and including termination.</p>

            <h3>8. Acceptance</h3>
            <p>By clicking "I Agree &amp; Continue", you confirm that you have read, understood, and agree to this Portal Usage Policy. You acknowledge that failure to comply may result in disciplinary action.</p>
            <p><strong>This agreement is recorded with a timestamp and linked to your employee profile.</strong></p>
        </div>
        <form method="POST">
            <div class="policy-footer">
                <div>
                    <label style="display:flex;align-items:center;gap:8px;font-size:0.78rem;cursor:pointer">
                        <input type="checkbox" id="agreeCheck" required style="width:16px;height:16px">
                        I have read and agree to the Portal Usage Policy
                    </label>
                </div>
                <div style="display:flex;align-items:center;gap:12px">
                    <span class="policy-timer" id="timerDisplayFooter">5:00 remaining</span>
                    <a href="<?= EP_URL ?>/logout.php" class="btn btn-secondary btn-sm">Decline &amp; Logout</a>
                    <button type="submit" name="agree" class="btn btn-primary btn-sm" id="agreeBtn" disabled>
                        I Agree &amp; Continue
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const LIMIT = <?= $limit ?>;
const loginTime = <?= $loginTime ?>;

function updateTimer() {
    const now = Math.floor(Date.now() / 1000);
    const elapsed = now - loginTime;
    const remaining = Math.max(0, LIMIT - elapsed);

    const mins = Math.floor(remaining / 60);
    const secs = remaining % 60;
    const display = mins + ':' + String(secs).padStart(2,'0') + ' remaining';

    document.getElementById('timerDisplay').textContent = display;
    document.getElementById('timerDisplayFooter').textContent = display;

    const pct = (remaining / LIMIT) * 100;
    document.getElementById('timerBar').style.width = pct + '%';

    if (remaining <= 60) {
        document.getElementById('timerDisplay').style.color = '#DC2626';
        document.getElementById('timerDisplayFooter').style.color = '#DC2626';
    }

    if (remaining <= 0) {
        window.location.href = '<?= EP_URL ?>/logout.php?reason=policy_expired';
    }
}

document.getElementById('agreeCheck').addEventListener('change', function() {
    document.getElementById('agreeBtn').disabled = !this.checked;
});

updateTimer();
setInterval(updateTimer, 1000);
</script>
</body>
</html>
