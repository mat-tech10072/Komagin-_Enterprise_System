<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

// Standalone self-service page — no admin auth required
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>3600,'httponly'=>true,'samesite'=>'Strict']);
    session_start();
}

$token = trim($_GET['token'] ?? '');
if (!$token || strlen($token) !== 64) {
    http_response_code(404);
    include __DIR__ . '/_expired.php'; exit;
}

// Token is stored as sha256 hash of the raw URL token (generated in generate_link.php)
$tokenHash = hash('sha256', $token);

// Canonical field names match the employees table exactly
$FIELD_MAP = [
    'phone'                    => 'Phone Number',
    'personal_email'           => 'Personal Email',
    'residential_address'      => 'Street Address',
    'city'                     => 'City',
    'country'                  => 'Country',
    'marital_status'           => 'Marital Status',
    'emergency_contact_name'   => 'Emergency Contact Name',
    'emergency_contact_relation'=> 'Emergency Contact Relationship',
    'emergency_contact_phone'  => 'Emergency Contact Phone',
    'nok_name'                 => 'Next of Kin Name',
    'nok_relation'             => 'Next of Kin Relationship',
    'nok_phone'                => 'Next of Kin Phone',
    'bank_name'                => 'Bank Name',
    'bank_account_number'      => 'Account Number',
    'bank_branch_code'         => 'Branch Code',
    'bank_account_type'        => 'Account Type',
];

$linkStmt = db()->prepare(
    "SELECT ul.*, e.first_name, e.last_name, e.employee_number,
        e.phone, e.personal_email, e.residential_address, e.city, e.country, e.marital_status,
        e.emergency_contact_name, e.emergency_contact_relation, e.emergency_contact_phone,
        e.nok_name, e.nok_relation, e.nok_phone,
        e.bank_name, e.bank_account_number, e.bank_branch_code, e.bank_account_type
    FROM employee_update_links ul
    JOIN employees e ON ul.employee_id = e.id
    WHERE ul.token = ? AND ul.is_active = 1 AND ul.is_revoked = 0 AND ul.expires_at > NOW()"
);
$linkStmt->execute([$tokenHash]);
$link = $linkStmt->fetch(PDO::FETCH_ASSOC);

if (!$link) {
    http_response_code(403);
    include __DIR__ . '/_expired.php'; exit;
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['ss_csrf_'.$link['id']]) || $csrfToken !== $_SESSION['ss_csrf_'.$link['id']]) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
        $changes = [];

        foreach ($FIELD_MAP as $field => $label) {
            $submitted = trim($_POST[$field] ?? '');
            $current   = (string)($link[$field] ?? '');
            if ($submitted !== $current) {
                $changes[$field] = ['label' => $label, 'old' => $current, 'new' => $submitted];
            }
        }

        // Validation
        if (!empty($changes['personal_email']['new']) &&
            !filter_var($changes['personal_email']['new'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid personal email address.';
        }

        if (empty($changes)) {
            $errors[] = 'No changes detected. Please update at least one field before submitting.';
        }

        if (empty($errors)) {
            $linkId = $link['id'];
            $empId  = $link['employee_id'];

            foreach ($changes as $fieldName => $data) {
                db()->prepare(
                    "INSERT INTO employee_pending_updates
                    (employee_id, update_link_id, field_name, field_label, old_value, new_value, status)
                    VALUES (?,?,?,?,?,?,'pending')"
                )->execute([$empId, $linkId, $fieldName, $data['label'], $data['old'], $data['new']]);
            }

            // Deactivate link after use
            db()->prepare("UPDATE employee_update_links SET is_active=0 WHERE id=?")
                ->execute([$linkId]);

            // Audit log
            auditLog('self_service', 'profile_update_submitted', $empId, null,
                json_encode(array_keys($changes)),
                count($changes) . ' field(s) submitted via self-service link');

            unset($_SESSION['ss_csrf_'.$link['id']]);
            $success = true;
        }
    }
}

