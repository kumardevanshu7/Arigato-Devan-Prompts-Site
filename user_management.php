<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php"); exit();
}

// ── AJAX: user activity ──
if (isset($_GET['xhr']) && $_GET['xhr'] === 'activity' && isset($_GET['uid'])) {
    header('Content-Type: application/json');
    try {
        $uid = (int)$_GET['uid'];
        try {
            $user = $pdo->prepare("SELECT id, username, email, avatar, gender, role, created_at, last_active FROM users WHERE id = ?");
            $user->execute([$uid]);
        } catch (Exception $e) {
            $user = $pdo->prepare("SELECT id, username, email, avatar, gender, role, created_at FROM users WHERE id = ?");
            $user->execute([$uid]);
        }
        $udata = $user->fetch(PDO::FETCH_ASSOC);
        if (!$udata) { echo json_encode(['ok'=>false]); exit; }
        if (!isset($udata['last_active'])) $udata['last_active'] = null;
        $unlocks = $pdo->prepare("SELECT p.title, p.slug FROM unlocked_prompts up LEFT JOIN prompts p ON up.prompt_id = p.id WHERE up.user_id = ? ORDER BY up.id DESC");
        $unlocks->execute([$uid]);
        $unlock_list = $unlocks->fetchAll(PDO::FETCH_ASSOC);
        $saves = $pdo->prepare("SELECT COUNT(*) FROM saved_prompts WHERE user_id = ?");
        $saves->execute([$uid]); $saves_count = (int)$saves->fetchColumn();
        $likes = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
        $likes->execute([$uid]); $likes_count = (int)$likes->fetchColumn();
        echo json_encode(['ok'=>true,'user'=>$udata,'unlock_list'=>$unlock_list,'saves_count'=>$saves_count,'likes_count'=>$likes_count]);
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

$users = $pdo->query("SELECT id, username, email, avatar, gender, role, created_at, last_active FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// ── Growth chart: last 30 days (IST) ──
$growth_raw = $pdo->query("
    SELECT DATE(CONVERT_TZ(created_at,'+00:00','+05:30')) as d, COUNT(*) as cnt
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY d ORDER BY d ASC
")->fetchAll(PDO::FETCH_ASSOC);
$growth_labels = [];
$growth_data   = [];
$growth_map    = [];
foreach ($growth_raw as $row) { $growth_map[$row['d']] = (int)$row['cnt']; }
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $growth_labels[] = date('d M', strtotime($date));
    $growth_data[]   = $growth_map[$date] ?? 0;
}

// ── Top 10 users by unlocks ──
try {
    $top_users = $pdo->query("
        SELECT u.id, u.username, u.email, u.avatar,
               COUNT(up.id) as unlock_count
        FROM users u
        LEFT JOIN unlocked_prompts up ON u.id = up.user_id
        WHERE u.role = 'user'
        GROUP BY u.id
        ORDER BY unlock_count DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $top_users = []; }
$total = count($users);
$total_male   = count(array_filter($users, fn($u) => strtolower($u['gender'] ?? '') === 'male'));
$total_female = count(array_filter($users, fn($u) => strtolower($u['gender'] ?? '') === 'female'));
$total_admin  = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'admin'));
// New today
$new_today = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(CONVERT_TZ(created_at,'+00:00','+05:30')) = CURDATE()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management — Admin</title>
    <link rel="stylesheet" href="style.css?v=2026052201">

    <style>
        body { background: var(--bg-color); }
        .um-wrap { max-width: 1100px; margin: 0 auto; padding: 32px 28px 100px; }

        /* Stats */
        .um-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 28px; }
        .um-stat-card { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 18px; padding: 18px 20px; box-shadow: var(--shadow-comic); display: flex; flex-direction: column; gap: 4px; }
        .um-stat-num  { font-size: 2rem; font-weight: 900; line-height: 1; }
        .um-stat-label{ font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #888; }

        /* Search + filter bar */
        .um-bar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .um-search { flex: 1; min-width: 220px; padding: 13px 18px; border: var(--border-width) solid var(--text-color); border-radius: 14px; font-family: var(--font-main); font-weight: 600; font-size: 1rem; background: var(--card-bg); color: var(--text-color); outline: none; box-shadow: var(--shadow-comic); transition: all .2s; box-sizing: border-box; }
        .um-search:focus { box-shadow: var(--shadow-comic-hover); transform: translateY(-1px); }
        .um-filter { padding: 13px 18px; border: var(--border-width) solid var(--text-color); border-radius: 14px; font-family: var(--font-main); font-weight: 700; font-size: .92rem; background: var(--card-bg); color: var(--text-color); outline: none; box-shadow: var(--shadow-comic); cursor: pointer; }

        /* Table card */
        .um-card { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 24px; box-shadow: var(--shadow-comic); overflow: clip; }
        .um-table { width: 100%; border-collapse: collapse; }
        .um-table thead tr { border-bottom: 2px solid var(--text-color); background: var(--bg-color); }
        .um-table th { padding: 14px 16px; font-size: .72rem; font-weight: 900; text-transform: uppercase; letter-spacing: .6px; text-align: left; }
        .um-table tbody tr { border-bottom: 1px solid var(--border-color); transition: background .15s; }
        .um-table tbody tr:last-child { border-bottom: none; }
        .um-table tbody tr:hover { background: var(--bg-color); }
        .um-table td { padding: 12px 16px; vertical-align: middle; }

        .um-avatar { width: 46px; height: 46px; border-radius: 50%; border: 2.5px solid var(--text-color); object-fit: cover; display: block; }
        .um-name   { font-weight: 800; font-size: .95rem; }
        .um-email  { font-size: .78rem; color: #888; font-weight: 600; margin-top: 2px; }
        .um-num    { font-weight: 900; font-size: .85rem; color: #bbb; text-align: center; }

        .role-pill { border-radius: 20px; padding: 4px 12px; font-size: .72rem; font-weight: 900; border: 2px solid currentColor; white-space: nowrap; }
        .role-admin{ background: #ffe3e3; color: #d03030; }
        .role-user { background: #f0eeff; color: #5b21b6; }

        .last-active-badge { font-size: .75rem; font-weight: 700; color: #22c55e; }
        .last-active-never { color: #ccc; }

        .act-btn { background: var(--primary-color); border: 2px solid var(--text-color); border-radius: 10px; padding: 7px 14px; font-family: var(--font-main); font-weight: 800; font-size: .78rem; cursor: pointer; box-shadow: 2px 2px 0 var(--text-color); white-space: nowrap; transition: all .15s; color: var(--text-color); }
        .act-btn:hover { transform: translateY(-1px); box-shadow: 3px 3px 0 var(--text-color); }

        /* Pagination */
        .um-pagination { display: flex; align-items: center; justify-content: center; gap: 7px; padding: 18px 16px; border-top: 2px solid var(--border-color); flex-wrap: wrap; }
        .pg-btn { background: var(--card-bg); border: 2px solid var(--text-color); border-radius: 10px; padding: 8px 14px; font-family: var(--font-main); font-weight: 800; font-size: .85rem; cursor: pointer; box-shadow: 2px 2px 0 var(--text-color); transition: all .15s; color: var(--text-color); }
        .pg-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 3px 3px 0 var(--text-color); }
        .pg-btn:disabled { opacity: .35; cursor: default; box-shadow: none; }
        .pg-btn.pg-active { background: var(--primary-color); }
        .pg-dots { font-weight: 800; color: #aaa; padding: 0 2px; }
        .pg-info  { font-size: .78rem; font-weight: 700; color: #999; margin-left: 6px; }

        .um-empty { text-align: center; color: #7D7887; font-weight: 600; padding: 40px 0; display: none; }

        /* Activity Modal */
        #activity-modal { display:none; position:fixed; inset:0; background:rgba(45,42,53,.5); backdrop-filter:blur(8px); z-index:2000; align-items:center; justify-content:center; padding:20px; }
        .act-modal-box { background:var(--card-bg); border:var(--border-width) solid var(--text-color); border-radius:24px; padding:32px; max-width:520px; width:100%; box-shadow:8px 8px 0 var(--text-color); max-height:88vh; overflow-y:auto; position:relative; }

        /* Growth chart + top users */
        .um-analytics { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:28px; }
        .um-chart-card { background:var(--card-bg); border:var(--border-width) solid var(--text-color); border-radius:20px; padding:22px; box-shadow:var(--shadow-comic); }
        .um-chart-title { font-size:.78rem; font-weight:900; text-transform:uppercase; letter-spacing:.07em; color:#888; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
        .um-chart-title i { color:var(--primary-color); font-size:1rem; }
        .top-user-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px dashed var(--border-color,#eee); }
        .top-user-row:last-child { border-bottom:none; }
        .top-rank { font-size:.82rem; font-weight:900; color:#aaa; width:22px; text-align:center; flex-shrink:0; }
        .top-rank.gold { color:#f59e0b; }
        .top-rank.silver { color:#94a3b8; }
        .top-rank.bronze { color:#b45309; }
        .top-user-av { width:34px; height:34px; border-radius:50%; border:2px solid var(--text-color); object-fit:cover; flex-shrink:0; }
        .top-user-av.placeholder { background:var(--primary-color); display:flex; align-items:center; justify-content:center; font-weight:900; font-size:.82rem; color:#fff; }
        .top-user-info { flex:1; min-width:0; }
        .top-user-name { font-weight:800; font-size:.85rem; color:var(--text-color); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .top-user-cnt { font-size:.72rem; font-weight:700; color:var(--primary-color); }
        @media (max-width:700px) { .um-analytics { grid-template-columns:1fr; } }

        @media (max-width: 700px) {
            .um-stats { grid-template-columns: 1fr 1fr; }
            .um-wrap  { padding: 20px 14px 80px; }
            /* Hide: Gender(4), Role(5), Joined(6), Last Active(7) — keep #, Avatar, Name, Activity */
            .um-table th:nth-child(4), .um-table td:nth-child(4),
            .um-table th:nth-child(5), .um-table td:nth-child(5),
            .um-table th:nth-child(6), .um-table td:nth-child(6),
            .um-table th:nth-child(7), .um-table td:nth-child(7) { display: none; }
            .um-card { overflow-x: auto; }
            .um-table th, .um-table td { padding: 10px 10px; }
            .um-name { font-size: .85rem; }
            .um-email { font-size: .7rem; }
            .um-avatar { width: 36px; height: 36px; }
        }
    </style>
    <?php include_once "gtag.php"; ?>
</head>
<body>
<header>
    <div class="logo-area" style="cursor:pointer">
        <div class="logo-flipper">
            <div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo"></div>
            <div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div>
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
            <span style="font-weight:800;">ADMIN</span>
        </div>
        <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
    </div>
</header>

<div class="um-wrap">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
        <div style="font-size:2rem;font-weight:900;display:flex;align-items:center;gap:12px;">
            <i class="fa-solid fa-users" style="color:var(--primary-color);"></i> User Management
        </div>
        <div class="badge" style="margin:0;transform:rotate(0);background:var(--secondary-color);padding:8px 20px;font-size:1rem;"><?= $total ?> Users</div>
    </div>

    <!-- Stats -->
    <div class="um-stats">
        <div class="um-stat-card">
            <div class="um-stat-num" style="color:var(--primary-color);"><?= $total ?></div>
            <div class="um-stat-label">Total Users</div>
        </div>
        <div class="um-stat-card">
            <div class="um-stat-num" style="color:#22c55e;"><?= $new_today ?></div>
            <div class="um-stat-label">Joined Today</div>
        </div>
        <div class="um-stat-card">
            <div class="um-stat-num" style="color:#3b82f6;"><?= $total_male ?></div>
            <div class="um-stat-label">Male</div>
        </div>
        <div class="um-stat-card">
            <div class="um-stat-num" style="color:#f43f5e;"><?= $total_female ?></div>
            <div class="um-stat-label">Female</div>
        </div>
    </div>

    <!-- Growth Chart + Top Users -->
    <div class="um-analytics">
        <div class="um-chart-card">
            <div class="um-chart-title"><i class="fa-solid fa-chart-line"></i> User Growth — Last 30 Days</div>
            <canvas id="growthChart" height="140"></canvas>
        </div>
        <div class="um-chart-card">
            <div class="um-chart-title"><i class="fa-solid fa-trophy"></i> Top Users by Unlocks</div>
            <?php if (empty($top_users)): ?>
                <p style="color:#aaa;font-size:.85rem;font-weight:600;text-align:center;padding:20px 0;">No unlock data yet.</p>
            <?php else: ?>
            <?php foreach ($top_users as $i => $tu): ?>
            <div class="top-user-row">
                <div class="top-rank <?= $i===0?'gold':($i===1?'silver':($i===2?'bronze':'')) ?>"><?= $i+1 ?></div>
                <?php if (!empty($tu['avatar']) && filter_var($tu['avatar'], FILTER_VALIDATE_URL)): ?>
                    <img loading="lazy" class="top-user-av" src="<?= htmlspecialchars($tu['avatar']) ?>" alt="">
                <?php else: ?>
                    <div class="top-user-av placeholder"><?= strtoupper(substr($tu['username']??'U',0,1)) ?></div>
                <?php endif; ?>
                <div class="top-user-info">
                    <div class="top-user-name"><?= htmlspecialchars($tu['username'] ?? 'User') ?></div>
                    <div class="top-user-cnt"><?= $tu['unlock_count'] ?> unlock<?= $tu['unlock_count']!=1?'s':'' ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search + Filter -->
    <div class="um-bar">
        <input type="text" class="um-search" id="um-search" placeholder="&#128269;  Search by name or email..." oninput="filterUsers(this.value)">
        <select class="um-filter" id="um-role-filter" onchange="filterUsers(document.getElementById('um-search').value)">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="user">User</option>
        </select>
        <select class="um-filter" id="um-gender-filter" onchange="filterUsers(document.getElementById('um-search').value)">
            <option value="">All Genders</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
        </select>
    </div>

    <!-- Table -->
    <div class="um-card">
        <div style="overflow-x:auto;">
        <table class="um-table" id="um-table">
            <thead>
                <tr>
                    <th style="width:36px;text-align:center;">#</th>
                    <th style="width:56px;">Avatar</th>
                    <th>Name / Email</th>
                    <th style="width:90px;">Gender</th>
                    <th style="width:90px;">Role</th>
                    <th style="width:130px;">Joined</th>
                    <th style="width:130px;">Last Active</th>
                    <th style="text-align:right;">Activity</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $i => $u):
                $u_avatar = !empty($u['avatar']) ? $u['avatar'] : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($u['email'] ?? 'x');
                $joined_dt = new DateTime($u['created_at'], new DateTimeZone('UTC'));
                $joined_dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
                $la_display = '';
                if (!empty($u['last_active'])) {
                    $la_dt = new DateTime($u['last_active'], new DateTimeZone('UTC'));
                    $la_dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
                    $la_display = $la_dt->format('d M Y, h:i A');
                }
                $search_val = strtolower(($u['username'] ?? '') . ' ' . ($u['email'] ?? ''));
            ?>
            <tr data-search="<?= htmlspecialchars($search_val) ?>" data-role="<?= htmlspecialchars($u['role'] ?? 'user') ?>" data-gender="<?= strtolower(htmlspecialchars($u['gender'] ?? '')) ?>">
                <td class="um-num"><?= $total - $i ?></td>
                <td><img loading="lazy" src="<?= htmlspecialchars($u_avatar) ?>" class="um-avatar" alt=""></td>
                <td>
                    <div class="um-name"><?= htmlspecialchars($u['username'] ?? '—') ?></div>
                    <div class="um-email"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                </td>
                <td style="font-weight:700;font-size:.88rem;"><?= htmlspecialchars(ucfirst($u['gender'] ?? '—')) ?></td>
                <td><span class="role-pill <?= ($u['role'] ?? '') === 'admin' ? 'role-admin' : 'role-user' ?>"><?= strtoupper($u['role'] ?? 'user') ?></span></td>
                <td style="font-size:.82rem;font-weight:700;color:#7D7887;">
                    <?= $joined_dt->format('d M Y') ?><br>
                    <span style="font-size:.75rem;color:#aaa;"><?= $joined_dt->format('h:i A') ?></span>
                </td>
                <td style="font-size:.78rem;font-weight:700;">
                    <?php if ($la_display): ?>
                        <span class="last-active-badge"><i class="fa-solid fa-circle" style="font-size:.5rem;margin-right:4px;"></i><?= $la_display ?></span>
                    <?php else: ?>
                        <span class="last-active-never">—</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;">
                    <button class="act-btn" onclick="openActivity(<?= (int)$u['id'] ?>)">
                        <i class="fa-solid fa-chart-simple"></i> See Activity
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <p class="um-empty" id="um-empty">No users match your search.</p>
        <div class="um-pagination" id="um-pagination"></div>
    </div>

</div>

<!-- Activity Modal -->
<div id="activity-modal" onclick="if(event.target===this)closeActivity()">
    <div class="act-modal-box">
        <button onclick="closeActivity()" style="position:absolute;top:16px;right:16px;background:var(--bg-color);border:2px solid var(--text-color);border-radius:50%;width:34px;height:34px;font-size:1rem;cursor:pointer;font-family:var(--font-main);font-weight:800;">&#10005;</button>
        <div id="activity-loading" style="text-align:center;padding:40px 0;font-weight:700;color:#888;"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
        <div id="activity-content" style="display:none;">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:18px;border-bottom:2px dashed var(--border-color);">
                <img loading="lazy" id="act-avatar" src="" style="width:56px;height:56px;border-radius:50%;border:3px solid var(--text-color);object-fit:cover;" alt="">
                <div>
                    <div id="act-name" style="font-size:1.15rem;font-weight:900;"></div>
                    <div id="act-email" style="font-size:.82rem;color:#7D7887;font-weight:600;"></div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:22px;">
                <div style="background:#f8f4ff;border:2px solid #c084fc;border-radius:14px;padding:14px;text-align:center;">
                    <div id="act-last-active" style="font-size:.95rem;font-weight:900;color:#7c3aed;"></div>
                    <div style="font-size:.72rem;font-weight:800;color:#888;margin-top:4px;text-transform:uppercase;">Last Active</div>
                </div>
                <div style="background:#fffbeb;border:2px solid #f59e0b;border-radius:14px;padding:14px;text-align:center;">
                    <div id="act-unlocks" style="font-size:1.6rem;font-weight:900;color:#b45309;"></div>
                    <div style="font-size:.72rem;font-weight:800;color:#888;margin-top:4px;text-transform:uppercase;">Prompts Unlocked</div>
                </div>
                <div style="background:#f0fdf4;border:2px solid #22c55e;border-radius:14px;padding:14px;text-align:center;">
                    <div id="act-saves" style="font-size:1.6rem;font-weight:900;color:#15803d;"></div>
                    <div style="font-size:.72rem;font-weight:800;color:#888;margin-top:4px;text-transform:uppercase;">Prompts Saved</div>
                </div>
                <div style="background:#fff1f2;border:2px solid #f43f5e;border-radius:14px;padding:14px;text-align:center;">
                    <div id="act-likes" style="font-size:1.6rem;font-weight:900;color:#be123c;"></div>
                    <div style="font-size:.72rem;font-weight:800;color:#888;margin-top:4px;text-transform:uppercase;">Prompts Liked</div>
                </div>
            </div>
            <div>
                <div style="font-size:.78rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:10px;">&#128274; Unlocked Prompts</div>
                <div id="act-unlock-list" style="display:flex;flex-direction:column;gap:6px;max-height:200px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>
</div>

<script>
const PER_PAGE = 12;
var currentPage = 1;
var searchQuery = '';
var roleFilter   = '';
var genderFilter = '';

function getAllRows() { return Array.from(document.querySelectorAll('#um-table tbody tr')); }
function getFilteredRows() {
    return getAllRows().filter(function(row) {
        var matchSearch = !searchQuery || (row.dataset.search || '').includes(searchQuery);
        var matchRole   = !roleFilter   || (row.dataset.role   || '') === roleFilter;
        var matchGender = !genderFilter || (row.dataset.gender || '') === genderFilter;
        return matchSearch && matchRole && matchGender;
    });
}
function renderPage(page) {
    var filtered   = getFilteredRows();
    var totalPages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
    currentPage    = Math.min(Math.max(1, page), totalPages);
    var start      = (currentPage - 1) * PER_PAGE;
    getAllRows().forEach(function(r){ r.style.display='none'; });
    filtered.slice(start, start + PER_PAGE).forEach(function(r){ r.style.display=''; });
    document.getElementById('um-empty').style.display = filtered.length === 0 ? 'block' : 'none';
    renderPagination(currentPage, totalPages, filtered.length);
}
function renderPagination(page, totalPages, totalRows) {
    var el = document.getElementById('um-pagination');
    if (totalPages <= 1) { el.style.display='none'; return; }
    el.style.display = 'flex';
    var html = '<button class="pg-btn" onclick="renderPage('+(page-1)+')"'+(page===1?' disabled':'')+'>&#8592;</button>';
    for (var i = 1; i <= totalPages; i++) {
        if (i===1||i===totalPages||Math.abs(i-page)<=1) {
            html += '<button class="pg-btn'+(i===page?' pg-active':'')+'" onclick="renderPage('+i+')">'+i+'</button>';
        } else if (Math.abs(i-page)===2) { html += '<span class="pg-dots">&hellip;</span>'; }
    }
    html += '<button class="pg-btn" onclick="renderPage('+(page+1)+')"'+(page===totalPages?' disabled':'')+'>&#8594;</button>';
    html += '<span class="pg-info">Showing '+((page-1)*PER_PAGE+1)+'&ndash;'+Math.min(page*PER_PAGE,totalRows)+' of '+totalRows+'</span>';
    el.innerHTML = html;
}
function filterUsers(query) {
    searchQuery  = query.toLowerCase().trim();
    roleFilter   = document.getElementById('um-role-filter').value.toLowerCase();
    genderFilter = document.getElementById('um-gender-filter').value.toLowerCase();
    renderPage(1);
}

// Activity Modal
function openActivity(uid) {
    var modal = document.getElementById('activity-modal');
    modal.style.display = 'flex';
    document.getElementById('activity-loading').style.display = 'block';
    document.getElementById('activity-content').style.display = 'none';
    fetch('user_management.php?xhr=activity&uid=' + uid)
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.ok) return;
            var u = data.user;
            var av = u.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=' + encodeURIComponent(u.email || 'x');
            document.getElementById('act-avatar').src = av;
            document.getElementById('act-name').textContent  = u.username || '—';
            document.getElementById('act-email').textContent = u.email    || '—';
            var la = u.last_active ? new Date(u.last_active.replace(' ','T')+'Z') : null;
            document.getElementById('act-last-active').textContent = la ? la.toLocaleString('en-IN',{timeZone:'Asia/Kolkata',day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : 'Never';
            document.getElementById('act-unlocks').textContent = data.unlock_list.length;
            document.getElementById('act-saves').textContent   = data.saves_count;
            document.getElementById('act-likes').textContent   = data.likes_count;
            var list = document.getElementById('act-unlock-list');
            list.innerHTML = '';
            if (data.unlock_list.length === 0) {
                list.innerHTML = '<div style="color:#aaa;font-size:.82rem;font-weight:600;">No prompts unlocked yet.</div>';
            } else {
                data.unlock_list.forEach(function(p) {
                    var d = document.createElement('div');
                    d.style.cssText = 'background:var(--bg-color);border:1.5px solid var(--border-color);border-radius:8px;padding:7px 12px;font-size:.82rem;font-weight:700;';
                    d.textContent = '🔓 ' + (p.title || '—');
                    list.appendChild(d);
                });
            }
            document.getElementById('activity-loading').style.display = 'none';
            document.getElementById('activity-content').style.display = 'block';
        });
}
function closeActivity() { document.getElementById('activity-modal').style.display='none'; }

document.addEventListener('DOMContentLoaded', function(){ renderPage(1); });
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    var labels = <?= json_encode($growth_labels) ?>;
    var data   = <?= json_encode($growth_data) ?>;
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor  = isDark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
    var labelColor = isDark ? '#aaa' : '#888';
    new Chart(document.getElementById('growthChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'New Users',
                data: data,
                backgroundColor: 'rgba(192,132,252,.7)',
                borderColor: '#c084fc',
                borderWidth: 2,
                borderRadius: 6,
                hoverBackgroundColor: '#c084fc'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function(ctx){ return ctx.parsed.y + ' users'; } } }
            },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: labelColor, font: { weight:'700', size:10 }, maxTicksLimit: 8, maxRotation: 0 } },
                y: { grid: { color: gridColor }, ticks: { color: labelColor, font: { weight:'700', size:10 }, precision: 0 }, beginAtZero: true }
            }
        }
    });
})();
</script>
</body>
</html>
