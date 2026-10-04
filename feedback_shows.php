<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';

// Fetch all approved testimonials marked to show
$feedbacks = [];
try {
    $stmt = $pdo->query("
        SELECT f.id, f.feedback_text, f.rating, f.submitted_at,
               u.username, u.avatar, u.profile_image, u.gender
        FROM feedbacks f
        LEFT JOIN users u ON f.user_id = u.id
        WHERE f.show_on_homepage = 1
        ORDER BY f.submitted_at DESC
    ");
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $feedbacks = [];
}

$_page_canonical = 'https://arigatodevan.com/feedback_shows.php';
$page_title      = 'Guest Testimonials — What They Say? | Arigato Devan';
$meta_desc       = 'Real reviews and feedback from creators using Arigato Devan AI prompt collections — honest words from our vibrant community.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FFFFFF">
    <base href="<?= (($_SERVER['HTTP_HOST'] ?? '') === 'localhost') ? '/Arigato%20Development%20Site/' : '/' ?>">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($_page_canonical) ?>">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="<?= htmlspecialchars($_page_canonical) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Cursive script for "Guest Testimonial" eyebrow from mockup -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&display=swap" rel="stylesheet">

    <?php include_once 'includes/theme_head.php'; ?>
    <link rel="stylesheet" href="css/feedback-shows.css?v=<?= @filemtime(__DIR__ . '/css/feedback-shows.css') ?: '20261004v1' ?>">

    <!-- Breadcrumbs Schema -->
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://arigatodevan.com'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Testimonials', 'item' => $_page_canonical],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <?php include_once 'gtag.php'; ?>
</head>
<body class="page-store theme-nogoda page-feedback-shows">

<?php $nav_active = ''; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

<main class="fbs-container">
    <!-- Decorative Accents from Mockup -->
    <div class="fbs-bg-accents" aria-hidden="true">
        <div class="fbs-dot-grid fbs-dots-tl"></div>
        <div class="fbs-dot-grid fbs-dots-tr"></div>
        <div class="fbs-dot-grid fbs-dots-bl"></div>
        <div class="fbs-dot-grid fbs-dots-br"></div>
        <div class="fbs-circle-bg fbs-circle-1"></div>
        <div class="fbs-circle-bg fbs-circle-2"></div>
    </div>

    <!-- Header matching reference photo -->
    <header class="fbs-hero">
        <span class="fbs-script-eyebrow">Guest Testimonial</span>
        <h1 class="fbs-heading">What They Say?</h1>
        <p class="fbs-sub">Honest thoughts, kind words, and real experiences from our prompt community.</p>
        <div class="fbs-cta-wrap">
            <a href="feedback.php" class="fbs-action-btn">
                <i class="fa-solid fa-pen-fancy"></i> Leave a Review
            </a>
        </div>
    </header>

    <!-- Testimonial Cards Stack -->
    <?php if (empty($feedbacks)): ?>
        <div class="fbs-empty">
            <i class="fa-solid fa-star"></i>
            <h3>No Reviews Featured Yet</h3>
            <p>Be the very first creator to share your experience with our prompt collection!</p>
            <a href="feedback.php" class="fbs-action-btn"><i class="fa-solid fa-heart"></i> Share Feedback</a>
        </div>
    <?php else: ?>
        <section class="fbs-stack" aria-label="Customer Reviews">
            <?php foreach ($feedbacks as $i => $fb):
                // Alternating Mint and White from mockup
                $card_theme = ($i % 2 === 0) ? 'fbs-card--mint' : 'fbs-card--white';
                $uname = trim((string)($fb['username'] ?? 'Community Creator'));
                if ($uname === '') $uname = 'Community Creator';
                $uname_safe = htmlspecialchars($uname);
                $seed = urlencode($uname);
                $av_src = !empty($fb['avatar']) ? $fb['avatar'] : (!empty($fb['profile_image']) ? $fb['profile_image'] : '');

                // Subtitle: Customers / Creator
                $sub_label = 'Community Creator';

                // Rating stars (5 gold stars as shown in mockup)
                $star_count = 5;
            ?>
            <article class="fbs-card <?= $card_theme ?>">
                <div class="fbs-card-head">
                    <div class="fbs-user-info">
                        <?php if ($av_src): ?>
                            <img src="<?= htmlspecialchars($av_src) ?>"
                                 class="fbs-avatar"
                                 alt="<?= $uname_safe ?>"
                                 loading="lazy"
                                 onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=<?= $seed ?>'">
                        <?php else: ?>
                            <div class="fbs-avatar-ph" aria-hidden="true"><?= strtoupper(substr($uname, 0, 1)) ?></div>
                        <?php endif; ?>

                        <div class="fbs-user-text">
                            <h2 class="fbs-user-name"><?= $uname_safe ?></h2>
                            <div class="fbs-user-sub"><?= $sub_label ?></div>
                        </div>
                    </div>

                    <!-- 5 Gold Stars Rating Pill -->
                    <div class="fbs-rating-pill" aria-label="5 out of 5 stars">
                        <?php for ($s = 0; $s < $star_count; $s++): ?>
                            <i class="fa-solid fa-star fbs-star" aria-hidden="true"></i>
                        <?php endfor; ?>
                    </div>
                </div>

                <p class="fbs-quote"><?= nl2br(htmlspecialchars($fb['feedback_text'])) ?></p>
            </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
