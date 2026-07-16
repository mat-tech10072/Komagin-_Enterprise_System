# HR Komagin — Comprehensive Playwright Inspection Report

**Generated:** 2026-06-24  
**Tested by:** Playwright (Chromium headless) — `comprehensive-inspect.js`  
**Base URL:** http://localhost/HR_Komagin  
**Authenticated as:** `superadmin`  
**Modules inspected:** 41  
**Total scan time:** 134 seconds (avg 3.3 s/page)

---

## Executive Summary

| Status | Count | % |
|--------|------:|--:|
| ✅ Passed (OK) | 28 | 68% |
| ❌ Fatal error | 1 | 2% |
| ⚠️ PHP Warning / Deprecated | 6 | 15% |
| ⚠️ JS Error / 404 | 6 | 15% |
| 🔒 Auth redirect | 0 | — |
| 💀 Navigation failure | 0 | — |

**The core HR admin system (28/35 admin pages) is stable.** Authentication, session management, and role-based access all work correctly — no page was incorrectly blocked. One hard fatal error crashes the Employee Master Report. Six PHP notices (non-fatal) leak into rendered HTML. The entire Employee Self-Service Portal is a dead 404 because the URL prefix is wrong.

---

## Module-by-Module Results

### ✅ Passing Modules (28)

| # | Module | Group | Load |
|---|--------|-------|-----:|
| 02 | Employees List | Employees | 2629ms |
| 03 | Add Employee | Employees | 2652ms |
| 04 | Attendance | Attendance | 2626ms |
| 05 | Attendance Kiosk | Attendance | 1087ms |
| 07 | Overtime Approvals | Timesheets | 3401ms |
| 08 | Leave Requests | Leave | 2596ms |
| 10 | Leave Types | Leave | 2630ms |
| 11 | Recruitment | Recruitment | 3618ms |
| 12 | Onboarding | Onboarding | 2585ms |
| 13 | Training Programs | Training | 2624ms |
| 14 | Performance Reviews | Performance | 2692ms |
| 15 | Disciplinary & Grievances | Disciplinary | 2742ms |
| 16 | Asset Management | Assets | 2685ms |
| 17 | Document Management | Documents | 2682ms |
| 18 | Missing Documents | Documents | 2629ms |
| 19 | Reports & Analytics Hub | Reports | 2688ms |
| 21 | Timesheet Report | Reports | 2634ms |
| 25 | Payroll Dashboard | Payroll | 2677ms |
| 26 | Payroll Payslips | Payroll | 2692ms |
| 27 | Payroll Deductions | Payroll | 2703ms |
| 28 | Savings & Benefits | Payroll | 2682ms |
| 29 | Payroll Reports | Payroll | 2662ms |
| 30 | Employee Request Hub | Hub | 2582ms |
| 31 | User Management | Admin | 2707ms |
| 32 | My Profile | Admin | 3779ms |
| 33 | Roles & Permissions | Admin | 2631ms |
| 34 | System Settings | Admin | 2721ms |
| 35 | Audit Logs | Admin | 2650ms |

**Interaction probes** run on passing modules confirmed:
- Employees List: search for "John" returned 1 row; table headers correct
  (`Employee | Emp. Number | Department | Position | Type | Start Date | Status | Actions`)
- Users Management: 2 user accounts visible in table
- Payroll Dashboard: month-filter navigation working (page reloads correctly)
- System Settings: 4 editable form fields present
- Roles & Permissions: 90 permission toggle forms rendered (one per permission/role combo)

---

## ❌ Issue 1 — FATAL: Employee Master Report crashes on null email

