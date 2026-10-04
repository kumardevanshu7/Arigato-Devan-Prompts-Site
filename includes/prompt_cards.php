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
    } elseif ($db_type === 'solo') {
        $ptype = 'solo';
    } else {
        $ptype = 'secret_code';
    }
    $labels = [
        'secret_code'      => ['label' => 'SECRET',    'cls' => 'scp'],
        'unreleased'       => ['label' => 'UNRELEASED','cls' => 'urp'],
        'insta_viral'      => ['label' => 'VIRAL',     'cls' => 'ivp'],
        'already_uploaded' => ['label' => 'UPLOADED',  'cls' => 'aup'],
        'direct'           => ['label' => 'DIRECT',    'cls' => 'dir'],
        'solo'             => ['label' => 'SOLO',      'cls' => 'sol'],
    ];
    $tinfo = $labels[$ptype] ?? $labels['secret_code'];
    return ['ptype' => $ptype, 'label' => $tinfo['label'], 'cls' => $tinfo['cls']];
}

/**
 * Curated 50 aesthetic light/pastel color palette.
 * High luminance (>85%), soft and easy on eyes, high contrast with dark navy typography.
 */
function get_prompt_card_palette(): array {
    return [
        '#d4f938', // 1. Electric Lime (Handpicked hero reference)
        '#d1fae5', // 2. Soft Mint
        '#fed7aa', // 3. Pastel Peach
        '#fce7f3', // 4. Blush Pink
        '#e0f2fe', // 5. Baby Sky Blue
        '#ede9fe', // 6. Pale Lavender
        '#fef08a', // 7. Butter Yellow
        '#fef3c7', // 8. Warm Cream
        '#dcfce7', // 9. Fresh Pistachio
        '#ffe4e6', // 10. Soft Coral
        '#e0f7fa', // 11. Ice Blue
        '#e8f5e9', // 12. Matcha Foam
        '#f3e8ff', // 13. Lilac Mist
        '#ffedd5', // 14. Apricot Cream
        '#eafaf1', // 15. Honeydew Green
        '#e0e7ff', // 16. Powder Periwinkle
        '#f7fee7', // 17. Pale Lime Sorbet
        '#faeed9', // 18. Soft Champagne
        '#ccfbf1', // 19. Mint Sherbet
        '#fbe7eb', // 20. Rose Quartz
        '#e2e8e0', // 21. Light Sage
        '#fffbeb', // 22. Vanilla Custard
        '#e0f4f7', // 23. Pale Aqua
        '#fae8ff', // 24. Muted Orchid
        '#e6f0fa', // 25. Cloud Blue
        '#fef2e2', // 26. Sunlit Almond
        '#ecfccb', // 27. Fresh Celery
        '#f5e6eb', // 28. Soft Mauve
        '#effcd5', // 29. Pear Green
        '#ffe8db', // 30. Sweet Melon
        '#e9e5ff', // 31. Pale Iris
        '#fdf2f8', // 32. Cotton Candy
        '#fef9c3', // 33. Lemon Cream
        '#e6faf5', // 34. Seafoam Glow
        '#ffecd6', // 35. Pale Tangerine
        '#ece8f5', // 36. Dusty Lavender
        '#f6f3ea', // 37. Oat Milk
        '#d1f4f0', // 38. Soft Turquoise
        '#fde8ea', // 39. Strawberry Milk
        '#e8edf2', // 40. Slate Frost
        '#fcf1db', // 41. Golden Sand
        '#e4f9cf', // 42. Spring Sprout
        '#f1ebfc', // 43. Pale Violet
        '#f5f0e8', // 44. Warm Linen
        '#ddf4ff', // 45. Nordic Blue
        '#ffe9e3', // 46. Blushed Salmon
        '#e3fced', // 47. Spearmint
        '#f5f3ff', // 48. Velvet Haze
        '#e8f8f5', // 49. Frosty Jade
        '#fff1f2', // 50. Blushing Rose
    ];
}

/**
 * Returns a randomized color from the 50-color palette.
 * Colors are shuffled once per request and cycle sequentially
 * so adjacent cards have different colors, and page refresh randomizes anew.
 */
function get_prompt_card_color(?int $index = null): string {
    static $shuffled = null;
    static $seq = 0;
    if ($shuffled === null) {
        $shuffled = get_prompt_card_palette();
        shuffle($shuffled);
    }
    $idx = ($index !== null) ? $index : $seq++;
    return $shuffled[$idx % count($shuffled)];
}

