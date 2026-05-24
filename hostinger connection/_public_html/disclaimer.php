<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disclaimer &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="AI-generated content disclaimer for Arigato Devan PromptVerse. Understand the nature of AI prompts and content.">
    <link rel="canonical" href="https://arigatodevan.com/disclaimer.php">
    <link rel="stylesheet" href="style.css?v=2026052201">
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
    <?php include_once "gtag.php"; ?>
</head>
<body>
    <!-- Aurora Background -->
    <div class="aurora-bg" aria-hidden="true" style="position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none;background:transparent;">
        <div style="position:absolute;width:65%;height:65%;background:radial-gradient(circle,#c8b4f8,#e9d8fd);border-radius:50%;filter:blur(90px);opacity:.55;top:-15%;left:-10%;animation:auroraFloat1 12s ease-in-out infinite;"></div>
        <div style="position:absolute;width:55%;height:55%;background:radial-gradient(circle,#ffb3c6,#ffd6e7);border-radius:50%;filter:blur(90px);opacity:.55;bottom:-20%;right:-10%;animation:auroraFloat2 15s ease-in-out infinite;"></div>
        <div style="position:absolute;width:45%;height:45%;background:radial-gradient(circle,#a5f3fc,#e0f2fe);border-radius:50%;filter:blur(90px);opacity:.55;top:30%;right:5%;animation:auroraFloat3 10s ease-in-out infinite;"></div>
        <div style="position:absolute;width:40%;height:40%;background:radial-gradient(circle,#fde68a,#fef9c3);border-radius:50%;filter:blur(90px);opacity:.55;bottom:10%;left:10%;animation:auroraFloat4 13s ease-in-out infinite;"></div>
    </div>
    <style>
    .aurora-bg~*{position:relative;z-index:1;}
    @keyframes auroraFloat1{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(6%,8%) scale(1.08);}66%{transform:translate(-4%,5%) scale(0.95);}}
    @keyframes auroraFloat2{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(-8%,-6%) scale(1.06);}66%{transform:translate(5%,-3%) scale(0.97);}}
    @keyframes auroraFloat3{0%,100%{transform:translate(0,0) scale(1);}50%{transform:translate(-10%,8%) scale(1.1);}}
    @keyframes auroraFloat4{0%,100%{transform:translate(0,0) scale(1);}50%{transform:translate(8%,-10%) scale(1.05);}}
    </style>
    <header>
        <div class="logo-area"  style="cursor:pointer;">
            <div class="logo-flipper">
                <div class="logo-front">
                    <img src="toplogo/logo01.webp" alt="Logo">
                </div>
                <div class="logo-back">
                    <img loading="lazy" src="toplogo/logo02.webp" alt="Logo Alt">
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
            <div class="legal-date">Effective Date: <?= date("F d, Y") ?></div>

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
        <div class="footer-links"><a href="about.php">ABOUT</a><a href="contact.php">CONTACT</a><a href="faq.php">FAQ</a><a href="privacy.php">PRIVACY POLICY</a><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
    </footer>
</body>
</html>
