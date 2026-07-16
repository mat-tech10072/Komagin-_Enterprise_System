/**
 * HR Komagin — Comprehensive Playwright Inspection
 * Logs in as superadmin, crawls every module, captures screenshots,
 * checks JS/PHP errors, tests search/filter/nav interactions, and emits
 * a structured JSON report written to inspect-report.json.
 */

const { chromium } = require('playwright');
const fs   = require('fs');
const path = require('path');

const BASE        = 'http://localhost/HR_Komagin';
const SCREENSHOTS = path.join(__dirname, 'screenshots');
const REPORT_FILE = path.join(__dirname, 'inspect-report.json');

if (!fs.existsSync(SCREENSHOTS)) fs.mkdirSync(SCREENSHOTS, { recursive: true });

// ─── Module catalogue ──────────────────────────────────────────────────────
const MODULES = [
  // Core
  { path: '/dashboard.php',                        label: 'Dashboard',              group: 'Core' },
  // Employees
  { path: '/modules/employees/index.php',          label: 'Employees List',         group: 'Employees' },
  { path: '/modules/employees/add.php',            label: 'Add Employee',           group: 'Employees' },
  // Attendance
  { path: '/modules/attendance/index.php',         label: 'Attendance',             group: 'Attendance' },
  { path: '/modules/attendance/kiosk.php',         label: 'Attendance Kiosk',       group: 'Attendance' },
  // Timesheets
  { path: '/modules/timesheets/index.php',         label: 'Timesheets',             group: 'Timesheets' },
  { path: '/modules/timesheets/overtime.php',      label: 'Overtime',               group: 'Timesheets' },
  // Leave
  { path: '/modules/leave/index.php',              label: 'Leave Requests',         group: 'Leave' },
  { path: '/modules/leave/apply.php',              label: 'Apply Leave',            group: 'Leave' },
  { path: '/modules/leave/types.php',              label: 'Leave Types',            group: 'Leave' },
  // Recruitment
  { path: '/modules/recruitment/index.php',        label: 'Recruitment',            group: 'Recruitment' },
  // Onboarding
  { path: '/modules/onboarding/index.php',         label: 'Onboarding',             group: 'Onboarding' },
  // Training
  { path: '/modules/training/index.php',           label: 'Training',               group: 'Training' },
  // Performance
  { path: '/modules/performance/index.php',        label: 'Performance',            group: 'Performance' },
  // Disciplinary
  { path: '/modules/disciplinary/index.php',       label: 'Disciplinary',           group: 'Disciplinary' },
  // Assets
  { path: '/modules/assets/index.php',             label: 'Assets',                 group: 'Assets' },
  // Documents
  { path: '/modules/documents/index.php',          label: 'Documents',              group: 'Documents' },
  { path: '/modules/documents/missing.php',        label: 'Missing Documents',      group: 'Documents' },
  // Reports
  { path: '/modules/reports/index.php',            label: 'Reports Hub',            group: 'Reports' },
  { path: '/modules/reports/employees.php',        label: 'Employee Report',        group: 'Reports' },
  { path: '/modules/reports/timesheets.php',       label: 'Timesheet Report',       group: 'Reports' },
  // Archive
  { path: '/modules/archive/monthly.php',          label: 'Archive Monthly',        group: 'Archive' },
  { path: '/modules/archive/quarterly.php',        label: 'Archive Quarterly',      group: 'Archive' },
  { path: '/modules/archive/yearly.php',           label: 'Archive Yearly',         group: 'Archive' },
  // Payroll
  { path: '/modules/payroll/index.php',            label: 'Payroll Dashboard',      group: 'Payroll' },
  { path: '/modules/payroll/payslips.php',         label: 'Payroll Payslips',       group: 'Payroll' },
  { path: '/modules/payroll/deductions.php',       label: 'Payroll Deductions',     group: 'Payroll' },
  { path: '/modules/payroll/savings.php',          label: 'Payroll Savings',        group: 'Payroll' },
  { path: '/modules/payroll/reports.php',          label: 'Payroll Reports',        group: 'Payroll' },
  // Hub
  { path: '/modules/hub/index.php',                label: 'Hub / Announcements',    group: 'Hub' },
  // Admin
  { path: '/modules/users/index.php',              label: 'Users Management',       group: 'Admin' },
  { path: '/modules/users/profile.php',            label: 'My Profile',             group: 'Admin' },
  { path: '/modules/roles/index.php',              label: 'Roles & Permissions',    group: 'Admin' },
  { path: '/modules/settings/index.php',           label: 'System Settings',        group: 'Admin' },
  { path: '/modules/audit/index.php',              label: 'Audit Logs',             group: 'Admin' },
  // Employee Portal (separate session — tested after portal login)
  { path: '/employee-portal/login.php',            label: 'Portal Login Page',      group: 'Employee-Portal' },
];

