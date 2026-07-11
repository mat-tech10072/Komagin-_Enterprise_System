# Komagin HR — Cookie & Session Configuration Report

**Document type:** Phase 2 Deliverable #8 of 11
**Objective addressed:** Objective 4 — Cookie Hardening
**Finding addressed:** KOM-042
**Date:** 2026-07-11/12

---

## 1. Configuration Matrix — Before Phase 2

| Surface | Secure | HttpOnly | SameSite | Lifetime |
|---|---|---|---|---|
| Admin | Conditional (HTTPS/`APP_ENV`) | Yes | Strict | 28800s |
| Employee Portal | **Never** | Yes | Strict | 28800s |
| Consultant Portal | **Never** | Yes | Strict | 28800s |
| Temp Employee Portal | **Never** | Yes | Strict | 28800s |
| Self-Service | **Never** | Yes | Strict | 3600s |

HttpOnly and SameSite=Strict were already consistent everywhere — the gap was specifically the Secure flag, present only on the admin surface.

## 2. Configuration Matrix — After Phase 2

| Surface | Secure | HttpOnly | SameSite | Lifetime |
|---|---|---|---|---|
| Admin | Conditional (HTTPS/`APP_ENV`) | Yes | Strict | 28800s |
| Employee Portal | **Conditional (HTTPS/`APP_ENV`)** | Yes | Strict | 28800s |
| Consultant Portal | **Conditional (HTTPS/`APP_ENV`)** | Yes | Strict | 28800s |
| Temp Employee Portal | **Conditional (HTTPS/`APP_ENV`)** (shares Employee Portal's cookie) | Yes | Strict | 28800s |
| Self-Service | **Conditional (HTTPS/`APP_ENV`)** | Yes | Strict | 3600s |

Every surface now applies the identical rule: `Secure` is set when the request is over HTTPS, or when `APP_ENV === 'production'`. This is the same logic the admin surface already had — Phase 2 propagated it, rather than inventing a new rule.

## 2a. Why "Conditional" and Not "Always"

Setting `Secure` unconditionally would break every surface under plain HTTP — the browser would refuse to send the cookie at all, making login impossible in this local XAMPP development environment (no HTTPS configured). The conditional rule is the correct one for an application that needs to run in both a local HTTP dev environment and a production HTTPS deployment without a code change between the two — matching the pattern the admin surface already established and proved correct before this phase began.

## 3. Cookie Scope (`path`)

`path=/` is now explicit and consistent across all four session-bearing surfaces via `bootstrapSession()`. Previously this was set inline per-surface with identical values, but as separate literal arrays — functionally the same result, but four places where a future edit could silently diverge. Self-service also had its `path` added explicitly for the same reason (it was previously omitted from that file's `session_set_cookie_params()` call, defaulting to PHP's own default rather than an explicit, auditable value).

## 4. Session Storage

Confirmed unchanged from the Phase 0 baseline: PHP's default file-based session storage, no Redis/Memcached, no custom save path. This phase did not introduce or require a session-store change — the shared framework operates entirely through PHP's standard `session_*()` API regardless of backend.

## 5. Why Self-Service Wasn't Folded Into `bootstrapSession()`

Explained fully in the Authentication Framework Report §3: self-service is a single-use magic-link flow, not a login session with a meaningful "rotate every 30 minutes" or "regenerate on login" concept (there is no login step to regenerate at — the token itself is the authentication). It received the matching cookie-hardening fix (`secure` flag, explicit `path`) applied directly to its own `session_set_cookie_params()` call, keeping the fix scoped to what actually applies to its request shape rather than forcing an architectural fit that doesn't exist.

## 6. Verification

All five surfaces' cookie behavior was exercised as part of the broader Phase 2 regression suite (logins, session ID capture, logout cookie-clearing) — see the Regression Test Report. The specific `Secure`-flag conditional logic itself was verified by code review against the already-proven-correct admin implementation, since this local environment runs over plain HTTP and cannot itself demonstrate the flag being set to `true` in a live request/response cycle.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial cookie and session configuration report with before/after matrices | Remediation Program — Phase 2 |
