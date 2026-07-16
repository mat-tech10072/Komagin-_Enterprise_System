<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
requirePermission('hub.view', 'view');

$pageTitle  = 'View Request';
$activeMenu = 'hub';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: ' . APP_URL . '/modules/hub/index.php'); exit; }

$stmt = db()->prepare("SELECT er.*, e.first_name, e.last_name, e.employee_number,
    e.department_id, d.name as dept_name, p.title as position_title,
    u.username as assigned_name,
    r.username as resolved_by_name
    FROM employee_requests er
    JOIN employees e ON er.employee_id=e.id
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN positions p ON e.position_id=p.id
    LEFT JOIN users u ON er.assigned_to=u.id
    LEFT JOIN users r ON er.resolved_by=r.id
    WHERE er.id=? LIMIT 1");
$stmt->execute([$id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$req) { header('Location: ' . APP_URL . '/modules/hub/index.php'); exit; }

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'respond') {
            $response    = trim($_POST['hr_response'] ?? '');
            $notes       = trim($_POST['internal_notes'] ?? '');
            $newStatus   = $_POST['status'] ?? $req['status'];
            $assignTo    = (int)($_POST['assigned_to'] ?? 0) ?: null;

            $allowed = ['open','in_progress','resolved','closed','rejected'];
            if (!in_array($newStatus, $allowed)) $newStatus = $req['status'];

            $resolvedAt = in_array($newStatus,['resolved','closed','rejected']) ? date('Y-m-d H:i:s') : null;
            $resolvedBy = $resolvedAt ? $_SESSION['user_id'] : null;

            db()->prepare("UPDATE employee_requests SET
                hr_response=?, internal_notes=?, status=?, assigned_to=?,
                resolved_by=?, resolved_at=?, updated_at=NOW()
                WHERE id=?")->execute([
                $response ?: null, $notes ?: null, $newStatus, $assignTo,
                $resolvedBy, $resolvedAt, $id
            ]);

            auditLog('employee_requests','respond',$id,null,null,"HR responded to request #{$id}, status: {$newStatus}");

            // Re-fetch
            $stmt->execute([$id]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);
            $success = 'Response saved.';
        }
    }
}

