<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
date_default_timezone_set('Asia/Kolkata');
require_once "db.php";
require_once __DIR__ . '/includes/footer_helper.php';

// Protect page (Admin Only)
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] = "You do not have permission to access the footer manager.";
    header("Location: index.php");
    exit();
}

$admin_name = $_SESSION["username"] ?? ($_SESSION["user_name"] ?? "Admin");
$success = "";
$error = "";

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// All published blogs from database
$published_blogs = get_published_blogs_list($pdo);

// Available pages for quick-pick
$available_pages = get_available_site_pages_for_footer();

// Handle POST save
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $error = "Invalid session token. Please reload and try again.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_default') {
        $default_cfg = get_default_footer_config();
        save_footer_config($default_cfg, $pdo);
        $success = "Footer layout restored to default configuration!";
    } elseif (isset($_POST['footer_json'])) {
        $raw_json = trim($_POST['footer_json']);
        $decoded = json_decode($raw_json, true);
        if (!is_array($decoded) || !isset($decoded['columns'])) {
            $error = "Invalid footer structure submitted. Please check your changes.";
        } else {
            // Clean blog IDs (ensure up to 5 valid integer IDs)
            $raw_blog_ids = $decoded['blog_ids'] ?? [];
            $clean_blog_ids = [];
            if (is_array($raw_blog_ids)) {
                foreach ($raw_blog_ids as $bid) {
                    $bid = (int)$bid;
                    if ($bid > 0 && !in_array($bid, $clean_blog_ids)) {
                        $clean_blog_ids[] = $bid;
                    }
                    if (count($clean_blog_ids) >= 5) break;
                }
            }

            // Clean social links
            $clean_socials = [];
            $supported_socials = ['instagram', 'x', 'pinterest', 'threads', 'facebook', 'mail'];
            $default_soc = get_default_social_platforms();
            $default_soc_map = [];
            foreach ($default_soc as $ds) {
                $default_soc_map[$ds['platform']] = $ds;
            }

            if (!empty($decoded['social_links']) && is_array($decoded['social_links'])) {
                foreach ($decoded['social_links'] as $soc) {
                    $plat = strtolower(trim((string)($soc['platform'] ?? '')));
                    if (!in_array($plat, $supported_socials)) continue;
                    $fallback = $default_soc_map[$plat] ?? ['icon' => 'fa-solid fa-link', 'title' => ucfirst($plat)];
                    $clean_socials[] = [
                        'platform' => $plat,
                        'title'    => trim((string)($soc['title'] ?? $fallback['title'])),
                        'icon'     => $soc['icon'] ?? $fallback['icon'],
                        'url'      => trim((string)($soc['url'] ?? '')),
                        'enabled'  => !empty($soc['enabled'])
                    ];
                }
            }
            if (empty($clean_socials)) {
                $clean_socials = $default_soc;
            }

            // Clean columns
            $clean_columns = [];
            foreach ($decoded['columns'] as $c_idx => $col) {
                $col_title = trim((string)($col['title'] ?? ''));
                if ($col_title === '') continue;
                $col_id = !empty($col['id']) ? preg_replace('/[^a-z0-9_-]/i', '', $col['id']) : ('col_' . ($c_idx + 1));
                $col_type = $col['type'] ?? ($col_id === 'blogs' ? 'blogs' : 'standard');

                $clean_links = [];
                if ($col_type !== 'blogs' && !empty($col['links']) && is_array($col['links'])) {
                    foreach ($col['links'] as $lnk) {
                        $l_title = trim((string)($lnk['title'] ?? ''));
                        $l_url = trim((string)($lnk['url'] ?? ''));
                        if ($l_title === '' || $l_url === '') continue;
                        $clean_links[] = [
                            'title'  => $l_title,
                            'url'    => $l_url,
                            'badge'  => trim((string)($lnk['badge'] ?? '')),
                            'target' => ($lnk['target'] ?? '_self') === '_blank' ? '_blank' : '_self'
                        ];
                    }
                }

                $clean_columns[] = [
                    'id'    => $col_id,
                    'title' => $col_title,
                    'type'  => $col_type,
                    'links' => $clean_links
                ];
            }

            $clean_cfg = [
                'watermark_text' => trim((string)($decoded['watermark_text'] ?? 'ARIGATO')),
                'tagline'        => trim((string)($decoded['tagline'] ?? 'KEEP CREATING.')),
                'brand_name'     => trim((string)($decoded['brand_name'] ?? 'ARIGATO DEVAN')),
                'social_links'   => $clean_socials,
                'blog_ids'       => $clean_blog_ids,
                'columns'        => $clean_columns
            ];

            save_footer_config($clean_cfg, $pdo);
            $success = "Footer layout & social settings updated successfully! Site footer refreshed.";
        }
    }
}

