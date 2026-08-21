<?php
require_once __DIR__ . '/../includes/session_bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/prompt_cards.php';

if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: ../onboarding.php");
    exit();
}

$page_title      = 'Romantic AI Prompts — Instagram Exclusive Guide | Arigato Devan';
$meta_desc       = 'Exclusive Romantic Couple AI Prompts. Ye prompts site par hidden hain aur exclusively hamare Instagram par hi available hain.';
$canonical_url   = 'https://arigatodevan.com/premium.php';
$nav_active      = 'premium';
$nav_base        = '../';
?>
<!DOCTYPE html>
<html lang="en" class="theme-nogoda">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2F4156">
    <base href="<?= ($_SERVER['HTTP_HOST'] === 'localhost') ? '/Arigato%20Development%20Site/' : '/' ?>">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta name="keywords" content="couple prompt for gemini ai, couple prompt, couple prompt gemini, trending couple prompt, gemini couple prompt, couple prompt for gemini, couple prompt chatgpt, romantic couple prompt, couple prompt chatgpt indian, chatgpt couple prompt, couple prompt generator, arigato devan">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta name="twitter:card" content="summary_large_image">
    
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://arigatodevan.com'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Premium Prompts', 'item' => $canonical_url],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>
    
    <?php include_once __DIR__ . '/../includes/theme_head.php'; ?>
    <link rel="stylesheet" href="premium/css/premium.css?v=20260821d">
    <?php include_once __DIR__ . '/../gtag.php'; ?>
</head>
<body class="page-store theme-nogoda page-category page-premium">

<?php
$nav_base = '';
include __DIR__ . '/../includes/site_nav.php';
?>

