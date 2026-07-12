<?php
/**
 * Phase 5, Stage 5.4 — shared bootstrap for every scheduled task.
 *
 * CLI-only: refuses to run under a web server request. These tasks are
 * meant to be triggered by a host-level cron job (e.g. cPanel's cron
 * manager), never a browser request — an unauthenticated web-accessible
 * cron endpoint is a real, entirely avoidable attack surface (anyone
 * could trigger mass notification sends, token expiry, or repeated
 * task runs just by requesting the URL).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain');
    die("Forbidden: this script may only be run from the command line (cron), not a web browser.\n");
}

define('CRON_RUNNING', true);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

// Cron jobs have no PHP session — $_SESSION is not populated here.
// auditLog() and similar helpers already degrade safely to a NULL
// actor when $_SESSION['user_id'] is unset (confirmed: config/functions.php
// uses `$_SESSION['user_id'] ?? null` throughout), so no special-casing
// is needed in task files for that.
