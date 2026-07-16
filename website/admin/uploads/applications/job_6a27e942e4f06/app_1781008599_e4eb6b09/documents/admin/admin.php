<?php
// admin.php - Complete CMS API and Web Admin Dashboard for Komagin Limited
// KOMAGIN v3 shared DB bootstrap
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$statelessPublicActions = [
    'get_projects', 'get_project_single', 'get_project_categories',
    'get_services', 'get_service_single',
    'get_testimonials', 'get_team', 'get_settings', 'submit_contact',
    'subscribe_newsletter', 'get_documents', 'get_document', 'get_csr_items',
    'get_partner_showcase', 'hr_get_jobs', 'partners_save_enquiry',
    'social_get_posts', 'blog_get_posts', 'blog_get_post', 'hire_get_items'
];
$requiresSession = $action === '' || !in_array($action, $statelessPublicActions, true);

if ($requiresSession && session_status() === PHP_SESSION_NONE) {
    $sessionDir = __DIR__ . '/sessions';
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }
    session_start();
}
if ($action === '' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    if (!isset($_SESSION['admin_logged_in']) || ($_SESSION['user_role'] ?? ($_SESSION['admin_role'] ?? '')) !== 'admin') {
        header('Location: index.php?error=auth');
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/admin.html');
    exit;
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
// Prevent ALL caching — ensures admin saves are immediately visible on the public site
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log errors but never print them ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â printed warnings corrupt JSON responses.
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Define paths
define('ROOT_DIR', dirname(__DIR__));
define('UPLOADS_DIR', __DIR__ . '/uploads/');

// Create directories if they don't exist
$directories = [UPLOADS_DIR];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Determine base URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$base = $protocol . $host . rtrim($scriptPath, '/\\') . '/';
define('ADMIN_URL', $base);
$siteUrl = preg_replace('#/admin/?$#', '/', rtrim($base, '/'));
if (!is_string($siteUrl) || $siteUrl === '') {
    $siteUrl = $base;
}
define('SITE_URL', rtrim($siteUrl, '/') . '/');

// ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ Top-level exception handler ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬
// Wraps ALL action processing so any uncaught exception (DB down, missing
// table, PHP error) always returns valid JSON ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â never a broken response.
try {

// Schema migrations ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â safe to skip if DB is unavailable
try {
    ensure_branch_content_schema(get_db());
    ensureCsrSchema();
    ensureJobListingsSchema();
    ensureJobApplicationsSchema();
    ensure_komagin_workflow_schema(get_db());
    ensurePartnersSchema();
    ensurePartnerShowcaseSchema();
} catch (Throwable $e) {
    // DB may not be set up yet; public read actions still need to respond.
    // Fall through ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â individual handlers will fail gracefully if needed.
}

// Public actions that don't require authentication
$publicActions = [
    'get_projects', 'get_project_single', 'get_project_categories',
    'get_services', 'get_service_single',
    'get_testimonials', 'get_team', 'get_settings', 'submit_contact',
    'login', 'logout', 'check_auth', 'subscribe_newsletter',
    'get_documents', 'get_document', 'get_csr_items', 'get_partner_showcase', 'hr_get_jobs', 'partners_save_enquiry', 'social_get_posts',
    'blog_get_posts', 'blog_get_post', 'hire_get_items'
];

// Check authentication for protected actions
if (!in_array($action, $publicActions)) {
    checkAuthentication();
    enforceActionRole($action);
}

$disabledActions = [
    'assets_get_all', 'assets_save', 'assets_delete', 'assets_assign', 'assets_return',
    'assets_upload_photo', 'assets_generate_assignment',
    'branch_users_get_all', 'branch_users_save', 'branch_users_toggle_active',
    'branch_user_password_set', 'branch_password_set', 'branch_manager_password_set',
    'branch_projects_get', 'branch_projects_save', 'branch_projects_update_progress',
    'branch_generate_project_report', 'branch_expenses_get', 'branch_expenses_save',
    'branch_expenses_approve', 'branch_expenses_reject', 'branch_expenses_upload_receipt',
    'branch_generate_expense_report', 'branch_assets_get', 'branch_assets_return',
    'branch_hub_get', 'branch_content_get', 'branch_content_set_status',
    'branch_reports_get', 'branch_reports_save', 'branch_reports_verify',
    'branch_reports_flag', 'branch_reports_upload_photos', 'branch_generate_site_report',
    'branch_milestones_get', 'branch_milestones_save', 'branch_milestones_complete',
    'branch_milestones_upload_evidence', 'branch_milestones_recalculate_progress',
    'branch_rfis_get', 'branch_rfis_save', 'branch_rfis_answer', 'branch_rfis_close',
    'branch_generate_rfi_response', 'branch_kpis_get', 'branch_kpis_generate',
    'branch_kpis_update_notes', 'branch_generate_kpi_report',
    'reports_get_hr', 'reports_get_projects', 'reports_get_expenses',
    'reports_get_assets', 'reports_get_partners',
    'users_get_all', 'users_save', 'users_toggle_active'
];

if (in_array($action, $disabledActions, true)) {
    echo json_encode([
        'success' => false,
        'error' => 'This capability has been removed from P01. It belongs to another EMS panel.'
    ]);
    exit;
}

// Route actions
if (shouldRunScheduledSocialPostsForAction($action)) {
    try { processScheduledSocialPosts(); } catch (Throwable $e) { /* non-critical */ }
}
$response = handleAction($action);
echo json_encode($response);

} catch (Throwable $e) {
    // Last-resort handler ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â always returns valid JSON
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'hint'    => 'Check that MySQL is running, credentials in admin/db_config.php are correct, and that you have run admin/setup.php'
    ]);
}

// ============ FUNCTIONS ============

function handleAction($action) {
    switch ($action) {
        // Project actions
        case 'get_projects':
            return getProjects();
        case 'get_project_single':
            return getProjectSingle();
        case 'create_project':
            return createProject();
        case 'update_project':
            return updateProject();
        case 'delete_project':
            return deleteProject();
        case 'get_project_categories':
            return getProjectCategories();
        case 'save_project_category':
            return saveProjectCategory();
        case 'delete_project_category':
            return deleteProjectCategory();
        
        // Service actions
        case 'get_services':
            return getServices();
        case 'get_service_single':
            return getServiceSingle();
        case 'create_service':
            return createService();
        case 'update_service':
            return updateService();
        case 'delete_service':
            return deleteService();
        
        // Testimonial actions
        case 'get_testimonials':
            return getTestimonials();
        case 'create_testimonial':
            return createTestimonial();
        case 'update_testimonial':
            return updateTestimonial();
        case 'delete_testimonial':
            return deleteTestimonial();
        
        // Team actions
        case 'get_team':
            return getTeam();
        case 'create_team':
            return createTeam();
        case 'update_team':
            return updateTeam();
        case 'delete_team':
            return deleteTeam();
        
        // Document actions
        case 'get_documents':
            return getDocuments();
        case 'documents_get_all':
            return documentsGetAll();
        case 'get_document':
            return getDocument();
        case 'document_save':
            return documentSave();
        case 'get_csr_items':
            return getCsrItems();
        case 'get_partner_showcase':
            return getPartnerShowcase();
        case 'csr_get_all':
            return csrGetAll();
        case 'hire_get_items':
            return hireGetItems();
        case 'hire_items_get_all':
            return hireItemsGetAll();
        case 'hire_item_save':
            return hireItemSave();
        case 'hire_item_delete':
            return hireItemDelete();
        case 'csr_save':
            return csrSave();
        case 'csr_delete':
            return csrDelete();
        case 'upload_document':
            return uploadDocument();
        case 'document_remove_file':
            return documentRemoveFile();
        case 'delete_document':
            return deleteDocument();
        
        // Contact actions
        case 'get_contacts':
            return getContacts();
        case 'submit_contact':
            return submitContact();
        case 'update_contact_status':
            return updateContactStatus();
        case 'delete_contact':
            return deleteContact();
        
        // Newsletter actions
        case 'subscribe_newsletter':
            return subscribeNewsletter();
        case 'get_subscribers':
            return getSubscribers();
        case 'send_newsletter':
            return sendNewsletter();

        // Blog CMS
        case 'blog_get_posts':
            return blogGetPosts();
        case 'blog_get_post':
            return blogGetPost();
        case 'blog_posts_get_all':
            return blogPostsGetAll();
        case 'blog_posts_save':
            return blogPostsSave();
        case 'blog_posts_delete':
            return blogPostsDelete();
        
        // Settings actions
        case 'get_settings':
            return getSettings();
        case 'update_settings':
            return updateSettings();
        case 'email_test_configuration':
            return emailTestConfiguration();
        
        // Stats
        case 'get_stats':
            return getStats();
        
        // File uploads
        case 'upload_image':
            return uploadImage();
        
        // Authentication
        case 'login':
            return login();
        case 'logout':
            return logout();
        case 'check_auth':
            return checkAuth();
        case 'change_password':
            return changePassword();
        case 'update_profile':
            return updateProfile();
        case 'get_profile':
            return getProfile();
        
        // Upgrade v2.0 session and role
        case 'get_session':
            return getSession();

        // HR management
        case 'hr_get_staff':
            return hrGetStaff();
        case 'hr_save_staff':
            return hrSaveStaff();
        case 'hr_delete_staff':
            return hrDeleteStaff();
        case 'hr_upload_staff_photo':
            return uploadScopedFile('photo', 'staff');
        case 'hr_generate_payslip':
            return hrGeneratePayslip();
        case 'hr_generate_letter':
            return hrGenerateLetter();
        case 'hr_get_leave':
            return hrGetLeave();
        case 'hr_save_leave':
            return hrSaveLeave();
        case 'hr_approve_leave':
            return hrSetLeaveStatus('approved');
        case 'hr_reject_leave':
            return hrSetLeaveStatus('rejected');
        case 'hr_get_payroll':
            return hrGetPayroll();
        case 'hr_generate_payroll':
            return hrGeneratePayroll();
        case 'hr_save_payroll_record':
            return hrSavePayrollRecord();
        case 'hr_process_payroll':
            return hrProcessPayroll();
        case 'hr_get_jobs':
            return hrGetJobs();
        case 'hr_save_job':
            return hrSaveJob();
        case 'hr_delete_job':
            return hrDeleteJob();
        case 'hr_toggle_job_status':
            return hrToggleJobStatus();
        case 'hr_get_applications':
            return hrGetApplications();
        case 'hr_get_application_detail':
            return hrGetApplicationDetail();
        case 'hr_update_application_status':
            return hrUpdateApplicationStatus();
        case 'hr_generate_interview_invite':
            return hrGenerateInterviewInvite();

        // Asset management
        case 'assets_get_all':
            return assetsGetAll();
        case 'assets_save':
            return assetsSave();
        case 'assets_delete':
            return assetsDelete();
        case 'assets_assign':
            return assetsAssign();
        case 'assets_return':
            return assetsReturn();
        case 'assets_upload_photo':
            return uploadScopedFile('photo', 'assets');
        case 'assets_generate_assignment':
            return assetsGenerateAssignment();
        case 'maintenance_get_all':
            return maintenanceGetAll();
        case 'maintenance_save':
            return maintenanceSave();
        case 'maintenance_complete':
            return maintenanceComplete();

        // Files management
        case 'files_get_categories':
            return filesGetCategories();
        case 'files_create_category':
            return filesCreateCategory();
        case 'files_update_category':
            return filesUpdateCategory();
        case 'files_delete_category':
            return filesDeleteCategory();
        case 'files_get_by_category':
            return filesGetByCategory();
        case 'media_library_get_assets':
            return mediaLibraryGetAssets();
        case 'files_upload':
            return filesUpload();
        case 'files_delete':
            return filesDelete();
        case 'files_toggle_template':
            return filesToggleTemplate();
        case 'files_download':
            return filesDownload();

        // Branch management
        case 'branches_get_all':
            return branchesGetAll();
        case 'branches_save':
            return branchesSave();
        case 'branches_delete':
            return branchesDelete();
        case 'branches_get_detail':
            return branchesGetDetail();
        case 'branches_provision_template':
            return branchesProvisionTemplate();
        case 'branch_users_get_all':
        case 'branch_users_save':
        case 'branch_users_toggle_active':
        case 'branch_user_password_set':
            return branchUsersDeprecated();
        case 'branch_password_set':
        case 'branch_manager_password_set':
            return branchPasswordSet();
        case 'branch_projects_get':
            return branchProjectsGet();
        case 'branch_projects_save':
            return branchProjectsSave();
        case 'branch_projects_update_progress':
            return branchProjectsUpdateProgress();
        case 'branch_generate_project_report':
            return branchGenerateProjectReport();
        case 'branch_expenses_get':
            return branchExpensesGet();
        case 'branch_expenses_save':
            return branchExpensesSave();
        case 'branch_expenses_approve':
            return branchExpensesSetStatus('approved');
        case 'branch_expenses_reject':
            return branchExpensesSetStatus('rejected');
        case 'branch_expenses_upload_receipt':
            return uploadScopedFile('receipt', 'receipts');
        case 'branch_generate_expense_report':
            return branchGenerateExpenseReport();
        case 'branch_assets_get':
            return branchAssetsGet();
        case 'branch_assets_return':
            return branchAssetsReturn();
        case 'branch_hub_get':
            return branchHubGet();
        case 'branch_content_get':
            return branchContentGet();
        case 'branch_content_set_status':
            return branchContentSetStatus();

        // Partner management
        case 'partners_get_all':
            return partnersGetAll();
        case 'partners_delete':
            return partnersDelete();
        case 'partners_get_detail':
            return partnersGetDetail();
        case 'partner_showcase_get_all':
            return partnerShowcaseGetAll();
        case 'partners_save_enquiry':
            return partnersSaveEnquiry();
        case 'partner_showcase_save':
            return partnerShowcaseSave();
        case 'partner_showcase_delete':
            return partnerShowcaseDelete();
        case 'partners_update_status':
            return partnersUpdateStatus();
        case 'partners_generate_nda':
            return partnersGenerateNda();

        // Reports and users
        case 'reports_get_hr':
            return reportsGetHr();
        case 'reports_get_projects':
            return reportsGetProjects();
        case 'reports_get_expenses':
            return reportsGetExpenses();
        case 'reports_get_assets':
            return reportsGetAssets();
        case 'reports_get_partners':
            return reportsGetPartners();
        case 'users_get_all':
            return usersGetAll();
        case 'users_save':
            return usersSave();
        case 'users_toggle_active':
            return usersToggleActive();
        // Social media management
        case 'social_get_platforms':
            return socialGetPlatforms();
        case 'social_save_platform_config':
            return socialSavePlatformConfig();
        case 'social_disconnect_platform':
            return socialDisconnectPlatform();
        case 'social_test_connection':
            return socialTestConnection();
        case 'social_upload_media':
            return socialUploadMedia();
        case 'social_get_media_library':
            return socialGetMediaLibrary();
        case 'social_schedule_post':
            return socialSchedulePost();
        case 'social_publish_post':
            return socialPublishPost();
        case 'social_get_posts':
            return socialGetPosts();
        case 'social_delete_post':
            return socialDeletePost();

        // Branch monitoring v3
        case 'branch_reports_get':
            return branchReportsGet();
        case 'branch_reports_save':
            return branchReportsSave();
        case 'branch_reports_verify':
            return branchReportsSetStatus('verified');
        case 'branch_reports_flag':
            return branchReportsSetStatus('flagged');
        case 'branch_reports_upload_photos':
            return branchReportsUploadPhotos();
        case 'branch_generate_site_report':
            return branchGenerateSiteReport();
        case 'branch_milestones_get':
            return branchMilestonesGet();
        case 'branch_milestones_save':
            return branchMilestonesSave();
        case 'branch_milestones_complete':
            return branchMilestonesComplete();
        case 'branch_milestones_upload_evidence':
            return branchMilestonesUploadEvidence();
        case 'branch_milestones_recalculate_progress':
            return branchMilestonesRecalculateProgress();
        case 'branch_rfis_get':
            return branchRfisGet();
        case 'branch_rfis_save':
            return branchRfisSave();
        case 'branch_rfis_answer':
            return branchRfisAnswer();
        case 'branch_rfis_close':
            return branchRfisClose();
        case 'branch_generate_rfi_response':
            return branchGenerateRfiResponse();
        case 'branch_kpis_get':
            return branchKpisGet();
        case 'branch_kpis_generate':
            return branchKpisGenerate();
        case 'branch_kpis_update_notes':
            return branchKpisUpdateNotes();
        case 'branch_generate_kpi_report':
            return branchGenerateKpiReport();

        // Activity log
        case 'activity_logs_get':
            return activityLogsGet();
        case 'activity_logs_purge':
            return activityLogsPurge();
        default:
            return ['success' => false, 'error' => 'Invalid action: ' . $action];
    }
}

function checkAuthentication() {
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
}

function isAuthenticated() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    $sessionTimeout = 7200;
    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > $sessionTimeout)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['admin_last_activity'] = time();
    return true;
}

// ============ UPGRADE v2.0 HELPERS AND MODULES ============

function getSession() {
    return [
        'success' => true,
        'authenticated' => isAuthenticated(),
        'user' => [
            'id' => $_SESSION['admin_id'] ?? '',
            'username' => $_SESSION['admin_username'] ?? '',
            'email' => $_SESSION['admin_email'] ?? '',
            'role' => currentUserRole()
        ],
        'user_role' => currentUserRole()
    ];
}

function currentUserRole() {
    return $_SESSION['user_role'] ?? ($_SESSION['admin_role'] ?? 'admin');
}

function currentUsername() {
    return $_SESSION['admin_username'] ?? 'system';
}

function enforceActionRole($action) {
    if (!isAuthenticated()) return;
    $role = currentUserRole();
    if ($role === 'admin' || $role === 'super_admin') return;

    $hrAllowed = [
        'get_session', 'check_auth', 'logout', 'change_password', 'update_profile',
        'get_profile', 'get_settings', 'get_stats'
    ];

    if ($role === 'hr_admin') {
        if (in_array($action, $hrAllowed, true) || strpos($action, 'hr_') === 0 || strpos($action, 'files_') === 0) {
            return;
        }
    }

    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

function requireRoles($roles) {
    $role = currentUserRole();
    if (!in_array($role, $roles, true) && $role !== 'super_admin') {
        http_response_code(403);
        return false;
    }
    return true;
}

function getPdoConnection(): ?PDO {
    try {
        return get_db();
    } catch (Throwable $e) {
        return null; // callers check for null and return safe defaults
    }
}

function dbTableExists($table) {
    $pdo = getPdoConnection();
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?"
        );
        $stmt->execute([$table]);
        return ((int)$stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function dbColumnExists($table, $column) {
    $pdo = getPdoConnection();
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
        );
        $stmt->execute([$table, $column]);
        return ((int)$stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function shouldRunScheduledSocialPostsForAction(string $action): bool {
    if ($action === '') return false;
    $socialActions = [
        'social_get_platforms',
        'social_save_platform_config',
        'social_disconnect_platform',
        'social_test_connection',
        'social_upload_media',
        'social_get_media_library',
        'social_schedule_post',
        'social_publish_post',
        'social_get_posts',
        'social_delete_post'
    ];
    return in_array($action, $socialActions, true);
}

function ensureBranchUsersSchema() {
    if (!dbTableExists('branch_users')) {
        dbWrite("CREATE TABLE IF NOT EXISTS branch_users (id VARCHAR(50) NOT NULL, branch_id VARCHAR(50) NOT NULL, username VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, full_name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, role VARCHAR(50) DEFAULT 'branch_user', portal_scope VARCHAR(20) DEFAULT 'admin', notes TEXT DEFAULT NULL, is_active TINYINT(1) DEFAULT 1, last_login DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id), INDEX idx_branch_id (branch_id), INDEX idx_scope (portal_scope), INDEX idx_active (is_active)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return;
    }

    $columns = [
        'role' => "ALTER TABLE branch_users ADD COLUMN role VARCHAR(50) DEFAULT 'branch_user' AFTER phone",
        'portal_scope' => "ALTER TABLE branch_users ADD COLUMN portal_scope VARCHAR(20) DEFAULT 'admin' AFTER role",
        'notes' => "ALTER TABLE branch_users ADD COLUMN notes TEXT DEFAULT NULL AFTER portal_scope"
    ];

    foreach ($columns as $column => $sql) {
        if (!dbColumnExists('branch_users', $column)) {
            try {
                dbWrite($sql);
            } catch (Throwable $e) {
                // Existing databases may already be patched by another import.
            }
        }
    }
}

function requireDb($tables = []) {
    $pdo = getPdoConnection();
    if (!$pdo) {
        http_response_code(503);
        return ['error' => 'Database unavailable. Check credentials in admin/db_config.php, ensure MySQL is running, then open admin/setup.php to create the database.'];
    }
    foreach ((array)$tables as $table) {
        if (!dbTableExists($table)) {
            http_response_code(503);
            return ['error' => "Database table '{$table}' is missing. Open admin/setup.php to create all required tables."];
        }
    }
    return $pdo;
}

function requestData() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!is_array($data)) $data = $_POST;
    return $data ?: [];
}

function cleanDbValue($value) {
    if (is_array($value)) return json_encode($value);
    if ($value === '') return null;
    return $value;
}

function dbAll($sql, $params = []) {
    $pdo = getPdoConnection();
    if (!$pdo) return [];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbOne($sql, $params = []) {
    $rows = dbAll($sql, $params);
    return $rows[0] ?? null;
}

function dbScalar($sql, $params = []) {
    $pdo = getPdoConnection();
    if (!$pdo) return 0;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() ?: 0;
}

function dbWrite($sql, $params = []) {
    $pdo = getPdoConnection();
    if (!$pdo) return false;
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function dbCount($table) {
    if (!dbTableExists($table)) return 0;
    return (int)dbScalar("SELECT COUNT(*) FROM `$table`");
}

function addUploadUrls(&$rows) {
    foreach ($rows as &$row) {
        if (!empty($row['image'])) {
            $row['image'] = normalizeProjectCmsAssetPath($row['image']);
            if (preg_match('/^(https?:)?\/\//i', $row['image']) || strpos($row['image'], '/') === 0) {
                $row['image_url'] = $row['image'];
            } elseif (strpos($row['image'], 'images/') === 0) {
                $row['image_url'] = SITE_URL . ltrim($row['image'], '/');
            } elseif (strpos($row['image'], 'admin/') === 0) {
                $row['image_url'] = SITE_URL . ltrim($row['image'], '/');
            } else {
                $row['image_url'] = ADMIN_URL . 'uploads/' . ltrim($row['image'], '/');
            }
        } elseif (array_key_exists('image', $row)) {
            $row['image_url'] = '';
        }
        if (!empty($row['filename']) && empty($row['file_url'])) {
            $row['file_url'] = ADMIN_URL . 'uploads/' . $row['filename'];
        }
    }
}

function normalizeDbJsonFields(&$rows, $fields) {
    foreach ($rows as &$row) {
        foreach ($fields as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $decoded = json_decode($row[$field], true);
                if (json_last_error() === JSON_ERROR_NONE) $row[$field] = $decoded;
            }
        }
    }
}

function normalizeProjectCmsAssetPath($path) {
    $raw = trim((string)$path);
    if ($raw === '') return '';
    if (preg_match('/^(https?:)?\/\//i', $raw) || strpos($raw, '/') === 0) return $raw;
    if (strpos($raw, 'admin/uploads/') === 0 || strpos($raw, 'images/') === 0) return $raw;
    if (strpos($raw, 'uploads/') === 0) return 'admin/' . $raw;
    return 'admin/uploads/' . ltrim($raw, '/');
}

function normalizeProjectGalleryPayload($value) {
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $value = $decoded;
        } else {
            $value = preg_split('/[\r\n,]+/', $value) ?: [];
        }
    }
    if (!is_array($value)) return [];
    $normalized = [];
    foreach ($value as $item) {
        $path = normalizeProjectCmsAssetPath($item);
        if ($path !== '') $normalized[] = $path;
    }
    return array_values(array_unique($normalized));
}

function normalizeProjectScopePayload($value, $fallbackDescription = '') {
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $value = $decoded;
        } else {
            $value = [];
        }
    }
    $normalized = [];
    if (is_array($value)) {
        foreach ($value as $section) {
            if (!is_array($section)) continue;
            $title = trim((string)($section['title'] ?? ''));
            $text = trim((string)($section['text'] ?? ($section['description'] ?? '')));
            $points = $section['points'] ?? [];
            if (is_string($points)) {
                $points = preg_split('/\r?\n+/', $points) ?: [];
            }
            if (!is_array($points)) $points = [];
            $points = array_values(array_filter(array_map(static function($point) {
                return trim((string)$point);
            }, $points)));
            if ($title === '' && $text === '' && !$points) continue;
            $normalized[] = [
                'title' => $title !== '' ? $title : 'Project Scope',
                'text' => $text,
                'points' => $points
            ];
        }
    }
    if (!$normalized && trim((string)$fallbackDescription) !== '') {
        $lines = preg_split('/\r?\n+/', trim((string)$fallbackDescription)) ?: [];
        $points = array_values(array_filter(array_map(static function($line) {
            return trim((string)$line);
        }, $lines)));
        $normalized[] = [
            'title' => 'Project Scope',
            'text' => '',
            'points' => $points
        ];
    }
    return $normalized;
}

function ensureProjectCmsSchema() {
    if (!dbTableExists('projects')) return;
    if (!dbColumnExists('projects', 'gallery_images')) {
        try {
            dbWrite("ALTER TABLE projects ADD COLUMN gallery_images LONGTEXT DEFAULT NULL AFTER image");
        } catch (Throwable $e) {}
    }
    if (!dbColumnExists('projects', 'scope_sections')) {
        try {
            dbWrite("ALTER TABLE projects ADD COLUMN scope_sections LONGTEXT DEFAULT NULL AFTER technologies");
        } catch (Throwable $e) {}
    }
    if (!dbColumnExists('projects', 'status')) {
        try {
            dbWrite("ALTER TABLE projects ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'PENDING' AFTER category");
        } catch (Throwable $e) {}
    }
    if (!dbColumnExists('projects', 'branch_name')) {
        try {
            dbWrite("ALTER TABLE projects ADD COLUMN branch_name VARCHAR(255) DEFAULT NULL AFTER branch_id");
        } catch (Throwable $e) {}
    }
    // Create project_categories table if missing
    if (!dbTableExists('project_categories')) {
        try {
            dbWrite("CREATE TABLE IF NOT EXISTS `project_categories` (
                id          VARCHAR(50)  NOT NULL,
                name        VARCHAR(255) NOT NULL,
                slug        VARCHAR(100) NOT NULL,
                description TEXT         DEFAULT NULL,
                sort_order  INT          DEFAULT 0,
                created_at  DATETIME     DEFAULT NULL,
                updated_at  DATETIME     DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uk_project_categories_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            // Seed the three default categories that match existing project data
            $defaults = [
                ['projcat_subdivision',   'Subdivision',    'subdivision',    'Residential and land subdivision projects',       1],
                ['projcat_building',      'Building',       'building',       'Commercial and residential building construction', 2],
                ['projcat_infrastructure','Infrastructure', 'infrastructure', 'Roads, utilities, and civil infrastructure',       3],
            ];
            foreach ($defaults as [$id, $name, $slug, $desc, $order]) {
                dbWrite(
                    "INSERT IGNORE INTO project_categories (id,name,slug,description,sort_order,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())",
                    [$id, $name, $slug, $desc, $order]
                );
            }
        } catch (Throwable $e) {}
    }
}

function normalizeServiceDetailSectionsPayload($value, $fallbackDescription = '') {
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $value = $decoded;
        }
    }
    $normalized = [];
    if (is_array($value)) {
        foreach ($value as $section) {
            if (!is_array($section)) continue;
            $title = trim((string)($section['title'] ?? ''));
            $text = trim((string)($section['text'] ?? ($section['description'] ?? '')));
            $points = $section['points'] ?? [];
            if (is_string($points)) {
                $points = preg_split('/\r?\n+/', $points) ?: [];
            }
            if (!is_array($points)) $points = [];
            $points = array_values(array_filter(array_map(static function($point) {
                return trim((string)$point);
            }, $points)));
            if ($title === '' && $text === '' && !$points) continue;
            $normalized[] = [
                'title' => $title !== '' ? $title : 'Service Details',
                'text' => $text,
                'points' => $points
            ];
        }
    }
    if (!$normalized && trim((string)$fallbackDescription) !== '') {
        $normalized[] = [
            'title' => 'Service Details',
            'text' => trim((string)$fallbackDescription),
            'points' => []
        ];
    }
    return $normalized;
}

function ensureServiceDetailsSchema() {
    if (!dbTableExists('services')) return;
    if (!dbColumnExists('services', 'detail_intro')) {
        try {
            dbWrite("ALTER TABLE services ADD COLUMN detail_intro TEXT DEFAULT NULL AFTER description");
        } catch (Throwable $e) {
        }
    }
    if (!dbColumnExists('services', 'detail_sections')) {
        try {
            dbWrite("ALTER TABLE services ADD COLUMN detail_sections LONGTEXT DEFAULT NULL AFTER detail_intro");
        } catch (Throwable $e) {
        }
    }
    if (!dbColumnExists('services', 'featured')) {
        try {
            dbWrite("ALTER TABLE services ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER image");
        } catch (Throwable $e) {
        }
    }
}

function normalizeServiceRows(&$rows) {
    foreach ($rows as &$row) {
        $row['detail_intro'] = trim((string)($row['detail_intro'] ?? ''));
        $row['detail_sections'] = normalizeServiceDetailSectionsPayload($row['detail_sections'] ?? [], $row['description'] ?? '');
        $row['featured'] = (int)($row['featured'] ?? 0);
    }
}

function normalizeProjectCmsRows(&$rows) {
    foreach ($rows as &$row) {
        $row['gallery_images'] = normalizeProjectGalleryPayload($row['gallery_images'] ?? []);
        $row['scope_sections'] = normalizeProjectScopePayload($row['scope_sections'] ?? [], $row['description'] ?? '');
        $row['featured'] = (int)($row['featured'] ?? 0);
    }
}

