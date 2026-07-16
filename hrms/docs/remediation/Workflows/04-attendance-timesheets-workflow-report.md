# Komagin HR — Phase 4 Workflow Group 4: Attendance & Timesheets

**Document type:** Phase 4 Deliverable — Workflow Group Report 4 of N
**Status:** Live-verified against disposable test records and a temporarily-opened kiosk session, both fully cleaned up/restored afterward.
**Date compiled:** 2026-07-12
**Scope:** Manual/kiosk/biometric attendance, clock in/out, late arrivals, overtime, absences, holidays, weekends, calculations, approval workflow; timesheet submission/editing/approval, payroll integration.

---

## 1. Critical Finding — Kiosk Allowed Remote Clock-In Impersonation With No Location Binding (FIXED — closes KOM-003, open since Phase 0)

`modules/attendance/kiosk.php` is deliberately a public, unauthenticated page (a physical terminal has no login). Its location-binding mechanism is a per-location `kiosk_token` in the URL (`?t=...`), generated and distributed by `kiosk_manage.php` when HR opens a location. But when no token was supplied, the code fell back to "whichever `kiosk_sessions` row happens to have `status='open'`" — meaning **anyone who could reach the server, from anywhere, with no login, no CSRF, and just a guessable employee number, could clock any employee in or out** as long as any kiosk location anywhere was open. This is doubly wrong: it's a real security bypass, and it's *also* functionally incorrect the moment more than one location is open at once (which `kiosk_manage.php` explicitly permits) — the fallback would attribute a clock-in to an arbitrary location, not necessarily the requesting terminal's real one.

Confirmed nothing legitimate relied on the fallback: `kiosk_manage.php`'s "Open Kiosk" flow always has a token and always shows HR the token-bearing URL to give the physical terminal.

**Fix:** the fallback is removed. A request with no token, or a token that doesn't match a real session, now shows a clear "not a configured kiosk terminal" message and performs no attendance action.

**Live-verified**: temporarily opened one kiosk location; a request with no token was correctly rejected (structurally and via the actual POST clock-in path — confirmed zero attendance rows were written); a request with the correct token for that location worked normally. Kiosk session restored to its original `closed` state afterward.

**Finding ID:** KOM-003 (pre-existing since Phase 0, now closed)

## 2. Finding — Overtime Approval Had No Duplicate-Action Guard (FIXED)

`modules/timesheets/corrections.php` (the sibling approval page for timesheet corrections) already correctly checks `status === 'pending'` before acting — but `modules/timesheets/overtime.php` had no equivalent check, so an already-approved or already-rejected overtime record could be re-approved or flipped after the fact with no protection at all.

**Fix:** added the same pending-status guard, matching `corrections.php`'s existing pattern, plus a clear error message when a record has already been decided.

**Live-verified**: approved a test overtime record; a second action (attempting to reject the same record) was correctly rejected with the status unchanged.

**Finding ID:** KOM-084 (new, fixed)

## 3. No Findings — Timesheet Approval/Lock Permission Gating

`modules/timesheets/approve.php`'s `approve`/`lock`/`unlock` actions each independently re-check `timesheets.approve:approve` rather than trusting the page-level `view` gate — this is Phase 1's KOM-010 fix, confirmed still correctly in place. No regression found.

## 4. Informational — Payroll Does Not Read Attendance/Overtime Data At All

The charter asks to "ensure payroll reads approved data only" for the timesheets→payroll handoff. In this codebase, that handoff **does not exist**: no file under `modules/payroll/` reads `attendance` or `overtime_records` in any form — payroll here is a flat salary-plus-deductions-plus-savings system, not a timesheet-driven wage calculation. There is therefore no risk of unapproved attendance data leaking into a payslip, but also no automatic overtime-hours-to-pay pipeline at all; approving overtime in the Timesheets module currently has no downstream financial effect anywhere in the system.

This is a genuine completeness gap relative to the charter's expectation, but building an attendance/overtime-to-payroll integration is a substantial feature addition, not a bug fix — parallel to the vacancy-handling gap (Workflow Group 2) and the two-stage leave approval gap (Workflow Group 3, KOM-083). Documented for awareness, not built unilaterally.

## 5. Informational — No Automated Public Holiday Calendar

`overtime_records.type` has a `public_holiday` category and `attendance.status` has a `holiday` value, but both are manually set by whoever is entering the record — there is no `holidays` table or calendar the late-arrival/absence calculations check against. An employee not clocking in on an actual public holiday would be treated identically to an unexplained absence unless someone manually intervenes. Same category as §4: a completeness gap, not a fixable bug, documented rather than built.

## 6. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`kiosk.php`, `overtime.php`) | 0 errors |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group) | 4/4 scenarios: kiosk no-token block (GET + POST paths), kiosk valid-token success, overtime duplicate-action block |

## 7. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-003 — Kiosk allowed remote clock-in impersonation, no location binding (pre-existing, Phase 0) | Critical | **Fixed** |
| KOM-084 — Overtime approval had no duplicate-action guard | Medium | **Fixed** |
| Payroll never reads attendance/overtime data (informational) | — | **Documented, not built** |
| No automated holiday calendar (informational) | — | **Documented, not built** |

**Both actionable findings fixed and live-verified.** Two completeness gaps documented for awareness, consistent with how similar scope-expanding feature gaps were handled in earlier workflow groups.
