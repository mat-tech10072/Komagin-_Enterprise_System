# Komagin HR — Disaster Recovery Guide

**Document type:** Phase 6 Deliverable — Stage 6.6 (charter §10)
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13/14

---

## 1. What Gets Backed Up, and Where It Lives

| Data | Where it actually lives | Backed up by |
|---|---|---|
| All business data (employees, payroll, leave, attendance, users, permissions, audit logs, notifications, scheduled-task state, password reset tokens...) | MariaDB, database `komagin_hr` | `scripts/backup.sh`'s `mysqldump` |
| Company settings, including SMTP/mail credentials (`company_settings.email_settings`) | MariaDB (same database) — **not** a separate config file | Same `mysqldump` — no separate mail-config backup needed |
| Uploaded documents, contracts, employee photos/avatars | `uploads/{documents,contracts,photos,avatars,employees}/` | `scripts/backup.sh`'s `tar` of `uploads/` |
| Branding assets (letterheads, logos, signatures, stamps, watermarks) | `uploads/{letterheads,logos,signatures,stamps,watermarks}/` | Same `tar` — one archive covers all of `uploads/`, branding included |
| Application code | Git (this repository) | Not this guide's concern — recovered via `git clone`/`git checkout`, see §12 of the deployment guide |
| Environment configuration (`DB_HOST`, `DB_PASS`, `APP_URL`, etc.) | PHP-FPM pool `env[]` directives, **not** in git (deliberately — see Stage 6.1) | Not database/file backup — see §6 below |
| Cron/scheduler schedule itself (the crontab entries) | System crontab (`deploy`/`www-data` users), **not** in git | Not database/file backup — see §6 below |
| Scheduler *state* (lock/run history) | MariaDB tables `scheduled_task_locks`/`scheduled_task_runs` | Covered by the `mysqldump` (just business data like any other table) |
| Logs (`logs/php_errors.log`, `logs/cron.log`, `logs/backup.log`) | `logs/` directory | Deliberately **not** backed up — see §7 |

The one-line summary: **two commands cover almost everything that matters** — a `mysqldump` of `komagin_hr` and a `tar` of `uploads/`. `scripts/backup.sh` runs both together, timestamped, in one invocation.

## 2. Backup Schedule and Retention

Wired into the DigitalOcean deployment guide's §10.5:

| Tier | Cron schedule | Retention |
|---|---|---|
| Daily | 02:00 every day | 14 backups (2 weeks) |
| Weekly | 02:30 every Sunday | 8 backups (~2 months) |
| Monthly | 03:00 on the 1st | 12 backups (1 year) |
| Manual | On demand (`scripts/backup.sh manual <label>`) | Never auto-rotated — deleted manually when no longer needed |

Retention rotation is handled inside `scripts/backup.sh` itself (keeps the N most recent files per tier, by filename pattern, deletes older ones) — no separate cleanup job needed.

**Backups should not live only on the same disk as the data they protect** — a droplet-level disk failure or a `rm -rf` mistake at the wrong path would take out both the live data and every backup simultaneously. The deployment guide's §10.5 flags this explicitly: point `BACKUP_ROOT` at a separate volume, or add an off-box sync step (e.g. `rclone`/`s3cmd` to DigitalOcean Spaces, or a DigitalOcean Droplet snapshot on top of this file-level backup) once the droplet exists. This program certifies the backup *mechanism* works correctly (§4 below); actually provisioning off-box storage is an infrastructure choice for whoever operates the real droplet, since no droplet exists yet (per this phase's confirmed scope).

## 3. RPO / RTO Targets

