# Komagin HR — Phase 6 Stage 6.5: Security Certification Report

**Document type:** Phase 6 Deliverable — Stage 6.5 (charter §9)
**Status:** Complete, live-verified.
**Date compiled:** 2026-07-13

---

## 1. Approach

This stage re-runs the charter's full vulnerability checklist. Phases 1, 2, and 5 already fixed and live-verified the large majority of these categories (CSRF, XSS, SQLi, session fixation/hijacking, privilege escalation, brute-force lockout, password reset token handling — each with its own Change Control Log entry and Master Register row). Rather than repeating that work from scratch, this stage:

1. **Spot-re-verifies** each already-fixed category against the current, post-Phase-6 codebase to confirm no regression.
2. **Newly tests** the categories most relevant to production readiness specifically — file/directory exposure, header/cookie behavior under real HTTP responses, and secrets-in-repo — since these weren't the focus of any earlier phase.

Two genuine, previously-undiscovered findings came out of category (2). Both are documented in full below and in the Master Remediation Register.

## 2. Finding 1: `.git/` Fully Exposed Over HTTP (Fixed)

Live-tested direct requests to `.git/config`, `.git/HEAD`, `.git/logs/HEAD` — all three returned **HTTP 200** with real repository content, including the full committed history. The root `.htaccess` had no rule blocking dotfiles or dot-directories at all; this app has no `public/`-style document root separating code from the web root, so anything not explicitly denied is servable.

**Fixed**: added a `mod_rewrite`-based rule to the root `.htaccess` that denies any request whose path contains a dot-prefixed segment (`RewriteCond %{REQUEST_URI} (^|/)\.` → `[F]`). A `<FilesMatch "^\.">`-based fallback is included for the (here, inapplicable) case `mod_rewrite` isn't loaded — `<FilesMatch>` alone can't do this job on its own since it only matches the leaf filename, not a path segment like `.git/`, so a request for `.git/config` (leaf name `config`) would slip past it.

**Verified live**: `.git/config`, `.git/HEAD`, `.git/logs/HEAD`, `.htaccess` itself, and `.env.example` now all return 403; `auth/login.php` and static assets (`assets/css/style.css`) are unaffected (200).

## 3. Finding 2 (KOM-100, Critical): `tests/` QA Toolkit Exposed, Containing a Currently-Valid Admin Credential

### 3.1 What was found

This repository carries a pre-existing (pre-remediation-program) Playwright-based QA/audit toolkit at `tests/` — ~300 tracked files: audit scripts, ~300 PNG screenshots of every module rendered under every admin role, and JSON audit reports (`node_modules/` itself is correctly `.gitignore`'d, so the Playwright library code isn't tracked, but everything else is). Because this app has no `public/`-style document root, `tests/` sits inside the web-served directory tree with no access restriction at all.

Live-tested: `tests/full-audit-2026.js` returned **HTTP 200** — fully downloadable. Reading it revealed a hardcoded credential block:

```js
superadmin: 'Admin@123',
hrmanager:  'Admin@123',
hrofficer:  'Admin@123',
payroll:    'Admin@123',
```

To determine real severity (a stale/rotated demo password vs. a live one), a real login was attempted: a POST to `auth/login.php` with `username=superadmin&password=Admin@123` and a freshly-fetched CSRF token returned **HTTP 302 to `dashboard.php`** — a successful login. **This is the actual, currently-valid password on this environment's live `superadmin` account** (and, by the same seed source, `hrmanager`/`hrofficer`/`payroll` too). The test session was logged out and its cookie jar removed immediately after confirming this.

Also directly downloadable, with no protection at all: `tests/audit-2026/audit-report.json`, `tests/inspect-report.json`, and ~300 screenshots covering every module under every role (`superadmin`, `hrmanager`, `hrofficer`, `payroll`) — full UI/data/architecture reconnaissance material for an unauthenticated attacker, even without the credential.

### 3.2 Why this happened — a data anomaly, not a code defect

`'Admin@123'` is not an accidental leak of a real secret — it is the **intentional, documented default seed password** (`database/seeds/001_baseline_admin.sql`, whose own header comment says it's "publicly documented in this project's own README"). The design is sound: `must_change_password` is forced to `1` on this seeded row, and the application enforces a redirect to `auth/change_password.php` before anything else is reachable until that flag is cleared by an actual password change. Stage 6.3's fresh-install drill already confirmed this mechanism works correctly on a truly fresh install.

The problem is specific to **this environment's live data**: querying the live `users` table shows `must_change_password = 0` for all four accounts (`superadmin`, `hrmanager`, `hrofficer`, `payroll`) — meaning the forced-change gate was bypassed or cleared at some point across this program's six phases of testing, without an actual password rotation ever happening. Combined with the file exposure, this meant a real, live, still-default super_admin credential was one HTTP GET away from any visitor.

### 3.3 What was fixed vs. what was deliberately not touched

**Fixed — the exposure vector:**
- Root `.htaccess`'s new dotfile/dot-directory rule (§2) already blocks nothing under `tests/` specifically (it doesn't start with a dot), so a dedicated `tests/.htaccess` was added with an unconditional `Deny from all`, matching the exact pattern already established in Stage 6.1 for `config/`, `database/`, and `logs/`.
- The Stage 6.2 DigitalOcean deployment guide updated in two places: (a) §5 (code deployment) now runs `rm -rf tests/` immediately after `git clone`, since dev/QA tooling should never exist on a production server at all — a web-server deny rule alone is a weaker, belt-only control; (b) §7's Nginx `location` deny list extended to include `tests/` as defense-in-depth for the case that removal step is ever skipped.

