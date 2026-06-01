<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$prompt_id = (int)($_POST['prompt_id'] ?? 0);
$action = $_POST['action'] ?? 'save';

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

$user_id = (int)$_SESSION['user_id'];

try {
    if ($action === 'unsave') {
        $del = $pdo->prepare("DELETE FROM saved_prompts WHERE user_id = ? AND prompt_id = ?");
        $del->execute([$user_id, $prompt_id]);
        echo json_encode([
            'success' => true,
            'saved'   => false,
            'message' => 'Removed from saved prompts.'
        ]);
        exit;
    }

    // Default: save
    $ins = $pdo->prepare("INSERT IGNORE INTO saved_prompts (user_id, prompt_id) VALUES (?, ?)");
    $ins->execute([$user_id, $prompt_id]);
    echo json_encode([
        'success' => true,
        'saved'   => true,
        'message' => 'Prompt saved to your account!'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
