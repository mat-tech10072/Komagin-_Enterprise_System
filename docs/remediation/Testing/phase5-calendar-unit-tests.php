<?php
/**
 * Phase 5, Stage 5.3 — working-day/holiday calendar unit tests.
 * Re-runnable: creates and cleans up its own disposable test holidays
 * (prefixed P5TEST), makes no other database changes.
 * Run: php docs/remediation/Testing/phase5-calendar-unit-tests.php
 */
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/config/functions.php';

$pdo = db();
$pass = 0; $fail = 0;

function t(string $label, bool $cond): void {
    global $pass, $fail;
    echo ($cond ? "PASS" : "FAIL") . " | $label\n";
    $cond ? $pass++ : $fail++;
}

// Baseline: default Mon-Fri, no holidays
t("2026-07-13 (Monday) is a working day", isWorkingDay('2026-07-13') === true);
t("2026-07-18 (Saturday) is NOT a working day", isWorkingDay('2026-07-18') === false);
t("2026-07-19 (Sunday) is NOT a working day", isWorkingDay('2026-07-19') === false);
t("2026-07-14 (Tuesday) is a working day", isWorkingDay('2026-07-14') === true);

$days = getWorkingDaysBetween('2026-07-13', '2026-07-19');
t("7-day week (Mon-Sun) has 5 working days", count($days) === 5);
t("countWorkingDays matches getWorkingDaysBetween", countWorkingDays('2026-07-13', '2026-07-19') === 5);

$days2 = getWorkingDaysBetween('2026-07-27', '2026-08-02');
t("Cross-month week has 5 working days", count($days2) === 5);

$leapDays = getWorkingDaysBetween('2028-02-01', '2028-02-29');
t("Leap year Feb 2028 range computes without error", is_array($leapDays));
t("isWorkingDay(2028-02-29) doesn't throw", is_bool(isWorkingDay('2028-02-29')));

t("Next working day after Fri 2026-07-17 is Mon 2026-07-20", getNextWorkingDay('2026-07-17') === '2026-07-20');

$pdo->exec("INSERT INTO work_calendar_holidays (name, start_date, end_date, is_recurring_annual, is_active, notes) VALUES ('P5TEST Holiday', '2026-07-14', '2026-07-14', 0, 1, 'test')");
t("2026-07-14 is now NOT a working day (active holiday)", isWorkingDay('2026-07-14') === false);
$days3 = getWorkingDaysBetween('2026-07-13', '2026-07-19');
t("Week with 1 holiday now has 4 working days", count($days3) === 4);

$pdo->exec("UPDATE work_calendar_holidays SET is_active=0 WHERE name='P5TEST Holiday'");
t("Inactive holiday no longer blocks the date", isWorkingDay('2026-07-14') === true);

$pdo->exec("INSERT INTO work_calendar_holidays (name, start_date, end_date, is_recurring_annual, is_active, notes) VALUES ('P5TEST Xmas', '2026-12-25', '2026-12-25', 1, 1, 'test')");
t("Dec 25 2026 blocked by recurring holiday", isWorkingDay('2026-12-25') === false);
t("Dec 25 2027 ALSO blocked (recurring, different year)", isWorkingDay('2027-12-25') === false);
t("Dec 24 2026 NOT blocked (adjacent day)", isWorkingDay('2026-12-24') === true);

$pdo->exec("DELETE FROM work_calendar_holidays WHERE name LIKE 'P5TEST%'");
$remaining = (int)$pdo->query("SELECT COUNT(*) FROM work_calendar_holidays WHERE name LIKE 'P5TEST%'")->fetchColumn();
t("Test holidays cleaned up", $remaining === 0);

echo "\n======================================\n";
echo "TOTAL: $pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
