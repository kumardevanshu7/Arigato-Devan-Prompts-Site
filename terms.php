<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service "” Arigato Devan PromptVerse</title>
    <meta name="description" content="Terms of Service for Arigato Devan PromptVerse. Usage rules, ownership, and platform guidelines.">
    <link rel="stylesheet" href="style.css?v=1777723415">
    <style>
        .legal-wrap { max-width: 780px; margin: 40px auto; padding: 0 24px 100px; }
        .legal-card { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 24px; padding: 48px 48px; box-shadow: var(--shadow-comic); }
        .legal-card h1 { font-size: 2.4rem; font-weight: 900; margin-bottom: 8px; }
        .legal-date { font-size: 0.85rem; font-weight: 600; color: #888; margin-bottom: 32px; }
        .legal-card h2 { font-size: 1.3rem; font-weight: 900; margin-top: 36px; margin-bottom: 10px; padding-left: 12px; border-left: 4px solid var(--primary-dark); }
        .legal-card p, .legal-card li { font-size: 1.05rem; line-height: 1.7; color: #444; margin-bottom: 14px; font-weight: 500; }
        .legal-card ul { padding-left: 24px; }
        .legal-card li { margin-bottom: 10px; }
        .legal-highlight { background: var(--secondary-color); border: var(--border-width) solid var(--text-color); border-radius: 12px; padding: 16px 20px; margin: 24px 0; font-weight: 700; color: var(--text-color); box-shadow: 3px 3px 0px var(--text-color); }
        @media (max-width: 600px) { .legal-card { padding: 28px 20px; } .legal-card h1 { font-size: 1.8rem; } }
    </style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Film Strip Background Layer -->
    <div class="filmstrip-bg" aria-hidden="true" style="opacity: 0.15; filter: blur(8px); z-index: -1; position: fixed; inset: 0; pointer-events: none; transform: scale(1.1) rotateX(10deg) rotateY(-5deg) translateZ(-50px); transform-style: preserve-3d; perspective: 1000px;">
        <div class="filmstrip-row row-1">
            <div class="filmstrip-track">
                <?php
                $strip_imgs = [];
                for($i=1; $i<=17; $i++) $strip_imgs[] = "landingpics/lan$i.png";
                $all = array_merge($strip_imgs, $strip_imgs);
                foreach($all as $img): ?>
                <div class="filmstrip-frame"><img src="<?= $img ?>" alt="" loading="lazy"></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="filmstrip-row row-2">
            <div class="filmstrip-track track-reverse">
                <?php foreach(array_merge(array_reverse($strip_imgs), array_reverse($strip_imgs)) as $img): ?>
                <div class="filmstrip-frame"><img src="<?= $img ?>" alt="" loading="lazy"></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="filmstrip-overlay" style="background: linear-gradient(to bottom, var(--bg-color) 0%, transparent 20%, transparent 80%, var(--bg-color) 100%);"></div>
    </div>
    <header>
        <div class="logo-area" onclick="window.location.href='index.php'" style="cursor:pointer;">
            <div class="logo-flipper">
                <div class="logo-front">
                    <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo">
                </div>
                <div class="logo-back">
                    <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt="Logo Alt">
                </div>
            </div>
            <div class="logo-text">ARIGATO<br>DEVAN</div>
        </div>
        <nav class="nav-links">
            <a href="index.php">HOME</a>
            <a href="gallery.php">GALLERY</a>
        </nav>
    </header>

    <div class="legal-wrap">
        <div class="legal-card">
            <h1>Terms of Service</h1>
            <div class="legal-date">Effective Date: <?= date('F d, Y') ?></div>

            <div class="legal-highlight">
                <i class="fa-solid fa-clipboard"></i> By accessing or using PromptVerse, you agree to these Terms. Please read carefully before using the platform.
            </div>

            <h2>1. Acceptance of Terms</h2>
            <p>By accessing the PromptVerse platform ("Service") operated by Arigato Devan, you agree to be bound by these Terms of Service. If you do not agree, please do not use the Service.</p>

            <h2>2. Use of Prompts</h2>
            <p>The prompts provided on PromptVerse are licensed for <strong>personal and commercial use</strong>, subject to the following conditions:</p>
            <ul>
                <li>You may use unlocked prompts to generate images for your own content, including social media, blogs, and commercial projects.</li>
                <li>You may <strong>NOT</strong> resell, redistribute, or share the raw prompt text as a product without prior written permission.</li>
                <li>You may <strong>NOT</strong> claim original authorship of the prompts themselves.</li>
                <li>Generated images belong to you, subject to the terms of the AI platform used to create them.</li>
            </ul>

            <h2>3. Intellectual Property & Ownership</h2>
            <p>All prompts, content, branding, and design elements on PromptVerse are the intellectual property of Arigato Devan unless otherwise noted. The prompt text is curated and crafted by Arigato Devan and protected under applicable copyright laws.</p>
            <p>Images generated using our prompts are your own creations. We claim no ownership over AI-generated outputs produced by you using our prompts.</p>

            <h2>4. Unlock Codes & Access</h2>
            <ul>
                <li>Unlock codes are provided exclusively through official channels (Instagram Reels, community giveaways, etc.).</li>
                <li>Sharing, selling, or distributing unlock codes without authorization is strictly prohibited.</li>
                <li>We reserve the right to revoke access to any account found misusing codes.</li>
            </ul>

            <h2>5. User Accounts</h2>
            <ul>
                <li>You must be at least 13 years old to use this service.</li>
                <li>You are responsible for maintaining the security of your Google account.</li>
                <li>We reserve the right to suspend or terminate accounts that violate these terms.</li>
            </ul>

            <h2>6. Prohibited Conduct</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Use the platform to create, share, or promote illegal, harmful, or offensive content</li>
                <li>Attempt to reverse-engineer, scrape, or copy the platform's content or code</li>
                <li>Circumvent any security or access control measures</li>
                <li>Use automated bots or scripts to interact with the platform</li>
            </ul>

            <h2>7. Changes to Terms</h2>
            <p>We reserve the right to modify these Terms at any time. Continued use of the platform after changes constitutes acceptance of the new Terms.</p>

            <h2>8. Contact</h2>
            <p>For any questions regarding these Terms, reach out via Instagram: <a href="https://www.instagram.com/arigato.devan/" target="_blank" style="color:var(--primary-dark); font-weight:700;">@arigato.devan</a></p>

            <div style="text-align:center; margin-top:40px;">
                <a href="index.php" class="comic-btn-small"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <footer>
        <div>&copy; 2026 ARIGATO DEVAN. KEEP CREATING.</div>
        <div class="footer-links">
            <a href="disclaimer.php">DISCLAIMER</a>
            <a href="terms.php">TERMS OF SERVICE</a>
        </div>
    </footer>
</body>
</html>





