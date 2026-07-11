-- ============================================================
-- PHASE 7 â€” REALISTIC TEST DATA
-- Run: mysql -u root komagin_hr < phase7_test_data.sql
-- ============================================================
USE komagin_hr;

-- ============================================================
-- EMPLOYEES (20 employees across 7 departments)
-- All passwords for portal: Admin@123
-- Kiosk PINs: 1234 (bcrypt)
-- ============================================================
SET @pin_hash  = '$2y$10$Ad.cRs9VYqt50aDnlCc5aO7o01ueEOkaS2a6SedC1vzODE1seN83S'; -- Admin@123
SET @portal_pw = '$2y$10$Ad.cRs9VYqt50aDnlCc5aO7o01ueEOkaS2a6SedC1vzODE1seN83S'; -- Admin@123

INSERT IGNORE INTO `employees`
(employee_number, first_name, last_name, date_of_birth, gender, marital_status,
 national_id, nationality, email, phone,
 residential_address, city, country,
 department_id, position_id, supervisor_id,
 employment_type, status, start_date, basic_salary, pay_frequency, work_location,
 bank_name, bank_account_number, bank_branch_code, bank_account_type,
 emergency_contact_name, emergency_contact_relation, emergency_contact_phone,
 nok_name, nok_relation, nok_phone,
 kiosk_pin, portal_password, portal_active, portal_policy_agreed,
 created_at)
VALUES

-- HR Department
('KOM-EMP-2026-0001','Sarah','Mokoena','1988-03-14','female','married',
 '8803145012087','Papua New Guinean','sarah.mokoena@komagin.com','0821234567',
 '14 Acacia Street, Sandton','Port Moresby','Papua New Guinea',
 1,1,NULL,'full_time','active','2021-01-10',55000,'monthly','Head Office',
 'FNB','62001234567','250655','cheque',
 'James Mokoena','Spouse','0831234567',
 'James Mokoena','Spouse','0831234567',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0002','Thabo','Nkosi','1990-07-22','male','single',
 '9007225034089','Papua New Guinean','thabo.nkosi@komagin.com','0731234568',
 '8 Olive Road, Midrand','Port Moresby','Papua New Guinea',
 1,2,1,'full_time','active','2022-03-01',38000,'monthly','Head Office',
 'ABSA','4098765432','632005','cheque',
 'Maria Nkosi','Mother','0839876543',
 'Maria Nkosi','Mother','0839876543',
 @pin_hash, @portal_pw, 1, 1, NOW()),

-- Finance Department
('KOM-EMP-2026-0003','Priya','Pillay','1985-11-30','female','married',
 '8511305234087','Papua New Guinean','priya.pillay@komagin.com','0821234569',
 '22 Jacaranda Ave, Pretoria','Lae','Papua New Guinea',
 2,3,NULL,'full_time','active','2019-05-15',72000,'monthly','Head Office',
 'Standard Bank','012345678901','051001','savings',
 'Raj Pillay','Spouse','0831234569',
 'Raj Pillay','Spouse','0831234569',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0004','David','van der Berg','1993-04-18','male','single',
 '9304185014082','Papua New Guinean','david.vdberg@komagin.com','0731234570',
 '5 Pine Close, Centurion','Lae','Papua New Guinea',
 2,4,3,'full_time','active','2023-01-09',32000,'monthly','Head Office',
 'Capitec','1234567890','470010','savings',
 'Anne van der Berg','Mother','0831234570',
 'Anne van der Berg','Mother','0831234570',
 @pin_hash, @portal_pw, 1, 1, NOW()),

-- Operations Department
('KOM-EMP-2026-0005','Sipho','Dlamini','1982-09-05','male','married',
 '8209055028082','Papua New Guinean','sipho.dlamini@komagin.com','0821234571',
 '67 Mango Street, Roodepoort','Port Moresby','Papua New Guinea',
 3,5,NULL,'full_time','active','2018-07-20',68000,'monthly','Operations Centre',
 'Nedbank','1108765432','198765','cheque',
 'Zanele Dlamini','Spouse','0831234571',
 'Zanele Dlamini','Spouse','0831234571',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0006','Nomsa','Cele','1995-12-10','female','single',
 '9512100184082','Papua New Guinean','nomsa.cele@komagin.com','0731234572',
 '3 Sunflower Road, Germiston','Port Moresby','Papua New Guinea',
 3,6,5,'full_time','active','2023-06-01',29000,'monthly','Operations Centre',
 'FNB','62009876543','250655','cheque',
 'Bongi Cele','Mother','0831234572',
 'Bongi Cele','Mother','0831234572',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0007','Michael','Peters','1987-02-25','male','married',
 '8702255034085','Papua New Guinean','michael.peters@komagin.com','0821234573',
 '18 Rose Avenue, Boksburg','Port Moresby','Papua New Guinea',
 3,6,5,'full_time','active','2020-11-03',28500,'monthly','Operations Centre',
 'ABSA','4001234567','632005','cheque',
 'Linda Peters','Spouse','0831234573',
 'Linda Peters','Spouse','0831234573',
 @pin_hash, @portal_pw, 1, 1, NOW()),

