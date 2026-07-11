<?php
// Employee Portal Session Guard
// Must be included at the top of every portal page

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 28800,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// Regenerate ID every 30 min
if (!isset($_SESSION['ep_last_regen'])) {
    $_SESSION['ep_last_regen'] = time();
} elseif (time() - $_SESSION['ep_last_regen'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['ep_last_regen'] = time();
}

// Session timeout (8 hours)
if (isset($_SESSION['ep_last_activity']) && (time() - $_SESSION['ep_last_activity']) > 28800) {
    session_destroy();
    header('Location: ' . EP_URL . '/login.php?reason=timeout');
    exit;
}
$_SESSION['ep_last_activity'] = time();

function epIsLoggedIn(): bool {
    return !empty($_SESSION['ep_employee_id']) && !empty($_SESSION['ep_policy_agreed']);
}

function epRequireLogin(): void {
    if (empty($_SESSION['ep_employee_id'])) {
        header('Location: ' . EP_URL . '/login.php');
        exit;
    }
    if (empty($_SESSION['ep_policy_agreed'])) {
        header('Location: ' . EP_URL . '/policy.php');
        exit;
    }
}

function epCurrentEmployee(): array {
    if (empty($_SESSION['ep_employee_id'])) return [];
    static $emp = null;
    if ($emp === null) {
        $stmt = db()->prepare("SELECT e.*, d.name as dept_name, p.title as position_title
            FROM employees e
            LEFT JOIN departments d ON e.department_id=d.id
            LEFT JOIN positions p ON e.position_id=p.id
            WHERE e.id=?");
        $stmt->execute([$_SESSION['ep_employee_id']]);
        $emp = $stmt->fetch() ?: [];
    }
    return $emp;
}
