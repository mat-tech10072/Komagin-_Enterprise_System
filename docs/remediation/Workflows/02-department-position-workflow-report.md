# Komagin HR — Phase 4 Workflow Group 2: Department & Position Management

**Document type:** Phase 4 Deliverable — Workflow Group Report 2 of N
**Status:** Live-verified against disposable test records where noted; one live production correction disclosed in §5.
**Date compiled:** 2026-07-12
**Scope:** Department creation/assignment/reassignment/deletion-protection/reporting; Position hierarchy, supervisor relationships, vacancy handling, reassignment.

---

## 1. Workflow Inventory — What Exists

| Action | File | Trigger |
|---|---|---|
| Department create | `modules/settings/index.php` (Departments tab) | HR adds a name/code |
| Department disable | Same file | Soft-disable only (`is_active=0`), no hard delete |
| Position create/disable | `modules/settings/index.php` (Positions tab — **new this phase**) | Previously did not exist anywhere |
| Department/Position assignment | `modules/employees/{add,edit}.php` | Dropdown selection; a post-Phase-4-Group-1 department change routes through the `transfer` approval workflow (see Workflow Group 1) |

## 2. Critical Finding — `positions` Had No Management UI Anywhere, and No Seed Data (FIXED)

Searching the entire codebase for `INSERT INTO positions` / `UPDATE positions` / `DELETE FROM positions` returned **zero matches** outside the new code this phase added. The `positions` table has a `CREATE TABLE` in `schema.sql` and is read everywhere (`getPositions()`, Add/Edit Employee dropdowns), but **nothing in the application could ever create, edit, or deactivate a position.** The only way the 23 live position rows came to exist was an untracked, undocumented manual database change — the same class of gap Phase 3 closed for tables, now found again at the *data* layer for a table whose structure *was* already tracked.

Consequence: a fresh install has **zero positions** and, before this fix, **no way to create one through the application at all** — the "Position" field on Add/Edit Employee would be permanently empty for the life of the deployment.

**Also discovered in the same pass:** `departments` has the exact same seed-data gap — no `INSERT INTO departments` exists in any tracked file either, despite `departments` at least having a management UI (unlike positions). A fresh install would start with zero departments too.

**Fix:**
1. New `database/seeds/003_departments_positions.sql` — the exact live 11 departments and 23 positions, verified by direct query, added to both `install.php`'s and `verify_clean_install.php`'s install sequence (after `002_doc_categories.sql`).
2. New **Positions** tab added to Settings (`modules/settings/index.php`), mirroring the existing Departments tab's pattern: add (title, department, grade) and disable, with a duplicate-name-within-department guard (positions have no DB-level uniqueness constraint, unlike departments).
3. `verify_clean_install.php` gained two new structural checks: departments seeded (11) and positions seeded (23).

**Live-verified**: clean-install test now 29/29 (was 26/26 before this group); added a real position via the new UI, confirmed a duplicate add is correctly rejected, confirmed disabling a position with active employees shows an informational count and correctly removes it from the Add/Edit Employee dropdown.

**Finding ID:** KOM-078 (new, fixed)

## 3. Finding — Adding a Duplicate Department Name Crashed the Entire Settings Page (FIXED)

`departments.name` has a database-level `UNIQUE KEY dept_name_unique` constraint (unconditional — it also blocks reusing the name of an already-*disabled* department), but the Departments-tab "Add" handler never checked for a collision before the `INSERT`. **Live-verified**: submitting "Human Resources" (an existing department name) threw an uncaught `PDOException` (`SQLSTATE[23000]... Duplicate entry`), crashing the page with a raw fatal-error response — the same class of bug as KOM-071 (Workflow Group 1) in a different location.

**Fix:** added an application-level duplicate-name check before the `INSERT`, distinguishing "already exists and active" from "already exists but disabled" (with a hint to re-enable rather than recreate).

**Live-verified**: duplicate submission now shows a clean inline error, no crash, no duplicate row created; a genuinely new department name still succeeds correctly.

**Finding ID:** KOM-079 (new, fixed)

## 4. Finding — Department Disable Had No Deletion-Protection Visibility (FIXED)

Disabling a department (`is_active=0`) previously had no check for how many employees or positions currently reference it — unlike `modules/employees/delete.php`'s full cascade-impact preview, this was a single click behind a generic JS `confirm()` with no information about consequences. Not destructive (no cascade, no orphaning — `getDepartments()`/`getPositions()` already correctly filter `is_active=1`, so a disabled department simply stops appearing as selectable for *new* assignments while existing employees/positions keep functioning normally), but the charter's instruction to "verify deletion protection" was not met by a silent, uninformative disable.

**Fix:** disabling a department now counts active employees and active positions still referencing it and includes that count in the confirmation message, so the action is informed rather than silent. The same employee-count check was added to Position disable.

**Live-verified**: disabling a department with an assigned active employee correctly surfaced "1 active employee(s)... still reference it."

**Finding ID:** KOM-080 (new, fixed)

## 5. Live Production Correction Disclosed

While live-testing §4's fix, the disable action was run against the **real** "Human Resources" department (id=1, the department a genuine employee belongs to) instead of a disposable test record — an oversight in test setup. It was disabled for approximately one minute, then immediately re-enabled (`UPDATE departments SET is_active=1 WHERE id=1`) upon noticing. Verified afterward: department count unchanged (11), `is_active=1` confirmed restored, no employee or position rows were altered by the disable/re-enable (the action only ever touches the `departments.is_active` column, never cascades). No functional impact — disclosed here in full per this program's standing practice of surfacing process issues rather than omitting them.

## 6. No Findings — Hierarchy, Supervisor Relationships, Vacancy Handling

- **Supervisor relationships**: already verified in Phase 3's data-integrity sweep (`Database/09-phase3-data-integrity-report.md`) — zero orphaned `supervisor_id` references, zero self-supervision. Re-confirmed no regression this group.
- **Position hierarchy**: this system models hierarchy at the *employee* level (`employees.supervisor_id`) rather than an abstract position-reports-to-position structure — `positions` has no parent/hierarchy column. This is a design characteristic, not a defect; flagged here only for completeness, not as a finding requiring a fix.
- **Vacancy handling**: `positions` has no headcount/slot-limit concept — any number of employees may hold the same position, and there is no "N of M filled" tracking. This is a genuine completeness gap relative to the charter's explicit mention of "vacancy handling," but building headcount-limited position slots is a feature addition, not a bug fix, and changes how positions are modeled company-wide. Documented for awareness (not fixed, not blocking) rather than built unilaterally — parallel to how Phase 0's KOM-058 (temp-employee timesheet) was logged as a completeness gap rather than treated as a defect.

## 7. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`settings/index.php`, `install.php`, `verify_clean_install.php`) | 0 errors |
| Clean-install test | 29/29 passed (was 26/26 before this group — 3 new checks: departments/positions seed steps + counts) |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group) | 6/6 scenarios: position add, duplicate-position block, position disable-with-warning, duplicate-department crash fix, department disable-with-warning, legitimate department add |

## 8. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-078 — No `positions` management UI anywhere, no seed data for departments or positions | Critical | **Fixed** |
| KOM-079 — Duplicate department name crashed the Settings page | High | **Fixed** |
| KOM-080 — Department/position disable had no deletion-protection visibility | Low | **Fixed** |

**All 3 findings fixed and live-verified.**
