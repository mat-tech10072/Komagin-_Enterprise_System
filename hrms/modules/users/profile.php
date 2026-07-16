<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();

$pageTitle  = 'My Profile';
$activeMenu = '';

$userId = $_SESSION['user_id'];

// Load full user row (own columns only — not the COALESCE view from currentUser())
$stmt = db()->prepare("SELECT u.*, e.first_name AS emp_first, e.last_name AS emp_last,
    e.photo AS emp_photo, e.employee_number, d.name AS dept_name, p.title AS position_title
    FROM users u
    LEFT JOIN employees e ON u.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions   p ON e.position_id   = p.id
    WHERE u.id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { header('Location: ' . APP_URL . '/auth/logout.php'); exit; }

// Effective display values (employee overrides user)
$displayFirst = $row['emp_first'] ?? $row['first_name'] ?? '';
$displayLast  = $row['emp_last']  ?? $row['last_name']  ?? '';
$displayPhoto = $row['emp_photo'] ?? $row['profile_photo'] ?? null;
$isLinked     = !empty($row['employee_id']); // linked to an employee record

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';

        // ── Personal details ──────────────────────────────────────────────
        if ($action === 'update_profile') {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName  = trim($_POST['last_name']  ?? '');
            $email     = trim($_POST['email']       ?? '');
            $jobTitle  = trim($_POST['job_title']   ?? '');
            $phone     = trim($_POST['phone']       ?? '');
            $bio       = trim($_POST['bio']         ?? '');

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';

            // Check email uniqueness
            if (empty($errors)) {
                $dup = db()->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $dup->execute([$email, $userId]);
                if ($dup->fetchColumn()) $errors[] = 'That email address is already used by another account.';
            }

            // Handle photo upload
            $photoPath = $row['profile_photo'];
            if (!empty($_FILES['profile_photo']['name'])) {
                $up = uploadFile($_FILES['profile_photo'], 'avatars', ALLOWED_IMAGE_TYPES);
                if ($up['success']) {
                    $photoPath = $up['path'];
                } else {
                    $errors[] = 'Photo upload failed: ' . $up['error'];
                }
            }

            if (empty($errors)) {
                db()->prepare("UPDATE users SET
                    first_name=?, last_name=?, email=?, job_title=?, phone=?, bio=?, profile_photo=?
                    WHERE id=?")
                    ->execute([$firstName ?: null, $lastName ?: null, $email, $jobTitle ?: null, $phone ?: null, $bio ?: null, $photoPath, $userId]);

                auditLog('users', 'update_profile', $userId,
                    json_encode(['email' => $row['email']]),
                    json_encode(['email' => $email, 'name' => "$firstName $lastName"])
                );
                setFlash('success', 'Profile updated successfully.');
                header('Location: ' . APP_URL . '/modules/users/profile.php');
                exit;
            }
        }

        // ── Password change ──────────────────────────────────────────────
        elseif ($action === 'change_password') {
            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password']     ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (!password_verify($current, $row['password_hash'])) $errors[] = 'Current password is incorrect.';
            if (strlen($new) < 8)  $errors[] = 'New password must be at least 8 characters.';
            if ($new !== $confirm)  $errors[] = 'Passwords do not match.';

            if (empty($errors)) {
                db()->prepare("UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?")
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
                auditLog('users', 'change_password', $userId, null, null);
                setFlash('success', 'Password changed successfully.');
                header('Location: ' . APP_URL . '/modules/users/profile.php');
                exit;
            }
        }
    }
}

// Refresh row after failed POST to keep values
$firstName = $_POST['first_name'] ?? $row['first_name'] ?? '';
$lastName  = $_POST['last_name']  ?? $row['last_name']  ?? '';
$email     = $_POST['email']      ?? $row['email']      ?? '';
$jobTitle  = $_POST['job_title']  ?? $row['job_title']  ?? '';
$phone     = $_POST['phone']      ?? $row['phone']      ?? '';
$bio       = $_POST['bio']        ?? $row['bio']        ?? '';

