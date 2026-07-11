# Komagin HR — Payroll Authorization Report

**Document type:** Phase 1 Deliverable #5 of 10
**Objective addressed:** Objective 6 — Payroll Authorization
**Findings addressed:** KOM-014 (H-09), KOM-011 (H-05, KOM-044 duplicate resolved)
**Date:** 2026-07-11/12

---

## 1. Scope of This Objective

The charter names five payroll surfaces to verify: salary viewing, deduction deletion, savings deletion, executive reporting, exports, approvals — each requiring the appropriate permission, with the explicit rule "roles with view access must never inherit destructive capabilities."

## 2. Deduction/Savings Delete — The Core Fix (KOM-014)

`modules/payroll/deductions.php` and `modules/payroll/savings.php` each gated their **entire** POST handler — create, toggle, update_balance, and delete alike — with a single page-level `requirePermission('payroll.deductions'|'payroll.savings', 'view')`. The seeded permission matrix explicitly sets `can_delete = 0` for every role that has `payroll.deductions`/`payroll.savings` access at all (`hr_manager`, `payroll_manager`, `payroll_officer` — confirmed by direct query against the live database before and after the fix). Because the code never checked `can_delete` specifically, that restriction was never actually enforced — any of those three roles could permanently delete a garnishee, loan deduction, or pension record.

**Fix:** every action branch in both files now calls its own `requirePermission()` with the correct action:

| File | Branch | Action checked |
|---|---|---|
| `deductions.php` | create | `create` |
| `deductions.php` | toggle | `edit` |
| `deductions.php` | delete | `delete` |
| `savings.php` | create (no `edit_id`) | `create` |
| `savings.php` | update (has `edit_id`) | `edit` |
| `savings.php` | update_balance | `edit` |
| `savings.php` | delete | `delete` |

Delete and toggle branches also gained `auditLog()` calls where none existed before (previously only `create` was logged in `deductions.php`; `savings.php` had none at all).

**Live verification:** logged in as `payroll_officer` (per the matrix: `can_view=1, can_create=1, can_edit=1, can_delete=0` for `payroll.deductions`). POSTed a delete action directly. Result: redirected to `dashboard.php?error=access_denied`; confirmed no row was removed. The same role successfully loaded the deductions list page (view still works) — confirming the fix is precise (blocks only the restricted action, doesn't over-block).

## 3. Executive Reporting — Payroll Masking (KOM-011/KOM-044)

`modules/reports/executive.php` rendered two blocks of aggregate payroll data — a "YTD Payroll" KPI card and a "Payroll Summary" card (total gross/net/deductions) — unconditionally, gated only by the report-level `reports.view` permission, with no check against `canViewSalaryData()` (the same helper every other payroll-sensitive view in the system already used).

**Fix:** both blocks now check `canViewSalaryData()` (which wraps `hasPermission('payroll.view','view')`) and substitute the existing `maskSalary()` blurred placeholder when false. The one non-monetary figure in the same card ("Employees on Payroll" — a headcount, not a currency value) remains unmasked, consistent with how the main dashboard already treats overtime *hours* as non-sensitive while treating payroll *cost* as sensitive.

**Live verification:** confirmed zero `filter:blur` occurrences in the rendered page for `super_admin` and `payroll_officer` (both hold `payroll.view`) — real figures shown. No currently-seeded role holds `reports.view` without also holding `payroll.view`, so the masked path itself could not be independently demonstrated with a live request in this environment; it was verified by code review and by confirming the conditional logic mirrors the exact pattern already proven correct elsewhere (`modules/employees/edit.php`, `view.php`).

This finding had a duplicate tracking entry (KOM-044, added during Phase 0 specifically because Audit II's regression pass hadn't re-read this file) — resolving KOM-011 resolves KOM-044 by definition; both rows in the Master Register note this.

## 4. Approvals (payroll_run workflow type)

The `payroll_run` workflow type (Payroll Officer Review → Payroll Manager Approval) is enforced entirely through the hardened `ApprovalEngine::act()` — see the Approval Engine Report. No payroll-specific engine changes were needed; the engine's role/stage/duplicate/separation-of-duties checks apply uniformly to every workflow type, payroll included.

## 5. What Was Already Correct (Confirmed, Not Changed)

- `modules/payroll/run_finalize.php`, `run_publish.php`, `payslip_finalize.php` already called `requirePermission('payroll.finalize', 'approve'|'publish')` correctly before Phase 1 — no change needed.
- `modules/payroll/run_save.php` already checked `requirePermission('payroll.run', 'create')` — no change needed.
- Salary/bank-field masking on individual employee records (`modules/employees/edit.php`, `view.php`) via `canViewSalaryData()`/`canViewBankData()`/`maskSalary()`/`maskBankField()` was already correctly implemented — confirmed still working, not touched.

## 6. Known Remaining Gap (Explicitly Out of Phase 1 Scope)

`modules/payroll/payslips.php`'s update branch still has no status guard (can edit an already-published/finalized payslip) and still doesn't call `auditLog()` on update (Master Register KOM-016/H-11). This is a data-integrity/audit-trail gap, not a missing permission check — the correct fix is adding a status-based business rule, which is a workflow change, explicitly out of scope for an authorization-consistency phase per the charter ("Do NOT redesign workflows"). Tracked for Phase 2.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial payroll authorization report, including live verification evidence | Remediation Program — Phase 1 |
