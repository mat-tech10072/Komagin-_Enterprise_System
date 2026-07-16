<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('performance.view', 'view');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: '.APP_URL.'/modules/performance/index.php'); exit; }

$review = db()->prepare("SELECT pr.*,
    CONCAT(e.first_name,' ',e.last_name) as employee_name, e.employee_number, e.photo,
    d.name as department, p.title as position,
    CONCAT(r.first_name,' ',r.last_name) as reviewer_name
    FROM performance_reviews pr
    JOIN employees e ON pr.employee_id=e.id
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN positions p ON e.position_id=p.id
    LEFT JOIN employees r ON pr.reviewer_id=r.id
    WHERE pr.id=?");
$review->execute([$id]); $review = $review->fetch(PDO::FETCH_ASSOC);
if (!$review) { setFlash('error','Review not found.'); header('Location: '.APP_URL.'/modules/performance/index.php'); exit; }

// Handle status update / completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    requirePermission('performance.approve', 'approve');
    $newStatus = $_POST['new_status'] ?? '';
    if (in_array($newStatus, ['submitted','completed'])) {
        db()->prepare("UPDATE performance_reviews SET status=?, updated_at=NOW() WHERE id=?")
            ->execute([$newStatus, $id]);
        auditLog('performance','update_review_status',$id,null,$newStatus);
        setFlash('success','Review status updated to '.ucfirst($newStatus).'.');
        header('Location: '.APP_URL.'/modules/performance/view.php?id='.$id); exit;
    }
}

$pageTitle  = 'Performance Review — '.$review['employee_name'];
$activeMenu = 'performance';
$csrf = generateCsrfToken();

$statusColors = ['draft'=>'secondary','submitted'=>'warning','completed'=>'success'];
$recColors = ['promote'=>'success','salary_review'=>'info','training'=>'warning','warning'=>'danger','no_action'=>'secondary'];
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/performance/index.php">Performance</a></li>
                <li class="breadcrumb-item active"><?= e($review['employee_name']) ?></li>
            </ol>
        </nav>
        <h1 class="page-title">Performance Review</h1>
        <p class="page-subtitle"><?= e($review['review_period'] ?? '') ?> · Reviewed by <?= e($review['reviewer_name']) ?></p>
    </div>
    <div class="page-actions">
        <span class="badge badge-<?= $statusColors[$review['status']] ?? 'secondary' ?>" style="font-size:0.78rem;padding:6px 12px;"><?= ucfirst($review['status']) ?></span>
        <?php if ($review['status'] === 'submitted' && canApprove('performance.approve')): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="new_status" value="completed">
            <button type="submit" class="btn btn-success btn-sm">Mark Completed</button>
        </form>
        <?php elseif ($review['status'] === 'draft' && canApprove('performance.approve')): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="new_status" value="submitted">
            <button type="submit" class="btn btn-primary btn-sm">Submit Review</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Employee Banner -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="display:flex;align-items:center;gap:16px;padding:16px 20px;">
        <div style="width:52px;height:52px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;color:var(--primary);">
            <?= strtoupper(substr($review['employee_name'],0,1)) ?>
        </div>
        <div style="flex:1;">
            <div style="font-weight:700;font-size:1rem;"><?= e($review['employee_name']) ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted);"><?= e($review['position']??'—') ?> · <?= e($review['department']??'—') ?> · <?= e($review['employee_number']) ?></div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:1.8rem;font-weight:800;color:<?= $review['overall_score']>=4?'var(--success)':($review['overall_score']>=3?'var(--warning)':'var(--danger)') ?>;"><?= $review['overall_score'] ?? '—' ?></div>
            <div style="font-size:0.7rem;color:var(--text-muted);">Overall Score (5)</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

    <div class="card">
        <div class="card-header"><span class="card-title">Review Details</span></div>
        <div class="card-body">
            <?php $meta = [
                ['Review Period',   $review['review_period'] ?? '—'],
                ['Review Date',     formatDate($review['review_date'])],
                ['Reviewer',        $review['reviewer_name']],
                ['Overall Score',   ($review['overall_score'] ?? '—') . ' / 5'],
                ['Recommendation',  $review['recommendation'] ? ucwords(str_replace('_',' ',$review['recommendation'])) : '—'],
                ['Status',          ucfirst($review['status'])],
            ]; ?>
            <?php foreach ($meta as [$l,$v]): ?>
            <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-light);font-size:0.78rem;">
                <span style="color:var(--text-muted);"><?= $l ?></span>
                <span style="font-weight:500;"><?= e($v) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($review['recommendation']): ?>
    <div class="card">
        <div class="card-header"><span class="card-title">Recommendation</span></div>
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <span class="badge badge-<?= $recColors[$review['recommendation']] ?? 'secondary' ?>" style="font-size:0.85rem;padding:8px 16px;">
                    <?= ucwords(str_replace('_',' ',$review['recommendation'])) ?>
                </span>
            </div>
            <?php if ($review['recommendation_notes']): ?>
            <p style="font-size:0.82rem;"><?= e($review['recommendation_notes']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
    <?php if ($review['self_assessment']): ?>
    <div class="card">
        <div class="card-header"><span class="card-title">Self Assessment</span></div>
        <div class="card-body"><p style="font-size:0.82rem;line-height:1.7;"><?= nl2br(e($review['self_assessment'])) ?></p></div>
    </div>
    <?php endif; ?>

    <?php if ($review['supervisor_assessment']): ?>
    <div class="card">
        <div class="card-header"><span class="card-title">Supervisor Assessment</span></div>
        <div class="card-body"><p style="font-size:0.82rem;line-height:1.7;"><?= nl2br(e($review['supervisor_assessment'])) ?></p></div>
    </div>
    <?php endif; ?>
</div>

<?php if ($review['strengths'] || $review['improvements']): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
    <?php if ($review['strengths']): ?>
    <div class="card">
        <div class="card-header"><span class="card-title">Strengths</span></div>
        <div class="card-body"><p style="font-size:0.82rem;line-height:1.7;"><?= nl2br(e($review['strengths'])) ?></p></div>
    </div>
    <?php endif; ?>
    <?php if ($review['improvements']): ?>
    <div class="card">
        <div class="card-header"><span class="card-title">Areas for Improvement</span></div>
        <div class="card-body"><p style="font-size:0.82rem;line-height:1.7;"><?= nl2br(e($review['improvements'])) ?></p></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
