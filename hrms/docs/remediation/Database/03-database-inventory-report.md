# Komagin HR — Database Inventory Report

**Document type:** Phase 0 Baseline Deliverable #3 of 9
**Status:** Documentation only — static inspection of `.sql` files and PHP query usage. No live database was queried, no SQL was changed, no migration was created.
**Date compiled:** 2026-07-11
**Baseline tag:** `v1.0-enterprise-baseline`

---

## 1. SQL / Install Assets in `database/`

| File | Lines | Purpose |
|---|---|---|
| `schema.sql` | 775 | Base schema definition (31 `CREATE TABLE`) — the original/out-of-date foundation |
| `migration_v2.sql` | 153 | Migration — payroll officer role, employee portal columns, 5 new tables |
| `phase1_permissions.sql` | 279 | Permission matrix foundation — extends `users.role` ENUM, (re)builds `permissions`/`role_permissions` |
| `phase5_branding_theme.sql` | 154 | Migration — branding/e-mail feature: 5 new tables, ALTERs `company_settings` and `doc_templates` |
| `phase6_templates.sql` | 1002 | Seed data only — 47 document template `INSERT`s (zero CREATE/ALTER statements) |
| `phase7_test_data.sql` | 661 | Seed/test data — bulk demo data via INSERTs + one self-cleaning stored procedure |
| `phase8_temp_employees.sql` | 109 | Migration + seed — Temporary Employees module: 3 new tables, permissions, seed rows |
| `phase9_consultants.sql` | 127 | Migration + seed — Consultants module: 3 new tables, permissions, seed rows |
| `mock_content_seed.sql` | 614 | Seed data — additional demo/mock content + one self-cleaning stored procedure |
| `install.php` | 157 | One-off web installer — **executes `schema.sql` only** (see §6) |
| `fix_payroll_role.php` | 44 | One-off fix — duplicates the `users.role` ENUM ALTER from migration_v2.sql |
| `fix_payslips_columns.php` | 55 | One-off fix — duplicates the `payslips` ALTER COLUMN set from migration_v2.sql |

No `phase2`, `phase3`, or `phase4` files exist — naming jumps from `migration_v2.sql`/`phase1` straight to `phase5`.

---

## 2. Tables Defined in `schema.sql` — Master List (31 tables)

company_settings, departments, positions, employees, employee_status_history, users, permissions, role_permissions, attendance, correction_requests, overtime_records, leave_types, leave_balances, leave_applications, recruitment_vacancies, recruitment_applications, onboarding_checklists, employee_documents, employee_update_links, employee_pending_updates, performance_reviews, training_programs, training_attendance, disciplinary_records, grievance_records, company_assets, asset_assignments, archive_records, notifications, audit_logs, employee_skills, payslips (base — later extended twice).

All 31 tables use `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY`; no explicit `ENGINE=` clause (relies on server default, InnoDB on any modern MySQL/MariaDB).

---

## 3. Tables/Alterations Outside `schema.sql`

**`migration_v2.sql`:** ALTERs `users` (role ENUM +payroll_officer), `employees` (+5 portal columns), `payslips` (+12 columns). **New tables:** `payroll_deductions`, `employee_savings`, `payslip_items`, `payroll_runs`, `employee_requests`.

**`phase1_permissions.sql`:** ALTERs `users` (role ENUM +4 more roles), `role_permissions` (+4 action columns). No new tables — reseeds `permissions`/`role_permissions` content.

**`phase5_branding_theme.sql`:** ALTERs `company_settings` (+7 columns), **and `doc_templates` (+13 columns) — a table with no CREATE TABLE anywhere in the repo, see §6.** **New tables:** `company_letterheads`, `company_signatures`, `company_stamps`, `company_watermarks`, `email_logs`.

**`phase6_templates.sql`:** pure seed data (INSERT only).

**`phase7_test_data.sql` / `mock_content_seed.sql`:** pure seed/demo data + one temporary, properly-cleaned-up stored procedure each.

**`phase8_temp_employees.sql`:** **New tables:** `temp_projects`, `temp_sites`, `temp_employees` (explicit `ENGINE=InnoDB`).

**`phase9_consultants.sql`:** **New tables:** `consultants`, `consultant_attendance`, `consultant_scopes` (explicit `ENGINE=InnoDB`).

**Drift list — 16 tables created entirely outside `schema.sql`:** payroll_deductions, employee_savings, payslip_items, payroll_runs, employee_requests, company_letterheads, company_signatures, company_stamps, company_watermarks, email_logs, temp_projects, temp_sites, temp_employees, consultants, consultant_attendance, consultant_scopes. (`payslips` is not new but is heavily extended via ALTER across two separate files.)

