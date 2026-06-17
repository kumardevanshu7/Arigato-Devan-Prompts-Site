<?php
session_start();
require_once "db.php";
$_page_canonical = 'https://arigatodevan.com/';
// Guard: if logged in but onboarding not done, force setup
if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

// Fetch approved testimonials for slider
$testimonials = [];
try {
    $tStmt = $pdo->query("
        SELECT f.feedback_text, f.rating, u.username, u.avatar, u.profile_image, u.gender
        FROM feedbacks f
        LEFT JOIN users u ON f.user_id = u.id
        WHERE f.show_on_homepage = 1
        ORDER BY f.submitted_at DESC
    ");
    $testimonials = $tStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $testimonials = []; }

// Fetch ONLY secret prompts for Home page
if (isset($_SESSION["user_id"])) {
    $stmt = $pdo->prepare("
        SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
               IF(l.id IS NOT NULL, 1, 0) as is_liked,
               IF(sv.id IS NOT NULL, 1, 0) as is_saved
        FROM prompts p
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
        LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
        LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
        WHERE p.prompt_type = 'secret' AND (p.is_trial = 0 OR p.is_trial IS NULL)
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]]);
    $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query(
        "SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE prompt_type = 'secret' AND (is_trial = 0 OR is_trial IS NULL) ORDER BY created_at DESC",
    );
    $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Featured Prompt of the Day
$featuredPrompt = null;
$featuredIsCustom = false;
try {
    // 1. Check for active custom POTD first
    $customPotd = $pdo->query("SELECT * FROM potd_custom WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($customPotd) {
        // Map custom POTD fields to match the prompts table structure for rendering
        $featuredPrompt = [
            'id' => 0,
            'title' => $customPotd['title'],
            'prompt_text' => $customPotd['prompt_text'],
            'image_path' => $customPotd['image_url'] ?: 'https://placehold.co/400x400/7c3aed/fff?text=POTD',
            'prompt_type' => 'secret',
            'likes_count' => 0,
            'reel_link' => '',
            'is_unlocked' => 1,
            'is_liked' => 0,
            'is_saved' => 0,
        ];
        $featuredIsCustom = true;
    }

    // 2. If no custom POTD, check existing prompts with is_featured=1
    if (!$featuredPrompt) {
        if (isset($_SESSION["user_id"])) {
            $fStmt = $pdo->prepare("
                SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
                       IF(l.id IS NOT NULL, 1, 0) as is_liked,
                       IF(sv.id IS NOT NULL, 1, 0) as is_saved
                FROM prompts p
                LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
                LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
                LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
                WHERE p.is_featured = 1 AND (p.is_trial = 0 OR p.is_trial IS NULL)
                LIMIT 1
            ");
            $fStmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]]);
        } else {
            $fStmt = $pdo->query(
                "SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE is_featured = 1 AND (is_trial = 0 OR is_trial IS NULL) LIMIT 1",
            );
        }
        $featuredPrompt = $fStmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Fallback: most liked prompt
    if (!$featuredPrompt) {
        if (isset($_SESSION["user_id"])) {
            $fStmt = $pdo->prepare("
                SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
                       IF(l.id IS NOT NULL, 1, 0) as is_liked,
                       IF(sv.id IS NOT NULL, 1, 0) as is_saved
                FROM prompts p
                LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
                LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
                LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
                WHERE (p.is_trial = 0 OR p.is_trial IS NULL)
                ORDER BY p.likes_count DESC LIMIT 1
            ");
            $fStmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]]);
        } else {
            $fStmt = $pdo->query(
                "SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL) ORDER BY likes_count DESC LIMIT 1",
            );
        }
        $featuredPrompt = $fStmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $featuredPrompt = null;
}

// Count of new prompts dropped in last 48 hours (for "NEW DROP" banner)
$new_drop_count = 0;
try {
    $newStmt = $pdo->query("SELECT COUNT(*) FROM prompts WHERE created_at >= NOW() - INTERVAL 48 HOUR AND (is_trial = 0 OR is_trial IS NULL)");
    $new_drop_count = (int) $newStmt->fetchColumn();
} catch (PDOException $e) {
    $new_drop_count = 0;
}

// Fetch user gender for personalized welcome
$user_gender = null;
if (isset($_SESSION['user_id'])) {
    try {
        $gRow = $pdo->prepare("SELECT gender FROM users WHERE id = ?");
        $gRow->execute([$_SESSION['user_id']]);
        $user_gender = strtolower(trim($gRow->fetchColumn() ?? ''));
    } catch (Exception $e) { $user_gender = null; }
}

// Social proof counts (logged-out only)
$sp_users = 0; $sp_prompts = 0; $sp_unlocks = 0;
try {
    $sp_users   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $sp_prompts = (int)$pdo->query("SELECT COUNT(*) FROM prompts")->fetchColumn();
    $sp_unlocks = (int)$pdo->query("SELECT COUNT(*) FROM unlocked_prompts")->fetchColumn();
} catch (Exception $e) {}

