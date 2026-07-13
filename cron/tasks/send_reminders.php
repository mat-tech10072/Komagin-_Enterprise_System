<?php
/**
 * Phase 5, Stage 5.6: Deferred Notification Workflows.
 *
 * Each block below fires an admin notification (notifyRole()) for one
 * category of time-sensitive event this codebase previously had no
 * reminder mechanism for at all (see Phase 4 Workflow Group 11's
 * completeness-gap note, and the Stage 5.4 scheduler this task now
 * runs on).
 *
 * Every threshold is an EXACT match (DATEDIFF = N, or "working days
 * elapsed = N"), not a >= comparison, so each reminder is only a
 * candidate on one calendar day per event. That alone isn't enough for
 * safety, though: cron/README.md recommends running the scheduler
 * every 15-30 minutes, and at that cadence the same candidate would
 * re-fire on every invocation within its matching day. fireOnce()
 * below closes that gap with a single per-day dedup table
 * (reminder_notifications_log) — cheap, and the smallest mechanism
 * that's actually correct at the cadence this application's own setup
 * instructions recommend, rather than only correct under an
 * unstated "runs once a day" assumption.
 */

$itemsProcessed = 0;
$today = date('Y-m-d');

/** Returns true (and records it) only the first time $key is seen for $today. */
function fireOnce(string $key, string $today): bool {
    try {
        db()->prepare("INSERT INTO reminder_notifications_log (reminder_key, reminder_date) VALUES (?, ?)")
            ->execute([$key, $today]);
        return true;
    } catch (PDOException $e) {
        return false; // UNIQUE constraint violation — already sent today
    }
}

