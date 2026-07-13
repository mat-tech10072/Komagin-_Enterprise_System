-- ============================================================
-- KOMAGIN HR MANAGEMENT SYSTEM — CANONICAL DATABASE SCHEMA
-- ============================================================
--
-- Regenerated: Phase 3 — Database Schema Integrity, Migration
-- Safety & Data-Layer Hardening (2026-07-12)
--
-- PROVENANCE: This file was reconstructed from the verified live
-- development database (a full mysqldump backup taken and validated
-- immediately before this file was rewritten — see
-- database/backups/pre_phase3_backup_20260712_082335.sql and
-- docs/remediation/Database/05-phase3-schema-drift-matrix.md for the
-- full before/after analysis). It replaces a prior schema.sql that
-- defined only 32 of the 59 tables the running application actually
-- depends on -- the other 27 existed only via undocumented manual
-- changes, one-off fix scripts (database/fix_payslips_columns.php,
-- now superseded), or migration files that only ever ALTERed a base
-- definition this file never had in the first place.
--
-- STRUCTURE ONLY: This file contains CREATE TABLE statements only —
-- no data, no default admin account, no permission grants. Baseline
-- data lives in database/seeds/. This is deliberate: a schema file
-- should not be the thing that decides what a fresh install's
-- default password is.
--
-- ORDERING: Tables appear in verified topological (dependency-safe)
-- order — every table referenced by a foreign key is defined before
-- the table that references it. This order was computed from the
-- live database's actual `information_schema.KEY_COLUMN_USAGE`
-- foreign-key graph (see docs/remediation/Database/07-phase3-migration-dependency-report.md),
-- not assumed or hand-sorted, so importing this file in a single
-- pass with FOREIGN_KEY_CHECKS left at its default (1/on) succeeds.
--
-- FOR A FRESH INSTALL: run this file, then everything under
-- database/seeds/ in filename order. See database/README.md.
--
-- FOR AN EXISTING INSTALLATION (upgrading from before Phase 3): do
-- NOT re-run this file against a populated database — every
-- statement uses CREATE TABLE IF NOT EXISTS, so it is safe to do so,
-- but the correct upgrade path is database/phase11_schema_reconciliation.sql,
-- which brings an existing "Phase 10" database up to this same
-- structure without requiring a rebuild. See database/README.md.
--
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────
-- Table: departments
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `departments` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `head_employee_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dept_name_unique` (`name`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: positions
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `positions` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `job_grade` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: employees
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employees` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_number` varchar(30) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `preferred_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed','other') DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `personal_email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `phone_alt` varchar(30) DEFAULT NULL,
  `residential_address` text DEFAULT NULL,
  `postal_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state_province` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Papua New Guinea',
  `postal_code` varchar(20) DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `position_id` int(10) unsigned DEFAULT NULL,
  `supervisor_id` int(10) unsigned DEFAULT NULL,
  `employment_type` enum('full_time','part_time','contract','casual','intern') DEFAULT 'full_time',
  `status` enum('active','probation','suspended','on_leave','resigned','terminated','deceased','archived') DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `probation_start` date DEFAULT NULL,
  `probation_end` date DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT NULL,
  `pay_frequency` enum('weekly','bi_weekly','monthly') DEFAULT 'monthly',
  `work_location` varchar(150) DEFAULT NULL,
  `kiosk_pin` varchar(255) DEFAULT NULL,
  `portal_password` varchar(255) DEFAULT NULL,
  `portal_policy_agreed` tinyint(1) DEFAULT 0,
  `portal_policy_agreed_at` datetime DEFAULT NULL,
  `portal_last_login` datetime DEFAULT NULL,
  `portal_active` tinyint(1) DEFAULT 1,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_branch_code` varchar(20) DEFAULT NULL,
  `bank_account_type` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_relation` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `emergency_contact_email` varchar(150) DEFAULT NULL,
  `nok_name` varchar(150) DEFAULT NULL,
  `nok_relation` varchar(100) DEFAULT NULL,
  `nok_phone` varchar(30) DEFAULT NULL,
  `nok_address` text DEFAULT NULL,
  `status_reason` text DEFAULT NULL,
  `exit_date` date DEFAULT NULL,
  `exit_reason` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_number` (`employee_number`),
  KEY `position_id` (`position_id`),
  KEY `idx_emp_number` (`employee_number`),
  KEY `idx_emp_status` (`status`),
  KEY `idx_emp_dept` (`department_id`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: users
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned DEFAULT NULL,
  `username` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `job_title` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password_changed_at` datetime DEFAULT NULL COMMENT 'Phase 5, Stage 5.5: compared against a session login_time to force re-login on other sessions after a password change/reset',
  `role` enum('super_admin','hr_manager','hr_officer','supervisor','employee','finance_viewer','payroll_manager','payroll_officer','recruitment_officer','training_officer','kiosk_terminal') NOT NULL DEFAULT 'employee',
  `is_active` tinyint(1) DEFAULT 1,
  `must_change_password` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: approval_workflows
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: approval_stages
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: archive_records
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `archive_records` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `archive_type` enum('monthly','quarterly','yearly') NOT NULL,
  `year` year(4) NOT NULL,
  `month` tinyint(2) DEFAULT NULL,
  `quarter` tinyint(1) DEFAULT NULL,
  `document_type` enum('timesheets','attendance','leave_report','overtime_report','payroll_support','hr_summary','employee_list','recruitment_summary','training_summary','disciplinary_summary','compliance') NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `locked_by` int(10) unsigned DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `generated_by` int(10) unsigned DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_archive_year_month` (`year`,`month`),
  KEY `idx_archive_type` (`archive_type`,`document_type`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: company_assets
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `company_assets` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(30) DEFAULT NULL,
  `asset_type` enum('laptop','phone','vehicle','ppe','tools','id_card','uniform','other') NOT NULL,
  `description` varchar(255) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `make_model` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_value` decimal(12,2) DEFAULT NULL,
  `current_condition` enum('excellent','good','fair','poor','damaged','lost') DEFAULT 'good',
  `image` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: asset_assignments
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `asset_assignments` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) unsigned NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `issued_date` date NOT NULL,
  `condition_on_issue` enum('excellent','good','fair','poor') DEFAULT 'good',
  `issued_by` int(10) unsigned DEFAULT NULL,
  `acknowledgement` tinyint(1) DEFAULT 0,
  `acknowledgement_date` datetime DEFAULT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `condition_on_return` enum('excellent','good','fair','poor','damaged','lost') DEFAULT NULL,
  `return_remarks` text DEFAULT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `is_returned` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `asset_assignments_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `company_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_assignments_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: attendance
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `attendance` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `employee_number` varchar(30) NOT NULL,
  `attendance_date` date NOT NULL,
  `sign_in` time DEFAULT NULL,
  `break_out` time DEFAULT NULL,
  `break_in` time DEFAULT NULL,
  `sign_out` time DEFAULT NULL,
  `break_duration_minutes` int(11) DEFAULT 0,
  `total_hours_worked` decimal(5,2) DEFAULT 0.00,
  `normal_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `is_late` tinyint(1) DEFAULT 0,
  `late_minutes` int(11) DEFAULT 0,
  `is_early_departure` tinyint(1) DEFAULT 0,
  `is_absent` tinyint(1) DEFAULT 0,
  `is_on_leave` tinyint(1) DEFAULT 0,
  `status` enum('present','absent','late','on_leave','half_day','holiday') DEFAULT 'present',
  `is_manually_adjusted` tinyint(1) DEFAULT 0,
  `adjustment_reason` text DEFAULT NULL,
  `adjusted_by` int(10) unsigned DEFAULT NULL,
  `adjusted_at` datetime DEFAULT NULL,
  `hr_remarks` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `device_info` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_unique` (`employee_id`,`attendance_date`),
  KEY `idx_att_date` (`attendance_date`),
  KEY `idx_att_emp` (`employee_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: audit_logs
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `audit_logs` (

  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `record_id` int(10) unsigned DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_date` (`created_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: company_letterheads
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `company_letterheads` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `type` enum('official','contract','payroll','hr_letter','certificate','memo','general') DEFAULT 'official',
  `image_path` varchar(500) NOT NULL,
  `header_html` longtext DEFAULT NULL COMMENT 'Optional HTML header alternative to image',
  `footer_html` longtext DEFAULT NULL COMMENT 'Optional HTML footer',
  `paper_size` enum('A4','A5','Letter','Legal') DEFAULT 'A4',
  `orientation` enum('portrait','landscape') DEFAULT 'portrait',
  `margin_top` smallint(6) DEFAULT 120 COMMENT 'px margin from top to leave space for letterhead',
  `margin_bottom` smallint(6) DEFAULT 60,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: company_settings
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `company_settings` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) DEFAULT 'Komagin Limited',
  `company_logo` varchar(255) DEFAULT NULL,
  `company_favicon` varchar(255) DEFAULT NULL,
  `company_footer` varchar(255) DEFAULT NULL,
  `login_background` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `work_start_time` time DEFAULT '08:00:00',
  `work_end_time` time DEFAULT '17:00:00',
  `grace_period_minutes` int(11) DEFAULT 15,
  `break_duration_minutes` int(11) DEFAULT 60,
  `standard_work_hours` decimal(4,2) DEFAULT 8.00,
  `overtime_threshold_hours` decimal(4,2) DEFAULT 8.00,
  `emp_number_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`emp_number_settings`)),
  `leave_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`leave_settings`)),
  `archive_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`archive_settings`)),
  `theme_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`theme_settings`)),
  `email_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`email_settings`)),
  `doc_number_prefix` varchar(20) DEFAULT 'KHR',
  `doc_number_counter` int(10) unsigned DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: company_signatures
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `company_signatures` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `signatory_name` varchar(150) NOT NULL,
  `designation` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `image_path` varchar(500) NOT NULL COMMENT 'Transparent PNG of signature',
  `approval_level` tinyint(3) unsigned DEFAULT 1 COMMENT '1=Officer, 2=Manager, 3=Director',
  `is_active` tinyint(1) DEFAULT 1,
  `version` tinyint(3) unsigned DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: company_stamps
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `company_stamps` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `image_path` varchar(500) NOT NULL COMMENT 'Transparent PNG of stamp',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: company_watermarks
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `company_watermarks` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `type` enum('image','text') DEFAULT 'text',
  `image_path` varchar(500) DEFAULT NULL,
  `text` varchar(100) DEFAULT NULL COMMENT 'e.g. CONFIDENTIAL, DRAFT, COPY',
  `opacity` decimal(3,2) DEFAULT 0.10,
  `color` varchar(20) DEFAULT '#808080',
  `font_size` smallint(6) DEFAULT 48,
  `rotation` smallint(6) DEFAULT -45,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: consultants
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `consultants` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `consultant_number` varchar(30) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `company` varchar(200) DEFAULT NULL,
  `position_title` varchar(200) DEFAULT NULL,
  `type` enum('time_based','output_based') NOT NULL DEFAULT 'time_based',
  `department` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','completed','terminated') NOT NULL DEFAULT 'active',
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `contract_value` decimal(12,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `portal_active` tinyint(1) NOT NULL DEFAULT 0,
  `portal_password` varchar(255) DEFAULT NULL,
  `portal_last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `consultant_number` (`consultant_number`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: consultant_attendance
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `consultant_attendance` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `consultant_id` int(10) unsigned NOT NULL,
  `work_date` date NOT NULL,
  `clock_in` datetime DEFAULT NULL,
  `break_start` datetime DEFAULT NULL,
  `break_end` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_consultant_date` (`consultant_id`,`work_date`),
  CONSTRAINT `consultant_attendance_ibfk_1` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: consultant_scopes
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `consultant_scopes` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `consultant_id` int(10) unsigned NOT NULL,
  `title` varchar(300) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `due_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed','on_hold') NOT NULL DEFAULT 'pending',
  `completion_pct` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `hr_notes` text DEFAULT NULL,
  `consultant_notes` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `consultant_id` (`consultant_id`),
  CONSTRAINT `consultant_scopes_ibfk_1` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: correction_requests
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `correction_requests` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `attendance_id` int(10) unsigned DEFAULT NULL,
  `request_date` date NOT NULL,
  `request_type` enum('forgot_sign_in','forgot_sign_out','forgot_break_out','forgot_break_in','wrong_time','overtime_not_captured','other') NOT NULL,
  `description` text NOT NULL,
  `requested_sign_in` time DEFAULT NULL,
  `requested_sign_out` time DEFAULT NULL,
  `requested_break_out` time DEFAULT NULL,
  `requested_break_in` time DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `hr_remarks` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `attendance_id` (`attendance_id`),
  CONSTRAINT `correction_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `correction_requests_ibfk_2` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: disciplinary_records
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `disciplinary_records` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `case_number` varchar(30) DEFAULT NULL,
  `incident_date` date NOT NULL,
  `incident_description` text NOT NULL,
  `case_type` enum('misconduct','poor_performance','absenteeism','insubordination','harassment','theft','fraud','other') NOT NULL,
  `action_taken` enum('verbal_warning','written_warning','final_warning','suspension','demotion','termination','dismissed','no_action') DEFAULT NULL,
  `investigation_notes` text DEFAULT NULL,
  `evidence_file` varchar(255) DEFAULT NULL,
  `warning_letter_file` varchar(255) DEFAULT NULL,
  `status` enum('open','investigating','closed','appealed') DEFAULT 'open',
  `hearing_date` date DEFAULT NULL,
  `resolved_at` date DEFAULT NULL,
  `hr_officer_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `disciplinary_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: doc_categories
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: doc_templates
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: doc_template_versions
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: email_logs
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `email_logs` (

  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('payslip','leave_approval','leave_rejection','document','general','test','password_reset') DEFAULT 'general',
  `recipient_name` varchar(200) DEFAULT NULL,
  `recipient_email` varchar(200) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body_html` longtext DEFAULT NULL,
  `status` enum('sent','failed','pending','bounced') DEFAULT 'pending',
  `employee_id` int(10) unsigned DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL COMMENT 'Payslip ID, Leave ID, Document ID, etc',
  `reference_type` varchar(50) DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `retry_count` tinyint(4) DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_status` (`status`),
  KEY `idx_email_type` (`type`),
  KEY `idx_email_emp` (`employee_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: employee_dependents
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: employee_documents
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employee_documents` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `category` enum('id_document','certificate','contract','medical','warning_letter','promotion_letter','leave_document','resignation','clearance','payslip','bank_document','training_certificate','other') NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(10) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `uploaded_by` int(10) unsigned DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_doc_emp` (`employee_id`),
  KEY `idx_doc_expiry` (`expiry_date`),
  CONSTRAINT `employee_documents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: employee_pending_updates
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employee_pending_updates` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `update_link_id` int(10) unsigned DEFAULT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_label` varchar(150) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_pending_updates_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: employee_qualifications
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: employee_requests
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employee_requests` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `request_type` enum('leave_query','payslip_query','employment_certificate','bank_update','salary_query','training_request','general_query','grievance','payroll_query','document_request') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `status` enum('open','in_progress','resolved','closed','rejected') DEFAULT 'open',
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `hr_response` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `resolved_by` int(10) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `attachment` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: employee_savings
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employee_savings` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `savings_type` enum('pension','provident','medical_aid','funeral','savings','other') NOT NULL,
  `fund_name` varchar(255) DEFAULT NULL,
  `target_amount` decimal(14,2) DEFAULT 0.00,
  `current_balance` decimal(14,2) DEFAULT 0.00,
  `employee_rate_pct` decimal(5,2) DEFAULT 0.00,
  `employer_rate_pct` decimal(5,2) DEFAULT 0.00,
  `monthly_employee_contrib` decimal(12,2) DEFAULT 0.00,
  `monthly_employer_contrib` decimal(12,2) DEFAULT 0.00,
  `total_employee_contrib` decimal(14,2) DEFAULT 0.00,
  `total_employer_contrib` decimal(14,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `projected_end_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_savings_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: employee_skills
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employee_skills` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `skill_name` varchar(150) NOT NULL,
  `proficiency` enum('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
  `certificate_file` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_skills_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: employee_status_history
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employee_status_history` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_status_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: employee_update_links
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employee_update_links` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `link_type` enum('monthly','quarterly','individual','request_based') DEFAULT 'individual',
  `scope` enum('all','department','individual') DEFAULT 'individual',
  `department_id` int(10) unsigned DEFAULT NULL,
  `employee_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_revoked` tinyint(1) DEFAULT 0,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_update_links_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: employee_work_history
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: generated_documents
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: grievance_records
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `grievance_records` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `case_number` varchar(30) DEFAULT NULL,
  `filed_date` date NOT NULL,
  `complaint_description` text NOT NULL,
  `grievance_type` varchar(100) DEFAULT NULL,
  `assigned_hr_officer` int(10) unsigned DEFAULT NULL,
  `investigation_notes` text DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `status` enum('open','investigating','resolved','closed') DEFAULT 'open',
  `resolved_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `grievance_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: kiosk_audit
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: kiosk_sessions
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: leave_types
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leave_types` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `max_days` int(11) DEFAULT 0,
  `carry_forward` tinyint(1) DEFAULT 0,
  `max_carry_forward_days` int(11) DEFAULT 0,
  `requires_document` tinyint(1) DEFAULT 0,
  `is_paid` tinyint(1) DEFAULT 1,
  `approval_required` tinyint(1) DEFAULT 1,
  `gender_specific` enum('all','male','female') DEFAULT 'all',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: leave_applications
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leave_applications` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `leave_type_id` int(10) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(5,1) DEFAULT 0.0,
  `reason` text DEFAULT NULL,
  `supporting_document` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `supervisor_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `supervisor_id` int(10) unsigned DEFAULT NULL,
  `supervisor_reviewed_at` datetime DEFAULT NULL,
  `supervisor_remarks` text DEFAULT NULL,
  `hr_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `hr_reviewed_by` int(10) unsigned DEFAULT NULL,
  `hr_reviewed_at` datetime DEFAULT NULL,
  `hr_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `leave_type_id` (`leave_type_id`),
  CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_applications_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: leave_balances
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leave_balances` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `leave_type_id` int(10) unsigned NOT NULL,
  `year` year(4) NOT NULL,
  `entitled_days` decimal(5,1) DEFAULT 0.0,
  `used_days` decimal(5,1) DEFAULT 0.0,
  `pending_days` decimal(5,1) DEFAULT 0.0,
  `carried_forward` decimal(5,1) DEFAULT 0.0,
  `remaining_days` decimal(5,1) DEFAULT 0.0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_balance_unique` (`employee_id`,`leave_type_id`,`year`),
  KEY `leave_type_id` (`leave_type_id`),
  CONSTRAINT `leave_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_balances_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: notifications
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: onboarding_checklists
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `onboarding_checklists` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `task_name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_by` int(10) unsigned DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `onboarding_checklists_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: overtime_records
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `overtime_records` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attendance_id` int(10) unsigned NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `overtime_date` date NOT NULL,
  `suggested_hours` decimal(5,2) DEFAULT 0.00,
  `approved_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_type` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `hr_remarks` text DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `attendance_id` (`attendance_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `overtime_records_ibfk_1` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE CASCADE,
  CONSTRAINT `overtime_records_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: payroll_deductions
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payroll_deductions` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `deduction_type` enum('tax','uif','pension','provident','medical_aid','loan','garnishee','other') NOT NULL,
  `description` varchar(255) NOT NULL,
  `is_percentage` tinyint(1) DEFAULT 0,
  `amount` decimal(12,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `employer_contribution` decimal(12,2) DEFAULT NULL,
  `employer_percentage` decimal(5,2) DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `payroll_deductions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: payroll_runs
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payroll_runs` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `period_month` tinyint(2) NOT NULL,
  `period_year` year(4) NOT NULL,
  `status` enum('draft','processing','finalized','published') DEFAULT 'draft',
  `total_gross` decimal(14,2) DEFAULT 0.00,
  `total_net` decimal(14,2) DEFAULT 0.00,
  `total_deductions` decimal(14,2) DEFAULT 0.00,
  `employee_count` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `processed_by` int(10) unsigned DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_run_unique` (`period_month`,`period_year`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: payslips
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payslips` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `period_month` tinyint(2) NOT NULL,
  `period_year` year(4) NOT NULL,
  `payroll_run_id` int(10) unsigned DEFAULT NULL,
  `gross_salary` decimal(12,2) DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `deductions` decimal(12,2) DEFAULT NULL,
  `net_salary` decimal(12,2) DEFAULT NULL,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `uif_employee` decimal(12,2) DEFAULT 0.00,
  `uif_employer` decimal(12,2) DEFAULT 0.00,
  `other_deductions` decimal(12,2) DEFAULT 0.00,
  `total_employer_cost` decimal(12,2) DEFAULT 0.00,
  `overtime_hours` decimal(6,2) DEFAULT 0.00,
  `overtime_amount` decimal(12,2) DEFAULT 0.00,
  `leave_days_taken` decimal(5,1) DEFAULT 0.0,
  `notes` text DEFAULT NULL,
  `status` enum('draft','finalized','sent') DEFAULT 'draft',
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_by` int(10) unsigned DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payslip_unique` (`employee_id`,`period_month`,`period_year`),
  CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: payslip_items
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payslip_items` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `payslip_id` int(10) unsigned NOT NULL,
  `item_type` enum('earning','deduction','employer_contribution','info') NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sort_order` tinyint(3) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `payslip_id` (`payslip_id`),
  CONSTRAINT `payslip_items_ibfk_1` FOREIGN KEY (`payslip_id`) REFERENCES `payslips` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: performance_reviews
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `performance_reviews` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `reviewer_id` int(10) unsigned NOT NULL,
  `review_period` varchar(50) DEFAULT NULL,
  `review_date` date NOT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `self_assessment` text DEFAULT NULL,
  `supervisor_assessment` text DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `improvements` text DEFAULT NULL,
  `recommendation` enum('promote','salary_review','training','warning','no_action') DEFAULT NULL,
  `recommendation_notes` text DEFAULT NULL,
  `status` enum('draft','submitted','completed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `performance_reviews_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: permissions
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `permissions` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: recruitment_vacancies
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `recruitment_vacancies` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_title` varchar(150) NOT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `position_id` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `employment_type` enum('full_time','part_time','contract','casual','intern') DEFAULT 'full_time',
  `location` varchar(150) DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('draft','open','closed','on_hold') DEFAULT 'draft',
  `positions_available` int(11) DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `recruitment_vacancies_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: recruitment_applications
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `recruitment_applications` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vacancy_id` int(10) unsigned NOT NULL,
  `application_number` varchar(30) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `current_position` varchar(150) DEFAULT NULL,
  `current_employer` varchar(150) DEFAULT NULL,
  `years_experience` int(11) DEFAULT 0,
  `qualifications` text DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `cv_file` varchar(255) DEFAULT NULL,
  `certificate_file` varchar(255) DEFAULT NULL,
  `cover_letter_file` varchar(255) DEFAULT NULL,
  `status` enum('submitted','reviewing','shortlisted','interview_scheduled','interviewed','selected','rejected','withdrawn') DEFAULT 'submitted',
  `interview_date` datetime DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `hr_remarks` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `converted_to_employee_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vacancy_id` (`vacancy_id`),
  CONSTRAINT `recruitment_applications_ibfk_1` FOREIGN KEY (`vacancy_id`) REFERENCES `recruitment_vacancies` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: role_permissions
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `role_permissions` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(50) NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `can_export` tinyint(1) DEFAULT 0,
  `can_publish` tinyint(1) DEFAULT 0,
  `can_share` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permission_unique` (`role`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: temp_projects
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `temp_projects` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(30) NOT NULL,
  `client` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','on_hold','completed') NOT NULL DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: temp_sites
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `temp_sites` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `temp_sites_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `temp_projects` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: temp_employees
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `temp_employees` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_number` varchar(30) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `position_title` varchar(200) DEFAULT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `site_id` int(10) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','completed','terminated') NOT NULL DEFAULT 'active',
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `rate_type` enum('daily','hourly') NOT NULL DEFAULT 'daily',
  `notes` text DEFAULT NULL,
  `portal_active` tinyint(1) NOT NULL DEFAULT 0,
  `attendance_method` enum('kiosk','timesheet','both') NOT NULL DEFAULT 'kiosk',
  `portal_password` varchar(255) DEFAULT NULL,
  `portal_last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_number` (`employee_number`),
  KEY `project_id` (`project_id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `temp_employees_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `temp_projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `temp_employees_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `temp_sites` (`id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: training_programs
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `training_programs` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `provider` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `cost` decimal(12,2) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('planned','ongoing','completed','cancelled') DEFAULT 'planned',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: training_attendance
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `training_attendance` (

  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `training_id` int(10) unsigned NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `attended` tinyint(1) DEFAULT 0,
  `certificate_file` varchar(255) DEFAULT NULL,
  `certificate_expiry` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `training_emp_unique` (`training_id`,`employee_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `training_attendance_ibfk_1` FOREIGN KEY (`training_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_attendance_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: schema_migrations
-- New in Phase 3 — tracks which migration files have been applied to
-- this database, so the migration runner (database/migrate.php) never
-- re-runs a completed migration and can report pending/applied state
-- accurately. See docs/remediation/Database/06-phase3-canonical-database-model.md.
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Table: work_calendar_settings
-- New in Phase 5, Stage 5.3 — single-row settings (same pattern as
-- company_settings) holding which ISO weekdays count as scheduled
-- working days. See database/phase13_workflow_completeness_automation.sql.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `work_calendar_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `working_weekdays` varchar(20) NOT NULL DEFAULT '1,2,3,4,5' COMMENT 'ISO-8601 weekday numbers, 1=Monday..7=Sunday, comma-separated',
  `timezone` varchar(50) NOT NULL DEFAULT 'Pacific/Port_Moresby',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `work_calendar_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: work_calendar_holidays
-- New in Phase 5, Stage 5.3 — public holidays / organization closure
-- days, optionally recurring annually by month/day.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `work_calendar_holidays` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL COMMENT 'same as start_date for a single-day holiday; later for a closure range',
  `is_recurring_annual` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'if set, month/day repeats every year regardless of the stored year',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_work_calendar_holidays_dates` (`start_date`,`end_date`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `work_calendar_holidays_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: scheduled_task_locks
-- New in Phase 5, Stage 5.4 — single-run lock for cron/run.php so an
-- overlapping cron invocation exits immediately instead of racing.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `scheduled_task_locks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lock_name` varchar(100) NOT NULL,
  `locked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `locked_by` varchar(150) DEFAULT NULL COMMENT 'hostname:pid, for diagnostics only',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_scheduled_task_locks_name` (`lock_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: scheduled_task_runs
-- New in Phase 5, Stage 5.4 — per-task-per-run audit trail for the
-- scheduler (start/finish time, status, items processed, error summary).
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `scheduled_task_runs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_name` varchar(100) NOT NULL,
  `status` enum('running','success','failed') NOT NULL DEFAULT 'running',
  `items_processed` int(10) unsigned NOT NULL DEFAULT 0,
  `error_summary` text DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `finished_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_scheduled_task_runs_name` (`task_name`),
  KEY `idx_scheduled_task_runs_started` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: password_reset_tokens
-- New in Phase 5, Stage 5.5 — self-service password recovery, Admin
-- surface only (KOM-041, user decision). Only a sha256 hash of the raw
-- token is ever stored.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'sha256 of the raw token emailed to the user; the raw token itself is never stored',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `requested_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_password_reset_tokens_hash` (`token_hash`),
  KEY `idx_password_reset_tokens_user` (`user_id`),
  CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: reminder_notifications_log
-- New in Phase 5, Stage 5.6 — deferred notification workflows.
-- Ensures each threshold-based reminder (cron/tasks/send_reminders.php)
-- fires at most once per calendar day per underlying event, regardless
-- of how frequently the scheduler itself runs (cron/README.md
-- recommends 15-30 minutes for the other tasks).
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `reminder_notifications_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reminder_key` varchar(150) NOT NULL COMMENT 'e.g. contract_expiry:employees:23',
  `reminder_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reminder_once_per_day` (`reminder_key`, `reminder_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Table: temp_attendance
-- New in Phase 5, Stage 5.8 — supervisor/HR-entered digital attendance
-- capture for temporary employees (KOM-090, KOM-058). One row per
-- employee per day; re-entry updates rather than duplicates.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `temp_attendance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `hours_worked` decimal(4,1) NOT NULL DEFAULT 0.0,
  `notes` varchar(255) DEFAULT NULL,
  `entered_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_temp_attendance_emp_date` (`employee_id`, `attendance_date`),
  KEY `idx_temp_attendance_date` (`attendance_date`),
  CONSTRAINT `temp_attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `temp_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `temp_attendance_ibfk_2` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
