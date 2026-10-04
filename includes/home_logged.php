<?php
/** Logged-in homepage — store design hero + prompt grid */
$uname = htmlspecialchars($_SESSION['username'] ?? 'Friend');
if ($user_gender === 'female' || $user_gender === 'f') {
    $welcome_hi = "Hiiii {$uname}~";
    $welcome_sub = 'Aaj kaun sa reel banayenge? Chalo explore karte hain';
    $welcome_icon = 'fa-heart';
} elseif ($user_gender === 'male' || $user_gender === 'm') {
    $welcome_hi = "Welcome back, {$uname}!";
    $welcome_sub = 'Tera next viral reel ready hai — unlock karo!';
    $welcome_icon = 'fa-fire';
} else {
    $welcome_hi = "Greetings, {$uname}!";
    $welcome_sub = 'Abhi profile pe ja aur gender set kar!';
    $welcome_icon = 'fa-robot';
}

$secret_sub_tags = [];
foreach ($prompts as $sp) {
    foreach (array_map('trim', explode(',', strtolower($sp['tag']))) as $t) {
        if (!empty($t) && $t !== 'secret' && $t !== 'direct') {
            $secret_sub_tags[] = $t;
        }
    }
}
$secret_sub_tags = array_unique($secret_sub_tags);
sort($secret_sub_tags);
?>
<section class="page-hero page-hero--logged">
    <div class="home-logged-shell">
        <div class="home-welcome">
            <span class="home-welcome-icon<?= $welcome_icon === 'fa-fire' ? ' is-fire' : '' ?>"><i class="fa-solid <?= $welcome_icon ?>"></i></span>
            <div>
                <div class="pw-hi"><?= $welcome_hi ?></div>
                <div class="pw-sub"><?= $welcome_sub ?></div>
            </div>
        </div>

        <?php if ($new_drop_count > 0): ?>
        <a href="gallery.php" class="home-drop-banner">
            <i class="fa-solid fa-fire"></i>
            <?= $new_drop_count ?> NEW <?= $new_drop_count === 1 ? 'PROMPT' : 'PROMPTS' ?> DROPPED!
            <i class="fa-solid fa-arrow-right"></i>
        </a>
        <?php endif; ?>

        <?php include __DIR__ . '/home_best_prompts_slider.php'; ?>

        <div class="home-logged-actions">
            <a href="gallery.php" class="home-gallery-cta">
                <span class="home-gallery-cta-icon" aria-hidden="true"><i class="fa-solid fa-images"></i></span>
                <span class="home-gallery-cta-body">
                    <span class="home-gallery-cta-title">Browse Prompt Gallery</span>
                    <span class="home-gallery-cta-desc">Complete collection — filter by vibe</span>
                </span>
                <span class="home-gallery-cta-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
            </a>

            <a href="surprise_me.php" class="home-surprise-btn">
                <span class="home-surprise-dice-pair" aria-hidden="true">
                    <i class="fa-solid fa-dice-three home-surprise-die home-surprise-die--red"></i>
                    <i class="fa-solid fa-dice-five home-surprise-die home-surprise-die--pink"></i>
                </span>
                Surprise Me
            </a>
        </div>
    </div>
</section>

