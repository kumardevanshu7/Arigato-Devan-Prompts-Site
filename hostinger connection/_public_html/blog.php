<?php
session_start();
require_once "db.php";
$slug = $_GET["slug"] ?? "";
if (!$slug) {
    header("Location: blogs.php");
    exit();
}

$stmt = $pdo->prepare(
    "SELECT b.*, u.username as author_name, u.avatar as author_avatar FROM blogs b LEFT JOIN users u ON b.author_id=u.id WHERE b.slug=? AND b.is_published=1",
);
$stmt->execute([$slug]);
$blog = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$blog) {
    header("Location: blogs.php");
    exit();
}

// Has current user liked?
$user_liked = false;
if (isset($_SESSION["user_id"])) {
    $lk = $pdo->prepare(
        "SELECT id FROM blog_likes WHERE user_id=? AND blog_id=?",
    );
    $lk->execute([$_SESSION["user_id"], $blog["id"]]);
    $user_liked = (bool) $lk->fetch();
}

// Comments
$comments = $pdo->prepare(
    "SELECT bc.*, u.username, u.avatar as profile_image FROM blog_comments bc LEFT JOIN users u ON bc.user_id=u.id WHERE bc.blog_id=? ORDER BY bc.created_at ASC",
);
$comments->execute([$blog["id"]]);
$comments = $comments->fetchAll(PDO::FETCH_ASSOC);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(
    $blog["meta_title"] ?? $blog["title"],
) ?> &ndash; Arigato Devan Prompts</title>
<meta name="description" content="<?= htmlspecialchars(
    $blog["meta_description"] ?? ($blog["description"] ?? ""),
) ?>">
<?php if ($blog["tags"]): ?><meta name="keywords" content="<?= htmlspecialchars(
    $blog["tags"],
) ?>"><?php endif; ?>
<link rel="stylesheet" href="style.css?v=1778100000">
<style>
.blog-detail-wrap{max-width:800px;margin:0 auto;padding:48px 32px 100px}
.blog-detail-hero-img{width:100%;max-height:440px;object-fit:cover;border-radius:22px;border:var(--border-width) solid var(--text-color);box-shadow:var(--shadow-comic);margin-bottom:36px;display:block}
.blog-detail-title{font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;line-height:1.2;margin-bottom:18px;font-family:var(--font-blog-heading);letter-spacing:-0.5px}
.blog-detail-meta{display:flex;align-items:center;gap:14px;margin-bottom:28px;flex-wrap:wrap}
.blog-author-avatar{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--primary-color)}
.blog-author-name{font-weight:800;font-size:.92rem}
.blog-detail-date{color:#888;font-size:.85rem;font-weight:600}
.blog-tags{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:32px}
.blog-tag{background:var(--secondary-color);border:1.5px solid var(--text-color);border-radius:20px;padding:4px 12px;font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:1px}
/* Paper Notebook Styling */
.blog-paper {
    background-color: #FDFBF7 !important;
    background-image:
        linear-gradient(90deg, transparent 55px, #ff9999 55px, #ff9999 57px, transparent 57px),
        repeating-linear-gradient(transparent, transparent calc(2rem - 2px), #EAE3F2 calc(2rem - 2px), #EAE3F2 2rem) !important;
    background-attachment: local !important;
    padding: 40px 40px 40px 85px !important;
    border-radius: 20px !important;
    border: 3px solid #2D2A35 !important;
    box-shadow: 10px 10px 0px #2D2A35 !important;
    margin-top: 30px !important;
    line-height: 2rem !important;
    position: relative !important;
    z-index: 10 !important;
}
.blog-content { font-size: 1.05rem; font-weight: 400; line-height: 2rem; color: var(--text-color); font-family: var(--font-blog-body); }
.blog-content h1{font-size:2rem;font-weight:900;margin:36px 0 16px;font-family:var(--font-blog-heading)}
.blog-content h2{font-size:1.6rem;font-weight:700;margin:30px 0 14px;font-family:var(--font-blog-heading)}
.blog-content h3{font-size:1.25rem;font-weight:700;margin:24px 0 10px;font-family:var(--font-blog-heading)}
.blog-content p{margin-bottom:18px}
.blog-content ul,.blog-content ol{padding-left:28px;margin-bottom:18px}
.blog-content li{margin-bottom:8px}
.blog-content strong{font-weight:700}
.blog-content em{font-style:italic}
.blog-content blockquote{border-left:4px solid var(--primary-dark);padding:14px 20px;margin:24px 0;background:var(--primary-color);border-radius:0 12px 12px 0;font-style:italic;font-weight:600;font-family:var(--font-blog-body)}
/* Font styles */
.blog-content .font-serif{font-family:Georgia,serif}
.blog-content .font-mono{font-family:monospace}
.blog-content .font-bold{font-weight:900}
.blog-content .font-light{font-weight:400;color:#555}
.blog-content .font-highlight{background:var(--secondary-color);padding:0 4px;border-radius:4px}
/* Like + Share bar */
.blog-action-bar{display:flex;align-items:center;gap:16px;padding:22px 0;border-top:2px dashed var(--border-color);border-bottom:2px dashed var(--border-color);margin:40px 0}
.blog-like-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:var(--secondary-color);border:var(--border-width) solid var(--text-color);border-radius:16px;font-family:var(--font-main);font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);transition:all .2s}
.blog-like-btn:hover{transform:translateY(-2px) rotate(2deg);box-shadow:var(--shadow-comic-hover)}
.blog-like-btn.liked{background:#FF6B6B;color:#fff}
.blog-like-btn.liked svg{fill:#fff;stroke:#fff}
/* Comments */
.comments-section h3{font-size:1.4rem;font-weight:900;margin-bottom:24px}
.comment-item{display:flex;gap:14px;margin-bottom:20px;padding:16px;background:var(--bg-color);border:1.5px solid var(--border-color);border-radius:14px;transition:all .2s}
.comment-item:hover{border-color:var(--text-color);box-shadow:2px 2px 0 var(--text-color)}
.comment-avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--primary-color);flex-shrink:0}
.comment-body{}
.comment-name{font-weight:800;font-size:.88rem;margin-bottom:4px}
.comment-text{font-size:.9rem;font-weight:500;line-height:1.55;color:#444}
.comment-time{font-size:.75rem;color:#aaa;font-weight:600;margin-top:4px}
.comment-form{margin-top:28px}
.comment-form textarea{width:100%;padding:14px 16px;border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-size:.95rem;font-weight:600;background:var(--bg-color);color:var(--text-color);resize:vertical;min-height:90px;outline:none;box-shadow:var(--shadow-comic);transition:all .2s;box-sizing:border-box}
.comment-form textarea:focus{border-color:var(--primary-dark);box-shadow:var(--shadow-comic-hover);transform:translateY(-1px)}
.comment-submit{margin-top:12px;padding:12px 24px;background:var(--primary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);transition:all .2s}
.comment-submit:hover{transform:translateY(-2px);box-shadow:var(--shadow-comic-hover);background:var(--primary-dark)}
.login-to-comment{padding:18px;background:var(--primary-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-weight:700;text-align:center}
.login-to-comment a{color:var(--primary-dark);font-weight:900;text-decoration:none}
@media(max-width:600px){.blog-detail-wrap{padding:28px 16px 80px}.blog-action-bar{flex-wrap:wrap}}
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link rel="preconnect" href="https://unpkg.com" crossorigin>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://arigatodevan.com"},{"@type":"ListItem","position":2,"name":"Blogs","item":"https://arigatodevan.com/blogs.php"},{"@type":"ListItem","position":3,"name":"<?= htmlspecialchars(
        addslashes($blog["meta_title"] ?? $blog["title"]),
    ) ?>","item":"https://arigatodevan.com/blog.php?slug=<?= urlencode(
    $blog["slug"],
) ?>"}]}
    </script>
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
      <span style="font-weight:600;">@arigato.devan</span><span class="pulse-dot"></span><span style="font-weight:800;font-size:1.1rem;">13K+</span>
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
        <a href="profile.php" style="color:var(--text-color)"><?= renderAvatar(
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

<div class="blog-detail-wrap">
  <!-- Back link -->
  <a href="blogs.php" style="display:inline-flex;align-items:center;gap:6px;font-weight:800;color:var(--text-color);text-decoration:none;margin-bottom:28px;font-size:.9rem;opacity:.7;transition:opacity .2s" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.7"><i class="fa-solid fa-arrow-left"></i> Back to Blogs</a>

  <!-- Hero image -->
  <?php if ($blog["image_path"]): ?>
  <img src="<?= htmlspecialchars(
      $blog["image_path"],
  ) ?>" class="blog-detail-hero-img" alt="<?= htmlspecialchars(
    $blog["title"],
) ?>">
  <?php endif; ?>

  <div class="blog-paper">
  <!-- Title -->
  <h1 class="blog-detail-title"><?= htmlspecialchars($blog["title"]) ?></h1>

  <!-- Meta -->
  <div class="blog-detail-meta">
    <img src="<?= htmlspecialchars(
        !empty($blog["author_avatar"])
            ? $blog["author_avatar"]
            : "https://api.dicebear.com/7.x/avataaars/svg?seed=" .
                urlencode($blog["author_name"] ?? "Admin"),
    ) ?>" class="blog-author-avatar" alt="" onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=<?= urlencode(
    $blog["author_name"] ?? "Admin",
) ?>'">
    <div>
      <div class="blog-author-name"><?= htmlspecialchars(
          $blog["author_name"] ?? "Admin",
      ) ?></div>
      <div class="blog-detail-date"><?= date(
          "d M Y",
          strtotime($blog["created_at"]),
      ) ?></div>
    </div>
  </div>

  <!-- Tags -->
  <?php if ($blog["tags"]): ?>
  <div class="blog-tags">
    <?php foreach (
        array_filter(array_map("trim", explode(",", $blog["tags"])))
        as $tag
    ): ?>
    <span class="blog-tag"><?= htmlspecialchars($tag) ?></span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Content -->
  <div class="blog-content"><?= $blog["content"]
/* HTML stored from editor "&ndash; safe because only admin writes it */
?></div>

  <!-- Like + action bar -->
  <div class="blog-action-bar">
    <button class="blog-like-btn <?= $user_liked
        ? "liked"
        : "" ?>" id="blog-like-btn" data-blog-id="<?= $blog["id"] ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $user_liked
          ? "#fff"
          : "none" ?>" stroke="currentColor" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
      <span id="blog-like-count"><?= (int) $blog["likes_count"] ?></span> Likes
    </button>
    <a href="blogs.php" style="font-weight:700;color:#888;font-size:.9rem;text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> All Blogs</a>
  </div>

  <!-- Comments -->
  <div class="comments-section">
    <h3><i class="fa-solid fa-comment"></i> Comments (<?= count(
        $comments,
    ) ?>)</h3>

    <?php if (count($comments) > 0): ?>
    <div id="comments-list">
      <?php foreach ($comments as $c): ?>
      <div class="comment-item">
        <?= renderAvatar($c["profile_image"] ?? "", "comment-avatar", "") ?>
        <div class="comment-body">
          <div class="comment-name"><?= htmlspecialchars(
              $c["username"] ?? "User",
          ) ?></div>
          <div class="comment-text"><?= nl2br(
              htmlspecialchars($c["comment"]),
          ) ?></div>
          <div class="comment-time"><?= date(
              "d M Y H:i",
              strtotime($c["created_at"]),
          ) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p id="no-comments-msg" style="color:#aaa;font-weight:600;margin-bottom:20px;">Be the first to comment! <i class="fa-solid fa-hand-point-down"></i></p>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_id"])): ?>
    <div class="comment-form">
      <textarea id="comment-input" placeholder="Share your thoughts..."></textarea>
      <button class="comment-submit" id="comment-submit-btn" data-blog-id="<?= $blog[
          "id"
      ] ?>">Post Comment <i class="fa-solid fa-paper-plane"></i></button>
    </div>
    <?php else: ?>
    <div class="login-to-comment">Login to leave a comment <i class="fa-solid fa-arrow-right"></i> <a href="login.php">Sign in with Google</a></div>
    <?php endif; ?>
  </div>
