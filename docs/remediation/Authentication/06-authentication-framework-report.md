# Komagin HR — Authentication Framework Report

**Document type:** Phase 2 Deliverable #1 of 11
**Program:** Enterprise Remediation Program — Phase 2: Authentication, Session Security & Portal Hardening
**Date:** 2026-07-11/12

---

## 1. The Problem This Phase Solved

Phase 0's baseline inventory documented four independent authentication surfaces (Admin, Employee Portal, Consultant Portal, Self-Service) plus a fifth quasi-surface (Temp Employee Portal, nested inside the Employee Portal but with its own separate session handling). Each had separately hand-written session bootstrapping, with small, accumulating differences: only the admin surface set the `Secure` cookie flag; only the admin surface regenerated its session ID on login; three different logout implementations existed with three different levels of completeness; the temp portal had no rotation or idle timeout at all.

Phase 2's mandate was explicit: build one framework, make every surface use it.

## 2. The Framework

`auth/session_common.php` (new) — three functions, used by all four session-bearing surfaces:

```php
bootstrapSession(string $prefix, int $lifetime): bool
regenerateSessionOnLogin(string $prefix): void
destroySessionCompletely(): void
```

`$prefix` is the only thing that varies per surface — `''` for Admin (preserving the existing unprefixed `$_SESSION['user_id']` etc. key names), `'ep_'` for Employee/Temp Portal (they already share this prefix and, critically, the same session store — a temp employee and a permanent employee log in through the identical `employee-portal/login.php`), and `'cp_'` for Consultant Portal. Everything else — cookie flags, rotation timing, idle-timeout logic, teardown — is now one implementation, not four.

## 3. Adoption Across Every Surface

| Surface | File(s) migrated | Session key prefix |
|---|---|---|
| Admin | `auth/session.php`, `auth/login.php`, `auth/logout.php` | `` (none) |
| Employee Portal | `employee-portal/_session.php`, `login.php`, `logout.php` | `ep_` |
| Consultant Portal | `consultant-portal/_session.php`, `login.php`, `logout.php`, `index.php` | `cp_` |
| Temporary Employee Portal | `employee-portal/temp_portal.php` | `ep_` (shared with Employee Portal) |

Self-service (`self-service/update.php`) was deliberately **not** folded into this framework — it's a stateless, single-use magic-link token flow, not a login session with a concept of "regenerate on auth" or "idle timeout" in the same sense. It received the matching cookie-hardening fix directly (Secure flag) without adopting the full rotation/timeout machinery, since that machinery doesn't map onto its request shape. This is a deliberate, documented boundary, not an oversight — see the Cookie & Session Configuration Report.

## 4. What "One Framework" Actually Bought

Before this phase, fixing "add the Secure cookie flag to the consultant portal" meant editing `consultant-portal/login.php`, `_session.php`, and `index.php` separately, and hoping the next person who touched any of them didn't reintroduce drift. After this phase, that same class of fix is made once, in `session_common.php`, and every surface inherits it automatically. This is the practical meaning of Objective 2 ("no duplicated implementations") — not just fewer lines of code, but a single point of correctness.

## 5. Duplicated Helper Removed, None Found to Remove Beyond Session Handling

Objective 10 asks for authentication-helper duplication to be eliminated. Beyond the session bootstrapping itself (addressed above), the audit for this phase found no other duplicated authentication *logic* — password verification (`password_verify()`), CSRF generation/verification (`generateCsrfToken()`/`verifyCsrfToken()` in `config/functions.php`), and the new brute-force helpers (`portalLoginBlocked()`/`recordPortalLoginFailure()`, also centralized in `config/functions.php` and shared by both portal logins rather than written twice) were already, or are now, single implementations reused everywhere.

## 6. Full Detail By Topic

This report is the index; mechanism-level detail for each area lives in its own deliverable:
- Session lifecycle specifics (rotation timing, idle timeout, fixation defense) — **Session Security Report**
- Cookie flags per surface — **Cookie & Session Configuration Report**
- CSRF implementation — **CSRF Review Report**
- Login flow, lockout, brute-force — **Login Security Report**
- Consultant module CRUD fix — **Consultant Module Report**
- Employee Portal specifics — **Employee Portal Security Report**
- Temp Portal specifics — **Temporary Employee Portal Report**

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial Phase 2 authentication framework report | Remediation Program — Phase 2 |