function dbUpsert($table, $fields, $data, $prefix) {
    $db = requireDb([$table]);
    if (is_array($db) && isset($db['error'])) return ['success' => false, 'error' => $db['error']];
    $id = trim($data['id'] ?? '');
    $now = date('Y-m-d H:i:s');
    $record = [];
    foreach ($fields as $field) {
        if (array_key_exists($field, $data)) $record[$field] = cleanDbValue($data[$field]);
    }
    if (in_array('updated_at', $fields, true)) $record['updated_at'] = $now;

    try {
        if ($id) {
            if (!$record) return ['success' => false, 'error' => 'No data supplied'];
            $sets = [];
            $params = [];
            foreach ($record as $field => $value) {
                if ($field === 'id' || $field === 'created_at') continue;
                $sets[] = "`$field` = ?";
                $params[] = $value;
            }
            $params[] = $id;
            $db->prepare("UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
        } else {
            $id = $prefix . '_' . uniqid();
            $record['id'] = $id;
            if (in_array('created_at', $fields, true) && empty($record['created_at'])) $record['created_at'] = $now;
            if (in_array('created_by', $fields, true) && empty($record['created_by'])) $record['created_by'] = currentUsername();
            $cols = array_keys($record);
            $placeholders = array_fill(0, count($cols), '?');
            $db->prepare("INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")")->execute(array_values($record));
        }
        logActivityDb($table . '_save', $id);
        return ['success' => true, 'message' => 'Record saved', 'data' => dbOne("SELECT * FROM `$table` WHERE id = ?", [$id])];
    } catch (Throwable $e) {
        http_response_code(400);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function logActivityDb($action, $details = '') {
    try {
        $stmt = get_db()->prepare("INSERT INTO activity_logs (user_id, username, action, details, ip_address, user_agent, created_at) VALUES (?,?,?,?,?,?,NOW())");
        $stmt->execute([
            $_SESSION['admin_id'] ?? null,
            currentUsername(),
            $action,
            is_scalar($details) ? (string)$details : json_encode($details),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Throwable $e) {
        // Logging must not break the business action.
    }
}

function ensureFileCategories() {
    if (!dbTableExists('file_categories')) return;
    ensureFileCategoriesSchema();
}

function ensureFileCategoriesSchema() {
    if (!dbTableExists('file_categories')) return;
    if (!dbColumnExists('file_categories', 'is_system_folder')) {
        dbWrite("ALTER TABLE file_categories ADD COLUMN is_system_folder TINYINT(1) DEFAULT 0 AFTER description");
    }
    dbWrite("UPDATE file_categories SET is_system_folder = 1 WHERE id LIKE 'cat\\_%' AND id NOT LIKE 'cat\\_custom\\_%'");
}

function getSettingsArray() {
    $settings = [];
    if (dbTableExists('settings')) {
        foreach (dbAll("SELECT setting_key, setting_value FROM settings") as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings + [
        'store_name' => 'Komagin Limited',
        'store_email' => 'info@komagin.com',
        'secondary_email' => 'projects@komagin.com',
        'store_phone' => '+675 1234 5678',
        'whatsapp_number' => '+675 1234 5678',
        'whatsapp_url' => 'https://wa.me/67512345678',
        'store_address' => 'Port Moresby, Papua New Guinea',
        'office_map_url' => 'https://maps.google.com/?q=Port%20Moresby%2C%20Papua%20New%20Guinea',
        'business_hours' => "Monday - Friday: 8:00 AM - 5:00 PM\nSaturday: 9:00 AM - 1:00 PM",
        'facebook' => 'https://facebook.com/komaginlimited',
        'youtube' => 'https://youtube.com/@komaginlimited',
        'youtube_url' => 'https://youtube.com/@komaginlimited',
        'linkedin' => 'https://linkedin.com/company/komagin-limited',
        'twitter' => 'https://twitter.com/komaginlimited',
        'instagram' => 'https://instagram.com/komaginlimited',
        'email_transport' => 'php_mail',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_encryption' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_email' => 'info@komagin.com',
        'smtp_from_name' => 'Komagin Limited',
        'smtp_reply_to' => 'info@komagin.com',
        'smtp_test_recipient' => 'info@komagin.com',
        'email_verification_status' => 'pending',
        'email_last_verified_at' => '',
        'email_verification_message' => 'Complete the email delivery settings and run verification before linking a production mailbox.',
        'default_currency' => 'PGK',
        'hr_admin_email' => 'hr@komagin.com',
        'hero_background_image' => 'images/hero-bg.jpeg',
        'hero_background_images' => json_encode([
            'images/hero-bg.jpeg',
            'image_20260505_055538_69f96a3ae5436.jpeg',
            'image_20260505_055930_69f96b2272d4c.jpeg',
            'image_20260505_060920_69f96d70ce8d8.jpeg',
            'image_20260505_122337_69f9c52928f9e.jpeg',
            'image_20260505_124028_69f9c91c22f39.jpeg'
        ]),
        'cta_background_image' => 'images/hero-bg.jpeg',
        'hero_badge_text' => 'Est. 2015 | Engineering Excellence Across Markets',
        'hero_title_line_1' => 'Quality Civil &',
        'hero_title_line_2' => 'Structural Engineering',
        'hero_title_line_3' => 'for Complex Developments',
        'hero_description' => "Delivering technical excellence through innovative engineering solutions, combining precision delivery with sustainable development practices for clients, projects, and infrastructure programs across local, regional, and international markets.",
        'hero_primary_label' => 'Our Projects',
        'hero_primary_target' => 'projects',
        'hero_secondary_label' => 'Request Consultation',
        'hero_secondary_target' => 'contact',
        'mission_title' => 'Our Mission',
        'mission_text' => 'To provide high-quality civil and engineering services to our clients through the provision of professional, technology-driven engineering solutions that supports businesses, and the infrastructure development of the country.',
        'vision_title' => 'Our Vision',
        'vision_text' => 'We strive to make Papua New Guinea a better place to live and do business by helping develop the nation through providing quality civil and structural engineering services.',
        'about_page_title' => 'About Komagin Limited',
        'about_page_subtitle' => 'Delivering engineering excellence in Papua New Guinea since 2015',
        'about_story_label' => 'OUR STORY',
        'about_story_title' => 'Company History',
        'about_story_content' => "Established in November 2015, Komagin Limited was founded in response to the increasing demand for civil and structural engineering projects development as the result of Papua New Guinea being a developing nation where many infrastructural activities are taking place.\n\nDespite being a relatively new entrant, the company is backed by a wealth of practical experience drawn from its management team and frontline operatives. These professionals come with years of background in engineering, technical services, and business expertise.\n\nKomagin Limited's operational model is distinguished by its commitment to leveraging human expertise in combination with modern engineering technologies, including GPS, Total Stations (GNSS), and drones.\n\nWe aim to retain all our professionals within PNG to help build our economy. Furthermore, we use every opportunity to train our skilled technical professionals, with the intent to become competent at the global level.",
        'hire_page_badge' => 'Plant & Machines for Hire',
        'hire_page_title' => 'Reliable Equipment Hire for Demanding Projects',
        'hire_page_subtitle' => 'Access well-maintained plant, machinery, and support equipment backed by KomaginÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢s engineering and project delivery standards.',
        'hire_page_intro' => 'Browse available plant and machine hire options for civil works, infrastructure delivery, surveying support, and site operations. Each listing can be updated from the admin panel as availability changes.',
        'hire_page_contact_phone' => '+675 7159 0097',
        'hire_page_contact_email' => 'jkoma@komagin.com',
        'hire_page_cta_label' => 'Request Equipment Hire',
        'hire_page_cta_target' => 'contact',
        'hire_page_background_image' => 'images/hero-bg.jpeg'
    ] + partnerNdaTemplateDefaults();
}

function partnerNdaTemplateDefaults() {
    return [
        'partner_nda_document_title' => 'Non-Disclosure Agreement',
        'partner_nda_intro_text' => 'This Non-Disclosure Agreement is entered into on {{effective_date}} between {{komagin_company}} and {{partner_company}}. The parties may exchange technical, commercial, operational, financial, and project information while assessing or carrying out collaboration opportunities.',
        'partner_nda_purpose_text' => 'Confidential information may be shared strictly for partnership assessment, project coordination, service delivery preparation, due diligence, and any related business discussions approved by both parties.',
        'partner_nda_confidential_text' => 'Confidential information includes documents, drawings, designs, specifications, schedules, commercial terms, technical processes, pricing, client data, internal reports, and any other non-public information shared in written, digital, verbal, or visual form.',
        'partner_nda_obligations_text' => 'The receiving party must keep confidential information secure, restrict access to personnel with a legitimate need to know, avoid disclosure to unauthorized third parties, and use the information only for the agreed business purpose.',
        'partner_nda_exclusions_text' => 'Confidentiality obligations do not apply to information that is already publicly available, independently developed without access to the disclosed material, lawfully received from another source without restriction, or required to be disclosed by law or regulation.',
        'partner_nda_duration_text' => 'The confidentiality obligations begin on the effective date of this agreement and continue during discussions, project activity, and after the working relationship ends unless released in writing by the disclosing party.',
        'partner_nda_return_text' => 'Upon request, the receiving party must return, delete, or securely destroy confidential materials and any copies in its possession, except for records required to be retained by law or internal compliance obligations.',
        'partner_nda_additional_text' => 'This template may be used as the standard NDA for partner engagements unless a project-specific legal review requires additional clauses or revisions before signing.',
        'partner_nda_left_signatory' => '{{komagin_company}}',
        'partner_nda_right_signatory' => '{{partner_company}}',
        'partner_nda_left_footer' => 'Authorized Representative',
        'partner_nda_right_footer' => 'Date'
    ];
}

function repairDocumentMojibake($text) {
    $value = (string)$text;
    $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value);
    if ($value === '') return $value;

    for ($i = 0; $i < 2; $i++) {
        if (!preg_match('/(?:Ã.|â.|Å.|œ|ž|�)/u', $value)) break;
        $candidate = function_exists('mb_convert_encoding')
            ? @mb_convert_encoding($value, 'UTF-8', 'Windows-1252')
            : @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
        if (!is_string($candidate) || $candidate === '') break;
        if (substr_count($candidate, 'Ã') > substr_count($value, 'Ã')) break;
        $value = $candidate;
    }

    return trim($value);
}

function partnerNdaApplyTokens($text, array $partner, array $settings = []) {
    return strtr(repairDocumentMojibake((string)$text), [
        '{{partner_company}}' => repairDocumentMojibake(trim((string)($partner['company_name'] ?? 'Partner Company'))),
        '{{effective_date}}' => date('d M Y'),
        '{{komagin_company}}' => repairDocumentMojibake(trim((string)($settings['store_name'] ?? 'Komagin Limited')))
    ]);
}

function partnerNdaFormatText($text, array $partner, array $settings = []) {
    $resolved = trim(partnerNdaApplyTokens((string)$text, $partner, $settings));
    if ($resolved === '') return '';
    $paragraphs = preg_split("/(?:\r\n|\r|\n){2,}/", $resolved) ?: [];
    $html = [];
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim((string)$paragraph);
        if ($paragraph === '') continue;
        $html[] = '<p>' . nl2br(htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8')) . '</p>';
    }
    return implode('', $html);
}

function partnerNdaRenderSection($heading, $text, array $partner, array $settings = []) {
    $content = partnerNdaFormatText($text, $partner, $settings);
    if ($content === '') return '';
    return '<section class="nda-section"><h3>' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h3>' . $content . '</section>';
}

function buildPartnerNdaBody(array $partner) {
    $settings = getSettingsArray();
    $sections = [
        ['Agreement Overview', $settings['partner_nda_intro_text'] ?? ''],
        ['Purpose of Collaboration', $settings['partner_nda_purpose_text'] ?? ''],
        ['What Counts as Confidential Information', $settings['partner_nda_confidential_text'] ?? ''],
        ['Use and Protection Obligations', $settings['partner_nda_obligations_text'] ?? ''],
        ['Exclusions', $settings['partner_nda_exclusions_text'] ?? ''],
        ['Duration and Survival', $settings['partner_nda_duration_text'] ?? ''],
        ['Return or Destruction of Materials', $settings['partner_nda_return_text'] ?? ''],
        ['Additional Notes', $settings['partner_nda_additional_text'] ?? '']
    ];

    $sectionMarkup = implode('', array_map(static function ($section) use ($partner, $settings) {
        return partnerNdaRenderSection($section[0], $section[1], $partner, $settings);
    }, $sections));

    $leftSignatory = htmlspecialchars(partnerNdaApplyTokens($settings['partner_nda_left_signatory'] ?? '{{komagin_company}}', $partner, $settings), ENT_QUOTES, 'UTF-8');
    $rightSignatory = htmlspecialchars(partnerNdaApplyTokens($settings['partner_nda_right_signatory'] ?? '{{partner_company}}', $partner, $settings), ENT_QUOTES, 'UTF-8');
    $leftFooter = htmlspecialchars(partnerNdaApplyTokens($settings['partner_nda_left_footer'] ?? 'Authorized Representative', $partner, $settings), ENT_QUOTES, 'UTF-8');
    $rightFooter = htmlspecialchars(partnerNdaApplyTokens($settings['partner_nda_right_footer'] ?? 'Date', $partner, $settings), ENT_QUOTES, 'UTF-8');

    return $sectionMarkup . "<div class='signatures'><div class='sig'>{$leftSignatory}</div><div class='sig'>{$rightSignatory}</div></div><div class='signatures'><div class='sig'>{$leftFooter}</div><div class='sig'>{$rightFooter}</div></div>";
}

function normalizeSettingUrlValue($value) {
    $value = trim((string)$value);
    if ($value === '') return '';
    if (preg_match('#^(https?:|mailto:|tel:|/#|#)#i', $value)) return $value;
    return 'https://' . ltrim($value, '/');
}

function normalizeSettingPageTarget($value, $fallback = 'home') {
    $allowed = ['home', 'about', 'services', 'projects', 'contact', 'blogs', 'careers', 'partners', 'plant-hire'];
    $target = strtolower(trim((string)$value));
    return in_array($target, $allowed, true) ? $target : $fallback;
}

function normalizeSettingValueByKey($key, $value) {
    $key = (string)$key;
    if (is_array($value) || is_object($value)) return json_encode($value);

    $value = trim((string)$value);
    $urlKeys = ['facebook', 'youtube', 'youtube_url', 'linkedin', 'twitter', 'instagram', 'whatsapp_url', 'office_map_url'];
    $emailKeys = ['store_email', 'secondary_email', 'smtp_from_email', 'smtp_reply_to', 'smtp_test_recipient', 'hire_page_contact_email', 'hr_admin_email'];

    if (in_array($key, $urlKeys, true)) return normalizeSettingUrlValue($value);
    if (in_array($key, $emailKeys, true)) return strtolower($value);
    if ($key === 'hero_primary_target') return normalizeSettingPageTarget($value, 'projects');
    if ($key === 'hero_secondary_target') return normalizeSettingPageTarget($value, 'contact');
    if ($key === 'hire_page_cta_target') return normalizeSettingPageTarget($value, 'contact');
    if ($key === 'partner_portal') return $value === 'disabled' ? 'disabled' : 'enabled';

    return $value;
}

function saveSettingValues(array $data): void {
    foreach ($data as $key => $value) {
        if ($key === 'id' || $key === 'created_at') continue;
        $value = normalizeSettingValueByKey($key, $value);
        dbWrite(
            "INSERT INTO settings (setting_key, setting_value, updated_at, updated_by) VALUES (?, ?, NOW(), ?)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW(), updated_by=VALUES(updated_by)",
            [$key, is_numeric($value) ? (string)$value : trim((string)$value), $_SESSION['admin_username'] ?? 'Unknown']
        );
    }
}

function socialPlatformDefinitions(): array {
    return [
        'facebook' => [
            'display_name' => 'Facebook Page',
            'icon' => 'fa-facebook',
            'page_label' => 'Facebook Page ID',
            'page_placeholder' => 'Paste the Facebook Page ID used for publishing',
            'description' => 'Link the official Facebook page used for public updates and announcements.',
            'requirements' => ['access_token', 'page_id'],
            'verification_mode' => 'api'
        ],
        'instagram' => [
            'display_name' => 'Instagram Business',
            'icon' => 'fa-instagram',
            'page_label' => 'Instagram Business Account ID',
            'page_placeholder' => 'Paste the Instagram Business Account ID',
            'description' => 'Link the Instagram business account that shares website and company media updates.',
            'requirements' => ['access_token', 'page_id'],
            'verification_mode' => 'api'
        ],
        'twitter' => [
            'display_name' => 'X / Twitter',
            'icon' => 'fa-twitter',
            'page_label' => 'Account Handle or User ID',
            'page_placeholder' => 'Optional account handle or numeric user ID',
            'description' => 'Link the X / Twitter account used for short-form public updates.',
            'requirements' => ['access_token'],
            'verification_mode' => 'api'
        ],
        'linkedin' => [
            'display_name' => 'LinkedIn Page',
            'icon' => 'fa-linkedin',
            'page_label' => 'LinkedIn Organization ID',
            'page_placeholder' => 'Paste the LinkedIn organization ID',
            'description' => 'Link the LinkedIn organization page used for official company posts.',
            'requirements' => ['access_token'],
            'verification_mode' => 'api'
        ],
        'whatsapp' => [
            'display_name' => 'WhatsApp Channel',
            'icon' => 'fa-whatsapp',
            'page_label' => 'WhatsApp Phone Number ID',
            'page_placeholder' => 'Paste the WhatsApp Cloud API phone number ID',
            'description' => 'Prepare the WhatsApp channel for verified posting and delivery integration.',
            'requirements' => ['access_token', 'page_id'],
            'verification_mode' => 'api'
        ],
        'tiktok' => [
            'display_name' => 'TikTok Business',
            'icon' => 'fa-tiktok',
            'page_label' => 'TikTok Account ID',
            'page_placeholder' => 'Optional TikTok account ID',
            'description' => 'Store TikTok business credentials so the account is ready for final deployment integration.',
            'requirements' => ['access_token', 'api_key'],
            'verification_mode' => 'manual_review'
        ]
    ];
}

function ensureSocialIntegrationSchema(): void {
    $pdo = get_db();
    if (!komagin_table_exists($pdo, 'social_platforms')) return;
    try {
        if (!komagin_column_exists($pdo, 'social_platforms', 'verification_status')) {
            $pdo->exec("ALTER TABLE social_platforms ADD COLUMN verification_status VARCHAR(30) DEFAULT 'pending' AFTER expires_at");
        }
        if (!komagin_column_exists($pdo, 'social_platforms', 'verification_message')) {
            $pdo->exec("ALTER TABLE social_platforms ADD COLUMN verification_message TEXT DEFAULT NULL AFTER verification_status");
        }
        if (!komagin_column_exists($pdo, 'social_platforms', 'verification_checked_at')) {
            $pdo->exec("ALTER TABLE social_platforms ADD COLUMN verification_checked_at DATETIME DEFAULT NULL AFTER verification_message");
        }
    } catch (Throwable $e) {
        error_log('Social schema update failed: ' . $e->getMessage());
    }
}

function ensureSocialPlatformCatalog(): void {
    $defs = socialPlatformDefinitions();
    foreach ($defs as $platform => $definition) {
        dbWrite(
            "INSERT INTO social_platforms (id, platform, display_name, is_enabled, verification_status, created_at, updated_at)
             VALUES (?, ?, ?, 0, 'pending', NOW(), NOW())
             ON DUPLICATE KEY UPDATE display_name=VALUES(display_name), updated_at=NOW()",
            ['splat_' . $platform, $platform, $definition['display_name']]
        );
    }
}

function socialPlatformRequirements(string $platform): array {
    $defs = socialPlatformDefinitions();
    return $defs[$platform]['requirements'] ?? ['access_token'];
}

function socialPlatformCompleteness(string $platform, array $row): array {
    $requirements = socialPlatformRequirements($platform);
    $labels = [
        'access_token' => 'access token',
        'page_id' => 'page or account ID',
        'api_key' => 'client ID / API key',
        'api_secret' => 'client secret'
    ];
    $missing = [];
    foreach ($requirements as $field) {
        if (trim((string)($row[$field] ?? '')) === '') {
            $missing[] = $labels[$field] ?? $field;
        }
    }
    return [
        'required_fields' => $requirements,
        'missing_fields' => $missing,
        'is_complete' => empty($missing)
    ];
}

function socialVerificationLabel(string $status): string {
    $map = [
        'verified' => 'Verified',
        'failed' => 'Verification failed',
        'incomplete' => 'Configuration incomplete',
        'manual_review' => 'Manual review ready',
        'pending' => 'Pending verification',
        'disconnected' => 'Disconnected'
    ];
    return $map[$status] ?? 'Pending verification';
}

function socialPersistVerification(string $platform, string $status, string $message): void {
    dbWrite(
        "UPDATE social_platforms
         SET verification_status=?, verification_message=?, verification_checked_at=NOW(), updated_at=NOW()
         WHERE platform=?",
        [$status, $message, $platform]
    );
}

function socialBuildPlatformResponse(array $row): array {
    $defs = socialPlatformDefinitions();
    $definition = $defs[$row['platform']] ?? [
        'display_name' => ucfirst((string)$row['platform']),
        'icon' => 'fa-share-alt',
        'page_label' => 'Page / Account ID',
        'page_placeholder' => 'Enter the account identifier',
        'description' => '',
        'requirements' => ['access_token'],
        'verification_mode' => 'api'
    ];
    $completeness = socialPlatformCompleteness((string)$row['platform'], $row);
    $verificationStatus = trim((string)($row['verification_status'] ?? '')) ?: ((int)($row['is_enabled'] ?? 0) === 1 ? 'pending' : 'disconnected');
    $verificationMessage = trim((string)($row['verification_message'] ?? ''));
    if ($verificationMessage === '') {
        if (!$completeness['is_complete']) {
            $verificationMessage = 'Complete the missing credentials before running verification.';
        } elseif ((int)($row['is_enabled'] ?? 0) !== 1) {
            $verificationMessage = 'Configuration is saved but this platform is currently disabled.';
        } else {
            $verificationMessage = 'Run Verify to confirm the linked account is ready for publishing.';
        }
    }
    $postingReady = (int)($row['is_enabled'] ?? 0) === 1
        && $completeness['is_complete']
        && in_array($verificationStatus, ['verified', 'manual_review'], true);
    return array_merge($row, [
        'display_name' => $definition['display_name'],
        'icon' => $definition['icon'],
        'page_label' => $definition['page_label'],
        'page_placeholder' => $definition['page_placeholder'],
        'description' => $definition['description'],
        'requirements' => $definition['requirements'],
        'verification_mode' => $definition['verification_mode'],
        'required_fields' => $completeness['required_fields'],
        'missing_fields' => $completeness['missing_fields'],
        'is_complete' => $completeness['is_complete'],
        'verification_status' => $verificationStatus,
        'verification_label' => socialVerificationLabel($verificationStatus),
        'verification_message' => $verificationMessage,
        'posting_ready' => $postingReady,
        'posting_label' => $postingReady ? 'Ready for posting' : 'Not ready for posting'
    ]);
}

function printCompanyDocument($title, $body) {
    $settings = getSettingsArray();
    header_remove('Content-Type');
    header('Content-Type: text/html; charset=utf-8');
    $company = htmlspecialchars(repairDocumentMojibake($settings['store_name'] ?? 'Komagin Limited'));
    $address = htmlspecialchars(repairDocumentMojibake($settings['store_address'] ?? 'Port Moresby, Papua New Guinea'));
    $phone = htmlspecialchars(repairDocumentMojibake($settings['store_phone'] ?? '+675 1234 5678'));
    echo "<!doctype html><html><head><meta charset='utf-8'><title>" . htmlspecialchars(repairDocumentMojibake($title)) . "</title><style>
        body{font-family:Arial,sans-serif;color:#1A2632;margin:32px;line-height:1.5}.letterhead{display:flex;align-items:center;gap:16px;border-bottom:3px solid #1A3A5C;padding-bottom:16px;margin-bottom:24px}.letterhead img{width:72px;height:72px;object-fit:contain}.company h1{margin:0;color:#1A3A5C}.muted{color:#6C757D}.doc-title{text-align:center;margin:20px 0;color:#1A3A5C;letter-spacing:2px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.box{border:1px solid #E9ECEF;padding:14px;border-radius:6px;background:#F8F9FA}table{width:100%;border-collapse:collapse;margin:16px 0}th,td{border:1px solid #DDE2E6;padding:9px;text-align:left}th{background:#1A3A5C;color:#fff}.net{background:#E8A317;color:#111;padding:16px;font-size:20px;font-weight:bold;text-align:right;margin:18px 0}.nda-section{margin:18px 0}.nda-section h3{margin:0 0 8px;color:#1A3A5C;font-size:1rem}.nda-section p{margin:0 0 10px}.signatures{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:50px}.sig{border-top:1px solid #111;padding-top:8px}.progress{height:12px;background:#E9ECEF;border-radius:6px;overflow:hidden}.progress div{height:100%;background:#27AE60}@media print{body{margin:0}.no-print{display:none}}</style></head><body>
        <button class='no-print' onclick='window.print()' style='float:right;padding:10px 16px'>Print</button>
        <div class='letterhead'><img src='../images/logo.png' alt='Logo'><div class='company'><h1>{$company}</h1><div class='muted'>{$address}</div><div class='muted'>{$phone}</div></div></div>
        <h2 class='doc-title'>" . htmlspecialchars(repairDocumentMojibake($title)) . "</h2>{$body}</body></html>";
    exit;
}

function money($amount) {
    $settings = getSettingsArray();
    return htmlspecialchars(($settings['default_currency'] ?? 'PGK') . ' ' . number_format((float)$amount, 2));
}

function uploadScopedFile($field, $folder) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error'];
    }
    $file = $_FILES[$field];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeFolder = preg_replace('/[^a-z0-9_-]/i', '', $folder);
    $rules = [
        'staff' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_size' => 5 * 1024 * 1024,
            'label' => 'staff image'
        ],
        'assets' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_size' => 5 * 1024 * 1024,
            'label' => 'asset image'
        ],
        'receipts' => [
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'],
            'max_size' => 12 * 1024 * 1024,
            'label' => 'receipt or supporting document'
        ]
    ];
    $rule = $rules[$safeFolder] ?? [
        'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'],
        'max_size' => 12 * 1024 * 1024,
        'label' => 'file'
    ];
    if (!$extension || !in_array($extension, $rule['extensions'], true)) {
        return ['success' => false, 'error' => 'Invalid ' . $rule['label'] . ' type. Allowed: ' . strtoupper(implode(', ', $rule['extensions']))];
    }
    if (($file['size'] ?? 0) > $rule['max_size']) {
        return ['success' => false, 'error' => ucfirst($rule['label']) . ' must be ' . (int)($rule['max_size'] / (1024 * 1024)) . 'MB or smaller'];
    }
    $dir = UPLOADS_DIR . $safeFolder . '/';
    if (!file_exists($dir)) mkdir($dir, 0755, true);
    $filename = $safeFolder . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        logActivityDb('file_upload', $safeFolder . '/' . $filename);
        return [
            'success' => true,
            'file_path' => $safeFolder . '/' . $filename,
            'file_url' => ADMIN_URL . 'uploads/' . $safeFolder . '/' . $filename,
            'original_name' => $file['name']
        ];
    }
    return ['success' => false, 'error' => 'Failed to upload file'];
}

function generateEmployeeId() {
    $year = date('Y');
    $count = (int)dbScalar("SELECT COUNT(*) FROM staff WHERE employee_id LIKE ?", ["EMP-{$year}%"]) + 1;
    return 'EMP-' . $year . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
}

function generateAssetTag($category) {
    $code = strtoupper(substr(preg_replace('/[^a-z]/i', '', $category ?: 'EQP'), 0, 3));
    $count = (int)dbScalar("SELECT COUNT(*) FROM assets WHERE category = ?", [$category]) + 1;
    return 'KOM-' . $code . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
}

function hrGetStaff() {
    $db = requireDb(['staff']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $rows = dbAll("SELECT * FROM staff ORDER BY full_name");
    $stats = [
        'total' => count($rows),
        'active' => (int)dbScalar("SELECT COUNT(*) FROM staff WHERE status = 'active'"),
        'on_leave' => (int)dbScalar("SELECT COUNT(*) FROM staff WHERE status = 'on_leave'"),
        'new_month' => (int)dbScalar("SELECT COUNT(*) FROM staff WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")
    ];
    return ['success' => true, 'data' => $rows, 'stats' => $stats];
}

function hrSaveStaff() {
    $data = requestData();
    if (empty($data['full_name']) || empty($data['position'])) return ['success' => false, 'error' => 'Full name and position are required'];
    if (empty($data['employee_id'])) $data['employee_id'] = generateEmployeeId();
    return dbUpsert('staff', ['id','employee_id','full_name','email','phone','department','position','employment_type','status','date_hired','date_terminated','salary','bank_account','tax_file_number','emergency_contact_name','emergency_contact_phone','photo','notes','created_at','updated_at','created_by'], $data, 'staff');
}

function hrDeleteStaff() {
    $id = $_GET['id'] ?? '';
    if (!$id) return ['success' => false, 'error' => 'Staff ID required'];
    dbWrite("UPDATE staff SET status = 'terminated', date_terminated = COALESCE(date_terminated, CURDATE()), updated_at = NOW() WHERE id = ?", [$id]);
    logActivityDb('hr_delete_staff', $id);
    return ['success' => true, 'message' => 'Staff record marked as terminated'];
}

function hrGetLeave() {
    $db = requireDb(['leave_requests','staff']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    return ['success' => true, 'data' => dbAll("SELECT lr.*, s.full_name AS staff_name FROM leave_requests lr LEFT JOIN staff s ON s.id = lr.staff_id ORDER BY lr.created_at DESC")];
}

function hrSaveLeave() {
    $data = requestData();
    if (empty($data['staff_id']) || empty($data['leave_type']) || empty($data['start_date']) || empty($data['end_date'])) return ['success' => false, 'error' => 'Staff, leave type, start date, and end date are required'];
    $start = new DateTime($data['start_date']);
    $end = new DateTime($data['end_date']);
    $data['days_requested'] = max(1, $start->diff($end)->days + 1);
    return dbUpsert('leave_requests', ['id','staff_id','leave_type','start_date','end_date','days_requested','reason','status','approved_by','approved_at','notes','created_at'], $data, 'leave');
}

function hrSetLeaveStatus($status) {
    $id = $_GET['id'] ?? ($_POST['id'] ?? '');
    $notes = requestData()['notes'] ?? null;
    if (!$id) return ['success' => false, 'error' => 'Leave ID required'];
    dbWrite("UPDATE leave_requests SET status = ?, approved_by = ?, approved_at = NOW(), notes = COALESCE(?, notes) WHERE id = ?", [$status, currentUsername(), $notes, $id]);
    logActivityDb('hr_leave_' . $status, $id);
    return ['success' => true, 'message' => 'Leave request ' . $status];
}

function hrGetPayroll() {
    $db = requireDb(['payroll','staff']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $period = $_GET['period'] ?? date('Y-m');
    return ['success' => true, 'data' => dbAll("SELECT p.*, s.full_name AS staff_name, s.employee_id FROM payroll p LEFT JOIN staff s ON s.id = p.staff_id WHERE DATE_FORMAT(p.period_start, '%Y-%m') = ? ORDER BY s.full_name", [$period])];
}

function hrGeneratePayroll() {
    $db = requireDb(['payroll','staff']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $period = $_GET['period'] ?? (requestData()['period'] ?? date('Y-m'));
    $start = $period . '-01';
    $end = date('Y-m-t', strtotime($start));
    $created = 0;
    foreach (dbAll("SELECT * FROM staff WHERE status = 'active'") as $staff) {
        $exists = dbScalar("SELECT COUNT(*) FROM payroll WHERE staff_id = ? AND period_start = ?", [$staff['id'], $start]);
        if (!$exists) {
            $salary = (float)($staff['salary'] ?? 0);
            dbWrite("INSERT INTO payroll (id, staff_id, period_start, period_end, base_salary, allowances, deductions, tax, net_pay, payment_status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())", ['pay_' . uniqid(), $staff['id'], $start, $end, $salary, 0, 0, 0, $salary, 'pending']);
            $created++;
        }
    }
    logActivityDb('hr_generate_payroll', $period);
    return ['success' => true, 'message' => "Payroll generated for {$created} staff"];
}

function hrSavePayrollRecord() {
    $data = requestData();
    $data['net_pay'] = (float)($data['base_salary'] ?? 0) + (float)($data['allowances'] ?? 0) - (float)($data['deductions'] ?? 0) - (float)($data['tax'] ?? 0);
    return dbUpsert('payroll', ['id','staff_id','period_start','period_end','base_salary','allowances','deductions','tax','net_pay','payment_method','payment_status','processed_at','processed_by','notes','created_at'], $data, 'pay');
}

function hrProcessPayroll() {
    $period = $_GET['period'] ?? (requestData()['period'] ?? date('Y-m'));
    dbWrite("UPDATE payroll SET payment_status = 'processed', processed_by = ?, processed_at = NOW() WHERE DATE_FORMAT(period_start, '%Y-%m') = ?", [currentUsername(), $period]);
    logActivityDb('hr_process_payroll', $period);
    return ['success' => true, 'message' => 'Payroll processed'];
}

function hrGeneratePayslip() {
    if (!requireRoles(['admin','hr_admin'])) return ['success' => false, 'error' => 'Access denied'];
    $staffId = $_GET['staff_id'] ?? '';
    $period = $_GET['period'] ?? date('Y-m');
    $staff = dbOne("SELECT * FROM staff WHERE id = ?", [$staffId]);
    if (!$staff) printCompanyDocument('Payslip', '<p>Staff record not found.</p>');
    $payroll = dbOne("SELECT * FROM payroll WHERE staff_id = ? AND DATE_FORMAT(period_start, '%Y-%m') = ? ORDER BY created_at DESC LIMIT 1", [$staffId, $period]);
    if (!$payroll) {
        $salary = (float)($staff['salary'] ?? 0);
        $payroll = ['base_salary' => $salary, 'allowances' => 0, 'deductions' => 0, 'tax' => 0, 'net_pay' => $salary, 'payment_status' => 'draft'];
    }
    $gross = (float)$payroll['base_salary'] + (float)$payroll['allowances'];
    $deductions = (float)$payroll['deductions'] + (float)$payroll['tax'];
    $body = "<div class='grid'><div class='box'><strong>Employee:</strong> " . htmlspecialchars($staff['full_name']) . "<br><strong>Employee ID:</strong> " . htmlspecialchars($staff['employee_id']) . "<br><strong>Position:</strong> " . htmlspecialchars($staff['position']) . "<br><strong>Department:</strong> " . htmlspecialchars($staff['department'] ?? '') . "</div><div class='box'><strong>Period:</strong> " . htmlspecialchars($period) . "<br><strong>Generated:</strong> " . date('d M Y') . "<br><strong>Bank Account:</strong> " . htmlspecialchars($staff['bank_account'] ?? '') . "</div></div><table><thead><tr><th>Earnings</th><th>Amount</th></tr></thead><tbody><tr><td>Base Salary</td><td>" . money($payroll['base_salary']) . "</td></tr><tr><td>Allowances</td><td>" . money($payroll['allowances']) . "</td></tr><tr><th>Gross Total</th><th>" . money($gross) . "</th></tr></tbody></table><table><thead><tr><th>Deductions</th><th>Amount</th></tr></thead><tbody><tr><td>Tax</td><td>" . money($payroll['tax']) . "</td></tr><tr><td>Other Deductions</td><td>" . money($payroll['deductions']) . "</td></tr><tr><th>Total Deductions</th><th>" . money($deductions) . "</th></tr></tbody></table><div class='net'>Net Pay: " . money($payroll['net_pay']) . "</div><div class='signatures'><div class='sig'>Authorised by</div><div class='sig'>Employee Acknowledgement</div></div>";
    logActivityDb('hr_generate_payslip', $staffId . ' ' . $period);
    printCompanyDocument('PAYSLIP', $body);
}

function hrGenerateLetter() {
    if (!requireRoles(['admin','hr_admin'])) return ['success' => false, 'error' => 'Access denied'];
    $staff = dbOne("SELECT * FROM staff WHERE id = ?", [$_GET['staff_id'] ?? '']);
    if (!$staff) printCompanyDocument('Employment Letter', '<p>Staff record not found.</p>');
    $type = $_GET['letter_type'] ?? 'confirmation';
    $title = ucwords(str_replace('_', ' ', $type)) . ' Letter';
    $body = "<p>" . date('d M Y') . "</p><p>Dear " . htmlspecialchars($staff['full_name']) . ",</p><p>This letter confirms your employment relationship with Komagin Limited in the role of <strong>" . htmlspecialchars($staff['position']) . "</strong> within the " . htmlspecialchars($staff['department'] ?? 'company') . " department.</p><p>Your employment status is recorded as <strong>" . htmlspecialchars($staff['status']) . "</strong>. Please retain this letter for your records.</p><p>Regards,<br><strong>Komagin Limited Management</strong></p><div class='signatures'><div class='sig'>Authorised Signature</div><div class='sig'>Employee Signature</div></div>";
    logActivityDb('hr_generate_letter', $staff['id'] . ' ' . $type);
    printCompanyDocument($title, $body);
}

function hrGetJobs() {
    ensureJobListingsSchema();
    $status = $_GET['status'] ?? '';
    if ($status !== 'published' && !isAuthenticated()) {
        http_response_code(401);
        return ['success' => false, 'error' => 'Authentication required'];
    }
    $db = requireDb(['job_listings']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $params = [];
    $where = '';
    if ($status) {
        $where = 'WHERE status = ?';
        $params[] = $status;
    }
    $rows = dbAll("SELECT * FROM job_listings {$where} ORDER BY created_at DESC", $params);
    $isPublicFeed = ($status === 'published');
    return ['success' => true, 'data' => array_map(static function ($row) use ($isPublicFeed) {
        return normalizeJobListingRow($row, $isPublicFeed);
    }, $rows)];
}

function hrSaveJob() {
    ensureJobListingsSchema();
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $data = requestData();
    if (empty($data['title']) || empty($data['description'])) return ['success' => false, 'error' => 'Title and description are required'];
    $allowedTypes = ['full_time','part_time','contract','casual','internship'];
    $allowedStatuses = ['draft','published','closed'];
    $data['type'] = in_array((string)($data['type'] ?? 'full_time'), $allowedTypes, true) ? $data['type'] : 'full_time';
    $data['status'] = in_array((string)($data['status'] ?? 'draft'), $allowedStatuses, true) ? $data['status'] : 'draft';
    $data['salary_range'] = trim((string)($data['salary_range'] ?? ''));
    $data['show_salary_range'] = !empty($data['show_salary_range']) ? 1 : 0;
    if (empty($data['salary_range'])) $data['show_salary_range'] = 0;
    if (isset($data['closing_date']) && trim((string)$data['closing_date']) === '') $data['closing_date'] = null;
    $result = dbUpsert('job_listings', ['id','title','department','location','type','description','requirements','salary_range','show_salary_range','closing_date','status','applications_count','created_at','updated_at','created_by'], $data, 'job');
    if (!empty($result['success'])) {
        exportPublishedJobsCache();
        $result['message'] = 'Vacancy saved';
        if (!empty($result['data']) && is_array($result['data'])) {
            $result['data'] = normalizeJobListingRow($result['data'], false);
        }
    }
    return $result;
}

function hrDeleteJob() {
    ensureJobListingsSchema();
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $id = $_GET['id'] ?? '';
    dbWrite("DELETE FROM job_listings WHERE id = ?", [$id]);
    exportPublishedJobsCache();
    logActivityDb('hr_delete_job', $id);
    return ['success' => true, 'message' => 'Job listing deleted'];
}

function hrToggleJobStatus() {
    ensureJobListingsSchema();
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $id = $_GET['id'] ?? '';
    $job = dbOne("SELECT status FROM job_listings WHERE id = ?", [$id]);
    $next = (($job['status'] ?? '') === 'published') ? 'closed' : 'published';
    dbWrite("UPDATE job_listings SET status = ?, updated_at = NOW() WHERE id = ?", [$next, $id]);
    exportPublishedJobsCache();
    logActivityDb('hr_toggle_job_status', $id . ' ' . $next);
    return ['success' => true, 'message' => 'Job status updated', 'status' => $next];
}

function ensureJobListingsSchema() {
    try {
        dbWrite("CREATE TABLE IF NOT EXISTS job_listings (
            id VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            department VARCHAR(100) DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            type VARCHAR(50) DEFAULT 'full_time',
            description TEXT NOT NULL,
            requirements TEXT DEFAULT NULL,
            salary_range VARCHAR(100) DEFAULT NULL,
            show_salary_range TINYINT(1) DEFAULT 0,
            closing_date DATE DEFAULT NULL,
            status ENUM('draft','published','closed') DEFAULT 'draft',
            applications_count INT DEFAULT 0,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            created_by VARCHAR(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_job_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if (dbColumnExists('job_listings', 'employment_type') && !dbColumnExists('job_listings', 'type')) {
            dbWrite("ALTER TABLE job_listings ADD COLUMN type VARCHAR(50) DEFAULT 'full_time' AFTER location");
            dbWrite("UPDATE job_listings SET type = CASE
                WHEN employment_type IN ('full-time','full_time') THEN 'full_time'
                WHEN employment_type IN ('part-time','part_time') THEN 'part_time'
                WHEN employment_type = 'contract' THEN 'contract'
                WHEN employment_type = 'casual' THEN 'casual'
                WHEN employment_type = 'internship' THEN 'internship'
                ELSE 'full_time'
            END
            WHERE type IS NULL OR type = ''");
        }
        if (!dbColumnExists('job_listings', 'show_salary_range')) {
            dbWrite("ALTER TABLE job_listings ADD COLUMN show_salary_range TINYINT(1) DEFAULT 0 AFTER salary_range");
        }
        if (dbColumnExists('job_listings', 'type')) {
            dbWrite("ALTER TABLE job_listings MODIFY COLUMN type VARCHAR(50) DEFAULT 'full_time'");
            dbWrite("UPDATE job_listings SET type = 'full_time' WHERE type IS NULL OR TRIM(type) = ''");
        }
        if (dbColumnExists('job_listings', 'show_salary_range')) {
            dbWrite("UPDATE job_listings SET show_salary_range = 0 WHERE show_salary_range IS NULL");
        }
    } catch (Throwable $e) {
        // Careers schema repair must not break page rendering.
    }
}

function normalizeJobListingRow($row, $public = false) {
    if (!$row) return $row;
    $type = trim((string)($row['type'] ?? $row['employment_type'] ?? 'full_time'));
    $row['type'] = $type !== '' ? $type : 'full_time';
    $row['show_salary_range'] = (int)($row['show_salary_range'] ?? 0);
    $row['salary_range'] = trim((string)($row['salary_range'] ?? ''));
    if ($public && $row['show_salary_range'] !== 1) {
        $row['salary_range'] = '';
    }
    unset($row['employment_type']);
    return $row;
}

function exportPublishedJobsCache() {
    try {
        if (!dbTableExists('job_listings')) return;
        $rows = dbAll("SELECT * FROM job_listings WHERE status = 'published' ORDER BY created_at DESC");
        $payload = [
            'success' => true,
            'data' => array_map(static function ($row) {
                return normalizeJobListingRow($row, true);
            }, $rows),
            'generated_at' => date('c')
        ];
        $cacheDir = __DIR__ . '/cache';
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
        @file_put_contents($cacheDir . '/jobs-published.json', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    } catch (Throwable $e) {
        // Cache export is optional and must not block admin actions.
    }
}

function ensureJobApplicationsSchema() {
    try {
        dbWrite("CREATE TABLE IF NOT EXISTS job_applications (
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

        if (!dbColumnExists('job_applications', 'cv_file') && dbColumnExists('job_applications', 'cv_path')) {
            dbWrite("ALTER TABLE job_applications ADD COLUMN cv_file VARCHAR(500) DEFAULT NULL AFTER cover_letter");
            dbWrite("UPDATE job_applications SET cv_file = cv_path WHERE cv_file IS NULL OR cv_file = ''");
        }
        if (!dbColumnExists('job_applications', 'document_bundle_name')) {
            dbWrite("ALTER TABLE job_applications ADD COLUMN document_bundle_name VARCHAR(255) DEFAULT NULL AFTER cv_file");
        }
        if (!dbColumnExists('job_applications', 'document_manifest')) {
            dbWrite("ALTER TABLE job_applications ADD COLUMN document_manifest LONGTEXT DEFAULT NULL AFTER document_bundle_name");
        }
        if (!dbColumnExists('job_applications', 'document_extract_dir')) {
            dbWrite("ALTER TABLE job_applications ADD COLUMN document_extract_dir VARCHAR(500) DEFAULT NULL AFTER document_manifest");
        }
        if (!dbColumnExists('job_applications', 'updated_at')) {
            dbWrite("ALTER TABLE job_applications ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER created_at");
        }
        if (dbColumnExists('job_applications', 'status')) {
            dbWrite("ALTER TABLE job_applications MODIFY COLUMN status VARCHAR(50) DEFAULT 'received'");
            dbWrite("UPDATE job_applications SET status = CASE
                WHEN status IS NULL OR TRIM(status) = '' THEN 'received'
                WHEN status = 'new' THEN 'received'
                WHEN status = 'reviewed' THEN 'received'
                WHEN status = 'interviewed' THEN 'interview'
                WHEN status = 'offered' THEN 'hired'
                ELSE status
            END");
        }
        if (dbColumnExists('job_applications', 'document_bundle_name')) {
            dbWrite("UPDATE job_applications SET document_bundle_name = SUBSTRING_INDEX(REPLACE(cv_file, '\\\\', '/'), '/', -1)
                WHERE (document_bundle_name IS NULL OR TRIM(document_bundle_name) = '')
                AND cv_file IS NOT NULL AND TRIM(cv_file) <> ''");
        }
    } catch (Throwable $e) {
        // Application schema repair must not break otherwise working requests.
    }
}

function normalizeJobApplicationStatus($status) {
    $value = strtolower(trim((string)$status));
    if ($value === '' || $value === 'new' || $value === 'reviewed') return 'received';
    if ($value === 'interviewed') return 'interview';
    if ($value === 'offered') return 'hired';
    $allowed = ['received', 'shortlisted', 'interview', 'hired', 'rejected', 'withdrawn'];
    return in_array($value, $allowed, true) ? $value : 'received';
}

function normalizeApplicationRelativePath($path) {
    $normalized = str_replace('\\', '/', trim((string)$path));
    return ltrim($normalized, '/');
}

function applicationUploadUrl($relativePath) {
    $normalized = normalizeApplicationRelativePath($relativePath);
    if ($normalized === '') return null;
    return ADMIN_URL . 'uploads/' . $normalized;
}

function applicationUploadAbsolutePath($relativePath) {
    $normalized = normalizeApplicationRelativePath($relativePath);
    if ($normalized === '') return null;
    return UPLOADS_DIR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
}

function decodeApplicationDocumentManifest($rawManifest) {
    if (!is_string($rawManifest) || trim($rawManifest) === '') return [];
    $decoded = json_decode($rawManifest, true);
    return is_array($decoded) ? $decoded : [];
}

function scanApplicationExtractedDocuments($relativeDir) {
    $normalizedDir = normalizeApplicationRelativePath($relativeDir);
    $absoluteDir = applicationUploadAbsolutePath($normalizedDir);
    if ($normalizedDir === '' || !$absoluteDir || !is_dir($absoluteDir)) return [];
    $documents = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absoluteDir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $absolutePath = $file->getPathname();
        $relativePath = normalizeApplicationRelativePath(substr($absolutePath, strlen(UPLOADS_DIR)));
        $folderPath = normalizeApplicationRelativePath(substr(dirname($absolutePath), strlen($absoluteDir)));
        $documents[] = [
            'name' => basename($absolutePath),
            'relative_path' => $relativePath,
            'folder' => $folderPath === '.' ? '' : $folderPath,
            'extension' => strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION)),
            'size' => @filesize($absolutePath) ?: 0,
            'url' => applicationUploadUrl($relativePath)
        ];
    }
    usort($documents, static function ($a, $b) {
        return strcasecmp(($a['folder'] ?? '') . '/' . ($a['name'] ?? ''), ($b['folder'] ?? '') . '/' . ($b['name'] ?? ''));
    });
    return $documents;
}

function getApplicationDocumentsForRow(array $row) {
    $documents = decodeApplicationDocumentManifest($row['document_manifest'] ?? '');
    if (!$documents) {
        $documents = scanApplicationExtractedDocuments($row['document_extract_dir'] ?? '');
    }
    return array_values(array_filter(array_map(static function ($document) {
        if (!is_array($document)) return null;
        $relativePath = normalizeApplicationRelativePath($document['relative_path'] ?? '');
        return [
            'name' => trim((string)($document['name'] ?? basename($relativePath))),
            'relative_path' => $relativePath,
            'folder' => normalizeApplicationRelativePath($document['folder'] ?? ''),
            'extension' => strtolower(trim((string)($document['extension'] ?? pathinfo($relativePath, PATHINFO_EXTENSION)))),
            'size' => (int)($document['size'] ?? 0),
            'url' => $document['url'] ?? applicationUploadUrl($relativePath)
        ];
    }, $documents), static function ($document) {
        return is_array($document) && !empty($document['relative_path']) && !empty($document['url']);
    }));
}

function normalizeJobApplicationRow(array $row, $includeDocuments = false) {
    $bundlePath = normalizeApplicationRelativePath($row['cv_file'] ?? $row['cv_path'] ?? '');
    $bundleName = trim((string)($row['document_bundle_name'] ?? basename($bundlePath)));
    $row['status'] = normalizeJobApplicationStatus($row['status'] ?? '');
    $row['cover_note'] = trim((string)($row['cover_letter'] ?? ''));
    $row['cover_note_word_count'] = $row['cover_note'] === '' ? 0 : count(array_values(array_filter(preg_split('/\s+/', $row['cover_note']))));
    $row['bundle_path'] = $bundlePath;
    $row['bundle_name'] = $bundleName !== '' ? $bundleName : ($bundlePath !== '' ? basename($bundlePath) : '');
    $row['bundle_url'] = applicationUploadUrl($bundlePath);
    $row['bundle_is_zip'] = (bool)preg_match('/\.zip$/i', $row['bundle_name']);
    $row['document_extract_dir'] = normalizeApplicationRelativePath($row['document_extract_dir'] ?? '');
    $documents = getApplicationDocumentsForRow($row);
    $row['documents_count'] = count($documents);
    if ($includeDocuments) {
        $row['documents'] = $documents;
    }
    unset($row['cv_path'], $row['cv_name']);
    return $row;
}

function hrGetApplications() {
    ensureJobApplicationsSchema();
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $db = requireDb(['job_applications', 'job_listings']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $status = $_GET['status'] ?? 'all';
    $allowed = ['all', 'received', 'shortlisted', 'interview', 'hired', 'rejected'];
    if (!in_array($status, $allowed, true)) $status = 'all';
    $sql = "SELECT a.*, j.title AS job_title, j.department AS job_department, j.location AS job_location FROM job_applications a LEFT JOIN job_listings j ON j.id = a.job_id";
    $params = [];
    if ($status !== 'all') {
        $sql .= " WHERE a.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY a.created_at DESC";
    return ['success' => true, 'data' => array_map(static function ($row) {
        return normalizeJobApplicationRow($row, false);
    }, dbAll($sql, $params))];
}

function hrGetApplicationDetail() {
    ensureJobApplicationsSchema();
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $id = trim((string)($_GET['id'] ?? ''));
    if ($id === '') return ['success' => false, 'error' => 'Application reference required'];
    $db = requireDb(['job_applications', 'job_listings']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $row = dbOne(
        "SELECT a.*, j.title AS job_title, j.department AS job_department, j.location AS job_location
         FROM job_applications a
         LEFT JOIN job_listings j ON j.id = a.job_id
         WHERE a.id = ?",
        [$id]
    );
    if (!$row) return ['success' => false, 'error' => 'Application not found'];
    return ['success' => true, 'data' => normalizeJobApplicationRow($row, true)];
}

function hrUpdateApplicationStatus() {
    ensureJobApplicationsSchema();
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $data = requestData();
    $id = trim($data['id'] ?? '');
    $status = normalizeJobApplicationStatus($data['status'] ?? '');
    if ($id === '' || $status === '') return ['success' => false, 'error' => 'Application and status are required'];
    $allowed = ['received', 'shortlisted', 'interview', 'hired', 'rejected', 'withdrawn'];
    if (!in_array($status, $allowed, true)) return ['success' => false, 'error' => 'Invalid application status'];
    $db = requireDb(['job_applications']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    dbWrite(
        "UPDATE job_applications SET status = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ?",
        [$status, currentUsername(), $id]
    );
    logActivityDb('job_application_status', $id . ' ' . $status);
    return ['success' => true, 'message' => 'Application updated'];
}

function hrGenerateInterviewInvite() {
    if (!requireRoles(['admin'])) {
        printCompanyDocument('Interview Invitation', '<p>Access denied.</p>');
    }
    $db = requireDb(['job_applications', 'job_listings']);
    if (is_array($db)) {
        printCompanyDocument('Interview Invitation', '<p>' . htmlspecialchars($db['error']) . '</p>');
    }
    $app = dbOne(
        "SELECT a.*, j.title AS job_title FROM job_applications a LEFT JOIN job_listings j ON j.id = a.job_id WHERE a.id = ?",
        [$_GET['id'] ?? '']
    );
    if (!$app) {
        printCompanyDocument('Interview Invitation', '<p>Application not found.</p>');
    }
    $body = "<p>Date: " . date('d F Y') . "</p>"
        . "<p>Dear <strong>" . htmlspecialchars($app['applicant_name'] ?? 'Applicant') . "</strong>,</p>"
        . "<p>We are pleased to advise that your application for the role of <strong>" . htmlspecialchars($app['job_title'] ?? 'the advertised vacancy') . "</strong> has progressed to the interview stage.</p>"
        . "<p><strong>Interview Details</strong><br>Date &amp; Time: ____________________<br>Venue: ____________________<br>Interviewer: ____________________</p>"
        . "<p>Please confirm your availability and bring all relevant qualifications and identification to the interview.</p>"
        . "<p>We look forward to meeting you.</p><p>Regards,<br><strong>Komagin Limited Careers Team</strong></p>";
    logActivityDb('job_application_invite', $app['id']);
    printCompanyDocument('Interview Invitation', $body);
}

function assetsGetAll() {
    $db = requireDb(['assets']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $rows = dbAll("SELECT a.*, s.full_name AS assigned_staff_name, b.name AS assigned_branch_name FROM assets a LEFT JOIN staff s ON s.id = a.assigned_to_staff_id LEFT JOIN branches b ON b.id = a.assigned_to_branch ORDER BY a.created_at DESC");
    $stats = [
        'total' => count($rows),
        'available' => (int)dbScalar("SELECT COUNT(*) FROM assets WHERE status='available'"),
        'assigned' => (int)dbScalar("SELECT COUNT(*) FROM assets WHERE status='assigned'"),
        'maintenance' => (int)dbScalar("SELECT COUNT(*) FROM assets WHERE status='maintenance'"),
        'disposed' => (int)dbScalar("SELECT COUNT(*) FROM assets WHERE status='disposed'")
    ];
    return ['success' => true, 'data' => $rows, 'stats' => $stats];
}

function assetsSave() {
    $data = requestData();
    if (empty($data['name'])) return ['success' => false, 'error' => 'Asset name is required'];
    if (empty($data['asset_tag'])) $data['asset_tag'] = generateAssetTag($data['category'] ?? 'equipment');
    $result = dbUpsert('assets', ['id','asset_tag','name','category','description','serial_number','purchase_date','purchase_cost','current_value','supplier','warranty_expiry','condition','status','assigned_to_staff_id','assigned_to_branch','assigned_date','location','photo','notes','created_at','updated_at','created_by'], $data, 'asset');
    if (!empty($result['success']) && !empty($data['assigned_to_branch'])) {
        $assetId = $result['data']['id'] ?? ($data['id'] ?? '');
        syncAssetToBranch($assetId, $data['assigned_to_branch'], $data);
    }
    return $result;
}

function assetsDelete() {
    $id = $_GET['id'] ?? '';
    $asset = dbOne("SELECT status FROM assets WHERE id = ?", [$id]);
    if (($asset['status'] ?? '') !== 'available') return ['success' => false, 'error' => 'Only available assets can be deleted'];
    dbWrite("DELETE FROM assets WHERE id = ?", [$id]);
    logActivityDb('assets_delete', $id);
    return ['success' => true, 'message' => 'Asset deleted'];
}

function assetsAssign() {
    $data = requestData();
    $id = $data['asset_id'] ?? ($_GET['id'] ?? '');
    dbWrite("UPDATE assets SET status='assigned', assigned_to_staff_id=?, assigned_to_branch=?, assigned_date=?, notes=COALESCE(?, notes), updated_at=NOW() WHERE id=?", [$data['assigned_to_staff_id'] ?? null, $data['assigned_to_branch'] ?? null, $data['assigned_date'] ?? date('Y-m-d'), $data['notes'] ?? null, $id]);
    if (!empty($data['assigned_to_branch'])) syncAssetToBranch($id, $data['assigned_to_branch'], $data);
    logActivityDb('assets_assign', $id);
    return ['success' => true, 'message' => 'Asset assigned'];
}

function assetsReturn() {
    $id = $_GET['id'] ?? (requestData()['asset_id'] ?? '');
    dbWrite("UPDATE assets SET status='available', assigned_to_staff_id=NULL, assigned_to_branch=NULL, assigned_date=NULL, updated_at=NOW() WHERE id=?", [$id]);
    logActivityDb('assets_return', $id);
    return ['success' => true, 'message' => 'Asset returned'];
}

function assetsGenerateAssignment() {
    $asset = dbOne("SELECT a.*, s.full_name AS staff_name FROM assets a LEFT JOIN staff s ON s.id = a.assigned_to_staff_id WHERE a.id = ?", [$_GET['asset_id'] ?? '']);
    if (!$asset) printCompanyDocument('Asset Assignment Form', '<p>Asset not found.</p>');
    $assignee = $asset['staff_name'] ?: ($asset['assigned_to_branch'] ?: 'Unassigned');
    $body = "<div class='box'><strong>Asset Tag:</strong> " . htmlspecialchars($asset['asset_tag']) . "<br><strong>Name:</strong> " . htmlspecialchars($asset['name']) . "<br><strong>Category:</strong> " . htmlspecialchars($asset['category']) . "<br><strong>Condition:</strong> " . htmlspecialchars($asset['condition']) . "<br><strong>Assigned To:</strong> " . htmlspecialchars($assignee) . "<br><strong>Assignment Date:</strong> " . htmlspecialchars($asset['assigned_date'] ?? date('Y-m-d')) . "</div><div class='signatures'><div class='sig'>Komagin Representative</div><div class='sig'>Assignee</div></div>";
    logActivityDb('assets_generate_assignment', $asset['id']);
    printCompanyDocument('Asset Assignment Form', $body);
}

function maintenanceGetAll() {
    $db = requireDb(['asset_maintenance','assets']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    return ['success' => true, 'data' => dbAll("SELECT m.*, a.asset_tag, a.name AS asset_name FROM asset_maintenance m LEFT JOIN assets a ON a.id = m.asset_id ORDER BY m.scheduled_date ASC")];
}

function maintenanceSave() {
    $data = requestData();
    if (empty($data['asset_id']) || empty($data['maintenance_type']) || empty($data['description'])) return ['success' => false, 'error' => 'Asset, type, and description are required'];
    return dbUpsert('asset_maintenance', ['id','asset_id','maintenance_type','description','cost','performed_by','scheduled_date','completed_date','status','next_maintenance_date','notes','created_at','created_by'], $data, 'maint');
}

function maintenanceComplete() {
    $id = $_GET['id'] ?? (requestData()['id'] ?? '');
    dbWrite("UPDATE asset_maintenance SET status='completed', completed_date=CURDATE() WHERE id=?", [$id]);
    logActivityDb('maintenance_complete', $id);
    return ['success' => true, 'message' => 'Maintenance completed'];
}

function filesGetCategories() {
    ensureFileCategories();
    $db = requireDb(['file_categories']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $sql = "SELECT fc.*,
                   COUNT(mf.id) AS file_count
            FROM file_categories fc
            LEFT JOIN managed_files mf ON mf.category_id = fc.id
            %s
            GROUP BY fc.id
            ORDER BY fc.name";
    if (currentUserRole() === 'hr_admin') {
        $rows = dbAll(sprintf($sql, "WHERE fc.access_role IN ('hr_admin','public')"));
    } else {
        $rows = dbAll(sprintf($sql, ""));
    }
    $rows = array_map(function ($row) {
        $row['file_count'] = (int)($row['file_count'] ?? 0);
        $row['is_system_folder'] = (int)($row['is_system_folder'] ?? 0);
        $row['can_delete'] = $row['is_system_folder'] === 1 ? 0 : 1;
        $row['can_edit'] = $row['is_system_folder'] === 1 ? 0 : 1;
        return $row;
    }, $rows);
    return ['success' => true, 'data' => $rows];
}

function sanitizeManagedFolderSlug($value) {
    return strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', (string)$value), '-'));
}

function filesCreateCategory() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    ensureFileCategories();
    $db = requireDb(['file_categories']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $data = requestData();
    $name = trim((string)($data['name'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $accessRole = trim((string)($data['access_role'] ?? 'admin'));
    if ($name === '') return ['success' => false, 'error' => 'Folder name is required'];
    if (!in_array($accessRole, ['admin', 'public', 'hr_admin'], true)) {
        $accessRole = 'admin';
    }
    $slugInput = trim((string)($data['slug'] ?? ''));
    $slugBase = $slugInput !== '' ? $slugInput : $name;
    $slug = sanitizeManagedFolderSlug($slugBase);
    if ($slug === '') return ['success' => false, 'error' => 'Folder slug could not be generated'];
    $existing = dbOne("SELECT id FROM file_categories WHERE slug=?", [$slug]);
    if ($existing) return ['success' => false, 'error' => 'A folder with this slug already exists'];
    $id = 'cat_custom_' . uniqid();
    dbWrite(
        "INSERT INTO file_categories (id, name, slug, access_role, description, is_system_folder, created_at) VALUES (?,?,?,?,?,0,NOW())",
        [$id, $name, $slug, $accessRole, $description]
    );
    $dir = UPLOADS_DIR . 'managed/' . preg_replace('/[^a-z0-9_-]/i', '', $slug) . '/';
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    logActivityDb('files_create_category', $name);
    return ['success' => true, 'message' => 'Folder created', 'data' => dbOne("SELECT * FROM file_categories WHERE id=?", [$id])];
}

function filesUpdateCategory() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    ensureFileCategories();
    $db = requireDb(['file_categories', 'managed_files']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $data = requestData();
    $id = trim((string)($data['id'] ?? ''));
    if ($id === '') return ['success' => false, 'error' => 'Folder not specified'];
    $category = dbOne("SELECT * FROM file_categories WHERE id=?", [$id]);
    if (!$category) return ['success' => false, 'error' => 'Folder not found'];
    if ((int)($category['is_system_folder'] ?? 0) === 1) {
        return ['success' => false, 'error' => 'System media folders are protected'];
    }
    $name = trim((string)($data['name'] ?? $category['name']));
    $description = trim((string)($data['description'] ?? $category['description']));
    $accessRole = trim((string)($data['access_role'] ?? $category['access_role'] ?? 'admin'));
    if ($name === '') return ['success' => false, 'error' => 'Folder name is required'];
    if (!in_array($accessRole, ['admin', 'public', 'hr_admin'], true)) {
        $accessRole = 'admin';
    }
    $slugInput = trim((string)($data['slug'] ?? $category['slug']));
    $slugBase = $slugInput !== '' ? $slugInput : $name;
    $newSlug = sanitizeManagedFolderSlug($slugBase);
    if ($newSlug === '') return ['success' => false, 'error' => 'Folder slug could not be generated'];
    $existing = dbOne("SELECT id FROM file_categories WHERE slug=? AND id<>?", [$newSlug, $id]);
    if ($existing) return ['success' => false, 'error' => 'A folder with this slug already exists'];

    $oldSlug = sanitizeManagedFolderSlug($category['slug'] ?? '');
    if ($oldSlug !== '' && $newSlug !== $oldSlug) {
        $oldDir = UPLOADS_DIR . 'managed/' . preg_replace('/[^a-z0-9_-]/i', '', $oldSlug) . '/';
        $newDir = UPLOADS_DIR . 'managed/' . preg_replace('/[^a-z0-9_-]/i', '', $newSlug) . '/';
        if (is_dir($oldDir) && !is_dir($newDir)) {
            @rename($oldDir, $newDir);
        } elseif (!is_dir($newDir)) {
            mkdir($newDir, 0755, true);
        }
        $files = dbAll("SELECT id, file_path FROM managed_files WHERE category_id=?", [$id]);
        foreach ($files as $file) {
            $currentPath = (string)($file['file_path'] ?? '');
            $updatedPath = preg_replace('#^managed/' . preg_quote($oldSlug, '#') . '/#', 'managed/' . $newSlug . '/', $currentPath);
            if ($updatedPath !== null && $updatedPath !== $currentPath) {
                dbWrite("UPDATE managed_files SET file_path=?, updated_at=NOW() WHERE id=?", [$updatedPath, $file['id']]);
            }
        }
    }

    dbWrite(
        "UPDATE file_categories SET name=?, slug=?, access_role=?, description=? WHERE id=?",
        [$name, $newSlug, $accessRole, $description, $id]
    );
    logActivityDb('files_update_category', $id);
    return ['success' => true, 'message' => 'Folder updated', 'data' => dbOne("SELECT * FROM file_categories WHERE id=?", [$id])];
}

function filesDeleteCategory() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    ensureFileCategories();
    $db = requireDb(['file_categories', 'managed_files']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $id = $_GET['id'] ?? (requestData()['id'] ?? '');
    if ($id === '') return ['success' => false, 'error' => 'Folder not specified'];
    $category = dbOne("SELECT * FROM file_categories WHERE id=?", [$id]);
    if (!$category) return ['success' => false, 'error' => 'Folder not found'];
    if ((int)($category['is_system_folder'] ?? 0) === 1) {
        return ['success' => false, 'error' => 'System media folders are protected'];
    }
    $files = dbAll("SELECT id, file_path FROM managed_files WHERE category_id=?", [$id]);
    foreach ($files as $file) {
        $path = UPLOADS_DIR . (string)$file['file_path'];
        if (is_file($path)) {
            @unlink($path);
        }
    }
    dbWrite("DELETE FROM managed_files WHERE category_id=?", [$id]);
    dbWrite("DELETE FROM file_categories WHERE id=?", [$id]);
    $dir = UPLOADS_DIR . 'managed/' . preg_replace('/[^a-z0-9_-]/i', '', $category['slug'] ?? '') . '/';
    if ($dir && is_dir($dir)) {
        @rmdir($dir);
    }
    logActivityDb('files_delete_category', $id);
    return ['success' => true, 'message' => 'Folder deleted'];
}

function filesGetByCategory() {
    ensureFileCategories();
    $categoryId = $_GET['category_id'] ?? '';
    $category = $categoryId ? dbOne("SELECT * FROM file_categories WHERE id=?", [$categoryId]) : dbOne("SELECT * FROM file_categories WHERE slug=?", [$_GET['category_slug'] ?? 'legal-compliance']);
    if (!$category) return ['success' => true, 'data' => []];
    if (currentUserRole() === 'hr_admin' && !in_array($category['access_role'], ['hr_admin','public'], true)) {
        http_response_code(403);
        return ['success' => false, 'error' => 'Access denied'];
    }
    return ['success' => true, 'category' => $category, 'data' => dbAll("SELECT * FROM managed_files WHERE category_id=? ORDER BY created_at DESC", [$category['id']])];
}

function mediaLibraryGetAssets() {
    ensureFileCategories();
    $db = requireDb(['managed_files', 'file_categories']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $params = [];
    $where = [];
    if (currentUserRole() === 'hr_admin') {
        $where[] = "fc.access_role IN ('hr_admin','public')";
    }

    $categoryId = trim((string)($_GET['category_id'] ?? ''));
    if ($categoryId !== '') {
        $where[] = "fc.id = ?";
        $params[] = $categoryId;
    }

    $search = trim((string)($_GET['search'] ?? ''));
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = "(mf.title LIKE ? OR mf.original_name LIKE ? OR fc.name LIKE ?)";
        array_push($params, $like, $like, $like);
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $rows = dbAll(
        "SELECT mf.*, fc.name AS category_name, fc.slug AS category_slug, fc.access_role AS category_access_role
         FROM managed_files mf
         LEFT JOIN file_categories fc ON fc.id = mf.category_id
         {$whereSql}
         ORDER BY mf.created_at DESC",
        $params
    );

    $imageRows = array_values(array_filter($rows, static function ($row) {
        $mime = strtolower((string)($row['mime_type'] ?? ''));
        $path = strtolower((string)($row['file_path'] ?? $row['filename'] ?? ''));
        if (strpos($mime, 'image/') === 0) return true;
        return (bool)preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/', $path);
    }));

    $categories = [];
    foreach ($imageRows as &$row) {
        $row['file_url'] = ADMIN_URL . 'uploads/' . ltrim((string)($row['file_path'] ?? ''), '/');
        $row['display_name'] = trim((string)($row['title'] ?? '')) ?: trim((string)($row['original_name'] ?? ''));
        $catId = (string)($row['category_id'] ?? '');
        if ($catId !== '' && !isset($categories[$catId])) {
            $categories[$catId] = [
                'id' => $catId,
                'name' => $row['category_name'] ?? 'Media',
                'slug' => $row['category_slug'] ?? '',
                'access_role' => $row['category_access_role'] ?? 'admin'
            ];
        }
    }
    unset($row);

    return [
        'success' => true,
        'data' => $imageRows,
        'categories' => array_values($categories)
    ];
}

function filesUpload() {
    ensureFileCategories();
    $db = requireDb(['managed_files','file_categories']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) return ['success' => false, 'error' => 'No file uploaded'];
    $categoryId = $_POST['category_id'] ?? '';
    $category = dbOne("SELECT * FROM file_categories WHERE id=?", [$categoryId]);
    if (!$category) return ['success' => false, 'error' => 'Category required'];
    if (currentUserRole() === 'hr_admin' && !in_array($category['access_role'], ['hr_admin','public'], true)) {
        http_response_code(403);
        return ['success' => false, 'error' => 'Access denied'];
    }
    $file = $_FILES['file'];
    $slug = preg_replace('/[^a-z0-9_-]/i', '', $category['slug']);
    $dir = UPLOADS_DIR . 'managed/' . $slug . '/';
    if (!file_exists($dir)) mkdir($dir, 0755, true);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = date('Ymd_His') . '_' . uniqid() . '.' . $extension;
    $path = $dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $path)) return ['success' => false, 'error' => 'Upload failed'];
    $existing = dbOne("SELECT id, version FROM managed_files WHERE category_id=? AND original_name=? ORDER BY version DESC LIMIT 1", [$categoryId, $file['name']]);
    $version = $existing ? ((int)$existing['version'] + 1) : 1;
    $parent = $existing['id'] ?? null;
    $id = 'file_' . uniqid();
    dbWrite("INSERT INTO managed_files (id, category_id, filename, original_name, file_path, file_size, mime_type, version, parent_file_id, title, description, access_role, is_template, uploaded_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())", [$id, $categoryId, $filename, $file['name'], 'managed/' . $slug . '/' . $filename, $file['size'], $file['type'], $version, $parent, $_POST['title'] ?? $file['name'], $_POST['description'] ?? '', $_POST['access_role'] ?? $category['access_role'], !empty($_POST['is_template']) ? 1 : 0, currentUsername()]);
    logActivityDb('files_upload', $file['name']);
    return ['success' => true, 'message' => 'File uploaded', 'data' => dbOne("SELECT * FROM managed_files WHERE id=?", [$id])];
}

function filesDelete() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $id = $_GET['id'] ?? '';
    $file = dbOne("SELECT * FROM managed_files WHERE id=?", [$id]);
    if ($file) {
        $path = UPLOADS_DIR . $file['file_path'];
        if (file_exists($path)) @unlink($path);
        dbWrite("DELETE FROM managed_files WHERE id=?", [$id]);
    }
    logActivityDb('files_delete', $id);
    return ['success' => true, 'message' => 'File deleted'];
}

function filesToggleTemplate() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $id = $_GET['id'] ?? '';
    dbWrite("UPDATE managed_files SET is_template = IF(is_template=1,0,1), updated_at=NOW() WHERE id=?", [$id]);
    logActivityDb('files_toggle_template', $id);
    return ['success' => true, 'message' => 'Template flag updated'];
}

function filesDownload() {
    $file = dbOne("SELECT mf.*, fc.access_role AS category_role FROM managed_files mf LEFT JOIN file_categories fc ON fc.id=mf.category_id WHERE mf.id=?", [$_GET['id'] ?? '']);
    if (!$file) { http_response_code(404); echo 'File not found'; exit; }
    if (currentUserRole() === 'hr_admin' && !in_array($file['access_role'], ['hr_admin','public'], true) && !in_array($file['category_role'], ['hr_admin','public'], true)) {
        http_response_code(403); echo 'Access denied'; exit;
    }
    $path = UPLOADS_DIR . $file['file_path'];
    if (!file_exists($path)) { http_response_code(404); echo 'File missing'; exit; }
    dbWrite("UPDATE managed_files SET download_count=download_count+1 WHERE id=?", [$file['id']]);
    header_remove('Content-Type');
    header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

function branchesGetAll() {
    $db = requireDb(['branches']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    ensureBranchUsersSchema();
    return ['success' => true, 'data' => dbAll(
        "SELECT b.*,
                bu.id AS manager_user_id,
                bu.username AS manager_username,
                bu.is_active AS manager_is_active,
                bu.last_login AS manager_last_login
         FROM branches b
         LEFT JOIN branch_users bu
           ON bu.branch_id = b.id
          AND bu.role = 'branch_manager'
         ORDER BY b.created_at DESC"
    )];
}

function branchesSave() {
    $data = requestData();
    if (empty($data['name'])) return ['success' => false, 'error' => 'Branch name is required'];
    if (empty($data['branch_code'])) $data['branch_code'] = strtoupper(substr(preg_replace('/[^A-Z]/i', '', $data['region'] ?? 'PNG'), 0, 3)) . '-' . str_pad((string)((int)dbScalar("SELECT COUNT(*) FROM branches") + 1), 3, '0', STR_PAD_LEFT);
    ensureBranchUsersSchema();
    $managerUsername = strtolower(preg_replace('/[^a-z0-9._-]+/i', '', trim($data['manager_username'] ?? '')));
    if ($managerUsername !== '') {
        $currentBranchId = trim($data['id'] ?? '');
        $taken = dbOne("SELECT id, branch_id FROM branch_users WHERE username=? AND branch_id<>? LIMIT 1", [$managerUsername, $currentBranchId ?: '']);
        if ($taken) return ['success' => false, 'error' => 'That branch manager username already belongs to another branch'];
        $data['manager_username'] = $managerUsername;
    }
    $result = dbUpsert('branches', ['id','branch_code','name','region','country','address','phone','email','manager_name','status','template_id','registered_at','created_at','created_by'], $data, 'branch');
    $branchId = $result['data']['id'] ?? ($data['id'] ?? null);
    if (($data['provision_templates'] ?? '1') === '1') branchesProvisionTemplate($branchId);
    $branch = $branchId ? dbOne("SELECT * FROM branches WHERE id=?", [$branchId]) : null;
    $managerAccount = ensureBranchManagerAccount($branch, $data);
    if ($managerAccount && !empty($managerAccount['error'])) {
        return ['success' => false, 'error' => $managerAccount['error'], 'data' => $result['data'] ?? null];
    }
    if ($managerAccount) {
        $result['branch_manager'] = $managerAccount;
        if (($managerAccount['password'] ?? '') !== 'unchanged') {
            $result['credentials'] = ['username' => $managerAccount['username'], 'password' => $managerAccount['password']];
            $result['message'] = ($result['message'] ?? 'Branch saved') . '. Branch login: ' . $managerAccount['username'] . ' / ' . $managerAccount['password'];
        } else {
            $result['message'] = ($result['message'] ?? 'Branch saved') . '. Branch login: ' . $managerAccount['username'] . ' / existing password';
        }
    }
    return $result;
}

function ensureBranchManagerAccount($branch, $options = []) {
    if (!$branch || empty($branch['id']) || empty($branch['branch_code'])) return null;
    ensureBranchUsersSchema();
    $existing = dbOne("SELECT * FROM branch_users WHERE branch_id=? AND role='branch_manager' LIMIT 1", [$branch['id']]);
    $username = trim($options['manager_username'] ?? '');
    if ($username === '' && $existing && !empty($existing['username'])) {
        $username = $existing['username'];
    }
    if ($username === '') $username = strtolower(preg_replace('/[^a-z0-9]+/i', '', $branch['branch_code'])) . '_manager';
    $username = strtolower(preg_replace('/[^a-z0-9._-]+/i', '', $username));
    $taken = dbOne("SELECT id, branch_id FROM branch_users WHERE username=? AND branch_id<>? LIMIT 1", [$username, $branch['id']]);
    if ($taken) return ['error' => 'That branch manager username already belongs to another branch'];
    if (!$existing) {
        $existing = dbOne("SELECT * FROM branch_users WHERE branch_id=? AND username=? LIMIT 1", [$branch['id'], $username]);
    }
    $plainPassword = trim((string)($options['manager_password'] ?? ($options['password'] ?? '')));
    $passwordWasProvided = $plainPassword !== '';
    if (!$passwordWasProvided) $plainPassword = 'branch123';
    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    if ($existing) {
        $params = [$username, $branch['manager_name'] ?? $branch['name'], $branch['email'] ?? '', $branch['phone'] ?? ''];
        $passwordSql = '';
        if ($passwordWasProvided) {
            $passwordSql = ', password=?';
            $params[] = $passwordHash;
        }
        $params[] = $existing['id'];
        dbWrite("UPDATE branch_users SET username=?, full_name=?, email=?, phone=?, role='branch_manager', portal_scope='admin', is_active=1{$passwordSql}, updated_at=NOW() WHERE id=?", $params);
        if ($passwordWasProvided && !branchPasswordMatches($existing['id'], $plainPassword)) {
            return ['error' => 'Branch manager password could not be verified after saving'];
        }
        return ['username' => $username, 'password' => $passwordWasProvided ? $plainPassword : 'unchanged'];
    }
    $id = 'buser_' . uniqid();
    dbWrite("INSERT INTO branch_users (id, branch_id, username, password, full_name, email, phone, role, portal_scope, is_active, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())", [$id, $branch['id'], $username, $passwordHash, $branch['manager_name'] ?? $branch['name'], $branch['email'] ?? '', $branch['phone'] ?? '', 'branch_manager', 'admin', 1]);
    if (!branchPasswordMatches($id, $plainPassword)) {
        return ['error' => 'Branch manager password could not be verified after saving'];
    }
    logActivityDb('branch_manager_account_created', $username);
    return ['username' => $username, 'password' => $plainPassword];
}

function branchPasswordMatches($id, $plainPassword) {
    $row = dbOne("SELECT password FROM branch_users WHERE id=? LIMIT 1", [$id]);
    return $row && password_verify($plainPassword, $row['password']);
}

function branchPasswordSet() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $db = requireDb(['branches']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    ensureBranchUsersSchema();

    $data = requestData();
    $password = trim((string)($data['password'] ?? ($data['manager_password'] ?? ($data['branch_password'] ?? ($data['new_password'] ?? '')))));
    if ($password === '') return ['success' => false, 'error' => 'Enter the new branch password before saving'];
    if (strlen($password) < 6) return ['success' => false, 'error' => 'Branch password must be at least 6 characters'];

    $user = null;
    $branch = null;
    $userId = trim((string)($data['id'] ?? ($data['user_id'] ?? ($data['manager_user_id'] ?? ''))));
    $branchId = trim((string)($data['branch_id'] ?? ''));
    $username = trim((string)($data['username'] ?? ($data['manager_username'] ?? '')));

    if ($userId !== '') {
        $user = dbOne("SELECT * FROM branch_users WHERE id=? LIMIT 1", [$userId]);
    }
    if (!$user && $username !== '') {
        $user = dbOne("SELECT * FROM branch_users WHERE username=? LIMIT 1", [$username]);
    }
    if (!$user && $branchId !== '') {
        $user = dbOne("SELECT * FROM branch_users WHERE branch_id=? AND role='branch_manager' LIMIT 1", [$branchId]);
    }

    if ($user) {
        if ($username !== '' && $username !== $user['username']) {
            $taken = dbOne("SELECT id FROM branch_users WHERE username=? AND id<>? LIMIT 1", [$username, $user['id']]);
            if ($taken) return ['success' => false, 'error' => 'That branch username is already in use'];
        } else {
            $username = $user['username'];
        }

        $sets = ['password=?', 'updated_at=NOW()'];
        $params = [password_hash($password, PASSWORD_DEFAULT)];
        if ($username !== $user['username']) {
            array_unshift($sets, 'username=?');
            array_unshift($params, $username);
        }
        $params[] = $user['id'];
        dbWrite("UPDATE branch_users SET " . implode(',', $sets) . " WHERE id=?", $params);
        if (!branchPasswordMatches($user['id'], $password)) {
            return ['success' => false, 'error' => 'Branch password could not be verified after saving'];
        }
        logActivityDb('branch_password_set', ['username' => $username, 'branch_id' => $user['branch_id'] ?? '']);
        return ['success' => true, 'message' => "Branch password saved. Login ID: {$username}", 'credentials' => ['username' => $username, 'password' => $password]];
    }

    if ($branchId === '' && isset($data['branch_code'])) {
        $branch = dbOne("SELECT * FROM branches WHERE branch_code=? LIMIT 1", [trim((string)$data['branch_code'])]);
        $branchId = $branch['id'] ?? '';
    }
    if (!$branch && $branchId !== '') {
        $branch = dbOne("SELECT * FROM branches WHERE id=? LIMIT 1", [$branchId]);
    }
    if (!$branch) return ['success' => false, 'error' => 'Branch user was not found for this password update'];

    $manager = ensureBranchManagerAccount($branch, [
        'manager_username' => $username,
        'manager_password' => $password
    ]);
    if (!$manager || !empty($manager['error'])) {
        return ['success' => false, 'error' => $manager['error'] ?? 'Branch manager account could not be created'];
    }
    $createdUser = dbOne("SELECT * FROM branch_users WHERE branch_id=? AND username=? LIMIT 1", [$branch['id'], $manager['username']]);
    if (!$createdUser || !branchPasswordMatches($createdUser['id'], $password)) {
        return ['success' => false, 'error' => 'Branch password could not be verified after saving'];
    }
    logActivityDb('branch_manager_password_set', ['username' => $manager['username'], 'branch_id' => $branch['id']]);
    return ['success' => true, 'message' => "Branch manager password saved. Login ID: {$manager['username']}", 'credentials' => ['username' => $manager['username'], 'password' => $password]];
}

function branchUsersGetAll() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $db = requireDb(['branches']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    ensureBranchUsersSchema();

    foreach (dbAll("SELECT * FROM branches WHERE status='active'") as $branch) {
        $hasUser = (int)dbScalar("SELECT COUNT(*) FROM branch_users WHERE branch_id=?", [$branch['id']]);
        if ($hasUser === 0) ensureBranchManagerAccount($branch);
    }

    $rows = dbAll(
        "SELECT bu.id, bu.branch_id, bu.username, bu.full_name, bu.email, bu.phone, bu.role, bu.portal_scope,
                bu.notes, bu.is_active, bu.last_login, bu.created_at, bu.updated_at,
                b.name AS branch_name, b.branch_code,
                CASE WHEN bu.is_active=1 THEN 'active' ELSE 'inactive' END AS status_label
         FROM branch_users bu
         LEFT JOIN branches b ON b.id = bu.branch_id
         ORDER BY b.name, bu.full_name, bu.username"
    );

    return [
        'success' => true,
        'data' => $rows,
        'stats' => [
            'total_users' => count($rows),
            'active_users' => (int)dbScalar("SELECT COUNT(*) FROM branch_users WHERE is_active=1"),
            'admin_sync' => (int)dbScalar("SELECT COUNT(*) FROM branch_users WHERE portal_scope='admin'"),
            'hr_sync' => (int)dbScalar("SELECT COUNT(*) FROM branch_users WHERE portal_scope='hr'")
        ]
    ];
}

function branchUsersDeprecated() {
    return [
        'success' => false,
        'error' => 'Branch user management has been consolidated into Branch Registry. Register or edit a branch to manage its login credentials.'
    ];
}

function branchUsersSave() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $db = requireDb(['branches']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    ensureBranchUsersSchema();

    $data = requestData();
    $id = trim($data['id'] ?? '');
    $branchId = trim($data['branch_id'] ?? '');
    $branch = $branchId ? dbOne("SELECT * FROM branches WHERE id=?", [$branchId]) : null;
    if (!$branch) return ['success' => false, 'error' => 'Select a valid branch before saving the user'];

    $fullName = trim($data['full_name'] ?? '');
    $username = trim($data['username'] ?? '');
    if ($username === '') $username = generateBranchUsername($branch, $fullName);
    if ($fullName === '') $fullName = $username;

    $role = in_array(($data['role'] ?? 'branch_user'), ['branch_user', 'branch_manager'], true) ? $data['role'] : 'branch_user';
    $scope = in_array(($data['portal_scope'] ?? 'admin'), ['admin', 'hr'], true) ? $data['portal_scope'] : 'admin';
    $isActive = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;

    $duplicate = dbOne("SELECT id FROM branch_users WHERE username=? AND id<>?", [$username, $id ?: '']);
    if ($duplicate) return ['success' => false, 'error' => 'This branch user ID is already taken'];

    $plainPassword = trim((string)($data['password'] ?? ''));
    $newPassword = false;
    if (!$id && $plainPassword === '') {
        $plainPassword = generateBranchPassword($branch['branch_code'] ?? 'BR');
        $newPassword = true;
    } elseif ($plainPassword !== '') {
        $newPassword = true;
    }

    if ($id) {
        $sets = ['branch_id=?', 'username=?', 'full_name=?', 'email=?', 'phone=?', 'role=?', 'portal_scope=?', 'notes=?', 'is_active=?', 'updated_at=NOW()'];
        $params = [$branchId, $username, $fullName, $data['email'] ?? '', $data['phone'] ?? '', $role, $scope, $data['notes'] ?? '', $isActive];
        if ($plainPassword !== '') {
            $sets[] = 'password=?';
            $params[] = password_hash($plainPassword, PASSWORD_DEFAULT);
        }
        $params[] = $id;
        dbWrite("UPDATE branch_users SET " . implode(',', $sets) . " WHERE id=?", $params);
    } else {
        $id = 'buser_' . uniqid();
        dbWrite(
            "INSERT INTO branch_users (id, branch_id, username, password, full_name, email, phone, role, portal_scope, notes, is_active, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())",
            [$id, $branchId, $username, password_hash($plainPassword, PASSWORD_DEFAULT), $fullName, $data['email'] ?? '', $data['phone'] ?? '', $role, $scope, $data['notes'] ?? '', $isActive]
        );
    }
    if ($newPassword && !branchPasswordMatches($id, $plainPassword)) {
        return ['success' => false, 'error' => 'Branch password could not be verified after saving'];
    }

    logActivityDb('branch_user_save', ['username' => $username, 'branch' => $branch['branch_code'] ?? $branchId, 'scope' => $scope]);
    $message = "Branch user saved. Login ID: {$username}";
    if ($newPassword) $message .= " | Password: {$plainPassword}";
    return ['success' => true, 'message' => $message, 'credentials' => ['username' => $username, 'password' => $newPassword ? $plainPassword : null]];
}

function branchUsersToggleActive() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    ensureBranchUsersSchema();
    $id = $_GET['id'] ?? '';
    dbWrite("UPDATE branch_users SET is_active = IF(is_active=1,0,1), updated_at=NOW() WHERE id=?", [$id]);
    logActivityDb('branch_user_toggle_active', $id);
    return ['success' => true, 'message' => 'Branch user status updated'];
}

function generateBranchUsername($branch, $fullName = '') {
    $branchCode = strtolower(preg_replace('/[^a-z0-9]+/i', '', $branch['branch_code'] ?? 'branch'));
    $name = strtolower(preg_replace('/[^a-z0-9]+/i', '', $fullName ?: 'user'));
    $name = substr($name ?: 'user', 0, 18);
    $base = trim($branchCode . '_' . $name, '_');
    $username = $base;
    $counter = 1;
    while (dbOne("SELECT id FROM branch_users WHERE username=?", [$username])) {
        $counter++;
        $username = $base . $counter;
    }
    return $username;
}

function generateBranchPassword($branchCode = 'BR') {
    $prefix = strtoupper(substr(preg_replace('/[^A-Z0-9]+/i', '', $branchCode), 0, 4) ?: 'BR');
    try {
        return $prefix . '-' . random_int(1000, 9999);
    } catch (Throwable $e) {
        return $prefix . '-' . substr((string)time(), -4);
    }
}

function branchesDelete() {
    $id = $_GET['id'] ?? '';
    $deps = (int)dbScalar("SELECT COUNT(*) FROM branch_projects WHERE branch_id=?", [$id]) + (int)dbScalar("SELECT COUNT(*) FROM branch_expenses WHERE branch_id=?", [$id]);
    if ($deps > 0) return ['success' => false, 'error' => 'Branch has dependent project or expense records'];
    dbWrite("DELETE FROM branches WHERE id=?", [$id]);
    return ['success' => true, 'message' => 'Branch deleted'];
}

function branchesGetDetail() {
    $id = $_GET['id'] ?? '';
    return ['success' => true, 'data' => dbOne("SELECT * FROM branches WHERE id=?", [$id])];
}

function branchesProvisionTemplate($branchId = null) {
    ensureFileCategories();
    $branchId = $branchId ?: ($_GET['branch_id'] ?? '');
    $branch = dbOne("SELECT * FROM branches WHERE id=?", [$branchId]);
    if (!$branch) return ['success' => false, 'error' => 'Branch not found'];
    $parent = dbOne("SELECT id FROM file_categories WHERE slug='branch-reports'");
    $slug = 'branch-reports-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $branch['branch_code']));
    $catId = 'cat_' . uniqid();
    dbWrite("INSERT IGNORE INTO file_categories (id, name, slug, parent_id, access_role, description, is_system_folder, created_at) VALUES (?,?,?,?,?,?,1,NOW())", [$catId, 'Branch Reports / ' . $branch['branch_code'], $slug, $parent['id'] ?? null, 'admin', 'Provisioned folder for ' . $branch['name']]);
    logActivityDb('branches_provision_template', $branch['branch_code']);
    return ['success' => true, 'message' => 'Branch folder provisioned', 'folder_slug' => $slug];
}

function branchProjectsGet() {
    $where = '';
    $params = [];
    if (!empty($_GET['branch_id'])) { $where = 'WHERE bp.branch_id=?'; $params[] = $_GET['branch_id']; }
    return ['success' => true, 'data' => dbAll("SELECT bp.*, b.name AS branch_name FROM branch_projects bp LEFT JOIN branches b ON b.id=bp.branch_id {$where} ORDER BY bp.created_at DESC", $params)];
}

function branchProjectsSave() {
    $data = requestData();
    if (empty($data['branch_id']) || empty($data['name'])) return ['success' => false, 'error' => 'Branch and project name are required'];
    return dbUpsert('branch_projects', ['id','branch_id','name','description','start_date','expected_end_date','actual_end_date','budget','spent','progress_percent','status','milestones','created_at','updated_at','submitted_by'], $data, 'bproj');
}

function syncProjectToBranch($projectId, $project) {
    ensure_komagin_workflow_schema(get_db());
    if (!dbTableExists('branch_projects') || empty($projectId) || empty($project['branch_id'])) return;
    $branchId = $project['branch_id'];
    $existing = dbOne("SELECT id FROM branch_projects WHERE source_project_id=?", [$projectId]);
    if ($existing) {
        dbWrite("UPDATE branch_projects SET branch_id=?, name=?, description=?, status=IF(status='cancelled','planning',status), updated_at=NOW(), submitted_by=? WHERE source_project_id=?", [
            $branchId,
            $project['name'] ?? 'Project',
            $project['description'] ?? '',
            currentUsername(),
            $projectId
        ]);
    } else {
        dbWrite("INSERT INTO branch_projects (id,source_project_id,branch_id,name,description,status,progress_percent,created_at,updated_at,submitted_by) VALUES (?,?,?,?,?,'planning',0,NOW(),NOW(),?)", [
            'bproj_' . uniqid(),
            $projectId,
            $branchId,
            $project['name'] ?? 'Project',
            $project['description'] ?? '',
            currentUsername()
        ]);
    }
}

function unsyncProjectFromBranch($projectId) {
    if (!dbTableExists('branch_projects') || empty($projectId) || !dbColumnExists('branch_projects', 'source_project_id')) return;
    dbWrite("UPDATE branch_projects SET status='cancelled', updated_at=NOW(), submitted_by=? WHERE source_project_id=? AND progress_percent=0", [currentUsername(), $projectId]);
}

function branchProjectsUpdateProgress() {
    return branchProjectsSave();
}

function branchGenerateProjectReport() {
    $project = dbOne("SELECT bp.*, b.name AS branch_name, b.branch_code FROM branch_projects bp LEFT JOIN branches b ON b.id=bp.branch_id WHERE bp.id=?", [$_GET['project_id'] ?? '']);
    if (!$project) printCompanyDocument('Project Progress Report', '<p>Project not found.</p>');
    $progress = max(0, min(100, (int)$project['progress_percent']));
    $milestones = json_decode($project['milestones'] ?? '[]', true) ?: [];
    $rows = '';
    foreach ($milestones as $m) $rows .= '<tr><td>' . htmlspecialchars($m['name'] ?? '') . '</td><td>' . htmlspecialchars($m['date'] ?? '') . '</td><td>' . (!empty($m['done']) ? 'Done' : 'Open') . '</td></tr>';
    $body = "<div class='box'><strong>Project:</strong> " . htmlspecialchars($project['name']) . "<br><strong>Branch:</strong> " . htmlspecialchars($project['branch_name']) . " (" . htmlspecialchars($project['branch_code']) . ")<br><strong>Status:</strong> " . htmlspecialchars($project['status']) . "</div><p>Progress: {$progress}%</p><div class='progress'><div style='width:{$progress}%'></div></div><table><tr><th>Budget</th><th>Spent</th></tr><tr><td>" . money($project['budget']) . "</td><td>" . money($project['spent']) . "</td></tr></table><table><thead><tr><th>Milestone</th><th>Date</th><th>Status</th></tr></thead><tbody>{$rows}</tbody></table>";
    logActivityDb('branch_generate_project_report', $project['id']);
    printCompanyDocument('Project Progress Report', $body);
}

function branchExpensesGet() {
    $where = [];
    $params = [];
    if (!empty($_GET['branch_id'])) { $where[] = 'be.branch_id=?'; $params[] = $_GET['branch_id']; }
    if (!empty($_GET['status'])) { $where[] = 'be.status=?'; $params[] = $_GET['status']; }
    $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $rows = dbAll("SELECT be.*, b.name AS branch_name, bp.name AS project_name FROM branch_expenses be LEFT JOIN branches b ON b.id=be.branch_id LEFT JOIN branch_projects bp ON bp.id=be.project_id {$sqlWhere} ORDER BY be.expense_date DESC", $params);
    $stats = [
        'submitted' => array_sum(array_map(fn($r) => (float)$r['amount'], $rows)),
        'approved' => array_sum(array_map(fn($r) => ($r['status'] === 'approved') ? (float)$r['amount'] : 0, $rows)),
        'pending' => array_sum(array_map(fn($r) => ($r['status'] === 'pending') ? (float)$r['amount'] : 0, $rows)),
        'rejected' => array_sum(array_map(fn($r) => ($r['status'] === 'rejected') ? (float)$r['amount'] : 0, $rows))
    ];
    return ['success' => true, 'data' => $rows, 'stats' => $stats];
}

function branchExpensesSave() {
    $data = requestData();
    if (empty($data['branch_id']) || empty($data['description']) || empty($data['amount']) || empty($data['expense_date'])) return ['success' => false, 'error' => 'Branch, description, amount, and date are required'];
    return dbUpsert('branch_expenses', ['id','branch_id','project_id','category','description','amount','currency','expense_date','receipt_file','status','approved_by','approved_at','notes','created_at','submitted_by'], $data, 'bexp');
}

function branchExpensesSetStatus($status) {
    $id = $_GET['id'] ?? (requestData()['id'] ?? '');
    $notes = requestData()['notes'] ?? null;
    dbWrite("UPDATE branch_expenses SET status=?, approved_by=?, approved_at=NOW(), notes=COALESCE(?, notes) WHERE id=?", [$status, currentUsername(), $notes, $id]);
    logActivityDb('branch_expenses_' . $status, $id);
    return ['success' => true, 'message' => 'Expense ' . $status];
}

function branchGenerateExpenseReport() {
    $branchId = $_GET['branch_id'] ?? '';
    $period = $_GET['period'] ?? date('Y-m');
    $branch = dbOne("SELECT * FROM branches WHERE id=?", [$branchId]);
    $rows = dbAll("SELECT * FROM branch_expenses WHERE branch_id=? AND DATE_FORMAT(expense_date, '%Y-%m')=? ORDER BY category, expense_date", [$branchId, $period]);
    $trs = '';
    $total = 0;
    foreach ($rows as $r) { $total += (float)$r['amount']; $trs .= '<tr><td>' . htmlspecialchars($r['category'] ?? '') . '</td><td>' . htmlspecialchars($r['description']) . '</td><td>' . htmlspecialchars($r['expense_date']) . '</td><td>' . money($r['amount']) . '</td><td>' . htmlspecialchars($r['status']) . '</td></tr>'; }
    $body = "<div class='box'><strong>Branch:</strong> " . htmlspecialchars($branch['name'] ?? '') . "<br><strong>Period:</strong> " . htmlspecialchars($period) . "</div><table><thead><tr><th>Category</th><th>Description</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>{$trs}</tbody></table><div class='net'>Total: " . money($total) . "</div><div class='signatures'><div class='sig'>Prepared by</div><div class='sig'>Approved by</div></div>";
    logActivityDb('branch_generate_expense_report', $branchId . ' ' . $period);
    printCompanyDocument('Branch Expense Report', $body);
}

function branchAssetsGet() {
    return ['success' => true, 'data' => dbAll("SELECT ba.*, a.asset_tag, a.name AS asset_name, a.category, b.name AS branch_name FROM branch_assets ba LEFT JOIN assets a ON a.id=ba.asset_id LEFT JOIN branches b ON b.id=ba.branch_id ORDER BY ba.assigned_date DESC")];
}

function syncAssetToBranch($assetId, $branchId, $data = []) {
    if (!dbTableExists('branch_assets') || empty($assetId) || empty($branchId)) return;
    $active = dbOne("SELECT id FROM branch_assets WHERE asset_id=? AND return_date IS NULL ORDER BY created_at DESC LIMIT 1", [$assetId]);
    if ($active) {
        dbWrite("UPDATE branch_assets SET branch_id=?, assigned_date=COALESCE(?, assigned_date), condition_on_assignment=COALESCE(?, condition_on_assignment), notes=COALESCE(?, notes) WHERE id=?", [
            $branchId,
            $data['assigned_date'] ?? date('Y-m-d'),
            $data['condition'] ?? null,
            $data['notes'] ?? null,
            $active['id']
        ]);
        return;
    }
    dbWrite("INSERT INTO branch_assets (id,branch_id,asset_id,assigned_date,condition_on_assignment,notes,created_at) VALUES (?,?,?,?,?,?,NOW())", [
        'basset_' . uniqid(),
        $branchId,
        $assetId,
        $data['assigned_date'] ?? date('Y-m-d'),
        $data['condition'] ?? null,
        $data['notes'] ?? null
    ]);
}

function branchAssetsReturn() {
    $id = $_GET['id'] ?? '';
    $row = dbOne("SELECT asset_id FROM branch_assets WHERE id=?", [$id]);
    dbWrite("UPDATE branch_assets SET return_date=CURDATE() WHERE id=?", [$id]);
    if ($row) dbWrite("UPDATE assets SET status='available', assigned_to_branch=NULL WHERE id=?", [$row['asset_id']]);
    return ['success' => true, 'message' => 'Branch asset returned'];
}

function branchHubGet() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    ensure_branch_content_schema(get_db());
    $stats = [
        'active_branches' => dbTableExists('branches') ? (int)dbScalar("SELECT COUNT(*) FROM branches WHERE status='active'") : 0,
        'submitted_content' => dbTableExists('branch_content_submissions') ? (int)dbScalar("SELECT COUNT(*) FROM branch_content_submissions WHERE status IN ('submitted','under_review')") : 0,
        'submitted_templates' => dbTableExists('branch_template_submissions') ? (int)dbScalar("SELECT COUNT(*) FROM branch_template_submissions WHERE status='submitted'") : 0,
        'pending_expenses' => dbTableExists('branch_expenses') ? (int)dbScalar("SELECT COUNT(*) FROM branch_expenses WHERE status='pending'") : 0,
        'open_rfis' => dbTableExists('branch_rfis') ? (int)dbScalar("SELECT COUNT(*) FROM branch_rfis WHERE status IN ('open','under_review')") : 0,
        'site_reports' => dbTableExists('branch_site_reports') ? (int)dbScalar("SELECT COUNT(*) FROM branch_site_reports WHERE status='submitted'") : 0,
        'overdue_milestones' => dbTableExists('branch_milestones') ? (int)dbScalar("SELECT COUNT(*) FROM branch_milestones WHERE due_date < CURDATE() AND status NOT IN ('completed')") : 0
    ];
    return ['success' => true, 'data' => [
        'stats' => $stats,
        'content' => branchContentRows(25),
        'template_submissions' => dbTableExists('branch_template_submissions') ? dbAll("SELECT bts.*, b.name branch_name, ht.title template_title FROM branch_template_submissions bts LEFT JOIN branches b ON b.id=bts.branch_id LEFT JOIN hr_templates ht ON ht.id=bts.template_id ORDER BY bts.updated_at DESC, bts.created_at DESC LIMIT 25") : [],
        'site_reports' => dbTableExists('branch_site_reports') ? dbAll("SELECT sr.*, b.name branch_name, bp.name project_name FROM branch_site_reports sr LEFT JOIN branches b ON b.id=sr.branch_id LEFT JOIN branch_projects bp ON bp.id=sr.project_id ORDER BY sr.created_at DESC LIMIT 25") : [],
        'expenses' => dbTableExists('branch_expenses') ? dbAll("SELECT be.*, b.name branch_name, bp.name project_name FROM branch_expenses be LEFT JOIN branches b ON b.id=be.branch_id LEFT JOIN branch_projects bp ON bp.id=be.project_id ORDER BY FIELD(be.status,'pending','rejected','approved'), be.created_at DESC LIMIT 25") : [],
        'rfis' => dbTableExists('branch_rfis') ? dbAll("SELECT r.*, b.name branch_name, bp.name project_name FROM branch_rfis r LEFT JOIN branches b ON b.id=r.branch_id LEFT JOIN branch_projects bp ON bp.id=r.project_id ORDER BY FIELD(r.status,'open','under_review','answered','closed'), r.created_at DESC LIMIT 25") : [],
        'milestones' => dbTableExists('branch_milestones') ? dbAll("SELECT bm.*, b.name branch_name, bp.name project_name FROM branch_milestones bm LEFT JOIN branches b ON b.id=bm.branch_id LEFT JOIN branch_projects bp ON bp.id=bm.project_id ORDER BY bm.due_date ASC LIMIT 25") : []
    ]];
}

function branchContentRows($limit = 100) {
    ensure_branch_content_schema(get_db());
    return dbAll("SELECT bcs.*, b.name branch_name, b.branch_code, bp.name project_name
        FROM branch_content_submissions bcs
        LEFT JOIN branches b ON b.id=bcs.branch_id
        LEFT JOIN branch_projects bp ON bp.id=bcs.project_id
        ORDER BY bcs.created_at DESC LIMIT " . (int)$limit);
}

function branchContentGet() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    return ['success' => true, 'data' => branchContentRows(200), 'stats' => [
        'submitted' => (int)dbScalar("SELECT COUNT(*) FROM branch_content_submissions WHERE status='submitted'"),
        'under_review' => (int)dbScalar("SELECT COUNT(*) FROM branch_content_submissions WHERE status='under_review'"),
        'approved' => (int)dbScalar("SELECT COUNT(*) FROM branch_content_submissions WHERE status='approved'"),
        'published' => (int)dbScalar("SELECT COUNT(*) FROM branch_content_submissions WHERE status='published'")
    ]];
}

function branchContentSetStatus() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    ensure_branch_content_schema(get_db());
    $data = requestData();
    $id = $_GET['id'] ?? ($data['id'] ?? '');
    $status = $_GET['status'] ?? ($data['status'] ?? 'under_review');
    $allowed = ['submitted','under_review','approved','rejected','published','archived'];
    if (!$id || !in_array($status, $allowed, true)) return ['success' => false, 'error' => 'Valid content ID and status are required'];
    dbWrite("UPDATE branch_content_submissions SET status=?, reviewed_by=?, reviewed_at=NOW(), admin_notes=COALESCE(?, admin_notes), updated_at=NOW() WHERE id=?", [$status, currentUsername(), $data['admin_notes'] ?? null, $id]);
    logActivityDb('branch_content_' . $status, $id);
    return ['success' => true, 'message' => 'Branch content marked ' . $status];
}

function partnersGetAll() {
    $db = requireDb(['partners']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $rows = array_map(static function ($row) {
        return normalizePartnerEnquiryRow($row, false);
    }, dbAll("SELECT * FROM partners ORDER BY created_at DESC"));
    $stats = [
        'total' => count($rows),
        'under_review' => (int)dbScalar("SELECT COUNT(*) FROM partners WHERE status='under_review'"),
        'approved' => (int)dbScalar("SELECT COUNT(*) FROM partners WHERE status='approved'"),
        'active' => (int)dbScalar("SELECT COUNT(*) FROM partners WHERE status='active'")
    ];
    return ['success' => true, 'data' => $rows, 'stats' => $stats];
}

function deleteUploadDirectoryTree($dir) {
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

function collectExtractedUploadDocuments($absoluteRoot) {
    if (!is_dir($absoluteRoot)) return [];
    $documents = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absoluteRoot, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $absolutePath = $file->getPathname();
        $relativePath = normalizeApplicationRelativePath(substr($absolutePath, strlen(UPLOADS_DIR)));
        $folder = normalizeApplicationRelativePath(substr(dirname($absolutePath), strlen($absoluteRoot)));
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

function extractUploadedDocumentBundle($archivePath, $extractDir, &$error = null) {
    $error = null;
    deleteUploadDirectoryTree($extractDir);
    if (!is_dir($extractDir) && !mkdir($extractDir, 0755, true) && !is_dir($extractDir)) {
        $error = 'Document package directory could not be prepared.';
        return [];
    }

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        $opened = $zip->open($archivePath);
        if ($opened !== true) {
            $error = 'The ZIP package could not be inspected.';
            return [];
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->getNameIndex($index);
            $normalized = str_replace('\\', '/', trim((string)$entry));
            if ($normalized === '' || str_ends_with($normalized, '/')) continue;
            if (str_contains($normalized, '../') || str_starts_with($normalized, '../') || preg_match('/^[A-Za-z]:/', $normalized) || str_starts_with($normalized, '/')) {
                $zip->close();
                $error = 'The ZIP package contains unsafe file paths.';
                return [];
            }
        }

        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            $error = 'The ZIP package could not be extracted.';
            return [];
        }

        $zip->close();
        return collectExtractedUploadDocuments($extractDir);
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
    return collectExtractedUploadDocuments($extractDir);
}

function getPartnerDocumentsForRow(array $row) {
    $documents = decodeApplicationDocumentManifest($row['document_manifest'] ?? '');
    if (!$documents) {
        $documents = scanApplicationExtractedDocuments($row['document_extract_dir'] ?? '');
    }
    return array_values(array_filter(array_map(static function ($document) {
        if (!is_array($document)) return null;
        $relativePath = normalizeApplicationRelativePath($document['relative_path'] ?? '');
        return [
            'name' => trim((string)($document['name'] ?? basename($relativePath))),
            'relative_path' => $relativePath,
            'folder' => normalizeApplicationRelativePath($document['folder'] ?? ''),
            'extension' => strtolower(trim((string)($document['extension'] ?? pathinfo($relativePath, PATHINFO_EXTENSION)))),
            'size' => (int)($document['size'] ?? 0),
            'url' => $document['url'] ?? applicationUploadUrl($relativePath)
        ];
    }, $documents), static function ($document) {
        return is_array($document) && !empty($document['relative_path']) && !empty($document['url']);
    }));
}

function normalizePartnerEnquiryRow(array $row, $includeDocuments = false) {
    $bundlePath = normalizeApplicationRelativePath($row['document_bundle_path'] ?? '');
    $bundleName = trim((string)($row['document_bundle_name'] ?? basename($bundlePath)));
    $row['bundle_path'] = $bundlePath;
    $row['bundle_name'] = $bundleName !== '' ? $bundleName : ($bundlePath !== '' ? basename($bundlePath) : '');
    $row['bundle_url'] = applicationUploadUrl($bundlePath);
    $row['bundle_is_zip'] = (bool)preg_match('/\.zip$/i', $row['bundle_name']);
    $row['document_extract_dir'] = normalizeApplicationRelativePath($row['document_extract_dir'] ?? '');
    $row['rejection_reason'] = trim((string)($row['rejection_reason'] ?? ''));
    $documents = getPartnerDocumentsForRow($row);
    $row['documents_count'] = count($documents);
    if ($includeDocuments) {
        $row['documents'] = $documents;
    }
    return $row;
}

function savePartnerEnquiryRecord(array $data, $isEdit) {
    $fields = ['id','company_name','contact_name','email','phone','country','expertise','portfolio_url','status','access_scope','nda_signed','nda_date','document_bundle_path','document_bundle_name','document_manifest','document_extract_dir','rejection_reason','notes','enquiry_date','approved_date','approved_by','created_at','updated_at'];
    if ($isEdit) {
        return dbUpsert('partners', $fields, $data, 'partner');
    }

    $db = requireDb(['partners']);
    if (is_array($db) && isset($db['error'])) return ['success' => false, 'error' => $db['error']];

    $id = trim((string)($data['id'] ?? ''));
    if ($id === '') {
        $id = 'partner_' . uniqid();
    }

    $now = date('Y-m-d H:i:s');
    $record = [];
    foreach ($fields as $field) {
        if (array_key_exists($field, $data)) {
            $record[$field] = cleanDbValue($data[$field]);
        }
    }

    $record['id'] = $id;
    if (!isset($record['status']) || $record['status'] === null || $record['status'] === '') $record['status'] = 'enquiry';
    if (!isset($record['enquiry_date']) || $record['enquiry_date'] === null || $record['enquiry_date'] === '') $record['enquiry_date'] = $now;
    if (!isset($record['created_at']) || $record['created_at'] === null || $record['created_at'] === '') $record['created_at'] = $now;
    $record['updated_at'] = $now;

    try {
        $columns = array_keys($record);
        $placeholders = array_fill(0, count($columns), '?');
        $db->prepare("INSERT INTO `partners` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $placeholders) . ")")->execute(array_values($record));
        logActivityDb('partners_save', $id);
        return ['success' => true, 'message' => 'Record saved', 'data' => dbOne("SELECT * FROM `partners` WHERE id = ?", [$id])];
    } catch (Throwable $e) {
        http_response_code(400);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function partnersGetDetail() {
    ensurePartnersSchema();
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $id = trim((string)($_GET['id'] ?? ''));
    if ($id === '') return ['success' => false, 'error' => 'Partner reference required'];
    $db = requireDb(['partners']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $row = dbOne("SELECT * FROM partners WHERE id = ?", [$id]);
    if (!$row) return ['success' => false, 'error' => 'Partner enquiry not found'];
    return ['success' => true, 'data' => normalizePartnerEnquiryRow($row, true)];
}

function getPartnerShowcase() {
    $db = requireDb(['partner_showcase']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $rows = dbAll("SELECT * FROM partner_showcase WHERE COALESCE(is_active, 1) = 1 ORDER BY sort_order ASC, company_name ASC");
    return ['success' => true, 'data' => $rows];
}

function partnerShowcaseGetAll() {
    $db = requireDb(['partner_showcase']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $rows = dbAll("SELECT * FROM partner_showcase ORDER BY sort_order ASC, updated_at DESC, company_name ASC");
    $stats = [
        'total' => count($rows),
        'visible' => (int)dbScalar("SELECT COUNT(*) FROM partner_showcase WHERE COALESCE(is_active, 1) = 1"),
        'hidden' => (int)dbScalar("SELECT COUNT(*) FROM partner_showcase WHERE COALESCE(is_active, 1) = 0")
    ];
    return ['success' => true, 'data' => $rows, 'stats' => $stats];
}

function partnerShowcaseSave() {
    $data = requestData();
    if (empty($data['company_name']) || empty($data['partnership_purpose']) || empty($data['delivered_value'])) {
        return ['success' => false, 'error' => 'Company name, partnership purpose, and delivered value are required'];
    }
    if (!isset($data['is_active'])) $data['is_active'] = 1;
    if (!isset($data['sort_order']) || $data['sort_order'] === '') $data['sort_order'] = 0;
    return dbUpsert('partner_showcase', ['id','company_name','logo','website_url','partnership_purpose','delivered_value','sort_order','is_active','created_at','updated_at'], $data, 'partner_showcase');
}

function partnerShowcaseDelete() {
    $db = requireDb(['partner_showcase']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $id = $_GET['id'] ?? '';
    if ($id === '') return ['success' => false, 'error' => 'Partner showcase ID required'];
    dbWrite("DELETE FROM partner_showcase WHERE id = ?", [$id]);
    logActivityDb('partner_showcase_delete', $id);
    return ['success' => true, 'message' => 'Partner showcase entry deleted'];
}

function partnersSaveEnquiry() {
    ensurePartnersSchema();
    $data = requestData();
    if (empty($data['company_name']) || empty($data['email'])) return ['success' => false, 'error' => 'Company name and email are required'];
    $data['company_name'] = trim((string)($data['company_name'] ?? ''));
    $data['contact_name'] = trim((string)($data['contact_name'] ?? ''));
    $data['email'] = trim((string)($data['email'] ?? ''));
    $data['phone'] = trim((string)($data['phone'] ?? ''));
    $data['country'] = trim((string)($data['country'] ?? ''));
    $data['expertise'] = trim((string)($data['expertise'] ?? ''));
    $data['portfolio_url'] = trim((string)($data['portfolio_url'] ?? ''));
    $data['notes'] = trim((string)($data['notes'] ?? ''));
    $data['rejection_reason'] = trim((string)($data['rejection_reason'] ?? ''));

    $isEdit = !empty($data['id']);
    $existing = null;
    if ($isEdit) {
        $existing = dbOne("SELECT * FROM partners WHERE id = ?", [$data['id']]);
        if (!$existing) return ['success' => false, 'error' => 'Partner enquiry not found'];
    }

    if ($isEdit) {
        if (!array_key_exists('status', $data) || $data['status'] === '') $data['status'] = $existing['status'] ?? 'enquiry';
        if (!array_key_exists('enquiry_date', $data) || $data['enquiry_date'] === '') $data['enquiry_date'] = $existing['enquiry_date'] ?? date('Y-m-d H:i:s');
        if (!array_key_exists('access_scope', $data)) $data['access_scope'] = $existing['access_scope'] ?? null;
        if (!array_key_exists('nda_signed', $data)) $data['nda_signed'] = $existing['nda_signed'] ?? 0;
        if (!array_key_exists('nda_date', $data)) $data['nda_date'] = $existing['nda_date'] ?? null;
        if (!array_key_exists('approved_date', $data)) $data['approved_date'] = $existing['approved_date'] ?? null;
        if (!array_key_exists('approved_by', $data)) $data['approved_by'] = $existing['approved_by'] ?? null;
        if (!array_key_exists('created_at', $data)) $data['created_at'] = $existing['created_at'] ?? null;
        if (!array_key_exists('document_bundle_path', $data)) $data['document_bundle_path'] = $existing['document_bundle_path'] ?? null;
        if (!array_key_exists('document_bundle_name', $data)) $data['document_bundle_name'] = $existing['document_bundle_name'] ?? null;
        if (!array_key_exists('document_manifest', $data)) $data['document_manifest'] = $existing['document_manifest'] ?? null;
        if (!array_key_exists('document_extract_dir', $data)) $data['document_extract_dir'] = $existing['document_extract_dir'] ?? null;
        if (!array_key_exists('rejection_reason', $data) || $data['rejection_reason'] === '') $data['rejection_reason'] = $existing['rejection_reason'] ?? null;
    } else {
        $data['status'] = $data['status'] ?? 'enquiry';
        $data['enquiry_date'] = $data['enquiry_date'] ?? date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    if (($data['status'] ?? 'enquiry') !== 'rejected') {
        $data['rejection_reason'] = null;
    }

    $bundleInput = $_FILES['partner_bundle'] ?? null;
    if ($bundleInput && !empty($bundleInput['tmp_name'])) {
        $ext = strtolower(pathinfo($bundleInput['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            return ['success' => false, 'error' => 'Partner supporting documents must be uploaded as a ZIP file'];
        }
        if (($bundleInput['size'] ?? 0) > 15 * 1024 * 1024) {
            return ['success' => false, 'error' => 'ZIP package is too large (max 15MB)'];
        }

        $partnerId = trim((string)($data['id'] ?? ''));
        if ($partnerId === '') $partnerId = 'partner_' . uniqid();
        $companySlug = preg_replace('/[^a-z0-9_-]/i', '_', trim((string)($data['company_name'] ?? 'partner')));
        if ($companySlug === '') $companySlug = 'partner';
        $partnerDir = UPLOADS_DIR . 'partners/' . $companySlug . '/' . $partnerId . '/';
        $documentsDir = $partnerDir . 'documents/';
        if (!is_dir($partnerDir) && !mkdir($partnerDir, 0755, true) && !is_dir($partnerDir)) {
            return ['success' => false, 'error' => 'Partner document storage could not be prepared'];
        }

        $originalName = trim((string)($bundleInput['name'] ?? 'partner-documents.zip'));
        $safeBaseName = preg_replace('/[^a-z0-9._-]/i', '_', pathinfo($originalName, PATHINFO_FILENAME));
        if ($safeBaseName === '') $safeBaseName = 'partner_documents';
        $bundleFilename = $safeBaseName . '.zip';
        $bundleAbsolutePath = $partnerDir . $bundleFilename;
        $bundleRelativePath = normalizeApplicationRelativePath('partners/' . $companySlug . '/' . $partnerId . '/' . $bundleFilename);
        $documentsRelativeDir = normalizeApplicationRelativePath('partners/' . $companySlug . '/' . $partnerId . '/documents');

        if (!move_uploaded_file($bundleInput['tmp_name'], $bundleAbsolutePath)) {
            return ['success' => false, 'error' => 'Partner ZIP upload failed. Please try again.'];
        }

        $extractError = null;
        $documents = extractUploadedDocumentBundle($bundleAbsolutePath, $documentsDir, $extractError);
        if ($extractError !== null) {
            @unlink($bundleAbsolutePath);
            deleteUploadDirectoryTree($documentsDir);
            return ['success' => false, 'error' => $extractError];
        }

        $data['id'] = $partnerId;
        $data['document_bundle_path'] = $bundleRelativePath;
        $data['document_bundle_name'] = $originalName;
        $data['document_manifest'] = json_encode($documents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $data['document_extract_dir'] = $documentsRelativeDir;
    }

    $result = savePartnerEnquiryRecord($data, $isEdit);
    if (!$isEdit) {
        $settings = getSettingsArray();
        if (!empty($settings['hr_admin_email'])) {
            try { @mail($settings['hr_admin_email'], 'New partner enquiry', 'New partner enquiry from ' . $data['company_name']); } catch (Throwable $e) { logActivityDb('mail_failure', $e->getMessage()); }
        }
    }
    return $result;
}

function partnersUpdateStatus() {
    $data = requestData();
    $id = $_GET['id'] ?? ($data['id'] ?? '');
    $status = $data['status'] ?? 'under_review';
    $rejectionReason = trim((string)($data['rejection_reason'] ?? ''));
    if ($status === 'rejected' && $rejectionReason === '') {
        return ['success' => false, 'error' => 'A rejection reason is required'];
    }
    if ($status !== 'rejected') {
        $rejectionReason = null;
    }
    dbWrite("UPDATE partners SET status=?, access_scope=?, approved_by=IF(? IN ('approved','active'), ?, approved_by), approved_date=IF(? IN ('approved','active'), NOW(), approved_date), rejection_reason=?, notes=COALESCE(?, notes), updated_at=NOW() WHERE id=?", [$status, isset($data['access_scope']) ? json_encode($data['access_scope']) : null, $status, currentUsername(), $status, $rejectionReason, $data['notes'] ?? null, $id]);
    logActivityDb('partners_update_status', $id . ' ' . $status);
    return ['success' => true, 'message' => 'Partner status updated'];
}

function partnersDelete() {
    ensurePartnersSchema();
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $id = trim((string)($_GET['id'] ?? ''));
    if ($id === '') return ['success' => false, 'error' => 'Partner enquiry ID required'];
    $row = dbOne("SELECT * FROM partners WHERE id = ?", [$id]);
    if (!$row) return ['success' => false, 'error' => 'Partner enquiry not found'];
    $bundlePath = normalizeApplicationRelativePath($row['document_bundle_path'] ?? '');
    $extractDir = normalizeApplicationRelativePath($row['document_extract_dir'] ?? '');
    dbWrite("DELETE FROM partners WHERE id = ?", [$id]);
    if ($bundlePath !== '') {
        deleteUploadDirectoryTree(dirname(UPLOADS_DIR . $bundlePath));
    } elseif ($extractDir !== '') {
        deleteUploadDirectoryTree(dirname(UPLOADS_DIR . rtrim($extractDir, '/\\')));
    }
    logActivityDb('partners_delete', $id);
    return ['success' => true, 'message' => 'Partner enquiry deleted'];
}

function partnersGenerateNda() {
    $partner = dbOne("SELECT * FROM partners WHERE id=?", [$_GET['partner_id'] ?? '']);
    if (!$partner) printCompanyDocument('Non-Disclosure Agreement', '<p>Partner not found.</p>');
    dbWrite("UPDATE partners SET nda_signed=0, nda_date=CURDATE() WHERE id=?", [$partner['id']]);
    $settings = getSettingsArray();
    $title = trim((string)($settings['partner_nda_document_title'] ?? '')) ?: 'Non-Disclosure Agreement';
    $body = buildPartnerNdaBody($partner);
    logActivityDb('partners_generate_nda', $partner['id']);
    printCompanyDocument($title, $body);
}

function reportsGetHr() {
    return ['success' => true, 'data' => [
        'staff_by_department' => dbAll("SELECT COALESCE(department,'Unassigned') AS label, COUNT(*) AS value FROM staff GROUP BY department"),
        'leave_this_month' => dbAll("SELECT leave_type AS label, COUNT(*) AS value FROM leave_requests WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') GROUP BY leave_type"),
        'payroll' => dbOne("SELECT SUM(base_salary + allowances) AS gross, SUM(net_pay) AS net, SUM(tax) AS tax FROM payroll WHERE DATE_FORMAT(period_start, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"),
        'open_jobs' => (int)dbScalar("SELECT COUNT(*) FROM job_listings WHERE status='published'")
    ]];
}

function reportsGetProjects() {
    return ['success' => true, 'data' => [
        'summary' => dbOne("SELECT COUNT(*) AS active_count, AVG(progress_percent) AS avg_progress FROM branch_projects WHERE status='active'"),
        'by_status' => dbAll("SELECT status AS label, COUNT(*) AS value FROM branch_projects GROUP BY status"),
        'at_risk' => dbAll("SELECT bp.*, b.name AS branch_name FROM branch_projects bp LEFT JOIN branches b ON b.id=bp.branch_id WHERE progress_percent < 30 AND expected_end_date < DATE_ADD(NOW(), INTERVAL 30 DAY)")
    ]];
}

function reportsGetExpenses() {
    return ['success' => true, 'data' => [
        'by_branch' => dbAll("SELECT b.name AS label, SUM(be.amount) AS value FROM branch_expenses be LEFT JOIN branches b ON b.id=be.branch_id WHERE DATE_FORMAT(be.expense_date, '%Y-%m')=DATE_FORMAT(NOW(), '%Y-%m') GROUP BY b.name"),
        'by_status' => dbAll("SELECT status AS label, SUM(amount) AS value FROM branch_expenses GROUP BY status"),
        'top_categories' => dbAll("SELECT category AS label, SUM(amount) AS value FROM branch_expenses GROUP BY category ORDER BY value DESC LIMIT 3")
    ]];
}

function reportsGetAssets() {
    return ['success' => true, 'data' => [
        'by_status' => dbAll("SELECT status AS label, COUNT(*) AS value FROM assets GROUP BY status"),
        'due_maintenance' => dbAll("SELECT m.*, a.asset_tag, a.name AS asset_name FROM asset_maintenance m LEFT JOIN assets a ON a.id=m.asset_id WHERE next_maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY next_maintenance_date"),
        'value' => dbOne("SELECT SUM(purchase_cost) AS purchase_cost, SUM(current_value) AS current_value FROM assets")
    ]];
}

function reportsGetPartners() {
    return ['success' => true, 'data' => [
        'by_status' => dbAll("SELECT status AS label, COUNT(*) AS value FROM partners GROUP BY status"),
        'by_country' => dbAll("SELECT COALESCE(country,'Unspecified') AS label, COUNT(*) AS value FROM partners GROUP BY country"),
        'recent_approvals' => dbAll("SELECT * FROM partners WHERE approved_date IS NOT NULL ORDER BY approved_date DESC LIMIT 5")
    ]];
}

function usersGetAll() {
    return [
        'success' => true,
        'data' => dbAll("SELECT id, username, email, role, is_active, created_at, updated_at, last_login FROM users ORDER BY username")
    ];
}

function usersSave() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $data = requestData();
    $data['username'] = trim($data['username'] ?? '');
    if (empty($data['username']) || (empty($data['id']) && empty($data['password']))) {
        return ['success' => false, 'error' => 'Username and password are required for new users'];
    }

    $id = $data['id'] ?? '';
    $isActive = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;
    $duplicate = dbOne("SELECT id FROM users WHERE username=? AND id<>? LIMIT 1", [$data['username'], $id ?: '']);
    if ($duplicate) return ['success' => false, 'error' => 'That admin username is already in use'];

    if ($id) {
        $sets = ['username=?', 'email=?', 'role=?', 'is_active=?', 'updated_at=NOW()'];
        $params = [$data['username'], $data['email'] ?? '', $data['role'] ?? 'admin', $isActive];
        if (!empty($data['password'])) {
            $sets[] = 'password=?';
            $sets[] = 'password_changed_at=NOW()';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $params[] = $id;
        dbWrite("UPDATE users SET " . implode(',', $sets) . " WHERE id=?", $params);
        $message = !empty($data['password']) ? 'User saved and password updated' : 'User saved; existing password kept';
    } else {
        dbWrite(
            "INSERT INTO users (id, username, password, email, role, is_active, created_at) VALUES (?,?,?,?,?,?,NOW())",
            ['user_' . uniqid(), $data['username'], password_hash($data['password'], PASSWORD_DEFAULT), $data['email'] ?? '', $data['role'] ?? 'admin', $isActive]
        );
        $message = 'User saved and password created';
    }

    logActivityDb('users_save', $data['username']);
    return ['success' => true, 'message' => $message];
}

function usersToggleActive() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    $id = $_GET['id'] ?? '';
    dbWrite("UPDATE users SET is_active = IF(is_active=1,0,1), updated_at=NOW() WHERE id=?", [$id]);
    logActivityDb('users_toggle_active', $id);
    return ['success' => true, 'message' => 'User status updated'];
}
// ============ PROJECT FUNCTIONS ============

function getProjects() {
    $db = requireDb(['projects']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    ensure_komagin_workflow_schema(get_db());
    ensureProjectCmsSchema();
    $category = $_GET['category'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $featured = $_GET['featured'] ?? false;
    $limit = isset($_GET['limit']) ? max(0, (int)$_GET['limit']) : null;
    $where = [];
    $params = [];
    if ($category !== 'all') {
        $where[] = 'p.category = ?';
        $params[] = $category;
    }
    if ($status !== 'all' && in_array($status, ['COMPLETED', 'PENDING'], true)) {
        $where[] = 'p.status = ?';
        $params[] = $status;
    }
    if ($featured === 'true') {
        $where[] = 'p.featured = 1';
    }
    if ($search !== '') {
        $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.location LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
    $hasBranchName = dbColumnExists('projects', 'branch_name');
    $hasBranchId   = dbColumnExists('projects', 'branch_id');
    if ($hasBranchName && $hasBranchId) {
        $select = "SELECT p.*, COALESCE(p.branch_name, b.name) AS branch_name FROM projects p LEFT JOIN branches b ON b.id COLLATE utf8mb4_unicode_ci = p.branch_id COLLATE utf8mb4_unicode_ci";
    } elseif ($hasBranchId) {
        $select = "SELECT p.*, b.name AS branch_name FROM projects p LEFT JOIN branches b ON b.id COLLATE utf8mb4_unicode_ci = p.branch_id COLLATE utf8mb4_unicode_ci";
    } else {
        $select = "SELECT p.* FROM projects p";
    }
    $projects = dbAll("{$select}{$whereSql} ORDER BY p.created_at DESC" . ($limit ? " LIMIT " . $limit : ''), $params);
    normalizeDbJsonFields($projects, ['technologies', 'scope_sections', 'gallery_images']);
    normalizeProjectCmsRows($projects);
    addUploadUrls($projects);
    return ['success' => true, 'data' => array_values($projects), 'total' => dbCount('projects'), 'filtered' => count($projects)];
}

function getProjectSingle() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        return ['success' => false, 'error' => 'Project ID required'];
    }

    $db = requireDb(['projects']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    ensureProjectCmsSchema();
    $project = dbOne("SELECT * FROM projects WHERE id=?", [$id]);
    if (!$project) return ['success' => false, 'error' => 'Project not found'];
    $rows = [$project];
    normalizeDbJsonFields($rows, ['technologies', 'scope_sections', 'gallery_images']);
    normalizeProjectCmsRows($rows);
    addUploadUrls($rows);
    return ['success' => true, 'data' => $rows[0]];
}

function createProject() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    if (empty($data['name'])) return ['success' => false, 'error' => 'Project name is required'];
    if (empty($data['description'])) return ['success' => false, 'error' => 'Project description is required'];

    $db = requireDb(['projects']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    ensure_komagin_workflow_schema(get_db());
    ensureProjectCmsSchema();
    $galleryImages = normalizeProjectGalleryPayload($data['gallery_images'] ?? []);
    $scopeSections = normalizeProjectScopePayload($data['scope_sections'] ?? [], trim($data['description'] ?? ''));
    $id = 'proj_' . time() . '_' . uniqid();
    $project = [
        'id' => $id,
        'name' => trim($data['name']),
        'description' => trim($data['description']),
        'location' => trim($data['location'] ?? ''),
        'category' => $data['category'] ?? 'subdivision',
        'status' => in_array($data['status'] ?? '', ['COMPLETED', 'PENDING']) ? $data['status'] : 'PENDING',
        'branch_id' => trim($data['branch_id'] ?? ''),
        'branch_name' => trim($data['branch_name'] ?? '') ?: null,
        'image' => $data['image'] ?? '',
        'gallery_images' => json_encode($galleryImages),
        'technologies' => json_encode($data['technologies'] ?? []),
        'scope_sections' => json_encode($scopeSections),
        'featured' => !empty($data['featured']) ? 1 : 0,
        'created_by' => $_SESSION['admin_username'] ?? 'Unknown'
    ];
    if (dbColumnExists('projects', 'branch_name')) {
        dbWrite("INSERT INTO projects (id,name,description,location,category,status,branch_id,branch_name,image,gallery_images,technologies,scope_sections,featured,created_at,updated_at,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),?)", array_values($project));
    } else {
        unset($project['branch_name']);
        dbWrite("INSERT INTO projects (id,name,description,location,category,status,branch_id,image,gallery_images,technologies,scope_sections,featured,created_at,updated_at,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),?)", array_values($project));
    }
    syncProjectToBranch($id, $project);
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('create_project', $id);
    return ['success' => true, 'message' => 'Project created successfully', 'data' => dbOne("SELECT * FROM projects WHERE id=?", [$id])];
}

function updateProject() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Project ID required'];
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    if (empty($data['name'])) return ['success' => false, 'error' => 'Project name is required'];

    $db = requireDb(['projects']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    ensure_komagin_workflow_schema(get_db());
    ensureProjectCmsSchema();
    $existing = dbOne("SELECT * FROM projects WHERE id=?", [$id]);
    if (!$existing) return ['success' => false, 'error' => 'Project not found'];
    $branchId = trim($data['branch_id'] ?? ($existing['branch_id'] ?? ''));
    $galleryImages = array_key_exists('gallery_images', $data)
        ? normalizeProjectGalleryPayload($data['gallery_images'])
        : normalizeProjectGalleryPayload($existing['gallery_images'] ?? []);
    $scopeSections = array_key_exists('scope_sections', $data)
        ? normalizeProjectScopePayload($data['scope_sections'], trim($data['description'] ?? ($existing['description'] ?? '')))
        : normalizeProjectScopePayload($existing['scope_sections'] ?? [], trim($existing['description'] ?? ''));
    $branchDisplayName = array_key_exists('branch_name', $data)
        ? (trim($data['branch_name']) ?: null)
        : ($existing['branch_name'] ?? null);
    if (dbColumnExists('projects', 'branch_name')) {
        dbWrite(
            "UPDATE projects SET name=?, description=?, location=?, category=?, status=?, branch_id=?, branch_name=?, image=?, gallery_images=?, technologies=?, scope_sections=?, featured=?, updated_at=NOW(), updated_by=? WHERE id=?",
            [
                trim($data['name']),
                trim($data['description'] ?? ''),
                trim($data['location'] ?? ''),
                $data['category'] ?? $existing['category'],
                in_array($data['status'] ?? '', ['COMPLETED', 'PENDING']) ? $data['status'] : ($existing['status'] ?? 'PENDING'),
                $branchId ?: null,
                $branchDisplayName,
                $data['image'] ?? $existing['image'],
                json_encode($galleryImages),
                json_encode($data['technologies'] ?? json_decode($existing['technologies'] ?? '[]', true) ?: []),
                json_encode($scopeSections),
                !empty($data['featured']) ? 1 : 0,
                $_SESSION['admin_username'] ?? 'Unknown',
                $id
            ]
        );
    } else {
        dbWrite(
            "UPDATE projects SET name=?, description=?, location=?, category=?, status=?, branch_id=?, image=?, gallery_images=?, technologies=?, scope_sections=?, featured=?, updated_at=NOW(), updated_by=? WHERE id=?",
            [
                trim($data['name']),
                trim($data['description'] ?? ''),
                trim($data['location'] ?? ''),
                $data['category'] ?? $existing['category'],
                in_array($data['status'] ?? '', ['COMPLETED', 'PENDING']) ? $data['status'] : ($existing['status'] ?? 'PENDING'),
                $branchId ?: null,
                $data['image'] ?? $existing['image'],
                json_encode($galleryImages),
                json_encode($data['technologies'] ?? json_decode($existing['technologies'] ?? '[]', true) ?: []),
                json_encode($scopeSections),
                !empty($data['featured']) ? 1 : 0,
                $_SESSION['admin_username'] ?? 'Unknown',
                $id
            ]
        );
    }
    if ($branchId) {
        syncProjectToBranch($id, [
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'branch_id' => $branchId,
            'category' => $data['category'] ?? $existing['category']
        ]);
    } else {
        unsyncProjectFromBranch($id);
    }
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('update_project', $id);
    return ['success' => true, 'message' => 'Project updated successfully'];
}

function deleteProject() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Project ID required'];

    $db = requireDb(['projects']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    unsyncProjectFromBranch($id);
    dbWrite("DELETE FROM projects WHERE id=?", [$id]);
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('delete_project', $id);
    return ['success' => true, 'message' => 'Project deleted successfully'];
}

// ============ PROJECT CATEGORY FUNCTIONS ============

function getProjectCategories() {
    ensureProjectCmsSchema();
    if (!dbTableExists('project_categories')) {
        return ['success' => true, 'data' => []];
    }
    $cats = dbAll("SELECT * FROM project_categories ORDER BY sort_order ASC, name ASC");
    return ['success' => true, 'data' => $cats ?: []];
}

function saveProjectCategory() {
    $data = requestData();
    $name = trim((string)($data['name'] ?? ''));
    if ($name === '') return ['success' => false, 'error' => 'Category name is required'];

    $slug = trim((string)($data['slug'] ?? ''));
    if ($slug === '') $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name));
    $slug = preg_replace('/[^a-z0-9_]/', '', strtolower($slug));
    if ($slug === '') return ['success' => false, 'error' => 'Invalid slug derived from name'];

    $description = trim((string)($data['description'] ?? ''));
    $sortOrder   = (int)($data['sort_order'] ?? 0);

    ensureProjectCmsSchema();

    $id = trim((string)($data['id'] ?? ''));
    if ($id !== '') {
        $existing = dbOne("SELECT * FROM project_categories WHERE id=?", [$id]);
        if (!$existing) return ['success' => false, 'error' => 'Category not found'];
        $dup = dbOne("SELECT id FROM project_categories WHERE slug=? AND id!=?", [$slug, $id]);
        if ($dup) return ['success' => false, 'error' => 'A category with this slug already exists'];
        dbWrite(
            "UPDATE project_categories SET name=?, slug=?, description=?, sort_order=?, updated_at=NOW() WHERE id=?",
            [$name, $slug, $description, $sortOrder, $id]
        );
        logActivityDb('save_project_category', $id);
        return ['success' => true, 'message' => 'Category updated', 'data' => dbOne("SELECT * FROM project_categories WHERE id=?", [$id])];
    }

    $dup = dbOne("SELECT id FROM project_categories WHERE slug=?", [$slug]);
    if ($dup) return ['success' => false, 'error' => 'A category with this slug already exists'];
    $newId = 'projcat_' . substr(md5(uniqid('', true)), 0, 10);
    dbWrite(
        "INSERT INTO project_categories (id,name,slug,description,sort_order,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())",
        [$newId, $name, $slug, $description, $sortOrder]
    );
    logActivityDb('save_project_category', $newId);
    return ['success' => true, 'message' => 'Category created', 'data' => dbOne("SELECT * FROM project_categories WHERE id=?", [$newId])];
}

function deleteProjectCategory() {
    $id = $_GET['id'] ?? ($_POST['id'] ?? '');
    if (!$id) return ['success' => false, 'error' => 'Category ID required'];
    ensureProjectCmsSchema();
    $cat = dbOne("SELECT * FROM project_categories WHERE id=?", [$id]);
    if (!$cat) return ['success' => false, 'error' => 'Category not found'];
    $inUse = (int)dbScalar("SELECT COUNT(*) FROM projects WHERE category=?", [$cat['slug']]);
    if ($inUse > 0) {
        return ['success' => false, 'error' => "Cannot delete: {$inUse} project(s) use this category. Reassign them first."];
    }
    dbWrite("DELETE FROM project_categories WHERE id=?", [$id]);
    logActivityDb('delete_project_category', $id);
    return ['success' => true, 'message' => 'Category deleted'];
}

// ============ SERVICE FUNCTIONS ============

function getServices() {
    $db = requireDb(['services']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    ensureServiceDetailsSchema();

    $category = $_GET['category'] ?? 'all';
    $limit = isset($_GET['limit']) ? max(0, (int)$_GET['limit']) : null;
    $params = [];
    $where = '';
    if ($category !== 'all') {
        $where = ' WHERE category=?';
        $params[] = $category;
    }
    $services = dbAll("SELECT * FROM services{$where} ORDER BY `order` ASC, created_at DESC" . ($limit ? " LIMIT " . $limit : ''), $params);
    normalizeServiceRows($services);
    addUploadUrls($services);
    return ['success' => true, 'data' => array_values($services)];
}

function getServiceSingle() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Service ID required'];

    $db = requireDb(['services']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    ensureServiceDetailsSchema();

    $service = dbOne("SELECT * FROM services WHERE id=?", [$id]);
    if (!$service) return ['success' => false, 'error' => 'Service not found'];
    $rows = [$service];
    normalizeServiceRows($rows);
    addUploadUrls($rows);
    return ['success' => true, 'data' => $rows[0]];
}

function createService() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    if (empty($data['name'])) return ['success' => false, 'error' => 'Service name is required'];
    if (empty($data['description'])) return ['success' => false, 'error' => 'Service description is required'];

    $db = requireDb(['services']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    ensureServiceDetailsSchema();

    $id = 'svc_' . time() . '_' . uniqid();
    $nextOrder = (int)dbScalar("SELECT COALESCE(MAX(`order`),0) + 1 FROM services");
    $detailIntro = trim((string)($data['detail_intro'] ?? ''));
    $detailSections = normalizeServiceDetailSectionsPayload($data['detail_sections'] ?? [], trim($data['description'] ?? ''));
    dbWrite(
        "INSERT INTO services (id,name,description,detail_intro,detail_sections,category,icon,image,featured,`order`,created_at,updated_at,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),?)",
        [$id, trim($data['name']), trim($data['description']), $detailIntro, json_encode($detailSections), $data['category'] ?? 'core', $data['icon'] ?? 'fa-cog', $data['image'] ?? '', !empty($data['featured']) ? 1 : 0, $nextOrder, $_SESSION['admin_username'] ?? 'Unknown']
    );
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('create_service', $id);
    return ['success' => true, 'message' => 'Service created successfully', 'data' => dbOne("SELECT * FROM services WHERE id=?", [$id])];
}

function updateService() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Service ID required'];
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    if (empty($data['name'])) return ['success' => false, 'error' => 'Service name is required'];

    $db = requireDb(['services']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    ensureServiceDetailsSchema();

    $existing = dbOne("SELECT * FROM services WHERE id=?", [$id]);
    if (!$existing) return ['success' => false, 'error' => 'Service not found'];
    $detailIntro = array_key_exists('detail_intro', $data)
        ? trim((string)$data['detail_intro'])
        : trim((string)($existing['detail_intro'] ?? ''));
    $detailSections = array_key_exists('detail_sections', $data)
        ? normalizeServiceDetailSectionsPayload($data['detail_sections'], trim($data['description'] ?? ($existing['description'] ?? '')))
        : normalizeServiceDetailSectionsPayload($existing['detail_sections'] ?? [], trim($existing['description'] ?? ''));
    dbWrite(
        "UPDATE services SET name=?, description=?, detail_intro=?, detail_sections=?, category=?, icon=?, image=?, featured=?, `order`=?, updated_at=NOW(), updated_by=? WHERE id=?",
        [
            trim($data['name']),
            trim($data['description'] ?? ''),
            $detailIntro,
            json_encode($detailSections),
            $data['category'] ?? $existing['category'],
            $data['icon'] ?? $existing['icon'],
            $data['image'] ?? $existing['image'],
            array_key_exists('featured', $data) ? (!empty($data['featured']) ? 1 : 0) : (int)($existing['featured'] ?? 0),
            (int)($data['order'] ?? $existing['order']),
            $_SESSION['admin_username'] ?? 'Unknown',
            $id
        ]
    );
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('update_service', $id);
    return ['success' => true, 'message' => 'Service updated successfully'];
}

function deleteService() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Service ID required'];

    $db = requireDb(['services']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    dbWrite("DELETE FROM services WHERE id=?", [$id]);
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('delete_service', $id);
    return ['success' => true, 'message' => 'Service deleted successfully'];
}

// ============ TESTIMONIAL FUNCTIONS ============

function getTestimonials() {
    $db = requireDb(['testimonials']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $rows = dbAll("SELECT * FROM testimonials ORDER BY created_at DESC");
    addUploadUrls($rows);
    return ['success' => true, 'data' => array_values($rows)];
}

function createTestimonial() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    if (empty($data['name'])) return ['success' => false, 'error' => 'Name is required'];
    if (empty($data['content'])) return ['success' => false, 'error' => 'Testimonial content is required'];

    $db = requireDb(['testimonials']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $id = 'test_' . time() . '_' . uniqid();
    $imagePath = normalizeProjectCmsAssetPath($data['image'] ?? '');
    dbWrite(
        "INSERT INTO testimonials (id,name,role,content,rating,image,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())",
        [$id, trim($data['name']), trim($data['role'] ?? ''), trim($data['content']), (int)($data['rating'] ?? 5), $imagePath]
    );
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('create_testimonial', $id);
    return ['success' => true, 'message' => 'Testimonial created successfully', 'data' => dbOne("SELECT * FROM testimonials WHERE id=?", [$id])];
}

function updateTestimonial() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Testimonial ID required'];
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    if (empty($data['name'])) return ['success' => false, 'error' => 'Name is required'];
    if (empty($data['content'])) return ['success' => false, 'error' => 'Testimonial content is required'];

    $db = requireDb(['testimonials']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $existing = dbOne("SELECT * FROM testimonials WHERE id=?", [$id]);
    if (!$existing) return ['success' => false, 'error' => 'Testimonial not found'];
    $imagePath = array_key_exists('image', $data)
        ? normalizeProjectCmsAssetPath($data['image'] ?? '')
        : ($existing['image'] ?? '');
    dbWrite(
        "UPDATE testimonials SET name=?, role=?, content=?, rating=?, image=?, updated_at=NOW() WHERE id=?",
        [trim($data['name']), trim($data['role'] ?? ''), trim($data['content']), (int)($data['rating'] ?? 5), $imagePath, $id]
    );
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('update_testimonial', $id);
    return ['success' => true, 'message' => 'Testimonial updated successfully'];
}

function deleteTestimonial() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Testimonial ID required'];

    $db = requireDb(['testimonials']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    dbWrite("DELETE FROM testimonials WHERE id=?", [$id]);
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('delete_testimonial', $id);
    return ['success' => true, 'message' => 'Testimonial deleted successfully'];
}

// ============ TEAM FUNCTIONS ============

function getTeam() {
    $db = requireDb(['team']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $team = dbAll("SELECT * FROM team ORDER BY `order` ASC, created_at DESC");
    addUploadUrls($team);
    return ['success' => true, 'data' => array_values($team)];
}

function createTeam() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    if (empty($data['name'])) return ['success' => false, 'error' => 'Team member name is required'];
    if (empty($data['position'])) return ['success' => false, 'error' => 'Position is required'];

    $db = requireDb(['team']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $id = 'team_' . time() . '_' . uniqid();
    $nextOrder = (int)dbScalar("SELECT COALESCE(MAX(`order`),0) + 1 FROM team");
    $imagePath = normalizeProjectCmsAssetPath($data['image'] ?? '');
    dbWrite(
        "INSERT INTO team (id,name,position,bio,image,email,`order`,created_at,updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())",
        [$id, trim($data['name']), trim($data['position']), trim($data['bio'] ?? ''), $imagePath, trim($data['email'] ?? ''), $nextOrder]
    );
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('create_team', $id);
    return ['success' => true, 'message' => 'Team member added successfully', 'data' => dbOne("SELECT * FROM team WHERE id=?", [$id])];
}

function updateTeam() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Team member ID required'];
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    if (empty($data['name'])) return ['success' => false, 'error' => 'Team member name is required'];

    $db = requireDb(['team']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $existing = dbOne("SELECT * FROM team WHERE id=?", [$id]);
    if (!$existing) return ['success' => false, 'error' => 'Team member not found'];
    $imagePath = array_key_exists('image', $data)
        ? normalizeProjectCmsAssetPath($data['image'] ?? '')
        : ($existing['image'] ?? '');
    dbWrite(
        "UPDATE team SET name=?, position=?, bio=?, image=?, email=?, `order`=?, updated_at=NOW() WHERE id=?",
        [trim($data['name']), trim($data['position'] ?? ''), trim($data['bio'] ?? ''), $imagePath, trim($data['email'] ?? ''), (int)($data['order'] ?? $existing['order']), $id]
    );
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('update_team', $id);
    return ['success' => true, 'message' => 'Team member updated successfully'];
}

function deleteTeam() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Team member ID required'];

    $db = requireDb(['team']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    dbWrite("DELETE FROM team WHERE id=?", [$id]);
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('delete_team', $id);
    return ['success' => true, 'message' => 'Team member deleted successfully'];
}

// ============ DOCUMENT FUNCTIONS ============

function defaultDocumentRecords() {
    return [
        [
            'id' => 'doc_registration',
            'title' => 'Business Registration Certificate',
            'type' => 'registration',
            'category' => 'legal',
            'summary' => 'Placeholder for the official company business registration certificate and related registration evidence.',
            'icon' => 'fa-file-certificate',
            'sort_order' => 1,
            'is_visible' => 1
        ],
        [
            'id' => 'doc_tax',
            'title' => 'Tax Compliance Letter',
            'type' => 'tax',
            'category' => 'compliance',
            'summary' => 'Placeholder for the latest tax compliance confirmation and supporting documentation.',
            'icon' => 'fa-file-invoice',
            'sort_order' => 2,
            'is_visible' => 1
        ],
        [
            'id' => 'doc_labour',
            'title' => 'Labour Law References',
            'type' => 'labour',
            'category' => 'compliance',
            'summary' => 'Placeholder for employment, labour, and workforce-related compliance references published on the website.',
            'icon' => 'fa-file-contract',
            'sort_order' => 3,
            'is_visible' => 1
        ],
        [
            'id' => 'doc_governance',
            'title' => 'Corporate Governance Summary',
            'type' => 'governance',
            'category' => 'governance',
            'summary' => 'Placeholder for the public-facing corporate governance summary and board-level compliance overview.',
            'icon' => 'fa-file-alt',
            'sort_order' => 4,
            'is_visible' => 1
        ]
    ];
}

function ensureDocumentsSchema() {
    $db = requireDb(['documents']);
    if (is_array($db)) return $db;

    if (!dbColumnExists('documents', 'category')) {
        dbWrite("ALTER TABLE documents ADD COLUMN category VARCHAR(100) DEFAULT 'legal' AFTER type");
    }
    if (!dbColumnExists('documents', 'summary')) {
        dbWrite("ALTER TABLE documents ADD COLUMN summary TEXT DEFAULT NULL AFTER category");
    }
    if (!dbColumnExists('documents', 'icon')) {
        dbWrite("ALTER TABLE documents ADD COLUMN icon VARCHAR(100) DEFAULT 'fa-file-alt' AFTER summary");
    }
    if (!dbColumnExists('documents', 'sort_order')) {
        dbWrite("ALTER TABLE documents ADD COLUMN sort_order INT DEFAULT 0 AFTER icon");
    }
    if (!dbColumnExists('documents', 'is_visible')) {
        dbWrite("ALTER TABLE documents ADD COLUMN is_visible TINYINT(1) DEFAULT 1 AFTER sort_order");
    }
    if (!dbColumnExists('documents', 'allow_public_download')) {
        dbWrite("ALTER TABLE documents ADD COLUMN allow_public_download TINYINT(1) DEFAULT 0 AFTER is_visible");
    }

    foreach (defaultDocumentRecords() as $doc) {
        if (!dbOne("SELECT id FROM documents WHERE type = ?", [$doc['type']])) {
            dbWrite(
                "INSERT INTO documents (id,title,type,category,summary,icon,sort_order,is_visible,filename,file_url,file_size,mime_type,created_at,updated_at,updated_by) VALUES (?,?,?,?,?,?,?,?,NULL,NULL,NULL,NULL,NOW(),NOW(),'system')",
                [$doc['id'], $doc['title'], $doc['type'], $doc['category'], $doc['summary'], $doc['icon'], $doc['sort_order'], $doc['is_visible']]
            );
        } else {
            dbWrite(
                "UPDATE documents
                 SET category = COALESCE(NULLIF(category,''), ?),
                     summary = CASE WHEN summary IS NULL OR summary = '' THEN ? ELSE summary END,
                     icon = COALESCE(NULLIF(icon,''), ?),
                     sort_order = CASE WHEN sort_order IS NULL THEN ? ELSE sort_order END,
                     is_visible = COALESCE(is_visible, 1),
                     title = COALESCE(NULLIF(title,''), ?)
                 WHERE type = ?",
                [$doc['category'], $doc['summary'], $doc['icon'], $doc['sort_order'], $doc['title'], $doc['type']]
            );
        }
    }

    return true;
}

function getDocuments() {
    $db = ensureDocumentsSchema();
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $documents = dbAll("SELECT * FROM documents WHERE is_visible = 1 ORDER BY sort_order ASC, title ASC");
    addUploadUrls($documents);
    return ['success' => true, 'data' => array_values($documents)];
}

function documentsGetAll() {
    $db = ensureDocumentsSchema();
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $documents = dbAll("SELECT * FROM documents ORDER BY sort_order ASC, title ASC");
    addUploadUrls($documents);
    return [
        'success' => true,
        'data' => array_values($documents),
        'stats' => [
            'total' => count($documents),
            'published' => count(array_filter($documents, fn($doc) => (int)($doc['is_visible'] ?? 1) === 1)),
            'with_files' => count(array_filter($documents, fn($doc) => !empty($doc['filename']))),
            'placeholders' => count(array_filter($documents, fn($doc) => empty($doc['filename'])))
        ]
    ];
}

function getDocument() {
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? '';
    if (empty($type) && empty($id)) return ['success' => false, 'error' => 'Document reference required'];

    $db = ensureDocumentsSchema();
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $doc = $id
        ? dbOne("SELECT * FROM documents WHERE id = ?", [$id])
        : dbOne("SELECT * FROM documents WHERE type = ?", [$type]);
    if (!$doc) return ['success' => false, 'error' => 'Document not found'];
    $rows = [$doc];
    addUploadUrls($rows);
    return ['success' => true, 'data' => $rows[0]];
}

function documentSave() {
    $db = ensureDocumentsSchema();
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $data = requestData();
    $title = trim((string)($data['title'] ?? ''));
    if ($title === '') return ['success' => false, 'error' => 'Document title is required'];

    $type = trim((string)($data['type'] ?? ''));
    if ($type === '') {
        $type = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $title), '_'));
    }
    if ($type === '') return ['success' => false, 'error' => 'Document key could not be generated'];

    $type = substr($type, 0, 50);
    $existing = dbOne("SELECT id, filename, file_url, file_size, mime_type, created_at FROM documents WHERE type = ? AND id <> ?", [$type, $data['id'] ?? '']);
    if ($existing) return ['success' => false, 'error' => 'Another document already uses this key'];

    $record = [
        'id' => trim((string)($data['id'] ?? '')),
        'title' => $title,
        'type' => $type,
        'category' => trim((string)($data['category'] ?? 'legal')) ?: 'legal',
        'summary' => trim((string)($data['summary'] ?? '')),
        'icon' => trim((string)($data['icon'] ?? 'fa-file-alt')) ?: 'fa-file-alt',
        'sort_order' => isset($data['sort_order']) ? (int)$data['sort_order'] : 0,
        'is_visible' => isset($data['is_visible']) ? (int)$data['is_visible'] : 1,
        'allow_public_download' => isset($data['allow_public_download']) ? (int)$data['allow_public_download'] : 0,
        'filename' => $existing['filename'] ?? ($data['filename'] ?? null),
        'file_url' => $existing['file_url'] ?? ($data['file_url'] ?? null),
        'file_size' => $existing['file_size'] ?? ($data['file_size'] ?? null),
        'mime_type' => $existing['mime_type'] ?? ($data['mime_type'] ?? null),
        'updated_by' => currentUsername()
    ];

    if ($record['id'] === '') {
        $record['id'] = 'doc_' . uniqid();
    } else {
        $current = dbOne("SELECT filename, file_url, file_size, mime_type, created_at FROM documents WHERE id = ?", [$record['id']]);
        if ($current) {
            $record['filename'] = $current['filename'];
            $record['file_url'] = $current['file_url'];
            $record['file_size'] = $current['file_size'];
            $record['mime_type'] = $current['mime_type'];
            $record['created_at'] = $current['created_at'];
        }
    }

    $result = dbUpsert(
        'documents',
        ['id','title','type','category','summary','icon','sort_order','is_visible','allow_public_download','filename','file_url','file_size','mime_type','created_at','updated_at','updated_by'],
        $record,
        'doc'
    );
    if (!empty($result['success'])) {
        logActivityDb('save_document_record', $record['type']);
        $result['message'] = 'Document details saved';
    }
    return $result;
}

function uploadDocument() {
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error'];
    }
    
    $db = ensureDocumentsSchema();
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $type = trim((string)($_POST['type'] ?? ''));
    $id = trim((string)($_POST['id'] ?? ''));
    if (empty($type) && !empty($id)) {
        $existing = dbOne("SELECT type FROM documents WHERE id = ?", [$id]);
        $type = $existing['type'] ?? '';
    }
    if (empty($type)) return ['success' => false, 'error' => 'Document type required'];
    
    $file = $_FILES['document'];
    $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG'];
    }
    
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Maximum size: 10MB'];
    }
    
    $filename = 'doc_' . $type . '_' . date('Ymd_His') . '.' . $extension;
    $filepath = UPLOADS_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        if (!dbOne("SELECT id FROM documents WHERE type=?", [$type])) {
            dbWrite(
                "INSERT INTO documents (id,title,type,category,summary,icon,sort_order,is_visible,filename,file_url,file_size,mime_type,created_at,updated_at,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),?)",
                ['doc_' . uniqid(), ucfirst(str_replace('_', ' ', $type)), $type, 'legal', '', 'fa-file-alt', 0, 1, $filename, ADMIN_URL . 'uploads/' . $filename, $file['size'], $file['type'] ?? '', currentUsername()]
            );
        } else {
            dbWrite(
                "UPDATE documents SET filename=?, file_url=?, file_size=?, mime_type=?, updated_at=NOW(), updated_by=? WHERE type=?",
                [$filename, ADMIN_URL . 'uploads/' . $filename, $file['size'], $file['type'] ?? '', currentUsername(), $type]
            );
        }
        $_SESSION['admin_last_activity'] = time();
        logActivityDb('upload_document', $type);
        return ['success' => true, 'message' => 'Document uploaded successfully', 'filename' => $filename, 'file_url' => ADMIN_URL . 'uploads/' . $filename];
    }
    return ['success' => false, 'error' => 'Failed to upload document'];
}

function documentRemoveFile() {
    $db = ensureDocumentsSchema();
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? '';
    if ($type === '' && $id === '') return ['success' => false, 'error' => 'Document reference required'];

    $doc = $id
        ? dbOne("SELECT * FROM documents WHERE id=?", [$id])
        : dbOne("SELECT * FROM documents WHERE type=?", [$type]);
    if (!$doc) return ['success' => false, 'error' => 'Document not found'];
    if (!empty($doc['filename']) && file_exists(UPLOADS_DIR . $doc['filename'])) {
        unlink(UPLOADS_DIR . $doc['filename']);
    }
    dbWrite("UPDATE documents SET filename='', file_url='', file_size=NULL, mime_type=NULL, updated_at=NOW(), updated_by=? WHERE id=?", [currentUsername(), $doc['id']]);
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('remove_document_file', $doc['type']);
    return ['success' => true, 'message' => 'Document file removed'];
}

function deleteDocument() {
    $db = ensureDocumentsSchema();
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $id = $_GET['id'] ?? '';
    $type = $_GET['type'] ?? '';
    if ($id === '' && $type === '') return ['success' => false, 'error' => 'Document reference required'];

    $doc = $id
        ? dbOne("SELECT * FROM documents WHERE id=?", [$id])
        : dbOne("SELECT * FROM documents WHERE type=?", [$type]);
    if (!$doc) return ['success' => false, 'error' => 'Document not found'];
    if (!empty($doc['filename']) && file_exists(UPLOADS_DIR . $doc['filename'])) {
        unlink(UPLOADS_DIR . $doc['filename']);
    }
    dbWrite("DELETE FROM documents WHERE id=?", [$doc['id']]);
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('delete_document_record', $doc['type']);
    return ['success' => true, 'message' => 'Document deleted successfully'];
}

// ============ CSR / COMMUNITY FUNCTIONS ============

function defaultCsrItems() {
    return [
        [
            'id' => 'csr_plowshares',
            'title' => 'Plowshares Ministry International',
            'description' => 'Supporting their mission of empowerment and faith development across Papua New Guinea.',
            'dropdown_header' => 'Community Partnership Focus',
            'dropdown_subheader' => 'A values-led partnership built around practical support, outreach, and long-term community strengthening.',
            'dropdown_bullets' => [
                'Supports family-focused outreach and community empowerment initiatives.',
                'Creates room for practical collaboration that benefits local networks.',
                'Allows the public website to explain the purpose and outcomes of the partnership clearly.'
            ],
            'icon' => 'fa-church',
            'image' => 'images/hero-bg.jpeg',
            'button_label' => 'Explore More',
            'sort_order' => 1,
            'is_active' => 1
        ],
        [
            'id' => 'csr_kongos_rugby',
            'title' => 'Komagin Kongos Rugby Club',
            'description' => 'Fostering unity, pride, and teamwork through sport in local communities.',
            'dropdown_header' => 'Sport and Community Development',
            'dropdown_subheader' => 'A community-facing initiative that uses sport to strengthen discipline, teamwork, and shared identity.',
            'dropdown_bullets' => [
                'Encourages teamwork, positive identity, and community pride.',
                'Highlights Komagin support for constructive youth and local engagement.',
                'Gives the website a simple way to present key outcomes without overcrowding the card.'
            ],
            'icon' => 'fa-football-ball',
            'image' => 'images/hero-bg.jpeg',
            'button_label' => 'Explore More',
            'sort_order' => 2,
            'is_active' => 1
        ]
    ];
}

function ensureCsrSchema() {
    try {
        $tableExists = dbTableExists('csr_items');
        dbWrite("CREATE TABLE IF NOT EXISTS csr_items (
            id VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            dropdown_header VARCHAR(255) DEFAULT NULL,
            dropdown_subheader TEXT DEFAULT NULL,
            dropdown_bullets LONGTEXT DEFAULT NULL,
            icon VARCHAR(100) DEFAULT 'fa-hands-helping',
            image VARCHAR(500) DEFAULT NULL,
            button_label VARCHAR(120) DEFAULT 'Explore More',
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            created_by VARCHAR(100) DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_csr_active (is_active),
            INDEX idx_csr_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        if (!dbColumnExists('csr_items', 'dropdown_header')) {
            dbWrite("ALTER TABLE csr_items ADD COLUMN dropdown_header VARCHAR(255) DEFAULT NULL AFTER description");
        }
        if (!dbColumnExists('csr_items', 'dropdown_subheader')) {
            dbWrite("ALTER TABLE csr_items ADD COLUMN dropdown_subheader TEXT DEFAULT NULL AFTER dropdown_header");
        }
        if (!dbColumnExists('csr_items', 'dropdown_bullets')) {
            dbWrite("ALTER TABLE csr_items ADD COLUMN dropdown_bullets LONGTEXT DEFAULT NULL AFTER dropdown_subheader");
        }
        if (!dbColumnExists('csr_items', 'button_label')) {
            dbWrite("ALTER TABLE csr_items ADD COLUMN button_label VARCHAR(120) DEFAULT 'Explore More' AFTER image");
        }

        if (!$tableExists && dbTableExists('csr_items') && dbCount('csr_items') === 0) {
            foreach (defaultCsrItems() as $item) {
                dbWrite(
                    "INSERT INTO csr_items (id,title,description,dropdown_header,dropdown_subheader,dropdown_bullets,icon,image,button_label,sort_order,is_active,created_at,updated_at,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),'system')",
                    [$item['id'], $item['title'], $item['description'], $item['dropdown_header'], $item['dropdown_subheader'], json_encode($item['dropdown_bullets']), $item['icon'], $item['image'], $item['button_label'], $item['sort_order'], $item['is_active']]
                );
            }
        }

        foreach (defaultCsrItems() as $item) {
            dbWrite(
                "UPDATE csr_items
                 SET dropdown_header = CASE WHEN dropdown_header IS NULL OR dropdown_header = '' THEN ? ELSE dropdown_header END,
                     dropdown_subheader = CASE WHEN dropdown_subheader IS NULL OR dropdown_subheader = '' THEN ? ELSE dropdown_subheader END,
                     dropdown_bullets = CASE WHEN dropdown_bullets IS NULL OR dropdown_bullets = '' THEN ? ELSE dropdown_bullets END,
                     button_label = CASE WHEN button_label IS NULL OR button_label = '' THEN ? ELSE button_label END
                 WHERE id = ?",
                [$item['dropdown_header'], $item['dropdown_subheader'], json_encode($item['dropdown_bullets']), $item['button_label'], $item['id']]
            );
        }
    } catch (Throwable $e) {
        // CSR content should fall back gracefully if the database is not ready.
    }
}

function csrPublicImage($image) {
    $image = trim((string)$image);
    if ($image === '') return 'images/hero-bg.jpeg';
    if (preg_match('#^https?://#i', $image) || substr($image, 0, 1) === '/') return $image;
    if (strpos($image, 'images/') === 0 || strpos($image, 'admin/') === 0) return $image;
    if (strpos($image, 'adminpanel/') === 0) return 'admin/' . ltrim(substr($image, strlen('adminpanel/')), '/');
    if (strpos($image, 'uploads/') === 0) return 'admin/' . $image;
    return 'admin/uploads/' . ltrim($image, '/');
}

function prepareCsrRowsForPublic($rows) {
    foreach ($rows as &$row) {
        $row['image'] = csrPublicImage($row['image'] ?? '');
        $bullets = $row['dropdown_bullets'] ?? [];
        if (is_string($bullets)) {
            $decoded = json_decode($bullets, true);
            $bullets = is_array($decoded) ? $decoded : preg_split('/\r\n|\r|\n/', $bullets);
        }
        if (!is_array($bullets)) $bullets = [];
        $row['dropdown_header'] = trim((string)($row['dropdown_header'] ?? $row['title'] ?? ''));
        $row['dropdown_subheader'] = trim((string)($row['dropdown_subheader'] ?? $row['description'] ?? ''));
        $row['dropdown_bullets'] = array_values(array_filter(array_map(static function ($bullet) {
            return trim((string)$bullet);
        }, $bullets)));
        $row['button_label'] = trim((string)($row['button_label'] ?? '')) ?: 'Explore More';
        $row['is_active'] = (int)($row['is_active'] ?? 1);
        $row['sort_order'] = (int)($row['sort_order'] ?? 0);
        unset($row['detail_content'], $row['gallery_images']);
    }
    return $rows;
}

function getCsrItems() {
    ensureCsrSchema();
    if (dbTableExists('csr_items')) {
        $rows = dbAll("SELECT * FROM csr_items WHERE is_active = 1 ORDER BY sort_order ASC, title ASC");
        return ['success' => true, 'data' => prepareCsrRowsForPublic($rows ?: [])];
    }
    return ['success' => true, 'data' => prepareCsrRowsForPublic(defaultCsrItems())];
}

function csrGetAll() {
    ensureCsrSchema();
    $db = requireDb(['csr_items']);
    if (is_array($db) && isset($db['error'])) return ['success' => false, 'error' => $db['error']];
    $rows = dbAll("SELECT * FROM csr_items ORDER BY sort_order ASC, title ASC");
    foreach ($rows as &$row) {
        $bullets = $row['dropdown_bullets'] ?? [];
        if (is_string($bullets)) {
            $decoded = json_decode($bullets, true);
            $bullets = is_array($decoded) ? $decoded : preg_split('/\r\n|\r|\n/', $bullets);
        }
        $row['dropdown_header'] = trim((string)($row['dropdown_header'] ?? ''));
        $row['dropdown_subheader'] = trim((string)($row['dropdown_subheader'] ?? ''));
        $row['dropdown_bullets'] = is_array($bullets) ? array_values(array_filter(array_map(static function ($bullet) {
            return trim((string)$bullet);
        }, $bullets))) : [];
        $row['button_label'] = trim((string)($row['button_label'] ?? '')) ?: 'Explore More';
        unset($row['detail_content'], $row['gallery_images']);
    }
    return [
        'success' => true,
        'data' => $rows,
        'stats' => [
            'total' => count($rows),
            'active' => (int)dbScalar("SELECT COUNT(*) FROM csr_items WHERE is_active = 1"),
            'hidden' => (int)dbScalar("SELECT COUNT(*) FROM csr_items WHERE is_active = 0")
        ]
    ];
}

function csrSave() {
    ensureCsrSchema();
    $data = requestData();
    if (empty($data['title'])) return ['success' => false, 'error' => 'CSR title is required'];
    $data['sort_order'] = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;
    $data['is_active'] = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    if (empty($data['icon'])) $data['icon'] = 'fa-hands-helping';
    $data['button_label'] = trim((string)($data['button_label'] ?? '')) ?: 'Explore More';
    $data['dropdown_header'] = trim((string)($data['dropdown_header'] ?? $data['title']));
    $data['dropdown_subheader'] = trim((string)($data['dropdown_subheader'] ?? ''));
    $bullets = $data['dropdown_bullets'] ?? [];
    if (is_string($bullets)) {
        $bullets = preg_split('/\r\n|\r|\n/', $bullets);
    }
    $data['dropdown_bullets'] = json_encode(array_values(array_filter(array_map(static function ($bullet) {
        return trim((string)$bullet);
    }, (array)$bullets))));
    $result = dbUpsert('csr_items', ['id','title','description','dropdown_header','dropdown_subheader','dropdown_bullets','icon','image','button_label','sort_order','is_active','created_at','updated_at','created_by'], $data, 'csr');
    if (!empty($result['success'])) $result['message'] = 'CSR item saved';
    return $result;
}

function csrDelete() {
    ensureCsrSchema();
    $id = $_GET['id'] ?? '';
    if ($id === '') return ['success' => false, 'error' => 'CSR item ID required'];
    dbWrite("DELETE FROM csr_items WHERE id = ?", [$id]);
    logActivityDb('csr_delete', $id);
    return ['success' => true, 'message' => 'CSR item deleted'];
}

function ensurePartnersSchema() {
    try {
        dbWrite("CREATE TABLE IF NOT EXISTS partners (
            id               VARCHAR(50)  NOT NULL,
            company_name     VARCHAR(255) DEFAULT NULL,
            contact_name     VARCHAR(255) DEFAULT NULL,
            email            VARCHAR(255) DEFAULT NULL,
            phone            VARCHAR(50)  DEFAULT NULL,
            country          VARCHAR(100) DEFAULT NULL,
            expertise        TEXT         DEFAULT NULL,
            portfolio_url    VARCHAR(500) DEFAULT NULL,
            status           VARCHAR(50)  DEFAULT 'enquiry',
            access_scope     TEXT         DEFAULT NULL,
            nda_signed       TINYINT(1)   DEFAULT 0,
            nda_date         DATE         DEFAULT NULL,
            document_bundle_path VARCHAR(500) DEFAULT NULL,
            document_bundle_name VARCHAR(255) DEFAULT NULL,
            document_manifest LONGTEXT DEFAULT NULL,
            document_extract_dir VARCHAR(500) DEFAULT NULL,
            rejection_reason VARCHAR(500) DEFAULT NULL,
            notes            TEXT         DEFAULT NULL,
            enquiry_date     DATETIME     DEFAULT NULL,
            approved_date    DATETIME     DEFAULT NULL,
            approved_by      VARCHAR(100) DEFAULT NULL,
            created_at       DATETIME     DEFAULT NULL,
            updated_at       DATETIME     DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_partners_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo = get_db();
        if (!komagin_column_exists($pdo, 'partners', 'created_at')) {
            $pdo->exec("ALTER TABLE partners ADD COLUMN created_at DATETIME DEFAULT NULL AFTER approved_by");
        }
        if (!komagin_column_exists($pdo, 'partners', 'updated_at')) {
            $pdo->exec("ALTER TABLE partners ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER created_at");
        }
        if (!komagin_column_exists($pdo, 'partners', 'document_bundle_path')) {
            $pdo->exec("ALTER TABLE partners ADD COLUMN document_bundle_path VARCHAR(500) DEFAULT NULL AFTER nda_date");
        }
        if (!komagin_column_exists($pdo, 'partners', 'document_bundle_name')) {
            $pdo->exec("ALTER TABLE partners ADD COLUMN document_bundle_name VARCHAR(255) DEFAULT NULL AFTER document_bundle_path");
        }
        if (!komagin_column_exists($pdo, 'partners', 'document_manifest')) {
            $pdo->exec("ALTER TABLE partners ADD COLUMN document_manifest LONGTEXT DEFAULT NULL AFTER document_bundle_name");
        }
        if (!komagin_column_exists($pdo, 'partners', 'document_extract_dir')) {
            $pdo->exec("ALTER TABLE partners ADD COLUMN document_extract_dir VARCHAR(500) DEFAULT NULL AFTER document_manifest");
        }
        if (!komagin_column_exists($pdo, 'partners', 'rejection_reason')) {
            $pdo->exec("ALTER TABLE partners ADD COLUMN rejection_reason VARCHAR(500) DEFAULT NULL AFTER document_extract_dir");
        }
    } catch (Throwable $e) {
        error_log('Partners schema error: ' . $e->getMessage());
    }
}

function ensurePartnerShowcaseSchema() {
    try {
        dbWrite("CREATE TABLE IF NOT EXISTS partner_showcase (
            id                   VARCHAR(50)  NOT NULL,
            company_name         VARCHAR(255) NOT NULL,
            logo                 VARCHAR(500) DEFAULT NULL,
            website_url          VARCHAR(500) DEFAULT NULL,
            partnership_purpose  TEXT         DEFAULT NULL,
            delivered_value      TEXT         DEFAULT NULL,
            sort_order           INT          DEFAULT 0,
            is_active            TINYINT(1)   DEFAULT 1,
            created_at           DATETIME     DEFAULT NULL,
            updated_at           DATETIME     DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_partner_showcase_active (is_active),
            KEY idx_partner_showcase_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        error_log('Partner showcase schema error: ' . $e->getMessage());
    }
}

function ensurePlantHireSchema() {
    $pdo = get_db();
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS hire_items (
            id VARCHAR(50) NOT NULL,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) DEFAULT 'equipment',
            short_description TEXT DEFAULT NULL,
            specifications TEXT DEFAULT NULL,
            rate_note VARCHAR(255) DEFAULT NULL,
            location VARCHAR(150) DEFAULT NULL,
            availability_status VARCHAR(50) DEFAULT 'available',
            operator_option VARCHAR(50) DEFAULT 'optional',
            delivery_option VARCHAR(50) DEFAULT 'available',
            image VARCHAR(500) DEFAULT NULL,
            tags TEXT DEFAULT NULL,
            featured TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            created_by VARCHAR(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_hire_category (category),
            KEY idx_hire_active (is_active),
            KEY idx_hire_featured (featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        error_log('Plant hire schema error: ' . $e->getMessage());
    }
}

function normalizeHireUploadPath($path) {
    $path = str_replace('\\', '/', trim((string)$path));
    if ($path === '') return '';
    if (preg_match('#^https?://#i', $path) || substr($path, 0, 1) === '/') return $path;
    foreach (['admin/uploads/', 'adminpanel/uploads/', 'uploads/'] as $prefix) {
        if (strpos($path, $prefix) === 0) {
            return ltrim(substr($path, strlen($prefix)), '/');
        }
    }
    return ltrim($path, '/');
}

function hireItemImageUrl($path) {
    $normalized = normalizeHireUploadPath($path);
    if ($normalized === '') return '';
    if (preg_match('#^https?://#i', $normalized) || substr($normalized, 0, 1) === '/') return $normalized;
    if (strpos($normalized, 'images/') === 0) return SITE_URL . ltrim($normalized, '/');
    if (strpos($normalized, 'admin/') === 0) return SITE_URL . ltrim($normalized, '/');
    return ADMIN_URL . 'uploads/' . ltrim($normalized, '/');
}

function normalizeHireItemRow(array $row): array {
    $row['category'] = (string)($row['category'] ?? 'other');
    $row['availability_status'] = (string)($row['availability_status'] ?? 'available');
    $row['operator_option'] = (string)($row['operator_option'] ?? 'optional');
    $row['delivery_option'] = (string)($row['delivery_option'] ?? 'available');
    $row['image'] = normalizeHireUploadPath($row['image'] ?? '');
    $row['featured'] = (int)($row['featured'] ?? 0);
    $row['is_active'] = (int)($row['is_active'] ?? 1);
    $row['sort_order'] = (int)($row['sort_order'] ?? 0);
    $row['image_url'] = !empty($row['image']) ? hireItemImageUrl($row['image']) : '';
    $row['tags_list'] = array_values(array_filter(array_map('trim', preg_split('/,/', (string)($row['tags'] ?? '')) ?: [])));
    return $row;
}

function hireGetItems() {
    ensurePlantHireSchema();
    $db = requireDb(['hire_items']);
    if (is_array($db) && isset($db['error'])) return ['success' => false, 'error' => $db['error']];
    $rows = dbAll("SELECT * FROM hire_items WHERE is_active = 1 ORDER BY featured DESC, sort_order ASC, name ASC");
    $rows = array_map('normalizeHireItemRow', $rows);
    return ['success' => true, 'data' => $rows];
}

function hireItemsGetAll() {
    ensurePlantHireSchema();
    $db = requireDb(['hire_items']);
    if (is_array($db) && isset($db['error'])) return ['success' => false, 'error' => $db['error']];
    $rows = dbAll("SELECT * FROM hire_items ORDER BY featured DESC, sort_order ASC, name ASC");
    $rows = array_map('normalizeHireItemRow', $rows);
    return [
        'success' => true,
        'data' => $rows,
        'stats' => [
            'total' => count($rows),
            'active' => (int)dbScalar("SELECT COUNT(*) FROM hire_items WHERE is_active = 1"),
            'featured' => (int)dbScalar("SELECT COUNT(*) FROM hire_items WHERE featured = 1 AND is_active = 1"),
            'categories' => (int)dbScalar("SELECT COUNT(DISTINCT category) FROM hire_items WHERE is_active = 1")
        ]
    ];
}

function hireItemSave() {
    ensurePlantHireSchema();
    $data = requestData();
    $data['name'] = trim((string)($data['name'] ?? ''));
    if ($data['name'] === '') return ['success' => false, 'error' => 'Equipment name is required'];
    $allowedCategories = ['excavators','graders','rollers','loaders','dump_trucks','bulldozers','cranes','water_carts','compactors','survey_support','survey_equipment','generators','support_vehicles','other'];
    $allowedAvailability = ['available','on_request','limited','booked'];
    $allowedOperator = ['included','optional','not_included'];
    $allowedDelivery = ['available','on_request','pickup_only'];
    $data['category'] = in_array((string)($data['category'] ?? ''), $allowedCategories, true) ? (string)$data['category'] : 'other';
    $data['availability_status'] = in_array((string)($data['availability_status'] ?? ''), $allowedAvailability, true) ? (string)$data['availability_status'] : 'available';
    $data['operator_option'] = in_array((string)($data['operator_option'] ?? ''), $allowedOperator, true) ? (string)$data['operator_option'] : 'optional';
    $data['delivery_option'] = in_array((string)($data['delivery_option'] ?? ''), $allowedDelivery, true) ? (string)$data['delivery_option'] : 'available';
    $data['short_description'] = trim((string)($data['short_description'] ?? ''));
    $data['specifications'] = trim((string)($data['specifications'] ?? ''));
    $data['rate_note'] = trim((string)($data['rate_note'] ?? ''));
    $data['location'] = trim((string)($data['location'] ?? ''));
    $data['image'] = normalizeHireUploadPath($data['image'] ?? '');
    $data['featured'] = isset($data['featured']) ? (int)$data['featured'] : 0;
    $data['sort_order'] = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;
    $data['is_active'] = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    if (isset($data['tags']) && is_array($data['tags'])) {
        $data['tags'] = implode(', ', array_map('trim', $data['tags']));
    } else {
        $data['tags'] = implode(', ', array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string)($data['tags'] ?? '')) ?: [])))));
    }
    $result = dbUpsert(
        'hire_items',
        ['id','name','category','short_description','specifications','rate_note','location','availability_status','operator_option','delivery_option','image','tags','featured','sort_order','is_active','created_at','updated_at','created_by'],
        $data,
        'hire'
    );
    if (!empty($result['success'])) {
        $result['message'] = 'Hire equipment item saved';
    }
    return $result;
}

function hireItemDelete() {
    ensurePlantHireSchema();
    $id = $_GET['id'] ?? '';
    if ($id === '') return ['success' => false, 'error' => 'Hire item ID required'];
    dbWrite("DELETE FROM hire_items WHERE id = ?", [$id]);
    logActivityDb('hire_item_delete', $id);
    return ['success' => true, 'message' => 'Hire equipment item deleted'];
}

// ============ CONTACT FUNCTIONS ============

function getContacts() {
    $db = requireDb(['contacts']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $status = $_GET['status'] ?? 'all';
    $params = [];
    $where = '';
    if ($status !== 'all') {
        $where = ' WHERE status=?';
        $params[] = $status;
    }
    return ['success' => true, 'data' => dbAll("SELECT * FROM contacts{$where} ORDER BY created_at DESC", $params)];
}

function submitContact() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;

    if (empty($data['name'])) return ['success' => false, 'error' => 'Name is required'];
    if (empty($data['email'])) return ['success' => false, 'error' => 'Email is required'];
    if (empty($data['message'])) return ['success' => false, 'error' => 'Message is required'];

    $db = requireDb(['contacts']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $id = 'contact_' . time() . '_' . uniqid();
    dbWrite(
        "INSERT INTO contacts (id,name,email,phone,subject,message,type,status,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())",
        [$id, trim($data['name']), trim($data['email']), trim($data['phone'] ?? ''), trim($data['subject'] ?? 'General Inquiry'), trim($data['message']), $data['type'] ?? 'contact', 'new']
    );
    logActivityDb('submit_contact', $id);
    return ['success' => true, 'message' => 'Thank you for your message. We will get back to you soon.', 'data' => dbOne("SELECT * FROM contacts WHERE id=?", [$id])];
}

function updateContactStatus() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Contact ID required'];

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;

    if (empty($data['status'])) return ['success' => false, 'error' => 'Status is required'];

    $db = requireDb(['contacts']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $existing = dbOne("SELECT id FROM contacts WHERE id=?", [$id]);
    if (!$existing) return ['success' => false, 'error' => 'Contact not found'];
    dbWrite("UPDATE contacts SET status=?, notes=?, updated_at=NOW(), updated_by=? WHERE id=?", [$data['status'], $data['notes'] ?? '', $_SESSION['admin_username'] ?? 'Unknown', $id]);
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('update_contact_status', $id);
    return ['success' => true, 'message' => 'Contact status updated successfully'];
}

function deleteContact() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) return ['success' => false, 'error' => 'Contact ID required'];

    $db = requireDb(['contacts']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    dbWrite("DELETE FROM contacts WHERE id=?", [$id]);
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('delete_contact', $id);
    return ['success' => true, 'message' => 'Contact deleted successfully'];
}

// ============ NEWSLETTER FUNCTIONS ============

function subscribeNewsletter() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    $email = strtolower(trim($data['email'] ?? ''));
    if (empty($email)) return ['success' => false, 'error' => 'Email address is required'];
    
    $emailRegex = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    if (!preg_match($emailRegex, $email)) return ['success' => false, 'error' => 'Invalid email address'];

    $db = requireDb(['newsletter']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $existing = dbOne("SELECT id,status FROM newsletter WHERE LOWER(email)=LOWER(?)", [$email]);
    if ($existing) {
        if (($existing['status'] ?? '') !== 'active') {
            dbWrite("UPDATE newsletter SET status='active', subscribed_at=NOW(), unsubscribed_at=NULL WHERE id=?", [$existing['id']]);
        }
        return ['success' => true, 'message' => 'You are already subscribed to our newsletter'];
    }
    dbWrite("INSERT INTO newsletter (id,email,status,subscribed_at) VALUES (?,?,?,NOW())", ['sub_' . time() . '_' . uniqid(), $email, 'active']);
    return ['success' => true, 'message' => 'Thank you for subscribing to our newsletter!'];
}

function getSubscribers() {
    $db = requireDb(['newsletter']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    return ['success' => true, 'data' => dbAll("SELECT * FROM newsletter ORDER BY subscribed_at DESC")];
}

function sendNewsletter() {
    checkAuthentication();
    
    $subject = $_POST['subject'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (empty($subject) || empty($content)) {
        return ['success' => false, 'error' => 'Subject and content are required'];
    }

    $db = requireDb(['newsletter']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $settings = getSettings()['data'] ?? [];
    $transport = strtolower((string)($settings['email_transport'] ?? 'php_mail'));
    if ($transport === 'smtp') {
        return [
            'success' => false,
            'error' => 'SMTP-linked delivery is verification-ready only in this admin. Complete final deployment mailer integration before sending newsletters from the linked mailbox.'
        ];
    }

    $attachments = saveNewsletterAttachments();
    $recipientType = strtolower((string)($_POST['recipient_type'] ?? 'all'));
    if ($recipientType === 'test') {
        $testRecipient = trim((string)($settings['smtp_test_recipient'] ?? $settings['store_email'] ?? ''));
        if ($testRecipient === '') {
            return ['success' => false, 'error' => 'Set a test recipient in Email Delivery & Verification first'];
        }
        $activeSubscribers = [['id' => 'test_delivery', 'email' => $testRecipient]];
    } elseif ($recipientType === 'active') {
        $activeSubscribers = dbAll("SELECT * FROM newsletter WHERE status='active' ORDER BY subscribed_at DESC");
    } else {
        $activeSubscribers = dbAll("SELECT * FROM newsletter ORDER BY subscribed_at DESC");
    }
    $recipientCount = count($activeSubscribers);
    if ($recipientCount === 0) {
        return ['success' => false, 'error' => $recipientType === 'test' ? 'No test recipient is configured' : 'No active subscribers found'];
    }
    $senderEmail = trim((string)($settings['smtp_from_email'] ?? '')) ?: ($settings['store_email'] ?? 'info@komagin.com');
    $senderName = trim((string)($settings['smtp_from_name'] ?? '')) ?: ($settings['store_name'] ?? 'Komagin Limited');
    $replyTo = trim((string)($settings['smtp_reply_to'] ?? ''));

    $sentCount = 0;
    foreach ($activeSubscribers as $subscriber) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $senderName . " <" . $senderEmail . ">\r\n";
        if ($replyTo !== '') $headers .= "Reply-To: " . $replyTo . "\r\n";
        $emailContent = buildNewsletterHtml($subject, $content, $attachments);
        if (mail($subscriber['email'], $subject, $emailContent, $headers)) {
            $sentCount++;
            if (($subscriber['id'] ?? '') !== 'test_delivery') {
                dbWrite("UPDATE newsletter SET last_email_sent=NOW() WHERE id=?", [$subscriber['id']]);
            }
        }
    }

    if ($recipientType !== 'test' && dbTableExists('newsletter_history')) {
        dbWrite(
            "INSERT INTO newsletter_history (id,subject,content,recipient_count,sent_count,failed_count,status,sent_at,sent_by) VALUES (?,?,?,?,?,?,?,NOW(),?)",
            ['news_' . uniqid(), $subject, $content, $recipientCount, $sentCount, $recipientCount - $sentCount, 'sent', $_SESSION['admin_username'] ?? 'Unknown']
        );
    }
    logActivityDb('send_newsletter', ['subject'=>$subject,'sent'=>$sentCount,'attachments'=>count($attachments)]);
    $message = $recipientType === 'test'
        ? "Test email sent to {$sentCount} recipient"
        : "Newsletter sent to {$sentCount} subscribers";
    return ['success' => true, 'message' => $message, 'recipient_count' => $sentCount, 'attachments' => $attachments];
}

function emailTestConfiguration() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $settings = getSettings()['data'] ?? [];
    $transport = strtolower((string)($settings['email_transport'] ?? 'php_mail'));
    if ($transport === 'smtp') {
        $required = [
            'smtp_host' => 'SMTP host',
            'smtp_port' => 'SMTP port',
            'smtp_username' => 'SMTP username',
            'smtp_password' => 'SMTP password',
            'smtp_from_email' => 'From email'
        ];
        $missing = [];
        foreach ($required as $key => $label) {
            if (trim((string)($settings[$key] ?? '')) === '') $missing[] = $label;
        }
        if ($missing) {
            $message = 'Missing required SMTP fields: ' . implode(', ', $missing);
            saveSettingValues([
                'email_verification_status' => 'incomplete',
                'email_last_verified_at' => date('Y-m-d H:i:s'),
                'email_verification_message' => $message
            ]);
            return ['success' => false, 'error' => $message];
        }

        $host = trim((string)$settings['smtp_host']);
        $port = (int)($settings['smtp_port'] ?? 0);
        $encryption = strtolower((string)($settings['smtp_encryption'] ?? 'tls'));
        $targetHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($targetHost, $port, $errno, $errstr, 10);
        if (!$socket) {
            $message = "SMTP connection failed for {$host}:{$port}. {$errstr}";
            saveSettingValues([
                'email_verification_status' => 'failed',
                'email_last_verified_at' => date('Y-m-d H:i:s'),
                'email_verification_message' => $message
            ]);
            return ['success' => false, 'error' => $message];
        }
        fclose($socket);
        $message = $encryption === 'ssl'
            ? "SMTP socket verified successfully over SSL for {$host}:{$port}. Mailbox is ready for final deployment integration."
            : "SMTP host {$host}:{$port} responded successfully. Mailbox is ready for final deployment integration and STARTTLS handshake.";
        saveSettingValues([
            'email_verification_status' => 'verified',
            'email_last_verified_at' => date('Y-m-d H:i:s'),
            'email_verification_message' => $message
        ]);
        return ['success' => true, 'message' => $message];
    }

    if (!function_exists('mail')) {
        $message = 'PHP mail() is not available on this server.';
        saveSettingValues([
            'email_verification_status' => 'failed',
            'email_last_verified_at' => date('Y-m-d H:i:s'),
            'email_verification_message' => $message
        ]);
        return ['success' => false, 'error' => $message];
    }

    $message = 'PHP mail() is available on this server. Local email delivery can be used while production mailbox integration remains optional.';
    saveSettingValues([
        'email_verification_status' => 'verified',
        'email_last_verified_at' => date('Y-m-d H:i:s'),
        'email_verification_message' => $message
    ]);
    return ['success' => true, 'message' => $message];
}


function buildNewsletterHtml($subject, $content, $attachments = []) {
    $settings = getSettings()['data'] ?? [];
    $attachmentList = '';
    if ($attachments) {
        $attachmentList = '<h3 style="color:#1A3A5C">Attachments</h3><ul>';
        foreach ($attachments as $a) $attachmentList .= '<li>' . htmlspecialchars($a['original_name']) . '</li>';
        $attachmentList .= '</ul>';
    }
    return "<!doctype html><html><body style='margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#1A2632'><div style='max-width:720px;margin:auto;background:#fff'><div style='background:#1A3A5C;color:#fff;padding:24px'><h1 style='margin:0'>Komagin Limited</h1><p style='margin:6px 0 0;color:#E8A317'>Engineering Excellence in Papua New Guinea</p></div><div style='padding:26px'><h2 style='color:#1A3A5C'>" . htmlspecialchars($subject) . "</h2><div style='line-height:1.65'>" . nl2br(htmlspecialchars($content)) . "</div>" . $attachmentList . "</div><div style='padding:18px 26px;background:#f8f9fa;color:#6C757D;font-size:12px'>Port Moresby, Papua New Guinea | " . htmlspecialchars($settings['store_email'] ?? 'info@komagin.com') . "</div></div></body></html>";
}

function saveNewsletterAttachments() {
    $saved = [];
    if (empty($_FILES['attachments']['tmp_name']) || !is_array($_FILES['attachments']['tmp_name'])) return $saved;
    $dir = UPLOADS_DIR . 'newsletter/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    foreach ($_FILES['attachments']['tmp_name'] as $i => $tmp) {
        if (($_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        if (($_FILES['attachments']['size'][$i] ?? 0) > 10 * 1024 * 1024) continue;
        $ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
        $filename = 'newsletter_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . ($ext ? '.' . $ext : '');
        if (move_uploaded_file($tmp, $dir . $filename)) {
            $saved[] = ['filename' => $filename, 'original_name' => $_FILES['attachments']['name'][$i], 'path' => 'newsletter/' . $filename, 'size' => $_FILES['attachments']['size'][$i]];
        }
    }
    return $saved;
}
// ============ SETTINGS FUNCTIONS ============

function getSettings() {
    $db = requireDb(['settings']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $rows = dbAll("SELECT setting_key, setting_value, updated_at, updated_by FROM settings ORDER BY setting_key");
    $latest = dbOne("SELECT updated_at, updated_by FROM settings WHERE updated_at IS NOT NULL ORDER BY updated_at DESC, setting_key DESC LIMIT 1");
    $meta = [];
    if (!empty($latest['updated_at'])) $meta['updated_at'] = $latest['updated_at'];
    if (!empty($latest['updated_by'])) $meta['updated_by'] = $latest['updated_by'];
    $settings = array_merge(getSettingsArray(), $meta);
    return ['success' => true, 'data' => $settings];
}

function updateSettings() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;

    $db = requireDb(['settings']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    saveSettingValues($data);

    $_SESSION['admin_last_activity'] = time();
    logActivityDb('update_settings', array_keys($data));
    return ['success' => true, 'message' => 'Settings updated successfully'];
}

// ============ STATS FUNCTIONS ============

function getStats() {
    checkAuthentication();
    $db = requireDb(['projects', 'services', 'contacts', 'testimonials', 'team', 'newsletter']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $stats = [
        'total_projects' => dbCount('projects'),
        'total_services' => dbCount('services'),
        'total_testimonials' => dbCount('testimonials'),
        'total_team' => dbCount('team'),
        'total_contacts' => dbCount('contacts'),
        'total_subscribers' => dbCount('newsletter'),
        'open_jobs' => dbTableExists('job_listings') ? (int)dbScalar("SELECT COUNT(*) FROM job_listings WHERE status = 'published'") : 0,
        'new_applications' => dbTableExists('job_applications') ? (int)dbScalar("SELECT COUNT(*) FROM job_applications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)") : 0,
        'pending_contacts' => (int)dbScalar("SELECT COUNT(*) FROM contacts WHERE status='new'"),
        'staff_count' => dbTableExists('staff') ? (int)dbScalar("SELECT COUNT(*) FROM staff WHERE status='active'") : 0,
        'open_assets' => dbTableExists('assets') ? (int)dbScalar("SELECT COUNT(*) FROM assets WHERE status='available'") : 0,
        'pending_approvals' => 0,
        'active_branches' => dbTableExists('branches') ? (int)dbScalar("SELECT COUNT(*) FROM branches WHERE status='active'") : 0
    ];
    if (dbTableExists('leave_requests')) $stats['pending_approvals'] += (int)dbScalar("SELECT COUNT(*) FROM leave_requests WHERE status='pending'");
    if (dbTableExists('branch_expenses')) $stats['pending_approvals'] += (int)dbScalar("SELECT COUNT(*) FROM branch_expenses WHERE status='pending'");
    if (dbTableExists('partners')) $stats['pending_approvals'] += (int)dbScalar("SELECT COUNT(*) FROM partners WHERE status='enquiry'");
    return ['success' => true, 'data' => $stats];
}

// ============ FILE UPLOAD FUNCTIONS ============

function uploadImage() {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error'];
    }
    
    $file = $_FILES['image'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP'];
    }
    
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Maximum size: 5MB'];
    }
    
    $filename = 'image_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
    $filepath = UPLOADS_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $_SESSION['admin_last_activity'] = time();
        return [
            'success' => true,
            'file_path' => $filename,
            'file_url' => ADMIN_URL . 'uploads/' . $filename,
            'message' => 'Image uploaded successfully'
        ];
    }
    return ['success' => false, 'error' => 'Failed to upload file'];
}


// ============ UPGRADE v3.0 SOCIAL, BRANCH MONITORING, LOGS ============

function v3RequireAdmin() {
    if (!requireRoles(['admin'])) return ['success' => false, 'error' => 'Access denied'];
    return true;
}

function socialGetPlatforms() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $db = requireDb(['social_platforms']); if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    ensureSocialIntegrationSchema();
    ensureSocialPlatformCatalog();
    $rows = dbAll("SELECT id, platform, display_name, is_enabled, page_id, account_name, connected_at, expires_at, verification_status, verification_message, verification_checked_at, created_at, updated_at FROM social_platforms ORDER BY FIELD(platform,'facebook','instagram','twitter','linkedin','whatsapp','tiktok')");
    $rows = array_map('socialBuildPlatformResponse', $rows);
    return ['success' => true, 'data' => $rows];
}

function socialSavePlatformConfig() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $data = requestData();
    $platform = $data['platform'] ?? '';
    if (!$platform) return ['success' => false, 'error' => 'Platform is required'];
    ensureSocialIntegrationSchema();
    ensureSocialPlatformCatalog();
    $token = $data['access_token'] ?? '';
    $apiKey = $data['api_key'] ?? '';
    $apiSecret = $data['api_secret'] ?? '';
    $verificationMessage = 'Configuration saved. Run Verify to confirm this account can be used for publishing.';
    dbWrite(
        "UPDATE social_platforms
         SET is_enabled=?, access_token=COALESCE(NULLIF(?,''), access_token), page_id=?, account_name=?, api_key=COALESCE(NULLIF(?,''), api_key), api_secret=COALESCE(NULLIF(?,''), api_secret), connected_at=NOW(), expires_at=?, verification_status='pending', verification_message=?, verification_checked_at=NULL, updated_at=NOW()
         WHERE platform=?",
        [
            !empty($data['is_enabled']) ? 1 : 0,
            $token !== '' ? encrypt_token($token) : '',
            $data['page_id'] ?? '',
            $data['account_name'] ?? '',
            $apiKey !== '' ? encrypt_token($apiKey) : '',
            $apiSecret !== '' ? encrypt_token($apiSecret) : '',
            $data['expires_at'] ?? null,
            $verificationMessage,
            $platform
        ]
    );
    logActivityDb('social_save_platform_config', $platform);
    return ['success' => true, 'message' => 'Platform configuration saved'];
}

function socialDisconnectPlatform() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $platform = $_GET['platform'] ?? '';
    ensureSocialIntegrationSchema();
    dbWrite("UPDATE social_platforms SET is_enabled=0, access_token=NULL, api_key=NULL, api_secret=NULL, page_id=NULL, account_name=NULL, connected_at=NULL, expires_at=NULL, verification_status='disconnected', verification_message='Platform disconnected.', verification_checked_at=NOW(), updated_at=NOW() WHERE platform=?", [$platform]);
    logActivityDb('social_disconnect_platform', $platform);
    return ['success' => true, 'message' => 'Platform disconnected'];
}

function socialTestConnection() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $platform = $_GET['platform'] ?? '';
    ensureSocialIntegrationSchema();
    $cfg = dbOne("SELECT * FROM social_platforms WHERE platform=?", [$platform]);
    if (!$cfg || empty($cfg['access_token'])) {
        socialPersistVerification($platform, 'incomplete', 'No access token is configured for this platform.');
        return ['success' => false, 'error' => 'No access token configured'];
    }
    $completeness = socialPlatformCompleteness($platform, $cfg);
    if (!$completeness['is_complete']) {
        $message = 'Missing required credentials: ' . implode(', ', $completeness['missing_fields']);
        socialPersistVerification($platform, 'incomplete', $message);
        return ['success' => false, 'error' => $message];
    }
    $token = decrypt_token($cfg['access_token']);
    if ($platform === 'tiktok') {
        $message = 'TikTok credentials are saved and ready for final deployment review. Run the final production verification during live integration.';
        socialPersistVerification($platform, 'manual_review', $message);
        return ['success' => true, 'message' => $message, 'status' => 'manual_review'];
    }

    $url = 'https://graph.facebook.com/v18.0/' . rawurlencode($cfg['page_id'] ?: 'me') . '?fields=id,name';
    if ($platform === 'instagram') $url = 'https://graph.facebook.com/v18.0/' . rawurlencode($cfg['page_id']) . '?fields=id,username';
    if ($platform === 'whatsapp') $url = 'https://graph.facebook.com/v18.0/' . rawurlencode($cfg['page_id']) . '?fields=id,display_phone_number,verified_name';
    if ($platform === 'linkedin') $url = 'https://api.linkedin.com/v2/me';
    if ($platform === 'twitter') $url = 'https://api.twitter.com/2/users/me';
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token]]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($code >= 200 && $code < 300) {
        $decoded = json_decode((string)$body, true) ?: [];
        $accountLabel = $decoded['name'] ?? $decoded['username'] ?? $decoded['verified_name'] ?? $cfg['account_name'] ?? socialPlatformDefinitions()[$platform]['display_name'] ?? ucfirst($platform);
        $message = $accountLabel . ' verified successfully and is ready for posting integration.';
        dbWrite("UPDATE social_platforms SET account_name=COALESCE(NULLIF(?,''), account_name), connected_at=NOW() WHERE platform=?", [$accountLabel, $platform]);
        socialPersistVerification($platform, 'verified', $message);
        return ['success' => true, 'status' => $code, 'message' => $message, 'detail' => substr((string)$body, 0, 500)];
    }
    $message = $err ?: substr((string)$body, 0, 500);
    socialPersistVerification($platform, 'failed', $message);
    return ['success' => false, 'status' => $code, 'error' => 'Connection failed', 'detail' => $message];
}

function socialUploadMedia() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) return ['success' => false, 'error' => 'No media uploaded'];
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $videoExts = ['mp4', 'mov', 'webm', 'm4v', 'avi'];
    $isImage = in_array($ext, $imageExts, true) || stripos((string)($file['type'] ?? ''), 'image/') === 0;
    $isVideo = in_array($ext, $videoExts, true) || stripos((string)($file['type'] ?? ''), 'video/') === 0;
    if (!$isImage && !$isVideo) return ['success' => false, 'error' => 'Only image and video uploads are allowed for social posts'];
    $maxSize = $isVideo ? 50 * 1024 * 1024 : 12 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSize) return ['success' => false, 'error' => $isVideo ? 'Video uploads must be 50MB or smaller' : 'Image uploads must be 12MB or smaller'];
    $dir = UPLOADS_DIR . 'social/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = 'social_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) return ['success' => false, 'error' => 'Upload failed'];
    $size = $isImage ? @getimagesize($dir . $filename) : false;
    $id = 'smedia_' . uniqid();
    dbWrite("INSERT INTO social_media_library (id, filename, original_name, file_path, file_size, mime_type, width, height, alt_text, uploaded_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())",
        [$id, $filename, $file['name'], 'social/' . $filename, $file['size'], $file['type'], $size[0] ?? null, $size[1] ?? null, $_POST['alt_text'] ?? '', currentUsername()]);
    logActivityDb('social_upload_media', $filename);
    return ['success' => true, 'id' => $id, 'path' => 'social/' . $filename, 'url' => ADMIN_URL . 'uploads/social/' . $filename, 'media_type' => $isVideo ? 'video' : 'image'];
}

function socialGetMediaLibrary() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    return ['success' => true, 'data' => dbAll("SELECT * FROM social_media_library ORDER BY created_at DESC LIMIT 100")];
}

function socialSchedulePost() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $data = requestData();
    $data['status'] = 'scheduled';
    return socialSavePostRecord($data);
}

function socialPublishPost() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $data = requestData();
    $postResult = socialSavePostRecord($data, 'publishing');
    if (!$postResult['success']) return $postResult;
    return socialPublishExistingPost($postResult['data']['id']);
}

function socialSavePostRecord($data, $status = null) {
    $platforms = $data['platforms'] ?? [];
    if (is_string($platforms)) $platforms = json_decode($platforms, true) ?: array_filter(array_map('trim', explode(',', $platforms)));
    $platforms = array_values(array_unique(array_filter(array_map(static fn($platform) => trim((string)$platform), (array)$platforms))));
    if (!$platforms) return ['success' => false, 'error' => 'Select at least one platform'];
    if (empty($data['content'])) return ['success' => false, 'error' => 'Post content is required'];
    $mediaPath = trim((string)($data['media_path'] ?? ''));
    $mediaType = trim((string)($data['media_type'] ?? ''));
    if ($mediaPath === '') {
        $mediaType = 'none';
        $mediaPath = null;
    } elseif ($mediaType === '' || $mediaType === 'none') {
        $mediaType = preg_match('/\.(mp4|mov|webm|m4v|avi)$/i', $mediaPath) ? 'video' : 'image';
    }
    $id = $data['id'] ?? ('spost_' . uniqid());
    $isNew = empty($data['id']);
    $finalStatus = $status ?: ($data['status'] ?? 'draft');
    if ($isNew) {
        dbWrite("INSERT INTO social_posts (id,title,content,media_path,media_type,link_url,hashtags,platforms,status,scheduled_at,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
            [$id, $data['title'] ?? '', $data['content'], $mediaPath, $mediaType, $data['link_url'] ?? null, $data['hashtags'] ?? '', json_encode(array_values($platforms)), $finalStatus, $data['scheduled_at'] ?? null, currentUsername()]);
    } else {
        dbWrite("UPDATE social_posts SET title=?, content=?, media_path=?, media_type=?, link_url=?, hashtags=?, platforms=?, status=?, scheduled_at=?, published_at=CASE WHEN ? IN ('scheduled','draft','publishing','failed') THEN NULL ELSE published_at END, updated_at=NOW() WHERE id=?",
            [$data['title'] ?? '', $data['content'], $mediaPath, $mediaType, $data['link_url'] ?? null, $data['hashtags'] ?? '', json_encode(array_values($platforms)), $finalStatus, $data['scheduled_at'] ?? null, $finalStatus, $id]);
    }
    logActivityDb('social_save_post', $id);
    return ['success' => true, 'data' => dbOne("SELECT * FROM social_posts WHERE id=?", [$id])];
}

function socialGetPosts() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $posts = dbAll("SELECT * FROM social_posts ORDER BY created_at DESC LIMIT 100");
    foreach ($posts as &$post) {
        $post['results'] = dbAll("SELECT * FROM social_post_results WHERE post_id=? ORDER BY platform", [$post['id']]);
        if (!empty($post['media_path'])) {
            $post['media_url'] = ADMIN_URL . 'uploads/' . ltrim($post['media_path'], '/');
            if (empty($post['media_type']) || $post['media_type'] === 'none') {
                $post['media_type'] = preg_match('/\.(mp4|mov|webm|m4v|avi)$/i', $post['media_path']) ? 'video' : 'image';
            }
        } else {
            $post['media_url'] = '';
            $post['media_type'] = 'none';
        }
    }
    return ['success' => true, 'data' => $posts];
}

function socialDeletePost() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $id = $_GET['id'] ?? '';
    dbWrite("DELETE FROM social_post_results WHERE post_id=?", [$id]);
    dbWrite("DELETE FROM social_posts WHERE id=?", [$id]);
    logActivityDb('social_delete_post', $id);
    return ['success' => true, 'message' => 'Social post deleted'];
}

function processScheduledSocialPosts() {
    if (!dbTableExists('social_posts')) return;
    $lockPath = __DIR__ . '/cache';
    if (!is_dir($lockPath)) {
        @mkdir($lockPath, 0755, true);
    }
    $lockFile = $lockPath . '/social-queue.lock';
    $lockHandle = @fopen($lockFile, 'c+');
    if (!$lockHandle) return;
    if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
        fclose($lockHandle);
        return;
    }
    $lastRunFile = $lockPath . '/social-queue.last-run';
    $lastRun = is_file($lastRunFile) ? (int)@file_get_contents($lastRunFile) : 0;
    if ($lastRun > 0 && (time() - $lastRun) < 20) {
        @flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        return;
    }
    @file_put_contents($lastRunFile, (string)time(), LOCK_EX);
    try {
        foreach (dbAll("SELECT id FROM social_posts WHERE status='scheduled' AND scheduled_at <= NOW() LIMIT 3") as $post) {
            socialPublishExistingPost($post['id']);
        }
    } finally {
        @flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

function socialPublishExistingPost($postId) {
    $post = dbOne("SELECT * FROM social_posts WHERE id=?", [$postId]);
    if (!$post) return ['success' => false, 'error' => 'Post not found'];
    $platforms = json_decode($post['platforms'], true) ?: [];
    $platforms = array_values(array_unique(array_filter(array_map(static fn($platform) => trim((string)$platform), (array)$platforms))));
    $outcomes = [];
    $published = 0;
    dbWrite("UPDATE social_posts SET status='publishing', updated_at=NOW() WHERE id=?", [$postId]);
    dbWrite("DELETE FROM social_post_results WHERE post_id=?", [$postId]);
    foreach ($platforms as $platform) {
        $cfg = dbOne("SELECT * FROM social_platforms WHERE platform=? AND is_enabled=1", [$platform]);
        if (!$cfg) {
            $result = ['success' => false, 'error' => 'Platform is not connected'];
        } else {
            $platformRow = socialBuildPlatformResponse($cfg);
            $result = !empty($platformRow['posting_ready'])
                ? publishToSocialPlatform($platform, $post, $cfg)
                : ['success' => false, 'error' => $platformRow['verification_message'] ?? 'Platform is not verified for posting'];
        }
        $status = $result['success'] ? 'published' : 'failed';
        if ($result['success']) $published++;
        dbWrite("INSERT INTO social_post_results (id,post_id,platform,platform_post_id,status,error_message,published_at,post_url) VALUES (?,?,?,?,?,?,?,?)",
            ['spres_' . uniqid(), $postId, $platform, $result['platform_post_id'] ?? null, $status, $result['error'] ?? null, $result['success'] ? date('Y-m-d H:i:s') : null, $result['post_url'] ?? null]);
        $outcomes[$platform] = $result;
    }
    $final = $published === count($platforms) ? 'published' : ($published > 0 ? 'partial' : 'failed');
    dbWrite("UPDATE social_posts SET status=?, published_at=IF(?='published' OR ?='partial', NOW(), published_at), updated_at=NOW() WHERE id=?", [$final, $final, $final, $postId]);
    logActivityDb('social_publish_post', $postId . ' ' . $final);
    return ['success' => true, 'status' => $final, 'results' => $outcomes];
}

function publishToSocialPlatform($platform, $post, $cfg) {
    $token = decrypt_token($cfg['access_token'] ?? '');
    if (!$token) return ['success' => false, 'error' => 'Missing access token'];
    if ($platform === 'facebook') $url = 'https://graph.facebook.com/v18.0/' . rawurlencode($cfg['page_id'] ?: 'me') . '/feed';
    elseif ($platform === 'instagram') $url = 'https://graph.facebook.com/v18.0/' . rawurlencode($cfg['page_id'] ?: 'me') . '/media';
    elseif ($platform === 'twitter') $url = 'https://api.twitter.com/2/tweets';
    elseif ($platform === 'linkedin') $url = 'https://api.linkedin.com/v2/ugcPosts';
    elseif ($platform === 'whatsapp') {
        return ['success' => false, 'error' => 'WhatsApp is linked and verification-ready, but posting will be enabled during final delivery integration because it depends on the approved channel flow.'];
    }
    else $url = 'https://open.tiktokapis.com/v2/post/publish/';
    $payload = ['message' => $post['content'], 'text' => $post['content'], 'content' => $post['content'], 'link' => $post['link_url']];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_NOSIGNAL => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($code >= 200 && $code < 300) {
        $decoded = json_decode($body, true) ?: [];
        return ['success' => true, 'platform_post_id' => $decoded['id'] ?? ($decoded['data']['id'] ?? null), 'post_url' => $decoded['permalink_url'] ?? null];
    }
    return ['success' => false, 'error' => $err ?: substr((string)$body, 0, 900), 'status' => $code];
}

function branchReportsGet() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $rows = dbAll("SELECT sr.*, b.name AS branch_name, bp.name AS project_name FROM branch_site_reports sr LEFT JOIN branches b ON b.id=sr.branch_id LEFT JOIN branch_projects bp ON bp.id=sr.project_id ORDER BY sr.report_date DESC, sr.created_at DESC");
    $stats = ['total_month'=>(int)dbScalar("SELECT COUNT(*) FROM branch_site_reports WHERE DATE_FORMAT(report_date,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m')"), 'verified'=>(int)dbScalar("SELECT COUNT(*) FROM branch_site_reports WHERE status='verified'"), 'flagged'=>(int)dbScalar("SELECT COUNT(*) FROM branch_site_reports WHERE status='flagged'"), 'incidents_month'=>(int)dbScalar("SELECT COALESCE(SUM(safety_incidents),0) FROM branch_site_reports WHERE DATE_FORMAT(report_date,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m')")];
    return ['success'=>true,'data'=>$rows,'stats'=>$stats];
}

function branchReportsSave() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $data = requestData();
    if (empty($data['branch_id']) || empty($data['report_date'])) return ['success'=>false,'error'=>'Branch and report date are required'];
    if (isset($data['photos']) && is_array($data['photos'])) $data['photos'] = json_encode($data['photos']);
    return dbUpsert('branch_site_reports', ['id','branch_id','project_id','report_date','report_type','weather','workers_on_site','activities_done','issues_raised','materials_used','equipment_used','safety_incidents','incident_detail','photos','submitted_by','verified_by','verified_at','status','created_at'], $data, 'sreport');
}

function branchReportsSetStatus($status) {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $id = $_GET['id'] ?? (requestData()['id'] ?? '');
    dbWrite("UPDATE branch_site_reports SET status=?, verified_by=?, verified_at=IF(?='verified', NOW(), verified_at) WHERE id=?", [$status, currentUsername(), $status, $id]);
    logActivityDb('branch_report_' . $status, $id);
    return ['success'=>true,'message'=>'Site report updated'];
}

function branchReportsUploadPhotos() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $branch = preg_replace('/[^a-z0-9_-]/i', '_', $_POST['branch_id'] ?? 'general');
    $date = preg_replace('/[^0-9-]/', '', $_POST['report_date'] ?? date('Y-m-d'));
    $dir = UPLOADS_DIR . "site_reports/{$branch}/{$date}/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $paths = [];
    foreach ($_FILES['photos']['tmp_name'] ?? [] as $i => $tmp) {
        if (($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        if (($_FILES['photos']['size'][$i] ?? 0) > 5 * 1024 * 1024) continue;
        $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
        $name = 'site_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($tmp, $dir . $name)) $paths[] = "site_reports/{$branch}/{$date}/{$name}";
    }
    return ['success'=>true,'paths'=>$paths];
}

function branchGenerateSiteReport() {
    $report = dbOne("SELECT sr.*, b.name AS branch_name, bp.name AS project_name FROM branch_site_reports sr LEFT JOIN branches b ON b.id=sr.branch_id LEFT JOIN branch_projects bp ON bp.id=sr.project_id WHERE sr.id=?", [$_GET['id'] ?? '']);
    if (!$report) printCompanyDocument('Site Report', '<p>Report not found.</p>');
    $photos = json_decode($report['photos'] ?? '[]', true) ?: [];
    $gallery = '';
    foreach ($photos as $p) $gallery .= "<img src='uploads/" . htmlspecialchars($p) . "' style='width:140px;height:100px;object-fit:cover;margin:6px;border:1px solid #ddd'>";
    $attachment = '';
    if (!empty($report['attachment_path'])) {
        $url = 'uploads/' . htmlspecialchars($report['attachment_path']);
        $name = htmlspecialchars($report['attachment_name'] ?: basename($report['attachment_path']));
        $attachment = "<div class='box'><strong>Attachment:</strong> <a href='{$url}' target='_blank'>{$name}</a></div>";
        if (preg_match('/image\\//i', $report['attachment_mime'] ?? '')) {
            $attachment .= "<img src='{$url}' style='max-width:100%;height:auto;margin-top:12px;border:1px solid #ddd'>";
        }
    }
    $body = "<div class='box'><strong>Branch:</strong> " . htmlspecialchars($report['branch_name'] ?? '') . "<br><strong>Project:</strong> " . htmlspecialchars($report['project_name'] ?? '') . "<br><strong>Date:</strong> " . htmlspecialchars($report['report_date']) . "<br><strong>Type:</strong> " . htmlspecialchars($report['report_type']) . "<br><strong>Status:</strong> " . htmlspecialchars($report['status'] ?? 'submitted') . "</div><table><tr><th>Weather</th><td>" . htmlspecialchars($report['weather'] ?? '') . "</td></tr><tr><th>Workers</th><td>" . (int)$report['workers_on_site'] . "</td></tr><tr><th>Activities</th><td>" . nl2br(htmlspecialchars($report['activities_done'] ?? '')) . "</td></tr><tr><th>Issues</th><td>" . nl2br(htmlspecialchars($report['issues_raised'] ?? '')) . "</td></tr><tr><th>Materials Used</th><td>" . nl2br(htmlspecialchars($report['materials_used'] ?? '')) . "</td></tr><tr><th>Equipment Used</th><td>" . nl2br(htmlspecialchars($report['equipment_used'] ?? '')) . "</td></tr><tr><th>Safety Incidents</th><td>" . (int)$report['safety_incidents'] . "</td></tr><tr><th>Incident Detail</th><td>" . nl2br(htmlspecialchars($report['incident_detail'] ?? '')) . "</td></tr></table><h3>Attachments</h3>{$attachment}<h3>Photos</h3>{$gallery}<div class='signatures'><div class='sig'>Submitted by</div><div class='sig'>Verified by</div></div>";
    logActivityDb('branch_generate_site_report', $report['id']);
    printCompanyDocument('Site Report', $body);
}

function branchMilestonesGet() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    dbWrite("UPDATE branch_milestones SET status='overdue', updated_at=NOW() WHERE due_date < CURDATE() AND status NOT IN ('completed','blocked')");
    $rows = dbAll("SELECT bm.*, bp.name AS project_name, b.name AS branch_name FROM branch_milestones bm LEFT JOIN branch_projects bp ON bp.id=bm.project_id LEFT JOIN branches b ON b.id=bm.branch_id ORDER BY bp.name, bm.due_date");
    return ['success'=>true,'data'=>$rows];
}

function branchMilestonesSave() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $data = requestData();
    if (empty($data['project_id']) || empty($data['branch_id']) || empty($data['title'])) return ['success'=>false,'error'=>'Project, branch, and title are required'];
    $currentId = $data['id'] ?? '';
    $existingWeight = (int)dbScalar("SELECT COALESCE(SUM(weight_percent),0) FROM branch_milestones WHERE project_id=? AND id<>?", [$data['project_id'], $currentId]);
    if ($existingWeight + (int)($data['weight_percent'] ?? 0) > 100) return ['success'=>false,'error'=>'Milestone weights cannot exceed 100% for a project'];
    $result = dbUpsert('branch_milestones', ['id','project_id','branch_id','title','description','due_date','completed_date','status','weight_percent','assigned_to','blockers','evidence_file','created_at','updated_at'], $data, 'mile');
    branchMilestonesRecalculateProgress($data['project_id']);
    return $result;
}

function branchMilestonesComplete() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $id = $_GET['id'] ?? (requestData()['id'] ?? '');
    $row = dbOne("SELECT project_id FROM branch_milestones WHERE id=?", [$id]);
    dbWrite("UPDATE branch_milestones SET status='completed', completed_date=CURDATE(), updated_at=NOW() WHERE id=?", [$id]);
    if ($row) branchMilestonesRecalculateProgress($row['project_id']);
    logActivityDb('branch_milestone_complete', $id);
    return ['success'=>true,'message'=>'Milestone completed'];
}

function branchMilestonesUploadEvidence() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $id = $_POST['id'] ?? '';
    $row = dbOne("SELECT project_id FROM branch_milestones WHERE id=?", [$id]);
    if (!$row || !isset($_FILES['file'])) return ['success'=>false,'error'=>'Milestone and file required'];
    $dir = UPLOADS_DIR . 'milestones/' . preg_replace('/[^a-z0-9_-]/i','_', $row['project_id']) . '/';
    if (!is_dir($dir)) mkdir($dir,0755,true);
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $name = 'evidence_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dir.$name)) return ['success'=>false,'error'=>'Upload failed'];
    $path = 'milestones/' . $row['project_id'] . '/' . $name;
    dbWrite("UPDATE branch_milestones SET evidence_file=?, updated_at=NOW() WHERE id=?", [$path, $id]);
    return ['success'=>true,'path'=>$path];
}

function branchMilestonesRecalculateProgress($projectId = null) {
    $projectId = $projectId ?: ($_GET['project_id'] ?? (requestData()['project_id'] ?? ''));
    if (!$projectId) return ['success'=>false,'error'=>'Project ID required'];
    $progress = (int)dbScalar("SELECT COALESCE(SUM(weight_percent),0) FROM branch_milestones WHERE project_id=? AND status='completed'", [$projectId]);
    dbWrite("UPDATE branch_projects SET progress_percent=?, updated_at=NOW() WHERE id=?", [$progress, $projectId]);
    return ['success'=>true,'progress_percent'=>$progress];
}

function branchRfisGet() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $stats = ['open'=>(int)dbScalar("SELECT COUNT(*) FROM branch_rfis WHERE status='open'"), 'under_review'=>(int)dbScalar("SELECT COUNT(*) FROM branch_rfis WHERE status='under_review'"), 'answered_today'=>(int)dbScalar("SELECT COUNT(*) FROM branch_rfis WHERE status='answered' AND DATE(answered_at)=CURDATE()"), 'urgent_overdue'=>(int)dbScalar("SELECT COUNT(*) FROM branch_rfis WHERE (priority='urgent' OR due_date<CURDATE()) AND status NOT IN ('answered','closed')")];
    $rows = dbAll("SELECT r.*, b.name AS branch_name, bp.name AS project_name FROM branch_rfis r LEFT JOIN branches b ON b.id=r.branch_id LEFT JOIN branch_projects bp ON bp.id=r.project_id ORDER BY FIELD(r.priority,'urgent','high','medium','low'), r.created_at DESC");
    return ['success'=>true,'data'=>$rows,'stats'=>$stats];
}

function branchRfisSave() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $data = requestData();
    if (empty($data['branch_id']) || empty($data['subject']) || empty($data['description'])) return ['success'=>false,'error'=>'Branch, subject, and description are required'];
    if (empty($data['rfi_number'])) {
        $branch = dbOne("SELECT branch_code FROM branches WHERE id=?", [$data['branch_id']]);
        $seq = (int)dbScalar("SELECT COUNT(*) FROM branch_rfis WHERE branch_id=? AND YEAR(created_at)=YEAR(NOW())", [$data['branch_id']]) + 1;
        $data['rfi_number'] = 'RFI-' . ($branch['branch_code'] ?? 'BR') . '-' . date('Y') . '-' . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
    }
    return dbUpsert('branch_rfis', ['id','branch_id','project_id','rfi_number','subject','description','priority','status','raised_by','answered_by','answer','attachment','due_date','answered_at','created_at'], $data, 'rfi');
}

function branchRfisAnswer() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $data = requestData();
    dbWrite("UPDATE branch_rfis SET answer=?, answered_by=?, answered_at=NOW(), status='answered' WHERE id=?", [$data['answer'] ?? '', currentUsername(), $data['id'] ?? ($_GET['id'] ?? '')]);
    logActivityDb('branch_rfi_answer', $data['id'] ?? '');
    return ['success'=>true,'message'=>'RFI answered'];
}

