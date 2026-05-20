<?php
session_start();
require_once "db.php";
header('Content-Type: application/json');

$blog_id  = (int)($_POST['blog_id'] ?? 0);
$reaction = trim($_POST['reaction'] ?? '');
$valid    = ['heart', 'fire', 'wow'];

if (!$blog_id || !in_array($reaction, $valid, true)) {
    echo json_encode(['ok' => false, 'msg' => 'invalid']);
    exit;
}

$rk = isset($_SESSION['user_id'])
    ? 'u' . $_SESSION['user_id']
    : 'ip' . md5($_SERVER['REMOTE_ADDR']);

try {
    $check = $pdo->prepare("SELECT id FROM blog_reactions WHERE blog_id=? AND reactor_key=? AND reaction=?");
    $check->execute([$blog_id, $rk, $reaction]);

    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM blog_reactions WHERE blog_id=? AND reactor_key=? AND reaction=?")
            ->execute([$blog_id, $rk, $reaction]);
        $active = false;
    } else {
        $pdo->prepare("INSERT INTO blog_reactions (blog_id, reactor_key, reaction) VALUES (?,?,?)")
            ->execute([$blog_id, $rk, $reaction]);
        $active = true;
    }

    $counts_stmt = $pdo->prepare("SELECT reaction, COUNT(*) as cnt FROM blog_reactions WHERE blog_id=? GROUP BY reaction");
    $counts_stmt->execute([$blog_id]);
    $counts = ['heart' => 0, 'fire' => 0, 'wow' => 0];
    foreach ($counts_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $counts[$row['reaction']] = (int)$row['cnt'];
    }

    echo json_encode(['ok' => true, 'active' => $active, 'reaction' => $reaction, 'counts' => $counts]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'db_error']);
}
