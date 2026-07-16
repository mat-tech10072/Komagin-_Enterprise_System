# Komagin HR — Phase 4 Workflow Group 10: Temporary Employee Module

**Document type:** Phase 4 Deliverable — Workflow Group Report 10 of N
**Status:** Live-verified against disposable test temp employees, fully cleaned up afterward.
**Date compiled:** 2026-07-13
**Scope:** Temp employee lifecycle → Project/Site assignment → Attendance capture → Portal access → Deletion.

---

## 1. Security Finding — Reflected XSS in the Export Status Banner (KOM-020, pre-existing since Phase 0)

`modules/temp_employees/index.php`'s PDF export (`?export=pdf&status=...`) echoed `$_GET['status']` completely unescaped in the "Filtered:" banner, while the adjacent search-term display on the same line correctly called `htmlspecialchars()`. This finding was documented in the original baseline audit (Audit II, NH-03) and correctly fell out of scope for Phase 2 (authentication/session hardening, not output encoding) — it was never picked up again until this workflow group's review.

**Live-verified before the fix:** `?export=pdf&status=<script>alert(1)</script>` rendered the raw, executable `<script>` tag directly into the exported HTML page (opened in a new tab via the PDF export link, with `window.print()` firing on load).

**Fix:** wrapped the value in `htmlspecialchars()`, matching the pattern already used one line above it for `$exportSearch`.

**Live-verified after the fix:** the same payload now renders as inert, HTML-entity-encoded text (`&lt;script&gt;alert(1)&lt;/script&gt;`) — zero occurrences of the raw tag in the response.

**Finding ID:** KOM-020 (pre-existing, fixed)

## 2. Fixed — Temp Employee Deletion Had No Safety Confirmation (KOM-091)

`modules/temp_employees/delete.php` was a single-click, JS `confirm()`-only instant hard delete, identical in kind to the gap closed in Workflow Group 9 for consultants (KOM-089) — no server-side confirmation, no way to recover an accidental deletion. Unlike consultants, this table has no cascading child tables in the schema, so there's no attendance/scope history at stake, but the record itself (contract dates, rate, notes, portal access) is still permanently lost with a single accidental click.

**Fix:** rewrote `delete.php` to the same GET-confirmation-page + type-the-`employee_number`-to-confirm pattern used by `employees/delete.php` and (this phase) `consultants/delete.php`. Updated both Delete buttons (index list and detail view) from instant form-submits to links to the confirmation page.

**Live-verified**: created a disposable test temp employee; confirmation page loaded correctly; wrong confirmation text correctly rejected (record still present); correct employee number correctly deleted it.

**Finding ID:** KOM-091 (new, fixed)

## 3. Fixed — Audit Trail Corrupted for Every Create/Edit/Delete in This Module (KOM-092)

While live-verifying KOM-091's deletion, the resulting `audit_logs` row showed `record_id=1` — the logged-in admin's own user ID — instead of the deleted temp employee's actual ID (9). Investigation found all three of `add.php`, `edit.php`, and `delete.php` called `auditLog()` with the wrong argument order:

```php
auditLog('temp_employees', 'delete', $_SESSION['user_id'], 'temp_employees', $id, "...");
```

