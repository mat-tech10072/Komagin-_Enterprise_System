# Komagin HR — Dashboard Security Report

**Document type:** Phase 1 Deliverable #7 of 10
**Objective addressed:** Objective 8 — Dashboard Data Exposure
**Finding addressed:** KOM-018 (NH-01)
**Date:** 2026-07-11/12

---

## 1. The Problem

`dashboard.php`, the landing page every admin-surface user sees immediately after login, is gated only by `requireLogin()` — by design, since every role needs *some* dashboard. But one specific widget, "Recent Activity," ran:

```php
$recentActivity = db()->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 8")->fetchAll();
```

unconditionally, and rendered the result regardless of the viewer's role. `audit_logs` rows (per the Activity Log Authorization Report and the underlying schema) can contain field-level old/new-value diffs — including salary changes, bank detail edits, and role changes — plus IP addresses and free-text reasons. A `kiosk_terminal` or `payroll_officer` session, neither of which holds any audit-viewing permission, saw the same 8 most recent system-wide events as a `super_admin` would, purely by virtue of loading the homepage.

## 2. The Fix

```php
$recentActivity = (canView('audit.view') || canView('activity_log.view'))
    ? db()->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 8")->fetchAll()
    : [];
```

The query itself is now conditional, not just the rendering — a role without either permission never even asks the database for this data, rather than fetching it and hiding it in the HTML (which would still leak the data to anyone reading the raw response). The existing empty-state UI ("No activity recorded yet.") was already present in the template and required no changes — an unauthorized viewer now sees exactly what a legitimately-empty audit log would look like, which is also the correct behavior from a non-disclosure standpoint (it doesn't reveal "there IS activity you're not allowed to see," it just shows nothing).

## 3. Why Both Permissions Are Checked

`audit.view` (the older, `modules/audit/index.php`-gated permission) and `activity_log.view` (the new permission introduced in this same phase — see Activity Log Authorization Report) both grant legitimate access to this same underlying data through their respective dedicated modules. The dashboard widget should be visible to anyone who could otherwise see this data through either of those two module pages — checking both with `||` achieves that without inventing a third permission concept for what is fundamentally the same data.

## 4. Live Verification

Logged in as `payroll_officer` (holds neither `audit.view` nor `activity_log.view` per the seeded matrix) — dashboard loaded with the "No activity recorded yet." empty-state text present. Logged in as `super_admin` — dashboard loaded with the Recent Activity card populated. Both confirmed via direct HTTP request against the running application.

## 5. Scope Note

The "View All" link inside this same card (`<a href="...audit/index.php">`) was not made conditional — it always renders, pointing at `modules/audit/index.php`, which is itself independently and correctly permission-gated. A user without access who clicks it is redirected with the standard access-denied flow. This is a minor UX nicety (a visible link that a low-privilege user can click but won't get anywhere useful from), not a security gap, so it was left as-is to avoid unnecessary UI changes outside this objective's scope.

Other dashboard widgets (headcount, attendance, probation-ending, missing-documents counts) were reviewed and confirmed to not expose payroll-cost or similarly sensitive aggregate figures — this was already true before Phase 1 (noted in the Phase 0 baseline inventory) and required no change.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial dashboard security report, including live verification evidence | Remediation Program — Phase 1 |
