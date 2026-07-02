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
        if (!empty($t) && $t !== 'secret') {
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

        <div class="home-logged-head">
            <span class="home-logged-eyebrow">Fresh Drops</span>
            <h1 class="home-logged-title">Unlock <em>the Magic</em></h1>
            <p class="home-logged-sub">Secret, viral &amp; unreleased AI prompts — pick one and go viral</p>
        </div>

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
        <article class="potd-featured"
             data-id="<?= $featuredPrompt['id'] ?>"
             data-slug="<?= htmlspecialchars($featuredPrompt['slug'] ?? '') ?>"
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

    <div class="home-tag-filters">
        <button type="button" class="filter-pill tag-filter-btn active" data-tag="all">All</button>
        <?php foreach ($secret_sub_tags as $t): ?>
        <button type="button" class="filter-pill tag-filter-btn" data-tag="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucfirst($t)) ?></button>
        <?php endforeach; ?>
    </div>

    <div class="prompt-grid" id="card-stack">
        <?php if (count($prompts) === 0): ?>
            <p style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:60px 20px;">No content yet! Check back soon.</p>
        <?php else: foreach ($prompts as $index => $p):
            $db_type = $p['prompt_type'] ?? 'secret';
            if ($db_type === 'insta_viral') { $ptype = 'insta_viral'; }
            elseif ($db_type === 'unreleased') { $ptype = 'unreleased'; }
            elseif ($db_type === 'already_uploaded') { $ptype = 'already_uploaded'; }
            else { $ptype = 'secret_code'; }
            $tags_arr = array_map('trim', explode(',', strtolower($p['tag'])));
            $type_labels = [
                'secret_code' => ['label' => 'SECRET', 'cls' => 'scp'],
                'unreleased' => ['label' => 'UNRELEASED', 'cls' => 'urp'],
                'insta_viral' => ['label' => 'VIRAL', 'cls' => 'ivp'],
                'already_uploaded' => ['label' => 'UPLOADED', 'cls' => 'aup'],
            ];
            $tinfo = $type_labels[$ptype] ?? $type_labels['secret_code'];
            $blur_style = ($ptype === 'unreleased' && !$p['is_unlocked']) ? 'filter:blur(5px);transform:scale(1.05);' : '';
        ?>
        <div class="product-card prompt-card card skeleton"
             data-index="<?= $index ?>"
             data-id="<?= $p['id'] ?>"
             data-slug="<?= htmlspecialchars($p['slug'] ?? '') ?>"
             data-created="<?= htmlspecialchars($p['created_at'] ?? '') ?>"
             data-image="<?= htmlspecialchars($p['image_path']) ?>"
             data-title="<?= htmlspecialchars($p['title']) ?>"
             data-reel="<?= htmlspecialchars($p['reel_link'] ?? '') ?>"
             data-prompt-type="<?= htmlspecialchars($ptype) ?>"
             data-tags="<?= htmlspecialchars(implode(',', $tags_arr)) ?>"
             data-unlocked="<?= $p['is_unlocked'] ? 'true' : 'false' ?>"
             data-saved="<?= !empty($p['is_saved']) ? 'true' : 'false' ?>"
             data-best-works-in="<?= htmlspecialchars($p['best_works_in'] ?? '') ?>"
             data-asset-title="<?= htmlspecialchars($p['asset_title'] ?? '') ?>"
             data-asset-images="<?= htmlspecialchars($p['asset_images'] ?? '[]') ?>"
             <?= $p['is_unlocked'] ? 'data-prompt-text="' . htmlspecialchars($p['prompt_text']) . '"' : '' ?>>
            <div class="card-image-wrap">
                <img src="<?= htmlspecialchars($p['image_path']) ?>" class="skeleton-img" alt="<?= htmlspecialchars($p['title']) ?>" style="<?= $blur_style ?>" <?= $index === 0 ? 'fetchpriority="high" loading="eager"' : ($index < 3 ? 'loading="eager"' : 'loading="lazy"') ?>>
                <span class="card-badge <?= $tinfo['cls'] ?>"><?= $tinfo['label'] ?></span>
                <?php if (!$p['is_unlocked']): ?>
                    <div class="card-lock-icon"><i class="fa-solid fa-lock"></i></div>
                <?php else: ?>
                    <div class="card-lock-icon unlocked"><i class="fa-solid fa-check"></i></div>
                <?php endif; ?>
                <div class="card-overlay">
                    <span class="quick-view-btn">
                        <?= $p['is_unlocked'] ? 'View Prompt' : 'Tap to Unlock' ?>
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>
            </div>
            <div class="card-info">
                <p class="card-title"><?= htmlspecialchars($p['title']) ?></p>
                <div class="card-like-display" data-liked="<?= $p['is_liked'] ? 'true' : 'false' ?>" data-prompt-id="<?= $p['id'] ?>">
                    <i class="fa-solid fa-heart <?= $p['is_liked'] ? 'liked-heart' : '' ?>"></i>
                    <span class="like-count"><?= (int)$p['likes_count'] ?></span>
                    <span class="card-likes-label">likes</span>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="home-promo-banner">
        <div>
            <h2>Secret <em style="font-style:italic;color:var(--accent-warm);">Drops</em> are waiting...</h2>
            <p>Exclusive reels you won't find anywhere else. Show some love to unlock them!</p>
        </div>
        <a href="unreleased.php" class="home-btn-primary"><i class="fa-solid fa-lock-open"></i> Unlock Drops</a>
    </div>
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
