# Komagin HR — Phase 4 Workflow Group 8: Training

**Document type:** Phase 4 Deliverable — Workflow Group Report 8 of N
**Status:** Live-verified against real and disposable test data, fully cleaned up afterward.
**Date compiled:** 2026-07-12
**Scope:** Training assignment → attendance → completion → certification → reporting.

---

## 1. Critical Finding — The Entire Training Module Was Fatally Broken in Three Separate Places (FIXED — closes KOM-008, open since Phase 0, and goes well beyond its original scope)

The Master Register already carried KOM-008 ("Training enrolments invisible due to column-name mismatch... reads/joins `training_id`... writes `program_id`"). Investigating it directly for Phase 4 found the module is broken far more extensively than that single mismatch:

1. **Enrolling anyone crashed immediately.** `modules/training/enrol.php`'s duplicate-check query used `program_id`, which doesn't exist in `training_attendance` at all (the real FK column is `training_id`); the `INSERT` right after it also named a `created_by` column that doesn't exist either. **Live-verified**: every single enrolment attempt threw an uncaught `PDOException` before any row was ever written.
2. **The Attendance tab crashed on load, independent of the above.** `modules/training/index.php`'s attendance-list query did `ORDER BY ta.created_at DESC` — `training_attendance` has no `created_at` column at all. **Live-verified**: simply navigating to the Attendance tab threw an uncaught `PDOException`, meaning even historical (seed) enrolment data could never be viewed.
3. **Creating a training program also crashed.** `modules/training/save.php`'s `INSERT` named `training_type` (doesn't exist anywhere in `training_programs`), `trainer_name` (real column: `provider`), and `venue` (real column: `location`). **Live-verified**: every "Add Training Program" submission threw an uncaught `PDOException`.

On top of the three crashes, the Attendance list's *display* code (which doesn't crash, since PHP silently treats an undefined array key as `null`) read `$a['created_at']`, `$a['status']`, and `$a['score']` — none of which exist (the real columns are `attended`, `certificate_file`, `certificate_expiry`) — so even a manually-inserted row would have rendered with a blank date, a blank/wrong status badge, and always "—" for score. The Programs list had the same class of silent-blank bug for `training_type`/`trainer_name`, and its status-badge color mapping used `'active'` where the real ENUM value is `'ongoing'`.

**In short: no part of this module — create a program, enrol someone, or even just view who's enrolled — worked at all before this fix.**

**Fix:**
- `enrol.php`: corrected to `training_id`, dropped the nonexistent `created_by` from the `INSERT`.
- `index.php` (Attendance tab query): `ORDER BY ta.id DESC` (no timestamp column exists to order by).
- `index.php` (Attendance tab display): corrected to show the real columns — an Attended Yes/No badge and certificate-on-file status, replacing the fictional Enrolled-date/Status/Score columns.
- `index.php` (Programs tab display): corrected to `provider`/`location`, fixed the `ongoing` status-badge mapping, removed the "Type" dropdown from the Add Program form since it had no corresponding column and was being silently discarded.
- `save.php`: corrected to `provider`/`location`, dropped the unmappable `training_type`.
- New `modules/training/attendance_update.php` + a "Mark Attended" button: previously there was no way to ever update `attended` after enrolment at all — this closes the "Attendance/Completion" step of the charter's lifecycle, which had no mechanism whatsoever, not even a broken one.

**Live-verified after fix**: created a training program (correct values in `provider`/`location`); enrolled a test employee (no crash, correct row); the Attendance tab loaded without error and displayed the real Attended/Certificate columns; marked the enrolment attended and confirmed the database update. All test data cleaned up afterward.

**Finding ID:** KOM-008 (pre-existing, closed with a much fuller root cause than originally documented — 3 separate crash sites plus 2 silent display bugs, not 1)

## 2. Informational — No Certificate Upload UI, No Training Reporting

Two further gaps remain, deliberately not built this round given the volume of fixes already made in this group:
- **No certificate upload exists.** `certificate_file`/`certificate_expiry` are now correctly *displayed* (§1) but there is still no form anywhere to actually upload a certificate or set its expiry — an employee can be marked "Attended" but never "Certified" through the UI.
- **Training reporting** was not audited in this pass (belongs to Workflow Group 13: Reports & Dashboards) — `modules/reports/executive.php` does reference training data, worth re-checking there for the same column-mismatch pattern found throughout this module.

Both documented for awareness rather than built/audited unilaterally in this already large fix set.

## 3. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`enrol.php`, `index.php`, `save.php`, `attendance_update.php`) | 0 errors |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group) | 6/6 scenarios: enrol crash → fixed, attendance-tab crash → fixed, add-program crash → fixed, duplicate-enrolment block, mark-attended, display correctness for provider/location/status |

## 4. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-008 — Entire Training module fatally broken in 3 places, plus 2 silent display bugs (pre-existing, Phase 0, far more severe than originally documented) | Critical | **Fixed** |

**The one finding for this group — expanded well beyond its original scope during investigation — is fully fixed and live-verified.** Two smaller completeness gaps (certificate upload, training reporting) documented for future awareness.
