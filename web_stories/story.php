<?php
require_once __DIR__ . '/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: index.php');
    exit;
}

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$preview = $is_admin && !empty($_GET['preview']);

$sql = $preview
    ? 'SELECT * FROM web_stories WHERE slug = ? LIMIT 1'
    : 'SELECT * FROM web_stories WHERE slug = ? AND is_published = 1 LIMIT 1';
$st = $pdo->prepare($sql);
$st->execute([$slug]);
$story = $st->fetch(PDO::FETCH_ASSOC);

if (!$story) {
    http_response_code(404);
    echo 'Story not found.';
    exit;
}

$pg = $pdo->prepare('SELECT * FROM web_story_pages WHERE story_id = ? ORDER BY sort_order ASC, id ASC');
$pg->execute([(int)$story['id']]);
$pages = $pg->fetchAll(PDO::FETCH_ASSOC);

if (count($pages) < 1) {
    http_response_code(404);
    echo 'This story has no pages yet.';
    exit;
}

ws_render_amp_story($pdo, $story, $pages);
