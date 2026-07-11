<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';
require_once dirname(dirname(__DIR__)) . '/config/DocumentEngine.php';

requireLogin();
requirePermission('documents.view');

$pageTitle  = 'Generate Document';
$activeMenu = 'documents';

$templateId = (int)($_GET['template'] ?? $_POST['template_id'] ?? 0);
$empId      = (int)($_GET['emp']      ?? $_POST['employee_id'] ?? 0);

// Load template
$tpl = null;
if ($templateId) {
    $st = db()->prepare("SELECT dt.*, dc.name as cat_name FROM doc_templates dt JOIN doc_categories dc ON dt.category_id=dc.id WHERE dt.id=? AND dt.is_active=1");
    $st->execute([$templateId]);
    $tpl = $st->fetch(PDO::FETCH_ASSOC);
}

// Load employee list for selection
$employees = db()->query("SELECT id, employee_number, first_name, last_name, department_id FROM employees WHERE status IN ('active','probation') ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);

// Categories and templates for selector
$allCategories = db()->query("SELECT dc.*, GROUP_CONCAT(dt.id,'|',dt.title ORDER BY dt.title SEPARATOR ';;') as templates_list
    FROM doc_categories dc LEFT JOIN doc_templates dt ON dt.category_id=dc.id AND dt.is_active=1
    WHERE dc.is_active=1 GROUP BY dc.id ORDER BY dc.sort_order")->fetchAll(PDO::FETCH_ASSOC);

// ── Generate / Save ───────────────────────────────────────────────────────
$previewHtml = '';
$generatedId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $postAction = $_POST['post_action'] ?? '';

    if ($postAction === 'preview' || $postAction === 'save') {
        $tplId = (int)($_POST['template_id'] ?? 0);
        $eId   = (int)($_POST['employee_id'] ?? 0);
        $extra = []; // extra overrides from form

        $tplRow = db()->prepare("SELECT * FROM doc_templates WHERE id=? AND is_active=1");
        $tplRow->execute([$tplId]);
        $tplRow = $tplRow->fetch(PDO::FETCH_ASSOC);

        $empRow = getEmployee($eId);

        if (!$tplRow || !$empRow) {
            setFlash('error', 'Template or employee not found.');
        } else {
            $settings = getCompanySettings();
            $engine   = new DocumentEngine(db(), $empRow, $settings);
            $rendered = $engine->render($tplRow['body_html'], $extra);
            $docTitle = $engine->render($tplRow['title']);
            // Apply letterhead/sig/stamp/watermark wrapping
            $rendered = $engine->wrapDocument($rendered, $tplRow);

            if ($postAction === 'preview') {
                $previewHtml = $rendered;
                $templateId  = $tplId;
                $empId       = $eId;
                // Re-fetch with category JOIN so cat_name is available in the sidebar
                $tplFull = db()->prepare("SELECT dt.*, dc.name as cat_name FROM doc_templates dt JOIN doc_categories dc ON dt.category_id=dc.id WHERE dt.id=?");
                $tplFull->execute([$tplId]);
                $tpl = $tplFull->fetch(PDO::FETCH_ASSOC) ?: $tplRow;
            } elseif ($postAction === 'save') {
                $status = $tplRow['requires_approval'] ? 'pending_approval' : 'draft';
                db()->prepare("INSERT INTO generated_documents (template_id, employee_id, title, body_html, status, generated_by)
                    VALUES (?,?,?,?,?,?)")
                    ->execute([$tplId, $eId, $docTitle, $rendered, $status, $_SESSION['user_id']]);
                $generatedId = (int)db()->lastInsertId();

                // Also save to employee_documents
                db()->prepare("INSERT INTO employee_documents (employee_id, category, document_name, file_path, uploaded_by, notes)
                    VALUES (?,?,?,?,?,?)")
                    ->execute([$eId, 'other', $docTitle, 'generated:'.$generatedId, $_SESSION['user_id'], 'Auto-generated from template: '.$tplRow['title']]);

                auditLog('documents','generate_document',$generatedId,null,json_encode(['template'=>$tplRow['title'],'employee'=>$empRow['employee_number']]));
                setFlash('success', "Document \"$docTitle\" generated successfully.");

                if ($tplRow['requires_approval']) {
                    setFlash('info', 'This document requires approval before it can be issued.');
                }

                header('Location: ' . APP_URL . '/modules/documents/view_generated.php?id='.$generatedId); exit;
            }
        }
    }
}

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/documents/index.php">Documents</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/documents/templates.php">Templates</a></li>
                <li class="breadcrumb-item active">Generate</li>
            </ol>
        </nav>
        <h1 class="page-title">Generate Document</h1>
        <p class="page-subtitle">Select a template and employee, preview, then save.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start;">

    <!-- Controls -->
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Document Setup</span></div>
            <form method="POST" style="padding:20px;" id="genForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="form-group">
                    <label class="form-label">Template <span class="required">*</span></label>
                    <select class="form-select" name="template_id" id="templateSelect" required onchange="loadTemplate(this.value)">
                        <option value="">Select template</option>
                        <?php foreach ($allCategories as $cat): ?>
                        <?php if (!$cat['templates_list']) continue; ?>
                        <optgroup label="<?= e($cat['name']) ?>">
                            <?php foreach (explode(';;', $cat['templates_list']) as $tEntry):
                                  [$tid, $tname] = explode('|', $tEntry, 2); ?>
                            <option value="<?= $tid ?>" <?= $templateId==$tid?'selected':'' ?>><?= e($tname) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Employee <span class="required">*</span></label>
                    <select class="form-select" name="employee_id" required>
                        <option value="">Select employee</option>
                        <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $empId==$e['id']?'selected':'' ?>>
                            <?= e($e['first_name'].' '.$e['last_name'].' ('.$e['employee_number'].')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($tpl): ?>
                <div style="padding:10px;background:var(--bg);border-radius:6px;border:1px solid var(--border);font-size:0.75rem;margin-bottom:14px;">
                    <strong><?= e($tpl['title']) ?></strong><br>
                    <span style="color:var(--text-muted);"><?= e($tpl['cat_name'] ?? '') ?></span>
                    <?php if ($tpl['requires_approval']): ?>
                    <br><span class="badge badge-warning" style="font-size:0.63rem;margin-top:4px;">Requires Approval</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div style="display:flex;gap:8px;flex-direction:column;">
                    <button type="submit" name="post_action" value="preview" class="btn btn-secondary">
                        Preview Document
                    </button>
                    <?php if ($previewHtml): ?>
                    <button type="submit" name="post_action" value="save" class="btn btn-primary">
                        Save &amp; Generate
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title" style="font-size:0.78rem;">Recently Generated</span></div>
            <?php
            $recent = db()->prepare("SELECT gd.*, e.first_name, e.last_name, dt.title as tpl_title
                FROM generated_documents gd
                JOIN employees e ON gd.employee_id=e.id
                JOIN doc_templates dt ON gd.template_id=dt.id
                ORDER BY gd.generated_at DESC LIMIT 8");
            $recent->execute(); $recentDocs = $recent->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div style="padding:8px 0;">
                <?php foreach ($recentDocs as $rd): ?>
                <a href="<?= APP_URL ?>/modules/documents/view_generated.php?id=<?= $rd['id'] ?>"
                   style="display:block;padding:8px 16px;font-size:0.75rem;border-bottom:1px solid var(--border-light);text-decoration:none;color:var(--text);">
                    <div style="font-weight:600;"><?= e($rd['first_name'].' '.$rd['last_name']) ?></div>
                    <div style="color:var(--text-muted);font-size:0.68rem;"><?= e($rd['tpl_title']) ?> · <?= date('d M Y', strtotime($rd['generated_at'])) ?></div>
                </a>
                <?php endforeach; ?>
                <?php if (empty($recentDocs)): ?>
                <div style="padding:12px 16px;font-size:0.75rem;color:var(--text-muted);">No documents generated yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Preview Pane -->
    <div>
        <?php if ($previewHtml): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Document Preview</span>
                <span style="font-size:0.72rem;color:var(--text-muted);">Review before saving</span>
            </div>
            <div style="padding:32px;background:white;min-height:600px;border-radius:0 0 8px 8px;line-height:1.7;">
                <?= $previewHtml ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card" style="min-height:400px;display:flex;align-items:center;justify-content:center;">
            <div style="text-align:center;color:var(--text-muted);">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="opacity:.3;display:block;margin:0 auto 16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <div style="font-size:0.85rem;">Select a template and employee,<br>then click Preview to see the document.</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
