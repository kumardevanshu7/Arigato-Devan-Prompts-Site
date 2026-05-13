<?php
session_start();
require_once "db.php";

// Protect page (Admin Only)
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] =
        "You do not have permission to access the upload page.";
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Prompt - Admin</title>
    <link rel="stylesheet" href="style.css?v=1778100000">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        body { background: var(--bg-color); }
        .dashboard-wrap { max-width: 800px; margin: 0 auto; padding: 30px 40px 100px; }
        .dash-page-title { font-size: 2.2rem; font-weight: 900; margin-bottom: 30px; display: flex; align-items: center; gap: 12px; }
        .dash-card { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 24px; padding: 30px; box-shadow: var(--shadow-comic); }
        .form-row { display: flex; gap: 20px; }
        .form-row > * { flex: 1; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.9rem; font-weight: 800; margin-bottom: 8px; color: var(--text-color); }
        input[type="text"], input[type="url"], textarea, select { width: 100%; padding: 12px 16px; border: var(--border-width) solid var(--text-color); border-radius: 14px; font-family: var(--font-main); font-size: 0.95rem; font-weight: 600; background: #fff; color: var(--text-color); outline: none; transition: all 0.2s; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
        input:focus, textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 4px rgba(255, 107, 107, 0.2); }
        .file-upload-wrapper { display: flex; align-items: center; gap: 12px; }
        .file-upload-btn { background: var(--primary-color); color: var(--text-color); border: var(--border-width) solid var(--text-color); border-radius: 12px; padding: 10px 16px; font-weight: 800; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: var(--shadow-comic); transition: transform 0.1s; }
        .file-upload-btn:active { transform: translateY(2px); box-shadow: none; }
        .file-upload-name { font-size: 0.85rem; font-weight: 600; color: #666; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .unreleased-toggle { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; background: #ffe3e3; padding: 12px 20px; border-radius: 12px; border: 2px dashed #ff8787; color: #d03030; font-weight: 800; transition: all 0.2s; }
        .unreleased-toggle:hover { background: #ffcfcf; }
        .unreleased-toggle input[type="checkbox"] { width: 18px !important; height: 18px !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; border: none !important; transform: none !important; cursor: pointer !important; accent-color: #d03030; flex-shrink: 0; }
        @media (max-width: 768px) { .form-row { flex-direction: column; gap: 0; } }

        /* Prompt Type Selector */
        .type-selector { display: flex; gap: 12px; margin-bottom: 4px; }
        .type-card { flex: 1; border: var(--border-width) solid var(--text-color); border-radius: 16px; padding: 14px 10px; text-align: center; cursor: pointer; font-family: var(--font-main); font-weight: 800; font-size: 0.9rem; transition: all 0.2s; background: #fff; position: relative; }
        .type-card:hover { transform: translateY(-2px); box-shadow: 4px 4px 0 var(--text-color); }
        .type-card input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
        .type-card .type-icon { font-size: 1.6rem; display: block; margin-bottom: 6px; }
        .type-card .type-label { display: block; }
        .type-card.selected-secret { background: #ffe3e3; border-color: #d03030; color: #d03030; box-shadow: 4px 4px 0 #d03030; }
        .type-card.selected-unreleased { background: #fff4cc; border-color: #e6a800; color: #7a5800; box-shadow: 4px 4px 0 #e6a800; }
        .type-card.selected-viral { background: #e3f7ff; border-color: #007ab8; color: #004f7a; box-shadow: 4px 4px 0 #007ab8; }
        .type-card.selected-uploaded { background: #e6f2ff; border-color: #00509e; color: #00509e; box-shadow: 4px 4px 0 #00509e; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <?php include_once "gtag.php"; ?>
</head>
<body>
    <header>
        <div class="logo-area"  style="cursor:pointer">
            <div class="logo-text" style="font-size:1.5rem;">ARIGATO<br>DEVAN PROMPTS</div>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> BACK TO DASHBOARD</a>
        </nav>
    </header>

    <div class="dashboard-wrap">
        <h1 class="dash-page-title"><i class="fa-solid fa-upload" style="color:var(--primary-color);"></i> Upload Prompt</h1>

        <?php if (isset($_SESSION["success_msg"])): ?>
            <div style="background:#d9f5e5;color:#2a7a4b;padding:16px;border-radius:12px;font-weight:700;margin-bottom:20px;border:2px solid #2a7a4b;">
                <i class="fa-solid fa-check-circle"></i> <?= $_SESSION[
                    "success_msg"
                ] ?>
            </div>
            <?php unset($_SESSION["success_msg"]); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION["error_msg"])): ?>
            <div style="background:#ffe3e3;color:#d03030;padding:16px;border-radius:12px;font-weight:700;margin-bottom:20px;border:2px solid #d03030;">
                <i class="fa-solid fa-circle-xmark"></i> <?= $_SESSION[
                    "error_msg"
                ] ?>
            </div>
            <?php unset($_SESSION["error_msg"]); ?>
        <?php endif; ?>

        <div class="dash-card">
            <form method="POST" action="upload.php" enctype="multipart/form-data">

                <!-- Prompt Type Selector -->
                <div class="form-group">
                    <label>Prompt Type</label>
                    <div class="type-selector">
                        <label class="type-card selected-secret" id="card-secret">
                            <input type="radio" name="prompt_type" value="secret" checked onchange="onTypeChange('secret')">
                            <span class="type-icon"><i class="bx bx-lock-alt type-icon"></i></span>
                            <span class="type-label">Secret Code</span>
                        </label>
                        <label class="type-card" id="card-unreleased">
                            <input type="radio" name="prompt_type" value="unreleased" onchange="onTypeChange('unreleased')">
                            <span class="type-icon"><i class="bx bx-moon type-icon"></i></span>
                            <span class="type-label">Unreleased</span>
                        </label>
                        <label class="type-card" id="card-viral">
                            <input type="radio" name="prompt_type" value="insta_viral" onchange="onTypeChange('insta_viral')">
                            <span class="type-icon"><i class="bx bxs-hot type-icon"></i></span>
                            <span class="type-label">Insta Viral</span>
                        </label>
                        <label class="type-card" id="card-uploaded">
                            <input type="radio" name="prompt_type" value="already_uploaded" onchange="onTypeChange('already_uploaded')">
                            <span class="type-icon"><i class="bx bx-history type-icon"></i></span>
                            <span class="type-label">Already Uploaded</span>
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" placeholder="Romantic Dusk..." required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tags (Type and press Enter, comma, or click a suggestion)</label>
                    <div class="tag-input-container" style="display:flex; flex-wrap:wrap; gap:8px; padding:10px; border:var(--border-width) solid var(--text-color); border-radius:12px; background:#fff; min-height:50px; cursor:text;" onclick="document.getElementById('tag-input-field').focus()">
                        <!-- Tag pills will go here -->
                        <input type="text" id="tag-input-field" placeholder="secret, couple, neon..." style="border:none; outline:none; background:transparent; flex-grow:1; min-width:150px; font-family:var(--font-main); font-size:1rem; padding:4px;">
                    </div>
                    <input type="hidden" id="tag" name="tag">

                    <div id="tag-suggestions" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:8px;">
                        <!-- Suggestions will be injected via PHP/JS -->
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
                        // Ensure the core tags are always suggested
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

                <div class="form-group">
                    <label for="prompt_text">Prompt Preview / Prompt Text</label>
                    <textarea id="prompt_text" name="prompt_text" rows="5" placeholder="A cinematic photo of a couple at golden hour..." required></textarea>
                </div>

                <!-- Access Code — full width, only shows for Secret Code type -->
                <div class="form-group" id="unlock-code-group" style="display:block;">
                    <label for="unlock_code"><i class="bx bx-key"></i> Access Code (6 chars)</label>
                    <input type="text" id="unlock_code" name="unlock_code" maxlength="6" pattern="[A-Za-z0-9]{6}" title="Exactly 6 alphanumeric characters" placeholder="e.g. MAGIC1" style="text-transform:uppercase; letter-spacing: 4px; font-weight: 900; font-size: 1.1rem;" required>
                </div>

                <!-- Cover Image + Reel Link — side by side -->
                <div class="form-row" style="gap:20px; margin-bottom:0;">
                    <div class="form-group" style="flex:1; min-width:0;">
                        <label>Cover Image</label>
                        <div class="file-upload-wrapper" style="flex-wrap:wrap; gap:10px;">
                            <label for="image" class="file-upload-btn" style="white-space:nowrap;">
                                <i class="fa-solid fa-image"></i> Choose Image
                            </label>
                            <span class="file-upload-name" id="file-name-display" style="max-width:100%; white-space:normal; word-break:break-all;">No file chosen</span>
                            <input type="file" id="image" name="image" accept="image/*" required style="display:none;" onchange="document.getElementById('file-name-display').textContent = this.files[0] ? this.files[0].name : 'No file chosen'">
                        </div>
                    </div>
                    <div class="form-group" id="reel-link-group" style="flex:1; min-width:0;">
                        <label for="reel_link">Reel Link <span style="font-weight:600;color:var(--text-color);">(Required for Secret Code)</span></label>
                        <input type="url" id="reel_link" name="reel_link" placeholder="https://instagram.com/reel/...">
                    </div>
                </div>

                <!-- Upload Button &mdash; full width with space -->
                <div style="margin-top: 24px;">
                    <button type="submit" class="comic-btn" style="width:100%; background:var(--secondary-color); font-size:1.15rem; padding:18px; letter-spacing:1px;">
                        Upload to Verse! <i class="fa-solid fa-rocket"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const tagInputContainer = document.querySelector('.tag-input-container');
        const tagInputField = document.getElementById('tag-input-field');
        const hiddenTagInput = document.getElementById('tag');
        const codeGroup = document.getElementById('unlock-code-group');
        const codeInput = document.getElementById('unlock_code');
        const reelLinkGroup = document.getElementById('reel-link-group');
        const reelLinkInput = document.getElementById('reel_link');

        let tags = [];

        // --- Prompt Type Logic ---
        function onTypeChange(type) {
            // Reset all card styles
            document.getElementById('card-secret').className = 'type-card';
            document.getElementById('card-unreleased').className = 'type-card';
            document.getElementById('card-viral').className = 'type-card';
            document.getElementById('card-uploaded').className = 'type-card';

            if (type === 'secret') {
                document.getElementById('card-secret').className = 'type-card selected-secret';
                codeGroup.style.display = 'block';
                codeInput.required = true;
                reelLinkGroup.style.display = 'block';
                reelLinkInput.required = true;
            } else if (type === 'unreleased') {
                document.getElementById('card-unreleased').className = 'type-card selected-unreleased';
                codeGroup.style.display = 'none';
                codeInput.required = false;
                codeInput.value = '';
                reelLinkGroup.style.display = 'none';
                reelLinkInput.required = false;
                reelLinkInput.value = '';
            } else if (type === 'insta_viral') {
                document.getElementById('card-viral').className = 'type-card selected-viral';
                codeGroup.style.display = 'none';
                codeInput.required = false;
                codeInput.value = '';
                reelLinkGroup.style.display = 'none';
                reelLinkInput.required = false;
                reelLinkInput.value = '';
            } else if (type === 'already_uploaded') {
                document.getElementById('card-uploaded').className = 'type-card selected-uploaded';
                codeGroup.style.display = 'none';
                codeInput.required = false;
                codeInput.value = '';
                reelLinkGroup.style.display = 'none';
                reelLinkInput.required = false;
                reelLinkInput.value = '';
            }
        }
        // Set initial state
        onTypeChange('secret');

        // --- Tag Logic ---
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

        // --- Form Submit Validation ---
        document.querySelector('form').addEventListener('submit', function(e) {
            // Auto-add any pending text in tag input field
            if (tagInputField.value.trim()) {
                addTag(tagInputField.value.trim());
            }

            // Check tags
            if (tags.length === 0) {
                e.preventDefault();
                alert('⚠️ Please add at least one tag before uploading!');
                tagInputField.focus();
                return;
            }

            // Check unlock code for secret type
            const selectedType = document.querySelector('input[name="prompt_type"]:checked')?.value;
            if (selectedType === 'secret') {
                const code = codeInput.value.trim();
                if (!code || code.length !== 6) {
                    e.preventDefault();
                    alert('&mdash; Access Code must be exactly 6 characters for Secret Code type!');
                    codeInput.focus();
                    return;
                }
            }

            // Check reel link for secret type
            if (selectedType === 'secret') {
                const reel = reelLinkInput.value.trim();
                if (!reel) {
                    e.preventDefault();
                    alert('⚠️ Reel Link is required for Secret Code type!');
                    reelLinkInput.focus();
                    return;
                }
            }

            // Make sure hidden tag input is updated
            hiddenTagInput.value = tags.join(',');
        });
    </script>
</body>
</html>
