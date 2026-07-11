# Komagin HR — Complete Project Inventory

**Document type:** Phase 0 Baseline Deliverable #1 of 9
**Status:** Documentation only — no application file referenced in this document was modified to produce it.
**Date compiled:** 2026-07-11
**Baseline tag:** `v1.0-enterprise-baseline`

---

## Purpose

This document is the master file-level inventory of the Komagin HR Management System as it exists at the moment the Enterprise Remediation Program begins. It is the reference point every later remediation phase diffs against. Size key: **small** < 100 lines · **medium** 100–300 lines · **large** > 300 lines.

---

## 1. Admin Modules (`modules/`) — 24 subdirectories, 89 PHP files

### `modules/activity_log/` — activity trail for individual users/entities (super-admin only; bypasses standard permission system — see Finding KOM-013/NH-02)
- `download.php` — CSV export of activity logs (individual + bulk/category export) — large (382)
- `index.php` — main activity log dashboard — large (472)
- `user.php` — per-user/entity activity log detail view — medium (356)

### `modules/approvals/` — central approval workflow inbox
- `index.php` — lists pending approvals for current user + all workflows for admin/HR, handles approve/reject — medium (187)

### `modules/archive/` — historical/period data snapshots
- `monthly.php` — monthly attendance/leave/headcount archive view + generate action — medium (221)
- `quarterly.php` — quarterly stats archive (OT, monthly breakdown) — medium (240)
- `save.php` — POST handler to persist an archive snapshot — small (49)
- `yearly.php` — annual summary archive — medium (336)

### `modules/assets/` — company asset tracking
- `index.php` — asset list/CRUD — medium (282)

### `modules/attendance/` — clock-in/out and attendance tracking
- `index.php` — daily attendance dashboard — medium (181)
- `kiosk.php` — standalone public kiosk clock-in/out screen, no login required — large (627)
- `kiosk_manage.php` — admin management of kiosk sessions/locations — large (404)

### `modules/audit/` — system audit log (older, permission-gated sibling of activity_log)
- `index.php` — audit log viewer — medium (143)

### `modules/consultants/` — external consultant management
- `add.php` — create consultant, auto-generates consultant number — medium (259)
- `delete.php` — delete consultant — small (33)
- `edit.php` — edit consultant record — medium (246)
- `index.php` — consultant list + CSV export + stats — medium (249)
- `scope_save.php` — save output-based scope/checklist items — small (66)
- `view.php` — consultant detail view, type-specific data — large (391)

### `modules/disciplinary/` — disciplinary case management
- `index.php` — disciplinary case list — medium (242)
- `save.php` — create/update disciplinary case — small (44)
- `view.php` — case detail + status update/close — medium (178)

### `modules/documents/` — document generation/template engine
- `generate.php` — generate document from template using DocumentEngine — medium (223)
- `index.php` — document list, expiring-soon widget — medium (154)
- `missing.php` — report of employees missing required documents — medium (114)
- `templates.php` — manage document templates/categories — large (400)
- `upload.php` — upload employee document — medium (130)
- `verify.php` — verify/approve an uploaded document — small (33)
- `view_generated.php` — view a generated document, approve/issue/print — medium (163)

### `modules/employees/` — core employee records
- `add.php` — create employee, photo upload, leave balances, kiosk PIN — large (463)
- `delete.php` — delete employee w/ cascade impact summary — large (310)
- `dependent_save.php` — save employee dependent record — small (39)
- `edit.php` — edit employee profile (bank/salary gated by payroll permission) — large (468)
- `generate_link.php` — generate secure self-service update link/token — small (84)
- `id_card.php` — generate/print employee ID card — medium (314)
- `index.php` — employee list, filters, stats — medium (241)
- `pending_updates.php` — review/approve self-service submitted field updates — medium (232)
- `qualification_save.php` — save employee qualification record — small (41)
- `set_portal_password.php` — set/reset employee portal login password — small (46)
- `status.php` — change employee status — medium (141)
- `view.php` — full employee profile detail page (largest module file in the system) — large (839)
- `work_history_save.php` — save employee work history entry — small (34)

