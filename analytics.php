<?php
session_start();
require_once "db.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

function sqAll($pdo, $sql) {
    try { return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
    catch (Exception $e) { return []; }
}
function sqOne($pdo, $sql, $default = 0) {
    try { $v = $pdo->query($sql)->fetchColumn(); return ($v !== false && $v !== null) ? $v : $default; }
    catch (Exception $e) { return $default; }
}
function fill30($pdo, $sql) {
    try { $raw = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
    catch (Exception $e) { $raw = []; }
    $map = [];
    foreach ($raw as $r) $map[$r['d']] = (int)$r['c'];
    $days = []; $vals = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $days[] = date('d M', strtotime($d));
        $vals[] = $map[$d] ?? 0;
    }
    return ['l' => json_encode($days), 'd' => json_encode($vals)];
}

// -- Core stats --
$total_prompts = sqOne($pdo, "SELECT COUNT(*) FROM prompts");
$total_likes   = sqOne($pdo, "SELECT COALESCE(SUM(likes_count),0) FROM prompts");
$total_users   = sqOne($pdo, "SELECT COUNT(*) FROM users");
$total_unlocks = sqOne($pdo, "SELECT COUNT(*) FROM unlocked_prompts");
$total_saves   = sqOne($pdo, "SELECT COUNT(*) FROM saved_prompts");
$weekly_p      = sqOne($pdo, "SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)");
$monthly_p     = sqOne($pdo, "SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)");
$weekly_u      = sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)");
$monthly_u     = sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)");
$total_views   = sqOne($pdo, "SELECT COALESCE(SUM(view_count),0) FROM prompts");
$total_copies  = sqOne($pdo, "SELECT COALESCE(SUM(copy_count),0) FROM prompts");
$total_shares  = sqOne($pdo, "SELECT COALESCE(SUM(share_count),0) FROM prompts");
$most_liked_r  = sqAll($pdo, "SELECT title,likes_count FROM prompts ORDER BY likes_count DESC LIMIT 1");
$most_liked    = $most_liked_r[0] ?? null;
$avg_journey   = round((float)sqOne($pdo, "SELECT AVG(DATEDIFF(f.fu,u.created_at)) FROM users u JOIN (SELECT user_id,MIN(created_at) as fu FROM unlocked_prompts GROUP BY user_id) f ON u.id=f.user_id", 0), 1);

// -- Chart data --
$top_prompts    = sqAll($pdo, "SELECT title, likes_count FROM prompts ORDER BY likes_count DESC LIMIT 10");
$top_unlocked   = sqAll($pdo, "SELECT p.title, COUNT(u.id) as c FROM unlocked_prompts u JOIN prompts p ON p.id=u.prompt_id GROUP BY p.id,p.title ORDER BY c DESC LIMIT 10");
$type_breakdown = sqAll($pdo, "SELECT prompt_type, COUNT(*) as cnt FROM prompts GROUP BY prompt_type ORDER BY cnt DESC");

// -- 30-day trends --
$ug  = fill30($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC");
$pg  = fill30($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC");
$spd = fill30($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM saved_prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC");
$upd = fill30($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM unlocked_prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC");

// -- Blog reads --
$top_blogs = sqAll($pdo, "SELECT title, COALESCE(view_count,0) as views FROM blogs WHERE is_published=1 ORDER BY views DESC LIMIT 10");

// -- New users by hour (IST, UTC+5:30) --
$ubh_raw = sqAll($pdo, "SELECT HOUR(CONVERT_TZ(created_at,'+00:00','+05:30')) as h, COUNT(*) as c FROM users GROUP BY h ORDER BY h ASC");
$hmap = array_fill(0, 24, 0);
foreach ($ubh_raw as $r) $hmap[(int)$r['h']] = (int)$r['c'];

// -- Retention (requires last_active column) --
$coh1   = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 1 DAY)");
$r1cnt  = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 1 DAY) AND last_active > DATE_ADD(created_at,INTERVAL 1 DAY)");
$coh7   = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 7 DAY)");
$r7cnt  = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 7 DAY) AND last_active > DATE_ADD(created_at,INTERVAL 7 DAY)");
$coh30  = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 30 DAY)");
$r30cnt = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 30 DAY) AND last_active > DATE_ADD(created_at,INTERVAL 30 DAY)");
$ret_d1  = $coh1  > 0 ? round($r1cnt*100/$coh1,1)   : 0;
$ret_d7  = $coh7  > 0 ? round($r7cnt*100/$coh7,1)   : 0;
$ret_d30 = $coh30 > 0 ? round($r30cnt*100/$coh30,1) : 0;

// -- New vs Returning (last 7 days) --
$new_7    = (int)$weekly_u;
$return_7 = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 7 DAY) AND last_active >= DATE_SUB(NOW(),INTERVAL 7 DAY)");

// -- Top saved prompts --
$top_saved = sqAll($pdo, "SELECT p.title, COUNT(sp.id) as c FROM saved_prompts sp JOIN prompts p ON p.id=sp.prompt_id GROUP BY p.id,p.title ORDER BY c DESC LIMIT 10");

// -- Unlock-to-view ratio --
$unlock_view = sqAll($pdo, "SELECT p.title, COUNT(up.id) as unlocks, COALESCE(p.view_count,0) as views FROM unlocked_prompts up JOIN prompts p ON p.id=up.prompt_id GROUP BY p.id,p.title ORDER BY unlocks DESC LIMIT 10");

// -- Prompt age vs performance --
$age_perf = sqAll($pdo, "SELECT p.title, DATEDIFF(NOW(),p.created_at) as age, COUNT(up.id) as unlocks, p.likes_count FROM prompts p LEFT JOIN unlocked_prompts up ON p.id=up.prompt_id GROUP BY p.id,p.title,p.created_at,p.likes_count ORDER BY unlocks DESC LIMIT 12");

