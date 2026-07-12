<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('employees.edit', 'edit');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$emp = getEmployee($id);
if (!$emp) { setFlash('error','Employee not found.'); header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$pageTitle  = 'Edit Employee';
$activeMenu = 'employees';
$errors     = [];

$departments = getDepartments();
$positions   = getPositions();
$supervisors = db()->query("SELECT id, CONCAT(first_name,' ',last_name) as name FROM employees WHERE status IN ('active','probation') ORDER BY first_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $firstName   = trim($_POST['first_name'] ?? '');
        $lastName    = trim($_POST['last_name'] ?? '');
        $dob         = $_POST['date_of_birth'] ?? '';
        $gender      = $_POST['gender'] ?? '';
        $nationalId  = trim($_POST['national_id'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $personalEmail = trim($_POST['personal_email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $address     = trim($_POST['residential_address'] ?? '');
        $city        = trim($_POST['city'] ?? '');
        $country     = $_POST['country'] ?? 'Papua New Guinea';
        $deptId      = (int)($_POST['department_id'] ?? 0) ?: null;
        $posId       = (int)($_POST['position_id'] ?? 0) ?: null;
        $supId       = (int)($_POST['supervisor_id'] ?? 0) ?: null;
        $empType     = $_POST['employment_type'] ?? '';
        $startDate   = $_POST['start_date'] ?? '';
        $contractEnd = $_POST['contract_end_date'] ?? '';
        $probStart   = $_POST['probation_start'] ?? '';
        $probEnd     = $_POST['probation_end'] ?? '';
        $workLocation = trim($_POST['work_location'] ?? '');
        // Bank and salary fields: only accepted if submitter has payroll access
        $bankName    = canViewBankData()   ? trim($_POST['bank_name']         ?? '') : ($emp['bank_name']            ?? '');
        $bankAccount = canViewBankData()   ? trim($_POST['bank_account_number'] ?? '') : ($emp['bank_account_number']   ?? '');
        $branchCode  = canViewBankData()   ? trim($_POST['bank_branch_code']    ?? '') : ($emp['bank_branch_code']      ?? '');
        $bankType    = canViewBankData()   ? ($_POST['bank_account_type']       ?? '') : ($emp['bank_account_type']     ?? '');
        $salary      = canViewSalaryData() ? trim($_POST['salary']              ?? '') : ($emp['basic_salary']          ?? '');
        $emergencyName  = trim($_POST['emergency_contact_name']     ?? '');
        $emergencyRel   = trim($_POST['emergency_contact_relation'] ?? '');
        $emergencyPhone = trim($_POST['emergency_contact_phone']    ?? '');
        $nokName     = trim($_POST['nok_name']     ?? '');
        $nokRel      = trim($_POST['nok_relation'] ?? '');
        $nokPhone    = trim($_POST['nok_phone']    ?? '');
        $marital     = $_POST['marital_status'] ?? '';
        $editReason  = trim($_POST['edit_reason'] ?? '');

        if (!$firstName) $errors[] = 'First name is required.';
        if (!$lastName)  $errors[] = 'Last name is required.';
        if (!$email)     $errors[] = 'Work email is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid work email.';
        if (!$editReason) $errors[] = 'Edit reason is required for audit.';

        // Check email unique (exclude self)
        $check = db()->prepare("SELECT id FROM employees WHERE (email=? OR personal_email=?) AND id!=?");
        $check->execute([$email,$email,$id]);
        if ($check->fetch()) $errors[] = 'Email address is already in use.';

        // Check national ID unique (exclude self) — same rehire-collision guard as add.php
        if (!empty($nationalId)) {
            $check = db()->prepare("SELECT id, employee_number FROM employees WHERE national_id=? AND id!=?");
            $check->execute([$nationalId, $id]);
            if ($existing = $check->fetch()) {
                $errors[] = "National ID already belongs to employee {$existing['employee_number']}.";
            }
        }

        if (empty($errors)) {
            $oldData = json_encode($emp);

            // Handle photo upload
            $photo = $emp['photo'];
            if (!empty($_FILES['photo']['name'])) {
                $upload = uploadFile($_FILES['photo'], 'photos', ALLOWED_IMAGE_TYPES);
                if ($upload['success']) $photo = $upload['path'];
                else $errors[] = $upload['error'];
            }

            db()->prepare("UPDATE employees SET
                first_name=?, last_name=?, date_of_birth=?, gender=?, national_id=?,
                email=?, personal_email=?, phone=?,
                residential_address=?, city=?, country=?, marital_status=?,
                department_id=?, position_id=?, supervisor_id=?,
                employment_type=?, start_date=?, contract_end_date=?,
                probation_start=?, probation_end=?,
                work_location=?, basic_salary=?,
                bank_name=?, bank_account_number=?, bank_branch_code=?, bank_account_type=?,
                emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?,
                nok_name=?, nok_relation=?, nok_phone=?,
                photo=?, updated_by=?, updated_at=NOW()
                WHERE id=?")->execute([
                $firstName, $lastName, $dob ?: null, $gender, $nationalId,
                $email, $personalEmail ?: null, $phone,
                $address, $city, $country, $marital,
                $deptId, $posId, $supId,
                $empType, $startDate, $contractEnd ?: null,
                $probStart ?: null, $probEnd ?: null,
                $workLocation, $salary ?: null,
                $bankName, $bankAccount, $branchCode, $bankType,
                $emergencyName, $emergencyRel, $emergencyPhone,
                $nokName, $nokRel, $nokPhone,
                $photo, $_SESSION['user_id'],
                $id
            ]);

            // Full new-state snapshot, not just name — a transfer (department/
            // supervisor) or promotion (position/salary) previously left no
            // reconstructable record of what actually changed in new_value,
            // only in the separately-stored old snapshot.
            auditLog('employees','edit',$id,$oldData,json_encode([
                'first_name'=>$firstName,'last_name'=>$lastName,
                'department_id'=>$deptId,'position_id'=>$posId,'supervisor_id'=>$supId,
                'employment_type'=>$empType,'basic_salary'=>$salary,
                'start_date'=>$startDate,'contract_end_date'=>$contractEnd ?: null,
            ]),$editReason);
            setFlash('success','Employee profile updated successfully.');
            header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$id);
            exit;
        }
    }
}

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/index.php">Employees</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $id ?>"><?= e($emp['first_name'].' '.$emp['last_name']) ?></a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
        <h1 class="page-title">Edit Employee</h1>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach($errors as $err) echo e($err).'<br>'; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" data-validate>
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

    <div style="display:grid;grid-template-columns:1fr 260px;gap:16px;">
        <div>
            <!-- Personal Information -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Personal Information</span></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name <span class="required">*</span></label>
                            <input type="text" class="form-control" name="first_name" value="<?= e($emp['first_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name <span class="required">*</span></label>
                            <input type="text" class="form-control" name="last_name" value="<?= e($emp['last_name']) ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="<?= e($emp['date_of_birth']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="">Select</option>
                                <?php foreach (['male'=>'Male','female'=>'Female','other'=>'Other','prefer_not_to_say'=>'Prefer not to say'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= $emp['gender']===$v?'selected':''?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">National ID / Passport</label>
                            <input type="text" class="form-control" name="national_id" value="<?= e($emp['national_id']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Marital Status</label>
                            <select class="form-select" name="marital_status">
                                <option value="">Select</option>
                                <?php foreach (['single','married','divorced','widowed'] as $ms): ?>
                                    <option value="<?= $ms ?>" <?= $emp['marital_status']===$ms?'selected':''?>><?= ucfirst($ms) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Contact Information</span></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Work Email <span class="required">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?= e($emp['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Personal Email</label>
                            <input type="email" class="form-control" name="personal_email" value="<?= e($emp['personal_email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?= e($emp['phone']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="residential_address" value="<?= e($emp['residential_address'] ?? '') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" value="<?= e($emp['city'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <input type="text" class="form-control" name="country" value="<?= e($emp['country'] ?? 'Papua New Guinea') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employment Details -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Employment Details</span></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department_id" id="deptSelect">
                                <option value="">None</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= $emp['department_id']==$d['id']?'selected':''?>><?= e($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Position</label>
                            <select class="form-select" name="position_id" id="posSelect">
                                <option value="">None</option>
                                <?php foreach ($positions as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $emp['position_id']==$p['id']?'selected':''?>><?= e($p['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Employment Type</label>
                            <select class="form-select" name="employment_type">
                                <?php foreach (['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= $emp['employment_type']===$v?'selected':''?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Supervisor</label>
                            <select class="form-select" name="supervisor_id">
                                <option value="">None</option>
                                <?php foreach ($supervisors as $s): ?>
                                    <?php if ($s['id'] != $id): ?>
                                        <option value="<?= $s['id'] ?>" <?= $emp['supervisor_id']==$s['id']?'selected':''?>><?= e($s['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?= e($emp['start_date']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contract End Date</label>
                            <input type="date" class="form-control" name="contract_end_date" value="<?= e($emp['contract_end_date'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Probation Start</label>
                            <input type="date" class="form-control" name="probation_start" value="<?= e($emp['probation_start'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Probation End</label>
                            <input type="date" class="form-control" name="probation_end" value="<?= e($emp['probation_end'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Work Location</label>
                            <input type="text" class="form-control" name="work_location" value="<?= e($emp['work_location'] ?? '') ?>" placeholder="Head Office / Remote / Branch">
                        </div>
                        <?php if (canViewSalaryData()): ?>
                        <div class="form-group">
                            <label class="form-label">Gross Salary (<?= CURRENCY_CODE ?>)</label>
                            <input type="number" class="form-control" name="salary" value="<?= e($emp['salary'] ?? '') ?>" step="0.01" min="0">
                        </div>
                        <?php else: ?>
                        <div class="form-group">
                            <label class="form-label">Gross Salary (<?= CURRENCY_CODE ?>)</label>
                            <div style="padding:8px 12px;background:var(--bg);border-radius:6px;border:1px solid var(--border);font-size:0.78rem;color:var(--text-muted);">
                                Restricted — requires Payroll access
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bank Details -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header">
                    <span class="card-title">Bank Details</span>
                    <?php if (!canViewBankData()): ?>
                    <span class="badge badge-warning" style="font-size:0.65rem;">Payroll-restricted</span>
                    <?php endif; ?>
                </div>
                <?php if (!canViewBankData()): ?>
                <div class="card-body">
                    <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:0.78rem;">
                        Bank details can only be edited by Payroll roles. Contact your Payroll Manager.
                    </div>
                </div>
                <?php else: ?>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Bank Name</label>
                            <input type="text" class="form-control" name="bank_name" value="<?= e($emp['bank_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Number</label>
                            <input type="text" class="form-control" name="bank_account_number" value="<?= e($emp['bank_account_number'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Branch Code</label>
                            <input type="text" class="form-control" name="bank_branch_code" value="<?= e($emp['bank_branch_code'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Type</label>
                            <select class="form-select" name="bank_account_type">
                                <option value="">Select</option>
                                <?php foreach (['cheque'=>'Cheque','savings'=>'Savings','transmission'=>'Transmission'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= ($emp['bank_account_type']??'')===$v?'selected':''?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; // end canViewBankData ?>
            </div>

            <!-- Emergency & Next of Kin -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header"><span class="card-title">Emergency Contact & Next of Kin</span></div>
                <div class="card-body">
                    <p style="font-size:0.72rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;">Emergency Contact</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="emergency_contact_name" value="<?= e($emp['emergency_contact_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Relationship</label>
                            <input type="text" class="form-control" name="emergency_contact_relation" value="<?= e($emp['emergency_contact_relation'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="emergency_contact_phone" value="<?= e($emp['emergency_contact_phone'] ?? '') ?>">
                    </div>
                    <hr style="border-color:var(--border);margin:16px 0;">
                    <p style="font-size:0.72rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;">Next of Kin</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="nok_name" value="<?= e($emp['nok_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Relationship</label>
                            <input type="text" class="form-control" name="nok_relation" value="<?= e($emp['nok_relation'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="nok_phone" value="<?= e($emp['nok_phone'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Audit Reason -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header" style="background:#FEF3C7;border-bottom:1px solid #FDE68A;">
                    <span class="card-title" style="color:#92400E;">Audit Trail — Edit Reason Required</span>
                </div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Reason for Edit <span class="required">*</span></label>
                        <textarea class="form-control" name="edit_reason" rows="2" required
                                  placeholder="Describe why this profile is being edited. This is permanently recorded."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right sidebar -->
        <div>
            <div class="card" style="margin-bottom:16px;position:sticky;top:76px;">
                <div class="card-header"><span class="card-title">Profile Photo</span></div>
                <div class="card-body" style="text-align:center;">
                    <?php if ($emp['photo']): ?>
                        <img id="photoPreview" src="<?= APP_URL ?>/<?= e($emp['photo']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:12px;border:2px solid var(--border);">
                    <?php else: ?>
                        <div id="photoPreview" class="emp-avatar" style="width:80px;height:80px;font-size:1.8rem;margin:0 auto 12px;">
                            <?= strtoupper(substr($emp['first_name'],0,1)) ?>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" name="photo" accept="image/jpeg,image/png,image/webp" id="photoInput">
                    <div class="form-hint">JPG/PNG. Max 5MB.</div>
                </div>
            </div>

            <div class="card" style="margin-bottom:16px;">
                <div class="card-body" style="padding:14px 16px;">
                    <div style="font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Employee Number</div>
                    <div style="font-size:1rem;font-weight:700;font-family:monospace;color:var(--primary);"><?= e($emp['employee_number']) ?></div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:8px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('photoInput')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('photoPreview');
        if (preview.tagName === 'IMG') {
            preview.src = e.target.result;
        } else {
            const img = document.createElement('img');
            img.id = 'photoPreview';
            img.style.cssText = 'width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:12px;border:2px solid var(--border);';
            img.src = e.target.result;
            preview.replaceWith(img);
        }
    };
    reader.readAsDataURL(file);
});
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
