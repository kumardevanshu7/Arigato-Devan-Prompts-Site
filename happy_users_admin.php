<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';
require_once 'includes/image_helpers.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$tab = ($_GET['tab'] ?? 'upload') === 'manage' ? 'manage' : 'upload';
$admin_name = $_SESSION['username'] ?? 'Admin';

// ── AJAX handlers ─────────────────────────────────────────────────────────────
if (!empty($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];

    if ($action === 'toggle') {
        $id  = (int) ($_POST['id'] ?? 0);
        $val = (int) ($_POST['val'] ?? 0) ? 1 : 0;
        try {
            $pdo->prepare('UPDATE happy_users_screenshots SET is_visible = ? WHERE id = ?')->execute([$val, $id]);
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $row = $pdo->prepare('SELECT image_path FROM happy_users_screenshots WHERE id = ?');
            $row->execute([$id]);
            if ($img = $row->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($img['image_path']) && is_file($img['image_path'])) {
                    @unlink($img['image_path']);
                }
            }
            $pdo->prepare('DELETE FROM happy_users_screenshots WHERE id = ?')->execute([$id]);
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'reorder') {
        $order = array_filter(array_map('intval', explode(',', $_POST['order'] ?? '')));
        try {
            $stmt = $pdo->prepare('UPDATE happy_users_screenshots SET sort_order = ? WHERE id = ?');
            foreach ($order as $i => $id) {
                if ($id > 0) {
                    $stmt->execute([$i + 1, $id]);
                }
            }
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    exit();
}

// ── Upload POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    verify_csrf();
    @ini_set('max_file_uploads', '100');
    @ini_set('max_input_vars', '3000');
    $uploaded = 0;
    $failed   = 0;
    $skipped  = 0;
    $maxSort  = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM happy_users_screenshots')->fetchColumn();

    if (!empty($_FILES['screenshots']['name']) && is_array($_FILES['screenshots']['name'])) {
        $count = count($_FILES['screenshots']['name']);
        $ins   = $pdo->prepare(
            'INSERT INTO happy_users_screenshots (image_path, img_width, img_height, sort_order, is_visible)
             VALUES (?, ?, ?, ?, 1)'
        );
        for ($i = 0; $i < $count; $i++) {
            $err = $_FILES['screenshots']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                $failed++;
                continue;
            }
            $file = [
                'name'     => $_FILES['screenshots']['name'][$i],
                'type'     => $_FILES['screenshots']['type'][$i],
                'tmp_name' => $_FILES['screenshots']['tmp_name'][$i],
                'error'    => $err,
                'size'     => $_FILES['screenshots']['size'][$i],
            ];
            $up = happy_users_upload_image($file);
            if ($up) {
                $maxSort++;
                $ins->execute([$up['path'], $up['width'], $up['height'], $maxSort]);
                $uploaded++;
            } else {
                $failed++;
            }
        }
    }

    if ($uploaded > 0) {
        $msg = $uploaded . ' screenshot(s) uploaded successfully.';
        if ($failed > 0) {
            $msg .= ' ' . $failed . ' file(s) failed.';
        }
        $_SESSION['hu_msg'] = $msg;
    } elseif ($failed > 0) {
        $_SESSION['hu_err'] = $failed . ' file(s) failed — use JPG, PNG, WebP or GIF.';
    } else {
        $_SESSION['hu_err'] = 'No files selected.';
    }
    header('Location: happy_users_admin.php?tab=upload');
    exit();
}

$flash_msg = $_SESSION['hu_msg'] ?? '';
$flash_err = $_SESSION['hu_err'] ?? '';
unset($_SESSION['hu_msg'], $_SESSION['hu_err']);

