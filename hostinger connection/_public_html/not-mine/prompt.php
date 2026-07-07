<?php
/**
 * Clean URL entry: /not-mine/{slug} → loads not_mine_prompt.php
 */
$slug = trim($_GET['slug'] ?? '');
if ($slug === '' && !empty($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/not-mine/([a-z0-9][a-z0-9-]*)/?$#i', $_SERVER['REQUEST_URI'], $m)) {
        $slug = strtolower($m[1]);
    }
}
if ($slug === '') {
    header('Location: ../not_mine.php');
    exit();
}

$_GET['slug'] = $slug;
require dirname(__DIR__) . '/not_mine_prompt.php';
