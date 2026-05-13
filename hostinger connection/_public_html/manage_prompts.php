<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] =
        "You do not have permission to access the manage prompts page.";
    header("Location: index.php");
    exit();
}

$prompts = $pdo
    ->query("SELECT *, is_featured FROM prompts ORDER BY created_at DESC")
    ->fetchAll(PDO::FETCH_ASSOC);
$total_prompts = count($prompts);

// Pre-build all tag list for filter dropdown
$all_mgr_tags = [];
foreach ($prompts as $p) {
    foreach (explode(",", $p["tag"]) as $t) {
        $t = trim(strtolower($t));
        if (!empty($t)) {
            $all_mgr_tags[] = $t;
        }
    }
}
$all_mgr_tags = array_unique($all_mgr_tags);
sort($all_mgr_tags);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Prompts - Admin</title>
    <link rel="stylesheet" href="style.css?v=1778100000">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { background: var(--bg-color); }
        .dashboard-wrap { max-width: 1000px; margin: 0 auto; padding: 30px 40px 100px; }
        .dash-page-title { font-size: 2.2rem; font-weight: 900; margin-bottom: 30px; display: flex; align-items: center; gap: 12px; }
        .dash-card { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 24px; padding: 30px; box-shadow: var(--shadow-comic); }
        .prompt-item { display: flex; align-items: center; gap: 16px; padding: 16px; border: 2px solid var(--border-color); border-radius: 16px; margin-bottom: 12px; transition: all 0.2s; background: #fff; }
        .prompt-item:hover { transform: translateX(4px); border-color: var(--text-color); box-shadow: 4px 4px 0px rgba(0,0,0,0.1); }
        .prompt-item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 12px; border: 2px solid var(--text-color); flex-shrink: 0; }
        .prompt-item-details { flex: 1; min-width: 0; }
        .prompt-item-title { font-size: 1.1rem; font-weight: 800; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .prompt-item-meta { font-size: 0.85rem; color: #777; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .code-badge { background: var(--text-color); color: #fff; padding: 2px 6px; border-radius: 6px; font-family: monospace; font-size: 0.8rem; font-weight: 800; }
        .edit-btn, .delete-btn { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid var(--text-color); font-size: 1rem; color: var(--text-color); transition: all 0.2s; text-decoration: none; }
        .edit-btn { background: #d4eaff; }
        .edit-btn:hover { background: #a5d8ff; transform: translateY(-2px); }
        .delete-btn { background: #ffe3e3; color: #d03030; }
        .delete-btn:hover { background: #ffc9c9; transform: translateY(-2px); }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <?php include_once "gtag.php"; ?>
</head>
<body>
<header>
    <div class="logo-area" style="cursor:pointer">
        <div class="logo-text" style="font-size:1.5rem;">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> BACK TO DASHBOARD</a>
    </nav>
</header>

<div class="dashboard-wrap">
    <h1 class="dash-page-title"><i class="fa-solid fa-list-check" style="color:var(--secondary-color);"></i> Manage Prompts</h1>
    <div class="dash-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;border-bottom:2px dashed var(--border-color);padding-bottom:16px;">
            <h2 style="margin:0;padding:0;border:none;">All Prompts</h2>
            <div class="badge" style="margin:0;transform:rotate(0);background:var(--primary-color);padding:6px 16px;"><?= $total_prompts ?> Total</div>
        </div>

        <div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
            <input type="text" id="prompt-search" placeholder="Search by title..." style="flex:1;min-width:200px;padding:12px 16px;border:2px solid var(--border-color);border-radius:12px;font-family:var(--font-main);font-weight:600;font-size:.95rem;background:var(--bg-color);color:var(--text-color);outline:none;transition:all .2s;" onfocus="this.style.borderColor='var(--text-color)'" onblur="this.style.borderColor='var(--border-color)'">
            <select id="prompt-tag-filter" style="padding:12px 16px;border:2px solid var(--border-color);border-radius:12px;font-family:var(--font-main);font-weight:700;font-size:.95rem;background:var(--bg-color);color:var(--text-color);outline:none;cursor:pointer;">
                <option value="">All Tags</option>
                <?php foreach ($all_mgr_tags as $mgr_t):
                    $mgr_t_safe = htmlspecialchars($mgr_t); ?>
                <option value="<?= $mgr_t_safe ?>"> #<?= $mgr_t_safe ?></option>
                <?php
                endforeach; ?>
            </select>
        </div>

        <?php if (count($prompts) === 0): ?>
            <p style="text-align:center;color:#7D7887;font-weight:600;padding:60px 0;font-size:1.1rem;">No prompts yet &mdash; start uploading!</p>
        <?php else: ?>
        <div id="prompts-list">
            <?php
            $type_badge_map = [
                "secret" => "SCP",
                "unreleased" => "URP",
                "insta_viral" => "IVP",
                "already_uploaded" => "AUP",
            ];
            $type_badge_cls = [
                "secret" => "scp",
                "unreleased" => "urp",
                "insta_viral" => "ivp",
                "already_uploaded" => "aup",
            ];
            foreach ($prompts as $p):

                $p_tags_arr = array_map(
                    "trim",
                    explode(",", strtolower($p["tag"])),
                );
                $ptype = isset($type_badge_map[$p["prompt_type"]])
                    ? $p["prompt_type"]
                    : "secret";
                $badge_label = $type_badge_map[$ptype];
                $badge_cls = $type_badge_cls[$ptype];
                $item_title = strtolower(htmlspecialchars($p["title"]));
                $item_tags = htmlspecialchars(implode(",", $p_tags_arr));
                $item_img = htmlspecialchars($p["image_path"]);
                $item_name = htmlspecialchars($p["title"]);
                $item_code = htmlspecialchars($p["unlock_code"]);
                $item_likes = (int) $p["likes_count"];
                $item_id = (int) $p["id"];
                $item_js = addslashes(htmlspecialchars($p["title"]));
                ?>
            <div class="prompt-item" data-title="<?= $item_title ?>" data-tags="<?= $item_tags ?>">
                <img src="<?= $item_img ?>" class="prompt-item-img" alt="Cover">
                <div class="prompt-item-details">
                    <div class="prompt-item-title">
                        <?= $item_name ?>
                        <span class="card-type-badge <?= $badge_cls ?>" style="font-size:0.6rem;padding:2px 7px;position:relative;top:auto;right:auto;border-top:2px solid var(--text-color);border-radius:6px;display:inline-block;margin-left:8px;box-shadow:none;"><?= $badge_label ?></span>
                    </div>
                    <div class="prompt-item-meta">
                        <?php if ($ptype === "secret"): ?>
                            Code: <span class="code-badge"><?= $item_code ?></span> &nbsp;|&nbsp;
                        <?php endif; ?>
                        <i class="fa-solid fa-heart"></i> <?= $item_likes ?>
                        &nbsp;|&nbsp;
                        <?php foreach ($p_tags_arr as $pt):
                            if (empty($pt)) {
                                continue;
                            } ?>
                            <span style="background:var(--secondary-color);padding:2px 7px;border-radius:10px;font-size:0.75rem;font-weight:800;border:1.5px solid var(--text-color);">#<?= htmlspecialchars(
                                $pt,
                            ) ?></span>
                        <?php
                        endforeach; ?>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0;align-items:center;">
                    <?php $feat_bg = $p["is_featured"]
                        ? "var(--secondary-color)"
                        : "var(--bg-color)"; ?>
                    <button onclick="featurePrompt(<?= $p["id"] ?>)"
                        class="comic-btn-small"
                        style="background:<?= $feat_bg ?>; font-size:0.8rem; padding:6px 12px; border:2px solid var(--text-color); border-radius:10px; cursor:pointer; font-family:var(--font-main); font-weight:800;"
                        id="feat-btn-<?= $p["id"] ?>"
                        title="<?= $p["is_featured"]
                            ? "Currently Featured"
                            : "Set as Prompt of the Day" ?>">
                        <?= $p["is_featured"] ? "⭐ Featured" : "☆ Feature" ?>
                    </button>
                    <a href="edit_prompt.php?id=<?= $item_id ?>" class="edit-btn" title="Edit"><i class="fa-solid fa-pencil"></i></a>
                    <button class="delete-btn" title="Delete" onclick="confirmDelete(<?= $item_id ?>, '<?= $item_js ?>')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php
            endforeach;
            ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<form id="delete-form" method="POST" action="delete_prompt.php" style="display:none;">
    <input type="hidden" name="prompt_id" id="delete-prompt-id">
</form>

<script>
function featurePrompt(id) {
    fetch('feature_prompt.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'prompt_id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            // Reset all feature buttons
            document.querySelectorAll('[id^="feat-btn-"]').forEach(btn => {
                btn.textContent = '☆ Feature';
                btn.style.background = 'var(--bg-color)';
                btn.title = 'Set as Prompt of the Day';
            });
            // Highlight selected
            const btn = document.getElementById('feat-btn-' + id);
            if (btn) {
                btn.textContent = '⭐ Featured';
                btn.style.background = 'var(--secondary-color)';
                btn.title = 'Currently Featured';
            }
        }
    });
}

function confirmDelete(id, title) {
    if (confirm("Are you sure you want to delete '" + title + "'?")) {
        document.getElementById('delete-prompt-id').value = id;
        document.getElementById('delete-form').submit();
    }
}

const searchInput = document.getElementById('prompt-search');
const tagFilter   = document.getElementById('prompt-tag-filter');
const items       = document.querySelectorAll('.prompt-item');

function filterPrompts() {
    const query = searchInput.value.toLowerCase();
    const tag   = tagFilter.value;
    items.forEach(function(item) {
        const matchQuery = item.dataset.title.includes(query);
        const itemTags   = item.dataset.tags.split(',').map(function(t) { return t.trim(); });
        const matchTag   = tag === '' || itemTags.includes(tag);
        item.style.display = (matchQuery && matchTag) ? 'flex' : 'none';
    });
}

if (searchInput) searchInput.addEventListener('input', filterPrompts);
if (tagFilter)   tagFilter.addEventListener('change', filterPrompts);
</script>
</body>
</html>
