<?php
session_start();
require_once "db.php";
if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

// Fetch published blogs with author info
$blogs = $pdo
    ->query(
        "
    SELECT b.*, u.username as author_name, u.avatar as author_avatar
    FROM blogs b
    LEFT JOIN users u ON b.author_id = u.id
    WHERE b.is_published = 1
    ORDER BY b.created_at DESC
",
    )
    ->fetchAll(PDO::FETCH_ASSOC);

// Extract all unique tags for filter
$all_tags = [];
foreach ($blogs as $b) {
    if ($b["tags"]) {
        foreach (
            array_filter(array_map("trim", explode(",", $b["tags"])))
            as $tag
        ) {
            $all_tags[$tag] = ($all_tags[$tag] ?? 0) + 1;
        }
    }
}
arsort($all_tags);

// Google auth url for login button
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Blogs &ndash; Arigato Devan Prompts</title>
<meta name="description" content="Read the latest blogs on AI, couple content, and creative prompts from Arigato Devan.">
<link rel="stylesheet" href="style.css?v=1778100000">
<style>
/* &mdash;&ndash;&mdash;&ndash;&mdash; Blogs Page &mdash;&ndash;&mdash;&ndash;&mdash; */
.blogs-hero {
    padding: 60px 40px 40px;
    max-width: 1300px;
    margin: 0 auto;
    text-align: center;
}
.blogs-hero h1 {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 900;
    margin-bottom: 14px;
    line-height: 1.1;
    font-family: var(--font-blog-heading);
    letter-spacing: -1px;
}
.blogs-hero p {
    font-size: 1.05rem;
    font-weight: 400;
    color: #666;
    max-width: 520px;
    margin: 0 auto 36px;
    font-family: var(--font-blog-body);
}

/* &mdash;&ndash;&mdash;&ndash;&mdash; Tag Filter Pills &mdash;&ndash;&mdash;&ndash;&mdash; */
.tag-filter-wrap {
    max-width: 1300px;
    margin: 0 auto 40px;
    padding: 0 40px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}
.tag-pill {
    padding: 8px 20px;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 40px;
    font-weight: 800;
    font-size: 0.78rem;
    cursor: pointer;
    transition: all 0.2s ease-out;
    font-family: var(--font-main);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-color);
}
.tag-pill:hover {
    border-color: var(--text-color);
    background: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 2px 2px 0 var(--text-color);
}
.tag-pill.active {
    background: var(--primary-color);
    border-color: var(--text-color);
    box-shadow: 3px 3px 0 var(--text-color);
    transform: translateY(-1px);
}

/* &mdash;&ndash;&mdash;&ndash;&mdash; Blog Grid &mdash;&ndash;&mdash;&ndash;&mdash; */
.blogs-wrap {
    max-width: 1300px;
    margin: 0 auto;
    padding: 0 40px 100px;
}

/* Featured Post (First post, wider) */
.blog-featured {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 0;
    background: var(--card-bg);
    border: var(--border-width) solid var(--text-color);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: var(--shadow-comic);
    margin-bottom: 36px;
    text-decoration: none;
    color: inherit;
    transition: all 0.25s ease-out;
}
.blog-featured:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-comic-hover);
}
.blog-featured-img {
    width: 100%;
    height: 100%;
    min-height: 320px;
    object-fit: cover;
    display: block;
    border-right: var(--border-width) solid var(--text-color);
}
.blog-featured-img-placeholder {
    width: 100%;
    min-height: 320px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 5rem;
    border-right: var(--border-width) solid var(--text-color);
}
.blog-featured-body {
    padding: 36px 32px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.blog-featured-badge {
    display: inline-block;
    background: var(--secondary-color);
    border: 1.5px solid var(--text-color);
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 0.7rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 16px;
    width: fit-content;
}
.blog-featured-title {
    font-size: 1.8rem;
    font-weight: 900;
    line-height: 1.25;
    margin-bottom: 14px;
    font-family: var(--font-blog-heading);
}
.blog-featured-desc {
    font-size: 0.95rem;
    font-weight: 400;
    color: #666;
    line-height: 1.65;
    margin-bottom: 24px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-family: var(--font-blog-body);
}
.blog-author-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.83rem;
    font-weight: 700;
    color: #888;
}
.blog-author-av {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary-color);
    flex-shrink: 0;
}
.blog-author-name { color: var(--text-color); font-weight: 800; }

