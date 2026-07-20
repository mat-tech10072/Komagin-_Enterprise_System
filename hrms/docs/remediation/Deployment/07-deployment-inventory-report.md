# Komagin HR — Deployment Inventory Report

**Document type:** Phase 0 Baseline Deliverable #7 of 9
**Status:** Documentation only — no configuration file was modified, and no destructive or state-changing command was run to produce this report.
**Date compiled:** 2026-07-11
**Baseline tag:** `v1.0-enterprise-baseline`

---

## 1. `config/config.php` — Every Defined Constant

| Constant | Value / Purpose |
|---|---|
| `APP_NAME` | `'Komagin HR'` |
| `APP_FULL_NAME` | `'Komagin HR Management System'` |
| `APP_VERSION` | `'1.0.0'` |
| `APP_URL` | `'http://localhost/HR_Komagin'` — **hardcoded, not environment-driven** |
| `DB_HOST` | `'localhost'` |
| `DB_NAME` | `'komagin_hr'` |
| `DB_USER` | `'root'` |
| `DB_PASS` | `''` — **empty** |
| `DB_CHARSET` | `'utf8mb4'` |
| `SESSION_LIFETIME` | `28800` (8 hours) |
| `UPLOAD_PATH` | `__DIR__ . '/../uploads/'` |
| `MAX_FILE_SIZE` | `10485760` (10MB) |
| `ALLOWED_IMAGE_TYPES` | jpeg/png/gif/webp MIME array |
| `ALLOWED_DOC_TYPES` | pdf/doc/docx/xls/xlsx + jpeg/png MIME array |
| `HRMS_CURRENCY_SYMBOL` | `'K'` |
| `HRMS_CURRENCY_CODE` | `'PGK'` |
| `EMP_PREFIX` | `'KOM-EMP'` |
| `EMP_YEAR_FORMAT` | `'Y'` |
| `EMP_NUMBER_LENGTH` | `4` |
| `DEFAULT_WORK_START` | `'08:00:00'` |
| `DEFAULT_WORK_END` | `'17:00:00'` |
| `DEFAULT_GRACE_PERIOD` | `15` (minutes) |
| `DEFAULT_BREAK_DURATION` | `60` (minutes) |
| `DEFAULT_WORK_HOURS` | `8` |
| `DEFAULT_OVERTIME_THRESHOLD` | `8` (hours) |
| `APP_ENV` | `getenv('APP_ENV') ?: 'development'` — **the only constant actually read from the environment** |
| `CSRF_TOKEN_LENGTH` | `32` |

**Non-`define()` side effects in this file:** sets timezone to `Pacific/Port_Moresby`; forces UTF-8 charset/mbstring settings; in `APP_ENV==='production'` mode, disables error display and routes errors to `logs/php_errors.log`; in any other mode, displays all errors inline (`E_ALL`, `display_errors=1`).

---

## 2. Every `.htaccess` File in the Project (9 total)

