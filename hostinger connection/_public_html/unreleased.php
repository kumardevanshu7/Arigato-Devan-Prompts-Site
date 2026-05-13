<?php
session_start();
require_once "db.php";
if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}
// Guests allowed &mdash; they need 90 taps; logged-in users need only 20
$tap_threshold = isset($_SESSION["user_id"]) ? 20 : 90;

// Fetch unreleased prompts by prompt_type
if (isset($_SESSION["user_id"])) {
    $stmt = $pdo->prepare("
        SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
               IF(l.id IS NOT NULL, 1, 0) as is_liked
        FROM prompts p
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
        LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
        WHERE p.prompt_type = 'unreleased'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"]]);
    $unreleased = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $unreleased = $pdo
        ->query(
            "SELECT *, 0 as is_unlocked, 0 as is_liked FROM prompts WHERE prompt_type='unreleased' ORDER BY created_at DESC",
        )
        ->fetchAll(PDO::FETCH_ASSOC);
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Unreleased Reels &mdash; Arigato Devan Prompts</title>
<meta name="description" content="Unlock exclusive unreleased prompts on PromptVerse by showing love!">
<link rel="stylesheet" href="style.css?v=2026051205">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <?php include_once "gtag.php"; ?>
</head>
<body>

<!-- Wallpaper Background -->
<div class="scroll-bg-container">
    <div class="bg-layer active" style="background-image:url('https://i.pinimg.com/736x/4d/e2/71/4de271ae9997273cf3fdd47098fa69a3.jpg')"></div>
    <div class="bg-layer" style="background-image:url('https://i.pinimg.com/1200x/76/50/aa/7650aa986d34ca65bb52f261f954149b.jpg')"></div>
    <div class="bg-layer" style="background-image:url('https://i.pinimg.com/1200x/64/c4/c5/64c4c528ee5812610d58ee2c98bbb76f.jpg')"></div>
    <div class="bg-layer" style="background-image:url('https://i.pinimg.com/736x/f9/fd/75/f9fd75e5aa551b89ac88a863921f2f75.jpg')"></div>
    <div class="bg-layer" style="background-image:url('https://i.pinimg.com/736x/a5/15/6a/a5156a264e06ebb47997cf59e66bee31.jpg')"></div>
    <div class="bg-creamy-overlay"></div>
</div>

<header>
    <div class="logo-area" id="logo-container"  style="cursor:pointer">
        <div class="logo-flipper">
            <div class="logo-front"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo" id="profile-logo"></div>
            <div class="logo-back"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt=""></div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="index.php">HOME</a>
        <a href="gallery.php">GALLERY</a>
        <a href="blogs.php">BLOGS</a>
        <a href="progress.php" title="Our Journey" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-chart-line nav-progress-icon"></i></a>
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
                    : "" ?>><i class="bx bx-history"></i> Already Uploaded <?= empty(
    $nav_counts["already_uploaded"]
)
    ? '<span class="dd-tag soon">SOON</span>'
    : ($curPage == "already_uploaded.php"
        ? '<span class="dd-tag">ACTIVE</span>'
        : "") ?></a>
            </div>
        </div>
        <a href="https://www.instagram.com/arigato.devan/" target="_blank" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;font-family:var(--font-main);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            <span style="font-weight:600;">@arigato.devan</span><span class="pulse-dot"></span><span style="font-weight:800;font-size:1.1rem;">13K+</span>
        </a>
    </nav>
    <div class="header-right">
        <div class="header-divider"></div>
        <?php if (isset($_SESSION["user_id"])): ?>
            <?php if ($_SESSION["role"] === "admin"): ?>
                <div style="display:flex;align-items:center;gap:8px;"><a href="profile.php" title="Edit Profile"><?= renderAvatar(
                    $_SESSION["profile_image"] ?? "",
                    "admin-avatar",
                    "Admin",
                ) ?></a><a href="dashboard.php" style="color:var(--text-color);font-weight:800;">ADMIN</a></div>
            <?php else: ?>
                <a href="profile.php" style="color:var(--text-color)"><?= renderAvatar(
                    $_SESSION["profile_image"] ?? "",
                    "admin-avatar",
                    "Profile",
                ) ?></a>
            <?php endif; ?>
            <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
        <?php else: ?>
            <a href="login.php" class="comic-btn" style="font-size:.85rem;padding:9px 18px;text-decoration:none;color:var(--text-color);background:var(--primary-color);">LOGIN</a>
        <?php endif; ?>
    </div>
</header>

<div class="container" style="padding-top:40px;position:relative;z-index:2;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px;">
        <div class="badge" style="margin:0;transform:rotate(-1deg);"><i class="fa-solid fa-lock"></i> UNRELEASED</div>
        <h1 style="font-size:2rem;font-weight:900;">Secret <span class="highlight">Drops</span></h1>
    </div>
    <p style="color:#666;font-weight:600;margin-bottom:30px;">
        Show some love to unlock &mdash; tap the Love Bar
        <strong><?= isset($_SESSION["user_id"]) ? "20" : "90" ?></strong> times!
        <i class="fa-solid fa-heart"></i>
        <?php if (!isset($_SESSION["user_id"])): ?>
            <span style="font-size:.85rem;color:#999;"> (Login to unlock faster with just 20 taps!)</span>
        <?php endif; ?>
    </p>

    <?php if (empty($unreleased)): ?>
        <div style="text-align:center;padding:80px 20px;">
            <div style="font-size:3rem;margin-bottom:16px;"><i class="fa-solid fa-lock"></i></div>
            <h2 style="font-size:1.6rem;font-weight:900;margin-bottom:8px;">Nothing here yet...</h2>
            <p style="color:#888;font-weight:600;">Unreleased reels will appear here when the admin drops them!</p>
        </div>
    <?php
        // Collect sub-tags (excluding 'unreleased' itself)
        // Collect sub-tags (excluding 'unreleased' itself)
        // Collect sub-tags (excluding 'unreleased' itself)
        // Collect sub-tags (excluding 'unreleased' itself)
        // Collect sub-tags (excluding 'unreleased' itself)
        // Collect sub-tags (excluding 'unreleased' itself)
        // Collect sub-tags (excluding 'unreleased' itself)
        // Collect sub-tags (excluding 'unreleased' itself)
        else: ?>
        <?php
        $ur_sub_tags = [];
        foreach ($unreleased as $ur_item) {
            $tarr = array_map(
                "trim",
                explode(",", strtolower($ur_item["tag"])),
            );
            foreach ($tarr as $t) {
                if (!empty($t) && $t !== "unreleased") {
                    $ur_sub_tags[] = $t;
                }
            }
        }
        $ur_sub_tags = array_unique($ur_sub_tags);
        sort($ur_sub_tags);
        ?>
        <?php if (!empty($ur_sub_tags)): ?>
        <div class="tag-filter-container" style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin:0 0 28px;">
            <button class="ur-filter-btn active" data-tag="all" style="background:var(--primary-color);padding:8px 18px;border-radius:20px;font-weight:800;border:2px solid var(--text-color);cursor:pointer;font-family:var(--font-main);font-size:0.85rem;transition:all 0.2s;">All</button>
            <?php foreach ($ur_sub_tags as $t): ?>
                <button class="ur-filter-btn" data-tag="<?= htmlspecialchars(
                    $t,
                ) ?>" style="background:var(--bg-color);padding:8px 18px;border-radius:20px;font-weight:800;border:2px solid var(--text-color);cursor:pointer;font-family:var(--font-main);font-size:0.85rem;transition:all 0.2s;text-transform:capitalize;"><?= htmlspecialchars(
    ucfirst($t),
) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="gallery-grid" id="card-stack">
            <?php foreach ($unreleased as $ur):

                $tags_arr = array_map(
                    "trim",
                    explode(",", strtolower($ur["tag"])),
                );
                $is_unlocked = $ur["is_unlocked"];
                $blur_style = $is_unlocked
                    ? ""
                    : "filter: blur(5px); transform: scale(1.1);";
                ?>
            <div class="card"
                 data-id="<?= $ur["id"] ?>"
                 data-image="<?= htmlspecialchars($ur["image_path"]) ?>"
                 data-title="<?= htmlspecialchars($ur["title"]) ?>"
                 data-prompt-type="unreleased"
                 data-unlocked="<?= $is_unlocked ? "true" : "false" ?>"
                 data-tags="<?= htmlspecialchars(implode(",", $tags_arr)) ?>"
                 <?= $is_unlocked
                     ? 'data-prompt-text="' .
                         htmlspecialchars($ur["prompt_text"]) .
                         '"'
                     : "" ?>>

                <img src="<?= htmlspecialchars(
                    $ur["image_path"],
                ) ?>" class="card-bg-image" alt="<?= htmlspecialchars(
    $ur["title"],
) ?>" style="<?= $blur_style ?>" loading="lazy">
                <div class="card-type-badge urp">UNRELEASED</div>

                <?php if (!$is_unlocked): ?>
                    <div class="card-lock-icon"><i class="fa-solid fa-lock"></i></div>
                <?php else: ?>
                    <div class="card-lock-icon" style="background:var(--primary-color);"><i class="fa-solid fa-check"></i></div>
                <?php endif; ?>

                <div class="card-click-trigger"></div>
                <div class="card-content-overlay">
                    <div class="card-title"><?= htmlspecialchars(
                        $ur["title"],
                    ) ?></div>
                    <div class="like-btn" data-prompt-id="<?= $ur["id"] ?>">
                        <i class="fa-solid fa-heart"></i>
                        <span class="like-count"><?= (int) $ur[
                            "likes_count"
                        ] ?></span>
                    </div>
                </div>
            </div>
            <?php
            endforeach; ?>
        </div>
    <?php endif; ?>
