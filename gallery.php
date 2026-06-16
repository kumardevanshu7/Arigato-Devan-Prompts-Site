<?php
session_start();
require_once "db.php";
// Guard: if logged in but onboarding not done, force setup
if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

// Pagination + tag filter
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 20;
$tag_filter = trim(strtolower($_GET['tag'] ?? ''));
$tag_param  = ($tag_filter && $tag_filter !== 'all') ? '%' . $tag_filter . '%' : null;
$_page_canonical = 'https://arigatodevan.com/gallery.php' . (($tag_filter && $tag_filter !== 'all') ? '?tag=' . urlencode($tag_filter) : '');
$offset     = ($page - 1) * $per_page;

// Count total for pagination
$count_sql  = $tag_param ? "SELECT COUNT(*) FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL) AND LOWER(tag) LIKE ?" : "SELECT COUNT(*) FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL)";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($tag_param ? [$tag_param] : []);
$total       = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

// All unique tags for filter buttons (separate query, not paginated)
$all_tags_raw = $pdo->query("SELECT tag FROM prompts WHERE tag IS NOT NULL AND tag != '' AND (is_trial = 0 OR is_trial IS NULL)") ->fetchAll(PDO::FETCH_COLUMN);
$all_tags = [];
foreach ($all_tags_raw as $ts) { foreach (explode(',', strtolower($ts)) as $t) { $t = trim($t); if ($t) $all_tags[] = $t; } }
$tag_counts = [];
foreach ($all_tags_raw as $ts) { foreach (explode(',', strtolower($ts)) as $t) { $t = trim($t); if ($t) $tag_counts[$t] = ($tag_counts[$t] ?? 0) + 1; } }
$all_tags = array_unique($all_tags); sort($all_tags);

