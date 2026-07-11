<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('documents.view');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/documents/generate.php'); exit; }

$st = db()->prepare("SELECT gd.*, dt.title as tpl_title, dt.requires_approval,
    e.first_name, e.last_name, e.employee_number,
    u1.username as generated_by_name,
    u2.username as approved_by_name,
    u3.username as issued_by_name
    FROM generated_documents gd
    JOIN doc_templates dt ON gd.template_id=dt.id
    JOIN employees e ON gd.employee_id=e.id
    LEFT JOIN users u1 ON gd.generated_by=u1.id
    LEFT JOIN users u2 ON gd.approved_by=u2.id
    LEFT JOIN users u3 ON gd.issued_by=u3.id
    WHERE gd.id=?");
$st->execute([$id]);
$doc = $st->fetch(PDO::FETCH_ASSOC);

if (!$doc) { setFlash('error', 'Document not found.'); header('Location: ' . APP_URL . '/modules/documents/generate.php'); exit; }

// Handle approval/issue actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $act = $_POST['doc_action'] ?? '';
    if ($act === 'approve' && $doc['status'] === 'pending_approval') {
        requirePermission('documents.verify', 'approve');
        db()->prepare("UPDATE generated_documents SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?")
            ->execute([$_SESSION['user_id'], $id]);
        auditLog('documents','approve_document',$id);
        setFlash('success', 'Document approved.');
    } elseif ($act === 'issue' && in_array($doc['status'],['approved','draft'])) {
        requirePermission('documents.upload', 'create');
        db()->prepare("UPDATE generated_documents SET status='issued', issued_by=?, issued_at=NOW() WHERE id=?")
            ->execute([$_SESSION['user_id'], $id]);
        auditLog('documents','issue_document',$id);
        setFlash('success', 'Document marked as issued.');
    } elseif ($act === 'reject') {
        requirePermission('documents.verify', 'approve');
        db()->prepare("UPDATE generated_documents SET status='rejected', approved_by=?, approved_at=NOW() WHERE id=?")
            ->execute([$_SESSION['user_id'], $id]);
        auditLog('documents','reject_document',$id);
        setFlash('error', 'Document rejected.');
    }
    header('Location: ' . APP_URL . '/modules/documents/view_generated.php?id='.$id); exit;
}

$pageTitle  = $doc['title'];
$activeMenu = 'documents';
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/documents/index.php">Documents</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/documents/generate.php">Generate</a></li>
                <li class="breadcrumb-item active"><?= e($doc['title']) ?></li>
            </ol>
        </nav>
        <h1 class="page-title"><?= e($doc['title']) ?></h1>
    </div>
    <div class="page-actions">
        <?php $statusColors = ['draft'=>'secondary','pending_approval'=>'warning','approved'=>'info','rejected'=>'danger','issued'=>'success']; ?>
        <span class="badge badge-<?= $statusColors[$doc['status']] ?? 'secondary' ?>" style="font-size:0.78rem;padding:6px 12px;">
            <?= ucwords(str_replace('_',' ',$doc['status'])) ?>
        </span>
        <button onclick="window.print()" class="btn btn-secondary btn-sm">Print / PDF</button>
        <?php if ($doc['status'] === 'pending_approval' && canApprove('documents.verify')): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="doc_action" value="approve">
            <button type="submit" class="btn btn-success btn-sm">Approve</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="doc_action" value="reject">
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this document?')">Reject</button>
        </form>
        <?php endif; ?>
        <?php if (in_array($doc['status'],['approved','draft']) && canCreate('documents.upload')): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="doc_action" value="issue">
            <button type="submit" class="btn btn-primary btn-sm">Mark as Issued</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 260px;gap:20px;align-items:start;">
    <!-- Document body -->
    <div class="card">
        <div id="documentPrint" style="padding:48px;background:white;min-height:800px;line-height:1.75;font-family:Georgia, serif;font-size:0.9rem;color:#1e293b;">
            <?= $doc['body_html'] ?>
        </div>
    </div>

    <!-- Metadata -->
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Document Info</span></div>
            <div class="card-body">
                <?php $meta = [
                    ['Employee',   $doc['first_name'].' '.$doc['last_name'].' ('.$doc['employee_number'].')'],
                    ['Template',   $doc['tpl_title']],
                    ['Generated',  formatDateTime($doc['generated_at']).' by '.($doc['generated_by_name']??'—')],
                    ['Approved',   $doc['approved_at'] ? formatDateTime($doc['approved_at']).' by '.($doc['approved_by_name']??'—') : '—'],
                    ['Issued',     $doc['issued_at']   ? formatDateTime($doc['issued_at'])  .' by '.($doc['issued_by_name']??'—')   : '—'],
                ]; ?>
                <?php foreach ($meta as [$l,$v]): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light);font-size:0.75rem;">
                    <span style="color:var(--text-muted);"><?= $l ?></span>
                    <span style="font-weight:500;text-align:right;"><?= e($v) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Workflow</span></div>
            <div class="card-body">
                <?php $steps = [
                    'draft'            => ['Draft',           'secondary'],
                    'pending_approval' => ['Pending Approval','warning'],
                    'approved'         => ['Approved',        'info'],
                    'issued'           => ['Issued',          'success'],
                ]; ?>
                <?php foreach ($steps as $sKey => [$sLabel, $sColor]): ?>
                <div style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:0.78rem;">
                    <div style="width:8px;height:8px;border-radius:50%;background:<?= $doc['status']===$sKey?'var(--'.$sColor.')':'var(--border)' ?>;flex-shrink:0;"></div>
                    <span style="<?= $doc['status']===$sKey?'font-weight:700;color:var(--text)':'color:var(--text-muted)' ?>;"><?= $sLabel ?></span>
                    <?php if ($doc['status']===$sKey): ?>
                    <span class="badge badge-<?= $sColor ?>" style="font-size:0.6rem;">Current</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .page-header, .sidebar, .app-wrapper > aside, nav, .tab-nav, .page-actions,
    .content-wrapper > div:last-child { display: none !important; }
    .content-wrapper { padding: 0; }
    #documentPrint { padding: 0; box-shadow: none; }
}
</style>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
