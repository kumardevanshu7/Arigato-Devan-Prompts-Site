<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

// Handle custom POTD form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_custom_potd"])) {
    $c_title = trim($_POST["custom_title"] ?? "");
    $c_text  = trim($_POST["custom_text"] ?? "");
    $c_img   = trim($_POST["custom_image"] ?? "");
    if ($c_title && $c_text) {
        $stmt = $pdo->prepare("INSERT INTO potd_custom (title, prompt_text, image_url) VALUES (?, ?, ?)");
        $stmt->execute([$c_title, $c_text, $c_img]);
        $_SESSION["potd_msg"] = "Custom POTD added!";
    } else {
        $_SESSION["potd_err"] = "Title and Prompt Text are required.";
    }
    header("Location: potd_manager.php");
    exit();
}

// Fetch existing prompts (sorted by likes)
$prompts = $pdo->query("SELECT id, title, image_path, prompt_type, likes_count, is_featured FROM prompts ORDER BY likes_count DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch custom POTD entries
$customs = $pdo->query("SELECT * FROM potd_custom ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Flash messages
$msg = $_SESSION["potd_msg"] ?? "";
$err = $_SESSION["potd_err"] ?? "";
unset($_SESSION["potd_msg"], $_SESSION["potd_err"]);

// Find which one is currently active
$active_existing_id = null;
$active_custom_id = null;
foreach ($prompts as $p) { if ($p["is_featured"]) $active_existing_id = (int)$p["id"]; }
foreach ($customs as $c) { if ($c["is_active"]) $active_custom_id = (int)$c["id"]; }

$type_map = [
    "secret" => ["emoji"=>"🔒","label"=>"Secret Code","bg"=>"#ffe3e3","color"=>"#d03030"],
    "unreleased" => ["emoji"=>"🌙","label"=>"Unreleased","bg"=>"#fff4cc","color"=>"#7a5800"],
    "insta_viral" => ["emoji"=>"🔥","label"=>"Insta Viral","bg"=>"#e3f7ff","color"=>"#004f7a"],
    "already_uploaded" => ["emoji"=>"📤","label"=>"Already Uploaded","bg"=>"#e6f2ff","color"=>"#00509e"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prompt of the Day Manager — Admin</title>
    <link rel="stylesheet" href="style.css?v=1778100000">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-color); }

        .pm-wrap { max-width: 1060px; margin: 0 auto; padding: 32px 36px 100px; }

        .pm-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; flex-wrap:wrap; gap:12px; }
        .pm-title { font-size:2.2rem; font-weight:900; display:flex; align-items:center; gap:12px; }
        .pm-sub { color:#7D7887; font-weight:600; font-size:.92rem; margin-bottom:24px; }

        .pm-search { width:100%; padding:13px 18px; border:var(--border-width) solid var(--text-color); border-radius:14px; font-family:var(--font-main); font-weight:600; font-size:1rem; background:var(--card-bg); color:var(--text-color); outline:none; box-shadow:var(--shadow-comic); transition:all .2s; margin-bottom:20px; box-sizing:border-box; }
        .pm-search:focus { box-shadow:var(--shadow-comic-hover); transform:translateY(-1px); }

        .pm-card { background:var(--card-bg); border:var(--border-width) solid var(--text-color); border-radius:24px; box-shadow:var(--shadow-comic); overflow:hidden; margin-bottom:32px; }

        .pm-table { width:100%; border-collapse:collapse; }
        .pm-table thead tr { border-bottom:2px solid var(--text-color); background:var(--bg-color); }
        .pm-table th { padding:14px 16px; font-size:.75rem; font-weight:900; text-transform:uppercase; letter-spacing:.6px; color:var(--text-color); text-align:left; }
        .pm-table tbody tr { border-bottom:1px solid var(--border-color); transition:all .2s; }
        .pm-table tbody tr:last-child { border-bottom:none; }
        .pm-table tbody tr:hover { background:var(--bg-color); }
        .pm-table td { padding:12px 16px; vertical-align:middle; }

        .pm-table tbody tr.row-active { background:rgba(255,215,0,0.08); border-left:4px solid #f0c040; }

        .pm-cover { width:52px; height:52px; object-fit:cover; border-radius:10px; border:2px solid var(--text-color); display:block; }
        .pm-title-cell { font-weight:800; font-size:.96rem; max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .pm-likes { font-size:.82rem; color:#999; font-weight:600; margin-top:3px; }

        .type-pill { border-radius:8px; padding:4px 10px; font-size:.72rem; font-weight:900; white-space:nowrap; border:1.5px solid currentColor; display:inline-block; }
        .custom-pill { background:#f3e8ff; color:#7c3aed; border:1.5px solid #7c3aed; border-radius:8px; padding:4px 10px; font-size:.72rem; font-weight:900; }

        /* Toggle Switch */
        .toggle-wrap { display:flex; align-items:center; gap:8px; }
        .toggle-label { font-size:.72rem; font-weight:800; text-transform:uppercase; color:#999; }
        .toggle-label.on { color:#22c55e; }

        .toggle-switch { position:relative; width:50px; height:28px; cursor:pointer; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .toggle-slider { position:absolute; inset:0; background:#ccc; border-radius:28px; border:2px solid var(--text-color); transition:all .3s cubic-bezier(.4,0,.2,1); }
        .toggle-slider::before { content:""; position:absolute; width:20px; height:20px; left:3px; bottom:2px; background:#fff; border-radius:50%; border:1.5px solid var(--text-color); transition:all .3s cubic-bezier(.4,0,.2,1); box-shadow:1px 1px 0 var(--text-color); }
        .toggle-switch input:checked + .toggle-slider { background:#22c55e; }
        .toggle-switch input:checked + .toggle-slider::before { transform:translateX(20px); }

        /* Flash Messages */
        .pm-flash-ok { background:#d9f5e5; color:#1e5c36; padding:14px 18px; border:var(--border-width) solid var(--text-color); border-radius:14px; font-weight:800; margin-bottom:20px; box-shadow:3px 3px 0 var(--text-color); }
        .pm-flash-err { background:#ffe6e6; color:#a70000; padding:14px 18px; border:var(--border-width) solid var(--text-color); border-radius:14px; font-weight:800; margin-bottom:20px; box-shadow:3px 3px 0 var(--text-color); }

        /* Custom POTD Form */
        .pm-form-card { background:var(--card-bg); border:var(--border-width) solid var(--text-color); border-radius:24px; padding:30px; box-shadow:var(--shadow-comic); }
        .pm-form-card h2 { font-size:1.5rem; font-weight:900; margin-bottom:20px; padding-bottom:14px; border-bottom:2px dashed var(--border-color); display:flex; align-items:center; gap:10px; }
        .pm-fg { margin-bottom:18px; }
        .pm-fg label { display:block; font-weight:800; margin-bottom:7px; font-size:.85rem; text-transform:uppercase; letter-spacing:.5px; }
        .pm-fg input, .pm-fg textarea { width:100%; padding:12px 16px; border:var(--border-width) solid var(--text-color); border-radius:12px; font-family:var(--font-main); font-size:.95rem; font-weight:600; background:var(--bg-color); color:var(--text-color); box-shadow:var(--shadow-comic); outline:none; transition:all .2s; box-sizing:border-box; }
        .pm-fg input:focus, .pm-fg textarea:focus { border-color:var(--primary-dark); box-shadow:var(--shadow-comic-hover); transform:translateY(-1px); }
        .pm-fg textarea { resize:vertical; min-height:90px; }

        .pm-submit { background:var(--primary-color); color:var(--text-color); border:var(--border-width) solid var(--text-color); border-radius:14px; padding:14px 28px; font-family:var(--font-main); font-weight:900; font-size:1rem; cursor:pointer; box-shadow:var(--shadow-comic); transition:all .2s; display:inline-flex; align-items:center; gap:8px; }
        .pm-submit:hover { transform:translateY(-2px); box-shadow:var(--shadow-comic-hover); }

        /* Delete button for custom entries */
        .pm-del-btn { background:#ffe3e3; color:#d03030; border:2px solid var(--text-color); border-radius:8px; padding:6px 10px; cursor:pointer; font-weight:800; font-size:.75rem; transition:all .15s; }
        .pm-del-btn:hover { background:#ffc9c9; transform:translateY(-1px); }

        /* Section divider */
        .pm-section-divider { display:flex; align-items:center; gap:14px; margin:36px 0 24px; }
        .pm-section-divider .line { flex:1; height:2px; background:var(--border-color); }
        .pm-section-divider .label { font-weight:900; font-size:.85rem; color:#7D7887; text-transform:uppercase; letter-spacing:1px; white-space:nowrap; }

        .pm-empty { text-align:center; color:#7D7887; font-weight:600; padding:40px 0; display:none; }

        @media (max-width: 600px) {
            .pm-wrap { padding:22px 16px 80px; }
            .pm-title { font-size:1.6rem; }
            .pm-title-cell { max-width:120px; }
        }
    </style>
    <?php include_once "gtag.php"; ?>
</head>
<body>

<header>
    <div class="logo-area" style="cursor:pointer">
        <div class="logo-flipper">
            <div class="logo-front">
                <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo">
            </div>
            <div class="logo-back">
                <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt="">
            </div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> BACK TO DASHBOARD</a>
    </nav>
    <div class="header-right">
        <div class="header-divider"></div>
        <div style="display:flex;align-items:center;gap:8px;">
            <?= renderAvatar($_SESSION["profile_image"] ?? "", "admin-avatar", "Admin") ?>
            <a href="dashboard.php" style="color:var(--text-color);font-weight:800;">ADMIN</a>
        </div>
        <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
    </div>
</header>

<div class="pm-wrap">

    <?php if ($msg): ?>
        <div class="pm-flash-ok"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="pm-flash-err"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <div class="pm-header">
        <div class="pm-title">
            <i class="fa-solid fa-star" style="color:#f0c040;"></i>
            Prompt of the Day Manager
        </div>
        <div class="badge" style="margin:0;transform:rotate(0);background:#fff3cd;padding:8px 20px;font-size:1rem;border:2px solid var(--text-color);">
            <?= count($prompts) + count($customs) ?> Total
        </div>
    </div>

    <p class="pm-sub">
        <i class="fa-solid fa-circle-info"></i>
        Turn ON the toggle to make any prompt the active <strong>Prompt of the Day</strong>. Only one can be active at a time. You can also add a custom prompt below.
    </p>

    <input type="text" class="pm-search" id="pm-search" placeholder="&#128269;  Search by title..." oninput="filterPotdTable(this.value)">

    <!-- ====== EXISTING PROMPTS TABLE ====== -->
    <div class="pm-card">
        <table class="pm-table" id="pm-table-existing">
            <thead>
                <tr>
                    <th style="width:68px;">Cover</th>
                    <th>Title</th>
                    <th style="width:110px;">Type</th>
                    <th style="width:60px;text-align:center;">❤️</th>
                    <th style="width:100px;text-align:center;">POTD</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($prompts as $p):
                $pt = $p["prompt_type"] ?? "secret";
                $tinfo = $type_map[$pt] ?? $type_map["secret"];
                $title = htmlspecialchars($p["title"]);
                $img = htmlspecialchars($p["image_path"]);
                $id = (int)$p["id"];
                $likes = (int)$p["likes_count"];
                $is_on = $p["is_featured"] ? true : false;
            ?>
            <tr data-search="<?= strtolower($title) ?>" class="<?= $is_on ? 'row-active' : '' ?>" id="row-existing-<?= $id ?>">
                <td><img src="<?= $img ?>" class="pm-cover" alt="Cover"></td>
                <td>
                    <div class="pm-title-cell"><?= $title ?></div>
                    <div class="pm-likes"><i class="fa-solid fa-heart" style="color:#ff6b6b;font-size:.75rem;"></i> <?= $likes ?> likes</div>
                </td>
                <td>
                    <span class="type-pill" style="background:<?= $tinfo["bg"] ?>;color:<?= $tinfo["color"] ?>;">
                        <?= $tinfo["emoji"] ?> <?= $tinfo["label"] ?>
                    </span>
                </td>
                <td style="text-align:center;font-weight:800;color:#ff6b6b;"><?= $likes ?></td>
                <td style="text-align:center;">
                    <div class="toggle-wrap" style="justify-content:center;">
                        <label class="toggle-switch">
                            <input type="checkbox" <?= $is_on ? 'checked' : '' ?> onchange="togglePotd('existing', <?= $id ?>, this)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="pm-empty" id="pm-empty-existing">No prompts match your search.</p>
    </div>

    <!-- ====== CUSTOM POTD TABLE ====== -->
    <?php if (count($customs) > 0): ?>
    <div class="pm-section-divider">
        <div class="line"></div>
        <div class="label"><i class="fa-solid fa-wand-magic-sparkles"></i> Custom POTD Entries</div>
        <div class="line"></div>
    </div>

    <div class="pm-card">
        <table class="pm-table" id="pm-table-custom">
            <thead>
                <tr>
                    <th style="width:68px;">Image</th>
                    <th>Title</th>
                    <th style="width:100px;">Source</th>
                    <th style="width:80px;text-align:center;">Actions</th>
                    <th style="width:100px;text-align:center;">POTD</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($customs as $c):
                $c_id = (int)$c["id"];
                $c_title = htmlspecialchars($c["title"]);
                $c_img = htmlspecialchars($c["image_url"]);
                $c_on = $c["is_active"] ? true : false;
            ?>
            <tr data-search="<?= strtolower($c_title) ?>" class="<?= $c_on ? 'row-active' : '' ?>" id="row-custom-<?= $c_id ?>">
                <td>
                    <?php if ($c_img): ?>
                        <img src="<?= $c_img ?>" class="pm-cover" alt="Custom" onerror="this.style.display='none'">
                    <?php else: ?>
                        <div class="pm-cover" style="display:flex;align-items:center;justify-content:center;background:#f3e8ff;font-size:1.2rem;">✨</div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="pm-title-cell"><?= $c_title ?></div>
                    <div class="pm-likes" style="color:#7c3aed;"><i class="fa-solid fa-pen-nib" style="font-size:.7rem;"></i> Custom entry</div>
                </td>
                <td><span class="custom-pill"><i class="fa-solid fa-wand-magic-sparkles"></i> Custom</span></td>
                <td style="text-align:center;">
                    <button class="pm-del-btn" onclick="deleteCustomPotd(<?= $c_id ?>, this)" title="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
                <td style="text-align:center;">
                    <div class="toggle-wrap" style="justify-content:center;">
                        <label class="toggle-switch">
                            <input type="checkbox" <?= $c_on ? 'checked' : '' ?> onchange="togglePotd('custom', <?= $c_id ?>, this)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ====== ADD CUSTOM POTD FORM ====== -->
    <div class="pm-section-divider">
        <div class="line"></div>
        <div class="label"><i class="fa-solid fa-plus"></i> Add Custom POTD</div>
        <div class="line"></div>
    </div>

    <div class="pm-form-card">
        <h2><i class="fa-solid fa-wand-magic-sparkles" style="color:#7c3aed;"></i> Add Custom Prompt of the Day</h2>
        <form method="POST" action="potd_manager.php">
            <input type="hidden" name="add_custom_potd" value="1">
            <div class="pm-fg">
                <label>Title *</label>
                <input type="text" name="custom_title" placeholder="e.g. Romantic Beach Sunset Prompt" required>
            </div>
            <div class="pm-fg">
                <label>Prompt Text *</label>
                <textarea name="custom_text" placeholder="Enter the full prompt text here..." required></textarea>
            </div>
            <div class="pm-fg">
                <label>Image URL (optional)</label>
                <input type="text" name="custom_image" placeholder="https://example.com/image.jpg">
            </div>
            <button type="submit" class="pm-submit">
                <i class="fa-solid fa-plus"></i> Add Custom POTD
            </button>
        </form>
    </div>

</div>

<script>
// Toggle POTD
function togglePotd(type, id, checkbox) {
    var active = checkbox.checked ? 1 : 0;

    // Immediately uncheck all other toggles visually
    document.querySelectorAll('.pm-table input[type="checkbox"]').forEach(function(cb) {
        if (cb !== checkbox) {
            cb.checked = false;
            cb.closest('tr').classList.remove('row-active');
        }
    });

    // Add/remove active class on this row
    var row = checkbox.closest('tr');
    if (active) {
        row.classList.add('row-active');
    } else {
        row.classList.remove('row-active');
    }

    fetch('potd_toggle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'type=' + type + '&id=' + id + '&active=' + active
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (!d.success) {
            alert('Error toggling POTD');
            checkbox.checked = !checkbox.checked;
        }
    })
    .catch(function() {
        alert('Network error');
        checkbox.checked = !checkbox.checked;
    });
}

// Delete custom POTD
function deleteCustomPotd(id, btn) {
    if (!confirm('Delete this custom POTD entry?')) return;

    fetch('potd_delete_custom.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            var row = btn.closest('tr');
            row.style.transition = 'opacity .3s';
            row.style.opacity = '0';
            setTimeout(function() { row.remove(); }, 300);
        } else {
            alert('Error deleting');
        }
    });
}

// Search filter
function filterPotdTable(query) {
    query = query.toLowerCase().trim();
    var tables = ['pm-table-existing', 'pm-table-custom'];
    tables.forEach(function(tid) {
        var tbl = document.getElementById(tid);
        if (!tbl) return;
        var rows = tbl.querySelectorAll('tbody tr');
        var found = 0;
        rows.forEach(function(row) {
            var match = (row.dataset.search || '').includes(query);
            row.style.display = match ? '' : 'none';
            if (match) found++;
        });
    });
    // Show/hide empty for existing table
    var allExisting = document.querySelectorAll('#pm-table-existing tbody tr');
    var anyVisible = false;
    allExisting.forEach(function(r) { if (r.style.display !== 'none') anyVisible = true; });
    var emptyEl = document.getElementById('pm-empty-existing');
    if (emptyEl) emptyEl.style.display = anyVisible ? 'none' : 'block';
}
</script>
</body>
</html>
