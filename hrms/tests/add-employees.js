const { chromium } = require('playwright');

const BASE = 'http://localhost/HR_Komagin';

const EMPLOYEES = [
  {
    first_name:'Taliban', last_name:'David',
    date_of_birth:'1980-04-12', gender:'male', marital_status:'married',
    national_id:'8004121234', email:'taliban.david@komagin.com', phone:'70123401',
    residential_address:'Lot 12 Boroko Drive', city:'Port Moresby', country:'Papua New Guinea',
    department:'Business Development', position:'Business Development Manager',
    employment_type:'full_time', start_date:'2024-01-15', work_location:'Head Office',
    bank_name:'BSP Bank', bank_account_number:'1000234501', bank_branch_code:'001', bank_account_type:'savings',
    emergency_contact_name:'Mary David', emergency_contact_relation:'Spouse', emergency_contact_phone:'70123411',
    nok_name:'Mary David', nok_relation:'Spouse', nok_phone:'70123411',
    basic_salary:'8500',
  },
  {
    first_name:'Charles', last_name:'Richard',
    date_of_birth:'1983-07-22', gender:'male', marital_status:'married',
    national_id:'8307221235', email:'charles.richard@komagin.com', phone:'70123402',
    residential_address:'Section 8 Waigani', city:'Port Moresby', country:'Papua New Guinea',
    department:'Human Resources', position:'HR Manager',
    employment_type:'full_time', start_date:'2023-03-01', work_location:'Head Office',
    bank_name:'BSP Bank', bank_account_number:'1000234502', bank_branch_code:'001', bank_account_type:'cheque',
    emergency_contact_name:'Lisa Richard', emergency_contact_relation:'Spouse', emergency_contact_phone:'70123412',
    nok_name:'Lisa Richard', nok_relation:'Spouse', nok_phone:'70123412',
    basic_salary:'7500',
  },
  {
    first_name:'Benson', last_name:'Barua',
    date_of_birth:'1995-02-14', gender:'male', marital_status:'single',
    national_id:'9502141236', email:'benson.barua@komagin.com', phone:'70123403',
    residential_address:'Konedobu Street', city:'Port Moresby', country:'Papua New Guinea',
    department:'Engineering', position:'Draftsman',
    employment_type:'full_time', start_date:'2025-01-06', work_location:'Site Office',
    bank_name:'Kina Bank', bank_account_number:'2000234503', bank_branch_code:'002', bank_account_type:'savings',
    emergency_contact_name:'Paul Barua', emergency_contact_relation:'Father', emergency_contact_phone:'70123413',
    nok_name:'Paul Barua', nok_relation:'Father', nok_phone:'70123413',
    basic_salary:'4200',
  },
  {
    first_name:'David', last_name:'Max',
    date_of_birth:'1993-11-08', gender:'male', marital_status:'single',
    national_id:'9311081237', email:'david.max@komagin.com', phone:'70123404',
    residential_address:'Morata Estate', city:'Port Moresby', country:'Papua New Guinea',
    department:'Engineering', position:'Draftsman',
    employment_type:'full_time', start_date:'2025-02-03', work_location:'Site Office',
    bank_name:'BSP Bank', bank_account_number:'1000234504', bank_branch_code:'001', bank_account_type:'savings',
    emergency_contact_name:'Jane Max', emergency_contact_relation:'Mother', emergency_contact_phone:'70123414',
    nok_name:'Jane Max', nok_relation:'Mother', nok_phone:'70123414',
    basic_salary:'4200',
  },
  {
    first_name:'Issac', last_name:'Jim',
    date_of_birth:'1988-05-30', gender:'male', marital_status:'married',
    national_id:'8805301238', email:'issac.jim@komagin.com', phone:'70123405',
    residential_address:'Hohola Unit 7', city:'Port Moresby', country:'Papua New Guinea',
    department:'Engineering', position:'Civil Engineer',
    employment_type:'full_time', start_date:'2022-08-15', work_location:'Site Office',
    bank_name:'ANZ Bank', bank_account_number:'3000234505', bank_branch_code:'003', bank_account_type:'cheque',
    emergency_contact_name:'Ruth Jim', emergency_contact_relation:'Spouse', emergency_contact_phone:'70123415',
    nok_name:'Ruth Jim', nok_relation:'Spouse', nok_phone:'70123415',
    basic_salary:'6500',
  },
  {
    first_name:'Joe', last_name:'Tengdui',
    date_of_birth:'1979-09-18', gender:'male', marital_status:'married',
    national_id:'7909181239', email:'joe.tengdui@komagin.com', phone:'70123406',
    residential_address:'Gerehu Stage 5', city:'Port Moresby', country:'Papua New Guinea',
    department:'Engineering', position:'Lead Civil Engineer',
    employment_type:'full_time', start_date:'2020-04-01', work_location:'Site Office',
    bank_name:'BSP Bank', bank_account_number:'1000234506', bank_branch_code:'001', bank_account_type:'cheque',
    emergency_contact_name:'Agnes Tengdui', emergency_contact_relation:'Spouse', emergency_contact_phone:'70123416',
    nok_name:'Agnes Tengdui', nok_relation:'Spouse', nok_phone:'70123416',
    basic_salary:'9000',
  },
  {
    first_name:'Joshua', last_name:'Paul',
    date_of_birth:'1991-03-25', gender:'male', marital_status:'married',
    national_id:'9103251240', email:'joshua.paul@komagin.com', phone:'70123407',
    residential_address:'Tokarara Block 18', city:'Port Moresby', country:'Papua New Guinea',
    department:'Survey', position:'Surveyor',
    employment_type:'full_time', start_date:'2023-06-12', work_location:'Site Office',
    bank_name:'Kina Bank', bank_account_number:'2000234507', bank_branch_code:'002', bank_account_type:'savings',
    emergency_contact_name:'Sarah Paul', emergency_contact_relation:'Spouse', emergency_contact_phone:'70123417',
    nok_name:'Sarah Paul', nok_relation:'Spouse', nok_phone:'70123417',
    basic_salary:'5800',
  },
  {
    first_name:'Frank', last_name:'David',
    date_of_birth:'1982-12-03', gender:'male', marital_status:'married',
    national_id:'8212031241', email:'frank.david@komagin.com', phone:'70123408',
    residential_address:'Six Mile Compound', city:'Port Moresby', country:'Papua New Guinea',
    department:'Survey', position:'Lead Surveyor',
    employment_type:'full_time', start_date:'2019-10-07', work_location:'Site Office',
    bank_name:'BSP Bank', bank_account_number:'1000234508', bank_branch_code:'001', bank_account_type:'cheque',
    emergency_contact_name:'Grace David', emergency_contact_relation:'Spouse', emergency_contact_phone:'70123418',
    nok_name:'Grace David', nok_relation:'Spouse', nok_phone:'70123418',
    basic_salary:'8200',
  },
  {
    first_name:'Mathew', last_name:'Jonathan',
    date_of_birth:'1990-08-17', gender:'male', marital_status:'single',
    national_id:'9008171242', email:'mathew.jonathan@komagin.com', phone:'70123409',
    residential_address:'Waigani Drive NCD', city:'Port Moresby', country:'Papua New Guinea',
    department:'Information Technology', position:'IT Officer',
    employment_type:'full_time', start_date:'2024-03-04', work_location:'Head Office',
    bank_name:'ANZ Bank', bank_account_number:'3000234509', bank_branch_code:'003', bank_account_type:'savings',
    emergency_contact_name:'Peter Jonathan', emergency_contact_relation:'Father', emergency_contact_phone:'70123419',
    nok_name:'Peter Jonathan', nok_relation:'Father', nok_phone:'70123419',
    basic_salary:'5500',
  },
  {
    first_name:'Kohn', last_name:'Digan',
    date_of_birth:'1985-06-20', gender:'male', marital_status:'married',
    national_id:'8506201243', email:'kohn.digan@komagin.com', phone:'70123410',
    residential_address:'Badili Street 9', city:'Port Moresby', country:'Papua New Guinea',
    department:'Operations', position:'Asset Manager',
    employment_type:'full_time', start_date:'2021-11-22', work_location:'Head Office',
    bank_name:'BSP Bank', bank_account_number:'1000234510', bank_branch_code:'001', bank_account_type:'cheque',
    emergency_contact_name:'Karen Digan', emergency_contact_relation:'Spouse', emergency_contact_phone:'70123420',
    nok_name:'Karen Digan', nok_relation:'Spouse', nok_phone:'70123420',
    basic_salary:'7000',
  },
  {
    first_name:'Norman', last_name:'Simon',
    date_of_birth:'1987-01-11', gender:'male', marital_status:'married',
    national_id:'8701111244', email:'norman.simon@komagin.com', phone:'70223401',
    residential_address:'Ela Beach Flats', city:'Port Moresby', country:'Papua New Guinea',
    department:'Finance', position:'Accounts Officer',
    employment_type:'full_time', start_date:'2022-02-14', work_location:'Head Office',
    bank_name:'Kina Bank', bank_account_number:'2000234511', bank_branch_code:'002', bank_account_type:'savings',
    emergency_contact_name:'Helen Simon', emergency_contact_relation:'Spouse', emergency_contact_phone:'70223411',
    nok_name:'Helen Simon', nok_relation:'Spouse', nok_phone:'70223411',
    basic_salary:'5000',
  },
  {
    first_name:'David', last_name:'Komane',
    date_of_birth:'1994-04-29', gender:'male', marital_status:'single',
    national_id:'9404291245', email:'david.komane@komagin.com', phone:'70223402',
    residential_address:'Gordon Estate Block 3', city:'Port Moresby', country:'Papua New Guinea',
    department:'Administration', position:'Admin Officer',
    employment_type:'full_time', start_date:'2025-03-10', work_location:'Head Office',
    bank_name:'BSP Bank', bank_account_number:'1000234512', bank_branch_code:'001', bank_account_type:'savings',
    emergency_contact_name:'Alice Komane', emergency_contact_relation:'Mother', emergency_contact_phone:'70223412',
    nok_name:'Alice Komane', nok_relation:'Mother', nok_phone:'70223412',
    basic_salary:'3800',
  },
  {
    first_name:'Zianna', last_name:'Koma',
    date_of_birth:'1975-10-06', gender:'female', marital_status:'married',
    national_id:'7510061246', email:'zianna.koma@komagin.com', phone:'70223403',
    residential_address:'Touaguba Hill Residence', city:'Port Moresby', country:'Papua New Guinea',
    department:'Executive', position:'General Manager',
    employment_type:'full_time', start_date:'2018-06-01', work_location:'Head Office',
    bank_name:'ANZ Bank', bank_account_number:'3000234513', bank_branch_code:'003', bank_account_type:'cheque',
    emergency_contact_name:'Thomas Koma', emergency_contact_relation:'Spouse', emergency_contact_phone:'70223413',
    nok_name:'Thomas Koma', nok_relation:'Spouse', nok_phone:'70223413',
    basic_salary:'15000',
  },
];

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page    = await browser.newPage();
  await page.setViewportSize({ width: 1366, height: 900 });

  // Log in as HR Manager
  await page.goto(BASE + '/auth/login.php');
  await page.fill('input[name="username"]', 'hrmanager');
  await page.fill('input[name="password"]', 'Admin@123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard.php', { timeout: 8000 });
  console.log('✅ Logged in as HR Manager\n');

  // Get position and department IDs from the add form
  await page.goto(BASE + '/modules/employees/add.php');
  await page.waitForTimeout(800);

  const deptOptions = await page.evaluate(() => {
    const sel = document.querySelector('select[name="department_id"]');
    return [...sel.options].reduce((m, o) => { if (o.value) m[o.textContent.trim()] = o.value; return m; }, {});
  });

  let added = 0, failed = 0;

  for (const emp of EMPLOYEES) {
    process.stdout.write(`Adding ${emp.first_name} ${emp.last_name} (${emp.position}) ... `);

    await page.goto(BASE + '/modules/employees/add.php');
    await page.waitForTimeout(400);

    // Fill form fields
    await page.fill('input[name="first_name"]', emp.first_name);
    await page.fill('input[name="last_name"]',  emp.last_name);
    if (emp.date_of_birth) await page.fill('input[name="date_of_birth"]', emp.date_of_birth);
    await page.selectOption('select[name="gender"]',         emp.gender);
    await page.selectOption('select[name="marital_status"]', emp.marital_status);
    if (emp.national_id) await page.fill('input[name="national_id"]', emp.national_id);
    await page.fill('input[name="email"]',  emp.email);
    await page.fill('input[name="phone"]',  emp.phone);
    await page.fill('[name="residential_address"]', emp.residential_address);
    await page.fill('input[name="city"]',    emp.city);
    await page.fill('input[name="country"]', emp.country);

    // Department → then position
    const deptId = deptOptions[emp.department];
    if (deptId) {
      await page.selectOption('select[name="department_id"]', deptId);
      await page.waitForTimeout(300);
      // Wait for position dropdown to reload
      await page.waitForSelector('select[name="position_id"]');
      const posOpts = await page.evaluate(() => {
        const sel = document.querySelector('select[name="position_id"]');
        return [...sel.options].reduce((m, o) => { if (o.value) m[o.textContent.trim()] = o.value; return m; }, {});
      });
      const posId = posOpts[emp.position];
      if (posId) await page.selectOption('select[name="position_id"]', posId);
    }

    await page.selectOption('select[name="employment_type"]', emp.employment_type);
    if (emp.start_date) await page.fill('input[name="start_date"]', emp.start_date);
    if (emp.work_location) await page.fill('input[name="work_location"]', emp.work_location);

    // Bank
    if (emp.bank_name)           await page.fill('input[name="bank_name"]',           emp.bank_name);
    if (emp.bank_account_number) await page.fill('input[name="bank_account_number"]', emp.bank_account_number);
    if (emp.bank_branch_code)    await page.fill('input[name="bank_branch_code"]',    emp.bank_branch_code);
    if (emp.bank_account_type)   await page.selectOption('select[name="bank_account_type"]', emp.bank_account_type).catch(()=>{});

    // Emergency
    if (emp.emergency_contact_name)     await page.fill('input[name="emergency_contact_name"]',     emp.emergency_contact_name);
    if (emp.emergency_contact_relation) await page.fill('input[name="emergency_contact_relation"]',  emp.emergency_contact_relation);
    if (emp.emergency_contact_phone)    await page.fill('input[name="emergency_contact_phone"]',    emp.emergency_contact_phone);

    // NOK
    if (emp.nok_name)     await page.fill('input[name="nok_name"]',     emp.nok_name);
    if (emp.nok_relation) await page.fill('input[name="nok_relation"]',  emp.nok_relation);
    if (emp.nok_phone)    await page.fill('input[name="nok_phone"]',    emp.nok_phone);

    // Salary (if visible)
    try { await page.fill('input[name="basic_salary"]', emp.basic_salary); } catch(e){}

    // Submit
    await page.click('button[type="submit"]');
    await page.waitForTimeout(1500);

    const url    = page.url();
    const flash  = await page.locator('.alert-success, .alert-danger').first().textContent().catch(() => '');
    const empNum = flash.match(/KOM-EMP-\d+-\d+/)?.[0] || '';

    if (url.includes('index') || url.includes('view') || flash.includes('successfully') || flash.includes('added')) {
      console.log('✅', empNum || 'OK');
      added++;
    } else {
      const err = await page.locator('.alert-danger, .text-danger').first().textContent().catch(() => 'unknown error');
      console.log('❌ FAILED —', err.trim().substring(0, 80));
      failed++;
    }
  }

  console.log(`\n════════════════════════════════`);
  console.log(`Added: ${added}  Failed: ${failed}`);
  console.log(`════════════════════════════════`);

  await browser.close();
})();
