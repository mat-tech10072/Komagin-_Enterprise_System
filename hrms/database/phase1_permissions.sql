-- ============================================================
-- PHASE 1 — ENTERPRISE PERMISSION FOUNDATION
-- Run once against komagin_hr database
-- ============================================================

-- 1. Extend users.role ENUM to include new enterprise roles
ALTER TABLE `users`
    MODIFY `role` ENUM(
        'super_admin',
        'hr_manager',
        'hr_officer',
        'supervisor',
        'employee',
        'finance_viewer',
        'payroll_manager',
        'payroll_officer',
        'recruitment_officer',
        'training_officer',
        'kiosk_terminal'
    ) NOT NULL DEFAULT 'employee';

-- 2. Wipe stale minimal permissions and rebuild complete set
DELETE FROM `role_permissions`;
DELETE FROM `permissions`;
ALTER TABLE `permissions` AUTO_INCREMENT = 1;

-- 3. Add missing columns to role_permissions if they don't exist
-- (can_approve, can_export, can_publish, can_share)
ALTER TABLE `role_permissions`
    ADD COLUMN IF NOT EXISTS `can_approve`  TINYINT(1) DEFAULT 0 AFTER `can_delete`,
    ADD COLUMN IF NOT EXISTS `can_export`   TINYINT(1) DEFAULT 0 AFTER `can_approve`,
    ADD COLUMN IF NOT EXISTS `can_publish`  TINYINT(1) DEFAULT 0 AFTER `can_export`,
    ADD COLUMN IF NOT EXISTS `can_share`    TINYINT(1) DEFAULT 0 AFTER `can_publish`;

-- 4. Insert full permission catalogue
-- Format: (name, slug, module, description)
INSERT INTO `permissions` (`name`, `slug`, `module`, `description`) VALUES

-- DASHBOARD
('View Dashboard',              'dashboard.view',             'dashboard',    'Access HR dashboard'),
('View Dashboard Analytics',    'dashboard.analytics',        'dashboard',    'View analytics charts on dashboard'),

-- EMPLOYEES
('View Employees',              'employees.view',             'employees',    'View employee list and profiles'),
('Create Employees',            'employees.create',           'employees',    'Add new employees'),
('Edit Employees',              'employees.edit',             'employees',    'Edit employee profiles'),
('Delete Employees',            'employees.delete',           'employees',    'Archive or delete employees'),
('Export Employees',            'employees.export',           'employees',    'Export employee data to CSV'),
('Manage Employee Status',      'employees.status',           'employees',    'Change employee employment status'),
('Approve Profile Updates',     'employees.approve_updates',  'employees',    'Approve employee self-service profile update requests'),
('Manage Portal Passwords',     'employees.portal_password',  'employees',    'Set and reset employee portal passwords'),
('Generate Update Links',       'employees.update_links',     'employees',    'Generate tokenised profile update links'),

-- ATTENDANCE
('View Attendance',             'attendance.view',            'attendance',   'View attendance records'),
('Edit Attendance',             'attendance.edit',            'attendance',   'Manually adjust attendance records'),
('Approve Attendance',          'attendance.approve',         'attendance',   'Lock and approve attendance records'),
('Export Attendance',           'attendance.export',          'attendance',   'Export attendance to CSV'),
('Manage Kiosk',                'kiosk.manage',               'attendance',   'Open and close the attendance kiosk'),

-- TIMESHEETS
('View Timesheets',             'timesheets.view',            'timesheets',   'View timesheet records'),
('Edit Timesheets',             'timesheets.edit',            'timesheets',   'Edit timesheet entries'),
('Approve Timesheets',          'timesheets.approve',         'timesheets',   'Approve timesheet correction requests'),
('Approve Overtime',            'timesheets.approve_ot',      'timesheets',   'Approve overtime records'),
('Export Timesheets',           'timesheets.export',          'timesheets',   'Export timesheets to CSV'),

-- LEAVE
('View Leave',                  'leave.view',                 'leave',        'View leave applications'),
('Apply Leave',                 'leave.apply',                'leave',        'Submit leave applications'),
('Approve Leave',               'leave.approve',              'leave',        'Approve or reject leave applications'),
('Manage Leave Types',          'leave.types',                'leave',        'Create and edit leave types'),
('Export Leave',                'leave.export',               'leave',        'Export leave data to CSV'),

