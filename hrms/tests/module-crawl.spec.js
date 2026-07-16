const { chromium } = require('playwright');

const BASE = 'http://localhost/HR_Komagin';

const MODULES = [
    ['/dashboard.php',                        'Dashboard'],
    ['/modules/employees/index.php',          'Employees'],
    ['/modules/employees/add.php',            'Add Employee'],
    ['/modules/attendance/index.php',         'Attendance'],
    ['/modules/attendance/kiosk.php',         'Kiosk'],
    ['/modules/timesheets/index.php',         'Timesheets'],
    ['/modules/timesheets/overtime.php',      'Overtime'],
    ['/modules/leave/index.php',              'Leave'],
    ['/modules/leave/apply.php',              'Leave Apply'],
    ['/modules/leave/types.php',              'Leave Types'],
    ['/modules/recruitment/index.php',        'Recruitment'],
    ['/modules/onboarding/index.php',         'Onboarding'],
    ['/modules/training/index.php',           'Training'],
    ['/modules/performance/index.php',        'Performance'],
    ['/modules/disciplinary/index.php',       'Disciplinary'],
    ['/modules/assets/index.php',             'Assets'],
    ['/modules/documents/index.php',          'Documents'],
    ['/modules/documents/missing.php',        'Missing Docs'],
    ['/modules/reports/index.php',            'Reports'],
    ['/modules/reports/employees.php',        'Reports: Employees'],
    ['/modules/reports/timesheets.php',       'Reports: Timesheets'],
    ['/modules/archive/monthly.php',          'Archive Monthly'],
    ['/modules/archive/quarterly.php',        'Archive Quarterly'],
    ['/modules/archive/yearly.php',           'Archive Yearly'],
    ['/modules/payroll/index.php',            'Payroll Dashboard'],
    ['/modules/payroll/payslips.php',         'Payroll Payslips'],
    ['/modules/payroll/deductions.php',       'Payroll Deductions'],
    ['/modules/payroll/savings.php',          'Payroll Savings'],
    ['/modules/payroll/reports.php',          'Payroll Reports'],
    ['/modules/hub/index.php',                'Hub'],
    ['/modules/users/index.php',              'Users'],
    ['/modules/users/profile.php',            'My Profile'],
    ['/modules/roles/index.php',              'Roles'],
    ['/modules/settings/index.php',           'Settings'],
    ['/modules/audit/index.php',              'Audit Logs'],
];

(async () => {
    const browser = await chromium.launch({ headless: true });
    const ctx  = await browser.newContext();
    const page = await ctx.newPage();

    // Login as superadmin
    await page.goto(`${BASE}/auth/login.php`);
    await page.fill('input[name="username"]', 'superadmin');
    await page.fill('input[name="password"]', 'Admin@123');
    await page.click('.btn-login');
    await page.waitForLoadState('networkidle');

    if (!page.url().includes('dashboard')) {
        console.error('Login failed — cannot proceed.');
        await browser.close();
        process.exit(1);
    }

    const results = [];
    const t0 = Date.now();

    for (const [path, label] of MODULES) {
        const start = Date.now();
        const errors = [];
        const handler = msg => { if (msg.type() === 'error') errors.push(msg.text()); };
        page.on('console', handler);

        await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
        const ms = Date.now() - start;

        const body = await page.locator('body').textContent().catch(() => '');
        const fatal = body.includes('Fatal error') || body.includes('Uncaught ') || body.includes('Parse error');
        const fatalSnip = fatal
            ? body.replace(/\s+/g, ' ').substring(
                Math.max(0, body.replace(/\s+/g,' ').indexOf('error')-10),
                Math.min(body.length, body.replace(/\s+/g,' ').indexOf('error')+140)
              )
            : null;

        const redirectedToLogin = page.url().includes('login.php');

        page.off('console', handler);

        const status = fatal ? 'FATAL' : redirectedToLogin ? 'LOGIN' : errors.length ? 'WARN' : 'OK';
        results.push({ label, path, status, ms, fatalSnip, errors: errors.slice(0,2) });
    }

    await browser.close();

    const pad = s => s.padEnd(30);
    console.log('\n' + '═'.repeat(72));
    console.log('MODULE CRAWL — superadmin');
    console.log('═'.repeat(72));
    for (const r of results) {
        const icon = r.status === 'OK' ? '✅' : r.status === 'WARN' ? '⚠️ ' : r.status === 'LOGIN' ? '🔒' : '❌';
        console.log(`${icon} ${pad(r.label)} ${String(r.ms).padStart(5)}ms  ${r.status}`);
        if (r.fatalSnip) console.log(`   └─ ${r.fatalSnip.trim().substring(0,150)}`);
        if (r.errors.length) r.errors.forEach(e => console.log(`   └─ JS: ${e.substring(0,120)}`));
    }

    const total = Date.now() - t0;
    const ok    = results.filter(r => r.status === 'OK').length;
    const fail  = results.filter(r => r.status !== 'OK').length;
    console.log('─'.repeat(72));
    console.log(`Total: ${results.length} pages | ✅ ${ok} OK | ❌/⚠️ ${fail} issues | ${total}ms`);
    console.log('═'.repeat(72));

    process.exit(fail > 0 ? 1 : 0);
})();
