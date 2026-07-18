<?php
/**
 * Payroll Dashboard Aggregation — Regression Test Suite
 *
 * CLI-runnable, no browser or PHPUnit needed — follows the same plain
 * pass/fail-record convention as database/verify_clean_install.php.
 *
 * Exercises getPayrollPeriodSummary(), normalizePayrollSummary(),
 * normalizePayrollPeriod(), and isValidPayrollYear() from
 * config/functions.php: the payroll dashboard aggregation hardening
 * introduced 2026-07-18 in response to
 * Komagin_HR_Payroll_Dashboard_Aggregation_Audit_2026-07-18.md.
 *
 * Safety: every fixture row this script inserts is written inside a
 * single database transaction that is ALWAYS rolled back at the end
 * (success or failure) — nothing is ever committed. Fixture periods are
 * chosen to be months with zero pre-existing local data (this repo's
 * local database only has real payroll data in May/June of one year), so
 * even if the rollback somehow didn't happen, no developer's real record
 * would be overwritten (INSERTs would simply fail on a genuine
 * employee_id/period collision rather than corrupt anything). The June
 * 2026 orphan-payslip repair script is never executed by this suite.
 *
 * Usage: php tests/payroll_dashboard_aggregation_test.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$results = ['pass' => 0, 'fail' => 0, 'log' => []];
function record(array &$results, bool $ok, string $msg): void {
    $results[$ok ? 'pass' : 'fail']++;
    $results['log'][] = ($ok ? 'PASS' : 'FAIL') . " | $msg";
    echo ($ok ? 'PASS' : 'FAIL') . " | $msg\n";
}
function approx(float $a, float $b, float $tolerance = 0.01): bool {
    return abs($a - $b) <= $tolerance;
}

$db = db();

// Test fixture year: a full calendar year with no local payroll data at
// all (this repo's real local data only exists for two specific months
// of one specific year — never this one), so every period used below is
// guaranteed collision-free against real records.
$FY = (int)date('Y') - 3;

echo "=== Payroll Dashboard Aggregation Regression Suite ===\n";
echo "Fixture year: $FY (chosen to have zero pre-existing local data)\n\n";

// Real employee ids already present locally — safe to reference (FK
// requires an existing employees.id), never written to, only referenced
// by temporary payslip fixture rows that are rolled back.
$empIds = $db->query("SELECT id FROM employees ORDER BY id LIMIT 6")->fetchAll(PDO::FETCH_COLUMN);
if (count($empIds) < 6) {
    fwrite(STDERR, "Need at least 6 employees in the local database to run this suite.\n");
    exit(1);
}
[$E1, $E2, $E3, $E4, $E5, $E6] = $empIds;

$db->beginTransaction();

try {
    function insertPayslip(PDO $db, int $empId, int $month, int $year, ?int $runId, string $status,
                            float $gross, float $ded, float $net): int {
        $db->prepare("INSERT INTO payslips
            (employee_id, period_month, period_year, payroll_run_id, gross_salary, basic_salary,
             net_salary, total_deductions, status)
            VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$empId, $month, $year, $runId, $gross, $gross, $net, $ded, $status]);
        return (int)$db->lastInsertId();
    }
    function insertRun(PDO $db, int $month, int $year, string $status): int {
        $db->prepare("INSERT INTO payroll_runs (period_month, period_year, status, total_gross, total_net, total_deductions, employee_count)
            VALUES (?,?,?,0,0,0,0)")
            ->execute([$month, $year, $status]);
        return (int)$db->lastInsertId();
    }

    // ── TEST 1: Empty period — no run, no payslips ──────────────────────
    $s = getPayrollPeriodSummary(1, $FY);
    record($results, $s['payslip_count'] === 0, 'TEST1 empty period: payslip_count is 0');
    record($results, approx($s['total_gross'], 0.0) && approx($s['total_deductions'], 0.0) && approx($s['total_net'], 0.0),
        'TEST1 empty period: all totals are 0.00');
    record($results, $s['run'] === null, 'TEST1 empty period: no run object returned');
    record($results, $s['warning'] === null, 'TEST1 empty period: no invariant warning');

    // ── TEST 2: Draft payslips before any run exists ────────────────────
    insertPayslip($db, $E1, 2, $FY, null, 'draft', 1000.00, 100.00, 900.00);
    insertPayslip($db, $E2, 2, $FY, null, 'draft', 2000.00, 200.00, 1800.00);
    $s = getPayrollPeriodSummary(2, $FY);
    record($results, $s['payslip_count'] === 2, 'TEST2 draft-before-run: payslip_count is 2');
    record($results, approx($s['total_gross'], 3000.00), 'TEST2 draft-before-run: total_gross is 3000.00');
    record($results, approx($s['total_net'], 2700.00), 'TEST2 draft-before-run: total_net is 2700.00');
    record($results, $s['mode'] === 'unlinked_period', 'TEST2 draft-before-run: mode is unlinked_period');

    // ── TEST 3: Existing finalized run, payslips linked to it ───────────
    $run3 = insertRun($db, 3, $FY, 'finalized');
    insertPayslip($db, $E1, 3, $FY, $run3, 'finalized', 5000.00, 500.00, 4500.00);
    insertPayslip($db, $E2, 3, $FY, $run3, 'finalized', 6000.00, 600.00, 5400.00);
    $s = getPayrollPeriodSummary(3, $FY);
    record($results, $s['payslip_count'] === 2, 'TEST3 finalized run: payslip_count is 2');
    record($results, approx($s['total_gross'], 11000.00), 'TEST3 finalized run: total_gross is 11000.00');
    record($results, $s['mode'] === 'payroll_run', 'TEST3 finalized run: mode is payroll_run');
    record($results, $s['run'] !== null && (int)$s['run']['id'] === $run3, 'TEST3 finalized run: run id matches');

    // ── TEST 4: Same period, one unlinked orphan alongside the run ──────
    $run4 = insertRun($db, 4, $FY, 'finalized');
    insertPayslip($db, $E1, 4, $FY, $run4, 'finalized', 3000.00, 300.00, 2700.00);
    insertPayslip($db, $E2, 4, $FY, $run4, 'finalized', 4000.00, 400.00, 3600.00);
    insertPayslip($db, $E3, 4, $FY, null, 'finalized', 7.00, 10.00, -3.00); // shape of the real June-2026 orphan
    $s = getPayrollPeriodSummary(4, $FY);
    record($results, $s['payslip_count'] === 2, 'TEST4 run+orphan: orphan excluded from payslip_count (2, not 3)');
    record($results, approx($s['total_gross'], 7000.00), 'TEST4 run+orphan: total_gross excludes orphan (7000.00)');
    record($results, !approx($s['total_gross'], 7007.00), 'TEST4 run+orphan: total_gross is NOT 7007.00 (would include orphan)');

    // ── TEST 5: Wrong-period data does not leak into the selected period ─
    insertPayslip($db, $E4, 5, $FY, null, 'draft', 999999.00, 0.00, 999999.00);
    $s3again = getPayrollPeriodSummary(3, $FY);
    record($results, approx($s3again['total_gross'], 11000.00), 'TEST5 wrong-period: period 3 totals unaffected by period 5 data');
    $sReal = getPayrollPeriodSummary(6, (int)date('Y')); // real local data, untouched by this suite's fixture year
    record($results, true, 'TEST5 wrong-period: real local June data queried without error (informational, no fixed expected value — see report)');

    // ── TEST 6: A second, separate payroll run's payslips are not mixed in ─
    $run6 = insertRun($db, 9, $FY, 'finalized');
    insertPayslip($db, $E5, 9, $FY, $run6, 'finalized', 50000.00, 5000.00, 45000.00);
    $s3final = getPayrollPeriodSummary(3, $FY);
    record($results, approx($s3final['total_gross'], 11000.00), 'TEST6 wrong-run: period 3 totals unaffected by period 9 separate run');
    record($results, (int)$s3final['run']['id'] !== $run6, 'TEST6 wrong-run: period 3 run id differs from period 9 run id');

    // ── TEST 7: Zero-row invariant (explicit re-check with strict equality) ─
    $s = getPayrollPeriodSummary(1, $FY);
    record($results, $s['total_gross'] === 0.0 && $s['total_deductions'] === 0.0 && $s['total_net'] === 0.0,
        'TEST7 zero-row invariant: totals are exactly 0.0 (float), not merely near-zero');

    // ── TEST 8: Accounting inconsistency (net != gross - deductions) ────
    insertPayslip($db, $E6, 8, $FY, null, 'draft', 1000.00, 100.00, 1.00); // net wildly wrong
    $s = getPayrollPeriodSummary(8, $FY);
    record($results, $s['payslip_count'] === 1, 'TEST8 accounting mismatch: row still counted (not silently dropped)');
    record($results, $s['warning'] !== null, 'TEST8 accounting mismatch: invariant warning is set');
    $rawRow = $db->query("SELECT gross_salary, total_deductions, net_salary FROM payslips
        WHERE employee_id=$E6 AND period_month=8 AND period_year=$FY")->fetch(PDO::FETCH_ASSOC);
    record($results, (float)$rawRow['net_salary'] === 1.00,
        'TEST8 accounting mismatch: underlying record was NOT silently rewritten (still net=1.00)');

    // ── TEST 9: Gross, deductions, and net accidentally identical ───────
    // Reproduces the exact numeric shape reported in the July 2026
    // incident (262145.00 for all three figures) as a single real,
    // non-zero-count fixture row, and proves the invariant check flags it.
    insertPayslip($db, $E1, 11, $FY, null, 'draft', 262145.00, 262145.00, 262145.00);
    $s = getPayrollPeriodSummary(11, $FY);
    record($results, $s['payslip_count'] === 1, 'TEST9 identical-triplet: row is counted (count=1, not the impossible count=0 from the incident)');
    record($results, approx($s['total_gross'], 262145.00) && approx($s['total_deductions'], 262145.00) && approx($s['total_net'], 262145.00),
        'TEST9 identical-triplet: aggregation itself is arithmetically correct for the one real row present');
    record($results, $s['warning'] !== null,
        'TEST9 identical-triplet: invariant warning fires (net=gross implies deductions should be ~0, not equal to gross)');

    // ── TEST 9b: The exact reported incident shape, unit-tested directly ─
    // count=0 with non-zero sums cannot be produced by getPayrollPeriodSummary()
    // itself (proven structurally in the audit — count and every SUM come
    // from one row of one query) — this calls normalizePayrollSummary()
    // directly with a hand-crafted contradictory row to prove the
    // defensive guard independently of whether the DB layer could ever
    // produce this input.
    $contradictory = normalizePayrollSummary(
        ['payslip_count' => 0, 'total_gross' => 262145.00, 'total_deductions' => 262145.00, 'total_net' => 262145.00],
        'unlinked_period', null, 7, $FY
    );
    record($results, $contradictory['payslip_count'] === 0, 'TEST9b contradictory input: payslip_count preserved as 0');
    record($results, $contradictory['total_gross'] === 0.0 && $contradictory['total_deductions'] === 0.0 && $contradictory['total_net'] === 0.0,
        'TEST9b contradictory input: all totals forced to 0.0 despite non-zero input sums');
    record($results, $contradictory['warning'] !== null,
        'TEST9b contradictory input: invariant warning is set — never silently displayed');

    // ── TEST 10: Invalid month/year normalization ────────────────────────
    $p = normalizePayrollPeriod(0, $FY);
    record($results, $p['month'] === 1 && $p['message'] !== null, 'TEST10a month=0 clamps to 1 with a message');
    $p = normalizePayrollPeriod(13, $FY);
    record($results, $p['month'] === 12 && $p['message'] !== null, 'TEST10b month=13 clamps to 12 with a message');
    $p = normalizePayrollPeriod('abc', $FY);
    record($results, $p['month'] === 1 && $p['message'] !== null, 'TEST10c non-numeric month clamps to 1 with a message');
    $p = normalizePayrollPeriod(6, 1800);
    record($results, $p['year'] === (int)date('Y') && $p['message'] !== null, 'TEST10d year=1800 resets to current year with a message');
    $p = normalizePayrollPeriod(6, 9999);
    record($results, $p['year'] === (int)date('Y') && $p['message'] !== null, 'TEST10e year=9999 resets to current year with a message');
    $p = normalizePayrollPeriod(6, $FY);
    record($results, $p['month'] === 6 && $p['year'] === $FY && $p['message'] === null, 'TEST10f valid month/year passes through unchanged with no message');

    // ── TEST 11: Run exists but is still draft/processing (nothing linked yet) ─
    // The gap found while designing this hardening: a freshly-created run
    // has nothing linked to it until run_finalize.php's backfill runs, so
    // a naive "run exists -> run-scoped" rule would incorrectly show 0 for
    // a period that actually has real draft payslips sitting in it.
    $run11 = insertRun($db, 12, $FY, 'draft');
    insertPayslip($db, $E2, 12, $FY, null, 'draft', 1500.00, 150.00, 1350.00);
    insertPayslip($db, $E3, 12, $FY, null, 'draft', 2500.00, 250.00, 2250.00);
    $s = getPayrollPeriodSummary(12, $FY);
    record($results, $s['payslip_count'] === 2, 'TEST11 draft-run-not-yet-linked: real draft payslips are still counted (2, not 0)');
    record($results, approx($s['total_gross'], 4000.00), 'TEST11 draft-run-not-yet-linked: total_gross is 4000.00, not 0.00');
    record($results, $s['mode'] === 'unlinked_period', 'TEST11 draft-run-not-yet-linked: mode is unlinked_period despite a run row existing');
    record($results, $s['run'] !== null && (int)$s['run']['id'] === $run11, 'TEST11 draft-run-not-yet-linked: run object is still returned (status draft)');

} finally {
    $db->rollBack();
    echo "\n(all fixture rows rolled back — no local data was permanently modified)\n";
}

echo "\n=== Summary ===\n";
echo "PASS: {$results['pass']}\n";
echo "FAIL: {$results['fail']}\n";
exit($results['fail'] > 0 ? 1 : 0);
