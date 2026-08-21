<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id     = (int)($_POST['blog_id'] ?? 0);
    if ($id) {
        if (isset($_POST['slider'])) {
            $on = (int)$_POST['slider'] ? 1 : 0;
            $pdo->prepare("UPDATE blogs SET in_slider=? WHERE id=?")->execute([$on, $id]);
            $_SESSION['success_msg'] = $on
                ? 'Added to homepage slider.'
                : 'Removed from homepage slider.';
        } else {
            $status = (int)($_POST['status'] ?? 0);
            $pdo->prepare("UPDATE blogs SET is_published=? WHERE id=?")->execute([$status, $id]);
            $_SESSION['success_msg'] = $status ? 'Blog published successfully!' : 'Blog unpublished successfully!';
        }
    }
}
header("Location: blog_admin.php"); exit();





