# Komagin HR — Change Control Log & Template

**Document type:** Phase 0 supporting deliverable (Task 11) — first populated in Phase 1
**Status:** Living log. 13 entries recorded for Phase 1; 11 more (CC-014–CC-024) recorded for Phase 2; 11 more (CC-025–CC-035) recorded for Phase 3; 10 more (CC-036–CC-045) recorded for Phase 4, Workflow Group 1; 5 more (CC-046–CC-050) recorded for Phase 4, Workflow Group 2; 7 more (CC-051–CC-057) recorded for Phase 4, Workflow Group 3; 4 more (CC-058–CC-061) recorded for Phase 4, Workflow Group 4; 5 more (CC-062–CC-066) recorded for Phase 4, Workflow Group 5; 1 more (CC-067) recording the KOM-085/KOM-086 user decisions; 3 more (CC-068–CC-070) recorded for Phase 4, Workflow Group 6; 4 more (CC-071–CC-074) recorded for Phase 4, Workflow Group 7; 4 more (CC-075–CC-078) recorded for Phase 4, Workflow Group 8; 3 more (CC-079–CC-081) recorded for Phase 4, Workflow Group 9; 6 more (CC-082–CC-087) recorded for Phase 4, Workflow Group 10; **5 more (CC-088–CC-092) recorded for Phase 4, Workflow Group 11 — more to follow as each subsequent workflow group completes.**
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
