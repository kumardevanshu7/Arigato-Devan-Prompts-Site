<?php
session_start();
require_once "db.php";

// Protect page (Admin Only)
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] =
        "You do not have permission to access the dashboard.";
    header("Location: index.php");
    exit();
}

// --- Analytics Queries ---
$total_prompts = $pdo->query("SELECT COUNT(*) FROM prompts")->fetchColumn();
$total_likes =
    $pdo->query("SELECT SUM(likes_count) FROM prompts")->fetchColumn() ?: 0;
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Most liked prompt
$most_liked = $pdo
    ->query(
        "SELECT title, likes_count FROM prompts ORDER BY likes_count DESC LIMIT 1",
    )
    ->fetch(PDO::FETCH_ASSOC);

// Weekly growth (prompts added this week)
$weekly_prompts = $pdo
    ->query(
        "SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    )
    ->fetchColumn();
$weekly_users = $pdo
    ->query(
        "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    )
    ->fetchColumn();

// All prompts list
$prompts = $pdo
    ->query("SELECT * FROM prompts ORDER BY created_at DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

// Users list
$users = $pdo
    ->query(
        "SELECT id, username, email, avatar, gender, role, created_at FROM users ORDER BY created_at DESC",
    )
    ->fetchAll(PDO::FETCH_ASSOC);
$total_users_count = count($users);

// Flash messages
$success = $_SESSION["success_msg"] ?? "";
$error = $_SESSION["error_msg"] ?? "";
unset($_SESSION["success_msg"], $_SESSION["error_msg"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard &ndash; Arigato Devan Prompts</title>
    <link rel="stylesheet" href="style.css?v=1778100000">
    <style>
        body { background: var(--bg-color); }

        .dashboard-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 40px 100px;
        }

        .dash-page-title {
            font-size: 2.2rem;
            font-weight: 900;
            margin-bottom: 30px;
        }

        /* Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--card-bg);
            border: var(--border-width) solid var(--text-color);
            border-radius: 20px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: var(--shadow-comic);
            transition: all 0.2s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-4px) rotate(-1deg);
            box-shadow: var(--shadow-comic-hover);
        }

        .stat-card.accent-1 { background: var(--primary-color); }
        .stat-card.accent-2 { background: var(--secondary-color); }
        .stat-card.accent-3 { background: #d4eaff; }
        .stat-card.accent-4 { background: #ffe3f0; }
        .stat-card.accent-5 { background: #d9f5e5; }

        .stat-value {
            font-size: 2.4rem;
            font-weight: 900;
            color: var(--text-color);
            margin-bottom: 6px;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--text-color);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-sub {
            font-size: 0.75rem;
            color: #666;
            margin-top: 4px;
            font-weight: 600;
        }

        /* Dashboard columns */
        .dashboard-cols {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
        }

        /* Form */
        .dash-card {
            background: var(--card-bg);
            border: var(--border-width) solid var(--text-color);
            border-radius: 24px;
            padding: 30px;
            box-shadow: var(--shadow-comic);
        }

        .dash-card h2 {
            font-size: 1.6rem;
            font-weight: 900;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px dashed var(--border-color);
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            font-weight: 800;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: var(--border-width) solid var(--text-color);
            border-radius: 12px;
            font-family: var(--font-main);
            font-size: 0.95rem;
            font-weight: 600;
            background: var(--bg-color);
            color: var(--text-color);
            box-shadow: var(--shadow-comic);
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary-dark);
            box-shadow: var(--shadow-comic-hover);
            transform: translateY(-1px);
        }

        .form-group textarea { resize: vertical; min-height: 100px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .unreleased-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--bg-color);
            border: var(--border-width) solid var(--text-color);
            border-radius: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .unreleased-toggle input[type="checkbox"] {
            width: 22px; height: 22px;
            box-shadow: none;
            cursor: pointer;
            border-radius: 6px;
            accent-color: var(--primary-dark);
        }

        /* Prompt List */
        .prompt-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px;
            border: 2px solid var(--border-color);
            border-radius: 14px;
            margin-bottom: 12px;
            transition: all 0.2s;
        }

        .prompt-item:hover {
            border-color: var(--text-color);
            transform: translateX(3px);
            box-shadow: 3px 3px 0px var(--text-color);
        }

        .prompt-item.is-unreleased { border-style: dashed; background: rgba(255, 220, 100, 0.08); }

        .prompt-item-img {
            width: 56px; height: 56px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid var(--text-color);
            flex-shrink: 0;
        }

        .prompt-item-details { flex-grow: 1; min-width: 0; }

        .prompt-item-title {
            font-weight: 800;
            font-size: 1rem;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .prompt-item-meta {
            font-size: 0.82rem;
            color: #7D7887;
            font-weight: 600;
        }

        .code-badge {
            font-weight: 900;
            background: var(--secondary-color);
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid var(--text-color);
            font-family: monospace;
            font-size: 0.9rem;
        }

        .unreleased-badge {
            display: inline-block;
            background: var(--primary-color);
            color: var(--text-color);
            font-size: 0.7rem;
            font-weight: 900;
            padding: 2px 8px;
            border-radius: 20px;
            border: 1.5px solid var(--text-color);
            text-transform: uppercase;
            margin-left: 6px;
            vertical-align: middle;
        }

        .delete-btn {
            background: #FF6B6B;
            color: #fff;
            border: 2px solid var(--text-color);
            padding: 8px 12px;
            border-radius: 10px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 2px 2px 0px var(--text-color);
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .delete-btn:hover {
            background: #FF4757;
            transform: translateY(-2px) rotate(2deg);
            box-shadow: 4px 4px 0px var(--text-color);
        }

        .file-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--bg-color);
            padding: 10px 16px;
            border: var(--border-width) solid var(--text-color);
            border-radius: 12px;
            box-shadow: var(--shadow-comic);
        }

        .file-upload-btn {
            background: var(--primary-color);
            color: var(--text-color);
            padding: 8px 16px;
            border: 2px solid var(--text-color);
            border-radius: 8px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 2px 2px 0px var(--text-color);
            transition: all 0.2s;
            white-space: nowrap;
        }

        .file-upload-name {
            font-weight: 600;
            color: #7D7887;
            font-size: 0.9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .flash-success {
            background: #d9f5e5;
            color: #1e5c36;
            padding: 16px;
            border: var(--border-width) solid var(--text-color);
            border-radius: 12px;
            font-weight: 800;
            margin-bottom: 20px;
            box-shadow: 3px 3px 0px var(--text-color);
        }

        .flash-error {
            background: #ffe6e6;
            color: #a70000;
            padding: 16px;
            border: var(--border-width) solid var(--text-color);
            border-radius: 12px;
            font-weight: 800;
            margin-bottom: 20px;
            box-shadow: 3px 3px 0px var(--text-color);
        }

        .edit-btn {
            background: var(--primary-color);
            color: var(--text-color);
            border: 2px solid var(--text-color);
            padding: 8px 10px;
            border-radius: 10px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 2px 2px 0 var(--text-color);
            transition: all .2s;
            text-decoration: none;
            font-size: .95rem;
            display: inline-flex;
            align-items: center;
        }
        .edit-btn:hover { transform: translateY(-2px); box-shadow: 4px 4px 0 var(--text-color); }
        .users-table { width:100%; border-collapse:collapse; }
        .users-table th { font-size:.8rem; font-weight:800; text-transform:uppercase; letter-spacing:.5px; padding:10px 14px; background:var(--bg-color); border-bottom:2px solid var(--border-color); text-align:left; }
        .users-table td { padding:12px 14px; border-bottom:1px solid var(--border-color); vertical-align:middle; }
        .users-table tr:last-child td { border-bottom:none; }
        .users-table tr:hover td { background:var(--bg-color); }
        .user-avatar-sm { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--primary-color); }
        .role-badge { padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:900; border:1.5px solid var(--text-color); }
        .role-admin { background:var(--primary-color); }
        .role-user  { background:var(--secondary-color); }
        @media (max-width: 992px) {
            .dashboard-cols { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <?php include_once "gtag.php"; ?>
</head>
<body>
    <header>
        <div class="logo-area" id="logo-container"  style="cursor:pointer;">
            <div class="logo-flipper">
                <div class="logo-front">
                    <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Arigato Devan Logo" id="profile-logo">
                </div>
                <div class="logo-back">
                    <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt="Logo Alt">
                </div>
            </div>
            <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
        </div>
        <nav class="nav-links">
            <a href="index.php">HOME</a>
            <a href="gallery.php">GALLERY</a>
            <a href="analytics.php" style="background:var(--secondary-color);border:2px solid var(--text-color);box-shadow:3px 3px 0 var(--text-color);border-radius:20px;"><i class="fa-solid fa-chart-simple"></i> ANALYTICS</a>
            <a href="blog_admin.php" style="background:var(--primary-color);border:2px solid var(--text-color);box-shadow:3px 3px 0 var(--text-color);border-radius:20px;"><i class="fa-solid fa-pen-nib"></i> BLOGS</a>
        </nav>
        <div class="header-right">
            <div class="header-divider"></div>
            <div style="display:flex;align-items:center;gap:8px;">
                <a href="profile.php" title="Edit Profile">
                    <?= renderAvatar(
                        $_SESSION["profile_image"] ?? "",
                        "admin-avatar",
                        "Admin",
                        'style="transition:transform 0.2s;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"',
                    ) ?>
                </a>
                <a href="dashboard.php" style="color:var(--text-color);font-weight:800;" class="active">ADMIN</a>
            </div>
            <a href="login.php?logout=1" class="logout">
                <i class="fa-solid fa-right-from-bracket"></i> LOGOUT
            </a>
        </div>
    </header>

    <div class="dashboard-wrap">
        <?php if ($success): ?>
            <div class="flash-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="dash-page-title"><i class="fa-solid fa-chart-simple"></i> Admin Dashboard</div>

        <!-- Analytics Grid -->
        <div class="analytics-grid">
            <div class="stat-card accent-1">
                <div class="stat-value"><?= $total_prompts ?></div>
                <div class="stat-label">Total Prompts</div>
            </div>
            <div class="stat-card accent-2">
                <div class="stat-value"><?= number_format($total_likes) ?></div>
                <div class="stat-label">Total Likes</div>
            </div>
            <div class="stat-card accent-3">
                <div class="stat-value"><?= $total_users ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card accent-4">
                <div class="stat-value">+<?= $weekly_prompts ?></div>
                <div class="stat-label">Prompts This Week</div>
            </div>
            <div class="stat-card accent-5">
                <div class="stat-value">+<?= $weekly_users ?></div>
                <div class="stat-label">New Users (7d)</div>
            </div>
            <?php if ($most_liked): ?>
            <div class="stat-card" style="background: #fff3cd; grid-column: span 2;">
                <div class="stat-value" style="font-size:1.1rem; font-weight:800; color:var(--text-color);"><i class="fa-solid fa-star"></i> <?= htmlspecialchars(
                    $most_liked["title"],
                ) ?></div>
                <div class="stat-label">Most Liked &mdash; <?= $most_liked[
                    "likes_count"
                ] ?> <i class="fa-solid fa-heart"></i></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick Action Cards: Upload & Manage (now separate pages) -->
        <div class="dashboard-cols" style="gap:24px;">
            <!-- Upload Prompt Card -->
            <a href="upload_prompt.php" class="dash-card" style="text-decoration:none;color:var(--text-color);display:block;background:var(--primary-color);transition:all .2s;cursor:pointer;" onmouseover="this.style.transform='translateY(-4px) rotate(-1deg)';this.style.boxShadow='var(--shadow-comic-hover)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="display:flex;align-items:center;gap:18px;">
                    <div style="width:64px;height:64px;background:var(--text-color);border-radius:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid fa-upload" style="font-size:1.6rem;color:var(--bg-color);"></i>
                    </div>
                    <div>
                        <h2 style="font-size:1.4rem;margin-bottom:4px;">Upload Prompt</h2>
                        <p style="color:var(--text-color);opacity:.7;font-weight:600;font-size:.9rem;">Add a new AI prompt reel to the platform</p>
                    </div>
                    <i class="fa-solid fa-arrow-right" style="margin-left:auto;font-size:1.3rem;opacity:.6;"></i>
                </div>
            </a>

            <!-- Manage Prompts Card -->
            <a href="manage_prompts.php" class="dash-card" style="text-decoration:none;color:var(--text-color);display:block;background:var(--secondary-color);transition:all .2s;cursor:pointer;" onmouseover="this.style.transform='translateY(-4px) rotate(1deg)';this.style.boxShadow='var(--shadow-comic-hover)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div style="display:flex;align-items:center;gap:18px;">
                    <div style="width:64px;height:64px;background:var(--text-color);border-radius:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid fa-list-check" style="font-size:1.6rem;color:var(--bg-color);"></i>
                    </div>
                    <div>
                        <h2 style="font-size:1.4rem;margin-bottom:4px;">Manage Prompts</h2>
                        <p style="color:var(--text-color);opacity:.7;font-weight:600;font-size:.9rem;">Edit, delete, or review all <?= $total_prompts ?> prompts</p>
                    </div>
                    <i class="fa-solid fa-arrow-right" style="margin-left:auto;font-size:1.3rem;opacity:.6;"></i>
                </div>
            </a>

        <!-- Prompt Share Links Card (links to dedicated page) -->
        <a href="prompt_links.php" class="dash-card" style="text-decoration:none;color:var(--text-color);display:block;background:#d4eaff;transition:all .2s;cursor:pointer;margin-top:0;align-self:start;" onmouseover="this.style.transform='translateY(-4px) rotate(-1deg)';this.style.boxShadow='var(--shadow-comic-hover)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="display:flex;align-items:center;gap:18px;">
                <div style="width:64px;height:64px;background:var(--text-color);border-radius:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid fa-link" style="font-size:1.6rem;color:#d4eaff;"></i>
                </div>
                <div>
                    <h2 style="font-size:1.4rem;margin-bottom:4px;">Prompt Share Links</h2>
                    <p style="color:var(--text-color);opacity:.7;font-weight:600;font-size:.9rem;">Copy direct links for any prompt to share with users</p>
                </div>
                <i class="fa-solid fa-arrow-right" style="margin-left:auto;font-size:1.3rem;opacity:.6;"></i>
            </div>
        </a>

        <!-- Prompt of the Day Manager Card -->
        <a href="potd_manager.php" class="dash-card" style="text-decoration:none;color:var(--text-color);display:block;background:#fff3cd;transition:all .2s;cursor:pointer;margin-top:0;align-self:start;" onmouseover="this.style.transform='translateY(-4px) rotate(1deg)';this.style.boxShadow='var(--shadow-comic-hover)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="display:flex;align-items:center;gap:18px;">
                <div style="width:64px;height:64px;background:var(--text-color);border-radius:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid fa-star" style="font-size:1.6rem;color:#f0c040;"></i>
                </div>
                <div>
                    <h2 style="font-size:1.4rem;margin-bottom:4px;">Prompt of the Day</h2>
                    <p style="color:var(--text-color);opacity:.7;font-weight:600;font-size:.9rem;">Manage featured daily prompts with toggle controls</p>
                </div>
                <i class="fa-solid fa-arrow-right" style="margin-left:auto;font-size:1.3rem;opacity:.6;"></i>
            </div>
        </a>

        <!-- PLACEHOLDER for old section start - will be removed below -->
        <div class="dash-card" style="display:none;margin-top:28px;grid-column:1/-1;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;border-bottom:2px dashed var(--border-color);padding-bottom:16px;flex-wrap:wrap;gap:12px;">
                <h2 style="margin:0;padding:0;border:none;"><i class="fa-solid fa-link" style="color:#007ab8;"></i> Prompt Share Links</h2>
                <div class="badge" style="margin:0;transform:rotate(0);background:#d4eaff;padding:6px 16px;"><?= $total_prompts ?> Prompts</div>
            </div>
            <p style="color:#7D7887;font-weight:600;margin-bottom:14px;font-size:.88rem;"><i class="fa-solid fa-circle-info"></i> Copy a prompt's direct link &mdash; when the user opens it, that card auto-opens on the site. Works for guests too!</p>
            <input type="text" id="link-table-search" placeholder="&#128269; Search by title..." oninput="filterLinkTable(this.value)" style="width:100%;padding:11px 15px;border:2px solid var(--border-color);border-radius:12px;font-family:var(--font-main);font-weight:600;font-size:.9rem;background:var(--bg-color);color:var(--text-color);outline:none;transition:all .2s;margin-bottom:16px;box-sizing:border-box;" onfocus="this.style.borderColor='var(--text-color)'" onblur="this.style.borderColor='var(--border-color)'">
            <div style="overflow-x:auto;">
                <table id="link-table" style="width:100%;border-collapse:collapse;min-width:480px;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--text-color);text-align:left;">
                            <th style="padding:10px 12px;font-size:.75rem;font-weight:900;text-transform:uppercase;letter-spacing:.5px;width:52px;">Cover</th>
                            <th style="padding:10px 12px;font-size:.75rem;font-weight:900;text-transform:uppercase;letter-spacing:.5px;">Title</th>
                            <th style="padding:10px 12px;font-size:.75rem;font-weight:900;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;">Type</th>
                            <th style="padding:10px 12px;font-size:.75rem;font-weight:900;text-transform:uppercase;letter-spacing:.5px;text-align:right;white-space:nowrap;">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $ltype_map = [
                        "secret" => [
                            "label" => "&#128274; SCP",
                            "bg" => "#ffe3e3",
                            "color" => "#d03030",
                        ],
                        "unreleased" => [
                            "label" => "&#127769; URP",
                            "bg" => "#fff4cc",
                            "color" => "#7a5800",
                        ],
                        "insta_viral" => [
                            "label" => "&#128293; IVP",
                            "bg" => "#e3f7ff",
                            "color" => "#004f7a",
                        ],
                        "already_uploaded" => [
                            "label" => "&#128228; AUP",
                            "bg" => "#e6f2ff",
                            "color" => "#00509e",
                        ],
                    ];
                    foreach ($prompts as $lp):

                        $lt = $lp["prompt_type"] ?? "secret";
                        $linfo = $ltype_map[$lt] ?? $ltype_map["secret"];
                        $ltitle = htmlspecialchars($lp["title"]);
                        $limg = htmlspecialchars($lp["image_path"]);
                        $lid = (int) $lp["id"];
                        ?>
                        <tr data-search="<?= strtolower(
                            $ltitle,
                        ) ?>" style="border-bottom:1px solid var(--border-color);transition:background .15s;" onmouseover="this.style.background='var(--bg-color)'" onmouseout="this.style.background=''">
                            <td style="padding:9px 12px;"><img src="<?= $limg ?>" style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:2px solid var(--text-color);display:block;"></td>
                            <td style="padding:9px 12px;font-weight:700;font-size:.92rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $ltitle ?></td>
                            <td style="padding:9px 12px;"><span style="background:<?= $linfo[
                                "bg"
                            ] ?>;color:<?= $linfo[
    "color"
] ?>;border:1.5px solid currentColor;border-radius:8px;padding:3px 9px;font-size:.72rem;font-weight:900;white-space:nowrap;"><?= $linfo[
    "label"
] ?></span></td>
                            <td style="padding:9px 12px;text-align:right;">
                                <button onclick="copyPromptLink(<?= $lid ?>, this)" style="background:var(--primary-color);border:2px solid var(--text-color);border-radius:10px;padding:7px 14px;font-family:var(--font-main);font-weight:800;font-size:.8rem;cursor:pointer;box-shadow:2px 2px 0 var(--text-color);transition:all .15s;white-space:nowrap;">
                                    <i class="fa-solid fa-copy"></i> Copy Link
                                </button>
                            </td>
                        </tr>
                    <?php
                    endforeach;
                    ?>
                    </tbody>
                </table>
            </div>
            <p id="link-table-empty" style="display:none;text-align:center;color:#7D7887;font-weight:600;padding:20px 0;">No prompts found.</p>
        </div>

        <!-- User Management -->
        <div class="dash-card" style="margin-top:40px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;border-bottom:2px dashed var(--border-color);padding-bottom:16px;">
                <h2 style="margin:0;padding:0;border:none;"><i class="fa-solid fa-users"></i> User Management</h2>
                <div class="badge" style="margin:0;transform:rotate(0);background:var(--secondary-color);padding:6px 16px;"><?= $total_users_count ?> Users</div>
            </div>
            <?php if (count($users) === 0): ?>
                <p style="text-align:center;color:#7D7887;font-weight:600;padding:30px 0;">No users registered yet.</p>
            <?php
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                // Show avatar (from onboarding) first, then dicebear &mdash; NEVER Google pic
                else: ?>
            <div style="overflow-x:auto;">
            <table class="users-table">
                <thead><tr><th>Avatar</th><th>Name / Email</th><th>Gender</th><th>Role</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                <td><?php $u_avatar = !empty($u["avatar"])
                    ? $u["avatar"]
                    : "https://api.dicebear.com/7.x/avataaars/svg?seed=" .
                        urlencode(
                            $u["email"] ?? "x",
                        ); ?><img src="<?= htmlspecialchars(
    $u_avatar,
) ?>" class="user-avatar-sm" alt=""></td>
                    <td><div style="font-weight:800;font-size:.95rem;"><?= htmlspecialchars(
                        $u["username"] ?? "&mdash;",
                    ) ?></div><div style="font-size:.8rem;color:#7D7887;font-weight:600;"><?= htmlspecialchars(
    $u["email"] ?? "",
) ?></div></td>
                    <td><?= htmlspecialchars(
                        ucfirst($u["gender"] ?? "&mdash;"),
                    ) ?></td>
                    <td><span class="role-badge <?= $u["role"] === "admin"
                        ? "role-admin"
                        : "role-user" ?>"><?= htmlspecialchars(
    strtoupper($u["role"] ?? "user"),
) ?></span></td>
                    <td style="font-size:.82rem;color:#7D7887;font-weight:600;"><?= date(
                        "d M Y",
                        strtotime($u["created_at"]),
                    ) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div id="delete-modal" style="display:none;position:fixed;inset:0;background:rgba(45,42,53,.45);backdrop-filter:blur(8px);z-index:2000;align-items:center;justify-content:center;">
        <div style="background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:36px 32px;max-width:400px;width:90%;box-shadow:8px 8px 0 var(--text-color);text-align:center;">
            <div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-trash"></i></div>
            <h3 style="font-size:1.4rem;font-weight:900;margin-bottom:10px;">Delete Prompt?</h3>
            <p id="delete-modal-name" style="font-weight:700;color:#555;margin-bottom:24px;font-size:.95rem;"></p>
            <div style="display:flex;gap:12px;">
                <button onclick="closeDeleteModal()" style="flex:1;padding:14px;background:var(--bg-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);transition:all .2s;">Cancel</button>
                <form id="delete-form" action="delete_prompt.php" method="POST" style="flex:1;margin:0;">
                    <input type="hidden" id="delete-prompt-id" name="prompt_id" value="">
                    <button type="submit" style="width:100%;padding:14px;background:#FF6B6B;color:#fff;border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);transition:all .2s;">Delete</button>
                </form>
            </div>
        </div>
    </div>

    </div><!-- end .dashboard-wrap -->
