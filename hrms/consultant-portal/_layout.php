<?php
// Consultant Portal shared layout
// Usage: cpLayoutStart($pageTitle, $activeNav) ... cpLayoutEnd()

function cpLayoutStart(string $pageTitle, string $activeNav): void {
    $con     = cpCurrentConsultant();
    $name    = trim(($con['first_name'] ?? '') . ' ' . ($con['last_name'] ?? ''));
    $initials = strtoupper(substr($con['first_name'] ?? 'C', 0, 1) . substr($con['last_name'] ?? 'P', 0, 1));
    $conNum  = $con['consultant_number'] ?? '';
    $type    = $_SESSION['cp_type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — Komagin Consultant Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= CP_URL ?>/assets/cp.css">
</head>
<body>
<div class="cp-wrapper">
    <!-- Sidebar -->
    <aside class="cp-sidebar" id="cpSidebar">
        <div class="cp-brand">
            <div class="cp-brand-name">KOMAGIN</div>
            <div class="cp-brand-sub">Consultant Portal</div>
        </div>
        <div class="cp-con-card">
            <div class="cp-con-avatar"><?= htmlspecialchars($initials) ?></div>
            <div>
                <div class="cp-con-name"><?= htmlspecialchars($name) ?></div>
                <div class="cp-con-num"><?= htmlspecialchars($conNum) ?></div>
            </div>
        </div>
        <nav class="cp-nav">
            <div class="cp-nav-section">My Portal</div>
            <a href="<?= CP_URL ?>/dashboard.php" class="cp-nav-link <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <?php if ($type === 'time_based'): ?>
            <div class="cp-nav-section">Attendance</div>
            <a href="<?= CP_URL ?>/kiosk.php" class="cp-nav-link <?= $activeNav === 'kiosk' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Clock In / Out
            </a>
            <?php elseif ($type === 'output_based'): ?>
            <div class="cp-nav-section">Work Scope</div>
            <a href="<?= CP_URL ?>/scope.php" class="cp-nav-link <?= $activeNav === 'scope' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                My Scope
            </a>
            <?php endif; ?>
        </nav>
        <div class="cp-sidebar-footer">
            <a href="<?= CP_URL ?>/logout.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="cp-main">
        <header class="cp-topbar">
            <button onclick="document.getElementById('cpSidebar').classList.toggle('open')" style="background:none;border:none;cursor:pointer;padding:4px;display:none;" id="cpMenuToggle">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="cp-topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
            <div class="cp-topbar-right">
                <span class="cp-topbar-time" id="cpClock"></span>
                <span style="font-size:0.78rem;color:#64748B;"><?= htmlspecialchars($name) ?></span>
            </div>
        </header>
        <main class="cp-content">
<?php
}

function cpLayoutEnd(): void {
?>
        </main>
    </div>
</div>
<script>
(function() {
    function tick() {
        const el = document.getElementById('cpClock');
        if (el) el.textContent = new Date().toLocaleTimeString('en-ZA', {hour:'2-digit', minute:'2-digit'});
    }
    tick();
    setInterval(tick, 30000);

    if (window.innerWidth <= 768) {
        const toggle = document.getElementById('cpMenuToggle');
        if (toggle) toggle.style.display = '';
    }
})();
</script>
</body>
</html>
<?php
}
