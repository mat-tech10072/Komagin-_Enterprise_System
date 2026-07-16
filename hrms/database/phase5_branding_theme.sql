-- ============================================================
-- PHASE 5 — BRANDING, SIGNATURES, STAMPS, EMAIL, THEME
-- Run against: komagin_hr
-- ============================================================
USE komagin_hr;

-- ── 1. Expand company_settings ────────────────────────────────────────────
ALTER TABLE `company_settings`
    ADD COLUMN IF NOT EXISTS `company_favicon`      VARCHAR(255) NULL AFTER `company_logo`,
    ADD COLUMN IF NOT EXISTS `company_footer`       VARCHAR(255) NULL AFTER `company_favicon`,
    ADD COLUMN IF NOT EXISTS `login_background`     VARCHAR(255) NULL AFTER `company_footer`,
    ADD COLUMN IF NOT EXISTS `theme_settings`       JSON NULL AFTER `archive_settings`,
    ADD COLUMN IF NOT EXISTS `email_settings`       JSON NULL AFTER `theme_settings`,
    ADD COLUMN IF NOT EXISTS `doc_number_prefix`    VARCHAR(20) DEFAULT 'KHR' AFTER `email_settings`,
    ADD COLUMN IF NOT EXISTS `doc_number_counter`   INT UNSIGNED DEFAULT 1 AFTER `doc_number_prefix`;

-- ── 2. Letterheads ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `company_letterheads` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`         VARCHAR(150) NOT NULL,
    `type`         ENUM('official','contract','payroll','hr_letter','certificate','memo','general') DEFAULT 'official',
    `image_path`   VARCHAR(500) NOT NULL,
    `header_html`  LONGTEXT NULL COMMENT 'Optional HTML header alternative to image',
    `footer_html`  LONGTEXT NULL COMMENT 'Optional HTML footer',
    `paper_size`   ENUM('A4','A5','Letter','Legal') DEFAULT 'A4',
    `orientation`  ENUM('portrait','landscape') DEFAULT 'portrait',
    `margin_top`   SMALLINT DEFAULT 120 COMMENT 'px margin from top to leave space for letterhead',
    `margin_bottom`SMALLINT DEFAULT 60,
    `is_default`   TINYINT(1) DEFAULT 0,
    `is_active`    TINYINT(1) DEFAULT 1,
    `created_by`   INT UNSIGNED NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── 3. Signatures ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `company_signatures` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `signatory_name` VARCHAR(150) NOT NULL,
    `designation`    VARCHAR(150) NULL,
    `department`     VARCHAR(150) NULL,
    `image_path`     VARCHAR(500) NOT NULL COMMENT 'Transparent PNG of signature',
    `approval_level` TINYINT UNSIGNED DEFAULT 1 COMMENT '1=Officer, 2=Manager, 3=Director',
    `is_active`      TINYINT(1) DEFAULT 1,
    `version`        TINYINT UNSIGNED DEFAULT 1,
    `notes`          TEXT NULL,
    `created_by`     INT UNSIGNED NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── 4. Stamps ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `company_stamps` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(150) NOT NULL,
    `image_path`  VARCHAR(500) NOT NULL COMMENT 'Transparent PNG of stamp',
    `is_active`   TINYINT(1) DEFAULT 1,
    `created_by`  INT UNSIGNED NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── 5. Watermarks ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `company_watermarks` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(150) NOT NULL,
    `type`        ENUM('image','text') DEFAULT 'text',
    `image_path`  VARCHAR(500) NULL,
    `text`        VARCHAR(100) NULL COMMENT 'e.g. CONFIDENTIAL, DRAFT, COPY',
    `opacity`     DECIMAL(3,2) DEFAULT 0.10,
    `color`       VARCHAR(20) DEFAULT '#808080',
    `font_size`   SMALLINT DEFAULT 48,
    `rotation`    SMALLINT DEFAULT -45,
    `is_active`   TINYINT(1) DEFAULT 1,
    `created_by`  INT UNSIGNED NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── 6. Expand doc_templates with config columns ───────────────────────────
ALTER TABLE `doc_templates`
    ADD COLUMN IF NOT EXISTS `letterhead_id`    INT UNSIGNED NULL AFTER `requires_approval`,
    ADD COLUMN IF NOT EXISTS `signature_ids`    JSON NULL AFTER `letterhead_id`,
    ADD COLUMN IF NOT EXISTS `stamp_id`         INT UNSIGNED NULL AFTER `signature_ids`,
    ADD COLUMN IF NOT EXISTS `watermark_id`     INT UNSIGNED NULL AFTER `stamp_id`,
    ADD COLUMN IF NOT EXISTS `show_letterhead`  TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `show_signature`   TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `show_stamp`       TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `show_watermark`   TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `show_qr_code`     TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `show_doc_number`  TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `show_page_number` TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `show_header`      TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `show_footer`      TINYINT(1) DEFAULT 1;

-- ── 7. Email notification log ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type`           ENUM('payslip','leave_approval','leave_rejection','document','general','test') DEFAULT 'general',
    `recipient_name` VARCHAR(200) NULL,
    `recipient_email`VARCHAR(200) NOT NULL,
    `subject`        VARCHAR(500) NOT NULL,
    `body_html`      LONGTEXT NULL,
    `status`         ENUM('sent','failed','pending','bounced') DEFAULT 'pending',
    `employee_id`    INT UNSIGNED NULL,
    `reference_id`   INT UNSIGNED NULL COMMENT 'Payslip ID, Leave ID, Document ID, etc',
    `reference_type` VARCHAR(50) NULL,
    `failure_reason` TEXT NULL,
    `retry_count`    TINYINT DEFAULT 0,
    `sent_at`        DATETIME NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email_status`  (`status`),
    INDEX `idx_email_type`    (`type`),
    INDEX `idx_email_emp`     (`employee_id`)
);

-- ── 8. New permissions ────────────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`, `description`) VALUES
('Manage Letterheads',  'branding.letterheads', 'branding', 'Upload and manage company letterheads'),
('Manage Signatures',   'branding.signatures',  'branding', 'Upload and manage signatory signatures'),
('Manage Stamps',       'branding.stamps',      'branding', 'Upload and manage company stamps'),
('Manage Watermarks',   'branding.watermarks',  'branding', 'Upload and manage watermarks'),
('Manage Theme',        'branding.theme',       'branding', 'Configure system colours and appearance'),
('Send Email',          'email.send',           'email',    'Send email notifications'),
('View Email Logs',     'email.logs',           'email',    'View email delivery logs');