**File:** [modules/reports/employees.php](modules/reports/employees.php#L122)  
**Severity:** Critical — page is unusable, hard PHP 8 TypeError  
**Status:** FATAL

### What happens
When the Employee Master Report renders its data table, it calls `e($emp['work_email'])` on line 122. The `e()` helper (HTML-escaping function) has its type signature constrained to `string`. If any employee has a NULL `work_email` in the database, PHP 8 throws:

```
Fatal error: Uncaught TypeError: e(): Argument #1 ($value)
must be of type string, null given,
called in .../modules/reports/employees.php on line 122
```

The entire page aborts mid-render — the report is completely broken.

### Root cause
The `work_email` column is nullable in the database (`LEFT JOIN` on `employees` returns NULL for any row where the column was not populated). The `e()` function does not coerce to string before escaping.

### Fix
```php
// employees.php line 122 — was:
<div style="font-size:0.65rem;color:var(--text-muted);"><?= e($emp['work_email']) ?></div>

// Fix — null-coalesce before passing:
<div style="font-size:0.65rem;color:var(--text-muted);"><?= e($emp['work_email'] ?? '') ?></div>
```

Also apply the same guard to any other `e(...)` calls on nullable columns (`dept_name`, `position_title` are already guarded with `?? '—'`).

---

## ⚠️ Issue 2 — PHP Warning: Array-to-string conversion on Dashboard

**File:** [dashboard.php](dashboard.php#L466)  
**Severity:** Medium — warning leaks into page HTML but chart still renders  
**Status:** PHP_WARN

### What happens
```php
$extraScripts = <<<JS
<script>
    const trendData = <?= json_encode($trendData ?? []) ?>;
JS;
```
The `<<<JS ... JS` heredoc string supports PHP variable interpolation (`$variable`). When PHP sees `$trendData` inside the heredoc it tries to concatenate it directly as a string — it does **not** call `json_encode()` because the `<?= ?>` tags are treated as literal text inside a heredoc, not as PHP code. PHP tries to stringify the array and emits:

```
Warning: Array to string conversion in dashboard.php on line 466
```

### Fix
Pre-encode to a variable before the heredoc:
```php
// Before the heredoc, add:
$trendDataJson  = json_encode($trendData  ?? []);
$deptDataJson   = json_encode($deptData   ?? []);
$leaveTypeJson  = json_encode($leaveByType ?? []);

// Then in the heredoc use the pre-encoded strings:
const trendData = $trendDataJson;
const deptData  = $deptDataJson;
```

---

## ⚠️ Issue 3 — PHP Deprecated: `number_format()` receives null (Timesheets)

**File:** [modules/timesheets/index.php](modules/timesheets/index.php#L85)  
**Severity:** Low — deprecated notice, page renders correctly  
**Status:** PHP_WARN

### What happens
```php
['Total Hours', number_format($summary['total_hours'],1), 'var(--success)'],
```
When there are no timesheet records `$summary['total_hours']` is NULL (aggregate SUM returns NULL on empty set). PHP 8.1 deprecated passing null to `number_format()`.

### Fix
```php
number_format((float)($summary['total_hours'] ?? 0), 1)
number_format((float)($summary['total_ot']    ?? 0), 1)
```

The same fix applies to **Archive Quarterly** ([modules/archive/quarterly.php:111](modules/archive/quarterly.php#L111)) and **Archive Yearly** ([modules/archive/yearly.php:130](modules/archive/yearly.php#L130)) which have the same pattern on their KPI cards.

---

## ⚠️ Issue 4 — PHP Warning: Undefined array key `is_paid` (Apply Leave)

**File:** [modules/leave/apply.php](modules/leave/apply.php#L172)  
**Severity:** Low — label shows "(Unpaid)" for every type, page still renders  
**Status:** PHP_WARN

### What happens
```php
<option value="<?= $lt['id'] ?>"><?= e($lt['name']) ?> <?= $lt['is_paid']?'(Paid)':'(Unpaid)' ?></option>
```
The SQL query that fetches `$leaveTypes` does not SELECT the `is_paid` column (or the column name differs in the DB schema). PHP emits an "Undefined array key" warning and short-circuits to `(Unpaid)` for every leave type.

### Fix — option A (guard the key)
```php
<?= isset($lt['is_paid']) && $lt['is_paid'] ? '(Paid)' : '(Unpaid)' ?>
```

### Fix — option B (fix the query)
Verify the actual column name in the `leave_types` table and add it to the SELECT in the query that populates `$leaveTypes`.

---

## ⚠️ Issue 5 — Wrong `include` path breaks Archive Monthly layout

**File:** [modules/archive/monthly.php](modules/archive/monthly.php#L57)  
**Severity:** Medium — nav sidebar missing; page title blank; CSS partially broken  
**Status:** PHP_WARN

### What happens
```php
$headerInclude = dirname(dirname(dirname(__DIR__))) . '/includes/header.php';
include $headerInclude;
```
`__DIR__` = `.../HR_Komagin/modules/archive`  
Three levels up → `.../New_xampp/htdocs` (outside the project).  
The resolved path becomes `C:\New_xampp\htdocs/includes/header.php` — this file does not exist.

```
Warning: include(C:\New_xampp\htdocs/includes/header.php): Failed to open stream:
No such file or directory
```

The nav sidebar is absent (the page has 0 nav items vs 26 on all other pages) and the `<title>` is empty.

### Fix
```php
// was: dirname(dirname(dirname(__DIR__)))
$headerInclude = dirname(dirname(__DIR__)) . '/includes/header.php';
include $headerInclude;
```
Two levels up is the project root (`HR_Komagin`), matching every other module.

---

## ⚠️ Issue 6 — Employee Self-Service Portal: all pages return HTTP 404

**Files:** `self-service/dashboard.php`, `employment.php`, `payslips.php`, `hub.php`, `savings.php`, `policy.php`  
**Severity:** High — the entire employee self-service experience is unreachable  
**Status:** JS_ERROR (underlying cause: HTTP 404)

### What happens
The test (and any browser navigating to `/HR_Komagin/self-service/`) receives a 404 for every URL because the `self-service/` directory contains **only** `update.php`.

The actual Employee Portal lives at `/HR_Komagin/employee-portal/` and contains all the expected pages:

```
employee-portal/
  dashboard.php
  employment.php
  hub.php
  login.php
  logout.php
  payslips.php
  policy.php
  savings.php
  _config.php
  _layout.php
  _session.php
```

### Root cause
The Playwright test was written with the wrong path prefix (`/self-service/` instead of `/employee-portal/`). This also likely means any nav links in the admin system pointing to `self-service/` will be broken for employees.

### Fix
1. Search and replace any hardcoded `/self-service/` links in the admin nav or email notifications with `/employee-portal/`.
2. Update the Playwright test path prefix.
3. (Optional) Create a redirect: `self-service/index.php` → `employee-portal/`.

---

## Performance Notes

All 35 admin pages load in 2.5–3.8 seconds. This is **slow for a local development server** and suggests either:
- PDO queries are not indexed (each page runs 5–15 queries)
- XAMPP PHP opcode cache is disabled
- `waitUntil: networkidle` is waiting for idle font/asset loads

Five slowest pages:

| Rank | Page | Time |
|------|------|-----:|
| 1 | My Profile | 3779ms |
| 2 | Recruitment | 3618ms |
| 3 | Overtime Approvals | 3401ms |
| 4 | Apply Leave | 2805ms |
| 5 | Archive Monthly | 2770ms |

The Attendance Kiosk (1087ms) and all Self-Service 404s (~515ms) are the only fast responses — because they return minimal content or an error page immediately.

**Recommendation:** Enable OPcache in `php.ini` (`opcache.enable=1`) and verify indexes exist on frequently-queried columns (`attendance.attendance_date`, `employees.status`, `payslips.period_month`).

---

## UI / Content Observations

| Module | H1 | Tables | Forms | Buttons |
|--------|----|-------:|------:|--------:|
| Dashboard | HR Dashboard | 2 | 0 | 14 |
| Employees List | Employees | 1 | 1 | 15 |
| Add Employee | Add New Employee | 0 | 1 | 6 |
| Leave Types | Leave Types | 1 | 9 | 15 |
| Roles & Permissions | Roles & Permissions | 1 | **90** | **93** |
| Payroll Dashboard | Payroll Dashboard | 0 | 2 | 7 |
| Users Management | User Management | 1 | 3 | 12 |

- **Roles & Permissions** has 90 forms and 93 buttons — one form+button per permission toggle. This is correct architecture (CSRF per toggle) but will be slow to render for large permission matrices.
- Several modules currently show **0 data rows** in their tables (Leave, Attendance, Recruitment, Training, Performance, Hub, Audit) — this is consistent with a fresh/demo database, not a code error.
- The `#` href appears in every module's bad-link scan — these are likely JavaScript-triggered modal buttons which use `href="#"`. This is standard practice, not a broken link.

---

## Action Items (Priority Order)

| Priority | Issue | File | Fix |
|----------|-------|------|-----|
| 🔴 P1 | Fatal error on Employee Report | [reports/employees.php:122](modules/reports/employees.php#L122) | `e($emp['work_email'] ?? '')` |
| 🔴 P1 | Employee Portal returns 404 | `self-service/*` | All pages are in `employee-portal/`, update paths |
| 🟠 P2 | Archive Monthly broken layout | [archive/monthly.php:57](modules/archive/monthly.php#L57) | Remove one `dirname()` level |
| 🟠 P2 | Dashboard array-to-string in heredoc | [dashboard.php:466](dashboard.php#L466) | Pre-encode arrays before heredoc |
| 🟡 P3 | Leave `is_paid` undefined key | [leave/apply.php:172](modules/leave/apply.php#L172) | Add `isset()` guard or fix SELECT |
| 🟡 P3 | `number_format(null)` deprecation (×3) | timesheets, quarterly, yearly | Cast to `(float)` with `?? 0` |
| 🟢 P4 | Slow page loads (~3s avg on localhost) | All modules | Enable PHP OPcache |

---

## Screenshots

Screenshots for all 41 pages are saved at:  
`tests/screenshots/01_dashboard.png` → `41_self_service_policy.png`

Raw JSON data for all results is at:  
`tests/inspect-report.json`
