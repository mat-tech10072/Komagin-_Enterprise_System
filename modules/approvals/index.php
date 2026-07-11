<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';
require_once dirname(dirname(__DIR__)) . '/config/ApprovalEngine.php';

// Approvals is a per-stage workflow inbox, not a single-permission feature —
// a supervisor, hr_manager, hr_officer, payroll_officer, and payroll_manager
// can each be the assigned approver for different workflow types, so the page
// itself is reachable by any authenticated user (to see work assigned to
// them). Authorization for the actual approve/reject action is enforced by
// ApprovalEngine::act() itself (role/assignee match, workflow state, stage
// state, separation of duties) — the engine, not this page, is the
// authorization boundary, consistent with record-level checks belonging
// next to the record rather than the page that happens to display it.
requireLogin();

$pageTitle  = 'Approvals';
$activeMenu = 'approvals';

$engine = new ApprovalEngine(db());
$role   = $_SESSION['user_role'];
$userId = (int)$_SESSION['user_id'];

// Handle approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $wfId    = (int)($_POST['workflow_id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    $comments= trim($_POST['comments'] ?? '');

    if ($wfId && in_array($action, ['approve','reject'], true)) {
        try {
            $engine->act($wfId, $userId, $role, $action, $comments);
            setFlash('success', 'Workflow '.ucfirst($action).'d successfully.');
        } catch (ApprovalAuthorizationException $e) {
            setFlash('error', $e->getMessage());
        }
    }
    header('Location: ' . APP_URL . '/modules/approvals/index.php'); exit;
}

// Load pending approvals for this user
$myPending = $engine->getPendingForUser($userId, $role);

// Load all workflows (admin/hr view)
$filterType   = $_GET['type']   ?? '';
$filterStatus = $_GET['status'] ?? 'pending';
$allWorkflows = [];
if (canView('approvals.manage_all')) {
    $allWorkflows = $engine->getAll(['type'=>$filterType ?: null, 'status'=>$filterStatus ?: null]);
}

$workflowTypes  = ApprovalEngine::workflowConfig();
$statusColors   = ['pending'=>'warning','in_review'=>'info','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary','withdrawn'=>'secondary'];
$priorityColors = ['low'=>'secondary','normal'=>'info','high'=>'warning','urgent'=>'danger'];
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Approval Workflows</h1>
        <p class="page-subtitle">Manage and action pending approvals</p>
    </div>
</div>

<!-- My Pending Approvals -->
<?php if ($myPending): ?>
<div class="card" style="margin-bottom:20px;border-left:4px solid #64748B;">
    <div class="card-header">
        <span class="card-title">Awaiting My Action</span>
        <span class="badge badge-warning"><?= count($myPending) ?></span>
    </div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead><tr><th>Type</th><th>Title</th><th>Employee</th><th>Stage</th><th>Priority</th><th>Raised</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($myPending as $wf): ?>
            <tr>
                <td>
                    <span class="badge badge-secondary" style="font-size:0.65rem;"><?= e($workflowTypes[$wf['workflow_type']]['label'] ?? ucfirst($wf['workflow_type'])) ?></span>
                </td>
                <td style="font-weight:600;font-size:0.82rem;"><?= e($wf['title']) ?></td>
                <td style="font-size:0.78rem;">
                    <?php if ($wf['employee_name']): ?>
                    <div><?= e($wf['employee_name']) ?></div>
                    <div class="emp-num"><?= e($wf['employee_number']) ?></div>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td style="font-size:0.75rem;color:var(--text-muted);">
                    <?= e($wf['stage_name']) ?><br>
                    <small>Stage <?= $wf['current_stage'] ?>/<?= $wf['total_stages'] ?></small>
                </td>
                <td><span class="badge badge-<?= $priorityColors[$wf['priority']] ?? 'secondary' ?>"><?= ucfirst($wf['priority']) ?></span></td>
                <td style="font-size:0.72rem;"><?= formatDateTime($wf['created_at']) ?></td>
                <td>
                    <button class="btn btn-success btn-sm" style="font-size:0.68rem;" onclick="actWorkflow(<?= $wf['id'] ?>,'approve')">Approve</button>
                    <button class="btn btn-danger btn-sm" style="font-size:0.68rem;" onclick="actWorkflow(<?= $wf['id'] ?>,'reject')">Reject</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div style="padding:16px 20px;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;margin-bottom:20px;font-size:0.82rem;color:var(--text-muted);">
    No pending approvals require your action at this time.
