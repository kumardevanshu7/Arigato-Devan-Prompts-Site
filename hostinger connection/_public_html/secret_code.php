<?php
session_start();
require_once "db.php";
if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

// Fetch secret prompts by prompt_type
if (isset($_SESSION["user_id"])) {
    $stmt = $pdo->prepare(
        "SELECT p.*, IF(u.id IS NOT NULL,1,0) as is_unlocked, IF(l.id IS NOT NULL,1,0) as is_liked, IF(sv.id IS NOT NULL, 1, 0) as is_saved FROM prompts p LEFT JOIN unlocked_prompts u ON p.id=u.prompt_id AND u.user_id=? LEFT JOIN likes l ON p.id=l.prompt_id AND l.user_id=? LEFT JOIN saved_prompts sv ON p.id=sv.prompt_id AND sv.user_id=? WHERE p.prompt_type='secret' AND (p.is_trial = 0 OR p.is_trial IS NULL) ORDER BY p.created_at DESC",
    );
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]]);
} else {
    $stmt = $pdo->query(
        "SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE prompt_type='secret' AND (is_trial = 0 OR is_trial IS NULL) ORDER BY created_at DESC",
    );
}
$secret_prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($_SESSION["oauth_state"])) {
    $_SESSION["oauth_state"] = bin2hex(random_bytes(16));
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <meta name="theme-color" content="#c084fc">
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Secret Code Reels &mdash; Arigato Devan Prompts</title>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="Arigato Devan Prompts">
    <meta property="og:description" content="Discover the best AI prompts for Instagram Reels.">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/secret_code.php">
    <meta name="twitter:card" content="summary_large_image">
<meta name="description" content="Unlock exclusive secret prompts on PromptVerse with a 6-character code.">
    <link rel="canonical" href="https://arigatodevan.com/secret_code.php">
<link rel="stylesheet" href="style.min.css?v=20260601">
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel='preload' href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' as='style' onload='this.onload=null;this.rel="stylesheet"'>
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<style>
.coming-soon-wrap{min-height:70vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:60px 24px;position:relative;z-index:2}
.cs-icon{font-size:4.5rem;background:var(--primary-color);border:var(--border-width) solid var(--text-color);border-radius:50%;width:110px;height:110px;display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-comic);margin-bottom:28px;animation:pulse-icon 2s ease-in-out infinite}
@keyframes pulse-icon{0%,100%{transform:scale(1) rotate(-3deg)}50%{transform:scale(1.08) rotate(3deg)}}
.cs-title{font-size:3rem;font-weight:900;letter-spacing:-1px;margin-bottom:12px;line-height:1.1}
.cs-sub{font-size:1.1rem;color:#666;font-weight:600;max-width:480px;line-height:1.6;margin-bottom:36px}
.cs-badge{display:inline-flex;align-items:center;gap:8px;background:var(--secondary-color);border:var(--border-width) solid var(--text-color);border-radius:40px;padding:12px 28px;font-weight:900;font-size:1rem;box-shadow:var(--shadow-comic);margin-bottom:24px}
.cs-notify-row{display:flex;gap:12px;flex-wrap:wrap;justify-content:center}
</style>
    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://arigatodevan.com"},{"@type":"ListItem","position":2,"name":"Secret Code Prompts","item":"https://arigatodevan.com/secret_code.php"}]}
    </script>
    <?php include_once "gtag.php"; ?>
    <style>
        html, body { background: transparent !important; height: 100%; margin: 0; }
        body::before { content: ''; position: fixed; inset: 0; z-index: -2; background-image: url('backgroundwally/only-homepage-pic.webp'); background-size: cover; background-position: center top; background-repeat: no-repeat; }
        body::after { content: ''; position: fixed; inset: 0; z-index: -1; background: rgba(0,0,0,0.52); pointer-events: none; }
        @media (max-width: 640px) { body::before { background-image: url('backgroundwally/only-homepage-pic-for-mobile.webp'); background-position: center center; } }
        .aurora-bg { display: none !important; }
    </style>
</head>
<body class="page-gallery">

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
            <div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo" id="profile-logo"></div>
            <div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div>
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
                    : "" ?>><i class="fa-solid fa-cloud-arrow-up"></i> Already Uploaded <?= empty(
    $nav_counts["already_uploaded"]
)
    ? '<span class="dd-tag soon">SOON</span>'
    : ($curPage == "already_uploaded.php"
        ? '<span class="dd-tag">ACTIVE</span>'
        : "") ?></a>
                    <a href="direct_prompts.php" <?= $curPage == "direct_prompts.php" ? 'style="background:var(--primary-color)"' : "" ?>><i class="fa-solid fa-hand-pointer"></i> Direct Prompts <?= empty($nav_counts["direct"]) ? '<span class="dd-tag soon">SOON</span>' : ($curPage == "direct_prompts.php" ? '<span class="dd-tag">ACTIVE</span>' : "") ?></a>
                </div>
        </div>
        <a href="https://www.instagram.com/arigato.devan/" target="_blank" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;font-family:var(--font-main);">
            <i class="fa-brands fa-instagram" style="font-size:18px;"></i>
            <span style="font-weight:600;">@arigato.devan</span><span class="pulse-dot"></span><span style="font-weight:800;font-size:1.1rem;">15K+</span>
        </a>
    </nav>
    <div class="header-right">
        <div class="header-divider"></div>
        <?php if (isset($_SESSION["user_id"])): ?>
            <?php if ($_SESSION["role"] === "admin"): ?>
                <div style="display:flex;align-items:center;gap:8px;"><a href="profile.php"><?= renderAvatar(
                    $_SESSION["profile_image"] ?? "",
                    "admin-avatar",
                    "Admin",
                ) ?></a><a href="dashboard.php" style="color:var(--text-color);font-weight:800;">ADMIN</a></div>
            <?php else: ?>
                <a href="profile.php"><?= renderAvatar(
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

<?php if (empty($secret_prompts)): ?>
<div class="coming-soon-wrap">
    <div class="cs-icon"><i class="fa-solid fa-lock" style="font-size:2.5rem;"></i></div>
    <div class="cs-badge"><i class="fa-solid fa-clock"></i> Coming Very Soon</div>
    <h1 class="cs-title">Secret Code<br><span class="highlight">Reels</span></h1>
    <p class="cs-sub">Exclusive locked prompts are being prepared. Watch our reels to get the secret codes and unlock them first! <i class="fa-solid fa-lock"></i></p>
    <div class="cs-notify-row">
        <a href="https://www.instagram.com/arigato.devan/" target="_blank" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#f09433,#dc2743);color:white;padding:12px 24px;border-radius:12px;border:var(--border-width) solid var(--text-color);font-weight:900;text-decoration:none;box-shadow:var(--shadow-comic);"><i class="fa-brands fa-instagram"></i> Follow @arigato.devan</a>
        <a href="gallery.php" class="comic-btn" style="text-decoration:none;padding:12px 22px;"><i class="fa-solid fa-arrow-left"></i> Explore Gallery</a>
    </div>
</div>
<?php else: ?>
<div class="container" style="padding-top:40px;position:relative;z-index:2;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px;">
        <div class="badge" style="margin:0;transform:rotate(-1deg);"><i class="fa-solid fa-lock"></i> SECRET CODE</div>
        <h1 style="font-size:2rem;font-weight:900;">Locked <span class="highlight">Prompts</span></h1>
    </div>
    <p style="color:#666;font-weight:600;margin-bottom:20px;">Watch our reels to get the secret code and unlock the prompt!</p>

    <?php
    $sc_sub_tags = [];
    foreach ($secret_prompts as $sp) {
        $tarr = array_map("trim", explode(",", strtolower($sp["tag"])));
        foreach ($tarr as $t) {
            if (!empty($t) && $t !== "secret") {
                $sc_sub_tags[] = $t;
            }
        }
    }
    $sc_sub_tags = array_unique($sc_sub_tags);
    sort($sc_sub_tags);
    ?>
    <?php if (!empty($sc_sub_tags)): ?>
    <div class="tag-filter-container" style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin:0 0 28px;">
        <button class="sc-filter-btn active" data-tag="all" style="background:var(--primary-color);padding:8px 18px;border-radius:20px;font-weight:800;border:2px solid var(--text-color);cursor:pointer;font-family:var(--font-main);font-size:0.85rem;transition:all 0.2s;">All</button>
        <?php foreach ($sc_sub_tags as $t): ?>
            <button class="sc-filter-btn" data-tag="<?= htmlspecialchars(
                $t,
            ) ?>" style="background:var(--bg-color);padding:8px 18px;border-radius:20px;font-weight:800;border:2px solid var(--text-color);cursor:pointer;font-family:var(--font-main);font-size:0.85rem;transition:all 0.2s;text-transform:capitalize;"><?= htmlspecialchars(
    ucfirst($t),
) ?></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="gallery-grid" id="sc-grid">
        <?php foreach ($secret_prompts as $p):
            $tags_arr = array_map(
                "trim",
                explode(",", strtolower($p["tag"])),
            ); ?>
        <div class="card"
             data-id="<?= $p["id"] ?>"
             data-slug="<?= htmlspecialchars($p['slug'] ?? '') ?>"
             data-created="<?= htmlspecialchars($p["created_at"] ?? "") ?>"
             data-image="<?= htmlspecialchars($p["image_path"]) ?>"
             data-title="<?= htmlspecialchars($p["title"]) ?>"
             data-reel="<?= htmlspecialchars($p["reel_link"] ?? "") ?>"
             data-unlocked="<?= $p["is_unlocked"] ? "true" : "false" ?>"
             data-prompt-type="secret_code"
             data-saved="<?= !empty($p["is_saved"]) ? "true" : "false" ?>"
             data-tags="<?= htmlspecialchars(implode(",", $tags_arr)) ?>"
             data-best-works-in="<?= htmlspecialchars($p['best_works_in'] ?? '') ?>"
             data-asset-title="<?= htmlspecialchars($p['asset_title'] ?? '') ?>"
             data-asset-images="<?= htmlspecialchars($p['asset_images'] ?? '[]') ?>"
             <?= $p["is_unlocked"]
                 ? 'data-prompt-text="' .
                     htmlspecialchars($p["prompt_text"]) .
                     '"'
                 : "" ?>>

            <img loading="lazy" src="<?= htmlspecialchars(
                $p["image_path"],
            ) ?>" class="card-bg-image" alt="<?= htmlspecialchars(
    $p["title"],
) ?>" loading="lazy">
            <div class="card-type-badge scp">SCP</div>

            <?php if (!$p["is_unlocked"]): ?>
                <div class="card-lock-icon"><i class="fa-solid fa-lock" style="font-size:14px;"></i></div>
            <?php else: ?>
                <div class="card-lock-icon" style="background:var(--primary-color);"><i class="fa-solid fa-check" style="font-size:14px;"></i></div>
            <?php endif; ?>

            <div class="card-click-trigger"></div>
            <div class="card-content-overlay">
                <div class="card-title"><?= htmlspecialchars(
                    $p["title"],
                ) ?></div>
                <div class="card-like-display"
                     data-liked="<?= $p["is_liked"] ? "true" : "false" ?>"
                     data-prompt-id="<?= $p["id"] ?>">
                    <i class="fa-solid fa-heart <?= $p["is_liked"]
                        ? "liked-heart"
                        : "" ?>"></i>
                    <span class="like-count"><?= (int) $p[
                        "likes_count"
                    ] ?></span>
                </div>
            </div>
        </div>
        <?php
        endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Unlock Modal -->
