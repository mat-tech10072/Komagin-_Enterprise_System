# Komagin HR — Phase 3 Seed Integrity Report

**Document type:** Phase 3 Deliverable
**Status:** Covers every seed-data defect found and fixed during Phase 3, plus verification evidence.
**Date compiled:** 2026-07-12

---

## 1. `hr_officer` / `hrofficer` Role-Name Typo — Fixed at the Source (KOM-023)

**Where:** `database/phase8_temp_employees.sql`, `database/phase9_consultants.sql`
**Problem:** Both files seeded `role_permissions` grants using the string `'hrofficer'` (no underscore). The canonical `users.role` ENUM value everywhere else in the system is `'hr_officer'`. Every real HR Officer account got **zero** `role_permissions` rows for `temp_employees.*`/`consultants.*` — a functional access-denial bug.
**Prior fix (Phase 1):** `database/phase10_authorization_framework.sql` ran `UPDATE role_permissions SET role='hr_officer' WHERE role='hrofficer'` against the live database. This fixed the *running system* but not the *source* — a fresh install using phase8/phase9 as tracked would reintroduce the identical bug on day one.
**Phase 3 fix:** Both seed files corrected directly to `'hr_officer'`.
**Verification:** `database/verify_clean_install.php` confirms 0 rows with `role='hrofficer'` and exactly 4 correct `hr_officer` → `temp_employees.*` grants on a database built only from tracked files (check #5/#6 in the clean-install test, see `Testing/12-phase3-clean-install-test-report.md`).

## 2. `doc_categories` Seed Data — Existed Nowhere (KOM-068, new finding)

**Where:** `database/phase6_templates.sql` (consumer), `database/seeds/002_doc_categories.sql` (new fix)
**Problem:** `phase6_templates.sql` resolves each of its 47 template inserts' category via `SET @cat_x = (SELECT id FROM doc_categories WHERE slug='...')`, and `doc_templates.category_id` is `NOT NULL`. No tracked file anywhere ever created the `doc_categories` rows this depends on — the live database had them from an untracked manual step. A fresh install following only tracked files would have every template insert fail its `NOT NULL` constraint.
**Discovery:** Found during Stage 3.8's first clean-install test attempt — the install sequence failed exactly at `phase6_templates.sql` with a constraint violation, which is precisely the kind of failure a from-empty install test is designed to surface.
**Fix:** New `database/seeds/002_doc_categories.sql`, containing the 10 live categories (`id`, `name`, `slug`, `icon`, `sort_order`, `is_active`), verified **byte-for-byte** against a direct query of the live database before writing, placed in the install sequence immediately before `phase6_templates.sql`.
**Verification:** Clean-install test confirms 10/10 categories seeded and all 47 templates load with valid `category_id` values.

## 3. `temp_employees` Missing Columns (KOM-005)

**Where:** `database/phase8_temp_employees.sql`
**Problem:** `rate_type` and `attendance_method` are written by `modules/temp_employees/{add,edit}.php` on every save and read by `index.php`/`view.php` throughout the UI, but neither column existed in this file's `CREATE TABLE` — only in the live database, via an untracked manual change.
**Fix:** Both columns added to the `CREATE TABLE`, matching the live, already-working definitions exactly (`rate_type ENUM('daily','hourly') NOT NULL DEFAULT 'daily'`, `attendance_method ENUM('kiosk','timesheet','both') NOT NULL DEFAULT 'kiosk'`).
**Verification:** Clean-install test structural check confirms both columns present; a database built from tracked files alone now supports Add/Edit on the very first submission.

## 4. Default Administrator Account — Did Not Exist in Any Tracked File

**Where:** New `database/seeds/001_baseline_admin.sql`
**Problem:** No tracked file created a default `super_admin` account, so a fresh install had no way to log in at all without a manual `INSERT`.
**Fix:** New seed creates a `superadmin` user with `must_change_password=1` forced. The password hash was verified with `password_verify('Admin@123', $hash)` before being committed, matching the documented default credential (`database/install.php`'s own on-screen text).
**Verification:** Clean-install test confirms exactly 1 `super_admin` user and `must_change_password=1`; live HTTP smoke test confirmed login → forced password-change flow works end-to-end against a freshly installed database.

## 5. No Real Employee Data in Any Seed

Confirmed by inspection: `database/seeds/001_baseline_admin.sql` and `002_doc_categories.sql` contain only a synthetic default administrator account and category metadata — no real names, emails, phone numbers, or payroll figures. `phase7_test_data.sql`/`phase8_temp_employees.sql`/`phase9_consultants.sql`'s sample rows (pre-existing, not new this phase) use clearly fictional Papua New Guinean names and are explicitly opt-in/development-only (`phase7_test_data.sql` is never run by `install.php`'s default sequence — see `database/install.php`'s `load_demo` checkbox).

## 6. Summary

| Defect | Finding ID | Fixed This Phase |
|---|---|---|
| `hrofficer` typo in `phase8_temp_employees.sql` | KOM-023 | Yes |
| `hrofficer` typo in `phase9_consultants.sql` | KOM-023 | Yes |
| `doc_categories` seed data missing entirely | KOM-068 | Yes |
| `temp_employees.rate_type`/`attendance_method` missing | KOM-005 | Yes |
| No default admin account seed | KOM-024 (part of) | Yes |
