-- ============================================================
-- KOMAGIN HR MANAGEMENT SYSTEM - DATABASE SCHEMA
-- ============================================================

CREATE DATABASE IF NOT EXISTS `komagin_hr` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `komagin_hr`;

-- ============================================================
-- COMPANY SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `company_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(255) DEFAULT 'Komagin Limited',
    `company_logo` VARCHAR(255) NULL,
    `address` TEXT NULL,
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(100) NULL,
    `website` VARCHAR(100) NULL,
    `work_start_time` TIME DEFAULT '08:00:00',
    `work_end_time` TIME DEFAULT '17:00:00',
    `grace_period_minutes` INT DEFAULT 15,
    `break_duration_minutes` INT DEFAULT 60,
    `standard_work_hours` DECIMAL(4,2) DEFAULT 8.00,
    `overtime_threshold_hours` DECIMAL(4,2) DEFAULT 8.00,
    `emp_number_settings` JSON NULL,
    `leave_settings` JSON NULL,
    `archive_settings` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO `company_settings` (`company_name`, `work_start_time`, `work_end_time`, `grace_period_minutes`,
    `break_duration_minutes`, `standard_work_hours`, `overtime_threshold_hours`, `emp_number_settings`)
VALUES ('Komagin Limited', '08:00:00', '17:00:00', 15, 60, 8.00, 8.00,
    '{"prefix":"KOM-EMP","year_format":"Y","number_length":4,"starting_number":1}');

-- ============================================================
-- DEPARTMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(20) NULL,
    `description` TEXT NULL,
    `head_employee_id` INT UNSIGNED NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `dept_name_unique` (`name`)
);

INSERT INTO `departments` (`name`, `code`) VALUES
('Human Resources', 'HR'),
('Finance', 'FIN'),
('Operations', 'OPS'),
('Engineering', 'ENG'),
('Workshop', 'WKSP'),
('Administration', 'ADMIN'),
('Project Management', 'PM');

-- ============================================================
-- POSITIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `positions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `department_id` INT UNSIGNED NULL,
    `title` VARCHAR(150) NOT NULL,
    `job_grade` VARCHAR(50) NULL,
    `description` TEXT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
);

INSERT INTO `positions` (`department_id`, `title`, `job_grade`) VALUES
(1, 'HR Manager', 'M1'),
(1, 'HR Officer', 'S2'),
(2, 'Finance Manager', 'M1'),
(2, 'Finance Officer', 'S2'),
(3, 'Operations Manager', 'M1'),
(3, 'Operations Supervisor', 'S1'),
(4, 'Senior Engineer', 'P2'),
(4, 'Engineer', 'P1'),
(5, 'Workshop Supervisor', 'S1'),
(5, 'Technician', 'T1'),
(6, 'Administrative Officer', 'S2'),
(7, 'Project Manager', 'M1');