<div id="unlock-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content split-view">
        <button class="close-modal">&times;</button>
        <div class="modal-left">
            <img loading="lazy" src="" id="modal-image" alt="Prompt Preview">
        </div>
        <div class="modal-right">
            <h2 id="modal-title">PROMPT LOCKED</h2>

            <?php if (!isset($_SESSION["user_id"])): ?>
            <div style="background:var(--secondary-color);border:var(--border-width) solid var(--text-color);border-radius:14px;padding:14px 16px;margin-bottom:16px;font-weight:700;font-size:0.9rem;">
                <i class="fa-solid fa-circle-info"></i> <a href="login.php" style="color:var(--text-color);font-weight:900;">Login</a> to save unlocked prompts permanently!
            </div>
            <?php endif; ?>

            <div class="want-code-section" id="modal-want-code" style="display:none;">
                <p class="want-code-text">Want the Code?</p>
                <a href="#" id="modal-reel-link" target="_blank" class="comic-btn-small"><i class="fa-solid fa-play"></i> WATCH REEL TO GET IT</a>
            </div>

            <div class="modal-unlock-area" id="modal-unlock-area">
                <p style="font-weight:700;margin-bottom:16px;color:#555;">Enter the 6-character secret code to reveal this prompt.</p>
                <input type="text" id="unlock-code-input" placeholder="6-Letter Code" maxlength="6" style="text-transform:uppercase;letter-spacing:3px;font-weight:900;">
                <p id="code-error-msg" style="color:#dc2743;font-weight:800;font-size:0.9rem;display:none;margin:8px 0;"><i class="fa-solid fa-xmark"></i> Wrong code! Try again.</p>
                <button id="submit-code"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt</button>
            </div>

            <div class="modal-unlocked-area" id="modal-unlocked-area" style="display:none;flex-direction:column;text-align:left;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap;"><h3 style="color:var(--text-color);font-size:1rem;margin:0;"><i class="fa-solid fa-scroll"></i> THE PROMPT:</h3><div id="modal-bwi-badge"></div></div>
                <div class="unlocked-text" id="modal-unlocked-text" style="font-family:monospace;font-size:0.95rem;font-weight:500;background:var(--bg-color);padding:15px;border-radius:12px;border:var(--border-width) solid var(--text-color);flex-grow:1;margin-bottom:15px;overflow-y:auto;max-height:200px;white-space:pre-wrap;word-break:break-all;color:var(--text-color);box-shadow:var(--shadow-comic);"></div>
                <div class="modal-action-buttons">
                    <button class="copy-btn" id="modal-copy-btn" style="flex:1;padding:12px;background:var(--primary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);white-space:nowrap;"><i class="fa-solid fa-copy"></i> COPY</button>
                    <button class="save-prompt-btn" id="modal-save-btn" data-prompt-id="" style="flex:1;padding:12px;background:var(--secondary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);white-space:nowrap;"><i class="fa-solid fa-bookmark"></i> SAVE</button>
                    <?php if (isset($_SESSION["user_id"])): ?>
                    <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="" style="flex-shrink:0;min-width:70px;padding:12px 0;background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:12px;cursor:pointer;box-shadow:var(--shadow-comic);transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="fa-solid fa-heart" style="font-size:1.1rem;color:#FF4444;"></i>
                        <span id="modal-like-count" style="font-weight:900;color:#FF4444;font-size:0.95rem;">0</span>
                    </button>
                    <?php else: ?>
                    <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="" data-guest="true" style="flex-shrink:0;min-width:70px;padding:12px 0;background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:12px;cursor:pointer;box-shadow:var(--shadow-comic);transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="fa-solid fa-heart" style="font-size:1.1rem;color:#FF4444;"></i>
                        <span id="modal-like-count" style="font-weight:900;color:#FF4444;font-size:0.95rem;">0</span>
                    </button>
                    <?php endif; ?>
                    <!-- Assets Section -->
                    <div id="modal-assets-area" style="display:none;margin-top:16px;border-top:var(--border-width) solid var(--text-color);padding-top:14px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;font-weight:900;font-size:.9rem;color:var(--text-color);"><i class="fa-solid fa-paperclip"></i> <span id="modal-asset-title">Assets</span></div>
                        <div id="modal-asset-images" style="display:flex;gap:10px;flex-wrap:wrap;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>const isLoggedIn = <?= isset($_SESSION["user_id"])
    ? "true"
    : "false" ?>;</script>
