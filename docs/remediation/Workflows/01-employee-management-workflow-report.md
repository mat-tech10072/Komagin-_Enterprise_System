# Komagin HR — Phase 4 Workflow Group 1: Employee Management

**Document type:** Phase 4 Deliverable — Workflow Group Report 1 of N
**Status:** Live-verified. Findings confirmed via direct HTTP testing against disposable test records (created and fully cleaned up afterward — zero impact on the 13 real employee records).
**Date compiled:** 2026-07-12
**Scope:** Employee creation, onboarding, activation, probation, confirmation, transfers, promotions, resignation, termination, rehire — every state transition on the `employees.status` field and every code path that mutates a "live" employee record.

---

## 1. Workflow Inventory — What Exists

| Action | File | Trigger |
|---|---|---|
| Create | `modules/employees/add.php` | HR submits new-hire form |
| Onboarding | `modules/onboarding/index.php` (checklist against `onboarding_checklists`) | Separate module, linked by `employee_id` |
| Edit / Transfer / Promotion | `modules/employees/edit.php` | Single undifferentiated form — same code path for a typo fix, a department transfer, or a salary promotion |
| Status change (activation, probation→confirmation, suspension, resignation, termination, deceased, archive, reactivation/rehire) | `modules/employees/status.php` | Single free-form dropdown — any status to any other status |
| Self-service field updates | `self-service/update.php` (employee-submitted) + `modules/employees/pending_updates.php` (HR approval) | Magic-link, no login |
| Hard delete | `modules/employees/delete.php` | Type-to-confirm, full cascade-impact preview |

**No dedicated "Onboard," "Confirm Probation," "Promote," "Transfer," or "Rehire" action exists.** All of these are the same generic Edit or Status-Change form. This is not necessarily wrong (a simpler system can reasonably choose one generic mechanism), but it means none of these business-significant events get distinct validation, notification, or approval treatment relative to a routine data correction — see §3.

## 2. Critical Finding — `personal_email` Column Never Existed: 3 Workflows Fatally Broken (FIXED)

**This is the most severe finding in this workflow group.** `personal_email` is referenced by three separate code paths — the duplicate-check and `UPDATE` in `modules/employees/edit.php`, the approval handler in `modules/employees/pending_updates.php`, and the initial `SELECT` in `self-service/update.php` — but the column existed in **none** of: the canonical `schema.sql`, any other tracked migration, or the live production database.

**Live-verified impact before the fix:**

| Code path | Result |
|---|---|
| `modules/employees/edit.php` — saving ANY employee edit | Uncaught `PDOException`, HTTP 200 with a raw fatal-error page (including a leaked server file path). Confirmed live against a disposable test employee. **Every real "Edit Employee" save has been failing this way.** |
| `self-service/update.php` — an employee opening their magic-link update page | Uncaught `PDOException` on the very first query, HTTP 200 with a raw fatal-error page. Confirmed live with a real, validly-generated token. **Every employee who has ever clicked a self-service update link has hit this.** |
| `modules/employees/pending_updates.php` — HR approving a `personal_email` self-service change request | Would fatally fail the `UPDATE employees SET \`personal_email\`=?...` (confirmed by code path; the SELECT-blocking bug above means no real request of this specific type could ever have reached this stage in production, so this branch was latent rather than separately observed failing) |

**Fix applied:** `personal_email varchar(150) DEFAULT NULL` added to `employees` in `database/schema.sql` (after `email`), and applied live to the production database via new `database/phase12_workflow_integrity_fixes.sql` (idempotent, `ADD COLUMN IF NOT EXISTS`).

**Verified live after the fix:** Edit Employee save now returns `302` and writes correctly; the self-service update page loads `200` with real content (no fatal error, "Personal Email" field renders); approving a `personal_email` pending-update request now correctly writes the new value. All three re-tested against the disposable test employee before it was deleted.

**Finding ID:** KOM-071 (new)

## 3. Finding — Terminations, Transfers, and Promotions Bypass the Approval Engine Entirely (FIXED, per user direction)

`approval_workflows.workflow_type` is an ENUM that explicitly includes `promotion`, `transfer`, and `termination` as first-class values, and `config/ApprovalEngine.php`'s `workflowConfig()` defines a full stage/approver-role configuration for all three (each currently: one `hr_manager` review stage). **Nothing in the codebase ever called `ApprovalEngine::create()` for any of them.** Of the 8 workflow types the engine supports, only `leave` (in `modules/leave/apply.php`) was ever actually instantiated.

