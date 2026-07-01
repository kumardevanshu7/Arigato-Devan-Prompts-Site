<?php
session_start();
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
$reaction_counts = ['heart'=>0,'fire'=>0,'wow'=>0];
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
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#c084fc">
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($blog["meta_title"] ?? $blog["title"]) ?> &ndash; Arigato Devan Prompts</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
<meta name="description" content="<?= htmlspecialchars(
    $blog["meta_description"] ?? ($blog["description"] ?? ""),
) ?>">
<?php if ($blog["tags"]): ?><meta name="keywords" content="<?= htmlspecialchars(
    $blog["tags"],
) ?>"><?php endif; ?>
<?php
    $blog_url     = 'https://arigatodevan.com/blog.php?slug=' . urlencode($blog['slug']);
    $blog_og_img  = !empty($blog['image_path'])
                    ? 'https://arigatodevan.com/' . ltrim($blog['image_path'], '/')
                    : 'https://arigatodevan.com/landingpics/lan9.webp';
    $blog_og_desc = htmlspecialchars($blog['meta_description'] ?? ($blog['description'] ?? substr(strip_tags($blog['content'] ?? ''), 0, 155)));
    $blog_og_title = htmlspecialchars(($blog['meta_title'] ?? $blog['title']) . ' ñ Arigato Devan');
?>
<!-- Canonical -->
<link rel="canonical" href="<?= $blog_url ?>">
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
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap');

body {
    background-color: #f1f5f9 !important; /* Neutral light-gray base */
    font-family: 'Inter', sans-serif !important;
    color: #1e293b !important;
    position: relative !important;
}

/* Luxury GSAP Splash Screen Loader Styles */
.blog-splash-screen {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: #090c15 !important; /* Premium dark background */
    z-index: 9999999 !important;
    display: flex;
    align-items: center !important;
    justify-content: center !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    overflow: hidden !important;
    clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%); /* Initial curtain down */
}
.splash-content {
    text-align: center !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    gap: 32px !important;
    perspective: 1000px !important;
}
.splash-logo-container {
    display: flex !important;
    align-items: center !important;
    gap: 20px !important;
    font-size: 2.8rem !important;
    font-weight: 900 !important;
    letter-spacing: -2px !important;
}
/* Word & Letter staggers */
.splash-word {
    display: flex !important;
    gap: 4px !important;
}
.splash-word span {
    display: inline-block !important;
    opacity: 0;
    transform: translateY(30px) scale(0.6);
    filter: blur(10px);
}
.prompt-word span {
    color: #e2e8f0 !important;
    text-shadow: 0 0 20px rgba(255,255,255,0.1) !important;
}
.blog-word span {
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    filter: drop-shadow(0 0 15px rgba(168,85,247,0.4)) !important;
}
/* Spinning Neon Ring Loader & Arrow */
.splash-arrow-wrap {
    position: relative !important;
    width: 80px !important;
    height: 80px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.splash-arrow {
    font-size: 1.8rem !important;
    color: #6366f1 !important;
    opacity: 0;
    transform: scale(0.3) rotate(-180deg);
    text-shadow: 0 0 25px rgba(99,102,241,0.8) !important;
    position: absolute !important;
    z-index: 10 !important;
}
/* Neon Ring Spinning Loader */
.splash-ring-loader {
    width: 68px !important;
    height: 68px !important;
    border: 3px solid rgba(99, 102, 241, 0.08) !important;
    border-top: 3px solid #6366f1 !important;
    border-right: 3px solid #a855f7 !important;
    border-radius: 50% !important;
    position: absolute !important;
    box-shadow: 0 0 20px rgba(168, 85, 247, 0.25) !important;
    opacity: 0;
    transform: scale(0.5);
}
.splash-loading-label {
    font-size: 0.8rem !important;
    font-weight: 800 !important;
    color: #475569 !important;
    text-transform: uppercase !important;
    letter-spacing: 3px !important;
    opacity: 0;
    transform: translateY(15px);
}

/* Responsive adjustments for Splash Screen */
@media (max-width: 768px) {
    .splash-logo-container {
        font-size: 2rem !important; /* Scale down on tablets */
        gap: 12px !important;
    }
    .splash-arrow-wrap {
        width: 60px !important;
        height: 60px !important;
    }
    .splash-ring-loader {
        width: 50px !important;
        height: 50px !important;
        border-width: 2px !important;
    }
    .splash-arrow {
        font-size: 1.4rem !important;
    }
}
@media (max-width: 480px) {
    .splash-logo-container {
        font-size: 1.4rem !important; /* Snug fit on mobile screens under 480px */
        gap: 8px !important;
    }
    .splash-arrow-wrap {
        width: 44px !important;
        height: 44px !important;
    }
    .splash-ring-loader {
        width: 36px !important;
        height: 36px !important;
        border-width: 2px !important;
    }
    .splash-arrow {
        font-size: 1rem !important;
    }
    .splash-loading-label {
        font-size: 0.65rem !important;
        letter-spacing: 2px !important;
    }
}
@media (max-width: 360px) {
    .splash-logo-container {
        font-size: 1.2rem !important; /* Perfect fit on ultra-small mobile screens */
        gap: 6px !important;
    }
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
header {
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
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-weight: 800 !important;
    font-size: 0.95rem !important;
    letter-spacing: -0.5px !important;
    color: #0f172a !important;
    line-height: 1.1 !important;
}
header .logo-flipper {
    display: block !important;
    width: 44px !important;
    height: 44px !important;
    position: relative !important;
    transform-style: preserve-3d !important;
    transition: transform 0.6s !important;
    flex-shrink: 0 !important;
}
header .logo-front, header .logo-back {
    position: absolute !important;
    width: 100% !important;
    height: 100% !important;
    backface-visibility: hidden !important;
    border-radius: 50% !important;
    overflow: hidden !important;
}
header .logo-back {
    transform: rotateY(180deg) !important;
}
header .logo-front img, header .logo-back img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 50% !important;
}
header .logo-area:hover .logo-flipper {
    transform: rotateY(180deg) !important;
}
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
    header {
        padding: 0 24px !important; /* Center elements vertically inside a 64px capsule */
        margin: 15px 16px 0 !important;
        border-radius: 20px !important;
        height: 64px !important; /* Exact mathematical height to match desktop perfectly */
        min-height: 64px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        overflow: hidden !important;
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
        padding: 80px 16px 60px; /* Mobile responsive padding */
    }
}