-- Engineering Department
('KOM-EMP-2026-0008','Ayesha','Karriem','1989-06-17','female','married',
 '8906175098082','Papua New Guinean','ayesha.karriem@komagin.com','0821234574',
 '42 Protea Place, Randburg','Port Moresby','Papua New Guinea',
 4,7,NULL,'full_time','active','2017-03-01',85000,'monthly','Engineering Hub',
 'Standard Bank','012345111111','051001','savings',
 'Yusuf Karriem','Spouse','0831234574',
 'Yusuf Karriem','Spouse','0831234574',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0009','James','Ndlovu','1991-08-28','male','single',
 '9108285074081','Papua New Guinean','james.ndlovu@komagin.com','0731234575',
 '9 Fern Road, Fourways','Port Moresby','Papua New Guinea',
 4,8,8,'full_time','active','2021-09-15',58000,'monthly','Engineering Hub',
 'Capitec','1234509876','470010','savings',
 'Grace Ndlovu','Mother','0831234575',
 'Grace Ndlovu','Mother','0831234575',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0010','Lerato','Khumalo','1994-01-12','female','probation',
 '9401125069082','Papua New Guinean','lerato.khumalo@komagin.com','0731234576',
 '7 Violet Street, Midrand','Port Moresby','Papua New Guinea',
 4,8,8,'full_time','probation','2026-01-06',48000,'monthly','Engineering Hub',
 'FNB','62002345678','250655','cheque',
 'Peter Khumalo','Father','0831234576',
 'Peter Khumalo','Father','0831234576',
 @pin_hash, @portal_pw, 1, 1, NOW()),

-- Workshop Department
('KOM-EMP-2026-0011','Bongani','Moyo','1980-05-22','male','married',
 '8005225098083','Papua New Guinean','bongani.moyo@komagin.com','0821234577',
 '15 Ntombi Road, Springs','Port Moresby','Papua New Guinea',
 5,9,NULL,'full_time','active','2015-02-16',62000,'monthly','Workshop',
 'Nedbank','1102345678','198765','cheque',
 'Thandi Moyo','Spouse','0831234577',
 'Thandi Moyo','Spouse','0831234577',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0012','Riaan','Coetzee','1986-10-08','male','married',
 '8610085012081','Papua New Guinean','riaan.coetzee@komagin.com','0821234578',
 '28 Baobab Lane, Vanderbijlpark','Mt Hagen','Papua New Guinea',
 5,10,11,'full_time','active','2019-08-01',35000,'monthly','Workshop',
 'ABSA','4007654321','632005','cheque',
 'Elna Coetzee','Spouse','0831234578',
 'Elna Coetzee','Spouse','0831234578',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0013','Zanele','Sithole','1997-03-30','female','single',
 '9703305034089','Papua New Guinean','zanele.sithole@komagin.com','0731234579',
 '2 Mopane Close, Sebokeng','Mt Hagen','Papua New Guinea',
 5,10,11,'full_time','active','2024-01-15',27000,'monthly','Workshop',
 'Capitec','1234560123','470010','savings',
 'Beauty Sithole','Mother','0831234579',
 'Beauty Sithole','Mother','0831234579',
 @pin_hash, @portal_pw, 1, 1, NOW()),

-- Administration Department
('KOM-EMP-2026-0014','Faizel','Abrahams','1983-07-14','male','married',
 '8307145069082','Papua New Guinean','faizel.abrahams@komagin.com','0821234580',
 '33 Tulip Street, Bloemfontein','Madang','Papua New Guinea',
 6,11,1,'full_time','active','2016-04-01',42000,'monthly','Head Office',
 'Standard Bank','012345222222','051001','cheque',
 'Soraya Abrahams','Spouse','0831234580',
 'Soraya Abrahams','Spouse','0831234580',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0015','Cindy','Booysen','1992-09-03','female','single',
 '9209035012089','Papua New Guinean','cindy.booysen@komagin.com','0731234581',
 '6 Daisy Road, East London','Goroka','Papua New Guinea',
 6,11,1,'full_time','active','2022-07-11',31000,'monthly','Branch Office',
 'FNB','62003456789','250655','cheque',
 'Tom Booysen','Father','0831234581',
 'Tom Booysen','Father','0831234581',
 @pin_hash, @portal_pw, 1, 1, NOW()),