// Recent activity
$activity = db()->prepare("SELECT * FROM audit_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 12");
$activity->execute([$userId]);
$recentActivity = $activity->fetchAll(PDO::FETCH_ASSOC);

$initials = strtoupper(substr($displayFirst ?: $row['username'], 0, 1) . substr($displayLast, 0, 1));

$roleLabels = [
    'super_admin'        => 'Super Admin',
    'hr_manager'         => 'HR Manager',
    'hr_officer'         => 'HR Officer',
    'hrofficer'          => 'HR Officer',
    'supervisor'         => 'Supervisor',
    'employee'           => 'Employee',
    'finance_viewer'     => 'Finance Viewer',
    'payroll_manager'    => 'Payroll Manager',
    'payroll_officer'    => 'Payroll Officer',
    'recruitment_officer'=> 'Recruitment Officer',
    'training_officer'   => 'Training Officer',
];
$roleBadge = [
    'super_admin'     => 'badge-danger',
    'hr_manager'      => 'badge-primary',
    'hr_officer'      => 'badge-info',
    'hrofficer'       => 'badge-info',
    'supervisor'      => 'badge-warning',
    'payroll_manager' => 'badge-success',
    'payroll_officer' => 'badge-success',
    'finance_viewer'  => 'badge-success',
];

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-subtitle">Manage your personal details, photo, and account settings</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach($errors as $err) echo e($err) . '<br>'; ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start;">

    <!-- Left: Avatar Card + Stats -->
    <div>
        <div class="card" style="margin-bottom:16px;overflow:visible;">
            <div class="card-body" style="text-align:center;padding:28px 20px;">
                <!-- Avatar -->
                <div style="position:relative;display:inline-block;margin-bottom:16px;">
                    <?php if ($displayPhoto): ?>
                    <img src="<?= APP_URL ?>/<?= e($displayPhoto) ?>"
                         id="avatarPreview"
                         alt="Profile photo"
                         style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--border);display:block;">
                    <?php else: ?>
                    <div id="avatarInitials" style="width:88px;height:88px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;margin:0 auto;">
                        <?= e($initials) ?>
                    </div>
                    <img id="avatarPreview" src="" alt="" style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--border);display:none;">
                    <?php endif; ?>
                    <label for="photoInput" title="Change photo"
                        style="position:absolute;bottom:0;right:0;width:26px;height:26px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid var(--bg-card);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    </label>
                </div>

                <div style="font-weight:700;font-size:1.05rem;margin-bottom:3px;">
                    <?= e(trim("$displayFirst $displayLast") ?: $row['username']) ?>
                </div>
                <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:8px;">
                    <?= e($row['job_title'] ?? ($row['position_title'] ?? '')) ?>
                </div>
                <span class="badge <?= $roleBadge[$row['role']] ?? 'badge-secondary' ?>">
                    <?= $roleLabels[$row['role']] ?? ucwords(str_replace('_', ' ', $row['role'])) ?>
                </span>

                <?php if ($row['employee_number']): ?>
                <div style="margin-top:10px;font-size:0.7rem;color:var(--text-muted);font-family:monospace;"><?= e($row['employee_number']) ?></div>
                <?php endif; ?>
                <?php if ($row['dept_name']): ?>
                <div style="margin-top:4px;font-size:0.72rem;color:var(--text-muted);"><?= e($row['dept_name']) ?></div>
                <?php endif; ?>
            </div>
            <div class="card-footer" style="text-align:center;font-size:0.72rem;color:var(--text-muted);">
                Last login: <?= $row['last_login'] ? formatDateTime($row['last_login']) : 'N/A' ?>
            </div>
        </div>

        <?php if ($isLinked): ?>
        <div class="alert alert-info" style="font-size:0.75rem;padding:10px 12px;">
            Your name and photo are managed through your employee record. You can still set a job title, phone, and bio below.
        </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header"><span class="card-title">Recent Activity</span></div>
            <?php if (empty($recentActivity)): ?>
            <div style="padding:16px;font-size:0.78rem;color:var(--text-muted);">No recent activity.</div>
            <?php else: ?>
            <div>
                <?php foreach ($recentActivity as $act): ?>
                <div style="padding:9px 16px;border-bottom:1px solid var(--border);font-size:0.74rem;">
                    <div style="display:flex;gap:6px;align-items:baseline;">
                        <span class="badge badge-secondary" style="font-size:0.58rem;flex-shrink:0;"><?= e(strtoupper($act['module'])) ?></span>
                        <span><?= e(str_replace('_', ' ', $act['action'])) ?></span>
                    </div>
                    <div style="color:var(--text-muted);font-size:0.65rem;margin-top:2px;"><?= formatDateTime($act['created_at']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Edit Forms -->
    <div>
        <!-- Personal Details Form -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Personal Details</span></div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="update_profile">
                <!-- Hidden photo input triggered by camera icon -->
                <input type="file" id="photoInput" name="profile_photo" accept="image/*" style="display:none;" onchange="previewPhoto(this)">

                <div class="card-body">
                    <?php if (!$isLinked): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" value="<?= e($firstName) ?>" placeholder="Your first name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="<?= e($lastName) ?>" placeholder="Your last name">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" value="<?= e($displayFirst) ?>" disabled title="Managed by your employee record">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" value="<?= e($displayLast) ?>" disabled title="Managed by your employee record">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email Address <span style="color:var(--danger)">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?= e($email) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= e($row['username']) ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Job Title</label>
                            <input type="text" class="form-control" name="job_title" value="<?= e($jobTitle) ?>" placeholder="e.g. HR Manager">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?= e($phone) ?>" placeholder="+675 7xxx xxxx">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bio / About</label>
                        <textarea class="form-control" name="bio" rows="3" style="resize:vertical;" placeholder="A short description about yourself..."><?= e($bio) ?></textarea>
                    </div>

                    <?php if (!$isLinked): ?>
                    <div class="form-group">
                        <label class="form-label">Profile Photo</label>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <?php if ($displayPhoto): ?>
                            <img src="<?= APP_URL ?>/<?= e($displayPhoto) ?>" id="photoThumb"
                                 style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
                            <?php else: ?>
                            <div id="photoThumb" style="width:48px;height:48px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;"><?= e($initials) ?></div>
                            <?php endif; ?>
                            <label for="photoInput" class="btn btn-secondary btn-sm" style="cursor:pointer;margin:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                Choose Photo
                            </label>
                            <span id="photoFileName" style="font-size:0.72rem;color:var(--text-muted);"></span>
                        </div>
                        <div style="font-size:0.7rem;color:var(--text-muted);margin-top:6px;">JPG, PNG, WebP — max 10 MB. Shown in the sidebar and topbar.</div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer" style="display:flex;gap:8px;align-items:center;">
                    <button type="submit" class="btn btn-primary btn-sm">Save Details</button>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header"><span class="card-title">Change Password</span></div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Current Password <span style="color:var(--danger)">*</span></label>
                        <input type="password" class="form-control" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">New Password <span style="color:var(--danger)">*</span></label>
                            <input type="password" class="form-control" name="new_password" required minlength="8" autocomplete="new-password">
                            <div style="font-size:0.68rem;color:var(--text-muted);margin-top:3px;">Minimum 8 characters</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password <span style="color:var(--danger)">*</span></label>
                            <input type="password" class="form-control" name="confirm_password" required autocomplete="new-password">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning btn-sm">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        // Update big avatar in left card
        const preview = document.getElementById('avatarPreview');
        const initials = document.getElementById('avatarInitials');
        if (preview) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        if (initials) initials.style.display = 'none';

        // Update small thumb in form
        const thumb = document.getElementById('photoThumb');
        if (thumb && thumb.tagName === 'IMG') {
            thumb.src = e.target.result;
        } else if (thumb) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.id = 'photoThumb';
            img.style.cssText = 'width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--border);';
            thumb.replaceWith(img);
        }

        // Show file name
        const nameEl = document.getElementById('photoFileName');
        if (nameEl) nameEl.textContent = file.name;
    };
    reader.readAsDataURL(file);
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
