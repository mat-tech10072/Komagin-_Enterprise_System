<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('temp_employees.create', 'create');

$activeMenu = 'temp_employees';

$projects = db()->query("SELECT id, name, code FROM temp_projects WHERE status != 'completed' ORDER BY name")->fetchAll();
$allSites  = db()->query("SELECT id, project_id, name FROM temp_sites WHERE status = 'active' ORDER BY name")->fetchAll();

$errors = [];
$form   = [];

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
            'daily_rate'     => $_POST['daily_rate']  !== '' ? (float)$_POST['daily_rate'] : null,
            'rate_type'      => $_POST['rate_type'] === 'hourly' ? 'hourly' : 'daily',
            'status'         => $_POST['status']      ?? 'active',
            'notes'          => trim($_POST['notes']  ?? ''),
            'portal_active'      => (int)isset($_POST['portal_active']),
            'attendance_method'  => in_array($_POST['attendance_method'] ?? '', ['kiosk','timesheet','both'])
                                    ? $_POST['attendance_method']
                                    : 'kiosk',
        ];

        // Validation
        if ($form['first_name'] === '') $errors[] = 'First name is required.';
        if ($form['last_name']  === '') $errors[] = 'Last name is required.';
        if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address is not valid.';
        }
        if (!in_array($form['status'], ['active','completed','terminated'])) $errors[] = 'Invalid status.';

        // Auto-generate employee number: KOM-TMP-YYYY-NNNN
        $year  = date('Y');
        $last  = db()->query("SELECT employee_number FROM temp_employees ORDER BY id DESC LIMIT 1")->fetchColumn();
        $seq   = 1;
        if ($last) {
            preg_match('/(\d{4})$/', $last, $m);
            $seq = (int)($m[1] ?? 0) + 1;
        }
        $empNum = sprintf('KOM-TMP-%s-%04d', $year, $seq);

        // Portal password
        $portalPwHash = null;
        if ($form['portal_active'] && !empty($_POST['portal_password'])) {
            $portalPwHash = password_hash($_POST['portal_password'], PASSWORD_DEFAULT);
        } elseif ($form['portal_active'] && empty($_POST['portal_password'])) {
            $errors[] = 'A portal password is required when enabling portal access.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare("
                INSERT INTO temp_employees
                    (employee_number, first_name, last_name, phone, email, position_title,
                     project_id, site_id, start_date, end_date, status, daily_rate, rate_type,
                     notes, portal_active, attendance_method, portal_password)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $empNum,
                $form['first_name'],
                $form['last_name'],
                $form['phone']      ?: null,
                $form['email']      ?: null,
                $form['position_title'] ?: null,
                $form['project_id'],
                $form['site_id'],
                $form['start_date'] ?: null,
                $form['end_date']   ?: null,
                $form['status'],
                $form['daily_rate'],
                $form['rate_type'],
                $form['notes']      ?: null,
                $form['portal_active'],
                $form['attendance_method'],
                $portalPwHash,
            ]);
            $newId = db()->lastInsertId();
            auditLog('temp_employees', 'create', $newId, null, null, "Created temp employee $empNum");
            setFlash('success', "Temporary employee {$form['first_name']} {$form['last_name']} ($empNum) added successfully.");
            header('Location: ' . APP_URL . '/modules/temp_employees/index.php');
            exit;
        }
    }
}

