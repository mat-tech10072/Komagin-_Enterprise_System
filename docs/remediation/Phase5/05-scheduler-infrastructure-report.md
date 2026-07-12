# Komagin HR — Phase 5 Stage 5.4: Scheduled Task Infrastructure

**Document type:** Phase 5 Deliverable — Stage 5.4 Report
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13

---

## 1. What Was Built

No cron/scheduled-task mechanism existed anywhere in this codebase — confirmed by a full repository search in Phase 4 and re-confirmed at Phase 5's baseline (§1 of `01-phase5-open-findings-scope.md`). Built a lightweight, shared-hosting-compatible scheduler with no framework dependency, matching the charter's explicit structure:

```
cron/
├── run.php            — entry point, invoked by a host cron job
├── bootstrap.php       — CLI-only guard + shared includes
├── .htaccess           — blocks all .php requests at the web-server level too
├── tasks/
│   ├── expire_tokens.php
│   ├── send_reminders.php           (infrastructure placeholder — Stage 5.6 fills this in)
│   ├── process_notifications.php    (infrastructure placeholder — no queue exists to process yet)
│   └── cleanup_safe_temp_files.php
└── README.md            — cPanel cron setup instructions
```

**Schema** (`database/phase13_workflow_completeness_automation.sql`, applied live and added to `database/schema.sql`):
- `scheduled_task_locks` — single-row-per-lock-name table; `run.php` inserts a row to acquire the scheduler-wide lock, deletes it to release. A `UNIQUE KEY` on `lock_name` makes acquisition atomic (a second concurrent `INSERT` fails outright, no race window).
- `scheduled_task_runs` — one row per task per run: name, status (`running`/`success`/`failed`), items processed, error summary, start/finish timestamps.

**Task file contract**: each file under `tasks/` is a standalone script `require`d by `run.php`, ending with `return $itemsProcessed;` (an int). No shared task-class hierarchy — the smallest complete contract that still gives `run.php` a real count to log.

## 2. Safety Properties, Live-Verified

| Requirement | How it's met | Verified |
|---|---|---|
| CLI-first, web execution blocked | `bootstrap.php` checks `PHP_SAPI !== 'cli'`; `cron/.htaccess` independently blocks all `.php` requests to the directory at the Apache level (defense in depth — two independent layers) | `curl` to `cron/run.php` returns **403** |
| One scheduler lock, no overlapping runs | `scheduled_task_locks` with a `UNIQUE KEY` on `lock_name`; a second `run.php` invocation while one is in progress detects the `INSERT` failure and exits immediately (exit 0, not an error) | Manually held the lock, ran `run.php` — correctly logged "Another scheduler run is already in progress" and touched nothing |
| Stale-lock recovery | A lock older than 30 minutes is deleted before the next acquisition attempt, so one crashed run can never permanently wedge the scheduler | Backdated a lock to 40 minutes old — the next run correctly cleared it and proceeded normally |
| Failure isolation between tasks | Each task runs inside its own `try/catch`; an exception is recorded in `scheduled_task_runs` and execution moves to the next task | Temporarily replaced `process_notifications.php` with a file that throws — confirmed the task before it (`send_reminders`) and after it (`cleanup_safe_temp_files`) both still ran and succeeded; the failure was correctly recorded with its exception message; file restored afterward |
| No secrets in logs | Task files in this codebase never construct exception messages from credentials/tokens (confirmed by reading all 4) | Code review |
| Idempotent processing | `expire_tokens`/`cleanup_safe_temp_files` use `WHERE` conditions that naturally converge to 0 matching rows once processed — safe to run twice in a row | Ran the scheduler twice in immediate succession against the same test data; second run correctly processed 0 items where the first had already handled them |
| Environment-aware | Uses the same `config/config.php`/`database.php` as every other entry point — no separate cron-specific environment config to drift out of sync | N/A (structural) |

## 3. The Two Functional Tasks

- **`expire_tokens.php`**: flips `employee_update_links.is_active` to 0 for rows past `expires_at`. Not strictly required for correctness (`self-service/update.php` already filters `expires_at > NOW()` at the point of use), but keeps `is_active` accurate for any code that queries it directly. Live-verified: 1 disposable expired-but-still-`is_active=1` test link correctly flipped to `is_active=0`.
- **`cleanup_safe_temp_files.php`**: deletes `employee_update_links` rows that are inactive/revoked/expired **and** older than a 180-day retention window — the only genuinely disposable, non-evidentiary data this application generates. Deliberately never touches `audit_logs` or any other record with compliance value. Live-verified: 1 disposable test row (backdated 200 days, already inactive) correctly deleted.

## 4. The Two Placeholder Tasks

`send_reminders.php` and `process_notifications.php` are registered, wired into `run.php`, and return `0` (a safe, honest no-op, not a failure) — their real logic depends on decisions and data that belong to later stages:
- `send_reminders.php`'s actual reminder types (contract expiry, training, recruitment, etc.) are Stage 5.6's explicit subject.
- `process_notifications.php` has nothing to do yet — every notification in this codebase is created synchronously at the point of the triggering action (`config/functions.php`'s `createNotification()`), so there is no queue to drain. Reserved for if that changes.

## 5. Deployment Note (Not Applied This Stage)

The charter's §5.0/§Prohibited-Actions is explicit that this program does not deploy to production or apply production migrations without separate authorization. This stage delivers the scheduler **code and documentation**; actually registering the cron job in a live cPanel account is a deployment action for the user to perform when ready, per `cron/README.md`'s setup instructions — not something this remediation phase does on its own.

## 6. Regression

| Suite | Result |
|---|---|
| PHP syntax check (6 files: `bootstrap.php`, `run.php`, 4 task files) | 0 errors |
| Live functional tests (this stage) | Web-access block (403); CLI run (4/4 tasks succeed); lock-held rejection; stale-lock recovery; failure isolation (1 forced failure, 3 unaffected successes); idempotent real-data processing for both functional tasks |
| Phase 1 regression | 20/20 |
| Phase 2 regression | 29/29 |

## 7. Register Update

No specific Master Register finding maps to this stage — it is new infrastructure the charter's Stage 5.6 (notifications) and Stage 5.5 (password-reset token expiry) will build on, not a fix to a previously-documented defect. Recorded in the Change Control Log for full traceability.