-- ============================================================
-- EMPLOYEES
-- ============================================================
CREATE TABLE IF NOT EXISTS `employees` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_number` VARCHAR(30) NOT NULL UNIQUE,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100) NULL,
    `preferred_name` VARCHAR(100) NULL,
    `date_of_birth` DATE NULL,
    `gender` ENUM('male','female','other','prefer_not_to_say') NULL,
    `marital_status` ENUM('single','married','divorced','widowed','other') NULL,
    `national_id` VARCHAR(50) NULL,
    `passport_number` VARCHAR(50) NULL,
    `nationality` VARCHAR(100) NULL,
    `photo` VARCHAR(255) NULL,

    -- Contact
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(30) NULL,
    `phone_alt` VARCHAR(30) NULL,
    `residential_address` TEXT NULL,
    `postal_address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state_province` VARCHAR(100) NULL,
    `country` VARCHAR(100) DEFAULT 'South Africa',
    `postal_code` VARCHAR(20) NULL,

    -- Employment
    `department_id` INT UNSIGNED NULL,
    `position_id` INT UNSIGNED NULL,
    `supervisor_id` INT UNSIGNED NULL,
    `employment_type` ENUM('full_time','part_time','contract','casual','intern') DEFAULT 'full_time',
    `status` ENUM('active','probation','suspended','on_leave','resigned','terminated','deceased','archived') DEFAULT 'active',
    `start_date` DATE NULL,
    `contract_end_date` DATE NULL,
    `probation_start` DATE NULL,
    `probation_end` DATE NULL,
    `basic_salary` DECIMAL(12,2) NULL,
    `pay_frequency` ENUM('weekly','bi_weekly','monthly') DEFAULT 'monthly',
    `work_location` VARCHAR(150) NULL,
    `kiosk_pin` VARCHAR(255) NULL,

    -- Bank
    `bank_name` VARCHAR(100) NULL,
    `bank_account_number` VARCHAR(50) NULL,
    `bank_branch_code` VARCHAR(20) NULL,
    `bank_account_type` VARCHAR(50) NULL,

    -- Emergency Contact
    `emergency_contact_name` VARCHAR(150) NULL,
    `emergency_contact_relation` VARCHAR(100) NULL,
    `emergency_contact_phone` VARCHAR(30) NULL,
    `emergency_contact_email` VARCHAR(150) NULL,

    -- Next of Kin
    `nok_name` VARCHAR(150) NULL,
    `nok_relation` VARCHAR(100) NULL,
    `nok_phone` VARCHAR(30) NULL,
    `nok_address` TEXT NULL,

    -- Metadata
    `status_reason` TEXT NULL,
    `exit_date` DATE NULL,
    `exit_reason` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `updated_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE SET NULL,
    INDEX `idx_emp_number` (`employee_number`),
    INDEX `idx_emp_status` (`status`),
    INDEX `idx_emp_dept` (`department_id`)
);

-- ============================================================
-- EMPLOYEE STATUS HISTORY
-- ============================================================
CREATE TABLE IF NOT EXISTS `employee_status_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `old_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `reason` TEXT NULL,
    `changed_by` INT UNSIGNED NULL,
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- USERS (LOGIN ACCOUNTS)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NULL,
    `username` VARCHAR(80) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin','hr_manager','hr_officer','supervisor','employee','finance_viewer','payroll_officer') NOT NULL DEFAULT 'employee',
    `is_active` TINYINT(1) DEFAULT 1,
    `must_change_password` TINYINT(1) DEFAULT 0,
    `last_login` DATETIME NULL,
    `login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL
);

-- Default Super Admin (password: Admin@123)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `is_active`)
VALUES ('superadmin', 'admin@komagin.com',
        '$2y$10$Ad.cRs9VYqt50aDnlCc5aO7o01ueEOkaS2a6SedC1vzODE1seN83S', 'super_admin', 1);

-- ============================================================
-- PERMISSIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `module` VARCHAR(50) NOT NULL,
    `description` TEXT NULL
);

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role` VARCHAR(50) NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `can_view` TINYINT(1) DEFAULT 0,
    `can_create` TINYINT(1) DEFAULT 0,
    `can_edit` TINYINT(1) DEFAULT 0,
    `can_delete` TINYINT(1) DEFAULT 0,
    UNIQUE KEY `role_permission_unique` (`role`, `permission_id`),
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
);

INSERT INTO `permissions` (`name`, `slug`, `module`) VALUES
('View Employees', 'employees.view', 'employees'),
('Create Employees', 'employees.create', 'employees'),
('Edit Employees', 'employees.edit', 'employees'),
('Delete Employees', 'employees.delete', 'employees'),
('View Attendance', 'attendance.view', 'attendance'),
('Edit Attendance', 'attendance.edit', 'attendance'),
('View Timesheets', 'timesheets.view', 'timesheets'),
('Edit Timesheets', 'timesheets.edit', 'timesheets'),
('Approve Timesheets', 'timesheets.approve', 'timesheets'),
('View Leave', 'leave.view', 'leave'),
('Approve Leave', 'leave.approve', 'leave'),
('View Recruitment', 'recruitment.view', 'recruitment'),
('Manage Recruitment', 'recruitment.manage', 'recruitment'),
('View Reports', 'reports.view', 'reports'),
('View Payroll Data', 'payroll.view', 'payroll'),
('Manage Settings', 'settings.manage', 'settings'),
('View Audit Logs', 'audit.view', 'audit'),
('Manage Users', 'users.manage', 'users');

