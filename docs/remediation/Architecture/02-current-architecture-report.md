# Komagin HR — Current Architecture Report

**Document type:** Phase 0 Baseline Deliverable #2 of 9
**Status:** Documentation only — describes the system as it exists; no redesign proposed.
**Date compiled:** 2026-07-11
**Baseline tag:** `v1.0-enterprise-baseline`

---

## Purpose

This report documents the current implementation of every major architectural subsystem, as it actually behaves today — not as it was intended to behave, not as it should behave. It is the reference point for controlled remediation: every future change should be traceable to a specific deviation from what's described here.

Full mechanism-level detail for Authentication lives in the separate **Authentication Report** (05); full detail for the Document/Branding pipeline lives in the separate **Document Pipeline Report** (06); the full role×permission data lives in the **Permission Matrix Report** (04). This document gives the architectural shape of each subsystem and cross-references those companion reports rather than duplicating their content.

---

## 1. Authentication Architecture

Four independent, non-interoperating authentication surfaces exist side by side:

| Surface | Entry point | Session key | Session config location |
|---|---|---|---|
| Admin/Staff | `auth/login.php` | `$_SESSION['user_id']` | `auth/session.php` (shared include) |
| Employee Portal (permanent) | `employee-portal/login.php` | `$_SESSION['ep_employee_id']` + `ep_policy_agreed` | inline in `employee-portal/login.php` / `_session.php` |
| Employee Portal (temporary) | `employee-portal/login.php` (fallback branch) | `$_SESSION['ep_is_temp']` + `ep_temp_employee_id` | same file, distinct session keys |
| Consultant Portal | `consultant-portal/login.php` | `$_SESSION['cp_consultant_id']` | inline in `consultant-portal/login.php` / `_session.php` |
| Self-Service (magic link) | `self-service/update.php` | none — stateless, token-in-URL | inline, 1-hour session cookie |
| Kiosk (no login) | `modules/attendance/kiosk.php` | none — employee-number-only | PHP default session config |

Each surface implements its own copy of: cookie parameter setup, session ID rotation, idle timeout, and logout. There is no shared session bootstrap module across all four — `auth/session.php` is used only by the admin surface; the other three each inline their own near-duplicate of the same pattern with small, undocumented behavioral differences (see Authentication Report §2–5, §10 for the exact divergences).

**Full detail:** see `Authentication Report` (Deliverable 05).

---

## 2. Authorization Architecture

The admin surface uses a single centralized authorization model:

```
requireLogin() → requirePermission(slug, action) → hasPermission(slug, action) → _loadRolePermissions() → role_permissions table
```

- `hasPermission()` short-circuits to `true` unconditionally for `$_SESSION['user_role'] === 'super_admin'` (`config/functions.php`) — super_admin never touches the `role_permissions` table.
- For every other role, `_loadRolePermissions()` loads that role's full permission row set once per request (static cache) and every subsequent `canView()`/`canEdit()`/`canDelete()`/`canApprove()`/`canExport()`/`canPublish()`/`canShare()`/`canCreate()` call reads from that cached matrix.
- `requirePermission()` additionally writes an `audit_logs` entry on every denial before redirecting.

**This model is not applied uniformly.** Baseline inventory (see Permission Matrix Report §4) found at least 10 files that perform authorization via hardcoded role-list checks (`in_array($_SESSION['user_role'], [...])`) instead of the permission functions above, and one entire module (`modules/activity_log/*`) that bypasses the permission system completely with a hardcoded `super_admin`-only gate. `config/functions.php` also defines a `requireRole(array $roles)` helper as a second, parallel authorization primitive — it has zero call sites anywhere in the codebase (dead code, confirmed).

The Employee Portal, Consultant Portal, and Self-Service surfaces do **not** use this authorization model at all — they have no concept of `role` or `permission`; access is scoped entirely to "is this session bound to employee/consultant record N," checked by lightweight guard functions (`epRequireLogin()`, `cpRequireLogin()`) local to each portal.

