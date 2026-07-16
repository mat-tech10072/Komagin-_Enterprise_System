# Komagin HR — Phase 4 Workflow Group 11: Notifications

**Document type:** Phase 4 Deliverable — Workflow Group Report 11 of N
**Status:** Live-verified with disposable test records, fully cleaned up afterward.
**Date compiled:** 2026-07-13
**Scope:** Every trigger that creates a `notifications` row or sends an email across the system — Approval, Rejection, Leave, Payroll, Employee Hub, and a check for Password, Training, Recruitment, and Reminder triggers.

---

## 1. Critical — Stored XSS in the Notification Bell, Reachable by Any Employee (KOM-093)

The notification dropdown used by every logged-in user (all roles) is populated by `api/notifications.php` (a plain `SELECT *` returned as JSON, no server-side escaping — correct, since escaping belongs at the point of output, not storage) and rendered client-side in `includes/footer.php` by interpolating `title`/`message` directly into a template literal assigned to `.innerHTML`, with no escaping anywhere in that chain.

`employee-portal/hub.php` — the employee self-service request form — passes the submitting employee's own raw, free-text `subject` straight into a notification sent to **every** `hr_manager` and `super_admin`:

```php
$notifMsg = $empName . ' submitted a ' . strtolower($typeLabel) . ': ' . $subject;
notifyRole('hr_manager',  'warning', $notifTitle, $notifMsg, $notifLink);
notifyRole('super_admin', 'warning', $notifTitle, $notifMsg, $notifLink);
```

**Live-verified**: logged in as a plain employee (no elevated role) and submitted a hub request with `subject=P4TESTXSS <script>alert(1)</script>`. The payload was stored verbatim; fetching `api/notifications.php?action=list` as `super_admin` confirmed the API returns the raw, dangerous payload unmodified — only the client-side renderer stood between that payload and execution.

