<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';
require_once 'slug_helper.php';

function nm_table_exists(PDO $pdo, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    try {
        $q = $pdo->prepare('SHOW TABLES LIKE ?');
        $q->execute([$table]);
        $cache[$table] = (bool) $q->fetchColumn();
    } catch (PDOException $e) {
        $cache[$table] = false;
    }
    return $cache[$table];
}

$cat = strtolower(trim($_GET['cat'] ?? 'all'));
$tag = strtolower(trim($_GET['tag'] ?? ''));
$valid_cats = ['all', 'boys', 'girls', 'couple', 'family', 'creativity'];
if (!in_array($cat, $valid_cats)) $cat = 'all';

if (!nm_table_exists($pdo, 'not_mine_prompts')) {
    $prompts = [];
} else {
    try {
        if ($cat === 'all') {
            $prompts = $pdo->query('SELECT nm.*, COALESCE(lc.cnt,0) AS like_count FROM not_mine_prompts nm LEFT JOIN (SELECT prompt_id, COUNT(*) as cnt FROM not_mine_likes GROUP BY prompt_id) lc ON lc.prompt_id = nm.id WHERE nm.is_visible = 1 ORDER BY nm.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare('SELECT nm.*, COALESCE(lc.cnt,0) AS like_count FROM not_mine_prompts nm LEFT JOIN (SELECT prompt_id, COUNT(*) as cnt FROM not_mine_likes GROUP BY prompt_id) lc ON lc.prompt_id = nm.id WHERE nm.is_visible = 1 AND nm.category = ? ORDER BY nm.created_at DESC');
            $stmt->execute([$cat]);
            $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // Backward compatibility for live DBs where not_mine_likes table is missing.
        if ($cat === 'all') {
            $prompts = $pdo->query('SELECT nm.*, 0 AS like_count FROM not_mine_prompts nm WHERE nm.is_visible = 1 ORDER BY nm.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare('SELECT nm.*, 0 AS like_count FROM not_mine_prompts nm WHERE nm.is_visible = 1 AND nm.category = ? ORDER BY nm.created_at DESC');
            $stmt->execute([$cat]);
            $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

if (!isset($prompts) || !is_array($prompts)) {
    $prompts = [];
}

if ($tag !== '') {
    $prompts = array_values(array_filter($prompts, function ($p) use ($tag) {
        $tags = array_map('trim', explode(',', strtolower($p['tags'] ?? '')));
        return in_array($tag, $tags, true);
    }));
}

$subtags = [];
foreach ($prompts as $p) {
    foreach (explode(',', strtolower($p['tags'] ?? '')) as $t) {
        $t = trim($t);
        if ($t !== '') $subtags[$t] = ($subtags[$t] ?? 0) + 1;
    }
}
ksort($subtags);

$counts = [];
try {
    if (nm_table_exists($pdo, 'not_mine_prompts')) {
        $cs = $pdo->query("SELECT category, COUNT(*) as cnt FROM not_mine_prompts WHERE is_visible = 1 GROUP BY category");
        while ($r = $cs->fetch(PDO::FETCH_ASSOC)) $counts[$r['category']] = (int)$r['cnt'];
    } else {
        $counts = [];
    }
} catch (PDOException $e) {
}
$total = array_sum($counts);

$cat_meta = [
    'all' => [
        'title' => 'Not Mine Prompts',
        'hero' => 'Not Mine',
        'tagline' => 'Ye woh prompts hain jo mere nahi hain — par internet pe famous hain. Category choose karo aur compare karo.',
        'icon' => 'fa-ban',
        'theme_color' => '#2F4156',
        'count_label' => 'Total prompts',
    ],
    'boys' => [
        'title' => 'Boys Prompts — Not Mine',
        'hero' => 'Boys',
        'tagline' => 'Trending boys prompts — ChatGPT vs Gemini compare karo.',
        'seo_desc' => 'Realistic portraits & viral reel looks ke liye best boys AI prompts yahan milenge.',
        'meta_desc' => 'Boys AI image prompts — ChatGPT vs Gemini comparison, vote karke unlock karo. Viral boys reel prompts for Instagram.',
        'icon' => 'fa-mars',
        'theme_color' => '#2563eb',
        'count_label' => 'Boys prompts',
    ],
    'girls' => [
        'title' => 'Girls Prompts — Not Mine',
        'hero' => 'Girls',
        'tagline' => 'Viral girls prompts — dono AI ka result ek saath dekho.',
        'seo_desc' => 'Aesthetic portraits & glam edits ke liye curated girls AI prompts.',
        'meta_desc' => 'Girls AI prompts collection — ChatGPT vs Gemini side by side. Aesthetic girls reel prompts unlock karo.',
        'icon' => 'fa-venus',
        'theme_color' => '#db2777',
        'count_label' => 'Girls prompts',
    ],
    'couple' => [
        'title' => 'Couple Prompts — Not Mine',
        'hero' => 'Couple',
        'tagline' => 'Romantic couple prompts — kaunsa AI better banata hai?',
        'seo_desc' => 'Love-story reels & duo portraits ke liye pink-red themed couple prompts.',
        'meta_desc' => 'Couple AI prompts — romantic duo images, ChatGPT vs Gemini results. Viral couple reel prompts.',
        'icon' => 'fa-heart',
        'theme_color' => '#e11d48',
        'count_label' => 'Couple prompts',
    ],
    'family' => [
        'title' => 'Family Prompts — Not Mine',
        'hero' => 'Family',
        'tagline' => 'Family & group prompts — warm vibes, real comparison.',
        'seo_desc' => 'Group portraits aur emotional family scenes ke liye AI prompts.',
        'meta_desc' => 'Family AI prompts — group photo & portrait prompts. ChatGPT vs Gemini compare karke unlock.',
        'icon' => 'fa-people-group',
        'theme_color' => '#16a34a',
        'count_label' => 'Family prompts',
    ],
    'creativity' => [
        'title' => 'Creativity Prompts — Not Mine',
        'hero' => 'Creativity',
        'tagline' => 'Creative prompts — imagination meets AI battle.',
        'seo_desc' => 'Artistic concepts & unique visual experiments ke liye creative AI prompts.',
        'meta_desc' => 'Creative AI prompts — artistic image prompts with ChatGPT vs Gemini comparison. Unlock unique concepts.',
        'icon' => 'fa-lightbulb',
        'theme_color' => '#7c3aed',
        'count_label' => 'Creativity prompts',
    ],
];
$meta = $cat_meta[$cat] ?? $cat_meta['all'];
$cat_count = $cat === 'all' ? $total : (int)($counts[$cat] ?? 0);
$page_desc = $cat === 'all'
    ? 'Ye woh prompts hain jo mere nahi hain — par internet pe famous hain. ChatGPT vs Gemini compare karo aur vote karke unlock karo.'
    : ($meta['meta_desc'] ?? $meta['tagline']);
?>
<!DOCTYPE html>
<html lang="en" class="theme-nogoda">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="<?= htmlspecialchars($meta['theme_color']) ?>">
    <title><?= htmlspecialchars($meta['title']) ?> — Arigato Devan</title>
    <meta name="description" content="<?= htmlspecialchars($page_desc) ?>">
    <?php include_once 'includes/theme_head.php'; ?>
    <link rel="stylesheet" href="css/info-pages.css?v=20260721">
    <?php include_once 'gtag.php'; ?>
<style>
/* ── Page shell ── */
.nm-page { --nm-accent: #F5709D; --nm-accent-soft: rgba(245,112,157,.12); --nm-accent-border: rgba(245,112,157,.28); --nm-page-bg: var(--pal-beige); }
.nm-page.nm-cat-boys { --nm-accent:#2563eb; --nm-accent-soft:rgba(37,99,235,.1); --nm-accent-border:rgba(37,99,235,.22); --nm-page-bg:linear-gradient(180deg,#eff6ff 0%,#f8fbff 42%,#f5efeb 100%); }
.nm-page.nm-cat-girls { --nm-accent:#db2777; --nm-accent-soft:rgba(219,39,119,.1); --nm-accent-border:rgba(219,39,119,.22); --nm-page-bg:linear-gradient(180deg,#fdf2f8 0%,#fff7fb 42%,#f5efeb 100%); }
.nm-page.nm-cat-couple { --nm-accent:#e11d48; --nm-accent-soft:rgba(225,29,72,.1); --nm-accent-border:rgba(244,63,94,.24); --nm-page-bg:linear-gradient(180deg,#fff1f2 0%,#ffe4e6 38%,#fdf2f8 68%,#f5efeb 100%); }
.nm-page.nm-cat-family { --nm-accent:#16a34a; --nm-accent-soft:rgba(22,163,74,.1); --nm-accent-border:rgba(22,163,74,.22); --nm-page-bg:linear-gradient(180deg,#ecfdf5 0%,#f4fdf8 42%,#f5efeb 100%); }
.nm-page.nm-cat-creativity { --nm-accent:#7c3aed; --nm-accent-soft:rgba(124,58,237,.1); --nm-accent-border:rgba(124,58,237,.22); --nm-page-bg:linear-gradient(180deg,#f5f3ff 0%,#faf8ff 42%,#f5efeb 100%); }
body.page-store.theme-nogoda.nm-page { background: var(--nm-page-bg) !important; min-height: 100vh; }

/* ── Hero ── */
.nm-hero { text-align: center; padding: 44px 20px 28px; max-width: 720px; margin: 0 auto; }
.nm-hero .hero-label { font-size: .68rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--nm-accent); margin-bottom: 10px; }
.nm-hero .hero-label i { color: var(--nm-accent); }
.nm-hero h1 { font-family: 'Playfair Display', serif; font-size: clamp(1.9rem, 5vw, 2.9rem); font-weight: 900; line-height: 1.12; margin-bottom: 10px; color: var(--pal-navy); }
.nm-hero h1 em { font-style: italic; color: var(--nm-accent); background: none; -webkit-text-fill-color: currentColor; }
.nm-hero p { font-size: .88rem; color: var(--pal-teal); max-width: 560px; margin: 0 auto; line-height: 1.65; }

.nm-cat-hero {
    text-align: center;
    padding: 36px 20px 28px;
    max-width: 760px;
    margin: 0 auto 8px;
}
.nm-back-all {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 18px;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(255,255,255,.82);
    border: 1px solid var(--nm-accent-border);
    color: var(--nm-accent);
    font-size: .74rem;
    font-weight: 700;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(47,65,86,.06);
}
.nm-back-all:hover { background: #fff; transform: translateY(-1px); }
.nm-cat-hero-icon {
    width: 64px; height: 64px;
    margin: 0 auto 14px;
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    color: var(--nm-accent);
    background: var(--nm-accent-soft);
    border: 1px solid var(--nm-accent-border);
    box-shadow: 0 10px 28px rgba(47,65,86,.08);
}
.nm-cat-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2rem, 5.5vw, 3rem);
    font-weight: 900;
    color: var(--pal-navy);
    margin-bottom: 8px;
    line-height: 1.1;
}
.nm-cat-hero p { font-size: .88rem; color: var(--pal-teal); max-width: 540px; margin: 0 auto 10px; line-height: 1.6; }
.nm-cat-hero-seo {
    font-size: .78rem;
    color: var(--pal-teal);
    opacity: .88;
    max-width: 520px;
    margin: 0 auto 14px;
    line-height: 1.55;
}
.nm-cat-hero-count {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 999px;
    background: #fff;
    border: 1px solid var(--nm-accent-border);
    color: var(--nm-accent);
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .04em;
}

/* ── Category hub (not tags) ── */
.nm-cat-hub { max-width: 1100px; margin: 0 auto; padding: 0 clamp(16px, 4vw, 28px) 40px; width: 100%; }
.nm-cat-hub-title {
    text-align: center;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--pal-teal);
    margin-bottom: 18px;
}
.nm-cat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: clamp(14px, 2.2vw, 20px);
    width: 100%;
}
.nm-cat-tile {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
}
.nm-cat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 10px;
    padding: 22px 16px 18px;
    border-radius: 20px;
    text-decoration: none;
    color: var(--pal-navy);
    background: #fff;
    border: 1.5px solid var(--pal-sky);
    box-shadow: 0 8px 24px rgba(47,65,86,.06);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    height: 100%;
}
.nm-cat-card:hover { transform: translateY(-4px); box-shadow: 0 16px 36px rgba(47,65,86,.1); }
.nm-cat-card-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.nm-cat-card-name { font-size: .95rem; font-weight: 800; letter-spacing: -.01em; }
.nm-cat-card-count { font-size: .68rem; font-weight: 700; opacity: .65; }
.nm-cat-card-boys { border-color: rgba(37,99,235,.18); }
.nm-cat-card-boys .nm-cat-card-icon { background: rgba(37,99,235,.1); color: #2563eb; }
.nm-cat-card-boys:hover { border-color: rgba(37,99,235,.35); }
.nm-cat-card-girls { border-color: rgba(219,39,119,.18); }
.nm-cat-card-girls .nm-cat-card-icon { background: rgba(219,39,119,.1); color: #db2777; }
.nm-cat-card-girls:hover { border-color: rgba(219,39,119,.35); }
.nm-cat-card-couple { border-color: rgba(244,63,94,.22); background: linear-gradient(180deg,#fff 0%,#fff7f8 100%); }
.nm-cat-card-couple .nm-cat-card-icon { background: linear-gradient(135deg,rgba(244,114,182,.22),rgba(225,29,72,.16)); color: #e11d48; }
.nm-cat-card-couple:hover { border-color: rgba(225,29,72,.38); box-shadow: 0 16px 36px rgba(225,29,72,.12); }
.nm-cat-card-family { border-color: rgba(22,163,74,.18); }
.nm-cat-card-family .nm-cat-card-icon { background: rgba(22,163,74,.1); color: #16a34a; }
.nm-cat-card-family:hover { border-color: rgba(22,163,74,.35); }
.nm-cat-card-creativity { border-color: rgba(124,58,237,.18); }
.nm-cat-card-creativity .nm-cat-card-icon { background: rgba(124,58,237,.1); color: #7c3aed; }
.nm-cat-card-creativity:hover { border-color: rgba(124,58,237,.35); }

@media (max-width: 640px) {
    .nm-cat-grid { grid-template-columns: 1fr; max-width: 440px; margin: 0 auto; }
}

/* compact switch on category pages */
.nm-cat-switch {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    padding: 0 20px 24px;
    max-width: 820px;
    margin: 0 auto;
}
.nm-cat-switch a {
    padding: 7px 14px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
    text-decoration: none;
    color: var(--pal-teal);
    background: rgba(255,255,255,.75);
    border: 1px solid var(--pal-sky);
}
.nm-cat-switch a:hover { color: var(--nm-accent); border-color: var(--nm-accent-border); }
.nm-cat-switch a.is-active {
    color: var(--nm-accent);
    background: var(--nm-accent-soft);
    border-color: var(--nm-accent-border);
}

/* Sub tags */
.nm-subtags { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; padding: 0 20px 24px; max-width: 780px; margin: 0 auto; }
.nm-subtag-btn {
    padding: 6px 14px;
    border: 1px solid var(--pal-sky);
    border-radius: 999px;
    background: var(--pal-white);
    color: var(--pal-teal);
    text-decoration: none;
    font-size: .72rem;
    font-weight: 700;
}
.nm-subtag-btn.active {
    background: var(--nm-accent-soft);
    color: var(--nm-accent);
    border-color: var(--nm-accent-border);
    box-shadow: none;
}
.nm-subtag-cnt { opacity: .6; margin-left: 4px; font-size: .62rem; }

/* ── Card grid ── */
.nm-grid {
    max-width: 1200px; margin: 0 auto;
    padding: 0 clamp(16px, 4vw, 48px) 100px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: clamp(16px, 2.2vw, 24px);
}

/* ── Card ── */
.nm-card {
    background: var(--pal-white, #fff);
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid var(--pal-sky, #C8D9E6);
    box-shadow: 0 4px 18px rgba(47,65,86,.07);
    transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
    text-decoration: none; color: inherit;
    display: flex; flex-direction: column; cursor: pointer;
}
.nm-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 36px var(--nm-accent-soft), 0 8px 24px rgba(47,65,86,.06);
    border-color: var(--nm-accent);
}

.nm-card-img { position: relative; width: 100%; overflow: hidden; background: var(--pal-beige, #F5EFEB); }
.nm-card-img::before {
    content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 38%; z-index: 2; pointer-events: none;
    background: linear-gradient(to top, rgba(47,65,86,.5) 0%, transparent 100%);
}
.nm-card-img img { width: 100%; aspect-ratio: 9/16; object-fit: cover; display: block; transition: transform .45s ease; }
.nm-card:hover .nm-card-img img { transform: scale(1.04); }

/* Category badge */
.nm-badge {
    position: absolute; top: 12px; left: 12px; z-index: 4;
    padding: 5px 11px; border-radius: 8px;
    font-size: .58rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,.18);
}
.nm-badge.boys { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.nm-badge.girls { background: linear-gradient(135deg, #f472b6, #ec4899); }
.nm-badge.couple { background: linear-gradient(135deg, #fb7185, #e11d48); }
.nm-badge.family { background: linear-gradient(135deg, #4ade80, #22c55e); }
.nm-badge.creativity { background: linear-gradient(135deg, #a78bfa, #7c3aed); }

/* AI status pills */
.nm-ai-pills { position: absolute; bottom: 10px; left: 10px; right: 10px; display: flex; justify-content: center; gap: 7px; z-index: 4; }
.nm-ai-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 99px;
    font-size: .62rem; font-weight: 800;
    backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.3);
    box-shadow: 0 2px 10px rgba(0,0,0,.12);
}
.nm-ai-pill svg { width: 14px; height: 14px; flex-shrink: 0; }
.nm-ai-pill img { width: 14px; height: 14px; flex-shrink: 0; border-radius: 2px; }
.nm-ai-ok { background: rgba(255,255,255,.94); color: #16a34a; }
.nm-ai-fail { background: rgba(220,38,38,.92); color: #fff; border-color: rgba(255,255,255,.2); }

/* Hover overlay */
.nm-card-overlay { position: absolute; inset: 0; background: rgba(47,65,86,.42); display: flex;
    align-items: center; justify-content: center; opacity: 0; transition: opacity .3s; z-index: 5; }
.nm-card:hover .nm-card-overlay { opacity: 1; }
.nm-card-overlay span { background: var(--nm-accent); color: #fff; padding: 10px 20px; border-radius: 99px;
    font-weight: 700; font-size: .78rem; transform: translateY(6px); transition: transform .3s;
    box-shadow: 0 4px 16px rgba(0,0,0,.15); letter-spacing: .02em; }
.nm-card:hover .nm-card-overlay span { transform: translateY(0); }

/* Card info */
.nm-card-info {
    padding: 13px 15px 15px;
    border-top: 1px solid var(--pal-sky, #C8D9E6);
    background: var(--pal-white, #fff);
}
.nm-card-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.nm-card-title {
    font-size: .88rem; font-weight: 700; color: var(--pal-navy);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    line-height: 1.3; flex: 1; min-width: 0; letter-spacing: -.01em;
}
.nm-card-likes {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 9px; border-radius: 99px;
    background: var(--nm-accent-soft); border: 1px solid var(--nm-accent-border);
    font-size: .72rem; font-weight: 700; color: var(--pal-navy); flex-shrink: 0;
}
.nm-card-likes i { font-size: .65rem; color: var(--nm-accent); }

@media (max-width: 480px) {
    .nm-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; padding-left: 10px; padding-right: 10px; }
    .nm-card-info { padding: 10px 11px 12px; }
    .nm-card-title { font-size: .8rem; }
    .nm-card-likes { padding: 3px 7px; font-size: .68rem; }
    .nm-ai-pill { padding: 4px 8px; font-size: .55rem; }
    .nm-badge { top: 9px; left: 9px; padding: 4px 9px; }
    .nm-card-overlay { display: none; }
}

/* ── Empty ── */
.nm-empty { text-align: center; padding: 80px 20px; color: var(--pal-teal); }
.nm-empty i { font-size: 2rem; margin-bottom: 12px; display: block; opacity: .4; }
</style>
</head>
<body class="page-store theme-nogoda page-info nm-page<?= $cat !== 'all' ? ' nm-cat-' . htmlspecialchars($cat) : '' ?>">

<?php $nav_active = ''; include 'includes/site_nav.php'; ?>

<?php if ($cat === 'all'): ?>
<div class="nm-hero">
    <p class="hero-label"><i class="fa-solid fa-ban"></i> Not Mine Collection</p>
    <h1><em>Not Mine</em> Prompts</h1>
    <p><?= htmlspecialchars($meta['tagline']) ?></p>
</div>

<div class="nm-cat-hub">
    <p class="nm-cat-hub-title">Choose a category page</p>
    <div class="nm-cat-grid">
        <?php foreach (['boys','girls','couple','family','creativity'] as $hub_key):
            $hub = $cat_meta[$hub_key];
            $hub_cnt = (int)($counts[$hub_key] ?? 0);
        ?>
        <article class="nm-cat-tile nm-cat-tile-<?= htmlspecialchars($hub_key) ?>">
            <a href="?cat=<?= urlencode($hub_key) ?>" class="nm-cat-card nm-cat-card-<?= htmlspecialchars($hub_key) ?>">
                <span class="nm-cat-card-icon"><i class="fa-solid <?= htmlspecialchars($hub['icon']) ?>"></i></span>
                <span class="nm-cat-card-name"><?= ucfirst($hub_key) ?></span>
                <span class="nm-cat-card-count"><?= $hub_cnt ?> prompt<?= $hub_cnt === 1 ? '' : 's' ?></span>
            </a>
        </article>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="nm-cat-hero">
    <a href="?cat=all" class="nm-back-all"><i class="fa-solid fa-arrow-left"></i> All Categories</a>
    <div class="nm-cat-hero-icon"><i class="fa-solid <?= htmlspecialchars($meta['icon']) ?>"></i></div>
    <h1><?= htmlspecialchars($meta['hero']) ?> Prompts</h1>
    <p><?= htmlspecialchars($meta['tagline']) ?></p>
    <?php if (!empty($meta['seo_desc'])): ?>
    <p class="nm-cat-hero-seo"><?= htmlspecialchars($meta['seo_desc']) ?></p>
    <?php endif; ?>
    <span class="nm-cat-hero-count"><i class="fa-solid fa-layer-group"></i> <?= $cat_count ?> <?= strtolower($meta['count_label']) ?></span>
</div>

<div class="nm-cat-switch">
    <a href="?cat=all">All</a>
    <?php foreach (['boys','girls','couple','family','creativity'] as $sw): ?>
    <a href="?cat=<?= urlencode($sw) ?>" class="<?= $cat === $sw ? 'is-active' : '' ?>"><?= ucfirst($sw) ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($subtags)): ?>
<div class="nm-subtags">
    <a href="?cat=<?= urlencode($cat) ?>" class="nm-subtag-btn <?= $tag === '' ? 'active' : '' ?>">All Tags</a>
    <?php foreach ($subtags as $st => $stCount): ?>
    <a href="?cat=<?= urlencode($cat) ?>&tag=<?= urlencode($st) ?>" class="nm-subtag-btn <?= $tag === $st ? 'active' : '' ?>">
        <?= htmlspecialchars(ucfirst($st)) ?><span class="nm-subtag-cnt"><?= (int)$stCount ?></span>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($prompts)): ?>
<div class="nm-empty">
    <i class="fa-solid fa-folder-open"></i>
    <p>No prompts in this category yet — check back soon!</p>
</div>
<?php else: ?>
<div class="nm-grid">
    <?php foreach ($prompts as $p): ?>
    <a href="<?= htmlspecialchars(nm_prompt_url($p)) ?>" class="nm-card">
        <div class="nm-card-img">
            <img src="<?= htmlspecialchars($p['thumbnail_image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy">
            <span class="nm-badge <?= $p['category'] ?>"><?= ucfirst($p['category']) ?></span>
            <div class="nm-ai-pills">
                <span class="nm-ai-pill <?= $p['chatgpt_failed'] ? 'nm-ai-fail' : 'nm-ai-ok' ?>">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg" alt="GPT">
                    <?= $p['chatgpt_failed'] ? '✗' : '✓' ?>
                </span>
                <span class="nm-ai-pill <?= $p['gemini_failed'] ? 'nm-ai-fail' : 'nm-ai-ok' ?>">
                    <svg viewBox="0 0 24 24" style="width:12px;height:12px"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    <?= $p['gemini_failed'] ? '✗' : '✓' ?>
                </span>
            </div>
            <div class="nm-card-overlay"><span>Vote & Unlock →</span></div>
        </div>
        <div class="nm-card-info">
            <div class="nm-card-row">
                <p class="nm-card-title"><?= htmlspecialchars($p['title']) ?></p>
                <span class="nm-card-likes"><i class="fa-solid fa-heart"></i> <?= (int)$p['like_count'] ?></span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
</body>
</html>