<main class="prm-page-wrap">
    <!-- ARTICLE HEADER -->
    <header class="prm-article-header">
        <div class="prm-tag-badge">
            <i class="fa-solid fa-crown"></i> Exclusive Romantic Collection
        </div>
        <h1 class="prm-main-title" id="prm-header-title">Premium Romantic AI Prompts</h1>
        <p class="prm-main-subtitle" id="prm-header-subtitle">
            Cinematic couple lighting, dreamy aesthetic portrait formulas, aur direct Instagram access guide.
        </p>

        <!-- LANGUAGE TOGGLE SWITCH (HINGLISH / ENGLISH) -->
        <div class="prm-lang-toggle-wrap">
            <div class="prm-lang-toggle" role="group" aria-label="Language selection">
                <button type="button" class="prm-lang-btn active" id="prm-btn-hi" onclick="setPrmLang('hi')">
                    <i class="fa-solid fa-language"></i> Hinglish
                </button>
                <button type="button" class="prm-lang-btn" id="prm-btn-en" onclick="setPrmLang('en')">
                    <i class="fa-solid fa-globe"></i> English
                </button>
            </div>
        </div>
    </header>

    <!-- TOP GLOWING INSTAGRAM CTA CONTAINER -->
    <section class="prm-top-cta">
        <div class="prm-hand-pointer">
            <span class="prm-hand-emoji-left" aria-hidden="true">👉</span>
            <span id="prm-cta-hand-text">Ye Prompts Sirf Instagram Par Available Hain</span>
            <span class="prm-hand-emoji-right" aria-hidden="true">👈</span>
        </div>
        <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener" class="prm-insta-btn" id="prm-main-cta-btn">
            <i class="fa-brands fa-instagram"></i>
            <span id="prm-btn-label">Visit Instagram Profile @arigato.devan</span>
            <i class="fa-solid fa-crown"></i>
        </a>
        <p class="prm-cta-note" id="prm-cta-subnote">
            Aapko ye prompts mere Instagram ke bio mein milenge, jahan par "Subscribe" button hai.
        </p>
    </section>

    <!-- ARTICLE MAIN CONTENT -->
    <article class="prm-article-body">
        
        <!-- HINGLISH CONTENT BLOCK -->
        <div id="prm-content-hi">
            <h2><i class="fa-solid fa-feather" style="color:#d97706;"></i> Ye Prompts Kya Hain Aur Kaise Work Karte Hain?</h2>
            <p>
                Ye hamare specially designed <strong>Romantic Couple AI Prompts</strong> ki exclusive collection hai. In prompts mein cinematic golden-hour lighting, aesthetic color grading, aur ultra-realistic portrait formulas use kiye gaye hain taaki aap apni AI photo generation mein natural couple intimacy aur rich photographic detailing pa sakein.
            </p>

            <div class="prm-info-callout">
                <p>
                    <i class="fa-solid fa-eye-slash" style="margin-right:6px;"></i>
                    <strong>Website Par Prompts Kyun Nahi Dikh Rahe?</strong><br>
                    Aapko iss page par prompt ka text nahi dikhega. Ye saare romantic prompts <strong>Hidden Mode</strong> mein hain aur <strong>sirf mere Instagram ke bio mein available hain (jahan par Subscribe button hai)</strong>. Isliye prompt lene ke liye direct Instagram par jayein!
                </p>
            </div>

            <!-- SIMPLE ARTICLE IMAGES -->
            <div class="prm-article-images">
                <img src="premium/img/placeholder1.webp" alt="Romantic Sunset Couple AI Photo" class="prm-simple-img">
                <img src="premium/img/placeholder2.webp" alt="Royal Studio Couple AI Photo" class="prm-simple-img">
            </div>

            <h2><i class="fa-solid fa-circle-check" style="color:#d97706;"></i> In Prompts Ko Kaise Access Karein? (Simple Steps)</h2>
            <p>
                Prompt paana bilkul simple aur direct hai:
            </p>

            <div class="prm-steps-grid">
                <div class="prm-step-card">
                    <div class="prm-step-badge">1</div>
                    <div class="prm-step-content">
                        <strong>Instagram Profile Par Jayein:</strong> Hamari official Instagram profile <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener" style="color:#d97706;font-weight:700;text-decoration:underline;">@arigato.devan</a> par visit karein.
                    </div>
                </div>
                <div class="prm-step-card">
                    <div class="prm-step-badge">2</div>
                    <div class="prm-step-content">
                        <strong>Bio &amp; Subscribe Button Check Karein:</strong> Profile ke bio section par jayein jahan <strong>"Subscribe" button</strong> hai — wahan aapko ye exclusive prompts dikhenge.
                    </div>
                </div>
                <div class="prm-step-card">
                    <div class="prm-step-badge">3</div>
                    <div class="prm-step-content">
                        <strong>Direct Prompts Paayein:</strong> Wahan se direct exclusive prompts copy karein aur 1-click mein images generate karein!
                    </div>
                </div>
            </div>

            <p>
                Agar aapko kisi prompt mein customization karni ho ya specific look create karna ho, toh aap direct Instagram DM par bhi connect kar sakte hain. Happy Creating!
            </p>

            <!-- TRENDING KEYWORDS & PROMPTS GUIDE (HINGLISH) -->
            <h2><i class="fa-solid fa-fire" style="color:#d97706;"></i> Trending Couple Prompts &amp; AI Generator Guide</h2>
            <p>
                Chahe aap Google Gemini ke liye <strong>couple prompt for gemini ai</strong>, <strong>gemini couple prompt</strong>, ya <strong>trending couple prompt</strong> dhoondh rahe hon — ya fir ChatGPT ke liye <strong>couple prompt chatgpt</strong>, <strong>romantic couple prompt</strong>, aur <strong>couple prompt chatgpt indian</strong> explore kar rahe hon — hamare prompts har platform ke liye optimized hain. In formulas ko ek natural <strong>couple prompt generator</strong> ki tarah test kiya gaya hai taaki high aesthetic couple photos effortlessly ban sakein.
            </p>

            <!-- KEYWORDS TAGS CLOUD -->
            <div class="prm-keywords-section">
                <div class="prm-keywords-title">
                    <i class="fa-solid fa-magnifying-glass"></i> Popular Searches &amp; Trending Prompts
                </div>
                <div class="prm-tags-cloud">
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt for gemini ai</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt gemini</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> trending couple prompt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> gemini couple prompt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt for gemini</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt chatgpt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> romantic couple prompt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt chatgpt indian</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> chatgpt couple prompt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt generator</span>
                </div>
            </div>
        </div>

        <!-- ENGLISH CONTENT BLOCK -->
        <div id="prm-content-en" style="display: none;">
            <h2><i class="fa-solid fa-feather" style="color:#d97706;"></i> About This Exclusive Romantic Collection</h2>
            <p>
                This is a curated collection of handcrafted <strong>Romantic Couple AI Prompts</strong>. Designed specifically for creators seeking cinematic lighting, dreamy golden-hour palettes, natural intimacy, and ultra-realistic photographic depth.
            </p>

            <div class="prm-info-callout">
                <p>
                    <i class="fa-solid fa-eye-slash" style="margin-right:6px;"></i>
                    <strong>Why aren't prompts displayed on this page?</strong><br>
                    You will not see prompt text on this webpage. These special prompts are kept in <strong>Hidden Mode</strong> and are <strong>available exclusively in our Instagram bio (where the Subscribe button is located)</strong>. Please visit our Instagram profile to access them!
                </p>
            </div>

            <!-- SIMPLE ARTICLE IMAGES -->
            <div class="prm-article-images">
                <img src="premium/img/placeholder1.webp" alt="Romantic Sunset Couple AI Photo" class="prm-simple-img">
                <img src="premium/img/placeholder2.webp" alt="Royal Studio Couple AI Photo" class="prm-simple-img">
            </div>

            <h2><i class="fa-solid fa-circle-check" style="color:#d97706;"></i> How to Get Direct Access (3 Simple Steps)</h2>
            <p>
                Getting these prompts is straightforward and direct:
            </p>

            <div class="prm-steps-grid">
                <div class="prm-step-card">
                    <div class="prm-step-badge">1</div>
                    <div class="prm-step-content">
                        <strong>Visit Instagram Profile:</strong> Head over to our official Instagram page <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener" style="color:#d97706;font-weight:700;text-decoration:underline;">@arigato.devan</a>.
                    </div>
                </div>
                <div class="prm-step-card">
                    <div class="prm-step-badge">2</div>
                    <div class="prm-step-content">
                        <strong>Check Bio &amp; Subscribe Button:</strong> Go to the profile bio where the <strong>"Subscribe" button</strong> is located — that's where you will find these exclusive prompts.
                    </div>
                </div>
                <div class="prm-step-card">
                    <div class="prm-step-badge">3</div>
                    <div class="prm-step-content">
                        <strong>Direct Access:</strong> Grab the prompts directly from there and generate your couple photos in 1 click!
                    </div>
                </div>
            </div>

            <p>
                Have questions or need help fine-tuning a prompt for your exact photo setup? Feel free to reach out via Instagram DM!
            </p>

            <!-- TRENDING KEYWORDS & PROMPTS GUIDE (ENGLISH) -->
            <h2><i class="fa-solid fa-fire" style="color:#d97706;"></i> Trending Couple Prompts &amp; AI Generator Guide</h2>
            <p>
                Whether you are searching for a <strong>couple prompt for gemini ai</strong>, <strong>gemini couple prompt</strong>, or <strong>trending couple prompt</strong> — or looking for <strong>couple prompt chatgpt</strong>, <strong>romantic couple prompt</strong>, and <strong>couple prompt chatgpt indian</strong> — our formulas are crafted to work seamlessly across major AI platforms. Engineered like an intuitive <strong>couple prompt generator</strong>, these prompts make high-end aesthetic visual creation effortless.
            </p>

            <!-- KEYWORDS TAGS CLOUD -->
            <div class="prm-keywords-section">
                <div class="prm-keywords-title">
                    <i class="fa-solid fa-magnifying-glass"></i> Popular Searches &amp; Trending Prompts
                </div>
                <div class="prm-tags-cloud">
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt for gemini ai</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt gemini</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> trending couple prompt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> gemini couple prompt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt for gemini</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt chatgpt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> romantic couple prompt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt chatgpt indian</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> chatgpt couple prompt</span>
                    <span class="prm-keyword-tag"><i class="fa-solid fa-sparkles"></i> couple prompt generator</span>
                </div>
            </div>
        </div>

        <div class="prm-article-footer">
            <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener" class="prm-insta-btn">
                <i class="fa-brands fa-instagram"></i>
                <span>Open Instagram @arigato.devan</span>
            </a>
        </div>

    </article>
