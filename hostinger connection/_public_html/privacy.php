<?php
session_start();
require_once "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <meta name="theme-color" content="#c084fc">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy &ndash; Arigato Devan Prompts</title>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="Arigato Devan Prompts">
    <meta property="og:description" content="Discover the best AI prompts for Instagram Reels.">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/privacy.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="description" content="Privacy Policy for Arigato Devan PromptVerse — how we collect, use and protect your data.">
    <link rel="canonical" href="https://arigatodevan.com/privacy.php">
    <link rel="stylesheet" href="style.min.css?v=20260601">
    <style>
        .legal-wrap { max-width: 800px; margin: 0 auto; padding: 60px 24px 100px; position: relative; z-index: 2; }
        .legal-card { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 24px; padding: 48px; box-shadow: var(--shadow-comic); }
        .legal-title { font-size: 2rem; font-weight: 900; margin-bottom: 8px; }
        .legal-date { font-size: .85rem; color: #888; font-weight: 600; margin-bottom: 32px; }
        .legal-card h2 { font-size: 1.15rem; font-weight: 900; margin: 28px 0 10px; color: var(--primary-dark); }
        .legal-card p, .legal-card li { font-size: .95rem; line-height: 1.8; color: #555; font-weight: 500; }
        .legal-card ul { padding-left: 20px; margin: 8px 0; }
        .legal-card a { color: var(--primary-dark); font-weight: 700; }
        @media (max-width: 600px) { .legal-card { padding: 28px 20px; } .legal-title { font-size: 1.5rem; } }
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
    <div class="logo-area" style="cursor:pointer;" onclick="window.location='index.php'">
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="index.php">HOME</a>
        <a href="blogs.php">BLOG</a>
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

<div class="legal-wrap">
    <div class="legal-card">
        <div class="legal-title"><i class="fa-solid fa-lock" style="color:var(--primary-dark);"></i> Privacy Policy</div>
        <div class="legal-date">Effective Date: May 24, 2026 &nbsp;&middot;&nbsp; arigatodevan.com</div>

        <p>Welcome to <strong>Arigato Devan PromptVerse</strong> ("we", "us", or "our"). We are committed to protecting your personal information and your right to privacy. This policy explains what information we collect, how we use it, and your rights regarding it.</p>

        <h2>1. Information We Collect</h2>
        <p>We collect information only when you sign in using Google OAuth. We receive and store:</p>
        <ul>
            <li>Your <strong>name</strong> and <strong>email address</strong> (from Google account)</li>
            <li>A unique <strong>Google ID</strong> to identify your account</li>
        </ul>
        <p>We also store activity data based on your interactions:</p>
        <ul>
            <li>Prompts you have <strong>unlocked or saved</strong></li>
            <li>Prompts and blog posts you have <strong>liked</strong></li>
            <li>Your <strong>daily visit streak</strong> and last active timestamp</li>
            <li>Your chosen <strong>avatar, username, and gender</strong> (set during onboarding)</li>
            <li>Aggregate <strong>prompt view and copy counts</strong> (not linked to individual users)</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <ul>
            <li>To <strong>authenticate you</strong> securely via Google Sign-In</li>
            <li>To <strong>save your unlocked prompts</strong> and personalised settings</li>
            <li>To <strong>track your streak</strong> and display your profile across the site</li>
            <li>To improve site content and user experience through aggregate analytics</li>
        </ul>
        <p>We do <strong>not</strong> sell, rent, or share your personal data with any third party for commercial purposes.</p>

        <h2>3. Google Sign-In & Firebase Authentication</h2>
        <p>We use <strong>Firebase Authentication</strong> (by Google LLC) for secure login via Google OAuth 2.0. We request only your basic profile — name and email. We do <strong>not</strong> access your Google Drive, Gmail, contacts, or any other Google services.</p>
        <p>Google's privacy policy: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a></p>

        <h2>4. Google Analytics</h2>
        <p>We use <strong>Google Analytics 4 (GA4)</strong> to understand how visitors interact with our website. GA4 collects anonymous data including:</p>
        <ul>
            <li>Pages visited and time spent on pages</li>
            <li>General geographic location (country/city)</li>
            <li>Device type (mobile, desktop, tablet)</li>
            <li>Traffic sources (how you found our site)</li>
        </ul>
        <p>This data is collected via cookies and is processed by Google LLC. You can opt out of Google Analytics by installing the <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener">Google Analytics Opt-out Browser Add-on</a>.</p>

        <h2>5. Google AdSense & Advertising</h2>
        <p>We may use <strong>Google AdSense</strong> to display advertisements on our site. Google AdSense uses cookies to serve ads based on your prior visits to this and other websites. Google's use of advertising cookies enables it and its partners to serve ads based on your visit to our site and/or other sites on the Internet.</p>
        <p>You may opt out of personalised advertising by visiting <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener">Google Ad Settings</a> or <a href="https://www.aboutads.info/choices/" target="_blank" rel="noopener">aboutads.info</a>.</p>

        <h2>6. Cloudflare</h2>
        <p>Our website is protected and delivered via <strong>Cloudflare</strong> CDN. Cloudflare may collect certain technical data (IP addresses, browser headers) for security, performance, and DDoS protection purposes. Their privacy policy: <a href="https://www.cloudflare.com/privacypolicy/" target="_blank" rel="noopener">cloudflare.com/privacypolicy</a></p>

        <h2>7. Cookies & Sessions</h2>
        <p>We use <strong>PHP sessions</strong> to keep you logged in during your visit. We also use first-party and third-party cookies for analytics (Google Analytics) and advertising (Google AdSense). You can control cookies through your browser settings. Note that disabling cookies may affect site functionality.</p>

        <h2>8. Data Storage & Security</h2>
        <p>Your data is stored on servers hosted by <strong>Hostinger</strong> (hostinger.com). We use HTTPS encryption, Cloudflare protection, and implement reasonable security measures to protect against unauthorised access, alteration, or disclosure of your data.</p>

        <h2>9. Data Retention</h2>
        <p>We retain your account data as long as your account is active. You may request account deletion at any time, after which all personal data will be permanently removed within 30 days.</p>

        <h2>10. Your Rights</h2>
        <p>You have the right to:</p>
        <ul>
            <li><strong>Access</strong> the personal data we hold about you</li>
            <li><strong>Correct</strong> inaccurate data</li>
            <li><strong>Delete</strong> your account and all associated data</li>
            <li><strong>Withdraw consent</strong> at any time by deleting your account</li>
        </ul>
        <p>To exercise these rights, contact us at: <a href="mailto:devansh.grow@gmail.com">devansh.grow@gmail.com</a></p>

        <h2>11. Children's Privacy</h2>
        <p>This site is intended for users aged <strong>13 and above</strong>. We do not knowingly collect personal data from children under 13. If you believe a child under 13 has provided personal information, please contact us immediately.</p>

        <h2>12. Third-Party Links</h2>
        <p>Our site may contain links to third-party websites (e.g., Instagram, AI platforms). We are not responsible for the privacy practices of those sites and encourage you to review their privacy policies.</p>

        <h2>13. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. The "Effective Date" at the top will reflect the latest revision. Continued use of the site after changes constitutes your acceptance of the updated policy.</p>

        <h2>14. Contact Us</h2>
        <p>For privacy-related questions or requests, contact us via our <a href="contact.php">Contact page</a> or email us directly at: <a href="mailto:devansh.grow@gmail.com">devansh.grow@gmail.com</a></p>
        <p>Instagram: <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener">@arigato.devan</a></p>
    </div>
</div>

<footer>
    <div>&copy; <?= date("Y") ?> ARIGATO DEVAN. KEEP CREATING.</div>
    <div class="footer-links"><a href="about.php">ABOUT</a><a href="contact.php">CONTACT</a><a href="faq.php">FAQ</a><a href="privacy.php">PRIVACY POLICY</a><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
</footer>

</body>
</html>