</div>
<?php endif; ?>

<!-- All Workflows (HR Manager / Super Admin) -->
<?php if (canView('approvals.manage_all')): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">All Workflows</span>
        <div style="display:flex;gap:8px;align-items:center;">
            <select onchange="window.location='?type='+this.value+'&status=<?= urlencode($filterStatus) ?>'" style="padding:4px 8px;font-size:0.72rem;border-radius:4px;border:1px solid var(--border);">
                <option value="">All Types</option>
                <?php foreach ($workflowTypes as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $filterType===$k?'selected':'' ?>><?= $v['label'] ?></option>
                <?php endforeach; ?>
            </select>
            <select onchange="window.location='?type=<?= urlencode($filterType) ?>&status='+this.value" style="padding:4px 8px;font-size:0.72rem;border-radius:4px;border:1px solid var(--border);">
                <option value="">All Statuses</option>
                <?php foreach (['pending'=>'Pending','in_review'=>'In Review','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'] as $k=>$l): ?>
                <option value="<?= $k ?>" <?= $filterStatus===$k?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead><tr><th>Type</th><th>Title</th><th>Employee</th><th>Status</th><th>Stage</th><th>Priority</th><th>Initiated</th></tr></thead>
            <tbody>
            <?php foreach ($allWorkflows as $wf): ?>
            <tr>
                <td><span class="badge badge-secondary" style="font-size:0.65rem;"><?= e($workflowTypes[$wf['workflow_type']]['label'] ?? ucfirst($wf['workflow_type'])) ?></span></td>
                <td style="font-weight:600;font-size:0.8rem;"><?= e($wf['title']) ?></td>
                <td style="font-size:0.78rem;"><?= e($wf['employee_name'] ?? '—') ?></td>
                <td><span class="badge badge-<?= $statusColors[$wf['status']] ?? 'secondary' ?>"><?= ucwords(str_replace('_',' ',$wf['status'])) ?></span></td>
                <td style="font-size:0.72rem;color:var(--text-muted);"><?= $wf['current_stage'] ?>/<?= $wf['total_stages'] ?></td>
                <td><span class="badge badge-<?= $priorityColors[$wf['priority']] ?? 'secondary' ?>" style="font-size:0.65rem;"><?= ucfirst($wf['priority']) ?></span></td>
                <td style="font-size:0.72rem;"><?= formatDate($wf['created_at']) ?><br><span style="color:var(--text-muted);"><?= e($wf['initiated_by_name']??'System') ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($allWorkflows)): ?>
            <tr><td colspan="7" class="empty-state">No workflows found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Action Modal -->
<div class="modal-overlay" id="actionModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title" id="actionTitle">Approve / Reject</h5>
            <button class="modal-close" data-modal-close="actionModal">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="workflow_id" id="actionWorkflowId" value="">
            <input type="hidden" name="action" id="actionType" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Comments <span style="font-size:0.7rem;color:var(--text-muted);">(optional)</span></label>
                    <textarea class="form-control" name="comments" rows="3" placeholder="Add any notes or reasons..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="actionModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="actionSubmitBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function actWorkflow(id, action) {
    document.getElementById('actionWorkflowId').value = id;
    document.getElementById('actionType').value = action;
    document.getElementById('actionTitle').textContent = (action === 'approve' ? 'Approve' : 'Reject') + ' Workflow';
    document.getElementById('actionSubmitBtn').textContent = action === 'approve' ? 'Confirm Approval' : 'Confirm Rejection';
    document.getElementById('actionSubmitBtn').className = 'btn ' + (action === 'approve' ? 'btn-success' : 'btn-danger');
    document.getElementById('actionModal').classList.add('active');
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
