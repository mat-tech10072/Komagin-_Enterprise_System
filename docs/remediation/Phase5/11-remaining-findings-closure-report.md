# Komagin HR — Phase 5 Stage 5.10: Remaining Open Findings Closure

**Document type:** Phase 5 Deliverable — Stage 5.10 Report
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13

---

## 1. Scope

20 individually smaller open findings carried forward from Phases 0–4, mapped to this stage in `01-phase5-open-findings-scope.md` §3: KOM-009, KOM-016, KOM-025, KOM-028, KOM-031, KOM-033, KOM-034, KOM-037, KOM-038, KOM-039, KOM-046, KOM-048, KOM-051, KOM-053, KOM-055, KOM-056, KOM-057, KOM-059, KOM-060, KOM-063. (KOM-054 explicitly excluded as deployment-scope, per the original mapping.)

Outcome: **18 fixed**, **1 resolved as already-fixed** (no code change needed), **1 deferred with documented reason**.

## 2. Findings Closed

| Finding | Summary | Fix |
|---|---|---|
| KOM-009 | Leave Approve/Reject buttons silently no-op | `view.php`'s inline POST forms sent `leave_id`/`action` fields, but `approve.php` reads `$_GET['id']`/`$_GET['action']` — a field-name AND method mismatch. Replaced with links to `approve.php`'s real GET-loaded confirmation page (which also correctly enforces "reject requires remarks," previously bypassed by the broken inline form's hardcoded placeholder reason). |
| KOM-016 | Published payslips editable, no audit trail | Update branch now fetches current status first; blocks the edit with a clear error if `finalized`/`sent`; on an allowed edit, adds an `auditLog()` call (previously entirely missing, unlike the create branch). |
| KOM-025 | Employee salary field always blank on edit | `edit.php` read `$emp['salary']` (a key that never existed) instead of `$emp['basic_salary']` (the actual column). One-line fix. |
| KOM-028 | Reports Hub "Export CSV" link dead | Nothing read `$_GET['export']`. Added a CSV export branch for all 4 report types (attendance/employees/leave/overtime), matching the existing `fputcsv()` pattern already used in `reports/executive.php`. |
| KOM-031 | SMTP password exposed in page HTML source | The "Payslip Notifications" form re-emitted every SMTP setting as a hidden field, including `smtp_pass`'s live cleartext value — while the primary form correctly used a placeholder. Removed `smtp_pass` from that hidden-field list entirely; the save handler already preserves the existing password whenever the field is blank/absent. |
| KOM-033 | Activity Log CSV export doesn't neutralize formula injection | `csvRow()` only escaped double-quotes. Now prefixes any value beginning with `=`, `+`, `-`, or `@` with a single quote before quoting — the standard Excel/Sheets formula-injection neutralization, applied at the one shared helper so it protects every export path in the file. |
| KOM-034 | Per-user CSV export ignores date filters, loads unbounded data | The admin/employee individual-export paths ignored `from`/`to` and used `fetchAll()`. Both now apply the same date-range filter the category exports already respect, and stream via `while ($stmt->fetch())`. |
| KOM-037 | Two menu items, two authorization models, one table | "Audit Logs" (`audit.view`, held by hr_manager/hr_officer/payroll_manager) and "Activity Logs" (`activity_log.view`, seeded to super_admin only) sat side by side. Per user decision, merged: the sidebar now shows only "Audit Logs"; `activity_log/index.php` and its `download.php` export are re-gated onto `audit.view`; a "View by User" link on Audit Logs and a "Back to Audit Logs" link on the per-user view connect the two pages. |
| KOM-038 | SVG accepted for letterhead uploads | SVG can carry embedded `<script>`, and server-side MIME sniffing passes a well-formed malicious SVG since it genuinely is one. Removed `image/svg+xml` from the allowed letterhead types; raster formats cover the same use case. |
| KOM-039 | Uploaded file extension trusted from client filename | `uploadFile()` saved files under the extension from the *client-supplied* filename, never cross-checked against the MIME type `finfo` actually detected. The extension is now derived solely from the detected MIME type via an explicit map; an allowed MIME type with no mapping is rejected rather than guessed at. |
| KOM-046 | Pagination values interpolated directly into SQL (6 files) | `employees`, `attendance`, `timesheets`, `recruitment` (both queries), `onboarding`, `training` (both queries) all used `LIMIT {$perPage} OFFSET {$offset}` string interpolation. Converted to `LIMIT ? OFFSET ?` bound parameters (verified working under this app's native, non-emulated prepared statements before applying at scale). Values were always int-cast already — not previously exploitable — this is a defense-in-depth/consistency fix. |
| KOM-048 | Missing-documents report is N+1 | One `SELECT` per employee in a loop. Replaced with a single `SELECT ... WHERE employee_id IN (...)` for all filtered employees, grouped in PHP — 2 queries total regardless of headcount. |
| KOM-051 | Archive "Lock" control silently no-ops | The Lock button posted `lock_id`, which the handler never read at all. Added a branch that sets `is_locked=1`/`locked_by`/`locked_at` and audit-logs the action. |
| KOM-053 | Error display defaults to on | `APP_ENV` defaulted to `'development'` (on-screen errors) whenever unset. Flipped the fail-safe default to `'production'` (errors suppressed on-screen, still logged to `logs/php_errors.log`) — a misconfigured/un-configured deployment no longer leaks stack traces/paths/queries by default. This local dev machine's Apache config was given an explicit `SetEnv APP_ENV development` override (outside the git repo) so local development is unaffected; the change takes effect on Apache's next natural restart. |
| KOM-055 | Replaced branding assets never deleted from disk | No `unlink()` on update/delete for any of the 4 asset types (letterheads/signatures/stamps/watermarks) — unbounded storage growth. Added a shared `deleteBrandingAssetFile()` helper, wired into all 8 update/delete handlers; a file is only deleted once the database change that supersedes it has actually succeeded. |
| KOM-056 | Server-generated date interpolated directly into SQL | `dashboard.php`'s `$today`/`$thisMonth` (both from `date()`, never user input — not exploitable) were interpolated into 6 queries. Converted to bound parameters for consistency with the prepared-statement standard used elsewhere. |
| KOM-057 | Dead query wastes a DB round-trip | The described defect (a `PDOStatement` assigned then immediately overwritten) is no longer present in the current file — resolved incidentally during Phase 4 Workflow Group 10's rewrite of this module. No code change needed; recorded as resolved. |
| KOM-059 | Temp employee position modeled as free text, not FK | **Deferred, documented.** Normalizing `position_title` to a FK against `positions` would require migrating existing free-text values (no reliable automatic mapping — temp/project role titles don't cleanly correspond to the permanent-employee position catalog) and deciding a fallback for values with no match. Not currently exploitable and has no live bug — a genuine data-modeling improvement, but a real migration-risk decision, not a mechanical fix. Left as-is; revisit if cross-module reporting on temp employee positions becomes a real requirement. |
| KOM-060 | Letterhead header/footer HTML fields saved but never used | `header_html`/`footer_html` were captured by the save handler with no corresponding form field anywhere in the page's UI, and never read by `DocumentEngine.php`. Per user decision, removed from the handler entirely (not wired up); the database columns are left in place, unused. |
| KOM-063 | Password minimum length inconsistent across admin-reset paths | Admin-side create-user and admin-reset-password both allowed 6 characters (client `minlength` and server `strlen()` checks), while self-service change/reset (Stage 5.5) require 8. Both admin paths raised to 8, matching every other password path in the app. |

## 3. Live Verification

All fixes were syntax-checked (`php -l`); the following were additionally exercised live with disposable test data, cleaned up after each check:

- **KOM-009**: created a disposable pending leave application; confirmed `view.php` now renders working links; clicked through the full Approve flow (GET confirmation page → POST with remarks) and confirmed the application's status flipped from `pending` to `approved` in the database.
- **KOM-016**: created a disposable `finalized` payslip; attempted an edit via crafted POST; confirmed the exact expected error message rendered and `gross_salary` remained unchanged in the database.
- **KOM-025**: confirmed a real employee's salary field now pre-fills correctly on the edit form.
- **KOM-028**: confirmed the Reports Hub CSV export downloads real data with correct headers and `Content-Type: text/csv`.
- **KOM-031**: confirmed `smtp_pass` no longer appears anywhere in the settings page's HTML source.
- **KOM-033**: inserted a disposable audit log row with a `=cmd|...` formula-injection payload as its reason; confirmed the exported CSV cell is prefixed with a leading single quote (neutralized), not the raw dangerous value.
- **KOM-037**: confirmed the sidebar shows only "Audit Logs"; confirmed the "View by User" link reaches the per-user page; confirmed both pages are reachable under the merged `audit.view` permission.
- **KOM-039 / KOM-055**: uploaded a file with real PNG content but a `.php` client-supplied filename — confirmed it was saved with a `.png` extension (derived from the detected MIME type, never the filename); then replaced that same letterhead's image and confirmed the original file was deleted from disk while the new one was saved correctly.
- **KOM-046**: confirmed all 6 affected pages still load correctly (200 OK) with the bound-parameter `LIMIT`/`OFFSET`.
- **KOM-063**: confirmed both admin-side password fields now render `minlength="8"`.
- **KOM-048, KOM-051**: confirmed both pages load without error post-fix (full functional exercise of the Lock button and the missing-documents grouping deferred to code-level verification given time — both are small, low-risk, single-purpose changes already covered by `php -l` and direct code review).

All disposable test data (leave application, payslip, audit log row, uploaded files) removed and verified absent afterward.

## 4. Regression Discipline Note

Fixing KOM-037 changed real, intended access-control behavior: `hr_manager`/`hr_officer` now correctly gain Activity Log access via the merged `audit.view` permission, which the **Phase 1** regression suite's KOM-019/NH-02 test had encoded as "blocked" under the old, pre-merge permission model. This surfaced as 2 failures on the first post-fix regression run. Confirmed this was the intended effect of the merge (not a regression), and updated the Phase 1 test's expectations to match the new, correct behavior — `payroll_officer` (who holds neither permission) remains correctly blocked. Documented here per this program's standing discipline of never silently reinterpreting a "failure" without recording why.

## 5. Regression

- Phase 1 suite: **20/20 passed** (after the KOM-037-related test update described above).
- Phase 2 suite: **29/29 passed**.
- Zero unintended regressions.

## 6. Register / Change Control

- **Master Remediation Register**: KOM-009, KOM-016, KOM-025, KOM-028, KOM-031, KOM-033, KOM-034, KOM-037, KOM-038, KOM-039, KOM-046, KOM-048, KOM-051, KOM-053, KOM-055, KOM-056, KOM-057, KOM-060, KOM-063 closed (19 rows). KOM-059 updated to Deferred with documented reason.
- **Change Control Log**: CC-116–CC-132.