</div>

<footer>
  <div>&copy; 2026 ARIGATO DEVAN. KEEP CREATING.</div>
  <div class="footer-links"><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
</footer>

<script defer src="script.js?v=1778000000"></script>
<script>
// Blog Like
function showToast(msg) {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#fff1b8;color:#2d2a35;border:3px solid #2d2a35;border-radius:14px;padding:12px 22px;font-weight:800;font-family:Outfit,sans-serif;box-shadow:4px 4px 0px #2d2a35;z-index:9999;font-size:.95rem;transition:opacity .3s';
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 2500);
}
const likeBtn = document.getElementById('blog-like-btn');
if (likeBtn) {
  likeBtn.addEventListener('click', () => {
    <?php if (!isset($_SESSION["user_id"])): ?>
    showToast('Login first to like! 💛'); return;
    <?php endif; ?>
    const blogId = likeBtn.dataset.blogId;
    const fd = new FormData(); fd.append('blog_id', blogId);
    fetch('blog_like.php', {method:'POST', body:fd})
      .then(r=>r.json()).then(d=>{
        if(d.success){
          document.getElementById('blog-like-count').textContent = d.likes_count;
          likeBtn.classList.toggle('liked', d.action==='liked');
          const svg = likeBtn.querySelector('svg');
          svg.setAttribute('fill', d.action==='liked' ? '#fff' : 'none');
          svg.setAttribute('stroke', d.action==='liked' ? '#fff' : 'currentColor');
        }
      });
  });
}