// Generate state token for CSRF if not exists
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#c084fc">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arigato Devan — AI Couple Prompts for Instagram Reels</title>
    <meta name="description" content="Explore premium AI couple prompts for Instagram Reels. Unlock secret, viral &amp; unreleased prompts — use instantly on ChatGPT. Only on Arigato Devan.">
    <!-- Open Graph & Twitter Card -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="Arigato Devan Prompts — Premium AI Couple Prompts">
    <meta property="og:description" content="Unlock exclusive AI couple prompts for Instagram Reels. Viral, unreleased &amp; secret prompts — only on Arigato Devan! 💜">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Arigato Devan Prompts — Premium AI Couple Prompts">
    <meta name="twitter:description" content="Unlock exclusive AI couple prompts for Instagram Reels. Viral, unreleased &amp; secret prompts — only on Arigato Devan! 💜">
    <meta name="twitter:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <!-- Canonical -->
    <link rel="canonical" href="<?= htmlspecialchars($_page_canonical) ?>">
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <!-- Schema Markup: WebSite + Organization -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "name": "Arigato Devan Prompts",
          "url": "https://arigatodevan.com",
          "description": "Premium AI couple prompts for Instagram Reels. Unlock secret, viral and unreleased prompts on Arigato Devan.",
          "potentialAction": {
            "@type": "SearchAction",
            "target": {
              "@type": "EntryPoint",
              "urlTemplate": "https://arigatodevan.com/gallery.php?search={search_term_string}"
            },
            "query-input": "required name=search_term_string"
          }
        },
        {
          "@type": "Organization",
          "name": "Arigato Devan",
          "url": "https://arigatodevan.com",
          "logo": "https://arigatodevan.com/toplogo/logo01.webp",
          "sameAs": ["https://www.instagram.com/arigato.devan"],
          "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "customer support",
            "url": "https://arigatodevan.com/contact.php"
          }
        }
      ]
    }
    </script>
        <link rel="stylesheet" href="style.min.css?v=20260601">
    

    <!-- Preload first 3 prompt images for faster perceived loading -->
    <?php if (isset($prompts) && is_array($prompts)) {
        for ($i = 0; $i < min(3, count($prompts)); $i++) {
            $fp = $i === 0 ? ' fetchpriority="high"' : '';
            echo '<link rel="preload" as="image" href="' .
                htmlspecialchars($prompts[$i]["image_path"]) .
                '"' . $fp . '>' .
                "\n";
        }
    } ?>

    <?php include_once "gtag.php"; ?>
    <style>
    .pers-welcome{display:inline-flex;align-items:center;gap:10px;padding:8px 20px;border-radius:999px;margin-bottom:14px;backdrop-filter:blur(10px);}
    .pers-female{background:rgba(255,182,210,0.22);border:1.5px solid rgba(255,150,190,0.5);}
    .pers-male{background:rgba(120,160,255,0.18);border:1.5px solid rgba(100,140,255,0.45);}
    .pers-alien{background:rgba(100,220,130,0.18);border:1.5px solid rgba(80,200,110,0.45);}
    .pw-emoji{font-size:1.2rem;flex-shrink:0;}
    .pw-text{display:flex;flex-direction:column;}
    .pw-hi{font-size:.92rem;font-weight:900;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.4);}
    .pw-sub{font-size:.7rem;font-weight:600;color:rgba(255,255,255,.7);margin-top:1px;}

    /* ========= HOMEPAGE FULL-PAGE FIXED BACKGROUND ========= */
    /* Covers header + content + footer as one seamless wallpaper */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        z-index: -2;
        background-image: url('backgroundwally/only-homepage-pic.webp');
        background-size: cover;
        background-position: center top;
        background-repeat: no-repeat;
    }
    /* Dark overlay so all elements stay readable */
    body::after {
        content: '';
        position: fixed;
        inset: 0;
        z-index: -1;
        background: rgba(0,0,0,0.50);
        pointer-events: none;
    }
    /* Mobile: switch to portrait image */
    @media (max-width: 640px) {
        body::before {
            background-image: url('backgroundwally/only-homepage-pic-for-mobile.webp');
            background-position: center center;
        }
    }
    /* ======================================================== */

    /* ---- Hero heading: visible on dark background ---- */
    .landing-comic-h1 {
        color: #ffffff;
        text-shadow: 0 2px 24px rgba(0,0,0,0.7);
    }
    /* "Couple AI" stroke — white outline instead of dark */
    .h1-stroke {
        -webkit-text-stroke: 3px #ffffff;
        color: transparent;
        filter: drop-shadow(0 0 12px rgba(255,255,255,0.35));
    }
    /* "Content" highlight — vivid accent color + glow */
    .h1-highlight {
        color: #f9a8d4;
        text-shadow: 0 0 30px rgba(249,168,212,0.6);
    }
    .h1-highlight::after {
        background: rgba(249,168,212,0.35);
    }
    /* Subtext, "How it Works" label — readable on dark */
    .landing-comic-sub {
        color: rgba(255,255,255,0.88);
        text-shadow: 0 1px 6px rgba(0,0,0,0.5);
    }
    /* -------------------------------------------------- */
    </style>
