<?php
// Consultant Portal Session Guard
// Include at the top of every portal page (after _config.php)

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 28800,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// Regenerate session ID every 30 minutes
if (!isset($_SESSION['cp_last_regen'])) {
    $_SESSION['cp_last_regen'] = time();
} elseif (time() - $_SESSION['cp_last_regen'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['cp_last_regen'] = time();
}

// Session timeout (8 hours)
if (isset($_SESSION['cp_last_activity']) && (time() - $_SESSION['cp_last_activity']) > 28800) {
    foreach (['cp_consultant_id','cp_type','cp_name','cp_number','cp_last_activity','cp_last_regen','cp_login_time'] as $k) {
        unset($_SESSION[$k]);
    }
    header('Location: ' . CP_URL . '/login.php?reason=timeout');
    exit;
}
$_SESSION['cp_last_activity'] = time();

function cpIsLoggedIn(): bool {
    return !empty($_SESSION['cp_consultant_id']);
}

function cpRequireLogin(): void {
    if (empty($_SESSION['cp_consultant_id'])) {
        header('Location: ' . CP_URL . '/login.php');
        exit;
    }
}

function cpRequireType(string $type): void {
    cpRequireLogin();
    if (($_SESSION['cp_type'] ?? '') !== $type) {
        header('Location: ' . CP_URL . '/dashboard.php');
        exit;
    }
}

function cpCurrentConsultant(): array {
    if (empty($_SESSION['cp_consultant_id'])) return [];
    static $con = null;
    if ($con === null) {
        $stmt = db()->prepare("SELECT * FROM consultants WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['cp_consultant_id']]);
        $con = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    return $con;
}
