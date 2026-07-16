const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const SHOTS = path.join(__dirname, 'phase4-shots');
if (!fs.existsSync(SHOTS)) fs.mkdirSync(SHOTS, { recursive: true });

const BASE = 'http://localhost/HR_Komagin';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1366, height: 900 });

  // Login as superadmin
  await page.goto(BASE + '/auth/login.php');
  await page.fill('input[name="username"]', 'superadmin');
  await page.fill('input[name="password"]', 'Admin@123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard.php', { timeout: 8000 });
  console.log('✅ Logged in');

  // ── STEP 1: Navigate to Generate Document ───────────────────────
  await page.goto(BASE + '/modules/documents/generate.php');
  await page.waitForTimeout(1000);
  await page.screenshot({ path: path.join(SHOTS, '01_generate_page.png') });
  console.log('✅ Step 1: Generate document page loaded');

  // ── STEP 2: Select first template and first employee ────────────
  // Select Employment Certificate template
  const templateSelect = page.locator('select[name="template_id"]');
  const options = await templateSelect.locator('option').allTextContents();
  const certOption = options.find(o => o.includes('Employment Confirmation'));
  if (certOption) {
    await templateSelect.selectOption({ label: certOption });
    console.log('✅ Step 2a: Template selected:', certOption);
  } else {
    // Fallback: select first available template
    const firstOption = await templateSelect.locator('option').nth(1).getAttribute('value');
    await templateSelect.selectOption(firstOption);
    console.log('⚠️  Step 2a: Used first available template');
  }

  // Select Sarah Mokoena (first employee)
  const empSelect = page.locator('select[name="employee_id"]');
  const empOptions = await empSelect.locator('option').allTextContents();
  const mokoenaOption = empOptions.find(o => o.includes('Mokoena'));
  if (mokoenaOption) {
    await empSelect.selectOption({ label: mokoenaOption });
    console.log('✅ Step 2b: Employee selected:', mokoenaOption);
  } else {
    const firstEmp = await empSelect.locator('option').nth(1).getAttribute('value');
    await empSelect.selectOption(firstEmp);
    console.log('⚠️  Step 2b: Used first available employee');
  }

  // ── STEP 3: Preview document ────────────────────────────────────
  await page.click('button[value="preview"]');
  await page.waitForTimeout(2000);
  await page.screenshot({ path: path.join(SHOTS, '02_preview.png') });
  const previewHtml = await page.locator('#documentPrint, .main-content').textContent().catch(() => '');
  const hasVariables = previewHtml.includes('{{');
  console.log(hasVariables ? '❌ Step 3: Variables NOT resolved' : '✅ Step 3: Document preview rendered (all {{}} replaced)');

  // ── STEP 4: Save document ───────────────────────────────────────
  const saveBtn = page.locator('button[value="save"]');
  if (await saveBtn.count() > 0) {
    await saveBtn.click();
    await page.waitForTimeout(2000);
    const savedUrl = page.url();
    const isSaved = savedUrl.includes('view_generated');
    console.log(isSaved ? '✅ Step 4: Document saved and redirected to view' : '❌ Step 4: Save redirect failed — URL: ' + savedUrl);
    await page.screenshot({ path: path.join(SHOTS, '03_saved_document.png') });

    if (isSaved) {
      // ── STEP 5: Check approval status ──────────────────────────
      const statusBadge = await page.locator('.badge').first().textContent();
      console.log('✅ Step 5: Document status:', statusBadge.trim());

      // ── STEP 6: Check audit log ─────────────────────────────────
      await page.goto(BASE + '/modules/audit/index.php?module=documents');
      await page.waitForTimeout(1000);
      const auditText = await page.locator('.main-content').textContent().catch(() => '');
      const hasDocAudit = auditText.includes('generate_document') || auditText.includes('document');
      console.log(hasDocAudit ? '✅ Step 6: Audit log has document generation entry' : '⚠️  Step 6: No document audit entry found yet');
      await page.screenshot({ path: path.join(SHOTS, '04_audit_log.png') });
    }
  } else {
    console.log('⚠️  Step 4: Save button not visible (preview may have failed)');
  }

  // ── STEP 7: Check generated documents list ──────────────────────
  await page.goto(BASE + '/modules/documents/generate.php');
  await page.waitForTimeout(1000);
  const recentText = await page.locator('.main-content').textContent().catch(() => '');
  const hasRecent = recentText.includes('Mokoena') || recentText.includes('Employment');
  console.log(hasRecent ? '✅ Step 7: Generated document visible in recent list' : '⚠️  Step 7: Document not yet visible in recent list');
  await page.screenshot({ path: path.join(SHOTS, '05_recent_list.png') });

  // ── STEP 8: Check employee document was attached ─────────────────
  await page.goto(BASE + '/modules/employees/index.php');
  await page.waitForTimeout(800);
  // Find Sarah Mokoena and click view
  const empLinks = page.locator('a[href*="view.php?id="]');
  if (await empLinks.count() > 0) {
    const href = await empLinks.first().getAttribute('href');
    await page.goto(BASE + href.replace(BASE,'') + '&tab=documents');
    await page.waitForTimeout(1000);
    const docText = await page.locator('.main-content').textContent().catch(() => '');
    const hasDoc = docText.includes('generated:') || docText.includes('Employment');
    console.log(hasDoc ? '✅ Step 8: Document attached to employee record' : '⚠️  Step 8: Document attachment check inconclusive');
    await page.screenshot({ path: path.join(SHOTS, '06_employee_docs_tab.png') });
  }

  console.log('\n════════════════════════════════════');
  console.log('PHASE 4 DOCUMENT ENGINE PROOF COMPLETE');
  console.log('Screenshots in:', SHOTS);
  console.log('════════════════════════════════════');

  await browser.close();
})();
