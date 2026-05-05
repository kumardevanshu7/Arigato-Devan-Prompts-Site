<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disclaimer "Ã¢â‚¬Â Arigato Devan PromptVerse</title>
    <meta name="description" content="AI-generated content disclaimer for Arigato Devan PromptVerse. Understand the nature of AI prompts and content.">
    <link rel="stylesheet" href="style.css?v=1777999999">
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Film Strip Background Layer -->
    <div class="filmstrip-bg" aria-hidden="true" style="opacity: 0.15; filter: blur(8px); z-index: -1; position: fixed; inset: 0; pointer-events: none; transform: scale(1.1) rotateX(10deg) rotateY(-5deg) translateZ(-50px); transform-style: preserve-3d; perspective: 1000px;">
        <div class="filmstrip-row row-1">
            <div class="filmstrip-track">
                <?php
                $strip_imgs = [];
                for($i=1; $i<=17; $i++) $strip_imgs[] = "landingpics/lan$i.webp";
                $all = array_merge($strip_imgs, $strip_imgs);
                foreach($all as $img): ?>
                <div class="filmstrip-frame">
                            <picture>
                                <source srcset="<?= $img ?>" type="image/webp">
                                <img src="<?= str_replace('.webp', '.png', $img) ?>" alt="" loading="lazy">
                            </picture>
                        </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="filmstrip-row row-2">
            <div class="filmstrip-track track-reverse">
                <?php foreach(array_merge(array_reverse($strip_imgs), array_reverse($strip_imgs)) as $img): ?>
                <div class="filmstrip-frame">
                            <picture>
                                <source srcset="<?= $img ?>" type="image/webp">
                                <img src="<?= str_replace('.webp', '.png', $img) ?>" alt="" loading="lazy">
                            </picture>
                        </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="filmstrip-overlay" style="background: linear-gradient(to bottom, var(--bg-color) 0%, transparent 20%, transparent 80%, var(--bg-color) 100%);"></div>
    </div>
    <header>
        <div class="logo-area"  style="cursor:pointer;">
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
            <h1>Disclaimer</h1>
            <div class="legal-date">Effective Date: <?= date('F d, Y') ?></div>

            <div class="legal-highlight">
                <i class="fa-solid fa-triangle-exclamation"></i> All prompts on this platform are designed for AI image generators. Results may vary based on the tool, settings, and version you use.
            </div>

            <h2>AI-Generated Content</h2>
            <p>The prompts provided on PromptVerse are crafted for use with AI image generation tools such as Midjourney, ChatGPT, Adobe Firefly, Stable Diffusion, and others. By using our prompts, you acknowledge the following:</p>
            <ul>
                <li><strong>No Exact Replications:</strong> AI models are non-deterministic. The same prompt will produce different results each time and across different tools. Our examples are demonstrations, not guarantees.</li>
                <li><strong>Tool Dependency:</strong> Output quality depends heavily on the specific AI model version, settings, seed number, and aspect ratio you use.</li>
                <li><strong>Content Variation:</strong> Results may differ significantly from preview images shown on our Instagram or website.</li>
                <li><strong>No Guarantee of Virality:</strong> While our prompts are optimized for aesthetics and social media, we make no guarantees regarding reach, engagement, or performance on any platform.</li>
            </ul>

            <h2>Third-Party Platforms</h2>
            <p>PromptVerse relies on third-party authentication (Google OAuth) and image generation platforms. We are not responsible for:</p>
            <ul>
                <li>Downtime or policy changes on these platforms</li>
                <li>Changes in AI model behavior or capabilities</li>
                <li>Loss of data due to third-party service failures</li>
            </ul>

            <h2>No Professional Advice</h2>
            <p>Nothing on this platform constitutes legal, financial, creative, or professional advice. All content is provided for educational and entertainment purposes only.</p>

            <h2>Limitation of Liability</h2>
            <p>Under no circumstances shall Arigato Devan, its creators, or affiliates be held liable for any direct, indirect, incidental, or consequential damages resulting from the use of prompts, generated content, or any information on this platform. You assume full responsibility for ensuring your generated content complies with local laws and applicable platform terms of service.</p>

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