| Metric | Target | Basis |
|---|---|---|
| **RPO** (Recovery Point Objective — maximum acceptable data loss) | 24 hours | Daily backup cadence. A mid-day incident could lose up to a day of changes — acceptable for an internal HR system with no real-time transactional requirement (unlike, say, payment processing). Lower this by increasing daily backup frequency if the operator later decides 24h isn't tight enough — `scripts/backup.sh daily` can be run more than once a day with no changes needed. |
| **RTO** (Recovery Time Objective — maximum acceptable downtime) | 2-4 hours for a full restore (DB + files) onto a working server; under 15 minutes for a single accidentally-deleted file or record, once identified | Based on the live-tested restore drill (§4) — a full `mysqldump`/`tar` restore of this system's current data volume completed in seconds; the 2-4 hour target budgets for the realistic parts of a real incident (diagnosing what actually needs restoring, provisioning a replacement server if the droplet itself is lost, DNS propagation) rather than the restore command itself, which is fast. |

These are starting targets for a newly-productionized internal system, not contractually derived SLAs — revisit them once real usage patterns and stakeholder expectations are known.

## 4. Live-Tested Restore Drill (Evidence)

Performed against this environment's real database and real `uploads/` directory, using disposable, clearly-prefixed test data throughout:

1. Inserted a disposable test employee (`P6TEST-DR-BEFORE`) and a disposable test file (`uploads/documents/P6TEST-DR-marker.txt`).
2. Ran `scripts/backup.sh manual dr-drill` — produced a real `mysqldump` (568K) and a real `tar.gz` of `uploads/` (632K).
3. Inserted a second disposable test employee (`P6TEST-DR-AFTER`) — deliberately *after* the backup — and deleted the test file from the live `uploads/`, simulating an accidental deletion.
4. Ran `scripts/restore.sh` **without** `--confirm`/`--target-db` — correctly **refused**, printing a clear error rather than silently overwriting the live database.
5. Re-ran with `--target-db komagin_hr_dr_restore_test` — restored cleanly into a scratch database.
6. Queried the restored scratch database: contained `P6TEST-DR-BEFORE` **only** — `P6TEST-DR-AFTER` correctly absent, proving the backup is a genuine point-in-time snapshot, not a live/moving reference.
7. Extracted the files backup: the deleted `P6TEST-DR-marker.txt` was present in the extracted archive, confirming file-level recovery from accidental deletion works.
8. Cleaned up: dropped the scratch database, removed the extracted-files directory, deleted both test employee rows from the live database, removed the disposable backup files.

This complements Stage 6.3's earlier backup/restore drill (Database Certification Report §3), which covered the same mechanism from the database-integrity angle (row-count matching, orphan-freedom after restore); this drill specifically exercises the **operational scripts** (`backup.sh`/`restore.sh`) that will actually run on a cron schedule in production, including their safety guard against an accidental live-database overwrite.

## 5. Recovery Procedures by Scenario

### 5.1 Accidental deletion of a single record or file

1. Identify the most recent backup that still contains the deleted item (check `database/backups/` — or the configured off-box `BACKUP_ROOT` — filenames are timestamped).
2. Restore that backup into a **scratch database** (`--target-db komagin_hr_recovery_scratch`, never directly onto the live database for a partial recovery).
3. `mysqldump` or manually copy just the needed row(s) out of the scratch database, or manually copy the needed file out of the extracted files archive.
4. Insert/copy the recovered item back into the live system.
5. Drop the scratch database.

### 5.2 Database corruption or a bad migration

1. Stop the application from serving traffic if the corruption is actively causing incorrect behavior (take Nginx offline or show a maintenance page — outside this guide's scope to script, since it depends on the real droplet's setup).
2. Identify the last known-good backup (before the corruption/migration).
3. Restore it with `scripts/restore.sh <backup> --confirm` (no `--target-db`, so it restores over the live `komagin_hr` database) — **only after confirming this really is the intended, deliberate action**, since `--confirm` is the flag that removes the safety refusal.
4. Verify the application functions correctly against the restored database (login, a few key pages) before resuming traffic.
5. Any changes made between the backup's timestamp and the corruption are lost (bounded by the RPO in §3) — this is the fundamental tradeoff of any backup-based recovery, not a defect in this procedure.

### 5.3 Full server / droplet loss

