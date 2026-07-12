<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('temp_employees.edit', 'edit');

$activeMenu = 'temp_employees';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/temp_employees/index.php'); exit; }

$row = db()->prepare("SELECT * FROM temp_employees WHERE id = ? LIMIT 1");
$row->execute([$id]);
$emp = $row->fetch();
if (!$emp) { setFlash('error', 'Temporary employee not found.'); header('Location: ' . APP_URL . '/modules/temp_employees/index.php'); exit; }

$projects = db()->query("SELECT id, name, code FROM temp_projects ORDER BY name")->fetchAll();
$allSites  = db()->query("SELECT id, project_id, name FROM temp_sites ORDER BY name")->fetchAll();

$errors = [];
$form   = $emp; // pre-populate from DB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $form = [
            'first_name'     => trim($_POST['first_name'] ?? ''),
            'last_name'      => trim($_POST['last_name']  ?? ''),
            'phone'          => trim($_POST['phone']      ?? ''),
            'email'          => trim($_POST['email']      ?? ''),
            'position_title' => trim($_POST['position_title'] ?? ''),
            'project_id'     => (int)($_POST['project_id'] ?? 0) ?: null,
            'site_id'        => (int)($_POST['site_id']    ?? 0) ?: null,
            'start_date'     => $_POST['start_date']  ?? '',
            'end_date'       => $_POST['end_date']    ?? '',
            'daily_rate'     => $_POST['daily_rate'] !== '' ? (float)$_POST['daily_rate'] : null,
            'rate_type'      => $_POST['rate_type'] === 'hourly' ? 'hourly' : 'daily',
            'status'         => $_POST['status']      ?? 'active',
            'notes'          => trim($_POST['notes']  ?? ''),
            'portal_active'     => (int)isset($_POST['portal_active']),
            'attendance_method' => in_array($_POST['attendance_method'] ?? '', ['kiosk','timesheet','both'])
                                   ? $_POST['attendance_method']
                                   : ($emp['attendance_method'] ?? 'kiosk'),
        ];

        if ($form['first_name'] === '') $errors[] = 'First name is required.';
        if ($form['last_name']  === '') $errors[] = 'Last name is required.';
        if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address is not valid.';
        }

        // Optional portal password change
        $newPwHash = null;
        if (!empty($_POST['portal_password'])) {
            $newPwHash = password_hash($_POST['portal_password'], PASSWORD_DEFAULT);
        }

        if (empty($errors)) {
            $stmt = db()->prepare("
                UPDATE temp_employees SET
                    first_name        = ?,
                    last_name         = ?,
                    phone             = ?,
                    email             = ?,
                    position_title    = ?,
                    project_id        = ?,
                    site_id           = ?,
                    start_date        = ?,
                    end_date          = ?,
                    status            = ?,
                    daily_rate        = ?,
                    rate_type         = ?,
                    notes             = ?,
                    portal_active     = ?,
                    attendance_method = ?,
                    portal_password   = COALESCE(?, portal_password)
                WHERE id = ?
            ");
            $stmt->execute([
                $form['first_name'],
                $form['last_name'],
                $form['phone']          ?: null,
                $form['email']          ?: null,
                $form['position_title'] ?: null,
                $form['project_id'],
                $form['site_id'],
                $form['start_date']     ?: null,
                $form['end_date']       ?: null,
                $form['status'],
                $form['daily_rate'],
                $form['rate_type'],
                $form['notes']          ?: null,
                $form['portal_active'],
                $form['attendance_method'],
                $newPwHash,
                $id,
            ]);
            auditLog('temp_employees', 'edit', $id, null, null, "Updated temp employee ID $id");
            setFlash('success', "Temporary employee updated successfully.");
            header('Location: ' . APP_URL . '/modules/temp_employees/view.php?id=' . $id);
            exit;
        }
    }
}

