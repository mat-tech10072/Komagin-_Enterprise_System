# Komagin HR — Phase 4 Deliverable: Business Workflow Inventory

**Document type:** Phase 4 Deliverable — Business Workflow Inventory
**Status:** Compiled at the close of Phase 4, from the 13 workflow-group reviews conducted across this phase (`Workflows/01` through `Workflows/13`).
**Date compiled:** 2026-07-13

---

## Purpose

This document catalogues every distinct business workflow reviewed during Phase 4 — the sequence of steps a real business process moves through in this system, independent of which module's UI happens to expose each step. It is the "what exists" companion to the Workflow Transition Matrix (`00-workflow-transition-matrix.md`, state-by-state detail) and the Approval Flow Report (`00-approval-flow-report.md`, decision-routing detail).

---

## 1. Employee Lifecycle

`Add Employee → Active → [Promotion / Transfer / Leave / Training / Performance Review cycles] → [Termination / Resignation] → Archived`

- **Entry**: `modules/employees/add.php` — direct HR entry, or (informally) via Recruitment (no automated conversion — see §7).
- **Ongoing**: edit (`employees/edit.php`), status changes (`employees/status.php`), document upload, self-service profile-update requests (`employee_pending_updates`, requires HR approval).
- **Exit**: termination/resignation routes through `ApprovalEngine` (as of Phase 4 Workflow Group 1); archived status is terminal.
- **Approval-gated sub-workflows**: promotion, transfer, termination (see Approval Flow Report §2).

## 2. Department & Position Management

`Create Department/Position → Assign employees → [Disable if no longer needed]`

- Flat CRUD, no approval workflow. Disabling is soft (is_active flag), preserves referential integrity for existing assignments (Workflow Group 2 added impact-count visibility on disable).

## 3. Leave Management

`Apply → [Approve / Reject] → Balance adjustment`

