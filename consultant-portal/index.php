<?php
require_once __DIR__ . '/_config.php';
require_once dirname(__DIR__) . '/auth/session_common.php';

// This is a pure router page — use the same hardened cookie configuration
// as the rest of the portal rather than a bare session_start() with PHP's
// unhardened defaults, so a visitor's very first cookie is never weaker
// than the one every subsequent page on this portal would set.
bootstrapSession('cp_', 28800);

if (!empty($_SESSION['cp_consultant_id'])) {
    header('Location: ' . CP_URL . '/dashboard.php');
} else {
    header('Location: ' . CP_URL . '/login.php');
}
exit;
