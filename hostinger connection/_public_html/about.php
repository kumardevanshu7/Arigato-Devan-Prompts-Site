<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

$total_prompts = (int)$pdo->query("SELECT COUNT(*) FROM prompts")->fetchColumn();
$total_followers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_unlocks = (int)$pdo->query("SELECT COUNT(*) FROM unlocked_prompts")->fetchColumn();
$total_copies = (int)$pdo->query("SELECT COALESCE(SUM(copy_count),0) FROM prompts")->fetchColumn();
$total_views = (int)$pdo->query("SELECT COALESCE(SUM(view_count),0) FROM prompts")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#2F4156">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Meet the creator behind Arigato Devan — testing and writing AI couple prompts for Gemini &amp; ChatGPT so your generated photos actually look like you.">
    <link rel="canonical" href="https://arigatodevan.com/about.php">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="About Us - Arigato Devan Prompts">
    <meta property="og:description" content="Meet the creator of Arigato Devan - crafting premium AI couple prompts for Instagram Reels using ChatGPT and Gemini.">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/about.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="robots" content="index, follow">
    <?php include_once 'includes/theme_head.php'; ?>
    <link rel="stylesheet" href="css/info-pages.css?v=20260727">
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-store page-info theme-nogoda">

<?php $nav_active = ''; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

<main class="info-page-main">
    <div class="info-page-hero">
        <p class="hero-label">Meet the Creator</p>
        <h1>About <em>Arigato Devan</em></h1>
        <p>Premium AI couple prompts for Instagram Reels — crafted with care, tested on Gemini &amp; ChatGPT.</p>
    </div>

    <div class="about-creator-card">
        <div class="about-profile-section">
        <div class="about-avatar-wrap">
            <div class="profile-flipper" id="profileFlipper" onclick="toggleFlip()" title="Click to flip">
                <div class="profile-inner" id="profileInner">
                    <div class="pf-front"><img loading="lazy" src="aboutmepics/new.webp" alt="Arigato Devan - Now"></div>
                    <div class="pf-back"><img loading="lazy" src="aboutmepics/old.webp" alt="Arigato Devan - Then"></div>
                </div>
            </div>
            <div class="about-verified-badge"><i class="fa-solid fa-check"></i></div>
        </div>
        <div class="flip-hint"><i class="fa-solid fa-rotate"></i> Click to flip</div>
        <h2 class="about-name">Arigato Devan</h2>
        <div class="about-title">AI Prompt Creator &amp; Digital Artist</div>
        <div class="about-handle"><a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i> @arigato.devan</a></div>
        <div class="about-social-row">
            <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener" class="social-icon-btn" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="mailto:devansh.grow@gmail.com" class="social-icon-btn" title="Email"><i class="fa-solid fa-envelope"></i></a>
            <a href="gallery.php" class="social-icon-btn" title="Gallery"><i class="fa-solid fa-images"></i></a>
        </div>
        <div class="about-tags-row">
            <span class="about-tag">#CouplePrompts</span>
            <span class="about-tag">#AIArt</span>
            <span class="about-tag">#ViralReels</span>
            <span class="about-tag">#AestheticShots</span>
            <span class="about-tag">#GeminiPrompts</span>
            <span class="about-tag">#DigitalCreator</span>
        </div>
        </div>

        <div class="about-stats-row">
            <div class="about-stat"><div class="stat-num"><?= $total_prompts ?>+</div><div class="stat-label">Prompts</div></div>
            <div class="about-stat"><div class="stat-num"><?= $total_unlocks ?>+</div><div class="stat-label">Unlocks</div></div>
            <div class="about-stat"><div class="stat-num"><?= $total_followers ?>+</div><div class="stat-label">Followers</div></div>
            <div class="about-stat"><div class="stat-num"><?= $total_copies ?>+</div><div class="stat-label">Copies</div></div>
            <div class="about-stat"><div class="stat-num"><?= $total_views ?>+</div><div class="stat-label">Views</div></div>
        </div>
    </div>

    <div class="info-card">
        <h2><i class="fa-solid fa-user"></i>About Me</h2>
        <p>Hello! I'm the creator behind <strong>Arigato Devan</strong> — a platform dedicated to crafting beautiful, ready-to-use AI couple prompts for Gemini and ChatGPT.</p>
        <p>Every prompt here is carefully designed to produce stunning visuals — from cinematic couple portraits to viral-worthy aesthetic shots. Just copy, paste into your favourite AI tool, and create magic.</p>
    </div>

    <div class="info-card">
        <h2><i class="fa-solid fa-lightbulb"></i>Why Arigato Devan Exists</h2>
        <p>I started Arigato Devan after noticing how many "AI couple prompt" lists online were copy-pasted from each other, never actually tested, and often produced two completely different-looking strangers instead of a real couple photo. That felt broken, so I began writing and testing my own prompts on Gemini and ChatGPT — checking that faces stayed consistent with the reference photo, that lighting and poses looked natural, and that the result was actually shareable.</p>
        <p>That testing process turned into this site: a constantly growing, categorised library of couple prompts — romantic, wedding, festival, cinematic, and everyday — built around one rule: if a prompt doesn't produce a believable, good-looking result, it doesn't get published.</p>
    </div>

    <div class="about-grid">
        <div class="about-info-card"><div class="aic-icon purple"><i class="fa-solid fa-robot"></i></div><h3>AI Tools Used</h3><p>Every prompt is written and tested on <strong>Google Gemini (Nano Banana)</strong> and <strong>ChatGPT Image</strong> — the two tools our prompts are built around. Some may work with light editing on other AI image generators, but results aren't guaranteed outside Gemini and ChatGPT.</p></div>
        <div class="about-info-card"><div class="aic-icon pink"><i class="fa-solid fa-lock"></i></div><h3>Your Privacy</h3><p>We use <strong>Google OAuth</strong> for secure login — only your name and email. No passwords stored. Your data is protected and never sold.</p></div>
        <div class="about-info-card"><div class="aic-icon green"><i class="fa-solid fa-globe"></i></div><h3>Platform &amp; Security</h3><p>Runs on <strong>Hostinger</strong> hosting, protected by <strong>Cloudflare</strong> CDN. Analytics via Google Analytics 4 and Search Console.</p></div>
        <div class="about-info-card"><div class="aic-icon blue"><i class="fa-brands fa-instagram"></i></div><h3>Community</h3><p>Most users discover us through Instagram. Follow <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener">@arigato.devan</a> for new prompt drops and tutorials every week.</p></div>
    </div>

    <div class="about-cta">
        <h2>Ready to create something beautiful?</h2>
        <p>Browse our full collection of prompts or reach out if you have questions!</p>
        <div class="cta-btns">
            <a href="gallery.php" class="cta-btn cta-btn-primary"><i class="fa-solid fa-images"></i> Browse Prompts</a>
            <a href="contact.php" class="cta-btn cta-btn-ghost"><i class="fa-solid fa-envelope"></i> Contact Us</a>
            <a href="feedback.php" class="cta-btn cta-btn-ghost"><i class="fa-solid fa-comment-dots"></i> Give Feedback</a>
            <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener" class="cta-btn cta-btn-ghost"><i class="fa-brands fa-instagram"></i> Instagram</a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<script>
var isFlipped = false;
function toggleFlip() {
    isFlipped = !isFlipped;
    document.getElementById('profileInner').classList.toggle('flipped', isFlipped);
}
setTimeout(function() { toggleFlip(); setInterval(toggleFlip, 5000); }, 5000);
</script>
</body>
</html>
