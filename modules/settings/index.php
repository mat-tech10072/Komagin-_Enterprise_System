<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('settings.manage');

$pageTitle  = 'Settings';
$activeMenu = 'settings';

$settings = getCompanySettings();
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $section = $_POST['section'] ?? '';

        if ($section === 'company') {
            $name    = trim($_POST['company_name'] ?? '');
            $phone   = trim($_POST['phone'] ?? '');
            $email   = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($name)) $errors[] = 'Company name is required.';

            if (empty($errors)) {
                $logoPath = $settings['company_logo'] ?? null;
                if (!empty($_FILES['logo']['name'])) {
                    $upload = uploadFile($_FILES['logo'], 'company', ALLOWED_IMAGE_TYPES);
                    if ($upload['success']) $logoPath = $upload['path'];
                    else $errors[] = 'Logo upload failed: ' . $upload['error'];
                }

                if (empty($errors)) {
                    db()->prepare("UPDATE company_settings SET company_name=?, phone=?, email=?, address=?, company_logo=? WHERE id=1")
                        ->execute([$name, $phone, $email, $address, $logoPath]);
                    auditLog('settings','update_company',1,null,json_encode(['name'=>$name]));
                    clearSettingsCache();
                    setFlash('success','Company settings saved.');
                    header('Location: ' . APP_URL . '/modules/settings/index.php');
                    exit;
                }
            }
        }

        if ($section === 'attendance') {
            db()->prepare("UPDATE company_settings SET
                work_start_time=?, work_end_time=?,
                grace_period_minutes=?, break_duration_minutes=?,
                standard_work_hours=?, overtime_threshold_hours=?
                WHERE id=1")
                ->execute([
                    $_POST['work_start'] ?? '08:00',
                    $_POST['work_end']   ?? '17:00',
                    (int)($_POST['grace_period'] ?? 15),
                    (int)($_POST['break_duration'] ?? 60),
                    (float)($_POST['standard_hours'] ?? 8),
                    (float)($_POST['overtime_threshold'] ?? 8),
                ]);
            auditLog('settings','update_attendance');
            clearSettingsCache();
            setFlash('success','Attendance settings saved.');
            header('Location: ' . APP_URL . '/modules/settings/index.php?tab=attendance');
            exit;
        }

        if ($section === 'emp_number') {
            $numSettings = json_encode([
                'prefix'         => trim($_POST['prefix'] ?? 'KOM-EMP'),
                'year_format'    => 'Y',
                'number_length'  => max(1,(int)($_POST['number_length'] ?? 4)),
                'starting_number'=> max(1,(int)($_POST['starting_number'] ?? 1)),
            ]);
            db()->prepare("UPDATE company_settings SET emp_number_settings=? WHERE id=1")->execute([$numSettings]);
            auditLog('settings','update_emp_number');
            clearSettingsCache();
            setFlash('success','Employee number settings saved.');
            header('Location: ' . APP_URL . '/modules/settings/index.php?tab=emp_number');
            exit;
        }
    }
}

$activeTab = $_GET['tab'] ?? 'company';
$empNumSettings = json_decode($settings['emp_number_settings'] ?? '{}', true) ?: [];
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">System configuration and preferences</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach($errors as $e) echo e($e).'<br>'; ?></div>
<?php endif; ?>

<div class="tab-nav">
    <?php $tabs = [['company','Company'],['attendance','Attendance'],['emp_number','Employee Numbers'],['departments','Departments'],['leave_types','Leave Types']]; ?>
    <?php foreach ($tabs as [$k,$l]): ?>
        <a href="?tab=<?= $k ?>" class="tab-item <?= $activeTab===$k?'active':'' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<?php if ($activeTab === 'company'): ?>
<div style="max-width:640px;">
    <div class="card">
        <div class="card-header"><span class="card-title">Company Information</span></div>
        <form method="POST" enctype="multipart/form-data" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="section" value="company">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Company Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="company_name" value="<?= e($settings['company_name'] ?? '') ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?= e($settings['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?= e($settings['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="2"><?= e($settings['address'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Company Logo</label>
                    <?php if (!empty($settings['company_logo'])): ?>
                        <div style="margin-bottom:8px;"><img src="<?= APP_URL ?>/<?= e($settings['company_logo']) ?>" style="height:48px;"></div>
                    <?php endif; ?>
                    <input type="file" class="form-control" name="logo" accept="image/*">
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Company Settings</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($activeTab === 'attendance'): ?>
<div style="max-width:560px;">
    <div class="card">
        <div class="card-header"><span class="card-title">Attendance & Time Settings</span></div>
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="section" value="attendance">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Work Start Time</label>
                        <input type="time" class="form-control" name="work_start" value="<?= e($settings['work_start_time'] ?? '08:00') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Work End Time</label>
                        <input type="time" class="form-control" name="work_end" value="<?= e($settings['work_end_time'] ?? '17:00') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Grace Period (minutes)</label>
                        <input type="number" class="form-control" name="grace_period" min="0" max="60" value="<?= e($settings['grace_period_minutes'] ?? 15) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Break Duration (minutes)</label>
                        <input type="number" class="form-control" name="break_duration" min="0" value="<?= e($settings['break_duration_minutes'] ?? 60) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Standard Work Hours</label>
                        <input type="number" class="form-control" name="standard_hours" min="1" max="24" step="0.5" value="<?= e($settings['standard_work_hours'] ?? 8) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Overtime Threshold (hours)</label>
                        <input type="number" class="form-control" name="overtime_threshold" min="1" max="24" step="0.5" value="<?= e($settings['overtime_threshold_hours'] ?? 8) ?>">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Attendance Settings</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($activeTab === 'emp_number'): ?>
