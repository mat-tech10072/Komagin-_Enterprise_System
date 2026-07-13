# Komagin HR — Phase 6 Stage 6.8: Logging & Monitoring Verification Report

**Document type:** Phase 6 Deliverable — Stage 6.8 (charter §12)
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-14

---

## 1. Log Surface Inventory

| Log surface | Where it lives | What it records |
|---|---|---|
| PHP errors/exceptions | `logs/php_errors.log` | Every PHP error/exception, unconditionally, routed here by `config/config.php` regardless of environment |
| Cron/scheduler output | `logs/cron.log` | `cron/run.php`'s stdout/stderr, per Stage 6.2's crontab redirect |
| Backup script output | `logs/backup.log` | `scripts/backup.sh`'s stdout/stderr, per Stage 6.6's crontab redirect |
| User action audit trail | `audit_logs` (database table) | Every significant user action (login/logout, create/edit/delete, approvals, settings changes) — module, action, actor, old/new value, timestamp |
| Email send attempts | `email_logs` (database table) | Every email the app attempts to send: type, recipient, subject, body, status, failure reason |
| Scheduler execution history | `scheduled_task_runs`/`scheduled_task_locks` (database tables) | Each scheduled task run's start/end/outcome, and the overlapping-run lock state |
| Reminder deduplication | `reminder_notifications_log` (database table) | Which reminder categories have already fired today, per recipient — prevents duplicate reminders across multiple daily cron runs |

## 2. Finding: Password Reset Tokens Leaked in Plaintext via `email_logs` (Fixed)

Checked every log surface above for sensitive-data leakage — passwords, tokens, secrets, SMTP credentials, PII beyond what's operationally necessary. One genuine finding:

`email_logs.body_html` stores the full rendered HTML body of every sent email, verbatim. The password-reset email (`auth/forgot_password.php`) necessarily includes a working reset link containing the raw, unhashed token — while `password_reset_tokens.token_hash` correctly stores only a sha256 hash of that same token (Phase 5, Stage 5.5's deliberate design). The result: **the audit-quality protection of hashing the token at rest was completely undermined by a plaintext copy of the identical token sitting in a second, broadly-readable table.** Anyone with read access to `email_logs` — any admin permission that surfaces email logs, a SQL injection, a leaked database backup — could extract a live, valid (until used or expired within 1 hour), account-takeover-capable token directly from this table.

**Fixed**: `sendEmail()` (`config/functions.php`) now accepts an optional `$logBodyHtml` parameter — when provided, it's what gets persisted to `email_logs`, while the original `$bodyHtml` (the real content, unaffected) is still what actually gets sent to the recipient. `auth/forgot_password.php` now passes a redacted body (the reset link replaced with `[reset link redacted from log — token is single-use and expires in 1 hour]`) for logging. No other `sendEmail()` call site in the codebase embeds a comparable secret in its body (checked: the only other call sites are a payslip notification and a settings-page test-email feature, neither of which carries a credential).

**Verified live**: triggered a real `forgot_password.php` request against a disposable test account — `email_logs.body_html` for that send contains the redaction placeholder, not the token; `password_reset_tokens.token_hash` for the same request is a normal, correctly-formed hash, confirming the actual reset mechanism is unaffected by the logging change.

**Regression suite updated**: `phase5-regression-run.sh`'s Group 5 previously extracted the raw token directly from `email_logs.body_html` to test the full reset-completion flow — exactly the behavior just fixed. Updated to (a) add a new assertion confirming the real `email_logs` row created by the enumeration-resistance test earlier in the same group does *not* contain a raw token pattern (positive proof the fix works, exercised against the real `forgot_password.php` HTTP endpoint), and (b) generate a fresh, independent token via the identical generation/hashing logic (direct PHP, not via a log) to keep exercising `reset_password.php`'s real completion/invalidation logic end to end. Full suite: **41/41** (was 40 — the 1 new assertion).

## 3. Everything Else Checked — No Leakage Found

- **`logs/php_errors.log`**: current content (from an earlier stage's deliberate forced-connection-failure test) shows MySQL's standard `Access denied for user '...' (using password: YES)` format — this never includes the actual password value, only a yes/no flag, which is MySQL's own safe-by-design error format.
- **SMTP authentication failures**: code-reviewed `sendEmail()`'s SMTP path — the exception message on an auth failure (`"SMTP auth failed: $r"`) is the SMTP server's own response text, which by standard SMTP protocol behavior never echoes back submitted credentials. No `email_logs.failure_reason` rows currently exist to check against live data, but the code path itself doesn't construct a message containing `$pass`.
- **`audit_logs`**: queried directly for any row containing a bcrypt-hash-shaped string or the literal text `password_hash`/`smtp_pass` in `old_value`/`new_value` — zero matches. Confirmed via code review that every `auditLog()` call site touching passwords (`auth/change_password.php`, `auth/reset_password.php`, `modules/users/index.php`'s admin password reset, `modules/users/profile.php`) passes `null` for `old_value`/`new_value`, logging only that the action happened, never the value. `modules/settings/email.php`'s `update_email_settings` audit entry also omits both values, consistent with the pre-existing KOM-031 fix that already keeps `smtp_pass` out of the settings page's own HTML.
- **Cron/backup logs**: reviewed the actual scripts (`cron/run.php`, `scripts/backup.sh`) — both log only operational status (task names, timestamps, file sizes, pass/fail), no data values.
- **Scheduler/reminder-dedup tables**: hold only task names, timestamps, and lock state — no user data.

## 4. Retention and Rotation

| Log | Rotation policy | Where documented |
|---|---|---|
| `logs/cron.log` | Weekly, keep 8 (~2 months), compressed | Deployment guide §10 |
| `logs/backup.log` | Weekly, keep 8 (~2 months), compressed | Deployment guide §10.5 |
| `logs/php_errors.log` | **Was undocumented — gap found and fixed this stage.** Now: weekly, keep 8 (~2 months), compressed, same pattern as the other two | Deployment guide §10.5 (new) |
| `audit_logs`/`email_logs`/scheduler tables | No automatic pruning — grows indefinitely, backed up along with all other business data (see Disaster Recovery Guide §1) | Not a file-rotation concern; a future data-retention policy decision if table size ever becomes an issue, out of this phase's scope |

`logs/php_errors.log` having no rotation policy was a real, if minor, gap — on a busy production system this file could grow unbounded. Fixed by adding a third `logrotate` config to the deployment guide, identical in shape to the two that already existed for `cron.log` and `backup.log`.

## 5. Regression

Full Phase 1 (20/20), Phase 2 (29/29), and Phase 5 (41/41 — includes the 1 new assertion from this stage) regression suites re-run — all passed.

## 6. Conclusion

One genuine, meaningful finding (the password-reset-token leak into `email_logs`) was found and fixed, with the fix itself now covered by an explicit, automated regression assertion rather than only a one-time manual check. One minor documentation gap (missing `php_errors.log` rotation) was found and closed. Every other log surface in the system was checked and found clean of sensitive-data leakage.
