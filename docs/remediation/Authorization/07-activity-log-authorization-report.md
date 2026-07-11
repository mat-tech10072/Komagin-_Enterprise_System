# Komagin HR — Activity Log Authorization Report

**Document type:** Phase 1 Deliverable #8 of 10
**Objective addressed:** Objective 7 — Activity Log Authorization
**Finding addressed:** KOM-019 (NH-02)
**Date:** 2026-07-11/12

---

## 1. Two Modules, One Table, Two Authorization Models

Komagin HR has two module surfaces reading the same `audit_logs` table:

- **`modules/audit/index.php`** — the older, smaller viewer. Correctly gated by `requirePermission('audit.view', 'view')`, a real seeded permission, since its introduction.
- **`modules/activity_log/{index,user,download}.php`** — a newer, larger, more feature-rich viewer (per-user drill-down, CSV export) added later. Gated by a hardcoded `if ($_SESSION['user_role'] !== 'super_admin')` check in all three files, with zero connection to the permission matrix.

This report covers bringing the second module in line with the first — **not** merging or removing either (that is a UI/navigation redesign question, tracked separately as KOM-037/NM-05, explicitly out of scope for an authorization-consistency phase).

## 2. The Fix

**New permission**, seeded via `database/phase10_authorization_framework.sql`:

```sql
INSERT IGNORE INTO permissions (slug, name, module, description) VALUES
('activity_log.view', 'View Activity Log', 'activity_log', 'View the detailed per-user/entity activity log and export it');

INSERT IGNORE INTO role_permissions (role, permission_id, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_publish, can_share)
SELECT 'super_admin', id, 1,0,0,0,0,1,0,0 FROM permissions WHERE slug = 'activity_log.view';
```

Seeded to `super_admin` only — **deliberately matching, not expanding, the access the hardcoded check already granted.** This is a mechanism change (hardcoded → DB-driven), explicitly not a policy change (who has access). Broadening access to `hr_manager` or others is a legitimate future decision but is a *product/policy* decision, not something this authorization-consistency phase should decide unilaterally — it's called out as an open question in the Phase 1 Completion Report for the next phase to pick up if desired.

**Code changes:**
- `modules/activity_log/index.php` and `user.php` — replaced the hardcoded block with `requirePermission('activity_log.view', 'view')`.
- `modules/activity_log/download.php` — this is a raw CSV-streaming endpoint, not an HTML page; `requirePermission()`'s denial path does an HTTP redirect, which is wrong for a download link. Kept the file's original raw-403 response shape, but routed the actual check through the same centralized `hasPermission('activity_log.view', 'export')` function (using the `export` action specifically, since downloading a report is conceptually distinct from viewing one — matches the Objective 2 action-vocabulary: `view, ..., export, ..., download, ...`), and added the `auditLog()` call for denials that `requirePermission()` would have made, so the audit trail is consistent even though the response mechanics differ.
- `includes/header.php` — the sidebar nav link's visibility condition changed from `($_SESSION['user_role'] ?? '') === 'super_admin'` to `canView('activity_log.view')`; the broader "Administration" section wrapper condition (an `||` of several `canView()` checks) had `canView('activity_log.view')` added to it, so a role holding only this permission would still see the Administration section header render (not currently possible with today's seed data, but correct for when it becomes possible).

## 3. The `$adminRoles` Arrays — Not Authorization, Left Alone

Both `index.php` and `download.php` also contain an `$adminRoles` array (`['super_admin','hr_manager','hr_officer','hrofficer',...]`) used inside a `WHERE role IN (...)` SQL clause to decide which users' activity counts as "admin-side activity" for a report breakdown (as opposed to portal-side employee/consultant activity). This is a **content filter**, not an access-control gate — it doesn't decide who may use the page, it decides what a query selects once they're already authorized. It was left unchanged. It does still contain the now-corrected-in-the-database `'hrofficer'` typo string as a defensive redundant entry (harmless — it's an `IN` list, so an extra non-matching string costs nothing) — noted here for completeness, not treated as a bug requiring a code change, since the underlying data-level typo (KOM-023) is what was actually fixed.

## 4. Live Verification

| Account | Role | `activity_log/index.php` result | Matches pre-fix behavior? |
|---|---|---|---|
| superadmin | `super_admin` | 200 OK | Yes (was already allowed) |
| hrmanager | `hr_manager` | 302 redirect | Yes (was already blocked) |
| hrofficer | `hr_officer` | 302 redirect | Yes (was already blocked) |
| payroll | `payroll_officer` | 302 redirect | Yes (was already blocked) |

All four outcomes are identical to pre-Phase-1 behavior — confirming this was purely a mechanism change with zero unintended access-policy shift, which was the explicit design goal.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial Activity Log authorization report, including live verification table | Remediation Program — Phase 1 |
