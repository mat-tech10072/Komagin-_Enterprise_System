-- ============================================================
-- Phase 8: Temporary Employees Module
-- Tables: temp_projects, temp_sites, temp_employees
-- Permissions + role grants + seed data
-- ============================================================

-- ── 1. Projects ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS temp_projects (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    code        VARCHAR(30)  NOT NULL UNIQUE,
    client      VARCHAR(200) DEFAULT NULL,
    location    VARCHAR(200) DEFAULT NULL,
    description TEXT         DEFAULT NULL,
    status      ENUM('active','on_hold','completed') NOT NULL DEFAULT 'active',
    start_date  DATE         DEFAULT NULL,
    end_date    DATE         DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Sites ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS temp_sites (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id  INT UNSIGNED NOT NULL,
    name        VARCHAR(200) NOT NULL,
    location    VARCHAR(200) DEFAULT NULL,
    description TEXT         DEFAULT NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES temp_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Temporary Employees ──────────────────────────────────
CREATE TABLE IF NOT EXISTS temp_employees (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_number   VARCHAR(30)  NOT NULL UNIQUE,
    first_name        VARCHAR(100) NOT NULL,
    last_name         VARCHAR(100) NOT NULL,
    phone             VARCHAR(30)  DEFAULT NULL,
    email             VARCHAR(200) DEFAULT NULL,
    position_title    VARCHAR(200) DEFAULT NULL,
    project_id        INT UNSIGNED DEFAULT NULL,
    site_id           INT UNSIGNED DEFAULT NULL,
    start_date        DATE         DEFAULT NULL,
    end_date          DATE         DEFAULT NULL,
    status            ENUM('active','completed','terminated') NOT NULL DEFAULT 'active',
    daily_rate        DECIMAL(10,2) DEFAULT NULL,
    notes             TEXT         DEFAULT NULL,
    portal_active     TINYINT(1)   NOT NULL DEFAULT 0,
    portal_password   VARCHAR(255) DEFAULT NULL,
    portal_last_login DATETIME     DEFAULT NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES temp_projects(id) ON DELETE SET NULL,
    FOREIGN KEY (site_id)    REFERENCES temp_sites(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Permissions ──────────────────────────────────────────
INSERT IGNORE INTO permissions (slug, name, module, description) VALUES
('temp_employees.view',   'View Temporary Employees',   'temp_employees', 'View the temporary employees list and profiles'),
('temp_employees.create', 'Create Temporary Employees', 'temp_employees', 'Add new temporary employees'),
('temp_employees.edit',   'Edit Temporary Employees',   'temp_employees', 'Edit temporary employee details'),
('temp_employees.delete', 'Delete Temporary Employees', 'temp_employees', 'Delete temporary employee records');

-- ── 5. Role permission grants ───────────────────────────────
-- super_admin: full access (all 4 permissions, all 8 actions)
INSERT IGNORE INTO role_permissions (role, permission_id, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_publish, can_share)
SELECT 'super_admin', id, 1,1,1,1,1,1,1,1 FROM permissions WHERE slug IN (
    'temp_employees.view','temp_employees.create','temp_employees.edit','temp_employees.delete'
);

-- hr_manager: view + create + edit + export (no delete)
INSERT IGNORE INTO role_permissions (role, permission_id, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_publish, can_share)
SELECT 'hr_manager', id, 1,1,1,0,1,1,0,0 FROM permissions WHERE slug IN (
    'temp_employees.view','temp_employees.create','temp_employees.edit','temp_employees.delete'
);

-- hrofficer: view + export only
INSERT IGNORE INTO role_permissions (role, permission_id, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_publish, can_share)
SELECT 'hrofficer', id, 1,0,0,0,0,1,0,0 FROM permissions WHERE slug IN (
    'temp_employees.view','temp_employees.create','temp_employees.edit','temp_employees.delete'
);

-- ── 6. Seed: Sample Projects ────────────────────────────────
INSERT IGNORE INTO temp_projects (id, name, code, client, location, description, status, start_date, end_date) VALUES
(1, 'Port Moresby Road Upgrade', 'PRJ-001', 'NCD Works Authority',  'Port Moresby, NCD',      'Rehabilitation of Waigani Drive and adjacent arterial roads', 'active',    '2026-01-10', '2026-12-31'),
(2, 'Lae Industrial Zone Build', 'PRJ-002', 'Morobe Development',   'Lae, Morobe Province',   'Construction of new light industrial precinct in Lae',        'active',    '2026-03-01', '2027-06-30'),
(3, 'Highlands Highway Survey',  'PRJ-003', 'Dept of Works & Highways', 'Mt Hagen, WHP',      'Feasibility and geotechnical survey of Highlands Highway',    'on_hold',   '2026-05-15', '2026-11-30');

-- ── 7. Seed: Sample Sites ───────────────────────────────────
INSERT IGNORE INTO temp_sites (id, project_id, name, location, description, status) VALUES
(1, 1, 'Section A, Waigani Drive',        'Waigani, NCD',      'Primary roadwork zone from Parliament to Gordon', 'active'),
(2, 1, 'Section B, Goro Road Interchange', 'Goro, NCD',        'Intersection upgrade and drainage works',          'active'),
(3, 2, 'Block 4 Industrial Site',          'Lae Tidal Basin',  'Main construction compound for industrial units',  'active'),
(4, 2, 'Site Office, Lae',                 'Lae City Centre',  'Administrative and staging site',                  'active'),
(5, 3, 'Survey Camp, Kainantu',            'Kainantu, EHP',    'Base camp for eastern survey corridor',            'inactive');

-- ── 8. Seed: Sample Temporary Employees ─────────────────────
INSERT IGNORE INTO temp_employees
    (employee_number, first_name, last_name, phone, email, position_title, project_id, site_id, start_date, end_date, status, daily_rate, portal_active, notes)
VALUES
('KOM-TMP-2026-0001', 'Benjamin',  'Moka',     '+675 7100 1001', 'b.moka@contractor.pg',    'Site Labourer',         1, 1, '2026-01-15', '2026-06-30', 'active',    180.00, 0, NULL),
('KOM-TMP-2026-0002', 'Grace',     'Tagari',   '+675 7100 1002', 'g.tagari@contractor.pg',  'Site Supervisor',       1, 2, '2026-01-20', '2026-12-31', 'active',    350.00, 0, NULL),
('KOM-TMP-2026-0003', 'Peter',     'Avi',      '+675 7100 1003', 'p.avi@contractor.pg',     'Equipment Operator',    1, 1, '2026-02-01', '2026-08-31', 'active',    280.00, 0, NULL),
('KOM-TMP-2026-0004', 'Susan',     'Teine',    '+675 7100 1004', 's.teine@contractor.pg',   'Safety Officer',        2, 3, '2026-03-05', '2026-12-31', 'active',    320.00, 0, NULL),
('KOM-TMP-2026-0005', 'Robert',    'Laka',     '+675 7100 1005', 'r.laka@contractor.pg',    'Concreter',             2, 3, '2026-03-10', '2026-09-30', 'active',    210.00, 0, NULL),
('KOM-TMP-2026-0006', 'Mary',      'Kapi',     '+675 7100 1006', 'm.kapi@contractor.pg',    'Site Admin',            2, 4, '2026-03-01', '2026-12-31', 'active',    260.00, 0, NULL),
('KOM-TMP-2026-0007', 'John',      'Wamugl',   '+675 7100 1007', 'j.wamugl@contractor.pg',  'Survey Technician',     3, 5, '2026-05-20', '2026-09-30', 'completed', 300.00, 0, 'Survey phase completed early'),
('KOM-TMP-2026-0008', 'Rachael',   'Poro',     '+675 7100 1008', 'r.poro@contractor.pg',    'Data Entry Clerk',      2, 4, '2026-04-01', '2026-10-31', 'active',    190.00, 0, NULL);