function branchRfisClose() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $id = $_GET['id'] ?? (requestData()['id'] ?? '');
    dbWrite("UPDATE branch_rfis SET status='closed' WHERE id=?", [$id]);
    return ['success'=>true,'message'=>'RFI closed'];
}

function branchGenerateRfiResponse() {
    $rfi = dbOne("SELECT r.*, b.name AS branch_name, bp.name AS project_name FROM branch_rfis r LEFT JOIN branches b ON b.id=r.branch_id LEFT JOIN branch_projects bp ON bp.id=r.project_id WHERE r.id=?", [$_GET['id'] ?? '']);
    if (!$rfi) printCompanyDocument('RFI Response', '<p>RFI not found.</p>');
    $body = "<div class='box'><strong>RFI:</strong> " . htmlspecialchars($rfi['rfi_number']) . "<br><strong>Project:</strong> " . htmlspecialchars($rfi['project_name'] ?? '') . "<br><strong>Branch:</strong> " . htmlspecialchars($rfi['branch_name'] ?? '') . "</div><h3>Question</h3><p>" . nl2br(htmlspecialchars($rfi['description'])) . "</p><h3>Official Answer</h3><p>" . nl2br(htmlspecialchars($rfi['answer'] ?? '')) . "</p><div class='signatures'><div class='sig'>Answered by " . htmlspecialchars($rfi['answered_by'] ?? currentUsername()) . "</div><div class='sig'>Date</div></div>";
    printCompanyDocument('RFI Response Letter', $body);
}

