<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$data  = is_array($input) ? $input : $_POST;

$company = trim($data['company_name'] ?? '');
$email   = trim($data['email'] ?? '');
if (!$company || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Company name and valid email are required']);
    exit;
}

try {
    $pdo = get_db();
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Database unavailable. Please try again later.']);
    exit;
}

$id = 'partner_' . time() . '_' . bin2hex(random_bytes(4));
$pdo->prepare("INSERT INTO partners (id, company_name, contact_name, email, phone, country, expertise, portfolio_url, status, notes, enquiry_date, created_at) VALUES (?,?,?,?,?,?,?,?, 'enquiry', ?, NOW(), NOW())")
    ->execute([
        $id,
        $company,
        trim($data['contact_name'] ?? ''),
        $email,
        trim($data['phone'] ?? ''),
        trim($data['country'] ?? ''),
        trim($data['expertise'] ?? ''),
        trim($data['portfolio_url'] ?? ''),
        trim($data['notes'] ?? ($data['message'] ?? '')),
    ]);

log_activity($pdo, 'partner_enquiry_received', $id);
echo json_encode(['success' => true, 'message' => 'Partner enquiry received. We will contact you shortly.']);
?>
