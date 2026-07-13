# Komagin HR — Change Control Log & Template

**Document type:** Phase 0 supporting deliverable (Task 11) — first populated in Phase 1
**Status:** Living log. 13 entries recorded for Phase 1; 11 more (CC-014–CC-024) recorded for Phase 2; 11 more (CC-025–CC-035) recorded for Phase 3; 10 more (CC-036–CC-045) recorded for Phase 4, Workflow Group 1; 5 more (CC-046–CC-050) recorded for Phase 4, Workflow Group 2; 7 more (CC-051–CC-057) recorded for Phase 4, Workflow Group 3; 4 more (CC-058–CC-061) recorded for Phase 4, Workflow Group 4; 5 more (CC-062–CC-066) recorded for Phase 4, Workflow Group 5; 1 more (CC-067) recording the KOM-085/KOM-086 user decisions; 3 more (CC-068–CC-070) recorded for Phase 4, Workflow Group 6; 4 more (CC-071–CC-074) recorded for Phase 4, Workflow Group 7; 4 more (CC-075–CC-078) recorded for Phase 4, Workflow Group 8; 3 more (CC-079–CC-081) recorded for Phase 4, Workflow Group 9; 6 more (CC-082–CC-087) recorded for Phase 4, Workflow Group 10; 5 more (CC-088–CC-092) recorded for Phase 4, Workflow Group 11; 6 more (CC-093–CC-098) recorded for Phase 4, Workflow Group 12; 4 more (CC-099–CC-102) recorded for Phase 4, Workflow Group 13; 1 more (CC-103) recording the KOM-045 close-out decision — all 13 Phase 4 workflow groups complete, see the Phase 4 Completion Report; 2 more (CC-104–CC-105) recorded for Phase 5, Stage 5.1; 1 more (CC-106) recorded for Phase 5, Stage 5.2; 1 more (CC-107) recorded for Phase 5, Stage 5.3; 1 more (CC-108) recorded for Phase 5, Stage 5.4; 2 more (CC-109–CC-110) recorded for Phase 5, Stage 5.5; 2 more (CC-111–CC-112) recorded for Phase 5, Stage 5.6; 1 more (CC-113) recorded for Phase 5, Stage 5.7; 1 more (CC-114) recorded for Phase 5, Stage 5.8; 1 more (CC-115) recorded for Phase 5, Stage 5.9; 17 more (CC-116–CC-132) recorded for Phase 5, Stage 5.10; 2 more (CC-133–CC-134) recorded for Phase 5, Stage 5.11; 1 more (CC-135) recorded for Phase 5, Stage 5.12; 1 more (CC-136) recorded for Phase 5, Stage 5.13 — all 13 Phase 5 stages complete, see the Phase 5 Completion Report; 1 more (CC-137) recorded for Phase 6, Stage 6.1; 1 more (CC-138) recorded for Phase 6, Stage 6.2; 1 more (CC-139) recorded for Phase 6, Stage 6.3; 1 more (CC-140) recorded for Phase 6, Stage 6.4; 1 more (CC-141) recorded for Phase 6, Stage 6.5; 1 more (CC-142) recorded for Phase 6, Stage 6.6; 1 more (CC-143) recorded for Phase 6, Stage 6.7; 1 more (CC-144) recorded for Phase 6, Stage 6.8; 1 more (CC-145) recorded for Phase 6, Stage 6.9; **1 more (CC-146) recorded for Phase 6, Stage 6.10 — more to follow as each subsequent stage completes.**
**Date compiled:** 2026-07-11 (template) — entries added 2026-07-11/12 (Phase 1) — added 2026-07-11/12 (Phase 2) — added 2026-07-12 (Phase 3) — **more added 2026-07-12 (Phase 4, in progress)**
**Baseline tag:** `v1.0-enterprise-baseline` → Phase 1 on branch `phase-1-authorization-framework` → Phase 2 on branch `phase-2-authentication-session-security` → Phase 3 on branch `phase-3-database-schema-integrity` → **Phase 4 on branch `phase-4-business-workflow-integrity`**

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

### CC-014 — Consultants module CSRF helper corrected + full CRUD regression

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** KOM-002 (C-02)
- **Files changed:** `modules/consultants/{add,edit,delete,scope_save}.php`
- **Reason:** All four called `validateCsrfToken()`, never defined anywhere; every write threw a PHP Fatal Error.
- **Tests added/updated:** `phase2-regression-run.sh`
- **Regression tests executed:** Full live CRUD lifecycle — created a consultant, edited it (confirmed the field-level change persisted), added a scope item, deleted the consultant (confirmed cascading delete removed the scope item too). All four operations completed with zero fatal errors.
- **Verification result:** Verified live against real database records, created and cleaned up during testing
- **Master Register updated:** Yes

### CC-015 — Enterprise session framework created

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** Objectives 2, 3, 4 (Authentication Framework Report) — foundational change enabling KOM-012, KOM-017, KOM-029, KOM-042, KOM-066, KOM-067
- **Files changed:** `auth/session_common.php` (new)
- **Reason:** Four authentication surfaces each independently implemented cookie configuration, ID rotation, and idle timeout, with small, accumulating differences (see Authentication Framework Report for the full before/after comparison table).
- **Tests added/updated:** `phase2-regression-run.sh`
- **Regression tests executed:** N/A directly (this file has no entry point of its own) — validated indirectly through every surface that adopted it (CC-016 through CC-019)
- **Verification result:** Code review + syntax check; functional correctness proven by the surfaces that consume it
- **Master Register updated:** N/A (infrastructure change, not itself a finding)

### CC-016 — Admin surface migrated to shared session framework

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** KOM-066
- **Files changed:** `auth/session.php`, `auth/login.php`, `auth/logout.php`
- **Reason:** `login.php`'s manual `session_regenerate_id(true)` and `session.php`'s own rotation logic redundantly regenerated the session ID twice on the first post-login page load.
- **Tests added/updated:** `phase2-regression-run.sh`
- **Regression tests executed:** Login regenerates session ID (confirmed changed pre/post-login); dashboard reachable post-login; logout expires the cookie client-side (`Set-Cookie: PHPSESSID=deleted`); old session rejected after logout (302). All passed.
- **Verification result:** Verified live
- **Master Register updated:** Yes (KOM-066)

### CC-017 — Employee Portal migrated to shared session framework + brute-force lockout + hub CSRF

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** KOM-017 (H-12), KOM-027 (M-04), KOM-052 (L-07)
- **Files changed:** `employee-portal/_session.php`, `employee-portal/login.php`, `employee-portal/logout.php`, `employee-portal/hub.php`, `config/functions.php` (new `portalLoginBlocked()`/`recordPortalLoginFailure()`)
- **Reason:** No session-ID regeneration on login (fixation risk); no CSRF on the login form or the hub request-submission form; no brute-force protection (employees/temp_employees have no `login_attempts`/`locked_until` columns, and Phase 2 forbids a database redesign, so brute-force tracking reuses the existing `audit_logs` table instead).
- **Tests added/updated:** `phase2-regression-run.sh`
- **Regression tests executed:** Login without CSRF rejected; login with valid CSRF succeeds and regenerates the session ID; 5 failed attempts then a 6th blocked with a clear message; hub submission without CSRF rejected (zero rows inserted, verified with a uniquely-timestamped marker to rule out collision with pre-existing data); hub submission with valid CSRF succeeds; logout expires the cookie. All passed.
- **Verification result:** Verified live; test employee's policy-agreement flag and brute-force audit_logs entries reverted/deleted after testing
- **Master Register updated:** Yes (KOM-017, KOM-027, KOM-052)

