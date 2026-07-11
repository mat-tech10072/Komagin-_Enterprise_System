<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('audit.view');

$pageTitle  = 'Audit Logs';
$activeMenu = 'audit';

$module  = $_GET['module'] ?? '';
$action  = $_GET['action'] ?? '';
$userId  = (int)($_GET['user'] ?? 0);
$dateFrom= $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo  = $_GET['to']   ?? date('Y-m-d');
$page    = max(1,(int)($_GET['page'] ?? 1));
$perPage = 40;

$where  = ['a.created_at BETWEEN ? AND ?'];
$params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
if ($module) { $where[] = 'a.module = ?'; $params[] = $module; }
if ($action) { $where[] = 'a.action = ?'; $params[] = $action; }
if ($userId) { $where[] = 'a.user_id = ?'; $params[] = $userId; }

$whereSQL = implode(' AND ', $where);
$countStmt = db()->prepare("SELECT COUNT(*) FROM audit_logs a WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total,$perPage,$page);

$stmt = db()->prepare("SELECT a.* FROM audit_logs a WHERE $whereSQL ORDER BY a.created_at DESC LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$modules = db()->query("SELECT DISTINCT module FROM audit_logs ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);
$users   = db()->query("SELECT id, username FROM users ORDER BY username")->fetchAll();

$actionColors = [
    'login' => 'success', 'logout' => 'secondary',
    'create' => 'primary', 'edit' => 'warning',
    'delete' => 'danger', 'approve' => 'success',
    'reject' => 'danger', 'password_change' => 'warning',
];
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Audit Logs</h1>
        <p class="page-subtitle">Complete audit trail of all system actions</p>
    </div>
</div>

<div class="card">
    <div class="filters-bar">
        <form method="GET" style="display:contents;">
            <input type="date" class="form-control" name="from" value="<?= e($dateFrom) ?>">
            <span style="font-size:0.72rem;color:var(--text-muted);">to</span>
            <input type="date" class="form-control" name="to" value="<?= e($dateTo) ?>">
            <select class="form-select" name="module" style="width:auto;">
                <option value="">All Modules</option>
                <?php foreach ($modules as $m): ?>
                    <option value="<?= e($m) ?>" <?= $module===$m ? 'selected' : '' ?>><?= e(ucfirst($m)) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="user" style="width:auto;">
                <option value="">All Users</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $userId==$u['id'] ? 'selected' : '' ?>><?= e($u['username']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
        <div style="margin-left:auto;font-size:0.72rem;color:var(--text-muted);"><?= $total ?> records</div>
    </div>

    <div class="card-body" style="padding:20px 24px;">
        <?php if (empty($logs)): ?>
        <div class="empty-state">
            <div class="empty-state-title">No audit records found</div>
            <div class="empty-state-desc">No activity matches your filters.</div>
        </div>
        <?php else: ?>
        <div class="timeline">
            <?php foreach ($logs as $log):
                $aColor = $actionColors[$log['action']] ?? 'primary';
            ?>
            <div class="timeline-item">
                <div class="timeline-dot" style="background:var(--<?= $aColor ?>);"></div>
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
                    <div style="flex:1;">
                        <div class="timeline-meta">
                            <strong><?= e($log['user_name'] ?? 'System') ?></strong>
                            · <?= e(ucfirst($log['module'])) ?>
                            · <?= formatDateTime($log['created_at']) ?>
                            <?php if ($log['ip_address']): ?>
                                <span style="opacity:.6;"> · <?= e($log['ip_address']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-title" style="margin-top:3px;">
                            <span class="badge badge-<?= $aColor ?>"><?= e(ucfirst(str_replace('_',' ',$log['action']))) ?></span>
                            <?php if ($log['record_id']): ?>
                                <span style="font-size:0.72rem;color:var(--text-muted);"> ID #<?= $log['record_id'] ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($log['reason']): ?>
                            <div class="timeline-desc"><?= e($log['reason']) ?></div>
                        <?php endif; ?>
                        <?php if ($log['old_value'] || $log['new_value']): ?>
                        <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;">
                            <?php if ($log['old_value']): ?>
                                <span class="diff-old">Before: <?= e(mb_strimwidth($log['old_value'], 0, 80, '…')) ?></span>
                            <?php endif; ?>
                            <?php if ($log['new_value']): ?>
                                <span class="diff-new">After: <?= e(mb_strimwidth($log['new_value'], 0, 80, '…')) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:0.72rem;color:var(--text-muted);">Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></span>
        <ul class="pagination">
            <?php for ($i=max(1,$page-2);$i<=min($pagination['total_pages'],$page+2);$i++): ?>
            <li class="page-item <?= $i===$page?'active':'' ?>">
                <a class="page-link" href="?page=<?= $i ?>&from=<?= $dateFrom ?>&to=<?= $dateTo ?>&module=<?= urlencode($module) ?>&user=<?= $userId ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
