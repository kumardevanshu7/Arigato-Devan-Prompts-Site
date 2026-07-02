<?php
/**
 * Shared prompt card grid renderer — Nogoda theme.
 * Usage: render_prompt_grid($prompts, ['grid_id' => 'card-stack']);
 */
function prompt_resolve_type(string $db_type): array {
    if ($db_type === 'insta_viral') {
        $ptype = 'insta_viral';
    } elseif ($db_type === 'unreleased') {
        $ptype = 'unreleased';
    } elseif ($db_type === 'already_uploaded') {
        $ptype = 'already_uploaded';
    } elseif ($db_type === 'direct') {
        $ptype = 'direct';
    } else {
        $ptype = 'secret_code';
    }
    $labels = [
        'secret_code'      => ['label' => 'SECRET',    'cls' => 'scp'],
        'unreleased'       => ['label' => 'UNRELEASED','cls' => 'urp'],
        'insta_viral'      => ['label' => 'VIRAL',     'cls' => 'ivp'],
        'already_uploaded' => ['label' => 'UPLOADED',  'cls' => 'aup'],
        'direct'           => ['label' => 'DIRECT',    'cls' => 'dir'],
    ];
    $tinfo = $labels[$ptype] ?? $labels['secret_code'];
    return ['ptype' => $ptype, 'label' => $tinfo['label'], 'cls' => $tinfo['cls']];
}

