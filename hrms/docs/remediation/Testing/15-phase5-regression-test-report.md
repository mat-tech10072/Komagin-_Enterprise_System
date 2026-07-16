# Komagin HR — Phase 5 Stage 5.13: Full Phase 5 Regression Suite

**Document type:** Phase 5 Deliverable — Stage 5.13 Report
**Status:** Complete.
**Date compiled:** 2026-07-13

---

## 1. What Was Built

A new, permanent, re-runnable regression script — `docs/remediation/Testing/phase5-regression-run.sh` — covering every dedicated test group the Phase 5 charter requires, following the same conventions as `phase1-regression-run.sh`/`phase2-regression-run.sh` (real HTTP requests and real database writes against the running local instance, using disposable `P5REGRESS`/`p5regress`-prefixed data the script creates and removes itself, results appended to `docs/remediation/Testing/phase5-regression-results.log`).

**13 test groups, 40 assertions total:**

1. **Leave** (Stage 5.1) — confirms the workflow config no longer has a Supervisor Review stage and leave is single-stage.
2. **ApprovalEngine** (Stage 5.2) — confirms the 4 dormant workflow types are gone from `workflowConfig()` and the `cancel()` concatenation bug is fixed.
3. **Working-Day Calendar** (Stage 5.3) — re-runs the existing 17-test unit suite (`phase5-calendar-unit-tests.php`) and confirms the admin UI is reachable.
4. **Scheduler** (Stage 5.4) — confirms `cron/run.php` is blocked over HTTP (403) and completes successfully via CLI with no task failures.
5. **Self-Service Password Recovery** (Stage 5.5) — the most involved group: creates a disposable user, confirms enumeration resistance (byte-identical response for a real vs. nonexistent identifier), extracts the real reset token from `email_logs`, completes a live reset, confirms the consumed token is rejected on reuse, and confirms a separately-authenticated session is force-invalidated (302) immediately after — the full session-invalidation mechanism, not just the token flow in isolation.
6. **Deferred Notifications** (Stage 5.6) — creates a disposable employee with a contract expiring tomorrow, runs `send_reminders.php` directly, confirms a real notification was created, runs it again the same day, and confirms zero duplicate notifications (the per-day dedup mechanism).
7. **Recruitment-to-Employee Conversion** (Stage 5.7) — creates a disposable `selected` application, confirms the guided Add Employee form pre-fills the applicant's name.
8. **Temporary Employee Attendance** (Stage 5.8) — confirms the entry page is reachable and the `temp_attendance` table exists.
9. **Document QR Verification** (Stage 5.9) — confirms the QR toggle is absent from the template editor UI and that `DocumentEngine.php` no longer calls the external QR API or checks the `show_qr_code` flag.
10. **Stage 5.10 spot checks** — Reports Hub CSV export reachable, SMTP password no longer emitted with a cleartext value, Audit/Activity Log merge link present.
11. **Full Phase 1 + Phase 2 re-run** — invokes both existing suites as subprocesses and folds their pass/fail totals into this suite's own result.
12. **Repo-wide PHP syntax scan** — `php -l` against every `.php` file in the repository, not just the files this phase touched.
13. **Migration verification** — re-applies `database/phase13_workflow_completeness_automation.sql` against the live database and confirms it is genuinely idempotent (0 output/errors on a second application), then confirms all 7 tables Phase 5 introduced actually exist.

## 2. Issues Found and Fixed While Building This Suite

Two issues were found and corrected during the first two runs — both in the test script itself, not in application code:

1. **Token-extraction bug**: the reset link appears twice in the password-reset email body (once in the `href` attribute, once as the visible link text). The original extraction regex matched both occurrences, concatenating them into a 129-character string that correctly failed `reset_password.php`'s own `strlen($rawToken) !== 64` validation — surfacing as 4 cascading failures in Group 5 on the first run. Not an application bug: the token itself was correct; the test's extraction needed `head -1` to take only the first match. Fixed and re-verified.
2. **QR assertion too broad**: the initial Group 9 assertion grepped for the literal string `show_qr_code`/`qrserver`, which also matches the *explanatory comments* Stage 5.9 deliberately left in `DocumentEngine.php` documenting why the feature is disabled (not just the removed functional code). Narrowed to check for the actual API URL (`https://api.qrserver.com`, with scheme — never present in the comments) and the specific conditional pattern (`$tpl['show_qr_code']`) instead, which correctly resolve to zero now that the functional code is gone.

Both are documented here per this program's standing discipline of disclosing every correction made along the way, even when the underlying application code was already correct.

## 3. Final Result

```
TOTAL: 40 passed, 0 failed
```

All 40 assertions pass. Phase 1 regression: 20/20. Phase 2 regression: 29/29. Repo-wide syntax scan: 0 errors across every `.php` file in the repository. Migration re-apply: idempotent, 0 errors. All 7 Phase 5 database tables confirmed present.

All disposable test data created by the script (test users, employees, applications, notifications, tokens, reminder-log entries) was confirmed removed after the run — verified with a direct database query showing 0 remaining rows across every `P5REGRESS`/`p5regress` prefix, and local scratch cookie jars cleaned up.

## 4. How to Re-Run

```bash
bash docs/remediation/Testing/phase5-regression-run.sh
```

Requires XAMPP Apache + MySQL running and the app reachable at `http://localhost/HR_Komagin`. Safe to re-run repeatedly — every test creates and removes its own disposable data, and the script itself doesn't assume any particular starting database state beyond the seeded test accounts already required by Phase 1/2's suites.
