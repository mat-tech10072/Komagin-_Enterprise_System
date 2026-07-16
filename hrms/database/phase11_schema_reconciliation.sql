-- ============================================================
-- Phase 11: Schema Reconciliation (Enterprise Remediation Phase 3)
--
-- Brings an EXISTING database (anything at "Phase 10" state — i.e. has
-- already run migration_v2/phase1/phase5/phase6/phase7/phase8/phase9/phase10)
-- up to parity with the new canonical database/schema.sql, WITHOUT
-- requiring a rebuild and WITHOUT touching any existing data.
--
-- Every statement in this file is additive and idempotent:
--   - CREATE TABLE IF NOT EXISTS — no-ops if the table already exists
--   - ADD COLUMN IF NOT EXISTS — no-ops if the column already exists
--   - MODIFY COLUMN (enum widening) -- safe to re-run -- MySQL/MariaDB does
--     not error when a MODIFY sets a column to the type it already has
--
-- This file is NOT needed for a fresh install — database/schema.sql
-- already contains everything below directly. It exists solely for
-- upgrading a database that was created before Phase 3.
--
-- Root cause this file addresses: 11 tables existed on the live
-- development database with NO CREATE TABLE statement in ANY tracked
-- file (created via undocumented manual changes at some point before
-- this remediation program began), plus several tables whose tracked
-- CREATE TABLE was missing columns the running application already
-- depended on (also added to the live database by undocumented manual
-- changes or the one-off database/fix_payslips_columns.php script).
-- See docs/remediation/Database/05-phase3-schema-drift-matrix.md for
-- the full evidence trail.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ── 1. Tables with no CREATE TABLE in any tracked file ──────────────────
-- (11 tables — DDL taken verbatim from the verified live database via
-- `SHOW CREATE TABLE`, cross-checked against every runtime query that
-- references them -- see the Runtime Database Usage Inventory.)

