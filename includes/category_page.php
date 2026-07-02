<?php
/**
 * Full category page shell — Nogoda theme.
 * Set these before include:
 *   $page_title, $meta_desc, $canonical_url
 *   $cat_prompts, $cat_badge, $cat_title, $cat_title_em, $cat_desc
 * Optional:
 *   $cat_steps_page, $cat_exclude_tag, $cat_empty_*, $cat_nav_active, $breadcrumb_name
 */
$cat_nav_active  = $cat_nav_active ?? '';
$cat_instruction = $cat_instruction ?? null;
$cat_hide_hero   = !empty($cat_hide_hero);
$breadcrumb_name = $breadcrumb_name ?? ($cat_title . ' ' . $cat_title_em);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#204162">
    <base href="<?= ($_SERVER['HTTP_HOST'] === 'localhost') ? '/Arigato%20Development%20Site/' : '/' ?>">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://arigatodevan.com'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $breadcrumb_name, 'item' => $canonical_url],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>
    <?php include_once __DIR__ . '/theme_head.php'; ?>
    <?php include_once __DIR__ . '/card_skeleton_assets.php'; ?>
    <link rel="stylesheet" href="css/category-pages.css?v=20260729">
    <?php include_once __DIR__ . '/../gtag.php'; ?>
</head>
<body class="page-store theme-nogoda page-category<?= !empty($cat_instruction) ? ' has-cat-instruction' : '' ?>">

<?php include __DIR__ . '/category_content.php'; ?>

<?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
