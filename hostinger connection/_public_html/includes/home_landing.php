<?php
/** Logged-out homepage landing — store design */
?>
<!-- 1:1 VivaChat Split Hero Flow Showcase (Full Width) -->
<?php include __DIR__ . '/home_hero_flow.php'; ?>

<!-- SEO Explainer Section (Contained) -->
<section class="home-landing-seo-wrap">
    <?php include __DIR__ . '/home_seo_section.php'; ?>
</section>

<div class="marquee-strip">
    <div class="marquee-track">
        <?php
        $ticker_items = ['Couple Prompts are here', 'Ultra-realistic AI prompts', 'Unlock viral content ideas', 'Create stunning couple scenes', 'Your next viral reel starts here', 'Premium prompts. Real emotions.', 'More drops every week'];
        $ticker_html = '';
        foreach ($ticker_items as $item) {
            $ticker_html .= '<span class="marquee-item">' . htmlspecialchars($item) . ' <span class="marquee-dot">✦</span></span>';
        }
        echo $ticker_html . $ticker_html;
        ?>
    </div>
</div>
