# Komagin HR — Session Security Report

**Document type:** Phase 2 Deliverable #2 of 11
**Objectives addressed:** Objective 3 (Session Fixation Protection), core of Objective 2
**Findings addressed:** KOM-012 (H-07), KOM-017 (H-12), KOM-066
**Date:** 2026-07-11/12

---

## 1. Session Fixation — Before and After

Session fixation: an attacker who can plant or predict a session ID before a victim authenticates can hijack the victim's session after login, if the ID doesn't change at the moment of authentication. Before Phase 2:

| Surface | Regenerated session ID on login? |
|---|---|
| Admin | Yes (already correct) |
| Employee Portal | **No** |
| Consultant Portal | **No** |
| Temp Employee Portal | **No** (no session framework of its own at all) |

Two of four surfaces — the two with the largest user populations — had no fixation defense whatsoever.

## 2. The Fix

`regenerateSessionOnLogin(string $prefix)` is called at the exact moment credentials are verified successfully, **before** any other session data is written:

```php
function regenerateSessionOnLogin(string $prefix): void {
    session_regenerate_id(true);              // destroys the OLD session, issues a new ID
    $_SESSION[$prefix . 'last_regen'] = time(); // prevents a redundant re-rotation on the next page load
}
```

Wired into every login success path:
- `auth/login.php` — one call site (was already calling `session_regenerate_id(true)` directly; now uses the shared function so the timestamp bookkeeping is consistent with every other surface).
- `employee-portal/login.php` — **two** call sites (permanent-employee branch and temp-employee branch — both were previously missing this entirely).
- `consultant-portal/login.php` — one call site (previously missing entirely).

## 3. Live Verification — Every Surface, Directly Measured

Rather than trust the code, every login was tested by capturing the `PHPSESSID` cookie value from the pre-login request and comparing it to the value after a successful login:

| Surface | Session ID before login | Session ID after login | Changed? |
|---|---|---|---|
| Admin | `hvs5jgp0ra926s541v6gl188s3` | `556i2u3subrl2t2je7e4fuqdon` | ✅ |
| Employee Portal | `ummo89l9rcoknb22dse7eqafma` | `kupbnpv0iflvtjdee4eqahr2rn` | ✅ |
| Consultant Portal | `4ags6h3pjn9a2grhmrk4sa1a1r` | `42lttiq91tjevc7v9svdcr7fv1` | ✅ |
| Temp Employee Portal | `sn4rgqjub8ak1tep4jlr8luju4` | `kieo6lbo28s92lvrqqgv1vb2m7` | ✅ |

All four confirmed changing. This table's values are from the actual test runs performed during this phase (see Regression Test Report for the reproducible script).

## 4. The Double-Regeneration Bug (KOM-066) — Fixed as a Side Effect of the Same Design

Previously, `auth/login.php` called `session_regenerate_id(true)` directly but never stamped a "last regenerated" timestamp itself — so the very next page load through `auth/session.php` would see no timestamp, conclude the session had never been rotated, and regenerate a second time. Harmless, but wasteful and undocumented. `regenerateSessionOnLogin()` fixes this by design: it stamps the timestamp in the same call that does the regeneration, so `bootstrapSession()`'s next invocation always sees a fresh timestamp and never redundantly re-rotates.

## 5. Idle Timeout and Periodic Rotation — Unified

All four surfaces now share:
- **30-minute periodic ID rotation** during continued activity (was already the pattern on 3 of 4 surfaces; now identical logic, not independently re-implemented).
- **8-hour idle timeout**, after which `destroySessionCompletely()` runs and the caller redirects to its own login page with `?reason=timeout`.

The temp employee portal previously had **neither** of these — no rotation, no idle timeout, relying solely on the 8-hour absolute cookie lifetime regardless of activity. It now has both, identical to the main employee portal, since it shares the exact same `bootstrapSession('ep_', 28800)` call via `_session.php`.

## 6. What Session Fixation Protection Does Not Cover

This phase did not add IP-binding or user-agent fingerprinting to sessions (binding a session to network characteristics), which is a separate, heavier-weight defense with its own tradeoffs (breaks for users on mobile networks that rotate IPs, VPNs, etc.) and was not requested by the charter. Session-ID regeneration on login plus periodic rotation is the standard, proportionate defense for this application's threat model and matches what the admin surface already had proven correct before this phase began.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial session security report with live verification table | Remediation Program — Phase 2 |
