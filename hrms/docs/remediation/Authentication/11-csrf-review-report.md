# Komagin HR — CSRF Review Report

**Document type:** Phase 2 Deliverable #6 of 11
**Objective addressed:** Objective 5 — CSRF Standardization
**Findings addressed:** KOM-002, KOM-013, KOM-026, KOM-027, KOM-050, KOM-062
**Date:** 2026-07-11/12

---

## 1. The Standard

One mechanism, `generateCsrfToken()`/`verifyCsrfToken()` in `config/functions.php`, already existed and was already correct (CSPRNG-backed generation, constant-time `hash_equals()` comparison). Phase 2's job was coverage, not invention: find every state-changing endpoint that didn't use it, and make it do so — "never rely on GET for state-changing actions," per the charter.

## 2. Coverage Table — Every Endpoint Named in the Charter

| Endpoint | Before | After |
|---|---|---|
| Consultant Portal login form | No token | Token generated + verified |
| Consultant Portal kiosk (5 forms: clock-in, break-out, break-in, clock-out ×2 states) | No token on any form or handler | All 5 forms carry a token, verified before any DB write |
| Consultant Portal scope notes | No token | Token added, verified before `UPDATE` |
| Employee Portal hub request | No token | Token added, verified before `INSERT` |
| Notifications `mark_read`/`mark_all_read` | GET request, no token at all | POST-only + token required; `list`/`count` correctly remain GET (pure reads) |
| Self-service per-link update form | Had a token, but compared with `!==` instead of `hash_equals()` | Now uses `hash_equals()`, matching the standard helper's comparison method |
| Consultants module (`add`/`edit`/`delete`/`scope_save`) | Called an undefined function (`validateCsrfToken`), so verification never actually ran — every write threw a fatal error before the check could even matter | Calls the real `verifyCsrfToken()`; full CRUD confirmed working (see Consultant Module Report) |

## 3. The Notifications Fix in Detail — GET-to-POST Migration

This was the one case requiring a client-side JS change, not just a server-side check, since `mark_read`/`mark_all_read` were called via `fetch()` with the default GET method:

1. `includes/footer.php` now exposes `window.CSRF_TOKEN` (alongside the pre-existing `window.APP_URL`).
2. Both `fetch()` calls changed from `GET .../notifications.php?action=mark_read&id=...` to a `POST` with a URL-encoded body carrying `action`, `id` (where applicable), and `csrf_token`.
3. `api/notifications.php` now rejects both actions unless `$_SERVER['REQUEST_METHOD'] === 'POST'` **and** `verifyCsrfToken()` passes, returning HTTP 403 otherwise. `list` and `count` — genuine reads with no side effect — were deliberately left as GET; forcing every API action to POST regardless of whether it mutates anything would be over-correction, not standardization.

**Live verification:** GET rejected; POST without a token rejected; POST with a token extracted from an actual page load (proving the token round-trips correctly from server-rendered page to client JS to a subsequent request) succeeded.

## 4. Design Note — Redirect vs. Raw Response on CSRF Failure

Most CSRF failures in this app redirect back to the originating page with a flash error message (matching the existing `requirePermission()` pattern from Phase 1). `api/notifications.php`'s failure path was deliberately kept as a raw `403` + JSON body instead, since it's an AJAX endpoint — a `Location:` redirect would be meaningless to a `fetch()` caller expecting JSON. This is a conscious exception to the redirect pattern, not an inconsistency; documented here so it isn't mistaken for one in a future review.

## 5. What Was Already Correct, Confirmed Not Regressed

`auth/login.php`, `modules/employees/generate_link.php`, `auth/change_password.php`, and every module-level write handler touched in Phase 1 already carried and verified CSRF tokens correctly before this phase began — none of that was modified, and the full-repository syntax/smoke test performed at the close of this phase confirms nothing broke.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial CSRF review report with full endpoint coverage table | Remediation Program — Phase 2 |
