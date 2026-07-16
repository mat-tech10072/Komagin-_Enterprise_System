# Komagin HR — Phase 6 Stage 6.3: Database Certification Report

**Document type:** Phase 6 Deliverable — Stage 6.3 (charter §7)
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13

---

## 1. Fresh Install

**Already certified in Stage 6.1** via `database/verify_clean_install.php` against a real, freshly-created scratch database: **30/30 assertions passed**, including the `INSTALL_SEQUENCE` fix that closed a genuine gap (the default `work_calendar_settings` row was missing before that fix). See `Phase6/03-production-blocker-fixes-report.md` §4 for the full run. Not repeated here.

## 2. Upgrade Install

**Already certified in Phase 3** (`Testing/13-phase3-upgrade-migration-test-report.md`, Stage 3.9) via a genuine restore of a real pre-Phase-3 production backup into a clone, running `phase11_schema_reconciliation.sql` against it, and confirming zero data loss across 10 critical tables plus a full 49/49 Phase 1+2 regression pass against the upgraded clone. That result stands unchanged — re-read in full as part of this stage's baseline review, not superseded by anything in Phase 4–6.

**New this stage — idempotency re-verification against real, current live data**: re-ran `phase11_schema_reconciliation.sql`, `phase12_workflow_integrity_fixes.sql`, and `phase13_workflow_completeness_automation.sql`, in order, directly against the live `komagin_hr` database (which already has all three applied). All three completed with exit code 0. Row counts for `employees` and `work_calendar_settings` were identical before and after — confirming these files are genuinely safe to re-run against an already-current database, not just safe in theory per their own header comments.

## 3. Backup & Restore Drill

1. Captured baseline row counts (`employees`: 13, `audit_logs`: 967).
2. Took a full `mysqldump` backup.
3. Inserted a disposable test employee (`P6TEST-BACKUP`) — simulating data added *after* the backup point.
4. Restored the backup into a fresh scratch database (`komagin_hr_restore_test`).
5. **Confirmed the restored copy has 13 employees, not 14** — proving the backup genuinely captured the state at its own point in time, not a live/moving snapshot.
6. Confirmed table counts match between source and restore (66 each).
7. Cleaned up: dropped the scratch database, removed the disposable test employee from the live database, removed the local backup file.

**Result: PASS.** The standard `mysqldump`/restore cycle works correctly and is the mechanism documented in the Backup & Disaster Recovery Guide (Stage 6.6).

## 4. Rollback

This codebase's migration philosophy (established since Phase 3) is **additive-only and idempotent** — every migration file uses `CREATE TABLE IF NOT EXISTS`, `ADD COLUMN IF NOT EXISTS`, or `INSERT IGNORE`, and none contains a corresponding "down" migration. This is a deliberate design choice, not a gap: **restoring from the most recent pre-change backup is this application's rollback mechanism**, confirmed working end-to-end in §3. There is no additional "migration rollback" mechanism to certify separately — attempting to hand-write reverse `DROP`/`ALTER` statements for a specific migration would be genuinely riskier than restoring a known-good backup, and is explicitly discouraged in the Deployment Guide's rollback section.

## 5. Data Integrity — Orphan & Duplicate Detection

Re-verified against the current live database (not re-trusting Phase 3/4's earlier audits, consistent with this program's standing practice):

| Check | Result |
|---|---|
| `employees.department_id` orphans | 0 |
| `employees.position_id` orphans | 0 |
| `attendance.employee_id` orphans | 0 |
| `leave_applications.employee_id` orphans | 0 |
| `payslips.employee_id` orphans | 0 |
| `users.employee_id` orphans | 0 |
| `temp_attendance.employee_id` orphans (Phase 5's newest FK relationship) | 0 |
| `password_reset_tokens.user_id` orphans (Phase 5's newest FK relationship) | 0 |
| Duplicate `employees.employee_number` | 0 |
| Duplicate `employees.national_id` (non-null) | 0 |

**Result: PASS.** No orphaned foreign key references, no duplicate identifiers, across every relationship checked including the two newest ones Phase 5 introduced.

## 6. Connection Failure Handling

`config/database.php`'s `Database::getConnection()` wraps the `PDO` constructor in a `try`/`catch`. Live-verified by forcing a real connection failure (bad credentials via environment variable override):

- **Client-facing response**: `{"error":"Database connection failed. Please contact system administrator."}` — generic, no DSN, no host, no credentials, no stack trace.
- **Server-side log**: the full `PDOException` message (`SQLSTATE[HY000] [1045] Access denied for user '...'@'localhost' (using password: YES)`) is captured via `error_log()`, which in production mode (`APP_ENV=production`) routes to the access-protected `logs/php_errors.log`, never to the client.

**Result: PASS.** Diagnostic detail is available to an administrator via the protected log; nothing sensitive reaches an end user or a potential attacker probing for information.

## 7. Query Plans / Index Usage

Spot-checked `EXPLAIN` output for the three heaviest query patterns in the application (dashboard attendance lookup, the Reports module's attendance join, and an audit-log export filter):

| Query | Result |
|---|---|
| Dashboard: today's attendance count | Uses `idx_att_date` (`type=ref`) — efficient. |
| Reports: employee × attendance join for a month | Employees table: `type=index` (full index scan — acceptable at the current 13-row scale; worth re-checking under Stage 6.4's larger synthetic dataset). Attendance join: uses `attendance_unique` (`type=ref`) — efficient. |
| Audit log export filter (`user_id` + date range, sorted by `created_at`) | Uses `idx_audit_user` correctly for filtering, but shows `Using filesort` for the `ORDER BY created_at` — no index covers the sort order. At the current 414-row result set this is sub-millisecond and not a real bottleneck. **Documented as a future scaling consideration**, not fixed now, per the charter's "optimize only where evidence exists" instruction: `audit_logs` is an append-only, ever-growing table (unlike `attendance`, which is naturally bounded), so if per-user exports become slow as the table grows into the hundreds of thousands of rows, a composite index on `(user_id, created_at)` is the identified fix — not applied speculatively today. |

**Result: PASS, with one documented future consideration (not a current defect).**

## 8. `schema_migrations` Table — Dev-Environment Note

The live local `komagin_hr` database (evolved organically across Phases 0–5 rather than built via `database/install.php`) does not have the `schema_migrations` tracking table `schema.sql`/`install.php` define — it's a pure bookkeeping table `install.php` populates on completion, with zero runtime dependency anywhere in the application. Confirmed via direct query: nothing in the codebase reads from `schema_migrations`. Not a defect; a real production deployment built via `install.php` (per the Stage 6.2 guide) will have this table populated correctly from the start. Noted here for completeness, not actioned.

## 9. Conclusion

**Stage 6.3 PASSED in full.** Fresh install, upgrade install, backup/restore, rollback strategy, data integrity, connection-failure handling, and query performance were all certified — several against genuinely live data and real backup/restore cycles, not simulated. No production blockers found in this stage; one minor future-scaling observation documented for monitoring, not action.
