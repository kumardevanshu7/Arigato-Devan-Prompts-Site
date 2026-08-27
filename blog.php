<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";
$slug = $_GET["slug"] ?? "";
if (!$slug) {
    header("Location: blogs.php");
    exit();
}

$stmt = $pdo->prepare(
    "SELECT b.*, u.username as author_name, u.avatar as author_avatar FROM blogs b LEFT JOIN users u ON b.author_id=u.id WHERE b.slug=? AND b.is_published=1",
);
$stmt->execute([$slug]);
$blog = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$blog) {
    header("Location: blogs.php");
    exit();
}

// Increment view count
try { $pdo->prepare("UPDATE blogs SET view_count = COALESCE(view_count,0) + 1 WHERE id = ?")->execute([$blog['id']]); } catch (Exception $e) {}

// Has current user liked?
$user_liked = false;
if (isset($_SESSION["user_id"])) {
    $lk = $pdo->prepare(
        "SELECT id FROM blog_likes WHERE user_id=? AND blog_id=?",
    );
    $lk->execute([$_SESSION["user_id"], $blog["id"]]);
    $user_liked = (bool) $lk->fetch();
}

// Reactions
$reaction_counts = ['heart'=>0,'fire'=>0,'wow'=>0,'clap'=>0,'laugh'=>0];
$my_reactions = [];
try {
    $rk = isset($_SESSION['user_id']) ? 'u'.$_SESSION['user_id'] : 'ip'.md5($_SERVER['REMOTE_ADDR']);
    $rc = $pdo->prepare("SELECT reaction, COUNT(*) as cnt FROM blog_reactions WHERE blog_id=? GROUP BY reaction");
    $rc->execute([$blog['id']]);
    foreach ($rc->fetchAll() as $r) $reaction_counts[$r['reaction']] = (int)$r['cnt'];
    $mr = $pdo->prepare("SELECT reaction FROM blog_reactions WHERE blog_id=? AND reactor_key=?");
    $mr->execute([$blog['id'], $rk]);
    $my_reactions = array_column($mr->fetchAll(), 'reaction');
} catch(Exception $e) {}

// Comments
$comments = $pdo->prepare(
    "SELECT bc.*, u.username, u.avatar as profile_image FROM blog_comments bc LEFT JOIN users u ON bc.user_id=u.id WHERE bc.blog_id=? ORDER BY bc.created_at ASC",
);
$comments->execute([$blog["id"]]);
$comments = $comments->fetchAll(PDO::FETCH_ASSOC);

// Calculate reading time (200 words per minute average)
$word_count = str_word_count(strip_tags($blog["content"] ?? ""));
$read_time = max(1, (int)ceil($word_count / 200));
$tags_list = array_values(array_filter(array_map('trim', explode(',', (string) ($blog['tags'] ?? '')))));
$author_av = !empty($blog['author_avatar'])
    ? $blog['author_avatar']
    : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($blog['author_name'] ?? 'Admin');
$cover_portrait = !empty($blog['image_path']) ? $blog['image_path'] : '';
$cover_landscape = !empty($blog['image_path_landscape']) ? $blog['image_path_landscape'] : '';
$has_cover = ($cover_portrait !== '' || $cover_landscape !== '');
$blog_content_en = $blog['content'] ?? '';
$blog_content_hi = $blog['content_hindi'] ?? '';
foreach ([$cover_portrait, $cover_landscape] as $cover_src) {
    if ($cover_src === '') continue;
    $hero_base = preg_quote(basename($cover_src), '/');
    $cover_pat = '/<img[^>]+src=["\'][^"\']*' . $hero_base . '["\'][^>]*>/i';
    $blog_content_en = preg_replace($cover_pat, '', $blog_content_en, 1);
    $blog_content_hi = preg_replace($cover_pat, '', $blog_content_hi, 1);
}
?><!DOCTYPE html>
<html lang="en" class="theme-nogoda">
<head>
    <meta name="theme-color" content="#c084fc">
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($blog["meta_title"] ?? $blog["title"]) ?> &ndash; Arigato Devan Prompts</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
<link rel="stylesheet" href="css/nogoda-theme.css?v=20260741">
<?php include_once 'includes/theme_head.php'; ?>
<link rel="stylesheet" href="css/blog-splash-loading.css?v=20260756">
<link rel="stylesheet" href="css/blog-magazine.css?v=20260820k">
<meta name="description" content="<?= htmlspecialchars(
    $blog["meta_description"] ?? ($blog["description"] ?? ""),
) ?>">
<?php if ($blog["tags"]): ?><meta name="keywords" content="<?= htmlspecialchars(
    $blog["tags"],
) ?>"><?php endif; ?>
<?php
    $blog_url     = 'https://arigatodevan.com/blog.php?slug=' . urlencode($blog['slug']);
    $_page_canonical = $blog_url;
    $og_file = $cover_landscape !== '' ? $cover_landscape : ($cover_portrait !== '' ? $cover_portrait : '');
    $blog_og_img  = $og_file
                    ? 'https://arigatodevan.com/' . ltrim($og_file, '/')
                    : 'https://arigatodevan.com/landingpics/lan9.webp';
    $blog_og_desc = htmlspecialchars($blog['meta_description'] ?? ($blog['description'] ?? substr(strip_tags($blog['content'] ?? ''), 0, 155)));
    $blog_og_title = htmlspecialchars(($blog['meta_title'] ?? $blog['title']) . ' – Arigato Devan');
