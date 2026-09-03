<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";
$is_admin = (isset($_SESSION["user_id"]) && ($_SESSION["role"] ?? "") === "admin");
$is_preview_mode = false;

// 1. Live form preview via POST (from blog_create.php or blog_edit.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['is_live_preview'])) {
    $blog = [
        'id' => (int)($_POST['preview_id'] ?? 0),
        'title' => trim($_POST['preview_title'] ?? 'Untitled Draft Preview'),
        'slug' => trim($_POST['preview_slug'] ?? 'draft-preview'),
        'content' => $_POST['preview_content'] ?? '',
        'content_hindi' => $_POST['preview_content_hindi'] ?? '',
        'category' => trim($_POST['preview_category'] ?? 'General'),
        'tags' => trim($_POST['preview_tags'] ?? ''),
        'description' => trim($_POST['preview_desc'] ?? ''),
        'meta_title' => trim($_POST['preview_meta_title'] ?? ''),
        'meta_description' => trim($_POST['preview_meta_desc'] ?? ''),
        'meta_keywords' => trim($_POST['preview_meta_keywords'] ?? ''),
        'image_path' => trim($_POST['preview_image_path'] ?? ''),
        'image_path_landscape' => trim($_POST['preview_image_path_landscape'] ?? ''),
        'is_published' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'author_id' => $_SESSION['user_id'] ?? 1,
        'author_name' => $_SESSION['username'] ?? 'Admin',
        'author_avatar' => $_SESSION['avatar'] ?? $_SESSION['profile_image'] ?? '',
        'view_count' => 0,
        'likes' => 0,
        'likes_count' => 0
    ];
    $_SESSION['blog_live_preview'] = $blog;
    $is_preview_mode = true;
} elseif (isset($_GET['preview']) && !empty($_SESSION['blog_live_preview']) && $is_admin && empty($_GET['slug'])) {
    // Stored live session preview
    $blog = $_SESSION['blog_live_preview'];
    $is_preview_mode = true;
} else {
    $slug = $_GET["slug"] ?? "";
    if (!$slug) {
        header("Location: blogs.php");
        exit();
    }

    if ($is_admin || isset($_GET['preview'])) {
        // Admin or preview request can view unpublished drafts
        $stmt = $pdo->prepare(
            "SELECT b.*, u.username as author_name, u.avatar as author_avatar FROM blogs b LEFT JOIN users u ON b.author_id=u.id WHERE b.slug=?"
        );
        $stmt->execute([$slug]);
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($blog && empty($blog['is_published'])) {
            $is_preview_mode = true;
        }
    } else {
        $stmt = $pdo->prepare(
            "SELECT b.*, u.username as author_name, u.avatar as author_avatar FROM blogs b LEFT JOIN users u ON b.author_id=u.id WHERE b.slug=? AND b.is_published=1"
        );
        $stmt->execute([$slug]);
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$blog) {
        header("Location: blogs.php");
        exit();
    }
}

// Increment view count (never in preview mode)
if (empty($is_preview_mode) && !empty($blog['id'])) {
    try { 
        $pdo->prepare("UPDATE blogs SET view_count = COALESCE(view_count,0) + 1 WHERE id = ?")->execute([$blog['id']]); 
        $blog['view_count'] = ((int)($blog['view_count'] ?? 0)) + 1;
    } catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE blogs ADD COLUMN view_count INT NOT NULL DEFAULT 0");
            $pdo->prepare("UPDATE blogs SET view_count = 1 WHERE id = ?")->execute([$blog['id']]);
            $blog['view_count'] = 1;
        } catch (Exception $ex) {}
    }
}

// Has current user liked?
$user_liked = false;
if (!empty($blog['id']) && isset($_SESSION["user_id"])) {
    $lk = $pdo->prepare(
        "SELECT id FROM blog_likes WHERE user_id=? AND blog_id=?",
    );
    $lk->execute([$_SESSION["user_id"], $blog["id"]]);
    $user_liked = (bool) $lk->fetch();
}

// Reactions
$reaction_counts = ['heart'=>0,'fire'=>0,'wow'=>0,'clap'=>0,'laugh'=>0];
$my_reactions = [];
if (!empty($blog['id'])) {
    try {
        $rk = isset($_SESSION['user_id']) ? 'u'.$_SESSION['user_id'] : 'ip'.md5($_SERVER['REMOTE_ADDR']);
        $rc = $pdo->prepare("SELECT reaction, COUNT(*) as cnt FROM blog_reactions WHERE blog_id=? GROUP BY reaction");
        $rc->execute([$blog['id']]);
        foreach ($rc->fetchAll() as $r) $reaction_counts[$r['reaction']] = (int)$r['cnt'];
        $mr = $pdo->prepare("SELECT reaction FROM blog_reactions WHERE blog_id=? AND reactor_key=?");
        $mr->execute([$blog['id'], $rk]);
        $my_reactions = array_column($mr->fetchAll(), 'reaction');
    } catch(Exception $e) {}
}

// Comments
$comments = [];
if (!empty($blog['id'])) {
    $stmt_c = $pdo->prepare(
        "SELECT bc.*, u.username, u.avatar as profile_image FROM blog_comments bc LEFT JOIN users u ON bc.user_id=u.id WHERE bc.blog_id=? ORDER BY bc.created_at ASC",
    );
    $stmt_c->execute([$blog["id"]]);
    $comments = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
}

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
<link rel="stylesheet" href="css/blog-magazine.css?v=20260903tables">
<style>
/* Code Block: Full width wide horizontal rectangle with compact height */
.ba-content pre,
.blog-content pre,
.ba-content li pre,
.blog-content li pre,
pre {
  display: block !important;
  width: 100% !important;
  min-width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
  clear: both !important;
  margin: 1.4em 0 !important;
}
.ba-content pre code,
.blog-content pre code,
.ba-content li pre code,
.blog-content li pre code,
pre code {
  display: block !important;
  width: 100% !important;
  min-width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
  max-height: 220px !important;
  overflow-y: auto !important;
  overflow-x: auto !important;
  padding: 46px 20px 18px 20px !important;
}
.ba-content ol,
.blog-content ol,
.ba-content ul,
.blog-content ul {
  width: 100% !important;
  box-sizing: border-box !important;
}
.ba-content li,
.blog-content li {
  width: 100% !important;
  box-sizing: border-box !important;
}

