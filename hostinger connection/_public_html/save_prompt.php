<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$prompt_id = (int)($_POST['prompt_id'] ?? 0);
if (!$prompt_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid prompt']);
    exit;
}

// Verify prompt exists
$stmt = $pdo->prepare("SELECT id FROM prompts WHERE id = ?");
$stmt->execute([$prompt_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Prompt not found']);
    exit;
}

try {
    $ins = $pdo->prepare("INSERT IGNORE INTO unlocked_prompts (user_id, prompt_id) VALUES (?, ?)");
    $ins->execute([$_SESSION['user_id'], $prompt_id]);
    echo json_encode(['success' => true, 'message' => 'Prompt saved to your account!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Could not save prompt.']);
}
?>
