<?php
// Simplified employee portal for temporary/contract employees.
//
// This page previously ran its own inline session_start() with no ID
// rotation and no idle timeout — a temp employee's session, once created,
// stayed valid for the full 8-hour absolute cookie lifetime regardless of
// inactivity. It now shares the exact same session guard, cookie
// configuration, rotation schedule, and idle-timeout logic as the rest of
// the employee portal via _session.php (same 'ep_' key prefix, same
// session store — permanent and temp employees already log in through the
// same employee-portal/login.php and end up in the same session).
require_once dirname(__FILE__) . '/_config.php';
require_once dirname(__FILE__) . '/_session.php';

epRequireTempLogin();

$tempId = (int)$_SESSION['ep_temp_employee_id'];

$stmt = db()->prepare("
    SELECT te.*,
           tp.name AS project_name, tp.code AS project_code, tp.client AS project_client,
           tp.location AS project_location, tp.start_date AS project_start, tp.end_date AS project_end,
           ts.name AS site_name, ts.location AS site_location
    FROM temp_employees te
    LEFT JOIN temp_projects tp ON tp.id = te.project_id
    LEFT JOIN temp_sites    ts ON ts.id = te.site_id
    WHERE te.id = ? AND te.portal_active = 1
    LIMIT 1
");
$stmt->execute([$tempId]);
$emp = $stmt->fetch();

if (!$emp) {
    destroySessionCompletely();
    header('Location: ' . APP_URL . '/employee-portal/login.php?reason=invalid');
    exit;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    destroySessionCompletely();
    header('Location: ' . APP_URL . '/employee-portal/login.php?reason=logout');
    exit;
}

$companySettings = getCompanySettings();
$logoPath = $companySettings['company_logo'] ?? null;
$logoSrc  = !empty($logoPath) ? APP_URL . '/' . $logoPath : null;
$companyName = $companySettings['company_name'] ?? 'Komagin HR';

$statusColor = match($emp['status']) {
    'active'     => '#10B981',
    'completed'  => '#6B7280',
    'terminated' => '#EF4444',
    default      => '#6B7280'
};

$contractDays = null;
if ($emp['start_date'] && $emp['end_date']) {
    $contractDays = (int)round((strtotime($emp['end_date']) - strtotime($emp['start_date'])) / 86400);
}

$today = date('Y-m-d');
$daysRemaining = null;
if ($emp['end_date'] && $emp['status'] === 'active') {
    $daysRemaining = (int)round((strtotime($emp['end_date']) - strtotime($today)) / 86400);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal — <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F1F5F9; min-height: 100vh; margin: 0; color: #0F172A; }
        .tp-header {
            background: linear-gradient(135deg, #023852 0%, #012a3d 100%);
            color: #fff;
            padding: 0 24px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .tp-header-brand { display: flex; align-items: center; gap: 12px; }
        .tp-header-logo { height: 32px; width: auto; }
        .tp-header-name { font-size: 0.9rem; font-weight: 600; }
        .tp-header-actions { display: flex; align-items: center; gap: 16px; }
        .tp-header-user { font-size: 0.8rem; opacity: 0.85; }
        .tp-header-logout { font-size: 0.8rem; color: rgba(255,255,255,0.7); text-decoration: none; }
        .tp-header-logout:hover { color: #fff; }

        .tp-hero {
            background: linear-gradient(135deg, #023852 0%, #01374a 100%);
            color: #fff;
            padding: 32px 24px 48px;
        }
        .tp-avatar {
            width: 56px; height: 56px; border-radius: 50%;
            background: rgba(255,255,255,0.18);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: 700;
            border: 2px solid rgba(255,255,255,0.3);
            flex-shrink: 0;
        }
        .tp-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 3px 10px; border-radius: 20px;
            font-size: 0.72rem; font-weight: 600;
            background: rgba(255,255,255,0.15); color: #fff;
        }
        .tp-main { max-width: 900px; margin: -20px auto 40px; padding: 0 16px; }
        .tp-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            overflow: hidden;
            margin-bottom: 16px;
        }
        .tp-card-header {
            padding: 14px 20px;
            border-bottom: 1px solid #F1F5F9;
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .tp-card-body { padding: 20px; }
        .tp-field-label { font-size: 0.72rem; color: #6B7280; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.04em; }
        .tp-field-value { font-size: 0.88rem; font-weight: 500; color: #111827; }
        .tp-kpi {
            text-align: center; padding: 20px 16px;
        }
        .tp-kpi-value { font-size: 1.6rem; font-weight: 700; color: #023852; line-height: 1; }
        .tp-kpi-label { font-size: 0.72rem; color: #6B7280; margin-top: 4px; }
        .tp-footer { text-align: center; padding: 24px; font-size: 0.72rem; color: #94A3B8; }
    </style>
</head>
<body>

<!-- Top bar -->
<div class="tp-header">
    <div class="tp-header-brand">
        <?php if ($logoSrc): ?>
        <img src="<?= e($logoSrc) ?>" alt="<?= e($companyName) ?>" class="tp-header-logo">
        <?php endif; ?>
        <span class="tp-header-name"><?= e($companyName) ?> — Employee Portal</span>
    </div>
    <div class="tp-header-actions">
        <span class="tp-header-user"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></span>
        <a href="?action=logout" class="tp-header-logout"
           onclick="return confirm('Sign out of the portal?')">Sign Out</a>
    </div>
</div>

<!-- Hero -->
<div class="tp-hero">
    <div style="max-width:900px;margin:0 auto;padding:0 16px;">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="tp-avatar">
                <?= strtoupper(substr($emp['first_name'],0,1) . substr($emp['last_name'],0,1)) ?>
            </div>
            <div>
                <h1 style="font-size:1.4rem;font-weight:700;margin:0 0 4px;">
                    <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?>
                </h1>
                <div style="font-size:0.84rem;opacity:0.8;"><?= e($emp['position_title'] ?? 'Contract Employee') ?></div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <span class="tp-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3H8a2 2 0 0 0-2 2v2h12V5a2 2 0 0 0-2-2z"/></svg>
                <?= e($emp['employee_number']) ?>
            </span>
            <span class="tp-badge" style="background:rgba(<?= $emp['status']==='active'?'16,185,129':'107,114,128' ?>,0.3);">
                <?= ucfirst($emp['status']) ?>
            </span>
            <?php if ($emp['project_code']): ?>
            <span class="tp-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                <?= e($emp['project_code']) ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="tp-main">

    <!-- KPI row -->
    <div class="tp-card">
        <div class="row g-0 divide-x">
            <div class="col-4 border-end">
                <div class="tp-kpi">
                    <div class="tp-kpi-value"><?= $contractDays !== null ? $contractDays : '—' ?></div>
                    <div class="tp-kpi-label">Contract Days</div>
                </div>
            </div>
            <div class="col-4 border-end">
                <div class="tp-kpi">
                    <?php if ($daysRemaining !== null): ?>
                    <div class="tp-kpi-value" style="color:<?= $daysRemaining < 14 ? '#EF4444' : '#023852' ?>">
                        <?= max(0, $daysRemaining) ?>
                    </div>
                    <div class="tp-kpi-label">Days Remaining</div>
                    <?php else: ?>
                    <div class="tp-kpi-value">—</div>
                    <div class="tp-kpi-label">Days Remaining</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-4">
                <div class="tp-kpi">
                    <div class="tp-kpi-value"><?= $emp['daily_rate'] !== null ? 'K ' . number_format((float)$emp['daily_rate'], 2) : '—' ?></div>
                    <div class="tp-kpi-label">Daily Rate</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Left: personal + contract -->
        <div class="col-md-7">
            <!-- Contact Details -->
            <div class="tp-card">
                <div class="tp-card-header">Personal Details</div>
                <div class="tp-card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="tp-field-label">Full Name</div>
                            <div class="tp-field-value"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="tp-field-label">Employee Number</div>
                            <div class="tp-field-value"><code><?= e($emp['employee_number']) ?></code></div>
                        </div>
                        <div class="col-6">
                            <div class="tp-field-label">Phone</div>
                            <div class="tp-field-value"><?= e($emp['phone'] ?? '—') ?></div>
                        </div>
                        <div class="col-6">
                            <div class="tp-field-label">Email</div>
                            <div class="tp-field-value" style="word-break:break-all;"><?= e($emp['email'] ?? '—') ?></div>
                        </div>
                        <div class="col-12">
                            <div class="tp-field-label">Position / Role</div>
                            <div class="tp-field-value"><?= e($emp['position_title'] ?? '—') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contract -->
            <div class="tp-card">
                <div class="tp-card-header">Contract Period</div>
                <div class="tp-card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="tp-field-label">Start Date</div>
                            <div class="tp-field-value"><?= $emp['start_date'] ? date('d M Y', strtotime($emp['start_date'])) : '—' ?></div>
                        </div>
                        <div class="col-6">
                            <div class="tp-field-label">End Date</div>
                            <div class="tp-field-value"><?= $emp['end_date'] ? date('d M Y', strtotime($emp['end_date'])) : '—' ?></div>
                        </div>
                        <div class="col-6">
                            <div class="tp-field-label">Status</div>
                            <div class="tp-field-value">
                                <span style="color:<?= $statusColor ?>;font-weight:600;"><?= ucfirst($emp['status']) ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="tp-field-label">Duration</div>
                            <div class="tp-field-value"><?= $contractDays !== null ? "$contractDays days" : '—' ?></div>
                        </div>
                    </div>
                    <?php if ($daysRemaining !== null && $daysRemaining < 30 && $daysRemaining >= 0): ?>
                    <div class="mt-3 p-3 rounded" style="background:<?= $daysRemaining < 7 ? '#FEF2F2' : '#FFFBEB' ?>;font-size:0.8rem;color:<?= $daysRemaining < 7 ? '#991B1B' : '#92400E' ?>">
                        <?php if ($daysRemaining === 0): ?>
                        Your contract ends today.
                        <?php elseif ($daysRemaining === 1): ?>
                        Your contract ends tomorrow.
                        <?php else: ?>
                        Your contract ends in <?= $daysRemaining ?> days (<?= date('d M Y', strtotime($emp['end_date'])) ?>).
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: project + site -->
        <div class="col-md-5">
            <!-- Project -->
            <div class="tp-card">
                <div class="tp-card-header">Project</div>
                <div class="tp-card-body">
                    <?php if ($emp['project_name']): ?>
                    <div class="mb-3">
                        <span style="display:inline-block;padding:2px 8px;border-radius:4px;background:#EFF6FF;color:#1D4ED8;font-size:0.72rem;font-weight:600;margin-bottom:6px;">
                            <?= e($emp['project_code']) ?>
                        </span>
                        <div class="tp-field-value" style="font-size:0.95rem;"><?= e($emp['project_name']) ?></div>
                    </div>
                    <?php if ($emp['project_client']): ?>
                    <div class="mb-2">
                        <div class="tp-field-label">Client</div>
                        <div class="tp-field-value"><?= e($emp['project_client']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($emp['project_location']): ?>
                    <div class="mb-2">
                        <div class="tp-field-label">Location</div>
                        <div class="tp-field-value"><?= e($emp['project_location']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($emp['project_start'] || $emp['project_end']): ?>
                    <div>
                        <div class="tp-field-label">Project Period</div>
                        <div class="tp-field-value" style="font-size:0.82rem;">
                            <?= $emp['project_start'] ? date('d M Y', strtotime($emp['project_start'])) : '' ?>
                            <?= ($emp['project_start'] && $emp['project_end']) ? ' — ' : '' ?>
                            <?= $emp['project_end'] ? date('d M Y', strtotime($emp['project_end'])) : 'Ongoing' ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="tp-field-value" style="color:#6B7280;">No project assigned.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Site -->
            <div class="tp-card">
                <div class="tp-card-header">Site</div>
                <div class="tp-card-body">
                    <?php if ($emp['site_name']): ?>
                    <div class="mb-2">
                        <div class="tp-field-label">Site Name</div>
                        <div class="tp-field-value"><?= e($emp['site_name']) ?></div>
                    </div>
                    <?php if ($emp['site_location']): ?>
                    <div>
                        <div class="tp-field-label">Location</div>
                        <div class="tp-field-value"><?= e($emp['site_location']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="tp-field-value" style="color:#6B7280;">No site assigned.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tp-footer">
    © <?= date('Y') ?> <?= e($companyName) ?> — Temporary Employee Portal &nbsp;·&nbsp;
    <a href="?action=logout" style="color:#94A3B8;" onclick="return confirm('Sign out?')">Sign Out</a>
</div>

</body>
</html>
