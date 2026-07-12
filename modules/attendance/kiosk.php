<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

// Kiosk is a standalone screen — no auth required for clock-in/out
session_start();

$settings = getAttendanceSettings();
$today = date('Y-m-d');
$now   = date('H:i:s');

// ── Kiosk state check ──────────────────────────────────────────────────────
// KOM-003: a request with no ?t= token used to fall back to "whichever
// kiosk_sessions row happens to be open" — with no login and no CSRF (this
// is deliberately a public, unauthenticated terminal page), that let anyone
// on the internet clock any guessable employee number in/out by simply
// omitting the token, no location binding required at all. Every kiosk
// session opened through kiosk_manage.php already has a token and HR is
// always given the token-bearing URL to bookmark on the physical terminal
// (see kiosk_manage.php's "Open Kiosk" flow) — nothing legitimate relies on
// the fallback. It's also wrong on its own terms the moment more than one
// location is open at once (kiosk_manage.php allows exactly that): the
// fallback picks an arbitrary single row, not necessarily the requesting
// terminal's actual location. A missing/invalid token now simply means "not
// a configured kiosk terminal" rather than "any open terminal will do."
$kioskToken    = trim($_GET['t'] ?? '');
$activeSession = null;
try {
    if ($kioskToken) {
        $sessStmt = db()->prepare("SELECT * FROM kiosk_sessions WHERE kiosk_token = ? LIMIT 1");
        $sessStmt->execute([$kioskToken]);
        $activeSession = $sessStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Exception $e) { /* table may not exist on first install */ }
$kioskOpen     = $activeSession !== null && $activeSession['status'] === 'open';
$locationName  = $activeSession['location_name'] ?? null;
$kioskNotConfigured = $kioskToken === '' || $activeSession === null;

$message = '';
$msgType = '';
$empInfo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $empNumber = strtoupper(trim($_POST['employee_number'] ?? ''));

    $validActions = ['sign_in','break_out','break_in','sign_out'];
    $ip           = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $sessionId    = $activeSession['id'] ?? null;

    // Rate limiting: block IP after 10 failed attempts in 5 minutes
    try {
        $failCount = db()->prepare("SELECT COUNT(*) FROM kiosk_audit WHERE ip_address=? AND result='error' AND action='failed_auth' AND recorded_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $failCount->execute([$ip]);
        $recentFails = (int)$failCount->fetchColumn();
    } catch (Exception $e) { $recentFails = 0; }

    if ($kioskNotConfigured) {
        $message = 'This terminal is not a configured kiosk location. Please contact HR.';
        $msgType = 'error';
    } elseif (!$kioskOpen) {
        $message = 'The attendance kiosk is currently closed. Please contact HR to open it.';
        $msgType = 'error';
    } elseif ($recentFails >= 10) {
        $message = 'Too many attempts from this terminal. Please wait 5 minutes before trying again.';
        $msgType = 'error';
    } elseif (!in_array($action, $validActions)) {
        $message = 'Invalid action selected.';
        $msgType = 'error';
    } elseif (empty($empNumber)) {
        $message = 'Please enter your Employee ID number.';
        $msgType = 'error';
    } else {
        // Employee ID-only authentication
        $emp = getEmployeeByNumber($empNumber);

        if (!$emp) {
            $message = 'Employee ID not found. Please check your employee number and try again.';
            $msgType = 'error';
            try {
                db()->prepare("INSERT INTO kiosk_audit (kiosk_session_id, employee_number, action, result, error_message, ip_address) VALUES (?,?,?,?,?,?)")
                    ->execute([$sessionId, $empNumber, 'failed_auth', 'error', 'Employee not found', $ip]);
            } catch (Exception $e) {}
        } elseif (!in_array($emp['status'], ['active','probation','on_leave'])) {
            $message = 'Access denied. Your account status does not permit kiosk access. Contact HR.';
            $msgType = 'error';
            try {
                db()->prepare("INSERT INTO kiosk_audit (kiosk_session_id, employee_id, employee_number, action, result, error_message, ip_address) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$sessionId, $emp['id'], $empNumber, 'failed_auth', 'error', 'Account status: '.$emp['status'], $ip]);
            } catch (Exception $e) {}
        } else {
            // Employee verified — process action
            $empId = $emp['id'];
            $currentTime = date('H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            // Get or create today's attendance record
            $stmt = db()->prepare("SELECT * FROM attendance WHERE employee_id = ? AND attendance_date = ?");
            $stmt->execute([$empId, $today]);
            $att = $stmt->fetch();

            $error = '';

            switch ($action) {
                case 'sign_in':
                    if ($att && $att['sign_in']) {
                        $error = "You are already signed in today at " . formatTime($att['sign_in']) . ".";
                    } else {
                        // Calculate lateness
                        $isLate = isLate($currentTime, $settings['work_start'], $settings['grace_period']);
                        $lateMinutes = 0;
                        if ($isLate) {
                            $workStart = strtotime(date('Y-m-d') . ' ' . $settings['work_start']);
                            $signInTs  = strtotime(date('Y-m-d') . ' ' . $currentTime);
                            $lateMinutes = (int)(($signInTs - $workStart - ($settings['grace_period'] * 60)) / 60);
                        }

                        if (!$att) {
                            db()->prepare("INSERT INTO attendance (employee_id, employee_number, attendance_date, sign_in, status, is_late, late_minutes, ip_address)
                                           VALUES (?,?,?,?,?,?,?,?)")
                                ->execute([$empId, $empNumber, $today, $currentTime, $isLate ? 'late' : 'present', $isLate ? 1 : 0, $lateMinutes, $ip]);
                        } else {
                            db()->prepare("UPDATE attendance SET sign_in=?, status=?, is_late=?, late_minutes=?, ip_address=? WHERE id=?")
                                ->execute([$currentTime, $isLate ? 'late' : 'present', $isLate ? 1 : 0, $lateMinutes, $ip, $att['id']]);
                        }

                        $empInfo = $emp;
                        $message = "Good " . (date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening')) . ", {$emp['first_name']}! Signed in at " . date('h:i A') . ($isLate ? " — <strong>Late by {$lateMinutes} min</strong>" : "");
                        $msgType = $isLate ? 'warning' : 'success';
                    }
                    break;

                case 'break_out':
                    if (!$att || !$att['sign_in']) {
                        $error = "You must sign in first before taking a break.";
                    } elseif ($att['break_out'] && !$att['break_in']) {
                        $error = "You are already on break since " . formatTime($att['break_out']) . ".";
                    } elseif ($att['sign_out']) {
                        $error = "You have already signed out today.";
                    } else {
                        db()->prepare("UPDATE attendance SET break_out=?, ip_address=? WHERE id=?")
                            ->execute([$currentTime, $ip, $att['id']]);
                        $empInfo = $emp;
                        $message = "Break started at " . date('h:i A') . ". Enjoy your break, {$emp['first_name']}!";
                        $msgType = 'info';
                    }
                    break;

                case 'break_in':
                    if (!$att || !$att['break_out']) {
                        $error = "You haven't started a break yet.";
                    } elseif ($att['break_in']) {
                        $error = "You already returned from break at " . formatTime($att['break_in']) . ".";
                    } else {
                        // Calculate break duration
                        $breakStart = strtotime(date('Y-m-d') . ' ' . $att['break_out']);
                        $breakEnd   = strtotime(date('Y-m-d') . ' ' . $currentTime);
                        $breakMins  = max(0, (int)(($breakEnd - $breakStart) / 60));

                        db()->prepare("UPDATE attendance SET break_in=?, break_duration_minutes=?, ip_address=? WHERE id=?")
                            ->execute([$currentTime, $breakMins, $ip, $att['id']]);
                        $empInfo = $emp;
                        $message = "Welcome back, {$emp['first_name']}! Break: " . minutesToHoursMinutes($breakMins) . ".";
                        $msgType = 'success';
                    }
                    break;

                case 'sign_out':
                    if (!$att || !$att['sign_in']) {
                        $error = "You must sign in first.";
                    } elseif ($att['sign_out']) {
                        $error = "You have already signed out today at " . formatTime($att['sign_out']) . ".";
                    } elseif ($att['break_out'] && !$att['break_in']) {
                        $error = "Please return from break before signing out.";
                    } else {
                        // Calculate hours
                        $breakMins = (int)($att['break_duration_minutes'] ?? 0);
                        $totalHours = calculateHours($att['sign_in'], $currentTime, $breakMins);
                        $normalHours = min($totalHours, (float)$settings['standard_hours']);
                        $otHours = max(0, $totalHours - $settings['overtime_threshold']);

                        $isEarlyDep = false;
                        $workEndTs = strtotime(date('Y-m-d') . ' ' . $settings['work_end']);
                        if (strtotime(date('Y-m-d') . ' ' . $currentTime) < $workEndTs) {
                            $isEarlyDep = true;
                        }

                        db()->prepare("UPDATE attendance SET sign_out=?, total_hours_worked=?, normal_hours=?, overtime_hours=?, is_early_departure=?, ip_address=? WHERE id=?")
                            ->execute([$currentTime, $totalHours, $normalHours, $otHours, $isEarlyDep ? 1 : 0, $ip, $att['id']]);

                        // Create overtime suggestion if > 0
                        if ($otHours > 0) {
                            $attId = $att['id'];
                            $existing = db()->prepare("SELECT id FROM overtime_records WHERE attendance_id=?");
                            $existing->execute([$attId]);
                            if (!$existing->fetch()) {
                                db()->prepare("INSERT INTO overtime_records (attendance_id, employee_id, overtime_date, suggested_hours, status) VALUES (?,?,?,?,'pending')")
                                    ->execute([$attId, $empId, $today, $otHours]);
                            }
                        }

                        $empInfo = $emp;
                        $message = "Goodbye, {$emp['first_name']}! Total hours: <strong>" . number_format($totalHours, 2) . " hrs</strong>" . ($otHours > 0 ? " (OT: {$otHours} hrs pending approval)" : "") . ($isEarlyDep ? " — Early departure noted." : "");
                        $msgType = 'success';
                    }
                    break;
            }

            if ($error) {
                $message = $error;
                $msgType = 'error';
                try {
                    db()->prepare("INSERT INTO kiosk_audit (kiosk_session_id, employee_id, employee_number, action, result, error_message, ip_address) VALUES (?,?,?,?,?,?,?)")
                        ->execute([$activeSession['id'] ?? null, $empId, $empNumber, $action, 'error', $error, $ip]);
                } catch (Exception $e) {}
            } else {
                // Log successful action
                try {
                    db()->prepare("INSERT INTO kiosk_audit (kiosk_session_id, employee_id, employee_number, action, result, ip_address) VALUES (?,?,?,?,?,?)")
                        ->execute([$activeSession['id'] ?? null, $empId, $empNumber, $action, 'success', $ip]);
                } catch (Exception $e) {}
            }
        }
    }
}

$companySettings = getCompanySettings();
$companyName = $companySettings['company_name'] ?? 'Komagin Limited';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Attendance Kiosk – <?= e($companyName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: #0F172A;
            color: #E2E8F0;
            overflow: hidden;
        }
        body { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }

        .kiosk-wrapper { width: 100%; max-width: 440px; }

        .kiosk-brand {
            text-align: center;
            margin-bottom: 24px;
        }
        .kiosk-brand-name { font-size: 1.1rem; font-weight: 700; color: #94A3B8; letter-spacing: .03em; }
        .kiosk-brand-sub  { font-size: 0.72rem; color: #475569; margin-top: 2px; }

        .kiosk-clock {
            text-align: center;
            margin-bottom: 28px;
        }
        .kiosk-time {
            font-size: 3.5rem;
            font-weight: 800;
            color: #F8FAFC;
            letter-spacing: -0.04em;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }
        .kiosk-date {
            font-size: 0.82rem;
            color: #64748B;
            margin-top: 6px;
        }

        .kiosk-card {
            background: #1E293B;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 28px;
        }

        /* Result banner */
        .kiosk-result {
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .kiosk-result.success { background: #14532D; border: 1px solid #166534; color: #86efac; }
        .kiosk-result.warning { background: #78350F; border: 1px solid #92400E; color: #fcd34d; }
        .kiosk-result.info    { background: #1e3a5f; border: 1px solid #1D4ED8; color: #93c5fd; }
        .kiosk-result.error   { background: #450a0a; border: 1px solid #7f1d1d; color: #fca5a5; }
        .kiosk-result .emp-badge {
            font-size: 1.1rem;
            font-weight: 800;
            display: block;
            margin-bottom: 4px;
            color: #F8FAFC;
        }

        /* Action Buttons */
        .action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 20px;
        }
        .action-btn {
            background: #0F172A;
            border: 2px solid #334155;
            border-radius: 10px;
            color: #94A3B8;
            font-family: 'Inter', sans-serif;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 12px 8px;
            cursor: pointer;
            transition: all 0.15s;
            text-align: center;
        }
        .action-btn:hover { border-color: #64748B; color: #E2E8F0; background: #1E293B; }
        .action-btn.active { border-color: #1D4ED8; color: #60a5fa; background: #172554; }
        .action-btn.active:hover { border-color: #3b82f6; }
        .action-btn .btn-icon-wrap {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
        }
        .action-btn.sign_in .btn-icon-wrap  { background: rgba(34,197,94,.15);  color: #4ade80; }
        .action-btn.break_out .btn-icon-wrap{ background: rgba(245,158,11,.15); color: #fbbf24; }
        .action-btn.break_in .btn-icon-wrap { background: rgba(59,130,246,.15); color: #60a5fa; }
        .action-btn.sign_out .btn-icon-wrap { background: rgba(239,68,68,.15);  color: #f87171; }
        .action-btn.active.sign_in  { border-color: #22c55e; color: #4ade80; background: rgba(34,197,94,.08); }
        .action-btn.active.break_out{ border-color: #f59e0b; color: #fbbf24; background: rgba(245,158,11,.08); }
        .action-btn.active.break_in { border-color: #3b82f6; color: #60a5fa; background: rgba(59,130,246,.08); }
        .action-btn.active.sign_out { border-color: #ef4444; color: #f87171; background: rgba(239,68,68,.08); }

        /* Form fields */
        .form-field { margin-bottom: 14px; }
        .form-field label { display: block; font-size: 0.7rem; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
        .form-field input {
            width: 100%;
            height: 44px;
            background: #0F172A;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #F8FAFC;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            padding: 0 14px;
            transition: border-color .15s;
        }
        .form-field input:focus { outline: none; border-color: #1D4ED8; box-shadow: 0 0 0 3px rgba(29,78,216,0.15); }
        .form-field input::placeholder { color: #475569; }
        input[type="password"] { letter-spacing: .2em; }
        input[type="password"]::placeholder { letter-spacing: 0; }

        .submit-btn {
            width: 100%;
            height: 48px;
            background: #1D4ED8;
            border: none;
            border-radius: 10px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s;
            letter-spacing: .01em;
        }
        .submit-btn:hover { background: #1e40af; }
        .submit-btn:active { background: #1d3a99; }

        .kiosk-footer {
            text-align: center;
            margin-top: 16px;
            font-size: 0.65rem;
            color: #334155;
        }
        .kiosk-footer a { color: #475569; }

        /* PIN dots */
        .pin-dots {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 8px 0;
        }
        .pin-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 1px solid #334155;
            background: transparent;
            transition: background .1s, border-color .1s;
        }
        .pin-dot.filled { background: #1D4ED8; border-color: #1D4ED8; }
    </style>
</head>
<body>
<div class="kiosk-wrapper">

    <div class="kiosk-brand">
        <?php
        $kioskSettings = getCompanySettings();
        $kioskLogoPath = $kioskSettings['company_logo'] ?? null;
        $kioskLogoSrc  = !empty($kioskLogoPath) ? APP_URL . '/' . $kioskLogoPath : null;
        ?>
        <?php if ($kioskLogoSrc): ?>
        <img src="<?= htmlspecialchars($kioskLogoSrc) ?>"
             alt="<?= e($companyName) ?>"
             style="height:70px;width:auto;margin-bottom:10px;display:block;margin-left:auto;margin-right:auto;max-width:none;">
        <?php endif; ?>
        <div class="kiosk-brand-name"><?= e($companyName) ?></div>
        <div class="kiosk-brand-sub"><?= $locationName ? e($locationName) . ' — Attendance Kiosk' : 'Employee Attendance Kiosk' ?></div>
    </div>

    <div class="kiosk-clock">
        <div class="kiosk-time" id="kioskClock">00:00:00 AM</div>
        <div class="kiosk-date" id="kioskDate"></div>
    </div>

    <div class="kiosk-card">

        <?php if ($kioskNotConfigured): ?>
        <div class="kiosk-result error" style="text-align:center;flex-direction:column;gap:12px;padding:24px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto;opacity:.7;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <div>
                <strong style="display:block;font-size:1rem;margin-bottom:6px;">Not a Configured Kiosk Terminal</strong>
                <span style="font-size:0.8rem;opacity:.8;">This link does not correspond to a known kiosk location.<br>Please contact HR Administration for the correct terminal URL.</span>
            </div>
        </div>
        <?php elseif (!$kioskOpen): ?>
        <div class="kiosk-result error" style="text-align:center;flex-direction:column;gap:12px;padding:24px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto;opacity:.7;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <div>
                <strong style="display:block;font-size:1rem;margin-bottom:6px;">
                    <?= $locationName ? e($locationName) . ' Kiosk is Closed' : 'Kiosk is Closed' ?>
                </strong>
                <span style="font-size:0.8rem;opacity:.8;">This attendance kiosk is currently offline.<br>Please contact HR Administration to open it.</span>
            </div>
        </div>
        <?php endif; ?>
        <!-- Notifications now handled by toast (top of page) -->

        <?php if ($kioskOpen): ?>
        <form method="POST" action="<?= $kioskToken ? '?t=' . urlencode($kioskToken) : '' ?>" id="kioskForm">
            <!-- Action Selection -->
            <div class="action-grid" style="margin-bottom:16px;">
                <?php
                $actions = [
                    ['sign_in',   'Sign In',   '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                    ['break_out', 'Break Out', '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
                    ['break_in',  'Break In',  '<path d="M21 2H3v16h5l3 3 3-3h7V2z"/><line x1="12" y1="7" x2="12" y2="11"/><line x1="12" y1="15" x2="12.01" y2="15"/>'],
                    ['sign_out',  'Sign Out',  '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>'],
                ];
                $selectedAction = $_POST['action'] ?? 'sign_in';
                foreach ($actions as [$val, $label, $iconPath]):
                ?>
                <button type="button"
                        class="action-btn <?= $val ?> <?= $selectedAction === $val ? 'active' : '' ?>"
                        onclick="selectAction('<?= $val ?>')">
                    <div class="btn-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $iconPath ?></svg>
                    </div>
                    <?= $label ?>
                </button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="action" id="actionInput" value="<?= e($selectedAction) ?>">

            <!-- Employee Number -->
            <div class="form-field">
                <label>Employee Number</label>
                <input type="text" name="employee_number"
                       placeholder="e.g. KOM-EMP-2026-0001"
                       value="<?= e(strtoupper($_POST['employee_number'] ?? '')) ?>"
                       autocomplete="off" autocorrect="off" autocapitalize="characters"
                       id="empInput">
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                Clock Action
            </button>
            <div style="text-align:center;font-size:0.62rem;color:rgba(255,255,255,0.25);margin-top:10px;">
                No PIN required · Kiosk must be opened by HR Admin
            </div>
        </form>
        <?php endif; // kioskOpen ?>
    </div>

    <div class="kiosk-footer">
        <?= date('Y') ?> <?= e($companyName) ?>
    </div>
</div>

<style>
/* Toast notification */
.kiosk-toast {
    position: fixed;
    top: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(-80px);
    min-width: 320px;
    max-width: 480px;
    border-radius: 14px;
    padding: 16px 22px;
    font-family: 'Inter', sans-serif;
    font-size: 0.92rem;
    font-weight: 600;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0,0,0,0.45);
    z-index: 9999;
    opacity: 0;
    transition: transform 0.35s cubic-bezier(.22,1,.36,1), opacity 0.35s ease;
    pointer-events: none;
    line-height: 1.5;
}
.kiosk-toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
.kiosk-toast.success {
    background: #14532D;
    border: 2px solid #22C55E;
    color: #86efac;
}
.kiosk-toast.error {
    background: #450a0a;
    border: 2px solid #EF4444;
    color: #fca5a5;
}
.kiosk-toast.warning {
    background: #78350F;
    border: 2px solid #F59E0B;
    color: #fcd34d;
}
.kiosk-toast.info {
    background: #1e3a5f;
    border: 2px solid #3B82F6;
    color: #93c5fd;
}
.kiosk-toast .toast-emp {
    display: block;
    font-size: 1.05rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 4px;
}
.kiosk-toast .toast-icon {
    font-size: 1.6rem;
    display: block;
    margin-bottom: 8px;
}
</style>

<!-- Toast container -->
<div id="kioskToast" class="kiosk-toast" role="alert"></div>

<script>
const PNG_TZ = 'Pacific/Port_Moresby';

function selectAction(action) {
    document.getElementById('actionInput').value = action;
    document.querySelectorAll('.action-btn').forEach(b => {
        b.classList.toggle('active', b.classList.contains(action));
    });
    const labels = { sign_in:'Sign In', break_out:'Break Out', break_in:'Break In', sign_out:'Sign Out' };
    const btn = document.getElementById('submitBtn');
    if (btn) btn.textContent = labels[action] || 'Submit';
    const emp = document.getElementById('empInput');
    if (emp) emp.focus();
}

// ── Live clock — always shows PNG time (UTC+10) ────────────────────────────
function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-AU', {
        timeZone: PNG_TZ,
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
    });
    const dateStr = now.toLocaleDateString('en-AU', {
        timeZone: PNG_TZ,
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
    document.getElementById('kioskClock').textContent = timeStr;
    document.getElementById('kioskDate').textContent  = dateStr;
}
updateClock();
setInterval(updateClock, 1000);

// ── Toast notification system ─────────────────────────────────────────────
function showToast(message, type, empName, duration) {
    const toast  = document.getElementById('kioskToast');
    const icons  = { success: '✅', error: '❌', warn: '⚠', info: 'ℹ' };
    const icon   = icons[type] || icons['warn'] || '';
    duration     = duration || (type === 'success' ? 4500 : 5000);

    toast.className = 'kiosk-toast ' + type;
    toast.innerHTML = (icon ? '<span class="toast-icon">' + icon + '</span>' : '') +
        (empName ? '<span class="toast-emp">' + empName + '</span>' : '') +
        message;

    // Force reflow then show
    void toast.offsetWidth;
    toast.classList.add('show');

    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

// ── Show toast from PHP result ────────────────────────────────────────────
<?php if ($message): ?>
(function() {
    const type    = <?= json_encode($msgType) ?>;
    const msg     = <?= json_encode(strip_tags($message)) ?>;
    const empName = <?= $empInfo ? json_encode($empInfo['first_name'].' '.$empInfo['last_name']) : 'null' ?>;
    showToast(msg, type, empName);

    <?php if ($msgType === 'success' || $msgType === 'info'): ?>
    // Auto-clear form after successful action
    setTimeout(() => {
        const empInput = document.getElementById('empInput');
        if (empInput) { empInput.value = ''; empInput.focus(); }
    }, 4500);
    <?php endif; ?>
})();
<?php endif; ?>

// Init button label
selectAction(document.getElementById('actionInput').value || 'sign_in');
</script>
</body>
</html>