</main>

<script>
function setPrmLang(lang) {
    const btnHi = document.getElementById('prm-btn-hi');
    const btnEn = document.getElementById('prm-btn-en');
    const contentHi = document.getElementById('prm-content-hi');
    const contentEn = document.getElementById('prm-content-en');
    const headerTitle = document.getElementById('prm-header-title');
    const headerSubtitle = document.getElementById('prm-header-subtitle');
    const ctaHandText = document.getElementById('prm-cta-hand-text');
    const btnLabel = document.getElementById('prm-btn-label');
    const ctaSubnote = document.getElementById('prm-cta-subnote');

    if (lang === 'en') {
        btnHi.classList.remove('active');
        btnEn.classList.add('active');
        contentHi.style.display = 'none';
        contentEn.style.display = 'block';

        if (headerTitle) headerTitle.textContent = 'Premium Romantic AI Prompts';
        if (headerSubtitle) headerSubtitle.textContent = 'Cinematic couple lighting, dreamy aesthetic portrait formulas, and direct Instagram access guide.';
        if (ctaHandText) ctaHandText.textContent = 'These Prompts Are Available Exclusively on Instagram';
        if (btnLabel) btnLabel.textContent = 'Visit Instagram Profile @arigato.devan';
        if (ctaSubnote) ctaSubnote.textContent = 'You will find these prompts in our Instagram bio right where the "Subscribe" button is located.';

        try { localStorage.setItem('prm_lang', 'en'); } catch(e){}
    } else {
        btnEn.classList.remove('active');
        btnHi.classList.add('active');
        contentEn.style.display = 'none';
        contentHi.style.display = 'block';

        if (headerTitle) headerTitle.textContent = 'Premium Romantic AI Prompts';
        if (headerSubtitle) headerSubtitle.textContent = 'Cinematic couple lighting, dreamy aesthetic portrait formulas, aur direct Instagram access guide.';
        if (ctaHandText) ctaHandText.textContent = 'Ye Prompts Sirf Instagram Par Available Hain';
        if (btnLabel) btnLabel.textContent = 'Visit Instagram Profile @arigato.devan';
        if (ctaSubnote) ctaSubnote.textContent = 'Aapko ye prompts mere Instagram ke bio mein milenge, jahan par "Subscribe" button hai.';

        try { localStorage.setItem('prm_lang', 'hi'); } catch(e){}
    }
}

// Restore user language preference
document.addEventListener('DOMContentLoaded', function() {
    try {
        const saved = localStorage.getItem('prm_lang');
        if (saved === 'en') {
            setPrmLang('en');
        }
    } catch(e){}
});
</script>

<?php if (file_exists(__DIR__ . '/../footer.php')) { include __DIR__ . '/../footer.php'; } ?>
</body>
</html>