**User direction:** wire all three into the approval engine (option 3 of the 3 presented).

**Fix implemented:**
- `modules/employees/status.php`: setting status to `terminated` no longer applies immediately. It creates an `ApprovalEngine` workflow (`type='termination'`, `hr_manager` review stage), notifies `hr_manager` role holders, and leaves the employee's actual `status` untouched until approved.
- `modules/employees/edit.php`: a department/supervisor change is now detected as a **transfer**, and a position/salary change as a **promotion** — both fields are held at their current value in the immediate `UPDATE` and instead carried in their own pending workflow. Every other field on the same form (name, contact info, bank details, emergency contacts) still applies instantly, since only transfer/promotion fields are approval-gated by the schema's own design.
- `config/ApprovalEngine.php`: `updateReference()` extended so that, on approval (not rejection), the proposed change is read back out of the workflow's `notes` JSON and actually applied — `applyApprovedTermination()` updates `employees.status`/`status_reason`/`exit_date`, writes `employee_status_history`, and disables the linked user account; `applyApprovedTransferOrPromotion()` applies the relevant field pair. Rejection leaves the employee record completely untouched, by design.

**Live-verified end-to-end**, using disposable test employees and a real second-actor approval (logged in as `hrmanager`, a different user than the one who submitted the request, honoring the engine's existing separation-of-duties check):
- Termination: submitted as `superadmin` → employee status remained `active` → approved as `hrmanager` → status flipped to `terminated`, `status_reason`/`exit_date` recorded, linked user account disabled, `employee_status_history` row written.
- Termination rejection: submitted → rejected as `hrmanager` → employee status confirmed untouched (`active`).
- Transfer: submitted a department change → `department_id` unchanged pending approval → approved → `department_id` updated to the requested value.
- Promotion: submitted a position + salary change → both fields unchanged pending approval → approved → both applied correctly.

**Finding ID:** KOM-072 (new, fixed)

## 4. Finding — Unrestricted Any-to-Any Status Transitions (FIXED, per user direction)

`status.php` allowed any of the 8 `employees.status` values to transition directly to any other, with no legality check. **Live-verified before the fix**: an `active` employee was moved directly to `archived` (normally a terminal/records-only state), then directly back to `active` — both succeeded with zero server-side objection beyond the free-text reason field.

**Fix implemented:** a transition matrix now governs which `(old_status → new_status)` pairs are legal for ordinary roles:

| From | Legal next statuses |
|---|---|
| `active` | probation, suspended, on_leave, resigned, terminated, deceased, archived |
| `probation` | active, suspended, on_leave, resigned, terminated, deceased |
| `suspended` | active, on_leave, resigned, terminated, deceased |
| `on_leave` | active, suspended, resigned, terminated, deceased |
| `resigned` | archived, active, probation *(reactivation/rehire)* |
| `terminated` | archived, active, probation *(reactivation/rehire)* |
| `deceased` | archived *(terminal, records-closure only)* |
| `archived` | active, probation *(reactivation/rehire)* |

`super_admin` may override the matrix entirely — a deliberate escape hatch for genuine data-entry corrections, so a mis-specified matrix can never permanently trap a real record; every other role is held to it. The status dropdown itself now only lists legal next-statuses for the current role (super_admin sees an additional "Override" group for everything else).

**Live-verified**: `terminated → suspended` correctly rejected for `hrmanager` (not in the matrix); `terminated → archived` correctly succeeded (in the matrix); `archived → suspended` correctly succeeded for `superadmin` only, via the override path.

**Finding ID:** KOM-073 (new, fixed)

**This was deliberately not fixed automatically**, for the same reason as §3 — defining "which transitions are legal" is an HR business-policy decision I'm not positioned to make unilaterally, and a wrong guess would block legitimate HR corrections (e.g., an HR team might have a real, accepted reason to reverse an accidental archive). Recommend: you specify the intended transition matrix (or confirm "any-to-any is intentional, HR is trusted"), and I implement server-side enforcement accordingly in a follow-up.

**Finding ID:** KOM-073 (new). Flagged for your decision.

## 5. Fixed — One-Directional Account Disable on Exit/Reactivation

`status.php` disabled the linked `users` account (`is_active=0`) when an employee's status changed to `resigned`/`terminated`/`deceased`/`archived`, but had no corresponding re-enable when the employee was brought back to `active`/`probation` — e.g. a rehire handled by reactivating the existing record rather than creating a new one. **Live-verified**: terminated a test employee (account correctly disabled), then reactivated to `active` — account remained disabled (`is_active=0`) with no path to fix it except a manual, undocumented trip to the Users module.

**Fix:** added the missing re-enable branch — returning to `active`/`probation` from any exit status now sets `is_active=1`. **Live-verified after fix**: identical terminate→reactivate sequence now correctly restores `is_active=1`.

**Finding ID:** KOM-074 (new, fixed)

## 6. Fixed — Exit Statuses Didn't Require an Exit Date

`status.php`'s `exit_date` field was optional server-side for every status, including `resigned`/`terminated`/`deceased` — the UI only conditionally *displayed* the field via JavaScript for those three statuses, but nothing enforced it being filled in, and nothing stopped it being silently skipped. **Fix:** server-side validation now requires `exit_date` when the new status is `resigned`, `terminated`, or `deceased` (matching the UI's own existing definition of which statuses are "exits"). **Live-verified**: a termination attempt with no exit date is now rejected with a clear error; the identical request with a date succeeds.

