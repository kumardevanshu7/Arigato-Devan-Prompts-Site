<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] =
        "You do not have permission to access the manage prompts page.";
    header("Location: index.php");
    exit();
}

// ── Bulk toggle (checkbox + publish/unpublish) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'], $_POST['selected_ids'])) {
    $ids = array_map('intval', (array)$_POST['selected_ids']);
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $new_type = $_POST['bulk_action'] === 'unreleased' ? 'unreleased' : $_POST['bulk_original_type'] ?? 'secret';
        $stmt = $pdo->prepare("UPDATE prompts SET prompt_type = ? WHERE id IN ($placeholders)");
        $stmt->execute(array_merge([$_POST['bulk_action']], $ids));
    }
    header('Location: manage_prompts.php'); exit;
}

$prompts = $pdo
    ->query("SELECT *, is_featured FROM prompts ORDER BY created_at DESC")
    ->fetchAll(PDO::FETCH_ASSOC);
$total_prompts = count($prompts);

// Performance table: top prompts by score (likes + saves)
$perf_prompts = $pdo->query("
    SELECT p.id, p.title, p.image_path, p.prompt_type, p.likes_count, p.slug,
           COALESCE((SELECT COUNT(*) FROM saved_prompts sp WHERE sp.prompt_id=p.id),0) as saves_count
    FROM prompts p
    ORDER BY (p.likes_count + COALESCE((SELECT COUNT(*) FROM saved_prompts sp WHERE sp.prompt_id=p.id),0)) DESC
    LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);

