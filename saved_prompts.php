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

// Fetch all prompts this user has explicitly saved
$stmt = $pdo->prepare("
    SELECT p.id, p.slug, p.title, p.image_path, p.prompt_type, p.likes_count,
           p.tag, p.prompt_text,
           IF(l.id IS NOT NULL, 1, 0) as is_liked
    FROM saved_prompts sp
    JOIN prompts p ON p.id = sp.prompt_id
    LEFT JOIN likes l ON l.prompt_id = p.id AND l.user_id = :uid
    WHERE sp.user_id = :uid2
    ORDER BY sp.created_at DESC
");
$stmt->execute([":uid" => $user_id, ":uid2" => $user_id]);
$saved = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($saved);

$stats = [
    "secret" => 0,
    "unreleased" => 0,
    "insta_viral" => 0,
    "already_uploaded" => 0
];
foreach ($saved as $p) {
    $pt = $p["prompt_type"] ?? "secret";
    if (isset($stats[$pt])) {
        $stats[$pt]++;
    }
}

$type_map = [
    "secret" => [
        "icon" => '<i class="fa-solid fa-lock"></i>',
        "label" => "Secret Code",
        "bg" => "#ffe3e3",
        "color" => "#d03030",
    ],
    "unreleased" => [
        "icon" => '<i class="fa-solid fa-moon"></i>',
        "label" => "Unreleased",
        "bg" => "#fff4cc",
        "color" => "#7a5800",
    ],
    "insta_viral" => [
        "icon" => '<i class="fa-solid fa-fire"></i>',
        "label" => "Insta Viral",
        "bg" => "#e3f7ff",
        "color" => "#004f7a",
    ],
    "already_uploaded" => [
        "icon" => '<i class="fa-solid fa-upload"></i>',
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
    <title>Saved Prompts &mdash; Arigato Devan Prompts</title>
    <link rel="stylesheet" href="style.min.css?v=20260601">
        <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
        <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel='preload' href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' as='style' onload='this.onload=null;this.rel="stylesheet"'>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <style>
        body { background: transparent; } /* Let the overlay handle it */
        .sp-wrap { max-width: 1100px; margin: 0 auto; padding: 32px 24px 100px; position: relative; z-index: 1; }
        
        .sp-hero { 
            margin-bottom: 50px; 
            background: #FFDE59; 
            border: var(--border-width, 3px) solid var(--text-color, #2d2a35); 
            border-radius: 24px; 
            padding: 30px; 
            box-shadow: 12px 12px 0 var(--text-color, #2d2a35);
            position: relative;
            z-index: 1;
            transform: rotate(-1deg);
            background-image: radial-gradient(var(--text-color, #2d2a35) 15%, transparent 16%);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
        }
        .sp-hero-inner {
            background: var(--card-bg, #fff);
            border: var(--border-width, 3px) solid var(--text-color, #2d2a35);
            border-radius: 16px;
            padding: 40px 20px;
            box-shadow: inset 0 0 0 4px #ffe6e6, 8px 8px 0 rgba(45, 42, 53, 0.1);
            position: relative;
            z-index: 2;
            transform: rotate(1deg);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .sp-comic-badge {
            position: absolute;
            top: -25px;
            right: 20px;
            background: #FF3B30;
            color: #fff;
            font-size: 1.2rem;
            font-weight: 900;
            padding: 10px 20px;
            border: var(--border-width, 3px) solid var(--text-color, #2d2a35);
            border-radius: 30px;
            transform: rotate(10deg);
            box-shadow: 4px 4px 0 var(--text-color, #2d2a35);
            z-index: 3;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .sp-title { 
            font-size: 3rem; 
            font-weight: 900; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            gap: 16px; 
            margin-bottom: 20px; 
            text-transform: uppercase;
            color: var(--text-color, #2d2a35);
            letter-spacing: -1px;
            text-shadow: 2px 2px 0px #fff;
        }
        .sp-title-icon-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: #00E5FF;
            border: var(--border-width, 3px) solid var(--text-color, #2d2a35);
            border-radius: 16px;
            box-shadow: 4px 4px 0 var(--text-color, #2d2a35);
            transform: rotate(-10deg);
        }
        .sp-title-icon-wrap i {
            color: var(--text-color, #2d2a35);
            font-size: 2rem;
        }
        .sp-sub { 
            color: var(--text-color, #2d2a35); 
            font-weight: 800; 
            font-size: 1.1rem; 
            background: #E8F5E9; 
            padding: 12px 24px; 
            border-radius: 16px; 
            border: var(--border-width, 3px) solid var(--text-color, #2d2a35);
            display: inline-block;
            box-shadow: 5px 5px 0 var(--text-color, #2d2a35);
            margin-bottom: 20px;
        }
        
        .sp-filters {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .sp-pill {
            background: #fff;
            color: var(--text-color, #2d2a35);
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 900;
            font-size: 0.95rem;
            text-transform: uppercase;
            box-shadow: 3px 3px 0 var(--text-color, #2d2a35);
            border: var(--border-width, 3px) solid var(--text-color, #2d2a35);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        .sp-pill:hover {
            transform: translateY(-2px);
            box-shadow: 5px 5px 0 var(--text-color, #2d2a35);
        }
        .sp-pill.active {
            background: var(--text-color, #2d2a35);
            color: #fff;
        }

        .bg-four-cols {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            display: flex;
            z-index: -5;
            background: var(--bg-color, #f8f9fa);
        }
        .bg-col {
            flex: 1;
            position: relative;
            overflow: hidden;
            border-right: 1px solid rgba(0,0,0,0.03);
        }
        .bg-col:last-child { border-right: none; }
        .bg-col-img {
            position: absolute;
            top: -10%; left: -10%;
            width: 120%; height: 120%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            animation: colCrossfade 24s infinite linear;
            filter: blur(3px) grayscale(20%);
        }

        @keyframes colCrossfade {
            0%   { opacity: 0; transform: scale(1); }
            8%   { opacity: 0.25; transform: scale(1.02); }
            25%  { opacity: 0.25; transform: scale(1.08); }
            33%  { opacity: 0; transform: scale(1.1); }
            100% { opacity: 0; transform: scale(1); }
        }

        @media (max-width: 768px) {
            .bg-four-cols { flex-direction: row; }
            .bg-col { border-right: 1px solid rgba(0,0,0,0.03); border-bottom: none; }
        }

        .sp-empty { text-align: center; padding: 80px 20px; }
        .sp-empty-icon { font-size: 4rem; margin-bottom: 16px; opacity: 0.8; }
        .sp-empty h2 { font-size: 1.6rem; font-weight: 900; margin-bottom: 8px; color: var(--text-color, #2d2a35); }
        .sp-empty p { color: #555; font-weight: 600; margin-bottom: 24px; }
        .sp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        
        @media (max-width: 768px) {
            .sp-wrap { padding: 30px 16px 80px; }
            .sp-hero { padding: 15px; margin-bottom: 40px; box-shadow: 8px 8px 0 var(--text-color, #2d2a35); transform: rotate(0deg); }
            .sp-hero-inner { padding: 25px 15px; transform: rotate(0deg); box-shadow: inset 0 0 0 3px #ffe6e6, 4px 4px 0 rgba(45, 42, 53, 0.1); }
            .sp-comic-badge { top: -20px; right: 10px; font-size: 0.9rem; padding: 6px 12px; transform: rotate(5deg); }
            .sp-title { font-size: 2rem; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; text-shadow: none; }
            .sp-title-icon-wrap { width: 45px; height: 45px; }
            .sp-title-icon-wrap i { font-size: 1.4rem; }
            .sp-sub { font-size: 0.95rem; padding: 8px 16px; margin-bottom: 15px; }
            .sp-pill { font-size: 0.85rem; padding: 4px 12px; }
        }
        @media (max-width: 600px) { .sp-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }
    </style>
    <?php include_once "gtag.php"; ?>
    <style>
        html, body { background: transparent !important; height: 100%; margin: 0; }
        body::before { content: ''; position: fixed; inset: 0; z-index: -2; background-image: url('backgroundwally/only-homepage-pic.webp'); background-size: cover; background-position: center top; background-repeat: no-repeat; }
        body::after { content: ''; position: fixed; inset: 0; z-index: -1; background: rgba(0,0,0,0.52); pointer-events: none; }
        @media (max-width: 640px) { body::before { background-image: url('backgroundwally/only-homepage-pic-for-mobile.webp'); background-position: center center; } }
        .aurora-bg { display: none !important; }
    </style>
</head>
<body>

<div class="bg-four-cols" aria-hidden="true">
    <div class="bg-col">
        <div class="bg-col-img" style="background-image:url('https://i1-e.pinimg.com/1200x/d1/23/59/d1235986fe6bfb8121fd0e534357a88c.jpg'); animation-delay: 0s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i.pinimg.com/736x/b0/e1/d5/b0e1d571157b14de3dd695368e1d8f6e.jpg'); animation-delay: 6s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i.pinimg.com/736x/90/03/87/9003879b29ad758a116371e53f4084a5.jpg'); animation-delay: 12s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i1-e.pinimg.com/736x/50/88/1f/50881f30c009e39875270aa8152db3e6.jpg'); animation-delay: 18s;"></div>
    </div>
    <div class="bg-col">
        <div class="bg-col-img" style="background-image:url('https://i.pinimg.com/736x/b0/e1/d5/b0e1d571157b14de3dd695368e1d8f6e.jpg'); animation-delay: -2s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i.pinimg.com/736x/90/03/87/9003879b29ad758a116371e53f4084a5.jpg'); animation-delay: 4s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i1-e.pinimg.com/736x/50/88/1f/50881f30c009e39875270aa8152db3e6.jpg'); animation-delay: 10s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i1-e.pinimg.com/1200x/d1/23/59/d1235986fe6bfb8121fd0e534357a88c.jpg'); animation-delay: 16s;"></div>
    </div>
    <div class="bg-col">
        <div class="bg-col-img" style="background-image:url('https://i.pinimg.com/736x/90/03/87/9003879b29ad758a116371e53f4084a5.jpg'); animation-delay: -4s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i1-e.pinimg.com/736x/50/88/1f/50881f30c009e39875270aa8152db3e6.jpg'); animation-delay: 2s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i1-e.pinimg.com/1200x/d1/23/59/d1235986fe6bfb8121fd0e534357a88c.jpg'); animation-delay: 8s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i.pinimg.com/736x/b0/e1/d5/b0e1d571157b14de3dd695368e1d8f6e.jpg'); animation-delay: 14s;"></div>
    </div>
    <div class="bg-col">
        <div class="bg-col-img" style="background-image:url('https://i1-e.pinimg.com/736x/50/88/1f/50881f30c009e39875270aa8152db3e6.jpg'); animation-delay: -6s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i1-e.pinimg.com/1200x/d1/23/59/d1235986fe6bfb8121fd0e534357a88c.jpg'); animation-delay: 0s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i.pinimg.com/736x/b0/e1/d5/b0e1d571157b14de3dd695368e1d8f6e.jpg'); animation-delay: 6s;"></div>
        <div class="bg-col-img" style="background-image:url('https://i.pinimg.com/736x/90/03/87/9003879b29ad758a116371e53f4084a5.jpg'); animation-delay: 12s;"></div>
    </div>
</div>

<header>
    <div class="logo-area" id="logo-container" style="cursor:pointer;">
        <div class="logo-flipper">
            <div class="logo-front">
                <img src="toplogo/logo01.webp" alt="Logo" id="profile-logo">
            </div>
            <div class="logo-back">
                <img loading="lazy" src="toplogo/logo02.webp" alt="">
            </div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="digital_store/index.php" class="shop-nav-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg> SHOP</a>
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
        <div class="sp-comic-badge">BAM!</div>
        <div class="sp-hero-inner">
            <div class="sp-title">
                <div class="sp-title-icon-wrap">
                    <i class="fa-solid fa-bookmark"></i>
                </div>
                Saved Prompts
            </div>
            <div class="sp-sub">All prompts you've unlocked — <span id="sp-counter"><?= $total ?></span> saved so far</div>
            <div class="sp-filters">
                <?php if($stats['secret'] > 0): ?><span class="sp-pill"><i class="fa-solid fa-lock"></i> Secret <?= $stats['secret'] ?></span><?php endif; ?>
                <?php if($stats['insta_viral'] > 0): ?><span class="sp-pill"><i class="fa-solid fa-fire"></i> Viral <?= $stats['insta_viral'] ?></span><?php endif; ?>
                <?php if($stats['unreleased'] > 0): ?><span class="sp-pill"><i class="fa-solid fa-moon"></i> Unreleased <?= $stats['unreleased'] ?></span><?php endif; ?>
                <?php if($stats['already_uploaded'] > 0): ?><span class="sp-pill"><i class="fa-solid fa-upload"></i> Uploaded <?= $stats['already_uploaded'] ?></span><?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($total === 0): ?>
    <div class="sp-empty">
        <div class="sp-empty-icon"><i class="fa-solid fa-bookmark"></i></div>
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
        <div class="card sp-card"
             data-id="<?= $p["id"] ?>"
             data-slug="<?= htmlspecialchars($p["slug"] ?? "") ?>"
             data-image="<?= htmlspecialchars($p["image_path"]) ?>"
             data-title="<?= htmlspecialchars($p["title"]) ?>"
             data-prompt-type="<?= htmlspecialchars($pt) ?>"
             data-unlocked="true"
             data-saved="true"
             data-prompt-text="<?= htmlspecialchars($p["prompt_text"]) ?>"
             data-tags="<?= htmlspecialchars(implode(",", $tags)) ?>"
             data-reel="">

            <img loading="lazy" src="<?= htmlspecialchars(
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
                <?= $tinfo["icon"] ?> <?= $tinfo["label"] ?>
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
            <img loading="lazy" id="modal-image" src="" alt="Prompt">
        </div>
        <div class="modal-right">
            <h2 id="modal-title">PROMPT</h2>
            <div id="modal-want-code" style="display:none;"><p>Need secret code?</p><a id="modal-reel-link" href="all_codes.php"><i class="fa-solid fa-code"></i> All Codes Here - Click to Know</a></div>
            <div id="modal-unlock-area" style="display:none;"></div>
            <div id="modal-unlocked-area" style="display:none;flex-direction:column;text-align:left;height:100%;">
                <h3 style="margin-bottom:10px;color:var(--text-color);font-size:1rem;"><i class="fa-solid fa-scroll"></i> THE PROMPT:</h3>
                <div class="unlocked-text" id="modal-unlocked-text" style="font-family:monospace;font-size:0.95rem;font-weight:500;background:var(--bg-color);padding:15px;border-radius:12px;border:var(--border-width) solid var(--text-color);flex-grow:1;margin-bottom:15px;overflow-y:auto;max-height:200px;white-space:pre-wrap;word-break:break-word;color:var(--text-color);box-shadow:var(--shadow-comic);"></div>
                <div style="display:flex;gap:10px;flex-wrap:nowrap;width:100%;">
                    <button class="copy-btn" id="modal-copy-btn" style="flex:1;padding:12px;background:var(--primary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);white-space:nowrap;"><i class="fa-solid fa-copy"></i> COPY</button>
                    <button id="modal-sp-remove-btn" data-prompt-id="" style="flex:1;padding:12px;background:#ffd6d6;color:#a01515;border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);white-space:nowrap;"><i class="fa-solid fa-trash-can"></i> REMOVE</button>
                    <?php if (isset($_SESSION["user_id"])): ?>
                    <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="" style="flex-shrink:0;min-width:70px;padding:12px 0;background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:12px;cursor:pointer;box-shadow:var(--shadow-comic);transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="fa-solid fa-heart" style="font-size:1.1rem;color:#FF4444;"></i>
                        <span id="modal-like-count" style="font-weight:900;color:#FF4444;font-size:0.95rem;">0</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- Remove-Saved Confirm Popup -->
<div id="sp-confirm-remove" style="display:none;position:fixed;inset:0;background:rgba(45,42,53,.5);backdrop-filter:blur(8px);z-index:3500;align-items:center;justify-content:center;">
    <div style="background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:32px 28px;max-width:400px;width:90%;box-shadow:8px 8px 0 var(--text-color);text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:12px;color:#dc2743;"><i class="fa-solid fa-trash-can"></i></div>
        <h3 style="font-size:1.3rem;font-weight:900;margin-bottom:10px;">Remove this prompt?</h3>
        <p style="font-weight:600;color:#555;margin-bottom:24px;">This will remove it from your saved list. You can save it again later.</p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <button id="sp-cancel-btn" style="flex:1;padding:14px;background:var(--bg-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);">Cancel</button>
            <button id="sp-confirm-btn" style="flex:1;padding:14px;background:#ffd6d6;border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);color:#a01515;">Yes, Remove</button>
        </div>
    </div>
</div>

<script>const isLoggedIn = <?= isset($_SESSION["user_id"])
    ? "true"
    : "false" ?>;</script>
<script defer src="script.js?v=20260702"></script>
<script>
(function () {
    const popup = document.getElementById('sp-confirm-remove');
    const cancelBtn = document.getElementById('sp-cancel-btn');
    const confirmBtn = document.getElementById('sp-confirm-btn');
    const grid = document.querySelector('.sp-grid');
    const wrap = document.querySelector('.sp-wrap');
    const subEl = document.getElementById('sp-counter');

    let pendingPromptId = null;
    let pendingCard = null;

    function closePopup() {
        popup.style.display = 'none';
        pendingPromptId = null;
        pendingCard = null;
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Yes, Remove';
    }

    function updateCounter() {
        if (!subEl) return;
        const remaining = grid ? grid.querySelectorAll('.sp-card').length : 0;
        subEl.textContent = remaining;
        if (remaining === 0 && wrap) {
            // Render empty state inline
            const emptyHtml = '<div class="sp-empty">'
                + '<div class="sp-empty-icon">\ud83d\udd16</div>'
                + '<h2>No Saved Prompts Yet</h2>'
                + '<p>Unlock prompts on the site and save them \u2014 they\'ll appear here!</p>'
                + '<a href="index.php" class="comic-btn-small"><i class="fa-solid fa-arrow-left"></i> Browse Prompts</a>'
                + '</div>';
            if (grid) grid.remove();
            const container = document.createElement('div');
            container.innerHTML = emptyHtml;
            wrap.appendChild(container.firstElementChild);
        }
    }

    // Long-press logic for Mobile (Unsave)
    let pressTimer;
    document.querySelectorAll('.sp-card').forEach(function(card) {
        card.addEventListener('touchstart', function(e) {
            // Don't trigger if they are clicking a button like the like button inside the card (if any)
            if (e.target.closest('button') || e.target.closest('a')) return;
            
            pressTimer = setTimeout(function() {
                pendingPromptId = card.dataset.id;
                pendingCard = card;
                if (navigator.vibrate) navigator.vibrate(50);
                popup.style.display = 'flex';
            }, 600); // 600ms hold
        }, {passive: true});

        card.addEventListener('touchend', function(e) {
            clearTimeout(pressTimer);
        });
        card.addEventListener('touchmove', function(e) {
            clearTimeout(pressTimer);
        });
        
        // Disable context menu on long press on mobile to prevent default image save popup
        card.addEventListener('contextmenu', function(e) {
            if (window.innerWidth <= 900) {
                e.preventDefault();
            }
        });
    });

    // Sync modal remove button promptId when any sp-card is clicked
    document.querySelectorAll('.sp-card').forEach(function(card) {
        card.addEventListener('click', function() {
            const modalRemoveBtn = document.getElementById('modal-sp-remove-btn');
            if (modalRemoveBtn) modalRemoveBtn.dataset.promptId = this.dataset.id;
        });
    });

    // Modal Remove button
    const modalRemoveBtn = document.getElementById('modal-sp-remove-btn');
    if (modalRemoveBtn) {
        modalRemoveBtn.addEventListener('click', function() {
            const promptId = this.dataset.promptId;
            if (!promptId) return;
            pendingPromptId = promptId;
            pendingCard = document.querySelector('.sp-card[data-id="' + promptId + '"]');
            // Close the modal first
            const modal = document.getElementById('unlock-modal');
            if (modal) modal.style.display = 'none';
            popup.style.display = 'flex';
        });
    }

    if (cancelBtn) cancelBtn.addEventListener('click', closePopup);
    popup.addEventListener('click', function (e) {
        if (e.target === popup) closePopup();
    });

    if (confirmBtn) confirmBtn.addEventListener('click', function () {
        if (!pendingPromptId || !pendingCard) { closePopup(); return; }
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Removing...';

        const fd = new FormData();
        fd.append('action', 'unsave');
        fd.append('prompt_id', pendingPromptId);

        fetch('save_prompt.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.saved === false) {
                    const card = pendingCard;
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(function () {
                        card.remove();
                        updateCounter();
                    }, 300);
                    closePopup();
                } else {
                    if (typeof showComicAlert === 'function') {
                        showComicAlert(data.message || 'Could not remove. Try again.', 'error');
                    } else {
                        alert(data.message || 'Could not remove. Try again.');
                    }
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Yes, Remove';
                }
            })
            .catch(function () {
                if (typeof showComicAlert === 'function') {
                    showComicAlert('Network error. Try again.', 'error');
                } else {
                    alert('Network error. Try again.');
                }
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Yes, Remove';
            });
    });
})();

// Card click → navigate to prompt page
document.querySelectorAll('.card').forEach(function(card) {
    var trigger = card.querySelector('.card-click-trigger');
    if (trigger) {
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            var url = card.dataset.slug ? ('/prompts/' + card.dataset.slug) : ('prompt.php?id=' + card.dataset.id);
            document.body.style.transition = 'opacity 0.15s ease';
            document.body.style.opacity = '0';
            setTimeout(function() { window.location.href = url; }, 150);
        });
    }
    card.addEventListener('mouseenter', function() {
        var url = card.dataset.slug ? ('/prompts/' + card.dataset.slug) : ('prompt.php?id=' + card.dataset.id);
        if (!document.querySelector('link[rel="prefetch"][href="' + url + '"]')) {
            var link = document.createElement('link');
            link.rel = 'prefetch'; link.href = url;
            document.head.appendChild(link);
        }
    }, { once: true });
});
</script>
</body>
</html>
