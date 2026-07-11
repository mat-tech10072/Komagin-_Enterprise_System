<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('branding.letterheads');

$pageTitle  = 'Branding & Assets';
$activeMenu = 'settings';
$activeTab  = $_GET['tab'] ?? 'letterheads';

// ── Allowed types for branding uploads ────────────────────────────────────
$IMG_TYPES = ['image/png','image/jpeg','image/gif','image/webp','image/svg+xml'];
$PNG_TYPES = ['image/png','image/gif','image/webp']; // transparency support

// ── POST handlers ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $act = $_POST['action'] ?? '';

    // ── LETTERHEADS ────────────────────────────────────────────────────────
    if ($act === 'save_letterhead') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $type     = $_POST['type'] ?? 'official';
        $paper    = $_POST['paper_size'] ?? 'A4';
        $orient   = $_POST['orientation'] ?? 'portrait';
        $marginT  = (int)($_POST['margin_top'] ?? 120);
        $marginB  = (int)($_POST['margin_bottom'] ?? 60);
        $isDefault= isset($_POST['is_default']) ? 1 : 0;
        $headerHtml = $_POST['header_html'] ?? null;
        $footerHtml = $_POST['footer_html'] ?? null;

        if (!$name) { setFlash('error','Name is required.'); header('Location: ?tab=letterheads'); exit; }

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $up = uploadFile($_FILES['image'], 'letterheads', $IMG_TYPES);
            if (!$up['success']) { setFlash('error', $up['error']); header('Location: ?tab=letterheads'); exit; }
            $imagePath = $up['path'];
        }

        if ($isDefault) db()->exec("UPDATE company_letterheads SET is_default=0");

        if ($id) {
            $set = "name=?,type=?,paper_size=?,orientation=?,margin_top=?,margin_bottom=?,is_default=?,header_html=?,footer_html=?";
            $vals = [$name,$type,$paper,$orient,$marginT,$marginB,$isDefault,$headerHtml,$footerHtml];
            if ($imagePath) { $set .= ",image_path=?"; $vals[] = $imagePath; }
            $vals[] = $id;
            db()->prepare("UPDATE company_letterheads SET $set WHERE id=?")->execute($vals);
            auditLog('branding','update_letterhead',$id);
            setFlash('success','Letterhead updated.');
        } else {
            if (!$imagePath) { setFlash('error','Image required for new letterhead.'); header('Location: ?tab=letterheads'); exit; }
            db()->prepare("INSERT INTO company_letterheads (name,type,paper_size,orientation,margin_top,margin_bottom,is_default,image_path,header_html,footer_html,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$name,$type,$paper,$orient,$marginT,$marginB,$isDefault,$imagePath,$headerHtml,$footerHtml,$_SESSION['user_id']]);
            auditLog('branding','create_letterhead',(int)db()->lastInsertId());
            setFlash('success','Letterhead added.');
        }
        header('Location: ?tab=letterheads'); exit;

    } elseif ($act === 'toggle_letterhead') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE company_letterheads SET is_active = NOT is_active WHERE id=?")->execute([$id]);
        auditLog('branding','toggle_letterhead',$id);
        header('Location: ?tab=letterheads'); exit;

    } elseif ($act === 'delete_letterhead') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM company_letterheads WHERE id=?")->execute([$id]);
        auditLog('branding','delete_letterhead',$id);
        setFlash('success','Letterhead deleted.');
        header('Location: ?tab=letterheads'); exit;

    // ── SIGNATURES ─────────────────────────────────────────────────────────
    } elseif ($act === 'save_signature') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['signatory_name'] ?? '');
        $desig = trim($_POST['designation'] ?? '');
        $dept  = trim($_POST['department'] ?? '');
        $level = (int)($_POST['approval_level'] ?? 1);
        $notes = trim($_POST['notes'] ?? '');

        if (!$name) { setFlash('error','Name required.'); header('Location: ?tab=signatures'); exit; }

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $up = uploadFile($_FILES['image'], 'signatures', $PNG_TYPES);
            if (!$up['success']) { setFlash('error', 'Signature image: '.$up['error']); header('Location: ?tab=signatures'); exit; }
            $imagePath = $up['path'];
        }

        if ($id) {
            $set = "signatory_name=?,designation=?,department=?,approval_level=?,notes=?";
            $vals = [$name,$desig,$dept,$level,$notes];
            if ($imagePath) { $set .= ",image_path=?,version=version+1"; $vals[] = $imagePath; }
            $vals[] = $id;
            db()->prepare("UPDATE company_signatures SET $set WHERE id=?")->execute($vals);
            auditLog('branding','update_signature',$id);
            setFlash('success','Signature updated.');
        } else {
            if (!$imagePath) { setFlash('error','Signature image (transparent PNG) required.'); header('Location: ?tab=signatures'); exit; }
            db()->prepare("INSERT INTO company_signatures (signatory_name,designation,department,approval_level,image_path,notes,created_by) VALUES (?,?,?,?,?,?,?)")
                ->execute([$name,$desig,$dept,$level,$imagePath,$notes,$_SESSION['user_id']]);
            auditLog('branding','create_signature',(int)db()->lastInsertId());
            setFlash('success','Signature added.');
        }
        header('Location: ?tab=signatures'); exit;

    } elseif ($act === 'toggle_signature') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE company_signatures SET is_active = NOT is_active WHERE id=?")->execute([$id]);
        header('Location: ?tab=signatures'); exit;

    } elseif ($act === 'delete_signature') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM company_signatures WHERE id=?")->execute([$id]);
        auditLog('branding','delete_signature',$id);
        setFlash('success','Signature deleted.');
        header('Location: ?tab=signatures'); exit;

    // ── STAMPS ─────────────────────────────────────────────────────────────
    } elseif ($act === 'save_stamp') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if (!$name) { setFlash('error','Name required.'); header('Location: ?tab=stamps'); exit; }

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $up = uploadFile($_FILES['image'], 'stamps', $PNG_TYPES);
            if (!$up['success']) { setFlash('error','Stamp image: '.$up['error']); header('Location: ?tab=stamps'); exit; }
            $imagePath = $up['path'];
        }

        if ($id) {
            $set = "name=?"; $vals = [$name];
            if ($imagePath) { $set .= ",image_path=?"; $vals[] = $imagePath; }
            $vals[] = $id;
            db()->prepare("UPDATE company_stamps SET $set WHERE id=?")->execute($vals);
            setFlash('success','Stamp updated.');
        } else {
            if (!$imagePath) { setFlash('error','Stamp image (transparent PNG) required.'); header('Location: ?tab=stamps'); exit; }
            db()->prepare("INSERT INTO company_stamps (name,image_path,created_by) VALUES (?,?,?)")
                ->execute([$name,$imagePath,$_SESSION['user_id']]);
            setFlash('success','Stamp added.');
        }
        auditLog('branding','save_stamp',$id ?: (int)db()->lastInsertId());
        header('Location: ?tab=stamps'); exit;

    } elseif ($act === 'toggle_stamp') {
        db()->prepare("UPDATE company_stamps SET is_active = NOT is_active WHERE id=?")->execute([(int)($_POST['id']??0)]);
        header('Location: ?tab=stamps'); exit;

    } elseif ($act === 'delete_stamp') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM company_stamps WHERE id=?")->execute([$id]);
        setFlash('success','Stamp deleted.'); header('Location: ?tab=stamps'); exit;

    // ── WATERMARKS ─────────────────────────────────────────────────────────
    } elseif ($act === 'save_watermark') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $type     = $_POST['wm_type'] ?? 'text';
        $text     = trim($_POST['text'] ?? '');
        $opacity  = min(1, max(0, (float)($_POST['opacity'] ?? 0.10)));
        $color    = $_POST['color'] ?? '#808080';
        $fontSize = (int)($_POST['font_size'] ?? 48);
        $rotation = (int)($_POST['rotation'] ?? -45);

        if (!$name) { setFlash('error','Name required.'); header('Location: ?tab=watermarks'); exit; }

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $up = uploadFile($_FILES['image'], 'watermarks', $PNG_TYPES);
            if ($up['success']) $imagePath = $up['path'];
        }

        if ($id) {
            db()->prepare("UPDATE company_watermarks SET name=?,type=?,text=?,opacity=?,color=?,font_size=?,rotation=?" . ($imagePath?",image_path=?":'') . " WHERE id=?")
                ->execute(array_filter([$name,$type,$text,$opacity,$color,$fontSize,$rotation,$imagePath,$id], fn($v)=>$v!==null || $v===0));
            setFlash('success','Watermark updated.');
        } else {
            db()->prepare("INSERT INTO company_watermarks (name,type,text,image_path,opacity,color,font_size,rotation,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$name,$type,$text,$imagePath,$opacity,$color,$fontSize,$rotation,$_SESSION['user_id']]);
            setFlash('success','Watermark added.');
        }
        auditLog('branding','save_watermark',$id);
        header('Location: ?tab=watermarks'); exit;

    } elseif ($act === 'toggle_watermark') {
        db()->prepare("UPDATE company_watermarks SET is_active = NOT is_active WHERE id=?")->execute([(int)($_POST['id']??0)]);
        header('Location: ?tab=watermarks'); exit;

    } elseif ($act === 'delete_watermark') {
        db()->prepare("DELETE FROM company_watermarks WHERE id=?")->execute([(int)($_POST['id']??0)]);
        setFlash('success','Watermark deleted.'); header('Location: ?tab=watermarks'); exit;
    }
}

