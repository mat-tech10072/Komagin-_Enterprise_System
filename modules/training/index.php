<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('training.view');

$pageTitle  = 'Training';
$activeMenu = 'training';

$activeTab  = $_GET['tab'] ?? 'programs';
$page       = max(1,(int)($_GET['page'] ?? 1));
$perPage    = 20;

// Stats
$totalPrograms  = (int)db()->query("SELECT COUNT(*) FROM training_programs")->fetchColumn();
$activePrograms = (int)db()->query("SELECT COUNT(*) FROM training_programs WHERE status='ongoing'")->fetchColumn();
$totalAttendees = (int)db()->query("SELECT COUNT(*) FROM training_attendance")->fetchColumn();
$completed      = (int)db()->query("SELECT COUNT(*) FROM training_attendance WHERE attended=1")->fetchColumn();

if ($activeTab === 'programs') {
    $total = $totalPrograms;
    $pagination = paginate($total,$perPage,$page);
    $stmt = db()->query("SELECT tp.*, (SELECT COUNT(*) FROM training_attendance ta WHERE ta.training_id=tp.id) as attendees
        FROM training_programs tp ORDER BY tp.start_date DESC LIMIT $perPage OFFSET {$pagination['offset']}");
    $programs = $stmt->fetchAll();
} else {
    $countStmt = db()->query("SELECT COUNT(*) FROM training_attendance ta JOIN employees e ON ta.employee_id=e.id");
    $total = (int)$countStmt->fetchColumn();
    $pagination = paginate($total,$perPage,$page);
    $stmt = db()->query("SELECT ta.*, e.first_name, e.last_name, e.employee_number, tp.title as program_title
        FROM training_attendance ta
        JOIN employees e ON ta.employee_id=e.id
        JOIN training_programs tp ON ta.training_id=tp.id
        ORDER BY ta.created_at DESC LIMIT $perPage OFFSET {$pagination['offset']}");
    $attendance = $stmt->fetchAll();
}

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Training Programs</h1>
        <p class="page-subtitle">Manage training programs and employee attendance</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary btn-sm" data-modal-open="addProgramModal">Add Program</button>
    </div>
</div>

<!-- Stats -->
<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="kpi-card kpi-primary"><div class="kpi-card-label">Total Programs</div><div class="kpi-card-value"><?= $totalPrograms ?></div></div>
    <div class="kpi-card kpi-success"><div class="kpi-card-label">Active Programs</div><div class="kpi-card-value"><?= $activePrograms ?></div></div>
    <div class="kpi-card kpi-info"><div class="kpi-card-label">Total Enrolments</div><div class="kpi-card-value"><?= $totalAttendees ?></div></div>
    <div class="kpi-card kpi-success"><div class="kpi-card-label">Completed</div><div class="kpi-card-value"><?= $completed ?></div></div>
</div>

<div class="tab-nav">
    <a href="?tab=programs" class="tab-item <?= $activeTab==='programs'?'active':''?>">Programs (<?= $totalPrograms ?>)</a>
    <a href="?tab=attendance" class="tab-item <?= $activeTab==='attendance'?'active':''?>">Enrolments (<?= $totalAttendees ?>)</a>
</div>

<div class="card">
    <div class="table-wrapper" style="border:none;">
        <?php if ($activeTab === 'programs'): ?>
        <?php if (empty($programs)): ?>
        <div class="empty-state"><div class="empty-state-title">No training programs yet</div><div class="empty-state-desc">Add your first training program to get started.</div></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Title</th><th>Type</th><th>Trainer</th><th>Start Date</th><th>End Date</th><th>Enrolments</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($programs as $p): ?>
            <tr>
                <td style="font-weight:600;"><?= e($p['title']) ?></td>
                <td style="font-size:0.72rem;"><?= ucwords(str_replace('_',' ',$p['training_type'] ?? '')) ?></td>
                <td style="font-size:0.75rem;"><?= e($p['trainer_name'] ?? '—') ?></td>
                <td style="font-size:0.75rem;"><?= $p['start_date'] ? formatDate($p['start_date']) : '—' ?></td>
                <td style="font-size:0.75rem;"><?= $p['end_date'] ? formatDate($p['end_date']) : '—' ?></td>
                <td><span class="badge badge-info"><?= $p['attendees'] ?></span></td>
                <td>
                    <?php $sc=['planned'=>'badge-secondary','active'=>'badge-success','completed'=>'badge-info','cancelled'=>'badge-danger'];?>
                    <span class="badge <?= $sc[$p['status']] ?? 'badge-secondary' ?>"><?= ucfirst($p['status']) ?></span>
                </td>
                <td>
                    <button class="btn btn-ghost btn-sm" onclick="openEnrolModal(<?= $p['id'] ?>, '<?= e($p['title']) ?>')">Enrol</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php else: ?>
        <?php if (empty($attendance)): ?>
        <div class="empty-state"><div class="empty-state-title">No enrolments yet</div></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Employee</th><th>Program</th><th>Enrolled</th><th>Status</th><th>Score</th></tr></thead>
            <tbody>
            <?php foreach ($attendance as $a): ?>
            <tr>
                <td>
                    <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $a['employee_id'] ?>" style="font-weight:600;color:var(--text);font-size:0.78rem;">
                        <?= e($a['first_name'].' '.$a['last_name']) ?>
                    </a>
                    <div class="emp-num"><?= e($a['employee_number']) ?></div>
                </td>
                <td style="font-size:0.75rem;"><?= e($a['program_title']) ?></td>
                <td style="font-size:0.72rem;"><?= formatDate($a['created_at']) ?></td>
                <td>
                    <?php $sc=['enrolled'=>'badge-info','in_progress'=>'badge-warning','completed'=>'badge-success','failed'=>'badge-danger','withdrawn'=>'badge-secondary'];?>
                    <span class="badge <?= $sc[$a['status']] ?? 'badge-secondary' ?>"><?= ucfirst(str_replace('_',' ',$a['status'])) ?></span>
                </td>
                <td style="font-size:0.75rem;"><?= $a['score'] !== null ? $a['score'].'%' : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Program Modal -->
<div class="modal-overlay" id="addProgramModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">Add Training Program</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/training/save.php" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Title <span class="required">*</span></label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="training_type">
                            <option value="internal">Internal</option>
                            <option value="external">External</option>
                            <option value="online">Online</option>
                            <option value="on_the_job">On the Job</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Trainer / Provider</label>
                        <input type="text" class="form-control" name="trainer_name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Venue</label>
                        <input type="text" class="form-control" name="venue">
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
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Program</button>
            </div>
        </form>
    </div>
</div>

<!-- Enrol Employee Modal -->
<div class="modal-overlay" id="enrolModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Enrol Employee — <span id="enrolProgramName"></span></h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/training/enrol.php" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="program_id" id="enrolProgramId">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom:0;">
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Enrol</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEnrolModal(programId, programName) {
    document.getElementById('enrolProgramId').value = programId;
    document.getElementById('enrolProgramName').textContent = programName;
    openModal('enrolModal');
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