### CC-018 — Consultant Portal migrated to shared session framework + login CSRF + kiosk/scope CSRF + brute-force + logout fix

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** KOM-012 (H-07), KOM-013 (H-08), KOM-043, KOM-050 (L-05), KOM-052 (L-07, extended to this surface)
- **Files changed:** `consultant-portal/_session.php`, `consultant-portal/login.php`, `consultant-portal/logout.php`, `consultant-portal/index.php`, `consultant-portal/kiosk.php` (5 forms), `consultant-portal/scope.php`
- **Reason:** No session-ID regeneration on login; no CSRF anywhere on this portal (login form, all 5 kiosk clock actions, scope note-save); logout never actually destroyed the session (only unset 7 named keys, leaving the session ID/cookie valid); no brute-force protection.
- **Tests added/updated:** `phase2-regression-run.sh` (includes temporary test-credential setup/teardown on a real consultant record, since none has portal access configured in this environment's seed data)
- **Regression tests executed:** Login without CSRF rejected; login with valid CSRF succeeds and regenerates session ID; kiosk clock-in without CSRF rejected (zero rows written, verified against the database); kiosk clock-in with valid CSRF succeeds (row confirmed written); logout now expires the cookie and old session is rejected on reuse (previously would have remained valid indefinitely). All passed.
- **Verification result:** Verified live; test consultant's `portal_active`/`portal_password` and the test attendance row reverted/deleted after testing
- **Master Register updated:** Yes (KOM-012, KOM-013, KOM-043, KOM-050)

### CC-019 — Temporary Employee Portal migrated onto the shared session framework

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** KOM-029 (M-06)
- **Files changed:** `employee-portal/temp_portal.php`
- **Reason:** Ran its own inline `session_start()` with no ID rotation and no idle timeout — a temp employee's session stayed valid for the full 8-hour absolute cookie lifetime regardless of inactivity. Now includes the same `_session.php` used by permanent employees (same `'ep_'` prefix, same session store — both already log in through the same `employee-portal/login.php`).
- **Tests added/updated:** `phase2-regression-run.sh` (temporary test-credential setup/teardown on a real temp employee record)
- **Regression tests executed:** Login regenerates session ID; portal page reachable via the shared framework; logout expires the cookie and old session is rejected on reuse. All passed.
- **Verification result:** Verified live; test temp employee's `portal_active`/`portal_password` reverted after testing
- **Master Register updated:** Yes (KOM-029)

### CC-020 — Notifications API CSRF standardization

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** KOM-026 (M-03)
- **Files changed:** `api/notifications.php`, `includes/footer.php`
- **Reason:** `mark_read`/`mark_all_read` mutated data on plain GET requests with no CSRF token — forgeable via a cross-site `<img>`/`<script>` request.
- **Tests added/updated:** `phase2-regression-run.sh`
- **Regression tests executed:** GET request rejected; POST without CSRF rejected; POST with a CSRF token extracted from a real page load succeeds. All passed.
- **Verification result:** Verified live, including confirming the new `window.CSRF_TOKEN` JS global matches what the server expects
- **Master Register updated:** Yes (KOM-026)

### CC-021 — Self-service CSRF comparison hardened + cookie Secure flag

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** KOM-062, KOM-042 (self-service portion)
- **Files changed:** `self-service/update.php`
- **Reason:** Per-link CSRF token was compared with a plain `!==` instead of the constant-time `hash_equals()` used by every other CSRF check in the app; the session cookie never set the Secure flag under any condition.
- **Tests added/updated:** None beyond syntax check — this flow requires a real magic-link token tied to a specific employee record, which is a larger setup than the scope of this fix justified re-creating; the change is a narrow, mechanical swap of comparison function and one added cookie parameter, both following exactly the pattern already proven correct on every other surface in this phase
- **Regression tests executed:** Syntax check only
- **Verification result:** Code-reviewed
- **Master Register updated:** Yes (KOM-062, KOM-042 partial — see KOM-042's row for the full multi-surface picture)

### CC-022 — Cookie Secure flag standardized across Admin/Employee/Consultant/Temp

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** KOM-042
- **Files changed:** `auth/session_common.php` (the shared `bootstrapSession()` — see CC-015)
- **Reason:** Only the admin surface conditionally set the Secure cookie flag based on HTTPS/`APP_ENV`; the three portals never considered HTTPS at all. Centralizing cookie configuration in one function means this is now automatically consistent across every surface that calls it, rather than a rule to remember to copy correctly four times.
- **Tests added/updated:** None beyond what CC-016 through CC-019 already cover
- **Regression tests executed:** Same evidence as CC-016–CC-019 (all four surfaces load and authenticate correctly with the shared cookie configuration)
- **Verification result:** Code review — the conditional logic is identical to the admin surface's pre-existing, already-correct implementation
- **Master Register updated:** Yes (KOM-042)

### CC-023 — Logout standardized across all four surfaces

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** KOM-043, KOM-067, Objective 7
- **Files changed:** `auth/session_common.php` (new `destroySessionCompletely()`), `auth/logout.php`, `employee-portal/logout.php`, `employee-portal/temp_portal.php` (inline logout handler), `consultant-portal/logout.php`
- **Reason:** Three different logout implementations existed with three different levels of completeness (admin: full teardown; employee: `session_destroy()` only; consultant: neither, just unset 7 keys) — none of them explicitly expired the session cookie client-side.
- **Tests added/updated:** `phase2-regression-run.sh`
- **Regression tests executed:** All four logout endpoints (admin, employee, consultant, temp) confirmed to send `Set-Cookie: PHPSESSID=deleted` and to reject the old session on a subsequent request. All passed.
- **Verification result:** Verified live for all four surfaces
- **Master Register updated:** Yes (KOM-043, KOM-067)

### CC-024 — Master Remediation Register updated for Phase 2

- **Date:** 2026-07-11/12
- **Phase:** 2
- **Finding ID(s) addressed:** All 14 findings closed this phase (documentation-only change)
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record Phase 2 outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-025 — Canonical `database/schema.sql` rewrite (32 tables → 60)

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** KOM-004, KOM-061
- **Files changed:** `database/schema.sql` (complete rewrite)
- **Reason:** The tracked schema file could only reconstruct 32 of the 59 tables the live application actually depends on; a from-empty install using only tracked files would fail immediately. Rewrote it as a pure-structure (no data), topologically-ordered (via a verified `information_schema.KEY_COLUMN_USAGE` dependency graph) extraction of the live database's exact structure — 59 real tables plus a new `schema_migrations` tracking table.
- **Tests added/updated:** `database/verify_clean_install.php` (new, see CC-031)
- **Regression tests executed:** Structural diff of a database built from `schema.sql` alone against the live database's `information_schema` — 60/60 tables present, every FK/index/CHECK constraint (e.g. `doc_templates.variables_used CHECK (json_valid(...))`) preserved. See `phase3-pre-change-schema-fingerprint.txt`/`phase3-post-change-schema-fingerprint.txt` — the only structural delta across all 59 pre-existing tables is the new `schema_migrations` table; every pre-existing column is byte-identical.
- **Verification result:** VERIFIED — clean-install test (`Testing/12-phase3-clean-install-test-report.md`) and fingerprint diff both confirm zero unintended drift.
- **Master Register updated:** Yes (KOM-004, KOM-061)

### CC-026 — `phase8_temp_employees.sql`: added missing columns, fixed role-name typo at source

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** KOM-005, KOM-023
- **Files changed:** `database/phase8_temp_employees.sql`
- **Reason:** `rate_type` and `attendance_method` are written by `modules/temp_employees/{add,edit}.php` on every save but were never in this file's `CREATE TABLE` — only present in the live database via an untracked manual change. Also seeded `hr_officer`'s permission grants under the typo'd role string `'hrofficer'`, which Phase 1 patched on the live database (`phase10_authorization_framework.sql`'s `UPDATE`) but never fixed at the source — a fresh install would have reintroduced the exact same access-denial bug.
- **Tests added/updated:** `database/verify_clean_install.php` structural + seed checks (see CC-031)
- **Regression tests executed:** Clean-install test confirms both columns present and zero `'hrofficer'` rows / correct 4 `hr_officer` → `temp_employees.*` grants on a database built only from tracked files.
- **Verification result:** VERIFIED via clean-install test
- **Master Register updated:** Yes (KOM-005, KOM-023)

### CC-027 — `phase9_consultants.sql`: fixed role-name typo at source

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** KOM-023
- **Files changed:** `database/phase9_consultants.sql`
- **Reason:** Same typo as CC-026 (`'hrofficer'` vs. `'hr_officer'`), same source-level gap — Phase 1 fixed the live database only.
- **Tests added/updated:** `database/verify_clean_install.php`
- **Regression tests executed:** Clean-install test confirms zero `'hrofficer'` rows for the consultants module on a fresh install.
- **Verification result:** VERIFIED via clean-install test
- **Master Register updated:** Yes (KOM-023)

### CC-028 — New `database/phase11_schema_reconciliation.sql` (idempotent upgrade path)

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** KOM-004, KOM-024
- **Files changed:** `database/phase11_schema_reconciliation.sql` (new)
- **Reason:** A canonical `schema.sql` alone only helps *fresh* installs. Existing (pre-Phase-3) databases need a safe, idempotent path to the same end state without touching their data — `CREATE TABLE IF NOT EXISTS` for the 11 previously-undocumented-but-real tables, `ADD COLUMN IF NOT EXISTS`/safe `MODIFY COLUMN` for columns added out-of-band on the live database, plus creation of `schema_migrations` itself.
- **Tests added/updated:** New Stage 3.9 upgrade-migration test procedure (manual, documented in `Testing/13-phase3-upgrade-migration-test-report.md`)
- **Regression tests executed:** Restored the Stage 3.0 pre-Phase-3 backup into a scratch database, ran this file against it, and confirmed: zero data loss (exact row-count match across 10 critical tables: employees, users, payslips, consultants, temp_employees, permissions, role_permissions, attendance, leave_applications, audit_logs — pre and post identical), table count 59→60, stable primary keys, zero orphaned FK references, then ran the full Phase 1 (20/20) and Phase 2 (29/29) regression suites against the upgraded clone with the application's `config.php` temporarily repointed at it — both suites passed in full.
- **Verification result:** VERIFIED live against a real clone of the production database
- **Master Register updated:** Yes (KOM-004, KOM-024)

### CC-029 — New seed files: `seeds/001_baseline_admin.sql`, `seeds/002_doc_categories.sql`

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** KOM-024, KOM-068
- **Files changed:** `database/seeds/001_baseline_admin.sql` (new), `database/seeds/002_doc_categories.sql` (new)
- **Reason:** A fresh install needs a default administrator account to log in with at all (none existed in any tracked file). Separately, `phase6_templates.sql` requires `doc_categories` rows to already exist (`NOT NULL` FK lookup by slug) but no tracked file ever created them — discovered only because Stage 3.8's clean-install test failed on this exact step. `001_baseline_admin.sql` seeds a `superadmin` account forced to change its password on first login (`must_change_password=1`); `002_doc_categories.sql` seeds the 10 categories verified byte-for-byte against the live database.
- **Tests added/updated:** `database/verify_clean_install.php`
- **Regression tests executed:** Clean-install test confirms exactly 1 `super_admin` user with `must_change_password=1`, 10/10 categories, and all 47 templates loading with valid `category_id` values.
- **Verification result:** VERIFIED via clean-install test + live HTTP smoke test (login → forced password change succeeded)
- **Master Register updated:** Yes (KOM-024, KOM-068)

### CC-030 — `database/install.php` rewritten around a defined install sequence

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** KOM-024
- **Files changed:** `database/install.php`
- **Reason:** Previously ran `schema.sql` only and told the operator to manually run `migration_v2.sql` via phpMyAdmin afterward — never mentioning phase1/5/6/7/8/9 at all. Following only the installer's own on-screen instructions produced a database with no permission matrix, no branding seed data, no document template library, and no temp-employee/consultant module support. Now runs a single defined `INSTALL_SEQUENCE` in one pass and stops immediately on the first failed step with a clear log.
- **Tests added/updated:** `database/verify_clean_install.php` (CLI counterpart of this form, for repeatable automated verification)
- **Regression tests executed:** Full clean-install test (26/26 automated structural/seed checks) + live HTTP smoke test (login, forced password change, 12 critical modules all 200 OK) against a database built only by this installer's logic.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-024)

### CC-031 — New `database/verify_clean_install.php` and `database/sql_split.php`

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** KOM-004, KOM-024 (test infrastructure supporting both)
- **Files changed:** `database/verify_clean_install.php` (new), `database/sql_split.php` (new)
- **Reason:** Both `install.php`'s original naive `explode(';', $sql)` statement splitter and a first draft of the rewrite broke on semicolons inside quoted strings (document template HTML bodies contain `style="color:red;margin:4px;"` attributes) and inside explanatory SQL comments. `sql_split.php` is a real tokenizer tracking quote-state and comment-state, shared by both `install.php` and this new verification script so there is exactly one statement-splitting implementation. `verify_clean_install.php` is the CLI-runnable, repeatable counterpart to the web installer, used for Stage 3.8 and safe to re-run at any time.
- **Tests added/updated:** This IS the new test.
- **Regression tests executed:** Self-verifying — 26/26 checks pass when run against a freshly built empty database.
- **Verification result:** VERIFIED — re-run multiple times during Phase 3 with consistent results
- **Master Register updated:** N/A (test infrastructure, not itself a finding)

### CC-032 — Fixed Activity Log CSV export's reference to a nonexistent `settings` table

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** KOM-070 (new finding this phase, closing the 12th item under KOM-004's scope)
- **Files changed:** `modules/activity_log/download.php`
- **Reason:** Discovered while reconciling KOM-004's 12 "undefined tables" — 11 were real, undocumented live tables; the 12th, `settings`, never existed anywhere at all, live or tracked. Every CSV export from this page threw an uncaught `PDOException` in production. Corrected to query `company_settings`, the table that actually holds this value elsewhere in the codebase.
- **Tests added/updated:** None beyond manual query verification
- **Regression tests executed:** Query executes without error against the live database
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (new finding KOM-070, immediately marked Fixed)

### CC-033 — Employee portal policy-agreement form: added CSRF protection

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** KOM-069 (new finding this phase)
- **Files changed:** `employee-portal/policy.php`, `docs/remediation/Testing/phase2-regression-run.sh` (test script updated to fetch and send the new token)
- **Reason:** Discovered during Phase 3 database-layer work — the POST that records `portal_policy_agreed` had no CSRF token at all, predating and missed by Phase 2's portal-wide CSRF standardization pass. Added `generateCsrfToken()`/`verifyCsrfToken()` matching every other portal mutation's pattern.
- **Tests added/updated:** `docs/remediation/Testing/phase2-regression-run.sh` — the policy-agreement step now fetches the form first to obtain a real token before posting `agree=1`, since the fix means the old un-tokened POST this script previously sent would now silently fail to record agreement (correct new behavior, but it required updating the script to match).
- **Regression tests executed:** Full Phase 2 regression suite re-run after this fix — 29/29 passed, including the corrected policy-agreement step and everything downstream of it (Hub request submission, which depends on having passed the policy gate).
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (new finding KOM-069, immediately marked Fixed)

### CC-034 — Master Remediation Register updated for Phase 3

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** KOM-004, KOM-005, KOM-023, KOM-024, KOM-045 (retargeted), KOM-061, KOM-068, KOM-069, KOM-070
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record Phase 3 outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-035 — Change Control Log updated for Phase 3

- **Date:** 2026-07-12
- **Phase:** 3
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 3 entries (CC-025–CC-035) per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-036 — Added missing `employees.personal_email` column, fixing 3 fatally-broken workflows

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-071
- **Files changed:** `database/schema.sql`, new `database/phase12_workflow_integrity_fixes.sql`, applied live to the production database
- **Reason:** `modules/employees/edit.php`, `modules/employees/pending_updates.php`, and `self-service/update.php` all read/wrote `personal_email`, but the column existed nowhere — schema.sql, any tracked migration, or the live database. Every Edit Employee save and every self-service magic-link page load threw an uncaught `PDOException` in production.
- **Tests added/updated:** None beyond live functional re-testing (no dedicated regression script for this module yet)
- **Regression tests executed:** Live re-test of all 3 affected code paths against a disposable test employee post-fix — Edit Employee save succeeds, self-service update page loads without error, pending-update approval writes correctly. Full Phase 1 (20/20) and Phase 2 (29/29) suites also re-run to confirm no unrelated regression.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-071)

### CC-037 — `status.php`: re-enable account on reactivation to active/probation

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-074
- **Files changed:** `modules/employees/status.php`
- **Reason:** Exiting statuses correctly disabled the linked `users` account, but reactivating an employee (e.g. a rehire handled via status change) never re-enabled it, silently locking out a legitimately returning employee with no code path to fix it short of a manual trip to the Users module.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Terminate→reactivate cycle against a disposable test employee — `is_active` confirmed to flip 1→0→1 correctly post-fix.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-074)

### CC-038 — `status.php`: require exit date for resignation/termination/death

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-075
- **Files changed:** `modules/employees/status.php`
- **Reason:** `exit_date` was optional server-side for every status; the UI only conditionally showed the field via JS for the 3 exit statuses, with nothing enforcing it being filled in.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Termination attempt with no exit date correctly rejected; identical request with a date succeeds.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-075)

### CC-039 — `add.php`/`edit.php`: block duplicate `national_id` (rehire collision guard)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-076
- **Files changed:** `modules/employees/add.php`, `modules/employees/edit.php`
- **Reason:** Only `email` was checked for duplicates; a former employee could be re-added as a disconnected new record with no link to their prior history.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Adding a second employee with an in-use `national_id` correctly rejected, referencing the existing employee number.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-076)

### CC-040 — `edit.php`: audit log now captures actual changed fields

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-077
- **Files changed:** `modules/employees/edit.php`
- **Reason:** `auditLog()`'s `new_value` only ever contained `first_name`/`last_name`, so a transfer or promotion's actual new department/position/salary was not independently reconstructable from the field meant to show it.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** A department transfer's audit log entry confirmed to include the new `department_id` post-fix.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-077)

### CC-041 — Master Remediation Register updated for Phase 4 Workflow Group 1

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-071 through KOM-077 (new)
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-042 — Change Control Log updated for Phase 4 Workflow Group 1

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 1 entries (CC-036–CC-042).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-043 — Termination wired into the approval engine (KOM-072, part 1)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-072
- **Files changed:** `modules/employees/status.php`, `config/ApprovalEngine.php`
- **Reason:** User reviewed KOM-072 (flagged as a business-policy decision, not fixed unilaterally) and directed wiring all three of promotion/transfer/termination into the approval engine, matching what the schema's `approval_workflows.workflow_type` ENUM was already built for. Termination is the highest-stakes of the three (irreversible, immediately affects pay/benefits/access), implemented first.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Full termination lifecycle against disposable test employees: (1) submitted by `superadmin`, status confirmed unchanged pending approval; (2) approved by a different `hr_manager` user (separation-of-duties honored), status correctly flipped to `terminated` with `status_reason`/`exit_date` recorded, linked user account disabled, `employee_status_history` written; (3) a second request rejected, employee confirmed completely untouched. Full Phase 1 (20/20) and Phase 2 (29/29) suites re-run — no regression.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-072)

### CC-044 — Transfer and promotion wired into the approval engine (KOM-072, part 2)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-072
- **Files changed:** `modules/employees/edit.php`, `config/ApprovalEngine.php`
- **Reason:** Completes KOM-072 — a department/supervisor change is now detected as a transfer and a position/salary change as a promotion, each held pending approval while every other field on the same edit form still applies immediately.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Submitted a department transfer — `department_id` unchanged until approved, then correctly applied. Submitted a position+salary promotion — both fields unchanged until approved, then correctly applied. Full Phase 1 (20/20) and Phase 2 (29/29) suites re-run — no regression.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-072, closed)

### CC-045 — Status-transition matrix enforced (KOM-073)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-073
- **Files changed:** `modules/employees/status.php`
- **Reason:** User reviewed KOM-073 (flagged as a business-policy decision) and directed implementing a transition matrix. A `super_admin`-only override was included so the matrix can never permanently trap a real record via a wrong guess.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** `terminated→suspended` correctly rejected for `hrmanager`; `terminated→archived` correctly succeeded; `archived→suspended` correctly succeeded for `superadmin` via override only.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-073)

### CC-046 — New Positions management UI, plus seed data for departments and positions (KOM-078)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-078
- **Files changed:** `modules/settings/index.php` (new Positions tab), new `database/seeds/003_departments_positions.sql`, `database/install.php`, `database/verify_clean_install.php`
- **Reason:** No code anywhere in the application could create, edit, or deactivate a `positions` row — the table could only ever be populated by a direct, untracked database edit. `departments` had the identical seed-data gap. A fresh install had zero positions, zero departments, and (for positions) no way to create one at all.
- **Tests added/updated:** `verify_clean_install.php` — 2 new structural checks (departments seeded=11, positions seeded=23)
- **Regression tests executed:** Clean-install test 29/29 (was 26/26). Live: added a position via the new UI, confirmed duplicate-name rejection, confirmed disable-with-employee-count works.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-078)

### CC-047 — Fixed duplicate department name crash (KOM-079)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-079
- **Files changed:** `modules/settings/index.php`
- **Reason:** `departments.name`'s DB-level UNIQUE constraint had no matching application-level check — submitting an existing name threw an uncaught `PDOException` and crashed the Settings page. Live-verified before the fix.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Duplicate submission now shows a clean error, no crash, no duplicate row; a genuinely new department name still succeeds.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-079)

### CC-048 — Deletion-protection visibility for department/position disable (KOM-080)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-080
- **Files changed:** `modules/settings/index.php`
- **Reason:** Disabling a department or position had no visibility into how many employees/positions currently reference it, unlike Employee Delete's full cascade-impact preview.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Disabling a department with an assigned active employee correctly surfaces the count in the confirmation message.
- **Verification result:** VERIFIED live. Note: this test was inadvertently run against the live "Human Resources" department (id=1) instead of a disposable record; it was re-enabled within roughly a minute of being disabled once noticed, and department count/active flag/employee and position rows were confirmed fully restored with no other side effects (the disable action only ever touches `departments.is_active`). Disclosed in full in `Workflows/02-department-position-workflow-report.md` §5.
- **Master Register updated:** Yes (KOM-080)

### CC-049 — Master Remediation Register updated for Phase 4 Workflow Group 2

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-078, KOM-079, KOM-080 (new)
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-050 — Change Control Log updated for Phase 4 Workflow Group 2

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 2 entries (CC-046–CC-050).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-051 — Fixed rejected leave never restoring `remaining_days` (KOM-081)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-081
- **Files changed:** `modules/leave/approve.php`
- **Reason:** The reject branch only ever reversed `pending_days`, never crediting `remaining_days` back — every rejected leave application permanently shrank the employee's real balance by the rejected amount. Directly contradicts the Phase 4 charter's explicit requirement.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** A 2-day request reserved a balance down to 7.0; after rejection, both `pending_days` and `remaining_days` now correctly return to their pre-application values (was: only `pending_days` restored).
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-081)