?>
<link rel="canonical" href="<?= htmlspecialchars($blog_url) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<!-- Favicon -->
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<!-- Open Graph -->
<meta property="og:type" content="article">
<meta property="og:site_name" content="Arigato Devan Prompts">
<meta property="og:title" content="<?= $blog_og_title ?>">
<meta property="og:description" content="<?= $blog_og_desc ?>">
<meta property="og:image" content="<?= $blog_og_img ?>">
<meta property="og:url" content="<?= $blog_url ?>">
<meta property="article:published_time" content="<?= date('c', strtotime($blog['created_at'])) ?>">
<meta property="article:modified_time" content="<?= date('c', strtotime($blog['updated_at'] ?? $blog['created_at'])) ?>">
<meta property="article:author" content="<?= htmlspecialchars($blog['author_name'] ?? 'Arigato Devan') ?>">
<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $blog_og_title ?>">
<meta name="twitter:description" content="<?= $blog_og_desc ?>">
<meta name="twitter:image" content="<?= $blog_og_img ?>">
<!-- BlogPosting Schema -->
<script type="application/ld+json">
<?= json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'BlogPosting',
    'headline'      => $blog['title'],
    'description'   => strip_tags($blog['meta_description'] ?? $blog['description'] ?? ''),
    'url'           => $blog_url,
    'image'         => $blog_og_img,
    'datePublished' => date('c', strtotime($blog['created_at'])),
    'dateModified'  => date('c', strtotime($blog['updated_at'] ?? $blog['created_at'])),
    'author'        => ['@type' => 'Person', 'name' => $blog['author_name'] ?? 'Arigato Devan'],
    'publisher'     => [
        '@type' => 'Organization',
        'name'  => 'Arigato Devan',
        'url'   => 'https://arigatodevan.com',
        'logo'  => ['@type' => 'ImageObject', 'url' => 'https://arigatodevan.com/toplogo/logo01.webp'],
    ],
    'inLanguage'    => 'en',
    'keywords'      => $blog['tags'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<link rel="stylesheet" href="style.min.css?v=20260601">
<style>
/* Global Modern Reset for Blog Post Viewer */
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap');

body {
    background-color: #f1f5f9 !important; /* Neutral light-gray base */
    font-family: 'Inter', sans-serif !important;
    color: #1e293b !important;
    position: relative !important;
}

/* Force hide all scrollbars of HTML and Body elements during splash transitions */
html.no-scroll, body.no-scroll {
    overflow: hidden !important;
    height: 100vh !important;
}

/* Animated Ambient Aurora Background */
.aurora-bg {
    position: fixed !important;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw !important;
    height: 100vh !important;
    z-index: -2 !important;
    background: 
        radial-gradient(circle at 15% 15%, rgba(193, 232, 255, 0.75) 0%, transparent 45%),
        radial-gradient(circle at 85% 15%, rgba(224, 218, 254, 0.75) 0%, transparent 45%),
        radial-gradient(circle at 50% 85%, rgba(245, 238, 253, 0.85) 0%, transparent 50%),
        radial-gradient(circle at 80% 85%, rgba(193, 232, 255, 0.5) 0%, transparent 40%) !important;
    filter: blur(80px) !important;
    animation: auroraShift 30s ease infinite alternate !important;
}

@keyframes auroraShift {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    50% {
        transform: translate(40px, -30px) scale(1.08);
    }
    100% {
        transform: translate(-30px, 20px) scale(0.92);
    }
}

/* Interactive Dynamic Mouse-Following Glow */
.back-glow {
    position: fixed !important;
    top: 0;
    left: 0;
    width: 100vw !important;
    height: 100vh !important;
    pointer-events: none !important;
    z-index: -1 !important;
    background: radial-gradient(700px circle at var(--x, 50vw) var(--y, 50vh), rgba(99, 102, 241, 0.08), rgba(168, 85, 247, 0.04) 50%, transparent 80%) !important;
    transition: background 0.1s ease !important;
}
@media (max-width: 768px) {
    .back-glow {
        display: none !important; /* Disabled on mobile for performance/page-speed boost! */
    }
}

/* Hide Main Website wallpaper in Blog */
.scroll-bg-container, body::before {
    display: none !important;
}

/* Override Header to be stunningly premium & clean (0% comic, 100% professional) */
header:not(.store-header) {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    border-radius: 24px !important;
    margin: 15px 24px 0 !important;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.02) !important;
    padding: 10px 30px !important;
    position: sticky !important;
    top: 15px !important;
    z-index: 1000 !important;
    box-sizing: border-box !important;
    min-height: 64px !important;
    display: flex !important;
    flex-direction: column !important; /* Stack drawer under main header line */
    justify-content: center !important;
    transform: none !important;
    transition: border-radius 0.25s ease, padding 0.25s ease !important;
}
header.menu-open {
    border-radius: 24px 24px 16px 16px !important;
    padding-bottom: 20px !important;
}
.header-top-row {
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    flex-wrap: nowrap !important;
    gap: 16px !important;
}
header .logo-area {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    flex-shrink: 0 !important;
}
header .logo-text {
    font-family: 'Playfair Display', Georgia, serif !important;
    font-style: italic !important;
    font-weight: 700 !important;
    font-size: 1.05rem !important;
    letter-spacing: -0.02em !important;
    color: #2F4156 !important;
    line-height: 1.15 !important;
    text-transform: lowercase !important;
    white-space: nowrap !important;
}
header .logo-text .blog-brand-suffix {
    background: linear-gradient(90deg, #6D2D52, #F5709D, #11FFC9, #2FA6C6) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    background-clip: text !important;
}
/* Override style.min.css � keep arigato.blog visible on all mobile widths */
header .logo-area .logo-text {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    flex: 1 1 auto !important;
    min-width: 0 !important;
    overflow: visible !important;
}
/* logo circle avatar � css/blog-header-logo.css */
header nav.nav-links {
    gap: 16px !important; /* Perfect spacious layout */
    border: none !important;
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    flex-wrap: nowrap !important; /* Never wrap on desktop */
}
header nav.nav-links a {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    color: #475569 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 12px !important;
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    border-radius: 12px !important;
    transition: all 0.2s;
    flex-shrink: 0 !important;
    white-space: nowrap !important;
}
header nav.nav-links a:hover, header nav.nav-links a.active {
    color: #6366f1 !important;
    background: rgba(99, 102, 241, 0.05) !important;
}
header .header-right {
    gap: 15px !important;
    display: flex !important;
    align-items: center !important;
    flex-shrink: 0 !important;
}
header .header-right .logout {
    border: none !important;
    background: #f1f5f9 !important;
    color: #475569 !important;
    border-radius: 12px !important;
    padding: 8px 16px !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    box-shadow: none !important;
    flex-shrink: 0 !important;
    white-space: nowrap !important;
}
header .header-right .logout:hover {
    background: #6366f1 !important;
    color: #ffffff !important;
}
header .admin-avatar {
    border: 2px solid #e2e8f0 !important;
    flex-shrink: 0 !important;
}

/* Default state for mobile components */
.dots-menu-toggle {
    display: none !important;
}
.mobile-menu-drawer {
    display: none !important;
}

/* Responsive Styles for Tablet and Mobile Viewports */
@media (max-width: 1150px) {
    header .desktop-only,
    header nav.nav-links.desktop-only,
    header .header-right.desktop-only {
        display: none !important; /* Force hide desktop-only items with absolute authority */
    }
    header:not(.store-header) {
        padding: 0 16px !important; /* Center elements vertically inside a 64px capsule */
        margin: 15px 12px 0 !important;
        border-radius: 20px !important;
        height: 64px !important; /* Exact mathematical height to match desktop perfectly */
        min-height: 64px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        overflow: visible !important;
    }
    header .header-top-row {
        min-width: 0 !important;
    }
    header .logo-area {
        flex: 1 1 auto !important;
        min-width: 0 !important;
        max-width: calc(100% - 64px) !important;
    }
    header .logo-area .logo-text {
        display: block !important;
        font-size: 0.92rem !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    header.menu-open {
        border-radius: 20px 20px 16px 16px !important;
        height: auto !important; /* Expand cleanly on mobile when open */
        overflow: visible !important;
        padding-bottom: 16px !important;
    }
    
    /* Show 3-Dot Comic Professional macOS Window Button Toggle */
    .dots-menu-toggle {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        background: #ffffff !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        padding: 10px 14px !important;
        border-radius: 16px !important;
        cursor: pointer !important;
        box-shadow: 0 4px 10px rgba(0,0,0,0.02) !important;
        transition: all 0.2s ease !important;
        outline: none !important;
    }
    .dots-menu-toggle:hover {
        background: #f8fafc !important;
        transform: scale(1.05);
    }
    .dots-menu-toggle .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    .dots-menu-toggle .dot.red { background: #ff5f56; }
    .dots-menu-toggle .dot.yellow { background: #ffbd2e; }
    .dots-menu-toggle .dot.green { background: #27c93f; }

    /* Sliding Dropdown Drawer for Mobile Menu */
    .mobile-menu-drawer {
        display: none !important; /* Completely hide when closed to prevent flex centering shift */
        width: 100% !important;
        max-height: 0;
        overflow: hidden !important;
        opacity: 0;
        transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease !important;
        border-top: 1px solid transparent;
        padding: 0 !important;
    }
    header.menu-open .mobile-menu-drawer {
        display: block !important; /* Enable layout only when open */
        max-height: 500px !important; /* Expand cleanly */
        opacity: 1 !important;
        border-top: 1px solid #f1f5f9 !important;
        padding-top: 16px !important;
        margin-top: 10px !important;
    }
    .mobile-nav-links {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        width: 100% !important;
    }
    .mobile-nav-links a {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        font-size: 0.9rem !important;
        font-weight: 700 !important;
        color: #475569 !important;
        text-decoration: none !important;
        padding: 12px 16px !important;
        border-radius: 12px !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        box-sizing: border-box !important;
        transition: all 0.2s !important;
    }
    .mobile-nav-links a:hover, .mobile-nav-links a.active {
        color: #6366f1 !important;
        background: rgba(99, 102, 241, 0.05) !important;
    }
}
header .comic-btn {
    border: none !important;
    background: #6366f1 !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.2) !important;
    border-radius: 12px !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-weight: 700 !important;
    padding: 10px 22px !important;
    text-decoration: none !important;
    transition: all 0.2s !important;
}
header .comic-btn:hover {
    background: #4f46e5 !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3) !important;
}

/* Post Detail Container wrapper */
.blog-detail-wrap {
    max-width: 860px;
    margin: 0 auto;
    padding: 100px 24px 100px; /* Added 100px top padding for beautiful breathing room below floating header */
}
@media (max-width: 768px) {
    .blog-detail-wrap {
        padding: 72px 12px 48px;
    }
}
}

/* Robust responsive floating header styling for mobile */
@media (max-width: 768px) {
    header:not(.store-header) {
        padding: 8px 16px !important;
        margin: 10px 12px 0 !important;
        border-radius: 16px !important;
        height: auto !important;
        min-height: 56px !important;
        flex-wrap: wrap !important;
        gap: 12px !important;
        justify-content: space-between !important;
    }
    header .logo-text {
        display: block !important;
        font-size: 0.86rem !important;
    }
    header nav.nav-links {
        order: 3 !important;
        width: 100% !important;
        justify-content: center !important;
        gap: 10px !important;
        border-top: 1px solid #f1f5f9 !important;
        padding-top: 10px !important;
        margin-top: 4px !important;
    }
    header nav.nav-links a {
        font-size: 0.72rem !important;
        padding: 6px 12px !important;
        border-radius: 8px !important;
    }
    header .header-right {
        gap: 10px !important;
    }
    header .header-right .logout {
        padding: 6px 12px !important;
        font-size: 0.72rem !important;
    }
}

@media (max-width: 380px) {
    header .logo-area .logo-text {
        display: block !important;
        font-size: 0.78rem !important;
    }
}
.blog-detail-hero-img {
    width: 100%;
    max-height: 640px;
    object-fit: cover;
    border-radius: 24px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 20px 40px rgba(0,0,0,0.04);
    margin-bottom: 40px;
    display: block;
}
@media (max-width: 640px) {
    .blog-detail-hero-img {
        max-height: 480px;
    }
}
.blog-detail-title {
    font-size: clamp(1.2rem, 0.7rem + 2.4vw, 2.85rem);
    font-weight: 800;
    line-height: 1.22;
    margin-top: 5px;
    margin-bottom: 22px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    letter-spacing: -0.04em;
    color: #0f172a;
}
.blog-detail-meta {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}
.blog-author-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}
.blog-author-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    color: #1e293b;
}
.blog-detail-date {
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 600;
    margin-top: 3px;
}

/* Horizontal Pills */
.blog-meta-pills {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 36px;
}
.meta-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 40px;
    padding: 8px 18px;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: capitalize;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.015);
    color: #475569;
}
.meta-pill.tag-pill {
    background: rgba(99, 102, 241, 0.08);
    color: #6366f1;
    border-color: rgba(99, 102, 241, 0.15);
}
.meta-pill.read-pill {
    background: rgba(168, 85, 247, 0.08);
    color: #a855f7;
    border-color: rgba(168, 85, 247, 0.15);
}

