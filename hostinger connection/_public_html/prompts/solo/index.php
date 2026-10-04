<?php
/**
 * Router target for clean SOLO URLs: /prompts/solo/{slug}
 *
 * The actual page stays in the shared root prompt.php so SOLO receives the
 * same authentication, unlock, SEO and related-prompt behaviour.
 */
$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: ../../solo_prompts.php', true, 301);
    exit;
}
if (!preg_match('/^[a-z0-9][a-z0-9-]*$/i', $slug)) {
    http_response_code(404);
    chdir(dirname(__DIR__, 2));
    require __DIR__ . '/../../404.php';
    exit;
}

$_GET['slug'] = strtolower($slug);
chdir(dirname(__DIR__, 2));
require __DIR__ . '/../../prompt.php';
