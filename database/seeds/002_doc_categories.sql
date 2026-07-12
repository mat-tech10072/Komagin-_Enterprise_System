-- ============================================================
-- Seed 002: Document Template Categories
--
-- Phase 3 discovery: database/phase6_templates.sql looks up each
-- category by slug (`SET @cat_emp = (SELECT id FROM doc_categories
-- WHERE slug='employment_letters')`) and doc_templates.category_id is
-- NOT NULL — so on a fresh install, with no tracked file anywhere ever
-- inserting these 10 rows, phase6_templates.sql would fail outright
-- with a NOT NULL constraint violation on its very first INSERT. This
-- is the seed that was always missing. Data below matches the live
-- development database's doc_categories table exactly (10 rows,
-- confirmed via direct query before writing this file).
-- ============================================================

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
