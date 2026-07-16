<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('consultants.delete', 'delete');

// KOM-0xx: this was previously a single-click, JS-confirm()-only instant
// delete with no server-side impact preview and no type-to-confirm
// safeguard -- unlike modules/employees/delete.php's established pattern
// for the exact same class of action (an irreversible hard delete that
// cascades to related history). A consultant with real attendance/scope
// history could be permanently erased by one accidental click.
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/consultants/index.php'); exit; }

$stmt = db()->prepare("SELECT * FROM consultants WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$con = $stmt->fetch();
if (!$con) {
    setFlash('error', 'Consultant not found.');
    header('Location: ' . APP_URL . '/modules/consultants/index.php');
    exit;
}

$impact = [];
foreach ([
    'consultant_attendance' => 'Attendance records',
    'consultant_scopes'     => 'Scope items',
] as $table => $label) {
    $c = db()->prepare("SELECT COUNT(*) FROM `$table` WHERE consultant_id=?");
    $c->execute([$id]);
    $count = (int)$c->fetchColumn();
    if ($count > 0) $impact[] = ['label' => $label, 'count' => $count];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.'); header('Location: ' . APP_URL . '/modules/consultants/delete.php?id=' . $id); exit;
    }
    if (($_POST['confirm_number'] ?? '') !== $con['consultant_number']) {
        setFlash('error', 'Consultant number did not match. Deletion cancelled.');
        header('Location: ' . APP_URL . '/modules/consultants/delete.php?id=' . $id); exit;
    }

    auditLog('consultants', 'hard_delete', $id,
        json_encode(['consultant_number' => $con['consultant_number'], 'name' => $con['first_name'].' '.$con['last_name'], 'status' => $con['status']]),
        null,
        'Consultant permanently deleted by ' . $_SESSION['user_name'] . '. ' . count($impact) . ' related record set(s) removed.'
    );

    db()->prepare("DELETE FROM consultants WHERE id=?")->execute([$id]);

    setFlash('success', 'Consultant ' . $con['consultant_number'] . ' (' . $con['first_name'] . ' ' . $con['last_name'] . ') has been permanently deleted along with all associated records.');
    header('Location: ' . APP_URL . '/modules/consultants/index.php');
    exit;
}

$pageTitle  = 'Delete Consultant — ' . $con['first_name'] . ' ' . $con['last_name'];
$activeMenu = 'consultants';
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/consultants/index.php">Consultants</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/consultants/view.php?id=<?= $id ?>"><?= e($con['first_name'].' '.$con['last_name']) ?></a></li>
                <li class="breadcrumb-item active">Permanent Delete</li>
            </ol>
        </nav>
        <h1 class="page-title" style="color:var(--danger);">Permanently Delete Consultant</h1>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/modules/consultants/view.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">Cancel — Keep Consultant</a>
    </div>
</div>

<div class="alert alert-danger" style="margin-bottom:20px;">
    <strong>This action is permanent and cannot be undone.</strong><br>
    All records listed below will be permanently erased. If you want to keep the records but mark this consultant
    inactive instead, edit their status to "Completed" or "Terminated" rather than deleting.
</div>

<div class="card" style="max-width:560px;">
    <div class="card-header"><span class="card-title">Impact Summary</span></div>
    <div class="card-body">
        <div style="margin-bottom:14px;"><strong><?= e($con['consultant_number']) ?></strong> — <?= e($con['first_name'].' '.$con['last_name']) ?></div>
        <?php if (empty($impact)): ?>
        <p style="color:var(--text-muted);font-size:0.85rem;">No related attendance or scope records exist for this consultant.</p>
        <?php else: ?>
        <ul style="font-size:0.85rem;">
            <?php foreach ($impact as $i): ?>
            <li><?= (int)$i['count'] ?> <?= e($i['label']) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="card-body" style="border-top:1px solid var(--border);">
            <label class="form-label">Type the consultant number (<code><?= e($con['consultant_number']) ?></code>) to confirm</label>
            <input type="text" class="form-control" name="confirm_number" required autocomplete="off">
        </div>
        <div class="card-footer" style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-danger">Permanently Delete</button>
            <a href="<?= APP_URL ?>/modules/consultants/view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
