<?php
require_once __DIR__ . '/_config.php';
require_once dirname(__DIR__) . '/auth/session_common.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Previously this only unset 7 named cp_* keys — the underlying PHP session
// was never destroyed, so the session ID/cookie remained valid on the
// client and any key outside that specific list would have survived
// "logout." destroySessionCompletely() fully tears down the session and
// explicitly expires the cookie, matching every other logout in the app.
destroySessionCompletely();

header('Location: ' . CP_URL . '/login.php?reason=logout');
exit;