-- ============================================================
-- ATTENDANCE
-- ============================================================
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `employee_number` VARCHAR(30) NOT NULL,
    `attendance_date` DATE NOT NULL,
    `sign_in` TIME NULL,
    `break_out` TIME NULL,
    `break_in` TIME NULL,
    `sign_out` TIME NULL,
    `break_duration_minutes` INT DEFAULT 0,
    `total_hours_worked` DECIMAL(5,2) DEFAULT 0.00,
    `normal_hours` DECIMAL(5,2) DEFAULT 0.00,
    `overtime_hours` DECIMAL(5,2) DEFAULT 0.00,
    `is_late` TINYINT(1) DEFAULT 0,
    `late_minutes` INT DEFAULT 0,
    `is_early_departure` TINYINT(1) DEFAULT 0,
    `is_absent` TINYINT(1) DEFAULT 0,
    `is_on_leave` TINYINT(1) DEFAULT 0,
    `status` ENUM('present','absent','late','on_leave','half_day','holiday') DEFAULT 'present',
    `is_manually_adjusted` TINYINT(1) DEFAULT 0,
    `adjustment_reason` TEXT NULL,
    `adjusted_by` INT UNSIGNED NULL,
    `adjusted_at` DATETIME NULL,
    `hr_remarks` TEXT NULL,
    `is_approved` TINYINT(1) DEFAULT 0,
    `approved_by` INT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `is_locked` TINYINT(1) DEFAULT 0,
    `device_info` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `attendance_unique` (`employee_id`, `attendance_date`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    INDEX `idx_att_date` (`attendance_date`),
    INDEX `idx_att_emp` (`employee_id`)
);

-- ============================================================
-- TIMESHEET CORRECTION REQUESTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `correction_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `attendance_id` INT UNSIGNED NULL,
    `request_date` DATE NOT NULL,
    `request_type` ENUM('forgot_sign_in','forgot_sign_out','forgot_break_out','forgot_break_in','wrong_time','overtime_not_captured','other') NOT NULL,
    `description` TEXT NOT NULL,
    `requested_sign_in` TIME NULL,
    `requested_sign_out` TIME NULL,
    `requested_break_out` TIME NULL,
    `requested_break_in` TIME NULL,
    `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
    `hr_remarks` TEXT NULL,
    `reviewed_by` INT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`attendance_id`) REFERENCES `attendance`(`id`) ON DELETE SET NULL
);

-- ============================================================
-- OVERTIME RECORDS
-- ============================================================
CREATE TABLE IF NOT EXISTS `overtime_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attendance_id` INT UNSIGNED NOT NULL,
    `employee_id` INT UNSIGNED NOT NULL,
    `overtime_date` DATE NOT NULL,
    `suggested_hours` DECIMAL(5,2) DEFAULT 0.00,
    `approved_hours` DECIMAL(5,2) DEFAULT 0.00,
    `overtime_type` VARCHAR(50) NULL,
    `reason` TEXT NULL,
    `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
    `reviewed_by` INT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `hr_remarks` TEXT NULL,
    `is_locked` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`attendance_id`) REFERENCES `attendance`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- LEAVE TYPES
-- ============================================================
CREATE TABLE IF NOT EXISTS `leave_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NULL,
    `max_days` INT DEFAULT 0,
    `carry_forward` TINYINT(1) DEFAULT 0,
    `max_carry_forward_days` INT DEFAULT 0,
    `requires_document` TINYINT(1) DEFAULT 0,
    `is_paid` TINYINT(1) DEFAULT 1,
    `approval_required` TINYINT(1) DEFAULT 1,
    `gender_specific` ENUM('all','male','female') DEFAULT 'all',
    `description` TEXT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO `leave_types` (`name`, `code`, `max_days`, `carry_forward`, `requires_document`, `is_paid`) VALUES