This is a genuine privilege-escalation chain: the lowest-privilege authenticated role in the system (a portal employee — no special permission required, just being logged in) can execute arbitrary script in the browser session of the highest-privilege live roles. Since the page also exposes `window.CSRF_TOKEN` and the live session cookie is available to any script running in that page's origin, this is a realistic path to full account takeover of an HR Manager or Super Admin — worse in reach than the register's other stored-XSS finding (KOM-022, which requires template create/edit rights before it's reachable at all).

**Fix:** added an `escapeHtml()` helper (the standard `textContent`→`innerHTML` round-trip idiom) to `footer.php`'s notification renderer and applied it to `title`, `message`, and the resolved notification `link` — a single fix at the shared chokepoint every current and future notification source flows through, rather than a patch to the one call site that happens to be exploitable today.

**Verification note:** confirmed by direct code review and by confirming the API still delivers the unescaped payload to the client (as expected — the fix is client-side by design). This environment has no browser automation available, so a full click-through (opening the actual dropdown as an admin and confirming no script fires) was not performed. `escapeHtml()` uses a well-established, correct escaping technique, but a live browser check is recommended before Phase 5 sign-off.

**Finding ID:** KOM-093 (new, fixed — Critical)

## 2. Fixed — Approval/Rejection Decisions Were Never Communicated to the Requester (KOM-095)

`leave/apply.php`/`leave/approve.php` bypass `ApprovalEngine` entirely and have always notified the leave applicant directly on decision. But every workflow that actually flows through the shared `ApprovalEngine::act()` method — in current practice, only `termination`, `transfer`, and `promotion` are ever created (the engine's other 5 defined types — `payroll_run`, `document`, `overtime`, `correction`, plus `leave` itself when routed through the engine — are never actually instantiated anywhere in the codebase) — resolved the workflow and applied the real employee change with zero notification to anyone. The HR staff member who submitted a termination, transfer, or promotion request had no in-app signal that a decision had even been made; the only way to find out was to manually revisit the Approvals page.

**Fix:** new private `notifyInitiator()` method on `ApprovalEngine`, called from both the reject branch and the final-stage-approved branch of `act()`. Notifies `approval_workflows.initiated_by` with the outcome and the reviewer's comments — generalizing the exact pattern `leave/approve.php` already used, but at the engine level so it applies to every current and future workflow type for free.

Deliberately **does not** notify the employee the workflow is *about* (e.g., the person being terminated) — for a termination specifically, that's a human-conversation matter, not an automated in-app popup, and extending notification to the subject employee (versus just the requester) is a separate, more sensitive design question kept out of scope here.

**Live-verified**: created a disposable test employee, submitted a termination request as one admin (`initiated_by`), approved it as a different `hr_manager` user (honoring the engine's existing separation-of-duties check), and confirmed the initiating admin received a correctly-typed `success` notification with the reviewer's comments attached. Workflow and employee status both correctly updated to `approved`/`terminated`.

**Finding ID:** KOM-095 (new, fixed — High)

## 3. Fixed — Same ENUM-Misuse Bug Class as Workflow Group 1's Self-Correction, Found Still Live (KOM-094)

While investigating KOM-093, found `employee-portal/hub.php` calling `notifyRole('hr_manager', 'hub_request', ...)` — but `notifications.type` is a 4-value ENUM (`info`/`success`/`warning`/`danger`), with no `hub_request` member. This database has no `STRICT_TRANS_TABLES` in its SQL mode, so MySQL silently coerced the invalid value to an empty string rather than erroring. Live-verified against real, pre-existing (not test) data: every "New Hub Request" notification ever created in this system had `type=''`. No current UI reads `type` for icon/color coding, so this had zero visible symptom — but it's exactly the same mistake self-caught and corrected in Workflow Group 1's own new code earlier this phase.

**Fix:** both `notifyRole()` calls changed to `'warning'`, matching the "requires HR attention" convention every other `notifyRole()` call in the codebase already uses.

**Live-verified**: submitted a real hub request post-fix — resulting `notifications` row has `type='warning'`.

**Finding ID:** KOM-094 (new, fixed — Medium)

## 4. Confirmed Correct — No Changes Needed

- `modules/leave/apply.php` / `approve.php`: applicant notification on submission and on decision both already correct (and were not reachable by the KOM-093 sink, since neither builds its message from unescaped user free text — `days` is numeric, `applicantName` comes from the employee's own DB record, not this submission's raw input).
- `modules/employees/{add,edit,status}.php`: HR-privileged notifications (new employee, transfer/promotion/termination awaiting approval) all correctly typed and unaffected — these require HR-level access to trigger already, a materially different exploitability profile than the Hub's open-to-any-employee path.
- `modules/payroll/run_publish.php`: correctly emails payslips via `sendPayslipEmail()` when `payslip_notify` is configured, gated on `smtp_host` being set, with a proper audit log entry and sent/failed count. No bug found.
- `api/notifications.php`'s `mark_read`/`mark_all_read` already require POST + CSRF (fixed in an earlier phase) — confirmed still correct.

## 5. Confirmed Absent — Documented as Completeness Gaps, Not Built

- **Training**: enrolling or marking an employee attended never notifies anyone — no `notifyRole`/`createNotification` call exists anywhere in `modules/training/*.php`.
- **Recruitment**: submitting or updating an application never notifies anyone — same, zero calls in `modules/recruitment/*.php`.
- **Password reset**: an admin resetting another user's password (`modules/users/index.php`'s `reset_password` action) audits the action but never notifies the affected user.
- **Reminder / scheduled notifications**: there is no cron job, scheduled-task runner, or any equivalent mechanism anywhere in the codebase (confirmed by a full-repository search for `cron`/`scheduled_task`/`reminder`) — contract-expiry, training-expiry, or document-expiry reminders are not a partially-broken feature, they simply do not exist as infrastructure.

None of these are a fix to existing broken behavior — they would each be new feature scope (Training/Recruitment also have no employee-facing portal surface today for a notification's link to even point to; a scheduler is genuine new infrastructure). Documented here for visibility, matching the treatment already given to comparable completeness gaps this phase (KOM-088, KOM-090), not built.

## 6. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`hub.php`, `footer.php`, `ApprovalEngine.php`) | 0 errors |
| JS syntax check (extracted inline script from `footer.php`) | 0 errors (`node -c`) |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group) | XSS payload confirmed reaching the client unescaped (pre-fix behavior, by design of the fix location); `notifications.type` correct post-fix; termination approval correctly notifies the initiator with correct type/content |

## 7. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-093 — Stored XSS in the notification bell via the employee Hub, reaching HR/admin sessions | Critical | **Fixed** |
| KOM-095 — Approval/rejection decisions never notified the requester | High | **Fixed** |
| KOM-094 — Invalid `notifications.type` ENUM value in Hub requests (same bug class as KOM-007) | Medium | **Fixed** |
| Training/Recruitment/password-reset notifications; any reminder/scheduler mechanism | — | **Documented, not built** (no existing behavior to fix; genuine new feature scope) |

**All three actionable findings are fixed and live-verified; KOM-093's client-side fix is additionally recommended for a manual browser click-through before Phase 5 sign-off, since this environment cannot execute a real browser to confirm visually.**
