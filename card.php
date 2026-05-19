<?php
/**
 * card.php — Universal shareable card link with rich OG previews
 * Usage: card.php?id=123
 *
 * - Renders Open Graph + Twitter Card meta tags so when this URL
 *   is shared on WhatsApp / Insta / Twitter / FB / iMessage etc.,
 *   a beautiful preview card appears (image + title + description).
 * - Real users (browsers) are redirected via JS/meta-refresh to
 *   the correct category page with ?open=ID so the modal opens.
 * - Social-media crawlers don't run JS, so they only read the meta tags.
 */
session_start();
require_once 'db.php';

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    header("Location: index.php");
    exit();
}

// Fetch enough fields to build a rich preview
$stmt = $pdo->prepare("SELECT id, title, image_path, prompt_type, tag FROM prompts WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    header("Location: index.php");
    exit();
}

// Map prompt_type → correct category page (same as before)
$page_map = [
    'secret'           => 'secret_code.php',
    'unreleased'       => 'unreleased.php',
    'insta_viral'      => 'insta_viral.php',
    'already_uploaded' => 'already_uploaded.php',
];
$target = $page_map[$p['prompt_type']] ?? 'gallery.php';

// Build absolute base URL (works on local XAMPP and on Hostinger)
$proto    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'arigatodevan.com';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$baseUrl  = $proto . '://' . $host . $basePath;

// Build absolute image URL (image_path may be relative or absolute)
$img = trim((string) ($p['image_path'] ?? ''));
if ($img !== '' && !preg_match('~^https?://~i', $img)) {
    $img = $baseUrl . '/' . ltrim($img, '/');
}
if ($img === '') {
    // Fallback to logo / generic image if prompt has no image
    $img = $baseUrl . '/logo.png';
}

// Friendly labels per prompt type for description
$type_labels = [
    'secret'           => 'Secret Code',
    'unreleased'       => 'Unreleased',
    'insta_viral'      => 'Insta Viral',
    'already_uploaded' => 'Already Uploaded',
];
$type_label = $type_labels[$p['prompt_type']] ?? 'Prompt';

$title       = $p['title'] ?: 'AI Couple Prompt';
$ogTitle     = $title . ' \u2014 Arigato Devan Prompts';
$description = "\xF0\x9F\x92\x95 Unlock this dreamy {$type_label} AI couple prompt on Arigato Devan \xE2\x80\x94 perfect for Gemini Nano 2 & Pro. Tap to reveal the magic!";

$shareUrl    = $baseUrl . '/card.php?id=' . $p['id'];
$redirectUrl = $baseUrl . '/' . $target . '?open=' . $p['id'];

/**
 * Detect social-media crawlers / preview bots.
 * Bots get the OG-only page (no redirect at all).
 * Real users get an instant server-side redirect to the category page.
 */
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isBot = (bool) preg_match(
    '/facebookexternalhit|Facebot|WhatsApp|Twitterbot|LinkedInBot|Slackbot|TelegramBot|Discordbot|Pinterest|redditbot|Googlebot|bingbot|DuckDuckBot|Applebot|Embedly|vkShare|W3C_Validator|SkypeUriPreview|Iframely|Snapchat|TikTokBot|Bytespider/i',
    $ua
);

if (!$isBot) {
    // Real user — instant redirect, no flicker
    header("Location: {$redirectUrl}", true, 302);
    exit();
}

// Bot / crawler — render OG preview page WITHOUT any redirect
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($title) ?> — Arigato Devan Prompts</title>

<!-- Open Graph (WhatsApp / Facebook / Instagram / iMessage / Telegram) -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="Arigato Devan Prompts">
<meta property="og:title" content="<?= htmlspecialchars($title) ?> — Arigato Devan">
<meta property="og:description" content="<?= htmlspecialchars($description) ?>">
<meta property="og:image" content="<?= htmlspecialchars($img) ?>">
<meta property="og:image:secure_url" content="<?= htmlspecialchars($img) ?>">
<meta property="og:image:alt" content="<?= htmlspecialchars($title) ?>">
<meta property="og:url" content="<?= htmlspecialchars($shareUrl) ?>">

<!-- Twitter / X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($title) ?> — Arigato Devan">
<meta name="twitter:description" content="<?= htmlspecialchars($description) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($img) ?>">

<!-- Standard meta -->
<meta name="description" content="<?= htmlspecialchars($description) ?>">
<link rel="canonical" href="<?= htmlspecialchars($shareUrl) ?>">
</head>
<body>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p><?= htmlspecialchars($description) ?></p>
    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($title) ?>" style="max-width:600px;">
    <p><a href="<?= htmlspecialchars($redirectUrl) ?>">View on Arigato Devan</a></p>
</body>
</html>

