# Komagin HR — Administrator Guide

**Document type:** Phase 6 Deliverable — Stage 6.7 (charter §11)
**Status:** Complete.
**Date compiled:** 2026-07-14
**Audience:** whoever operates this system day to day — `super_admin`/`hr_manager` role users, and whoever holds SSH access to the production droplet.

---

This guide consolidates day-to-day operation of Komagin HR. It doesn't repeat what's already fully documented elsewhere — it points to those documents and focuses on what isn't written down anywhere yet: how to actually run the business processes, how to respond when something breaks, and how to maintain the system over time.

**Related documents** (read these for their specific topics — this guide doesn't duplicate them):
- Installation / fresh setup: `database/README.md`
- Deployment to a new server: `Deployment/phase6-digitalocean-deployment-guide.md`
- Backup, restore, disaster recovery: `Phase6/07-disaster-recovery-guide.md`
- Scheduled tasks / cron mechanism: `cron/README.md`
- Security posture / what's been hardened: `Phase6/06-security-certification-report.md`

## 1. Installation and Upgrade

**Fresh install**: run `database/install.php` once (see `database/README.md` for the exact sequence it runs), then delete it — this is already covered step-by-step in the deployment guide §9.

**Upgrading an existing installation** (applying a new migration after a code update): `database/README.md`'s "Upgrade Path" section documents which migration files are safe to re-run against an already-migrated database (`phase11`–`phase13` are all idempotent — safe to run more than once, they check for existing state before changing anything). The general procedure:

1. Take a fresh backup first (`scripts/backup.sh manual pre-upgrade` — see the Disaster Recovery Guide).
2. Pull the new code (`git pull` or re-deploy per the deployment guide).
3. Run any new migration file(s) added since the last upgrade (`mysql komagin_hr < database/phaseNN_....sql`), in numeric order.
4. Smoke-test: log in, check the dashboard loads, spot-check the module(s) the update touched.
5. If anything looks wrong, restore the pre-upgrade backup (Disaster Recovery Guide §5.2) rather than trying to hand-fix a partially-applied upgrade.

## 2. User and Permission Management

Users are managed at **Settings → Users** (`modules/users/index.php`). Four roles exist: `super_admin`, `hr_manager`, `hr_officer`, `payroll_officer`. A role's actual capabilities are governed by the **permission matrix** at **Settings → Roles & Permissions**, not by the role name itself — the matrix maps each role to a set of module-level `view`/`create`/`edit`/`delete`/`approve` grants, independently per module. To change what a role can do, edit the matrix; to change what one specific user can do, either assign them a different role or (for narrower cases) use per-module overrides where the module supports them (e.g., branding's four asset-type-specific permissions).

**Creating a new admin-side user**: Settings → Users → Add User. The role you assign determines their access via the matrix above — there is no separate "custom permissions per user" concept for admin-side accounts.

**Deactivating a user** (e.g., someone leaves the HR/payroll team): use the user's own "Deactivate" action, not deletion — this preserves their audit trail (every action they took while active remains attributed to them in `audit_logs`) while blocking further login.

