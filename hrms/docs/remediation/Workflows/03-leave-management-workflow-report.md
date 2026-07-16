# Komagin HR — Phase 4 Workflow Group 3: Leave Management

**Document type:** Phase 4 Deliverable — Workflow Group Report 3 of N
**Status:** Live-verified against a disposable test employee, fully cleaned up afterward.
**Date compiled:** 2026-07-12
**Scope:** Full lifecycle — Request → Supervisor Review → HR Review → Approval → Balance deduction → Notification → Reporting. No duplicate deductions. Rejected requests restore balances correctly.

---

## 1. Workflow Inventory — What Exists

| Action | File |
|---|---|
| Apply | `modules/leave/apply.php` |
| Approve/Reject | `modules/leave/approve.php` |
| List/filter | `modules/leave/index.php` |
| View detail | `modules/leave/view.php` |
| Leave types admin | `modules/leave/types.php` |

## 2. Critical Finding — Rejected Leave Applications Never Restored `remaining_days` (FIXED)

The charter explicitly calls out this exact requirement ("Ensure rejected requests restore balances correctly") — and it was **not** correct. `apply.php` reserves a leave request by both incrementing `pending_days` and decrementing `remaining_days`. `approve.php`'s reject branch only ever decremented `pending_days` back — it never credited `remaining_days` back. **Live-verified**: an employee with 9.0 remaining days submitted a 2-day request (correctly reserved down to 7.0), which was then rejected — `pending_days` correctly returned to 0, but `remaining_days` stayed at 7.0 instead of returning to 9.0. Every rejected leave application permanently and silently shrank the employee's real balance by the rejected amount.

**Fix:** the reject branch now also restores `remaining_days` (capped at `entitled_days` as a defensive guard against any drift). **Live-verified after fix**: an identical reject cycle now correctly returns both `pending_days` and `remaining_days` to their pre-application values.

**Finding ID:** KOM-081 (new, fixed)

## 3. Critical Finding — `notifyRole()` Crashed Every Single Leave Submission (FIXED — this is KOM-007, previously documented but never fixed)

This is the Master Register's own KOM-007, open since Phase 0, targeted at "Phase 2" but never actually addressed there (Phase 2's charter was authentication/session, not business workflow — it was correctly out of scope then). `apply.php` called `notifyRole(['hr_manager','super_admin'], 'New leave application requires approval.', 'leave', $url)` — an array where a string role is required, with the remaining arguments shifted out of the function's actual `(role, type, title, message, link)` order. **Live-verified**: every leave submission threw an uncaught `TypeError` — *after* the application record and balance reservation had already committed successfully, so the underlying data was fine, but the user got a raw fatal-error page instead of a success confirmation, every single time.

**Fix:** loop over each role and call `notifyRole()` correctly, once per role, with properly ordered and semantically correct arguments. (A related slip was caught in the same pass: `notifications.type` is a 4-value ENUM — `info`/`success`/`warning`/`danger` — not a free-text category; an invalid value is silently stored as an empty string rather than erroring. Standardized this and the three "awaiting approval" notifications added in Workflow Group 1 — termination, transfer, promotion — to `'warning'`, correcting the same mistake in my own prior work before it went live.)

**Live-verified after fix**: leave submission now returns a clean redirect; `hr_manager` and `super_admin` both receive a correctly-populated notification.

**Finding ID:** KOM-007 (pre-existing, now closed)

## 4. Finding — The `approval_workflows` Record Never Reflected the Real Decision (FIXED)

`apply.php` creates a real `ApprovalEngine` workflow (`type='leave'`) on submission. But `approve.php` — the page that actually decides the outcome — updates `leave_applications` directly and never calls `ApprovalEngine::act()`. **Live-verified**: approved a leave application through the normal Leave module UI; `leave_applications.status` correctly became `approved`, but the linked `approval_workflows` row stayed `pending` — permanently. The Approvals module would show this leave request as still awaiting action forever, even though HR had already decided it through the page actually used for that purpose.

**Fix:** `approve.php` now also resolves the matching `approval_workflows` row and every one of its `approval_stages` (not just the current stage — this page finalizes the decision outright rather than progressing through the engine's normal per-stage flow, so nothing should be left dangling at `pending`).

**Live-verified**: approving a leave application now correctly flips both stages (`Supervisor Review`, `HR Approval`) and the parent workflow to `approved` in one action.

**Finding ID:** KOM-082 (new, fixed)

## 5. Finding — Leave Approval Is Single-Stage (HR Only) in Practice, Despite Being Modeled as Two-Stage (NOT FIXED — decision needed)

`ApprovalEngine::workflowConfig()['leave']` defines two stages — `Supervisor Review` then `HR Approval` — but `modules/leave/approve.php` never checks or enforces either stage; it's gated purely by the `leave.approve` permission (held by `hr_officer`/`hr_manager`/`super_admin`), and a single approval there resolves the entire request, supervisor step included (now correctly marked "approved" by the sync fix in §4, but never actually reviewed by a supervisor). In practice, this is — and always has been — a single-stage, HR-only approval process; the two-stage data model was aspirational and never wired to an actual supervisor-facing review step.

This mirrors KOM-072 from Workflow Group 1 exactly: a real approval mechanism exists and now behaves *consistently*, but doesn't match the multi-party review the schema (and this charter's own lifecycle diagram: "Request → Supervisor Review → HR Review → Approval") describes. Building an actual supervisor-review step would mean: a supervisor-facing pending-approvals view scoped to their direct reports (`employees.supervisor_id`), a decision on what happens to employees with no `supervisor_id` set, and deciding whether HR can still act without supervisor sign-off (fast-track) or must wait. This is a genuine workflow-behavior decision, not a bug fix — flagged for your direction rather than built unilaterally, consistent with how KOM-072 was handled.

**Finding ID:** KOM-083 (new). Flagged for your decision: leave as single-stage HR approval (documented, accepted), or build the supervisor-review stage.

## 6. No Findings — Duplicate Deductions, Overlap Detection, Working-Day Calculation

- **No duplicate deductions**: `approve.php` only acts on applications with `status='pending'` (checked before rendering the form and re-checked implicitly by the `WHERE` on read) — a second approve/reject attempt on an already-decided application is rejected before any balance mutation. Confirmed by code review; not separately live-race-tested (a genuine concurrent double-submit is a narrow timing window, same class of issue as the already-logged KOM-030 payroll race condition — not re-litigated here).
- **Overlap detection**: `apply.php` correctly rejects a new application whose date range overlaps any of the employee's own non-rejected/non-cancelled applications.
- **Working-day calculation**: excludes weekends correctly in both the PHP submission handler and the client-side JS preview; live-verified against a 3-weekday range (Mon–Wed) producing exactly 3 days.

## 7. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`apply.php`, `approve.php`, `status.php`, `edit.php`) | 0 errors |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group) | 5/5 scenarios: reject-restores-balance, notifyRole crash fix, workflow-sync on approve (single and multi-stage), notification-type ENUM correction (including retroactive fix to Group 1's own new code) |

## 8. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-081 — Rejected leave never restored `remaining_days` | Critical | **Fixed** |
| KOM-007 — `notifyRole()` crashed every leave submission (pre-existing, Phase 0) | Critical | **Fixed** |
| KOM-082 — `approval_workflows` never reflected the real leave decision | High | **Fixed** |
| KOM-083 — Leave approval is single-stage HR-only, not two-stage as modeled | Medium | **Documented — decision needed** |

**3 of 4 findings fixed and live-verified. 1 (KOM-083) is a workflow-design decision flagged for your confirmation**, per the same reasoning applied to KOM-072/073 in Workflow Group 1.
