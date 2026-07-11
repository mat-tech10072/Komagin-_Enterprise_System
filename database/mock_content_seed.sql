-- ============================================================
-- KOMAGIN HR — COMPREHENSIVE MOCK CONTENT SEED
-- Fills all modules with realistic demo data
-- ============================================================
USE komagin_hr;

-- ── 1. Set employee salaries ──────────────────────────────────────────────
UPDATE employees SET basic_salary=8500,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0001';
UPDATE employees SET basic_salary=7500,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0002';
UPDATE employees SET basic_salary=4200,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0003';
UPDATE employees SET basic_salary=4200,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0004';
UPDATE employees SET basic_salary=6500,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0005';
UPDATE employees SET basic_salary=9000,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0006';
UPDATE employees SET basic_salary=5800,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0007';
UPDATE employees SET basic_salary=8200,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0008';
UPDATE employees SET basic_salary=5500,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0009';
UPDATE employees SET basic_salary=7000,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0010';
UPDATE employees SET basic_salary=5000,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0011';
UPDATE employees SET basic_salary=3800,  pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0012';
UPDATE employees SET basic_salary=15000, pay_frequency='monthly' WHERE employee_number='KOM-EMP-2026-0013';

-- Set start dates
UPDATE employees SET start_date='2024-01-15' WHERE employee_number='KOM-EMP-2026-0001';
UPDATE employees SET start_date='2023-03-01' WHERE employee_number='KOM-EMP-2026-0002';
UPDATE employees SET start_date='2025-01-06' WHERE employee_number='KOM-EMP-2026-0003';
UPDATE employees SET start_date='2025-02-03' WHERE employee_number='KOM-EMP-2026-0004';
UPDATE employees SET start_date='2022-08-15' WHERE employee_number='KOM-EMP-2026-0005';
UPDATE employees SET start_date='2020-04-01' WHERE employee_number='KOM-EMP-2026-0006';
UPDATE employees SET start_date='2023-06-12' WHERE employee_number='KOM-EMP-2026-0007';
UPDATE employees SET start_date='2019-10-07' WHERE employee_number='KOM-EMP-2026-0008';
UPDATE employees SET start_date='2024-03-04' WHERE employee_number='KOM-EMP-2026-0009';
UPDATE employees SET start_date='2021-11-22' WHERE employee_number='KOM-EMP-2026-0010';
UPDATE employees SET start_date='2022-02-14' WHERE employee_number='KOM-EMP-2026-0011';
UPDATE employees SET start_date='2025-03-10' WHERE employee_number='KOM-EMP-2026-0012';
UPDATE employees SET start_date='2018-06-01' WHERE employee_number='KOM-EMP-2026-0013';
UPDATE employees SET work_location='Head Office' WHERE department_id IN (1,2,6,8,10,11);
UPDATE employees SET work_location='Site Office'  WHERE department_id IN (4,9);
UPDATE employees SET work_location='Head Office'  WHERE department_id=3;

-- ── 2. Leave balances (all active employees, current year) ─────────────────
INSERT INTO leave_balances (employee_id, leave_type_id, year, entitled_days, used_days, pending_days, carried_forward, remaining_days)
SELECT e.id, lt.id, YEAR(NOW()), lt.max_days, 0, 0, 0, lt.max_days
FROM employees e CROSS JOIN leave_types lt
WHERE e.status IN ('active','probation')
ON DUPLICATE KEY UPDATE entitled_days=lt.max_days, remaining_days=lt.max_days;

-- Simulate some used days
UPDATE leave_balances lb
JOIN leave_types lt ON lb.leave_type_id=lt.id
SET lb.used_days=FLOOR(RAND()*6)+1,
    lb.remaining_days=lb.entitled_days-(FLOOR(RAND()*6)+1)
WHERE lt.code='AL' AND lb.remaining_days>10;

-- ── 3. ATTENDANCE — 22 working days (June 2026) ────────────────────────────
DROP PROCEDURE IF EXISTS seed_attendance_june;
DELIMITER $$
CREATE PROCEDURE seed_attendance_june()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_id INT;
    DECLARE v_num VARCHAR(30);
    DECLARE v_salary DECIMAL(12,2);

    DECLARE emp_cur CURSOR FOR
        SELECT id, employee_number, COALESCE(basic_salary,5000)
        FROM employees WHERE status IN ('active','probation') ORDER BY id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done=1;
    OPEN emp_cur;
    emp_loop: LOOP
        FETCH emp_cur INTO v_id, v_num, v_salary;
        IF done THEN LEAVE emp_loop; END IF;

        -- Generate attendance for each weekday in June 2026
        SET @d = '2026-06-01';
        WHILE @d <= '2026-06-27' DO
            -- Skip weekends
            IF DAYOFWEEK(@d) NOT IN (1,7) THEN
                SET @is_late   = IF(RAND()<0.15, 1, 0);
                SET @late_mins = IF(@is_late, FLOOR(RAND()*35)+5, 0);
                SET @sin       = ADDTIME('08:00:00', SEC_TO_TIME(@late_mins*60 + FLOOR(RAND()*300)));
                SET @bout      = '12:30:00';
                SET @bin       = ADDTIME('13:00:00', SEC_TO_TIME(FLOOR(RAND()*600)));
                SET @sout      = ADDTIME('17:00:00', SEC_TO_TIME(FLOOR(RAND()*90)*60));
                SET @brk_mins  = 60 + FLOOR(RAND()*15);
                SET @tot_hrs   = ROUND((TIME_TO_SEC(@sout)-TIME_TO_SEC(@sin)-@brk_mins*60)/3600, 2);
                SET @ot_hrs    = GREATEST(0, @tot_hrs - 8.00);

                INSERT IGNORE INTO attendance
                (employee_id, employee_number, attendance_date, sign_in, break_out, break_in, sign_out,
                 break_duration_minutes, total_hours_worked, normal_hours, overtime_hours,
                 is_late, late_minutes, status, is_approved, ip_address)
                VALUES
                (v_id, v_num, @d, @sin, @bout, @bin, @sout,
                 @brk_mins, @tot_hrs, LEAST(@tot_hrs,8.00), @ot_hrs,
                 @is_late, @late_mins,
                 IF(@is_late,'late','present'), 1, '127.0.0.1');
            END IF;
            SET @d = DATE_ADD(@d, INTERVAL 1 DAY);
        END WHILE;
    END LOOP;
    CLOSE emp_cur;
