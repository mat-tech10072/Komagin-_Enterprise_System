<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('roles.manage');

$pageTitle  = 'Roles & Permissions';
$activeMenu = 'roles';

$ROLES = [
    'hr_manager'         => 'HR Manager',
    'hr_officer'         => 'HR Officer',
    'payroll_manager'    => 'Payroll Manager',
    'payroll_officer'    => 'Payroll Officer',
    'recruitment_officer'=> 'Recruitment Officer',
    'training_officer'   => 'Training Officer',
    'supervisor'         => 'Supervisor',
    'employee'           => 'Employee',
    'finance_viewer'     => 'Finance Viewer',
    'kiosk_terminal'     => 'Kiosk Terminal',
];

$ACTIONS = ['view','create','edit','delete','approve','export','publish','share'];
$ACTION_LABELS = ['view'=>'View','create'=>'Create','edit'=>'Edit','delete'=>'Delete',
                  'approve'=>'Approve','export'=>'Export','publish'=>'Publish','share'=>'Share'];

// Handle toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $toggleRole  = $_POST['role']       ?? '';
    $toggleSlug  = $_POST['permission'] ?? '';
    $toggleAction= $_POST['paction']    ?? 'view';
    $currentVal  = (int)($_POST['current_value'] ?? 0);

    if (!array_key_exists($toggleRole, $ROLES) || !$toggleSlug || !in_array($toggleAction, $ACTIONS)) {
        setFlash('error', 'Invalid request.');
    } else {
        $col = 'can_' . $toggleAction;
        $newVal = $currentVal ? 0 : 1;

        $permStmt = db()->prepare("SELECT id FROM permissions WHERE slug=?");
        $permStmt->execute([$toggleSlug]);
        $perm = $permStmt->fetch();

        if ($perm) {
            $existing = db()->prepare("SELECT id FROM role_permissions WHERE role=? AND permission_id=?");
            $existing->execute([$toggleRole, $perm['id']]);
            if ($existing->fetch()) {
                db()->prepare("UPDATE role_permissions SET `$col`=? WHERE role=? AND permission_id=?")
                    ->execute([$newVal, $toggleRole, $perm['id']]);
            } else {
                db()->prepare("INSERT INTO role_permissions (role, permission_id, `$col`) VALUES (?,?,?)")
                    ->execute([$toggleRole, $perm['id'], $newVal]);
            }
            auditLog('roles', 'toggle_permission', $perm['id'], null,
                json_encode(['role'=>$toggleRole,'perm'=>$toggleSlug,'action'=>$toggleAction,'granted'=>(bool)$newVal]));
            setFlash('success', ucfirst($toggleRole) . ': ' . $toggleSlug . '.' . $toggleAction . ' set to ' . ($newVal?'GRANTED':'DENIED') . '.');
        }
    }
    header('Location: ' . APP_URL . '/modules/roles/index.php'); exit;
}

// Load permission matrix
$permsStmt = db()->query("SELECT * FROM permissions ORDER BY module, name");
$allPerms  = $permsStmt->fetchAll();

$permsByModule = [];
foreach ($allPerms as $p) {
    $permsByModule[$p['module']][] = $p;
}

