# Komagin HR — Change Control Log & Template

**Document type:** Phase 0 supporting deliverable (Task 11) — first populated in Phase 1
**Status:** Living log. 13 entries recorded for Phase 1 (Security Foundation & Authorization Framework).
**Date compiled:** 2026-07-11 (template) — **first entries added 2026-07-11/12 (Phase 1)**
**Baseline tag:** `v1.0-enterprise-baseline` → Phase 1 work on branch `phase-1-authorization-framework`

---

## Rule

**No undocumented change may be made after Phase 0 closes.** Every commit/PR in every future remediation phase must have a corresponding entry in this log before it is considered complete. This is the enforcement mechanism for the "controlled remediation" goal of the whole program.

## Entry Template

Copy this block for every change and append it to the log below.

```
### CC-XXX — <short title>

- **Date:**
- **Phase:**
- **Finding ID(s) addressed:** KOM-XXX[, KOM-YYY, ...]
- **Files changed:**
- **Reason:**
- **Tests added/updated:** (reference Testing/09-baseline-test-plan.md row(s), or state "new test added: <description>")
- **Regression tests executed:** (list each, pass/fail)
- **Verification result:** (who verified, how, outcome)
- **Master Register updated:** Yes/No — (Completion Status, Verification Status, Date Closed fields updated for the relevant KOM-XXX row(s))
```

## Rules for Filling This Out

1. **One entry per logical change**, not per commit — if a PR touches 3 files to fix 1 finding, that's one entry.
2. **Finding ID is mandatory.** If a change doesn't trace to a Master Remediation Register entry, add the entry to the register first (as a new finding, if it's something discovered mid-phase — see the "Important" instruction in the Phase 0 charter: document first, link to a Finding ID, assign a phase, don't fix ad hoc).
3. **A change is not complete until its regression test has run and passed**, and the corresponding Master Remediation Register row has been updated (Completion Status → Fixed/Verified, Verification Status → Verified, Date Closed → actual date).
4. Entries are numbered sequentially (`CC-001`, `CC-002`, ...) and never renumbered or deleted, even if a change is later reverted (revert gets its own new entry referencing the original).

---

## Log

### CC-001 — Centralize the authorization layer: require explicit permission actions

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** Objective 1 & 2 (Authorization Framework Report) — foundational change enabling KOM-001, KOM-010, KOM-014, KOM-015, KOM-036 and others
- **Files changed:** `config/functions.php` (removed default `$action='view'` from `hasPermission()`/`requirePermission()`, added `PERMISSION_ACTIONS` allow-list, removed dead `requireRole()`); 53 module files + `includes/header.php` + `api/search.php` where a single-argument call was made explicit with the semantically correct action (full list: see `git diff` on branch `phase-1-authorization-framework`, commit history)
- **Reason:** A missing action argument silently defaulted to checking `can_view`, meaning several delete/create/edit-only endpoints (e.g. `consultants/add.php`, `consultants/delete.php`) were actually authorizing on the *view* flag of their permission slug, not the flag their own action implied.
- **Tests added/updated:** `Testing/phase1-regression-run.sh` (new); manual `php -l` syntax check across all 58 initially-touched files, then all subsequent files as they were edited
- **Regression tests executed:** Full smoke test (login + load) across every touched module as `super_admin`; 20-case scripted regression suite (see CC-013); all passed
- **Verification result:** Self-verified via live HTTP requests against the running app (Apache/MariaDB via XAMPP) — 200 OK, zero fatal errors, across every touched page
- **Master Register updated:** Yes — this is the mechanism underlying the fixes recorded against KOM-001/010/014/015/036 etc. below

### CC-002 — Harden ApprovalEngine::act()

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-001 (C-01)
- **Files changed:** `config/ApprovalEngine.php`, `modules/approvals/index.php`
- **Reason:** `act()` never verified the acting user's role against the stage's `approver_role`, allowing any authenticated user to approve/reject any workflow.
- **Tests added/updated:** Live functional test — created a real leave-application workflow via `modules/leave/apply.php`, attempted self-approval and wrong-role approval
- **Regression tests executed:** (1) Initiator self-approval attempt → blocked, workflow status unchanged. (2) Wrong-role (`hr_officer` vs. required `supervisor`) approval attempt → blocked, workflow status unchanged. (3) Invalid workflow ID → typed exception caught, friendly flash message, no fatal error. All passed.
- **Verification result:** Verified live against the running app; test data (workflow + leave application) cleaned up from the database after verification
- **Master Register updated:** Yes — KOM-001 marked Fixed