function render_prompt_card(array $p, int $index = 0, array $opts = []): void {
    $clickable = $opts['clickable'] ?? true;
    $extra_class = !empty($opts['extra_class']) ? ' ' . trim($opts['extra_class']) : '';
    $card_class = ($clickable ? 'product-card prompt-card card' : 'product-card prompt-card') . $extra_class;
    $db_type = $p['prompt_type'] ?? 'secret';
    $type    = prompt_resolve_type($db_type);
    $ptype   = $type['ptype'];
    $tags_arr = array_map('trim', explode(',', strtolower($p['tag'] ?? '')));
    $blur_style = ($ptype === 'unreleased' && empty($p['is_unlocked'])) ? 'filter:blur(5px);transform:scale(1.05);' : '';
    $is_unlocked = !empty($p['is_unlocked']);
    $is_liked    = !empty($p['is_liked']);
    $card_bg     = $opts['bg_color'] ?? get_prompt_card_color();
    ?>
    <div class="<?= $card_class ?> skeleton"
         style="--card-bg: <?= htmlspecialchars($card_bg) ?>; background-color: <?= htmlspecialchars($card_bg) ?> !important;"
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
         <?= $is_unlocked && !empty($p['prompt_text']) ? 'data-prompt-text="' . htmlspecialchars($p['prompt_text']) . '"' : '' ?>>
        <div class="card-image-wrap">
            <img src="<?= htmlspecialchars($p['image_path']) ?>"
                 class="skeleton-img"
                 alt="<?= htmlspecialchars($p['title']) ?>"
                 style="<?= $blur_style ?>"
                 <?= $index === 0 ? 'fetchpriority="high" loading="eager"' : ($index < 3 ? 'loading="eager"' : 'loading="lazy"') ?>>
            <span class="card-badge <?= $type['cls'] ?>"><?= $type['label'] ?></span>
            <?php if (!$is_unlocked): ?>
                <div class="card-lock-icon" title="Locked"><i class="fa-solid fa-lock"></i></div>
            <?php else: ?>
                <div class="card-lock-icon unlocked" title="Unlocked"><i class="fa-solid fa-check"></i></div>
            <?php endif; ?>
        </div>
        <div class="card-info">
            <p class="card-title"><?= htmlspecialchars($p['title']) ?></p>
            <div class="card-footer-row">
                <div class="card-stats-col">
                    <div class="card-stat-item card-like-display" data-liked="<?= $is_liked ? 'true' : 'false' ?>" data-prompt-id="<?= (int)$p['id'] ?>" title="Likes">
                        <i class="fa-solid fa-heart <?= $is_liked ? 'liked-heart' : '' ?>"></i>
                        <span class="like-count"><?= (int)($p['likes_count'] ?? 0) ?></span>
                    </div>
                    <div class="card-stat-item card-views-display" title="<?= (int)($p['view_count'] ?? 0) ?> views">
                        <i class="fa-regular fa-eye"></i>
                        <span class="view-count"><?= (int)($p['view_count'] ?? 0) ?></span>
                    </div>
                </div>
                <div class="card-prompt-btn-wrap">
                    <span class="card-prompt-btn">Prompt <svg class="prompt-btn-arrow" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg></span>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function render_prompt_grid(array $prompts, array $opts = []): void {
    $grid_id   = $opts['grid_id'] ?? 'card-stack';
    ?>
    <div class="prompt-grid" id="<?= htmlspecialchars($grid_id) ?>">
    <?php if (count($prompts) === 0): ?>
        <p class="grid-empty-msg">No prompts here yet. Check back soon!</p>
    <?php else: foreach ($prompts as $index => $p):
        render_prompt_card($p, $index, $opts);
    endforeach; endif; ?>
    </div>
    <?php
}

function render_trending_row(array $prompts, array $opts = []): void {
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
            render_prompt_card($p, $rank, ['clickable' => true, 'extra_class' => 'trending-card']);
        endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php
}

function prompt_page_url_js(): string {
    return <<<'JS'
function promptPageUrl(card) {
    if (card.dataset.slug) {
        if (card.dataset.promptType === 'solo') {
            return 'prompts/solo/' + encodeURIComponent(card.dataset.slug);
        }
        return 'prompt.php?slug=' + encodeURIComponent(card.dataset.slug);
    }
    return 'prompt.php?id=' + card.dataset.id;
}
function bindPromptCardClicks(selector, opts) {
    opts = opts || {};
    document.querySelectorAll(selector).forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.card-like-display')) return;
            if (e.target.closest('.card-prompt-btn')) {
                if (opts.modalOnly) return;
                var url = promptPageUrl(card);
                document.body.style.transition = 'opacity 0.15s ease';
                document.body.style.opacity = '0';
                setTimeout(function() { window.location.href = url; }, 150);
                return;
            }
            if (window.openPromptImagePreview) {
                e.preventDefault();
                window.openPromptImagePreview(card);
            }
        });
    });
}
JS;
}
