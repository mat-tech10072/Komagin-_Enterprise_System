# Komagin HR — Permission Matrix Report

**Document type:** Phase 0 Baseline Deliverable #4 of 9
**Status:** Documentation only — no permission, role, or matrix data was changed to produce this report.
**Date compiled:** 2026-07-11
**Baseline tag:** `v1.0-enterprise-baseline`

This document is the canonical permission reference for all remediation phases from this point forward. Any phase that touches authorization must diff its intended change against this document before merging.

---

## 1. Roles and Permission Seed Sources

**`users.role` ENUM — 11 roles** (defined across `phase1_permissions.sql`, cumulative):
`super_admin`, `hr_manager`, `hr_officer`, `supervisor`, `employee`, `finance_viewer`, `payroll_manager`, `payroll_officer`, `recruitment_officer`, `training_officer`, `kiosk_terminal`.

**Permission slugs — 94 total seeded**, across four files applied in this order:
- `phase1_permissions.sql` — 79 base slugs, grouped: `dashboard.*`(2), `employees.*`(9), `attendance.*`(4)+`kiosk.manage`(1), `timesheets.*`(5), `leave.*`(5), `recruitment.*`(4), `onboarding.*`(2), `training.*`(4), `performance.*`(3), `disciplinary.*`(3), `assets.*`(3), `documents.*`(5), `payroll.*`(8), `reports.*`(5), `archive.*`(3), `hub.*`(2), `portal.*`(5), plus users/roles/settings/audit (6).
- `phase5_branding_theme.sql` — +7 (`branding.*`×5, `email.*`×2)
- `phase8_temp_employees.sql` — +4 (`temp_employees.*`)
- `phase9_consultants.sql` — +4 (`consultants.*`)

Each slug carries up to 8 action flags: `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`.

### Access breadth by role (summary)

| Role | Breadth |
|---|---|
| `super_admin` | Bypasses the matrix entirely at the code level — never queried against `role_permissions` |
| `hr_manager` | Near-universal — all modules, all actions, except `payroll.run`/`payroll.finalize`/`roles.manage`/`audit.export` |
| `hr_officer` | Broad view + selective create/edit, no delete, curated approve/export list — **but see §6: effectively zero access to `temp_employees.*`/`consultants.*` due to a role-name seed typo** |
| `payroll_manager` / `payroll_officer` | Tightly scoped to payroll + related-view only |
| `finance_viewer` | View + export only, payroll/reports |
| `recruitment_officer` / `training_officer` | Scoped to own module + `employees.view` |
| `supervisor` | View-only plus leave/timesheet approvals |
| `employee` | `portal.*` + `leave.apply` only |
| `kiosk_terminal` | `attendance.view` + `kiosk.manage` only |

---

## 2. Permission Slugs Actually Referenced in Code (by module)

| Module | Slugs used | Files |
|---|---|---|
| employees | `.view` `.create` `.edit` `.delete` `.status` `.approve_updates` `.portal_password` `.update_links` | `modules/employees/{index,view,edit,add,delete,status,pending_updates,set_portal_password,generate_link,id_card,work_history_save,qualification_save,dependent_save}.php` |
| attendance | `.view`, `kiosk.manage` | `modules/attendance/{index,kiosk_manage}.php`, `includes/header.php` |
| timesheets | `.view` `.edit` `.approve` `.approve_ot` | `modules/timesheets/{index,edit,approve,overtime,corrections}.php` |
| leave | `.view` `.types` `.approve` | `modules/leave/{index,view,types,approve}.php` |
| recruitment | `.view` `.manage` `.review` | `modules/recruitment/{index,vacancy_save,application_update}.php` |
| onboarding | `.view` `.manage` | `modules/onboarding/{index,save}.php` |
| training | `.view` `.manage` `.enrol` | `modules/training/{index,save,enrol}.php` |
| performance | `.view` `.manage` `.approve` | `modules/performance/{index,view,save}.php` |
| disciplinary | `.view` `.manage` `.close` | `modules/disciplinary/{index,view,save}.php` |
| assets | `.view` `.manage` | `modules/assets/index.php` |
| documents | `.view` `.upload` `.verify` `.missing` | `modules/documents/{index,upload,verify,templates,generate,missing,view_generated}.php` |
| payroll | `.view` `.payslips` `.run` `.finalize` `.deductions` `.savings` `.reports` | `modules/payroll/{index,payslips,run_save,run_finalize,run_publish,payslip_finalize,deductions,savings,reports}.php` |
| reports | `.view` `.timesheets` `.employees` | `modules/reports/{index,executive,timesheets,employees}.php` |
| archive | `.view` `.generate` | `modules/archive/{monthly,quarterly,yearly,save}.php` |
| hub | `.view` | `modules/hub/{index,view}.php` |
| temp_employees | `.view` `.create` `.edit` `.delete` | `modules/temp_employees/*` |
| consultants | `.view` `.create` `.edit` `.delete` | `modules/consultants/*` |
| admin | `users.manage`, `roles.manage`, `settings.manage`, `audit.view` | `modules/{users,roles,settings,audit}/index.php`, `settings/email.php`, `api/search.php` |
| branding | `.letterheads`, `.theme` | `settings/branding.php`, `settings/theme.php`, `includes/header.php` |

`includes/header.php` aggregates `canView()` checks across nearly every module to build the permission-filtered sidebar menu.

`employee-portal/`, `consultant-portal/`, `self-service/` **do not call any permission function** — they run on a wholly separate, role-less session model (see Architecture Report §2, Authentication Report).

