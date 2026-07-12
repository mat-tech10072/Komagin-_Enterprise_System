# Komagin HR — Phase 5 Stage 5.3: Working-Day & Holiday Calendar

**Document type:** Phase 5 Deliverable — Stage 5.3 Report
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13

---

## 1. What Was Built

No working-day/holiday calendar existed anywhere in this codebase before this stage — confirmed absent in Phase 4's investigation and carried forward as the remaining half of KOM-098 (dashboard/report "Absent" figures could be fixed for single days, but never for a period, without this).

**Schema** (`database/phase13_workflow_completeness_automation.sql`, applied live and added to the canonical `database/schema.sql`):
- `work_calendar_settings` — single-row settings (same pattern as `company_settings`) holding which ISO weekdays count as working days. Default: Monday–Friday.
- `work_calendar_holidays` — public holidays / organization closure days. Supports a date range (`start_date`/`end_date`, equal for a single day), an `is_recurring_annual` flag (month/day repeats every year), and `is_active` for soft-disable without deleting history.

**Functions** (`config/functions.php`):
- `getWorkCalendarSettings()` — cached settings fetch.
- `isWorkingDay(string $date): bool`
- `getWorkingDaysBetween(string $start, string $end): array`
- `countWorkingDays(string $start, string $end): int`
- `getNextWorkingDay(string $date): string` (bounded to a 30-day look-ahead so a misconfiguration can never loop forever)

Internally, range queries (`getWorkingDaysBetween`) fetch holiday data **once** per call and do all date iteration in PHP — not a query per day.

**Admin UI**: new `modules/settings/calendar.php` (gated by `settings.manage`, matching the convention used by `modules/settings/{index,email}.php`) — toggle working weekdays, add/edit/activate/deactivate/delete holidays, with an app-level duplicate-date-range guard. Linked from the sidebar under Settings.

## 2. Consumers Updated

Per the charter's explicit list ("attendance-rate calculations; absence calculations; leave-day calculations where appropriate; scheduled reminders; reporting period calculations"):

| File | Before | After |
|---|---|---|
| `dashboard.php` — Absent Today | Active employees minus clocked-in, every day including weekends (Phase 4 partial fix) | Same, but now **0 on a non-working day** — no one is expected to be present on a day off |
| `dashboard.php` — 7-day trend | Same as above, per day | Same fix applied per day in the trend |
| `dashboard.php` — Monthly Attendance Rate | `is_absent`-dependent (KOM-098, always ~0% or ~100%) | `(active employees × working days elapsed this month) vs. actual present-instances` — a real, verifiable rate |
| `modules/attendance/index.php` — day summary | Same weekday-blind Absent calculation | 0 on a non-working day for the viewed date |
| `modules/reports/index.php` — attendance report | `attendance JOIN employees` — an employee with zero attendance rows all month never appeared; `absent_days` from dead `is_absent` | `employees LEFT JOIN attendance` — every employee appears; `absent_days` = working days in period so far minus days present |
| `modules/reports/executive.php` — Attendance Rate KPI | `present / total attendance rows` (excluded never-clocked-in employees from the denominator entirely) | `present / (active employees × working days elapsed)` |
| `modules/reports/timesheets.php` — per-employee summary | `absent` counted rows where `status='absent'`, a value nothing ever writes | `absent` = working days in period (capped at today) minus days present |
| `modules/leave/apply.php` — leave day calculation | Hand-rolled loop hardcoding "weekday < 6" (Mon–Fri only), no holiday awareness | `countWorkingDays()` — now correctly excludes any configured public holiday from the charged leave-day count |

**Known limitation, not fixed this stage**: `leave/apply.php`'s client-side JS date-range preview (a display convenience shown before submission) still does its own simple weekend-only estimate in JavaScript — it does not know about holidays, so it can show a slightly different number than what the server actually saves if the selected range includes a holiday. The server-side value is always authoritative; this is a minor UX polish item, not a data-integrity issue, and was not built out this stage (would require either an API endpoint or embedding holiday data into the page).

## 3. Live Verification

**Unit tests** (`docs/remediation/Testing/phase5-calendar-unit-tests.php`, re-runnable, self-cleaning): 17/17 passed — weekend exclusion, cross-month ranges, leap-year date arithmetic (Feb 29 2028), single-day and recurring-annual holiday exclusion, inactive-holiday non-blocking, `getNextWorkingDay()` weekend skip.

**Admin UI**: added a real holiday through the actual form — stored correctly; a duplicate submission (identical date range) correctly rejected with no second row created; toggle and delete both confirmed working with audit log entries.

**Dashboard integration**: with 13 active employees and 0 clocked in today (a working day), "Absent Today" correctly showed 13; inserting one disposable attendance row dropped it to 12 and moved the Monthly Attendance Rate from 0% to 1% (consistent with 1 present out of ~117 expected employee-days this month so far); removing the test row restored both to baseline.

**Leave day-count with a real holiday**: submitted a 5-calendar-day leave request (Mon–Fri) with a disposable test holiday on the middle Wednesday — resulting `total_days` correctly saved as **4**, not 5.

All test data (holidays, attendance rows, leave application, workflow, notifications, audit entries) removed after verification; leave balance confirmed restored to its exact pre-test value.

## 4. Incident Disclosure

During test-data cleanup for the leave-holiday verification, a single `mysql` batch included one statement referencing a nonexistent table (`leave_balances_history`, which doesn't exist in this schema — a copy-paste leftover from drafting the cleanup script). The client stopped the batch on that error, so 4 of 6 intended cleanup statements in that same invocation did not run, briefly leaving the test leave application, its approval workflow, one audit log row, and one test holiday in place, and leaving the test employee's leave balance at `remaining_days=11.0` instead of the correct `15.0`. Caught immediately by the next verification query, corrected in a follow-up statement, and reconfirmed clean (all test artifacts removed, balance restored to `15.0`/`0.0`) before proceeding. No production data was at risk — this was entirely disposable test data created and destroyed within the same verification pass. Disclosed here in full, consistent with this program's standing practice.

## 5. Regression

| Suite | Result |
|---|---|
| PHP syntax check (7 files: `functions.php`, `calendar.php`, `header.php`, `dashboard.php`, `modules/attendance/index.php`, `modules/reports/{index,executive,timesheets}.php`, `modules/leave/apply.php`) | 0 errors |
| Calendar unit tests | 17/17 |
| Phase 1 regression | 20/20 |
| Phase 2 regression | 29/29 |

## 6. Register Update

Completes the deferred half of **KOM-098** (Phase 4, partially fixed pending exactly this infrastructure) — the monthly/period attendance figures in Reports now use the real working calendar instead of the dead `is_absent` column or an incomplete attendance-row-only denominator.
