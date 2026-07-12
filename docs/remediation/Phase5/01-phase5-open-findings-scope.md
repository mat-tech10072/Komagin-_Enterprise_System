# Komagin HR — Phase 5 Deliverable: Open Findings Scope

**Document type:** Phase 5 Deliverable — Open Findings Scope (charter §4)
**Status:** Planning only. No code or schema changes have been made. Per the Phase 5 charter, implementation does not begin until this document and the decision matrix (§5) are reviewed and approved.
**Date compiled:** 2026-07-13
**Baseline:** Phase 4 close, commit `40b564e52feff480a98d33069f2fc305160d789e`, branch `phase-5-workflow-completeness-automation` created from it.

---

## 0. A Correction Found While Compiling This Document

The Master Remediation Register's own running "Open" tally (last stated as **31**) does not match a fresh, full line-by-line read of every one of the 99 rows, cross-checked against each severity section's own header count (Critical 13, High 28, Medium 31, Low 27 — all four confirmed correct). The actual count of findings whose **Current Status** cell is genuinely unresolved is **28**, not 31. Two things account for the drift:

1. **KOM-035** ("Temp Employees audit trail logs the wrong record ID," Medium, still marked `Open`) is the **same defect, same files, same root cause** as **KOM-092** ("`auditLog()` called with the wrong argument order across the entire Temp Employee module"), which was fixed and verified in Phase 4 Workflow Group 10. KOM-092 was logged as a new finding instead of being cross-referenced to KOM-035 and closing it, exactly the mistake KOM-044 avoided when it was correctly superseded by KOM-011 in Phase 1. **KOM-035 is stale and should be closed as superseded by KOM-092**, not carried into Phase 5 as live work.
2. The narrative "Open" figure was carried forward and incremented/decremented informally each workflow-group round rather than recomputed from the full register each time, and accumulated a small drift.

Both are corrected in this document's count below, and the fix (closing KOM-035, correcting the Register Totals narrative) is scoped into Stage 5.11 (§4, below) as a one-line register-hygiene item — no application code is affected.

**Corrected baseline: 27 genuinely open findings** (28 minus KOM-035) once KOM-035 is closed.

---

## 1. Baseline State (Stage 5.0)

