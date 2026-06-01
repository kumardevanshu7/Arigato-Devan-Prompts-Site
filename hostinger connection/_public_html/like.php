<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Must be logged in to like.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $prompt_id = $_POST['prompt_id'] ?? 0;
    
    if (!$prompt_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid prompt.']);
        exit();
    }

    try {
        // Check if user already liked
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND prompt_id = ?");
        $stmt->execute([$user_id, $prompt_id]);
        $like = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($like) {
            // Unlike
            $pdo->prepare("DELETE FROM likes WHERE id = ?")->execute([$like['id']]);
            $pdo->prepare("UPDATE prompts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = ?")->execute([$prompt_id]);
            $action = 'unliked';
        } else {
            // Like
            $pdo->prepare("INSERT INTO likes (user_id, prompt_id) VALUES (?, ?)")->execute([$user_id, $prompt_id]);
            $pdo->prepare("UPDATE prompts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$prompt_id]);
            $action = 'liked';
        }

        // Fetch updated count
        $stmt = $pdo->prepare("SELECT likes_count FROM prompts WHERE id = ?");
        $stmt->execute([$prompt_id]);
        $likes_count = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'action' => $action, 'likes_count' => (int)$likes_count]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}
?>

