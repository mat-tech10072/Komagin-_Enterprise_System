const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = 'http://localhost/HR_Komagin';
const SHOT = path.join(__dirname, 'sidebar-shots');
if (!fs.existsSync(SHOT)) fs.mkdirSync(SHOT, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true });

  // ── 1. Superadmin — very tall viewport so everything renders without scrolling
  console.log('\n════════════════════════════════════════════════');
  console.log('STEP 1: Superadmin — full sidebar verify');
  console.log('════════════════════════════════════════════════');

  const page = await browser.newPage();
  await page.setViewportSize({ width: 1600, height: 3000 }); // tall enough for all items

  await page.goto(BASE + '/auth/login.php');
  await page.fill('input[name="username"]', 'superadmin');
  await page.fill('input[name="password"]', 'Admin@123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard.php', { timeout: 8000 });
  await page.waitForTimeout(1500);

  // Screenshot entire sidebar element (no clipping)
  const sidebar = page.locator('.sidebar');
  const sidebarBox = await sidebar.boundingBox();
  console.log('Sidebar bounding box:', JSON.stringify(sidebarBox));
  await sidebar.screenshot({ path: path.join(SHOT, '01_superadmin_full_sidebar.png') });
  console.log('✅ Full sidebar screenshot saved (01_superadmin_full_sidebar.png)');

  // Full page screenshot
  await page.screenshot({ path: path.join(SHOT, '02_superadmin_dashboard_fullpage.png'), fullPage: true });
  console.log('✅ Full page dashboard screenshot saved (02_superadmin_dashboard_fullpage.png)');

  // Extract all items with detail
  const navData = await page.evaluate(() => {
    const sections = [];
    document.querySelectorAll('.sidebar-section').forEach(sec => {
      const label = sec.querySelector('.sidebar-section-label');
      const links = sec.querySelectorAll('.sidebar-nav-item');
      sections.push({
        label: label ? label.textContent.trim() : '(no label)',
        items: [...links].map(a => ({
          text: a.textContent.trim().replace(/\s+/g, ' ').substring(0, 40),
          href: a.getAttribute('href'),
          visible: a.offsetParent !== null,
          display: window.getComputedStyle(a).display,
          height: a.getBoundingClientRect().height
        }))
      });
    });
    return sections;
  });

  let totalItems = 0;
  navData.forEach(sec => {
    console.log(`\n  ─── ${sec.label} ───`);
    sec.items.forEach(item => {
      totalItems++;
      const vis = item.visible ? '✓' : '✗ HIDDEN';
      console.log(`    [${vis}] ${item.text} → ${item.href}`);
    });
  });
  console.log(`\n  Total items in DOM: ${totalItems}`);

  // ── 2. Check each nav item actually loads a page (spot check 10 items)
  console.log('\n════════════════════════════════════════════════');
  console.log('STEP 2: Click-through verification');
  console.log('════════════════════════════════════════════════');

  const allLinks = navData.flatMap(s => s.items).filter(i => i.href && i.href !== '#');
  let passed = 0, failed = 0;

  for (const link of allLinks.slice(0, 31)) {
    try {
      const resp = await page.goto(BASE + link.href.replace('http://localhost/HR_Komagin', ''), { timeout: 5000 });
      const status = resp?.status() || 0;
      const url = page.url();
      const redirectedToLogin = url.includes('login');
      if (redirectedToLogin) {
        console.log(`  ✗ ACCESS DENIED → ${link.text} (${link.href})`);
        failed++;
      } else if (status >= 200 && status < 400) {
        console.log(`  ✓ OK [${status}] ${link.text}`);
        passed++;
      } else {
        console.log(`  ✗ HTTP ${status} → ${link.text}`);
        failed++;
      }
    } catch (e) {
      console.log(`  ✗ TIMEOUT → ${link.text}`);
      failed++;
    }
  }

  console.log(`\n  Click-through: ${passed} passed, ${failed} failed`);

  // ── 3. Go back to dashboard and take final confirmation screenshot
  await page.goto(BASE + '/dashboard.php');
  await page.waitForTimeout(1500);
  await page.setViewportSize({ width: 1600, height: 3000 });
  await page.screenshot({ path: path.join(SHOT, '03_final_confirmation.png'), fullPage: true });
  console.log('\n✅ Final confirmation screenshot saved (03_final_confirmation.png)');

  // ── 4. Check CSS — is the sidebar being clipped anywhere?
  const cssCheck = await page.evaluate(() => {
    const sidebar = document.querySelector('.sidebar');
    const style = window.getComputedStyle(sidebar);
    return {
      overflowY: style.overflowY,
      overflowX: style.overflowX,
      height: style.height,
      maxHeight: style.maxHeight,
      position: style.position,
      display: style.display,
      scrollHeight: sidebar.scrollHeight,
      clientHeight: sidebar.clientHeight
    };
  });
  console.log('\n════════════════════════════════════════════════');
  console.log('STEP 3: Sidebar CSS diagnostic');
  console.log('════════════════════════════════════════════════');
  Object.entries(cssCheck).forEach(([k,v]) => console.log(`  ${k}: ${v}`));
  if (cssCheck.scrollHeight > cssCheck.clientHeight) {
    console.log(`\n  ⚠️  SCROLL REQUIRED: content is ${cssCheck.scrollHeight}px, visible area is ${cssCheck.clientHeight}px`);
    console.log('  → Users on smaller screens MUST SCROLL to see all sidebar items.');
  }

  await page.close();
  await browser.close();
  console.log('\nAll screenshots in:', SHOT);
})();
