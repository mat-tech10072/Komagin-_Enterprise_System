# Komagin HR — Phase 4 Workflow Group 13: Reports & Dashboards Consistency

**Document type:** Phase 4 Deliverable — Workflow Group Report 13 of N
**Status:** Live-verified against real data with disposable test attendance rows, cleaned up afterward.
**Date compiled:** 2026-07-13
**Scope:** Verify report calculations match source data; verify dashboard numbers equal database numbers. Covers `dashboard.php`, `modules/attendance/index.php`, and `modules/reports/{index,executive,employees,timesheets}.php`.

---

## 1. High — Systemic Finding: `attendance.is_absent` Has Been Dead Code Since Introduction (KOM-098)

While reviewing the Dashboard's "Absent Today" KPI against this workflow group's explicit charter ("dashboard numbers equal database numbers"), found that `attendance.is_absent` — a column read by `WHERE is_absent=1` / `SUM(is_absent)` in **six separate files** (`dashboard.php`, `modules/attendance/index.php`, `modules/reports/index.php`, `modules/reports/executive.php`, `modules/reports/timesheets.php`, and `employee-portal/attendance.php`) — is **never written by any code path anywhere in the codebase**. Confirmed by a full-repository search: no `INSERT` or `UPDATE` statement anywhere sets it. It defaults to `0` in the schema.

Compounding this: an `attendance` row is only ever created when an employee actually clocks in via the kiosk (`modules/attendance/kiosk.php`'s `sign_in` action). An employee who never shows up — a genuine, unexcused absence — never gets a row at all, so there is no row for `is_absent` to be set on even in principle.

**Net effect:** every "Absent" figure that reads this column has been structurally guaranteed to return 0, permanently, since the day the column was introduced. This isn't a rare edge case — it's mathematically impossible for it to ever show a nonzero value through any of these six call sites.

**Live-verified before the fix:** with 13 active employees and 0 attendance records for today, the Dashboard's "Absent Today" KPI showed **0**. Its own sub-label already read "Not clocked in" — strong evidence the original intent was exactly the calculation now implemented, just miswired against a column nothing ever populates.

### Fix scope and reasoning

This bug spans two genuinely different problems that need different treatment:

**Daily figures (fixed):** "Absent Today" and similar single-day counts don't need any calendar knowledge — they can be honestly computed as *active/probation employees who have no attendance row for that specific date*. Fixed in:
- `dashboard.php` — "Absent Today" KPI and the 7-day attendance trend chart's daily absent counts.
- `modules/attendance/index.php` — the day-summary "Absent" KPI card, respecting the page's existing department filter.

**Monthly/period figures (documented, not fixed):** `modules/reports/index.php`'s per-employee monthly `absent_days`, `modules/reports/executive.php`'s current-month attendance KPI, and `modules/reports/timesheets.php`'s per-employee monthly absent count all have the identical dead-column problem, but fixing them correctly requires knowing which calendar days were actually *expected* working days for each employee — weekends, public holidays, and any individual work-schedule variation. No such calendar exists anywhere in this codebase (confirmed: no `working_days`/holiday-calendar setting, and — as already flagged informationally in Workflow Group 4's report — no cron/scheduled-task infrastructure of any kind). A naive fix (active employees × calendar days elapsed, minus days present) would count every weekend as an absence for every employee, replacing one wrong number (always 0, easy to eventually notice as suspicious) with a different, more confidently-wrong one (a large, "precise-looking" but still-incorrect absence count) — worse, not better. Left documented rather than attempting a fix that would introduce new, more visible incorrect data.

**Live-verified after the fix:**
- With 13 active employees, 0 clocked in: Dashboard and Attendance-module day view both correctly showed **13 absent**.
- Inserted one disposable test attendance row (simulating a clock-in): both correctly dropped to **12**.
- Removed the test row: both correctly returned to **13**.

**Finding ID:** KOM-098 (new, partially fixed — High)

## 2. Fixed — Dashboard "Pending Approvals" Header Disagreed With Its Own Rows (KOM-099)

`dashboard.php`'s `$totalPending` summed only 4 of the 5 categories the "Pending Approvals" card actually displays: Leave, Timesheet Corrections, Profile Updates, and Overtime were included; `$pendingRecruitment` was fetched, rendered as the card's 5th visible row ("New Applications"), and never added to the running total.

**Live-verified before the fix:** with 3 pending leave, 1 correction, 0 updates, 0 overtime, and 1 recruitment application, the header badge read "**4 pending**" while the 5 visible rows in the same card summed to **5**.

**Fix:** added `$pendingRecruitment` to the `$totalPending` sum.

**Live-verified after the fix:** header badge now correctly reads "**5 pending**," matching the sum of all 5 visible rows.

**Finding ID:** KOM-099 (new, fixed — Low)

## 3. Confirmed Correct — No Changes Needed

- `dashboard.php`'s other KPIs (Total Employees, Active, On Leave, On Probation, Clocked In, Late Today, Open Vacancies, Monthly OT) all directly reflect a single, well-defined `COUNT`/`SUM` query with no derived or dead-column dependency — cross-checked each against its stated label and found no mismatch.
- `modules/reports/employees.php` — a straightforward employee listing/export with department, status, and employment-type filters; no calculated aggregate figures to be wrong.
- `modules/reports/index.php`'s non-attendance report types (`employees`, and others not touching `is_absent`) — no issues found.
- Department distribution chart, leave-by-type chart, contract/probation-expiry alerts, missing-documents count — all directly reflect a single correctly-scoped query.

## 4. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`dashboard.php`, `modules/attendance/index.php`) | 0 errors |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group) | Absent Today 0→13 (correct, matching 13 active/0 clocked-in); test clock-in correctly dropped it to 12, removal correctly restored 13; Pending Approvals header 4→5, matching the sum of its own 5 visible rows |

## 5. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-098 — `attendance.is_absent` dead code, every "Absent" figure structurally wrong | High | **Partially fixed** (daily figures fixed; monthly/period figures documented — need a working-day calendar this codebase doesn't have) |
| KOM-099 — Dashboard "Pending Approvals" header total disagreed with its own visible rows | Low | **Fixed** |

**Both findings addressed to the extent cleanly possible without new infrastructure; the remaining monthly-figure gap in KOM-098 is a genuine architectural dependency (a working-day/holiday calendar), not a quick fix, and is documented for a future phase.**