**Full detail:** see `Permission Matrix Report` (Deliverable 04).

---

## 3. Permission Framework

DB-driven: `permissions` table (94 seeded slugs across 13 modules, grouped by prefix e.g. `employees.*`, `payroll.*`) × `role_permissions` table (11 roles × 8 action columns: `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`). Seed source is split across four files applied in sequence: `phase1_permissions.sql` (base 79), `phase5_branding_theme.sql` (+7 branding/email), `phase8_temp_employees.sql` (+4 temp employees), `phase9_consultants.sql` (+4 consultants).

Two of those four seed files (`phase8`, `phase9`) grant their permissions to a misspelled role string (`'hrofficer'`) that does not match the canonical ENUM value (`'hr_officer'`) — see Permission Matrix Report §6. This is a data-seeding defect, not an architecture defect, but it means the framework's real-world behavior for that one role diverges from its design for two entire modules.

**Full detail:** see `Permission Matrix Report` (Deliverable 04).

---

## 4. Approval Engine

`config/ApprovalEngine.php` — a shared class used by Leave, Payroll, and Documents to route multi-stage sign-off. Backed by two tables neither of which has a `CREATE TABLE` statement anywhere in the tracked SQL files: `approval_workflows` and `approval_stages` (see Database Inventory Report §5 — schema-drift finding).

Mechanism: a workflow is created with an ordered list of stages, each stage carrying an `approver_role`. `ApprovalEngine::act()` advances the current stage when called. As documented in the prior security audits and reconfirmed in this baseline pass, `act()` does not compare the acting user's `$_SESSION['user_role']` against the stage's `approver_role` before advancing it, and the sole UI entry point (`modules/approvals/index.php`) gates access with `requireLogin()` only — no `requirePermission()` call. Functionally, the engine's stage-sequencing logic operates correctly; the gap is that any authenticated identity, not just the assigned approver, can trigger a stage transition.

`ApprovalEngine::cancel()` builds its cancellation-reason string using PHP's `+` operator rather than string concatenation, which is presently unreachable dead code (no caller invokes `cancel()`).

---

## 5. Attendance Flow

Two parallel attendance-recording paths write to the same `attendance` table:

1. **Admin-managed corrections** (`modules/timesheets/corrections.php`, `modules/timesheets/edit.php`) — HR-initiated adjustments to existing attendance rows.
2. **Kiosk self-service clock-in** (`modules/attendance/kiosk.php`) — the primary data-entry path for daily attendance. No login; identity is asserted purely by typing an employee number. A `kiosk_sessions` table (open/closed state per physical location) and a `kiosk_audit` table (event log) back this flow — **neither table has a `CREATE TABLE` statement in any tracked SQL file** (Database Inventory Report §5).

When no kiosk-location token is present in the request, the kiosk falls back to matching *any* globally-open kiosk session rather than a specific terminal. Rate limiting (10 failed attempts / 5 min) is scoped by requesting IP and only counts `failed_auth` events (unrecognized employee number), not successful clock-actions against a valid, guessed employee number.

`modules/attendance/kiosk_manage.php` is the admin-side control surface for opening/closing kiosk sessions and viewing kiosk_audit history, gated by the `kiosk.manage` permission.

---

## 6. Payroll Flow

```
Employee data (basic_salary, bank_*) → modules/payroll/payslips.php (create/edit)
   → modules/payroll/run_save.php (group payslips into a payroll_run)
   → modules/payroll/run_finalize.php (lock the run)
   → modules/payroll/run_publish.php (mark payslips 'sent', trigger sendEmail() per employee)
```

