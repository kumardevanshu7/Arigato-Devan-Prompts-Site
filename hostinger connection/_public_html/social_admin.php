<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
date_default_timezone_set('Asia/Kolkata');
require_once "db.php";
require_once __DIR__ . '/includes/settings_helper.php';

// Protect page (Admin Only)
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] = "You do not have permission to access this page.";
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
        $p_name = trim($_POST['profile_name'] ?? 'Arigato Devan');
        $p_title = trim($_POST['profile_title'] ?? 'AI Prompt Creator & Digital Artist');
        $p_handle = trim($_POST['profile_handle'] ?? '@arigato.devan');
        $p_handle_url = trim($_POST['profile_handle_url'] ?? 'https://www.instagram.com/arigato.devan/');
        $c_email = trim($_POST['contact_email'] ?? 'devansh.grow@gmail.com');
        $c_resp = trim($_POST['response_time'] ?? 'Within 24 hours');

        // Parse platforms
        $raw_links = $_POST['links'] ?? [];
        $clean_links = [];

        if (is_array($raw_links)) {
            foreach ($raw_links as $lnk) {
                $id = preg_replace('/[^a-z0-9_-]/i', '', strtolower(trim($lnk['id'] ?? '')));
                $name = trim($lnk['name'] ?? '');
                $icon = trim($lnk['icon'] ?? 'fa-solid fa-link');
                $url = trim($lnk['url'] ?? '');
                $enabled = !empty($lnk['enabled']);

                if ($name === '') continue;

                $clean_links[] = [
                    'id'      => $id ?: 'link_' . uniqid(),
                    'name'    => $name,
                    'icon'    => $icon,
                    'url'     => $url,
                    'enabled' => $enabled
                ];
            }
        }

        $new_config = [
            'profile_name'       => $p_name ?: 'Arigato Devan',
            'profile_title'      => $p_title ?: 'AI Prompt Creator & Digital Artist',
            'profile_handle'     => $p_handle ?: '@arigato.devan',
            'profile_handle_url' => $p_handle_url ?: 'https://www.instagram.com/arigato.devan/',
            'contact_email'      => $c_email ?: 'devansh.grow@gmail.com',
            'response_time'      => $c_resp ?: 'Within 24 hours',
            'social_links'       => $clean_links
        ];

        if (save_contact_social_config($new_config, $pdo)) {
            // Also sync navbar instagram settings if present
            if ($p_handle !== '') update_site_setting('insta_handle', $p_handle, $pdo);
            if ($p_handle_url !== '') update_site_setting('insta_url', $p_handle_url, $pdo);

            $success = "Social and Contact settings updated successfully! Changes are live on About and Contact pages.";
        } else {
            $error = "Failed to save settings. Please check database permissions.";
        }
    }
}

// Current config
$cfg = get_contact_social_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Social &amp; Contact Links — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,900;1,700&display=swap" rel="stylesheet">
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
.main { margin-left: 220px; padding: 28px 32px 80px; max-width: 1050px; }
.topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
.tb-title { font-size: 1.35rem; font-weight: 900; display: flex; align-items: center; gap: 10px; }
.tb-title i { color: #f472b6; }

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
.card-body { padding: 24px; }

/* Live Preview Box */
.preview-box {
  background: #f8f6f2; /* Site warm beige */
  border: 1px solid rgba(200, 217, 230, 0.9);
  border-radius: 24px;
  padding: 32px 24px 28px;
  margin-bottom: 28px;
  text-align: center;
  color: #2F4156;
  box-shadow: 0 12px 36px rgba(0,0,0,0.15);
  position: relative;
  max-width: 440px;
  margin-left: auto;
  margin-right: auto;
}
.prev-badge-tag {
  position: absolute;
  top: 12px;
  left: 14px;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #567C8D;
  background: rgba(255,255,255,0.85);
  padding: 3px 10px;
  border-radius: 999px;
  border: 1px solid rgba(86, 124, 141, 0.2);
}
.prev-avatar-wrap {
  width: 100px;
  height: 100px;
  margin: 0 auto 10px;
  position: relative;
}
.prev-avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #fff;
  box-shadow: 0 4px 16px rgba(47,65,86,0.15);
}
.prev-verified {
  position: absolute;
  bottom: 4px;
  right: 4px;
  width: 24px;
  height: 24px;
  background: linear-gradient(135deg, #11FFC9, #2FA6C6);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.65rem;
  border: 2px solid #fff;
}
.prev-flip-hint {
  font-size: 0.72rem;
  font-weight: 600;
  color: #567C8D;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}
.prev-name {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: 1.65rem;
  font-weight: 900;
  color: #2F4156;
  margin-bottom: 4px;
  line-height: 1.2;
}
.prev-title {
  font-size: 0.82rem;
  font-weight: 600;
  color: #567C8D;
  margin-bottom: 6px;
}
.prev-handle {
  font-size: 0.85rem;
  font-weight: 700;
  color: #F5709D;
  margin-bottom: 16px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.prev-social-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}
.prev-icon-btn {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #ffffff;
  border: 1.5px solid rgba(200, 217, 230, 0.9);
  color: #2F4156;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  box-shadow: 0 4px 12px rgba(47, 65, 86, 0.08);
  transition: all 0.2s ease;
}

/* Form controls */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}
.form-group {
  margin-bottom: 18px;
}
.form-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 8px;
}
.form-input {
  width: 100%;
  padding: 12px 14px;
  border-radius: 10px;
  background: var(--surface2);
  border: 1px solid var(--border);
  color: var(--text);
  font-size: 0.85rem;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.form-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
}
.form-hint {
  font-size: 0.72rem;
  color: var(--muted);
  margin-top: 5px;
}

