# Komagin HR — Employee Portal Security Report

**Document type:** Phase 2 Deliverable #4 of 11
**Findings addressed:** KOM-017 (H-12), KOM-027 (M-04), KOM-052 (L-07), KOM-067
**Date:** 2026-07-11/12

---

## 1. Scope

This report covers `employee-portal/{login,logout,_session,hub}.php` — the permanent-employee-facing surface. Temp-employee-specific changes are covered separately in the Temporary Employee Portal Report, since Objective 8 treats that as its own review area even though the code now shares most of its infrastructure with this surface.

## 2. Session Fixation (KOM-017) — Fixed

Both login branches (permanent employee and, previously, temp employee — now handled here since they share `login.php`) were missing `session_regenerate_id()` entirely. Both now call `regenerateSessionOnLogin('ep_')` immediately on successful password verification. See the Session Security Report for the live-verified before/after session ID comparison.

## 3. Hub Request CSRF (KOM-027) — Fixed

`employee-portal/hub.php`'s request-submission form had no CSRF token, unlike its HR-facing counterpart (`modules/hub/view.php`), which already verified one. A token is now generated and embedded in the form, and verified before the `INSERT INTO employee_requests` runs.

**Live verification:** a submission without a token was rejected with zero rows inserted (checked using a uniquely-timestamped test marker to rule out any collision with genuine pre-existing data — an earlier test run had a false failure for exactly this reason, corrected before the final pass); a submission with a valid token succeeded and inserted the expected row, which was then deleted as test cleanup.

## 4. Brute-Force Protection (KOM-052) — Fixed

`employees`/`temp_employees` have no `login_attempts`/`locked_until` columns, and Phase 2's charter forbids a database redesign. Rather than add columns, brute-force tracking reuses the existing `audit_logs` table — the same "count recent failures in a rolling window" pattern the attendance kiosk already used successfully. `portalLoginBlocked('employee_portal')` counts `failed_login` audit entries for the requesting IP in the last 15 minutes; at 5 or more, further attempts are blocked with a clear message, before any password check even runs.

**Live verification:** 5 consecutive wrong-password attempts against a real employee number each returned "Incorrect password"; the 6th attempt returned "Too many failed attempts from this location. Please try again in 15 minutes." — confirming the threshold triggers exactly as designed. Test audit-log entries were deleted afterward.

## 5. Logout Consistency (KOM-067) — Fixed

`employee-portal/logout.php` previously called `session_destroy()` alone, skipping the explicit `session_unset()` step `auth/logout.php` performed — a minor but real inconsistency. Both now call the identical shared `destroySessionCompletely()`, which also adds a capability neither had before: explicitly expiring the session cookie client-side via `setcookie()`, not just destroying the server-side session data.

**Live verification:** logout response now includes `Set-Cookie: PHPSESSID=deleted`; a subsequent request with the old cookie is rejected (302 to login).

## 6. What Was Already Correct

`employee-portal/policy.php`'s policy-acceptance gate, `_layout.php`'s presentational-only role (no session logic to worry about), and the portal's IDOR-safe record scoping (every query filtered by the session-derived employee ID, confirmed in the Phase 0 baseline audit) were not touched and remain correct.

## 7. Not Addressed in This Phase

`policy.php`'s own agree-to-policy POST handler has no CSRF token either — this was noticed during testing (the policy-agreement step had to be completed via a plain POST with no token to unblock further testing) but is not one of the findings named in the Phase 2 charter. Flagged here for a future phase's consideration rather than fixed unilaterally, consistent with this program's scope discipline.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial employee portal security report | Remediation Program — Phase 2 |
