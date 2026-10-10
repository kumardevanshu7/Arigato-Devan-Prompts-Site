<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

$id   = (int)($_GET['id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');
if ($id <= 0 && empty($slug)) { header("Location: gallery.php"); exit(); }

$where        = $slug ? "p.slug = ?"  : "p.id = ?";
$where_plain  = $slug ? "slug = ?"   : "id = ?";
$where_val    = $slug ? $slug         : $id;

if (isset($_SESSION["user_id"])) {
    $stmt = $pdo->prepare("
        SELECT p.*,
               IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
               IF(l.id IS NOT NULL, 1, 0) as is_liked,
               IF(sv.id IS NOT NULL, 1, 0) as is_saved
        FROM prompts p
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
        LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
        LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
        WHERE {$where}
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"], $where_val]);
} else {
    $stmt = $pdo->prepare("SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE {$where_plain}");
    $stmt->execute([$where_val]);
}

$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit();
}
$id = (int)$p['id'];

$db_type  = $p["prompt_type"] ?? "secret";
$ptype    = match($db_type) {
    "insta_viral"     => "insta_viral",
    "unreleased"      => "unreleased",
    "already_uploaded"=> "already_uploaded",
    "direct"          => "direct",
    "solo"            => "solo",
    default           => "secret_code"
};
$tinfo = [
    "secret_code"      => ["label" => "SECRET CODE",       "bg" => "#e6d7ff", "color" => "#4a00b0"],
    "unreleased"       => ["label" => "UNRELEASED",         "bg" => "#fff1b8", "color" => "#7a5c00"],
    "insta_viral"      => ["label" => "INSTA VIRAL",        "bg" => "#c8f5d4", "color" => "#1a5c30"],
    "already_uploaded" => ["label" => "ALREADY UPLOADED",   "bg" => "#e6f2ff", "color" => "#00509e"],
    "direct"           => ["label" => "DIRECT PROMPT",      "bg" => "#ffe4e6", "color" => "#be123c"],
    "solo"             => ["label" => "SOLO",               "bg" => "#dcfce7", "color" => "#166534"],
][$ptype];

require_once __DIR__ . '/includes/prompt_cards.php';

if (isset($_SESSION["user_id"])) {
    $rel_stmt = $pdo->prepare("
        SELECT p.id, p.slug, p.title, p.image_path, p.likes_count, p.view_count, p.prompt_type, p.tag, p.created_at, p.reel_link, p.best_works_in,
               IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
               IF(l.id IS NOT NULL, 1, 0) as is_liked,
               IF(sv.id IS NOT NULL, 1, 0) as is_saved
        FROM prompts p
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
        LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
        LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
        WHERE p.prompt_type = ? AND p.id != ? AND p.is_trial = 0
        ORDER BY RAND() LIMIT 4
    ");
    $rel_stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"], $db_type, $id]);
} else {
    $rel_stmt = $pdo->prepare("
        SELECT id, slug, title, image_path, likes_count, view_count, prompt_type, tag, created_at, reel_link, best_works_in,
               0 as is_unlocked, 0 as is_liked, 0 as is_saved
        FROM prompts
        WHERE prompt_type = ? AND id != ? AND is_trial = 0
        ORDER BY RAND() LIMIT 4
    ");
    $rel_stmt->execute([$db_type, $id]);
}
$related = $rel_stmt->fetchAll(PDO::FETCH_ASSOC);

$is_unlocked  = (bool)$p["is_unlocked"];
// Track view
$pdo->prepare("UPDATE prompts SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
$p['view_count'] = (int)($p['view_count'] ?? 0) + 1;
$asset_images = json_decode($p['asset_images'] ?? '[]', true) ?: [];
$solo_before_image = trim($p['solo_before_image'] ?? '');
$solo_examples = json_decode($p['solo_examples'] ?? '[]', true) ?: [];
$tags_arr          = array_filter(array_map('trim', explode(',', $p['tag'] ?? '')));
$extra_prompts_arr = json_decode($p['extra_prompts'] ?? '[]', true) ?: [];
$total_prompts     = 1 + count($extra_prompts_arr);
$og_img       = "https://arigatodevan.com/" . ltrim($p["image_path"] ?? "landingpics/lan9.webp", "/");
$page_title   = htmlspecialchars($p["title"]) . ' — AI Prompt | Arigato Devan';
$canonical    = !empty($p['slug'])
              ? "https://arigatodevan.com/prompts/" . ($ptype === 'solo' ? 'solo/' : '') . $p['slug']
              : "https://arigatodevan.com/prompt.php?id={$id}";
$_page_canonical = $canonical;
$tags_str     = !empty($tags_arr) ? implode(', ', array_slice($tags_arr, 0, 3)) : '';
$meta_desc    = !empty($p['description'])
              ? htmlspecialchars($p['description'])
              : htmlspecialchars($p['title']) . ' is a ' . $tinfo['label'] . ($ptype === 'solo' ? ' AI solo photo prompt' : ' AI couple prompt') . ' on Arigato Devan.'
                . (!empty($tags_str) ? ' Perfect for ' . $tags_str . '.' : '')
                . ' Copy and use instantly on ChatGPT or any AI tool.';
$meta_keywords = !empty($p['meta_keywords']) ? htmlspecialchars($p['meta_keywords']) : htmlspecialchars($tags_str);
$meta_desc_raw = trim($p['description'] ?? '');
$about_prompt_raw = trim($p['about_prompt'] ?? '');
require_once __DIR__ . '/includes/step_pics_helper.php';
$how_to_use_raw = trim((string)($p['how_to_use'] ?? ''));
$how_to_use_steps = parse_how_to_use_steps($how_to_use_raw);
$pp_kw_list    = array_filter(array_map('trim', explode(',', $p['meta_keywords'] ?? '')));
$type_page    = match($ptype) {
    'insta_viral'      => 'curated_ai_prompts.php',
    'unreleased'       => 'unreleased.php',
    'already_uploaded' => 'already_uploaded.php',
    'direct'           => 'gallery.php',
    'solo'             => 'solo_prompts.php',
    default            => 'gallery.php',
};
$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#2F4156">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= ($_SERVER['HTTP_HOST'] === 'localhost') ? '/Arigato%20Development%20Site/' : '/' ?>">
    <title><?= $page_title ?></title>
    <meta name="description" content="<?= $meta_desc ?>">
    <?php if (!empty($meta_keywords)): ?><meta name="keywords" content="<?= $meta_keywords ?>"><?php endif; ?>
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= htmlspecialchars($p['title']) ?> — Arigato Devan">
    <meta property="og:description" content="<?= $meta_desc ?>">
    <meta property="og:image" content="<?= $og_img ?>">
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <meta property="og:url" content="<?= $canonical ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= $og_img ?>">
    <script type="application/ld+json">
    <?= json_encode([
        '@context'  => 'https://schema.org',
        '@type'     => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',              'item' => 'https://arigatodevan.com'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $tinfo['label'],     'item' => 'https://arigatodevan.com/' . $type_page],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $p['title'],         'item' => $canonical],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <script type="application/ld+json">
    <?= json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'CreativeWork',
        'name'            => $p['title'],
        'description'     => $meta_desc,
        'url'             => $canonical,
        'image'           => $og_img,
        'author'          => ['@type' => 'Organization', 'name' => 'Arigato Devan'],
        'publisher'       => ['@type' => 'Organization', 'name' => 'Arigato Devan', 'url' => 'https://arigatodevan.com'],
        'keywords'        => implode(', ', $tags_arr),
        'genre'           => $tinfo['label'],
        'datePublished'   => isset($p['created_at']) ? date('c', strtotime($p['created_at'])) : null,
        'inLanguage'      => 'en',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php include_once 'includes/theme_head.php'; ?>
    <style>
    /* About prompt badge & subtitle hint */
    .pp-about h2 {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin: 0 0 8px 0 !important;
    }
    .pp-about-badge {
        font-size: 0.68rem;
        font-weight: 700;
        color: #ef4444;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .pp-about-hint {
        font-family: inherit;
        font-style: italic;
        font-size: 0.82rem;
        font-weight: 500;
        color: var(--pal-teal, #567c8d);
        margin: 0 0 22px 0 !important;
        line-height: 1.5;
        letter-spacing: 0.01em;
        display: block;
    }

    /* Clickable Keyword Filter Capsules */
    .pp-about-kw {
        margin-top: 16px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px 10px;
        align-items: center;
    }
    .pp-about-kw .pp-kw-chip,
    .pp-about-kw a,
    .pp-about-kw span {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        padding: 6px 14px !important;
        border-radius: 999px !important;
        background: #C8D9E6 !important;
        color: #2F4156 !important;
        font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        font-size: 0.72rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.02em !important;
        text-decoration: none !important;
        border: 1px solid rgba(47, 65, 86, 0.16) !important;
        box-shadow: 0 2px 6px rgba(47, 65, 86, 0.06) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        cursor: pointer !important;
        line-height: 1.4 !important;
        user-select: none !important;
    }
    .pp-about-kw .pp-kw-chip::before {
        content: "#";
        font-weight: 800;
        opacity: 0.55;
        font-size: 0.75rem;
    }
    .pp-about-kw .pp-kw-chip:hover,
    .pp-about-kw a:hover {
        background: #2F4156 !important;
        color: #ffffff !important;
        border-color: #2F4156 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 14px rgba(47, 65, 86, 0.22) !important;
    }
    .pp-about-kw .pp-kw-chip:hover::before {
        color: #11FFC9 !important;
        opacity: 0.95 !important;
    }
    .pp-about-kw .pp-kw-chip:active,
    .pp-about-kw a:active {
        transform: translateY(0) !important;
        box-shadow: 0 2px 6px rgba(47, 65, 86, 0.15) !important;
    }

    /* Footer & Page Layout Overrides */
    body.page-prompt {
        display: flex !important;
        flex-direction: column !important;
        min-height: 100vh !important;
        min-height: 100dvh !important;
        width: 100% !important;
        align-items: stretch !important;
    }
    .theme-nogoda .pp-wrap {
        flex: 1 0 auto !important;
        width: 100% !important;
        max-width: 1240px !important;
        margin: 0 auto !important;
        padding: clamp(20px, 3vw, 36px) clamp(16px, 4vw, 40px) 48px !important;
        box-sizing: border-box !important;
    }
    .theme-nogoda .pp-layout {
        display: flex !important;
        gap: clamp(24px, 3.5vw, 40px) !important;
        align-items: flex-start !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    @media (min-width: 769px) {
        .theme-nogoda .pp-img-col:not(.pp-solo-img-col) {
            width: clamp(280px, 26vw, 340px) !important;
            flex-shrink: 0 !important;
            position: sticky !important;
            top: calc(var(--nav-sticky-offset, 80px) + 12px) !important;
        }
    }
    .theme-nogoda .pp-info-col {
        flex: 1 1 0% !important;
        min-width: 0 !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    .theme-nogoda .pp-related {
        width: 100% !important;
        box-sizing: border-box !important;
        margin-top: clamp(32px, 4vw, 48px) !important;
        padding-top: 24px !important;
        border-top: 1px solid rgba(200, 217, 230, 0.85) !important;
    }
    .theme-nogoda .pp-rel-grid {
        display: grid !important;
        width: 100% !important;
        box-sizing: border-box !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: clamp(14px, 2vw, 22px) !important;
    }
    @media (max-width: 960px) {
        .theme-nogoda .pp-rel-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 16px !important;
        }
    }
    @media (max-width: 768px) {
        .theme-nogoda .pp-layout {
            flex-direction: column !important;
            align-items: center !important;
            gap: 20px !important;
        }
        .theme-nogoda .pp-img-col:not(.pp-solo-img-col),
        .theme-nogoda .pp-img-col {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto !important;
            position: static !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
        }
        .theme-nogoda .pp-img-frame {
            max-width: 280px !important;
            width: 100% !important;
            margin: 0 auto !important;
        }
        .theme-nogoda .pp-img-meta {
            max-width: 280px !important;
            width: 100% !important;
            margin: 12px auto 0 !important;
        }
        .theme-nogoda .pp-info-col {
            width: 100% !important;
        }
        .theme-nogoda .pp-rel-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 12px !important;
        }
    }
    body.page-prompt .store-footer,
    .theme-nogoda.page-prompt .store-footer {
        display: block !important;
        position: relative !important;
        z-index: 10 !important;
        width: 100% !important;
        margin-top: clamp(32px, 5vw, 60px) !important;
        background: #FFFDF4 !important;
        border-top: 1px solid var(--pal-sky, #C8D9E6) !important;
        padding: clamp(28px, 5vw, 40px) clamp(20px, 4vw, 80px) calc(28px + env(safe-area-inset-bottom, 0px)) !important;
        box-sizing: border-box !important;
        clear: both !important;
    }
    body.page-prompt .store-footer-inner,
    .theme-nogoda.page-prompt .store-footer-inner {
        display: flex !important;
        max-width: 1200px !important;
        margin: 0 auto !important;
        flex-direction: column !important;
        align-items: stretch !important;
        text-align: left !important;
    }

    /* Mobile view footer & scrolling guarantee */
    @media (max-width: 768px) {
        html,
        body.page-prompt,
        body.page-prompt.theme-nogoda {
            overflow-x: hidden !important;
        }
        body.page-prompt,
        body.page-prompt.theme-nogoda {
            display: flex !important;
            flex-direction: column !important;
            min-height: 100vh !important;
            min-height: 100dvh !important;
            height: auto !important;
        }
        .theme-nogoda .pp-wrap {
            flex: 1 0 auto !important;
            width: 100% !important;
            padding-bottom: 24px !important;
        }
        .theme-nogoda .pp-related {
            margin-bottom: 20px !important;
        }
        body.page-prompt .store-footer,
        .theme-nogoda.page-prompt .store-footer {
            display: block !important;
            flex-shrink: 0 !important;
            position: relative !important;
            z-index: 10 !important;
            width: 100% !important;
            margin-top: 36px !important;
            margin-bottom: 0 !important;
            padding: 32px 16px calc(68px + env(safe-area-inset-bottom, 0px)) !important;
            background: #FFFDF4 !important;
            border-top: 1px solid var(--pal-sky, #C8D9E6) !important;
            clear: both !important;
            box-sizing: border-box !important;
        }
        body.page-prompt .store-footer-inner,
        .theme-nogoda.page-prompt .store-footer-inner {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            text-align: left !important;
            width: 100% !important;
        }
        body.page-prompt .footer-links,
        .theme-nogoda.page-prompt .footer-links {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 10px 16px !important;
            width: 100% !important;
        }
        body.page-prompt .footer-links a,
        .theme-nogoda.page-prompt .footer-links a {
            font-size: 0.78rem !important;
            white-space: nowrap !important;
        }
    }

    /* Prevent drag ghost on touch/mouse to ensure smooth downward scrolling */
    .pp-rel-card img,
    .pp-prompt-img,
    .pp-rel-card,
    .pp-img-frame {
        -webkit-user-drag: none;
        -khtml-user-drag: none;
        -moz-user-drag: none;
        -o-user-drag: none;
        user-drag: none;
        user-select: none;
        -webkit-user-select: none;
    }

    /* How to Use Box */
    .pp-how-to-use {
        margin-top: 32px;
        padding: 22px 24px;
        background: var(--bg-card, #ffffff);
        border: 1.5px solid var(--pal-sky, #C8D9E6);
        border-radius: 18px;
        box-shadow: 0 2px 12px rgba(47, 65, 86, 0.08);
    }
    .pp-how-to-use h2 {
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--pal-teal, #567C8D);
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .pp-how-steps {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .pp-how-step-item {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .pp-how-step-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #2F4156;
        color: #ffffff;
        font-size: 0.75rem;
        font-weight: 800;
        flex-shrink: 0;
        margin-top: 0 !important;
        box-shadow: 0 2px 6px rgba(47, 65, 86, 0.15);
    }
    .pp-how-step-text {
        font-size: .88rem;
        line-height: 1.7;
        color: var(--pal-navy, #2F4156);
        flex: 0 1 auto !important;
    }
    .pp-how-step-content {
        display: flex;
        align-items: center;
        justify-content: flex-start !important;
        flex-wrap: wrap;
        gap: 8px 14px;
        flex: 1;
        min-width: 0;
    }
    .pp-step-pics-wrap {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        flex-shrink: 0;
        flex-wrap: wrap;
        padding: 4px 6px;
    }
    .pp-step-pic-plus {
        font-size: 0.85rem;
        font-weight: 900;
        color: var(--pal-teal, #567C8D);
        user-select: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 14px;
        height: 14px;
        opacity: 0.85;
    }
    .pp-step-pic-thumb {
        display: inline-block !important;
        width: 40px !important;
        height: 40px !important;
        min-width: 40px !important;
        min-height: 40px !important;
        max-width: 40px !important;
        max-height: 40px !important;
        border-radius: 9px !important;
        border: 1.5px solid var(--pal-sky, #C8D9E6) !important;
        background: #ffffff !important;
        overflow: hidden !important;
        position: relative !important;
        box-shadow: 0 2px 7px rgba(47, 65, 86, 0.1) !important;
        transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease, border-color 0.2s ease !important;
        cursor: pointer !important;
        flex-shrink: 0 !important;
        text-decoration: none !important;
    }
    /* Playful tilt for step thumbnail boxes */
    .pp-step-pic-thumb:nth-of-type(1) {
        transform: rotate(-3.5deg);
    }
    .pp-step-pic-thumb:nth-of-type(2) {
        transform: rotate(3.5deg);
    }
    .pp-step-pic-thumb:nth-of-type(3) {
        transform: rotate(2.5deg);
    }
    .pp-step-pic-thumb:nth-of-type(4) {
        transform: rotate(-3deg);
    }
    .pp-step-pic-thumb:nth-of-type(5) {
        transform: rotate(3deg);
    }
    .pp-step-pic-thumb:hover {
        transform: translateY(-2px) scale(1.15) rotate(0deg) !important;
        border-color: var(--pal-teal, #567C8D) !important;
        box-shadow: 0 6px 16px rgba(47, 65, 86, 0.22) !important;
        z-index: 5 !important;
    }
    .pp-step-pic-thumb img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
        border-radius: 7px !important;
    }
    .pp-step-pic-zoom {
        position: absolute;
        inset: 0;
        background: rgba(47, 65, 86, 0.45);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        opacity: 0;
        transition: opacity 0.2s ease;
        pointer-events: none;
    }
    .pp-step-pic-thumb:hover .pp-step-pic-zoom {
        opacity: 1;
    }
    @media (max-width: 640px) {
        .pp-how-step-item {
            align-items: flex-start !important;
        }
        .pp-how-step-num {
            margin-top: 2px !important;
        }
        .pp-how-step-content {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 8px !important;
            width: 100% !important;
        }
        .pp-how-step-text {
            width: 100% !important;
        }
        .pp-step-pics-wrap {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            margin-top: 6px !important;
            width: 100% !important;
            padding: 4px 2px !important;
        }
        .pp-step-pic-thumb {
            width: 52px !important;
            height: 52px !important;
            min-width: 52px !important;
            min-height: 52px !important;
            max-width: 52px !important;
            max-height: 52px !important;
            border-radius: 11px !important;
            border-width: 1.5px !important;
        }
        .pp-step-pic-thumb img {
            border-radius: 9px !important;
        }
        .pp-step-pic-plus {
            font-size: 1rem !important;
            width: 16px !important;
        }
    }
    .pp-tag {
        font-size: 0.72rem;
        padding: 5px 12px;
        border-radius: 999px;
        border: 1px solid var(--pal-sky, #C8D9E6);
        background: var(--pal-white, #ffffff);
        color: var(--pal-teal, #567C8D);
        text-transform: capitalize;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    a.pp-tag:hover {
        background: var(--pal-teal, #567C8D);
        color: #ffffff;
        border-color: var(--pal-teal, #567C8D);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(47, 65, 86, 0.1);
    }

    /* Floating Little Hearts Canvas (Instagram / TikTok Live Engine) */
    .pp-love-area {
        position: relative !important;
        overflow: visible !important;
    }
    .pp-love-canvas {
        position: absolute !important;
        left: 50% !important;
        top: -180px !important;
        transform: translateX(-50%) !important;
        width: 320px !important;
        height: 260px !important;
        pointer-events: none !important;
        z-index: 20 !important;
    }
    .pp-love-btn {
        position: relative !important;
        z-index: 10 !important;
        outline: none !important;
        user-select: none !important;
        -webkit-tap-highlight-color: transparent !important;
        transition: transform 0.1s cubic-bezier(0.175, 0.885, 0.32, 1.275), filter 0.15s ease !important;
    }
    .pp-love-btn:active {
        transform: scale(0.92) !important;
    }

    /* Guest User Auth Modal */
    .pp-guest-modal-overlay {
        position: fixed !important;
        inset: 0 !important;
        background: rgba(15, 23, 42, 0.55) !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
        z-index: 99999 !important;
        display: none;
        align-items: center !important;
        justify-content: center !important;
        padding: 20px !important;
        box-sizing: border-box !important;
        animation: ppGuestFade 0.22s ease-out;
    }
    .pp-guest-modal-overlay.is-open {
        display: flex !important;
    }
    @keyframes ppGuestFade {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .pp-guest-modal-box {
        background: #ffffff !important;
        border-radius: 24px !important;
        padding: 32px 28px !important;
        max-width: 420px !important;
        width: 100% !important;
        position: relative !important;
        box-shadow: 0 24px 50px -12px rgba(15, 23, 42, 0.28) !important;
        text-align: center !important;
        box-sizing: border-box !important;
        border: 1px solid rgba(86, 124, 141, 0.2) !important;
        animation: ppGuestPop 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    @keyframes ppGuestPop {
        from { transform: scale(0.92) translateY(10px); opacity: 0; }
        to { transform: scale(1) translateY(0); opacity: 1; }
    }
    .pp-guest-modal-close {
        position: absolute !important;
        top: 14px !important;
        right: 16px !important;
        background: #f1f5f9 !important;
        border: none !important;
        width: 32px !important;
        height: 32px !important;
        border-radius: 50% !important;
        font-size: 1.25rem !important;
        color: #64748b !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        line-height: 1 !important;
        transition: all 0.2s ease !important;
    }
    .pp-guest-modal-close:hover {
        background: #e2e8f0 !important;
        color: #0f172a !important;
        transform: rotate(90deg);
    }
    .pp-guest-icon-badge {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        margin: 0 auto 16px;
        transition: all 0.3s ease;
    }
    .pp-guest-icon-badge.badge-like {
        background: linear-gradient(135deg, #ffe4e6, #fce7f3);
        color: #e11d48;
        box-shadow: 0 8px 20px rgba(225, 29, 72, 0.2);
    }
    .pp-guest-icon-badge.badge-save {
        background: linear-gradient(135deg, #e0f2fe, #dbeafe);
        color: #0284c7;
        box-shadow: 0 8px 20px rgba(2, 132, 199, 0.2);
    }
    .pp-guest-title {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
        font-size: 1.3rem !important;
        font-weight: 800 !important;
        color: #2F4156 !important;
        margin: 0 0 10px 0 !important;
    }
    .pp-guest-desc {
        font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        font-size: 0.92rem !important;
        line-height: 1.55 !important;
        color: #567C8D !important;
        margin: 0 0 24px 0 !important;
    }
    .pp-guest-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .pp-guest-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px 20px;
        border-radius: 9999px;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        font-size: 0.92rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        border: none;
    }
    .pp-guest-btn:hover {
        transform: translateY(-1px);
    }
    .pp-guest-btn-primary {
        background: #2F4156 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(47, 65, 86, 0.25) !important;
    }
    .pp-guest-btn-primary:hover {
        background: #1e2c3a !important;
        box-shadow: 0 6px 18px rgba(47, 65, 86, 0.35) !important;
    }
    .pp-guest-btn-secondary {
        background: #f1f5f9 !important;
        color: #64748b !important;
    }
    .pp-guest-btn-secondary:hover {
        background: #e2e8f0 !important;
        color: #334155 !important;
    }
    </style>
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-store theme-nogoda page-prompt <?= $ptype === 'solo' ? 'page-prompt-solo' : '' ?>">

<?php $nav_active = 'gallery'; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>
    <div class="pp-wrap">
  
        <div class="pp-layout">

            <!-- Image Column -->
            <div class="pp-img-col <?= ($ptype === 'unreleased' && !$is_unlocked) ? 'blurred' : '' ?> <?= $ptype === 'solo' ? 'pp-solo-img-col' : '' ?>" id="pp-img-col">
                <?php if ($ptype === 'solo' && $solo_before_image !== ''): ?>
                <div class="pp-solo-compare" aria-label="Before and after comparison">
                    <figure class="pp-solo-shot before pp-solo-zoom" role="button" tabindex="0" data-solo-lb aria-label="Preview before image">
                        <span class="pp-solo-label">Before</span>
                        <img loading="eager" draggable="false" src="<?= htmlspecialchars($solo_before_image) ?>" class="pp-prompt-img" alt="Before — <?= htmlspecialchars($p['title']) ?>">
                    </figure>
                    <div class="pp-solo-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
                    <figure class="pp-solo-shot after pp-solo-zoom" role="button" tabindex="0" data-solo-lb aria-label="Preview after image">
                        <span class="pp-solo-label">After</span>
                        <img loading="eager" draggable="false" src="<?= htmlspecialchars($p['image_path']) ?>" class="pp-prompt-img" id="pp-main-img" alt="After — <?= htmlspecialchars($p['title']) ?>">
                    </figure>
                </div>
                <?php else: ?>
                <div class="pp-img-frame">
                    <img loading="lazy" draggable="false" src="<?= htmlspecialchars($p['image_path']) ?>" class="pp-prompt-img" id="pp-main-img" alt="<?= htmlspecialchars($p['title']) ?>">
                    <span class="pp-badge"><?= $tinfo['label'] ?></span>
                </div>
                <?php endif; ?>
                <div class="pp-img-meta">
                    <div class="pp-stats-row">
                        <div class="pp-like-mini">
                            <i class="fa-solid fa-heart"></i>
                            <span id="pp-like-count-mini"><?= (int)$p['likes_count'] ?></span> likes
                        </div>
                        <div class="pp-views-mini" title="<?= (int)$p['view_count'] ?> views">
                            <i class="fa-regular fa-eye"></i>
                            <span id="pp-view-count-mini"><?= (int)$p['view_count'] ?></span> views
                        </div>
                    </div>
                    <?php if (!empty($tags_arr)): ?>
                    <div class="pp-tags">
                        <?php foreach ($tags_arr as $t): ?>
                            <a href="gallery.php?tag=<?= urlencode(strtolower(trim($t))) ?>" class="pp-tag" title="Filter prompts by tag <?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucfirst($t)) ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Column -->
            <div class="pp-info-col">
                <?php if ($total_prompts > 1): ?>
                <div class="pp-multi-badge"><i class="fa-solid fa-layer-group"></i> <?= $total_prompts ?> Prompts Inside!</div>
                <?php endif; ?>
                <h1 class="pp-title"><?= htmlspecialchars($p['title']) ?></h1>

                <!-- -- TASK SECTION (shown when locked) -- -->
                <?php if (!$is_unlocked): ?>

                <div id="pp-task" class="pp-task-card google-anno-skip">
                    <?php if ($ptype === 'secret_code'): ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-lock"></i></div>
                        <h3>Enter Secret Code</h3>
                        <p>All secret codes are listed in one place. Open the code hub and copy your 6-letter code.</p>
                        <a href="all_codes.php#code-<?= (int)$p['id'] ?>" class="pp-reel-btn">
                            <i class="fa-solid fa-code"></i> All Codes Here - Click to Know
                        </a>
                        <div class="pp-input-group">
                            <input type="text" id="pp-code-input" placeholder="6-LETTER CODE" maxlength="6" autocomplete="off" style="letter-spacing:.2em;">
                            <button id="pp-submit-code" class="pp-unlock-btn"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt</button>
                        </div>

                    <?php elseif ($ptype === 'unreleased'): ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-heart"></i></div>
                        <h3>Show Some Love!</h3>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <p>Tap the heart <strong>20 times</strong> to unlock this prompt.</p>
                        <?php else: ?>
                        <p>Tap the heart <strong>90 times</strong> to unlock — or <a href="login.php" style="font-weight:900;color:var(--primary-dark);">login</a> for just 20 taps!</p>
                        <?php endif; ?>
                        <div class="pp-love-area google-anno-skip">
                            <button id="pp-love-btn" class="pp-love-btn"><i class="fa-solid fa-heart"></i></button>
                            <div class="pp-progress-bar"><div class="pp-progress-fill" id="pp-progress-fill" style="width:0%"></div></div>
                            <div class="pp-love-progress"><span id="pp-tap-count">0</span> / <span id="pp-tap-total"><?= isset($_SESSION['user_id']) ? 20 : 90 ?></span></div>
                        </div>

                    <?php elseif ($ptype === 'insta_viral'): ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-calculator"></i></div>
                        <h3>Quick Math Challenge</h3>
                        <p>Solve this to prove you're human and unlock the prompt!</p>
                        <div class="pp-math-q" id="pp-math-q">Loading...</div>
                        <div class="pp-input-group">
                            <input type="number" id="pp-math-input" placeholder="Your Answer" style="font-size:1.2rem;">
                            <button id="pp-submit-math" class="pp-unlock-btn"><i class="fa-solid fa-check"></i> Unlock Prompt</button>
                        </div>

                    <?php elseif ($ptype === 'already_uploaded'): ?>
                        <div class="pp-task-icon"><i class="fa-brands fa-instagram" style="background:linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i></div>
                        <h3>Already on Instagram!</h3>
                        <p>This prompt has been shared on our Instagram. Tap the heart <strong>9 times</strong> to unlock it!</p>
                        <div class="pp-love-area google-anno-skip">
                            <button id="pp-love-btn-au" class="pp-love-btn"><i class="fa-solid fa-heart"></i></button>
                            <div class="pp-progress-bar"><div class="pp-progress-fill" id="pp-progress-fill-au" style="width:0%"></div></div>
                            <div class="pp-love-progress"><span id="pp-tap-count-au">0</span> / 9</div>
                        </div>

                    <?php elseif ($ptype === 'direct' || $ptype === 'solo'): ?>
                        <?php $req_taps = (int)($p['unlock_code'] ?: 9); ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-heart"></i></div>
                        <h3>Show Some Love!</h3>
                        <p>Tap the heart <strong><?= $req_taps ?> times</strong> to unlock this prompt!</p>
                        <div class="pp-love-area google-anno-skip">
                            <button id="pp-love-btn-dir" class="pp-love-btn"><i class="fa-solid fa-heart"></i></button>
                            <div class="pp-progress-bar"><div class="pp-progress-fill" id="pp-progress-fill-dir" style="width:0%"></div></div>
                            <div class="pp-love-progress"><span id="pp-tap-count-dir">0</span> / <?= $req_taps ?></div>
                            <script>const DIR_REQ_TAPS = <?= $req_taps ?>;</script>
                        </div>
                    <?php endif; ?>

                    <div id="pp-task-error" class="pp-error" style="display:none;"></div>
                </div>
                <?php endif; ?>

                <!-- -- CONTENT SECTION (shown when unlocked) -- -->
                <div id="pp-content" class="pp-content-section" <?= !$is_unlocked ? 'style="display:none;"' : '' ?>>
                    <div class="pp-prompt-head">
                        <span class="pp-prompt-label"><i class="fa-solid fa-scroll"></i> THE PROMPT:</span>
                        <?php 
                        if (!empty($p['best_works_in'])) {
                            require_once __DIR__ . '/includes/bwi_helper.php';
                            $bwi_info = parse_bwi_display($p['best_works_in']);
                            if ($bwi_info):
                        ?>
                        <span class="pp-bwi-badge <?= htmlspecialchars($bwi_info['badge_class']) ?>">
                            <i class="<?= htmlspecialchars($bwi_info['icon']) ?>"></i> <?= htmlspecialchars($bwi_info['label']) ?>
                        </span>
                        <?php 
                            endif;
                        } 
                        ?>
                    </div>

                    <div class="pp-code-block">
                        <div class="pp-code-header">
                            <div class="pp-code-header-dots"><span style="background:#ff5f57"></span><span style="background:#febc2e"></span><span style="background:#28c840"></span></div>
                            <span>PROMPT.txt</span>
                            <span style="opacity:.6;font-size:.7rem;" id="pp-word-count"><?= $is_unlocked ? str_word_count($p['prompt_text']) : 0 ?> words</span>
                        </div>
                        <div class="pp-prompt-text" id="pp-prompt-text"><?= $is_unlocked ? htmlspecialchars($p['prompt_text']) : '' ?></div>
                    </div>

                    <div class="pp-actions google-anno-skip">
                        <button type="button" class="pp-btn pp-copy-btn" id="pp-copy-btn"><i class="fa-solid fa-copy"></i> COPY</button>
                        <button type="button" class="pp-btn pp-save-btn" id="pp-save-btn" data-prompt-id="<?= $id ?>" data-saved="<?= $p['is_saved'] ? 'true' : 'false' ?>">
                            <i class="fa-solid fa-bookmark"></i> <span id="pp-save-label"><?= $p['is_saved'] ? 'SAVED' : 'SAVE' ?></span>
                        </button>
                        <button class="pp-like-btn <?= $p['is_liked'] ? 'is-liked' : '' ?>" id="pp-like-btn" data-prompt-id="<?= $id ?>">
                            <i class="fa-solid fa-heart <?= $p['is_liked'] ? 'liked-heart' : '' ?>" id="pp-like-icon"></i>
                            <span id="pp-like-count"><?= (int)$p['likes_count'] ?></span>
                        </button>
                        <button type="button" class="pp-btn pp-share-btn" id="pp-share-btn"><i class="fa-solid fa-share-nodes"></i> SHARE</button>
                    </div>

                    <?php if (!empty($asset_images) || !empty($p['asset_title'])): ?>
                    <div class="pp-assets">
                        <div class="pp-assets-title"><i class="fa-solid fa-paperclip"></i> <?= htmlspecialchars($p['asset_title'] ?? 'Assets') ?></div>
                        <div class="pp-assets-grid">
                            <?php foreach ($asset_images as $i => $ai): ?>
                            <div class="pp-assets-item">
                                <img loading="lazy" src="<?= htmlspecialchars($ai) ?>" alt="Asset <?= $i+1 ?>">
                                <a href="<?= htmlspecialchars($ai) ?>" class="pp-btn pp-download-btn" download target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-download"></i> Download</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php foreach ($extra_prompts_arr as $ep_i => $ep): ?>
                    <div class="pp-extra-section" id="pp-extra-<?= $ep_i ?>">
                        <div class="pp-extra-num"><i class="fa-solid fa-layer-group"></i> Prompt <?= $ep_i + 2 ?></div>
                        <div class="pp-extra-layout">
                            <?php if (!empty($ep['image_path'])): ?>
                            <div class="pp-extra-img-col">
                                <img loading="lazy" src="<?= htmlspecialchars($ep['image_path']) ?>" class="pp-extra-img" alt="Prompt <?= $ep_i + 2 ?>">
                            </div>
                            <?php endif; ?>
                            <div class="pp-extra-info">
                                <?php if (!empty($ep['title'])): ?>
                                <h2 class="pp-extra-title"><?= htmlspecialchars($ep['title']) ?></h2>
                                <?php endif; ?>
                                <div class="pp-code-block">
                                    <div class="pp-code-header">
                                        <div class="pp-code-header-dots"><span style="background:#ff5f57"></span><span style="background:#febc2e"></span><span style="background:#28c840"></span></div>
                                        <span>PROMPT <?= $ep_i + 2 ?>.txt</span>
                                    </div>
                                    <div class="pp-prompt-text" id="pp-extra-text-<?= $ep_i ?>"><?= $is_unlocked ? htmlspecialchars($ep['prompt_text']) : '' ?></div>
                                </div>
                                <div style="margin-top:12px;">
                                    <button class="pp-btn pp-copy-btn" onclick="copyExtra(<?= $ep_i ?>, this)"><i class="fa-solid fa-copy"></i> COPY</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if ($ptype === 'solo' && !empty($solo_examples)): ?>
        <section class="pp-solo-examples" aria-labelledby="solo-examples-title">
            <div class="pp-solo-examples-head">
                <p class="hero-label">More Transformations</p>
                <h2 id="solo-examples-title">Before &amp; After <em>Examples</em></h2>
                <p>Tap any photo to open a full preview. Swipe or use arrows to move between shots.</p>
            </div>
            <div class="pp-solo-examples-grid">
                <?php foreach (array_slice($solo_examples, 0, 5) as $example_i => $example): ?>
                    <?php if (empty($example['before']) || empty($example['after'])) continue; ?>
                    <article class="pp-solo-example-card">
                        <div class="pp-solo-example-num">Example <?= $example_i + 1 ?> · tap to preview</div>
                        <div class="pp-solo-example-pair">
                            <figure class="pp-solo-zoom" role="button" tabindex="0" data-solo-lb aria-label="Preview example <?= $example_i + 1 ?> before">
                                <span>Before</span>
                                <img loading="lazy" src="<?= htmlspecialchars($example['before']) ?>" alt="Example <?= $example_i + 1 ?> before">
                            </figure>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            <figure class="pp-solo-zoom" role="button" tabindex="0" data-solo-lb aria-label="Preview example <?= $example_i + 1 ?> after">
                                <span>After</span>
                                <img loading="lazy" src="<?= htmlspecialchars($example['after']) ?>" alt="Example <?= $example_i + 1 ?> after result">
                            </figure>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($how_to_use_steps)): ?>
        <section class="pp-how-to-use" aria-label="How to use this prompt">
            <h2><i class="fa-solid fa-list-check" style="font-size:0.85rem;"></i> How to use this prompt</h2>
            <ol class="pp-how-steps">
                <?php foreach ($how_to_use_steps as $step_i => $step_item): 
                    $step_text = $step_item['text'] ?? '';
                    $step_imgs = !empty($step_item['images']) && is_array($step_item['images']) ? $step_item['images'] : [];
                ?>
                <li class="pp-how-step-item">
                    <span class="pp-how-step-num"><?= $step_i + 1 ?></span>
                    <div class="pp-how-step-content">
                        <?php if ($step_text !== ''): ?>
                        <div class="pp-how-step-text"><?= nl2br(htmlspecialchars($step_text)) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($step_imgs)): ?>
                        <div class="pp-step-pics-wrap" aria-label="Step sample pictures">
                            <?php foreach ($step_imgs as $img_idx => $s_img): 
                                $img_url = (preg_match('#^https?://#i', $s_img)) ? $s_img : ltrim($s_img, '/');
                            ?>
                                <?php if ($img_idx > 0): ?>
                                    <span class="pp-step-pic-plus" aria-hidden="true">+</span>
                                <?php endif; ?>
                                <a href="<?= htmlspecialchars($img_url) ?>" class="pp-step-pic-thumb" target="_blank" rel="noopener" title="Click to view sample picture" onclick="openStepPicModal(event, '<?= htmlspecialchars($img_url, ENT_QUOTES) ?>')">
                                    <img src="<?= htmlspecialchars($img_url) ?>" alt="Step <?= $step_i + 1 ?> sample <?= $img_idx + 1 ?>" loading="lazy">
                                    <span class="pp-step-pic-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>
        <?php endif; ?>

        <?php if ($about_prompt_raw !== '' || $meta_desc_raw !== '' || !empty($pp_kw_list)): ?>
        <section class="pp-about" aria-label="About this prompt">
            <?php if ($about_prompt_raw !== ''): ?>
            <h2>About this prompt <span class="pp-about-badge">(This is not the prompt)</span></h2>
            <p class="pp-about-hint"><em>(Upar hearts ko click karne ke baad aayega prompt, naaki ye about wala prompt hai... buddy!)</em></p>
            <p class="pp-about-body"><?= nl2br(htmlspecialchars($about_prompt_raw)) ?></p>
            <?php endif; ?>
            <?php if ($meta_desc_raw !== ''): ?>
            <p class="pp-about-seo"><?= nl2br(htmlspecialchars($meta_desc_raw)) ?></p>
            <?php endif; ?>
            <?php if (!empty($pp_kw_list)): ?>
            <div class="pp-about-kw" aria-label="Keywords">
                <?php foreach ($pp_kw_list as $kw): ?>
                <a href="gallery.php?search=<?= urlencode($kw) ?>" class="pp-kw-chip" title="Explore prompts for <?= htmlspecialchars($kw, ENT_QUOTES) ?>"><?= htmlspecialchars($kw) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Related Prompts -->
        <?php if (!empty($related)): ?>
        <div class="pp-related">
            <h2>More <?= htmlspecialchars($tinfo['label']) ?> Prompts</h2>
            <div class="pp-rel-grid">
                <?php foreach ($related as $index => $r): ?>
                    <?php render_prompt_card($r, $index); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    <?php include_once 'includes/card_skeleton_assets.php'; ?>

    <script>
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    const promptId = <?= $id ?>;
    const ptype    = '<?= $ptype ?>';

    // -- Error helper --
    function showError(msg) {
        const el = document.getElementById('pp-task-error');
        if (!el) return;
        el.textContent = msg;
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 4000);
    }

    // -- Reveal content after unlock --
    function revealPrompt(text, extraPrompts) {
        const task = document.getElementById('pp-task');
        const content = document.getElementById('pp-content');
        const imgCol = document.getElementById('pp-img-col');
        if (task) task.style.display = 'none';
        if (content) { 
            content.style.display = 'flex'; 
            document.getElementById('pp-prompt-text').textContent = text; 
            const wcEl = document.getElementById('pp-word-count');
            if (wcEl) wcEl.textContent = text.trim().split(/\s+/).filter(w => w.length > 0).length + ' words';
        }
        if (imgCol) imgCol.classList.remove('blurred');
        const mainImg = document.getElementById('pp-main-img');
        if (mainImg) mainImg.style.filter = '';
        if (extraPrompts && Array.isArray(extraPrompts)) {
            extraPrompts.forEach(function(ep, i) {
                const el = document.getElementById('pp-extra-text-' + i);
                if (el) el.textContent = ep.prompt_text || '';
            });
        }
        content.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function copyToClipboard(text) {
        if (!text || !text.trim()) return Promise.reject(new Error('empty'));
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).catch(function() {
                return copyToClipboardFallback(text);
            });
        }
        return copyToClipboardFallback(text);
    }
    function copyToClipboardFallback(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
        return Promise.resolve();
    }

    function copyExtra(idx, btn) {
        const el = document.getElementById('pp-extra-text-' + idx);
        if (!el || !el.textContent.trim()) return;
        copyToClipboard(el.textContent.trim()).then(function() {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
            setTimeout(() => btn.innerHTML = '<i class="fa-solid fa-copy"></i> COPY', 2000);
        });
    }

    // -- SECRET CODE --
    const submitCode = document.getElementById('pp-submit-code');
    if (submitCode) {
        submitCode.addEventListener('click', async function() {
            const code = document.getElementById('pp-code-input').value.trim();
            if (code.length < 4) { showError('Please enter the code!'); return; }
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
            const fd = new FormData();
            fd.append('action', 'verify'); fd.append('prompt_id', promptId); fd.append('code', code);
            const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
            else { showError(res.message || 'Wrong code! Watch the reel to get it.'); this.disabled = false; this.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt'; }
        });
    }

    // ============================================================
    // HARDWARE-ACCELERATED 60FPS CANVAS FLOATING HEARTS ENGINE
    // ============================================================
    const heartSvgPath = 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z';
    let heartPath2D = null;
    try { heartPath2D = new Path2D(heartSvgPath); } catch (e) {}

    class HeartCanvasManager {
        constructor(area) {
            this.area = area;
            this.canvas = area.querySelector('.pp-love-canvas');
            if (!this.canvas) {
                this.canvas = document.createElement('canvas');
                this.canvas.className = 'pp-love-canvas';
                this.area.prepend(this.canvas);
            }
            this.ctx = this.canvas.getContext('2d');
            this.dpr = Math.min(window.devicePixelRatio || 1, 2);
            this.width = 320;
            this.height = 260;
            this.canvas.width = this.width * this.dpr;
            this.canvas.height = this.height * this.dpr;
            this.ctx.scale(this.dpr, this.dpr);
            this.particles = [];
            this.animId = null;
            this.lastTime = 0;
            this.colors = ['#FF3366', '#FF5388', '#F5709D', '#FF7597', '#FF85A1', '#C084FC', '#E879F9', '#FF4D6D', '#FB7185', '#F43F5E'];
            this.celebrateColors = ['#FF3366', '#FF5388', '#F5709D', '#11FFC9', '#FFD166', '#C084FC', '#38BDF8', '#FB7185'];
        }

        spawn(btn, count = 4) {
            const btnRect = btn ? btn.getBoundingClientRect() : null;
            const canvasRect = this.canvas.getBoundingClientRect();
            const originX = btnRect ? (btnRect.left + (btnRect.width / 2) - canvasRect.left) : (this.width / 2);
            const originY = btnRect ? (btnRect.top + (btnRect.height / 2) - canvasRect.top) : (this.height - 50);

            for (let i = 0; i < count; i++) {
                const color = this.colors[Math.floor(Math.random() * this.colors.length)];
                const size = 16 + Math.random() * 12;
                const p = {
                    x: originX + (Math.random() - 0.5) * 12,
                    y: originY + (Math.random() - 0.5) * 6,
                    size: size,
                    color: color,
                    vx: (Math.random() - 0.5) * 1.5,
                    vy: -(2.6 + Math.random() * 2.0),
                    swayFreq: 0.04 + Math.random() * 0.03,
                    swayAmp: 1.2 + Math.random() * 1.4,
                    swayPhase: Math.random() * Math.PI * 2,
                    rotation: (Math.random() - 0.5) * 0.4,
                    rotSpeed: (Math.random() - 0.5) * 0.02,
                    scale: 0.15,
                    maxScale: 1.0,
                    opacity: 1.0,
                    age: 0
                };
                this.particles.push(p);
            }
            if (!this.animId) {
                this.lastTime = performance.now();
                this.loop();
            }
        }

        celebrate(btn) {
            const btnRect = btn ? btn.getBoundingClientRect() : null;
            const canvasRect = this.canvas.getBoundingClientRect();
            const originX = btnRect ? (btnRect.left + (btnRect.width / 2) - canvasRect.left) : (this.width / 2);
            const originY = btnRect ? (btnRect.top + (btnRect.height / 2) - canvasRect.top) : (this.height - 50);
            const count = 30;

            for (let i = 0; i < count; i++) {
                const angle = (i / count) * Math.PI * 2 + (Math.random() - 0.5) * 0.25;
                const speed = 3.2 + Math.random() * 3.8;
                const color = this.celebrateColors[i % this.celebrateColors.length];
                const size = 16 + Math.random() * 14;
                const p = {
                    x: originX,
                    y: originY,
                    size: size,
                    color: color,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 1.2,
                    swayFreq: 0,
                    swayAmp: 0,
                    swayPhase: 0,
                    rotation: (Math.random() - 0.5) * 0.6,
                    rotSpeed: (Math.random() - 0.5) * 0.04,
                    scale: 0.3,
                    maxScale: 1.2,
                    opacity: 1.0,
                    isBurst: true,
                    age: 0
                };
                this.particles.push(p);
            }
            if (!this.animId) {
                this.lastTime = performance.now();
                this.loop();
            }
        }

        loop() {
            const now = performance.now();
            const dt = Math.min((now - this.lastTime) / 16.66, 2.5);
            this.lastTime = now;

            this.ctx.clearRect(0, 0, this.width, this.height);

            for (let i = this.particles.length - 1; i >= 0; i--) {
                const p = this.particles[i];
                p.age += dt;

                if (p.isBurst) {
                    p.x += p.vx * dt;
                    p.y += p.vy * dt;
                    p.vx *= 0.95;
                    p.vy = (p.vy * 0.95) + 0.08 * dt;
                    p.rotation += p.rotSpeed * dt;
                    if (p.scale < p.maxScale) p.scale += 0.08 * dt;
                    p.opacity -= 0.018 * dt;
                } else {
                    p.y += p.vy * dt;
                    p.vy *= Math.pow(0.992, dt);
                    p.x += (p.vx + Math.sin(p.age * p.swayFreq + p.swayPhase) * p.swayAmp) * dt;
                    p.rotation += p.rotSpeed * dt;
                    if (p.scale < p.maxScale) p.scale = Math.min(p.maxScale, p.scale + 0.15 * dt);

                    if (p.y < 80) {
                        p.opacity -= 0.025 * dt;
                    }
                }

                if (p.opacity <= 0.01 || p.y < -30 || p.x < -30 || p.x > this.width + 30) {
                    this.particles.splice(i, 1);
                    continue;
                }

                this.ctx.save();
                this.ctx.translate(p.x, p.y);
                this.ctx.rotate(p.rotation);
                const s = (p.size / 24) * p.scale;
                this.ctx.scale(s, s);
                this.ctx.translate(-12, -12);
                this.ctx.globalAlpha = Math.max(0, Math.min(1, p.opacity));
                this.ctx.fillStyle = p.color;
                this.ctx.shadowColor = 'rgba(245, 112, 157, 0.45)';
                this.ctx.shadowBlur = 6;

                if (heartPath2D) {
                    this.ctx.fill(heartPath2D);
                } else {
                    this.ctx.beginPath();
                    this.ctx.moveTo(12, 21.35);
                    this.ctx.bezierCurveTo(10.55, 20.03, 2, 12.28, 2, 8.5);
                    this.ctx.bezierCurveTo(2, 5.42, 4.42, 3, 7.5, 3);
                    this.ctx.bezierCurveTo(9.24, 3, 10.91, 3.81, 12, 5.09);
                    this.ctx.bezierCurveTo(13.09, 3.81, 14.76, 3, 16.5, 3);
                    this.ctx.bezierCurveTo(19.58, 3, 22, 5.42, 22, 8.5);
                    this.ctx.bezierCurveTo(22, 12.28, 13.45, 20.03, 12, 21.35);
                    this.ctx.closePath();
                    this.ctx.fill();
                }
                this.ctx.restore();
            }

            if (this.particles.length > 0) {
                this.animId = requestAnimationFrame(() => this.loop());
            } else {
                this.ctx.clearRect(0, 0, this.width, this.height);
                this.animId = null;
            }
        }
    }

    const heartManagers = new WeakMap();
    function getHeartManager(area) {
        if (!area) return null;
        if (!heartManagers.has(area)) {
            heartManagers.set(area, new HeartCanvasManager(area));
        }
        return heartManagers.get(area);
    }

    function spawnFloatingHearts(btn, count = 4) {
        if (!btn) return;
        const area = btn.closest('.pp-love-area') || btn.parentElement;
        if (!area) return;

        btn.style.transform = 'scale(' + (1.28 + Math.random() * 0.08) + ') rotate(' + ((Math.random() - 0.5) * 6) + 'deg)';
        btn.style.filter = 'drop-shadow(0 0 16px rgba(245, 112, 157, 0.8))';
        clearTimeout(btn._heartTimer);
        btn._heartTimer = setTimeout(() => {
            btn.style.transform = '';
            btn.style.filter = '';
        }, 110);

        const mgr = getHeartManager(area);
        if (mgr) mgr.spawn(btn, count);
    }

    function spawnCelebrationHearts(btn) {
        if (!btn) return;
        const area = btn.closest('.pp-love-area') || btn.parentElement;
        if (!area) return;

        btn.style.transform = 'scale(1.45) rotate(0deg)';
        btn.style.filter = 'drop-shadow(0 0 24px rgba(245, 112, 157, 0.95))';
        setTimeout(() => {
            btn.style.transform = '';
            btn.style.filter = '';
        }, 280);

        const mgr = getHeartManager(area);
        if (mgr) mgr.celebrate(btn);
    }

    // -- UNRELEASED (20 taps logged in / 90 taps guest) --
    const loveBtn = document.getElementById('pp-love-btn');
    if (loveBtn) {
        let tapCount = 0;
        const TAPS = (typeof isLoggedIn !== 'undefined' && isLoggedIn) ? 20 : 90;
        document.getElementById('pp-tap-total').textContent = TAPS;
        loveBtn.addEventListener('click', function() {
            if (tapCount === 0) {
                const fd = new FormData(); fd.append('action', 'init_love'); fd.append('prompt_id', promptId);
                fetch('unlock.php', { method: 'POST', body: fd });
            }
            tapCount++;
            document.getElementById('pp-tap-count').textContent = tapCount;
            document.getElementById('pp-progress-fill').style.width = (tapCount / TAPS * 100) + '%';
            spawnFloatingHearts(this, 4);
            if (tapCount >= TAPS) {
                spawnCelebrationHearts(this);
                this.disabled = true;
                const fd = new FormData(); fd.append('action', 'unreleased'); fd.append('prompt_id', promptId);
                fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
                    if (res.success) { 
                        setTimeout(() => revealPrompt(res.prompt_text, res.extra_prompts), 450); 
                    } else { 
                        tapCount = 0; 
                        document.getElementById('pp-tap-count').textContent = '0'; 
                        document.getElementById('pp-progress-fill').style.width = '0%'; 
                        this.disabled = false; 
                        showError(res.message); 
                    }
                });
            }
        });
    }

    // -- ALREADY UPLOADED (9 heart taps) --
    const loveBtnAu = document.getElementById('pp-love-btn-au');
    if (loveBtnAu) {
        let tapCountAu = 0;
        const TAPS_AU = 9;
        loveBtnAu.addEventListener('click', function() {
            tapCountAu++;
            document.getElementById('pp-tap-count-au').textContent = tapCountAu;
            document.getElementById('pp-progress-fill-au').style.width = (tapCountAu / TAPS_AU * 100) + '%';
            spawnFloatingHearts(this, 4);
            if (tapCountAu >= TAPS_AU) {
                spawnCelebrationHearts(this);
                this.disabled = true;
                const fd = new FormData(); fd.append('action', 'already_uploaded'); fd.append('prompt_id', promptId);
                fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
                    if (res.success) { 
                        setTimeout(() => revealPrompt(res.prompt_text, res.extra_prompts), 450); 
                    } else { 
                        tapCountAu = 0; 
                        document.getElementById('pp-tap-count-au').textContent = '0'; 
                        document.getElementById('pp-progress-fill-au').style.width = '0%'; 
                        this.disabled = false; 
                        showError(res.message); 
                    }
                });
            }
        });
    }

    // -- DIRECT PROMPT (configurable heart taps) --
    const loveBtnDir = document.getElementById('pp-love-btn-dir');
    if (loveBtnDir) {
        let tapCountDir = 0;
        const TAPS_DIR = typeof DIR_REQ_TAPS !== 'undefined' ? DIR_REQ_TAPS : 9;
        loveBtnDir.addEventListener('click', function() {
            tapCountDir++;
            document.getElementById('pp-tap-count-dir').textContent = tapCountDir;
            document.getElementById('pp-progress-fill-dir').style.width = (tapCountDir / TAPS_DIR * 100) + '%';
            spawnFloatingHearts(this, 4);
            if (tapCountDir >= TAPS_DIR) {
                spawnCelebrationHearts(this);
                this.disabled = true;
                const reqAction = ptype === 'solo' ? 'solo' : 'direct';
                const fd = new FormData(); fd.append('action', reqAction); fd.append('prompt_id', promptId);
                fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
                    if (res.success) { 
                        setTimeout(() => revealPrompt(res.prompt_text, res.extra_prompts), 450); 
                    } else { 
                        tapCountDir = 0; 
                        document.getElementById('pp-tap-count-dir').textContent = '0'; 
                        document.getElementById('pp-progress-fill-dir').style.width = '0%'; 
                        this.disabled = false; 
                        showError(res.message); 
                    }
                });
            }
        });
    }

    // -- INSTA VIRAL (math) --
    const mathQ = document.getElementById('pp-math-q');
    if (mathQ) {
        (async function initMath() {
            const fd = new FormData(); fd.append('action', 'get_challenge'); fd.append('prompt_id', promptId);
            const d = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
            mathQ.textContent = d.n1 + ' + ' + d.n2 + ' + ' + d.n3 + ' + ' + d.n4 + ' = ?';
        })();
        document.getElementById('pp-submit-math').addEventListener('click', async function() {
            const ans = document.getElementById('pp-math-input').value;
            if (!ans) { showError('Enter your answer!'); return; }
            this.disabled = true;
            const fd = new FormData(); fd.append('action', 'insta_viral'); fd.append('prompt_id', promptId); fd.append('user_answer', ans);
            const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
            else { showError(res.message || 'Wrong answer! Try again.'); this.disabled = false; mathQ.textContent = 'Loading...'; initMath(); }
        });
    }


    // -- COPY (with fallback for non-HTTPS / arigato.local) --
    const copyBtn = document.getElementById('pp-copy-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const text = document.getElementById('pp-prompt-text').textContent;
            copyToClipboard(text).then(() => {
                this.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
                setTimeout(() => this.innerHTML = '<i class="fa-solid fa-copy"></i> COPY', 2000);
                const fd = new FormData(); fd.append('action','copy'); fd.append('prompt_id', promptId);
                fetch('track.php', {method:'POST', body:fd});
            }).catch(function() { showError('Could not copy — try selecting the text manually.'); });
        });
    }

    // -- SAVE --
    const saveBtn = document.getElementById('pp-save-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', async function() {
            <?php if (!isset($_SESSION['user_id'])): ?>
            showGuestAuthModal('save');
            return;
            <?php endif; ?>
            const isSaved = this.dataset.saved === 'true';
            const action  = isSaved ? 'unsave' : 'save';
            const fd = new FormData(); fd.append('prompt_id', promptId); fd.append('action', action);
            const res = await fetch('save_prompt.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                this.dataset.saved = res.saved ? 'true' : 'false';
                document.getElementById('pp-save-label').textContent = res.saved ? 'SAVED' : 'SAVE';
                this.style.background = res.saved ? 'var(--primary-color)' : 'var(--secondary-color)';
            }
        });
    }

    // -- SHARE --
    const shareBtn = document.getElementById('pp-share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', async function() {
            const url = window.location.href;
            const title = <?= json_encode($p['title']) ?>;
            if (navigator.share) {
                try { await navigator.share({ title: title + ' — Arigato Devan', url: url }); return; } catch(e) {}
            }
            navigator.clipboard.writeText(url).then(() => {
                this.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
                setTimeout(() => this.innerHTML = '<i class="fa-solid fa-share-nodes"></i> SHARE', 2000);
            });
        });
    }

    // -- LIKE (Main Prompt) --
    const likeBtn = document.getElementById('pp-like-btn');
    if (likeBtn) {
        let isLiking = false;
        likeBtn.addEventListener('click', async function() {
            <?php if (!isset($_SESSION['user_id'])): ?>
            showGuestAuthModal('like');
            return;
            <?php endif; ?>
            if (isLiking) return;
            isLiking = true;
            try {
                const fd = new FormData(); fd.append('prompt_id', promptId);
                const res = await fetch('like.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) {
                    const isLiked = res.action === 'liked';
                    this.classList.toggle('is-liked', isLiked);
                    document.getElementById('pp-like-icon').classList.toggle('liked-heart', isLiked);
                    document.getElementById('pp-like-count').textContent = res.likes_count;
                    document.getElementById('pp-like-count-mini').textContent = res.likes_count;
                    if (isLiked) {
                        spawnFloatingHearts(this, 5);
                    }
                }
            } catch (err) {
                console.error('Like error:', err);
            } finally {
                isLiking = false;
            }
        });
    }

    // -- CARD LIKE DELEGATION FOR RELATED PROMPTS --
    document.addEventListener('click', async function(e) {
        const likeDisplay = e.target.closest('.card-like-display');
        if (!likeDisplay) return;
        e.preventDefault();
        e.stopPropagation();

        const pid = likeDisplay.dataset.promptId;
        if (!pid) return;

        <?php if (!isset($_SESSION['user_id'])): ?>
        showGuestAuthModal('like');
        return;
        <?php endif; ?>

        const heartIcon = likeDisplay.querySelector('.fa-heart');
        const countSpan = likeDisplay.querySelector('.like-count');
        const isCurrentlyLiked = likeDisplay.dataset.liked === 'true';

        // Optimistic UI
        likeDisplay.dataset.liked = isCurrentlyLiked ? 'false' : 'true';
        if (heartIcon) heartIcon.classList.toggle('liked-heart', !isCurrentlyLiked);
        if (countSpan) {
            const currentCount = parseInt(countSpan.textContent, 10) || 0;
            countSpan.textContent = isCurrentlyLiked ? Math.max(0, currentCount - 1) : currentCount + 1;
        }

        const fd = new FormData();
        fd.append('prompt_id', pid);

        try {
            const res = await fetch('like.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                const isLiked = res.action === 'liked';
                likeDisplay.dataset.liked = isLiked ? 'true' : 'false';
                if (heartIcon) heartIcon.classList.toggle('liked-heart', isLiked);
                if (countSpan) countSpan.textContent = res.likes_count;
            }
        } catch(err) {
            console.error('Like error:', err);
        }
    });
    </script>

    <?php if ($ptype === 'solo'): ?>
    <div class="pp-solo-lb" id="ppSoloLb" aria-hidden="true" role="dialog" aria-label="Image preview">
        <div class="pp-solo-lb-backdrop" aria-hidden="true"></div>
        <button type="button" class="pp-solo-lb-close" aria-label="Close preview"><i class="fa-solid fa-xmark"></i></button>
        <button type="button" class="pp-solo-lb-nav pp-solo-lb-prev" aria-label="Previous image"><i class="fa-solid fa-chevron-left"></i></button>
        <div class="pp-solo-lb-stage">
            <div class="pp-solo-lb-frame">
                <img class="pp-solo-lb-img" src="" alt="">
                <p class="pp-solo-lb-caption" aria-live="polite"></p>
            </div>
        </div>
        <button type="button" class="pp-solo-lb-nav pp-solo-lb-next" aria-label="Next image"><i class="fa-solid fa-chevron-right"></i></button>
        <p class="pp-solo-lb-counter" aria-live="polite"></p>
    </div>
    <script>
    (function () {
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-solo-lb]'));
        var lb = document.getElementById('ppSoloLb');
        if (!items.length || !lb) return;
        if (lb.parentNode !== document.body) document.body.appendChild(lb);

        var lbImg = lb.querySelector('.pp-solo-lb-img');
        var lbCaption = lb.querySelector('.pp-solo-lb-caption');
        var lbCounter = lb.querySelector('.pp-solo-lb-counter');
        var lbPrev = lb.querySelector('.pp-solo-lb-prev');
        var lbNext = lb.querySelector('.pp-solo-lb-next');
        var current = 0;
        var touchStartX = 0, touchStartY = 0, touchActive = false;

        function getSrc(el) {
            var img = el.querySelector('img');
            return img ? (img.currentSrc || img.src || '') : '';
        }
        function getLabel(el) {
            var badge = el.querySelector('.pp-solo-label, span');
            var alt = el.querySelector('img');
            if (badge && badge.textContent.trim()) return badge.textContent.trim();
            return alt ? (alt.alt || 'Preview') : 'Preview';
        }
        function show(i) {
            current = (i + items.length) % items.length;
            var el = items[current];
            var label = getLabel(el);
            lbImg.src = getSrc(el);
            lbImg.alt = label;
            if (lbCaption) {
                lbCaption.textContent = label;
                lbCaption.classList.toggle('is-before', /before/i.test(label));
                lbCaption.classList.toggle('is-after', /after/i.test(label));
            }
            if (lbCounter) lbCounter.textContent = (current + 1) + ' / ' + items.length;
            if (lbPrev) lbPrev.disabled = items.length <= 1;
            if (lbNext) lbNext.disabled = items.length <= 1;
        }
        function open(i) {
            show(i);
            lb.classList.add('is-open');
            lb.setAttribute('aria-hidden', 'false');
            document.body.classList.add('pp-solo-lb-open');
        }
        function close() {
            lb.classList.remove('is-open');
            lb.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('pp-solo-lb-open');
            lbImg.removeAttribute('src');
        }
        function prev() { if (items.length > 1) show(current - 1); }
        function next() { if (items.length > 1) show(current + 1); }

        items.forEach(function (el, i) {
            el.addEventListener('click', function () { open(i); });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    open(i);
                }
            });
        });

        lb.querySelector('.pp-solo-lb-close').addEventListener('click', close);
        lb.querySelector('.pp-solo-lb-backdrop').addEventListener('click', close);
        if (lbPrev) lbPrev.addEventListener('click', function (e) { e.stopPropagation(); prev(); });
        if (lbNext) lbNext.addEventListener('click', function (e) { e.stopPropagation(); next(); });

        document.addEventListener('keydown', function (e) {
            if (!lb.classList.contains('is-open')) return;
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') prev();
            if (e.key === 'ArrowRight') next();
        });

        lb.addEventListener('touchstart', function (e) {
            if (!lb.classList.contains('is-open') || !e.touches.length) return;
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            touchActive = true;
        }, { passive: true });
        lb.addEventListener('touchend', function (e) {
            if (!touchActive) return;
            touchActive = false;
            var t = e.changedTouches[0];
            if (!t) return;
            var dx = t.clientX - touchStartX;
            var dy = t.clientY - touchStartY;
            if (Math.abs(dx) < 48 || Math.abs(dx) < Math.abs(dy)) return;
            if (dx < 0) next(); else prev();
        }, { passive: true });
    })();
    </script>
    <?php endif; ?>
    <!-- Step Picture Lightbox Modal -->
    <div id="stepPicModal" class="pp-step-modal" aria-hidden="true" onclick="closeStepPicModal()">
        <div class="pp-step-modal-box" onclick="event.stopPropagation()">
            <button type="button" class="pp-step-modal-close" onclick="closeStepPicModal()" aria-label="Close picture">&times;</button>
            <img id="stepPicModalImg" src="" alt="Sample step picture">
        </div>
    </div>
    <!-- Guest User Auth Modal for Like & Save -->
    <div id="pp-guest-modal" class="pp-guest-modal-overlay google-anno-skip" style="display:none;" aria-hidden="true" role="dialog" aria-modal="true" onclick="closeGuestModal()">
        <div class="pp-guest-modal-box" onclick="event.stopPropagation()">
            <button type="button" class="pp-guest-modal-close" onclick="closeGuestModal()" aria-label="Close dialog">&times;</button>
            <div class="pp-guest-icon-badge" id="pp-guest-icon-badge">
                <i class="fa-solid fa-heart"></i>
            </div>
            <h3 class="pp-guest-title" id="pp-guest-title">Show Some Love! ❤️✨</h3>
            <p class="pp-guest-desc" id="pp-guest-desc">
                Love this prompt? Sign in to like and support prompt creators, and sync your favorite prompts across all your devices!
            </p>
            <div class="pp-guest-actions">
                <a href="login.php?redirect_to=<?= urlencode('prompt.php?id=' . $id) ?>" class="pp-guest-btn pp-guest-btn-primary" id="pp-guest-login-btn">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>Sign In to Like ✨</span>
                </a>
                <button type="button" class="pp-guest-btn pp-guest-btn-secondary" onclick="closeGuestModal()">
                    Maybe Later / Keep Exploring
                </button>
            </div>
        </div>
    </div>
    <script>
    function showGuestAuthModal(type) {
        var modal = document.getElementById('pp-guest-modal');
        if (!modal) return;
        var badge = document.getElementById('pp-guest-icon-badge');
        var title = document.getElementById('pp-guest-title');
        var desc  = document.getElementById('pp-guest-desc');
        var ctaBtn = document.getElementById('pp-guest-login-btn');

        if (type === 'save') {
            if (badge) {
                badge.className = 'pp-guest-icon-badge badge-save';
                badge.innerHTML = '<i class="fa-solid fa-bookmark"></i>';
            }
            if (title) title.innerHTML = 'Save Your Favorites! 🔖';
            if (desc) desc.textContent = 'Sign in to bookmark this prompt so you can easily find and copy it anytime from your profile!';
            if (ctaBtn) {
                var span = ctaBtn.querySelector('span');
                if (span) span.textContent = 'Sign In to Save ✨';
            }
        } else {
            if (badge) {
                badge.className = 'pp-guest-icon-badge badge-like';
                badge.innerHTML = '<i class="fa-solid fa-heart"></i>';
            }
            if (title) title.innerHTML = 'Show Some Love! ❤️✨';
            if (desc) desc.textContent = 'Love this prompt? Sign in to like and support prompt creators, and sync your favorites across all devices!';
            if (ctaBtn) {
                var span = ctaBtn.querySelector('span');
                if (span) span.textContent = 'Sign In to Like ✨';
            }
        }

        modal.classList.add('is-open');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeGuestModal() {
        var modal = document.getElementById('pp-guest-modal');
        if (modal) {
            modal.classList.remove('is-open');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
    function openStepPicModal(e, url) {
        if (e) e.preventDefault();
        var m = document.getElementById('stepPicModal');
        var img = document.getElementById('stepPicModalImg');
        if (m && img) {
            img.src = url;
            m.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
    }
    function closeStepPicModal() {
        var m = document.getElementById('stepPicModal');
        if (m) {
            m.classList.remove('is-open');
            document.body.style.overflow = '';
        }
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeStepPicModal();
            closeGuestModal();
        }
    });
    </script>
</body>
</html>

