-- ============================================================
-- Phase 13: Workflow Completeness, Scheduled Automation, Recovery
-- Flows & Remaining Findings Closure (Phase 5 program)
-- Idempotent, additive-only. Safe to run against any existing
-- installation, including one already brought current by
-- phase11_schema_reconciliation.sql / phase12_workflow_integrity_fixes.sql.
-- ============================================================

-- ── Stage 5.3: Working-day & holiday calendar ──────────────────────────
-- No working-day/holiday calendar existed anywhere in this codebase
-- before Phase 5 — every "absence" figure across Dashboard/Reports/
-- Attendance could only ever be computed from raw attendance rows
-- (which only exist when someone actually clocks in), never from a
-- real notion of which days employees were expected to be present.
-- See Workflows/00-workflow-transition-matrix.md's cross-cutting
-- observation and KOM-098 (Phase 4, partially fixed pending exactly
-- this infrastructure).

-- Single-row settings table (same pattern as company_settings) holding
-- which ISO weekdays count as scheduled working days.
CREATE TABLE IF NOT EXISTS work_calendar_settings (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  working_weekdays varchar(20) NOT NULL DEFAULT '1,2,3,4,5' COMMENT 'ISO-8601 weekday numbers, 1=Monday..7=Sunday, comma-separated',
  timezone varchar(50) NOT NULL DEFAULT 'Pacific/Port_Moresby',
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  updated_by int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (id),
  KEY updated_by (updated_by),
  CONSTRAINT work_calendar_settings_ibfk_1 FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO work_calendar_settings (id, working_weekdays, timezone)
SELECT 1, '1,2,3,4,5', 'Pacific/Port_Moresby'
WHERE NOT EXISTS (SELECT 1 FROM work_calendar_settings WHERE id = 1);

-- Public holidays / organization closure days. A "holiday" may span a
-- date range (e.g. a multi-day office shutdown) — start_date=end_date
-- for a single day. is_recurring_annual lets a fixed-date holiday
-- (e.g. Christmas Day, Independence Day) repeat every year without a
-- new row each January; the stored year is then only a placeholder for
-- month/day matching, not itself meaningful.
CREATE TABLE IF NOT EXISTS work_calendar_holidays (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(150) NOT NULL,
  start_date date NOT NULL,
  end_date date NOT NULL COMMENT 'same as start_date for a single-day holiday; later for a closure range',
  is_recurring_annual tinyint(1) NOT NULL DEFAULT 0 COMMENT 'if set, month/day repeats every year regardless of the stored year',
  is_active tinyint(1) NOT NULL DEFAULT 1,
  notes text DEFAULT NULL,
  created_by int(10) unsigned DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_work_calendar_holidays_dates (start_date, end_date),
  KEY created_by (created_by),
  CONSTRAINT work_calendar_holidays_ibfk_1 FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Stage 5.4: Scheduled task infrastructure ───────────────────────────
-- No cron/scheduled-task mechanism existed anywhere in this codebase —
-- confirmed by a full repository search in Phase 4. cron/run.php (new)
-- is meant to be triggered by a host-level cron job (e.g. cPanel),
-- never a web request. These two tables give it a single-run lock (so
-- an overlapping cron invocation exits immediately instead of racing
-- the one already in progress) and a per-task-per-run audit trail.

CREATE TABLE IF NOT EXISTS scheduled_task_locks (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  lock_name varchar(100) NOT NULL,
  locked_at timestamp NOT NULL DEFAULT current_timestamp(),
  locked_by varchar(150) DEFAULT NULL COMMENT 'hostname:pid, for diagnostics only',
  PRIMARY KEY (id),
  UNIQUE KEY uk_scheduled_task_locks_name (lock_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scheduled_task_runs (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  task_name varchar(100) NOT NULL,
  status enum('running','success','failed') NOT NULL DEFAULT 'running',
  items_processed int(10) unsigned NOT NULL DEFAULT 0,
  error_summary text DEFAULT NULL,
  started_at timestamp NOT NULL DEFAULT current_timestamp(),
  finished_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_scheduled_task_runs_name (task_name),
  KEY idx_scheduled_task_runs_started (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Stage 5.5: Self-service password recovery (Admin surface only) ────
-- KOM-041: no self-service password reset flow existed on any of the 4
-- authentication surfaces. User decision: build it for the Admin
-- surface only (guaranteed to have a real, verified email on file);
-- Employee/Consultant/Temp Portal keep the current admin-assisted-only
-- model.

ALTER TABLE users ADD COLUMN IF NOT EXISTS password_changed_at datetime DEFAULT NULL
  COMMENT 'Phase 5, Stage 5.5: compared against a session''s login_time to force re-login on other sessions after a password change/reset' AFTER password_hash;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL,
  token_hash char(64) NOT NULL COMMENT 'sha256 of the raw token emailed to the user; the raw token itself is never stored',
  expires_at datetime NOT NULL,
  used_at datetime DEFAULT NULL,
  requested_ip varchar(45) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_password_reset_tokens_hash (token_hash),
  KEY idx_password_reset_tokens_user (user_id),
  CONSTRAINT password_reset_tokens_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sendEmail()'s existing email_logs.type enum had no value for this new
-- flow; passing 'password_reset' to it was silently coerced to '' by
-- MariaDB's non-strict SQL mode rather than raising an error. Extending
-- the enum, consistent with how every other email flow (payslip,
-- leave_approval, etc.) has its own type value.
ALTER TABLE email_logs MODIFY COLUMN type enum('payslip','leave_approval','leave_rejection','document','general','test','password_reset') DEFAULT 'general';
