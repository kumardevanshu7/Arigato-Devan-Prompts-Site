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
    $raw_testimonials = $tStmt->fetchAll(PDO::FETCH_ASSOC);
    $seen_testimonial_text = [];
    foreach ($raw_testimonials as $tRow) {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', (string)($tRow['feedback_text'] ?? ''))));
        if ($normalized === '' || isset($seen_testimonial_text[$normalized])) {
            continue;
        }
        $seen_testimonial_text[$normalized] = true;
        $testimonials[] = $tRow;
        if (count($testimonials) >= 8) {
            break;
        }
    }
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
    <meta name="theme-color" content="#2F4156">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arigato Devan &mdash; AI Couple Prompts for Instagram Reels</title>
    <meta name="description" content="Explore premium AI couple prompts for Instagram Reels. Unlock secret, viral &amp; unreleased prompts &mdash; use instantly on ChatGPT. Only on Arigato Devan.">
    <!-- Open Graph & Twitter Card -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="Arigato Devan Prompts &mdash; Premium AI Couple Prompts">
    <meta property="og:description" content="Unlock exclusive AI couple prompts for Instagram Reels. Viral, unreleased &amp; secret prompts &mdash; only on Arigato Devan!">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Arigato Devan Prompts &mdash; Premium AI Couple Prompts">
    <meta name="twitter:description" content="Unlock exclusive AI couple prompts for Instagram Reels. Viral, unreleased &amp; secret prompts &mdash; only on Arigato Devan!">
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
        <?php include_once 'includes/theme_head.php'; ?>
        <?php include_once 'includes/card_skeleton_assets.php'; ?>
    <link rel="stylesheet" href="css/home-page.css?v=20260740">
    

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
</head>
<body class="page-store page-home theme-nogoda<?= isset($_SESSION['user_id']) ? ' page-home-logged' : '' ?>">

<?php $nav_active = 'home'; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

    <?php if (!isset($_SESSION["user_id"])): ?>
    <?php include 'includes/home_landing.php'; ?>

    <?php else: ?>
    <?php include 'includes/home_logged.php'; ?>

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
                    <p class="want-code-text">Need Secret Code?</p>
                    <a href="all_codes.php" id="modal-reel-link" class="comic-btn-small"><i class="fa-solid fa-code"></i> ALL CODES HERE - CLICK TO KNOW</a>
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

    <script>const isLoggedIn = <?= isset($_SESSION["user_id"]) ? "true" : "false" ?>;
const isAdmin = <?= (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") ? "true" : "false" ?>;</script>
    <script defer src="script.js?v=20260617"></script>
    <script>
        document.addEventListener('modalOpened', function(e) {
            const btn = document.getElementById('modal-save-btn');
            if (btn && e.detail && e.detail.promptId) btn.dataset.promptId = e.detail.promptId;
        });
    </script>
</body>
</html>

