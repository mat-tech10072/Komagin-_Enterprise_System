# Komagin HR — Phase 5 Stage 5.2: ApprovalEngine Dormant Workflow Types

**Document type:** Phase 5 Deliverable — Stage 5.2 Report
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13
**Decision:** Remove `overtime`, `correction`, `payroll_run`, and `document` as dead configuration (user sign-off, 2026-07-13 — see `01-phase5-open-findings-scope.md` §6).

---

## 1. What Was Found

The Phase 4 Approval Flow Report (`Workflows/00-approval-flow-report.md`) documented that `ApprovalEngine::workflowConfig()` defines 8 workflow types, but only 3 are ever actually created through the engine's real authorization path (`promotion`, `transfer`, `termination`), and a 4th (`leave`) is created but its decision bypasses `act()` entirely (resolved instead, see Stage 5.1). The remaining 4 — `overtime`, `correction`, `payroll_run`, `document` — are pure dead configuration: nothing anywhere in the codebase ever calls `->create()` for any of them, confirmed by a full-repository search before this stage began.

Each of the 4 already has its own independently-built, working approval mechanism:

| Dormant type | Real mechanism actually used |
|---|---|
| `overtime` | `modules/timesheets/overtime.php` — its own status-guard logic (Phase 4, KOM-084 added the duplicate-action guard) |
| `correction` | `modules/timesheets/corrections.php` — its own independent status handling |
| `payroll_run` | `modules/payroll/run_{save,finalize,publish}.php` — atomic compare-and-swap status transitions (Phase 4, KOM-030) |
| `document` | `modules/documents/view_generated.php` — its own `generated_documents.status` field, gated by `documents.verify` |

## 2. The Fix

Removed all 4 from `ApprovalEngine::workflowConfig()` — `leave`, `promotion`, `transfer`, `termination` remain, matching exactly the 4 types the engine actually creates workflows for today. Migrating the 3 already-working mechanisms (overtime, corrections, payroll) onto `ApprovalEngine::act()` was explicitly rejected as the higher-risk option — it would mean rewriting 3 features that currently work correctly, for no functional gain.

The `approval_workflows.workflow_type` database ENUM still technically permits these 4 values — this is a separate, lower-risk schema concern not touched this stage (removing an ENUM value is a schema change requiring its own migration/rollback plan per the charter's own rule; nothing in the application can create a row with these values regardless of whether the ENUM still allows them, so there is no functional gap left by not touching the schema).

**Also fixed alongside** (KOM-047, mapped to this stage in the open-findings scope): `ApprovalEngine::cancel()` used `+` inside a raw SQL string (`' | Cancelled: '+ ?`), which MySQL interprets as numeric addition rather than concatenation — both the literal label and the reason parameter were silently coerced to `0` instead of being appended to the notes field. Fixed to a proper multi-argument `CONCAT()`. `cancel()` remains dead code (no call site anywhere), but was fixed rather than removed since it's a generically useful method for any of the 3 real workflow types, independent of this stage's dormant-type cleanup.

## 3. Live Verification

- **Approvals UI type filter**: before the fix, listed 8 workflow types; after, lists exactly 4 (`Leave Application`, `Promotion Request`, `Transfer Request`, `Termination Request`) — confirmed via direct HTML inspection of the rendered `<select>` dropdown.
- **Page load**: `modules/approvals/index.php` returns 200 with no fatal error after the config change.
- **Full round-trip test on a real, still-configured type**: created a disposable test employee, submitted a termination request, approved it via the generic Approvals inbox — workflow correctly created with `total_stages=1`, approval correctly flipped the workflow to `approved`, the employee to `terminated`, and the initiating admin received a correctly-typed `success` notification (confirming Phase 4's KOM-095 notification fix and this stage's config change work together correctly).
- **Zero existing data affected**: confirmed 0 `approval_workflows` rows of any of the 4 removed types existed before this change (nothing had ever created one) — no orphaned data, no migration needed.

All test data (employee, workflow, stage, notification, audit entries) removed after verification.

## 4. Regression

| Suite | Result |
|---|---|
| PHP syntax check (`ApprovalEngine.php`) | 0 errors |
| Phase 1 regression | 20/20 |
| Phase 2 regression | 29/29 |

## 5. Register Update

New finding **not** required — this is a resolution of the gap the Phase 4 Approval Flow Report already documented, not a newly-discovered defect. KOM-047 closed: `cancel()`'s concatenation bug fixed. `ApprovalEngine::workflowConfig()` now accurately reflects the 4 workflow types actually in use, closing the underlying "schema/config models more than the application does" gap for this specific engine (the broader pattern — several other entities across the app share this shape — remains documented in the Phase 4 Workflow Transition Matrix as a cross-cutting observation, not something this single stage resolves everywhere).
