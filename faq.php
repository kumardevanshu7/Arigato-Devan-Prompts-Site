<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
$curPage = 'faq.php';
if (isset($_SESSION['user_id'])) {
    require_once "db.php";
    try {
        $stmt = $pdo->prepare("SELECT
            SUM(CASE WHEN prompt_type = 'secret' THEN 1 ELSE 0 END) as secret_code,
            SUM(CASE WHEN prompt_type = 'unreleased' THEN 1 ELSE 0 END) as unreleased,
            SUM(CASE WHEN prompt_type = 'insta_viral' THEN 1 ELSE 0 END) as insta_viral,
            SUM(CASE WHEN prompt_type = 'already_uploaded' THEN 1 ELSE 0 END) as already_uploaded,
            SUM(CASE WHEN prompt_type = 'direct' THEN 1 ELSE 0 END) as direct
        FROM prompts");
        $stmt->execute();
        $nav_counts = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $nav_counts = []; }
} else { $nav_counts = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#2F4156">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Frequently asked questions about Arigato Devan Prompts — unlocking, login, viral reels, and more.">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <?php include_once 'includes/theme_head.php'; ?>
    <link rel="stylesheet" href="css/info-pages.css?v=20260701">
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-store page-info theme-nogoda">

<?php $nav_active = ''; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

<main class="info-page-main info-page-main--wide">
        <div class="info-page-hero">
            <p class="hero-label">Help Center</p>
            <h1>Frequently Asked <em>Questions</em></h1>
            <p>Everything you need to know about unlocking prompts, logging in, and getting the best AI couple results.</p>
        </div>

        <div class="faq-grid">
            <!-- Left Column -->
            <div class="faq-column">
                <h2>Get to Know Arigato Devan Better</h2>

                <div class="faq-card">
                    <button class="faq-question">
                        Is this site completely free?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner"><strong>Yes, 100% free for now!</strong> No subscriptions, no hidden charges. Enjoy unlimited access.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        How often are new prompts added?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">New prompts are added <strong>every 2–3 days</strong>. Follow <a href='https://www.instagram.com/arigato.devan/' target='_blank'>@arigato.devan</a> on Instagram to get notified first!</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        Is my data safe?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">100% safe. Only your name and email are collected — nothing else. Everything is secured through Google's own services.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        What is a streak and how do I increase it?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">Your <strong>streak increases by 1 every day you log in</strong>. Miss a single day and it resets to zero. Stay consistent!</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        Is Google login required?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">Not at all! You can browse and unlock prompts <strong>without logging in</strong>. But login unlocks extra benefits: save &amp; like prompts, and unlock Unreleased prompts with just <strong>20 taps</strong> instead of 90.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        What prompt to give Gemini for couple photo?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">Specify realistic photography details such as: <em>"Cinematic 35mm portrait of a stylish young couple, natural smiling chemistry, golden hour backlighting, soft focal blur, authentic skin textures, 8k resolution"</em>. You can copy full tested versions directly from our site.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        How to create couple images with Gemini AI prompt generator?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">Use our prompt collections as your generator template: choose your favorite mood (romantic, aesthetic cafe, festive, or travel), copy the formula, customize outfit colors or background location, and run in Google Gemini for instant high-quality results.</div></div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="faq-column">
                <h2>Questions About Our Prompts</h2>

                <div class="faq-card">
                    <button class="faq-question">
                        What is a Secret Code prompt?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">A Secret Code is a <strong>6-letter code</strong> hidden inside an Instagram Reel. Drop a comment on the reel &rarr; the code arrives in your DMs automatically via Auto-DM &rarr; enter it on the site &rarr; exclusive prompt unlocked!</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        How do I unlock a Secret Code prompt?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner"><strong>Step 1:</strong> Go to the Instagram Reel &nbsp;&rarr;&nbsp; <strong>Step 2:</strong> Drop any comment &nbsp;&rarr;&nbsp; <strong>Step 3:</strong> Code arrives in DMs via Auto-DM &nbsp;&rarr;&nbsp; <strong>Step 4:</strong> Copy it &nbsp;&rarr;&nbsp; <strong>Step 5:</strong> Paste in the Secret Code box on this site &nbsp;&rarr;&nbsp; Prompt unlocked!</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        What are Unreleased prompts?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">These are prompts that were <strong>created but never posted</strong> on Instagram — too experimental, too niche, or just didn't feel right for the feed. Instead of deleting them, they're shared here exclusively.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        How do I unlock Unreleased prompts?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">No code needed here! <br><strong>Without login:</strong> 90 heart taps to unlock. <br><strong>With Google login:</strong> Just 20 taps. Simple! <a href='login.php'>Login here &rarr;</a></div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        Which AI tool should I use?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner"><strong>Gemini (Google)</strong> is the best — 90% of prompts are optimized for Gemini. It generates stunning, hot romantic visuals with far fewer restrictions. ChatGPT tends to block or water down couple content.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        What is the prompt for a couple photo in Gemini &amp; how to make one?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">A Gemini couple prompt is a detailed description specifying the couple's poses, outfits, facial expressions, and cinematic lighting (like golden-hour glow or soft studio rim light). To make one: <strong>1.</strong> Copy any couple prompt from our gallery. <strong>2.</strong> Open Google Gemini. <strong>3.</strong> Paste the prompt and tap generate for instant realistic couple portraits.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        How to create or edit a couple photo in Gemini prompt?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner"><strong>To create:</strong> Simply paste our prompt into Gemini with desired aesthetics (e.g. romantic cafe, sunset terrace, or festive Indian styles).<br><strong>To edit:</strong> Upload your reference couple photo in Gemini and prompt: <em>"Maintain the facial identity and natural pose of this couple, but enhance the lighting to 8K cinematic warm aesthetic."</em></div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        How to make a couple photo in ChatGPT prompt?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">When prompting ChatGPT (DALL-E 3) for couple photos, focus on descriptive atmospheric words like <em>"affectionate candid pose"</em>, <em>"soft golden ambient light"</em>, and detailed attire descriptions (e.g., <em>"modern Indian couple in elegant ethnic attire"</em>) to achieve clean, high-aesthetic outputs without policy triggers.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        What is the 👑 Premium Romantic category?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">Premium Prompts are our curated, high-end romantic couple concepts kept in <strong>Hidden Mode</strong>. They are available directly through our official Instagram profile (<a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener">@arigato.devan</a>) bio and broadcast channel Notion hub.</div></div>
                </div>
            </div>
        </div>
</main>

<?php include 'footer.php'; ?>

    <script>
    document.querySelectorAll('.faq-question').forEach(button => {
        button.addEventListener('click', () => {
            const card = button.closest('.faq-card');
            const answer = card.querySelector('.faq-answer');
            const isOpen = card.classList.contains('open');

            // Close all other open cards
            document.querySelectorAll('.faq-card.open').forEach(openCard => {
                openCard.classList.remove('open');
                openCard.querySelector('.faq-answer').style.maxHeight = null;
            });

            if (!isOpen) {
                card.classList.add('open');
                answer.style.maxHeight = answer.scrollHeight + 'px';
            }
        });
    });
    </script>
</body>
</html>