-- Project Management
('KOM-EMP-2026-0016','Kehinde','Okonkwo','1979-11-18','male','married',
 NULL,'Nigerian','kehinde.okonkwo@komagin.com','0821234582',
 '19 Willow Avenue, Sandton','Port Moresby','Papua New Guinea',
 7,12,NULL,'contract','active','2024-06-01',95000,'monthly','Head Office',
 'FNB','62004567890','250655','cheque',
 'Ngozi Okonkwo','Spouse','0831234582',
 'Ngozi Okonkwo','Spouse','0831234582',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0017','Aisha','Mahlangu','1996-04-25','female','single',
 '9604255034087','Papua New Guinean','aisha.mahlangu@komagin.com','0731234583',
 '11 Cedar Close, Polokwane','Kokopo','Papua New Guinea',
 7,12,16,'full_time','active','2025-02-03',52000,'monthly','Head Office',
 'ABSA','4009876543','632005','savings',
 'Samuel Mahlangu','Father','0831234583',
 'Samuel Mahlangu','Father','0831234583',
 @pin_hash, @portal_pw, 1, 1, NOW()),

-- Part-time and suspended examples
('KOM-EMP-2026-0018','Piet','Joubert','1984-06-30','male','divorced',
 '8406305034083','Papua New Guinean','piet.joubert@komagin.com','0821234584',
 '45 Yellowwood Street, Krugersdorp','Kimbe','Papua New Guinea',
 3,6,5,'part_time','active','2023-03-20',18000,'monthly','Operations Centre',
 'Nedbank','1103456789','198765','cheque',
 'Hein Joubert','Brother','0831234584',
 'Hein Joubert','Brother','0831234584',
 @pin_hash, @portal_pw, 1, 1, NOW()),

('KOM-EMP-2026-0019','Lungelo','Zwane','1988-01-15','male','married',
 '8801155034082','Papua New Guinean','lungelo.zwane@komagin.com','0821234585',
 '3 Banana Road, KwaMashu','Wewak','Papua New Guinea',
 5,10,11,'full_time','suspended','2020-06-15',33000,'monthly','Workshop',
 'FNB','62005678901','250655','cheque',
 'Nokuthula Zwane','Spouse','0831234585',
 'Nokuthula Zwane','Spouse','0831234585',
 @pin_hash, @portal_pw, 1, 0, NOW()),

('KOM-EMP-2026-0020','Estelle','Ferreira','1990-08-20','female','married',
 '9008205034088','Papua New Guinean','estelle.ferreira@komagin.com','0821234586',
 '9 Magnolia Lane, George','George','Papua New Guinea',
 2,4,3,'full_time','resigned','2020-04-01',36000,'monthly','Head Office',
 'Standard Bank','012345333333','051001','savings',
 'Hendrik Ferreira','Spouse','0831234586',
 'Hendrik Ferreira','Spouse','0831234586',
 @pin_hash, @portal_pw, 0, 0, '2025-11-30');

-- ============================================================
-- USER ACCOUNTS for employees (portal access)
-- ============================================================
INSERT INTO `users` (username, email, password_hash, role, employee_id, is_active)
SELECT e.employee_number, e.email, @portal_pw, 'employee', e.id, 1
FROM employees e
WHERE e.employee_number LIKE 'KOM-EMP-2026-%'
ON DUPLICATE KEY UPDATE employee_id=e.id, is_active=1;

-- ============================================================
-- LEAVE BALANCES (current year for all active employees)
-- ============================================================
INSERT INTO `leave_balances` (employee_id, leave_type_id, year, entitled_days, used_days, pending_days, carried_forward, remaining_days)
SELECT
    e.id,
    lt.id,
    YEAR(NOW()),
    lt.max_days,
    FLOOR(RAND() * LEAST(lt.max_days, 10)),
    0,
    0,
    lt.max_days - FLOOR(RAND() * LEAST(lt.max_days, 10))
FROM employees e
CROSS JOIN leave_types lt
WHERE e.status IN ('active','probation')
AND e.employee_number LIKE 'KOM-EMP-2026-%'
ON DUPLICATE KEY UPDATE updated_at=NOW();