---

## 4. Tables Referenced in Application Code (Full Distinct List)

company_settings, departments, positions, employees, employee_status_history, users, permissions, role_permissions, attendance, correction_requests, overtime_records, leave_types, leave_balances, leave_applications, recruitment_vacancies, recruitment_applications, onboarding_checklists, employee_documents, employee_update_links, employee_pending_updates, performance_reviews, training_programs, training_attendance, disciplinary_records, grievance_records, company_assets, asset_assignments, archive_records, notifications, audit_logs, employee_skills, payslips, payroll_deductions, employee_savings, payslip_items, payroll_runs, employee_requests, company_letterheads, company_signatures, company_stamps, company_watermarks, email_logs, temp_projects, temp_sites, temp_employees, consultants, consultant_attendance, consultant_scopes, **doc_templates, doc_categories, doc_template_versions, generated_documents, kiosk_sessions, kiosk_audit, employee_dependents, employee_qualifications, employee_work_history, approval_workflows, approval_stages, settings**.

---

## 5. Diff — Code vs. SQL Definitions (Schema Drift)

### Tables referenced in code, defined in NO `.sql` file anywhere in the repository — 12 tables

| Table | Used by | Note |
|---|---|---|
| `doc_templates` | `modules/documents/{templates,generate,view_generated}.php`, `api/search.php`, `modules/reports/executive.php` | Only `ALTER` (phase5) and `INSERT` (phase6) exist — no `CREATE TABLE` |
| `doc_categories` | same set | Only `SELECT`ed/`JOIN`ed — no `CREATE TABLE` |
| `generated_documents` | `modules/documents/{generate,view_generated}.php` | Only `INSERT`ed/`SELECT`ed — no `CREATE TABLE` |
| `doc_template_versions` | `modules/documents/templates.php` | Only `INSERT`ed (versioning) — no `CREATE TABLE` |
| `kiosk_sessions` | `modules/attendance/{kiosk,kiosk_manage}.php` | Fully undefined |
| `kiosk_audit` | `modules/attendance/{kiosk,kiosk_manage}.php` | Fully undefined |
| `employee_dependents` | `modules/employees/{view,dependent_save}.php` | Fully undefined |
| `employee_qualifications` | `modules/employees/{view,qualification_save}.php` | Fully undefined |
| `employee_work_history` | `modules/employees/{view,work_history_save}.php` | Fully undefined |
| `approval_workflows` | `config/ApprovalEngine.php`, `includes/header.php` | Fully undefined |
| `approval_stages` | `config/ApprovalEngine.php` | Fully undefined |
| `settings` | `modules/activity_log/download.php` | Generic key/value table, distinct from `company_settings`; fully undefined |

This baseline pass **triples the previously known scope** of this issue: the prior security audit found 4 missing document tables; this inventory confirms those 4 and finds 8 more (kiosk ×2, employee-profile-tab ×3, approvals ×2, generic settings ×1). See Master Remediation Register, Finding **KOM-006** (updated scope).

### Tables defined in SQL but never referenced in application code

- `employee_skills` (schema.sql) — has a `CREATE TABLE`, is never seeded, and no module queries it. The employee profile view uses `employee_qualifications`/`employee_work_history` instead, suggesting `employee_skills` is a superseded/renamed precursor table left behind.

---

## 6. Migration Order & Dependency Integrity

**Apparent intended order** (by filename): `schema.sql` → `migration_v2.sql` → `phase1_permissions.sql` → `phase5_branding_theme.sql` → `phase6_templates.sql` → `phase7_test_data.sql` → `phase8_temp_employees.sql` → `phase9_consultants.sql` (with `mock_content_seed.sql` layered in ad hoc). Phases 2–4 do not exist in the repository.

**Confirmed broken dependency:** `phase5_branding_theme.sql` runs `ALTER TABLE doc_templates ADD COLUMN ...`, which presupposes `doc_templates` already exists. No file — earlier or later — contains `CREATE TABLE doc_templates`. `phase6_templates.sql` (phase 6, after phase 5) both `SELECT`s from and expects populated rows in `doc_categories`, which likewise has no `CREATE TABLE` anywhere. **The tracked migration chain cannot be run end-to-end against an empty database** — phase5 and phase6 fail outright, meaning `doc_templates`/`doc_categories` were created by an untracked, never-committed script or manual DDL statement.