// -- Power users (5+ unlocks) --
$power_users = sqAll($pdo, "SELECT u.username, u.email, COUNT(up.id) as cnt FROM users u JOIN unlocked_prompts up ON u.id=up.user_id GROUP BY u.id,u.username,u.email HAVING cnt >= 5 ORDER BY cnt DESC LIMIT 15");

// -- Churn risk (active 8?30 days ago, not in last 7 days) --
$churn_users = sqAll($pdo, "SELECT username, email, last_active FROM users WHERE last_active >= DATE_SUB(NOW(),INTERVAL 30 DAY) AND last_active < DATE_SUB(NOW(),INTERVAL 7 DAY) ORDER BY last_active ASC LIMIT 10");

// -- Dead prompts (0 unlocks in last 30 days) --
$dead_prompts = sqAll($pdo, "SELECT p.title, p.created_at, p.likes_count FROM prompts p WHERE p.id NOT IN (SELECT DISTINCT prompt_id FROM unlocked_prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)) ORDER BY p.likes_count DESC LIMIT 10");

// -- Spike days (50+ signups) --
$spike_days = sqAll($pdo, "SELECT DATE(CONVERT_TZ(created_at,'+00:00','+05:30')) as d, COUNT(*) as cnt FROM users GROUP BY d HAVING cnt >= 50 ORDER BY d DESC LIMIT 5");

// -- Milestones --
$ms_all     = [50, 100, 150, 200, 300, 500, 750, 1000];
$next_ms    = null;
foreach ($ms_all as $m) { if ((int)$total_users < $m) { $next_ms = $m; break; } }
$reached_ms = array_values(array_filter($ms_all, fn($m) => (int)$total_users >= $m));

