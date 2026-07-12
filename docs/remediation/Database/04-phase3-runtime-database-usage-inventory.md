# Komagin HR — Phase 3 Runtime Database Usage Inventory

**Document type:** Phase 3 Deliverable — Database/04 of 6 Phase 3 Database documents
**Status:** Live-verified. All figures in this document were obtained by querying the running `komagin_hr` database's `information_schema` directly (Stage 3.0/3.1), not by reading source files.
**Date compiled:** 2026-07-12
**Baseline tag:** `v1.0-enterprise-baseline` → Phase 3 on branch `phase-3-database-schema-integrity`

---

## 1. Purpose

Phase 0's baseline inventory (`Database/03-database-inventory-report.md`) established table lists by reading `.sql` files. This document supersedes that approach for Phase 3's purposes: it records what the **running database actually contains**, queried directly, as the ground truth against which every tracked `.sql` file is measured for the rest of Phase 3.

## 2. Live Table Count

```
SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='komagin_hr';
→ 59
```

The full list (one name per line, alphabetical) is preserved at `docs/remediation/Database/fingerprints/live_table_list.txt`, captured at Stage 3.0 before any Phase 3 change was made.

## 3. Live Column/Type/Constraint Signature

A complete, structure-only fingerprint (`table.column type [NOT NULL] [PK]` — no data, no passwords, no PII) of the pre-Phase-3 live database, restored from the Stage 3.0 backup, is preserved at:

- `docs/remediation/Database/fingerprints/phase3-pre-change-schema-fingerprint.txt` — 846 lines, 59 tables, 54 foreign keys

## 4. Reconciliation Against the Pre-Phase-3 Tracked `schema.sql` (32 tables)

Comparing the live table list against the pre-Phase-3 `database/schema.sql` (32 `CREATE TABLE` statements, confirmed via `git show HEAD:database/schema.sql`):

- **32 tables** were correctly defined in `schema.sql` and matched the live structure exactly.
- **27 tables** existed live but had **no `CREATE TABLE` in `schema.sql`** — their structure only ever reached the database via `migration_v2.sql`, `phase1_permissions.sql`, `phase5_branding_theme.sql`, `phase8_temp_employees.sql`, `phase9_consultants.sql`, or (for 11 of the 27) an **untracked, undocumented manual change with no corresponding file anywhere in the repository at all**.
- **1 table referenced by live code (`settings`, via `modules/activity_log/download.php`) never existed anywhere** — not live, not in any tracked file. This was not a documentation gap; it was a live, currently-broken code path (every CSV export from that page threw an uncaught `PDOException`). See KOM-070 and `Database/09-phase3-data-integrity-report.md`.

This reconciles and confirms Phase 0's KOM-004 finding ("12 tables undefined") with runtime evidence: 11 of the 12 were real undocumented tables, the 12th was a genuine phantom.

## 5. Runtime Query Surface Confirmed Against Live Structure

The following table groups were confirmed, by direct query against the live database, to be both structurally present and non-empty/functional, matching what application code in `modules/*` actually reads and writes:

| Module area | Tables confirmed live | Row count (informational, Stage 3.0 snapshot) |
|---|---|---|
| Core HR | `employees`, `departments`, `positions`, `users` | 13 / — / — / 17 |
| Payroll | `payslips`, `payroll_runs`, `payroll_deductions`, `payslip_items`, `employee_savings` | 26 / — / — / — / — |
| Attendance/Leave | `attendance`, `leave_applications`, `leave_balances`, `leave_types` | 261 / 7 / — / — |
| Documents | `doc_categories`, `doc_templates`, `doc_template_versions`, `generated_documents` | 10 / 47 / — / — |
| Approvals | `approval_workflows`, `approval_stages` | — / — |
| Temp Employees | `temp_projects`, `temp_sites`, `temp_employees` | — / — / 8 |
| Consultants | `consultants`, `consultant_attendance`, `consultant_scopes` | 5 / — / — |
| Kiosk | `kiosk_sessions`, `kiosk_audit` | — / — |
| Branding | `company_letterheads`, `company_signatures`, `company_stamps`, `company_watermarks`, `email_logs` | — / — / — / — / — |
| Permissions | `permissions`, `role_permissions` | 96 / 832 |
| Audit | `audit_logs` | 428 |

`—` indicates the table was confirmed present and structurally correct but its row count was not part of Stage 3.0's specific captured baseline (only the 10 tables load-bearing for Stage 3.9's zero-data-loss check were counted precisely; all others were confirmed present/queryable via the clean-install and upgrade-test smoke tests in `Testing/12-` and `13-phase3-*-test-report.md`).

## 6. Conclusion

The live database was, and remains, functionally complete and internally consistent — every runtime failure discovered this phase (`settings` phantom table, `doc_categories` seed gap) was a **repository-reproducibility** problem, not a live-data problem. This finding shaped Phase 3's entire approach: extract the live structure as ground truth into `schema.sql`, rather than attempting to redesign or "correct" a database that was already working correctly for its users.