$csrf = generateCsrfToken();
$pageTitle = 'Edit Temporary Employee';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<div class="page-header mb-4">
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb" style="font-size:0.8rem;">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/temp_employees/index.php">Temporary Employees</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/temp_employees/view.php?id=<?= $id ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
        <h1 class="page-title mb-0">Edit — <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></h1>
        <div class="text-muted small"><?= e($emp['employee_number']) ?></div>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $er) echo "<li>" . htmlspecialchars($er) . "</li>"; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="row g-4">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <!-- Personal Details -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Personal Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control form-control-sm"
                                   value="<?= e($form['first_name']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control form-control-sm"
                                   value="<?= e($form['last_name']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control form-control-sm"
                                   value="<?= e($form['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm"
                                   value="<?= e($form['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Position / Role Title</label>
                            <input type="text" name="position_title" class="form-control form-control-sm"
                                   value="<?= e($form['position_title'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="active"     <?= $form['status'] === 'active'     ? 'selected' : '' ?>>Active</option>
                                <option value="completed"  <?= $form['status'] === 'completed'  ? 'selected' : '' ?>>Completed</option>
                                <option value="terminated" <?= $form['status'] === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold" id="editRateLabel">
                                <?= ($form['rate_type'] ?? 'daily') === 'hourly' ? 'Hourly' : 'Daily' ?> Rate (K)
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="daily_rate" class="form-control form-control-sm"
                                       value="<?= e($form['daily_rate'] ?? '') ?>" min="0" step="0.01">
                                <span class="input-group-text" id="editRateUnit" style="font-size:0.72rem;">
                                    /<?= ($form['rate_type'] ?? 'daily') === 'hourly' ? 'hr' : 'day' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex flex-column">
                            <label class="form-label form-label-sm fw-semibold">Rate Type</label>
                            <div class="btn-group btn-group-sm mt-auto" role="group" aria-label="Rate type">
                                <input type="radio" class="btn-check" name="rate_type" id="edit_rt_daily" value="daily"
                                       autocomplete="off" <?= ($form['rate_type'] ?? 'daily') === 'daily' ? 'checked' : '' ?>
                                       onchange="document.getElementById('editRateLabel').textContent='Daily Rate (K)';document.getElementById('editRateUnit').textContent='/day';">
                                <label class="btn btn-outline-secondary" for="edit_rt_daily">Daily</label>
                                <input type="radio" class="btn-check" name="rate_type" id="edit_rt_hourly" value="hourly"
                                       autocomplete="off" <?= ($form['rate_type'] ?? 'daily') === 'hourly' ? 'checked' : '' ?>
                                       onchange="document.getElementById('editRateLabel').textContent='Hourly Rate (K)';document.getElementById('editRateUnit').textContent='/hr';">
                                <label class="btn btn-outline-secondary" for="edit_rt_hourly">Hourly</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Project &amp; Site Assignment</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Project</label>
                            <select name="project_id" class="form-select form-select-sm" id="editProjectSel">
                                <option value="">— No project —</option>
                                <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $form['project_id'] == $p['id'] ? 'selected' : '' ?>>
                                    <?= e($p['code']) ?> — <?= e($p['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Site</label>
                            <select name="site_id" class="form-select form-select-sm" id="editSiteSel">
                                <option value="">— No site —</option>
                                <?php foreach ($allSites as $s): ?>
                                <option value="<?= $s['id'] ?>" data-project="<?= $s['project_id'] ?>"
                                    <?= $form['site_id'] == $s['id'] ? 'selected' : '' ?>>
                                    <?= e($s['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm"
                                   value="<?= e($form['start_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm"
                                   value="<?= e($form['end_date'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Portal Access -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Employee Portal Access</div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="portal_active" id="portalToggle"
                               value="1" <?= $form['portal_active'] ? 'checked' : '' ?>
                               onchange="document.getElementById('editPwBlock').style.display=this.checked?'block':'none'">
                        <label class="form-check-label fw-semibold" for="portalToggle" style="font-size:0.85rem;">
                            Enable Employee Portal Access
                        </label>
                    </div>
                    <?php if ($form['portal_last_login']): ?>
                    <p class="text-muted small">Last portal login: <?= date('d M Y H:i', strtotime($form['portal_last_login'])) ?></p>
                    <?php endif; ?>
                    <div id="editPwBlock" style="display:<?= $form['portal_active'] ? 'block' : 'none' ?>;">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">New Portal Password <span class="text-muted fw-normal">(leave blank to keep existing)</span></label>
                            <input type="password" name="portal_password" class="form-control form-control-sm"
                                   placeholder="Enter new password to change" autocomplete="new-password">
                        </div>
                    </div>

                    <?php if (canEdit('temp_employees.edit')): ?>
                    <div class="border-top mt-3 pt-3">
                        <label class="form-label form-label-sm fw-semibold d-block mb-2">
                            Attendance Method
                            <span class="text-muted fw-normal ms-1" style="font-size:0.78rem;">— how this employee records their time</span>
                        </label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label for="am_kiosk" class="am-card d-flex align-items-start gap-2 p-2 rounded border h-100" style="cursor:pointer;">
                                    <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="attendance_method" id="am_kiosk"
                                           value="kiosk" <?= ($form['attendance_method'] ?? 'kiosk') === 'kiosk' ? 'checked' : '' ?>>
                                    <div>
                                        <div class="fw-semibold d-flex align-items-center gap-1" style="font-size:0.84rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            Kiosk Only
                                        </div>
                                        <div class="text-muted" style="font-size:0.75rem;">Not yet supported — kiosk doesn't recognize temp employees</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label for="am_timesheet" class="am-card d-flex align-items-start gap-2 p-2 rounded border h-100" style="cursor:pointer;">
                                    <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="attendance_method" id="am_timesheet"
                                           value="timesheet" <?= ($form['attendance_method'] ?? '') === 'timesheet' ? 'checked' : '' ?>>
                                    <div>
                                        <div class="fw-semibold d-flex align-items-center gap-1" style="font-size:0.84rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                            Timesheet Only
                                        </div>
                                        <div class="text-muted" style="font-size:0.75rem;">Blank paper form only — filled by hand, not entered back into the system</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label for="am_both" class="am-card d-flex align-items-start gap-2 p-2 rounded border h-100" style="cursor:pointer;">
                                    <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="attendance_method" id="am_both"
                                           value="both" <?= ($form['attendance_method'] ?? '') === 'both' ? 'checked' : '' ?>>
                                    <div>
                                        <div class="fw-semibold d-flex align-items-center gap-1" style="font-size:0.84rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                            Both (Kiosk &amp; Timesheet)
                                        </div>
                                        <div class="text-muted" style="font-size:0.75rem;">Neither currently captures data digitally for temp employees</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mt-3 p-2 rounded" style="background:var(--bs-light,#f8f9fa);font-size:0.8rem;">
                        <span class="text-muted">Attendance method: </span>
                        <strong><?= match($form['attendance_method'] ?? 'kiosk') {
                            'kiosk'     => 'Kiosk Only',
                            'timesheet' => 'Timesheet Only',
                            'both'      => 'Both (Kiosk & Timesheet)',
                            default     => 'Kiosk Only'
                        } ?></strong>
                        <span class="text-muted ms-2" style="font-size:0.72rem;">(Only HR Manager or Super Admin can change this)</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Notes</div>
                <div class="card-body">
                    <textarea name="notes" class="form-control form-control-sm" rows="3"><?= e($form['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            <a href="<?= APP_URL ?>/modules/temp_employees/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
        </div>
    </form>

<script>
document.getElementById('editProjectSel').addEventListener('change', function() {
    const pid = this.value;
    const sel = document.getElementById('editSiteSel');
    Array.from(sel.options).forEach(opt => {
        if (!opt.value) return;
        opt.hidden = pid && opt.dataset.project !== pid;
    });
    if (pid && sel.value && sel.options[sel.selectedIndex].dataset.project !== pid) sel.value = '';
});
document.getElementById('editProjectSel').dispatchEvent(new Event('change'));

(function() {
    var cards = document.querySelectorAll('.am-card');
    function refreshCards() {
        cards.forEach(function(card) {
            var inp = card.querySelector('input[type=radio]');
            if (inp && inp.checked) {
                card.style.borderColor = '#0d6efd';
                card.style.background   = 'rgba(13,110,253,0.04)';
            } else {
                card.style.borderColor = '';
                card.style.background  = '';
            }
        });
    }
    cards.forEach(function(card) {
        var inp = card.querySelector('input[type=radio]');
        if (inp) inp.addEventListener('change', refreshCards);
    });
    refreshCards();
})();
</script>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
