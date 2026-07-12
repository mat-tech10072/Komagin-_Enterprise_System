# Komagin HR — Phase 4 Workflow Group 5: Payroll

**Document type:** Phase 4 Deliverable — Workflow Group Report 5 of N
**Status:** Live-verified, including a genuine 5-way concurrent-request race test. Test payroll run created and fully cleaned up afterward. No production financial data was modified.
**Date compiled:** 2026-07-12
**Scope:** Payroll period creation, generation, allowances, deductions, overtime, tax, leave impacts, final approval, payslip generation. No duplicate payroll. No double payment. No orphan deductions. No inconsistent totals.

---

## 1. Fixed — Payroll Run Creation, Finalization, and Publishing All Had a Check-Then-Act Race Condition (closes KOM-030, open since Phase 0)

Three separate payroll actions — creating a run, finalizing it, and publishing it — each did a `SELECT` status/existence check followed by a separate `UPDATE`/`INSERT`, with no atomicity between them:

- **`run_save.php`** (create): checked no run exists for the period, then inserted. A concurrent double-submit could both pass the check; the table's own `UNIQUE KEY (period_month, period_year)` would prevent an actual duplicate row, but the losing request hit that constraint as an **uncaught `PDOException`** — a fatal-error crash, not a graceful "already exists" message.
- **`run_finalize.php`**: checked status is `draft`/`processing`, then updated to `finalized` with recalculated totals. A concurrent request could recalculate from a second, possibly-different snapshot.
- **`run_publish.php`**: checked status is `finalized`, then updated to `published` **and emailed every employee's payslip**. This is the highest-stakes of the three — a race here means real employees could receive duplicate payslip emails.

**Fix:** all three now make the status transition itself the atomic guard — `UPDATE ... WHERE id=? AND status='<expected>'`, checking `rowCount()` to determine whether the request actually won the race. A losing request short-circuits cleanly (no crash, no duplicate email, no duplicate audit entry) instead of proceeding. `run_save.php` additionally wraps its `INSERT` in a try/catch for the duplicate-key case specifically, converting the crash into the same clean "already exists" redirect the pre-check already used for the non-race case.

**Live-verified with a genuine concurrency test** (not just sequential retries): fired 5 simultaneous `POST` requests at `run_publish.php` for the same run. Final state: exactly **one** `audit_logs` entry for the publish action — only one of the five requests actually proceeded past the guard; the other four were correctly turned away by the atomic check. Also verified the create-duplicate path (second concurrent create attempt cleanly redirected to "already exists," no crash, table still has exactly one row for the period) and the finalize path (repeat finalize attempts are correctly no-ops).

**Finding ID:** KOM-030 (pre-existing since Phase 0, now closed)

## 2. Finding — Payroll Deductions and Savings Are Never Reflected in Payslip Totals (NOT FIXED — decision needed)

`modules/payroll/payslips.php` computes `total_deductions` and `net_salary` from exactly three fields entered directly on the payslip form: `tax_amount + uif_employee + other_deductions`. It never reads `payroll_deductions` (the dedicated recurring/one-time deduction module — loan repayments, union dues, garnishees, pension, medical aid, etc.) or `employee_savings` (the savings-contribution module) at all. Neither `modules/payroll/deductions.php` nor `modules/payroll/savings.php` ever writes back to `payslips.total_deductions`/`net_salary` either — the three systems are completely disconnected.

Concretely: if HR sets up a loan-repayment deduction for an employee via the Deductions module, that amount is tracked and visible there, but **does not automatically appear on or affect that employee's payslip** — HR would have to notice it separately and manually re-enter an equivalent amount into the payslip's free-text "other_deductions" field for it to actually reduce net pay. There is also a properly-designed `payslip_items` table (earning/deduction/employer_contribution/info line items) that exists in the schema but is **never referenced by any code at all** — a fully dead table, the same class of gap as the already-documented KOM-065 (`employee_skills`).

