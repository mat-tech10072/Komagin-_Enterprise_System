-- ============================================================
-- Phase 10: Authorization Framework (Enterprise Remediation Phase 1)
--
-- Data-only migration. No CREATE TABLE / ALTER TABLE. Adds the
-- permission slugs the Phase 1 authorization-centralization work
-- needs, and corrects a role-name seeding typo that has been
-- silently denying hr_officer users access to two modules since
-- those modules were introduced.
--
-- Findings addressed: KOM-019 (NH-02, Activity Log bypassed the
-- permission system), KOM-023 (hrofficer/hr_officer typo), plus the
-- 'approvals.manage_all' slug introduced by the ApprovalEngine
-- hardening work (replaces a hardcoded role check in
-- modules/approvals/index.php).
-- ============================================================

-- ── 1. New permission: Activity Log ─────────────────────────────
-- Seeded super_admin-only, matching EXACTLY the access the module
-- already had under its hardcoded check — this migration changes the
-- MECHANISM (DB-driven instead of hardcoded), not who currently has
-- access. Broadening access to other roles is a separate policy
-- decision for a later phase, not bundled into this authorization
-- refactor.
INSERT IGNORE INTO permissions (slug, name, module, description) VALUES
('activity_log.view', 'View Activity Log', 'activity_log', 'View the detailed per-user/entity activity log and export it');

INSERT IGNORE INTO role_permissions (role, permission_id, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_publish, can_share)
SELECT 'super_admin', id, 1,0,0,0,0,1,0,0 FROM permissions WHERE slug = 'activity_log.view';

-- ── 2. New permission: Approvals org-wide view ──────────────────
-- Replaces the hardcoded in_array($role, ['super_admin','hr_manager'])
-- check in modules/approvals/index.php that gated the "All Workflows"
-- admin view. Seeded to the same two roles the hardcoded check
-- already allowed — again, mechanism change only, not a policy change.
INSERT IGNORE INTO permissions (slug, name, module, description) VALUES
('approvals.manage_all', 'View All Approval Workflows', 'approvals', 'See every approval workflow in the system, not just those assigned to you');

INSERT IGNORE INTO role_permissions (role, permission_id, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_publish, can_share)
SELECT 'super_admin', id, 1,0,0,0,0,0,0,0 FROM permissions WHERE slug = 'approvals.manage_all';

INSERT IGNORE INTO role_permissions (role, permission_id, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_publish, can_share)
SELECT 'hr_manager', id, 1,0,0,0,0,0,0,0 FROM permissions WHERE slug = 'approvals.manage_all';

-- ── 3. Fix role-name typo: 'hrofficer' → 'hr_officer' ───────────
-- database/phase8_temp_employees.sql and database/phase9_consultants.sql
-- both seeded their role grants against the string 'hrofficer' (no
-- underscore). The real role, used everywhere else in the system
-- (users.role ENUM, every other permission seed, every session check),
-- is 'hr_officer'. Because _loadRolePermissions() queries
-- role_permissions WHERE role = ? using the literal session role
-- string, every real hr_officer user has had ZERO permissions for
-- temp_employees.* and consultants.* since those modules launched —
-- not a security exposure, but a silent, unintended access denial to
-- a legitimate role. Verified before this migration: hr_officer has
-- no existing rows for these permission_ids, so this is a pure rename
-- with no risk of violating role_permission_unique(role, permission_id).
UPDATE role_permissions
SET role = 'hr_officer'
WHERE role = 'hrofficer';
