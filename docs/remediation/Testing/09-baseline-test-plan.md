# Komagin HR — Baseline Test Plan

**Document type:** Phase 0 supporting deliverable (Task 10)
**Status:** Preparation only — no test in this document has been executed as part of Phase 0. This is a checklist for future remediation phases to run against.
**Date compiled:** 2026-07-11
**Baseline tag:** `v1.0-enterprise-baseline`

---

## Purpose

Every module gets a standard 7-category verification checklist. Later phases run the relevant rows before and after a fix to prove (a) the fix works and (b) nothing else broke. Test IDs are referenced from the Master Remediation Register's "Verification Required" column where a specific finding drives the test.

## Test Categories (applied per module)

| Category | What it proves |
|---|---|
| **Smoke** | The module's entry page loads without a fatal error or blank screen, for at least one valid role |
| **Functional** | The module's core create/read/update/delete/approve actions behave as documented in the Architecture Report |
| **Permission** | Every role sees exactly the access the Permission Matrix Report says it should — no more, no less |
| **Authentication** | Session/login boundaries hold — no page is reachable without the session state it requires |
| **Integration** | Cross-module data flows correctly (e.g. a leave approval updates the leave balance the reports module later reads) |
| **Regression** | A specific previously-reported finding, re-tested to confirm it stays fixed after remediation |
| **Deployment** | The module behaves correctly in a fresh/clean environment, not just the current dev database |

---

## Module-by-Module Checklist

For each module below: Smoke / Functional / Permission tests are the minimum bar before any Phase 1+ work is considered mergeable for that module. Integration and Deployment tests are called out only where a specific cross-module dependency or environment risk exists (see Master Remediation Register).

### Approvals
- [ ] Smoke: `modules/approvals/index.php` loads for `hr_manager`
- [ ] Functional: create a leave workflow, advance it through all stages
- [ ] **Permission (KOM-001 regression):** attempt to approve a stage as a role that is NOT the stage's `approver_role`; must be rejected
- [ ] Integration: an approved leave workflow correctly updates `leave_applications.status`

### Attendance / Kiosk
- [ ] Smoke: `modules/attendance/index.php`, `kiosk.php`, `kiosk_manage.php` all load
- [ ] Functional: sign-in/break/sign-out sequencing enforced correctly
- [ ] **Authentication (KOM-003 regression):** POST to kiosk.php with no `?t=` token from an external client and a guessed employee number; must be rejected once a location-bound token is required
- [ ] Permission: `kiosk.manage` required for `kiosk_manage.php`
- [ ] Deployment: confirm `kiosk_sessions`/`kiosk_audit` tables exist post-fresh-install (KOM-004 dependency)

### Consultants
- [ ] Smoke: `modules/consultants/index.php` loads
- [ ] **Functional (KOM-002 regression):** submit add/edit/delete/scope_save forms; none may throw a fatal error
- [ ] Permission: `consultants.*` slugs enforced per matrix

### Temp Employees
- [ ] Smoke: all 6 module files load
- [ ] **Functional (KOM-005 regression):** save an add/edit form; confirm no DB column error
- [ ] **Regression (KOM-020):** submit `?status=<script>` to `index.php`'s export; confirm no script execution
- [ ] **Regression (KOM-035):** create/edit/delete a temp employee; confirm the resulting `audit_logs.record_id` matches the temp employee, not the admin
- [ ] Permission: `temp_employees.*` enforced per matrix, including for `hr_officer` specifically (KOM-023 regression)

### Employees
- [ ] Smoke, Functional: standard CRUD
- [ ] **Regression (KOM-025):** open edit form for an employee with salary set; confirm field pre-populated
- [ ] Permission: bank/salary fields masked for non-payroll roles

### Leave
- [ ] **Functional (KOM-007 regression):** submit a leave application; confirm no fatal error and HR notified
- [ ] **Functional (KOM-009 regression):** click Approve/Reject from the detail page; confirm status changes
- [ ] Integration: approval correctly decrements leave balance

### Timesheets
- [ ] **Permission (KOM-010 regression):** attempt timesheet/overtime approval as a view-only role; must be rejected
- [ ] Permission: corrections approval matches the DB-driven matrix, not a hardcoded list (KOM-040 regression)

### Training
- [ ] **Functional (KOM-008 regression):** enrol an employee; confirm they appear in the roster and attendee count