Supporting: `modules/payroll/deductions.php` (recurring deductions), `modules/payroll/savings.php` (cooperative/pension tracking), `modules/payroll/reports.php` (aggregation + CSV export). Backed by `payroll_runs`, `payroll_deductions`, `payslip_items`, `employee_savings` — all four defined only in `migration_v2.sql`, not `schema.sql` (Database Inventory Report §3).

The `payslips.php` update path does not gate on payslip status (no guard preventing edits to an already-`finalized`/`sent` payslip) and does not call the shared `auditLog()` helper on update (only on create). Salary/bank fields are masked in read views via `canViewSalaryData()`/`canViewBankData()` (gated by `payroll.view`), but this masking is applied per call-site rather than at a data-access layer, so its coverage is only as complete as each view file's individual discipline in calling it.

No automated salary-calculation engine exists — payslip amounts (basic salary, deductions, tax, UIF, overtime) are entered/edited manually per payslip; deductions and savings do not auto-populate into payslip totals.

---

## 7. Leave Workflow

```
employee-portal (view only, no self-apply) / modules/leave/apply.php (HR-initiated on employee's behalf)
   → balance + overlap validation
   → INSERT leave_applications
   → ApprovalEngine workflow created
   → modules/leave/approve.php (approve/reject, balance decrement on approval)
```

`apply.php`'s post-save notification call (`notifyRole()`) is called with a mismatched argument shape (an array where a single string role is expected) against the function's actual signature in `config/functions.php`, which throws after the `INSERT` has already committed. `approve.php` reads only `$_GET` for its action parameters, while the detail page `view.php` submits the corresponding form via `$_POST` — the two never connect for that entry point.

---

## 8. Training Workflow

```
modules/training/index.php (course catalogue + roster)
   → modules/training/enrol.php (enrol an employee) → training_attendance table
```

`enrol.php` writes the enrolled program's foreign key into a column named `program_id`; `index.php`'s roster/attendee-count queries join against a column named `training_id` on the same table. The two files disagree on the column name for what is conceptually the same relationship.

---

## 9. Recruitment Workflow

```
modules/recruitment/index.php (vacancy + applicant pipeline dashboard)
   → modules/recruitment/vacancy_save.php (create/edit vacancy)
   → modules/recruitment/application_update.php (advance applicant through pipeline stages)
```

No candidate-facing public application intake exists — all applicant records are entered by HR staff internally; there is no external form or portal for candidates to self-submit.

---

## 10. Document Generation

Template-driven HTML rendering with placeholder substitution, browser-print for "PDF" output (no server-side PDF library present anywhere in the codebase). Full pipeline trace — template authoring, `DocumentEngine.php` internals, branding-asset fetch, escaping model, upload validation — is documented in the dedicated **Document Pipeline Report** (Deliverable 06), since the user request for this baseline specifically called out this subsystem for deep inspection.

---

## 11. Branding Pipeline

Four asset types (letterhead, signature, stamp, watermark) managed from one page (`modules/settings/branding.php`), each stored under its own `uploads/<type>/` folder with an `is_active` DB flag controlling which asset is currently live. All four upload folders carry a `.htaccess` using legacy Apache 2.2 `Deny from all` syntax, inconsistent with the parent `uploads/.htaccess`'s modern, narrowly-scoped syntax — flagged in both prior audits as a likely-broken image-serving path (see Deployment Inventory Report §10, and Master Remediation Register KOM-006/NC-02).

**Full detail:** see `Document Pipeline Report` (Deliverable 06).

---

## 12. Employee Portal

Self-service read-mostly portal for permanent employees (dashboard, employment details, attendance history, leave history/balance, payslips, savings, hub/requests) plus a policy-acceptance gate (`policy.php`) that must be passed once per... (session — `ep_policy_agreed` is a session flag, not a persisted DB flag, so it resets every login) before the rest of the portal becomes reachable. A parallel, simplified single-page portal (`temp_portal.php`) serves temporary employees using a distinct session-key pair and does not include the shared `_session.php` guard module (uses its own inline, less-frequently-rotated session setup).

