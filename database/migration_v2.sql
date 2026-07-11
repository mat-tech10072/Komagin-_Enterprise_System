-- ============================================================
-- KOMAGIN HR v2 MIGRATION — Payroll Officer + Employee Portal + Hub
-- Run this after the main schema.sql has been installed
-- ============================================================

USE `komagin_hr`;

-- ============================================================
-- FIX: Add payroll_officer to users.role ENUM
-- ============================================================
ALTER TABLE `users`
    MODIFY COLUMN `role` ENUM('super_admin','hr_manager','hr_officer','supervisor','employee','finance_viewer','payroll_officer') NOT NULL DEFAULT 'employee';

-- Fix the payroll user's role if it was stored as empty string
UPDATE `users` SET `role` = 'payroll_officer' WHERE `username` = 'payroll' AND (`role` = '' OR `role` = 'employee');

-- ============================================================
-- EMPLOYEE PORTAL ACCESS (add columns to employees table)
-- ============================================================
ALTER TABLE `employees`
    ADD COLUMN IF NOT EXISTS `portal_password`        VARCHAR(255) NULL AFTER `kiosk_pin`,
    ADD COLUMN IF NOT EXISTS `portal_policy_agreed`   TINYINT(1) DEFAULT 0 AFTER `portal_password`,
    ADD COLUMN IF NOT EXISTS `portal_policy_agreed_at` DATETIME NULL AFTER `portal_policy_agreed`,
    ADD COLUMN IF NOT EXISTS `portal_last_login`      DATETIME NULL AFTER `portal_policy_agreed_at`,
    ADD COLUMN IF NOT EXISTS `portal_active`          TINYINT(1) DEFAULT 1 AFTER `portal_last_login`;

-- ============================================================
-- PAYROLL DEDUCTIONS — per-employee recurring / one-off
-- ============================================================
CREATE TABLE IF NOT EXISTS `payroll_deductions` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id`     INT UNSIGNED NOT NULL,
    `deduction_type`  ENUM('tax','uif','pension','provident','medical_aid','loan','garnishee','other') NOT NULL,
    `description`     VARCHAR(255) NOT NULL,
    `is_percentage`   TINYINT(1) DEFAULT 0,
    `amount`          DECIMAL(12,2) NULL,
    `percentage`      DECIMAL(5,2) NULL,
    `employer_contribution` DECIMAL(12,2) NULL,
    `employer_percentage`   DECIMAL(5,2) NULL,
    `is_recurring`    TINYINT(1) DEFAULT 1,
    `effective_from`  DATE NOT NULL,
    `effective_to`    DATE NULL,
    `is_active`       TINYINT(1) DEFAULT 1,
    `notes`           TEXT NULL,
    `created_by`      INT UNSIGNED NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- EMPLOYEE SAVINGS — pension / provident fund tracker
-- ============================================================
CREATE TABLE IF NOT EXISTS `employee_savings` (
    `id`                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id`             INT UNSIGNED NOT NULL,
    `savings_type`            ENUM('pension','provident','medical_aid','funeral','savings','other') NOT NULL,
    `fund_name`               VARCHAR(255) NULL,
    `target_amount`           DECIMAL(14,2) DEFAULT 0,
    `current_balance`         DECIMAL(14,2) DEFAULT 0,
    `employee_rate_pct`       DECIMAL(5,2) DEFAULT 0,
    `employer_rate_pct`       DECIMAL(5,2) DEFAULT 0,
    `monthly_employee_contrib` DECIMAL(12,2) DEFAULT 0,
    `monthly_employer_contrib` DECIMAL(12,2) DEFAULT 0,
    `total_employee_contrib`  DECIMAL(14,2) DEFAULT 0,
    `total_employer_contrib`  DECIMAL(14,2) DEFAULT 0,
    `start_date`              DATE NULL,
    `projected_end_date`      DATE NULL,
    `notes`                   TEXT NULL,
    `created_by`              INT UNSIGNED NULL,
    `created_at`              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- PAYSLIP ITEMS (line items per payslip)
