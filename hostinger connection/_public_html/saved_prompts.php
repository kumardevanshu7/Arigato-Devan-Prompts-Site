<?php
session_start();
require_once "db.php";

// Must be logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Onboarding check
if (empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

// Fetch all prompts this user has saved/unlocked
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.image_path, p.prompt_type, p.likes_count,
           p.tag, p.prompt_text,
           IF(l.id IS NOT NULL, 1, 0) as is_liked
    FROM unlocked_prompts up
    JOIN prompts p ON p.id = up.prompt_id
    LEFT JOIN likes l ON l.prompt_id = p.id AND l.user_id = :uid
    WHERE up.user_id = :uid2
    ORDER BY up.created_at DESC
");
$stmt->execute([":uid" => $user_id, ":uid2" => $user_id]);
$saved = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($saved);

$type_map = [
    "secret" => [
        "emoji" => "🔒",
        "label" => "Secret Code",
        "bg" => "#ffe3e3",
        "color" => "#d03030",
    ],
    "unreleased" => [
        "emoji" => "🌙",
        "label" => "Unreleased",
        "bg" => "#fff4cc",
        "color" => "#7a5800",
    ],
    "insta_viral" => [
        "emoji" => "🔥",
        "label" => "Insta Viral",
        "bg" => "#e3f7ff",
        "color" => "#004f7a",
    ],
    "already_uploaded" => [
        "emoji" => "📤",
        "label" => "Already Uploaded",
        "bg" => "#e6f2ff",
        "color" => "#00509e",
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Prompts — PromptVerse</title>
    <link rel="stylesheet" href="style.css?v=1778100000">
    <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
        <link rel="preconnect" href="https://unpkg.com" crossorigin>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-color); }
        .sp-wrap { max-width: 1100px; margin: 0 auto; padding: 32px 24px 100px; }
        .sp-hero { margin-bottom: 28px; }
        .sp-title { font-size: 2rem; font-weight: 900; display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
        .sp-sub { color: #7D7887; font-weight: 600; font-size: .9rem; }
        .sp-empty { text-align: center; padding: 80px 20px; }
        .sp-empty-icon { font-size: 4rem; margin-bottom: 16px; }
        .sp-empty h2 { font-size: 1.6rem; font-weight: 900; margin-bottom: 8px; }
        .sp-empty p { color: #888; font-weight: 600; margin-bottom: 24px; }
        .sp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        @media (max-width: 600px) { .sp-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } .sp-wrap { padding: 20px 14px 80px; } }
    </style>
    <?php include_once 'gtag.php'; ?>
</head>
<body>

<!-- Background -->
<div class="scroll-bg-container" aria-hidden="true">
    <?php for ($i = 1; $i <= 4; $i++): ?>
    <div class="bg-layer" style="background-image:url('landingpics/lan<?= $i ?>.webp');"></div>
    <?php endfor; ?>
</div>
<div class="bg-creamy-overlay" aria-hidden="true"></div>

<header>
    <div class="logo-area" id="logo-container" style="cursor:pointer;">
        <div class="logo-flipper">
            <div class="logo-front">
                <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo" id="profile-logo">
            </div>
            <div class="logo-back">
                <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt="">
            </div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="index.php">HOME</a>
        <a href="gallery.php">GALLERY</a>
    </nav>
    <div class="header-right">
        <div class="header-divider"></div>
        <a href="profile.php" style="color:var(--text-color)">
            <?= renderAvatar(
                $_SESSION["profile_image"] ?? "",
                "admin-avatar",
                "Profile",
            ) ?>
        </a>
        <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
    </div>
</header>

<div class="sp-wrap">
    <div class="sp-hero">
        <div class="sp-title">
            <i class="fa-solid fa-bookmark" style="color:var(--primary-dark);"></i>
            Saved Prompts
        </div>
        <p class="sp-sub">
            All prompts you've unlocked — <?= $total ?> saved so far
        </p>
    </div>

    <?php if ($total === 0): ?>
    <div class="sp-empty">
        <div class="sp-empty-icon">🔖</div>
        <h2>No Saved Prompts Yet</h2>
        <p>Unlock prompts on the site and they'll appear here!</p>
        <a href="index.php" class="comic-btn-small"><i class="fa-solid fa-arrow-left"></i> Browse Prompts</a>
    </div>
    <?php else: ?>
    <div class="sp-grid gallery-grid">
        <?php foreach ($saved as $p):

            $pt = $p["prompt_type"] ?? "secret";
            $tinfo = $type_map[$pt] ?? $type_map["secret"];
            $tags = array_map("trim", explode(",", strtolower($p["tag"])));
            ?>
        <div class="card"
             data-id="<?= $p["id"] ?>"
             data-image="<?= htmlspecialchars($p["image_path"]) ?>"
             data-title="<?= htmlspecialchars($p["title"]) ?>"
             data-prompt-type="<?= htmlspecialchars($pt) ?>"
             data-unlocked="true"
             data-prompt-text="<?= htmlspecialchars($p["prompt_text"]) ?>"
             data-tags="<?= htmlspecialchars(implode(",", $tags)) ?>"
             data-reel="">

            <img src="<?= htmlspecialchars(
                $p["image_path"],
            ) ?>" class="card-bg-image" alt="<?= htmlspecialchars(
    $p["title"],
) ?>" loading="lazy">

            <span class="card-type-badge <?= [
                "secret" => "scp",
                "unreleased" => "urp",
                "insta_viral" => "ivp",
                "already_uploaded" => "aup",
            ][$pt] ?? "scp" ?>" style="font-size:.55rem;padding:2px 6px;">
                <?= $tinfo["emoji"] ?> <?= $tinfo["label"] ?>
            </span>

            <div class="card-lock-icon" style="background:var(--primary-color);">
                <i class="fa-solid fa-check" style="font-size:14px;"></i>
            </div>

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
    <?php endif; ?>
</div>

<!-- Modal (same as other pages) -->
<div id="unlock-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content split-view">
        <button class="close-modal"><i class="fa-solid fa-xmark"></i></button>
        <div class="modal-left">
            <img id="modal-image" src="" alt="Prompt">
        </div>
        <div class="modal-right">
            <h2 id="modal-title">PROMPT</h2>
            <div id="modal-want-code" style="display:none;"><p>Get the code from our Instagram reel!</p><a id="modal-reel-link" href="#" target="_blank"><i class="fa-brands fa-instagram"></i> View Reel</a></div>
            <div id="modal-unlock-area" style="display:none;"></div>
            <div id="modal-unlocked-area" style="display:none;flex-direction:column;gap:12px;">
                <p id="modal-unlocked-text" class="unlocked-text" style="word-break:break-word;"></p>
                <div class="modal-action-buttons" style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="copy-btn" id="modal-copy-btn" style="flex:1;padding:12px;background:var(--primary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;font-family:var(--font-main);"><i class="fa-solid fa-copy"></i> COPY</button>
                </div>
                <?php if (isset($_SESSION["user_id"])): ?>
                <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="" style="margin-top:12px;">
                    <i class="fa-solid fa-heart"></i>
                    <span id="modal-like-count">0</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer>
    <div>&copy; 2026 ARIGATO DEVAN. KEEP CREATING.</div>
    <div class="footer-links"><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
</footer>

<script>const isLoggedIn = <?= isset($_SESSION["user_id"])
    ? "true"
    : "false" ?>;</script>
<script defer src="script.js?v=2026051206"></script>
</body>
</html>
