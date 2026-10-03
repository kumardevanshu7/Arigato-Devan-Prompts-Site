<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] = "You do not have permission to access Home Card Flow.";
    header("Location: index.php");
    exit();
}

$admin_name = $_SESSION["username"] ?? ($_SESSION["user_name"] ?? "Admin");
$msg = '';
$err = '';

// Handle Actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $pid = (int)($_POST['prompt_id'] ?? 0);
        $col = in_array($_POST['column_pos'] ?? '', ['col1', 'col2']) ? $_POST['column_pos'] : 'col1';
        $order = (int)($_POST['sort_order'] ?? 0);

        if ($pid > 0) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO home_card_flow (prompt_id, source_type, column_pos, sort_order, is_active)
                    VALUES (?, 'prompt', ?, ?, 1)
                    ON DUPLICATE KEY UPDATE column_pos = VALUES(column_pos), sort_order = VALUES(sort_order), is_active = 1
                ");
                $stmt->execute([$pid, $col, $order]);
                $msg = "Prompt added to Home Card Flow successfully!";
            } catch (Exception $e) {
                $err = "Error adding prompt: " . $e->getMessage();
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE home_card_flow SET is_active = (1 - is_active) WHERE id = ?")->execute([$id]);
            echo json_encode(['ok' => true]);
            exit;
        }
    } elseif ($action === 'switch_col') {
        $id = (int)($_POST['id'] ?? 0);
        $new_col = $_POST['new_col'] === 'col2' ? 'col2' : 'col1';
        if ($id > 0) {
            $pdo->prepare("UPDATE home_card_flow SET column_pos = ? WHERE id = ?")->execute([$new_col, $id]);
            $msg = "Card moved to " . ($new_col === 'col1' ? 'Column 1 (Up)' : 'Column 2 (Down)');
        }
    } elseif ($action === 'update_order') {
        $id = (int)($_POST['id'] ?? 0);
        $order = (int)($_POST['sort_order'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE home_card_flow SET sort_order = ? WHERE id = ?")->execute([$order, $id]);
            $msg = "Order updated!";
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM home_card_flow WHERE id = ?")->execute([$id]);
            $msg = "Card removed from Home Card Flow.";
        }
    }
}

