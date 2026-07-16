const { chromium } = require('playwright');
(async () => {
  const b = await chromium.launch({ headless: true });
  const p = await b.newPage();
  await p.setViewportSize({ width: 1366, height: 768 });
  await p.goto('http://localhost/HR_Komagin/auth/login.php');
  await p.fill('input[name="username"]', 'superadmin');
  await p.fill('input[name="password"]', 'Admin@123');
  await p.click('button[type="submit"]');
  await p.waitForURL('**/dashboard.php', { timeout: 8000 });
  await p.waitForTimeout(1000);
  const r = await p.evaluate(() => {
    const s = document.querySelector('.sidebar');
    const items = [...document.querySelectorAll('.sidebar-nav-item')];
    let visible = 0;
    items.forEach(a => { const t = a.getBoundingClientRect().top; if (t < window.innerHeight && t > 0) visible++; });
    return {
      sidebarScrollH: s.scrollHeight,
      sidebarClientH: s.clientHeight,
      totalItems: items.length,
      visibleWithoutScroll: visible,
      firstHiddenItem: items.find(a => a.getBoundingClientRect().top >= window.innerHeight)?.textContent.trim()
    };
  });
  console.log('Measurement at 1366×768:');
  console.log('  Sidebar scrollHeight (content):', r.sidebarScrollH + 'px');
  console.log('  Sidebar clientHeight (visible):', r.sidebarClientH + 'px');
  console.log('  Total items:', r.totalItems);
  console.log('  Visible WITHOUT scrolling:', r.visibleWithoutScroll);
  console.log('  First item hidden below fold:', r.firstHiddenItem);
  console.log('  Overflow needed:', r.sidebarScrollH - r.sidebarClientH, 'px');
  await b.close();
})();
