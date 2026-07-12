# Komagin HR — Phase 4 Workflow Group 5: Payroll

**Document type:** Phase 4 Deliverable — Workflow Group Report 5 of N
**Status:** Live-verified, including a genuine 5-way concurrent-request race test. Test payroll run created and fully cleaned up afterward. §3's 32 orphaned rows were deleted from the live database per explicit user direction (backed up first) — no other production data was modified.
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

## 2. Finding — Payroll Deductions and Savings Are Never Reflected in Payslip Totals (ACCEPTED AS DESIGNED — user decision)

`modules/payroll/payslips.php` computes `total_deductions` and `net_salary` from exactly three fields entered directly on the payslip form: `tax_amount + uif_employee + other_deductions`. It never reads `payroll_deductions` (the dedicated recurring/one-time deduction module — loan repayments, union dues, garnishees, pension, medical aid, etc.) or `employee_savings` (the savings-contribution module) at all. Neither `modules/payroll/deductions.php` nor `modules/payroll/savings.php` ever writes back to `payslips.total_deductions`/`net_salary` either — the three systems are completely disconnected.

Concretely: if HR sets up a loan-repayment deduction for an employee via the Deductions module, that amount is tracked and visible there, but **does not automatically appear on or affect that employee's payslip** — HR would have to notice it separately and manually re-enter an equivalent amount into the payslip's free-text "other_deductions" field for it to actually reduce net pay. There is also a properly-designed `payslip_items` table (earning/deduction/employer_contribution/info line items) that exists in the schema but is **never referenced by any code at all** — a fully dead table, the same class of gap as the already-documented KOM-065 (`employee_skills`).

Live data check: all 13 real employees have `payroll_deductions` rows, and 13 have `employee_savings` rows — not a hypothetical edge case, real tracked deduction/savings data exists today that payslip calculations do not account for.

**User decision (2026-07-12): leave as-is, accepted as designed.** Deductions and savings remain record-keeping-only modules; payslip entry stays an independent manual step performed by HR. No code change made.

**This was deliberately not fixed automatically.** Changing how net pay is calculated is a direct change to real employees' pay amounts — the highest-stakes kind of change in this entire system, and not something to guess at unilaterally. Options: (a) leave as-is, documented, with deductions/savings tracked for record-keeping only and payslip amounts entered independently by HR (possibly the intended design, if payslip entry is meant to be a manual final step regardless of what's tracked elsewhere); (b) auto-sum active `payroll_deductions`/`employee_savings` into a payslip's totals when it's created, with the flat fields becoming an override/adjustment on top; (c) migrate to the unused `payslip_items` table as the actual source of truth and compute totals from it.

**Finding ID:** KOM-085 (new). Flagged for your decision — this affects real pay calculations, not just workflow mechanics.

## 3. Finding — 32 Orphaned `payroll_deductions` Rows (Nearly Half the Table) (FIXED — per user direction)

`payroll_deductions.employee_id` has a DB-level `FOREIGN KEY ... ON DELETE CASCADE` to `employees`, yet 32 of the table's 67 rows reference `employee_id` values (22–38 and others) that do not exist in the current 13-row `employees` table. `employee_savings` and `payslips` — which have the identical FK pattern — have **zero** orphans, so this is specific to `payroll_deductions`. All 32 share the exact same `created_at` timestamp (`2026-06-25 11:05:31`) and have `amount=NULL`, strongly indicating they were bulk-inserted by a demo/seed process referencing employee records that were later removed without the FK cascade actually firing (most likely a bulk cleanup that had `FOREIGN_KEY_CHECKS` disabled, or the FK constraint was added to the table after these rows already existed).

**User decision (2026-07-12): delete the 32 orphaned rows.** All 32 backed up to `database/backups/orphaned_payroll_deductions_20260712.tsv` (gitignored, matching the existing `database/backups/` policy) before deletion. **Verified**: `payroll_deductions` now has 35 rows, all 13 real employees, zero orphans (confirmed by the same query that found them).

**Finding ID:** KOM-086 (new, fixed)

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
| KOM-085 — Deductions/savings never reflected in payslip totals | High | **Accepted as designed** (user decision) |
| KOM-086 — 32 orphaned `payroll_deductions` rows | Medium | **Fixed** (user-directed deletion, backed up first) |

**All 3 findings resolved.** KOM-030 fixed and live-verified with genuine concurrency testing. KOM-085 and KOM-086 were correctly flagged rather than decided unilaterally (one changes real pay calculations, the other deletes existing database rows) — both were resolved per explicit user direction: KOM-085 left as-is, KOM-086's orphaned rows backed up then deleted.