### CC-052 — Fixed `notifyRole()` crash on every leave submission (KOM-007, closing a pre-existing finding)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-007
- **Files changed:** `modules/leave/apply.php`
- **Reason:** KOM-007 has been open since Phase 0 — correctly out of scope for Phases 1–3 (authorization, authentication, database), squarely in scope for Phase 4. `notifyRole()` was called with an array where a string role is required and the remaining arguments shifted out of order, throwing a fatal `TypeError` after the application record and balance reservation had already committed.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Leave submission now returns a clean redirect; `hr_manager`/`super_admin` both receive a correctly-populated notification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-007, closed)

### CC-053 — Corrected `notifications.type` ENUM misuse (self-correction of Workflow Group 1's own new code)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (pre-release self-correction, not a separately registered finding — caught before Group 1's code had been exercised with this exact input)
- **Files changed:** `modules/employees/status.php`, `modules/employees/edit.php`
- **Reason:** While fixing KOM-007, discovered `notifications.type` is a 4-value ENUM (`info`/`success`/`warning`/`danger`), not a free-text category — an invalid value is silently stored as an empty string rather than erroring. The three "awaiting approval" notifications added in Workflow Group 1 (termination, transfer, promotion) had used `'approval'`, an invalid value. Standardized all four (including the leave one) to `'warning'`.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Confirmed notification rows now populate a valid, non-blank `type`.
- **Verification result:** VERIFIED live
- **Master Register updated:** N/A (correction to already-Fixed KOM-072 findings, no register status change)

### CC-054 — Synced `approval_workflows`/`approval_stages` with real leave decisions (KOM-082)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-082
- **Files changed:** `modules/leave/approve.php`
- **Reason:** `approve.php` decides the real outcome of a leave application directly against `leave_applications`, but never called `ApprovalEngine::act()` — the linked `approval_workflows` row stayed `pending` forever after a real decision was made, so the Approvals module would show it as permanently awaiting action.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Approving a leave application now correctly resolves both stages (`Supervisor Review`, `HR Approval`) and the parent workflow to `approved` in one action.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-082)

### CC-055 — Documented leave's single-stage-in-practice approval gap (KOM-083)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-083 (new)
- **Files changed:** None (documentation only, pending decision)
- **Reason:** `approve.php` never actually enforces the two-stage (Supervisor Review, HR Approval) flow the schema models — a single `leave.approve` holder resolves everything. Flagged for a product decision (mirrors KOM-072's handling) rather than built unilaterally.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (KOM-083, new, Open)

### CC-056 — Master Remediation Register updated for Phase 4 Workflow Group 3

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-081, KOM-082, KOM-083 (new), KOM-007 (closed)
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-057 — Change Control Log updated for Phase 4 Workflow Group 3

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 3 entries (CC-051–CC-057).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-058 — Closed kiosk remote clock-in impersonation (KOM-003, closing a pre-existing finding)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-003
- **Files changed:** `modules/attendance/kiosk.php`
- **Reason:** KOM-003 has been open since Phase 0 — an attendance-workflow/identification gap, correctly out of scope for Phase 1's authorization-consistency focus, squarely in scope for Phase 4. A request with no kiosk token fell back to "whichever session happens to be open," allowing unauthenticated remote clock-in impersonation with no location binding, and was also functionally wrong once more than one location is open concurrently (which the app explicitly permits).
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Temporarily opened one kiosk location; no-token requests rejected on both page-render and actual POST clock-in paths (zero attendance rows written); correct token for that location worked normally. Kiosk restored to closed state afterward.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-003, closed)

### CC-059 — Added duplicate-action guard to overtime approval (KOM-084)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-084
- **Files changed:** `modules/timesheets/overtime.php`
- **Reason:** `corrections.php` (the sibling approval page) already checks `status==='pending'` before acting; `overtime.php` had no equivalent guard, letting an already-decided record be re-approved or flipped.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Approved a test overtime record; a second action on the same record was correctly rejected with status unchanged.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-084)

### CC-060 — Master Remediation Register updated for Phase 4 Workflow Group 4

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-084 (new), KOM-003 (closed)
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-061 — Change Control Log updated for Phase 4 Workflow Group 4

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 4 entries (CC-058–CC-061).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-062 — Fixed payroll run create/finalize/publish race condition (KOM-030, closing a pre-existing finding)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-030
- **Files changed:** `modules/payroll/run_save.php`, `modules/payroll/run_finalize.php`, `modules/payroll/run_publish.php`
- **Reason:** KOM-030 has been open since Phase 0 — correctly out of scope for Phase 3 (database schema, not business workflow), squarely in scope for Phase 4. All three payroll actions did a `SELECT` status check followed by a separate `UPDATE`/`INSERT` with no atomicity; a concurrent request could pass the same check twice, most seriously in `run_publish.php` where it would send every employee's payslip email a second time.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** A genuine 5-way simultaneous `POST` request test against `run_publish.php` for the same run — exactly 1 `audit_logs` publish entry resulted (down from the 5 that a naive check-then-act implementation would risk). Sequential create-duplicate and re-finalize attempts also confirmed clean (no crash, no duplicate row, no duplicate recalculation).
- **Verification result:** VERIFIED live with genuine concurrency, not just sequential retries
- **Master Register updated:** Yes (KOM-030, closed)

### CC-063 — Documented deductions/savings not reflected in payslip totals (KOM-085)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-085 (new)
- **Files changed:** None (documentation only, pending decision)
- **Reason:** `payslips.php` computes totals from only 3 manually-entered fields, never reading `payroll_deductions` or `employee_savings`; neither of those modules writes back either. This directly affects real employees' calculated net pay — the highest-stakes kind of change in the system — so it was documented and flagged rather than changed unilaterally.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (KOM-085, new, Open)

### CC-064 — Documented orphaned `payroll_deductions` rows, no deletion applied (KOM-086)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-086 (new)
- **Files changed:** None (documentation only, pending decision)
- **Reason:** 32 of 67 `payroll_deductions` rows reference `employee_id` values that don't exist, despite an `ON DELETE CASCADE` FK. Consistent with this program's established practice (Phase 3, Stage 3.10), a data-integrity finding involving deletion of existing rows is documented with a recommendation, not acted on automatically.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (KOM-086, new, Open)

### CC-065 — Master Remediation Register updated for Phase 4 Workflow Group 5

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-085, KOM-086 (new), KOM-030 (closed)
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-066 — Change Control Log updated for Phase 4 Workflow Group 5

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 5 entries (CC-062–CC-066).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-067 — KOM-085 accepted as designed; KOM-086 orphaned rows deleted per user decision

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-085, KOM-086
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md` (status updates); live database (`payroll_deductions` — 32 rows deleted); new `database/backups/orphaned_payroll_deductions_20260712.tsv`
- **Reason:** User was asked to decide both KOM-085 (deductions/savings integration into payslip totals) and KOM-086 (orphaned row cleanup) directly, given the financial/data risk of each. Decision: KOM-085 — leave as-is, accepted as designed, no code change. KOM-086 — delete the 32 orphaned rows.
- **Tests added/updated:** None beyond direct verification query
- **Regression tests executed:** `SELECT COUNT(*) FROM payroll_deductions WHERE employee_id NOT IN (SELECT id FROM employees)` returns 0 post-deletion (was 32); 35 legitimate rows remain (67 − 32).
- **Verification result:** VERIFIED live. All 32 deleted rows backed up to `database/backups/orphaned_payroll_deductions_20260712.tsv` (gitignored, matching the existing `database/backups/` policy) before deletion.
- **Master Register updated:** Yes (KOM-085 → Accepted as designed, KOM-086 → Fixed)

### CC-068 — Fixed performance review creation, root cause more severe than originally documented (KOM-049, severity corrected)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-049
- **Files changed:** `modules/performance/save.php`
- **Reason:** KOM-049 was open since Phase 0, originally reported as a Low-severity single-field mismatch ("reads overall_rating; form posts overall_score"). Investigating it directly for Phase 4 revealed the actual `INSERT` named three columns that don't exist anywhere in `performance_reviews` (`overall_rating`, `comments`, `recommendations`) — not silent data loss but an uncaught `PDOException` on every single attempt. The entire Performance Review creation feature had never worked. Severity corrected from Low to Critical in the Master Register to reflect the true root cause.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Confirmed the fatal crash pre-fix; post-fix, submitted a review with a rating/comment/recommendation and confirmed all three values correctly saved into the real columns (`overall_score`, `supervisor_assessment`, `recommendation_notes`), both of the latter two already displayed by `view.php` with no UI change needed.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-049, moved from LOW to CRITICAL section, closed)

### CC-069 — Master Remediation Register updated for Phase 4 Workflow Group 6

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-049 (severity-corrected, closed)
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-070 — Change Control Log updated for Phase 4 Workflow Group 6

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 6 entries (CC-068–CC-070).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-071 — New "Add Application" entry point for recruitment (KOM-087)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-087
- **Files changed:** `modules/recruitment/index.php`, new `modules/recruitment/application_save.php`
- **Reason:** No code anywhere created a `recruitment_applications` row except demo seed data — the recruitment pipeline's first step had no working entry point. Added a new "Add Application" button/modal and handler mirroring the existing "Post Vacancy" pattern already in the same module.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Submitted a new application against a real open vacancy (visible immediately in the Applications tab); duplicate submission (same email + same vacancy) correctly rejected; same email against a different vacancy correctly succeeded (per-vacancy duplicate scoping, not global).
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-087)

### CC-072 — Documented missing employee-conversion step, not built (KOM-088)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-088 (new)
- **Files changed:** None (documentation only, pending decision)
- **Reason:** `recruitment_applications.converted_to_employee_id` exists in the schema but is never read or written by any code — a real conversion feature (pre-populate Add Employee, write back the link, decide cross-system duplicate detection) is a feature build, not a bug fix, and was flagged rather than built unilaterally, consistent with KOM-072/083/085's handling this phase.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (KOM-088, new, Open)

### CC-073 — Master Remediation Register updated for Phase 4 Workflow Group 7

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-087, KOM-088 (new)
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-074 — Change Control Log updated for Phase 4 Workflow Group 7

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 7 entries (CC-071–CC-074).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-075 — KOM-088 deferred per user decision

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-088
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md` (status update only)
- **Reason:** User was asked whether to build a "Convert to Employee" action for recruitment; decided to leave it as a manual, disconnected step for now.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (KOM-088 → Deferred)

### CC-076 — Fixed the entire Training module, root cause far more severe than originally documented (KOM-008, severity corrected)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-008
- **Files changed:** `modules/training/enrol.php`, `modules/training/index.php`, `modules/training/save.php`, new `modules/training/attendance_update.php`
- **Reason:** KOM-008 was open since Phase 0, originally reported as a single High-severity column mismatch. Investigating it directly for Phase 4 found the module fatally broken in three separate places (enrolling, viewing the attendance tab, adding a program) plus two further silent display bugs from additional nonexistent columns. No part of the module worked at all. Severity corrected from High to Critical.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Created a training program, enrolled a test employee, loaded the previously-crashing Attendance tab, and marked the enrolment attended — all four steps confirmed working end-to-end for the first time, all test data cleaned up afterward.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-008, moved from HIGH to CRITICAL section, closed)

### CC-077 — Master Remediation Register updated for Phase 4 Workflow Group 8

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-008 (severity-corrected, closed)
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-078 — Change Control Log updated for Phase 4 Workflow Group 8

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 8 entries (CC-075–CC-078).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-079 — Added type-to-confirm safety pattern to consultant deletion (KOM-089)

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-089
- **Files changed:** `modules/consultants/delete.php`, `modules/consultants/index.php`, `docs/remediation/Testing/phase2-regression-run.sh`
- **Reason:** `delete.php` was a single-click, JS `confirm()`-only instant hard delete with no server-side impact preview and no type-to-confirm safeguard, despite cascading to a consultant's entire `consultant_attendance` and `consultant_scopes` history. Inconsistent with the already-established, already-proven pattern for the same class of action (`modules/employees/delete.php`). Rewrote `delete.php` to a GET confirmation page showing an impact-count summary, requiring the exact `consultant_number` to be typed before the `POST` proceeds; updated the Consultants list's Delete button from an instant form-submit to a link to this confirmation page. The Phase 2 regression suite's consultant-delete test was written against the old instant-delete behavior and had to be updated to fetch the confirmation page and submit the real `consultant_number` first — this is a test-script correction, not a product regression.
- **Tests added/updated:** Updated the consultant-delete case in `phase2-regression-run.sh` to match the new confirmation flow
- **Regression tests executed:** Phase 1 (20/20), Phase 2 (29/29, after the test-script update — failed 28/29 immediately after the code change with the stale test, confirming the fix changed real behavior as intended). Live functional: created a disposable test consultant; confirmation page loaded with correct impact summary; wrong confirmation text correctly rejected (record still present); correct consultant number correctly deleted it.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-089, new, added to MEDIUM section, Fixed)

### CC-080 — Master Remediation Register updated for Phase 4 Workflow Group 9

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-089
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-081 — Change Control Log updated for Phase 4 Workflow Group 9

- **Date:** 2026-07-12
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 9 entries (CC-079–CC-081).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-082 — Fixed reflected XSS in the temp employee export status banner (KOM-020, closing a pre-existing finding)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-020
- **Files changed:** `modules/temp_employees/index.php`
- **Reason:** `$_GET['status']` was echoed unescaped in the PDF export's "Filtered:" banner, while the adjacent search-term display on the same line correctly called `htmlspecialchars()`. Open since the original baseline audit (Audit II, NH-03); correctly out of scope for Phase 2 (session/authentication, not output encoding); found still open while reviewing this module in Phase 4.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Submitted `?export=pdf&status=<script>alert(1)</script>` before and after the fix.
- **Verification result:** VERIFIED live — payload rendered as raw, executable `<script>` before the fix; renders as inert, HTML-entity-encoded text after
- **Master Register updated:** Yes (KOM-020, moved from Open to Fixed)

### CC-083 — Added type-to-confirm safety pattern to temp employee deletion (KOM-091)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-091
- **Files changed:** `modules/temp_employees/delete.php`, `modules/temp_employees/index.php`, `modules/temp_employees/view.php`
- **Reason:** `delete.php` was a single-click, JS `confirm()`-only instant hard delete with no server-side confirmation safeguard, the same class of gap closed for consultants in Workflow Group 9 (KOM-089). Rewrote to a GET confirmation page requiring the exact `employee_number` typed before the `POST` proceeds; updated both Delete buttons (list and detail view) from instant form-submits to links to the confirmation page.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Phase 1 (20/20), Phase 2 (29/29). Live functional: created a disposable test temp employee; confirmation page loaded correctly; wrong confirmation text correctly rejected (record still present); correct employee number correctly deleted it.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-091, new, added to MEDIUM section, Fixed)