### CC-003 — Convert Approvals org-wide view to permission-based gating

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-001 (C-01, related hardcoded-check cleanup), Objective 10
- **Files changed:** `modules/approvals/index.php`, `database/phase10_authorization_framework.sql` (new `approvals.manage_all` permission)
- **Reason:** The "All Workflows" admin view was gated by `in_array($role, ['super_admin','hr_manager'])` instead of the permission system.
- **Tests added/updated:** Included in `phase1-regression-run.sh`
- **Regression tests executed:** super_admin and hr_manager see the section; hr_officer does not. All passed.
- **Verification result:** Verified live
- **Master Register updated:** Yes

### CC-004 — Server-side role allow-list for user creation/management

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-015 (H-10)
- **Files changed:** `config/functions.php` (`VALID_USER_ROLES`, `assignableRoles()`, `isValidAssignableRole()`), `modules/users/index.php`
- **Reason:** `$_POST['role']` was inserted after only a non-empty check — no server-side validation that the role was real or that the acting admin was authorized to grant it.
- **Tests added/updated:** Included in `phase1-regression-run.sh`
- **Regression tests executed:** hr_manager POSTed `role=super_admin` directly to the add_user handler; confirmed via direct DB query that no such row was created. Passed.
- **Verification result:** Verified live
- **Master Register updated:** Yes

### CC-005 — Document generation/viewing authorization separation

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-036 (H-06/NM-04), KOM-021 (NH-04)
- **Files changed:** `modules/documents/generate.php`, `modules/documents/view_generated.php`, `config/functions.php` (`canAccessGeneratedDocument()`)
- **Reason:** `generate.php`'s save action wasn't separately checked from view; `view_generated.php` had no record-level scoping and no audit-on-view.
- **Tests added/updated:** Smoke test only — current seed data has no role combination that can independently demonstrate the fixed behavior live (see register notes on KOM-021/KOM-036)
- **Regression tests executed:** Page loads, no fatal errors, for super_admin
- **Verification result:** Code-reviewed + smoke-tested
- **Master Register updated:** Yes, with the seed-data caveat noted in both rows

### CC-006 — Payroll deduction/savings action-specific permission checks

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-014 (H-09)
- **Files changed:** `modules/payroll/deductions.php`, `modules/payroll/savings.php`
- **Reason:** All actions (create/toggle/delete) were gated by the same single `view`-defaulted check; the permission matrix's `can_delete=0` restriction for payroll roles was never actually enforced.
- **Tests added/updated:** Included in `phase1-regression-run.sh`
- **Regression tests executed:** `payroll_officer` (can_view/create/edit=1, can_delete=0) attempted a delete POST → redirected with `error=access_denied`, no row removed. Passed.
- **Verification result:** Verified live
- **Master Register updated:** Yes

### CC-007 — Executive Analytics payroll masking

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-011 (H-05), KOM-044 (duplicate, resolved)
- **Files changed:** `modules/reports/executive.php`
- **Reason:** Aggregate payroll totals rendered unconditionally regardless of `payroll.view`.
- **Tests added/updated:** Included in `phase1-regression-run.sh`
- **Regression tests executed:** super_admin and payroll_officer (both hold `payroll.view`) see unmasked figures — 0 `filter:blur` occurrences. Passed. (No currently-seeded role has `reports.view` without `payroll.view`, so the masked path is code-verified, not independently live-demonstrated.)
- **Verification result:** Verified live (unmasked path) + code review (masked path)
- **Master Register updated:** Yes

### CC-008 — Activity Log migrated to centralized permissions

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-019 (NH-02)
- **Files changed:** `modules/activity_log/index.php`, `modules/activity_log/user.php`, `modules/activity_log/download.php`, `includes/header.php`, `database/phase10_authorization_framework.sql` (new `activity_log.view` permission, seeded super_admin-only to match prior behavior exactly)
- **Reason:** All three files hardcoded `$_SESSION['user_role'] !== 'super_admin'` instead of using the permission system.
- **Tests added/updated:** Included in `phase1-regression-run.sh`
- **Regression tests executed:** super_admin → 200; hr_manager, hr_officer, payroll_officer → 302 (matches pre-fix access exactly). Passed.
- **Verification result:** Verified live
- **Master Register updated:** Yes

