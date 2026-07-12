<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('temp_employees.delete', 'delete');

// KOM-091: this was previously a single-click, JS-confirm()-only instant
// delete with no server-side confirmation safeguard at all — inconsistent
// with the established pattern already proven for the same class of
// action elsewhere (modules/employees/delete.php, modules/consultants/delete.php).
// A single accidental click on the Temporary Employees list permanently
// erased the record (contract dates, rates, notes) with no way to recover it.
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/temp_employees/index.php'); exit; }

$stmt = db()->prepare("SELECT * FROM temp_employees WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) {
    setFlash('error', 'Temporary employee not found.');
    header('Location: ' . APP_URL . '/modules/temp_employees/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
        header('Location: ' . APP_URL . '/modules/temp_employees/delete.php?id=' . $id);
        exit;
    }
    if (($_POST['confirm_number'] ?? '') !== $emp['employee_number']) {
        setFlash('error', 'Employee number did not match. Deletion cancelled.');
        header('Location: ' . APP_URL . '/modules/temp_employees/delete.php?id=' . $id);
        exit;
    }

    db()->prepare("DELETE FROM temp_employees WHERE id = ?")->execute([$id]);

    auditLog('temp_employees', 'delete', $id, null, null,
        "Deleted temp employee {$emp['employee_number']} — {$emp['first_name']} {$emp['last_name']}");

    setFlash('success', "{$emp['first_name']} {$emp['last_name']} ({$emp['employee_number']}) has been deleted.");
    header('Location: ' . APP_URL . '/modules/temp_employees/index.php');
    exit;
}

$csrf = generateCsrfToken();
$pageTitle = 'Delete Temporary Employee';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<div class="page-header mb-4">
    <nav aria-label="breadcrumb" class="mb-1">
        <ol class="breadcrumb" style="font-size:0.8rem;">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/temp_employees/index.php">Temporary Employees</a></li>
            <li class="breadcrumb-item active">Delete</li>
        </ol>
    </nav>
    <h1 class="page-title mb-0 text-danger">Delete Temporary Employee</h1>
</div>

<div class="card border-0 shadow-sm" style="max-width:560px;">
    <div class="card-body">
        <div class="alert alert-danger">
            <strong>This action is permanent and cannot be undone.</strong>
            All record of <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['employee_number']) ?>) —
            contract dates, rate, notes, and portal access — will be permanently erased.
        </div>

        <p class="mb-1"><strong>Name:</strong> <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></p>
        <p class="mb-1"><strong>Employee Number:</strong> <code><?= e($emp['employee_number']) ?></code></p>
        <p class="mb-3"><strong>Status:</strong> <?= ucfirst($emp['status']) ?></p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="mb-3">
                <label class="form-label form-label-sm fw-semibold">
                    Type the employee number (<code><?= e($emp['employee_number']) ?></code>) to confirm deletion
                </label>
                <input type="text" name="confirm_number" class="form-control form-control-sm" autocomplete="off" required>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger btn-sm">Permanently Delete</button>
                <a href="<?= APP_URL ?>/modules/temp_employees/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