**Not fixed — the live credential itself.** This is an active login credential the user uses to administer their own system today. Rotating it unilaterally, without warning, could lock the user out mid-session or disrupt work in progress — the kind of consequential, hard-to-reverse-from-the-user's-perspective action this program's operating discipline requires flagging rather than silently doing. **Recommendation, surfaced here and directly to the user:** rotate the `superadmin`/`hrmanager`/`hrofficer`/`payroll` passwords on this environment at your convenience, and before any real data is ever loaded into a database copied or migrated from this one. The deployment guide's existing §11 checklist already correctly requires a fresh production install to go through the forced first-login change (line: "`superadmin`/`Admin@123` password changed") — that mechanism is sound; it's this long-lived local testing database specifically whose flag was cleared out-of-band.

### 3.4 Verification

- Exposure fix: `tests/full-audit-2026.js`, `tests/audit-2026/audit-report.json`, `tests/screenshots/01_dashboard.png` all now return 403; `auth/login.php` and static assets unaffected (200).
- Credential validity: confirmed via a real, disposable login POST (session logged out and cookie jar deleted immediately after).
- Not re-verified after rotation, since no rotation was performed this phase (see §3.3).

## 4. Re-Verification of Already-Fixed Categories (No Regressions Found)

| Category | Phase originally fixed | Spot-check performed this stage | Result |
|---|---|---|---|
| CSRF (all 4 auth surfaces + APIs) | 1, 2 | Confirmed `config/config.php`'s `getenv()` changes and Stage 6.1's header additions didn't touch any CSRF code path; live login flow (§3.1's test) required a valid CSRF token to succeed | No regression |
| Session fixation / ID rotation | 2 | `auth/session_common.php` unchanged this phase; live login (§3.1) confirmed session ID present and cookie correctly issued | No regression |
| Cookie flags (`HttpOnly`, `Secure`, `SameSite=Strict`) | 2 | Live `Set-Cookie` header inspected on `auth/login.php`: `HttpOnly; secure; SameSite=Strict` all present | No regression. See §5 for a related HTTPS-sequencing note. |
| Security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, HSTS, CSP-Report-Only) | 6, Stage 6.1 | Live-fetched headers on `auth/login.php` — all 5 present with the expected values | Confirmed live, first time verified over real HTTP in this stage |
| Directory protections (`config/`, `database/`, `logs/`, `uploads/` PHP execution) | 1, 6.1 | Live-tested: `config/config.php`, `database/schema.sql`, `logs/php_errors.log`, `database/backups/*.sql`, `uploads/test.php` — all 403 | No regression |
| SQLi (PDO prepared statements, `ATTR_EMULATE_PREPARES=false`) | 1 (foundational), ongoing | `config/database.php` unchanged this phase; grepped tracked PHP files for string-concatenated SQL literals — none found | No regression |
| Stored XSS (notification bell, template authoring) | 4 (KOM-093, KOM-022) | No code touched in either file this phase | No regression |
| Password reset token replay / single-use | 5 (Stage 5.5) | No code touched this phase | No regression |
| Secrets committed to git | N/A (new check) | Grepped all tracked PHP files for hardcoded password/secret/API-key literals — none found; searched full git history (`git log --all -p`) for `.env` files ever committed — none found; searched history for secret-like literals — the only hit was the `tests/full-audit-2026.js` documented default password (§3) | 1 finding (KOM-100, documented above) |

## 5. Cookie `Secure` Flag Over Plain HTTP — Confirmed Not a Problem, With a Go-Live Sequencing Note

`auth/session_common.php`'s `bootstrapSession()` sets the cookie's `Secure` flag whenever `APP_ENV === 'production'` OR the connection is HTTPS (§Phase 2, CC-022). This local environment currently resolves `APP_ENV=production` with no HTTPS listener — meaning every session cookie here is sent with `Secure` over plain HTTP.

By the letter of the cookie spec this looks alarming (`Secure` cookies aren't supposed to be stored by a browser over a non-HTTPS connection), but modern browsers (Chrome, Firefox, Edge, Safari) treat `http://localhost` and `http://127.0.0.1` as "potentially trustworthy" origins specifically because they cannot be intercepted by a network attacker — a standardized exception (W3C Secure Contexts spec), not a bug. This is why login has worked correctly in this environment throughout the whole program.

**The sequencing implication for the real droplet**: once this app is deployed to a real domain (not `localhost`), that browser exception no longer applies. If the app is ever reachable over plain HTTP on the real domain — even briefly, e.g. before Certbot has run — no real browser will store the session cookie at all, and login will silently, completely fail (not error, just never "stick"). The Stage 6.2 deployment guide's sequencing already gets this right: Nginx's HTTP listener (§7) immediately 301-redirects to HTTPS, and Certbot (§8) runs before the application is ever exercised (§9, `install.php`) — so this failure mode shouldn't be reachable in practice if the guide is followed in order. Documented here explicitly so a future operator who deviates from that order (e.g. testing over plain HTTP before Certbot) understands why login would appear completely broken rather than assuming a code bug.

## 6. Regression

Full Phase 1 (20/20), Phase 2 (29/29), and Phase 5 (40/40) regression suites re-run after the `.htaccess` changes — all passed, confirming the new dotfile/dot-directory and `tests/` deny rules did not affect any application page, asset, or API endpoint.

## 7. Conclusion

One exposure-class finding (`.git/`) and one critical, genuinely serious finding (KOM-100 — an exposed, currently-valid admin credential via an unrestricted `tests/` directory) were found and the exposure vectors closed, live-verified, with zero regressions. Every other checklist category re-confirmed intact from Phases 1, 2, and 5 with no regressions found. One item — rotating the live default admin credentials on this specific environment — is deliberately left to the account owner rather than done unilaterally, and is called out explicitly both here and directly to the user.
