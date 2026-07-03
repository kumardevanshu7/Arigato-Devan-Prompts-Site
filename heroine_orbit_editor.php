<?php
session_start();
require_once 'db.php';
require_once 'includes/heroines_orbit.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['username'] ?? 'Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_layouts') {
        $json = $_POST['orbit_layouts_json'] ?? '';
        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            $_SESSION['heroine_err'] = 'Invalid layout data — could not save.';
        } else {
            foreach (heroines_orbit_viewport_keys() as $vp) {
                $rows = isset($payload[$vp]) && is_array($payload[$vp]) ? $payload[$vp] : [];
                heroines_save_viewport_layout($pdo, $vp, $rows);
            }
            $_SESSION['heroine_msg'] = 'Landing circle layouts saved for laptop, tablet & mobile.';
        }
        header('Location: heroine_orbit_editor.php');
        exit();
    }

    if ($action === 'reset_all_layouts') {
        heroines_clear_viewport_layout($pdo, null);
        $_SESSION['heroine_msg'] = 'Custom layouts cleared — landing page uses built-in defaults again.';
        header('Location: heroine_orbit_editor.php');
        exit();
    }

    if ($action === 'reset_viewport') {
        $vp = $_POST['viewport'] ?? '';
        if (in_array($vp, heroines_orbit_viewport_keys(), true)) {
            heroines_clear_viewport_layout($pdo, $vp);
            $_SESSION['heroine_msg'] = ucfirst($vp) . ' layout reset to built-in defaults.';
        }
        header('Location: heroine_orbit_editor.php');
        exit();
    }

    if ($action === 'shuffle_orbit') {
        $active = $pdo->query(
            'SELECT * FROM heroines WHERE is_active = 1 AND circle_image != "" ORDER BY sort_order ASC, name ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
        if (empty($active)) {
            $_SESSION['heroine_err'] = 'Add at least one live heroine with a circle photo first.';
        } else {
            $stats = heroines_redistribute_landing_photos($pdo, $active, true);
            $_SESSION['heroine_msg'] = 'Photos shuffled equally — Laptop ' . $stats['laptop']
                . ', Tablet ' . $stats['tablet'] . ', Mobile ' . $stats['mobile'] . ' circles (positions kept).';
        }
        header('Location: heroine_orbit_editor.php');
        exit();
    }
}

