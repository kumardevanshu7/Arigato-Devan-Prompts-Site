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

// Ghost users preview (only 3 for dashboard)
try {
    $ghost_total = (int)$pdo->query("
        SELECT COUNT(*) FROM users u WHERE u.role='user'
          AND NOT EXISTS (SELECT 1 FROM unlocked_prompts up WHERE up.user_id=u.id)
          AND NOT EXISTS (SELECT 1 FROM saved_prompts   sp WHERE sp.user_id=u.id)
          AND NOT EXISTS (SELECT 1 FROM likes            l  WHERE  l.user_id=u.id)
    ")->fetchColumn();
    $ghost_users = $pdo->query("
        SELECT u.id, u.username, u.email, u.created_at
        FROM users u WHERE u.role='user'
          AND NOT EXISTS (SELECT 1 FROM unlocked_prompts up WHERE up.user_id=u.id)
          AND NOT EXISTS (SELECT 1 FROM saved_prompts   sp WHERE sp.user_id=u.id)
          AND NOT EXISTS (SELECT 1 FROM likes            l  WHERE  l.user_id=u.id)
        ORDER BY u.created_at DESC LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $ghost_users = []; $ghost_total = 0; }

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
<title>Admin Dashboard — Arigato Devan</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include_once "gtag.php"; ?>
<style>
:root{
  --bg:#07060f;--surface:#0f0d1e;--surface2:#15122a;
  --border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);
  --accent:#8b5cf6;--accent2:#c084fc;
  --pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;
  --yellow:#fbbf24;--orange:#fb923c;--red:#f87171;
  --text:#e2e0ff;--muted:#9490bb;
  --font:'Inter',sans-serif;--mono:'JetBrains Mono',monospace;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:var(--font);overflow-x:hidden;min-height:100vh}
#sp{position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,var(--accent),var(--pink),var(--cyan));z-index:9999;transition:width .1s;box-shadow:0 0 10px var(--accent)}
#pc{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.6}
/* SIDEBAR */
.sidebar{position:fixed;left:0;top:0;bottom:0;width:220px;background:rgba(7,6,15,0.98);border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column}
.sb-logo{padding:20px 18px 14px;border-bottom:1px solid var(--border2)}
.sb-brand{font-size:.72rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:flex;align-items:center;gap:8px}
.sb-brand i{-webkit-text-fill-color:#a78bfa;font-size:1rem}
.sb-admin{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border2)}
.sb-av{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--accent);flex-shrink:0}
.sb-av-ph{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;color:#fff;flex-shrink:0}
.sb-uname{font-size:.78rem;font-weight:800;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sb-role{font-size:.6rem;font-weight:700;color:var(--accent2);text-transform:uppercase;letter-spacing:.1em}
.sb-nav{flex:1;overflow-y:auto;padding:10px 8px}
.sb-nav::-webkit-scrollbar{width:2px}
.sb-nav::-webkit-scrollbar-thumb{background:var(--accent);border-radius:10px}
.sb-sec{font-size:.58rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 10px 5px}
.sb-link{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;border:1px solid transparent;margin-bottom:1px}
.sb-link:hover{background:rgba(139,92,246,0.08);color:var(--text)}
.sb-link.active{background:rgba(139,92,246,0.15);color:var(--accent2);border-color:var(--border)}
.sb-link i{width:16px;text-align:center;flex-shrink:0}
.sb-bottom{padding:12px 8px;border-top:1px solid var(--border2)}
.sb-logout{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:700;color:var(--red);text-decoration:none;transition:all .2s}
.sb-logout:hover{background:rgba(248,113,113,0.1)}
/* MAIN */
.main{margin-left:220px;min-height:100vh;padding:28px 32px 80px;position:relative;z-index:1;max-width:1300px}
/* TOPBAR */
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:24px;flex-wrap:wrap}
.tb-title{font-size:1.5rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1;display:flex;align-items:center;gap:10px}
.tb-title i{-webkit-text-fill-color:var(--accent2);font-size:1.3rem}
.tb-time{font-size:.72rem;font-weight:700;color:var(--muted);background:rgba(15,13,30,0.8);border:1px solid var(--border2);padding:6px 14px;border-radius:100px}
.tb-btn{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;border:1px solid;transition:all .2s;cursor:pointer;font-family:var(--font)}
.tb-red{background:rgba(248,113,113,0.08);color:var(--red);border-color:rgba(248,113,113,0.2)}
.tb-red:hover{background:rgba(248,113,113,0.15)}
/* GREETING */
.greeting{background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(244,114,182,0.08));border:1px solid var(--border);border-radius:18px;padding:18px 24px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;position:relative;overflow:hidden}
.greeting::after{content:'';position:absolute;top:-50px;right:-50px;width:160px;height:160px;background:radial-gradient(circle,rgba(139,92,246,0.2),transparent 70%)}
.g-text{font-size:.98rem;font-weight:800;color:var(--text);position:relative;z-index:1}
.g-dt{font-size:.72rem;color:var(--muted);font-weight:600;position:relative;z-index:1}
/* FLASH */
.flash{padding:13px 18px;border-radius:12px;font-weight:700;font-size:.83rem;margin-bottom:18px;display:flex;align-items:center;gap:10px;border:1px solid}
.flash-ok{background:rgba(74,222,128,0.07);color:var(--green);border-color:rgba(74,222,128,0.2)}
.flash-err{background:rgba(248,113,113,0.07);color:var(--red);border-color:rgba(248,113,113,0.2)}
/* PILLS */
.pills{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px}
.pill{display:flex;align-items:center;gap:8px;background:rgba(15,13,30,0.8);border:1px solid var(--border2);border-radius:100px;padding:8px 18px;font-size:.82rem;font-weight:700;transition:all .22s;cursor:default;backdrop-filter:blur(8px)}
.pill:hover{border-color:rgba(139,92,246,0.35);background:rgba(139,92,246,0.07)}
.pill-num{font-size:1rem;font-weight:900}
/* STAT GRID */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;margin-bottom:20px}
.scard{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:20px 18px;transition:all .3s;position:relative;overflow:hidden;cursor:default}
.scard::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;opacity:0;transition:opacity .3s;border-radius:16px 16px 0 0}
.scard:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,0.3)}
.scard:hover::before{opacity:1}
.sc-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;margin-bottom:12px}
.sc-val{font-size:1.9rem;font-weight:900;line-height:1;margin-bottom:3px}
.sc-lbl{font-size:.65rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}
/* color combos */
.s-purple .sc-icon{background:rgba(139,92,246,0.15);color:var(--accent2)}.s-purple .sc-val{color:var(--accent2)}.s-purple::before{background:var(--accent2)}
.s-red .sc-icon{background:rgba(248,113,113,0.12);color:var(--red)}.s-red .sc-val{color:var(--red)}.s-red::before{background:var(--red)}
.s-cyan .sc-icon{background:rgba(34,211,238,0.1);color:var(--cyan)}.s-cyan .sc-val{color:var(--cyan)}.s-cyan::before{background:var(--cyan)}
.s-green .sc-icon{background:rgba(74,222,128,0.1);color:var(--green)}.s-green .sc-val{color:var(--green)}.s-green::before{background:var(--green)}
.s-yellow .sc-icon{background:rgba(251,191,36,0.1);color:var(--yellow)}.s-yellow .sc-val{color:var(--yellow)}.s-yellow::before{background:var(--yellow)}
.s-orange .sc-icon{background:rgba(251,146,60,0.1);color:var(--orange)}.s-orange .sc-val{color:var(--orange)}.s-orange::before{background:var(--orange)}
/* MLB */
.ml-banner{background:linear-gradient(135deg,rgba(251,191,36,0.1),rgba(251,146,60,0.06));border:1px solid rgba(251,191,36,0.22);border-radius:14px;padding:14px 20px;display:flex;align-items:center;gap:14px;margin-bottom:20px}
.ml-icon{width:40px;height:40px;border-radius:12px;background:rgba(251,191,36,0.14);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--yellow);flex-shrink:0}
.ml-lbl{font-size:.6rem;font-weight:900;color:var(--yellow);text-transform:uppercase;letter-spacing:.1em}
.ml-t{font-size:.9rem;font-weight:800;color:var(--text)}
.ml-cnt{margin-left:auto;font-size:.78rem;font-weight:800;color:var(--yellow);white-space:nowrap}
/* CARD */
.card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:18px;backdrop-filter:blur(8px);transition:border-color .3s}
.card:hover{border-color:rgba(139,92,246,0.3)}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border2);gap:10px;flex-wrap:wrap}
.card-title{font-size:.88rem;font-weight:900;display:flex;align-items:center;gap:8px;color:var(--text)}
.card-title i{color:var(--accent2)}
.card-lnk{font-size:.72rem;font-weight:800;color:var(--accent);text-decoration:none;display:flex;align-items:center;gap:4px;transition:color .2s}
.card-lnk:hover{color:var(--accent2)}
/* WEEKLY */
.weekly-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:18px}
.wcard{border-radius:14px;padding:16px 18px;border:1px solid;transition:all .25s}
.wcard:hover{transform:translateY(-3px)}
.wc-p{background:rgba(139,92,246,0.1);border-color:rgba(139,92,246,0.22)}
.wc-pk{background:rgba(244,114,182,0.08);border-color:rgba(244,114,182,0.18)}
.wc-y{background:rgba(251,191,36,0.07);border-color:rgba(251,191,36,0.18)}
.wc-lbl{font-size:.6rem;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:7px;display:flex;align-items:center;gap:5px}
.wc-val{font-size:1.8rem;font-weight:900;line-height:1;margin-bottom:4px}
.wc-trend{font-size:.7rem;font-weight:800;display:flex;align-items:center;gap:4px}
.t-up{color:var(--green)}.t-dn{color:var(--red)}.t-flat{color:var(--muted)}
/* MILESTONE */
.milestone{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin-bottom:18px}
.ms-top{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px}
.ms-lbl{font-size:.62rem;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;display:flex;align-items:center;gap:6px}
.ms-cnt{font-size:.85rem;font-weight:800;color:var(--accent2)}
.ms-track{background:rgba(255,255,255,0.05);border-radius:40px;height:11px;overflow:hidden;margin-bottom:7px;border:1px solid var(--border2)}
.ms-fill{height:100%;border-radius:40px;background:linear-gradient(90deg,var(--accent),var(--pink));transition:width .8s ease;box-shadow:0 0 10px rgba(139,92,246,0.4)}
.ms-row{display:flex;justify-content:space-between;font-size:.68rem;font-weight:800;color:var(--muted)}
.ms-achieved{display:flex;flex-wrap:wrap;gap:5px;margin-top:9px}
.ms-badge{background:rgba(139,92,246,0.1);border:1px solid var(--border2);border-radius:100px;padding:2px 10px;font-size:.62rem;font-weight:900;color:var(--accent2)}
/* DUAL */
.dual-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
/* HEATMAP */
.hm-grid{display:grid;grid-template-columns:repeat(24,1fr);gap:3px;margin-top:10px}
.hm-cell{aspect-ratio:1;border-radius:4px;cursor:default;transition:transform .15s;position:relative}
.hm-cell:hover{transform:scale(1.4);z-index:5}
.hm-cell::after{content:attr(data-tip);position:absolute;bottom:130%;left:50%;transform:translateX(-50%);background:#0a0a16;color:#fff;font-size:.58rem;font-weight:700;padding:3px 7px;border-radius:6px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .15s;border:1px solid var(--border);z-index:10}
.hm-cell:hover::after{opacity:1}
.hm-labels{display:grid;grid-template-columns:repeat(24,1fr);gap:3px;margin-top:3px}
.hm-lbl{font-size:.48rem;font-weight:700;color:var(--muted);text-align:center}
/* PLATFORM */
.plat-bar{display:flex;height:14px;border-radius:40px;overflow:hidden;border:1px solid var(--border);margin:10px 0}
.plat-m{background:linear-gradient(90deg,var(--accent),var(--accent2))}
.plat-d{background:linear-gradient(90deg,var(--cyan),#38bdf8)}
/* TOP3 */
.top3-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:12px}
.t3card{background:rgba(0,0,0,0.3);border:1px solid var(--border2);border-radius:12px;padding:14px 10px;text-align:center;transition:all .2s}
.t3card:hover{transform:translateY(-3px);border-color:var(--border)}
.t3-av{width:44px;height:44px;border-radius:50%;border:2px solid var(--accent);object-fit:cover;margin:0 auto 7px;display:block}
.t3-ph{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;margin:0 auto 7px;font-weight:900;color:#fff}
.t3-name{font-weight:800;font-size:.78rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)}
.t3-score{font-size:.65rem;font-weight:700;color:var(--accent2);margin-top:2px}
/* GHOST */
.ghost-row{display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid var(--border2)}
.ghost-row:last-child{border-bottom:none}
.g-ph{width:32px;height:32px;border-radius:50%;background:rgba(139,92,246,0.1);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.75rem;color:var(--accent2);flex-shrink:0}
/* ACTION GRID */
.action-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px}
.acard{display:flex;align-items:center;gap:14px;padding:18px;border-radius:14px;text-decoration:none;border:1px solid;transition:all .28s;position:relative;overflow:hidden}
.acard:hover{transform:translateY(-3px);box-shadow:0 12px 30px rgba(0,0,0,0.3)}
.ac-p{background:rgba(139,92,246,0.08);border-color:rgba(139,92,246,0.22)}.ac-p:hover{background:rgba(139,92,246,0.13)}
.ac-pk{background:rgba(244,114,182,0.06);border-color:rgba(244,114,182,0.18)}.ac-pk:hover{background:rgba(244,114,182,0.1)}
.ac-c{background:rgba(34,211,238,0.05);border-color:rgba(34,211,238,0.15)}.ac-c:hover{background:rgba(34,211,238,0.09)}
.ac-y{background:rgba(251,191,36,0.06);border-color:rgba(251,191,36,0.18)}.ac-y:hover{background:rgba(251,191,36,0.1)}
.ac-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0}
.ai-p{background:rgba(139,92,246,0.18);color:var(--accent2)}
.ai-pk{background:rgba(244,114,182,0.14);color:var(--pink)}
.ai-c{background:rgba(34,211,238,0.1);color:var(--cyan)}
.ai-y{background:rgba(251,191,36,0.12);color:var(--yellow)}
.ac-t{font-size:.9rem;font-weight:900;color:var(--text);margin-bottom:2px}
.ac-d{font-size:.7rem;color:var(--muted);font-weight:500}
.ac-arr{margin-left:auto;font-size:.85rem;color:var(--muted);transition:all .22s;flex-shrink:0}
.acard:hover .ac-arr{color:var(--accent2);transform:translateX(4px)}
/* TABLE */
.dtable{width:100%;border-collapse:collapse;font-size:.78rem}
.dtable th{background:rgba(139,92,246,0.07);color:var(--accent2);font-weight:800;font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;padding:9px 13px;text-align:left;border-bottom:1px solid var(--border)}
.dtable td{padding:10px 13px;border-bottom:1px solid var(--border2);color:var(--muted);vertical-align:middle}
.dtable tr:last-child td{border-bottom:none}
.dtable tr:hover td{background:rgba(139,92,246,0.03)}
.u-av{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid rgba(139,92,246,0.3)}
.u-n{font-weight:800;color:var(--text);font-size:.83rem}
.u-e{font-size:.68rem;color:var(--muted);margin-top:1px}
.rbadge{display:inline-flex;align-items:center;padding:2px 9px;border-radius:100px;font-size:.6rem;font-weight:900;text-transform:uppercase;border:1px solid}
.rb-a{background:rgba(251,191,36,0.1);color:var(--yellow);border-color:rgba(251,191,36,0.22)}
.rb-u{background:rgba(139,92,246,0.08);color:var(--accent2);border-color:var(--border2)}
.d-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:9px;font-size:.73rem;font-weight:800;border:1px solid;transition:all .2s;cursor:pointer;background:transparent;font-family:var(--font);text-decoration:none}
.db-p{color:var(--accent2);border-color:rgba(139,92,246,0.25);background:rgba(139,92,246,0.07)}
.db-p:hover{background:rgba(139,92,246,0.15)}
.db-full{width:100%;justify-content:center;padding:11px}
/* MODALS */
.modal-ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(14px);z-index:2000;align-items:center;justify-content:center;padding:20px}
.modal-box{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:28px;max-width:480px;width:100%;box-shadow:0 24px 80px rgba(0,0,0,0.6);max-height:88vh;overflow-y:auto;position:relative}
.modal-box::-webkit-scrollbar{width:3px}
.modal-box::-webkit-scrollbar-thumb{background:var(--accent);border-radius:10px}
.m-close{position:absolute;top:13px;right:13px;width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,0.05);border:1px solid var(--border);color:var(--muted);font-size:.85rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;font-family:var(--font)}
.m-close:hover{background:rgba(248,113,113,0.1);color:var(--red)}
.act-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px}
.astat{border-radius:12px;padding:13px;text-align:center;border:1px solid}
.as-p{background:rgba(139,92,246,0.07);border-color:rgba(139,92,246,0.18)}
.as-y{background:rgba(251,191,36,0.05);border-color:rgba(251,191,36,0.18)}
.as-g{background:rgba(74,222,128,0.05);border-color:rgba(74,222,128,0.16)}
.as-r{background:rgba(248,113,113,0.05);border-color:rgba(248,113,113,0.16)}
.astat-val{font-size:1.4rem;font-weight:900;line-height:1}
.astat-lbl{font-size:.6rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-top:3px}
.del-icon{width:56px;height:56px;border-radius:16px;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.22);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--red);margin:0 auto 14px}
.del-btns{display:flex;gap:10px;margin-top:18px}
.del-cancel{flex:1;padding:11px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:11px;color:var(--muted);font-weight:800;cursor:pointer;font-family:var(--font);font-size:.85rem;transition:all .2s}
.del-cancel:hover{border-color:var(--accent);color:var(--text)}
.del-confirm{flex:1;padding:11px;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.25);border-radius:11px;color:var(--red);font-weight:800;cursor:pointer;font-family:var(--font);font-size:.85rem;transition:all .2s;width:100%}
.del-confirm:hover{background:rgba(248,113,113,0.18)}
/* SCROLLBAR */
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:rgba(139,92,246,0.4);border-radius:10px}
/* GENDER */
.gi-m{color:var(--cyan)}.gi-f{color:var(--pink)}.gi-a{color:var(--muted)}
@media(max-width:1100px){.dual-grid,.weekly-grid,.action-grid{grid-template-columns:1fr}}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-admin{padding:10px;justify-content:center}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px}}
@media(max-width:600px){.sidebar{display:none}.main{margin-left:0;padding:14px 14px 90px}.stats-grid{grid-template-columns:1fr 1fr}.top3-grid{grid-template-columns:1fr 1fr}}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:14px 14px 90px}.mob-topbar{display:flex!important}.mob-bottom-nav{display:block!important}.topbar{display:none}}
/* MOBILE TOPBAR */
.mob-topbar{display:none;position:sticky;top:0;z-index:300;background:rgba(7,6,15,0.96);backdrop-filter:blur(16px);border-bottom:1px solid var(--border2);padding:13px 16px;align-items:center;gap:12px}
.mob-menu-btn{width:38px;height:38px;border-radius:10px;background:rgba(139,92,246,0.08);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--accent2);font-size:1rem;cursor:pointer;flex-shrink:0}
.mob-page-title{font-size:1rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1}
.mob-home-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;background:rgba(34,211,238,0.08);color:var(--cyan);border:1px solid rgba(34,211,238,0.2);flex-shrink:0}
/* MOBILE DRAWER */
.drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(6px);z-index:500}
.drawer{position:fixed;left:0;top:0;bottom:0;width:265px;background:rgba(7,6,15,0.99);border-right:1px solid var(--border);z-index:600;display:flex;flex-direction:column;transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1)}
.drawer.open{transform:translateX(0)}
.drawer-overlay.open{display:block}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:18px 16px;border-bottom:1px solid var(--border2)}
.drawer-brand{font-size:.8rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.drawer-close{width:32px;height:32px;border-radius:8px;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:var(--red);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem}
.drawer-user{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border2)}
.d-av{width:40px;height:40px;border-radius:50%;border:2px solid var(--accent);object-fit:cover;flex-shrink:0}
.d-av-ph{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;flex-shrink:0}
.d-uname{font-size:.85rem;font-weight:800}
.d-role{font-size:.65rem;color:var(--accent2);font-weight:700;text-transform:uppercase}
.drawer-nav{flex:1;overflow-y:auto;padding:8px 10px}
.d-sec{font-size:.6rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 8px 5px}
.d-link{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;margin-bottom:2px}
.d-link:hover,.d-link.active{background:rgba(139,92,246,0.1);color:var(--accent2)}
.d-link i{width:18px;text-align:center}
.drawer-bottom{padding:12px 10px;border-top:1px solid var(--border2)}
.d-logout{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:700;color:var(--red);text-decoration:none}
.d-logout:hover{background:rgba(248,113,113,0.08)}
/* MOBILE BOTTOM NAV */
.mob-bottom-nav{display:none;position:fixed;bottom:0;left:0;right:0;z-index:400;background:rgba(7,6,15,0.96);backdrop-filter:blur(20px);border-top:1px solid var(--border);padding:8px 0 env(safe-area-inset-bottom,8px)}
.mob-nav-items{display:flex;justify-content:space-around;align-items:center}
.mob-nav-item{display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px 10px;border-radius:12px;text-decoration:none;color:var(--muted);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;transition:all .2s;border:1px solid transparent}
.mob-nav-item i{font-size:1.1rem}
.mob-nav-item.active{color:var(--accent2);background:rgba(139,92,246,0.1);border-color:var(--border)}
/* CUSTOM CURSOR */
*{cursor:none!important}
#c-dot{position:fixed;width:8px;height:8px;background:#c084fc;border-radius:50%;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width .15s,height .15s,background .2s;box-shadow:0 0 8px #c084fc,0 0 16px rgba(192,132,252,0.4)}
#c-ring{position:fixed;width:32px;height:32px;border:1.5px solid rgba(139,92,246,0.6);border-radius:50%;pointer-events:none;z-index:99998;transform:translate(-50%,-50%);transition:width .2s,height .2s,border-color .2s,opacity .2s;box-shadow:0 0 10px rgba(139,92,246,0.2)}
@media(max-width:768px){#c-dot,#c-ring{display:none!important}}
.c-hover #c-dot{width:12px;height:12px;background:#f472b6;box-shadow:0 0 12px #f472b6,0 0 24px rgba(244,114,182,0.5)}
.c-hover #c-ring{width:44px;height:44px;border-color:rgba(244,114,182,0.5);box-shadow:0 0 14px rgba(244,114,182,0.2)}
@media(max-width:768px){#c-dot,#c-ring{display:none!important}}
.c-click #c-dot{width:6px;height:6px;background:#22d3ee;box-shadow:0 0 10px #22d3ee}
.c-click #c-ring{width:24px;height:24px;border-color:rgba(34,211,238,0.7)}
@media(max-width:768px){#c-dot,#c-ring{display:none!important}}</style>
</head>
<body>
<div id="c-dot"></div>
<div id="c-ring"></div>
<div id="sp"></div>
<canvas id="pc"></canvas>

<!-- MOBILE DRAWER -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="sideDrawer">
  <div class="drawer-head">
    <div class="drawer-brand">Arigato Admin</div>
    <div class="drawer-close" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></div>
  </div>
  <div class="drawer-user">
    <?php $sav2=!empty($_SESSION['profile_image'])?htmlspecialchars($_SESSION['profile_image']):''; ?>
    <?php if($sav2): ?><img src="<?= $sav2 ?>" class="d-av" alt="">
    <?php else: ?><div class="d-av-ph"><?= strtoupper(substr($admin_name,0,1)) ?></div><?php endif; ?>
    <div><div class="d-uname"><?= htmlspecialchars($admin_name) ?></div><div class="d-role">Admin</div></div>
  </div>
  <nav class="drawer-nav">
    <div class="d-sec">Overview</div>
    <a href="dashboard.php" class="d-link active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="analytics.php" class="d-link"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    <div class="d-sec">Content</div>
    <a href="upload_prompt.php" class="d-link"><i class="fa-solid fa-upload"></i> Upload Prompt</a>
    <a href="manage_prompts.php" class="d-link"><i class="fa-solid fa-list-check"></i> Manage Prompts</a>
    <a href="prompt_links.php" class="d-link"><i class="fa-solid fa-link"></i> Prompt Links</a>
    <a href="potd_manager.php" class="d-link"><i class="fa-solid fa-sun"></i> POTD Manager</a>
    <a href="trending_settings.php" class="d-link"><i class="fa-solid fa-fire-flame-curved"></i> Trending Settings</a>
    <div class="d-sec">Blog</div>
    <a href="blog_admin.php" class="d-link"><i class="fa-solid fa-pen-nib"></i> Blog Admin</a>
    <a href="blog_create.php" class="d-link"><i class="fa-solid fa-plus"></i> New Post</a>
    <div class="d-sec">Community</div>
    <a href="feedback_admin.php" class="d-link"><i class="fa-solid fa-comments"></i> Feedbacks</a>
    <div class="d-sec">Users</div>
    <a href="user_management.php" class="d-link"><i class="fa-solid fa-users"></i> Users</a>
    <div class="d-sec">Tools</div>
    <a href="index.php" class="d-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
  </nav>
  <div class="drawer-bottom">
    <a href="login.php?logout=1" class="d-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </div>
</div>

<!-- MOBILE TOP BAR -->
<div class="mob-topbar">
  <div class="mob-menu-btn" onclick="openDrawer()"><i class="fa-solid fa-bars"></i></div>
  <div class="mob-page-title"><i class="fa-solid fa-gauge-high" style="-webkit-text-fill-color:var(--accent2);margin-right:6px"></i>Dashboard</div>
  <a href="index.php" class="mob-home-btn" target="_blank"><i class="fa-solid fa-house"></i> Site</a>
</div>

<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-brand"><i class="fa-solid fa-shield-halved"></i> <span>Arigato Admin</span></div>
  </div>
  <div class="sb-admin">
    <?php $sav=!empty($_SESSION['profile_image'])?htmlspecialchars($_SESSION['profile_image']):''; ?>
    <?php if($sav): ?><img src="<?= $sav ?>" class="sb-av" alt="">
    <?php else: ?><div class="sb-av-ph"><?= strtoupper(substr($admin_name,0,1)) ?></div><?php endif; ?>
    <div><div class="sb-uname"><?= htmlspecialchars($admin_name) ?></div><div class="sb-role">Admin</div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-sec">Overview</div>
    <a href="dashboard.php" class="sb-link active"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
    <a href="analytics.php" class="sb-link"><i class="fa-solid fa-chart-line"></i> <span>Analytics</span></a>
    <div class="sb-sec">Content</div>
    <a href="upload_prompt.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="manage_prompts.php" class="sb-link"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
    <a href="prompt_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="potd_manager.php" class="sb-link"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
    <a href="trending_settings.php" class="sb-link"><i class="fa-solid fa-fire-flame-curved"></i> <span>Trending Settings</span></a>
    <div class="sb-sec">Blog</div>
    <a href="blog_admin.php" class="sb-link"><i class="fa-solid fa-pen-nib"></i> <span>Blog Admin</span></a>
    <a href="blog_create.php" class="sb-link"><i class="fa-solid fa-plus"></i> <span>New Post</span></a>
    <div class="sb-sec">Community</div>
    <a href="feedback_admin.php" class="sb-link"><i class="fa-solid fa-comments"></i> <span>Feedbacks</span></a>
    <div class="sb-sec">Users</div>
    <a href="user_management.php" class="sb-link"><i class="fa-solid fa-users"></i> <span>Users</span></a>
    <div class="sb-sec">Tools</div>
    <a href="index.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Site</span></a>
  </nav>
  <div class="sb-bottom">
    <a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-gauge-high"></i> Admin Dashboard</div>
    <div class="tb-time"><i class="fa-regular fa-clock"></i> <?= date('D, d M Y | h:i A') ?> IST</div>
    <a href="404.php" target="_blank" class="tb-btn tb-red"><i class="fa-solid fa-triangle-exclamation"></i> Preview 404</a>
  </div>

  <?php if ($success): ?><div class="flash flash-ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="flash flash-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="greeting">
    <div class="g-text"><?= $admin_greet ?></div>
    <div class="g-dt"><?= date('l, d F Y') ?></div>
  </div>

  <div class="pills">
    <div class="pill"><i class="fa-solid fa-users" style="color:var(--cyan)"></i><span class="pill-num" style="color:var(--cyan)"><?= $total_users_count ?></span><span style="color:var(--muted)">Users</span></div>
    <div class="pill"><i class="fa-solid fa-wand-magic-sparkles" style="color:var(--accent2)"></i><span class="pill-num" style="color:var(--accent2)"><?= $total_prompts ?></span><span style="color:var(--muted)">Prompts</span></div>
    <div class="pill"><i class="fa-solid fa-heart" style="color:var(--red)"></i><span class="pill-num" style="color:var(--red)"><?= number_format($total_likes) ?></span><span style="color:var(--muted)">Likes</span></div>
    <div class="pill"><i class="fa-solid fa-bookmark" style="color:var(--yellow)"></i><span class="pill-num" style="color:var(--yellow)"><?= $total_saves ?></span><span style="color:var(--muted)">Saves</span></div>
    <div class="pill"><i class="fa-solid fa-pen-nib" style="color:var(--green)"></i><span class="pill-num" style="color:var(--green)"><?= $total_blogs ?></span><span style="color:var(--muted)">Blogs</span></div>
  </div>

  <div class="stats-grid">
    <div class="scard s-purple"><div class="sc-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div><div class="sc-val"><?= $total_prompts ?></div><div class="sc-lbl">Total Prompts</div></div>
    <div class="scard s-red"><div class="sc-icon"><i class="fa-solid fa-heart"></i></div><div class="sc-val"><?= number_format($total_likes) ?></div><div class="sc-lbl">Total Likes</div></div>
    <div class="scard s-cyan"><div class="sc-icon"><i class="fa-solid fa-users"></i></div><div class="sc-val"><?= $total_users ?></div><div class="sc-lbl">Total Users</div></div>
    <div class="scard s-green"><div class="sc-icon"><i class="fa-solid fa-bookmark"></i></div><div class="sc-val"><?= $total_saves ?></div><div class="sc-lbl">Total Saves</div></div>
    <div class="scard s-yellow"><div class="sc-icon"><i class="fa-solid fa-user-plus"></i></div><div class="sc-val">+<?= $weekly_users ?></div><div class="sc-lbl">New Users (7d)</div></div>
    <div class="scard s-orange"><div class="sc-icon"><i class="fa-solid fa-fire"></i></div><div class="sc-val">+<?= $weekly_prompts ?></div><div class="sc-lbl">Prompts (7d)</div></div>
  </div>

  <?php if ($most_liked): ?>
  <div class="ml-banner">
    <div class="ml-icon"><i class="fa-solid fa-trophy"></i></div>
    <div><div class="ml-lbl">Most Liked Prompt</div><div class="ml-t"><?= htmlspecialchars($most_liked['title']) ?></div></div>
    <div class="ml-cnt"><i class="fa-solid fa-heart"></i> <?= $most_liked['likes_count'] ?></div>
  </div>
  <?php endif; ?>

  <?php
  $u_arrow=$users_trend>0?'<i class="fa-solid fa-arrow-trend-up"></i>':($users_trend<0?'<i class="fa-solid fa-arrow-trend-down"></i>':'<i class="fa-solid fa-minus"></i>');
  $u_cls=$users_trend>0?'t-up':($users_trend<0?'t-dn':'t-flat');
  $p_arrow=$prompts_trend>0?'<i class="fa-solid fa-arrow-trend-up"></i>':($prompts_trend<0?'<i class="fa-solid fa-arrow-trend-down"></i>':'<i class="fa-solid fa-minus"></i>');
  $p_cls=$prompts_trend>0?'t-up':($prompts_trend<0?'t-dn':'t-flat');
  ?>
  <div class="weekly-grid">
    <div class="wcard wc-p"><div class="wc-lbl"><i class="fa-solid fa-user-plus"></i> New Users This Week</div><div class="wc-val" style="color:var(--accent2)">+<?= $weekly_users_int ?></div><div class="wc-trend <?= $u_cls ?>"><?= $u_arrow ?> <?= abs($users_trend) ?>% vs last week</div></div>
    <div class="wcard wc-pk"><div class="wc-lbl"><i class="fa-solid fa-wand-magic-sparkles"></i> Prompts This Week</div><div class="wc-val" style="color:var(--pink)">+<?= $weekly_prompts_int ?></div><div class="wc-trend <?= $p_cls ?>"><?= $p_arrow ?> <?= abs($prompts_trend) ?>% vs last week</div></div>
    <?php if ($best_day&&$best_day['cnt']>0): ?>
    <div class="wcard wc-y"><div class="wc-lbl"><i class="fa-solid fa-trophy"></i> Best Signup Day Ever</div><div class="wc-val" style="color:var(--yellow)"><?= $best_day['cnt'] ?> users</div><div class="wc-trend t-flat"><?= date('d M Y',strtotime($best_day['day'])) ?></div></div>
    <?php endif; ?>
  </div>

  <div class="milestone">
    <div class="ms-top">
      <div class="ms-lbl"><i class="fa-solid fa-chart-line"></i> User Milestone Progress</div>
      <?php if ($next_milestone): ?><div class="ms-cnt"><?= $total_users_count ?> / <?= $next_milestone ?> users</div>
      <?php else: ?><div class="ms-cnt" style="color:var(--green)"><i class="fa-solid fa-circle-check"></i> All cleared!</div><?php endif; ?>
    </div>
    <div class="ms-track"><div class="ms-fill" id="msFill" style="width:0%"></div></div>
    <div class="ms-row"><span><?= $prev_milestone ?></span><span style="color:var(--accent2);font-weight:900"><?= $milestone_pct ?>%</span><span><?= $next_milestone??'?' ?></span></div>
    <?php $achieved=array_filter($milestone_goals,fn($g)=>$total_users_count>=$g); ?>
    <?php if(!empty($achieved)): ?><div class="ms-achieved"><?php foreach($achieved as $ag): ?><span class="ms-badge"><i class="fa-solid fa-check" style="font-size:.5rem"></i> <?= $ag ?></span><?php endforeach; ?></div><?php endif; ?>
  </div>

  <div class="dual-grid">
    <div class="card" style="margin-bottom:0">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-clock"></i> Hourly Signup Heatmap</div><span style="font-size:.65rem;color:var(--muted)">Hover for count</span></div>
      <div class="hm-grid">
        <?php foreach($hourly_data as $hr=>$cnt):
          $intensity=$hourly_max>0?$cnt/$hourly_max:0;
          $a=0.07+$intensity*0.78;
        ?>
        <div class="hm-cell" style="background:rgba(139,92,246,<?= $a ?>);border:1px solid rgba(139,92,246,0.08)" data-tip="<?= $hr ?>:00 — <?= $cnt ?> users"></div>
        <?php endforeach; ?>
      </div>
      <div class="hm-labels"><?php for($h=0;$h<24;$h++): ?><div class="hm-lbl"><?= $h%6===0?$h:'' ?></div><?php endfor; ?></div>
    </div>

    <div class="card" style="margin-bottom:0">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-chart-pie"></i> Platform + Leaderboard</div></div>
      <?php $plat_total=$mobile_count+$desktop_count; ?>
      <?php if($plat_total>0): $mob_pct=round($mobile_count/$plat_total*100);$desk_pct=100-$mob_pct; ?>
      <div class="plat-bar"><div class="plat-m" style="width:<?= $mob_pct ?>%"></div><div class="plat-d" style="width:<?= $desk_pct ?>%"></div></div>
      <div style="display:flex;gap:16px;font-size:.74rem;font-weight:700;margin-bottom:14px">
        <span><span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:var(--accent);margin-right:5px;vertical-align:middle"></span><i class="fa-solid fa-mobile-screen" style="color:var(--accent2)"></i> Mobile <?= $mob_pct ?>% (<?= $mobile_count ?>)</span>
        <span><span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:var(--cyan);margin-right:5px;vertical-align:middle"></span><i class="fa-solid fa-desktop" style="color:var(--cyan)"></i> Desktop <?= $desk_pct ?>% (<?= $desktop_count ?>)</span>
      </div>
      <?php else: ?><div style="color:var(--muted);font-size:.8rem;text-align:center;padding:10px 0 14px"><i class="fa-solid fa-circle-info"></i> Data will show after users log in</div><?php endif; ?>
      <div style="padding-top:12px;border-top:1px solid var(--border2)">
        <div style="font-size:.62rem;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:10px"><i class="fa-solid fa-crown" style="color:var(--yellow)"></i> Top 3 Most Active Users</div>
        <?php if(empty($top3_users)): ?><div style="color:var(--muted);font-size:.8rem;text-align:center;padding:10px 0">No activity data yet.</div>
        <?php else: ?>
        <div class="top3-grid" style="grid-template-columns:repeat(<?= count($top3_users) ?>,1fr)">
          <?php $cr=['<i class="fa-solid fa-crown" style="color:var(--yellow)"></i>','<i class="fa-solid fa-crown" style="color:var(--muted)"></i>','<i class="fa-solid fa-crown" style="color:var(--orange)"></i>']; ?>
          <?php foreach($top3_users as $i=>$tu): $g=strtolower($tu['gender']??''); ?>
          <div class="t3card">
            <div style="font-size:1.2rem;margin-bottom:5px"><?= $cr[$i]??($i+1) ?></div>
            <div style="font-size:.65rem;margin-bottom:5px" class="<?= $g==='male'?'gi-m':($g==='female'?'gi-f':'gi-a') ?>"><i class="fa-solid fa-<?= $g==='male'?'mars':($g==='female'?'venus':'user-astronaut') ?>"></i></div>
            <?php if(!empty($tu['avatar'])): ?><img class="t3-av" src="<?= htmlspecialchars($tu['avatar']) ?>" alt="">
            <?php else: ?><div class="t3-ph"><?= strtoupper(substr($tu['username']??'U',0,1)) ?></div><?php endif; ?>
            <div class="t3-name"><?= htmlspecialchars($tu['username']??'User') ?></div>
            <div class="t3-score"><?= $tu['score'] ?> pts</div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if(!empty($ghost_users)): ?>
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-ghost" style="color:var(--muted)"></i> Ghost Users <span style="font-size:.62rem;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:var(--red);border-radius:100px;padding:2px 9px;font-weight:900;margin-left:6px"><?= $ghost_total ?> never interacted</span></div>
      <a href="user_management.php#ghost-section" class="card-lnk"><i class="fa-solid fa-arrow-right"></i> View All</a>
    </div>
    <?php foreach($ghost_users as $gu): ?>
    <div class="ghost-row">
      <div class="g-ph"><?= strtoupper(substr($gu['username']??'U',0,1)) ?></div>
      <div style="flex:1;min-width:0"><div style="font-weight:800;font-size:.83rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)"><?= htmlspecialchars($gu['username']??'User') ?></div><div style="font-size:.68rem;color:var(--muted)"><?= htmlspecialchars($gu['email']??'') ?></div></div>
      <div style="font-size:.68rem;color:var(--muted);white-space:nowrap"><?= date('d M Y',strtotime($gu['created_at'])) ?></div>
    </div>
    <?php endforeach; ?>
    <a href="user_management.php#ghost-section" style="display:flex;align-items:center;justify-content:center;gap:7px;margin-top:12px;padding:10px;border-radius:10px;background:rgba(248,113,113,0.05);border:1px solid rgba(248,113,113,0.15);color:var(--red);font-size:.78rem;font-weight:800;text-decoration:none;transition:all .2s" onmouseover="this.style.background='rgba(248,113,113,0.1)'" onmouseout="this.style.background='rgba(248,113,113,0.05)'">
      <i class="fa-solid fa-ghost"></i> View all <?= $ghost_total ?> ghost users →
    </a>
  </div>
  <?php endif; ?>

  <div style="font-size:.62rem;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.12em;margin-bottom:12px"><i class="fa-solid fa-bolt" style="color:var(--yellow)"></i> Quick Actions</div>
  <div class="action-grid">
    <a href="upload_prompt.php" class="acard ac-p"><div class="ac-icon ai-p"><i class="fa-solid fa-upload"></i></div><div><div class="ac-t">Upload Prompt</div><div class="ac-d">Add a new AI prompt to the platform</div></div><div class="ac-arr"><i class="fa-solid fa-arrow-right"></i></div></a>
    <a href="manage_prompts.php" class="acard ac-pk"><div class="ac-icon ai-pk"><i class="fa-solid fa-list-check"></i></div><div><div class="ac-t">Manage Prompts</div><div class="ac-d">Edit, delete, review all <?= $total_prompts ?> prompts</div></div><div class="ac-arr"><i class="fa-solid fa-arrow-right"></i></div></a>
    <a href="prompt_links.php" class="acard ac-c"><div class="ac-icon ai-c"><i class="fa-solid fa-link"></i></div><div><div class="ac-t">Prompt Share Links</div><div class="ac-d">Copy direct links to share prompts</div></div><div class="ac-arr"><i class="fa-solid fa-arrow-right"></i></div></a>
    <a href="potd_manager.php" class="acard ac-y"><div class="ac-icon ai-y"><i class="fa-solid fa-sun"></i></div><div><div class="ac-t">Prompt of the Day</div><div class="ac-d">Manage featured daily prompts</div></div><div class="ac-arr"><i class="fa-solid fa-arrow-right"></i></div></a>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-users"></i> Recent Users <span style="font-size:.68rem;background:rgba(34,211,238,0.08);border:1px solid rgba(34,211,238,0.18);color:var(--cyan);border-radius:100px;padding:2px 9px;margin-left:6px;font-weight:900"><?= $total_users_count ?> Total</span></div>
      <a href="user_management.php" class="card-lnk"><i class="fa-solid fa-arrow-up-right-from-square"></i> Full Page</a>
    </div>
    <?php if(count($users)===0): ?>
    <div style="text-align:center;color:var(--muted);padding:28px 0;font-size:.85rem"><i class="fa-solid fa-users-slash"></i> No users registered yet.</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="dtable">
      <thead><tr><th>Avatar</th><th>Name / Email</th><th>Gender</th><th>Role</th><th>Joined</th><th>Activity</th></tr></thead>
      <tbody>
      <?php foreach($users as $u):
        $u_avatar=!empty($u['avatar'])?$u['avatar']:'https://api.dicebear.com/7.x/avataaars/svg?seed='.urlencode($u['email']??'x');
        $jdt=new DateTime($u['created_at'],new DateTimeZone('UTC'));
        $jdt->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $ug=strtolower($u['gender']??'');
      ?>
      <tr>
        <td><img loading="lazy" src="<?= htmlspecialchars($u_avatar) ?>" class="u-av" alt=""></td>
        <td><div class="u-n"><?= htmlspecialchars($u['username']??'—') ?></div><div class="u-e"><?= htmlspecialchars($u['email']??'') ?></div></td>
        <td class="<?= $ug==='male'?'gi-m':($ug==='female'?'gi-f':'gi-a') ?>"><i class="fa-solid fa-<?= $ug==='male'?'mars':($ug==='female'?'venus':'user-astronaut') ?>"></i> <?= $ug===''?'Alien':ucfirst($ug) ?></td>
        <td><span class="rbadge <?= $u['role']==='admin'?'rb-a':'rb-u' ?>"><?= strtoupper($u['role']??'user') ?></span></td>
        <td style="font-size:.72rem;color:var(--muted)"><?= $jdt->format('d M Y') ?><br><span style="opacity:.6;font-size:.65rem"><?= $jdt->format('h:i A') ?></span></td>
        <td><button onclick="openActivity(<?= (int)$u['id'] ?>)" class="d-btn db-p"><i class="fa-solid fa-chart-simple"></i> Activity</button></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
    <div style="text-align:center;padding:14px 0 4px;border-top:1px solid var(--border2);margin-top:14px">
      <a href="user_management.php" class="d-btn db-p db-full"><i class="fa-solid fa-users"></i> View All <?= $total_users_count ?> Users</a>
    </div>
  </div>

</main>

<!-- ACTIVITY MODAL -->
<div id="activity-modal" class="modal-ov" onclick="if(event.target===this)closeActivity()">
  <div class="modal-box">
    <button class="m-close" onclick="closeActivity()"><i class="fa-solid fa-xmark"></i></button>
    <div id="act-loading" style="text-align:center;padding:40px 0;color:var(--muted)"><i class="fa-solid fa-spinner fa-spin" style="color:var(--accent);font-size:1.5rem"></i></div>
    <div id="act-content" style="display:none">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border2)">
        <img id="act-avatar" src="" style="width:50px;height:50px;border-radius:50%;border:2px solid var(--accent);object-fit:cover" alt="">
        <div><div id="act-name" style="font-size:1rem;font-weight:900;color:var(--text)"></div><div id="act-email" style="font-size:.75rem;color:var(--muted);margin-top:2px"></div></div>
      </div>
      <div class="act-grid">
        <div class="astat as-p" style="grid-column:1/-1"><div id="act-last-active" class="astat-val" style="color:var(--accent2);font-size:.95rem"></div><div class="astat-lbl">Last Active</div></div>
        <div class="astat as-y"><div id="act-unlocks" class="astat-val" style="color:var(--yellow)"></div><div class="astat-lbl">Unlocked</div></div>
        <div class="astat as-g"><div id="act-saves" class="astat-val" style="color:var(--green)"></div><div class="astat-lbl">Saved</div></div>
        <div class="astat as-r"><div id="act-likes" class="astat-val" style="color:var(--red)"></div><div class="astat-lbl">Liked</div></div>
      </div>
      <div style="font-size:.62rem;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:9px"><i class="fa-solid fa-lock-open" style="color:var(--accent2)"></i> Unlocked Prompts</div>
      <div id="act-unlock-list" style="display:flex;flex-direction:column;gap:5px;max-height:200px;overflow-y:auto"></div>
    </div>
  </div>
</div>

<!-- DELETE MODAL -->
<div id="delete-modal" class="modal-ov" onclick="if(event.target===this)closeDeleteModal()">
  <div class="modal-box" style="max-width:360px;text-align:center">
    <button class="m-close" onclick="closeDeleteModal()"><i class="fa-solid fa-xmark"></i></button>
    <div class="del-icon"><i class="fa-solid fa-trash"></i></div>
    <div style="font-size:1.1rem;font-weight:900;color:var(--text);margin-bottom:7px">Delete Prompt?</div>
    <div id="delete-modal-name" style="font-size:.85rem;color:var(--muted);font-weight:600"></div>
    <div class="del-btns">
      <button onclick="closeDeleteModal()" class="del-cancel">Cancel</button>
      <form id="delete-form" action="delete_prompt.php" method="POST" style="flex:1;margin:0">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
        <input type="hidden" id="delete-prompt-id" name="prompt_id" value="">
        <button type="submit" class="del-confirm">Delete</button>
      </form>
    </div>
  </div>
</div>

<script>
// Scroll progress
const sp=document.getElementById('sp');
window.addEventListener('scroll',()=>{const h=document.documentElement;sp.style.width=(h.scrollTop/(h.scrollHeight-h.clientHeight)*100)+'%';},{passive:true});

// Particles
(function(){
  const c=document.getElementById('pc'),ctx=c.getContext('2d');let W,H,pts=[];
  function rs(){W=c.width=window.innerWidth;H=c.height=window.innerHeight}rs();window.addEventListener('resize',rs);
  class P{constructor(){this.reset()}reset(){this.x=Math.random()*W;this.y=Math.random()*H;this.vx=(Math.random()-.5)*.3;this.vy=(Math.random()-.5)*.3;this.r=Math.random()*1.4+.3;this.a=Math.random()*.45+.1;const cols=['139,92,246','244,114,182','34,211,238'];this.col=cols[Math.floor(Math.random()*cols.length)]}
  update(){this.x+=this.vx;this.y+=this.vy;if(this.x<0||this.x>W||this.y<0||this.y>H)this.reset()}
  draw(){ctx.beginPath();ctx.arc(this.x,this.y,this.r,0,Math.PI*2);ctx.fillStyle=`rgba(${this.col},${this.a})`;ctx.fill()}}
  for(let i=0;i<70;i++)pts.push(new P());
  function loop(){ctx.clearRect(0,0,W,H);pts.forEach(p=>{p.update();p.draw()});
    for(let i=0;i<pts.length;i++)for(let j=i+1;j<pts.length;j++){const dx=pts[i].x-pts[j].x,dy=pts[i].y-pts[j].y,d=Math.sqrt(dx*dx+dy*dy);if(d<100){ctx.beginPath();ctx.moveTo(pts[i].x,pts[i].y);ctx.lineTo(pts[j].x,pts[j].y);ctx.strokeStyle=`rgba(139,92,246,${(1-d/100)*.09})`;ctx.lineWidth=.5;ctx.stroke()}}
    requestAnimationFrame(loop)}loop();
})();

// Milestone fill animate
setTimeout(()=>{const f=document.getElementById('msFill');if(f)f.style.width='<?= $milestone_pct ?>%';},500);

// CountUp for stat cards
document.querySelectorAll('.sc-val').forEach(el=>{
  const raw=el.textContent.trim();
  const prefix=raw.startsWith('+')?'+':'';
  const num=parseInt(raw.replace(/[^0-9]/g,''));
  if(!isNaN(num)&&num>0){el.textContent=prefix+'0';let s=0,step=num/60;const t=setInterval(()=>{s+=step;if(s>=num){s=num;clearInterval(t)}el.textContent=prefix+Math.floor(s).toLocaleString()},16)}
});
document.querySelectorAll('.pill-num').forEach(el=>{
  const n=parseInt(el.textContent.replace(/,/g,''));
  if(!isNaN(n)&&n>0){el.textContent='0';let s=0,step=n/50;const t=setInterval(()=>{s+=step;if(s>=n){s=n;clearInterval(t)}el.textContent=Math.floor(s).toLocaleString()},16)}
});

// Delete Modal
function confirmDelete(id,name){document.getElementById('delete-prompt-id').value=id;document.getElementById('delete-modal-name').textContent='"'+name+'"';document.getElementById('delete-modal').style.display='flex'}
function closeDeleteModal(){document.getElementById('delete-modal').style.display='none'}

// Activity Modal
function openActivity(uid){
  document.getElementById('activity-modal').style.display='flex';
  document.getElementById('act-loading').style.display='block';
  document.getElementById('act-content').style.display='none';
  fetch('dashboard.php?xhr=activity&uid='+uid).then(r=>r.json()).then(data=>{
    if(!data.ok)return;const u=data.user;
    const av=u.avatar||'https://api.dicebear.com/7.x/avataaars/svg?seed='+encodeURIComponent(u.email||'x');
    document.getElementById('act-avatar').src=av;
    document.getElementById('act-name').textContent=u.username||'—';
    document.getElementById('act-email').textContent=u.email||'—';
    const la=u.last_active?new Date(u.last_active.replace(' ','T')+'Z'):null;
    document.getElementById('act-last-active').textContent=la?la.toLocaleString('en-IN',{timeZone:'Asia/Kolkata',day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}):'Never';
    document.getElementById('act-unlocks').textContent=data.unlock_list.length;
    document.getElementById('act-saves').textContent=data.saves_count;
    document.getElementById('act-likes').textContent=data.likes_count;
    const list=document.getElementById('act-unlock-list');list.innerHTML='';
    if(data.unlock_list.length===0){list.innerHTML='<div style="color:var(--muted);font-size:.8rem;text-align:center;padding:8px 0">No prompts unlocked yet.</div>'}
    else{data.unlock_list.forEach(p=>{const d=document.createElement('div');d.style.cssText='background:rgba(139,92,246,0.07);border:1px solid var(--border2);border-radius:8px;padding:6px 12px;font-size:.78rem;font-weight:700;color:var(--text)';d.innerHTML='<i class="fa-solid fa-lock-open" style="color:var(--accent2);margin-right:6px;font-size:.65rem"></i>'+(p.title||'—');list.appendChild(d)})}
    document.getElementById('act-loading').style.display='none';document.getElementById('act-content').style.display='block';
  });
}
function closeActivity(){document.getElementById('activity-modal').style.display='none'}

// Copy prompt link
function copyPromptLink(id,btn){
  var link=window.location.origin+'/card.php?id='+id;
  navigator.clipboard.writeText(link).then(()=>{var o=btn.innerHTML;btn.innerHTML='<i class="fa-solid fa-check"></i> Copied!';btn.style.color='var(--green)';btn.style.borderColor='rgba(74,222,128,0.3)';setTimeout(()=>{btn.innerHTML=o;btn.style.color='';btn.style.borderColor=''},2000)}).catch(()=>window.prompt('Copy link:',link));
}
function filterLinkTable(q){q=q.toLowerCase();var rows=document.querySelectorAll('#link-table tbody tr'),any=false;rows.forEach(r=>{var m=(r.dataset.search||'').includes(q);r.style.display=m?'':'none';if(m)any=true});var em=document.getElementById('link-table-empty');if(em)em.style.display=any?'none':'block'}

// Mobile drawer
function openDrawer(){document.getElementById('sideDrawer').classList.add('open');document.getElementById('drawerOverlay').classList.add('open');}
function closeDrawer(){document.getElementById('sideDrawer').classList.remove('open');document.getElementById('drawerOverlay').classList.remove('open');}
</script>

<!-- MOBILE BOTTOM NAV -->
<nav class="mob-bottom-nav">
  <div class="mob-nav-items">
    <a href="dashboard.php" class="mob-nav-item active"><i class="fa-solid fa-gauge-high"></i><span>Dash</span></a>
    <a href="manage_prompts.php" class="mob-nav-item"><i class="fa-solid fa-list-check"></i><span>Prompts</span></a>
    <a href="blog_admin.php" class="mob-nav-item"><i class="fa-solid fa-pen-nib"></i><span>Blogs</span></a>
    <a href="user_management.php" class="mob-nav-item"><i class="fa-solid fa-users"></i><span>Users</span></a>
    <a href="analytics.php" class="mob-nav-item"><i class="fa-solid fa-chart-line"></i><span>Stats</span></a>
  </div>
</nav>
</html>


