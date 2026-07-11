<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('temp_employees.view');

$activeMenu = 'temp_employees';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/temp_employees/index.php'); exit; }

$stmt = db()->prepare("
    SELECT te.*,
           tp.name AS project_name, tp.code AS project_code, tp.client AS project_client,
           tp.location AS project_location, tp.status AS project_status,
           tp.start_date AS project_start, tp.end_date AS project_end,
           ts.name AS site_name, ts.location AS site_location
    FROM temp_employees te
    LEFT JOIN temp_projects tp ON tp.id = te.project_id
    LEFT JOIN temp_sites    ts ON ts.id = te.site_id
    WHERE te.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) { setFlash('error', 'Temporary employee not found.'); header('Location: ' . APP_URL . '/modules/temp_employees/index.php'); exit; }

$flash = getFlash();

$statusColor = match($emp['status']) {
    'active'     => 'success',
    'completed'  => 'secondary',
    'terminated' => 'danger',
    default      => 'secondary'
};

// Contract duration
$contractDays = null;
if ($emp['start_date'] && $emp['end_date']) {
    $contractDays = (int)round((strtotime($emp['end_date']) - strtotime($emp['start_date'])) / 86400);
}

$pageTitle = e($emp['first_name'] . ' ' . $emp['last_name']) . ' — Temp Employee';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<!-- Breadcrumb + actions -->
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <nav aria-label="breadcrumb" class="mb-1">
                <ol class="breadcrumb" style="font-size:0.8rem;">
                    <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/temp_employees/index.php">Temporary Employees</a></li>
                    <li class="breadcrumb-item active"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></li>
                </ol>
            </nav>
            <h1 class="page-title mb-0"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></h1>
            <div class="d-flex align-items-center gap-2 mt-1">
                <code style="font-size:0.8rem;"><?= e($emp['employee_number']) ?></code>
                <span class="badge bg-<?= $statusColor ?> bg-opacity-15 text-<?= $statusColor ?> border border-<?= $statusColor ?>" style="font-size:0.72rem;">
                    <?= ucfirst($emp['status']) ?>
                </span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (canEdit('temp_employees.edit')): ?>
            <a href="<?= APP_URL ?>/modules/temp_employees/edit.php?id=<?= $id ?>" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
            </a>
            <?php endif; ?>
            <?php if (canDelete('temp_employees.delete')): ?>
            <form method="POST" action="<?= APP_URL ?>/modules/temp_employees/delete.php"
                  onsubmit="return confirm('Permanently delete this temporary employee? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/modules/temp_employees/index.php" class="btn btn-outline-secondary btn-sm">← Back</a>
        </div>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
        <?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left col: main details -->
        <div class="col-lg-8">

            <!-- Personal Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Personal Details</div>
                <div class="card-body">
                    <div class="row g-3" style="font-size:0.84rem;">
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Full Name</div>
                            <div class="fw-semibold"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Employee Number</div>
                            <code><?= e($emp['employee_number']) ?></code>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Position / Role</div>
                            <div><?= e($emp['position_title'] ?? '—') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <?php $rateType = $emp['rate_type'] ?? 'daily'; ?>
                            <div class="text-muted small mb-1"><?= $rateType === 'hourly' ? 'Hourly' : 'Daily' ?> Rate</div>
                            <div>
                                <?= $emp['daily_rate'] !== null ? 'K ' . number_format((float)$emp['daily_rate'], 2) : '—' ?>
                                <?php if ($emp['daily_rate'] !== null): ?>
                                <span class="badge bg-<?= $rateType === 'hourly' ? 'warning' : 'primary' ?> bg-opacity-15 text-<?= $rateType === 'hourly' ? 'warning' : 'primary' ?> ms-1" style="font-size:0.65rem;">
                                    /<?= $rateType === 'hourly' ? 'hr' : 'day' ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Phone</div>
                            <div><?= e($emp['phone'] ?? '—') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Email</div>
                            <div><?= $emp['email'] ? '<a href="mailto:' . e($emp['email']) . '">' . e($emp['email']) . '</a>' : '—' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contract Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Contract Period</div>
                <div class="card-body">
                    <div class="row g-3" style="font-size:0.84rem;">
                        <div class="col-sm-4">
                            <div class="text-muted small mb-1">Start Date</div>
                            <div><?= $emp['start_date'] ? date('d M Y', strtotime($emp['start_date'])) : '—' ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small mb-1">End Date</div>
                            <div><?= $emp['end_date'] ? date('d M Y', strtotime($emp['end_date'])) : '—' ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small mb-1">Duration</div>
                            <div><?= $contractDays !== null ? $contractDays . ' days' : '—' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <?php if ($emp['notes']): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Notes</div>
                <div class="card-body" style="font-size:0.84rem;white-space:pre-wrap;"><?= e($emp['notes']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right col: project, site, portal -->
        <div class="col-lg-4">

            <!-- Project Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Project</div>
                <div class="card-body" style="font-size:0.84rem;">
                    <?php if ($emp['project_name']): ?>
                    <div class="mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary mb-1"><?= e($emp['project_code']) ?></span>
                        <div class="fw-semibold"><?= e($emp['project_name']) ?></div>
                    </div>
                    <div class="text-muted small mb-1">Client</div>
                    <div class="mb-2"><?= e($emp['project_client'] ?? '—') ?></div>
                    <div class="text-muted small mb-1">Location</div>
                    <div class="mb-2"><?= e($emp['project_location'] ?? '—') ?></div>
                    <div class="text-muted small mb-1">Project Period</div>
                    <div><?= $emp['project_start'] ? date('d M Y', strtotime($emp['project_start'])) : '—' ?>
                         — <?= $emp['project_end'] ? date('d M Y', strtotime($emp['project_end'])) : 'Ongoing' ?></div>
                    <div class="mt-2">
                        <?php
                        $pBadge = match($emp['project_status'] ?? '') {
                            'active'    => 'success',
                            'on_hold'   => 'warning',
                            'completed' => 'secondary',
                            default     => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?= $pBadge ?> bg-opacity-15 text-<?= $pBadge ?> border border-<?= $pBadge ?>" style="font-size:0.72rem;">
                            <?= ucfirst(str_replace('_', ' ', $emp['project_status'] ?? '')) ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="text-muted">No project assigned.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Site Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Site</div>
                <div class="card-body" style="font-size:0.84rem;">
                    <?php if ($emp['site_name']): ?>
                    <div class="fw-semibold mb-1"><?= e($emp['site_name']) ?></div>
                    <div class="text-muted"><?= e($emp['site_location'] ?? '') ?></div>
                    <?php else: ?>
                    <div class="text-muted">No site assigned.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Portal Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Employee Portal</div>
                <div class="card-body" style="font-size:0.84rem;">
                    <?php if ($emp['portal_active']): ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-success bg-opacity-15 text-success border border-success">Enabled</span>
                    </div>
                    <div class="text-muted small mb-1">Last Login</div>
                    <div class="mb-3"><?= $emp['portal_last_login'] ? date('d M Y H:i', strtotime($emp['portal_last_login'])) : 'Never' ?></div>
                    <div class="text-muted small mb-1">Login with</div>
                    <div><code><?= e($emp['employee_number']) ?></code></div>
                    <?php else: ?>
                    <div class="text-muted mb-3">Portal access is disabled.</div>
                    <?php if (canEdit('temp_employees.edit')): ?>
                    <a href="<?= APP_URL ?>/modules/temp_employees/edit.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
                        Enable Portal Access
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Attendance Method Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold" style="font-size:0.88rem;">Attendance Method</div>
                <div class="card-body" style="font-size:0.84rem;">
                    <?php
                    $method = $emp['attendance_method'] ?? 'kiosk';
                    $methodLabel = match($method) {
                        'kiosk'     => 'Kiosk Only',
                        'timesheet' => 'Timesheet Only',
                        'both'      => 'Both (Kiosk & Timesheet)',
                        default     => 'Kiosk Only',
                    };
                    $methodBadge = match($method) {
                        'kiosk'     => 'primary',
                        'timesheet' => 'warning',
                        'both'      => 'success',
                        default     => 'secondary',
                    };
                    $methodDesc = match($method) {
                        'kiosk'     => 'This employee clocks in and out using the kiosk tablet.',
                        'timesheet' => 'This employee records hours on a downloadable timesheet.',
                        'both'      => 'This employee may use either the kiosk or a timesheet.',
                        default     => '',
                    };
                    ?>
                    <div class="mb-2">
                        <span class="badge bg-<?= $methodBadge ?> bg-opacity-15 text-<?= $methodBadge ?> border border-<?= $methodBadge ?>" style="font-size:0.78rem;padding:4px 10px;">
                            <?php if ($method === 'kiosk' || $method === 'both'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:3px;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <?php endif; ?>
                            <?php if ($method === 'timesheet' || $method === 'both'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:3px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <?php endif; ?>
                            <?= $methodLabel ?>
                        </span>
                    </div>
                    <div class="text-muted small"><?= $methodDesc ?></div>
                    <?php if (in_array($_SESSION['user_role'] ?? '', ['super_admin','hr_manager']) && canEdit('temp_employees.edit')): ?>
                    <div class="mt-2">
                        <a href="<?= APP_URL ?>/modules/temp_employees/edit.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm" style="font-size:0.72rem;">Change Method</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Meta -->
            <div class="card border-0 shadow-sm">
                <div class="card-body text-muted" style="font-size:0.78rem;">
                    <div>Added: <?= date('d M Y', strtotime($emp['created_at'])) ?></div>
                    <div>Updated: <?= date('d M Y H:i', strtotime($emp['updated_at'])) ?></div>
                </div>
            </div>
        </div>
    </div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