$reqTypeLabels = [
    'leave_query'=>'Leave Query','payslip_query'=>'Payslip Query',
    'employment_certificate'=>'Employment Certificate','bank_update'=>'Bank Update',
    'salary_query'=>'Salary Query','training_request'=>'Training Request',
    'general_query'=>'General Query','grievance'=>'Grievance',
    'payroll_query'=>'Payroll Query','document_request'=>'Document Request',
];
$statusLabels  = ['open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed','rejected'=>'Rejected'];
$priorityLabels= ['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'];
$hrUsers = db()->query("SELECT id, username FROM users WHERE role IN ('hr_manager','hr_officer','super_admin') AND is_active=1 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper">
<div class="content-header">
    <div>
        <h1 class="page-title">Request #<?= str_pad($req['id'],6,'0',STR_PAD_LEFT) ?></h1>
        <p class="page-sub"><?= $reqTypeLabels[$req['request_type']]??$req['request_type'] ?> — submitted <?= date('d M Y H:i', strtotime($req['created_at'])) ?></p>
    </div>
    <a href="<?= APP_URL ?>/modules/hub/index.php" class="btn btn-secondary btn-sm">← Back to Hub</a>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
    <div>
        <!-- Request -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= e($req['subject']) ?></span>
                <?php
                $pc=['low'=>'badge-secondary','normal'=>'badge-info','high'=>'badge-warning','urgent'=>'badge-danger'];
                $sc=['open'=>'badge-primary','in_progress'=>'badge-warning','resolved'=>'badge-success','closed'=>'badge-secondary','rejected'=>'badge-danger'];
                ?>
                <span class="badge <?= $pc[$req['priority']]??'badge-secondary' ?>"><?= $priorityLabels[$req['priority']]??$req['priority'] ?></span>
            </div>
            <div class="card-body">
                <p style="font-size:0.85rem;line-height:1.75;white-space:pre-wrap"><?= e($req['description']) ?></p>
            </div>
        </div>

        <!-- Response Form -->
        <?php if (!in_array($req['status'],['resolved','closed','rejected'])): ?>
        <div class="card" style="margin-top:16px">
            <div class="card-header"><span class="card-title">Respond to Request</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="respond">
                    <div class="form-row" style="grid-template-columns:1fr 1fr">
                        <div class="form-group">
                            <label class="form-label">Update Status</label>
                            <select name="status" class="form-control">
                                <?php foreach($statusLabels as $v=>$l): ?>
                                <option value="<?=$v?>" <?=$req['status']===$v?'selected':''?>><?=$l?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">Unassigned</option>
                                <?php foreach($hrUsers as $u): ?>
                                <option value="<?=$u['id']?>" <?=$req['assigned_to']==$u['id']?'selected':''?>><?= e($u['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Response to Employee</label>
                        <textarea name="hr_response" class="form-control" rows="4"
                            placeholder="This response will be visible to the employee in their portal..."><?= e($req['hr_response']??'') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Internal Notes (HR only)</label>
                        <textarea name="internal_notes" class="form-control" rows="2"
                            placeholder="Internal HR notes — not visible to employee"><?= e($req['internal_notes']??'') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Response</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Resolved view -->
        <?php if ($req['hr_response']): ?>
        <div class="card" style="margin-top:16px">
            <div class="card-header" style="background:#F0FDF4;border-bottom-color:#BBF7D0">
                <span class="card-title" style="color:#166534">HR Response (Sent to Employee)</span>
                <?php if ($req['resolved_at']): ?>
                <span style="font-size:0.7rem;color:var(--text-muted)"><?= date('d M Y H:i', strtotime($req['resolved_at'])) ?> by <?= e($req['resolved_by_name']??'') ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p style="font-size:0.85rem;line-height:1.75;white-space:pre-wrap"><?= e($req['hr_response']) ?></p>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($req['internal_notes']): ?>
        <div class="card" style="margin-top:12px">
            <div class="card-header" style="background:#FFFBEB;border-bottom-color:#FDE68A">
                <span class="card-title" style="color:#92400E">Internal Notes (HR Only)</span>
            </div>
            <div class="card-body">
                <p style="font-size:0.82rem;line-height:1.7;white-space:pre-wrap"><?= e($req['internal_notes']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Allow re-opening / further action -->
        <div class="card" style="margin-top:12px">
            <div class="card-body">
                <form method="POST" style="display:flex;gap:8px;align-items:center">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="respond">
                    <input type="hidden" name="status" value="in_progress">
                    <input type="hidden" name="hr_response" value="<?= e($req['hr_response']??'') ?>">
                    <span style="font-size:0.78rem;color:var(--text-muted)">Need to re-open this request?</span>
                    <button type="submit" class="btn btn-secondary btn-sm">Re-open</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Info -->
    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Request Details</span></div>
            <div class="card-body" style="padding:0">
                <?php
                $details = [
                    'Status'     => '<span class="badge '.$sc[$req['status']].'">'.$statusLabels[$req['status']].'</span>',
                    'Priority'   => '<span class="badge '.$pc[$req['priority']].'">'.$priorityLabels[$req['priority']].'</span>',
                    'Type'       => e($reqTypeLabels[$req['request_type']]??$req['request_type']),
                    'Assigned'   => e($req['assigned_name']??'Unassigned'),
                    'Submitted'  => date('d M Y H:i', strtotime($req['created_at'])),
                    'Updated'    => date('d M Y H:i', strtotime($req['updated_at'])),
                ];
                foreach ($details as $k=>$v): ?>
                <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:0.7rem;font-weight:600;text-transform:uppercase;color:var(--text-muted)"><?=$k?></span>
                    <span style="font-size:0.78rem"><?=$v?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="margin-top:12px">
            <div class="card-header"><span class="card-title">Employee</span></div>
            <div class="card-body" style="padding:0">
                <?php
                $empInfo = [
                    'Name'       => e($req['first_name'].' '.$req['last_name']),
                    'Emp No.'    => '<span style="font-family:monospace">'.e($req['employee_number']).'</span>',
                    'Department' => e($req['dept_name']??'—'),
                    'Position'   => e($req['position_title']??'—'),
                ];
                foreach ($empInfo as $k=>$v): ?>
                <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:0.7rem;font-weight:600;text-transform:uppercase;color:var(--text-muted)"><?=$k?></span>
                    <span style="font-size:0.78rem"><?=$v?></span>
                </div>
                <?php endforeach; ?>
                <div style="padding:12px 16px">
                    <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?=$req['employee_id']?>"
                       class="btn btn-ghost btn-sm" style="width:100%;justify-content:center">
                        View Employee Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
