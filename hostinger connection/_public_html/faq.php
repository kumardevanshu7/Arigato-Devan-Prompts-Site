<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Frequently asked questions about Arigato Devan Prompts — how to unlock secret codes, use AI tools, and get the best couple AI content for Instagram Reels.">
    <link rel="canonical" href="https://arigatodevan.com/faq.php">
    <meta name="theme-color" content="#c084fc">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
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
    <link rel="stylesheet" href="style.min.css?v=20260601">
    <style>
        /* ── Clean FAQ Layout - Complete Override ── */
        body { background: #fdfcfd !important; color: #101828 !important; }
        body::before { display: none !important; background: none !important; } /* Kill global background */
        
        .faq-page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 32px 140px;
            display: flex;
            gap: 80px;
            align-items: flex-start;
        }

        /* ── Sidebar ── */
        .faq-sidebar {
            width: 300px;
            flex-shrink: 0;
            position: sticky;
            top: 120px;
        }
        .faq-sidebar-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #101828;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
            font-family: var(--font-main, 'Inter', sans-serif);
        }
        .faq-sidebar-desc {
            font-size: 1rem;
            color: #475467;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        .faq-sidebar-links {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 32px;
        }
        .faq-sidebar-link {
            text-decoration: none;
            color: #475467;
            font-size: 1rem;
            font-weight: 600;
            padding: 12px 16px;
            border-radius: 8px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .faq-sidebar-link:hover {
            background: #f9fafb;
            color: #101828;
        }
        .faq-sidebar-link.active {
            background: #f9fafb;
            color: #101828;
            font-weight: 700;
        }
        .faq-sidebar-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .faq-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: .95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: 1px solid transparent;
            box-shadow: 0 1px 2px rgba(16,24,40,0.05);
        }
        .faq-btn-primary { background: #1570ef; color: #fff; border-color: #1570ef; }
        .faq-btn-primary:hover { background: #175cd3; border-color: #175cd3; }
        .faq-btn-secondary { background: #fff; color: #344054; border-color: #d0d5dd; }
        .faq-btn-secondary:hover { background: #f9fafb; color: #101828; }

        /* Lang toggle in sidebar */
        .lang-toggle-clean {
            display: flex;
            background: #f2f4f7;
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 32px;
        }
        .lang-btn-clean {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: .9rem;
            font-weight: 600;
            color: #667085;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .lang-btn-clean.active {
            background: #fff;
            color: #344054;
            box-shadow: 0 1px 3px rgba(16,24,40,0.1), 0 1px 2px rgba(16,24,40,0.06);
        }

        /* ── Main Content ── */
        .faq-content {
            flex: 1;
            min-width: 0;
            padding-top: 10px;
        }
        .faq-header {
            margin-bottom: 64px;
        }
        .faq-header h1 {
            font-size: clamp(2.5rem, 5vw, 3.8rem);
            font-weight: 800;
            color: #101828;
            margin-bottom: 24px;
            letter-spacing: -0.03em;
            line-height: 1.1;
        }
        .faq-header p {
            font-size: 1.25rem;
            color: #475467;
            line-height: 1.6;
            max-width: 700px;
        }

        /* ── Category heading ── */
        .faq-cat-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #101828;
            margin: 64px 0 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eaecf0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .faq-cat-title i { color: #98a2b3; font-size: 1.2rem; }

        /* ── FAQ Item — Accordion ── */
        .faq-item {
            background: #fdfcfd;
            border-bottom: 1px solid #eaecf0;
            margin-bottom: 0;
            overflow: hidden;
            transition: all .3s ease;
        }
        .faq-item:last-child {
            border-bottom: none;
        }
        .faq-q {
            width: 100%;
            background: none;
            border: none;
            text-align: left;
            padding: 24px 0;
            font-family: inherit;
            font-weight: 600;
            font-size: 1.1rem;
            color: #101828;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            line-height: 1.5;
        }
        .faq-item.open {
            background: #f9fafb;
            border-radius: 12px;
            border-bottom: none;
            padding: 0 24px;
            margin: 16px -24px;
            box-shadow: 0 1px 3px rgba(16,24,40,0.02);
        }
        .faq-item.open .faq-q {
            padding: 24px 0 12px;
        }
        .faq-arrow {
            color: #98a2b3;
            transition: transform .3s ease;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .faq-item.open .faq-arrow {
            transform: rotate(180deg);
            color: #1570ef;
        }
        .faq-a { max-height: 0; overflow: hidden; transition: max-height .35s ease; }
        .faq-item.open .faq-a { max-height: 600px; }
        .faq-a-inner {
            padding: 0 0 24px;
            font-size: 1.05rem;
            color: #475467;
            line-height: 1.6;
        }
        .faq-a-inner strong { color: #344054; font-weight: 600; }
        .faq-a-inner a { color: #1570ef; text-decoration: none; font-weight: 600; }
        .faq-a-inner a:hover { text-decoration: underline; }

        @media (max-width: 992px) {
            .faq-page-container { flex-direction: column; padding: 48px 24px 80px; gap: 48px; }
            .faq-sidebar { width: 100%; position: static; }
            .faq-sidebar-actions { flex-direction: row; }
            .faq-btn { flex: 1; }
            .faq-header h1 { font-size: 2.5rem; }
            .faq-item.open { margin: 16px 0; padding: 0 16px; }
        }
        @media (max-width: 600px) {
            .faq-header h1 { font-size: 2rem; }
            .faq-header p { font-size: 1.1rem; }
            .faq-q { font-size: 1.05rem; padding: 20px 0; }
            .faq-a-inner { font-size: 1rem; }
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
                        <img loading="lazy" src="<?= htmlspecialchars($_SESSION["profile_image"]) ?>" referrerpolicy="no-referrer" style="width:36px;height:36px;border-radius:50%;border:2px solid var(--primary-color);">
                    <?php else: ?>
                        <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-color);border:2px solid var(--text-color);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;color:#fff;"><?= strtoupper(substr($_SESSION["username"] ?? "U", 0, 1)) ?></div>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-primary" style="font-size:.82rem;padding:8px 16px;">Sign In</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="faq-page-container">

        <!-- Sidebar -->
        <aside class="faq-sidebar">
            <div class="faq-sidebar-title">Frequently Asked Questions</div>
            <div class="faq-sidebar-desc">Quick answers to questions you may have about Arigato Devan Prompts. Can't find what you're looking for?</div>
            <div class="lang-toggle-clean">
                <button class="lang-btn-clean active" id="btn-en" onclick="setLang('en')">English</button>
                <button class="lang-btn-clean" id="btn-hi" onclick="setLang('hi')">Hinglish</button>
            </div>
            <div class="faq-sidebar-links">
                <a href="#secret-code" class="faq-sidebar-link active">Secret Code</a>
                <a href="#unreleased" class="faq-sidebar-link">Unreleased</a>
                <a href="#viral" class="faq-sidebar-link">Insta Viral</a>
                <a href="#uploaded" class="faq-sidebar-link">Already Uploaded</a>
                <a href="#general" class="faq-sidebar-link">General</a>
            </div>
            <div class="faq-sidebar-actions">
                <a href="how_to_use.php" class="faq-btn faq-btn-secondary">Documentation</a>
                <a href="contact.php" class="faq-btn faq-btn-primary">Get in touch</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="faq-content">
            <div class="faq-header">
                <h1>Frequently Asked Questions</h1>
                <p>We provide a secure, powerful platform to get the absolute best AI prompts directly from viral Instagram reels.</p>
            </div>

            <!-- SECRET CODE -->
            <div class="faq-cat-title" id="secret-code">Secret Code</div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFAQ(this)">
                    <span class="faq-qtext"
                        data-en="What is a Secret Code prompt?"
                        data-hi="Secret Code prompt kya hota hai?">What is a Secret Code prompt?</span>
                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                </button>
                <div class="faq-a"><div class="faq-a-inner"
                    data-en="A Secret Code is a <strong>6-letter code</strong> hidden inside an Instagram Reel. Drop a comment on the reel &rarr; the code arrives in your DMs automatically via Auto-DM &rarr; enter it on the site &rarr; exclusive prompt unlocked!"
                    data-hi="Har reel mein ek <strong>6-letter ka code</strong> chhipa hota hai. Reel pe comment karo &rarr; code aapke DM mein automatically aayega &rarr; use yahan site pe daalo &rarr; aur exclusive prompt unlock ho jayega!">A Secret Code is a <strong>6-letter code</strong> hidden inside an Instagram Reel. Drop a comment on the reel &rarr; the code arrives in your DMs automatically via Auto-DM &rarr; enter it on the site &rarr; exclusive prompt unlocked!</div></div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFAQ(this)">
                    <span class="faq-qtext"
                        data-en="How do I unlock a Secret Code prompt?"
                        data-hi="Isko unlock karne ke liye kya karna hoga?">How do I unlock a Secret Code prompt?</span>
                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                </button>
                <div class="faq-a"><div class="faq-a-inner"
                    data-en="<strong>Step 1:</strong> Go to the Instagram Reel &nbsp;&rarr;&nbsp; <strong>Step 2:</strong> Drop any comment &nbsp;&rarr;&nbsp; <strong>Step 3:</strong> Code arrives in DMs via Auto-DM &nbsp;&rarr;&nbsp; <strong>Step 4:</strong> Copy it &nbsp;&rarr;&nbsp; <strong>Step 5:</strong> Paste in the Secret Code box on this site &nbsp;&rarr;&nbsp; Prompt unlocked!"
                    data-hi="<strong>Step 1:</strong> Instagram Reel pe jao &nbsp;&rarr;&nbsp; <strong>Step 2:</strong> Koi bhi comment karo &nbsp;&rarr;&nbsp; <strong>Step 3:</strong> Code aapke DM mein aayega &nbsp;&rarr;&nbsp; <strong>Step 4:</strong> Copy karo &nbsp;&rarr;&nbsp; <strong>Step 5:</strong> Site pe Secret Code box mein paste karo &nbsp;&rarr;&nbsp; Prompt unlocked!"><strong>Step 1:</strong> Go to the Instagram Reel &nbsp;&rarr;&nbsp; <strong>Step 2:</strong> Drop any comment &nbsp;&rarr;&nbsp; <strong>Step 3:</strong> Code arrives in DMs via Auto-DM &nbsp;&rarr;&nbsp; <strong>Step 4:</strong> Copy it &nbsp;&rarr;&nbsp; <strong>Step 5:</strong> Paste in the Secret Code box on this site &nbsp;&rarr;&nbsp; Prompt unlocked!</div></div>
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
                    data-hi="Ye seedha <strong>Secret Code page pe hi</strong> dikhega — kahin aur dhoondne ki zaroorat nahi!">It appears <strong>right there on the Secret Code page</strong> — no need to navigate anywhere else!</div></div>
            </div>

            <!-- UNRELEASED -->
            <div class="faq-cat-title" id="unreleased">Unreleased</div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFAQ(this)">
                    <span class="faq-qtext"
                        data-en="What are Unreleased prompts?"
                        data-hi="Unreleased prompts kya hote hain?">What are Unreleased prompts?</span>
                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                </button>
                <div class="faq-a"><div class="faq-a-inner"
                    data-en="These are prompts that were <strong>created but never posted</strong> on Instagram — too experimental, too niche, or just didn't feel right for the feed. Instead of deleting them, they're shared here exclusively."
                    data-hi="Ye wo prompts hain jo <strong>banaaye gaye the par Instagram pe kabhi post nahi hue</strong>. Shayad feed ke liye theek nahi lage. Inhe delete karne ke bajaay, sirf site pe share kiya jata hai.">These are prompts that were <strong>created but never posted</strong> on Instagram — too experimental, too niche, or just didn't feel right for the feed. Instead of deleting them, they're shared here exclusively.</div></div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFAQ(this)">
                    <span class="faq-qtext"
                        data-en="How do I unlock Unreleased prompts?"
                        data-hi="Inhe unlock karne ka tarika kya hai?">How do I unlock Unreleased prompts?</span>
                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                </button>
                <div class="faq-a"><div class="faq-a-inner"
                    data-en="No code needed here! <br><strong>Without login:</strong> 90 heart taps to unlock. <br><strong>With Google login:</strong> Just 20 taps. Simple! <a href='login.php'>Login here →</a>"
                    data-hi="Isme koi code nahi chahiye!<br><strong>Bina login:</strong> 90 heart taps karne honge unlock karne ke liye.<br><strong>Google Login ke sath:</strong> Sirf 20 taps. Bahut aasan! <a href='login.php'>Yahan login karein →</a>">No code needed here! <br><strong>Without login:</strong> 90 heart taps to unlock. <br><strong>With Google login:</strong> Just 20 taps. Simple! <a href="login.php">Login here →</a></div></div>
            </div>

            <!-- INSTA VIRAL -->
            <div class="faq-cat-title" id="viral">Insta Viral</div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFAQ(this)">
                    <span class="faq-qtext"
                        data-en="What are Insta Viral prompts?"
                        data-hi="Insta Viral prompts kya hain?">What are Insta Viral prompts?</span>
                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                </button>
                <div class="faq-a"><div class="faq-a-inner"
                    data-en="These are prompts <strong>spotted going viral on Instagram</strong> — trending reels that everyone is using right now. Collected, curated, and brought here for you!"
                    data-hi="Ye wo prompts hain jo aaj kal <strong>Instagram pe viral</strong> ho rahe hain. Trending reels se collect karke yahan laaye gaye hain taaki aap bhi inhe use kar sakein!">These are prompts <strong>spotted going viral on Instagram</strong> — trending reels that everyone is using right now. Collected, curated, and brought here for you!</div></div>
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
                    data-hi="Bilkul! Ye <strong>asli viral reels ke hi prompts</strong> hain. Agar aapne koi AI couple reel dekhi hai jo bohot trend kar rahi hai, uska prompt aapko yahan zaroor mil jayega.">Absolutely! These are <strong>actual prompts from viral reels</strong> — real trending content. If you've seen a reel blowing up with AI couple content, the prompt behind it is probably right here.</div></div>
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
                    data-hi="<strong>Gemini (Google)</strong> sabse best hai — 90% prompts Gemini ke liye hi banaye gaye hain. Ye romantic aur hot visuals bina zyada restrictions ke banata hai. ChatGPT aisi images ko block kar deta hai."><strong>Gemini (Google)</strong> is the best — 90% of prompts are optimized for Gemini. It generates stunning, hot romantic visuals with far fewer restrictions. ChatGPT tends to block or water down couple content.</div></div>
            </div>

            <!-- ALREADY UPLOADED -->
            <div class="faq-cat-title" id="uploaded">Already Uploaded</div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFAQ(this)">
                    <span class="faq-qtext"
                        data-en="What is the Already Uploaded section?"
                        data-hi='"Already Uploaded" section kya hai?'>What is the Already Uploaded section?</span>
                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                </button>
                <div class="faq-a"><div class="faq-a-inner"
                    data-en="Before this site existed, prompts were shared via <strong>Notion and Instagram Reels</strong>. This section is the complete archive — every old prompt, all in one place. New prompts won't come here; they go straight to the main sections."
                    data-hi="Is site ke banne se pehle prompts sirf <strong>Notion aur Instagram Reels</strong> pe milte the. Ye section purane sabhi prompts ka archive hai — ek hi jagah. Naye prompts yahan nahi aayenge, wo seedha main sections mein jayenge.">Before this site existed, prompts were shared via <strong>Notion and Instagram Reels</strong>. This section is the complete archive — every old prompt, all in one place. New prompts won't come here; they go straight to the main sections.</div></div>
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
                    data-hi="Haan bilkul! Jitni baar chaho inhe use karo — koi limit nahi hai.">Yes, absolutely! Use them as many times as you want — no restrictions.</div></div>
            </div>

            <!-- GENERAL -->
            <div class="faq-cat-title" id="general">General</div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFAQ(this)">
                    <span class="faq-qtext"
                        data-en="Is this site completely free?"
                        data-hi="Kya ye site bilkul free hai?">Is this site completely free?</span>
                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                </button>
                <div class="faq-a"><div class="faq-a-inner"
                    data-en="<strong>Yes, 100% free for now!</strong> No subscriptions, no hidden charges. Enjoy unlimited access."
                    data-hi="<strong>Haan, abhi ke liye ye 100% free hai!</strong> Koi subscription ya hidden charges nahi hain. Unlimited use karein."><strong>Yes, 100% free for now!</strong> No subscriptions, no hidden charges. Enjoy unlimited access.</div></div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFAQ(this)">
                    <span class="faq-qtext"
                        data-en="How often are new prompts added?"
                        data-hi="Naye prompts kitne frequently aate hain?">How often are new prompts added?</span>
                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                </button>
                <div class="faq-a"><div class="faq-a-inner"
                    data-en="New prompts are added <strong>every 2–3 days</strong>. Follow <a href='https://www.instagram.com/arigato.devan/' target='_blank'>@arigato.devan</a> on Instagram to get notified first!"
                    data-hi="Har <strong>2–3 din</strong> mein naye prompts aate hain. Sabse pehle jaanne ke liye Instagram pe <a href='https://www.instagram.com/arigato.devan/' target='_blank'>@arigato.devan</a> follow karo!">New prompts are added <strong>every 2–3 days</strong>. Follow <a href="https://www.instagram.com/arigato.devan/" target="_blank">@arigato.devan</a> on Instagram to get notified first!</div></div>
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
                    data-hi="AI tools <strong>probability aur creativity</strong> se output generate karte hain — same prompt, alag result har baar. Ye actually ek <strong>feature hai, bug nahi!</strong> 2–3 baar try karo aur apna favourite result choose karo.">AI tools generate images using <strong>probability and creativity</strong> — even the same prompt produces a unique output every single time. This is a <strong>feature, not a bug!</strong> Try 2–3 times and pick your favourite result.</div></div>
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
                    data-hi="Nahi! Aap <strong>bina login kiye</strong> bhi prompts dhoondh aur unlock kar sakte hain. Par login karne se fayde hain: prompts ko save aur like karna, aur Unreleased prompts ko sirf <strong>20 taps</strong> mein unlock karna.">Not at all! You can browse and unlock prompts <strong>without logging in</strong>. But login unlocks extra benefits: save &amp; like prompts, and unlock Unreleased prompts with just <strong>20 taps</strong> instead of 90.</div></div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFAQ(this)">
                    <span class="faq-qtext"
                        data-en="What is a streak and how do I increase it?"
                        data-hi="Streak kya hoti hai aur kaise badhayein?">What is a streak and how do I increase it?</span>
                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                </button>
                <div class="faq-a"><div class="faq-a-inner"
                    data-en="Your <strong>streak increases by 1 every day you log in</strong>. Miss a single day and it resets to zero. Stay consistent &mdash; the longer the streak, the more satisfying it feels!"
                    data-hi="Har din login karne pe <strong>streak 1 badhti hai</strong>. Ek din miss kiya &mdash; reset! Consistent rehna &mdash; jitni lambi streak, utna zyada maza!">Your <strong>streak increases by 1 every day you log in</strong>. Miss a single day and it resets to zero. Stay consistent!</div></div>
            </div>

        </main>
    </div>

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
