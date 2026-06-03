<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once "db.php";

// Protect page (Admin Only)
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] =
        "You do not have permission to access the dashboard.";
    header("Location: index.php");
    exit();
}

// -- AJAX: user activity --
if (isset($_GET['xhr']) && $_GET['xhr'] === 'activity' && isset($_GET['uid'])) {
    header('Content-Type: application/json');
    try {
        $uid = (int)$_GET['uid'];
        // Fetch user � last_active may not exist yet, fallback gracefully
        try {
            $user = $pdo->prepare("SELECT id, username, email, avatar, gender, role, created_at, last_active FROM users WHERE id = ?");
            $user->execute([$uid]);
        } catch (Exception $e) {
            $user = $pdo->prepare("SELECT id, username, email, avatar, gender, role, created_at FROM users WHERE id = ?");
            $user->execute([$uid]);
        }
        $udata = $user->fetch(PDO::FETCH_ASSOC);
        if (!$udata) { echo json_encode(['ok'=>false,'error'=>'User not found']); exit; }
        if (!isset($udata['last_active'])) $udata['last_active'] = null;
        // Unlocks � order by id DESC (safe, always exists)
        $unlocks = $pdo->prepare("SELECT p.title, p.slug FROM unlocked_prompts up LEFT JOIN prompts p ON up.prompt_id = p.id WHERE up.user_id = ? ORDER BY up.id DESC");
        $unlocks->execute([$uid]);
        $unlock_list = $unlocks->fetchAll(PDO::FETCH_ASSOC);
        $saves = $pdo->prepare("SELECT COUNT(*) FROM saved_prompts WHERE user_id = ?");
        $saves->execute([$uid]);
        $saves_count = (int)$saves->fetchColumn();
        $likes = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
        $likes->execute([$uid]);
        $likes_count = (int)$likes->fetchColumn();
        echo json_encode([
            'ok'          => true,
            'user'        => $udata,
            'unlock_list' => $unlock_list,
            'saves_count' => $saves_count,
            'likes_count' => $likes_count,
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
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
$total_users_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$users = $pdo
    ->query(
        "SELECT id, username, email, avatar, gender, role, created_at FROM users ORDER BY created_at DESC LIMIT 7",
    )
    ->fetchAll(PDO::FETCH_ASSOC);

// -- Extended Analytics ------------------------------------------
// Best signup day ever
$best_day = $pdo->query("SELECT DATE(created_at) as day, COUNT(*) as cnt FROM users GROUP BY DATE(created_at) ORDER BY cnt DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Total saves
$total_saves = (int)$pdo->query("SELECT COUNT(*) FROM saved_prompts")->fetchColumn();

// Total blogs
try { $total_blogs = (int)$pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn(); } catch(Exception $e) { $total_blogs = 0; }

// Prev week comparison (users & prompts)
$prev_week_users   = (int)$pdo->query("SELECT COUNT(*) FROM users   WHERE created_at >= DATE_SUB(NOW(),INTERVAL 14 DAY) AND created_at < DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
$prev_week_prompts = (int)$pdo->query("SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 14 DAY) AND created_at < DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
$weekly_users_int  = (int)$weekly_users;
$weekly_prompts_int= (int)$weekly_prompts;
$users_trend   = $prev_week_users   > 0 ? round(($weekly_users_int   - $prev_week_users)   / $prev_week_users   * 100) : ($weekly_users_int   > 0 ? 100 : 0);
$prompts_trend = $prev_week_prompts > 0 ? round(($weekly_prompts_int - $prev_week_prompts) / $prev_week_prompts * 100) : ($weekly_prompts_int > 0 ? 100 : 0);

// Hourly signup heatmap (24 hours)
$hourly_raw  = $pdo->query("SELECT HOUR(created_at) as hr, COUNT(*) as cnt FROM users GROUP BY HOUR(created_at)")->fetchAll(PDO::FETCH_ASSOC);
$hourly_data = array_fill(0, 24, 0);
foreach ($hourly_raw as $r) $hourly_data[(int)$r['hr']] = (int)$r['cnt'];
$hourly_max  = max($hourly_data) ?: 1;

// Top 3 users by activity score
$top3_users = $pdo->query("
    SELECT u.id, u.username, u.avatar, u.gender,
        COALESCE((SELECT COUNT(*) FROM unlocked_prompts up WHERE up.user_id=u.id),0) +
        COALESCE((SELECT COUNT(*) FROM saved_prompts   sp WHERE sp.user_id=u.id),0) +
        COALESCE((SELECT COUNT(*) FROM likes           l  WHERE l.user_id=u.id), 0) as score
    FROM users u WHERE u.role='user'
    ORDER BY score DESC LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

// Ghost users (no unlocks, saves, or likes at all)
try {
    $ghost_users = $pdo->query("
        SELECT u.id, u.username, u.email, u.gender, u.created_at
        FROM users u WHERE u.role='user'
          AND NOT EXISTS (SELECT 1 FROM unlocked_prompts up WHERE up.user_id=u.id)
          AND NOT EXISTS (SELECT 1 FROM saved_prompts   sp WHERE sp.user_id=u.id)
          AND NOT EXISTS (SELECT 1 FROM likes            l  WHERE  l.user_id=u.id)
        ORDER BY u.created_at DESC LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $ghost_users = []; }

// Platform breakdown (user_agent column � may not exist yet)
try {
    $mobile_count  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE user_agent REGEXP 'Mobile|Android|iPhone'")->fetchColumn();
    $desktop_count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE user_agent IS NOT NULL AND user_agent NOT REGEXP 'Mobile|Android|iPhone'")->fetchColumn();
} catch(Exception $e) { $mobile_count = 0; $desktop_count = 0; }

// Admin greeting
$admin_info  = $pdo->query("SELECT username, gender FROM users WHERE role='admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$admin_hour  = (int)date('H');
$admin_gender = strtolower($admin_info['gender'] ?? 'male');
$admin_name  = $admin_info['username'] ?? 'Admin';
if ($admin_gender === 'female') {
    if ($admin_hour >= 5  && $admin_hour < 12) $admin_greet = "Good Morning, Sundari! <i class=\"fa-solid fa-sun\"></i> Aaj bhi site shining hai teri tarah!";
    elseif ($admin_hour >= 12 && $admin_hour < 15) $admin_greet = "Hey Beautiful! <i class=\"fa-solid fa-crown\"></i> Lunch break mein bhi admin grind? Queen hai tu!";
    elseif ($admin_hour >= 15 && $admin_hour < 18) $admin_greet = "Babe ?? Afternoon check-in sab smooth chal raha hai? ??";
    elseif ($admin_hour >= 18 && $admin_hour < 21) $admin_greet = "Hey Gorgeous! <i class=\"fa-solid fa-crown\"></i> Evening mein bhi site dekh rahi hai? Crown tujhe hi milega!";
    else                                            $admin_greet = "Late night session, Babe! <i class=\"fa-solid fa-moon\"></i> Thak gayi? Thoda rest bhi karo!";
} else {
    if ($admin_hour >= 5  && $admin_hour < 12) $admin_greet = "Good Morning, Bhai ?? Fresh start aaj kya plan hai? ??";
    elseif ($admin_hour >= 12 && $admin_hour < 15) $admin_greet = "Kya chal raha hai, King! <i class=\"fa-solid fa-crown\"></i> Lunch break admin session? Respect!";
    elseif ($admin_hour >= 15 && $admin_hour < 18) $admin_greet = "Afternoon hustle mode, Bhai ?? Site grow ho rahi hai check karo stats! ??";
    elseif ($admin_hour >= 18 && $admin_hour < 21) $admin_greet = "Evening check-in, Boss! <i class=\"fa-solid fa-chart-bar\"></i> Aaj ka kaam kaisa raha? Dekho numbers!";
    else                                            $admin_greet = "Late night grind, Bhai ?? Site ka khyal rakh raha hai respect! ??";
}

// User growth milestones
$milestone_goals = [50, 100, 250, 500, 1000, 2000, 5000, 10000];
$next_milestone = null; $prev_milestone = 0;
foreach ($milestone_goals as $g) {
    if ($total_users_count < $g) { $next_milestone = $g; break; }
    $prev_milestone = $g;
}
$milestone_pct = $next_milestone
    ? min(100, round(($total_users_count - $prev_milestone) / ($next_milestone - $prev_milestone) * 100))
    : 100;

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
    <link rel="stylesheet" href="style.min.css?v=20260601">
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
        /* Fix card text cut-off on mobile */
        .dash-card > a > div,
        .dash-card > div[style*="display:flex"] {
            flex-wrap: nowrap;
        }
        .dash-card [style*="display:flex"][style*="align-items:center"] > div:not([style*="width:64px"]):not([style*="width: 64px"]) {
            flex: 1;
            min-width: 0;
        }
        .dash-card p { word-break: break-word; }
        @media (max-width: 480px) {
            .dash-card { padding: 18px 16px; }
            .dash-card h2 { font-size: 1.15rem; }
            .dash-card p { font-size: .82rem; }
            .dash-card [style*="width:64px"], .dash-card [style*="width: 64px"] { width: 48px !important; height: 48px !important; border-radius: 14px !important; flex-shrink: 0 !important; }
            .dash-card [style*="width:64px"] i, .dash-card [style*="width: 64px"] i { font-size: 1.2rem !important; }
        }

        /* -- Greeting Bar -- */
        .greeting-bar { background:linear-gradient(135deg,var(--primary-color),var(--secondary-color)); border:var(--border-width) solid var(--text-color); border-radius:20px; padding:18px 24px; box-shadow:var(--shadow-comic); margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
        .greeting-text { font-size:1.05rem; font-weight:800; color:var(--text-color); }
        .greeting-time { font-size:.78rem; font-weight:700; color:var(--text-color); opacity:.7; }

        /* -- Live Stats Bar -- */
        .live-stats-bar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:28px; }
        .ls-pill { display:flex; align-items:center; gap:7px; background:var(--card-bg); border:var(--border-width) solid var(--text-color); border-radius:40px; padding:8px 18px; font-weight:800; font-size:.88rem; box-shadow:3px 3px 0 var(--text-color); white-space:nowrap; }
        .ls-pill .ls-num { font-size:1.05rem; font-weight:900; }

        /* -- Weekly Summary -- */
        .weekly-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:28px; }
        .wk-card { background:var(--card-bg); border:var(--border-width) solid var(--text-color); border-radius:18px; padding:18px 20px; box-shadow:var(--shadow-comic); }
        .wk-title { font-size:.7rem; font-weight:900; text-transform:uppercase; letter-spacing:.06em; color:#999; margin-bottom:6px; }
        .wk-val { font-size:1.6rem; font-weight:900; line-height:1; }
        .wk-trend { font-size:.78rem; font-weight:800; margin-top:4px; }
        .trend-up { color:#22c55e; } .trend-dn { color:#ef4444; } .trend-flat { color:#aaa; }
        @media(max-width:700px){ .weekly-row { grid-template-columns:1fr 1fr; } }

        /* -- Milestones -- */
        .milestone-bar-wrap { background:var(--card-bg); border:var(--border-width) solid var(--text-color); border-radius:18px; padding:18px 22px; box-shadow:var(--shadow-comic); margin-bottom:28px; }
        .ms-track { background:#eee; border-radius:40px; height:14px; overflow:hidden; margin:10px 0 6px; border:1.5px solid var(--text-color); }
        .ms-fill  { height:100%; border-radius:40px; background:linear-gradient(90deg,var(--primary-color),var(--secondary-color)); transition:width .5s ease; }
        .ms-labels { display:flex; justify-content:space-between; font-size:.75rem; font-weight:800; color:#aaa; }

        /* -- Hourly Heatmap -- */
        .heatmap-grid { display:grid; grid-template-columns:repeat(24,1fr); gap:4px; margin-top:14px; }
        .hm-cell { aspect-ratio:1; border-radius:5px; cursor:default; transition:transform .15s; position:relative; }
        .hm-cell:hover { transform:scale(1.3); z-index:2; }
        .hm-cell::after { content:attr(data-tip); position:absolute; bottom:130%; left:50%; transform:translateX(-50%); background:#222; color:#fff; font-size:.65rem; font-weight:700; padding:3px 7px; border-radius:6px; white-space:nowrap; pointer-events:none; opacity:0; transition:opacity .15s; }
        .hm-cell:hover::after { opacity:1; }
        .hm-labels { display:grid; grid-template-columns:repeat(24,1fr); gap:4px; margin-top:4px; }
        .hm-label  { font-size:.55rem; font-weight:700; color:#aaa; text-align:center; }
        @media(max-width:600px){ .heatmap-grid,.hm-labels { grid-template-columns:repeat(12,1fr); } }

        /* -- Platform Breakdown -- */
        .platform-bar { display:flex; height:18px; border-radius:40px; overflow:hidden; border:2px solid var(--text-color); margin:10px 0; }
        .plat-mobile  { background:#a78bfa; }
        .plat-desktop { background:#34d399; }

        /* -- Top 3 Leaderboard -- */
        .top3-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-top:14px; }
        .top3-card { border:var(--border-width) solid var(--text-color); border-radius:16px; padding:16px 14px; text-align:center; box-shadow:var(--shadow-comic); transition:transform .15s; }
        .top3-card:hover { transform:translateY(-3px); }
        .top3-rank { font-size:1.5rem; margin-bottom:6px; }
        .top3-av { width:52px; height:52px; border-radius:50%; border:3px solid var(--text-color); object-fit:cover; margin:0 auto 8px; display:block; }
        .top3-av-ph { width:52px; height:52px; border-radius:50%; border:3px solid var(--text-color); background:var(--primary-color); display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-weight:900; font-size:1.1rem; color:#fff; }
        .top3-name { font-weight:800; font-size:.88rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .top3-score { font-size:.75rem; font-weight:700; color:var(--primary-color); margin-top:3px; }
        @media(max-width:500px){ .top3-grid { grid-template-columns:1fr 1fr; } }

        /* -- Ghost Users -- */
        .ghost-row { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px dashed var(--border-color); }
        .ghost-row:last-child { border-bottom:none; }
        .ghost-av-ph { width:36px; height:36px; border-radius:50%; border:2px solid var(--text-color); background:#eee; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:.8rem; flex-shrink:0; }

        /* -- Dual section grid -- */
        .dual-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:28px; }
        @media(max-width:800px){ .dual-grid { grid-template-columns:1fr; } }
    </style>
    <link rel='preload' href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' as='style' onload='this.onload=null;this.rel="stylesheet"'>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <?php include_once "gtag.php"; ?>
</head>
<body>
    <header>
        <div class="logo-area" id="logo-container"  style="cursor:pointer;">
            <div class="logo-flipper">
                <div class="logo-front">
                    <img src="toplogo/logo01.webp" alt="Arigato Devan Logo" id="profile-logo">
                </div>
                <div class="logo-back">
                    <img loading="lazy" src="toplogo/logo02.webp" alt="Logo Alt">
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

        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:30px;">
            <div class="dash-page-title" style="margin-bottom:0;"><i class="fa-solid fa-chart-simple"></i> Admin Dashboard</div>
            <a href="404.php" target="_blank"
               style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#ff6b6b;color:#fff;border:3px solid var(--text-color);border-radius:999px;font-family:var(--font-main);font-weight:800;font-size:.82rem;text-decoration:none;box-shadow:3px 3px 0 var(--text-color);transition:transform .12s ease,box-shadow .12s ease;white-space:nowrap;"
               onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='5px 5px 0 var(--text-color)'"
               onmouseout="this.style.transform='';this.style.boxShadow='3px 3px 0 var(--text-color)'"
               onmousedown="this.style.transform='translate(2px,2px)';this.style.boxShadow='1px 1px 0 var(--text-color)'"
               onmouseup="this.style.transform='translateY(-2px)';this.style.boxShadow='5px 5px 0 var(--text-color)'">
                <i class="fa-solid fa-triangle-exclamation"></i> Preview 404 Page
            </a>
        </div>

        <!-- Greeting Bar -->
        <div class="greeting-bar">
            <div class="greeting-text"><?= $admin_greet ?></div>
            <div class="greeting-time"><?= date('D, d M Y | h:i A') ?> IST</div>
        </div>

        <!-- Live Stats Bar -->
        <div class="live-stats-bar">
            <div class="ls-pill"><span><i class="fa-solid fa-users"></i></span> <span class="ls-num"><?= $total_users_count ?></span> Users</div>
            <div class="ls-pill"><span><i class="fa-solid fa-scroll"></i></span> <span class="ls-num"><?= $total_prompts ?></span> Prompts</div>
            <div class="ls-pill"><span><i class="fa-solid fa-heart"></i></span> <span class="ls-num"><?= number_format($total_likes) ?></span> Likes</div>
            <div class="ls-pill"><span><i class="fa-solid fa-bookmark"></i></span> <span class="ls-num"><?= $total_saves ?></span> Saves</div>
            <div class="ls-pill"><span><i class="fa-solid fa-feather"></i></span> <span class="ls-num"><?= $total_blogs ?></span> Blogs</div>
        </div>

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

        <!-- Weekly Summary + Best Day + Milestones -->
        <div class="weekly-row">
            <?php
            $u_arrow = $users_trend > 0 ? '<i class="fa-solid fa-arrow-trend-up"></i>' : ($users_trend < 0 ? '<i class="fa-solid fa-arrow-trend-down"></i>' : '<i class="fa-solid fa-minus"></i>');
            $u_cls   = $users_trend > 0 ? 'trend-up' : ($users_trend < 0 ? 'trend-dn' : 'trend-flat');
            $p_arrow = $prompts_trend > 0 ? '<i class="fa-solid fa-arrow-trend-up"></i>' : ($prompts_trend < 0 ? '<i class="fa-solid fa-arrow-trend-down"></i>' : '<i class="fa-solid fa-minus"></i>');
            $p_cls   = $prompts_trend > 0 ? 'trend-up' : ($prompts_trend < 0 ? 'trend-dn' : 'trend-flat');
            ?>
            <div class="wk-card" style="background:var(--primary-color);">
                <div class="wk-title"><i class="fa-solid fa-user-plus"></i> New Users This Week</div>
                <div class="wk-val">+<?= $weekly_users_int ?></div>
                <div class="wk-trend <?= $u_cls ?>"><?= $u_arrow ?> <?= abs($users_trend) ?>% vs last week</div>
            </div>
            <div class="wk-card" style="background:var(--secondary-color);">
                <div class="wk-title"><i class="fa-solid fa-scroll"></i> Prompts This Week</div>
                <div class="wk-val">+<?= $weekly_prompts_int ?></div>
                <div class="wk-trend <?= $p_cls ?>"><?= $p_arrow ?> <?= abs($prompts_trend) ?>% vs last week</div>
            </div>
            <?php if ($best_day && $best_day['cnt'] > 0): ?>
            <div class="wk-card" style="background:#fff3cd;">
                <div class="wk-title"><i class="fa-solid fa-trophy"></i> Best Signup Day Ever</div>
                <div class="wk-val"><?= $best_day['cnt'] ?> users</div>
                <div class="wk-trend trend-flat"><?= date('d M Y', strtotime($best_day['day'])) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- User Growth Milestones -->
        <div class="milestone-bar-wrap">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                <div style="font-size:.78rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#999;"><i class="fa-solid fa-chart-line"></i> User Milestone Progress</div>
                <?php if ($next_milestone): ?>
                <div style="font-size:.85rem;font-weight:800;"><?= $total_users_count ?> / <?= $next_milestone ?> users</div>
                <?php else: ?>
                <div style="font-size:.85rem;font-weight:800;color:#22c55e;"><i class="fa-solid fa-circle-check"></i> All milestones cleared!</div>
                <?php endif; ?>
            </div>
            <div class="ms-track"><div class="ms-fill" style="width:<?= $milestone_pct ?>%;"></div></div>
            <div class="ms-labels">
                <span><?= $prev_milestone ?></span>
                <span style="font-size:.8rem;font-weight:900;color:var(--primary-color);"><?= $milestone_pct ?>%</span>
                <span><?= $next_milestone ?? '?' ?></span>
            </div>
            <?php
            $achieved = array_filter($milestone_goals, fn($g) => $total_users_count >= $g);
            if (!empty($achieved)): ?>
            <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;">
                <?php foreach ($achieved as $ag): ?>
                <span style="background:var(--secondary-color);border:1.5px solid var(--text-color);border-radius:20px;padding:3px 12px;font-size:.72rem;font-weight:900;">? <?= $ag ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Hourly Heatmap + Platform Breakdown (dual) -->
        <div class="dual-grid">
            <!-- Hourly Heatmap -->
            <div class="dash-card" style="margin-bottom:0;">
                <div style="font-size:.78rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#999;margin-bottom:4px;"><i class="fa-solid fa-clock"></i> HOURLY SIGNUP HEATMAP</div>
                <div style="font-size:.75rem;color:#aaa;font-weight:600;margin-bottom:8px;">When do users join? (hover for count)</div>
                <div class="heatmap-grid">
                    <?php foreach ($hourly_data as $hr => $cnt):
                        $intensity = $hourly_max > 0 ? $cnt / $hourly_max : 0;
                        $r = (int)(200 - $intensity * 100);
                        $g = (int)(100 + $intensity * 120);
                        $b = (int)(220 - $intensity * 150);
                        $bg = "rgb($r,$g,$b)";
                    ?>
                    <div class="hm-cell" style="background:<?= $bg ?>;border:1.5px solid rgba(0,0,0,.08);" data-tip="<?= $hr ?>:00 &mdash; <?= $cnt ?> users"></div>
                    <?php endforeach; ?>
                </div>
                <div class="hm-labels">
                    <?php for ($h=0;$h<24;$h++): ?>
                    <div class="hm-label"><?= $h%6===0 ? $h : '' ?></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Platform Breakdown -->
            <div class="dash-card" style="margin-bottom:0;">
                <div style="font-size:.78rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#999;margin-bottom:8px;"><i class="fa-solid fa-chart-pie"></i> Platform Breakdown</div>
                <?php $plat_total = $mobile_count + $desktop_count; ?>
                <?php if ($plat_total > 0): ?>
                <?php $mob_pct = round($mobile_count/$plat_total*100); $desk_pct = 100-$mob_pct; ?>
                <div class="platform-bar">
                    <div class="plat-mobile"  style="width:<?= $mob_pct ?>%;"></div>
                    <div class="plat-desktop" style="width:<?= $desk_pct ?>%;"></div>
                </div>
                <div style="display:flex;gap:14px;font-size:.82rem;font-weight:800;margin-top:8px;">
                    <span><span style="display:inline-block;width:12px;height:12px;background:#a78bfa;border-radius:3px;margin-right:5px;"></span><i class="fa-solid fa-mobile-screen"></i> Mobile <?= $mob_pct ?>% (<?= $mobile_count ?>)</span>
                    <span><span style="display:inline-block;width:12px;height:12px;background:#34d399;border-radius:3px;margin-right:5px;"></span><i class="fa-solid fa-desktop"></i> Desktop <?= $desk_pct ?>% (<?= $desktop_count ?>)</span>
                </div>
                <?php else: ?>
                <div style="color:#aaa;font-size:.85rem;font-weight:600;padding:20px 0;text-align:center;">
                    <i class="fa-solid fa-circle-info"></i> Data collection starting � will show after users log in
                </div>
                <?php endif; ?>

                <!-- Top 3 Leaderboard inside platform card -->
                <div style="margin-top:20px;border-top:2px dashed var(--border-color);padding-top:16px;">
                    <div style="font-size:.78rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#999;margin-bottom:10px;"><i class="fa-solid fa-crown"></i> Top 3 Most Active Users</div>
                    <?php if (empty($top3_users)): ?>
                    <div style="color:#aaa;font-size:.85rem;font-weight:600;text-align:center;padding:12px 0;">No activity data yet.</div>
                    <?php else: ?>
                    <div class="top3-grid" style="grid-template-columns:repeat(<?= count($top3_users) ?>,1fr);">
                        <?php $crowns = ['<i class="fa-solid fa-crown" style="color:#f59e0b;"></i>', '<i class="fa-solid fa-crown" style="color:#94a3b8;"></i>', '<i class="fa-solid fa-crown" style="color:#b45309;"></i>']; ?>
                        <?php foreach ($top3_users as $i => $tu): ?>
                        <div class="top3-card" style="background:<?= $i===0?'#fff3cd':($i===1?'#f1f5f9':'#fef6ee') ?>;">
                            <div class="top3-rank"><?= $crowns[$i] ?? ($i+1) ?></div>
                            <?php $g = strtolower($tu['gender'] ?? ''); ?>
                            <div class="top3-gender"><?= $g === 'male' ? '<i class="fa-solid fa-mars"></i>' : ($g === 'female' ? '<i class="fa-solid fa-venus"></i>' : ($g === 'nonbinary' ? '<i class="fa-solid fa-venus-mars"></i>' : '<i class="fa-solid fa-user-astronaut" title="Alien"></i>')) ?></div>
                            <?php if (!empty($tu['avatar'])): ?>
                            <img class="top3-av" src="<?= htmlspecialchars($tu['avatar']) ?>" alt="">
                            <?php else: ?>
                            <div class="top3-av-ph"><?= strtoupper(substr($tu['username']??'U',0,1)) ?></div>
                            <?php endif; ?>
                            <div class="top3-name"><?= htmlspecialchars($tu['username']??'User') ?></div>
                            <div class="top3-score"><?= $tu['score'] ?> pts</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ghost Users -->
        <?php if (!empty($ghost_users)): ?>
        <div class="dash-card" style="margin-bottom:28px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:16px;border-bottom:2px dashed var(--border-color);padding-bottom:14px;">
                <div style="font-size:1.1rem;font-weight:900;display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-ghost"></i> Ghost Users <span style="font-size:.72rem;background:#ffe3e3;border:1.5px solid #d03030;color:#d03030;border-radius:20px;padding:2px 10px;font-weight:900;"><?= count($ghost_users) ?>+ joined, never interacted</span></div>
                <a href="user_management.php" style="font-size:.78rem;font-weight:800;color:var(--primary-color);text-decoration:none;">View All ?</a>
            </div>
            <?php foreach ($ghost_users as $gu): ?>
            <div class="ghost-row">
                <div class="ghost-av-ph"><?= strtoupper(substr($gu['username']??'U',0,1)) ?></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:800;font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($gu['username']??'User') ?></div>
                    <div style="font-size:.72rem;color:#aaa;font-weight:600;"><?= htmlspecialchars($gu['email']??'') ?></div>
                </div>
                <div style="font-size:.72rem;font-weight:700;color:#bbb;white-space:nowrap;"><?= date('d M Y', strtotime($gu['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

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
                            <td style="padding:9px 12px;"><img loading="lazy" src="<?= $limg ?>" style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:2px solid var(--text-color);display:block;"></td>
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
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="badge" style="margin:0;transform:rotate(0);background:var(--secondary-color);padding:6px 12px;width:fit-content;flex-shrink:0;font-size:.82rem;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $total_users_count ?> Users</div>
                    <a href="user_management.php" style="background:var(--primary-color);border:2px solid var(--text-color);border-radius:12px;padding:7px 16px;font-family:var(--font-main);font-weight:800;font-size:.82rem;text-decoration:none;color:var(--text-color);box-shadow:2px 2px 0 var(--text-color);white-space:nowrap;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Full Page</a>
                </div>
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
                <thead><tr><th>Avatar</th><th>Name / Email</th><th>Gender</th><th>Role</th><th>Joined</th><th>Activity</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                <td><?php $u_avatar = !empty($u["avatar"])
                    ? $u["avatar"]
                    : "https://api.dicebear.com/7.x/avataaars/svg?seed=" .
                        urlencode(
                            $u["email"] ?? "x",
                        ); ?><img loading="lazy" src="<?= htmlspecialchars(
    $u_avatar,
) ?>" class="user-avatar-sm" alt=""></td>
                    <td><div style="font-weight:800;font-size:.95rem;"><?= htmlspecialchars(
                        $u["username"] ?? "&mdash;",
                    ) ?></div><div style="font-size:.8rem;color:#7D7887;font-weight:600;"><?= htmlspecialchars(
    $u["email"] ?? "",
) ?></div></td>
                    <td><?= empty($u["gender"]) ? '<i class="fa-solid fa-user-astronaut"></i> Alien' : htmlspecialchars(ucfirst($u["gender"])) ?></td>
                    <td><span class="role-badge <?= $u["role"] === "admin"
                        ? "role-admin"
                        : "role-user" ?>"><?= htmlspecialchars(
    strtoupper($u["role"] ?? "user"),
) ?></span></td>
                    <?php
                    $joined_dt = new DateTime($u['created_at'], new DateTimeZone('UTC'));
                    $joined_dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
                    ?>
                    <td style="font-size:.82rem;color:#7D7887;font-weight:600;"><?= $joined_dt->format('d M Y') ?><br><span style="font-size:.75rem;color:#aaa;"><?= $joined_dt->format('h:i A') ?></span></td>
                    <td><button onclick="openActivity(<?= (int)$u['id'] ?>)" style="background:var(--primary-color);border:2px solid var(--text-color);border-radius:10px;padding:7px 14px;font-family:var(--font-main);font-weight:800;font-size:.78rem;cursor:pointer;box-shadow:2px 2px 0 var(--text-color);white-space:nowrap;transition:all .15s;"><i class="fa-solid fa-chart-simple"></i> See Activity</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
            <div style="text-align:center;padding:18px 16px;border-top:2px dashed var(--border-color);">
                <a href="user_management.php" style="display:inline-flex;align-items:center;gap:8px;background:var(--primary-color);border:2px solid var(--text-color);border-radius:14px;padding:11px 28px;font-family:var(--font-main);font-weight:800;font-size:.92rem;text-decoration:none;color:var(--text-color);box-shadow:3px 3px 0 var(--text-color);transition:all .15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='4px 4px 0 var(--text-color)'" onmouseout="this.style.transform='';this.style.boxShadow='3px 3px 0 var(--text-color)'"><i class="fa-solid fa-users"></i> View All <?= $total_users_count ?> Users &rarr;</a>
            </div>
        </div>
    </div>

    <!-- User Activity Modal -->
    <div id="activity-modal" style="display:none;position:fixed;inset:0;background:rgba(45,42,53,.5);backdrop-filter:blur(8px);z-index:2000;align-items:center;justify-content:center;padding:20px;" onclick="if(event.target===this)closeActivity()">
        <div style="background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:32px;max-width:520px;width:100%;box-shadow:8px 8px 0 var(--text-color);max-height:88vh;overflow-y:auto;position:relative;">
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
                        <div id="act-last-active" style="font-size:1rem;font-weight:900;color:#7c3aed;"></div>
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
                <div id="act-unlock-list-wrap">
                    <div style="font-size:.78rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:10px;">&#128274; Unlocked Prompts</div>
                    <div id="act-unlock-list" style="display:flex;flex-direction:column;gap:6px;max-height:200px;overflow-y:auto;"></div>
                </div>
            </div>
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

// User Activity Modal
function openActivity(uid) {
    const modal = document.getElementById('activity-modal');
    modal.style.display = 'flex';
    document.getElementById('activity-loading').style.display = 'block';
    document.getElementById('activity-content').style.display = 'none';
    fetch('dashboard.php?xhr=activity&uid=' + uid)
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;
            const u = data.user;
            const av = u.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=' + encodeURIComponent(u.email || 'x');
            document.getElementById('act-avatar').src = av;
            document.getElementById('act-name').textContent = u.username || '�';
            document.getElementById('act-email').textContent = u.email || '�';
            const la = u.last_active ? new Date(u.last_active.replace(' ', 'T') + 'Z') : null;
            document.getElementById('act-last-active').textContent = la ? la.toLocaleString('en-IN', {timeZone:'Asia/Kolkata',day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : 'Never';
            document.getElementById('act-unlocks').textContent = data.unlock_list.length;
            document.getElementById('act-saves').textContent = data.saves_count;
            document.getElementById('act-likes').textContent = data.likes_count;
            const list = document.getElementById('act-unlock-list');
            list.innerHTML = '';
            if (data.unlock_list.length === 0) {
                list.innerHTML = '<div style="color:#aaa;font-size:.82rem;font-weight:600;">No prompts unlocked yet.</div>';
            } else {
                data.unlock_list.forEach(p => {
                    const d = document.createElement('div');
                    d.style.cssText = 'background:var(--bg-color);border:1.5px solid var(--border-color);border-radius:8px;padding:7px 12px;font-size:.82rem;font-weight:700;';
                    d.textContent = '↗ ' + (p.title || '�');
                    list.appendChild(d);
                });
            }
            document.getElementById('activity-loading').style.display = 'none';
            document.getElementById('activity-content').style.display = 'block';
        });
}
function closeActivity() {
    document.getElementById('activity-modal').style.display = 'none';
}

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