-- RECRUITMENT
('View Recruitment',            'recruitment.view',           'recruitment',  'View vacancies and applications'),
('Manage Recruitment',          'recruitment.manage',         'recruitment',  'Create and manage vacancies'),
('Review Applications',         'recruitment.review',         'recruitment',  'Review and shortlist applicants'),
('Export Recruitment',          'recruitment.export',         'recruitment',  'Export recruitment data'),

-- ONBOARDING
('View Onboarding',             'onboarding.view',            'onboarding',   'View onboarding checklists'),
('Manage Onboarding',           'onboarding.manage',          'onboarding',   'Create and complete onboarding tasks'),

-- TRAINING
('View Training',               'training.view',              'training',     'View training programs'),
('Manage Training',             'training.manage',            'training',     'Create and manage training programs'),
('Enrol in Training',           'training.enrol',             'training',     'Enrol employees in training'),
('Export Training',             'training.export',            'training',     'Export training data'),

-- PERFORMANCE
('View Performance',            'performance.view',           'performance',  'View performance reviews'),
('Manage Performance',          'performance.manage',         'performance',  'Create and submit performance reviews'),
('Approve Performance',         'performance.approve',        'performance',  'Finalise performance reviews'),

-- DISCIPLINARY
('View Disciplinary',           'disciplinary.view',          'disciplinary', 'View disciplinary and grievance records'),
('Manage Disciplinary',         'disciplinary.manage',        'disciplinary', 'Create and manage cases'),
('Close Disciplinary',          'disciplinary.close',         'disciplinary', 'Close and resolve disciplinary cases'),

-- ASSETS
('View Assets',                 'assets.view',                'assets',       'View asset register'),
('Manage Assets',               'assets.manage',              'assets',       'Add and manage assets'),
('Assign Assets',               'assets.assign',              'assets',       'Assign and return assets to employees'),

-- DOCUMENTS
('View Documents',              'documents.view',             'documents',    'View employee documents'),
('Upload Documents',            'documents.upload',           'documents',    'Upload documents for employees'),
('Verify Documents',            'documents.verify',           'documents',    'Mark documents as verified'),
('Delete Documents',            'documents.delete',           'documents',    'Delete employee documents'),
('View Missing Documents',      'documents.missing',          'documents',    'View missing document alerts'),

-- PAYROLL
('View Payroll Dashboard',      'payroll.view',               'payroll',      'View payroll dashboard and summaries'),
('Manage Payslips',             'payroll.payslips',           'payroll',      'Create and edit payslips'),
('Run Payroll',                 'payroll.run',                'payroll',      'Create and process payroll runs'),
('Finalize Payroll',            'payroll.finalize',           'payroll',      'Finalize and publish payroll runs'),
('Manage Deductions',           'payroll.deductions',         'payroll',      'Manage employee deduction rules'),
('Manage Savings',              'payroll.savings',            'payroll',      'Manage employee savings and benefits'),
('View Payroll Reports',        'payroll.reports',            'payroll',      'View payroll reports and summaries'),
('Export Payroll',              'payroll.export',             'payroll',      'Export payroll data'),

-- REPORTS
('View Reports',                'reports.view',               'reports',      'Access the reports hub'),
('Export Reports',              'reports.export',             'reports',      'Export reports to CSV/PDF'),
('View Employee Reports',       'reports.employees',          'reports',      'View employee master reports'),
('View Timesheet Reports',      'reports.timesheets',         'reports',      'View timesheet reports'),
('View Payroll Reports (HR)',    'reports.payroll',            'reports',      'View payroll cost reports (HR side)'),

-- ARCHIVE
('View Archive',                'archive.view',               'archive',      'View archived records'),
('Generate Archives',           'archive.generate',           'archive',      'Generate monthly/quarterly/yearly archives'),
('Lock Archives',               'archive.lock',               'archive',      'Lock archive records'),