$csrf = generateCsrfToken();
$pageTitle = 'Add Temporary Employee';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<div class="page-header mb-4">
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb" style="font-size:0.8rem;">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/temp_employees/index.php">Temporary Employees</a></li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </nav>
        <h1 class="page-title mb-0">Add Temporary Employee</h1>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
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
                                   value="<?= e($form['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control form-control-sm"
                                   value="<?= e($form['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control form-control-sm"
                                   value="<?= e($form['phone'] ?? '') ?>" placeholder="+675 7xxx xxxx">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm"
                                   value="<?= e($form['email'] ?? '') ?>" placeholder="name@example.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Position / Role Title</label>
                            <input type="text" name="position_title" class="form-control form-control-sm"
                                   value="<?= e($form['position_title'] ?? '') ?>" placeholder="e.g. Site Labourer">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="active"     <?= ($form['status'] ?? 'active') === 'active'     ? 'selected' : '' ?>>Active</option>
                                <option value="completed"  <?= ($form['status'] ?? '') === 'completed'  ? 'selected' : '' ?>>Completed</option>
                                <option value="terminated" <?= ($form['status'] ?? '') === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold" id="addRateLabel">
                                <?= ($form['rate_type'] ?? 'daily') === 'hourly' ? 'Hourly' : 'Daily' ?> Rate (K)
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="daily_rate" class="form-control form-control-sm"
                                       value="<?= e($form['daily_rate'] ?? '') ?>" min="0" step="0.01" placeholder="0.00">
                                <span class="input-group-text" id="addRateUnit" style="font-size:0.72rem;">
                                    /<?= ($form['rate_type'] ?? 'daily') === 'hourly' ? 'hr' : 'day' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex flex-column">
                            <label class="form-label form-label-sm fw-semibold">Rate Type</label>
                            <div class="btn-group btn-group-sm mt-auto" role="group" aria-label="Rate type">
                                <input type="radio" class="btn-check" name="rate_type" id="add_rt_daily" value="daily"
                                       autocomplete="off" <?= ($form['rate_type'] ?? 'daily') === 'daily' ? 'checked' : '' ?>
                                       onchange="document.getElementById('addRateLabel').textContent='Daily Rate (K)';document.getElementById('addRateUnit').textContent='/day';">
                                <label class="btn btn-outline-secondary" for="add_rt_daily">Daily</label>
                                <input type="radio" class="btn-check" name="rate_type" id="add_rt_hourly" value="hourly"
                                       autocomplete="off" <?= ($form['rate_type'] ?? 'daily') === 'hourly' ? 'checked' : '' ?>
                                       onchange="document.getElementById('addRateLabel').textContent='Hourly Rate (K)';document.getElementById('addRateUnit').textContent='/hr';">
                                <label class="btn btn-outline-secondary" for="add_rt_hourly">Hourly</label>
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
                            <select name="project_id" class="form-select form-select-sm" id="addProjectSel">
                                <option value="">— No project —</option>
                                <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($form['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                    <?= e($p['code']) ?> — <?= e($p['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Site</label>
                            <select name="site_id" class="form-select form-select-sm" id="addSiteSel">
                                <option value="">— No site —</option>
                                <?php foreach ($allSites as $s): ?>
                                <option value="<?= $s['id'] ?>" data-project="<?= $s['project_id'] ?>"
                                    <?= ($form['site_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
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
                               value="1" <?= !empty($form['portal_active']) ? 'checked' : '' ?>
                               onchange="document.getElementById('portalPwBlock').style.display=this.checked?'block':'none'">
                        <label class="form-check-label fw-semibold" for="portalToggle" style="font-size:0.85rem;">
                            Enable Employee Portal Access
                        </label>
                        <div class="text-muted small mt-1">Employee can log in to the portal using their employee number and the password below.</div>
                    </div>
                    <div id="portalPwBlock" style="display:<?= !empty($form['portal_active']) ? 'block' : 'none' ?>;">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Portal Password</label>
                            <input type="password" name="portal_password" class="form-control form-control-sm"
                                   placeholder="Set initial portal password" autocomplete="new-password">
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
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Notes</div>
                <div class="card-body">
                    <textarea name="notes" class="form-control form-control-sm" rows="3"
                              placeholder="Any additional notes about this temporary employee…"><?= e($form['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Save Temporary Employee</button>
            <a href="<?= APP_URL ?>/modules/temp_employees/index.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
        </div>
    </form>

<script>
document.getElementById('addProjectSel').addEventListener('change', function() {
    const pid = this.value;
    const sel = document.getElementById('addSiteSel');
    Array.from(sel.options).forEach(opt => {
        if (!opt.value) return;
        opt.hidden = pid && opt.dataset.project !== pid;
    });
    if (pid && sel.value && sel.options[sel.selectedIndex].dataset.project !== pid) sel.value = '';
});
document.getElementById('addProjectSel').dispatchEvent(new Event('change'));

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