('Annual Leave', 'AL', 21, 1, 0, 1),
('Sick Leave', 'SL', 30, 0, 1, 1),
('Compassionate Leave', 'CL', 5, 0, 1, 1),
('Maternity Leave', 'ML', 120, 0, 1, 1),
('Paternity Leave', 'PL', 10, 0, 0, 1),
('Study Leave', 'STL', 10, 0, 1, 1),
('Leave Without Pay', 'LWP', 0, 0, 1, 0),
('Emergency Leave', 'EL', 3, 0, 0, 1);

-- ============================================================
-- LEAVE BALANCES
-- ============================================================
CREATE TABLE IF NOT EXISTS `leave_balances` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `leave_type_id` INT UNSIGNED NOT NULL,
    `year` YEAR NOT NULL,
    `entitled_days` DECIMAL(5,1) DEFAULT 0,
    `used_days` DECIMAL(5,1) DEFAULT 0,
    `pending_days` DECIMAL(5,1) DEFAULT 0,
    `carried_forward` DECIMAL(5,1) DEFAULT 0,
    `remaining_days` DECIMAL(5,1) DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `leave_balance_unique` (`employee_id`, `leave_type_id`, `year`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- LEAVE APPLICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `leave_applications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `leave_type_id` INT UNSIGNED NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_days` DECIMAL(5,1) DEFAULT 0,
    `reason` TEXT NULL,
    `supporting_document` VARCHAR(255) NULL,
    `status` ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
    `supervisor_status` ENUM('pending','approved','rejected') DEFAULT 'pending',
    `supervisor_id` INT UNSIGNED NULL,
    `supervisor_reviewed_at` DATETIME NULL,
    `supervisor_remarks` TEXT NULL,
    `hr_status` ENUM('pending','approved','rejected') DEFAULT 'pending',
    `hr_reviewed_by` INT UNSIGNED NULL,
    `hr_reviewed_at` DATETIME NULL,
    `hr_remarks` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- RECRUITMENT VACANCIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `recruitment_vacancies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `job_title` VARCHAR(150) NOT NULL,
    `department_id` INT UNSIGNED NULL,
    `position_id` INT UNSIGNED NULL,
    `description` TEXT NULL,
    `requirements` TEXT NULL,
    `responsibilities` TEXT NULL,
    `employment_type` ENUM('full_time','part_time','contract','casual','intern') DEFAULT 'full_time',
    `location` VARCHAR(150) NULL,
    `salary_range` VARCHAR(100) NULL,
    `deadline` DATE NULL,
    `status` ENUM('draft','open','closed','on_hold') DEFAULT 'draft',
    `positions_available` INT DEFAULT 1,
    `published_at` DATETIME NULL,
    `closed_at` DATETIME NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
);

-- ============================================================
-- RECRUITMENT APPLICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `recruitment_applications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `vacancy_id` INT UNSIGNED NOT NULL,
    `application_number` VARCHAR(30) NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `current_position` VARCHAR(150) NULL,
    `current_employer` VARCHAR(150) NULL,
    `years_experience` INT DEFAULT 0,
    `qualifications` TEXT NULL,
    `cover_letter` TEXT NULL,
    `cv_file` VARCHAR(255) NULL,
    `certificate_file` VARCHAR(255) NULL,
    `cover_letter_file` VARCHAR(255) NULL,
    `status` ENUM('submitted','reviewing','shortlisted','interview_scheduled','interviewed','selected','rejected','withdrawn') DEFAULT 'submitted',
    `interview_date` DATETIME NULL,
    `interview_notes` TEXT NULL,
    `hr_remarks` TEXT NULL,
    `reviewed_by` INT UNSIGNED NULL,
    `converted_to_employee_id` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`vacancy_id`) REFERENCES `recruitment_vacancies`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- ONBOARDING
