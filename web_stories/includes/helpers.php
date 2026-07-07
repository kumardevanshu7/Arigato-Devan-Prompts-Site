<?php
/**
 * Web Stories — uploads, settings, SEO-friendly AMP output.
 */
require_once SITE_ROOT . '/includes/image_helpers.php';

function ws_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'story';
}

function ws_abs_url(string $path): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'arigatodevan.com';
    return $scheme . '://' . $host . '/' . ltrim($path, '/');
}

function ws_story_url(string $slug, bool $preview = false): string
{
    $url = 'web_stories/story.php?slug=' . rawurlencode($slug);
    if ($preview) {
        $url .= '&preview=1';
    }
    return ws_abs_url($url);
}

function ws_upload_image(array $file, string $prefix, int $maxW = 1080, int $maxH = 1920): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    if (!in_array($mime, $allowed, true)) {
        return null;
    }
    $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext = $ext_map[$mime] ?? 'jpg';
    $dir = SITE_ROOT . '/uploads/web_stories/';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return null;
    }
    $target = $dir . $prefix . '_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }
    $saved = resizeToWebP($target, $maxW, $maxH, 85);
    return 'uploads/web_stories/' . basename($saved);
}

function ws_esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function ws_amp_js_str(string $s): string
{
    return addcslashes($s, "\\'");
}

function ws_setting(PDO $pdo, string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = $pdo->query('SELECT setting_key, setting_value FROM web_stories_settings')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (PDOException $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function ws_save_setting(PDO $pdo, string $key, string $value): void
{
    $pdo->prepare('INSERT INTO web_stories_settings (setting_key, setting_value) VALUES (?,?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
        ->execute([$key, $value]);
}

function ws_delete_story_files(PDO $pdo, int $id): void
{
    $st = $pdo->prepare('SELECT poster_image FROM web_stories WHERE id = ?');
    $st->execute([$id]);
    if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['poster_image']) && is_file(SITE_ROOT . '/' . $row['poster_image'])) {
            @unlink(SITE_ROOT . '/' . $row['poster_image']);
        }
    }
    $pg = $pdo->prepare('SELECT bg_image FROM web_story_pages WHERE story_id = ?');
    $pg->execute([$id]);
    while ($p = $pg->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($p['bg_image']) && is_file(SITE_ROOT . '/' . $p['bg_image'])) {
            @unlink(SITE_ROOT . '/' . $p['bg_image']);
        }
    }
}

function ws_validate_for_publish(array $story, array $pages): ?string
{
    if (empty($story['title'])) {
        return 'Title is required.';
    }
    if (empty($story['poster_image'])) {
        return 'Poster image (9:16) is required for Google.';
    }
    if (count($pages) < 5) {
        return 'Minimum 5 slides required (Google Web Stories guideline).';
    }
    if (count($pages) > 30) {
        return 'Maximum 30 slides allowed.';
    }
    if (empty(trim($story['description'] ?? ''))) {
        return 'SEO description is required before publishing.';
    }
    foreach ($pages as $i => $p) {
        if (empty($p['bg_image']) && empty($p['bg_color'])) {
            return 'Slide ' . ($i + 1) . ' needs a background image or color.';
        }
    }
    return null;
}

function ws_share_provider_options(): array
{
    return [
        'whatsapp' => 'WhatsApp',
        'twitter' => 'X (Twitter)',
        'facebook' => 'Facebook',
        'linkedin' => 'LinkedIn',
        'email' => 'Email',
        'system' => 'More / Native share',
        'pinterest' => 'Pinterest',
        'sms' => 'SMS',
    ];
}

function ws_share_providers_enabled(PDO $pdo): array
{
    $raw = trim(ws_setting($pdo, 'share_providers', '') ?: '');
    $allowed = array_keys(ws_share_provider_options());
    if ($raw === '') {
        return ['whatsapp', 'twitter', 'facebook', 'linkedin', 'email', 'system'];
    }
    $picked = array_values(array_filter(array_map('trim', explode(',', $raw))));
    $picked = array_values(array_intersect($picked, $allowed));
    return $picked ?: ['whatsapp', 'twitter', 'facebook', 'linkedin', 'email', 'system'];
}

