<?php
/**
 * Category page content — hero + tag filters + prompt grid.
 */
require_once __DIR__ . '/prompt_cards.php';

$cat_exclude_tag = $cat_exclude_tag ?? '';
$cat_steps_page  = $cat_steps_page ?? '';
$cat_empty_icon  = $cat_empty_icon ?? 'fa-folder-open';
$cat_empty_title = $cat_empty_title ?? 'Nothing here yet...';
$cat_empty_text  = $cat_empty_text ?? 'Prompts will appear here when the admin adds them!';
$cat_nav_active  = $cat_nav_active ?? '';
$cat_instruction = $cat_instruction ?? null;
$cat_hide_hero   = !empty($cat_hide_hero);

if (!function_exists('render_cat_instruction_banner')) {
    function render_cat_instruction_banner(array $instruction): void {
        $compact = !isset($instruction['compact']) || $instruction['compact'] !== false;
        $icon    = htmlspecialchars($instruction['icon'] ?? 'fa-heart');
        $title   = $instruction['title'] ?? '';
        ?>
        <div class="cat-instruction-wrap cat-instruction-wrap--above-grid">
            <div class="cat-instruction-banner<?= $compact ? ' cat-instruction-banner--compact' : '' ?>" role="note">
                <span class="cat-instruction-icon" aria-hidden="true">
                    <i class="fa-solid <?= $icon ?>"></i>
                </span>
                <p class="cat-instruction-line"><?= htmlspecialchars($title) ?></p>
            </div>
        </div>
        <?php
    }
}
?>
<?php $nav_active = $cat_nav_active; include __DIR__ . '/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

<?php if (!$cat_hide_hero): ?>
<section class="cat-hero">
    <span class="cat-hero-badge"><?= $cat_badge ?></span>
    <h1><?= htmlspecialchars($cat_title) ?> <em><?= htmlspecialchars($cat_title_em) ?></em></h1>
    <p class="cat-hero-desc"><?= $cat_desc ?></p>
    <?php if ($cat_steps_page): ?>
        <?php $_steps_page = $cat_steps_page; include_once __DIR__ . '/../steps_guide.php'; ?>
    <?php endif; ?>
</section>
<?php endif; ?>

<main class="cat-main<?= !empty($cat_instruction) ? ' cat-main--with-instruction' : '' ?>">
    <?php if (empty($cat_prompts)): ?>
        <?php if (!empty($cat_instruction)) { render_cat_instruction_banner($cat_instruction); } ?>
        <div class="grid-empty-msg">
            <div class="grid-empty-icon"><i class="fa-solid <?= htmlspecialchars($cat_empty_icon) ?>"></i></div>
            <h2><?= htmlspecialchars($cat_empty_title) ?></h2>
            <p><?= htmlspecialchars($cat_empty_text) ?></p>
        </div>
    <?php else:
        $sub_tags = [];
        foreach ($cat_prompts as $item) {
            foreach (array_map('trim', explode(',', strtolower($item['tag'] ?? ''))) as $t) {
                if ($t && $t !== $cat_exclude_tag) {
                    $sub_tags[] = $t;
                }
            }
        }
        $sub_tags = array_unique($sub_tags);
        sort($sub_tags);
    ?>
        <?php if (!empty($cat_instruction)) { render_cat_instruction_banner($cat_instruction); } ?>

        <?php if (!empty($sub_tags)): ?>
        <div class="home-tag-filters cat-tag-filters">
            <button type="button" class="filter-pill cat-filter-btn active" data-tag="all">All</button>
            <?php foreach ($sub_tags as $t): ?>
            <button type="button" class="filter-pill cat-filter-btn" data-tag="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucfirst($t)) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php render_prompt_grid($cat_prompts, ['grid_id' => 'card-stack']); ?>
        <div id="cat-filter-empty" class="grid-empty-msg" style="display:none;padding:50px 20px;text-align:center;">
            <div class="grid-empty-icon"><i class="fa-solid fa-filter-circle-xmark"></i></div>
            <h2>No prompts found</h2>
            <p>No prompts in this section match the selected tag.</p>
        </div>
    <?php endif; ?>

    <?php
    require_once __DIR__ . '/category_seo_renderer.php';
    render_category_seo_section($cat_nav_active ?? '');
    ?>
</main>

<script>
<?= prompt_page_url_js() ?>
(function() {
    function initCatFilters() {
        bindPromptCardClicks('.prompt-grid .prompt-card');

        var filterBtns = document.querySelectorAll('.cat-filter-btn');
        var cards = document.querySelectorAll('#card-stack .prompt-card');
        var emptyMsg = document.getElementById('cat-filter-empty');
        if (!filterBtns.length) return;

        function norm(s) {
            return (s || '').toLowerCase().replace(/\s+/g, ' ').trim();
        }

        function applyFilter(selectedTag, updateHistory) {
            var target = norm(selectedTag || 'all');
            var matchedCount = 0;

            filterBtns.forEach(function(b) {
                var bTag = norm(b.dataset.tag || 'all');
                b.classList.toggle('active', bTag === target);
            });

            cards.forEach(function(card) {
                var cardTags = (card.dataset.tags || '').split(',').map(norm).filter(Boolean);
                var isMatch = (target === 'all' || cardTags.indexOf(target) !== -1);

                if (isMatch) {
                    card.classList.remove('is-tag-hidden');
                    card.removeAttribute('hidden');
                    card.style.removeProperty('display');
                    matchedCount++;
                } else {
                    card.classList.add('is-tag-hidden');
                    card.setAttribute('hidden', '');
                    card.style.setProperty('display', 'none', 'important');
                }
            });

            if (emptyMsg) {
                emptyMsg.style.display = (matchedCount === 0) ? 'block' : 'none';
            }

            if (updateHistory) {
                try {
                    var u = new URL(window.location.href);
                    if (target === 'all') {
                        u.searchParams.delete('tag');
                    } else {
                        u.searchParams.set('tag', target);
                    }
                    window.history.replaceState({ catTag: target }, '', u.pathname + u.search + u.hash);
                } catch(e) {}
            }
        }

        filterBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var tag = btn.dataset.tag || 'all';
                applyFilter(tag, true);
            });
        });

        try {
            var urlParam = new URLSearchParams(window.location.search).get('tag');
            if (urlParam) {
                applyFilter(urlParam, false);
            }
        } catch(e) {}

        window.addEventListener('popstate', function() {
            var urlParam = new URLSearchParams(window.location.search).get('tag') || 'all';
            applyFilter(urlParam, false);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCatFilters);
    } else {
        initCatFilters();
    }
})();
</script>