/* Scribe-style Paper Canvas */
.blog-paper {
    background-color: #ffffff !important;
    padding: 60px 60px !important;
    border-radius: 24px !important;
    border: 1px solid #eaeaea !important;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.03) !important;
    margin-top: 20px !important;
    position: relative !important;
    z-index: 10 !important;
}
@media (max-width: 640px) {
    .blog-paper {
        padding: 18px 14px !important;
        border-radius: 16px !important;
        margin-top: 8px !important;
    }
    .blog-detail-title {
        font-size: 1.42rem;
        letter-spacing: -0.6px;
        margin-bottom: 12px;
        line-height: 1.25;
    }
    .blog-detail-meta { margin-bottom: 14px; gap: 10px; }
    .blog-author-avatar { width: 36px; height: 36px; }
    .blog-author-name { font-size: 0.88rem; }
    .blog-detail-date { font-size: 0.75rem; }
    .blog-meta-pills { margin-bottom: 16px; gap: 6px; }
    .meta-pill { padding: 5px 12px; font-size: 0.7rem; }
    .blog-content { font-size: 0.95rem; line-height: 1.68; }
    .blog-content p { margin-bottom: 14px; }
    .blog-content h1 { font-size: 1.28rem; margin-top: 22px; }
    .blog-content h2 { font-size: 1.14rem; margin-top: 22px; }
    .blog-content h3 { font-size: 1.02rem; margin-top: 18px; }
    .blog-content ul, .blog-content ol { padding-left: 18px; margin-bottom: 14px; }
    .blog-content li { margin-bottom: 6px; }
}

/* Premium Typography matching Pic 1 article view */
.blog-content {
    font-size: 1.125rem;
    font-weight: 400;
    line-height: 1.85;
    color: #334155;
    font-family: 'Inter', sans-serif;
}
.blog-content h1, .blog-content h2, .blog-content h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    color: #0f172a;
    margin-top: 40px;
    margin-bottom: 16px;
    line-height: 1.3;
}
.blog-content h1 { font-size: 2.1rem; }
.blog-content h2 { font-size: 1.7rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
.blog-content h3 { font-size: 1.35rem; }
.blog-content p { margin-bottom: 24px; }
.blog-content ul, .blog-content ol { padding-left: 24px; margin-bottom: 24px; }
.blog-content li { margin-bottom: 12px; }
.blog-content strong { font-weight: 700; color: #0f172a; }
.blog-content em { font-style: italic; }
.blog-table-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 1.25em 0;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.blog-content table {
    width: 100%;
    min-width: 280px;
    border-collapse: collapse;
    font-size: 0.92em;
    line-height: 1.45;
    margin: 0;
}
.blog-content th,
.blog-content td {
    border: 1px solid #e2e8f0;
    padding: 10px 12px;
    text-align: left;
    vertical-align: top;
}
.blog-content th {
    background: #f8fafc;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
}
.blog-content tr:nth-child(even) td { background: #fafbfc; }
.read-size-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0 0 16px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
}
.read-size-bar span { margin-right: 4px; letter-spacing: .04em; text-transform: uppercase; }
.read-size-btn {
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
    font-family: inherit;
}
.read-size-btn.is-on {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
}
html.blog-read-little .blog-content { font-size: 1.12rem; line-height: 1.8; }
html.blog-read-medium .blog-content { font-size: 1.28rem; line-height: 1.85; }
@media (max-width: 640px) {
    html.blog-read-little .blog-content { font-size: 1.05rem; }
    html.blog-read-medium .blog-content { font-size: 1.18rem; }
    .blog-content th, .blog-content td { padding: 8px 9px; font-size: 0.88em; }
}
.blog-content blockquote {
    border-left: 4px solid #6366f1;
    padding: 20px 24px;
    margin: 32px 0;
    background: #f8fafc;
    border-radius: 0 16px 16px 0;
    font-style: italic;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    font-size: 1.15rem;
    color: #475569;
}
/* Code blocks inside blog content */
.blog-content .blog-code-block {
    position: relative;
    margin: 24px 0;
    background: #0f172a;
    border-radius: 12px;
    padding: 16px 20px;
    font-family: monospace;
    color: #cbd5e1;
    box-sizing: border-box;
    text-align: left;
    overflow: hidden;
}
.blog-content .blog-code-block pre,
.blog-content .blog-code-block .code-content {
    margin: 0;
    outline: none;
    border: none;
    background: transparent;
    color: #cbd5e1;
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.9rem;
    line-height: 1.5;
    white-space: pre-wrap !important;
    overflow-x: auto;
    text-align: left;
}
.blog-content .blog-code-block button {
    flex-shrink: 0 !important;
    white-space: nowrap !important;
    min-width: auto !important;
    border-radius: 6px !important;
}

/* Font styles inside editor content */
.blog-content .font-serif { font-family: Georgia, serif; }
.blog-content img,
.ba-content img {
    max-width: 100% !important;
    max-height: none !important;
    height: auto !important;
    object-fit: unset !important;
    border-radius: 12px;
}
.blog-content img.img-border,
.ba-content img.img-border {
    border: 1px solid #000000;
    border-radius: 12px !important;
    box-sizing: border-box !important;
}
.blog-content .font-mono { font-family: monospace; font-size: 0.95rem; background: #faf9ff; padding: 2px 6px; border-radius: 4px; border: 1px solid #dbdae5; }
.blog-content .font-bold { font-weight: 900; }
.blog-content .font-light { font-weight: 400; color: #6a6775; }
.blog-content .font-highlight,
.blog-content mark {
    background: #fef08a !important;
    padding: 2px 6px;
    border-radius: 4px;
    color: #0f172a;
}
.blog-content a {
    color: #2563eb;
    text-decoration: underline;
    text-underline-offset: 3px;
    font-weight: 500;
    transition: color 0.15s ease, text-decoration-color 0.15s ease;
}
.blog-content a:hover {
    color: #1d4ed8;
    text-decoration-color: #1d4ed8;
}

/* Smooth Jump Scroll for Table of Contents */
html { scroll-behavior: smooth; }
.blog-content h1, .blog-content h2, .blog-content h3, .blog-content h4,
.ba-content h1, .ba-content h2, .ba-content h3, .ba-content h4 {
    scroll-margin-top: 100px;
}

/* 1. Grey Disclaimer / Callout Box & Prompt Showcase */
.blog-grey-box, .ba-grey-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #94a3b8;
    border-radius: 14px;
    padding: 18px 22px;
    margin: 1.8em 0;
    color: #475569;
    font-size: 0.95rem;
    line-height: 1.65;
    box-sizing: border-box;
}
.blog-grey-box p, .ba-grey-box p {
    margin: 0 !important;
    color: inherit;
}
/* Single Unified Prompt Card (No nested boxes) */
.blog-prompt-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 20px 14px;
    margin: 1.8em 0;
    box-sizing: border-box;
    transition: all 0.2s ease;
}
.blog-prompt-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05);
}
.bpc-item {
    display: flex;
    align-items: center;
    gap: 14px;
}
.bpc-item + .bpc-item {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed #e2e8f0;
}
.bpc-thumb {
    width: 58px;
    height: 58px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    background: #ffffff;
    border: 1px solid #e2e8f0;
}
.bpc-thumb img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    display: block !important;
    margin: 0 !important;
    border-radius: 0 !important;
}
.bpc-details {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.bpc-badge {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0284c7;
    line-height: 1.2;
}
.bpc-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.96rem;
    font-weight: 700;
    color: #0f172a !important;
    line-height: 1.3;
    margin: 0 !important;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.bpc-desc {
    font-size: 0.78rem;
    color: #64748b;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.bpc-action {
    flex-shrink: 0;
    margin-left: auto;
}
.bpc-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #0f172a;
    color: #ffffff !important;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none !important;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.bpc-btn:hover {
    background: #2563eb;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}
.bpc-note {
    margin: 12px 0 0 !important;
    padding-top: 10px;
    border-top: 1px solid #e2e8f0;
    font-size: 0.84rem !important;
    color: #64748b !important;
    line-height: 1.55 !important;
    font-style: italic;
}
@media (max-width: 520px) {
    .bpc-item {
        flex-wrap: wrap;
        gap: 10px;
    }
    .bpc-action {
        width: 100%;
        margin-left: 0;
    }
    .bpc-btn {
        width: 100%;
        justify-content: center;
    }
}

/* 2. Image Caption */
figcaption, .img-caption {
    font-size: 0.86rem;
    color: #64748b;
    font-style: italic;
    margin-top: 6px;
    margin-bottom: 1.2em;
    text-align: center;
    line-height: 1.4;
}

/* 3. Table of Contents (TOC) Box - 3 Configurable Sizes */
.blog-toc-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #0284c7;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
}
.blog-toc-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0369a1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.blog-toc-toggle {
    background: #e0f2fe;
    border: none;
    border-radius: 6px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    color: #0284c7;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.15s ease, color 0.15s ease;
}
.blog-toc-toggle:hover {
    background: #bae6fd;
    color: #0369a1;
}
.blog-toc-box ol {
    margin: 0;
}
.blog-toc-box li {
    color: #0284c7;
}
.blog-toc-box a {
    color: #0369a1 !important;
    text-decoration: none !important;
    font-weight: 500 !important;
    transition: color 0.15s ease, text-decoration 0.15s ease;
}
.blog-toc-box a:hover {
    color: #0284c7 !important;
    text-decoration: underline !important;
    text-underline-offset: 2px !important;
}