/* &mdash;&ndash;&mdash;&ndash;&mdash; Normal Grid &mdash;&ndash;&mdash;&ndash;&mdash; */
.blogs-grid {
    column-count: 3;
    column-gap: 26px;
    margin-top: 20px;
}
.blog-card {
    break-inside: avoid;
    margin-bottom: 26px;
    background: var(--card-bg);
    border: var(--border-width) solid var(--text-color);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: var(--shadow-comic);
    transition: all 0.25s ease-out;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: block;
}
.blog-card:hover {
    transform: translateY(-6px) rotate(-0.5deg);
    box-shadow: var(--shadow-comic-hover);
}
.blog-card-img {
    width: 100%;
    display: block;
    object-fit: cover;
    border-bottom: var(--border-width) solid var(--text-color);
}
.blog-card-img.ratio-16-9 { aspect-ratio: 16 / 9; }
.blog-card-img.ratio-9-16 { aspect-ratio: 9 / 16; }

.blog-card-img-placeholder {
    width: 100%;
    aspect-ratio: 16 / 9;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    border-bottom: var(--border-width) solid var(--text-color);
}
.blog-card-img-placeholder.ratio-16-9 { aspect-ratio: 16 / 9; }
.blog-card-img-placeholder.ratio-9-16 { aspect-ratio: 9 / 16; }

.blog-card-body { padding: 20px 18px 18px; }
.blog-card-tag {
    display: inline-block;
    background: var(--secondary-color);
    border: 1.5px solid var(--text-color);
    border-radius: 20px;
    padding: 3px 12px;
    font-size: 0.72rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 11px;
}
.blog-card-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 9px;
    line-height: 1.3;
    font-family: var(--font-blog-heading);
}
.blog-card-desc {
    font-size: 0.87rem;
    font-weight: 400;
    color: #666;
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 14px;
    font-family: var(--font-blog-body);
}
.blog-card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1.5px dashed var(--border-color);
    font-size: 0.78rem;
    font-weight: 700;
    color: #999;
}
.blog-card-meta-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.blog-card-likes {
    display: flex;
    align-items: center;
    gap: 4px;
}

.empty-blogs {
    text-align: center;
    padding: 80px 20px;
    color: #7D7887;
    font-weight: 700;
    font-size: 1.1rem;
}