/* ── Code Block Themes ── */
.ba-content pre.code-theme-light,
.blog-content pre.code-theme-light,
pre.code-theme-light {
  background: #f8fafc !important;
  border-color: #cbd5e1 !important;
  color: #0f172a !important;
  box-shadow: 4px 4px 0 rgba(203, 213, 225, 0.5) !important;
}
.ba-content pre.code-theme-light::before,
.blog-content pre.code-theme-light::before,
pre.code-theme-light::before {
  background: 
    radial-gradient(circle 5px at 18px 18px, #ff5f57 100%, transparent 0),
    radial-gradient(circle 5px at 33px 18px, #febc2e 100%, transparent 0),
    radial-gradient(circle 5px at 48px 18px, #28c840 100%, transparent 0),
    #e2e8f0 !important;
  color: #334155 !important;
}
.ba-content pre.code-theme-light code,
.blog-content pre.code-theme-light code,
pre.code-theme-light code {
  color: #0f172a !important;
}

.ba-content pre.code-theme-cyber,
.blog-content pre.code-theme-cyber,
pre.code-theme-cyber {
  background: #090a10 !important;
  border-color: #8b5cf6 !important;
  color: #34d399 !important;
  box-shadow: 0 0 15px rgba(139, 92, 246, 0.25), 4px 4px 0 rgba(139, 92, 246, 0.3) !important;
}
.ba-content pre.code-theme-cyber::before,
.blog-content pre.code-theme-cyber::before,
pre.code-theme-cyber::before {
  background: 
    radial-gradient(circle 5px at 18px 18px, #ec4899 100%, transparent 0),
    radial-gradient(circle 5px at 33px 18px, #8b5cf6 100%, transparent 0),
    radial-gradient(circle 5px at 48px 18px, #06b6d4 100%, transparent 0),
    linear-gradient(90deg, #1e1035 0%, #2e1065 100%) !important;
  color: #e9d5ff !important;
}
.ba-content pre.code-theme-cyber code,
.blog-content pre.code-theme-cyber code,
pre.code-theme-cyber code {
  color: #34d399 !important;
}

/* Secondary / Muted Grey Text */
.ba-content .text-muted,
.blog-content .text-muted,
.text-muted,
p.text-muted,
span.text-muted {
  color: #64748b !important;
}

/* Horizontal Line Divider */
.ba-content hr,
.blog-content hr,
hr.blog-hr-divider,
hr {
  border: none !important;
  border-top: 1.5px solid #cbd5e1 !important;
  margin: 2.4em 0 !important;
  clear: both !important;
  display: block !important;
  width: 100% !important;
}

/* Inline Prompt Variable / Placeholder Pill Boxes */
code.prompt-var,
span.prompt-var,
.prompt-var,
.ba-content code:not(pre code),
.blog-content code:not(pre code) {
  background-color: #eaecf0 !important;
  color: #1e293b !important;
  font-family: 'JetBrains Mono', Consolas, Monaco, monospace !important;
  font-size: 0.88em !important;
  padding: 2px 7px !important;
  border-radius: 5px !important;
  border: 1px solid #cbd5e1 !important;
  display: inline-block !important;
  vertical-align: baseline !important;
  line-height: 1.45 !important;
  letter-spacing: 0.01em !important;
  font-weight: 600 !important;
  word-break: break-word !important;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04) !important;
}

code.prompt-var-blue, span.prompt-var-blue,
.ba-content code.prompt-var-blue, .blog-content code.prompt-var-blue {
  background-color: #e0f2fe !important;
  color: #0369a1 !important;
  border-color: #bae6fd !important;
}

code.prompt-var-purple, span.prompt-var-purple,
.ba-content code.prompt-var-purple, .blog-content code.prompt-var-purple {
  background-color: #ede9fe !important;
  color: #6d28d9 !important;
  border-color: #ddd6fe !important;
}

code.prompt-var-amber, span.prompt-var-amber,
.ba-content code.prompt-var-amber, .blog-content code.prompt-var-amber {
  background-color: #fef3c7 !important;
  color: #92400e !important;
  border-color: #fde68a !important;
}

/* Top Reading Progress Bar (Dynamic Red -> Yellow -> Orange -> Green) */
.ba-reading-progress-bar {
  position: fixed;
  top: 0;
  left: 0;
  width: 0%;
  height: 3.5px;
  background: linear-gradient(90deg, #ef4444, #f87171);
  z-index: 9999999;
  transition: width 0.08s linear, background 0.3s ease, box-shadow 0.3s ease;
  pointer-events: none;
  box-shadow: 0 1px 6px rgba(239, 68, 68, 0.4);
}

/* Desktop Circular Reading Indicator */
.ba-reading-circle {
  position: fixed;
  left: max(20px, calc((100vw - 1360px) / 2 - 70px));
  bottom: 36px;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.95);
  border: 1.5px solid rgba(47, 65, 86, 0.18);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 99999;
  opacity: 0;
  transform: translateY(12px) scale(0.9);
  transition: opacity 0.25s ease, transform 0.25s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  user-select: none;
}
.ba-reading-circle.is-visible {
  opacity: 1;
  transform: translateY(0) scale(1);
}
.ba-reading-circle:hover {
  transform: translateY(-2px) scale(1.06);
  box-shadow: 0 6px 22px rgba(139, 92, 246, 0.28);
  border-color: rgba(139, 92, 246, 0.5);
}
.ba-circle-svg {
  width: 50px;
  height: 50px;
  transform: rotate(-90deg);
  position: absolute;
  top: 0;
  left: 0;
}
.ba-circle-track {
  fill: none;
  stroke: rgba(47, 65, 86, 0.12);
  stroke-width: 3.5;
}
.ba-circle-fill {
  fill: none;
  stroke: #8b5cf6;
  stroke-width: 3.5;
  stroke-linecap: round;
  stroke-dasharray: 113.1;
  stroke-dashoffset: 113.1;
  transition: stroke-dashoffset 0.1s linear;
}
.ba-circle-inner {
  font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
  font-size: 0.68rem;
  font-weight: 800;
  color: #2f4156;
  z-index: 2;
  text-align: center;
  line-height: 1;
}

/* Tooltip Speech Bubble above Reading Circle / Clock */
.ba-circle-tooltip {
  position: absolute;
  bottom: calc(100% + 7px);
  left: 50%;
  transform: translateX(-50%) translateY(8px) scale(0.88);
  background: #1e293b;
  color: #ffffff;
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
  font-size: 0.78rem;
  font-weight: 800;
  padding: 5px 14px;
  border-radius: 999px;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.32s cubic-bezier(0.34, 1.56, 0.64, 1), transform 0.32s cubic-bezier(0.34, 1.56, 0.64, 1), visibility 0.32s;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.25);
  z-index: 100000;
  letter-spacing: -0.01em;
  line-height: 1.25;
}
.ba-circle-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #1e293b;
}
.ba-circle-tooltip.show {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(0) scale(1);
}
.ba-circle-tooltip.tooltip-50 {
  background: linear-gradient(95deg, #1e293b 0%, #334155 100%) !important;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.35) !important;
}
.ba-circle-tooltip.tooltip-50::after {
  border-top-color: #334155 !important;
}

.ba-circle-tooltip.tooltip-70 {
  background: linear-gradient(95deg, #3730a3 0%, #6366f1 100%) !important;
  box-shadow: 0 4px 16px rgba(99, 102, 241, 0.4) !important;
}
.ba-circle-tooltip.tooltip-70::after {
  border-top-color: #6366f1 !important;
}

.ba-circle-tooltip.tooltip-90 {
  background: linear-gradient(95deg, #c2410c 0%, #ea580c 100%) !important;
  box-shadow: 0 4px 16px rgba(234, 88, 12, 0.4) !important;
}
.ba-circle-tooltip.tooltip-90::after {
  border-top-color: #ea580c !important;
}

.ba-circle-tooltip.tooltip-celebrate {
  background: linear-gradient(95deg, #7928ca 0%, #a832a6 48%, #d8358e 100%) !important;
  box-shadow: 0 5px 18px rgba(216, 53, 142, 0.42), 0 2px 6px rgba(121, 40, 202, 0.25) !important;
  color: #ffffff !important;
}
.ba-circle-tooltip.tooltip-celebrate::after {
  border-top-color: #ba2f99 !important;
}

@media (max-width: 900px) {
  .ba-reading-circle {
    display: none !important;
  }
}

/* Direct In-Page Override: Desktop Separation & Mobile Edge-to-Edge Boundary */
@media (min-width: 901px) {
  .ba-page {
    max-width: 1360px !important;
    padding-left: 36px !important;
    padding-right: 36px !important;
  }
  .ba-layout {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) 270px !important;
    gap: 150px !important;
    align-items: start !important;
  }
  .ba-layout > div:first-child {
    max-width: 760px !important;
    width: 100% !important;
  }
}
@media (max-width: 1280px) and (min-width: 1025px) {
  .ba-layout {
    grid-template-columns: minmax(0, 1fr) 260px !important;
    gap: 120px !important;
  }
}
@media (max-width: 1024px) and (min-width: 901px) {
  .ba-layout {
    grid-template-columns: minmax(0, 1fr) 250px !important;
    gap: 96px !important;
  }
}
@media (max-width: 900px) {
  .ba-page {
    padding: 0 !important;
    margin: 0 !important;
    max-width: 100vw !important;
    width: 100% !important;
    box-sizing: border-box !important;
    overflow-x: hidden !important;
  }
  .ba-hero.has-photo {
    width: 100vw !important;
    max-width: 100vw !important;
    margin: 0 !important;
    left: 0 !important;
    right: 0 !important;
    border-radius: 0 !important;
  }
  .ba-hero.has-photo .ba-gallery,
  .ba-hero.has-photo .ba-gallery-main,
  .ba-hero.has-photo .ba-gallery-main img,
  .ba-hero.has-photo .ba-cover-pic {
    width: 100vw !important;
    max-width: 100vw !important;
    left: 0 !important;
    right: 0 !important;
    margin: 0 !important;
    border-radius: 0 !important;
  }
  .ba-layout {
    grid-template-columns: minmax(0, 1fr) !important;
    gap: 24px !important;
  }
}
</style>
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

/* Highlights Category & Related Topics Tag Badges */
@media (min-width: 901px) {
    .ba-page {
        max-width: 1080px !important;
        margin: 0 auto !important;
        padding: 48px 24px 72px !important;
    }
    .ba-gallery.is-single {
        width: 100% !important;
        max-width: 1080px !important;
        height: auto !important;
        margin: 0 auto 28px !important;
        display: block !important;
    }
    .ba-gallery.is-single .ba-gallery-main {
        width: 100% !important;
        height: auto !important;
        border-radius: 18px !important;
        overflow: hidden !important;
        display: block !important;
    }
    .ba-gallery.is-single .ba-cover-pic,
    .ba-gallery.is-single .ba-cover-pic img,
    .ba-gallery.is-single .ba-gallery-main img {
        width: 100% !important;
        height: auto !important;
        max-height: 85vh !important;
        object-fit: contain !important;
        display: block !important;
        border-radius: 18px !important;
        margin: 0 auto !important;
    }
    .ba-layout {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) 280px !important;
        gap: 40px !important;
        align-items: start !important;
    }
}
@media (max-width: 900px) {
    .ba-page {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100vw !important;
        width: 100% !important;
        box-sizing: border-box !important;
        overflow-x: hidden !important;
    }
    .ba-hero.has-photo {
        width: 100vw !important;
        max-width: 100vw !important;
        margin: 0 !important;
        left: 0 !important;
        right: 0 !important;
        border-radius: 0 !important;
    }
    .ba-hero.has-photo .ba-gallery,
    .ba-hero.has-photo .ba-gallery-main,
    .ba-hero.has-photo .ba-gallery-main img,
    .ba-hero.has-photo .ba-cover-pic {
        width: 100vw !important;
        max-width: 100vw !important;
        left: 0 !important;
        right: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }
    .ba-sheet {
        padding: 20px 16px 40px !important;
    }
    .ba-layout {
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        max-width: 100% !important;
        gap: 24px !important;
        box-sizing: border-box !important;
    }
    .ba-layout > div:first-child {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    .ba-side {
        width: 100% !important;
        max-width: 100% !important;
    }
}
.ba-hl { list-style: none !important; padding: 0 !important; margin: 0 !important; }
.ba-hl li {
    display: flex !important;
    gap: 10px !important;
    align-items: center !important;
    justify-content: flex-start !important;
    text-align: left !important;
    padding: 10px 0 !important;
    border-bottom: 1px solid #f1f5f9 !important;
    font-size: 0.88rem !important;
    color: #334155 !important;
    font-weight: 500 !important;
}
.ba-hl li:last-child { border-bottom: none !important; }
.ba-hl li i { font-size: 0.88rem !important; flex-shrink: 0 !important; }
.ba-hl-cat-item {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
    align-items: center !important;
    justify-content: flex-start !important;
    text-align: left !important;
    width: 100% !important;
}
.ba-hl-cat-label {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    font-size: 0.85rem !important;
    flex-shrink: 0 !important;
}
.ba-hl-cats {
    display: inline-flex !important;
    flex-wrap: wrap !important;
    gap: 6px !important;
    align-items: center !important;
}
.ba-hl-cat-pill {
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
    background: #e0f2fe !important;
    color: #0369a1 !important;
    border: 1px solid #bae6fd !important;
    border-radius: 20px !important;
    padding: 4px 12px !important;
    font-size: 0.78rem !important;
    font-weight: 700 !important;
    text-decoration: none !important;
    transition: all 0.18s ease !important;
}
.ba-hl-cat-pill:hover {
    background: #0284c7 !important;
    color: #ffffff !important;
    border-color: #0284c7 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25) !important;
}
.bpc-thumb {
    width: 60px !important;
    height: 60px !important;
    min-width: 60px !important;
    max-width: 60px !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    flex-shrink: 0 !important;
    background: #0f172a !important;
    border: 1px solid #e2e8f0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.bpc-thumb img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    object-position: center top !important;
    display: block !important;
    margin: 0 !important;
    border-radius: 0 !important;
}
.ba-hl-tags-box {
    margin-top: 14px !important;
    padding-top: 14px !important;
    border-top: 1px solid #f1f5f9 !important;
}
.ba-hl-tags-label {
    font-size: 0.76rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: #64748b !important;
    margin-bottom: 10px !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}
.ba-hl-tags-wrap {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
}
.ba-hl-tag-badge {
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
    background: #f8fafc !important;
    color: #334155 !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 9999px !important;
    padding: 6px 13px !important;
    font-size: 0.78rem !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.ba-hl-tag-badge .tag-hash {
    color: #6366f1 !important;
    font-weight: 800 !important;
    font-size: 0.74rem !important;
}
.ba-hl-tag-badge .tag-text {
    color: #334155 !important;
}
.ba-hl-tag-badge:hover {
    background: #eff6ff !important;
    border-color: #93c5fd !important;
    color: #1d4ed8 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15) !important;
}
.ba-hl-tag-badge:hover .tag-text {
    color: #1d4ed8 !important;
}

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
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
    margin: 28px 0 !important;
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 16px !important;
    background: #ffffff !important;
    box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05) !important;
}
.blog-content table {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    border-collapse: collapse !important;
    border-spacing: 0 !important;
    margin: 0 !important;
    background: #ffffff !important;
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
    font-size: 0.92rem !important;
    line-height: 1.6 !important;
}
.blog-content th {
    background: #f8fafc !important;
    color: #0f172a !important;
    font-size: 0.82rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    padding: 14px 18px !important;
    border: none !important;
    border-bottom: 2px solid #e2e8f0 !important;
    border-right: 1px solid #f1f5f9 !important;
    text-align: left !important;
    vertical-align: middle !important;
    white-space: normal !important;
}
.blog-content td {
    padding: 14px 18px !important;
    color: #334155 !important;
    border: none !important;
    border-bottom: 1px solid #f1f5f9 !important;
    border-right: 1px solid #f8fafc !important;
    text-align: left !important;
    vertical-align: top !important;
    background: transparent !important;
    transition: background 0.15s ease !important;
}
.blog-content tr:last-child td { border-bottom: none !important; }
.blog-content th:last-child,
.blog-content td:last-child { border-right: none !important; }
.blog-content tbody tr:nth-child(even) td { background: #fafcff !important; }
.blog-content tbody tr:hover td { background: #f1f5f9 !important; }
.blog-content table a {
    color: #7c3aed !important;
    font-weight: 700 !important;
    text-decoration: underline !important;
    text-decoration-thickness: 1.5px !important;
    text-underline-offset: 3px !important;
    transition: color 0.15s ease !important;
}
.blog-content table a:hover {
    color: #581c87 !important;
}
.blog-content table mark {
    background: #fef08a !important;
    color: #713f12 !important;
    padding: 2px 8px !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    font-size: 0.9em !important;
    display: inline-block !important;
    box-decoration-break: clone !important;
    -webkit-box-decoration-break: clone !important;
}
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
.blog-content h1, .ba-content h1 {
    font-size: 2.35rem !important;
    font-weight: 800 !important;
    line-height: 1.25 !important;
    letter-spacing: -0.02em !important;
    margin-top: 1.6em !important;
    margin-bottom: 0.5em !important;
    color: #0f172a !important;
}

/* 1. Grey Disclaimer / Callout Box & 3 Themes */
.blog-grey-box, .ba-grey-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #0284c7;
    border-radius: 14px;
    padding: 16px 20px;
    margin: 1.8em 0;
    color: #334155;
    font-size: 0.95rem;
    line-height: 1.65;
    box-sizing: border-box;
}
.blog-grey-box p, .ba-grey-box p {
    margin: 0 !important;
    color: inherit;
}
.blog-box-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 800;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.blog-box-header .box-icon { font-size: 1.15rem; line-height: 1; }

/* 1. Bulb Theme (Extra Tip / Pro Tip) */
.blog-grey-box.blog-box-tip, .ba-grey-box.blog-box-tip {
    background: #fffdf5 !important;
    border-color: #fef08a !important;
    border-left: 4px solid #f59e0b !important;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.06);
}
.blog-grey-box.blog-box-tip .blog-box-header, .ba-grey-box.blog-box-tip .blog-box-header { color: #b45309 !important; }
.blog-grey-box.blog-box-tip p, .ba-grey-box.blog-box-tip p { color: #451a03 !important; }

/* 2. Info Theme (Classic Slate / Blue) */
.blog-grey-box.blog-box-info, .ba-grey-box.blog-box-info {
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
    border-left: 4px solid #0284c7 !important;
    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.06);
}
.blog-grey-box.blog-box-info .blog-box-header, .ba-grey-box.blog-box-info .blog-box-header { color: #0369a1 !important; }
.blog-grey-box.blog-box-info p, .ba-grey-box.blog-box-info p { color: #334155 !important; }

/* 3. Alert / Secret Code Theme (Rose / Coral) */
.blog-grey-box.blog-box-alert, .ba-grey-box.blog-box-alert {
    background: #fff5f6 !important;
    border-color: #fecdd3 !important;
    border-left: 4px solid #f43f5e !important;
    box-shadow: 0 2px 8px rgba(244, 63, 94, 0.06);
}
.blog-grey-box.blog-box-alert .blog-box-header, .ba-grey-box.blog-box-alert .blog-box-header { color: #be123c !important; }
.blog-grey-box.blog-box-alert p, .ba-grey-box.blog-box-alert p { color: #4c0519 !important; }

/* ── Sleek Quick FAQ Section Box with 5 Themes & Watermark ── */
.blog-faq-box {
    position: relative !important;
    border-radius: 18px !important;
    padding: 24px 26px !important;
    margin: 2.2em 0 !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
    transition: all 0.2s ease !important;
}
.blog-faq-watermark {
    position: absolute !important;
    inset: 0 !important;
    pointer-events: none !important;
    user-select: none !important;
    z-index: 0 !important;
    background-repeat: repeat !important;
    background-size: 140px 140px !important;
    opacity: 0.9 !important;
}
.blog-faq-inner {
    position: relative !important;
    z-index: 1 !important;
}
.blog-faq-head {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding-bottom: 16px !important;
    margin-bottom: 20px !important;
    border-bottom: 1.5px solid rgba(0,0,0,0.06) !important;
}
.blog-faq-icon {
    width: 36px !important;
    height: 36px !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}
.blog-faq-icon svg {
    width: 20px !important;
    height: 20px !important;
    stroke: currentColor !important;
}
.blog-faq-title {
    font-size: 1.22rem !important;
    font-weight: 800 !important;
    margin: 0 !important;
    letter-spacing: -0.02em !important;
}
.blog-faq-list {
    display: flex !important;
    flex-direction: column !important;
    gap: 14px !important;
}
.blog-faq-item {
    border-radius: 14px !important;
    padding: 16px 18px !important;
    box-sizing: border-box !important;
    background: #ffffff !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease !important;
}
.blog-faq-item:hover {
    transform: translateY(-1px) !important;
}
.faq-q-row {
    display: flex !important;
    align-items: flex-start !important;
    gap: 12px !important;
    margin-bottom: 8px !important;
}
.faq-num-badge {
    width: 26px !important;
    height: 26px !important;
    border-radius: 8px !important;
    font-size: 0.75rem !important;
    font-weight: 800 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    margin-top: 1px !important;
    letter-spacing: -0.02em !important;
}
.faq-q-text {
    font-size: 1rem !important;
    font-weight: 700 !important;
    margin: 0 !important;
    line-height: 1.4 !important;
    flex: 1 !important;
}
.faq-toggle-icon {
    font-size: 0.85rem !important;
    opacity: 0.6 !important;
    margin-top: 4px !important;
}
.faq-a-row {
    display: flex !important;
    align-items: flex-start !important;
    gap: 12px !important;
}
.faq-a-spacer {
    width: 26px !important;
    flex-shrink: 0 !important;
}
.faq-a-text {
    font-size: 0.93rem !important;
    margin: 0 !important;
    line-height: 1.65 !important;
    flex: 1 !important;
}

/* 1. Theme: Nogoda Electric Violet (Default) */
.faq-theme-nogoda {
    background: linear-gradient(145deg, #ffffff 0%, #faf5ff 100%) !important;
    border: 1.5px solid #ddd6fe !important;
    box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.08) !important;
}
.faq-theme-nogoda .blog-faq-watermark {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='b'%3E%3CfeGaussianBlur stdDeviation='1.5'/%3E%3C/filter%3E%3Ctext x='25' y='45' font-size='24' font-family='sans-serif' font-weight='800' fill='%237c3aed' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='95' y='110' font-size='36' font-family='sans-serif' font-weight='800' fill='%237c3aed' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='100' y='35' font-size='18' font-family='sans-serif' font-weight='800' fill='%237c3aed' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='35' y='125' font-size='20' font-family='sans-serif' font-weight='800' fill='%237c3aed' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3C/svg%3E") !important;
}
.faq-theme-nogoda .blog-faq-head { border-bottom-color: #ede9fe !important; }
.faq-theme-nogoda .blog-faq-icon { background: #ede9fe !important; color: #7c3aed !important; }
.faq-theme-nogoda .blog-faq-title { color: #4c1d95 !important; }
.faq-theme-nogoda .blog-faq-item { background: #ffffff !important; border: 1px solid #e9d5ff !important; box-shadow: 0 2px 8px rgba(124, 58, 237, 0.04) !important; }
.faq-theme-nogoda .faq-num-badge { background: #f3e8ff !important; color: #7c3aed !important; }
.faq-theme-nogoda .faq-q-text { color: #1e1b4b !important; }
.faq-theme-nogoda .faq-a-text { color: #4b5563 !important; }
.faq-theme-nogoda .faq-toggle-icon { color: #7c3aed !important; }

/* 2. Theme: Modern Minimalist Sky (Sample Pic 2) */
.faq-theme-sky {
    background: linear-gradient(145deg, #ffffff 0%, #f0f9ff 100%) !important;
    border: 1.5px solid #bae6fd !important;
    box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.08) !important;
}
.faq-theme-sky .blog-faq-watermark {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='b'%3E%3CfeGaussianBlur stdDeviation='1.5'/%3E%3C/filter%3E%3Ctext x='25' y='45' font-size='24' font-family='sans-serif' font-weight='800' fill='%230284c7' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='95' y='110' font-size='36' font-family='sans-serif' font-weight='800' fill='%230284c7' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='100' y='35' font-size='18' font-family='sans-serif' font-weight='800' fill='%230284c7' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='35' y='125' font-size='20' font-family='sans-serif' font-weight='800' fill='%230284c7' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3C/svg%3E") !important;
}
.faq-theme-sky .blog-faq-head { border-bottom-color: #e0f2fe !important; }
.faq-theme-sky .blog-faq-icon { background: #e0f2fe !important; color: #0284c7 !important; }
.faq-theme-sky .blog-faq-title { color: #0369a1 !important; }
.faq-theme-sky .blog-faq-item { background: #ffffff !important; border: 1px solid #e0f2fe !important; box-shadow: 0 2px 8px rgba(2, 132, 199, 0.04) !important; }
.faq-theme-sky .faq-num-badge { background: #e0f2fe !important; color: #0284c7 !important; }
.faq-theme-sky .faq-q-text { color: #0f172a !important; }
.faq-theme-sky .faq-a-text { color: #475569 !important; }
.faq-theme-sky .faq-toggle-icon { color: #0284c7 !important; }

/* 3. Theme: Executive Navy & Dark Slate */
.faq-theme-navy {
    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%) !important;
    border: 1.5px solid #cbd5e1 !important;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06) !important;
}
.faq-theme-navy .blog-faq-watermark {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='b'%3E%3CfeGaussianBlur stdDeviation='1.5'/%3E%3C/filter%3E%3Ctext x='25' y='45' font-size='24' font-family='sans-serif' font-weight='800' fill='%23334155' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='95' y='110' font-size='36' font-family='sans-serif' font-weight='800' fill='%23334155' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='100' y='35' font-size='18' font-family='sans-serif' font-weight='800' fill='%23334155' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='35' y='125' font-size='20' font-family='sans-serif' font-weight='800' fill='%23334155' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3C/svg%3E") !important;
}
.faq-theme-navy .blog-faq-head { border-bottom-color: #e2e8f0 !important; }
.faq-theme-navy .blog-faq-icon { background: #0f172a !important; color: #ffffff !important; }
.faq-theme-navy .blog-faq-title { color: #0f172a !important; }
.faq-theme-navy .blog-faq-item { background: #ffffff !important; border: 1px solid #e2e8f0 !important; }
.faq-theme-navy .faq-num-badge { background: #0f172a !important; color: #ffffff !important; }
.faq-theme-navy .faq-q-text { color: #0f172a !important; }
.faq-theme-navy .faq-a-text { color: #475569 !important; }
.faq-theme-navy .faq-toggle-icon { color: #64748b !important; }

/* 4. Theme: Warm Sunset Amber / Gold */
.faq-theme-amber {
    background: linear-gradient(145deg, #ffffff 0%, #fffdf5 100%) !important;
    border: 1.5px solid #fde68a !important;
    box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.08) !important;
}
.faq-theme-amber .blog-faq-watermark {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='b'%3E%3CfeGaussianBlur stdDeviation='1.5'/%3E%3C/filter%3E%3Ctext x='25' y='45' font-size='24' font-family='sans-serif' font-weight='800' fill='%23f59e0b' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='95' y='110' font-size='36' font-family='sans-serif' font-weight='800' fill='%23f59e0b' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='100' y='35' font-size='18' font-family='sans-serif' font-weight='800' fill='%23f59e0b' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='35' y='125' font-size='20' font-family='sans-serif' font-weight='800' fill='%23f59e0b' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3C/svg%3E") !important;
}
.faq-theme-amber .blog-faq-head { border-bottom-color: #fef3c7 !important; }
.faq-theme-amber .blog-faq-icon { background: #fef3c7 !important; color: #d97706 !important; }
.faq-theme-amber .blog-faq-title { color: #92400e !important; }
.faq-theme-amber .blog-faq-item { background: #ffffff !important; border: 1px solid #fef3c7 !important; }
.faq-theme-amber .faq-num-badge { background: #fef3c7 !important; color: #b45309 !important; }
.faq-theme-amber .faq-q-text { color: #451a03 !important; }
.faq-theme-amber .faq-a-text { color: #78350f !important; }
.faq-theme-amber .faq-toggle-icon { color: #d97706 !important; }

/* 5. Theme: Clean Emerald Mint */
.faq-theme-emerald {
    background: linear-gradient(145deg, #ffffff 0%, #f0fdf4 100%) !important;
    border: 1.5px solid #a7f3d0 !important;
    box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.08) !important;
}
.faq-theme-emerald .blog-faq-watermark {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='b'%3E%3CfeGaussianBlur stdDeviation='1.5'/%3E%3C/filter%3E%3Ctext x='25' y='45' font-size='24' font-family='sans-serif' font-weight='800' fill='%2310b981' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='95' y='110' font-size='36' font-family='sans-serif' font-weight='800' fill='%2310b981' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='100' y='35' font-size='18' font-family='sans-serif' font-weight='800' fill='%2310b981' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='35' y='125' font-size='20' font-family='sans-serif' font-weight='800' fill='%2310b981' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3C/svg%3E") !important;
}
.faq-theme-emerald .blog-faq-head { border-bottom-color: #d1fae5 !important; }
.faq-theme-emerald .blog-faq-icon { background: #d1fae5 !important; color: #059669 !important; }
.faq-theme-emerald .blog-faq-title { color: #065f46 !important; }
.faq-theme-emerald .blog-faq-item { background: #ffffff !important; border: 1px solid #d1fae5 !important; }
.faq-theme-emerald .faq-num-badge { background: #d1fae5 !important; color: #047857 !important; }
.faq-theme-emerald .faq-q-text { color: #064e3b !important; }
.faq-theme-emerald .faq-a-text { color: #047857 !important; }
.faq-theme-emerald .faq-toggle-icon { color: #059669 !important; }
    margin: 0 !important;
    line-height: 1.6 !important;
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
.blog-toc-box ol,
.blog-toc-list {
    margin: 0;
}
.blog-toc-box li {
    color: #0284c7;
    break-inside: avoid;
}
.blog-toc-sublist {
    list-style-type: circle !important;
    padding-left: 18px !important;
    margin: 4px 0 6px !important;
}
.blog-toc-sublist li {
    color: #0284c7 !important;
    margin-bottom: 3px !important;
    font-size: 0.94em !important;
    break-inside: avoid;
    list-style-type: circle !important;
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

/* Fix Carousel image margins inside blog article */
.ba-content .bcar-slide img,
.blog-content .bcar-slide img {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
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
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    border-collapse: collapse !important;
    background: #fff !important;
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
    .blog-content, .ba-content {
        font-size: 1.0625rem !important;
        line-height: 1.7 !important;
        color: #1e293b !important;
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
        overflow-wrap: break-word !important;
        word-break: break-word !important;
    }
    .blog-content *, .ba-content * {
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    .blog-content h1, .ba-content h1 {
        font-size: 1.45rem !important;
        line-height: 1.3 !important;
        margin-top: 1.2em !important;
        margin-bottom: 0.45em !important;
        overflow-wrap: break-word !important;
        word-break: break-word !important;
    }
    .blog-content p, .ba-content p {
        margin-bottom: 1em !important;
        overflow-wrap: break-word !important;
        word-break: break-word !important;
    }
    .blog-content h2, .ba-content h2 {
        font-size: 1.18rem !important;
        margin: 1.35em 0 0.5em !important;
        border: 0 !important;
        padding: 0 !important;
        overflow-wrap: break-word !important;
        word-break: break-word !important;
    }
    .blog-content h3, .ba-content h3 {
        font-size: 1.05rem !important;
        margin: 1.15em 0 0.4em !important;
        overflow-wrap: break-word !important;
        word-break: break-word !important;
    }
    .blog-content ul, .blog-content ol, .ba-content ul, .ba-content ol {
        padding-left: 1.2em !important;
        margin-bottom: 1em !important;
        overflow-wrap: break-word !important;
        word-break: break-word !important;
    }
    .blog-content li, .ba-content li {
        margin-bottom: 0.35em !important;
        overflow-wrap: break-word !important;
        word-break: break-word !important;
    }
    .blog-toc-box {
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow-wrap: break-word !important;
        word-break: break-word !important;
    }
    .blog-toc-box * {
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow-wrap: break-word !important;
        word-break: break-word !important;
    }
    .blog-toc-box ol, .blog-toc-box ul {
        columns: 1 !important;
    }
    .blog-faq-box, .blog-grey-box, .ba-grey-box, .blog-cta-box {
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 16px 14px !important;
    }
    .blog-content img, .ba-content img {
        max-width: 100% !important;
        height: auto !important;
        border-radius: 10px !important;
        margin: 12px 0 !important;
    }
    .blog-content blockquote, .ba-content blockquote {
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

/* ── Floating Admin Draft Preview Banner ── */
.ba-preview-banner {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 999999;
    background: linear-gradient(90deg, #0f172a 0%, #1e1b4b 50%, #31104b 100%);
    color: #ffffff;
    border-bottom: 1.5px solid rgba(192, 132, 252, 0.45);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
    padding: 8px 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.ba-preview-inner {
    max-width: 1360px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    font-size: 0.84rem;
}
.ba-preview-badge {
    background: #f59e0b;
    color: #1c1917;
    font-weight: 800;
    font-size: 0.72rem;
    padding: 4px 9px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    letter-spacing: 0.05em;
    flex-shrink: 0;
}
.ba-preview-text {
    color: rgba(255, 255, 255, 0.88);
    font-weight: 500;
    flex: 1;
}
.ba-preview-btn {
    background: rgba(255, 255, 255, 0.16);
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.28);
    padding: 5px 14px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
    flex-shrink: 0;
}
.ba-preview-btn:hover {
    background: rgba(255, 255, 255, 0.28);
    transform: translateY(-1px);
}
@media (max-width: 768px) {
    .ba-preview-banner {
        padding: 6px 12px !important;
    }
    .ba-preview-inner {
        gap: 8px !important;
        font-size: 0.76rem !important;
    }
    .ba-preview-text {
        display: none !important;
    }
    .ba-preview-badge {
        font-size: 0.68rem !important;
        padding: 3px 8px !important;
    }
    .ba-preview-btn {
        padding: 4px 10px !important;
        font-size: 0.72rem !important;
    }
}
</style>
</head>
<body class="page-store theme-nogoda bm-article">
<!-- Reading Progress Bar (Top Line - Nogoda Theme) -->
<div id="ba-reading-progress-bar" class="ba-reading-progress-bar" aria-hidden="true"></div>

<!-- Desktop Circular Reading Progress Badge -->
<div id="ba-reading-circle" class="ba-reading-circle" title="Reading progress - Click to go top" aria-label="Reading progress" role="button" tabindex="0">
    <div id="ba-circle-tooltip" class="ba-circle-tooltip">Read more 50%</div>
    <svg class="ba-circle-svg" viewBox="0 0 50 50">
        <circle class="ba-circle-track" cx="25" cy="25" r="18"></circle>
        <circle class="ba-circle-fill" id="ba-circle-fill" cx="25" cy="25" r="18"></circle>
    </svg>
    <div class="ba-circle-inner" id="ba-circle-text">0%</div>
</div>

<?php if (!empty($is_preview_mode)): ?>
<div class="ba-preview-banner">
    <div class="ba-preview-inner">
        <span class="ba-preview-badge"><i class="fa-solid fa-eye"></i> DRAFT PREVIEW MODE</span>
        <span class="ba-preview-text">This post is unpublished. Visible only to admins for previewing before publish.</span>
        <div class="ba-preview-actions">
            <?php if (!empty($blog['id'])): ?>
                <a href="blog_edit.php?id=<?= (int)$blog['id'] ?>" class="ba-preview-btn"><i class="fa-solid fa-pen-to-square"></i> Edit in CMS</a>
            <?php else: ?>
                <button type="button" onclick="window.close()" class="ba-preview-btn"><i class="fa-solid fa-xmark"></i> Close Preview</button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
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
          <?php 
          if (!empty($blog['category']) && $blog['category'] !== 'Uncategorized'): 
              $hl_cats = array_filter(array_map('trim', explode(',', $blog['category'])));
          ?>
          <li class="ba-hl-cat-item">
            <span class="ba-hl-cat-label"><i class="fa-solid fa-folder-open" style="color:#0284c7;"></i> Category:</span>
            <div class="ba-hl-cats">
              <?php foreach ($hl_cats as $hcat): ?>
              <a href="blogs.php?category=<?= urlencode($hcat) ?>" class="ba-hl-cat-pill" title="Filter blogs by <?= htmlspecialchars($hcat) ?>">
                <?= htmlspecialchars($hcat) ?>
              </a>
              <?php endforeach; ?>
            </div>
          </li>
          <?php endif; ?>
          <li><i class="fa-solid fa-clock" style="color:#64748b;"></i> <?= $read_time ?> minute read</li>
          <li><i class="fa-solid fa-eye" style="color:#64748b;"></i> <?= number_format((int)($blog["view_count"] ?? 0)) ?> views</li>
        </ul>

        <?php if (!empty($tags_list)): ?>
        <div class="ba-hl-tags-box">
          <div class="ba-hl-tags-label"><i class="fa-solid fa-tags" style="color:#6366f1;"></i> Related Topics</div>
          <div class="ba-hl-tags-wrap">
            <?php foreach (array_slice($tags_list, 0, 10) as $tag): ?>
            <a href="blogs.php?tag=<?= urlencode($tag) ?>" class="ba-hl-tag-badge" title="Filter by <?= htmlspecialchars($tag) ?>">
              <span class="tag-hash">#</span>
              <span class="tag-text"><?= htmlspecialchars($tag) ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
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
            <b><?= (int) ($blog["likes_count"] ?? 0) ?></b>
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
          <span id="blog-like-count"><?= (int) ($blog["likes_count"] ?? 0) ?></span> Likes
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

document.querySelectorAll('.blog-content table, .ba-content table').forEach(function(table) {
    table.removeAttribute('width');
    table.removeAttribute('height');
    table.style.width = '100%';
    table.style.maxWidth = '100%';
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

// Auto-attach [Hide / Show] toggle button & normalize any flat TOC hierarchy
document.querySelectorAll('.blog-toc-box').forEach(function(box) {
    var title = box.querySelector('.blog-toc-title');
    var list = box.querySelector('ol');
    
    // If there's an older flat TOC with circle subheadings directly in <ol>, group them into nested <ul>
    if (list) {
        var directLis = Array.from(list.children).filter(function(el) { return el.nodeName === 'LI'; });
        var currentParentLi = null;
        var currentSubUl = null;
        
        directLis.forEach(function(li) {
            var styleStr = (li.getAttribute('style') || '').toLowerCase();
            var isSub = li.style.listStyleType === 'circle' || styleStr.indexOf('circle') !== -1 || styleStr.indexOf('margin-left') !== -1;
            if (isSub) {
                if (currentParentLi) {
                    if (!currentSubUl) {
                        currentSubUl = document.createElement('ul');
                        currentSubUl.className = 'blog-toc-sublist';
                        currentParentLi.appendChild(currentSubUl);
                    }
                    li.removeAttribute('style');
                    currentSubUl.appendChild(li);
                }
            } else {
                currentParentLi = li;
                currentSubUl = null;
            }
        });
    }

    var activeList = box.querySelector('ol, ul');
    if (title && activeList && !box.querySelector('.blog-toc-toggle')) {
        var toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'blog-toc-toggle';
        toggleBtn.innerHTML = '<span class="toc-toggle-text">Hide</span> <i class="fa-solid fa-chevron-up" style="font-size:0.7rem;"></i>';
        toggleBtn.onclick = function() {
            var isHidden = activeList.style.display === 'none';
            activeList.style.display = isHidden ? '' : 'none';
            toggleBtn.querySelector('.toc-toggle-text').textContent = isHidden ? 'Hide' : 'Show';
            var icon = toggleBtn.querySelector('i');
            if (icon) icon.className = isHidden ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down';
        };
        title.appendChild(toggleBtn);
    }
});

// Auto-enhance code blocks with word count & copy button (flex container, zero overlap)
document.querySelectorAll('.ba-content pre, .blog-content pre').forEach(function(pre) {
    // Ensure all content is inside a <code> tag for smooth scrolling under the pinned header
    if (!pre.querySelector('code')) {
        var codeEl = document.createElement('code');
        while (pre.firstChild) {
            codeEl.appendChild(pre.firstChild);
        }
        pre.appendChild(codeEl);
    }

    var text = (pre.textContent || pre.innerText || '').trim();
    var words = text ? (text.match(/\S+/g) || []).length : 0;
    var wordLabel = words + (words === 1 ? ' word' : ' words');
    pre.setAttribute('data-words', wordLabel);
    
    if (!pre.querySelector('.ba-code-tools')) {
        pre.classList.add('has-tools');
        var tools = document.createElement('div');
        tools.className = 'ba-code-tools';
        
        var wordsSpan = document.createElement('span');
        wordsSpan.className = 'ba-code-words';
        wordsSpan.textContent = wordLabel;
        
        var copyBtn = document.createElement('button');
        copyBtn.type = 'button';
        copyBtn.className = 'ba-code-copy-btn';
        copyBtn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy';
        copyBtn.title = 'Copy code';
        copyBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            var codeText = (pre.querySelector('code') ? pre.querySelector('code').innerText : pre.innerText) || text;
            navigator.clipboard.writeText(codeText.trim()).then(function() {
                copyBtn.classList.add('copied');
                copyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                setTimeout(function() {
                    copyBtn.classList.remove('copied');
                    copyBtn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy';
                }, 2000);
            });
        });
        
        tools.appendChild(wordsSpan);
        tools.appendChild(copyBtn);
        pre.appendChild(tools);
    }
});

// ── Interactive Frontend Carousel Controller (Overflow Aware) ──
document.querySelectorAll('.blog-carousel-box').forEach(function(box) {
    var viewport = box.querySelector('.bcar-viewport');
    var track = box.querySelector('.bcar-track');
    var slides = box.querySelectorAll('.bcar-slide');
    var prevBtn = box.querySelector('.bcar-prev');
    var nextBtn = box.querySelector('.bcar-next');
    var dotsWrap = box.querySelector('.bcar-dots');
    var dots = box.querySelectorAll('.bcar-dot');
    if (!viewport || !track || !slides.length) return;

    var currentIndex = 0;
    var is916 = box.classList.contains('bcar-ratio-9-16') || box.getAttribute('data-ratio') === '9:16';

    function checkOverflowAndNav() {
        var hasOverflow = track.scrollWidth > viewport.clientWidth + 8;
        if (!hasOverflow) {
            // Everything fits! No slide needed (e.g. 2 cards on laptop)
            box.classList.add('bcar-no-overflow');
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
            if (dotsWrap) dotsWrap.style.display = 'none';
            return;
        }

        // Sliding is needed! (e.g. on mobile or with 3+ cards)
        box.classList.remove('bcar-no-overflow');
        if (dotsWrap) dotsWrap.style.display = 'flex';

        var scrollLeft = viewport.scrollLeft;
        var maxScroll = track.scrollWidth - viewport.clientWidth;

        if (prevBtn) {
            prevBtn.style.display = scrollLeft > 10 ? 'flex' : 'none';
        }
        if (nextBtn) {
            nextBtn.style.display = scrollLeft < (maxScroll - 10) ? 'flex' : 'none';
        }
    }

    function updateCarousel(idx) {
        if (idx < 0) idx = 0;
        if (idx >= slides.length) idx = slides.length - 1;
        currentIndex = idx;

        if (!is916) {
            var scrollX = idx * viewport.clientWidth;
            viewport.scrollTo({ left: scrollX, behavior: 'smooth' });
        } else {
            var slideEl = slides[idx];
            if (slideEl) {
                var targetLeft = slideEl.offsetLeft - (viewport.clientWidth - slideEl.clientWidth) / 2;
                viewport.scrollTo({ left: Math.max(0, targetLeft), behavior: 'smooth' });
            }
        }

        dots.forEach(function(d, i) {
            d.classList.toggle('active', i === currentIndex);
        });

        setTimeout(checkOverflowAndNav, 350);
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            updateCarousel(currentIndex - 1);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            updateCarousel(currentIndex + 1);
        });
    }

    dots.forEach(function(dot, i) {
        dot.addEventListener('click', function(e) {
            e.stopPropagation();
            updateCarousel(i);
        });
    });

    viewport.addEventListener('scroll', function() {
        checkOverflowAndNav();
    }, { passive: true });

    window.addEventListener('resize', checkOverflowAndNav);
    window.addEventListener('load', checkOverflowAndNav);
    setTimeout(checkOverflowAndNav, 60);
    setTimeout(checkOverflowAndNav, 350);

    var startX = 0;
    viewport.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
    }, { passive: true });

    viewport.addEventListener('touchend', function(e) {
        var endX = e.changedTouches[0].clientX;
        var diff = startX - endX;
        if (Math.abs(diff) > 40) {
            if (diff > 0) updateCarousel(currentIndex + 1);
            else updateCarousel(currentIndex - 1);
        }
    }, { passive: true });
});

// ── Reading Progress Tracker with Milestones, Dynamic Colors & Confetti ──
(function() {
    var bar = document.getElementById('ba-reading-progress-bar');
    var circle = document.getElementById('ba-reading-circle');
    var circleFill = document.getElementById('ba-circle-fill');
    var circleText = document.getElementById('ba-circle-text');
    var circleTooltip = document.getElementById('ba-circle-tooltip');

    var shown50Notice = false;
    var shown70Notice = false;
    var shown90Notice = false;
    var confettiFired = false;
    var autoLikedDone = false;
    var tooltipHideTimer = null;

    // 10 Unique engaging messages for each milestone (randomly chosen on each visit)
    var MESSAGES_50 = [
        'Halfway there! 🚀',
        '50% done! Keep going ✨',
        'Halfway through! Great read 💡',
        '50% down, best parts ahead! 🔥',
        'Awesome focus! 50% read 🎯',
        'Midway milestone reached! ⚡',
        '50% completed! Enjoying it? ☕',
        'Halfway there, knowledge unlocked! 🧠',
        'Cruising nicely! 50% done 💫',
        'Solid progress! 50% down 📈'
    ];

    var MESSAGES_70 = [
        'Almost done! 70% 🌟',
        'Cruising through! 70% ⚡',
        'Only a few scrolls left! 📖',
        '70% done! The best insights 🔥',
        'Impressive speed! 70% read 🚀',
        '70% milestone! Finish strong 💪',
        'Nearly at the finish line! 🎯',
        'Almost there! 70% completed ✨',
        'Key takeaways ahead! 💡',
        '70% read! You’re a pro 👏'
    ];

    var MESSAGES_90 = [
        'Last stretch! 90% 🏁',
        'Just 10% left! So close 🎯',
        'Wrapping up! 90% read 💫',
        'Final thoughts ahead! 90% 🌟',
        'Almost at 100%! Ready? 🚀',
        '90% done! Masterpiece read 📖',
        'The finale! 90% completed ✨',
        'Home stretch! Almost there 🔥',
        'Final wisdom coming! 💡',
        'Final seconds of reading! ⚡'
    ];

    function getRandomMsg(arr) {
        return arr[Math.floor(Math.random() * arr.length)];
    }

    if (circle) {
        circle.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function showCircleTooltip(text, duration, variantClass) {
        if (!circleTooltip) return;
        clearTimeout(tooltipHideTimer);
        circleTooltip.textContent = text;
        circleTooltip.classList.remove('tooltip-50', 'tooltip-70', 'tooltip-90', 'tooltip-celebrate');
        if (variantClass) {
            circleTooltip.classList.add(variantClass);
        }
        circleTooltip.classList.add('show');
        tooltipHideTimer = setTimeout(function() {
            circleTooltip.classList.remove('show');
        }, duration || 3500);
    }

    // Celebratory Confetti Animation
    function launchReadingConfetti() {
        var canvas = document.createElement('canvas');
        canvas.id = 'ba-confetti-canvas';
        canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999999;';
        document.body.appendChild(canvas);
        
        var ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        
        var colors = ['#8b5cf6', '#ec4899', '#3b82f6', '#10b981', '#f59e0b', '#a855f7', '#06b6d4', '#ef4444'];
        var particles = [];
        for (var i = 0; i < 95; i++) {
            particles.push({
                x: window.innerWidth * (0.15 + Math.random() * 0.7),
                y: window.innerHeight * 0.55 + (Math.random() * 80 - 40),
                vx: (Math.random() - 0.5) * 18,
                vy: -Math.random() * 16 - 7,
                size: Math.random() * 9 + 5,
                color: colors[Math.floor(Math.random() * colors.length)],
                rotation: Math.random() * 360,
                rotSpeed: (Math.random() - 0.5) * 14,
                opacity: 1,
                gravity: 0.45 + Math.random() * 0.35
            });
        }
        
        var startTime = Date.now();
        function animate() {
            var elapsed = Date.now() - startTime;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            particles.forEach(function(p) {
                p.x += p.vx;
                p.y += p.vy;
                p.vy += p.gravity;
                p.vx *= 0.98;
                p.rotation += p.rotSpeed;
                if (elapsed > 2000) {
                    p.opacity -= 0.025;
                }
                
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate((p.rotation * Math.PI) / 180);
                ctx.globalAlpha = Math.max(0, p.opacity);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
                ctx.restore();
            });
            
            if (elapsed < 3500 && particles.some(function(p) { return p.opacity > 0; })) {
                requestAnimationFrame(animate);
            } else {
                if (canvas.parentNode) canvas.parentNode.removeChild(canvas);
            }
        }
        requestAnimationFrame(animate);
    }

    // Auto-like blog for logged-in user upon reading completion (silent in background)
    function triggerAutoLikeOnRead() {
        <?php if (isset($_SESSION["user_id"]) && !empty($blog["id"])): ?>
        if (autoLikedDone) return;
        var likeBtnEl = document.getElementById('blog-like-btn');
        if (likeBtnEl && likeBtnEl.classList.contains('liked')) {
            return; // Already liked manually
        }
        autoLikedDone = true;
        var blogId = <?= (int)$blog['id'] ?>;
        var fd = new FormData();
        fd.append('blog_id', blogId);
        fd.append('action', 'like_only');
        
        fetch('blog_like.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success && d.action === 'liked') {
                    var countEl = document.getElementById('blog-like-count');
                    if (countEl) countEl.textContent = d.likes_count;
                    if (likeBtnEl) {
                        likeBtnEl.classList.add('liked');
                        var svg = likeBtnEl.querySelector('svg');
                        if (svg) {
                            svg.setAttribute('fill', '#fff');
                            svg.setAttribute('stroke', '#fff');
                        }
                    }
                    // Auto-like is completely silent - NO popups or toasts
                }
            })
            .catch(function() {});
        <?php endif; ?>
    }

    function calculateReadingProgress() {
        var content = document.querySelector('.ba-content, .blog-content');
        if (!content) return;
        
        var rect = content.getBoundingClientRect();
        var contentTop = rect.top + window.scrollY;
        var contentHeight = content.offsetHeight;
        var currentScroll = window.scrollY;
        var windowH = window.innerHeight;
        
        var startScroll = contentTop - (windowH * 0.2);
        var endScroll = contentTop + contentHeight - (windowH * 0.7);
        
        var progress = 0;
        if (currentScroll > startScroll) {
            if (endScroll <= startScroll) {
                progress = 100;
            } else {
                progress = Math.min(100, Math.max(0, Math.round(((currentScroll - startScroll) / (endScroll - startScroll)) * 100)));
            }
        }
        
        // ── Mobile/Top Bar Progressive Color Transition (Red -> Orange -> Yellow -> Green) ──
        if (bar) {
            bar.style.width = progress + '%';
            var barColor = 'linear-gradient(90deg, #ef4444, #f87171)';
            var barShadow = 'rgba(239, 68, 68, 0.45)';

            if (progress < 30) {
                barColor = 'linear-gradient(90deg, #dc2626, #ef4444)';
                barShadow = 'rgba(239, 68, 68, 0.45)';
            } else if (progress < 60) {
                barColor = 'linear-gradient(90deg, #ef4444, #f97316)';
                barShadow = 'rgba(249, 115, 22, 0.45)';
            } else if (progress < 85) {
                barColor = 'linear-gradient(90deg, #f97316, #eab308)';
                barShadow = 'rgba(234, 179, 8, 0.45)';
            } else {
                barColor = 'linear-gradient(90deg, #10b981, #22c55e)';
                barShadow = 'rgba(16, 185, 129, 0.55)';
            }
            bar.style.background = barColor;
            bar.style.boxShadow = '0 1px 8px ' + barShadow;
        }
        
        // ── Desktop Circular Badge ──
        if (circle) {
            circle.classList.toggle('is-visible', currentScroll > 150);
            
            if (circleFill) {
                var circumference = 113.1;
                var offset = circumference - (circumference * progress / 100);
                circleFill.style.strokeDashoffset = offset;
            }
            if (circleText) {
                if (progress >= 100) {
                    circleText.innerHTML = '<i class="fa-solid fa-check" style="color:#10b981; font-size:0.75rem;"></i>';
                } else {
                    circleText.textContent = progress + '%';
                }
            }
        }

        // ── Milestone 1: 50% Notice (Randomized from 10 engaging messages) ──
        if (progress >= 50 && progress < 70) {
            if (!shown50Notice) {
                shown50Notice = true;
                showCircleTooltip(getRandomMsg(MESSAGES_50), 3800, 'tooltip-50');
            }
        }

        // ── Milestone 2: 70% Notice (Randomized from 10 engaging messages) ──
        if (progress >= 70 && progress < 90) {
            if (!shown70Notice) {
                shown70Notice = true;
                showCircleTooltip(getRandomMsg(MESSAGES_70), 3800, 'tooltip-70');
            }
        }

        // ── Milestone 3: 90% Notice (Randomized from 10 engaging messages) ──
        if (progress >= 90 && progress < 98) {
            if (!shown90Notice) {
                shown90Notice = true;
                showCircleTooltip(getRandomMsg(MESSAGES_90), 3800, 'tooltip-90');
            }
        }

        // ── Milestone 4: 100% Completed -> Confetti + Sleek "Like kardo 💖" Bubble + Silent Auto-Like ──
        if (progress >= 98) {
            if (!confettiFired) {
                confettiFired = true;
                launchReadingConfetti();
                showCircleTooltip('Like kardo 💖', 7000, 'tooltip-celebrate');
                triggerAutoLikeOnRead();
            }
        }
    }
    
    window.addEventListener('scroll', calculateReadingProgress, { passive: true });
    window.addEventListener('resize', calculateReadingProgress);
    document.addEventListener('DOMContentLoaded', calculateReadingProgress);
    calculateReadingProgress();
})();
</script>

<!-- ── Interactive Fullscreen Image Lightbox Preview Modal ── -->
<style id="blog-lightbox-inline-css">
#blogImageLightbox {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 99999999 !important;
    display: none;
    background: rgba(8, 12, 22, 0.94) !important;
    backdrop-filter: blur(20px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
    opacity: 0;
    transition: opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
    user-select: none !important;
    box-sizing: border-box !important;
    margin: 0 !important;
    padding: 0 !important;
}
#blogImageLightbox.is-open {
    opacity: 1 !important;
}
.blog-lightbox-overlay {
    position: absolute !important;
    inset: 0 !important;
    cursor: zoom-out !important;
    z-index: 1 !important;
}

/* Top-Left Counter */
.blog-lightbox-top-left {
    position: absolute !important;
    top: 20px !important;
    left: 24px !important;
    z-index: 10 !important;
    pointer-events: auto !important;
}
.blog-lightbox-counter {
    display: inline-flex !important;
    align-items: center !important;
    font-family: 'Plus Jakarta Sans', -apple-system, sans-serif !important;
    font-size: 0.84rem !important;
    font-weight: 700 !important;
    color: #f8fafc !important;
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.22) !important;
    padding: 6px 16px !important;
    border-radius: 30px !important;
    letter-spacing: 0.5px !important;
    backdrop-filter: blur(12px) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
}

/* Top-Right Buttons */
.blog-lightbox-top-right {
    position: absolute !important;
    top: 20px !important;
    right: 24px !important;
    z-index: 10 !important;
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    pointer-events: auto !important;
}
.blog-lightbox-btn {
    width: 44px !important;
    height: 44px !important;
    border-radius: 50% !important;
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.22) !important;
    color: #ffffff !important;
    font-size: 1.15rem !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    text-decoration: none !important;
    backdrop-filter: blur(12px) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
    transition: background 0.18s ease, transform 0.18s ease, color 0.18s ease !important;
}
.blog-lightbox-btn:hover {
    background: rgba(255, 255, 255, 0.26) !important;
    transform: scale(1.08) !important;
    color: #38bdf8 !important;
}

/* Centered Stage & Image */
.blog-lightbox-center-stage {
    position: absolute !important;
    inset: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 50px 80px !important;
    box-sizing: border-box !important;
    pointer-events: none !important;
    z-index: 2 !important;
}
.blog-lightbox-img {
    max-width: 90vw !important;
    max-height: 85vh !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain !important;
    border-radius: 16px !important;
    box-shadow: 0 30px 70px rgba(0, 0, 0, 0.85), 0 0 0 1px rgba(255, 255, 255, 0.12) !important;
    transform: scale(0.92) translateY(6px) !important;
    opacity: 0 !important;
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.18s ease-out !important;
    pointer-events: auto !important;
}
#blogImageLightbox.is-open .blog-lightbox-img {
    transform: scale(1) translateY(0) !important;
    opacity: 1 !important;
}

/* Nav Arrows */
.blog-lightbox-arrow {
    position: absolute !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 50px !important;
    height: 50px !important;
    border-radius: 50% !important;
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.22) !important;
    color: #ffffff !important;
    font-size: 1.35rem !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    backdrop-filter: blur(12px) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
    transition: background 0.18s ease, transform 0.18s ease, color 0.18s ease !important;
    z-index: 10 !important;
    pointer-events: auto !important;
}
.blog-lightbox-arrow:hover {
    background: rgba(255, 255, 255, 0.28) !important;
    transform: translateY(-50%) scale(1.1) !important;
    color: #38bdf8 !important;
}
.blog-lightbox-prev { left: 24px !important; }
.blog-lightbox-next { right: 24px !important; }

/* Caption Pill (Bottom Centered) */
.blog-lightbox-caption-pill {
    position: absolute !important;
    bottom: 24px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    z-index: 10 !important;
    max-width: 80vw !important;
    background: rgba(15, 23, 42, 0.88) !important;
    border: 1px solid rgba(255, 255, 255, 0.18) !important;
    border-radius: 30px !important;
    padding: 8px 24px !important;
    color: #f8fafc !important;
    font-family: 'Plus Jakarta Sans', -apple-system, sans-serif !important;
    font-size: 0.86rem !important;
    font-weight: 600 !important;
    text-align: center !important;
    backdrop-filter: blur(14px) !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    pointer-events: auto !important;
}

@media (max-width: 768px) {
  .blog-lightbox-top-left { top: 14px !important; left: 14px !important; }
  .blog-lightbox-top-right { top: 14px !important; right: 14px !important; gap: 8px !important; }
  .blog-lightbox-counter { font-size: 0.76rem !important; padding: 5px 12px !important; }
  .blog-lightbox-btn { width: 38px !important; height: 38px !important; font-size: 1rem !important; }
  .blog-lightbox-prev { left: 10px !important; width: 42px !important; height: 42px !important; font-size: 1.15rem !important; }
  .blog-lightbox-next { right: 10px !important; width: 42px !important; height: 42px !important; font-size: 1.15rem !important; }
  .blog-lightbox-center-stage { padding: 55px 16px 45px !important; }
  .blog-lightbox-img { max-width: 95vw !important; max-height: 80vh !important; }
  .blog-lightbox-caption-pill { bottom: 14px !important; font-size: 0.78rem !important; padding: 6px 16px !important; }
}
</style>

<div id="blogImageLightbox" class="blog-lightbox-backdrop" aria-hidden="true" role="dialog">
    <div class="blog-lightbox-overlay" onclick="closeBlogLightbox()"></div>
    
    <!-- Top-Left Counter -->
    <div class="blog-lightbox-top-left">
        <div class="blog-lightbox-counter" id="blogLightboxCounter">1 / 1</div>
    </div>

    <!-- Top-Right Actions -->
    <div class="blog-lightbox-top-right">
        <a href="#" target="_blank" id="blogLightboxOpenOrig" class="blog-lightbox-btn" title="Open original image in new tab">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </a>
        <button type="button" class="blog-lightbox-btn blog-lightbox-close" onclick="closeBlogLightbox()" title="Close (Esc)">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Nav Arrows -->
    <button type="button" class="blog-lightbox-arrow blog-lightbox-prev" onclick="prevBlogLightbox(event)" title="Previous (Left arrow)">
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    <button type="button" class="blog-lightbox-arrow blog-lightbox-next" onclick="nextBlogLightbox(event)" title="Next (Right arrow)">
        <i class="fa-solid fa-chevron-right"></i>
    </button>

    <!-- Center Stage with Image -->
    <div class="blog-lightbox-center-stage">
        <img src="" alt="" id="blogLightboxImg" class="blog-lightbox-img">
    </div>

    <!-- Bottom Caption Pill -->
    <div class="blog-lightbox-caption-pill" id="blogLightboxCaption" style="display:none;"></div>
</div>

<script>
// ── Interactive Fullscreen Image Lightbox Preview Controller ──
(function() {
    var imagesList = [];
    var activeIdx = 0;

    function getLb() {
        return document.getElementById('blogImageLightbox');
    }

    function gatherImages() {
        var imgs = document.querySelectorAll('.ba-content img, .blog-content img, .bcar-slide img, .ba-gallery-main img, .ba-gallery-side img, .ba-cover-pic img, .blog-article-content img');
        imagesList = [];
        imgs.forEach(function(img) {
            if (img.classList.contains('admin-avatar') || img.classList.contains('be-serp-favicon') || img.classList.contains('fa-icon') || img.classList.contains('bm-slider-btn') || (img.naturalWidth && img.naturalWidth < 40)) return;
            var src = img.getAttribute('data-full-src') || img.currentSrc || img.src;
            if (!src) return;

            var caption = '';
            var parentFig = img.closest('figure');
            if (parentFig && parentFig.querySelector('figcaption, .img-caption')) {
                caption = parentFig.querySelector('figcaption, .img-caption').textContent.trim();
            }
            var slideCap = img.closest('.bcar-slide');
            if (slideCap && slideCap.querySelector('.bcar-caption')) {
                caption = slideCap.querySelector('.bcar-caption').textContent.trim();
            }

            imagesList.push({ src: src, caption: caption, el: img });
        });
    }

    window.openBlogLightbox = function(idx) {
        var lightbox = getLb();
        if (!lightbox) return;

        var lbImg = document.getElementById('blogLightboxImg');
        var lbCaption = document.getElementById('blogLightboxCaption');
        var lbCounter = document.getElementById('blogLightboxCounter');
        var lbOpenOrig = document.getElementById('blogLightboxOpenOrig');
        var lbPrev = lightbox.querySelector('.blog-lightbox-prev');
        var lbNext = lightbox.querySelector('.blog-lightbox-next');

        if (!imagesList.length) gatherImages();
        if (idx < 0) idx = 0;
        if (idx >= imagesList.length) idx = imagesList.length - 1;
        activeIdx = idx;

        var item = imagesList[activeIdx];
        if (!item) return;

        if (lbImg) {
            lbImg.src = item.src;
            lbImg.alt = item.caption || '';
        }
        if (lbCaption) {
            if (item.caption && item.caption.length > 0) {
                lbCaption.textContent = item.caption;
                lbCaption.style.display = 'block';
            } else {
                lbCaption.textContent = '';
                lbCaption.style.display = 'none';
            }
        }
        if (lbCounter) lbCounter.textContent = (activeIdx + 1) + ' / ' + Math.max(1, imagesList.length);
        if (lbOpenOrig) lbOpenOrig.href = item.src;

        if (imagesList.length <= 1) {
            if (lbPrev) lbPrev.style.display = 'none';
            if (lbNext) lbNext.style.display = 'none';
            if (lbCounter) lbCounter.style.display = 'none';
        } else {
            if (lbPrev) lbPrev.style.display = 'flex';
            if (lbNext) lbNext.style.display = 'flex';
            if (lbCounter) lbCounter.style.display = 'block';
        }

        lightbox.style.display = 'flex';
        void lightbox.offsetWidth;
        lightbox.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    };

    window.closeBlogLightbox = function() {
        var lightbox = getLb();
        if (!lightbox) return;
        lightbox.classList.remove('is-open');
        setTimeout(function() {
            lightbox.style.display = 'none';
            var lbImg = document.getElementById('blogLightboxImg');
            if (lbImg) lbImg.src = '';
            document.body.style.overflow = '';
        }, 200);
    };

    window.prevBlogLightbox = function(e) {
        if (e) e.stopPropagation();
        var nextIdx = (activeIdx - 1 + imagesList.length) % Math.max(1, imagesList.length);
        openBlogLightbox(nextIdx);
    };

    window.nextBlogLightbox = function(e) {
        if (e) e.stopPropagation();
        var nextIdx = (activeIdx + 1) % Math.max(1, imagesList.length);
        openBlogLightbox(nextIdx);
    };

    // Global Direct Click Handler
    document.addEventListener('click', function(e) {
        // Ignore clicks on nav buttons, dots, tools, links, and inside open lightbox
        if (e.target.closest('.bcar-prev, .bcar-next, .bcar-dots, .bcar-dot, .ba-code-tools, .blog-toc-toggle, .read-size-bar, #blogImageLightbox')) return;
        
        var targetImg = null;
        if (e.target.tagName === 'IMG') {
            targetImg = e.target;
        } else {
            var container = e.target.closest('.bcar-slide, .bcar-viewport, figure, .ba-cover-pic, .ba-gallery-item');
            if (container) targetImg = container.querySelector('img');
        }

        if (!targetImg) return;

        // Check if inside blog content or hero
        if (!targetImg.closest('.ba-content, .blog-content, .blog-carousel-box, .ba-hero-cover, .ba-cover-pic, article')) return;

        // Ignore small icons & avatars
        if (targetImg.classList.contains('admin-avatar') || targetImg.classList.contains('be-serp-favicon') || targetImg.classList.contains('fa-icon') || (targetImg.naturalWidth && targetImg.naturalWidth < 40)) return;

        e.preventDefault();
        gatherImages();

        var src = targetImg.getAttribute('data-full-src') || targetImg.currentSrc || targetImg.src;
        var foundIdx = imagesList.findIndex(function(item) {
            return item.el === targetImg || (src && item.src === src);
        });

        if (foundIdx === -1) {
            imagesList.push({ src: src, caption: '', el: targetImg });
            foundIdx = imagesList.length - 1;
        }

        openBlogLightbox(foundIdx);
    });

    document.addEventListener('keydown', function(e) {
        var lightbox = getLb();
        if (!lightbox || lightbox.style.display === 'none') return;
        if (e.key === 'Escape') closeBlogLightbox();
        if (e.key === 'ArrowLeft') prevBlogLightbox();
        if (e.key === 'ArrowRight') nextBlogLightbox();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', gatherImages);
    } else {
        gatherImages();
    }
})();
</script>

<!-- Nogoda 3-Second Download Countdown Modal -->
<div id="nogodaDownloadModal" class="nogoda-dl-modal-backdrop" style="display:none;" role="dialog" aria-modal="true">
  <div class="nogoda-dl-modal-box">
    <button type="button" class="nogoda-dl-close-btn" onclick="closeNogodaDownloadModal()" aria-label="Close download modal">
      <i class="fa-solid fa-xmark"></i>
    </button>
    
    <!-- State 1: 3-Second Animated Countdown -->
    <div id="nogodaDlStateCounting" class="nogoda-dl-state">
      <div class="nogoda-dl-header-badge">
        <i class="fa-solid fa-cloud-arrow-down"></i>
        <span>Arigato Devan Asset</span>
      </div>
      
      <h3 class="nogoda-dl-title" id="nogodaDlModalTitle">Preparing Your Download</h3>
      <p class="nogoda-dl-subtitle">Your high-speed direct download will start automatically in:</p>
      
      <!-- Circular Progress Ring with Nogoda Theme -->
      <div class="nogoda-dl-timer-container">
        <svg class="nogoda-dl-timer-svg" viewBox="0 0 120 120">
          <circle class="nogoda-dl-circle-bg" cx="60" cy="60" r="52"></circle>
          <circle class="nogoda-dl-circle-bar" id="nogodaDlCircleBar" cx="60" cy="60" r="52" style="stroke-dasharray: 326.72; stroke-dashoffset: 0;"></circle>
        </svg>
        <div class="nogoda-dl-timer-number" id="nogodaDlCountdownNum">3</div>
      </div>
      
      <div class="nogoda-dl-file-pill">
        <span class="nogoda-dl-pill-badge" id="nogodaDlModalBadge">ZIP ARCHIVE</span>
        <span class="nogoda-dl-pill-name" id="nogodaDlModalFileName">Asset-Pack.zip</span>
        <span class="nogoda-dl-pill-size" id="nogodaDlModalFileSize">4.8 MB</span>
      </div>
    </div>

    <!-- State 2: Download Started & Thank You -->
    <div id="nogodaDlStateFinished" class="nogoda-dl-state" style="display:none;">
      <div class="nogoda-dl-success-icon">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <h3 class="nogoda-dl-title">Thank You for Downloading! 🎉</h3>
      <p class="nogoda-dl-subtitle">Your file download has started automatically. Check your browser downloads folder!</p>
      
      <div class="nogoda-dl-success-actions">
        <a id="nogodaDlDirectLink" href="#" download class="nogoda-dl-again-btn">
          <i class="fa-solid fa-arrow-rotate-right"></i> Download Again (If not started)
        </a>
        <button type="button" class="nogoda-dl-done-btn" onclick="closeNogodaDownloadModal()">
          <i class="fa-solid fa-check"></i> Done
        </button>
      </div>
    </div>
  </div>
</div>

<script>
window.currentDownloadTimer = null;

function triggerBlogAssetDownload(btnEl) {
    if (!btnEl) return;
    var url = btnEl.getAttribute('data-url') || '';
    var name = btnEl.getAttribute('data-name') || 'Download-Asset';
    var size = btnEl.getAttribute('data-size') || '';
    var badge = btnEl.getAttribute('data-badge') || 'FREE ASSET';
    
    if (!url) {
        alert('Download link not found.');
        return;
    }
    
    var modal = document.getElementById('nogodaDownloadModal');
    if (!modal) {
        var a = document.createElement('a');
        a.href = url;
        a.download = name;
        a.target = '_blank';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        return;
    }
    
    // Setup modal text
    document.getElementById('nogodaDlModalTitle').textContent = name;
    document.getElementById('nogodaDlModalFileName').textContent = name;
    document.getElementById('nogodaDlModalFileSize').textContent = size ? ('• ' + size) : '';
    document.getElementById('nogodaDlModalBadge').textContent = badge;
    
    var dlLink = document.getElementById('nogodaDlDirectLink');
    if (dlLink) {
        dlLink.href = url;
        dlLink.setAttribute('download', name);
    }
    
    document.getElementById('nogodaDlStateCounting').style.display = 'block';
    document.getElementById('nogodaDlStateFinished').style.display = 'none';
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    var countdownNum = document.getElementById('nogodaDlCountdownNum');
    var circleBar = document.getElementById('nogodaDlCircleBar');
    var totalSeconds = 3;
    var currentSec = 3;
    var circumference = 2 * Math.PI * 52; // 326.72
    
    if (circleBar) {
        circleBar.style.strokeDasharray = circumference;
        circleBar.style.strokeDashoffset = 0;
        circleBar.style.transition = 'stroke-dashoffset 1s linear';
    }
    
    if (countdownNum) countdownNum.textContent = '3';
    
    if (window.currentDownloadTimer) clearInterval(window.currentDownloadTimer);
    
    window.currentDownloadTimer = setInterval(function() {
        currentSec--;
        if (currentSec > 0) {
            if (countdownNum) countdownNum.textContent = currentSec;
            if (circleBar) {
                var offset = circumference * ((totalSeconds - currentSec) / totalSeconds);
                circleBar.style.strokeDashoffset = offset;
            }
        } else {
            clearInterval(window.currentDownloadTimer);
            if (countdownNum) countdownNum.textContent = '✓';
            if (circleBar) circleBar.style.strokeDashoffset = circumference;
            
            // Trigger actual browser download
            var trigA = document.createElement('a');
            trigA.href = url;
            trigA.download = name;
            trigA.target = '_blank';
            document.body.appendChild(trigA);
            trigA.click();
            document.body.removeChild(trigA);
            
            // Switch to Thank you screen
            setTimeout(function() {
                var stateCount = document.getElementById('nogodaDlStateCounting');
                var stateFinish = document.getElementById('nogodaDlStateFinished');
                if (stateCount) stateCount.style.display = 'none';
                if (stateFinish) stateFinish.style.display = 'block';
            }, 350);
        }
    }, 1000);
}

function closeNogodaDownloadModal() {
    var modal = document.getElementById('nogodaDownloadModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
    if (window.currentDownloadTimer) clearInterval(window.currentDownloadTimer);
}

// Close on backdrop click & allow clicking entire download card
document.addEventListener('click', function(e) {
    var modal = document.getElementById('nogodaDownloadModal');
    if (modal && e.target === modal) {
        closeNogodaDownloadModal();
    }
    
    var card = e.target.closest('.blog-download-card');
    if (card && !e.target.closest('.blog-dl-trigger-btn')) {
        var btn = card.querySelector('.blog-dl-trigger-btn');
        if (btn) triggerBlogAssetDownload(btn);
    }
});

// ── Inject SVG icons into download cards (TinyMCE strips inline SVGs during save) ──
(function() {
    function getAssetSvgIcon(badgeType) {
        var b = (badgeType || '').toUpperCase();
        if (b.indexOf('VIDEO') !== -1 || b.indexOf('MP4') !== -1) {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="3"/></svg>';
        } else if (b.indexOf('PDF') !== -1 || b.indexOf('DOC') !== -1) {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M10 12v6"/><path d="M14 15v3"/></svg>';
        } else if (b.indexOf('AUDIO') !== -1 || b.indexOf('MP3') !== -1 || b.indexOf('MUSIC') !== -1) {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>';
        } else if (b.indexOf('IMAGE') !== -1 || b.indexOf('PHOTO') !== -1 || b.indexOf('GRAPHIC') !== -1) {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="3"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';
        } else if (b.indexOf('PROMPT') !== -1 || b.indexOf('PRESET') !== -1) {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>';
        } else if (b.indexOf('ZIP') !== -1 || b.indexOf('RAR') !== -1 || b.indexOf('ARCHIVE') !== -1) {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 20V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2h6"/><circle cx="16" cy="19" r="2"/><path d="M18 11v5.5"/><path d="M21 16l-3 3-3-3"/></svg>';
        } else {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>';
        }
    }

    var arrowSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v13"/><path d="m6 11 6 6 6-6"/><path d="M4 21h16"/></svg>';

    function injectDownloadCardIcons() {
        var cards = document.querySelectorAll('.blog-download-card');
        cards.forEach(function(card) {
            var badge = card.getAttribute('data-dl-badge') || '';

            // Inject icon into left badge circle if empty or has no SVG
            var iconBadge = card.querySelector('.blog-dl-icon-badge');
            if (iconBadge && !iconBadge.querySelector('svg')) {
                iconBadge.innerHTML = getAssetSvgIcon(badge);
            }

            // Inject arrow into right trigger button if empty or has no SVG
            var triggerBtn = card.querySelector('.blog-dl-trigger-btn');
            if (triggerBtn && !triggerBtn.querySelector('svg')) {
                triggerBtn.innerHTML = arrowSvg;
            }
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectDownloadCardIcons);
    } else {
        injectDownloadCardIcons();
    }
})();
</script>
</body></html>