---

## 13. Consultant Portal

Self-service portal for external consultants: dashboard, time-based kiosk-style clock-in/out (`kiosk.php`), and output-based scope/checklist tracking (`scope.php`) — the two working models ("time-based" vs "output-based" consultants) are switched on a `cp_type` session value set at login, checked via `cpRequireType()`.

---

## 14. Temporary Employee Portal

Reached via `employee-portal/temp_portal.php` (not a separate top-level portal directory — it lives inside `employee-portal/` and is dispatched to from the same `login.php`). Combines dashboard, attendance, and payslip-adjacent views into a single page rather than the separate pages the permanent-employee portal uses.

---

## 15. Notification System

`notifyRole()` (creates in-app `notifications` rows for every user holding a given role) and `notifyUser()` (single-user variant), both in `config/functions.php`. `api/notifications.php` is the read/mark-read AJAX endpoint the header bell icon polls. `sendEmail()`, also in `config/functions.php`, is a separate mechanism (see §Deployment Inventory Report §15) — real outbound email over hand-rolled SMTP sockets or PHP `mail()`, logged to `email_logs`, distinct from the in-app `notifications` table.

---

## 16. Reporting System

`modules/reports/` — four report surfaces (hub, executive analytics, employee master report, timesheet report), each independently implementing its own filter-building and CSV export logic rather than sharing a common reporting/export layer. `executive.php` aggregates payroll cost data for its charts without routing through the `canViewSalaryData()` masking helper used elsewhere.

---

## 17. Audit Logging

**Two parallel systems**, both ultimately reading/writing the single `audit_logs` table:

1. `modules/audit/index.php` — the original viewer, correctly gated by the `audit.view` permission (DB-driven, role-sensitive).
2. `modules/activity_log/{index,user,download}.php` — a newer, larger (65KB combined), more feature-rich viewer (per-user drill-down, CSV export) added after the first, gated by a hardcoded `super_admin`-only check that bypasses the permission system entirely, and never wired to replace or redirect from the older module.

Both remain live in the sidebar navigation simultaneously (`includes/header.php`).

---

## 18. Session Management

No shared session module across all authentication surfaces — see §1 above and the full mechanism-by-mechanism comparison in the Authentication Report (Deliverable 05, §2–5). Summary of the divergence: only the admin surface (`auth/session.php`) conditionally sets the `secure` cookie flag based on HTTPS/`APP_ENV`; the other three surfaces (employee portal, consultant portal, self-service) never set it under any condition. Session ID regeneration on login is present only on the admin surface. Idle-timeout destruction is implemented three different ways across the three portal-adjacent surfaces (`session_unset()`+`session_destroy()` vs. `session_destroy()` alone vs. manual per-key `unset()`).

---

## 19. Upload Pipeline

Single shared helper, `uploadFile()` in `config/functions.php`, used by employee photo uploads, document uploads, and branding asset uploads alike. Performs real server-side MIME detection (`finfo`, not client-supplied `Content-Type`), enforces `MAX_FILE_SIZE` (10MB), and always writes a randomized filename (`uniqid()+time()`) — but takes the file *extension* for that randomized name directly from the client-supplied original filename, with no cross-check against the detected MIME type.

---

## 20. Database Layer

Single `Database` class (`config/database.php`) — a singleton PDO wrapper, `utf8mb4` charset, no ORM. Every module hand-writes its own SQL against this shared connection object. No query builder, no migration-runner tooling — schema changes are applied by manually executing `.sql` files in an undocumented, partially-inconsistent order (see Database Inventory Report §6 for the confirmed broken dependency: `phase5_branding_theme.sql` ALTERs a table, `doc_templates`, that no tracked file ever creates).

**Full detail:** see `Database Inventory Report` (Deliverable 03).

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-11 | Initial baseline architecture report compiled for Phase 0 | Remediation Program — Phase 0 |