-- ============================================================
CREATE TABLE IF NOT EXISTS `onboarding_checklists` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `task_name` VARCHAR(200) NOT NULL,
    `category` VARCHAR(100) NULL,
    `is_completed` TINYINT(1) DEFAULT 0,
    `completed_by` INT UNSIGNED NULL,
    `completed_at` DATETIME NULL,
    `due_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- EMPLOYEE DOCUMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `employee_documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `category` ENUM('id_document','certificate','contract','medical','warning_letter','promotion_letter','leave_document','resignation','clearance','payslip','bank_document','training_certificate','other') NOT NULL,
    `document_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(100) NULL,
    `file_size` INT NULL,
    `expiry_date` DATE NULL,
    `is_verified` TINYINT(1) DEFAULT 0,
    `verified_by` INT UNSIGNED NULL,
    `verified_at` DATETIME NULL,
    `notes` TEXT NULL,
    `uploaded_by` INT UNSIGNED NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `is_deleted` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    INDEX `idx_doc_emp` (`employee_id`),
    INDEX `idx_doc_expiry` (`expiry_date`)
);

-- ============================================================
-- EMPLOYEE PROFILE UPDATE LINKS
-- ============================================================
CREATE TABLE IF NOT EXISTS `employee_update_links` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `link_type` ENUM('monthly','quarterly','individual','request_based') DEFAULT 'individual',
    `scope` ENUM('all','department','individual') DEFAULT 'individual',
    `department_id` INT UNSIGNED NULL,
    `employee_id` INT UNSIGNED NULL,
    `title` VARCHAR(200) NULL,
    `instructions` TEXT NULL,
    `expires_at` DATETIME NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `is_revoked` TINYINT(1) DEFAULT 0,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- EMPLOYEE PENDING UPDATES (awaiting HR approval)
-- ============================================================
CREATE TABLE IF NOT EXISTS `employee_pending_updates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `update_link_id` INT UNSIGNED NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `field_label` VARCHAR(150) NOT NULL,
    `old_value` TEXT NULL,
    `new_value` TEXT NOT NULL,
    `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
    `rejection_reason` TEXT NULL,
    `reviewed_by` INT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- PERFORMANCE REVIEWS
-- ============================================================
CREATE TABLE IF NOT EXISTS `performance_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `reviewer_id` INT UNSIGNED NOT NULL,
    `review_period` VARCHAR(50) NULL,
    `review_date` DATE NOT NULL,
    `overall_score` DECIMAL(5,2) NULL,
    `self_assessment` TEXT NULL,
    `supervisor_assessment` TEXT NULL,
    `strengths` TEXT NULL,
    `improvements` TEXT NULL,
    `recommendation` ENUM('promote','salary_review','training','warning','no_action') NULL,
    `recommendation_notes` TEXT NULL,
    `status` ENUM('draft','submitted','completed') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- TRAINING PROGRAMS
-- ============================================================
CREATE TABLE IF NOT EXISTS `training_programs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `provider` VARCHAR(200) NULL,
    `description` TEXT NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `cost` DECIMAL(12,2) NULL,
    `location` VARCHAR(200) NULL,
    `status` ENUM('planned','ongoing','completed','cancelled') DEFAULT 'planned',
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `training_attendance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `training_id` INT UNSIGNED NOT NULL,
    `employee_id` INT UNSIGNED NOT NULL,
    `attended` TINYINT(1) DEFAULT 0,
    `certificate_file` VARCHAR(255) NULL,
    `certificate_expiry` DATE NULL,
    `notes` TEXT NULL,
    UNIQUE KEY `training_emp_unique` (`training_id`, `employee_id`),
    FOREIGN KEY (`training_id`) REFERENCES `training_programs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- DISCIPLINARY RECORDS
-- ============================================================
CREATE TABLE IF NOT EXISTS `disciplinary_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `case_number` VARCHAR(30) NULL,
    `incident_date` DATE NOT NULL,
    `incident_description` TEXT NOT NULL,
    `case_type` ENUM('misconduct','poor_performance','absenteeism','insubordination','harassment','theft','fraud','other') NOT NULL,
    `action_taken` ENUM('verbal_warning','written_warning','final_warning','suspension','demotion','termination','dismissed','no_action') NULL,
    `investigation_notes` TEXT NULL,
    `evidence_file` VARCHAR(255) NULL,
    `warning_letter_file` VARCHAR(255) NULL,
    `status` ENUM('open','investigating','closed','appealed') DEFAULT 'open',
    `hearing_date` DATE NULL,
    `resolved_at` DATE NULL,
    `hr_officer_id` INT UNSIGNED NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- GRIEVANCE RECORDS
