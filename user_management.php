<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php"); exit();
}

// -- AJAX: user activity --
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

// -- Growth chart: last 30 days (IST) --
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

// -- Top 10 users by unlocks --
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
$total_alien  = count(array_filter($users, fn($u) => empty($u['gender']) || !in_array(strtolower($u['gender']), ['male','female','nonbinary'])));
$total_admin  = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'admin'));
// New today
$new_today = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(CONVERT_TZ(created_at,'+00:00','+05:30')) = CURDATE()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include_once "gtag.php"; ?>
<style>
:root{--bg:#07060f;--surface:#0f0d1e;--surface2:#15122a;--border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);--accent:#8b5cf6;--accent2:#c084fc;--pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;--yellow:#fbbf24;--orange:#fb923c;--red:#f87171;--text:#e2e0ff;--muted:#9490bb;--font:'Inter',sans-serif}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:var(--font);overflow-x:hidden;min-height:100vh}
#sp{position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,var(--accent),var(--pink),var(--cyan));z-index:9999;transition:width .1s;box-shadow:0 0 10px var(--accent)}
#pc{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.5}
/* SIDEBAR */
.sidebar{position:fixed;left:0;top:0;bottom:0;width:220px;background:rgba(7,6,15,0.98);border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column}
.sb-logo{padding:20px 18px 14px;border-bottom:1px solid var(--border2)}
.sb-brand{font-size:.72rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:flex;align-items:center;gap:8px}
.sb-brand i{-webkit-text-fill-color:#a78bfa}
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
.main{margin-left:220px;min-height:100vh;padding:28px 32px 80px;position:relative;z-index:1}
/* TOPBAR */
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:24px;flex-wrap:wrap}
.tb-title{font-size:1.5rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1}
.tb-time{font-size:.72rem;font-weight:700;color:var(--muted);background:rgba(15,13,30,0.8);border:1px solid var(--border2);padding:6px 14px;border-radius:100px}
/* STAT GRID */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:20px}
.scard{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:18px;transition:all .3s;position:relative;overflow:hidden;cursor:default}
.scard::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;opacity:0;transition:opacity .3s;border-radius:16px 16px 0 0}
.scard:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,0.3)}
.scard:hover::before{opacity:1}
.sc-icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:11px}
.sc-val{font-size:1.8rem;font-weight:900;line-height:1;margin-bottom:3px}
.sc-lbl{font-size:.63rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}
.s-cyan .sc-icon{background:rgba(34,211,238,0.1);color:var(--cyan)}.s-cyan .sc-val{color:var(--cyan)}.s-cyan::before{background:var(--cyan)}
.s-green .sc-icon{background:rgba(74,222,128,0.1);color:var(--green)}.s-green .sc-val{color:var(--green)}.s-green::before{background:var(--green)}
.s-blue .sc-icon{background:rgba(96,165,250,0.1);color:#60a5fa}.s-blue .sc-val{color:#60a5fa}.s-blue::before{background:#60a5fa}
.s-pink .sc-icon{background:rgba(244,114,182,0.1);color:var(--pink)}.s-pink .sc-val{color:var(--pink)}.s-pink::before{background:var(--pink)}
.s-purple .sc-icon{background:rgba(139,92,246,0.12);color:var(--accent2)}.s-purple .sc-val{color:var(--accent2)}.s-purple::before{background:var(--accent2)}
/* DUAL GRID */
.dual-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
/* CARD */
.card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:18px;backdrop-filter:blur(8px);transition:border-color .3s}
.card:hover{border-color:rgba(139,92,246,0.3)}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border2);gap:10px;flex-wrap:wrap}
.card-title{font-size:.88rem;font-weight:900;display:flex;align-items:center;gap:8px;color:var(--text)}
.card-title i{color:var(--accent2)}
/* SEARCH BAR */
.search-row{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.srch-inp{flex:2;min-width:180px;padding:10px 16px;background:rgba(15,13,30,0.8);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:var(--font);font-size:.85rem;font-weight:500;outline:none;transition:all .2s}
.srch-inp:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(139,92,246,0.1)}
.srch-inp::placeholder{color:var(--muted)}
.srch-sel{display:none}
/* CUSTOM DROPDOWN */
.csel-wrap{flex:1;min-width:120px;position:relative;user-select:none}
.csel-trigger{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 14px;background:rgba(15,13,30,0.8);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:var(--font);font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}
.csel-trigger:hover,.csel-wrap.open .csel-trigger{border-color:var(--accent);background:rgba(139,92,246,0.07);color:var(--accent2)}
.csel-trigger i{font-size:.65rem;color:var(--muted);transition:transform .2s}
.csel-wrap.open .csel-trigger i{transform:rotate(180deg);color:var(--accent2)}
.csel-menu{display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;background:rgba(10,8,22,0.98);border:1px solid var(--border);border-radius:12px;overflow:hidden;z-index:999;backdrop-filter:blur(20px);box-shadow:0 16px 40px rgba(0,0,0,0.5)}
.csel-wrap.open .csel-menu{display:block}
.csel-opt{padding:9px 14px;font-size:.8rem;font-weight:600;color:var(--muted);cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:8px}
.csel-opt:hover{background:rgba(139,92,246,0.1);color:var(--text)}
.csel-opt.selected{background:rgba(139,92,246,0.12);color:var(--accent2);font-weight:800}
.csel-opt:not(:last-child){border-bottom:1px solid var(--border2)}
/* TABLE */
.dtable{width:100%;border-collapse:collapse;font-size:.78rem}
.dtable th{background:rgba(139,92,246,0.07);color:var(--accent2);font-weight:800;font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;padding:9px 13px;text-align:left;border-bottom:1px solid var(--border)}
.dtable td{padding:10px 13px;border-bottom:1px solid var(--border2);color:var(--muted);vertical-align:middle}
.dtable tr:last-child td{border-bottom:none}
.dtable tr:hover td{background:rgba(139,92,246,0.03)}
.u-av{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid rgba(139,92,246,0.3)}
.u-n{font-weight:800;color:var(--text);font-size:.83rem}
.u-e{font-size:.68rem;color:var(--muted);margin-top:1px}
.rbadge{display:inline-flex;align-items:center;padding:2px 9px;border-radius:100px;font-size:.6rem;font-weight:900;text-transform:uppercase;border:1px solid}
.rb-a{background:rgba(251,191,36,0.1);color:var(--yellow);border-color:rgba(251,191,36,0.22)}
.rb-u{background:rgba(139,92,246,0.08);color:var(--accent2);border-color:var(--border2)}
.gi-m{color:var(--cyan)}.gi-f{color:var(--pink)}.gi-a{color:var(--muted)}
.d-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:9px;font-size:.73rem;font-weight:800;border:1px solid;transition:all .2s;cursor:pointer;background:transparent;font-family:var(--font)}
.db-p{color:var(--accent2);border-color:rgba(139,92,246,0.25);background:rgba(139,92,246,0.07)}
.db-p:hover{background:rgba(139,92,246,0.15)}
.db-full{width:100%;justify-content:center;padding:11px}
/* PAGINATION */
.pagination{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:12px 0 4px;border-top:1px solid var(--border2);margin-top:10px;font-size:.75rem;font-weight:700;color:var(--muted)}
.pag-btns{display:flex;gap:5px;flex-wrap:wrap}
.pag-btn{padding:5px 11px;border-radius:8px;border:1px solid var(--border2);background:transparent;color:var(--muted);cursor:pointer;font-family:var(--font);font-size:.72rem;font-weight:700;transition:all .2s}
.pag-btn:hover{border-color:var(--accent);color:var(--accent2)}
.pag-btn.active{background:rgba(139,92,246,0.15);color:var(--accent2);border-color:var(--border)}
.pag-btn:disabled{opacity:.35;cursor:default}
/* TOP USERS */
.top-user-row{display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid var(--border2)}
.top-user-row:last-child{border-bottom:none}
.rank-num{width:24px;font-size:.75rem;font-weight:900;text-align:center;flex-shrink:0}
.tu-av{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid rgba(139,92,246,0.3);flex-shrink:0}
.tu-ph{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.78rem;color:#fff;flex-shrink:0}
.tu-name{font-weight:800;font-size:.82rem;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tu-unlocks{margin-left:auto;font-size:.72rem;font-weight:900;color:var(--accent2);white-space:nowrap;flex-shrink:0}
/* GHOST SECTION */
.ghost-row{display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid var(--border2)}
.ghost-row:last-child{border-bottom:none}
.g-ph{width:32px;height:32px;border-radius:50%;background:rgba(139,92,246,0.1);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.75rem;color:var(--accent2);flex-shrink:0}
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
/* SCROLLBAR */
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:rgba(139,92,246,0.4);border-radius:10px}
/* MOBILE BOTTOM NAV */
.mob-nav{display:none;position:fixed;bottom:0;left:0;right:0;background:rgba(7,6,15,0.97);border-top:1px solid var(--border);z-index:500;padding:8px 0 max(8px,env(safe-area-inset-bottom));flex-direction:row;justify-content:space-around;align-items:center}
.mn-link{display:flex;flex-direction:column;align-items:center;gap:3px;font-size:.6rem;font-weight:700;color:var(--muted);text-decoration:none;padding:4px 8px;border-radius:10px;transition:all .2s;min-width:48px}
.mn-link.active,.mn-link:hover{color:var(--accent2)}
.mn-link i{font-size:1.1rem}
.mn-more{color:var(--accent2)}
/* RESPONSIVE */
@media(max-width:1100px){.dual-grid{grid-template-columns:1fr}}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-admin{padding:10px;justify-content:center}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px;padding:20px 16px 80px}.stats-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:600px){
  .sidebar{display:none}.main{margin-left:0;padding:14px 14px 80px}
  .mob-nav{display:flex}
  .stats-grid{grid-template-columns:1fr 1fr}
  .dual-grid{grid-template-columns:1fr}
  .search-row{flex-direction:column}
  .srch-inp,.srch-sel{width:100%;min-width:unset}
  .tb-title{font-size:1.2rem}
  .topbar{margin-bottom:16px}
}
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

