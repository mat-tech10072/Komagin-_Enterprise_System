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

// Phase 5, Stage 5.5: if this account's password was changed (e.g. via a
// self-service reset) after this specific session was established,
// force re-login. This is the effective equivalent of "invalidate all
// other active sessions on password reset" — this codebase's default
// file-based PHP sessions have no central per-user session registry to
// selectively destroy other sessions by, so comparing against a
// database timestamp is the achievable substitute.
if (isset($_SESSION['user_id'], $_SESSION['login_time'])) {
    require_once dirname(__DIR__) . '/config/database.php';
    $pwCheckStmt = db()->prepare("SELECT password_changed_at FROM users WHERE id=?");
    $pwCheckStmt->execute([$_SESSION['user_id']]);
    $pwChangedAt = $pwCheckStmt->fetchColumn();
    if ($pwChangedAt && strtotime($pwChangedAt) > $_SESSION['login_time']) {
        destroySessionCompletely();
        header('Location: ' . APP_URL . '/auth/login.php?reason=password_changed');
        exit;
    }
}
