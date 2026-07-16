# Komagin HR — Authorization Framework Report

**Document type:** Phase 1 Deliverable #1 of 10
**Program:** Enterprise Remediation Program — Phase 1: Security Foundation & Authorization Framework
**Date:** 2026-07-11/12
**Baseline tag:** `v1.0-enterprise-baseline` → work performed on branch `phase-1-authorization-framework`

---

## 1. Objective

Phase 1's charter was explicit: this is not a bug-fixing phase, it is the establishment of **one enterprise authorization framework that every module must follow.** This report documents what that framework is, how it was strengthened, and — just as importantly — what was deliberately left alone.

## 2. What Already Existed (and Was Kept)

Komagin HR already had the right *shape* of authorization system before Phase 1 began: a DB-driven `permissions` × `role_permissions` matrix (94 slugs × 11 roles × 8 action columns), with `hasPermission()`/`requirePermission()` in `config/functions.php` as the intended single entry point, and a `super_admin` bypass baked into that same choke point. Phase 1 did not replace this design — it enforced it consistently and closed the gaps where code had quietly stopped using it.

## 3. What Changed — The Core Fix

**Before:** `requirePermission(string $permission, string $action = 'view')` — the action argument was optional and defaulted to `view`.

**After:** `requirePermission(string $permission, string $action)` — the action argument is required. `hasPermission()` was changed identically, and both now validate the action against a fixed `PERMISSION_ACTIONS` list (`view, create, edit, delete, approve, export, publish, share`), throwing `InvalidArgumentException` for anything else.

This single signature change was deliberately chosen as the mechanism to satisfy Objective 2 ("no module should rely on implicit defaults that silently fall back to view") because PHP itself then enforces it: any call site that hadn't been updated would throw a fatal "too few arguments" error the moment it executed. That made the sweep self-verifying — there was no way to miss a call site and have the application silently keep running with a stale default.

**Consequence found while doing this:** the default wasn't just a style problem. Several files (`modules/consultants/add.php`, `delete.php`, `edit.php`, `scope_save.php` among them) named their permission slug after the real action (`consultants.create`, `consultants.delete`, `consultants.edit`) but, because the action argument was omitted, were actually checking that slug's `can_view` column — not `can_create`, `can_delete`, or `can_edit`. A role with view-only rights on a *create*-named permission slug would have passed the check. This is documented in the Permission Consistency Report and was fixed as part of the sweep.

## 4. Scope of the Sweep

- **55 single-argument `requirePermission()` calls** across 53 module files, plus **8 single-argument `hasPermission()` calls** in `includes/header.php` and `api/search.php` — all made explicit with the correct action for that call site (51 became `'view'`, 4 in the consultants module became `'create'`/`'edit'`/`'delete'` to match their actual purpose).
- **Beyond the mechanical sweep**, every action branch inside a POST handler was checked for whether it needed its *own*, more specific permission call distinct from the page-level gate — this is where the majority of the real fixes live (see §5, and the Payroll/Document/Approval Engine reports for the specific cases).

## 5. Dead Code Removed

`config/functions.php` had a second, parallel authorization primitive: `requireRole(array $roles)`, a hardcoded-list checker with zero call sites anywhere in the codebase (confirmed in Phase 0 baseline inventory, finding KOM-064). Objective 1 explicitly calls for removing duplicated authorization logic — this function was deleted. Its *pattern* (hardcoded role lists) is the same pattern found live in 13 other locations across the codebase; see the Permission Consistency Report for how those were handled.

## 6. New Framework Additions

Two new primitives were added to `config/functions.php` because the existing framework had no equivalent:

1. **`assignableRoles()` / `isValidAssignableRole()`** — server-side role allow-list validation (Objective 4). See Role Validation Report.
2. **`canAccessGeneratedDocument(array $doc)`** — a record-level check, distinct from the module-level permission (Objective 9). See Document Authorization Report.

Both are documented here as framework-level additions because they follow the same design principle as the rest of the system: authorization logic lives in `config/functions.php`, callers invoke a named function, no module reimplements the check inline.

## 7. What Objective 1 Did NOT Mean

"Centralize authorization" did not mean collapsing every distinct permission concept into one function call everywhere. Two deliberate exceptions were kept and documented rather than converted:

- **`modules/leave/apply.php`**'s distinction between "HR can apply for any employee" vs. "an employee can only apply for themselves" remains a role check (`in_array($_SESSION['user_role'], ['super_admin','hr_manager','hr_officer'])`), because it is a business rule about *whose employee record you may act on*, not a feature-access permission — there is no clean single permission slug that expresses it, and inventing one would be scope creep into permission-matrix redesign, which this phase's charter explicitly forbids ("Do NOT redesign workflows").
- **`modules/activity_log/{index,download}.php`**'s `$adminRoles` array is a *content filter* (which users' activity counts as "admin activity" in a report breakdown), not an access-control gate — access to the page itself is now fully permission-gated (see Activity Log Authorization Report); this array decides what a `WHERE role IN (...)` clause selects, which is a reporting concern, not an authorization one.

Both are called out explicitly in the Permission Consistency Report so a future reviewer doesn't mistake "we found this and left it" for "we missed this."

## 8. Verification Approach

Every change in this report was verified against the **running application** (XAMPP Apache + MariaDB, `http://localhost/HR_Komagin`), not just read for correctness:
- `php -l` syntax-checked every touched file before any live test.
- Live HTTP smoke tests (login as `super_admin`, `hr_manager`, `hr_officer`, `payroll_officer`, load every touched page) confirmed zero fatal errors introduced by the sweep.
- Targeted functional/security tests (documented per-finding in the Regression Test Report) confirmed the specific behavior changes: blocked actions are blocked, allowed actions remain allowed.

## 9. Summary

| Metric | Count |
|---|---|
| Files with an authorization-relevant change | 68 |
| Permission call sites made explicit (mechanical) | 63 |
| Findings from the Master Remediation Register fixed | 12 |
| New permission slugs added | 2 (`activity_log.view`, `approvals.manage_all`) |
| Data-only migration files added | 1 (`database/phase10_authorization_framework.sql`) |
| Dead authorization code removed | 1 function (`requireRole()`) |
| Hardcoded role checks converted to permission checks | 10 of 13 (3 kept, justified — see §7) |

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial Phase 1 authorization framework report | Remediation Program — Phase 1 |
