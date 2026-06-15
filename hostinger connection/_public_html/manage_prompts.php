<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] =
        "You do not have permission to access the manage prompts page.";
    header("Location: index.php");
    exit();
}

// -- AJAX Toggle Trial --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_trial_id'], $_POST['is_trial'])) {
    $tid = intval($_POST['toggle_trial_id']);
    $val = intval($_POST['is_trial']);
    $pdo->prepare("UPDATE prompts SET is_trial = ? WHERE id = ?")->execute([$val, $tid]);
    echo "OK";
    exit;
}

// -- Bulk toggle (checkbox + publish/unpublish) --
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
<title>Manage Prompts — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include_once "gtag.php"; ?>
<style>
:root{--bg:#07060f;--surface:#0f0d1e;--border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);--accent:#8b5cf6;--accent2:#c084fc;--pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;--yellow:#fbbf24;--orange:#fb923c;--red:#f87171;--text:#e2e0ff;--muted:#9490bb;--font:'Inter',sans-serif}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:var(--font);overflow-x:hidden;min-height:100vh}
#sp{position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,var(--accent),var(--pink),var(--cyan));z-index:9999;box-shadow:0 0 10px var(--accent)}
#pc{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.4}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:220px;background:rgba(7,6,15,0.98);border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column}
.sb-logo{padding:20px 18px 14px;border-bottom:1px solid var(--border2)}
.sb-brand{font-size:.72rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:flex;align-items:center;gap:8px}
.sb-brand i{-webkit-text-fill-color:#a78bfa}
.sb-admin{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border2)}
.sb-av-ph{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;flex-shrink:0}
.sb-uname{font-size:.78rem;font-weight:800;color:var(--text)}
.sb-role{font-size:.6rem;font-weight:700;color:var(--accent2);text-transform:uppercase;letter-spacing:.1em}
.sb-nav{flex:1;overflow-y:auto;padding:10px 8px}
.sb-nav::-webkit-scrollbar{width:2px}.sb-nav::-webkit-scrollbar-thumb{background:var(--accent);border-radius:10px}
.sb-sec{font-size:.58rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 10px 5px}
.sb-link{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;border:1px solid transparent;margin-bottom:1px}
.sb-link:hover{background:rgba(139,92,246,0.08);color:var(--text)}
.sb-link.active{background:rgba(139,92,246,0.15);color:var(--accent2);border-color:var(--border)}
.sb-link i{width:16px;text-align:center;flex-shrink:0}
.sb-bottom{padding:12px 8px;border-top:1px solid var(--border2)}
.sb-logout{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:700;color:var(--red);text-decoration:none;transition:all .2s}
.sb-logout:hover{background:rgba(248,113,113,0.1)}
.main{margin-left:220px;min-height:100vh;padding:28px 32px 80px;position:relative;z-index:1}
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:22px;flex-wrap:wrap}
.tb-title{font-size:1.5rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1}
.card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:18px;backdrop-filter:blur(8px);transition:border-color .3s}
.card:hover{border-color:rgba(139,92,246,0.3)}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border2);gap:10px;flex-wrap:wrap}
.card-title{font-size:.88rem;font-weight:900;display:flex;align-items:center;gap:8px;color:var(--text)}
.card-title i{color:var(--accent2)}
.srch-inp{width:100%;padding:10px 16px;background:rgba(15,13,30,0.8);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:var(--font);font-size:.85rem;outline:none;transition:all .2s;box-sizing:border-box;margin-bottom:12px}
.srch-inp:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(139,92,246,0.1)}
.srch-inp::placeholder{color:var(--muted)}
.tag-sel{padding:10px 14px;background:rgba(15,13,30,0.8);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:var(--font);font-size:.82rem;outline:none;cursor:pointer}
/* PROMPT ITEM */
.prompt-item{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid var(--border2);transition:all .2s}
.prompt-item:last-child{border-bottom:none}
.prompt-item:hover{background:rgba(139,92,246,0.02);margin:0 -8px;padding-left:8px;padding-right:8px;border-radius:10px}
.p-cover{width:52px;height:52px;border-radius:10px;object-fit:cover;border:1px solid var(--border);flex-shrink:0}
.p-info{flex:1;min-width:0}
.p-title{font-weight:800;font-size:.88rem;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:5px}
.p-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.type-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:100px;font-size:.6rem;font-weight:900;border:1px solid;text-transform:uppercase}
.tb-scp{background:rgba(248,113,113,0.1);color:var(--red);border-color:rgba(248,113,113,0.25)}
.tb-urp{background:rgba(251,191,36,0.1);color:var(--yellow);border-color:rgba(251,191,36,0.25)}
.tb-ivp{background:rgba(34,211,238,0.08);color:var(--cyan);border-color:rgba(34,211,238,0.2)}
.tb-aup{background:rgba(96,165,250,0.08);color:#60a5fa;border-color:rgba(96,165,250,0.2)}
.tb-trial{background:rgba(74,222,128,0.1);color:var(--green);border-color:rgba(74,222,128,0.22)}
.p-tag-pill{padding:2px 8px;background:rgba(139,92,246,0.08);border:1px solid var(--border2);border-radius:100px;font-size:.6rem;font-weight:700;color:var(--muted)}
.p-actions{display:flex;gap:6px;flex-shrink:0;align-items:center}
@media(max-width:700px){.prompt-item{align-items:flex-start;flex-wrap:wrap}.p-actions{width:100%;padding-left:66px;margin-top:6px}}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:9px;font-size:.72rem;font-weight:800;border:1px solid;transition:all .2s;cursor:pointer;background:transparent;font-family:var(--font);text-decoration:none}
.btn-purple{color:var(--accent2);border-color:rgba(139,92,246,0.25);background:rgba(139,92,246,0.07)}
.btn-purple:hover{background:rgba(139,92,246,0.15)}
.btn-yellow{color:var(--yellow);border-color:rgba(251,191,36,0.25);background:rgba(251,191,36,0.07)}
.btn-yellow:hover{background:rgba(251,191,36,0.15)}
.btn-cyan{color:var(--cyan);border-color:rgba(34,211,238,0.2);background:rgba(34,211,238,0.05)}
.btn-cyan:hover{background:rgba(34,211,238,0.1)}
.btn-red{color:var(--red);border-color:rgba(248,113,113,0.22);background:rgba(248,113,113,0.07)}
.btn-red:hover{background:rgba(248,113,113,0.14)}
.btn-full{width:100%;justify-content:center;padding:11px}
/* PERF TABLE */
.dtable{width:100%;border-collapse:collapse;font-size:.78rem}
.dtable th{background:rgba(139,92,246,0.07);color:var(--accent2);font-weight:800;font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;padding:9px 13px;text-align:left;border-bottom:1px solid var(--border)}
.dtable td{padding:10px 13px;border-bottom:1px solid var(--border2);color:var(--muted);vertical-align:middle}
.dtable tr:last-child td{border-bottom:none}
.dtable tr:hover td{background:rgba(139,92,246,0.03)}
.score-bar{height:5px;border-radius:10px;background:linear-gradient(90deg,var(--accent),var(--pink));margin-top:4px}
/* BULK */
.bulk-type-btns{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.bulk-type-btn{padding:8px 16px;border-radius:10px;font-size:.75rem;font-weight:800;border:1px solid;cursor:pointer;font-family:var(--font);transition:all .2s;background:transparent}
.bt-scp{color:var(--red);border-color:rgba(248,113,113,0.3)}
.bt-scp.sel{background:rgba(248,113,113,0.12)}
.bt-urp{color:var(--yellow);border-color:rgba(251,191,36,0.3)}
.bt-urp.sel{background:rgba(251,191,36,0.1)}
.bt-ivp{color:var(--cyan);border-color:rgba(34,211,238,0.25)}
.bt-ivp.sel{background:rgba(34,211,238,0.08)}
.bt-aup{color:#60a5fa;border-color:rgba(96,165,250,0.25)}
.bt-aup.sel{background:rgba(96,165,250,0.08)}
.bt-all{color:var(--muted);border-color:var(--border2)}
.bt-all:hover{color:var(--text);border-color:var(--border)}
.bulk-list{max-height:300px;overflow-y:auto;border:1px solid var(--border2);border-radius:12px;padding:8px}
.bulk-list::-webkit-scrollbar{width:3px}.bulk-list::-webkit-scrollbar-thumb{background:var(--accent);border-radius:10px}
.bulk-item{display:flex;align-items:center;gap:10px;padding:8px;border-radius:8px;cursor:pointer;transition:background .15s}
.bulk-item:hover{background:rgba(139,92,246,0.06)}
.bulk-item input[type=checkbox]{accent-color:var(--accent);width:16px;height:16px;cursor:pointer}
.bulk-item-img{width:32px;height:32px;border-radius:6px;object-fit:cover;flex-shrink:0}
.bulk-item-title{font-size:.78rem;font-weight:700;color:var(--text);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* MODAL */
.modal-ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(14px);z-index:2000;align-items:center;justify-content:center;padding:20px}
.modal-box{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:28px;max-width:380px;width:100%;box-shadow:0 24px 80px rgba(0,0,0,0.6);text-align:center;position:relative}
.m-close{position:absolute;top:13px;right:13px;width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,0.05);border:1px solid var(--border);color:var(--muted);font-size:.85rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;font-family:var(--font)}
.m-close:hover{background:rgba(248,113,113,0.1);color:var(--red)}
.del-icon{width:56px;height:56px;border-radius:16px;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.22);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--red);margin:0 auto 14px}
.del-btns{display:flex;gap:10px;margin-top:18px}
.del-cancel{flex:1;padding:11px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:11px;color:var(--muted);font-weight:800;cursor:pointer;font-family:var(--font);font-size:.85rem;transition:all .2s}
.del-cancel:hover{border-color:var(--accent);color:var(--text)}
.del-confirm{flex:1;padding:11px;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.25);border-radius:11px;color:var(--red);font-weight:800;cursor:pointer;font-family:var(--font);font-size:.85rem;transition:all .2s;width:100%}
.del-confirm:hover{background:rgba(248,113,113,0.18)}
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:rgba(139,92,246,0.4);border-radius:10px}
/* MOBILE */
.mob-nav{display:none;position:fixed;bottom:0;left:0;right:0;background:rgba(7,6,15,0.97);border-top:1px solid var(--border);z-index:500;padding:8px 0 max(8px,env(safe-area-inset-bottom));flex-direction:row;justify-content:space-around;align-items:center}
.mn-link{display:flex;flex-direction:column;align-items:center;gap:3px;font-size:.6rem;font-weight:700;color:var(--muted);text-decoration:none;padding:4px 8px;min-width:48px;transition:all .2s}
.mn-link.active,.mn-link:hover{color:var(--accent2)}
.mn-link i{font-size:1.1rem}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-admin{padding:10px;justify-content:center}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px;padding:20px 16px 80px}}
@media(max-width:768px){.sidebar{display:none!important}.main{margin-left:0;padding:14px 14px 90px}.mob-nav{display:flex!important}.mob-topbar{display:flex!important}.topbar{display:none}.p-actions{display:none!important}.p-dot-wrap{display:block!important}.bulk-type-btns{gap:6px}}
/* MOBILE TOPBAR */
.mob-topbar{display:none;position:sticky;top:0;z-index:300;background:rgba(7,6,15,0.96);backdrop-filter:blur(16px);border-bottom:1px solid var(--border2);padding:13px 16px;align-items:center;gap:12px}
.mob-menu-btn{width:38px;height:38px;border-radius:10px;background:rgba(139,92,246,0.08);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--accent2);font-size:1rem;cursor:pointer;flex-shrink:0}
.mob-page-title{font-size:1rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1}
.mob-home-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;background:rgba(34,211,238,0.08);color:var(--cyan);border:1px solid rgba(34,211,238,0.2);flex-shrink:0}
/* DRAWER */
.drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(6px);z-index:500}
.drawer{position:fixed;left:0;top:0;bottom:0;width:265px;background:rgba(7,6,15,0.99);border-right:1px solid var(--border);z-index:600;display:flex;flex-direction:column;transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1)}
.drawer.open{transform:translateX(0)}
.drawer-overlay.open{display:block}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:18px 16px;border-bottom:1px solid var(--border2)}
.drawer-brand{font-size:.8rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.drawer-close{width:32px;height:32px;border-radius:8px;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:var(--red);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem}
.drawer-user{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border2)}
.d-av-ph2{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;flex-shrink:0}
.d-uname{font-size:.85rem;font-weight:800}
.d-role2{font-size:.65rem;color:var(--accent2);font-weight:700;text-transform:uppercase}
.drawer-nav2{flex:1;overflow-y:auto;padding:8px 10px}
.d-sec2{font-size:.6rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 8px 5px}
.d-link2{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;margin-bottom:2px}
.d-link2:hover,.d-link2.active{background:rgba(139,92,246,0.1);color:var(--accent2)}
.d-link2 i{width:18px;text-align:center}
.drawer-bot{padding:12px 10px;border-top:1px solid var(--border2)}
.d-out{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:700;color:var(--red);text-decoration:none}
.d-out:hover{background:rgba(248,113,113,0.08)}
/* THREE-DOT DROPDOWN */
.p-actions-wrap{display:flex;align-items:center;gap:6px;flex-shrink:0}
.p-dot-wrap{position:relative;display:none}
.p-dot-btn{width:34px;height:34px;border-radius:9px;background:rgba(139,92,246,0.08);border:1px solid var(--border);color:var(--accent2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.1rem;transition:all .2s}
.p-dot-btn:hover{background:rgba(139,92,246,0.15)}
.p-dropdown{display:none;position:absolute;right:0;top:40px;background:rgba(10,9,20,0.98);border:1px solid var(--border);border-radius:13px;padding:6px;min-width:175px;z-index:200;box-shadow:0 12px 40px rgba(0,0,0,0.5);backdrop-filter:blur(16px)}
.p-dropdown.open{display:block}
.pd-item{display:flex;align-items:center;gap:10px;padding:10px 13px;border-radius:9px;font-size:.82rem;font-weight:700;color:var(--text);text-decoration:none;cursor:pointer;transition:background .15s;border:none;background:transparent;width:100%;font-family:var(--font)}
.pd-item:hover{background:rgba(139,92,246,0.08)}
.pd-del:hover{background:rgba(248,113,113,0.08)!important}
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
@media(max-width:768px){#c-dot,#c-ring{display:none!important}}body::before, body::after { display: none !important; background-image: none !important; }
</style>
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
    <div class="d-av-ph2"><?= strtoupper(substr($__sn,0,1)) ?></div>
    <div><div class="d-uname"><?= htmlspecialchars($__sn) ?></div><div class="d-role2">Admin</div></div>
  </div>
  <nav class="drawer-nav2">
    <div class="d-sec2">Overview</div>
    <a href="dashboard.php" class="d-link2"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="analytics.php" class="d-link2"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    <div class="d-sec2">Content</div>
    <a href="upload_prompt.php" class="d-link2"><i class="fa-solid fa-upload"></i> Upload Prompt</a>
    <a href="manage_prompts.php" class="d-link2 active"><i class="fa-solid fa-list-check"></i> Manage Prompts</a>
    <a href="prompt_links.php" class="d-link2"><i class="fa-solid fa-link"></i> Prompt Links</a>
    <a href="potd_manager.php" class="d-link2"><i class="fa-solid fa-sun"></i> POTD Manager</a>
    <div class="d-sec2">Blog</div>
    <a href="blog_admin.php" class="d-link2"><i class="fa-solid fa-pen-nib"></i> Blog Admin</a>
    <a href="blog_create.php" class="d-link2"><i class="fa-solid fa-plus"></i> New Post</a>
    <div class="d-sec2">Users</div>
    <a href="user_management.php" class="d-link2"><i class="fa-solid fa-users"></i> Users</a>
    <div class="d-sec2">Tools</div>
    <a href="index.php" class="d-link2" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
  </nav>
  <div class="drawer-bot">
    <a href="login.php?logout=1" class="d-out"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </div>
</div>

<!-- MOBILE TOP BAR -->
<div class="mob-topbar">
  <div class="mob-menu-btn" onclick="openDrawer()"><i class="fa-solid fa-bars"></i></div>
  <div class="mob-page-title"><i class="fa-solid fa-list-check" style="-webkit-text-fill-color:var(--accent2);margin-right:6px"></i>Manage Prompts</div>
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
    <a href="analytics.php" class="sb-link"><i class="fa-solid fa-chart-line"></i> <span>Analytics</span></a>
    <div class="sb-sec">Content</div>
    <a href="upload_prompt.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="manage_prompts.php" class="sb-link active"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
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
    <div class="tb-title"><i class="fa-solid fa-list-check" style="color:var(--accent2);-webkit-text-fill-color:var(--accent2)"></i> Manage Prompts</div>
    <span style="font-size:.75rem;background:rgba(139,92,246,0.1);border:1px solid var(--border);color:var(--accent2);border-radius:100px;padding:5px 14px;font-weight:800"><?= $total_prompts ?> Total</span>
    <a href="upload_prompt.php" class="btn btn-purple"><i class="fa-solid fa-plus"></i> <span>Upload New</span></a>
  </div>

  <!-- ALL PROMPTS -->
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-wand-magic-sparkles"></i> All Prompts</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">
      <input type="text" id="prompt-search" class="srch-inp" style="margin-bottom:0;flex:2;min-width:180px" placeholder="Search by title or tag..." oninput="filterPrompts()">
      <select id="prompt-tag-filter" class="tag-sel" onchange="filterPrompts()">
        <option value="">All Tags</option>
        <?php foreach($all_mgr_tags as $t): ?><option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div id="prompts-list">
    <?php
    $type_badge_map=['secret'=>['cls'=>'tb-scp','lbl'=>'SCP'],'unreleased'=>['cls'=>'tb-urp','lbl'=>'URP'],'insta_viral'=>['cls'=>'tb-ivp','lbl'=>'IVP'],'already_uploaded'=>['cls'=>'tb-aup','lbl'=>'AUP']];
    foreach($prompts as $p):
      $ptype=$p['prompt_type']??'secret';
      $binfo=$type_badge_map[$ptype]??$type_badge_map['secret'];
      $item_title=strtolower(htmlspecialchars($p['title']??''));
      $item_tags=implode(',',array_map('trim',explode(',',$p['tag']??'')));
      $item_img=htmlspecialchars($p['image_path']??'');
      $item_id=(int)$p['id'];
    ?>
    <div class="prompt-item" data-title="<?= $item_title ?>" data-tags="<?= strtolower($item_tags) ?>">
      <img class="p-cover" src="<?= $item_img ?>" alt="" loading="lazy">
      <div class="p-info">
        <div class="p-title"><?= htmlspecialchars($p['title']??'Untitled') ?></div>
        <div class="p-meta">
          <span class="type-badge <?= $binfo['cls'] ?>"><?= $binfo['lbl'] ?></span>
          <?php if($ptype === 'secret' && !empty($p['secret_code'])): ?><span class="type-badge" style="background:rgba(192,132,252,0.1);color:#c084fc;border-color:rgba(192,132,252,0.25);font-family:monospace;cursor:pointer;" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($p['secret_code']) ?>');alert('Code copied!');" title="Click to copy code"><?= htmlspecialchars($p['secret_code']) ?></span><?php endif; ?>
          <span class="type-badge tb-trial" style="cursor:pointer;<?= empty($p['is_trial']) ? 'opacity:0.4;background:transparent;border-style:dashed;' : '' ?>" onclick="openTrialModal(<?= $item_id ?>, <?= empty($p['is_trial']) ? '0' : '1' ?>)"><?= empty($p['is_trial']) ? '+ Trial' : 'TRIAL' ?></span>
          <?php if(!empty($p['is_featured'])): ?><span class="type-badge" style="background:rgba(251,191,36,0.1);color:var(--yellow);border-color:rgba(251,191,36,0.25)"><i class="fa-solid fa-star" style="font-size:.55rem"></i> POTD</span><?php endif; ?>
          <?php if(!empty($p['likes_count'])): ?><span style="font-size:.68rem;color:var(--muted)"><i class="fa-solid fa-heart" style="color:var(--red)"></i> <?= $p['likes_count'] ?></span><?php endif; ?>
          <?php foreach(array_filter(array_map('trim',explode(',',$p['tag']??''))) as $tg): ?><span class="p-tag-pill"><?= htmlspecialchars($tg) ?></span><?php endforeach; ?>
        </div>
      </div>
      <div class="p-actions-wrap">
        <div class="p-actions">
          <button id="feat-btn-<?= $item_id ?>" onclick="featurePrompt(<?= $item_id ?>)" class="btn btn-yellow"><i class="fa-solid fa-star"></i> <span>POTD</span></button>
          <a href="edit_prompt.php?id=<?= $item_id ?>" class="btn btn-cyan"><i class="fa-solid fa-pen"></i> <span>Edit</span></a>
          <button onclick="confirmDelete(<?= $item_id ?>, '<?= addslashes($p['title']??'') ?>')" class="btn btn-red"><i class="fa-solid fa-trash"></i></button>
        </div>
        <div class="p-dot-wrap">
          <button class="p-dot-btn" onclick="togglePDrop(this)"><i class="fa-solid fa-ellipsis-vertical"></i></button>
          <div class="p-dropdown">
            <a href="edit_prompt.php?id=<?= $item_id ?>" class="pd-item"><i class="fa-solid fa-pen" style="color:var(--cyan)"></i> Edit Prompt</a>
            <button class="pd-item" onclick="featurePrompt(<?= $item_id ?>);closePDrops()"><i class="fa-solid fa-star" style="color:var(--yellow)"></i> Toggle POTD</button>
            <button class="pd-item pd-del" onclick="confirmDelete(<?= $item_id ?>,'<?= addslashes($p['title']??'') ?>');closePDrops()"><i class="fa-solid fa-trash" style="color:var(--red)"></i> Delete</button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <p id="no-results" style="display:none;text-align:center;color:var(--muted);padding:20px 0;font-size:.85rem"><i class="fa-solid fa-magnifying-glass"></i> No prompts match your search.</p>
  </div>

  <!-- PERFORMANCE TABLE -->
  <div class="card">
    <div class="card-head"><div class="card-title"><i class="fa-solid fa-chart-bar"></i> Top 15 by Performance Score</div></div>
    <div style="overflow-x:auto">
    <table class="dtable">
      <thead><tr><th>#</th><th>Prompt</th><th><i class="fa-solid fa-heart" style="color:var(--red)"></i> Likes</th><th><i class="fa-solid fa-bookmark" style="color:var(--yellow)"></i> Saves</th><th><i class="fa-solid fa-bolt" style="color:var(--orange)"></i> Score</th></tr></thead>
      <tbody>
      <?php foreach($perf_prompts as $i=>$pp):
        $maxScore=max(1,$perf_prompts[0]['likes_count']+$perf_prompts[0]['saves_count']);
        $score=(int)$pp['likes_count']+(int)$pp['saves_count'];
        $pct=min(100,round($score/$maxScore*100));
        $scoreCls=$i===0?'color:var(--yellow)':($i<3?'color:var(--cyan)':'color:var(--muted)');
      ?>
      <tr>
        <td style="font-weight:900;font-size:.78rem;<?= $i===0?'color:var(--yellow)':($i<3?'color:var(--accent2)':'color:var(--muted)') ?>"><?= $i+1 ?></td>
        <td><div style="font-weight:700;font-size:.82rem;color:var(--text);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($pp['title']??'') ?></div><div class="score-bar" style="width:<?= $pct ?>%"></div></td>
        <td style="font-weight:800;color:var(--red)"><?= $pp['likes_count'] ?></td>
        <td style="font-weight:800;color:var(--yellow)"><?= $pp['saves_count'] ?></td>
        <td style="font-weight:900;<?= $scoreCls ?>"><?= $score ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- DEAD PROMPTS -->
  <?php if(!empty($dead_prompts)): ?>
  <div class="card" style="border-color:rgba(248,113,113,0.2)">
    <div class="card-head"><div class="card-title"><i class="fa-solid fa-skull" style="color:var(--red)"></i> Dead Prompts <span style="font-size:.62rem;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:var(--red);border-radius:100px;padding:2px 9px;font-weight:900;margin-left:6px">0 likes &amp; saves</span></div></div>
    <?php foreach($dead_prompts as $dp):
      $dptype=$dp['prompt_type']??'secret';
      $dpinfo=$type_badge_map[$dptype]??$type_badge_map['secret'];
    ?>
    <div class="prompt-item">
      <img class="p-cover" src="<?= htmlspecialchars($dp['image_path']??'') ?>" alt="" loading="lazy">
      <div class="p-info">
        <div class="p-title"><?= htmlspecialchars($dp['title']??'Untitled') ?></div>
        <div class="p-meta">
          <span class="type-badge <?= $dpinfo['cls'] ?>"><?= $dpinfo['lbl'] ?></span>
          <span style="font-size:.68rem;color:var(--muted)">Added <?= date('d M Y',strtotime($dp['created_at']??'now')) ?></span>
        </div>
        <div class="p-actions"><a href="edit_prompt.php?id=<?= (int)$dp['id'] ?>" class="btn btn-cyan"><i class="fa-solid fa-pen"></i> Edit</a></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- BULK TYPE CHANGE -->
  <div class="card">
    <div class="card-head"><div class="card-title"><i class="fa-solid fa-sliders"></i> Bulk Type Change</div></div>
    <form id="bulk-form" method="POST" action="manage_prompts.php">
      <input type="hidden" id="bulk-action-val" name="bulk_action" value="">
      <p style="font-size:.78rem;color:var(--muted);margin-bottom:12px"><i class="fa-solid fa-circle-info" style="color:var(--accent2)"></i> Select an action type, then tick prompts to change them all at once.</p>
      <div class="bulk-type-btns">
        <button type="button" class="bulk-type-btn bt-scp" onclick="setBulkAction('secret',this)"><i class="fa-solid fa-lock"></i> Secret</button>
        <button type="button" class="bulk-type-btn bt-urp" onclick="setBulkAction('unreleased',this)"><i class="fa-solid fa-star"></i> Unreleased</button>
        <button type="button" class="bulk-type-btn bt-ivp" onclick="setBulkAction('insta_viral',this)"><i class="fa-brands fa-instagram"></i> Insta Viral</button>
        <button type="button" class="bulk-type-btn bt-aup" onclick="setBulkAction('already_uploaded',this)"><i class="fa-solid fa-clock-rotate-left"></i> Already Uploaded</button>
        <button type="button" class="bulk-type-btn bt-all" onclick="selectAllBulk()"><i class="fa-solid fa-check-double"></i> Select All</button>
      </div>
      <div class="bulk-list" id="bulk-list">
        <?php foreach($prompts as $bp): ?>
        <label class="bulk-item">
          <input type="checkbox" name="selected_ids[]" value="<?= (int)$bp['id'] ?>" onchange="updateBulkSubmit()">
          <img class="bulk-item-img" src="<?= htmlspecialchars($bp['image_path']??'') ?>" alt="" loading="lazy">
          <span class="bulk-item-title"><?= htmlspecialchars($bp['title']??'Untitled') ?></span>
          <?php $bt=$bp['prompt_type']??'secret';$binfo2=$type_badge_map[$bt]??$type_badge_map['secret']; ?>
          <span class="type-badge <?= $binfo2['cls'] ?>" style="flex-shrink:0"><?= $binfo2['lbl'] ?></span>
        </label>
        <?php endforeach; ?>
      </div>
      <button type="submit" id="bulk-submit" class="btn btn-purple btn-full" style="margin-top:12px" disabled><i class="fa-solid fa-check"></i> Apply to Selected</button>
    </form>
  </div>
</main>

<nav class="mob-nav">
  <a href="dashboard.php" class="mn-link"><i class="fa-solid fa-gauge-high"></i><span>Home</span></a>
  <a href="manage_prompts.php" class="mn-link active"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Prompts</span></a>
  <a href="user_management.php" class="mn-link"><i class="fa-solid fa-users"></i><span>Users</span></a>
  <a href="analytics.php" class="mn-link"><i class="fa-solid fa-chart-line"></i><span>Stats</span></a>
  <a href="upload_prompt.php" class="mn-link" style="color:var(--accent2)"><i class="fa-solid fa-plus"></i><span>Upload</span></a>
</nav>

<!-- DELETE MODAL -->
<div id="delete-modal" class="modal-ov" onclick="if(event.target===this)closeDeleteModal()">
  <div class="modal-box">
    <button class="m-close" onclick="closeDeleteModal()"><i class="fa-solid fa-xmark"></i></button>
    <div class="del-icon"><i class="fa-solid fa-trash"></i></div>
    <div style="font-size:1.1rem;font-weight:900;color:var(--text);margin-bottom:7px">Delete Prompt?</div>
    <div id="delete-modal-name" style="font-size:.85rem;color:var(--muted);font-weight:600"></div>
    <div class="del-btns">
      <button onclick="closeDeleteModal()" class="del-cancel">Cancel</button>
      <form id="delete-form" action="delete_prompt.php" method="POST" style="flex:1;margin:0">
        <input type="hidden" id="delete-prompt-id" name="prompt_id" value="">
        <button type="submit" class="del-confirm">Delete</button>
      </form>
    </div>
  </div>
</div>

<script>
window.addEventListener('scroll',()=>{const h=document.documentElement;document.getElementById('sp').style.width=(h.scrollTop/(h.scrollHeight-h.clientHeight)*100)+'%';},{passive:true});
(function(){const c=document.getElementById('pc');if(!c)return;const ctx=c.getContext('2d');let W,H,pts=[];function rs(){W=c.width=window.innerWidth;H=c.height=window.innerHeight}rs();window.addEventListener('resize',rs);class P{constructor(){this.reset()}reset(){this.x=Math.random()*W;this.y=Math.random()*H;this.vx=(Math.random()-.5)*.3;this.vy=(Math.random()-.5)*.3;this.r=Math.random()*1.2+.3;this.a=Math.random()*.35+.1;const cols=['139,92,246','244,114,182','34,211,238'];this.col=cols[Math.floor(Math.random()*cols.length)]}update(){this.x+=this.vx;this.y+=this.vy;if(this.x<0||this.x>W||this.y<0||this.y>H)this.reset()}draw(){ctx.beginPath();ctx.arc(this.x,this.y,this.r,0,Math.PI*2);ctx.fillStyle=`rgba(${this.col},${this.a})`;ctx.fill()}}for(let i=0;i<50;i++)pts.push(new P());function loop(){ctx.clearRect(0,0,W,H);pts.forEach(p=>{p.update();p.draw()});requestAnimationFrame(loop)}loop();})();

function filterPrompts(){
  const q=(document.getElementById('prompt-search').value||'').toLowerCase();
  const tag=(document.getElementById('prompt-tag-filter').value||'').toLowerCase();
  let vis=0;
  document.querySelectorAll('#prompts-list .prompt-item').forEach(item=>{
    const mt=!q||(item.dataset.title||'').includes(q);
    const mg=!tag||(item.dataset.tags||'').includes(tag);
    item.style.display=(mt&&mg)?'':'none';
    if(mt&&mg)vis++;
  });
  document.getElementById('no-results').style.display=vis===0?'block':'none';
}

function featurePrompt(id){
  fetch('feature_prompt.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'prompt_id='+id})
  .then(r=>r.json()).then(d=>{
    if(d.success){
      document.querySelectorAll('[id^="feat-btn-"]').forEach(b=>{b.innerHTML='<i class="fa-solid fa-star"></i> <span>POTD</span>';b.className='btn btn-yellow'});
      const b=document.getElementById('feat-btn-'+id);
      if(b){b.innerHTML='<i class="fa-solid fa-star"></i> <span>Un-POTD</span>';b.style.background='rgba(251,191,36,0.2)'}
    }
  });
}

function confirmDelete(id,name){document.getElementById('delete-prompt-id').value=id;document.getElementById('delete-modal-name').textContent='"'+name+'"';document.getElementById('delete-modal').style.display='flex'}
function closeDeleteModal(){document.getElementById('delete-modal').style.display='none'}

let _bulkAction='';
function setBulkAction(type,btn){
  _bulkAction=type;document.getElementById('bulk-action-val').value=type;
  document.querySelectorAll('.bulk-type-btn').forEach(b=>b.classList.remove('sel'));
  if(btn)btn.classList.add('sel');updateBulkSubmit();
}
function selectAllBulk(){
  const cbs=document.querySelectorAll('#bulk-list input[type=checkbox]');
  const anyUnchecked=Array.from(cbs).some(c=>!c.checked);
  cbs.forEach(c=>c.checked=anyUnchecked);updateBulkSubmit();
}
function updateBulkSubmit(){
  const cnt=document.querySelectorAll('#bulk-list input:checked').length;
  const btn=document.getElementById('bulk-submit');
  btn.disabled=!(cnt>0&&_bulkAction);
  btn.textContent=cnt>0&&_bulkAction?`Apply to ${cnt} prompt(s)`:'Apply to Selected';
}
document.getElementById('bulk-form')?.addEventListener('submit',function(e){
  const cnt=document.querySelectorAll('#bulk-list input:checked').length;
  if(!_bulkAction||cnt===0){e.preventDefault();return}
  if(!confirm(`Change ${cnt} prompt(s) to type: ${_bulkAction}?`))e.preventDefault();
});

// Mobile drawer
function openDrawer(){document.getElementById('sideDrawer').classList.add('open');document.getElementById('drawerOverlay').classList.add('open');}
function closeDrawer(){document.getElementById('sideDrawer').classList.remove('open');document.getElementById('drawerOverlay').classList.remove('open');}
// Three-dot dropdown
function togglePDrop(btn){var dd=btn.nextElementSibling;var wasOpen=dd.classList.contains('open');closePDrops();if(!wasOpen)dd.classList.add('open');}
function closePDrops(){document.querySelectorAll('.p-dropdown.open').forEach(function(d){d.classList.remove('open')});}
document.addEventListener('click',function(e){if(!e.target.closest('.p-dot-wrap'))closePDrops();});

let currentTrialId = 0;
function openTrialModal(id, currentState) {
  currentTrialId = id;
  document.getElementById('trial-toggle-switch').checked = (currentState == 1);
  document.getElementById('trial-modal').style.display = 'flex';
}
function closeTrialModal() {
  document.getElementById('trial-modal').style.display = 'none';
}
function saveTrialModal() {
  const isTrial = document.getElementById('trial-toggle-switch').checked ? 1 : 0;
  fetch('manage_prompts.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'toggle_trial_id=' + currentTrialId + '&is_trial=' + isTrial
  }).then(r => r.text()).then(txt => {
    if (txt.includes('OK')) {
      location.reload();
    } else {
      alert('Error updating trial status.');
    }
  });
}


let currentTrialId = 0;
function openTrialModal(id, currentState) {
  currentTrialId = id;
  document.getElementById('trial-toggle-switch').checked = (currentState == 1);
  document.getElementById('trial-modal').style.display = 'flex';
}
function closeTrialModal() {
  document.getElementById('trial-modal').style.display = 'none';
}
function saveTrialModal() {
  const isTrial = document.getElementById('trial-toggle-switch').checked ? 1 : 0;
  fetch('manage_prompts.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'toggle_trial_id=' + currentTrialId + '&is_trial=' + isTrial
  }).then(r => r.text()).then(txt => {
    if (txt.includes('OK')) {
      location.reload();
    } else {
      alert('Error updating trial status.');
    }
  });
}

</script>
</html>