// Fetch items for Column 1
$col1_items = $pdo->query("
    SELECT f.*, p.title, p.image_path, p.prompt_type, p.likes_count
    FROM home_card_flow f
    JOIN prompts p ON f.prompt_id = p.id
    WHERE f.column_pos = 'col1'
    ORDER BY f.sort_order ASC, f.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch items for Column 2
$col2_items = $pdo->query("
    SELECT f.*, p.title, p.image_path, p.prompt_type, p.likes_count
    FROM home_card_flow f
    JOIN prompts p ON f.prompt_id = p.id
    WHERE f.column_pos = 'col2'
    ORDER BY f.sort_order ASC, f.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// All active prompts for selector dropdown
$all_prompts = $pdo->query("
    SELECT id, title, prompt_type, image_path 
    FROM prompts 
    WHERE (is_trial = 0 OR is_trial IS NULL)
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total_active = 0;
foreach (array_merge($col1_items, $col2_items) as $it) {
    if (!empty($it['is_active'])) $total_active++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Home Card Flow Manager — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include_once "gtag.php"; ?>
<style>
:root{--bg:#07060f;--surface:#0f0d1e;--surface2:#15122b;--border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);--accent:#8b5cf6;--accent2:#c084fc;--pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;--yellow:#fbbf24;--red:#f87171;--text:#e2e0ff;--muted:#9490bb;--font:'Inter',sans-serif;--font-head:'Outfit',sans-serif}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:var(--font);min-height:100vh}

.main{margin-left:220px;padding:28px 32px 80px}
.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap}
.tb-title{font-family:var(--font-head);font-size:1.45rem;font-weight:900;display:flex;align-items:center;gap:10px}
.tb-title i{color:#c084fc}

.stat-pills-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:22px}
.stat-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:999px;background:rgba(192,132,252,0.12);border:1px solid rgba(192,132,252,0.25);color:var(--accent2);font-size:.78rem;font-weight:800}
.stat-pill.pill-green{background:rgba(74,222,128,0.12);border-color:rgba(74,222,128,0.25);color:var(--green)}
.stat-pill.pill-pink{background:rgba(244,114,182,0.12);border-color:rgba(244,114,182,0.25);color:var(--pink)}
.stat-pill.pill-cyan{background:rgba(34,211,238,0.12);border-color:rgba(34,211,238,0.25);color:var(--cyan)}

.info-box{background:rgba(139,92,246,0.08);border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin-bottom:24px;font-size:.88rem;color:var(--muted);line-height:1.6}
.info-box strong{color:var(--accent2)}

/* Add Form Card */
.add-card{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px 24px;margin-bottom:28px;box-shadow:0 8px 30px rgba(0,0,0,0.25)}
.add-card-title{font-family:var(--font-head);font-size:1.05rem;font-weight:800;color:#fff;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.add-form-grid{display:grid;grid-template-columns:2fr 1fr 100px auto;gap:12px;align-items:center}
.form-input{width:100%;padding:10px 14px;border-radius:10px;border:1px solid var(--border);background:#1a1630;color:var(--text);font-family:var(--font);font-size:.85rem}
.form-input:focus{outline:none;border-color:var(--accent)}
.btn-add{padding:10px 20px;border-radius:10px;border:none;background:linear-gradient(135deg,var(--accent),var(--pink));color:#fff;font-family:var(--font-head);font-weight:800;font-size:.9rem;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:transform .15s}
.btn-add:hover{transform:translateY(-1px)}

/* Dual Column Layout */
.flow-columns-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.col-panel{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px;display:flex;flex-direction:column}
.col-panel-head{display:flex;align-items:center;justify-content:space-between;padding-bottom:14px;margin-bottom:14px;border-bottom:1px solid var(--border2)}
.col-panel-title{font-family:var(--font-head);font-size:1.1rem;font-weight:800;display:flex;align-items:center;gap:8px}
.col-dir-badge{padding:3px 10px;border-radius:999px;font-size:.7rem;font-weight:800;text-transform:uppercase}
.dir-up{background:rgba(34,211,238,0.15);color:var(--cyan);border:1px solid rgba(34,211,238,0.3)}
.dir-down{background:rgba(244,114,182,0.15);color:var(--pink);border:1px solid rgba(244,114,182,0.3)}

/* Flow Items List */
.flow-items{display:flex;flex-direction:column;gap:12px}
.flow-item{background:var(--surface2);border:1px solid var(--border2);border-radius:14px;padding:12px 14px;display:flex;align-items:center;gap:14px;transition:border-color .2s}
.flow-item.is-off{opacity:.5;filter:grayscale(60%)}
.flow-thumb{width:56px;height:78px;border-radius:8px;object-fit:cover;background:#1a1630;flex-shrink:0}
.flow-details{flex:1;min-width:0}
.flow-title{font-size:.88rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:4px}
.flow-meta{font-size:.72rem;color:var(--muted);display:flex;align-items:center;gap:10px}
.flow-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}

.order-input{width:50px;padding:5px 8px;border-radius:6px;border:1px solid var(--border);background:#1a1630;color:var(--text);font-size:.8rem;text-align:center}
.btn-action{padding:6px 10px;border-radius:8px;border:1px solid var(--border);background:rgba(255,255,255,0.05);color:var(--text);font-size:.75rem;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:background .15s}
.btn-action:hover{background:rgba(255,255,255,0.12)}
.btn-del{border-color:rgba(248,113,113,0.3);color:var(--red)}
.btn-del:hover{background:rgba(248,113,113,0.15)}

/* Toggle Switch */
.toggle{position:relative;width:40px;height:24px;display:inline-block}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:#2a2540;border-radius:999px;cursor:pointer;transition:.25s;border:1px solid var(--border)}
.toggle-slider:before{content:'';position:absolute;width:16px;height:16px;left:3px;top:3px;background:#6b6688;border-radius:50%;transition:.25s}
.toggle input:checked + .toggle-slider{background:linear-gradient(135deg,var(--pink),var(--accent));border-color:transparent}
.toggle input:checked + .toggle-slider:before{transform:translateX(16px);background:#fff}

.alert-msg{padding:12px 18px;border-radius:12px;background:rgba(74,222,128,0.12);border:1px solid rgba(74,222,128,0.3);color:var(--green);margin-bottom:20px;font-size:.88rem;display:flex;align-items:center;gap:8px}
.alert-err{padding:12px 18px;border-radius:12px;background:rgba(248,113,113,0.12);border:1px solid rgba(248,113,113,0.3);color:var(--red);margin-bottom:20px;font-size:.88rem;display:flex;align-items:center;gap:8px}

@media(max-width:1024px){.flow-columns-grid{grid-template-columns:1fr}.add-form-grid{grid-template-columns:1fr}}
@media(max-width:768px){.main{margin-left:0;padding:16px}}
</style>
</head>
<body class="no-site-cursor">

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-arrows-up-down"></i> Home Card Flow Manager</div>
    <a href="index.php#homeHeroFlow" target="_blank" class="stat-pill pill-cyan" style="text-decoration:none"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview on Homepage</a>
  </div>

  <div class="stat-pills-row">
    <span class="stat-pill pill-green"><i class="fa-solid fa-bolt"></i> <?= $total_active ?> Active in Flow</span>
    <span class="stat-pill pill-cyan"><i class="fa-solid fa-arrow-up"></i> <?= count($col1_items) ?> in Column 1 (Up)</span>
    <span class="stat-pill pill-pink"><i class="fa-solid fa-arrow-down"></i> <?= count($col2_items) ?> in Column 2 (Down)</span>
  </div>

  <?php if ($msg): ?>
  <div class="alert-msg"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
  <div class="alert-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="info-box">
    <strong><i class="fa-solid fa-circle-info"></i> How it works:</strong><br>
    Yeh cards Homepage ke naye <strong>Split Hero Section</strong> mein lagte hain. 
    <strong>Column 1</strong> ke cards continuously <strong>Upar (UP ⬆)</strong> scroll hote hain aur <strong>Column 2</strong> ke cards <strong>Neeche (DOWN ⬇)</strong> scroll hote hain. 
    Aap yahan se kisi bhi prompt ko add kar sakte ho, column switch kar sakte ho, ya toggle ON/OFF kar sakte ho.
  </div>

  <!-- ADD PROMPT TO FLOW FORM -->
  <div class="add-card">
    <div class="add-card-title"><i class="fa-solid fa-plus-circle" style="color:var(--pink)"></i> Add Prompt to Hero Flow</div>
    <form method="POST" action="home_card_flow.php" class="add-form-grid">
      <input type="hidden" name="action" value="add">
      <div>
        <select name="prompt_id" class="form-input" required>
          <option value="">-- Select Prompt from Gallery --</option>
          <?php foreach ($all_prompts as $ap): ?>
          <option value="<?= (int)$ap['id'] ?>">#<?= (int)$ap['id'] ?> - <?= htmlspecialchars($ap['title']) ?> (<?= htmlspecialchars($ap['prompt_type']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <select name="column_pos" class="form-input">
          <option value="col1">Column 1 (Flows UP ⬆)</option>
          <option value="col2">Column 2 (Flows DOWN ⬇)</option>
        </select>
      </div>
      <div>
        <input type="number" name="sort_order" class="form-input" placeholder="Order (0)" value="0">
      </div>
      <div>
        <button type="submit" class="btn-add"><i class="fa-solid fa-plus"></i> Add to Flow</button>
      </div>
    </form>
  </div>

  <!-- DUAL COLUMN MANAGEMENT -->
  <div class="flow-columns-grid">
    <!-- COLUMN 1: UPWARD -->
    <div class="col-panel">
      <div class="col-panel-head">
        <div class="col-panel-title">
          <i class="fa-solid fa-arrow-up" style="color:var(--cyan)"></i>
          <span>Column 1 Layer</span>
        </div>
        <span class="col-dir-badge dir-up"><i class="fa-solid fa-arrow-up"></i> Scrolls UP</span>
      </div>

      <div class="flow-items">
        <?php if (empty($col1_items)): ?>
          <p style="text-align:center;color:var(--muted);padding:24px 0;font-size:.85rem">No prompts added to Column 1 yet. (Auto-fallback prompts will show on site until added).</p>
        <?php else: foreach ($col1_items as $item): ?>
          <div class="flow-item <?= empty($item['is_active']) ? 'is-off' : '' ?>" id="item-row-<?= (int)$item['id'] ?>">
            <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="" class="flow-thumb">
            <div class="flow-details">
              <div class="flow-title"><?= htmlspecialchars($item['title']) ?></div>
              <div class="flow-meta">
                <span><i class="fa-solid fa-heart" style="color:#f43f5e"></i> <?= (int)$item['likes_count'] ?></span>
                <span>Type: <?= htmlspecialchars($item['prompt_type']) ?></span>
              </div>
            </div>
            <div class="flow-actions">
              <!-- Switch Column -->
              <form method="POST" action="home_card_flow.php" style="display:inline">
                <input type="hidden" name="action" value="switch_col">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <input type="hidden" name="new_col" value="col2">
                <button type="submit" class="btn-action" title="Move to Column 2"><i class="fa-solid fa-arrow-right"></i> Col 2</button>
              </form>

              <!-- Order Update -->
              <form method="POST" action="home_card_flow.php" style="display:inline-flex;align-items:center;gap:4px">
                <input type="hidden" name="action" value="update_order">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <input type="number" name="sort_order" class="order-input" value="<?= (int)$item['sort_order'] ?>" title="Sort order">
                <button type="submit" class="btn-action" title="Save order"><i class="fa-solid fa-check"></i></button>
              </form>

              <!-- Toggle Active Switch -->
              <label class="toggle" title="Toggle Active">
                <input type="checkbox" <?= !empty($item['is_active']) ? 'checked' : '' ?> onchange="toggleFlowActive(<?= (int)$item['id'] ?>, this)">
                <span class="toggle-slider"></span>
              </label>

              <!-- Delete -->
              <form method="POST" action="home_card_flow.php" style="display:inline" onsubmit="return confirm('Remove this prompt from Home Card Flow?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <button type="submit" class="btn-action btn-del" title="Remove"><i class="fa-solid fa-trash"></i></button>
              </form>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- COLUMN 2: DOWNWARD -->
    <div class="col-panel">
      <div class="col-panel-head">
        <div class="col-panel-title">
          <i class="fa-solid fa-arrow-down" style="color:var(--pink)"></i>
          <span>Column 2 Layer</span>
        </div>
        <span class="col-dir-badge dir-down"><i class="fa-solid fa-arrow-down"></i> Scrolls DOWN</span>
      </div>

      <div class="flow-items">
        <?php if (empty($col2_items)): ?>
          <p style="text-align:center;color:var(--muted);padding:24px 0;font-size:.85rem">No prompts added to Column 2 yet. (Auto-fallback prompts will show on site until added).</p>
        <?php else: foreach ($col2_items as $item): ?>
          <div class="flow-item <?= empty($item['is_active']) ? 'is-off' : '' ?>" id="item-row-<?= (int)$item['id'] ?>">
            <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="" class="flow-thumb">
            <div class="flow-details">
              <div class="flow-title"><?= htmlspecialchars($item['title']) ?></div>
              <div class="flow-meta">
                <span><i class="fa-solid fa-heart" style="color:#f43f5e"></i> <?= (int)$item['likes_count'] ?></span>
                <span>Type: <?= htmlspecialchars($item['prompt_type']) ?></span>
              </div>
            </div>
            <div class="flow-actions">
              <!-- Switch Column -->
              <form method="POST" action="home_card_flow.php" style="display:inline">
                <input type="hidden" name="action" value="switch_col">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <input type="hidden" name="new_col" value="col1">
                <button type="submit" class="btn-action" title="Move to Column 1"><i class="fa-solid fa-arrow-left"></i> Col 1</button>
              </form>

              <!-- Order Update -->
              <form method="POST" action="home_card_flow.php" style="display:inline-flex;align-items:center;gap:4px">
                <input type="hidden" name="action" value="update_order">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <input type="number" name="sort_order" class="order-input" value="<?= (int)$item['sort_order'] ?>" title="Sort order">
                <button type="submit" class="btn-action" title="Save order"><i class="fa-solid fa-check"></i></button>
              </form>

              <!-- Toggle Active Switch -->
              <label class="toggle" title="Toggle Active">
                <input type="checkbox" <?= !empty($item['is_active']) ? 'checked' : '' ?> onchange="toggleFlowActive(<?= (int)$item['id'] ?>, this)">
                <span class="toggle-slider"></span>
              </label>

              <!-- Delete -->
              <form method="POST" action="home_card_flow.php" style="display:inline" onsubmit="return confirm('Remove this prompt from Home Card Flow?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <button type="submit" class="btn-action btn-del" title="Remove"><i class="fa-solid fa-trash"></i></button>
              </form>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</main>

<script>
function toggleFlowActive(id, checkbox) {
  var fd = new FormData();
  fd.append('action', 'toggle');
  fd.append('id', id);

  fetch('home_card_flow.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d && d.ok) {
      var row = document.getElementById('item-row-' + id);
      if (row) {
        row.classList.toggle('is-off', !checkbox.checked);
      }
    } else {
      checkbox.checked = !checkbox.checked;
      alert('Error updating status');
    }
  }).catch(function(e) {
    checkbox.checked = !checkbox.checked;
    alert('Server connection error');
  });
}
</script>
</body>
</html>