| File | Restricts |
|---|---|
| `/.htaccess` (root) | `Options -Indexes`; browser caching (`mod_expires`) for static assets; security headers (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`); gzip (`mod_deflate`); PHP output-buffering tweak. **No HTTPS redirect.** |
| `uploads/.htaccess` | Blocks execution of scripts (php/php3/php4/php5/phtml/pl/py/cgi/sh/rb/asp/aspx/jsp) within uploads; disables directory listing; forces `Content-Disposition: attachment` on doc/office file types. Modern Apache 2.4 `<FilesMatch>` syntax. |
| `database/.htaccess` | `Deny from all` (blocks all web access to the folder) |
| `config/.htaccess` | `Deny from all` |
| `logs/.htaccess` | `Deny from all` |
| `uploads/letterheads/.htaccess` | `Options -Indexes` + `Deny from all` — **legacy Apache 2.2 syntax** |
| `uploads/signatures/.htaccess` | same legacy syntax |
| `uploads/stamps/.htaccess` | same legacy syntax |
| `uploads/watermarks/.htaccess` | same legacy syntax |

The four branding-asset-folder `.htaccess` files are byte-identical to each other and syntactically inconsistent with the parent `uploads/.htaccess` one level up — see Document Pipeline Report §9 and Master Remediation Register **NC-02** for the operational risk this creates.

---

## 3. `.env.example` vs. Actual Environment Wiring

`.env.example` defines: `APP_ENV`, `APP_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `SESSION_LIFETIME`, `APP_TIMEZONE`, `UPLOAD_PATH`, `MAX_FILE_SIZE`, `EMP_PREFIX`, plus a production checklist as comments.

**Only `APP_ENV` is actually read via `getenv()` anywhere in `config/config.php`.** Every other variable listed in `.env.example` — `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `SESSION_LIFETIME`, `APP_TIMEZONE`, `UPLOAD_PATH`, `MAX_FILE_SIZE`, `EMP_PREFIX` — is hardcoded as a literal `define()` value in `config.php` and is **not** wired to `getenv()` anywhere. `.env.example` currently functions as documentation of intended production configuration surface, not as a live configuration mechanism.

---

## 4. Logging — `logs/` Directory

Contains only `.htaccess` (`Deny from all`). **No `php_errors.log` file exists yet** — nothing has triggered a logged PHP error in this deployment to date. The error-log path is only wired up when `APP_ENV==='production'`; in every other mode, PHP errors are displayed inline rather than logged to file.

---

## 5. Cron / Scheduled Jobs

**Absent.** No cron file, Windows Task Scheduler definition, `crontab` reference, or any `CronJob`/`schtasks`/"run this periodically" comment found anywhere in the codebase.

---

## 6. Backup Scripts

**Absent.** No file or code referencing "backup" anywhere in the project.

---

## 7. Mail / SMTP Configuration — `modules/settings/email.php`

Config fields (stored as JSON in `company_settings.email_settings`): `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_encryption` (tls/ssl/none), `from_name`, `from_email`, `payslip_notify`, `payslip_subject`, `payslip_body`.

**Real sending happens — not just DB logging.** `sendEmail()` in `config/functions.php` always inserts a `pending` row into `email_logs` first, then either (a) falls back to PHP's native `mail()` if no SMTP host/from-address is configured, or (b) performs a hand-rolled SMTP conversation over a raw `stream_socket_client()` connection — no PHPMailer or any mail library — updating the `email_logs` row's status/failure reason afterward.

---

## 8. SSL / HTTPS Handling

**No forced HTTPS redirect exists anywhere** — no `.htaccess` rewrite rule, no PHP-level `header('Location: https://...')` check. `auth/session.php` only *conditionally* sets `session.cookie_secure=1` when `$_SERVER['HTTPS']` is already on or `APP_ENV==='production'` — this hardens the cookie if HTTPS happens to already be in use, but does not enforce or redirect to HTTPS. The three portal surfaces (employee, consultant, self-service) never set `cookie_secure` under any condition (Authentication Report §3, §5, §6).

---

## 9. Session Storage

Confirmed PHP default file-based session storage. `auth/session.php` and the equivalent portal session files only call `ini_set()`/`session_set_cookie_params()` plus `session_start()` — no `session_set_save_handler()`, no `session.save_path` override, and no Redis/Memcached reference anywhere in application code (the only Redis/Memcached-adjacent text in the repository is inside `tests/node_modules/playwright*` license/type-definition files, unrelated to the application).

---

## Deployment Posture Summary at Baseline

| Area | State |
|---|---|
| Environment-driven configuration | Only `APP_ENV`; every other setting is a code-level constant |
| Database credentials | Blank root password (local/dev default, not yet hardened) |
| HTTPS | Not enforced; cookies only conditionally hardened if HTTPS already detected |
| Access control (`.htaccess`) | Root/uploads folders use modern syntax; 4 branding subfolders use legacy syntax — inconsistency needing live verification (Finding NC-02) |
| Logging | Wired for production mode only; nothing logged yet in this environment |
| Backups | No mechanism exists |
| Scheduled jobs | None exist; no feature in the system currently depends on one |
| Outbound email | Real SMTP/mail() sending exists and is wired, contingent on `modules/settings/email.php` configuration |
| Session storage | Default file-based, no external session store |

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-11 | Initial baseline deployment inventory compiled for Phase 0 | Remediation Program — Phase 0 |