### `modules/hub/` — announcements/requests hub
- `index.php` — hub/announcements listing, HR user assignment dropdown — medium (183)
- `view.php` — single hub post/request detail view — medium (245)

### `modules/leave/` — leave management
- `apply.php` — submit leave application, balance/overlap checks, approval workflow creation — large (281)
- `approve.php` — approve/reject leave application, balance update — medium (188)
- `index.php` — leave applications list w/ status counts — medium (177)
- `types.php` — manage leave types — medium (118)
- `view.php` — leave application detail — medium (184)

### `modules/onboarding/` — new-hire onboarding checklist
- `index.php` — onboarding dashboard for recent/probation hires — medium (177)
- `save.php` — save onboarding checklist item — small (30)

### `modules/payroll/` — payroll processing
- `deductions.php` — manage payroll deductions — medium (278)
- `index.php` — payroll dashboard, period summary, recent payslips — medium (204)
- `payslip_finalize.php` — finalize a single payslip — small (16)
- `payslips.php` — payslip list/create/edit — large (340)
- `reports.php` — payroll reports + CSV export — medium (252)
- `run_finalize.php` — finalize entire payroll run — small (40)
- `run_publish.php` — publish/email payslips to employees — small (46)
- `run_save.php` — create new payroll run from payslip totals — small (37)
- `savings.php` — employee savings/cooperative tracking — medium (331)

### `modules/performance/` — performance review management
- `index.php` — performance review list — medium (194)
- `save.php` — create/update performance review — small (32)
- `view.php` — review detail, status update/approve — medium (169)

### `modules/recruitment/` — hiring/vacancy management
- `application_update.php` — update job application status — small (40)
- `index.php` — vacancy/applicant pipeline dashboard — medium (270)
- `vacancy_save.php` — create/update job vacancy — small (33)

### `modules/reports/` — reporting hub
- `employees.php` — employee report + CSV export — medium (137)
- `executive.php` — executive analytics dashboard — large (395)
- `index.php` — reports hub landing page — medium (237)
- `timesheets.php` — timesheet report per employee — medium (183)

### `modules/roles/` — role/permission matrix
- `index.php` — manage roles and permission matrix toggles — medium (192)

### `modules/settings/` — system configuration
- `branding.php` — manage letterheads/signatures/stamps/watermarks — large (634)
- `email.php` — SMTP/email notification settings + recent email logs — medium (225)
- `index.php` — general system settings — large (326)
- `theme.php` — theme/appearance settings, favicon/login background upload — large (277)

### `modules/temp_employees/` — temporary/contract employee & timesheet management
- `add.php` — create temp employee, auto employee number — large (367)
- `delete.php` — delete temp employee — small (35)
- `edit.php` — edit temp employee incl. portal password — large (387)
- `index.php` — temp employee list, filters, Excel/CSV export — large (497)
- `timesheet.php` — weekly timesheet grid per project/site, Excel export, print view (blank-template, no persistence — see Finding NL-04) — large (412)
- `view.php` — temp employee detail, contract duration — medium (296)

### `modules/timesheets/` — timesheet & overtime management (permanent staff)
- `approve.php` — approve/reject timesheet — small (49)
- `corrections.php` — review/apply attendance correction requests (hardcoded role check — Finding M-02) — large (342)
- `edit.php` — manual timesheet edit w/ hour recalculation & audit — medium (254)
- `index.php` — timesheet list + summary stats — medium (242)
- `overtime.php` — overtime approval/rejection, summary stats — medium (252)

### `modules/training/` — training/course management
- `enrol.php` — enrol employee in training (column-name mismatch — Finding H-02) — small (29)
- `index.php` — training list + stats — medium (228)
- `save.php` — create/update training course — small (33)

### `modules/users/` — system user (login) accounts
- `index.php` — manage system users, POST action handler — medium (267)
- `profile.php` — "My Profile" self-edit page for logged-in user — large (392)

---

## 2. Portals

