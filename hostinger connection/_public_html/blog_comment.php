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
$comment = trim($_POST['comment'] ?? '');

// 🔒 Validation
if ($blog_id <= 0 || $comment === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

if (mb_strlen($comment) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Max 1000 characters']);
    exit();
}

try {
    // 🔥 Insert comment
    $stmt = $pdo->prepare("INSERT INTO blog_comments (blog_id, user_id, comment) VALUES (?,?,?)");
    $stmt->execute([$blog_id, $user_id, $comment]);

    // 🔥 Fetch user
    $user = $pdo->prepare("SELECT username, avatar FROM users WHERE id=?");
    $user->execute([$user_id]);
    $u = $user->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'comment'  => htmlspecialchars($comment, ENT_QUOTES, 'UTF-8'),
        'username' => htmlspecialchars($u['username'] ?? 'User', ENT_QUOTES, 'UTF-8'),
        'avatar'   => htmlspecialchars($u['avatar'] ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=user', ENT_QUOTES, 'UTF-8'),
        'time'     => 'Just now'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>