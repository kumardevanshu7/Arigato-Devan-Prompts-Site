<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }
$id     = (int)($_POST['blog_id'] ?? 0);
$status = (int)($_POST['status'] ?? 0);
if ($id) {
    $pdo->prepare("UPDATE blogs SET is_published=? WHERE id=?")->execute([$status, $id]);
    $_SESSION['success_msg'] = $status ? '<i class="fa-solid fa-check"></i> Blog published!' : '<i class="fa-solid fa-check"></i> Blog unpublished.';
}
header("Location: blog_admin.php"); exit();