-- HUB (Employee Requests)
('View Hub Requests',           'hub.view',                   'hub',          'View employee request hub'),
('Manage Hub Requests',         'hub.manage',                 'hub',          'Assign and resolve employee requests'),

-- EMPLOYEE PORTAL
('Access Portal',               'portal.access',              'portal',       'Access employee self-service portal'),
('Submit Portal Requests',      'portal.requests',            'portal',       'Submit requests via employee portal'),
('View Own Payslips',           'portal.payslips',            'portal',       'View own payslips in portal'),
('View Own Attendance',         'portal.attendance',          'portal',       'View own attendance in portal'),
('View Own Leave',              'portal.leave',               'portal',       'View own leave in portal'),

-- ADMIN
('Manage Users',                'users.manage',               'users',        'Create and manage user accounts'),
('View Users',                  'users.view',                 'users',        'View user accounts'),
('Manage Roles',                'roles.manage',               'roles',        'Configure role permissions'),
('Manage Settings',             'settings.manage',            'settings',     'Manage company settings'),
('View Audit Logs',             'audit.view',                 'audit',        'View system audit logs'),
('Export Audit Logs',           'audit.export',               'audit',        'Export audit log data');

-- ============================================================
-- 5. ROLE-PERMISSION MATRIX
-- Columns: role, permission_id, can_view, can_create, can_edit,
--           can_delete, can_approve, can_export, can_publish, can_share
-- Super admin bypasses this table entirely (code-level check).
-- ============================================================

-- Helper: insert by slug name to avoid ID dependency
-- We use a stored procedure pattern via INSERT...SELECT

-- ── HR MANAGER ──────────────────────────────────────────────
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'hr_manager', id,
    1, -- can_view
    CASE WHEN slug NOT IN ('payroll.finalize','payroll.run','roles.manage','audit.export') THEN 1 ELSE 0 END,
    CASE WHEN slug NOT IN ('payroll.finalize','payroll.run','roles.manage','audit.export') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('employees.delete','documents.delete') THEN 1 ELSE 0 END,
    1, -- can_approve: all
    CASE WHEN slug NOT IN ('payroll.export','payroll.finalize','roles.manage') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('payroll.finalize') THEN 0 ELSE 1 END,
    CASE WHEN slug IN ('employees.update_links') THEN 1 ELSE 0 END
FROM `permissions`;

-- ── HR OFFICER ──────────────────────────────────────────────
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'hr_officer', id,
    CASE WHEN slug NOT IN ('payroll.finalize','payroll.run','payroll.export','roles.manage','settings.manage','audit.export','users.manage','payroll.deductions','payroll.savings','reports.payroll') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('employees.create','attendance.edit','leave.apply','documents.upload','onboarding.manage','training.manage','training.enrol','performance.manage','disciplinary.manage','assets.manage','assets.assign','hub.manage','employees.update_links','employees.portal_password') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('employees.edit','attendance.edit','documents.upload','onboarding.manage','training.manage','performance.manage','disciplinary.manage','assets.manage','assets.assign','hub.manage','employees.approve_updates','employees.status') THEN 1 ELSE 0 END,
    0,
    CASE WHEN slug IN ('attendance.approve','timesheets.approve','timesheets.approve_ot','leave.approve','documents.verify','employees.approve_updates','onboarding.manage','performance.approve','disciplinary.close') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('employees.export','attendance.export','timesheets.export','leave.export','reports.employees','reports.timesheets','recruitment.export','training.export') THEN 1 ELSE 0 END,
    0,
    CASE WHEN slug IN ('employees.update_links') THEN 1 ELSE 0 END
FROM `permissions`;

-- ── PAYROLL MANAGER ─────────────────────────────────────────
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'payroll_manager', id,
    CASE WHEN slug IN ('dashboard.view','payroll.view','payroll.payslips','payroll.run','payroll.finalize','payroll.deductions','payroll.savings','payroll.reports','payroll.export','reports.payroll','reports.timesheets','employees.view','attendance.view','timesheets.view','leave.view','audit.view') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('payroll.payslips','payroll.run','payroll.deductions','payroll.savings') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('payroll.payslips','payroll.run','payroll.deductions','payroll.savings') THEN 1 ELSE 0 END,
    0,
    CASE WHEN slug IN ('payroll.finalize','payroll.run') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('payroll.export','reports.payroll','payroll.reports') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('payroll.finalize') THEN 1 ELSE 0 END,
    0
