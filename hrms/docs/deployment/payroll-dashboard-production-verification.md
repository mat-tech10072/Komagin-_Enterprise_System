# Payroll Dashboard — Production Verification Procedure

**Purpose:** disambiguate the July 2026 payroll dashboard incident's remaining open hypotheses (deployment drift vs. production-only data — see `Komagin_HR_Payroll_Dashboard_Aggregation_Audit_2026-07-18.md` §17/§19) and confirm the 2026-07-18 aggregation-hardening change is actually live, using **read-only commands only**.

**Repository root on DigitalOcean:** `/var/www/komagin-enterprise`
**Payroll dashboard file:** `/var/www/komagin-enterprise/hrms/modules/payroll/index.php`
**Live domain:** `https://hrms.komagin.com`

**This document does not connect to production itself.** It is a checklist for a human operator to run manually, later, after this remediation is reviewed and deployed. Every command below is READ-ONLY and SAFE TO RUN against a live system — none of them mutate code, data, or running services. Do not combine `cd` and a following command on the same line unless using an explicit `&&`.

---

## 1. Confirm the deployed Git commit

**READ-ONLY. SAFE TO RUN.**

```bash
cd /var/www/komagin-enterprise
git log -1 --format="%H %ci %s"
git status
git diff origin/main --stat
```

**Expected output:** the first command's hash should match the latest commit on `origin/main` at the time of deployment (this remediation's commit, once created — see the completion report's Deployment Instructions for the exact commit workflow). `git status` should report a clean working tree. `git diff origin/main --stat` should report no differences.

**What failure means:**
- A hash that doesn't match `origin/main`'s latest commit → the droplet has not pulled the latest code. This alone is enough to explain "the fix is on GitHub but the live symptom persists," per the audit's top hypothesis.
- `git status` showing uncommitted changes → someone has edited files directly on the server, bypassing the normal Git-based deployment workflow. Investigate what was changed and why before doing anything else.
- `git diff origin/main --stat` showing differences → the droplet's working tree has diverged from GitHub in some way (partial pull, manual edit, or a merge conflict left unresolved).

---

## 2. Confirm the served payroll files match Git HEAD

**READ-ONLY. SAFE TO RUN.**

```bash
cd /var/www/komagin-enterprise/hrms
md5sum modules/payroll/index.php modules/payroll/reports.php modules/reports/executive.php config/functions.php config/config.php
git show HEAD:hrms/modules/payroll/index.php | md5sum
git show HEAD:hrms/modules/payroll/reports.php | md5sum
git show HEAD:hrms/modules/reports/executive.php | md5sum
git show HEAD:hrms/config/functions.php | md5sum
git show HEAD:hrms/config/config.php | md5sum
```

**Expected output:** each file's on-disk `md5sum` matches the corresponding `git show HEAD:...` hash.

**What failure means:** the file on disk was edited outside of Git (a manual hotfix that was never committed, or a deploy that only partially completed). This would mean production is running code this repository has no record of — treat as a serious finding and do not proceed to further diagnosis until reconciled (either commit the on-server change properly, or overwrite it with a clean `git checkout` after backing it up — do not do either without a separate, deliberate decision; this document is diagnostic only).

---

## 3. Check Apache and PHP runtime (detect what's actually installed — do not assume)

**READ-ONLY. SAFE TO RUN.**

This deployment has previously used Apache — do not assume Nginx or PHP-FPM without checking first.

```bash
apache2ctl -M
php -v
systemctl list-units --type=service | grep -E "apache2|php.*fpm|nginx"
systemctl status apache2
```

**Expected output:** `apache2ctl -M` lists loaded Apache modules (confirms Apache, not Nginx, is the active web server, consistent with the deployment guide). `php -v` reports the PHP version in use. The `systemctl` grep shows which of `apache2`/`php*-fpm`/`nginx` are actually installed as services — on an Apache+mod_php setup there may be no separate PHP-FPM service at all, which is fine; a PHP-FPM-based setup would show a `php*-fpm` unit instead (or in addition). `systemctl status apache2` shows whether Apache is active and, in its startup log tail, roughly when it was last (re)started.

**What failure means:** if `systemctl status apache2` shows Apache was last restarted well before the last `git pull`, that's independent evidence supporting the "code was pulled but the running server never picked it up" hypothesis (relevant if this Apache setup uses `mod_php`, whose compiled/cached opcode behavior — see §4 — is tied to the running Apache worker processes, not just the files on disk).