</head>
<body>


    <header>
        <div class="logo-area" id="logo-container"  style="cursor:pointer;">
            <div class="logo-flipper">
                <div class="logo-front">
                    <img src="toplogo/logo01.webp" alt="Arigato Devan Logo" id="profile-logo" fetchpriority="high">
                </div>
                <div class="logo-back">
                    <img loading="lazy" src="toplogo/logo02.webp" alt="Logo Alt">
                </div>
            </div>
            <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
        </div>
        <nav class="nav-links">
            <a href="digital_store/index.php" class="shop-nav-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg> SHOP</a>
            <a href="gallery.php">GALLERY</a>
            <a href="blogs.php">BLOGS</a>
        <a href="progress.php" title="Our Journey" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-chart-line nav-progress-icon"></i></a>
            <a href="faq.php" title="FAQ" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-circle-question" style="font-size:1.2rem;"></i></a>
            <div class="nav-dropdown">
                <button class="nav-dropdown-btn"><i class="fa-solid fa-film"></i> Reels Type <i class="fa-solid fa-chevron-down dd-arrow"></i></button>
                <?php $curPage = basename($_SERVER["PHP_SELF"]); ?>
                <div class="nav-dropdown-menu">
                    <a href="secret_code.php" <?= $curPage == "secret_code.php"
                        ? 'style="background:var(--primary-color)"'
                        : "" ?>><i class="fa-solid fa-lock"></i> Secret Code Reels <?= empty(
    $nav_counts["secret_code"]
)
    ? '<span class="dd-tag soon">SOON</span>'
    : ($curPage == "secret_code.php"
        ? '<span class="dd-tag">ACTIVE</span>'
        : "") ?></a>
                    <a href="unreleased.php" <?= $curPage == "unreleased.php"
                        ? 'style="background:var(--primary-color)"'
                        : "" ?>><i class="fa-solid fa-star"></i> Unreleased Reels <?= empty(
    $nav_counts["unreleased"]
)
    ? '<span class="dd-tag soon">SOON</span>'
    : ($curPage == "unreleased.php"
        ? '<span class="dd-tag">ACTIVE</span>'
        : "") ?></a>
                    <a href="insta_viral.php" <?= $curPage == "insta_viral.php"
                        ? 'style="background:var(--primary-color)"'
                        : "" ?>><i class="fa-brands fa-instagram"></i> Insta Viral Reels <?= empty(
    $nav_counts["insta_viral"]
)
    ? '<span class="dd-tag soon">SOON</span>'
    : ($curPage == "insta_viral.php"
        ? '<span class="dd-tag">ACTIVE</span>'
        : "") ?></a>
                <a href="already_uploaded.php" <?= $curPage ==
                "already_uploaded.php"
                    ? 'style="background:var(--primary-color)"'
                    : "" ?>><i class="fa-solid fa-clock-rotate-left"></i> Already Uploaded <?= empty(
    $nav_counts["already_uploaded"]
)
    ? '<span class="dd-tag soon">SOON</span>'
    : ($curPage == "already_uploaded.php"
        ? '<span class="dd-tag">ACTIVE</span>'
        : "") ?></a>
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
                <?php if ($_SESSION["role"] === "admin"): ?>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <a href="profile.php" title="Edit Profile" id="admin-profile-avatar-link">
                            <?= renderAvatar(
                                $_SESSION["profile_image"] ?? "",
                                "admin-avatar",
                                "Admin",
                                'style="transition:transform 0.2s;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"',
                            ) ?>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="profile.php" title="Edit Profile" style="color:var(--text-color);display:flex;align-items:center;gap:8px;">
                        <?= renderAvatar(
                            $_SESSION["profile_image"] ?? "",
                            "admin-avatar",
                            "Profile",
                            'style="transition:transform 0.2s;cursor:pointer;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"',
                        ) ?>
                    </a>
                <?php endif; ?>
                <a href="login.php?logout=1" class="logout">
                    <i class="fa-solid fa-right-from-bracket"></i> LOGOUT
                </a>
            <?php else: ?>
                <a href="login.php" class="comic-btn" style="font-size:.85rem;padding:9px 18px;text-decoration:none;color:var(--text-color);background:var(--primary-color);">LOGIN</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!isset($_SESSION["user_id"])): ?>
    <!-- ============ LANDING PAGE (LOGGED OUT) ============ -->
    <div class="landing-page-root" style="background:transparent;">

        <style>
        .sp-strip{display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap;margin:22px auto 0;padding:13px 26px;max-width:380px;background:rgba(255,255,255,0.8);backdrop-filter:blur(8px);border:2.5px solid var(--text-color,#2d2a35);border-radius:28px;box-shadow:4px 4px 0 var(--text-color,#2d2a35);}
        .sp-item{display:flex;flex-direction:column;align-items:center;gap:1px;}
        .sp-num{font-size:1.35rem;font-weight:900;color:var(--text-color,#2d2a35);line-height:1;}
        .sp-label{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#888;}
        .sp-dot{font-size:.7rem;color:#ccc;font-weight:900;}
        </style>

        <!-- Center Hero Content -->
        <div class="landing-center">

            <!-- Sticker Tags row -->
            <?php if (!empty($testimonials)): ?>
            <!-- ══ MINI COLORFUL TESTIMONIALS ══ -->
            <div style="width:100%;max-width:760px;margin:0 auto 28px;padding:0 10px;">
                <p style="text-align:center;font-size:.58rem;font-weight:900;text-transform:uppercase;letter-spacing:.22em;color:#9490bb;margin-bottom:16px;font-family:'Inter',sans-serif;">✦ What our users say ✦</p>
                <div id="miniTestiTrack" style="display:flex;align-items:center;gap:0;overflow-x:auto;padding:12px 12px 24px;scrollbar-width:none;-ms-overflow-style:none;scroll-snap-type:x mandatory;">
                <?php
                $mc = [['#ffd6e7','#f9a8d4','#831843'],['#d0f4de','#86efac','#14532d'],['#e8d5f5','#c4b5fd','#4c1d95'],['#fff3cd','#fde68a','#78350f'],['#cfe2ff','#93c5fd','#1e3a5f'],['#fde8c0','#fdba74','#7c2d12']];
                $t_emojis2=['😭','😢','😟','😕','🙂','😊','😄','😁','🤩','🔥','⭐'];
                foreach ($testimonials as $ti => $t2):
                    $ci = $ti % count($mc);
                    $bg = $mc[$ci][0]; $bc = $mc[$ci][1]; $tc = $mc[$ci][2];
                    $r2 = max(0,min(10,(int)$t2['rating']));
                    $em2 = $t_emojis2[$r2];
                    $tname2 = htmlspecialchars($t2['username'] ?? 'User');
                    $tav2   = $t2['profile_image'] ?? $t2['avatar'] ?? '';
                    $tseed2 = urlencode($t2['username'] ?? 'user');
                    if ($tav2 && !str_starts_with($tav2, 'http')) {
                        $tav2 = ltrim($tav2, '.');
                        $tav2 = ltrim($tav2, '/');
                    }
                    $tg2 = strtolower(trim($t2['gender'] ?? ''));
                    $tgi2 = in_array($tg2,['male','m']) ? '♂' : (in_array($tg2,['female','f']) ? '♀' : '');
                    $shorttext = mb_strlen($t2['feedback_text']) > 68 ? mb_substr($t2['feedback_text'],0,68).'…' : $t2['feedback_text'];
                    // Floating quote separator between cards (not before first)
                    if ($ti > 0):
                ?>
                <!-- Floating quote separator -->
                <div class="testi-sep" style="flex-shrink:0;display:flex;align-items:center;justify-content:center;width:38px;user-select:none;pointer-events:none;">
                    <span style="font-family:'Cormorant Garamond',Georgia,serif;font-size:2.4rem;font-weight:700;color:rgba(139,92,246,0.35);animation:quoteFloat <?= 2 + ($ti * 0.3) ?>s ease-in-out infinite;display:block;line-height:1;">❝</span>
                </div>
                <?php endif; ?>
                <!-- Card -->
                <div style="min-width:210px;max-width:210px;background:<?= $bg ?>;border:2px solid <?= $bc ?>;border-radius:20px;padding:12px 14px 10px;flex-shrink:0;scroll-snap-align:start;box-shadow:3px 3px 0 <?= $bc ?>;transition:transform .2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                    <!-- Rating badge -->
                    <div style="display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,0.78);border-radius:100px;padding:2px 10px;font-size:.68rem;font-weight:900;color:<?= $tc ?>;margin-bottom:8px;border:1px solid <?= $bc ?>;"><?= $em2 ?> <?= $r2 ?>/10</div>
                    <!-- Quote text -->
                    <p style="font-family:'Cormorant Garamond',Georgia,serif;font-size:.95rem;font-style:italic;color:#1a1410;line-height:1.5;margin-bottom:10px;"><?= htmlspecialchars($shorttext) ?></p>
                    <!-- Divider -->
                    <div style="height:1px;background:<?= $bc ?>;opacity:0.4;margin-bottom:9px;"></div>
                    <!-- User row -->
                    <div style="display:flex;align-items:center;gap:7px;">
                        <div style="width:26px;height:26px;border-radius:50%;overflow:hidden;border:2px solid <?= $bc ?>;flex-shrink:0;background:#eee;display:flex;align-items:center;justify-content:center;">
                            <?php if ($tav2): ?>
                            <img src="<?= htmlspecialchars($tav2) ?>" loading="lazy" referrerpolicy="no-referrer" style="width:100%;height:100%;object-fit:cover;" alt="" onerror="this.style.display='none';this.parentNode.querySelector('.av-fallback').style.display='flex'">
                            <span class="av-fallback" style="display:none;font-size:.7rem;font-weight:900;color:<?= $tc ?>;text-transform:uppercase;"><?= strtoupper(substr($t2['username'] ?? 'U', 0, 1)) ?></span>
                            <?php else: ?>
                            <span style="font-size:.7rem;font-weight:900;color:<?= $tc ?>;text-transform:uppercase;"><?= strtoupper(substr($t2['username'] ?? 'U', 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-family:'Inter',sans-serif;font-size:.7rem;font-weight:800;color:#1a1410;"><?= $tname2 ?><?= $tgi2 ? ' <span style="opacity:.65">'.$tgi2.'</span>' : '' ?></div>
                            <div style="font-size:.58rem;color:<?= $tc ?>;font-weight:700;opacity:.7;">Arigato User</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <style>
                #miniTestiTrack::-webkit-scrollbar{display:none}
                @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@1,600&display=swap');
                @keyframes quoteFloat {
                    0%,100% { transform: translateY(0) rotate(-8deg) scale(1); }
                    50%     { transform: translateY(-10px) rotate(8deg) scale(1.15); }
                }
                </style>
            </div>
            <?php endif; ?>



            <div class="sticker-row">
                <div class="sticker sticker-new"><i class="fa-solid fa-wand-magic-sparkles"></i> NEW</div>
                <div class="sticker sticker-hot"><i class="fa-solid fa-fire"></i> HOT</div>
                <div class="sticker sticker-ai"><i class="fa-solid fa-robot"></i> AI-POWERED</div>
            </div>

            <!-- Comic Guest Note -->
            <div id="hero-comic-note" style="position:relative;margin:18px auto 18px;max-width:460px;background:#fffbe6;border:3px solid var(--text-color,#2d2a35);border-radius:18px;padding:16px 22px 16px;box-shadow:4px 4px 0 var(--text-color,#2d2a35);text-align:center;">
                <div style="position:absolute;top:-12px;left:20px;background:#ffec99;border:2.5px solid var(--text-color,#2d2a35);border-radius:999px;padding:2px 12px;font-family:var(--font-main);font-size:.7rem;font-weight:900;text-transform:uppercase;letter-spacing:1px;color:var(--text-color,#2d2a35);"><i class="fa-solid fa-thumbtack"></i> Note</div>
                <p id="comic-note-text" style="font-family:var(--font-main);font-size:.9rem;font-weight:700;color:var(--text-color,#2d2a35);line-height:1.6;margin:8px 0 14px;">No need to login &mdash; you can copy any prompt for free! Just click <strong>Explore</strong>. Login is only for liking &amp; saving prompts</p>
                <a href="gallery.php" style="display:inline-flex;align-items:center;gap:7px;background:var(--secondary-color,#c8b4f8);color:var(--text-color,#2d2a35);border:2.5px solid var(--text-color,#2d2a35);border-radius:999px;padding:10px 24px;font-family:var(--font-main);font-weight:900;font-size:.88rem;text-decoration:none;box-shadow:3px 3px 0 var(--text-color,#2d2a35);transition:transform .15s;" onmouseover="this.style.transform='translate(-2px,-2px)'" onmouseout="this.style.transform=''"><i class="fa-solid fa-compass"></i> Explore Prompts &rarr;</a>
                <div style="margin-top:14px;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <svg id="hero-arrow-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--text-color,#2d2a35)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="animation:heroNoteArrow 1.1s ease-in-out infinite;"><line x1="12" y1="3" x2="12" y2="21"/><polyline points="6 15 12 21 18 15"/></svg>
                    <span style="font-family:var(--font-main);font-size:.85rem;font-weight:900;color:var(--text-color,#2d2a35);text-transform:uppercase;letter-spacing:1.2px;">Go Down For <span style="display:inline-block;border:2px solid var(--text-color,#2d2a35);border-radius:6px;padding:0 7px;background:#ffb3c6;color:var(--text-color,#2d2a35);font-weight:900;">Login</span></span>
                </div>
            </div>
            <style>
            @keyframes heroNoteArrow {
                0%,100% { transform:translateY(0); }
                50% { transform:translateY(8px); }
            }
            </style>
            <script>
            (function(){
                var msgs = [
                    'No need to login &mdash; you can copy any prompt for free! Just click <strong>Explore</strong>. Login is only for liking &amp; saving prompts \u{1F60A}',
                    'Login ki zaroorat nahi &mdash; bina login ke bhi koi bhi prompt copy kar sakte ho! Bas <strong>Explore</strong> click karo. Login sirf like &amp; save ke liye hai \u{1F604}'
                ];
                var i = 0;
                var el = document.getElementById('comic-note-text');
                if (!el) return;
                setInterval(function(){
                    el.style.transition = 'opacity .3s';
                    el.style.opacity = '0';
                    setTimeout(function(){
                        i = (i+1) % msgs.length;
                        el.innerHTML = msgs[i];
                        el.style.opacity = '1';
                    }, 320);
                }, 7000);
            })();
            </script>

            <!-- Main Heading -->
            <h1 class="landing-comic-h1">
                Create Viral<br>
                <span class="h1-stroke">Couple AI</span><br>
                <span class="h1-highlight">Content</span>
            </h1>

            <!-- Subtext -->
            <p class="landing-comic-sub">
                Powered by <strong>Gemini Nano 2</strong> +<br class="mobile-br"> <strong>ChatGPT Image 2.0</strong>
            </p>

            <!-- CTA Buttons -->
            <div class="landing-comic-cta">
                <a href="login.php" class="cta-btn cta-primary" id="hero-login-btn">
                    <i class="fa-brands fa-google" style="font-size:18px;"></i>
                    Login with Google
                </a>
                <a href="gallery.php" class="cta-btn cta-secondary" id="hero-gallery-btn">
                    Explore Prompts →
                </a>
            </div>

            <!-- Social Proof Strip -->
            <div class="sp-strip">
                <div class="sp-item"><span class="sp-num"><?= $sp_users ?>+</span><span class="sp-label">Happy Users</span></div>
                <div class="sp-dot">✦</div>
                <div class="sp-item"><span class="sp-num"><?= $sp_prompts ?>+</span><span class="sp-label">AI Prompts</span></div>
                <div class="sp-dot">✦</div>
                <div class="sp-item"><span class="sp-num"><?= $sp_unlocks ?>+</span><span class="sp-label">Unlocks</span></div>
            </div>

            <!-- How It Works Steps -->
            <div style="max-width:700px;margin:32px auto 0;padding:0 20px;">
                <p style="text-align:center;font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;color:#999;margin-bottom:2px;font-family:var(--font-main);">&#9472;&#9472; How It Works &#9472;&#9472;</p>
                <?php $_steps_page = 'homepage'; include_once 'steps_guide.php'; ?>
            </div>

            <?php if ($featuredPrompt): ?>
            <div style="max-width:480px;margin:32px auto;padding:0 20px;">
                <div style="background:var(--secondary-color);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:20px;box-shadow:var(--shadow-comic);text-align:center;">
                    <div class="badge" style="margin:0 auto 12px;display:inline-flex;">&#11088; PROMPT OF THE DAY</div>
                    <div style="position:relative;border-radius:16px;overflow:hidden;border:3px solid var(--text-color);margin-bottom:14px;">
                        <img loading="lazy" src="<?= htmlspecialchars(
                            $featuredPrompt["image_path"],
                        ) ?>" style="width:100%;height:180px;object-fit:cover;filter:blur(8px) brightness(0.7);" alt="Featured Prompt">
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;">
                            <div style="background:rgba(255,255,255,0.95);border:3px solid var(--text-color);border-radius:50%;width:48px;height:48px;display:flex;align-items:center;justify-content:center;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2d2a35" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </div>
                            <span style="color:#fff;font-weight:900;font-size:.95rem;text-shadow:0 1px 4px rgba(0,0,0,.5);"><?= htmlspecialchars(
                                $featuredPrompt["title"],
                            ) ?></span>
                        </div>
                    </div>
                    <a href="login.php" class="comic-btn" style="text-decoration:none;padding:12px 28px;background:var(--primary-color);display:inline-block;"><i class="fa-solid fa-lock-open"></i> Login to Unlock</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Comparison Cards -->
            <div class="login-compare-section" aria-label="Login vs Guest comparison" style="padding-top:30px; margin-bottom:-20px;">
                <p class="login-compare-heading"><i class="fa-solid fa-scale-balanced"></i>&nbsp; What you get</p>
                <div class="login-compare-row">

                    <!-- WITH LOGIN -->
                    <div class="cmp-card cmp-card-with">
                        <div class="cmp-card-badge">
                            <i class="fa-solid fa-circle-check"></i> WITH LOGIN
                        </div>
                        <div class="cmp-card-title">Logged-in Benefits</div>
                        <ul class="cmp-list">
                            <li>
                                <span class="cmp-icon"><i class="fa-solid fa-check"></i></span>
                                <span>Save your prompts permanently</span>
                            </li>
                            <li>
                                <span class="cmp-icon"><i class="fa-solid fa-check"></i></span>
                                <span>No need to unlock again after refresh</span>
                            </li>
                            <li>
                                <span class="cmp-icon"><i class="fa-solid fa-check"></i></span>
                                <span>Only <strong>20 taps</strong> required to unlock prompts</span>
                            </li>
                            <li>
                                <span class="cmp-icon"><i class="fa-solid fa-check"></i></span>
                                <span>Access &amp; purchase premium couple prompts</span>
                            </li>
                            <li>
                                <span class="cmp-icon"><i class="fa-solid fa-check"></i></span>
                                <span>Can comment on blog posts</span>
                            </li>
                        </ul>
                    </div>

                    <!-- WITHOUT LOGIN -->
                    <div class="cmp-card cmp-card-without">
                        <div class="cmp-card-badge">
                            <i class="fa-solid fa-circle-xmark"></i> WITHOUT LOGIN
                        </div>
                        <div class="cmp-card-title">Guest Limitations</div>
                        <ul class="cmp-list">
                            <li>
                                <span class="cmp-icon"><i class="fa-solid fa-xmark"></i></span>
                                <span>Cannot save prompts permanently</span>
                            </li>
                            <li>
                                <span class="cmp-icon"><i class="fa-solid fa-xmark"></i></span>
                                <span>Need to unlock again after refresh</span>
                            </li>
                            <li>
                                <span class="cmp-icon"><i class="fa-solid fa-xmark"></i></span>
                                <span><strong>90 taps</strong> required to unlock prompts</span>
                            </li>
                            <li>
                                <span class="cmp-icon"><i class="fa-solid fa-xmark"></i></span>
                                <span>Cannot access or purchase premium couple prompts</span>
                            </li>
                            <li>
                                <span class="cmp-icon"><i class="fa-solid fa-xmark"></i></span>
                                <span>Cannot comment on blog posts</span>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

        </div>


        <!-- News Ticker -->
        <div class="news-ticker" aria-label="Latest updates">
            <div class="ticker-label">LIVE</div>
            <div class="ticker-track-wrap">
                <div class="ticker-track">
                    <?php
                    $ticker_items = [
                        "Couple Prompts are here <i class=\"fa-solid fa-heart\"></i>",
                        "Get ready for ultra-realistic AI prompts",
                        "Unlock viral content ideas instantly",
                        "Create stunning couple scenes with AI",
                        "Your next viral reel starts here",
                        "Premium prompts. Real emotions.",
                        "Turn ideas into aesthetic visuals",
                        "AI couple content made easy",
                        "Scroll. Unlock. Create.",
                        "More drops coming every week <i class=\"fa-solid fa-rocket\"></i>",
                    ];
                    $all_ticker = array_merge($ticker_items, $ticker_items); // duplicate for loop
                    foreach ($all_ticker as $t): ?>
                    <span class="ticker-item"><?= $t ?><span class="ticker-sep"><i class="fa-solid fa-star"></i></span></span>
                    <?php endforeach;
                    ?>
                </div>
            </div>
        </div>
    </div><!-- end .landing-page-root -->

    <?php else: ?>
    <!-- ============ LOGGED IN HERO ============ -->
    <div class="hero hero-logged-in">
        <!-- Personalized Welcome -->
        <?php
        $uname = htmlspecialchars($_SESSION['username'] ?? 'Friend');
        if ($user_gender === 'female' || $user_gender === 'f'): ?>
        <div class="pers-welcome pers-female">
            <span class="pw-emoji"><i class="fa-solid fa-heart" style="color:#ff6b9d;"></i></span>
            <div class="pw-text">
                <span class="pw-hi">Hiiii <?= $uname ?>~</span>
                <span class="pw-sub">Aaj kaun sa reel banayenge? Chalo explore karte hain</span>
            </div>
        </div>
        <?php elseif ($user_gender === 'male' || $user_gender === 'm'): ?>
        <div class="pers-welcome pers-male">
            <span class="pw-emoji"><i class="fa-solid fa-dumbbell"></i></span>
            <div class="pw-text">
                <span class="pw-hi">Welcome back, <?= $uname ?>!</span>
                <span class="pw-sub">Tera next viral reel ready hai &mdash; unlock karo! <i class="fa-solid fa-fire"></i></span>
            </div>
        </div>
        <?php else: ?>
        <div class="pers-welcome pers-alien">
            <span class="pw-emoji"><i class="fa-solid fa-robot"></i></span>
            <div class="pw-text">
                <span class="pw-hi">Greetings, <?= $uname ?>!</span>
                <span class="pw-sub">Abhi profile pe ja aur gender set kar!</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($new_drop_count > 0): ?>
        <!-- NEW DROP BANNER -->
        <a href="gallery.php" class="new-drop-banner" style="display:inline-flex;align-items:center;gap:10px;padding:10px 18px;background:linear-gradient(90deg,#ff6b9d,#fb923c);color:#fff;border:var(--border-width) solid var(--text-color);border-radius:999px;font-weight:900;font-size:.9rem;text-transform:uppercase;letter-spacing:.5px;box-shadow:var(--shadow-comic);text-decoration:none;margin-bottom:18px;animation:newDropPulse 1.6s ease-in-out infinite;">
            <i class="fa-solid fa-fire"></i>
            <span><?= $new_drop_count ?> NEW <?= $new_drop_count === 1 ? "PROMPT" : "PROMPTS" ?> DROPPED!</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
        <style>
            @keyframes newDropPulse {
                0%, 100% { transform: scale(1); box-shadow: var(--shadow-comic); }
                50% { transform: scale(1.04); box-shadow: 6px 6px 0 var(--text-color); }
            }
        </style>
        <?php endif; ?>
        <div class="badge">
            <svg style="vertical-align: middle; margin-right: 5px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            FRESH DROPS!
        </div>
        <h1 style="color:#ffffff;text-shadow:0 2px 20px rgba(0,0,0,0.6);">UNLOCK<br>THE <span class="highlight" style="color:#f9a8d4;text-shadow:0 0 24px rgba(249,168,212,0.7);">MAGIC.</span></h1>
        <!-- Gallery Browse Card -->
        <a href="gallery.php" id="gallery-browse-card" style="display:block;text-decoration:none;max-width:520px;margin:0 auto 8px;padding:18px 24px;background:rgba(255,255,255,0.12);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border:2px solid rgba(255,255,255,0.3);border-radius:22px;box-shadow:0 6px 30px rgba(0,0,0,0.28),inset 0 1px 0 rgba(255,255,255,0.2);transition:all .2s ease;color:#fff;">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:52px;height:52px;background:linear-gradient(135deg,#f9a8d4 0%,#c084fc 100%);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.45rem;flex-shrink:0;box-shadow:0 4px 16px rgba(192,132,252,0.45);">
                    <i class="fa-solid fa-images"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:1rem;font-weight:900;color:#fff;margin-bottom:4px;text-shadow:0 1px 8px rgba(0,0,0,0.5);letter-spacing:.2px;">Browse the Complete Prompt Gallery</div>
                    <div style="font-size:.78rem;font-weight:600;color:rgba(255,255,255,0.72);letter-spacing:.3px;">Explore all secret, viral &amp; unreleased AI prompts ✨</div>
                </div>
                <i class="fa-solid fa-arrow-right" style="font-size:1rem;color:rgba(255,255,255,0.6);flex-shrink:0;transition:transform .2s;"></i>
            </div>
        </a>
        <style>
        #gallery-browse-card:hover{transform:translate(-2px,-3px);box-shadow:0 10px 40px rgba(0,0,0,0.38),6px 6px 0 rgba(255,255,255,0.18),inset 0 1px 0 rgba(255,255,255,0.25);background:rgba(255,255,255,0.18);}
        #gallery-browse-card:hover .fa-arrow-right{transform:translateX(4px);}
        #gallery-browse-card:active{transform:translate(1px,1px);}
        </style>
        <!-- Surprise Me Button -->
        <a href="surprise_me.php" class="surprise-me-btn" style="display:inline-flex;align-items:center;gap:10px;margin-top:16px;padding:14px 26px;background:var(--secondary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:900;font-size:1rem;text-transform:uppercase;letter-spacing:.5px;box-shadow:var(--shadow-comic);text-decoration:none;transition:all .15s ease;">
            <i class="fa-solid fa-dice"></i>
            <span>SURPRISE ME</span>
        </a>
        <style>
            .surprise-me-btn:hover { transform: translate(-2px,-2px); box-shadow: 6px 6px 0 var(--text-color); }
            .surprise-me-btn:active { transform: translate(2px,2px); box-shadow: 1px 1px 0 var(--text-color); }
        </style>
    </div>
    <?php endif; ?>


    <?php if (isset($_SESSION["user_id"])): ?>
    <div class="container">
        <?php
        // Collect unique sub-tags across these secret prompts (exclude 'secret' itself)
        $secret_sub_tags = [];
        foreach ($prompts as $sp) {
            $tarr = array_map("trim", explode(",", strtolower($sp["tag"])));
            foreach ($tarr as $t) {
                if (!empty($t) && $t !== "secret") {
                    $secret_sub_tags[] = $t;
                }
            }
        }
        $secret_sub_tags = array_unique($secret_sub_tags);
        sort($secret_sub_tags);
        ?>
        <?php if ($featuredPrompt):

            $fdb_type = $featuredPrompt["prompt_type"] ?? "secret";
            if ($fdb_type === "insta_viral") {
                $fptype = "insta_viral";
            } elseif ($fdb_type === "unreleased") {
                $fptype = "unreleased";
            } elseif ($fdb_type === "already_uploaded") {
                $fptype = "already_uploaded";
            } else {
                $fptype = "secret_code";
            }
            $potd_badges = [
                "secret_code" => '<i class="fa-solid fa-lock"></i> SECRET',
                "unreleased" => '<i class="fa-solid fa-moon"></i> UNRELEASED',
                "insta_viral" => '<i class="fa-solid fa-fire"></i> VIRAL',
                "already_uploaded" => '<i class="fa-solid fa-circle-check"></i> UPLOADED',
            ];
            $type_badge = $potd_badges[$fptype] ?? '<i class="fa-solid fa-lock"></i> SECRET';
            ?>
        <div class="potd-section" style="margin-bottom:36px;">
            <div class="potd-label"><i class="fa-solid fa-star"></i> PROMPT OF THE DAY</div>
            <div class="potd-wrapper">
                <!-- Floating heart decorations -->
                <span class="potd-heart ph1"><i class="fa-solid fa-heart"></i></span>
                <span class="potd-heart ph2"><i class="fa-solid fa-heart"></i></span>
                <span class="potd-heart ph3"><i class="fa-solid fa-heart"></i></span>
                <span class="potd-heart ph4"><i class="fa-solid fa-heart"></i></span>
                <span class="potd-heart ph5"><i class="fa-solid fa-heart"></i></span>
                <span class="potd-heart ph6"><i class="fa-solid fa-heart"></i></span>
                <span class="potd-heart ph7"><i class="fa-solid fa-heart"></i></span>
                <!-- Card -->
                <div class="card potd-card"
                     data-id="<?= $featuredPrompt["id"] ?>"
                     data-slug="<?= htmlspecialchars($featuredPrompt['slug'] ?? '') ?>"
                     data-image="<?= htmlspecialchars(
                         $featuredPrompt["image_path"],
                     ) ?>"
                     data-title="<?= htmlspecialchars(
                         $featuredPrompt["title"],
                     ) ?>"
                     data-reel="<?= htmlspecialchars(
                         $featuredPrompt["reel_link"] ?? "",
                     ) ?>"
                     data-prompt-type="<?= htmlspecialchars($fptype) ?>"
                     data-tags="<?= htmlspecialchars(
                         strtolower($featuredPrompt["tag"]),
                     ) ?>"
                     data-unlocked="<?= $featuredPrompt["is_unlocked"]
                         ? "true"
                         : "false" ?>"
                     data-saved="<?= !empty($featuredPrompt["is_saved"])
                         ? "true"
                         : "false" ?>"
                     data-best-works-in="<?= htmlspecialchars($featuredPrompt['best_works_in'] ?? '') ?>"
                     data-asset-title="<?= htmlspecialchars($featuredPrompt['asset_title'] ?? '') ?>"
                     data-asset-images="<?= htmlspecialchars($featuredPrompt['asset_images'] ?? '[]') ?>"
                     <?= $featuredPrompt["is_unlocked"]
                         ? 'data-prompt-text="' .
                             htmlspecialchars($featuredPrompt["prompt_text"]) .
                             '"'
                         : "" ?>>
                    <!-- Left: inset portrait image -->
                    <div class="potd-img-wrap">
                        <img loading="lazy" src="<?= htmlspecialchars(
                            $featuredPrompt["image_path"],
                        ) ?>" alt="Prompt of the Day">
                        <div class="potd-lock-icon">
                            <?php if (!$featuredPrompt["is_unlocked"]): ?>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <?php else: ?>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#2d2a35" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Right: info -->
                    <div class="potd-info">
                        <div class="potd-title"><?= htmlspecialchars(
                            $featuredPrompt["title"],
                        ) ?></div>
                        <div class="potd-likes-row">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="#e74c3c"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                            <span><?= (int) $featuredPrompt[
                                "likes_count"
                            ] ?> likes</span>
                        </div>
                        <div class="potd-type-badge"><?= $type_badge ?></div>
                        <div class="potd-cta"><?= $featuredPrompt["is_unlocked"]
                            ? "View Prompt →"
                            : "Tap to Unlock →" ?></div>
                    </div>
                    <div class="card-click-trigger"></div>
                </div>
            </div>
        </div>
        <?php
        endif; ?>

        <div class="tag-filter-container" style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin:0 0 24px;">
            <button class="tag-filter-btn active" data-tag="all" style="background:var(--primary-color);padding:8px 18px;border-radius:20px;font-weight:800;border:2px solid var(--text-color);cursor:pointer;font-family:var(--font-main);font-size:0.85rem;transition:all 0.2s;">All</button>
            <?php foreach ($secret_sub_tags as $t): ?>
                <button class="tag-filter-btn" data-tag="<?= htmlspecialchars(
                    $t,
                ) ?>" style="background:var(--bg-color);padding:8px 18px;border-radius:20px;font-weight:800;border:2px solid var(--text-color);cursor:pointer;font-family:var(--font-main);font-size:0.85rem;transition:all 0.2s;text-transform:capitalize;"><?= htmlspecialchars(
    ucfirst($t),
) ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Mobile Swipe Hint — only visible on mobile via CSS, hidden on desktop -->
        <div class="swipe-hint-box" id="swipe-hint-box" aria-label="Swipe instruction">
            <i class="fa-solid fa-arrow-left swipe-hint-arrow"></i>
            <span>Swipe right or left to see the cards</span>
            <i class="fa-solid fa-arrow-right swipe-hint-arrow"></i>
        </div>

        <div class="card-stack-container" id="card-stack">
            <?php if (count($prompts) === 0): ?>
                <p style="text-align:center; width: 100%; font-weight: 700; font-size: 1.2rem; margin-top: 50px;">No content yet! Admins can log in to upload prompts.</p>
            <?php
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                // Map DB prompt_type → JS/UI ptype key
                else: ?>
                <?php foreach ($prompts as $index => $p):

                    $db_type = $p["prompt_type"] ?? "secret";
                    if ($db_type === "insta_viral") {
                        $ptype = "insta_viral";
                    } elseif ($db_type === "unreleased") {
                        $ptype = "unreleased";
                    } else {
                        $ptype = "secret_code";
                    }

                    $tags_arr = array_map(
                        "trim",
                        explode(",", strtolower($p["tag"])),
                    );
                    ?>
                    <div class="card <?= $index === 0
                        ? "card-active"
                        : "card-next" ?>"
                         data-index="<?= $index ?>"
                         data-id="<?= $p["id"] ?>"
                         data-slug="<?= htmlspecialchars($p['slug'] ?? '') ?>"
                         data-created="<?= htmlspecialchars($p["created_at"] ?? "") ?>"
                         data-image="<?= htmlspecialchars($p["image_path"]) ?>"
                         data-title="<?= htmlspecialchars($p["title"]) ?>"
                         data-reel="<?= htmlspecialchars(
                             $p["reel_link"] ?? "",
                         ) ?>"
                         data-prompt-type="<?= htmlspecialchars($ptype) ?>"
                         data-tags="<?= htmlspecialchars(
                             implode(",", $tags_arr),
                         ) ?>"
                         data-unlocked="<?= $p["is_unlocked"]
                             ? "true"
                             : "false" ?>"
                         data-saved="<?= !empty($p["is_saved"])
                             ? "true"
                             : "false" ?>"
                         data-best-works-in="<?= htmlspecialchars($p['best_works_in'] ?? '') ?>"
                         data-asset-title="<?= htmlspecialchars($p['asset_title'] ?? '') ?>"
                         data-asset-images="<?= htmlspecialchars($p['asset_images'] ?? '[]') ?>"
                         <?= $p["is_unlocked"]
                             ? 'data-prompt-text="' .
                                 htmlspecialchars($p["prompt_text"]) .
                                 '"'
                             : "" ?>>
                        <img src="<?= htmlspecialchars($p["image_path"]) ?>" class="card-bg-image" alt="Prompt Image" <?= $index === 0 ? 'fetchpriority="high" loading="eager"' : ($index < 3 ? 'loading="eager"' : 'loading="lazy"') ?>>

                        <?php if (!$p["is_unlocked"]): ?>
                            <div class="card-lock-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </div>
                        <?php else: ?>
                            <div class="card-lock-icon" style="background: var(--primary-color); border-color: var(--text-color);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                        <?php endif; ?>

                        <!-- Clickable overlay to trigger modal -->
                        <div class="card-click-trigger"></div>

                        <div class="card-content-overlay">
                            <div class="card-title"><?= htmlspecialchars(
                                $p["title"],
                            ) ?></div>
                            <div class="like-btn" data-prompt-id="<?= $p[
                                "id"
                            ] ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                <span class="like-count"><?= (int) $p[
                                    "likes_count"
                                ] ?></span>
                            </div>
                        </div>
                    </div>
                <?php
                endforeach; ?>

                <!-- End Card -->
                <div class="card end-card card-next" id="end-card">
                    <div class="end-card-content">
                        <div class="end-card-heart"><i class="fa-solid fa-heart"></i></div>
                        <h3>More prompts coming soon&ndash;¦</h3>
                        <p>stay tuned</p>
                    </div>
                </div>

                <!-- Desktop Navigation Controls (Optional but helpful) -->
                <div class="swipe-controls">
                    <button id="swipe-left-btn" class="comic-btn-small">&larr; Prev</button>
                    <button id="swipe-right-btn" class="comic-btn-small">Next &rarr;</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container" style="padding-top:40px;padding-bottom:20px;position:relative;z-index:2;">
        <div style="background:var(--secondary-color);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:28px 36px;box-shadow:var(--shadow-comic);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;">
            <div>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><div class="badge" style="margin:0;"><i class="fa-solid fa-lock"></i> UNRELEASED</div></div>
                <h2 style="font-size:1.5rem;font-weight:900;margin-bottom:4px;">Secret <span class="highlight">Drops</span> are waiting...</h2>
                <p style="color:#666;font-weight:600;font-size:.9rem;">Exclusive reels you won't find anywhere else. Show some love to unlock them!</p>
            </div>
            <a href="unreleased.php" class="comic-btn" style="text-decoration:none;padding:14px 28px;background:var(--primary-color);white-space:nowrap;"><i class="fa-solid fa-lock-open"></i> Unlock Drops</a>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <!-- Unlock Modal - No longer restricted by session so guests can see the "login to save" features -->
    <div id="unlock-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content split-view">
            <button class="close-modal">&times;</button>
            <div class="modal-left">
                <img loading="lazy" src="" id="modal-image" alt="Prompt Preview">
            </div>
            <div class="modal-right">
                <h2 id="modal-title">PROMPT LOCKED</h2>

                <div class="want-code-section" id="modal-want-code" style="display:none;">
                    <p class="want-code-text">Want Code?</p>
                    <a href="#" id="modal-reel-link" target="_blank" class="comic-btn-small"><i class="fa-solid fa-play"></i> WATCH REEL TO GET IT</a>
                </div>

                <div class="modal-unlock-area" id="modal-unlock-area">
                    <p style="font-weight:700;margin-bottom:16px;color:#555;">Enter the secret code to reveal this prompt.</p>
                    <input type="text" id="unlock-code-input" placeholder="6-Letter Code" maxlength="6">
                    <button id="submit-code"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt</button>
                </div>

                <div class="modal-unlocked-area" id="modal-unlocked-area" style="display:none;flex-direction:column;text-align:left;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap;"><h3 style="color:var(--text-color);font-size:1rem;margin:0;"><i class="fa-solid fa-scroll"></i> THE PROMPT:</h3><div id="modal-bwi-badge"></div></div>
                    <div class="unlocked-text" id="modal-unlocked-text" style="font-family:monospace;font-size:0.95rem;font-weight:500;background:var(--bg-color);padding:15px;border-radius:12px;border:var(--border-width) solid var(--text-color);flex-grow:1;margin-bottom:15px;overflow-y:auto;max-height:200px;white-space:pre-wrap;word-break:break-all;color:var(--text-color);box-shadow:var(--shadow-comic);"></div>
                    <div style="display:flex;gap:10px;flex-wrap:nowrap;width:100%;">
                        <button class="copy-btn" id="modal-copy-btn" style="flex:1;padding:12px;background:var(--primary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);white-space:nowrap;"><i class="fa-solid fa-copy"></i> COPY</button>
                        <button class="save-prompt-btn" id="modal-save-btn" data-prompt-id="" style="flex:1;padding:12px;background:var(--secondary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);white-space:nowrap;"><i class="fa-solid fa-bookmark"></i> SAVE</button>
                        <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="" <?= !isset(
                            $_SESSION["user_id"],
                        )
                            ? 'data-guest="true"'
                            : "" ?> style="flex-shrink:0;min-width:70px;padding:12px 0;background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:12px;cursor:pointer;box-shadow:var(--shadow-comic);transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:6px;">
                            <i class="fa-solid fa-heart" style="font-size:1.1rem;color:#FF4444;"></i>
                            <span id="modal-like-count" style="font-weight:900;color:#FF4444;font-size:0.95rem;">0</span>
                        </button>
                    </div>
                </div>
                <!-- Assets Section -->
                <div id="modal-assets-area" style="display:none;margin-top:16px;border-top:var(--border-width) solid var(--text-color);padding-top:14px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;font-weight:900;font-size:.9rem;color:var(--text-color);"><i class="fa-solid fa-paperclip"></i> <span id="modal-asset-title">Assets</span></div>
                    <div id="modal-asset-images" style="display:flex;gap:10px;flex-wrap:wrap;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Login-to-Save Popup -->
    <div id="login-save-popup" style="display:none;position:fixed;inset:0;background:rgba(45,42,53,.5);backdrop-filter:blur(8px);z-index:3000;align-items:center;justify-content:center;">
        <div style="background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:36px 32px;max-width:400px;width:90%;box-shadow:8px 8px 0 var(--text-color);text-align:center;">
            <div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-lock"></i></div>
            <h3 style="font-size:1.4rem;font-weight:900;margin-bottom:10px;">Login Required</h3>
            <p style="font-weight:600;color:#555;margin-bottom:24px;">Login is mandatory to save your prompt.</p>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button onclick="document.getElementById('login-save-popup').style.display='none'" style="flex:1;padding:14px;background:var(--bg-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);">Cancel</button>
                <a href="login.php" style="flex:1;padding:14px;background:var(--primary-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:var(--text-color);font-family:var(--font-main);">
                    <i class="fa-brands fa-google" style="margin-right:8px;"></i> Login with Google
                </a>
            </div>
        </div>
    </div>

    <script>const isLoggedIn = <?= isset($_SESSION["user_id"])
        ? "true"
        : "false" ?>;