/* SIZE 1: SMALL / COMPACT (Default) */
.blog-toc-box.blog-toc-sm, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) {
    padding: 10px 14px;
    margin: 1.1em 0 1.4em;
    border-radius: 10px;
}
.blog-toc-sm .blog-toc-title, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) .blog-toc-title {
    font-size: 0.82rem;
    margin-bottom: 6px;
}
.blog-toc-sm .blog-toc-toggle, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) .blog-toc-toggle {
    padding: 2px 8px;
    font-size: 0.72rem;
}
.blog-toc-sm ol, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) ol {
    padding-left: 18px;
    font-size: 0.80rem;
    line-height: 1.36;
}
.blog-toc-sm li, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) li {
    margin-bottom: 3px;
}
@media (min-width: 640px) {
    .blog-toc-sm ol, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) ol {
        columns: 2;
        column-gap: 24px;
    }
    .blog-toc-sm li, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) li {
        break-inside: avoid;
        margin-bottom: 4px;
    }
}

/* SIZE 2: MEDIUM / STANDARD */
.blog-toc-box.blog-toc-md {
    padding: 14px 18px;
    margin: 1.4em 0 1.8em;
    border-radius: 12px;
}
.blog-toc-md .blog-toc-title {
    font-size: 0.92rem;
    margin-bottom: 9px;
}
.blog-toc-md .blog-toc-toggle {
    padding: 3px 10px;
    font-size: 0.76rem;
}
.blog-toc-md ol {
    padding-left: 20px;
    font-size: 0.88rem;
    line-height: 1.48;
}
.blog-toc-md li {
    margin-bottom: 5px;
}
@media (min-width: 640px) {
    .blog-toc-md ol {
        columns: 2;
        column-gap: 28px;
    }
    .blog-toc-md li {
        break-inside: avoid;
        margin-bottom: 5px;
    }
}

/* SIZE 3: LARGE / SPACIOUS */
.blog-toc-box.blog-toc-lg {
    padding: 18px 24px;
    margin: 1.8em 0 2.2em;
    border-radius: 14px;
}
.blog-toc-lg .blog-toc-title {
    font-size: 1.05rem;
    margin-bottom: 12px;
}
.blog-toc-lg .blog-toc-toggle {
    padding: 4px 12px;
    font-size: 0.80rem;
}
.blog-toc-lg ol {
    padding-left: 22px;
    font-size: 0.95rem;
    line-height: 1.62;
}
.blog-toc-lg li {
    margin-bottom: 7px;
}
@media (min-width: 640px) {
    .blog-toc-lg ol {
        columns: 2;
        column-gap: 32px;
    }
    .blog-toc-lg li {
        break-inside: avoid;
        margin-bottom: 7px;
    }
}

/* 4. CTA Color Box (3 Nogoda Themes) */
.blog-cta-box {
    border-radius: 20px;
    padding: 34px 28px;
    margin: 2.2em 0;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    position: relative;
    overflow: hidden;
}
.blog-cta-box h3, .blog-cta-title {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-weight: 800 !important;
    font-size: 1.55rem !important;
    line-height: 1.3 !important;
    margin: 0 0 12px !important;
    color: #ffffff !important;
}
.blog-cta-box p, .blog-cta-desc {
    font-size: 1.05rem !important;
    line-height: 1.6 !important;
    margin: 0 auto 22px !important;
    max-width: 680px;
    opacity: 0.95;
    color: #f1f5f9 !important;
}
.blog-cta-buttons {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 8px;
}
.blog-cta-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 24px;
    border-radius: 9999px;
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
    cursor: pointer;
}
.blog-cta-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
}
/* Theme 1: Nogoda Electric Violet (Default) */
.blog-cta-violet {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 55%, #9333ea 100%);
    border: 1px solid rgba(255, 255, 255, 0.18);
}
.blog-cta-violet .blog-cta-btn-primary {
    background: #f43f5e;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(244, 63, 94, 0.4);
}
.blog-cta-violet .blog-cta-btn-secondary {
    background: #ffffff;
    color: #4f46e5 !important;
}
.blog-cta-violet .blog-cta-btn-ghost {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.35);
    backdrop-filter: blur(8px);
}
/* Theme 2: Nogoda Deep Navy & Sky */
.blog-cta-navy {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #2F4156 100%);
    border: 1px solid rgba(200, 217, 230, 0.22);
}
.blog-cta-navy .blog-cta-btn-primary {
    background: #38bdf8;
    color: #0f172a !important;
    box-shadow: 0 4px 14px rgba(56, 189, 248, 0.35);
}
.blog-cta-navy .blog-cta-btn-secondary {
    background: #ffffff;
    color: #0f172a !important;
}
.blog-cta-navy .blog-cta-btn-ghost {
    background: rgba(255, 255, 255, 0.12);
    color: #e2e8f0 !important;
    border: 1px solid rgba(255, 255, 255, 0.25);
}
/* Theme 3: Nogoda Sunset Rose */
.blog-cta-rose {
    background: linear-gradient(135deg, #9f1239 0%, #e11d48 55%, #f43f5e 100%);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.blog-cta-rose .blog-cta-btn-primary {
    background: #fbbf24;
    color: #881337 !important;
    box-shadow: 0 4px 14px rgba(251, 191, 36, 0.4);
}
.blog-cta-rose .blog-cta-btn-secondary {
    background: #ffffff;
    color: #be123c !important;
}
.blog-cta-rose .blog-cta-btn-ghost {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.35);
}

/* Like + Share bar */
.blog-action-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 30px 0;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    margin: 50px 0;
}
.blog-like-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 28px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 40px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    color: #475569;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.blog-like-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
    border-color: #94a3b8;
}
.blog-like-btn.liked {
    background: #ff6b8b;
    color: #ffffff;
    border-color: #ff6b8b;
    box-shadow: 0 10px 20px rgba(255, 107, 139, 0.25);
}
.blog-like-btn.liked svg {
    fill: #ffffff;
    stroke: #ffffff;
}

