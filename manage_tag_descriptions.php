<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/tag_descriptions_helper.php';

// Check admin role
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    $_SESSION["error_msg"] = "You do not have permission to access the Tag Descriptions Manager.";
    header("Location: index.php");
    exit();
}

$admin_name = $_SESSION["username"] ?? ($_SESSION["user_name"] ?? "Admin");

// 1. AJAX Save Request Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_tag_desc') {
        header('Content-Type: application/json; charset=utf-8');
        $tag = trim((string)($_POST['tag_name'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $res = save_tag_description($pdo, $tag, $desc);
        echo json_encode($res);
        exit;
    }
}

// 2. Sync all discovered tags from prompts and seed if empty
$tag_counts = sync_discovered_tags($pdo);

// 3. Filtering, Searching, and Stats Calculation
$search = trim((string)($_GET['q'] ?? ''));
$filter = trim((string)($_GET['filter'] ?? 'all')); // 'all', 'with_desc', 'missing_desc'
$sort   = trim((string)($_GET['sort'] ?? 'tag_asc')); // 'tag_asc', 'tag_desc', 'used_desc'

// Global stats
$total_tags = (int)$pdo->query("SELECT COUNT(*) FROM tag_descriptions")->fetchColumn();
$with_desc_count = (int)$pdo->query("SELECT COUNT(*) FROM tag_descriptions WHERE description IS NOT NULL AND TRIM(description) != ''")->fetchColumn();
$missing_desc_count = max(0, $total_tags - $with_desc_count);
$completion_pct = $total_tags > 0 ? round(($with_desc_count / $total_tags) * 100) : 0;

// Query items with filter & search
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(tag_name LIKE ? OR description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($filter === 'with_desc') {
    $where[] = "(description IS NOT NULL AND TRIM(description) != '')";
} elseif ($filter === 'missing_desc') {
    $where[] = "(description IS NULL OR TRIM(description) = '')";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM tag_descriptions $whereClause");
$countStmt->execute($params);
$filtered_count = (int)$countStmt->fetchColumn();

// Pagination: 15 tags per page strictly
$per_page = 15;
$total_pages = max(1, (int)ceil($filtered_count / $per_page));
$current_page = max(1, min($total_pages, (int)($_GET['page'] ?? 1)));
$offset = ($current_page - 1) * $per_page;

// Ordering
$orderBy = match ($sort) {
    'tag_desc'    => "tag_name DESC",
    'updated_desc' => "updated_at DESC, id DESC",
    default       => "tag_name ASC",
};

$dataStmt = $pdo->prepare("SELECT * FROM tag_descriptions $whereClause ORDER BY $orderBy LIMIT $per_page OFFSET $offset");
$dataStmt->execute($params);
$tags_list = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tag Descriptions Manager — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include_once "gtag.php"; ?>
<style>
:root {
  --bg: #07060f;
  --surface: #0f0d1e;
  --surface2: #15122b;
  --border: rgba(139, 92, 246, 0.18);
  --border2: rgba(139, 92, 246, 0.08);
  --accent: #8b5cf6;
  --accent2: #c084fc;
  --pink: #f472b6;
  --cyan: #22d3ee;
  --green: #4ade80;
  --yellow: #fbbf24;
  --red: #f87171;
  --text: #e2e0ff;
  --muted: #9490bb;
  --font: 'Inter', sans-serif;
  --font-head: 'Outfit', sans-serif;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { background:var(--bg); color:var(--text); font-family:var(--font); min-height:100vh; overflow-x:hidden; }

.main { margin-left:220px; padding:28px 32px 80px; max-width:1400px; }
.topbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
.tb-title { font-family:var(--font-head); font-size:1.45rem; font-weight:900; display:flex; align-items:center; gap:10px; }
.tb-title i { color:var(--yellow); }

/* STATS ROW */
.stats-cards-row {
  display:grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap:14px;
  margin-bottom:24px;
}
.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 18px 20px;
  display:flex;
  align-items:center;
  gap:16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.25);
  transition: transform .2s ease, border-color .2s ease;
}
.stat-card:hover {
  transform: translateY(-2px);
  border-color: rgba(139, 92, 246, 0.35);
}
.sc-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size: 1.25rem;
  flex-shrink: 0;
}
.sc-val { font-size: 1.7rem; font-weight: 900; font-family: var(--font-head); line-height: 1.1; margin-bottom: 2px; }
.sc-label { font-size: 0.72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; }

.sc-purple .sc-icon { background: rgba(139,92,246,0.15); color: var(--accent2); }
.sc-purple .sc-val { color: var(--accent2); }
.sc-green .sc-icon { background: rgba(74,222,128,0.15); color: var(--green); }
.sc-green .sc-val { color: var(--green); }
.sc-yellow .sc-icon { background: rgba(251,191,36,0.15); color: var(--yellow); }
.sc-yellow .sc-val { color: var(--yellow); }
.sc-cyan .sc-icon { background: rgba(34,211,238,0.15); color: var(--cyan); }
.sc-cyan .sc-val { color: var(--cyan); }

/* CONTROLS BAR */
.controls-bar {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 16px 20px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:14px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.search-box {
  position:relative;
  flex: 1;
  min-width: 240px;
}
.search-box i {
  position:absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: 0.85rem;
}
.search-box input {
  width: 100%;
  padding: 10px 14px 10px 38px;
  background: rgba(0,0,0,0.3);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: #fff;
  font-size: 0.85rem;
  outline: none;
  box-sizing: border-box;
  transition: border-color .15s ease;
}
.search-box input:focus {
  border-color: var(--accent2);
}

.filters-group {
  display:flex;
  align-items:center;
  gap: 8px;
  flex-wrap: wrap;
}
.filter-btn {
  display: inline-flex;
  align-items:center;
  gap:6px;
  padding: 7px 14px;
  border-radius: 8px;
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--border2);
  color: var(--muted);
  font-size: 0.78rem;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  transition: all .15s ease;
}
.filter-btn:hover {
  background: rgba(139,92,246,0.1);
  color: var(--text);
  border-color: var(--border);
}
.filter-btn.active {
  background: rgba(139,92,246,0.2);
  color: var(--accent2);
  border-color: var(--accent);
}

/* TAGS LIST */
.tag-cards-grid {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-bottom: 28px;
}
.tag-item-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 18px 20px;
  display:flex;
  flex-direction: column;
  gap: 12px;
  transition: border-color .2s ease, box-shadow .2s ease;
}
.tag-item-card:hover {
  border-color: rgba(139, 92, 246, 0.35);
  box-shadow: 0 8px 24px rgba(0,0,0,0.28);
}
.tag-card-header {
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 10px;
  flex-wrap: wrap;
}
.tag-card-badge {
  display:inline-flex;
  align-items:center;
  gap: 8px;
}
.tag-pill-badge {
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding: 5px 12px;
  border-radius: 8px;
  background: rgba(251,191,36,0.12);
  border: 1px solid rgba(251,191,36,0.3);
  color: var(--yellow);
  font-size: 0.88rem;
  font-weight: 800;
  letter-spacing: 0.2px;
}
.tag-uses-count {
  font-size: 0.74rem;
  color: var(--muted);
  font-weight: 600;
  display:inline-flex;
  align-items:center;
  gap: 5px;
}
.tag-gallery-link {
  display:inline-flex;
  align-items:center;
  gap: 6px;
  font-size: 0.74rem;
  font-weight: 700;
  color: var(--cyan);
  text-decoration: none;
  padding: 5px 10px;
  border-radius: 6px;
  background: rgba(34,211,238,0.08);
  border: 1px solid rgba(34,211,238,0.2);
  transition: all .15s ease;
}
.tag-gallery-link:hover {
  background: rgba(34,211,238,0.18);
  color: #fff;
}

