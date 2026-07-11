<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';

cpRequireLogin();
cpRequireType('output_based');

$con = cpCurrentConsultant();
$cid = $con['id'];

// Handle POST — consultant can only update their own notes per scope item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scopeId      = (int)($_POST['scope_id'] ?? 0);
    $consultantNote = trim($_POST['consultant_notes'] ?? '');

    if ($scopeId) {
        // Verify this scope item belongs to this consultant
        $check = db()->prepare("SELECT id FROM consultant_scopes WHERE id = ? AND consultant_id = ? LIMIT 1");
        $check->execute([$scopeId, $cid]);
        if ($check->fetchColumn()) {
            db()->prepare("UPDATE consultant_scopes SET consultant_notes = ? WHERE id = ? AND consultant_id = ?")
                ->execute([$consultantNote ?: null, $scopeId, $cid]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Your notes have been saved.'];
        }
    }

    header('Location: ' . CP_URL . '/scope.php');
    exit;
}

// Fetch all scope items
$items = db()->prepare("SELECT * FROM consultant_scopes WHERE consultant_id = ? ORDER BY sort_order, id");
$items->execute([$cid]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
$total     = count($items);
$done      = count(array_filter($items, fn($i) => $i['status'] === 'completed'));
$inProg    = count(array_filter($items, fn($i) => $i['status'] === 'in_progress'));
$avgPct    = $total ? round(array_sum(array_column($items, 'completion_pct')) / $total) : 0;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$priorityLabels = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];
$statusLabels   = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'on_hold' => 'On Hold'];
$statusBadge    = ['pending' => 'badge-secondary', 'in_progress' => 'badge-primary', 'completed' => 'badge-success', 'on_hold' => 'badge-warning'];
$priorityBadge  = ['low' => 'badge-secondary', 'normal' => 'badge-info', 'high' => 'badge-warning', 'urgent' => 'badge-danger'];

$pageTitle = 'My Scope of Work';
cpLayoutStart($pageTitle, 'scope');
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<!-- Progress Overview -->
<div class="cp-kpi-grid" style="margin-bottom:24px;">
    <div class="cp-kpi">
        <div class="cp-kpi-label">Total Items</div>
        <div class="cp-kpi-value"><?= $total ?></div>
    </div>
    <div class="cp-kpi">
        <div class="cp-kpi-label">Completed</div>
        <div class="cp-kpi-value"><?= $done ?></div>
        <div class="cp-kpi-sub">of <?= $total ?> items</div>
    </div>
    <div class="cp-kpi">
        <div class="cp-kpi-label">In Progress</div>
        <div class="cp-kpi-value"><?= $inProg ?></div>
    </div>
    <div class="cp-kpi">
        <div class="cp-kpi-label">Overall Progress</div>
        <div class="cp-kpi-value"><?= $avgPct ?>%</div>
    </div>
</div>

<!-- Overall progress bar -->
<?php if ($total): ?>
<div style="margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;font-size:0.72rem;color:var(--cp-text-muted);margin-bottom:6px;">
        <span>Overall Completion</span>
        <span><?= $avgPct ?>%</span>
    </div>
    <div class="progress-wrap" style="height:12px;">
        <div class="progress-bar <?= $avgPct >= 100 ? 'green' : ($avgPct >= 50 ? '' : 'amber') ?>" style="width:<?= $avgPct ?>%;"></div>
    </div>
</div>
<?php endif; ?>

<!-- Scope Items -->
<?php if (!$items): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:40px;color:var(--cp-text-muted);">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:.4;"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <div style="font-weight:600;margin-bottom:4px;">No scope items yet</div>
        <div style="font-size:0.78rem;">Your HR manager will add your scope of work items. Check back later.</div>
    </div>
</div>
<?php else: ?>

