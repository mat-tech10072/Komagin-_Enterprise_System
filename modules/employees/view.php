<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('employees.view');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$emp = getEmployee($id);
if (!$emp) { setFlash('error', 'Employee not found.'); header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$pageTitle  = $emp['first_name'] . ' ' . $emp['last_name'];
$activeMenu = 'employees';

// Load related data
$documents = db()->prepare("SELECT * FROM employee_documents WHERE employee_id=? AND is_deleted=0 ORDER BY uploaded_at DESC");
$documents->execute([$id]); $documents = $documents->fetchAll();

$dependents = db()->prepare("SELECT * FROM employee_dependents WHERE employee_id=? ORDER BY relationship, full_name");
$dependents->execute([$id]); $dependents = $dependents->fetchAll();

$qualifications = db()->prepare("SELECT * FROM employee_qualifications WHERE employee_id=? ORDER BY year_obtained DESC");
$qualifications->execute([$id]); $qualifications = $qualifications->fetchAll();

$workHistory = db()->prepare("SELECT * FROM employee_work_history WHERE employee_id=? ORDER BY start_date DESC");
$workHistory->execute([$id]); $workHistory = $workHistory->fetchAll();

$skills = db()->prepare("SELECT * FROM employee_skills WHERE employee_id=? ORDER BY skill_name");
$skills->execute([$id]); $skills = $skills->fetchAll();

// Profile completeness
$completenessChecks = [
    'Photo'           => !empty($emp['photo']),
    'Date of Birth'   => !empty($emp['date_of_birth']),
    'National ID'     => !empty($emp['national_id']),
    'Phone'           => !empty($emp['phone']),
    'Address'         => !empty($emp['residential_address']),
    'Emergency Contact' => !empty($emp['emergency_contact_name']),
    'Bank Details'    => !empty($emp['bank_account_number']),
    'Qualifications'  => count($qualifications) > 0,
    'Skills'          => count($skills) > 0,
    'Start Date'      => !empty($emp['start_date']),
];
$completePct = count(array_filter($completenessChecks)) / count($completenessChecks) * 100;

$recentAttendance = db()->prepare("SELECT * FROM attendance WHERE employee_id=? ORDER BY attendance_date DESC LIMIT 10");
$recentAttendance->execute([$id]); $recentAttendance = $recentAttendance->fetchAll();

$leaveHistory = db()->prepare("SELECT la.*, lt.name as leave_type FROM leave_applications la JOIN leave_types lt ON la.leave_type_id=lt.id WHERE la.employee_id=? ORDER BY la.created_at DESC LIMIT 5");
$leaveHistory->execute([$id]); $leaveHistory = $leaveHistory->fetchAll();

$leaveBalances = db()->prepare("SELECT lb.*, lt.name FROM leave_balances lb JOIN leave_types lt ON lb.leave_type_id=lt.id WHERE lb.employee_id=? AND lb.year=?");
$leaveBalances->execute([$id, date('Y')]); $leaveBalances = $leaveBalances->fetchAll();

$statusHistory = db()->prepare("SELECT esh.*, u.username FROM employee_status_history esh LEFT JOIN users u ON esh.changed_by=u.id WHERE esh.employee_id=? ORDER BY esh.changed_at DESC LIMIT 5");
$statusHistory->execute([$id]); $statusHistory = $statusHistory->fetchAll();

$assets = db()->prepare("SELECT aa.*, ca.description, ca.asset_type FROM asset_assignments aa JOIN company_assets ca ON aa.asset_id=ca.id WHERE aa.employee_id=? AND aa.is_returned=0");
$assets->execute([$id]); $assets = $assets->fetchAll();

$activeTab = $_GET['tab'] ?? 'overview';
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<!-- Header -->
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/employees/index.php">Employees</a></li>
                <li class="breadcrumb-item active"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></li>
            </ol>
        </nav>
    </div>
    <div class="page-actions">
        <?php if (canShare('employees.update_links')): ?>
        <button class="btn btn-secondary btn-sm" data-modal-open="generateLinkModal">Send Update Link</button>
        <?php endif; ?>
        <?php if (canEdit('employees.portal_password')): ?>
        <button class="btn btn-secondary btn-sm" data-modal-open="portalPasswordModal">Portal Password</button>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/modules/employees/id_card.php?id=<?= $id ?>" target="_blank" class="btn btn-secondary btn-sm">ID Card</a>
        <?php if (canEdit('employees.edit')): ?>
        <a href="<?= APP_URL ?>/modules/employees/edit.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">Edit Profile</a>
        <?php endif; ?>
        <?php if (canEdit('employees.status')): ?>
        <a href="<?= APP_URL ?>/modules/employees/status.php?id=<?= $id ?>" class="btn btn-primary btn-sm">Change Status</a>
        <?php endif; ?>
        <?php if (canDelete('employees.delete')): ?>
        <a href="<?= APP_URL ?>/modules/employees/delete.php?id=<?= $id ?>"
           class="btn btn-danger btn-sm"
           title="Permanently delete this employee and all their records">
            Delete
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Profile Banner -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-body" style="display:flex;align-items:center;gap:20px;padding:20px 24px;">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--primary-light);border:2px solid var(--border);flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700;color:var(--primary);">
            <?php if (!empty($emp['photo'])): ?>
                <img src="<?= APP_URL ?>/<?= e($emp['photo']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <?= strtoupper(substr($emp['first_name'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div style="flex:1;">
            <div style="font-size:1.25rem;font-weight:700;color:var(--text);"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
            <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:2px;"><?= e($emp['position_title'] ?? '—') ?> · <?= e($emp['department_name'] ?? '—') ?></div>
            <div style="margin-top:8px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <code style="font-size:0.75rem;background:var(--bg);padding:3px 8px;border-radius:5px;border:1px solid var(--border);"><?= e($emp['employee_number']) ?></code>
                <?= employeeStatusBadge($emp['status']) ?>
                <span style="font-size:0.72rem;color:var(--text-muted);"><?= ucfirst(str_replace('_',' ',$emp['employment_type'])) ?></span>
                <?php if ($emp['start_date']): ?>
                    <span style="font-size:0.72rem;color:var(--text-muted);">Since <?= formatDate($emp['start_date']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
            <?php if ($emp['email']): ?>
                <div style="font-size:0.75rem;color:var(--text-secondary);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <?= e($emp['email']) ?>
                </div>
            <?php endif; ?>
            <?php if ($emp['phone']): ?>
                <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:3px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.61 4.38 2 2 0 0 1 3.59 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <?= e($emp['phone']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Profile Completeness -->
<?php if ($completePct < 100): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;display:flex;align-items:center;gap:14px;">
    <div style="flex:1;">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:0.75rem;">
            <span style="font-weight:600;color:var(--text);">Profile Completeness</span>
            <span style="font-weight:700;color:<?= $completePct>=80?'var(--success)':($completePct>=50?'var(--warning)':'var(--danger)') ?>;"><?= round($completePct) ?>%</span>
        </div>
        <div style="height:6px;background:var(--bg);border-radius:3px;overflow:hidden;">
            <div style="width:<?= round($completePct) ?>%;height:100%;background:<?= $completePct>=80?'var(--success)':($completePct>=50?'var(--warning)':'var(--danger)') ?>;border-radius:3px;transition:width .3s;"></div>
        </div>
        <div style="margin-top:6px;font-size:0.68rem;color:var(--text-muted);">
            Missing: <?= implode(', ', array_keys(array_filter($completenessChecks, fn($v)=>!$v))) ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="tab-nav">
    <?php $tabs = [
        ['overview','Overview'],
        ['dependents','Dependents ('.count($dependents).')'],
        ['qualifications','Qualifications ('.count($qualifications).')'],
        ['skills','Skills ('.count($skills).')'],
        ['work_history','Work History'],
        ['attendance','Attendance'],
        ['leave','Leave'],
        ['documents','Documents ('. count($documents).')'],
        ['assets','Assets'],
        ['history','History'],
    ]; ?>
    <?php foreach ($tabs as [$key,$label]): ?>
        <a href="?id=<?= $id ?>&tab=<?= $key ?>" class="tab-item <?= $activeTab===$key ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php if ($activeTab === 'overview'): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <!-- Personal Details -->
    <div class="card">
        <div class="card-header"><span class="card-title">Personal Details</span></div>
        <div class="card-body">
            <?php $rows = [
                ['Date of Birth', formatDate($emp['date_of_birth'])],
                ['Gender', ucfirst($emp['gender'] ?? '—')],
                ['Marital Status', ucfirst(str_replace('_',' ',$emp['marital_status'] ?? '—'))],
                ['National ID', $emp['national_id'] ?? '—'],
                ['Nationality', $emp['nationality'] ?? '—'],
                ['City', $emp['city'] ?? '—'],
                ['Country', $emp['country'] ?? '—'],
            ]; ?>
            <?php foreach ($rows as [$label,$val]): ?>
            <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-light);font-size:0.78rem;">
                <span style="color:var(--text-secondary);"><?= e($label) ?></span>
                <span style="font-weight:500;"><?= e($val) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Employment Details -->
    <div class="card">
        <div class="card-header"><span class="card-title">Employment Details</span></div>
        <div class="card-body">
            <?php $rows = [
                ['Department', $emp['department_name'] ?? '—'],
                ['Position', $emp['position_title'] ?? '—'],
                ['Supervisor', $emp['supervisor_name'] ?? '—'],
                ['Type', ucfirst(str_replace('_',' ',$emp['employment_type']))],
                ['Start Date', formatDate($emp['start_date'])],
                ['Contract End', formatDate($emp['contract_end_date'])],
                ['Probation End', formatDate($emp['probation_end'])],
                ['Work Location', $emp['work_location'] ?? '—'],
            ]; ?>
            <?php foreach ($rows as [$label,$val]): ?>
            <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-light);font-size:0.78rem;">
                <span style="color:var(--text-secondary);"><?= e($label) ?></span>
                <span style="font-weight:500;"><?= e($val) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="card">
        <div class="card-header"><span class="card-title">Emergency Contact</span></div>
        <div class="card-body">
            <?php $rows = [
                ['Name', $emp['emergency_contact_name'] ?? '—'],
                ['Relationship', $emp['emergency_contact_relation'] ?? '—'],
                ['Phone', $emp['emergency_contact_phone'] ?? '—'],
            ]; ?>
            <?php foreach ($rows as [$label,$val]): ?>
            <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-light);font-size:0.78rem;">
                <span style="color:var(--text-secondary);"><?= e($label) ?></span>
                <span style="font-weight:500;"><?= e($val) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bank Details -->
    <div class="card">
        <div class="card-header"><span class="card-title">Bank Details</span>
            <span style="font-size:0.68rem;color:var(--text-muted);">Payroll-restricted</span>
        </div>
        <div class="card-body">
            <?php if (!canViewBankData()): ?>
            <div style="text-align:center;padding:16px;color:var(--text-muted);font-size:0.78rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:block;margin:0 auto 8px;opacity:.4;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Bank details are restricted to Payroll roles.<br>
                Contact your Payroll Manager for access.
            </div>
            <?php else: ?>
            <?php $rows = [
                ['Bank', $emp['bank_name'] ?? '—'],
                ['Account Number', $emp['bank_account_number'] ? '****' . substr($emp['bank_account_number'],-4) : '—'],
                ['Branch Code', $emp['bank_branch_code'] ?? '—'],
                ['Account Type', $emp['bank_account_type'] ?? '—'],
            ]; ?>
            <?php foreach ($rows as [$label,$val]): ?>
            <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-light);font-size:0.78rem;">
                <span style="color:var(--text-secondary);"><?= e($label) ?></span>
                <span style="font-weight:500;"><?= e($val) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Leave Balances -->
