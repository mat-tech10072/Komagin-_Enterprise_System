# Komagin HR — Phase 5 Stage 5.5: Self-Service Password Recovery (Admin Surface Only)

**Document type:** Phase 5 Deliverable — Stage 5.5 Report
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13

---

## 1. Scope Decision (Recap)

KOM-041: no self-service password-reset flow existed on any of the 4 authentication surfaces (Admin, Employee Portal, Consultant Portal, Temp Portal) — the only recovery path was an already-authenticated admin manually resetting another user's password. Per the decision matrix in `01-phase5-open-findings-scope.md` §6 (user sign-off), the scope for Phase 5 is **Admin surface only** — the Admin surface is the one guaranteed to have a real, verified email address on file for every account. Employee/Consultant/Temp Portal keep the existing admin-assisted-only model; this is a documented, deliberate scope limitation, not an oversight.

## 2. What Was Built

**Schema** (`database/phase13_workflow_completeness_automation.sql`, applied live and added to `database/schema.sql`):
- `users.password_changed_at` (datetime, nullable) — timestamp of the last password change from any source (self-service change, self-service reset, or admin-initiated reset).
- `password_reset_tokens` — `token_hash` (sha256 of the raw token; **the raw token itself is never stored anywhere in the database**), `expires_at` (1 hour), `used_at`, `requested_ip`, FK to `users` with `ON DELETE CASCADE`.
- `email_logs.type` enum extended with `'password_reset'` (see §5, Incident).

**New pages:**
- `auth/forgot_password.php` — accepts a username or email, always shows an identical generic confirmation message regardless of whether the account exists, rate-limited (5 requests / 15 minutes per requesting IP, reusing the `audit_logs`-query rate-limit pattern established in Phase 2 rather than a dedicated table). Invalidates any prior unused token for the same user before issuing a new one. Sends the reset link via the existing `sendEmail()`.
- `auth/reset_password.php` — validates the token (hash match, not expired, not used, account still active) via `findValidResetToken()`; one generic "invalid or expired" message covers every failure mode (not found / expired / already used) so the page cannot be used to distinguish which. Enforces an 8-character minimum password. On success: updates `password_hash`, sets `password_changed_at = NOW()`, clears `must_change_password` / `login_attempts` / `locked_until`, marks the token `used_at = NOW()` (one-time use), and audits `password_reset_completed`.

**Modified:**
- `auth/login.php` — sets `$_SESSION['login_time']` at login (alongside the existing `last_activity`); added a "Forgot password?" link; renders a distinct alert for `?reason=password_changed`.
- `auth/session.php` — on every admin-page load, compares `users.password_changed_at` against the current session's own `login_time`; if the password changed after this session began, the session is destroyed and the request is redirected to `login.php?reason=password_changed`. This is the practical substitute for "invalidate all other active sessions," since this codebase's default file-based PHP sessions have no central per-user session registry to selectively destroy other sessions by.
- `auth/change_password.php` (self-service, already-logged-in change) — now also sets `password_changed_at = NOW()`, and refreshes its **own** `$_SESSION['login_time']` immediately after, so the session that performed the change doesn't immediately self-invalidate on its very next request.
- `modules/users/index.php` (admin-initiated reset) — now also sets `password_changed_at = NOW()`, so an admin resetting another user's password correctly forces that user's other sessions to re-login too.
- `cron/tasks/expire_tokens.php` (Stage 5.4 infrastructure) — extended to also mark expired `password_reset_tokens` as used, keeping the table's `used_at` column accurate for any future admin-facing "active tokens" listing even though expiry is already independently enforced at point of use.

## 3. Security Properties, Live-Verified

All tests used disposable data (`p5testuser` / `p5testuser@example.com`, id 54), fully removed afterward (see §6).

