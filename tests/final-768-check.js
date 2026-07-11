const { chromium } = require('playwright');
const path = require('path');
const SHOT = path.join(__dirname, 'sidebar-shots');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1366, height: 768 });

  await page.goto('http://localhost/HR_Komagin/auth/login.php');
  await page.fill('input[name="username"]', 'superadmin');
  await page.fill('input[name="password"]', 'Admin@123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard.php', { timeout: 8000 });
  await page.waitForTimeout(1500);

  const stats = await page.evaluate(() => {
    const sb = document.querySelector('.sidebar');
    const items = [...document.querySelectorAll('.sidebar-nav-item')];
    let visibleInViewport = 0;
    items.forEach(a => {
      const r = a.getBoundingClientRect();
      if (r.top >= 0 && r.bottom <= window.innerHeight) visibleInViewport++;
    });
    return {
      sidebarScrollH: sb.scrollHeight,
      sidebarClientH: sb.clientHeight,
      totalItems: items.length,
      visibleInViewport,
      canScroll: sb.scrollHeight > sb.clientHeight,
      firstHidden: items.find(a => a.getBoundingClientRect().bottom > window.innerHeight)?.textContent.trim()
    };
  });

  console.log('\n═══════════════════════════════════════════════');
  console.log(' SIDEBAR FINAL CHECK — 1366×768 (laptop screen)');
  console.log('═══════════════════════════════════════════════');
  console.log(' Content height :', stats.sidebarScrollH + 'px');
  console.log(' Visible height :', stats.sidebarClientH + 'px');
  console.log(' Can scroll     :', stats.canScroll ? 'YES ✅' : 'NO ✗');
  console.log(' Total items    :', stats.totalItems);
  console.log(' Visible NOW    :', stats.visibleInViewport, '(without scrolling)');
  console.log(' First off-screen:', stats.firstHidden || 'none — all fit!');
  console.log('');

  // Screenshot at 768
  await page.locator('.sidebar').screenshot({ path: path.join(SHOT, 'FINAL_768_before_scroll.png') });
  console.log(' Screenshot 1: FINAL_768_before_scroll.png (no scroll)');

  // Scroll sidebar to bottom and screenshot
  await page.evaluate(() => { document.querySelector('.sidebar').scrollTop = 9999; });
  await page.waitForTimeout(500);
  await page.locator('.sidebar').screenshot({ path: path.join(SHOT, 'FINAL_768_after_scroll.png') });
  console.log(' Screenshot 2: FINAL_768_after_scroll.png (scrolled to bottom)');

  // Full-height screenshot (set viewport to content height)
  await page.setViewportSize({ width: 1366, height: stats.sidebarScrollH + 20 });
  await page.evaluate(() => { document.querySelector('.sidebar').scrollTop = 0; });
  await page.waitForTimeout(500);
  await page.locator('.sidebar').screenshot({ path: path.join(SHOT, 'FINAL_fullheight.png') });
  console.log(' Screenshot 3: FINAL_fullheight.png (all items, full height)');

  console.log('\n All screenshots in:', SHOT);
  await browser.close();
})();