/* Comments Section style overrides */
.comments-section h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 28px;
}
.comment-item {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    padding: 24px;
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.01);
    transition: all 0.25s ease;
}
.comment-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.03);
}
.comment-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 1.5px solid #e2e8f0;
    flex-shrink: 0;
}
.comment-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    color: #0f172a;
    margin-bottom: 4px;
}
.comment-text {
    font-size: 0.95rem;
    font-weight: 400;
    line-height: 1.6;
    color: #475569;
}
.comment-time {
    font-size: 0.78rem;
    color: #94a3b8;
    font-weight: 500;
    margin-top: 8px;
}
.comment-form {
    margin-top: 36px;
}
.comment-form textarea {
    width: 100%;
    padding: 16px 20px;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    font-weight: 400;
    background: #ffffff;
    color: #1e293b;
    resize: vertical;
    min-height: 110px;
    outline: none;
    box-shadow: 0 4px 10px rgba(0,0,0,0.005);
    transition: all 0.25s ease;
    box-sizing: border-box;
}
.comment-form textarea:focus {
    border-color: #6366f1;
    background: #ffffff;
    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.05);
}
.comment-submit {
    margin-top: 16px;
    padding: 12px 28px;
    background: #6366f1;
    color: #ffffff;
    border: none;
    border-radius: 14px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.2);
    transition: all 0.2s;
}
.comment-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
    background: #4f46e5;
}
.login-to-comment {
    padding: 24px;
    background: rgba(99, 102, 241, 0.05);
    border: 1px solid rgba(99, 102, 241, 0.1);
    border-radius: 16px;
    font-weight: 600;
    text-align: center;
    color: #475569;
}
.login-to-comment a {
    color: #6366f1;
    font-weight: 700;
    text-decoration: none;
}

/* Reactions design match Pic 2 icons style (Pill gradients) */
.blog-reactions {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 24px 0 10px;
    flex-wrap: wrap;
}
.react-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 40px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: .88rem;
    color: #475569;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.01);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.react-btn:hover {
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    border-color: #cbd5e1;
}
.react-btn.reacted {
    color: #ffffff;
    border-color: transparent;
}
.react-btn[data-reaction="heart"].reacted {
    background: #ef4444;
    box-shadow: 0 8px 18px rgba(239, 68, 68, 0.28);
}
.react-btn[data-reaction="fire"].reacted {
    background: #f97316;
    box-shadow: 0 8px 18px rgba(249, 115, 22, 0.28);
}
.react-btn[data-reaction="wow"].reacted {
    background: #eab308;
    box-shadow: 0 8px 18px rgba(234, 179, 8, 0.28);
}
.react-btn[data-reaction="clap"].reacted {
    background: #8b5cf6;
    box-shadow: 0 8px 18px rgba(139, 92, 246, 0.28);
}
.react-btn[data-reaction="laugh"].reacted {
    background: #22c55e;
    box-shadow: 0 8px 18px rgba(34, 197, 94, 0.28);
}
.react-btn[data-reaction="heart"] .r-emoji i { color: #ef4444; }
.react-btn[data-reaction="fire"] .r-emoji i { color: #f97316; }
.react-btn[data-reaction="wow"] .r-emoji i { color: #ca8a04; }
.react-btn[data-reaction="clap"] .r-emoji i { color: #8b5cf6; }
.react-btn[data-reaction="laugh"] .r-emoji i { color: #22c55e; }
.react-btn.reacted .r-count {
    color: #ffffff;
}
.react-btn .r-emoji {
    font-size: 1.1rem;
}
.react-btn .r-count {
    font-size: .85rem;
    color: #64748b;
    font-weight: 700;
}

/* Footer modern look override */
footer {
    background: #ffffff !important;
    border: none !important;
    border-top: 1px solid #f1f5f9 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    margin: 80px 0 0 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    padding: 40px 80px !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    backdrop-filter: none !important;
}
footer div {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 0.85rem !important;
    color: #94a3b8 !important;
    font-weight: 600 !important;
}
footer .footer-links {
    display: flex !important;
    gap: 32px !important;
}
footer .footer-links a {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    color: #475569 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    text-decoration: none !important;
    transition: color 0.2s !important;
}
footer .footer-links a:hover {
    color: #6366f1 !important;
}
@media (max-width: 768px) {
    footer {
        flex-direction: column !important;
        gap: 20px !important;
        padding: 30px 20px !important;
        text-align: center !important;
    }
    footer .footer-links {
        flex-wrap: wrap !important;
        justify-content: center !important;
        gap: 16px 20px !important;
    }
}

/* Reaction Icons Micro-Animations */
@keyframes heartBeat {
    0% { transform: scale(1); }
    14% { transform: scale(1.25); }
    28% { transform: scale(1); }
    42% { transform: scale(1.25); }
    70% { transform: scale(1); }
}
@keyframes fireFlicker {
    0%, 100% { transform: rotate(-5deg) scale(1); }
    50% { transform: rotate(5deg) scale(1.1); }
}
@keyframes wowBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-3px); }
}
.react-btn[data-reaction="heart"]:hover .r-emoji i, .react-btn[data-reaction="heart"].reacted .r-emoji i {
    animation: heartBeat 1s infinite;
    color: #ef4444;
}
.react-btn[data-reaction="fire"]:hover .r-emoji i, .react-btn[data-reaction="fire"].reacted .r-emoji i {
    animation: fireFlicker 0.4s infinite alternate;
    color: #f97316;
}
.react-btn[data-reaction="wow"]:hover .r-emoji i, .react-btn[data-reaction="wow"].reacted .r-emoji i {
    animation: wowBounce 0.6s infinite;
    color: #eab308;
}
.react-btn[data-reaction="clap"]:hover .r-emoji i, .react-btn[data-reaction="clap"].reacted .r-emoji i {
    animation: wowBounce 0.5s infinite;
    color: #8b5cf6;
}
.react-btn[data-reaction="laugh"]:hover .r-emoji i, .react-btn[data-reaction="laugh"].reacted .r-emoji i {
    animation: heartBeat 1.1s infinite;
    color: #22c55e;
}
.react-btn.reacted .r-emoji i {
    color: #ffffff !important;
}
.r-emoji i {
    transition: color 0.2s;
}

/* Language Toggle UI */
.lang-toggle-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(241, 245, 249, 0.6);
    border-radius: 30px;
    padding: 4px;
    border: 1px solid #e2e8f0;
}
.lang-btn {
    padding: 6px 14px;
    border-radius: 20px;
    border: none;
    background: transparent;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 0.75rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}
.lang-btn.active {
    background: #ffffff;
    color: #6366f1;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.meta-flex-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 36px;
    flex-wrap: wrap;
    gap: 16px;
}
.blog-meta-pills {
    margin-bottom: 0 !important;
}

/* Final article polish — must sit last so mobile sizes actually win */
.blog-content table {
    display: table;
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}
.blog-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 18px 0;
    border: 1px solid #e8edf3;
    border-radius: 14px;
    background: #fff;
}
.blog-content th,
.blog-content td {
    border-bottom: 1px solid #eef2f6;
    border-right: 1px solid #eef2f6;
    padding: 11px 14px;
}
.blog-content tr:last-child td { border-bottom: 0; }
.blog-content th:last-child,
.blog-content td:last-child { border-right: 0; }
.blog-content th {
    background: #f7f8fb;
    font-size: 0.78rem;
    letter-spacing: .04em;
    text-transform: none;
    white-space: normal;
}
@media (max-width: 720px) {
    .blog-detail-wrap { padding: 64px 10px 40px !important; }
    .blog-paper {
        padding: 16px 14px 18px !important;
        border-radius: 14px !important;
        margin-top: 4px !important;
        box-shadow: 0 6px 18px rgba(0,0,0,.04) !important;
    }
    .blog-detail-title {
        font-size: 1.22rem !important;
        letter-spacing: -0.4px !important;
        margin: 0 0 10px !important;
        line-height: 1.28 !important;
        font-weight: 800 !important;
    }
    .blog-detail-meta { margin-bottom: 10px !important; gap: 8px !important; }
    .blog-author-avatar { width: 32px !important; height: 32px !important; }
    .blog-author-name { font-size: 0.82rem !important; }
    .blog-detail-date { font-size: 0.72rem !important; }
    .blog-meta-pills { margin-bottom: 12px !important; gap: 6px !important; }
    .meta-pill { padding: 4px 10px !important; font-size: 0.68rem !important; }
    .read-size-bar { margin: 0 0 0 auto; }
    .blog-content {
        font-size: 0.92rem !important;
        line-height: 1.62 !important;
    }
    .blog-content p { margin-bottom: 11px !important; }
    .blog-content h1 { font-size: 1.12rem !important; margin-top: 18px !important; margin-bottom: 8px !important; }
    .blog-content h2 { font-size: 1.05rem !important; margin-top: 18px !important; margin-bottom: 8px !important; padding-bottom: 0 !important; border-bottom: 0 !important; }
    .blog-content h3 { font-size: 0.98rem !important; margin-top: 14px !important; }
    .blog-content ul, .blog-content ol { padding-left: 16px !important; margin-bottom: 10px !important; }
    .blog-content li { margin-bottom: 5px !important; }
    .blog-content blockquote { padding: 10px 12px !important; margin: 14px 0 !important; font-size: 0.9rem !important; }
    .blog-content th, .blog-content td { padding: 8px 10px !important; font-size: 0.8rem !important; }
    html.blog-read-little .blog-content { font-size: 1.02rem !important; line-height: 1.7 !important; }
    html.blog-read-medium .blog-content { font-size: 1.12rem !important; line-height: 1.75 !important; }
}
</style>
<link rel="stylesheet" href="css/blog-header-logo.css?v=20260758">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel='preload' href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' as='style' onload='this.onload=null;this.rel="stylesheet"'>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Article Schema -->
    <script type="application/ld+json">
    <?= json_encode([
        '@context'      => 'https://schema.org',
        '@type'         => 'Article',
        'headline'      => $blog['meta_title'] ?? $blog['title'],
        'description'   => $blog['meta_description'] ?? ($blog['description'] ?? ''),
        'image'         => !empty($blog['image_path']) ? 'https://arigatodevan.com/' . ltrim($blog['image_path'], '/') : 'https://arigatodevan.com/landingpics/lan9.webp',
        'url'           => 'https://arigatodevan.com/blog.php?slug=' . $blog['slug'],
        'author'        => ['@type' => 'Person', 'name' => $blog['author_name'] ?? 'Arigato Devan'],
        'publisher'     => ['@type' => 'Organization', 'name' => 'Arigato Devan', 'url' => 'https://arigatodevan.com'],
        'datePublished' => date('c', strtotime($blog['created_at'])),
        'inLanguage'    => 'en',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://arigatodevan.com"},{"@type":"ListItem","position":2,"name":"Blogs","item":"https://arigatodevan.com/blogs.php"},{"@type":"ListItem","position":3,"name":"<?= htmlspecialchars(
        addslashes($blog["meta_title"] ?? $blog["title"]),
    ) ?>","item":"https://arigatodevan.com/blog.php?slug=<?= urlencode(
    $blog["slug"],
) ?>"}]}
    </script>
    <?php include_once "gtag.php"; ?>
