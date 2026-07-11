<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('consultants.create', 'create');

$activeMenu = 'consultants';
$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $old = $_POST;
        $type          = $_POST['type'] ?? 'time_based';
        $firstName     = trim($_POST['first_name'] ?? '');
        $lastName      = trim($_POST['last_name'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $phone         = trim($_POST['phone'] ?? '');
        $company       = trim($_POST['company'] ?? '');
        $positionTitle = trim($_POST['position_title'] ?? '');
        $department    = trim($_POST['department'] ?? '');
        $startDate     = $_POST['start_date'] ?? null;
        $endDate       = $_POST['end_date'] ?? null;
        $status        = $_POST['status'] ?? 'active';
        $hourlyRate    = $type === 'time_based' && $_POST['hourly_rate'] !== '' ? (float)$_POST['hourly_rate'] : null;
        $dailyRate     = $type === 'time_based' && $_POST['daily_rate'] !== '' ? (float)$_POST['daily_rate'] : null;
        $contractValue = $type === 'output_based' && $_POST['contract_value'] !== '' ? (float)$_POST['contract_value'] : null;
        $notes         = trim($_POST['notes'] ?? '');
        $portalActive  = isset($_POST['portal_active']) ? 1 : 0;
        $portalPassword = trim($_POST['portal_password'] ?? '');

        if (!$firstName) $errors[] = 'First name is required.';
        if (!$lastName)  $errors[] = 'Last name is required.';
        if (!in_array($type, ['time_based','output_based'])) $errors[] = 'Invalid type.';
        if ($portalActive && !$portalPassword) $errors[] = 'A portal password is required when enabling portal access.';
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

        if (!$errors) {
            // Generate consultant number
            $year   = date('Y');
            $maxStmt = db()->query("SELECT MAX(CAST(SUBSTRING_INDEX(consultant_number,'-',-1) AS UNSIGNED)) FROM consultants WHERE consultant_number LIKE 'KOM-CON-$year-%'");
            $maxNum  = (int)$maxStmt->fetchColumn();
            $conNum  = 'KOM-CON-' . $year . '-' . str_pad($maxNum + 1, 4, '0', STR_PAD_LEFT);

            $hash = $portalActive && $portalPassword ? password_hash($portalPassword, PASSWORD_DEFAULT) : null;

            $stmt = db()->prepare("INSERT INTO consultants
                (consultant_number, first_name, last_name, email, phone, company, position_title, type, department,
                 start_date, end_date, status, hourly_rate, daily_rate, contract_value, notes, portal_active, portal_password)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$conNum, $firstName, $lastName, $email ?: null, $phone ?: null, $company ?: null, $positionTitle ?: null,
                $type, $department ?: null, $startDate ?: null, $endDate ?: null, $status, $hourlyRate, $dailyRate, $contractValue,
                $notes ?: null, $portalActive, $hash]);
            $newId = db()->lastInsertId();

            setFlash('success', "Consultant $conNum created successfully.");
            header('Location: ' . APP_URL . '/modules/consultants/view.php?id=' . $newId);
            exit;
        }
    }
}