function ws_share_message(PDO $pdo, array $story, string $url): string
{
    $title = trim($story['title'] ?? '') ?: 'Web Story';
    $desc = trim($story['description'] ?? '') ?: $title;
    $tpl = trim(ws_setting($pdo, 'share_text_template', '') ?: '');
    if ($tpl === '') {
        $tpl = '🔥 {title} — Arigato Devan Web Story' . "\n" . '{url}';
    }
    return str_replace(
        ['{title}', '{url}', '{description}'],
        [$title, $url, $desc],
        $tpl
    );
}

function ws_build_share_config(PDO $pdo, array $story): array
{
    $url = ws_story_url($story['slug'] ?? '');
    $title = trim($story['title'] ?? '') ?: 'Web Story';
    $desc = trim($story['description'] ?? '') ?: $title;
    $message = ws_share_message($pdo, $story, $url);
    $twitterText = trim(ws_setting($pdo, 'share_twitter_text', '') ?: '');
    if ($twitterText === '') {
        $twitterText = '{title} — swipe through on Arigato Devan';
    }
    $twitterText = str_replace(['{title}', '{url}', '{description}'], [$title, $url, $desc], $twitterText);
    if (strlen($twitterText) > 240) {
        $twitterText = mb_substr($twitterText, 0, 237) . '...';
    }

    $fbAppId = trim(ws_setting($pdo, 'share_facebook_app_id', '') ?: '');
    $providers = [];
    foreach (ws_share_providers_enabled($pdo) as $key) {
        switch ($key) {
            case 'whatsapp':
                $providers[] = ['provider' => 'whatsapp', 'text' => $message];
                break;
            case 'twitter':
                $providers[] = ['provider' => 'twitter', 'text' => $twitterText];
                break;
            case 'facebook':
                $providers[] = $fbAppId !== ''
                    ? ['provider' => 'facebook', 'app_id' => $fbAppId]
                    : 'facebook';
                break;
            case 'linkedin':
                $providers[] = 'linkedin';
                break;
            case 'email':
                $providers[] = [
                    'provider' => 'email',
                    'subject' => $title . ' — Arigato Devan',
                    'body' => $message,
                ];
                break;
            case 'pinterest':
                $providers[] = ['provider' => 'pinterest', 'description' => $desc];
                break;
            case 'sms':
                $providers[] = ['provider' => 'sms', 'body' => $message];
                break;
            case 'system':
                $providers[] = 'system';
                break;
        }
    }

    return ['shareProviders' => $providers];
}

function ws_render_amp_story(PDO $pdo, array $story, array $pages): void
{
    $title = $story['title'] ?? 'Web Story';
    $slug = $story['slug'] ?? '';
    $desc = trim($story['description'] ?? '') ?: $title;
    $keywords = trim($story['meta_keywords'] ?? '');
    $publisher = $story['publisher_name'] ?: (ws_setting($pdo, 'publisher_name', 'Arigato Devan') ?: 'Arigato Devan');
    $canonical = ws_story_url($slug);
    $poster = !empty($story['poster_image'])
        ? ws_abs_url($story['poster_image'])
        : (!empty($pages[0]['bg_image']) ? ws_abs_url($pages[0]['bg_image']) : ws_abs_url('favicon.ico'));
    $logoPath = ws_setting($pdo, 'publisher_logo', '') ?: 'favicon.ico';
    $logo = ws_abs_url($logoPath);
    $gaId = trim(ws_setting($pdo, 'ga_tracking_id', '') ?: '');
    $noindex = !empty($story['noindex']);
    $needsAnalytics = $gaId !== '';
    $presetsUsed = [];
    foreach ($pages as $p) {
        $np = ws_normalize_slide($p);
        $presetsUsed[] = $np['font_preset'];
    }
    $presetsUsed = array_unique($presetsUsed);
    $fontsUrl = ws_google_fonts_url($presetsUsed);
    $shareConfig = ws_build_share_config($pdo, $story);
    $fontCss = '';
    foreach (ws_font_presets() as $key => $fp) {
        if (!in_array($key, $presetsUsed, true)) {
            continue;
        }
        $fontCss .= ".ws-font-{$key} h1{font-family:{$fp['family']};font-size:{$fp['title_size']};font-weight:{$fp['title_weight']};letter-spacing:{$fp['title_spacing']};text-transform:{$fp['title_transform']};}";
        $fontCss .= ".ws-font-{$key} p{font-family:{$fp['family']};font-size:{$fp['body_size']};font-weight:{$fp['body_weight']};text-transform:{$fp['body_transform']};}";
        if (!empty($fp['accent'])) {
            $fontCss .= ".ws-font-{$key} h1{color:{$fp['accent']};}";
        }
    }

    header('Content-Type: text/html; charset=utf-8');
    if ($noindex) {
        header('X-Robots-Tag: noindex, nofollow', true);
    }

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $title,
        'description' => $desc,
        'image' => [$poster],
        'author' => ['@type' => 'Organization', 'name' => $publisher],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $publisher,
            'logo' => ['@type' => 'ImageObject', 'url' => $logo],
        ],
        'mainEntityOfPage' => $canonical,
    ];
    ?>