| Item | Value |
|---|---|
| Git commit (Phase 4 close) | `40b564e52feff480a98d33069f2fc305160d789e` |
| Working tree | Clean |
| Branch | `phase-5-workflow-completeness-automation` (created from the above commit) |
| Database backup | `database/backups/pre_phase5_backup_20260713_075742.sql` (59 tables, verified complete) |
| PHP version | 8.2.12 (CLI, ZTS) |
| Database version | MariaDB 10.4.32 |
| Register totals | Critical 13, High 28, Medium 31, Low 27 — **Total 99** |
| Open findings (corrected) | **27** (see §0) |
| Fixed | 66 (2 partial: KOM-090, KOM-098) |
| Accepted as designed | 2 (KOM-085, KOM-045) |
| Deferred | 2 (KOM-088, KOM-097) |
| Cron/scheduler infrastructure | **None exists.** Confirmed by repository search — no `cron/` directory, no scheduled-task table, no task-runner script anywhere in the application (the only `scheduled_task*` hit is an unrelated Claude Code tooling artifact under `.claude/`, not application code). |
| Leave approval configuration | `ApprovalEngine::workflowConfig()['leave']` defines 2 stages (Supervisor Review → HR Approval), but `leave/approve.php` bypasses `ApprovalEngine::act()` entirely and resolves the whole request in one HR-only step (KOM-083). |
| ApprovalEngine workflow types | 8 configured (`leave`, `overtime`, `correction`, `payroll_run`, `promotion`, `transfer`, `termination`, `document`). Only 3 (`promotion`, `transfer`, `termination`) are ever actually created via `act()`'s real authorization path; `leave` is created but bypasses `act()`; `overtime`, `correction`, `payroll_run`, `document` are configured but never instantiated anywhere. |
| Document QR usage | 0 of 47 live templates have `show_qr_code` enabled. |
| `notifications.type` | 4-value ENUM: `info`, `success`, `warning`, `danger`. No dedicated "reminder" type. |
| Active permission slugs | 96 total; 26 confirmed unreferenced in any code path (KOM-045, reviewed and accepted, no change). |
| Production migration status | `schema_migrations` table **does not exist on the live database** — `database/phase11_schema_reconciliation.sql` (built and tested against a clone in Phase 3) has still not been applied to production. Unchanged since Phase 3's and Phase 4's completion reports; remains a deployment decision, not a Phase 5 remediation prerequisite (per this phase's own charter §5.0). |

---

## 2. The 27 Open Findings

Every column below is drawn directly from the Master Remediation Register (`Findings/08-master-remediation-register.md`); "Recommended action" and the two "belongs in Phase 5" columns are this document's own assessment, cross-referenced against the charter's Stage list (§3 of this document).

| ID | Sev. | Title | Module | Current behavior | Expected behavior | Recommended action | Product decision needed? | Phase 5 in scope? | Verification method |
|---|---|---|---|---|---|---|---|---|---|
| KOM-009 | High | Leave approve/reject buttons non-functional | Leave | `view.php` POSTs hidden fields; `approve.php` only reads `$_GET` — clicks silently no-op | Approve/Reject buttons on the leave detail page actually change status | Fix `approve.php` to read the POSTed fields, or fix `view.php`'s form to match whichever side is correct | No — straightforward bug | Yes — Stage 5.10 | Live: click Approve from the detail page, confirm status changes and balance updates |
| KOM-016 | High | Published payslips editable with no audit trail | Payroll | Update branch has no status guard (can edit `finalized`/`sent` payslips) and never calls `auditLog()` | Either block edits to finalized/sent payslips, or allow with a full audit trail | Add a status guard consistent with the payroll run's own atomic-transition pattern (Phase 4, KOM-030); add `auditLog()` call | Possibly — "should finalized payslips ever be editable at all" is a policy question | Yes — Stage 5.10 | Live: attempt to edit a finalized payslip, confirm rejection or audit entry |
| KOM-025 | Medium | Employee salary field always blank on edit | Employees | Reads `$emp['salary']`; real column is `basic_salary` | Edit form pre-populates the existing salary | One-line column-name fix, matching the recurring "column doesn't exist" bug class found repeatedly in Phase 4 | No | Yes — Stage 5.10 | Live: open edit form for an employee with a salary set, confirm pre-populated |
| KOM-028 | Medium | Reports Hub "Export CSV" link is dead | Reports | Nothing reads `$_GET['export']` on this page, unlike sibling reports | Export CSV button produces a file | Wire up the same CSV export pattern already used in `employees.php`/`timesheets.php`/`executive.php` | No | Yes — Stage 5.10 | Live: click Export CSV, confirm a file downloads |
| KOM-031 | Medium | SMTP password exposed in page HTML source | Settings | Second "Payslip Notifications" form re-emits `smtp_pass` as a hidden input's cleartext value; primary form already fixed | No cleartext credential anywhere in page source | Apply the same placeholder-masking fix already used on the primary form | No — same fix, second location | Yes — Stage 5.10 | Manual: View Source, confirm no cleartext password anywhere |
| KOM-033 | Medium | Activity Log CSV export doesn't neutralize formula injection | Activity Log | Escapes quotes but not leading `=`,`+`,`-`,`@` in exported free-text fields | Exported CSV is safe to open in Excel/Sheets without formula execution | Prefix a neutralizing character (e.g. leading `'`) to any field starting with a formula-trigger character before writing the CSV row | No | Yes — Stage 5.10 | Security test: export a record containing a formula-injection payload, confirm neutralization |
| KOM-034 | Medium | Activity Log per-user export ignores filters, unbounded load | Activity Log | Per-user export path ignores date-range filters and uses `fetchAll()` instead of the streaming pattern used elsewhere in the same file | Filters respected; memory-safe streaming for large exports | Apply the same filter/streaming pattern already used by the category-export path in the same file | No | Yes — Stage 5.10 | Live: export a long-tenured user's history with a date filter, confirm respected |
| KOM-035 | Medium | *(stale — superseded)* | Temp Employees | Duplicate of KOM-092 (Phase 4, fixed) | — | **Close as Resolved — superseded by KOM-092**, no code change | No | Yes — Stage 5.11 (register hygiene only) | N/A — documentation correction |
| KOM-037 | Medium | Two menu items, two authorization models, one audit table | Navigation | "Audit Logs" (permission-gated) and "Activity Logs" (hardcoded super-admin-only) both read `audit_logs`, sit adjacent in the sidebar | A single, consistently-gated entry point, or a clearly differentiated purpose for each | Needs a decision: merge into one entry point, or document why two legitimately different views over the same table are intentional | **Yes** | Yes — Stage 5.10 | Product decision + code review of the resulting single (or clearly-differentiated) entry point |
| KOM-038 | Medium | SVG accepted for letterhead uploads | Settings / Branding | `image/svg+xml` allowed for letterheads only; SVG can carry embedded script | SVG excluded, or sanitized before storage/serving | Remove `image/svg+xml` from the letterhead-upload allowed-MIME list (letterheads don't need vector format) | No | Yes — Stage 5.10 | Code review: confirm SVG rejected by `uploadFile()`'s allowed-types check |
| KOM-039 | Medium | Uploaded branding file extension trusted from client filename | Settings / Branding | `uploadFile()` saves the client-supplied extension without cross-checking it against the `finfo`-detected MIME type | Extension on disk is derived from the detected MIME type, not the client's claim | Map detected MIME type → canonical extension in `uploadFile()`, ignore the client-supplied one | No | Yes — Stage 5.10 | Security test: upload a polyglot/mismatched-extension file, confirm the saved file's extension matches its real content type |
| KOM-041 | Medium | No self-service password-reset flow on any surface | Authentication (all 4 surfaces) | Only path is an already-authenticated admin manually resetting another user's password; portals show "contact HR" text with no mechanism | A secure self-service reset flow, or an explicit accepted decision that this is by design | **Yes — this is Stage 5.5's entire subject** | **Yes** | Yes — Stage 5.5 (dedicated) | Full flow test per charter §Stage 5.13 "Password reset" test group |
| KOM-083 | Medium | Leave approval is single-stage (HR only) in practice, not two-stage as modeled | Leave / Approvals | `approve.php` never checks or enforces the Supervisor Review stage `ApprovalEngine::workflowConfig()` defines | Either the schema's 2-stage model is actually enforced, or the schema/UI is corrected to reflect single-stage-HR-only as the real, intended design | **Yes — this is Stage 5.1's entire subject.** Already flagged once (Phase 4) and left deferred at user direction; needs a final decision this phase, not another deferral | **Yes** | Yes — Stage 5.1 (dedicated) | Full flow test per charter §Stage 5.13 "Leave" test group |
| KOM-046 | Low | Pagination values interpolated directly into SQL | 6 modules | Int-cast before use (not currently exploitable) but deviates from the prepared-statement standard | All dynamic SQL values passed as bound parameters | Convert the 6 `LIMIT/OFFSET` interpolations to bound parameters (mechanical, low-risk, matches existing pattern elsewhere) | No | Yes — Stage 5.10 | Code review + regression pass on all 6 affected list pages |
| KOM-047 | Low | Approval-cancellation reason silently corrupted | Approvals | `ApprovalEngine::cancel()` uses `+` instead of string concatenation; the method itself is dead code (nothing calls it) | Either fix the concatenation bug, or remove the dead method | Tied to Stage 5.2's ApprovalEngine review — resolve alongside the dormant-workflow-type decision | Partially — depends on whether `cancel()` becomes wired up in Stage 5.2 | Yes — Stage 5.2 | Code review + (if wired up) live test of a cancellation reason round-trip |
| KOM-048 | Low | Missing-documents report runs one query per employee (N+1) | Documents | Loops per-employee instead of a single joined query | Single query, scales flat with headcount | Rewrite as one `LEFT JOIN`/`NOT EXISTS` query, matching the pattern already used in `dashboard.php`'s missing-docs count | No | Yes — Stage 5.10 | Live: confirm identical results, reduced query count |
| KOM-051 | Low | Archive "Lock" control silently no-ops | Archive | Posts a `lock_id` field the handler never reads | Lock control actually locks/unlocks the archive period | Wire up the missing handler branch | Possibly — depends on what "locking" an archive period is supposed to prevent | Yes — Stage 5.10 | Live: toggle lock, confirm the archive period's editability changes accordingly |
| KOM-053 | Low | Error display defaults to on | Config | `APP_ENV` defaults to `'development'` unless explicitly set | Defaults to a safe production posture | Change the default, or document that this must be explicitly set at deploy time | No — this is standard deployment hygiene | Yes — Stage 5.10 (config-only) | Code review: confirm `display_errors` off when `APP_ENV` unset |
| KOM-054 | Low | Standing deployment hardening items unaddressed | Environment | Blank root DB password, default admin password, no HTTPS redirect, no CSP header | Documented deployment hardening checklist, applied before any real production launch | Document as a pre-launch checklist item — these are environment/deployment concerns, not application-code defects, and this phase's charter explicitly excludes production deployment | No | **No — deployment, not remediation** | Documented only |
| KOM-055 | Low | Replaced branding assets never deleted from disk | Settings / Branding | No `unlink()` on update/delete; unbounded storage growth | Old file removed when replaced/deleted | Add `unlink()` calls at the 3 relevant mutation points | No | Yes — Stage 5.10 | Live: replace/delete a branding asset, confirm old file removed from `uploads/` |
| KOM-056 | Low | Server-generated date interpolated directly into SQL | Dashboard | Not exploitable (server value, not user input), but deviates from the prepared-statement standard | Bound parameter, consistent with the rest of the codebase | Mechanical fix | No | Yes — Stage 5.10 | Code review + dashboard regression |
| KOM-057 | Low | Dead query wastes a DB round-trip on temp-employee list | Temp Employees | Vestigial `COUNT` statement's result assigned then immediately overwritten | No wasted query | Remove the dead statement | No | Yes — Stage 5.10 | Code review + confirm identical page output |
| KOM-058 | Low | Temp-employee timesheet is a blank template, not a data-capture tool | Temp Employees | Renders a printable grid only; no persistence | Depends on Stage 5.8's decision for temp employee attendance capture generally | Resolve alongside Stage 5.8, not independently | Yes — folded into Stage 5.8 | Yes — Stage 5.8 | Per Stage 5.8's resolution |
| KOM-059 | Low | Temp employee position modeled as free text, not FK | Temp Employees | Inconsistent with `employees`'s normalized `positions` table | Normalized FK, or explicitly accepted as intentionally simpler for this module | Schema change with migration — needs explicit scoping given "no destructive operation without rollback plan" | Possibly | Yes — Stage 5.10, low priority | Code review + (if changed) migration tested against a clone first |
| KOM-060 | Low | Letterhead header/footer HTML fields saved but never used | Settings / Branding / Documents | `company_letterheads.header_html`/`footer_html` captured on save, never read by `DocumentEngine` | Either read and render them, or remove the unused form fields | Small: read the fields in `wrapDocument()`, or remove the dead UI inputs | Yes — build the feature vs. remove the misleading fields | Yes — Stage 5.10 | Live: set header/footer HTML on a letterhead, generate a document, confirm rendered (or confirm fields removed from the form) |
| KOM-063 | Low | Password minimum length inconsistent across admin-reset paths | Users / Auth | Admin-reset modal allows 6 characters; self-service change requires 8 | Consistent minimum everywhere | Raise the admin-reset modal's minimum to match | No | Yes — Stage 5.10 | Live: attempt a 6–7 character admin-initiated reset, confirm rejected post-fix |
| KOM-064 | Low | `requireRole()` dead code | Config | Defined, zero call sites, a second parallel authorization primitive | Removed, since it duplicates `requirePermission()`'s job and its existence risks future accidental use of the wrong mechanism | Remove the function | No | Yes — Stage 5.11 | Code review: confirm no call sites before removal, full regression after |
| KOM-065 | Low | `employee_skills` table defined but never used | Database | Has a `CREATE TABLE`, never seeded or queried | Removed, or documented as reserved | Given `employee_qualifications`/`employee_work_history` are its apparent functional successors, recommend documenting as superseded rather than a schema change this phase | No | Yes — Stage 5.11 (documentation), schema removal out of scope this phase | Documented only, no schema change |

---

## 3. Mapping to Phase 5 Charter Stages

| Stage | Subject | Findings addressed |
|---|---|---|
| 5.1 | Leave approval model completion | KOM-083 |
| 5.2 | ApprovalEngine dormant workflow types (`overtime`, `correction`, `payroll_run`, `document`) | KOM-047 (tied to `cancel()`'s fate); no open KOM number directly covers the 4 dormant types themselves — this is new investigative work following up the Approval Flow Report's finding that only 3 of 8 types are ever created |
| 5.3 | Working-day & holiday calendar | Completes KOM-098's deferred half (not itself "open" — already Partially Fixed — but this stage is what finishes it) |
| 5.4 | Scheduled task infrastructure | Foundational — enables Stage 5.6; no open KOM number directly, addresses the "no cron/scheduler exists" gap documented informationally in Phase 4 Workflow Groups 4, 11, 13 |
| 5.5 | Self-service password recovery | KOM-041 |
| 5.6 | Deferred notification workflows | Addresses the Training/Recruitment/password-reset notification gaps documented informationally in Phase 4 Workflow Group 11 |
| 5.7 | Recruitment-to-employee conversion | KOM-088 (Deferred status, not "open," but this stage's dedicated subject) |
| 5.8 | Temporary employee attendance capture | KOM-090 (Partially Fixed — UI corrected, capture mechanism this stage's subject), KOM-058 |
| 5.9 | Document QR verification | KOM-097 (Deferred status, this stage's dedicated subject) |
| 5.10 | Remaining open findings | KOM-009, KOM-016, KOM-025, KOM-028, KOM-031, KOM-033, KOM-034, KOM-037, KOM-038, KOM-039, KOM-046, KOM-048, KOM-051, KOM-053, KOM-055, KOM-056, KOM-057, KOM-059, KOM-060, KOM-063 (KOM-054 explicitly excluded — deployment, not remediation) |
| 5.11 | Permissions, configuration & dead-code reconciliation | KOM-035 (close as duplicate), KOM-045 (already accepted — re-confirm), KOM-064, KOM-065 |
| 5.12 | Security & privacy review | Applies to every new feature built in 5.1–5.9, not a specific finding |
| 5.13 | Testing | Regression coverage for everything above |

Every one of the 27 open findings (26 after KOM-035 closes as a duplicate) is accounted for above. Zero open findings require entirely new investigation before Stage assignment.

---

## 4. Summary

- **27 open findings** (26 after the KOM-035 duplicate-closure correction), all mapped to a specific Phase 5 stage.
- **6 findings require an explicit product decision before implementation**: KOM-016 (should finalized payslips ever be editable), KOM-037 (merge or differentiate the two audit-log menu entries), KOM-041 (build self-service password reset or formally accept the gap — Stage 5.5), KOM-060 (build the letterhead header/footer feature or remove the dead fields), KOM-083 (finalize leave's single- vs. two-stage model — Stage 5.1), and — separately from the open-findings list — Stages 5.2/5.7/5.8/5.9 each carry their own already-flagged product decision (ApprovalEngine dormant types, recruitment conversion, temp attendance, QR verification).
- **1 finding is out of this phase's scope by the charter's own terms**: KOM-054 (deployment hardening checklist — explicitly excluded, "do not begin production deployment... in this phase").
- **1 finding is a documentation-only correction**: KOM-035, closes as superseded by KOM-092, no code change.
- No open finding requires reopening or redesigning any Phase 1–4 hardening work.

This document, together with the Decision Matrix below, is the complete open-findings scope the charter requires before implementation begins.

---

## 5. Decision Matrix — Points Requiring Explicit Sign-Off Before Code Changes

Per the charter's decision hierarchy (§5) and its instruction to present concise options before building product-level features, the following points need an explicit decision before Stages 5.1–5.9 implementation begins. These will be presented for decision in the next step, before any code is written.

1. **Leave approval model (Stage 5.1 / KOM-083)** — build the real 2-stage Supervisor→HR flow, or formally lock in single-stage HR-only as the permanent, intended design (removing the misleading 2-stage schema/UI artifacts)?
2. **ApprovalEngine's 4 dormant workflow types (Stage 5.2)** — wire up `overtime`/`correction`/`payroll_run`/`document` to the engine, remove them from configuration as dead, or leave reserved?
3. **Self-service password recovery (Stage 5.5 / KOM-041)** — build it (and for which of the 4 auth surfaces — Admin, Employee Portal, Consultant Portal, Temp Portal — given not all surfaces necessarily have a reliable email address on file), or formally accept the current admin-assisted-only model?
4. **Recruitment-to-employee conversion (Stage 5.7 / KOM-088)** — build the guided-conversion action (Option A, the charter's own recommended default), keep the fully manual process, or is full automation ever acceptable (charter says no)?
5. **Temporary employee attendance capture (Stage 5.8 / KOM-090, KOM-058)** — build supervisor/HR-entered attendance (the charter's recommended safe baseline), or leave the capability absent and further correct the UI to say so plainly?
6. **Document QR verification (Stage 5.9 / KOM-097)** — build a real public verification page, or disable the QR-code option entirely (charter recommends disabling, given 0 of 47 templates currently use it)?
7. **KOM-016** — should a finalized/sent payslip ever be directly editable at all, or should this become a formal correction-request flow instead?
8. **KOM-037** — merge "Audit Logs" and "Activity Logs" into one entry point, or keep both with a documented, real distinction between them?
9. **KOM-060** — build letterhead header/footer HTML rendering, or remove the dead form fields?

**No code or schema changes have been made in producing this document.** Per the charter, the next step is presenting these 9 decision points for explicit sign-off before Stage 5.1 implementation begins.
