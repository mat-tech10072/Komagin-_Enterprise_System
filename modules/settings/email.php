<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('settings.manage', 'view');

$pageTitle  = 'Email Settings';
$activeMenu = 'settings';
$settings   = getCompanySettings();
$emailJson  = $settings['email_settings'] ?? '{}';
$email      = json_decode($emailJson, true) ?: [];

$defaults = [
    'smtp_host'       => '',
    'smtp_port'       => '587',
    'smtp_user'       => '',
    'smtp_pass'       => '',
    'smtp_encryption' => 'tls',
    'from_name'       => $settings['company_name'] ?? 'Komagin HR',
    'from_email'      => $settings['email'] ?? '',
    'payslip_notify'  => '1',
    'payslip_subject' => 'Your Payslip for {{month}} {{year}} is ready',
    'payslip_body'    => 'Dear {{employee_name}},\n\nPlease find your payslip for {{month}} {{year}} attached.\n\nNet Pay: {{currency}} {{net_pay}}\n\nIf you have any queries, please contact HR.\n\nRegards,\n{{company_name}} HR Team',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $act = $_POST['action'] ?? '';

    if ($act === 'save_email_settings') {
        $newEmail = [];
        foreach ($defaults as $k => $def) {
            $v = $_POST[$k] ?? $def;
            // Don't overwrite password if blank (keep existing)
            if ($k === 'smtp_pass' && $v === '' && !empty($email['smtp_pass'])) {
                $v = $email['smtp_pass'];
            }
            $newEmail[$k] = $v;
        }
        db()->prepare("UPDATE company_settings SET email_settings=? WHERE id=1")->execute([json_encode($newEmail)]);
        auditLog('settings','update_email_settings',1);
        setFlash('success','Email settings saved.');
        header('Location: email.php'); exit;

    } elseif ($act === 'test_email') {
        $testTo = trim($_POST['test_to'] ?? '');
        if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            setFlash('error','Invalid test email address.');
        } else {
            $result = sendEmail(
                $testTo, 'Test — ' . ($settings['company_name']??'Komagin HR'),
                '<p>This is a test email from <strong>'.htmlspecialchars($settings['company_name']??'Komagin HR').'</strong> HR System.</p><p>If you received this, your SMTP settings are working correctly.</p>',
                [], 'test'
            );
            if ($result['success']) {
                setFlash('success', 'Test email sent to '.$testTo);
            } else {
                setFlash('error', 'Failed: '.$result['error']);
            }
        }
        header('Location: email.php'); exit;
    }
}

$e = array_merge($defaults, $email);