<aside class="sidebar">
  <div class="sb-logo"><div class="sb-brand"><i class="fa-solid fa-shield-halved"></i> <span>Arigato Admin</span></div></div>
  <div class="sb-admin">
    <?php
      $__sn = $_SESSION['username'] ?? 'Admin';
      $__sa = $_SESSION['profile_image'] ?? '';
      if(empty($__sa)){
        try{
          $__q=$pdo->prepare("SELECT username,avatar,profile_image FROM users WHERE id=? LIMIT 1");
          $__q->execute([$_SESSION['user_id']??0]);
          $__u=$__q->fetch(PDO::FETCH_ASSOC);
          if($__u){$__sn=$__u['username']??$__sn;$__sa=$__u['profile_image']??$__u['avatar']??'';}
        }catch(Exception $__e){}
      }
    ?>
    <?php if(!empty($__sa)): ?><img src="<?= htmlspecialchars($__sa) ?>" class="sb-av" alt="">
    <?php else: ?><div class="sb-av-ph"><?= strtoupper(substr($__sn,0,1)) ?></div><?php endif; ?>
    <div><div class="sb-uname"><?= htmlspecialchars($__sn) ?></div><div class="sb-role">Administrator</div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-sec">Overview</div>
    <a href="dashboard.php" class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
    <a href="analytics.php" class="sb-link"><i class="fa-solid fa-chart-line"></i> <span>Analytics</span></a>
    <div class="sb-sec">Content</div>
    <a href="upload_prompt.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="manage_prompts.php" class="sb-link"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
    <a href="prompt_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="potd_manager.php" class="sb-link"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
    <div class="sb-sec">Blog</div>
    <a href="blog_admin.php" class="sb-link"><i class="fa-solid fa-pen-nib"></i> <span>Blog Admin</span></a>
    <a href="blog_create.php" class="sb-link"><i class="fa-solid fa-plus"></i> <span>New Post</span></a>
    <div class="sb-sec">Users</div>
    <a href="user_management.php" class="sb-link active"><i class="fa-solid fa-users"></i> <span>Users</span></a>
    <div class="sb-sec">Tools</div>
    
    <a href="index.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Site</span></a>
  </nav>
  <div class="sb-bottom"><a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-users" style="color:var(--accent2);-webkit-text-fill-color:var(--accent2)"></i> User Management</div>
    <div class="tb-time"><i class="fa-regular fa-clock"></i> <?= date('D, d M Y | h:i A') ?> IST</div>
    <a href="dashboard.php" class="d-btn db-p"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
  </div>

  <!-- STAT CARDS -->
  <div class="stats-grid">
    <div class="scard s-cyan"><div class="sc-icon"><i class="fa-solid fa-users"></i></div><div class="sc-val" data-val="<?= $total ?>"><?= $total ?></div><div class="sc-lbl">Total Users</div></div>
    <div class="scard s-green"><div class="sc-icon"><i class="fa-solid fa-user-plus"></i></div><div class="sc-val" data-val="<?= $new_today ?>"><?= $new_today ?></div><div class="sc-lbl">Joined Today</div></div>
    <div class="scard s-blue"><div class="sc-icon"><i class="fa-solid fa-mars"></i></div><div class="sc-val" data-val="<?= $total_male ?>"><?= $total_male ?></div><div class="sc-lbl">Male</div></div>
    <div class="scard s-pink"><div class="sc-icon"><i class="fa-solid fa-venus"></i></div><div class="sc-val" data-val="<?= $total_female ?>"><?= $total_female ?></div><div class="sc-lbl">Female</div></div>
    <div class="scard s-purple"><div class="sc-icon"><i class="fa-solid fa-user-astronaut"></i></div><div class="sc-val" data-val="<?= $total_alien ?>"><?= $total_alien ?></div><div class="sc-lbl">Alien / Other</div></div>
  </div>

  <!-- DUAL: GROWTH CHART + TOP USERS -->
  <div class="dual-grid">
    <div class="card" style="margin-bottom:0">
      <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-chart-line"></i> User Growth — Last 30 Days</div>
      </div>
      <canvas id="growthChart" height="160"></canvas>
    </div>
    <div class="card" style="margin-bottom:0">
      <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-trophy"></i> Top Users by Unlocks</div>
      </div>
      <?php if(empty($top_users)): ?>
      <div style="text-align:center;color:var(--muted);padding:20px 0;font-size:.82rem"><i class="fa-solid fa-circle-info"></i> No unlock data yet.</div>
      <?php else: ?>
      <?php $rankIcons=['<i class="fa-solid fa-crown" style="color:var(--yellow)"></i>','<i class="fa-solid fa-crown" style="color:#94a3b8"></i>','<i class="fa-solid fa-crown" style="color:var(--orange)"></i>']; ?>
      <?php foreach($top_users as $idx=>$tu): ?>
      <div class="top-user-row">
        <div class="rank-num"><?= $rankIcons[$idx] ?? '<span style="color:var(--muted)">'.(($idx+1)).'</span>' ?></div>
        <?php $tav=!empty($tu['avatar'])?$tu['avatar']:'https://api.dicebear.com/7.x/avataaars/svg?seed='.urlencode($tu['email']??'x'); ?>
        <img class="tu-av" src="<?= htmlspecialchars($tav) ?>" alt="">
        <div class="tu-name"><?= htmlspecialchars($tu['username']??'User') ?></div>
        <div class="tu-unlocks"><i class="fa-solid fa-lock-open" style="font-size:.65rem"></i> <?= $tu['unlock_count'] ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- GHOST USERS -->
  <?php
  try {
    $ghost_total = (int)$pdo->query("
      SELECT COUNT(*) FROM users u WHERE u.role='user'
        AND NOT EXISTS (SELECT 1 FROM unlocked_prompts up WHERE up.user_id=u.id)
        AND NOT EXISTS (SELECT 1 FROM saved_prompts sp WHERE sp.user_id=u.id)
        AND NOT EXISTS (SELECT 1 FROM likes l WHERE l.user_id=u.id)
    ")->fetchColumn();
    $ghost_users = $pdo->query("
      SELECT u.id, u.username, u.email, u.gender, u.created_at
      FROM users u WHERE u.role='user'
        AND NOT EXISTS (SELECT 1 FROM unlocked_prompts up WHERE up.user_id=u.id)
        AND NOT EXISTS (SELECT 1 FROM saved_prompts sp WHERE sp.user_id=u.id)
        AND NOT EXISTS (SELECT 1 FROM likes l WHERE l.user_id=u.id)
      ORDER BY u.created_at DESC LIMIT 500
    ")->fetchAll(PDO::FETCH_ASSOC);
  } catch(Exception $e) { $ghost_users = []; $ghost_total = 0; }
  ?>
  <?php if(!empty($ghost_users)): ?>
  <div class="card" id="ghost-section">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-ghost" style="color:var(--muted)"></i> Ghost Users <span style="font-size:.62rem;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:var(--red);border-radius:100px;padding:2px 9px;font-weight:900;margin-left:6px"><?= $ghost_total ?> never interacted</span></div>
    </div>
    <div id="ghost-list"></div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:12px;border-top:1px solid var(--border2)">
      <button id="ghost-prev" onclick="ghostPage(-1)" style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:9px;font-size:.75rem;font-weight:800;border:1px solid var(--border);background:rgba(139,92,246,0.07);color:var(--accent2);cursor:pointer;font-family:inherit;transition:all .2s">
        <i class="fa-solid fa-chevron-left"></i> Prev
      </button>
      <span id="ghost-page-info" style="font-size:.72rem;font-weight:800;color:var(--muted)"></span>
      <button id="ghost-next" onclick="ghostPage(1)" style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:9px;font-size:.75rem;font-weight:800;border:1px solid var(--border);background:rgba(139,92,246,0.07);color:var(--accent2);cursor:pointer;font-family:inherit;transition:all .2s">
        Next <i class="fa-solid fa-chevron-right"></i>
      </button>
    </div>
  </div>
  <script>
  var ghostData = <?= json_encode(array_values($ghost_users)) ?>;
  var ghostPerPage = 8, ghostCurPage = 0;
  function renderGhost(){
    var list = document.getElementById('ghost-list'); if(!list) return;
    var start = ghostCurPage * ghostPerPage;
    var slice = ghostData.slice(start, start + ghostPerPage);
    var html = '';
    slice.forEach(function(gu){
      var name = gu.username || 'User';
      var d = (gu.created_at||'').substring(0,10);
      html += '<div class="ghost-row">';
      html += '<div class="g-ph">' + name.charAt(0).toUpperCase() + '</div>';
      html += '<div style="flex:1;min-width:0"><div style="font-weight:800;font-size:.82rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)">' + name + '</div>';
      html += '<div style="font-size:.68rem;color:var(--muted)">' + (gu.email||'') + '</div></div>';
      html += '<div style="font-size:.68rem;color:var(--muted);white-space:nowrap">' + d + '</div></div>';
    });
    list.innerHTML = html;
    var tp = Math.ceil(ghostData.length / ghostPerPage);
    document.getElementById('ghost-page-info').textContent = 'Page '+(ghostCurPage+1)+' of '+tp+' ('+ghostData.length+' total)';
    var pp=document.getElementById('ghost-prev'), np=document.getElementById('ghost-next');
    pp.disabled = ghostCurPage===0; pp.style.opacity = ghostCurPage===0?'.4':'1';
    np.disabled = ghostCurPage>=tp-1; np.style.opacity = ghostCurPage>=tp-1?'.4':'1';
  }
  function ghostPage(d){ var tp=Math.ceil(ghostData.length/ghostPerPage); ghostCurPage=Math.max(0,Math.min(tp-1,ghostCurPage+d)); renderGhost(); }
  renderGhost();
  </script>
  <?php endif; ?>

  <!-- ALL USERS TABLE -->
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-table-list"></i> All Users <span style="font-size:.68rem;background:rgba(34,211,238,0.08);border:1px solid rgba(34,211,238,0.18);color:var(--cyan);border-radius:100px;padding:2px 9px;margin-left:6px;font-weight:900"><?= $total ?> Total</span></div>
    </div>
    <div class="search-row">
      <input type="text" id="um-search" class="srch-inp" placeholder="Search by name or email..." oninput="filterUsers()">
      <select id="um-role-filter" class="srch-sel" onchange="filterUsers()"><option value="">All Roles</option><option value="admin">Admin</option><option value="user">User</option></select>
      <div class="csel-wrap" id="csel-role">
        <div class="csel-trigger" onclick="toggleCsel('csel-role')"><span id="csel-role-lbl">All Roles</span><i class="fa-solid fa-chevron-down"></i></div>
        <div class="csel-menu">
          <div class="csel-opt selected" onclick="setCsel('csel-role','','All Roles')"><i class="fa-solid fa-users" style="color:var(--accent2);font-size:.7rem"></i> All Roles</div>
          <div class="csel-opt" onclick="setCsel('csel-role','admin','Admin')"><i class="fa-solid fa-crown" style="color:var(--yellow);font-size:.7rem"></i> Admin</div>
          <div class="csel-opt" onclick="setCsel('csel-role','user','User')"><i class="fa-solid fa-user" style="color:var(--cyan);font-size:.7rem"></i> User</div>
        </div>
      </div>
      <select id="um-gender-filter" class="srch-sel" onchange="filterUsers()"><option value="">All Genders</option><option value="male">Male</option><option value="female">Female</option><option value="alien">Alien / Other</option></select>
      <div class="csel-wrap" id="csel-gender">
        <div class="csel-trigger" onclick="toggleCsel('csel-gender')"><span id="csel-gender-lbl">All Genders</span><i class="fa-solid fa-chevron-down"></i></div>
        <div class="csel-menu">
          <div class="csel-opt selected" onclick="setCsel('csel-gender','','All Genders')"><i class="fa-solid fa-globe" style="color:var(--accent2);font-size:.7rem"></i> All Genders</div>
          <div class="csel-opt" onclick="setCsel('csel-gender','male','Male')"><i class="fa-solid fa-mars" style="color:var(--cyan);font-size:.7rem"></i> Male</div>
          <div class="csel-opt" onclick="setCsel('csel-gender','female','Female')"><i class="fa-solid fa-venus" style="color:var(--pink);font-size:.7rem"></i> Female</div>
          <div class="csel-opt" onclick="setCsel('csel-gender','alien','Alien / Other')"><i class="fa-solid fa-user-astronaut" style="color:var(--muted);font-size:.7rem"></i> Alien / Other</div>
        </div>
      </div>
    </div>
    <div style="overflow-x:auto">
    <table class="dtable" id="um-table">
      <thead><tr><th>#</th><th>Avatar</th><th>Name / Email</th><th>Gender</th><th>Role</th><th>Joined</th><th>Last Active</th><th>Activity</th></tr></thead>
      <tbody>
      <?php foreach($users as $idx=>$u):
        $u_avatar=!empty($u['avatar'])?$u['avatar']:'https://api.dicebear.com/7.x/avataaars/svg?seed='.urlencode($u['email']??'x');
        $jdt=new DateTime($u['created_at'],new DateTimeZone('UTC'));
        $jdt->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $ug=strtolower($u['gender']??'');
        $isAlien=$ug===''||!in_array($ug,['male','female','nonbinary']);
        $genderFilter=$isAlien?'alien':$ug;
        $la=$u['last_active']??null;
        $laStr='Never';
        if($la){try{$ladt=new DateTime($la,new DateTimeZone('UTC'));$ladt->setTimezone(new DateTimeZone('Asia/Kolkata'));$laStr=$ladt->format('d M Y');}catch(Exception $e){}}
      ?>
      <tr data-search="<?= htmlspecialchars(strtolower(($u['username']??'').' '.($u['email']??''))) ?>" data-role="<?= $u['role']??'user' ?>" data-gender="<?= $genderFilter ?>">
        <td style="font-size:.7rem;color:var(--muted);font-weight:700"><?= $idx+1 ?></td>
        <td><img loading="lazy" src="<?= htmlspecialchars($u_avatar) ?>" class="u-av" alt=""></td>
        <td><div class="u-n"><?= htmlspecialchars($u['username']??'—') ?></div><div class="u-e"><?= htmlspecialchars($u['email']??'') ?></div></td>
        <td class="<?= $ug==='male'?'gi-m':($ug==='female'?'gi-f':'gi-a') ?>"><i class="fa-solid fa-<?= $ug==='male'?'mars':($ug==='female'?'venus':'user-astronaut') ?>"></i> <?= $isAlien?'Alien':ucfirst($ug) ?></td>
        <td><span class="rbadge <?= $u['role']==='admin'?'rb-a':'rb-u' ?>"><?= strtoupper($u['role']??'user') ?></span></td>
        <td style="font-size:.72rem;color:var(--muted)"><?= $jdt->format('d M Y') ?><br><span style="opacity:.6;font-size:.65rem"><?= $jdt->format('h:i A') ?></span></td>
        <td style="font-size:.72rem;color:var(--muted)"><?= $laStr ?></td>
        <td><button onclick="openActivity(<?= (int)$u['id'] ?>)" class="d-btn db-p"><i class="fa-solid fa-chart-simple"></i> <span class="hide-sm">Activity</span></button></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <p id="um-empty" style="display:none;text-align:center;color:var(--muted);padding:20px 0;font-size:.85rem"><i class="fa-solid fa-magnifying-glass"></i> No users match your search.</p>
    <div class="pagination">
      <div id="um-info" style="color:var(--muted)"></div>
      <div class="pag-btns" id="um-pagination"></div>
    </div>
  </div>
</main>

<!-- MOBILE BOTTOM NAV -->
<nav class="mob-nav">
  <a href="dashboard.php" class="mn-link"><i class="fa-solid fa-gauge-high"></i><span>Home</span></a>
  <a href="manage_prompts.php" class="mn-link"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Prompts</span></a>
  <a href="user_management.php" class="mn-link active"><i class="fa-solid fa-users"></i><span>Users</span></a>
  <a href="analytics.php" class="mn-link"><i class="fa-solid fa-chart-line"></i><span>Stats</span></a>
  <a href="upload_prompt.php" class="mn-link mn-more"><i class="fa-solid fa-plus"></i><span>More</span></a>
</nav>

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
        <div class="astat as-p" style="grid-column:1/-1"><div id="act-last-active" class="astat-val" style="color:var(--accent2);font-size:.9rem"></div><div class="astat-lbl">Last Active</div></div>
        <div class="astat as-y"><div id="act-unlocks" class="astat-val" style="color:var(--yellow)"></div><div class="astat-lbl">Unlocked</div></div>
        <div class="astat as-g"><div id="act-saves" class="astat-val" style="color:var(--green)"></div><div class="astat-lbl">Saved</div></div>
        <div class="astat as-r"><div id="act-likes" class="astat-val" style="color:var(--red)"></div><div class="astat-lbl">Liked</div></div>
      </div>
      <div style="font-size:.62rem;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:9px"><i class="fa-solid fa-lock-open" style="color:var(--accent2)"></i> Unlocked Prompts</div>
      <div id="act-unlock-list" style="display:flex;flex-direction:column;gap:5px;max-height:200px;overflow-y:auto"></div>
    </div>
  </div>
</div>

<script>
// Scroll progress
const sp=document.getElementById('sp');
window.addEventListener('scroll',()=>{const h=document.documentElement;sp.style.width=(h.scrollTop/(h.scrollHeight-h.clientHeight)*100)+'%';},{passive:true});

// Particles
(function(){const c=document.getElementById('pc');if(!c)return;const ctx=c.getContext('2d');let W,H,pts=[];function rs(){W=c.width=window.innerWidth;H=c.height=window.innerHeight}rs();window.addEventListener('resize',rs);class P{constructor(){this.reset()}reset(){this.x=Math.random()*W;this.y=Math.random()*H;this.vx=(Math.random()-.5)*.3;this.vy=(Math.random()-.5)*.3;this.r=Math.random()*1.2+.3;this.a=Math.random()*.4+.1;const cols=['139,92,246','244,114,182','34,211,238'];this.col=cols[Math.floor(Math.random()*cols.length)]}update(){this.x+=this.vx;this.y+=this.vy;if(this.x<0||this.x>W||this.y<0||this.y>H)this.reset()}draw(){ctx.beginPath();ctx.arc(this.x,this.y,this.r,0,Math.PI*2);ctx.fillStyle=`rgba(${this.col},${this.a})`;ctx.fill()}}for(let i=0;i<60;i++)pts.push(new P());function loop(){ctx.clearRect(0,0,W,H);pts.forEach(p=>{p.update();p.draw()});requestAnimationFrame(loop)}loop();})();

// CountUp
document.querySelectorAll('.sc-val').forEach(el=>{
  const n=parseInt(el.dataset.val);
  if(!isNaN(n)&&n>0){el.textContent='0';let s=0,step=n/50;const t=setInterval(()=>{s+=step;if(s>=n){s=n;clearInterval(t)}el.textContent=Math.floor(s)},16)}
});

// Growth Chart
const gLabels=<?= json_encode($growth_labels) ?>;
const gData=<?= json_encode($growth_data) ?>;
Chart.defaults.color='#9490bb';
Chart.defaults.borderColor='rgba(139,92,246,0.1)';
new Chart(document.getElementById('growthChart'),{
  type:'line',
  data:{labels:gLabels,datasets:[{label:'New Users',data:gData,borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,0.1)',borderWidth:2,fill:true,tension:.4,pointBackgroundColor:'#8b5cf6',pointRadius:3,pointHoverRadius:5}]},
  options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(139,92,246,0.08)'},ticks:{color:'#9490bb',font:{size:10},maxTicksLimit:8}},y:{grid:{color:'rgba(139,92,246,0.08)'},ticks:{color:'#9490bb',font:{size:10},stepSize:1}}}}
});

// Pagination
const PER_PAGE=12;
let currentPage=1;
function getAllRows(){return Array.from(document.querySelectorAll('#um-table tbody tr'))}
function getFilteredRows(){
  const q=(document.getElementById('um-search').value||'').toLowerCase().trim();
  const role=(document.getElementById('um-role-filter').value||'').toLowerCase();
  const gender=(document.getElementById('um-gender-filter').value||'').toLowerCase();
  return getAllRows().filter(r=>{
    const ms=!q||(r.dataset.search||'').includes(q);
    const mr=!role||(r.dataset.role||'')==role;
    const mg=!gender||(r.dataset.gender||'')==gender;
    return ms&&mr&&mg;
  });
}
function renderPage(page){
  currentPage=page;
  const rows=getFilteredRows();
  const total=rows.length;
  const pages=Math.max(1,Math.ceil(total/PER_PAGE));
  if(page>pages)page=pages;
  const start=(page-1)*PER_PAGE,end=start+PER_PAGE;
  getAllRows().forEach(r=>r.style.display='none');
  rows.forEach((r,i)=>r.style.display=(i>=start&&i<end)?'':'none');
  document.getElementById('um-empty').style.display=total===0?'block':'none';
  document.getElementById('um-info').textContent=total===0?'':`Showing ${Math.min(start+1,total)}–${Math.min(end,total)} of ${total}`;
  renderPagination(page,pages,total);
}
function renderPagination(page,pages,total){
  const c=document.getElementById('um-pagination');
  if(pages<=1){c.innerHTML='';return}
  let h=`<button class="pag-btn" onclick="renderPage(${page-1})" ${page===1?'disabled':''}>Prev</button>`;
  for(let i=1;i<=pages;i++)h+=`<button class="pag-btn ${i===page?'active':''}" onclick="renderPage(${i})">${i}</button>`;
  h+=`<button class="pag-btn" onclick="renderPage(${page+1})" ${page===pages?'disabled':''}>Next</button>`;
  c.innerHTML=h;
}
function filterUsers(){renderPage(1)}
window.addEventListener('load',()=>renderPage(1));

// Activity Modal
function openActivity(uid){
  document.getElementById('activity-modal').style.display='flex';
  document.getElementById('act-loading').style.display='block';
  document.getElementById('act-content').style.display='none';
  fetch('user_management.php?xhr=activity&uid='+uid).then(r=>r.json()).then(data=>{
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

// Custom Dropdowns
function toggleCsel(id){const w=document.getElementById(id);const isOpen=w.classList.contains('open');document.querySelectorAll('.csel-wrap.open').forEach(el=>el.classList.remove('open'));if(!isOpen)w.classList.add('open');}
function setCsel(id,val,lbl){
  const w=document.getElementById(id);
  document.getElementById(id+'-lbl').textContent=lbl;
  w.querySelectorAll('.csel-opt').forEach(o=>o.classList.remove('selected'));
  event.currentTarget.classList.add('selected');
  w.classList.remove('open');
  // Sync hidden native select
  const selId=id==='csel-role'?'um-role-filter':'um-gender-filter';
  const sel=document.getElementById(selId);
  if(sel){sel.value=val;sel.dispatchEvent(new Event('change'));}
}
document.addEventListener('click',e=>{if(!e.target.closest('.csel-wrap'))document.querySelectorAll('.csel-wrap.open').forEach(el=>el.classList.remove('open'));});
</script>
</html>

