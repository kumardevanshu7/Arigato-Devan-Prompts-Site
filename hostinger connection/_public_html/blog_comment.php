<?php
// Blog Comment Submit "&ndash; AJAX/POST endpoint
session_start();
require_once "db.php";
header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Login required to comment",
    ]);
    exit();
}

$blog_id = (int) ($_POST["blog_id"] ?? 0);
$comment = strip_tags(trim($_POST["comment"] ?? ""));

if (!$blog_id || !$comment) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit();
}

if (mb_strlen($comment) > 1000) {
    echo json_encode([
        "success" => false,
        "message" => "Comment too long (max 1000 chars)",
    ]);
    exit();
}

$user_id = $_SESSION["user_id"];
$pdo->prepare(
    "INSERT INTO blog_comments (blog_id, user_id, comment) VALUES (?,?,?)",
)->execute([$blog_id, $user_id, $comment]);

// Return comment data for live append
$user = $pdo->prepare(
    "SELECT username, avatar as profile_image FROM users WHERE id=?",
);
$user->execute([$user_id]);
$u = $user->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "comment" => htmlspecialchars($comment),
    "username" => htmlspecialchars($u["username"] ?? "User"),
    "avatar" => htmlspecialchars(
        $u["profile_image"] ??
            "https://api.dicebear.com/7.x/avataaars/svg?seed=x",
    ),
    "time" => "Just now",
]);
