<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('employees.create', 'create');

$pageTitle  = 'Add Employee';
$activeMenu = 'employees';

$errors  = [];
$data    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Collect fields
        $fields = ['first_name','last_name','middle_name','date_of_birth','gender','marital_status',
                   'national_id','email','phone','phone_alt','residential_address','city','country',
                   'department_id','position_id','supervisor_id','employment_type','start_date',
                   'contract_end_date','probation_start','probation_end','work_location',
                   'bank_name','bank_account_number','bank_branch_code','bank_account_type',
                   'emergency_contact_name','emergency_contact_relation','emergency_contact_phone',
                   'nok_name','nok_relation','nok_phone'];

        foreach ($fields as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }

        // Validation
        if (empty($data['first_name'])) $errors[] = 'First name is required.';
        if (empty($data['last_name']))  $errors[] = 'Last name is required.';
        if (empty($data['start_date'])) $errors[] = 'Start date is required.';
        if (empty($data['employment_type'])) $errors[] = 'Employment type is required.';
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }

        // Check duplicate email
        if (!empty($data['email'])) {
            $check = db()->prepare("SELECT id FROM employees WHERE email = ?");
            $check->execute([$data['email']]);
            if ($check->fetch()) $errors[] = 'Email address is already in use.';
        }

        if (empty($errors)) {
            // Generate employee number
            $empNumber = generateEmployeeNumber();

            // Handle photo upload
            $photoPath = null;
            if (!empty($_FILES['photo']['name'])) {
                $upload = uploadFile($_FILES['photo'], 'employees', ALLOWED_IMAGE_TYPES);
                if ($upload['success']) {
                    $photoPath = $upload['path'];
                } else {
                    $errors[] = 'Photo upload failed: ' . $upload['error'];
                }
            }

            if (empty($errors)) {
                // Generate kiosk PIN (last 4 digits of national ID or random)
                $pin = !empty($data['national_id']) ? substr(preg_replace('/\D/','',$data['national_id']), -4) : str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $pinHash = password_hash($pin, PASSWORD_BCRYPT, ['cost' => 10]);

                $sql = "INSERT INTO employees (
                    employee_number, first_name, last_name, middle_name, date_of_birth, gender, marital_status,
                    national_id, email, phone, phone_alt, residential_address, city, country,
                    department_id, position_id, supervisor_id, employment_type, status,
                    start_date, contract_end_date, probation_start, probation_end, work_location,
                    bank_name, bank_account_number, bank_branch_code, bank_account_type,
                    emergency_contact_name, emergency_contact_relation, emergency_contact_phone,
                    nok_name, nok_relation, nok_phone, photo, kiosk_pin, created_by
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

                $empStatus = (!empty($data['probation_start'])) ? 'probation' : 'active';

                $stmt = db()->prepare($sql);
                $stmt->execute([
                    $empNumber,
                    $data['first_name'], $data['last_name'], $data['middle_name'] ?: null,
                    $data['date_of_birth'] ?: null, $data['gender'] ?: null, $data['marital_status'] ?: null,
                    $data['national_id'] ?: null, $data['email'] ?: null,
                    $data['phone'] ?: null, $data['phone_alt'] ?: null,
                    $data['residential_address'] ?: null, $data['city'] ?: null,
                    $data['country'] ?: 'Papua New Guinea',
                    $data['department_id'] ?: null, $data['position_id'] ?: null,
                    $data['supervisor_id'] ?: null, $data['employment_type'],
                    $empStatus,
                    $data['start_date'], $data['contract_end_date'] ?: null,
                    $data['probation_start'] ?: null, $data['probation_end'] ?: null,
                    $data['work_location'] ?: null,
                    $data['bank_name'] ?: null, $data['bank_account_number'] ?: null,
                    $data['bank_branch_code'] ?: null, $data['bank_account_type'] ?: null,
                    $data['emergency_contact_name'] ?: null, $data['emergency_contact_relation'] ?: null,
                    $data['emergency_contact_phone'] ?: null,
                    $data['nok_name'] ?: null, $data['nok_relation'] ?: null, $data['nok_phone'] ?: null,
                    $photoPath, $pinHash, $_SESSION['user_id']
                ]);

                $newId = db()->lastInsertId();

                // Status history
                db()->prepare("INSERT INTO employee_status_history (employee_id, new_status, reason, changed_by)
                                VALUES (?, ?, 'New employee added', ?)")
                    ->execute([$newId, $empStatus, $_SESSION['user_id']]);

                // Create default leave balances
                $leaveTypes = getLeaveTypes();
                $year = date('Y');
                foreach ($leaveTypes as $lt) {
                    db()->prepare("INSERT INTO leave_balances (employee_id, leave_type_id, year, entitled_days, remaining_days)
                                   VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE entitled_days=VALUES(entitled_days), remaining_days=VALUES(remaining_days)")
                        ->execute([$newId, $lt['id'], $year, $lt['max_days'], $lt['max_days']]);
                }

                // Notify HR roles
                notifyRole('hr_manager', 'info', 'New Employee Added',
                    "Employee {$empNumber} - {$data['first_name']} {$data['last_name']} has been added.",
                    APP_URL . '/modules/employees/view.php?id=' . $newId);

                auditLog('employees', 'create', (int)$newId,
                    null, json_encode(['name' => $data['first_name'].' '.$data['last_name'], 'number' => $empNumber]),
                    'New employee registration');

                setFlash('success', "Employee {$empNumber} - {$data['first_name']} {$data['last_name']} added successfully. Kiosk PIN: {$pin}");
                header('Location: ' . APP_URL . '/modules/employees/view.php?id=' . $newId);
                exit;
            }
        }
    }
}

