<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

require_once 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = trim($input['token'] ?? '');

if (empty($token) || strlen($token) < 50) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO fcm_tokens (token, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), updated_at = NOW()");
    $stmt->execute([$token, $user_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB error']);
}
?>