.tag-card-body {
  position:relative;
}
.tag-desc-textarea {
  width: 100%;
  padding: 12px 14px;
  border-radius: 10px;
  background: rgba(0,0,0,0.35);
  border: 1px solid var(--border);
  color: #f8fafc;
  font-size: 0.88rem;
  line-height: 1.5;
  font-family: inherit;
  resize: vertical;
  min-height: 72px;
  box-sizing: border-box;
  transition: border-color .15s ease, box-shadow .15s ease;
}
.tag-desc-textarea:focus {
  outline: none;
  border-color: var(--accent2);
  box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
}
.tag-desc-textarea.limit-exceeded {
  border-color: var(--red) !important;
  box-shadow: 0 0 0 3px rgba(248,113,113,0.2) !important;
}

.tag-card-footer {
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 12px;
  flex-wrap: wrap;
}
.tag-word-counter {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--muted);
  display:flex;
  align-items:center;
  gap: 6px;
}
.tag-word-counter.limit-exceeded {
  color: var(--red) !important;
}
.tag-card-actions {
  display:flex;
  align-items:center;
  gap: 8px;
}
.btn-save-tag {
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding: 7px 18px;
  border-radius: 8px;
  background: linear-gradient(135deg, #8b5cf6, #c084fc);
  color: #0f172a;
  border: none;
  font-size: 0.78rem;
  font-weight: 800;
  cursor: pointer;
  transition: all .15s ease;
}
.btn-save-tag:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(139,92,246,0.4);
}
.btn-save-tag.is-saved {
  background: var(--green) !important;
  color: #0f172a !important;
}
.btn-clear-tag {
  padding: 7px 12px;
  border-radius: 8px;
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--border2);
  color: var(--muted);
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  transition: all .15s ease;
}
.btn-clear-tag:hover {
  background: rgba(248,113,113,0.1);
  color: var(--red);
  border-color: rgba(248,113,113,0.3);
}

