# Komagin HR — Database Directory

This directory contains the complete database structure, seed data, and migration history for the Komagin HR Management System.

## Fresh Install

Run `database/install.php` in a browser (or via `php -S` / the app's own web server) and follow the form. It runs the complete, correct install sequence in one pass — structure, permissions, and the document template library — and stops immediately with a clear error if any step fails.

The exact sequence it runs (`install.php`'s `INSTALL_SEQUENCE` constant), in order:

| # | File | What it adds |
|---|---|---|
| 1 | `schema.sql` | Core table structure — every table the application uses, kept continuously in sync as the canonical current structure (see `docs/remediation/Database/06-phase3-canonical-database-model.md`) |
| 2 | `seeds/001_baseline_admin.sql` | The default `superadmin` account (forced to change its password on first login) |
| 3 | `seeds/002_doc_categories.sql` | The 10 document template categories `phase6_templates.sql` requires to exist first |
| 4 | `seeds/003_departments_positions.sql` | Starter departments & positions |
| 5 | `phase1_permissions.sql` | The core permission matrix |
| 6 | `phase5_branding_theme.sql` | Branding & email settings permissions |
| 7 | `phase6_templates.sql` | The 47-template document library |
| 8 | `phase8_temp_employees.sql` | Temporary employees module & its permissions |
| 9 | `phase9_consultants.sql` | Consultants module & its permissions |
| 10 | `phase10_authorization_framework.sql` | Activity Log & Approvals permissions |
| 11 | `phase13_workflow_completeness_automation.sql` | The default `work_calendar_settings` row every "Absent Today" calculation depends on, plus the scheduler/password-reset/notification-dedup tables (all also already in `schema.sql`'s structure — this file's CREATE/ALTER statements are redundant-but-harmless here; it's included specifically for that one seed row) |

Every step uses `IF NOT EXISTS`/`INSERT IGNORE`-style idempotent statements, so re-running the installer after fixing whatever caused a failure is always safe — it picks up wherever it stopped rather than duplicating what already succeeded.

**`phase7_test_data.sql`** (demo/fixture data) is deliberately **not** run by default — it's an opt-in checkbox on the install form, and must never be loaded on a production install.

**Why `phase11_schema_reconciliation.sql` and `phase12_workflow_integrity_fixes.sql` are correctly excluded from a fresh install**: both files say so explicitly in their own header comments. They exist solely to bring an *existing* database that predates Phase 3/Phase 4 up to parity with the current `schema.sql` (11 tables and several columns that were once added to the live database by undocumented manual changes, before this remediation program tracked everything). Everything they would otherwise add is already present natively in `schema.sql` for a fresh install — running them there would be redundant, not wrong, but unnecessary.

### Verifying a fresh install works

```bash
php database/verify_clean_install.php
```

CLI-runnable, no browser needed. Creates a genuinely empty scratch database (`komagin_hr_phase3_clean_test`), runs the exact same sequence `install.php` uses, verifies table count/structure/seed-data completeness with 30 assertions, and drops the scratch database when finished (pass `--keep` to leave it for manual inspection). Safe to re-run any time to confirm the repository can still rebuild a working system from nothing — this is the correct way to validate any future change to the install sequence or a migration file, rather than trusting the sequence by inspection alone.

## Upgrading an Existing Installation

If your database predates this program's Phase 3 (i.e., was set up before `schema.sql` became the single source of truth), run, in order:

1. `phase11_schema_reconciliation.sql` — brings 11 undocumented-but-live tables and several missing columns up to the tracked, canonical structure. Idempotent; safe to re-run.
2. `phase12_workflow_integrity_fixes.sql` — adds `employees.personal_email`, missing on any database older than Phase 4. Idempotent; safe to re-run.

Any database already at or past those phases (i.e., anything created via `install.php`'s current sequence, or already running this application successfully) does **not** need either file — they will simply no-op every statement.

## Other Files in This Directory

| File | Purpose |
|---|---|
| `schema.sql` | Canonical current table structure. Structure only — no seed data. |
| `seeds/` | The 3 files providing minimum-viable seed data a fresh install needs (admin account, doc categories, starter departments/positions). |
| `phase1_permissions.sql` through `phase13_workflow_completeness_automation.sql` | Numbered migration files, one per remediation phase that introduced new schema/permissions/seed data. Each is self-contained and idempotent — see each file's own header comment for what it does and why. |
| `phase7_test_data.sql` | Demo/fixture data for local development only. Never run this against a production database. |
| `migration_v2.sql` | Superseded — this was the manually-run-via-phpMyAdmin migration file before Phase 3's `install.php` rewrite. Kept for historical reference only; not part of any current install/upgrade path. |
| `mock_content_seed.sql` | Additional demo content, not part of the install sequence. |
| `install.php` | The web-based fresh-install tool described above. **Delete or restrict access to this file after installation** — it can create/overwrite the application's database connection settings in `config/config.php`. |
| `verify_clean_install.php` | The CLI fresh-install verification tool described above. |
| `sql_split.php` | Helper used by `install.php`/`verify_clean_install.php` to correctly split multi-statement `.sql` files (handles `DELIMITER`-free files with semicolons inside string literals/comments). |
| `fix_payroll_role.php`, `fix_payslips_columns.php` | One-off historical data-patching scripts for specific issues found and fixed earlier in this program. Already applied to the live database; kept for historical reference, not part of any install/upgrade path. |
| `backups/` | Not version-controlled (gitignored). Location for manual/automated database and file backups — see `docs/remediation/Phase6/` for the backup procedure. |

## Production Deployment Notes

- **Delete or restrict access to `database/install.php` after installation.** It accepts arbitrary MySQL connection details via POST and writes them into `config/config.php` — a real risk if left reachable on a live server.
- **Change the default `superadmin` / `Admin@123` credentials immediately.** The installer already forces a password change on first login, but the account must actually be logged into and changed before the system is exposed to real users.
- **Never run `phase7_test_data.sql` (the "Load Demo Data" checkbox) against a production database.**
- See `docs/remediation/Deployment/` for the full deployment guide, and `docs/remediation/Phase6/` for backup/disaster-recovery procedures.
