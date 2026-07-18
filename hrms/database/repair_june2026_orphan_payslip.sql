-- ────────────────────────────────────────────────────────────────────────
-- Manual repair: June 2026 orphan payslip (employee_id=13, payslip id=1)
-- ────────────────────────────────────────────────────────────────────────
-- NOT run automatically by the application or by any other script. Review
-- and run this by hand (e.g. via phpMyAdmin or `mysql < this_file.sql`)
-- only after you've decided which option below is correct.
--
-- Background
-- ----------
-- payslips.id=1 (employee_id=13, period 6/2026) has payroll_run_id=NULL,
-- status='finalized', gross_salary=7.00, total_deductions=10.00,
-- net_salary=-3.00, notes='final pay slip'. It was never linked to
-- payroll_runs.id=2 (the official June 2026 run, already finalized with
-- 12 correctly-linked payslips). The application code fix (see the
-- payroll module changes made 2026-07-18) now excludes this row from
-- every dashboard/report total going forward, so it is no longer required
-- to run this script to fix the *reporting* symptom. This script is only
-- for cleaning up the underlying bad row itself, if desired.
--
-- Figures 7.00 / 10.00 / -3.00 are not a plausible real payslip (a
-- negative net pay), so this is very likely leftover test/seed data,
-- not a real transaction that needs preserving. Confirm this with
-- whoever manages payroll before running Option A or B — do not assume.
--
-- Run ONE of the two options below, not both.

-- Sanity check before doing anything: confirm this is still the only
-- affected row and nothing else has changed since this script was written.
SELECT id, employee_id, period_month, period_year, payroll_run_id, status,
       gross_salary, total_deductions, net_salary, notes
FROM payslips
WHERE id = 1 AND employee_id = 13 AND period_month = 6 AND period_year = 2026;
-- Expect exactly one row matching the figures described above before
-- proceeding. If it does not match, STOP and investigate — do not run
-- Option A or B blindly.


-- ── Option A (recommended): link it into the official June run ──────────
-- Use this if the row represents a real (if botched) payslip for employee
-- 13 that should count as part of June payroll. This does NOT change any
-- of the bad figures — it only attaches the row to run 2 so it becomes
-- visible/governed by the run, and then makes the run authoritative again
-- by recomputing its stored totals from its now-complete linked set.
-- You would still want to separately correct the actual gross/deductions/
-- net figures for employee 13 through the normal payslip edit workflow
-- (but note payslips.php blocks edits once status is 'finalized' — you'd
-- need to manually reset status to 'draft' first if a correction is
-- needed, which is a deliberate decision, not part of this script).

-- UPDATE payslips SET payroll_run_id = 2 WHERE id = 1;
--
-- UPDATE payroll_runs SET
--     total_gross       = (SELECT SUM(gross_salary)      FROM payslips WHERE payroll_run_id = 2),
--     total_net         = (SELECT SUM(net_salary)        FROM payslips WHERE payroll_run_id = 2),
--     total_deductions  = (SELECT SUM(total_deductions)  FROM payslips WHERE payroll_run_id = 2),
--     employee_count    = (SELECT COUNT(*)               FROM payslips WHERE payroll_run_id = 2)
-- WHERE id = 2;


-- ── Option B: delete the row entirely ────────────────────────────────────
-- Use this only if you've confirmed with payroll that this is test/seed
-- debris and employee 13 has a legitimate way of getting a correct June
-- payslip issued separately (e.g. re-created through the normal Payslip
-- Management screen). This permanently removes the row and its audit
-- trail linkage; there is no undo once run.

-- DELETE FROM payslips WHERE id = 1 AND employee_id = 13
--     AND period_month = 6 AND period_year = 2026 AND payroll_run_id IS NULL;


-- After running either option, verify:
-- SELECT COUNT(*) FROM payslips WHERE payroll_run_id IS NULL AND status <> 'draft';
-- Expect 0 rows (no more finalized/sent payslips left unlinked to a run).