@media (max-width: 900px) {
    .blogs-grid { column-count: 2; }
}
@media (max-width: 768px) {
    .blog-featured { grid-template-columns: 1fr; }
    .blog-featured-img, .blog-featured-img-placeholder { min-height: 220px; border-right: none; border-bottom: var(--border-width) solid var(--text-color); }
    .blog-featured-body { padding: 24px 20px; }
    .blog-featured-title { font-size: 1.4rem; }
    .tag-filter-wrap, .blogs-wrap { padding: 0 16px 80px; }
    .blogs-hero { padding: 40px 20px 28px; }
    .blogs-grid { column-gap: 16px; }
}
@media (max-width: 600px) {
    .blogs-grid { column-count: 1; }
}
</style>
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
    <!-- Scrollable Wallpaper Background -->
    <div class="scroll-bg-container" id="scroll-bg-container">
        <div class="bg-layer active" style="background-image: url('https://i.pinimg.com/736x/4d/e2/71/4de271ae9997273cf3fdd47098fa69a3.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/1200x/76/50/aa/7650aa986d34ca65bb52f261f954149b.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/1200x/64/c4/c5/64c4c528ee5812610d58ee2c98bbb76f.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/736x/f9/fd/75/f9fd75e5aa551b89ac88a863921f2f75.jpg')"></div>
        <div class="bg-layer" style="background-image: url('https://i.pinimg.com/736x/a5/15/6a/a5156a264e06ebb47997cf59e66bee31.jpg')"></div>
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
    <a href="blogs.php" class="active">BLOGS</a>
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
      <span style="font-weight:600;">@arigato.devan</span>
      <span class="pulse-dot"></span>
      <span style="font-weight:800;font-size:1.1rem;">13K+</span>
    </a>
  </nav>
  <div class="header-right">
    <div class="header-divider"></div>
    <?php if (isset($_SESSION["user_id"])): ?>
      <?php if ($_SESSION["role"] === "admin"): ?>
        <div style="display:flex; align-items:center; gap:8px;"><a href="profile.php" title="Edit Profile"><?= renderAvatar(
            $_SESSION["profile_image"] ?? "",
            "admin-avatar",
            "Admin",
            'style="transition: transform 0.2s;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"',
        ) ?></a><a href="dashboard.php" style="color:var(--text-color); font-weight:800;">ADMIN</a></div>
      <?php else: ?>
        <a href="profile.php" style="color:var(--text-color);display:flex;align-items:center;gap:8px"><?= renderAvatar(
            $_SESSION["profile_image"] ?? "",
            "admin-avatar",
            "Profile",
        ) ?></a>
      <?php endif; ?>
      <a href="login.php?logout=1" class="logout"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> LOGOUT</a>
    <?php else: ?>
      <a href="login.php" class="comic-btn" style="font-size:.85rem;padding:9px 18px;text-decoration:none;color:var(--text-color);background:var(--primary-color);">LOGIN</a>
    <?php endif; ?>
  </div>
</header>

<!-- Hero -->
<div class="blogs-hero">
  <div class="badge"><i class="fa-solid fa-pen-nib"></i> FRESH READS</div>
  <h1>The <span class="highlight">Arigato Devan</span> Blog</h1>
  <p>Tips, stories, and ideas to fuel your creative AI journey.</p>
</div>

<?php if (count($blogs) === 0): ?>
<div class="empty-blogs"><i class="fa-solid fa-pen"></i> No blogs published yet &mdash; check back soon!</div>
<?php else: ?>

