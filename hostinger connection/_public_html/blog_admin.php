<?php
session_start();
require_once 'db.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_msg'] = "Access denied.";
    header("Location: index.php");
    exit();
}

// Flash messages
$success = $_SESSION['success_msg'] ?? '';
$error   = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Fetch all blogs with author name
$blogs_all = $pdo->query("
    SELECT b.*, u.username as author_name, u.avatar as author_avatar_ob, u.profile_image as author_google_img
    FROM blogs b
    LEFT JOIN users u ON b.author_id = u.id
    ORDER BY b.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$blog_count   = count($blogs_all);
$pub_count    = count(array_filter($blogs_all, fn($b) => $b['is_published']));
$draft_count  = $blog_count - $pub_count;
$total_likes  = array_sum(array_column($blogs_all, 'likes_count'));

// Helper: get best avatar for a row
function getAuthorAvatar(array $row): string {
    if (!empty($row['author_avatar_ob']))   return $row['author_avatar_ob'];
    if (!empty($row['author_google_img']))  return $row['author_google_img'];
    return 'https://api.dicebear.com/7.x/avataaars/svg?seed=admin';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Blog Management "â€ Admin | PromptVerse</title>
<link rel="stylesheet" href="style.css?v=1777999999">
<style>
body { background: var(--bg-color); }

.bm-wrap {
    max-width: 1100px;
    margin: 0 auto;
    padding: 30px 40px 100px;
}

.bm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 16px;
}

.bm-title {
    font-size: 2rem;
    font-weight: 900;
}

.bm-stat-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.bm-stat-chip {
    padding: 9px 20px;
    border: 2px solid var(--text-color);
    border-radius: 40px;
    font-weight: 800;
    font-size: 0.83rem;
    box-shadow: 2px 2px 0 var(--text-color);
}

.bm-card {
    background: var(--card-bg);
    border: var(--border-width) solid var(--text-color);
    border-radius: 24px;
    padding: 30px;
    box-shadow: var(--shadow-comic);
}

.bm-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
    padding-bottom: 16px;
    border-bottom: 2px dashed var(--border-color);
}

.bm-card-header h2 {
    font-size: 1.4rem;
    font-weight: 900;
    margin: 0;
}

/* Blog list items */
.blog-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px;
    border: 2px solid var(--border-color);
    border-radius: 14px;
    margin-bottom: 12px;
    transition: all 0.2s;
}

.blog-item:hover {
    border-color: var(--text-color);
    transform: translateX(3px);
    box-shadow: 3px 3px 0 var(--text-color);
}

.blog-item.is-draft {
    border-style: dashed;
    background: rgba(255, 220, 100, 0.06);
}

.blog-item-img {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    object-fit: cover;
    border: 2px solid var(--text-color);
    flex-shrink: 0;
}

.blog-item-img-placeholder {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    border: 2px solid var(--text-color);
    background: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    flex-shrink: 0;
}

.blog-item-details { flex-grow: 1; min-width: 0; }

.blog-item-title {
    font-weight: 800;
    font-size: 1rem;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.blog-item-meta {
    font-size: 0.82rem;
    color: #7D7887;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.blog-item-meta .author-av {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    object-fit: cover;
    border: 1.5px solid var(--primary-color);
    flex-shrink: 0;
}

.status-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 900;
    border: 1.5px solid var(--text-color);
    text-transform: uppercase;
}

