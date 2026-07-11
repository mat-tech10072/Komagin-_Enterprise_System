<?php
// This file starts HTML output. All PHP setup must be done before including this.
// Required: $pageTitle (string), $activeMenu (string)
header('Content-Type: text/html; charset=UTF-8');
$pageTitle    = $pageTitle ?? 'Dashboard';
$activeMenu   = $activeMenu ?? '';
$activeParent = $activeParent ?? '';

$user = currentUser();
$unreadNotifs = getUnreadNotificationCount($_SESSION['user_id']);
$settings = getCompanySettings();
$companyName = $settings['company_name'] ?? 'Komagin HR';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> – <?= e($companyName) ?></title>
    <?php if (!empty($settings['company_favicon'])): ?>
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/<?= e($settings['company_favicon']) ?>">
    <?php endif; ?>
    <?php
    // ── Inject DB-driven theme CSS variables ─────────────────────────────
    $themeSettings = json_decode($settings['theme_settings'] ?? '{}', true) ?: [];
    if (!empty($themeSettings)):
        $fontFamily = $themeSettings['font_family'] ?? 'Inter';
    ?>
    <style>
        :root {
            <?php if (!empty($themeSettings['primary'])): ?>
            --primary:        <?= htmlspecialchars($themeSettings['primary']) ?>;
            --primary-dark:   <?= htmlspecialchars($themeSettings['primary_dark'] ?? $themeSettings['primary']) ?>;
            --primary-light:  <?= htmlspecialchars($themeSettings['primary_light'] ?? '#E8F4F8') ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['sidebar_bg'])): ?>
            --secondary:      <?= htmlspecialchars($themeSettings['sidebar_bg']) ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['bg'])): ?>
            --bg:             <?= htmlspecialchars($themeSettings['bg']) ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['bg_card'])): ?>
            --bg-card:        <?= htmlspecialchars($themeSettings['bg_card']) ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['border'])): ?>
            --border:         <?= htmlspecialchars($themeSettings['border']) ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['text'])): ?>
            --text:           <?= htmlspecialchars($themeSettings['text']) ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['text_secondary'])): ?>
            --text-secondary: <?= htmlspecialchars($themeSettings['text_secondary']) ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['success'])): ?>
            --success:        <?= htmlspecialchars($themeSettings['success']) ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['warning'])): ?>
            --warning:        <?= htmlspecialchars($themeSettings['warning']) ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['danger'])): ?>
            --danger:         <?= htmlspecialchars($themeSettings['danger']) ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['info'])): ?>
            --info:           <?= htmlspecialchars($themeSettings['info']) ?>;
            <?php endif; ?>
            <?php if (!empty($themeSettings['sidebar_width'])): ?>
            --sidebar-w:      <?= (int)$themeSettings['sidebar_width'] ?>px;
            <?php endif; ?>
            <?php if (!empty($themeSettings['border_radius'])): ?>
            --radius:         <?= (int)$themeSettings['border_radius'] ?>px;
            <?php endif; ?>
        }
        <?php if ($fontFamily !== 'Inter'): ?>
        body { font-family: '<?= htmlspecialchars($fontFamily) ?>', -apple-system, BlinkMacSystemFont, sans-serif !important; }
        <?php endif; ?>
    </style>
    <?php if ($fontFamily !== 'Inter'): ?>
    <link href="https://fonts.googleapis.com/css2?family=<?= urlencode($fontFamily) ?>:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link href="<?= APP_URL ?>/assets/css/style.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/style.css') ?>" rel="stylesheet">
    <script>window.APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>