<style id="blog-article-mobile">
.blog-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: #64748b;
    text-decoration: none;
    margin: 0 0 18px;
    font-size: 0.8rem;
    letter-spacing: 0.02em;
}
.blog-back-link:hover { color: #0f172a; }
.blog-byline { min-width: 0; flex: 1; }
.blog-detail-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: nowrap;
}
.read-size-bar {
    margin: 0 0 0 auto;
    flex-shrink: 0;
    background: #f8fafc;
    border: 1px solid #e8edf3;
    border-radius: 999px;
    padding: 3px;
    gap: 2px;
    width: auto;
}
.read-size-btn {
    border: 0;
    background: transparent;
    min-width: 32px;
    padding: 5px 8px;
}
.read-size-btn.is-on {
    background: #fff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(15,23,42,.08);
}

@media (max-width: 720px) {
    html { -webkit-text-size-adjust: 100%; }
    body.page-store.theme-nogoda { background: #fff !important; }
    .aurora-bg,
    .back-glow,
    .scroll-bg-container { display: none !important; }
    .blog-detail-wrap {
        max-width: none !important;
        padding: 70px 0 40px !important;
    }
    .blog-back-link {
        margin: 0 18px 6px;
        font-size: 0.78rem;
        min-height: 36px;
    }
    .blog-detail-hero-img {
        border-radius: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
        margin: 0 0 8px !important;
        max-height: 220px !important;
        width: 100%;
        object-fit: cover;
    }
    .blog-paper {
        background: #fff !important;
        padding: 4px 18px 40px !important;
        padding-left: max(18px, env(safe-area-inset-left)) !important;
        padding-right: max(18px, env(safe-area-inset-right)) !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
    }
    h1.blog-detail-title {
        font-size: 1.48rem !important;
        line-height: 1.25 !important;
        font-weight: 800 !important;
        letter-spacing: -0.03em !important;
        margin: 8px 0 14px !important;
        overflow-wrap: anywhere;
    }
    .blog-detail-meta {
        display: grid !important;
        grid-template-columns: auto 1fr auto;
        grid-template-areas: "avatar byline size";
        gap: 10px 10px !important;
        align-items: center !important;
        margin: 0 0 16px !important;
        padding-bottom: 14px !important;
        border-bottom: 1px solid #eef2f6;
        flex-wrap: unset !important;
    }
    .blog-author-avatar {
        grid-area: avatar;
        width: 36px !important;
        height: 36px !important;
        border: 0 !important;
    }
    .blog-byline { grid-area: byline; }
    .blog-author-name {
        font-size: 0.88rem !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
    }
    .blog-detail-date {
        font-size: 0.72rem !important;
        font-weight: 500 !important;
        margin-top: 2px !important;
        color: #64748b !important;
        line-height: 1.3 !important;
    }
    .read-size-bar {
        grid-area: size;
        margin: 0 !important;
        width: auto !important;
        max-width: none;
        justify-content: flex-end;
        padding: 2px !important;
        background: #f1f5f9 !important;
    }
    .read-size-btn {
        min-width: 28px !important;
        min-height: 32px !important;
        padding: 4px 7px !important;
        font-size: 0.68rem !important;
        border-radius: 999px;
    }
    .blog-meta-pills { margin: 0 0 14px !important; gap: 6px !important; }
    .meta-pill { padding: 4px 10px !important; font-size: 0.68rem !important; }
    .blog-content {
        font-size: 1.0625rem !important;
        line-height: 1.7 !important;
        color: #1e293b !important;
        overflow-wrap: break-word;
    }
    .blog-content p { margin-bottom: 1em !important; }
    .blog-content h2 { font-size: 1.18rem !important; margin: 1.35em 0 0.5em !important; border: 0 !important; padding: 0 !important; }
    .blog-content h3 { font-size: 1.05rem !important; margin: 1.15em 0 0.4em !important; }
    .blog-content ul, .blog-content ol { padding-left: 1.2em !important; margin-bottom: 1em !important; }
    .blog-content li { margin-bottom: 0.35em !important; }
    .blog-content img { border-radius: 10px !important; margin: 12px 0 !important; }
    .blog-content blockquote {
        margin: 1.15em 0 !important;
        padding: 2px 0 2px 12px !important;
        font-size: 1rem !important;
        background: transparent !important;
        border-radius: 0 !important;
        border-left: 3px solid #c4b5fd !important;
        color: #475569 !important;
    }
    .blog-table-wrap {
        margin: 14px -6px !important;
        border-radius: 10px !important;
    }
    .blog-reactions { gap: 8px !important; margin: 22px 0 12px !important; }
    .blog-action-bar {
        flex-wrap: wrap !important;
        gap: 10px !important;
        margin: 8px 0 20px !important;
    }
    .comments-section { margin-top: 8px !important; }
    .comments-section h3 { font-size: 1.05rem !important; }
    .comment-form textarea { min-height: 88px; }
    html.blog-read-little .blog-content { font-size: 1.15rem !important; line-height: 1.75 !important; }
    html.blog-read-medium .blog-content { font-size: 1.26rem !important; line-height: 1.8 !important; }
}
</style>
</head>
<body class="page-store theme-nogoda bm-article">
<!-- Blog portal splash loader -->
<div id="blog-splash-screen" class="blog-splash-screen" role="status" aria-live="polite" aria-busy="true">
    <div class="splash-content">
        <p class="splash-typewriter" id="splash-typewriter">
            <span class="splash-prefix">arigato.</span><span class="splash-suffix splash-suffix--prompt" id="splash-suffix">prompt</span><span class="splash-cursor" id="splash-cursor" aria-hidden="true">|</span>
        </p>
        <div class="splash-loading-bar" aria-hidden="true">
            <span class="splash-loading-bar-fill" id="splash-bar-fill"></span>
        </div>
        <p class="splash-loading-label" id="splash-loading-label">LOADING CREATIVE REALM</p>
    </div>
</div>

<div class="aurora-bg"></div>
<div class="back-glow" id="back-glow"></div>
    <!-- Scrollable Wallpaper Background -->
    <div class="scroll-bg-container" id="scroll-bg-container">
        <div class="bg-layer active" style="background-image: url('https://i.pinimg.com/736x/4d/e2/71/4de271ae9997273cf3fdd47098fa69a3.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/1200x/76/50/aa/7650aa986d34ca65bb52f261f954149b.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/1200x/64/c4/c5/64c4c528ee5812610d58ee2c98bbb76f.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/736x/f9/fd/75/f9fd75e5aa551b89ac88a863921f2f75.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/736x/a5/15/6a/a5156a264e06ebb47997cf59e66bee31.jpg')"></div>
        <div class="bg-creamy-overlay"></div>
    </div>
<?php $nav_active = 'blogs'; include 'includes/site_nav.php'; ?>

<div class="ba-page">
  <div class="ba-hero<?= $has_cover ? ' has-photo' : '' ?>">
    <?php if ($has_cover): ?>
    <div class="ba-gallery is-single">
      <div class="ba-gallery-main">
        <picture class="ba-cover-pic">
          <?php if ($cover_landscape !== ''): ?>
          <source media="(min-width: 721px)" srcset="<?= htmlspecialchars($cover_landscape) ?>">
          <?php endif; ?>
          <img loading="lazy" src="<?= htmlspecialchars($cover_portrait !== '' ? $cover_portrait : $cover_landscape) ?>" alt="<?= htmlspecialchars($blog["title"]) ?>">
        </picture>
      </div>
    </div>
    <?php endif; ?>

    <div class="ba-hero-copy">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap;">
        <a href="blogs.php" class="ba-kicker" style="margin-bottom:0;"><i class="fa-solid fa-arrow-left"></i> Blogs</a>
        <?php 
        $blog_cats = !empty($blog['category']) ? array_filter(array_map('trim', explode(',', $blog['category']))) : [];
        foreach ($blog_cats as $bcat):
          if ($bcat !== 'Uncategorized'):
        ?>
          <span style="font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; background:#e0f2fe; color:#0284c7; padding:3px 10px; border-radius:9999px; border:1px solid #bae6fd; display:inline-flex; align-items:center; gap:5px;">
            <i class="fa-solid fa-folder-open" style="font-size:0.68rem;"></i> <?= htmlspecialchars($bcat) ?>
          </span>
        <?php 
          endif;
        endforeach; 
        ?>
      </div>
      <div class="ba-head">
        <h1><?= htmlspecialchars($blog["title"]) ?></h1>
        <div class="ba-tools ba-tools-desk">
          <div class="read-size-bar" role="group" aria-label="Reading size">
            <button type="button" class="read-size-btn is-on" data-size="default" title="Default size">A</button>
            <button type="button" class="read-size-btn" data-size="little" title="Larger">A+</button>
            <button type="button" class="read-size-btn" data-size="medium" title="Largest">A++</button>
          </div>
        </div>
      </div>
      <div class="ba-byline">
        <img loading="lazy" src="<?= htmlspecialchars($author_av) ?>" alt="">
        <div>
          <strong><?= htmlspecialchars($blog["author_name"] ?? "Admin") ?></strong>
          <div><?= date("M j, Y", strtotime($blog["created_at"])) ?> · <?= $read_time ?> min read</div>
        </div>
      </div>
    </div>
  </div>

  <div class="ba-sheet">
    <div class="ba-share">
      <div class="read-size-bar" role="group" aria-label="Reading size">
        <button type="button" class="read-size-btn is-on" data-size="default" title="Default size">A</button>
        <button type="button" class="read-size-btn" data-size="little" title="Larger">A+</button>
        <button type="button" class="read-size-btn" data-size="medium" title="Largest">A++</button>
      </div>
      <span class="ba-share-comments"><?= count($comments) ?> comments</span>
    </div>

  <div class="ba-layout">
    <div>
      <?php if(!empty($blog["content_hindi"])): ?>
      <div class="meta-flex-row" style="margin-bottom:20px;">
        <div class="lang-toggle-wrapper" id="lang-toggle">
          <button class="lang-btn active" id="btn-en" onclick="switchLang('en')"><i class="fa-solid fa-language"></i> English</button>
          <button class="lang-btn" id="btn-hi" onclick="switchLang('hi')"><i class="fa-solid fa-language"></i> Hindi / Hinglish</button>
        </div>
      </div>
      <?php endif; ?>
      <div class="blog-content ba-content" id="blog-content-en"><?= $blog_content_en ?></div>
      <?php if(!empty($blog["content_hindi"])): ?>
      <div class="blog-content ba-content" id="blog-content-hi" style="display:none;"><?= $blog_content_hi ?></div>
      <?php endif; ?>
    </div>

    <aside class="ba-side">
      <div class="ba-panel">
        <h3>Highlights</h3>
        <ul class="ba-hl">
          <?php if (!empty($blog['category']) && $blog['category'] !== 'Uncategorized'): ?>
          <li><i class="fa-solid fa-folder" style="color:#0284c7;"></i> Category: <?= htmlspecialchars($blog['category']) ?></li>
          <?php endif; ?>
          <li><i class="fa-solid fa-check"></i> <?= $read_time ?> minute read</li>
          <li><i class="fa-solid fa-check"></i> <?= (int)($blog["view_count"] ?? 0) ?> views</li>
          <?php foreach (array_slice($tags_list, 0, 5) as $tag): ?>
          <li><i class="fa-solid fa-check"></i> <?= htmlspecialchars($tag) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="ba-panel">
        <div class="ba-author">
          <img loading="lazy" src="<?= htmlspecialchars($author_av) ?>" alt="">
          <div>
            <strong><?= htmlspecialchars($blog["author_name"] ?? "Admin") ?></strong>
            <span>Writer</span>
          </div>
        </div>
        <div class="ba-stats">
          <div>
            <b><?= (int) $blog["likes_count"] ?></b>
            <span>Likes</span>
          </div>
          <div>
            <b><?= count($comments) ?></b>
            <span>Comments</span>
          </div>
        </div>
      </div>
      <div class="ba-panel">
        <div class="blog-reactions" id="blog-reactions">
          <?php foreach (['heart'=>'<i class="fa-solid fa-heart"></i>','fire'=>'<i class="fa-solid fa-fire"></i>','wow'=>'<i class="fa-solid fa-face-surprise"></i>','clap'=>'<i class="fa-solid fa-hands-clapping"></i>','laugh'=>'<i class="fa-solid fa-face-laugh-beam"></i>'] as $rtype=>$remoji): ?>
          <button class="react-btn <?= in_array($rtype,$my_reactions)?'reacted':'' ?>" data-reaction="<?= $rtype ?>" data-blog="<?= $blog['id'] ?>">
            <span class="r-emoji"><?= $remoji ?></span>
            <span class="r-count" id="rc-<?= $rtype ?>"><?= $reaction_counts[$rtype] ?></span>
          </button>
          <?php endforeach; ?>
        </div>
        <button class="blog-like-btn <?= $user_liked ? "liked" : "" ?>" id="blog-like-btn" data-blog-id="<?= $blog["id"] ?>" style="margin-top:12px;width:100%;justify-content:center;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $user_liked ? "#fff" : "none" ?>" stroke="currentColor" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
          <span id="blog-like-count"><?= (int) $blog["likes_count"] ?></span> Likes
        </button>
      </div>
    </aside>
  </div>

  <div class="ba-comments comments-section">
    <h3>Comments (<?= count($comments) ?>)</h3>
    <?php if (count($comments) > 0): ?>
    <div id="comments-list">
      <?php foreach ($comments as $c): ?>
      <div class="comment-item">
        <?= renderAvatar($c["profile_image"] ?? "", "comment-avatar", "") ?>
        <div class="comment-body">
          <div class="comment-name"><?= htmlspecialchars($c["username"] ?? "User") ?></div>
          <div class="comment-text"><?= nl2br(htmlspecialchars($c["comment"])) ?></div>
          <div class="comment-time"><?= date("d M Y H:i", strtotime($c["created_at"])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p id="no-comments-msg" style="color:#aaa;font-weight:600;margin-bottom:20px;">Be the first to comment.</p>
    <?php endif; ?>
    <?php if (isset($_SESSION["user_id"])): ?>
    <div class="comment-form">
      <textarea id="comment-input" placeholder="Share your thoughts..."></textarea>
      <button class="comment-submit" id="comment-submit-btn" data-blog-id="<?= $blog["id"] ?>">Post Comment <i class="fa-solid fa-paper-plane"></i></button>
    </div>
    <?php else: ?>
    <div class="login-to-comment">Login to leave a comment <a href="login.php">Sign in with Google</a></div>
    <?php endif; ?>
  </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script defer src="script.min.js?v=20260616"></script>
<script>
// Live Code Copy Engine
function copyCodeText(btn) {
  const block = btn.closest('.blog-code-block');
  if (!block) return;
  let pre = block.querySelector('.code-content');
  if (!pre) pre = block.querySelector('pre');
  if (!pre) return;
  const code = pre.innerText;

  function showCopied(button) {
    const originalText = button.innerHTML;
    button.innerHTML = `<i class="fa-solid fa-check" style="color:#10b981;"></i> Copied!`;
    button.style.background = '#065f46';
    button.style.color = '#ffffff';
    setTimeout(() => {
      button.innerHTML = originalText;
      button.style.background = '#1e293b';
      button.style.color = '#cbd5e1';
    }, 2000);
  }

  function fallbackCopy(text, button) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      showCopied(button);
    } catch(e) {
      alert('Could not copy code.');
    }
    document.body.removeChild(ta);
  }

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(code).then(() => showCopied(btn)).catch(() => fallbackCopy(code, btn));
  } else {
    fallbackCopy(code, btn);
  }
}

 // Delegate copy button clicks (works even if inline onclick is stripped)
 document.addEventListener('click', function(e){
   var btn = e.target.closest('.blog-code-block button');
   if (!btn) return;
   e.preventDefault();
   try { copyCodeText(btn); } catch(err) {}
 });