// Blog Comment
const submitBtn = document.getElementById('comment-submit-btn');
if (submitBtn) {
  submitBtn.addEventListener('click', () => {
    const input = document.getElementById('comment-input');
    const text = input.value.trim();
    if (!text) return;
    submitBtn.disabled = true; submitBtn.textContent = 'Posting...';
    const fd = new FormData();
    fd.append('blog_id', submitBtn.dataset.blogId);
    fd.append('comment', text);
    fetch('blog_comment.php', {method:'POST', body:fd})
      .then(r=>r.json()).then(d=>{
        if(d.success){
          const noMsg = document.getElementById('no-comments-msg');
          if(noMsg) noMsg.remove();
          let list = document.getElementById('comments-list');
          if(!list){ list = document.createElement('div'); list.id='comments-list'; submitBtn.closest('.comment-form').before(list); }
          const el = document.createElement('div'); el.className='comment-item';
          el.innerHTML = `<img src="${d.avatar}" class="comment-avatar" alt=""><div class="comment-body"><div class="comment-name">${d.username}</div><div class="comment-text">${d.comment.replace(/\n/g,'<br>')}</div><div class="comment-time">${d.time}</div></div>`;
          list.appendChild(el);
          input.value='';
          // Update count
          const h3 = document.querySelector('.comments-section h3');
          if(h3) h3.innerHTML = '<i class="fa-solid fa-comment"></i> Comments (' + list.children.length + ')';
        }
        submitBtn.disabled=false; submitBtn.innerHTML='Post Comment <i class="fa-solid fa-paper-plane"></i>';
      });
  });
}
</script>
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
</script>
</body></html>
