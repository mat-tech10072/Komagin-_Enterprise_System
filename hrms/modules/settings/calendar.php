<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('settings.manage', 'view');

$pageTitle  = 'Working Calendar';
$activeMenu = 'settings';

$weekdayLabels = [1=>'Monday', 2=>'Tuesday', 3=>'Wednesday', 4=>'Thursday', 5=>'Friday', 6=>'Saturday', 7=>'Sunday'];

// ── POST handlers ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    requirePermission('settings.manage', 'edit');
    $act = $_POST['action'] ?? '';

    if ($act === 'save_working_days') {
        $selected = array_filter(array_map('intval', (array)($_POST['working_weekdays'] ?? [])));
        if (empty($selected)) {
            setFlash('error', 'At least one working weekday must be selected.');
        } else {
            sort($selected);
            $csv = implode(',', $selected);
            db()->prepare("UPDATE work_calendar_settings SET working_weekdays=?, updated_by=? WHERE id=1")
                ->execute([$csv, $_SESSION['user_id']]);
            auditLog('work_calendar', 'update_working_days', 1, null, $csv);
            setFlash('success', 'Working days updated.');
        }
        header('Location: ' . APP_URL . '/modules/settings/calendar.php'); exit;
    }

    if ($act === 'save_holiday') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $start    = $_POST['start_date'] ?? '';
        $end      = $_POST['end_date'] ?? $start;
        $recurring= isset($_POST['is_recurring_annual']) ? 1 : 0;
        $notes    = trim($_POST['notes'] ?? '');

        if (!$name || !$start) {
            setFlash('error', 'Name and start date are required.');
            header('Location: ' . APP_URL . '/modules/settings/calendar.php'); exit;
        }
        if (strtotime($end) < strtotime($start)) { $end = $start; }

        // Duplicate/overlap guard: same non-recurring date range already
        // active, or same recurring name already covering this month/day.
        $dupCheck = db()->prepare("SELECT id FROM work_calendar_holidays
            WHERE is_active=1 AND is_recurring_annual=? AND start_date=? AND end_date=? AND id != ?");
        $dupCheck->execute([$recurring, $start, $end, $id]);
        if ($dupCheck->fetch()) {
            setFlash('error', 'An active holiday with the identical date range already exists.');
            header('Location: ' . APP_URL . '/modules/settings/calendar.php'); exit;
        }

        if ($id) {
            db()->prepare("UPDATE work_calendar_holidays SET name=?, start_date=?, end_date=?, is_recurring_annual=?, notes=? WHERE id=?")
                ->execute([$name, $start, $end, $recurring, $notes ?: null, $id]);
            auditLog('work_calendar', 'update_holiday', $id, null, json_encode(['name'=>$name,'start'=>$start,'end'=>$end]));
            setFlash('success', 'Holiday updated.');
        } else {
            db()->prepare("INSERT INTO work_calendar_holidays (name, start_date, end_date, is_recurring_annual, notes, created_by) VALUES (?,?,?,?,?,?)")
                ->execute([$name, $start, $end, $recurring, $notes ?: null, $_SESSION['user_id']]);
            $newId = (int)db()->lastInsertId();
            auditLog('work_calendar', 'create_holiday', $newId, null, json_encode(['name'=>$name,'start'=>$start,'end'=>$end]));
            setFlash('success', "Holiday \"$name\" added.");
        }
        header('Location: ' . APP_URL . '/modules/settings/calendar.php'); exit;
    }

    if ($act === 'toggle_holiday') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE work_calendar_holidays SET is_active = NOT is_active WHERE id=?")->execute([$id]);
        auditLog('work_calendar', 'toggle_holiday', $id);
        header('Location: ' . APP_URL . '/modules/settings/calendar.php'); exit;
    }

    if ($act === 'delete_holiday') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM work_calendar_holidays WHERE id=?")->execute([$id]);
        auditLog('work_calendar', 'delete_holiday', $id);
        setFlash('success', 'Holiday removed.');
        header('Location: ' . APP_URL . '/modules/settings/calendar.php'); exit;
    }
}

$settings = getWorkCalendarSettings();
$activeWeekdays = array_map('intval', explode(',', $settings['working_weekdays']));

$holidays = db()->query("SELECT h.*, u.username as created_by_name FROM work_calendar_holidays h
    LEFT JOIN users u ON h.created_by = u.id
    ORDER BY h.is_active DESC, h.start_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$csrf  = generateCsrfToken();
$flash = getFlash();
?>
<?php require_once dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Working Calendar</h1>
        <p class="page-subtitle">Working weekdays and public holidays used for attendance-rate and reporting calculations</p>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
    <?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Working Weekdays -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Working Weekdays</div>
            <div class="card-body">
                <form method="POST" class="d-flex flex-wrap gap-3 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save_working_days">
                    <?php foreach ($weekdayLabels as $num => $label): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="working_weekdays[]" value="<?= $num ?>"
                               id="wd_<?= $num ?>" <?= in_array($num, $activeWeekdays, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="wd_<?= $num ?>"><?= $label ?></label>
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Holiday -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Add Holiday / Closure Day</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save_holiday">
                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Independence Day">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" placeholder="Same as start if blank">
                        </div>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_recurring_annual" id="recurring" value="1">
                        <label class="form-check-label" for="recurring" style="font-size:0.82rem;">
                            Repeats every year (same month/day)
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Add Holiday</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Holidays List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Holidays &amp; Closure Days</div>
            <div class="card-body p-0">
                <?php if (empty($holidays)): ?>
                <div class="text-center py-4 text-muted">No holidays configured yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:0.82rem;">
                        <thead class="table-light">
                            <tr><th class="ps-3">Name</th><th>Dates</th><th>Recurring</th><th>Status</th><th class="text-end pe-3">Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($holidays as $h): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold"><?= e($h['name']) ?></div>
                                <?php if ($h['notes']): ?><div class="text-muted" style="font-size:0.72rem;"><?= e($h['notes']) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <?= $h['start_date'] === $h['end_date']
                                    ? date('d M Y', strtotime($h['start_date']))
                                    : date('d M', strtotime($h['start_date'])) . ' – ' . date('d M Y', strtotime($h['end_date'])) ?>
                            </td>
                            <td><?= $h['is_recurring_annual'] ? '<span class="badge bg-info bg-opacity-15 text-info">Yearly</span>' : '<span class="text-muted">One-time</span>' ?></td>
                            <td>
                                <?= $h['is_active']
                                    ? '<span class="badge bg-success bg-opacity-15 text-success border border-success">Active</span>'
                                    : '<span class="badge bg-light text-muted border">Inactive</span>' ?>
                            </td>
                            <td class="text-end pe-3">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="toggle_holiday">
                                    <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" style="padding:2px 8px;font-size:0.7rem;">
                                        <?= $h['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this holiday entry permanently?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete_holiday">
                                    <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" style="padding:2px 8px;font-size:0.7rem;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
