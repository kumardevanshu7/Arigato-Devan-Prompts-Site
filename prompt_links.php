<?php
session_start();
require_once "db.php";

// Admin only
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

$prompts = $pdo
    ->query(
        "SELECT id, title, image_path, prompt_type, likes_count FROM prompts ORDER BY created_at DESC",
    )
    ->fetchAll(PDO::FETCH_ASSOC);
$total = count($prompts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prompt Share Links — Admin</title>
    <link rel="stylesheet" href="style.css?v=1778100000">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-color); }

        .pl-wrap {
            max-width: 1000px;
            margin: 0 auto;
            padding: 32px 36px 100px;
        }

        .pl-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .pl-title {
            font-size: 2.2rem;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pl-sub {
            color: #7D7887;
            font-weight: 600;
            font-size: .92rem;
            margin-bottom: 24px;
        }

        .pl-search {
            width: 100%;
            padding: 13px 18px;
            border: var(--border-width) solid var(--text-color);
            border-radius: 14px;
            font-family: var(--font-main);
            font-weight: 600;
            font-size: 1rem;
            background: var(--card-bg);
            color: var(--text-color);
            outline: none;
            box-shadow: var(--shadow-comic);
            transition: all .2s;
            margin-bottom: 20px;
            box-sizing: border-box;
        }

        .pl-search:focus {
            box-shadow: var(--shadow-comic-hover);
            transform: translateY(-1px);
        }

        .pl-card {
            background: var(--card-bg);
            border: var(--border-width) solid var(--text-color);
            border-radius: 24px;
            box-shadow: var(--shadow-comic);
            overflow: hidden;
        }

        .pl-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pl-table thead tr {
            border-bottom: 2px solid var(--text-color);
            background: var(--bg-color);
        }

        .pl-table th {
            padding: 14px 16px;
            font-size: .75rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--text-color);
            text-align: left;
        }

        .pl-table th:last-child { text-align: right; }

        .pl-table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: background .15s;
        }

        .pl-table tbody tr:last-child { border-bottom: none; }
        .pl-table tbody tr:hover { background: var(--bg-color); }

        .pl-table td {
            padding: 12px 16px;
            vertical-align: middle;
        }

        .pl-cover {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid var(--text-color);
            display: block;
        }

        .pl-title-cell {
            font-weight: 800;
            font-size: .96rem;
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pl-likes {
            font-size: .82rem;
            color: #999;
            font-weight: 600;
            margin-top: 3px;
        }

        .type-pill {
            border-radius: 8px;
            padding: 4px 10px;
            font-size: .72rem;
            font-weight: 900;
            white-space: nowrap;
            border: 1.5px solid currentColor;
            display: inline-block;
        }

        .copy-btn {
            background: var(--primary-color);
            border: 2px solid var(--text-color);
            border-radius: 12px;
            padding: 9px 18px;
            font-family: var(--font-main);
            font-weight: 800;
            font-size: .85rem;
            cursor: pointer;
            box-shadow: 3px 3px 0 var(--text-color);
            transition: all .15s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 4px 4px 0 var(--text-color);
        }

        .copy-btn:active {
            transform: translateY(1px);
            box-shadow: 1px 1px 0 var(--text-color);
        }

        .copy-btn.copied {
            background: #d9f5e5;
            color: #2a7a4b;
            border-color: #2a7a4b;
            box-shadow: 3px 3px 0 #2a7a4b;
        }

        .pl-empty {
            text-align: center;
            color: #7D7887;
            font-weight: 600;
            padding: 40px 0;
            display: none;
        }

        @media (max-width: 600px) {
            .pl-wrap { padding: 22px 16px 80px; }
            .pl-title { font-size: 1.6rem; }
            .pl-title-cell { max-width: 140px; }
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
            <?= renderAvatar(
                $_SESSION["profile_image"] ?? "",
                "admin-avatar",
                "Admin",
            ) ?>
            <a href="dashboard.php" style="color:var(--text-color);font-weight:800;">ADMIN</a>
        </div>
        <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
    </div>
</header>

<div class="pl-wrap">

    <div class="pl-header">
        <div class="pl-title">
            <i class="fa-solid fa-link" style="color:#007ab8;"></i>
            Prompt Share Links
        </div>
        <div class="badge" style="margin:0;transform:rotate(0);background:#d4eaff;padding:8px 20px;font-size:1rem;">
            <?= $total ?> Prompts
        </div>
    </div>

    <p class="pl-sub">
        <i class="fa-solid fa-circle-info"></i>
        Copy any prompt's direct link &mdash; when a user opens it, that card auto-opens on the site. Works for guests &amp; logged-in users both!
    </p>

    <input
        type="text"
        class="pl-search"
        id="pl-search"
        placeholder="&#128269;  Search by title..."
        oninput="filterTable(this.value)"
    >

    <div class="pl-card">
        <table class="pl-table" id="pl-table">
            <thead>
                <tr>
                    <th style="width:68px;">Cover</th>
                    <th>Title</th>
                    <th style="width:110px;">Type</th>
                    <th style="width:60px;text-align:center;">❤️</th>
                    <th style="text-align:right;">Direct Link</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $type_map = [
                "secret" => [
                    "emoji" => "🔒",
                    "label" => "Secret Code",
                    "bg" => "#ffe3e3",
                    "color" => "#d03030",
                ],
                "unreleased" => [
                    "emoji" => "🌙",
                    "label" => "Unreleased",
                    "bg" => "#fff4cc",
                    "color" => "#7a5800",
                ],
                "insta_viral" => [
                    "emoji" => "🔥",
                    "label" => "Insta Viral",
                    "bg" => "#e3f7ff",
                    "color" => "#004f7a",
                ],
                "already_uploaded" => [
                    "emoji" => "📤",
                    "label" => "Already Uploaded",
                    "bg" => "#e6f2ff",
                    "color" => "#00509e",
                ],
            ];
            foreach ($prompts as $p):

                $pt = $p["prompt_type"] ?? "secret";
                $tinfo = $type_map[$pt] ?? $type_map["secret"];
                $title = htmlspecialchars($p["title"]);
                $img = htmlspecialchars($p["image_path"]);
                $id = (int) $p["id"];
                $likes = (int) $p["likes_count"];
                ?>
            <tr data-search="<?= strtolower($title) ?>">
                <td><img src="<?= $img ?>" class="pl-cover" alt="Cover"></td>
                <td>
                    <div class="pl-title-cell"><?= $title ?></div>
                    <div class="pl-likes"><i class="fa-solid fa-heart" style="color:#ff6b6b;font-size:.75rem;"></i> <?= $likes ?> likes</div>
                </td>
                <td>
                    <span class="type-pill" style="background:<?= $tinfo[
                        "bg"
                    ] ?>;color:<?= $tinfo["color"] ?>;">
                        <?= $tinfo["emoji"] ?> <?= $tinfo["label"] ?>
                    </span>
                </td>
                <td style="text-align:center;font-weight:800;color:#ff6b6b;"><?= $likes ?></td>
                <td style="text-align:right;">
                    <button class="copy-btn" onclick="copyLink(<?= $id ?>, this)">
                        <i class="fa-solid fa-copy"></i> Copy Link
                    </button>
                </td>
            </tr>
            <?php
            endforeach;
            ?>
            </tbody>
        </table>
        <p class="pl-empty" id="pl-empty">No prompts match your search.</p>
    </div>

</div>

<script>
function copyLink(id, btn) {
    var link = window.location.origin + '/card.php?id=' + id;
    navigator.clipboard.writeText(link).then(function() {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="fa-solid fa-copy"></i> Copy Link';
        }, 2500);
    }).catch(function() {
        window.prompt('Copy this link:', window.location.origin + '/card.php?id=' + id);
    });
}

function filterTable(query) {
    query = query.toLowerCase().trim();
    var rows  = document.querySelectorAll('#pl-table tbody tr');
    var found = 0;
    rows.forEach(function(row) {
        var match = (row.dataset.search || '').includes(query);
        row.style.display = match ? '' : 'none';
        if (match) found++;
    });
    document.getElementById('pl-empty').style.display = found === 0 ? 'block' : 'none';
}
</script>
</body>
</html>