$departments = getDepartments();
$positions   = getPositions();
$supervisors = db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as name FROM employees WHERE status = 'active' ORDER BY first_name")->fetchAll();
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/index.php">Employees</a></li>
                <li class="breadcrumb-item active">Add Employee</li>
            </ol>
        </nav>
        <h1 class="page-title">Add New Employee</h1>
        <p class="page-subtitle">Employee number will be auto-generated</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong>Please fix the following errors:</strong>
    <ul style="margin:6px 0 0;padding-left:18px;">
        <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" data-validate>
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

    <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start;">

        <!-- LEFT COLUMN -->
        <div>
            <!-- Personal Information -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Personal Information</span></div>
                <div class="card-body">
                    <div class="form-row-3" style="margin-bottom:16px;">
                        <div class="form-group">
                            <label class="form-label">First Name <span class="required">*</span></label>
                            <input type="text" class="form-control" name="first_name" value="<?= e($data['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name <span class="required">*</span></label>
                            <input type="text" class="form-control" name="last_name" value="<?= e($data['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" value="<?= e($data['middle_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="<?= e($data['date_of_birth'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="">Select</option>
                                <option value="male" <?= ($data['gender']??'')==='male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($data['gender']??'')==='female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= ($data['gender']??'')==='other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">National ID Number</label>
                            <input type="text" class="form-control" name="national_id" value="<?= e($data['national_id'] ?? '') ?>">
                            <div class="form-hint">Last 4 digits will be used as default Kiosk PIN</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Marital Status</label>
                            <select class="form-select" name="marital_status">
                                <option value="">Select</option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="divorced">Divorced</option>
                                <option value="widowed">Widowed</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Contact Information</span></div>
                <div class="card-body">
                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?= e($data['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" value="<?= e($data['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label class="form-label">Residential Address</label>
                        <textarea class="form-control" name="residential_address" rows="2"><?= e($data['residential_address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" value="<?= e($data['city'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <input type="text" class="form-control" name="country" value="<?= e($data['country'] ?? 'Papua New Guinea') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employment Details -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Employment Details</span></div>
                <div class="card-body">
                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department_id" id="department_id">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= ($data['department_id']??'')==$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Position</label>
                            <select class="form-select" name="position_id" id="position_id">
                                <option value="">Select Position</option>
                                <?php foreach ($positions as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($data['position_id']??'')==$p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group">
                            <label class="form-label">Supervisor / Manager</label>
                            <select class="form-select" name="supervisor_id">
                                <option value="">None</option>
                                <?php foreach ($supervisors as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($data['supervisor_id']??'')==$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Employment Type <span class="required">*</span></label>
                            <select class="form-select" name="employment_type" required>
                                <option value="">Select</option>
                                <option value="full_time" <?= ($data['employment_type']??'')==='full_time' ? 'selected' : '' ?>>Full Time</option>
                                <option value="part_time" <?= ($data['employment_type']??'')==='part_time' ? 'selected' : '' ?>>Part Time</option>
                                <option value="contract" <?= ($data['employment_type']??'')==='contract' ? 'selected' : '' ?>>Contract</option>
                                <option value="casual" <?= ($data['employment_type']??'')==='casual' ? 'selected' : '' ?>>Casual</option>
                                <option value="intern" <?= ($data['employment_type']??'')==='intern' ? 'selected' : '' ?>>Intern</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group">
                            <label class="form-label">Start Date <span class="required">*</span></label>
                            <input type="date" class="form-control" name="start_date" value="<?= e($data['start_date'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contract End Date</label>
                            <input type="date" class="form-control" name="contract_end_date" value="<?= e($data['contract_end_date'] ?? '') ?>">
                            <div class="form-hint">Leave blank for permanent positions</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Probation Start</label>
                            <input type="date" class="form-control" name="probation_start" value="<?= e($data['probation_start'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Probation End</label>
                            <input type="date" class="form-control" name="probation_end" value="<?= e($data['probation_end'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Details -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Bank Details</span></div>
                <div class="card-body">
                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group">
                            <label class="form-label">Bank Name</label>
                            <input type="text" class="form-control" name="bank_name" value="<?= e($data['bank_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Number</label>
                            <input type="text" class="form-control" name="bank_account_number" value="<?= e($data['bank_account_number'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Branch Code</label>
                            <input type="text" class="form-control" name="bank_branch_code" value="<?= e($data['bank_branch_code'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Type</label>
                            <input type="text" class="form-control" name="bank_account_type" placeholder="e.g. Cheque, Savings" value="<?= e($data['bank_account_type'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Emergency Contact & Next of Kin</span></div>
                <div class="card-body">
                    <div class="form-section-title">Emergency Contact</div>
                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="emergency_contact_name" value="<?= e($data['emergency_contact_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Relationship</label>
                            <input type="text" class="form-control" name="emergency_contact_relation" value="<?= e($data['emergency_contact_relation'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="emergency_contact_phone" value="<?= e($data['emergency_contact_phone'] ?? '') ?>">
                    </div>

                    <div class="form-section-title">Next of Kin</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="nok_name" value="<?= e($data['nok_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Relationship</label>
                            <input type="text" class="form-control" name="nok_relation" value="<?= e($data['nok_relation'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="nok_phone" value="<?= e($data['nok_phone'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div>
            <!-- Photo Upload -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Employee Photo</span></div>
                <div class="card-body" style="text-align:center;">
                    <div style="width:100px;height:100px;border-radius:50%;background:var(--bg);border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;overflow:hidden;">
                        <img id="photoPreview" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" id="photoIcon"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <input type="file" name="photo" id="photoInput" accept="image/*" style="display:none;" data-photo-preview="photoPreview">
                    <label for="photoInput" class="btn btn-secondary btn-sm" style="cursor:pointer;">Upload Photo</label>
                    <div class="form-hint" style="margin-top:6px;">JPG, PNG. Max 10MB.</div>
                </div>
            </div>

            <!-- Auto-Generated Number Preview -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Employee Number</span></div>
                <div class="card-body" style="text-align:center;">
                    <div style="font-size:0.92rem;font-weight:700;color:var(--primary);letter-spacing:.02em;padding:12px;background:var(--primary-light);border-radius:8px;font-family:monospace;">
                        <?= e(generateEmployeeNumber()) ?>
                    </div>
                    <div class="form-hint" style="margin-top:8px;">Auto-generated on save</div>
                </div>
            </div>

            <!-- Work Location -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Work Location</span></div>
                <div class="card-body">
                    <input type="text" class="form-control" name="work_location" placeholder="e.g. Head Office, Site A" value="<?= e($data['work_location'] ?? '') ?>">
                </div>
            </div>

            <!-- Summary Card -->
            <div class="card" style="background:var(--bg);border-color:var(--border-light);">
                <div class="card-body" style="padding:14px;">
                    <div style="font-size:0.72rem;color:var(--text-secondary);line-height:1.7;">
                        <div>✓ Employee number auto-generated</div>
                        <div>✓ Kiosk PIN set from National ID</div>
                        <div>✓ Default leave balances created</div>
                        <div>✓ Status: Active or Probation</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div style="display:flex;gap:8px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
        <button type="submit" class="btn btn-primary">Save Employee</button>
        <a href="<?= APP_URL ?>/modules/employees/index.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
document.getElementById('photoInput').addEventListener('change', function() {
    const img = document.getElementById('photoPreview');
    const icon = document.getElementById('photoIcon');
    if (this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; img.style.display = ''; icon.style.display = 'none'; };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