-- ============================================================
CREATE TABLE IF NOT EXISTS `payslip_items` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payslip_id`   INT UNSIGNED NOT NULL,
    `item_type`    ENUM('earning','deduction','employer_contribution','info') NOT NULL,
    `description`  VARCHAR(255) NOT NULL,
    `amount`       DECIMAL(12,2) NOT NULL DEFAULT 0,
    `sort_order`   TINYINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (`payslip_id`) REFERENCES `payslips`(`id`) ON DELETE CASCADE
);

-- Extend payslips table
ALTER TABLE `payslips`
    ADD COLUMN IF NOT EXISTS `payroll_run_id`    INT UNSIGNED NULL AFTER `period_year`,
    ADD COLUMN IF NOT EXISTS `tax_amount`        DECIMAL(12,2) DEFAULT 0 AFTER `net_salary`,
    ADD COLUMN IF NOT EXISTS `uif_employee`      DECIMAL(12,2) DEFAULT 0 AFTER `tax_amount`,
    ADD COLUMN IF NOT EXISTS `uif_employer`      DECIMAL(12,2) DEFAULT 0 AFTER `uif_employee`,
    ADD COLUMN IF NOT EXISTS `other_deductions`  DECIMAL(12,2) DEFAULT 0 AFTER `uif_employer`,
    ADD COLUMN IF NOT EXISTS `total_employer_cost` DECIMAL(12,2) DEFAULT 0 AFTER `other_deductions`,
    ADD COLUMN IF NOT EXISTS `overtime_hours`    DECIMAL(6,2) DEFAULT 0 AFTER `total_employer_cost`,
    ADD COLUMN IF NOT EXISTS `overtime_amount`   DECIMAL(12,2) DEFAULT 0 AFTER `overtime_hours`,
    ADD COLUMN IF NOT EXISTS `leave_days_taken`  DECIMAL(5,1) DEFAULT 0 AFTER `overtime_amount`,
    ADD COLUMN IF NOT EXISTS `notes`             TEXT NULL AFTER `leave_days_taken`,
    ADD COLUMN IF NOT EXISTS `status`            ENUM('draft','finalized','sent') DEFAULT 'draft' AFTER `notes`;

-- ============================================================
-- PAYROLL RUNS — batch processing
-- ============================================================
CREATE TABLE IF NOT EXISTS `payroll_runs` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `period_month`  TINYINT(2) NOT NULL,
    `period_year`   YEAR NOT NULL,
    `status`        ENUM('draft','processing','finalized','published') DEFAULT 'draft',
    `total_gross`   DECIMAL(14,2) DEFAULT 0,
    `total_net`     DECIMAL(14,2) DEFAULT 0,
    `total_deductions` DECIMAL(14,2) DEFAULT 0,
    `employee_count` INT DEFAULT 0,
    `notes`         TEXT NULL,
    `processed_by`  INT UNSIGNED NULL,
    `finalized_at`  DATETIME NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `payroll_run_unique` (`period_month`, `period_year`)
);

-- ============================================================
-- EMPLOYEE REQUESTS (Hub)
-- ============================================================
CREATE TABLE IF NOT EXISTS `employee_requests` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id`   INT UNSIGNED NOT NULL,
    `request_type`  ENUM('leave_query','payslip_query','employment_certificate','bank_update','salary_query','training_request','general_query','grievance','payroll_query','document_request') NOT NULL,
    `subject`       VARCHAR(255) NOT NULL,
    `description`   TEXT NOT NULL,
    `priority`      ENUM('low','normal','high','urgent') DEFAULT 'normal',
    `status`        ENUM('open','in_progress','resolved','closed','rejected') DEFAULT 'open',
    `assigned_to`   INT UNSIGNED NULL,
    `hr_response`   TEXT NULL,
    `internal_notes` TEXT NULL,
    `resolved_by`   INT UNSIGNED NULL,
    `resolved_at`   DATETIME NULL,
    `attachment`    VARCHAR(500) NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- DEFAULT PAYROLL OFFICER USER
-- ============================================================
INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role`, `is_active`)
VALUES ('payroll', 'payroll@komagin.com',
        '$2y$10$Ad.cRs9VYqt50aDnlCc5aO7o01ueEOkaS2a6SedC1vzODE1seN83S', 'payroll_officer', 1);
-- Default password: Admin@123

SELECT 'Migration v2 complete.' AS status;
