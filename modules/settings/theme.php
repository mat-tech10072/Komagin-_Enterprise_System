<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('branding.theme', 'view');

$pageTitle  = 'System Appearance';
$activeMenu = 'settings';

$settings     = getCompanySettings();
$themeJson    = $settings['theme_settings'] ?? '{}';
$theme        = json_decode($themeJson, true) ?: [];

// Defaults
$defaults = [
    'primary'        => '#023852',
    'primary_dark'   => '#012a3d',
    'primary_light'  => '#E8F4F8',
    'sidebar_bg'     => '#0F172A',
    'sidebar_text'   => 'rgba(255,255,255,0.65)',
    'bg'             => '#F8FAFC',
    'bg_card'        => '#FFFFFF',
    'border'         => '#E2E8F0',
    'text'           => '#0F172A',
    'text_secondary' => '#64748B',
    'success'        => '#22C55E',
    'warning'        => '#F59E0B',
    'danger'         => '#EF4444',
    'info'           => '#3B82F6',
    'sidebar_width'  => '232',
    'border_radius'  => '10',
    'font_family'    => 'Inter',
    'mode'           => 'light',
    'login_bg'       => '#023852',
    'login_bg_dark'  => '#012a3d',
    'welcome_message'=> 'Welcome to Komagin HR Management System',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $newTheme = [];
    foreach ($defaults as $key => $default) {
        $newTheme[$key] = trim($_POST[$key] ?? $default);
    }

    // Handle favicon upload
    if (!empty($_FILES['favicon']['name'])) {
        $up = uploadFile($_FILES['favicon'], 'company', ['image/png','image/x-icon','image/gif','image/jpeg']);
        if ($up['success']) {
            db()->prepare("UPDATE company_settings SET company_favicon=? WHERE id=1")->execute([$up['path']]);
        }
    }

    // Handle login background upload
    if (!empty($_FILES['login_bg_image']['name'])) {
        $up = uploadFile($_FILES['login_bg_image'], 'company', ['image/jpeg','image/png','image/webp']);
        if ($up['success']) {
            db()->prepare("UPDATE company_settings SET login_background=? WHERE id=1")->execute([$up['path']]);
        }
    }

    db()->prepare("UPDATE company_settings SET theme_settings=? WHERE id=1")->execute([json_encode($newTheme)]);
    auditLog('settings','update_theme',1,null,json_encode($newTheme));
    setFlash('success','Appearance settings saved. Reload the page to see changes.');
    header('Location: theme.php'); exit;
}

// Merge saved with defaults
$t = array_merge($defaults, $theme);
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/settings/index.php">Settings</a></li>
                <li class="breadcrumb-item active">System Appearance</li>
            </ol>
        </nav>
        <h1 class="page-title">System Appearance</h1>
        <p class="page-subtitle">Customise colours, typography, and layout — changes apply site-wide instantly</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-secondary btn-sm" onclick="resetDefaults()">Reset to Defaults</button>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" id="themeForm">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