// ─── Interaction probes per module path ───────────────────────────────────
// Each probe receives (page) and returns { interactions: [...] }
const PROBES = {
  '/modules/employees/index.php': async (page) => {
    const items = [];
    // Test search box
    const searchBox = page.locator('input[name="search"], input[placeholder*="earch"]').first();
    if (await searchBox.count()) {
      await searchBox.fill('John');
      await page.keyboard.press('Enter');
      await page.waitForLoadState('networkidle');
      const count = await page.locator('table tbody tr, .employee-card').count();
      items.push({ action: 'search "John"', result: `${count} row(s) returned`, ok: true });
      await searchBox.fill('');
      await page.keyboard.press('Enter');
      await page.waitForLoadState('networkidle');
    }
    // Check table headers
    const headers = await page.locator('table th').allTextContents();
    items.push({ action: 'Table headers', result: headers.join(' | '), ok: headers.length > 0 });
    return items;
  },

  '/modules/payroll/index.php': async (page) => {
    const items = [];
    // Check month selector
    const monthSel = page.locator('select[name="month"]');
    if (await monthSel.count()) {
      await monthSel.selectOption('3');
      await page.waitForLoadState('networkidle');
      items.push({ action: 'Month filter March', result: 'page reloaded', ok: !page.url().includes('login') });
    }
    // Stat cards
    const cards = await page.locator('.stat-card, .card, .summary-card').count();
    items.push({ action: 'Summary cards visible', result: `${cards} card(s)`, ok: cards > 0 });
    return items;
  },

  '/modules/leave/index.php': async (page) => {
    const items = [];
    const rows = await page.locator('table tbody tr').count();
    items.push({ action: 'Leave request rows', result: `${rows} row(s)`, ok: true });
    const statusFilter = page.locator('select[name="status"]');
    if (await statusFilter.count()) {
      await statusFilter.selectOption({ index: 1 });
      await page.waitForLoadState('networkidle');
      items.push({ action: 'Status filter applied', result: 'filtered', ok: true });
    }
    return items;
  },

  '/modules/attendance/index.php': async (page) => {
    const items = [];
    const rows = await page.locator('table tbody tr').count();
    items.push({ action: 'Attendance rows', result: `${rows} row(s)`, ok: true });
    return items;
  },

  '/modules/timesheets/index.php': async (page) => {
    const items = [];
    const rows = await page.locator('table tbody tr').count();
    items.push({ action: 'Timesheet rows', result: `${rows} row(s)`, ok: true });
    return items;
  },

  '/modules/users/index.php': async (page) => {
    const items = [];
    const rows = await page.locator('table tbody tr').count();
    items.push({ action: 'User rows', result: `${rows} row(s)`, ok: rows > 0 });
    return items;
  },

  '/modules/audit/index.php': async (page) => {
    const items = [];
    const rows = await page.locator('table tbody tr').count();
    items.push({ action: 'Audit log rows', result: `${rows} row(s)`, ok: true });
    return items;
  },

  '/modules/roles/index.php': async (page) => {
    const items = [];
    const tabs = await page.locator('.nav-tab, .role-tab, [data-role]').count();
    items.push({ action: 'Role tabs visible', result: `${tabs} tab(s)`, ok: true });
    return items;
  },

  '/modules/settings/index.php': async (page) => {
    const items = [];
    const inputs = await page.locator('input[type="text"], input[type="email"], select').count();
    items.push({ action: 'Settings form fields', result: `${inputs} field(s)`, ok: inputs > 0 });
    return items;
  },

  '/modules/hub/index.php': async (page) => {
    const items = [];
    const announcements = await page.locator('.announcement, .post, article, .hub-item').count();
    items.push({ action: 'Hub items visible', result: `${announcements} item(s)`, ok: true });
    return items;
  },

  '/modules/reports/employees.php': async (page) => {
    const items = [];
    const rows = await page.locator('table tbody tr').count();
    items.push({ action: 'Report rows', result: `${rows} row(s)`, ok: true });
    const exportBtn = await page.locator('a[href*="export"], button:has-text("Export"), a:has-text("CSV")').count();
    items.push({ action: 'Export button present', result: exportBtn > 0 ? 'yes' : 'not found', ok: exportBtn > 0 });
    return items;
  },

  '/modules/recruitment/index.php': async (page) => {
    const items = [];
    const vacancies = await page.locator('table tbody tr, .vacancy-card').count();
    items.push({ action: 'Vacancies visible', result: `${vacancies} item(s)`, ok: true });
    return items;
  },

  '/modules/training/index.php': async (page) => {
    const items = [];
    const rows = await page.locator('table tbody tr, .training-card').count();
    items.push({ action: 'Training items', result: `${rows} item(s)`, ok: true });
    return items;
  },

  '/modules/performance/index.php': async (page) => {
    const items = [];
    const rows = await page.locator('table tbody tr, .appraisal-card').count();
    items.push({ action: 'Performance records', result: `${rows} item(s)`, ok: true });
    return items;
  },
};

