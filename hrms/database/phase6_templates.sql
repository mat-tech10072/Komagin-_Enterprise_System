-- ============================================================
-- PHASE 6 — DOCUMENT TEMPLATE LIBRARY
-- 50+ templates across all 10 categories
-- ============================================================
USE komagin_hr;

-- Helper: get category IDs
SET @cat_emp   = (SELECT id FROM doc_categories WHERE slug='employment_letters');
SET @cat_hr    = (SELECT id FROM doc_categories WHERE slug='hr_letters');
SET @cat_cert  = (SELECT id FROM doc_categories WHERE slug='certificates');
SET @cat_pay   = (SELECT id FROM doc_categories WHERE slug='payroll_documents');
SET @cat_leave = (SELECT id FROM doc_categories WHERE slug='leave_documents');
SET @cat_disc  = (SELECT id FROM doc_categories WHERE slug='disciplinary');
SET @cat_comp  = (SELECT id FROM doc_categories WHERE slug='compliance');
SET @cat_onb   = (SELECT id FROM doc_categories WHERE slug='onboarding');
SET @cat_exit  = (SELECT id FROM doc_categories WHERE slug='exit_management');
SET @cat_gen   = (SELECT id FROM doc_categories WHERE slug='general');

-- ============================================================
-- EMPLOYMENT LETTERS (10 templates)
-- ============================================================
INSERT INTO doc_templates (category_id,title,slug,description,body_html,variables_used,version,created_by) VALUES

