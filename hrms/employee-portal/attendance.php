<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';
epRequireLogin();

$emp   = epCurrentEmployee();
$empId = $emp['id'];

$month = (int)($_GET['month'] ?? date('n'));
$year  = (int)($_GET['year']  ?? date('Y'));
$month = max(1, min(12, $month));

// Monthly attendance
$att = db()->prepare("SELECT * FROM attendance WHERE employee_id=? AND MONTH(attendance_date)=? AND YEAR(attendance_date)=?
    ORDER BY attendance_date DESC");
$att->execute([$empId, $month, $year]);
$records = $att->fetchAll(PDO::FETCH_ASSOC);

// Summary
$total     = count($records);
$present   = count(array_filter($records, fn($r) => !$r['is_absent']));
$absent    = count(array_filter($records, fn($r) => $r['is_absent']));
$late      = count(array_filter($records, fn($r) => $r['is_late']));
$totalHrs  = array_sum(array_column($records,'total_hours_worked'));
$totalOT   = array_sum(array_column($records,'overtime_hours'));

$months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

// Prev/Next navigation
$prevMonth = $month === 1 ? 12 : $month - 1;
$prevYear  = $month === 1 ? $year - 1 : $year;
$nextMonth = $month === 12 ? 1 : $month + 1;
$nextYear  = $month === 12 ? $year + 1 : $year;

epLayoutStart('My Attendance', 'attendance');
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <h2 style="font-size:1rem;font-weight:700;margin:0;">Attendance — <?= $months[$month] ?> <?= $year ?></h2>
    <div style="display:flex;gap:6px;">
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-ghost btn-sm">← Prev</a>
        <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn btn-ghost btn-sm">This Month</a>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-ghost btn-sm">Next →</a>
    </div>
</div>

<!-- Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px;">
    <?php $kpis = [
        ['Days', $total, '#1D4ED8'],
        ['Present', $present, '#16a34a'],
        ['Absent', $absent, '#dc2626'],
        ['Late', $late, '#d97706'],
        ['OT Hrs', number_format($totalOT,1), '#7c3aed'],
    ]; ?>
    <?php foreach ($kpis as [$l,$v,$c]): ?>
    <div class="card" style="padding:14px;text-align:center;border-top:3px solid <?= $c ?>;">
        <div style="font-size:1.4rem;font-weight:800;color:<?= $c ?>;"><?= $v ?></div>
        <div style="font-size:0.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-top:4px;"><?= $l ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Attendance Records -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Daily Records</span>
        <span style="font-size:0.72rem;color:var(--text-muted);">Total hours: <?= number_format($totalHrs,1) ?>h</span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if ($records): ?>
        <table style="width:100%;border-collapse:collapse;font-size:0.78rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 16px;text-align:left;">Date</th>
                    <th style="padding:10px 16px;text-align:center;">Sign In</th>
                    <th style="padding:10px 16px;text-align:center;">Break</th>
                    <th style="padding:10px 16px;text-align:center;">Sign Out</th>
                    <th style="padding:10px 16px;text-align:center;">Hours</th>
                    <th style="padding:10px 16px;text-align:center;">OT</th>
                    <th style="padding:10px 16px;text-align:left;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $r): ?>
            <tr style="border-top:1px solid #f1f5f9;<?= $r['is_absent']?'background:#fff5f5;':($r['is_late']?'background:#fffbeb;':'') ?>">
                <td style="padding:10px 16px;">
                    <div style="font-weight:600;"><?= date('D d', strtotime($r['attendance_date'])) ?></div>
                    <div style="font-size:0.68rem;color:#64748b;"><?= date('M Y', strtotime($r['attendance_date'])) ?></div>
                </td>
                <td style="padding:10px 16px;text-align:center;"><?= $r['sign_in'] ? date('h:i A',strtotime($r['sign_in'])) : '—' ?></td>
                <td style="padding:10px 16px;text-align:center;font-size:0.72rem;color:#64748b;">
                    <?= ($r['break_out']&&$r['break_in']) ? $r['break_duration_minutes'].'m' : ($r['break_out']?'On break':'—') ?>
                </td>
                <td style="padding:10px 16px;text-align:center;"><?= $r['sign_out'] ? date('h:i A',strtotime($r['sign_out'])) : '—' ?></td>
                <td style="padding:10px 16px;text-align:center;font-weight:600;"><?= $r['total_hours_worked'] ? number_format($r['total_hours_worked'],1).'h' : '—' ?></td>
                <td style="padding:10px 16px;text-align:center;color:<?= $r['overtime_hours']>0?'#7c3aed':'#94a3b8' ?>;">
                    <?= $r['overtime_hours'] > 0 ? '+'.$r['overtime_hours'].'h' : '—' ?>
                </td>
                <td style="padding:10px 16px;">
                    <?php if ($r['is_absent']): ?>
                    <span style="font-size:0.7rem;font-weight:600;color:#dc2626;">Absent</span>
                    <?php elseif ($r['is_on_leave']): ?>
                    <span style="font-size:0.7rem;font-weight:600;color:#0284c7;">On Leave</span>
                    <?php elseif ($r['is_late']): ?>
                    <span style="font-size:0.7rem;font-weight:600;color:#d97706;">Late <?= $r['late_minutes'] ?>m</span>
                    <?php else: ?>
                    <span style="font-size:0.7rem;font-weight:600;color:#16a34a;">Present</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="padding:32px;text-align:center;color:var(--text-muted);">
            <div style="font-size:0.85rem;">No attendance records for <?= $months[$month] ?> <?= $year ?>.</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php epLayoutEnd(); ?>
