<?php
session_start();
require_once "db.php";
require_once "slug_helper.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}
$id = (int) ($_GET["id"] ?? 0);
if (!$id) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $tag = trim($_POST["tag"] ?? "");
    $prompt_text  = trim($_POST["prompt_text"] ?? "");
    $description   = trim($_POST["description"] ?? "");
    $reel_link = trim($_POST["reel_link"] ?? "");
    $bwi_raw = trim($_POST["best_works_in"] ?? "");
    $best_works_in = in_array($bwi_raw, ["nano_banana", "chatgpt"]) ? $bwi_raw : null;
    $has_assets = isset($_POST["has_assets"]) && $_POST["has_assets"] === "1";
    $asset_title = $has_assets ? trim($_POST["asset_title"] ?? "") : null;
    $asset_images_json = $_POST["current_asset_images"] ?? null;
    if (!$has_assets) { $asset_images_json = null; }
    if ($has_assets && isset($_FILES["asset_images"]) && !empty($_FILES["asset_images"]["name"][0])) {
        $asset_dir = "uploads/assets/";
        if (!is_dir($asset_dir)) { mkdir($asset_dir, 0755, true); }
        $asset_paths = [];
        $allowed_ext = ["jpg","jpeg","png","gif","webp"];
        foreach ($_FILES["asset_images"]["tmp_name"] as $i => $tmp) {
            if ($i >= 2) break;
            if ($_FILES["asset_images"]["error"][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES["asset_images"]["name"][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) continue;
            $afname = "uploads/assets/" . uniqid("asset_") . "." . $ext;
            if (move_uploaded_file($tmp, $afname)) { $asset_paths[] = $afname; }
        }
        if (!empty($asset_paths)) { $asset_images_json = json_encode($asset_paths); }
    }

    $prompt_type = trim($_POST["prompt_type"] ?? "secret");
    $valid_types = ["secret", "unreleased", "insta_viral", "already_uploaded"];
    if (!in_array($prompt_type, $valid_types)) {
        $prompt_type = "secret";
    }
    $is_secret = $prompt_type === "secret";

    // Only validate code for secret type
    if ($is_secret) {
        $unlock_code = strtoupper(trim($_POST["unlock_code"] ?? ""));
        if (!$title || !$tag || !$prompt_text || strlen($unlock_code) !== 6) {
            $_SESSION["edit_error"] =
                "All fields required. Code must be 6 chars.";
            header("Location: edit_prompt.php?id=$id");
            exit();
        }
    } else {
        $unlock_code = "XXXXXX"; // dummy placeholder for non-code types
        if (!$title || !$tag || !$prompt_text) {
            $_SESSION["edit_error"] =
                "Title, tags and prompt text are required.";
            header("Location: edit_prompt.php?id=$id");
            exit();
        }
    }

    $image_path = $_POST["current_image"];
    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] === UPLOAD_ERR_OK
    ) {
        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $fn = "uploads/" . uniqid("prompt_") . "." . $ext;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $fn)) {
            $image_path = $fn;
        }
    }

    // Handle extra prompts (2 and 3)
    $extra_prompts_data = [];
    $allowed_ext_ep = ["jpg","jpeg","png","gif","webp"];
    for ($ep = 2; $ep <= 3; $ep++) {
        $ep_text = trim($_POST["extra_prompt_{$ep}_text"] ?? '');
        if (empty($ep_text)) continue;
        $ep_title = trim($_POST["extra_prompt_{$ep}_title"] ?? '');
        $ep_image_path = trim($_POST["extra_prompt_{$ep}_current_image"] ?? '');
        if (isset($_FILES["extra_prompt_{$ep}_image"]) && $_FILES["extra_prompt_{$ep}_image"]["error"] === UPLOAD_ERR_OK) {
            $ep_ext = strtolower(pathinfo($_FILES["extra_prompt_{$ep}_image"]["name"], PATHINFO_EXTENSION));
            if (in_array($ep_ext, $allowed_ext_ep)) {
                $ep_fname = "uploads/" . uniqid("ep_") . "." . $ep_ext;
                if (move_uploaded_file($_FILES["extra_prompt_{$ep}_image"]["tmp_name"], $ep_fname)) {
                    $ep_image_path = $ep_fname;
                }
            }
        }
        $extra_prompts_data[] = ['title' => $ep_title ?: null, 'prompt_text' => $ep_text, 'image_path' => $ep_image_path ?: null];
    }
    $extra_prompts_json = !empty($extra_prompts_data) ? json_encode($extra_prompts_data) : null;

    $updated_slug = uniqueSlug($pdo, $title, $id);
    $pdo->prepare(
        "UPDATE prompts SET title=?, slug=?, tag=?, prompt_text=?, unlock_code=?, reel_link=?, image_path=?, prompt_type=?, best_works_in=?, asset_title=?, asset_images=?, description=?, extra_prompts=? WHERE id=?",
    )->execute([
        $title,
        $updated_slug,
        $tag,
        $prompt_text,
        $unlock_code,
        $reel_link,
        $image_path,
        $prompt_type,
        $best_works_in,
        $asset_title,
        $asset_images_json,
        $description ?: null,
        $extra_prompts_json,
        $id,
    ]);

    $_SESSION["success_msg"] =
        '<i class="fa-solid fa-check"></i> Prompt updated!';
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM prompts WHERE id=?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    header("Location: dashboard.php");
    exit();
}
$edit_error = $_SESSION["edit_error"] ?? "";
unset($_SESSION["edit_error"]);
$current_tags = array_map("trim", explode(",", strtolower($p["tag"])));
$is_secret = $p["prompt_type"] === "secret";
$current_prompt_type = $p["prompt_type"] ?? "secret";
$current_bwi = $p["best_works_in"] ?? "";
$current_asset_title = $p["asset_title"] ?? "";
$current_asset_images = $p["asset_images"] ?? "";
$has_current_assets = !empty($current_asset_title) || !empty($current_asset_images);
$current_extra_arr  = json_decode($p['extra_prompts'] ?? '[]', true) ?: [];
$ep2_data = $current_extra_arr[0] ?? null;
$ep3_data = $current_extra_arr[1] ?? null;
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Prompt &mdash; Admin</title><link rel="stylesheet" href="style.css?v=2026052201">
<style>
body{background:var(--bg-color)}.edit-wrap{max-width:820px;margin:0 auto;padding:40px 30px 100px}
.edit-page-title{font-size:2rem;font-weight:900;margin-bottom:6px;display:flex;align-items:center;gap:10px}
.edit-page-sub{color:#7D7887;font-weight:600;margin-bottom:28px;font-size:.95rem}
.edit-card{background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:36px;box-shadow:var(--shadow-comic)}
.edit-card h2{font-size:1.4rem;font-weight:900;margin-bottom:24px;padding-bottom:14px;border-bottom:2px dashed var(--border-color)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-weight:800;margin-bottom:7px;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:11px 15px;border:var(--border-width) solid var(--text-color);border-radius:12px;font-family:var(--font-main);font-size:.95rem;font-weight:600;background:var(--bg-color);color:var(--text-color);box-shadow:var(--shadow-comic);outline:none;transition:all .2s;box-sizing:border-box}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:var(--primary-dark);box-shadow:var(--shadow-comic-hover);transform:translateY(-1px)}
.form-group textarea{resize:vertical;min-height:110px}
.img-preview{display:flex;align-items:center;gap:14px;padding:12px;background:var(--bg-color);border:2px dashed var(--border-color);border-radius:12px;margin-bottom:10px}
.bwi-selector{display:flex;gap:12px;flex-wrap:wrap;}
.bwi-btn{display:inline-flex;align-items:center;gap:8px;border:var(--border-width) solid var(--text-color);border-radius:14px;padding:12px 22px;cursor:pointer;font-family:var(--font-main);font-weight:900;font-size:1rem;transition:all .2s;user-select:none;}
.bwi-btn input[type=radio]{display:none;}
.bwi-banana-opt{background:#fffaed;color:#7a5800;}
.bwi-banana-opt.bwi-selected{background:#ffe066;box-shadow:3px 3px 0 var(--text-color);transform:translateY(-2px);}
.bwi-chatgpt-opt{background:#f0faf7;color:#10a37f;}
.bwi-chatgpt-opt.bwi-selected{background:#10a37f;color:#fff;box-shadow:3px 3px 0 var(--text-color);transform:translateY(-2px);}
.img-preview img{width:60px;height:60px;object-fit:cover;border-radius:10px;border:2px solid var(--text-color)}
.img-preview span{font-size:.85rem;font-weight:600;color:#7D7887}
.file-upload-wrapper{display:flex;align-items:center;gap:14px;background:var(--bg-color);padding:10px 15px;border:var(--border-width) solid var(--text-color);border-radius:12px;box-shadow:var(--shadow-comic)}
.file-upload-btn{background:var(--primary-color);color:var(--text-color);padding:7px 14px;border:2px solid var(--text-color);border-radius:8px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:7px;box-shadow:2px 2px 0 var(--text-color);white-space:nowrap;font-size:.88rem}
.file-upload-name{font-weight:600;color:#7D7887;font-size:.88rem}
.flash-error{background:#ffe6e6;color:#a70000;padding:14px;border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;margin-bottom:18px;box-shadow:3px 3px 0 var(--text-color)}
.btn-row{display:flex;gap:14px;margin-top:8px}
.btn-cancel{display:inline-flex;align-items:center;justify-content:center;padding:14px 22px;background:var(--bg-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;font-size:1rem;text-decoration:none;box-shadow:var(--shadow-comic);transition:all .2s;flex:1;text-align:center}
.btn-cancel:hover{transform:translateY(-2px);box-shadow:var(--shadow-comic-hover)}
.type-info-box{padding:12px 16px;border-radius:12px;font-size:.85rem;font-weight:700;margin-top:8px;border:2px dashed var(--border-color);display:flex;align-items:center;gap:8px}
.type-info-box.secret{background:#fff0f0;border-color:#ff8787;color:#c0392b}
.type-info-box.unreleased{background:#fff8e0;border-color:#f0c040;color:#7a5c00}
.type-info-box.viral{background:#e8f9ef;border-color:#5cb85c;color:#1a5c30}
@media(max-width:640px){.form-row{grid-template-columns:1fr}.edit-card{padding:22px 18px}}
.assets-toggle-label{display:inline-flex;align-items:center;gap:10px;cursor:pointer;background:#f0f7ff;padding:12px 20px;border-radius:12px;border:2px dashed #5b9bd5;color:#1a4f8a;font-weight:900;font-size:.95rem;transition:all .2s;user-select:none;}
.assets-toggle-label:hover{background:#dceeff;}
.assets-toggle-label input[type=checkbox]{width:18px!important;height:18px!important;margin:0!important;padding:0!important;box-shadow:none!important;border:none!important;accent-color:#1a4f8a;cursor:pointer;flex-shrink:0;}
.assets-fields-box{background:#f8fbff;border:2px solid #5b9bd5;border-radius:16px;padding:20px;margin-top:14px;}
.asset-previews{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;}
.asset-preview-thumb{width:80px;height:80px;border-radius:10px;overflow:hidden;border:2px solid var(--text-color);}
.asset-preview-thumb img{width:100%;height:100%;object-fit:cover;}
.type-selector{display:flex;gap:10px;margin-bottom:4px;flex-wrap:wrap}
.e-type-card{flex:1;min-width:100px;border:var(--border-width) solid var(--text-color);border-radius:16px;padding:12px 8px;text-align:center;cursor:pointer;font-family:var(--font-main);font-weight:800;font-size:.85rem;transition:all .2s;background:#fff;position:relative}
.e-type-card:hover{transform:translateY(-2px);box-shadow:4px 4px 0 var(--text-color)}
.e-type-card input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.e-type-card.sel-secret{background:#ffe3e3;border-color:#d03030;color:#d03030;box-shadow:4px 4px 0 #d03030}
.e-type-card.sel-unreleased{background:#fff4cc;border-color:#e6a800;color:#7a5800;box-shadow:4px 4px 0 #e6a800}
.e-type-card.sel-viral{background:#e3f7ff;border-color:#007ab8;color:#004f7a;box-shadow:4px 4px 0 #007ab8}
.e-type-card.sel-uploaded{background:#e6f2ff;border-color:#00509e;color:#00509e;box-shadow:4px 4px 0 #00509e}
.extra-prompt-box{background:#faf6ff;border:2px dashed #c084fc;border-radius:16px;padding:20px;margin-top:10px;margin-bottom:10px;}
.extra-prompt-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.extra-prompt-num{font-weight:900;color:#7c3aed;font-size:.95rem;}
.extra-remove-btn{background:#ffe3e3;border:2px solid #d03030;color:#d03030;border-radius:8px;padding:5px 12px;font-weight:800;font-size:.85rem;cursor:pointer;font-family:var(--font-main);}
.extra-add-btn{display:inline-flex;align-items:center;gap:6px;background:#f0f7ff;border:2px dashed #5b9bd5;color:#1a4f8a;border-radius:12px;padding:10px 20px;font-weight:900;font-size:.9rem;cursor:pointer;margin-top:10px;font-family:var(--font-main);transition:background .2s;}
.extra-add-btn:hover{background:#dceeff;}
</style>
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel='preload' href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' as='style' onload='this.onload=null;this.rel="stylesheet"'>

    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <?php include_once "gtag.php"; ?>
</head><body>
<header>
  <div class="logo-area"  style="cursor:pointer">
    <div class="logo-flipper">
      <div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo" id="profile-logo"></div>
      <div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div>
    </div>
    <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
  </div>
  <nav class="nav-links">
    <a href="index.php">HOME</a>
    <a href="dashboard.php">DASHBOARD</a>
  </nav>
  <div class="header-right">
    <div class="header-divider"></div>
    <div style="display:flex; align-items:center; gap:8px;">
      <a href="profile.php" title="Edit Profile">
        <img loading="lazy" src="<?= htmlspecialchars(
            $_SESSION["profile_image"] ?? "",
        ) ?>" class="admin-avatar" alt="Admin" referrerpolicy="no-referrer" style="transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1) rotate(-5deg)'" onmouseout="this.style.transform=''">
      </a>
      <a href="dashboard.php" style="color:var(--text-color); font-weight:800;">ADMIN</a>
    </div>
    <a href="login.php?logout=1" class="logout">
      <i class="fa-solid fa-right-from-bracket"></i> LOGOUT
    </a>
  </div>
</header>

<div class="edit-wrap">
  <div class="edit-page-title"><i class="fa-solid fa-pencil"></i> Edit Prompt</div>
  <div class="edit-page-sub">Editing: <strong><?= htmlspecialchars(
      $p["title"],
  ) ?></strong></div>
  <?php if (
      $edit_error
  ): ?><div class="flash-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars(
    $edit_error,
) ?></div><?php endif; ?>
  <div class="edit-card">
    <h2>Prompt Details</h2>
    <form method="POST" action="edit_prompt.php?id=<?= $id ?>" enctype="multipart/form-data">
      <input type="hidden" name="current_image" value="<?= htmlspecialchars(
          $p["image_path"],
      ) ?>">

      <!-- Prompt Type Selector -->
      <div class="form-group">
        <label>Prompt Type</label>
        <div class="type-selector">
          <label class="e-type-card <?= $current_prompt_type === "secret"
              ? "sel-secret"
              : "" ?>" id="e-card-secret">
            <input type="radio" name="prompt_type" value="secret" <?= $current_prompt_type ===
            "secret"
                ? "checked"
                : "" ?> onchange="onEditTypeChange('secret')">
            <span style="font-size:1.4rem;display:block;margin-bottom:4px;">🔒</span><span>Secret Code</span>
          </label>
          <label class="e-type-card <?= $current_prompt_type === "unreleased"
              ? "sel-unreleased"
              : "" ?>" id="e-card-unreleased">
            <input type="radio" name="prompt_type" value="unreleased" <?= $current_prompt_type ===
            "unreleased"
                ? "checked"
                : "" ?> onchange="onEditTypeChange('unreleased')">
            <span style="font-size:1.4rem;display:block;margin-bottom:4px;">🌙</span><span>Unreleased</span>
          </label>
          <label class="e-type-card <?= $current_prompt_type === "insta_viral"
              ? "sel-viral"
              : "" ?>" id="e-card-viral">
            <input type="radio" name="prompt_type" value="insta_viral" <?= $current_prompt_type ===
            "insta_viral"
                ? "checked"
                : "" ?> onchange="onEditTypeChange('insta_viral')">
            <span style="font-size:1.4rem;display:block;margin-bottom:4px;">🔥</span><span>Insta Viral</span>
          </label>
          <label class="e-type-card <?= $current_prompt_type ===
          "already_uploaded"
              ? "sel-uploaded"
              : "" ?>" id="e-card-uploaded">
            <input type="radio" name="prompt_type" value="already_uploaded" <?= $current_prompt_type ===
            "already_uploaded"
                ? "checked"
                : "" ?> onchange="onEditTypeChange('already_uploaded')">
            <span style="font-size:1.4rem;display:block;margin-bottom:4px;">📤</span><span>Already Uploaded</span>
          </label>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex:1;"><label for="e-title">Title *</label><input type="text" id="e-title" name="title" value="<?= htmlspecialchars(
            $p["title"],
        ) ?>" required></div>
      </div>

      <div class="form-group">
          <label>Tags (Type and press Enter, comma, or click a suggestion)</label>
          <div class="tag-input-container" style="display:flex; flex-wrap:wrap; gap:8px; padding:10px; border:var(--border-width) solid var(--text-color); border-radius:12px; background:#fff; min-height:50px; cursor:text;" onclick="document.getElementById('tag-input-field').focus()">
              <input type="text" id="tag-input-field" placeholder="secret, couple, neon..." style="border:none; outline:none; background:transparent; flex-grow:1; min-width:150px; font-family:var(--font-main); font-size:1rem; padding:4px;">
          </div>
          <input type="hidden" id="e-tag" name="tag" value="<?= htmlspecialchars(
              $p["tag"],
          ) ?>" required>

          <div id="tag-suggestions" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:8px;">
              <?php
              $stmt = $pdo->query("SELECT tag FROM prompts");
              $all_tags = [];
              while ($row = $stmt->fetch()) {
                  $tarr = explode(",", $row["tag"]);
                  foreach ($tarr as $t) {
                      $t = trim($t);
                      if (!empty($t)) {
                          $all_tags[] = strtolower($t);
                      }
                  }
              }
              $unique_tags = array_unique($all_tags);
              $core_tags = [];
              $unique_tags = array_unique(
                  array_merge($core_tags, $unique_tags),
              );
              sort($unique_tags);
              foreach ($unique_tags as $ut) {
                  echo '<span class="tag-suggestion" onclick="addTag(\'' .
                      htmlspecialchars($ut) .
                      '\')" style="background:var(--secondary-color); padding:4px 10px; border-radius:20px; font-size:0.85rem; font-weight:800; cursor:pointer; border:2px solid var(--text-color);">+' .
                      htmlspecialchars($ut) .
                      "</span>";
              }
              ?>
          </div>
      </div>

      <div class="form-group">
        <label>Best Works In <span style="font-weight:600;color:#888;text-transform:none;font-size:.85rem;">(optional)</span></label>
        <div class="bwi-selector">
          <label class="bwi-btn bwi-banana-opt <?= $current_bwi === 'nano_banana' ? 'bwi-selected' : '' ?>" onclick="setBwi('nano_banana',this)">
            <input type="radio" name="best_works_in" value="nano_banana" <?= $current_bwi === 'nano_banana' ? 'checked' : '' ?>>
            🍌 Nano Banana
          </label>
          <label class="bwi-btn bwi-chatgpt-opt <?= $current_bwi === 'chatgpt' ? 'bwi-selected' : '' ?>" onclick="setBwi('chatgpt',this)">
            <input type="radio" name="best_works_in" value="chatgpt" <?= $current_bwi === 'chatgpt' ? 'checked' : '' ?>>
            ✦ ChatGPT
          </label>
        </div>
      </div>

      <div class="form-group"><label for="e-prompt">Prompt Text *</label><textarea id="e-prompt" name="prompt_text" rows="6" required><?= htmlspecialchars(
          $p["prompt_text"],
      ) ?></textarea></div>

      <div class="form-group">
        <label for="e-desc">SEO Description <span style="font-weight:600;color:#888;text-transform:none;font-size:.85rem;">(optional — shown in Google search results)</span></label>
        <textarea id="e-desc" name="description" rows="3" maxlength="160" placeholder="Short description for Google search results (max 160 chars). Leave blank to auto-generate."><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
        <div style="font-size:.78rem;color:#888;font-weight:600;margin-top:4px;"><span id="desc-char-count"><?= strlen($p['description'] ?? '') ?></span>/160 characters</div>
      </div>

      <!-- Extra Prompts -->
      <div class="form-group">
        <label>Extra Prompts <span style="font-weight:600;color:#888;text-transform:none;font-size:.85rem;">(optional — up to 2 more variants for this card)</span></label>

        <div id="ep2-section" style="<?= $ep2_data ? '' : 'display:none;' ?>">
          <div class="extra-prompt-box">
            <div class="extra-prompt-header">
              <span class="extra-prompt-num">✦ Prompt 2</span>
              <button type="button" class="extra-remove-btn" onclick="removeEP(2)">✕ Remove</button>
            </div>
            <input type="hidden" name="extra_prompt_2_current_image" value="<?= htmlspecialchars($ep2_data['image_path'] ?? '') ?>">
            <div class="form-group" style="margin-bottom:12px;">
              <label>Prompt 2 Title <span style="font-weight:600;color:#888;text-transform:none;">(optional)</span></label>
              <input type="text" name="extra_prompt_2_title" value="<?= htmlspecialchars($ep2_data['title'] ?? '') ?>" placeholder="e.g. Rainy Day Version">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
              <label>Prompt 2 Text</label>
              <textarea name="extra_prompt_2_text" id="ep2_text" rows="4"><?= htmlspecialchars($ep2_data['prompt_text'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label>Prompt 2 Image <span style="font-weight:600;color:#888;text-transform:none;">(leave blank to keep current)</span></label>
              <?php if (!empty($ep2_data['image_path'])): ?>
              <div style="margin-bottom:8px;"><img loading="lazy" src="<?= htmlspecialchars($ep2_data['image_path']) ?>" style="width:55px;height:75px;object-fit:cover;border-radius:8px;border:2px solid var(--text-color);"></div>
              <?php endif; ?>
              <div class="file-upload-wrapper">
                <label for="ep2_image" class="file-upload-btn" style="background:var(--secondary-color);white-space:nowrap;"><i class="fa-solid fa-image"></i> <?= $ep2_data ? 'Change' : 'Choose' ?> Image</label>
                <span class="file-upload-name" id="ep2-fname">No file chosen</span>
                <input type="file" id="ep2_image" name="extra_prompt_2_image" accept="image/*" style="display:none;" onchange="document.getElementById('ep2-fname').textContent=this.files[0]?this.files[0].name:'No file chosen'">
              </div>
            </div>
          </div>
        </div>

        <div id="ep3-section" style="<?= $ep3_data ? '' : 'display:none;' ?>">
          <div class="extra-prompt-box">
            <div class="extra-prompt-header">
              <span class="extra-prompt-num">✦ Prompt 3</span>
              <button type="button" class="extra-remove-btn" onclick="removeEP(3)">✕ Remove</button>
            </div>
            <input type="hidden" name="extra_prompt_3_current_image" value="<?= htmlspecialchars($ep3_data['image_path'] ?? '') ?>">
            <div class="form-group" style="margin-bottom:12px;">
              <label>Prompt 3 Title <span style="font-weight:600;color:#888;text-transform:none;">(optional)</span></label>
              <input type="text" name="extra_prompt_3_title" value="<?= htmlspecialchars($ep3_data['title'] ?? '') ?>" placeholder="e.g. Sunset Version">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
              <label>Prompt 3 Text</label>
              <textarea name="extra_prompt_3_text" id="ep3_text" rows="4"><?= htmlspecialchars($ep3_data['prompt_text'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label>Prompt 3 Image <span style="font-weight:600;color:#888;text-transform:none;">(leave blank to keep current)</span></label>
              <?php if (!empty($ep3_data['image_path'])): ?>
              <div style="margin-bottom:8px;"><img loading="lazy" src="<?= htmlspecialchars($ep3_data['image_path']) ?>" style="width:55px;height:75px;object-fit:cover;border-radius:8px;border:2px solid var(--text-color);"></div>
              <?php endif; ?>
              <div class="file-upload-wrapper">
                <label for="ep3_image" class="file-upload-btn" style="background:var(--secondary-color);white-space:nowrap;"><i class="fa-solid fa-image"></i> <?= $ep3_data ? 'Change' : 'Choose' ?> Image</label>
                <span class="file-upload-name" id="ep3-fname">No file chosen</span>
                <input type="file" id="ep3_image" name="extra_prompt_3_image" accept="image/*" style="display:none;" onchange="document.getElementById('ep3-fname').textContent=this.files[0]?this.files[0].name:'No file chosen'">
              </div>
            </div>
          </div>
        </div>

        <div id="ep-add-btns">
          <button type="button" id="ep-add2-btn" class="extra-add-btn" style="<?= $ep2_data ? 'display:none;' : '' ?>" onclick="addEP(2)">➕ Add Prompt 2</button>
          <?php if ($ep2_data): ?>
          <button type="button" id="ep-add3-btn" class="extra-add-btn" style="<?= $ep3_data ? 'display:none;' : '' ?>" onclick="addEP(3)">➕ Add Prompt 3</button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Code field &mdash; only shown for secret -->
      <div class="form-row" id="code-field-row" style="<?= $is_secret
          ? ""
          : "display:none;" ?>">
        <div class="form-group">
          <label for="e-code">Access Code (6 chars) *</label>
          <input type="text" id="e-code" name="unlock_code" maxlength="6" value="<?= htmlspecialchars(
              $p["unlock_code"] ?? "",
          ) ?>" style="text-transform:uppercase;letter-spacing:3px;font-weight:900" <?= $is_secret
    ? "required"
    : "" ?>>
        </div>
      </div>

      <!-- Reel link standalone -->
      <div class="form-group" id="reel-link-group" style="<?= $is_secret
          ? ""
          : "display:none;" ?>">
        <label for="e-reel">Reel Link <span style="font-weight:600;color:var(--text-color);">(Required for Secret Code)</span></label>
        <input type="url" id="e-reel" name="reel_link" value="<?= htmlspecialchars(
            $p["reel_link"] ?? "",
        ) ?>" placeholder="https://instagram.com/reel/..." <?= $is_secret
    ? "required"
    : "" ?>>
      </div>

      <!-- Assets Toggle -->
      <div class="form-group">
        <label>Assets <span style="font-weight:600;color:#888;text-transform:none;font-size:.85rem;">(optional — reference images shown after unlock)</span></label>
        <label class="assets-toggle-label" id="assets-toggle-label">
            <input type="checkbox" name="has_assets" id="has_assets" value="1" onchange="toggleAssets(this)" <?= $has_current_assets ? 'checked' : '' ?>>
            <span>📎 Include Assets</span>
        </label>
        <div id="assets-fields" style="<?= $has_current_assets ? 'display:block;' : 'display:none;' ?>">
            <div class="assets-fields-box">
                <input type="hidden" name="current_asset_images" value="<?= htmlspecialchars($current_asset_images) ?>">
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="margin-top:0;">Assets Title</label>
                    <input type="text" name="asset_title" id="edit-asset-title" value="<?= htmlspecialchars($current_asset_title) ?>" placeholder="e.g. Reference Photos">
                </div>
                <?php if (!empty($current_asset_images)): ?>
                <div style="margin-bottom:12px;">
                    <label style="font-size:.8rem;color:#888;font-weight:700;text-transform:uppercase;">Current Assets</label>
                    <div class="asset-previews">
                        <?php foreach (json_decode($current_asset_images, true) ?? [] as $aimg): ?>
                        <div class="asset-preview-thumb"><img loading="lazy" src="<?= htmlspecialchars($aimg) ?>" alt="asset"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Replace Images <span style="font-weight:600;color:#888;text-transform:none;">(leave blank to keep current, max 2)</span></label>
                    <div class="file-upload-wrapper">
                        <label for="e-asset-images" class="file-upload-btn" style="background:var(--secondary-color);">
                            <i class="fa-solid fa-paperclip"></i> Choose Files
                        </label>
                        <span class="file-upload-name" id="e-asset-fname">No files chosen</span>
                        <input type="file" id="e-asset-images" name="asset_images[]" accept="image/*" multiple style="display:none;" onchange="handleEditAssetFiles(this)">
                    </div>
                    <div class="asset-previews" id="e-asset-previews"></div>
                </div>
            </div>
        </div>
      </div>

      <div class="form-group">
        <label>Cover Image (leave blank to keep current)</label>
        <div class="img-preview">
          <img loading="lazy" src="<?= htmlspecialchars(
              $p["image_path"],
          ) ?>" alt="Current cover">
          <span>Current cover image</span>
        </div>
        <div class="file-upload-wrapper">
          <label for="e-img" class="file-upload-btn">
            <i class="fa-solid fa-upload"></i> Replace Image
          </label>
          <span class="file-upload-name" id="e-fname">No file chosen</span>
          <input type="file" id="e-img" name="image" accept="image/*" style="display:none" onchange="document.getElementById('e-fname').textContent=this.files[0]?.name||'No file chosen'">
        </div>
      </div>

      <div class="btn-row">
        <a href="dashboard.php" class="btn-cancel"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
        <button type="submit" class="comic-btn" style="flex:2;background:var(--secondary-color)">Save Changes <i class="fa-solid fa-check"></i></button>
      </div>
    </form>
  </div>
</div>

<script>
        const tagInputContainer = document.querySelector('.tag-input-container');
        const tagInputField = document.getElementById('tag-input-field');
        const hiddenTagInput = document.getElementById('e-tag');
        const codeRow = document.getElementById('code-field-row');
        const codeInput = document.getElementById('e-code');
        const reelLinkGroup = document.getElementById('reel-link-group');
        const reelLinkInput = document.getElementById('e-reel');

        // Initialize from PHP
        let tags = <?= json_encode(array_values($current_tags)) ?>;

        function renderTags() {
            document.querySelectorAll('.tag-pill').forEach(el => el.remove());
            tags.forEach((tag, index) => {
                const pill = document.createElement('span');
                pill.className = 'tag-pill';
                pill.style.cssText = 'background:var(--primary-color); padding:4px 10px; border-radius:20px; font-size:0.85rem; font-weight:800; border:2px solid var(--text-color); display:flex; align-items:center; gap:6px;';
                pill.innerHTML = `${tag} <i class="fa-solid fa-xmark" style="cursor:pointer;" onclick="removeTag(${index})"></i>`;
                tagInputContainer.insertBefore(pill, tagInputField);
            });
            hiddenTagInput.value = tags.join(',');
            checkSecretTag();
        }

        function addTag(tag) {
            tag = tag.trim().replace(/[^a-zA-Z0-9 ]/g, '').replace(/\s+/g, ' ');
            tag = tag.replace(/\b\w/g, c => c.toUpperCase());
            if (tag && !tags.includes(tag)) {
                tags.push(tag);
                renderTags();
            }
            tagInputField.value = '';
        }

        window.removeTag = function(index) {
            tags.splice(index, 1);
            renderTags();
        }

        tagInputField.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addTag(this.value);
            } else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) {
                tags.pop();
                renderTags();
            }
        });

        function onEditTypeChange(type) {
            const classMap = {secret:'sel-secret',unreleased:'sel-unreleased',insta_viral:'sel-viral',already_uploaded:'sel-uploaded'};
            const idMap = {secret:'e-card-secret',unreleased:'e-card-unreleased',insta_viral:'e-card-viral',already_uploaded:'e-card-uploaded'};
            ['e-card-secret','e-card-unreleased','e-card-viral','e-card-uploaded'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.className = 'e-type-card';
            });
            const card = document.getElementById(idMap[type]);
            if (card) card.classList.add(classMap[type]);
            checkSecretTag();
            if (type !== 'secret') {
                reelLinkInput.value = '';
            }
        }

        function checkSecretTag() {
            const selectedType = document.querySelector('input[name="prompt_type"]:checked')?.value;
            if (selectedType === 'secret') {
                codeRow.style.display = 'flex';
                codeInput.required = true;
                codeInput.disabled = false;
                reelLinkGroup.style.display = 'block';
                reelLinkInput.required = true;
            } else {
                codeRow.style.display = 'none';
                codeInput.required = false;
                codeInput.disabled = true;
                reelLinkGroup.style.display = 'none';
                reelLinkInput.required = false;
            }
        }

        // Initial render
        renderTags();

        function setBwi(val, el) {
            document.querySelectorAll('.bwi-btn').forEach(b => b.classList.remove('bwi-selected'));
            el.classList.add('bwi-selected');
            el.querySelector('input[type=radio]').checked = true;
        }

        function toggleAssets(cb) {
            document.getElementById('assets-fields').style.display = cb.checked ? 'block' : 'none';
            document.getElementById('assets-toggle-label').style.background = cb.checked ? '#dceeff' : '';
        }
        // Init toggle visual
        (function(){ const cb = document.getElementById('has_assets'); if(cb && cb.checked) document.getElementById('assets-toggle-label').style.background='#dceeff'; })();

        // Description char counter
        document.getElementById('e-desc').addEventListener('input', function() {
            document.getElementById('desc-char-count').textContent = this.value.length;
        });

        function addEP(num) {
            document.getElementById('ep'+num+'-section').style.display = 'block';
            document.getElementById('ep-add'+num+'-btn').style.display = 'none';
            if (num === 2) {
                const addBtns = document.getElementById('ep-add-btns');
                let b3 = document.getElementById('ep-add3-btn');
                if (!b3) {
                    b3 = document.createElement('button');
                    b3.type='button'; b3.id='ep-add3-btn'; b3.className='extra-add-btn';
                    b3.innerHTML='➕ Add Prompt 3'; b3.onclick=function(){ addEP(3); };
                    addBtns.appendChild(b3);
                } else { b3.style.display=''; }
            }
        }
        function removeEP(num) {
            document.getElementById('ep'+num+'-section').style.display='none';
            const t=document.getElementById('ep'+num+'_text'); if(t) t.value='';
            const im=document.getElementById('ep'+num+'_image'); if(im) im.value='';
            const fn=document.getElementById('ep'+num+'-fname'); if(fn) fn.textContent='No file chosen';
            const ab=document.getElementById('ep-add'+num+'-btn'); if(ab) ab.style.display='';
            if(num===2){ removeEP(3); const b3=document.getElementById('ep-add3-btn'); if(b3) b3.style.display='none'; }
        }

        function handleEditAssetFiles(input) {
            const files = Array.from(input.files).slice(0, 2);
            document.getElementById('e-asset-fname').textContent = files.map(f=>f.name).join(', ') || 'No files chosen';
            const prev = document.getElementById('e-asset-previews');
            prev.innerHTML = '';
            files.forEach(f => {
                const r = new FileReader();
                r.onload = e => {
                    const d = document.createElement('div');
                    d.className = 'asset-preview-thumb';
                    d.innerHTML = `<img loading="lazy" src="${e.target.result}">`;
                    prev.appendChild(d);
                };
                r.readAsDataURL(f);
            });
        }
</script>
</body></html>
