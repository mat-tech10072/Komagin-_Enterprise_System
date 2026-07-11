<?php
require_once dirname(__DIR__) . '/auth/session.php';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// mark_read/mark_all_read change data and must be POST + CSRF-verified —
// the same standard every other state-changing endpoint in the app
// follows. Previously these ran on plain GET with no token at all, making
// them forgeable via a cross-site <img>/<script> request. list/count are
// pure reads and stay GET-accessible.
if (in_array($action, ['mark_read', 'mark_all_read'], true)) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid request.']);
        exit;
    }
}

switch ($action) {
    case 'mark_all_read':
        db()->prepare("UPDATE notifications SET is_read=1, read_at=NOW() WHERE user_id=?")
            ->execute([$_SESSION['user_id']]);
        clearNotifCache($_SESSION['user_id']);
        echo json_encode(['success' => true]);
        break;

    case 'mark_read':
        $nid = (int)($_POST['id'] ?? 0);
        if ($nid) {
            db()->prepare("UPDATE notifications SET is_read=1, read_at=NOW() WHERE id=? AND user_id=?")
                ->execute([$nid, $_SESSION['user_id']]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'count':
        $count = getUnreadNotificationCount($_SESSION['user_id']);
        echo json_encode(['count' => $count]);
        break;

    case 'list':
        $stmt = db()->prepare("
            SELECT *,
                   DATE_FORMAT(created_at, '%d %b %Y %H:%i') AS time
            FROM notifications
            WHERE user_id = ? AND is_read = 0
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['notifications' => $rows]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
