<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';
epRequireLogin();

$emp   = epCurrentEmployee();
$empId = $emp['id'];
$year  = date('Y');

// Leave balances
$lbStmt = db()->prepare("SELECT lb.*, lt.name as leave_type_name
    FROM leave_balances lb JOIN leave_types lt ON lb.leave_type_id=lt.id
    WHERE lb.employee_id=? AND lb.year=?");
$lbStmt->execute([$empId, $year]);
$leaveBalances = $lbStmt->fetchAll(PDO::FETCH_ASSOC);

epLayoutStart('My Employment', 'employment');
?>

<div class="page-header">
    <div>
        <div class="page-title">Employment Details</div>
        <div class="page-sub">Your current employment information on record</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <!-- Personal Information -->
    <div class="card">
        <div class="card-header"><span class="card-title">Personal Information</span></div>
        <div class="card-body" style="padding:0">
            <?php
            $personal = [
                'First Name'    => $emp['first_name'] ?? '',
                'Last Name'     => $emp['last_name']  ?? '',
                'Date of Birth' => $emp['date_of_birth'] ? date('d M Y', strtotime($emp['date_of_birth'])) : '—',
                'Gender'        => ucfirst($emp['gender'] ?? '—'),
                'Marital Status'=> ucfirst(str_replace('_',' ',$emp['marital_status'] ?? '—')),
                'National ID'   => $emp['national_id'] ? '****' . substr($emp['national_id'], -4) : '—',
                'Nationality'   => $emp['nationality'] ?? '—',
            ];
            foreach ($personal as $label => $value):
            ?>
            <div style="padding:11px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:0.72rem;font-weight:600;text-transform:uppercase;color:var(--text-muted)"><?= $label ?></span>
                <span style="font-size:0.82rem;font-weight:500"><?= htmlspecialchars($value) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Employment Information -->
    <div class="card">
        <div class="card-header"><span class="card-title">Employment Information</span></div>
        <div class="card-body" style="padding:0">
            <?php
            $employment = [
                'Employee No.'       => $emp['employee_number'] ?? '—',
                'Department'         => $emp['dept_name'] ?? '—',
                'Position'           => $emp['position_title'] ?? '—',
                'Employment Type'    => ucfirst(str_replace('_',' ',$emp['employment_type'] ?? '—')),
                'Start Date'         => !empty($emp['start_date']) ? date('d M Y', strtotime($emp['start_date'])) : 'Not recorded',
                'Probation End'      => !empty($emp['probation_end']) ? date('d M Y', strtotime($emp['probation_end'])) : 'N/A',
                'Work Location'      => $emp['work_location'] ?? '—',
                'Status'             => ucfirst($emp['status'] ?? '—'),
            ];
            foreach ($employment as $label => $value):
            ?>
            <div style="padding:11px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:0.72rem;font-weight:600;text-transform:uppercase;color:var(--text-muted)"><?= $label ?></span>
                <span style="font-size:0.82rem;font-weight:500"><?= htmlspecialchars($value) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="card">
        <div class="card-header"><span class="card-title">Contact Details</span></div>
        <div class="card-body" style="padding:0">
            <?php
            $contact = [
                'Phone'          => $emp['phone'] ?? '—',
                'Alt Phone'      => $emp['phone_alt'] ?? '—',
                'Work Email'     => $emp['email'] ?? '—',
                'Personal Email' => $emp['personal_email'] ?? '—',
                'Address'        => $emp['residential_address'] ?? '—',
                'City'           => $emp['city'] ?? '—',
                'Country'        => $emp['country'] ?? '—',
            ];
            foreach ($contact as $label => $value):
            ?>
            <div style="padding:11px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:0.72rem;font-weight:600;text-transform:uppercase;color:var(--text-muted)"><?= $label ?></span>
                <span style="font-size:0.82rem;font-weight:500"><?= htmlspecialchars($value) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Banking Details -->
    <div class="card">
        <div class="card-header"><span class="card-title">Banking Details</span></div>
        <div class="card-body" style="padding:0">
            <?php
            $bank = [
                'Bank Name'        => $emp['bank_name'] ?? '—',
                'Account Holder'   => trim(($emp['first_name']??'').' '.($emp['last_name']??'')) ?: '—',
                'Account Number'   => !empty($emp['bank_account_number']) ? '****' . substr($emp['bank_account_number'],-4) : '—',
                'Branch Code'      => $emp['bank_branch_code'] ?? '—',
                'Account Type'     => ucfirst($emp['bank_account_type'] ?? '—'),
            ];
            foreach ($bank as $label => $value):
            ?>
            <div style="padding:11px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:0.72rem;font-weight:600;text-transform:uppercase;color:var(--text-muted)"><?= $label ?></span>
                <span style="font-size:0.82rem;font-weight:500"><?= htmlspecialchars($value) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer">
            <div class="alert alert-info" style="margin:0;padding:8px 12px;font-size:0.72rem">
                To update banking details, submit a <strong>Bank Update</strong> request via the Hub.
            </div>
        </div>
    </div>
</div>

<!-- Leave Balances -->
<div class="card" style="margin-top:16px">
    <div class="card-header">
        <span class="card-title">Leave Balances — <?= $year ?></span>
        <a href="<?= EP_URL ?>/hub.php?type=leave_query&new=1" class="btn btn-ghost btn-sm">Query Leave</a>
    </div>
    <?php if ($leaveBalances): ?>
    <div class="table-wrap">
        <table class="ep-table">
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Entitled</th>
                    <th>Used</th>
                    <th>Pending</th>
                    <th>Remaining</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaveBalances as $lb): ?>
                <?php
                $pct = $lb['entitled_days'] > 0
                    ? (($lb['entitled_days'] - $lb['remaining_days']) / $lb['entitled_days']) * 100
                    : 0;
                $barColor = $pct > 80 ? 'red' : ($pct > 50 ? 'amber' : 'green');
                ?>
                <tr>
                    <td><?= htmlspecialchars($lb['leave_type_name']) ?></td>
                    <td><?= $lb['entitled_days'] ?> days</td>
                    <td><?= $lb['used_days'] ?> days</td>
                    <td><?= $lb['pending_days'] ?? 0 ?> days</td>
                    <td><strong><?= $lb['remaining_days'] ?> days</strong></td>
                    <td style="min-width:120px">
                        <div class="progress-wrap">
                            <div class="progress-bar <?= $barColor ?>" style="width:<?= min(100,$pct) ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-title">No leave balances loaded for <?= $year ?></div>
        </div>
    <?php endif; ?>
</div>

<?php epLayoutEnd(); ?>