</body>
<script>
// Search & Filter
const searchInput = document.getElementById('prompt-search');
const catFilter   = document.getElementById('prompt-cat-filter');
function filterPrompts() {
    const q   = (searchInput?.value || '').toLowerCase();
    const cat = (catFilter?.value || '').toLowerCase();
    document.querySelectorAll('#prompts-list .prompt-item').forEach(item => {
        const title = item.dataset.title || '';
        const c     = (item.dataset.cat || '').toLowerCase();
        const show  = title.includes(q) && (!cat || c === cat);
        item.style.display = show ? '' : 'none';
    });
}
searchInput?.addEventListener('input', filterPrompts);
catFilter?.addEventListener('change', filterPrompts);

// Delete Confirm Modal
function confirmDelete(id, name) {
    document.getElementById('delete-prompt-id').value = id;
    document.getElementById('delete-modal-name').textContent = '"' + name + '"';
    const m = document.getElementById('delete-modal');
    m.style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
}
document.getElementById('delete-modal')?.addEventListener('click', function(e){
    if (e.target === this) closeDeleteModal();
});

// Blog Delete Confirm Modal
function confirmBlogDelete(id, name) {
    document.getElementById('blog-delete-id').value = id;
    document.getElementById('blog-delete-name').textContent = '"' + name + '"';
    const m = document.getElementById('blog-delete-modal');
    m.style.display = 'flex';
}
document.getElementById('blog-delete-modal')?.addEventListener('click', function(e){
    if (e.target === this) this.style.display = 'none';
});