function render_prompt_grid(array $prompts, array $opts = []): void {
    $grid_id   = $opts['grid_id'] ?? 'card-stack';
    $clickable = $opts['clickable'] ?? true;
    $card_class = $clickable ? 'product-card prompt-card card' : 'product-card prompt-card';
    ?>
    <div class="prompt-grid" id="<?= htmlspecialchars($grid_id) ?>">
    <?php if (count($prompts) === 0): ?>
        <p class="grid-empty-msg">No prompts here yet. Check back soon!</p>
    <?php else: foreach ($prompts as $index => $p):
        $db_type = $p['prompt_type'] ?? 'secret';
        $type    = prompt_resolve_type($db_type);
        $ptype   = $type['ptype'];
        $tags_arr = array_map('trim', explode(',', strtolower($p['tag'] ?? '')));
        $blur_style = ($ptype === 'unreleased' && empty($p['is_unlocked'])) ? 'filter:blur(5px);transform:scale(1.05);' : '';
        $is_unlocked = !empty($p['is_unlocked']);
        $is_liked    = !empty($p['is_liked']);
    ?>
        <div class="<?= $card_class ?> skeleton"
             data-index="<?= (int)$index ?>"
             data-id="<?= (int)$p['id'] ?>"
             data-slug="<?= htmlspecialchars($p['slug'] ?? '') ?>"
             data-created="<?= htmlspecialchars($p['created_at'] ?? '') ?>"
             data-image="<?= htmlspecialchars($p['image_path']) ?>"
             data-title="<?= htmlspecialchars($p['title']) ?>"
             data-reel="<?= htmlspecialchars($p['reel_link'] ?? '') ?>"
             data-prompt-type="<?= htmlspecialchars($ptype) ?>"
             data-tags="<?= htmlspecialchars(implode(',', $tags_arr)) ?>"
             data-unlocked="<?= $is_unlocked ? 'true' : 'false' ?>"
             data-saved="<?= !empty($p['is_saved']) ? 'true' : 'false' ?>"
             data-best-works-in="<?= htmlspecialchars($p['best_works_in'] ?? '') ?>"
             data-asset-title="<?= htmlspecialchars($p['asset_title'] ?? '') ?>"
             data-asset-images="<?= htmlspecialchars($p['asset_images'] ?? '[]') ?>"
             <?= $is_unlocked ? 'data-prompt-text="' . htmlspecialchars($p['prompt_text']) . '"' : '' ?>>
            <div class="card-image-wrap">
                <img src="<?= htmlspecialchars($p['image_path']) ?>"
                     class="skeleton-img"
                     alt="<?= htmlspecialchars($p['title']) ?>"
                     style="<?= $blur_style ?>"
                     <?= $index === 0 ? 'fetchpriority="high" loading="eager"' : ($index < 3 ? 'loading="eager"' : 'loading="lazy"') ?>>
                <span class="card-badge <?= $type['cls'] ?>"><?= $type['label'] ?></span>
                <?php if (!$is_unlocked): ?>
                    <div class="card-lock-icon"><i class="fa-solid fa-lock"></i></div>
                <?php else: ?>
                    <div class="card-lock-icon unlocked"><i class="fa-solid fa-check"></i></div>
                <?php endif; ?>
                <div class="card-overlay">
                    <span class="quick-view-btn"><?= $is_unlocked ? 'View Prompt' : 'Tap to Unlock' ?> &rarr;</span>
                </div>
            </div>
            <div class="card-info">
                <p class="card-title"><?= htmlspecialchars($p['title']) ?></p>
                <div class="card-like-display" data-liked="<?= $is_liked ? 'true' : 'false' ?>" data-prompt-id="<?= (int)$p['id'] ?>">
                    <i class="fa-solid fa-heart <?= $is_liked ? 'liked-heart' : '' ?>"></i>
                    <span class="like-count"><?= (int)($p['likes_count'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
    </div>
    <?php
}

function render_trending_row(array $prompts, array $opts = []): void {
    $type_short = [
        'scp' => 'Secret',
        'urp' => 'Unreleased',
        'ivp' => 'Viral',
        'aup' => 'Uploaded',
        'dir' => 'Direct',
    ];
    ?>
    <section class="gal-trending-section" id="gal-trending">
        <div class="gal-section-head">
            <div class="gal-section-titles">
                <h2><span class="gal-trend-flame" aria-hidden="true"><i class="fa-solid fa-fire-flame-curved"></i></span> Trending Now</h2>
                <p class="gal-section-sub">Hand-picked from admin trending settings</p>
            </div>
        </div>
        <?php if (count($prompts) === 0): ?>
        <div class="gal-trending-empty">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
            <p>No trending picks yet.</p>
            <span>Admin → Trending Settings → toggle ON karo</span>
        </div>
        <?php else: ?>
        <div class="gal-trending-row-wrap">
            <button type="button" class="gal-trend-edge gal-trend-prev" aria-label="Scroll left"><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" class="gal-trend-edge gal-trend-next" aria-label="Scroll right"><i class="fa-solid fa-chevron-right"></i></button>
            <div class="gal-trending-scroll" id="gal-trending-scroll">
        <?php foreach ($prompts as $rank => $p):
            $db_type = $p['prompt_type'] ?? 'secret';
            $type    = prompt_resolve_type($db_type);
            $is_unlocked = !empty($p['is_unlocked']);
            $year = !empty($p['created_at']) ? date('Y', strtotime($p['created_at'])) : '';
            $blur = ($type['ptype'] === 'unreleased' && !$is_unlocked) ? 'filter:blur(4px);transform:scale(1.05);' : '';
            $tags_arr = array_map('trim', explode(',', strtolower($p['tag'] ?? '')));
            $short_label = $type_short[$type['cls']] ?? 'Prompt';
        ?>
            <article class="trending-card"
                 data-id="<?= (int)$p['id'] ?>"
                 data-slug="<?= htmlspecialchars($p['slug'] ?? '') ?>"
                 data-image="<?= htmlspecialchars($p['image_path']) ?>"
                 data-title="<?= htmlspecialchars($p['title']) ?>"
                 data-reel="<?= htmlspecialchars($p['reel_link'] ?? '') ?>"
                 data-prompt-type="<?= htmlspecialchars($type['ptype']) ?>"
                 data-tags="<?= htmlspecialchars(implode(',', $tags_arr)) ?>"
                 data-unlocked="<?= $is_unlocked ? 'true' : 'false' ?>"
                 data-saved="<?= !empty($p['is_saved']) ? 'true' : 'false' ?>"
                 <?= $is_unlocked ? 'data-prompt-text="' . htmlspecialchars($p['prompt_text']) . '"' : '' ?>>
                <div class="trending-card-poster">
                    <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy" style="<?= $blur ?>">
                    <div class="trending-card-shade"></div>
                    <span class="trend-type trend-type-<?= $type['cls'] ?>"><?= htmlspecialchars($short_label) ?></span>
                    <?php if (!$is_unlocked): ?>
                        <span class="trending-lock" title="Locked"><i class="fa-solid fa-lock"></i></span>
                    <?php endif; ?>
                    <div class="trending-card-hover">
                        <span class="trending-play"><i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                </div>
                <div class="trending-card-info">
                    <p class="trending-card-title"><?= htmlspecialchars($p['title']) ?></p>
                    <div class="trending-card-meta">
                        <span class="trend-year"><?= $year ?></span>
                        <span class="likes"><i class="fa-solid fa-heart"></i> <?= (int)($p['likes_count'] ?? 0) ?></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php
}

function prompt_page_url_js(): string {
    return <<<'JS'
function promptPageUrl(card) {
    var isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (!isLocal && card.dataset.slug) return '/prompts/' + card.dataset.slug;
    return 'prompt.php?id=' + card.dataset.id;
}
function bindPromptCardClicks(selector, opts) {
    opts = opts || {};
    document.querySelectorAll(selector).forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.card-like-display')) return;
            if (opts.modalOnly) return;
            var url = promptPageUrl(card);
            document.body.style.transition = 'opacity 0.15s ease';
            document.body.style.opacity = '0';
            setTimeout(function() { window.location.href = url; }, 150);
        });
    });
}
JS;
}