// -- JSON for charts --
$bar_labels  = json_encode(array_column($top_prompts, "title"));
$bar_data    = json_encode(array_column($top_prompts, "likes_count"));
$ul_labels   = json_encode(array_column($top_unlocked, "title"));
$ul_data     = json_encode(array_column($top_unlocked, "c"));
$type_labels = json_encode(array_column($type_breakdown, "prompt_type"));
$type_data   = json_encode(array_column($type_breakdown, "cnt"));
$blog_labels = json_encode(array_column($top_blogs, "title"));
$blog_data   = json_encode(array_column($top_blogs, "views"));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js">function openDrawer(){document.getElementById('sideDrawer').classList.add('open');document.getElementById('drawerOverlay').classList.add('open');}
function closeDrawer(){document.getElementById('sideDrawer').classList.remove('open');document.getElementById('drawerOverlay').classList.remove('open');}
</script>
<?php include_once "gtag.php"; ?>
<style>
:root{--bg:#07060f;--surface:#0f0d1e;--border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);--accent:#8b5cf6;--accent2:#c084fc;--pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;--yellow:#fbbf24;--orange:#fb923c;--red:#f87171;--text:#e2e0ff;--muted:#9490bb;--font:'Inter',sans-serif}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:var(--font);overflow-x:hidden;min-height:100vh}
#sp{position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,var(--accent),var(--pink),var(--cyan));z-index:9999;box-shadow:0 0 10px var(--accent)}
#pc{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.35}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:220px;background:rgba(7,6,15,0.98);border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column}
.sb-logo{padding:20px 18px 14px;border-bottom:1px solid var(--border2)}
.sb-brand{font-size:.72rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:flex;align-items:center;gap:8px}
.sb-brand i{-webkit-text-fill-color:#a78bfa}
.sb-admin{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border2)}
.sb-av-ph{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;flex-shrink:0}
.sb-uname{font-size:.78rem;font-weight:800;color:var(--text)}.sb-role{font-size:.6rem;font-weight:700;color:var(--accent2);text-transform:uppercase;letter-spacing:.1em}
.sb-nav{flex:1;overflow-y:auto;padding:10px 8px}.sb-nav::-webkit-scrollbar{width:2px}.sb-nav::-webkit-scrollbar-thumb{background:var(--accent);border-radius:10px}
.sb-sec{font-size:.58rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 10px 5px}
.sb-link{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;border:1px solid transparent;margin-bottom:1px}
.sb-link:hover{background:rgba(139,92,246,0.08);color:var(--text)}.sb-link.active{background:rgba(139,92,246,0.15);color:var(--accent2);border-color:var(--border)}
.sb-link i{width:16px;text-align:center;flex-shrink:0}
.sb-bottom{padding:12px 8px;border-top:1px solid var(--border2)}
.sb-logout{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:700;color:var(--red);text-decoration:none;transition:all .2s}.sb-logout:hover{background:rgba(248,113,113,0.1)}
.main{margin-left:220px;min-height:100vh;padding:28px 32px 80px;position:relative;z-index:1}
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:22px;flex-wrap:wrap}
.tb-title{font-size:1.5rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1}
/* STAT GRID */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:12px;margin-bottom:18px}
.scard{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:15px;padding:16px;transition:all .3s;position:relative;overflow:hidden;cursor:default}
.scard::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;opacity:0;transition:opacity .3s}
.scard:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,0.3)}.scard:hover::before{opacity:1}
.sc-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;margin-bottom:10px}
.sc-val{font-size:1.6rem;font-weight:900;line-height:1;margin-bottom:2px}
.sc-lbl{font-size:.6rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}
.sc-sub{font-size:.65rem;font-weight:700;color:var(--muted);margin-top:4px}
.s-purple .sc-icon{background:rgba(139,92,246,0.12);color:var(--accent2)}.s-purple .sc-val{color:var(--accent2)}.s-purple::before{background:var(--accent2)}
.s-pink .sc-icon{background:rgba(244,114,182,0.1);color:var(--pink)}.s-pink .sc-val{color:var(--pink)}.s-pink::before{background:var(--pink)}
.s-cyan .sc-icon{background:rgba(34,211,238,0.08);color:var(--cyan)}.s-cyan .sc-val{color:var(--cyan)}.s-cyan::before{background:var(--cyan)}
.s-orange .sc-icon{background:rgba(251,146,60,0.1);color:var(--orange)}.s-orange .sc-val{color:var(--orange)}.s-orange::before{background:var(--orange)}
.s-green .sc-icon{background:rgba(74,222,128,0.08);color:var(--green)}.s-green .sc-val{color:var(--green)}.s-green::before{background:var(--green)}
.s-yellow .sc-icon{background:rgba(251,191,36,0.08);color:var(--yellow)}.s-yellow .sc-val{color:var(--yellow)}.s-yellow::before{background:var(--yellow)}
.s-red .sc-icon{background:rgba(248,113,113,0.08);color:var(--red)}.s-red .sc-val{color:var(--red)}.s-red::before{background:var(--red)}
.s-span2{grid-column:span 2}
/* CARD */
.card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:18px;backdrop-filter:blur(8px);transition:border-color .3s}
.card:hover{border-color:rgba(139,92,246,0.28)}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border2);gap:10px;flex-wrap:wrap}
.card-title{font-size:.88rem;font-weight:900;display:flex;align-items:center;gap:8px;color:var(--text)}
.card-title i{color:var(--accent2)}
/* GRIDS */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
.grid-2-1{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px}
/* ALERT CARDS */
.alert-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px}
.alert-card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:14px;padding:16px}
.alert-title{font-size:.78rem;font-weight:900;display:flex;align-items:center;gap:7px;margin-bottom:10px;color:var(--text)}
.ms-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:.63rem;font-weight:900;border:1px solid;margin:2px}
.ms-done{background:rgba(74,222,128,0.08);color:var(--green);border-color:rgba(74,222,128,0.2)}
.ms-next{background:rgba(251,191,36,0.1);color:var(--yellow);border-color:rgba(251,191,36,0.25)}
/* RETENTION BARS */
.ret-row{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.ret-label{font-size:.72rem;font-weight:800;color:var(--muted);width:40px;flex-shrink:0}
.ret-bar-wrap{flex:1;height:10px;background:rgba(255,255,255,0.05);border-radius:100px;overflow:hidden}
.ret-bar{height:100%;border-radius:100px;transition:width 1s ease}
.ret-pct{font-size:.72rem;font-weight:900;color:var(--text);width:40px;text-align:right;flex-shrink:0}
/* TABLE */
.dtable{width:100%;border-collapse:collapse;font-size:.78rem}
.dtable th{background:rgba(139,92,246,0.07);color:var(--accent2);font-weight:800;font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;padding:9px 13px;text-align:left;border-bottom:1px solid var(--border)}
.dtable td{padding:10px 13px;border-bottom:1px solid var(--border2);color:var(--muted);vertical-align:middle}
.dtable tr:last-child td{border-bottom:none}.dtable tr:hover td{background:rgba(139,92,246,0.03)}
/* SCROLLBAR */
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:rgba(139,92,246,0.4);border-radius:10px}
/* MOBILE */
.mob-nav{display:none;position:fixed;bottom:0;left:0;right:0;background:rgba(7,6,15,0.97);border-top:1px solid var(--border);z-index:500;padding:8px 0 max(8px,env(safe-area-inset-bottom));flex-direction:row;justify-content:space-around;align-items:center}
.mn-link{display:flex;flex-direction:column;align-items:center;gap:3px;font-size:.6rem;font-weight:700;color:var(--muted);text-decoration:none;padding:4px 8px;min-width:48px;transition:all .2s}
.mn-link.active,.mn-link:hover{color:var(--accent2)}.mn-link i{font-size:1.1rem}
@media(max-width:1100px){.grid-4{grid-template-columns:1fr 1fr}.grid-2-1{grid-template-columns:1fr 1fr}}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-admin{padding:10px;justify-content:center}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px;padding:20px 16px 80px}.grid-2{grid-template-columns:1fr}.alert-grid{grid-template-columns:1fr}}
@media(max-width:600px){.sidebar{display:none}.main{margin-left:0;padding:14px 14px 80px}.mob-nav{display:flex}.stats-grid{grid-template-columns:1fr 1fr}.s-span2{grid-column:span 1}.grid-2{grid-template-columns:1fr}.grid-4{grid-template-columns:1fr}.grid-2-1{grid-template-columns:1fr}.alert-grid{grid-template-columns:1fr}.chart-scroll{overflow-x:auto}}
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
@media(max-width:768px){#c-dot,#c-ring{display:none!important}}/* MOBILE TOPBAR */
.mob-topbar{display:none;position:sticky;top:0;z-index:300;background:rgba(7,6,15,0.96);backdrop-filter:blur(16px);border-bottom:1px solid var(--border2);padding:13px 16px;align-items:center;gap:12px}
.mob-menu-btn{width:38px;height:38px;border-radius:10px;background:rgba(139,92,246,0.08);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--accent2);font-size:1rem;cursor:pointer;flex-shrink:0}
.mob-page-title{font-size:1rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1}
.mob-home-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;background:rgba(34,211,238,0.08);color:var(--cyan);border:1px solid rgba(34,211,238,0.2);flex-shrink:0}
.drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(6px);z-index:500}
.drawer{position:fixed;left:0;top:0;bottom:0;width:265px;background:rgba(7,6,15,0.99);border-right:1px solid var(--border);z-index:600;display:flex;flex-direction:column;transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1)}
.drawer.open{transform:translateX(0)}.drawer-overlay.open{display:block}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:18px 16px;border-bottom:1px solid var(--border2)}
.drawer-brand{font-size:.8rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.drawer-close{width:32px;height:32px;border-radius:8px;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:var(--red);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem}
.drawer-user{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border2)}
.d-av-ph2{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;flex-shrink:0}
.d-uname{font-size:.85rem;font-weight:800}.d-role2{font-size:.65rem;color:var(--accent2);font-weight:700;text-transform:uppercase}
.drawer-nav2{flex:1;overflow-y:auto;padding:8px 10px}
.d-sec2{font-size:.6rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 8px 5px}
.d-link2{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;margin-bottom:2px}
.d-link2:hover,.d-link2.active{background:rgba(139,92,246,0.1);color:var(--accent2)}.d-link2 i{width:18px;text-align:center}
.drawer-bot{padding:12px 10px;border-top:1px solid var(--border2)}
.d-out{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:700;color:var(--red);text-decoration:none}
.d-out:hover{background:rgba(248,113,113,0.08)}
@media(max-width:768px){.sidebar{display:none!important}.main{margin-left:0!important;padding-bottom:90px!important}.mob-topbar{display:flex!important}.topbar{display:none!important}.mob-nav{display:flex!important}}
</style>
</head>
<body>
<div id="c-dot"></div>
<div id="c-ring"></div>
<div id="sp"></div>
<canvas id="pc"></canvas>
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="sideDrawer">
  <div class="drawer-head"><div class="drawer-brand">Arigato Admin</div><div class="drawer-close" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></div></div>
  <div class="drawer-user">
    <div class="d-av-ph2" id="d-letter">A</div>
    <div><div class="d-uname" id="d-name">Admin</div><div class="d-role2">Admin</div></div>
  </div>
  <nav class="drawer-nav2">
    <div class="d-sec2">Overview</div>
    <a href="dashboard.php" class="d-link2 "><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="analytics.php" class="d-link2 active"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    <div class="d-sec2">Content</div>
    <a href="upload_prompt.php" class="d-link2 "><i class="fa-solid fa-upload"></i> Upload Prompt</a>
    <a href="manage_prompts.php" class="d-link2 "><i class="fa-solid fa-list-check"></i> Manage Prompts</a>
    <a href="prompt_links.php" class="d-link2 "><i class="fa-solid fa-link"></i> Prompt Links</a>
    <a href="potd_manager.php" class="d-link2 "><i class="fa-solid fa-sun"></i> POTD Manager</a>
    <div class="d-sec2">Blog</div>
    <a href="blog_admin.php" class="d-link2"><i class="fa-solid fa-pen-nib"></i> Blog Admin</a>
    <a href="blog_create.php" class="d-link2"><i class="fa-solid fa-plus"></i> New Post</a>
    <div class="d-sec2">Users</div>
    <a href="user_management.php" class="d-link2 "><i class="fa-solid fa-users"></i> Users</a>
    <div class="d-sec2">Tools</div>
    <a href="index.php" class="d-link2" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
  </nav>
  <div class="drawer-bot"><a href="login.php?logout=1" class="d-out"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