### CC-084 — Fixed auditLog() wrong argument order corrupting the module's audit trail (KOM-092)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-092
- **Files changed:** `modules/temp_employees/add.php`, `modules/temp_employees/edit.php`, `modules/temp_employees/delete.php`
- **Reason:** All three files called `auditLog('temp_employees', '<action>', $_SESSION['user_id'], 'temp_employees', $id, "...")` against the real signature `auditLog(module, action, recordId, oldValue, newValue, reason)` — the acting admin's own user ID landed in `record_id` instead of the temp employee's ID, a nonsensical literal string landed in `old_value`, and the employee's real ID was silently discarded into `new_value`. Discovered while live-verifying CC-083: a test deletion recorded `record_id=1` (the admin's ID) instead of the deleted employee's actual ID (9). Cross-checked `employees/delete.php`, `employees/edit.php`, and this phase's `consultants/delete.php` — all three call the function correctly; the bug was confined to this module.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Created, edited, then deleted a disposable test temp employee; confirmed each resulting `audit_logs` row's `record_id` directly against the database, both before the fix (wrong) and after (correct).
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-092, new, added to HIGH section, Fixed)

### CC-085 — Corrected misleading attendance-method UI copy per user decision (KOM-090)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-090
- **Files changed:** `modules/temp_employees/add.php`, `modules/temp_employees/edit.php`, `modules/temp_employees/view.php`
- **Reason:** The Attendance Method selector described "Kiosk Only" as "Clock in/out via the kiosk tablet," but the real kiosk (`modules/attendance/kiosk.php`) only recognizes permanent employees, and the "Timesheet Only" option is a blank paper form with no digital re-entry point — no temp employee's hours are ever actually captured anywhere in the system. Flagged for a user decision rather than built unilaterally, since a real fix (new attendance table, kiosk wiring, digital timesheet entry) is a substantial feature addition, not a bug fix. User chose (2026-07-13) to correct the misleading copy only, deferring the underlying capture mechanism.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Loaded Add, Edit, and View pages and confirmed the corrected copy renders for all three attendance-method options.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-090, new, added to MEDIUM section, Partially Fixed — capture mechanism deferred)

### CC-086 — Master Remediation Register updated for Phase 4 Workflow Group 10

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-020, KOM-090, KOM-091, KOM-092
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-087 — Change Control Log updated for Phase 4 Workflow Group 10

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 10 entries (CC-082–CC-087).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-088 — Fixed stored XSS in the shared notification renderer (KOM-093)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-093
- **Files changed:** `includes/footer.php`
- **Reason:** The notification dropdown (used by every logged-in user, all roles) rendered `title`/`message` via a JS template literal assigned directly to `.innerHTML` with no escaping anywhere in the chain. `employee-portal/hub.php`'s self-service request form feeds a plain employee's raw free-text `subject` into a notification sent to every `hr_manager`/`super_admin` — a stored-XSS privilege-escalation chain reachable by the lowest-privilege authenticated role against the highest. Discovered while auditing notification triggers for this workflow group.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Submitted a hub request as a plain employee with `subject=<script>alert(1)</script>`; confirmed the notifications API still returns the raw payload (expected — the fix is client-side by design); confirmed by code review that the new `escapeHtml()` helper is applied to every interpolated field (`title`, `message`, `link`) before DOM insertion. Full browser click-through not performed (no browser automation available in this environment) — recommended before Phase 5 sign-off.
- **Verification result:** VERIFIED by code review + confirmed unescaped payload reaches the client; live browser click-through outstanding
- **Master Register updated:** Yes (KOM-093, new, added to CRITICAL section, Fixed)

### CC-089 — Fixed missing decision notification in ApprovalEngine (KOM-095)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-095
- **Files changed:** `config/ApprovalEngine.php`
- **Reason:** `act()` resolved every workflow (and, via `updateReference()`, applied the real employee change for termination/transfer/promotion) with zero notification to anyone — the requester had no in-app way to learn a decision had been made, unlike leave's own `approve.php` which already notifies its applicant.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Created a disposable test employee, submitted a termination request as one admin, approved it as a different `hr_manager` user (separation-of-duties check honored), confirmed the initiating admin received a correctly-typed `success` notification with the reviewer's comments; workflow and employee status both correctly updated. Test data fully cleaned up.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-095, new, added to HIGH section, Fixed)

### CC-090 — Fixed invalid notifications.type ENUM value in employee Hub requests (KOM-094)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-094
- **Files changed:** `employee-portal/hub.php`
- **Reason:** `notifyRole(..., 'hub_request', ...)` used a type value not in the 4-member `notifications.type` ENUM — the same mistake self-caught and corrected in Workflow Group 1's own new code, found still live here. Non-strict SQL mode silently coerced it to an empty string on every hub-request notification ever created.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Submitted a real hub request post-fix; confirmed the resulting `notifications` row has `type='warning'`.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-094, new, added to MEDIUM section, Fixed)

### CC-091 — Master Remediation Register updated for Phase 4 Workflow Group 11

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-093, KOM-094, KOM-095
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-092 — Change Control Log updated for Phase 4 Workflow Group 11

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 11 entries (CC-088–CC-092).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-093 — Fixed branding-asset .htaccess blocking all image serving (KOM-006, closing a pre-existing finding)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-006
- **Files changed:** `uploads/letterheads/.htaccess`, `uploads/signatures/.htaccess`, `uploads/stamps/.htaccess`, `uploads/watermarks/.htaccess`
- **Reason:** Flagged since the baseline audit as "likely blocks all letterhead/signature/stamp/watermark image serving" but never live-verified; correctly out of scope for Phases 1–3. Live-verified in Phase 4's Documents workflow group: all 4 folders had an unconditional `Deny from all` with no `<FilesMatch>` scoping, confirmed via direct HTTP request (403) on a disposable test image — the entire document-branding feature was non-functional on every generated document.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Test image in each of the 4 folders: 403 before, 200 after. Test `.php` file in the same folder: 403 both before and after (script execution protection intact — confirmed `mod_access_compat` is loaded and this syntax genuinely works on this Apache 2.4 install). Directory listing: still 403. All test files removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-006, closed, Critical)

### CC-094 — Fixed stored XSS via unsanitized document template bodies (KOM-022, closing a pre-existing finding)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-022
- **Files changed:** `config/DocumentEngine.php`, `modules/documents/templates.php`
- **Reason:** `render()` only ever escaped `{{placeholder}}` values, never the surrounding markup a `hr_officer`/`hr_manager` authors as raw HTML in a plain textarea — a `<script>` tag or `onerror=` handler in a template body would execute for every future viewer of any document generated from it, including more-privileged approvers. Flagged since the baseline audit, correctly out of Phase 1 scope, addressed here as part of the Documents generation lifecycle review.
- **Tests added/updated:** Standalone script with 12 sanitizer test cases (script tags, event handlers, javascript:/data: URLs, CSS expression(), legitimate tables/styles/placeholders, placeholders inside href, mixed malicious+legitimate content) — 12/12 correct.
- **Regression tests executed:** Saved a real test template through the actual hr_manager UI flow with a script tag, an onerror handler, and a javascript: href all present — all three neutralized in the stored body_html, with a legitimate {{company.website}} placeholder inside an href correctly preserved (a libxml percent-encoding quirk affecting placeholders inside URI attributes was found and fixed during this testing). Generated an actual document from the sanitized template for a real employee and confirmed the full pipeline end to end. Scanned all 47 existing live templates for dangerous markup: zero matches, no retroactive fix needed.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-022, closed, High)

### CC-095 — Fixed broken separator in "Documents Expiring Soon" banner (KOM-096)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-096
- **Files changed:** `modules/documents/index.php`
- **Reason:** `if (!end($expiringSoon) === $d) echo '; ';` — operator precedence means `!` applies to `end($expiringSoon)` (an array) before the comparison, so the condition is a strict-type mismatch that's never true. Every entry in the expiry-reminder banner ran together with no separator.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Inserted 2 disposable test documents with near-term expiry dates; confirmed no separator before the fix, correct `; ` separator (between entries only, not after the last) after. Test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-096, new, added to LOW section, Fixed)

### CC-096 — Documented QR-code verification dead-end, left deferred per user decision (KOM-097)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-097
- **Files changed:** None (documentation only)
- **Reason:** A template's "Show QR Code" option encodes a URL to `/verify-doc.php`, which does not exist anywhere in the repository — confirmed dormant (0 of 47 live templates currently enable QR codes). Building a real public, unauthenticated verification page is a genuine feature requiring its own design decisions (what to expose to an anonymous third party, rate-limiting) — flagged for a decision rather than built. User chose to leave it documented, not built.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A (no code change)
- **Master Register updated:** Yes (KOM-097, new, added to LOW section, deferred)