<script defer src="script.min.js?v=20260616"></script>
<script>
// Background scroll
const bgLayers = document.querySelectorAll('.bg-layer');
if(bgLayers.length>0){window.addEventListener('scroll',()=>{const s=window.scrollY;let i=Math.floor(s/500);if(i>=bgLayers.length)i=bgLayers.length-1;bgLayers.forEach((l,idx)=>{if(idx===i)l.classList.add('active');else l.classList.remove('active');});});}

// Card click &rarr; open modal
const modal = document.getElementById('unlock-modal');
const modalImg = document.getElementById('modal-image');
const modalTitle = document.getElementById('modal-title');
const unlockArea = document.getElementById('modal-unlock-area');
const unlockedArea = document.getElementById('modal-unlocked-area');
const reelSection = document.getElementById('modal-want-code');
const reelLink = document.getElementById('modal-reel-link');
const codeInput = document.getElementById('unlock-code-input');
const codeError = document.getElementById('code-error-msg');
const submitBtn = document.getElementById('submit-code');
const unlockedText = document.getElementById('modal-unlocked-text');
const copyBtn = document.getElementById('modal-copy-btn');
const saveBtn = document.getElementById('modal-save-btn');
let currentPromptId = '';

document.querySelectorAll('#sc-grid .card').forEach(card => {
    card.addEventListener('click', function(e) {
        if(e.target.closest('.like-btn')) { e.stopPropagation(); return; }
        const isUnlocked = this.dataset.unlocked === 'true';
        const promptText = this.dataset.promptText || '';
        currentPromptId = this.dataset.id;

        modalImg.src = this.dataset.image;
        modalTitle.textContent = this.dataset.title.toUpperCase();
        if(saveBtn) {
            saveBtn.dataset.promptId = currentPromptId;
            if (typeof applySaveBtnState === 'function') {
                applySaveBtnState(saveBtn, this.dataset.saved === 'true');
            }
        }

        const reel = this.dataset.reel;
        if(reel) { reelSection.style.display='block'; reelLink.href=reel; } else { reelSection.style.display='none'; }

        codeInput.value=''; codeError.style.display='none';

        if(isUnlocked && promptText) {
            unlockArea.style.display='none';
            unlockedArea.style.display='flex';
            unlockedText.textContent=promptText;
        } else {
            unlockArea.style.display='block';
            unlockedArea.style.display='none';
        }

        modal.style.display='flex';
        document.body.style.overflow='hidden';
    });
});

