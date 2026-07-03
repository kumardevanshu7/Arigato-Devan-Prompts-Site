<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";
if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

// Fetch published blogs with author info
$blogs = $pdo
    ->query(
        "
    SELECT b.*, u.username as author_name, u.avatar as author_avatar
    FROM blogs b
    LEFT JOIN users u ON b.author_id = u.id
    WHERE b.is_published = 1
    ORDER BY b.created_at DESC
",
    )
    ->fetchAll(PDO::FETCH_ASSOC);

// Extract all unique tags for filter
$all_tags = [];
foreach ($blogs as $b) {
    if ($b["tags"]) {
        foreach (
            array_filter(array_map("trim", explode(",", $b["tags"])))
            as $tag
        ) {
            $all_tags[$tag] = ($all_tags[$tag] ?? 0) + 1;
        }
    }
}
arsort($all_tags);

// Google auth url for login button
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#c084fc">
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Blogs &ndash; Arigato Devan Prompts</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
<link rel="stylesheet" href="css/nogoda-theme.css?v=20260741">
<link rel="stylesheet" href="css/blog-splash-loading.css?v=20260756">
<meta name="description" content="Read the latest blogs on AI, couple content, and creative prompts from Arigato Devan. ??">
    <link rel="canonical" href="https://arigatodevan.com/blogs.php">
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<!-- Open Graph & Twitter Card -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="Arigato Devan Prompts">
<meta property="og:title" content="Blogs ù Arigato Devan Prompts">
<meta property="og:description" content="Read the latest blogs on AI, couple content, and creative prompts from Arigato Devan. ??">
<meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
<meta property="og:url" content="https://arigatodevan.com/blogs.php">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Blogs ù Arigato Devan Prompts">
<meta name="twitter:description" content="Read the latest blogs on AI, couple content, and creative prompts from Arigato Devan. ??">
<meta name="twitter:image" content="https://arigatodevan.com/landingpics/lan9.webp">
<link rel="stylesheet" href="style.min.css?v=20260601">
<style>
/* Global Modern Reset for Blog Section */
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap');

body {
    background-color: #f1f5f9 !important; /* Neutral light-gray base */
    font-family: 'Inter', sans-serif !important;
    color: #1e293b !important;
    margin: 0;
    padding: 0;
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
/* Override style.min.css ù keep arigato.blog visible on all mobile widths */
header .logo-area .logo-text {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    flex: 1 1 auto !important;
    min-width: 0 !important;
    overflow: visible !important;
}
/* logo circle avatar ù css/blog-header-logo.css */
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

/* Footer modern look */
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

/* Hero Section - Matching Pic 1 */
.blogs-hero {
    padding: 80px 40px 40px !important;
    max-width: 1200px;
    margin: 0 auto;
    text-align: center;
}
.blogs-hero .badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(99, 102, 241, 0.08);
    color: #6366f1;
    border-radius: 40px;
    padding: 6px 16px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 20px;
}
.blogs-hero h1 {
    font-size: clamp(2.2rem, 5vw, 3.8rem) !important;
    font-weight: 900 !important;
    color: #0f172a !important;
    margin-bottom: 18px !important;
    line-height: 1.15 !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    letter-spacing: -1.5px !important;
}
.blogs-hero h1 .highlight {
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.blogs-hero p {
    font-size: 1.15rem !important;
    font-weight: 400 !important;
    color: #64748b !important;
    max-width: 600px !important;
    margin: 0 auto !important;
    font-family: 'Inter', sans-serif !important;
}

/* Tag Filter Pills */
.tag-filter-wrap {
    max-width: 1200px;
    margin: 0 auto 50px;
    padding: 0 40px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}
.tag-pill {
    padding: 10px 22px !important;
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 40px !important;
    font-weight: 700 !important;
    font-size: 0.8rem !important;
    cursor: pointer !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    text-transform: capitalize !important;
    letter-spacing: 0px !important;
    color: #475569 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02) !important;
}
.tag-pill:hover {
    border-color: #cbd5e1 !important;
    background: #f8fafc !important;
    transform: translateY(-1px) !important;
}
.tag-pill.active {
    background: #0f172a !important;
    color: #ffffff !important;
    border-color: #0f172a !important;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.15) !important;
}

/* Main Layout Grid wrapper with Sidebar on Desktop */
.blogs-container-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 40px;
    max-width: 1250px;
    margin: 0 auto;
    padding: 40px 40px 100px; /* Spacing below the floating header */
}
@media (max-width: 1024px) {
    .blogs-container-layout {
        grid-template-columns: 1fr;
    }
    .blog-sidebar {
        display: none;
    }
}

