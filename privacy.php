<?php
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — Arigato Devan PromptVerse</title>
    <meta name="description" content="Privacy Policy for Arigato Devan PromptVerse — how we collect, use and protect your data.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css?v=2026051301">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
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
    <?php include_once 'gtag.php'; ?>
</head>
<body>

<div class="filmstrip-bg" aria-hidden="true" style="opacity:0.08;">
    <?php for($i=1;$i<=4;$i++): ?>
    <div class="bg-layer active" style="background-image:url('landingpics/lan<?=$i?>.webp');"></div>
    <?php endfor; ?>
</div>
<div class="bg-creamy-overlay" aria-hidden="true"></div>

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
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
        <?php else: ?>
            <a href="login.php" class="comic-btn" style="font-size:.85rem;padding:9px 18px;text-decoration:none;">LOGIN</a>
        <?php endif; ?>
    </div>
</header>

<div class="legal-wrap">
    <div class="legal-card">
        <div class="legal-title">🔒 Privacy Policy</div>
        <div class="legal-date">Effective Date: May 13, 2025 &nbsp;·&nbsp; arigatodevan.com</div>

        <p>Welcome to <strong>Arigato Devan PromptVerse</strong>. Your privacy matters to us. This policy explains what information we collect, how we use it, and how we keep it safe.</p>

        <h2>1. Information We Collect</h2>
        <p>When you sign in with Google, we receive and store:</p>
        <ul>
            <li>Your <strong>name</strong> and <strong>email address</strong></li>
            <li>Your <strong>Google profile picture</strong></li>
            <li>A unique <strong>Google ID</strong> (to identify your account)</li>
        </ul>
        <p>We also store activity data such as:</p>
        <ul>
            <li>Prompts you have <strong>unlocked or saved</strong></li>
            <li>Prompts and blogs you have <strong>liked</strong></li>
            <li>Your <strong>daily visit streak</strong></li>
            <li>Your chosen <strong>avatar, username, and gender</strong> (set during onboarding)</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <ul>
            <li>To <strong>authenticate you</strong> securely via Google Sign-In</li>
            <li>To <strong>save your unlocked prompts</strong> so you don't lose them</li>
            <li>To <strong>track your streak</strong> and personalise your experience</li>
            <li>To display your <strong>profile</strong> across the site</li>
        </ul>
        <p>We do <strong>not</strong> sell, rent, or share your personal data with any third party.</p>

        <h2>3. Google Sign-In & Firebase</h2>
        <p>We use <strong>Firebase Authentication</strong> (by Google) for login. We only request your basic profile information. We do not access your Google Drive, Gmail, or any other Google service.</p>

        <h2>4. Cookies & Sessions</h2>
        <p>We use <strong>PHP sessions</strong> to keep you logged in during your visit. No third-party tracking cookies are used on this site.</p>

        <h2>5. Data Storage</h2>
        <p>Your data is stored securely on our server hosted on <strong>Hostinger</strong>. We take reasonable steps to protect it from unauthorised access.</p>

        <h2>6. Your Rights</h2>
        <p>You can request deletion of your account and all associated data at any time by contacting us at:</p>
        <p><a href="mailto:arigato.devan@gmail.com">arigato.devan@gmail.com</a></p>

        <h2>7. Children's Privacy</h2>
        <p>This site is intended for users aged <strong>13 and above</strong>. We do not knowingly collect data from children under 13.</p>

        <h2>8. Changes to This Policy</h2>
        <p>We may update this policy occasionally. Any major changes will be announced on our Instagram page <a href="https://instagram.com/arigato.devan" target="_blank">@arigato.devan</a>.</p>

        <h2>9. Contact</h2>
        <p>Questions? Reach us on Instagram: <a href="https://instagram.com/arigato.devan" target="_blank">@arigato.devan</a></p>
    </div>
</div>

<footer>
    <div>&copy; <?= date('Y') ?> ARIGATO DEVAN. KEEP CREATING.</div>
    <div class="footer-links">
        <a href="disclaimer.php">DISCLAIMER</a>
        <a href="terms.php">TERMS OF SERVICE</a>
        <a href="privacy.php">PRIVACY POLICY</a>
    </div>
</footer>

</body>
</html>
