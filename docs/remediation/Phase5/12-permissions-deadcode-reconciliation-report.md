# Komagin HR — Phase 5 Stage 5.11: Permissions, Configuration & Dead-Code Reconciliation

**Document type:** Phase 5 Deliverable — Stage 5.11 Report
**Status:** Complete.
**Date compiled:** 2026-07-13

---

## 1. KOM-045 Re-Confirmation

Per the charter's instruction to re-verify KOM-045's accepted 26-slug list fresh against the post-Stage-5.10 codebase (rather than assume prior tracking remains accurate — the same discipline applied at Phase 5's start, which caught the KOM-035/KOM-092 duplicate).

Re-checked all 26 previously-flagged slugs (8 redundant export slugs, 5 `portal.*` slugs, 13 miscellaneous) against every `.php` file in the application (excluding docs/database seed files). **All 26 remain unwired** — none of Stage 5.1–5.10's substantial code changes accidentally wired up (or orphaned) any of them. In particular, Stage 5.10's KOM-037 fix gates its export action through `audit.view`'s own `can_export` column, not the separate, still-dormant `audit.export` slug; KOM-051's Archive Lock fix reused the existing `archive.generate` permission, not the separate, still-dormant `archive.lock` slug. No new permission slugs were created anywhere in Phase 5 — every Stage 5.1–5.10 fix reused an existing slug.

**KOM-045's Phase 4 status stands unchanged: REVIEWED AND ACCEPTED, no changes.** Re-confirmed rather than re-litigated.

## 2. KOM-064 — `requireRole()` Dead Code

**Original finding:** a second, parallel hardcoded-list authorization primitive (`requireRole()`) defined in `config/functions.php` with zero call sites.

**Re-verification:** `requireRole` no longer appears anywhere in the live application code (`grep` across every `.php` file returns 0 matches in `config/`, `auth/`, or any module — the only remaining references are historical mentions in documentation). It was already removed, most likely during Phase 1's authorization framework build when ad-hoc role checks were replaced by the `requirePermission()`/`canX()` permission system.

**Status: RESOLVED — already removed, no code change needed.** Same treatment as Stage 5.10's KOM-057 (a finding whose underlying defect no longer exists, discovered during this stage's own re-verification pass rather than assumed).

## 3. KOM-065 — `employee_skills` Table

**Original finding text:** "table defined but never used anywhere in code... never seeded or queried."

**Re-verification found this characterization is now stale/inaccurate.** `employee_skills` **is** referenced in two places:
- `modules/employees/view.php` (line 32-33): queries it and renders a full "Skills" tab in the employee profile (tab label, empty-state message, row-rendering loop) — a genuinely built, working *display* feature.
- `modules/employees/delete.php` (line 49): included in the standard cascade-delete impact-preview list alongside every other employee-related table.

**What's actually missing**: no `INSERT`/`UPDATE` path exists anywhere in the codebase for this table — confirmed by a full-codebase search. The "Skills" tab is permanently empty; there is no way, through any UI, for a skill to ever be added. This makes it a genuine, if minor, completeness gap (a built display with no way to populate it) rather than fully dead/unreferenced code as originally characterized.

**Decision: ACCEPTED AS DESIGNED, documented — no code change.** Reasoning:
- Not a security issue, not blocking any workflow — worst case is a permanently-empty tab with a clear "no skills recorded" message, which is honest UI (it doesn't claim data exists that doesn't).
- `employee_qualifications` and `employee_work_history` (both fully built, with real create/edit paths) already cover the substantive need this table appears to have been an earlier, superseded attempt at — the register's own original finding already noted this ("appear to be its functional successors").
- Building a full Skills CRUD (add/edit/delete UI, its own permission wiring, validation) is new feature scope, not a reconciliation-stage fix — matches this program's treatment of comparable genuine-feature-gap findings (KOM-059 in Stage 5.10, deferred for the same class of reason).
- The finding's factual description is corrected in the register (queried/referenced, not fully dead — but no write path) so a future reviewer has an accurate starting point rather than a stale "never used" characterization.

## 4. Regression

No code was changed in this stage (KOM-045 re-confirmed as-is, KOM-064 already resolved, KOM-065 documented without a code change) — Phase 1/Phase 2 regression suites were not re-run, since there is nothing in this stage's changes that could affect their outcome. Both suites remain at their Stage 5.10 result: **20/20** and **29/29**.

## 5. Register / Change Control

- **Master Remediation Register**: KOM-045 re-confirmed (no change to its Phase 4 closure). KOM-064 closed as Resolved. KOM-065 corrected and closed as Accepted as designed.
- **Change Control Log**: CC-133, CC-134.