<div style="max-width:480px;">
    <div class="card">
        <div class="card-header"><span class="card-title">Employee Number Format</span></div>
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="section" value="emp_number">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Prefix</label>
                    <input type="text" class="form-control" name="prefix" value="<?= e($empNumSettings['prefix'] ?? 'KOM-EMP') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Number Length</label>
                        <input type="number" class="form-control" name="number_length" min="2" max="8" value="<?= e($empNumSettings['number_length'] ?? 4) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Starting Number</label>
                        <input type="number" class="form-control" name="starting_number" min="1" value="<?= e($empNumSettings['starting_number'] ?? 1) ?>">
                    </div>
                </div>
                <div style="background:var(--primary-light);border-radius:8px;padding:12px;font-size:0.75rem;">
                    <strong>Preview:</strong>
                    <code style="font-size:0.88rem;display:block;margin-top:4px;color:var(--primary);">
                        <?= e($empNumSettings['prefix'] ?? 'KOM-EMP') ?>-<?= date('Y') ?>-<?= str_pad($empNumSettings['starting_number'] ?? 1, $empNumSettings['number_length'] ?? 4, '0', STR_PAD_LEFT) ?>
                    </code>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Number Settings</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($activeTab === 'departments'): ?>
<?php
$depts = getDepartments();
$deptError = $deptSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '') && ($_POST['section'] ?? '') === 'dept') {
    $deptAction = $_POST['dept_action'] ?? '';
    if ($deptAction === 'add') {
        $dname = trim($_POST['dept_name'] ?? '');
        $dcode = trim($_POST['dept_code'] ?? '');
        if ($dname) {
            db()->prepare("INSERT INTO departments (name,code) VALUES (?,?)")->execute([$dname,$dcode]);
            $deptSuccess = "Department '{$dname}' added.";
        }
    } elseif ($deptAction === 'delete') {
        $did = (int)($_POST['dept_id'] ?? 0);
        db()->prepare("UPDATE departments SET is_active=0 WHERE id=?")->execute([$did]);
        $deptSuccess = "Department disabled.";
    }
    $depts = getDepartments();
}
?>
<div style="max-width:560px;">
    <?php if ($deptSuccess): ?><div class="alert alert-success"><?= e($deptSuccess) ?></div><?php endif; ?>
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><span class="card-title">Departments</span></div>
        <div class="table-wrapper" style="border:none;">
            <table class="table">
                <thead><tr><th>Name</th><th>Code</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($depts as $d): ?>
                <tr>
                    <td><?= e($d['name']) ?></td>
                    <td><?= e($d['code'] ?? '—') ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="section" value="dept">
                            <input type="hidden" name="dept_action" value="delete">
                            <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm btn-icon" data-confirm="Disable this department?" title="Disable">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <form method="POST" style="display:flex;gap:8px;">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="section" value="dept">
                <input type="hidden" name="dept_action" value="add">
                <input type="text" class="form-control" name="dept_name" placeholder="Department name" style="flex:1;" required>
                <input type="text" class="form-control" name="dept_code" placeholder="Code" style="width:80px;">
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </form>
        </div>
    </div>
</div>

<?php elseif ($activeTab === 'leave_types'): ?>
<?php $leavetypes = db()->query("SELECT * FROM leave_types ORDER BY name")->fetchAll(); ?>
<div style="max-width:700px;">
    <div class="card">
        <div class="card-header"><span class="card-title">Leave Types</span></div>
        <div class="table-wrapper" style="border:none;">
            <table class="table">
                <thead><tr><th>Name</th><th>Code</th><th>Max Days</th><th>Paid</th><th>Carry Forward</th><th>Active</th></tr></thead>
                <tbody>
                <?php foreach ($leavetypes as $lt): ?>
                <tr>
                    <td><?= e($lt['name']) ?></td>
                    <td><?= e($lt['code'] ?? '—') ?></td>
                    <td><?= $lt['max_days'] ?: 'Unlimited' ?></td>
                    <td><?= $lt['is_paid'] ? '<span class="badge badge-success">Paid</span>' : '<span class="badge badge-danger">Unpaid</span>' ?></td>
                    <td><?= $lt['carry_forward'] ? 'Yes' : 'No' ?></td>
                    <td><?= $lt['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
