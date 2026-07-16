# Komagin HR — Scheduled Tasks

Phase 5, Stage 5.4. This directory runs recurring background tasks (token expiry, reminders, data hygiene) via a host-level cron job. There is no queue worker, no daemon, and no dependency beyond the same PHP the rest of the application already uses — this is deliberately shared-hosting-compatible (built and tested against a Namecheap/cPanel-style deployment target, not a VPS with a process manager).

## How it works

- `run.php` is the single entry point. It acquires a database lock (`scheduled_task_locks`), runs every task listed in its `TASKS` constant in order, and releases the lock when done.
- Each file under `tasks/` is a standalone script `require`d by `run.php`. It ends with `return $itemsProcessed;` (an integer) — that's the entire contract, no shared task-class hierarchy to learn.
- If a task throws an exception, `run.php` catches it, records the failure in `scheduled_task_runs`, and **continues to the next task** — one broken task never blocks the others.
- If `run.php` is invoked while a previous run is still in progress (the lock is held), it logs that and exits immediately (exit code 0 — this is expected, not an error) rather than running two copies concurrently.
- A lock older than 30 minutes is treated as abandoned (from a crashed previous run) and cleared automatically, so a single bad run can never permanently wedge the scheduler.
- Both `run.php`/`bootstrap.php` (via a `PHP_SAPI !== 'cli'` check) and `.htaccess` (blocking all `.php` requests to this directory at the web-server level) refuse to run as a web request — this must only ever be triggered by cron.

## Setting up the cron job (cPanel)

1. Log in to cPanel → **Cron Jobs**.
2. Find your PHP CLI binary path. This varies by host and PHP version — check under cPanel → **Select PHP Version**, or ask your host's support; a common pattern is `/usr/local/bin/php` or `/usr/local/bin/ea-php82`. **Do not guess** — an incorrect path silently does nothing.
3. Add a new cron job:
   - **Command**: `/usr/local/bin/php /home/CPANEL_USERNAME/public_html/cron/run.php` (replace both the PHP path and the site path with your actual values)
   - **Schedule**: every 15–30 minutes is a reasonable starting point for this application's task set (nothing here is latency-sensitive to the minute). `send_reminders.php` (Stage 5.6) is safe at this cadence — its threshold checks are deduplicated per calendar day via `reminder_notifications_log`, so running the scheduler more often never produces duplicate reminder notifications.
4. Save. cPanel runs the command on schedule; there's no need to keep a terminal session open (unlike a VPS `screen`/`tmux`-based worker).

## Checking it's working

```sql
-- Most recent run of each task
SELECT task_name, status, items_processed, started_at, finished_at, error_summary
FROM scheduled_task_runs
ORDER BY started_at DESC LIMIT 20;
```

A healthy scheduler shows `status='success'` rows appearing at your configured interval for all 4 tasks. `error_summary` is populated only on `status='failed'` rows and never contains credentials or tokens (see each task file — none of them log secrets by design).

## Adding a new task

1. Create `tasks/your_task_name.php` ending with `return $itemsProcessed;` (an int).
2. Add it to the `TASKS` constant in `run.php`.
3. Keep it idempotent — assume it might run twice in a row (e.g. after a crash-and-retry) and make sure that's harmless.
