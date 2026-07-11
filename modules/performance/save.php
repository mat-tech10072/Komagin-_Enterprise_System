<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('performance.manage', 'create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/performance/index.php'); exit;
}

$empId      = (int)($_POST['employee_id'] ?? 0);
$reviewDate = $_POST['review_date'] ?? '';
if (!$empId || !$reviewDate) { setFlash('error','Employee and review date required.'); header('Location: ' . APP_URL . '/modules/performance/index.php'); exit; }

db()->prepare("INSERT INTO performance_reviews (employee_id, reviewer_id, review_period, review_date, overall_rating, comments, recommendations, status)
    VALUES (?,?,?,?,?,?,?,'draft')")
    ->execute([
        $empId, $_SESSION['user_id'],
        trim($_POST['review_period'] ?? '') ?: null,
        $reviewDate,
        $_POST['overall_rating'] ?: null,
        trim($_POST['comments'] ?? '') ?: null,
        trim($_POST['recommendations'] ?? '') ?: null,
    ]);

auditLog('performance','create_review',null,null,json_encode(['employee'=>$empId,'date'=>$reviewDate]));
setFlash('success','Performance review saved.');
header('Location: ' . APP_URL . '/modules/performance/index.php');
exit;
