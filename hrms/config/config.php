<?php
// ============================================================
// KOMAGIN HR MANAGEMENT SYSTEM - MAIN CONFIGURATION
// ============================================================

define('APP_NAME', 'Komagin HR');
define('APP_FULL_NAME', 'Komagin HR Management System');
define('APP_VERSION', '1.0.0');

// Safe, lightweight build/deployment identifier for runtime diagnostics
// (e.g. the payroll dashboard's diagnostic log line — see
// config/functions.php's logPayrollDashboardDiagnostics()). Never shells
// out to `git` on a request — that would add a process-spawn cost to
// every page load. A deploy script MAY set BUILD_ID or GIT_COMMIT as a
// real server environment variable (e.g. `export BUILD_ID=$(git rev-parse
// HEAD)` right after `git pull` on the droplet) to make diagnostics show
// exactly which commit is live; if neither is set, this safely falls back
// to APP_VERSION so nothing breaks on an environment that hasn't opted in.
define('BUILD_ID', getenv('BUILD_ID') ?: (getenv('GIT_COMMIT') ?: APP_VERSION));

// Phase 6, Stage 6.1: these constants are now environment-driven — set
// APP_URL/DB_*/etc. as real server environment variables (Apache SetEnv,
// Nginx fastcgi_param, or PHP-FPM pool env[] directives) on any
// deployment other than this local dev box; see .env.example for the
// full documented list. Every fallback below reproduces this app's
// existing local XAMPP dev behavior exactly, so no environment
// variable needs to be set anywhere for local development to keep
// working unchanged.
//
// 2026-07-18: the fallback below previously read 'http://localhost/
// HR_Komagin' — correct before this app lived in its own top-level
// htdocs folder, but stale ever since commit 73924a6 restructured the
// repository into this Komagin_Enterprise_System/hrms monorepo layout
// and never updated it. A stale APP_URL breaks every internal absolute
// link the app generates from it — including, critically, the
// post-login redirect to dashboard.php, which is why a fresh local
// login was landing on a 404 instead of the dashboard. Updated to match
// this repository's actual current local path.
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/Komagin_Enterprise_System/hrms');

// Database Configuration
// Phase 6, Stage 6.4: default fallback is '127.0.0.1', not 'localhost'.
// Found via load testing: on this Windows/XAMPP dev environment,
// resolving the hostname "localhost" for the PDO/MySQL TCP connection
// took ~2.0-2.6 seconds per connection (an IPv6-then-IPv4-fallback
// pattern), versus ~0.02s connecting to 127.0.0.1 directly — a ~100x
// difference, adding roughly 2 seconds to every single request that
// touches the database (i.e. nearly every request). '127.0.0.1'
// unambiguously means "local TCP/IP loopback" on every platform,
// avoiding hostname-resolution behavior that varies by OS/network
// config — a safer default regardless of deployment target, not just
// a workaround for this one environment. Still fully overridable via
// the DB_HOST environment variable (e.g. a deployment that
// specifically wants MySQL's Unix-socket-on-"localhost" behavior on
// Linux can still set DB_HOST=localhost explicitly).
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'komagin_hr');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// Session Settings
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 28800)); // 8 hours

// File Upload Settings
define('UPLOAD_PATH', getenv('UPLOAD_PATH') ?: (__DIR__ . '/../uploads/'));
define('MAX_FILE_SIZE', (int)(getenv('MAX_FILE_SIZE') ?: 10485760)); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg', 'image/png']);

// Currency Settings — change these two lines to switch currency system-wide
define('CURRENCY_SYMBOL', 'K');   // e.g. '$', '£', '€', 'K', 'N', 'R', 'USD'
define('CURRENCY_CODE',   'PGK'); // e.g. 'USD', 'GBP', 'EUR', 'ZAR', 'NGN', 'ZMW'

// Employee Number Format
define('EMP_PREFIX', getenv('EMP_PREFIX') ?: 'KOM-EMP');
define('EMP_YEAR_FORMAT', 'Y');
define('EMP_NUMBER_LENGTH', 4);

// Attendance Settings Defaults
define('DEFAULT_WORK_START', '08:00:00');
define('DEFAULT_WORK_END', '17:00:00');
define('DEFAULT_GRACE_PERIOD', 15); // minutes
define('DEFAULT_BREAK_DURATION', 60); // minutes
define('DEFAULT_WORK_HOURS', 8);
define('DEFAULT_OVERTIME_THRESHOLD', 8); // hours before overtime kicks in

// Timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Pacific/Port_Moresby');

// Force UTF-8 output encoding
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Environment — set APP_ENV=development in server config for verbose
// on-screen errors during local development. KOM-053: this previously
// defaulted to 'development' (errors displayed) whenever APP_ENV wasn't
// explicitly set — the safer fail-closed default is production (errors
// suppressed on-screen, still logged to logs/php_errors.log) so a
// misconfigured/un-configured deployment doesn't leak stack traces,
// file paths, or query text to end users by default.
define('APP_ENV', getenv('APP_ENV') ?: 'production');

if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Security
define('CSRF_TOKEN_LENGTH', 32);