CREATE TABLE IF NOT EXISTS `approval_workflows` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_type` enum('leave','payroll_run','promotion','transfer','termination','document','overtime','correction') NOT NULL,
  `reference_id` int(10) unsigned NOT NULL COMMENT 'ID in the source table',
  `reference_table` varchar(100) NOT NULL COMMENT 'Source table name',
  `title` varchar(255) NOT NULL,
  `initiated_by` int(10) unsigned DEFAULT NULL,
  `employee_id` int(10) unsigned DEFAULT NULL COMMENT 'Employee the workflow is about',
  `status` enum('pending','in_review','approved','rejected','cancelled','withdrawn') DEFAULT 'pending',
  `current_stage` tinyint(4) DEFAULT 1,
  `total_stages` tinyint(4) DEFAULT 1,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `initiated_by` (`initiated_by`),
  KEY `employee_id` (`employee_id`),
  KEY `idx_approval_type` (`workflow_type`),
  KEY `idx_approval_status` (`status`),
  KEY `idx_approval_ref` (`workflow_type`,`reference_id`),
  CONSTRAINT `approval_workflows_ibfk_1` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `approval_workflows_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `approval_stages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` int(10) unsigned NOT NULL,
  `stage_number` tinyint(4) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `approver_role` varchar(50) DEFAULT NULL,
  `approver_user_id` int(10) unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected','skipped') DEFAULT 'pending',
  `action` enum('approve','reject') DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `acted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflow_stage_unique` (`workflow_id`,`stage_number`),
  KEY `approver_user_id` (`approver_user_id`),
  CONSTRAINT `approval_stages_ibfk_1` FOREIGN KEY (`workflow_id`) REFERENCES `approval_workflows` (`id`) ON DELETE CASCADE,
  CONSTRAINT `approval_stages_ibfk_2` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `doc_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'file-text',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `doc_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `body_html` longtext NOT NULL,
  `variables_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of variable names used in this template' CHECK (json_valid(`variables_used`)),
  `version` tinyint(3) unsigned DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `requires_approval` tinyint(1) DEFAULT 0,
  `letterhead_id` int(10) unsigned DEFAULT NULL,
  `signature_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`signature_ids`)),
  `stamp_id` int(10) unsigned DEFAULT NULL,
  `watermark_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `show_letterhead` tinyint(1) DEFAULT 0,
  `show_signature` tinyint(1) DEFAULT 0,
  `show_stamp` tinyint(1) DEFAULT 0,
  `show_watermark` tinyint(1) DEFAULT 0,
  `show_qr_code` tinyint(1) DEFAULT 0,
  `show_doc_number` tinyint(1) DEFAULT 0,
  `show_page_number` tinyint(1) DEFAULT 0,
  `show_header` tinyint(1) DEFAULT 1,
  `show_footer` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `doc_templates_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `doc_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `doc_template_versions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL,
  `version` tinyint(3) unsigned NOT NULL,
  `body_html` longtext NOT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `doc_template_versions_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `doc_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `generated_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `body_html` longtext NOT NULL,
  `status` enum('draft','pending_approval','approved','rejected','issued') DEFAULT 'draft',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `issued_by` int(10) unsigned DEFAULT NULL,
  `issued_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `generated_by` int(10) unsigned DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  KEY `idx_gendoc_emp` (`employee_id`),
  KEY `idx_gendoc_status` (`status`),
  CONSTRAINT `generated_documents_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `doc_templates` (`id`),
  CONSTRAINT `generated_documents_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_dependents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `relationship` enum('spouse','child','parent','sibling','other') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `is_beneficiary` tinyint(1) DEFAULT 0,
  `beneficiary_percentage` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_dependents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_qualifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `qualification_type` enum('matric','diploma','degree','honours','masters','phd','trade_cert','professional_cert','other') NOT NULL,
  `title` varchar(200) NOT NULL,
  `institution` varchar(200) DEFAULT NULL,
  `field_of_study` varchar(150) DEFAULT NULL,
  `year_obtained` year(4) DEFAULT NULL,
  `grade_result` varchar(50) DEFAULT NULL,
  `certificate_file` varchar(500) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(10) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_qualifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_work_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `employer_name` varchar(200) NOT NULL,
  `position_held` varchar(150) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason_for_leaving` text DEFAULT NULL,
  `reference_name` varchar(150) DEFAULT NULL,
  `reference_phone` varchar(30) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_work_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kiosk_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kiosk_token` varchar(32) NOT NULL DEFAULT '',
  `location_name` varchar(150) NOT NULL DEFAULT 'Main Office',
  `status` enum('open','closed') NOT NULL DEFAULT 'closed',
  `opened_by` int(10) unsigned DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `closed_by` int(10) unsigned DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `scheduled_open` time DEFAULT NULL,
  `scheduled_close` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kiosk_token` (`kiosk_token`),
  KEY `opened_by` (`opened_by`),
  KEY `closed_by` (`closed_by`),
  CONSTRAINT `kiosk_sessions_ibfk_1` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kiosk_sessions_ibfk_2` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kiosk_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kiosk_session_id` int(10) unsigned DEFAULT NULL,
  `employee_id` int(10) unsigned DEFAULT NULL,
  `employee_number` varchar(30) DEFAULT NULL,
  `action` enum('sign_in','break_out','break_in','sign_out','failed_auth','kiosk_opened','kiosk_closed') NOT NULL,
  `result` enum('success','error') DEFAULT 'success',
  `error_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kiosk_emp` (`employee_id`),
  KEY `idx_kiosk_date` (`recorded_at`),
  CONSTRAINT `kiosk_audit_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Columns proven used by application code but missing from the ─────