// Dead prompts (0 likes + 0 saves)
$dead_prompts = $pdo->query("
    SELECT p.id, p.title, p.image_path, p.prompt_type, p.created_at
    FROM prompts p
    WHERE p.likes_count = 0
      AND NOT EXISTS (SELECT 1 FROM saved_prompts sp WHERE sp.prompt_id=p.id)
    ORDER BY p.created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="style.css?v=2026052201">
    <link rel='preload' href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' as='style' onload='this.onload=null;this.rel="stylesheet"'>
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
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
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
                <img loading="lazy" src="<?= $item_img ?>" class="prompt-item-img" alt="Cover">
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

<!-- Prompt Performance Table -->
<div class="dashboard-wrap" style="padding-top:0;">
    <div class="dash-card" style="margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:16px;border-bottom:2px dashed var(--border-color);padding-bottom:14px;">
            <h2 style="margin:0;padding:0;border:none;font-size:1.2rem;"><i class="fa-solid fa-chart-bar" style="color:#3b82f6;"></i> Prompt Performance</h2>
            <span style="font-size:.75rem;font-weight:800;color:#999;">Top 15 by Score (Likes + Saves)</span>
        </div>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:480px;">
            <thead>
                <tr style="border-bottom:2px solid var(--text-color);text-align:left;">
                    <th style="padding:10px 12px;font-size:.7rem;font-weight:900;text-transform:uppercase;">#</th>
                    <th style="padding:10px 12px;font-size:.7rem;font-weight:900;text-transform:uppercase;">Prompt</th>
                    <th style="padding:10px 12px;font-size:.7rem;font-weight:900;text-transform:uppercase;text-align:center;">❤️ Likes</th>
                    <th style="padding:10px 12px;font-size:.7rem;font-weight:900;text-transform:uppercase;text-align:center;">🔖 Saves</th>
                    <th style="padding:10px 12px;font-size:.7rem;font-weight:900;text-transform:uppercase;text-align:center;">🔥 Score</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($perf_prompts as $pi => $pp):
                $score = $pp['likes_count'] + $pp['saves_count'];
                $max_score = $perf_prompts[0]['likes_count'] + $perf_prompts[0]['saves_count'] ?: 1;
                $bar_w = round($score / $max_score * 100);
            ?>
            <tr style="border-bottom:1px solid var(--border-color);">
                <td style="padding:10px 12px;font-weight:900;color:#bbb;font-size:.82rem;"><?= $pi+1 ?></td>
                <td style="padding:10px 12px;max-width:220px;">
                    <div style="font-weight:800;font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($pp['title']) ?></div>
                    <div style="background:#eee;border-radius:4px;height:5px;margin-top:5px;overflow:hidden;">
                        <div style="width:<?= $bar_w ?>%;height:100%;background:linear-gradient(90deg,var(--primary-color),var(--secondary-color));border-radius:4px;"></div>
                    </div>
                </td>
                <td style="padding:10px 12px;text-align:center;font-weight:800;font-size:.88rem;color:#ef4444;"><?= $pp['likes_count'] ?></td>
                <td style="padding:10px 12px;text-align:center;font-weight:800;font-size:.88rem;color:#3b82f6;"><?= $pp['saves_count'] ?></td>
                <td style="padding:10px 12px;text-align:center;">
                    <span style="background:<?= $pi===0?'#fff3cd':($pi<3?'#e0f2fe':'#f8f8f8') ?>;border:1.5px solid var(--text-color);border-radius:10px;padding:3px 10px;font-weight:900;font-size:.82rem;"><?= $score ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Dead Prompts -->
    <?php if (!empty($dead_prompts)): ?>
    <div class="dash-card" style="margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:16px;border-bottom:2px dashed var(--border-color);padding-bottom:14px;">
            <h2 style="margin:0;padding:0;border:none;font-size:1.2rem;"><i class="fa-solid fa-skull" style="color:#ef4444;"></i> Dead Prompts <span style="font-size:.72rem;background:#ffe3e3;color:#d03030;border:1.5px solid #d03030;border-radius:20px;padding:2px 10px;font-weight:900;"><?= count($dead_prompts) ?> with 0 likes & 0 saves</span></h2>
        </div>
        <?php
        $type_colors = ['secret'=>'#ffe3e3','unreleased'=>'#fff4cc','insta_viral'=>'#e3f7ff','already_uploaded'=>'#e6f2ff'];
        foreach ($dead_prompts as $dp): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px dashed var(--border-color);">
            <img loading="lazy" src="<?= htmlspecialchars($dp['image_path']) ?>" style="width:42px;height:42px;object-fit:cover;border-radius:8px;border:2px solid var(--text-color);flex-shrink:0;" alt="">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:800;font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($dp['title']) ?></div>
                <div style="font-size:.72rem;color:#aaa;font-weight:600;"><?= date('d M Y', strtotime($dp['created_at'])) ?></div>
            </div>
            <span style="background:<?= $type_colors[$dp['prompt_type']] ?? '#eee' ?>;border:1.5px solid var(--text-color);border-radius:8px;padding:3px 9px;font-size:.7rem;font-weight:900;white-space:nowrap;"><?= strtoupper($dp['prompt_type']) ?></span>
            <a href="edit_prompt.php?id=<?= $dp['id'] ?>" style="background:#d4eaff;border:2px solid var(--text-color);border-radius:8px;padding:5px 11px;font-size:.75rem;font-weight:800;text-decoration:none;color:var(--text-color);white-space:nowrap;">✏️ Edit</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Bulk Publish/Unpublish -->
    <div class="dash-card" style="margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:16px;border-bottom:2px dashed var(--border-color);padding-bottom:14px;">
            <h2 style="margin:0;padding:0;border:none;font-size:1.2rem;"><i class="fa-solid fa-check-double" style="color:#22c55e;"></i> Bulk Type Change</h2>
            <span style="font-size:.75rem;font-weight:800;color:#999;">Select prompts → change type</span>
        </div>
        <form method="POST" id="bulk-form">
            <input type="hidden" name="bulk_action" id="bulk-action-val" value="">
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
                <?php foreach (['secret','unreleased','insta_viral','already_uploaded'] as $bt): ?>
                <button type="button" onclick="setBulkAction('<?= $bt ?>')"
                    style="background:<?= $type_colors[$bt]??'#eee' ?>;border:2px solid var(--text-color);border-radius:12px;padding:8px 16px;font-family:var(--font-main);font-weight:800;font-size:.82rem;cursor:pointer;box-shadow:2px 2px 0 var(--text-color);transition:all .15s;"
                    id="bulk-btn-<?= $bt ?>">
                    Set → <?= strtoupper($bt) ?>
                </button>
                <?php endforeach; ?>
                <button type="button" onclick="selectAllBulk()" style="background:var(--bg-color);border:2px solid var(--text-color);border-radius:12px;padding:8px 16px;font-family:var(--font-main);font-weight:800;font-size:.82rem;cursor:pointer;box-shadow:2px 2px 0 var(--text-color);">☑️ Select All</button>
            </div>
            <div id="bulk-list" style="max-height:320px;overflow-y:auto;border:2px solid var(--border-color);border-radius:14px;padding:8px;">
                <?php foreach ($prompts as $bp): ?>
                <label style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:10px;cursor:pointer;transition:background .15s;" onmouseover="this.style.background='var(--bg-color)'" onmouseout="this.style.background=''">
                    <input type="checkbox" name="selected_ids[]" value="<?= $bp['id'] ?>" style="width:18px;height:18px;accent-color:var(--primary-color);flex-shrink:0;">
                    <img loading="lazy" src="<?= htmlspecialchars($bp['image_path']) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:8px;border:2px solid var(--text-color);flex-shrink:0;" alt="">
                    <span style="font-weight:800;font-size:.88rem;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($bp['title']) ?></span>
                    <span style="font-size:.7rem;font-weight:900;background:<?= $type_colors[$bp['prompt_type']]??'#eee' ?>;border:1.5px solid var(--text-color);border-radius:8px;padding:2px 8px;white-space:nowrap;"><?= strtoupper($bp['prompt_type']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <button type="submit" id="bulk-submit" style="background:var(--primary-color);border:2px solid var(--text-color);border-radius:12px;padding:10px 22px;font-family:var(--font-main);font-weight:900;font-size:.9rem;cursor:pointer;box-shadow:3px 3px 0 var(--text-color);" disabled>Apply to Selected</button>
                <span id="bulk-selected-count" style="font-size:.82rem;font-weight:700;color:#999;">0 selected</span>
            </div>
        </form>
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

// Bulk action JS
function setBulkAction(type) {
    document.getElementById('bulk-action-val').value = type;
    document.querySelectorAll('[id^="bulk-btn-"]').forEach(b => b.style.outline = 'none');
    const btn = document.getElementById('bulk-btn-' + type);
    if (btn) btn.style.outline = '3px solid var(--text-color)';
    updateBulkSubmit();
}
function selectAllBulk() {
    const cbs = document.querySelectorAll('#bulk-list input[type=checkbox]');
    const allChecked = [...cbs].every(c => c.checked);
    cbs.forEach(c => c.checked = !allChecked);
    updateBulkSubmit();
}
function updateBulkSubmit() {
    const checked = document.querySelectorAll('#bulk-list input[type=checkbox]:checked').length;
    const action  = document.getElementById('bulk-action-val').value;
    document.getElementById('bulk-selected-count').textContent = checked + ' selected';
    document.getElementById('bulk-submit').disabled = !(checked > 0 && action);
}
document.querySelectorAll('#bulk-list input[type=checkbox]').forEach(cb => cb.addEventListener('change', updateBulkSubmit));
document.getElementById('bulk-form').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('#bulk-list input[type=checkbox]:checked').length;
    const action  = document.getElementById('bulk-action-val').value;
    if (!checked || !action) { e.preventDefault(); return; }
    if (!confirm('Change ' + checked + ' prompt(s) to ' + action + '?')) e.preventDefault();
});

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