-- ============================================================
-- ATTENDANCE RECORDS (last 30 days for active employees)
-- ============================================================
-- Generate 20 days of attendance for employees 1-10
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS seed_attendance()
BEGIN
    DECLARE emp_cursor_done INT DEFAULT 0;
    DECLARE v_emp_id INT;
    DECLARE v_emp_num VARCHAR(30);
    DECLARE v_day INT;
    DECLARE v_date DATE;
    DECLARE v_sign_in TIME;
    DECLARE v_sign_out TIME;
    DECLARE v_break_out TIME;
    DECLARE v_break_in TIME;
    DECLARE v_break_mins INT;
    DECLARE v_total_hrs DECIMAL(5,2);
    DECLARE v_ot_hrs DECIMAL(5,2);
    DECLARE v_is_late TINYINT;
    DECLARE v_late_mins INT;

    DECLARE emp_cursor CURSOR FOR
        SELECT id, employee_number FROM employees
        WHERE status IN ('active','probation')
        AND employee_number LIKE 'KOM-EMP-2026-%'
        LIMIT 15;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET emp_cursor_done = 1;

    OPEN emp_cursor;

    emp_loop: LOOP
        FETCH emp_cursor INTO v_emp_id, v_emp_num;
        IF emp_cursor_done THEN LEAVE emp_loop; END IF;

        SET v_day = 0;
        WHILE v_day < 25 DO
            SET v_date = DATE_SUB(CURDATE(), INTERVAL v_day DAY);

            -- Skip weekends
            IF DAYOFWEEK(v_date) NOT IN (1,7) THEN
                -- Random lateness (20% chance)
                SET v_is_late = IF(RAND() < 0.2, 1, 0);
                SET v_late_mins = IF(v_is_late, FLOOR(RAND() * 30) + 5, 0);
                SET v_sign_in = ADDTIME('08:00:00', SEC_TO_TIME((v_late_mins + FLOOR(RAND()*10)) * 60));
                SET v_break_out = '12:30:00';
                SET v_break_mins = 60 + FLOOR(RAND() * 15);
                SET v_break_in = ADDTIME('13:30:00', SEC_TO_TIME(FLOOR(RAND()*10)*60));
                SET v_sign_out = ADDTIME('17:00:00', SEC_TO_TIME(FLOOR(RAND()*60)*60));
                SET v_total_hrs = ROUND(
                    (TIME_TO_SEC(v_sign_out) - TIME_TO_SEC(v_sign_in) - v_break_mins * 60) / 3600,
                    2);
                SET v_ot_hrs = GREATEST(0, v_total_hrs - 8);

                INSERT IGNORE INTO attendance
                (employee_id, employee_number, attendance_date, sign_in, break_out, break_in,
                 sign_out, break_duration_minutes, total_hours_worked, normal_hours, overtime_hours,
                 is_late, late_minutes, status, is_approved)
                VALUES
                (v_emp_id, v_emp_num, v_date, v_sign_in, v_break_out, v_break_in,
                 v_sign_out, v_break_mins, v_total_hrs, LEAST(v_total_hrs, 8), v_ot_hrs,
                 v_is_late, v_late_mins,
                 IF(v_is_late, 'late', 'present'), 1);
            END IF;

            SET v_day = v_day + 1;
        END WHILE;

    END LOOP;

    CLOSE emp_cursor;
END $$
DELIMITER ;

CALL seed_attendance();
DROP PROCEDURE IF EXISTS seed_attendance;

-- ============================================================
-- LEAVE APPLICATIONS
-- ============================================================
INSERT INTO `leave_applications`
(employee_id, leave_type_id, start_date, end_date, total_days, reason, status, hr_status, hr_reviewed_by, hr_reviewed_at, created_at)
SELECT e.id, 1,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND()*60+5) DAY),
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND()*5+1) DAY),
    3, 'Annual leave planned', 'approved', 'approved', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()
FROM employees e WHERE e.status='active' AND e.employee_number LIKE 'KOM-EMP-2026-00%' LIMIT 8;

-- Pending leave
INSERT INTO `leave_applications`
(employee_id, leave_type_id, start_date, end_date, total_days, reason, status, hr_status, created_at)
SELECT e.id, 1,
    DATE_ADD(CURDATE(), INTERVAL FLOOR(RAND()*20+5) DAY),
    DATE_ADD(CURDATE(), INTERVAL FLOOR(RAND()*20+8) DAY),
    3, 'Personal vacation', 'pending', 'pending', NOW()
FROM employees e WHERE e.status='active' AND e.employee_number LIKE 'KOM-EMP-2026-01%' LIMIT 4;

-- Sick leave
INSERT INTO `leave_applications`
(employee_id, leave_type_id, start_date, end_date, total_days, reason, status, hr_status, hr_reviewed_by, hr_reviewed_at, created_at)
SELECT e.id, 2,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND()*30+2) DAY),
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND()*2+1) DAY),
    2, 'Medical certificate attached', 'approved', 'approved', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()
FROM employees e WHERE e.status='active' AND e.employee_number LIKE 'KOM-EMP-2026-00%' LIMIT 5;

