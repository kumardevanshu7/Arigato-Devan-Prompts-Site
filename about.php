<?php
session_start();
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
    <meta name="theme-color" content="#c084fc">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Meet the creator behind Arigato Devan PromptVerse - a platform crafting beautiful AI prompts for couples and creative souls.">
    <link rel="canonical" href="https://arigatodevan.com/about.php">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="About Us - Arigato Devan PromptVerse">
    <meta property="og:description" content="Meet the creator of Arigato Devan - crafting premium AI couple prompts for Instagram Reels using ChatGPT and Gemini.">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/about.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="About Us - Arigato Devan PromptVerse">
    <meta name="twitter:description" content="Meet the creator of Arigato Devan - crafting premium AI couple prompts for Instagram Reels.">
    <meta name="twitter:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <link rel="stylesheet" href="style.min.css?v=20260601">
    <style>
        html, body { margin:0; height:100%; background:transparent !important; }
        body::before { content:''; position:fixed; inset:0; z-index:-2; background-image:url('backgroundwally/only-homepage-pic.webp'); background-size:cover; background-position:center top; background-repeat:no-repeat; }
        body::after { content:''; position:fixed; inset:0; z-index:-1; background:rgba(0,0,0,0.52); pointer-events:none; }
        @media (max-width:640px) { body::before { background-image:url('backgroundwally/only-homepage-pic-for-mobile.webp'); background-position:center center; } }
        .aurora-bg { display:none !important; }
        .about-wrap { max-width:880px; margin:0 auto; padding:40px 24px 100px; position:relative; z-index:1; }
        .about-profile-section { text-align:center; margin-bottom:36px; }
        .about-avatar-wrap { position:relative; display:inline-block; margin-bottom:8px; }
        .profile-flipper { width:140px; height:140px; perspective:700px; cursor:pointer; margin:0 auto; }
        .profile-inner { width:100%; height:100%; position:relative; transform-style:preserve-3d; transition:transform 0.9s cubic-bezier(.4,0,.2,1); }
        .profile-inner.flipped { transform:rotateY(180deg); }
        .pf-front, .pf-back { position:absolute; inset:0; backface-visibility:hidden; -webkit-backface-visibility:hidden; border-radius:50%; overflow:hidden; border:4px solid rgba(255,255,255,0.5); box-shadow:0 6px 24px rgba(0,0,0,0.4); }
        .pf-back { transform:rotateY(180deg); }
        .pf-front img, .pf-back img { width:100%; height:100%; object-fit:cover; display:block; }
        .about-verified-badge { position:absolute; bottom:6px; right:6px; width:28px; height:28px; background:linear-gradient(135deg,#c084fc,#f43f5e); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.7rem; color:#fff; border:2.5px solid rgba(0,0,0,0.3); box-shadow:0 2px 8px rgba(192,132,252,0.5); }
        .flip-hint { font-size:.7rem; font-weight:600; color:rgba(255,255,255,0.5); margin-top:8px; }
        .about-name { font-size:2rem; font-weight:900; color:#fff; margin:12px 0 2px; text-shadow:0 2px 16px rgba(0,0,0,0.5); }
        .about-title { font-size:.88rem; font-weight:700; color:rgba(255,255,255,0.6); margin-bottom:4px; letter-spacing:.5px; }
        .about-handle { font-size:.85rem; font-weight:700; color:#f9a8d4; margin-bottom:16px; }
        .about-handle a { color:#f9a8d4; text-decoration:none; }
        .about-handle a:hover { text-decoration:underline; }
        .about-social-row { display:flex; gap:10px; justify-content:center; margin-bottom:20px; }
        .social-icon-btn { width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,0.1); border:1.5px solid rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.8); font-size:.95rem; transition:all .2s; text-decoration:none; }
        .social-icon-btn:hover { background:rgba(255,255,255,0.2); color:#fff; transform:translateY(-2px); }
        .about-tags-row { display:flex; flex-wrap:wrap; gap:8px; justify-content:center; margin-bottom:28px; }
        .about-tag { padding:6px 14px; border-radius:20px; font-size:.72rem; font-weight:800; color:rgba(255,255,255,0.85); background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.18); letter-spacing:.3px; transition:all .15s; }
        .about-tag:hover { background:rgba(255,255,255,0.2); }
        .about-stats-row { display:flex; gap:0; justify-content:center; margin-bottom:32px; background:rgba(255,255,255,0.08); backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); border:1px solid rgba(255,255,255,0.12); border-radius:20px; overflow:hidden; }
        .about-stat { flex:1; text-align:center; padding:18px 12px; border-right:1px solid rgba(255,255,255,0.1); }
        .about-stat:last-child { border-right:none; }
        .about-stat .stat-num { font-size:1.6rem; font-weight:900; color:#fff; line-height:1; }
        .about-stat .stat-label { font-size:.62rem; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:.8px; margin-top:4px; }
        .about-bio-card { background:rgba(255,255,255,0.08); backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px); border:1px solid rgba(255,255,255,0.12); border-radius:22px; padding:32px 28px; margin-bottom:24px; }
        .about-bio-card h2 { font-size:1.2rem; font-weight:900; color:#fff; margin-bottom:16px; letter-spacing:.3px; }
        .about-bio-card p { font-size:.92rem; line-height:1.8; color:rgba(255,255,255,0.75); font-weight:500; margin-bottom:12px; }
        .about-bio-card p strong { color:#fff; }
        .about-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px,1fr)); gap:16px; margin-bottom:24px; }
        .about-info-card { background:rgba(255,255,255,0.08); backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); border:1px solid rgba(255,255,255,0.12); border-radius:18px; padding:22px 20px; transition:all .2s; }
        .about-info-card:hover { background:rgba(255,255,255,0.12); transform:translateY(-2px); }
        .aic-icon { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1rem; margin-bottom:12px; color:#fff; }
        .aic-icon.purple { background:linear-gradient(135deg,#c084fc,#8b5cf6); }
        .aic-icon.pink   { background:linear-gradient(135deg,#f9a8d4,#ec4899); }
        .aic-icon.green  { background:linear-gradient(135deg,#34d399,#10b981); }
        .aic-icon.blue   { background:linear-gradient(135deg,#60a5fa,#3b82f6); }
        .about-info-card h3 { font-size:.92rem; font-weight:900; color:#fff; margin-bottom:6px; }
        .about-info-card p  { font-size:.82rem; line-height:1.6; color:rgba(255,255,255,0.6); font-weight:500; margin:0; }
        .about-info-card p a { color:#f9a8d4; font-weight:700; }
        .about-cta { background:linear-gradient(135deg,rgba(192,132,252,0.2),rgba(249,168,212,0.15)); backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px); border:1px solid rgba(255,255,255,0.15); border-radius:22px; padding:32px 28px; text-align:center; }
        .about-cta h2 { font-size:1.3rem; font-weight:900; color:#fff; margin-bottom:8px; }
        .about-cta p  { font-size:.88rem; color:rgba(255,255,255,0.6); margin-bottom:20px; font-weight:500; }
        .cta-btns { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
        .cta-btn { padding:11px 22px; border-radius:14px; font-family:var(--font-main); font-weight:800; font-size:.85rem; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:all .15s; }
        .cta-btn-primary { background:linear-gradient(135deg,#c084fc,#ec4899); color:#fff; border:none; }
        .cta-btn-primary:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(192,132,252,0.4); }
        .cta-btn-ghost { background:rgba(255,255,255,0.1); color:#fff; border:1px solid rgba(255,255,255,0.2); }
        .cta-btn-ghost:hover { background:rgba(255,255,255,0.18); transform:translateY(-2px); }
        @media (max-width:660px) { .about-wrap { padding:24px 14px 80px; } .about-name { font-size:1.6rem; } .profile-flipper { width:120px; height:120px; } .about-stat .stat-num { font-size:1.3rem; } .about-bio-card { padding:24px 18px; } }
    </style>
    <?php include_once "gtag.php"; ?>
</head>
<body>
<div class="aurora-bg" aria-hidden="true" style="position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none;"></div>
<style>.aurora-bg~*{position:relative;z-index:1;}</style>

<header>
    <div class="logo-area" style="cursor:pointer;" onclick="window.location='index.php'">
        <div class="logo-flipper">
            <div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo"></div>
            <div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="index.php">HOME</a>
        <a href="gallery.php">GALLERY</a>
        <a href="contact.php">CONTACT</a>
    </nav>
    <div class="header-right">
        <div class="header-divider"></div>
        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
        <?php else: ?>
            <a href="login.php" class="comic-btn" style="font-size:.85rem;padding:9px 18px;text-decoration:none;">LOGIN</a>
        <?php endif; ?>
    </div>
</header>

<div class="about-wrap">
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
        <h1 class="about-name">Arigato Devan</h1>
        <div class="about-title">AI Prompt Creator & Digital Artist</div>
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
            <span class="about-tag">#PromptVerse</span>
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
    <div class="about-bio-card">
        <h2><i class="fa-solid fa-user" style="margin-right:8px;color:#c084fc;"></i>About Me</h2>
        <p>Hello! I'm the creator behind <strong>Arigato Devan PromptVerse</strong> - a platform dedicated to crafting beautiful, ready-to-use AI prompts for couples, romantics, and creative souls.</p>
        <p>Every prompt here is carefully designed to produce stunning visuals - from cinematic couple portraits to viral-worthy aesthetic shots. Just copy, paste into your favourite AI tool, and create magic.</p>
    </div>
    <div class="about-grid">
        <div class="about-info-card"><div class="aic-icon purple"><i class="fa-solid fa-robot"></i></div><h3>AI Tools Used</h3><p>All prompts are tested with <strong style="color:#fff;">ChatGPT (DALL-E)</strong> and <strong style="color:#fff;">Google Gemini</strong>. They also work on Midjourney, Adobe Firefly, Stable Diffusion and more.</p></div>
        <div class="about-info-card"><div class="aic-icon pink"><i class="fa-solid fa-lock"></i></div><h3>Your Privacy</h3><p>We use <strong style="color:#fff;">Google OAuth</strong> for secure login - only your name and email. No passwords stored. Your data is protected and never sold.</p></div>
        <div class="about-info-card"><div class="aic-icon green"><i class="fa-solid fa-globe"></i></div><h3>Platform & Security</h3><p>Runs on <strong style="color:#fff;">Hostinger</strong> hosting, protected by <strong style="color:#fff;">Cloudflare</strong> CDN. Analytics via Google Analytics 4 and Search Console.</p></div>
        <div class="about-info-card"><div class="aic-icon blue"><i class="fa-brands fa-instagram"></i></div><h3>Community</h3><p>Most users discover us through Instagram. Follow <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener">@arigato.devan</a> for new prompt drops and tutorials every week.</p></div>
    </div>
    <div class="about-cta">
        <h2>Ready to create something beautiful?</h2>
        <p>Browse our full collection of prompts or reach out if you have questions!</p>
        <div class="cta-btns">
            <a href="gallery.php" class="cta-btn cta-btn-primary"><i class="fa-solid fa-images"></i> Browse Prompts</a>
            <a href="contact.php" class="cta-btn cta-btn-ghost"><i class="fa-solid fa-envelope"></i> Contact Us</a>
            <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener" class="cta-btn cta-btn-ghost"><i class="fa-brands fa-instagram"></i> Instagram</a>
        </div>
    </div>
</div>

<footer>
    <div>&copy; <?= date("Y") ?> ARIGATO DEVAN. KEEP CREATING.</div>
    <div class="footer-links"><a href="about.php">ABOUT</a><a href="contact.php">CONTACT</a><a href="faq.php">FAQ</a><a href="privacy.php">PRIVACY POLICY</a><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
</footer>

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
