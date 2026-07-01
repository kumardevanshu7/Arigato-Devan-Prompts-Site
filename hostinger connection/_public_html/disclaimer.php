<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#2F4156">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disclaimer &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="AI-generated content disclaimer for Arigato Devan PromptVerse. Understand the nature of AI prompts and content.">
    <link rel="canonical" href="https://arigatodevan.com/disclaimer.php">
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

            <div style="text-align:center; margin-top:32px;">
                <a href="index.php" class="cta-btn cta-btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
</main>

    <?php include 'footer.php'; ?>
</body>
</html>
