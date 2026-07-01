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

function render_cat_instruction_banner(array $instruction): void {
    $compact = !isset($instruction['compact']) || $instruction['compact'] !== false;
    $icon    = htmlspecialchars($instruction['icon'] ?? 'fa-heart');
    ?>
    <div class="cat-instruction-wrap cat-instruction-wrap--above-grid">
        <div class="cat-instruction-banner<?= $compact ? ' cat-instruction-banner--compact' : '' ?>" role="note">
            <i class="fa-solid <?= $icon ?> cat-instruction-heart" aria-hidden="true"></i>
            <p class="cat-instruction-line"><?= htmlspecialchars($instruction['title'] ?? '') ?></p>
        </div>
    </div>
    <?php
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
    <?php endif; ?>
</main>

<script>
<?= prompt_page_url_js() ?>
document.addEventListener('DOMContentLoaded', function() {
    bindPromptCardClicks('.prompt-grid .prompt-card');
    document.querySelectorAll('.cat-filter-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.cat-filter-btn').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var tag = btn.dataset.tag;
            document.querySelectorAll('#card-stack .prompt-card').forEach(function(card) {
                var tags = (card.dataset.tags || '').split(',').map(function(t) { return t.trim(); });
                card.style.display = (tag === 'all' || tags.includes(tag)) ? '' : 'none';
            });
        });
    });
});
</script>