$pageTitle = 'Add Consultant';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb" style="font-size:0.8rem;">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/consultants/index.php">Consultants</a></li>
                <li class="breadcrumb-item active">Add Consultant</li>
            </ol>
        </nav>
        <h1 class="page-title">Add Consultant</h1>
    </div>
    <a href="<?= APP_URL ?>/modules/consultants/index.php" class="btn btn-secondary btn-sm">← Back</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

        <!-- Left column -->
        <div>
            <!-- Consultant Type -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Consultant Type</span></div>
                <div class="card-body">
                    <div style="display:flex;gap:12px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:12px 16px;border:2px solid var(--border);border-radius:8px;flex:1;transition:border-color 0.15s;" id="lbl-time">
                            <input type="radio" name="type" value="time_based" id="type-time" <?= ($old['type']??'time_based')==='time_based'?'checked':'' ?> onchange="toggleType(this.value)" style="accent-color:var(--primary);">
                            <div>
                                <div style="font-weight:600;font-size:0.85rem;">Time-Based</div>
                                <div style="font-size:0.72rem;color:var(--text-secondary);">Clock in / out via kiosk</div>
                            </div>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:12px 16px;border:2px solid var(--border);border-radius:8px;flex:1;transition:border-color 0.15s;" id="lbl-output">
                            <input type="radio" name="type" value="output_based" id="type-output" <?= ($old['type']??'')==='output_based'?'checked':'' ?> onchange="toggleType(this.value)" style="accent-color:var(--primary);">
                            <div>
                                <div style="font-weight:600;font-size:0.85rem;">Output-Based</div>
                                <div style="font-size:0.72rem;color:var(--text-secondary);">Scope of work checklist</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Personal Info -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Personal Details</span></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name <span style="color:var(--danger)">*</span></label>
                            <input type="text" class="form-control" name="first_name" value="<?= e($old['first_name']??'') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name <span style="color:var(--danger)">*</span></label>
                            <input type="text" class="form-control" name="last_name" value="<?= e($old['last_name']??'') ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= e($old['email']??'') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?= e($old['phone']??'') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Company / Organisation</label>
                            <input type="text" class="form-control" name="company" value="<?= e($old['company']??'') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Position / Role</label>
                            <input type="text" class="form-control" name="position_title" value="<?= e($old['position_title']??'') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department" value="<?= e($old['department']??'') ?>">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="card">
                <div class="card-header"><span class="card-title">Notes</span></div>
                <div class="card-body">
                    <textarea class="form-control" name="notes" rows="3" style="resize:vertical;"><?= e($old['notes']??'') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div>
            <!-- Contract -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Contract Details</span></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?= e($old['start_date']??'') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?= e($old['end_date']??'') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <?php foreach (['active'=>'Active','completed'=>'Completed','terminated'=>'Terminated'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($old['status']??'active')===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Time-based rates -->
                    <div id="time-fields">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Hourly Rate (K)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="hourly_rate" value="<?= e($old['hourly_rate']??'') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Daily Rate (K)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="daily_rate" value="<?= e($old['daily_rate']??'') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Output-based contract value -->
                    <div id="output-fields" style="display:none;">
                        <div class="form-group">
                            <label class="form-label">Contract Value (K)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="contract_value" value="<?= e($old['contract_value']??'') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Portal Access -->
            <div class="card">
                <div class="card-header"><span class="card-title">Consultant Portal Access</span></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="portal_active" id="portalActiveChk" value="1" <?= !empty($old['portal_active'])?'checked':'' ?> onchange="document.getElementById('portalPwField').style.display=this.checked?'':'none'">
                            <span style="font-size:0.84rem;">Enable portal access for this consultant</span>
                        </label>
                        <div style="font-size:0.72rem;color:var(--text-secondary);margin-top:4px;">Consultant will use their number + password to log in at <code><?= APP_URL ?>/consultant-portal/</code></div>
                    </div>
                    <div id="portalPwField" style="display:<?= !empty($old['portal_active'])?'block':'none' ?>;">
                        <label class="form-label">Portal Password</label>
                        <input type="password" class="form-control" name="portal_password" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:20px;display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">Save Consultant</button>
        <a href="<?= APP_URL ?>/modules/consultants/index.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
function toggleType(val) {
    const isTime = val === 'time_based';
    document.getElementById('time-fields').style.display   = isTime ? '' : 'none';
    document.getElementById('output-fields').style.display = isTime ? 'none' : '';
    document.getElementById('lbl-time').style.borderColor   = isTime ? 'var(--primary)' : 'var(--border)';
    document.getElementById('lbl-output').style.borderColor = !isTime ? 'var(--primary)' : 'var(--border)';
}
// Init on load
const selType = document.querySelector('input[name="type"]:checked');
if (selType) toggleType(selType.value);
</script>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
