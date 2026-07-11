<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('disciplinary.view');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: '.APP_URL.'/modules/disciplinary/index.php'); exit; }

$case = db()->prepare("SELECT dr.*,
    CONCAT(e.first_name,' ',e.last_name) as employee_name, e.employee_number,
    d.name as department, p.title as position,
    CONCAT(h.first_name,' ',h.last_name) as hr_officer_name
    FROM disciplinary_records dr
    JOIN employees e ON dr.employee_id=e.id
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN positions p ON e.position_id=p.id
    LEFT JOIN employees h ON dr.hr_officer_id=h.id
    WHERE dr.id=?");
$case->execute([$id]); $case = $case->fetch(PDO::FETCH_ASSOC);
if (!$case) { setFlash('error','Case not found.'); header('Location: '.APP_URL.'/modules/disciplinary/index.php'); exit; }

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    requirePermission('disciplinary.close', 'approve');
    $newStatus = $_POST['new_status'] ?? '';
    $notes     = trim($_POST['notes'] ?? '');
    if (in_array($newStatus, ['open','investigating','closed','appealed'])) {
        db()->prepare("UPDATE disciplinary_records SET status=?, investigation_notes=CONCAT(IFNULL(investigation_notes,''), ?), updated_at=NOW() WHERE id=?")
            ->execute([$newStatus, $notes ? "\n\n[".date('d M Y')."] ".$notes : '', $id]);
        auditLog('disciplinary','update_case_status',$id,null,$newStatus);
        setFlash('success','Case status updated.');
        header('Location: '.APP_URL.'/modules/disciplinary/view.php?id='.$id); exit;
    }
}

$pageTitle  = 'Disciplinary Case — '.$case['case_number'];
$activeMenu = 'disciplinary';
$csrf = generateCsrfToken();

$statusColors = ['open'=>'danger','investigating'=>'warning','closed'=>'secondary','appealed'=>'info'];
$actionColors = [
    'verbal_warning'=>'warning','written_warning'=>'warning','final_warning'=>'danger',
    'suspension'=>'danger','demotion'=>'warning','termination'=>'danger','dismissed'=>'danger',
    'no_action'=>'secondary'
];
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/disciplinary/index.php">Disciplinary</a></li>
                <li class="breadcrumb-item active"><?= e($case['case_number'] ?? 'Case #'.$id) ?></li>
            </ol>
        </nav>
        <h1 class="page-title">Disciplinary Case <?= e($case['case_number'] ?? '') ?></h1>
        <p class="page-subtitle"><?= e($case['employee_name']) ?> · <?= formatDate($case['incident_date']) ?></p>
    </div>
    <div class="page-actions">
        <span class="badge badge-<?= $statusColors[$case['status']] ?? 'secondary' ?>" style="font-size:0.78rem;padding:6px 12px;">
            <?= ucfirst($case['status']) ?>
        </span>
        <?php if (canApprove('disciplinary.close')): ?>
        <button class="btn btn-secondary btn-sm" data-modal-open="updateStatusModal">Update Status</button>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;">
    <div>
        <!-- Case Summary -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Case Summary</span></div>
            <div class="card-body">
                <?php $meta = [
                    ['Case Number',     $case['case_number'] ?? '—'],
                    ['Employee',        $case['employee_name'].' ('.$case['employee_number'].')'],
                    ['Department',      $case['department'] ?? '—'],
                    ['Position',        $case['position'] ?? '—'],
                    ['Incident Date',   formatDate($case['incident_date'])],
                    ['Case Type',       ucwords(str_replace('_',' ',$case['case_type']))],
                    ['HR Officer',      $case['hr_officer_name'] ?? '—'],
                    ['Hearing Date',    $case['hearing_date'] ? formatDate($case['hearing_date']) : '—'],
                    ['Resolved',        $case['resolved_at'] ? formatDate($case['resolved_at']) : '—'],
                ]; ?>
                <?php foreach ($meta as [$l,$v]): ?>
                <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-light);font-size:0.78rem;">
                    <span style="color:var(--text-muted);"><?= $l ?></span>
                    <span style="font-weight:500;"><?= e($v) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Incident Description</span></div>
            <div class="card-body"><p style="font-size:0.82rem;line-height:1.7;"><?= nl2br(e($case['incident_description'])) ?></p></div>
        </div>

        <?php if ($case['investigation_notes']): ?>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Investigation Notes</span></div>
            <div class="card-body"><p style="font-size:0.82rem;line-height:1.7;"><?= nl2br(e($case['investigation_notes'])) ?></p></div>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><span class="card-title">Action Taken</span></div>
            <div class="card-body">
                <?php if ($case['action_taken']): ?>
                <span class="badge badge-<?= $actionColors[$case['action_taken']] ?? 'secondary' ?>" style="font-size:0.82rem;padding:8px 14px;">
                    <?= ucwords(str_replace('_',' ',$case['action_taken'])) ?>
                </span>
                <?php else: ?>
                <span style="font-size:0.78rem;color:var(--text-muted);">Pending determination</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($case['evidence_file'] || $case['warning_letter_file']): ?>
        <div class="card">
            <div class="card-header"><span class="card-title">Attachments</span></div>
            <div class="card-body">
                <?php if ($case['evidence_file']): ?>
                <a href="<?= APP_URL ?>/<?= e($case['evidence_file']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display:block;margin-bottom:8px;">Evidence File</a>
                <?php endif; ?>
                <?php if ($case['warning_letter_file']): ?>
                <a href="<?= APP_URL ?>/<?= e($case['warning_letter_file']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display:block;">Warning Letter</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Status Modal -->
<?php if (canApprove('disciplinary.close')): ?>
<div class="modal-overlay" id="updateStatusModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Update Case Status</h5>
            <button class="modal-close" data-modal-close="updateStatusModal">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">New Status</label>
                    <select class="form-select" name="new_status" required>
                        <?php foreach (['open'=>'Open','investigating'=>'Investigating','closed'=>'Closed','appealed'=>'Appealed'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $case['status']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Additional Notes</label>
                    <textarea class="form-control" name="notes" rows="4" placeholder="Notes to append to investigation record..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="updateStatusModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
