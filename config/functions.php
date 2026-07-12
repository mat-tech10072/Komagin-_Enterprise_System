<?php
require_once __DIR__ . '/database.php';

// ============================================================
// SECURITY
// ============================================================

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize(?string $value): string {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// ============================================================
// SESSION & AUTH
// ============================================================

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

function currentUser(): array {
    if (!isLoggedIn()) return [];
    // Cache in static variable — one DB hit per request max
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = db()->prepare("SELECT u.*,
        COALESCE(e.first_name, u.first_name) AS first_name,
        COALESCE(e.last_name,  u.last_name)  AS last_name,
        COALESCE(e.photo, u.profile_photo)   AS photo,
        e.employee_number
        FROM users u
        LEFT JOIN employees e ON u.employee_id = e.id
        WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cache = $stmt->fetch() ?: [];
    return $cache;
}

function _loadRolePermissions(): array {
    static $loaded = false;
    static $matrix = [];
    if ($loaded) return $matrix;
    $loaded = true;
    if (!isLoggedIn()) return $matrix;
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'super_admin') return $matrix; // super_admin bypasses — no need to load
    $stmt = db()->prepare(
        "SELECT p.slug, rp.can_view, rp.can_create, rp.can_edit, rp.can_delete,
                rp.can_approve, rp.can_export, rp.can_publish, rp.can_share
         FROM role_permissions rp
         JOIN permissions p ON rp.permission_id = p.id
         WHERE rp.role = ?"
    );
    $stmt->execute([$role]);
    foreach ($stmt->fetchAll() as $row) {
        $matrix[$row['slug']] = $row;
    }
    return $matrix;
}

// ============================================================
// CANONICAL AUTHORIZATION LAYER
//
// hasPermission()/requirePermission() are the ONE authorization
// primitive for this application. Every module-level access check
// and every state-changing action must call through here — do not
// write a new hardcoded role check (in_array($_SESSION['user_role'], ...))
// anywhere outside this file. If a hardcoded check seems necessary,
// it means a permission slug/action is missing from the matrix; add
// the slug via a migration and grant it to the appropriate roles
// instead of branching on role name in module code.
//
// $action is intentionally required, not defaulted. A missing action
// argument used to silently fall back to checking can_view — meaning
// a delete/create/approve/publish/export/share action could be
// authorized by a role that only had VIEW rights on that permission
// slug. Every call site must say out loud which of the 8 action
// columns (view, create, edit, delete, approve, export, publish,
// share) it is actually authorizing.
// ============================================================

const PERMISSION_ACTIONS = ['view','create','edit','delete','approve','export','publish','share'];

function hasPermission(string $permission, string $action): bool {
    if (!in_array($action, PERMISSION_ACTIONS, true)) {
        throw new \InvalidArgumentException("Unknown permission action '$action' for '$permission' — must be one of: " . implode(', ', PERMISSION_ACTIONS));
    }
    if (!isLoggedIn()) return false;
    if ($_SESSION['user_role'] === 'super_admin') return true;
    $matrix = _loadRolePermissions();
    if (!isset($matrix[$permission])) return false;
    $col = 'can_' . $action;
    return isset($matrix[$permission][$col]) && (bool)$matrix[$permission][$col];
}

function canView(string $permission): bool    { return hasPermission($permission, 'view'); }
function canCreate(string $permission): bool  { return hasPermission($permission, 'create'); }
function canEdit(string $permission): bool    { return hasPermission($permission, 'edit'); }
function canDelete(string $permission): bool  { return hasPermission($permission, 'delete'); }
function canApprove(string $permission): bool { return hasPermission($permission, 'approve'); }
function canExport(string $permission): bool  { return hasPermission($permission, 'export'); }
function canPublish(string $permission): bool { return hasPermission($permission, 'publish'); }
function canShare(string $permission): bool   { return hasPermission($permission, 'share'); }

function requirePermission(string $permission, string $action): void {
    requireLogin();
    if (!hasPermission($permission, $action)) {
        // Audit the denied access attempt
        $userId   = $_SESSION['user_id']   ?? null;
        $userName = $_SESSION['user_name'] ?? 'unknown';
        $role     = $_SESSION['user_role'] ?? 'unknown';
        $url      = $_SERVER['REQUEST_URI'] ?? 'unknown';
        try {
            $db = db();
            $db->prepare("INSERT INTO audit_logs (user_id, user_name, module, action, reason, ip_address, created_at)
                VALUES (?,?,?,?,?,?,NOW())")
                ->execute([$userId, $userName, 'security', 'access_denied',
                    "Role '$role' denied: $permission.$action at $url",
                    $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (\Exception $e) { /* non-fatal */ }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied.']);
            exit;
        }
        setFlash('error', 'You do not have permission to access that page.');
        header('Location: ' . APP_URL . '/dashboard.php?error=access_denied');
        exit;
    }
}

function currentUserPermissions(): array {
    if (!isLoggedIn()) return [];
    if ($_SESSION['user_role'] === 'super_admin') return ['__super_admin__' => true];
    return _loadRolePermissions();
}

// ============================================================
// SERVER-SIDE ROLE VALIDATION
//
// Any code path that assigns/changes a users.role value must
// validate against this list, never trust a client-submitted role
// string directly. assignableRoles() additionally encodes "who is
// allowed to grant which roles" — only super_admin may grant
// super_admin, so a lower-privileged admin with users.manage rights
// cannot escalate themselves or anyone else to super_admin by
// crafting a POST body.
// ============================================================

const VALID_USER_ROLES = [
    'super_admin', 'hr_manager', 'hr_officer', 'supervisor', 'employee',
    'finance_viewer', 'payroll_manager', 'payroll_officer',
    'recruitment_officer', 'training_officer', 'kiosk_terminal',
];

function assignableRoles(): array {
    if (($_SESSION['user_role'] ?? '') === 'super_admin') {
        return VALID_USER_ROLES;
    }
    // Non-super_admin users (even those holding users.manage) may never grant super_admin.
    return array_values(array_diff(VALID_USER_ROLES, ['super_admin']));
}

function isValidAssignableRole(string $role): bool {
    return in_array($role, assignableRoles(), true);
}

// ============================================================
// RECORD-LEVEL AUTHORIZATION
//
// Module-level permissions (documents.view etc.) answer "can this
// role use this feature at all." They do not answer "should this
// specific user see this specific record." Use these helpers for
// resources where the two questions can have different answers.
// ============================================================

function canAccessGeneratedDocument(array $doc): bool {
    // Approved/issued documents are the module's finished output — anyone
    // holding documents.view (an HR-tier permission by design) may view them.
    if (in_array($doc['status'] ?? '', ['approved', 'issued'], true)) {
        return hasPermission('documents.view', 'view');
    }
    // Drafts and pending-approval documents are work in progress: visible
    // only to the person who generated them, or to someone who can verify/
    // approve documents — not to every documents.view holder by default.
    $isOwner = isLoggedIn() && (int)($doc['generated_by'] ?? 0) === (int)($_SESSION['user_id'] ?? 0);
    return $isOwner || hasPermission('documents.verify', 'approve');
}

// ============================================================
// HR / PAYROLL SEPARATION CONTROLS
// ============================================================

function canViewSalaryData(): bool {
    // Only roles with explicit payroll.view permission can see salary fields
    return hasPermission('payroll.view', 'view');
}

function canViewBankData(): bool {
    // Bank details are payroll-sensitive; require payroll.view
    return hasPermission('payroll.view', 'view');
}

function canViewPayrollBreakdown(): bool {
    // Full payslip breakdown (tax, UIF, deductions) requires payroll.payslips
    return hasPermission('payroll.payslips', 'view');
}

function canViewPersonalHRData(): bool {
    // Disciplinary records, performance, personal notes — HR domain
    return hasPermission('employees.view', 'view') && !isPurePayrollRole();
}

function isPurePayrollRole(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, ['payroll_officer', 'payroll_manager', 'finance_viewer', 'kiosk_terminal']);
}

function isHRRole(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, ['super_admin', 'hr_manager', 'hr_officer', 'supervisor', 'recruitment_officer', 'training_officer']);
}

function maskSalary(mixed $value): string {
    if (canViewSalaryData()) return e(number_format((float)$value, 2));
    return '<span style="filter:blur(4px);user-select:none;pointer-events:none;">████████</span>';
}

function maskBankField(?string $value): string {
    if (canViewBankData()) return e($value ?? '—');
    return '<span style="filter:blur(4px);user-select:none;pointer-events:none;">████████</span>';
}

function logCrossModuleAccess(string $module, string $dataType, int $employeeId): void {
    $role = $_SESSION['user_role'] ?? 'unknown';
    $sensitiveAccess = [
        'payroll_reading_hr'  => isPurePayrollRole() && in_array($dataType, ['disciplinary','performance','personal_notes']),
        'hr_reading_payroll'  => isHRRole() && in_array($dataType, ['salary','bank','tax','deductions']) && !canViewSalaryData(),
    ];
    foreach ($sensitiveAccess as $type => $triggered) {
        if ($triggered) {
            auditLog($module, 'cross_module_access_attempt', $employeeId, null,
                json_encode(['type'=>$type,'data'=>$dataType,'role'=>$role]));
        }
    }
}

// ============================================================
// EMPLOYEE NUMBERS
// ============================================================

function generateEmployeeNumber(): string {
    $year = date('Y');
    $stmt = db()->prepare("SELECT emp_number_settings FROM company_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();

    $prefix = EMP_PREFIX;
    $length = EMP_NUMBER_LENGTH;

    if ($settings && !empty($settings['emp_number_settings'])) {
        $numSettings = json_decode($settings['emp_number_settings'], true);
        $prefix = $numSettings['prefix'] ?? EMP_PREFIX;
        $length = $numSettings['number_length'] ?? EMP_NUMBER_LENGTH;
    }

    $stmt = db()->prepare("SELECT COUNT(*) as cnt FROM employees WHERE employee_number LIKE ?");
    $stmt->execute([$prefix . '-' . $year . '-%']);
    $count = $stmt->fetchColumn() + 1;

    return $prefix . '-' . $year . '-' . str_pad($count, $length, '0', STR_PAD_LEFT);
}

// ============================================================
// AUDIT LOGGING
// ============================================================

function auditLog(string $module, string $action, ?int $recordId = null,
                  ?string $oldValue = null, ?string $newValue = null,
                  ?string $reason = null): void {
    try {
        $stmt = db()->prepare("INSERT INTO audit_logs
            (user_id, user_name, module, action, record_id, old_value, new_value, reason, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $_SESSION['user_name'] ?? 'System',
            $module,
            $action,
            $recordId,
            $oldValue,
            $newValue,
            $reason,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

// ============================================================
// PORTAL BRUTE-FORCE PROTECTION
//
// employees/temp_employees/consultants have no login_attempts/locked_until
// columns (unlike the admin-surface users table), and Phase 2's charter
// forbids a database redesign to add them. This reuses the existing
// audit_logs table instead — the same "count recent failed attempts in a
// rolling window" pattern already proven correct by the attendance kiosk's
// rate limiting — so both portal logins get real brute-force protection
// with zero schema change.
// ============================================================

function portalLoginBlocked(string $module, int $maxAttempts = 5, int $windowMinutes = 15): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip === '') return false;
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM audit_logs
         WHERE module = ? AND action = 'failed_login' AND ip_address = ?
           AND created_at > (NOW() - INTERVAL ? MINUTE)"
    );
    $stmt->execute([$module, $ip, $windowMinutes]);
    return (int)$stmt->fetchColumn() >= $maxAttempts;
}

function recordPortalLoginFailure(string $module, string $identifier): void {
    auditLog($module, 'failed_login', null, null, null, "identifier=$identifier");
}

// ============================================================
// NOTIFICATIONS
// ============================================================

function createNotification(int $userId, string $type, string $title,
                             string $message, ?string $link = null): void {
    try {
        $stmt = db()->prepare("INSERT INTO notifications (user_id, type, title, message, link, created_at)
                               VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $type, $title, $message, $link]);
    } catch (Exception $e) {
        error_log('Notification creation failed: ' . $e->getMessage());
    }
}

function notifyRole(string $role, string $type, string $title,
                    string $message, ?string $link = null): void {
    $stmt = db()->prepare("SELECT id FROM users WHERE role = ? AND is_active = 1");
    $stmt->execute([$role]);
    $users = $stmt->fetchAll();
    foreach ($users as $user) {
        createNotification($user['id'], $type, $title, $message, $link);
    }
}

function getUnreadNotificationCount(int $userId): int {
    // Cache in session for 60 seconds to avoid a query on every page load
    $key = 'notif_count_' . $userId;
    if (isset($_SESSION[$key], $_SESSION[$key . '_ts']) && (time() - $_SESSION[$key . '_ts']) < 60) {
        return (int)$_SESSION[$key];
    }
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $count = (int)$stmt->fetchColumn();
    $_SESSION[$key] = $count;
    $_SESSION[$key . '_ts'] = time();
    return $count;
}

function clearNotifCache(int $userId): void {
    unset($_SESSION['notif_count_' . $userId], $_SESSION['notif_count_' . $userId . '_ts']);
}

// ============================================================
// EMAIL (SMTP via native PHP mail or socket — no vendor deps)
// ============================================================

function getEmailSettings(): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    $stmt = db()->query("SELECT email_settings FROM company_settings WHERE id=1");
    $row  = $stmt->fetch();
    $cached = $row ? (json_decode($row['email_settings'] ?? '{}', true) ?: []) : [];
    return $cached;
}

function sendEmail(
    string $to,
    string $subject,
    string $bodyHtml,
    array  $attachments = [],
    string $type        = 'general',
    ?int   $employeeId  = null,
    ?int   $referenceId = null,
    string $referenceType = ''
): array {
    $cfg      = getEmailSettings();
    $host     = $cfg['smtp_host']       ?? '';
    $port     = (int)($cfg['smtp_port'] ?? 587);
    $user     = $cfg['smtp_user']       ?? '';
    $pass     = $cfg['smtp_pass']       ?? '';
    $enc      = $cfg['smtp_encryption'] ?? 'tls';
    $fromName = $cfg['from_name']       ?? 'Komagin HR';
    $fromAddr = $cfg['from_email']      ?? '';

    // Log the attempt first (pending)
    $logId = null;
    try {
        $settings = getCompanySettings();
        db()->prepare("INSERT INTO email_logs (type, recipient_email, subject, body_html, status, employee_id, reference_id, reference_type) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$type, $to, $subject, $bodyHtml, 'pending', $employeeId, $referenceId, $referenceType]);
        $logId = (int)db()->lastInsertId();
    } catch (Exception $e) { /* non-fatal */ }

    // If no SMTP configured — use PHP mail() as fallback
    if (empty($host) || empty($fromAddr)) {
        $headers  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <" . (empty($fromAddr) ? ini_get('sendmail_from') : $fromAddr) . ">\r\n";
        $sent = @mail($to, $subject, $bodyHtml, $headers);
        $status  = $sent ? 'sent' : 'failed';
        $errMsg  = $sent ? null : 'PHP mail() returned false. Configure SMTP or check server mail.';
        if ($logId) {
            db()->prepare("UPDATE email_logs SET status=?, failure_reason=?, sent_at=NOW() WHERE id=?")
                ->execute([$status, $errMsg, $logId]);
        }
        return ['success' => $sent, 'error' => $errMsg];
    }

    // SMTP send via PHP streams (no external library needed)
    try {
        $prefix = match($enc) { 'ssl' => 'ssl://', default => '' };
        $ctx    = stream_context_create(['ssl' => ['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]]);
        $sock   = stream_socket_client($prefix . $host . ':' . $port, $errno, $errStr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$sock) throw new Exception("Connection failed: $errStr ($errno)");

        $recv = fgets($sock, 1024);
        if (substr($recv, 0, 3) !== '220') throw new Exception("SMTP greeting failed: $recv");

        $domain = gethostname() ?: 'localhost';
        $cmds = [
            "EHLO $domain",
        ];
        if ($enc === 'tls') $cmds[] = 'STARTTLS';

        foreach ($cmds as $cmd) {
            fwrite($sock, "$cmd\r\n");
            $r = ''; $line = '';
            while (($line = fgets($sock, 1024)) !== false) {
                $r .= $line;
                if (strlen($line) >= 4 && $line[3] === ' ') break;
            }
        }

        if ($enc === 'tls') {
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fwrite($sock, "EHLO $domain\r\n");
            while (($line = fgets($sock, 1024)) !== false) { if (strlen($line) >= 4 && $line[3] === ' ') break; }
        }

        // Auth
        fwrite($sock, "AUTH LOGIN\r\n");
        $r = fgets($sock, 1024);
        fwrite($sock, base64_encode($user) . "\r\n");
        $r = fgets($sock, 1024);
        fwrite($sock, base64_encode($pass) . "\r\n");
        $r = fgets($sock, 1024);
        if (substr($r, 0, 3) !== '235') throw new Exception("SMTP auth failed: $r");

        // Envelope
        fwrite($sock, "MAIL FROM:<$fromAddr>\r\n"); fgets($sock, 1024);
        fwrite($sock, "RCPT TO:<$to>\r\n"); fgets($sock, 1024);
        fwrite($sock, "DATA\r\n"); fgets($sock, 1024);

        // Build MIME message
        $boundary = md5(uniqid('', true));
        $hasAttachments = !empty($attachments);

        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromAddr>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        if ($hasAttachments) {
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";
            $body  = "--$boundary\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($bodyHtml)) . "\r\n";
            foreach ($attachments as $att) {
                $body .= "--$boundary\r\n";
                $body .= "Content-Type: " . ($att['mime'] ?? 'application/octet-stream') . "; name=\"" . $att['name'] . "\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"" . $att['name'] . "\"\r\n\r\n";
                $body .= chunk_split(base64_encode($att['data'])) . "\r\n";
            }
            $body .= "--$boundary--\r\n";
        } else {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body = chunk_split(base64_encode($bodyHtml));
        }

        fwrite($sock, $headers . $body . "\r\n.\r\n");
        $r = fgets($sock, 1024);
        if (substr($r, 0, 3) !== '250') throw new Exception("DATA failed: $r");

        fwrite($sock, "QUIT\r\n");
        fclose($sock);

        if ($logId) db()->prepare("UPDATE email_logs SET status='sent', sent_at=NOW() WHERE id=?")->execute([$logId]);
        return ['success' => true, 'error' => null];

    } catch (Exception $ex) {
        $errMsg = $ex->getMessage();
        error_log('SMTP send failed: ' . $errMsg);
        if ($logId) db()->prepare("UPDATE email_logs SET status='failed', failure_reason=?, retry_count=retry_count+1 WHERE id=?")->execute([$errMsg, $logId]);
        return ['success' => false, 'error' => $errMsg];
    }
}

function sendPayslipEmail(int $payslipId): array {
    $cfg = getEmailSettings();
    if (empty($cfg['payslip_notify']) || $cfg['payslip_notify'] != '1') {
        return ['success' => false, 'error' => 'Payslip email notifications disabled.'];
    }

    $stmt = db()->prepare("SELECT ps.*, e.first_name, e.last_name, e.email, d.name as dept_name, p.title as position_title
        FROM payslips ps JOIN employees e ON ps.employee_id=e.id
        LEFT JOIN departments d ON e.department_id=d.id
        LEFT JOIN positions p ON e.position_id=p.id WHERE ps.id=?");
    $stmt->execute([$payslipId]);
    $ps = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ps || empty($ps['email'])) {
        return ['success' => false, 'error' => 'Employee or email not found.'];
    }

    $settings = getCompanySettings();
    $cur      = CURRENCY_SYMBOL;
    $months   = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
    $month    = $months[(int)$ps['period_month']] ?? $ps['period_month'];
    $year     = $ps['period_year'];

    $vars = [
        '{{employee_name}}' => $ps['first_name'].' '.$ps['last_name'],
        '{{month}}'         => $month,
        '{{year}}'          => $year,
        '{{currency}}'      => $cur,
        '{{net_pay}}'       => number_format((float)$ps['net_salary'], 2),
        '{{gross_pay}}'     => number_format((float)$ps['gross_salary'], 2),
        '{{company_name}}'  => $settings['company_name'] ?? 'Komagin HR',
    ];

    $subject = strtr($cfg['payslip_subject'] ?? 'Your Payslip for {{month}} {{year}}', $vars);
    $bodyTpl = $cfg['payslip_body'] ?? 'Dear {{employee_name}},\n\nYour payslip for {{month}} {{year}} is ready.\n\nNet Pay: {{currency}} {{net_pay}}\n\nRegards,\n{{company_name}} HR Team';
    $bodyText = strtr($bodyTpl, $vars);
    $bodyHtml = '<html><body style="font-family:Arial,sans-serif;line-height:1.6;color:#333;">'
        . nl2br(htmlspecialchars($bodyText))
        . '</body></html>';

    return sendEmail(
        $ps['email'],
        $subject,
        $bodyHtml,
        [],
        'payslip',
        (int)$ps['employee_id'],
        $payslipId,
        'payslips'
    );
}

// ============================================================
// DATE / TIME HELPERS
// ============================================================

function formatDate(?string $date, string $format = 'd M Y'): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

function formatDateTime(?string $datetime, string $format = 'd M Y H:i'): string {
    if (empty($datetime)) return '—';
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '—';
}

function formatTime(?string $time): string {
    if (empty($time) || $time === '00:00:00') return '—';
    $ts = strtotime($time);
    return $ts ? date('h:i A', $ts) : '—';
}

function nf(?float $value, int $decimals = 0): string {
    return number_format((float)($value ?? 0), $decimals);
}

function money(?float $value, int $decimals = 2): string {
    return CURRENCY_SYMBOL . ' ' . number_format((float)($value ?? 0), $decimals);
}

function calculateHours(string $start, string $end, int $breakMinutes = 0): float {
    if (empty($start) || empty($end)) return 0;
    $startTs = strtotime($start);
    $endTs = strtotime($end);
    if ($endTs <= $startTs) return 0;
    $totalSeconds = $endTs - $startTs - ($breakMinutes * 60);
    return round(max(0, $totalSeconds / 3600), 2);
}

function minutesToHoursMinutes(int $minutes): string {
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $h . 'h ' . str_pad($m, 2, '0', STR_PAD_LEFT) . 'm';
}

function isLate(string $signInTime, ?string $workStart = null, int $gracePeriod = 0): bool {
    $workStart = $workStart ?? DEFAULT_WORK_START;
    $threshold = strtotime(date('Y-m-d') . ' ' . $workStart) + ($gracePeriod * 60);
    $actual = strtotime(date('Y-m-d') . ' ' . $signInTime);
    return $actual > $threshold;
}

// ============================================================
// FILE UPLOADS
// ============================================================

function uploadFile(array $file, string $folder, array $allowedTypes): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File exceeds maximum allowed size of 10MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed.'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('', true) . '_' . time() . '.' . strtolower($ext);
    $targetDir = UPLOAD_PATH . $folder . '/';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to save file.'];
    }

    return ['success' => true, 'filename' => $filename, 'path' => 'uploads/' . $folder . '/' . $filename, 'mime' => $mimeType];
}

// ============================================================
// FLASH MESSAGES
// ============================================================

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';

    $icons = ['success' => 'check-circle', 'error' => 'x-circle',
              'warning' => 'alert-triangle', 'info' => 'info'];
    $icon = $icons[$flash['type']] ?? 'info';

    return '<div class="alert alert-' . e($flash['type']) . ' alert-dismissible" role="alert">
        <i data-lucide="' . $icon . '" class="me-2" style="width:16px;height:16px;"></i>
        ' . e($flash['message']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// ============================================================
// PAGINATION
// ============================================================

function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}

// ============================================================
// STATUS BADGE HELPERS
// ============================================================

function employeeStatusBadge(string $status): string {
    $map = [
        'active'      => ['success', 'Active'],
        'probation'   => ['warning', 'On Probation'],
        'suspended'   => ['danger', 'Suspended'],
        'on_leave'    => ['info', 'On Leave'],
        'resigned'    => ['secondary', 'Resigned'],
        'terminated'  => ['danger', 'Terminated'],
        'deceased'    => ['dark', 'Deceased'],
        'archived'    => ['secondary', 'Archived'],
    ];
    $item = $map[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge badge-' . $item[0] . '">' . $item[1] . '</span>';
}

function leaveStatusBadge(string $status): string {
    $map = [
        'pending'   => ['warning', 'Pending'],
        'approved'  => ['success', 'Approved'],
        'rejected'  => ['danger', 'Rejected'],
        'cancelled' => ['secondary', 'Cancelled'],
    ];
    $item = $map[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge badge-' . $item[0] . '">' . $item[1] . '</span>';
}

function attendanceStatusBadge(string $status): string {
    $map = [
        'present' => ['success', 'Present'],
        'absent'  => ['danger', 'Absent'],
        'late'    => ['warning', 'Late'],
        'on_leave'=> ['info', 'On Leave'],
        'half_day'=> ['warning', 'Half Day'],
    ];
    $item = $map[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge badge-' . $item[0] . '">' . $item[1] . '</span>';
}

// ============================================================
// DATA FETCH HELPERS
// ============================================================

function getDepartments(): array {
    return db()->query("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name")->fetchAll();
}

function getPositions(int $departmentId = 0): array {
    if ($departmentId) {
        $stmt = db()->prepare("SELECT id, title FROM positions WHERE department_id = ? AND is_active = 1 ORDER BY title");
        $stmt->execute([$departmentId]);
        return $stmt->fetchAll();
    }
    return db()->query("SELECT id, title, department_id FROM positions WHERE is_active = 1 ORDER BY title")->fetchAll();
}

function getLeaveTypes(): array {
    return db()->query("SELECT id, name, code, max_days, is_paid, carry_forward, requires_document, gender_specific FROM leave_types WHERE is_active = 1 ORDER BY name")->fetchAll();
}

function getEmployee(int $id): ?array {
    $stmt = db()->prepare("SELECT e.*, d.name as department_name, p.title as position_title,
                           CONCAT(s.first_name, ' ', s.last_name) as supervisor_name
                           FROM employees e
                           LEFT JOIN departments d ON e.department_id = d.id
                           LEFT JOIN positions p ON e.position_id = p.id
                           LEFT JOIN employees s ON e.supervisor_id = s.id
                           WHERE e.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getEmployeeByNumber(string $empNumber): ?array {
    $stmt = db()->prepare("SELECT e.*, d.name as department_name, p.title as position_title
                           FROM employees e
                           LEFT JOIN departments d ON e.department_id = d.id
                           LEFT JOIN positions p ON e.position_id = p.id
                           WHERE e.employee_number = ?");
    $stmt->execute([$empNumber]);
    return $stmt->fetch() ?: null;
}

function getCompanySettings(): array {
    // Request-scope static cache only — never persisted in session
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = db()->query("SELECT * FROM company_settings WHERE id = 1");
    $cache = $stmt->fetch() ?: [];
    return $cache;
}

function clearSettingsCache(): void {
    // Clear session remnant if any, plus request-scope cache reset happens automatically
    unset($_SESSION['_company_settings']);
    // Force static reset via a workaround (reassign by reference through closure)
    // Settings change is rare — just let next request re-read from DB
}

function getAttendanceSettings(): array {
    $settings = getCompanySettings();
    return [
        'work_start'         => $settings['work_start_time'] ?? DEFAULT_WORK_START,
        'work_end'           => $settings['work_end_time'] ?? DEFAULT_WORK_END,
        'grace_period'       => (int)($settings['grace_period_minutes'] ?? DEFAULT_GRACE_PERIOD),
        'break_duration'     => (int)($settings['break_duration_minutes'] ?? DEFAULT_BREAK_DURATION),
        'standard_hours'     => (float)($settings['standard_work_hours'] ?? DEFAULT_WORK_HOURS),
        'overtime_threshold' => (float)($settings['overtime_threshold_hours'] ?? DEFAULT_OVERTIME_THRESHOLD),
    ];
}

// ============================================================
// WORKING-DAY & HOLIDAY CALENDAR (Phase 5, Stage 5.3)
// ============================================================
// No working-day/holiday calendar existed anywhere in this codebase
// before this — every "absence" figure across Dashboard/Reports could
// only ever be derived from raw attendance rows (which only exist when
// someone actually clocks in), never from a real notion of which days
// employees were expected to be present. See KOM-098.

function getWorkCalendarSettings(): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    $stmt = db()->query("SELECT * FROM work_calendar_settings WHERE id=1");
    $row = $stmt->fetch();
    $cached = $row ?: ['working_weekdays' => '1,2,3,4,5', 'timezone' => 'Pacific/Port_Moresby'];
    return $cached;
}

// Fetch all active holidays that could possibly affect a date range in
// one pass — callers should never query per-day in a loop.
function getActiveHolidaysForRange(string $startDate, string $endDate): array {
    $fixedStmt = db()->prepare("SELECT * FROM work_calendar_holidays
        WHERE is_active=1 AND is_recurring_annual=0
        AND start_date <= ? AND end_date >= ?");
    $fixedStmt->execute([$endDate, $startDate]);

    $recurringStmt = db()->query("SELECT * FROM work_calendar_holidays WHERE is_active=1 AND is_recurring_annual=1");

    return [
        'fixed'     => $fixedStmt->fetchAll(PDO::FETCH_ASSOC),
        'recurring' => $recurringStmt->fetchAll(PDO::FETCH_ASSOC),
    ];
}

// Internal: evaluate a single date against already-fetched calendar
// data, avoiding a DB round-trip per date when checking a range.
function _isWorkingDayWithCalendar(string $date, array $workingWeekdays, array $holidays): bool {
    $isoWeekday = (int)date('N', strtotime($date));
    if (!in_array($isoWeekday, $workingWeekdays, true)) return false;

    foreach ($holidays['fixed'] as $h) {
        if ($date >= $h['start_date'] && $date <= $h['end_date']) return false;
    }

    $md = date('m-d', strtotime($date));
    foreach ($holidays['recurring'] as $h) {
        $startMd = date('m-d', strtotime($h['start_date']));
        $endMd   = date('m-d', strtotime($h['end_date']));
        if ($startMd <= $endMd) {
            if ($md >= $startMd && $md <= $endMd) return false;
        } else {
            // Range wraps the year boundary (e.g. Dec 30 - Jan 2) —
            // real-world recurring holidays in this deployment are
            // single-day, so this is a documented simplification, not
            // fully general.
            if ($md >= $startMd || $md <= $endMd) return false;
        }
    }
    return true;
}

function isWorkingDay(string $date): bool {
    $settings = getWorkCalendarSettings();
    $workingWeekdays = array_map('intval', explode(',', $settings['working_weekdays']));
    $holidays = getActiveHolidaysForRange($date, $date);
    return _isWorkingDayWithCalendar($date, $workingWeekdays, $holidays);
}

function getWorkingDaysBetween(string $startDate, string $endDate): array {
    if (strtotime($startDate) > strtotime($endDate)) return [];
    $settings = getWorkCalendarSettings();
    $workingWeekdays = array_map('intval', explode(',', $settings['working_weekdays']));
    $holidays = getActiveHolidaysForRange($startDate, $endDate);

    $result  = [];
    $current = strtotime($startDate);
    $end     = strtotime($endDate);
    while ($current <= $end) {
        $d = date('Y-m-d', $current);
        if (_isWorkingDayWithCalendar($d, $workingWeekdays, $holidays)) {
            $result[] = $d;
        }
        $current = strtotime('+1 day', $current);
    }
    return $result;
}

function countWorkingDays(string $startDate, string $endDate): int {
    return count(getWorkingDaysBetween($startDate, $endDate));
}

function getNextWorkingDay(string $date): string {
    $next = date('Y-m-d', strtotime('+1 day', strtotime($date)));
    // Bounded look-ahead so a misconfiguration (e.g. every weekday
    // disabled) can never loop forever.
    for ($i = 0; $i < 30; $i++) {
        if (isWorkingDay($next)) return $next;
        $next = date('Y-m-d', strtotime('+1 day', strtotime($next)));
    }
    return $next;
}