-- ============================================================
-- OVERTIME RECORDS
-- ============================================================
INSERT INTO `overtime_records`
(attendance_id, employee_id, overtime_date, suggested_hours, approved_hours, overtime_type, reason, status, reviewed_by, reviewed_at)
SELECT a.id, a.employee_id, a.attendance_date,
    a.overtime_hours, a.overtime_hours, 'authorized', 'Project deadline', 'approved', 1, NOW()
FROM attendance a
WHERE a.overtime_hours > 0
LIMIT 20;

-- ============================================================
-- PAYROLL â€” Runs and Payslips
-- ============================================================
-- Payroll run for last month
SET @last_month = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH));
SET @last_year  = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH));

INSERT INTO `payroll_runs` (period_month, period_year, status, total_gross, total_net, total_deductions, employee_count, processed_by, finalized_at)
VALUES (@last_month, @last_year, 'published', 0, 0, 0, 0, 1, NOW());

SET @run_id = LAST_INSERT_ID();

-- Payslips for all active employees
INSERT INTO `payslips`
(employee_id, period_month, period_year, payroll_run_id, gross_salary, basic_salary,
 deductions, total_deductions, net_salary, tax_amount, uif_employee, uif_employer,
 other_deductions, status, uploaded_by)
SELECT
    e.id,
    @last_month,
    @last_year,
    @run_id,
    e.basic_salary,
    e.basic_salary,
    ROUND(e.basic_salary * 0.27, 2),
    ROUND(e.basic_salary * 0.27, 2),
    ROUND(e.basic_salary * 0.73, 2),
    ROUND(e.basic_salary * 0.20, 2),
    ROUND(e.basic_salary * 0.01, 2),
    ROUND(e.basic_salary * 0.01, 2),
    ROUND(e.basic_salary * 0.05, 2),
    'finalized',
    1
FROM employees e
WHERE e.status IN ('active','probation')
AND e.employee_number LIKE 'KOM-EMP-2026-%'
ON DUPLICATE KEY UPDATE status='finalized';

-- Update payroll run totals
UPDATE payroll_runs SET
    total_gross = (SELECT SUM(gross_salary) FROM payslips WHERE payroll_run_id=@run_id),
    total_net   = (SELECT SUM(net_salary)   FROM payslips WHERE payroll_run_id=@run_id),
    total_deductions = (SELECT SUM(total_deductions) FROM payslips WHERE payroll_run_id=@run_id),
    employee_count   = (SELECT COUNT(*) FROM payslips WHERE payroll_run_id=@run_id)
WHERE id = @run_id;

-- Payroll run for current month (draft)
INSERT INTO `payroll_runs` (period_month, period_year, status, processed_by)
VALUES (MONTH(NOW()), YEAR(NOW()), 'draft', 1);

-- ============================================================
-- DEDUCTIONS
-- ============================================================
INSERT INTO `payroll_deductions`
(employee_id, deduction_type, description, is_percentage, percentage, is_recurring, effective_from, created_by)
SELECT e.id, 'uif', 'UIF Employee Contribution', 1, 1.00, 1, '2026-01-01', 1
FROM employees e WHERE e.status='active' AND e.employee_number LIKE 'KOM-EMP-2026-%'
ON DUPLICATE KEY UPDATE deduction_type=deduction_type;

INSERT INTO `payroll_deductions`
(employee_id, deduction_type, description, is_percentage, percentage, is_recurring, effective_from, created_by)
SELECT e.id, 'tax', 'PAYE Tax Deduction', 1, 20.00, 1, '2026-01-01', 1
FROM employees e WHERE e.status='active' AND e.employee_number LIKE 'KOM-EMP-2026-%'
ON DUPLICATE KEY UPDATE deduction_type=deduction_type;

-- ============================================================
-- SAVINGS
-- ============================================================
INSERT INTO `employee_savings`
(employee_id, savings_type, fund_name, target_amount, current_balance,
 employee_rate_pct, employer_rate_pct, monthly_employee_contrib, monthly_employer_contrib,
 total_employee_contrib, total_employer_contrib, start_date, created_by)
SELECT e.id, 'pension', 'Komagin Pension Fund',
    e.basic_salary * 12 * 20,
    ROUND(RAND() * e.basic_salary * 3, 2),
    7.5, 7.5,
    ROUND(e.basic_salary * 0.075, 2),
    ROUND(e.basic_salary * 0.075, 2),
    ROUND(e.basic_salary * 0.075 * 12, 2),
    ROUND(e.basic_salary * 0.075 * 12, 2),
    DATE_FORMAT(e.start_date, '%Y-%m-01'),
    1
FROM employees e
WHERE e.status IN ('active','probation')
AND e.employee_number LIKE 'KOM-EMP-2026-%'
ON DUPLICATE KEY UPDATE savings_type=savings_type;