(@cat_emp,'Employment Offer Letter','offer_letter','Standard employment offer letter to new candidates',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;">
<strong>{{company.name}}</strong><br>{{company.address}}<br>{{company.phone}}<br>{{company.email}}
</div>
<p style="margin-bottom:24px;">{{date.today}}</p>
<p><strong>{{employee.full_name}}</strong></p>
<p style="margin-bottom:24px;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1.1rem;margin-bottom:16px;text-decoration:underline;">LETTER OF APPOINTMENT</h2>
<p>We are pleased to offer you a position as <strong>{{employee.position}}</strong> in our <strong>{{employee.department}}</strong> Department at {{company.name}}.</p>
<p style="margin-top:16px;"><strong>Terms and Conditions:</strong></p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
<tr><td style="padding:6px 0;width:220px;color:#555;">Start Date:</td><td><strong>{{employee.start_date}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Employment Type:</td><td><strong>{{employee.type}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Department:</td><td><strong>{{employee.department}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Position:</td><td><strong>{{employee.position}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Work Location:</td><td><strong>{{employee.work_location}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Gross Monthly Salary:</td><td><strong>{{employee.salary_gross}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Reporting To:</td><td><strong>{{employee.supervisor}}</strong></td></tr>
</table>
<p>This offer is contingent upon the successful completion of all pre-employment checks including reference verification and document verification.</p>
<p style="margin-top:16px;">Please sign and return a copy of this letter by <strong>{{date.today}}</strong> to confirm your acceptance.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">
I accept this offer: <strong>{{employee.full_name}}</strong><br>Date: _______________
</div>
</div>',
'["company.name","company.address","company.phone","company.email","employee.full_name","employee.preferred_name","employee.position","employee.department","employee.start_date","employee.type","employee.work_location","employee.salary_gross","employee.supervisor","date.today"]',
1,1),

(@cat_emp,'Appointment Confirmation Letter','appointment_confirmation','Confirms permanent appointment after probation',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>{{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">CONFIRMATION OF PERMANENT APPOINTMENT</h2>
<p style="margin-top:16px;">We are pleased to confirm your permanent appointment as <strong>{{employee.position}}</strong> in the <strong>{{employee.department}}</strong> Department with effect from {{date.today}}.</p>
<p style="margin-top:16px;">Having successfully completed your probationary period, your employment terms remain as per your original letter of appointment. Your revised employment status is <strong>Permanent Employee</strong>.</p>
<p style="margin-top:16px;">We congratulate you on this achievement and look forward to your continued contribution to {{company.name}}.</p>
<p style="margin-top:32px;">Yours faithfully,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.department","date.today"]',1,1),

(@cat_emp,'Contract Renewal Letter','contract_renewal','Renews a fixed-term employment contract',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">CONTRACT RENEWAL — {{employee.position}}</h2>
<p style="margin-top:16px;">This letter serves to inform you that your fixed-term contract as <strong>{{employee.position}}</strong> has been renewed effective <strong>{{date.today}}</strong>.</p>
<p>Your revised terms are as follows:</p>
<table style="width:100%;border-collapse:collapse;margin:12px 0;">
<tr><td style="padding:5px 0;width:200px;color:#555;">Position:</td><td><strong>{{employee.position}}</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Department:</td><td><strong>{{employee.department}}</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Gross Salary:</td><td><strong>{{employee.salary_gross}}</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Renewal Date:</td><td><strong>{{date.today}}</strong></td></tr>
</table>
<p>All other terms and conditions of your original contract remain unchanged. Please sign below to acknowledge receipt and acceptance.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
<div style="margin-top:40px;border-top:1px solid #333;width:220px;padding-top:8px;font-size:0.85rem;">Signature: _______________<br>Date: _______________</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.department","employee.salary_gross","date.today"]',1,1),

(@cat_emp,'Probation Extension Letter','probation_extension','Extends an employee probationary period',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">EXTENSION OF PROBATIONARY PERIOD</h2>
<p style="margin-top:16px;">Please be advised that your probationary period as <strong>{{employee.position}}</strong> has been extended with effect from <strong>{{date.today}}</strong>.</p>
<p style="margin-top:12px;">This extension is to allow adequate time to assess your performance and suitability for permanent appointment. During this extended period, you will receive additional support and guidance from {{employee.supervisor}}.</p>
<p style="margin-top:12px;">A formal review will be conducted at the end of the extension period. We trust you will use this time to demonstrate your full capabilities.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.supervisor","date.today"]',1,1),

(@cat_emp,'Promotion Letter','promotion_letter','Formal promotion announcement letter',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">LETTER OF PROMOTION</h2>
<p style="margin-top:16px;">It is with great pleasure that we inform you of your promotion to the position of <strong>{{employee.position}}</strong> within the <strong>{{employee.department}}</strong> Department, effective <strong>{{date.today}}</strong>.</p>
<p style="margin-top:12px;">Your revised remuneration will be <strong>{{employee.salary_gross}}</strong> per month gross. All other benefits and conditions of employment remain as per your current contract.</p>
<p style="margin-top:12px;">This promotion is in recognition of your outstanding performance, dedication, and contribution to {{company.name}}. We have every confidence in your ability to excel in your new role.</p>
<p style="margin-top:32px;">Congratulations and we wish you continued success.</p>
<p style="margin-top:16px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.department","employee.salary_gross","date.today"]',1,1),

(@cat_emp,'Salary Increase Letter','salary_increase','Notifies employee of a salary increase',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">SALARY ADJUSTMENT NOTIFICATION</h2>
<p style="margin-top:16px;">Following the annual salary review, we are pleased to inform you that your salary has been adjusted with effect from <strong>{{date.today}}</strong>.</p>
<table style="width:100%;border-collapse:collapse;margin:12px 0;">
<tr><td style="padding:5px 0;width:200px;color:#555;">Position:</td><td><strong>{{employee.position}}</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">New Gross Salary:</td><td><strong>{{employee.salary_gross}}</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Effective Date:</td><td><strong>{{date.today}}</strong></td></tr>
</table>
<p>This adjustment is in recognition of your performance and contribution to the organisation. All other terms of your employment remain unchanged.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.salary_gross","date.today"]',1,1),

(@cat_emp,'Transfer Letter','transfer_letter','Notifies employee of an internal transfer',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">LETTER OF TRANSFER</h2>
<p style="margin-top:16px;">This letter serves to formally advise you that with effect from <strong>{{date.today}}</strong>, you will be transferred to the <strong>{{employee.department}}</strong> Department, based at <strong>{{employee.work_location}}</strong>.</p>
<p style="margin-top:12px;">Your position title will remain <strong>{{employee.position}}</strong>. Your remuneration and other employment benefits remain unchanged.</p>
<p style="margin-top:12px;">We trust this transfer will further develop your skills and we look forward to your continued contribution.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.department","employee.work_location","date.today"]',1,1),

(@cat_emp,'Maternity Leave Approval Letter','maternity_approval','Formal maternity leave approval notification',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">MATERNITY LEAVE APPROVAL</h2>
<p style="margin-top:16px;">We acknowledge receipt of your application for maternity leave and are pleased to confirm approval in accordance with applicable labour legislation and company policy.</p>
<p style="margin-top:12px;">Your maternity leave will be granted as requested and in compliance with the relevant statutory provisions. Please ensure that you submit all required medical documentation to the Human Resources Department prior to your departure.</p>
<p style="margin-top:12px;">We wish you a safe and healthy delivery and look forward to welcoming you back upon your return.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","date.today"]',1,1),

(@cat_emp,'Remote Work Agreement','remote_work_agreement','Formalises a work-from-home arrangement',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">REMOTE WORK ARRANGEMENT AGREEMENT</h2>
<p style="margin-top:16px;">This letter confirms the approval of a remote work arrangement for <strong>{{employee.full_name}}</strong>, <strong>{{employee.position}}</strong>, effective <strong>{{date.today}}</strong>.</p>
<p style="margin-top:12px;"><strong>Terms of the arrangement:</strong></p>
<ul style="margin:12px 0;padding-left:24px;">
<li>The employee agrees to maintain regular working hours as per their employment contract.</li>
<li>The employee remains responsible for all deliverables and performance targets.</li>
<li>The employee must be reachable during core working hours.</li>
<li>Company equipment and data must be protected in accordance with the ICT Policy.</li>
<li>This arrangement may be reviewed or revoked by management with reasonable notice.</li>
</ul>
<p>Both parties agree to the above terms by signing below.</p>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-top:48px;">
<div><div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Employee Signature<br>{{employee.full_name}}<br>Date: ___________</div></div>
<div><div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">HR Manager<br>{{company.name}}<br>Date: ___________</div></div>
</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","date.today"]',1,1),

(@cat_emp,'Job Description Letter','job_description_letter','Documents an employee s formal job description',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="margin-bottom:24px;"><strong>{{company.name}}</strong> | Job Description</div>
<h2 style="font-size:1.1rem;margin-bottom:4px;">{{employee.position}}</h2>
<p style="color:#555;margin-bottom:24px;">Department: {{employee.department}} | Location: {{employee.work_location}} | Reports to: {{employee.supervisor}}</p>
<p><strong>Employee:</strong> {{employee.full_name}} ({{employee.number}})</p>
<p style="margin-top:16px;"><strong>Purpose of Role:</strong></p>
<p>[Describe the primary purpose and objective of this role in context of the department and company strategy.]</p>
<p style="margin-top:16px;"><strong>Key Responsibilities:</strong></p>
<ol style="padding-left:24px;margin-top:8px;">
<li>Perform duties as assigned by {{employee.supervisor}} in the {{employee.department}} Department.</li>
<li>Meet performance targets and quality standards set for this position.</li>
<li>Comply with all company policies, procedures, and workplace health and safety requirements.</li>
<li>Maintain professional conduct and represent {{company.name}} values at all times.</li>
<li>[Additional responsibilities specific to this role.]</li>
</ol>
<p style="margin-top:16px;"><strong>Minimum Requirements:</strong></p>
<ul style="padding-left:24px;margin-top:8px;">
<li>Relevant qualifications as per company standards.</li>
<li>Demonstrated competence in core role functions.</li>
</ul>
<p style="margin-top:24px;font-size:0.85rem;color:#555;">Issued: {{date.today}}</p>
</div>',
'["company.name","employee.full_name","employee.number","employee.position","employee.department","employee.work_location","employee.supervisor","date.today"]',1,1);

-- ============================================================
-- CERTIFICATES (8 templates)
-- ============================================================
INSERT INTO doc_templates (category_id,title,slug,description,body_html,variables_used,version,created_by) VALUES

(@cat_cert,'Employment Confirmation Certificate','employment_confirmation_cert','Confirms current employment status',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;text-align:center;">
<p style="margin-bottom:4px;"><strong>{{company.name}}</strong></p>
<p style="font-size:0.85rem;color:#555;margin-bottom:32px;">{{company.address}} | {{company.phone}}</p>
<h1 style="font-size:1.4rem;letter-spacing:.1em;text-transform:uppercase;margin-bottom:4px;">CERTIFICATE OF EMPLOYMENT</h1>
<div style="width:80px;height:3px;background:#1D4ED8;margin:0 auto 32px;"></div>
<p style="font-size:1rem;margin-bottom:8px;">This is to certify that</p>
<p style="font-size:1.4rem;font-weight:700;margin-bottom:8px;">{{employee.full_name}}</p>
<p style="color:#555;margin-bottom:24px;">Employee Number: {{employee.number}}</p>
<p style="max-width:500px;margin:0 auto;line-height:1.8;">is currently employed by <strong>{{company.name}}</strong> as a <strong>{{employee.position}}</strong> in the <strong>{{employee.department}}</strong> Department, on a <strong>{{employee.type}}</strong> basis, with effect from <strong>{{employee.start_date}}</strong>.</p>
<p style="margin-top:24px;">This certificate is issued at the request of the employee for purposes known to them.</p>
<div style="margin-top:64px;display:inline-block;text-align:left;min-width:220px;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Authorised Signature<br>Human Resources<br>{{company.name}}<br>Date: {{date.today}}</div>
</div>
</div>',
'["company.name","company.address","company.phone","employee.full_name","employee.number","employee.position","employee.department","employee.type","employee.start_date","date.today"]',1,1),

(@cat_cert,'Salary Certificate','salary_certificate','Confirms employee salary for external use',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;text-align:center;">
<p style="margin-bottom:4px;"><strong>{{company.name}}</strong></p>
<p style="font-size:0.85rem;color:#555;margin-bottom:32px;">{{company.address}}</p>
<h1 style="font-size:1.3rem;letter-spacing:.1em;text-transform:uppercase;margin-bottom:4px;">SALARY CERTIFICATE</h1>
<div style="width:60px;height:3px;background:#1D4ED8;margin:0 auto 32px;"></div>
<p>This is to certify that</p>
<p style="font-size:1.3rem;font-weight:700;margin:8px 0;">{{employee.full_name}}</p>
<p style="color:#555;margin-bottom:20px;">Employee Number: {{employee.number}}</p>
<p style="max-width:520px;margin:0 auto;line-height:1.8;">is a <strong>{{employee.type}}</strong> employee of {{company.name}} holding the position of <strong>{{employee.position}}</strong> in the <strong>{{employee.department}}</strong> Department. The employee draws a gross monthly salary of <strong>{{employee.salary_gross}}</strong> and a net monthly salary of <strong>{{employee.salary_net}}</strong>.</p>
<p style="margin-top:16px;">This certificate is issued upon the request of the employee and is for information purposes only.</p>
<div style="margin-top:64px;display:inline-block;text-align:left;min-width:220px;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Authorised Signature<br>Human Resources / Payroll<br>{{company.name}}<br>Date: {{date.today}}</div>
</div>
</div>',
'["company.name","company.address","employee.full_name","employee.number","employee.position","employee.department","employee.type","employee.salary_gross","employee.salary_net","date.today"]',1,1),

(@cat_cert,'Experience Letter','experience_letter','Issued to departing employee confirming work experience',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{company.address}}<br>{{date.today}}</div>
<p style="margin-bottom:8px;">TO WHOM IT MAY CONCERN</p>
<h2 style="font-size:1rem;text-decoration:underline;margin-bottom:16px;">EXPERIENCE LETTER</h2>
<p>This is to certify that <strong>{{employee.full_name}}</strong> (Employee No: {{employee.number}}) was employed at <strong>{{company.name}}</strong> as <strong>{{employee.position}}</strong> in the <strong>{{employee.department}}</strong> Department from <strong>{{employee.start_date}}</strong>.</p>
<p style="margin-top:16px;">During the period of employment, {{employee.preferred_name}} demonstrated professionalism, dedication and competence in all assigned duties. We found the employee to be reliable, hardworking and a valuable member of our team.</p>
<p style="margin-top:16px;">We wish {{employee.preferred_name}} every success in future endeavours.</p>
<p style="margin-top:32px;">Yours faithfully,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","company.address","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.department","employee.start_date","date.today"]',1,1),

(@cat_cert,'Reference Letter','reference_letter','Professional reference letter for a departing employee',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p>To Whom It May Concern,</p>
<h2 style="font-size:1rem;text-decoration:underline;margin:16px 0;">LETTER OF REFERENCE — {{employee.full_name}}</h2>
<p>I am pleased to provide this reference letter for <strong>{{employee.full_name}}</strong> who served as <strong>{{employee.position}}</strong> at {{company.name}} in the {{employee.department}} Department.</p>
<p style="margin-top:12px;">{{employee.preferred_name}} joined our organisation on <strong>{{employee.start_date}}</strong> and consistently demonstrated a high level of professionalism, competence and commitment throughout the duration of employment.</p>
<p style="margin-top:12px;">Key attributes observed include:</p>
<ul style="padding-left:24px;margin-top:8px;">
<li>Strong work ethic and reliability</li>
<li>Ability to work independently and as part of a team</li>
<li>Excellent communication and interpersonal skills</li>
<li>Commitment to quality and meeting deadlines</li>
</ul>
<p style="margin-top:12px;">I have no hesitation in recommending {{employee.preferred_name}} and am confident they will be a valuable addition to any organisation. For any further enquiries please contact us at {{company.email}}.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","company.email","employee.full_name","employee.preferred_name","employee.position","employee.department","employee.start_date","date.today"]',1,1),

(@cat_cert,'Training Completion Certificate','training_completion_cert','Certificate awarded on completion of training',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;text-align:center;border:3px solid #1D4ED8;padding:48px;">
<p style="font-size:0.85rem;letter-spacing:.1em;text-transform:uppercase;color:#1D4ED8;margin-bottom:8px;">{{company.name}}</p>
<h1 style="font-size:1.6rem;letter-spacing:.05em;text-transform:uppercase;margin-bottom:4px;">Certificate of Completion</h1>
<div style="width:100px;height:3px;background:#1D4ED8;margin:12px auto 24px;"></div>
<p style="font-size:1rem;color:#555;">This is to certify that</p>
<p style="font-size:1.6rem;font-weight:700;font-style:italic;margin:12px 0;">{{employee.full_name}}</p>
<p style="color:#555;margin-bottom:20px;">{{employee.position}} | {{employee.department}}</p>
<p style="font-size:1rem;color:#555;">has successfully completed the training programme</p>
<p style="font-size:1.2rem;font-weight:600;margin:12px 0;">[Training Programme Name]</p>
<p style="font-size:0.9rem;color:#555;">Conducted on {{date.today}}</p>
<div style="margin-top:48px;display:grid;grid-template-columns:1fr 1fr;gap:32px;text-align:left;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Trainer Signature<br>Date: {{date.today}}</div>
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">HR Manager<br>{{company.name}}</div>
</div>
</div>',
'["company.name","employee.full_name","employee.position","employee.department","date.today"]',1,1),

(@cat_cert,'Long Service Award Letter','long_service_award','Recognises employee for long service milestone',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;text-align:center;">
<div style="margin-bottom:24px;"><strong>{{company.name}}</strong></div>
<h1 style="font-size:1.3rem;letter-spacing:.05em;">LONG SERVICE RECOGNITION</h1>
<div style="width:80px;height:3px;background:gold;margin:12px auto 32px;"></div>
<p>We are proud to honour</p>
<p style="font-size:1.5rem;font-weight:700;margin:8px 0;">{{employee.full_name}}</p>
<p style="color:#555;">{{employee.position}} | {{employee.department}}</p>
<p style="margin:24px auto;max-width:500px;line-height:1.8;">
In grateful recognition of <strong>[X] years</strong> of loyal, dedicated and outstanding service to {{company.name}} since <strong>{{employee.start_date}}</strong>.
</p>
<p>Your commitment, professionalism and contributions have made a lasting impact on our organisation.</p>
<p style="margin-top:32px;font-size:1.1rem;font-style:italic;color:#1D4ED8;">Thank you for your dedication.</p>
<div style="margin-top:48px;display:inline-block;text-align:left;min-width:200px;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Managing Director / CEO<br>{{company.name}}<br>{{date.today}}</div>
</div>
</div>',
'["company.name","employee.full_name","employee.position","employee.department","employee.start_date","date.today"]',1,1),

(@cat_cert,'Performance Award Letter','performance_award','Recognises exceptional employee performance',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>{{employee.position}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">RECOGNITION OF OUTSTANDING PERFORMANCE</h2>
<p style="margin-top:16px;">On behalf of {{company.name}}, it is my privilege to formally recognise and commend you for your exceptional performance during {{date.month}}.</p>
<p style="margin-top:12px;">Your dedication, initiative and the high quality of your work have not gone unnoticed. You have consistently exceeded expectations and serve as an inspiration to your colleagues.</p>
<p style="margin-top:12px;">We are proud to have you as part of the {{employee.department}} team and look forward to your continued excellence.</p>
<p style="margin-top:32px;">Congratulations and well done!</p>
<p style="margin-top:16px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.position","employee.department","date.today","date.month"]',1,1),

(@cat_cert,'Skills Assessment Certificate','skills_assessment_cert','Documents completion of a skills assessment',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;text-align:center;border:2px solid #334155;padding:40px;">
<p style="font-size:0.8rem;letter-spacing:.1em;text-transform:uppercase;color:#555;margin-bottom:8px;">{{company.name}}</p>
<h1 style="font-size:1.3rem;">SKILLS ASSESSMENT CERTIFICATE</h1>
<div style="width:60px;height:2px;background:#334155;margin:12px auto 24px;"></div>
<p>This certifies that</p>
<p style="font-size:1.4rem;font-weight:700;margin:8px 0;">{{employee.full_name}}</p>
<p style="color:#555;margin-bottom:16px;">{{employee.position}} | Employee No: {{employee.number}}</p>
<p>has been assessed and has demonstrated competency in</p>
<p style="font-size:1.1rem;font-weight:600;margin:12px 0;">[Assessment Name / Skill Area]</p>
<p style="font-size:0.9rem;color:#555;">Assessed on: {{date.today}}</p>
<div style="margin-top:48px;display:grid;grid-template-columns:1fr 1fr;gap:32px;text-align:left;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Assessor Signature<br>Date: {{date.today}}</div>
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">HR Manager<br>{{company.name}}</div>
</div>
</div>',
'["company.name","employee.full_name","employee.number","employee.position","date.today"]',1,1);

-- ============================================================
-- HR LETTERS (8 templates)
-- ============================================================
INSERT INTO doc_templates (category_id,title,slug,description,body_html,variables_used,version,created_by) VALUES

(@cat_hr,'First Written Warning','first_written_warning','First formal disciplinary warning letter',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>PRIVATE AND CONFIDENTIAL</strong></p>
<p style="margin-top:8px;"><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}<br>{{employee.position}} | {{employee.department}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">FIRST WRITTEN WARNING</h2>
<p style="margin-top:16px;">Following the disciplinary hearing held on {{date.today}}, this letter serves as a formal <strong>First Written Warning</strong> regarding [nature of misconduct/poor performance].</p>
<p style="margin-top:12px;"><strong>Summary of Findings:</strong><br>
[Detail the specific issue, incidents, dates and circumstances that led to this warning.]</p>
<p style="margin-top:12px;"><strong>Expected Improvement:</strong><br>
[Specific, measurable improvements required and by when.]</p>
<p style="margin-top:12px;">This warning will remain on your record for a period of <strong>6 months</strong>. Should there be no recurrence or further breach within this period, it will be disregarded.</p>
<p style="margin-top:12px;">Should there be a recurrence, further disciplinary action will be taken which may result in a Final Written Warning or dismissal.</p>
<p style="margin-top:12px;">You have the right to appeal this warning within <strong>5 working days</strong> by submitting a written appeal to the Human Resources Department.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
<div style="margin-top:24px;border-top:1px solid #333;width:280px;padding-top:8px;font-size:0.85rem;">
Employee Acknowledgement: I have read and understand this warning.<br><br>
Signature: _______________<br>Date: _______________
</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.department","date.today"]',1,1),

(@cat_hr,'Final Written Warning','final_written_warning','Final formal disciplinary warning before dismissal',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>PRIVATE AND CONFIDENTIAL</strong></p>
<p style="margin-top:8px;"><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">FINAL WRITTEN WARNING</h2>
<p style="margin-top:16px;">Following the disciplinary hearing held on {{date.today}}, you are hereby issued with a <strong>Final Written Warning</strong>.</p>
<p style="margin-top:12px;"><strong>Nature of Misconduct/Poor Performance:</strong><br>[Details]</p>
<p style="margin-top:12px;"><strong>Required Corrective Action:</strong><br>[Specific actions required]</p>
<p style="margin-top:12px;">You are warned that any further transgression of company rules, policies or a repeat of the behaviour that led to this warning, within the validity period of <strong>12 months</strong>, will result in your dismissal.</p>
<p style="margin-top:12px;">You have the right to appeal this decision within <strong>5 working days</strong>.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
<div style="margin-top:24px;border-top:1px solid #333;width:280px;padding-top:8px;font-size:0.85rem;">
Employee Acknowledgement:<br><br>Signature: _______________<br>Date: _______________
</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","date.today"]',1,1),

(@cat_hr,'Suspension Letter','suspension_letter','Places employee on suspension pending investigation',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>PRIVATE AND CONFIDENTIAL</strong></p>
<p style="margin-top:8px;"><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">NOTICE OF PRECAUTIONARY SUSPENSION</h2>
<p style="margin-top:16px;">You are hereby placed on <strong>precautionary suspension</strong> with immediate effect from <strong>{{date.today}}</strong>, pending an investigation into alleged [nature of allegation].</p>
<p style="margin-top:12px;">This suspension is a precautionary measure to protect the integrity of the investigation and does not constitute a finding of guilt.</p>
<p style="margin-top:12px;"><strong>Conditions of Suspension:</strong></p>
<ul style="padding-left:24px;margin-top:8px;">
<li>You are to remain away from company premises until further notice.</li>
<li>You may not contact any witnesses or employees connected to this matter.</li>
<li>You are to be available for any disciplinary proceedings as required.</li>
<li>Your salary will continue to be paid during this period.</li>
</ul>
<p style="margin-top:12px;">You will be notified of the disciplinary hearing date in due course.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","date.today"]',1,1),

(@cat_hr,'Disciplinary Hearing Notice','disciplinary_hearing_notice','Notice calling employee to a disciplinary hearing',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>PRIVATE AND CONFIDENTIAL</strong></p>
<p style="margin-top:8px;"><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}<br>{{employee.position}} | {{employee.department}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">NOTICE OF DISCIPLINARY HEARING</h2>
<p style="margin-top:16px;">You are hereby notified to attend a disciplinary hearing:</p>
<table style="width:100%;border-collapse:collapse;margin:12px 0;">
<tr><td style="padding:5px 0;width:180px;color:#555;">Date:</td><td><strong>[Hearing Date]</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Time:</td><td><strong>[Hearing Time]</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Venue:</td><td><strong>[Hearing Venue]</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Chairperson:</td><td><strong>[Chairperson Name]</strong></td></tr>
</table>
<p><strong>Charges Against You:</strong></p>
<ol style="padding-left:24px;margin-top:8px;">
<li>[Charge 1 — specific allegation, date, incident details]</li>
<li>[Charge 2 — if applicable]</li>
</ol>
<p style="margin-top:12px;">You are entitled to be represented by a fellow employee or trade union representative. Please advise HR of your representative by [date].</p>
<p style="margin-top:12px;">Failure to attend without reasonable cause may result in the hearing proceeding in your absence.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.department","date.today"]',1,1),

(@cat_hr,'Grievance Acknowledgement Letter','grievance_acknowledgement','Acknowledges receipt of employee grievance',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">ACKNOWLEDGEMENT OF GRIEVANCE</h2>
<p style="margin-top:16px;">We confirm receipt of your formal grievance submitted on <strong>{{date.today}}</strong>.</p>
<p style="margin-top:12px;">Your grievance has been registered under reference number <strong>[GR-{{date.year}}-XXXX]</strong> and will be investigated in accordance with our Grievance Procedure.</p>
<p style="margin-top:12px;">An HR Officer will contact you within <strong>5 working days</strong> to schedule a grievance meeting and discuss the matter further.</p>
<p style="margin-top:12px;">We treat all grievances seriously and assure you that your concern will be handled with confidentiality and impartiality.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","date.today","date.year"]',1,1),

(@cat_hr,'Performance Improvement Plan','performance_improvement_plan','Formal PIP letter for underperforming employee',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}<br>{{employee.position}} | {{employee.department}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">PERFORMANCE IMPROVEMENT PLAN (PIP)</h2>
<p style="margin-top:16px;">Following recent performance discussions, this letter formalises a Performance Improvement Plan (PIP) to support you in achieving the required performance standards for your role as <strong>{{employee.position}}</strong>.</p>
<p style="margin-top:12px;"><strong>PIP Duration:</strong> {{date.today}} to [end date] (90 days)</p>
<p style="margin-top:12px;"><strong>Performance Areas Requiring Improvement:</strong></p>
<ol style="padding-left:24px;margin-top:8px;">
<li>[Performance area 1 — specific, measurable target]</li>
<li>[Performance area 2 — specific, measurable target]</li>
<li>[Performance area 3 — specific, measurable target]</li>
</ol>
<p style="margin-top:12px;"><strong>Support Provided:</strong><br>Your supervisor {{employee.supervisor}} will conduct bi-weekly check-in meetings to monitor progress and provide guidance.</p>
<p style="margin-top:12px;"><strong>Consequences:</strong> Failure to meet the required targets by the end of the PIP period may result in further disciplinary action, including termination of employment.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:40px;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">HR Manager<br>{{company.name}}</div>
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Employee: {{employee.full_name}}<br>Date: _______________</div>
</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.department","employee.supervisor","date.today"]',1,1),

(@cat_hr,'Commendation Letter','commendation_letter','Formally commends an employee for excellent work',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>{{employee.position}} | {{employee.department}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">LETTER OF COMMENDATION</h2>
<p style="margin-top:16px;">I write to formally commend you for your exceptional contribution during [specific period/project].</p>
<p style="margin-top:12px;">[Specific achievement or behaviour to be commended.]</p>
<p style="margin-top:12px;">Your conduct reflects the highest standards of professionalism and embodies the values of {{company.name}}. Such dedication does not go unnoticed and serves as a benchmark for excellence within our team.</p>
<p style="margin-top:12px;">This letter will be placed on your personal file as a record of your outstanding performance.</p>
<p style="margin-top:32px;">Well done and keep up the excellent work!</p>
<p style="margin-top:16px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.position","employee.department","date.today"]',1,1),

(@cat_hr,'Return to Work Letter','return_to_work','Confirms employee return to work after absence',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">RETURN TO WORK NOTIFICATION</h2>
<p style="margin-top:16px;">We are pleased to confirm your return to work effective <strong>{{date.today}}</strong> following your recent absence.</p>
<p style="margin-top:12px;">A return-to-work interview will be conducted by your supervisor, {{employee.supervisor}}, to ensure your wellbeing and discuss any support that may be required to assist with your reintegration.</p>
<p style="margin-top:12px;">Please ensure all outstanding documentation (e.g. medical certificates) is submitted to HR by the end of this week.</p>
<p style="margin-top:12px;">We are glad to have you back and trust you are feeling well.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.supervisor","date.today"]',1,1);

-- ============================================================
-- LEAVE DOCUMENTS (5 templates)
-- ============================================================
INSERT INTO doc_templates (category_id,title,slug,description,body_html,variables_used,version,created_by) VALUES

(@cat_leave,'Leave Approval Notification','leave_approval_notification','Formal leave approval confirmation to employee',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">LEAVE APPROVAL CONFIRMATION</h2>
<p style="margin-top:16px;">This letter confirms that your leave application has been approved as follows:</p>
<table style="width:100%;border-collapse:collapse;margin:12px 0;">
<tr><td style="padding:5px 0;width:180px;color:#555;">Leave Type:</td><td><strong>[Leave Type]</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">From:</td><td><strong>[Start Date]</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">To:</td><td><strong>[End Date]</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Total Days:</td><td><strong>[Number of Days]</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Remaining Balance:</td><td><strong>{{employee.leave_balance}}</strong></td></tr>
</table>
<p>Please ensure that a handover has been completed with your colleagues prior to your departure and that all urgent matters have been attended to.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.leave_balance","date.today"]',1,1),

(@cat_leave,'Leave Rejection Letter','leave_rejection_letter','Formally rejects a leave application with reason',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">LEAVE APPLICATION — NOT APPROVED</h2>
<p style="margin-top:16px;">We regret to inform you that your leave application for [dates] has not been approved at this time.</p>
<p style="margin-top:12px;"><strong>Reason:</strong> [Specific reason — operational requirements, insufficient balance, inadequate notice, etc.]</p>
<p style="margin-top:12px;">You are welcome to re-apply at a more suitable time. Please discuss alternative dates with your supervisor {{employee.supervisor}} at your earliest convenience.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.supervisor","date.today"]',1,1),

(@cat_leave,'Annual Leave Summary','annual_leave_summary','Summary of employee annual leave balance and usage',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}<br>{{employee.position}} | {{employee.department}}</p>
<h2 style="font-size:1rem;text-decoration:underline;margin:24px 0 16px;">ANNUAL LEAVE SUMMARY — {{date.year}}</h2>
<table style="width:100%;border-collapse:collapse;margin:12px 0;">
<thead><tr style="background:#f1f5f9;">
<th style="padding:8px;text-align:left;font-size:0.85rem;">Leave Type</th>
<th style="padding:8px;text-align:center;">Entitled</th>
<th style="padding:8px;text-align:center;">Taken</th>
<th style="padding:8px;text-align:center;">Balance</th>
</tr></thead>
<tbody>
<tr><td style="padding:8px;">Annual Leave</td><td style="padding:8px;text-align:center;">[X]</td><td style="padding:8px;text-align:center;">[X]</td><td style="padding:8px;text-align:center;"><strong>[X]</strong></td></tr>
<tr><td style="padding:8px;background:#f8fafc;">Sick Leave</td><td style="padding:8px;text-align:center;background:#f8fafc;">[X]</td><td style="padding:8px;text-align:center;background:#f8fafc;">[X]</td><td style="padding:8px;text-align:center;background:#f8fafc;"><strong>[X]</strong></td></tr>
</tbody>
</table>
<p style="font-size:0.85rem;color:#555;">Total remaining leave balance as at {{date.today}}: <strong>{{employee.leave_balance}}</strong></p>
<p style="margin-top:24px;font-size:0.85rem;color:#555;">This summary is for informational purposes. For queries contact HR at {{company.email}}.</p>
</div>',
'["company.name","company.email","employee.full_name","employee.number","employee.position","employee.department","employee.leave_balance","date.today","date.year"]',1,1),

(@cat_leave,'Leave Without Pay Approval','lwp_approval','Approves leave without pay arrangement',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">APPROVAL OF LEAVE WITHOUT PAY</h2>
<p style="margin-top:16px;">This letter confirms approval of your request for Leave Without Pay (LWOP) from <strong>[start date]</strong> to <strong>[end date]</strong> (<strong>[X] days</strong>).</p>
<p style="margin-top:12px;"><strong>Important conditions:</strong></p>
<ul style="padding-left:24px;margin-top:8px;">
<li>No salary or benefits will be paid during this period.</li>
<li>UIF and pension fund arrangements may be affected — please consult Payroll.</li>
<li>Your leave balance will not accrue during this period.</li>
<li>Your position will be held for the agreed duration.</li>
</ul>
<p style="margin-top:12px;">Please ensure a thorough handover is completed before your departure.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","date.today"]',1,1),

(@cat_leave,'Study Leave Approval','study_leave_approval','Approves study leave for an employee',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">STUDY LEAVE APPROVAL</h2>
<p style="margin-top:16px;">We are pleased to approve your application for study leave as follows:</p>
<table style="width:100%;border-collapse:collapse;margin:12px 0;">
<tr><td style="padding:5px 0;width:180px;color:#555;">Institution:</td><td><strong>[Institution Name]</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Course / Qualification:</td><td><strong>[Course Name]</strong></td></tr>
<tr><td style="padding:5px 0;color:#555;">Duration:</td><td><strong>[Start Date] to [End Date]</strong></td></tr>
</table>
<p style="margin-top:12px;">This approval is conditional upon you submitting proof of results to HR upon completion of each study period. The company reserves the right to withdraw this benefit if performance standards are not maintained.</p>
<p style="margin-top:32px;">We wish you every success in your studies!</p>
<p style="margin-top:16px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","date.today"]',1,1);

-- ============================================================
-- ONBOARDING (5 templates)
-- ============================================================
INSERT INTO doc_templates (category_id,title,slug,description,body_html,variables_used,version,created_by) VALUES

(@cat_onb,'Welcome Letter','welcome_letter','Welcome letter for new employee joining the company',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong></p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1.1rem;text-decoration:underline;">WELCOME TO {{company.name}}</h2>
<p style="margin-top:16px;">On behalf of the entire team at {{company.name}}, I am delighted to welcome you as our new <strong>{{employee.position}}</strong> in the <strong>{{employee.department}}</strong> Department.</p>
<p style="margin-top:12px;">Your first day is <strong>{{employee.start_date}}</strong>. Please report to {{employee.work_location}} and ask for {{employee.supervisor}} who will guide you through your first week.</p>
<p style="margin-top:12px;"><strong>What to bring on your first day:</strong></p>
<ul style="padding-left:24px;margin-top:8px;">
<li>Certified copy of your identity document</li>
<li>Original qualifications / certificates</li>
<li>Bank account details</li>
<li>Tax reference number (if available)</li>
<li>Next of kin details</li>
</ul>
<p style="margin-top:12px;">We are confident that you will thrive in your new role and look forward to your contribution to our growing team. Do not hesitate to reach out to Human Resources at {{company.email}} if you have any questions.</p>
<p style="margin-top:24px;">Welcome aboard!</p>
<p style="margin-top:16px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","company.email","employee.full_name","employee.preferred_name","employee.position","employee.department","employee.start_date","employee.work_location","employee.supervisor","date.today"]',1,1),

(@cat_onb,'Induction Checklist','induction_checklist','Standard employee induction checklist form',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<h2 style="font-size:1rem;margin-bottom:4px;">Employee Induction Checklist</h2>
<p style="color:#555;font-size:0.85rem;margin-bottom:24px;">{{company.name}} | HR Department</p>
<table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
<tr><td style="padding:6px 0;width:200px;color:#555;">Employee Name:</td><td><strong>{{employee.full_name}}</strong></td><td style="width:200px;color:#555;">Employee No:</td><td><strong>{{employee.number}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Position:</td><td><strong>{{employee.position}}</strong></td><td style="color:#555;">Department:</td><td><strong>{{employee.department}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Start Date:</td><td><strong>{{employee.start_date}}</strong></td><td style="color:#555;">Supervisor:</td><td><strong>{{employee.supervisor}}</strong></td></tr>
</table>
<table style="width:100%;border-collapse:collapse;">
<thead><tr style="background:#f1f5f9;"><th style="padding:8px;text-align:left;">Induction Item</th><th style="padding:8px;text-align:center;width:100px;">Completed</th><th style="padding:8px;text-align:center;width:120px;">Date</th><th style="padding:8px;text-align:left;">By</th></tr></thead>
<tbody>
<?php foreach ([
"Company overview and history","Organisational structure","HR policies and procedures",
"Employment contract signed","IT systems access setup","Email and communication tools",
"Health and safety briefing","Site/office tour","Introduction to team members",
"Job responsibilities explained","Performance management process","Leave policy explained",
"Payroll and benefits overview","Emergency procedures","Code of conduct and ethics",
"Data protection (POPI) briefing","Probation period explained","Completion sign-off"
] as $item): ?>
<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:7px 8px;font-size:0.82rem;"><?= $item ?></td><td style="text-align:center;">☐</td><td style="text-align:center;"></td><td></td></tr>
<?php endforeach; ?>
</tbody>
</table>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-top:32px;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Employee Signature: _______________<br>Date: _______________</div>
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">HR Officer: _______________<br>Date: _______________</div>
</div>
</div>',
'["company.name","employee.full_name","employee.number","employee.position","employee.department","employee.start_date","employee.supervisor"]',1,1),

(@cat_onb,'Code of Conduct Acknowledgement','code_of_conduct_acknowledgement','Employee acknowledgement of code of conduct',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:center;margin-bottom:32px;"><strong>{{company.name}}</strong><br><span style="font-size:0.85rem;color:#555;">Code of Conduct — Employee Acknowledgement</span></div>
<p>I, <strong>{{employee.full_name}}</strong> (Employee No: {{employee.number}}), hereby acknowledge that I have received, read and understood the {{company.name}} Code of Conduct.</p>
<p style="margin-top:12px;">I understand that the Code of Conduct forms part of my conditions of employment and that any breach thereof may result in disciplinary action, up to and including dismissal.</p>
<p style="margin-top:12px;">I commit to upholding the values and standards set out in the Code of Conduct at all times during my employment at {{company.name}}.</p>
<p style="margin-top:24px;font-size:0.9rem;"><strong>Key Commitments I am making:</strong></p>
<ul style="padding-left:24px;margin-top:8px;">
<li>Act with integrity and honesty in all dealings</li>
<li>Treat all colleagues, customers and stakeholders with respect and dignity</li>
<li>Comply with all applicable laws and company policies</li>
<li>Protect confidential company information</li>
<li>Report any conflicts of interest or unethical behaviour</li>
<li>Maintain professional standards at all times</li>
</ul>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-top:40px;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Employee: {{employee.full_name}}<br>Signature: _______________<br>Date: {{date.today}}</div>
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Witness / HR Officer: _______________<br>Signature: _______________<br>Date: {{date.today}}</div>
</div>
</div>',
'["company.name","employee.full_name","employee.number","date.today"]',1,1),

(@cat_onb,'POPI Act Acknowledgement','popi_acknowledgement','Employee acknowledgement of POPI data protection',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:center;margin-bottom:24px;"><strong>{{company.name}}</strong></div>
<h2 style="font-size:1rem;text-align:center;text-decoration:underline;margin-bottom:24px;">POPI ACT — EMPLOYEE DATA CONSENT AND ACKNOWLEDGEMENT</h2>
<p>I, <strong>{{employee.full_name}}</strong> (Employee No: {{employee.number}}), hereby acknowledge and consent to the collection, storage, processing, and use of my personal information by <strong>{{company.name}}</strong> as required for the administration of my employment, in accordance with the Protection of Personal Information Act (POPI Act No. 4 of 2013).</p>
<p style="margin-top:12px;">I understand that:</p>
<ul style="padding-left:24px;margin-top:8px;">
<li>My personal information will be collected, processed and stored securely.</li>
<li>My data will only be used for lawful employment-related purposes.</li>
<li>I have the right to access, correct, or object to the processing of my personal data.</li>
<li>My data will not be shared with third parties without my consent, except as required by law.</li>
<li>I have the right to lodge a complaint with the Information Regulator if I believe my rights have been infringed.</li>
</ul>
<div style="margin-top:40px;border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">
Employee Name: {{employee.full_name}}<br>
Signature: _______________<br>
Date: {{date.today}}
</div>
</div>',
'["company.name","employee.full_name","employee.number","date.today"]',1,1),

(@cat_onb,'IT Acceptable Use Policy Acknowledgement','it_acceptable_use_ack','Employee acknowledges IT acceptable use policy',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:center;margin-bottom:24px;"><strong>{{company.name}}</strong></div>
<h2 style="font-size:1rem;text-align:center;text-decoration:underline;margin-bottom:24px;">IT ACCEPTABLE USE POLICY — ACKNOWLEDGEMENT</h2>
<p>I, <strong>{{employee.full_name}}</strong>, acknowledge that I have been informed of the {{company.name}} IT Acceptable Use Policy and agree to comply with its requirements.</p>
<p style="margin-top:12px;">I understand and agree to the following:</p>
<ul style="padding-left:24px;margin-top:8px;">
<li>Company IT resources (computers, internet, email, software) are provided for business purposes and may be monitored.</li>
<li>I will not use company systems for unauthorised, illegal or inappropriate activities.</li>
<li>I will protect my login credentials and not share passwords.</li>
<li>I will not install unauthorised software on company devices.</li>
<li>I will report any security incidents or data breaches immediately to IT.</li>
<li>Company data will not be copied to personal devices or cloud services without authorisation.</li>
<li>Upon termination of employment, all company data and devices must be returned.</li>
</ul>
<div style="margin-top:40px;border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">
Employee: {{employee.full_name}} ({{employee.number}})<br>
Signature: _______________<br>
Date: {{date.today}}
</div>
</div>',
'["company.name","employee.full_name","employee.number","date.today"]',1,1);

-- ============================================================
-- EXIT MANAGEMENT (5 templates)
-- ============================================================
INSERT INTO doc_templates (category_id,title,slug,description,body_html,variables_used,version,created_by) VALUES

(@cat_exit,'Resignation Acceptance Letter','resignation_acceptance','Formally accepts employee resignation',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}<br>{{employee.position}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">ACCEPTANCE OF RESIGNATION</h2>
<p style="margin-top:16px;">We acknowledge receipt of your letter of resignation dated [resignation date] and hereby confirm acceptance of your resignation as <strong>{{employee.position}}</strong>, effective from <strong>[last working day]</strong>.</p>
<p style="margin-top:12px;">We request that you complete the following prior to your departure:</p>
<ul style="padding-left:24px;margin-top:8px;">
<li>Hand over all company property, equipment and access cards</li>
<li>Complete a comprehensive handover of all outstanding work</li>
<li>Submit an exit interview form to HR</li>
<li>Settle any outstanding company obligations</li>
</ul>
<p style="margin-top:12px;">Your final remuneration including outstanding leave pay will be processed with your last salary payment. A certificate of service will be issued upon request.</p>
<p style="margin-top:12px;">We appreciate your contribution during your tenure and wish you all the best in your future endeavours.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","date.today"]',1,1),

(@cat_exit,'Clearance Certificate','clearance_certificate','Employee clearance certificate on exit',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;text-align:center;">
<strong>{{company.name}}</strong>
<h2 style="font-size:1.1rem;text-transform:uppercase;letter-spacing:.05em;margin:16px 0 4px;">Employee Clearance Certificate</h2>
<div style="width:60px;height:2px;background:#1D4ED8;margin:0 auto 24px;"></div>
<div style="text-align:left;">
<table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
<tr><td style="padding:6px 0;width:200px;color:#555;">Employee Name:</td><td><strong>{{employee.full_name}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Employee Number:</td><td><strong>{{employee.number}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Position:</td><td><strong>{{employee.position}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Department:</td><td><strong>{{employee.department}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Last Working Day:</td><td><strong>[Last Working Day]</strong></td></tr>
</table>
<table style="width:100%;border-collapse:collapse;">
<thead><tr style="background:#f1f5f9;"><th style="padding:8px;text-align:left;">Department / Item</th><th style="padding:8px;text-align:center;width:120px;">Cleared</th><th style="padding:8px;text-align:left;">Authorised By</th><th style="padding:8px;text-align:left;">Date</th></tr></thead>
<tbody>
<?php foreach (["IT — Systems access revoked","IT — Equipment returned","Finance — No outstanding advances","HR — Documents submitted","HR — Leave balance settled","Payroll — Final salary confirmed","Assets — All assets returned","Security — Access card returned","Manager — Handover completed"] as $item): ?>
<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:8px;font-size:0.82rem;"><?= $item ?></td><td style="text-align:center;">☐</td><td></td><td></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-top:32px;text-align:left;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Employee: {{employee.full_name}}<br>Signature: _______________<br>Date: {{date.today}}</div>
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">HR Manager<br>{{company.name}}<br>Date: _______________</div>
</div>
</div>',
'["company.name","employee.full_name","employee.number","employee.position","employee.department","date.today"]',1,1),

(@cat_exit,'Dismissal Letter','dismissal_letter','Formal notice of dismissal after disciplinary process',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>PRIVATE AND CONFIDENTIAL</strong></p>
<p style="margin-top:8px;"><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}<br>{{employee.position}} | {{employee.department}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">NOTICE OF DISMISSAL</h2>
<p style="margin-top:16px;">Following the disciplinary hearing conducted on [hearing date], at which you were afforded the opportunity to state your case and be represented, the chairperson found you <strong>guilty</strong> of the following:</p>
<ol style="padding-left:24px;margin-top:8px;">
<li>[Charge 1]</li>
<li>[Charge 2 — if applicable]</li>
</ol>
<p style="margin-top:12px;">Having considered all the evidence presented and mitigating factors, the chairperson imposed the sanction of <strong>DISMISSAL</strong>.</p>
<p style="margin-top:12px;">Your dismissal takes effect from <strong>{{date.today}}</strong>. You are required to return all company property immediately. Your final salary payment including any outstanding amounts due to you will be processed in accordance with applicable law.</p>
<p style="margin-top:12px;">You have the right to appeal this decision within <strong>5 working days</strong> by submitting a written appeal to the HR Department at {{company.email}}.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","company.email","employee.full_name","employee.preferred_name","employee.number","employee.position","employee.department","date.today"]',1,1),

(@cat_exit,'Retrenchment Letter','retrenchment_letter','Formal notice of retrenchment with UIF information',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:right;margin-bottom:32px;"><strong>{{company.name}}</strong><br>{{date.today}}</div>
<p><strong>PRIVATE AND CONFIDENTIAL</strong></p>
<p style="margin-top:8px;"><strong>{{employee.full_name}}</strong><br>Employee No: {{employee.number}}</p>
<p style="margin:24px 0;">Dear {{employee.preferred_name}},</p>
<h2 style="font-size:1rem;text-decoration:underline;">NOTICE OF RETRENCHMENT</h2>
<p style="margin-top:16px;">Following the Section 189/189A consultation process, we regret to inform you that your position of <strong>{{employee.position}}</strong> has been declared redundant, effective <strong>[last day of employment]</strong>.</p>
<p style="margin-top:12px;"><strong>Severance Package:</strong><br>In accordance with applicable labour legislation, you will receive:</p>
<ul style="padding-left:24px;margin-top:8px;">
<li>Severance pay equivalent to 1 week per year of completed service</li>
<li>Payment in lieu of outstanding leave balance</li>
<li>Notice pay where applicable</li>
</ul>
<p style="margin-top:12px;"><strong>UIF:</strong><br>You are entitled to claim from the Unemployment Insurance Fund (UIF). HR will assist you with the relevant documentation.</p>
<p style="margin-top:12px;">We sincerely regret the impact of this decision and wish you every success. A certificate of service will be issued upon request.</p>
<p style="margin-top:32px;">Yours sincerely,</p>
<div style="margin-top:48px;border-top:1px solid #333;width:200px;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","employee.full_name","employee.preferred_name","employee.number","employee.position","date.today"]',1,1),

(@cat_exit,'Exit Interview Form','exit_interview_form','Structured exit interview form for departing employees',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="margin-bottom:24px;"><strong>{{company.name}}</strong> | EXIT INTERVIEW FORM</div>
<table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
<tr><td style="padding:6px 0;width:200px;color:#555;">Employee Name:</td><td><strong>{{employee.full_name}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Position:</td><td><strong>{{employee.position}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Department:</td><td><strong>{{employee.department}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Date of Interview:</td><td><strong>{{date.today}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#555;">Interviewer:</td><td><strong>_______________</strong></td></tr>
</table>
<p style="font-size:0.85rem;margin-bottom:16px;">Please answer the following questions honestly. Your feedback is confidential and helps us improve.</p>
<?php $qs = [
"What is your main reason for leaving {{company.name}}?",
"How would you rate your overall experience working here? (1–10)",
"Were your roles and responsibilities clearly defined?",
"How was your relationship with your direct supervisor ({{employee.supervisor}})?",
"Were you given sufficient opportunities for growth and development?",
"Would you recommend {{company.name}} as an employer to others? Why?",
"What did you enjoy most about working here?",
"What improvements would you suggest for {{company.name}}?"
]; foreach ($qs as $i => $q): ?>
<div style="margin-bottom:20px;">
<p style="font-weight:600;font-size:0.88rem;"><?= ($i+1) ?>. <?= $q ?></p>
<div style="border-bottom:1px solid #cbd5e1;margin-top:28px;"></div>
<div style="border-bottom:1px solid #cbd5e1;margin-top:20px;"></div>
</div>
<?php endforeach; ?>
<div style="margin-top:32px;border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Employee Signature: _______________&nbsp;&nbsp;&nbsp;Date: _______________</div>
</div>',
'["company.name","employee.full_name","employee.position","employee.department","employee.supervisor","date.today"]',1,1);

-- ============================================================
-- COMPLIANCE (3 templates)
-- ============================================================
INSERT INTO doc_templates (category_id,title,slug,description,body_html,variables_used,version,created_by) VALUES

(@cat_comp,'Health and Safety Acknowledgement','health_safety_ack','Employee acknowledges health and safety requirements',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:center;margin-bottom:24px;"><strong>{{company.name}}</strong></div>
<h2 style="font-size:1rem;text-align:center;text-decoration:underline;margin-bottom:24px;">OCCUPATIONAL HEALTH AND SAFETY ACKNOWLEDGEMENT</h2>
<p>I, <strong>{{employee.full_name}}</strong> ({{employee.number}}), have been informed of the Occupational Health and Safety requirements of {{company.name}} and commit to comply with:</p>
<ul style="padding-left:24px;margin-top:12px;">
<li>All relevant provisions of the Occupational Health and Safety Act</li>
<li>Company health and safety policies and procedures</li>
<li>Correct use of personal protective equipment (PPE)</li>
<li>Reporting all accidents, near-misses and unsafe conditions immediately</li>
<li>Not performing work under the influence of alcohol or drugs</li>
<li>Attending all required health and safety training</li>
</ul>
<div style="margin-top:40px;border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">
Employee: {{employee.full_name}}<br>Signature: _______________<br>Date: {{date.today}}
</div>
</div>',
'["company.name","employee.full_name","employee.number","date.today"]',1,1),

(@cat_comp,'Confidentiality Agreement','confidentiality_agreement','Employee confidentiality and non-disclosure agreement',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:center;margin-bottom:24px;"><strong>{{company.name}}</strong></div>
<h2 style="font-size:1rem;text-align:center;text-decoration:underline;margin-bottom:24px;">EMPLOYEE CONFIDENTIALITY AGREEMENT</h2>
<p>This Confidentiality Agreement is entered into between <strong>{{company.name}}</strong> ("the Company") and <strong>{{employee.full_name}}</strong> ("the Employee"), Employee No: {{employee.number}}, effective <strong>{{employee.start_date}}</strong>.</p>
<p style="margin-top:16px;"><strong>1. Confidential Information</strong><br>
The Employee agrees to keep confidential all trade secrets, business strategies, client information, financial data, personnel records, pricing information, and any other proprietary information of the Company.</p>
<p style="margin-top:12px;"><strong>2. Non-Disclosure</strong><br>
The Employee will not, without prior written consent, disclose any confidential information to any third party during or after the period of employment.</p>
<p style="margin-top:12px;"><strong>3. Return of Information</strong><br>
Upon termination of employment, the Employee will return all documents, data, and materials containing confidential information.</p>
<p style="margin-top:12px;"><strong>4. Duration</strong><br>
This agreement remains in force during employment and for a period of 2 years after termination.</p>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-top:40px;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">For: {{company.name}}<br>Signature: _______________<br>Date: {{date.today}}</div>
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Employee: {{employee.full_name}}<br>Signature: _______________<br>Date: {{date.today}}</div>
</div>
</div>',
'["company.name","employee.full_name","employee.number","employee.start_date","date.today"]',1,1),

(@cat_comp,'Conflict of Interest Declaration','conflict_of_interest','Employee declaration of any conflicts of interest',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="text-align:center;margin-bottom:24px;"><strong>{{company.name}}</strong></div>
<h2 style="font-size:1rem;text-align:center;text-decoration:underline;margin-bottom:24px;">DECLARATION OF CONFLICT OF INTEREST</h2>
<p>I, <strong>{{employee.full_name}}</strong>, Employee No: {{employee.number}}, employed as <strong>{{employee.position}}</strong>, hereby declare the following in connection with potential conflicts of interest:</p>
<div style="margin-top:16px;padding:16px;border:1px solid #e2e8f0;border-radius:8px;">
<p style="font-weight:600;">Please tick the applicable option:</p>
<div style="margin-top:12px;">☐ I have NO conflict of interest to declare.</div>
<div style="margin-top:8px;">☐ I have a conflict of interest to declare as follows:</div>
<div style="border-bottom:1px solid #cbd5e1;margin-top:24px;"></div>
<div style="border-bottom:1px solid #cbd5e1;margin-top:20px;"></div>
<div style="border-bottom:1px solid #cbd5e1;margin-top:20px;"></div>
</div>
<p style="margin-top:16px;font-size:0.85rem;color:#555;">I understand that it is my ongoing obligation to disclose any conflicts of interest that arise and that failure to do so may result in disciplinary action.</p>
<div style="margin-top:40px;border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">
Employee: {{employee.full_name}}<br>Signature: _______________<br>Date: {{date.today}}
</div>
</div>',
'["company.name","employee.full_name","employee.number","employee.position","date.today"]',1,1);

-- ============================================================
-- GENERAL (3 templates)
-- ============================================================
INSERT INTO doc_templates (category_id,title,slug,description,body_html,variables_used,version,created_by) VALUES

(@cat_gen,'General HR Memo','general_hr_memo','General purpose HR memorandum template',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="background:#f1f5f9;padding:16px;border-radius:8px;margin-bottom:24px;">
<p style="font-size:0.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#475569;">MEMORANDUM</p>
<table style="width:100%;font-size:0.85rem;margin-top:8px;">
<tr><td style="padding:3px 0;width:80px;color:#64748b;">TO:</td><td><strong>{{employee.full_name}}</strong> | {{employee.position}}</td></tr>
<tr><td style="padding:3px 0;color:#64748b;">FROM:</td><td><strong>Human Resources</strong> | {{company.name}}</td></tr>
<tr><td style="padding:3px 0;color:#64748b;">DATE:</td><td>{{date.today}}</td></tr>
<tr><td style="padding:3px 0;color:#64748b;">RE:</td><td><strong>[Subject of Memo]</strong></td></tr>
</table>
</div>
<p>[Body of memorandum. State the purpose, information, action required, and any deadlines clearly and concisely.]</p>
<p style="margin-top:16px;">[Additional paragraphs as needed.]</p>
<p style="margin-top:24px;">For queries, please contact Human Resources at {{company.email}} or {{company.phone}}.</p>
<div style="margin-top:40px;border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","company.email","company.phone","employee.full_name","employee.position","date.today"]',1,1),

(@cat_gen,'Employee Address Verification','address_verification','Confirms employee residential address',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;text-align:center;">
<p style="margin-bottom:4px;"><strong>{{company.name}}</strong></p>
<p style="font-size:0.85rem;color:#555;margin-bottom:32px;">{{company.address}}</p>
<h1 style="font-size:1.1rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">PROOF OF EMPLOYMENT AND ADDRESS</h1>
<div style="width:60px;height:3px;background:#1D4ED8;margin:12px auto 32px;"></div>
<div style="text-align:left;max-width:500px;margin:0 auto;">
<p>This is to confirm that <strong>{{employee.full_name}}</strong> (Employee No: {{employee.number}}) is employed at {{company.name}} as <strong>{{employee.position}}</strong> and resides at the following address:</p>
<div style="border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:16px 0;">
<p style="font-weight:600;">{{employee.address}}</p>
</div>
<p>This letter is issued at the request of the employee for official purposes.</p>
</div>
<div style="margin-top:48px;display:inline-block;text-align:left;min-width:220px;">
<div style="border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Authorised Signature<br>Human Resources<br>{{company.name}}<br>Date: {{date.today}}</div>
</div>
</div>',
'["company.name","company.address","employee.full_name","employee.number","employee.position","employee.address","date.today"]',1,1),

(@cat_gen,'Internal Announcement','internal_announcement','General staff announcement or notice',
'<div style="font-family:Georgia,serif;max-width:700px;margin:0 auto;">
<div style="border-bottom:3px solid #1D4ED8;padding-bottom:16px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:baseline;">
<strong>{{company.name}}</strong>
<span style="font-size:0.85rem;color:#555;">{{date.today}}</span>
</div>
<h2 style="font-size:1.1rem;margin-bottom:8px;">STAFF ANNOUNCEMENT</h2>
<p style="font-size:0.9rem;font-weight:600;color:#475569;margin-bottom:20px;">[Announcement Subject / Title]</p>
<p>Dear All,</p>
<p style="margin-top:12px;">[Opening paragraph — state the key message clearly.]</p>
<p style="margin-top:12px;">[Supporting information, context, or background details.]</p>
<p style="margin-top:12px;">[Any actions required, deadlines, or next steps.]</p>
<p style="margin-top:12px;">[Closing paragraph — thank the team, encourage questions.]</p>
<p style="margin-top:24px;">For further information, please contact Human Resources at {{company.email}}.</p>
<div style="margin-top:40px;border-top:1px solid #333;padding-top:8px;font-size:0.85rem;">Human Resources<br>{{company.name}}</div>
</div>',
'["company.name","company.email","date.today"]',1,1);

-- Count result
SELECT 'Templates inserted:' as status, COUNT(*) as total FROM doc_templates;
SELECT dc.name as category, COUNT(dt.id) as template_count
FROM doc_categories dc LEFT JOIN doc_templates dt ON dt.category_id=dc.id
GROUP BY dc.id, dc.name ORDER BY dc.sort_order;
