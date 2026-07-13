# Komagin HR — Phase 6: Implementation Plan

**Document type:** Phase 6 Deliverable — Implementation Plan (charter §21.5)
**Status:** Complete — proceeding to execution.
**Date compiled:** 2026-07-13

---

## Scope Decision (User-Confirmed)

No DigitalOcean Droplet exists yet. Phase 6 **certifies the codebase and produces deployment-ready documentation**, executing everything genuinely testable in this local environment (config hardening, backup/restore drills, security re-scan, scaled load testing via Apache Bench, migration/syntax verification) and writing every droplet-specific procedure as a precise, ready-to-execute guide for when the droplet is provisioned. **Nothing is deployed to any live server this phase.** Stack: Ubuntu LTS + **Nginx + PHP-FPM** + MariaDB/MySQL + Let's Encrypt (user-confirmed). Load testing uses locally-meaningful concurrency tiers (not the charter's literal 1000-user figure, which would only measure this laptop's ceiling) with results explicitly labeled as dev-machine capacity, not a production SLA.

**Platform correction from the charter's original framing**: the charter's §6 assumes cPanel/Namecheap shared hosting (AutoSSL, MultiPHP Manager, Cron Jobs UI). A DigitalOcean Droplet is a self-managed VPS with root/SSH access instead — every deployment/security item below is written for that model. Critically, this also means the app's 10 existing `.htaccess` files (blocking PHP execution in `uploads/`, denying access to `config/`/`database/`/`cron/`/`logs/`, and setting security headers) **do nothing at all under Nginx**, which doesn't read `.htaccess` by default — every one of those protections must be re-expressed as Nginx `server`/`location` directives, not just carried over. This is the single most consequential change the platform decision introduces and is treated as a genuine production blocker (Stage 6.1/6.2), not a documentation nicety.

## Execution Stages

| # | Stage | Maps to charter § | Output |
|---|---|---|---|
| 6.1 | Fix genuine production blockers found in the baseline audit | §1, §5 | `APP_URL`/DB credentials made environment-driven; `logs/.htaccess` BOM fixed (still worth fixing even though `.htaccess` won't be read under Nginx — Apache remains this dev environment's own server); `install.php` migration sequence completed (phase11/13); `database/README.md` written; CSP/HSTS/HTTPS-redirect headers added (closes KOM-054) |
| 6.2 | DigitalOcean Droplet deployment guide (Nginx + PHP-FPM) | §6 | `docs/remediation/Deployment/phase6-digitalocean-deployment-guide.md` — exact, prescriptive steps: droplet sizing, Ubuntu/Nginx/PHP-FPM/MariaDB install, **every `.htaccess` rule translated into an Nginx server block** (this is the load-bearing security-parity item), Let's Encrypt/Certbot, systemd cron entry with output suppression, `ufw` firewall, SSH hardening, folder ownership/permissions |
| 6.3 | Database certification | §7 | Fresh-install drill (via `install.php` against a scratch schema), backup/restore drill, rollback drill, orphan/duplicate detection re-run, migration-sequence fix verified — `Database Certification Report` |
| 6.4 | Load & performance testing | §8 | Apache Bench runs at locally-meaningful concurrency tiers against key endpoints (login, dashboard, attendance, payroll, document generation, report export); bottleneck analysis; fix only where evidence shows a real bottleneck |
| 6.5 | Security certification | §9 | Re-run of the full vulnerability checklist (XSS/SQLi/CSRF/IDOR/session/upload/etc.) plus the header/cookie/HTTPS items specific to production readiness; confirms no secrets committed |
| 6.6 | Backup & disaster recovery | §10 | Documented backup procedure (DB + files), RPO/RTO targets, restore-tested procedure, `Disaster Recovery Guide` |
| 6.7 | Operational documentation | §11 | Administrator guide covering installation, upgrade, troubleshooting, user/permission/payroll/leave/attendance/document/branding operations, incident response, maintenance |
| 6.8 | Logging & monitoring verification | §12 | Confirms every log surface (audit/error/mail/cron/auth) has no sensitive-data leakage, documents rotation/retention |
| 6.9 | Release Candidate checklist | §13 | Every checklist item verified against current, post-6.1–6.8 state |
| 6.10 | Final regression | §14 | Full Phase 1–5 re-run + repo syntax scan + migration verification + backup/restore verification + deployment-simulation dry run + security re-test + browser smoke test |
| 6.11 | Release documents | §15, §19 | The full set of certification/checklist/report deliverables, consolidated (several are natural byproducts of 6.1–6.10 rather than separate new work) |
| 6.12 | Final completion report | §16, §20 | `docs/remediation/Phase6/00-phase6-completion-report.md`, ending with the exact required sign-off text |

Each stage follows this program's established discipline: implement only the approved scope, live-verify with disposable/reversible actions where applicable, re-run Phase 1+2 (and, from 6.10 onward, Phase 5) regression, update the Master Remediation Register and Change Control Log, then move to the next stage.

Proceeding to Stage 6.1.