---

## 3. Orphan Cross-Check

- **Used in code but not seeded anywhere:** none found. Every slug referenced through a permission function matches a seeded slug exactly.
- **Seeded but never referenced anywhere in code (24 slugs):** `dashboard.view`, `dashboard.analytics` (dashboard.php has no permission check at all — see Master Remediation Register **KOM-021 / NH-01**), `employees.export`, `attendance.edit`, `attendance.approve`, `attendance.export`, `leave.apply`, `leave.export`, `recruitment.export`, `training.export`, `assets.assign`, `documents.delete`, `archive.lock`, `hub.manage`, `portal.access`, `portal.requests`, `portal.payslips`, `portal.attendance`, `portal.leave`, `users.view`, `audit.export`, `branding.signatures`, `branding.stamps`, `branding.watermarks`, `email.send`, `email.logs`.

This is inventory, not a defect list — some of these may be intentionally reserved for future wiring (e.g. `branding.signatures`/`.stamps`/`.watermarks` look like they were meant to individually gate what `branding.letterheads` currently gates for all four asset types — see Master Remediation Register **M-09**). Flagged here so remediation planning can decide, per slug, whether to wire it up or remove it.

---

## 4. Authorization Checks That Bypass the Permission System

Hardcoded role-list checks found in place of `requirePermission()`/`canX()`:

| File:Line | Pattern |
|---|---|
| `modules/activity_log/index.php:10`, `download.php:9`, `user.php:9` | `$_SESSION['user_role'] !== 'super_admin'` (whole-module gate) |
| `modules/activity_log/index.php:26`, `download.php:215` | `$adminRoles` array, includes the misspelled `'hrofficer'` |
| `modules/timesheets/corrections.php:17` | `in_array($_SESSION['user_role'], ['super_admin','hr_manager','hr_officer'])` |
| `modules/employees/view.php:769` | same 3-role hardcode |
| `modules/assets/index.php:85` | same 3-role hardcode |
| `modules/documents/index.php:137` | same 3-role hardcode |
| `modules/leave/view.php:35`, `apply.php:17`, `index.php:147` | same 3-role hardcode (×3 files) |
| `modules/archive/monthly.php:155` | `in_array(..., ['super_admin','hr_manager'])` |
| `modules/temp_employees/view.php:278`, `add.php:263`, `edit.php:273` | `in_array(..., ['super_admin','hr_manager'])` |

**13 distinct occurrences across 12 files.** See Master Remediation Register, Finding **KOM-058** (consolidated).

`config/functions.php:42-48` also defines `requireRole(array $roles)` — a second, parallel hardcoded-list authorization primitive, formally part of the framework but with **zero call sites anywhere in the codebase** (dead code).

---

## 5. The `super_admin` Bypass Mechanism

In `config/functions.php`:
- `_loadRolePermissions()` (line 75): `if ($role === 'super_admin') return $matrix;` — returns an empty matrix immediately, never queries `role_permissions`.
- `hasPermission()` (line 92): `if ($_SESSION['user_role'] === 'super_admin') return true;` — unconditional true before any matrix lookup.
- `currentUserPermissions()` (line 140): returns the sentinel `['__super_admin__' => true]` instead of the matrix.

This is by-design and documented here for completeness — not itself a finding, but relevant context for anyone auditing "why doesn't `role_permissions` have rows for `super_admin`" (it deliberately doesn't need any).

---

## 6. Role-Name Typo — `'hrofficer'` vs `'hr_officer'` (CONFIRMED)

- Canonical ENUM value: `database/phase1_permissions.sql:11` — `'hr_officer'` (underscore).
- `database/phase8_temp_employees.sql:79`: `SELECT 'hrofficer', id, ...` (no underscore).
- `database/phase9_consultants.sql:85`: `SELECT 'hrofficer', id, ...` (no underscore).

**Effect:** real `hr_officer` users receive zero rows in `role_permissions` for `temp_employees.*` and `consultants.*`, because `_loadRolePermissions()` queries `WHERE rp.role = ?` using the literal session role string `hr_officer`, which never matches the seeded `hrofficer` rows. The typo independently reappears in `modules/activity_log/{index,download}.php`'s hardcoded `$adminRoles` arrays (§4), suggesting it propagated from the SQL seed into at least one piece of application code. See Master Remediation Register, Finding **KOM-056**.

---

## 7. Salary / Bank Data Masking Coverage

`config/functions.php`:
- `canViewSalaryData()` (line 148) — gated by `payroll.view`
- `canViewBankData()` (line 153) — gated by `payroll.view`
- `canViewPayrollBreakdown()` (line 158) — gated by `payroll.payslips`
- `canViewPersonalHRData()` (line 163) — gated by `employees.view AND !isPurePayrollRole()`
- `maskSalary()` (line 178) / `maskBankField()` (line 183) — apply the above, substitute a masked placeholder when false

**Call-site count:** 18 total across 3 files — `config/functions.php` (9, definitions/internal), `modules/employees/edit.php` (8), `modules/employees/view.php` (1). Coverage is per-call-site, not enforced at a shared data-access layer — `modules/reports/executive.php` was found in prior review to aggregate payroll totals without routing through this masking (Master Remediation Register **H-05**), which this call-site count corroborates: 1 call site in the entirety of `modules/reports/` would be needed for full coverage there, and none exist.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-11 | Initial baseline permission matrix compiled for Phase 0 | Remediation Program — Phase 0 |
