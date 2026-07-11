<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';
epRequireLogin();

$emp   = epCurrentEmployee();
$empId = $emp['id'];

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
$statusLabels = ['open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed','rejected'=>'Rejected'];
$priorityLabels = ['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'];

$success = '';
$error   = '';

$csrf = generateCsrfToken();

// Handle new submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
    $type     = $_POST['request_type'] ?? '';
    $subject  = trim($_POST['subject'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';

    if (!$type || !$subject || !$desc) {
        $error = 'All fields are required.';
    } elseif (!array_key_exists($type, $reqTypeLabels)) {
        $error = 'Invalid request type.';
    } elseif (!in_array($priority, ['low','normal','high','urgent'])) {
        $error = 'Invalid priority.';
    } else {
        db()->prepare("INSERT INTO employee_requests (employee_id, request_type, subject, description, priority)
            VALUES (?,?,?,?,?)")->execute([$empId, $type, $subject, $desc, $priority]);

        $empName   = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
        $typeLabel = $reqTypeLabels[$type] ?? $type;
        $notifTitle = 'New Hub Request: ' . $typeLabel;
        $notifMsg   = $empName . ' submitted a ' . strtolower($typeLabel) . ': ' . $subject;
        $notifLink  = APP_URL . '/modules/hub/index.php';
        notifyRole('hr_manager',  'hub_request', $notifTitle, $notifMsg, $notifLink);
        notifyRole('super_admin', 'hub_request', $notifTitle, $notifMsg, $notifLink);

        $success = 'Your request has been submitted. HR will respond shortly.';
    }
    }
}

// View single request
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$reqDetail = null;
if ($viewId) {
    $stmt = db()->prepare("SELECT * FROM employee_requests WHERE id=? AND employee_id=? LIMIT 1");
    $stmt->execute([$viewId, $empId]);
    $reqDetail = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Filter
$filterStatus = $_GET['status'] ?? '';
$showNew = isset($_GET['new']) || isset($_POST['submit_request']);
$preType = $_GET['type'] ?? '';

// List all requests
$where = 'WHERE er.employee_id=?';
$params = [$empId];
if ($filterStatus && in_array($filterStatus, ['open','in_progress','resolved','closed','rejected'])) {
    $where .= ' AND er.status=?';
    $params[] = $filterStatus;
}
$reqStmt = db()->prepare("SELECT er.*, u.username as assigned_name
    FROM employee_requests er
    LEFT JOIN users u ON er.assigned_to=u.id
    $where ORDER BY er.created_at DESC");
$reqStmt->execute($params);
$requests = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

$openCount = 0; $ipCount = 0;
foreach ($requests as $r) {
    if ($r['status'] === 'open') $openCount++;
    if ($r['status'] === 'in_progress') $ipCount++;
}

epLayoutStart('Request Hub', 'hub');
?>

<?php if ($reqDetail): ?>
<!-- Detail View -->
<div class="page-header">
    <div>
        <div class="page-title"><?= htmlspecialchars($reqDetail['subject']) ?></div>
        <div class="page-sub">Request #<?= str_pad($reqDetail['id'],6,'0',STR_PAD_LEFT) ?> &mdash; <?= $reqTypeLabels[$reqDetail['request_type']] ?? $reqDetail['request_type'] ?></div>
    </div>
    <a href="<?= EP_URL ?>/hub.php" class="btn btn-secondary btn-sm">← Back to Hub</a>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Your Request</span></div>
            <div class="card-body">
                <p style="font-size:0.82rem;line-height:1.7;white-space:pre-wrap"><?= htmlspecialchars($reqDetail['description']) ?></p>
            </div>
        </div>

        <?php if ($reqDetail['hr_response']): ?>
        <div class="card" style="margin-top:12px">
            <div class="card-header" style="background:#F0FDF4;border-bottom-color:#BBF7D0">
                <span class="card-title" style="color:#166534">HR Response</span>
                <?php if ($reqDetail['resolved_at']): ?>
                <span style="font-size:0.67rem;color:var(--text-muted)"><?= date('d M Y H:i', strtotime($reqDetail['resolved_at'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p style="font-size:0.82rem;line-height:1.7;white-space:pre-wrap"><?= htmlspecialchars($reqDetail['hr_response']) ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="card" style="margin-top:12px">
            <div class="card-body">
                <div class="empty-state" style="padding:24px">
                    <div class="empty-state-title">Awaiting HR Response</div>
                    <div class="empty-state-desc">HR has been notified. You will see their response here once reviewed.</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Request Details</span></div>
            <div class="card-body" style="padding:0">
                <?php
                $details = [
                    'Status'   => '<span class="badge status-'.$reqDetail['status'].'">'.$statusLabels[$reqDetail['status']].'</span>',
                    'Priority' => htmlspecialchars($priorityLabels[$reqDetail['priority']]),
                    'Type'     => htmlspecialchars($reqTypeLabels[$reqDetail['request_type']] ?? $reqDetail['request_type']),
                    'Assigned' => htmlspecialchars($reqDetail['assigned_name'] ?? 'Unassigned'),
                    'Submitted'=> date('d M Y H:i', strtotime($reqDetail['created_at'])),
                    'Updated'  => date('d M Y H:i', strtotime($reqDetail['updated_at'])),
                ];
                foreach ($details as $k=>$v):
                ?>
                <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:0.7rem;font-weight:600;text-transform:uppercase;color:var(--text-muted)"><?= $k ?></span>
                    <span style="font-size:0.78rem"><?= $v ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Hub List + New Form -->
<div class="page-header">
    <div>
        <div class="page-title">Request Hub</div>
        <div class="page-sub">Submit and track your HR requests</div>
    </div>
    <button class="btn btn-primary btn-sm" id="newReqBtn">+ New Request</button>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- New Request Form (hidden by default) -->
<div id="newReqForm" style="<?= ($showNew || $error) ? '' : 'display:none' ?>">
    <div class="card" style="margin-bottom:16px;border-color:var(--primary)">
        <div class="card-header" style="background:var(--primary-light)">
            <span class="card-title" style="color:var(--primary)">Submit New Request</span>
            <button type="button" class="btn btn-ghost btn-sm" id="cancelReqBtn">Cancel</button>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <div class="form-row" style="grid-template-columns:1fr 1fr 1fr">
                    <div class="form-group">
                        <label class="form-label">Request Type <span class="required">*</span></label>
                        <select name="request_type" class="form-select" required>
                            <option value="">— Select —</option>
                            <?php foreach ($reqTypeLabels as $val=>$lab): ?>
                            <option value="<?= $val ?>" <?= ($preType===$val || ($_POST['request_type']??'')===$val) ? 'selected' : '' ?>>
                                <?= $lab ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <?php foreach ($priorityLabels as $val=>$lab): ?>
                            <option value="<?= $val ?>" <?= (($_POST['priority']??'normal')===$val)?'selected':'' ?>><?= $lab ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject <span class="required">*</span></label>
                        <input type="text" name="subject" class="form-control" required
                               value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                               placeholder="Brief description">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description <span class="required">*</span></label>
                    <textarea name="description" class="form-control" rows="5" required
                              placeholder="Provide as much detail as possible..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" class="btn btn-secondary btn-sm" id="cancelReqBtn2">Cancel</button>
                    <button type="submit" name="submit_request" class="btn btn-primary btn-sm">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stats row -->
<div style="display:flex;gap:10px;margin-bottom:16px;align-items:center">
    <a href="?" class="btn btn-sm <?= !$filterStatus?'btn-primary':'btn-secondary' ?>">All (<?= count($requests) ?>)</a>
    <a href="?status=open" class="btn btn-sm <?= $filterStatus==='open'?'btn-primary':'btn-secondary' ?>">Open (<?= $openCount ?>)</a>
    <a href="?status=in_progress" class="btn btn-sm <?= $filterStatus==='in_progress'?'btn-primary':'btn-secondary' ?>">In Progress (<?= $ipCount ?>)</a>
    <a href="?status=resolved" class="btn btn-sm <?= $filterStatus==='resolved'?'btn-primary':'btn-secondary' ?>">Resolved</a>
    <a href="?status=closed"   class="btn btn-sm <?= $filterStatus==='closed'?'btn-primary':'btn-secondary' ?>">Closed</a>
</div>

<div class="card">
    <?php if ($requests): ?>
    <div class="table-wrap">
        <table class="ep-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td style="font-family:monospace;font-size:0.72rem"><?= str_pad($r['id'],6,'0',STR_PAD_LEFT) ?></td>
                    <td style="font-weight:600"><?= htmlspecialchars($r['subject']) ?></td>
                    <td><?= $reqTypeLabels[$r['request_type']] ?? $r['request_type'] ?></td>
                    <td>
                        <?php $pc=['low'=>'badge-secondary','normal'=>'badge-info','high'=>'badge-warning','urgent'=>'badge-danger']; ?>
                        <span class="badge <?= $pc[$r['priority']] ?? 'badge-secondary' ?>"><?= $priorityLabels[$r['priority']] ?? $r['priority'] ?></span>
                    </td>
                    <td><span class="badge status-<?= $r['status'] ?>"><?= $statusLabels[$r['status']] ?? $r['status'] ?></span></td>
                    <td><?= htmlspecialchars($r['assigned_name'] ?? 'Unassigned') ?></td>
                    <td style="font-size:0.72rem;color:var(--text-muted)"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                    <td><a href="?view=<?= $r['id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">💬</div>
        <div class="empty-state-title">No requests found</div>
        <div class="empty-state-desc">Submit a request to get help from HR</div>
    </div>
    <?php endif; ?>
</div>

<?php endif; // end list view ?>

<script>
document.getElementById('newReqBtn').addEventListener('click', function() {
    document.getElementById('newReqForm').style.display = '';
    this.style.display = 'none';
});
document.getElementById('cancelReqBtn').addEventListener('click', hideForm);
document.getElementById('cancelReqBtn2').addEventListener('click', hideForm);
function hideForm() {
    document.getElementById('newReqForm').style.display = 'none';
    document.getElementById('newReqBtn').style.display = '';
}
</script>

<?php epLayoutEnd(); ?>
