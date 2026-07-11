# Komagin HR — Authentication Report

**Document type:** Phase 0 Baseline Deliverable #5 of 9
**Status:** Documentation only — no authentication file was modified to produce this report.
**Date compiled:** 2026-07-11
**Baseline tag:** `v1.0-enterprise-baseline`

This document describes, mechanism by mechanism, how each of the system's authentication surfaces actually behaves today. It is factual and descriptive, not evaluative — known defects are cross-referenced to their Finding ID in the Master Remediation Register rather than re-argued here.

---

## 1. Admin Login — `auth/login.php` (336 lines)

1. Requires `auth/session.php` → `config/config.php` → `config/database.php` → `config/functions.php`, in that order.
2. If already logged in, redirects to `dashboard.php` (or `modules/payroll/index.php` for `payroll_officer`).
3. On POST: CSRF checked first via `verifyCsrfToken()`.
4. Username/password trimmed, both required non-empty.
5. User looked up by `(username = ? OR email = ?) AND is_active = 1`, parameterized.
6. **Account lockout:** if `locked_until` is set and in the future, login is blocked with remaining-minutes messaging. Per-account (DB column), not per-IP.
7. Password check: `password_verify()` against a bcrypt hash created with `PASSWORD_BCRYPT, ['cost' => 12]`.
8. **On success:** `session_regenerate_id(true)` called immediately after password verification, before any `$_SESSION` values are set. `$_SESSION` populated with `user_id, user_role, user_name, user_email, employee_id, last_activity`. DB updated: `login_attempts=0, locked_until=NULL, last_login=NOW()`. `auditLog('auth','login', ...)` recorded. Role-based redirect map: `payroll_officer`/`payroll_manager` → payroll dashboard, `kiosk_terminal` → kiosk screen, else → main dashboard (overridable by a prior `redirect_after_login` session value). If `must_change_password` is set, redirected to `auth/change_password.php` regardless of the above.
9. **On wrong password:** `login_attempts` incremented; at `>= 5` attempts, `locked_until = NOW() + 15 minutes` is set. **Lockout threshold: 5 attempts / 15-minute lockout.**
10. Unknown username → generic error message (no distinction from other failure paths for that specific case).
11. Login form itself carries a CSRF hidden field, checked server-side (§9).
12. **No "remember me" feature exists.**

## 2. Admin Session Config — `auth/session.php` (34 lines)

Shared include for the admin surface only.
1. `ini_set()`: `session.cookie_httponly=1`, `session.use_strict_mode=1`, `session.cookie_samesite='Strict'`, `session.gc_maxlifetime=SESSION_LIFETIME` (28800s / 8h).
2. `session.cookie_secure=1` set **only if** `$_SERVER['HTTPS']` is on, **or** `APP_ENV==='production'`. Not set under local/dev HTTP.
3. `session_start()` guarded by `PHP_SESSION_NONE`.
4. **ID regeneration:** if `last_regenerated` unset, regenerates immediately and stamps; if `> 1800s` (30 min) since last stamp, regenerates again. Net effect on a fresh login: `auth/login.php` already regenerated once at line 42 but never sets `last_regenerated` itself, so the very next page load through `session.php` regenerates a second time — a double-regeneration on the first post-login request (factual, not evaluated further).
5. **Idle timeout:** `> SESSION_LIFETIME` (8h) since `last_activity` → `session_unset()` + `session_destroy()` + redirect to `login.php?reason=timeout`.
6. `last_activity` refreshed every request (sliding).

## 3. Employee Portal Login — `employee-portal/login.php` (154 lines)

Does **not** include `auth/session.php` — manages its own inline session config, entirely separate from the admin surface.

