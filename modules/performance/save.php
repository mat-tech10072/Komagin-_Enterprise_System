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

// KOM-049: this INSERT named three columns that don't exist anywhere in
// performance_reviews at all (overall_rating, comments, recommendations) —
// every single submission threw an uncaught PDOException, not just a silent
// rating-not-saved bug as originally reported. The real columns are
// overall_score (matches the form field already), and the closest existing
// single free-text columns for the form's generic "Comments"/
// "Recommendations" fields are supervisor_assessment and
// recommendation_notes respectively — the table's more granular
// self_assessment/strengths/improvements/recommendation(enum) columns have
// no corresponding field on this form and are correctly left NULL rather
// than guessed at.
db()->prepare("INSERT INTO performance_reviews (employee_id, reviewer_id, review_period, review_date, overall_score, supervisor_assessment, recommendation_notes, status)
    VALUES (?,?,?,?,?,?,?,'draft')")
    ->execute([
        $empId, $_SESSION['user_id'],
        trim($_POST['review_period'] ?? '') ?: null,
        $reviewDate,
        $_POST['overall_score'] ?: null,
        trim($_POST['comments'] ?? '') ?: null,
        trim($_POST['recommendations'] ?? '') ?: null,
    ]);

auditLog('performance','create_review',null,null,json_encode(['employee'=>$empId,'date'=>$reviewDate]));
setFlash('success','Performance review saved.');
header('Location: ' . APP_URL . '/modules/performance/index.php');
exit;
