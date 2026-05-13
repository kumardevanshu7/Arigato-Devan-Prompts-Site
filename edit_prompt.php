<?php
session_start();
require_once "db.php";
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
    $prompt_text = trim($_POST["prompt_text"] ?? "");
    $reel_link = trim($_POST["reel_link"] ?? "");

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

    $pdo->prepare(
        "UPDATE prompts SET title=?, tag=?, prompt_text=?, unlock_code=?, reel_link=?, image_path=?, prompt_type=? WHERE id=?",
    )->execute([
        $title,
        $tag,
        $prompt_text,
        $unlock_code,
        $reel_link,
        $image_path,
        $prompt_type,
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
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Prompt &mdash; Admin</title><link rel="stylesheet" href="style.css?v=1778100000">
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
.type-selector{display:flex;gap:10px;margin-bottom:4px;flex-wrap:wrap}
.e-type-card{flex:1;min-width:100px;border:var(--border-width) solid var(--text-color);border-radius:16px;padding:12px 8px;text-align:center;cursor:pointer;font-family:var(--font-main);font-weight:800;font-size:.85rem;transition:all .2s;background:#fff;position:relative}
.e-type-card:hover{transform:translateY(-2px);box-shadow:4px 4px 0 var(--text-color)}
.e-type-card input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.e-type-card.sel-secret{background:#ffe3e3;border-color:#d03030;color:#d03030;box-shadow:4px 4px 0 #d03030}
.e-type-card.sel-unreleased{background:#fff4cc;border-color:#e6a800;color:#7a5800;box-shadow:4px 4px 0 #e6a800}
.e-type-card.sel-viral{background:#e3f7ff;border-color:#007ab8;color:#004f7a;box-shadow:4px 4px 0 #007ab8}
.e-type-card.sel-uploaded{background:#e6f2ff;border-color:#00509e;color:#00509e;box-shadow:4px 4px 0 #00509e}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head><body>
<header>
  <div class="logo-area"  style="cursor:pointer">
    <div class="logo-flipper">
      <div class="logo-front"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo" id="profile-logo"></div>
      <div class="logo-back"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt=""></div>
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
        <img src="<?= htmlspecialchars(
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

      <div class="form-group"><label for="e-prompt">Prompt Text *</label><textarea id="e-prompt" name="prompt_text" rows="6" required><?= htmlspecialchars(
          $p["prompt_text"],
      ) ?></textarea></div>

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
      <div class="form-group">
        <label for="e-reel">Reel Link (optional)</label>
        <input type="url" id="e-reel" name="reel_link" value="<?= htmlspecialchars(
            $p["reel_link"] ?? "",
        ) ?>" placeholder="https://instagram.com/reel/...">
      </div>

      <div class="form-group">
        <label>Cover Image (leave blank to keep current)</label>
        <div class="img-preview">
          <img src="<?= htmlspecialchars(
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
            tag = tag.trim().toLowerCase().replace(/[^a-z0-9]/g, '');
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
        }

        function checkSecretTag() {
            const selectedType = document.querySelector('input[name="prompt_type"]:checked')?.value;
            if (selectedType === 'secret') {
                codeRow.style.display = 'flex';
                codeInput.required = true;
                codeInput.disabled = false;
            } else {
                codeRow.style.display = 'none';
                codeInput.required = false;
                codeInput.disabled = true;
            }
        }

        // Initial render
        renderTags();
</script>
</body></html>
