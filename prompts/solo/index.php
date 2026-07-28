<?php
/**
 * Router target for clean SOLO URLs: /prompts/solo/{slug}
 *
 * The actual page stays in the shared root prompt.php so SOLO receives the
 * same authentication, unlock, SEO and related-prompt behaviour.
 */
$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' || !preg_match('/^[a-z0-9][a-z0-9-]*$/i', $slug)) {
    header('Location: ../../gallery.php');
    exit;
}

$_GET['slug'] = strtolower($slug);
chdir(dirname(__DIR__, 2));
require __DIR__ . '/../../prompt.php';