**The default admin credential**: a fresh install seeds one `superadmin` account with password `Admin@123` and forces a password change on first login. **Change this immediately on any real installation** — see the Stage 6.5 Security Certification Report §3 for why this matters (a stale/never-rotated default credential is one of that stage's findings).

## 3. Payroll Operations

A payroll run moves through three states: **create → finalize → publish** (module: `modules/payroll/`). Create builds the draft payslips for a period from attendance/timesheet/deduction/savings data already on file; finalize locks the figures (no further automatic recalculation); publish makes payslips visible to employees via the Self-Service portal. Each transition is a deliberate, separate action — don't publish a run you haven't reviewed after finalizing, since employees can see it immediately.

Deductions and savings (`modules/payroll/{deductions,savings}.php`) are **record-keeping only** — the system tracks amounts against an employee but does not itself calculate loan interest, government-mandated deduction schedules, or similar (a deliberate scope decision from Phase 4 — see Master Register KOM-085). If your organization needs automatic recalculation, that remains a manual bookkeeping step outside this system today.

**Common payroll troubleshooting**:
- A payslip shows unexpected values → check the source attendance/timesheet data for that period before assuming a payroll bug; the payroll figures are computed from that data.
- A finalized run needs a correction → there is no "un-finalize" — you'll need to review the specific record directly or, if the error is significant, treat it as a data-correction task (edit the underlying payslip record) rather than trying to re-run the whole period.

## 4. Leave and Attendance

**Leave approval** is single-stage, HR-only (Phase 5, Stage 5.1 locked this in as the permanent model — no supervisor pre-approval stage). An employee applies via Apply Leave (or the Employee Portal); any user with `leave.approve` (typically `hr_manager`/`hr_officer`) approves or rejects from **Leave Management**. Rejecting correctly restores the employee's leave balance (Master Register KOM-081).

**Attendance** has three capture paths: the physical/on-screen **Kiosk** (permanent employees, ID-based, no PIN), **Timesheets** for corrections/overtime, and — for temporary employees specifically, who don't use the kiosk — a supervisor/HR-entered **manual attendance capture** screen (`modules/temp_employees/attendance_entry.php`, built in Phase 5 Stage 5.8).

**The working-day/holiday calendar** (Settings → Calendar, built in Phase 5 Stage 5.3) governs which days count toward leave-day calculations and monthly attendance reporting. Keep it current — add public holidays for the coming year at the start of each year, since leave-day math and "Absent" figures both depend on it being accurate.

## 5. Document Generation and Branding

**Document Templates** (Settings → Document Templates, or **modules/documents/templates.php**) hold the actual template bodies (HTML with variable placeholders) used by **Generate Document**. Template bodies are sanitized at save time (Phase 4, KOM-022) — this closes a stored-XSS risk but also means certain raw HTML constructs may be stripped; if a template renders unexpectedly after editing, check whether the edit relied on something the sanitizer removes.

**Branding assets** — letterheads, signatures, stamps, watermarks — are managed at **Settings → Branding**, each with its own independent permission grant (four separate slugs, not one shared "branding" permission — Phase 1, KOM-032). These are the images that appear on generated documents; if a generated document is missing its letterhead, check that the correct asset is both uploaded *and* selected as active for that document type.

**QR code verification on generated documents is disabled**, deliberately (Phase 5, Stage 5.9 — Master Register KOM-097). The feature previously linked to a public verification page that never existed and called an unauthorized third-party API to render the QR image; rather than building the missing page, the toggle was removed from the template editor and the flag hardcoded off. If a future requirement genuinely needs document verification, that's new feature work, not a re-enable of the old flag.

## 6. Troubleshooting

| Symptom | Likely cause / where to look |
|---|---|
| A page shows a generic "something went wrong" instead of details | Correct, intended behavior in production (`APP_ENV=production` suppresses raw error detail from users) — check `logs/php_errors.log` for the real error. |
| Login "doesn't stick" — redirected straight back to the login page after a successful-looking login | If this happens on the real production domain (not `localhost`), check whether HTTPS is actually live — session cookies are marked `Secure`, which real browsers refuse to store over plain HTTP on a non-localhost origin. See Stage 6.5 Security Certification Report §5. |
| An email (password reset, notification, reminder) never arrived | Check **Settings → Email/SMTP** configuration is correct; check `logs/php_errors.log` for an SMTP send failure (the app logs full SMTP error detail server-side even though it never shows it to the end user). |
| Scheduled tasks (reminders, token expiry, temp-file cleanup) seem to have stopped running | Check the droplet's crontab is actually installed (`crontab -u www-data -l`) and check `logs/cron.log` for recent entries / errors — see `cron/README.md`. |
| A module page 500s / fatal-errors | Check `logs/php_errors.log` immediately — this is the single most useful diagnostic step for any fatal error, since production mode never shows the detail to the browser. |
| Suspected unauthorized access or a wrongly-visible feature | Check **Activity Log** (Settings → Activity Log, `super_admin` only) for what the account actually did; cross-check the Roles & Permissions matrix for whether the access was actually correctly granted (surprisingly often, "unauthorized access" turns out to be a matrix grant nobody remembered setting). |

## 7. Incident Response

1. **Identify scope** — is this affecting one user, one module, or the whole system? Check `logs/php_errors.log` and, if relevant, `logs/cron.log`/`logs/backup.log` for the time window in question.
2. **Contain, if the issue is active data corruption or an ongoing security incident** — this may mean taking the site offline (stop Nginx / show a maintenance page) rather than leaving it running while corrupted data continues to be written or an active exploit continues.
3. **Do not restore from backup as a first response to something that isn't actually data loss/corruption** — a slow page, a single broken feature, or a misconfiguration is a code/config fix, not a disaster-recovery event. Restoring is for when the data itself is wrong or gone (see Disaster Recovery Guide §5).
4. **If it is a real data incident**, follow the Disaster Recovery Guide's scenario-specific procedure (§5.1 single record, §5.2 corruption, §5.3 full server loss).
5. **After resolution**, write down what happened and why — this program's own Change Control Log (`Regression/change-control-template.md`) is the model to follow: what broke, what was changed, how it was verified fixed. Even a short note prevents the same incident recurring silently.

## 8. Routine Maintenance

- **Weekly**: skim `logs/php_errors.log` for recurring errors even if nothing user-visible has been reported — a caught-early pattern is cheaper than a later incident.
- **Monthly**: confirm backups are actually running and non-empty (`ls -la` the backup directory, check `logs/backup.log`) — see Disaster Recovery Guide §2. A backup pipeline that's silently been failing for months is worse than no backup pipeline, since it creates false confidence.
- **Annually** (or whenever holidays are announced): update the working-day/holiday calendar (§4 above) for the coming year.
- **Whenever the SSL certificate is close to expiry**: Let's Encrypt certificates from Certbot (deployment guide §8) auto-renew via a systemd timer — confirm this is actually working (`sudo certbot renew --dry-run`) rather than waiting to find out it silently stopped.

## 9. Version Upgrades and Release Notes

This system does not currently have a formal version-numbering scheme beyond this remediation program's own phase structure. Until one is adopted:

- Treat each Phase (1 through 6, and any future phase) as a release unit — the phase's own Completion Report is that release's release notes (what changed, what was fixed, what regression testing confirmed it).
- Before adopting any future code change from this repository's `main` branch (or wherever the certified Release Candidate lands), check the Master Remediation Register (`Findings/08-master-remediation-register.md`) and Change Control Log (`Regression/change-control-template.md`) for what's changed since the version currently running in production.
- A more formal semantic-versioning scheme (tagging releases, maintaining a `CHANGELOG.md`) is a reasonable future improvement but is new process, not a production-readiness blocker for this certification — left as a recommendation, not built this phase.

## 10. Regression

This stage is documentation-only — no application code, configuration, or database changes were made. No regression suite re-run required.