$shots = [];
try {
    $shots = $pdo->query(
        'SELECT * FROM happy_users_screenshots ORDER BY sort_order ASC, id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $shots = [];
}

$visible_count = count(array_filter($shots, fn($s) => (int) $s['is_visible'] === 1));
$page_title    = $tab === 'manage' ? 'Manage Pics' : 'Upload Screenshots';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> — Happy Users Admin</title>
<meta name="robots" content="noindex">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#07060f;--surface:#0f0d1e;--surface2:#15122a;--border:rgba(139,92,246,.18);--border2:rgba(139,92,246,.08);--accent:#8b5cf6;--accent2:#c084fc;--pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;--red:#f87171;--yellow:#fbbf24;--text:#e2e0ff;--muted:#9490bb;--font:'Inter',sans-serif}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:var(--font);min-height:100vh}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:220px;background:rgba(7,6,15,.98);border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column}
.sb-logo{padding:20px 18px 14px;border-bottom:1px solid var(--border2)}
.sb-brand{font-size:.72rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;display:flex;align-items:center;gap:8px}
.sb-brand i{-webkit-text-fill-color:#a78bfa}
.sb-admin{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border2)}
.sb-av-ph{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff}
.sb-uname{font-size:.78rem;font-weight:800}
.sb-role{font-size:.6rem;font-weight:700;color:var(--accent2);text-transform:uppercase;letter-spacing:.1em}
.sb-nav{flex:1;overflow-y:auto;padding:10px 8px}
.sb-sec{font-size:.58rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 10px 5px}
.sb-link{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;border:1px solid transparent;margin-bottom:1px}
.sb-link:hover{background:rgba(139,92,246,.08);color:var(--text)}
.sb-link.active{background:rgba(244,114,182,.12);color:var(--pink);border-color:rgba(244,114,182,.25)}
.sb-link i{width:16px;text-align:center}
.sb-bottom{padding:12px 8px;border-top:1px solid var(--border2)}
.sb-logout{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:700;color:var(--red);text-decoration:none}
.main{margin-left:220px;padding:28px 32px 80px;max-width:1200px}
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:24px;flex-wrap:wrap}
.tb-title{font-size:1.45rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--pink));-webkit-background-clip:text;-webkit-text-fill-color:transparent;flex:1;display:flex;align-items:center;gap:10px}
.tb-title i{-webkit-text-fill-color:var(--pink)}
.tb-actions{display:flex;gap:10px;flex-wrap:wrap}
.tb-btn{display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;border:1px solid var(--border2);color:var(--muted);transition:all .2s}
.tb-btn:hover{color:var(--text);border-color:var(--border)}
.tb-btn.primary{background:rgba(244,114,182,.12);color:var(--pink);border-color:rgba(244,114,182,.3)}
.flash{padding:12px 16px;border-radius:12px;font-size:.85rem;font-weight:600;margin-bottom:18px}
.flash.ok{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.25);color:var(--green)}
.flash.err{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:var(--red)}
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px}
.scard{background:rgba(15,13,30,.7);border:1px solid var(--border);border-radius:16px;padding:18px}
.sc-val{font-size:1.8rem;font-weight:900;color:var(--pink)}
.sc-lbl{font-size:.65rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}
.upload-panel{background:rgba(15,13,30,.7);border:1px solid var(--border);border-radius:18px;padding:28px}
.upload-zone{display:block;width:100%;position:relative;border:2px dashed rgba(244,114,182,.35);border-radius:16px;padding:56px 24px;text-align:center;cursor:pointer;transition:all .2s;background:rgba(244,114,182,.04);min-height:220px}
.upload-zone:hover,.upload-zone.dragover{border-color:var(--pink);background:rgba(244,114,182,.08)}
.upload-zone-input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:2}
.upload-zone-inner{position:relative;z-index:1;pointer-events:none}
.upload-zone-inner i{font-size:2.6rem;color:var(--pink);margin-bottom:14px;display:block}
.upload-zone-inner h3{font-size:1.05rem;margin-bottom:8px;color:var(--text);font-weight:800}
.upload-zone-inner p{font-size:.85rem;color:var(--muted);line-height:1.5;margin:0}
.upload-zone-inner .file-count{margin-top:12px;font-weight:700;color:var(--pink)}
.preview-row{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;max-height:280px;overflow-y:auto;padding:4px}
.preview-thumb{width:72px;height:72px;border-radius:10px;object-fit:cover;border:1px solid var(--border)}
.upload-actions{display:flex;align-items:center;gap:12px;margin-top:20px;flex-wrap:wrap}
.btn-submit{display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--pink));color:#fff;font-size:.85rem;font-weight:800;cursor:pointer}
.btn-clear{display:none;align-items:center;gap:6px;padding:12px 18px;border-radius:12px;border:1px solid var(--border2);background:transparent;color:var(--muted);font-size:.82rem;font-weight:700;cursor:pointer}
.btn-clear:hover{color:var(--red);border-color:rgba(248,113,113,.35)}
.btn-submit:disabled{opacity:.5;cursor:not-allowed}
.manage-hint{font-size:.82rem;color:var(--muted);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.manage-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(148px,1fr));gap:12px}
.hu-item{background:rgba(15,13,30,.85);border:1px solid var(--border2);border-radius:14px;overflow:hidden;cursor:grab;transition:box-shadow .2s,border-color .2s,opacity .2s;position:relative;display:flex;flex-direction:column;height:248px}
.hu-item:hover{border-color:rgba(244,114,182,.35);box-shadow:0 8px 24px rgba(0,0,0,.25)}
.hu-item.dragging{opacity:.5;cursor:grabbing;box-shadow:0 12px 32px rgba(244,114,182,.2)}
.hu-item.hidden-item{opacity:.6;border-style:dashed}
.hu-item-media{flex:1;min-height:0;display:flex;align-items:center;justify-content:center;padding:10px;background:rgba(0,0,0,.28);overflow:hidden}
.hu-item img{max-width:100%;max-height:100%;width:auto;height:auto;object-fit:contain;display:block;border-radius:6px}
.hu-item-bar{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;gap:6px;border-top:1px solid var(--border2);flex-shrink:0;background:rgba(12,10,22,.95);min-height:44px}
.hu-drag{color:var(--muted);font-size:.8rem;padding:4px;cursor:grab}
.hu-actions{display:flex;align-items:center;gap:4px;margin-left:auto}
.hu-btn{width:30px;height:30px;border-radius:8px;border:1px solid var(--border2);background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.75rem;transition:all .2s}
.hu-btn:hover{color:var(--text);border-color:var(--border)}
.hu-btn.del:hover{color:var(--red);border-color:rgba(248,113,113,.4);background:rgba(248,113,113,.08)}
.hu-badge{position:absolute;top:8px;left:8px;font-size:.58rem;font-weight:900;padding:3px 8px;border-radius:100px;text-transform:uppercase;letter-spacing:.06em;background:rgba(0,0,0,.65);color:var(--yellow)}
.toggle-wrap{display:flex;align-items:center;gap:6px;font-size:.68rem;font-weight:700;color:var(--muted)}
.toggle-wrap input{accent-color:var(--pink)}
.toast{position:fixed;bottom:24px;right:24px;padding:12px 18px;border-radius:12px;font-size:.82rem;font-weight:700;background:rgba(15,13,30,.95);border:1px solid var(--border);color:var(--text);opacity:0;transform:translateY(10px);transition:all .3s;z-index:999;pointer-events:none}
.toast.show{opacity:1;transform:translateY(0)}
.toast.ok{border-color:rgba(74,222,128,.4);color:var(--green)}
.toast.err{border-color:rgba(248,113,113,.4);color:var(--red)}
.hu-del-modal{display:none;position:fixed;inset:0;z-index:5000;background:rgba(4,3,10,.82);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);align-items:center;justify-content:center;padding:20px}
.hu-del-modal.is-open{display:flex}
.hu-del-card{background:linear-gradient(160deg,rgba(21,18,42,.98),rgba(15,13,30,.98));border:1px solid rgba(244,114,182,.22);border-radius:22px;padding:28px 26px 24px;max-width:380px;width:100%;box-shadow:0 28px 80px rgba(0,0,0,.55);text-align:center;animation:huDelIn .28s ease}
@keyframes huDelIn{from{opacity:0;transform:scale(.94) translateY(12px)}to{opacity:1;transform:none}}
.hu-del-icon{width:58px;height:58px;margin:0 auto 14px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.35rem;color:#fff;background:linear-gradient(135deg,#f87171,#e85d8a);box-shadow:0 8px 24px rgba(232,93,138,.35)}
.hu-del-title{font-size:1.08rem;font-weight:900;color:var(--text);margin-bottom:8px}
.hu-del-sub{font-size:.84rem;color:var(--muted);line-height:1.5;margin-bottom:16px}
.hu-del-thumb-wrap{width:88px;height:88px;margin:0 auto 18px;border-radius:12px;overflow:hidden;border:2px solid rgba(244,114,182,.25);background:rgba(0,0,0,.35)}
.hu-del-thumb{width:100%;height:100%;object-fit:contain;display:block}
.hu-del-actions{display:flex;gap:10px}
.hu-del-cancel,.hu-del-confirm{flex:1;padding:12px 16px;border-radius:12px;font-size:.84rem;font-weight:800;cursor:pointer;font-family:var(--font);transition:all .2s}
.hu-del-cancel{border:1px solid var(--border2);background:rgba(255,255,255,.04);color:var(--muted)}
.hu-del-cancel:hover{border-color:var(--border);color:var(--text)}
.hu-del-confirm{border:none;background:linear-gradient(135deg,#f87171,#e85d8a);color:#fff;box-shadow:0 6px 20px rgba(232,93,138,.3)}
.hu-del-confirm:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(232,93,138,.4)}
.drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:300}
.drawer-overlay.open{display:block}
.drawer{position:fixed;top:0;left:-280px;width:280px;height:100%;background:var(--surface);z-index:301;transition:left .3s;overflow-y:auto;border-right:1px solid var(--border)}
.drawer.open{left:0}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:18px;border-bottom:1px solid var(--border2)}
.drawer-brand{font-weight:900;font-size:.85rem}
.drawer-close{cursor:pointer;color:var(--muted);padding:6px}
.d-link{display:flex;align-items:center;gap:10px;padding:11px 18px;font-size:.82rem;font-weight:600;color:var(--muted);text-decoration:none}
.d-link:hover,.d-link.active{color:var(--pink);background:rgba(244,114,182,.08)}
.d-sec{font-size:.58rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:14px 18px 6px}
.mob-topbar{display:none;align-items:center;gap:12px;padding:12px 16px;background:rgba(7,6,15,.95);border-bottom:1px solid var(--border2);position:sticky;top:0;z-index:100}
.mob-menu-btn{width:38px;height:38px;border-radius:10px;background:rgba(139,92,246,.1);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--accent2)}
.mob-page-title{font-size:.9rem;font-weight:800;flex:1}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px;padding:20px 16px 80px}}
@media(max-width:768px){.sidebar{display:none!important}.main{margin-left:0!important;padding:14px 14px 80px!important}.mob-topbar{display:flex!important}.stats-row{grid-template-columns:1fr}.manage-grid{grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px}.hu-item{height:220px}}
</style>
</head>
<body>

