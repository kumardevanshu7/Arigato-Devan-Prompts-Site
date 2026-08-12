<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';
require_once 'includes/heroines_orbit.php';

$heroines = $pdo->query(
    "SELECT * FROM heroines WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$viewport_configs = [
    'laptop' => ['class' => 'heroines-orbit--laptop'],
    'tablet' => ['class' => 'heroines-orbit--tablet'],
    'mobile' => ['class' => 'heroines-orbit--mobile'],
];

$viewport_slots = [];
$viewport_maps  = [];
foreach ($viewport_configs as $vp => $_meta) {
    $viewport_slots[$vp] = heroines_resolve_viewport_slots($pdo, $vp);
    $viewport_maps[$vp]   = heroines_resolve_orbit_map($pdo, $heroines, count($viewport_slots[$vp]));
}

$blob_colors = ['#FFE4EC', '#F5D0E0', '#FFD6E8', '#F0E6F5', '#FFF0F5', '#FAD4E4'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FFF8FA">
    <title>My Heroines — Arigato Devan Prompts</title>
    <meta name="description" content="Meet the heroines featured in Arigato Devan prompts and creative content — AI girls and real profiles.">
    <?php include_once 'includes/theme_head.php'; ?>
    <link rel="stylesheet" href="css/heroines-page.css?v=20260792">
    <link rel="stylesheet" href="css/info-pages.css?v=20260719">
    <?php include_once 'gtag.php'; ?>
</head>
<body class="page-store theme-nogoda page-heroines">

<?php
$nav_brand_words = ['devan', 'prompt', 'myra', 'heroines'];
$nav_active = 'heroines';
include 'includes/site_nav.php';
?>

<main>
    <div class="heroines-landing-wrap">
        <section class="heroines-landing" aria-label="Heroines landing">
            <?php foreach ($viewport_configs as $vp => $meta):
                $slots = $viewport_slots[$vp];
                $map   = $viewport_maps[$vp];
            ?>
            <div class="heroines-orbit <?= htmlspecialchars($meta['class']) ?>" aria-hidden="true">
                <?php foreach ($slots as $i => $slot):
                    $h = heroines_heroine_for_layout_slot($heroines, $slot, $i, $map);
                    $has_img = $h && !empty($h['circle_image']);
                    if ($vp === 'mobile' && !$has_img && empty($slot['heroine_id'])) {
                        continue;
                    }
                    $zone = isset($slot['zone'])
                        ? 'orbit-zone-' . $slot['zone']
                        : heroines_orbit_zone($slot['left'], $slot['top']);
                ?>
                <div class="heroines-orbit-item <?= $zone ?><?= $has_img ? '' : ' is-placeholder' ?>"
                     style="<?= heroines_orbit_item_style($slot) ?>">
                    <?php if ($has_img): ?>
                        <img src="<?= htmlspecialchars($h['circle_image']) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="orbit-ph-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <div class="heroines-landing-inner">
                <p class="heroines-eyebrow">Featured Profiles</p>
                <h1><em>Heroines</em> Details</h1>
                <p class="heroines-landing-sub">The beautiful faces behind our prompts &amp; creative content</p>

                <button type="button" class="heroines-cta-btn" id="heroesRevealBtn" aria-expanded="false" aria-controls="heroinesCardsPanel">
                    <i class="fa-solid fa-heart"></i>
                    My Heroines
                    <i class="fa-solid fa-heart"></i>
                </button>
                <span class="heroines-cta-hint">Tap to meet each profile</span>
            </div>
        </section>
    </div>

    <div id="heroinesCardsPanel" class="heroines-cards-panel" hidden aria-hidden="true">
        <div class="heroines-main">
            <div class="heroines-section-head">
                <p class="hero-label">Who We Feature</p>
                <h2><i class="fa-solid fa-heart"></i> Meet Our Heroines <i class="fa-solid fa-heart"></i></h2>
                <p>The amazing profiles behind the prompts you love — AI creations and real personalities.</p>
            </div>

            <?php if (empty($heroines)): ?>
                <div class="heroines-empty">
                    <div><i class="fa-solid fa-heart"></i></div>
                    <p>Heroine profiles coming soon. Stay tuned!</p>
                </div>
            <?php else: ?>
                <div class="heroines-grid">
                    <?php foreach ($heroines as $i => $h):
                        $is_ai     = ($h['heroine_type'] ?? 'ai') === 'ai';
                        $blob      = $blob_colors[$i % count($blob_colors)];
                        $insta     = trim($h['instagram_username'] ?? '');
                        $insta_url = trim($h['instagram_url'] ?? '');
                        if ($insta_url === '' && $insta !== '') {
                            $handle    = ltrim($insta, '@');
                            $insta_url = 'https://www.instagram.com/' . rawurlencode($handle) . '/';
                        }
                    ?>
                    <article class="heroine-card">
                        <div class="heroine-card-photo-wrap">
                            <div class="heroine-card-blob" style="background:<?= htmlspecialchars($blob) ?>"></div>
                            <img src="<?= htmlspecialchars($h['card_image']) ?>"
                                 alt="<?= htmlspecialchars($h['name']) ?>"
                                 class="heroine-card-photo"
                                 loading="lazy">
                        </div>
                        <h3 class="heroine-card-name">
                            <?= htmlspecialchars($h['name']) ?>
                            <i class="fa-solid fa-venus" title="Female" aria-hidden="true"></i>
                        </h3>
                        <span class="heroine-card-type heroine-card-type--<?= $is_ai ? 'ai' : 'real' ?>">
                            <i class="fa-solid <?= $is_ai ? 'fa-wand-magic-sparkles' : 'fa-user' ?>"></i>
                            <?= $is_ai ? 'AI Girl' : 'Real Person' ?>
                        </span>
                        <div class="heroine-card-meta">
                            <div class="heroine-card-used">
                                Used <span><?= (int) $h['times_used'] ?></span> times in content
                            </div>
                            <?php if (!empty($h['country'])): ?>
                                <div class="heroine-card-country">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <?= htmlspecialchars($h['country']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($insta !== '' || $insta_url !== ''): ?>
                                <a href="<?= htmlspecialchars($insta_url ?: '#') ?>"
                                   class="heroine-card-insta"
                                   <?= $insta_url ? 'target="_blank" rel="noopener"' : '' ?>>
                                    <i class="fa-brands fa-instagram"></i>
                                    <?= $insta !== '' ? htmlspecialchars($insta) : 'Instagram' ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include_once 'footer.php'; ?>
<script src="js/heroines-page.js?v=20260766" defer></script>
</body>
</html>