</div>

<!-- MOBILE TOP BAR -->
<div class="mob-topbar">
  <div class="mob-menu-btn" onclick="openDrawer()"><i class="fa-solid fa-bars"></i></div>
  <div class="mob-page-title"><i class="fa-solid fa-chart-line" style="-webkit-text-fill-color:var(--accent2);margin-right:6px"></i>Analytics</div>
  <a href="index.php" class="mob-home-btn" target="_blank"><i class="fa-solid fa-house"></i> Site</a>
</div>
<aside class="sidebar">
  <div class="sb-logo"><div class="sb-brand"><i class="fa-solid fa-shield-halved"></i> <span>Arigato Admin</span></div></div>
  <div class="sb-admin">
    <?php
      $__sn = $_SESSION['username'] ?? ($_SESSION['user_name'] ?? 'Admin');
      $__sa = $_SESSION['profile_image'] ?? ($_SESSION['avatar'] ?? '');
      if(empty($__sa)){
        try{
          $__q=$pdo->prepare("SELECT username,avatar,profile_image FROM users WHERE id=? LIMIT 1");
          $__q->execute([$_SESSION['user_id']??0]);
          $__u=$__q->fetch(PDO::FETCH_ASSOC);
          if($__u){$__sn=$__u['username']??$__sn;$__sa=$__u['profile_image']??$__u['avatar']??'';}
        }catch(Exception $__e){}
      }
    ?>
    <?php if(!empty($__sa)): ?><img src="<?= htmlspecialchars($__sa) ?>" class="sb-av" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--accent);flex-shrink:0" alt="">
    <?php else: ?><div class="sb-av-ph"><?= strtoupper(substr($__sn,0,1)) ?></div><?php endif; ?>
    <div><div class="sb-uname"><?= htmlspecialchars($__sn) ?></div><div class="sb-role">Administrator</div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-sec">Overview</div>
    <a href="dashboard.php" class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
    <a href="analytics.php" class="sb-link active"><i class="fa-solid fa-chart-line"></i> <span>Analytics</span></a>
    <div class="sb-sec">Content</div>
    <a href="upload_prompt.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="manage_prompts.php" class="sb-link"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
    <a href="prompt_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="potd_manager.php" class="sb-link"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
    <div class="sb-sec">Blog</div>
    <a href="blog_admin.php" class="sb-link"><i class="fa-solid fa-pen-nib"></i> <span>Blog Admin</span></a>
    <a href="blog_create.php" class="sb-link"><i class="fa-solid fa-plus"></i> <span>New Post</span></a>
    <div class="sb-sec">Users</div>
    <a href="user_management.php" class="sb-link"><i class="fa-solid fa-users"></i> <span>Users</span></a>
    <div class="sb-sec">Tools</div>
    
    <a href="index.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Site</span></a>
  </nav>
  <div class="sb-bottom"><a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-chart-line" style="color:var(--accent2);-webkit-text-fill-color:var(--accent2)"></i> Analytics</div>
    <div style="font-size:.72rem;font-weight:700;color:var(--muted);background:rgba(15,13,30,0.8);border:1px solid var(--border2);padding:6px 14px;border-radius:100px"><i class="fa-regular fa-clock"></i> <?= date('D, d M Y') ?> IST</div>
    <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:9px;font-size:.75rem;font-weight:800;border:1px solid rgba(139,92,246,0.22);background:rgba(139,92,246,0.07);color:var(--accent2);text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
  </div>

  <!-- 12 STAT CARDS -->
  <div class="stats-grid">
    <div class="scard s-purple"><div class="sc-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div><div class="sc-val" data-val="<?= $total_prompts ?>"><?= $total_prompts ?></div><div class="sc-lbl">Total Prompts</div><div class="sc-sub">+<?= $weekly_p ?> this week</div></div>
    <div class="scard s-pink"><div class="sc-icon"><i class="fa-solid fa-heart"></i></div><div class="sc-val" data-val="<?= $total_likes ?>"><?= number_format($total_likes) ?></div><div class="sc-lbl">Total Likes</div></div>
    <div class="scard s-cyan"><div class="sc-icon"><i class="fa-solid fa-users"></i></div><div class="sc-val" data-val="<?= $total_users ?>"><?= $total_users ?></div><div class="sc-lbl">Total Users</div><div class="sc-sub">+<?= $weekly_u ?> this week</div></div>
    <div class="scard s-orange"><div class="sc-icon"><i class="fa-solid fa-lock-open"></i></div><div class="sc-val" data-val="<?= $total_unlocks ?>"><?= number_format($total_unlocks) ?></div><div class="sc-lbl">Total Unlocks</div></div>
    <div class="scard s-green"><div class="sc-icon"><i class="fa-solid fa-bookmark"></i></div><div class="sc-val" data-val="<?= $total_saves ?>"><?= number_format($total_saves) ?></div><div class="sc-lbl">Total Saves</div></div>
    <div class="scard s-purple"><div class="sc-icon"><i class="fa-solid fa-eye"></i></div><div class="sc-val" data-val="<?= $total_views ?>"><?= number_format($total_views) ?></div><div class="sc-lbl">Total Views</div></div>
    <div class="scard s-yellow"><div class="sc-icon"><i class="fa-solid fa-copy"></i></div><div class="sc-val" data-val="<?= $total_copies ?>"><?= number_format($total_copies) ?></div><div class="sc-lbl">Total Copies</div></div>
    <div class="scard s-cyan"><div class="sc-icon"><i class="fa-solid fa-share-nodes"></i></div><div class="sc-val" data-val="<?= $total_shares ?>"><?= number_format($total_shares) ?></div><div class="sc-lbl">Total Shares</div></div>
    <div class="scard s-orange"><div class="sc-icon"><i class="fa-solid fa-bolt"></i></div><div class="sc-val" data-val="<?= $monthly_p ?>"><?= $monthly_p ?></div><div class="sc-lbl">Prompts (30d)</div></div>
    <div class="scard s-green"><div class="sc-icon"><i class="fa-solid fa-user-plus"></i></div><div class="sc-val" data-val="<?= $monthly_u ?>"><?= $monthly_u ?></div><div class="sc-lbl">Users (30d)</div></div>
    <div class="scard s-yellow"><div class="sc-icon"><i class="fa-solid fa-route"></i></div><div class="sc-val" data-val="<?= round($avg_journey) ?>"><?= round($avg_journey) ?></div><div class="sc-lbl">Avg Journey (days)</div></div>
    <?php if($most_liked_r): ?>
    <div class="scard s-pink s-span2"><div class="sc-icon"><i class="fa-solid fa-trophy"></i></div><div class="sc-val" style="font-size:1rem;font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:3px"><?= htmlspecialchars($most_liked['title']??'—') ?></div><div class="sc-lbl">Most Liked Prompt — <?= (int)($most_liked['likes_count']??0) ?> Likes</div></div>
    <?php endif; ?>
  </div>

  <!-- ALERTS: Milestones + Spike Days + Dead + Churn -->
  <div class="alert-grid">
    <!-- MILESTONES -->
    <div class="alert-card">
      <div class="alert-title"><i class="fa-solid fa-trophy" style="color:var(--yellow)"></i> User Milestones</div>
      <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:10px">
        <?php foreach($reached_ms as $m): ?><span class="ms-pill ms-done"><i class="fa-solid fa-check"></i> <?= $m ?>+</span><?php endforeach; ?>
      </div>
      <?php if($next_ms): ?>
      <div style="font-size:.72rem;color:var(--muted);margin-bottom:6px">Next target:</div>
      <div style="display:flex;align-items:center;gap:10px">
        <span class="ms-pill ms-next"><i class="fa-solid fa-flag"></i> <?= $next_ms ?>+ users</span>
        <div style="flex:1;height:6px;background:rgba(255,255,255,0.05);border-radius:100px;overflow:hidden"><div style="height:100%;background:linear-gradient(90deg,var(--yellow),var(--orange));width:<?= min(100,round($total_users/$next_ms*100)) ?>%;border-radius:100px"></div></div>
        <span style="font-size:.68rem;font-weight:900;color:var(--yellow)"><?= $total_users ?>/<?= $next_ms ?></span>
      </div>
      <?php endif; ?>
    </div>
    <!-- SPIKE DAYS -->
    <div class="alert-card">
      <div class="alert-title"><i class="fa-solid fa-fire" style="color:var(--orange)"></i> Spike Days (50+ signups)</div>
      <?php if(empty($spike_days)): ?>
      <div style="font-size:.78rem;color:var(--muted)"><i class="fa-solid fa-circle-info"></i> No spike days yet (50+ signups in a day).</div>
      <?php else: ?>
      <?php foreach($spike_days as $sd): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border2)">
        <span style="font-size:.78rem;font-weight:700;color:var(--text)"><?= htmlspecialchars($sd['d']??'') ?></span>
        <span style="font-size:.78rem;font-weight:900;color:var(--orange)"><i class="fa-solid fa-arrow-up"></i> <?= (int)($sd['cnt']??0) ?></span>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <!-- DEAD PROMPTS ALERT -->
    <div class="alert-card" style="border-color:rgba(248,113,113,0.18)">
      <div class="alert-title"><i class="fa-solid fa-skull" style="color:var(--red)"></i> Dead Prompts (0 unlocks/30d)</div>
      <?php if(empty($dead_prompts)): ?>
      <div style="font-size:.78rem;color:var(--muted)"><i class="fa-solid fa-party-horn" style="color:var(--green)"></i> No dead prompts! All active.</div>
      <?php else: ?>
      <?php foreach(array_slice($dead_prompts,0,5) as $dp): ?>
      <div style="font-size:.75rem;padding:5px 0;border-bottom:1px solid var(--border2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted)"><?= htmlspecialchars($dp['title']??'') ?></div>
      <?php endforeach; ?>
      <?php if(count($dead_prompts)>5): ?><div style="font-size:.68rem;color:var(--red);margin-top:6px">+ <?= count($dead_prompts)-5 ?> more</div><?php endif; ?>
      <?php endif; ?>
    </div>
    <!-- CHURN RISK -->
    <div class="alert-card" style="border-color:rgba(251,191,36,0.18)">
      <div class="alert-title"><i class="fa-solid fa-user-clock" style="color:var(--yellow)"></i> Churn Risk (8-30d inactive)</div>
      <?php if(empty($churn_users)): ?>
      <div style="font-size:.78rem;color:var(--muted)"><i class="fa-solid fa-circle-check" style="color:var(--green)"></i> No churn risk users detected.</div>
      <?php else: ?>
      <?php foreach(array_slice($churn_users,0,5) as $cu): ?>
      <div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid var(--border2)">
        <div style="font-size:.75rem;font-weight:700;color:var(--text);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($cu['username']??'User') ?></div>
        <div style="font-size:.65rem;color:var(--yellow)"><?= htmlspecialchars($cu['email']??'') ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- 4 LINE TREND CHARTS -->
  <div class="grid-2">
    <div class="card" style="margin-bottom:0"><div class="card-head"><div class="card-title"><i class="fa-solid fa-bookmark"></i> Saves per Day (30d)</div></div><div class="chart-scroll"><canvas id="savesChart" height="160"></canvas></div></div>
    <div class="card" style="margin-bottom:0"><div class="card-head"><div class="card-title"><i class="fa-solid fa-lock-open"></i> Unlocks per Day (30d)</div></div><div class="chart-scroll"><canvas id="unlocksPerDayChart" height="160"></canvas></div></div>
  </div>
  <div style="margin-bottom:18px"></div>
  <div class="grid-2">
    <div class="card" style="margin-bottom:0"><div class="card-head"><div class="card-title"><i class="fa-solid fa-user-plus"></i> User Growth (30d)</div></div><div class="chart-scroll"><canvas id="userLineChart" height="160"></canvas></div></div>
    <div class="card" style="margin-bottom:0"><div class="card-head"><div class="card-title"><i class="fa-solid fa-upload"></i> Prompt Uploads (30d)</div></div><div class="chart-scroll"><canvas id="promptLineChart" height="160"></canvas></div></div>
  </div>
  <div style="margin-bottom:18px"></div>

  <!-- BLOG + HOUR CHARTS -->
  <div class="grid-2">
    <div class="card" style="margin-bottom:0"><div class="card-head"><div class="card-title"><i class="fa-solid fa-newspaper"></i> Blog Reads Top 10</div></div><div class="chart-scroll"><canvas id="blogChart" height="180"></canvas></div></div>
    <div class="card" style="margin-bottom:0"><div class="card-head"><div class="card-title"><i class="fa-solid fa-clock"></i> New Users by Hour (IST)</div></div><div class="chart-scroll"><canvas id="hourChart" height="180"></canvas></div></div>
  </div>
  <div style="margin-bottom:18px"></div>

  <!-- RETENTION + NEW vs RETURN -->
  <div class="grid-2">
    <div class="card" style="margin-bottom:0">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-rotate-right"></i> User Retention</div></div>
      <div class="ret-row">
        <div class="ret-label">Day 1</div>
        <div class="ret-bar-wrap"><div class="ret-bar" style="width:<?= $ret_d1 ?>%;background:linear-gradient(90deg,var(--accent),var(--pink))"></div></div>
        <div class="ret-pct" style="color:var(--accent2)"><?= $ret_d1 ?>%</div>
      </div>
      <div class="ret-row">
        <div class="ret-label">Day 7</div>
        <div class="ret-bar-wrap"><div class="ret-bar" style="width:<?= $ret_d7 ?>%;background:linear-gradient(90deg,var(--cyan),var(--accent))"></div></div>
        <div class="ret-pct" style="color:var(--cyan)"><?= $ret_d7 ?>%</div>
      </div>
      <div class="ret-row">
        <div class="ret-label">Day 30</div>
        <div class="ret-bar-wrap"><div class="ret-bar" style="width:<?= $ret_d30 ?>%;background:linear-gradient(90deg,var(--green),var(--cyan))"></div></div>
        <div class="ret-pct" style="color:var(--green)"><?= $ret_d30 ?>%</div>
      </div>
      <div style="font-size:.65rem;color:var(--muted);margin-top:12px"><i class="fa-solid fa-circle-info"></i> Based on last_active vs signup date.</div>
    </div>
    <div class="card" style="margin-bottom:0"><div class="card-head"><div class="card-title"><i class="fa-solid fa-circle-half-stroke"></i> New vs Returning (7d)</div></div><canvas id="newReturnChart" height="160"></canvas></div>
  </div>
  <div style="margin-bottom:18px"></div>

  <!-- FULL WIDTH CHARTS -->
  <div class="card"><div class="card-head"><div class="card-title"><i class="fa-solid fa-heart"></i> Top 10 Prompts by Likes</div></div><div class="chart-scroll"><canvas id="barChart" height="120"></canvas></div></div>
  <div class="card"><div class="card-head"><div class="card-title"><i class="fa-solid fa-lock-open"></i> Top 10 Most Unlocked Prompts</div></div><div class="chart-scroll"><canvas id="unlockChart" height="120"></canvas></div></div>
  <div class="card"><div class="card-head"><div class="card-title"><i class="fa-solid fa-tag"></i> Prompt Type Breakdown</div></div><div style="max-width:400px;margin:0 auto"><canvas id="typeChart" height="200"></canvas></div></div>

  <!-- 4 TABLES -->
  <div class="grid-2">
    <div class="card" style="margin-bottom:0">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-bookmark"></i> Top Saved Prompts</div></div>
      <div style="overflow-x:auto">
      <table class="dtable"><thead><tr><th>#</th><th>Prompt</th><th>Saves</th></tr></thead><tbody>
      <?php foreach($top_saved as $i=>$ts): ?>
      <tr><td style="font-weight:900;color:var(--accent2)"><?= $i+1 ?></td><td style="font-weight:700;color:var(--text)"><?= htmlspecialchars($ts['title']??'') ?></td><td style="font-weight:900;color:var(--green)"><?= (int)($ts['save_count']??0) ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
      </div>
    </div>
    <div class="card" style="margin-bottom:0">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-percent"></i> Unlock-to-View Ratio</div></div>
      <div style="overflow-x:auto">
      <table class="dtable"><thead><tr><th>Prompt</th><th>Views</th><th>Unlocks</th><th>Ratio</th></tr></thead><tbody>
      <?php foreach($unlock_view as $uv): $ratio=$uv['view_count']>0?round($uv['uc']/$uv['view_count']*100):0; ?>
      <tr><td style="font-weight:700;color:var(--text);max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($uv['title']??'') ?></td><td style="color:var(--cyan)"><?= number_format($uv['view_count']??0) ?></td><td style="color:var(--orange)"><?= (int)($uv['uc']??0) ?></td><td style="font-weight:900;color:<?= $ratio>50?'var(--green)':($ratio>20?'var(--yellow)':'var(--red)') ?>"><?= $ratio ?>%</td></tr>
      <?php endforeach; ?>
      </tbody></table>
      </div>
    </div>
  </div>
  <div style="margin-bottom:18px"></div>
  <div class="grid-2">
    <div class="card" style="margin-bottom:0">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-crown"></i> Power Users (5+ unlocks)</div></div>
      <div style="overflow-x:auto">
      <table class="dtable"><thead><tr><th>#</th><th>User</th><th>Email</th><th>Unlocks</th></tr></thead><tbody>
      <?php foreach($power_users as $i=>$pu): ?>
      <tr><td style="font-weight:900;color:var(--yellow)"><?= $i+1 ?></td><td style="font-weight:700;color:var(--text)"><?= htmlspecialchars($pu['username']??'') ?></td><td style="color:var(--muted);font-size:.68rem"><?= htmlspecialchars($pu['email']??'') ?></td><td style="font-weight:900;color:var(--orange)"><?= (int)($pu['cnt']??0) ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
      </div>
    </div>
    <div class="card" style="margin-bottom:0">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-hourglass"></i> Prompt Age vs Performance</div></div>
      <div style="overflow-x:auto">
      <table class="dtable"><thead><tr><th>Prompt</th><th>Age</th><th>Unlocks</th><th>Likes</th></tr></thead><tbody>
      <?php foreach($age_perf as $ap): ?>
      <tr><td style="font-weight:700;color:var(--text);max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($ap['title']??'') ?></td><td style="color:var(--muted)"><?= (int)($ap['age_days']??0) ?>d</td><td style="color:var(--orange)"><?= (int)($ap['unlocks']??0) ?></td><td style="color:var(--red)"><?= (int)($ap['likes']??0) ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
      </div>
    </div>
  </div>
