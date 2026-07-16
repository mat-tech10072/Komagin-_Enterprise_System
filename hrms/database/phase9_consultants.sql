-- ============================================================
-- Phase 9: Consultants Module
-- Two categories: time_based (kiosk clock in/out) and output_based (scope checklist)
-- ============================================================

-- ── 1. Consultants ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS consultants (
    id                INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    consultant_number VARCHAR(30)   NOT NULL UNIQUE,
    first_name        VARCHAR(100)  NOT NULL,
    last_name         VARCHAR(100)  NOT NULL,
    email             VARCHAR(200)  DEFAULT NULL,
    phone             VARCHAR(30)   DEFAULT NULL,
    company           VARCHAR(200)  DEFAULT NULL,
    position_title    VARCHAR(200)  DEFAULT NULL,
    type              ENUM('time_based','output_based') NOT NULL DEFAULT 'time_based',
    department        VARCHAR(100)  DEFAULT NULL,
    start_date        DATE          DEFAULT NULL,
    end_date          DATE          DEFAULT NULL,
    status            ENUM('active','completed','terminated') NOT NULL DEFAULT 'active',
    hourly_rate       DECIMAL(10,2) DEFAULT NULL,
    daily_rate        DECIMAL(10,2) DEFAULT NULL,
    contract_value    DECIMAL(12,2) DEFAULT NULL,
    notes             TEXT          DEFAULT NULL,
    portal_active     TINYINT(1)    NOT NULL DEFAULT 0,
    portal_password   VARCHAR(255)  DEFAULT NULL,
    portal_last_login DATETIME      DEFAULT NULL,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Consultant Attendance (time-based only) ───────────────
CREATE TABLE IF NOT EXISTS consultant_attendance (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consultant_id INT UNSIGNED NOT NULL,
    work_date     DATE         NOT NULL,
    clock_in      DATETIME     DEFAULT NULL,
    break_start   DATETIME     DEFAULT NULL,
    break_end     DATETIME     DEFAULT NULL,
    clock_out     DATETIME     DEFAULT NULL,
    total_hours   DECIMAL(5,2) DEFAULT NULL,
    notes         VARCHAR(500) DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_consultant_date (consultant_id, work_date),
    FOREIGN KEY (consultant_id) REFERENCES consultants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Consultant Scope Items (output-based only) ────────────
CREATE TABLE IF NOT EXISTS consultant_scopes (
    id               INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    consultant_id    INT UNSIGNED     NOT NULL,
    title            VARCHAR(300)     NOT NULL,
    description      TEXT             DEFAULT NULL,
    priority         ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    due_date         DATE             DEFAULT NULL,
    status           ENUM('pending','in_progress','completed','on_hold') NOT NULL DEFAULT 'pending',
    completion_pct   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    hr_notes         TEXT             DEFAULT NULL,
    consultant_notes TEXT             DEFAULT NULL,
    completed_at     DATETIME         DEFAULT NULL,
    sort_order       INT UNSIGNED     NOT NULL DEFAULT 0,
    created_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (consultant_id) REFERENCES consultants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Permissions ──────────────────────────────────────────
INSERT IGNORE INTO permissions (slug, name, module, description) VALUES
('consultants.view',   'View Consultants',   'consultants', 'View consultant list and profiles'),
('consultants.create', 'Create Consultants', 'consultants', 'Add new consultants'),
('consultants.edit',   'Edit Consultants',   'consultants', 'Edit consultant details and scope items'),
('consultants.delete', 'Delete Consultants', 'consultants', 'Delete consultant records');

-- ── 5. Role grants ──────────────────────────────────────────
INSERT IGNORE INTO role_permissions (role, permission_id, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_publish, can_share)
SELECT 'super_admin', id, 1,1,1,1,1,1,1,1 FROM permissions WHERE slug IN
    ('consultants.view','consultants.create','consultants.edit','consultants.delete');

INSERT IGNORE INTO role_permissions (role, permission_id, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_publish, can_share)
SELECT 'hr_manager', id, 1,1,1,0,1,1,0,0 FROM permissions WHERE slug IN
    ('consultants.view','consultants.create','consultants.edit','consultants.delete');

-- Phase 3 fix: this was seeded as 'hrofficer' (no underscore), which does not
-- match the canonical role ENUM value 'hr_officer' used everywhere else in
-- the system — a fresh install using the original version of this file would
-- have silently denied every hr_officer user access to this module, exactly
-- as happened on the live database until Phase 1's phase10 migration patched
-- the existing rows. This is the source-file fix so a NEW install never
-- reintroduces that bug (see docs/remediation/Database/08-phase3-seed-integrity-report.md).
INSERT IGNORE INTO role_permissions (role, permission_id, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_publish, can_share)
SELECT 'hr_officer', id, 1,0,0,0,0,1,0,0 FROM permissions WHERE slug IN
    ('consultants.view','consultants.create','consultants.edit','consultants.delete');

-- ── 6. Seed: Consultants ────────────────────────────────────
INSERT IGNORE INTO consultants
    (consultant_number, first_name, last_name, email, phone, company, position_title, type, department, start_date, end_date, status, hourly_rate, daily_rate, portal_active)
VALUES
('KOM-CON-2026-0001', 'David',    'Naime',  'd.naime@naitech.pg',   '+675 7200 1001', 'NaiTech Solutions',  'IT Systems Consultant', 'time_based',   'ICT',          '2026-02-01', '2026-12-31', 'active',    85.00, 680.00, 0),
('KOM-CON-2026-0002', 'Anna',     'Pepe',   'a.pepe@pmcon.pg',      '+675 7200 1002', 'PM Consultancy Ltd', 'Project Manager',       'time_based',   'Operations',   '2026-03-01', '2026-09-30', 'active',    95.00, 760.00, 0),
('KOM-CON-2026-0003', 'Michael',  'Toua',   'm.toua@legaladv.pg',   '+675 7200 1003', 'Pacific Legal Adv.', 'Legal Advisor',         'output_based', 'HR & Legal',   '2026-01-15', '2026-12-31', 'active',    NULL,  NULL,   0),
('KOM-CON-2026-0004', 'Christine','Kila',   'c.kila@finworks.pg',   '+675 7200 1004', 'FinWorks PNG',       'Financial Analyst',     'output_based', 'Finance',      '2026-04-01', '2026-10-31', 'active',    NULL,  NULL,   0);

-- ── 7. Seed: Attendance for time-based consultants ──────────
-- David Naime (id=1) — past 5 working days
INSERT IGNORE INTO consultant_attendance (consultant_id, work_date, clock_in, break_start, break_end, clock_out, total_hours) VALUES
(1, DATE_SUB(CURDATE(),INTERVAL 4 DAY), DATE_SUB(CURDATE(),INTERVAL 4 DAY) + INTERVAL 8*60+15 MINUTE, DATE_SUB(CURDATE(),INTERVAL 4 DAY) + INTERVAL 12*60 MINUTE, DATE_SUB(CURDATE(),INTERVAL 4 DAY) + INTERVAL 12*60+45 MINUTE, DATE_SUB(CURDATE(),INTERVAL 4 DAY) + INTERVAL 17*60 MINUTE, 8.00),
(1, DATE_SUB(CURDATE(),INTERVAL 3 DAY), DATE_SUB(CURDATE(),INTERVAL 3 DAY) + INTERVAL 8*60+05 MINUTE, DATE_SUB(CURDATE(),INTERVAL 3 DAY) + INTERVAL 12*60 MINUTE, DATE_SUB(CURDATE(),INTERVAL 3 DAY) + INTERVAL 12*60+30 MINUTE, DATE_SUB(CURDATE(),INTERVAL 3 DAY) + INTERVAL 17*60+10 MINUTE, 8.58),
(1, DATE_SUB(CURDATE(),INTERVAL 2 DAY), DATE_SUB(CURDATE(),INTERVAL 2 DAY) + INTERVAL 8*60+20 MINUTE, DATE_SUB(CURDATE(),INTERVAL 2 DAY) + INTERVAL 13*60 MINUTE, DATE_SUB(CURDATE(),INTERVAL 2 DAY) + INTERVAL 13*60+30 MINUTE, DATE_SUB(CURDATE(),INTERVAL 2 DAY) + INTERVAL 17*60+30 MINUTE, 8.67),
(1, DATE_SUB(CURDATE(),INTERVAL 1 DAY), DATE_SUB(CURDATE(),INTERVAL 1 DAY) + INTERVAL 8*60 MINUTE, NULL, NULL, DATE_SUB(CURDATE(),INTERVAL 1 DAY) + INTERVAL 16*60+45 MINUTE, 8.75);

-- Anna Pepe (id=2) — past 3 days
INSERT IGNORE INTO consultant_attendance (consultant_id, work_date, clock_in, break_start, break_end, clock_out, total_hours) VALUES
(2, DATE_SUB(CURDATE(),INTERVAL 2 DAY), DATE_SUB(CURDATE(),INTERVAL 2 DAY) + INTERVAL 9*60 MINUTE, DATE_SUB(CURDATE(),INTERVAL 2 DAY) + INTERVAL 12*60+30 MINUTE, DATE_SUB(CURDATE(),INTERVAL 2 DAY) + INTERVAL 13*60 MINUTE, DATE_SUB(CURDATE(),INTERVAL 2 DAY) + INTERVAL 17*60 MINUTE, 7.50),
(2, DATE_SUB(CURDATE(),INTERVAL 1 DAY), DATE_SUB(CURDATE(),INTERVAL 1 DAY) + INTERVAL 8*60+50 MINUTE, DATE_SUB(CURDATE(),INTERVAL 1 DAY) + INTERVAL 12*60 MINUTE, DATE_SUB(CURDATE(),INTERVAL 1 DAY) + INTERVAL 12*60+45 MINUTE, DATE_SUB(CURDATE(),INTERVAL 1 DAY) + INTERVAL 17*60+15 MINUTE, 7.67);

-- ── 8. Seed: Scope items for output-based consultants ────────
-- Michael Toua (id=3) — Legal Advisor scope
INSERT IGNORE INTO consultant_scopes (consultant_id, title, description, priority, due_date, status, completion_pct, sort_order) VALUES
(3, 'Employment Contract Templates Review', 'Review and update all standard employment contract templates to comply with PNG Labour Act 2023 amendments.', 'high',   '2026-07-31', 'in_progress', 60, 1),
(3, 'HR Policy Manual Legal Audit',         'Audit the existing HR Policy Manual sections against current PNG legislation and flag required updates.', 'high',   '2026-08-15', 'pending',     0,  2),
(3, 'Workplace Grievance Procedure Update', 'Draft revised grievance and disciplinary procedures aligned with Fair Work principles.', 'normal', '2026-09-01', 'pending',     0,  3),
(3, 'NDA Templates for Contractors',        'Prepare standard NDA and IP assignment agreements for external contractors and consultants.', 'normal', '2026-07-20', 'completed',   100, 4),
(3, 'Termination Process Legal Guide',      'Document a step-by-step termination process guide for HR officers with legal risk checkpoints.', 'low',    '2026-10-01', 'pending',     0,  5);

-- Christine Kila (id=4) — Financial Analyst scope
INSERT IGNORE INTO consultant_scopes (consultant_id, title, description, priority, due_date, status, completion_pct, sort_order) VALUES
(4, 'Payroll Cost Analysis Report',         'Prepare a detailed payroll cost breakdown by department, comparing actuals to budget for Q1-Q2 2026.', 'urgent', '2026-07-15', 'in_progress', 75, 1),
(4, 'Superannuation Compliance Review',     'Verify all employee superannuation contributions comply with the Superannuation (General Provisions) Act.', 'high',   '2026-07-30', 'in_progress', 40, 2),
(4, 'Leave Liability Reconciliation',       'Calculate and reconcile outstanding annual leave liabilities for all active employees as of 30 June 2026.', 'high',   '2026-08-01', 'pending',     0,  3),
(4, 'Budget Variance Report — H1 2026',    'Deliver a half-year budget vs actual report for HR-related expenditure with explanatory notes.', 'normal', '2026-08-15', 'pending',     0,  4);

-- Update completed_at for completed scope items
UPDATE consultant_scopes SET completed_at = NOW() WHERE status = 'completed';
