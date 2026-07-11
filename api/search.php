<?php
require_once dirname(__DIR__) . '/auth/session.php';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

$like = '%' . $q . '%';
$results = [];

// Employees
if (hasPermission('employees.view')) {
    $stmt = db()->prepare("SELECT id, employee_number, first_name, last_name, status
        FROM employees WHERE (first_name LIKE ? OR last_name LIKE ? OR employee_number LIKE ?) AND status != 'archived'
        ORDER BY first_name LIMIT 5");
    $stmt->execute([$like, $like, $like]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'type'  => 'employee',
            'icon'  => 'user',
            'title' => $r['first_name'].' '.$r['last_name'],
            'sub'   => $r['employee_number'].' · '.ucfirst($r['status']),
            'url'   => APP_URL.'/modules/employees/view.php?id='.$r['id'],
        ];
    }
}

// Documents (generated)
if (hasPermission('documents.view')) {
    $stmt = db()->prepare("SELECT gd.id, gd.title, e.first_name, e.last_name, gd.status
        FROM generated_documents gd JOIN employees e ON gd.employee_id=e.id
        WHERE gd.title LIKE ? LIMIT 4");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'type'  => 'document',
            'icon'  => 'file-text',
            'title' => $r['title'],
            'sub'   => 'Document for '.$r['first_name'].' '.$r['last_name'].' · '.ucfirst($r['status']),
            'url'   => APP_URL.'/modules/documents/view_generated.php?id='.$r['id'],
        ];
    }
}

// Document templates
if (hasPermission('documents.view')) {
    $stmt = db()->prepare("SELECT id, title, description FROM doc_templates WHERE title LIKE ? AND is_active=1 LIMIT 3");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'type'  => 'template',
            'icon'  => 'layout',
            'title' => $r['title'],
            'sub'   => 'Template — '.(substr($r['description']??'',0,50) ?: 'Click to generate'),
            'url'   => APP_URL.'/modules/documents/generate.php?template='.$r['id'],
        ];
    }
}

// Audit logs
if (hasPermission('audit.view')) {
    $stmt = db()->prepare("SELECT id, module, action, reason FROM audit_logs WHERE reason LIKE ? OR action LIKE ? ORDER BY created_at DESC LIMIT 3");
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'type'  => 'audit',
            'icon'  => 'shield',
            'title' => ucfirst($r['module']).': '.$r['action'],
            'sub'   => $r['reason'] ?? 'Audit entry',
            'url'   => APP_URL.'/modules/audit/index.php',
        ];
    }
}

echo json_encode(array_slice($results, 0, 10));
