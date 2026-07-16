<?php
// Consultant Portal Session Guard — include at the top of every portal page
// (after _config.php).
require_once dirname(__DIR__) . '/auth/session_common.php';

if (!bootstrapSession('cp_', 28800)) {
    header('Location: ' . CP_URL . '/login.php?reason=timeout');
    exit;
}

function cpIsLoggedIn(): bool {
    return !empty($_SESSION['cp_consultant_id']);
}

function cpRequireLogin(): void {
    if (empty($_SESSION['cp_consultant_id'])) {
        header('Location: ' . CP_URL . '/login.php');
        exit;
    }
}

function cpRequireType(string $type): void {
    cpRequireLogin();
    if (($_SESSION['cp_type'] ?? '') !== $type) {
        header('Location: ' . CP_URL . '/dashboard.php');
        exit;
    }
}

function cpCurrentConsultant(): array {
    if (empty($_SESSION['cp_consultant_id'])) return [];
    static $con = null;
    if ($con === null) {
        $stmt = db()->prepare("SELECT * FROM consultants WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['cp_consultant_id']]);
        $con = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    return $con;
}