function branchKpisGet() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $period = $_GET['period'] ?? date('Y-m');
    return ['success'=>true,'data'=>dbAll("SELECT k.*, b.name AS branch_name, b.branch_code FROM branch_kpis k LEFT JOIN branches b ON b.id=k.branch_id WHERE k.period=? ORDER BY b.name", [$period])];
}

function branchKpisGenerate() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $period = $_GET['period'] ?? (requestData()['period'] ?? date('Y-m'));
    [$year,$month] = array_map('intval', explode('-', $period));
    foreach (dbAll("SELECT * FROM branches") as $b) {
        $active = (int)dbScalar("SELECT COUNT(*) FROM branch_projects WHERE branch_id=? AND status='active'", [$b['id']]);
        $completed = (int)dbScalar("SELECT COUNT(*) FROM branch_projects WHERE branch_id=? AND status='completed' AND YEAR(actual_end_date)=? AND MONTH(actual_end_date)=?", [$b['id'],$year,$month]);
        $delayed = (int)dbScalar("SELECT COUNT(*) FROM branch_projects WHERE branch_id=? AND expected_end_date<CURDATE() AND status NOT IN ('completed','cancelled')", [$b['id']]);
        $budget = dbOne("SELECT COALESCE(SUM(budget),0) AS total, COALESCE(SUM(spent),0) AS spent FROM branch_projects WHERE branch_id=? AND status IN ('active','completed')", [$b['id']]);
        $avg = (float)dbScalar("SELECT COALESCE(AVG(progress_percent),0) FROM branch_projects WHERE branch_id=? AND status='active'", [$b['id']]);
        $incidents = (int)dbScalar("SELECT COALESCE(SUM(safety_incidents),0) FROM branch_site_reports WHERE branch_id=? AND YEAR(report_date)=? AND MONTH(report_date)=?", [$b['id'],$year,$month]);
        $headcount = (int)dbScalar("SELECT COUNT(*) FROM staff WHERE department LIKE ? AND status='active'", ['%' . $b['name'] . '%']);
        $assets = (int)dbScalar("SELECT COUNT(*) FROM branch_assets WHERE branch_id=? AND return_date IS NULL", [$b['id']]);
        $id = 'kpi_' . md5($b['id'] . $period);
        dbWrite("INSERT INTO branch_kpis (id,branch_id,period,projects_active,projects_completed,projects_delayed,budget_total,budget_spent,budget_variance,avg_milestone_completion,safety_incidents,staff_headcount,assets_deployed,generated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE projects_active=VALUES(projects_active), projects_completed=VALUES(projects_completed), projects_delayed=VALUES(projects_delayed), budget_total=VALUES(budget_total), budget_spent=VALUES(budget_spent), budget_variance=VALUES(budget_variance), avg_milestone_completion=VALUES(avg_milestone_completion), safety_incidents=VALUES(safety_incidents), staff_headcount=VALUES(staff_headcount), assets_deployed=VALUES(assets_deployed), generated_at=NOW()",
            [$id,$b['id'],$period,$active,$completed,$delayed,$budget['total'],$budget['spent'],(float)$budget['total']-(float)$budget['spent'],$avg,$incidents,$headcount,$assets]);
    }
    logActivityDb('branch_kpis_generate', $period);
    return branchKpisGet();
}