/* Robust responsive floating header styling for mobile */
@media (max-width: 768px) {
    header {
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
        font-size: 0.8rem !important;
    }
    header .logo-flipper {
        width: 32px !important;
        height: 32px !important;
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
    font-size: clamp(2.2rem, 5vw, 3.2rem);
    font-weight: 900;
    line-height: 1.2;
    margin-top: 5px;
    margin-bottom: 22px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    letter-spacing: -1.5px;
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
        padding: 30px 20px !important;
    }
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
.blog-content img { max-width: 100%; height: auto; border-radius: 12px; }
.blog-content .font-mono { font-family: monospace; font-size: 0.95rem; background: #faf9ff; padding: 2px 6px; border-radius: 4px; border: 1px solid #dbdae5; }
.blog-content .font-bold { font-weight: 900; }
.blog-content .font-light { font-weight: 400; color: #6a6775; }
.blog-content .font-highlight { background: #FFF1B8; padding: 2px 8px; border-radius: 6px; border: 1px solid #cbd5e1; }

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
    gap: 12px;
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
    background: linear-gradient(135deg, #06b6d4 0%, #0db8a6 100%); /* Premium gradient button overlay inspired by Pic 2 */
    color: #ffffff;
    border-color: transparent;
    box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
}
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
.react-btn.reacted .r-emoji i {
    color: #ffffff !important;
}
.r-emoji i {
    transition: color 0.2s;
    color: #94a3b8;
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
</style>
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
</head>
<body>
<!-- High-End GSAP Splash Screen Loader -->
<div id="blog-splash-screen" class="blog-splash-screen">
    <div class="splash-content">
        <div class="splash-logo-container" id="splash-logo-container">
            <div class="splash-word prompt-word" id="splash-prompt-word">
                <span>P</span><span>R</span><span>O</span><span>M</span><span>P</span><span>T</span>
            </div>
            <div class="splash-arrow-wrap">
                <i class="fa-solid fa-arrow-right splash-arrow" id="splash-arrow"></i>
                <div class="splash-ring-loader" id="splash-ring-loader"></div>
            </div>
            <div class="splash-word blog-word" id="splash-blog-word">
                <span>B</span><span>L</span><span>O</span><span>G</span>
            </div>
        </div>
        <div class="splash-loading-label" id="splash-loading-label">LOADING CREATIVE REALM</div>
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
<header>
  <div class="header-top-row">
    <div class="logo-area" id="logo-container" style="cursor:pointer">
      <div class="logo-flipper">
        <div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo" id="profile-logo"></div>
        <div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div>
      </div>
      <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    
    <nav class="nav-links desktop-only">
      <a href="digital_store/index.php" class="shop-nav-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg> SHOP</a>
      <a href="gallery.php">GALLERY</a>
      <a href="blogs.php" class="active">BLOGS</a>
      <a href="progress.php" title="Our Journey" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-chart-line nav-progress-icon"></i></a>
      <a href="faq.php" title="FAQ" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-circle-question" style="font-size:1.2rem;"></i></a>
      <div class="nav-dropdown">
          <button class="nav-dropdown-btn"><i class="fa-solid fa-film"></i> Reels Type <i class="fa-solid fa-chevron-down dd-arrow"></i></button>
          <?php $curPage = basename($_SERVER["PHP_SELF"]); ?>
          <div class="nav-dropdown-menu">
              <a href="secret_code.php" <?= $curPage == "secret_code.php" ? 'style="background:var(--primary-color)"' : "" ?>><i class="fa-solid fa-lock"></i> Secret Code Reels <?= empty($nav_counts["secret_code"]) ? '<span class="dd-tag soon">SOON</span>' : ($curPage == "secret_code.php" ? '<span class="dd-tag">ACTIVE</span>' : "") ?></a>
              <a href="unreleased.php" <?= $curPage == "unreleased.php" ? 'style="background:var(--primary-color)"' : "" ?>><i class="fa-solid fa-star"></i> Unreleased Reels <?= empty($nav_counts["unreleased"]) ? '<span class="dd-tag soon">SOON</span>' : ($curPage == "unreleased.php" ? '<span class="dd-tag">ACTIVE</span>' : "") ?></a>
              <a href="insta_viral.php" <?= $curPage == "insta_viral.php" ? 'style="background:var(--primary-color)"' : "" ?>><i class="fa-brands fa-instagram"></i> Insta Viral Reels <?= empty($nav_counts["insta_viral"]) ? '<span class="dd-tag soon">SOON</span>' : ($curPage == "insta_viral.php" ? '<span class="dd-tag">ACTIVE</span>' : "") ?></a>
              <a href="already_uploaded.php" <?= $curPage == "already_uploaded.php" ? 'style="background:var(--primary-color)"' : "" ?>><i class="bx bx-history"></i> Already Uploaded <?= empty($nav_counts["already_uploaded"]) ? '<span class="dd-tag soon">SOON</span>' : ($curPage == "already_uploaded.php" ? '<span class="dd-tag">ACTIVE</span>' : "") ?></a>
                    <a href="direct_prompts.php" <?= $curPage == "direct_prompts.php" ? 'style="background:var(--primary-color)"' : "" ?>><i class="fa-solid fa-hand-pointer"></i> Direct Prompts <?= empty($nav_counts["direct"]) ? '<span class="dd-tag soon">SOON</span>' : ($curPage == "direct_prompts.php" ? '<span class="dd-tag">ACTIVE</span>' : "") ?></a>
                </div>
      </div>
      <a href="https://www.instagram.com/arigato.devan/" target="_blank" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;font-family:var(--font-main);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
        <span style="font-weight:600;">@arigato.devan</span>
        <span class="pulse-dot"></span>
        <span style="font-weight:800;font-size:1.1rem;">15K+</span>
      </a>
    </nav>
    
    <div class="header-right desktop-only">
      <div class="header-divider"></div>
      <?php if (isset($_SESSION["user_id"])): ?>
        <?php if ($_SESSION["role"] === "admin"): ?>
          <div style="display:flex; align-items:center; gap:8px;"><a href="profile.php" title="Edit Profile"><?= renderAvatar($_SESSION["profile_image"] ?? "", "admin-avatar", "Admin", 'style="transition: transform 0.2s;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"') ?></a><a href="dashboard.php" style="color:var(--text-color); font-weight:800;">ADMIN</a></div>
        <?php else: ?>
          <a href="profile.php" style="color:var(--text-color)"><?= renderAvatar($_SESSION["profile_image"] ?? "", "admin-avatar", "Profile") ?></a>
        <?php endif; ?>
        <a href="login.php?logout=1" class="logout"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> LOGOUT</a>
      <?php else: ?>
        <a href="login.php" class="comic-btn" style="font-size:.85rem;padding:9px 18px;text-decoration:none;color:var(--text-color);background:var(--primary-color);">LOGIN</a>
      <?php endif; ?>
    </div>

    <!-- macOS style 3-dot mobile toggle -->
    <button class="dots-menu-toggle" id="mobile-dots-toggle" type="button" aria-label="Toggle Menu">
      <span class="dot red"></span>
      <span class="dot yellow"></span>
      <span class="dot green"></span>
    </button>
  </div>

  <!-- Mobile Sliding Menu Drawer -->
  <div class="mobile-menu-drawer">
    <nav class="mobile-nav-links">
      <a href="index.php"><i class="fa-solid fa-house"></i> HOME</a>
      <a href="gallery.php"><i class="fa-solid fa-images"></i> GALLERY</a>
      <a href="blogs.php" class="active"><i class="fa-solid fa-feather"></i> BLOGS</a>
      <a href="progress.php"><i class="fa-solid fa-chart-line"></i> OUR JOURNEY</a>
      <a href="faq.php"><i class="fa-solid fa-circle-question"></i> FAQ</a>
      <a href="https://www.instagram.com/arigato.devan/" target="_blank"><i class="fa-brands fa-instagram"></i> INSTAGRAM (15K+)</a>
      <div style="width:100%; height:1px; background:#f1f5f9; margin:4px 0;"></div>
      <?php if (isset($_SESSION["user_id"])): ?>
        <?php if ($_SESSION["role"] === "admin"): ?>
          <a href="dashboard.php"><i class="fa-solid fa-lock"></i> ADMIN DASHBOARD</a>
        <?php else: ?>
          <a href="profile.php"><i class="fa-solid fa-user"></i> MY PROFILE</a>
        <?php endif; ?>
        <a href="login.php?logout=1" style="color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
      <?php else: ?>
        <a href="login.php" style="color:#6366f1; background:rgba(99,102,241,0.05);"><i class="fa-solid fa-right-to-bracket"></i> LOGIN</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<div class="blog-detail-wrap">
  <!-- Back link -->
  <a href="blogs.php" style="display:inline-flex;align-items:center;gap:6px;font-weight:800;color:var(--text-color);text-decoration:none;margin-bottom:28px;font-size:.9rem;opacity:.7;transition:opacity .2s" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.7"><i class="fa-solid fa-arrow-left"></i> Back to Blogs</a>

  <!-- Hero image (Fully responsive, rounded with outline border) -->
  <?php if ($blog["image_path"]): 
      $hero_ratio = ($blog["image_ratio"] ?? "16:9") === "9:16" ? "9/16" : "16/9";
  ?>
  <img loading="lazy" src="<?= htmlspecialchars($blog["image_path"]) ?>" class="blog-detail-hero-img" alt="<?= htmlspecialchars($blog["title"]) ?>" style="aspect-ratio: <?= $hero_ratio ?>;">
  <?php endif; ?>

  <div class="blog-paper">
    <!-- Title of the Post -->
    <h1 class="blog-detail-title"><?= htmlspecialchars($blog["title"]) ?></h1>

    <!-- Author & Date Meta Section -->
    <div class="blog-detail-meta">
      <img loading="lazy" src="<?= htmlspecialchars(
          !empty($blog["author_avatar"])
              ? $blog["author_avatar"]
              : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($blog["author_name"] ?? "Admin"),
      ) ?>" class="blog-author-avatar" alt="" onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=<?= urlencode($blog["author_name"] ?? "Admin") ?>'">
      <div>
        <div class="blog-author-name">Written by <?= htmlspecialchars($blog["author_name"] ?? "Admin") ?></div>
        <div class="blog-detail-date"><?= date("M d, Y", strtotime($blog["created_at"])) ?></div>
      </div>
    </div>

    <!-- Metadata Horizontal Pills (Category tags & Read time) -->
    <div class="blog-meta-pills">
      <!-- Reading Time Pill -->
      <span class="meta-pill read-pill"><i class="fa-regular fa-clock"></i> <?= $read_time ?> min read</span>
      
      <!-- Category Tags Pills -->
      <?php if ($blog["tags"]): ?>
        <?php foreach (array_filter(array_map("trim", explode(",", $blog["tags"]))) as $tag): ?>
          <span class="meta-pill tag-pill"><i class="fa-solid fa-hashtag"></i> <?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if(!empty($blog["content_hindi"])): ?>
    <!-- Language Toggle -->
    <div class="meta-flex-row" style="margin-top:24px;margin-bottom:20px;">
      <div class="lang-toggle-wrapper" id="lang-toggle">
        <button class="lang-btn active" id="btn-en" onclick="switchLang('en')"><i class="fa-solid fa-language"></i> English</button>
        <button class="lang-btn" id="btn-hi" onclick="switchLang('hi')"><i class="fa-solid fa-language"></i> Hindi / Hinglish</button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Content of the Blog Post -->
    <div class="blog-content" id="blog-content-en"><?= $blog["content"] ?></div>
    <?php if(!empty($blog["content_hindi"])): ?>
    <div class="blog-content" id="blog-content-hi" style="display:none;"><?= $blog["content_hindi"] ?></div>
    <?php endif; ?>

  <!-- Reactions -->
  <div class="blog-reactions" id="blog-reactions">
    <?php foreach (['heart'=>'<i class="fa-solid fa-heart"></i>','fire'=>'<i class="fa-solid fa-fire"></i>','wow'=>'<i class="fa-solid fa-face-surprise"></i>'] as $rtype=>$remoji): ?>
    <button class="react-btn <?= in_array($rtype,$my_reactions)?'reacted':'' ?>" data-reaction="<?= $rtype ?>" data-blog="<?= $blog['id'] ?>">
      <span class="r-emoji"><?= $remoji ?></span>
      <span class="r-count" id="rc-<?= $rtype ?>"><?= $reaction_counts[$rtype] ?></span>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Like + action bar -->
  <div class="blog-action-bar">
    <button class="blog-like-btn <?= $user_liked
        ? "liked"
        : "" ?>" id="blog-like-btn" data-blog-id="<?= $blog["id"] ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $user_liked
          ? "#fff"
          : "none" ?>" stroke="currentColor" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
      <span id="blog-like-count"><?= (int) $blog["likes_count"] ?></span> Likes
    </button>
    <a href="blogs.php" style="font-weight:700;color:#888;font-size:.9rem;text-decoration:none;"><i class="fa-solid fa-arrow-left"></i>ê All Blogs</a>
  </div>

  <!-- Comments -->
  <div class="comments-section">
    <h3><i class="fa-solid fa-comment"></i> Comments (<?= count(
        $comments,
    ) ?>)</h3>

    <?php if (count($comments) > 0): ?>
    <div id="comments-list">
      <?php foreach ($comments as $c): ?>
      <div class="comment-item">
        <?= renderAvatar($c["profile_image"] ?? "", "comment-avatar", "") ?>
        <div class="comment-body">
          <div class="comment-name"><?= htmlspecialchars(
              $c["username"] ?? "User",
          ) ?></div>
          <div class="comment-text"><?= nl2br(
              htmlspecialchars($c["comment"]),
          ) ?></div>
          <div class="comment-time"><?= date(
              "d M Y H:i",
              strtotime($c["created_at"]),
          ) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p id="no-comments-msg" style="color:#aaa;font-weight:600;margin-bottom:20px;">Be the first to comment! <i class="fa-solid fa-hand-point-down"></i></p>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_id"])): ?>
    <div class="comment-form">
      <textarea id="comment-input" placeholder="Share your thoughts..."></textarea>
      <button class="comment-submit" id="comment-submit-btn" data-blog-id="<?= $blog[
          "id"
      ] ?>">Post Comment <i class="fa-solid fa-paper-plane"></i></button>
    </div>
    <?php else: ?>
    <div class="login-to-comment">Login to leave a comment <i class="fa-solid fa-arrow-right"></i> <a href="login.php">Sign in with Google</a></div>
    <?php endif; ?>
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
<script>
// High-End GSAP Splash Screen Loader Logic
document.addEventListener("DOMContentLoaded", () => {
    const splash = document.getElementById('blog-splash-screen');
    if (!splash) return;

    // Helper functions to lock and release scrollbar of both html and body elements
    const lockScroll = () => {
        document.documentElement.classList.add('no-scroll');
        document.body.classList.add('no-scroll');
    };
    const unlockScroll = () => {
        document.documentElement.classList.remove('no-scroll');
        document.body.classList.remove('no-scroll');
    };

    // Safety Fallback 1: Instantly hide if GSAP CDN is blocked/offline/delayed
    if (typeof gsap === "undefined") {
        splash.style.setProperty('display', 'none', 'important');
        unlockScroll();
        return;
    }

    // Safety Fallback 2: Absolute pure JS timer to force-remove splash screen after 4.2 seconds max
    setTimeout(() => {
        if (splash && splash.style.display !== 'none') {
            splash.style.setProperty('display', 'none', 'important');
            unlockScroll();
        }
    }, 4200);

    // Elegant UX: Only show splash loader if entering the blog system from main site
    const referrer = document.referrer;
    const isFromMainSite = referrer === "" || referrer.includes("index") || !referrer.includes("blog");

    if (isFromMainSite) {
        lockScroll(); // Block scroll completely during transition and hide scrollbar
        
        try {
            // Spin loader infinitely on its own independent tween so it doesn't infinite-inflate the entrance timeline's duration!
            gsap.to(".splash-ring-loader", { rotation: 360, repeat: -1, duration: 1.0, ease: "none" });

            // GSAP Timeline (Snappy 0.9s build-up and 0.4s slide-up curtain reveal!) - perfectly finite!
            const tl = gsap.timeline();
            
            tl.to(".splash-ring-loader", { opacity: 1, scale: 1, duration: 0.4, ease: "back.out(1.2)" })
              .to(".splash-arrow", { opacity: 1, scale: 1, rotation: 360, duration: 0.4, ease: "back.out(1.2)" }, "-=0.25")
              .to(".prompt-word span", { opacity: 1, y: 0, scale: 1, filter: "blur(0px)", stagger: 0.03, duration: 0.35, ease: "power2.out" }, "-=0.25")
              .to(".blog-word span", { opacity: 1, y: 0, scale: 1, filter: "blur(0px)", stagger: 0.03, duration: 0.35, ease: "power2.out" }, "-=0.3")
              .to(".splash-loading-label", { opacity: 1, y: 0, duration: 0.3 }, "-=0.2")
              .to({}, { duration: 0.45 }) // Short pause to absorb the gorgeous layout
              .to(".splash-content", { scale: 0.9, opacity: 0, duration: 0.3, ease: "power2.in" })
              .to("#blog-splash-screen", { 
                  clipPath: "polygon(0 0, 100% 0, 100% 0, 0 0)", // Fast slide up reveal!
                  duration: 0.45, 
                  ease: "power3.inOut",
                  onComplete: () => {
                      splash.style.setProperty('display', 'none', 'important');
                      unlockScroll(); // Restore scrolling and scrollbars
                  }
              }, "-=0.1");
        } catch (err) {
            splash.style.setProperty('display', 'none', 'important');
            unlockScroll();
        }
    } else {
        // Instant load with no delay if navigating within blog pages
        splash.style.setProperty('display', 'none', 'important');
        unlockScroll();
    }
});

// Intercept Outbound Clicks for BLOG ? PROMPT 2-second Reverse Transition
document.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (!link) return;
    
    const href = link.getAttribute('href');
    if (!href) return;
    
    // Check if link redirects back to index/home page
    const isGoingBack = href === "index.php" || href === "index" || href.includes("index.php") || href === "gallery.php" || href.includes("gallery.php") || href === "./" || href === "/";
    
    if (isGoingBack && typeof gsap !== "undefined") {
        const splash = document.getElementById('blog-splash-screen');
        if (splash) {
            e.preventDefault(); // Intercept redirect
            
            // Swap text positions to BLOG ? PROMPT
            const blogWordHTML = `<span style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important; -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important; filter: drop-shadow(0 0 15px rgba(168,85,247,0.4)) !important;">B</span><span style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important; -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important; filter: drop-shadow(0 0 15px rgba(168,85,247,0.4)) !important;">L</span><span style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important; -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important; filter: drop-shadow(0 0 15px rgba(168,85,247,0.4)) !important;">O</span><span style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important; -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important; filter: drop-shadow(0 0 15px rgba(168,85,247,0.4)) !important;">G</span>`;
            const promptWordHTML = `<span style="color: #cbd5e1 !important; text-shadow: 0 0 20px rgba(255,255,255,0.1) !important;">P</span><span style="color: #cbd5e1 !important; text-shadow: 0 0 20px rgba(255,255,255,0.1) !important;">R</span><span style="color: #cbd5e1 !important; text-shadow: 0 0 20px rgba(255,255,255,0.1) !important;">O</span><span style="color: #cbd5e1 !important; text-shadow: 0 0 20px rgba(255,255,255,0.1) !important;">M</span><span style="color: #cbd5e1 !important; text-shadow: 0 0 20px rgba(255,255,255,0.1) !important;">P</span><span style="color: #cbd5e1 !important; text-shadow: 0 0 20px rgba(255,255,255,0.1) !important;">T</span>`;
            
            document.getElementById('splash-prompt-word').innerHTML = blogWordHTML;
            document.getElementById('splash-blog-word').innerHTML = promptWordHTML;
            
            const arrow = document.getElementById('splash-arrow');
            arrow.style.transform = 'scale(1) rotate(180deg)'; // Point backwards!
            arrow.style.color = '#a855f7';
            
            document.getElementById('splash-loading-label').textContent = 'RETURNING TO MAIN PORTAL';
            
            // Preset states
            gsap.set("#splash-prompt-word span", { opacity: 0, y: 20, scale: 0.7, filter: "blur(5px)" });
            gsap.set("#splash-blog-word span", { opacity: 0, y: 20, scale: 0.7, filter: "blur(5px)" });
            gsap.set(".splash-ring-loader", { opacity: 0, scale: 0.6 });
            gsap.set(".splash-arrow", { opacity: 0, scale: 0.4 });
            gsap.set(".splash-loading-label", { opacity: 0, y: 10 });
            gsap.set(".splash-content", { scale: 1, opacity: 1 });
            
            splash.style.display = 'flex';
            
            // Hide scrollbar during exit transition
            document.documentElement.classList.add('no-scroll');
            document.body.classList.add('no-scroll');
            
            // Spin loader infinitely on its own independent tween so it doesn't infinite-inflate the timeline's duration!
            gsap.to(".splash-ring-loader", { rotation: -360, repeat: -1, duration: 1.0, ease: "none" });

            // Exit Timeline (Snappy 0.85s build-up) - now perfectly finite and guaranteed to trigger onComplete!
            const exitTl = gsap.timeline({
                onComplete: () => {
                    window.location.href = href; // Route to destination
                }
            });
            
            exitTl.to("#blog-splash-screen", { 
                      clipPath: "polygon(0 0, 100% 0, 100% 100%, 0 100%)", // Curtain drops super fast!
                      duration: 0.35, 
                      ease: "power3.out" 
                  })
                  .to(".splash-ring-loader", { opacity: 1, scale: 1, duration: 0.25, ease: "back.out(1.2)" }, "-=0.15")
                  .to(".splash-arrow", { opacity: 1, scale: 1, duration: 0.25, ease: "back.out(1.2)" }, "-=0.2")
                  .to("#splash-prompt-word span", { opacity: 1, y: 0, scale: 1, filter: "blur(0px)", stagger: 0.03, duration: 0.3, ease: "power2.out" }, "-=0.2")
                  .to("#splash-blog-word span", { opacity: 1, y: 0, scale: 1, filter: "blur(0px)", stagger: 0.03, duration: 0.3, ease: "power2.out" }, "-=0.3")
                  .to(".splash-loading-label", { opacity: 1, y: 0, duration: 0.25 }, "-=0.2")
                  .to({}, { duration: 0.25 }); // Short pause before instant redirect!
        }
    }
});

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
</script>
</body></html>