-- ============================================================
-- ASSETS
-- ============================================================
INSERT INTO `company_assets` (asset_code, asset_type, description, serial_number, make_model, purchase_date, purchase_value, current_condition) VALUES
('ASSET-LT-001','laptop','Dell Latitude 5520','SN001234','Dell Latitude 5520','2023-01-15',18000,'good'),
('ASSET-LT-002','laptop','HP EliteBook 840','SN001235','HP EliteBook 840','2023-01-15',16500,'good'),
('ASSET-LT-003','laptop','Lenovo ThinkPad X1','SN001236','Lenovo ThinkPad X1','2022-06-01',22000,'excellent'),
('ASSET-PH-001','phone','Samsung Galaxy A53','SN002001','Samsung Galaxy A53','2023-03-01',8500,'good'),
('ASSET-PH-002','phone','iPhone 13','SN002002','Apple iPhone 13','2022-11-01',18000,'good'),
('ASSET-VC-001','vehicle','VW Polo Company Car','ABC123GP','Volkswagen Polo','2021-05-01',220000,'good'),
('ASSET-VC-002','vehicle','Toyota Hilux Bakkie','XYZ456GP','Toyota Hilux','2020-02-01',380000,'fair'),
('ASSET-PPE-001','ppe','Safety Helmet Set 1','PPE001','3M H-Series','2024-01-01',450,'good'),
('ASSET-PPE-002','ppe','Safety Boots Workshop','PPE002','Bova Safety Boots','2024-01-01',800,'good'),
('ASSET-ID-001','id_card','Employee ID Card - Mokoena','IDC001','Standard Issue',NOW(),50,'excellent');

