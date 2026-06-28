<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAdmin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: AdminLogin.php');
        exit;
    }
}

function isSuperAdmin(): bool {
    return ($_SESSION['admin_role'] ?? '') === 'super';
}

function logAction(PDO $pdo, string $action, string $targetType = '', int $targetId = 0, string $note = ''): void {
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_type, target_id, note)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$_SESSION['admin_id'], $action, $targetType, $targetId, $note]);
}
