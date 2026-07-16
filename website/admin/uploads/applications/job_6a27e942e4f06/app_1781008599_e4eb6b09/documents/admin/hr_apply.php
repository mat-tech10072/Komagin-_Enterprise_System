<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

define('JOB_APPLICATION_UPLOAD_ROOT', __DIR__ . '/uploads/');

function job_application_normalize_status(string $status = ''): string {
    $value = strtolower(trim($status));
    if ($value === '' || $value === 'new' || $value === 'reviewed') return 'received';
    if ($value === 'interviewed') return 'interview';
    if ($value === 'offered') return 'hired';
    $allowed = ['received', 'shortlisted', 'interview', 'hired', 'rejected', 'withdrawn'];
    return in_array($value, $allowed, true) ? $value : 'received';
}

function ensure_job_application_schema(PDO $pdo): void {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS job_applications (
            id VARCHAR(50) NOT NULL,
            job_id VARCHAR(50) NOT NULL,
            applicant_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            cover_letter TEXT DEFAULT NULL,
            cv_file VARCHAR(500) DEFAULT NULL,
            document_bundle_name VARCHAR(255) DEFAULT NULL,
            document_manifest LONGTEXT DEFAULT NULL,
            document_extract_dir VARCHAR(500) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'received',
            notes TEXT DEFAULT NULL,
            reviewed_by VARCHAR(100) DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_app_job (job_id),
            KEY idx_app_status (status),
            KEY idx_app_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("ALTER TABLE job_applications MODIFY COLUMN status VARCHAR(50) DEFAULT 'received'");
        foreach ([
            "ALTER TABLE job_applications ADD COLUMN document_bundle_name VARCHAR(255) DEFAULT NULL AFTER cv_file",
            "ALTER TABLE job_applications ADD COLUMN document_manifest LONGTEXT DEFAULT NULL AFTER document_bundle_name",
            "ALTER TABLE job_applications ADD COLUMN document_extract_dir VARCHAR(500) DEFAULT NULL AFTER document_manifest",
            "ALTER TABLE job_applications ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER created_at"
        ] as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // Ignore duplicate column attempts.
            }
        }
        $pdo->exec("UPDATE job_applications SET status = CASE
            WHEN status IS NULL OR TRIM(status) = '' THEN 'received'
            WHEN status = 'new' THEN 'received'
            WHEN status = 'reviewed' THEN 'received'
            WHEN status = 'interviewed' THEN 'interview'
            WHEN status = 'offered' THEN 'hired'
            ELSE status
        END");
    } catch (Throwable $e) {
        // Keep public apply route resilient even if schema repair encounters a non-fatal issue.
    }
}

function delete_directory_tree(string $dir): void {
    if (!is_dir($dir)) return;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }
    @rmdir($dir);
}

function normalize_relative_upload_path(string $path): string {
    return ltrim(str_replace('\\', '/', trim($path)), '/');
}

function collect_extracted_documents(string $absoluteRoot, string $relativeRoot): array {
    if (!is_dir($absoluteRoot)) return [];
    $documents = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absoluteRoot, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $absolutePath = $file->getPathname();
        $relativePath = normalize_relative_upload_path(substr($absolutePath, strlen(JOB_APPLICATION_UPLOAD_ROOT)));
        $folder = normalize_relative_upload_path(substr(dirname($absolutePath), strlen($absoluteRoot)));
        if ($folder === '.') $folder = '';
        $documents[] = [
            'name' => basename($absolutePath),
            'relative_path' => $relativePath,
            'folder' => $folder,
            'extension' => strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION)),
            'size' => @filesize($absolutePath) ?: 0
        ];
    }
    usort($documents, static function ($a, $b) {
        return strcasecmp(($a['folder'] ?? '') . '/' . ($a['name'] ?? ''), ($b['folder'] ?? '') . '/' . ($b['name'] ?? ''));
    });
    return $documents;
}