<main class="page-main">
    <?php if ($featuredPrompt):
        $fdb_type = $featuredPrompt['prompt_type'] ?? 'secret';
        if ($fdb_type === 'insta_viral') { $fptype = 'insta_viral'; }
        elseif ($fdb_type === 'unreleased') { $fptype = 'unreleased'; }
        elseif ($fdb_type === 'already_uploaded') { $fptype = 'already_uploaded'; }
        else { $fptype = 'secret_code'; }
    ?>
    <div class="potd-section">
        <div class="potd-header">
            <span class="potd-eyebrow"><i class="fa-solid fa-star"></i> Today's Pick</span>
            <h2 class="potd-heading">Prompt of the <em>Day</em></h2>
        </div>
        <article class="potd-featured"
             data-id="<?= $featuredPrompt['id'] ?>"
             data-slug="<?= htmlspecialchars($featuredPrompt['slug'] ?? '') ?>"
             <?= !empty($featuredIsCustom) ? 'data-custom-potd="1"' : '' ?>
             data-image="<?= htmlspecialchars($featuredPrompt['image_path']) ?>"
             data-title="<?= htmlspecialchars($featuredPrompt['title']) ?>"
             data-reel="<?= htmlspecialchars($featuredPrompt['reel_link'] ?? '') ?>"
             data-prompt-type="<?= htmlspecialchars($fptype) ?>"
             data-tags="<?= htmlspecialchars(strtolower($featuredPrompt['tag'] ?? '')) ?>"
             data-unlocked="<?= $featuredPrompt['is_unlocked'] ? 'true' : 'false' ?>"
             data-saved="<?= !empty($featuredPrompt['is_saved']) ? 'true' : 'false' ?>"
             data-best-works-in="<?= htmlspecialchars($featuredPrompt['best_works_in'] ?? '') ?>"
             data-asset-title="<?= htmlspecialchars($featuredPrompt['asset_title'] ?? '') ?>"
             data-asset-images="<?= htmlspecialchars($featuredPrompt['asset_images'] ?? '[]') ?>"
             <?= $featuredPrompt['is_unlocked'] ? 'data-prompt-text="' . htmlspecialchars($featuredPrompt['prompt_text']) . '"' : '' ?>>
            <div class="potd-featured-img">
                <img loading="lazy" src="<?= htmlspecialchars($featuredPrompt['image_path']) ?>" alt="<?= htmlspecialchars($featuredPrompt['title']) ?>">
                <?php if (!$featuredPrompt['is_unlocked']): ?>
                <span class="potd-status-tag is-locked"><i class="fa-solid fa-lock"></i></span>
                <?php else: ?>
                <span class="potd-status-tag is-open"><i class="fa-solid fa-check"></i></span>
                <?php endif; ?>
            </div>
            <div class="potd-featured-body">
                <div class="potd-title-row">
                    <h3><?= htmlspecialchars($featuredPrompt['title']) ?></h3>
                    <span class="potd-star-badge" title="Prompt of the Day"><i class="fa-solid fa-star"></i></span>
                </div>
                <p class="potd-sub">
                    <?= $featuredPrompt['is_unlocked']
                        ? 'Prompt of the Day — ready to copy &amp; create.'
                        : 'Prompt of the Day — unlock and go viral today.' ?>
                </p>
                <div class="potd-foot">
                    <span class="potd-stat">
                        <i class="fa-solid fa-heart"></i>
                        <?= (int)$featuredPrompt['likes_count'] ?>
                    </span>
                    <span class="potd-cta-link">
                        <?= $featuredPrompt['is_unlocked'] ? 'View' : 'Unlock' ?>
                        <i class="fa-solid fa-plus"></i>
                    </span>
                </div>
            </div>
        </article>
    </div>
    <?php endif; ?>

    <style>
    .page-home-logged .home-tag-filters {
        display: flex !important;
        gap: 10px !important;
        padding: 10px 4px 12px !important;
        margin-bottom: 24px !important;
        align-items: center !important;
        overflow-x: auto !important;
        overflow-y: visible !important;
        scrollbar-width: none !important;
        -webkit-overflow-scrolling: touch !important;
    }
    .page-home-logged .home-tag-filters::-webkit-scrollbar {
        display: none !important;
    }
    .page-home-logged .home-tag-filters .filter-pill {
        flex-shrink: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: 38px !important;
        padding: 0 20px !important;
        border-radius: 999px !important;
        font-size: 0.84rem !important;
        font-weight: 500 !important;
        line-height: 1 !important;
        border: 1.5px solid rgba(200, 217, 230, 0.9) !important;
        background: #ffffff !important;
        color: var(--pal-teal, #567C8D) !important;
        transform: none !important;
        box-sizing: border-box !important;
        cursor: pointer !important;
        margin: 0 !important;
        outline: none !important;
        box-shadow: none !important;
        transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease !important;
    }
    .page-home-logged .home-tag-filters .filter-pill:hover {
        border-color: var(--pal-teal, #567C8D) !important;
        color: var(--pal-navy, #2F4156) !important;
        transform: none !important;
    }
    .page-home-logged .home-tag-filters .filter-pill.active {
        background: var(--nogoda-gradient, linear-gradient(135deg, #F5709D 0%, #11FFC9 55%, #2FA6C6 100%)) !important;
        border: 1.5px solid transparent !important;
        color: var(--pal-navy, #2F4156) !important;
        font-weight: 700 !important;
        transform: none !important;
        border-radius: 999px !important;
        box-shadow: 0 4px 14px rgba(17, 255, 201, 0.25) !important;
    }
    @media (min-width: 769px) {
        .page-home-logged .home-tag-filters {
            justify-content: center !important;
            flex-wrap: wrap !important;
            max-width: 900px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            overflow: visible !important;
        }
    }
    </style>
    <div class="home-tag-filters">
        <button type="button" class="filter-pill tag-filter-btn active" data-tag="all">All</button>
        <?php foreach ($secret_sub_tags as $t): ?>
        <button type="button" class="filter-pill tag-filter-btn" data-tag="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucfirst($t)) ?></button>
        <?php endforeach; ?>
    </div>

    <div class="prompt-grid" id="card-stack">
        <?php if (count($prompts) === 0): ?>
            <p style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:60px 20px;">No content yet! Check back soon.</p>
        <?php else:
            require_once __DIR__ . '/prompt_cards.php';
            foreach ($prompts as $index => $p):
                render_prompt_card($p, $index);
            endforeach;
        endif; ?>
    </div>

    <?php include __DIR__ . '/home_seo_section.php'; ?>
</main>

<script>
document.querySelectorAll('.tag-filter-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tag-filter-btn').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var tag = btn.dataset.tag;
        document.querySelectorAll('#card-stack .prompt-card').forEach(function(card) {
            var cardTags = (card.dataset.tags || '').split(',').map(function(t) { return t.trim(); });
            card.style.display = (tag === 'all' || cardTags.includes(tag)) ? '' : 'none';
        });
    });
});
</script>
