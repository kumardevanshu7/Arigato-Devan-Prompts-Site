<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// 🔒 Login check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit();
}

// 🔒 Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];
$blog_id = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;

if ($blog_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid blog']);
    exit();
}

try {
    // 🔥 Transaction start
    $pdo->beginTransaction();

    // Check existing like
    $check = $pdo->prepare("SELECT id FROM blog_likes WHERE user_id=? AND blog_id=?");
    $check->execute([$user_id, $blog_id]);
    $liked = $check->fetch(PDO::FETCH_ASSOC);

    if ($liked) {
        // 🔥 UNLIKE
        $pdo->prepare("DELETE FROM blog_likes WHERE user_id=? AND blog_id=?")
            ->execute([$user_id, $blog_id]);

        $pdo->prepare("
            UPDATE blogs 
            SET likes_count = CASE 
                WHEN likes_count > 0 THEN likes_count - 1 
                ELSE 0 
            END 
            WHERE id=?
        ")->execute([$blog_id]);

        $action = 'unliked';

    } else {
        // 🔥 LIKE (prevent duplicate)
        $pdo->prepare("
            INSERT IGNORE INTO blog_likes (user_id, blog_id) 
            VALUES (?, ?)
        ")->execute([$user_id, $blog_id]);

        $pdo->prepare("
            UPDATE blogs 
            SET likes_count = likes_count + 1 
            WHERE id=?
        ")->execute([$blog_id]);

        $action = 'liked';
    }

    // Get updated count
    $stmt = $pdo->prepare("SELECT likes_count FROM blogs WHERE id=?");
    $stmt->execute([$blog_id]);
    $likes = (int)$stmt->fetchColumn();

    // 🔥 Commit
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes_count' => $likes
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>