<?php
session_start();
require_once 'db.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$type = $_POST['type'] ?? '';    // 'existing' or 'custom'
$id   = (int)($_POST['id'] ?? 0);
$active = (int)($_POST['active'] ?? 0);

if (!$id || !in_array($type, ['existing', 'custom'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid params']);
    exit();
}

try {
    // First: deactivate everything
    $pdo->exec("UPDATE prompts SET is_featured = 0");
    $pdo->exec("UPDATE potd_custom SET is_active = 0");

    if ($active) {
        // Activate the selected one
        if ($type === 'existing') {
            $stmt = $pdo->prepare("UPDATE prompts SET is_featured = 1 WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("UPDATE potd_custom SET is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
    // If $active === 0, everything is already deactivated (no POTD)

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
