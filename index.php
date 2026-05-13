<?php
session_start();
require_once "db.php";
// Guard: if logged in but onboarding not done, force setup
if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

// Fetch ONLY secret prompts for Home page
if (isset($_SESSION["user_id"])) {
    $stmt = $pdo->prepare("
        SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
               IF(l.id IS NOT NULL, 1, 0) as is_liked
        FROM prompts p
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
        LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
        WHERE p.prompt_type = 'secret'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"]]);
    $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query(
        "SELECT *, 0 as is_unlocked, 0 as is_liked FROM prompts WHERE prompt_type = 'secret' ORDER BY created_at DESC",
    );
    $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generate state token for CSRF if not exists
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arigato Devan Prompts</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
        <link rel="preconnect" href="https://unpkg.com" crossorigin>
        <link rel="stylesheet" href="style.css?v=2026051205">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Preload first 3 prompt images for faster perceived loading -->
    <?php if (isset($prompts) && is_array($prompts)) {
        for ($i = 0; $i < min(3, count($prompts)); $i++) {
            echo '<link rel="preload" as="image" href="' .
                htmlspecialchars($prompts[$i]["image_path"]) .
                '">' .
                "\n";
        }
    } ?>
    <?php include_once "gtag.php"; ?>
</head>
<body>

<?php if (isset($_SESSION["user_id"])): ?>
    <!-- Scrollable Wallpaper Background -->
    <div class="scroll-bg-container" id="scroll-bg-container">
        <div class="bg-layer active" style="background-image: url('https://i.pinimg.com/736x/4d/e2/71/4de271ae9997273cf3fdd47098fa69a3.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/1200x/76/50/aa/7650aa986d34ca65bb52f261f954149b.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/1200x/64/c4/c5/64c4c528ee5812610d58ee2c98bbb76f.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/736x/f9/fd/75/f9fd75e5aa551b89ac88a863921f2f75.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/736x/a5/15/6a/a5156a264e06ebb47997cf59e66bee31.jpg')"></div>
        <div class="bg-creamy-overlay"></div>
    </div>
<?php endif; ?>

    <header>
        <div class="logo-area" id="logo-container"  style="cursor:pointer;">
            <div class="logo-flipper">
                <div class="logo-front">
                    <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Arigato Devan Logo" id="profile-logo">
                </div>
                <div class="logo-back">
                    <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt="Logo Alt">
                </div>
            </div>
            <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
        </div>
        <nav class="nav-links">
            <a href="index.php" class="active">HOME</a>
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
            <a href="https://www.instagram.com/arigato.devan/" target="_blank" style="display:flex;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;color:inherit;font-family:var(--font-main);">
                <i class="fa-brands fa-instagram" style="font-size:18px;"></i>
                <span style="font-weight:600;">@arigato.devan</span>
                <span class="pulse-dot"></span>
                <span style="font-weight:800;font-size:1.1rem;">13K+</span>
            </a>
        </nav>
        <div class="header-right">
            <div class="header-divider"></div>
            <?php if (isset($_SESSION["user_id"])): ?>
                <?php if ($_SESSION["role"] === "admin"): ?>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <a href="profile.php" title="Edit Profile">
                            <?= renderAvatar(
                                $_SESSION["profile_image"] ?? "",
                                "admin-avatar",
                                "Admin",
                                'style="transition:transform 0.2s;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"',
                            ) ?>
                        </a>
                        <a href="dashboard.php" style="color:var(--text-color);font-weight:800;">ADMIN</a>
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
    <div class="landing-page-root">

        <!-- Film Strip Background Layer -->
        <div class="filmstrip-bg" aria-hidden="true">
            <!-- Row 1: left scroll -->
            <div class="filmstrip-row row-1">
                <div class="filmstrip-track">
                    <?php
                    $strip_imgs = [
                        "landingpics/lan1.webp",
                        "landingpics/lan2.webp",
                        "landingpics/lan3.webp",
                        "landingpics/lan4.webp",
                        "landingpics/lan5.webp",
                        "landingpics/lan6.webp",
                        "landingpics/lan7.webp",
                        "landingpics/lan8.webp",
                        "landingpics/lan9.webp",
                        "landingpics/lan10.webp",
                        "landingpics/lan11.webp",
                        "landingpics/lan12.webp",
                        "landingpics/lan13.webp",
                        "landingpics/lan14.webp",
                        "landingpics/lan15.webp",
                        "landingpics/lan16.webp",
                        "landingpics/lan17.webp",
                    ];
                    // Duplicate for seamless loop
                    $all = array_merge($strip_imgs, $strip_imgs);
                    foreach ($all as $img): ?>
                    <div class="filmstrip-frame">
                        <picture>
                            <source srcset="<?= $img ?>" type="image/webp">
                            <img src="<?= str_replace(
                                ".webp",
                                ".png",
                                $img,
                            ) ?>" alt="" loading="lazy" width="200" height="356">
                        </picture>
                    </div>
                    <?php endforeach;
                    ?>
                </div>
            </div>
            <!-- Row 2: right scroll (reversed, middle row, larger) -->
            <div class="filmstrip-row row-2">
                <div class="filmstrip-track track-reverse">
                    <?php
                    // Shift array for variety in middle row
                    $middle_imgs = array_slice($strip_imgs, 8);
                    $middle_imgs = array_merge(
                        $middle_imgs,
                        array_slice($strip_imgs, 0, 8),
                    );
                    foreach (
                        array_merge($middle_imgs, $middle_imgs)
                        as $img
                    ): ?>
                    <div class="filmstrip-frame frame-large">
                        <picture>
                            <source srcset="<?= $img ?>" type="image/webp">
                            <img src="<?= str_replace(
                                ".webp",
                                ".png",
                                $img,
                            ) ?>" alt="" loading="lazy">
                        </picture>
                    </div>
                    <?php endforeach;
                    ?>
                </div>
            </div>
            <!-- Row 3: left scroll (bottom row) -->
            <div class="filmstrip-row row-3">
                <div class="filmstrip-track">
                    <?php foreach (
                        array_merge(
                            array_reverse($strip_imgs),
                            array_reverse($strip_imgs),
                        )
                        as $img
                    ): ?>
                    <div class="filmstrip-frame">
                        <picture>
                            <source srcset="<?= $img ?>" type="image/webp">
                            <img src="<?= str_replace(
                                ".webp",
                                ".png",
                                $img,
                            ) ?>" alt="" loading="lazy">
                        </picture>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Gradient overlay for readability -->
            <div class="filmstrip-overlay"></div>
        </div>

        <!-- Center Hero Content -->
        <div class="landing-center">

            <!-- Sticker Tags row -->
            <div class="sticker-row">
                <div class="sticker sticker-new"><i class="fa-solid fa-wand-magic-sparkles"></i> NEW</div>
                <div class="sticker sticker-hot"><i class="fa-solid fa-fire"></i> HOT</div>
                <div class="sticker sticker-ai"><i class="fa-solid fa-robot"></i> AI-POWERED</div>
            </div>

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
                    <img src="https://developers.google.com/identity/images/g-logo.png" alt="G">
                    Login with Google
                </a>
                <a href="gallery.php" class="cta-btn cta-secondary" id="hero-gallery-btn">
                    Explore Prompts →
                </a>
            </div>

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
        <div class="badge">
            <svg style="vertical-align: middle; margin-right: 5px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            FRESH DROPS!
        </div>
        <h1>UNLOCK<br>THE <span class="highlight">MAGIC.</span></h1>
        <div class="search-bar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" placeholder="SEARCH PROMPTS...">
        </div>
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
                         <?= $p["is_unlocked"]
                             ? 'data-prompt-text="' .
                                 htmlspecialchars($p["prompt_text"]) .
                                 '"'
                             : "" ?>>
                        <img src="<?= htmlspecialchars(
                            $p["image_path"],
                        ) ?>" class="card-bg-image" alt="Prompt Image" <?= $index <
