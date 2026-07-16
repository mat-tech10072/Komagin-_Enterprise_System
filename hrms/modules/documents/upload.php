<?php
require_once dirname(dirname(__DIR__)) . '/auth/session.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/functions.php';

requireLogin();
requirePermission('documents.upload', 'create');

$pageTitle  = 'Upload Document';
$activeMenu = 'documents';

$empId  = (int)($_GET['emp'] ?? 0);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $docEmpId   = (int)($_POST['employee_id'] ?? 0);
        $category   = $_POST['category'] ?? '';
        $docName    = trim($_POST['document_name'] ?? '');
        $expiryDate = $_POST['expiry_date'] ?? '';
        $notes      = trim($_POST['notes'] ?? '');

        if (!$docEmpId) $errors[] = 'Employee is required.';
        if (!$category) $errors[] = 'Category is required.';
        if (!$docName)  $errors[] = 'Document name is required.';
        if (empty($_FILES['document']['name'])) $errors[] = 'Please select a file.';

        if (empty($errors)) {
            $upload = uploadFile($_FILES['document'], 'documents', ALLOWED_DOC_TYPES);
            if (!$upload['success']) {
                $errors[] = 'File upload failed: ' . $upload['error'];
            } else {
                db()->prepare("INSERT INTO employee_documents
                    (employee_id, category, document_name, file_path, file_type, file_size, expiry_date, notes, uploaded_by)
                    VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([
                        $docEmpId, $category, $docName,
                        $upload['path'], $upload['mime'],
                        $_FILES['document']['size'],
                        $expiryDate ?: null,
                        $notes ?: null,
                        $_SESSION['user_id']
                    ]);
                auditLog('documents','upload',null,null,json_encode(['name'=>$docName,'emp'=>$docEmpId]));
                setFlash('success','Document uploaded successfully.');
                header('Location: ' . APP_URL . '/modules/employees/view.php?id='.$docEmpId.'&tab=documents');
                exit;
            }
        }
    }
}

$employees = db()->query("SELECT id, CONCAT(first_name,' ',last_name,' (',employee_number,')') as name FROM employees WHERE status != 'archived' ORDER BY first_name")->fetchAll();
$categories = ['id_document'=>'ID Document','certificate'=>'Certificate','contract'=>'Contract','medical'=>'Medical Document','warning_letter'=>'Warning Letter','promotion_letter'=>'Promotion Letter','leave_document'=>'Leave Document','resignation'=>'Resignation Letter','clearance'=>'Clearance Form','payslip'=>'Payslip','bank_document'=>'Bank Document','training_certificate'=>'Training Certificate','other'=>'Other'];
$csrf = generateCsrfToken();
?>
<?php include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/modules/documents/index.php">Documents</a></li>
                <li class="breadcrumb-item active">Upload</li>
            </ol>
        </nav>
        <h1 class="page-title">Upload Employee Document</h1>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach($errors as $e) echo e($e).'<br>'; ?></div>
<?php endif; ?>

<div style="max-width:640px;">
    <div class="card">
        <div class="card-header"><span class="card-title">Document Details</span></div>
        <form method="POST" enctype="multipart/form-data" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Employee <span class="required">*</span></label>
                    <select class="form-select" name="employee_id" required>
                        <option value="">Select employee</option>
                        <?php foreach ($employees as $em): ?>
                            <option value="<?= $em['id'] ?>" <?= $empId==$em['id']?'selected':''?>><?= e($em['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category <span class="required">*</span></label>
                        <select class="form-select" name="category" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $k=>$v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Document Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="document_name" placeholder="e.g. National ID, Degree Certificate" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Upload File <span class="required">*</span></label>
                    <input type="file" class="form-control" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                    <div class="form-hint">PDF, Word, or Image. Max 10MB.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Expiry Date (if applicable)</label>
                    <input type="date" class="form-control" name="expiry_date">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="2"></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">Upload Document</button>
                <a href="<?= APP_URL ?>/modules/documents/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
