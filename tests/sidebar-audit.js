const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = 'http://localhost/HR_Komagin';
const SHOT = path.join(__dirname, 'sidebar-shots');
if (!fs.existsSync(SHOT)) fs.mkdirSync(SHOT, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1400, height: 900 });

  // --- Login ---
  await page.goto(BASE + '/auth/login.php');
  await page.fill('input[name="username"]', 'superadmin');
  await page.fill('input[name="password"]', 'Admin@123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard.php', { timeout: 10000 });
  console.log('✅ Logged in');

  // --- Full page screenshot ---
  await page.waitForTimeout(1500);
  await page.screenshot({ path: path.join(SHOT, 'full-dashboard.png'), fullPage: false });
  console.log('📸 Full dashboard screenshot saved');

  // --- Extract all sidebar nav items ---
  const navItems = await page.evaluate(() => {
    const items = [];

    // Section labels
    const allElements = document.querySelectorAll('.sidebar-section');
    allElements.forEach(section => {
      const label = section.querySelector('.sidebar-section-label');
      const links = section.querySelectorAll('.sidebar-nav-item');
      const sectionName = label ? label.textContent.trim() : '(no label)';

      links.forEach(link => {
        const text = link.textContent.trim().replace(/\s+/g, ' ');
        const href = link.getAttribute('href');
        const isSubItem = link.style.paddingLeft && parseInt(link.style.paddingLeft) > 20;
        items.push({
          section: sectionName,
          text: text,
          href: href,
          isSubItem: isSubItem,
          isActive: link.classList.contains('active')
        });
      });
    });
    return items;
  });

  console.log('\n════════════════════════════════════════════════════');
  console.log('SIDEBAR AUDIT — What is actually visible on screen');
  console.log('════════════════════════════════════════════════════');

  let currentSection = '';
  let itemCount = 0;
  navItems.forEach(item => {
    if (item.section !== currentSection) {
      currentSection = item.section;
      console.log(`\n  ── ${currentSection} ──`);
    }
    const prefix = item.isSubItem ? '    ↳' : '  •';
    const active = item.isActive ? ' [ACTIVE]' : '';
    console.log(`${prefix} ${item.text}${active}`);
    itemCount++;
  });

  console.log(`\n════════════════════════════════════════════════════`);
  console.log(`Total sidebar nav items rendered: ${itemCount}`);

  // --- Sidebar-only screenshot ---
  const sidebar = page.locator('.sidebar');
  await sidebar.screenshot({ path: path.join(SHOT, 'sidebar-only.png') });
  console.log(`📸 Sidebar-only screenshot saved`);

  // --- Check which expected items are MISSING ---
  const expected = [
    'Dashboard', 'Approvals',
    'Employees', 'Attendance', 'Kiosk Control', 'Timesheets', 'Leave',
    'Recruitment', 'Onboarding', 'Training',
    'Payroll', 'Payslips', 'Deductions', 'Savings', 'Payroll Reports',
    'Requests Hub',
    'Assets', 'Performance', 'Disciplinary', 'Documents', 'Templates', 'Generate',
    'Reports', 'Executive Analytics',
    'Monthly Archive', 'Quarterly Archive', 'Yearly Archive',
    'Users', 'Roles', 'Settings', 'Audit Logs'
  ];

  const rendered = navItems.map(i => i.text.replace(/\s*\d+\s*$/, '').trim());

  console.log('\n════════════════════════════════════════════════════');
  console.log('MISSING ITEMS (expected but not found in sidebar):');
  console.log('════════════════════════════════════════════════════');
  const missing = [];
  expected.forEach(e => {
    const found = rendered.some(r => r.includes(e));
    if (!found) {
      missing.push(e);
      console.log(`  ✗ MISSING: ${e}`);
    }
  });
  if (missing.length === 0) console.log('  None — all expected items are present.');

  await browser.close();
  console.log('\nScreenshots saved to:', SHOT);
})();