.badge-pub  { background: var(--secondary-color); }
.badge-draft{ background: #ddd; }

.action-btns {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.edit-btn {
    background: var(--primary-color);
    color: var(--text-color);
    border: 2px solid var(--text-color);
    padding: 8px 11px;
    border-radius: 10px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 2px 2px 0 var(--text-color);
    transition: all .2s;
    text-decoration: none;
    font-size: .95rem;
    display: inline-flex;
    align-items: center;
}

.edit-btn:hover { transform: translateY(-2px); box-shadow: 4px 4px 0 var(--text-color); }

.delete-btn {
    background: #FF6B6B;
    color: #fff;
    border: 2px solid var(--text-color);
    padding: 8px 12px;
    border-radius: 10px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 2px 2px 0 var(--text-color);
    transition: all .2s;
    flex-shrink: 0;
}

.delete-btn:hover {
    background: #FF4757;
    transform: translateY(-2px) rotate(2deg);
    box-shadow: 4px 4px 0 var(--text-color);
}

.flash-success {
    background: #d9f5e5;
    color: #1e5c36;
    padding: 16px;
    border: var(--border-width) solid var(--text-color);
    border-radius: 12px;
    font-weight: 800;
    margin-bottom: 20px;
    box-shadow: 3px 3px 0 var(--text-color);
}

.flash-error {
    background: #ffe6e6;
    color: #a70000;
    padding: 16px;
    border: var(--border-width) solid var(--text-color);
    border-radius: 12px;
    font-weight: 800;
    margin-bottom: 20px;
    box-shadow: 3px 3px 0 var(--text-color);
}

/* Search bar */
.bm-search {
    width: 100%;
    padding: 11px 16px;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    font-family: var(--font-main);
    font-weight: 600;
    font-size: .9rem;
    background: var(--bg-color);
    color: var(--text-color);
    outline: none;
    transition: all .2s;
    margin-bottom: 18px;
    box-sizing: border-box;
}

.bm-search:focus { border-color: var(--text-color); box-shadow: var(--shadow-comic); }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #7D7887;
    font-weight: 700;
    font-size: 1rem;
}

@media (max-width: 640px) {
    .bm-wrap { padding: 20px 16px 80px; }
    .bm-title { font-size: 1.5rem; }
    .blog-item { flex-wrap: wrap; }
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <div class="logo-area" id="logo-container" onclick="window.location.href='index.php'" style="cursor:pointer;">
        <div class="logo-flipper">
            <div class="logo-front"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo" id="profile-logo"></div>
            <div class="logo-back"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt=""></div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="index.php">HOME</a>
        <a href="gallery.php">GALLERY</a>
        <a href="analytics.php" style="background:var(--secondary-color);border:2px solid var(--text-color);box-shadow:3px 3px 0 var(--text-color);border-radius:20px;"><i class="fa-solid fa-chart-simple"></i> ANALYTICS</a>
        <a href="blog_admin.php" class="active" style="background:var(--primary-color);border:2px solid var(--text-color);box-shadow:3px 3px 0 var(--text-color);border-radius:20px;"><i class="fa-solid fa-pen-nib"></i> BLOGS</a>
    </nav>
    <div class="header-right">
        <div class="header-divider"></div>
            <div style="display:flex; align-items:center; gap:8px;">
                <a href="profile.php" title="Edit Profile">
                    <?= renderAvatar($_SESSION['profile_image'] ?? '', 'admin-avatar', 'Admin', 'style="transition: transform 0.2s;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"') ?>
                </a>
                <a href="dashboard.php" style="color:var(--text-color); font-weight:800;">ADMIN</a>
            </div>
        <a href="login.php?logout=1" class="logout">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            LOGOUT
        </a>
    </div>
</header>

<div class="bm-wrap">

    <?php if($success): ?>
    <div class="flash-success"><?=htmlspecialchars($success)?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="flash-error"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="bm-header">
        <div class="bm-title"><i class="fa-solid fa-pen-nib"></i> Blog Management</div>
        <a href="blog_create.php" class="comic-btn" style="background:var(--secondary-color);text-decoration:none;color:var(--text-color);padding:11px 24px;">+ New Blog Post</a>
    </div>

    <!-- Stats Row -->
    <div class="bm-stat-row">
        <div class="bm-stat-chip" style="background:var(--secondary-color);"><i class="fa-solid fa-rocket"></i> <?=$pub_count?> Published</div>
        <div class="bm-stat-chip" style="background:#ddd;"><i class="fa-solid fa-file-pen"></i> <?=$draft_count?> Drafts</div>
        <div class="bm-stat-chip" style="background:#ffe3f0;"><i class="fa-solid fa-heart"></i> <?=$total_likes?> Total Likes</div>
        <a href="blogs.php" target="_blank" class="bm-stat-chip" style="background:var(--primary-color);text-decoration:none;color:var(--text-color);"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Public Blog</a>
    </div>

    <!-- Blog List Card -->
    <div class="bm-card">
        <div class="bm-card-header">
            <h2>All Blog Posts</h2>
            <div class="badge" style="margin:0;transform:rotate(0);background:var(--primary-color);padding:6px 16px;"><?=$blog_count?> Total</div>
        </div>

        <?php if($blog_count === 0): ?>
        <div class="empty-state">
            No blog posts yet.<br>
            <a href="blog_create.php" style="color:var(--primary-dark);font-weight:800;">Create your first post <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <?php else: ?>
        <!-- Search -->
        <input type="text" id="blog-search" class="bm-search" placeholder="Search by title or tag...">

        <div id="blog-items-list">
        <?php foreach($blogs_all as $bl):
            $authorAv = getAuthorAvatar($bl);
            $tagsArr  = $bl['tags'] ? array_filter(array_map('trim', explode(',', $bl['tags']))) : [];
        ?>
        <div class="blog-item <?=$bl['is_published']?'':'is-draft'?>"
             data-title="<?=strtolower(htmlspecialchars($bl['title']))?>"
             data-tags="<?=strtolower(htmlspecialchars($bl['tags']??''))?>">

            <!-- Thumbnail -->
            <?php if($bl['image_path']): ?>
            <img src="<?=htmlspecialchars($bl['image_path'])?>" class="blog-item-img" alt="">
            <?php else: ?>
            <div class="blog-item-img-placeholder"><i class="fa-solid fa-file-pen"></i></div>
            <?php endif; ?>

            <!-- Details -->
            <div class="blog-item-details">
                <div class="blog-item-title">
                    <?=htmlspecialchars($bl['title'])?>
                    <span class="status-badge <?=$bl['is_published']?'badge-pub':'badge-draft'?>">
                        <?=$bl['is_published']?'Published':'Draft'?>
                    </span>
                </div>
                <div class="blog-item-meta">
                    <?= renderAvatar($authorAv, 'author-av', '') ?>
                    <span style="color:var(--text-color);font-weight:800;"><?=htmlspecialchars($bl['author_name']??'Admin')?></span>
                    <span>Â·</span>
                    <span><i class="fa-solid fa-heart"></i> <?=(int)$bl['likes_count']?></span>
                    <span>Â·</span>
                    <span><?=date('d M Y', strtotime($bl['created_at']))?></span>
                    <?php if(!empty($tagsArr)): ?>
                    <span>Â·</span>
                    <?php foreach(array_slice($tagsArr,0,3) as $t): ?>
                    <span style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:1px 8px;font-size:.72rem;"><?=htmlspecialchars($t)?></span>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <span>Â·</span>
                    <a href="blog.php?slug=<?=urlencode($bl['slug'])?>" target="_blank" style="color:var(--primary-dark);font-weight:800;">View <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Actions -->
            <div class="action-btns">
                <a href="blog_edit.php?id=<?=$bl['id']?>" class="edit-btn" title="Edit"><i class="fa-solid fa-pencil"></i></a>
                <form action="blog_toggle.php" method="POST" style="margin:0;">
                    <input type="hidden" name="blog_id" value="<?=$bl['id']?>">
                    <input type="hidden" name="status" value="<?=$bl['is_published']?0:1?>">
                    <button type="submit" class="edit-btn"
                        style="background:<?=$bl['is_published']?'#ffe3f0':'var(--secondary-color)'?>"
                        title="<?=$bl['is_published']?'Unpublish':'Publish'?>">
                        <?=$bl['is_published']?'<i class="fa-solid fa-eye-slash"></i>':'<i class="fa-solid fa-rocket"></i>'?>
                    </button>
                </form>
                <button class="delete-btn" onclick="confirmDel(<?=$bl['id']?>, '<?=addslashes(htmlspecialchars($bl['title']))?>')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>

</div><!-- end .bm-wrap -->

<!-- Delete Modal -->
<div id="del-modal" style="display:none;position:fixed;inset:0;background:rgba(45,42,53,.45);backdrop-filter:blur(8px);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:36px 32px;max-width:400px;width:90%;box-shadow:8px 8px 0 var(--text-color);text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-trash"></i></div>
        <h3 style="font-size:1.4rem;font-weight:900;margin-bottom:10px;">Delete Blog?</h3>
        <p id="del-modal-name" style="font-weight:700;color:#555;margin-bottom:24px;font-size:.95rem;"></p>
        <div style="display:flex;gap:12px;">
            <button onclick="document.getElementById('del-modal').style.display='none'"
                style="flex:1;padding:14px;background:var(--bg-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;cursor:pointer;box-shadow:var(--shadow-comic);">Cancel</button>
            <form id="del-form" action="blog_delete.php" method="POST" style="flex:1;margin:0;">
                <input type="hidden" id="del-blog-id" name="blog_id" value="">
                <button type="submit" style="width:100%;padding:14px;background:#FF6B6B;color:#fff;border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;cursor:pointer;box-shadow:var(--shadow-comic);">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
// Search filter
const searchInput = document.getElementById('blog-search');
searchInput?.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase();
    document.querySelectorAll('#blog-items-list .blog-item').forEach(item => {
        const t = (item.dataset.title || '') + ' ' + (item.dataset.tags || '');
        item.style.display = t.includes(q) ? '' : 'none';
    });
});

// Delete modal
function confirmDel(id, name) {
    document.getElementById('del-blog-id').value = id;
    document.getElementById('del-modal-name').textContent = '"' + name + '"';
    document.getElementById('del-modal').style.display = 'flex';
}
document.getElementById('del-modal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>




