# Komagin HR — Phase 5 Stage 5.12: Security & Privacy Review

**Document type:** Phase 5 Deliverable — Stage 5.12 Report
**Status:** Complete.
**Date compiled:** 2026-07-13
**Scope:** Every file added or modified across Phase 5, Stages 5.1–5.11 (47 PHP files; diff base `40b564e52feff480a98d33069f2fc305160d789e`, Phase 4's final commit).

---

## 1. Method

A dedicated re-review pass across all Phase 5 additions, independent of the per-stage live-verification already performed during each stage. Covers: authentication/authorization gating, CSRF protection, SQL injection surface, file upload handling, external dependencies, dangerous-function usage, and PII/privacy exposure. Findings are either confirmed-clean (with the evidence) or, where a gap was found, already fixed within this same stage — this program's standing discipline of not letting a review-stage finding go undocumented or unfixed.

## 2. Authentication & Session Security

| Area | Finding |
|---|---|
| New unauthenticated pages (`auth/forgot_password.php`, `auth/reset_password.php`) | Both correctly check `isLoggedIn()` and redirect away if already authenticated; both carry session-bound CSRF tokens despite being pre-login pages (a CSRF token doesn't require a *logged-in* session, just *a* session, which `session.php`'s bootstrap always establishes) — confirmed protecting against cross-site triggering of the reset-request and reset-submission actions. |
| Token generation | `bin2hex(random_bytes(32))` — 256 bits of cryptographically secure entropy. Only `sha256()` of the raw token is ever stored (`password_reset_tokens.token_hash`); the raw token exists only transiently in the generated link and the outbound email body. |
| Token validation | Single query checks hash match, `used_at IS NULL`, `expires_at > NOW()`, and `u.is_active = 1` together — an inactive/deactivated account's outstanding tokens are correctly treated as invalid even before expiry. One generic "invalid or expired" message covers every failure mode (not found / expired / used / account inactive), preventing the page from being used as an oracle. |
| Enumeration resistance | `forgot_password.php` returns byte-identical output whether or not the submitted identifier matches a real account — confirmed in Stage 5.5's live test. |
| Rate limiting | `passwordResetRequestBlocked()` checked *before* any account lookup; a rate-limit hit returns the same generic success message rather than revealing the limit was hit. |
| Session invalidation on password change | `auth/session.php` compares `users.password_changed_at` against each session's own `login_time` on every protected-page load, forcing re-login on every *other* active session after a change/reset — live-verified end-to-end in Stage 5.5 with two genuinely separate authenticated sessions. |
| CSRF token comparison | `verifyCsrfToken()` uses `hash_equals()` (timing-safe comparison) — pre-existing, unchanged, confirmed still in place. |

**No gaps found in this area.**

## 3. Authorization / Permission Gating

Every new or modified page handling a state-changing action was checked for `requireLogin()`/`requirePermission()`/`hasPermission()` presence:

| Page | Gate |
|---|---|
| `modules/settings/calendar.php` | `requirePermission('settings.manage', 'view'/'edit')` |
| `modules/temp_employees/attendance_entry.php` | `requirePermission('temp_employees.edit', 'edit')` |
| `cron/*` | Not permission-gated by design — CLI-only via two independent layers (`PHP_SAPI !== 'cli'` check + `.htaccess`), confirmed still in place. |
| `modules/employees/add.php` (guided conversion) | Existing `requirePermission('employees.create', 'create')` unchanged; the conversion-specific pre-fill/linking additionally requires `canApprove('recruitment.review')`, checked independently at both GET (pre-fill) and POST (linking) time. |
| `modules/recruitment/index.php` (Convert action visibility) | Gated on both `canApprove('recruitment.review')` and `canCreate('employees.create')`. |
| `modules/activity_log/{index,download}.php` | Re-gated onto `audit.view` (Stage 5.10, KOM-037) — confirmed `payroll_officer` (who holds neither) remains blocked; confirmed `hr_manager`/`hr_officer` correctly gain access as the intended effect of the merge. |
| `modules/archive/monthly.php` (Lock action) | Reuses the existing `requirePermission('archive.generate', 'create')` gate already covering the whole POST handler — the new `lock_id` branch sits inside the same guarded block, not a separate unguarded path. |

**No gaps found.** No new page introduced in Phase 5 is reachable without an appropriate permission check.

## 4. SQL Injection Surface

- Full-codebase grep across every Phase 5–touched file for `$_GET`/`$_POST` values interpolated directly into a SQL string: **zero matches**.
- The two new pages with the most custom SQL (`calendar.php`, `attendance_entry.php`) were read in full — every query uses `prepare()`/`execute()` with bound parameters throughout, including the dynamic `WHERE employee_id IN (...)` clause in `attendance_entry.php` (built from placeholder repetition, values passed via the parameter array — never concatenated).
- Stage 5.10 additionally converted 6 pre-existing files' `LIMIT`/`OFFSET` pagination (KOM-046) and `dashboard.php`'s server-generated dates (KOM-056) to bound parameters, closing the last remaining raw-interpolation patterns in the areas this phase touched.

**No gaps found.**

## 5. File Upload Handling

- Stage 5.10 (KOM-038) removed `image/svg+xml` from allowed letterhead types — SVG's embedded-script risk is no longer accepted anywhere in the application.
- Stage 5.10 (KOM-039) changed `uploadFile()` (the single shared upload helper used by all 14 call sites app-wide, not just branding) to derive the saved file's extension from the server-detected MIME type via an explicit map, never from the client-supplied filename — live-verified with a PNG-content file uploaded under a `.php` filename, confirmed saved as `.png`.
- Stage 5.10 (KOM-055) ensures superseded branding asset files are deleted from disk, closing an unbounded-storage-growth path — not a security vulnerability itself, but confirmed as part of this review since it touches the same upload/storage code path.

**No gaps found; two real gaps from this same phase already closed in Stage 5.10.**

## 6. External Dependencies

Full-codebase grep for outbound URLs across every Phase 5–touched file found only the application's own `APP_URL` constant and the pre-existing Google Fonts CDN `<link>` in `header.php` (neither carries any application data). **Zero new external network dependencies were introduced anywhere in Phase 5.** The one external dependency that existed before this phase in the areas Phase 5 touched — the QR code feature's call to `api.qrserver.com` — was removed outright in Stage 5.9, not merely worked around. Phase 5 ends with *fewer* external dependencies than it started with.

## 7. Dangerous Function Usage

Full-codebase grep across every Phase 5–touched file for `eval`, `unserialize`, `extract`, `assert`, `system`, `exec`, `shell_exec`, `passthru`: only `PDO::exec()` calls on static, non-parameterized SQL (safe) and disposable test-file `PDO::exec()` calls in a testing script. No dangerous function is invoked on user-controlled input anywhere in Phase 5.

## 8. PII / Privacy Review

- **Notification content** (Stage 5.6's reminders, and notifications created throughout Phase 5): every notification recipient role was checked against the sensitivity of what it's told. Contract/probation/document-expiry and training reminders go to `hr_manager` (already the role with legitimate visibility into that employee data via the rest of the application). Payroll-publication reminders go to `payroll_manager` only — never to `hr_manager` or any non-payroll role, keeping payroll-sensitive process state within the payroll audience. No notification created in Phase 5 carries salary, bank, or national-ID data in its message text.
- **`temp_attendance`** (Stage 5.8) stores only `employee_id`, `attendance_date`, `hours_worked`, `notes`, `entered_by` — no new PII fields.
- **Password reset flow**: the raw token exists transiently in the emailed link and, as a byproduct of this dev environment's pre-existing `sendEmail()` logging design (not something Stage 5.5 introduced), in `email_logs.body_html` — disclosed in full in the Stage 5.5 report at the time. This is unchanged by this review; flagged again here for completeness since a security review is exactly the place a reader would look for it. Not a new gap: the *hashed* token is the only thing the application's own token-validation logic ever trusts.
- **CSV exports** (Stage 5.10, KOM-033/034): formula-injection neutralization now protects every export path in `activity_log/download.php`, including the ones producing employee/consultant attendance and personal detail exports — closes a vector where a malicious free-text field (e.g., a leave `reason`) could execute a formula/command when a recipient opens the exported CSV in a spreadsheet application.

**No new PII exposure introduced by Phase 5.**

## 9. Summary

No unresolved security or privacy findings from this dedicated review. Every gap this pass would otherwise have surfaced (SVG uploads, extension-trust-from-filename, CSV formula injection, SMTP password exposure, `APP_ENV` fail-open default, the QR external dependency) was already identified and fixed within Stages 5.5–5.10 as part of the normal per-stage work, several of them via the same "smallest complete safe solution" review discipline applied here. This stage's contribution is the independent confirmation that no *additional*, previously-unnoticed issue exists across the full Phase 5 diff — not a fresh set of new fixes.
