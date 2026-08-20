<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/slug_helper.php';

$slug = trim($_GET['slug'] ?? '');
$id = (int) ($_GET['id'] ?? 0);

if ($slug === '' && $id > 0) {
    try {
        $st = $pdo->prepare('SELECT slug FROM curated_prompts WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $slug = trim((string) ($st->fetchColumn() ?: ''));
    } catch (Exception $e) {
        $slug = '';
    }
    if ($slug === '') {
        try {
            $st = $pdo->prepare('SELECT slug FROM not_mine_prompts WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $slug = trim((string) ($st->fetchColumn() ?: ''));
        } catch (Exception $e) {
            $slug = '';
        }
    }
}

if ($slug !== '') {
    header('Location: /curated-ai-prompts/' . rawurlencode($slug), true, 301);
    exit;
}

header('Location: /curated_ai_prompts.php', true, 301);
exit;