</main>

<nav class="mob-nav">
  <a href="dashboard.php" class="mn-link"><i class="fa-solid fa-gauge-high"></i><span>Home</span></a>
  <a href="manage_prompts.php" class="mn-link"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Prompts</span></a>
  <a href="user_management.php" class="mn-link"><i class="fa-solid fa-users"></i><span>Users</span></a>
  <a href="analytics.php" class="mn-link active"><i class="fa-solid fa-chart-line"></i><span>Stats</span></a>
  <a href="upload_prompt.php" class="mn-link" style="color:var(--accent2)"><i class="fa-solid fa-plus"></i><span>Upload</span></a>
</nav>

<script>
window.addEventListener('scroll',()=>{const h=document.documentElement;document.getElementById('sp').style.width=(h.scrollTop/(h.scrollHeight-h.clientHeight)*100)+'%';},{passive:true});
(function(){const c=document.getElementById('pc');if(!c)return;const ctx=c.getContext('2d');let W,H,pts=[];function rs(){W=c.width=window.innerWidth;H=c.height=window.innerHeight}rs();window.addEventListener('resize',rs);class P{constructor(){this.reset()}reset(){this.x=Math.random()*W;this.y=Math.random()*H;this.vx=(Math.random()-.5)*.3;this.vy=(Math.random()-.5)*.3;this.r=Math.random()*1.2+.3;this.a=Math.random()*.35+.1;const cols=['139,92,246','244,114,182','34,211,238'];this.col=cols[Math.floor(Math.random()*cols.length)]}update(){this.x+=this.vx;this.y+=this.vy;if(this.x<0||this.x>W||this.y<0||this.y>H)this.reset()}draw(){ctx.beginPath();ctx.arc(this.x,this.y,this.r,0,Math.PI*2);ctx.fillStyle=`rgba(${this.col},${this.a})`;ctx.fill()}}for(let i=0;i<50;i++)pts.push(new P());function loop(){ctx.clearRect(0,0,W,H);pts.forEach(p=>{p.update();p.draw()});requestAnimationFrame(loop)}loop();})();