-- ============================================================
CREATE TABLE IF NOT EXISTS `grievance_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `case_number` VARCHAR(30) NULL,
    `filed_date` DATE NOT NULL,
    `complaint_description` TEXT NOT NULL,
    `grievance_type` VARCHAR(100) NULL,
    `assigned_hr_officer` INT UNSIGNED NULL,
    `investigation_notes` TEXT NULL,
    `resolution` TEXT NULL,
    `status` ENUM('open','investigating','resolved','closed') DEFAULT 'open',
    `resolved_at` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- ASSETS
-- ============================================================
CREATE TABLE IF NOT EXISTS `company_assets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `asset_code` VARCHAR(30) NULL,
    `asset_type` ENUM('laptop','phone','vehicle','ppe','tools','id_card','uniform','other') NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `serial_number` VARCHAR(100) NULL,
    `make_model` VARCHAR(100) NULL,
    `purchase_date` DATE NULL,
    `purchase_value` DECIMAL(12,2) NULL,
    `current_condition` ENUM('excellent','good','fair','poor','damaged','lost') DEFAULT 'good',
    `image` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `is_available` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `asset_assignments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `asset_id` INT UNSIGNED NOT NULL,
    `employee_id` INT UNSIGNED NOT NULL,
    `issued_date` DATE NOT NULL,
    `condition_on_issue` ENUM('excellent','good','fair','poor') DEFAULT 'good',
    `issued_by` INT UNSIGNED NULL,
    `acknowledgement` TINYINT(1) DEFAULT 0,
    `acknowledgement_date` DATETIME NULL,
    `expected_return_date` DATE NULL,
    `actual_return_date` DATE NULL,
    `condition_on_return` ENUM('excellent','good','fair','poor','damaged','lost') NULL,
    `return_remarks` TEXT NULL,
    `received_by` INT UNSIGNED NULL,
    `is_returned` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`asset_id`) REFERENCES `company_assets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- ARCHIVE RECORDS
-- ============================================================
CREATE TABLE IF NOT EXISTS `archive_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `archive_type` ENUM('monthly','quarterly','yearly') NOT NULL,
    `year` YEAR NOT NULL,
    `month` TINYINT(2) NULL,
    `quarter` TINYINT(1) NULL,
    `document_type` ENUM('timesheets','attendance','leave_report','overtime_report','payroll_support','hr_summary','employee_list','recruitment_summary','training_summary','disciplinary_summary','compliance') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NULL,
    `department_id` INT UNSIGNED NULL,
    `is_locked` TINYINT(1) DEFAULT 0,
    `locked_by` INT UNSIGNED NULL,
    `locked_at` DATETIME NULL,
    `generated_by` INT UNSIGNED NULL,
    `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT NULL,
    INDEX `idx_archive_year_month` (`year`, `month`),
    INDEX `idx_archive_type` (`archive_type`, `document_type`)
);

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('info','success','warning','danger') DEFAULT 'info',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(500) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notif_user` (`user_id`, `is_read`)
);

-- ============================================================
-- AUDIT LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `user_name` VARCHAR(150) NULL,
    `module` VARCHAR(100) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `record_id` INT UNSIGNED NULL,
    `old_value` TEXT NULL,
    `new_value` TEXT NULL,
    `reason` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_module` (`module`),
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_date` (`created_at`)
);

-- ============================================================
-- EMPLOYEE SKILLS
-- ============================================================
CREATE TABLE IF NOT EXISTS `employee_skills` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `skill_name` VARCHAR(150) NOT NULL,
    `proficiency` ENUM('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
    `certificate_file` VARCHAR(255) NULL,
    `expiry_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- PAYSLIPS
-- ============================================================
CREATE TABLE IF NOT EXISTS `payslips` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT UNSIGNED NOT NULL,
    `period_month` TINYINT(2) NOT NULL,
    `period_year` YEAR NOT NULL,
    `gross_salary` DECIMAL(12,2) NULL,
    `deductions` DECIMAL(12,2) NULL,
    `net_salary` DECIMAL(12,2) NULL,
    `file_path` VARCHAR(500) NULL,
    `uploaded_by` INT UNSIGNED NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `payslip_unique` (`employee_id`, `period_month`, `period_year`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);