END $$
DELIMITER ;
CALL seed_attendance_june();
DROP PROCEDURE IF EXISTS seed_attendance_june;

-- ── 4. OVERTIME RECORDS (auto-generate from attendance OT hours) ────────────
INSERT IGNORE INTO overtime_records
(attendance_id, employee_id, overtime_date, suggested_hours, approved_hours, overtime_type, reason, status, reviewed_by, reviewed_at)
SELECT a.id, a.employee_id, a.attendance_date, a.overtime_hours, a.overtime_hours, 'authorized',
    'Project delivery requirement', 'approved', 1, NOW()
FROM attendance a WHERE a.overtime_hours > 0 LIMIT 40;

-- ── 5. LEAVE APPLICATIONS ──────────────────────────────────────────────────
INSERT INTO leave_applications
(employee_id, leave_type_id, start_date, end_date, total_days, reason, status, hr_status, hr_reviewed_by, hr_reviewed_at)
SELECT id, 1, '2026-05-12', '2026-05-14', 3, 'Annual leave — family event', 'approved', 'approved', 1, '2026-05-10 09:00:00'
FROM employees WHERE employee_number='KOM-EMP-2026-0001';

INSERT INTO leave_applications
(employee_id, leave_type_id, start_date, end_date, total_days, reason, status, hr_status, hr_reviewed_by, hr_reviewed_at)
SELECT id, 2, '2026-06-03', '2026-06-04', 2, 'Medical certificate attached', 'approved', 'approved', 1, '2026-06-02 08:30:00'
FROM employees WHERE employee_number='KOM-EMP-2026-0003';

INSERT INTO leave_applications
(employee_id, leave_type_id, start_date, end_date, total_days, reason, status, hr_status)
SELECT id, 1, '2026-07-07', '2026-07-11', 5, 'Annual leave — vacation', 'pending', 'pending'
FROM employees WHERE employee_number='KOM-EMP-2026-0005';

INSERT INTO leave_applications
(employee_id, leave_type_id, start_date, end_date, total_days, reason, status, hr_status)
SELECT id, 1, '2026-07-14', '2026-07-16', 3, 'Annual leave — personal matter', 'pending', 'pending'
FROM employees WHERE employee_number='KOM-EMP-2026-0007';

INSERT INTO leave_applications
(employee_id, leave_type_id, start_date, end_date, total_days, reason, status, hr_status, hr_reviewed_by, hr_reviewed_at)
SELECT id, 2, '2026-06-10', '2026-06-10', 1, 'Sick — doctor visit', 'approved', 'approved', 1, '2026-06-09 17:00:00'
FROM employees WHERE employee_number='KOM-EMP-2026-0009';

INSERT INTO leave_applications
(employee_id, leave_type_id, start_date, end_date, total_days, reason, status, hr_status, hr_reviewed_by, hr_reviewed_at)
SELECT id, 3, '2026-05-20', '2026-05-22', 3, 'Compassionate — bereavement', 'approved', 'approved', 1, '2026-05-19 10:00:00'
FROM employees WHERE employee_number='KOM-EMP-2026-0011';

INSERT INTO leave_applications
(employee_id, leave_type_id, start_date, end_date, total_days, reason, status, hr_status)
SELECT id, 6, '2026-08-04', '2026-08-08', 5, 'Study leave — Professional cert exam', 'pending', 'pending'
FROM employees WHERE employee_number='KOM-EMP-2026-0012';