// Load existing role_permissions
$rpStmt = db()->query("SELECT rp.role, p.slug, rp.can_view, rp.can_create, rp.can_edit, rp.can_delete,
    rp.can_approve, rp.can_export, rp.can_publish, rp.can_share
    FROM role_permissions rp JOIN permissions p ON rp.permission_id=p.id");
$matrix = []; // $matrix[$role][$slug][$action] = 0|1
foreach ($rpStmt->fetchAll() as $rp) {
    foreach ($ACTIONS as $a) {
        $matrix[$rp['role']][$rp['slug']]['can_'.$a] = (int)$rp['can_'.$a];
    }
}

$activeTab = $_GET['role'] ?? 'hr_manager';
if (!array_key_exists($activeTab, $ROLES)) $activeTab = 'hr_manager';

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Roles &amp; Permissions</li>
            </ol>
        </nav>
        <h1 class="page-title">Roles &amp; Permissions</h1>
        <p class="page-subtitle">Database-driven access control matrix — <?= count($ROLES) ?> roles · <?= count($allPerms) ?> permissions · <?= count($ACTIONS) ?> action types</p>
    </div>
</div>

<!-- Role Tabs -->
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;">
    <a href="?role=__super_admin__" style="padding:6px 14px;border-radius:6px;font-size:0.75rem;font-weight:600;background:<?= $activeTab==='__super_admin__'?'var(--primary)':'var(--bg-card)' ?>;color:<?= $activeTab==='__super_admin__'?'#fff':'var(--text-muted)' ?>;border:1px solid var(--border);text-decoration:none;">
        Super Admin (all)
    </a>
    <?php foreach ($ROLES as $roleKey => $roleLabel): ?>
    <a href="?role=<?= $roleKey ?>" style="padding:6px 14px;border-radius:6px;font-size:0.75rem;font-weight:600;background:<?= $activeTab===$roleKey?'var(--primary)':'var(--bg-card)' ?>;color:<?= $activeTab===$roleKey?'#fff':'var(--text-muted)' ?>;border:1px solid var(--border);text-decoration:none;">
        <?= e($roleLabel) ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($activeTab === '__super_admin__'): ?>
<div class="card">
    <div class="card-header"><span class="card-title">Super Admin</span></div>
    <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:0.85rem;">
        Super Admin bypasses all permission checks at the code level.<br>
        No configuration needed — this role always has full access to everything.
    </div>
</div>
<?php else: ?>

<?php $currentRole = $activeTab; $currentLabel = $ROLES[$activeTab]; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><?= e($currentLabel) ?> — Permission Matrix</span>
        <span style="font-size:0.72rem;color:var(--text-muted);">Click any cell to toggle. Changes take effect immediately.</span>
    </div>
    <div class="table-wrapper" style="border:none;overflow-x:auto;">
        <table class="table" id="permMatrix" style="min-width:900px;font-size:0.75rem;">
            <thead>
                <tr>
                    <th style="width:260px;min-width:220px;">Permission</th>
                    <?php foreach ($ACTIONS as $action): ?>
                    <th style="text-align:center;width:72px;"><?= $ACTION_LABELS[$action] ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($permsByModule as $module => $perms): ?>
                <tr>
                    <td colspan="<?= count($ACTIONS)+1 ?>" style="background:var(--bg);font-size:0.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--text-muted);padding:6px 16px;">
                        <?= e(strtoupper($module)) ?>
                    </td>
                </tr>
                <?php foreach ($perms as $perm): ?>
                <tr>
                    <td style="padding-left:20px;">
                        <div style="font-weight:500;"><?= e($perm['name']) ?></div>
                        <div style="font-size:0.63rem;color:var(--text-muted);font-family:monospace;"><?= e($perm['slug']) ?></div>
                    </td>
                    <?php foreach ($ACTIONS as $action): ?>
                    <?php $colKey = 'can_'.$action; $hasIt = (int)($matrix[$currentRole][$perm['slug']][$colKey] ?? 0); ?>
                    <td style="text-align:center;padding:4px;">
                        <form method="POST" style="display:inline;" class="perm-form">
                            <input type="hidden" name="csrf_token"    value="<?= $csrf ?>">
                            <input type="hidden" name="role"          value="<?= e($currentRole) ?>">
                            <input type="hidden" name="permission"    value="<?= e($perm['slug']) ?>">
                            <input type="hidden" name="paction"       value="<?= e($action) ?>">
                            <input type="hidden" name="current_value" value="<?= $hasIt ?>">
                            <button type="submit" title="<?= e($action) ?>"
                                style="background:none;border:none;cursor:pointer;padding:3px 6px;border-radius:4px;font-size:0.85rem;
                                       color:<?= $hasIt?'var(--success)':'#CBD5E1' ?>;
                                       background:<?= $hasIt?'rgba(34,197,94,.1)':'transparent' ?>;">
                                <?= $hasIt ? '✓' : '·' ?>
                            </button>
                        </form>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display:flex;gap:24px;font-size:0.75rem;color:var(--text-muted);margin-top:12px;padding:0 4px;">
    <span><span style="color:var(--success);font-weight:700;">✓</span> = Granted</span>
    <span><span style="color:#CBD5E1;">·</span> = Not granted</span>
    <span>Click any cell to toggle. Changes are immediate and audited.</span>
</div>

<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
