<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/image_helpers.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['error_msg'] = 'You do not have permission to edit the gallery carousel.';
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['username'] ?? ($_SESSION['user_name'] ?? 'Admin');
$flash = '';
$flash_type = 'ok';
$show_order = false;

function gc_delete_file(string $rel): void
{
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if ($rel === '' || str_contains($rel, '..')) {
        return;
    }
    $abs = __DIR__ . '/' . $rel;
    if (is_file($abs)) {
        @unlink($abs);
    }
}

// One-time seed: import existing /banner images if table is empty.
try {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM gallery_carousel')->fetchColumn();
    if ($count === 0) {
        $bannerDir = __DIR__ . '/banner';
        if (is_dir($bannerDir)) {
            $files = scandir($bannerDir) ?: [];
            natsort($files);
            $order = 1;
            $ins = $pdo->prepare('INSERT INTO gallery_carousel (image_path, is_active, sort_order) VALUES (?, 1, ?)');
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                    continue;
                }
                if (!preg_match('/\.(webp|jpe?g|png)$/i', $file)) {
                    continue;
                }
                $ins->execute(['banner/' . $file, $order++]);
            }
        }
    }
} catch (Throwable $e) {
    // ignore seed failures
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $files = $_FILES['carousel_images'] ?? null;
        $uploaded = 0;
        $failed = 0;
        if ($files && is_array($files['name'] ?? null)) {
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM gallery_carousel')->fetchColumn();
            $ins = $pdo->prepare('INSERT INTO gallery_carousel (image_path, alt_text, is_active, sort_order) VALUES (?, ?, 0, ?)');
            $n = count($files['name']);
            $defaultAlt = trim($_POST['default_alt'] ?? '');
            if (mb_strlen($defaultAlt) > 255) {
                $defaultAlt = mb_substr($defaultAlt, 0, 255);
            }
            for ($i = 0; $i < $n; $i++) {
                $one = [
                    'name' => $files['name'][$i] ?? '',
                    'type' => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i] ?? '',
                    'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $files['size'][$i] ?? 0,
                ];
                if (($one['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $path = gallery_carousel_upload_image($one);
                if ($path) {
                    $max++;
                    $ins->execute([$path, $defaultAlt !== '' ? $defaultAlt : null, $max]);
                    $uploaded++;
                } else {
                    $failed++;
                }
            }
        }
        if ($uploaded > 0) {
            $flash = $uploaded . ' image(s) uploaded as 16:9. Add/edit Alt Text on each card, toggle ON, then Save.';
            if ($failed > 0) {
                $flash .= ' ' . $failed . ' failed.';
            }
        } else {
            $flash = 'No images uploaded. Use JPG/PNG/WebP under 8MB.';
            $flash_type = 'err';
        }
    }

    if ($action === 'save_toggles') {
        $active_ids = array_map('intval', $_POST['active_ids'] ?? []);
        $active_ids = array_values(array_unique(array_filter($active_ids)));
        $alt_map = $_POST['alt_text'] ?? [];
        if (!is_array($alt_map)) {
            $alt_map = [];
        }

        // Save alt text for every card (SEO), regardless of active state.
        $altUpd = $pdo->prepare('UPDATE gallery_carousel SET alt_text = ? WHERE id = ?');
        foreach ($alt_map as $aid => $alt) {
            $aid = (int) $aid;
            if ($aid <= 0) {
                continue;
            }
            $alt = trim((string) $alt);
            if (mb_strlen($alt) > 255) {
                $alt = mb_substr($alt, 0, 255);
            }
            $altUpd->execute([$alt !== '' ? $alt : null, $aid]);
        }

        $pdo->exec('UPDATE gallery_carousel SET is_active = 0');
        if (!empty($active_ids)) {
            $placeholders = implode(',', array_fill(0, count($active_ids), '?'));
            $stmt = $pdo->prepare("UPDATE gallery_carousel SET is_active = 1 WHERE id IN ($placeholders)");
            $stmt->execute($active_ids);

            // Keep relative order among newly active ones; append any newly activated at end.
            $activeRows = $pdo->query(
                'SELECT id FROM gallery_carousel WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
            )->fetchAll(PDO::FETCH_COLUMN);
            $ord = 1;
            $upd = $pdo->prepare('UPDATE gallery_carousel SET sort_order = ? WHERE id = ?');
            foreach ($activeRows as $aid) {
                $upd->execute([$ord++, (int) $aid]);
            }
        }
        $flash = 'Carousel selection + alt text saved. Now adjust placement order below.';
        $show_order = true;
    }

    if ($action === 'save_order') {
        $order_ids = array_map('intval', $_POST['order_ids'] ?? []);
        $order_ids = array_values(array_filter($order_ids));
        $upd = $pdo->prepare('UPDATE gallery_carousel SET sort_order = ? WHERE id = ? AND is_active = 1');
        $ord = 1;
        foreach ($order_ids as $oid) {
            $upd->execute([$ord++, $oid]);
        }
        $flash = 'Carousel order saved. Gallery hero will use this sequence.';
        $show_order = true;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $row = $pdo->prepare('SELECT image_path FROM gallery_carousel WHERE id = ?');
            $row->execute([$id]);
            $path = $row->fetchColumn();
            $pdo->prepare('DELETE FROM gallery_carousel WHERE id = ?')->execute([$id]);
            if ($path && str_starts_with((string) $path, 'uploads/gallery_carousel/')) {
                gc_delete_file((string) $path);
            }
            $flash = 'Image removed from library.';
        }
    }

    if ($action === 'move') {
        $id = (int) ($_POST['id'] ?? 0);
        $dir = $_POST['dir'] ?? '';
        $rows = $pdo->query(
            'SELECT id, sort_order FROM gallery_carousel WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
        $idx = -1;
        foreach ($rows as $i => $r) {
            if ((int) $r['id'] === $id) {
                $idx = $i;
                break;
            }
        }
        if ($idx >= 0) {
            $swapWith = $dir === 'up' ? $idx - 1 : ($dir === 'down' ? $idx + 1 : -1);
            if ($swapWith >= 0 && $swapWith < count($rows)) {
                $a = $rows[$idx];
                $b = $rows[$swapWith];
                $upd = $pdo->prepare('UPDATE gallery_carousel SET sort_order = ? WHERE id = ?');
                $upd->execute([(int) $b['sort_order'], (int) $a['id']]);
                $upd->execute([(int) $a['sort_order'], (int) $b['id']]);
            }
        }
        $show_order = true;
        $flash = 'Order updated.';
    }
}

$items = $pdo->query(
    'SELECT id, image_path, alt_text, is_active, sort_order, created_at FROM gallery_carousel ORDER BY is_active DESC, sort_order ASC, id DESC'
)->fetchAll(PDO::FETCH_ASSOC);
$active_items = array_values(array_filter($items, static fn($r) => !empty($r['is_active'])));
usort($active_items, static function ($a, $b) {
    return ((int) $a['sort_order'] <=> (int) $b['sort_order']) ?: ((int) $a['id'] <=> (int) $b['id']);
});
$active_count = count($active_items);
$total_count = count($items);
if (isset($_GET['order']) || $show_order) {
    $show_order = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Gallery Carousel — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include_once 'gtag.php'; ?>
<style>
:root{--bg:#07060f;--surface:#0f0d1e;--border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);--accent:#8b5cf6;--accent2:#c084fc;--pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;--red:#f87171;--text:#e2e0ff;--muted:#9490bb;--font:'Inter',sans-serif}
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
.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:22px;flex-wrap:wrap}
.tb-title{font-size:1.35rem;font-weight:900;display:flex;align-items:center;gap:10px}
.tb-title i{color:var(--cyan)}
.stat-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;background:rgba(34,211,238,0.12);border:1px solid rgba(34,211,238,0.25);color:var(--cyan);font-size:.75rem;font-weight:800}
.info-box{background:rgba(139,92,246,0.08);border:1px solid var(--border);border-radius:14px;padding:16px 18px;margin-bottom:18px;font-size:.85rem;color:var(--muted);line-height:1.55}
.info-box strong{color:var(--accent2)}
.flash{padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:.84rem;font-weight:700}
.flash.ok{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.3);color:var(--green)}
.flash.err{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:var(--red)}
.card{background:var(--surface);border:1px solid var(--border2);border-radius:16px;padding:18px;margin-bottom:18px}
.card h2{font-size:.78rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:var(--accent2);margin-bottom:12px;display:flex;align-items:center;gap:8px}
.help{font-size:.75rem;color:var(--muted);margin-bottom:12px;line-height:1.5}
.file-row{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:11px;border:1px solid var(--border);background:rgba(139,92,246,.15);color:var(--accent2);font-weight:800;font-size:.82rem;cursor:pointer;font-family:var(--font)}
.btn:hover{background:rgba(139,92,246,.25)}
.btn-primary{background:linear-gradient(135deg,rgba(139,92,246,.9),rgba(244,114,182,.75));border-color:transparent;color:#fff}
.btn-primary:hover{filter:brightness(1.05)}
.btn-ghost{background:transparent}
.btn-danger{background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.3);color:var(--red)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}
.item{background:rgba(7,6,15,.55);border:1px solid var(--border2);border-radius:14px;overflow:hidden;display:flex;flex-direction:column}
.item.is-on{border-color:rgba(34,211,238,.45);box-shadow:0 0 0 1px rgba(34,211,238,.12)}
.item img{width:100%;aspect-ratio:16/9;object-fit:cover;display:block;background:#1a1630}
.item-body{padding:12px;display:flex;flex-direction:column;gap:10px}
.item-meta{font-size:.7rem;color:var(--muted);font-weight:700}
.item-row{display:flex;align-items:center;justify-content:space-between;gap:10px}
.alt-input,.form-input{width:100%;padding:9px 11px;border-radius:10px;border:1px solid var(--border);background:#0b0a16;color:var(--text);font-size:.78rem;font-family:var(--font)}
.alt-input:focus,.form-input:focus{outline:none;border-color:var(--accent)}
.alt-label{font-size:.68rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;display:block}
.toggle{position:relative;width:48px;height:28px;display:inline-block}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:#2a2540;border-radius:999px;cursor:pointer;transition:.25s;border:1px solid var(--border)}
.toggle-slider:before{content:'';position:absolute;width:20px;height:20px;left:3px;top:3px;background:#6b6688;border-radius:50%;transition:.25s}
.toggle input:checked + .toggle-slider{background:linear-gradient(135deg,var(--cyan),var(--accent));border-color:transparent}
.toggle input:checked + .toggle-slider:before{transform:translateX(20px);background:#fff}
.order-list{display:flex;flex-direction:column;gap:10px}
.order-row{display:flex;align-items:center;gap:12px;padding:10px;border-radius:12px;border:1px solid var(--border2);background:rgba(7,6,15,.45)}
.order-row img{width:120px;aspect-ratio:16/9;object-fit:cover;border-radius:8px;background:#1a1630}
.order-num{width:28px;height:28px;border-radius:50%;background:rgba(34,211,238,.15);color:var(--cyan);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.75rem;flex-shrink:0}
.order-actions{margin-left:auto;display:flex;gap:6px}
.empty{text-align:center;padding:28px;color:var(--muted);font-size:.85rem}
.save-bar{position:sticky;bottom:14px;z-index:50;display:flex;justify-content:flex-end;gap:10px;padding-top:8px}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.main{margin-left:58px;padding:20px 16px 80px}}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:14px 14px 90px}.order-row{flex-wrap:wrap}.order-row img{width:100%}}
</style>
</head>
<body class="no-site-cursor">
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
    <a href="trending_settings.php" class="sb-link"><i class="fa-solid fa-fire-flame-curved"></i> <span>Trending Settings</span></a>
    <a href="gallery_carousel_manager.php" class="sb-link active"><i class="fa-solid fa-images"></i> <span>Edit Gallery Carousel</span></a>
    <div class="sb-sec">Tools</div>
    <a href="gallery.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Gallery</span></a>
  </nav>
  <div class="sb-bottom">
    <a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-images"></i> Edit Gallery Carousel</div>
    <span class="stat-pill"><i class="fa-solid fa-clapperboard"></i> <?= $active_count ?> live / <?= $total_count ?> total</span>
  </div>

  <div class="info-box">
    Gallery hero carousel ke liye images yahan manage karo. Har image auto <strong>16:9</strong> crop/resize hoti hai.
    Har image ka <strong>Alt Text</strong> SEO/accessibility ke liye likho. Toggle <strong>ON</strong> = carousel mein dikhega. <strong>Save</strong> ke baad placement order set karo.
  </div>

  <?php if ($flash !== ''): ?>
  <div class="flash <?= $flash_type === 'err' ? 'err' : 'ok' ?>"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <div class="card">
    <h2><i class="fa-solid fa-cloud-arrow-up"></i> Upload Images</h2>
    <p class="help">Multiple images upload kar sakte ho (16:9). Optional default alt text sab nayi images pe lag jayega — baad mein har card pe alag edit bhi kar sakte ho.</p>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
      <input type="hidden" name="action" value="upload">
      <label class="alt-label" for="default_alt">Default Alt Text (optional)</label>
      <input class="form-input" type="text" id="default_alt" name="default_alt" maxlength="255" placeholder="e.g. AI couple prompt banner — cinematic rooftop selfie" style="margin-bottom:12px">
      <div class="file-row">
        <label class="btn">
          <input type="file" name="carousel_images[]" accept="image/*" multiple required style="display:none" onchange="document.getElementById('up-count').textContent=this.files.length+' file(s) selected'">
          <i class="fa-solid fa-image"></i> Choose Images
        </label>
        <span id="up-count" class="help" style="margin:0">No files chosen</span>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> Upload</button>
      </div>
    </form>
  </div>

  <form method="post" id="toggle-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
    <input type="hidden" name="action" value="save_toggles">
    <div class="card">
      <h2><i class="fa-solid fa-toggle-on"></i> Carousel Library</h2>
      <p class="help">Har image ka Alt Text likho, phir jin images ka toggle ON hoga wahi carousel mein jayengi. Save pe alt + selection dono save hote hain.</p>
      <?php if (empty($items)): ?>
        <div class="empty">No carousel images yet. Upload some 16:9 banners above.</div>
      <?php else: ?>
      <div class="grid">
        <?php foreach ($items as $item): $on = !empty($item['is_active']); ?>
        <div class="item<?= $on ? ' is-on' : '' ?>">
          <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['alt_text'] ?: 'Carousel slide') ?>" loading="lazy">
          <div class="item-body">
            <div class="item-row">
              <span class="item-meta">#<?= (int)$item['id'] ?> · <?= $on ? 'ON' : 'OFF' ?></span>
              <label class="toggle" title="Show in carousel">
                <input type="checkbox" name="active_ids[]" value="<?= (int)$item['id'] ?>" <?= $on ? 'checked' : '' ?> onchange="this.closest('.item').classList.toggle('is-on', this.checked); this.closest('.item').querySelector('.item-meta').textContent='#'+this.value+' · '+(this.checked?'ON':'OFF')">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div>
              <label class="alt-label" for="alt-<?= (int)$item['id'] ?>">Alt Text (SEO)</label>
              <input class="alt-input" type="text" id="alt-<?= (int)$item['id'] ?>" name="alt_text[<?= (int)$item['id'] ?>]" maxlength="255" value="<?= htmlspecialchars($item['alt_text'] ?? '') ?>" placeholder="Describe this banner image">
            </div>
            <button type="button" class="btn btn-danger" style="width:100%" onclick="if(confirm('Delete this image from library?')) document.getElementById('del-<?= (int)$item['id'] ?>').submit()">
              <i class="fa-solid fa-trash"></i> Delete
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="save-bar">
        <a href="gallery.php" target="_blank" class="btn btn-ghost"><i class="fa-solid fa-eye"></i> Preview Gallery</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Carousel Selection</button>
      </div>
    </div>
  </form>

  <?php foreach ($items as $item): ?>
  <form method="post" id="del-<?= (int)$item['id'] ?>" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
  </form>
  <?php endforeach; ?>

  <div class="card" id="order-section"<?= $show_order || $active_count ? '' : ' style="opacity:.7"' ?>>
    <h2><i class="fa-solid fa-arrow-down-1-9"></i> Placement Order</h2>
    <p class="help">Active slides ka order yahan set karo. 1 = pehle dikhega. Save selection ke baad yahan adjust karo.</p>
    <?php if (empty($active_items)): ?>
      <div class="empty">Pehle kuch images ka toggle ON karke Save karo — phir order set kar paoge.</div>
    <?php else: ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
      <input type="hidden" name="action" value="save_order">
      <div class="order-list" id="order-list">
        <?php foreach ($active_items as $i => $item): ?>
        <div class="order-row" data-id="<?= (int)$item['id'] ?>">
          <div class="order-num"><?= $i + 1 ?></div>
          <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="">
          <input type="hidden" name="order_ids[]" value="<?= (int)$item['id'] ?>">
          <div class="order-actions">
            <button type="button" class="btn" <?= $i === 0 ? 'disabled' : '' ?> onclick="document.getElementById('move-up-<?= (int)$item['id'] ?>').submit()"><i class="fa-solid fa-arrow-up"></i></button>
            <button type="button" class="btn" <?= $i === count($active_items) - 1 ? 'disabled' : '' ?> onclick="document.getElementById('move-down-<?= (int)$item['id'] ?>').submit()"><i class="fa-solid fa-arrow-down"></i></button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="save-bar">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Order</button>
      </div>
    </form>

    <?php foreach ($active_items as $item): ?>
    <form method="post" id="move-up-<?= (int)$item['id'] ?>" style="display:none">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
      <input type="hidden" name="action" value="move">
      <input type="hidden" name="dir" value="up">
      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
    </form>
    <form method="post" id="move-down-<?= (int)$item['id'] ?>" style="display:none">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
      <input type="hidden" name="action" value="move">
      <input type="hidden" name="dir" value="down">
      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
    </form>
    <?php endforeach; ?>

    <?php if ($show_order): ?>
    <script>document.getElementById('order-section').scrollIntoView({behavior:'smooth', block:'start'});</script>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
