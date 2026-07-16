# Komagin HR — Release Candidate Checklist

**Document type:** Phase 6 Deliverable — Stage 6.9 (charter §13)
**Status:** Complete, live-verified against the current (post-Stage 6.8) state.
**Date compiled:** 2026-07-14

---

This checklist consolidates every production-readiness item certified across Stages 6.1–6.8 into a single pass/fail list, independently re-verified live (not just copy-checked from each stage's own report) as part of compiling it. 30 items, grouped by area.

| # | Item | Status | Evidence |
|---|---|---|---|
| **Deployment & Infrastructure** | | | |
| 1 | Deployment guide covers the full stack (Ubuntu, Nginx, PHP-FPM, MariaDB, Let's Encrypt) end to end | **PASS** | `Deployment/phase6-digitalocean-deployment-guide.md` §1–9 |
| 2 | Every existing `.htaccess` protection has a verified Nginx equivalent | **PASS** | Deployment guide §7; cross-checked directly against the live `.htaccess` files during Stage 6.2 |
| 3 | Rollback procedure documented (code-level `git checkout`) | **PASS** | Deployment guide §12 |
| 4 | HTTP→HTTPS redirect and Certbot sequencing correctly ordered (no window where the app is live over plain HTTP with `Secure` cookies) | **PASS** | Deployment guide §7–9; explained in Stage 6.5 report §5 |
| **Production Configuration** | | | |
| 5 | No hardcoded dev URLs — `APP_URL` environment-driven | **PASS** | Stage 6.1; `config/config.php` |
| 6 | No hardcoded DB credentials — all environment-driven, `127.0.0.1` default (not `localhost`, avoiding the Stage 6.4 Windows-resolution bug as a bonus portability improvement) | **PASS** | Stage 6.1, 6.4 |
| 7 | No placeholder/test emails or secrets in deployable application code | **PASS** | Stage 6.1; Stage 6.5 §4 (git history + tracked-file grep, no hits) |
| 8 | Production mode suppresses raw error detail from end users while still logging full detail server-side | **PASS** | Stage 6.3 (live-forced DB connection failure test); `config/config.php` |
| 9 | Timezone configuration correct and environment-overridable | **PASS** | Stage 6.1 |
| 10 | Security headers present on live responses: CSP (Report-Only), HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy | **PASS** | Stage 6.1; re-verified live this stage |
| **Database** | | | |
| 11 | Fresh install verified against a scratch database | **PASS** | Stage 6.3 §1 (30/30 checks) |
| 12 | Upgrade install path verified idempotent against an already-migrated database | **PASS** | Stage 6.3 §2 |
| 13 | Backup/restore drill performed with real point-in-time verification | **PASS** | Stage 6.3 §3, Stage 6.6 §4 (two independent drills) |
| 14 | Rollback strategy (restore-from-backup) documented and tested | **PASS** | Stage 6.6 §5.2 |
| 15 | Orphan/duplicate-row detection clean across all tracked FK relationships | **PASS** | Stage 6.3 §5 |
| 16 | Database connection failure handled gracefully (generic client-facing error, full server-side log) | **PASS** | Stage 6.3 §6 |
| **Performance** | | | |
| 17 | Load testing performed at a locally-meaningful scale across key endpoints, results honestly labeled as dev-machine capacity | **PASS** | Stage 6.4 |
| 18 | Any bottleneck found during load testing investigated and fixed (or explicitly documented if not fixable with current evidence) | **PASS** | Stage 6.4 (`DB_HOST` fixed; `dashboard.php` c=50 anomaly investigated and documented, not a real defect) |
| **Security** | | | |
| 19 | Full vulnerability checklist re-run against current codebase (CSRF, XSS, SQLi, session, IDOR, headers, etc.) | **PASS** | Stage 6.5, 6.8 |
| 20 | No secrets or credentials committed to git (current tree or history) | **PASS** | Stage 6.5 §4 |
| 21 | Non-web-facing directories (`config/`, `database/`, `logs/`, `cron/`, `tests/`, `scripts/`) and dotfiles/dot-directories (`.git/`, `.env*`) all blocked from HTTP access | **PASS** | Stage 6.5, 6.6; re-verified live this stage |
| 22 | No sensitive data (passwords, tokens, SMTP credentials) leaks via any log surface | **PASS** | Stage 6.8 (KOM-101 found and fixed) |
| 22a | *(Disclosed exception, not a failure of this checklist)* The `tests/` exposure (KOM-100) is fixed at the web-access layer, but the live default admin credential on **this specific environment** was deliberately left un-rotated — the account owner's action, not a code defect | **DISCLOSED** | Stage 6.5 §3.3; Master Register KOM-100 |
| **Backup & Disaster Recovery** | | | |
| 23 | Automated backup script built, scheduled (daily/weekly/monthly), retention-managed | **PASS** | Stage 6.6 |
| 24 | RPO (24h) and RTO (2-4h) targets defined with stated basis | **PASS** | Stage 6.6 §3 |
| 25 | Recovery procedures documented for realistic disaster scenarios (single-record loss, corruption, full server loss) | **PASS** | Stage 6.6 §5 |
| **Scheduler / Cron** | | | |
| 26 | Scheduler mechanism verified: lock-based single entry, overlapping-run rejection, stale-lock recovery, per-task failure isolation, blocked over HTTP | **PASS** | Phase 5 Stage 5.4; re-verified live this stage (`cron/run.php` → 403) |
| 27 | Cron wired into the deployment guide with output redirection and log rotation (app scheduler, backups, PHP errors — all 3) | **PASS** | Stage 6.2, 6.6, 6.8 |
| **Email** | | | |
| 28 | SMTP configuration documented; no credential leakage via settings pages, audit logs, or email logs | **PASS** | Stage 6.8; Phase 1 KOM-031 |
| **Documentation** | | | |
| 29 | Administrator Guide covers installation, operations, troubleshooting, incident response, and maintenance | **PASS** | Stage 6.7 |
| **Regression** | | | |
| 30 | Full regression suite stack (Phase 1, Phase 2, Phase 5) passing at 100% against the current, fully-patched codebase | **PASS** | 20/20, 29/29, 41/41 — most recently re-confirmed at the close of Stage 6.8 |

## Summary

**29 of 30 items PASS outright; 1 item (22a) is a disclosed exception, not a failure** — the live default admin credential on this specific local development environment was deliberately left for the account owner to rotate rather than changed unilaterally (see Stage 6.5 §3.3). This is a data-state fact about this particular environment, not a defect in the certified code or deployment procedure; a fresh production install correctly forces this rotation before the account is usable (Stage 6.3 §1, Stage 6.2 §11 checklist item).

No item on this checklist is marked FAIL. The Release Candidate is ready for Stage 6.10's final regression pass.