// Blog Like
function showToast(msg) {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#fff1b8;color:#2d2a35;border:3px solid #2d2a35;border-radius:14px;padding:12px 22px;font-weight:800;font-family:Outfit,sans-serif;box-shadow:4px 4px 0px #2d2a35;z-index:9999;font-size:.95rem;transition:opacity .3s';
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 2500);
}
const likeBtn = document.getElementById('blog-like-btn');
if (likeBtn) {
  likeBtn.addEventListener('click', () => {
    <?php if (!isset($_SESSION["user_id"])): ?>
    showToast('Login first to like!'); return;
    <?php endif; ?>
    const blogId = likeBtn.dataset.blogId;
    const fd = new FormData(); fd.append('blog_id', blogId);
    fetch('blog_like.php', {method:'POST', body:fd})
      .then(r=>r.json()).then(d=>{
        if(d.success){
          document.getElementById('blog-like-count').textContent = d.likes_count;
          likeBtn.classList.toggle('liked', d.action==='liked');
          const svg = likeBtn.querySelector('svg');
          svg.setAttribute('fill', d.action==='liked' ? '#fff' : 'none');
          svg.setAttribute('stroke', d.action==='liked' ? '#fff' : 'currentColor');
        }
      });
  });
}

// Blog Comment
const submitBtn = document.getElementById('comment-submit-btn');
if (submitBtn) {
  submitBtn.addEventListener('click', () => {
    const input = document.getElementById('comment-input');
    const text = input.value.trim();
    if (!text) return;
    submitBtn.disabled = true; submitBtn.textContent = 'Posting...';
    const fd = new FormData();
    fd.append('blog_id', submitBtn.dataset.blogId);
    fd.append('comment', text);
    fetch('blog_comment.php', {method:'POST', body:fd})
      .then(r=>r.json()).then(d=>{
        if(d.success){
          const noMsg = document.getElementById('no-comments-msg');
          if(noMsg) noMsg.remove();
          let list = document.getElementById('comments-list');
          if(!list){ list = document.createElement('div'); list.id='comments-list'; submitBtn.closest('.comment-form').before(list); }
          const el = document.createElement('div'); el.className='comment-item';
          el.innerHTML = `<img loading="lazy" src="${d.avatar}" class="comment-avatar" alt=""><div class="comment-body"><div class="comment-name">${d.username}</div><div class="comment-text">${d.comment.replace(/\n/g,'<br>')}</div><div class="comment-time">${d.time}</div></div>`;
          list.appendChild(el);
          input.value='';
          // Update count
          const h3 = document.querySelector('.comments-section h3');
          if(h3) h3.innerHTML = '<i class="fa-solid fa-comment"></i> Comments (' + list.children.length + ')';
        }
        submitBtn.disabled=false; submitBtn.innerHTML='Post Comment <i class="fa-solid fa-paper-plane"></i>';
      });
  });
}

