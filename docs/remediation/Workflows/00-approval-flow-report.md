# Komagin HR — Phase 4 Deliverable: Approval Flow Report

**Document type:** Phase 4 Deliverable — Approval Flow Report
**Status:** Compiled at the close of Phase 4, from direct review of `config/ApprovalEngine.php` and every call site, live-tested during Workflow Groups 1, 3, and 11.
**Date compiled:** 2026-07-13

---

## 1. The Engine's 8 Configured Workflow Types

`ApprovalEngine::workflowConfig()` defines 8 workflow types, each with 1–2 stages and a required approver role per stage:

| Type | Stages | Approver role(s) | Actually created anywhere in the codebase? |
|---|---|---|---|
| `leave` | Supervisor Review → HR Approval | `supervisor`, `hr_manager` | Yes — `leave/apply.php`, but the decision **bypasses `act()` entirely** (see §3). |
| `overtime` | HR Review | `hr_officer` | **No.** Overtime approval (`timesheets/overtime.php`) does not use `ApprovalEngine` at all. |
| `correction` | HR Approval | `hr_officer` | **No.** Timesheet corrections do not use `ApprovalEngine`. |
| `payroll_run` | Payroll Officer Review → Payroll Manager Approval | `payroll_officer`, `payroll_manager` | **No.** Payroll's own atomic status-transition mechanism (`draft`→`finalized`→`published`) is entirely separate from this engine. |
| `promotion` | HR Manager Review | `hr_manager` | Yes — `employees/edit.php` (built in Workflow Group 1). |
| `transfer` | HR Manager Review | `hr_manager` | Yes — `employees/edit.php` (built in Workflow Group 1). |
| `termination` | HR Manager Review | `hr_manager` | Yes — `employees/status.php` (built in Workflow Group 1). |
| `document` | HR Review | `hr_officer` | **No.** Document approval (`documents/view_generated.php`) uses its own direct status field, not `ApprovalEngine`. |

**Only 3 of 8 configured types are ever actually created**, and a 4th (`leave`) is created but its decision is handled entirely outside the engine. This means `ApprovalEngine::act()` — the engine's authorization boundary, separation-of-duties check, and (as of Workflow Group 11) notification logic — is, in current practice, exercised only by termination/transfer/promotion.

## 2. `act()` — The Authorization Boundary

Every call to `act($workflowId, $actingUserId, $actingUserRole, $action, $comments)` independently re-derives whether the action should be allowed, in this order:

1. Workflow exists and is in `pending`/`in_review` status.
2. The current stage exists and is still `pending` (duplicate-approval prevention).
3. **Separation of duties**: `initiated_by !== $actingUserId` — the person who submitted the request can never be the one who resolves it, regardless of role. `super_admin` is deliberately **not** exempted from this check (Phase 1 finding KOM-001 fixed this).
4. The acting user's role matches the stage's `approver_role`, or their user ID matches a specific `approver_user_id`.

Every attempt — allowed or blocked — is written to `audit_logs` via `auditAttempt()`, independent of the calling page's own audit logging.

On approval of the final stage, `updateReference()` applies the real change (see §4). On rejection, `updateReference()` is called with `'rejected'` (a no-op for most reference tables by design — rejecting a termination request should leave the employee's record untouched, which it does).

**As of Workflow Group 11 (KOM-095)**: both the approve and reject paths now also call `notifyInitiator()`, which sends a `success`/`danger`-typed notification to `approval_workflows.initiated_by` with the outcome and the reviewer's comments. Before this fix, the requester had no in-app signal a decision had even been made.

## 3. The `leave` Exception

`leave/apply.php` creates a real `ApprovalEngine` workflow via `->create('leave', ...)`. But the actual decision happens in `leave/approve.php`, which:
- Updates `leave_applications.status` directly.
- Manually resolves the matching `approval_workflows` row and every one of its `approval_stages` (fixed in Workflow Group 3, KOM-082 — this sync was previously missing entirely, leaving the Approvals module showing every decided leave application as permanently still-pending).
- Sends its own notification directly to the applicant (`createNotification()`), independent of `ApprovalEngine::act()`'s `notifyInitiator()`.

**Practical effect**: the 2-stage Supervisor→HR design the schema and this engine both model is not what actually happens for leave. A single `leave.approve` holder (in practice, HR) resolves the entire request in one step, supervisor stage included. This is KOM-083 (Workflow Group 3), left open and flagged for a product decision (not fixed unilaterally) — deferred by the user, matching the pattern already established for `leave`'s single-stage-in-practice behavior at the time (KOM-072's analogous finding for promotion/transfer/termination was resolved by wiring those three into the real engine; leave's equivalent gap was explicitly left as-is).

## 4. `updateReference()` — Applying the Real Change

A private dispatcher, keyed by `workflow_type`:
- Generic types flip a status column via a configured `$columnMap` (not exercised in practice, since `overtime`/`correction`/`payroll_run`/`document` are never created — see §1).
- `termination` → `applyApprovedTermination()` (Workflow Group 1): sets the employee's status to the requested exit status, disables the linked `users` account.
- `transfer`/`promotion` → `applyApprovedTransferOrPromotion()` (Workflow Group 1): applies the department/supervisor or position/salary change stored in the workflow's `notes` JSON at creation time — the employee record is held at its *current* value until approval, only changing on `approved`.

## 5. Who Gets Notified, and Who Doesn't

| Event | Notified | Not notified |
|---|---|---|
| Leave decided | The applicant (direct, `leave/approve.php`) | — |
| Termination/transfer/promotion decided | The initiator (HR staff who submitted the request) — fixed this phase, KOM-095 | The employee the workflow is *about* (e.g., the person being terminated) — **deliberately** left out of scope. For a termination specifically, notifying the subject via an automated in-app popup is a human-conversation matter, not a system notification; extending this to transfer/promotion is a separate, less-sensitive question not investigated this phase. |
| Any workflow reaching an intermediate stage (multi-stage `in_review` advance) | No one | Not currently reachable in practice — every workflow type actually created today (`termination`/`transfer`/`promotion`) is single-stage, so this code path is unexercised, not just unwired. |

## 6. Summary Assessment

The engine's authorization and audit-trail mechanics (separation of duties, role/assignee matching, duplicate-action prevention, attempt logging) are sound and were already correctly built before Phase 4 (Phase 1's KOM-001). What Phase 4 found and fixed were gaps in what happens *around* a correctly-authorized decision: the real employee-record change wasn't being applied at all for 3 of the engine's types (KOM-072, fixed), a parallel manual-sync path for `leave` was silently out of date with the engine's own state (KOM-082, fixed), and no one was told a decision had been made (KOM-095, fixed). The remaining gap — half the engine's configured workflow types are dead configuration, and `leave`'s modeled 2-stage review isn't what actually happens — is a design/scope question, not a defect, and was correctly left for a future product decision rather than built or removed unilaterally.