<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="sideDrawer">
  <div class="drawer-head"><div class="drawer-brand">Arigato Admin</div><div class="drawer-close" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></div></div>
  <div class="d-sec">Happy Users</div>
  <a href="happy_users_admin.php?tab=upload" class="d-link <?= $tab === 'upload' ? 'active' : '' ?>"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Screenshots</a>
  <a href="happy_users_admin.php?tab=manage" class="d-link <?= $tab === 'manage' ? 'active' : '' ?>"><i class="fa-solid fa-images"></i> Manage Pics</a>
  <div class="d-sec">Tools</div>
  <a href="happy_users.php" class="d-link" target="_blank"><i class="fa-solid fa-eye"></i> View Page</a>
  <a href="dashboard.php" class="d-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
</div>

<div class="mob-topbar">
  <div class="mob-menu-btn" onclick="openDrawer()"><i class="fa-solid fa-bars"></i></div>
  <div class="mob-page-title"><i class="fa-solid fa-heart" style="color:var(--pink);margin-right:6px"></i><?= htmlspecialchars($page_title) ?></div>
  <a href="happy_users.php" target="_blank" style="font-size:.75rem;font-weight:800;color:var(--pink);text-decoration:none"><i class="fa-solid fa-eye"></i></a>
</div>

<aside class="sidebar">
  <div class="sb-logo"><div class="sb-brand"><i class="fa-solid fa-shield-halved"></i> <span>Arigato Admin</span></div></div>
  <div class="sb-admin">
    <div class="sb-av-ph"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
    <div><div class="sb-uname"><?= htmlspecialchars($admin_name) ?></div><div class="sb-role">Admin</div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-sec">Overview</div>
    <a href="dashboard.php" class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
    <div class="sb-sec">Happy Users</div>
    <a href="happy_users_admin.php?tab=upload" class="sb-link <?= $tab === 'upload' ? 'active' : '' ?>"><i class="fa-solid fa-cloud-arrow-up"></i> <span>Upload Screenshots</span></a>
    <a href="happy_users_admin.php?tab=manage" class="sb-link <?= $tab === 'manage' ? 'active' : '' ?>"><i class="fa-solid fa-images"></i> <span>Manage Pics</span></a>
    <div class="sb-sec">Tools</div>
    <a href="happy_users.php" class="sb-link" target="_blank"><i class="fa-solid fa-eye"></i> <span>View Page</span></a>
    <a href="index.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Site</span></a>
  </nav>
  <div class="sb-bottom"><a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-heart"></i> <?= htmlspecialchars($page_title) ?></div>
    <div class="tb-actions">
      <a href="happy_users.php" class="tb-btn primary" target="_blank"><i class="fa-solid fa-eye"></i> View Happy Users</a>
      <?php if ($tab === 'upload'): ?>
      <a href="happy_users_admin.php?tab=manage" class="tb-btn"><i class="fa-solid fa-images"></i> Manage Pics</a>
      <?php else: ?>
      <a href="happy_users_admin.php?tab=upload" class="tb-btn"><i class="fa-solid fa-cloud-arrow-up"></i> Upload</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($flash_msg): ?><div class="flash ok"><?= htmlspecialchars($flash_msg) ?></div><?php endif; ?>
  <?php if ($flash_err): ?><div class="flash err"><?= htmlspecialchars($flash_err) ?></div><?php endif; ?>

  <div class="stats-row">
    <div class="scard"><div class="sc-val"><?= count($shots) ?></div><div class="sc-lbl">Total Screenshots</div></div>
    <div class="scard"><div class="sc-val"><?= $visible_count ?></div><div class="sc-lbl">Visible on Page</div></div>
    <div class="scard"><div class="sc-val"><?= count($shots) - $visible_count ?></div><div class="sc-lbl">Hidden</div></div>
  </div>

  <?php if ($tab === 'upload'): ?>
  <div class="upload-panel">
    <form method="post" enctype="multipart/form-data" id="uploadForm">
      <input type="hidden" name="action" value="upload">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
      <div class="upload-zone" id="uploadZone">
        <input type="file" id="fileInput" class="upload-zone-input" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
        <div class="upload-zone-inner">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <h3>Upload chat screenshots</h3>
          <p>Click here or drag &amp; drop — <strong>ek saath bahut saari</strong> pics select kar sakte ho</p>
          <p>Ctrl+click ya Shift+click se multiple files choose karo</p>
          <p class="file-count" id="fileCount"></p>
        </div>
      </div>
      <div class="preview-row" id="previewRow"></div>
      <div class="upload-actions">
        <button type="submit" class="btn-submit" id="submitBtn" disabled><i class="fa-solid fa-upload"></i> Upload All to Happy Users</button>
        <button type="button" class="btn-clear" id="clearBtn" style="display:none"><i class="fa-solid fa-xmark"></i> Clear selection</button>
      </div>
    </form>
  </div>
  <?php else: ?>
  <p class="manage-hint"><i class="fa-solid fa-grip-vertical"></i> Drag cards to reorder. Toggle visibility or delete below.</p>
  <?php if (empty($shots)): ?>
    <div class="upload-panel" style="text-align:center;color:var(--muted)">
      <p>No screenshots yet. <a href="happy_users_admin.php?tab=upload" style="color:var(--pink)">Upload some</a>.</p>
    </div>
  <?php else: ?>
  <div class="manage-grid" id="manageGrid">
    <?php foreach ($shots as $shot): ?>
    <div class="hu-item <?= (int) $shot['is_visible'] ? '' : 'hidden-item' ?>"
         id="huItem<?= (int) $shot['id'] ?>"
         data-id="<?= (int) $shot['id'] ?>"
         draggable="true">
      <?php if (!(int) $shot['is_visible']): ?><span class="hu-badge">Hidden</span><?php endif; ?>
      <div class="hu-item-media">
        <img src="<?= htmlspecialchars($shot['image_path']) ?>" alt="Screenshot #<?= (int) $shot['id'] ?>" loading="lazy">
      </div>
      <div class="hu-item-bar">
        <span class="hu-drag" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>
        <div class="hu-actions">
          <label class="toggle-wrap" title="Show on page">
            <input type="checkbox" class="hu-toggle" data-id="<?= (int) $shot['id'] ?>" <?= (int) $shot['is_visible'] ? 'checked' : '' ?>>
            Show
          </label>
          <button type="button" class="hu-btn del" data-id="<?= (int) $shot['id'] ?>" title="Delete"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</main>

<div class="hu-del-modal" id="huDeleteModal" aria-hidden="true" role="dialog" aria-labelledby="huDeleteTitle">
  <div class="hu-del-card">
    <div class="hu-del-icon"><i class="fa-solid fa-trash-can"></i></div>
    <h3 class="hu-del-title" id="huDeleteTitle">Delete screenshot?</h3>
    <p class="hu-del-sub">Yeh permanently delete ho jayega — undo nahi hoga.</p>
    <div class="hu-del-thumb-wrap">
      <img class="hu-del-thumb" id="huDeleteThumb" src="" alt="" loading="lazy">
    </div>
    <div class="hu-del-actions">
      <button type="button" class="hu-del-cancel" id="huDeleteCancel">Cancel</button>
      <button type="button" class="hu-del-confirm" id="huDeleteConfirm"><i class="fa-solid fa-trash"></i> Delete</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>
<script src="js/happy-users-admin.js?v=20260788" defer></script>
<script>
function openDrawer(){document.getElementById('sideDrawer').classList.add('open');document.getElementById('drawerOverlay').classList.add('open')}
function closeDrawer(){document.getElementById('sideDrawer').classList.remove('open');document.getElementById('drawerOverlay').classList.remove('open')}
</script>
</body>
</html>
