<?php
// Blog Like Toggle "Ã¢â‚¬Â AJAX endpoint
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit();
}

$blog_id = (int)($_POST['blog_id'] ?? 0);
if (!$blog_id) { echo json_encode(['success' => false]); exit(); }

$user_id = $_SESSION['user_id'];

// Check if already liked
$check = $pdo->prepare("SELECT id FROM blog_likes WHERE user_id=? AND blog_id=?");
$check->execute([$user_id, $blog_id]);

if ($check->fetch()) {
    // Unlike
    $pdo->prepare("DELETE FROM blog_likes WHERE user_id=? AND blog_id=?")->execute([$user_id, $blog_id]);
    $pdo->prepare("UPDATE blogs SET likes_count = GREATEST(0, likes_count - 1) WHERE id=?")->execute([$blog_id]);
    $action = 'unliked';
} else {
    // Like
    $pdo->prepare("INSERT INTO blog_likes (user_id, blog_id) VALUES (?,?)")->execute([$user_id, $blog_id]);
    $pdo->prepare("UPDATE blogs SET likes_count = likes_count + 1 WHERE id=?")->execute([$blog_id]);
    $action = 'liked';
}

$count = $pdo->prepare("SELECT likes_count FROM blogs WHERE id=?");
$count->execute([$blog_id]);
$likes = $count->fetchColumn();

echo json_encode(['success' => true, 'action' => $action, 'likes_count' => (int)$likes]);


