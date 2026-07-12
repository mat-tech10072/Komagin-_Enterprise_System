# Komagin HR — Phase 5 Stage 5.1: Leave Approval Model Completion

**Document type:** Phase 5 Deliverable — Stage 5.1 Report
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13
**Decision:** Lock in single-stage HR-only leave approval as the permanent, intended design (user sign-off, 2026-07-13 — see `01-phase5-open-findings-scope.md` §6).

---

## 1. What Was Found

`ApprovalEngine::workflowConfig()['leave']` modeled 2 stages — Supervisor Review (`supervisor` role), then HR Approval (`hr_manager` role) — but no code anywhere ever built a supervisor-facing review step:

- `leave/apply.php` creates the workflow via `ApprovalEngine::create('leave', ...)`, which faithfully creates both stage rows per the config.
- `leave/approve.php` — the only page that actually decides a leave application — bypasses `ApprovalEngine::act()` entirely and resolves **every** pending stage on the workflow in one HR-only action (a Phase 4 fix, KOM-082, that made the workflow/stage rows agree with the real decision rather than staying permanently `pending`).
- The generic Approvals inbox (`modules/approvals/index.php`) *would* let a `supervisor`-role user act on stage 1 via the real `act()` method if one existed and tried — but since `leave/approve.php`'s later HR decision unconditionally overwrites every stage regardless of any earlier partial action, a supervisor's stage-1 approval was functionally meaningless even when technically reachable.
- No UI anywhere displayed a "Supervisor Review" progress step for leave outside the `ApprovalEngine` config itself (confirmed by a full-repository text search).

This is KOM-083, already investigated once in Phase 4 and left deferred pending a final decision — which this stage now resolves.

## 2. The Fix

`config/ApprovalEngine.php`'s `leave` entry reduced to a single stage:

```php
'leave' => [
    'label'  => 'Leave Application',
    'stages' => [
        ['name'=>'HR Approval', 'role'=>'hr_manager'],
    ],
],
```

No other file required a change: `ApprovalEngine::create()` already derives `total_stages` dynamically from `count($config['stages'])` and creates exactly that many stage rows, and `leave/approve.php`'s existing "resolve every pending stage" logic (from the Phase 4 KOM-082 fix) already works correctly regardless of stage count — it required no modification to correctly handle a single stage.

**Scope check**: confirmed the `supervisor` role itself is not being removed — it holds an extensive, legitimate permission set across many modules (`role_permissions` shows ~70 grants) independent of leave, currently with 0 assigned users. This fix removes only the leave-workflow-specific stage tied to that role; the role itself is untouched.

**Historical data**: zero existing `approval_workflows` rows of type `leave` existed at the time of this fix (confirmed by direct query) — no migration or backfill was needed for old 2-stage rows.

## 3. Live Verification

Submitted a real leave application for a disposable test date range:

| Check | Result |
|---|---|
| Resulting `approval_workflows.total_stages` | **1** (was 2 before the fix) |
| Resulting `approval_stages` row | Exactly 1 row: `stage_number=1`, `stage_name='HR Approval'`, `approver_role='hr_manager'` — no Supervisor Review stage created |
| HR approval via `leave/approve.php` | `leave_applications.status` → `approved`; `approval_workflows.status` → `approved`; the single `approval_stages` row → `approved` — all three in sync, in one action |
| Balance handling | Unaffected by this change — verified `remaining_days`/`pending_days` correctly restored to their pre-test baseline after cleanup |

All test data (leave application, workflow, stage, notifications, audit log entries) removed after verification; leave balance confirmed restored to its exact pre-test value.

## 4. Regression

| Suite | Result |
|---|---|
| PHP syntax check (`ApprovalEngine.php`) | 0 errors |
| Phase 1 regression | 20/20 |
| Phase 2 regression | 29/29 |

## 5. Register Update

KOM-083 closed: **"LOCKED IN as single-stage HR-only — Phase 5, user decision."** No further leave-approval work remains open.