// Copy prompt shareable link
function copyPromptLink(id, btn) {
    var link = window.location.origin + '/card.php?id=' + id;
    navigator.clipboard.writeText(link).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        btn.style.background = '#d9f5e5';
        btn.style.color = '#2a7a4b';
        btn.style.borderColor = '#2a7a4b';
        btn.style.boxShadow = '2px 2px 0 #2a7a4b';
        setTimeout(function() {
            btn.innerHTML = orig;
            btn.style.background = '';
            btn.style.color = '';
            btn.style.borderColor = '';
            btn.style.boxShadow = '';
        }, 2000);
    }).catch(function() {
        // Fallback for older browsers
        window.prompt('Copy this link:', window.location.origin + '/card.php?id=' + id);
    });
}

// Filter prompt link table
function filterLinkTable(query) {
    query = query.toLowerCase();
    var rows = document.querySelectorAll('#link-table tbody tr');
    var anyVisible = false;
    rows.forEach(function(row) {
        var match = (row.dataset.search || '').includes(query);
        row.style.display = match ? '' : 'none';
        if (match) anyVisible = true;
    });
    var emptyMsg = document.getElementById('link-table-empty');
    if (emptyMsg) emptyMsg.style.display = anyVisible ? 'none' : 'block';
}
</script>
</html>