**Finding ID:** KOM-075 (new, fixed)

## 7. Fixed — No Duplicate Detection for Rehires / Same-Person Re-Entry

`national_id` had no uniqueness check anywhere (only `email` was checked) — a former employee (resigned/terminated, any status) could be re-added via `add.php` as a completely new, disconnected record: new `employee_number`, new `id`, fresh leave balances, zero link to their prior employment history, while their old record sits inert and unrelated. **Fix:** `add.php` now blocks a duplicate `national_id` with a message identifying the existing record and its status, explicitly suggesting reactivation instead; `edit.php` got the same check (excluding self) for consistency. **Live-verified**: attempting to add a second employee with a `national_id` already in use is correctly rejected, referencing the existing employee number.

**Finding ID:** KOM-076 (new, fixed)

## 8. Fixed — Audit Log for Employee Edits Didn't Capture What Changed

`edit.php`'s `auditLog()` call recorded the full pre-edit snapshot in `old_value`, but `new_value` only ever contained `first_name`/`last_name` — meaning a transfer (department/supervisor change) or promotion (position/salary change) left no independently reconstructable record of the actual new state in the one field meant to show "what changed to what." **Fix:** `new_value` now includes `department_id`, `position_id`, `supervisor_id`, `employment_type`, `basic_salary`, `start_date`, `contract_end_date` alongside the name. **Live-verified**: a department transfer's audit log entry now shows the new `department_id` directly.

**Finding ID:** KOM-077 (new, fixed)

## 9. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`status.php`, `edit.php`, `add.php`) | 0 errors |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group, disposable test records) | 7/7 scenarios passed: fatal-error fix ×3 paths, exit-date requirement, account re-enable, national_id duplicate block, audit-log completeness |

No regression to Phase 1 (authorization) or Phase 2 (authentication/session) guarantees. All test employee/user/audit rows created for this group's live testing were deleted afterward; production employee count confirmed unchanged (13, before and after).

## 10. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-071 — `personal_email` column never existed, 3 workflows fatally broken | Critical | **Fixed** |
| KOM-072 — Promotion/transfer/termination bypass the approval engine entirely | High | **Fixed** (per user direction: wired all three) |
| KOM-073 — Unrestricted any-to-any status transitions | Medium | **Fixed** (per user direction) |
| KOM-074 — One-directional account disable (reactivation doesn't restore access) | High | **Fixed** |
| KOM-075 — Exit statuses didn't require an exit date | Medium | **Fixed** |
| KOM-076 — No duplicate detection for rehires (`national_id`) | Medium | **Fixed** |
| KOM-077 — Employee-edit audit log didn't capture actual changes | Low | **Fixed** |

**All 7 findings fixed and live-verified.** KOM-072 and KOM-073 were initially flagged as business-policy decisions rather than fixed unilaterally; the user reviewed both and directed a full fix (wire all three workflow types into the approval engine; enforce the transition matrix with a super_admin override). Both are now implemented and live-tested end-to-end, including a real second-actor approval and a rejection path.