1. `session_set_cookie_params(['lifetime'=>28800,'path'=>'/','httponly'=>true,'samesite'=>'Strict'])` then `session_start()`. **No `cookie_secure` flag set under any condition** (no HTTPS/production conditional at all, unlike `auth/session.php`).
2. Already-logged-in redirect: permanent employees (`ep_employee_id`+`ep_policy_agreed`) → `dashboard.php`; temp employees (`ep_is_temp`+`ep_temp_employee_id`) → `temp_portal.php`.
3. **No CSRF token on the login form or its POST handler** — confirmed by full-file read, no `csrf_token` field, no `verifyCsrfToken()` call.
4. **Permanent-employee branch:** looks up `employees` by `employee_number = ? AND status='active'`; checks `portal_active`, `portal_password` set, `password_verify()`. On success: sets `ep_employee_id, ep_employee_name, ep_employee_num, ep_policy_agreed=false, ep_login_time, ep_last_activity, ep_last_regen`. Updates `portal_last_login`. Redirects to `policy.php` (dashboard access gated behind policy acceptance). **`session_regenerate_id()` is never called anywhere in this file** — confirmed absent by full-text search.
5. **Temp-employee branch** (fallback when permanent lookup fails): same pattern against `temp_employees`. On success: sets `ep_is_temp=true, ep_temp_employee_id, ep_employee_name, ep_employee_num, ep_login_time, ep_last_activity` — **note: `ep_last_regen` is NOT set in this branch**, unlike the permanent branch. Redirects directly to `temp_portal.php` (no policy gate). Also no `session_regenerate_id()`.
6. **No account lockout** — no `login_attempts`/`locked_until` columns exist for `employees` or `temp_employees`; unlimited password guesses per employee number.
7. **No "remember me."**
8. Failure messages are more granular than the admin surface's (distinguishes not-found / inactive / no-password-set / wrong-password).
9. Footer text: "Forgot your password? Contact your HR department." — no self-service reset exists (§8).

## 4. Employee Portal Session Guard — `employee-portal/_session.php` (60 lines)

The actual per-page guard, included manually at the top of every portal page by convention (not centrally enforced by routing). `_layout.php` is purely presentational — no session logic.