// Generate CSRF per link ID
if (!$success && !isset($_SESSION['ss_csrf_'.$link['id']])) {
    $_SESSION['ss_csrf_'.$link['id']] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['ss_csrf_'.$link['id']] ?? '';

$companySettings = getCompanySettings();
$companyName     = $companySettings['company_name'] ?? 'Komagin HR';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Update — <?= htmlspecialchars($companyName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Inter',sans-serif;background:#F8FAFC;color:#0F172A;min-height:100vh;padding:32px 16px;}
        .page{max-width:640px;margin:0 auto;}
        .header{text-align:center;margin-bottom:28px;}
        .header h1{font-size:1.3rem;font-weight:700;margin-bottom:4px;}
        .header p{color:#64748B;font-size:0.83rem;}
        .card{background:#fff;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
        .card-header{padding:12px 20px;border-bottom:1px solid #E2E8F0;background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;}
        .card-title{font-weight:700;font-size:0.78rem;text-transform:uppercase;letter-spacing:.06em;color:#64748B;}
        .card-body{padding:20px;}
        .form-group{margin-bottom:14px;}
        .form-group:last-child{margin-bottom:0;}
        .form-label{display:block;font-size:0.73rem;font-weight:600;color:#374151;margin-bottom:5px;}
        .form-control,.form-select{width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:0.82rem;font-family:inherit;background:#fff;color:#0F172A;transition:border-color .15s;}
        .form-control:focus,.form-select:focus{outline:none;border-color:#1D4ED8;box-shadow:0 0 0 3px rgba(29,78,216,.1);}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 22px;border-radius:7px;font-size:0.82rem;font-weight:600;cursor:pointer;border:1px solid transparent;font-family:inherit;transition:background .15s;}
        .btn-primary{background:#1D4ED8;color:#fff;border-color:#1D4ED8;}
        .btn-primary:hover{background:#1e40af;}
        .alert{padding:12px 16px;border-radius:7px;font-size:0.78rem;margin-bottom:14px;line-height:1.6;}
        .alert-danger{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;}
        .alert-success{background:#F0FDF4;border:1px solid #BBF7D0;color:#166534;}
        .alert-info{background:#EFF6FF;border:1px solid #BFDBFE;color:#1e40af;}
        .emp-tag{display:inline-block;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:5px;padding:3px 9px;font-size:0.73rem;font-weight:700;color:#1D4ED8;font-family:monospace;}
        .success-icon{font-size:3rem;margin-bottom:12px;}
        @media(max-width:560px){.form-row{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <h1><?= htmlspecialchars($companyName) ?></h1>
        <p>Self-Service Profile Update</p>
    </div>

    <?php if ($success): ?>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:48px 32px;">
            <div class="success-icon">&#10003;</div>
            <h2 style="font-size:1.1rem;margin-bottom:8px;">Submitted Successfully</h2>
            <p style="color:#64748B;font-size:0.83rem;line-height:1.6;">
                Your profile update has been submitted to HR for review.<br>
                Changes will be applied once approved. You will be notified by HR.
            </p>
        </div>
    </div>

    <?php else: ?>

    <div class="card">
        <div class="card-body" style="padding:14px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($link['first_name'].' '.$link['last_name']) ?></div>
                <div style="font-size:0.72rem;color:#64748B;margin-top:2px;">Profile update request · expires <?= date('d M Y', strtotime($link['expires_at'])) ?></div>
            </div>
            <span class="emp-tag"><?= htmlspecialchars($link['employee_number']) ?></span>
        </div>
    </div>

    <div class="alert alert-info">
        Only fields you actually change will be submitted for HR review. You may leave any field unchanged.
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?><?= htmlspecialchars($err) ?><br><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <!-- Contact Details -->
        <div class="card">
            <div class="card-header"><span class="card-title">Contact Details</span></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone"
                            value="<?= htmlspecialchars($link['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Personal Email</label>
                        <input type="email" class="form-control" name="personal_email"
                            value="<?= htmlspecialchars($link['personal_email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Residential Address</label>
                    <input type="text" class="form-control" name="residential_address"
                        value="<?= htmlspecialchars($link['residential_address'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city"
                            value="<?= htmlspecialchars($link['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <input type="text" class="form-control" name="country"
                            value="<?= htmlspecialchars($link['country'] ?? 'Papua New Guinea') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Marital Status</label>
                    <select class="form-select" name="marital_status">
                        <option value="">— Select —</option>
                        <?php foreach (['single'=>'Single','married'=>'Married','divorced'=>'Divorced','widowed'=>'Widowed','other'=>'Other'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($link['marital_status']??'')===$v?'selected':''?>>
                            <?= $l ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="card">
            <div class="card-header"><span class="card-title">Emergency Contact</span></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="emergency_contact_name"
                            value="<?= htmlspecialchars($link['emergency_contact_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relationship</label>
                        <input type="text" class="form-control" name="emergency_contact_relation"
                            value="<?= htmlspecialchars($link['emergency_contact_relation'] ?? '') ?>"
                            placeholder="e.g. Spouse, Parent">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="emergency_contact_phone"
                        value="<?= htmlspecialchars($link['emergency_contact_phone'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Next of Kin -->
        <div class="card">
            <div class="card-header"><span class="card-title">Next of Kin</span></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="nok_name"
                            value="<?= htmlspecialchars($link['nok_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relationship</label>
                        <input type="text" class="form-control" name="nok_relation"
                            value="<?= htmlspecialchars($link['nok_relation'] ?? '') ?>"
                            placeholder="e.g. Spouse, Sibling">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="nok_phone"
                        value="<?= htmlspecialchars($link['nok_phone'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Bank Details -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Bank Details</span>
                <span style="font-size:0.65rem;color:#64748B;">Payroll-sensitive — HR will verify</span>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Bank Name</label>
                        <input type="text" class="form-control" name="bank_name"
                            value="<?= htmlspecialchars($link['bank_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Number</label>
                        <input type="text" class="form-control" name="bank_account_number"
                            value="<?= htmlspecialchars($link['bank_account_number'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Branch Code</label>
                        <input type="text" class="form-control" name="bank_branch_code"
                            value="<?= htmlspecialchars($link['bank_branch_code'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Type</label>
                        <select class="form-select" name="bank_account_type">
                            <option value="">— Select —</option>
                            <?php foreach (['cheque'=>'Cheque','savings'=>'Savings','transmission'=>'Transmission'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($link['bank_account_type']??'')===$v?'selected':''?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:0.88rem;">
            Submit Profile Update for HR Review
        </button>
        <p style="text-align:center;font-size:0.7rem;color:#94A3B8;margin-top:10px;">
            All changes require HR approval before being applied to your record.
        </p>
    </form>

    <?php endif; ?>
</div>
</body>
</html>
