<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';
require_once 'slug_helper.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$prompts = [];
try {
    $prompts = $pdo->query('SELECT id, slug, title, thumbnail_image, category, is_visible, created_at FROM not_mine_prompts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $prompts = [];
}
$total = count($prompts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Not Mine — Prompt Links</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--bg:#0b0b10;--surface:#141419;--surface-2:#1a1a22;--border:#252530;--text:#ededf0;--muted:#72728a;--accent:#F5709D;--soft:#11FFC9;--green:#34d399}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:Inter,system-ui,sans-serif}
.sidebar{position:fixed;inset:0 auto 0 0;width:250px;background:var(--surface);border-right:1px solid var(--border);padding:28px 16px}
.sb-brand{display:flex;align-items:center;gap:10px;font-weight:900;color:var(--accent);margin-bottom:30px}
.sb-sec{font-size:.6rem;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin:20px 0 10px;padding:0 8px}
.sb-link{display:flex;gap:10px;align-items:center;padding:10px 12px;border-radius:10px;color:var(--muted);text-decoration:none}
.sb-link.active{background:rgba(245,112,157,.16);color:var(--soft);border:1px solid rgba(245,112,157,.2)}
.main{margin-left:250px;padding:40px 48px 80px;max-width:1100px}
.head{margin-bottom:20px}.head h1{font-size:1.45rem;font-weight:900;display:flex;align-items:center;gap:10px}
.head h1 i{color:var(--accent)}
.head p{color:var(--muted);font-size:.85rem;margin-top:8px}
.count-badge{display:inline-flex;align-items:center;padding:5px 12px;border-radius:999px;font-size:.72rem;font-weight:800;background:rgba(245,112,157,.12);color:var(--soft);border:1px solid rgba(245,112,157,.2);margin-left:8px}
.list-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden}
.list-card h2{padding:18px 22px;border-bottom:1px solid var(--border);font-size:1rem;font-weight:800}
.links-hint{padding:12px 22px 14px;font-size:.78rem;color:var(--muted);border-bottom:1px solid var(--border)}
.links-hint code{color:var(--soft)}
.links-search{width:calc(100% - 44px);margin:14px 22px 16px;height:42px;padding:0 12px;background:rgba(11,11,16,.65);border:1px solid #2f3140;border-radius:12px;color:var(--text);font-size:.82rem}
.links-search:focus{outline:none;border-color:#f472b6;box-shadow:0 0 0 3px rgba(245,112,157,.12)}
.links-table{width:100%;border-collapse:collapse}
.links-table th{font-size:.65rem;color:var(--muted);letter-spacing:.08em;text-transform:uppercase;text-align:left;padding:12px 14px;background:var(--surface-2)}
.links-table td{padding:12px 14px;border-top:1px solid var(--border);font-size:.82rem;vertical-align:middle}
.list-thumb{width:50px;height:50px;border-radius:10px;object-fit:cover;border:1px solid var(--border)}
.link-slug{font-size:.68rem;color:var(--muted);font-family:ui-monospace,Consolas,monospace;margin-top:3px}
.cat-badge{padding:4px 10px;border-radius:999px;font-size:.65rem;font-weight:800;text-transform:uppercase}
.cat-boys{background:rgba(59,130,246,.12);color:#60a5fa}.cat-girls{background:rgba(236,72,153,.12);color:#f472b6}.cat-couple{background:rgba(168,85,247,.12);color:#c084fc}.cat-family{background:rgba(52,211,153,.12);color:#34d399}.cat-creativity{background:rgba(250,204,21,.12);color:#eab308}
.copy-link-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;border:1px solid rgba(245,112,157,.25);background:rgba(245,112,157,.1);color:var(--soft);font-weight:800;font-size:.74rem;cursor:pointer;font-family:inherit}
.copy-link-btn:hover{filter:brightness(1.06)}
.copy-link-btn.copied{color:var(--green);border-color:rgba(52,211,153,.3);background:rgba(52,211,153,.08)}
.links-empty{padding:30px;color:var(--muted);text-align:center;font-size:.85rem}
.vis-off{font-size:.65rem;font-weight:800;color:#fda4af}
@media(max-width:900px){.sidebar{display:none}.main{margin:0;padding:16px}}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sb-brand"><i class="fa-solid fa-ban"></i> Not Mine</div>
  <div class="sb-sec">Not Mine</div>
  <a href="not_mine_admin.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
  <a href="not_mine_manage.php" class="sb-link"><i class="fa-solid fa-table-list"></i> <span>Manage Prompts</span></a>
  <a href="not_mine_links.php" class="sb-link active"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
  <a href="not_mine.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Page</span></a>
  <div class="sb-sec">Back</div>
  <a href="dashboard.php" class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
</aside>

<div class="main">
  <div class="head">
    <h1><i class="fa-solid fa-link"></i> Prompt Share Links <span class="count-badge"><?= $total ?></span></h1>
    <p>Upload ke baad yahan se direct link copy karo. Format: <code>/not-mine/slug</code></p>
  </div>

  <div class="list-card">
    <?php if (empty($prompts)): ?>
      <div class="links-empty"><i class="fa-solid fa-folder-open"></i><br><br>Koi prompt nahi — pehle upload karo.</div>
    <?php else: ?>
      <input type="text" class="links-search" id="linksSearch" placeholder="Search title or slug..." oninput="filterLinks(this.value)">
      <table class="links-table">
        <thead><tr><th>Thumb</th><th>Title</th><th>Category</th><th>Status</th><th>Copy Link</th></tr></thead>
        <tbody id="linksBody">
        <?php foreach ($prompts as $lp): ?>
          <?php
            $share_url = nm_prompt_share_url($lp);
            $search_key = strtolower(($lp['title'] ?? '') . ' ' . ($lp['slug'] ?? ''));
          ?>
          <tr data-search="<?= htmlspecialchars($search_key) ?>">
            <td><img src="<?= htmlspecialchars($lp['thumbnail_image'] ?? '') ?>" class="list-thumb" alt=""></td>
            <td style="max-width:280px">
              <div style="font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($lp['title'] ?? '') ?></div>
              <div class="link-slug">/not-mine/<?= htmlspecialchars($lp['slug'] ?? '') ?></div>
            </td>
            <td><span class="cat-badge cat-<?= htmlspecialchars($lp['category'] ?? '') ?>"><?= htmlspecialchars(ucfirst($lp['category'] ?? '')) ?></span></td>
            <td><?= !empty($lp['is_visible']) ? '<span style="color:var(--green);font-weight:700;font-size:.72rem">Live</span>' : '<span class="vis-off">Hidden</span>' ?></td>
            <td>
              <input type="hidden" id="link-<?= (int) $lp['id'] ?>" value="<?= htmlspecialchars($share_url) ?>">
              <button type="button" class="copy-link-btn" onclick="nmCopyShare('link-<?= (int) $lp['id'] ?>', this)"><i class="fa-solid fa-copy"></i> Copy Link</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <p id="linksEmpty" class="links-empty" style="display:none">Koi match nahi mila.</p>
    <?php endif; ?>
  </div>
</div>

<script>
function nmCopyShare(inputId, btn) {
  var input = document.getElementById(inputId);
  var text = input ? input.value : '';
  if (!text) return;
  function onOk() {
    var orig = btn.innerHTML;
    btn.classList.add('copied');
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
    setTimeout(function() { btn.classList.remove('copied'); btn.innerHTML = orig; }, 1600);
  }
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(onOk).catch(function() {
      if (input.select) input.select();
      try { document.execCommand('copy'); onOk(); } catch (e) { window.prompt('Copy link:', text); }
    });
  } else {
    if (input.select) input.select();
    try { document.execCommand('copy'); onOk(); } catch (e) { window.prompt('Copy link:', text); }
  }
}

function filterLinks(q) {
  q = (q || '').toLowerCase().trim();
  var rows = document.querySelectorAll('#linksBody tr');
  var shown = 0;
  rows.forEach(function(row) {
    var ok = !q || (row.getAttribute('data-search') || '').indexOf(q) !== -1;
    row.style.display = ok ? '' : 'none';
    if (ok) shown++;
  });
  var empty = document.getElementById('linksEmpty');
  if (empty) empty.style.display = shown === 0 && rows.length > 0 ? 'block' : 'none';
}
</script>
</body>
</html>