### `employee-portal/` — self-service portal for permanent + temporary employees
- `_config.php` — bootstraps config/db/functions, defines `EP_URL` — tiny (7)
- `_layout.php` — shared portal HTML layout helper (presentational only, no session logic) — medium (130)
- `_session.php` — portal session guard, must be manually included on every page — small (59)
- `attendance.php` — employee's own attendance view — medium (124)
- `dashboard.php` — portal home dashboard — medium (145)
- `employment.php` — employment details/contract info view — medium (180)
- `hub.php` — employee-facing hub/announcements & requests — large (297)
- `leave.php` — employee's leave applications/balance view — medium (112)
- `login.php` — employee portal login page (permanent + temp branches) — medium (153)
- `logout.php` — destroys portal session, redirects to login — tiny (7)
- `payslip-download.php` — standalone print-to-PDF payslip view — large (380)
- `payslips.php` — employee's payslip list — medium (213)
- `policy.php` — company policy acceptance/view page (pre-login-gate) — medium (163)
- `savings.php` — employee's savings/cooperative view — medium (151)
- `temp_portal.php` — simplified combined portal for temp/contract employees — large (368)

### `consultant-portal/` — self-service portal for consultants
- `_config.php` — bootstraps config, defines `CP_URL` — tiny (7)
- `_layout.php` — shared consultant portal layout — medium (104)
- `_session.php` — consultant portal session guard — small (61)
- `dashboard.php` — consultant portal home — medium (205)
- `index.php` — entry redirect (login/dashboard router) — small (13)
- `kiosk.php` — time-based consultant clock-in/out (in-portal) — medium (219)
- `login.php` — consultant login page — medium (116)
- `logout.php` — destroys session (partially — see Finding NEW-6), redirects to login — small (13)
- `scope.php` — output-based consultant scope/checklist view — medium (190)
- `assets/cp.css` — consultant portal stylesheet

### `self-service/` — standalone token-based self-service (no login)
- `_expired.php` — "link expired" static error page — tiny (24)
- `update.php` — standalone employee data self-update form via secure magic-link token — large (350)

---

## 3. Core / Config (`config/`)
- `config.php` — main app configuration constants (DB creds, APP_URL, upload limits, currency, employee numbering, work hours, APP_ENV logic) — small (70)
- `database.php` — `Database` class, singleton PDO connection wrapper — small (28)
- `functions.php` — large shared function library: CSRF, auth/permission helpers, notifications, email sending, salary/bank masking, upload helper — large (750)
- `ApprovalEngine.php` — class that creates/advances/resolves approval workflows across leave/payroll/documents — medium (234)
- `DocumentEngine.php` — class that resolves `{{variable}}` placeholders and assembles branded document HTML — large (357)
- `.htaccess` — deny direct web access to config directory

---

## 4. Auth (`auth/`)
- `login.php` — main admin/staff login form + auth logic — large (336)
- `logout.php` — logs auditLog entry, destroys session, redirects to login — tiny (15)
- `session.php` — session bootstrap: cookie security settings, ID rotation, idle timeout — small (34)
- `change_password.php` — logged-in user password change form — small (88)

---

## 5. Includes / Shared UI (`includes/`)
- `header.php` — shared page header/sidebar/nav, permission-filtered menu — large (514)
- `footer.php` — shared page footer, loads Lucide icons + conditional Chart.js — medium (118)

---

## 6. API Endpoints (`api/`)
- `leave_balance.php` — JSON: leave balance lookup for employee+leave type (AJAX) — small (32)
- `notifications.php` — JSON: unread/notification list for logged-in user (AJAX) — small (54)
- `positions.php` — JSON: position/job-title lookup list (AJAX) — small (16)
- `search.php` — JSON: global search endpoint — small (85)

---

## 7. Root-Level Entry Scripts
- `dashboard.php` — main authenticated landing dashboard after login — large (538)
- `index.php` — root redirect: to dashboard if logged in, else to login — tiny (11)
- `PHASE_12_DOCUMENTATION.md` — project phase documentation (not code)
- `PRODUCTION_READINESS_REPORT.md` — readiness report doc (not code)
- `.env.example` — sample environment variable file (documentation-only — see Deployment Inventory Report §11)
- `.htaccess` — root Apache config/rewrite rules

