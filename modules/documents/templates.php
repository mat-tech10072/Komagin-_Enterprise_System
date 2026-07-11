<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';
require_once dirname(dirname(__DIR__)) . '/config/DocumentEngine.php';

requireLogin();
requirePermission('documents.view', 'view');

$pageTitle  = 'Document Templates';
$activeMenu = 'documents';

$action = $_GET['action'] ?? 'list';

// ── Save / Update Template ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    requirePermission('documents.upload', 'create');

    $postAction = $_POST['post_action'] ?? '';

    if ($postAction === 'save_template') {
        $catId   = (int)($_POST['category_id'] ?? 0);
        $title   = trim($_POST['title'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $body    = $_POST['body_html'] ?? '';
        $reqAppr = isset($_POST['requires_approval']) ? 1 : 0;
        $editId  = (int)($_POST['edit_id'] ?? 0);

        // Document config options
        $lhId       = (int)($_POST['letterhead_id'] ?? 0) ?: null;
        $sigIds     = array_filter(array_map('intval', (array)($_POST['signature_ids'] ?? [])));
        $stId       = (int)($_POST['stamp_id'] ?? 0) ?: null;
        $wmId       = (int)($_POST['watermark_id'] ?? 0) ?: null;
        $showLH     = isset($_POST['show_letterhead'])  ? 1 : 0;
        $showSig    = isset($_POST['show_signature'])   ? 1 : 0;
        $showSt     = isset($_POST['show_stamp'])       ? 1 : 0;
        $showWM     = isset($_POST['show_watermark'])   ? 1 : 0;
        $showQR     = isset($_POST['show_qr_code'])     ? 1 : 0;
        $showDN     = isset($_POST['show_doc_number'])  ? 1 : 0;
        $showPN     = isset($_POST['show_page_number']) ? 1 : 0;
        $showHdr    = isset($_POST['show_header'])      ? 1 : 0;
        $showFtr    = isset($_POST['show_footer'])      ? 1 : 0;

        if (!$catId || !$title || !$body) {
            setFlash('error', 'Category, title, and body are required.');
        } else {
            $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($title));
            $usedVars = json_encode(DocumentEngine::extractVariables($body));
            $sigJson  = !empty($sigIds) ? json_encode(array_values($sigIds)) : null;

            $configCols = "letterhead_id=?,signature_ids=?,stamp_id=?,watermark_id=?,
                show_letterhead=?,show_signature=?,show_stamp=?,show_watermark=?,
                show_qr_code=?,show_doc_number=?,show_page_number=?,show_header=?,show_footer=?";
            $configVals = [$lhId,$sigJson,$stId,$wmId,$showLH,$showSig,$showSt,$showWM,$showQR,$showDN,$showPN,$showHdr,$showFtr];

            if ($editId) {
                $old = db()->prepare("SELECT body_html, version FROM doc_templates WHERE id=?");
                $old->execute([$editId]); $oldData = $old->fetch();
                if ($oldData) {
                    db()->prepare("INSERT INTO doc_template_versions (template_id, version, body_html, changed_by) VALUES (?,?,?,?)")
                        ->execute([$editId, $oldData['version'], $oldData['body_html'], $_SESSION['user_id']]);
                    $stmt = db()->prepare("UPDATE doc_templates SET category_id=?, title=?, description=?, body_html=?,
                        variables_used=?, requires_approval=?, $configCols,
                        version=version+1, updated_by=?, updated_at=NOW() WHERE id=?");
                    $stmt->execute(array_merge([$catId,$title,$desc,$body,$usedVars,$reqAppr], $configVals, [$_SESSION['user_id'],$editId]));
                }
                auditLog('documents','update_template',$editId,null,$title);
                setFlash('success', 'Template updated.');
            } else {
                $slugCheck = db()->prepare("SELECT id FROM doc_templates WHERE slug=?");
                $slugCheck->execute([$slug]);
                if ($slugCheck->fetch()) $slug .= '_' . time();
                $stmt = db()->prepare("INSERT INTO doc_templates
                    (category_id, title, slug, description, body_html, variables_used, requires_approval,
                     letterhead_id, signature_ids, stamp_id, watermark_id,
                     show_letterhead, show_signature, show_stamp, show_watermark,
                     show_qr_code, show_doc_number, show_page_number, show_header, show_footer, created_by)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute(array_merge(
                    [$catId,$title,$slug,$desc,$body,$usedVars,$reqAppr],
                    [$lhId,$sigJson,$stId,$wmId,$showLH,$showSig,$showSt,$showWM,$showQR,$showDN,$showPN,$showHdr,$showFtr],
                    [$_SESSION['user_id']]
                ));
                $newId = (int)db()->lastInsertId();
                auditLog('documents','create_template',$newId,null,$title);
                setFlash('success', "Template \"$title\" created.");
            }
        }
        header('Location: ' . APP_URL . '/modules/documents/templates.php'); exit;

    } elseif ($postAction === 'toggle_template') {
        $tId = (int)($_POST['template_id'] ?? 0);
        db()->prepare("UPDATE doc_templates SET is_active = NOT is_active WHERE id=?")->execute([$tId]);
        auditLog('documents', 'toggle_template', $tId);
        setFlash('success', 'Template status toggled.');
        header('Location: ' . APP_URL . '/modules/documents/templates.php'); exit;
    }
}

// ── Load data ─────────────────────────────────────────────────────────────
$editTemplate = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $et = db()->prepare("SELECT * FROM doc_templates WHERE id=?");
    $et->execute([(int)$_GET['id']]);
    $editTemplate = $et->fetch(PDO::FETCH_ASSOC);
}

$categories = db()->query("SELECT * FROM doc_categories WHERE is_active=1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
$catFilter  = (int)($_GET['cat'] ?? 0);

$tWhere = 'WHERE 1'; $tParams = [];
if ($catFilter) { $tWhere .= ' AND dt.category_id=?'; $tParams[] = $catFilter; }

$templates = db()->prepare("SELECT dt.*, dc.name as cat_name
    FROM doc_templates dt JOIN doc_categories dc ON dt.category_id=dc.id
    $tWhere ORDER BY dc.sort_order, dt.title");
$templates->execute($tParams);
$templates = $templates->fetchAll(PDO::FETCH_ASSOC);

$catalogue = DocumentEngine::catalogue();
$csrf      = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/documents/index.php">Documents</a></li>
                <li class="breadcrumb-item active">Templates</li>
            </ol>
        </nav>
        <h1 class="page-title">Document Templates</h1>
        <p class="page-subtitle"><?= count($templates) ?> template(s) · <?= count($categories) ?> categories</p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/modules/documents/index.php" class="btn btn-secondary btn-sm">Employee Documents</a>
        <?php if (canCreate('documents.upload')): ?>
        <a href="?action=new" class="btn btn-primary btn-sm">New Template</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'new' || $action === 'edit'): ?>
<!-- Template Editor -->
<div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start;">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><?= $editTemplate ? 'Edit Template: '.e($editTemplate['title']) : 'New Template' ?></span>
        </div>
        <form method="POST" style="padding:20px;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="post_action" value="save_template">
            <?php if ($editTemplate): ?>
            <input type="hidden" name="edit_id" value="<?= $editTemplate['id'] ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category <span class="required">*</span></label>
                    <select class="form-select" name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($editTemplate['category_id']??0)==$c['id']?'selected':'' ?>>
                            <?= e($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Template Title <span class="required">*</span></label>
                    <input type="text" class="form-control" name="title" required
                        value="<?= e($editTemplate['title'] ?? '') ?>" placeholder="e.g. Employment Offer Letter">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" class="form-control" name="description"
                    value="<?= e($editTemplate['description'] ?? '') ?>" placeholder="Brief description of when this template is used">
            </div>
            <div class="form-group">
                <label class="form-label" style="display:flex;justify-content:space-between;">
                    <span>Template Body <span class="required">*</span></span>
                    <span style="font-size:0.68rem;color:var(--text-muted);">Use {{variable.name}} placeholders — see Variable Reference →</span>
                </label>
                <textarea name="body_html" id="templateBody" rows="22"
                    style="width:100%;padding:12px;font-family:monospace;font-size:0.78rem;border:1px solid var(--border);border-radius:8px;background:var(--bg-card);color:var(--text);resize:vertical;"
                    required placeholder="Enter HTML template body here. Use {{employee.full_name}}, {{date.today}}, etc."
                ><?= htmlspecialchars($editTemplate['body_html'] ?? '') ?></textarea>
            </div>
            <!-- Document Configuration Panel -->
            <?php
            $letterheads = db()->query("SELECT id, name, type FROM company_letterheads WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            $signatures  = db()->query("SELECT id, signatory_name, designation, approval_level FROM company_signatures WHERE is_active=1 ORDER BY approval_level")->fetchAll(PDO::FETCH_ASSOC);
            $stamps      = db()->query("SELECT id, name FROM company_stamps WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            $watermarks  = db()->query("SELECT id, name, type, text FROM company_watermarks WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            $tplSigIds   = !empty($editTemplate['signature_ids']) ? json_decode($editTemplate['signature_ids'], true) : [];
            ?>
            <div style="border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:16px;background:var(--bg);">
                <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:12px;">Document Configuration</div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <!-- Letterhead -->
                    <div>
                        <label style="display:flex;align-items:center;gap:6px;font-size:0.78rem;font-weight:600;margin-bottom:6px;cursor:pointer;">
                            <input type="checkbox" name="show_letterhead" <?= !empty($editTemplate['show_letterhead'])?'checked':'' ?>> Use Letterhead
                        </label>
                        <select class="form-select" name="letterhead_id" style="font-size:0.75rem;">
                            <option value="">— None —</option>
                            <?php foreach ($letterheads as $lh): ?>
                            <option value="<?= $lh['id'] ?>" <?= ($editTemplate['letterhead_id']??0)==$lh['id']?'selected':'' ?>><?= e($lh['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Watermark -->
                    <div>
                        <label style="display:flex;align-items:center;gap:6px;font-size:0.78rem;font-weight:600;margin-bottom:6px;cursor:pointer;">
                            <input type="checkbox" name="show_watermark" <?= !empty($editTemplate['show_watermark'])?'checked':'' ?>> Watermark
                        </label>
                        <select class="form-select" name="watermark_id" style="font-size:0.75rem;">
                            <option value="">— None —</option>
                            <?php foreach ($watermarks as $wm): ?>
                            <option value="<?= $wm['id'] ?>" <?= ($editTemplate['watermark_id']??0)==$wm['id']?'selected':'' ?>><?= e($wm['name']) ?> <?= $wm['text']?'('.$wm['text'].')':'' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Stamp -->
                    <div>
                        <label style="display:flex;align-items:center;gap:6px;font-size:0.78rem;font-weight:600;margin-bottom:6px;cursor:pointer;">
                            <input type="checkbox" name="show_stamp" <?= !empty($editTemplate['show_stamp'])?'checked':'' ?>> Company Stamp
                        </label>
                        <select class="form-select" name="stamp_id" style="font-size:0.75rem;">
                            <option value="">— None —</option>
                            <?php foreach ($stamps as $st): ?>
                            <option value="<?= $st['id'] ?>" <?= ($editTemplate['stamp_id']??0)==$st['id']?'selected':'' ?>><?= e($st['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Signatures -->
                    <div>
                        <label style="display:flex;align-items:center;gap:6px;font-size:0.78rem;font-weight:600;margin-bottom:6px;cursor:pointer;">
                            <input type="checkbox" name="show_signature" <?= !empty($editTemplate['show_signature'])?'checked':'' ?>> Signatures
                        </label>
                        <div style="border:1px solid var(--border);border-radius:6px;padding:8px;max-height:100px;overflow-y:auto;">
                            <?php if (empty($signatures)): ?>
                            <div style="font-size:0.7rem;color:var(--text-muted);">No signatures configured</div>
                            <?php else: ?>
                            <?php foreach ($signatures as $sig): ?>
                            <label style="display:flex;align-items:center;gap:6px;font-size:0.72rem;cursor:pointer;margin-bottom:3px;">
                                <input type="checkbox" name="signature_ids[]" value="<?= $sig['id'] ?>"
                                    <?= in_array($sig['id'], $tplSigIds)?'checked':'' ?>>
                                <?= e($sig['signatory_name']) ?> <?= $sig['designation']?'— '.$sig['designation']:'' ?>
                            </label>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Toggle options -->
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;">
                    <?php $toggles = [
                        ['show_qr_code',    'QR Code',     $editTemplate['show_qr_code']    ?? 0],
                        ['show_doc_number', 'Doc Number',  $editTemplate['show_doc_number']  ?? 0],
                        ['show_page_number','Page Number', $editTemplate['show_page_number'] ?? 0],
                        ['show_header',     'Header',      $editTemplate['show_header']      ?? 1],
                        ['show_footer',     'Footer',      $editTemplate['show_footer']      ?? 1],
                        ['requires_approval','Req. Approval', $editTemplate['requires_approval'] ?? 0],
                    ]; ?>
                    <?php foreach ($toggles as [$name,$label,$checked]): ?>
                    <label style="display:flex;align-items:center;gap:5px;font-size:0.75rem;cursor:pointer;padding:4px 6px;border:1px solid var(--border);border-radius:5px;background:var(--bg-card);">
                        <input type="checkbox" name="<?= $name ?>" <?= $checked?'checked':'' ?>> <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Save Template</button>
                <a href="<?= APP_URL ?>/modules/documents/templates.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Variable Reference -->
    <div class="card" style="position:sticky;top:20px;">
        <div class="card-header"><span class="card-title" style="font-size:0.8rem;">Variable Reference</span></div>
        <div style="padding:12px;max-height:calc(100vh - 200px);overflow-y:auto;">
            <?php foreach ($catalogue as $group => $vars): ?>
            <div style="margin-bottom:12px;">
                <div style="font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:6px;"><?= e($group) ?></div>
                <?php foreach ($vars as $key => $label): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid var(--border-light);cursor:pointer;"
                     onclick="insertVar('{{<?= $key ?>>}}')" title="Click to insert">
                    <code style="font-size:0.63rem;color:var(--primary);">{{<?= $key ?>}}</code>
                    <span style="font-size:0.63rem;color:var(--text-muted);"><?= e($label) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function insertVar(varName) {
    const ta = document.getElementById('templateBody');
    const pos = ta.selectionStart;
    ta.value = ta.value.slice(0, pos) + varName.replace('>}}','}}') + ta.value.slice(pos);
    ta.focus();
    ta.setSelectionRange(pos + varName.length - 1, pos + varName.length - 1);
}
</script>

<?php else: ?>
<!-- Template List -->

<!-- Category filter -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
    <a href="?" class="btn btn-<?= !$catFilter?'primary':'secondary' ?> btn-sm">All</a>
    <?php foreach ($categories as $c): ?>
    <a href="?cat=<?= $c['id'] ?>" class="btn btn-<?= $catFilter==$c['id']?'primary':'secondary' ?> btn-sm"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead>
                <tr>
                    <th>Template</th>
                    <th>Category</th>
                    <th>Variables</th>
                    <th>Version</th>
                    <th>Approval</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($templates as $t): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:0.82rem;"><?= e($t['title']) ?></div>
                    <?php if ($t['description']): ?>
                    <div style="font-size:0.7rem;color:var(--text-muted);"><?= e($t['description']) ?></div>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-secondary" style="font-size:0.65rem;"><?= e($t['cat_name']) ?></span></td>
                <td style="font-size:0.72rem;color:var(--text-muted);">
                    <?php $vars = json_decode($t['variables_used'] ?? '[]', true); ?>
                    <?= count($vars) ?> var(s)
                </td>
                <td style="font-size:0.75rem;color:var(--text-muted);">v<?= $t['version'] ?></td>
                <td>
                    <?php if ($t['requires_approval']): ?>
                    <span class="badge badge-warning" style="font-size:0.65rem;">Required</span>
                    <?php else: ?>
                    <span style="font-size:0.72rem;color:var(--text-muted);">None</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($t['is_active']): ?>
                    <span class="badge badge-success">Active</span>
                    <?php else: ?>
                    <span class="badge badge-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <a href="<?= APP_URL ?>/modules/documents/generate.php?template=<?= $t['id'] ?>" class="btn btn-primary btn-sm" style="font-size:0.68rem;">Generate</a>
                        <?php if (canCreate('documents.upload')): ?>
                        <a href="?action=edit&id=<?= $t['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:0.68rem;">Edit</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="post_action" value="toggle_template">
                            <input type="hidden" name="template_id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm" style="font-size:0.68rem;">
                                <?= $t['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($templates)): ?>
            <tr><td colspan="7" class="empty-state">
                No templates yet. <a href="?action=new">Create your first template →</a>
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
