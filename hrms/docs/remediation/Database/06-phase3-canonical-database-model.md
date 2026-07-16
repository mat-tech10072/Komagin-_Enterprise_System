# Komagin HR — Phase 3 Canonical Database Model

**Document type:** Phase 3 Deliverable — Database/06 of 6 Phase 3 Database documents
**Status:** Describes `database/schema.sql` as rewritten in Phase 3 — the single authoritative structural definition of the Komagin HR database.
**Date compiled:** 2026-07-12

---

## 1. What Changed

`database/schema.sql` was rewritten from 32 `CREATE TABLE` statements to 60. It is now a **pure-structure, complete, single-pass-importable** definition of every table the application depends on, plus one new tracking table. See `Database/05-phase3-schema-drift-matrix.md` for exactly which 28 tables were added and why.

## 2. Design Decisions

**Structure only, no data.** `schema.sql` contains zero `INSERT` statements — no default admin account, no permission grants, no seed rows. Baseline data lives in `database/seeds/`, run afterward in a defined order. A schema file should not be the thing that decides what a fresh install's default password is, and keeping structure and data separate makes each independently reviewable and independently safe to re-run.

**`CREATE TABLE IF NOT EXISTS` throughout.** Every statement is idempotent. Running `schema.sql` a second time against an already-populated database is a safe no-op, not an error — this was a deliberate choice to make the file forgiving of operator mistakes (e.g. accidentally re-running it during a deployment) without requiring a wrapper script to guard against it.

**Verified topological ordering, not assumed.** Tables are ordered so that every table a foreign key points to is defined before the table that references it. This was **computed**, not hand-sorted: extracted from the live database's `information_schema.KEY_COLUMN_USAGE` foreign-key graph and confirmed cycle-free (see `Database/07-phase3-migration-dependency-report.md`). Importing the file in one pass with `FOREIGN_KEY_CHECKS` at its default value (`1`/on) succeeds — no `SET FOREIGN_KEY_CHECKS=0` workaround needed or used anywhere in Phase 3's SQL.

**One file, two usage paths.** The same `schema.sql` serves both scenarios described in the Phase 3 charter:
- **Fresh install:** run `schema.sql`, then `database/seeds/` in filename order, then the permission/template/module `phase*.sql` files — see §3.
- **Existing (pre-Phase-3) installation:** do **not** re-run `schema.sql` as an upgrade path on its own initiative — the correct upgrade path is the purpose-built `database/phase11_schema_reconciliation.sql`, which is idempotent and additive-only against a database that may already hold years of production data (see `Database/07-phase3-migration-dependency-report.md` §4 and `Testing/13-phase3-upgrade-migration-test-report.md`).

## 3. Fresh-Install Sequence (as implemented in `database/install.php`)

```
1. schema.sql                          — core structure (60 tables)
2. seeds/001_baseline_admin.sql        — default super_admin (must_change_password=1)
3. seeds/002_doc_categories.sql        — 10 document categories
4. phase1_permissions.sql              — 79-permission matrix + role grants
5. phase5_branding_theme.sql           — branding/email permission grants
6. phase6_templates.sql                — 47 document templates
7. phase8_temp_employees.sql           — temp-employee module permissions + sample data
8. phase9_consultants.sql              — consultants module permissions + sample data
9. phase10_authorization_framework.sql — activity log / approvals permissions
[optional] phase7_test_data.sql        — demo data, off by default
```

`migration_v2.sql` and phase10's own `CREATE`/`ALTER` statements are now redundant on a fresh install (their structural content is already in `schema.sql`) and are deliberately skipped by `install.php`; phase10's **seed** statements (permission grants) are still required and still run.

## 4. Existing-Database Upgrade Path

`database/phase11_schema_reconciliation.sql` — see `Database/07-phase3-migration-dependency-report.md` for full contents and rationale. Summary: `CREATE TABLE IF NOT EXISTS` for the 11 genuinely-undocumented tables, `ADD COLUMN IF NOT EXISTS` / safe `MODIFY COLUMN` for columns known to exist live but missing from older tracked files, a `role_permissions` typo-fix `UPDATE` (idempotent no-op if already applied, as it is on the current live database via Phase 1), and creation of `schema_migrations` itself.

## 5. What Was Deliberately NOT Done

Per the Phase 3 charter's explicit scope boundaries:
- **No table, column, or relationship was redesigned.** Every structural element in the canonical `schema.sql` is a verbatim extraction of what the live database already had — Phase 3 is a reproducibility and documentation exercise, not a data-modeling exercise.
- **No live production schema change was made.** `phase11_schema_reconciliation.sql` was built and thoroughly tested against a real clone of the production database (Stage 3.9), but was **not applied to the live `komagin_hr` database** during this phase — see `Phase3/00-phase3-completion-report.md` §6 for the explicit reasoning and the resulting fingerprint comparison showing the live database is unchanged.
- **`FOREIGN_KEY_CHECKS` was never disabled** as a substitute for correct ordering, in any script, at any point.
