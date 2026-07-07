<?php
/**
 * Clean URL entry: /not-mine/{slug} → loads not_mine_prompt.php
 */
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: ../not_mine.php');
    exit();
}

$_GET['slug'] = $slug;
require dirname(__DIR__) . '/not_mine_prompt.php';