const isAdmin = <?= (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") ? "true" : "false" ?>;</script>
        <script defer src="script.js?v=20260616"></script>
                <script>

        // Background Scroll Logic
        const bgLayers = document.querySelectorAll('.bg-layer');
        let ticking = false;
        if (bgLayers.length > 0) {
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        const scrollPos = window.scrollY;
                        const pixelsPerLayer = 500;
                        let activeIndex = Math.floor(scrollPos / pixelsPerLayer);
                        if (activeIndex >= bgLayers.length) activeIndex = bgLayers.length - 1;
                        bgLayers.forEach((layer, index) => {
                            if (index === activeIndex) layer.classList.add('active');
                            else layer.classList.remove('active');
                        });
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        }

        // Update save-btn promptId when modal opens (script.js handles save logic)
        document.addEventListener('modalOpened', function(e) {
            const btn = document.getElementById('modal-save-btn');
            if (btn && e.detail && e.detail.promptId) btn.dataset.promptId = e.detail.promptId;
        });
    </script>

    <script>
    // Tag filter for swipe-stack on Secret Code page
    document.querySelectorAll('.tag-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tag-filter-btn').forEach(b => {
                b.classList.remove('active');
                b.style.background = 'var(--bg-color)';
            });
            btn.classList.add('active');
            btn.style.background = 'var(--primary-color)';

            const tag = btn.dataset.tag;
            document.querySelectorAll('.card-stack-container .card:not(#end-card)').forEach(card => {
                const cardTags = (card.dataset.tags || '').split(',').map(t => t.trim());
                card.style.display = (tag === 'all' || cardTags.includes(tag)) ? '' : 'none';
            });
        });
    });
    </script>
</body>
</html>