1. Same inline `session_set_cookie_params()` pattern as login.php, no `cookie_secure` under any condition.
2. **ID regeneration:** if `ep_last_regen` unset, **only stamps it (does not regenerate)** — differs from `auth/session.php`, which regenerates immediately on first-unset. If `>1800s` elapsed, regenerates. Practical consequence: since `login.php` never regenerates at login, and the permanent-employee branch sets `ep_last_regen` at login (so the "first-unset" stamp-only branch doesn't fire), the session ID issued pre-login persists unchanged through login and for up to the first 30 minutes of the authenticated session. For temp employees (who never get `ep_last_regen` set at login), the first regeneration opportunity is the very first authenticated page load post-login, and even then it only *stamps*, not regenerates, on that first sight.
3. **Idle timeout:** `>28800s` (8h) since `ep_last_activity` → `session_destroy()` **only** (no preceding `session_unset()`, unlike the admin pattern) → redirect to `login.php?reason=timeout`.
4. `ep_last_activity` refreshed every request.
5. Guard functions: `epIsLoggedIn()` (true iff `ep_employee_id` AND `ep_policy_agreed`), `epRequireLogin()` (redirects to login if no `ep_employee_id`, to policy.php if not yet agreed) — **does not handle the temp-employee session shape at all**; only pages using temp-specific logic can gate temp sessions. `epCurrentEmployee()` — static-cached DB lookup.

## 5. Consultant Portal Login & Session — `consultant-portal/login.php` (117) / `_session.php` (62)

**login.php:**
1. Same inline cookie-param pattern, no `cookie_secure`.
2. Already-logged-in (`cp_consultant_id`) → redirect to dashboard.
3. **No CSRF token on the login form or POST handler.**
4. Lookup: `consultants WHERE consultant_number = ? AND status='active'` (number uppercased before query). Checks `portal_active`, `portal_password` set, `password_verify()`.
5. On success: sets `cp_consultant_id, cp_type, cp_name, cp_number, cp_login_time, cp_last_activity, cp_last_regen` (all set at login — unlike the employee-portal temp branch). Updates `portal_last_login`. Redirects to dashboard.
6. **No `session_regenerate_id()` call anywhere in this file.**
7. **No account lockout** — `consultants.portal_password` exists, no `login_attempts`/`locked_until` columns.
8. "Forgot your password? Contact your HR department." — no self-service reset.

**_session.php:**
1. Same cookie setup as login.
2. **ID regeneration:** same stamp-then-30-min-rotate pattern as employee portal. Since `login.php` *does* set `cp_last_regen` at login, the first actual rotation happens at the 30-minute mark post-login.
3. **Idle timeout (8h):** does **not** call `session_destroy()` at all — instead manually `unset()`s a hardcoded list of 7 keys (`cp_consultant_id, cp_type, cp_name, cp_number, cp_last_activity, cp_last_regen, cp_login_time`) then redirects. A third, distinct idle-timeout implementation pattern (see §10 comparison table).
4. Guard functions: `cpIsLoggedIn()` (`cp_consultant_id` only — no separate policy gate), `cpRequireLogin()`, `cpRequireType(string $type)` (checks `cp_type` matches, used to separate time-based vs output-based consultant flows), `cpCurrentConsultant()`.

## 6. Self-Service Magic-Link Flow

**Generation** — `modules/employees/generate_link.php` (reached only by an authenticated admin holding `employees.update_links` share permission, CSRF-checked):
1. `$token = bin2hex(random_bytes(32))` — CSPRNG, 64 hex chars.
2. `$hash = hash('sha256', $token)` — the **hash**, not the raw token, is stored in `employee_update_links.token`.
3. Any existing active link for that employee is deactivated first (single-active-link-per-employee at generation time).
4. `expires_at` defaults to `+7 days` (admin-adjustable).
5. Shareable URL uses the raw, unhashed token: `.../self-service/update.php?token=<raw>`.
6. Logged via `auditLog('employees','generate_update_link', ...)`.

**Validation/use** — `self-service/update.php` (351 lines):
1. Standalone session, `lifetime=3600` (1 hour) — shortest of any surface. No `cookie_secure` conditional.
2. Malformed token (empty or `strlen() !== 64`) → immediate 404/`_expired.php`, no DB hit.
3. `$tokenHash = hash('sha256', $token)` recomputed from the URL token.
4. Single query: `WHERE ul.token = ? AND is_active=1 AND is_revoked=0 AND expires_at > NOW()`, joined to `employees`. **The hash match itself happens as a parameterized SQL `=` comparison, not a PHP-level `hash_equals()`/`==`** — there is no application-level comparison of the hash at all; it's folded into the `WHERE` predicate.
5. Not-found / inactive / revoked / expired are all indistinguishable to the caller — all four cases return the same 403 + `_expired.php`.
6. **Single-use/replay prevention:** enforced by `is_active=1` at lookup, combined with `is_active=0` written immediately after a *successful POST submission* (not on GET/view — a link can be viewed repeatedly without being consumed; only a successful, no-validation-error submission consumes it).
7. **Per-link CSRF:** `$_SESSION['ss_csrf_'.$link['id']]` generated once per link ID (not a single global token). Compared on POST via **plain `!==` string comparison, not `hash_equals()`** — contrasts with `config/functions.php`'s shared `verifyCsrfToken()`, which does use `hash_equals()`. Unset on successful submit.
8. Field diffing against 16 whitelisted fields (`$FIELD_MAP`); only genuinely-changed values become pending-update rows.
9. Validation: only `personal_email` is format-checked (`FILTER_VALIDATE_EMAIL`); no format validation on phone, bank account number, etc.
10. On success: each changed field inserted as a separate row into `employee_pending_updates` (`status='pending'`) — nothing is applied directly to `employees`; everything routes through HR approval (`modules/employees/pending_updates.php`). Link deactivated. `auditLog('self_service','profile_update_submitted', ...)` recorded.
11. **No rate limiting / no CAPTCHA / no IP throttling** on repeated GET/POST attempts against a given or guessed token.

## 7. Kiosk Clock-In — `modules/attendance/kiosk.php` (628 lines)

1. Deliberately no auth: `session_start()` called unconditionally with **PHP's default session cookie settings** (no `session_set_cookie_params()` call at all — distinct from every other surface).
2. **Kiosk session identification:** if `?t=<token>` present, looks up `kiosk_sessions WHERE kiosk_token = ?`. If absent, falls back to `SELECT * FROM kiosk_sessions WHERE status='open' LIMIT 1` — whichever session is globally marked open, if no token is supplied.
3. **Employee identification:** `employee_number` alone — no PIN, no password. Page footer text explicitly states "No PIN required."
4. **Rate limiting:** counts `kiosk_audit` rows with `ip_address=?, result='error', action='failed_auth'` in the last 5 minutes; blocks at `>=10`. Only counts *invalid-employee-number* attempts, not other error types or successful actions against a valid, guessed number. Per-IP, rolling window.
5. Status gate: only `active`/`probation`/`on_leave` employees may clock in.
6. **No CSRF token anywhere in this file.**
7. Every attempt (success or failure) logged to `kiosk_audit` (employee_id if resolved, employee_number, action, result, error_message, ip_address).
8. `kiosk_token` (if present) is echoed back into the form's action URL to keep subsequent POSTs scoped to the same terminal/session.

## 8. Password Reset ("Forgot Password") — Full Grep Result

**No self-service, email-based, or token-based password-reset flow exists anywhere in the codebase, for any of the four authentication surfaces.** The only recovery path found:

- `modules/users/index.php` — an **admin-initiated** reset: an already-authenticated admin can reset another admin-surface user's password via a modal (min length 6 chars — shorter than `auth/change_password.php`'s 8-char minimum), which sets `must_change_password=1` and forces the target through `auth/change_password.php` at next login. This requires no email/token — the admin sets the password directly.
- `auth/change_password.php` — requires being already logged in AND knowing the current password. Not a recovery mechanism.
- `employee-portal/login.php` and `consultant-portal/login.php` both display static "Contact your HR department" text with no link, form, or token mechanism.
- `modules/employees/set_portal_password.php` and the equivalent consultant edit-page fields are the admin-side tools for setting an employee's/consultant's portal password directly (same non-self-service pattern as the admin-surface reset).