function branchKpisUpdateNotes() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    $data = requestData();
    dbWrite("UPDATE branch_kpis SET notes=?, client_satisfaction=? WHERE id=?", [$data['notes'] ?? '', $data['client_satisfaction'] ?? null, $data['id'] ?? '']);
    return ['success'=>true,'message'=>'KPI notes updated'];
}

function branchGenerateKpiReport() {
    $kpi = dbOne("SELECT k.*, b.name AS branch_name, b.branch_code FROM branch_kpis k LEFT JOIN branches b ON b.id=k.branch_id WHERE k.id=?", [$_GET['id'] ?? '']);
    if (!$kpi) printCompanyDocument('Branch KPI Report', '<p>KPI record not found.</p>');
    $pct = $kpi['budget_total'] > 0 ? min(100, round(($kpi['budget_spent'] / $kpi['budget_total']) * 100)) : 0;
    $body = "<div class='box'><strong>Branch:</strong> " . htmlspecialchars($kpi['branch_name']) . " (" . htmlspecialchars($kpi['branch_code']) . ")<br><strong>Period:</strong> " . htmlspecialchars($kpi['period']) . "</div><table><tr><th>Active Projects</th><td>{$kpi['projects_active']}</td></tr><tr><th>Completed</th><td>{$kpi['projects_completed']}</td></tr><tr><th>Delayed</th><td>{$kpi['projects_delayed']}</td></tr><tr><th>Safety Incidents</th><td>{$kpi['safety_incidents']}</td></tr><tr><th>Client Satisfaction</th><td>" . htmlspecialchars($kpi['client_satisfaction'] ?? 'Not rated') . "</td></tr></table><p>Budget spent: {$pct}%</p><div class='progress'><div style='width:{$pct}%'></div></div><h3>Notes</h3><p>" . nl2br(htmlspecialchars($kpi['notes'] ?? '')) . "</p>";
    printCompanyDocument('Branch KPI Report', $body);
}