1. Provision a replacement droplet, following the deployment guide (`Deployment/phase6-digitalocean-deployment-guide.md`) from §1 through §9 (server setup, stack install, database setup, code deployment, environment config, Nginx, SSL).
2. Instead of `database/install.php` (§9, which builds a fresh empty schema), restore the most recent off-box backup: `scripts/restore.sh <latest_db_backup.sql> <latest_files_backup.tar.gz> --target-db komagin_hr --confirm`, then move the extracted files backup into `uploads/` in place of the empty directory the fresh clone would otherwise have.
3. Re-apply environment configuration (§6 below — this is **not** covered by the data backup, since it was never in the database or `uploads/` to begin with).
4. Re-install the crontab entries (app scheduler + backup jobs) — also not covered by the data backup, see §6.
5. Point DNS at the new droplet's IP (or re-attach a DigitalOcean Floating IP if one was used, which avoids this step entirely — worth provisioning on the real droplet for exactly this reason).
6. Run through the deployment guide's §11 Post-Deployment Checklist in full before resuming production traffic.

## 6. What Backups Do NOT Cover — and Why That's Handled Separately

Two categories of "configuration" deliberately live outside both git and the database, so they are **not** captured by `scripts/backup.sh`:

- **Environment variables** (`DB_HOST`, `DB_PASS`, `APP_URL`, etc., set via the PHP-FPM pool's `env[]` directives). This was a deliberate Stage 6.1 design choice — secrets don't belong in a git-tracked file or a database dump that might be copied around more loosely than the server itself. **Mitigation**: the deployment guide (§6) documents the exact `env[]` block needed; keep a copy of the real, filled-in values in a password manager or secrets vault (outside this repository), not as a "backup" in the traditional sense.
- **The crontab entries themselves** (both the app scheduler from §10 and the backup jobs from §10.5). **Mitigation**: both are fully documented, copy-pasteable command blocks in the deployment guide — re-applying them after a server rebuild is a guide lookup, not a restore operation.

This is intentional, not an oversight: infrastructure-as-documentation (this guide) is the correct recovery mechanism for server *configuration*, while `scripts/backup.sh` is the correct mechanism for *data*. Conflating the two (e.g., trying to back up `env[]` values into the same `mysqldump`) would mean secrets end up inside a backup file that gets rotated, copied, and potentially synced off-box — a worse security posture, not a more resilient one.

## 7. Logs Are Not Backed Up (Deliberately)

`logs/php_errors.log`, `logs/cron.log`, and `logs/backup.log` are excluded from the backup scope. Logs are diagnostic and re-generative — losing history in them is inconvenient (see the Stage 6.8 report for retention/rotation policy) but never means losing actual business data or configuration, so including them in the RPO/RTO-bearing backup pipeline would add size and complexity for no recovery benefit. If audit-log retention for compliance reasons becomes a requirement, that's a distinct future decision, out of this phase's charter scope (this program's audit trail of *user actions* lives in the `audit_logs` **database table**, which the `mysqldump` already fully covers — that's a different thing from the PHP/cron/backup *diagnostic* log files this section is about).

## 8. Regression

Full Phase 1 (20/20), Phase 2 (29/29), and Phase 5 (40/40) regression suites re-run after adding `scripts/backup.sh`/`scripts/restore.sh` — all passed, confirming the new scripts (which touch no application code) introduced no regression.

## 9. Conclusion

A tested, documented backup and restore pipeline now exists: `scripts/backup.sh` (daily/weekly/monthly/manual, with retention rotation) and `scripts/restore.sh` (with a safety refusal against accidental live-database overwrite), both live-verified with a real point-in-time restore drill proving correctness for both database rows and uploaded files. RPO/RTO targets are documented as reasonable starting points for this system's actual risk profile. Two categories of server configuration (environment variables, crontab entries) are deliberately handled via documentation rather than the data-backup pipeline, for sound security reasons, not by oversight.
