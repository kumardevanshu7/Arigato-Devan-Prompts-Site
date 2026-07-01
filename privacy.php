<?php
session_start();
require_once "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#2F4156">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Privacy Policy for Arigato Devan PromptVerse — how we collect, use and protect your data.">
    <link rel="canonical" href="https://arigatodevan.com/privacy.php">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <?php include_once 'includes/theme_head.php'; ?>
    <link rel="stylesheet" href="css/info-pages.css?v=20260701">
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-store page-info theme-nogoda">

<?php $nav_active = ''; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

<main class="info-page-main">
    <div class="legal-card">
        <h1><i class="fa-solid fa-lock"></i> Privacy Policy</h1>
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
</main>

<?php include 'footer.php'; ?>

</body>
</html>

