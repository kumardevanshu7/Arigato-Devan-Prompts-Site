<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';

$shots = [];
try {
    $shots = $pdo->query(
        'SELECT * FROM happy_users_screenshots WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $shots = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FFF8FA">
    <title>Happy Users — Arigato Devan Prompts</title>
    <meta name="description" content="Real screenshots and kind words from happy Arigato Devan users — see what the community loves about our prompts.">
    <?php include_once 'includes/theme_head.php'; ?>
    <link rel="stylesheet" href="css/info-pages.css?v=20260721">
    <link rel="stylesheet" href="css/happy-users-page.css?v=20260791">
    <link rel="stylesheet" href="css/happy-users-lightbox.css?v=20260787">
    <?php include_once 'gtag.php'; ?>
</head>
<body class="page-store theme-nogoda page-info page-happy-users">

<?php $nav_active = ''; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

<main class="hu-main">
    <header class="hu-hero">
        <p class="hero-label"><i class="fa-solid fa-heart"></i> Community Love</p>
        <h1><em>Happy</em> Users</h1>
        <p>Real chats from creators who loved our prompts — sweet words, thank-yous, and praise that made our day. Your kindness means everything.</p>
    </header>

    <?php if (empty($shots)): ?>
        <div class="hu-empty">
            <div><i class="fa-solid fa-heart"></i></div>
            <p>Happy user moments coming soon!</p>
        </div>
    <?php else: ?>
        <div class="hu-masonry" aria-label="Happy user screenshots">
            <?php foreach ($shots as $i => $shot):
                $comment_num = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            ?>
            <figure class="hu-pin">
                <div class="hu-pin-media">
                    <span class="hu-chat-badge">#User_Chat_<?= $comment_num ?></span>
                    <div class="hu-pin-img-wrap">
                    <img src="<?= htmlspecialchars($shot['image_path']) ?>"
                         alt="User chat <?= $comment_num ?>"
                         loading="lazy"
                         <?php if (!empty($shot['img_width']) && !empty($shot['img_height'])): ?>
                         width="<?= (int) $shot['img_width'] ?>"
                         height="<?= (int) $shot['img_height'] ?>"
                         <?php endif; ?>>
                    </div>
                </div>
            </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include_once 'footer.php'; ?>

<?php if (!empty($shots)): ?>
<div class="hu-lightbox" id="huLightbox" aria-hidden="true" role="dialog" aria-label="Screenshot preview">
    <div class="hu-lb-backdrop" aria-hidden="true"></div>
    <button type="button" class="hu-lb-close" aria-label="Close preview"><i class="fa-solid fa-xmark"></i></button>
    <button type="button" class="hu-lb-nav hu-lb-prev" aria-label="Previous screenshot"><i class="fa-solid fa-chevron-left"></i></button>
    <div class="hu-lb-stage">
        <img class="hu-lb-img" src="" alt="">
    </div>
    <button type="button" class="hu-lb-nav hu-lb-next" aria-label="Next screenshot"><i class="fa-solid fa-chevron-right"></i></button>
    <p class="hu-lb-counter" aria-live="polite"></p>
    <p class="hu-lb-swipe-hint">Swipe left or right</p>
</div>
<script src="js/happy-users-lightbox.js?v=20260787" defer></script>
<?php endif; ?>
</body>
</html>
