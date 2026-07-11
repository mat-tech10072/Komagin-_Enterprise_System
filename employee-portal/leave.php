<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';
epRequireLogin();

$emp   = epCurrentEmployee();
$empId = $emp['id'];
$year  = (int)($_GET['year'] ?? date('Y'));

// Leave balances
$balances = db()->prepare("SELECT lb.*, lt.name, lt.is_paid, lt.code
    FROM leave_balances lb
    JOIN leave_types lt ON lb.leave_type_id=lt.id
    WHERE lb.employee_id=? AND lb.year=?
    ORDER BY lt.name");
$balances->execute([$empId, $year]);
$balances = $balances->fetchAll(PDO::FETCH_ASSOC);

// Leave applications
$apps = db()->prepare("SELECT la.*, lt.name as leave_type_name, lt.is_paid
    FROM leave_applications la
    JOIN leave_types lt ON la.leave_type_id=lt.id
    WHERE la.employee_id=?
    ORDER BY la.created_at DESC LIMIT 30");
$apps->execute([$empId]);
$applications = $apps->fetchAll(PDO::FETCH_ASSOC);

$statusColors = ['pending'=>'amber','approved'=>'green','rejected'=>'red','cancelled'=>''];
$statusLabels = ['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'];

epLayoutStart('My Leave', 'leave');
?>

<div style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
    <h2 style="font-size:1rem;font-weight:700;margin:0;">Leave Balances — <?= $year ?></h2>
    <div style="display:flex;gap:6px;align-items:center;">
        <a href="?year=<?= $year-1 ?>" class="btn btn-ghost btn-sm">← <?= $year-1 ?></a>
        <a href="?year=<?= $year+1 ?>" class="btn btn-ghost btn-sm"><?= $year+1 ?> →</a>
    </div>
</div>

<!-- Balance Cards -->
<?php if ($balances): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:24px;">
    <?php foreach ($balances as $b):
        $pct = $b['entitled_days'] > 0 ? ($b['remaining_days'] / $b['entitled_days'] * 100) : 0;
        $color = $pct >= 50 ? '#16a34a' : ($pct >= 25 ? '#d97706' : '#dc2626');
    ?>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:8px;"><?= htmlspecialchars($b['name']) ?></div>
        <div style="font-size:2rem;font-weight:800;color:<?= $color ?>;"><?= number_format($b['remaining_days'],1) ?></div>
        <div style="font-size:0.7rem;color:var(--text-muted);margin-bottom:10px;">of <?= number_format($b['entitled_days'],1) ?> days</div>
        <div style="height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden;">
            <div style="width:<?= min(100,$pct) ?>%;height:100%;background:<?= $color ?>;border-radius:2px;"></div>
        </div>
        <div style="font-size:0.65rem;color:var(--text-muted);margin-top:6px;">
            Used: <?= number_format($b['used_days'],1) ?> · <?= $b['is_paid'] ? 'Paid' : 'Unpaid' ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card" style="padding:32px;text-align:center;color:var(--text-muted);margin-bottom:24px;">
    <div style="font-size:0.85rem;">No leave balances found for <?= $year ?>.</div>
    <div style="font-size:0.75rem;margin-top:8px;">Contact HR to set up your leave entitlements.</div>
</div>
<?php endif; ?>

<!-- Applications History -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Leave History</span>
        <span style="font-size:0.72rem;color:var(--text-muted);"><?= count($applications) ?> records</span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if ($applications): ?>
        <table style="width:100%;border-collapse:collapse;font-size:0.78rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 16px;text-align:left;font-weight:600;font-size:0.7rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Type</th>
                    <th style="padding:10px 16px;text-align:left;">From</th>
                    <th style="padding:10px 16px;text-align:left;">To</th>
                    <th style="padding:10px 16px;text-align:center;">Days</th>
                    <th style="padding:10px 16px;text-align:left;">Status</th>
                    <th style="padding:10px 16px;text-align:left;">Applied</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $a): ?>
            <tr style="border-top:1px solid #f1f5f9;">
                <td style="padding:10px 16px;font-weight:600;"><?= htmlspecialchars($a['leave_type_name']) ?></td>
                <td style="padding:10px 16px;"><?= date('d M Y',strtotime($a['start_date'])) ?></td>
                <td style="padding:10px 16px;"><?= date('d M Y',strtotime($a['end_date'])) ?></td>
                <td style="padding:10px 16px;text-align:center;font-weight:600;"><?= $a['total_days'] ?></td>
                <td style="padding:10px 16px;">
                    <span class="badge status-<?= $a['status'] ?>"><?= $statusLabels[$a['status']] ?? ucfirst($a['status']) ?></span>
                </td>
                <td style="padding:10px 16px;color:#64748b;font-size:0.72rem;"><?= date('d M Y',strtotime($a['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="padding:32px;text-align:center;color:var(--text-muted);">
            <div style="font-size:0.85rem;">No leave applications on record.</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php epLayoutEnd(); ?>