**Role-name drift (new finding, this baseline pass):** `phase1_permissions.sql` and `schema.sql`'s `users.role` ENUM consistently use `hr_officer` (underscore). `phase8_temp_employees.sql` and `phase9_consultants.sql` grant permissions to `'hrofficer'` (no underscore) — a typo. Real `hr_officer` users receive **zero** permission rows for the Temporary Employees and Consultants modules, since `_loadRolePermissions()` queries `role_permissions WHERE role = ?` against the literal session role string, which never matches. See Master Remediation Register, Finding **KOM-056**.

**`install.php` gap:** the installer executes `schema.sql` only; it displays a manual on-screen instruction to also run `migration_v2.sql`, but never mentions phase1/phase5/phase6/phase7/phase8/phase9 at all. A fresh install following only the installer's own instructions ends up missing all phase-1-through-9 tables, permissions, and the entire 47-template library. See Master Remediation Register, Finding **KOM-057**.

---

## 7. Indexes, Foreign Keys, Constraints — Overall Pattern

- **Foreign keys:** used consistently in `schema.sql` (~31 occurrences across ~20 of 31 tables) and the newer migration files (migration_v2.sql: 4, phase8: 3, phase9: 2), almost always `FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE`, occasionally `ON DELETE SET NULL` for optional relations. **No foreign keys at all** in `phase1_permissions.sql` or `phase5_branding_theme.sql` — the branding tables (letterheads, signatures, stamps, watermarks, email_logs) have unconstrained `created_by`/`employee_id`/`reference_id` columns.
- **Unique keys:** `dept_name_unique`, `role_permission_unique`, `attendance_unique` (employee_id+date), `leave_balance_unique` (employee_id+type+year), `training_emp_unique`, `payslip_unique` (employee_id+month+year) in schema.sql; `payroll_run_unique` (month+year) in migration_v2.sql; `uk_consultant_date` in phase9. Plus plain-unique columns: `employees.employee_number`, `users.username`/`email`, `permissions.slug`, `temp_projects.code`, `temp_employees.employee_number`, `consultants.consultant_number`.
- **Secondary indexes:** sparse — roughly 10 explicit `INDEX` clauses total, concentrated in `schema.sql` and phase5's `email_logs`. Newer high-traffic tables (`payroll_runs`, `employee_requests`, `temp_employees`, `consultants`, `consultant_attendance`) rely solely on primary/unique keys — no secondary indexes on frequently-filtered columns like `status` or `employee_id`.
- **Engine/charset declarations:** only `phase8_temp_employees.sql` and `phase9_consultants.sql` explicitly declare `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` per table. All earlier files omit `ENGINE=` and rely on server defaults.

**Overall pattern:** most tables use InnoDB (implicit or explicit) with FK constraints cascading from `employee_id` to `employees`, but FK usage is inconsistent — the permissions and branding/email subsystems have none — and secondary indexing coverage thins out noticeably in every module added after the original `schema.sql`.

---

## 8. Views, Triggers, Stored Procedures

- **Views:** none found anywhere in `database/*.sql`.
- **Triggers:** none found anywhere.
- **Stored procedures:** exactly two, both one-off seed-data generators, both properly self-cleaning (`DROP PROCEDURE IF EXISTS ...; CALL ...; DROP PROCEDURE IF EXISTS ...`) rather than left permanently installed: `seed_attendance_june()` (`mock_content_seed.sql`) and `seed_attendance()` (`phase7_test_data.sql`). No permanent stored routines exist in this codebase.

---

## Summary — Schema Health at Baseline

| Metric | Count |
|---|---|
| Tables defined in `schema.sql` | 31 |
| Additional tables defined outside `schema.sql` (drift, but at least tracked somewhere) | 16 |
| Tables used by live code with **no CREATE TABLE anywhere in the repo** | 12 |
| Confirmed broken forward-reference in the tracked migration chain | 1 (`doc_templates`/`doc_categories`, blocks phase5+phase6 on a fresh DB) |
| Role-name seed typo affecting live authorization | 1 (`hrofficer` vs `hr_officer`, 2 modules affected) |
| Tables defined but apparently unused/dead | 1 (`employee_skills`) |

**This is the single most consequential fact in the entire baseline for planning remediation sequencing:** no committed set of `.sql` files, run in any order, currently reconstructs a working copy of this database from empty. Any disaster-recovery, staging-environment, or CI-database-provisioning plan must account for this before it can be relied upon. See Master Remediation Register, Finding **KOM-006**, Target Phase 1.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-11 | Initial baseline database inventory compiled for Phase 0 | Remediation Program — Phase 0 |