<?php foreach ($items as $item): ?>
<div class="cp-scope-item <?= $item['status'] ?>">
    <div class="cp-scope-header">
        <div style="flex:1;">
            <div class="cp-scope-title"><?= htmlspecialchars($item['title']) ?></div>
            <?php if ($item['description']): ?>
            <div class="cp-scope-desc" style="margin-top:4px;"><?= nl2br(htmlspecialchars($item['description'])) ?></div>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0;align-items:center;">
            <span class="badge <?= $priorityBadge[$item['priority']] ?? 'badge-secondary' ?>"><?= $priorityLabels[$item['priority']] ?? $item['priority'] ?></span>
            <span class="badge <?= $statusBadge[$item['status']] ?? 'badge-secondary' ?>"><?= $statusLabels[$item['status']] ?? $item['status'] ?></span>
        </div>
    </div>

    <!-- Progress bar -->
    <div style="margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;font-size:0.65rem;color:var(--cp-text-muted);margin-bottom:4px;">
            <span>Completion</span>
            <span><?= $item['completion_pct'] ?>%</span>
        </div>
        <div class="progress-wrap" style="height:7px;">
            <div class="progress-bar <?= $item['completion_pct'] >= 100 ? 'green' : ($item['completion_pct'] >= 50 ? '' : 'amber') ?>" style="width:<?= $item['completion_pct'] ?>%;"></div>
        </div>
    </div>

    <div class="cp-scope-meta" style="margin-bottom:12px;">
        <?php if ($item['due_date']): ?>
        <span style="font-size:0.72rem;color:var(--cp-text-muted);">
            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Due: <?= date('d M Y', strtotime($item['due_date'])) ?>
        </span>
        <?php endif; ?>
        <?php if ($item['completed_at']): ?>
        <span style="font-size:0.72rem;color:#16A34A;">
            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:3px;"><polyline points="20 6 9 17 4 12"/></svg>
            Completed: <?= date('d M Y', strtotime($item['completed_at'])) ?>
        </span>
        <?php endif; ?>
    </div>

    <!-- HR Notes (read-only) -->
    <?php if ($item['hr_notes']): ?>
    <div style="background:var(--cp-bg);border-radius:6px;padding:10px 12px;margin-bottom:12px;border-left:3px solid #64748B;">
        <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cp-text-muted);margin-bottom:4px;">HR Notes</div>
        <div style="font-size:0.78rem;color:var(--cp-text);"><?= nl2br(htmlspecialchars($item['hr_notes'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Consultant Notes (editable) -->
    <div style="border-top:1px solid var(--cp-border);padding-top:12px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <div style="font-size:0.72rem;font-weight:600;color:var(--cp-text-muted);">My Notes</div>
            <button type="button" onclick="toggleNote(<?= $item['id'] ?>)" class="btn btn-secondary btn-sm">
                Edit Notes
            </button>
        </div>
        <?php if ($item['consultant_notes']): ?>
        <div id="note-display-<?= $item['id'] ?>" style="font-size:0.82rem;color:var(--cp-text);white-space:pre-wrap;"><?= htmlspecialchars($item['consultant_notes']) ?></div>
        <?php else: ?>
        <div id="note-display-<?= $item['id'] ?>" style="font-size:0.78rem;color:var(--cp-text-muted);font-style:italic;">No notes added yet. Click "Edit Notes" to add your notes.</div>
        <?php endif; ?>

        <form method="POST" id="note-form-<?= $item['id'] ?>" style="display:none;margin-top:8px;">
            <input type="hidden" name="scope_id" value="<?= $item['id'] ?>">
            <textarea name="consultant_notes" class="form-control" rows="3" style="margin-bottom:8px;"><?= htmlspecialchars($item['consultant_notes'] ?? '') ?></textarea>
            <div style="display:flex;gap:6px;">
                <button type="submit" class="btn btn-primary btn-sm">Save Notes</button>
                <button type="button" onclick="toggleNote(<?= $item['id'] ?>)" class="btn btn-secondary btn-sm">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
function toggleNote(id) {
    const display = document.getElementById('note-display-' + id);
    const form    = document.getElementById('note-form-'    + id);
    const hidden  = form.style.display === 'none';
    form.style.display    = hidden ? '' : 'none';
    display.style.display = hidden ? 'none' : '';
}
</script>

<?php cpLayoutEnd(); ?>
