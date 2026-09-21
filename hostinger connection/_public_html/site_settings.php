<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
date_default_timezone_set('Asia/Kolkata');
require_once "db.php";
require_once __DIR__ . '/includes/settings_helper.php';

// Protect page (Admin Only)
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] = "You do not have permission to access site settings.";
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

// Handle POST save
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $error = "Invalid session token. Please try again.";
    } else {
        $count = trim($_POST['insta_follower_count'] ?? '');
        $handle = trim($_POST['insta_handle'] ?? '');
        $url = trim($_POST['insta_url'] ?? '');

        if ($count === '') {
            $count = '17K+';
        }
        if ($handle === '') {
            $handle = '@arigato.devan';
        }
        if ($url === '') {
            $url = 'https://www.instagram.com/arigato.devan/';
        }

        update_site_setting('insta_follower_count', $count, $pdo);
        update_site_setting('insta_handle', $handle, $pdo);
        update_site_setting('insta_url', $url, $pdo);

        $success = "Settings updated successfully! The navbar now displays " . htmlspecialchars($count) . ".";
    }
}

// Current values
$current_count = site_setting('insta_follower_count', '17K+');
$current_handle = site_setting('insta_handle', '@arigato.devan');
$current_url = site_setting('insta_url', 'https://www.instagram.com/arigato.devan/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Site Settings — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
.main { margin-left: 220px; padding: 28px 32px 80px; max-width: 1000px; }
.topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
.tb-title { font-size: 1.35rem; font-weight: 900; display: flex; align-items: center; gap: 10px; }
.tb-title i { color: #38bdf8; }

/* Flash message */
.flash { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-radius: 12px; font-size: .85rem; font-weight: 600; margin-bottom: 20px; }
.flash-ok { background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.3); color: #86efac; }
.flash-err { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.3); color: #fca5a5; }

/* Card */
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

/* Live Preview box */
.preview-box {
  background: rgba(255,255,255,0.03); border: 1px dashed rgba(139,92,246,0.3);
  border-radius: 14px; padding: 20px; margin-bottom: 24px;
}
.preview-label { font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 12px; display: block; }
.preview-inner {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 18px; border-radius: 999px;
  background: rgba(255,255,255,0.85);
  box-shadow: 0 4px 16px rgba(0,0,0,0.25);
  transition: all .2s ease;
}
.preview-inner i.fa-instagram {
  font-size: 1.15rem;
  background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.preview-handle { font-size: .88rem; font-weight: 600; color: #1e293b; }
.preview-dot {
  width: 6px; height: 6px; background: #22c55e; border-radius: 50%; display: inline-block;
  animation: pulseDot 2s ease-in-out infinite;
}
.preview-count { font-size: .92rem; font-weight: 800; color: #0f172a; }

@keyframes pulseDot {
  0%,100% { opacity:1; transform:scale(1); }
  50% { opacity:0.4; transform:scale(0.7); }
}

/* Form controls */
.form-group { margin-bottom: 20px; }
.form-label { display: block; font-size: .82rem; font-weight: 700; margin-bottom: 8px; color: var(--text); }
.form-hint { font-size: .74rem; color: var(--muted); margin-top: 6px; line-height: 1.5; }
.form-input {
  width: 100%; max-width: 480px; padding: 12px 16px; border-radius: 12px;
  background: var(--surface2); border: 1px solid var(--border); color: var(--text);
  font-size: .9rem; font-family: inherit; transition: border-color .2s;
}
.form-input:focus { outline: none; border-color: var(--accent2); box-shadow: 0 0 0 3px rgba(192,132,252,0.15); }

/* Preset pills */
.presets-wrap { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; align-items: center; }
.presets-label { font-size: .72rem; font-weight: 700; color: var(--muted); margin-right: 4px; }
.preset-btn {
  background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.25);
  color: var(--accent2); padding: 5px 12px; border-radius: 999px;
  font-size: .75rem; font-weight: 700; cursor: pointer; transition: all .15s ease;
}
.preset-btn:hover { background: var(--accent); color: #fff; transform: translateY(-1px); }

/* Submit button */
.btn-save {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 13px 26px; border-radius: 12px;
  background: linear-gradient(135deg, #8b5cf6, #ec4899);
  color: #fff; font-size: .88rem; font-weight: 800; border: none; cursor: pointer;
  box-shadow: 0 4px 18px rgba(139,92,246,0.35); transition: all .2s ease;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(139,92,246,0.5); }

@media(max-width: 900px) {
  .sidebar { width: 58px; }
  .sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span { display: none; }
  .main { margin-left: 58px; padding: 20px 16px 80px; }
}
@media(max-width: 768px) {
  .sidebar { display: none; }
  .main { margin-left: 0; padding: 14px 14px 90px; }
}
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
    <a href="analytics.php" class="sb-link"><i class="fa-solid fa-chart-line" style="color:#d4f938;"></i> <span style="color:#d4f938; font-weight:700;">Analytics</span></a>
    <div class="sb-sec">Content</div>
    <a href="upload_prompt.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="manage_prompts.php" class="sb-link"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
    <a href="prompt_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="potd_manager.php" class="sb-link"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
    <a href="trending_settings.php" class="sb-link"><i class="fa-solid fa-fire-flame-curved"></i> <span>Trending Settings</span></a>
    <a href="gallery_carousel_manager.php" class="sb-link"><i class="fa-solid fa-images"></i> <span>Edit Gallery Carousel</span></a>
    <div class="sb-sec">Blog</div>
    <a href="blog_admin.php" class="sb-link"><i class="fa-solid fa-pen-nib" style="color:#38bdf8;"></i> <span style="color:#38bdf8; font-weight:700;">Blog Admin</span></a>
    <a href="blog_create.php" class="sb-link"><i class="fa-solid fa-plus" style="color:#38bdf8;"></i> <span style="color:#38bdf8; font-weight:700;">New Post</span></a>
    <div class="sb-sec">Community</div>
    <a href="feedback_admin.php" class="sb-link"><i class="fa-solid fa-comments" style="color:#fb923c;"></i> <span style="color:#fb923c; font-weight:700;">Feedback Manager</span></a>
    <div class="sb-sec">Happy Users</div>
    <a href="happy_users_admin.php?tab=upload" class="sb-link"><i class="fa-solid fa-cloud-arrow-up"></i> <span>Upload Screenshots</span></a>
    <a href="happy_users_admin.php?tab=manage" class="sb-link"><i class="fa-solid fa-images"></i> <span>Manage Pics</span></a>
    <div class="sb-sec nm-dash-brand">Curated AI Prompts</div>
    <a href="curated_admin.php" class="sb-link nm-dash-brand"><i class="fa-solid fa-upload" style="color:#e879f9;"></i> <span style="color:#e879f9; font-weight:700;">Curated — Upload</span></a>
    <a href="curated_manage.php" class="sb-link nm-dash-brand"><i class="fa-solid fa-table-list" style="color:#e879f9;"></i> <span style="color:#e879f9; font-weight:700;">Curated — Manage</span></a>
    <a href="curated_links.php" class="sb-link nm-dash-brand"><i class="fa-solid fa-link" style="color:#e879f9;"></i> <span style="color:#e879f9; font-weight:700;">Curated — Links</span></a>
    <div class="sb-sec">Users</div>
    <a href="user_management.php" class="sb-link"><i class="fa-solid fa-users"></i> <span>Users</span></a>
    <div class="sb-sec">Settings</div>
    <a href="site_settings.php" class="sb-link active"><i class="fa-solid fa-gear" style="color:#38bdf8;"></i> <span style="color:#38bdf8; font-weight:700;">Site Settings</span></a>
    <div class="sb-sec">Tools</div>
    <a href="index.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Site</span></a>
  </nav>
  <div class="sb-bottom">
    <a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-gear"></i> Site Settings</div>
  </div>

  <?php if ($success): ?>
    <div class="flash flash-ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="flash flash-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="settings-card">
    <div class="card-header">
      <div class="card-title">
        <i class="fa-brands fa-instagram"></i>
        <span>Instagram Follower Badge Settings</span>
      </div>
    </div>
    <div class="card-body">
      <!-- Live Preview -->
      <div class="preview-box">
        <span class="preview-label"><i class="fa-solid fa-eye"></i> Live Header Preview (How it looks on site):</span>
        <div class="preview-inner">
          <i class="fa-brands fa-instagram"></i>
          <span class="preview-handle" id="prevHandle"><?= htmlspecialchars($current_handle) ?></span>
          <span class="preview-dot"></span>
          <span class="preview-count" id="prevCount"><?= htmlspecialchars($current_count) ?></span>
        </div>
      </div>

      <form method="POST" action="site_settings.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <div class="form-group">
          <label class="form-label" for="insta_follower_count">Instagram Follower Count (e.g. 17K+, 20K+, 50K+)</label>
          <input type="text" id="insta_follower_count" name="insta_follower_count" class="form-input" maxlength="30" value="<?= htmlspecialchars($current_count) ?>" required placeholder="e.g. 17K+">
          <p class="form-hint">This text appears directly in the site header navigation next to your Instagram handle.</p>

          <div class="presets-wrap">
            <span class="presets-label">Quick presets:</span>
            <button type="button" class="preset-btn" onclick="setPreset('17K+')">17K+</button>
            <button type="button" class="preset-btn" onclick="setPreset('18K+')">18K+</button>
            <button type="button" class="preset-btn" onclick="setPreset('20K+')">20K+</button>
            <button type="button" class="preset-btn" onclick="setPreset('25K+')">25K+</button>
            <button type="button" class="preset-btn" onclick="setPreset('50K+')">50K+</button>
            <button type="button" class="preset-btn" onclick="setPreset('100K+')">100K+</button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="insta_handle">Instagram Handle</label>
          <input type="text" id="insta_handle" name="insta_handle" class="form-input" maxlength="100" value="<?= htmlspecialchars($current_handle) ?>" required placeholder="@arigato.devan">
          <p class="form-hint">Displayed next to the Instagram icon in the navbar.</p>
        </div>

        <div class="form-group">
          <label class="form-label" for="insta_url">Instagram Profile URL</label>
          <input type="url" id="insta_url" name="insta_url" class="form-input" maxlength="255" value="<?= htmlspecialchars($current_url) ?>" required placeholder="https://www.instagram.com/arigato.devan/">
          <p class="form-hint">Destination link when users click on the Instagram badge.</p>
        </div>

        <button type="submit" class="btn-save">
          <i class="fa-solid fa-floppy-disk"></i> Save Settings
        </button>
      </form>
    </div>
  </div>
</main>

<script>
var countInput = document.getElementById('insta_follower_count');
var handleInput = document.getElementById('insta_handle');
var prevCount = document.getElementById('prevCount');
var prevHandle = document.getElementById('prevHandle');

if (countInput && prevCount) {
  countInput.addEventListener('input', function() {
    prevCount.textContent = this.value.trim() || '17K+';
  });
}

if (handleInput && prevHandle) {
  handleInput.addEventListener('input', function() {
    prevHandle.textContent = this.value.trim() || '@arigato.devan';
  });
}

function setPreset(val) {
  if (countInput && prevCount) {
    countInput.value = val;
    prevCount.textContent = val;
    countInput.focus();
  }
}
</script>
</body>
</html>