## 9. CSRF Implementation — `config/functions.php`

```php
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH)); // 32 bytes → 64 hex chars
    }
    return $_SESSION['csrf_token'];
}
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
```
CSPRNG-backed generation, single session-scoped token (idempotent — reused, not rotated per-form/per-request), constant-time comparison via `hash_equals()`. This is the system's only general-purpose CSRF mechanism; `self-service/update.php` uses a separate, bespoke per-link scheme (§6) that does not call these functions or use `hash_equals()`.

**Login-form CSRF coverage across the 4 surfaces:**

| Surface | Login form has CSRF field? | POST handler verifies it? |
|---|---|---|
| Admin (`auth/login.php`) | Yes | **Yes**, via `verifyCsrfToken()` |
| Employee portal | No | No |
| Consultant portal | No | No |
| Self-service (field-update form, not a login) | Yes (per-link) | Yes, but via raw `!==`, not `hash_equals()` |

**Only 1 of 3 traditional login forms is CSRF-protected using the shared, constant-time mechanism.**

## 10. Logout — Comparison Across All Three Session-Bearing Surfaces

| Surface | `session_unset()` | `session_destroy()` | Manual key-unset only | Audit logged |
|---|---|---|---|---|
| Admin (`auth/logout.php`) | Yes | Yes | No | Yes |
| Employee portal (`employee-portal/logout.php`) | No | Yes | No | No |
| Consultant portal (`consultant-portal/logout.php`) | No | **No** | **Yes** (7 named `cp_*` keys only) | No |

`auth/logout.php`: logs `auditLog('auth','logout', ...)` before destroying session state; `session_unset()` then `session_destroy()`, in the correct order; redirects to `login.php?reason=logout`.

`employee-portal/logout.php`: `session_destroy()` only (no preceding `session_unset()`); captures `$_GET['reason']` before destroying, passes it through to the login redirect (allows the idle-timeout redirect to reuse this endpoint with `reason=timeout`); no audit log.

`consultant-portal/logout.php`: **neither `session_unset()` nor `session_destroy()` is called** — only the 7 named `cp_*` session keys are individually `unset()`. The underlying PHP session itself is never destroyed; any session key not in that specific list (none currently set by this portal's own code, but any key set by shared/global code in the future) would survive a "logout." No audit log.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-11 | Initial baseline authentication report compiled for Phase 0 | Remediation Program — Phase 0 |
