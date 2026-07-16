-- ============================================================
-- Seed: Departments & Positions (Phase 4 discovery)
-- ============================================================
-- Neither `departments` nor `positions` had any seed data in any
-- tracked file, despite both having a CREATE TABLE in schema.sql.
-- Departments at least have a management UI (Settings > Departments)
-- so a fresh install could eventually be repopulated by hand; positions
-- had NO management UI anywhere in the application until Phase 4 added
-- one (Settings > Positions) -- before that fix, a fresh install had
-- zero positions and no way to create one at all, permanently leaving
-- the "Position" dropdown empty on Add/Edit Employee.
--
-- Data below is the exact live set, verified by direct query, safe to
-- run against an already-populated database (INSERT IGNORE on the
-- live primary keys, so re-running this is a no-op once applied).
-- See docs/remediation/Workflows/02-department-position-workflow-report.md.
-- ============================================================

INSERT IGNORE INTO departments (id, name, code) VALUES
(1,  'Human Resources',       'HR'),
(2,  'Finance',                'FIN'),
(3,  'Operations',              'OPS'),
(4,  'Engineering',             'ENG'),
(5,  'Workshop',                'WKSP'),
(6,  'Administration',          'ADMIN'),
(7,  'Project Management',      'PM'),
(8,  'Business Development',    'BD'),
(9,  'Survey',                  'SURV'),
(10, 'Information Technology',  'IT'),
(11, 'Executive',               'EXEC');

INSERT IGNORE INTO positions (id, department_id, title, job_grade) VALUES
(1,  1,  'HR Manager',                    'M1'),
(2,  1,  'HR Officer',                    'S2'),
(3,  2,  'Finance Manager',               'M1'),
(4,  2,  'Finance Officer',               'S2'),
(5,  3,  'Operations Manager',            'M1'),
(6,  3,  'Operations Supervisor',         'S1'),
(7,  4,  'Senior Engineer',               'P2'),
(8,  4,  'Engineer',                      'P1'),
(9,  5,  'Workshop Supervisor',           'S1'),
(10, 5,  'Technician',                    'T1'),
(11, 6,  'Administrative Officer',        'S2'),
(12, 7,  'Project Manager',               'M1'),
(13, 8,  'Business Development Manager',  'M1'),
(14, 4,  'Civil Engineer',                'P1'),
(15, 4,  'Lead Civil Engineer',           'P2'),
(16, 4,  'Draftsman',                     'T2'),
(17, 9,  'Surveyor',                      'P1'),
(18, 9,  'Lead Surveyor',                 'P2'),
(19, 10, 'IT Officer',                    'P1'),
(20, 3,  'Asset Manager',                 'M1'),
(21, 2,  'Accounts Officer',              'S2'),
(22, 6,  'Admin Officer',                 'S1'),
(23, 11, 'General Manager',               'E1');
