# Komagin HR — Phase 4 Workflow Group 9: Consultant Module

**Document type:** Phase 4 Deliverable — Workflow Group Report 9 of N
**Status:** Live-verified against a disposable test consultant, fully cleaned up afterward (self-deleting by the nature of the test).
**Date compiled:** 2026-07-12
**Scope:** Consultant lifecycle → Assignments → Clocking → Payments → Completion → Archiving.

---

## 1. Context — This Module Was Already Extensively Hardened in Phase 2

Unlike every other workflow group so far, the Consultant Module's core CRUD, portal login, kiosk clock-in/out, and logout flows were already thoroughly fixed and live-tested in Phase 2 (KOM-002 and related findings — undefined CSRF function, session fixation, missing CSRF on kiosk/scope actions). Cross-checked `add.php`'s `INSERT` against the live `consultants` schema directly (the exact bug class found repeatedly in Workflow Groups 6–8): every column matches. No repeat of that bug class here.

## 2. Fixed — Consultant Deletion Had No Safety Confirmation, Unlike the Established Pattern

`modules/consultants/delete.php` was a single-click, JS `confirm()`-only instant hard delete — no server-side impact preview, no type-to-confirm safeguard — despite cascading to a consultant's entire `consultant_attendance` and `consultant_scopes` history. This is inconsistent with the established, already-proven pattern for the exact same class of action: `modules/employees/delete.php` (full impact-count preview + type-the-identifier-to-confirm) has existed since before this program began. A single accidental click on the Consultants list could permanently erase a consultant's whole history with no warning beyond a browser popup.

**Fix:** rewrote `delete.php` to match the employees pattern — a GET confirmation page showing an impact summary (attendance/scope record counts) and requiring the exact `consultant_number` to be typed before the `POST` proceeds. Updated the Consultants list's Delete button from an instant form-submit to a link to this confirmation page.

**Live-verified**: created a disposable test consultant; the confirmation page loaded correctly with the impact summary; submitting the wrong confirmation text was correctly rejected (record still present); submitting the correct consultant number correctly deleted it.

**Test-script update, not a product regression**: the Phase 2 regression suite's consultant-delete check POSTed directly with no confirmation step (matching the *old* instant-delete behavior it was written against) — updated it to fetch the confirmation page and submit the real `consultant_number`, consistent with how the employees-module equivalent test already worked. Re-run after the fix: 29/29 passing again.

**Finding ID:** KOM-089 (new, fixed)

## 3. Informational — No Consultant Payment/Invoicing Connection to Payroll

`consultants.hourly_rate`/`daily_rate`/`contract_value` exist, and `consultant_attendance.total_hours` is tracked, but nothing in the codebase connects consultant attendance to any payment or invoice generation — consultants are not paid through the Payroll module at all (confirmed: no file under `modules/payroll/` references `consultants` or `consultant_attendance`). This may well be intentional — contractors are commonly paid via a separate accounts-payable process outside a payroll system built for employees — so this is documented for awareness only, matching the same category of finding as Workflow Group 4's payroll/attendance disconnect, not treated as a defect.

## 4. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`delete.php`, `index.php`) | 0 errors |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed (after updating the consultant-delete test to match the new confirmation flow) |
| Live functional tests (this group) | 3/3 scenarios: confirmation page loads with correct impact summary, wrong confirmation text blocked, correct confirmation deletes |

## 5. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-089 — Consultant deletion had no safety confirmation, unlike the established employee-delete pattern | Medium | **Fixed** |
| No consultant payment/invoicing connection to payroll (informational) | — | **Documented, not built** |

**The one actionable finding is fixed and live-verified.**