</div>

    <div id="unlock-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content split-view">
            <button class="close-modal">&times;</button>
            <div class="modal-left">
                <img src="" id="modal-image" alt="Prompt Preview">
            </div>
            <div class="modal-right">
                <h2 id="modal-title">PROMPT LOCKED</h2>

                <div class="want-code-section" id="modal-want-code" style="display:none;">
                    <p class="want-code-text">Want Code?</p>
                    <a href="#" id="modal-reel-link" target="_blank" class="comic-btn-small">
                        <i class="fa-solid fa-play"></i> WATCH REEL TO GET IT
                    </a>
                </div>

                <div class="modal-unlock-area" id="modal-unlock-area">
                    <p style="font-weight:700;margin-bottom:16px;color:#555;">Enter the secret code to reveal this prompt.</p>
                    <input type="text" id="unlock-code-input" placeholder="6-Letter Code" maxlength="6">
                    <button id="submit-code"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt</button>
                </div>

                <div class="modal-unlocked-area" id="modal-unlocked-area" style="display:none;flex-direction:column;text-align:left;">
                    <h3 style="margin-bottom:10px;color:var(--text-color);font-size:1rem;"><i class="fa-solid fa-scroll"></i> THE PROMPT:</h3>
                    <div class="unlocked-text" id="modal-unlocked-text" style="font-family:monospace;font-size:0.95rem;font-weight:500;background:var(--bg-color);padding:15px;border-radius:12px;border:var(--border-width) solid var(--text-color);flex-grow:1;margin-bottom:15px;overflow-y:auto;max-height:200px;white-space:pre-wrap;word-break:break-all;color:var(--text-color);box-shadow:var(--shadow-comic);"></div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button class="copy-btn" id="modal-copy-btn" style="flex:1;min-width:120px;padding:12px;background:var(--primary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);"><i class="fa-solid fa-copy"></i> COPY</button>
                        <button class="save-prompt-btn" id="modal-save-btn" data-prompt-id="" style="flex:1;min-width:120px;padding:12px;background:var(--secondary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);"><i class="fa-solid fa-bookmark"></i> SAVE</button>
                    </div>
                    <!-- Like button: below copy/save -->
                    <?php if (isset($_SESSION["user_id"])): ?>
                    <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="" style="margin-top:12px;">
                        <i class="fa-solid fa-heart"></i>
                        <span id="modal-like-count">0</span>
                    </button>
                    <?php else: ?>
                    <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="" data-guest="true" style="margin-top:12px;">
                        <i class="fa-solid fa-heart"></i>
                        <span id="modal-like-count">0</span>
                    </button>
                    <?php endif; ?>
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

