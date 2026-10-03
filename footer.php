<?php
$site_base = $site_base ?? '';
require_once __DIR__ . '/includes/footer_helper.php';
$footer_cfg = get_footer_config();
$footer_columns = $footer_cfg['columns'] ?? [];
$footer_watermark = $footer_cfg['watermark_text'] ?? 'ARIGATO';
$footer_tagline = $footer_cfg['tagline'] ?? 'KEEP CREATING.';
$footer_brand = $footer_cfg['brand_name'] ?? 'ARIGATO DEVAN';
$footer_socials = $footer_cfg['social_links'] ?? [];
$footer_blog_ids = $footer_cfg['blog_ids'] ?? [];

// Resolve dynamic 5 blogs if column type is blogs
$resolved_blogs = resolve_footer_blog_links($footer_blog_ids, $pdo ?? null);
?>
<link rel="stylesheet" href="<?= htmlspecialchars($site_base) ?>css/store-footer.css?v=<?= @filemtime(__DIR__ . '/css/store-footer.css') ?: '20261004v1' ?>">
<?php if (!empty($_SESSION['user_id'])): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($site_base) ?>css/logout-confirm.css?v=<?= @filemtime(__DIR__ . '/css/logout-confirm.css') ?: '20261004v1' ?>">
<?php endif; ?>

<footer class="store-footer">
    <div class="store-footer-inner">
        <!-- Top Multi-Column Navigation -->
        <?php if (!empty($footer_columns)): ?>
        <div class="sf-grid" role="navigation" aria-label="Footer Navigation">
            <?php foreach ($footer_columns as $col):
                $c_type = $col['type'] ?? ($col['id'] ?? '');
                $col_title = $col['title'] ?? '';
                if ($c_type === 'blogs') {
                    $col_title = 'Blogs';
                    $links = array_slice($resolved_blogs, 0, 5);
                } else {
                    $links = $col['links'] ?? [];
                }
            ?>
            <div class="sf-col">
                <div class="sf-col-title"><?= htmlspecialchars($col_title) ?></div>
                <?php if (!empty($links) && is_array($links)): ?>
                <ul class="sf-links">
                    <?php foreach ($links as $lnk):
                        $l_url = trim((string)($lnk['url'] ?? ''));
                        if ($l_url === '') continue;
                        $is_external = preg_match('#^https?://#i', $l_url);
                        $href = $is_external ? $l_url : ($site_base . ltrim($l_url, '/'));
                        $target = !empty($lnk['target']) ? $lnk['target'] : ($is_external ? '_blank' : '_self');
                        $rel = ($target === '_blank') ? 'rel="noopener"' : '';
                        $badge = trim((string)($lnk['badge'] ?? ''));
                        $badge_class = 'sf-badge';
                        if (stripos($badge, 'pro') !== false) {
                            $badge_class .= ' sf-badge-gold';
                        } elseif (stripos($badge, 'love') !== false || stripos($badge, '17k') !== false) {
                            $badge_class .= ' sf-badge-teal';
                        }
                    ?>
                    <li>
                        <a href="<?= htmlspecialchars($href) ?>" class="sf-link" target="<?= htmlspecialchars($target) ?>" <?= $rel ?>>
                            <span><?= htmlspecialchars($lnk['title'] ?? '') ?></span>
                            <?php if ($badge !== ''): ?>
                                <span class="<?= $badge_class ?>"><?= htmlspecialchars($badge) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Horizontal Divider -->
        <hr class="sf-divider" aria-hidden="true">

        <!-- Bottom Bar: Brand & Copyright + Social Pills -->
        <div class="sf-bottom-bar">
            <div class="sf-copy">
                &copy; <?= date("Y") ?> <span class="sf-brand-name"><?= htmlspecialchars($footer_brand) ?></span>. <span class="sf-tagline"><?= htmlspecialchars($footer_tagline) ?></span>
            </div>

            <?php if (!empty($footer_socials)): ?>
            <div class="sf-socials" aria-label="Social Links">
                <?php foreach ($footer_socials as $soc):
                    $is_enabled = !empty($soc['enabled']);
                    $s_url = trim((string)($soc['url'] ?? ''));
                    if (!$is_enabled || $s_url === '') continue;
                    $platform = strtolower($soc['platform'] ?? 'link');
                    $is_ext = preg_match('#^https?://#i', $s_url);
                    $s_href = $is_ext ? $s_url : ($site_base . ltrim($s_url, '/'));
                    $s_target = $is_ext ? '_blank' : '_self';
                    $s_icon = $soc['icon'] ?? 'fa-solid fa-link';
                ?>
                <a href="<?= htmlspecialchars($s_href) ?>" 
                   class="sf-social-btn sf-soc-<?= htmlspecialchars($platform) ?>" 
                   target="<?= htmlspecialchars($s_target) ?>" 
                   rel="noopener" 
                   title="<?= htmlspecialchars($soc['title'] ?? ucfirst($platform)) ?>">
                    <i class="<?= htmlspecialchars($s_icon) ?>"></i>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Big Faded ARIGATO Watermark -->
        <?php if (!empty($footer_watermark)): ?>
        <div class="sf-watermark-wrap" aria-hidden="true">
            <span class="sf-watermark-text"><?= htmlspecialchars($footer_watermark) ?></span>
        </div>
        <?php endif; ?>
    </div>
</footer>

<?php if (!empty($_SESSION['user_id'])): ?>
    <?php include_once __DIR__ . '/includes/logout_confirm.php'; ?>
    <script src="<?= htmlspecialchars($site_base) ?>js/logout-confirm.js?v=20260781" defer></script>
<?php endif; ?>