---

## 4. Check OPcache configuration

**READ-ONLY. SAFE TO RUN.**

```bash
php -i | grep -Ei "opcache.enable|opcache.validate_timestamps|opcache.revalidate_freq"
```

**Expected output and interpretation:**
- `opcache.enable => On` — OPcache is active (normal and desirable for production performance).
- `opcache.validate_timestamps => On` — PHP re-checks each file's mtime against its cached bytecode and automatically recompiles on change. This is the safe default; if this is `On`, OPcache is very unlikely to be the cause of stale code being served after a `git pull` (each pulled file gets a new mtime).
- `opcache.validate_timestamps => Off` — **this is a strong lead.** With validation off, OPcache will keep serving the bytecode it compiled the first time a file was requested, indefinitely, until the web server process is restarted/reloaded — regardless of how many times the file changes on disk afterward. A `git pull` alone would silently do nothing in this configuration.
- `opcache.revalidate_freq` — if `validate_timestamps` is On, this is how many seconds between re-checks (0 = check every request). A high value here combined with infrequent traffic could also cause a lag between a file changing and OPcache noticing.

**What failure means:** if `validate_timestamps` is `Off`, that alone can fully explain "the fix was pushed to GitHub and pulled to the server, but the live page still shows old behavior" — the running PHP processes are still executing the previously-compiled version of `index.php`/`functions.php` from before the `git pull`. The fix in that case is an Apache/PHP restart or reload (`sudo systemctl reload apache2` for mod_php, or the appropriate PHP-FPM reload command if a `php*-fpm` service was found in §3) — an infrastructure action, deliberately not included as a command in this document since it is not read-only; the deployment steps in the completion report's Deployment Instructions cover it explicitly, gated on this check.

---

## 5. Check the configured production database — without exposing the password

**READ-ONLY. SAFE TO RUN. Reads a config file; does not print the password.**

```bash
grep -A1 "DB_NAME\]" /etc/php/*/fpm/pool.d/*.conf /etc/apache2/envvars 2>/dev/null
grep "DB_NAME\|DB_HOST\|DB_USER" /etc/apache2/envvars 2>/dev/null
php -r "require '/var/www/komagin-enterprise/hrms/config/config.php'; echo DB_HOST,' ',DB_NAME,' ',DB_USER,' ',APP_ENV,' build=',BUILD_ID,PHP_EOL;"
```