The real signature is `auditLog(module, action, recordId, oldValue, newValue, reason)`. Positionally, this meant the acting admin's user ID landed in `record_id`, the literal string `'temp_employees'` landed in `old_value`, and the temp employee's real ID was silently discarded into `new_value`. The human-readable `reason` text was unaffected (so the audit log wasn't completely opaque), but any structured query against `record_id` for this module — "show me the audit history for employee X" — was returning the wrong rows or none at all. Cross-checked: `employees/delete.php`, `employees/edit.php`, and this phase's `consultants/delete.php` all call the function correctly; the bug is confined to this module's three files.

**Fix:** corrected all three call sites to `auditLog('temp_employees', '<action>', $id, null, null, "...")`.

**Live-verified**: created, edited, then deleted a disposable test temp employee — all three resulting `audit_logs` rows now show the correct `record_id` matching the actual affected row (confirmed `record_id=10` for a test record with id 10, both before and after the fix, directly against the database).

**Finding ID:** KOM-092 (new, fixed)

## 4. Partially Fixed, Per User Decision — Attendance-Capture UI Claimed a Feature That Doesn't Exist (KOM-090)

The Attendance Method selector (Add/Edit/View pages) let HR choose "Kiosk Only," "Timesheet Only," or "Both" for each temp employee, and described "Kiosk Only" as *"Clock in/out via the kiosk tablet."* Investigation found this claim is false for every temp employee:

- `modules/attendance/kiosk.php` authenticates purely via `getEmployeeByNumber()`, which queries the `employees` table only — a `KOM-TMP-####` number is always rejected as "Employee ID not found."
- The "Timesheet Only" option (`modules/temp_employees/timesheet.php`) downloads a blank, printable Excel/PDF form intended to be filled by hand — there is no digital entry screen anywhere to re-enter that data into the system.
- No `temp_attendance` (or equivalent) table exists in the schema at all.

Net effect: **no temp employee's worked hours or days are ever captured anywhere in the system**, digitally or otherwise, despite the UI presenting attendance tracking as a working, configurable feature. This also means temp employee payroll (which is entirely outside the Payroll module — confirmed no file under `modules/payroll/` references `temp_employees`, the same informational pattern already documented for consultants in Workflow Group 9) cannot be calculated from any system-recorded attendance data, since none exists.

Building the real capture mechanism — a new attendance table, kiosk lookup wired to recognize temp employees, and/or a digital timesheet entry screen — is a substantial, multi-file feature addition, not a bug fix. This was flagged for an explicit product decision rather than built unilaterally.

**User decision (2026-07-13):** correct the misleading UI copy only; do not build the underlying capture mechanism this phase.

**Fix applied:** updated the Kiosk/Timesheet/Both descriptions in `add.php`, `edit.php`, and `view.php` to state plainly that neither method currently captures attendance data digitally for temp employees, rather than implying a working feature. No new table, no kiosk changes, no digital timesheet entry screen — the underlying gap remains and is documented for a future phase.

**Live-verified**: corrected copy renders on the Add, Edit, and View pages.

**Finding ID:** KOM-090 (new, partially fixed — UI corrected, capture mechanism deferred)

## 5. Minor Hygiene — Dead Code Removed (no finding number)

`modules/temp_employees/index.php` ran a wasted `COUNT(*)` query via a convoluted one-line ternary (`(int)db()->prepare(...)->execute(...) ? db()->prepare(...) : 0`) whose result was immediately discarded and overwritten two lines later by the actual, correct count query. Zero behavior impact — removed as a minor cleanup while in the file.

## 6. Clean Areas Confirmed

- Cross-checked `add.php`'s `INSERT` and `edit.php`'s `UPDATE` against the live `temp_employees` schema directly (the recurring "column doesn't exist" bug class found in Workflow Groups 6–8): every column matches, no repeat of that bug class here.
- `employee-portal/temp_portal.php` and `employee-portal/login.php`'s temp-employee branch already share the same hardened session guard, ID rotation, and idle-timeout logic as the rest of the employee portal (established in Phase 2) — no changes needed.
- Permission slugs `temp_employees.{view,create,edit,delete}` all exist and are used consistently; no orphaned or missing slugs found in this module.

## 7. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`add.php`, `edit.php`, `view.php`, `index.php`, `delete.php`) | 0 errors |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group) | XSS payload neutralized; delete confirmation page loads/rejects/accepts correctly; audit log `record_id` correct for create/edit/delete; corrected UI copy renders on all three pages |

## 8. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-020 — Reflected XSS via `$_GET['status']` in the PDF export banner | High | **Fixed** |
| KOM-092 — `auditLog()` wrong argument order, corrupting the module's audit trail | High | **Fixed** |
| KOM-091 — Temp employee deletion had no safety confirmation | Medium | **Fixed** |
| KOM-090 — Attendance-method UI claimed a working capture feature that doesn't exist | Medium | **Partially fixed** (UI corrected; capture mechanism deferred per user decision) |

**Three of four findings fully fixed and live-verified; the fourth had its misleading aspect corrected per explicit user direction, with the larger feature-build question deliberately left for a future phase.**