3
    ? ""
    : 'loading="lazy"' ?>>

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

    <footer>
        <div>&copy; 2026 ARIGATO DEVAN. KEEP CREATING.</div>
        <div class="footer-links">
            <a href="disclaimer.php">DISCLAIMER</a>
            <a href="terms.php">TERMS OF SERVICE</a>
        </div>
    </footer>

    <!-- Unlock Modal - No longer restricted by session so guests can see the "login to save" features -->
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
                    <a href="#" id="modal-reel-link" target="_blank" class="comic-btn-small"><i class="fa-solid fa-play"></i> WATCH REEL TO GET IT</a>
                </div>

                <div class="modal-unlock-area" id="modal-unlock-area">
                    <p style="font-weight:700;margin-bottom:16px;color:#555;">Enter the secret code to reveal this prompt.</p>
                    <input type="text" id="unlock-code-input" placeholder="6-Letter Code" maxlength="6">
                    <button id="submit-code"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt</button>
                </div>

                <div class="modal-unlocked-area" id="modal-unlocked-area" style="display:none;flex-direction:column;text-align:left;">
                    <h3 style="margin-bottom:10px;color:var(--text-color);font-size:1rem;"><i class="fa-solid fa-scroll"></i> THE PROMPT:</h3>
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

        // Save Prompt Logic
        document.addEventListener('click', function(e) {
            const saveBtn = e.target.closest('.save-prompt-btn');
            if (!saveBtn) return;
            const promptId = saveBtn.dataset.promptId;
            if (!promptId) return;

            if (!isLoggedIn) {
                document.getElementById('login-save-popup').style.display = 'flex';
                return;
            }

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            fetch('save_prompt.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'prompt_id=' + encodeURIComponent(promptId)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    saveBtn.innerHTML = '<i class="fa-solid fa-check"></i> SAVED!';
                    saveBtn.style.background = 'var(--success-color, #d9f5e5)';
                    saveBtn.style.color = 'var(--text-color)';
                    saveBtn.classList.add('btn-success-pop');
                } else {
                    saveBtn.innerHTML = '<i class="fa-solid fa-bookmark"></i> SAVE';
                    saveBtn.disabled = false;
                }
            })
            .catch(() => {
                saveBtn.innerHTML = '<i class="fa-solid fa-bookmark"></i> SAVE';
                saveBtn.disabled = false;
            });
        });

        // Update save-btn promptId when modal opens
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