// ─── Helpers ──────────────────────────────────────────────────────────────
function slugify(s) { return s.replace(/[^a-z0-9]/gi,'_').replace(/__+/g,'_').toLowerCase(); }

function detectPhpError(text) {
  const patterns = [
    { re: /Fatal error/i,         kind: 'Fatal error' },
    { re: /Parse error/i,         kind: 'Parse error' },
    { re: /Uncaught /i,           kind: 'Uncaught exception' },
    { re: /Warning:/i,            kind: 'PHP Warning' },
    { re: /Notice:/i,             kind: 'PHP Notice' },
    { re: /Deprecated:/i,         kind: 'PHP Deprecated' },
    { re: /Call to undefined/i,   kind: 'Undefined function' },
    { re: /headers already sent/i,kind: 'Headers already sent' },
  ];
  for (const { re, kind } of patterns) {
    if (re.test(text)) {
      const idx = text.search(re);
      return { kind, snippet: text.substring(Math.max(0,idx-5), idx+180).replace(/\s+/g,' ').trim() };
    }
  }
  return null;
}

function detectMissingLinks(links) {
  return links.filter(href =>
    href && (href.includes('undefined') || href === '#' || href.includes('null'))
  );
}

// ─── Main ─────────────────────────────────────────────────────────────────
(async () => {
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
  const ctx     = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page    = await ctx.newPage();

  // ── Login ──────────────────────────────────────────────────────────────
  console.log('⏳ Logging in as superadmin …');
  await page.goto(`${BASE}/auth/login.php`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="username"]', 'superadmin');
  await page.fill('input[name="password"]', 'Admin@123');
  await page.click('.btn-login');
  await page.waitForLoadState('networkidle');

  const loginScreenshot = path.join(SCREENSHOTS, '00_login.png');
  await page.screenshot({ path: loginScreenshot, fullPage: true });

  if (!page.url().includes('dashboard')) {
    console.error('❌ Login failed. Check credentials or server state.');
    await browser.close();
    process.exit(1);
  }
  console.log('✅ Logged in — at:', page.url());

  const report = {
    generated: new Date().toISOString(),
    base:      BASE,
    login:     { ok: true, screenshotFile: '00_login.png' },
    modules:   [],
    summary:   {},
  };

  const globalStart = Date.now();

  // ── Module crawl ───────────────────────────────────────────────────────
  for (let i = 0; i < MODULES.length; i++) {
    const { path: modPath, label, group } = MODULES[i];
    const jsErrors    = [];
    const networkFail = [];

    const onConsole  = msg => { if (msg.type() === 'error') jsErrors.push(msg.text()); };
    const onResponse = res => {
      if (res.status() >= 400) networkFail.push(`${res.status()} ${res.url()}`);
    };

    page.on('console',  onConsole);
    page.on('response', onResponse);

    const t0 = Date.now();
    let navOk = true;
    try {
      await page.goto(`${BASE}${modPath}`, { waitUntil: 'domcontentloaded', timeout: 20000 });
      await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
    } catch (e) {
      navOk = false;
    }
    const ms = Date.now() - t0;

    page.off('console',  onConsole);
    page.off('response', onResponse);

    // ── Page analysis ────────────────────────────────────────────────────
    const bodyText    = await page.locator('body').textContent().catch(() => '');
    const phpError    = detectPhpError(bodyText);
    const redirectedToLogin = page.url().includes('login.php');
    const pageTitle   = await page.title().catch(() => '');

    // Visible text word-count (rough content indicator)
    const wordCount = bodyText.replace(/\s+/g,' ').trim().split(' ').length;

    // Extract all hrefs for broken-link detection
    const links = await page.locator('a[href]').evaluateAll(els =>
      els.map(a => a.getAttribute('href'))
    ).catch(() => []);
    const badLinks = detectMissingLinks(links);

    // H1 presence
    const h1 = await page.locator('h1').first().textContent().catch(() => null);

    // Count nav items, tables, forms, buttons
    const navItems  = await page.locator('nav a, .sidebar a').count().catch(() => 0);
    const tables    = await page.locator('table').count().catch(() => 0);
    const forms     = await page.locator('form').count().catch(() => 0);
    const buttons   = await page.locator('button, .btn').count().catch(() => 0);
    const alerts    = await page.locator('.alert, .flash-message, .notice').count().catch(() => 0);

    // Screenshot
    const ssFile = `${String(i+1).padStart(2,'0')}_${slugify(label)}.png`;
    await page.screenshot({ path: path.join(SCREENSHOTS, ssFile), fullPage: true }).catch(() => {});

    // ── Interaction probe ─────────────────────────────────────────────────
    let interactions = [];
    if (!redirectedToLogin && !phpError && navOk && PROBES[modPath]) {
      try {
        interactions = await PROBES[modPath](page);
      } catch (e) {
        interactions = [{ action: 'probe error', result: e.message, ok: false }];
      }
    }

    // ── Status determination ───────────────────────────────────────────────
    let status = 'OK';
    if (!navOk)             status = 'NAV_FAIL';
    else if (phpError && (phpError.kind === 'Fatal error' || phpError.kind === 'Parse error' || phpError.kind === 'Uncaught exception')) status = 'FATAL';
    else if (redirectedToLogin) status = 'AUTH_REDIRECT';
    else if (phpError)      status = 'PHP_WARN';
    else if (jsErrors.length > 0) status = 'JS_ERROR';

    const mod = {
      index:        i + 1,
      group,
      label,
      path:         modPath,
      status,
      loadMs:       ms,
      pageTitle,
      h1:           h1 ? h1.trim() : null,
      wordCount,
      navItems,
      tables,
      forms,
      buttons,
      alerts,
      phpError:     phpError || null,
      jsErrors:     jsErrors.slice(0, 5),
      networkFail:  networkFail.slice(0, 5),
      badLinks:     badLinks.slice(0, 5),
      interactions,
      screenshotFile: ssFile,
    };

    report.modules.push(mod);

    // Console output
    const icon = status === 'OK' ? '✅' : status === 'AUTH_REDIRECT' ? '🔒' :
                 status === 'PHP_WARN' ? '⚠️ ' : status === 'JS_ERROR' ? '⚠️ ' : '❌';
    const lbl  = label.padEnd(32);
    console.log(`${icon} [${String(i+1).padStart(2,'0')}] ${lbl} ${String(ms).padStart(5)}ms  ${status}`);
    if (phpError) console.log(`        PHP ${phpError.kind}: ${phpError.snippet.substring(0,120)}`);
    if (jsErrors.length)   jsErrors.slice(0,2).forEach(e => console.log(`        JS: ${e.substring(0,100)}`));
    if (redirectedToLogin) console.log(`        → redirected to login`);
  }

  // ── Summary stats ───────────────────────────────────────────────────────
  const total        = report.modules.length;
  const ok           = report.modules.filter(m => m.status === 'OK').length;
  const fatal        = report.modules.filter(m => m.status === 'FATAL').length;
  const authRedir    = report.modules.filter(m => m.status === 'AUTH_REDIRECT').length;
  const phpWarn      = report.modules.filter(m => m.status === 'PHP_WARN').length;
  const jsError      = report.modules.filter(m => m.status === 'JS_ERROR').length;
  const navFail      = report.modules.filter(m => m.status === 'NAV_FAIL').length;
  const totalMs      = Date.now() - globalStart;
  const avgMs        = Math.round(totalMs / total);
  const slowest      = [...report.modules].sort((a,b) => b.loadMs - a.loadMs).slice(0,5).map(m => ({ label: m.label, ms: m.loadMs }));

  report.summary = { total, ok, fatal, authRedir, phpWarn, jsError, navFail, totalMs, avgMs, slowest };

  // ── Write JSON report ────────────────────────────────────────────────────
  fs.writeFileSync(REPORT_FILE, JSON.stringify(report, null, 2), 'utf8');

  // ── Console summary ──────────────────────────────────────────────────────
  console.log('\n' + '═'.repeat(72));
  console.log('COMPREHENSIVE INSPECTION SUMMARY');
  console.log('═'.repeat(72));
  console.log(`Total modules :  ${total}`);
  console.log(`✅  OK         :  ${ok}`);
  console.log(`❌  Fatal      :  ${fatal}`);
  console.log(`🔒  Auth redir :  ${authRedir}`);
  console.log(`⚠️   PHP warn  :  ${phpWarn}`);
  console.log(`⚠️   JS error  :  ${jsError}`);
  console.log(`💀  Nav fail   :  ${navFail}`);
  console.log(`⏱️   Total time :  ${totalMs}ms  (avg ${avgMs}ms/page)`);
  console.log('\nTop 5 slowest pages:');
  slowest.forEach((s,i) => console.log(`  ${i+1}. ${s.label.padEnd(35)} ${s.ms}ms`));
  console.log('─'.repeat(72));
  console.log(`Report JSON   : ${REPORT_FILE}`);
  console.log(`Screenshots   : ${SCREENSHOTS}`);
  console.log('═'.repeat(72));

  await browser.close();
  process.exit(fatal + navFail > 0 ? 1 : 0);
})();
