<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('archive.generate', 'create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . APP_URL . '/modules/archive/monthly.php'); exit;
}

$archiveType  = $_POST['period_type'] ?? 'monthly';
$year         = (int)($_POST['period_year'] ?? date('Y'));
$month        = (int)($_POST['period_month'] ?? 0);
$quarter      = (int)($_POST['period_quarter'] ?? 0);
$documentType = $_POST['document_type'] ?? 'hr_summary';
$title        = trim($_POST['title'] ?? '');
$notes        = trim($_POST['description'] ?? '');

if (!$title) {
    $label = $archiveType === 'monthly' ? date('F Y', mktime(0,0,0,$month,1,$year)) : ($archiveType === 'quarterly' ? "Q{$quarter} {$year}" : "Annual {$year}");
    $title = $label . ' — ' . ucwords(str_replace('_',' ',$documentType));
}

db()->prepare("INSERT INTO archive_records (archive_type, year, month, quarter, document_type, title, notes, generated_by)
    VALUES (?,?,?,?,?,?,?,?)")
    ->execute([
        $archiveType,
        $year,
        $month ?: null,
        $quarter ?: null,
        $documentType,
        $title,
        $notes ?: null,
        $_SESSION['user_id']
    ]);

auditLog('archive','create',null,null,json_encode(['type'=>$archiveType,'year'=>$year,'doc_type'=>$documentType]),$notes);
setFlash('success','Archive record generated: '.$title);

$redirect = match($archiveType) {
    'quarterly' => APP_URL . '/modules/archive/quarterly.php?year='.$year.'&q='.$quarter,
    'yearly'    => APP_URL . '/modules/archive/yearly.php?year='.$year,
    default     => APP_URL . '/modules/archive/monthly.php?year='.$year.'&month='.str_pad($month,2,'0',STR_PAD_LEFT),
};
header('Location: '.$redirect);
exit;
