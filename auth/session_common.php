<?php
/**
 * Komagin HR — Enterprise Session Framework
 *
 * ONE session lifecycle, shared by every authentication surface: Admin,
 * Employee Portal, Consultant Portal, and Temporary Employee Portal. Each
 * surface calls bootstrapSession() with its own session-key prefix (admin
 * uses '', employee/temp portal uses 'ep_', consultant portal uses 'cp_')
 * and its own timeout — the cookie configuration, ID-rotation timing, and
 * idle-timeout logic are implemented exactly once, here, instead of being
 * copy-pasted per surface with small, accumulating differences.
 *
 * This file is intentionally dependency-free (no config/functions.php
 * requirement) so it can be included as the very first thing on any page,
 * before anything else that might touch the session.
 */

const SESSION_ROTATE_INTERVAL = 1800; // 30 minutes — same for every surface

/**
 * Start (or resume) a session with standardized cookie flags, rotate the
 * session ID on a fixed schedule, and enforce idle timeout.
 *
 * @param string $prefix   Session-key namespace for this surface ('' for
 *                          admin, 'ep_' for employee/temp portal, 'cp_' for
 *                          consultant portal). Determines the names of the
 *                          two bookkeeping keys this function owns:
 *                          "{$prefix}last_regen" and "{$prefix}last_activity".
 * @param int    $lifetime  Idle-timeout / cookie lifetime in seconds.
 * @return bool  true if the session is valid and the caller may proceed;
 *               false if the session had timed out and was just destroyed —
 *               the caller must redirect to its own login page.
 */
function bootstrapSession(string $prefix, int $lifetime): bool {
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (defined('APP_ENV') && APP_ENV === 'production');

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => $secure,
        ]);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }

    $regenKey    = $prefix . 'last_regen';
    $activityKey = $prefix . 'last_activity';

    // Idle timeout — checked BEFORE rotation, so a stale session is fully
    // destroyed rather than rotated-then-destroyed.
    if (isset($_SESSION[$activityKey]) && (time() - $_SESSION[$activityKey]) > $lifetime) {
        destroySessionCompletely();
        return false;
    }

    // Periodic ID rotation — every surface gets the same 30-minute schedule.
    // The very first sight of a session (no regen timestamp yet) always
    // rotates immediately; this is the fixation defense for any session that
    // reaches here without having gone through regenerateSessionOnLogin()
    // first (e.g. an anonymous pre-login session that starts accumulating
    // activity for some other reason).
    if (!isset($_SESSION[$regenKey])) {
        session_regenerate_id(true);
        $_SESSION[$regenKey] = time();
    } elseif (time() - $_SESSION[$regenKey] > SESSION_ROTATE_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION[$regenKey] = time();
    }

    $_SESSION[$activityKey] = time();
    return true;
}

/**
 * Call this immediately after verifying credentials, BEFORE writing any
 * other session data. Regenerates the session ID (destroying the
 * pre-authentication session) and stamps the rotation timestamp so
 * bootstrapSession()'s next call doesn't redundantly rotate again on the
 * very next page load.
 */
function regenerateSessionOnLogin(string $prefix): void {
    session_regenerate_id(true);
    $_SESSION[$prefix . 'last_regen'] = time();
}

/**
 * Full session teardown for logout AND idle-timeout: clears all session
 * data, destroys the server-side session store, AND explicitly expires the
 * session cookie client-side (session_destroy() alone does not do this —
 * the browser will keep sending an already-invalid session ID cookie until
 * it separately expires or is overwritten).
 */
function destroySessionCompletely(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_unset();
    session_destroy();
}