function activityLogsGet() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    return ['success'=>true,'data'=>dbAll("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 300")];
}

function activityLogsPurge() {
    $ok = v3RequireAdmin(); if ($ok !== true) return $ok;
    dbWrite("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    logActivityDb('activity_logs_purge', 'older than 90 days');
    return ['success'=>true,'message'=>'Old logs purged'];
}

// ============ AUTHENTICATION FUNCTIONS ============

function login() {
    $data = requestData();
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    if ($username === '' || $password === '') {
        return ['success' => false, 'error' => 'Username and password required'];
    }

    $stmt = get_db()->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin' AND is_active = 1 LIMIT 1");
    $stmt->execute([$username]);
    $dbUser = $stmt->fetch();

    if ($dbUser && password_verify($password, $dbUser['password'])) {
        get_db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$dbUser['id']]);
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $dbUser['username'];
        $_SESSION['admin_id'] = $dbUser['id'];
        $_SESSION['admin_email'] = $dbUser['email'] ?? '';
        $_SESSION['admin_role'] = 'admin';
        $_SESSION['user_role'] = 'admin';
        $_SESSION['admin_last_login'] = date('Y-m-d H:i:s');
        $_SESSION['admin_last_activity'] = time();
        $_SESSION['admin_session_start'] = time();
        logActivityDb('login', $dbUser['username']);

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'username' => $dbUser['username'],
                'id' => $dbUser['id'],
                'email' => $dbUser['email'] ?? '',
                'role' => 'admin'
            ]
        ];
    }

    return ['success' => false, 'error' => 'Invalid username or password'];
}
function logout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