- **Entry**: `modules/leave/apply.php` — creates a `leave_applications` row and (since Workflow Group 3's fix) a real `ApprovalEngine` workflow row, and reserves the requested days against the employee's balance immediately (`remaining_days` decremented, `pending_days` incremented).
- **Decision**: `modules/leave/approve.php` — bypasses `ApprovalEngine::act()` entirely, decides directly. Approval releases `pending_days`; rejection restores `remaining_days` (Workflow Group 3 fix) and releases `pending_days`.
- **Modeled but not built**: two-stage (Supervisor → HR) review — in practice, a single `leave.approve` holder resolves the entire request (KOM-083, deferred by user decision).

## 4. Attendance & Timesheets

`Kiosk Clock-In → [Break Out/In] → Clock-Out → Timesheet aggregation → [Correction request] → [Overtime approval]`

- **Entry**: `modules/attendance/kiosk.php` — public, token-scoped terminal, no login (Workflow Group 4 closed the no-token-fallback impersonation gap, KOM-003).
- Corrections: `modules/timesheets/corrections.php`. Overtime: auto-suggested on sign-out if hours exceed threshold, then `modules/timesheets/overtime.php` for approval.
- **Structural limitation** (Workflow Group 13, KOM-098): "absence" cannot be reliably computed beyond a single day without a working-day/holiday calendar, which doesn't exist in this codebase.

## 5. Payroll

`Create Run → [Add/Edit Payslips] → Finalize → Publish (→ email payslips if configured)`

- `modules/payroll/run_save.php` → `run_finalize.php` → `run_publish.php`, each an atomic compare-and-swap state transition (Workflow Group 5 closed a real race condition here, KOM-030).
- Deductions/savings tracked in parallel tables but never flow into payslip totals — accepted as designed per user decision (KOM-085).
- No connection to Attendance/Overtime data (documented gap, Workflow Group 4) or to Consultant/Temp Employee pay (documented gaps, Workflow Groups 9–10).

## 6. Performance Management

`Schedule Review → Submit Review → [stored, viewable]`

- No approval/acknowledgement workflow modeled; a straightforward create-and-store flow (Workflow Group 6 fixed a 100%-failure crash on submission, KOM-049).

## 7. Recruitment

`Post Vacancy → Application submitted → Pipeline stage updates → [Selected / Rejected / Withdrawn] → (manual, disconnected) re-entry as a new Employee`

- Workflow Group 7 built the previously-entirely-missing "Add Application" entry point (KOM-087).
- **Not built**: automated conversion of a selected application into an Employee record (`converted_to_employee_id` exists in schema, never read/written) — deferred by user decision (KOM-088).

## 8. Training

`Create Program → Enrol Employee → [Mark Attended] → Certificate tracking`

- Workflow Group 8 found and fixed the entire module fatally broken in 3 separate places (KOM-008, severity-corrected from High to Critical) and built the previously-missing "Mark Attended" step.

## 9. Consultant Lifecycle

`Add Consultant → Portal login enabled → Kiosk clock-in/scope entries → [Delete, with confirmation]`

- Already substantially hardened in Phase 2. Workflow Group 9 closed the one remaining gap: deletion had no confirmation safeguard (KOM-089).
- No connection to Payroll (documented as informational, consistent with contractors typically being paid outside a payroll system built for employees).

## 10. Temporary Employee Lifecycle

`Add Temp Employee (assigned to Project/Site) → Portal login (optional) → [Attendance — see limitation] → [Delete, with confirmation]`

- Workflow Group 10 fixed a reflected XSS pre-existing since baseline (KOM-020), added deletion confirmation (KOM-091), fixed an audit-log argument-order bug corrupting the entire module's audit trail (KOM-092), and corrected misleading UI claiming a working kiosk/timesheet attendance-capture mechanism that does not exist (KOM-090, UI corrected, underlying feature deferred by user decision).

## 11. Notifications

`Trigger event (Approval decision / Leave submission / Employee Hub request / etc.) → notifyRole()/createNotification() → notifications table → Bell dropdown / (Payroll only) Email`

- Workflow Group 11 closed a Critical stored-XSS in the shared notification renderer (KOM-093), added the previously-entirely-missing notify-on-decision step to `ApprovalEngine::act()` (KOM-095), and fixed an invalid ENUM value bug (KOM-094).
- **Confirmed absent**: Training, Recruitment, and password-reset notifications; any scheduled/"reminder" mechanism (no cron/scheduler exists anywhere in this codebase).

## 12. Document Generation Lifecycle

`Author Template (with letterhead/signature/stamp/watermark/QR options) → Generate for Employee → [Approve / Reject if required] → Issue → Download/Print`

- Workflow Group 12 closed two findings open since the baseline audit: branding images were completely non-functional across the whole app (KOM-006, Critical), and template bodies were unsanitized, a stored-XSS-via-template-authoring gap (KOM-022, High).
- **Confirmed absent**: the QR-code verification page it links to (`/verify-doc.php`) — dormant (0 of 47 templates enable it), left deferred by user decision (KOM-097).

## 13. Reports & Dashboards

`Live query against source tables → aggregate/format → display`

- Not a stateful workflow in its own right, but the consumer of every other workflow's data. Workflow Group 13 found and partially fixed a systemic defect (`attendance.is_absent` dead code, KOM-098) that silently zeroed out every "Absent" figure across Dashboard and Reports since the column's introduction, and a Dashboard arithmetic mismatch (KOM-099).

---

## Cross-Cutting Observations

- **The `ApprovalEngine` is far less used than its schema suggests.** Of 8 defined workflow types, only `leave` (which actually bypasses the engine's own `act()` method), `termination`, `transfer`, and `promotion` are ever instantiated. `payroll_run`, `document`, `overtime`, `correction` are dead configuration.
- **A recurring bug class this phase**: PHP code referencing database columns that don't exist, discovered independently in Employee (KOM-071), Performance (KOM-049), Training (KOM-008), Temp Employees (KOM-005, pre-Phase-4), and Consultants — always caught by directly cross-checking `INSERT`/`UPDATE`/`ORDER BY` statements against the live schema.
- **A recurring completeness-gap pattern**: several modules have a schema column or UI option that implies a working feature but has zero implementing code (Recruitment→Employee conversion, Temp Employee attendance capture, QR document verification, Training/Recruitment notifications, working-day calendar). Each was investigated, confirmed real, and either fixed (where a clean fix existed) or explicitly deferred per user decision (where building it was a genuine feature addition, not a bug fix) — never silently ignored.
