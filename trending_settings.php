<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] = "You do not have permission to access trending settings.";
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_trending_id"], $_POST["is_trending"])) {
    $pid = (int) $_POST["toggle_trending_id"];
    $val = (int) $_POST["is_trending"] ? 1 : 0;
    if ($val === 1) {
        $max = (int) $pdo->query("SELECT COALESCE(MAX(trending_order), 0) FROM prompts")->fetchColumn();
        $pdo->prepare("UPDATE prompts SET is_trending = 1, trending_order = ? WHERE id = ?")->execute([$max + 1, $pid]);
    } else {
        $pdo->prepare("UPDATE prompts SET is_trending = 0 WHERE id = ?")->execute([$pid]);
    }
    echo "OK";
    exit;
}

$prompts = $pdo->query("
    SELECT id, title, image_path, prompt_type, likes_count, is_trending, is_trial, created_at
    FROM prompts
    WHERE (is_trial = 0 OR is_trial IS NULL)
    ORDER BY is_trending DESC, trending_order DESC, likes_count DESC, created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$trending_count = 0;
foreach ($prompts as $p) {
    if (!empty($p["is_trending"])) {
        $trending_count++;
    }
}

$admin_name = $_SESSION["username"] ?? ($_SESSION["user_name"] ?? "Admin");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trending Settings — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include_once "gtag.php"; ?>
<style>
:root{--bg:#07060f;--surface:#0f0d1e;--border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);--accent:#8b5cf6;--accent2:#c084fc;--pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;--yellow:#fbbf24;--text:#e2e0ff;--muted:#9490bb;--font:'Inter',sans-serif}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:var(--font);min-height:100vh}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:220px;background:rgba(7,6,15,0.98);border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column}
.sb-logo{padding:20px 18px 14px;border-bottom:1px solid var(--border2)}
.sb-brand{font-size:.72rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;display:flex;align-items:center;gap:8px}
.sb-admin{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border2)}
.sb-av-ph{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff}
.sb-uname{font-size:.78rem;font-weight:800}.sb-role{font-size:.6rem;font-weight:700;color:var(--accent2);text-transform:uppercase}
.sb-nav{flex:1;overflow-y:auto;padding:10px 8px}
.sb-sec{font-size:.58rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 10px 5px}
.sb-link{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:600;color:var(--muted);text-decoration:none;margin-bottom:1px;border:1px solid transparent}
.sb-link:hover{background:rgba(139,92,246,0.08);color:var(--text)}
.sb-link.active{background:rgba(139,92,246,0.15);color:var(--accent2);border-color:var(--border)}
.sb-link i{width:16px;text-align:center}
.sb-bottom{padding:12px 8px;border-top:1px solid var(--border2)}
.sb-logout{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:700;color:#f87171;text-decoration:none}
.main{margin-left:220px;padding:28px 32px 80px}
.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap}
.tb-title{font-size:1.35rem;font-weight:900;display:flex;align-items:center;gap:10px}
.tb-title i{color:var(--pink)}
.info-box{background:rgba(139,92,246,0.08);border:1px solid var(--border);border-radius:14px;padding:16px 18px;margin-bottom:22px;font-size:.85rem;color:var(--muted);line-height:1.55}
.info-box strong{color:var(--accent2)}
.stat-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;background:rgba(244,114,182,0.12);border:1px solid rgba(244,114,182,0.25);color:var(--pink);font-size:.75rem;font-weight:800}
.search-bar{width:100%;max-width:360px;padding:11px 14px;border-radius:12px;border:1px solid var(--border);background:var(--surface);color:var(--text);font-size:.85rem;margin-bottom:18px}
.search-bar:focus{outline:none;border-color:var(--accent)}
.trend-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.trend-card{background:var(--surface);border:1px solid var(--border2);border-radius:16px;overflow:hidden;display:flex;gap:12px;padding:12px;align-items:center;transition:border-color .2s,box-shadow .2s}
.trend-card.is-on{border-color:rgba(244,114,182,0.45);box-shadow:0 0 0 1px rgba(244,114,182,0.12)}
.trend-thumb{width:72px;height:96px;border-radius:10px;object-fit:cover;flex-shrink:0;background:#1a1630}
.trend-meta{flex:1;min-width:0}
.trend-title{font-size:.88rem;font-weight:800;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.trend-sub{font-size:.72rem;color:var(--muted);display:flex;gap:10px;flex-wrap:wrap}
.trend-sub span{display:inline-flex;align-items:center;gap:4px}
.toggle-wrap{flex-shrink:0}
.toggle{position:relative;width:48px;height:28px;display:inline-block}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:#2a2540;border-radius:999px;cursor:pointer;transition:.25s;border:1px solid var(--border)}
.toggle-slider:before{content:'';position:absolute;width:20px;height:20px;left:3px;top:3px;background:#6b6688;border-radius:50%;transition:.25s}
.toggle input:checked + .toggle-slider{background:linear-gradient(135deg,var(--pink),var(--accent));border-color:transparent}
.toggle input:checked + .toggle-slider:before{transform:translateX(20px);background:#fff}
.empty-msg{grid-column:1/-1;text-align:center;padding:40px;color:var(--muted)}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.main{margin-left:58px;padding:20px 16px 80px}}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:14px 14px 90px}}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sb-logo"><div class="sb-brand"><i class="fa-solid fa-shield-halved"></i> <span>Arigato Admin</span></div></div>
  <div class="sb-admin">
    <div class="sb-av-ph"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
    <div><div class="sb-uname"><?= htmlspecialchars($admin_name) ?></div><div class="sb-role">Admin</div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-sec">Overview</div>
    <a href="dashboard.php" class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
    <a href="analytics.php" class="sb-link"><i class="fa-solid fa-chart-line"></i> <span>Analytics</span></a>
    <div class="sb-sec">Content</div>
    <a href="upload_prompt.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="manage_prompts.php" class="sb-link"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
    <a href="prompt_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="potd_manager.php" class="sb-link"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
    <a href="trending_settings.php" class="sb-link active"><i class="fa-solid fa-fire-flame-curved"></i> <span>Trending Settings</span></a>
    <div class="sb-sec">Blog</div>
    <a href="blog_admin.php" class="sb-link"><i class="fa-solid fa-pen-nib"></i> <span>Blog Admin</span></a>
    <a href="blog_create.php" class="sb-link"><i class="fa-solid fa-plus"></i> <span>New Post</span></a>
    <div class="sb-sec">Community</div>
    <a href="feedback_admin.php" class="sb-link"><i class="fa-solid fa-comments"></i> <span>Feedbacks</span></a>
    <div class="sb-sec">Users</div>
    <a href="user_management.php" class="sb-link"><i class="fa-solid fa-users"></i> <span>Users</span></a>
    <div class="sb-sec">Tools</div>
    <a href="gallery.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Gallery</span></a>
  </nav>
  <div class="sb-bottom">
    <a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-fire-flame-curved"></i> Trending Settings</div>
    <span class="stat-pill"><i class="fa-solid fa-chart-line"></i> <?= $trending_count ?> in trending</span>
  </div>

  <div class="info-box">
    <strong>Gallery → Trending Now</strong> section mein dikhane ke liye kisi bhi <strong>published</strong> prompt ka toggle ON karo.
    Trial prompts yahan nahi dikhte. Banner images ke liye project root mein <code>banner/</code> folder mein 16:9 images daalo.
  </div>

  <input type="search" class="search-bar" id="trend-search" placeholder="Search by title..." autocomplete="off">

  <div class="trend-grid" id="trend-grid">
    <?php if (empty($prompts)): ?>
      <p class="empty-msg">No published prompts yet.</p>
    <?php else: foreach ($prompts as $p):
      $on = !empty($p["is_trending"]);
    ?>
    <div class="trend-card<?= $on ? ' is-on' : '' ?>" data-title="<?= htmlspecialchars(strtolower($p['title'])) ?>" data-id="<?= (int)$p['id'] ?>">
      <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="" class="trend-thumb" loading="lazy">
      <div class="trend-meta">
        <div class="trend-title"><?= htmlspecialchars($p['title']) ?></div>
        <div class="trend-sub">
          <span><i class="fa-solid fa-heart" style="color:var(--pink)"></i> <?= (int)$p['likes_count'] ?></span>
          <span><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($p['prompt_type'] ?? 'secret') ?></span>
        </div>
      </div>
      <div class="toggle-wrap">
        <label class="toggle" title="Show in Trending">
          <input type="checkbox" class="trend-toggle" data-id="<?= (int)$p['id'] ?>" <?= $on ? 'checked' : '' ?>>
          <span class="toggle-slider"></span>
        </label>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</main>

<script>
document.getElementById('trend-search').addEventListener('input', function() {
  var q = this.value.trim().toLowerCase();
  document.querySelectorAll('.trend-card').forEach(function(card) {
    var title = card.dataset.title || '';
    card.style.display = (!q || title.indexOf(q) !== -1) ? '' : 'none';
  });
});

document.querySelectorAll('.trend-toggle').forEach(function(inp) {
  inp.addEventListener('change', function() {
    var id = this.dataset.id;
    var val = this.checked ? 1 : 0;
    var card = this.closest('.trend-card');
    fetch('trending_settings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'toggle_trending_id=' + id + '&is_trending=' + val
    }).then(function(r) { return r.text(); }).then(function(res) {
      if (res.trim() === 'OK') {
        card.classList.toggle('is-on', val === 1);
      } else {
        inp.checked = !inp.checked;
        alert('Could not update trending. Try again.');
      }
    }).catch(function() {
      inp.checked = !inp.checked;
      alert('Network error. Try again.');
    });
  });
});
</script>
</body>
</html>
