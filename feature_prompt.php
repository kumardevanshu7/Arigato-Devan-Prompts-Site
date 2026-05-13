<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit();
}
header('Content-Type: application/json');
$id = (int)($_POST['prompt_id'] ?? 0);
if (!$id) { echo json_encode(['success' => false]); exit(); }

// Unfeature all, then feature this one (only 1 featured at a time)
$pdo->exec("UPDATE prompts SET is_featured = 0");
$stmt = $pdo->prepare("UPDATE prompts SET is_featured = 1 WHERE id = ?");
$stmt->execute([$id]);
echo json_encode(['success' => true]);
