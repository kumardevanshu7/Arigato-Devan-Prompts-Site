<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }
$id = (int)($_POST['blog_id'] ?? 0);
if ($id) {
    $pdo->prepare("DELETE FROM blog_likes WHERE blog_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM blog_comments WHERE blog_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM blogs WHERE id=?")->execute([$id]);
    $_SESSION['success_msg'] = '<i class="fa-solid fa-check"></i> Blog deleted.';
}
header("Location: blog_admin.php"); exit();