// CountUp
document.querySelectorAll('.sc-val[data-val]').forEach(el=>{
  const n=parseInt(el.dataset.val)||0;
  if(n>0){el.textContent='0';let s=0,step=Math.max(1,n/60);const t=setInterval(()=>{s+=step;if(s>=n){s=n;clearInterval(t)}el.textContent=Math.floor(s).toLocaleString()},16)}
});

// Chart defaults
Chart.defaults.color='#9490bb';
Chart.defaults.borderColor='rgba(139,92,246,0.1)';

function mkLine(id,labels,data,color='#8b5cf6',fill=true){
  new Chart(document.getElementById(id),{
    type:'line',
    data:{labels,datasets:[{data,borderColor:color,backgroundColor:fill?color.replace(')',',0.1)').replace('rgb','rgba'):'transparent',borderWidth:2,fill,tension:.4,pointBackgroundColor:color,pointRadius:3,pointHoverRadius:5}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(139,92,246,0.06)'},ticks:{color:'#9490bb',font:{size:9},maxTicksLimit:8}},y:{grid:{color:'rgba(139,92,246,0.06)'},ticks:{color:'#9490bb',font:{size:9}}}}}
  });
}
function mkBar(id,labels,data,color='rgba(139,92,246,0.5)',borderColor='#8b5cf6'){
  new Chart(document.getElementById(id),{
    type:'bar',
    data:{labels,datasets:[{data,backgroundColor:color,borderColor,borderWidth:1,borderRadius:5}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(139,92,246,0.06)'},ticks:{color:'#9490bb',font:{size:9},maxRotation:30}},y:{grid:{color:'rgba(139,92,246,0.06)'},ticks:{color:'#9490bb',font:{size:9}}}}}
  });
}

window.addEventListener('DOMContentLoaded',()=>{
  const spd=<?= json_encode($spd) ?>;
  const upd=<?= json_encode($upd) ?>;
  const ug=<?= json_encode($ug) ?>;
  const pg=<?= json_encode($pg) ?>;
  mkLine('savesChart',JSON.parse(spd.l),JSON.parse(spd.d),'#8b5cf6');
  mkLine('unlocksPerDayChart',JSON.parse(upd.l),JSON.parse(upd.d),'#f472b6');
  mkLine('userLineChart',JSON.parse(ug.l),JSON.parse(ug.d),'#22d3ee');
  mkLine('promptLineChart',JSON.parse(pg.l),JSON.parse(pg.d),'#fb923c');

  mkBar('blogChart',<?= $blog_labels ?>,<?= $blog_data ?>,'rgba(244,114,182,0.5)','#f472b6');
  mkBar('hourChart',[...Array(24).keys()].map(h=>h+':00'),<?= json_encode($hmap) ?>,'rgba(34,211,238,0.4)','#22d3ee');

  new Chart(document.getElementById('newReturnChart'),{
    type:'doughnut',
    data:{labels:['New Users','Returning'],datasets:[{data:[<?= $new_7 ?>,<?= $return_7 ?>],backgroundColor:['#8b5cf6','#f472b6'],borderColor:'rgba(15,13,30,0.5)',borderWidth:2}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:'#9490bb',font:{size:11},padding:16}}}}
  });

  mkBar('barChart',<?= $bar_labels ?>,<?= $bar_data ?>,'rgba(139,92,246,0.55)','#8b5cf6');
  mkBar('unlockChart',<?= $ul_labels ?>,<?= $ul_data ?>,'rgba(251,146,60,0.5)','#fb923c');

  new Chart(document.getElementById('typeChart'),{
    type:'doughnut',
    data:{labels:<?= $type_labels ?>,datasets:[{data:<?= $type_data ?>,backgroundColor:['#8b5cf6','#fbbf24','#22d3ee','#60a5fa'],borderColor:'rgba(15,13,30,0.5)',borderWidth:2}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:'#9490bb',font:{size:11},padding:16}}}}
  });
});
function openDrawer(){document.getElementById('sideDrawer').classList.add('open');document.getElementById('drawerOverlay').classList.add('open');}
function closeDrawer(){document.getElementById('sideDrawer').classList.remove('open');document.getElementById('drawerOverlay').classList.remove('open');}
</script>
</html>


