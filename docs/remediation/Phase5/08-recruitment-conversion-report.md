# Komagin HR — Phase 5 Stage 5.7: Recruitment-to-Employee Conversion

**Document type:** Phase 5 Deliverable — Stage 5.7 Report
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13

---

## 1. Scope Decision (Recap)

KOM-088: `recruitment_applications.converted_to_employee_id` existed in the schema, but no code path anywhere ever set it — an applicant marked "Selected" had no connection to the employee record eventually created for them; HR had to re-key every field by hand into a disconnected Add Employee form, and the two systems had no traceable link. Per user decision, built a **guided conversion**, not a fully separate system: the applicant's existing Add Employee form pre-fills from the application and records the link on save, rather than duplicating the employee-creation form as a second, parallel implementation to maintain.

## 2. What Was Built

- **`modules/recruitment/index.php`**: applications with `status = 'selected'` and no existing `converted_to_employee_id` now show a "Convert to Employee" action (visible only to users holding both `recruitment.review` approve and `employees.create` create permissions). Once converted, this is replaced with a "View Employee" link to the resulting record — makes the conversion state visible and prevents a second attempt through the UI.
- **`modules/employees/add.php`**: accepts an optional `?from_application=<id>` parameter.
  - On GET, if the application is genuinely convertible (status `selected`, `converted_to_employee_id IS NULL`) and the current user holds `recruitment.review`, the form pre-fills first name, last name, email, and phone from the application, and shows a banner naming which application is being converted. Any other case (invalid ID, wrong status, already converted, missing permission) silently falls through to the ordinary blank Add Employee form — no error, no information disclosure.
  - The application ID travels through as a hidden field, so it survives to the POST handler.
  - On successful employee creation, the same convertible-state condition is **re-checked** at POST time via the `UPDATE ... WHERE status='selected' AND converted_to_employee_id IS NULL` guard, so a stale form (e.g., two tabs open, or the application moved to a different pipeline stage in the interim) cannot double-convert or silently overwrite a link set by someone else in the meantime. Only if that guard's row-count confirms the update actually happened does an audit entry get written and the success message mention the link.
- All of `add.php`'s existing validation, duplicate-email/national-ID checks, employee numbering, kiosk PIN generation, default leave balances, and HR notification continue to apply unchanged — conversion is additive, not a parallel code path with its own rules to drift out of sync.

## 3. Live Verification

Used a disposable application (`P5TestConv`/`p5testconv@example.com`, status `selected`) and superadmin session (permission bypass covers both required permissions, matching the same testing approach used throughout this phase).

1. **Pre-fill**: `GET add.php?from_application=<id>` — confirmed `first_name`, `last_name`, `phone`, `email` fields pre-populated from the application, banner rendered, hidden `from_application` field present.
2. **Conversion**: completed the form (adding the required `start_date`/`employment_type`) and submitted — new employee created (`id=25`, `KOM-EMP-2026-0014`); `recruitment_applications.converted_to_employee_id` correctly set to `25`; audit log entry `recruitment` / `convert_to_employee` recorded with the employee number.
3. **Re-conversion blocked**: re-requested `add.php?from_application=<id>` — the pre-fill banner no longer appears (application no longer matches the convertible-state query) — falls through to a blank form rather than re-linking.
4. **UI reflects state**: the Recruitment applications list now shows "View Employee" (linking to the created record) in place of "Convert to Employee" for this application.
5. **Baseline unaffected**: the ordinary `add.php` (no `from_application` parameter) renders identically to before — 200 OK, no conversion banner, normal blank form.
6. All disposable test rows (employee, its auto-created `leave_balances`/`employee_status_history`, notifications, audit log entries, the test application) removed and verified absent.

## 4. Regression

- Phase 1 suite: **20/20 passed**.
- Phase 2 suite: **29/29 passed**.
- Zero regressions.

## 5. Register / Change Control

- **Master Remediation Register**: KOM-088 closed.
- **Change Control Log**: CC-113.
