<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('hub.view', 'view');

$pageTitle  = 'Employee Hub';
$activeMenu = 'hub';

$reqTypeLabels = [
    'leave_query'            => 'Leave Query',
    'payslip_query'          => 'Payslip Query',
    'employment_certificate' => 'Employment Certificate',
    'bank_update'            => 'Bank Update',
    'salary_query'           => 'Salary Query',
    'training_request'       => 'Training Request',
    'general_query'          => 'General Query',
    'grievance'              => 'Grievance',
    'payroll_query'          => 'Payroll Query',
    'document_request'       => 'Document Request',
];
$statusLabels  = ['open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed','rejected'=>'Rejected'];
$priorityLabels= ['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'];

$filterStatus   = $_GET['status']   ?? '';
$filterType     = $_GET['type']     ?? '';
$filterPriority = $_GET['priority'] ?? '';
$filterAssigned = isset($_GET['assigned']) ? (int)$_GET['assigned'] : 0;
$search         = trim($_GET['q'] ?? '');

$where  = 'WHERE 1';
$params = [];
if ($filterStatus)   { $where .= ' AND er.status=?';        $params[] = $filterStatus; }
if ($filterType)     { $where .= ' AND er.request_type=?';  $params[] = $filterType; }
if ($filterPriority) { $where .= ' AND er.priority=?';      $params[] = $filterPriority; }
if ($filterAssigned) { $where .= ' AND er.assigned_to=?';   $params[] = $filterAssigned; }
if ($search) {
    $where .= ' AND (er.subject LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_number LIKE ?)';
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like);
}

$stmt = db()->prepare("SELECT er.*, e.first_name, e.last_name, e.employee_number,
    u.username as assigned_name
    FROM employee_requests er
    JOIN employees e ON er.employee_id=e.id
    LEFT JOIN users u ON er.assigned_to=u.id
    $where ORDER BY
        CASE er.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
        er.created_at DESC");
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts
$counts = [];
$countStmt = db()->query("SELECT status, COUNT(*) as cnt FROM employee_requests GROUP BY status");
foreach ($countStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $counts[$row['status']] = $row['cnt'];
}
$totalOpen = ($counts['open']??0) + ($counts['in_progress']??0);

// HR Users for assign dropdown
$hrUsers = db()->query("SELECT id, username FROM users WHERE role IN ('hr_manager','hr_officer','super_admin') AND is_active=1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper">
<div class="content-header">
    <div>
        <h1 class="page-title">Employee Request Hub</h1>
        <p class="page-sub">Manage and respond to employee requests</p>
    </div>
    <?php if ($totalOpen > 0): ?>
    <span class="badge badge-danger" style="font-size:0.82rem;padding:6px 14px">
        <?= $totalOpen ?> Active Request<?= $totalOpen!==1?'s':'' ?>
    </span>
    <?php endif; ?>
</div>

<!-- Status KPIs -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr)">
    <?php
    $kpis = [
        'open'        => ['Open',        $counts['open']??0],
        'in_progress' => ['In Progress', $counts['in_progress']??0],
        'resolved'    => ['Resolved',    $counts['resolved']??0],
        'closed'      => ['Closed',      $counts['closed']??0],
        'rejected'    => ['Rejected',    $counts['rejected']??0],
    ];
    foreach ($kpis as $s=>[$label,$cnt]): ?>
    <div class="stat-card">
        <div class="stat-label"><?=$label?></div>
        <div class="stat-value"><?=$cnt?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card-filters">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name / subject / emp no." value="<?= e($search) ?>" style="max-width:220px">
        <select name="status" class="form-control form-control-sm">
            <option value="">All Status</option>
            <?php foreach($statusLabels as $v=>$l): ?>
            <option value="<?=$v?>" <?=$filterStatus===$v?'selected':''?>><?=$l?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="form-control form-control-sm">
            <option value="">All Types</option>
            <?php foreach($reqTypeLabels as $v=>$l): ?>
            <option value="<?=$v?>" <?=$filterType===$v?'selected':''?>><?=$l?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority" class="form-control form-control-sm">
            <option value="">All Priority</option>
            <?php foreach($priorityLabels as $v=>$l): ?>
            <option value="<?=$v?>" <?=$filterPriority===$v?'selected':''?>><?=$l?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="?" class="btn btn-secondary btn-sm">Reset</a>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Requests (<?= count($requests) ?>)</span>
    </div>
    <?php if ($requests): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($requests as $r): ?>
                <?php
                $pc=['low'=>'badge-secondary','normal'=>'badge-info','high'=>'badge-warning','urgent'=>'badge-danger'];
                $sc=['open'=>'badge-primary','in_progress'=>'badge-warning','resolved'=>'badge-success','closed'=>'badge-secondary','rejected'=>'badge-danger'];
                ?>
                <tr>
                    <td style="font-family:monospace;font-size:0.72rem"><?= str_pad($r['id'],6,'0',STR_PAD_LEFT) ?></td>
                    <td>
                        <div style="font-weight:600"><?= e($r['first_name'].' '.$r['last_name']) ?></div>
                        <div style="font-size:0.7rem;color:var(--text-muted)"><?= e($r['employee_number']) ?></div>
                    </td>
                    <td><?= e($r['subject']) ?></td>
                    <td style="font-size:0.75rem"><?= $reqTypeLabels[$r['request_type']]??$r['request_type'] ?></td>
                    <td><span class="badge <?= $pc[$r['priority']]??'badge-secondary' ?>"><?= $priorityLabels[$r['priority']]??$r['priority'] ?></span></td>
                    <td><span class="badge <?= $sc[$r['status']]??'badge-secondary' ?>"><?= $statusLabels[$r['status']]??$r['status'] ?></span></td>
                    <td style="font-size:0.75rem"><?= $r['assigned_name'] ? e($r['assigned_name']) : '<span style="color:var(--text-muted)">Unassigned</span>' ?></td>
                    <td style="font-size:0.72rem;color:var(--text-muted)"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/modules/hub/view.php?id=<?=$r['id']?>" class="btn btn-ghost btn-sm">Respond</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">💬</div>
        <div>No requests match your filter</div>
    </div>
    <?php endif; ?>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