function checkAuth() {
    if (isAuthenticated()) {
        return [
            'success' => true,
            'authenticated' => true,
            'user' => [
                'username' => $_SESSION['admin_username'] ?? '',
                'id' => $_SESSION['admin_id'] ?? '',
                'email' => $_SESSION['admin_email'] ?? '',
                'role' => $_SESSION['admin_role'] ?? 'admin'
            ],
            'user_role' => $_SESSION['admin_role'] ?? 'admin'
        ];
    }
    return ['success' => true, 'authenticated' => false, 'message' => 'Not authenticated'];
}

function changePassword() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        return ['success' => false, 'error' => 'All password fields are required'];
    }
    
    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'error' => 'New passwords do not match'];
    }
    
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters'];
    }
    
    $db = requireDb(['users']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $dbUser = dbOne("SELECT * FROM users WHERE id = ?", [$_SESSION['admin_id'] ?? '']);
    if (!$dbUser) return ['success' => false, 'error' => 'User not found'];
    if (!password_verify($currentPassword, $dbUser['password'])) {
        return ['success' => false, 'error' => 'Current password is incorrect'];
    }
    if (password_verify($newPassword, $dbUser['password'])) {
        return ['success' => false, 'error' => 'New password cannot be same as current password'];
    }
    dbWrite("UPDATE users SET password = ?, password_changed_at = NOW(), updated_at = NOW() WHERE id = ?", [password_hash($newPassword, PASSWORD_DEFAULT), $dbUser['id']]);
    logActivityDb('change_password', $dbUser['username']);
    return ['success' => true, 'message' => 'Password changed successfully'];
}