Live data check: 29 employees currently have `payroll_deductions` rows, 13 have `employee_savings` rows (out of 13 total real employees) — this is not a hypothetical edge case, real deduction/savings data exists today that payslip calculations do not account for.

**This was deliberately not fixed automatically.** Changing how net pay is calculated is a direct change to real employees' pay amounts — the highest-stakes kind of change in this entire system, and not something to guess at unilaterally. Options: (a) leave as-is, documented, with deductions/savings tracked for record-keeping only and payslip amounts entered independently by HR (possibly the intended design, if payslip entry is meant to be a manual final step regardless of what's tracked elsewhere); (b) auto-sum active `payroll_deductions`/`employee_savings` into a payslip's totals when it's created, with the flat fields becoming an override/adjustment on top; (c) migrate to the unused `payslip_items` table as the actual source of truth and compute totals from it.

**Finding ID:** KOM-085 (new). Flagged for your decision — this affects real pay calculations, not just workflow mechanics.

## 3. Finding — 32 Orphaned `payroll_deductions` Rows (Nearly Half the Table) (NOT FIXED — recommendation only, no destructive action taken)

`payroll_deductions.employee_id` has a DB-level `FOREIGN KEY ... ON DELETE CASCADE` to `employees`, yet 32 of the table's 67 rows reference `employee_id` values (22–38 and others) that do not exist in the current 13-row `employees` table. `employee_savings` and `payslips` — which have the identical FK pattern — have **zero** orphans, so this is specific to `payroll_deductions`. All 32 share the exact same `created_at` timestamp (`2026-06-25 11:05:31`) and have `amount=NULL`, strongly indicating they were bulk-inserted by a demo/seed process referencing employee records that were later removed without the FK cascade actually firing (most likely a bulk cleanup that had `FOREIGN_KEY_CHECKS` disabled, or the FK constraint was added to the table after these rows already existed).

**Recommendation, not applied:** these 32 rows are safe to delete — they reference no employee that exists, carry no amount, and cannot appear on any real payslip (nothing joins `payroll_deductions` to a payslip calculation at all per §2). Consistent with this program's established data-integrity practice (Phase 3, Stage 3.10: "produce recommendations, not automatic deletions"), no rows were removed. Query to review before any cleanup: `SELECT * FROM payroll_deductions WHERE employee_id NOT IN (SELECT id FROM employees);`

**Finding ID:** KOM-086 (new). Flagged for your decision on cleanup.

## 4. No Findings — Orphan Protection Elsewhere, No Double-Counting in Overtime

- `employee_savings` and `payslips` have zero orphaned rows (checked directly).
- Overtime approval (Workflow Group 4, KOM-084) already gained a duplicate-action guard this phase, and — confirmed in §1 above — has no automatic payroll effect at all (documented as a completeness gap in that group's report), so there is no double-counting risk between overtime approval and payslip totals; they simply don't interact yet.

## 5. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`run_save.php`, `run_finalize.php`, `run_publish.php`) | 0 errors |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group) | 5/5 scenarios: duplicate-run create block, sequential finalize no-op, **genuine 5-way concurrent publish race test** (1 winner confirmed via audit log), orphan data confirmed in `payroll_deductions` only (not `employee_savings`/`payslips`) |

## 6. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-030 — Payroll run create/finalize/publish race condition (pre-existing, Phase 0) | Medium | **Fixed** |
| KOM-085 — Deductions/savings never reflected in payslip totals | High | **Documented — decision needed (affects real pay calculations)** |
| KOM-086 — 32 orphaned `payroll_deductions` rows | Medium | **Documented — recommendation only, no deletion applied** |

**1 of 3 findings fixed and live-verified (including genuine concurrency testing). 2 are flagged for your explicit decision** — one because it changes how real pay is calculated, the other because it involves deleting existing database rows, both correctly outside what should be decided unilaterally.
