# Komagin HR — Approval Engine Report

**Document type:** Phase 1 Deliverable #2 of 10
**Finding addressed:** KOM-001 (Audit I: C-01) — the flagship Critical finding of the entire remediation program
**Date:** 2026-07-11/12

---

## 1. The Problem, Precisely

`config/ApprovalEngine.php::act()` is the single method that advances or resolves every multi-stage approval workflow in the system (leave, overtime, timesheet corrections, payroll runs, promotions, transfers, terminations, documents). Before Phase 1:

```php
public function act(int $workflowId, int $actingUserId, string $action, string $comments = ''): bool {
    $workflow = $this->getWorkflow($workflowId);
    if (!$workflow || !in_array($workflow['status'], ['pending','in_review'])) return false;
    $stage = $this->getCurrentStage($workflowId, $workflow['current_stage']);
    if (!$stage) return false;
    // ... marks the stage and advances the workflow, unconditionally ...
}
```

It checked that the workflow existed and was in an actionable state. It checked that a current stage existed. **It never checked who was calling it.** Its sole caller, `modules/approvals/index.php`, gated the whole page with `requireLogin()` and nothing else. The combination meant any authenticated user — regardless of role — could POST an approve/reject action for any workflow ID and it would be honored.

## 2. Design Decision: Where Does Authorization Live?

Objective 3 of the Phase 1 charter is explicit: *"The Approval Engine itself — not only the page controller — must verify [everything]... The Approval Engine must never trust the caller."* This is a deliberate architectural choice worth stating plainly: **for this one subsystem, the engine is the authorization boundary, not the page.** Every other module in this application gates access at the page/controller level (`requirePermission()` at the top of the file). The Approval Engine is different because "who may act" isn't a fixed role-to-feature mapping — it's *per-workflow, per-stage, and sometimes per-individual* (a stage can be assigned to a specific `approver_user_id`, not just a role). A page-level permission check cannot express that; only the engine, which has the workflow and stage loaded, can.

`modules/approvals/index.php` was correspondingly *simplified*, not tightened: it still only requires `requireLogin()` for general access (because the page's "Awaiting My Action" section legitimately needs to be reachable by nearly every role — supervisors, HR, payroll officers/managers are all assigned approvers for different workflow types), and it now wraps the actual `act()` call in a `try/catch` for the engine's typed exception. The org-wide "All Workflows" admin view (a genuinely permission-worthy, role-restricted feature) was separately converted from a hardcoded `in_array($role, ['super_admin','hr_manager'])` check to a proper `canView('approvals.manage_all')` permission — see the Permission Consistency Report.

## 3. What `act()` Now Verifies, In Order

```php
public function act(int $workflowId, int $actingUserId, string $actingUserRole, string $action, string $comments = ''): bool
```

The signature itself changed — `$actingUserRole` is now a required parameter, not derived from a global. Preconditions, checked in this order, each with its own typed rejection:

1. **Valid action** — `approve`/`reject` only, else `InvalidArgumentException`.
2. **Workflow exists** — else `ApprovalAuthorizationException('Workflow not found.')`.
3. **Workflow is actionable** — status must be `pending`/`in_review`, else the exception names the actual current status.
4. **Current stage exists and matches the workflow's `current_stage`** — else exception.
5. **Duplicate-approval prevention** — the stage's own `status` must still be `pending`; if it's already `approved`/`rejected`, the attempt is rejected with the stage's actual status named.
6. **Separation of duties** — `(int)$workflow['initiated_by'] === $actingUserId` is checked and blocked, **with no `super_admin` exemption.** This is the one place in the entire application where `super_admin` does not bypass a check, by design: separation of duties is a control on the *person*, not a feature-access permission, and an admin bypass would defeat the entire purpose of having approval stages.
7. **Correct approver** — either `$stage['approver_role'] === $actingUserRole`, or `$stage['approver_user_id'] === $actingUserId` for stages assigned to a specific individual. Fails otherwise, naming both the actual and required role in the audit log entry (not in the user-facing message, to avoid leaking role structure to an unauthorized caller).

Only after all seven pass does the engine touch the database.

## 4. Audit Logging

Every call to `act()` — allowed or blocked — writes an `audit_logs` row via a new private `auditAttempt()` method, recording the workflow ID, acting user, action, and either the success detail (e.g. `advanced_to_stage:2`) or the specific block reason (e.g. `wrong_approver_role:have=hr_officer;need=supervisor`, `self_approval_blocked`, `stage_already_actioned:approved`). This was not present before at all for blocked attempts, and only partially present (via the calling page, not the engine) for successful ones. Audit logging is wrapped in its own try/catch so a logging failure can never block or corrupt an otherwise-valid approval.

## 5. Live Verification

This is the one fix in Phase 1 that was verified with a complete, real, end-to-end scenario rather than a synthetic test:

1. Logged in as `superadmin`, submitted a real leave application via `modules/leave/apply.php` for employee `KOM-EMP-2026-0001` — this created a live `approval_workflows` row (type `leave`, stage 1, `approver_role = 'supervisor'`, `initiated_by` = superadmin's user ID).
2. **Self-approval test:** still logged in as `superadmin` (the initiator), POSTed an approve action for that exact workflow. Result: redirected with flash message *"You cannot approve or reject a workflow you initiated yourself."* Workflow status confirmed unchanged (`pending`) via direct DB query.
3. **Wrong-role test:** logged in as `hrofficer` (role `hr_officer`, not `supervisor`, not the initiator), POSTed an approve action for the same workflow. Result: *"You are not the assigned approver for this stage."* Workflow status confirmed still `pending`.
4. **Invalid-ID test:** POSTed an approve action for workflow ID `999999`. Result: *"Workflow not found."* — no fatal error, no stack trace; the typed exception was caught cleanly by `modules/approvals/index.php`.
5. Test data (the workflow, its stage, and the underlying leave application) was deleted from the database after verification to avoid polluting the seeded dataset.

All four outcomes matched the intended design exactly, with zero unintended state mutation in the three rejection cases.

## 6. What Was Deliberately Not Touched

`ApprovalEngine::create()` (workflow creation) and `getPendingForUser()`/`getAll()` (read paths) were not modified — they had no reported authorization defect, and Phase 1's charter forbids workflow redesign. `ApprovalEngine::cancel()` has an unrelated data-correctness bug (string concatenation via `+` instead of `.`, tracked as KOM-047/L-02) — it is currently dead code (no caller) and out of scope for an authorization-focused phase.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial Approval Engine report, including live verification evidence | Remediation Program — Phase 1 |
