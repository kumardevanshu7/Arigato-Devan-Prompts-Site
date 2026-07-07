<?php
require_once __DIR__ . '/bootstrap.php';

$stories = $pdo->query("
    SELECT id, title, slug, description, poster_image, created_at
    FROM web_stories
    WHERE is_published = 1
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$site_root = '../';
$nav_base = '../';
$site_base = '../';
$asset_base = '../';
$nav_active = 'web_stories';
$nav_brand_words = ['prompt', 'stories', 'devan'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2F4156">
    <title>Web Stories &mdash; Arigato Devan</title>
    <meta name="description" content="Visual AI prompt stories — swipe through tips, codes and inspiration from Arigato Devan.">
    <link rel="canonical" href="https://arigatodevan.com/web_stories/">
    <link rel="icon" href="<?= $site_root ?>favicon.ico" type="image/x-icon">
    <?php
    $theme_head_path = SITE_ROOT . '/includes/theme_head.php';
    if (is_file($theme_head_path)) include $theme_head_path;
    $gtag_path = SITE_ROOT . '/gtag.php';
    if (is_file($gtag_path)) include $gtag_path;
    ?>
    <link rel="stylesheet" href="assets/css/public.css?v=5">
</head>
<body class="page-store page-web-stories theme-nogoda">
<?php include SITE_ROOT . '/includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>
<main class="ws-wrap">
    <section class="ws-hero">
        <p class="hero-label" style="justify-content:center;">Swipe &amp; Read</p>
        <h1>Web Stories</h1>
        <p>Phone-style visual stories — AI prompts, tips aur inspiration. Tap karo, swipe karo.</p>
    </section>
    <?php if (empty($stories)): ?>
        <div class="ws-empty">
            <h2>Stories coming soon</h2>
            <p>Nayi web stories jaldi yahan dikhengi.</p>
        </div>
    <?php else: ?>
        <div class="ws-toolbar" id="ws-view-toolbar" aria-label="Grid view">
            <span class="ws-toolbar-label"><i class="fa-solid fa-table-cells" aria-hidden="true"></i> View</span>
            <div class="ws-view-btns" role="group" aria-label="Columns">
                <?php foreach ([3, 4, 5, 6, 7] as $n): ?>
                <button type="button" class="ws-view-btn" data-cols="<?= $n ?>" aria-pressed="false"><?= $n ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="ws-grid" id="ws-grid" data-cols="5">
            <?php foreach ($stories as $s):
                $poster = $s['poster_image'] ?: '';
            ?>
            <a class="ws-card" href="story.php?slug=<?= urlencode($s['slug']) ?>">
                <div class="ws-poster">
                    <?php if ($poster): ?>
                    <img src="<?= $site_root . htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($s['title']) ?>" loading="lazy" width="360" height="640">
                    <?php endif; ?>
                </div>
                <div class="ws-meta">
                    <h2><?= htmlspecialchars($s['title']) ?></h2>
                    <?php if (!empty($s['description'])): ?>
                    <p><?= htmlspecialchars($s['description']) ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php include SITE_ROOT . '/footer.php'; ?>
<script src="assets/js/public.js?v=1"></script>
</body>
</html>
