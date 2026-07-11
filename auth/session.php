<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/session_common.php';

// Admin surface uses the unprefixed key namespace ('') — this preserves the
// existing $_SESSION['user_id'] etc. key names used throughout the admin
// codebase; only the internal bookkeeping keys (last_regen/last_activity)
// are now shared logic, not shared names with the portals.
if (!bootstrapSession('', SESSION_LIFETIME)) {
    header('Location: ' . APP_URL . '/auth/login.php?reason=timeout');
    exit;
}