/* LEFT CONTENT WRAP */
.blogs-wrap {
    padding: 0 !important;
}

/* 2-column Post Grid matching Pic 1 style */
.blogs-grid {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 28px !important;
    margin-top: 0 !important;
}
@media (max-width: 640px) {
    .blogs-grid {
        grid-template-columns: 1fr !important;
        gap: 24px !important;
    }
    .blogs-container-layout {
        padding: 30px 16px 80px; /* Perfect spacing for mobile floating header and narrow margins */
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

/* Premium Card Design matching Pic 1 exactly */
.blog-card {
    background: #ffffff !important;
    border: 1px solid #eaeaea !important;
    border-radius: 20px !important;
    overflow: hidden !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02) !important;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1) !important;
    text-decoration: none !important;
    color: inherit !important;
    display: flex !important;
    flex-direction: column !important;
    height: 100%;
}
.blog-card:hover {
    transform: translateY(-6px) !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05) !important;
    border-color: #e2e8f0 !important;
}
.blog-card-img-wrapper {
    position: relative;
    width: 100%;
    overflow: hidden;
}
.blog-card-img-wrapper.ratio-16-9 {
    aspect-ratio: 16 / 9;
}
.blog-card-img-wrapper.ratio-9-16 {
    aspect-ratio: 9 / 16;
}
.blog-card-img {
    width: 100%;
    height: 100%;
    object-fit: cover !important;
    transition: transform 0.5s ease;
    border-bottom: none !important;
}
.blog-card:hover .blog-card-img {
    transform: scale(1.04);
}
.blog-card-img-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: #94a3b8 !important;
    border-bottom: none !important;
}

.blog-card-body {
    padding: 24px !important;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.blog-card-tag {
    background: rgba(99, 102, 241, 0.08) !important;
    color: #6366f1 !important;
    border: none !important;
    border-radius: 30px !important;
    padding: 4px 12px !important;
    font-size: 0.7rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    margin-bottom: 14px !important;
    width: fit-content;
}
.blog-card-title {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 1.25rem !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    line-height: 1.35 !important;
    margin-bottom: 10px !important;
    transition: color 0.2s;
}
.blog-card:hover .blog-card-title {
    color: #6366f1;
}
.blog-card-desc {
    font-family: 'Inter', sans-serif !important;
    font-size: 0.88rem !important;
    color: #64748b !important;
    line-height: 1.6 !important;
    margin-bottom: 20px !important;
}
.blog-card-meta {
    margin-top: auto !important;
    padding-top: 16px !important;
    border-top: 1px solid #f1f5f9 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
}
.blog-card-meta-left {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    font-size: 0.78rem !important;
    color: #64748b !important;
    font-weight: 500 !important;
}
.blog-author-av {
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 50% !important;
}
.blog-card-likes {
    font-size: 0.78rem !important;
    color: #94a3b8 !important;
    font-weight: 600 !important;
}

/* SIDEBAR STYLES matching Pic 1 exactly */
.blog-sidebar {
    display: flex;
    flex-direction: column;
    gap: 30px;
}
.sidebar-card {
    background: #ffffff;
    border: 1px solid #eaeaea;
    border-radius: 20px;
    padding: 26px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.015);
}
.sidebar-card-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.9rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #0f172a;
    margin-bottom: 18px;
    padding-bottom: 10px;
    border-bottom: 1.5px solid #f1f5f9;
}

/* About Author card */
.author-profile-box {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}
.author-profile-box img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #6366f1;
}
.author-profile-box .name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    color: #0f172a;
}
.author-profile-box .title {
    font-size: 0.72rem;
    color: #8c8994;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.author-bio {
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.55;
    margin-bottom: 18px;
}
.author-location {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #475569;
    font-weight: 600;
}
.author-location i {
    color: #ef4444;
}

/* Featured Posts Sidebar layout */
.sidebar-featured-item {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
    text-decoration: none;
    color: inherit;
}
.sidebar-featured-item:last-child {
    margin-bottom: 0;
}
.sidebar-featured-img {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    object-fit: cover;
    background: #f1f5f9;
}
.sidebar-featured-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.sidebar-featured-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.85rem;
    font-weight: 800;
    line-height: 1.3;
    color: #0f172a;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 4px;
}
.sidebar-featured-date {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 500;
}