<!-- Colour Settings -->
<div class="card">
    <div class="card-header"><span class="card-title">Colour Palette</span></div>
    <div class="card-body">
        <?php $colorFields = [
            ['primary',       'Primary Colour',        'Main buttons, active nav, links'],
            ['primary_dark',  'Primary Dark',          'Hover state for primary'],
            ['primary_light', 'Primary Light',         'Light backgrounds on primary elements'],
            ['sidebar_bg',    'Sidebar Background',    'Admin sidebar colour'],
            ['bg',            'Page Background',       'Main content area background'],
            ['bg_card',       'Card Background',       'Card and panel background'],
            ['border',        'Border Colour',         'Lines, separators, table borders'],
            ['text',          'Primary Text',          'Headings and main body text'],
            ['text_secondary','Secondary Text',        'Labels, subtitles'],
            ['success',       'Success Colour',        'Active, approved, positive states'],
            ['warning',       'Warning Colour',        'Pending, caution states'],
            ['danger',        'Danger Colour',         'Rejected, error, delete states'],
            ['info',          'Info Colour',           'Information, in-progress states'],
        ]; ?>
        <?php foreach ($colorFields as [$key, $label, $desc]): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid var(--border-light);">
            <input type="color" name="<?= $key ?>" id="c_<?= $key ?>" value="<?= e($t[$key]) ?>"
                   style="width:40px;height:32px;border:1px solid var(--border);border-radius:6px;padding:2px;cursor:pointer;"
                   oninput="document.getElementById('h_<?= $key ?>').value=this.value;livePreview()">
            <div style="flex:1;">
                <div style="font-size:0.8rem;font-weight:600;"><?= $label ?></div>
                <div style="font-size:0.68rem;color:var(--text-muted);"><?= $desc ?></div>
            </div>
            <input type="text" id="h_<?= $key ?>" value="<?= e($t[$key]) ?>"
                   style="width:90px;font-family:monospace;font-size:0.75rem;padding:4px 8px;border:1px solid var(--border);border-radius:5px;"
                   oninput="if(this.value.match(/^#[0-9a-fA-F]{6}$/)){document.getElementById('c_<?= $key ?>').value=this.value;document.querySelector('[name=<?= $key ?>]').value=this.value;livePreview()}"
                   onchange="document.querySelector('[name=<?= $key ?>]').value=this.value">
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Layout & Typography -->
<div>
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><span class="card-title">Login Page</span></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Left Panel Background</label>
                    <input type="color" name="login_bg" value="<?= e($t['login_bg']) ?>"
                           style="width:100%;height:36px;border:1px solid var(--border);border-radius:6px;padding:2px;cursor:pointer;">
                </div>
                <div class="form-group">
                    <label class="form-label">Panel Gradient End</label>
                    <input type="color" name="login_bg_dark" value="<?= e($t['login_bg_dark']) ?>"
                           style="width:100%;height:36px;border:1px solid var(--border);border-radius:6px;padding:2px;cursor:pointer;">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Login Background Image (optional)</label>
                <input type="file" class="form-control" name="login_bg_image" accept="image/*">
                <?php $loginBg = $settings['login_background'] ?? null; ?>
                <?php if ($loginBg): ?>
                <div style="margin-top:6px;font-size:0.72rem;color:var(--text-muted);">Current: <code><?= e(basename($loginBg)) ?></code></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><span class="card-title">Layout</span></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Sidebar Width (px)</label>
                    <input type="number" class="form-control" name="sidebar_width" value="<?= e($t['sidebar_width']) ?>" min="180" max="320">
                </div>
                <div class="form-group">
                    <label class="form-label">Border Radius (px)</label>
                    <input type="number" class="form-control" name="border_radius" value="<?= e($t['border_radius']) ?>" min="0" max="24">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Font Family</label>
                <select class="form-select" name="font_family">
                    <?php foreach (['Inter','Roboto','Open Sans','Lato','Poppins','Nunito','Source Sans Pro'] as $font): ?>
                    <option value="<?= $font ?>" <?= $t['font_family']===$font?'selected':'' ?>><?= $font ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><span class="card-title">Dashboard</span></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Welcome Message</label>
                <input type="text" class="form-control" name="welcome_message" value="<?= e($t['welcome_message']) ?>" placeholder="Welcome to Komagin HR">
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Favicon</span></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Upload Favicon (PNG, 32×32 recommended)</label>
                <input type="file" class="form-control" name="favicon" accept="image/png,image/x-icon,image/gif">
                <?php $fav = $settings['company_favicon'] ?? null; ?>
                <?php if ($fav): ?>
                <div style="margin-top:8px;display:flex;align-items:center;gap:8px;">
                    <img src="<?= APP_URL ?>/<?= e($fav) ?>" style="width:32px;height:32px;border:1px solid var(--border);border-radius:4px;">
                    <span style="font-size:0.72rem;color:var(--text-muted);">Current favicon</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Live Preview Banner -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Live Preview</span></div>
    <div id="livePreview" style="padding:16px;background:var(--bg);border-radius:var(--radius);display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
        <button id="prevPrimary" style="padding:8px 18px;border-radius:8px;border:none;font-weight:600;font-size:0.82rem;cursor:pointer;">Primary Button</button>
        <button id="prevSecondary" style="padding:8px 18px;border-radius:8px;border:1px solid;font-weight:600;font-size:0.82rem;cursor:pointer;background:transparent;">Secondary</button>
        <span id="prevBadgeSuccess" style="padding:3px 10px;border-radius:4px;font-size:0.72rem;font-weight:600;">Active</span>
        <span id="prevBadgeWarning" style="padding:3px 10px;border-radius:4px;font-size:0.72rem;font-weight:600;">Pending</span>
        <span id="prevBadgeDanger" style="padding:3px 10px;border-radius:4px;font-size:0.72rem;font-weight:600;">Rejected</span>
        <div id="prevCard" style="padding:12px 16px;border-radius:8px;border:1px solid;font-size:0.82rem;flex:1;min-width:180px;">
            <div id="prevText" style="font-weight:600;">Sample Card Text</div>
            <div id="prevSubText" style="font-size:0.72rem;margin-top:3px;">Subtitle / metadata</div>
        </div>
    </div>
</div>

<div style="display:flex;justify-content:flex-end;gap:12px;">
    <a href="<?= APP_URL ?>/modules/settings/index.php" class="btn btn-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary" style="padding:0 32px;">Save Appearance Settings</button>
</div>
</form>

<script>
const DEFAULTS = <?= json_encode($defaults) ?>;

function livePreview() {
    const get = name => document.querySelector('[name='+name+']')?.value || DEFAULTS[name];
    const p = get('primary'), pd = get('primary_dark'), pl = get('primary_light');
    const bg = get('bg'), bgc = get('bg_card'), bdr = get('border');
    const txt = get('text'), txts = get('text_secondary');
    const suc = get('success'), wrn = get('warning'), dng = get('danger');
    const br = get('border_radius') + 'px';

    const btn = document.getElementById('prevPrimary');
    btn.style.background = p; btn.style.color = '#fff'; btn.style.borderRadius = br;
    const btn2 = document.getElementById('prevSecondary');
    btn2.style.borderColor = bdr; btn2.style.color = txt; btn2.style.borderRadius = br;
    document.getElementById('prevBadgeSuccess').style.background = suc + '22'; document.getElementById('prevBadgeSuccess').style.color = suc;
    document.getElementById('prevBadgeWarning').style.background = wrn + '22'; document.getElementById('prevBadgeWarning').style.color = wrn;
    document.getElementById('prevBadgeDanger').style.background  = dng + '22'; document.getElementById('prevBadgeDanger').style.color  = dng;
    const card = document.getElementById('prevCard');
    card.style.background = bgc; card.style.borderColor = bdr; card.style.borderRadius = br;
    document.getElementById('prevText').style.color = txt;
    document.getElementById('prevSubText').style.color = txts;
    document.getElementById('livePreview').style.background = bg;
}

function resetDefaults() {
    if (!confirm('Reset all appearance settings to defaults?')) return;
    Object.entries(DEFAULTS).forEach(([k,v]) => {
        const el = document.querySelector('[name='+k+']');
        if (el) { el.value = v; const hEl = document.getElementById('h_'+k); if (hEl) hEl.value = v; const cEl = document.getElementById('c_'+k); if (cEl) cEl.value = v; }
    });
    livePreview();
}

livePreview();
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
