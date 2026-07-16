<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    if (isset($_COOKIE['komagin_hr_session']) && !isset($_COOKIE[session_name()])) {
        session_name('komagin_hr_session');
    }
    session_start();
}

header('Content-Type: application/json');

$role = $_SESSION['user_role'] ?? ($_SESSION['admin_role'] ?? ($_SESSION['hr_role'] ?? ''));
if (!in_array($role, ['admin', 'hr_admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$type = $_POST['upload_type'] ?? '';
$allowed = ['project','service','testimonial','team','staff','asset','social','site_report','milestone','managed_file','receipt','cv'];
if (!in_array($type, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid upload type']);
    exit;
}

if ($role === 'hr_admin' && !in_array($type, ['staff','managed_file','cv'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Upload type not allowed for HR admin']);
    exit;
}

$base = __DIR__ . '/uploads/';
$paths = [
    'project' => $base . 'projects/',
    'service' => $base . 'services/',
    'testimonial' => $base . 'testimonials/',
    'team' => $base . 'team/',
    'staff' => $base . 'staff/',
    'asset' => $base . 'assets/',
    'social' => $base . 'social/',
    'site_report' => $base . 'site_reports/',
    'milestone' => $base . 'milestones/',
    'managed_file' => $base . 'managed/',
    'receipt' => $base . 'receipts/',
    'cv' => $base . 'cvs/',
];

$upload_dir = $paths[$type];
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload failed or no file received']);
    exit;
}

$max_size = 10 * 1024 * 1024;
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'error' => 'File too large (max 10MB)']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = $type . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($ext ? '.' . $ext : '');
$dest = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'error' => 'Could not save file']);
    exit;
}

log_activity($pdo, 'upload_' . $type, $filename);
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'path' => 'uploads/' . basename($upload_dir) . '/' . $filename,
    'url' => 'uploads/' . basename($upload_dir) . '/' . $filename,
    'size' => $file['size'],
    'mime' => mime_content_type($dest),
]);
?>
