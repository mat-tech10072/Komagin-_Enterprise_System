const { chromium } = require('playwright');

const BASE = 'http://localhost/HR_Komagin';

async function testLogin(browser, label, username, password, expectedPath) {
    const ctx  = await browser.newContext();
    const page = await ctx.newPage();

    console.log(`\n─────────────────────────────────────────`);
    console.log(`Testing: ${label}`);
    console.log(`  Username : ${username}`);
    console.log(`  Expected : ${BASE}${expectedPath}`);

    await page.goto(`${BASE}/auth/login.php`);
    await page.waitForSelector('input[name="username"]');

    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"], .btn-login');

    // Wait for navigation to settle
    await page.waitForLoadState('networkidle');

    const actual = page.url();
    const passed = actual === `${BASE}${expectedPath}` || actual.startsWith(`${BASE}${expectedPath}`);

    console.log(`  Actual   : ${actual}`);
    console.log(`  Result   : ${passed ? '✅  PASS' : '❌  FAIL'}`);

    if (!passed) {
        // Grab any visible error text
        const errorText = await page.locator('.alert-error, .alert-danger').textContent().catch(() => '');
        if (errorText.trim()) console.log(`  Error msg: ${errorText.trim()}`);
        // Also grab page title to give a clue
        const title = await page.title();
        console.log(`  Page title: ${title}`);
    }

    await ctx.close();
    return { label, username, expected: `${BASE}${expectedPath}`, actual, passed };
}

(async () => {
    const browser = await chromium.launch({ headless: true });

    const results = [];

    results.push(await testLogin(
        browser,
        'Superadmin',
        'superadmin',
        'Admin@123',
        '/dashboard.php'
    ));

    results.push(await testLogin(
        browser,
        'Payroll Officer',
        'payroll',
        'Admin@123',
        '/modules/payroll/index.php'
    ));

    await browser.close();

    console.log('\n═════════════════════════════════════════');
    console.log('SUMMARY');
    console.log('═════════════════════════════════════════');
    for (const r of results) {
        console.log(`  ${r.passed ? '✅' : '❌'}  ${r.label.padEnd(20)} → ${r.actual}`);
    }

    const allPassed = results.every(r => r.passed);
    console.log(`\nOverall: ${allPassed ? '✅  ALL PASS' : '❌  SOME FAILURES'}`);
    process.exit(allPassed ? 0 : 1);
})();
