# Komagin HR — Temporary Employee Portal Report

**Document type:** Phase 2 Deliverable #5 of 11
**Objective addressed:** Objective 8 — Temp Employee Portal
**Finding addressed:** KOM-029 (M-06)
**Date:** 2026-07-11/12

---

## 1. Before: Independent, Weaker Session Handling

`employee-portal/temp_portal.php` ran entirely outside the shared portal session guard:

```php
session_set_cookie_params(['lifetime'=>28800,'path'=>'/','httponly'=>true,'samesite'=>'Strict']);
session_start();
if (empty($_SESSION['ep_is_temp']) || empty($_SESSION['ep_temp_employee_id'])) { ... }
```

No `Secure` cookie flag, no 30-minute ID rotation, no idle timeout — a temp employee's session, once created, stayed valid for the full 8-hour absolute cookie lifetime regardless of how long the device sat unattended. This was a real gap given temp/contract staff are, by the nature of the role, more likely to use shared or site-provided devices.

## 2. After: Same Framework, Same Guarantees, As the Permanent-Employee Portal

```php
require_once dirname(__FILE__) . '/_config.php';
require_once dirname(__FILE__) . '/_session.php';
epRequireTempLogin();
```

`_session.php` — the same file every other employee-portal page includes — now handles cookie configuration, rotation, and idle timeout via the shared `bootstrapSession('ep_', 28800)`. This is possible with zero duplication because permanent and temporary employees already share the same login entry point (`employee-portal/login.php`) and land in the same session store under the same `'ep_'` key prefix — only the specific session keys differ (`ep_employee_id`+`ep_policy_agreed` for permanent, `ep_is_temp`+`ep_temp_employee_id` for temp).

**New helper functions** added to `_session.php` to make this distinction explicit rather than ad hoc:
```php
epIsTempLoggedIn(): bool     // checks ep_is_temp + ep_temp_employee_id
epRequireTempLogin(): void   // redirects to login.php if not
```

## 3. Logout Also Fixed

The page's own inline `?action=logout` handler previously called bare `session_destroy()`. It now calls the shared `destroySessionCompletely()` — full teardown plus explicit client-side cookie expiry, matching every other logout in the app (see Session Security Report and CC-023 in the Change Control Log).

## 4. Live Verification

Since no temp employee in this environment's seed data has portal access configured, a real temp-employee record (`KOM-TMP-2026-0001`) had its `portal_active`/`portal_password` temporarily set for testing, then reverted afterward:

1. **Login** — session ID confirmed to change (fixation defense, shared with the rest of the portal — see Session Security Report).
2. **Portal access** — `temp_portal.php` loaded successfully (200) through the new shared session guard.
3. **Logout** — cookie explicitly expired (`Set-Cookie: PHPSESSID=deleted`); subsequent request to the portal correctly rejected (302 to login).

All three passed. Test credentials and any test-generated audit log entries were removed after verification.

## 5. "Remove Independent Session Handling Where Appropriate" — Fully Satisfied

The charter's exact phrasing anticipated that full removal might not always be appropriate; in this case it was — there was no legitimate reason for the temp portal's session handling to differ from the main portal's, since they are the same population of users (temp employees) authenticating through the same login form into the same session store. Zero independent session logic remains in `temp_portal.php`.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial temporary employee portal report with live verification | Remediation Program — Phase 2 |
