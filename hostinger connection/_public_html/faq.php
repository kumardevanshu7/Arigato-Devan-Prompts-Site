<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Frequently asked questions about Arigato Devan Prompts — how to unlock secret codes, use AI tools, and get the best couple AI content for Instagram Reels.">
    <link rel="canonical" href="https://arigatodevan.com/faq.php">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="FAQ — Arigato Devan Prompts">
    <meta property="og:description" content="Got questions? We have answers. Learn how Secret Code, Unreleased, Insta Viral and Already Uploaded prompts work on Arigato Devan.">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/faq.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FAQ — Arigato Devan Prompts">
    <meta name="twitter:description" content="Got questions? We have answers. Learn how to use Arigato Devan Prompts.">
    <meta name="twitter:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <!-- FAQ Schema Markup -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {"@type":"Question","name":"What is a Secret Code prompt?","acceptedAnswer":{"@type":"Answer","text":"A Secret Code is a 6-letter code hidden inside an Instagram Reel. Drop a comment on the reel and the code arrives in your DMs automatically via Auto-DM. Enter it on the site to unlock the exclusive prompt."}},
        {"@type":"Question","name":"How do I unlock a Secret Code prompt?","acceptedAnswer":{"@type":"Answer","text":"Go to the Instagram Reel, drop any comment, the code arrives in your DMs via Auto-DM, copy it, paste it in the Secret Code box on the site, and the prompt is unlocked!"}},
        {"@type":"Question","name":"Where does the prompt appear after unlocking?","acceptedAnswer":{"@type":"Answer","text":"It appears right there on the Secret Code page — no need to go anywhere else."}},
        {"@type":"Question","name":"What are Unreleased prompts?","acceptedAnswer":{"@type":"Answer","text":"These are prompts that were created but didn't make it to Instagram — too experimental or just didn't feel right. Instead of deleting them, they are shared here exclusively."}},
        {"@type":"Question","name":"How do I unlock Unreleased prompts?","acceptedAnswer":{"@type":"Answer","text":"No code needed. Without login — 90 heart taps to unlock. With Google login — just 20 taps."}},
        {"@type":"Question","name":"What are Insta Viral prompts?","acceptedAnswer":{"@type":"Answer","text":"These are prompts spotted going viral on Instagram — trending reels everyone is using, collected and curated here for you."}},
        {"@type":"Question","name":"Which AI tool should I use?","acceptedAnswer":{"@type":"Answer","text":"Gemini (Google) works best — 90% of prompts are optimized for Gemini. ChatGPT tends to add too many restrictions on romantic and couple content."}},
        {"@type":"Question","name":"What is the Already Uploaded section?","acceptedAnswer":{"@type":"Answer","text":"Before the site existed, prompts were shared via Notion and Instagram Reels. This section is that archive — all old prompts brought together here."}},
        {"@type":"Question","name":"Is this site free?","acceptedAnswer":{"@type":"Answer","text":"Yes, completely free for now. No subscriptions, no hidden charges."}},
        {"@type":"Question","name":"Is Google login required?","acceptedAnswer":{"@type":"Answer","text":"Not at all. You can browse and unlock prompts without logging in. Login benefits: save and like prompts, and unlock Unreleased prompts with just 20 taps instead of 90."}},
        {"@type":"Question","name":"Is my data safe?","acceptedAnswer":{"@type":"Answer","text":"100% safe. Only your name and email are collected — nothing else. Everything is secured through Google's own services."}},
        {"@type":"Question","name":"Why does the same prompt give different results?","acceptedAnswer":{"@type":"Answer","text":"AI tools generate results using probability and creativity — the same prompt produces a different output every time. Try 2-3 times to find your perfect shot."}},
        {"@type":"Question","name":"Can I use prompts for commercial projects?","acceptedAnswer":{"@type":"Answer","text":"Yes! Use them freely for Instagram Reels, YouTube Shorts, or any personal or commercial creative content."}}
      ]
    }
    </script>
    <link rel="stylesheet" href="style.css?v=2026052401">
    <style>
        .faq-wrap { max-width: 860px; margin: 0 auto; padding: 40px 24px 100px; }

        .faq-hero { text-align: center; margin-bottom: 36px; }
        .faq-hero h1 { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 900; color: var(--text-color); margin: 0 0 10px; }
        .faq-hero p { font-size: .95rem; font-weight: 600; color: var(--subtext-color, #7D7887); max-width: 500px; margin: 0 auto 20px; }

        /* Lang toggle */
        .lang-toggle { display: inline-flex; border: 2.5px solid var(--text-color); border-radius: 12px; overflow: hidden; box-shadow: 3px 3px 0 var(--text-color); }
        .lang-btn { padding: 8px 22px; font-family: var(--font-main, 'Outfit', sans-serif); font-weight: 800; font-size: .85rem; cursor: pointer; border: none; background: var(--card-bg, #fff); color: var(--text-color); transition: background .2s, color .2s; }
        .lang-btn.active { background: var(--primary-color, #c084fc); color: var(--text-color); }

        /* Category heading */
        .faq-cat { display: flex; align-items: center; gap: 10px; margin: 36px 0 14px; }
        .faq-cat-icon { width: 38px; height: 38px; border-radius: 10px; border: 2.5px solid var(--text-color); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
        .faq-cat h2 { font-size: 1rem; font-weight: 900; color: var(--text-color); margin: 0; text-transform: uppercase; letter-spacing: .06em; }

        /* FAQ item */
        .faq-item { background: var(--card-bg, #fff); border: 2.5px solid var(--text-color); border-radius: 16px; margin-bottom: 10px; box-shadow: 4px 4px 0 var(--text-color); overflow: hidden; transition: box-shadow .15s; }
        .faq-item:hover { box-shadow: 5px 5px 0 var(--text-color); }
        .faq-q { width: 100%; background: none; border: none; text-align: left; padding: 16px 20px; font-family: var(--font-main, 'Outfit', sans-serif); font-weight: 800; font-size: .93rem; color: var(--text-color); cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .faq-q .faq-arrow { flex-shrink: 0; font-size: .8rem; color: var(--primary-color, #c084fc); transition: transform .25s; }
        .faq-item.open .faq-arrow { transform: rotate(180deg); }
        .faq-a { max-height: 0; overflow: hidden; transition: max-height .35s ease, padding .25s; }
        .faq-item.open .faq-a { max-height: 300px; }
        .faq-a-inner { padding: 0 20px 16px; font-size: .88rem; font-weight: 600; color: var(--subtext-color, #555); line-height: 1.65; border-top: 1.5px dashed var(--primary-color, #c084fc); padding-top: 12px; margin-top: 0; }
        .faq-a-inner strong { color: var(--text-color); }

        /* Cat icon colors */
        .cat-secret  { background: #ffe3fb; color: #9b59b6; }
        .cat-unreleased { background: #fff3e0; color: #e67e22; }
        .cat-viral   { background: #fce4ec; color: #e91e63; }
        .cat-uploaded { background: #e8f5e9; color: #2e7d32; }
        .cat-general { background: #e8eaf6; color: #3f51b5; }

        @media (max-width: 600px) {
            .faq-q { font-size: .86rem; padding: 14px 16px; }
            .faq-a-inner { font-size: .83rem; padding: 0 16px 14px; padding-top: 10px; }
        }
    </style>
    <?php include_once "gtag.php"; ?>
</head>
<body>

    <?php
    $curPage = 'faq.php';
    if (isset($_SESSION['user_id'])) {
        require_once "db.php";
        try {
            $stmt = $pdo->prepare("SELECT
                (SELECT COUNT(*) FROM prompts WHERE prompt_type='secret') as secret_code,
                (SELECT COUNT(*) FROM prompts WHERE prompt_type='unreleased') as unreleased,
                (SELECT COUNT(*) FROM prompts WHERE prompt_type='insta_viral') as insta_viral,
                (SELECT COUNT(*) FROM prompts WHERE prompt_type='already_uploaded') as already_uploaded
            ");
            $stmt->execute();
            $nav_counts = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $nav_counts = []; }
    } else { $nav_counts = []; }
    ?>

    <header>
        <div class="logo-area" id="logo-container" style="cursor:pointer;" onclick="window.location='index.php'">
            <div class="logo-flipper">
                <div class="logo-front"><img src="toplogo/logo01.webp" alt="Arigato Devan Logo" id="profile-logo"></div>
                <div class="logo-back"><img src="toplogo/logo02.webp" alt="Logo Alt" loading="lazy"></div>
            </div>
            <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
        </div>
        <nav class="nav-links">
            <a href="index.php">HOME</a>
            <a href="gallery.php">GALLERY</a>
            <a href="blogs.php">BLOGS</a>
            <a href="progress.php" title="Our Journey" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-chart-line nav-progress-icon"></i></a>
            <a href="faq.php" title="FAQ" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-circle-question" style="font-size:1.2rem;"></i></a>
            <a href="faq.php" title="FAQ" class="active" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-circle-question" style="font-size:1.2rem;"></i></a>
            <div class="nav-dropdown">
                <button class="nav-dropdown-btn"><i class="fa-solid fa-film"></i> Reels Type <i class="fa-solid fa-chevron-down dd-arrow"></i></button>
                <div class="nav-dropdown-menu">
                    <a href="secret_code.php"><i class="fa-solid fa-lock"></i> Secret Code Reels <?= empty($nav_counts["secret_code"]) ? '<span class="dd-tag soon">SOON</span>' : "" ?></a>
                    <a href="unreleased.php"><i class="fa-solid fa-star"></i> Unreleased Reels <?= empty($nav_counts["unreleased"]) ? '<span class="dd-tag soon">SOON</span>' : "" ?></a>
                    <a href="insta_viral.php"><i class="fa-brands fa-instagram"></i> Insta Viral Reels <?= empty($nav_counts["insta_viral"]) ? '<span class="dd-tag soon">SOON</span>' : "" ?></a>
                    <a href="already_uploaded.php"><i class="fa-solid fa-clock-rotate-left"></i> Already Uploaded <?= empty($nav_counts["already_uploaded"]) ? '<span class="dd-tag soon">SOON</span>' : "" ?></a>
                </div>
            </div>
            <a href="https://www.instagram.com/arigato.devan/" target="_blank" style="display:flex;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;color:inherit;font-family:var(--font-main);">
                <i class="fa-brands fa-instagram" style="font-size:18px;"></i>
                <span style="font-weight:600;">@arigato.devan</span>
                <span class="pulse-dot"></span>
                <span style="font-weight:800;font-size:1.1rem;">15K+</span>
            </a>
        </nav>
        <div class="header-right">
            <div class="header-divider"></div>
            <?php if (isset($_SESSION["user_id"])): ?>
                <a href="profile.php" title="Profile" style="display:flex;align-items:center;">
                    <?php if (!empty($_SESSION["profile_image"])): ?>
                        <img loading="lazy" src="<?= htmlspecialchars($_SESSION["profile_image"]) ?>" style="width:36px;height:36px;border-radius:50%;border:2px solid var(--primary-color);" loading="lazy">
                    <?php else: ?>
                        <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-color);border:2px solid var(--text-color);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;color:#fff;"><?= strtoupper(substr($_SESSION["name"] ?? "U", 0, 1)) ?></div>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-primary" style="font-size:.82rem;padding:8px 16px;">Sign In</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="faq-wrap">

        <!-- Hero -->
        <div class="faq-hero">
            <h1>❓ Frequently Asked Questions</h1>
            <p>Everything you need to know about Arigato Devan Prompts — answered.</p>
            <div class="lang-toggle">
                <button class="lang-btn active" id="btn-en" onclick="setLang('en')">🇬🇧 English</button>
                <button class="lang-btn" id="btn-hi" onclick="setLang('hi')">🇮🇳 Hinglish</button>
            </div>
        </div>

        <!-- SECRET CODE -->
        <div class="faq-cat">
            <div class="faq-cat-icon cat-secret"><i class="fa-solid fa-lock"></i></div>
            <h2>Secret Code</h2>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="What is a Secret Code prompt?"
                    data-hi="Secret Code prompt kya hota hai?">What is a Secret Code prompt?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="A Secret Code is a <strong>6-letter code</strong> hidden inside an Instagram Reel. Drop a comment on the reel → the code arrives in your DMs automatically via Auto-DM → enter it on the site → exclusive prompt unlocked! 🎉"
                data-hi="Secret Code ek <strong>6-letter ka code</strong> hota hai jo Instagram Reel ke andar chhupa hota hai. Reel pe comment karo → code apne aap Auto-DM se DM mein aa jaata hai → site pe enter karo → exclusive prompt unlock! 🎉">
                A Secret Code is a <strong>6-letter code</strong> hidden inside an Instagram Reel. Drop a comment on the reel → the code arrives in your DMs automatically via Auto-DM → enter it on the site → exclusive prompt unlocked! 🎉
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="How do I unlock a Secret Code prompt?"
                    data-hi="Isko unlock karne ke liye kya karna hoga?">How do I unlock a Secret Code prompt?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="<strong>Step 1:</strong> Go to the Instagram Reel &nbsp;→&nbsp; <strong>Step 2:</strong> Drop any comment &nbsp;→&nbsp; <strong>Step 3:</strong> Code arrives in DMs via Auto-DM &nbsp;→&nbsp; <strong>Step 4:</strong> Copy it &nbsp;→&nbsp; <strong>Step 5:</strong> Paste in the Secret Code box on this site &nbsp;→&nbsp; Prompt unlocked! 🔓"
                data-hi="<strong>Step 1:</strong> Instagram Reel pe jaao &nbsp;→&nbsp; <strong>Step 2:</strong> Koi bhi comment karo &nbsp;→&nbsp; <strong>Step 3:</strong> Auto-DM se code DM mein aayega &nbsp;→&nbsp; <strong>Step 4:</strong> Copy karo &nbsp;→&nbsp; <strong>Step 5:</strong> Site ke Secret Code box mein paste karo &nbsp;→&nbsp; Prompt unlock! 🔓">
                <strong>Step 1:</strong> Go to the Instagram Reel &nbsp;→&nbsp; <strong>Step 2:</strong> Drop any comment &nbsp;→&nbsp; <strong>Step 3:</strong> Code arrives in DMs via Auto-DM &nbsp;→&nbsp; <strong>Step 4:</strong> Copy it &nbsp;→&nbsp; <strong>Step 5:</strong> Paste in the Secret Code box on this site &nbsp;→&nbsp; Prompt unlocked! 🔓
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="Where does the prompt appear after unlocking?"
                    data-hi="Unlock karne ke baad prompt kaahan milega?">Where does the prompt appear after unlocking?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="It appears <strong>right there on the Secret Code page</strong> — no need to navigate anywhere else!"
                data-hi="Wahi <strong>Secret Code page pe turant show ho jaata hai</strong> — kahin aur jaane ki zaroorat nahi!">
                It appears <strong>right there on the Secret Code page</strong> — no need to navigate anywhere else!
            </div></div>
        </div>

        <!-- UNRELEASED -->
        <div class="faq-cat">
            <div class="faq-cat-icon cat-unreleased"><i class="fa-solid fa-star"></i></div>
            <h2>Unreleased</h2>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="What are Unreleased prompts?"
                    data-hi="Unreleased prompts kya hote hain?">What are Unreleased prompts?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="These are prompts that were <strong>created but never posted</strong> on Instagram — too experimental, too niche, or just didn't feel right for the feed. Instead of deleting them, they're shared here exclusively."
                data-hi="Ye woh prompts hain jo <strong>banaye gaye lekin Instagram pe post nahi hue</strong> — thoda alag the, ya feed ke liye sahi nahi lage. Delete karne ki jagah yahan share kar diye gaye!">
                These are prompts that were <strong>created but never posted</strong> on Instagram — too experimental, too niche, or just didn't feel right for the feed. Instead of deleting them, they're shared here exclusively.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="How do I unlock Unreleased prompts?"
                    data-hi="Inhe unlock karne ka tarika kya hai?">How do I unlock Unreleased prompts?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="No code needed here! <br><strong>Without login:</strong> 90 heart taps to unlock. <br><strong>With Google login:</strong> Just 20 taps. Simple! <a href='login.php' style='color:var(--primary-color);font-weight:800;'>Login here →</a>"
                data-hi="Koi code nahi chahiye! <br><strong>Bina login:</strong> 90 heart taps lagenge. <br><strong>Google login ke saath:</strong> Sirf 20 taps. Bas! <a href='login.php' style='color:var(--primary-color);font-weight:800;'>Login karo →</a>">
                No code needed here! <br><strong>Without login:</strong> 90 heart taps to unlock. <br><strong>With Google login:</strong> Just 20 taps. Simple! <a href="login.php" style="color:var(--primary-color);font-weight:800;">Login here →</a>
            </div></div>
        </div>

        <!-- INSTA VIRAL -->
        <div class="faq-cat">
            <div class="faq-cat-icon cat-viral"><i class="fa-brands fa-instagram"></i></div>
            <h2>Insta Viral</h2>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="What are Insta Viral prompts?"
                    data-hi="Insta Viral prompts kya hain?">What are Insta Viral prompts?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="These are prompts <strong>spotted going viral on Instagram</strong> — trending reels that everyone is using right now. Collected, curated, and brought here for you!"
                data-hi="Ye woh prompts hain jo <strong>Instagram pe viral ho rahe hain</strong> — trending reels jo abhi sabh use kar rahe hain. Collect karke, curate karke, yahan laaye gaye!">
                These are prompts <strong>spotted going viral on Instagram</strong> — trending reels that everyone is using right now. Collected, curated, and brought here for you!
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="Are these actually viral on Instagram?"
                    data-hi="Ye sach mein Instagram pe viral hote hain?">Are these actually viral on Instagram?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="Absolutely! These are <strong>actual prompts from viral reels</strong> — real trending content. If you've seen a reel blowing up with AI couple content, the prompt behind it is probably right here."
                data-hi="Bilkul! Ye <strong>actual viral reels ke prompts</strong> hain — real trending content. Agar koi couple AI reel boom kar rahi thi, uska prompt yahan mil jaayega.">
                Absolutely! These are <strong>actual prompts from viral reels</strong> — real trending content. If you've seen a reel blowing up with AI couple content, the prompt behind it is probably right here.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="Which AI tool should I use for these prompts?"
                    data-hi="Inhe kis AI tool mein use karein?">Which AI tool should I use for these prompts?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="<strong>Gemini (Google)</strong> is the best — 90% of prompts are optimized for Gemini. It generates stunning, hot romantic visuals with far fewer restrictions. ChatGPT tends to block or water down couple content."
                data-hi="<strong>Gemini (Google)</strong> best hai — 90% prompts Gemini ke liye optimize hain. Zyada detailed aur hot romantic visuals banata hai, restrictions kaafi kam hain. ChatGPT couple content pe bahut restrictions lagata hai.">
                <strong>Gemini (Google)</strong> is the best — 90% of prompts are optimized for Gemini. It generates stunning romantic visuals with far fewer restrictions. ChatGPT tends to block or water down couple content.
            </div></div>
        </div>

        <!-- ALREADY UPLOADED -->
        <div class="faq-cat">
            <div class="faq-cat-icon cat-uploaded"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <h2>Already Uploaded</h2>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="What is the Already Uploaded section?"
                    data-hi='"Already Uploaded" section kya hai?'>What is the Already Uploaded section?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="Before this site existed, prompts were shared via <strong>Notion and Instagram Reels</strong>. This section is the complete archive — every old prompt, all in one place. New prompts won't come here; they go straight to the main sections."
                data-hi="Site aane se pehle prompts <strong>Notion aur Instagram Reels</strong> pe share hote the. Ye section usi ka poora archive hai — saare purane prompts ek hi jagah. Naye prompts yahan nahi aayenge; woh seedhe main sections mein jaate hain.">
                Before this site existed, prompts were shared via <strong>Notion and Instagram Reels</strong>. This section is the complete archive — every old prompt, all in one place. New prompts won't come here; they go straight to the main sections.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="Can I reuse Already Uploaded prompts?"
                    data-hi="Kya main ye prompts dobara use kar sakta hoon?">Can I reuse Already Uploaded prompts?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="Yes, absolutely! Use them as many times as you want — no restrictions."
                data-hi="Haan, bilkul! Jitni baar chahein use karo — koi restriction nahi.">
                Yes, absolutely! Use them as many times as you want — no restrictions.
            </div></div>
        </div>

        <!-- GENERAL -->
        <div class="faq-cat">
            <div class="faq-cat-icon cat-general"><i class="fa-solid fa-circle-info"></i></div>
            <h2>General</h2>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="Is this site completely free?"
                    data-hi="Kya ye site bilkul free hai?">Is this site completely free?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="<strong>Yes, 100% free for now!</strong> No subscriptions, no hidden charges. Enjoy unlimited access."
                data-hi="<strong>Haan, abhi ke liye 100% free hai!</strong> Koi subscription nahi, koi hidden charge nahi. Jee bhar ke use karo.">
                <strong>Yes, 100% free for now!</strong> No subscriptions, no hidden charges. Enjoy unlimited access.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="Is Google login required?"
                    data-hi="Google se login karna zaroori hai?">Is Google login required?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="Not at all! You can browse and unlock prompts <strong>without logging in</strong>. But login unlocks extra benefits: save &amp; like prompts, and unlock Unreleased prompts with just <strong>20 taps</strong> instead of 90."
                data-hi="Bilkul nahi! Bina login ke bhi prompts dekh aur unlock kar sakte ho. Lekin login ke fayde hain: prompts save aur like kar sakte ho, aur Unreleased prompts sirf <strong>20 taps</strong> mein unlock ho jaate hain (bina login 90 taps lagte hain).">
                Not at all! You can browse and unlock prompts <strong>without logging in</strong>. But login unlocks extra benefits: save &amp; like prompts, and unlock Unreleased prompts with just <strong>20 taps</strong> instead of 90.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="Is my data safe?"
                    data-hi="Mera data safe hai?">Is my data safe?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="<strong>100% safe.</strong> Only your name and email are collected — nothing else. Everything is secured through Google's own infrastructure."
                data-hi="<strong>100% safe.</strong> Sirf tumhara naam aur email liya jaata hai — aur kuch nahi. Sab kuch Google ki apni secure infrastructure pe hai.">
                <strong>100% safe.</strong> Only your name and email are collected — nothing else. Everything is secured through Google's own infrastructure.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="How often are new prompts added?"
                    data-hi="Naye prompts kitne frequently aate hain?">How often are new prompts added?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="New prompts are added <strong>every 2–3 days</strong>. Follow <a href='https://www.instagram.com/arigato.devan/' target='_blank' style='color:var(--primary-color);font-weight:800;'>@arigato.devan</a> on Instagram to get notified first!"
                data-hi="Har <strong>2–3 din</strong> mein naye prompts aate hain. Sabse pehle jaanne ke liye Instagram pe <a href='https://www.instagram.com/arigato.devan/' target='_blank' style='color:var(--primary-color);font-weight:800;'>@arigato.devan</a> follow karo!">
                New prompts are added <strong>every 2–3 days</strong>. Follow <a href="https://www.instagram.com/arigato.devan/" target="_blank" style="color:var(--primary-color);font-weight:800;">@arigato.devan</a> on Instagram to get notified first!
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="Why does the same prompt give different results each time?"
                    data-hi="Same prompt se result alag alag kyun aata hai?">Why does the same prompt give different results each time?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="AI tools generate images using <strong>probability and creativity</strong> — even the same prompt produces a unique output every single time. This is a <strong>feature, not a bug!</strong> Try 2–3 times and pick your favourite result."
                data-hi="AI tools <strong>probability aur creativity</strong> se output generate karte hain — same prompt, alag result har baar. Ye actually ek <strong>feature hai, bug nahi!</strong> 2–3 baar try karo aur apna favourite result choose karo.">
                AI tools generate images using <strong>probability and creativity</strong> — even the same prompt produces a unique output every single time. This is a <strong>feature, not a bug!</strong> Try 2–3 times and pick your favourite result.
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="Can I use these prompts for commercial projects?"
                    data-hi="Kya inhe commercial use ke liye use kar sakte hain?">Can I use these prompts for commercial projects?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="<strong>Yes!</strong> Use them freely for Instagram Reels, YouTube Shorts, or any personal or commercial creative content. Go create! 🚀"
                data-hi="<strong>Haan!</strong> Instagram Reels, YouTube Shorts ya kisi bhi personal/commercial creative content ke liye freely use kar sakte ho. Banao dhamaka! 🚀">
                <strong>Yes!</strong> Use them freely for Instagram Reels, YouTube Shorts, or any personal or commercial creative content. Go create! 🚀
            </div></div>
        </div>

        <div class="faq-item">
            <button class="faq-q" onclick="toggleFAQ(this)">
                <span class="faq-qtext"
                    data-en="What is a streak and how do I increase it?"
                    data-hi="Streak kya hoti hai aur kaise badhayein?">What is a streak and how do I increase it?</span>
                <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </button>
            <div class="faq-a"><div class="faq-a-inner"
                data-en="Your <strong>streak increases by 1 every day you log in</strong>. Miss a single day and it resets to zero. Stay consistent — the longer the streak, the more satisfying it feels! 🔥"
                data-hi="Har din login karne pe <strong>streak 1 badhti hai</strong>. Ek din miss kiya — reset! Consistent rehna — jitni lambi streak, utna zyada maza! 🔥">
                Your <strong>streak increases by 1 every day you log in</strong>. Miss a single day and it resets to zero. Stay consistent! 🔥
            </div></div>
        </div>

    </main>

    <footer>
        <div>&copy; <?= date("Y") ?> ARIGATO DEVAN. KEEP CREATING.</div>
        <div class="footer-links">
            <a href="about.php">ABOUT</a>
            <a href="contact.php">CONTACT</a>
            <a href="faq.php">FAQ</a>
            <a href="privacy.php">PRIVACY POLICY</a>
            <a href="disclaimer.php">DISCLAIMER</a>
            <a href="terms.php">TERMS OF SERVICE</a>
        </div>
    </footer>

    <script>
    function toggleFAQ(btn) {
        var item = btn.closest('.faq-item');
        var isOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item.open').forEach(function(el){ el.classList.remove('open'); });
        if (!isOpen) item.classList.add('open');
    }

    var currentLang = 'en';
    function setLang(lang) {
        currentLang = lang;
        document.getElementById('btn-en').classList.toggle('active', lang === 'en');
        document.getElementById('btn-hi').classList.toggle('active', lang === 'hi');
        document.querySelectorAll('.faq-qtext').forEach(function(el){
            el.innerHTML = el.dataset[lang] || el.dataset.en;
        });
        document.querySelectorAll('.faq-a-inner').forEach(function(el){
            el.innerHTML = el.dataset[lang] || el.dataset.en;
        });
    }
    </script>

</body>
</html>
