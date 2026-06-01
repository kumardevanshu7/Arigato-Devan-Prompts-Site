<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Meet the creator behind Arigato Devan PromptVerse — a platform crafting beautiful AI prompts for couples and creative souls.">
    <link rel="canonical" href="https://arigatodevan.com/about.php">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="About Us — Arigato Devan PromptVerse">
    <meta property="og:description" content="Meet the creator of Arigato Devan — crafting premium AI couple prompts for Instagram Reels using ChatGPT and Gemini.">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/about.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="About Us — Arigato Devan PromptVerse">
    <meta name="twitter:description" content="Meet the creator of Arigato Devan — crafting premium AI couple prompts for Instagram Reels.">
    <meta name="twitter:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <link rel="stylesheet" href="style.min.css?v=20260601">
    <style>
        .about-wrap { max-width: 960px; margin: 0 auto; padding: 40px 24px 100px; }

        /* Hero card */
        .about-hero { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 28px; padding: 48px 48px 40px; box-shadow: var(--shadow-comic); display: flex; gap: 48px; align-items: center; flex-wrap: wrap; margin-bottom: 32px; }

        /* Profile flip */
        .profile-flip-wrap { flex-shrink: 0; display: flex; flex-direction: column; align-items: center; gap: 14px; }
        .profile-flipper { width: 170px; height: 170px; perspective: 700px; cursor: pointer; }
        .profile-inner { width: 100%; height: 100%; position: relative; transform-style: preserve-3d; transition: transform 0.9s cubic-bezier(.4,0,.2,1); }
        .profile-inner.flipped { transform: rotateY(180deg); }
        .pf-front, .pf-back { position: absolute; inset: 0; backface-visibility: hidden; -webkit-backface-visibility: hidden; border-radius: 50%; overflow: hidden; border: 4px solid var(--text-color); box-shadow: 5px 5px 0 var(--text-color); }
        .pf-back { transform: rotateY(180deg); }
        .pf-front img, .pf-back img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .flip-hint { font-size: .72rem; font-weight: 700; color: #aaa; text-align: center; }

        /* About text */
        .about-bio { flex: 1; min-width: 240px; }
        .about-bio h1 { font-size: 2.2rem; font-weight: 900; margin-bottom: 4px; }
        .about-bio .handle { font-size: .9rem; font-weight: 700; color: #888; margin-bottom: 20px; }
        .about-bio p { font-size: 1rem; line-height: 1.75; color: #555; font-weight: 500; margin-bottom: 14px; }
        .about-badge-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 18px 0; }
        .about-badge { background: var(--secondary-color); border: 2px solid var(--text-color); border-radius: 20px; padding: 5px 14px; font-size: .78rem; font-weight: 800; box-shadow: 2px 2px 0 var(--text-color); display: inline-flex; align-items: center; gap: 6px; }

        /* Info grid */
        .about-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .about-info-card { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 20px; padding: 24px 26px; box-shadow: var(--shadow-comic); }
        .about-info-card .aic-icon { width: 44px; height: 44px; background: var(--primary-color); border: 2.5px solid var(--text-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: 14px; box-shadow: 2px 2px 0 var(--text-color); }
        .about-info-card h3 { font-size: 1rem; font-weight: 900; margin-bottom: 8px; }
        .about-info-card p { font-size: .88rem; line-height: 1.65; color: #666; font-weight: 500; margin: 0; }

        /* Stats */
        .about-stats { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 20px; padding: 24px 32px; box-shadow: var(--shadow-comic); display: flex; gap: 0; align-items: stretch; flex-wrap: wrap; margin-bottom: 32px; }
        .stat-item { flex: 1; min-width: 120px; text-align: center; padding: 16px 20px; border-right: 2px dashed var(--border-color); }
        .stat-item:last-child { border-right: none; }
        .stat-num { font-size: 2rem; font-weight: 900; color: var(--primary-dark); line-height: 1; }
        .stat-label { font-size: .72rem; font-weight: 800; color: #888; text-transform: uppercase; letter-spacing: .05em; margin-top: 6px; }

        /* CTA */
        .about-cta { background: var(--primary-color); border: var(--border-width) solid var(--text-color); border-radius: 20px; padding: 32px; box-shadow: var(--shadow-comic); text-align: center; }
        .about-cta h2 { font-size: 1.5rem; font-weight: 900; margin-bottom: 10px; }
        .about-cta p { font-size: .95rem; color: #555; margin-bottom: 22px; font-weight: 500; }
        .cta-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .cta-btn-main { background: var(--card-bg); border: 2.5px solid var(--text-color); border-radius: 12px; padding: 12px 24px; font-family: var(--font-main); font-weight: 800; font-size: .92rem; cursor: pointer; box-shadow: 3px 3px 0 var(--text-color); text-decoration: none; color: var(--text-color); display: inline-flex; align-items: center; gap: 8px; transition: all .15s; }
        .cta-btn-main:hover { transform: translateY(-2px); box-shadow: 4px 4px 0 var(--text-color); }

        @media (max-width: 660px) {
            .about-hero { padding: 30px 22px; gap: 28px; justify-content: center; text-align: center; }
            .about-bio h1 { font-size: 1.7rem; }
            .about-badge-row { justify-content: center; }
            .about-wrap { padding: 24px 14px 80px; }
        }
    </style>
    <?php include_once "gtag.php"; ?>
</head>
<body>
<!-- Aurora -->
<div class="aurora-bg" aria-hidden="true" style="position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none;">
    <div style="position:absolute;width:65%;height:65%;background:radial-gradient(circle,#c8b4f8,#e9d8fd);border-radius:50%;filter:blur(90px);opacity:.55;top:-15%;left:-10%;animation:auroraFloat1 12s ease-in-out infinite;"></div>
    <div style="position:absolute;width:55%;height:55%;background:radial-gradient(circle,#ffb3c6,#ffd6e7);border-radius:50%;filter:blur(90px);opacity:.55;bottom:-20%;right:-10%;animation:auroraFloat2 15s ease-in-out infinite;"></div>
    <div style="position:absolute;width:45%;height:45%;background:radial-gradient(circle,#a5f3fc,#e0f2fe);border-radius:50%;filter:blur(90px);opacity:.55;top:30%;right:5%;animation:auroraFloat3 10s ease-in-out infinite;"></div>
</div>
<style>
.aurora-bg~*{position:relative;z-index:1;}
@keyframes auroraFloat1{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(6%,8%) scale(1.08);}66%{transform:translate(-4%,5%) scale(0.95);}}
@keyframes auroraFloat2{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(-8%,-6%) scale(1.06);}66%{transform:translate(5%,-3%) scale(0.97);}}
@keyframes auroraFloat3{0%,100%{transform:translate(0,0) scale(1);}50%{transform:translate(-10%,8%) scale(1.1);}}
</style>

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

    <!-- Hero -->
    <div class="about-hero">
        <div class="profile-flip-wrap">
            <div class="profile-flipper" id="profileFlipper" onclick="toggleFlip()" title="Click to flip">
                <div class="profile-inner" id="profileInner">
                    <div class="pf-front">
                        <img loading="lazy" src="aboutmepics/new.webp" alt="Arigato Devan — Now">
                    </div>
                    <div class="pf-back">
                        <img loading="lazy" src="aboutmepics/old.webp" alt="Arigato Devan — Then">
                    </div>
                </div>
            </div>
            <div class="flip-hint"><i class="fa-solid fa-rotate"></i> Click to flip</div>
        </div>

        <div class="about-bio">
            <h1>Arigato Devan</h1>
            <div class="handle"><i class="fa-brands fa-instagram"></i> @arigato.devan</div>
            <p>Hello! I'm the creator behind <strong>Arigato Devan PromptVerse</strong> — a platform dedicated to crafting beautiful, ready-to-use AI prompts for couples, romantics, and creative souls.</p>
            <p>Every prompt here is carefully designed to produce stunning visuals — from cinematic couple portraits to viral-worthy aesthetic shots. Just copy, paste into your favourite AI tool, and create magic.</p>
            <div class="about-badge-row">
                <span class="about-badge"><i class="fa-solid fa-heart"></i> Couple Prompts</span>
                <span class="about-badge"><i class="fa-solid fa-sparkles"></i> Lovely Prompts</span>
                <span class="about-badge"><i class="fa-solid fa-wand-magic-sparkles"></i> AI Art</span>
                <span class="about-badge"><i class="fa-brands fa-instagram"></i> Instagram Viral</span>
            </div>
        </div>
    </div>

    <!-- Info grid -->
    <div class="about-grid">
        <div class="about-info-card">
            <div class="aic-icon"><i class="fa-solid fa-robot"></i></div>
            <h3>AI Tools Used</h3>
            <p>All prompts are primarily crafted and tested with <strong>ChatGPT (DALL·E)</strong> and <strong>Google Gemini</strong>. However, they work beautifully on any AI image platform — Midjourney, Adobe Firefly, Stable Diffusion, and more.</p>
        </div>
        <div class="about-info-card">
            <div class="aic-icon" style="background:var(--secondary-color);"><i class="fa-solid fa-lock"></i></div>
            <h3>Your Privacy</h3>
            <p>We use <strong>Google OAuth</strong> for secure login — we only access your name and email. No passwords are stored. Your data is protected and never sold to third parties.</p>
        </div>
        <div class="about-info-card">
            <div class="aic-icon" style="background:#d1fae5;"><i class="fa-solid fa-globe"></i></div>
            <h3>Platform & Security</h3>
            <p>The site runs on <strong>Hostinger</strong> hosting, protected by <strong>Cloudflare</strong> CDN. Performance and analytics are tracked via <strong>Google Analytics 4</strong> and <strong>Google Search Console</strong>.</p>
        </div>
        <div class="about-info-card">
            <div class="aic-icon" style="background:#fce7f3;"><i class="fa-brands fa-instagram"></i></div>
            <h3>Community</h3>
            <p>Most of our users discover us through <strong>Instagram</strong>. Follow <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener" style="color:var(--primary-dark);font-weight:700;">@arigato.devan</a> for new prompt drops, tutorials, and inspiration every week.</p>
        </div>
    </div>

    <!-- CTA -->
    <div class="about-cta">
        <h2>Ready to create something beautiful?</h2>
        <p>Browse our full collection of prompts or reach out if you have questions, suggestions, or just want to say hi!</p>
        <div class="cta-btns">
            <a href="gallery.php" class="cta-btn-main"><i class="fa-solid fa-images"></i> Browse Prompts</a>
            <a href="contact.php" class="cta-btn-main"><i class="fa-solid fa-envelope"></i> Contact Us</a>
            <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener" class="cta-btn-main"><i class="fa-brands fa-instagram"></i> Instagram</a>
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
// Auto-flip after 5 seconds, then toggle every 5 seconds
setTimeout(function() {
    toggleFlip();
    setInterval(toggleFlip, 5000);
}, 5000);
</script>
</body>
</html>
