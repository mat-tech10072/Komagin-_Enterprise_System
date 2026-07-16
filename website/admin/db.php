<?php
// adminpanel/db.php
// Single source of truth for DB connection - include this everywhere.

require_once __DIR__ . '/db_config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Throw so the caller decides how to respond — never exit here,
            // because calling code (HTML pages, API handlers) needs to handle
            // errors in their own way.
            throw new RuntimeException(
                'Database unavailable. Check your database credentials in admin/db_config.php and ensure MySQL is running. Detail: ' . $e->getMessage(),
                503,
                $e
            );
        }
    }
    return $pdo;
}

function log_activity(PDO $pdo, string $action, string $details = ''): void {
    $user_id = $_SESSION['user_id'] ?? ($_SESSION['admin_id'] ?? ($_SESSION['hr_user_id'] ?? ($_SESSION['branch_user_id'] ?? null)));
    $username = $_SESSION['username'] ?? ($_SESSION['admin_username'] ?? ($_SESSION['hr_username'] ?? ($_SESSION['branch_username'] ?? 'unknown')));
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, username, action, details, ip_address, user_agent, created_at) VALUES (?,?,?,?,?,?,NOW())");
        $stmt->execute([$user_id, $username, $action, $details, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
    } catch (Throwable $e) {
        // Logging must not interrupt user-facing workflows.
    }
}

function ensure_branch_content_schema(PDO $pdo): void {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS branch_content_submissions (
            id VARCHAR(50) NOT NULL,
            branch_id VARCHAR(50) NOT NULL,
            project_id VARCHAR(50) DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            submission_type ENUM('document','announcement','progress_update','photo','other') DEFAULT 'document',
            description TEXT DEFAULT NULL,
            content_body TEXT DEFAULT NULL,
            file_path VARCHAR(500) DEFAULT NULL,
            original_name VARCHAR(255) DEFAULT NULL,
            file_size BIGINT DEFAULT NULL,
            mime_type VARCHAR(100) DEFAULT NULL,
            status ENUM('submitted','under_review','approved','rejected','published','archived') DEFAULT 'submitted',
            submitted_by VARCHAR(100) DEFAULT NULL,
            reviewed_by VARCHAR(100) DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            admin_notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_branch_content_branch (branch_id),
            KEY idx_branch_content_status (status),
            KEY idx_branch_content_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        // Schema repair must not interrupt an otherwise working request.
    }
}

function komagin_table_exists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$table]);
        return ((int)$stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function komagin_column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $stmt->execute([$table, $column]);
        return ((int)$stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function ensure_komagin_workflow_schema(PDO $pdo): void {
    try {
        if (komagin_table_exists($pdo, 'projects') && !komagin_column_exists($pdo, 'projects', 'branch_id')) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN branch_id VARCHAR(50) DEFAULT NULL AFTER category");
        }
        if (komagin_table_exists($pdo, 'projects') && komagin_column_exists($pdo, 'projects', 'branch_id')) {
            $pdo->exec("ALTER TABLE projects MODIFY branch_id VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL");
        }
        if (komagin_table_exists($pdo, 'branch_projects') && !komagin_column_exists($pdo, 'branch_projects', 'source_project_id')) {
            $pdo->exec("ALTER TABLE branch_projects ADD COLUMN source_project_id VARCHAR(50) DEFAULT NULL AFTER id");
        }
        if (komagin_table_exists($pdo, 'branch_site_reports') && !komagin_column_exists($pdo, 'branch_site_reports', 'attachment_path')) {
            $pdo->exec("ALTER TABLE branch_site_reports ADD COLUMN attachment_path VARCHAR(500) DEFAULT NULL AFTER photos");
            $pdo->exec("ALTER TABLE branch_site_reports ADD COLUMN attachment_name VARCHAR(255) DEFAULT NULL AFTER attachment_path");
            $pdo->exec("ALTER TABLE branch_site_reports ADD COLUMN attachment_mime VARCHAR(120) DEFAULT NULL AFTER attachment_name");
            $pdo->exec("ALTER TABLE branch_site_reports ADD COLUMN attachment_size BIGINT DEFAULT NULL AFTER attachment_mime");
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS hr_templates (
            id VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            category VARCHAR(100) DEFAULT 'standard',
            template_schema JSON DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_by VARCHAR(100) DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_hr_templates_active (is_active),
            KEY idx_hr_templates_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS branch_template_submissions (
            id VARCHAR(50) NOT NULL,
            template_id VARCHAR(50) NOT NULL,
            branch_id VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            form_data JSON DEFAULT NULL,
            save_directory VARCHAR(255) DEFAULT 'Branch Templates',
            status ENUM('draft','submitted','reviewed','archived') DEFAULT 'draft',
            submitted_by VARCHAR(100) DEFAULT NULL,
            submitted_at DATETIME DEFAULT NULL,
            reviewed_by VARCHAR(100) DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_template_submission_template (template_id),
            KEY idx_template_submission_branch (branch_id),
            KEY idx_template_submission_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (
            id VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            excerpt TEXT DEFAULT NULL,
            content LONGTEXT DEFAULT NULL,
            category VARCHAR(100) DEFAULT 'news',
            status ENUM('draft','published','archived') DEFAULT 'draft',
            featured_image VARCHAR(500) DEFAULT NULL,
            attachment_path VARCHAR(500) DEFAULT NULL,
            attachment_name VARCHAR(255) DEFAULT NULL,
            attachment_mime VARCHAR(120) DEFAULT NULL,
            attachment_size BIGINT DEFAULT NULL,
            published_at DATETIME DEFAULT NULL,
            author VARCHAR(100) DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_blog_slug (slug),
            KEY idx_blog_status (status),
            KEY idx_blog_category (category),
            KEY idx_blog_published (published_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        // Workflow schema repair must not interrupt panel loading.
    }
}

// Connection is lazy — get_db() is called on first use, not at require time.
?>