// Blog Reactions
document.querySelectorAll('.react-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    var fd = new FormData();
    fd.append('blog_id', btn.dataset.blog);
    fd.append('reaction', btn.dataset.reaction);
    // Optimistic toggle
    var wasReacted = btn.classList.contains('reacted');
    btn.classList.toggle('reacted', !wasReacted);
    var countEl = btn.querySelector('.r-count');
    countEl.textContent = parseInt(countEl.textContent||0) + (wasReacted ? -1 : 1);
    fetch('react.php', {method:'POST', body:fd})
      .then(function(r){return r.json();})
      .then(function(d){
        if(d.ok && d.counts){
          Object.keys(d.counts).forEach(function(r){
            var el = document.getElementById('rc-'+r);
            if(el) el.textContent = d.counts[r];
          });
          document.querySelectorAll('.react-btn').forEach(function(b){
            if(b.dataset.reaction === d.reaction){
              b.classList.toggle('reacted', d.active);
            }
          });
        }
      }).catch(function(){
        // revert on error
        btn.classList.toggle('reacted', wasReacted);
        countEl.textContent = parseInt(countEl.textContent||0) + (wasReacted ? 1 : -1);
      });
  });
});

// Language Toggle
function switchLang(lang) {
  var enEl = document.getElementById('blog-content-en');
  var hiEl = document.getElementById('blog-content-hi');
  var btnEn = document.getElementById('btn-en');
  var btnHi = document.getElementById('btn-hi');
  if (!enEl || !hiEl) return;
  if (lang === 'hi') {
    enEl.style.display = 'none';
    hiEl.style.display = '';
    btnHi.classList.add('active');
    btnEn.classList.remove('active');
  } else {
    hiEl.style.display = 'none';
    enEl.style.display = '';
    btnEn.classList.add('active');
    btnHi.classList.remove('active');
  }
}
</script>
<script src="js/blog-splash.js?v=20260756" defer></script>
<script>
// Interactive Ambient Mouse Glow Tracker
document.addEventListener('mousemove', (e) => {
    const glow = document.getElementById('back-glow');
    if (glow) {
        glow.style.setProperty('--x', e.clientX + 'px');
        glow.style.setProperty('--y', e.clientY + 'px');
    }
});

// Toggle mobile menu drawer
const dotsToggle = document.getElementById('mobile-dots-toggle');
const header = document.querySelector('header');
if (dotsToggle && header) {
    dotsToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        header.classList.toggle('menu-open');
    });
    document.addEventListener('click', (e) => {
        if (!header.contains(e.target)) {
            header.classList.remove('menu-open');
        }
    });
}

if (typeof gtag !== 'undefined') {
    gtag('event', 'blog_read', { blog_slug: '<?= addslashes($blog["slug"]) ?>', blog_title: '<?= addslashes($blog["title"] ?? "") ?>' });
}

document.querySelectorAll('.blog-content table').forEach(function(table) {
    if (table.parentElement && table.parentElement.classList.contains('blog-table-wrap')) return;
    var wrap = document.createElement('div');
    wrap.className = 'blog-table-wrap';
    table.parentNode.insertBefore(wrap, table);
    wrap.appendChild(table);
});

(function() {
    var key = 'blogReadSize';
    var saved = localStorage.getItem(key) || 'default';
    function applySize(size) {
        document.documentElement.classList.remove('blog-read-little', 'blog-read-medium');
        if (size === 'little') document.documentElement.classList.add('blog-read-little');
        if (size === 'medium') document.documentElement.classList.add('blog-read-medium');
        document.querySelectorAll('.read-size-btn').forEach(function(btn) {
            btn.classList.toggle('is-on', btn.getAttribute('data-size') === size);
        });
        localStorage.setItem(key, size);
    }
    document.querySelectorAll('.read-size-btn').forEach(function(btn) {
        btn.addEventListener('click', function() { applySize(btn.getAttribute('data-size')); });
    });
    applySize(saved);
})();

// Auto-attach [Hide / Show] toggle button to Table of Contents
document.querySelectorAll('.blog-toc-box').forEach(function(box) {
    var title = box.querySelector('.blog-toc-title');
    var list = box.querySelector('ol, ul');
    if (title && list && !box.querySelector('.blog-toc-toggle')) {
        var toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'blog-toc-toggle';
        toggleBtn.innerHTML = '<span class="toc-toggle-text">Hide</span> <i class="fa-solid fa-chevron-up" style="font-size:0.7rem;"></i>';
        toggleBtn.onclick = function() {
            var isHidden = list.style.display === 'none';
            list.style.display = isHidden ? '' : 'none';
            toggleBtn.querySelector('.toc-toggle-text').textContent = isHidden ? 'Hide' : 'Show';
            var icon = toggleBtn.querySelector('i');
            if (icon) icon.className = isHidden ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down';
        };
        title.appendChild(toggleBtn);
    }
});
</script>
</body></html>