// Recent email logs
$recentLogs = db()->query("SELECT * FROM email_logs ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/settings/index.php">Settings</a></li>
                <li class="breadcrumb-item active">Email Settings</li>
            </ol>
        </nav>
        <h1 class="page-title">Email Settings</h1>
        <p class="page-subtitle">Configure SMTP, payslip notifications, and view delivery logs</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

<!-- SMTP Settings -->
<div class="card">
    <div class="card-header"><span class="card-title">SMTP Configuration</span></div>
    <form method="POST" style="padding:16px;">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="save_email_settings">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">SMTP Host</label>
                <input type="text" class="form-control" name="smtp_host" value="<?= e($e['smtp_host']) ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="form-group">
                <label class="form-label">Port</label>
                <input type="number" class="form-control" name="smtp_port" value="<?= e($e['smtp_port']) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">SMTP Username</label>
                <input type="text" class="form-control" name="smtp_user" value="<?= e($e['smtp_user']) ?>" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">SMTP Password</label>
                <input type="password" class="form-control" name="smtp_pass" placeholder="<?= !empty($e['smtp_pass'])?'••••••••':'Enter password' ?>" autocomplete="new-password">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Encryption</label>
                <select class="form-select" name="smtp_encryption">
                    <?php foreach (['tls'=>'TLS (port 587)','ssl'=>'SSL (port 465)','none'=>'None'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= $e['smtp_encryption']===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">From Email</label>
                <input type="email" class="form-control" name="from_email" value="<?= e($e['from_email']) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">From Name</label>
            <input type="text" class="form-control" name="from_name" value="<?= e($e['from_name']) ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Save SMTP Settings</button>
    </form>

    <!-- Test Email -->
    <div style="border-top:1px solid var(--border);padding:14px 16px;background:var(--bg);border-radius:0 0 var(--radius) var(--radius);">
        <div style="font-size:0.78rem;font-weight:600;margin-bottom:8px;">Send Test Email</div>
        <form method="POST" style="display:flex;gap:8px;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="test_email">
            <input type="email" class="form-control" name="test_to" placeholder="test@example.com" required style="flex:1;">
            <button type="submit" class="btn btn-secondary">Send Test</button>
        </form>
    </div>
</div>

<!-- Payslip Notification Settings -->
<div class="card">
    <div class="card-header"><span class="card-title">Payslip Email Notifications</span></div>
    <form method="POST" style="padding:16px;">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="save_email_settings">
        <?php // KOM-031: smtp_pass deliberately excluded here — re-emitting it as a
        // hidden field's cleartext value exposed it in the page's HTML source. Not
        // submitting it at all is safe: the save handler above already preserves
        // the existing stored password whenever the field is blank/absent. ?>
        <?php foreach (['smtp_host','smtp_port','smtp_user','smtp_encryption','from_name','from_email'] as $k): ?>
        <input type="hidden" name="<?= $k ?>" value="<?= e($e[$k]) ?>">
        <?php endforeach; ?>

        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.85rem;font-weight:600;">
                <input type="checkbox" name="payslip_notify" value="1" <?= !empty($e['payslip_notify'])?'checked':'' ?> style="width:16px;height:16px;">
                Email payslip to employee when payroll is published
            </label>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px;">When a payroll run is published, employees will automatically receive their payslip by email.</div>
        </div>
        <div class="form-group">
            <label class="form-label">Email Subject</label>
            <input type="text" class="form-control" name="payslip_subject" value="<?= e($e['payslip_subject']) ?>">
            <div style="font-size:0.68rem;color:var(--text-muted);margin-top:3px;">Variables: {{month}}, {{year}}, {{employee_name}}, {{company_name}}</div>
        </div>
        <div class="form-group">
            <label class="form-label">Email Body</label>
            <textarea class="form-control" name="payslip_body" rows="8"><?= e($e['payslip_body']) ?></textarea>
            <div style="font-size:0.68rem;color:var(--text-muted);margin-top:3px;">Variables: {{employee_name}}, {{month}}, {{year}}, {{currency}}, {{net_pay}}, {{gross_pay}}, {{company_name}}</div>
        </div>
        <div class="alert alert-info" style="font-size:0.75rem;">
            <strong>Note:</strong> Payslip PDF is attached automatically. SMTP must be configured above for emails to deliver.
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Save Notification Settings</button>
    </form>
</div>
</div>

<!-- Email Log -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Email Delivery Log</span>
        <span style="font-size:0.72rem;color:var(--text-muted);">Last 20 records</span>
    </div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead>
                <tr><th>Time</th><th>Type</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Retries</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recentLogs as $log): ?>
            <tr>
                <td style="font-size:0.72rem;white-space:nowrap;font-family:monospace;"><?= formatDateTime($log['created_at']) ?></td>
                <td><span class="badge badge-secondary"><?= ucfirst($log['type']) ?></span></td>
                <td style="font-size:0.78rem;"><?= e($log['recipient_email']) ?></td>
                <td style="font-size:0.75rem;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($log['subject']) ?></td>
                <td>
                    <?php $sc=['sent'=>'success','failed'=>'danger','pending'=>'warning','bounced'=>'danger']; ?>
                    <span class="badge badge-<?= $sc[$log['status']]??'secondary' ?>"><?= ucfirst($log['status']) ?></span>
                    <?php if ($log['failure_reason']): ?>
                    <div style="font-size:0.65rem;color:var(--danger);margin-top:2px;" title="<?= e($log['failure_reason']) ?>">
                        <?= e(substr($log['failure_reason'],0,50)) ?>...
                    </div>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.75rem;"><?= $log['retry_count'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentLogs)): ?>
            <tr><td colspan="6" class="empty-state">No emails sent yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
