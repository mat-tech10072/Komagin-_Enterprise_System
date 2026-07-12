# Komagin HR — Phase 3 Schema Drift Matrix

**Document type:** Phase 3 Deliverable — Database/05 of 6 Phase 3 Database documents
**Status:** Verified by direct comparison of `git show HEAD:database/*.sql` (pre-Phase-3 tracked state) against `docs/remediation/Database/fingerprints/live_table_list.txt` (live state, captured Stage 3.0).
**Date compiled:** 2026-07-12

---

## 1. How to Read This Matrix

For every one of the 60 tables now in the canonical `database/schema.sql`, four questions are answered against the **pre-Phase-3** state of the repository (i.e. what `git show HEAD` returns — the state before any Phase 3 commit):

1. Was it in the old `schema.sql`?
2. Was it defined by some *other* tracked `phase*.sql`/`migration_v2.sql` file?
3. Did it exist live in the running `komagin_hr` database?
4. Is it in the new, canonical `database/schema.sql`?

## 2. Category A — Fully Tracked, No Drift (32 tables)

Defined in the old `schema.sql`, matched the live database exactly, unchanged in the new canonical file:

`archive_records`, `asset_assignments`, `attendance`, `audit_logs`, `company_assets`, `company_settings`, `correction_requests`, `departments`, `disciplinary_records`, `employee_documents`, `employee_pending_updates`, `employee_skills`, `employee_status_history`, `employee_update_links`, `employees`, `grievance_records`, `leave_applications`, `leave_balances`, `leave_types`, `notifications`, `onboarding_checklists`, `overtime_records`, `payslips`, `performance_reviews`, `permissions`, `positions`, `recruitment_applications`, `recruitment_vacancies`, `role_permissions`, `training_attendance`, `training_programs`, `users`

## 3. Category B — Tracked Elsewhere, Not in `schema.sql` (16 tables)

Live, and defined by a `CREATE TABLE` in a different tracked file — a documentation-location gap, not a reproducibility gap; `schema.sql` alone was incomplete but the repository as a whole was not:

| Table | Defined in |
|---|---|
| `payroll_deductions` | `migration_v2.sql` |
| `employee_savings` | `migration_v2.sql` |
| `payslip_items` | `migration_v2.sql` |
| `payroll_runs` | `migration_v2.sql` |
| `employee_requests` | `migration_v2.sql` |
| `company_letterheads` | `phase5_branding_theme.sql` |
| `company_signatures` | `phase5_branding_theme.sql` |
| `company_stamps` | `phase5_branding_theme.sql` |
| `company_watermarks` | `phase5_branding_theme.sql` |
| `email_logs` | `phase5_branding_theme.sql` |
| `temp_projects` | `phase8_temp_employees.sql` |
| `temp_sites` | `phase8_temp_employees.sql` |
| `temp_employees` | `phase8_temp_employees.sql` |
| `consultants` | `phase9_consultants.sql` |
| `consultant_attendance` | `phase9_consultants.sql` |
| `consultant_scopes` | `phase9_consultants.sql` |

**Resolution:** all 16 are now also present in the canonical `schema.sql`, consistent with the decision (§5 of `Database/06-phase3-canonical-database-model.md`) that `schema.sql` should be a single complete structural reference, not merely the first migration in a chain.

## 4. Category C — Untracked Anywhere (11 tables) — the real KOM-004 gap

Live, structurally real, and used by production code paths, but **no `CREATE TABLE` for these existed in any tracked file at all** prior to Phase 3. Their structure only ever reached the live database via an undocumented manual change (`ALTER`/`CREATE` run directly, e.g. via phpMyAdmin, with no corresponding commit):

| Table | Consuming code |
|---|---|
| `approval_workflows` | `config/ApprovalEngine.php`, `modules/approvals/*` |
| `approval_stages` | `config/ApprovalEngine.php`, `modules/approvals/*` |
| `doc_categories` | `modules/documents/templates.php`, `phase6_templates.sql`'s seed lookups |
| `doc_templates` | `modules/documents/*`, `config/DocumentEngine.php` |
| `doc_template_versions` | `modules/documents/templates.php` |
| `generated_documents` | `modules/documents/{generate,view_generated}.php` |
| `employee_dependents` | `modules/employees/view.php` |
| `employee_qualifications` | `modules/employees/view.php` |
| `employee_work_history` | `modules/employees/view.php` |
| `kiosk_sessions` | `modules/attendance/kiosk.php` |
| `kiosk_audit` | `modules/attendance/kiosk.php` |

**Resolution:** exact structure extracted verbatim from the live database (via `information_schema`) and added to the canonical `schema.sql`. See KOM-004, KOM-061 in the Master Remediation Register.

## 5. Category D — Referenced by Code, Never Existed Anywhere (1 table)

| Table | Consuming code | Resolution |
|---|---|---|
| `settings` | `modules/activity_log/download.php` (`SELECT value FROM settings WHERE key='company_name'`) | **Not a schema gap — a live code bug.** No table named `settings` ever existed, live or tracked. Fixed by correcting the query to read from `company_settings` (the table that actually holds this value), not by fabricating a `settings` table. See KOM-070. |

## 6. Category E — Brand New This Phase (1 table)

| Table | Purpose |
|---|---|
| `schema_migrations` | Tracks which install/migration steps have been applied (`migration_name`, `checksum`, `executed_at`, `execution_time_ms`, `status`, `error_summary`). Populated by both `database/install.php` (fresh installs) and available for future migration tooling. Did not exist before Phase 3, live or tracked — this is the one genuine schema addition Phase 3 makes, everything else is documentation of what already existed. |

## 7. Totals

| Category | Count | Action Taken |
|---|---|---|
| A — No drift | 32 | None needed |
| B — Tracked elsewhere | 16 | Consolidated into canonical `schema.sql` |
| C — Untracked anywhere | 11 | Extracted from live DB, added to canonical `schema.sql` |
| D — Phantom (code bug) | 1 | Code fixed, no table created |
| E — New this phase | 1 | `schema_migrations` created |
| **Canonical `schema.sql` total** | **60** | (32 + 16 + 11 + 1) |

This matrix is the evidence base for the canonical model described in `Database/06-phase3-canonical-database-model.md` and the byte-for-byte fingerprint diff in `Database/fingerprints/phase3-pre-change-schema-fingerprint.txt` vs `phase3-post-change-schema-fingerprint.txt`, which confirms zero unintended structural change to any of the 59 pre-existing tables.
