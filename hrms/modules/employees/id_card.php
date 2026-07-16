<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('employees.view', 'view');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$emp = getEmployee($id);
if (!$emp) { setFlash('error', 'Employee not found.'); header('Location: ' . APP_URL . '/modules/employees/index.php'); exit; }

$settings = getCompanySettings();
$companyName = $settings['company_name'] ?? 'Komagin Limited';
$companyPhone = $settings['phone'] ?? '';
$companyEmail = $settings['email'] ?? '';

auditLog('employees', 'generate_id_card', $id, null, null, "ID card generated for {$emp['employee_number']}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card — <?= e($emp['first_name'].' '.$emp['last_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 32px;
            gap: 24px;
        }
        .controls {
            display: flex;
            gap: 12px;
        }
        .controls button {
            padding: 8px 20px;
            border-radius: 8px;
            border: 1px solid #CBD5E1;
            background: white;
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            color: #1e293b;
        }
        .controls button.primary { background: #1D4ED8; color: white; border-color: #1D4ED8; }

        /* ID Card */
        .card-wrapper {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .id-card {
            width: 340px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,.15);
            font-family: 'Inter', sans-serif;
        }

        /* Front */
        .card-front {
            background: linear-gradient(135deg, #1D4ED8 0%, #1e40af 60%, #1e3a8a 100%);
            color: white;
            position: relative;
        }
        .card-front-header {
            padding: 20px 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .company-name {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            opacity: .85;
        }
        .card-type {
            font-size: 0.6rem;
            font-weight: 600;
            background: rgba(255,255,255,.15);
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .card-front-body {
            padding: 20px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .card-photo {
            width: 76px;
            height: 90px;
            border-radius: 8px;
            background: rgba(255,255,255,.15);
            border: 2px solid rgba(255,255,255,.3);
            flex-shrink: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            color: rgba(255,255,255,.7);
        }
        .card-photo img { width: 100%; height: 100%; object-fit: cover; }
        .card-info { flex: 1; }
        .card-name {
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        .card-position {
            font-size: 0.7rem;
            opacity: .8;
            margin-bottom: 12px;
        }
        .card-detail {
            font-size: 0.62rem;
            opacity: .75;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 1px;
        }
        .card-detail-val {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .card-front-footer {
            background: rgba(0,0,0,.25);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .emp-number-badge {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: .08em;
            font-family: 'Courier New', monospace;
        }
        .card-status-badge {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 2px 8px;
            border-radius: 4px;
            background: rgba(34,197,94,.3);
            border: 1px solid rgba(34,197,94,.5);
            color: #86efac;
        }

        /* Decorative elements */
        .card-circles {
            position: absolute;
            top: 0; right: 0;
            width: 120px; height: 120px;
            pointer-events: none;
        }
        .card-circles circle { fill: rgba(255,255,255,.06); }

        /* Back */
        .card-back {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
        }
        .card-back-stripe {
            background: #1D4ED8;
            height: 40px;
        }
        .card-back-body { padding: 16px 20px; }
        .card-back-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #F1F5F9;
            font-size: 0.72rem;
        }
        .card-back-row:last-child { border-bottom: none; }
        .card-back-label { color: #64748B; font-weight: 500; }
        .card-back-val { font-weight: 600; color: #1e293b; text-align: right; }
        .card-back-footer {
            padding: 12px 20px;
            background: #F1F5F9;
            font-size: 0.65rem;
            color: #64748B;
            text-align: center;
            border-top: 1px solid #E2E8F0;
        }
        .signature-line {
            border-top: 1px solid #CBD5E1;
            margin: 16px 20px 0;
            padding-top: 4px;
            font-size: 0.62rem;
            color: #94A3B8;
            text-align: center;
        }

        @media print {
            body { background: white; padding: 0; gap: 0; }
            .controls { display: none; }
            .card-wrapper { gap: 8px; }
            .id-card { box-shadow: none; border: 1px solid #E2E8F0; }
        }
    </style>
</head>
<body>

<div class="controls">
    <button onclick="window.print()" class="primary">Print / Save as PDF</button>
    <button onclick="window.close()">Close</button>
    <a href="<?= APP_URL ?>/modules/employees/view.php?id=<?= $id ?>" style="padding:8px 20px;border-radius:8px;border:1px solid #CBD5E1;background:white;font-family:Inter;font-size:0.82rem;font-weight:600;cursor:pointer;color:#1e293b;text-decoration:none;">← Back to Profile</a>
</div>

<div class="card-wrapper">

    <!-- FRONT -->
    <div class="id-card">
        <div class="card-front">
            <svg class="card-circles" viewBox="0 0 120 120"><circle cx="110" cy="10" r="80"/><circle cx="110" cy="10" r="50"/></svg>
            <div class="card-front-header">
                <div class="company-name"><?= e($companyName) ?></div>
                <div class="card-type">Staff ID</div>
            </div>
            <div class="card-front-body">
                <div class="card-photo">
                    <?php if (!empty($emp['photo'])): ?>
                    <img src="<?= APP_URL ?>/<?= e($emp['photo']) ?>" alt="">
                    <?php else: ?>
                    <?= strtoupper(substr($emp['first_name'],0,1)) ?>
                    <?php endif; ?>
                </div>
                <div class="card-info">
                    <div class="card-name"><?= e($emp['first_name'].' '.$emp['last_name']) ?></div>
                    <div class="card-position"><?= e($emp['position_title'] ?? $emp['employment_type']) ?></div>
                    <div class="card-detail">Department</div>
                    <div class="card-detail-val"><?= e($emp['department_name'] ?? '—') ?></div>
                    <div class="card-detail">Work Location</div>
                    <div class="card-detail-val"><?= e($emp['work_location'] ?? 'Main Office') ?></div>
                </div>
            </div>
            <div class="card-front-footer">
                <div class="emp-number-badge"><?= e($emp['employee_number']) ?></div>
                <div class="card-status-badge"><?= ucfirst($emp['status']) ?></div>
            </div>
        </div>
    </div>

    <!-- BACK -->
    <div class="id-card">
        <div class="card-back">
            <div class="card-back-stripe"></div>
            <div class="card-back-body">
                <div class="card-back-row">
                    <span class="card-back-label">Employee Number</span>
                    <span class="card-back-val" style="font-family:monospace;"><?= e($emp['employee_number']) ?></span>
                </div>
                <div class="card-back-row">
                    <span class="card-back-label">Employment Type</span>
                    <span class="card-back-val"><?= ucfirst(str_replace('_',' ',$emp['employment_type'])) ?></span>
                </div>
                <div class="card-back-row">
                    <span class="card-back-label">Start Date</span>
                    <span class="card-back-val"><?= formatDate($emp['start_date']) ?></span>
                </div>
                <div class="card-back-row">
                    <span class="card-back-label">Work Email</span>
                    <span class="card-back-val" style="font-size:0.65rem;"><?= e($emp['email'] ?? '—') ?></span>
                </div>
                <div class="card-back-row">
                    <span class="card-back-label">Emergency Contact</span>
                    <span class="card-back-val"><?= e($emp['emergency_contact_name'] ?? '—') ?></span>
                </div>
                <div class="card-back-row">
                    <span class="card-back-label">Emergency Phone</span>
                    <span class="card-back-val"><?= e($emp['emergency_contact_phone'] ?? '—') ?></span>
                </div>
            </div>
            <div class="signature-line">Authorised Signature</div>
            <div style="height:32px;"></div>
            <div class="card-back-footer">
                <?php if ($companyEmail): ?><?= e($companyEmail) ?><?php endif; ?>
                <?php if ($companyPhone): ?> &nbsp;·&nbsp; <?= e($companyPhone) ?><?php endif; ?>
                <br>This card is property of <?= e($companyName) ?>. If found, please return.
            </div>
        </div>
    </div>

</div>

</body>
</html>
