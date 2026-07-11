<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('users.manage');

$pageTitle  = 'User Management';
$activeMenu = 'users';

$errors = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $postAction = $_POST['post_action'] ?? '';

    if ($postAction === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? '';
        $empId    = (int)($_POST['employee_id'] ?? 0) ?: null;
        $pass     = $_POST['password'] ?? '';

        if (!$username) $errors[] = 'Username required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if (!$role) $errors[] = 'Role required.';
        if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';

        if (empty($errors)) {
            try {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                db()->prepare("INSERT INTO users (username,email,password_hash,role,employee_id,is_active,must_change_password,created_by)
                                VALUES (?,?,?,?,?,1,1,?)")
                    ->execute([$username, $email, $hash, $role, $empId, $_SESSION['user_id']]);
                auditLog('users','create_user',null,null,json_encode(['username'=>$username,'role'=>$role]));
                setFlash('success',"User '{$username}' created. They will be prompted to change password on first login.");
            } catch (PDOException $e) {
                $errors[] = 'Username or email already exists.';
            }
        }
    }

    if ($postAction === 'toggle_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid && $uid !== (int)$_SESSION['user_id']) {
            $curr = db()->prepare("SELECT is_active FROM users WHERE id=?");
            $curr->execute([$uid]);
            $u = $curr->fetch();
            db()->prepare("UPDATE users SET is_active=? WHERE id=?")->execute([$u['is_active'] ? 0 : 1, $uid]);
            auditLog('users','toggle_user',$uid);
            setFlash('success','User status updated.');
        }
        header('Location: ' . APP_URL . '/modules/users/index.php'); exit;
    }

    if ($postAction === 'reset_password') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $newpass = trim($_POST['new_password'] ?? '');
        if ($uid && strlen($newpass) >= 6) {
            $hash = password_hash($newpass, PASSWORD_BCRYPT, ['cost'=>12]);
            db()->prepare("UPDATE users SET password_hash=?, must_change_password=1 WHERE id=?")->execute([$hash,$uid]);
            auditLog('users','reset_password',$uid,null,null,'Admin reset password');
            setFlash('success','Password reset. User must change on next login.');
        }
        header('Location: ' . APP_URL . '/modules/users/index.php'); exit;
    }
}

$users = db()->query("SELECT u.*, e.first_name, e.last_name, e.employee_number FROM users u LEFT JOIN employees e ON u.employee_id=e.id ORDER BY u.created_at DESC")->fetchAll();
$employees = db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as name FROM employees WHERE status='active' ORDER BY first_name")->fetchAll();

$roleLabels = [
    'super_admin'   => ['Super Admin',  'badge-danger'],
    'hr_manager'    => ['HR Manager',   'badge-primary'],
    'hr_officer'    => ['HR Officer',   'badge-info'],
    'supervisor'    => ['Supervisor',   'badge-warning'],
    'employee'      => ['Employee',     'badge-secondary'],
    'finance_viewer'=> ['Finance View', 'badge-secondary'],
];

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle"><?= count($users) ?> system users</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary btn-sm" data-modal-open="addUserModal">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add User
        </button>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach($errors as $e) echo e($e).'<br>'; ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Linked Employee</th>
                    <th>Last Login</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div class="emp-row-info">
                        <div class="emp-avatar" style="width:28px;height:28px;font-size:0.7rem;">
                            <?= strtoupper(substr($u['username'],0,1)) ?>
                        </div>
                        <div>
                            <div class="emp-name"><?= e($u['username']) ?></div>
                            <?php if ($u['must_change_password']): ?>
                                <div class="emp-num" style="color:var(--warning);">Must change password</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="font-size:0.75rem;"><?= e($u['email']) ?></td>
                <td>
                    <?php $rl = $roleLabels[$u['role']] ?? ['Unknown','badge-secondary']; ?>
                    <span class="badge <?= $rl[1] ?>"><?= $rl[0] ?></span>
                </td>
                <td style="font-size:0.75rem;">
                    <?php if ($u['first_name']): ?>
                        <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $u['employee_id'] ?>" style="color:var(--primary);">
                            <?= e($u['first_name'].' '.$u['last_name']) ?>
                        </a>
                        <div class="emp-num"><?= e($u['employee_number']) ?></div>
                    <?php else: ?>
                        <span style="color:var(--text-muted);">—</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.72rem;color:var(--text-muted);">
                    <?= $u['last_login'] ? formatDateTime($u['last_login']) : 'Never' ?>
                </td>
                <td>
                    <?= $u['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>' ?>
                </td>
                <td>
                    <div class="table-actions">
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="post_action" value="toggle_user">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" title="<?= $u['is_active'] ? 'Disable' : 'Enable' ?>">
                                <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                            </button>
                        </form>
                        <button class="btn btn-ghost btn-sm" onclick="showResetModal(<?= $u['id'] ?>, '<?= e($u['username']) ?>')">Reset PW</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Add System User</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="post_action" value="add_user">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username <span class="required">*</span></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role <span class="required">*</span></label>
                        <select class="form-select" name="role" required>
                            <option value="">Select role</option>
                            <?php foreach ($roleLabels as $rv => [$rl, $_]): ?>
                                <option value="<?= $rv ?>"><?= $rl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Temporary Password <span class="required">*</span></label>
                        <input type="password" class="form-control" name="password" minlength="6" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Link to Employee (optional)</label>
                    <select class="form-select" name="employee_id">
                        <option value="">No employee link</option>
                        <?php foreach ($employees as $em): ?>
                            <option value="<?= $em['id'] ?>"><?= e($em['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="resetPwModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h5 class="modal-title">Reset Password — <span id="resetUserName"></span></h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="post_action" value="reset_password">
            <input type="hidden" name="user_id" id="resetUserId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">New Password <span class="required">*</span></label>
                    <input type="password" class="form-control" name="new_password" minlength="6" required>
                </div>
                <div class="alert alert-warning" style="margin:0;">User will be forced to change password on next login.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function showResetModal(uid, uname) {
    document.getElementById('resetUserId').value = uid;
    document.getElementById('resetUserName').textContent = uname;
    window.openModal('resetPwModal');
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
