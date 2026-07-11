<?php
require_once dirname(dirname(dirname(__FILE__))) . '/auth/session.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/functions.php';

requireLogin();
requirePermission('consultants.view', 'view');
$activeMenu = 'consultants';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/consultants/index.php'); exit; }

$con = db()->prepare("SELECT * FROM consultants WHERE id=? LIMIT 1");
$con->execute([$id]);
$con = $con->fetch(PDO::FETCH_ASSOC);
if (!$con) { setFlash('error','Consultant not found.'); header('Location: ' . APP_URL . '/modules/consultants/index.php'); exit; }

$flash = getFlash();

// Type-specific data
$attendance = [];
$scopes     = [];
$attStats   = [];

if ($con['type'] === 'time_based') {
    $stmt = db()->prepare("SELECT * FROM consultant_attendance WHERE consultant_id=? ORDER BY work_date DESC LIMIT 30");
    $stmt->execute([$id]);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $agg = db()->prepare("SELECT COUNT(*) AS days, SUM(total_hours) AS total_h, AVG(total_hours) AS avg_h FROM consultant_attendance WHERE consultant_id=? AND clock_out IS NOT NULL");
    $agg->execute([$id]);
    $attStats = $agg->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = db()->prepare("SELECT * FROM consultant_scopes WHERE consultant_id=? ORDER BY sort_order, id");
    $stmt->execute([$id]);
    $scopes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$sColor = match($con['status']) { 'active'=>'success','completed'=>'secondary','terminated'=>'danger', default=>'secondary' };
$typeColor = $con['type'] === 'time_based' ? 'info' : 'warning';
$typeLabel = $con['type'] === 'time_based' ? 'Time-Based' : 'Output-Based';

$contractDays = null;
if ($con['start_date'] && $con['end_date']) {
    $contractDays = (int)round((strtotime($con['end_date']) - strtotime($con['start_date'])) / 86400);
}

$pageTitle = e($con['first_name'] . ' ' . $con['last_name']) . ' — Consultant';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb" style="font-size:0.8rem;">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/consultants/index.php">Consultants</a></li>
                <li class="breadcrumb-item active"><?= e($con['first_name'].' '.$con['last_name']) ?></li>
            </ol>
        </nav>
        <h1 class="page-title mb-0"><?= e($con['first_name'].' '.$con['last_name']) ?></h1>
        <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
            <code style="font-size:0.78rem;"><?= e($con['consultant_number']) ?></code>
            <span class="badge" style="background:var(--<?=$typeColor?>-bg);color:var(--<?=$typeColor?>);border:1px solid var(--<?=$typeColor?>);font-size:0.7rem;"><?= $typeLabel ?></span>
            <span class="badge" style="background:var(--<?=$sColor?>-bg);color:var(--<?=$sColor?>);border:1px solid var(--<?=$sColor?>);font-size:0.7rem;"><?= ucfirst($con['status']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if (canEdit('consultants.edit')): ?>
        <a href="<?= APP_URL ?>/modules/consultants/edit.php?id=<?= $id ?>" class="btn btn-primary btn-sm">Edit</a>
        <?php endif; ?>
        <?php if (canDelete('consultants.delete')): ?>
        <form method="POST" action="<?= APP_URL ?>/modules/consultants/delete.php" onsubmit="return confirm('Delete this consultant?');" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/modules/consultants/index.php" class="btn btn-secondary btn-sm">← Back</a>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible">
    <?= e($flash['message']) ?><button class="btn-close" onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;margin-bottom:24px;">

    <!-- Left: profile -->
    <div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent" style="font-weight:600;font-size:0.88rem;">Personal Details</div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:0.84rem;">
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Full Name</div><div style="font-weight:600;"><?= e($con['first_name'].' '.$con['last_name']) ?></div></div>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Consultant No.</div><code><?= e($con['consultant_number']) ?></code></div>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Company</div><div><?= e($con['company'] ?? '—') ?></div></div>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Position</div><div><?= e($con['position_title'] ?? '—') ?></div></div>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Department</div><div><?= e($con['department'] ?? '—') ?></div></div>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Phone</div><div><?= e($con['phone'] ?? '—') ?></div></div>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Email</div><div><?= $con['email'] ? '<a href="mailto:'.e($con['email']).'">'.e($con['email']).'</a>' : '—' ?></div></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent" style="font-weight:600;font-size:0.88rem;">Contract Period</div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;font-size:0.84rem;">
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Start</div><div><?= $con['start_date'] ? date('d M Y', strtotime($con['start_date'])) : '—' ?></div></div>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">End</div><div><?= $con['end_date'] ? date('d M Y', strtotime($con['end_date'])) : '—' ?></div></div>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Duration</div><div><?= $contractDays !== null ? $contractDays . ' days' : '—' ?></div></div>
                    <?php if ($con['type'] === 'time_based'): ?>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Hourly Rate</div><div><?= $con['hourly_rate'] ? 'K '.number_format($con['hourly_rate'],2) : '—' ?></div></div>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Daily Rate</div><div><?= $con['daily_rate'] ? 'K '.number_format($con['daily_rate'],2) : '—' ?></div></div>
                    <?php else: ?>
                    <div><div style="font-size:0.72rem;color:var(--text-secondary);margin-bottom:2px;">Contract Value</div><div><?= $con['contract_value'] ? 'K '.number_format($con['contract_value'],2) : '—' ?></div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($con['notes']): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent" style="font-weight:600;font-size:0.88rem;">Notes</div>
            <div class="card-body" style="font-size:0.84rem;white-space:pre-wrap;"><?= e($con['notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: portal & meta -->
    <div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent" style="font-weight:600;font-size:0.88rem;">Consultant Portal</div>
            <div class="card-body" style="font-size:0.84rem;">
                <?php if ($con['portal_active']): ?>
                <span class="badge" style="background:var(--success-bg);color:var(--success);border:1px solid var(--success);">Enabled</span>
                <div style="margin-top:12px;color:var(--text-secondary);font-size:0.78rem;">Login ID</div>
                <code><?= e($con['consultant_number']) ?></code>
                <?php if ($con['portal_last_login']): ?>
                <div style="margin-top:8px;color:var(--text-muted);font-size:0.72rem;">Last login: <?= date('d M Y H:i', strtotime($con['portal_last_login'])) ?></div>
                <?php else: ?>
                <div style="margin-top:8px;color:var(--text-muted);font-size:0.72rem;">Never logged in</div>
                <?php endif; ?>
                <div style="margin-top:12px;">
                    <a href="<?= APP_URL ?>/consultant-portal/login.php" target="_blank" class="btn btn-secondary btn-sm" style="font-size:0.72rem;">Open Portal ↗</a>
                </div>
                <?php else: ?>
                <div style="color:var(--text-muted);margin-bottom:12px;">Portal access is disabled.</div>
                <?php if (canEdit('consultants.edit')): ?>
                <a href="<?= APP_URL ?>/modules/consultants/edit.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">Enable Portal Access</a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($con['type'] === 'time_based' && $attStats): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent" style="font-weight:600;font-size:0.88rem;">Attendance Summary</div>
            <div class="card-body" style="font-size:0.84rem;">
                <div style="display:grid;gap:8px;">
                    <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-secondary);">Days Worked</span><strong><?= $attStats['days'] ?></strong></div>
                    <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-secondary);">Total Hours</span><strong><?= number_format($attStats['total_h'] ?? 0, 1) ?>h</strong></div>
                    <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-secondary);">Avg Hours/Day</span><strong><?= number_format($attStats['avg_h'] ?? 0, 1) ?>h</strong></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($con['type'] === 'output_based' && $scopes): ?>
        <?php
            $total = count($scopes);
            $done  = count(array_filter($scopes, fn($s) => $s['status'] === 'completed'));
            $avgPct = $total ? round(array_sum(array_column($scopes,'completion_pct')) / $total) : 0;
        ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent" style="font-weight:600;font-size:0.88rem;">Scope Progress</div>
            <div class="card-body" style="font-size:0.84rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;"><span style="color:var(--text-secondary);">Completed</span><strong><?= $done ?>/<?= $total ?></strong></div>
                <div class="progress-bar-wrap"><div class="progress-bar-fill <?= $avgPct >= 80 ? 'success' : ($avgPct >= 40 ? '' : 'warning') ?>" style="width:<?= $avgPct ?>%"></div></div>
                <div style="text-align:right;font-size:0.72rem;color:var(--text-muted);margin-top:4px;"><?= $avgPct ?>% avg completion</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body" style="font-size:0.72rem;color:var(--text-muted);">
                <div>Added: <?= date('d M Y', strtotime($con['created_at'])) ?></div>
                <div>Updated: <?= date('d M Y H:i', strtotime($con['updated_at'])) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Type-specific section ──────────────────────────────── -->
<?php if ($con['type'] === 'time_based'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent" style="display:flex;align-items:center;justify-content:space-between;">
        <span style="font-weight:600;font-size:0.88rem;">Attendance Records (Recent 30)</span>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr><th>Date</th><th>Clock In</th><th>Break Out</th><th>Break In</th><th>Clock Out</th><th>Total Hours</th></tr>
            </thead>
            <tbody>
            <?php if (!$attendance): ?>
                <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No attendance records yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($attendance as $a): ?>
            <tr>
                <td style="font-weight:600;"><?= date('D d M Y', strtotime($a['work_date'])) ?></td>
                <td style="color:var(--success);"><?= $a['clock_in'] ? date('H:i', strtotime($a['clock_in'])) : '—' ?></td>
                <td style="color:var(--warning);"><?= $a['break_start'] ? date('H:i', strtotime($a['break_start'])) : '—' ?></td>
                <td style="color:var(--info);"><?= $a['break_end'] ? date('H:i', strtotime($a['break_end'])) : '—' ?></td>
                <td style="color:var(--danger);"><?= $a['clock_out'] ? date('H:i', strtotime($a['clock_out'])) : '—' ?></td>
                <td><?= $a['total_hours'] !== null ? number_format($a['total_hours'],2).'h' : '<span style="color:var(--text-muted)">In progress</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: /* output_based */ ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent" style="display:flex;align-items:center;justify-content:space-between;">
        <span style="font-weight:600;font-size:0.88rem;">Scope of Work Checklist</span>
        <?php if (canEdit('consultants.edit')): ?>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('addScopeForm').style.display=document.getElementById('addScopeForm').style.display==='none'?'block':'none'">+ Add Item</button>
        <?php endif; ?>
    </div>

    <?php if (canEdit('consultants.edit')): ?>
    <!-- Add scope item form -->
    <div id="addScopeForm" style="display:none;padding:16px;border-bottom:1px solid var(--border);background:var(--bg);">
        <form method="POST" action="<?= APP_URL ?>/modules/consultants/scope_save.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="consultant_id" value="<?= $id ?>">
            <div style="display:grid;grid-template-columns:1fr auto auto auto;gap:10px;align-items:end;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.75rem;">Title <span style="color:var(--danger)">*</span></label>
                    <input type="text" class="form-control" name="title" required placeholder="Scope item title">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.75rem;">Priority</label>
                    <select class="form-select" name="priority">
                        <option value="low">Low</option><option value="normal" selected>Normal</option>
                        <option value="high">High</option><option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.75rem;">Due Date</label>
                    <input type="date" class="form-control" name="due_date">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="height:38px;">Add</button>
            </div>
            <div class="form-group" style="margin-top:10px;margin-bottom:0;">
                <label class="form-label" style="font-size:0.75rem;">Description</label>
                <textarea class="form-control" name="description" rows="2" placeholder="Optional description of the deliverable"></textarea>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div style="padding:0;">
        <?php if (!$scopes): ?>
        <div style="text-align:center;padding:32px;color:var(--text-muted);font-size:0.84rem;">No scope items yet. Click "Add Item" to begin.</div>
        <?php endif; ?>
        <?php foreach ($scopes as $s): ?>
        <?php
            $pBadge = match($s['priority']) { 'urgent'=>['danger','Urgent'], 'high'=>['warning','High'], 'low'=>['secondary','Low'], default=>['info','Normal'] };
            $sBadge = match($s['status']) { 'completed'=>['success','Completed'], 'in_progress'=>['info','In Progress'], 'on_hold'=>['warning','On Hold'], default=>['secondary','Pending'] };
        ?>
        <div style="padding:16px 20px;border-bottom:1px solid var(--border-light);">
            <div style="display:flex;align-items:flex-start;gap:12px;">
                <!-- Checkbox indicator -->
                <div style="margin-top:2px;width:18px;height:18px;border-radius:4px;border:2px solid var(--<?=$sBadge[0]?>);display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?= $s['status']==='completed'?'var(--success)':'transparent' ?>;">
                    <?php if ($s['status'] === 'completed'): ?><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg><?php endif; ?>
                </div>
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                        <span style="font-weight:600;font-size:0.84rem;<?= $s['status']==='completed'?'text-decoration:line-through;color:var(--text-muted)':'' ?>"><?= e($s['title']) ?></span>
                        <span class="badge" style="background:var(--<?=$pBadge[0]?>-bg);color:var(--<?=$pBadge[0]?>);border:1px solid var(--<?=$pBadge[0]?>);font-size:0.65rem;"><?= $pBadge[1] ?></span>
                        <span class="badge" style="background:var(--<?=$sBadge[0]?>-bg);color:var(--<?=$sBadge[0]?>);border:1px solid var(--<?=$sBadge[0]?>);font-size:0.65rem;"><?= $sBadge[1] ?></span>
                        <?php if ($s['due_date']): ?>
                        <span style="font-size:0.72rem;color:var(--text-muted);">Due: <?= date('d M Y', strtotime($s['due_date'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($s['description']): ?>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-bottom:6px;"><?= e($s['description']) ?></div>
                    <?php endif; ?>
                    <!-- Progress bar -->
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <div class="progress-bar-wrap" style="flex:1;"><div class="progress-bar-fill <?= $s['completion_pct']>=80?'success':($s['completion_pct']>=40?'':'warning') ?>" style="width:<?= $s['completion_pct'] ?>%"></div></div>
                        <span style="font-size:0.72rem;color:var(--text-muted);width:32px;text-align:right;"><?= $s['completion_pct'] ?>%</span>
                    </div>
                    <?php if ($s['hr_notes']): ?>
                    <div style="font-size:0.75rem;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:6px 10px;margin-bottom:6px;"><span style="color:var(--text-muted);">HR Notes: </span><?= e($s['hr_notes']) ?></div>
                    <?php endif; ?>
                    <?php if ($s['consultant_notes']): ?>
                    <div style="font-size:0.75rem;background:var(--primary-light,#EFF6FF);border:1px solid var(--primary);border-radius:6px;padding:6px 10px;"><span style="color:var(--primary);font-weight:600;">Consultant Notes: </span><?= e($s['consultant_notes']) ?></div>
                    <?php endif; ?>
                </div>

                <?php if (canEdit('consultants.edit')): ?>
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <button class="btn btn-secondary btn-sm" style="font-size:0.7rem;" onclick="toggleEditScope(<?= $s['id'] ?>)">Edit</button>
                    <form method="POST" action="<?= APP_URL ?>/modules/consultants/scope_save.php" onsubmit="return confirm('Delete this scope item?');" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="scope_id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="consultant_id" value="<?= $id ?>">
                        <button type="submit" class="btn btn-danger btn-sm" style="font-size:0.7rem;">Del</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <?php if (canEdit('consultants.edit')): ?>
            <!-- Inline edit form -->
            <div id="editScope<?= $s['id'] ?>" style="display:none;margin-top:12px;padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:8px;">
                <form method="POST" action="<?= APP_URL ?>/modules/consultants/scope_save.php">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="scope_id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="consultant_id" value="<?= $id ?>">
                    <div style="display:grid;grid-template-columns:1fr auto auto auto auto;gap:8px;align-items:end;margin-bottom:8px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.72rem;">Title</label>
                            <input type="text" class="form-control" name="title" value="<?= e($s['title']) ?>" required>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.72rem;">Priority</label>
                            <select class="form-select" name="priority">
                                <?php foreach (['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= $s['priority']===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.72rem;">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach (['pending'=>'Pending','in_progress'=>'In Progress','completed'=>'Completed','on_hold'=>'On Hold'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= $s['status']===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.72rem;">% Complete</label>
                            <input type="number" class="form-control" name="completion_pct" min="0" max="100" value="<?= $s['completion_pct'] ?>" style="width:70px;">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:0.72rem;">Due Date</label>
                            <input type="date" class="form-control" name="due_date" value="<?= e($s['due_date']??'') ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="form-label" style="font-size:0.72rem;">Description</label>
                        <textarea class="form-control" name="description" rows="2"><?= e($s['description']??'') ?></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="form-label" style="font-size:0.72rem;">HR Notes</label>
                        <textarea class="form-control" name="hr_notes" rows="2"><?= e($s['hr_notes']??'') ?></textarea>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEditScope(<?= $s['id'] ?>)">Cancel</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleEditScope(id) {
    const el = document.getElementById('editScope' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php endif; ?>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