$heroines = $pdo->query(
    'SELECT * FROM heroines WHERE is_active = 1 AND circle_image != "" ORDER BY sort_order ASC, name ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$layouts  = heroines_editor_bootstrap_layouts($pdo, $pdo->query('SELECT * FROM heroines ORDER BY sort_order ASC, name ASC')->fetchAll(PDO::FETCH_ASSOC));
$defaults = [];
$allRows  = $pdo->query('SELECT * FROM heroines ORDER BY sort_order ASC, name ASC')->fetchAll(PDO::FETCH_ASSOC);
$active   = array_values(array_filter($allRows, static fn($h) => !empty($h['is_active']) && !empty($h['circle_image'])));

foreach (heroines_orbit_viewport_keys() as $vp) {
    $slots = heroines_orbit_default_slots($vp);
    $map   = heroines_build_equal_shuffle_map($active, count($slots), 'editor-def-' . $vp);
    foreach ($slots as $i => &$slot) {
        if (!empty($map[$i])) {
            $slot['heroine_id'] = (int) $map[$i];
        }
        $slot['uid'] = 'c' . ($i + 1);
    }
    unset($slot);
    $defaults[$vp] = $slots;
}

$custom_flags = [];
foreach (heroines_orbit_viewport_keys() as $vp) {
    $custom_flags[$vp] = heroines_has_custom_layout($pdo, $vp);
}

$msg = $_SESSION['heroine_msg'] ?? '';
$err = $_SESSION['heroine_err'] ?? '';
unset($_SESSION['heroine_msg'], $_SESSION['heroine_err']);

$editor_heroines = array_map(static function ($h) {
    return ['id' => (int) $h['id'], 'name' => $h['name'], 'circle_image' => $h['circle_image']];
}, $heroines);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Landing Circles — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/heroine-orbit-editor.css?v=20260779">
<?php include_once 'gtag.php'; ?>
<style>
:root{--bg:#07060f;--surface:#0f0d1e;--border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);--accent:#8b5cf6;--accent2:#c084fc;--pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;--red:#f87171;--text:#e2e0ff;--muted:#9490bb;--font:'Inter',sans-serif}
*{margin:0;padding:0;box-sizing:border-box}body{background:var(--bg);color:var(--text);font-family:var(--font);min-height:100vh}
.main{max-width:1200px;margin:0 auto;padding:24px 20px 80px}
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:22px;flex-wrap:wrap}
.tb-title{font-size:1.25rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;flex:1}
.tb-back{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;background:rgba(139,92,246,0.1);color:var(--accent2);border:1px solid rgba(139,92,246,0.22)}
.tb-preview{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;background:rgba(34,211,238,0.08);color:var(--cyan);border:1px solid rgba(34,211,238,0.2)}
.card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:18px}
.flash-ok{background:rgba(74,222,128,0.07);border:1px solid rgba(74,222,128,0.22);color:var(--green);padding:11px 16px;border-radius:12px;font-size:.83rem;font-weight:700;margin-bottom:16px}
.flash-err{background:rgba(248,113,113,0.07);border:1px solid rgba(248,113,113,0.22);color:var(--red);padding:11px 16px;border-radius:12px;font-size:.83rem;font-weight:700;margin-bottom:16px}
</style>
</head>
<body>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-circle-nodes"></i> Edit Circles on Landing Page</div>
    <a href="heroine_admin.php" class="tb-back"><i class="fa-solid fa-arrow-left"></i> Back to Heroines</a>
    <a href="my_heroines.php" target="_blank" class="tb-preview"><i class="fa-solid fa-eye"></i> Preview Landing</a>
  </div>

  <?php if ($msg): ?><div class="flash-ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="flash-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="card orbit-editor-wrap">
    <p class="orbit-editor-intro">
      Manually place &amp; resize landing circles for <strong>three screen sizes</strong>.
      Drag a circle to move it, drag the purple corner dot to resize, pick a photo from the side panel.
      Save when done — each tab is independent (laptop, tablet, mobile).
    </p>

    <div class="orbit-editor-tabs">
      <button type="button" class="orbit-editor-tab active" data-orbit-tab="laptop">
        <i class="fa-solid fa-laptop"></i> Laptop / Desktop
        <?php if ($custom_flags['laptop']): ?><i class="fa-solid fa-pen" title="Custom saved"></i><?php endif; ?>
      </button>
      <button type="button" class="orbit-editor-tab" data-orbit-tab="tablet">
        <i class="fa-solid fa-tablet-screen-button"></i> Tablet
        <?php if ($custom_flags['tablet']): ?><i class="fa-solid fa-pen" title="Custom saved"></i><?php endif; ?>
      </button>
      <button type="button" class="orbit-editor-tab" data-orbit-tab="mobile">
        <i class="fa-solid fa-mobile-screen"></i> Mobile
        <?php if ($custom_flags['mobile']): ?><i class="fa-solid fa-pen" title="Custom saved"></i><?php endif; ?>
      </button>
    </div>

    <div class="orbit-editor-layout">
      <div class="orbit-editor-canvas-wrap" id="orbitEditorCanvasWrap">
        <div class="orbit-editor-canvas-label">
          <span id="orbitCanvasLabel">Laptop / Desktop preview</span>
          <span style="color:var(--muted);font-weight:600">Drag · Resize · Click empty to deselect</span>
        </div>
        <div class="orbit-editor-canvas" id="orbitEditorCanvas">
          <div class="orbit-editor-mock" aria-hidden="true">
            <div class="orbit-editor-mock-inner">
              <p class="eyebrow">Featured Profiles</p>
              <h2><em>Heroines</em> Details</h2>
              <p>The beautiful faces behind our prompts</p>
              <span class="fake-btn">My Heroines</span>
            </div>
          </div>
        </div>
      </div>

      <aside class="orbit-editor-side" id="orbitEditorSide"></aside>
    </div>

    <div class="orbit-editor-actions">
      <button type="button" class="btn-oe btn-oe-add" id="oeAddCircle"><i class="fa-solid fa-plus"></i> Add Circle</button>
      <button type="button" class="btn-oe btn-oe-secondary" id="oeLoadDefaults"><i class="fa-solid fa-rotate-left"></i> Load Defaults (this tab)</button>

      <form method="POST" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
        <input type="hidden" name="action" value="shuffle_orbit">
        <button type="submit" class="btn-oe btn-oe-shuffle"><i class="fa-solid fa-shuffle"></i> Shuffle Photos Equally</button>
      </form>

      <form method="POST" id="orbitSaveForm" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
        <input type="hidden" name="action" value="save_layouts">
        <input type="hidden" name="orbit_layouts_json" id="orbitLayoutsJson" value="">
        <button type="submit" class="btn-oe btn-oe-primary"><i class="fa-solid fa-floppy-disk"></i> Save All Layouts</button>
      </form>

      <form method="POST" style="display:inline" onsubmit="return confirm('Reset ALL custom layouts (laptop, tablet, mobile)?')">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
        <input type="hidden" name="action" value="reset_all_layouts">
        <button type="submit" class="btn-oe btn-oe-danger"><i class="fa-solid fa-trash"></i> Reset All Custom</button>
      </form>
    </div>

    <p class="orbit-editor-status <?= ($custom_flags['laptop'] || $custom_flags['tablet'] || $custom_flags['mobile']) ? 'is-custom' : '' ?>">
      <?php if ($custom_flags['laptop'] || $custom_flags['tablet'] || $custom_flags['mobile']): ?>
        <i class="fa-solid fa-circle-check"></i>
        Custom layout active:
        <?= $custom_flags['laptop'] ? 'Laptop' : '' ?>
        <?= $custom_flags['tablet'] ? ($custom_flags['laptop'] ? ' · Tablet' : 'Tablet') : '' ?>
        <?= $custom_flags['mobile'] ? (($custom_flags['laptop'] || $custom_flags['tablet']) ? ' · Mobile' : 'Mobile') : '' ?>
      <?php else: ?>
        Using built-in default positions until you save a custom layout.
      <?php endif; ?>
    </p>
  </div>
</main>

<script>
window.HEROINE_ORBIT_EDITOR = {
  layouts: <?= json_encode($layouts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
  defaults: <?= json_encode($defaults, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
  heroines: <?= json_encode($editor_heroines, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
};
</script>
<script src="js/heroine-orbit-editor.js?v=20260779" defer></script>
</body>
</html>
