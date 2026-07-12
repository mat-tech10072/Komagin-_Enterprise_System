# Komagin HR — Phase 4 Deliverable: Workflow Transition Matrix

**Document type:** Phase 4 Deliverable — Workflow Transition Matrix
**Status:** Compiled at the close of Phase 4. Every ENUM value below was extracted directly from the live `database/schema.sql`, not reconstructed from memory; every transition marked "Enforced" was confirmed by reading the actual code path (not assumed from the schema alone) during the corresponding workflow group's review.
**Date compiled:** 2026-07-13

---

## Purpose

For every stateful entity touched during Phase 4, this matrix records: the full set of states the schema allows, which transitions the application code actually performs, and whether each transition is enforced (validated server-side) or merely possible (the database would accept it, but nothing stops an invalid jump via a crafted request). This complements the Business Workflow Inventory (`00-business-workflow-inventory.md`).

---

## 1. Employees (`employees.status`)

States: `active`, `probation`, `suspended`, `on_leave`, `resigned`, `terminated`, `deceased`, `archived`

| From → To | Enforced? | Notes |
|---|---|---|
| `probation` → `active` | Enforced (Workflow Group 1, KOM-073's status-transition matrix) | |
| `active`/`probation` → `resigned`/`terminated`/`deceased` | Enforced, approval-gated for `terminated` | Termination routes through `ApprovalEngine`; resignation/deceased do not (a policy asymmetry, not investigated further this phase). |
| Exit statuses (`resigned`/`terminated`/`deceased`/`archived`) → `active`/`probation` (reactivation) | Enforced | Also re-enables the linked `users` account (KOM-074 fix, Workflow Group 1). |
| Any → `suspended`/`on_leave` | Possible via `status.php`, no dedicated workflow found | Not deeply investigated this phase. |
| `super_admin` override | Enforced | Can bypass the transition matrix entirely (documented, intentional escape hatch). |

## 2. Leave Applications (`leave_applications.status`)

States: `pending`, `approved`, `rejected`, `cancelled`

| From → To | Enforced? | Notes |
|---|---|---|
| (create) → `pending` | Enforced | Reserves `remaining_days`/`pending_days` on the employee's balance immediately. |
| `pending` → `approved` | Enforced | Releases `pending_days`; balance stays reserved. |
| `pending` → `rejected` | Enforced (fixed, KOM-081) | Releases `pending_days` AND restores `remaining_days` — the restoration half was missing before Phase 4. |
| `pending` → `cancelled` | Schema allows it; no code path found creating this transition | Dead state in practice. |
| Modeled 2-stage (Supervisor → HR) | **Not enforced** (KOM-083, deferred) | A single `leave.approve` holder resolves the entire request regardless of the schema's 2-stage design. |

## 3. Approval Workflows (`approval_workflows.status` / `approval_stages.status`)

Workflow states: `pending`, `in_review`, `approved`, `rejected`, `cancelled`, `withdrawn`
Stage states: `pending`, `approved`, `rejected`, `skipped`

| From → To | Enforced? | Notes |
|---|---|---|
| (create) → `pending` | Enforced | `ApprovalEngine::create()`. |
| `pending`/`in_review` → `in_review` (multi-stage advance) | Enforced | `current_stage` increments; only reachable in practice for `termination`/`transfer`/`promotion` (single-stage each in current config, so this path is currently unexercised). |
| `pending`/`in_review` → `approved` (final stage) | Enforced | Separation-of-duties check: initiator cannot approve their own request, correct approver role/assignee required. Applies real change via `updateReference()`. Now also notifies the initiator (KOM-095, Workflow Group 11). |
| `pending`/`in_review` → `rejected` | Enforced | Same checks; `updateReference()` called with `'rejected'` (no-op for most reference tables, by design). Now also notifies the initiator. |
| `cancelled`/`withdrawn` | Schema allows it; `ApprovalEngine::cancel()` exists but is dead code (KOM-047, not fixed) | Nothing in the UI calls it. |
| `leave` workflow type | **Bypassed entirely** | `leave/apply.php` creates the row via `ApprovalEngine::create()`, but `leave/approve.php` decides directly against `leave_applications` and manually syncs `approval_workflows`/`approval_stages` (fixed to actually do this in Workflow Group 3, KOM-082) rather than calling `act()`. |
| `payroll_run`, `document`, `overtime`, `correction` workflow types | **Never created** | Configured in `ApprovalEngine::workflowConfig()` but zero `->create('payroll_run', ...)`-style calls exist anywhere. Dead configuration. |

## 4. Payroll Runs (`payroll_runs.status`)

States: `draft`, `processing`, `finalized`, `published`

| From → To | Enforced? | Notes |
|---|---|---|
| `draft` → `finalized` | Enforced, atomic | `UPDATE ... WHERE status='draft'` + `rowCount()` check — closed a real concurrent-request race condition this phase (KOM-030, Workflow Group 5, tested with 5 simultaneous requests). |
| `finalized` → `published` | Enforced, atomic | Same compare-and-swap pattern; also marks payslips `sent` and optionally emails them. |
| `processing` | Schema value; no code path found setting it | Appears unused in practice. |
| Reverse transitions (`published` → `finalized`, etc.) | Not implemented | One-way pipeline by design. |

## 5. Recruitment Applications (`recruitment_applications.status`)

States: `submitted`, `reviewing`, `shortlisted`, `interview_scheduled`, `interviewed`, `selected`, `rejected`, `withdrawn`

| From → To | Enforced? | Notes |
|---|---|---|
| (create) → `submitted` | Enforced (built this phase, KOM-087) | Previously no entry point existed at all. Duplicate-application guard: same email + same vacancy blocked; same email + different vacancy allowed. |
| `submitted` → any pipeline stage → `selected`/`rejected`/`withdrawn` | Enforced (pre-existing, `application_update.php`) | Free-form stage transitions, no strict linear sequence enforced. |
| `selected` → (new Employee record) | **Not implemented** (KOM-088, deferred) | `converted_to_employee_id` exists in schema, never read or written; HR must manually re-enter the candidate's details with zero system linkage back to the application. |

## 6. Generated Documents (`generated_documents.status`)

States: `draft`, `pending_approval`, `approved`, `rejected`, `issued`

| From → To | Enforced? | Notes |
|---|---|---|
| (generate) → `draft` or `pending_approval` | Enforced | Depends on the source template's `requires_approval` flag. |
| `pending_approval` → `approved`/`rejected` | Enforced | Requires `documents.verify`, checked at the point of mutation (not just page-level). |
| `approved`/`draft` → `issued` | Enforced | Requires `documents.upload`/`create`. |
| Record-level visibility | Enforced (KOM-021, Phase 1) | Draft/pending documents visible only to their generator or a verifier; approved/issued visible to any `documents.view` holder. |

## 7. Temp Employees (`temp_employees.status`)

States: `active`, `completed`, `terminated`

| From → To | Enforced? | Notes |
|---|---|---|
| Any → any | Enforced only as "is a valid ENUM value," no transition-sequencing logic | Simpler than the main `employees` state machine — no approval gating, no reactivation-triggers-account logic (temp employees don't have the same linked-`users`-account concept). |

## 8. Consultants (`consultants.status`)

States: `active`, `completed`, `terminated`

| From → To | Enforced? | Notes |
|---|---|---|
| Any → any | Same pattern as Temp Employees — simple CRUD, no workflow engine involvement | Not deeply re-investigated this phase beyond the deletion-safety fix (KOM-089). |

## 9. Attendance (`attendance.status`)

States: `present`, `absent`, `late`, `on_leave`, `half_day`, `holiday`

| From → To | Enforced? | Notes |
|---|---|---|
| (kiosk sign-in) → `present`/`late` | Enforced | Set based on `isLate()` calculation at sign-in time. |
| → `absent` | **Never set by any code path** (KOM-098, Workflow Group 13) | A row is only ever created on an actual sign-in — there is no proactive process that creates an `absent` row (or flips `is_absent`) for an employee who simply never clocks in. This is the root cause of KOM-098: the schema models absence as a first-class state, but nothing in the application ever produces it. |
| → `on_leave`/`half_day`/`holiday` | Schema values exist; no code path found setting them | Dead states in practice, same pattern as `absent`. |

---

## Cross-Cutting Pattern

Across all nine entities above, the same shape recurs repeatedly: **the schema models more states and transitions than the application code actually drives.** In every case investigated this phase, this was either (a) a genuine bug where the missing transition silently corrupted data (leave rejection not restoring balance, approval decisions never notifying anyone) — fixed — or (b) a genuine unbuilt feature where the schema anticipated something the application layer never got built for (payroll_run/document/overtime/correction workflow types, recruitment-to-employee conversion, attendance's absent/on_leave/half_day/holiday states) — documented and, where a decision was needed, explicitly deferred by the user rather than silently left as-is.
