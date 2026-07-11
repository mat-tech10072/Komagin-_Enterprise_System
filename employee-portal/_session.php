<?php
// Employee Portal Session Guard — must be included at the top of every
// portal page (including temp_portal.php, which shares this same session
// store/cookie and the 'ep_' key prefix with the permanent-employee portal).
require_once dirname(__DIR__) . '/auth/session_common.php';

if (!bootstrapSession('ep_', 28800)) {
    header('Location: ' . EP_URL . '/login.php?reason=timeout');
    exit;
}

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

// Temp-employee counterpart to epIsLoggedIn()/epRequireLogin() — temp
// employees have no policy-agreement gate, and are identified by
// ep_is_temp/ep_temp_employee_id rather than ep_employee_id.
function epIsTempLoggedIn(): bool {
    return !empty($_SESSION['ep_is_temp']) && !empty($_SESSION['ep_temp_employee_id']);
}

function epRequireTempLogin(): void {
    if (!epIsTempLoggedIn()) {
        header('Location: ' . EP_URL . '/login.php');
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
