<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('performance.view', 'view');

$pageTitle  = 'Performance Reviews';
$activeMenu = 'performance';

$yearFilter = (int)($_GET['year'] ?? date('Y'));
$deptFilter = (int)($_GET['dept'] ?? 0);
$page       = max(1,(int)($_GET['page'] ?? 1));
$perPage    = 25;

$where  = ['YEAR(pr.review_date)=?'];
$params = [$yearFilter];
if ($deptFilter) { $where[] = 'e.department_id=?'; $params[] = $deptFilter; }
$whereSQL = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM performance_reviews pr JOIN employees e ON pr.employee_id=e.id WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total,$perPage,$page);

$stmt = db()->prepare("SELECT pr.*, e.first_name, e.last_name, e.employee_number, d.name as dept_name
    FROM performance_reviews pr
    JOIN employees e ON pr.employee_id=e.id
    LEFT JOIN departments d ON e.department_id=d.id
    WHERE $whereSQL ORDER BY pr.review_date DESC LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$statsStmt = db()->prepare("SELECT
    COUNT(*) as total,
    AVG(overall_score) as avg_rating,
    SUM(CASE WHEN pr.status='completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN pr.status='draft' THEN 1 ELSE 0 END) as draft
    FROM performance_reviews pr JOIN employees e ON pr.employee_id=e.id WHERE YEAR(pr.review_date)=?");
$statsStmt->execute([$yearFilter]);
$stats = $statsStmt->fetch();

$departments = getDepartments();
$csrf = generateCsrfToken();

$years = range(date('Y'), max(date('Y')-5, 2020));
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Performance Reviews</h1>
        <p class="page-subtitle">Employee performance appraisals — <?= $yearFilter ?></p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary btn-sm" data-modal-open="addReviewModal">New Review</button>
    </div>
</div>

<!-- Stats -->
<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="kpi-card kpi-primary"><div class="kpi-card-label">Total Reviews</div><div class="kpi-card-value"><?= $stats['total'] ?></div></div>
    <div class="kpi-card kpi-success"><div class="kpi-card-label">Completed</div><div class="kpi-card-value"><?= $stats['completed'] ?></div></div>
    <div class="kpi-card kpi-warning"><div class="kpi-card-label">Drafts</div><div class="kpi-card-value"><?= $stats['draft'] ?></div></div>
    <div class="kpi-card kpi-info"><div class="kpi-card-label">Avg Rating</div><div class="kpi-card-value"><?= $stats['avg_rating'] ? number_format($stats['avg_rating'],1).'/5' : '—' ?></div></div>
</div>

<div class="card">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <select class="form-select" name="year" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $yearFilter===$y?'selected':''?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="dept" style="width:auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':''?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);"><?= $total ?> reviews</div>
    </div>

    <div class="table-wrapper" style="border:none;">
        <?php if (empty($reviews)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No performance reviews for <?= $yearFilter ?></div>
            <div class="empty-state-desc">Create a new review to get started.</div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Employee</th><th>Dept</th><th>Review Period</th><th>Review Date</th><th>Overall Rating</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($reviews as $rev): ?>
            <tr>
                <td>
                    <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $rev['employee_id'] ?>" style="font-weight:600;color:var(--text);font-size:0.78rem;">
                        <?= e($rev['first_name'].' '.$rev['last_name']) ?>
                    </a>
                    <div class="emp-num"><?= e($rev['employee_number']) ?></div>
                </td>
                <td style="font-size:0.72rem;"><?= e($rev['dept_name'] ?? '—') ?></td>
                <td style="font-size:0.75rem;"><?= e($rev['review_period'] ?? '—') ?></td>
                <td style="font-size:0.75rem;"><?= formatDate($rev['review_date']) ?></td>
                <td>
                    <?php if ($rev['overall_score']): ?>
                    <?php $r = $rev['overall_score']; $color = $r>=4?'var(--success)':($r>=3?'var(--warning)':'var(--danger)');?>
                    <span style="font-weight:700;color:<?= $color ?>;"><?= number_format($r,1) ?>/5</span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <?php $sc=['draft'=>'badge-secondary','submitted'=>'badge-info','completed'=>'badge-success'];?>
                    <span class="badge <?= $sc[$rev['status']] ?? 'badge-secondary' ?>"><?= ucfirst($rev['status']) ?></span>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/modules/performance/view.php?id=<?= $rev['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:0.68rem;">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Review Modal -->
<div class="modal-overlay" id="addReviewModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">New Performance Review</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/performance/save.php" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Employee <span class="required">*</span></label>
                        <select class="form-select" name="employee_id" required>
                            <option value="">Select employee</option>
                            <?php
                            $allEmps = db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as n FROM employees WHERE status IN ('active','probation') ORDER BY first_name")->fetchAll();
                            foreach ($allEmps as $ae): ?>
                                <option value="<?= $ae['id'] ?>"><?= e($ae['n']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Review Period</label>
                        <input type="text" class="form-control" name="review_period" placeholder="e.g. Q1 2026 / Annual 2025">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Review Date <span class="required">*</span></label>
                        <input type="date" class="form-control" name="review_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Overall Rating (1–5)</label>
                        <select class="form-select" name="overall_score">
                            <option value="">Select</option>
                            <option value="5">5 — Outstanding</option>
                            <option value="4">4 — Exceeds Expectations</option>
                            <option value="3">3 — Meets Expectations</option>
                            <option value="2">2 — Below Expectations</option>
                            <option value="1">1 — Unsatisfactory</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Comments</label>
                    <textarea class="form-control" name="comments" rows="3"></textarea>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Recommendations</label>
                    <textarea class="form-control" name="recommendations" rows="2" placeholder="Promotions, salary adjustments, training recommendations…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Review</button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
