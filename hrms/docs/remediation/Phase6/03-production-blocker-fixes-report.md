# Komagin HR — Phase 6 Stage 6.1: Production Blocker Fixes

**Document type:** Phase 6 Deliverable — Stage 6.1 Report
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13

---

## 1. Scope

Per charter §1 ("only fix genuine production blockers discovered during certification") and the concrete gaps identified in `Phase6/01-production-readiness-baseline.md`. Closes **KOM-054**, open since Phase 0, never actually closed, and independently re-discovered by this phase's own baseline audit — plus one register-tracking correction disclosed in full (§5).

## 2. What Was Fixed

- **Environment-driven configuration** (`config/config.php`): `APP_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `SESSION_LIFETIME`, `UPLOAD_PATH`, `MAX_FILE_SIZE`, `EMP_PREFIX`, and the timezone are now read via `getenv()` with a fallback to this app's existing local-dev values — the mechanism `.env.example` already documented ("set these as server environment variables... read via `getenv()`") but that was never actually wired up beyond `APP_ENV` itself. Every fallback exactly reproduces current local behavior, so no environment variable needs to be set anywhere for this dev environment to keep working unchanged.
- **`logs/.htaccess` BOM fix**: the file had a UTF-8 byte-order mark before its `Deny from all` directive — confirmed via hex dump — which can cause some Apache/`mod_access_compat` combinations to silently skip the rule, making `logs/php_errors.log` web-accessible. Rewritten clean, and hardened to work under both Apache 2.2 (`mod_access_compat`) and native Apache 2.4 (`Require all denied`) syntax via `<IfModule>` branching.
- **`config/.htaccess`, `database/.htaccess`**: given the same dual-syntax hardening as `logs/.htaccess`, for consistency (the third file using this pattern, `cron/.htaccess`, and the upload-folder `.htaccess` files, already used `<FilesMatch>`-wrapped rules with a different risk profile and were left as-is — the real, comprehensive fix for the target Nginx deployment is translating every rule into Nginx directives, done in Stage 6.2, not further polishing Apache-specific syntax that becomes inert on the actual production platform).
- **`database/install.php`'s fresh-install sequence**: added `phase13_workflow_completeness_automation.sql` — the one migration file carrying seed data a fresh install genuinely needs (the default `work_calendar_settings` row every "Absent Today" calculation across Dashboard/Reports/Attendance depends on) that `schema.sql` intentionally excludes. `phase11_schema_reconciliation.sql` and `phase12_workflow_integrity_fixes.sql` were investigated and correctly **not** added — both explicitly self-document as upgrade-path-only for pre-Phase-3/Phase-4 databases, and direct inspection confirmed everything they would otherwise add (11 tables, `employees.personal_email`, the `doc_categories` seed rows) is already present natively in `schema.sql` or `seeds/002_doc_categories.sql`. `database/verify_clean_install.php` updated to match (it carries its own separate, duplicate sequence list rather than reusing `install.php`'s constant).
- **`database/README.md`**: written — previously referenced by `install.php`'s own docblock ("see `database/README.md` for what each step does and why the order matters") but did not exist anywhere in the repository.
- **HSTS + CSP headers** (`.htaccess`): `Strict-Transport-Security` added unconditionally (browsers only ever honor it over an already-HTTPS connection, so it's inert and harmless on this plain-HTTP local dev environment and becomes meaningful automatically once HTTPS is live in production). `Content-Security-Policy-Report-Only` added — deliberately Report-Only, not enforcing: this codebase makes extensive, pre-existing use of inline `<style>` blocks, inline `style=""` attributes, inline `<script>` blocks, and inline `onclick=""` handlers throughout every module, none of which is new to Phase 6, and making the policy strict-compliant (nonces, moving every inline handler to external files) would be a UI/frontend rewrite this phase's charter explicitly prohibits. `'unsafe-inline'` is included in `script-src`/`style-src` so the policy doesn't silently break the existing UI, while still restricting which origins scripts/styles/fonts/images/connections may load from at all — the policy explicitly allowlists exactly the 4 external domains a full-codebase grep confirmed the app actually uses (`cdn.jsdelivr.net`, `unpkg.com`, `fonts.googleapis.com`, `fonts.gstatic.com`) and denies everything else. Report-Only is the standard, safe way to introduce CSP to an app not built CSP-first — it logs violations to the browser console without blocking anything. Switching to the enforcing header is documented as a deployment-time step in the Stage 6.2 guide, to be done only after confirming zero console violations against real traffic on the actual production domain.
- **Forced HTTPS redirect — deliberately NOT added at the application/`.htaccess` layer.** The target deployment is a DigitalOcean Droplet running Nginx, which doesn't read `.htaccess` at all. Adding a PHP-level redirect conditioned on `APP_ENV==='production'` was considered and rejected: this local dev environment is *currently* resolving `APP_ENV=production` itself (a side effect of Phase 5's KOM-053 fail-safe-default fix, combined with an earlier failed attempt to set a local override in Apache's config that never actually took effect), and this dev box has no HTTPS listener at all — an app-level redirect would have broken every page load in this environment immediately. The correct, safe place for this is the Nginx server block itself (`listen 80; return 301 https://$host$request_uri;`), covered in the Stage 6.2 deployment guide.