// Fetch prompts with unlocked status (LIMIT/OFFSET interpolated as int — safe, no user input)
$tag_where = $tag_param ? " AND LOWER(tag) LIKE ?" : "";
if (isset($_SESSION["user_id"])) {
    $sql = "SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
               IF(l.id IS NOT NULL, 1, 0) as is_liked,
               IF(sv.id IS NOT NULL, 1, 0) as is_saved
        FROM prompts p
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
        LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
        LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
        WHERE (p.is_trial = 0 OR p.is_trial IS NULL){$tag_where}
        ORDER BY p.created_at DESC LIMIT {$per_page} OFFSET {$offset}";
    $params = [$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]];
    if ($tag_param) $params[] = $tag_param;
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sql = "SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL){$tag_where} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}";
    $params = []; if ($tag_param) $params[] = $tag_param;
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Avatar helper
function getAvatar($user)
{
    if (!empty($user["avatar"])) {
        return $user["avatar"];
    }
    return "https://api.dicebear.com/7.x/avataaars/svg?seed=" .
        urlencode($user["email"] ?? "user");
}
function sessionAvatar()
{
    return !empty($_SESSION["profile_image"])
        ? $_SESSION["profile_image"]
        : "https://api.dicebear.com/7.x/avataaars/svg?seed=" .
                urlencode($_SESSION["username"] ?? "user");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#c084fc">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery &mdash; Arigato Devan Prompts</title>
    <meta name="description" content="Browse all AI couple prompts in one place. Save, unlock &amp; share your favourites — only on Arigato Devan! ✨">

    <!-- Open Graph & Twitter Card -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="Gallery — All AI Couple Prompts | Arigato Devan">
    <meta property="og:description" content="Browse all AI couple prompts in one place. Save, unlock &amp; share your favourites — only on Arigato Devan! ✨">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/gallery.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Gallery — All AI Couple Prompts | Arigato Devan">
    <meta name="twitter:description" content="Browse all AI couple prompts in one place. Save, unlock &amp; share your favourites — only on Arigato Devan! ✨">
    <meta name="twitter:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <!-- Canonical -->
    <link rel="canonical" href="<?= htmlspecialchars($_page_canonical) ?>">
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="style.min.css?v=20260601">
        <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
        <link rel="preconnect" href="https://unpkg.com" crossorigin>

    <style>
        .tag-ctrl-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px;}
        .tag-ctrl-label{font-size:.82rem;font-weight:900;color:var(--text-color);letter-spacing:.5px;text-transform:uppercase;opacity:.7;}
        .tag-ctrl-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
        .tag-sort-grp{display:flex;border:2px solid var(--text-color);border-radius:14px;overflow:hidden;}
        .tag-sort-btn{padding:5px 12px;font-size:.72rem;font-weight:800;font-family:var(--font-main);background:var(--bg-color);color:var(--text-color);border:none;border-right:2px solid var(--text-color);cursor:pointer;transition:background .15s;white-space:nowrap;}
        .tag-sort-btn:last-child{border-right:none;}
        .tag-sort-btn.active{background:var(--primary-color);}
        .tag-sort-btn:hover:not(.active){background:var(--card-bg);}
        .tag-toggle-btn{display:flex;align-items:center;gap:6px;padding:5px 14px;font-size:.72rem;font-weight:800;font-family:var(--font-main);background:var(--card-bg);color:var(--text-color);border:2px solid var(--text-color);border-radius:14px;cursor:pointer;transition:all .15s;white-space:nowrap;box-shadow:2px 2px 0 var(--text-color);}
        .tag-toggle-btn:hover{background:var(--primary-color);}
        .tag-filter-container{transition:max-height .35s ease,opacity .3s ease,margin .3s ease;max-height:280px;opacity:1;overflow:hidden;margin-bottom:30px;}
        .tag-filter-container.tags-hidden{max-height:0!important;opacity:0;margin-bottom:0!important;pointer-events:none;}
        .tag-scroll-inner{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;padding:4px 2px 14px;max-height:220px;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin;scrollbar-color:var(--primary-color) transparent;}
        .tag-scroll-inner::-webkit-scrollbar{width:5px;}
        .tag-scroll-inner::-webkit-scrollbar-track{background:transparent;}
        .tag-scroll-inner::-webkit-scrollbar-thumb{background:var(--primary-color);border-radius:99px;}
        .gallery-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }
        .gallery-title { font-size: 2rem; font-weight: 900; }
        .gallery-count {
            background: var(--primary-color);
            border: var(--border-width) solid var(--text-color);
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 800;
            font-size: 0.9rem;
            box-shadow: 2px 2px 0px var(--text-color);
        }
        /* Search bar */
        .gallery-search-wrap {
            position: relative;
            margin-bottom: 22px;
        }
        .gallery-search-wrap .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-color);
            font-size: 1rem;
            opacity: 0.5;
            pointer-events: none;
        }
        #gallery-search {
            width: 100%;
            padding: 13px 18px 13px 46px;
            border: var(--border-width) solid var(--text-color);
            border-radius: 20px;
            font-family: var(--font-main);
            font-size: 0.95rem;
            font-weight: 700;
            background: var(--card-bg);
            color: var(--text-color);
            box-shadow: 4px 4px 0 var(--text-color);
            outline: none;
            transition: box-shadow 0.2s, transform 0.2s;
            box-sizing: border-box;
        }
        #gallery-search:focus {
            box-shadow: 5px 5px 0 var(--text-color);
            transform: translateY(-1px);
        }
        #gallery-search::placeholder { opacity: 0.55; font-weight: 600; }
        .search-clear-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-color);
            border: 2px solid var(--text-color);
            border-radius: 50%;
            width: 26px; height: 26px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: .7rem;
            font-weight: 900;
            color: var(--text-color);
            box-shadow: 2px 2px 0 var(--text-color);
        }
        .search-clear-btn.visible { display: flex; }
        .gallery-no-results {
            text-align: center;
            padding: 60px 20px;
            display: none;
        }
        .gallery-no-results.show { display: block; }
        .gallery-no-results .no-res-emoji { font-size: 3rem; margin-bottom: 12px; }
        .gallery-no-results h3 { font-size: 1.3rem; font-weight: 900; margin-bottom: 8px; }
        .gallery-no-results p { font-weight: 600; opacity: .65; }
        /* ─── Autocomplete dropdown ─── */
        .search-autocomplete{display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;background:var(--card-bg);border:var(--border-width,3px) solid var(--text-color);border-radius:18px;box-shadow:5px 5px 0 var(--text-color);overflow:hidden;z-index:500;font-family:var(--font-main);}
        .ac-item{display:flex;align-items:center;gap:12px;padding:11px 16px;cursor:pointer;border-bottom:1px solid var(--border-color,#eae3f2);transition:background .12s;}
        .ac-item:last-child{border-bottom:none;}
        .ac-item.active,.ac-item:hover{background:var(--bg-color);}
        .ac-badge{flex-shrink:0;font-size:.58rem;font-weight:900;padding:3px 8px;border-radius:20px;color:#fff;letter-spacing:.5px;text-transform:uppercase;}
        .ac-info{flex:1;min-width:0;}
        .ac-title{font-size:.88rem;font-weight:800;color:var(--text-color);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .ac-tags{font-size:.72rem;font-weight:600;color:#888;margin-top:2px;}
        .ac-arrow{font-size:.7rem;color:var(--text-color);opacity:.4;flex-shrink:0;}
        .ac-header{padding:8px 16px 6px;font-size:.65rem;font-weight:900;text-transform:uppercase;letter-spacing:1px;color:#aaa;border-bottom:1px solid var(--border-color,#eae3f2);}
    </style>
    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://arigatodevan.com"},{"@type":"ListItem","position":2,"name":"Gallery","item":"https://arigatodevan.com/gallery.php"}]}
    </script>
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-gallery">
    <header>
        <div class="logo-area" id="logo-container"  style="cursor:pointer;">
            <div class="logo-flipper">
                <div class="logo-front">
                    <img src="toplogo/logo01.webp" alt="Logo" id="profile-logo">
                </div>
                <div class="logo-back">
                    <img loading="lazy" src="toplogo/logo02.webp" alt="Logo Alt">
                </div>
            </div>
            <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
        </div>
        <nav class="nav-links">
            <a href="digital_store/index.php">SHOP</a>
            <a href="gallery.php" class="active">GALLERY</a>
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
                    : "" ?>><i class="bx bx-history"></i> Already Uploaded <?= empty(
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
                        <a href="profile.php" title="Edit Profile">
                            <?= renderAvatar(
                                sessionAvatar(),
                                "admin-avatar",
                                "Admin",
                                'style="transition:transform 0.2s;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"',
                            ) ?>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="profile.php" title="Edit Profile" style="color:var(--text-color);display:flex;align-items:center;gap:8px;">
                        <?= renderAvatar(
                            sessionAvatar(),
                            "admin-avatar",
                            "Profile",
                            'style="transition:transform 0.2s;cursor:pointer;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"',
                        ) ?>
                    </a>
                <?php endif; ?>
                <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
            <?php else: ?>
                <a href="login.php" class="comic-btn" style="display:inline-flex;align-items:center;font-size:0.85rem;padding:10px 18px;background:#fff;text-decoration:none;color:#000;">
                    <i class="fa-brands fa-google" style="font-size:18px;"></i>
                    Login
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="container" style="padding-top:40px;">
        <div class="gallery-header">
            <h1 class="gallery-title">All Prompts <span class="highlight">Gallery</span></h1>
            <div class="gallery-count" id="gallery-count-badge"><?= $total ?> Prompts</div>
        </div>

        <!-- Search Bar -->
        <div class="gallery-search-wrap">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="gallery-search" placeholder="Search prompts by name or tag..." autocomplete="off">
            <button class="search-clear-btn" id="search-clear-btn" title="Clear search" onclick="clearGallerySearch()">✕</button>
            <div class="search-autocomplete" id="search-autocomplete"></div>
        </div>


        <?php if (count($prompts) === 0): ?>
            <p style="text-align:center;font-weight:700;font-size:1.2rem;margin-top:60px;">No prompts yet. Check back soon!</p>
        <?php else: ?>
            <!-- Tag Controls -->
            <div class="tag-ctrl-bar">
              <span class="tag-ctrl-label"><i class="fa-solid fa-tag" style="margin-right:5px;"></i>Filter by Tag</span>
              <div class="tag-ctrl-right">
                <div class="tag-sort-grp">
                  <button class="tag-sort-btn active" data-sort="az">A &rarr; Z</button>
                  <button class="tag-sort-btn" data-sort="za">Z &rarr; A</button>
                  <button class="tag-sort-btn" data-sort="pop">Popular</button>
                </div>
                <button class="tag-toggle-btn" id="tag-toggle-btn">
                  <i class="fa-solid fa-chevron-up" id="tag-toggle-icon"></i>
                  <span id="tag-toggle-label">Hide</span>
                </button>
              </div>
            </div>

            <div class="tag-filter-container" id="tag-filter-container">
                <div class="tag-scroll-inner" id="tag-scroll-inner">
                <a href="gallery.php" class="tag-filter-btn <?= !$tag_filter || $tag_filter === 'all' ? 'active' : '' ?>" data-label="All" data-count="9999" style="background:<?= !$tag_filter || $tag_filter === 'all' ? 'var(--primary-color)' : 'var(--bg-color)' ?>;padding:8px 18px;border-radius:20px;font-weight:800;border:2px solid var(--text-color);cursor:pointer;font-family:var(--font-main);font-size:0.85rem;transition:all 0.2s;text-decoration:none;color:var(--text-color);">All</a>
                <?php
                $badge_colors = ['#c084fc','#f43f5e','#fb923c','#22c55e','#0ea5e9','#f59e0b','#8b5cf6','#ec4899','#14b8a6','#ef4444'];
                $ci = 0;
                foreach ($all_tags as $t):
                    $bc = $badge_colors[$ci % count($badge_colors)]; $ci++;
                ?>
                    <a href="gallery.php?tag=<?= urlencode($t) ?>" class="tag-filter-btn <?= $tag_filter === $t ? 'active' : '' ?>" data-label="<?= htmlspecialchars(ucfirst($t)) ?>" data-count="<?= $tag_counts[$t] ?? 0 ?>" style="background:<?= $tag_filter === $t ? 'var(--primary-color)' : 'var(--bg-color)' ?>;padding:8px 14px 8px 18px;border-radius:20px;font-weight:800;border:2px solid var(--text-color);cursor:pointer;font-family:var(--font-main);font-size:0.85rem;transition:all 0.2s;text-transform:capitalize;text-decoration:none;color:var(--text-color);display:inline-flex;align-items:center;gap:7px;"><?= htmlspecialchars(ucfirst($t)) ?><span style="display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 5px;background:<?= $bc ?>;color:#fff;border-radius:99px;font-size:.65rem;font-weight:900;line-height:1;box-shadow:1px 1px 0 rgba(0,0,0,0.25);"><?= $tag_counts[$t] ?? 0 ?></span></a>
                <?php endforeach; ?>

                </div>
            </div>


            <div class="gallery-grid" id="card-stack">
            <?php foreach ($prompts as $p):

                $db_type = $p["prompt_type"] ?? "secret";
                if ($db_type === "insta_viral") {
                    $ptype = "insta_viral";
                } elseif ($db_type === "unreleased") {
                    $ptype = "unreleased";
                } elseif ($db_type === "already_uploaded") {
                    $ptype = "already_uploaded";
                } else {
                    $ptype = "secret_code";
                }

                $tags_arr = array_map(
                    "trim",
                    explode(",", strtolower($p["tag"])),
                );

                $type_labels = [
                    "secret_code" => ["label" => "SECRET", "cls" => "scp"],
                    "unreleased" => ["label" => "UNRELEASED", "cls" => "urp"],
                    "insta_viral" => ["label" => "VIRAL", "cls" => "ivp"],
                    "already_uploaded" => [
                        "label" => "UPLOADED",
                        "cls" => "aup",
                    ],
                ];
                $tinfo = $type_labels[$ptype] ?? $type_labels["secret_code"];
                ?>
                    <div class="card"
                         data-id="<?= $p["id"] ?>"
                         data-slug="<?= htmlspecialchars($p["slug"] ?? "") ?>"
                         data-created="<?= htmlspecialchars($p["created_at"] ?? "") ?>"
                         data-image="<?= htmlspecialchars($p["image_path"]) ?>"
                         data-title="<?= htmlspecialchars($p["title"]) ?>"
                         data-reel="<?= htmlspecialchars(
                             $p["reel_link"] ?? "",
                         ) ?>"
                         data-unlocked="<?= $p["is_unlocked"]
                             ? "true"
                             : "false" ?>"
                         data-saved="<?= !empty($p["is_saved"])
                             ? "true"
                             : "false" ?>"
                         data-prompt-type="<?= htmlspecialchars($ptype) ?>"
                         data-tags="<?= htmlspecialchars(
                             implode(",", $tags_arr),
                         ) ?>"
                         data-best-works-in="<?= htmlspecialchars($p['best_works_in'] ?? '') ?>"
                         data-asset-title="<?= htmlspecialchars($p['asset_title'] ?? '') ?>"
                         data-asset-images="<?= htmlspecialchars($p['asset_images'] ?? '[]') ?>"
                         <?= $p["is_unlocked"]
                             ? 'data-prompt-text="' .
                                 htmlspecialchars($p["prompt_text"]) .
                                 '"'
                             : "" ?>>

                        <?php $blur_style =
                            $ptype === "unreleased" && !$p["is_unlocked"]
                                ? "filter: blur(5px); transform: scale(1.1);"
                                : ""; ?>
                        <img loading="lazy" src="<?= htmlspecialchars(
                            $p["image_path"],
                        ) ?>" class="card-bg-image" alt="<?= htmlspecialchars(
    $p["title"],
) ?>" style="<?= $blur_style ?>" loading="lazy">

                        <!-- Type Label Ribbon -->
                        <div class="card-type-badge <?= $tinfo[
                            "cls"
                        ] ?>"><?= $tinfo["label"] ?></div>

                        <?php if (!$p["is_unlocked"]): ?>
                            <div class="card-lock-icon">
                                <i class="fa-solid fa-lock" style="font-size:14px;"></i>
                            </div>
                        <?php else: ?>
                            <div class="card-lock-icon" style="background:var(--primary-color);">
                                <i class="fa-solid fa-check" style="font-size:14px;"></i>
                            </div>
                        <?php endif; ?>

                        <div class="card-click-trigger"></div>
                        <div class="card-content-overlay">
                            <div class="card-title"><?= htmlspecialchars(
                                $p["title"],
                            ) ?></div>
                            <!-- Static like display on card (not clickable) -->
                            <div class="card-like-display"
                                 data-liked="<?= $p["is_liked"]
                                     ? "true"
                                     : "false" ?>"
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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-top:40px;padding-bottom:20px;">
                <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" style="padding:10px 18px;background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;font-family:var(--font-main);text-decoration:none;color:var(--text-color);box-shadow:var(--shadow-comic);">&larr; Prev</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" style="padding:10px 18px;background:<?= $i === $page ? 'var(--primary-color)' : 'var(--card-bg)' ?>;border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;font-family:var(--font-main);text-decoration:none;color:var(--text-color);box-shadow:var(--shadow-comic);"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" style="padding:10px 18px;background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;font-family:var(--font-main);text-decoration:none;color:var(--text-color);box-shadow:var(--shadow-comic);">Next &rarr;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer>
        <div>&copy; 2026 ARIGATO DEVAN. KEEP CREATING.</div>
        <div class="footer-links"><a href="about.php">ABOUT</a><a href="contact.php">CONTACT</a><a href="faq.php">FAQ</a><a href="privacy.php">PRIVACY POLICY</a><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
    </footer>

    <!-- Unlock Modal -->
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
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap;"><h3 style="color:var(--text-color);font-size:1rem;margin:0;"><i class="fa-solid fa-scroll"></i> THE PROMPT:</h3><div id="modal-bwi-badge"></div></div>
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
                <a id="login-save-url" href="login.php" style="flex:1;padding:14px;background:var(--primary-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:var(--text-color);font-family:var(--font-main);">
                    <i class="fa-brands fa-google" style="margin-right:8px;"></i> Login with Google
                </a>
            </div>
        </div>
    </div>

    <script>const isLoggedIn = <?= isset($_SESSION["user_id"]) ? "true" : "false" ?>;</script>
    <script>
    (function(){
      var container  = document.getElementById('tag-filter-container');
      var inner      = document.getElementById('tag-scroll-inner');
      var toggleBtn  = document.getElementById('tag-toggle-btn');
      var toggleIcon = document.getElementById('tag-toggle-icon');
      var toggleLabel= document.getElementById('tag-toggle-label');
      var sortBtns   = document.querySelectorAll('.tag-sort-btn');
      if (!container || !toggleBtn || !inner) return;

      function getTags() {
        return Array.from(inner.querySelectorAll('.tag-filter-btn:not([data-label="All"])'));
      }

      function sortTags(type) {
        var tags = getTags();
        tags.sort(function(a, b) {
          if (type === 'az') return a.dataset.label.localeCompare(b.dataset.label);
          if (type === 'za') return b.dataset.label.localeCompare(a.dataset.label);
          if (type === 'pop') return (parseInt(b.dataset.count)||0) - (parseInt(a.dataset.count)||0);
          return 0;
        });
        tags.forEach(function(t){ inner.appendChild(t); });
        sortBtns.forEach(function(b){ b.classList.toggle('active', b.dataset.sort === type); });
        try { localStorage.setItem('tagbar_sort', type); } catch(e){}
      }

      function applyHide(hidden) {
        container.classList.toggle('tags-hidden', hidden);
        toggleIcon.className = hidden ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-up';
        toggleLabel.textContent = hidden ? 'Show' : 'Hide';
        try { localStorage.setItem('tagbar_hidden', hidden ? '1' : '0'); } catch(e){}
      }

      var savedSort = 'az';
      try { savedSort = localStorage.getItem('tagbar_sort') || 'az'; } catch(e){}
      sortTags(savedSort);
      var wasHidden = false;
      try { wasHidden = localStorage.getItem('tagbar_hidden') === '1'; } catch(e){}
      if (wasHidden) applyHide(true);

      sortBtns.forEach(function(btn){
        btn.addEventListener('click', function(){ sortTags(btn.dataset.sort); });
      });
      toggleBtn.addEventListener('click', function(){
        applyHide(!container.classList.contains('tags-hidden'));
      });
    })();
    </script>
        <script defer src="script.min.js?v=20260616"></script>
        <script>

        // Card click → navigate to clean prompt URL (with fade-out)
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.gallery-grid .card').forEach(function(card) {
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
                // Prefetch on hover
                card.addEventListener('mouseenter', function() {
                    var id = card.dataset.id;
                    if (!id) return;
                    var url = card.dataset.slug ? ('/prompts/' + card.dataset.slug) : ('prompt.php?id=' + id);
                    if (!document.querySelector('link[rel="prefetch"][href="' + url + '"]')) {
                        var link = document.createElement('link');
                        link.rel = 'prefetch'; link.href = url;
                        document.head.appendChild(link);
                    }
                }, { once: true });
            });
        });

        // ── Gallery autocomplete search ──
        (function() {
            var inp       = document.getElementById('gallery-search');
            var clearBtn  = document.getElementById('search-clear-btn');
            var countBadge= document.getElementById('gallery-count-badge');
            var noResults = document.getElementById('gallery-no-results');
            var ac        = document.getElementById('search-autocomplete');
            if (!inp) return;

            var cards = Array.from(document.querySelectorAll('.gallery-grid .card'));
            var activeIdx = -1;
            var acMatches = [];

            function escHTML(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

            var typeLabels  = {secret_code:'SECRET',unreleased:'UNRELEASED',insta_viral:'VIRAL',already_uploaded:'UPLOADED'};
            var typeColors  = {secret_code:'#c084fc',unreleased:'#fb923c',insta_viral:'#f43f5e',already_uploaded:'#22c55e'};

            function filterGallery(q) {
                var visible = 0;
                cards.forEach(function(card) {
                    var title = (card.dataset.title || '').toLowerCase();
                    var tags  = (card.dataset.tags  || '').toLowerCase();
                    var match = !q || title.includes(q) || tags.includes(q);
                    card.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                if (countBadge) countBadge.textContent = visible + ' Prompts';
                if (noResults)  noResults.classList.toggle('show', visible === 0 && q.length > 0);
                clearBtn.classList.toggle('visible', q.length > 0);
            }

            function buildAC(q) {
                if (!q) { hideAC(); return; }
                acMatches = cards.filter(function(card) {
                    var title = (card.dataset.title || '').toLowerCase();
                    var tags  = (card.dataset.tags  || '').toLowerCase();
                    return title.includes(q) || tags.includes(q);
                }).slice(0, 7);
                if (!acMatches.length) { hideAC(); return; }

                ac.innerHTML = '<div class="ac-header"><i class="fa-solid fa-magnifying-glass" style="margin-right:5px;"></i>Suggestions</div>';
                acMatches.forEach(function(card, i) {
                    var type     = card.dataset.promptType || 'secret_code';
                    var label    = typeLabels[type] || 'PROMPT';
                    var color    = typeColors[type] || '#c084fc';
                    var tagList  = (card.dataset.tags || '').split(',').slice(0,3).map(function(t){ return t.trim(); }).filter(Boolean).join(' · ');
                    var item = document.createElement('div');
                    item.className = 'ac-item';
                    item.innerHTML =
                        '<div class="ac-badge" style="background:'+color+'">'+label+'</div>'+
                        '<div class="ac-info">'+
                          '<div class="ac-title">'+escHTML(card.dataset.title || '')+'</div>'+
                          (tagList ? '<div class="ac-tags">'+escHTML(tagList)+'</div>' : '')+
                        '</div>'+
                        '<i class="fa-solid fa-arrow-right ac-arrow"></i>';
                    item.addEventListener('mouseover', function(){ setActive(i); });
                    item.addEventListener('mousedown', function(e){
                        e.preventDefault();
                        pickSuggestion(acMatches[i]);
                    });
                    ac.appendChild(item);
                });
                activeIdx = -1;
                ac.style.display = 'block';
            }

            function setActive(i) {
                activeIdx = i;
                ac.querySelectorAll('.ac-item').forEach(function(el, idx){
                    el.classList.toggle('active', idx === i);
                });
            }

            function pickSuggestion(card) {
                inp.value = card.dataset.title || '';
                hideAC();
                filterGallery(inp.value.trim().toLowerCase());
                // Scroll to card + highlight
                card.style.display = '';
                setTimeout(function(){
                    card.scrollIntoView({ behavior:'smooth', block:'center' });
                    card.style.outline = '3px solid var(--primary-color)';
                    card.style.outlineOffset = '3px';
                    setTimeout(function(){ card.style.outline=''; card.style.outlineOffset=''; }, 1800);
                }, 80);
            }

            function hideAC() {
                ac.style.display = 'none';
                ac.innerHTML = '';
                activeIdx = -1;
                acMatches = [];
            }

            inp.addEventListener('input', function(){
                var q = inp.value.trim().toLowerCase();
                filterGallery(q);
                buildAC(q);
            });

            inp.addEventListener('keydown', function(e){
                var items = ac.querySelectorAll('.ac-item');
                if (ac.style.display !== 'block' || !items.length) return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault(); setActive(Math.min(activeIdx+1, items.length-1));
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault(); setActive(Math.max(activeIdx-1, -1));
                } else if (e.key === 'Enter' && activeIdx >= 0) {
                    e.preventDefault(); if (acMatches[activeIdx]) pickSuggestion(acMatches[activeIdx]);
                } else if (e.key === 'Escape') {
                    hideAC();
                }
            });

            inp.addEventListener('focus', function(){
                if (inp.value.trim().length > 0) buildAC(inp.value.trim().toLowerCase());
            });

            document.addEventListener('click', function(e){
                if (!e.target.closest('.gallery-search-wrap')) hideAC();
            });

            window.clearGallerySearch = function() {
                inp.value = '';
                filterGallery('');
                hideAC();
                inp.focus();
            };
        })();
    </script>


<!-- No-results state -->
<div class="gallery-no-results" id="gallery-no-results">
    <div class="no-res-emoji">🔍</div>
    <h3>No prompts found</h3>
    <p>Try a different keyword or clear the search</p>
</div>

<!-- Wrong Code Comic Popup -->
<div id="wrong-code-popup">
    <div class="wrong-code-card">
        <span class="wrong-code-emoji"><i class="fa-solid fa-xmark"></i></span>
        <div class="wrong-code-title">NO NO BACHA...</div>
        <div class="wrong-code-msg">Its wrong code <i class="fa-solid fa-face-sad-cry"></i><br>Watch the reel to get the correct one!</div>
        <button class="wrong-code-close" onclick="document.getElementById('wrong-code-popup').classList.remove('show')">TRY AGAIN <i class="fa-solid fa-rotate"></i></button>
    </div>
</div>
</body>
</html>

