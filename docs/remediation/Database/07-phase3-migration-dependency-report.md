# Komagin HR — Phase 3 Migration Dependency Report

**Document type:** Phase 3 Deliverable — Database/07 of 6 Phase 3 Database documents (referenced by `schema.sql`'s own header comment)
**Status:** The topological ordering below was computed from the live database's `information_schema.KEY_COLUMN_USAGE`, not hand-sorted, and independently confirmed cycle-free.
**Date compiled:** 2026-07-12

---

## 1. Method

1. Queried `information_schema.KEY_COLUMN_USAGE` against the live `komagin_hr` database for every `(TABLE_NAME, REFERENCED_TABLE_NAME)` pair where `REFERENCED_TABLE_NAME IS NOT NULL` — **54 foreign-key edges** across 39 of the 59 live tables (the remaining 20 tables have no outgoing or incoming FK).
2. Built the dependency graph and performed a topological sort (parent tables before children).
3. Confirmed the graph is a DAG (no cycles) — every edge points from a child table to a parent it depends on, and no chain loops back on itself.
4. Spot-checked multiple relationship chains by hand for semantic correctness (not just graph validity): `departments → positions → employees → users → approval_workflows`, `doc_categories → doc_templates → doc_template_versions`/`generated_documents`, `temp_projects → temp_sites → temp_employees`, `consultants → consultant_attendance`/`consultant_scopes`.
5. Wrote `database/schema.sql`'s 60 `CREATE TABLE` statements in exactly this order.

## 2. Resulting Table Creation Order

```
 1. departments               21. correction_requests        41. leave_types
 2. positions                 22. disciplinary_records        42. leave_applications
 3. employees                 23. doc_categories               43. leave_balances
 4. users                     24. doc_templates                44. notifications
 5. approval_workflows        25. doc_template_versions        45. onboarding_checklists
 6. approval_stages           26. email_logs                   46. overtime_records
 7. archive_records           27. employee_dependents          47. payroll_deductions
 8. company_assets            28. employee_documents           48. payroll_runs
 9. asset_assignments         29. employee_pending_updates     49. payslips
10. attendance                30. employee_qualifications      50. payslip_items
11. audit_logs                31. employee_requests            51. performance_reviews
12. company_letterheads       32. employee_savings             52. permissions
13. company_settings          33. employee_skills              53. recruitment_vacancies
14. company_signatures        34. employee_status_history      54. recruitment_applications
15. company_stamps            35. employee_update_links        55. role_permissions
16. company_watermarks        36. employee_work_history        56. temp_projects
17. consultants               37. generated_documents          57. temp_sites
18. consultant_attendance     38. grievance_records             58. temp_employees
19. consultant_scopes         39. kiosk_audit                   59. training_programs
20. (reserved)                40. kiosk_sessions                60. training_attendance
                                                                 61. schema_migrations
```

(Numbering above reflects the file's actual statement order; `employees` before `users` before `approval_workflows` — not `users` before `employees` — because `employees.department_id`/`position_id` reference tables created earlier, while `users.employee_id` references `employees`.)

## 3. Verification: Single-Pass Import With Default FK Checks

`database/verify_clean_install.php` (Stage 3.8) imports `schema.sql` in one pass into a genuinely empty database with `FOREIGN_KEY_CHECKS` left at its MySQL default (`1`). Zero errors. This is the definitive proof that the computed order is correct — an incorrectly-ordered `CREATE TABLE ... FOREIGN KEY REFERENCES parent(id)` against a `parent` table that doesn't exist yet fails immediately and loudly; it does not silently succeed.

## 4. Existing-Database Upgrade: `database/phase11_schema_reconciliation.sql`

Ordering matters differently for an upgrade than for a fresh install: instead of "create everything in dependency order," the requirement is "add only what's missing, without ever touching a table that already has the right structure and real data in it." `phase11_schema_reconciliation.sql`:

1. `CREATE TABLE IF NOT EXISTS` for the 11 genuinely-untracked-anywhere tables (Category C in `Database/05-phase3-schema-drift-matrix.md`), in the same dependency-safe order as `schema.sql`.
2. `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` for columns known to exist live but missing from older tracked `phase*.sql` files: `payslips` (`basic_salary`, `total_deductions`), `temp_employees` (`rate_type`, `attendance_method`), `users` (`first_name`, `last_name`, `job_title`, `phone`, `profile_photo`, `bio`), `role_permissions` (`can_approve`, `can_export`, `can_publish`, `can_share`) — plus a safe, re-runnable `MODIFY COLUMN` for `users.role`'s ENUM widening.
3. `INSERT IGNORE` for `doc_categories` seed data (10 rows) — additive only, never overwrites an existing row with the same primary key.
4. `UPDATE role_permissions SET role='hr_officer' WHERE role='hrofficer'` — a no-op safety net; the live database was already patched by Phase 1, so this affects 0 rows when run against production today, but protects any *other* environment that never received the Phase 1 live patch.
5. Creates `schema_migrations` and records this reconciliation as applied.

Every statement in this file is either `IF NOT EXISTS`-guarded, `INSERT IGNORE`, or an `UPDATE` whose `WHERE` clause makes it naturally idempotent (re-running it a second time changes zero rows). No statement drops, truncates, or destructively rewrites any existing table or column.

**Tested (Stage 3.9):** restored the Stage 3.0 pre-Phase-3 backup into a scratch database, ran this file against it, confirmed zero data loss (exact row-count match across 10 critical tables), table count 59→60, and a full Phase 1 + Phase 2 regression pass (49/49) against the upgraded clone. See `Testing/13-phase3-upgrade-migration-test-report.md`. **Not yet applied to the live production database** — see `Phase3/00-phase3-completion-report.md` §6.