<footer>
    <div>&copy; 2026 ARIGATO DEVAN. KEEP CREATING.</div>
    <div class="footer-links"><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
</footer>

<script>const isLoggedIn = <?= isset($_SESSION["user_id"])
    ? "true"
    : "false" ?>;</script>
<script defer src="script.js?v=2026051205"></script>
<script>
// Background Scroll Logic
const bgLayers = document.querySelectorAll('.bg-layer');
let ticking = false;
if (bgLayers.length > 0) {
    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                const scrollPos = window.scrollY;
                let activeIndex = Math.floor(scrollPos / 500);
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
</script>

<script>
// Unreleased filter
document.querySelectorAll('.ur-filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.ur-filter-btn').forEach(b => {
            b.classList.remove('active');
            b.style.background = 'var(--bg-color)';
            b.style.color = 'var(--text-color)';
        });
        btn.classList.add('active');
        btn.style.background = 'var(--primary-color)';
        const tag = btn.dataset.tag;
        document.querySelectorAll('#unreleased-grid .unreleased-card').forEach(card => {
            const tags = (card.dataset.tags || '').split(',').map(t => t.trim());
            card.style.display = (tag === 'all' || tags.includes(tag)) ? '' : 'none';
        });
    });
});

// Auto-open card from shareable link (?open=ID)
(function(){
    var openId = new URLSearchParams(window.location.search).get('open');
    if (!openId) return;
    setTimeout(function(){
        var card = document.querySelector('#card-stack .card[data-id="' + openId + '"]');
        if (card) card.click();
    }, 400);
})();
</script></body></html>