// Close modal
document.querySelector('.close-modal').addEventListener('click',()=>{modal.style.display='none';document.body.style.overflow='';});
modal.addEventListener('click',e=>{if(e.target===modal){modal.style.display='none';document.body.style.overflow='';}});

// Submit code
submitBtn.addEventListener('click', verifyCode);
codeInput.addEventListener('keydown',e=>{if(e.key==='Enter')verifyCode();});

function verifyCode(){
    const code = codeInput.value.trim();
    if(code.length!==6){codeError.innerHTML='<i class="fa-solid fa-xmark"></i> Code must be 6 characters!';codeError.style.display='block';return;}
    submitBtn.disabled=true; submitBtn.textContent='Checking...';
    fetch('unlock.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=verify&prompt_id=${currentPromptId}&code=${encodeURIComponent(code)}`})
        .then(r=>r.json()).then(data=>{
            if(data.success){
                codeError.style.display='none';
                unlockArea.style.display='none';
                unlockedArea.style.display='flex';
                unlockedText.textContent=data.prompt_text;
                // update card state
                const card=document.querySelector(`#sc-grid .card[data-id="${currentPromptId}"]`);
                if(card){card.dataset.unlocked='true';card.dataset.promptText=data.prompt_text;const lock=card.querySelector('.card-lock-icon');if(lock){lock.style.background='var(--primary-color)';lock.innerHTML='<i class="fa-solid fa-check" style="font-size:14px;"></i>';}}
                spawnEmojis();
                if (typeof checkFirstUnlock === 'function') checkFirstUnlock();
            } else {
                codeError.innerHTML='<i class="fa-solid fa-xmark"></i> Wrong code! Try again.';
                codeError.style.display='block';
                codeInput.value='';
                codeInput.style.animation='none'; setTimeout(()=>{codeInput.style.animation='shake 0.4s';},10);
            }
        }).catch(()=>{codeError.innerHTML='<i class="fa-solid fa-xmark"></i> Network error.';codeError.style.display='block';})
        .finally(()=>{submitBtn.disabled=false;submitBtn.innerHTML='<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt';});
}

function spawnEmojis(){const icons=['fa-star','fa-heart','fa-fire','fa-bolt','fa-lock-open','fa-wand-magic-sparkles'];for(let i=0;i<18;i++){const s=document.createElement('span');const ic=icons[Math.floor(Math.random()*icons.length)];s.innerHTML='<i class="fa-solid '+ic+'"></i>';s.style.cssText='position:fixed;top:-40px;left:'+Math.random()*100+'vw;font-size:'+(1.5+Math.random()*1.5)+'rem;pointer-events:none;z-index:9999;animation:fall '+(1.5+Math.random()*2)+'s ease forwards;color:hsl('+Math.floor(Math.random()*360)+',80%,60%);';document.body.appendChild(s);setTimeout(()=>s.remove(),4000);}}

// Copy
if(copyBtn){copyBtn.addEventListener('click',()=>{navigator.clipboard.writeText(unlockedText.textContent).then(()=>{copyBtn.innerHTML='<i class="fa-solid fa-check"></i> COPIED!';setTimeout(()=>{copyBtn.innerHTML='<i class="fa-solid fa-copy"></i> COPY';},2000);});});}

// Tag filter
document.querySelectorAll('.sc-filter-btn').forEach(btn=>{btn.addEventListener('click',()=>{document.querySelectorAll('.sc-filter-btn').forEach(b=>{b.classList.remove('active');b.style.background='var(--bg-color)';b.style.color='var(--text-color)';});btn.classList.add('active');btn.style.background='var(--primary-color)';const tag=btn.dataset.tag;document.querySelectorAll('#sc-grid .card').forEach(card=>{const tags=(card.dataset.tags||'').split(',').map(t=>t.trim());card.style.display=(tag==='all'||tags.includes(tag))?'':'none';});});});

// Auto-open card from shareable link (?open=ID)
(function(){
    var openId = new URLSearchParams(window.location.search).get('open');
    if (!openId) return;
    setTimeout(function(){
        var card = document.querySelector('#sc-grid .card[data-id="' + openId + '"]');
        if (card) card.click();
    }, 400);
})();
</script>
<style>
@keyframes fall{0%{transform:translateY(0) rotate(0deg);opacity:1}100%{transform:translateY(110vh) rotate(360deg);opacity:0}}
@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-8px)}40%,80%{transform:translateX(8px)}}
</style>
</body></html>