<!-- Tag Filter -->
<?php if (!empty($all_tags)): ?>
<div class="tag-filter-wrap" id="tag-filters">
  <button class="tag-pill active" data-tag="all" onclick="filterByTag('all', this)">ALL</button>
  <?php foreach ($all_tags as $tag => $count): ?>
  <button class="tag-pill" data-tag="<?= htmlspecialchars(
      strtolower($tag),
  ) ?>" onclick="filterByTag('<?= htmlspecialchars(
    strtolower($tag),
) ?>', this)"><?= htmlspecialchars(
    $tag,
) ?> <span style="opacity:.5;font-size:.7em;"><?= $count ?></span></button>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="blogs-wrap">

  <div class="blogs-grid" id="blogs-grid">
    <?php foreach ($blogs as $b):

        $ratio_class =
            ($b["image_ratio"] ?? "16:9") === "9:16"
                ? "ratio-9-16"
                : "ratio-16-9";
        $short_preview = mb_substr(strip_tags($b["content"]), 0, 60) . "...";
        ?>
    <a href="blog.php?slug=<?= urlencode($b["slug"]) ?>" class="blog-card"
       data-tags="<?= htmlspecialchars(strtolower($b["tags"] ?? "")) ?>">
      <?php if ($b["image_path"]): ?>
        <img src="<?= htmlspecialchars(
            $b["image_path"],
        ) ?>" class="blog-card-img <?= $ratio_class ?>" alt="<?= htmlspecialchars(
    $b["title"],
) ?>" loading="lazy">
      <?php else: ?>
        <div class="blog-card-img-placeholder <?= $ratio_class ?>"><i class="fa-solid fa-image"></i></div>
      <?php endif; ?>
      <div class="blog-card-body">
        <?php if ($b["tags"]): ?>
          <div class="blog-card-tag"><?= htmlspecialchars(
              explode(",", $b["tags"])[0],
          ) ?></div>
        <?php endif; ?>
        <div class="blog-card-title"><?= htmlspecialchars($b["title"]) ?></div>

        <div class="blog-card-desc"><?= htmlspecialchars(
            $short_preview,
        ) ?></div>

        <div class="blog-card-meta">
          <div class="blog-card-meta-left">
            <img src="<?= htmlspecialchars(
                $b["author_avatar"] ??
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=x",
            ) ?>" class="blog-author-av" alt="" style="width:26px;height:26px;">
            <span style="color:var(--text-color);font-weight:800;"><?= htmlspecialchars(
                $b["author_name"] ?? "Admin",
            ) ?></span>
            <span>&middot;</span>
            <span><?= date("d M Y", strtotime($b["created_at"])) ?></span>
          </div>
          <div>
            <span class="blog-card-likes" style="margin-right:8px;"><i class="fa-solid fa-heart"></i> <?= (int) $b[
                "likes_count"
            ] ?></span>
            <span class="blog-card-likes"><i class="fa-solid fa-eye"></i> <?= (int) ($b[
                "views_count"
            ] ?? 0) ?></span>
          </div>
        </div>
      </div>
    </a>
    <?php
    endforeach; ?>
  </div>
  <!-- No results message -->
  <div id="no-results-msg" style="display:none;text-align:center;padding:60px 20px;color:#7D7887;font-weight:700;font-size:1.1rem;">
    No blogs found for this tag <i class="fa-solid fa-magnifying-glass"></i>
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

<script defer src="script.js?v=1778000000"></script>
<script>
function filterByTag(tag, btn) {
  // Update active pill
  document.querySelectorAll('.tag-pill').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');

  const featured = document.getElementById('featured-post');
  const grid     = document.getElementById('blogs-grid');
  const noRes    = document.getElementById('no-results-msg');

  if (tag === 'all') {
    if (featured) featured.style.display = '';
    document.querySelectorAll('#blogs-grid .blog-card').forEach(c => c.style.display = '');
    if (noRes) noRes.style.display = 'none';
    return;
  }

  // Filter featured
  let anyVisible = false;
  if (featured) {
    const ftags = (featured.dataset.tags || '').toLowerCase();
    const show = ftags.includes(tag);
    featured.style.display = show ? '' : 'none';
    if (show) anyVisible = true;
  }

  // Filter grid cards
  document.querySelectorAll('#blogs-grid .blog-card').forEach(card => {
    const ctags = (card.dataset.tags || '').toLowerCase();
    const show = ctags.includes(tag);
    card.style.display = show ? '' : 'none';
    if (show) anyVisible = true;
  });

  if (noRes) noRes.style.display = anyVisible ? 'none' : 'block';
}
</script>
<script>
        // Background Scroll Logic
        const bgLayers = document.querySelectorAll('.bg-layer');
        let _blogsScrollTicking = false;
        if (bgLayers.length > 0) {
            window.addEventListener('scroll', function() {
                if (!_blogsScrollTicking) {
                    requestAnimationFrame(function() {
                        const scrollPos = window.scrollY;
                        const pixelsPerLayer = 500;
                        let activeIndex = Math.floor(scrollPos / pixelsPerLayer);
                        if (activeIndex >= bgLayers.length) activeIndex = bgLayers.length - 1;
                        bgLayers.forEach((layer, index) => {
                            if (index === activeIndex) layer.classList.add('active');
                            else layer.classList.remove('active');
                        });
                        _blogsScrollTicking = false;
                    });
                    _blogsScrollTicking = true;
                }
            });
        }
</script>
</body></html>