/* Technologies Sidebar */
.tech-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}
.tech-item:last-child {
    margin-bottom: 0;
}
.tech-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    font-size: 1rem;
    color: #475569;
}
.tech-icon.figma { background: #fee2e2; color: #f43f5e; }
.tech-icon.notion { background: #f1f5f9; color: #0f172a; }
.tech-icon.photoshop { background: #e0f2fe; color: #0284c7; }
.tech-icon.ai { background: #fef3c7; color: #d97706; }

.tech-info .name {
    font-size: 0.8rem;
    font-weight: 800;
    color: #0f172a;
}
.tech-info .desc {
    font-size: 0.7rem;
    color: #94a3b8;
}

.empty-blogs {
    text-align: center;
    padding: 80px 20px;
    color: #64748b;
    font-weight: 600;
    font-size: 1.1rem;
}
</style>
<link rel="stylesheet" href="css/blog-header-logo.css?v=20260758">
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preconnect" href="https://unpkg.com" crossorigin>
<link rel="preload" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Breadcrumb Schema -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://arigatodevan.com"},{"@type":"ListItem","position":2,"name":"Blogs","item":"https://arigatodevan.com/blogs.php"}]}
</script>
<?php include_once "gtag.php"; ?>
<?php if (!empty($_SESSION['user_id'])): ?>
<link rel="stylesheet" href="css/logout-confirm.css?v=20260781">
<?php endif; ?>
</head>
<body class="blog-splash-active">
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
<header>
  <div class="header-top-row">
    <div class="logo-area" id="logo-container" style="cursor:pointer">
      <div class="logo-flipper">
        <div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo" id="profile-logo"></div>
        <div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div>
      </div>
      <div class="logo-text">arigato.<span class="blog-brand-suffix">blog</span></div>
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
          <a href="profile.php" style="color:var(--text-color);display:flex;align-items:center;gap:8px"><?= renderAvatar($_SESSION["profile_image"] ?? "", "admin-avatar", "Profile") ?></a>
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

<!-- Hero -->
<div class="blogs-hero">
  <div class="badge"><i class="fa-solid fa-pen-nib"></i> FRESH READS</div>
  <h1>The <span class="highlight">Arigato Devan</span> Blog</h1>
  <p>Tips, stories, and ideas to fuel your creative AI journey.</p>
</div>

<?php if (count($blogs) === 0): ?>
<div class="empty-blogs"><i class="fa-solid fa-pen"></i> No blogs published yet &mdash; check back soon!</div>
<?php else: ?>

<!-- Tag Filter -->
<?php if (!empty($all_tags)): ?>
<div class="tag-filter-wrap" id="tag-filters">
  <button class="tag-pill active" data-tag="all" onclick="filterByTag('all', this)">ALL</button>
  <?php foreach ($all_tags as $tag => $count): ?>
  <button class="tag-pill" data-tag="<?= htmlspecialchars(
      strtolower($tag),
  ) ?>" onclick="filterByTag('<?= htmlspecialchars(
    strtolower($tag),
) ?>', this)"><?= htmlspecialchars(
    $tag,
) ?> <span style="opacity:.5;font-size:.7em;"><?= $count ?></span></button>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Container Layout holding main grid + premium sidebar -->
<div class="blogs-container-layout">

  <div class="blogs-wrap">
    <div class="blogs-grid" id="blogs-grid">
      <?php foreach ($blogs as $b):
          $ratio_class = ($b["image_ratio"] ?? "16:9") === "9:16" ? "ratio-9-16" : "ratio-16-9";
          $short_preview = mb_substr(strip_tags($b["content"]), 0, 75) . "...";
          ?>
      <a href="blog.php?slug=<?= urlencode($b["slug"]) ?>" class="blog-card"
         data-tags="<?= htmlspecialchars(strtolower($b["tags"] ?? "")) ?>">
        <?php if ($b["image_path"]): ?>
          <div class="blog-card-img-wrapper <?= $ratio_class ?>">
            <img loading="lazy" src="<?= htmlspecialchars($b["image_path"]) ?>" class="blog-card-img" alt="<?= htmlspecialchars($b["title"]) ?>">
          </div>
        <?php else: ?>
          <div class="blog-card-img-wrapper <?= $ratio_class ?>">
            <div class="blog-card-img-placeholder"><i class="fa-solid fa-image"></i></div>
          </div>
        <?php endif; ?>
        
        <div class="blog-card-body">
          <?php if ($b["tags"]): ?>
            <div class="blog-card-tag"><?= htmlspecialchars(explode(",", $b["tags"])[0]) ?></div>
          <?php endif; ?>
          <h2 class="blog-card-title"><?= htmlspecialchars($b["title"]) ?></h2>
          <p class="blog-card-desc"><?= htmlspecialchars($short_preview) ?></p>

          <div class="blog-card-meta">
            <div class="blog-card-meta-left">
              <img loading="lazy" src="<?= htmlspecialchars(!empty($b["author_avatar"]) ? $b["author_avatar"] : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($b["author_name"] ?? "Admin")) ?>" class="blog-author-av" alt="" style="width:26px;height:26px;">
              <span style="font-weight: 700; color: #334155;"><?= htmlspecialchars($b["author_name"] ?? "Admin") ?></span>
              <span>&middot;</span>
              <span><?= date("M d, Y", strtotime($b["created_at"])) ?></span>
            </div>
            <div class="blog-card-likes">
              <span style="color:#94a3b8;margin-right:6px;">&middot;</span>
              <span style="margin-right:8px;"><i class="fa-solid fa-heart" style="color:#f43f5e;"></i> <?= (int)$b["likes_count"] ?></span>
              <span><i class="fa-solid fa-eye"></i> <?= (int)($b["views_count"] ?? 0) ?></span>
            </div>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- No results message -->
    <div id="no-results-msg" style="display:none;text-align:center;padding:60px 20px;color:#64748b;font-weight:700;font-size:1.1rem;">
      No blogs found for this tag <i class="fa-solid fa-magnifying-glass"></i>
    </div>
  </div>

  <!-- RIGHT SIDEBAR (Matching Pic 1 exactly) -->
  <aside class="blog-sidebar">
    <!-- About Card -->
    <div class="sidebar-card">
      <div class="sidebar-card-title">ABOUT ARIGATO DEVAN</div>
      <div class="author-profile-box">
        <img src="aboutmepics/new.webp" alt="Arigato Devan" style="object-fit: cover !important;">
        <div>
          <div class="name">Arigato Devan</div>
          <div class="title">Creative Prompter</div>
        </div>
      </div>
      <p class="author-bio">I'm the creator behind <strong>Arigato Devan PromptVerse</strong> ù a platform dedicated to crafting beautiful, ready-to-use AI prompts for couples, romantics, and creative souls.</p>
      <div class="author-location">
        <i class="fa-solid fa-location-dot"></i> Mumbai, India
      </div>
    </div>

    <!-- Featured Posts Card (Dynamic) -->
    <div class="sidebar-card">
      <div class="sidebar-card-title">FEATURED POSTS</div>
      <?php 
      $featured_limit = array_slice($blogs, 0, 3);
      foreach ($featured_limit as $fb): ?>
        <a href="blog.php?slug=<?= urlencode($fb["slug"]) ?>" class="sidebar-featured-item">
          <?php if ($fb["image_path"]): ?>
            <img src="<?= htmlspecialchars($fb["image_path"]) ?>" class="sidebar-featured-img" alt="">
          <?php else: ?>
            <div class="sidebar-featured-img" style="background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8;"><i class="fa-solid fa-image"></i></div>
          <?php endif; ?>
          <div class="sidebar-featured-info">
            <h4 class="sidebar-featured-title"><?= htmlspecialchars($fb["title"]) ?></h4>
            <span class="sidebar-featured-date"><?= date("M d, Y", strtotime($fb["created_at"])) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

  </aside>

</div>
<?php endif; ?>

<?php include 'footer.php'; ?>

<script defer src="script.min.js?v=20260616"></script>
<script>
function filterByTag(tag, btn) {
  // Update active pill
  document.querySelectorAll('.tag-pill').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');

  const featured = document.getElementById('featured-post');
  const grid     = document.getElementById('blogs-grid');
  const noRes    = document.getElementById('no-results-msg');

  if (tag === 'all') {
    if (featured) featured.style.display = '';
    document.querySelectorAll('#blogs-grid .blog-card').forEach(c => c.style.display = '');
    if (noRes) noRes.style.display = 'none';
    return;
  }

  // Filter featured
  let anyVisible = false;
  if (featured) {
    const ftags = (featured.dataset.tags || '').toLowerCase();
    const show = ftags.includes(tag);
    featured.style.display = show ? '' : 'none';
    if (show) anyVisible = true;
  }

  // Filter grid cards
  document.querySelectorAll('#blogs-grid .blog-card').forEach(card => {
    const ctags = (card.dataset.tags || '').toLowerCase();
    const show = ctags.includes(tag);
    card.style.display = show ? '' : 'none';
    if (show) anyVisible = true;
  });

  if (noRes) noRes.style.display = anyVisible ? 'none' : 'block';
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
</script>
</body></html>

