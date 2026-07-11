# Komagin HR — Permission Consistency Report

**Document type:** Phase 1 Deliverable #3 of 10
**Objective addressed:** Objective 10 — Permission Consistency Review
**Date:** 2026-07-11/12

---

## 1. Purpose

Objective 10 asks four specific questions across the codebase: are permission names consistent, are action names consistent, are helper functions reused, and are legacy/hardcoded checks eliminated where not architecturally justified. This report answers each, with the evidence.

## 2. Permission Names — Consistent

The Permission Matrix Report (Phase 0, Deliverable 04) already confirmed every permission slug referenced in code matches a seeded slug exactly — zero orphans. Phase 1 added exactly two new slugs (`activity_log.view`, `approvals.manage_all`), both following the existing `module.action_or_scope` naming convention.

## 3. Action Names — Now Consistent (Was the Core Phase 1 Fix)

Before Phase 1, "action name consistency" was undermined at the root: 63 call sites didn't name an action at all, silently defaulting to `view`. This is documented fully in the Authorization Framework Report. After Phase 1, every `requirePermission()`/`hasPermission()` call in the codebase names one of the eight canonical actions (`view, create, edit, delete, approve, export, publish, share`) explicitly, and the shared functions reject anything else with a thrown exception.

## 4. Helper Functions — Reused, Not Reimplemented

Every fix in Phase 1 routed through the existing `canView()`/`canCreate()`/`canEdit()`/`canDelete()`/`canApprove()`/`canExport()`/`canPublish()`/`canShare()`/`hasPermission()`/`requirePermission()` family, or the two new framework additions (`assignableRoles()`, `canAccessGeneratedDocument()`) — no module wrote its own inline permission-checking logic during this phase.

## 5. Hardcoded Role Checks — The Full Inventory and Disposition

Phase 0's baseline inventory found 13 occurrences of `in_array($_SESSION['user_role'], [hardcoded list])` used as an authorization check, consolidated as finding KOM-040. Phase 1's disposition of each:

| # | Location | Disposition | Replacement |
|---|---|---|---|
| 1 | `modules/timesheets/corrections.php:17` | **Converted** | `canApprove('timesheets.approve')` |
| 2 | `modules/employees/view.php:769` | **Converted** | `canShare('employees.update_links')` |
| 3 | `modules/assets/index.php:85` | **Converted** | `canCreate('assets.manage')` |
| 4 | `modules/documents/index.php:137` | **Converted** | `canApprove('documents.verify')` |
| 5 | `modules/leave/view.php:35` | **Converted** | `canApprove('leave.approve')` |
| 6 | `modules/leave/index.php:147` | **Converted** | `canApprove('leave.approve')` |
| 7 | `modules/archive/monthly.php:155` | **Converted** | `canEdit('archive.generate')` |
| 8 | `modules/temp_employees/view.php:278` | **Converted** (was redundantly double-checked with `canEdit()` already — simplified to one check) | `canEdit('temp_employees.edit')` |
| 9 | `modules/temp_employees/add.php:263` | **Converted** | `canEdit('temp_employees.edit')` |
| 10 | `modules/temp_employees/edit.php:273` | **Converted** | `canEdit('temp_employees.edit')` |
| 11 | `modules/leave/apply.php:17` | **Kept, documented as justified** | Business rule distinguishing "apply for anyone" vs. "apply for self," not a permission gap — see Authorization Framework Report §7 |
| 12 | `modules/activity_log/index.php:26` | **Kept, documented as justified** | Content filter for a report breakdown, not an access-control gate |
| 13 | `modules/activity_log/download.php:215` | **Kept, documented as justified** | Same as #12 |

**10 of 13 converted; 3 kept with explicit reasoning recorded in code comments and in this report** — satisfying Objective 10's own allowance ("hardcoded role checks are minimized *unless architecturally justified*").

## 6. Bugs Discovered by This Sweep (Not Just Style Fixes)

Converting #1, #5, and #6 above surfaced a real, previously undetected access bug: the seeded permission matrix grants `supervisor` role `can_approve = 1` for both `timesheets.approve` and `leave.approve`, but none of the three old hardcoded lists (`['super_admin','hr_manager','hr_officer']`) included `supervisor`. Supervisors who were *supposed* to be able to approve leave and timesheet corrections per the permission matrix could not see the approve/reject buttons in the UI at all — the matrix said yes, the hardcoded UI check said no. Converting these three checks to permission-function calls fixed this silently, as a byproduct of the consistency work, not a separately-hunted bug. (No `supervisor`-role test account exists in this environment's seed data, so this specific fix is code-verified via the permission matrix values rather than click-tested live — see Regression Test Report.)

## 7. A Structural Observation, Not a Finding

While tracing the consultants module's permission seeding (`database/phase9_consultants.sql`) to understand the action-defaulting bug (§3 above / Authorization Framework Report §3), it became clear that this module's four permission slugs (`consultants.view`, `.create`, `.edit`, `.delete`) are seeded with **identical action-column values across all four rows** for every role — i.e., the slug name itself carries no distinguishing information; only the `$action` parameter passed by the calling code does. This is a valid design (the slug identifies "this feature area," the action parameter identifies "this operation"), but it means a module that used only ONE slug (e.g. `consultants.manage`) with varying actions would have behaved identically. This isn't a defect — it's a documented observation for whoever designs the next module, to prevent unnecessary slug proliferation. No register entry was created for this since it's not a bug, just a design note.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial permission consistency report | Remediation Program — Phase 1 |