**Expected output:** `DB_HOST=localhost` (or `127.0.0.1`), `DB_NAME=komagin_hr`, `DB_USER=komagin_app` (per the deployment guide's provisioning steps), `APP_ENV=production`. The last command additionally now prints `BUILD_ID` (added by this remediation) — if a deploy script sets `BUILD_ID` or `GIT_COMMIT` as a real environment variable, this line will show exactly which commit is live without needing to run any `git` command on the server per-request; if neither is set, it falls back to printing the static `APP_VERSION` string, which confirms the fallback is working but doesn't identify a specific commit.

**What failure means:** any of these resolving to something other than the expected value (e.g. `DB_NAME` pointing at an unexpected schema name, or `APP_ENV` reporting anything other than `production`) means the running application is not configured the way the deployment guide intends — investigate before trusting any data seen in §6 below, since it may be reading from the wrong database entirely.

---

## 6. Run read-only production SQL for July 2026

**READ-ONLY. SAFE TO RUN. SELECT statements only.**

```bash
mysql -u komagin_app -p komagin_hr -e "
  SELECT * FROM payroll_runs WHERE period_month=7 AND period_year=2026;"

mysql -u komagin_app -p komagin_hr -e "
  SELECT id, employee_id, period_month, period_year, payroll_run_id, status,
         gross_salary, total_deductions, net_salary
  FROM payslips WHERE period_month=7 AND period_year=2026;"

mysql -u komagin_app -p komagin_hr -e "
  SELECT
      COUNT(*) AS payslip_count,
      COALESCE(SUM(gross_salary),0) AS total_gross,
      COALESCE(SUM(total_deductions),0) AS total_deductions,
      COALESCE(SUM(net_salary),0) AS total_net
  FROM payslips
  WHERE period_month=7 AND period_year=2026;"
```

**Expected output:** if the incident's root cause was purely code-level (drift/OPcache), these queries should return **zero rows** for `payroll_runs`/`payslips` and an all-zero aggregate — consistent with what this repository's local database already shows (verified in the audit). If any row IS returned here, that is new information this audit could not have had — proceed to §7.

**What failure means:** any non-empty result here means production genuinely holds July 2026 payroll data that does not exist in this repository or its seed scripts (already ruled out as a source in the audit, §14). Do not assume this is mock/demo data — treat it as real production data requiring the same care as any other payroll record, and bring it back for a properly scoped follow-up (not a repeat of this same investigation) rather than acting on it here.

---

## 7. Check whether `262145` exists in relevant production columns

**READ-ONLY. SAFE TO RUN.**

```bash
mysql -u komagin_app -p komagin_hr -e "
  SELECT id, employee_id, period_month, period_year, payroll_run_id, status,
         gross_salary, total_deductions, net_salary
  FROM payslips
  WHERE gross_salary=262145 OR total_deductions=262145 OR net_salary=262145;"

mysql -u komagin_app -p komagin_hr -e "
  SELECT id, period_month, period_year, status, total_gross, total_net, total_deductions
  FROM payroll_runs
  WHERE total_gross=262145 OR total_net=262145 OR total_deductions=262145;"
```

**Expected output:** zero rows (matches this repository's local database).

**What failure means:** a matching row identifies exactly the data that produced the reported figures — record its full row content (safely, in a private follow-up, not pasted into a public channel, since it may contain real payroll amounts) and use it to write a properly scoped, evidence-based fix, rather than guessing.

---

## 8. Review only recent, relevant Apache/PHP errors

**READ-ONLY. SAFE TO RUN. Read-only tail of existing log files.**

```bash
sudo tail -n 200 /var/log/apache2/error.log | grep -i -E "payroll|PAYROLL_DIAG|PAYROLL_INVARIANT|PHP Fatal|PHP Warning"
sudo tail -n 200 /var/www/komagin-enterprise/hrms/logs/php_errors.log 2>/dev/null | grep -i -E "payroll|PAYROLL_DIAG|PAYROLL_INVARIANT"
```

**Expected output (once this remediation is deployed):** `[PAYROLL_DIAG]` lines from `logPayrollDashboardDiagnostics()` for every dashboard view (non-sensitive — see the completion report's Security and Privacy Review for exactly what these contain), and `[PAYROLL_INVARIANT]` lines only if the defensive checks in `normalizePayrollSummary()` ever actually fire. Before this remediation is deployed, expect no such lines at all — their total absence is itself useful evidence that the deployed code predates this change (reinforcing §1/§2 above).

**What failure means:** a `[PAYROLL_INVARIANT]` line appearing for a real production request is a live confirmation that the underlying data itself (not just the query logic) has a genuine inconsistency for that specific period — cross-reference its `period=`/`mode=`/`run_id=` fields against §6's query for that exact period.

---

## 9. Confirm no reverse-proxy or FastCGI cache is configured

**READ-ONLY. SAFE TO RUN.**

```bash
grep -ri "proxy_cache\|fastcgi_cache" /etc/nginx/sites-enabled/* /etc/nginx/nginx.conf 2>/dev/null
grep -ri "CacheEnable\|mod_cache" /etc/apache2/sites-enabled/* /etc/apache2/apache2.conf /etc/apache2/mods-enabled/*.load 2>/dev/null
```

**Expected output:** no matches (this deployment is documented as Apache-only with no caching layer configured in front of it).

**What failure means:** any match identifies an additional layer between the browser and PHP that could be serving a stale response independently of anything covered in §1-§4 — would need its own cache-clear/bypass step, not covered by this document, before further diagnosis is meaningful.

---

## Explicitly excluded from this document

Do not run, as part of this procedure, without a separate and deliberate follow-up decision:

- Any `UPDATE`, `INSERT`, `DELETE`, `TRUNCATE`, `DROP`, or `ALTER` statement.
- `git pull`, `git reset`, `git checkout --`, or any other command that changes the working tree or history.
- `sudo systemctl restart|reload apache2` or any PHP-FPM restart/reload — these ARE the correct remedy if §1/§2/§4 point to drift or OPcache staleness, but performing them is a deployment action with real (if brief) service-availability impact, not a read-only verification step, and belongs in a deliberate, announced deployment window per the completion report's Deployment Instructions — not bundled into a diagnostic pass.
- Execution of `database/repair_june2026_orphan_payslip.sql` — irrelevant to a July 2026 finding regardless, and requires its own explicit review per its own header comment.