-- Assign some assets
INSERT INTO `asset_assignments` (asset_id, employee_id, issued_date, condition_on_issue, issued_by, acknowledgement)
SELECT ca.id, (SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0001'), CURDATE(), 'good', 1, 1
FROM company_assets ca WHERE ca.asset_code='ASSET-LT-001';

INSERT INTO `asset_assignments` (asset_id, employee_id, issued_date, condition_on_issue, issued_by, acknowledgement)
SELECT ca.id, (SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0008'), CURDATE(), 'good', 1, 1
FROM company_assets ca WHERE ca.asset_code='ASSET-LT-003';

UPDATE company_assets SET is_available=0 WHERE asset_code IN ('ASSET-LT-001','ASSET-LT-003');

-- ============================================================
-- RECRUITMENT
-- ============================================================
INSERT INTO `recruitment_vacancies` (job_title, department_id, employment_type, description, requirements, status, positions_available, deadline, created_by) VALUES
('Senior Software Engineer',4,'full_time','Lead engineering projects','5+ years experience in PHP/Python','open',2,'2026-08-31',1),
('HR Business Partner',1,'full_time','Partner with business units on HR strategy','HR degree + 3 years BP experience','open',1,'2026-07-31',1),
('Workshop Technician',5,'full_time','Maintain and repair equipment','Trade certificate in mechanical engineering','open',3,'2026-09-30',1);

SET @vac1 = (SELECT id FROM recruitment_vacancies WHERE job_title='Senior Software Engineer' LIMIT 1);
SET @vac2 = (SELECT id FROM recruitment_vacancies WHERE job_title='HR Business Partner' LIMIT 1);

INSERT INTO `recruitment_applications` (vacancy_id, first_name, last_name, email, phone, current_position, current_employer, years_experience, status, created_at) VALUES
(@vac1,'Thandeka','Sibanda','thandeka.s@email.com','0821111001','Software Developer','Tech Corp SA',4,'shortlisted',NOW()),
(@vac1,'Craig','Mitchell','craig.m@email.com','0731111002','Senior Dev','StartupXYZ',6,'interview_scheduled',NOW()),
(@vac1,'Farai','Mutasa','farai.m@email.com','0821111003','Backend Engineer','Digital Agency',3,'submitted',NOW()),
(@vac2,'Ntombi','Zulu','ntombi.z@email.com','0731111004','HR Officer','Retail Group',5,'shortlisted',NOW()),
(@vac2,'Pieter','Swart','pieter.s@email.com','0821111005','HR Generalist','Manufacturing Co',4,'reviewing',NOW());

-- ============================================================
-- TRAINING
-- ============================================================
INSERT INTO `training_programs` (title, provider, description, start_date, end_date, cost, location, status, created_by) VALUES
('PHP 8 Advanced Workshop','PNG Training Institute','Modern PHP development patterns and best practices','2026-02-10','2026-02-12',4500,'Port Moresby','completed',1),
('Leadership Development Program','Business School','Developing leadership competencies for senior staff','2026-03-01','2026-03-05',12000,'Cape Town','completed',1),
('Occupational Health & Safety','SafetyFirst Training','OHS compliance and workplace safety','2026-04-15','2026-04-15',800,'Head Office','completed',1),
('Excel Advanced for Finance','Micro Training','Advanced Excel for financial reporting','2026-05-20','2026-05-21',2200,'Head Office','completed',1),
('Welding Certification Renewal','Trade Institute','Renewal of welding certification','2026-07-01','2026-07-03',3500,'Workshop','planned',1);

-- Training attendance
SET @t1 = (SELECT id FROM training_programs WHERE title='PHP 8 Advanced Workshop');
SET @t2 = (SELECT id FROM training_programs WHERE title='Leadership Development Program');
SET @t3 = (SELECT id FROM training_programs WHERE title='Occupational Health & Safety');

INSERT INTO `training_attendance` (training_id, employee_id, attended)
SELECT @t1, e.id, 1 FROM employees e WHERE e.department_id=4 AND e.status='active';

INSERT INTO `training_attendance` (training_id, employee_id, attended)
SELECT @t2, e.id, 1 FROM employees e WHERE e.position_id IN (1,3,5,7,9,12) AND e.status='active';

INSERT INTO `training_attendance` (training_id, employee_id, attended)
SELECT @t3, e.id, 1 FROM employees e WHERE e.status='active' AND e.employee_number LIKE 'KOM-EMP-2026-%' LIMIT 12;

-- ============================================================
-- PERFORMANCE REVIEWS
-- ============================================================
INSERT INTO `performance_reviews`
(employee_id, reviewer_id, review_period, review_date, overall_score,
 self_assessment, supervisor_assessment, strengths, improvements, recommendation, status)
SELECT
    e.id,
    (SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0001'),
    CONCAT(YEAR(NOW()), ' Q1'),
    DATE_FORMAT(NOW(), '%Y-03-31'),
    ROUND(3.5 + RAND() * 1.5, 1),
    'I have met all my targets and contributed significantly to team goals.',
    'Employee shows strong performance and continues to grow professionally.',
    'Technical skills, teamwork, problem-solving',
    'Time management, documentation',
    ELT(1+FLOOR(RAND()*3), 'no_action', 'training', 'salary_review'),
    'completed'
FROM employees e
WHERE e.status='active'
AND e.employee_number LIKE 'KOM-EMP-2026-0%'
LIMIT 12;

-- ============================================================
-- DISCIPLINARY RECORDS
-- ============================================================
INSERT INTO `disciplinary_records`
(employee_id, case_number, incident_date, incident_description, case_type, action_taken, status, hr_officer_id, created_by)
VALUES
((SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0006'),
 'DISC-2026-001', DATE_SUB(NOW(), INTERVAL 45 DAY),
 'Employee arrived late on 5 consecutive occasions without valid reason.',
 'absenteeism','verbal_warning','closed',2,1),
((SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0012'),
 'DISC-2026-002', DATE_SUB(NOW(), INTERVAL 30 DAY),
 'Failure to follow safety procedures in workshop area. PPE not worn correctly.',
 'misconduct','written_warning','closed',2,1),
((SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0019'),
 'DISC-2026-003', DATE_SUB(NOW(), INTERVAL 60 DAY),
 'Altercation with supervisor during performance review meeting.',
 'insubordination','final_warning','open',2,1);

-- ============================================================
-- EMPLOYEE REQUESTS (for portal Hub)
-- ============================================================
INSERT INTO `employee_requests`
(employee_id, request_type, subject, description, priority, status, created_at)
VALUES
((SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0002'),
 'payslip_query','Missing payslip for February 2026',
 'I cannot see my payslip for February 2026 in the portal. Please assist.',
 'normal','resolved', DATE_SUB(NOW(), INTERVAL 10 DAY)),
((SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0004'),
 'employment_certificate','Employment certificate for bank',
 'I need an employment certificate for a home loan application at FNB. Please issue urgent.',
 'high','open', DATE_SUB(NOW(), INTERVAL 3 DAY)),
((SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0007'),
 'leave_query','Leave balance discrepancy',
 'My leave balance shows 8 days but I believe I have 14 days remaining. Please review.',
 'normal','in_progress', DATE_SUB(NOW(), INTERVAL 5 DAY)),
((SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0009'),
 'bank_update','Banking details update request',
 'I have changed my bank account to Capitec. Please update my banking details. New account: 1234567891',
 'high','open', DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0013'),
 'general_query','Work from home request',
 'I would like to discuss a partial work from home arrangement for 2 days per week.',
 'low','open', NOW());

-- ============================================================
-- ONBOARDING for new hire
-- ============================================================
SET @new_emp = (SELECT id FROM employees WHERE employee_number='KOM-EMP-2026-0010');

INSERT INTO `onboarding_checklists` (employee_id, task_name, category, is_completed, completed_at, due_date) VALUES
(@new_emp, 'Signed employment contract', 'Documentation', 1, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 25 DAY)),
(@new_emp, 'Email and system access setup', 'IT Setup', 1, DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_SUB(CURDATE(), INTERVAL 25 DAY)),
(@new_emp, 'Company induction completed', 'Orientation', 1, DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(CURDATE(), INTERVAL 24 DAY)),
(@new_emp, 'POPI acknowledgement signed', 'Compliance', 1, DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(CURDATE(), INTERVAL 24 DAY)),
(@new_emp, 'IT Acceptable Use Policy signed', 'Compliance', 1, DATE_SUB(NOW(), INTERVAL 17 DAY), DATE_SUB(CURDATE(), INTERVAL 23 DAY)),
(@new_emp, 'Code of Conduct signed', 'Compliance', 0, NULL, DATE_ADD(CURDATE(), INTERVAL 2 DAY)),
(@new_emp, 'Bank details submitted', 'Payroll', 1, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
(@new_emp, 'Health and Safety briefing', 'Compliance', 0, NULL, DATE_ADD(CURDATE(), INTERVAL 5 DAY)),
(@new_emp, '30-day probation check-in', 'Performance', 0, NULL, DATE_ADD(CURDATE(), INTERVAL 10 DAY)),
(@new_emp, 'Laptop and equipment issued', 'IT Setup', 1, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 25 DAY));

-- ============================================================
-- AUDIT LOG seed entries
-- ============================================================
INSERT INTO `audit_logs` (user_id, user_name, module, action, record_id, reason, ip_address, created_at) VALUES
(1,'superadmin','employees','create',1,'New HR Manager onboarded','127.0.0.1',DATE_SUB(NOW(), INTERVAL 150 DAY)),
(1,'superadmin','payroll','publish_run',1,'Monthly payroll published','127.0.0.1',DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1,'superadmin','leave','approve',1,'Leave approved for Sarah Mokoena','127.0.0.1',DATE_SUB(NOW(), INTERVAL 10 DAY)),
(1,'superadmin','documents','generate_document',1,'Employment certificate generated','127.0.0.1',DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1,'superadmin','auth','login',1,NULL,'127.0.0.1',NOW());

-- ============================================================
-- Status history for resigned employee
-- ============================================================
INSERT INTO `employee_status_history` (employee_id, old_status, new_status, reason, changed_by, changed_at)
SELECT id, 'active', 'resigned', 'Employee resigned to pursue further studies', 1, '2025-11-30 09:00:00'
FROM employees WHERE employee_number='KOM-EMP-2026-0020';

-- ============================================================
-- Notifications for users
-- ============================================================
INSERT INTO `notifications` (user_id, type, title, message, link)
SELECT u.id, 'info', 'Leave application submitted',
    'A new leave application has been submitted for review.',
    '/modules/leave/index.php'
FROM users u WHERE u.role IN ('hr_manager','super_admin') LIMIT 2;

INSERT INTO `notifications` (user_id, type, title, message, link)
SELECT u.id, 'warning', 'Payroll run due',
    'The payroll run for this month is due for processing.',
    '/modules/payroll/index.php'
FROM users u WHERE u.role IN ('payroll_officer','super_admin') LIMIT 2;

-- ============================================================
-- Verify counts
-- ============================================================
SELECT 'Employees' as entity, COUNT(*) as count FROM employees WHERE employee_number LIKE 'KOM-EMP-2026-%'
UNION ALL
SELECT 'Leave Balances', COUNT(*) FROM leave_balances
UNION ALL
SELECT 'Attendance Records', COUNT(*) FROM attendance
UNION ALL
SELECT 'Leave Applications', COUNT(*) FROM leave_applications
UNION ALL
SELECT 'Payslips', COUNT(*) FROM payslips
UNION ALL
SELECT 'Assets', COUNT(*) FROM company_assets
UNION ALL
SELECT 'Training Programs', COUNT(*) FROM training_programs
UNION ALL
SELECT 'Performance Reviews', COUNT(*) FROM performance_reviews
UNION ALL
SELECT 'Recruitment Applications', COUNT(*) FROM recruitment_applications
UNION ALL
SELECT 'Employee Requests', COUNT(*) FROM employee_requests
UNION ALL
SELECT 'Disciplinary Cases', COUNT(*) FROM disciplinary_records
UNION ALL
SELECT 'Audit Log Entries', COUNT(*) FROM audit_logs;