### CC-009 — Dashboard Recent Activity widget permission-gated

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-018 (NH-01)
- **Files changed:** `dashboard.php`
- **Reason:** The widget queried and rendered `audit_logs` data for every logged-in user regardless of role.
- **Tests added/updated:** Included in `phase1-regression-run.sh`
- **Regression tests executed:** payroll_officer (no audit.view/activity_log.view) sees the empty-state text; super_admin sees populated data. Passed.
- **Verification result:** Verified live
- **Master Register updated:** Yes

### CC-010 — Branding permission granularity (4 asset types, 12 action branches)

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-032 (M-09) — pulled forward from Phase 3, named explicitly in the Phase 1 charter
- **Files changed:** `modules/settings/branding.php`
- **Reason:** A single `branding.letterheads` permission gated create/edit/delete across all four asset types (letterheads/signatures/stamps/watermarks) despite four separate slugs existing in seed data.
- **Tests added/updated:** Smoke test
- **Regression tests executed:** Page loads without error for super_admin; not independently live-demonstrable since hr_manager/super_admin currently hold identical grants across all four types
- **Verification result:** Code-reviewed + smoke-tested
- **Master Register updated:** Yes, with the seed-data caveat noted

### CC-011 — Hardcoded role-check sweep (10 of 13 converted)

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-040 (consolidates M-02)
- **Files changed:** `modules/timesheets/corrections.php`, `modules/employees/view.php`, `modules/assets/index.php`, `modules/documents/index.php`, `modules/leave/view.php`, `modules/leave/index.php`, `modules/leave/apply.php` (gate added, role-distinction check kept and documented as justified), `modules/archive/monthly.php`, `modules/temp_employees/view.php`, `modules/temp_employees/add.php`, `modules/temp_employees/edit.php`
- **Reason:** 13 hardcoded role-list authorization checks bypassed the DB-driven permission matrix; converting them surfaced two real bugs where `supervisor` had matrix-granted approval rights the hardcoded lists never included.
- **Tests added/updated:** Smoke test across all files; included in `phase1-regression-run.sh` where a distinguishing live test was possible
- **Regression tests executed:** All touched pages load without error; `leave/apply.php`'s new permission gate verified to block `payroll_officer` while allowing `hr_manager`
- **Verification result:** Verified live (access gates); code-verified (supervisor-visibility fix, no supervisor test account available in this environment)
- **Master Register updated:** Yes

### CC-012 — `timesheets.approve`/`timesheets.approve_ot` action-level checks completed

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-010 (H-04)
- **Files changed:** `modules/timesheets/approve.php` (the `approve` branch specifically — `lock`/`unlock` already had this from a prior partial fix), `modules/timesheets/overtime.php`
- **Reason:** The page-level gate defaulted to `view`; the actual approve/reject POST branches never separately checked the `approve` action.
- **Tests added/updated:** Smoke test
- **Regression tests executed:** Both files load without error; no role in current seed data has can_view without can_approve for these exact slugs, so the specific bypass isn't independently live-demonstrable today — verified by code review that the gate now exists
- **Verification result:** Code-reviewed + smoke-tested
- **Master Register updated:** Yes

### CC-013 — hr_officer/hrofficer role-name typo corrected

- **Date:** 2026-07-11/12
- **Phase:** 1
- **Finding ID(s) addressed:** KOM-023
- **Files changed:** `database/phase10_authorization_framework.sql` (new migration, applied to the live database via `mysql komagin_hr < phase10_authorization_framework.sql`)
- **Reason:** `phase8_temp_employees.sql`/`phase9_consultants.sql` seeded role grants against `'hrofficer'` (no underscore); the real role is `'hr_officer'`, so real HR Officers had zero permissions for two modules.
- **Tests added/updated:** `phase1-regression-run.sh`
- **Regression tests executed:** Logged in as `hrofficer` (role `hr_officer`); confirmed 200 OK on `modules/temp_employees/index.php` and `modules/consultants/index.php` (previously blocked). Full 20-case regression suite run: **20/20 passed.**
- **Verification result:** Verified live; pre-migration collision check run first (`SELECT` confirmed zero overlapping `(role, permission_id)` rows before the `UPDATE`)
- **Master Register updated:** Yes

---

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-11 | Template and rules established for Phase 0 | Remediation Program — Phase 0 |
| 2026-07-11/12 | 13 entries (CC-001–CC-013) recorded for Phase 1 | Remediation Program — Phase 1 |
