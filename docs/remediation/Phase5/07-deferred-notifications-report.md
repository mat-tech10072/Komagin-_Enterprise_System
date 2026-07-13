# Komagin HR — Phase 5 Stage 5.6: Deferred Notification Workflows

**Document type:** Phase 5 Deliverable — Stage 5.6 Report
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13

---

## 1. What Was Built

Phase 4 Workflow Group 11 (Notifications) documented, but did not build, several categories of "reminder" notification — Training, Recruitment, and any scheduled/time-based notification mechanism were confirmed absent because no cron/scheduler existed at all at that point. Stage 5.4 built the scheduler; this stage fills in `cron/tasks/send_reminders.php`'s real logic, the placeholder left for it.

**Nine reminder categories**, each an admin `notifyRole()` call (matching the existing convention used throughout the codebase, e.g. `leave/apply.php`, `employees/edit.php`):

| Category | Trigger | Threshold(s) | Notified role |
|---|---|---|---|
| Employee contract expiry | `employees.contract_end_date`, status `active` | 30 / 14 / 7 / 1 days out | `hr_manager` |
| Employee probation ending | `employees.probation_end`, status `probation` | 7 / 1 days out | `hr_manager` |
| Temp employee assignment ending | `temp_employees.end_date`, status `active` | 7 / 1 days out | `hr_manager` |
| Consultant contract ending | `consultants.end_date`, status `active` | 7 / 1 days out | `hr_manager` |
| Employee document expiry | `employee_documents.expiry_date`, not deleted | 30 / 7 days out | `hr_manager` |
| Training program starting soon | `training_programs.start_date`, status `planned` | 7 / 1 days out | `hr_manager` |
| Recruitment interview tomorrow | `recruitment_applications.interview_date`, status `interview_scheduled` | 1 day out | `hr_manager` |
| Leave approval sitting unactioned | `approval_workflows` (`leave`, pending/in_review) | 2 **working** days elapsed (Stage 5.3 calendar) | `hr_manager` |
| Payroll run finalized, not published | `payroll_runs.finalized_at`, status `finalized` | 2 **working** days elapsed (Stage 5.3 calendar) | `payroll_manager` |

The last two use `countWorkingDays()` (Stage 5.3) rather than raw calendar days, so a workflow created Friday afternoon isn't flagged as "2 days overdue" by Monday morning.

**Password-reset notifications** (also named as a gap in Phase 4 Workflow Group 11) are addressed by Stage 5.5's email-based flow instead — a different, already-complete mechanism, not part of this stage.

## 2. Design Correction Found During Testing: Per-Day Dedup

The original design used only an exact-day match (`DATEDIFF = N`) as its own safeguard against repeat notifications, on the assumption the scheduler runs once daily. **This assumption was wrong against this application's own documentation**: `cron/README.md` (written in Stage 5.4) recommends running the scheduler every 15–30 minutes. At that cadence, every matching reminder would have re-fired on every single invocation within its matching day — a real spam bug, caught before commit by re-reading the scheduler's own setup instructions during live testing, not by external report.

**Fix:** added `reminder_notifications_log` (`reminder_key` + `reminder_date`, `UNIQUE KEY` on the pair), applied live and to `database/schema.sql`/`phase13_workflow_completeness_automation.sql`. A `fireOnce()` helper attempts an insert before each notification; a UNIQUE-constraint failure means "already sent today," and the notification is skipped. This makes every reminder correct at any scheduler cadence, not just a daily one. Retention cleanup (90 days) added to the existing `cleanup_safe_temp_files.php` task, consistent with that task's existing scope of pruning disposable, non-evidentiary data.

## 3. Live Verification

Used disposable `P5TEST`-prefixed rows across `employees`, `temp_employees`, `consultants`, `employee_documents`, `training_programs`, `recruitment_applications`, `approval_workflows`, and `payroll_runs`, dated to land exactly on each category's nearest threshold (working-day thresholds computed via the application's own `countWorkingDays()` rather than guessed, to avoid a test that's wrong about the calendar).

- **First run**: `itemsProcessed = 9` (all 9 categories matched); 8 real notifications delivered to the one seeded `hr_manager` account (the 9th, payroll, targets `payroll_manager`, a role with no currently-seeded active user — `notifyRole()` correctly no-ops with zero recipients, matching existing behavior elsewhere in the codebase, while the reminder itself is still correctly logged as fired).
- **Second run, same calendar day, fresh CLI process** (to accurately simulate a second real cron invocation rather than reusing PHP's own function-declaration state): `itemsProcessed = 0`, notification delta `0` — confirms the per-day dedup works.
- **Full scheduler run** (`php cron/run.php`) against real (non-test) data: all 4 tasks report `OK`, `send_reminders` correctly processes `0` items (no real production data currently matches any threshold).
- All disposable test rows and their `reminder_notifications_log`/`notifications` rows fully removed and verified absent afterward.

## 4. Regression

- Phase 1 suite: **20/20 passed**.
- Phase 2 suite: **29/29 passed**.
- Zero regressions.

## 5. Register / Change Control

No specific finding number maps to this stage (same as Stage 5.4) — it fills in previously-documented completeness gaps (Phase 4 Workflow Group 11's "Training, Recruitment... reminder notification mechanism... confirmed to not exist") rather than fixing a defect in existing behavior. Recorded for full traceability. See Change Control Log CC-111–CC-112.
