<?php
/** 16:9 sliding hero banner — images from /banner/ folder */
$gal_banner_slides = $gal_banner_slides ?? gallery_banner_slides();
if (empty($gal_banner_slides)) {
    return;
}
?>
<section class="gal-hero-banner" aria-label="Featured banners">
    <div class="gal-banner-track-wrap" id="gal-banner-wrap">
        <div class="gal-banner-track" id="gal-banner-track">
            <?php foreach ($gal_banner_slides as $i => $slide): ?>
            <div class="gal-banner-slide">
                <?php if (!empty($slide['image'])): ?>
                    <img src="<?= htmlspecialchars($slide['image']) ?>" alt="<?= htmlspecialchars($slide['title'] ?? 'Banner') ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                <?php else: ?>
                    <div class="gal-banner-ph" style="background:<?= htmlspecialchars($slide['gradient'] ?? 'linear-gradient(135deg,#2F4156,#567C8D)') ?>;"></div>
                <?php endif; ?>
                <div class="gal-banner-overlay">
                    <div class="gal-banner-content">
                        <div class="gal-banner-meta">Featured</div>
                        <h2 class="gal-banner-title"><?= htmlspecialchars($slide['title'] ?? '') ?></h2>
                        <p class="gal-banner-sub"><?= htmlspecialchars($slide['subtitle'] ?? '') ?></p>
                        <a href="<?= htmlspecialchars($slide['href'] ?? '#card-stack') ?>" class="gal-banner-cta">
                            <?= htmlspecialchars($slide['cta'] ?? 'Explore') ?> <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($gal_banner_slides) > 1): ?>
        <button type="button" class="gal-banner-nav gal-banner-prev" id="gal-banner-prev" aria-label="Previous slide"><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="gal-banner-nav gal-banner-next" id="gal-banner-next" aria-label="Next slide"><i class="fa-solid fa-chevron-right"></i></button>
        <div class="gal-banner-dots" id="gal-banner-dots">
            <?php foreach ($gal_banner_slides as $i => $slide): ?>
            <button type="button" class="gal-banner-dot<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