function updateProfile() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    
    $newUsername = trim($data['username'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    
    if (empty($newUsername)) return ['success' => false, 'error' => 'Username is required'];
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Enter a valid email address'];
    }
    
    $db = requireDb(['users']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $userId = $_SESSION['admin_id'] ?? '';
    if ($userId === '') return ['success' => false, 'error' => 'User session not found'];
    $dbUser = dbOne("SELECT id, username, is_active FROM users WHERE id = ?", [$userId]);
    if (!$dbUser) return ['success' => false, 'error' => 'User not found'];
    if ((int)($dbUser['is_active'] ?? 1) !== 1) return ['success' => false, 'error' => 'This profile is inactive'];
    $existing = dbOne("SELECT id FROM users WHERE username = ? AND id <> ?", [$newUsername, $userId]);
    if ($existing) return ['success' => false, 'error' => 'Username already taken'];
    dbWrite("UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id = ?", [$newUsername, $email, $userId]);
    $_SESSION['admin_username'] = $newUsername;
    $_SESSION['admin_email'] = $email;
    $_SESSION['admin_last_activity'] = time();
    logActivityDb('update_profile', $newUsername);
    $updatedUser = dbOne("SELECT username, email, created_at, updated_at, last_login, password_changed_at FROM users WHERE id = ?", [$userId]);
    return ['success' => true, 'message' => 'Profile updated successfully', 'user' => $updatedUser ?: ['username' => $newUsername, 'email' => $email]];
}

function getProfile() {
    $db = requireDb(['users']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $dbUser = dbOne("SELECT id, username, email, role, is_active, created_at, updated_at, last_login, password_changed_at FROM users WHERE id = ?", [$_SESSION['admin_id'] ?? '']);
    if (!$dbUser) return ['success' => false, 'error' => 'User not found'];
    return [
        'success' => true,
        'user' => [
            'username' => $dbUser['username'],
            'email' => $dbUser['email'] ?? '',
            'created_at' => $dbUser['created_at'],
            'last_login' => $dbUser['last_login'] ?? '',
            'role' => $dbUser['role'] ?? 'admin',
            'updated_at' => $dbUser['updated_at'] ?? '',
            'is_active' => (bool)($dbUser['is_active'] ?? true),
            'password_changed_at' => $dbUser['password_changed_at'] ?? ''
        ]
    ];
}

function slugifyBlog($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'blog-post';
}

function ensureUniqueBlogSlug($slug, $ignoreId = '') {
    $base = slugifyBlog($slug);
    $candidate = $base;
    $i = 2;
    while (true) {
        $row = dbOne("SELECT id FROM blog_posts WHERE slug=?", [$candidate]);
        if (!$row || ($ignoreId && $row['id'] === $ignoreId)) return $candidate;
        $candidate = $base . '-' . $i++;
    }
}

function normalizeBlogRow($row) {
    if (!$row) return $row;
    $row['featured_image_url'] = !empty($row['featured_image']) ? blogBuildPublicFileUrl($row['featured_image']) : '';
    $row['attachment_url'] = !empty($row['attachment_path']) ? blogBuildPublicFileUrl($row['attachment_path']) : '';
    $row['display_date'] = $row['published_at'] ?: ($row['created_at'] ?? '');
    if (empty($row['excerpt'])) {
        $row['excerpt'] = substr(trim(strip_tags($row['content'] ?? '')), 0, 220);
    }
    return $row;
}

function blogBuildPublicFileUrl($path) {
    $raw = trim((string)$path);
    if ($raw === '') return '';
    if (preg_match('#^(https?:)?//#i', $raw)) return $raw;
    if (strpos($raw, '/Komagin/') === 0) return $raw;
    if (strpos($raw, 'admin/uploads/') === 0) return SITE_URL . ltrim($raw, '/');
    if (strpos($raw, 'uploads/') === 0) return ADMIN_URL . ltrim($raw, '/');
    if (strpos($raw, 'images/') === 0 || strpos($raw, 'admin/') === 0) return SITE_URL . ltrim($raw, '/');
    return ADMIN_URL . 'uploads/' . ltrim($raw, '/');
}

function blogCanDeleteStoredFile($path) {
    $normalized = ltrim(str_replace('\\', '/', (string)$path), '/');
    return strpos($normalized, 'blog/') === 0;
}

function blogResolveAttachmentMeta($path) {
    $normalized = trim((string)$path);
    if ($normalized === '') return ['name' => '', 'mime' => '', 'size' => null];
    $row = dbTableExists('managed_files') ? dbOne("SELECT original_name, mime_type, file_size FROM managed_files WHERE file_path=? LIMIT 1", [$normalized]) : null;
    if ($row) {
        return [
            'name' => $row['original_name'] ?: basename($normalized),
            'mime' => $row['mime_type'] ?? '',
            'size' => $row['file_size'] ?? null
        ];
    }
    return [
        'name' => basename($normalized),
        'mime' => '',
        'size' => null
    ];
}

function blogGetPosts() {
    $db = requireDb(['blog_posts']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $category = trim($_GET['category'] ?? '');
    $params = [];
    $where = "WHERE status='published'";
    if ($category !== '' && $category !== 'all') {
        $where .= " AND category=?";
        $params[] = $category;
    }
    $rows = dbAll("SELECT * FROM blog_posts {$where} ORDER BY COALESCE(published_at, created_at) DESC, created_at DESC", $params);
    return ['success' => true, 'data' => array_map('normalizeBlogRow', $rows)];
}

function blogGetPost() {
    $db = requireDb(['blog_posts']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $slug = trim($_GET['slug'] ?? '');
    $id = trim($_GET['id'] ?? '');
    if ($slug !== '') {
        $row = dbOne("SELECT * FROM blog_posts WHERE slug=? AND status='published'", [$slug]);
    } elseif ($id !== '') {
        $row = dbOne("SELECT * FROM blog_posts WHERE id=? AND status='published'", [$id]);
    } else {
        return ['success' => false, 'error' => 'Post identifier is required'];
    }
    if (!$row) return ['success' => false, 'error' => 'Post not found'];
    return ['success' => true, 'data' => normalizeBlogRow($row)];
}

function blogPostsGetAll() {
    $ok = v3RequireAdmin();
    if ($ok !== true) return $ok;
    $db = requireDb(['blog_posts']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];
    $rows = dbAll("SELECT * FROM blog_posts ORDER BY COALESCE(updated_at, created_at) DESC");
    return [
        'success' => true,
        'data' => array_map('normalizeBlogRow', $rows),
        'stats' => [
            'published' => (int)dbScalar("SELECT COUNT(*) FROM blog_posts WHERE status='published'"),
            'drafts' => (int)dbScalar("SELECT COUNT(*) FROM blog_posts WHERE status='draft'"),
            'attachments' => (int)dbScalar("SELECT COUNT(*) FROM blog_posts WHERE attachment_path IS NOT NULL AND attachment_path <> ''")
        ]
    ];
}

function saveBlogUpload($field, $subdir, $allowedExtensions, $maxBytes) {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return ['error' => 'Upload failed for ' . $field];
    if (($file['size'] ?? 0) > $maxBytes) return ['error' => 'File is too large for ' . $field];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) return ['error' => 'Unsupported file type: ' . $ext];
    $dir = UPLOADS_DIR . 'blog/' . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = 'blog_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) return ['error' => 'Could not save uploaded file'];
    return [
        'path' => 'blog/' . $subdir . '/' . $name,
        'name' => $file['name'],
        'mime' => $file['type'] ?? '',
        'size' => $file['size'] ?? 0
    ];
}

function blogPostsSave() {
    $ok = v3RequireAdmin();
    if ($ok !== true) return $ok;
    $db = requireDb(['blog_posts']);
    if (is_array($db)) return ['success' => false, 'error' => $db['error']];

    $data = requestData();
    $id = trim($data['id'] ?? '');
    $existing = $id ? dbOne("SELECT * FROM blog_posts WHERE id=?", [$id]) : null;
    $title = trim($data['title'] ?? '');
    if ($title === '') return ['success' => false, 'error' => 'Blog title is required'];
    $status = in_array(($data['status'] ?? 'draft'), ['draft','published','archived'], true) ? $data['status'] : 'draft';
    $slug = ensureUniqueBlogSlug($data['slug'] ?? $title, $id);

    $featured = saveBlogUpload('featured_image', 'images', ['jpg','jpeg','png','webp','gif'], 8 * 1024 * 1024);
    if (is_array($featured) && isset($featured['error'])) return ['success' => false, 'error' => $featured['error']];
    $attachment = saveBlogUpload('attachment', 'attachments', ['pdf','doc','docx','jpg','jpeg','png','webp','gif'], 15 * 1024 * 1024);
    if (is_array($attachment) && isset($attachment['error'])) return ['success' => false, 'error' => $attachment['error']];

    $featuredLibraryPath = trim((string)($data['featured_image_path'] ?? ''));
    $featuredPath = $featured['path'] ?? ($featuredLibraryPath !== '' ? $featuredLibraryPath : ($existing['featured_image'] ?? ''));
    $attachmentLibraryPath = trim((string)($data['attachment_path'] ?? ''));
    $attachmentPath = $attachment['path'] ?? ($attachmentLibraryPath !== '' ? $attachmentLibraryPath : ($existing['attachment_path'] ?? ''));
    $attachmentName = $attachment['name'] ?? ($existing['attachment_name'] ?? '');
    $attachmentMime = $attachment['mime'] ?? ($existing['attachment_mime'] ?? '');
    $attachmentSize = $attachment['size'] ?? ($existing['attachment_size'] ?? null);
    if (!$attachment && $attachmentLibraryPath !== '' && $attachmentLibraryPath !== ($existing['attachment_path'] ?? '')) {
        $attachmentMeta = blogResolveAttachmentMeta($attachmentLibraryPath);
        $attachmentName = $attachmentMeta['name'];
        $attachmentMime = $attachmentMeta['mime'];
        $attachmentSize = $attachmentMeta['size'];
    }
    $publishedAt = $existing['published_at'] ?? null;
    if ($status === 'published' && empty($publishedAt)) $publishedAt = date('Y-m-d H:i:s');
    if ($status !== 'published') $publishedAt = null;

    if ($existing) {
        foreach ([
            ['old' => $existing['featured_image'] ?? '', 'new' => $featuredPath],
            ['old' => $existing['attachment_path'] ?? '', 'new' => $attachmentPath]
        ] as $change) {
            if ($change['old'] !== '' && $change['old'] !== $change['new'] && blogCanDeleteStoredFile($change['old'])) {
                $stalePath = UPLOADS_DIR . ltrim($change['old'], '/');
                if (file_exists($stalePath)) @unlink($stalePath);
            }
        }
    }

    if ($existing) {
        dbWrite(
            "UPDATE blog_posts SET title=?, slug=?, excerpt=?, content=?, category=?, status=?, featured_image=?, attachment_path=?, attachment_name=?, attachment_mime=?, attachment_size=?, published_at=?, author=?, updated_at=NOW() WHERE id=?",
            [
                $title,
                $slug,
                trim($data['excerpt'] ?? ''),
                trim($data['content'] ?? ''),
                trim($data['category'] ?? 'news'),
                $status,
                $featuredPath,
                $attachmentPath,
                $attachmentName,
                $attachmentMime,
                $attachmentSize,
                $publishedAt,
                currentUsername(),
                $id
            ]
        );
        logActivityDb('blog_update', $title);
    } else {
        $id = 'blog_' . uniqid();
        dbWrite(
            "INSERT INTO blog_posts (id, title, slug, excerpt, content, category, status, featured_image, attachment_path, attachment_name, attachment_mime, attachment_size, published_at, author, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
            [
                $id,
                $title,
                $slug,
                trim($data['excerpt'] ?? ''),
                trim($data['content'] ?? ''),
                trim($data['category'] ?? 'news'),
                $status,
                $featuredPath,
                $attachmentPath,
                $attachmentName,
                $attachmentMime,
                $attachmentSize,
                $publishedAt,
                currentUsername()
            ]
        );
        logActivityDb('blog_create', $title);
    }

    return ['success' => true, 'message' => 'Blog post saved', 'id' => $id, 'slug' => $slug];
}

function blogPostsDelete() {
    $ok = v3RequireAdmin();
    if ($ok !== true) return $ok;
    $id = trim($_GET['id'] ?? '');
    if ($id === '') return ['success' => false, 'error' => 'Post id is required'];
    $row = dbOne("SELECT * FROM blog_posts WHERE id=?", [$id]);
    if (!$row) return ['success' => false, 'error' => 'Post not found'];
    foreach (['featured_image', 'attachment_path'] as $field) {
        if (!empty($row[$field]) && blogCanDeleteStoredFile($row[$field])) {
            $path = UPLOADS_DIR . ltrim($row[$field], '/');
            if (file_exists($path)) @unlink($path);
        }
    }
    dbWrite("DELETE FROM blog_posts WHERE id=?", [$id]);
    logActivityDb('blog_delete', $row['title'] ?? $id);
    return ['success' => true, 'message' => 'Blog post deleted'];
}
?>