<?php if (!empty($leaveBalances)): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header"><span class="card-title">Leave Balances — <?= date('Y') ?></span></div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead><tr><th>Leave Type</th><th>Entitled</th><th>Used</th><th>Pending</th><th>Remaining</th></tr></thead>
            <tbody>
            <?php foreach ($leaveBalances as $lb): ?>
            <tr>
                <td><?= e($lb['name']) ?></td>
                <td><?= $lb['entitled_days'] ?></td>
                <td><?= $lb['used_days'] ?></td>
                <td><?= $lb['pending_days'] ?></td>
                <td><strong><?= $lb['remaining_days'] ?></strong></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php elseif ($activeTab === 'attendance'): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Recent Attendance</span>
        <a href="<?= APP_URL ?>/modules/attendance/index.php?emp=<?= $id ?>" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <div class="table-wrapper" style="border:none;">
        <?php if (empty($recentAttendance)): ?>
        <div class="empty-state"><div class="empty-state-desc">No attendance records found.</div></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Date</th><th>Sign In</th><th>Break Out</th><th>Break In</th><th>Sign Out</th><th>Hours</th><th>OT</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentAttendance as $att): ?>
            <tr>
                <td><?= formatDate($att['attendance_date']) ?></td>
                <td><?= formatTime($att['sign_in']) ?></td>
                <td><?= formatTime($att['break_out']) ?></td>
                <td><?= formatTime($att['break_in']) ?></td>
                <td><?= formatTime($att['sign_out']) ?></td>
                <td><?= $att['total_hours_worked'] ?> hrs</td>
                <td><?= $att['overtime_hours'] > 0 ? $att['overtime_hours'].' hrs' : '—' ?></td>
                <td><?= attendanceStatusBadge($att['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($activeTab === 'leave'): ?>
<div class="card">
    <div class="card-header"><span class="card-title">Leave Applications</span></div>
    <div class="table-wrapper" style="border:none;">
        <?php if (empty($leaveHistory)): ?>
        <div class="empty-state"><div class="empty-state-desc">No leave applications found.</div></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th><th>Applied</th></tr></thead>
            <tbody>
            <?php foreach ($leaveHistory as $lv): ?>
            <tr>
                <td><?= e($lv['leave_type']) ?></td>
                <td><?= formatDate($lv['start_date']) ?></td>
                <td><?= formatDate($lv['end_date']) ?></td>
                <td><?= $lv['total_days'] ?></td>
                <td><?= leaveStatusBadge($lv['status']) ?></td>
                <td><?= formatDate($lv['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($activeTab === 'documents'): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Employee Documents</span>
        <a href="<?= APP_URL ?>/modules/documents/upload.php?emp=<?= $id ?>" class="btn btn-primary btn-sm">Upload Document</a>
    </div>
    <div class="table-wrapper" style="border:none;">
        <?php if (empty($documents)): ?>
        <div class="empty-state">
            <div class="empty-state-desc">No documents uploaded yet.</div>
            <a href="<?= APP_URL ?>/modules/documents/upload.php?emp=<?= $id ?>" class="btn btn-primary btn-sm">Upload Document</a>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Document</th><th>Category</th><th>Expiry</th><th>Verified</th><th>Uploaded</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($documents as $doc): ?>
            <tr>
                <td><?= e($doc['document_name']) ?></td>
                <td><span class="badge badge-secondary"><?= e(ucfirst(str_replace('_',' ',$doc['category']))) ?></span></td>
                <td><?= $doc['expiry_date'] ? formatDate($doc['expiry_date']) : '—' ?></td>
                <td><?= $doc['is_verified'] ? '<span class="badge badge-success">Verified</span>' : '<span class="badge badge-warning">Pending</span>' ?></td>
                <td><?= formatDate($doc['uploaded_at']) ?></td>
                <td>
                    <a href="<?= APP_URL ?>/<?= e($doc['file_path']) ?>" class="btn btn-ghost btn-sm" target="_blank">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($activeTab === 'assets'): ?>
<div class="card">
    <div class="card-header"><span class="card-title">Assigned Assets</span></div>
    <div class="table-wrapper" style="border:none;">
        <?php if (empty($assets)): ?>
        <div class="empty-state"><div class="empty-state-desc">No assets currently assigned.</div></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Asset</th><th>Type</th><th>Issued</th><th>Condition</th></tr></thead>
            <tbody>
            <?php foreach ($assets as $a): ?>
            <tr>
                <td><?= e($a['description']) ?></td>
                <td><span class="badge badge-secondary"><?= e(ucfirst($a['asset_type'])) ?></span></td>
                <td><?= formatDate($a['issued_date']) ?></td>
                <td><?= e(ucfirst($a['condition_on_issue'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($activeTab === 'history'): ?>
<div class="card">
    <div class="card-header"><span class="card-title">Status History</span></div>
    <div class="card-body">
        <?php if (empty($statusHistory)): ?>
        <div class="empty-state"><div class="empty-state-desc">No status changes recorded.</div></div>
        <?php else: ?>
        <div class="timeline">
            <?php foreach ($statusHistory as $h): ?>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-meta"><?= e($h['username'] ?? 'System') ?> · <?= formatDateTime($h['changed_at']) ?></div>
                <div class="timeline-title">
                    Status changed: <?= e(ucfirst($h['old_status'] ?? 'New')) ?> → <?= e(ucfirst($h['new_status'])) ?>
                </div>
                <?php if ($h['reason']): ?>
                    <div class="timeline-desc"><?= e($h['reason']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($activeTab === 'dependents'): ?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <span class="card-title">Dependents</span>
        <?php if (canEdit('employees.edit')): ?>
        <button class="btn btn-primary btn-sm" data-modal-open="addDependentModal">Add Dependent</button>
        <?php endif; ?>
    </div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead><tr><th>Name</th><th>Relationship</th><th>Date of Birth</th><th>Gender</th><th>National ID</th><th>Beneficiary</th></tr></thead>
            <tbody>
            <?php foreach ($dependents as $d): ?>
            <tr>
                <td style="font-weight:600;font-size:0.82rem;"><?= e($d['full_name']) ?></td>
                <td><span class="badge badge-secondary"><?= ucfirst($d['relationship']) ?></span></td>
                <td style="font-size:0.78rem;"><?= formatDate($d['date_of_birth']) ?></td>
                <td style="font-size:0.78rem;"><?= $d['gender'] ? ucfirst($d['gender']) : '—' ?></td>
                <td style="font-size:0.78rem;"><?= e($d['national_id'] ?? '—') ?></td>
                <td>
                    <?php if ($d['is_beneficiary']): ?>
                    <span class="badge badge-success"><?= $d['beneficiary_percentage'] ? $d['beneficiary_percentage'].'%' : 'Yes' ?></span>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($dependents)): ?>
            <tr><td colspan="6" class="empty-state">No dependents recorded.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Dependent Modal -->
<?php if (canEdit('employees.edit')): ?>
<div class="modal-overlay" id="addDependentModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Add Dependent</h5>
            <button class="modal-close" data-modal-close="addDependentModal">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/employees/dependent_save.php">
            <?php $csrfD = generateCsrfToken(); ?>
            <input type="hidden" name="csrf_token" value="<?= $csrfD ?>">
            <input type="hidden" name="employee_id" value="<?= $id ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relationship <span class="required">*</span></label>
                        <select class="form-select" name="relationship" required>
                            <?php foreach (['spouse','child','parent','sibling','other'] as $rel): ?>
                            <option value="<?= $rel ?>"><?= ucfirst($rel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="date_of_birth">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">National ID</label>
                        <input type="text" class="form-control" name="national_id">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Beneficiary %</label>
                        <input type="number" class="form-control" name="beneficiary_percentage" min="0" max="100" step="0.01" placeholder="e.g. 50">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="addDependentModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Dependent</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php elseif ($activeTab === 'qualifications'): ?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <span class="card-title">Qualifications &amp; Education</span>
        <?php if (canEdit('employees.edit')): ?>
        <button class="btn btn-primary btn-sm" data-modal-open="addQualModal">Add Qualification</button>
        <?php endif; ?>
    </div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead><tr><th>Qualification</th><th>Institution</th><th>Field</th><th>Year</th><th>Grade</th><th>Verified</th></tr></thead>
            <tbody>
            <?php foreach ($qualifications as $q): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:0.82rem;"><?= e($q['title']) ?></div>
                    <span class="badge badge-secondary" style="font-size:0.63rem;"><?= ucwords(str_replace('_',' ',$q['qualification_type'])) ?></span>
                </td>
                <td style="font-size:0.78rem;"><?= e($q['institution'] ?? '—') ?></td>
                <td style="font-size:0.78rem;"><?= e($q['field_of_study'] ?? '—') ?></td>
                <td style="font-size:0.78rem;"><?= $q['year_obtained'] ?? '—' ?></td>
                <td style="font-size:0.78rem;"><?= e($q['grade_result'] ?? '—') ?></td>
                <td>
                    <?php if ($q['is_verified']): ?>
                    <span class="badge badge-success">Verified</span>
                    <?php else: ?>
                    <span class="badge badge-warning">Unverified</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($qualifications)): ?>
            <tr><td colspan="6" class="empty-state">No qualifications recorded.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Qualification Modal -->
<?php if (canEdit('employees.edit')): ?>
<div class="modal-overlay" id="addQualModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Add Qualification</h5>
            <button class="modal-close" data-modal-close="addQualModal">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/employees/qualification_save.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="employee_id" value="<?= $id ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Title <span class="required">*</span></label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g. Bachelor of Commerce">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type <span class="required">*</span></label>
                        <select class="form-select" name="qualification_type" required>
                            <?php foreach (['matric'=>'Matric','diploma'=>'Diploma','degree'=>'Degree','honours'=>'Honours','masters'=>'Masters','phd'=>'PhD','trade_cert'=>'Trade Certificate','professional_cert'=>'Professional Certificate','other'=>'Other'] as $v=>$l): ?>
                            <option value="<?= $v ?>"><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Institution</label>
                        <input type="text" class="form-control" name="institution">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Field of Study</label>
                        <input type="text" class="form-control" name="field_of_study">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Year Obtained</label>
                        <input type="number" class="form-control" name="year_obtained" min="1950" max="<?= date('Y') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grade / Result</label>
                        <input type="text" class="form-control" name="grade_result" placeholder="e.g. Distinction, 75%">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Certificate</label>
                    <input type="file" class="form-control" name="certificate" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="addQualModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Qualification</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php elseif ($activeTab === 'skills'): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Skills &amp; Competencies</span>
        <?php if (canEdit('employees.edit')): ?>
        <button class="btn btn-primary btn-sm" data-modal-open="addSkillModal">Add Skill</button>
        <?php endif; ?>
    </div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead><tr><th>Skill</th><th>Proficiency</th><th>Expiry</th><th>Certificate</th></tr></thead>
            <tbody>
            <?php foreach ($skills as $sk): ?>
            <tr>
                <td style="font-weight:600;font-size:0.82rem;"><?= e($sk['skill_name']) ?></td>
                <td>
                    <?php $pColors = ['beginner'=>'secondary','intermediate'=>'info','advanced'=>'warning','expert'=>'success']; ?>
                    <span class="badge badge-<?= $pColors[$sk['proficiency']] ?? 'secondary' ?>"><?= ucfirst($sk['proficiency']) ?></span>
                </td>
                <td style="font-size:0.78rem;">
                    <?php if ($sk['expiry_date']): ?>
                        <?php $exp = strtotime($sk['expiry_date']); $expired = $exp < time(); ?>
                        <span style="color:<?= $expired?'var(--danger)':'var(--text)' ?>;"><?= formatDate($sk['expiry_date']) ?></span>
                        <?php if ($expired): ?><span class="badge badge-danger" style="font-size:0.6rem;">Expired</span><?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <?php if ($sk['certificate_file']): ?>
                    <a href="<?= APP_URL ?>/<?= e($sk['certificate_file']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="font-size:0.68rem;">View</a>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($skills)): ?>
            <tr><td colspan="4" class="empty-state">No skills recorded.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($activeTab === 'work_history'): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Work History</span>
        <?php if (canEdit('employees.edit')): ?>
        <button class="btn btn-primary btn-sm" data-modal-open="addWorkHistModal">Add Record</button>
        <?php endif; ?>
    </div>
    <div class="table-wrapper" style="border:none;">
        <table class="table">
            <thead><tr><th>Employer</th><th>Position</th><th>Period</th><th>Reason for Leaving</th><th>Reference</th></tr></thead>
            <tbody>
            <?php foreach ($workHistory as $wh): ?>
            <tr>
                <td style="font-weight:600;font-size:0.82rem;"><?= e($wh['employer_name']) ?></td>
                <td style="font-size:0.78rem;"><?= e($wh['position_held'] ?? '—') ?></td>
                <td style="font-size:0.75rem;white-space:nowrap;">
                    <?= formatDate($wh['start_date']) ?> — <?= $wh['end_date'] ? formatDate($wh['end_date']) : 'Current' ?>
                </td>
                <td style="font-size:0.75rem;"><?= e($wh['reason_for_leaving'] ?? '—') ?></td>
                <td style="font-size:0.75rem;">
                    <?php if ($wh['reference_name']): ?>
                        <?= e($wh['reference_name']) ?><?= $wh['reference_phone'] ? '<br><span style="color:var(--text-muted)">'.e($wh['reference_phone']).'</span>' : '' ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($workHistory)): ?>
            <tr><td colspan="5" class="empty-state">No work history recorded.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Work History Modal -->
<?php if (canEdit('employees.edit')): ?>
<div class="modal-overlay" id="addWorkHistModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Add Work History</h5>
            <button class="modal-close" data-modal-close="addWorkHistModal">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/employees/work_history_save.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="employee_id" value="<?= $id ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Employer Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="employer_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position Held</label>
                        <input type="text" class="form-control" name="position_held">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for Leaving</label>
                    <input type="text" class="form-control" name="reason_for_leaving">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Reference Name</label>
                        <input type="text" class="form-control" name="reference_name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reference Phone</label>
                        <input type="text" class="form-control" name="reference_phone">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="addWorkHistModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Generate Self-Service Update Link Modal -->
<?php if (in_array($_SESSION['user_role'],['super_admin','hr_manager','hr_officer'])): ?>
<div class="modal-overlay" id="generateLinkModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Generate Self-Service Update Link</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/employees/generate_link.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="employee_id" value="<?= $id ?>">
            <div class="modal-body">
                <p style="font-size:0.82rem;margin-bottom:16px;">
                    Send a secure link to <strong><?= e($emp['first_name'].' '.$emp['last_name']) ?></strong> to update their personal details. HR will review and approve changes before applying.
                </p>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Link Expiry Date</label>
                    <input type="date" class="form-control" name="expires" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Generate Link</button>
            </div>
        </form>
    </div>
</div>
<!-- Portal Password Modal -->
<div class="modal-overlay" id="portalPasswordModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Set / Reset Portal Password</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/employees/set_portal_password.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="employee_id" value="<?= $id ?>">
            <div class="modal-body">
                <p style="font-size:0.82rem;margin-bottom:14px">
                    Set or reset the Employee Portal password for
                    <strong><?= e($emp['first_name'].' '.$emp['last_name']) ?></strong>.
                    The employee uses their Employee Number + this password to log into the portal.
                </p>
                <div class="form-group">
                    <label class="form-label">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" required
                           minlength="8" placeholder="Min 8 characters" autocomplete="new-password">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" required
                           placeholder="Repeat password" autocomplete="new-password">
                </div>
                <p style="font-size:0.72rem;color:var(--text-muted);margin-top:8px">
                    Portal access is <?= ($emp['portal_active']??1) ? '<strong>enabled</strong>' : '<strong style="color:var(--danger)">disabled</strong>' ?>.
                    <?php if ($emp['portal_last_login']): ?>
                    Last login: <?= date('d M Y H:i', strtotime($emp['portal_last_login'])) ?>.
                    <?php else: ?>
                    Employee has not logged in yet.
                    <?php endif; ?>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Set Password</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
