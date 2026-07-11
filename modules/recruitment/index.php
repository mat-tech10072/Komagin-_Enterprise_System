<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('recruitment.view', 'view');

$pageTitle  = 'Recruitment';
$activeMenu = 'recruitment';

$activeTab = $_GET['tab'] ?? 'vacancies';
$status    = $_GET['status'] ?? '';
$page      = max(1,(int)($_GET['page'] ?? 1));
$perPage   = 20;

$departments = getDepartments();

if ($activeTab === 'vacancies') {
    $where  = ['1=1'];
    $params = [];
    if ($status) { $where[] = 'rv.status = ?'; $params[] = $status; }
    $whereSQL = implode(' AND ', $where);

    $countStmt = db()->prepare("SELECT COUNT(*) FROM recruitment_vacancies rv WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pagination = paginate($total,$perPage,$page);

    $stmt = db()->prepare("SELECT rv.*, d.name as dept_name,
        (SELECT COUNT(*) FROM recruitment_applications ra WHERE ra.vacancy_id=rv.id) as app_count
        FROM recruitment_vacancies rv LEFT JOIN departments d ON rv.department_id=d.id
        WHERE $whereSQL ORDER BY rv.created_at DESC LIMIT $perPage OFFSET {$pagination['offset']}");
    $stmt->execute($params);
    $vacancies = $stmt->fetchAll();
} else {
    $where  = ['1=1'];
    $params = [];
    if ($status) { $where[] = 'ra.status = ?'; $params[] = $status; }
    $whereSQL = implode(' AND ', $where);

    $countStmt = db()->prepare("SELECT COUNT(*) FROM recruitment_applications ra WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pagination = paginate($total,$perPage,$page);

    $stmt = db()->prepare("SELECT ra.*, rv.job_title FROM recruitment_applications ra
        JOIN recruitment_vacancies rv ON ra.vacancy_id=rv.id
        WHERE $whereSQL ORDER BY ra.created_at DESC LIMIT $perPage OFFSET {$pagination['offset']}");
    $stmt->execute($params);
    $applications = $stmt->fetchAll();
}

$vacStats = db()->query("SELECT status, COUNT(*) as cnt FROM recruitment_vacancies GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$appStats = db()->query("SELECT status, COUNT(*) as cnt FROM recruitment_applications GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<?= renderFlash() ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Recruitment</h1>
        <p class="page-subtitle">Manage vacancies and applicants</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary btn-sm" data-modal-open="addVacancyModal">Post Vacancy</button>
    </div>
</div>

<!-- Stats -->
<div class="kpi-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:20px;">
    <?php $stats=[['Open Vacancies',$vacStats['open']??0,'kpi-primary'],['Applications',$appStats['submitted']??0,'kpi-info'],['Shortlisted',$appStats['shortlisted']??0,'kpi-warning'],['Interviews',$appStats['interview_scheduled']??0,'kpi-warning'],['Selected',$appStats['selected']??0,'kpi-success']]; ?>
    <?php foreach($stats as [$l,$v,$c]): ?>
    <div class="kpi-card <?= $c ?>">
        <div class="kpi-card-label"><?= $l ?></div>
        <div class="kpi-card-value" style="font-size:1.4rem;"><?= $v ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="tab-nav">
    <a href="?tab=vacancies" class="tab-item <?= $activeTab==='vacancies'?'active':'' ?>">Vacancies (<?= array_sum($vacStats) ?>)</a>
    <a href="?tab=applications" class="tab-item <?= $activeTab==='applications'?'active':'' ?>">Applications (<?= array_sum($appStats) ?>)</a>
</div>

<div class="card">
    <div class="table-wrapper" style="border:none;">
        <?php if ($activeTab === 'vacancies'): ?>
        <?php if (empty($vacancies)): ?>
        <div class="empty-state"><div class="empty-state-title">No vacancies posted yet</div></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Job Title</th><th>Department</th><th>Type</th><th>Deadline</th><th>Applications</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($vacancies as $v): ?>
            <tr>
                <td style="font-weight:600;"><?= e($v['job_title']) ?></td>
                <td style="font-size:0.72rem;"><?= e($v['dept_name']??'—') ?></td>
                <td style="font-size:0.72rem;"><?= ucfirst(str_replace('_',' ',$v['employment_type'])) ?></td>
                <td style="font-size:0.75rem;"><?= $v['deadline'] ? formatDate($v['deadline']) : '—' ?></td>
                <td>
                    <span class="badge badge-info"><?= $v['app_count'] ?></span>
                </td>
                <td>
                    <?php $sc=['draft'=>'badge-secondary','open'=>'badge-success','closed'=>'badge-danger','on_hold'=>'badge-warning'];?>
                    <span class="badge <?= $sc[$v['status']]??'badge-secondary' ?>"><?= ucfirst($v['status']) ?></span>
                </td>
                <td>
                    <a href="?tab=applications&vacancy=<?= $v['id'] ?>" class="btn btn-ghost btn-sm">Applicants</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php else: ?>
        <?php if (empty($applications)): ?>
        <div class="empty-state"><div class="empty-state-title">No applications yet</div></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Applicant</th><th>Position</th><th>Email</th><th>Experience</th><th>Status</th><th>Applied</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($applications as $a): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:0.82rem;"><?= e($a['first_name'].' '.$a['last_name']) ?></div>
                    <?php if ($a['current_position'] && $a['current_employer']): ?>
                    <div style="font-size:0.68rem;color:var(--text-muted);"><?= e($a['current_position']) ?> @ <?= e($a['current_employer']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.75rem;"><?= e($a['job_title']) ?></td>
                <td style="font-size:0.72rem;"><?= e($a['email']) ?></td>
                <td style="font-size:0.75rem;"><?= $a['years_experience'] ? $a['years_experience'].' yrs' : '—' ?></td>
                <td>
                    <?php $sc=['submitted'=>'badge-info','reviewing'=>'badge-secondary','shortlisted'=>'badge-warning','interview_scheduled'=>'badge-warning','interviewed'=>'badge-primary','selected'=>'badge-success','rejected'=>'badge-danger','withdrawn'=>'badge-secondary'];?>
                    <span class="badge <?= $sc[$a['status']]??'badge-secondary' ?>"><?= ucfirst(str_replace('_',' ',$a['status'])) ?></span>
                </td>
                <td style="font-size:0.72rem;"><?= formatDate($a['created_at']) ?></td>
                <td>
                    <div class="table-actions" style="display:flex;gap:4px;flex-wrap:wrap;">
                        <?php if ($a['cv_file']): ?>
                            <a href="<?= APP_URL ?>/<?= e($a['cv_file']) ?>" class="btn btn-secondary btn-sm" target="_blank" style="font-size:0.68rem;">CV</a>
                        <?php endif; ?>
                        <?php if (canApprove('recruitment.review')): ?>
                        <button class="btn btn-primary btn-sm" style="font-size:0.68rem;" onclick="updateApp(<?= $a['id'] ?>, '<?= e($a['first_name'].' '.$a['last_name']) ?>', '<?= $a['status'] ?>')">Update</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Vacancy Modal -->
<div class="modal-overlay" id="addVacancyModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h5 class="modal-title">Post New Vacancy</h5>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/recruitment/vacancy_save.php" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Job Title <span class="required">*</span></label>
                        <input type="text" class="form-control" name="job_title" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department_id">
                            <option value="">Select</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Employment Type</label>
                        <select class="form-select" name="employment_type">
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="intern">Intern</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Application Deadline</label>
                        <input type="date" class="form-control" name="deadline">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Job Description</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Requirements</label>
                    <textarea class="form-control" name="requirements" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Post Vacancy</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Application Modal -->
<div class="modal-overlay" id="updateAppModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title" id="updateAppTitle">Update Application</h5>
            <button class="modal-close" data-modal-close="updateAppModal">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/modules/recruitment/application_update.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="application_id" id="updateAppId" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Pipeline Stage</label>
                    <select class="form-select" name="new_status" id="updateAppStatus" required>
                        <?php foreach (['submitted'=>'Submitted','reviewing'=>'Reviewing','shortlisted'=>'Shortlisted','interview_scheduled'=>'Interview Scheduled','interviewed'=>'Interviewed','selected'=>'Selected','rejected'=>'Rejected','withdrawn'=>'Withdrawn'] as $v=>$l): ?>
                        <option value="<?= $v ?>"><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="interviewDateGroup" style="display:none;">
                    <label class="form-label">Interview Date &amp; Time</label>
                    <input type="datetime-local" class="form-control" name="interview_date" id="interviewDate">
                </div>
                <div class="form-group">
                    <label class="form-label">HR Remarks</label>
                    <textarea class="form-control" name="hr_remarks" rows="3" placeholder="Notes about this stage change..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="updateAppModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Pipeline</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateApp(id, name, currentStatus) {
    document.getElementById('updateAppId').value = id;
    document.getElementById('updateAppTitle').textContent = 'Update: ' + name;
    document.getElementById('updateAppStatus').value = currentStatus;
    document.getElementById('updateAppStatus').dispatchEvent(new Event('change'));
    document.getElementById('updateAppModal').classList.add('active');
}
document.getElementById('updateAppStatus')?.addEventListener('change', function() {
    document.getElementById('interviewDateGroup').style.display =
        this.value === 'interview_scheduled' ? 'block' : 'none';
});
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