/* Platform rows */
.platform-item {
  display: flex;
  align-items: center;
  gap: 14px;
  background: var(--surface2);
  border: 1px solid var(--border2);
  border-radius: 14px;
  padding: 14px 18px;
  margin-bottom: 12px;
  transition: border-color 0.2s, background 0.2s;
}
.platform-item:hover {
  border-color: var(--border);
  background: rgba(139, 92, 246, 0.05);
}
.plat-icon-badge {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  flex-shrink: 0;
  background: rgba(255,255,255,0.06);
}
.plat-icon-badge.instagram { color: #f472b6; background: rgba(244,114,182,0.12); }
.plat-icon-badge.email { color: #fbbf24; background: rgba(251,191,36,0.12); }
.plat-icon-badge.gallery { color: #38bdf8; background: rgba(56,189,248,0.12); }
.plat-icon-badge.whatsapp { color: #4ade80; background: rgba(74,222,128,0.12); }
.plat-icon-badge.telegram { color: #22d3ee; background: rgba(34,211,238,0.12); }
.plat-icon-badge.youtube { color: #f87171; background: rgba(248,113,113,0.12); }
.plat-icon-badge.twitter { color: #e2e8f0; background: rgba(255,255,255,0.1); }
.plat-icon-badge.pinterest { color: #e11d48; background: rgba(225,29,72,0.12); }
.plat-icon-badge.threads { color: #cbd5e1; background: rgba(203,213,225,0.12); }
.plat-icon-badge.discord { color: #818cf8; background: rgba(129,140,248,0.12); }

.plat-details {
  flex: 1;
  min-width: 0;
}
.plat-name {
  font-size: 0.85rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: 4px;
}
.plat-input-wrap {
  position: relative;
}
.plat-input {
  width: 100%;
  padding: 8px 12px;
  border-radius: 8px;
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text);
  font-size: 0.8rem;
  font-family: inherit;
  outline: none;
}
.plat-input:focus {
  border-color: var(--accent2);
}

/* Switch toggle */
.switch-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--muted);
  cursor: pointer;
  user-select: none;
}
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
  flex-shrink: 0;
}
.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: rgba(255,255,255,0.15);
  border-radius: 34px;
  transition: 0.25s;
}
.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  border-radius: 50%;
  transition: 0.25s;
}
input:checked + .toggle-slider {
  background: linear-gradient(135deg, #10b981, #059669);
}
input:checked + .toggle-slider:before {
  transform: translateX(20px);
}

/* Bottom submit bar */
.submit-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding-top: 14px;
  border-top: 1px solid var(--border2);
  flex-wrap: wrap;
}
.save-btn {
  padding: 12px 28px;
  border-radius: 12px;
  background: linear-gradient(135deg, #8b5cf6, #ec4899);
  color: #fff;
  border: none;
  font-size: 0.88rem;
  font-weight: 800;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 18px rgba(139, 92, 246, 0.35);
  transition: transform 0.2s, box-shadow 0.2s;
}
.save-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(139, 92, 246, 0.5);
}
.view-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--accent2);
  text-decoration: none;
  padding: 8px 14px;
  border-radius: 8px;
  background: rgba(139,92,246,0.1);
  border: 1px solid rgba(139,92,246,0.2);
}
.view-link:hover {
  background: rgba(139,92,246,0.2);
}

@media (max-width: 768px) {
  .sidebar { display: none; }
  .main { margin-left: 0; padding: 20px 16px 60px; }
  .platform-item { flex-direction: column; align-items: flex-start; }
  .plat-details { width: 100%; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<!-- MAIN CONTENT -->
<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-share-nodes"></i> Social &amp; Contact Manager</div>
    <div style="display:flex;gap:10px;">
      <a href="about.php" target="_blank" class="view-link"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview About</a>
      <a href="contact.php" target="_blank" class="view-link"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview Contact</a>
    </div>
  </div>

  <?php if ($success): ?>
  <div class="flash flash-ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="flash flash-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- LIVE CARD PREVIEW -->
  <div class="preview-box">
    <span class="prev-badge-tag"><i class="fa-solid fa-eye"></i> Live Profile Card Preview</span>
    <div class="prev-avatar-wrap">
      <img src="aboutmepics/new.webp" alt="Avatar" class="prev-avatar-img" onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=arigato'">
      <div class="prev-verified"><i class="fa-solid fa-check"></i></div>
    </div>
    <div class="prev-flip-hint"><i class="fa-solid fa-rotate"></i> Click to flip</div>
    <h2 class="prev-name" id="prevName"><?= htmlspecialchars($cfg['profile_name']) ?></h2>
    <div class="prev-title" id="prevTitle"><?= htmlspecialchars($cfg['profile_title']) ?></div>
    <div class="prev-handle"><i class="fa-brands fa-instagram"></i> <span id="prevHandle"><?= htmlspecialchars($cfg['profile_handle']) ?></span></div>

    <div class="prev-social-row" id="prevSocialRow">
      <?php foreach ($cfg['social_links'] as $s): ?>
        <?php if (!empty($s['enabled'])): ?>
        <span class="prev-icon-btn" title="<?= htmlspecialchars($s['name']) ?>" data-prev-id="<?= htmlspecialchars($s['id']) ?>">
          <i class="<?= htmlspecialchars($s['icon']) ?>"></i>
        </span>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <form method="POST" action="social_admin.php" id="socialForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

    <!-- CARD 1: Profile & Card Identity -->
    <div class="settings-card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-user-pen"></i> Creator Profile &amp; Card Details (About Page)</div>
      </div>
      <div class="card-body">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label" for="profile_name">Creator Name</label>
            <input type="text" id="profile_name" name="profile_name" class="form-input" value="<?= htmlspecialchars($cfg['profile_name']) ?>" required placeholder="Arigato Devan">
            <p class="form-hint">Displayed as the main title on the profile card.</p>
          </div>
          <div class="form-group">
            <label class="form-label" for="profile_title">Job Title / Subtitle</label>
            <input type="text" id="profile_title" name="profile_title" class="form-input" value="<?= htmlspecialchars($cfg['profile_title']) ?>" required placeholder="AI Prompt Creator &amp; Digital Artist">
            <p class="form-hint">Displayed beneath the name.</p>
          </div>
          <div class="form-group">
            <label class="form-label" for="profile_handle">Instagram Handle</label>
            <input type="text" id="profile_handle" name="profile_handle" class="form-input" value="<?= htmlspecialchars($cfg['profile_handle']) ?>" required placeholder="@arigato.devan">
            <p class="form-hint">Shown in pink with the Instagram icon.</p>
          </div>
          <div class="form-group">
            <label class="form-label" for="profile_handle_url">Instagram Profile URL</label>
            <input type="url" id="profile_handle_url" name="profile_handle_url" class="form-input" value="<?= htmlspecialchars($cfg['profile_handle_url']) ?>" required placeholder="https://www.instagram.com/arigato.devan/">
            <p class="form-hint">Where clicking the handle leads.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- CARD 2: Contact Details -->
    <div class="settings-card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-address-book" style="color:#22d3ee;"></i> Contact Page Specifics (Contact Page)</div>
      </div>
      <div class="card-body">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label" for="contact_email">Public Contact Email</label>
            <input type="email" id="contact_email" name="contact_email" class="form-input" value="<?= htmlspecialchars($cfg['contact_email']) ?>" required placeholder="devansh.grow@gmail.com">
            <p class="form-hint">Displayed on the Contact page Email card.</p>
          </div>
          <div class="form-group">
            <label class="form-label" for="response_time">Response Time Note</label>
            <input type="text" id="response_time" name="response_time" class="form-input" value="<?= htmlspecialchars($cfg['response_time']) ?>" required placeholder="Within 24 hours">
            <p class="form-hint">Displayed on the response time info card.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- CARD 3: Social Links Manager -->
    <div class="settings-card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-share-nodes" style="color:#f472b6;"></i> Social Links &amp; Icons (Displayed on Card &amp; Contact)</div>
      </div>
      <div class="card-body">
        <div id="platformsList">
          <?php foreach ($cfg['social_links'] as $idx => $soc): ?>
          <div class="platform-item" data-plat-id="<?= htmlspecialchars($soc['id']) ?>">
            <input type="hidden" name="links[<?= $idx ?>][id]" value="<?= htmlspecialchars($soc['id']) ?>">
            <input type="hidden" name="links[<?= $idx ?>][name]" value="<?= htmlspecialchars($soc['name']) ?>">
            <input type="hidden" name="links[<?= $idx ?>][icon]" value="<?= htmlspecialchars($soc['icon']) ?>">

            <div class="plat-icon-badge <?= htmlspecialchars($soc['id']) ?>">
              <i class="<?= htmlspecialchars($soc['icon']) ?>"></i>
            </div>

            <div class="plat-details">
              <div class="plat-name"><?= htmlspecialchars($soc['name']) ?></div>
              <div class="plat-input-wrap">
                <input type="text" name="links[<?= $idx ?>][url]" class="plat-input plat-url-field" value="<?= htmlspecialchars($soc['url']) ?>" placeholder="<?= $soc['id'] === 'email' ? 'mailto:you@email.com' : 'https://...' ?>" data-target-id="<?= htmlspecialchars($soc['id']) ?>">
              </div>
            </div>

            <label class="switch-label">
              <span class="toggle-switch">
                <input type="checkbox" name="links[<?= $idx ?>][enabled]" value="1" <?= !empty($soc['enabled']) ? 'checked' : '' ?> class="plat-toggle" data-target-id="<?= htmlspecialchars($soc['id']) ?>" data-icon="<?= htmlspecialchars($soc['icon']) ?>" data-name="<?= htmlspecialchars($soc['name']) ?>">
                <span class="toggle-slider"></span>
              </span>
              <span><?= !empty($soc['enabled']) ? 'Active' : 'Disabled' ?></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="submit-bar">
          <button type="submit" class="save-btn"><i class="fa-solid fa-floppy-disk"></i> Save Social &amp; Contact Settings</button>
          <span style="font-size:0.75rem;color:var(--muted);"><i class="fa-solid fa-shield-halved"></i> Changes sync instantly across About &amp; Contact.</span>
        </div>
      </div>
    </div>
  </form>
</main>

<script>
// Live update preview card
var nameInput = document.getElementById('profile_name');
var titleInput = document.getElementById('profile_title');
var handleInput = document.getElementById('profile_handle');
var prevName = document.getElementById('prevName');
var prevTitle = document.getElementById('prevTitle');
var prevHandle = document.getElementById('prevHandle');
var prevSocialRow = document.getElementById('prevSocialRow');

if (nameInput && prevName) {
  nameInput.addEventListener('input', function() {
    prevName.textContent = this.value.trim() || 'Arigato Devan';
  });
}
if (titleInput && prevTitle) {
  titleInput.addEventListener('input', function() {
    prevTitle.textContent = this.value.trim() || 'AI Prompt Creator & Digital Artist';
  });
}
if (handleInput && prevHandle) {
  handleInput.addEventListener('input', function() {
    prevHandle.textContent = this.value.trim() || '@arigato.devan';
  });
}

// Live update social icons in preview
function refreshPreviewIcons() {
  if (!prevSocialRow) return;
  prevSocialRow.innerHTML = '';
  document.querySelectorAll('.plat-toggle').forEach(function(toggle) {
    if (toggle.checked) {
      var id = toggle.dataset.targetId;
      var icon = toggle.dataset.icon;
      var name = toggle.dataset.name;
      var span = document.createElement('span');
      span.className = 'prev-icon-btn';
      span.title = name;
      span.innerHTML = '<i class="' + icon + '"></i>';
      prevSocialRow.appendChild(span);
    }
  });
}

document.querySelectorAll('.plat-toggle').forEach(function(toggle) {
  toggle.addEventListener('change', function() {
    var label = this.closest('.switch-label').querySelector('span:last-child');
    if (label) {
      label.textContent = this.checked ? 'Active' : 'Disabled';
    }
    refreshPreviewIcons();
  });
});
</script>
</body>
</html>