-- Update leave balances for approved leaves
UPDATE leave_balances lb
SET lb.used_days=lb.used_days+3, lb.remaining_days=lb.remaining_days-3
WHERE lb.employee_id=(SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0001')
AND lb.leave_type_id=1;

-- ── 6. TIMESHEET CORRECTION REQUESTS ──────────────────────────────────────
INSERT INTO correction_requests
(employee_id, attendance_id, request_date, request_type, description, requested_sign_in, status, hr_remarks, reviewed_by, reviewed_at)
SELECT e.id, a.id, '2026-06-05', 'forgot_sign_in',
    'I forgot to sign in on arrival. Was on site from 07:45', '07:45:00', 'approved',
    'Verified with site supervisor', 1, '2026-06-06 09:00:00'
FROM employees e
JOIN attendance a ON a.employee_id=e.id AND a.attendance_date='2026-06-05'
WHERE e.employee_number='KOM-EMP-2026-0003' LIMIT 1;

INSERT INTO correction_requests
(employee_id, attendance_id, request_date, request_type, description, requested_sign_out, status)
SELECT e.id, a.id, '2026-06-18', 'forgot_sign_out',
    'Forgot to sign out — left at 18:30 after emergency meeting', '18:30:00', 'pending'
FROM employees e
JOIN attendance a ON a.employee_id=e.id AND a.attendance_date='2026-06-18'
WHERE e.employee_number='KOM-EMP-2026-0006' LIMIT 1;

-- ── 7. PAYROLL RUNS + PAYSLIPS (May and June 2026) ─────────────────────────
-- May 2026 — published
INSERT INTO payroll_runs (period_month, period_year, status, processed_by, finalized_at)
VALUES (5, 2026, 'published', 1, '2026-05-31 16:00:00');
SET @may_run = LAST_INSERT_ID();

INSERT INTO payslips
(employee_id, period_month, period_year, payroll_run_id, basic_salary, gross_salary,
 total_deductions, tax_amount, uif_employee, uif_employer, other_deductions, net_salary, status)
SELECT id, 5, 2026, @may_run,
    basic_salary, basic_salary,
    ROUND(basic_salary*0.27,2),
    ROUND(basic_salary*0.20,2),
    ROUND(basic_salary*0.01,2),
    ROUND(basic_salary*0.01,2),
    ROUND(basic_salary*0.05,2),
    ROUND(basic_salary*0.73,2),
    'sent'
FROM employees WHERE status IN ('active','probation')
ON DUPLICATE KEY UPDATE status='sent';

-- Update run totals
UPDATE payroll_runs SET
    total_gross=(SELECT SUM(gross_salary) FROM payslips WHERE payroll_run_id=@may_run),
    total_net=(SELECT SUM(net_salary) FROM payslips WHERE payroll_run_id=@may_run),
    total_deductions=(SELECT SUM(total_deductions) FROM payslips WHERE payroll_run_id=@may_run),
    employee_count=(SELECT COUNT(*) FROM payslips WHERE payroll_run_id=@may_run)
WHERE id=@may_run;

-- June 2026 — finalized (not yet published)
INSERT INTO payroll_runs (period_month, period_year, status, processed_by, finalized_at)
VALUES (6, 2026, 'finalized', 1, '2026-06-27 12:00:00');
SET @jun_run = LAST_INSERT_ID();

INSERT INTO payslips
(employee_id, period_month, period_year, payroll_run_id, basic_salary, gross_salary,
 total_deductions, tax_amount, uif_employee, uif_employer,
 overtime_hours, overtime_amount, other_deductions, net_salary, status)
SELECT e.id, 6, 2026, @jun_run,
    e.basic_salary,
    e.basic_salary + COALESCE(ot.ot_pay,0),
    ROUND((e.basic_salary + COALESCE(ot.ot_pay,0))*0.27,2),
    ROUND((e.basic_salary + COALESCE(ot.ot_pay,0))*0.20,2),
    ROUND((e.basic_salary + COALESCE(ot.ot_pay,0))*0.01,2),
    ROUND((e.basic_salary + COALESCE(ot.ot_pay,0))*0.01,2),
    COALESCE(ot.ot_hrs,0),
    COALESCE(ot.ot_pay,0),
    ROUND((e.basic_salary + COALESCE(ot.ot_pay,0))*0.05,2),
    ROUND((e.basic_salary + COALESCE(ot.ot_pay,0))*0.73,2),
    'finalized'
FROM employees e
LEFT JOIN (
    SELECT employee_id,
        ROUND(SUM(approved_hours),2) as ot_hrs,
        ROUND(SUM(approved_hours) * (e2.basic_salary/160) * 1.5, 2) as ot_pay
    FROM overtime_records ot2
    JOIN employees e2 ON e2.id=ot2.employee_id
    WHERE MONTH(overtime_date)=6 AND YEAR(overtime_date)=2026
    GROUP BY employee_id
) ot ON ot.employee_id=e.id
WHERE e.status IN ('active','probation')
ON DUPLICATE KEY UPDATE status='finalized';

UPDATE payroll_runs SET
    total_gross=(SELECT SUM(gross_salary) FROM payslips WHERE payroll_run_id=@jun_run),
    total_net=(SELECT SUM(net_salary) FROM payslips WHERE payroll_run_id=@jun_run),
    total_deductions=(SELECT SUM(total_deductions) FROM payslips WHERE payroll_run_id=@jun_run),
    employee_count=(SELECT COUNT(*) FROM payslips WHERE payroll_run_id=@jun_run)
WHERE id=@jun_run;

-- ── 8. PAYROLL DEDUCTIONS ──────────────────────────────────────────────────
INSERT INTO payroll_deductions
(employee_id, deduction_type, description, is_percentage, percentage, is_recurring, effective_from, is_active, created_by)
SELECT id, 'uif', 'UIF Employee Contribution (1%)', 1, 1.00, 1, '2026-01-01', 1, 1
FROM employees WHERE status IN ('active','probation')
ON DUPLICATE KEY UPDATE deduction_type=deduction_type;

INSERT INTO payroll_deductions
(employee_id, deduction_type, description, is_percentage, percentage, is_recurring, effective_from, is_active, created_by)
SELECT id, 'tax', 'PAYE Income Tax (20%)', 1, 20.00, 1, '2026-01-01', 1, 1
FROM employees WHERE status IN ('active','probation')
ON DUPLICATE KEY UPDATE deduction_type=deduction_type;

-- Medical aid for senior staff
INSERT INTO payroll_deductions
(employee_id, deduction_type, description, is_percentage, amount, is_recurring, effective_from, is_active, created_by)
SELECT id, 'medical_aid', 'Medical Aid — Employee Contribution', 0, 450.00, 1, '2026-01-01', 1, 1
FROM employees WHERE basic_salary >= 6000 AND status='active'
ON DUPLICATE KEY UPDATE deduction_type=deduction_type;

-- ── 9. SAVINGS / BENEFITS ──────────────────────────────────────────────────
INSERT INTO employee_savings
(employee_id, savings_type, fund_name, target_amount, current_balance,
 employee_rate_pct, employer_rate_pct, monthly_employee_contrib, monthly_employer_contrib,
 total_employee_contrib, total_employer_contrib, start_date, created_by)
SELECT e.id, 'pension', 'Komagin Pension Fund',
    e.basic_salary * 240,
    ROUND(e.basic_salary * 0.075 * (DATEDIFF(NOW(), e.start_date)/30), 2),
    7.5, 7.5,
    ROUND(e.basic_salary * 0.075, 2),
    ROUND(e.basic_salary * 0.075, 2),
    ROUND(e.basic_salary * 0.075 * (DATEDIFF(NOW(), e.start_date)/30), 2),
    ROUND(e.basic_salary * 0.075 * (DATEDIFF(NOW(), e.start_date)/30), 2),
    DATE_FORMAT(e.start_date, '%Y-%m-01'), 1
FROM employees e WHERE e.status IN ('active','probation')
ON DUPLICATE KEY UPDATE savings_type=savings_type;

-- Medical aid savings for senior staff
INSERT INTO employee_savings
(employee_id, savings_type, fund_name, target_amount, current_balance,
 monthly_employee_contrib, monthly_employer_contrib,
 total_employee_contrib, total_employer_contrib, start_date, created_by)
SELECT e.id, 'medical_aid', 'Pacific MMI Health Insurance',
    0, 0, 450.00, 450.00,
    ROUND(450 * (DATEDIFF(NOW(),e.start_date)/30), 2),
    ROUND(450 * (DATEDIFF(NOW(),e.start_date)/30), 2),
    DATE_FORMAT(e.start_date,'%Y-%m-01'), 1
FROM employees e WHERE e.basic_salary >= 6000 AND e.status='active'
ON DUPLICATE KEY UPDATE savings_type=savings_type;

-- ── 10. COMPANY ASSETS — assign to employees ──────────────────────────────
-- Ensure some assets exist
INSERT IGNORE INTO company_assets (asset_code, asset_type, description, serial_number, make_model, purchase_date, purchase_value, current_condition) VALUES
('ASSET-LT-001','laptop','Dell Latitude 5520','SN001001','Dell Latitude 5520','2024-01-10',3800.00,'good'),
('ASSET-LT-002','laptop','HP EliteBook 840','SN001002','HP EliteBook 840','2024-01-10',3500.00,'good'),
('ASSET-LT-003','laptop','Lenovo ThinkPad X1','SN001003','Lenovo ThinkPad X1','2023-06-01',5200.00,'excellent'),
('ASSET-LT-004','laptop','Dell XPS 15','SN001004','Dell XPS 15','2025-01-15',6000.00,'excellent'),
('ASSET-PH-001','phone','Samsung Galaxy A54','SN002001','Samsung A54','2024-03-01',1800.00,'good'),
('ASSET-PH-002','phone','iPhone 14','SN002002','Apple iPhone 14','2023-11-01',3500.00,'good'),
('ASSET-VC-001','vehicle','Toyota HiLux','ABC123PG','Toyota HiLux 2.8GD','2022-05-01',75000.00,'good'),
('ASSET-VC-002','vehicle','Mitsubishi Triton','XYZ456PG','Mitsubishi Triton','2021-02-01',65000.00,'fair'),
('ASSET-PPE-001','ppe','Hard Hat — Kohn','PPE001','MSA V-Gard','2025-01-01',85.00,'good'),
('ASSET-PPE-002','ppe','Safety Boots — Site','PPE002','Bova Safety','2025-01-01',180.00,'good'),
('ASSET-ID-001','id_card','ID Card — Zianna Koma','IDC001','Standard Issue','2026-01-01',15.00,'excellent'),
('ASSET-ID-002','id_card','ID Card — Taliban David','IDC002','Standard Issue','2026-01-01',15.00,'excellent');

-- Assign laptops to key staff
INSERT IGNORE INTO asset_assignments (asset_id, employee_id, issued_date, condition_on_issue, issued_by, acknowledgement, acknowledgement_date)
SELECT ca.id, e.id, '2026-01-15', 'good', 1, 1, '2026-01-15 09:00:00'
FROM company_assets ca, employees e WHERE ca.asset_code='ASSET-LT-001' AND e.employee_number='KOM-EMP-2026-0013';

INSERT IGNORE INTO asset_assignments (asset_id, employee_id, issued_date, condition_on_issue, issued_by, acknowledgement, acknowledgement_date)
SELECT ca.id, e.id, '2026-01-15', 'good', 1, 1, '2026-01-15 09:00:00'
FROM company_assets ca, employees e WHERE ca.asset_code='ASSET-LT-002' AND e.employee_number='KOM-EMP-2026-0002';

INSERT IGNORE INTO asset_assignments (asset_id, employee_id, issued_date, condition_on_issue, issued_by, acknowledgement, acknowledgement_date)
SELECT ca.id, e.id, '2026-01-15', 'excellent', 1, 1, '2026-01-15 09:00:00'
FROM company_assets ca, employees e WHERE ca.asset_code='ASSET-LT-003' AND e.employee_number='KOM-EMP-2026-0001';

INSERT IGNORE INTO asset_assignments (asset_id, employee_id, issued_date, condition_on_issue, issued_by, acknowledgement, acknowledgement_date)
SELECT ca.id, e.id, '2026-03-01', 'good', 1, 1, '2026-03-01 09:00:00'
FROM company_assets ca, employees e WHERE ca.asset_code='ASSET-PH-001' AND e.employee_number='KOM-EMP-2026-0010';

INSERT IGNORE INTO asset_assignments (asset_id, employee_id, issued_date, condition_on_issue, issued_by, acknowledgement, acknowledgement_date)
SELECT ca.id, e.id, '2026-03-01', 'good', 1, 1, '2026-03-01 09:00:00'
FROM company_assets ca, employees e WHERE ca.asset_code='ASSET-PH-002' AND e.employee_number='KOM-EMP-2026-0013';

INSERT IGNORE INTO asset_assignments (asset_id, employee_id, issued_date, condition_on_issue, issued_by, acknowledgement, acknowledgement_date)
SELECT ca.id, e.id, '2026-01-10', 'good', 1, 1, '2026-01-10 08:00:00'
FROM company_assets ca, employees e WHERE ca.asset_code='ASSET-PPE-001' AND e.employee_number='KOM-EMP-2026-0010';

UPDATE company_assets SET is_available=0
WHERE asset_code IN ('ASSET-LT-001','ASSET-LT-002','ASSET-LT-003','ASSET-PH-001','ASSET-PH-002','ASSET-PPE-001','ASSET-ID-001','ASSET-ID-002');

-- ── 11. PERFORMANCE REVIEWS ───────────────────────────────────────────────
INSERT INTO performance_reviews
(employee_id, reviewer_id, review_period, review_date, overall_score,
 self_assessment, supervisor_assessment, strengths, improvements, recommendation, recommendation_notes, status)
SELECT e.id, 1, 'Q1 2026', '2026-03-31',
    ROUND(3.2 + RAND()*1.6, 1),
    'I have consistently met my targets this quarter, contributing to team goals and maintaining good working relationships.',
    'Demonstrates strong commitment and technical competence. Consistently delivers quality work.',
    'Technical expertise, reliability, teamwork',
    'Time management on complex multi-task assignments',
    ELT(1+FLOOR(RAND()*3),'no_action','training','salary_review'),
    'Recommended based on Q1 performance assessment',
    'completed'
FROM employees e WHERE e.status='active' AND e.employee_number NOT IN ('KOM-EMP-2026-0012','KOM-EMP-2026-0013');

-- Zianna Koma (GM) — exceptional review
INSERT INTO performance_reviews
(employee_id, reviewer_id, review_period, review_date, overall_score,
 self_assessment, supervisor_assessment, strengths, improvements, recommendation, recommendation_notes, status)
SELECT e.id, 1, 'Q1 2026', '2026-03-31', 4.8,
    'Successfully steered the organisation through the Q1 strategic review and expansion. All KPIs achieved.',
    'Exceptional strategic leadership. The organisation has benefited significantly from her direction.',
    'Strategic vision, stakeholder management, decision-making, mentoring',
    'Delegation of operational tasks to enable more strategic focus',
    'salary_review',
    'Outstanding performance — recommend 10% salary review effective July 2026',
    'completed'
FROM employees e WHERE e.employee_number='KOM-EMP-2026-0013';

-- ── 12. DISCIPLINARY RECORDS ──────────────────────────────────────────────
INSERT INTO disciplinary_records
(employee_id, case_number, incident_date, incident_description, case_type, action_taken,
 investigation_notes, status, hearing_date, resolved_at, hr_officer_id, created_by)
SELECT e.id, CONCAT('DISC-2026-', LPAD(1,3,'0')),
    '2026-04-15',
    'Employee recorded arriving 45 minutes late on 5 separate occasions during March 2026 without prior notification or valid reason.',
    'absenteeism', 'verbal_warning',
    'Pattern of late arrivals confirmed via kiosk records. Employee acknowledged the issue and committed to improvement.',
    'closed', '2026-04-18', '2026-04-18',
    2, 1
FROM employees e WHERE e.employee_number='KOM-EMP-2026-0003';

INSERT INTO disciplinary_records
(employee_id, case_number, incident_date, incident_description, case_type, action_taken,
 investigation_notes, status, hr_officer_id, created_by)
SELECT e.id, CONCAT('DISC-2026-', LPAD(2,3,'0')),
    '2026-05-22',
    'Employee submitted a timesheet claiming 8 hours on a day when kiosk records show a sign-out at 14:30.',
    'misconduct', 'written_warning',
    'Discrepancy confirmed. Employee claims kiosk malfunction — not substantiated. Written warning issued.',
    'closed', 2, 1
FROM employees e WHERE e.employee_number='KOM-EMP-2026-0004';

INSERT INTO grievance_records
(employee_id, case_number, filed_date, complaint_description, grievance_type,
 assigned_hr_officer, investigation_notes, resolution, status, resolved_at)
SELECT e.id, CONCAT('GRIEV-2026-', LPAD(1,3,'0')),
    '2026-06-10',
    'Employee raises concern about inequitable distribution of overtime assignments within the Engineering department.',
    'Workplace fairness',
    2,
    'Meeting held with department head. Overtime rotation schedule to be formalised.',
    'Rotation schedule introduced from July 2026. Employee satisfied with outcome.',
    'resolved', '2026-06-20'
FROM employees e WHERE e.employee_number='KOM-EMP-2026-0005';

-- ── 13. RECRUITMENT ───────────────────────────────────────────────────────
INSERT INTO recruitment_vacancies
(job_title, department_id, employment_type, description, requirements, responsibilities, status, positions_available, deadline, created_by)
VALUES
('Quantity Surveyor', 9, 'full_time',
 'We are seeking a qualified Quantity Surveyor to join our Survey team.',
 'Degree in Quantity Surveying, 3+ years experience, proficient in CostX',
 'Cost estimation, bill of quantities, tender preparation, site measurement',
 'open', 1, '2026-08-31', 1),
('Junior Accountant', 2, 'full_time',
 'Entry-level accountant position within the Finance team.',
 'Accounting degree or equivalent, knowledge of MYOB preferred',
 'Accounts payable/receivable, bank reconciliations, monthly reporting support',
 'open', 1, '2026-07-31', 1),
('Site Engineer', 4, 'contract',
 'Contract site engineer required for the Eastern Highway Project.',
 'Civil Engineering degree, 5+ years site experience, valid PNGEngineers license',
 'Daily site supervision, quality control, progress reporting, safety compliance',
 'open', 2, '2026-08-15', 1);

SET @vac1 = (SELECT id FROM recruitment_vacancies WHERE job_title='Quantity Surveyor' LIMIT 1);
SET @vac2 = (SELECT id FROM recruitment_vacancies WHERE job_title='Junior Accountant' LIMIT 1);
SET @vac3 = (SELECT id FROM recruitment_vacancies WHERE job_title='Site Engineer' LIMIT 1);

INSERT INTO recruitment_applications
(vacancy_id, application_number, first_name, last_name, email, phone, current_position, current_employer, years_experience, qualifications, cover_letter, status, created_at)
VALUES
(@vac1, 'APP-2026-001', 'Peter', 'Teine', 'peter.teine@email.com', '+675 7123 4001', 'Junior QS', 'PNG Roads Authority', 4, 'BSc Quantity Surveying, UTECH', 'I am excited to apply for this position...', 'shortlisted', NOW()),
(@vac1, 'APP-2026-002', 'Maria', 'Geno', 'maria.geno@email.com', '+675 7123 4002', 'QS Technician', 'BuildCo PNG', 2, 'Diploma in QS, UNITECH', 'My experience in construction measurement...', 'reviewing', NOW()),
(@vac1, 'APP-2026-003', 'Henry', 'Kaupa', 'henry.kaupa@email.com', '+675 7123 4003', 'Assistant QS', 'Pacific Contractors', 3, 'BSc QS, Divine Word University', 'With 3 years in QS practice...', 'interview_scheduled', NOW()),
(@vac2, 'APP-2026-004', 'Grace', 'Peke', 'grace.peke@email.com', '+675 7123 4004', 'Accounts Clerk', 'PNG Harbours Limited', 2, 'Diploma Accounting, IBSAT', 'I am a detail-oriented accounting professional...', 'shortlisted', NOW()),
(@vac2, 'APP-2026-005', 'Thomas', 'Warr', 'thomas.warr@email.com', '+675 7123 4005', 'Graduate Accountant', 'KPMG PNG', 1, 'BCom Accounting, UPNG', 'As a recent graduate with KPMG experience...', 'submitted', NOW()),
(@vac3, 'APP-2026-006', 'James', 'Poi', 'james.poi@email.com', '+675 7123 4006', 'Civil Engineer', 'NDB PNG', 6, 'BE Civil, UNITECH', 'I have 6 years of site engineering experience...', 'shortlisted', NOW()),
(@vac3, 'APP-2026-007', 'Anna', 'Kolo', 'anna.kolo@email.com', '+675 7123 4007', 'Site Supervisor', 'Highland Mining', 8, 'BE Civil, UNITECH', 'My 8 years in mining and infrastructure...', 'interview_scheduled', NOW());

-- ── 14. TRAINING PROGRAMS ─────────────────────────────────────────────────
INSERT INTO training_programs (title, provider, description, start_date, end_date, cost, location, status, created_by) VALUES
('Workplace Health & Safety Awareness', 'SafeWork PNG', 'Annual WHS compliance training for all staff', '2026-02-10', '2026-02-10', 850.00, 'Head Office — Boardroom', 'completed', 1),
('AutoCAD Fundamentals for Drafting', 'PNG Technical Institute', 'CAD software skills for draftsmen', '2026-03-03', '2026-03-07', 2800.00, 'Port Moresby — PTI Campus', 'completed', 1),
('Project Management Professional (PMP) Prep', 'PM Institute Online', 'PMP examination preparation course', '2026-04-14', '2026-05-16', 5500.00, 'Online (remote)', 'completed', 1),
('Advanced Excel & Financial Reporting', 'Business Skills PNG', 'Excel for finance and reporting', '2026-05-19', '2026-05-20', 1200.00, 'Head Office — Training Room', 'completed', 1),
('Leadership Essentials for Supervisors', 'Pacific Leadership Academy', 'Leadership and team management skills', '2026-07-07', '2026-07-09', 4200.00, 'Port Moresby — Ela Beach Hotel', 'planned', 1),
('ISO 9001 Quality Management Awareness', 'Bureau Veritas PNG', 'Quality management system introduction', '2026-08-11', '2026-08-11', 950.00, 'Head Office', 'planned', 1);

SET @t1=(SELECT id FROM training_programs WHERE title='Workplace Health & Safety Awareness');
SET @t2=(SELECT id FROM training_programs WHERE title='AutoCAD Fundamentals for Drafting');
SET @t3=(SELECT id FROM training_programs WHERE title='Project Management Professional (PMP) Prep');
SET @t4=(SELECT id FROM training_programs WHERE title='Advanced Excel & Financial Reporting');

-- WHS: all staff
INSERT IGNORE INTO training_attendance (training_id, employee_id, attended)
SELECT @t1, id, 1 FROM employees WHERE status='active';

-- AutoCAD: draftsmen + engineers
INSERT IGNORE INTO training_attendance (training_id, employee_id, attended)
SELECT @t2, id, 1 FROM employees WHERE department_id=4 AND status='active';

-- PMP: management + senior staff
INSERT IGNORE INTO training_attendance (training_id, employee_id, attended)
SELECT @t3, id, 1 FROM employees WHERE basic_salary >= 7000 AND status='active';

-- Excel: finance + admin
INSERT IGNORE INTO training_attendance (training_id, employee_id, attended)
SELECT @t4, id, 1 FROM employees WHERE department_id IN (2,6) AND status='active';

-- ── 15. ONBOARDING CHECKLISTS ─────────────────────────────────────────────
-- For the two newest employees (Benson Barua & David Max — joined Jan/Feb 2026)
SET @e3=(SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0003');
SET @e4=(SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0004');
SET @e12=(SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0012');

INSERT IGNORE INTO onboarding_checklists (employee_id, task_name, category, is_completed, completed_by, completed_at, due_date) VALUES
(@e3,'Signed employment contract','Documentation',1,1,'2026-01-06 09:00:00','2026-01-06'),
(@e3,'IT access and email setup','IT Setup',1,1,'2026-01-06 10:30:00','2026-01-06'),
(@e3,'Company induction completed','Orientation',1,1,'2026-01-07 16:00:00','2026-01-07'),
(@e3,'Health & Safety briefing','Compliance',1,1,'2026-01-07 14:00:00','2026-01-07'),
(@e3,'POPI acknowledgement signed','Compliance',1,1,'2026-01-08 09:00:00','2026-01-08'),
(@e3,'Uniform and PPE issued','Equipment',1,1,'2026-01-08 11:00:00','2026-01-08'),
(@e3,'30-day probation check-in','Performance',1,1,'2026-02-07 14:00:00','2026-02-07'),
(@e4,'Signed employment contract','Documentation',1,1,'2026-02-03 09:00:00','2026-02-03'),
(@e4,'IT access and email setup','IT Setup',1,1,'2026-02-03 11:00:00','2026-02-03'),
(@e4,'Company induction completed','Orientation',1,1,'2026-02-04 16:00:00','2026-02-04'),
(@e4,'Health & Safety briefing','Compliance',1,1,'2026-02-04 14:00:00','2026-02-04'),
(@e4,'30-day probation check-in','Performance',0,NULL,NULL,'2026-03-05'),
(@e12,'Signed employment contract','Documentation',1,1,'2025-03-10 09:00:00','2025-03-10'),
(@e12,'IT access and email setup','IT Setup',1,1,'2025-03-10 11:00:00','2025-03-10'),
(@e12,'Company induction completed','Orientation',1,1,'2025-03-11 16:00:00','2025-03-11'),
(@e12,'POPI acknowledgement signed','Compliance',1,1,'2025-03-11 09:00:00','2025-03-11'),
(@e12,'60-day performance review','Performance',1,1,'2025-05-12 14:00:00','2025-05-12');

-- ── 16. EMPLOYEE REQUESTS (Hub) ───────────────────────────────────────────
INSERT INTO employee_requests
(employee_id, request_type, subject, description, priority, status, hr_response, resolved_at, created_at)
SELECT e.id, 'payslip_query', 'May 2026 payslip — overtime query',
    'My May payslip does not reflect 6.5 hours of approved overtime worked on 2026-05-23. Please review.',
    'normal', 'resolved',
    'Payslip recalculated. OT of 6.5 hrs at K92/hr added. Amended payslip issued.', NOW(), DATE_SUB(NOW(), INTERVAL 12 DAY)
FROM employees e WHERE e.employee_number='KOM-EMP-2026-0005';

INSERT INTO employee_requests
(employee_id, request_type, subject, description, priority, status, hr_response, resolved_at, created_at)
SELECT e.id, 'employment_certificate', 'Employment certificate — BSP home loan application',
    'I require an official employment certificate confirming my employment, salary, and length of service for a BSP home loan application. Please issue as soon as possible.',
    'high', 'resolved',
    'Employment certificate generated and provided to employee on 2026-06-15.', NOW(), DATE_SUB(NOW(), INTERVAL 15 DAY)
FROM employees e WHERE e.employee_number='KOM-EMP-2026-0008';

INSERT INTO employee_requests
(employee_id, request_type, subject, description, priority, status, created_at)
SELECT e.id, 'leave_query', 'Leave balance discrepancy — Annual Leave',
    'My portal shows 15 days AL remaining but I calculated I should have 18 days. I took 3 days in May which was approved. Please clarify.',
    'normal', 'in_progress', DATE_SUB(NOW(), INTERVAL 5 DAY)
FROM employees e WHERE e.employee_number='KOM-EMP-2026-0007';

INSERT INTO employee_requests
(employee_id, request_type, subject, description, priority, status, created_at)
SELECT e.id, 'bank_update', 'Bank account update — BSP to Kina Bank',
    'I have changed my salary bank account from BSP to Kina Bank. New account: 2001234567, Branch Code: 002. Please update records effective July payroll.',
    'high', 'open', DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM employees e WHERE e.employee_number='KOM-EMP-2026-0011';

INSERT INTO employee_requests
(employee_id, request_type, subject, description, priority, status, created_at)
SELECT e.id, 'training_request', 'Request to attend Leadership Training — July 2026',
    'I would like to be considered for the Leadership Essentials training scheduled for July 7–9. I believe it will significantly develop my supervisory skills.',
    'normal', 'open', DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM employees e WHERE e.employee_number='KOM-EMP-2026-0006';

INSERT INTO employee_requests
(employee_id, request_type, subject, description, priority, status, hr_response, resolved_at, created_at)
SELECT e.id, 'general_query', 'Annual performance review — when will I receive Q2 results?',
    'Could you please advise when Q2 2026 performance reviews will be conducted and when results will be communicated to staff?',
    'low', 'resolved',
    'Q2 performance reviews are scheduled for July 31 – August 8, 2026. You will be notified by HR at least one week prior.',
    NOW(), DATE_SUB(NOW(), INTERVAL 8 DAY)
FROM employees e WHERE e.employee_number='KOM-EMP-2026-0009';

-- ── 17. ARCHIVE RECORDS ───────────────────────────────────────────────────
INSERT IGNORE INTO archive_records
(archive_type, year, month, document_type, title, generated_by, is_locked, locked_by, locked_at, generated_at) VALUES
('monthly', 2026, 5, 'attendance', 'Attendance Summary — May 2026', 1, 1, 1, '2026-06-02 09:00:00', '2026-06-02 08:30:00'),
('monthly', 2026, 5, 'leave_report', 'Leave Report — May 2026', 1, 1, 1, '2026-06-02 09:30:00', '2026-06-02 08:45:00'),
('monthly', 2026, 5, 'overtime_report', 'Overtime Report — May 2026', 1, 1, 1, '2026-06-02 10:00:00', '2026-06-02 09:00:00'),
('monthly', 2026, 5, 'payroll_support', 'Payroll Support Pack — May 2026', 1, 1, 1, '2026-06-02 10:30:00', '2026-06-02 09:30:00'),
('monthly', 2026, 5, 'hr_summary', 'HR Monthly Summary — May 2026', 1, 1, 1, '2026-06-02 11:00:00', '2026-06-02 10:00:00'),
('quarterly', 2026, NULL, 'timesheets', 'Q1 2026 Timesheet Archive', 1, 1, 1, '2026-04-05 09:00:00', '2026-04-05 08:00:00'),
('quarterly', 2026, NULL, 'attendance', 'Q1 2026 Attendance Archive', 1, 1, 1, '2026-04-05 10:00:00', '2026-04-05 08:30:00'),
('yearly', 2025, NULL, 'hr_summary', 'Annual HR Summary 2025', 1, 1, 1, '2026-01-15 09:00:00', '2026-01-15 08:00:00'),
('yearly', 2025, NULL, 'employee_list', 'Employee Register 2025', 1, 1, 1, '2026-01-15 10:00:00', '2026-01-15 08:30:00');

-- ── 18. NOTIFICATIONS ─────────────────────────────────────────────────────
INSERT INTO notifications (user_id, type, title, message, link, is_read)
SELECT u.id, 'success', 'Payroll Published — May 2026',
    'May 2026 payroll has been published. Payslips are now visible in employee portals.',
    '/modules/payroll/index.php', 0
FROM users u WHERE u.role IN ('hr_manager','payroll_officer') LIMIT 2;

INSERT INTO notifications (user_id, type, title, message, link, is_read)
SELECT u.id, 'warning', '7 Leave Applications Awaiting Review',
    'There are pending leave applications requiring HR Manager approval.',
    '/modules/leave/index.php', 0
FROM users u WHERE u.role IN ('hr_manager','super_admin') LIMIT 2;

INSERT INTO notifications (user_id, type, title, message, link, is_read)
SELECT u.id, 'info', 'New Employee Hub Requests',
    '5 employee requests have been submitted and require HR response.',
    '/modules/hub/index.php', 0
FROM users u WHERE u.role IN ('hr_manager','hr_officer') LIMIT 2;

INSERT INTO notifications (user_id, type, title, message, link, is_read)
SELECT u.id, 'info', '3 New Recruitment Applications',
    'New applications received for open vacancies. Please review.',
    '/modules/recruitment/index.php', 1
FROM users u WHERE u.role IN ('hr_manager','super_admin') LIMIT 2;

-- ── 19. AUDIT LOG ENTRIES ─────────────────────────────────────────────────
INSERT INTO audit_logs (user_id, user_name, module, action, record_id, reason, ip_address, created_at)
VALUES
(1,'superadmin','payroll','publish_run',@may_run,'May 2026 payroll published — all 13 payslips sent','127.0.0.1',DATE_SUB(NOW(),INTERVAL 28 DAY)),
(1,'superadmin','employees','create',1,'New employee onboarded — Taliban David','127.0.0.1',DATE_SUB(NOW(),INTERVAL 165 DAY)),
(1,'superadmin','employees','create',2,'New employee onboarded — Charles Richard','127.0.0.1',DATE_SUB(NOW(),INTERVAL 115 DAY)),
(1,'superadmin','leave','approve',1,'Leave approved — Taliban David — 3 days annual','127.0.0.1',DATE_SUB(NOW(),INTERVAL 48 DAY)),
(1,'superadmin','disciplinary','create',1,'Verbal warning issued — Benson Barua — late arrivals','127.0.0.1',DATE_SUB(NOW(),INTERVAL 73 DAY)),
(1,'superadmin','documents','generate_document',1,'Employment cert generated for Frank David','127.0.0.1',DATE_SUB(NOW(),INTERVAL 12 DAY)),
(1,'superadmin','recruitment','manage_recruitment',@vac1,'New vacancy posted — Quantity Surveyor','127.0.0.1',DATE_SUB(NOW(),INTERVAL 30 DAY)),
(1,'superadmin','settings','update_theme',1,'System theme updated — sidebar colour adjusted','127.0.0.1',DATE_SUB(NOW(),INTERVAL 2 DAY));

-- ── VERIFICATION ──────────────────────────────────────────────────────────
SELECT 'Data seeded successfully' as status;
SELECT 'employees with salary' as metric, COUNT(*) as count FROM employees WHERE basic_salary IS NOT NULL
UNION ALL SELECT 'attendance records', COUNT(*) FROM attendance
UNION ALL SELECT 'leave applications', COUNT(*) FROM leave_applications
UNION ALL SELECT 'payroll runs', COUNT(*) FROM payroll_runs
UNION ALL SELECT 'payslips', COUNT(*) FROM payslips
UNION ALL SELECT 'payroll deductions', COUNT(*) FROM payroll_deductions
UNION ALL SELECT 'savings records', COUNT(*) FROM employee_savings
UNION ALL SELECT 'asset assignments', COUNT(*) FROM asset_assignments
UNION ALL SELECT 'overtime records', COUNT(*) FROM overtime_records
UNION ALL SELECT 'correction requests', COUNT(*) FROM correction_requests
UNION ALL SELECT 'performance reviews', COUNT(*) FROM performance_reviews
UNION ALL SELECT 'disciplinary cases', COUNT(*) FROM disciplinary_records
UNION ALL SELECT 'grievances', COUNT(*) FROM grievance_records
UNION ALL SELECT 'recruitment vacancies', COUNT(*) FROM recruitment_vacancies
UNION ALL SELECT 'applications', COUNT(*) FROM recruitment_applications
UNION ALL SELECT 'training programs', COUNT(*) FROM training_programs
UNION ALL SELECT 'training attendance', COUNT(*) FROM training_attendance
UNION ALL SELECT 'onboarding tasks', COUNT(*) FROM onboarding_checklists
UNION ALL SELECT 'employee requests', COUNT(*) FROM employee_requests
UNION ALL SELECT 'archive records', COUNT(*) FROM archive_records
UNION ALL SELECT 'notifications', COUNT(*) FROM notifications
UNION ALL SELECT 'audit log entries', COUNT(*) FROM audit_logs;
