<?php
require_once __DIR__ . '/bootstrap.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit();
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'save_settings') {
        ws_save_setting($pdo, 'publisher_name', trim($_POST['publisher_name'] ?? '') ?: 'Arigato Devan');
        ws_save_setting($pdo, 'ga_tracking_id', trim($_POST['ga_tracking_id'] ?? ''));
        $shareKeys = array_keys(ws_share_provider_options());
        $picked = [];
        foreach ($shareKeys as $k) {
            if (!empty($_POST['share_provider'][$k])) {
                $picked[] = $k;
            }
        }
        ws_save_setting($pdo, 'share_providers', implode(',', $picked));
        ws_save_setting($pdo, 'share_text_template', trim($_POST['share_text_template'] ?? ''));
        ws_save_setting($pdo, 'share_twitter_text', trim($_POST['share_twitter_text'] ?? ''));
        ws_save_setting($pdo, 'share_facebook_app_id', trim($_POST['share_facebook_app_id'] ?? ''));
        $existing_logo = ws_setting($pdo, 'publisher_logo', '');
        if (!empty($_FILES['publisher_logo']['tmp_name'])) {
            $up = ws_upload_image($_FILES['publisher_logo'], 'logo', 192, 192);
            if ($up) {
                if ($existing_logo && is_file(SITE_ROOT . '/' . $existing_logo)) {
                    @unlink(SITE_ROOT . '/' . $existing_logo);
                }
                ws_save_setting($pdo, 'publisher_logo', $up);
            }
        }
        $msg = 'Settings saved.';
    }
}

$pub_name = ws_setting($pdo, 'publisher_name', 'Arigato Devan');
$ga_id = ws_setting($pdo, 'ga_tracking_id', '');
$logo = ws_setting($pdo, 'publisher_logo', '');
$share_enabled = ws_share_providers_enabled($pdo);
$share_tpl = ws_setting($pdo, 'share_text_template', '') ?: '';
$share_twitter = ws_setting($pdo, 'share_twitter_text', '') ?: '';
$share_fb_app = ws_setting($pdo, 'share_facebook_app_id', '') ?: '';
$share_options = ws_share_provider_options();
$csrf = generate_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Web Stories Settings</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/editor.css?v=13">
</head>
<body class="ws-editor-body">
<header class="ws-ed-top">
    <a href="index.php" class="ws-ed-back"><i class="fa-solid fa-arrow-left"></i> Stories</a>
    <h1>Settings</h1>
    <span></span>
</header>
<main class="ws-settings-page">
    <?php if ($msg): ?><div class="ws-flash ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="ws-settings-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="save_settings">
        <section class="ws-panel">
            <h2>Publisher (Google requirement)</h2>
            <p class="ws-hint">Logo must be square, min 96×96px, non-transparent background (JPG/PNG).</p>
            <label>Publisher Name</label>
            <input type="text" name="publisher_name" value="<?= htmlspecialchars($pub_name) ?>">
            <label>Publisher Logo</label>
            <?php
            $logoPreview = $logo ? '../../' . $logo : '';
            ws_render_upload_zone('publisher_logo', $logoPreview, 'Square · min 96×96 · JPG or PNG', true);
            ?>
        </section>
        <section class="ws-panel">
            <h2>Google Analytics</h2>
            <p class="ws-hint">GA4 ID (G-XXXX) or Universal Analytics (UA-XXXX). Optional — added to every AMP story.</p>
            <label>Tracking ID</label>
            <input type="text" name="ga_tracking_id" value="<?= htmlspecialchars($ga_id) ?>" placeholder="G-XXXXXXXX or UA-000000-1">
        </section>
        <section class="ws-panel">
            <h2>Share popup</h2>
            <p class="ws-hint">AMP share sheet — platforms, order (top to bottom), aur custom text. Popup design AMP control karta hai; yahan content configure hota hai.</p>
            <label>Platforms (order = display order)</label>
            <div class="ws-share-picks">
                <?php foreach ($share_options as $key => $label): ?>
                <label class="ws-share-pick">
                    <input type="checkbox" name="share_provider[<?= htmlspecialchars($key) ?>]" value="1" <?= in_array($key, $share_enabled, true) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($label) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <label>WhatsApp / SMS / Email message</label>
            <textarea name="share_text_template" rows="3" placeholder="🔥 {title} — Arigato Devan Web Story&#10;{url}"><?= htmlspecialchars($share_tpl) ?></textarea>
            <p class="ws-hint">Placeholders: <code>{title}</code> <code>{url}</code> <code>{description}</code></p>
            <label>X (Twitter) text</label>
            <input type="text" name="share_twitter_text" value="<?= htmlspecialchars($share_twitter) ?>" placeholder="{title} — swipe through on Arigato Devan">
            <label>Facebook App ID (optional)</label>
            <input type="text" name="share_facebook_app_id" value="<?= htmlspecialchars($share_fb_app) ?>" placeholder="Leave blank for default">
        </section>
        <button type="submit" class="ws-btn primary">Save Settings</button>
    </form>
</main>
<script src="../assets/js/upload-zone.js?v=1"></script>
</body>
</html>