## 3. Register Correction Disclosed

**KOM-054 was found still genuinely Open** during this stage's opening baseline audit, despite Phase 5's Stage 5.11 entry and completion report both claiming 0 Open findings across the register's 99 rows. A full-text search of every Phase 5 stage narrative confirms KOM-054 is never mentioned anywhere in Phase 5's record — it was missed entirely by Phase 5's own opening baseline audit (`Phase5/01-phase5-open-findings-scope.md`), which was explicitly supposed to catalog every open finding fresh rather than trust prior tracking, and evidently still had this one gap.

Disclosed in full rather than silently absorbed, consistent with this program's standing correction practice (the KOM-035 duplicate catch at Phase 5's start; the Stage 5.9 arithmetic-slip correction found and fixed at Stage 5.10). **Corrected Phase 5 closing tally: 90 Fixed (not 91), 1 Open — KOM-054 (not 0).** Every other Phase 5 figure stands as originally recorded. With KOM-054 now fixed this stage, the register returns to 91 Fixed / 0 Open — the same headline numbers Phase 5 originally (incorrectly) reported, now actually true.

## 4. Live Verification

1. **Environment-variable override**: confirmed `config.php` produces byte-identical values to before when no environment variables are set (local dev behavior unchanged), then confirmed setting `APP_URL` as a real environment variable and re-running correctly overrides the constant.
2. **`.htaccess` protections**: confirmed `config/`, `database/`, and `logs/` all still return `403 Forbidden` over HTTP after the syntax hardening.
3. **New headers**: confirmed `Strict-Transport-Security` and `Content-Security-Policy-Report-Only` both appear correctly on real HTTP responses (`curl -I`), with no change to page load status codes.
4. **Fresh-install drill**: ran `database/verify_clean_install.php` against a real, freshly-created scratch database (`komagin_hr_phase3_clean_test`) before and after the `INSTALL_SEQUENCE` fix. Before: 29/30 (all 11 install steps succeeded, but the `work_calendar_settings` default row was confirmed missing per the audit). After: **30/30** — the corrected sequence installs cleanly in one pass, and the previously-stale hardcoded table-count assertion (60, now genuinely 67 after Phase 5's 7 new tables) was corrected in the same pass. Scratch database dropped cleanly after each run.
5. **`logs/.htaccess` BOM**: confirmed via hex dump before (`EF BB BF` present) and after (absent) the fix.

## 5. Regression

- Phase 1 suite: **20/20 passed**.
- Phase 2 suite: **29/29 passed**.
- Phase 5 suite: **40/40 passed**.
- Zero regressions from any of Stage 6.1's changes.

## 6. Register / Change Control

- **Master Remediation Register**: KOM-054 closed. A register-tracking correction for Phase 5's stale "0 Open" claim disclosed in full (§3 above).
- **Change Control Log**: CC-137.