-- Grant all branding/email permissions to super_admin and hr_manager
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'super_admin', id, 1,1,1,1,1,1,1,1 FROM `permissions`
WHERE `module` IN ('branding','email')
ON DUPLICATE KEY UPDATE can_view=1, can_create=1, can_edit=1, can_delete=1;

INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'hr_manager', id, 1,1,1,1,1,1,1,1 FROM `permissions`
WHERE `module` IN ('branding','email')
ON DUPLICATE KEY UPDATE can_view=1, can_create=1, can_edit=1, can_delete=1;

-- hr_officer: view email logs only
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'hr_officer', id, 1,0,0,0,0,0,0,0 FROM `permissions`
WHERE `slug` = 'email.logs'
ON DUPLICATE KEY UPDATE can_view=1;

-- ── 9. Default watermarks ─────────────────────────────────────────────────
INSERT IGNORE INTO `company_watermarks` (`name`, `type`, `text`, `opacity`, `color`, `font_size`, `rotation`, `is_active`) VALUES
('Confidential', 'text', 'CONFIDENTIAL', 0.10, '#cc0000', 52, -45, 1),
('Draft',        'text', 'DRAFT',        0.12, '#888888', 52, -45, 1),
('Copy',         'text', 'COPY',         0.10, '#888888', 52, -45, 1),
('Original',     'text', 'ORIGINAL',     0.10, '#006600', 52, -45, 1);

SELECT 'company_letterheads' as t, COUNT(*) as n FROM company_letterheads
UNION ALL SELECT 'company_signatures', COUNT(*) FROM company_signatures
UNION ALL SELECT 'company_stamps',     COUNT(*) FROM company_stamps
UNION ALL SELECT 'company_watermarks', COUNT(*) FROM company_watermarks
UNION ALL SELECT 'email_logs',         COUNT(*) FROM email_logs
UNION ALL SELECT 'new permissions',    COUNT(*) FROM permissions WHERE module IN ('branding','email');