// ── Employee contract expiry ────────────────────────────────────────
$stmt = db()->prepare("SELECT id, first_name, last_name, contract_end_date FROM employees
    WHERE status = 'active' AND contract_end_date IS NOT NULL
    AND DATEDIFF(contract_end_date, ?) IN (30, 14, 7, 1)");
$stmt->execute([$today]);
foreach ($stmt->fetchAll() as $emp) {
    if (!fireOnce("contract_expiry:employees:{$emp['id']}", $today)) continue;
    $days = (int)((strtotime($emp['contract_end_date']) - strtotime($today)) / 86400);
    $name = trim($emp['first_name'] . ' ' . $emp['last_name']);
    notifyRole('hr_manager', 'warning', 'Employee Contract Expiring',
        "{$name}'s contract ends in {$days} day(s) (" . $emp['contract_end_date'] . ").",
        APP_URL . '/modules/employees/view.php?id=' . $emp['id']);
    $itemsProcessed++;
}

// ── Employee probation ending ───────────────────────────────────────
$stmt = db()->prepare("SELECT id, first_name, last_name, probation_end FROM employees
    WHERE status = 'probation' AND probation_end IS NOT NULL
    AND DATEDIFF(probation_end, ?) IN (7, 1)");
$stmt->execute([$today]);
foreach ($stmt->fetchAll() as $emp) {
    if (!fireOnce("probation_ending:employees:{$emp['id']}", $today)) continue;
    $days = (int)((strtotime($emp['probation_end']) - strtotime($today)) / 86400);
    $name = trim($emp['first_name'] . ' ' . $emp['last_name']);
    notifyRole('hr_manager', 'warning', 'Employee Probation Ending',
        "{$name}'s probation period ends in {$days} day(s) (" . $emp['probation_end'] . ") — review before it lapses.",
        APP_URL . '/modules/employees/view.php?id=' . $emp['id']);
    $itemsProcessed++;
}

// ── Temporary employee end date ─────────────────────────────────────
$stmt = db()->prepare("SELECT id, first_name, last_name, end_date FROM temp_employees
    WHERE status = 'active' AND end_date IS NOT NULL
    AND DATEDIFF(end_date, ?) IN (7, 1)");
$stmt->execute([$today]);
foreach ($stmt->fetchAll() as $t) {
    if (!fireOnce("temp_employee_ending:temp_employees:{$t['id']}", $today)) continue;
    $days = (int)((strtotime($t['end_date']) - strtotime($today)) / 86400);
    $name = trim($t['first_name'] . ' ' . $t['last_name']);
    notifyRole('hr_manager', 'warning', 'Temporary Employee Assignment Ending',
        "{$name}'s temporary assignment ends in {$days} day(s) (" . $t['end_date'] . ").",
        APP_URL . '/modules/temp_employees/view.php?id=' . $t['id']);
    $itemsProcessed++;
}

// ── Consultant contract end date ────────────────────────────────────
$stmt = db()->prepare("SELECT id, first_name, last_name, end_date FROM consultants
    WHERE status = 'active' AND end_date IS NOT NULL
    AND DATEDIFF(end_date, ?) IN (7, 1)");
$stmt->execute([$today]);
foreach ($stmt->fetchAll() as $c) {
    if (!fireOnce("consultant_ending:consultants:{$c['id']}", $today)) continue;
    $days = (int)((strtotime($c['end_date']) - strtotime($today)) / 86400);
    $name = trim($c['first_name'] . ' ' . $c['last_name']);
    notifyRole('hr_manager', 'warning', 'Consultant Contract Ending',
        "{$name}'s consultant contract ends in {$days} day(s) (" . $c['end_date'] . ").",
        APP_URL . '/modules/consultants/view.php?id=' . $c['id']);
    $itemsProcessed++;
}

// ── Employee document expiry ────────────────────────────────────────
$stmt = db()->prepare("SELECT ed.id, ed.employee_id, ed.document_name, ed.expiry_date,
        e.first_name, e.last_name
    FROM employee_documents ed
    JOIN employees e ON e.id = ed.employee_id
    WHERE ed.is_deleted = 0 AND ed.expiry_date IS NOT NULL
    AND DATEDIFF(ed.expiry_date, ?) IN (30, 7)");
$stmt->execute([$today]);
foreach ($stmt->fetchAll() as $d) {
    if (!fireOnce("document_expiry:employee_documents:{$d['id']}", $today)) continue;
    $days = (int)((strtotime($d['expiry_date']) - strtotime($today)) / 86400);
    $name = trim($d['first_name'] . ' ' . $d['last_name']);
    notifyRole('hr_manager', 'warning', 'Employee Document Expiring',
        "\"{$d['document_name']}\" for {$name} expires in {$days} day(s) (" . $d['expiry_date'] . ").",
        APP_URL . '/modules/employees/view.php?id=' . $d['employee_id']);
    $itemsProcessed++;
}

// ── Training program starting soon ──────────────────────────────────
$stmt = db()->prepare("SELECT id, title, start_date FROM training_programs
    WHERE status = 'planned' AND start_date IS NOT NULL
    AND DATEDIFF(start_date, ?) IN (7, 1)");
$stmt->execute([$today]);
foreach ($stmt->fetchAll() as $tp) {
    if (!fireOnce("training_starting:training_programs:{$tp['id']}", $today)) continue;
    $days = (int)((strtotime($tp['start_date']) - strtotime($today)) / 86400);
    notifyRole('hr_manager', 'info', 'Training Program Starting Soon',
        "\"{$tp['title']}\" starts in {$days} day(s) (" . $tp['start_date'] . ").",
        APP_URL . '/modules/training/index.php');
    $itemsProcessed++;
}

// ── Recruitment interview tomorrow ──────────────────────────────────
$stmt = db()->prepare("SELECT id, first_name, last_name, interview_date FROM recruitment_applications
    WHERE status = 'interview_scheduled' AND interview_date IS NOT NULL
    AND DATE(interview_date) = DATE_ADD(?, INTERVAL 1 DAY)");
$stmt->execute([$today]);
foreach ($stmt->fetchAll() as $app) {
    if (!fireOnce("interview_tomorrow:recruitment_applications:{$app['id']}", $today)) continue;
    $name = trim($app['first_name'] . ' ' . $app['last_name']);
    notifyRole('hr_manager', 'info', 'Interview Scheduled Tomorrow',
        "Interview with {$name} is scheduled for " . date('M j, Y g:i A', strtotime($app['interview_date'])) . ".",
        APP_URL . '/modules/recruitment/index.php');
    $itemsProcessed++;
}

// ── Leave approval sitting unactioned ───────────────────────────────
// "Working days elapsed" (Stage 5.3 calendar) rather than calendar
// days, so a workflow created on a Friday isn't flagged as overdue by
// Monday morning. countWorkingDays() is inclusive of both endpoints,
// so subtract 1 to get days elapsed since (not including) creation.
$stmt = db()->prepare("SELECT id, employee_id, created_at FROM approval_workflows
    WHERE workflow_type = 'leave' AND status IN ('pending','in_review')");
$stmt->execute();
foreach ($stmt->fetchAll() as $wf) {
    $createdDate = date('Y-m-d', strtotime($wf['created_at']));
    $elapsed = countWorkingDays($createdDate, $today) - 1;
    if ($elapsed !== 2) continue;
    if (!fireOnce("leave_pending:approval_workflows:{$wf['id']}", $today)) continue;
    $empStmt = db()->prepare("SELECT first_name, last_name FROM employees WHERE id = ?");
    $empStmt->execute([$wf['employee_id']]);
    $emp = $empStmt->fetch();
    $name = $emp ? trim($emp['first_name'] . ' ' . $emp['last_name']) : "Employee #{$wf['employee_id']}";
    notifyRole('hr_manager', 'warning', 'Leave Approval Pending',
        "{$name}'s leave application has been awaiting approval for 2 working days.",
        APP_URL . '/modules/leave/index.php');
    $itemsProcessed++;
}

// ── Payroll run finalized but not yet published ─────────────────────
$stmt = db()->prepare("SELECT id, period_month, period_year, finalized_at FROM payroll_runs
    WHERE status = 'finalized' AND finalized_at IS NOT NULL");
$stmt->execute();
foreach ($stmt->fetchAll() as $run) {
    $finalizedDate = date('Y-m-d', strtotime($run['finalized_at']));
    $elapsed = countWorkingDays($finalizedDate, $today) - 1;
    if ($elapsed !== 2) continue;
    if (!fireOnce("payroll_unpublished:payroll_runs:{$run['id']}", $today)) continue;
    $period = date('F Y', mktime(0, 0, 0, (int)$run['period_month'], 1, (int)$run['period_year']));
    notifyRole('payroll_manager', 'warning', 'Payroll Run Awaiting Publication',
        "The {$period} payroll run was finalized 2 working days ago and has not been published yet.",
        APP_URL . '/modules/payroll/index.php');
    $itemsProcessed++;
}

return $itemsProcessed;