<div class="app-wrapper">

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand" style="padding:10px 14px;">
        <?php
        // Always read fresh from DB — no fossil fallback
        $logoPath = $settings['company_logo'] ?? null;
        $logoSrc  = !empty($logoPath) ? APP_URL . '/' . $logoPath : null;
        ?>
        <?php if ($logoSrc): ?>
        <a href="<?= APP_URL ?>/dashboard.php"
           style="display:flex;align-items:center;gap:10px;text-decoration:none;width:100%;overflow:visible;">
            <img src="<?= e($logoSrc) ?>"
                 alt="<?= e($companyName) ?>"
                 style="height:46px;width:auto;display:block;flex-shrink:0;max-width:none;">
            <div class="sidebar-brand-text">
                <div class="sidebar-brand-title"><?= e($companyName) ?></div>
                <div class="sidebar-brand-sub">HR Management System</div>
            </div>
        </a>
        <?php else: ?>
        <a href="<?= APP_URL ?>/dashboard.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;width:100%;">
            <div class="sidebar-brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="sidebar-brand-text">
                <div class="sidebar-brand-title"><?= e($companyName) ?></div>
                <div class="sidebar-brand-sub">HR Management System</div>
            </div>
        </a>
        <?php endif; ?>
    </div>

    <!-- Dashboard -->
    <div class="sidebar-section">
        <a href="<?= APP_URL ?>/dashboard.php" class="sidebar-nav-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a href="<?= APP_URL ?>/modules/approvals/index.php" class="sidebar-nav-item <?= $activeMenu === 'approvals' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Approvals
            <?php
            try {
                $pendingApprovals = db()->prepare("SELECT COUNT(*) FROM approval_workflows aw JOIN approval_stages a_s ON a_s.workflow_id=aw.id AND a_s.stage_number=aw.current_stage WHERE aw.status IN ('pending','in_review') AND (a_s.approver_role=? OR a_s.approver_user_id=?)");
                $pendingApprovals->execute([$_SESSION['user_role']??'', $_SESSION['user_id']??0]);
                $pendingCount = (int)$pendingApprovals->fetchColumn();
                if ($pendingCount > 0) echo '<span style="margin-left:auto;background:var(--danger);color:white;font-size:0.6rem;font-weight:700;padding:1px 6px;border-radius:9px;">'.$pendingCount.'</span>';
            } catch (Exception $e) {}
            ?>
        </a>
    </div>

    <!-- HR Management -->
    <div class="sidebar-section">
        <span class="sidebar-section-label">HR Management</span>
        <?php if (canView('employees.view')): ?>
        <a href="<?= APP_URL ?>/modules/employees/index.php" class="sidebar-nav-item <?= $activeMenu === 'employees' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Employees
        </a>
        <?php endif; ?>
        <?php if (canView('attendance.view')): ?>
        <a href="<?= APP_URL ?>/modules/attendance/index.php" class="sidebar-nav-item <?= $activeMenu === 'attendance' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Attendance
        </a>
        <?php endif; ?>
        <?php if (hasPermission('kiosk.manage')): ?>
        <a href="<?= APP_URL ?>/modules/attendance/kiosk_manage.php" class="sidebar-nav-item <?= $activeMenu === 'kiosk_manage' ? 'active' : '' ?>" style="padding-left:28px;font-size:0.72rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Kiosk Control
        </a>
        <?php endif; ?>
        <?php if (canView('timesheets.view')): ?>
        <a href="<?= APP_URL ?>/modules/timesheets/index.php" class="sidebar-nav-item <?= $activeMenu === 'timesheets' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Timesheets
        </a>
        <?php endif; ?>
        <?php if (canView('leave.view')): ?>
        <a href="<?= APP_URL ?>/modules/leave/index.php" class="sidebar-nav-item <?= $activeMenu === 'leave' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Leave
        </a>
        <?php endif; ?>
        <?php if (canView('recruitment.view')): ?>
        <a href="<?= APP_URL ?>/modules/recruitment/index.php" class="sidebar-nav-item <?= $activeMenu === 'recruitment' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Recruitment
        </a>
        <?php endif; ?>
        <?php if (canView('onboarding.view')): ?>
        <a href="<?= APP_URL ?>/modules/onboarding/index.php" class="sidebar-nav-item <?= $activeMenu === 'onboarding' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Onboarding
        </a>
        <?php endif; ?>
        <?php if (canView('training.view')): ?>
        <a href="<?= APP_URL ?>/modules/training/index.php" class="sidebar-nav-item <?= $activeMenu === 'training' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            Training
        </a>
        <?php endif; ?>
        <?php if (canView('temp_employees.view')): ?>
        <a href="<?= APP_URL ?>/modules/temp_employees/index.php" class="sidebar-nav-item <?= $activeMenu === 'temp_employees' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M5.5 20a6.5 6.5 0 0 1 13 0"/><line x1="19" y1="9" x2="23" y2="9"/><line x1="21" y1="7" x2="21" y2="11"/></svg>
            Temporary Employees
        </a>
        <?php endif; ?>
    </div>

    <!-- Payroll -->
    <?php if (canView('payroll.view')): ?>
    <div class="sidebar-section">
        <span class="sidebar-section-label">Payroll</span>
        <a href="<?= APP_URL ?>/modules/payroll/index.php" class="sidebar-nav-item <?= $activeMenu === 'payroll' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Payroll
        </a>
        <a href="<?= APP_URL ?>/modules/payroll/payslips.php" class="sidebar-nav-item <?= $activeMenu === 'payroll_payslips' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Payslips
        </a>
        <a href="<?= APP_URL ?>/modules/payroll/deductions.php" class="sidebar-nav-item <?= $activeMenu === 'payroll_deductions' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Deductions
        </a>
        <a href="<?= APP_URL ?>/modules/payroll/savings.php" class="sidebar-nav-item <?= $activeMenu === 'payroll_savings' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10H12V2z"/><path d="M21.18 13.52A10 10 0 0 0 12 2v10z"/></svg>
            Savings
        </a>
        <a href="<?= APP_URL ?>/modules/payroll/reports.php" class="sidebar-nav-item <?= $activeMenu === 'payroll_reports' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Payroll Reports
        </a>
    </div>
    <?php endif; ?>

    <!-- Employee Hub -->
    <?php if (canView('hub.view')): ?>
    <div class="sidebar-section">
        <span class="sidebar-section-label">Employee Hub</span>
        <a href="<?= APP_URL ?>/modules/hub/index.php" class="sidebar-nav-item <?= $activeMenu === 'hub' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Requests Hub
        </a>
    </div>
    <?php endif; ?>

    <!-- Consultants -->
    <?php if (canView('consultants.view')): ?>
    <div class="sidebar-section">
        <span class="sidebar-section-label">Consultants</span>
        <a href="<?= APP_URL ?>/modules/consultants/index.php" class="sidebar-nav-item <?= $activeMenu === 'consultants' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Consultants
        </a>
    </div>
    <?php endif; ?>

    <!-- Operations -->
    <?php if (canView('assets.view') || canView('performance.view') || canView('disciplinary.view') || canView('documents.view') || canView('reports.view')): ?>
    <div class="sidebar-section">
        <span class="sidebar-section-label">Operations</span>
        <?php if (canView('assets.view')): ?>
        <a href="<?= APP_URL ?>/modules/assets/index.php" class="sidebar-nav-item <?= $activeMenu === 'assets' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            Assets
        </a>
        <?php endif; ?>
        <?php if (canView('performance.view')): ?>
        <a href="<?= APP_URL ?>/modules/performance/index.php" class="sidebar-nav-item <?= $activeMenu === 'performance' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Performance
        </a>
        <?php endif; ?>
        <?php if (canView('disciplinary.view')): ?>
        <a href="<?= APP_URL ?>/modules/disciplinary/index.php" class="sidebar-nav-item <?= $activeMenu === 'disciplinary' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Disciplinary
        </a>
        <?php endif; ?>
        <?php if (canView('documents.view')): ?>
        <a href="<?= APP_URL ?>/modules/documents/index.php" class="sidebar-nav-item <?= $activeMenu === 'documents' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Documents
        </a>
        <a href="<?= APP_URL ?>/modules/documents/templates.php" class="sidebar-nav-item <?= $activeMenu === 'doc_templates' ? 'active' : '' ?>" style="padding-left:28px;font-size:0.72rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
            Templates
        </a>
        <a href="<?= APP_URL ?>/modules/documents/generate.php" class="sidebar-nav-item <?= $activeMenu === 'doc_generate' ? 'active' : '' ?>" style="padding-left:28px;font-size:0.72rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Generate
        </a>
        <?php endif; ?>
        <?php if (canView('reports.view')): ?>
        <a href="<?= APP_URL ?>/modules/reports/index.php" class="sidebar-nav-item <?= $activeMenu === 'reports' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Reports
        </a>
        <a href="<?= APP_URL ?>/modules/reports/executive.php" class="sidebar-nav-item <?= $activeMenu === 'reports_executive' ? 'active' : '' ?>" style="padding-left:28px;font-size:0.72rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            Executive Analytics
        </a>
        <?php endif; // canView reports ?>
    </div>
    <?php endif; // Operations section ?>

    <!-- Archives -->
    <?php if (canView('archive.view')): ?>
    <div class="sidebar-section">
        <span class="sidebar-section-label">Archives</span>
        <a href="<?= APP_URL ?>/modules/archive/monthly.php" class="sidebar-nav-item <?= $activeMenu === 'archive_monthly' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
            Monthly Archive
        </a>
        <a href="<?= APP_URL ?>/modules/archive/quarterly.php" class="sidebar-nav-item <?= $activeMenu === 'archive_quarterly' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
            Quarterly Archive
        </a>
        <a href="<?= APP_URL ?>/modules/archive/yearly.php" class="sidebar-nav-item <?= $activeMenu === 'archive_yearly' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
            Yearly Archive
        </a>
    </div>
    <?php endif; ?>

    <!-- Administration -->
    <?php if (canView('users.manage') || canView('roles.manage') || hasPermission('settings.manage') || canView('audit.view')): ?>
    <div class="sidebar-section" style="margin-bottom:16px;">
        <span class="sidebar-section-label">Administration</span>
        <?php if (canView('users.manage')): ?>
        <a href="<?= APP_URL ?>/modules/users/index.php" class="sidebar-nav-item <?= $activeMenu === 'users' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Users
        </a>
        <?php endif; ?>
        <?php if (canView('roles.manage')): ?>
        <a href="<?= APP_URL ?>/modules/roles/index.php" class="sidebar-nav-item <?= $activeMenu === 'roles' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Roles
        </a>
        <?php endif; ?>
        <?php if (hasPermission('settings.manage')): ?>
        <a href="<?= APP_URL ?>/modules/settings/index.php" class="sidebar-nav-item <?= $activeMenu === 'settings' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Settings
        </a>
        <!-- Branding & Theme sub-items -->
        <?php if (hasPermission('branding.letterheads')): ?>
        <a href="<?= APP_URL ?>/modules/settings/branding.php" class="sidebar-nav-item <?= $activeMenu === 'branding' ? 'active' : '' ?>" style="padding-left:28px;font-size:0.72rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            Branding Assets
        </a>
        <?php endif; ?>
        <?php if (hasPermission('branding.theme')): ?>
        <a href="<?= APP_URL ?>/modules/settings/theme.php" class="sidebar-nav-item <?= $activeMenu === 'theme' ? 'active' : '' ?>" style="padding-left:28px;font-size:0.72rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 0 20"/><path d="M12 6l-4 6h8l-4 6"/></svg>
            Appearance
        </a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/modules/settings/email.php" class="sidebar-nav-item <?= $activeMenu === 'email_settings' ? 'active' : '' ?>" style="padding-left:28px;font-size:0.72rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Email &amp; Notifications
        </a>
        <?php endif; ?>
        <?php if (canView('audit.view')): ?>
        <a href="<?= APP_URL ?>/modules/audit/index.php" class="sidebar-nav-item <?= $activeMenu === 'audit' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Audit Logs
        </a>
        <?php endif; ?>
        <?php if (($_SESSION['user_role'] ?? '') === 'super_admin'): ?>
        <a href="<?= APP_URL ?>/modules/activity_log/index.php" class="sidebar-nav-item <?= $activeMenu === 'activity_log' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Activity Logs
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Scroll-more fade indicator -->
    <div id="sidebarScrollHint" style="
        position:sticky;bottom:0;left:0;right:0;height:32px;
        background:linear-gradient(to bottom, transparent, rgba(15,23,42,0.95));
        pointer-events:none;display:flex;align-items:flex-end;justify-content:center;
        padding-bottom:5px;font-size:0.58rem;color:rgba(255,255,255,0.28);letter-spacing:.06em;
        flex-shrink:0;
    ">
        ▼ scroll for more
    </div>

    <!-- Sidebar User Profile Card -->
    <div class="sidebar-user-card" style="
        flex-shrink:0;
        padding:12px 14px;
        border-top:1px solid rgba(255,255,255,0.08);
        background:rgba(0,0,0,0.25);
        display:flex;
        align-items:center;
        gap:11px;
        position:sticky;
        bottom:0;
    ">
        <div style="
            width:36px;height:36px;border-radius:50%;
            background:var(--primary);
            display:flex;align-items:center;justify-content:center;
            font-size:0.88rem;font-weight:700;color:#fff;
            flex-shrink:0;letter-spacing:-.01em;
        ">
            <?php
            $initials = strtoupper(substr($user['first_name'] ?? $_SESSION['user_name'], 0, 1));
            if (!empty($user['last_name'])) $initials .= strtoupper(substr($user['last_name'], 0, 1));
            echo $initials;
            ?>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:0.86rem;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $_SESSION['user_name']) ?>
            </div>
            <div style="font-size:0.72rem;color:rgba(255,255,255,0.45);text-transform:capitalize;margin-top:1px;">
                <?= str_replace('_', ' ', $_SESSION['user_role']) ?>
            </div>
        </div>
        <a href="<?= APP_URL ?>/auth/logout.php" title="Logout" style="
            color:rgba(255,255,255,0.3);flex-shrink:0;display:flex;
            align-items:center;transition:color .15s;
        " onmouseover="this.style.color='rgba(255,255,255,0.7)'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- TOP HEADER -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="topbar-icon-btn topbar-mobile-toggle" id="sidebarToggle" style="border:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="topbar-search" style="margin-left:8px;">
                <span class="topbar-search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" placeholder="Search employees, records..." id="globalSearch" autocomplete="off">
            </div>
        </div>

        <div class="topbar-right">
            <!-- Quick Actions -->
            <a href="<?= APP_URL ?>/modules/attendance/kiosk.php" class="btn btn-sm btn-primary" style="text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Kiosk
            </a>

            <!-- Notifications -->
            <div style="position:relative;">
                <button class="topbar-icon-btn" id="notifBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="notif-badge" id="notifBadge" style="<?= $unreadNotifs > 0 ? '' : 'display:none' ?>"><?= $unreadNotifs > 9 ? '9+' : $unreadNotifs ?></span>
                </button>

                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-dropdown-header">
                        Notifications
                        <a href="#" id="markAllReadBtn" style="font-size:0.68rem;color:var(--primary);">Mark all read</a>
                    </div>
                    <div id="notifList" style="min-height:60px">
                        <div class="notif-item" style="text-align:center;color:var(--text-muted);">Loading…</div>
                    </div>
                    <div class="notif-dropdown-footer">Notifications auto-refresh every 60s</div>
                </div>
            </div>

            <!-- User Menu -->
            <div style="position:relative;">
                <div class="topbar-user" id="userMenuBtn">
                    <div class="topbar-avatar">
                        <?php if (!empty($user['photo'])): ?>
                            <img src="<?= APP_URL ?>/<?= e($user['photo']) ?>" alt="Avatar">
                        <?php else: ?>
                            <?= strtoupper(substr($user['first_name'] ?? $_SESSION['user_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="topbar-user-name"><?= e($user['first_name'] ?? $_SESSION['user_name']) ?></div>
                        <div class="topbar-user-role"><?= str_replace('_', ' ', $_SESSION['user_role']) ?></div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </div>

                <div class="user-dropdown" id="userDropdown">
                    <div class="user-dropdown-header">
                        <div class="user-dropdown-name"><?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? $_SESSION['user_name'])) ?></div>
                        <div class="user-dropdown-role"><?= str_replace('_', ' ', $_SESSION['user_role']) ?></div>
                    </div>
                    <a href="<?= APP_URL ?>/modules/users/profile.php" class="user-dropdown-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        My Profile
                    </a>
                    <a href="<?= APP_URL ?>/auth/change_password.php" class="user-dropdown-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Change Password
                    </a>
                    <div class="user-dropdown-divider"></div>
                    <a href="<?= APP_URL ?>/auth/logout.php" class="user-dropdown-item danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Sign Out
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <main class="page-content">