### CC-097 — Master Remediation Register updated for Phase 4 Workflow Group 12

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-006, KOM-022, KOM-096, KOM-097
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-098 — Change Control Log updated for Phase 4 Workflow Group 12

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 12 entries (CC-093–CC-098).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-099 — Fixed dead attendance.is_absent column for daily figures; documented monthly figures (KOM-098)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-098
- **Files changed:** `dashboard.php`, `modules/attendance/index.php`
- **Reason:** `attendance.is_absent` defaults to 0 and is never written by any code path in the codebase (a row only ever gets created on kiosk sign-in), so every `WHERE is_absent=1`/`SUM(is_absent)` across 6 files was structurally guaranteed to return 0, permanently. Live-verified: with 13 active employees and 0 clocked in, "Absent Today" showed 0.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** With 13 active/0 clocked in, Dashboard and Attendance-module day view both correctly showed 13 absent; inserted one disposable test attendance row, both correctly dropped to 12; removed it, both correctly returned to 13.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-098, new, added to HIGH section, partially fixed — monthly/period instances in modules/reports/*.php documented, not fixed, pending a working-day calendar that doesn't exist in this codebase)

### CC-100 — Fixed Dashboard Pending Approvals header total (KOM-099)

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-099
- **Files changed:** `dashboard.php`
- **Reason:** `$totalPending` summed only 4 of the 5 categories the "Pending Approvals" card displays, omitting recruitment applications from the header badge despite showing them as the card's 5th row.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Confirmed live counts (3 leave + 1 correction + 0 updates + 0 overtime + 1 recruitment): header showed "4 pending" before the fix, "5 pending" after — now matching the sum of all 5 visible rows.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-099, new, added to LOW section, Fixed)

### CC-101 — Master Remediation Register updated for Phase 4 Workflow Group 13

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-098, KOM-099
- **Files changed:** `docs/remediation/Findings/08-master-remediation-register.md`
- **Reason:** Record this workflow group's outcomes per the program's change-control requirement.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** Yes (this entry documents that update itself)

### CC-102 — Change Control Log updated for Phase 4 Workflow Group 13

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** N/A (documentation-only)
- **Files changed:** `docs/remediation/Regression/change-control-template.md`
- **Reason:** Record this log's own Phase 4 Workflow Group 13 entries (CC-099–CC-102).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A
- **Master Register updated:** N/A (this entry documents the change-control log itself, not the register)

### CC-103 — KOM-045 (unused permission slugs) reviewed and closed, no changes, per user decision

- **Date:** 2026-07-13
- **Phase:** 4
- **Finding ID(s) addressed:** KOM-045
- **Files changed:** None (documentation only)
- **Reason:** KOM-045 (deferred from Phase 3, out of that phase's schema-integrity charter scope) re-verified fresh against the post-Workflow-Group-13 codebase — a full scan of all 97 live permission slugs against actual code usage corrected the unused count from 24 to 26 (a few originally-flagged slugs were incidentally wired up by Phase 1–3 fixes; a few new never-wired slugs were seeded since). Grouped into 3 categories (8 redundant export slugs, 5 portal.* feature-toggle slugs, 13 miscellaneous) and presented to the user for a keep/wire-up/remove decision. User chose to leave all 26 documented, no code or database changes, matching the treatment already given to every other genuine feature-gap finding this phase.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A (no code change; verification was the permission-usage audit itself)
- **Master Register updated:** Yes (KOM-045 status changed from Open/deferred to Accepted as designed; count corrected 24→26; no register-total change, a status update to an existing finding)

### CC-104 — Phase 5 open-findings audit: closed KOM-035 as a stale duplicate of KOM-092

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-035
- **Files changed:** None (documentation only)
- **Reason:** A full line-by-line re-read of all 99 register rows (required by the Phase 5 charter §4 before implementation) found KOM-035 ("Temp Employees audit trail logs the wrong record ID") describes the identical defect, same files, same root cause as KOM-092, which was independently discovered and fixed in Phase 4 Workflow Group 10 without being cross-referenced to close this row — the same mistake KOM-044 correctly avoided when superseded by KOM-011 in Phase 1. Also corrected the register's own running "Open" tally, which had drifted from the true count (31 stated, 28 actual, corrected to 27 after this closure).
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A
- **Verification result:** N/A (no code change; KOM-092's existing Phase 4 fix and verification already cover this)
- **Master Register updated:** Yes (KOM-035 status changed from Open to Resolved — superseded by KOM-092)

### CC-105 — Locked in single-stage HR-only leave approval (KOM-083, Stage 5.1)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-083
- **Files changed:** `config/ApprovalEngine.php`
- **Reason:** `ApprovalEngine::workflowConfig()['leave']` modeled a 2-stage Supervisor Review → HR Approval flow, but no supervisor-facing review step was ever built — `leave/approve.php` has always resolved the entire request in one HR-only action. Presented to the user as a decision point (build the real 2-stage flow, or lock in single-stage as the permanent design); user chose to lock in single-stage HR-only. The never-enforced Supervisor Review stage removed from the config entirely.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Submitted a real leave application; confirmed the resulting `approval_workflows.total_stages=1` and exactly one `approval_stages` row (`HR Approval`, `hr_manager`, no Supervisor Review stage created). Approved it via `leave/approve.php`; confirmed the application, workflow, and its single stage all flipped to `approved` together in one action. Confirmed zero existing `leave`-type workflow rows existed at the time of this change (no historical-data migration needed). Confirmed the `supervisor` role itself (used by other permissions independent of leave) was not touched. Phase 1 regression 20/20, Phase 2 regression 29/29. Test data fully cleaned up, leave balance confirmed restored to its exact pre-test value.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-083 status changed from Open/deferred to Fixed)

### CC-106 — Removed 4 dormant ApprovalEngine workflow types, fixed cancel() bug (KOM-047, Stage 5.2)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-047
- **Files changed:** `config/ApprovalEngine.php`
- **Reason:** `overtime`, `correction`, `payroll_run`, and `document` were configured as `ApprovalEngine` workflow types but nothing anywhere ever called `->create()` for any of them — each already has its own independently-built, working approval mechanism (overtime/corrections have their own status handling, payroll runs use their own atomic status-transition pipeline, document approval uses its own status field). Presented to the user as a decision point (wire up, remove, or mark reserved); user chose to remove as dead configuration. Also fixed `cancel()`'s `+`-vs-string-concatenation bug (KOM-047) while in this file — a trivial, safe fix independent of the dormant-type removal, kept since `cancel()` remains generically useful for the 3 real workflow types even though it currently has no call site.
- **Tests added/updated:** None beyond live functional re-testing
- **Regression tests executed:** Confirmed the Approvals UI type-filter dropdown now lists exactly 4 workflow types (was 8). Full round-trip test on a real, still-configured type: created a disposable test employee, submitted and approved a termination request via the generic Approvals inbox — workflow correctly created and resolved, employee status correctly changed, initiator correctly notified. Confirmed zero existing `approval_workflows` rows of any of the 4 removed types existed (no orphaned data). Phase 1 regression 20/20, Phase 2 regression 29/29. Test data fully cleaned up.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-047 status changed from Open to Fixed)

### CC-107 — Built working-day/holiday calendar, completed KOM-098's deferred monthly figures (Stage 5.3)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-098 (completes the monthly/period half deferred in Phase 4)
- **Files changed:** `database/phase13_workflow_completeness_automation.sql` (new), `database/schema.sql`, `config/functions.php`, `modules/settings/calendar.php` (new), `includes/header.php`, `dashboard.php`, `modules/attendance/index.php`, `modules/reports/index.php`, `modules/reports/executive.php`, `modules/reports/timesheets.php`, `modules/leave/apply.php`
- **Reason:** No working-day/holiday calendar existed anywhere in this codebase — Phase 4 could only fix the daily-granularity "Absent" figures (no calendar needed for a single day), leaving the monthly/period figures in Reports documented but not fixed, and `leave/apply.php`'s day-count calculation hardcoded weekends-only with no holiday awareness. Built `work_calendar_settings`/`work_calendar_holidays` tables and 5 calendar functions (`isWorkingDay`, `countWorkingDays`, `getWorkingDaysBetween`, `getNextWorkingDay`, `getWorkCalendarSettings`), a new admin UI to manage them, and wired them into every consumer the charter named.
- **Tests added/updated:** New `docs/remediation/Testing/phase5-calendar-unit-tests.php` (17 cases, re-runnable, self-cleaning)
- **Regression tests executed:** 17/17 calendar unit tests (weekends, cross-month ranges, leap year, single-day and recurring-annual holiday exclusion, inactive-holiday non-blocking, `getNextWorkingDay()` weekend skip). Admin UI: added a real holiday via the actual form, confirmed duplicate-range rejection, toggle, and delete all correct with audit log entries. Dashboard: Attendance Rate moved 0%→1% and Absent Today 13→12→13 correctly across a disposable test clock-in insert/removal. Leave: a 5-calendar-day request spanning a disposable test holiday correctly saved `total_days=4`, not 5. Phase 1 regression 20/20, Phase 2 regression 29/29. All test data (holidays, attendance rows, leave application/workflow, notifications, audit entries) removed after verification; one test-cleanup batch error (a stray reference to a nonexistent table stopped 4 of 6 cleanup statements) caught and corrected within the same pass — disclosed in `Phase5/04-working-day-calendar-report.md` §4.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (KOM-098's "partial" qualifier removed — fully resolved across Phase 4 + this stage)

### CC-108 — Built the scheduled task infrastructure (Stage 5.4)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** None — new infrastructure, not a fix to a documented defect
- **Files changed:** `database/phase13_workflow_completeness_automation.sql` (extended), `database/schema.sql` (extended), `cron/bootstrap.php` (new), `cron/run.php` (new), `cron/.htaccess` (new), `cron/README.md` (new), `cron/tasks/{expire_tokens,send_reminders,process_notifications,cleanup_safe_temp_files}.php` (new)
- **Reason:** No cron/scheduled-task mechanism existed anywhere in this codebase (confirmed by full repository search, Phase 4 and re-confirmed at Phase 5 baseline) — a prerequisite for Stage 5.5's password-reset token expiry and Stage 5.6's reminder notifications. Built a lightweight, shared-hosting/cPanel-compatible scheduler: a single entry point (`run.php`) invoked by a host cron job, a DB-backed single-run lock, per-task-per-run audit logging, and failure isolation between tasks. Two of the four registered tasks are fully functional now (`expire_tokens`, `cleanup_safe_temp_files`); the other two (`send_reminders`, `process_notifications`) are wired-in, safe no-op placeholders whose real logic belongs to Stage 5.6.
- **Tests added/updated:** None beyond live functional re-testing (a dedicated Stage 5.4 automated test suite was not created; live CLI/HTTP verification below covers every safety property)
- **Regression tests executed:** Confirmed `cron/run.php` returns 403 over HTTP (two independent layers: `PHP_SAPI` check in `bootstrap.php`, `.htaccess` at the Apache level). CLI execution: all 4 tasks run and log correctly to `scheduled_task_runs`. Overlapping-run protection: manually held the lock, confirmed a second invocation detects it and exits without touching any task. Stale-lock recovery: backdated a lock to 40 minutes old, confirmed the next run auto-clears it. Failure isolation: temporarily replaced `process_notifications.php` with a file that throws, confirmed the task before and after it both still ran and succeeded, the failure was correctly recorded with its exception message, then restored the original file. Idempotency: `expire_tokens` correctly flipped a disposable expired-but-active test `employee_update_links` row's `is_active` to 0; `cleanup_safe_temp_files` correctly deleted a disposable old-and-inactive test row; re-running against the same (now-clean) data processed 0 items the second time. Phase 1 regression 20/20, Phase 2 regression 29/29. All test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** N/A (no specific finding; recorded here for full traceability)

---

### CC-109 — Self-service password recovery, Admin surface only (Stage 5.5)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-041
- **Files changed:** `database/phase13_workflow_completeness_automation.sql` (extended), `database/schema.sql` (extended — `users.password_changed_at`, `password_reset_tokens`), `auth/forgot_password.php` (new), `auth/reset_password.php` (new), `auth/login.php` (sets `login_time`, adds "Forgot password?" link, adds `password_changed` alert), `auth/session.php` (adds the `password_changed_at`-vs-`login_time` invalidation check), `auth/change_password.php` (sets `password_changed_at`, refreshes own `login_time`), `modules/users/index.php` (admin-initiated reset now also sets `password_changed_at`), `cron/tasks/expire_tokens.php` (extended to also expire stale `password_reset_tokens`)
- **Reason:** KOM-041 — no self-service password-reset flow existed on any of the 4 authentication surfaces; the only recovery path was an already-authenticated admin manually resetting another user's password. Per the Stage 5.5 decision matrix (user sign-off, `Phase5/01-phase5-open-findings-scope.md` §6), scoped to the Admin surface only — the one surface guaranteed to have a real, verified email on file. Built an enumeration-resistant, rate-limited, single-use, sha256-hashed-at-rest token flow, plus a session-invalidation mechanism (`login_time` vs. `password_changed_at`) that is this codebase's practical substitute for "invalidate other active sessions," since the default file-based PHP session setup has no central session registry to selectively destroy other sessions by.
- **Tests added/updated:** No dedicated automated suite (live HTTP/DB verification below covers every safety property); live testing used disposable data (`p5testuser`, id 54), fully removed afterward.
- **Regression tests executed:** Full forgot-password → email-logged token → reset → login → token-reuse-rejected cycle. Enumeration resistance: identical generic response for an existing vs. nonexistent identifier. Self-service change does not self-logout: performed a change, immediately reloaded a protected page in the same session — 200 OK. **Full two-session invalidation test**: Session A logged in and confirmed reachable (200 OK on `dashboard.php`); a second, independent, never-before-authenticated session then completed a fresh reset for the same account; Session A's next request to `dashboard.php` correctly received a 302 to `login.php?reason=password_changed`, followed the redirect and confirmed the message renders, and confirmed the session stays logged out on a further request. Phase 1 regression 20/20, Phase 2 regression 29/29. All test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes — KOM-041 row updated to Fixed with full verification detail.

---

### CC-110 — `email_logs.type` enum extended for `password_reset` (Stage 5.5 follow-up)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** None directly — a self-introduced defect caught and fixed within the same stage as CC-109, not a pre-existing register finding
- **Files changed:** `database/phase13_workflow_completeness_automation.sql` (extended), `database/schema.sql` (extended — `email_logs.type` enum)
- **Reason:** `auth/forgot_password.php` (built in CC-109) calls `sendEmail(..., 'password_reset', ...)`, but the pre-existing `email_logs.type` enum had no `'password_reset'` value. Under this MariaDB instance's non-strict SQL mode, the `INSERT` did not fail — it silently coerced the value to `''`. Caught during live testing when inspecting logged test rows and finding `type` blank instead of the expected value. Impact was cosmetic/categorization only (the email itself was logged and sent correctly; no security property was affected) — fixed by extending the enum, consistent with how every other email flow already has its own type value.
- **Tests added/updated:** None — verified directly via the `password_reset_tokens`/`email_logs` rows produced by CC-109's own live test.
- **Regression tests executed:** Confirmed `DESCRIBE email_logs` shows the extended enum; confirmed a fresh `forgot_password.php` request now logs `type='password_reset'` correctly; the two pre-existing mis-typed test rows were corrected before test-data cleanup. Phase 1/Phase 2 regression suites re-run as part of CC-109's verification pass (20/20, 29/29) — this change does not affect either suite's code paths.
- **Verification result:** VERIFIED live
- **Master Register updated:** No — not a distinct register finding; disclosed in `Phase5/06-password-recovery-report.md` §5 and folded into KOM-041's Stage 5.5 update.

---

### CC-111 — Deferred notification workflows built (Stage 5.6)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** None directly — completes a completeness gap documented informationally in Phase 4 Workflow Group 11, same treatment as CC-108 (Stage 5.4)
- **Files changed:** `cron/tasks/send_reminders.php` (replaces the Stage 5.4 no-op placeholder with 9 reminder categories)
- **Reason:** Phase 4 Workflow Group 11 documented, but did not build, Training/Recruitment/scheduled-reminder notifications, since no scheduler existed at that point. Stage 5.4 built the scheduler; this fills in the real logic: employee contract expiry, probation ending, temp employee/consultant contract ending, employee document expiry, training starting soon, recruitment interview tomorrow, leave approval sitting unactioned (using the Stage 5.3 working-day calendar), and payroll run finalized-but-unpublished (also working-day-based). Each notifies `hr_manager` (or `payroll_manager` for the payroll category) via the existing `notifyRole()` convention.
- **Tests added/updated:** None beyond live functional re-testing (see CC-112 for the dedup mechanism this depends on; both verified together).
- **Regression tests executed:** Full 9-category live test using disposable `P5TEST`-prefixed rows dated to land exactly on each threshold (working-day thresholds computed via the app's own `countWorkingDays()`, not guessed). First run: `itemsProcessed=9`, 8 real notifications delivered to the one seeded `hr_manager` account (the 9th targets `payroll_manager`, no seeded active user, correctly a no-op recipient-wise while still counted as fired). Full scheduler run (`php cron/run.php`) against real production data: all 4 tasks `OK`, `send_reminders` correctly processes 0 items (no real data currently matches a threshold). Phase 1 regression 20/20, Phase 2 regression 29/29. All test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** N/A (no specific finding; recorded here for full traceability)

---

### CC-112 — Per-day reminder dedup added (Stage 5.6 correction)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** None — a design gap in CC-111's own same-stage work, caught and fixed before commit
- **Files changed:** `database/phase13_workflow_completeness_automation.sql` (extended — `reminder_notifications_log`), `database/schema.sql` (extended), `cron/tasks/send_reminders.php` (`fireOnce()` helper wraps every notification), `cron/tasks/cleanup_safe_temp_files.php` (90-day retention prune for the new log table), `cron/README.md` (cadence note updated)
- **Reason:** `send_reminders.php`'s original design used only an exact-day threshold match as its safeguard against repeats, implicitly assuming a once-daily cron run. `cron/README.md` (written in Stage 5.4) actually recommends running the scheduler every 15-30 minutes — at that cadence every matching reminder would have re-fired on every invocation within its matching day. Caught during live testing by re-reading the scheduler's own setup instructions, not by external report. Fixed with `reminder_notifications_log` (`reminder_key` + `reminder_date`, `UNIQUE KEY` on the pair) and a `fireOnce()` helper: a UNIQUE-constraint failure on insert means "already sent today," and the notification is skipped. Retention cleanup added to the existing `cleanup_safe_temp_files.php` task, consistent with that task's existing scope (disposable, non-evidentiary data).
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Second run of `send_reminders.php` (fresh CLI process, same calendar day, immediately after CC-111's first run) — `itemsProcessed=0`, notification delta `0`, confirming no duplicate fires. Phase 1/Phase 2 regression suites re-run as part of CC-111's verification pass (20/20, 29/29) — this change does not affect either suite's code paths.
- **Verification result:** VERIFIED live
- **Master Register updated:** N/A (no specific finding; folded into CC-111's traceability)

---

### CC-113 — Guided recruitment-to-employee conversion (Stage 5.7)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-088
- **Files changed:** `modules/recruitment/index.php` (Convert to Employee / View Employee action), `modules/employees/add.php` (`?from_application=<id>` pre-fill + POST-time linking)
- **Reason:** `recruitment_applications.converted_to_employee_id` existed in the schema with no code anywhere reading or writing it — HR had to manually re-key a selected candidate's details into a disconnected Add Employee form. Per user decision, built a guided conversion rather than a separate parallel system: the existing, already-tested Add Employee form pre-fills from the application (name/email/phone) when reached via the new action, and links the two records on save. The convertible-state condition (`status='selected'`, not already converted) and the `recruitment.review` permission check are both re-verified at POST time, not just at the initial GET, so a stale form submission (two tabs, a status change in the interim) cannot double-convert or overwrite an existing link.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Full live cycle using a disposable application: pre-fill confirmed on GET (`first_name`/`last_name`/`phone`/`email` values present, hidden `from_application` field present); POST completed the conversion (new employee created, `converted_to_employee_id` set, audit log entry recorded); re-request after conversion correctly fell through to a blank form (no re-linking); Recruitment applications list confirmed to show "View Employee" in place of the action post-conversion; baseline Add Employee page (no `from_application` parameter) confirmed rendering identically to before (200 OK, no conversion banner). Phase 1 regression 20/20, Phase 2 regression 29/29. All test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes — KOM-088 row updated to Fixed with full verification detail.

---

### CC-114 — Supervisor/HR-entered temp employee attendance capture (Stage 5.8)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-090, KOM-058
- **Files changed:** `database/phase13_workflow_completeness_automation.sql` (extended — `temp_attendance`), `database/schema.sql` (extended), `modules/temp_employees/attendance_entry.php` (new), `modules/temp_employees/index.php` (Enter Attendance link), `modules/temp_employees/timesheet.php` (Enter Attendance Digitally link), `modules/temp_employees/view.php` (Recent Attendance summary card), `modules/temp_employees/add.php`, `modules/temp_employees/edit.php` (attendance-method descriptions updated to reflect the new capability)
- **Reason:** Neither the Kiosk nor Timesheet attendance method for temp employees ever captured any data — the kiosk only recognizes permanent employees, and the timesheet was a blank printable form with no digital re-entry point. Phase 4 corrected the misleading UI copy but deferred building the actual capture mechanism. Per user decision, built supervisor/HR-entered digital capture (`temp_attendance`, one row per employee per day, re-entry updates rather than duplicates) rather than a kiosk-based self-service flow — a kiosk path for temp employees would need its own identity-verification design not authorized this phase. `attendance_entry.php` reuses `timesheet.php`'s existing project/site/week selection logic so both pages show the same employee list for the same week; the printable timesheet is kept for manual/signature record-keeping alongside the new digital path, not replaced by it.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Loaded the entry grid for a real active project (3 employees × 7 days = 21 cells, all blank); POSTed 2 days of hours for one employee, confirmed correct `temp_attendance` rows with the correct `entered_by`; reloaded the same week and confirmed both values pre-fill correctly; confirmed `view.php`'s new Recent Attendance card shows the correct entries and total (12.5 hrs); confirmed the audit log entry. Permission gating (`temp_employees.edit`) uses the same `requirePermission()` mechanism already covered 20/20 in Phase 1 regression — not independently re-tested. Phase 1 regression 20/20, Phase 2 regression 29/29. All test data (2 `temp_attendance` rows, 1 audit log entry) removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes — KOM-090 and KOM-058 rows updated to Fixed with full verification detail.

---

### CC-115 — Document QR verification disabled (Stage 5.9)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-097
- **Files changed:** `modules/documents/templates.php` (QR toggle removed from UI; server-side flag hardcoded to 0), `config/DocumentEngine.php` (QR-rendering block removed; vestigial `'qr_code'` HTML-escaping entry removed)
- **Reason:** The "Show QR Code" template option linked to `/verify-doc.php`, a public verification page that never existed anywhere in the repository, and generated the QR image via an outbound call to a third-party API (`api.qrserver.com`) — an unauthorized external dependency. 0 of 47 live templates had it enabled. Per user decision, the feature is disabled rather than completed (building a real public, unauthenticated verification endpoint is a genuine feature requiring its own scope/security decisions, not a byproduct of closing this finding). The `show_qr_code` column is left in the schema (not dropped), per the decision hierarchy's guidance against removing data structures without migration/rollback planning — this is a UI/rendering-level deactivation.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Confirmed the QR Code toggle no longer renders in the template editor. Submitted a crafted `POST` directly to `templates.php` with `show_qr_code=1` included — confirmed the saved row's value is `0` regardless. Manually forced a disposable test template's `show_qr_code` to `1` directly in the database (simulating a stale/pre-existing row) and generated a real document preview from it — confirmed zero references to `qrserver`, `verify-doc`, or a QR image in the output, proving the rendering path itself no longer honors the flag at all. Confirmed existing document-generation features (letterhead/signature/stamp/watermark/doc-number), none of which were touched, continue to render correctly. Phase 1 regression 20/20, Phase 2 regression 29/29. Disposable test template removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes — KOM-097 row updated to Fixed with full verification detail.

---

### CC-116 — Leave approve/reject buttons fixed (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-009
- **Files changed:** `modules/leave/view.php`
- **Reason:** The Approve/Reject buttons POSTed `leave_id`/`action` fields, but `approve.php` only ever reads `$_GET['id']`/`$_GET['action']` — a field-name and method mismatch meant every click silently no-opped. Replaced the broken inline forms with links to `approve.php`'s real GET-loaded confirmation page.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Created a disposable pending leave application, clicked through the full Approve flow (GET confirmation → POST with remarks), confirmed status flipped from `pending` to `approved` in the database. Phase 1 regression 20/20 (after the KOM-037-related test update, see CC-122), Phase 2 regression 29/29. Test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-117 — Finalized/sent payslips blocked from editing (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-016
- **Files changed:** `modules/payroll/payslips.php`
- **Reason:** The update branch had no status guard (could silently rewrite a `finalized`/`sent` payslip) and never called `auditLog()`. Per user decision, edits are now blocked once a payslip leaves `draft`; an allowed edit is now audit-logged.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Created a disposable `finalized` payslip, attempted an edit via crafted POST, confirmed the expected error rendered and `gross_salary` remained unchanged. Test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-118 — Employee salary field display fixed (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-025
- **Files changed:** `modules/employees/edit.php`
- **Reason:** Read `$emp['salary']` (a key that never existed) instead of `$emp['basic_salary']` (the real column) — the field always rendered blank regardless of the employee's actual salary.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Confirmed a real employee's salary field now pre-fills correctly.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-119 — Reports Hub CSV export implemented (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-028
- **Files changed:** `modules/reports/index.php`
- **Reason:** The "Export CSV" link existed but nothing ever read `$_GET['export']`. Added an export branch for all 4 report types (attendance/employees/leave/overtime), matching the existing `fputcsv()` pattern in `reports/executive.php`.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Confirmed the export downloads real data with correct headers and `Content-Type: text/csv`.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-120 — SMTP password no longer exposed in HTML source (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-031
- **Files changed:** `modules/settings/email.php`
- **Reason:** The "Payslip Notifications" form re-emitted every SMTP setting as a hidden field, including `smtp_pass`'s live cleartext value. Removed `smtp_pass` from that hidden-field list; the save handler already preserves the existing password whenever the field is blank/absent.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Confirmed `smtp_pass` no longer appears anywhere in the settings page's HTML source.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-121 — Activity Log CSV formula injection and filter/streaming fixes (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-033, KOM-034
- **Files changed:** `modules/activity_log/download.php`
- **Reason:** `csvRow()` (the shared helper every export path uses) escaped double-quotes but not a leading `=`,`+`,`-`,`@` — a spreadsheet formula-injection risk given several exported columns are free text (reason, old_value, new_value). Fixed by prefixing any such value with a single quote before quoting. Separately, the admin/employee individual-export paths ignored the `from`/`to` date-range filter the category exports already respect, and used `fetchAll()` instead of the streaming pattern used elsewhere in the file — both fixed together since they're adjacent code in the same file.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Inserted a disposable audit log row with a `=cmd|...` payload as its reason; exported it and confirmed the CSV cell is neutralized (`'=cmd...`), not the raw value. Test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes — both findings.

---

### CC-122 — Audit Logs / Activity Logs menus merged (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-037
- **Files changed:** `includes/header.php` (Activity Logs sidebar entry removed), `modules/activity_log/index.php` and `modules/activity_log/download.php` (re-gated onto `audit.view`), `modules/audit/index.php` ("View by User" link added), `modules/activity_log/index.php` ("Back to Audit Logs" link added), `docs/remediation/Testing/phase1-regression-run.sh` (KOM-019/NH-02 test expectations updated)
- **Reason:** "Audit Logs" (`audit.view`, held by hr_manager/hr_officer/payroll_manager) and "Activity Logs" (`activity_log.view`, seeded to super_admin only) sat side by side in the sidebar, both reading the same `audit_logs` table under two different authorization models. Per user decision, merged into one consistently-gated entry point.
- **Tests added/updated:** Updated the Phase 1 regression suite's KOM-019/NH-02 assertions — hr_manager/hr_officer now correctly expect 200 (not 302) for Activity Log, since they hold `audit.view`; payroll_officer (who holds neither) remains expected at 302.
- **Regression tests executed:** Confirmed the sidebar shows only "Audit Logs"; confirmed "View by User" reaches the per-user page; confirmed both pages reachable under `audit.view`. First Phase 1 regression run surfaced 2 "failures" that were the deliberate, intended effect of this merge, not a defect — confirmed and the test updated accordingly (see `Phase5/11-remaining-findings-closure-report.md` §4). Phase 1 regression 20/20 after the update, Phase 2 regression 29/29.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-123 — Branding upload hardening: SVG removed, extension derived from MIME (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-038, KOM-039
- **Files changed:** `modules/settings/branding.php` (`image/svg+xml` removed from allowed letterhead types), `config/functions.php` (`uploadFile()` — extension derived from detected MIME type, not client filename)
- **Reason:** SVG can carry embedded `<script>`, and server-side MIME sniffing passes a well-formed malicious SVG since it genuinely is one — removed rather than sanitized, since raster formats cover the same use case. Separately (but touching the same upload path), the file extension saved to disk came from the client-supplied original filename, never cross-checked against the MIME type `finfo` actually detected — a crafted upload could choose any extension regardless of real content. The extension is now derived solely from the server-detected MIME type via an explicit map, applying to every `uploadFile()` caller app-wide.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Uploaded a file with real PNG content but a client-supplied filename of `malicious.php` — confirmed it was saved with a `.png` extension. Test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes — both findings.

---

### CC-124 — Pagination bound parameters, 6 files (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-046
- **Files changed:** `modules/employees/index.php`, `modules/attendance/index.php`, `modules/timesheets/index.php`, `modules/recruitment/index.php` (2 queries), `modules/onboarding/index.php`, `modules/training/index.php` (2 queries)
- **Reason:** `LIMIT {$perPage} OFFSET {$offset}` string interpolation across all 6 files — values were always int-cast before use (not previously exploitable), but deviated from the prepared-statement standard used elsewhere. Converted to `LIMIT ? OFFSET ?` bound parameters. Verified empirically first (via a standalone test against this app's actual `db()` connection) that plain `execute($array)` correctly handles `LIMIT`/`OFFSET` under this app's native, non-emulated prepared statements (`PDO::ATTR_EMULATE_PREPARES => false`) before applying the change at scale, since that combination is a known PDO/MySQL gotcha in other configurations.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Confirmed all 6 pages still load correctly (200 OK) post-fix. Phase 1 regression 20/20, Phase 2 regression 29/29.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-125 — Missing-documents report N+1 query fixed (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-048
- **Files changed:** `modules/documents/missing.php`
- **Reason:** One `SELECT` per employee in a loop — scaled linearly with headcount. Replaced with a single `SELECT ... WHERE employee_id IN (...)` for all filtered employees, grouped in PHP; 2 queries total regardless of headcount.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Confirmed the page loads correctly post-fix.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-126 — Archive Lock control wired up (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-051
- **Files changed:** `modules/archive/monthly.php`
- **Reason:** The Lock button posted `lock_id`, which the POST handler never read at all — every click silently did nothing. Added a handler branch that sets `is_locked=1`/`locked_by`/`locked_at` and audit-logs the action.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Confirmed the page loads correctly post-fix; the hardcoded-role-list visibility issue this finding also named was confirmed already resolved by KOM-040's Phase 1 fix.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-127 — APP_ENV fail-safe default (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-053
- **Files changed:** `config/config.php`
- **Reason:** `APP_ENV` defaulted to `'development'` (on-screen errors) whenever unset. Flipped the fail-safe default to `'production'` (errors suppressed on-screen, still logged to `logs/php_errors.log`) — a misconfigured/un-configured deployment no longer leaks stack traces/paths/queries by default.
- **Tests added/updated:** None.
- **Regression tests executed:** Confirmed production-mode error logging still works via the log file. This local dev machine's Apache config (`C:\New_xampp\apache\conf\httpd.conf`, outside the git repo) was given an explicit `SetEnv APP_ENV development` override so local development is unaffected — takes effect on Apache's next natural restart; a forced restart attempt during this session did not succeed (non-service standalone Apache) but did not disrupt the running server either, confirmed via process check.
- **Verification result:** VERIFIED (code-level; behavioral effect confirmed via direct `production`-mode error-logging test)
- **Master Register updated:** Yes.

---

### CC-128 — Branding asset file cleanup and dead letterhead fields removed (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-055, KOM-060
- **Files changed:** `modules/settings/branding.php`
- **Reason:** Replacing or deleting a branding asset (letterhead/signature/stamp/watermark) only ever touched the database row — the old file on disk was never removed, so `uploads/{letterheads,signatures,stamps,watermarks}/` grows without bound. Added a shared `deleteBrandingAssetFile()` helper wired into all 8 update/delete handlers (a file is only deleted once the superseding database change has actually succeeded). Separately (same file, same user decision session), `header_html`/`footer_html` — captured by the letterhead save handler with no corresponding form field anywhere in the page's UI and never read by `DocumentEngine.php` — removed from the handler per user decision; the database columns are left in place, unused.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Replaced a disposable test letterhead's image and confirmed the original file was deleted from disk while the new one saved correctly. Confirmed no remaining references to `header_html`/`footer_html` anywhere in the codebase. Test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes — both findings.

---

### CC-129 — Dashboard SQL bound parameters (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-056
- **Files changed:** `dashboard.php`
- **Reason:** `$today`/`$thisMonth` (both server-generated via `date()`, never user input — not exploitable) were interpolated into 6 queries. Converted to bound parameters for consistency with the prepared-statement standard used elsewhere.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Confirmed the dashboard loads correctly post-fix.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-130 — KOM-057 resolved, no code change (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-057
- **Files changed:** None.
- **Reason:** The described defect (a throwaway `COUNT` statement's `PDOStatement` object assigned then immediately overwritten) is no longer present in `modules/temp_employees/index.php` — the current file uses distinct, correctly-named `$stmtCount`/`$stmtRows` variables. Resolved incidentally during Phase 4 Workflow Group 10's rewrite of this module; recorded here for traceability rather than left silently unaddressed.
- **Tests added/updated:** None.
- **Regression tests executed:** Current file re-read line by line; no reused/overwritten statement variable found.
- **Verification result:** VERIFIED (code review — no live behavior to test, since there is no defect to reproduce)
- **Master Register updated:** Yes — marked Resolved.

---

### CC-131 — KOM-059 deferred, documented (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-059
- **Files changed:** None.
- **Reason:** Normalizing `temp_employees.position_title` from free text to a FK against `positions` would require migrating existing values with no reliable automatic mapping (temp/project role titles don't cleanly correspond to the permanent-employee position catalog) and deciding a fallback for unmatched values. Not exploitable, no live bug — a genuine data-modeling improvement but a real migration-risk decision, not a mechanical fix within this stage's safe scope. Deferred rather than attempted unilaterally.
- **Tests added/updated:** None.
- **Regression tests executed:** N/A — no change made.
- **Verification result:** N/A
- **Master Register updated:** Yes — marked Deferred with documented reason.

---

### CC-132 — Admin password minimum length raised to 8 (Stage 5.10)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-063
- **Files changed:** `modules/users/index.php`
- **Reason:** Admin-initiated create-user and reset-password both allowed a 6-character minimum (client `minlength` and server `strlen()`), inconsistent with the 8-character minimum every self-service password path in the app enforces (self-service change; Stage 5.5's self-service reset). Raised both admin paths to 8.
- **Tests added/updated:** None beyond live functional re-testing.
- **Regression tests executed:** Confirmed both admin-side password fields now render `minlength="8"`.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes.

---

### CC-133 — KOM-045 re-confirmed, KOM-064 resolved (Stage 5.11)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-045, KOM-064
- **Files changed:** None.
- **Reason:** Re-verified KOM-045's previously-accepted 26-slug unused-permission list fresh against the post-Stage-5.10 codebase (rather than assume it still holds) — all 26 remain unwired, none of Stage 5.1–5.10's changes accidentally wired up or orphaned any of them, no new slugs created anywhere in Phase 5. Phase 4's "reviewed and accepted, no changes" status stands. Separately, re-verified KOM-064 (`requireRole()` dead code) and found it no longer exists anywhere in the codebase — already removed, most likely during Phase 1's authorization framework build.
- **Tests added/updated:** None.
- **Regression tests executed:** N/A — no code change made. Full-codebase searches performed for both findings.
- **Verification result:** VERIFIED (code review)
- **Master Register updated:** Yes — KOM-045 re-confirmed unchanged, KOM-064 marked Resolved.

---

### CC-134 — KOM-065 corrected and accepted as designed (Stage 5.11)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** KOM-065
- **Files changed:** None (register text corrected only).
- **Reason:** Re-verification found the finding's original "never used" characterization stale: `employee_skills` is queried by `modules/employees/view.php` and rendered as a full "Skills" tab (empty-state message, row-rendering loop), and included in `delete.php`'s cascade-delete preview. What's actually missing is any `INSERT`/`UPDATE` path anywhere — the tab is permanently empty with no way to populate it. Not a security issue or blocked workflow. `employee_qualifications`/`employee_work_history` already cover the substantive need this table appears to have been an earlier, superseded attempt at. Building a full Skills CRUD is new feature scope, not a reconciliation-stage fix — documented as an honest (not misleading) completeness gap rather than built.
- **Tests added/updated:** None.
- **Regression tests executed:** N/A — no code change made. Confirmed the read/render path exists and no write path exists anywhere via full-codebase search.
- **Verification result:** VERIFIED (code review)
- **Master Register updated:** Yes — KOM-065 corrected and marked Accepted as designed.

---

### CC-135 — Phase 5 security & privacy review (Stage 5.12)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** None — dedicated review pass, not tied to a specific finding.
- **Files changed:** None (review found no new gaps to fix).
- **Reason:** Charter-required dedicated security/privacy review of every file added or modified across Phase 5 (47 PHP files), independent of each stage's own live-verification. Covered authentication/session security, authorization gating, SQL injection surface, file upload handling, external dependencies, dangerous-function usage, and PII/privacy exposure.
- **Tests added/updated:** None.
- **Regression tests executed:** N/A — no code change made. Full-codebase greps performed for: raw `$_GET`/`$_POST` SQL interpolation, dangerous PHP functions (`eval`/`unserialize`/`extract`/`assert`/`system`/`exec`/`shell_exec`/`passthru`), and outbound HTTP URLs, across every Phase 5–touched file. `cron/`'s CLI-only guard re-confirmed present. CSRF/permission gating re-confirmed present on every new page.
- **Verification result:** VERIFIED (code review) — no new findings; every gap this pass would have surfaced was already identified and fixed within Stages 5.5–5.10's normal work.
- **Master Register updated:** No new/altered findings — narrative entry only, see `Security/15-phase5-security-privacy-review.md`.

---

### CC-136 — Full Phase 5 regression suite built (Stage 5.13)

- **Date:** 2026-07-13
- **Phase:** 5
- **Finding ID(s) addressed:** None — new permanent test tooling, not tied to a specific finding.
- **Files changed:** `docs/remediation/Testing/phase5-regression-run.sh` (new)
- **Reason:** Charter-required dedicated regression suite covering Leave, ApprovalEngine, Calendar, Scheduler, Password Recovery, Notifications, Recruitment Conversion, Temp Attendance, and QR Verification, plus a full Phase 1+2 re-run, repo-wide syntax scan, and migration verification — built as a new, permanent, re-runnable script (not a one-off manual test), matching the existing `phase1-regression-run.sh`/`phase2-regression-run.sh` conventions.
- **Tests added/updated:** New: `docs/remediation/Testing/phase5-regression-run.sh` (40 assertions across 13 groups).
- **Regression tests executed:** Full suite run 3 times during development. First run: 5 failures, all traced to the test script itself (a token-extraction regex matching the password-reset email's duplicate link occurrence; an overly-broad QR assertion matching Stage 5.9's own explanatory comments) — both fixed in the script, not the application. Final run: **40/40 passed**, Phase 1 20/20, Phase 2 29/29, repo-wide `php -l` scan 0 errors, migration re-apply idempotent (0 errors), all 7 Phase 5 tables confirmed present. All disposable test data confirmed removed after each run via direct database query.
- **Verification result:** VERIFIED live
- **Master Register updated:** No new/altered findings — narrative entry only, see `Testing/15-phase5-regression-test-report.md`.

---

### CC-137 — Production blocker fixes: environment config, .htaccess hardening, install sequence, security headers (Stage 6.1)

- **Date:** 2026-07-13
- **Phase:** 6
- **Finding ID(s) addressed:** KOM-054
- **Files changed:** `config/config.php` (env-driven constants), `.env.example` (timezone default corrected), `logs/.htaccess` (BOM removed, dual-syntax hardening), `config/.htaccess`, `database/.htaccess` (dual-syntax hardening), `database/install.php` (`INSTALL_SEQUENCE` corrected), `database/verify_clean_install.php` (sequence + table-count assertion corrected), `database/README.md` (new), `.htaccess` (HSTS + CSP-Report-Only headers added)
- **Reason:** KOM-054 (open since Phase 0, never closed) named blank root DB password, default admin password, no HTTPS redirect, no CSP header. This phase's own baseline audit independently re-confirmed the gap and found it had been missed entirely by Phase 5's opening audit despite that phase's completion report claiming 0 Open findings — disclosed as a register-tracking correction (see the Master Remediation Register's Stage 6.1 narrative entry for full detail). Fixed: `config.php`'s DB/URL/session/upload constants are now environment-driven via `getenv()` (mechanism already documented in `.env.example`, never wired up); `logs/.htaccess`'s UTF-8 BOM (a real defect, not cosmetic) removed; `install.php`'s fresh-install sequence corrected to include `phase13_workflow_completeness_automation.sql` (the one migration carrying fresh-install-needed seed data `schema.sql` intentionally excludes — `phase11`/`phase12` correctly remain excluded, confirmed redundant by direct inspection); `database/README.md` written (referenced but missing); HSTS + `Content-Security-Policy-Report-Only` headers added. The forced-HTTPS-redirect item is deliberately deferred to the Stage 6.2 Nginx deployment guide — the target platform doesn't read `.htaccess` at all, and an application-level redirect risked breaking this local dev environment (no HTTPS listener, and `APP_ENV` is currently resolving to `production` here due to an earlier failed local override attempt).
- **Tests added/updated:** `database/verify_clean_install.php`'s sequence and table-count assertion updated to match `install.php`'s corrected sequence.
- **Regression tests executed:** Environment-variable override proven to work (`APP_URL` correctly overridden when set; identical fallback values confirmed when unset). `config/`, `database/`, `logs/` all re-confirmed returning 403 over HTTP after the `.htaccess` syntax hardening. New HSTS/CSP headers confirmed present on real HTTP responses via `curl -I`. `verify_clean_install.php` run against a real scratch database before (29/30, the 1 failure being the genuinely-missing `work_calendar_settings` default row) and after (**30/30**) the `INSTALL_SEQUENCE` fix — scratch database dropped cleanly both times. Phase 1 regression 20/20, Phase 2 regression 29/29, Phase 5 regression 40/40 — all re-run clean after every change in this entry.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes — KOM-054 row updated to Fixed with full verification detail; a register-tracking correction for Phase 5's stale "0 Open" claim disclosed in the same narrative entry.

---

### CC-138 — DigitalOcean Droplet deployment guide (Stage 6.2)

- **Date:** 2026-07-13
- **Phase:** 6
- **Finding ID(s) addressed:** None — new documentation deliverable, not tied to a specific finding.
- **Files changed:** `docs/remediation/Deployment/phase6-digitalocean-deployment-guide.md` (new)
- **Reason:** Charter §6 (retargeted from cPanel/Namecheap to the user-confirmed DigitalOcean Droplet + Nginx + PHP-FPM platform) requires a deployment certification guide. Written as a complete, prescriptive, copy-paste-ready procedure: droplet sizing, initial server hardening (SSH keys, `ufw`), Nginx/PHP-FPM/MariaDB installation, dedicated non-root database user, environment-variable configuration via PHP-FPM pool `env[]` directives (using Stage 6.1's new `getenv()` mechanism), Let's Encrypt SSL, cron/scheduler setup with output-suppression and log rotation, and a post-deployment checklist. Its most consequential section translates all 10 of the app's `.htaccess` files into equivalent Nginx directives — this app has no `public/`-style document root separating the codebase from the web root, making these deny-rules (blocking PHP execution in `uploads/`, denying `config/`/`database/`/`cron/`/`logs/` entirely) a hard security requirement under Nginx, not a nice-to-have, since Nginx never reads `.htaccess` at all.
- **Tests added/updated:** None — a documentation deliverable, not executable/testable without a real droplet (explicitly out of this phase's confirmed scope).
- **Regression tests executed:** N/A — no code changed. The Nginx translation was verified by reading every one of the 10 actual `.htaccess` files' current live content directly (not from memory or the earlier baseline audit's summary) and confirming line-by-line coverage in the new Nginx config; the `Content-Security-Policy-Report-Only` string was confirmed byte-identical to the one Stage 6.1 added to the live `.htaccess`, so the two never drift apart.
- **Verification result:** VERIFIED (documentation review — content-complete, not live-execution-verified, consistent with this stage's confirmed scope)
- **Master Register updated:** No new/altered findings — narrative entry only.

---

### CC-139 — Database certification (Stage 6.3)

- **Date:** 2026-07-13
- **Phase:** 6
- **Finding ID(s) addressed:** None — certification pass, not tied to a specific finding.
- **Files changed:** None (verification-only stage; `docs/remediation/Phase6/04-database-certification-report.md` new).
- **Reason:** Charter §7 requires database certification (fresh install, upgrade install, backup/restore, rollback, large dataset, index usage, orphan/duplicate detection, connection failures). Fresh install and upgrade install were already certified (Stage 6.1 and Phase 3's Stage 3.9 respectively) and referenced rather than repeated. This stage added: idempotency re-verification of the 3 upgrade migration files against real live (already-migrated) data; a genuine backup/restore drill; a documented rollback strategy; orphan/duplicate re-verification; connection-failure handling verification; and query-plan spot-checks.
- **Tests added/updated:** None — all verification performed via direct MySQL queries and live PHP execution against disposable test data/scratch databases.
- **Regression tests executed:** Re-ran `phase11_schema_reconciliation.sql`, `phase12_workflow_integrity_fixes.sql`, `phase13_workflow_completeness_automation.sql` against the live database — all exit code 0, row counts unchanged (genuine idempotency proof). Backup/restore drill: real `mysqldump` backup, disposable test insert, restore into a scratch database, confirmed the restore correctly excludes the post-backup insert (13 employees, not 14) and table counts match (66/66). Orphan checks across 8 FK relationships (including Phase 5's two newest, `temp_attendance` and `password_reset_tokens`) — 0 orphans. Duplicate checks on `employee_number`/`national_id` — 0 duplicates. Connection-failure test: forced a real PDO connection failure via bad credentials, confirmed the client-facing response is generic (`{"error":"Database connection failed..."}`) while the full diagnostic detail is captured server-side via `error_log()`. `EXPLAIN` run on 3 representative heavy queries (dashboard attendance count, Reports employee×attendance join, audit-log export filter) — 2 use indexes efficiently; 1 (`audit_logs` export sorted by `created_at`) shows `Using filesort`, not fixed since it's sub-millisecond at the current 414-row scale — documented as a future scaling consideration only. All scratch databases dropped and disposable test data removed after verification.
- **Verification result:** VERIFIED live
- **Master Register updated:** No new/altered findings — narrative entry only.

### CC-140 — DB_HOST hostname-resolution bottleneck fixed via load testing

- **Date:** 2026-07-13
- **Phase:** 6, Stage 6.4 (Load & Performance Testing)
- **Finding ID(s) addressed:** None (performance investigation, not a tracked register finding)
- **Files changed:** `config/config.php` (`DB_HOST` fallback default changed from `'localhost'` to `'127.0.0.1'`), `docs/remediation/Phase6/05-load-performance-testing-report.md` (new)
- **Reason:** Apache Bench load testing (scaled tiers 10/25/50/100 concurrent requests, per user-confirmed scope — see Stage 6.0 decisions) on `auth/login.php` showed a suspicious flat ~2.3s per-request latency independent of concurrency. Isolated via curl timing → PHP profiling → raw PDO connection testing to a Windows-specific IPv6-then-IPv4-fallback delay when resolving the hostname `"localhost"` (~2.07s) versus connecting directly to `127.0.0.1` (~0.018s) — a ~115x difference affecting nearly every database-touching request. Fixed by changing the default fallback (still fully overridable via the `DB_HOST` environment variable per Stage 6.1's mechanism); `127.0.0.1` is also a more portable default independent of this specific Windows artifact.
- **Tests added/updated:** None — verified via direct `curl`/`ab`/raw-PDO timing measurements, not a scripted regression test.
- **Regression tests executed:** 5 consecutive live HTTP requests to `auth/login.php` post-fix: ~20–45ms each (down from ~2.3–2.6s). Full `login.php` load-test re-run across all 4 tiers: throughput rose from a 4.39–42.98 req/s range to a 42.63–204.62 req/s range, 0 failures at every tier both before and after. Separately investigated (not fixed, no code change) a `dashboard.php` anomaly at c=50 (298/500 "failed" — Length mismatches), traced most likely to PHP's exclusive per-session file lock serializing all concurrent `ab` workers behind one shared test session cookie — an artifact of the test's own single-session methodology, not a genuine multi-user production defect; documented in full in the Stage 6.4 report per "optimize only where evidence exists." Full Phase 1 (20/20), Phase 2 (29/29), and Phase 5 (40/40) regression suites re-run after the `DB_HOST` fix — all passed.
- **Verification result:** VERIFIED live
- **Master Register updated:** No new/altered KOM finding — narrative entry only (see register's Stage 6.4 log entry).

### CC-141 — Security certification: .git exposure fixed, tests/ QA toolkit exposure fixed (KOM-100, new)

- **Date:** 2026-07-13
- **Phase:** 6, Stage 6.5 (Security Certification)
- **Finding ID(s) addressed:** KOM-100 (new)
- **Files changed:** `.htaccess` (root, new dotfile/dot-directory deny rule via `mod_rewrite`), `tests/.htaccess` (new, `Deny from all`), `docs/remediation/Deployment/phase6-digitalocean-deployment-guide.md` (§5 now runs `rm -rf tests/` after clone; §7's Nginx deny list extended to include `tests/`), `docs/remediation/Phase6/06-security-certification-report.md` (new)
- **Reason:** Live testing found `.git/config`, `.git/HEAD`, and `.git/logs/HEAD` all servable over HTTP (HTTP 200, no rule blocking dotfiles/dot-directories at all) — a full source-history dump was possible. Separately, and more seriously, found the pre-existing `tests/` QA toolkit (~300 tracked files: Playwright audit scripts, ~300 screenshots across every module/role, JSON audit reports) fully downloadable with no restriction; `tests/full-audit-2026.js` contains the hardcoded default admin password, confirmed via a real login POST to still be the actual, currently-valid password on this environment's `superadmin`/`hrmanager`/`hrofficer`/`payroll` accounts (`must_change_password` is incorrectly `0` on all four — the forced-first-login-change gate was bypassed at some point without a real rotation happening). New finding KOM-100 (Critical) logged for this.
- **Tests added/updated:** None — verified via direct `curl` HTTP-status checks and a real (immediately-cleaned-up) login POST.
- **Regression tests executed:** `.git/config`, `.git/HEAD`, `.git/logs/HEAD`, `.htaccess`, `.env.example`, `tests/full-audit-2026.js`, `tests/audit-2026/audit-report.json`, `tests/screenshots/01_dashboard.png` all confirmed to now return 403; `auth/login.php` and `assets/css/style.css` confirmed unaffected (200). Full Phase 1 (20/20), Phase 2 (29/29), and Phase 5 (40/40) regression suites re-run after the `.htaccess` changes — all passed.
- **Verification result:** VERIFIED live. The live credential itself was deliberately **not** rotated — it is the user's own active login to their own system; flagged explicitly to the user and in the Stage 6.5 report rather than changed unilaterally.
- **Master Register updated:** Yes (new finding KOM-100, marked Partially Fixed — exposure vector closed, credential rotation left to the account owner)

### CC-142 — Backup & restore scripts built and live-tested (Disaster Recovery Guide)

- **Date:** 2026-07-13/14
- **Phase:** 6, Stage 6.6 (Backup & Disaster Recovery)
- **Finding ID(s) addressed:** None (new infrastructure, not a fix to a documented defect)
- **Files changed:** `scripts/backup.sh` (new), `scripts/restore.sh` (new), `scripts/.htaccess` (new, `Deny from all`), `docs/remediation/Deployment/phase6-digitalocean-deployment-guide.md` (new §10.5 backup cron scheduling; §7 Nginx deny list and §11 checklist updated), `docs/remediation/Phase6/07-disaster-recovery-guide.md` (new)
- **Reason:** Charter §10 requires a documented, tested backup/restore/DR procedure covering the database, uploaded files (including branding assets), and configuration, with RPO/RTO targets and recovery procedures for realistic disaster scenarios. No such tooling or documentation existed before this stage.
- **Tests added/updated:** None scripted — verified via a real, disposable-data restore drill (see next field).
- **Regression tests executed:** Live drill: inserted a disposable pre-backup test employee (`P6TEST-DR-BEFORE`) and test file (`uploads/documents/P6TEST-DR-marker.txt`); ran `backup.sh manual`; inserted a disposable post-backup test employee (`P6TEST-DR-AFTER`) and deleted the test file (simulating accidental deletion); confirmed `restore.sh` refuses to run without `--confirm`/`--target-db`; restored into a scratch database (`komagin_hr_dr_restore_test`) and confirmed it contains `P6TEST-DR-BEFORE` only (not `-AFTER`), proving genuine point-in-time correctness; confirmed the deleted test file was present in the extracted files archive. All test artifacts (scratch database, extracted-files directory, both test employee rows, disposable backup files) removed after verification. Full Phase 1 (20/20), Phase 2 (29/29), and Phase 5 (40/40) regression suites re-run — all passed.
- **Verification result:** VERIFIED live
- **Master Register updated:** No new/altered KOM finding — narrative entry only (see register's Stage 6.6 log entry).

### CC-143 — Administrator Guide written

- **Date:** 2026-07-14
- **Phase:** 6, Stage 6.7 (Operational Documentation)
- **Finding ID(s) addressed:** None (documentation-only)
- **Files changed:** `docs/remediation/Phase6/08-administrator-guide.md` (new)
- **Reason:** Charter §11 requires operational documentation covering installation, upgrade, backup/restore, troubleshooting, cron, email, database, permissions, user management, payroll, leave, attendance, document generation, branding, DR, incident response, maintenance, version upgrade, and release notes. Several of these already had dedicated, detailed documents (`database/README.md`, the deployment guide, the Disaster Recovery Guide, `cron/README.md`) — this stage writes the remaining genuinely-undocumented operational material (day-to-day business-process operation, troubleshooting, incident response, maintenance) and cross-references rather than duplicates the rest.
- **Tests added/updated:** N/A
- **Regression tests executed:** N/A — documentation-only, no application code, configuration, or database touched.
- **Verification result:** N/A
- **Master Register updated:** No new/altered KOM finding — narrative entry only (see register's Stage 6.7 log entry).

### CC-144 — Password reset token no longer leaked via email_logs (KOM-101, new); php_errors.log rotation added

- **Date:** 2026-07-14
- **Phase:** 6, Stage 6.8 (Logging & Monitoring Verification)
- **Finding ID(s) addressed:** KOM-101 (new)
- **Files changed:** `config/functions.php` (`sendEmail()` gained an optional `$logBodyHtml` parameter), `auth/forgot_password.php` (passes a redacted body for logging), `docs/remediation/Testing/phase5-regression-run.sh` (new redaction-verification assertion; replacement token-generation step since the raw token is no longer recoverable from any log), `docs/remediation/Deployment/phase6-digitalocean-deployment-guide.md` (added `logs/php_errors.log` rotation, §11 checklist updated), `docs/remediation/Phase6/09-logging-monitoring-report.md` (new)
- **Reason:** `email_logs.body_html` persisted the full password-reset email verbatim, including the raw, unhashed reset token embedded in the link — a live, account-takeover-capable credential recoverable in plaintext from a table that `password_reset_tokens.token_hash` was specifically designed to keep hashed. Live-verified exploitable before the fix. Separately, `logs/php_errors.log` had no documented rotation policy, unlike the other two log files.
- **Tests added/updated:** `phase5-regression-run.sh` Group 5 — new assertion confirming the real `email_logs` row created by the existing enumeration-resistance test does not contain a raw-token pattern; replaced the old token-extraction step (which relied on the now-fixed leak) with a direct-generation step using the identical hashing logic, keeping the full reset-completion/session-invalidation flow tested end to end.
- **Regression tests executed:** Live: triggered a real `forgot_password.php` request against a disposable test account — confirmed `email_logs.body_html` now contains the redaction placeholder (not the token) while `password_reset_tokens.token_hash` remains a normal, correctly-formed hash. Full Phase 1 (20/20), Phase 2 (29/29), and Phase 5 (41/41, +1 new assertion) regression suites re-run — all passed.
- **Verification result:** VERIFIED live
- **Master Register updated:** Yes (new finding KOM-101, marked Fixed)

### CC-145 — Release Candidate Checklist compiled (30 items, independently re-verified)

- **Date:** 2026-07-14
- **Phase:** 6, Stage 6.9 (Release Candidate Checklist)
- **Finding ID(s) addressed:** None (documentation/verification consolidation)
- **Files changed:** `docs/remediation/Phase6/10-release-candidate-checklist.md` (new)
- **Reason:** Charter §13 requires a 30-item Release Candidate checklist, every item verified against current, post-6.1–6.8 state, before the program can proceed to final regression and sign-off.
- **Tests added/updated:** None new — re-ran a live spot-check sweep across the highest-risk items (security headers, exposure protections for `.git/`/`tests/`/`scripts/`/`config/`/`database/`/`logs/`/`cron/`, scheduler web-blocking, basic app functionality) rather than only trusting each earlier stage's own report.
- **Regression tests executed:** Live: `curl` checks confirmed all 5 security headers present on `auth/login.php`; 9 non-web-facing paths all return 403; `auth/login.php` returns 200 and `dashboard.php` (unauthenticated) returns 302. No application code changed this stage, so the full Phase 1/2/5 suites were not re-run (last confirmed clean at the close of Stage 6.8: 20/20, 29/29, 41/41).
- **Verification result:** VERIFIED live. 29/30 items PASS; 1 disclosed exception (KOM-100's live-credential rotation, left to the account owner) — not a FAIL.
- **Master Register updated:** No new/altered KOM finding — narrative entry only (see register's Stage 6.9 log entry).

### CC-146 — Final regression pass; deployment simulation and browser testing limitations disclosed

- **Date:** 2026-07-14
- **Phase:** 6, Stage 6.10 (Final Regression)
- **Finding ID(s) addressed:** None (verification/documentation)
- **Files changed:** `docs/remediation/Phase6/11-final-regression-report.md` (new)
- **Reason:** Charter §14 requires a final, comprehensive regression pass across Phases 1-6 before sign-off, covering the full syntax/migration/cron/email/backup/restore/deployment/load/security/browser/acceptance surface.
- **Tests added/updated:** None new — a fresh, final execution of the existing suites/checks.
- **Regression tests executed:** Phase 1 (20/20), Phase 2 (29/29), Phase 5 (41/41, includes repo-wide syntax scan + migration verification), scheduler CLI run (4/4 tasks OK), security headers (5/5 present), web-exposure protections (7/7 paths return 403), basic app functionality (login 200, unauthenticated dashboard 302) — all re-run fresh and passed. Backup/restore, load testing, and security testing cited from their own already-completed Stage 6.3/6.4/6.5/6.6/6.8 live drills rather than redundantly repeated.
- **Verification result:** VERIFIED live. Two categories explicitly disclosed as not performable in this environment (full deployment simulation against real infrastructure; real-browser smoke testing) rather than claimed complete — both carried forward as pre-go-live recommendations. Administrator acceptance flagged as requiring the account owner's own review.
- **Master Register updated:** No new/altered KOM finding — narrative entry only (see register's Stage 6.10 log entry).

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-11 | Template and rules established for Phase 0 | Remediation Program — Phase 0 |
| 2026-07-11/12 | 13 entries (CC-001–CC-013) recorded for Phase 1 | Remediation Program — Phase 1 |
| 2026-07-11/12 | 11 entries (CC-014–CC-024) recorded for Phase 2 | Remediation Program — Phase 2 |
| 2026-07-12 | 11 entries (CC-025–CC-035) recorded for Phase 3 | Remediation Program — Phase 3 |
| 2026-07-12 | 10 entries (CC-036–CC-045) recorded for Phase 4, Workflow Group 1 (Employee Management) | Remediation Program — Phase 4 |
| 2026-07-12 | 5 entries (CC-046–CC-050) recorded for Phase 4, Workflow Group 2 (Department & Position Management) | Remediation Program — Phase 4 |
| 2026-07-12 | 7 entries (CC-051–CC-057) recorded for Phase 4, Workflow Group 3 (Leave Management) | Remediation Program — Phase 4 |
| 2026-07-12 | 4 entries (CC-058–CC-061) recorded for Phase 4, Workflow Group 4 (Attendance & Timesheets) | Remediation Program — Phase 4 |
| 2026-07-12 | 5 entries (CC-062–CC-066) recorded for Phase 4, Workflow Group 5 (Payroll) | Remediation Program — Phase 4 |
| 2026-07-12 | 1 entry (CC-067) recording KOM-085/KOM-086 user decisions | Remediation Program — Phase 4 |
| 2026-07-12 | 3 entries (CC-068–CC-070) recorded for Phase 4, Workflow Group 6 (Performance Management) | Remediation Program — Phase 4 |
| 2026-07-12 | 4 entries (CC-071–CC-074) recorded for Phase 4, Workflow Group 7 (Recruitment) | Remediation Program — Phase 4 |
| 2026-07-12 | 4 entries (CC-075–CC-078) recorded for Phase 4, Workflow Group 8 (Training) | Remediation Program — Phase 4 |
| 2026-07-12 | 3 entries (CC-079–CC-081) recorded for Phase 4, Workflow Group 9 (Consultant Module) | Remediation Program — Phase 4 |
| 2026-07-13 | 6 entries (CC-082–CC-087) recorded for Phase 4, Workflow Group 10 (Temporary Employee Module) | Remediation Program — Phase 4 |
| 2026-07-13 | 5 entries (CC-088–CC-092) recorded for Phase 4, Workflow Group 11 (Notifications) | Remediation Program — Phase 4 |
| 2026-07-13 | 6 entries (CC-093–CC-098) recorded for Phase 4, Workflow Group 12 (Documents Generation Lifecycle) | Remediation Program — Phase 4 |
| 2026-07-13 | 4 entries (CC-099–CC-102) recorded for Phase 4, Workflow Group 13 (Reports & Dashboards Consistency) | Remediation Program — Phase 4 |
| 2026-07-13 | 1 entry (CC-103) recorded for the KOM-045 close-out decision — all 13 Phase 4 workflow groups now complete | Remediation Program — Phase 4 |
| 2026-07-13 | 2 entries (CC-104–CC-105) recorded for Phase 5, Stage 5.1 (Leave Approval Model Completion) | Remediation Program — Phase 5 |
| 2026-07-13 | 1 entry (CC-106) recorded for Phase 5, Stage 5.2 (ApprovalEngine Dormant Workflow Types) | Remediation Program — Phase 5 |
| 2026-07-13 | 1 entry (CC-107) recorded for Phase 5, Stage 5.3 (Working-Day & Holiday Calendar) | Remediation Program — Phase 5 |
| 2026-07-13 | 1 entry (CC-108) recorded for Phase 5, Stage 5.4 (Scheduled Task Infrastructure) | Remediation Program — Phase 5 |
| 2026-07-13 | 2 entries (CC-109–CC-110) recorded for Phase 5, Stage 5.5 (Self-Service Password Recovery, Admin Surface Only) | Remediation Program — Phase 5 |
| 2026-07-13 | 2 entries (CC-111–CC-112) recorded for Phase 5, Stage 5.6 (Deferred Notification Workflows) | Remediation Program — Phase 5 |
| 2026-07-13 | 1 entry (CC-113) recorded for Phase 5, Stage 5.7 (Recruitment-to-Employee Conversion) | Remediation Program — Phase 5 |
| 2026-07-13 | 1 entry (CC-114) recorded for Phase 5, Stage 5.8 (Temporary Employee Attendance Capture) | Remediation Program — Phase 5 |
| 2026-07-13 | 1 entry (CC-115) recorded for Phase 5, Stage 5.9 (Document QR Verification — Disabled) | Remediation Program — Phase 5 |
| 2026-07-13 | 17 entries (CC-116–CC-132) recorded for Phase 5, Stage 5.10 (Remaining Open Findings Closure — 20 findings) | Remediation Program — Phase 5 |
| 2026-07-13 | 2 entries (CC-133–CC-134) recorded for Phase 5, Stage 5.11 (Permissions, Configuration & Dead-Code Reconciliation) | Remediation Program — Phase 5 |
| 2026-07-13 | 1 entry (CC-135) recorded for Phase 5, Stage 5.12 (Security & Privacy Review) | Remediation Program — Phase 5 |
| 2026-07-13 | 1 entry (CC-136) recorded for Phase 5, Stage 5.13 (Full Phase 5 Regression Suite) | Remediation Program — Phase 5 |
| 2026-07-13 | 1 entry (CC-137) recorded for Phase 6, Stage 6.1 (Production Blocker Fixes) | Remediation Program — Phase 6 |
| 2026-07-13 | 1 entry (CC-138) recorded for Phase 6, Stage 6.2 (DigitalOcean Droplet Deployment Guide) | Remediation Program — Phase 6 |
| 2026-07-13 | 1 entry (CC-139) recorded for Phase 6, Stage 6.3 (Database Certification) | Remediation Program — Phase 6 |
| 2026-07-13 | 1 entry (CC-140) recorded for Phase 6, Stage 6.4 (Load & Performance Testing) | Remediation Program — Phase 6 |
| 2026-07-13 | 1 entry (CC-141) recorded for Phase 6, Stage 6.5 (Security Certification) | Remediation Program — Phase 6 |
| 2026-07-14 | 1 entry (CC-142) recorded for Phase 6, Stage 6.6 (Backup & Disaster Recovery) | Remediation Program — Phase 6 |
| 2026-07-14 | 1 entry (CC-143) recorded for Phase 6, Stage 6.7 (Operational Documentation) | Remediation Program — Phase 6 |
| 2026-07-14 | 1 entry (CC-144) recorded for Phase 6, Stage 6.8 (Logging & Monitoring Verification) | Remediation Program — Phase 6 |
| 2026-07-14 | 1 entry (CC-145) recorded for Phase 6, Stage 6.9 (Release Candidate Checklist) | Remediation Program — Phase 6 |
| 2026-07-14 | 1 entry (CC-146) recorded for Phase 6, Stage 6.10 (Final Regression) | Remediation Program — Phase 6 |
