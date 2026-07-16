<?php
/**
 * Phase 5, Stage 5.4 — scheduled task runner.
 *
 * Intended cPanel/host cron command (confirm the exact PHP CLI binary
 * path for your hosting account first — see cron/README.md):
 *   /usr/local/bin/php /home/CPANEL_USERNAME/public_html/cron/run.php
 *
 * Runs every registered task in TASKS below, in order. A single-run
 * lock (scheduled_task_locks) means an overlapping cron invocation
 * (e.g. a slow previous run still in progress) exits immediately
 * rather than racing it. One task throwing an exception is isolated —
 * every other task still runs, and the failure is recorded per-task in
 * scheduled_task_runs, not silently swallowed.
 */
require_once __DIR__ . '/bootstrap.php';

const LOCK_NAME = 'scheduler';
const LOCK_MAX_AGE_MINUTES = 30; // treat a lock older than this as abandoned by a crashed run

const TASKS = [
    'expire_tokens'           => __DIR__ . '/tasks/expire_tokens.php',
    'send_reminders'          => __DIR__ . '/tasks/send_reminders.php',
    'process_notifications'   => __DIR__ . '/tasks/process_notifications.php',
    'cleanup_safe_temp_files' => __DIR__ . '/tasks/cleanup_safe_temp_files.php',
];

function acquireSchedulerLock(): bool {
    $pdo = db();
    // A lock older than LOCK_MAX_AGE_MINUTES means the process that
    // took it almost certainly crashed without releasing it (no task
    // in this file is expected to run anywhere near that long) —
    // clear it so the scheduler doesn't stay permanently blocked.
    $pdo->prepare("DELETE FROM scheduled_task_locks WHERE lock_name = ? AND locked_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)")
        ->execute([LOCK_NAME, LOCK_MAX_AGE_MINUTES]);
    try {
        $pdo->prepare("INSERT INTO scheduled_task_locks (lock_name, locked_by) VALUES (?, ?)")
            ->execute([LOCK_NAME, (gethostname() ?: 'unknown') . ':' . getmypid()]);
        return true;
    } catch (PDOException $e) {
        return false; // UNIQUE constraint violation — another run holds the lock right now
    }
}

function releaseSchedulerLock(): void {
    db()->prepare("DELETE FROM scheduled_task_locks WHERE lock_name = ?")->execute([LOCK_NAME]);
}

$runStart = microtime(true);
fwrite(STDOUT, "[" . date('Y-m-d H:i:s') . "] Scheduler run starting...\n");

if (!acquireSchedulerLock()) {
    fwrite(STDOUT, "[" . date('Y-m-d H:i:s') . "] Another scheduler run is already in progress. Exiting.\n");
    exit(0);
}

$summaryLines = [];
$anyFailed = false;

foreach (TASKS as $taskName => $taskFile) {
    $taskStart = microtime(true);

    $insertRun = db()->prepare("INSERT INTO scheduled_task_runs (task_name, status) VALUES (?, 'running')");
    $insertRun->execute([$taskName]);
    $runId = (int)db()->lastInsertId();

    try {
        if (!file_exists($taskFile)) {
            throw new RuntimeException("Task file not found: $taskFile");
        }
        // Each task file is a standalone script that ends with
        // `return $itemsProcessed;` (an int) — a simple, explicit
        // contract, no shared task-class hierarchy needed.
        $itemsProcessed = require $taskFile;
        if (!is_int($itemsProcessed)) { $itemsProcessed = 0; }

        db()->prepare("UPDATE scheduled_task_runs SET status='success', items_processed=?, finished_at=NOW() WHERE id=?")
            ->execute([$itemsProcessed, $runId]);

        $elapsed = round(microtime(true) - $taskStart, 2);
        $summaryLines[] = "OK   | $taskName | $itemsProcessed item(s) | {$elapsed}s";
    } catch (Throwable $e) {
        // Failure isolation: this task's exception is caught here and
        // never propagates — every other task in TASKS still runs.
        $anyFailed = true;
        // No secrets in logs: exception messages in this codebase's
        // task files never include credentials/tokens by design (see
        // each task file), so the raw message is safe to store/print.
        $errMsg = get_class($e) . ': ' . $e->getMessage();
        db()->prepare("UPDATE scheduled_task_runs SET status='failed', error_summary=?, finished_at=NOW() WHERE id=?")
            ->execute([$errMsg, $runId]);
        $summaryLines[] = "FAIL | $taskName | $errMsg";
    }
}

releaseSchedulerLock();

$totalElapsed = round(microtime(true) - $runStart, 2);
fwrite(STDOUT, implode("\n", $summaryLines) . "\n");
fwrite(STDOUT, "[" . date('Y-m-d H:i:s') . "] Scheduler run finished in {$totalElapsed}s.\n");

exit($anyFailed ? 1 : 0);
