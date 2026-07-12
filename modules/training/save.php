<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('training.manage', 'create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/training/index.php'); exit;
}

$title = trim($_POST['title'] ?? '');
if (!$title) { setFlash('error','Title required.'); header('Location: ' . APP_URL . '/modules/training/index.php'); exit; }

// KOM-008 (continued): this was the third fatal crash found in the
// Training module — training_programs has no training_type column, and
// the real names for the form's "Trainer/Provider" and "Venue" fields are
// provider and location, not trainer_name/venue. The form's training_type
// select has no corresponding column to persist to; left unmapped rather
// than guessed into an unrelated column (status is a workflow state, not
// a training-type category).
db()->prepare("INSERT INTO training_programs (title, provider, location, start_date, end_date, description, created_by)
    VALUES (?,?,?,?,?,?,?)")
    ->execute([
        $title,
        trim($_POST['trainer_name'] ?? '') ?: null,
        trim($_POST['venue'] ?? '') ?: null,
        $_POST['start_date'] ?: null,
        $_POST['end_date'] ?: null,
        trim($_POST['description'] ?? '') ?: null,
        $_SESSION['user_id']
    ]);

auditLog('training','create_program',null,null,json_encode(['title'=>$title]));
setFlash('success',"Training program '{$title}' added.");
header('Location: ' . APP_URL . '/modules/training/index.php');
exit;