function extract_application_bundle(string $archivePath, string $extractDir, string &$error = null): array {
    $error = null;
    delete_directory_tree($extractDir);
    if (!is_dir($extractDir) && !mkdir($extractDir, 0755, true) && !is_dir($extractDir)) {
        $error = 'Application package directory could not be prepared.';
        return [];
    }

    $entries = [];
    exec('tar -tf ' . escapeshellarg($archivePath) . ' 2>&1', $entries, $listCode);
    if ($listCode !== 0) {
        $error = 'The ZIP package could not be inspected.';
        return [];
    }

    foreach ($entries as $entry) {
        $normalized = str_replace('\\', '/', trim((string)$entry));
        if ($normalized === '' || str_ends_with($normalized, '/')) continue;
        if (str_contains($normalized, '../') || str_starts_with($normalized, '../') || preg_match('/^[A-Za-z]:/', $normalized) || str_starts_with($normalized, '/')) {
            $error = 'The ZIP package contains unsafe file paths.';
            return [];
        }
    }

    exec('tar -xf ' . escapeshellarg($archivePath) . ' -C ' . escapeshellarg($extractDir) . ' 2>&1', $extractOutput, $extractCode);
    if ($extractCode !== 0) {
        $error = 'The ZIP package could not be extracted.';
        return [];
    }

    return collect_extracted_documents($extractDir, '');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$job_id = $_POST['job_id'] ?? ($_GET['job_id'] ?? '');
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$cover_note = trim($_POST['cover_note'] ?? ($_POST['cover_letter'] ?? ''));

if (!$job_id || !$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Required fields missing or invalid email']);
    exit;
}

$coverWords = $cover_note === '' ? [] : array_values(array_filter(preg_split('/\s+/', $cover_note)));
if (count($coverWords) > 15) {
    echo json_encode(['success' => false, 'error' => 'Cover note must be 15 words or fewer']);
    exit;
}

try {
    $pdo = get_db();
    ensure_job_application_schema($pdo);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Database unavailable. Please try again later.']);
    exit;
}

$job = $pdo->prepare("SELECT id, title FROM job_listings WHERE id = ? AND status = 'published'");
$job->execute([$job_id]);
if (!$job->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Job listing is not available']);
    exit;
}

$bundleInput = $_FILES['application_bundle'] ?? ($_FILES['cv'] ?? null);
if (empty($bundleInput['tmp_name'])) {
    echo json_encode(['success' => false, 'error' => 'Please upload one ZIP file containing the applicant documents']);
    exit;
}

$allowedExtensions = ['zip'];
$ext = strtolower(pathinfo($bundleInput['name'] ?? '', PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExtensions, true)) {
    echo json_encode(['success' => false, 'error' => 'Applicant documents must be uploaded as a ZIP file']);
    exit;
}
if (($bundleInput['size'] ?? 0) > 15 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'ZIP package is too large (max 15MB)']);
    exit;
}

$id = 'app_' . time() . '_' . bin2hex(random_bytes(4));
$jobSlug = preg_replace('/[^a-z0-9_-]/i', '_', $job_id);
$applicationDir = JOB_APPLICATION_UPLOAD_ROOT . 'applications/' . $jobSlug . '/' . $id . '/';
$documentsDir = $applicationDir . 'documents/';
if (!is_dir($applicationDir) && !mkdir($applicationDir, 0755, true) && !is_dir($applicationDir)) {
    echo json_encode(['success' => false, 'error' => 'Application storage could not be prepared']);
    exit;
}

$originalName = trim((string)($bundleInput['name'] ?? 'application-documents.zip'));
$safeBaseName = preg_replace('/[^a-z0-9._-]/i', '_', pathinfo($originalName, PATHINFO_FILENAME));
if ($safeBaseName === '') $safeBaseName = 'application_documents';
$bundleFilename = $safeBaseName . '.zip';
$bundleAbsolutePath = $applicationDir . $bundleFilename;
$bundleRelativePath = normalize_relative_upload_path('applications/' . $jobSlug . '/' . $id . '/' . $bundleFilename);
$documentsRelativeDir = normalize_relative_upload_path('applications/' . $jobSlug . '/' . $id . '/documents');

if (!move_uploaded_file($bundleInput['tmp_name'], $bundleAbsolutePath)) {
    echo json_encode(['success' => false, 'error' => 'Applicant ZIP upload failed. Please try again.']);
    exit;
}

$extractError = null;
$documents = extract_application_bundle($bundleAbsolutePath, $documentsDir, $extractError);
if ($extractError !== null) {
    @unlink($bundleAbsolutePath);
    delete_directory_tree($documentsDir);
    echo json_encode(['success' => false, 'error' => $extractError]);
    exit;
}

$pdo->prepare("INSERT INTO job_applications (id, job_id, applicant_name, email, phone, cover_letter, cv_file, document_bundle_name, document_manifest, document_extract_dir, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())")
    ->execute([
        $id,
        $job_id,
        $name,
        $email,
        $phone,
        $cover_note,
        $bundleRelativePath,
        $originalName,
        json_encode($documents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $documentsRelativeDir,
        job_application_normalize_status('received')
    ]);
$pdo->prepare("UPDATE job_listings SET applications_count = applications_count + 1 WHERE id = ?")->execute([$job_id]);

try {
    @mail($email, 'Application received - Komagin Limited', "Thank you for applying to Komagin Limited. We will contact you shortly.");
} catch (Throwable $e) {
    log_activity($pdo, 'mail_failure', $e->getMessage());
}
log_activity($pdo, 'job_application_received', $id);
echo json_encode(['success' => true, 'message' => 'Application received. We will contact you shortly.']);
?>