FROM `permissions`;

-- ── PAYROLL OFFICER ─────────────────────────────────────────
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'payroll_officer', id,
    CASE WHEN slug IN ('dashboard.view','payroll.view','payroll.payslips','payroll.run','payroll.deductions','payroll.savings','payroll.reports','reports.payroll','employees.view','attendance.view','timesheets.view') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('payroll.payslips','payroll.deductions','payroll.savings') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('payroll.payslips','payroll.deductions','payroll.savings') THEN 1 ELSE 0 END,
    0,
    0,
    CASE WHEN slug IN ('payroll.export','payroll.reports') THEN 1 ELSE 0 END,
    0,
    0
FROM `permissions`;

-- ── FINANCE VIEWER ──────────────────────────────────────────
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'finance_viewer', id,
    CASE WHEN slug IN ('dashboard.view','payroll.view','payroll.reports','reports.payroll','reports.view') THEN 1 ELSE 0 END,
    0, 0, 0, 0,
    CASE WHEN slug IN ('payroll.reports','reports.payroll') THEN 1 ELSE 0 END,
    0, 0
FROM `permissions`;

-- ── RECRUITMENT OFFICER ─────────────────────────────────────
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'recruitment_officer', id,
    CASE WHEN slug IN ('dashboard.view','recruitment.view','recruitment.manage','recruitment.review','employees.view','onboarding.view','onboarding.manage') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('recruitment.manage','onboarding.manage') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('recruitment.manage','recruitment.review','onboarding.manage') THEN 1 ELSE 0 END,
    0,
    CASE WHEN slug IN ('recruitment.review') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('recruitment.export') THEN 1 ELSE 0 END,
    0, 0
FROM `permissions`;

-- ── TRAINING OFFICER ────────────────────────────────────────
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'training_officer', id,
    CASE WHEN slug IN ('dashboard.view','training.view','training.manage','training.enrol','employees.view','performance.view') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('training.manage','training.enrol') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('training.manage','training.enrol') THEN 1 ELSE 0 END,
    0, 0,
    CASE WHEN slug IN ('training.export') THEN 1 ELSE 0 END,
    0, 0
FROM `permissions`;

-- ── SUPERVISOR ──────────────────────────────────────────────
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'supervisor', id,
    CASE WHEN slug IN ('dashboard.view','employees.view','attendance.view','timesheets.view','leave.view','performance.view','training.view','hub.view','portal.access','portal.attendance','portal.leave','portal.payslips','portal.requests') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('leave.apply') THEN 1 ELSE 0 END,
    0, 0,
    CASE WHEN slug IN ('leave.approve','timesheets.approve','timesheets.approve_ot') THEN 1 ELSE 0 END,
    0, 0, 0
FROM `permissions`;

-- ── EMPLOYEE ────────────────────────────────────────────────
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'employee', id,
    CASE WHEN slug IN ('portal.access','portal.attendance','portal.leave','portal.payslips','portal.requests','leave.apply') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('leave.apply','portal.requests') THEN 1 ELSE 0 END,
    0, 0, 0, 0, 0, 0
FROM `permissions`;

-- ── KIOSK TERMINAL ──────────────────────────────────────────
INSERT INTO `role_permissions` (`role`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_publish`, `can_share`)
SELECT 'kiosk_terminal', id,
    CASE WHEN slug IN ('attendance.view','kiosk.manage') THEN 1 ELSE 0 END,
    CASE WHEN slug IN ('attendance.view') THEN 1 ELSE 0 END,
    0, 0, 0, 0, 0, 0
FROM `permissions`;

-- ============================================================
-- Verification
-- ============================================================
SELECT 'Permissions inserted:' as status, COUNT(*) as count FROM permissions;
SELECT 'Role-permission rows:' as status, COUNT(*) as count FROM role_permissions;
SELECT role, COUNT(*) as perms FROM role_permissions GROUP BY role ORDER BY role;
