# Komagin HR — Consultant Module Report

**Document type:** Phase 2 Deliverable #3 of 11
**Objective addressed:** Objective 1 — Consultant Module Stabilization
**Finding addressed:** KOM-002 (C-02) — the flagship Critical finding of this phase
**Date:** 2026-07-11/12

---

## 1. The Bug

`modules/consultants/{add,edit,delete,scope_save}.php` each called `validateCsrfToken($_POST['csrf_token'] ?? '')`. That function does not exist anywhere in the codebase — the real helper, used correctly by every other module including the sibling `temp_employees` module built in the same development wave, is `verifyCsrfToken()`. Every write to this module — creating a consultant, editing one, deleting one, or saving a scope-item note — threw an uncaught PHP Fatal Error. The module's read paths (`index.php`, `view.php`) worked; nothing that changed data did.

## 2. The Fix

A one-line change per file: `validateCsrfToken(` → `verifyCsrfToken(`. No other logic in these files was touched.

## 3. Full CRUD Regression — Every Operation, Live, Against Real Data

Objective 1 was explicit that this isn't complete until every write operation is confirmed working, not just non-fatal. Each step below was performed as a real HTTP request against the running application, with the database checked directly before and after:

1. **Add** — POST to `add.php` with a new consultant's details. Result: `302` redirect to `view.php?id=5`; confirmed via `SELECT` that a new row existed with `consultant_number = 'KOM-CON-2026-0005'`.
2. **Edit** — POST to `edit.php?id=5` changing the last name. First attempt used a malformed test request (missing `?id=` on the URL, since the field reads from `$_GET['id']` not `$_POST['id']`) which correctly no-opped rather than silently succeeding — this was the test being wrong, not the application; corrected and re-run, confirmed via `SELECT` that `last_name` had actually changed in the database.
3. **Scope Save** — POST to `scope_save.php` with `action=add` for consultant #5 (an `output_based` consultant, the type this feature applies to). Result: confirmed via `SELECT` that a new row existed in `consultant_scopes`.
4. **Delete** — POST to `delete.php` for consultant #5. Result: confirmed via `SELECT` that both the consultant row and its scope item were gone — the scope item's removal via `ON DELETE CASCADE` (defined in `phase9_consultants.sql`) was also exercised and confirmed working as part of this same test.

All four operations completed with **zero fatal errors** and the database state matched expectations at every step. Test data was fully cleaned up after verification.

## 4. Bulk Operations

The charter's Objective 1 also asks to confirm bulk operations "if present." A review of `modules/consultants/` found no bulk-action UI or endpoint (no bulk delete, bulk status change, or similar) — this is confirmed absent, not overlooked.

## 5. Scope Boundary — What This Fix Did Not Touch

This module's *authorization* (which permission action each file checks) was already corrected in Phase 1 — `add.php` now checks `consultants.create`, `edit.php` checks `consultants.edit`, `delete.php` checks `consultants.delete`, matching their actual purpose instead of defaulting to `view`. Phase 2's work here was purely the CSRF function-name fix and its regression proof; no authorization logic in this module was touched again.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial consultant module report with full CRUD regression evidence | Remediation Program — Phase 2 |
