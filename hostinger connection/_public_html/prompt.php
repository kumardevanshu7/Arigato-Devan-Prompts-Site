<?php
session_start();
require_once "db.php";

$id   = (int)($_GET['id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');
if ($id <= 0 && empty($slug)) { header("Location: gallery.php"); exit(); }

$where        = $slug ? "p.slug = ?"  : "p.id = ?";
$where_plain  = $slug ? "slug = ?"   : "id = ?";
$where_val    = $slug ? $slug         : $id;

if (isset($_SESSION["user_id"])) {
    $stmt = $pdo->prepare("
        SELECT p.*,
               IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
               IF(l.id IS NOT NULL, 1, 0) as is_liked,
               IF(sv.id IS NOT NULL, 1, 0) as is_saved
        FROM prompts p
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
        LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
        LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
        WHERE {$where}
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"], $where_val]);
} else {
    $stmt = $pdo->prepare("SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE {$where_plain}");
    $stmt->execute([$where_val]);
}

$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { header("Location: gallery.php"); exit(); }
$id = (int)$p['id'];

$db_type  = $p["prompt_type"] ?? "secret";
$ptype    = match($db_type) {
    "insta_viral"     => "insta_viral",
    "unreleased"      => "unreleased",
    "already_uploaded"=> "already_uploaded",
    default           => "secret_code"
};
$tinfo = [
    "secret_code"      => ["label" => "SECRET CODE",       "bg" => "#e6d7ff", "color" => "#4a00b0"],
    "unreleased"       => ["label" => "UNRELEASED",         "bg" => "#fff1b8", "color" => "#7a5c00"],
    "insta_viral"      => ["label" => "INSTA VIRAL",        "bg" => "#c8f5d4", "color" => "#1a5c30"],
    "already_uploaded" => ["label" => "ALREADY UPLOADED",   "bg" => "#e6f2ff", "color" => "#00509e"],
][$ptype];

$rel_stmt = $pdo->prepare("SELECT id, slug, title, image_path FROM prompts WHERE prompt_type = ? AND id != ? AND is_trial = 0 ORDER BY RAND() LIMIT 4");
$rel_stmt->execute([$db_type, $id]);
$related = $rel_stmt->fetchAll(PDO::FETCH_ASSOC);

$is_unlocked  = (bool)$p["is_unlocked"];
// Track view
$pdo->prepare("UPDATE prompts SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
$asset_images = json_decode($p['asset_images'] ?? '[]', true) ?: [];
$tags_arr          = array_filter(array_map('trim', explode(',', $p['tag'] ?? '')));
$extra_prompts_arr = json_decode($p['extra_prompts'] ?? '[]', true) ?: [];
$total_prompts     = 1 + count($extra_prompts_arr);
$og_img       = "https://arigatodevan.com/" . ltrim($p["image_path"] ?? "landingpics/lan9.webp", "/");
$page_title   = htmlspecialchars($p["title"]) . " � AI Prompt | Arigato Devan";
$canonical    = !empty($p['slug']) ? "https://arigatodevan.com/prompts/" . $p['slug'] : "https://arigatodevan.com/prompt.php?id={$id}";
$tags_str     = !empty($tags_arr) ? implode(', ', array_slice($tags_arr, 0, 3)) : '';
$meta_desc    = !empty($p['description'])
              ? htmlspecialchars($p['description'])
              : htmlspecialchars($p['title']) . ' is a ' . $tinfo['label'] . ' AI couple prompt on Arigato Devan.'
                . (!empty($tags_str) ? ' Perfect for ' . $tags_str . '.' : '')
                . ' Copy and use instantly on ChatGPT or any AI tool.';
$type_page    = match($ptype) {
    'insta_viral'      => 'insta_viral.php',
    'unreleased'       => 'unreleased.php',
    'already_uploaded' => 'already_uploaded.php',
    default            => 'gallery.php',
};

function sessionAvatar() {
    return !empty($_SESSION["profile_image"])
        ? $_SESSION["profile_image"]
        : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($_SESSION["username"] ?? "user");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= ($_SERVER['HTTP_HOST'] === 'localhost') ? '/Arigato%20Development%20Site/' : '/' ?>">
    <title><?= $page_title ?></title>
    <meta name="description" content="<?= $meta_desc ?>">
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= htmlspecialchars($p['title']) ?> � Arigato Devan">
    <meta property="og:description" content="<?= $meta_desc ?>">
    <meta property="og:image" content="<?= $og_img ?>">
    <link rel="canonical" href="<?= $canonical ?>">
    <meta property="og:url" content="<?= $canonical ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= $og_img ?>">
    <script type="application/ld+json">
    <?= json_encode([
        '@context'  => 'https://schema.org',
        '@type'     => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',              'item' => 'https://arigatodevan.com'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $tinfo['label'],     'item' => 'https://arigatodevan.com/' . $type_page],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $p['title'],         'item' => $canonical],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <script type="application/ld+json">
    <?= json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'CreativeWork',
        'name'            => $p['title'],
        'description'     => $meta_desc,
        'url'             => $canonical,
        'image'           => $og_img,
        'author'          => ['@type' => 'Organization', 'name' => 'Arigato Devan'],
        'publisher'       => ['@type' => 'Organization', 'name' => 'Arigato Devan', 'url' => 'https://arigatodevan.com'],
        'keywords'        => implode(', ', $tags_arr),
        'genre'           => $tinfo['label'],
        'datePublished'   => isset($p['created_at']) ? date('c', strtotime($p['created_at'])) : null,
        'inLanguage'      => 'en',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <link rel="stylesheet" href="style.min.css?v=20260601">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <style>
        .pp-wrap { max-width: 1100px; margin: 0 auto; padding: 20px 20px 80px; }
        .pp-back { margin-bottom: 20px; }
        .pp-back a { font-weight: 800; color: var(--text-color); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.95rem; padding: 8px 16px; background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 20px; box-shadow: 2px 2px 0 var(--text-color); transition: all .15s; }
        .pp-back a:hover { box-shadow: 4px 4px 0 var(--text-color); transform: translateY(-1px); }
        .pp-layout { display: flex; gap: 32px; align-items: flex-start; }
        .pp-img-col { width: 300px; flex-shrink: 0; position: sticky; top: 100px; }
        .pp-prompt-img { width: 100%; aspect-ratio: 9/16; object-fit: cover; border-radius: 20px; border: var(--border-width) solid var(--text-color); box-shadow: var(--shadow-comic); display: block; }
        .pp-img-col.blurred .pp-prompt-img { filter: blur(8px); transform: scale(1.04); }
        .pp-badge { display: inline-flex; align-items: center; font-size: 0.75rem; font-weight: 900; padding: 6px 14px; border-radius: 20px; border: 2px solid var(--text-color); box-shadow: 2px 2px 0 var(--text-color); margin-bottom: 14px; text-transform: uppercase; letter-spacing: .05em; }
        .pp-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 14px; }
        .pp-tag { font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; background: var(--bg-color); border: 2px solid var(--text-color); text-transform: capitalize; }
        .pp-like-mini { display: flex; align-items: center; gap: 6px; margin-top: 12px; font-weight: 800; font-size: 0.9rem; color: #888; }
        .pp-multi-badge { display:inline-flex; align-items:center; gap:8px; background:#fff1b8; color:#7a5c00; border:2.5px solid #e6a800; border-radius:14px; padding:8px 18px; font-weight:900; font-size:.88rem; margin-bottom:14px; box-shadow:3px 3px 0 #e6a800; letter-spacing:.03em; }
        .pp-extra-section { margin-top:32px; border-top:2px dashed var(--border-color); padding-top:28px; }
        .pp-extra-num { font-size:.75rem; font-weight:900; text-transform:uppercase; letter-spacing:.12em; color:#888; margin-bottom:18px; display:flex; align-items:center; gap:8px; }
        .pp-extra-num::after { content:''; flex:1; height:2px; background:var(--border-color); }
        .pp-extra-layout { display:flex; gap:32px; align-items:flex-start; }
        .pp-extra-img-col { width:300px; flex-shrink:0; }
        .pp-extra-img { width:100%; aspect-ratio:9/16; object-fit:cover; border-radius:20px; border:var(--border-width) solid var(--text-color); box-shadow:var(--shadow-comic); display:block; }
        .pp-extra-info { flex:1; min-width:0; }
        .pp-extra-title { font-size:clamp(1.2rem,3vw,1.6rem); font-weight:900; margin-bottom:16px; line-height:1.2; }
        @media(max-width:700px){.pp-extra-layout{flex-direction:column;} .pp-extra-img-col{width:100%;max-width:300px;}}
        .pp-info-col { flex: 1; min-width: 0; }
        .pp-title { font-size: clamp(1.4rem, 4vw, 2rem); font-weight: 900; margin-bottom: 20px; line-height: 1.2; }
        /* -- Task Card -- */
        .pp-task-card { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 24px; padding: 32px 28px; box-shadow: var(--shadow-comic); text-align: center; }
        .pp-task-icon { font-size: 3rem; margin-bottom: 12px; }
        .pp-task-card h3 { font-size: 1.5rem; font-weight: 900; margin-bottom: 10px; }
        .pp-task-card p { font-weight: 600; color: #555; margin-bottom: 20px; font-size: 0.95rem; line-height: 1.5; }
        .pp-reel-btn { display: inline-flex; align-items: center; gap: 8px; background: var(--secondary-color); border: var(--border-width) solid var(--text-color); border-radius: 12px; padding: 10px 20px; font-weight: 800; font-family: var(--font-main); cursor: pointer; box-shadow: var(--shadow-comic); text-decoration: none; color: var(--text-color); margin-bottom: 20px; transition: all .15s; }
        .pp-reel-btn:hover { box-shadow: var(--shadow-comic-hover); transform: translateY(-2px); }
        .pp-input-group { display: flex; flex-direction: column; gap: 12px; max-width: 340px; margin: 0 auto; }
        .pp-input-group input { padding: 14px 18px; border: var(--border-width) solid var(--text-color); border-radius: 14px; font-family: var(--font-main); font-size: 1rem; font-weight: 700; background: var(--card-bg); box-shadow: var(--shadow-comic); text-align: center; text-transform: uppercase; outline: none; }
        .pp-input-group input:focus { border-color: var(--primary-dark); }
        .pp-unlock-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 24px; background: var(--primary-color); border: var(--border-width) solid var(--text-color); border-radius: 14px; font-weight: 900; font-size: 1rem; font-family: var(--font-main); cursor: pointer; box-shadow: var(--shadow-comic); transition: all .15s; color: var(--text-color); width: 100%; }
        .pp-unlock-btn:hover { box-shadow: var(--shadow-comic-hover); transform: translateY(-2px); }
        .pp-unlock-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .pp-unlock-big { background: var(--secondary-color); font-size: 1.1rem; padding: 18px 32px; margin-top: 8px; }
        /* -- Love Tap -- */
        .pp-love-area { display: flex; flex-direction: column; align-items: center; gap: 12px; margin: 8px 0 20px; }
        .pp-love-btn { font-size: 3.5rem; background: none; border: none; cursor: pointer; transition: transform .1s; line-height: 1; padding: 0; color: #e11d48; }
        .pp-love-btn:active { transform: scale(0.85); }
        .pp-love-btn:hover { color: #be123c; }
        .pp-love-progress { font-size: 1.4rem; font-weight: 900; }
        .pp-progress-bar { width: 200px; height: 10px; background: #eee; border-radius: 20px; border: 2px solid var(--text-color); overflow: hidden; }
        .pp-progress-fill { height: 100%; background: var(--primary-dark); border-radius: 20px; transition: width .2s; }
        /* -- Math -- */
        .pp-math-q { font-size: 1.8rem; font-weight: 900; background: var(--secondary-color); border: var(--border-width) solid var(--text-color); border-radius: 14px; padding: 16px 24px; margin: 0 auto 20px; display: inline-block; box-shadow: var(--shadow-comic); }
        /* -- Error -- */
        .pp-error { background: #fff0f0; border: 2px solid #ff6b6b; color: #c00; border-radius: 12px; padding: 10px 16px; font-weight: 700; margin-top: 14px; font-size: 0.9rem; }
        /* -- Content Section -- */
        .pp-content-section { display: flex; flex-direction: column; gap: 16px; }
        .pp-prompt-label { font-weight: 900; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .pp-bwi-badge { display: inline-flex; align-items: center; gap: 6px; border: 2px solid var(--text-color); border-radius: 20px; padding: 4px 14px; font-size: .78rem; font-weight: 900; box-shadow: 2px 2px 0 var(--text-color); }
        .pp-code-block { border: var(--border-width) solid var(--text-color); border-radius: 14px; overflow: hidden; box-shadow: var(--shadow-comic); }
        .pp-code-header { background: var(--text-color); color: #fff; padding: 8px 14px; font-size: 0.75rem; font-weight: 800; font-family: var(--font-main); display: flex; align-items: center; justify-content: space-between; letter-spacing: .08em; }
        .pp-code-header-dots { display: flex; gap: 5px; }
        .pp-code-header-dots span { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .pp-prompt-text { font-family: monospace; font-size: 0.9rem; font-weight: 500; background: #1e1e2e; color: #cdd6f4; padding: 16px; white-space: pre-wrap; word-break: break-word; line-height: 1.7; max-height: 220px; overflow-y: auto; display: block; }
        .pp-prompt-text::-webkit-scrollbar { width: 6px; }
        .pp-prompt-text::-webkit-scrollbar-track { background: #2a2a3e; }
        .pp-prompt-text::-webkit-scrollbar-thumb { background: var(--primary-dark); border-radius: 4px; }
        .pp-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .pp-btn { flex: 1; min-width: 110px; padding: 12px; border: var(--border-width) solid var(--text-color); border-radius: 12px; font-weight: 800; font-family: var(--font-main); cursor: pointer; box-shadow: var(--shadow-comic); transition: all .15s; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.85rem; text-transform: uppercase; }
        .pp-btn:hover { box-shadow: var(--shadow-comic-hover); transform: translateY(-1px); }
        .pp-copy-btn { background: var(--primary-color); color: var(--text-color); }
        .pp-save-btn { background: var(--secondary-color); color: var(--text-color); }
        .pp-like-btn { display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 12px; font-weight: 800; font-family: var(--font-main); font-size: 0.85rem; cursor: pointer; box-shadow: var(--shadow-comic); transition: all .15s; color: var(--text-color); }
        .pp-like-btn:hover { box-shadow: var(--shadow-comic-hover); transform: translateY(-1px); }
        .pp-like-btn .liked-heart { color: #ff6b6b; }
        .pp-like-btn.is-liked { background: rgba(255,107,107,.15); border-color: #ff6b6b; }
        /* -- Assets -- */
        .pp-assets { border-top: var(--border-width) solid var(--text-color); padding-top: 16px; margin-top: 4px; }
        .pp-assets-title { font-weight: 900; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
        .pp-assets-grid { display: flex; gap: 10px; flex-wrap: wrap; }
        .pp-assets-grid img { width: 100px; aspect-ratio: 3/4; object-fit: cover; border-radius: 12px; border: var(--border-width) solid var(--text-color); }
        /* -- Related -- */
        .pp-related { margin-top: 48px; }
        .pp-related h2 { font-size: 1.4rem; font-weight: 900; margin-bottom: 20px; }
        .pp-rel-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .pp-rel-card { display: block; text-decoration: none; color: inherit; border-radius: 16px; overflow: hidden; border: var(--border-width) solid var(--text-color); box-shadow: var(--shadow-comic); transition: all .2s; background: var(--card-bg); }
        .pp-rel-card:hover { box-shadow: var(--shadow-comic-hover); transform: translateY(-3px); }
        .pp-rel-card img { width: 100%; aspect-ratio: 9/16; object-fit: cover; display: block; }
        .pp-rel-card-title { padding: 10px 12px; font-weight: 800; font-size: 0.8rem; line-height: 1.3; }
        /* -- Mobile -- */
        @media (max-width: 768px) {
            .pp-layout { flex-direction: column; }
            .pp-img-col { width: 100%; position: static; max-width: 320px; margin: 0 auto; }
            .pp-rel-grid { grid-template-columns: repeat(2, 1fr); }
            .pp-task-card { padding: 24px 18px; }
            .pp-math-q { font-size: 1.4rem; }
        }
        @media (max-width: 480px) {
            .pp-wrap { padding: 16px 14px 60px; }
            .pp-rel-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        }
    </style>
    <?php include_once "gtag.php"; ?>
</head>
<body>
    <header>
        <div class="logo-area" id="logo-container" style="cursor:pointer;" onclick="window.location='index.php'">
            <div class="logo-flipper">
                <div class="logo-front"><img src="toplogo/logo01.webp" alt="Arigato Devan Logo" id="profile-logo"></div>
                <div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt="Logo Alt"></div>
            </div>
            <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
        </div>
        <nav class="nav-links">
            <a href="index.php">HOME</a>
            <a href="gallery.php" class="active">GALLERY</a>
            <a href="blogs.php">BLOGS</a>
        </nav>
        <div class="header-right">
            <div class="header-divider"></div>
            <?php if (isset($_SESSION["user_id"])): ?>
                <?php if ($_SESSION["role"] === "admin"): ?>
                    <a href="dashboard.php" style="color:var(--text-color);font-weight:800;">ADMIN</a>
                <?php endif; ?>
                <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
            <?php else: ?>
                <a href="login.php" class="comic-btn" style="display:inline-flex;align-items:center;font-size:0.85rem;padding:10px 18px;background:#fff;text-decoration:none;color:#000;">
                    <i class="fa-brands fa-google" style="font-size:18px;"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="pp-wrap">
        <!-- Back -->
        <div class="pp-back">
            <a href="gallery.php"><i class="fa-solid fa-arrow-left"></i> Back to Gallery</a>
        </div>

        <div class="pp-layout">
            <!-- Image Column -->
            <div class="pp-img-col <?= ($ptype === 'unreleased' && !$is_unlocked) ? 'blurred' : '' ?>" id="pp-img-col">
                <div class="pp-badge" style="background:<?= $tinfo['bg'] ?>;color:<?= $tinfo['color'] ?>;">
                    <?= $tinfo['label'] ?>
                </div>
                <img loading="lazy" src="<?= htmlspecialchars($p['image_path']) ?>" class="pp-prompt-img" id="pp-main-img" alt="<?= htmlspecialchars($p['title']) ?>">
                <div class="pp-like-mini">
                    <i class="fa-solid fa-heart" style="color:#ff6b6b;"></i>
                    <span id="pp-like-count-mini"><?= (int)$p['likes_count'] ?></span> likes
                </div>
                <?php if (!empty($tags_arr)): ?>
                <div class="pp-tags">
                    <?php foreach ($tags_arr as $t): ?>
                        <span class="pp-tag"><?= htmlspecialchars(ucfirst($t)) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info Column -->
            <div class="pp-info-col">
                <?php if ($total_prompts > 1): ?>
                <div class="pp-multi-badge"><i class="fa-solid fa-layer-group"></i> <?= $total_prompts ?> Prompts Inside!</div>
                <?php endif; ?>
                <h1 class="pp-title"><?= htmlspecialchars($p['title']) ?></h1>

                <!-- -- TASK SECTION (shown when locked) -- -->
                <?php if (!$is_unlocked): ?>
                <div id="pp-task" class="pp-task-card">
                    <?php if ($ptype === 'secret_code'): ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-lock"></i></div>
                        <h3>Enter Secret Code</h3>
                        <p>Watch our Instagram Reel to get the 6-letter secret code, then enter it below to reveal this prompt.</p>
                        <?php if (!empty($p['reel_link'])): ?>
                        <a href="<?= htmlspecialchars($p['reel_link']) ?>" target="_blank" class="pp-reel-btn">
                            <i class="fa-solid fa-play"></i> Watch Reel to Get Code
                        </a>
                        <?php endif; ?>
                        <div class="pp-input-group">
                            <input type="text" id="pp-code-input" placeholder="6-LETTER CODE" maxlength="6" autocomplete="off" style="letter-spacing:.2em;">
                            <button id="pp-submit-code" class="pp-unlock-btn"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt</button>
                        </div>

                    <?php elseif ($ptype === 'unreleased'): ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-heart"></i></div>
                        <h3>Show Some Love!</h3>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <p>Tap the heart <strong>20 times</strong> to unlock this prompt.</p>
                        <?php else: ?>
                        <p>Tap the heart <strong>90 times</strong> to unlock � or <a href="login.php" style="font-weight:900;color:var(--primary-dark);">login</a> for just 20 taps!</p>
                        <?php endif; ?>
                        <div class="pp-love-area">
                            <button id="pp-love-btn" class="pp-love-btn"><i class="fa-solid fa-heart"></i></button>
                            <div class="pp-progress-bar"><div class="pp-progress-fill" id="pp-progress-fill" style="width:0%"></div></div>
                            <div class="pp-love-progress"><span id="pp-tap-count">0</span> / <span id="pp-tap-total"><?= isset($_SESSION['user_id']) ? 20 : 90 ?></span></div>
                        </div>

                    <?php elseif ($ptype === 'insta_viral'): ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-calculator"></i></div>
                        <h3>Quick Math Challenge</h3>
                        <p>Solve this to prove you're human and unlock the prompt!</p>
                        <div class="pp-math-q" id="pp-math-q">Loading...</div>
                        <div class="pp-input-group">
                            <input type="number" id="pp-math-input" placeholder="Your Answer" style="font-size:1.2rem;">
                            <button id="pp-submit-math" class="pp-unlock-btn"><i class="fa-solid fa-check"></i> Unlock Prompt</button>
                        </div>

                    <?php elseif ($ptype === 'already_uploaded'): ?>
                        <div class="pp-task-icon"><i class="fa-brands fa-instagram" style="background:linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i></div>
                        <h3>Already on Instagram!</h3>
                        <p>This prompt has been shared on our Instagram. Tap the heart <strong>9 times</strong> to unlock it!</p>
                        <div class="pp-love-area">
                            <button id="pp-love-btn-au" class="pp-love-btn"><i class="fa-solid fa-heart"></i></button>
                            <div class="pp-progress-bar"><div class="pp-progress-fill" id="pp-progress-fill-au" style="width:0%"></div></div>
                            <div class="pp-love-progress"><span id="pp-tap-count-au">0</span> / 9</div>
                        </div>
                    <?php endif; ?>

                    <div id="pp-task-error" class="pp-error" style="display:none;"></div>
                </div>
                <?php endif; ?>

                <!-- -- CONTENT SECTION (shown when unlocked) -- -->
                <div id="pp-content" class="pp-content-section" <?= !$is_unlocked ? 'style="display:none;"' : '' ?>>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <span class="pp-prompt-label"><i class="fa-solid fa-scroll"></i> THE PROMPT:</span>
                        <?php if (!empty($p['best_works_in'])): ?>
                        <span class="pp-bwi-badge" style="background:<?= $p['best_works_in'] === 'nano_banana' ? '#ffe066' : '#10a37f' ?>;color:<?= $p['best_works_in'] === 'nano_banana' ? '#2d2a35' : '#fff' ?>;">
                            <?= $p['best_works_in'] === 'nano_banana' ? '<i class="fa-solid fa-star"></i> Best in Nano Banana' : '<i class="fa-solid fa-robot"></i> Best in ChatGPT' ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="pp-code-block">
                        <div class="pp-code-header">
                            <div class="pp-code-header-dots"><span style="background:#ff5f57"></span><span style="background:#febc2e"></span><span style="background:#28c840"></span></div>
                            <span>PROMPT.txt</span>
                            <span style="opacity:.6;font-size:.7rem;"><?= $is_unlocked ? strlen($p['prompt_text']) : 0 ?> chars</span>
                        </div>
                        <div class="pp-prompt-text" id="pp-prompt-text"><?= $is_unlocked ? htmlspecialchars($p['prompt_text']) : '' ?></div>
                    </div>

                    <div class="pp-actions">
                        <button class="pp-btn pp-copy-btn" id="pp-copy-btn"><i class="fa-solid fa-copy"></i> COPY</button>
                        <button class="pp-btn pp-save-btn" id="pp-save-btn" data-prompt-id="<?= $id ?>" data-saved="<?= $p['is_saved'] ? 'true' : 'false' ?>">
                            <i class="fa-solid fa-bookmark"></i> <span id="pp-save-label"><?= $p['is_saved'] ? 'SAVED' : 'SAVE' ?></span>
                        </button>
                        <button class="pp-like-btn <?= $p['is_liked'] ? 'is-liked' : '' ?>" id="pp-like-btn" data-prompt-id="<?= $id ?>">
                            <i class="fa-solid fa-heart <?= $p['is_liked'] ? 'liked-heart' : '' ?>" id="pp-like-icon"></i>
                            <span id="pp-like-count"><?= (int)$p['likes_count'] ?></span>
                        </button>
                        <button class="pp-btn" id="pp-share-btn" style="background:var(--card-bg);flex:0;padding:12px 16px;"><i class="fa-solid fa-share-nodes"></i> SHARE</button>
                    </div>

                    <?php if (!empty($asset_images) || !empty($p['asset_title'])): ?>
                    <div class="pp-assets">
                        <div class="pp-assets-title"><i class="fa-solid fa-paperclip"></i> <?= htmlspecialchars($p['asset_title'] ?? 'Assets') ?></div>
                        <div class="pp-assets-grid">
                            <?php foreach ($asset_images as $i => $ai): ?>
                            <div style="position:relative;display:inline-flex;flex-direction:column;gap:6px;">
                                <img loading="lazy" src="<?= htmlspecialchars($ai) ?>" alt="Asset <?= $i+1 ?>">
                                <a href="<?= htmlspecialchars($ai) ?>" download target="_blank" style="display:flex;align-items:center;justify-content:center;gap:5px;background:var(--secondary-color);border:2px solid var(--text-color);border-radius:8px;padding:5px 8px;font-size:0.72rem;font-weight:800;font-family:var(--font-main);text-decoration:none;color:var(--text-color);box-shadow:2px 2px 0 var(--text-color);transition:all .15s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''"><i class="fa-solid fa-download"></i> Download</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php foreach ($extra_prompts_arr as $ep_i => $ep): ?>
                    <div class="pp-extra-section" id="pp-extra-<?= $ep_i ?>">
                        <div class="pp-extra-num">? Prompt <?= $ep_i + 2 ?></div>
                        <div class="pp-extra-layout">
                            <?php if (!empty($ep['image_path'])): ?>
                            <div class="pp-extra-img-col">
                                <img loading="lazy" src="<?= htmlspecialchars($ep['image_path']) ?>" class="pp-extra-img" alt="Prompt <?= $ep_i + 2 ?>">
                            </div>
                            <?php endif; ?>
                            <div class="pp-extra-info">
                                <?php if (!empty($ep['title'])): ?>
                                <h2 class="pp-extra-title"><?= htmlspecialchars($ep['title']) ?></h2>
                                <?php endif; ?>
                                <div class="pp-code-block">
                                    <div class="pp-code-header">
                                        <div class="pp-code-header-dots"><span style="background:#ff5f57"></span><span style="background:#febc2e"></span><span style="background:#28c840"></span></div>
                                        <span>PROMPT <?= $ep_i + 2 ?>.txt</span>
                                    </div>
                                    <div class="pp-prompt-text" id="pp-extra-text-<?= $ep_i ?>"><?= $is_unlocked ? htmlspecialchars($ep['prompt_text']) : '' ?></div>
                                </div>
                                <div style="margin-top:12px;">
                                    <button class="pp-btn pp-copy-btn" onclick="copyExtra(<?= $ep_i ?>, this)"><i class="fa-solid fa-copy"></i> COPY</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Related Prompts -->
        <?php if (!empty($related)): ?>
        <div class="pp-related">
            <h2>More <?= htmlspecialchars($tinfo['label']) ?> Prompts</h2>
            <div class="pp-rel-grid">
                <?php foreach ($related as $r): ?>
                <a href="<?= !empty($r['slug']) ? '/prompts/' . htmlspecialchars($r['slug']) : 'prompt.php?id=' . $r['id'] ?>" class="pp-rel-card">
                    <img loading="lazy" src="<?= htmlspecialchars($r['image_path']) ?>" alt="<?= htmlspecialchars($r['title']) ?>" loading="lazy">
                    <div class="pp-rel-card-title"><?= htmlspecialchars($r['title']) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <div>&copy; 2026 ARIGATO DEVAN. KEEP CREATING.</div>
        <div class="footer-links"><a href="about.php">ABOUT</a><a href="contact.php">CONTACT</a><a href="faq.php">FAQ</a><a href="privacy.php">PRIVACY POLICY</a><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
    </footer>

    <script>
    const promptId = <?= $id ?>;
    const ptype    = '<?= $ptype ?>';

    // -- Error helper --
    function showError(msg) {
        const el = document.getElementById('pp-task-error');
        if (!el) return;
        el.textContent = msg;
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 4000);
    }

    // -- Reveal content after unlock --
    function revealPrompt(text, extraPrompts) {
        const task = document.getElementById('pp-task');
        const content = document.getElementById('pp-content');
        const imgCol = document.getElementById('pp-img-col');
        if (task) task.style.display = 'none';
        if (content) { content.style.display = 'flex'; document.getElementById('pp-prompt-text').textContent = text; }
        if (imgCol) imgCol.classList.remove('blurred');
        const mainImg = document.getElementById('pp-main-img');
        if (mainImg) mainImg.style.filter = '';
        if (extraPrompts && Array.isArray(extraPrompts)) {
            extraPrompts.forEach(function(ep, i) {
                const el = document.getElementById('pp-extra-text-' + i);
                if (el) el.textContent = ep.prompt_text || '';
            });
        }
        content.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function copyExtra(idx, btn) {
        const el = document.getElementById('pp-extra-text-' + idx);
        if (!el || !el.textContent.trim()) return;
        navigator.clipboard.writeText(el.textContent.trim()).then(function() {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
            setTimeout(() => btn.innerHTML = '<i class="fa-solid fa-copy"></i> COPY', 2000);
        });
    }

    // -- SECRET CODE --
    const submitCode = document.getElementById('pp-submit-code');
    if (submitCode) {
        submitCode.addEventListener('click', async function() {
            const code = document.getElementById('pp-code-input').value.trim();
            if (code.length < 4) { showError('Please enter the code!'); return; }
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
            const fd = new FormData();
            fd.append('action', 'verify'); fd.append('prompt_id', promptId); fd.append('code', code);
            const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
            else { showError(res.message || 'Wrong code! Watch the reel to get it.'); this.disabled = false; this.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt'; }
        });
    }

    // -- UNRELEASED (20 taps logged in / 90 taps guest) --
    const loveBtn = document.getElementById('pp-love-btn');
    if (loveBtn) {
        let tapCount = 0;
        const TAPS = (typeof isLoggedIn !== 'undefined' && isLoggedIn) ? 20 : 90;
        document.getElementById('pp-tap-total').textContent = TAPS;
        loveBtn.addEventListener('click', async function() {
            if (tapCount === 0) {
                const fd = new FormData(); fd.append('action', 'init_love'); fd.append('prompt_id', promptId);
                await fetch('unlock.php', { method: 'POST', body: fd });
            }
            tapCount++;
            document.getElementById('pp-tap-count').textContent = tapCount;
            document.getElementById('pp-progress-fill').style.width = (tapCount / TAPS * 100) + '%';
            this.style.transform = 'scale(1.35)';
            setTimeout(() => this.style.transform = '', 120);
            if (tapCount >= TAPS) {
                this.disabled = true;
                const fd = new FormData(); fd.append('action', 'unreleased'); fd.append('prompt_id', promptId);
                const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
                else { tapCount = 0; document.getElementById('pp-tap-count').textContent = '0'; document.getElementById('pp-progress-fill').style.width = '0%'; this.disabled = false; showError(res.message); }
            }
        });
    }

    // -- ALREADY UPLOADED (9 heart taps) --
    const loveBtnAu = document.getElementById('pp-love-btn-au');
    if (loveBtnAu) {
        let tapCountAu = 0;
        const TAPS_AU = 9;
        loveBtnAu.addEventListener('click', async function() {
            tapCountAu++;
            document.getElementById('pp-tap-count-au').textContent = tapCountAu;
            document.getElementById('pp-progress-fill-au').style.width = (tapCountAu / TAPS_AU * 100) + '%';
            this.style.transform = 'scale(1.35)';
            setTimeout(() => this.style.transform = '', 120);
            if (tapCountAu >= TAPS_AU) {
                this.disabled = true;
                const fd = new FormData(); fd.append('action', 'already_uploaded'); fd.append('prompt_id', promptId);
                const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
                else { tapCountAu = 0; document.getElementById('pp-tap-count-au').textContent = '0'; document.getElementById('pp-progress-fill-au').style.width = '0%'; this.disabled = false; showError(res.message); }
            }
        });
    }

    // -- INSTA VIRAL (math) --
    const mathQ = document.getElementById('pp-math-q');
    if (mathQ) {
        (async function initMath() {
            const fd = new FormData(); fd.append('action', 'get_challenge'); fd.append('prompt_id', promptId);
            const d = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
            mathQ.textContent = d.n1 + ' + ' + d.n2 + ' + ' + d.n3 + ' + ' + d.n4 + ' = ?';
        })();
        document.getElementById('pp-submit-math').addEventListener('click', async function() {
            const ans = document.getElementById('pp-math-input').value;
            if (!ans) { showError('Enter your answer!'); return; }
            this.disabled = true;
            const fd = new FormData(); fd.append('action', 'insta_viral'); fd.append('prompt_id', promptId); fd.append('user_answer', ans);
            const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
            else { showError(res.message || 'Wrong answer! Try again.'); this.disabled = false; mathQ.textContent = 'Loading...'; initMath(); }
        });
    }


    // -- COPY --
    const copyBtn = document.getElementById('pp-copy-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const text = document.getElementById('pp-prompt-text').textContent;
            navigator.clipboard.writeText(text).then(() => {
                this.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
                setTimeout(() => this.innerHTML = '<i class="fa-solid fa-copy"></i> COPY', 2000);
                const fd = new FormData(); fd.append('action','copy'); fd.append('prompt_id', promptId);
                fetch('track.php', {method:'POST', body:fd});
            });
        });
    }

    // -- SAVE --
    const saveBtn = document.getElementById('pp-save-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', async function() {
            <?php if (!isset($_SESSION['user_id'])): ?>
            window.location.href = 'login.php';
            return;
            <?php endif; ?>
            const isSaved = this.dataset.saved === 'true';
            const action  = isSaved ? 'unsave' : 'save';
            const fd = new FormData(); fd.append('prompt_id', promptId); fd.append('action', action);
            const res = await fetch('save_prompt.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                this.dataset.saved = res.saved ? 'true' : 'false';
                document.getElementById('pp-save-label').textContent = res.saved ? 'SAVED' : 'SAVE';
                this.style.background = res.saved ? 'var(--primary-color)' : 'var(--secondary-color)';
            }
        });
    }

    // -- SHARE --
    const shareBtn = document.getElementById('pp-share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', async function() {
            const url = window.location.href;
            const title = <?= json_encode($p['title']) ?>;
            if (navigator.share) {
                try { await navigator.share({ title: title + ' � Arigato Devan', url: url }); return; } catch(e) {}
            }
            navigator.clipboard.writeText(url).then(() => {
                this.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
                setTimeout(() => this.innerHTML = '<i class="fa-solid fa-share-nodes"></i> SHARE', 2000);
            });
        });
    }

    // -- LIKE --
    const likeBtn = document.getElementById('pp-like-btn');
    if (likeBtn) {
        likeBtn.addEventListener('click', async function() {
            <?php if (!isset($_SESSION['user_id'])): ?>
            window.location.href = 'login.php';
            return;
            <?php endif; ?>
            const fd = new FormData(); fd.append('prompt_id', promptId);
            const res = await fetch('like.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                const isLiked = res.action === 'liked';
                this.classList.toggle('is-liked', isLiked);
                document.getElementById('pp-like-icon').classList.toggle('liked-heart', isLiked);
                document.getElementById('pp-like-count').textContent = res.likes_count;
                document.getElementById('pp-like-count-mini').textContent = res.likes_count;
            }
        });
    }
    </script>
</body>
</html>
