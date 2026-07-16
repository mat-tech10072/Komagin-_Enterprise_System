<?php
// Portal shared layout helper
// Usage: include _layout.php AFTER setting $pageTitle and $activeNav
// Then call epLayoutEnd() at bottom of page

function epLayoutStart(string $pageTitle, string $activeNav): void {
    $emp    = epCurrentEmployee();
    $name   = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
    $initials = strtoupper(substr($emp['first_name'] ?? 'E', 0, 1) . substr($emp['last_name'] ?? 'P', 0, 1));
    $empNum = $emp['employee_number'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — Komagin Employee Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= EP_URL ?>/assets/portal.css">
</head>
<body>
<div class="ep-wrapper">
    <!-- Sidebar -->
    <aside class="ep-sidebar" id="epSidebar">
        <div class="ep-brand">
            <?php
            $epLayoutSettings = getCompanySettings();
            $epLayoutLogoPath = $epLayoutSettings['company_logo'] ?? null;
            $epLayoutLogoSrc  = !empty($epLayoutLogoPath) ? APP_URL . '/' . $epLayoutLogoPath : null;
            ?>
            <?php if ($epLayoutLogoSrc): ?>
            <img src="<?= htmlspecialchars($epLayoutLogoSrc) ?>"
                 alt="Komagin"
                 style="height:46px;width:auto;margin-bottom:8px;display:block;max-width:none;">
            <?php endif; ?>
            <div class="ep-brand-name">KOMAGIN</div>
            <div class="ep-brand-sub">Employee Portal</div>
        </div>
        <div class="ep-emp-card">
            <div class="ep-emp-avatar"><?= htmlspecialchars($initials) ?></div>
            <div>
                <div class="ep-emp-name"><?= htmlspecialchars($name) ?></div>
                <div class="ep-emp-num"><?= htmlspecialchars($empNum) ?></div>
            </div>
        </div>
        <nav class="ep-nav">
            <div class="ep-nav-section">My Portal</div>
            <a href="<?= EP_URL ?>/dashboard.php"   class="ep-nav-link <?= $activeNav==='dashboard'  ?'active':'' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="<?= EP_URL ?>/employment.php" class="ep-nav-link <?= $activeNav==='employment' ?'active':'' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                Employment
            </a>
            <div class="ep-nav-section">HR</div>
            <a href="<?= EP_URL ?>/attendance.php" class="ep-nav-link <?= $activeNav==='attendance' ?'active':'' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Attendance
            </a>
            <a href="<?= EP_URL ?>/leave.php"       class="ep-nav-link <?= $activeNav==='leave'      ?'active':'' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Leave
            </a>
            <div class="ep-nav-section">Payroll</div>
            <a href="<?= EP_URL ?>/payslips.php"   class="ep-nav-link <?= $activeNav==='payslips'   ?'active':'' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Pay Slips
            </a>
            <a href="<?= EP_URL ?>/savings.php"    class="ep-nav-link <?= $activeNav==='savings'    ?'active':'' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10H12V2z"/><path d="M21.18 13.52A10 10 0 0 0 12 2v10z"/></svg>
                Savings
            </a>
            <div class="ep-nav-section">Support</div>
            <a href="<?= EP_URL ?>/hub.php"        class="ep-nav-link <?= $activeNav==='hub'        ?'active':'' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Request Hub
            </a>
        </nav>
        <div class="ep-sidebar-footer">
            <a href="<?= EP_URL ?>/logout.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="ep-main">
        <header class="ep-topbar">
            <button class="btn btn-ghost btn-sm" id="sidebarToggle" style="display:none">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="ep-topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
            <div class="ep-topbar-right">
                <span class="ep-topbar-time" id="clockDisplay"></span>
                <span style="font-size:0.78rem;color:#64748B"><?= htmlspecialchars($name) ?></span>
            </div>
        </header>
        <main class="ep-content">
<?php
}

function epLayoutEnd(): void {
?>
        </main>
    </div>
</div>
<script>
function updateClock() {
    const now = new Date();
    document.getElementById('clockDisplay').textContent = now.toLocaleTimeString('en-ZA',{hour:'2-digit',minute:'2-digit'});
}
updateClock(); setInterval(updateClock, 30000);

const toggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('epSidebar');
if (window.innerWidth <= 768) {
    toggle.style.display = '';
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
}
</script>
</body>
</html>
<?php
}



