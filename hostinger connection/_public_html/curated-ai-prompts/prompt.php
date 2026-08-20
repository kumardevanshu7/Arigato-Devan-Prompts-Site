<?php
/**
 * Clean URL entry: /curated-ai-prompts/{slug} → loads curated_prompt.php
 */
$slug = trim($_GET['slug'] ?? '');
if ($slug === '' && !empty($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/curated-ai-prompts/([a-z0-9][a-z0-9-]*)/?$#i', $_SERVER['REQUEST_URI'], $m)) {
        $slug = strtolower($m[1]);
    }
}
if ($slug === '') {
    header('Location: ../curated_ai_prompts.php');
    exit();
}

$_GET['slug'] = $slug;
require dirname(__DIR__) . '/curated_prompt.php';