// Current loaded configuration
$footer_cfg = get_footer_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Footer Manager — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/store-footer.css?v=20261002footerv4">
<?php include_once "gtag.php"; ?>
<style>
:root {
  --bg: #07060f;
  --surface: #0f0d1e;
  --surface2: #16132b;
  --border: rgba(139,92,246,0.18);
  --border2: rgba(139,92,246,0.08);
  --accent: #8b5cf6;
  --accent2: #c084fc;
  --pink: #f472b6;
  --cyan: #22d3ee;
  --green: #4ade80;
  --yellow: #fbbf24;
  --text: #e2e0ff;
  --muted: #9490bb;
  --font: 'Inter', sans-serif;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { background: var(--bg); color: var(--text); font-family: var(--font); min-height: 100vh; }

/* Sidebar */
.sidebar {
  position: fixed; left: 0; top: 0; bottom: 0; width: 220px;
  background: rgba(7,6,15,0.98); border-right: 1px solid var(--border);
  z-index: 200; display: flex; flex-direction: column;
}
.sb-logo { padding: 20px 18px 14px; border-bottom: 1px solid var(--border2); }
.sb-brand {
  font-size: .72rem; font-weight: 900; letter-spacing: .15em; text-transform: uppercase;
  background: linear-gradient(135deg, #a78bfa, #f472b6);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  display: flex; align-items: center; gap: 8px;
}
.sb-admin { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--border2); }
.sb-av-ph {
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), var(--pink));
  display: flex; align-items: center; justify-content: center; font-weight: 900; color: #fff;
}
.sb-uname { font-size: .78rem; font-weight: 800; }
.sb-role { font-size: .6rem; font-weight: 700; color: var(--accent2); text-transform: uppercase; }
.sb-nav { flex: 1; overflow-y: auto; padding: 10px 8px; }
.sb-sec { font-size: .58rem; font-weight: 900; color: var(--muted); letter-spacing: .15em; text-transform: uppercase; padding: 10px 10px 5px; }
.sb-link {
  display: flex; align-items: center; gap: 9px; padding: 9px 10px; border-radius: 10px;
  font-size: .78rem; font-weight: 600; color: var(--muted); text-decoration: none; margin-bottom: 1px;
  border: 1px solid transparent; transition: all .15s ease;
}
.sb-link:hover { background: rgba(139,92,246,0.08); color: var(--text); }
.sb-link.active { background: rgba(139,92,246,0.15); color: var(--accent2); border-color: var(--border); }
.sb-link i { width: 16px; text-align: center; }
.sb-bottom { padding: 12px 8px; border-top: 1px solid var(--border2); }
.sb-logout { display: flex; align-items: center; gap: 8px; padding: 9px 10px; border-radius: 10px; font-size: .78rem; font-weight: 700; color: #f87171; text-decoration: none; }

/* Main layout */
.main { margin-left: 220px; padding: 28px 32px 100px; max-width: 1200px; }
.topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
.tb-title { font-size: 1.35rem; font-weight: 900; display: flex; align-items: center; gap: 10px; }
.tb-title i { color: #2dd4bf; }
.tb-actions { display: flex; align-items: center; gap: 10px; }

/* Flash message */
.flash { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-radius: 12px; font-size: .85rem; font-weight: 600; margin-bottom: 20px; }
.flash-ok { background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.3); color: #86efac; }
.flash-err { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.3); color: #fca5a5; }

/* Settings Card */
.settings-card {
  background: var(--surface); border: 1px solid var(--border); border-radius: 18px;
  overflow: hidden; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.card-header {
  padding: 18px 24px; border-bottom: 1px solid var(--border2);
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  background: rgba(139,92,246,0.04);
}
.card-title { font-size: 1.05rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }
.card-title i { color: #f472b6; }
.card-body { padding: 24px; }

/* Form Controls */
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 20px; }
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: .82rem; font-weight: 700; margin-bottom: 8px; color: var(--text); }
.form-hint { font-size: .74rem; color: var(--muted); margin-top: 6px; line-height: 1.4; }
.form-input, .form-select {
  width: 100%; padding: 11px 14px; border-radius: 10px;
  background: var(--surface2); border: 1px solid var(--border); color: var(--text);
  font-size: .88rem; font-family: inherit; transition: border-color .2s;
}
.form-input:focus, .form-select:focus {
  outline: none; border-color: var(--accent2); box-shadow: 0 0 0 3px rgba(192,132,252,0.15);
}

/* Columns Builder */
.cols-container { display: flex; flex-direction: column; gap: 20px; }
.col-box {
  background: rgba(22, 19, 43, 0.7); border: 1px solid var(--border);
  border-radius: 16px; padding: 20px; transition: border-color .2s;
}
.col-box:hover { border-color: rgba(192,132,252,0.4); }
.col-header {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  margin-bottom: 16px; flex-wrap: wrap; padding-bottom: 12px; border-bottom: 1px solid var(--border2);
}
.col-title-wrap { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 200px; }
.col-badge {
  font-size: .65rem; font-weight: 800; padding: 3px 8px; border-radius: 999px;
  background: rgba(45,212,191,0.15); color: #2dd4bf; border: 1px solid rgba(45,212,191,0.3);
}
.col-tools { display: flex; align-items: center; gap: 6px; }
.btn-icon {
  width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
  background: var(--surface2); color: var(--text); display: inline-flex;
  align-items: center; justify-content: center; cursor: pointer; transition: all .15s; font-size: .8rem;
}
.btn-icon:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
.btn-icon.btn-danger:hover { background: #ef4444; border-color: #ef4444; color: #fff; }

/* Links List in Column */
.links-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.link-row {
  display: grid; grid-template-columns: 2fr 2.5fr 1fr 1fr auto; gap: 8px; align-items: center;
  background: rgba(15,13,30,0.6); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border2);
}
.link-row:hover { border-color: rgba(139,92,246,0.3); }
.link-row-actions { display: flex; align-items: center; gap: 4px; }

/* Add Link Row */
.add-link-box {
  display: grid; grid-template-columns: 1.5fr 2fr 1fr auto; gap: 10px; align-items: center;
  background: rgba(139,92,246,0.06); padding: 14px; border-radius: 12px; border: 1px dashed var(--border);
}

/* Quick Page Pill Picker */
.quick-pages-wrap { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.quick-tag { font-size: .68rem; font-weight: 700; color: var(--muted); margin-right: 4px; }
.quick-pill {
  font-size: .7rem; font-weight: 600; padding: 3px 10px; border-radius: 999px;
  background: rgba(255,255,255,0.05); border: 1px solid var(--border2); color: var(--text);
  cursor: pointer; transition: all .15s;
}
.quick-pill:hover { background: var(--accent); border-color: var(--accent); color: #fff; }

/* Social Links Manager Styles */
.social-manager-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 14px;
}
.social-item-row {
  background: rgba(15,13,30,0.6); border: 1px solid var(--border2);
  border-radius: 12px; padding: 12px 14px; display: flex; align-items: center; gap: 12px;
}
.social-icon-badge {
  width: 38px; height: 38px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
  background: var(--surface2); border: 1px solid var(--border);
}
.soc-toggle {
  display: flex; align-items: center; gap: 6px; font-size: .76rem; font-weight: 700; cursor: pointer;
}
.soc-toggle input { cursor: pointer; accent-color: #2dd4bf; width: 16px; height: 16px; }

/* Global Buttons */
.btn-primary {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 12px 24px; border-radius: 12px;
  background: linear-gradient(135deg, #2dd4bf, #06b6d4);
  color: #042f2e; font-size: .88rem; font-weight: 800; border: none; cursor: pointer;
  box-shadow: 0 4px 18px rgba(45,212,191,0.3); transition: all .2s ease;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(45,212,191,0.5); }
.btn-secondary {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 18px; border-radius: 10px;
  background: var(--surface2); color: var(--text); border: 1px solid var(--border);
  font-size: .82rem; font-weight: 700; cursor: pointer; transition: all .15s ease;
}
.btn-secondary:hover { background: rgba(139,92,246,0.2); border-color: var(--accent2); color: #fff; }

/* Live Preview Panel */
.preview-container {
  background: #FFFFFF; border-radius: 18px; overflow: hidden;
  box-shadow: 0 12px 40px rgba(0,0,0,0.4); margin-bottom: 28px;
}
.preview-header-bar {
  background: #2F4156; color: #FFFFFF; padding: 10px 18px; font-size: .78rem; font-weight: 700;
  display: flex; align-items: center; justify-content: space-between;
}

@media(max-width: 900px) {
  .sidebar { width: 58px; }
  .sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span { display: none; }
  .main { margin-left: 58px; padding: 20px 16px 80px; }
  .link-row { grid-template-columns: 1fr; gap: 6px; }
  .add-link-box { grid-template-columns: 1fr; }
}
@media(max-width: 768px) {
  .sidebar { display: none; }
  .main { margin-left: 0; padding: 14px 14px 90px; }
}
</style>
</head>
<body class="no-site-cursor">

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<main class="main">
  <div class="topbar">
    <div class="tb-title">
      <i class="fa-solid fa-table-columns"></i> Footer Columns &amp; Links Manager
    </div>
    <div class="tb-actions">
      <form method="POST" onsubmit="return confirm('Are you sure you want to reset the footer to the default configuration?');" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="reset_default">
        <button type="submit" class="btn-secondary" title="Restore default columns and links">
          <i class="fa-solid fa-rotate-left"></i> Reset to Default
        </button>
      </form>
      <button type="button" class="btn-primary" onclick="saveAllFooterSettings()">
        <i class="fa-solid fa-floppy-disk"></i> Save Footer
      </button>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="flash flash-ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="flash flash-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Live Visual Preview of Footer -->
  <div class="preview-container">
    <div class="preview-header-bar">
      <span><i class="fa-solid fa-eye"></i> Live Site Footer Preview (Updates automatically)</span>
      <span style="font-size:0.7rem; opacity:0.8;">Nogoda Theme Palette</span>
    </div>
    <div id="footerLivePreview">
      <!-- Injected by JS -->
    </div>
  </div>

  <!-- Form for Submitting JSON -->
  <form id="footerSubmitForm" method="POST" action="footer_admin.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="footer_json" id="footerJsonInput">

    <!-- Card 1: Branding & Watermark Settings -->
    <div class="settings-card">
      <div class="card-header">
        <div class="card-title">
          <i class="fa-solid fa-font"></i>
          <span>Typography, Watermark &amp; Branding</span>
        </div>
      </div>
      <div class="card-body">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label" for="watermark_text">Bottom Big Faded Watermark Text (Spelling: ARIGATO)</label>
            <input type="text" id="watermark_text" class="form-input" value="<?= htmlspecialchars($footer_cfg['watermark_text'] ?? 'ARIGATO') ?>" placeholder="e.g. ARIGATO" oninput="updateLivePreview()">
            <p class="form-hint">Displayed at the bottom edge in monumental faded typography.</p>
          </div>
          <div class="form-group">
            <label class="form-label" for="brand_name">Brand Name (Gradient Accent)</label>
            <input type="text" id="brand_name" class="form-input" value="<?= htmlspecialchars($footer_cfg['brand_name'] ?? 'ARIGATO DEVAN') ?>" placeholder="e.g. ARIGATO DEVAN" oninput="updateLivePreview()">
            <p class="form-hint">Displays next to copyright with Nogoda gradient text styling.</p>
          </div>
          <div class="form-group">
            <label class="form-label" for="tagline">Footer Tagline</label>
            <input type="text" id="tagline" class="form-input" value="<?= htmlspecialchars($footer_cfg['tagline'] ?? 'KEEP CREATING.') ?>" placeholder="e.g. KEEP CREATING." oninput="updateLivePreview()">
            <p class="form-hint">Appears in bold after the brand name in the copyright bar.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Card 2: 5 Selected Blogs Manager (Column 2) -->
    <div class="settings-card">
      <div class="card-header">
        <div class="card-title">
          <i class="fa-solid fa-newspaper" style="color:#38bdf8;"></i>
          <span>Blogs Section — Select Your 5 Featured Blogs</span>
        </div>
        <a href="blog_admin.php" target="_blank" class="btn-secondary" style="font-size:0.75rem;">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Blog Manager
        </a>
      </div>
      <div class="card-body">
        <p class="form-hint" style="margin-bottom:16px;">
          Choose any 5 blogs from your published posts. These will appear under the <strong>Blogs</strong> column in your footer. You can also reorder them using the Up / Down arrows.
        </p>

        <div id="blogPickersContainer" style="display:flex; flex-direction:column; gap:12px;">
          <!-- Generated by JS -->
        </div>
      </div>
    </div>

    <!-- Card 3: Social Media Links Manager (X, Pinterest, Threads, Instagram, Facebook, Mail) -->
    <div class="settings-card">
      <div class="card-header">
        <div class="card-title">
          <i class="fa-solid fa-share-nodes" style="color:#e879f9;"></i>
          <span>Social Media Links (Right Bottom Bar)</span>
        </div>
      </div>
      <div class="card-body">
        <p class="form-hint" style="margin-bottom:16px;">
          Check the box to show a platform in the footer and provide its profile URL. You can also reorder icons.
        </p>

        <div class="social-manager-grid" id="socialLinksContainer">
          <!-- Generated by JS -->
        </div>
      </div>
    </div>

    <!-- Card 4: Standard Columns & Links Reordering (Explore Prompts, Legal & Policies, Community & Connect) -->
    <div class="settings-card">
      <div class="card-header">
        <div class="card-title">
          <i class="fa-solid fa-bars-staggered"></i>
          <span>Footer Columns &amp; Links Order</span>
        </div>
        <button type="button" class="btn-secondary" onclick="addNewColumn()">
          <i class="fa-solid fa-plus"></i> Add Extra Column
        </button>
      </div>
      <div class="card-body">
        <div class="cols-container" id="columnsContainer">
          <!-- Dynamic Column Elements Generated by JS -->
        </div>
      </div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
      <button type="button" class="btn-primary" style="padding:14px 32px; font-size:1rem;" onclick="saveAllFooterSettings()">
        <i class="fa-solid fa-floppy-disk"></i> Save Footer Settings
      </button>
    </div>
  </form>
</main>

<script>
// Initial Data from PHP
let footerData = <?= json_encode($footer_cfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const siteAvailablePages = <?= json_encode($available_pages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const allPublishedBlogs = <?= json_encode($published_blogs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

// Ensure blog_ids array exists with 5 items
if (!footerData.blog_ids || !Array.isArray(footerData.blog_ids)) {
  footerData.blog_ids = allPublishedBlogs.slice(0, 5).map(b => parseInt(b.id));
}
while (footerData.blog_ids.length < 5) {
  const unused = allPublishedBlogs.find(b => !footerData.blog_ids.includes(parseInt(b.id)));
  if (unused) {
    footerData.blog_ids.push(parseInt(unused.id));
  } else {
    break;
  }
}

// Render Blog Selection Slots
function renderBlogPickers() {
  const container = document.getElementById('blogPickersContainer');
  container.innerHTML = '';

  for (let i = 0; i < 5; i++) {
    const currentId = footerData.blog_ids[i] ? parseInt(footerData.blog_ids[i]) : 0;
    const row = document.createElement('div');
    row.style.cssText = 'display:flex; align-items:center; gap:10px; background:rgba(15,13,30,0.6); padding:10px 14px; border-radius:12px; border:1px solid var(--border2);';

    row.innerHTML = `
      <span style="font-size:0.75rem; font-weight:800; color:#38bdf8; min-width:65px;">Blog #${i + 1}:</span>
      <select class="form-select" onchange="onBlogSelectChange(${i}, this.value)" style="flex:1;">
        <option value="0">-- Select a Blog (or Leave Empty) --</option>
        ${allPublishedBlogs.map(b => `
          <option value="${b.id}" ${parseInt(b.id) === currentId ? 'selected' : ''}>
            ${escapeHtml(b.title)} (${b.slug || 'id:' + b.id})
          </option>
        `).join('')}
      </select>
      <div style="display:flex; align-items:center; gap:4px;">
        <button type="button" class="btn-icon" title="Move Up" onclick="moveBlogSlot(${i}, -1)" ${i === 0 ? 'disabled style="opacity:0.3;"' : ''}>
          <i class="fa-solid fa-chevron-up"></i>
        </button>
        <button type="button" class="btn-icon" title="Move Down" onclick="moveBlogSlot(${i}, 1)" ${i === 4 ? 'disabled style="opacity:0.3;"' : ''}>
          <i class="fa-solid fa-chevron-down"></i>
        </button>
        <button type="button" class="btn-icon btn-danger" title="Clear this slot" onclick="clearBlogSlot(${i})">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    `;

    container.appendChild(row);
  }

  updateLivePreview();
}

function onBlogSelectChange(slotIdx, val) {
  const bId = parseInt(val) || 0;
  footerData.blog_ids[slotIdx] = bId;
  updateLivePreview();
}

function moveBlogSlot(slotIdx, dir) {
  const targetIdx = slotIdx + dir;
  if (targetIdx < 0 || targetIdx >= 5) return;
  const temp = footerData.blog_ids[slotIdx];
  footerData.blog_ids[slotIdx] = footerData.blog_ids[targetIdx];
  footerData.blog_ids[targetIdx] = temp;
  renderBlogPickers();
}

function clearBlogSlot(slotIdx) {
  footerData.blog_ids[slotIdx] = 0;
  renderBlogPickers();
}

// Render Social Links Manager
function renderSocialLinks() {
  const container = document.getElementById('socialLinksContainer');
  container.innerHTML = '';

  const platforms = footerData.social_links || [];
  platforms.forEach((soc, sIdx) => {
    const box = document.createElement('div');
    box.className = 'social-item-row';

    const pColorMap = {
      instagram: '#E1306C',
      x: '#FFFFFF',
      pinterest: '#E60023',
      threads: '#FFFFFF',
      facebook: '#1877F2',
      mail: '#2FA6C6'
    };
    const pColor = pColorMap[soc.platform] || '#2dd4bf';

    box.innerHTML = `
      <div class="social-icon-badge" style="color:${pColor};">
        <i class="${escapeHtml(soc.icon || 'fa-solid fa-link')}"></i>
      </div>
      <div style="flex:1; min-width:0;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
          <label class="soc-toggle">
            <input type="checkbox" ${soc.enabled ? 'checked' : ''} onchange="onSocialToggle(${sIdx}, this.checked)">
            <span>${escapeHtml(soc.title || soc.platform)}</span>
          </label>
          <div style="display:flex; align-items:center; gap:4px;">
            <button type="button" class="btn-icon" style="width:26px; height:26px; font-size:0.7rem;" title="Move Left / Up" onclick="moveSocialItem(${sIdx}, -1)" ${sIdx === 0 ? 'disabled style="opacity:0.3;"' : ''}>
              <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button type="button" class="btn-icon" style="width:26px; height:26px; font-size:0.7rem;" title="Move Right / Down" onclick="moveSocialItem(${sIdx}, 1)" ${sIdx === platforms.length - 1 ? 'disabled style="opacity:0.3;"' : ''}>
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>
        </div>
        <input type="text" class="form-input" style="padding:7px 10px; font-size:0.8rem;" 
               value="${escapeHtml(soc.url || '')}" 
               placeholder="Enter ${escapeHtml(soc.title)} URL..."
               oninput="onSocialUrlChange(${sIdx}, this.value)">
      </div>
    `;

    container.appendChild(box);
  });

  updateLivePreview();
}

function onSocialToggle(sIdx, isChecked) {
  footerData.social_links[sIdx].enabled = isChecked;
  updateLivePreview();
}

function onSocialUrlChange(sIdx, val) {
  footerData.social_links[sIdx].url = val.trim();
  updateLivePreview();
}

function moveSocialItem(sIdx, dir) {
  const targetIdx = sIdx + dir;
  if (targetIdx < 0 || targetIdx >= footerData.social_links.length) return;
  const temp = footerData.social_links[sIdx];
  footerData.social_links[sIdx] = footerData.social_links[targetIdx];
  footerData.social_links[targetIdx] = temp;
  renderSocialLinks();
}

// Render Standard Columns Editor
function renderColumnsEditor() {
  const container = document.getElementById('columnsContainer');
  container.innerHTML = '';

  if (!footerData.columns || footerData.columns.length === 0) {
    container.innerHTML = '<div style="color:var(--muted); padding:20px; text-align:center;">No columns configured. Click "Add New Column" to begin.</div>';
    updateLivePreview();
    return;
  }

  footerData.columns.forEach((col, cIdx) => {
    const isBlogCol = col.type === 'blogs' || col.id === 'blogs';
    const colBox = document.createElement('div');
    colBox.className = 'col-box';

    // Header of Column
    colBox.innerHTML = `
      <div class="col-header">
        <div class="col-title-wrap">
          <span class="col-badge">Col ${cIdx + 1}</span>
          <input type="text" class="form-input col-name-input" value="${escapeHtml(col.title)}" 
                 placeholder="Column Title (e.g. Explore Prompts)" 
                 oninput="onColumnTitleChange(${cIdx}, this.value)" style="max-width:280px; font-weight:700;">
          ${isBlogCol ? '<span style="font-size:0.7rem; font-weight:700; color:#38bdf8; background:rgba(56,189,248,0.12); padding:3px 8px; border-radius:6px;">Dynamic Blogs Column</span>' : ''}
        </div>
        <div class="col-tools">
          <button type="button" class="btn-icon" title="Move Left" onclick="moveColumn(${cIdx}, -1)" ${cIdx === 0 ? 'disabled style="opacity:0.3;"' : ''}>
            <i class="fa-solid fa-arrow-left"></i>
          </button>
          <button type="button" class="btn-icon" title="Move Right" onclick="moveColumn(${cIdx}, 1)" ${cIdx === footerData.columns.length - 1 ? 'disabled style="opacity:0.3;"' : ''}>
            <i class="fa-solid fa-arrow-right"></i>
          </button>
          ${!isBlogCol ? `
            <button type="button" class="btn-icon btn-danger" title="Delete Column" onclick="deleteColumn(${cIdx})">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          ` : ''}
        </div>
      </div>
      
      ${isBlogCol ? `
        <div style="background:rgba(56,189,248,0.06); border:1px dashed rgba(56,189,248,0.3); border-radius:12px; padding:16px; margin-bottom:12px;">
          <div style="font-size:0.82rem; font-weight:700; color:#38bdf8; margin-bottom:4px;">
            <i class="fa-solid fa-circle-info"></i> Managed via the "Blogs Section" Card Above
          </div>
          <div style="font-size:0.75rem; color:var(--muted);">
            The 5 blogs selected in the card above will automatically render in this column, plus a "View All Blogs" link.
          </div>
        </div>
      ` : `
        <div class="links-list" id="linksList_${cIdx}"></div>
        
        <!-- Add Link Row -->
        <div class="add-link-box">
          <input type="text" id="new_title_${cIdx}" class="form-input" placeholder="Link Title (e.g. FAQ)">
          <input type="text" id="new_url_${cIdx}" class="form-input" placeholder="Page URL (e.g. faq.php)">
          <input type="text" id="new_badge_${cIdx}" class="form-input" placeholder="Badge (optional)">
          <button type="button" class="btn-secondary" onclick="addLinkToColumn(${cIdx})" style="white-space:nowrap;">
            <i class="fa-solid fa-plus"></i> Add Link
          </button>
        </div>

        <!-- Quick Pick Page Pills -->
        <div class="quick-pages-wrap">
          <span class="quick-tag"><i class="fa-solid fa-bolt"></i> Quick Pick:</span>
          ${siteAvailablePages.map((pg) => `
            <button type="button" class="quick-pill" onclick="quickFillLink(${cIdx}, '${escapeHtml(pg.title)}', '${escapeHtml(pg.url)}')">
              ${escapeHtml(pg.title)}
            </button>
          `).join('')}
        </div>
      `}
    `;

    container.appendChild(colBox);

    if (!isBlogCol) {
      const linksList = colBox.querySelector(`#linksList_${cIdx}`);
      if (col.links && col.links.length > 0) {
        col.links.forEach((lnk, lIdx) => {
          const row = document.createElement('div');
          row.className = 'link-row';
          row.innerHTML = `
            <input type="text" class="form-input" value="${escapeHtml(lnk.title)}" placeholder="Title" oninput="onLinkFieldChange(${cIdx}, ${lIdx}, 'title', this.value)">
            <input type="text" class="form-input" value="${escapeHtml(lnk.url)}" placeholder="URL" oninput="onLinkFieldChange(${cIdx}, ${lIdx}, 'url', this.value)">
            <input type="text" class="form-input" value="${escapeHtml(lnk.badge || '')}" placeholder="Badge" oninput="onLinkFieldChange(${cIdx}, ${lIdx}, 'badge', this.value)">
            <select class="form-select" onchange="onLinkFieldChange(${cIdx}, ${lIdx}, 'target', this.value)">
              <option value="_self" ${lnk.target === '_self' ? 'selected' : ''}>Same Tab</option>
              <option value="_blank" ${lnk.target === '_blank' ? 'selected' : ''}>New Tab</option>
            </select>
            <div class="link-row-actions">
              <button type="button" class="btn-icon" title="Move Up" onclick="moveLink(${cIdx}, ${lIdx}, -1)" ${lIdx === 0 ? 'disabled style="opacity:0.3;"' : ''}>
                <i class="fa-solid fa-chevron-up"></i>
              </button>
              <button type="button" class="btn-icon" title="Move Down" onclick="moveLink(${cIdx}, ${lIdx}, 1)" ${lIdx === col.links.length - 1 ? 'disabled style="opacity:0.3;"' : ''}>
                <i class="fa-solid fa-chevron-down"></i>
              </button>
              <button type="button" class="btn-icon btn-danger" title="Delete Link" onclick="deleteLink(${cIdx}, ${lIdx})">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          `;
          linksList.appendChild(row);
        });
      } else {
        linksList.innerHTML = '<div style="color:var(--muted); font-size:0.76rem; padding:8px 4px;">No links in this column yet. Add one below.</div>';
      }
    }
  });

  updateLivePreview();
}

function onColumnTitleChange(cIdx, val) {
  footerData.columns[cIdx].title = val;
  updateLivePreview();
}

function onLinkFieldChange(cIdx, lIdx, field, val) {
  footerData.columns[cIdx].links[lIdx][field] = val;
  updateLivePreview();
}

function quickFillLink(cIdx, title, url) {
  const tInput = document.getElementById(`new_title_${cIdx}`);
  const uInput = document.getElementById(`new_url_${cIdx}`);
  if (tInput && uInput) {
    tInput.value = title;
    uInput.value = url;
    tInput.focus();
  }
}

function addLinkToColumn(cIdx) {
  const tInput = document.getElementById(`new_title_${cIdx}`);
  const uInput = document.getElementById(`new_url_${cIdx}`);
  const bInput = document.getElementById(`new_badge_${cIdx}`);

  const title = tInput.value.trim();
  const url = uInput.value.trim();
  const badge = bInput.value.trim();

  if (!title || !url) {
    alert('Please enter both Link Title and URL.');
    return;
  }

  if (!footerData.columns[cIdx].links) {
    footerData.columns[cIdx].links = [];
  }

  const isExt = /^https?:\/\//i.test(url);
  footerData.columns[cIdx].links.push({
    title: title,
    url: url,
    badge: badge,
    target: isExt ? '_blank' : '_self'
  });

  renderColumnsEditor();
}

function deleteLink(cIdx, lIdx) {
  footerData.columns[cIdx].links.splice(lIdx, 1);
  renderColumnsEditor();
}

function moveLink(cIdx, lIdx, dir) {
  const links = footerData.columns[cIdx].links;
  const targetIdx = lIdx + dir;
  if (targetIdx < 0 || targetIdx >= links.length) return;
  const temp = links[lIdx];
  links[lIdx] = links[targetIdx];
  links[targetIdx] = temp;
  renderColumnsEditor();
}

function addNewColumn() {
  const colName = prompt('Enter title for new column (e.g. Featured Categories):', 'New Column');
  if (!colName || !colName.trim()) return;
  footerData.columns.push({
    id: 'col_' + Date.now(),
    title: colName.trim(),
    type: 'standard',
    links: []
  });
  renderColumnsEditor();
}

function deleteColumn(cIdx) {
  if (!confirm(`Are you sure you want to delete column "${footerData.columns[cIdx].title}" and all its links?`)) return;
  footerData.columns.splice(cIdx, 1);
  renderColumnsEditor();
}

function moveColumn(cIdx, dir) {
  const targetIdx = cIdx + dir;
  if (targetIdx < 0 || targetIdx >= footerData.columns.length) return;
  const temp = footerData.columns[cIdx];
  footerData.columns[cIdx] = footerData.columns[targetIdx];
  footerData.columns[targetIdx] = temp;
  renderColumnsEditor();
}

function updateLivePreview() {
  const preview = document.getElementById('footerLivePreview');
  const watermarkText = (document.getElementById('watermark_text')?.value || footerData.watermark_text || 'ARIGATO').trim();
  const brandName = (document.getElementById('brand_name')?.value || footerData.brand_name || 'ARIGATO DEVAN').trim();
  const tagline = (document.getElementById('tagline')?.value || footerData.tagline || 'KEEP CREATING.').trim();

  // Resolve 5 blogs for preview
  const resolvedBlogLinks = (footerData.blog_ids || []).map(bId => {
    const found = allPublishedBlogs.find(b => parseInt(b.id) === parseInt(bId));
    if (!found) return null;
    return {
      title: found.title,
      url: found.slug ? `blog.php?slug=${encodeURIComponent(found.slug)}` : `blog.php?id=${found.id}`
    };
  }).filter(Boolean);

  let colsHtml = '';
  if (footerData.columns && footerData.columns.length > 0) {
    colsHtml = `
      <div class="sf-grid">
        ${footerData.columns.map(col => {
          const isBlogCol = col.type === 'blogs' || col.id === 'blogs';
          const links = isBlogCol ? resolvedBlogLinks : (col.links || []);
          return `
            <div class="sf-col">
              <div class="sf-col-title">${escapeHtml(col.title)}</div>
              <ul class="sf-links">
                ${links.map(lnk => `
                  <li>
                    <a href="${escapeHtml(lnk.url)}" class="sf-link" onclick="return false;">
                      <span>${escapeHtml(lnk.title)}</span>
                      ${lnk.badge ? `<span class="sf-badge">${escapeHtml(lnk.badge)}</span>` : ''}
                    </a>
                  </li>
                `).join('')}
                ${isBlogCol ? `
                  <li>
                    <a href="blogs.php" class="sf-link" style="font-weight:700; color:#2FA6C6;" onclick="return false;">
                      <span>View All Blogs <i class="fa-solid fa-arrow-right" style="font-size:0.7rem; margin-left:2px;"></i></span>
                    </a>
                  </li>
                ` : ''}
              </ul>
            </div>
          `;
        }).join('')}
      </div>
    `;
  }

  // Active Social Links for preview
  const activeSocials = (footerData.social_links || []).filter(s => s.enabled && s.url);

  preview.innerHTML = `
    <footer class="store-footer" style="margin-top:0 !important; border-top:none !important;">
      <div class="store-footer-inner">
        ${colsHtml}
        <hr class="sf-divider">
        <div class="sf-bottom-bar">
          <div class="sf-copy">
            &copy; ${new Date().getFullYear()} <span class="sf-brand-name">${escapeHtml(brandName)}</span>. <span class="sf-tagline">${escapeHtml(tagline)}</span>
          </div>
          <div class="sf-socials">
            ${activeSocials.map(soc => `
              <span class="sf-social-btn sf-soc-${escapeHtml(soc.platform)}" title="${escapeHtml(soc.title)}">
                <i class="${escapeHtml(soc.icon)}"></i>
              </span>
            `).join('')}
          </div>
        </div>
        ${watermarkText ? `
          <div class="sf-watermark-wrap">
            <span class="sf-watermark-text">${escapeHtml(watermarkText)}</span>
          </div>
        ` : ''}
      </div>
    </footer>
  `;
}

function saveAllFooterSettings() {
  footerData.watermark_text = (document.getElementById('watermark_text')?.value || 'ARIGATO').trim();
  footerData.brand_name = (document.getElementById('brand_name')?.value || 'ARIGATO DEVAN').trim();
  footerData.tagline = (document.getElementById('tagline')?.value || 'KEEP CREATING.') .trim();

  document.getElementById('footerJsonInput').value = JSON.stringify(footerData);
  document.getElementById('footerSubmitForm').submit();
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', () => {
  renderBlogPickers();
  renderSocialLinks();
  renderColumnsEditor();
});
</script>
</body>
</html>
