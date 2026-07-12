-- ============================================================
-- Seed 001: Baseline Super Admin Account
--
-- Required for every fresh install — without at least one super_admin
-- user, nobody can log in to configure the system at all.
--
-- SECURITY: must_change_password is forced to 1. The seeded password
-- ('Admin@123') is publicly documented in this project's own README
-- and audit history, so it must never be treated as a real credential —
-- the very first login is required to change it (auth/login.php already
-- enforces this: must_change_password=1 redirects to
-- auth/change_password.php before anything else is reachable).
--
-- This was previously embedded directly in database/schema.sql (a
-- structure file), which is why it's been moved here as part of Phase 3 —
-- a schema definition should not also be the thing deciding what a fresh
-- install's default password is. See
-- docs/remediation/Database/06-phase3-canonical-database-model.md.
-- ============================================================

INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role`, `is_active`, `must_change_password`)
VALUES (
    'superadmin',
    'admin@komagin.com',
    '$2y$10$Ad.cRs9VYqt50aDnlCc5aO7o01ueEOkaS2a6SedC1vzODE1seN83S', -- 'Admin@123' — CHANGE ON FIRST LOGIN
    'super_admin',
    1,
    1
);