--      previously-tracked CREATE TABLE / ALTER TABLE statements
ALTER TABLE `payslips`
    ADD COLUMN IF NOT EXISTS `basic_salary`        DECIMAL(12,2) DEFAULT 0    AFTER `gross_salary`,
    ADD COLUMN IF NOT EXISTS `total_deductions`     DECIMAL(12,2) DEFAULT 0    AFTER `net_salary`;

ALTER TABLE `temp_employees`
    ADD COLUMN IF NOT EXISTS `rate_type`         ENUM('daily','hourly') NOT NULL DEFAULT 'daily' AFTER `daily_rate`,
    ADD COLUMN IF NOT EXISTS `attendance_method`  ENUM('kiosk','timesheet','both') NOT NULL DEFAULT 'kiosk' AFTER `portal_active`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `first_name`    VARCHAR(100) DEFAULT NULL AFTER `employee_id`,
    ADD COLUMN IF NOT EXISTS `last_name`     VARCHAR(100) DEFAULT NULL AFTER `first_name`,
    ADD COLUMN IF NOT EXISTS `job_title`     VARCHAR(150) DEFAULT NULL AFTER `last_name`,
    ADD COLUMN IF NOT EXISTS `phone`         VARCHAR(30)  DEFAULT NULL AFTER `job_title`,
    ADD COLUMN IF NOT EXISTS `profile_photo` VARCHAR(255) DEFAULT NULL AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `bio`           TEXT         DEFAULT NULL AFTER `profile_photo`;

-- users.role ENUM widening -- safe to re-run -- a MODIFY to an identical
-- definition is a no-op change as far as data is concerned.
ALTER TABLE `users`
    MODIFY COLUMN `role` ENUM('super_admin','hr_manager','hr_officer','supervisor','employee','finance_viewer','payroll_manager','payroll_officer','recruitment_officer','training_officer','kiosk_terminal') NOT NULL DEFAULT 'employee';

ALTER TABLE `role_permissions`
    ADD COLUMN IF NOT EXISTS `can_approve` TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `can_export`  TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `can_publish` TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `can_share`   TINYINT(1) DEFAULT 0;

-- ── 2a. Document template categories (see database/seeds/002_doc_categories.sql
--       for the full explanation — phase6_templates.sql cannot succeed
--       without these 10 rows existing first) ────────────────────────────
INSERT IGNORE INTO `doc_categories` (`id`, `name`, `slug`, `icon`, `sort_order`, `is_active`) VALUES
(1,  'Employment Letters', 'employment_letters', 'file-text',      1,  1),
(2,  'HR Letters',         'hr_letters',         'file-text',      2,  1),
(3,  'Certificates',       'certificates',       'award',          3,  1),
(4,  'Payroll Documents',  'payroll_documents',  'dollar-sign',    4,  1),
(5,  'Leave Documents',    'leave_documents',    'calendar',       5,  1),
(6,  'Disciplinary',       'disciplinary',       'alert-triangle', 6,  1),
(7,  'Compliance',         'compliance',         'shield',         7,  1),
(8,  'Onboarding',         'onboarding',         'user-plus',      8,  1),
(9,  'Exit Management',    'exit_management',    'log-out',        9,  1),
(10, 'General',            'general',            'file',           10, 1);

-- ── 3. Role-name typo correction for any pre-Phase-1 database ───────────
-- No-op if phase10_authorization_framework.sql (Phase 1) already ran and
-- fixed these rows -- included here so a database that skipped straight to
-- Phase 3 without ever running phase10 is still corrected.
UPDATE `role_permissions` SET `role` = 'hr_officer' WHERE `role` = 'hrofficer';

-- ── 4. Migration tracking table (see database/migrate.php) ──────────────
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(150) NOT NULL,
  `checksum` char(64) DEFAULT NULL COMMENT 'sha256 of the migration file at the time it was applied',
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `execution_time_ms` int(10) unsigned DEFAULT NULL,
  `status` enum('success','failed') NOT NULL DEFAULT 'success',
  `error_summary` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_name` (`migration_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
