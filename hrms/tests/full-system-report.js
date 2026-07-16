const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = 'http://localhost/HR_Komagin';
const SHOTS = path.join(__dirname, 'report-shots');
if (!fs.existsSync(SHOTS)) fs.mkdirSync(SHOTS, { recursive: true });

const USERS = [
  { username: 'superadmin', password: 'Admin@123', role: 'Super Admin' },
  { username: 'hrmanager',  password: 'Admin@123', role: 'HR Manager' },
  { username: 'hrofficer',  password: 'Admin@123', role: 'HR Officer' },
  { username: 'payroll',    password: 'Admin@123', role: 'Payroll Officer' },
];

const ALL_MODULES = [
  // { label, path, section, description }
  { label:'Dashboard',          path:'/dashboard.php',                             section:'Core' },
  { label:'Approvals',          path:'/modules/approvals/index.php',               section:'Core' },
  { label:'Employees',          path:'/modules/employees/index.php',               section:'HR Management' },
  { label:'Add Employee',       path:'/modules/employees/add.php',                 section:'HR Management' },
  { label:'Attendance',         path:'/modules/attendance/index.php',              section:'HR Management' },
  { label:'Kiosk Control',      path:'/modules/attendance/kiosk_manage.php',       section:'HR Management' },
  { label:'Kiosk Screen',       path:'/modules/attendance/kiosk.php',              section:'HR Management' },
  { label:'Timesheets',         path:'/modules/timesheets/index.php',              section:'HR Management' },
  { label:'Overtime',           path:'/modules/timesheets/overtime.php',           section:'HR Management' },
  { label:'Leave',              path:'/modules/leave/index.php',                   section:'HR Management' },
  { label:'Apply Leave',        path:'/modules/leave/apply.php',                   section:'HR Management' },
  { label:'Leave Types',        path:'/modules/leave/types.php',                   section:'HR Management' },
  { label:'Recruitment',        path:'/modules/recruitment/index.php',             section:'HR Management' },
  { label:'Onboarding',         path:'/modules/onboarding/index.php',              section:'HR Management' },
  { label:'Training',           path:'/modules/training/index.php',                section:'HR Management' },
  { label:'Performance',        path:'/modules/performance/index.php',             section:'Operations' },
  { label:'Disciplinary',       path:'/modules/disciplinary/index.php',            section:'Operations' },
  { label:'Assets',             path:'/modules/assets/index.php',                  section:'Operations' },
  { label:'Documents',          path:'/modules/documents/index.php',               section:'Operations' },
  { label:'Doc Templates',      path:'/modules/documents/templates.php',           section:'Operations' },
  { label:'Generate Doc',       path:'/modules/documents/generate.php',            section:'Operations' },
  { label:'Missing Docs',       path:'/modules/documents/missing.php',             section:'Operations' },
  { label:'Reports Hub',        path:'/modules/reports/index.php',                 section:'Operations' },
  { label:'Employee Report',    path:'/modules/reports/employees.php',             section:'Operations' },
  { label:'Timesheet Report',   path:'/modules/reports/timesheets.php',            section:'Operations' },
  { label:'Executive Analytics',path:'/modules/reports/executive.php',             section:'Operations' },
  { label:'Requests Hub',       path:'/modules/hub/index.php',                     section:'Employee Hub' },
  { label:'Payroll Dashboard',  path:'/modules/payroll/index.php',                 section:'Payroll' },
  { label:'Payslips',           path:'/modules/payroll/payslips.php',              section:'Payroll' },
  { label:'Deductions',         path:'/modules/payroll/deductions.php',            section:'Payroll' },
  { label:'Savings',            path:'/modules/payroll/savings.php',               section:'Payroll' },
  { label:'Payroll Reports',    path:'/modules/payroll/reports.php',               section:'Payroll' },
  { label:'Monthly Archive',    path:'/modules/archive/monthly.php',               section:'Archives' },
  { label:'Quarterly Archive',  path:'/modules/archive/quarterly.php',             section:'Archives' },
  { label:'Yearly Archive',     path:'/modules/archive/yearly.php',                section:'Archives' },
  { label:'Users',              path:'/modules/users/index.php',                   section:'Administration' },
  { label:'My Profile',         path:'/modules/users/profile.php',                 section:'Administration' },
  { label:'Roles & Permissions',path:'/modules/roles/index.php',                   section:'Administration' },
  { label:'Settings',           path:'/modules/settings/index.php',                section:'Administration' },
  { label:'Audit Logs',         path:'/modules/audit/index.php',                   section:'Administration' },
];

async function inspectPage(page, module, username) {
  try {
    await page.goto(BASE + module.path, { timeout: 10000, waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(800);

    const url = page.url();
    if (url.includes('login')) {
      return { status: 'DENIED', url, h1: '', tables: 0, forms: 0, buttons: 0, kpis: 0, tabs: 0, pageText: '' };
    }

    const data = await page.evaluate(() => {
      const h1 = document.querySelector('h1, .page-title')?.textContent.trim() || '';
      const tables = document.querySelectorAll('table').length;
      const forms = document.querySelectorAll('form').length;
      const buttons = document.querySelectorAll('button, .btn').length;
      const kpis = document.querySelectorAll('.kpi-card, .ep-kpi').length;
      const tabs = document.querySelectorAll('.tab-item, .ep-nav-link').length;
      const emptyState = !!document.querySelector('.empty-state');
      const hasChart = !!document.querySelector('canvas');
      const modalCount = document.querySelectorAll('.modal-overlay').length;
      const alerts = document.querySelectorAll('.alert').length;

      // Capture key text from cards/tables (first 200 chars)
      const bodyText = (document.querySelector('.main-content, body') || document.body)
        .innerText.replace(/\s+/g, ' ').substring(0, 400);

      return { h1, tables, forms, buttons, kpis, tabs, emptyState, hasChart, modalCount, alerts, bodyText };
    });

    // Screenshot
    const filename = username + '_' + module.label.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.png';
    await page.screenshot({ path: path.join(SHOTS, filename), fullPage: false });

    return { status: 'OK', url, ...data, screenshot: filename };
  } catch(e) {
    return { status: 'ERROR:' + e.message.substring(0, 60), url: '', h1: '', tables: 0, forms: 0, buttons: 0, kpis: 0, tabs: 0 };
  }
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const results = {};

  for (const user of USERS) {
    console.log('\n▶ Logging in as:', user.username, '(' + user.role + ')');
    const page = await browser.newPage();
    await page.setViewportSize({ width: 1366, height: 900 });

    await page.goto(BASE + '/auth/login.php');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    if (page.url().includes('login')) {
      console.log('  LOGIN FAILED'); await page.close(); continue;
    }

    results[user.username] = { role: user.role, modules: [] };

    for (const mod of ALL_MODULES) {
      process.stdout.write('  [' + mod.label + '] ');
      const r = await inspectPage(page, mod, user.username);
      results[user.username].modules.push({ ...mod, result: r });
      console.log(r.status === 'OK' ? '✓' : '✗ ' + r.status);
    }

    await page.close();
  }

  // Write JSON report
  fs.writeFileSync(path.join(SHOTS, 'system-report.json'), JSON.stringify(results, null, 2));
  console.log('\n✅ Report saved. Screenshots in:', SHOTS);

  await browser.close();
})();