| Requirement | How it's met | Verified |
|---|---|---|
| No account-existence disclosure | Identical generic response for existing vs. non-existing identifier | Requested reset for `p5testuser` and for a nonexistent username — byte-identical confirmation page for both |
| Raw token never persisted | Only `sha256(token)` stored; raw token exists only transiently in the emailed link | Confirmed `password_reset_tokens.token_hash` is a 64-char hex digest, and no column anywhere stores the plaintext |
| Rate limiting | Max 5 requests / 15 min per requesting IP | Code-verified against the same `audit_logs`-query pattern already live-tested in Phase 2; not re-exercised to exhaustion here to avoid polluting `audit_logs` with 5+ additional test rows beyond what was already needed |
| Token single-use | `used_at` set on first successful reset; a second attempt with the same token is rejected | Reused a consumed token — rejected with the same generic "invalid or expired" message |
| Token expiry | 1-hour `expires_at`, enforced in the `WHERE` clause of `findValidResetToken()` | Code-verified (schema + query); not clock-manipulated in this environment to force real-time expiry |
| **Other active sessions invalidated on reset** | `auth/session.php`'s `login_time` vs. `password_changed_at` comparison | **Full live end-to-end test** (see below) |
| Self-service change doesn't self-logout | `change_password.php` refreshes its own `login_time` | Live-tested: performed a self-service change, immediately reloaded a protected page in the same session — 200 OK, no forced logout |
| Full HTML syntax validity | `php -l` on every touched file | All clean |

### 3.1 Session Invalidation — Full Live Test

This was the load-bearing test for the "invalidate other active sessions" requirement, since this codebase has no session registry to verify against directly — only the practical, end-to-end effect can prove it works.

1. Completed a full forgot-password → email-logged token → reset → login → token-reuse-rejected cycle for `p5testuser`, setting its password to a known value.
2. Established **Session A**: logged into `p5testuser` in an independent cookie jar, confirmed it could load `dashboard.php` (**200 OK**).
3. From a **second, independent** cookie jar (**Session B**, never authenticated), requested a fresh password reset for the same account, extracted the new raw token from `email_logs.body_html` (see Phase 5 testing-technique note in Stage 5.5 planning — the plaintext token only ever exists transiently as a byproduct of the pre-existing `sendEmail()` logging behavior, not something created for this stage), and completed the reset — setting yet another new password.
4. Re-requested `dashboard.php` using **Session A's original cookie jar** (whose `login_time` predates step 3's `password_changed_at`):
   - **Before** step 3: `dashboard.php` → 200 OK.
   - **After** step 3: `dashboard.php` → **302 Found, `Location: auth/login.php?reason=password_changed`**.
5. Followed the redirect — confirmed the "Your password was changed. Please sign in again." message renders.
6. Re-requested `dashboard.php` again using Session A's (now-destroyed) cookie — confirmed it stays logged out (302 to plain `login.php`, no session state survives).

This directly confirms the mechanism works end-to-end on a live, separately-authenticated session, not just at the database-write level.

## 4. Regression

- Phase 1 suite: **20/20 passed**.
- Phase 2 suite: **29/29 passed**.
- Zero regressions introduced.

## 5. Incident: `email_logs.type` Enum Gap (Self-Disclosed)

While building this stage, `auth/forgot_password.php` calls `sendEmail(..., 'password_reset', ...)`, passing `'password_reset'` as the email `type`. The pre-existing `email_logs.type` column is an `enum('payslip','leave_approval','leave_rejection','document','general','test')` with no `'password_reset'` value. Because this MariaDB instance runs in non-strict SQL mode, the `INSERT` did not fail — it silently coerced the invalid enum value to `''` (empty string) instead. This was caught during live testing when inspecting `email_logs` rows for the test account and finding `type` blank instead of the expected value.

**Impact:** cosmetic/categorization only — the email was still logged and sent correctly, the reset flow was never affected, and no security property was weakened. The only consequence would have been any future admin-facing "filter emails by type" view silently missing password-reset emails.

**Fix:** extended the enum to `..., 'password_reset'`, applied live via `ALTER TABLE` and added to both `database/phase13_workflow_completeness_automation.sql` (for reproducibility on any environment applying this migration fresh) and `database/schema.sql` (canonical schema). The two mis-typed test rows were corrected before cleanup; no production data was ever affected (this is a dev/test environment with no real users on this account).

## 6. Test Data Cleanup

All disposable test artifacts removed and verified absent:
- `users` row id=54 (`p5testuser`)
- `password_reset_tokens` rows for user 54
- `email_logs` rows referencing user 54
- `audit_logs` rows for user 54
- Local cookie jars / session-inspection scratch files

## 7. Register / Change Control Updates

- **Master Remediation Register**: KOM-041 closed — see entry update.
- **Change Control Log**: CC-109 (schema + password recovery flow), CC-110 (email_logs enum fix).

## 8. Deliberately Out of Scope (Unchanged This Stage)

- Employee Portal, Consultant Portal, Temp Portal self-service reset — per the Stage 5.5 decision, these remain admin-assisted-only. Not a defect; a scoped decision, revisitable in a future phase if the business requires it.
