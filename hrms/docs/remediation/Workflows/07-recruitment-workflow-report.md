# Komagin HR — Phase 4 Workflow Group 7: Recruitment

**Document type:** Phase 4 Deliverable — Workflow Group Report 7 of N
**Status:** Live-verified against test data (a real vacancy, a disposable test application), fully cleaned up afterward.
**Date compiled:** 2026-07-12
**Scope:** Job → Application → Shortlist → Interview → Selection → Offer → Acceptance → Employee conversion. Ensure duplicate applicants cannot become duplicate employees.

---

## 1. Critical Finding — No Way to Create a Recruitment Application Existed Anywhere (FIXED)

Searching the entire codebase for `INSERT INTO recruitment_applications` found exactly one match: `database/mock_content_seed.sql` (demo data). `modules/recruitment/index.php` only ever lists/filters/counts applications, and `modules/recruitment/application_update.php` only ever changes an *existing* application's pipeline stage. **There was no "Add Application" form, button, or handler anywhere in the application.** The recruitment pipeline's very first step — a candidate applying for a posted vacancy — had no working entry point at all; the only applications that have ever existed in this system came from demo seed data.

**Fix:** added the missing entry point, mirroring the existing "Post Vacancy" pattern (`vacancy_save.php`) already in the same module:
- New "Add Application" button and modal in `modules/recruitment/index.php` (vacancy selector restricted to open vacancies, name/email/phone, current position/employer, years of experience, qualifications, cover letter, and an optional CV upload using the existing `ALLOWED_DOC_TYPES`/`uploadFile()` pattern already used elsewhere in the app).
- New `modules/recruitment/application_save.php` handler: validates required fields, confirms the selected vacancy exists, and blocks a duplicate submission (same email applying to the *same* vacancy twice) — deliberately scoped per-vacancy rather than globally, since a candidate legitimately applying to two different open roles is not a duplicate.

**Live-verified**: submitted a new application against a real open vacancy — created correctly with an auto-generated application number, visible immediately in the Applications tab. A second submission with the same email against the *same* vacancy was correctly rejected; the same email against a *different* vacancy correctly succeeded.

**Finding ID:** KOM-087 (new, fixed)

## 2. Finding — No Employee-Conversion Step Exists, Despite the Schema Being Built for It (NOT FIXED — decision needed)

`recruitment_applications.converted_to_employee_id` exists as a column in the schema — clear evidence the system was designed to link a selected candidate to the employee record they become — but **no code anywhere reads or writes it**. The pipeline's `status` ENUM stops at `selected`/`rejected`/`withdrawn`; there is no "Convert to Employee" action. In practice, if HR selects a candidate, they must separately and manually navigate to Add Employee and re-type everything from scratch, with zero connection back to the original application — the exact scenario the charter's "ensure duplicate applicants cannot become duplicate employees" concern is about, since nothing here would catch a re-typed duplicate against the applicant's own prior application (though Workflow Group 1's KOM-076 `national_id` check would still catch a duplicate against an *existing employee*, since applications don't currently capture `national_id` at all — only name/email/phone).

**This was deliberately not built.** Unlike §1 (a same-pattern, low-risk CRUD gap directly analogous to already-working code), an actual conversion feature means: pre-populating the Add Employee form from application data, writing back `converted_to_employee_id` for audit traceability, and deciding how duplicate-detection should work across two systems that currently don't share an identity field. This is a genuine feature build, not a bug fix — flagged for your decision rather than built unilaterally, consistent with how KOM-072/083/085 were handled this phase.

**Finding ID:** KOM-088 (new). Flagged for your decision: leave as a manual, disconnected step (documented, accepted), or build a "Convert to Employee" action that pre-fills Add Employee from the application and records the link.

## 3. No Findings — Vacancy Posting, Pipeline Stage Updates

`vacancy_save.php`'s columns all correspond to real, valid schema columns (no mismatch bug found here, unlike Performance Management's `save.php`). `application_update.php` correctly re-derives the old/new status for the audit log and gates the action behind `recruitment.review:approve`. No duplicate-transition risk beyond the low-severity observation that a candidate could theoretically be moved through pipeline stages out of a "typical" order (e.g., straight from `submitted` to `selected`) — not flagged as a defect, since HR discretion over pipeline stage is a legitimate business judgment call, not a data-integrity concern.

## 4. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`application_save.php`, `index.php`) | 0 errors |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group) | 3/3 scenarios: new application creation, per-vacancy duplicate block, cross-vacancy non-duplicate success |

## 5. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-087 — No way to create a recruitment application existed anywhere | Critical | **Fixed** |
| KOM-088 — No employee-conversion step, despite the schema being built for it | High | **Documented — decision needed** |

**1 of 2 findings fixed and live-verified. 1 is flagged for your explicit decision** — building a real cross-system conversion feature, correctly outside what should be decided unilaterally.