<!doctype html>
<html ⚡ lang="en">
<head>
    <meta charset="utf-8">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-story" src="https://cdn.ampproject.org/v0/amp-story-1.0.js"></script>
    <?php if ($needsAnalytics): ?>
    <script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>
    <?php endif; ?>
    <title><?= ws_esc($title) ?></title>
    <link rel="canonical" href="<?= ws_esc($canonical) ?>">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <meta name="description" content="<?= ws_esc($desc) ?>">
    <?php if ($keywords): ?><meta name="keywords" content="<?= ws_esc($keywords) ?>"><?php endif; ?>
    <?php if ($noindex): ?><meta name="robots" content="noindex,nofollow"><?php else: ?><meta name="robots" content="index,follow,max-image-preview:large"><?php endif; ?>
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= ws_esc($title) ?>">
    <meta property="og:description" content="<?= ws_esc($desc) ?>">
    <meta property="og:url" content="<?= ws_esc($canonical) ?>">
    <meta property="og:image" content="<?= ws_esc($poster) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= ws_esc($title) ?>">
    <meta name="twitter:description" content="<?= ws_esc($desc) ?>">
    <meta name="twitter:image" content="<?= ws_esc($poster) ?>">
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <link rel="stylesheet" href="<?= ws_esc($fontsUrl) ?>">
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>
    <noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    <style amp-custom>
        amp-story-page { background: #2F4156; }
        .ws-fill-color { width: 100%; height: 100%; }
        .ws-fill-wrap { position: relative; width: 100%; height: 100%; }
        .ws-grad { position: absolute; inset: 0; pointer-events: none; z-index: 1; }
        .ws-overlay { position: absolute; inset: 0; width: 100%; height: 100%; box-sizing: border-box; }
        .ws-text {
            position: absolute;
            left: 7%;
            right: 7%;
            width: 86%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            z-index: 2;
        }
        .ws-text.valign-top { top: 14%; bottom: auto; transform: none; }
        .ws-text.valign-center { top: 50%; bottom: auto; transform: translateY(-50%); }
        .ws-text.valign-bottom { top: auto; bottom: 12%; transform: none; }
        .ws-text.valign-bottom.above-logo { bottom: 22%; }
        .ws-text.align-left { align-items: flex-start; text-align: left; }
        .ws-text.align-center { align-items: center; text-align: center; }
        .ws-text.align-right { align-items: flex-end; text-align: right; }
        .ws-text h1 { margin: 0 0 10px; line-height: 1.15; text-shadow: 0 2px 14px rgba(0,0,0,.55); max-width: 100%; color: #fff; }
        .ws-text p { margin: 0; line-height: 1.45; text-shadow: 0 1px 10px rgba(0,0,0,.5); max-width: 100%; opacity: .96; color: #fff; }
        .ws-brand { font-size: .68rem; letter-spacing: .16em; text-transform: uppercase; opacity: .88; margin-bottom: 8px; color: #fff; }
        .ws-cta { display: inline-block; margin-top: 16px; padding: 11px 20px; border-radius: 999px; background: linear-gradient(135deg,#F5709D,#11FFC9); color: #2F4156; font-weight: 800; text-decoration: none; font-size: .88rem; cursor: pointer; }
        .ws-logo {
            position: absolute;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            z-index: 3;
            padding: 0;
            width: fit-content;
            max-width: 86%;
        }
        .ws-logo.pos-top-left { top: 12%; left: 7%; right: auto; bottom: auto; transform: none; }
        .ws-logo.pos-top-center { top: 12%; left: 50%; right: auto; bottom: auto; transform: translateX(-50%); }
        .ws-logo.pos-top-right { top: 12%; right: 7%; left: auto; bottom: auto; transform: none; }
        .ws-logo.pos-bottom-left { bottom: 5%; left: 7%; right: auto; top: auto; transform: none; }
        .ws-logo.pos-bottom-center { bottom: 5%; left: 50%; right: auto; top: auto; transform: translateX(-50%); }
        .ws-logo.pos-bottom-right { bottom: 5%; right: 7%; left: auto; top: auto; transform: none; }
        .ws-logo-img { object-fit: cover; display: block; flex-shrink: 0; }
        .ws-logo.style-circle .ws-logo-img { width: 34px; height: 34px; border-radius: 50%; border: 2px solid rgba(255,255,255,.92); box-shadow: 0 2px 10px rgba(0,0,0,.28); }
        .ws-logo.style-ring .ws-logo-ring { padding: 2px; border-radius: 50%; background: linear-gradient(135deg,#F5709D,#11FFC9,#7c3aed); display: inline-flex; line-height: 0; }
        .ws-logo.style-ring .ws-logo-img { width: 30px; height: 30px; border-radius: 50%; border: 1.5px solid #fff; }
        .ws-logo.style-square .ws-logo-img { width: 34px; height: 34px; border-radius: 8px; border: 1.5px solid rgba(255,255,255,.88); }
        .ws-logo.style-badge { background: rgba(0,0,0,.38); border-radius: 999px; padding: 4px 10px 4px 4px; border: 1px solid rgba(255,255,255,.2); backdrop-filter: blur(8px); gap: 7px; white-space: nowrap; }
        .ws-logo.style-badge .ws-logo-img { width: 26px; height: 26px; border-radius: 50%; border: none; box-shadow: none; }
        .ws-logo-name { font-family: 'Outfit', sans-serif; font-size: .56rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; text-shadow: 0 1px 4px rgba(0,0,0,.45); color: rgba(255,255,255,.94); line-height: 1; white-space: nowrap; }
        <?= $fontCss ?>
    </style>
</head>
<body>
<?php if ($needsAnalytics):
    $isGa4 = strpos($gaId, 'G-') === 0;
?>
<amp-analytics type="<?= $isGa4 ? 'googleanalytics' : 'gtag' ?>">
    <script type="application/json">
    <?= $isGa4 ? json_encode([
        'vars' => ['gtag_id' => $gaId],
        'triggers' => ['storyProgress' => ['on' => 'story-page-visible', 'request' => 'event', 'vars' => ['event_name' => 'page_view']]],
    ]) : json_encode([
        'vars' => ['account' => $gaId],
        'triggers' => ['storyProgress' => ['on' => 'story-page-visible', 'request' => 'pageview']],
    ]) ?>
    </script>
</amp-analytics>
<?php endif; ?>
<amp-story standalone
    title="<?= ws_esc($title) ?>"
    publisher="<?= ws_esc($publisher) ?>"
    publisher-logo-src="<?= ws_esc($logo) ?>"
    poster-portrait-src="<?= ws_esc($poster) ?>">

    <?php foreach ($pages as $i => $page):
        $page = ws_normalize_slide($page);
        $pid = 'page-' . ($i + 1);
        $bgColor = preg_match('/^#[0-9A-Fa-f]{3,8}$/', $page['bg_color'] ?? '') ? $page['bg_color'] : '#2F4156';
        $hasImg = !empty($page['bg_image']);
        $ctaUrl = trim($page['cta_url'] ?? '');
        $ctaLabel = trim($page['cta_label'] ?? '');
        $align = in_array($page['text_align'] ?? '', ['left', 'center', 'right'], true) ? $page['text_align'] : 'left';
        $valign = $page['text_valign'];
        $fontKey = $page['font_preset'];
        $gradCss = ws_gradient_css((int)$page['gradient_opacity'], $page['gradient_color']);
        $showLogo = !empty($page['show_logo']);
        $logoPos = $page['logo_position'];
        $logoStyle = $page['logo_style'];
        $anim = preg_match('/^[a-z0-9-]+$/', $page['animate_in'] ?? '') ? $page['animate_in'] : 'fade-in';
        $advSec = max(0, (int)($page['auto_advance_sec'] ?? 0));
        $pageAttrs = $advSec > 0 ? ' auto-advance-after="' . $advSec . 's"' : '';
        $logoTop = strpos($logoPos, 'top-') === 0;
        $logoBottom = strpos($logoPos, 'bottom-') === 0;
        $hasText = !empty($page['title']) || !empty($page['body_text']) || ($ctaUrl && $ctaLabel);
        $textAboveLogo = $hasText && $showLogo && $logoBottom && $valign === 'bottom' ? ' above-logo' : '';
    ?>
    <amp-story-page id="<?= $pid ?>"<?= $pageAttrs ?>>
        <amp-story-grid-layer template="fill">
            <div class="ws-fill-wrap">
            <?php if ($hasImg): ?>
            <amp-img src="<?= ws_esc(ws_abs_url($page['bg_image'])) ?>"
                     width="720" height="1280" layout="responsive"
                     alt="<?= ws_esc($page['title'] ?: 'Story slide ' . ($i + 1)) ?>"></amp-img>
            <?php else: ?>
            <div class="ws-fill-color" style="background:<?= ws_esc($bgColor) ?>"></div>
            <?php endif; ?>
            <?php if ($gradCss !== 'none'): ?>
            <div class="ws-grad" style="background:<?= ws_esc($gradCss) ?>"></div>
            <?php endif; ?>
            </div>
        </amp-story-grid-layer>
        <?php if ($hasText || $showLogo): ?>
        <amp-story-grid-layer template="fill">
            <div class="ws-overlay">
                <?php if ($showLogo): ?>
                <div class="ws-logo style-<?= ws_esc($logoStyle) ?> pos-<?= ws_esc($logoPos) ?>">
                    <?php if ($logoStyle === 'ring'): ?><div class="ws-logo-ring"><?php endif; ?>
                    <amp-img class="ws-logo-img" src="<?= ws_esc($logo) ?>" width="34" height="34" layout="fixed" alt="<?= ws_esc($publisher) ?>"></amp-img>
                    <?php if ($logoStyle === 'ring'): ?></div><?php endif; ?>
                    <span class="ws-logo-name"><?= ws_esc($publisher) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($hasText): ?>
                <div class="ws-text ws-font-<?= ws_esc($fontKey) ?> align-<?= ws_esc($align) ?> valign-<?= ws_esc($valign) ?><?= $textAboveLogo ?>">
                    <?php if ($i === 0 && !$showLogo): ?><div class="ws-brand"><?= ws_esc($publisher) ?></div><?php endif; ?>
                    <?php if (!empty($page['title'])): ?>
                    <h1 animate-in="<?= ws_esc($anim) ?>" animate-in-duration="0.5s"><?= ws_esc($page['title']) ?></h1>
                    <?php endif; ?>
                    <?php if (!empty($page['body_text'])): ?>
                    <p animate-in="<?= ws_esc($anim) ?>" animate-in-delay="0.12s" animate-in-duration="0.5s"><?= ws_esc($page['body_text']) ?></p>
                    <?php endif; ?>
                    <?php if ($ctaUrl && $ctaLabel): ?>
                    <div class="ws-cta" role="link" tabindex="0" animate-in="fade-in" animate-in-delay="0.25s" on="tap:AMP.navigateTo(url='<?= ws_amp_js_str($ctaUrl) ?>', target='_top')"><?= ws_esc($ctaLabel) ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </amp-story-grid-layer>
        <?php endif; ?>
    </amp-story-page>
    <?php endforeach; ?>

    <amp-story-social-share layout="nodisplay">
        <script type="application/json"><?= json_encode($shareConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    </amp-story-social-share>
</amp-story>
</body>
</html>
    <?php
}

function ws_ensure_schema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS web_stories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL DEFAULT '',
        slug VARCHAR(255) NOT NULL DEFAULT '',
        description TEXT,
        meta_keywords VARCHAR(500) DEFAULT '',
        poster_image VARCHAR(255) DEFAULT '',
        publisher_name VARCHAR(120) DEFAULT '',
        noindex TINYINT(1) NOT NULL DEFAULT 0,
        is_published TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS web_story_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        story_id INT NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        bg_image VARCHAR(255) DEFAULT '',
        bg_color VARCHAR(16) DEFAULT '#2F4156',
        title VARCHAR(500) DEFAULT '',
        body_text TEXT,
        cta_label VARCHAR(120) DEFAULT '',
        cta_url VARCHAR(500) DEFAULT '',
        text_align VARCHAR(10) DEFAULT 'left',
        animate_in VARCHAR(32) DEFAULT 'fade-in',
        auto_advance_sec INT NOT NULL DEFAULT 0,
        text_valign VARCHAR(10) DEFAULT 'bottom',
        font_preset VARCHAR(32) DEFAULT 'editorial',
        gradient_opacity TINYINT UNSIGNED NOT NULL DEFAULT 0,
        gradient_color VARCHAR(16) DEFAULT '#000000',
        show_logo TINYINT(1) NOT NULL DEFAULT 0,
        logo_style VARCHAR(16) DEFAULT 'circle',
        logo_position VARCHAR(24) DEFAULT 'bottom-center',
        KEY idx_story (story_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS web_stories_settings (
        setting_key VARCHAR(64) PRIMARY KEY,
        setting_value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $migrations = [
        'text_valign' => "VARCHAR(10) NOT NULL DEFAULT 'bottom'",
        'font_preset' => "VARCHAR(32) NOT NULL DEFAULT 'editorial'",
        'gradient_opacity' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'gradient_color' => "VARCHAR(16) NOT NULL DEFAULT '#000000'",
        'show_logo' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'logo_style' => "VARCHAR(16) NOT NULL DEFAULT 'circle'",
        'logo_position' => "VARCHAR(24) NOT NULL DEFAULT 'bottom-center'",
    ];
    foreach ($migrations as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE web_story_pages ADD COLUMN {$col} {$def}");
        } catch (PDOException $e) {
            // already exists
        }
    }
    $ready = true;
}

/** Font + layout presets for slides */
function ws_font_presets(): array
{
    return [
        'usa_bold' => [
            'label' => 'USA Headline',
            'family' => "'Oswald', sans-serif",
            'google' => 'Oswald:wght@500;600;700',
            'title_size' => '1.75rem',
            'title_weight' => '700',
            'title_transform' => 'uppercase',
            'title_spacing' => '0.04em',
            'body_size' => '1.05rem',
            'body_weight' => '600',
            'body_transform' => 'uppercase',
        ],
        'editorial' => [
            'label' => 'Editorial Serif',
            'family' => "'Playfair Display', serif",
            'google' => 'Playfair+Display:wght@600;700;900',
            'title_size' => '1.65rem',
            'title_weight' => '900',
            'title_transform' => 'none',
            'title_spacing' => '-0.02em',
            'body_size' => '0.95rem',
            'body_weight' => '400',
            'body_transform' => 'none',
        ],
        'neon_pulse' => [
            'label' => 'Neon Night',
            'family' => "'Outfit', sans-serif",
            'google' => 'Outfit:wght@500;700;800',
            'title_size' => '1.55rem',
            'title_weight' => '800',
            'title_transform' => 'uppercase',
            'title_spacing' => '0.12em',
            'body_size' => '0.88rem',
            'body_weight' => '500',
            'body_transform' => 'none',
        ],
        'minimal' => [
            'label' => 'Minimal Clean',
            'family' => "'Inter', sans-serif",
            'google' => 'Inter:wght@300;500;700',
            'title_size' => '1.35rem',
            'title_weight' => '700',
            'title_transform' => 'none',
            'title_spacing' => '-0.03em',
            'body_size' => '0.82rem',
            'body_weight' => '400',
            'body_transform' => 'none',
        ],
        'luxury' => [
            'label' => 'Luxury Gold',
            'family' => "'Cormorant Garamond', serif",
            'google' => 'Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500',
            'title_size' => '1.85rem',
            'title_weight' => '600',
            'title_transform' => 'none',
            'title_spacing' => '0.06em',
            'body_size' => '1rem',
            'body_weight' => '500',
            'body_transform' => 'none',
            'accent' => '#d4af37',
        ],
    ];
}

function ws_slide_layout_defaults(): array
{
    return [
        'text_valign' => 'bottom',
        'font_preset' => 'editorial',
        'gradient_opacity' => 0,
        'gradient_color' => '#000000',
        'show_logo' => 0,
        'logo_style' => 'circle',
        'logo_position' => 'bottom-center',
    ];
}

function ws_normalize_slide(array $slide): array
{
    $presets = array_keys(ws_font_presets());
    $layout = ws_slide_layout_defaults();
    $slide = array_merge($layout, $slide);
    $slide['text_valign'] = in_array($slide['text_valign'] ?? '', ['top', 'center', 'bottom'], true) ? $slide['text_valign'] : 'bottom';
    $slide['font_preset'] = in_array($slide['font_preset'] ?? '', $presets, true) ? $slide['font_preset'] : 'editorial';
    $slide['gradient_opacity'] = max(0, min(100, (int)($slide['gradient_opacity'] ?? 0)));
    $slide['gradient_color'] = preg_match('/^#[0-9A-Fa-f]{3,8}$/', $slide['gradient_color'] ?? '') ? $slide['gradient_color'] : '#000000';
    $slide['show_logo'] = !empty($slide['show_logo']) ? 1 : 0;
    $logoStyles = ['circle', 'ring', 'square', 'badge'];
    $slide['logo_style'] = in_array($slide['logo_style'] ?? '', $logoStyles, true) ? $slide['logo_style'] : 'circle';
    $logoPos = ['bottom-center', 'bottom-left', 'bottom-right', 'top-left', 'top-center', 'top-right'];
    $slide['logo_position'] = in_array($slide['logo_position'] ?? '', $logoPos, true) ? $slide['logo_position'] : 'bottom-center';
    return $slide;
}

function ws_hex_to_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function ws_gradient_css(int $opacity, string $color = '#000000'): string
{
    if ($opacity <= 0) {
        return 'none';
    }
    [$r, $g, $b] = ws_hex_to_rgb($color);
    $a = round($opacity / 100, 2);
    $mid = round($opacity * 0.45 / 100, 2);
    return "linear-gradient(to top, rgba({$r},{$g},{$b},{$a}) 0%, rgba({$r},{$g},{$b},{$mid}) 38%, transparent 72%)";
}

function ws_google_fonts_url(array $presetsUsed): string
{
    $families = [];
    foreach (ws_font_presets() as $key => $p) {
        if (in_array($key, $presetsUsed, true) && !empty($p['google'])) {
            $families[] = 'family=' . $p['google'];
        }
    }
    if (!$families) {
        $families[] = 'family=Inter:wght@400;600;700';
    }
    $families[] = 'family=Outfit:wght@600;700';
    return 'https://fonts.googleapis.com/css2?' . implode('&', array_unique($families)) . '&display=swap';
}

function ws_render_slide_design_fields(array $slide, int|string $i): void
{
    $s = ws_normalize_slide($slide);
    $presets = ws_font_presets();
    ?>
    <div class="ws-design-block">
        <h4 class="ws-design-title"><i class="fa-solid fa-wand-magic-sparkles"></i> Design</h4>
        <div class="ws-row2">
            <div class="ws-field">
                <label>Text Position</label>
                <select name="slides[<?= $i ?>][text_valign]">
                    <option value="top" <?= $s['text_valign'] === 'top' ? 'selected' : '' ?>>Top</option>
                    <option value="center" <?= $s['text_valign'] === 'center' ? 'selected' : '' ?>>Center</option>
                    <option value="bottom" <?= $s['text_valign'] === 'bottom' ? 'selected' : '' ?>>Bottom</option>
                </select>
            </div>
            <div class="ws-field">
                <label>Font Style</label>
                <select name="slides[<?= $i ?>][font_preset]">
                    <?php foreach ($presets as $key => $p): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= $s['font_preset'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($p['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="ws-field">
            <label>Lower Gradient <span class="ws-range-val" data-for="grad-<?= $i ?>"><?= (int)$s['gradient_opacity'] ?>%</span></label>
            <input type="range" class="ws-grad-range" id="grad-<?= $i ?>" name="slides[<?= $i ?>][gradient_opacity]" min="0" max="100" value="<?= (int)$s['gradient_opacity'] ?>">
            <small class="ws-hint">0 = off · Higher = darker fade at bottom for readable text</small>
        </div>
        <div class="ws-row2">
            <div class="ws-field ws-field-color">
                <label>Gradient Color</label>
                <div class="ws-color-wrap">
                    <input type="color" name="slides[<?= $i ?>][gradient_color]" value="<?= htmlspecialchars($s['gradient_color']) ?>">
                    <span class="ws-color-val"><?= htmlspecialchars($s['gradient_color']) ?></span>
                </div>
            </div>
            <div class="ws-field ws-field-check">
                <label class="ws-check">
                    <input type="checkbox" name="slides[<?= $i ?>][show_logo]" value="1" <?= $s['show_logo'] ? 'checked' : '' ?>>
                    Show logo on slide
                </label>
            </div>
        </div>
        <div class="ws-row2">
            <div class="ws-field">
                <label>Logo Style</label>
                <select name="slides[<?= $i ?>][logo_style]">
                    <option value="circle" <?= $s['logo_style'] === 'circle' ? 'selected' : '' ?>>Circle</option>
                    <option value="ring" <?= $s['logo_style'] === 'ring' ? 'selected' : '' ?>>Gradient Ring</option>
                    <option value="square" <?= $s['logo_style'] === 'square' ? 'selected' : '' ?>>Rounded Square</option>
                    <option value="badge" <?= $s['logo_style'] === 'badge' ? 'selected' : '' ?>>Badge Pill</option>
                </select>
            </div>
            <div class="ws-field">
                <label>Logo Position</label>
                <select name="slides[<?= $i ?>][logo_position]">
                    <?php
                    $lpos = [
                        'top-left' => 'Top Left', 'top-center' => 'Top Center', 'top-right' => 'Top Right',
                        'bottom-left' => 'Bottom Left', 'bottom-center' => 'Bottom Center', 'bottom-right' => 'Bottom Right',
                    ];
                    foreach ($lpos as $val => $lbl):
                    ?>
                    <option value="<?= $val ?>" <?= $s['logo_position'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <?php
}

function ws_get_templates(): array
{
    $file = WS_ROOT . '/assets/templates.json';
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

/**
 * Styled image upload dropzone (hides native file input).
 */
function ws_render_upload_zone(string $inputName, string $previewUrl = '', string $hint = 'JPG, PNG, WebP · 9:16 portrait', bool $square = false): void
{
    $hasPreview = $previewUrl !== '';
    $displayName = $hasPreview ? basename(parse_url($previewUrl, PHP_URL_PATH) ?: $previewUrl) : '';
    $zoneClass = 'ws-upload-zone' . ($square ? ' ws-upload-square' : '');
    ?>
    <div class="<?= $zoneClass ?>">
        <input type="file" class="ws-upload-input" name="<?= htmlspecialchars($inputName) ?>" accept="image/jpeg,image/png,image/webp,image/gif">
        <div class="ws-upload-empty"<?= $hasPreview ? ' hidden' : '' ?>>
            <div class="ws-upload-icon" aria-hidden="true"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <span class="ws-upload-title">Upload image</span>
            <small><?= htmlspecialchars($hint) ?></small>
        </div>
        <div class="ws-upload-filled"<?= $hasPreview ? '' : ' hidden' ?>>
            <div class="ws-upload-thumb-wrap">
                <img class="ws-upload-thumb ws-local-img-preview" alt=""<?= $hasPreview ? ' src="' . htmlspecialchars($previewUrl) . '"' : '' ?>>
            </div>
            <div class="ws-upload-bar">
                <span class="ws-upload-fname"><?= htmlspecialchars($displayName) ?></span>
                <button type="button" class="ws-upload-btn" data-action="replace">Replace</button>
            </div>
        </div>
    </div>
    <?php
}