// ── Load data ─────────────────────────────────────────────────────────────
$letterheads = db()->query("SELECT * FROM company_letterheads ORDER BY is_default DESC, name")->fetchAll(PDO::FETCH_ASSOC);
$signatures  = db()->query("SELECT * FROM company_signatures ORDER BY approval_level, signatory_name")->fetchAll(PDO::FETCH_ASSOC);
$stamps      = db()->query("SELECT * FROM company_stamps ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$watermarks  = db()->query("SELECT * FROM company_watermarks ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Edit mode
$editLH  = isset($_GET['edit_lh'])  ? db()->prepare("SELECT * FROM company_letterheads WHERE id=?")->execute([(int)$_GET['edit_lh']])  && ($r=db()->query("SELECT * FROM company_letterheads WHERE id=".(int)$_GET['edit_lh'])->fetch()) ? $r : null : null;
$editSig = isset($_GET['edit_sig']) ? db()->query("SELECT * FROM company_signatures WHERE id=".(int)$_GET['edit_sig'])->fetch() ?: null : null;
$editSt  = isset($_GET['edit_st'])  ? db()->query("SELECT * FROM company_stamps WHERE id=".(int)$_GET['edit_st'])->fetch()  ?: null : null;
$editWM  = isset($_GET['edit_wm'])  ? db()->query("SELECT * FROM company_watermarks WHERE id=".(int)$_GET['edit_wm'])->fetch() ?: null : null;

$csrf = generateCsrfToken();
$LH_TYPES = ['official'=>'Official Letter','contract'=>'Contract','payroll'=>'Payroll','hr_letter'=>'HR Letter','certificate'=>'Certificate','memo'=>'Internal Memo','general'=>'General'];
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/settings/index.php">Settings</a></li>
                <li class="breadcrumb-item active">Branding &amp; Assets</li>
            </ol>
        </nav>
        <h1 class="page-title">Branding &amp; Document Assets</h1>
        <p class="page-subtitle">Manage letterheads, signatures, stamps and watermarks used in document generation</p>
    </div>
</div>

<!-- Tabs -->
<div class="tab-nav" style="margin-bottom:20px;">
    <?php $tabs=['letterheads'=>'Letterheads ('.count($letterheads).')','signatures'=>'Signatures ('.count($signatures).')','stamps'=>'Stamps ('.count($stamps).')','watermarks'=>'Watermarks ('.count($watermarks).')']; ?>
    <?php foreach ($tabs as $k=>$l): ?>
    <a href="?tab=<?= $k ?>" class="tab-item <?= $activeTab===$k?'active':'' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<!-- ══ LETTERHEADS ══════════════════════════════════════════════════════════ -->
<?php if ($activeTab === 'letterheads'): ?>
<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start;">
    <!-- List -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Letterheads</span>
            <a href="?tab=letterheads" class="btn btn-primary btn-sm">+ New</a>
        </div>
        <div class="table-wrapper" style="border:none;">
            <table class="table">
                <thead><tr><th>Preview</th><th>Name</th><th>Type</th><th>Paper</th><th>Default</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($letterheads as $lh): ?>
                <tr>
                    <td>
                        <?php if (!empty($lh['image_path'])): ?>
                        <img src="<?= APP_URL ?>/<?= e($lh['image_path']) ?>" style="height:40px;object-fit:contain;border:1px solid var(--border);border-radius:4px;padding:2px;">
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="font-weight:600;font-size:0.82rem;"><?= e($lh['name']) ?></td>
                    <td><span class="badge badge-secondary"><?= e($LH_TYPES[$lh['type']] ?? $lh['type']) ?></span></td>
                    <td style="font-size:0.75rem;"><?= e($lh['paper_size']) ?> <?= e($lh['orientation']) ?></td>
                    <td><?= $lh['is_default'] ? '<span class="badge badge-success">Default</span>' : '—' ?></td>
                    <td><span class="badge badge-<?= $lh['is_active']?'success':'secondary' ?>"><?= $lh['is_active']?'Active':'Inactive' ?></span></td>
                    <td>
                        <a href="?tab=letterheads&edit_lh=<?= $lh['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="toggle_letterhead">
                            <input type="hidden" name="id" value="<?= $lh['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm"><?= $lh['is_active']?'Deactivate':'Activate' ?></button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this letterhead?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="delete_letterhead">
                            <input type="hidden" name="id" value="<?= $lh['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($letterheads)): ?>
                <tr><td colspan="7" class="empty-state">No letterheads yet. Add your first one.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form -->
    <div class="card">
        <div class="card-header"><span class="card-title"><?= $editLH ? 'Edit Letterhead' : 'Add Letterhead' ?></span></div>
        <form method="POST" enctype="multipart/form-data" style="padding:16px;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="save_letterhead">
            <?php if ($editLH): ?><input type="hidden" name="id" value="<?= $editLH['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Name <span class="required">*</span></label>
                <input type="text" class="form-control" name="name" required value="<?= e($editLH['name'] ?? '') ?>" placeholder="e.g. Official Company Letterhead">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="type">
                        <?php foreach ($LH_TYPES as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($editLH['type']??'official')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Paper</label>
                    <select class="form-select" name="paper_size">
                        <?php foreach (['A4','A5','Letter','Legal'] as $p): ?>
                        <option value="<?= $p ?>" <?= ($editLH['paper_size']??'A4')===$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Orientation</label>
                    <select class="form-select" name="orientation">
                        <option value="portrait" <?= ($editLH['orientation']??'portrait')==='portrait'?'selected':'' ?>>Portrait</option>
                        <option value="landscape" <?= ($editLH['orientation']??'')==='landscape'?'selected':'' ?>>Landscape</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Top Margin (px)</label>
                    <input type="number" class="form-control" name="margin_top" value="<?= $editLH['margin_top'] ?? 120 ?>" min="0" max="400">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Letterhead Image <?= $editLH?'(leave blank to keep current)':'' ?></label>
                <input type="file" class="form-control" name="image" accept="image/*">
                <?php if (!empty($editLH['image_path'])): ?>
                <div style="margin-top:8px;"><img src="<?= APP_URL ?>/<?= e($editLH['image_path']) ?>" style="max-height:80px;border:1px solid var(--border);border-radius:4px;padding:4px;"></div>
                <?php endif; ?>
                <div style="font-size:0.7rem;color:var(--text-muted);margin-top:4px;">Upload the full-page letterhead (PNG/JPG). The document body will be overlaid on it.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Bottom Margin (px)</label>
                <input type="number" class="form-control" name="margin_bottom" value="<?= $editLH['margin_bottom'] ?? 60 ?>" min="0" max="300">
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.82rem;">
                    <input type="checkbox" name="is_default" <?= !empty($editLH['is_default'])?'checked':'' ?>>
                    Set as default letterhead
                </label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">
                <?= $editLH ? 'Update Letterhead' : 'Save Letterhead' ?>
            </button>
            <?php if ($editLH): ?><a href="?tab=letterheads" class="btn btn-secondary" style="width:100%;margin-top:8px;">Cancel</a><?php endif; ?>
        </form>
    </div>
</div>

<!-- ══ SIGNATURES ══════════════════════════════════════════════════════════ -->
<?php elseif ($activeTab === 'signatures'): ?>
<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Signatories</span>
            <a href="?tab=signatures" class="btn btn-primary btn-sm">+ New</a>
        </div>
        <div class="table-wrapper" style="border:none;">
            <table class="table">
                <thead><tr><th>Signature</th><th>Name</th><th>Designation</th><th>Level</th><th>Version</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $levelLabels=[1=>'Officer',2=>'Manager',3=>'Director']; ?>
                <?php foreach ($signatures as $sig): ?>
                <tr>
                    <td>
                        <img src="<?= APP_URL ?>/<?= e($sig['image_path']) ?>"
                             style="height:36px;max-width:100px;object-fit:contain;background:repeating-conic-gradient(#ccc 0% 25%, white 0% 50%) 0 0/10px 10px;border-radius:4px;padding:2px;"
                             title="Transparent PNG preview">
                    </td>
                    <td style="font-weight:600;font-size:0.82rem;"><?= e($sig['signatory_name']) ?></td>
                    <td style="font-size:0.75rem;"><?= e($sig['designation'] ?? '—') ?></td>
                    <td><span class="badge badge-info"><?= $levelLabels[$sig['approval_level']] ?? 'L'.$sig['approval_level'] ?></span></td>
                    <td style="font-size:0.75rem;">v<?= $sig['version'] ?></td>
                    <td><span class="badge badge-<?= $sig['is_active']?'success':'secondary' ?>"><?= $sig['is_active']?'Active':'Inactive' ?></span></td>
                    <td>
                        <a href="?tab=signatures&edit_sig=<?= $sig['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="toggle_signature">
                            <input type="hidden" name="id" value="<?= $sig['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm"><?= $sig['is_active']?'Deactivate':'Activate' ?></button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this signature?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="delete_signature">
                            <input type="hidden" name="id" value="<?= $sig['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($signatures)): ?>
                <tr><td colspan="7" class="empty-state">No signatures yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title"><?= $editSig?'Edit Signature':'Add Signature' ?></span></div>
        <form method="POST" enctype="multipart/form-data" style="padding:16px;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="save_signature">
            <?php if ($editSig): ?><input type="hidden" name="id" value="<?= $editSig['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Signatory Name <span class="required">*</span></label>
                <input type="text" class="form-control" name="signatory_name" required value="<?= e($editSig['signatory_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Designation</label>
                <input type="text" class="form-control" name="designation" value="<?= e($editSig['designation'] ?? '') ?>" placeholder="e.g. HR Manager">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" class="form-control" name="department" value="<?= e($editSig['department'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Approval Level</label>
                    <select class="form-select" name="approval_level">
                        <?php foreach ([1=>'1 — Officer',2=>'2 — Manager',3=>'3 — Director'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($editSig['approval_level']??1)==$v?'selected':''?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Signature Image (transparent PNG) <?= $editSig?'— leave blank to keep':'' ?></label>
                <input type="file" class="form-control" name="image" accept="image/png,image/gif,image/webp">
                <?php if (!empty($editSig['image_path'])): ?>
                <div style="margin-top:8px;padding:8px;background:repeating-conic-gradient(#ccc 0% 25%, white 0% 50%) 0 0/12px 12px;border-radius:4px;display:inline-block;">
                    <img src="<?= APP_URL ?>/<?= e($editSig['image_path']) ?>" style="max-height:60px;max-width:180px;">
                </div>
                <?php endif; ?>
                <div style="font-size:0.7rem;color:var(--text-muted);margin-top:4px;">Use a transparent PNG for best results. The signature will be embedded directly into the document.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2"><?= e($editSig['notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;"><?= $editSig?'Update':'Save Signature' ?></button>
            <?php if ($editSig): ?><a href="?tab=signatures" class="btn btn-secondary" style="width:100%;margin-top:8px;">Cancel</a><?php endif; ?>
        </form>
    </div>
</div>

<!-- ══ STAMPS ══════════════════════════════════════════════════════════════ -->
<?php elseif ($activeTab === 'stamps'): ?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Company Stamps</span>
            <a href="?tab=stamps" class="btn btn-primary btn-sm">+ New</a>
        </div>
        <div class="table-wrapper" style="border:none;">
            <table class="table">
                <thead><tr><th>Preview</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($stamps as $st): ?>
                <tr>
                    <td>
                        <img src="<?= APP_URL ?>/<?= e($st['image_path']) ?>"
                             style="height:48px;max-width:80px;object-fit:contain;background:repeating-conic-gradient(#ccc 0% 25%, white 0% 50%) 0 0/10px 10px;border-radius:4px;padding:2px;">
                    </td>
                    <td style="font-weight:600;font-size:0.82rem;"><?= e($st['name']) ?></td>
                    <td><span class="badge badge-<?= $st['is_active']?'success':'secondary' ?>"><?= $st['is_active']?'Active':'Inactive' ?></span></td>
                    <td>
                        <a href="?tab=stamps&edit_st=<?= $st['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="toggle_stamp">
                            <input type="hidden" name="id" value="<?= $st['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm"><?= $st['is_active']?'Deactivate':'Activate' ?></button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="delete_stamp">
                            <input type="hidden" name="id" value="<?= $st['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($stamps)): ?><tr><td colspan="4" class="empty-state">No stamps yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title"><?= $editSt?'Edit Stamp':'Add Stamp' ?></span></div>
        <form method="POST" enctype="multipart/form-data" style="padding:16px;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="save_stamp">
            <?php if ($editSt): ?><input type="hidden" name="id" value="<?= $editSt['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Name <span class="required">*</span></label>
                <input type="text" class="form-control" name="name" required value="<?= e($editSt['name'] ?? '') ?>" placeholder="e.g. Company Official Stamp">
            </div>
            <div class="form-group">
                <label class="form-label">Stamp Image (transparent PNG) <?= $editSt?'— leave blank to keep':'' ?></label>
                <input type="file" class="form-control" name="image" accept="image/png,image/gif,image/webp">
                <?php if (!empty($editSt['image_path'])): ?>
                <div style="margin-top:8px;padding:8px;background:repeating-conic-gradient(#ccc 0% 25%, white 0% 50%) 0 0/12px 12px;border-radius:4px;display:inline-block;">
                    <img src="<?= APP_URL ?>/<?= e($editSt['image_path']) ?>" style="max-height:80px;max-width:160px;">
                </div>
                <?php endif; ?>
                <div style="font-size:0.7rem;color:var(--text-muted);margin-top:4px;">Transparent PNG recommended. Stamp will be embedded into generated documents.</div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;"><?= $editSt?'Update':'Save Stamp' ?></button>
            <?php if ($editSt): ?><a href="?tab=stamps" class="btn btn-secondary" style="width:100%;margin-top:8px;">Cancel</a><?php endif; ?>
        </form>
    </div>
</div>

<!-- ══ WATERMARKS ══════════════════════════════════════════════════════════ -->
<?php elseif ($activeTab === 'watermarks'): ?>
<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Watermarks</span>
            <a href="?tab=watermarks" class="btn btn-primary btn-sm">+ New</a>
        </div>
        <div class="table-wrapper" style="border:none;">
            <table class="table">
                <thead><tr><th>Preview</th><th>Name</th><th>Type</th><th>Opacity</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($watermarks as $wm): ?>
                <tr>
                    <td>
                        <?php if ($wm['type'] === 'text' && $wm['text']): ?>
                        <span style="font-size:1rem;font-weight:700;color:<?= e($wm['color']) ?>;opacity:<?= $wm['opacity'] * 3 ?>;transform:rotate(<?= $wm['rotation'] ?>deg);display:inline-block;white-space:nowrap;font-size:0.82rem;">
                            <?= e($wm['text']) ?>
                        </span>
                        <?php elseif (!empty($wm['image_path'])): ?>
                        <img src="<?= APP_URL ?>/<?= e($wm['image_path']) ?>" style="height:32px;object-fit:contain;opacity:0.4;">
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:600;font-size:0.82rem;"><?= e($wm['name']) ?></td>
                    <td><span class="badge badge-secondary"><?= ucfirst($wm['type']) ?></span></td>
                    <td style="font-size:0.75rem;"><?= ($wm['opacity']*100) ?>%</td>
                    <td><span class="badge badge-<?= $wm['is_active']?'success':'secondary' ?>"><?= $wm['is_active']?'Active':'Inactive' ?></span></td>
                    <td>
                        <a href="?tab=watermarks&edit_wm=<?= $wm['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="toggle_watermark">
                            <input type="hidden" name="id" value="<?= $wm['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm"><?= $wm['is_active']?'Deactivate':'Activate' ?></button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="delete_watermark">
                            <input type="hidden" name="id" value="<?= $wm['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($watermarks)): ?><tr><td colspan="6" class="empty-state">No watermarks defined.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title"><?= $editWM?'Edit Watermark':'Add Watermark' ?></span></div>
        <form method="POST" enctype="multipart/form-data" style="padding:16px;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="save_watermark">
            <?php if ($editWM): ?><input type="hidden" name="id" value="<?= $editWM['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Name <span class="required">*</span></label>
                <input type="text" class="form-control" name="name" required value="<?= e($editWM['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Type</label>
                <select class="form-select" name="wm_type" id="wmType" onchange="document.getElementById('wmTextSection').style.display=this.value==='text'?'block':'none';document.getElementById('wmImgSection').style.display=this.value==='image'?'block':'none';">
                    <option value="text" <?= ($editWM['type']??'text')==='text'?'selected':'' ?>>Text</option>
                    <option value="image" <?= ($editWM['type']??'')==='image'?'selected':'' ?>>Image</option>
                </select>
            </div>
            <div id="wmTextSection" style="<?= ($editWM['type']??'text')==='text'?'':'display:none' ?>">
                <div class="form-group">
                    <label class="form-label">Watermark Text</label>
                    <input type="text" class="form-control" name="text" value="<?= e($editWM['text'] ?? '') ?>" placeholder="e.g. CONFIDENTIAL">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Color</label>
                        <input type="color" class="form-control" name="color" value="<?= e($editWM['color'] ?? '#808080') ?>" style="height:40px;padding:4px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Font Size</label>
                        <input type="number" class="form-control" name="font_size" value="<?= $editWM['font_size'] ?? 48 ?>" min="20" max="120">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Opacity (0–1)</label>
                        <input type="number" class="form-control" name="opacity" step="0.01" min="0.01" max="1" value="<?= $editWM['opacity'] ?? 0.10 ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rotation (°)</label>
                        <input type="number" class="form-control" name="rotation" value="<?= $editWM['rotation'] ?? -45 ?>" min="-90" max="90">
                    </div>
                </div>
            </div>
            <div id="wmImgSection" style="<?= ($editWM['type']??'text')==='image'?'':'display:none' ?>">
                <div class="form-group">
                    <label class="form-label">Watermark Image (transparent PNG)</label>
                    <input type="file" class="form-control" name="image" accept="image/png,image/gif,image/webp">
                    <div class="form-group" style="margin-top:8px;">
                        <label class="form-label">Opacity</label>
                        <input type="number" class="form-control" name="opacity" step="0.01" min="0.01" max="1" value="<?= $editWM['opacity'] ?? 0.10 ?>">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;"><?= $editWM?'Update':'Save Watermark' ?></button>
            <?php if ($editWM): ?><a href="?tab=watermarks" class="btn btn-secondary" style="width:100%;margin-top:8px;">Cancel</a><?php endif; ?>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