/* PAGINATION */
.pagination-wrap {
  display:flex;
  align-items:center;
  justify-content:center;
  gap: 6px;
  flex-wrap: wrap;
  margin-top: 20px;
}
.page-btn {
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-width: 36px;
  height: 36px;
  padding: 0 10px;
  border-radius: 8px;
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
  transition: all .15s ease;
}
.page-btn:hover {
  background: rgba(139,92,246,0.15);
  color: #fff;
  border-color: var(--accent2);
}
.page-btn.active {
  background: var(--accent) !important;
  color: #fff !important;
  border-color: var(--accent) !important;
}
.page-btn.disabled {
  opacity: 0.35;
  pointer-events: none;
}

@media (max-width: 768px) {
  .main { margin-left: 0; padding: 16px 14px 90px; }
  .stats-cards-row { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body class="no-site-cursor">

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<main class="main">
  <!-- TOPBAR -->
  <div class="topbar">
    <div class="tb-title">
      <i class="fa-solid fa-tags"></i> Tag Descriptions Manager
    </div>
    <div style="font-size:0.78rem;color:var(--muted);font-weight:600;">
      Displaying <strong>15 tags</strong> per page &bull; Word limit &lt; 40 words
    </div>
  </div>

  <!-- STATS ROW -->
  <div class="stats-cards-row">
    <div class="stat-card sc-purple">
      <div class="sc-icon"><i class="fa-solid fa-hashtag"></i></div>
      <div>
        <div class="sc-val"><?= $total_tags ?></div>
        <div class="sc-label">Total Unique Tags</div>
      </div>
    </div>
    <div class="stat-card sc-green">
      <div class="sc-icon"><i class="fa-solid fa-circle-check"></i></div>
      <div>
        <div class="sc-val"><?= $with_desc_count ?></div>
        <div class="sc-label">With Description</div>
      </div>
    </div>
    <div class="stat-card sc-yellow">
      <div class="sc-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div>
        <div class="sc-val"><?= $missing_desc_count ?></div>
        <div class="sc-label">Missing Description</div>
      </div>
    </div>
    <div class="stat-card sc-cyan">
      <div class="sc-icon"><i class="fa-solid fa-chart-pie"></i></div>
      <div>
        <div class="sc-val"><?= $completion_pct ?>%</div>
        <div class="sc-label">Completion Rate</div>
      </div>
    </div>
  </div>

  <!-- CONTROLS BAR -->
  <div class="controls-bar">
    <form method="GET" action="manage_tag_descriptions.php" class="search-box">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search tags or descriptions..." onkeydown="if(event.key==='Enter')this.form.submit()">
      <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
    </form>

    <div class="filters-group">
      <?php
      $qParam = $search !== '' ? '&q=' . urlencode($search) : '';
      ?>
      <a href="manage_tag_descriptions.php?filter=all<?= $qParam ?>" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
        All (<?= $total_tags ?>)
      </a>
      <a href="manage_tag_descriptions.php?filter=with_desc<?= $qParam ?>" class="filter-btn <?= $filter === 'with_desc' ? 'active' : '' ?>">
        <i class="fa-solid fa-check" style="color:var(--green)"></i> With Description (<?= $with_desc_count ?>)
      </a>
      <a href="manage_tag_descriptions.php?filter=missing_desc<?= $qParam ?>" class="filter-btn <?= $filter === 'missing_desc' ? 'active' : '' ?>">
        <i class="fa-solid fa-circle-exclamation" style="color:var(--yellow)"></i> Missing (<?= $missing_desc_count ?>)
      </a>
    </div>
  </div>

  <!-- TAGS LIST -->
  <?php if (empty($tags_list)): ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:48px 20px;text-align:center;color:var(--muted);">
      <i class="fa-solid fa-tags" style="font-size:2.4rem;margin-bottom:12px;opacity:0.35;"></i>
      <div style="font-weight:800;font-size:1.1rem;color:var(--text);margin-bottom:6px;">No tags found</div>
      <p style="font-size:0.84rem;">Try adjusting your search query or filter options.</p>
    </div>
  <?php else: ?>
    <div class="tag-cards-grid">
      <?php foreach ($tags_list as $t): 
        $tname = $t['tag_name'];
        $tdesc = $t['description'] ?? '';
        $wcount = count_tag_words($tdesc);
        $usedCount = $tag_counts[$tname] ?? 0;
      ?>
        <div class="tag-item-card" id="tag-card-<?= md5($tname) ?>">
          <div class="tag-card-header">
            <div class="tag-card-badge">
              <span class="tag-pill-badge">#<?= htmlspecialchars($tname) ?></span>
              <span class="tag-uses-count">
                <i class="fa-solid fa-layer-group"></i> Used in <?= $usedCount ?> <?= $usedCount === 1 ? 'prompt' : 'prompts' ?>
              </span>
            </div>
            <a href="gallery.php?tag=<?= urlencode($tname) ?>" target="_blank" class="tag-gallery-link" title="Open this collection in Gallery">
              <span>View in Gallery</span> <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
          </div>

          <div class="tag-card-body">
            <textarea 
              class="tag-desc-textarea <?= $wcount > 40 ? 'limit-exceeded' : '' ?>" 
              id="desc-<?= md5($tname) ?>" 
              placeholder="Write a creative short description under 40 words for this tag..." 
              oninput="handleTagTextInput('<?= md5($tname) ?>')"
            ><?= htmlspecialchars($tdesc) ?></textarea>
          </div>

          <div class="tag-card-footer">
            <div class="tag-word-counter <?= $wcount > 40 ? 'limit-exceeded' : '' ?>" id="wc-<?= md5($tname) ?>">
              <i class="fa-solid fa-pen-ruler"></i>
              <span><?= $wcount ?></span> / 40 words
              <span class="limit-warn" style="display:<?= $wcount > 40 ? 'inline' : 'none' ?>;margin-left:8px;color:var(--red);">
                (Exceeds 40 words!)
              </span>
            </div>
            <div class="tag-card-actions">
              <button type="button" class="btn-clear-tag" onclick="clearTagDesc('<?= md5($tname) ?>')">Clear</button>
              <button type="button" class="btn-save-tag" id="btn-save-<?= md5($tname) ?>" onclick="saveTagDesc('<?= htmlspecialchars(addslashes($tname)) ?>', '<?= md5($tname) ?>')">
                <i class="fa-solid fa-floppy-disk"></i> Save
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- PAGINATION (15 items per page) -->
    <?php if ($total_pages > 1): ?>
      <div class="pagination-wrap">
        <?php
        $basePageUrl = "manage_tag_descriptions.php?filter=" . urlencode($filter) . $qParam . "&page=";
        ?>
        <a href="<?= $basePageUrl . max(1, $current_page - 1) ?>" class="page-btn <?= $current_page <= 1 ? 'disabled' : '' ?>">
          <i class="fa-solid fa-chevron-left"></i>
        </a>

        <?php
        $startPage = max(1, $current_page - 2);
        $endPage = min($total_pages, $current_page + 2);
        if ($startPage > 1): ?>
          <a href="<?= $basePageUrl ?>1" class="page-btn">1</a>
          <?php if ($startPage > 2): ?><span style="color:var(--muted);padding:0 4px;">...</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
          <a href="<?= $basePageUrl . $p ?>" class="page-btn <?= $p === $current_page ? 'active' : '' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>

        <?php if ($endPage < $total_pages): ?>
          <?php if ($endPage < $total_pages - 1): ?><span style="color:var(--muted);padding:0 4px;">...</span><?php endif; ?>
          <a href="<?= $basePageUrl . $total_pages ?>" class="page-btn"><?= $total_pages ?></a>
        <?php endif; ?>

        <a href="<?= $basePageUrl . min($total_pages, $current_page + 1) ?>" class="page-btn <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
          <i class="fa-solid fa-chevron-right"></i>
        </a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<script>
function countWords(str) {
  str = (str || '').trim();
  if (!str) return 0;
  return str.split(/\s+/).filter(Boolean).length;
}

function handleTagTextInput(idHash) {
  var ta = document.getElementById('desc-' + idHash);
  var wcEl = document.getElementById('wc-' + idHash);
  if (!ta || !wcEl) return;

  var wc = countWords(ta.value);
  var numSpan = wcEl.querySelector('span');
  if (numSpan) numSpan.textContent = wc;
  var warnSpan = wcEl.querySelector('.limit-warn');

  if (wc > 40) {
    ta.classList.add('limit-exceeded');
    wcEl.classList.add('limit-exceeded');
    if (warnSpan) warnSpan.style.display = 'inline';
  } else {
    ta.classList.remove('limit-exceeded');
    wcEl.classList.remove('limit-exceeded');
    if (warnSpan) warnSpan.style.display = 'none';
  }
}

function clearTagDesc(idHash) {
  var ta = document.getElementById('desc-' + idHash);
  if (!ta) return;
  ta.value = '';
  handleTagTextInput(idHash);
}

function saveTagDesc(tagName, idHash) {
  var ta = document.getElementById('desc-' + idHash);
  var btn = document.getElementById('btn-save-' + idHash);
  if (!ta || !btn) return;

  var desc = ta.value.trim();
  var wc = countWords(desc);
  if (wc > 40) {
    alert('Description exceeds 40 words limit (currently ' + wc + ' words). Please shorten it before saving.');
    return;
  }

  var origHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

  var fd = new FormData();
  fd.append('action', 'save_tag_desc');
  fd.append('tag_name', tagName);
  fd.append('description', desc);

  fetch('manage_tag_descriptions.php', {
    method: 'POST',
    body: fd
  })
  .then(function(r) { return r.json(); })
  .then(function(res) {
    btn.disabled = false;
    if (res.success) {
      btn.classList.add('is-saved');
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Saved!';
      setTimeout(function() {
        btn.classList.remove('is-saved');
        btn.innerHTML = origHtml;
      }, 2000);
    } else {
      btn.innerHTML = origHtml;
      alert(res.message || 'Failed to save description.');
    }
  })
  .catch(function(err) {
    btn.disabled = false;
    btn.innerHTML = origHtml;
    alert('Network error while saving description.');
  });
}
</script>
</body>
</html>