### Recruitment / Onboarding / Performance / Disciplinary / Assets
- [ ] Smoke, Functional, Permission — standard checklist, no module-specific findings currently open beyond KOM-049 (Performance rating save)
- [ ] **Functional (KOM-049 regression):** submit a performance review score; confirm it's saved and displayed correctly

### Documents
- [ ] Smoke, Functional: template authoring, generation, viewing
- [ ] **Permission (KOM-036 regression):** attempt to save a generated document as a view-only role once one exists in the matrix
- [ ] **Permission (KOM-021 regression):** attempt to view another employee's generated document by ID; must be rejected
- [ ] **Security (KOM-022 regression):** save a template containing a script payload; confirm it renders neutralized
- [ ] **Deployment (KOM-006 / NC-02):** load a branding image URL directly; confirm 200, not 403

### Reports
- [ ] **Permission (KOM-011/KOM-044 regression):** view Executive Analytics as a non-payroll `reports.view` role; confirm payroll totals are masked
- [ ] **Functional (KOM-028 regression):** click Export CSV from the Reports Hub; confirm a file downloads

### Payroll
- [ ] **Permission (KOM-014 regression):** attempt to delete a deduction/savings record as a role with `can_delete=0`; must be rejected
- [ ] **Functional (KOM-016 regression):** attempt to edit a published payslip; confirm rejection or audit entry recorded
- [ ] **Integration (KOM-030 regression):** two concurrent publish requests on the same run; confirm no duplicate emails

### Archive
- [ ] **Functional (KOM-051 regression):** confirm the Lock control actually locks the target period

### Users / Roles / Settings / Audit / Activity Log
- [ ] **Security (KOM-015 regression):** attempt to set an unauthorized role value via direct POST to `users/index.php`; must be rejected
- [ ] **Permission (KOM-019 regression):** confirm Activity Log is gated by a seeded permission slug, not a hardcoded role check
- [ ] Manual (KOM-031 regression): View Source on `settings/email.php`; confirm no cleartext SMTP password anywhere
- [ ] Permission (KOM-032 regression): confirm each branding asset type has its own permission gate

### Hub
- [ ] Smoke, Functional, Permission — standard checklist

### Employee Portal / Temp Portal
- [ ] **Authentication (KOM-017 regression):** log in, confirm session ID changes (regeneration)
- [ ] **Authentication (KOM-052 regression):** attempt 6+ rapid failed logins; confirm lockout behavior once implemented
- [ ] **Security (KOM-027 regression):** forged cross-site POST to hub.php; must be rejected once CSRF is added
- [ ] Deployment (KOM-042 regression): confirm session cookie carries `Secure` flag when served over HTTPS

### Consultant Portal
- [ ] **Authentication (KOM-012 regression):** log in, confirm session ID changes
- [ ] **Security (KOM-013 regression):** forged cross-site POST to kiosk.php/scope.php; must be rejected once CSRF is added
- [ ] **Functional (KOM-043 regression):** log out, inspect session state server-side; confirm full destruction

### Self-Service
- [ ] Functional: generate a link, use it once, confirm it's rejected on reuse
- [ ] Security (KOM-062 regression): confirm CSRF comparison uses `hash_equals()` post-fix

### Dashboard
- [ ] **Permission (KOM-018 regression):** view dashboard as a narrowly-scoped role (e.g. `kiosk_terminal`); confirm the Recent Activity widget is absent or filtered

### Database / Deployment (system-wide)
- [ ] **Deployment (KOM-004 regression):** run a from-empty install using only tracked `.sql` files in documented order; confirm zero missing-table errors
- [ ] **Deployment (KOM-024 regression):** run `database/install.php` on an empty DB; confirm all modules are functional immediately after, not just those covered by `schema.sql`

---

## Execution Notes for Future Phases

1. Every checkbox above corresponds 1:1 to a "Verification Required" entry in the Master Remediation Register. When a finding is remediated, run its regression test, record the result in `Verification/` (see Baseline Verification Report §3 for the template), and only then update the register's Completion/Verification Status columns.
2. No test in this plan has been executed yet. Phase 0's job was to prepare this checklist, not run it.
3. Recommended execution order follows the register's Target Phase assignment: Phase 1 (Critical) tests first, then Phase 2 (High), etc.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-11 | Initial baseline test plan compiled for Phase 0 | Remediation Program — Phase 0 |