---

## 8. Assets (`assets/`)
- `assets/css/style.css` — main application stylesheet
- `assets/js/main.js` — main application JavaScript
- `assets/img/` — image assets directory (not individually enumerated)

---

## 9. Uploads Structure (`uploads/`)
Subdirectories only (contents are user-generated, not enumerated):
`avatars`, `company`, `contracts`, `documents`, `employees`, `letterheads`, `logos`, `photos`, `signatures`, `stamps`, `watermarks`.

---

## 10. Database Assets (`database/`)
- `schema.sql` — base database schema (31 `CREATE TABLE` statements) — large (775) — **known significantly out of date; see Database Inventory Report**
- `migration_v2.sql` — v2 migration: payroll officer role, employee portal columns, 5 new tables — medium (153)
- `mock_content_seed.sql` — demo/mock content seed data — large (614)
- `phase1_permissions.sql` — permission matrix foundation — medium (279)
- `phase5_branding_theme.sql` — branding/signatures/stamps/email/theme schema additions — medium (154)
- `phase6_templates.sql` — 47 document template seed rows across 10 categories — large (1002)
- `phase7_test_data.sql` — realistic test data seed — large (661)
- `phase8_temp_employees.sql` — Temporary Employees module schema + permissions (contains role-name typo — see Finding NEW-1) — medium (109)
- `phase9_consultants.sql` — Consultants module schema + permissions (contains role-name typo — see Finding NEW-1) — medium (127)
- `install.php` — one-time DB installer script (only runs schema.sql — see Finding NEW-2) — medium (157)
- `fix_payroll_role.php` — one-time fix script — small (44)
- `fix_payslips_columns.php` — one-time fix script — small (55)
- `.htaccess` — deny direct web access to database directory

No `phase2`, `phase3`, or `phase4` files exist in the repository — naming jumps from `migration_v2.sql`/`phase1` straight to `phase5`.

---

## 11. Tests (`tests/`)
Playwright-based E2E/visual testing setup:
- `package.json` / `package-lock.json` / `node_modules/` — Playwright dependency install
- Test scripts: `add-employees.js`, `comprehensive-inspect.js`, `final-768-check.js`, `full-audit-2026.js`, `full-sidebar-verify.js`, `full-system-report.js`, `login-redirect.spec.js`, `measure-768.js`, `module-crawl.spec.js`, `phase4-doc-proof.js`, `phase5-workflow-proof.js`, `phase6-payroll-proof.js`, `sidebar-audit.js`
- Reports: `INSPECTION_REPORT.md`, `PHASE_0_REPORT.md`, `inspect-report.json`
- Screenshot output directories: `audit-2026/` (incl. `audit-report.json`), `content-shots/`, `logo-shots/`, `mobile-shots/`, `phase4-shots/`, `phase5-shots/`, `phase6-shots/`, `report-shots/`, `screenshots/`, `sidebar-shots/`

---

## 12. Other Top-Level Directories
- `docs/remediation/` — this remediation documentation tree (created in Phase 0)
- `logs/` — application log output directory; `.htaccess` present; **no `php_errors.log` exists yet** (confirmed empty as of baseline — see Deployment Inventory Report §12)
- `reports/generated/` — runtime-generated report exports/output directory

---

## Summary Counts

| Category | Subdirectories/Apps | Files | Approx. Lines |
|---|---|---|---|
| Admin modules | 24 | 89 | ~20,610 |
| Portals | 3 | 27 | ~4,050 |
| Core/config | — | 6 | ~1,510 |
| Auth | — | 4 | ~473 |
| Includes | — | 2 | ~632 |
| API | — | 4 | ~187 |
| Root entry | — | 2 (+2 docs) | ~549 |
| Database | — | 13 (10 SQL/PHP + .htaccess) | — |
| Tests | — | Playwright suite, 6+ screenshot galleries, ~13 scripts | — |

**Total application PHP surface (excluding tests/vendor): ~28,000 lines across ~132 files.**

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-11 | Initial baseline inventory compiled for Phase 0 | Remediation Program — Phase 0 |
