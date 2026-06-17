<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once "db.php";

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// ── AJAX: Toggle show_on_homepage ─────────────────────────────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle') {
    header('Content-Type: application/json');
    $id  = (int)($_POST['id'] ?? 0);
    $val = (int)($_POST['val'] ?? 0);
    try {
        $s = $pdo->prepare("UPDATE feedbacks SET show_on_homepage=? WHERE id=?");
        $s->execute([$val, $id]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// ── AJAX: Delete feedback ─────────────────────────────────────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'delete') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    try {
        $s = $pdo->prepare("DELETE FROM feedbacks WHERE id=?");
        $s->execute([$id]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// ── Fetch all feedbacks with user info ────────────────────────────────────────
try {
    $feedbacks = $pdo->query("
        SELECT f.*, u.username, u.avatar, u.profile_image, u.gender
        FROM feedbacks f
        LEFT JOIN users u ON f.user_id = u.id
        ORDER BY f.submitted_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $feedbacks = []; }

// Stats
$total_fb   = count($feedbacks);
$visible_fb = count(array_filter($feedbacks, fn($f) => $f['show_on_homepage']));
$avg_rating = $total_fb > 0
    ? round(array_sum(array_column($feedbacks, 'rating')) / $total_fb, 1)
    : 0;

// Admin info
$admin_info = $pdo->query("SELECT username FROM users WHERE role='admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$admin_name = $admin_info['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback Manager — Arigato Admin</title>
<meta name="robots" content="noindex">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ══ SAME BASE AS dashboard.php ══ */
:root {
    --bg:#07060f; --surface:#0f0d1e; --surface2:#15122a;
    --border:rgba(139,92,246,0.18); --border2:rgba(139,92,246,0.08);
    --accent:#8b5cf6; --accent2:#c084fc;
    --pink:#f472b6; --cyan:#22d3ee; --green:#4ade80;
    --yellow:#fbbf24; --orange:#fb923c; --red:#f87171;
    --text:#e2e0ff; --muted:#9490bb;
    --font:'Inter',sans-serif; --mono:'JetBrains Mono',monospace;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:var(--font);overflow-x:hidden;min-height:100vh}
#sp{position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,var(--accent),var(--pink),var(--cyan));z-index:9999;transition:width .1s;box-shadow:0 0 10px var(--accent)}

/* ══ SIDEBAR (copy from dashboard) ══ */
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

/* ══ MAIN ══ */
.main{margin-left:220px;min-height:100vh;padding:28px 32px 80px;position:relative;z-index:1;max-width:1300px}
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:24px;flex-wrap:wrap}
.tb-title{font-size:1.5rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1;display:flex;align-items:center;gap:10px}
.tb-title i{-webkit-text-fill-color:var(--accent2);font-size:1.3rem}
.tb-time{font-size:.72rem;font-weight:700;color:var(--muted);background:rgba(15,13,30,0.8);border:1px solid var(--border2);padding:6px 14px;border-radius:100px}

/* ══ STAT CARDS ══ */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:26px}
.scard{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:20px 18px;transition:all .3s;position:relative;overflow:hidden}
.scard::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;border-radius:16px 16px 0 0;opacity:0;transition:opacity .3s}
.scard:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,0.3)}
.scard:hover::before{opacity:1}
.sc-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;margin-bottom:12px}
.sc-val{font-size:1.9rem;font-weight:900;line-height:1;margin-bottom:3px}
.sc-lbl{font-size:.65rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}
.s-purple .sc-icon{background:rgba(139,92,246,0.15);color:var(--accent2)}.s-purple .sc-val{color:var(--accent2)}.s-purple::before{background:var(--accent2)}
.s-green  .sc-icon{background:rgba(74,222,128,0.1);color:var(--green)}.s-green .sc-val{color:var(--green)}.s-green::before{background:var(--green)}
.s-yellow .sc-icon{background:rgba(251,191,36,0.1);color:var(--yellow)}.s-yellow .sc-val{color:var(--yellow)}.s-yellow::before{background:var(--yellow)}

/* ══ FEEDBACK CARDS ══ */
.fb-grid{display:flex;flex-direction:column;gap:14px}
.fb-card{
    background:rgba(15,13,30,0.7);
    border:1px solid var(--border2);
    border-radius:16px; padding:20px 22px;
    transition:all .25s;
    display:grid;
    grid-template-columns: 56px 1fr auto;
    gap:16px; align-items:start;
}
.fb-card:hover{border-color:var(--border);background:rgba(21,18,42,0.8)}
.fb-card.homepage-on{border-color:rgba(74,222,128,0.3);background:rgba(74,222,128,0.03)}

/* Avatar */
.fb-avatar{width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0}
.fb-av-ph{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.1rem;color:#fff;flex-shrink:0}

/* Content */
.fb-content{}
.fb-user-row{display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap}
.fb-username{font-size:.9rem;font-weight:800;color:var(--text)}
.fb-gender{font-size:.8rem}
.gi-m{color:var(--cyan)}.gi-f{color:var(--pink)}.gi-a{color:var(--muted)}
.fb-date{font-size:.62rem;font-weight:600;color:var(--muted);margin-left:auto}
.fb-rating{display:inline-flex;align-items:center;gap:5px;background:rgba(139,92,246,0.08);border:1px solid var(--border2);border-radius:100px;padding:3px 10px;font-size:.72rem;font-weight:800;color:var(--accent2);margin-bottom:10px}
.fb-text{font-size:.88rem;color:var(--muted);line-height:1.65;font-style:italic;}

/* Actions */
.fb-actions{display:flex;flex-direction:column;align-items:flex-end;gap:10px;flex-shrink:0}

/* Toggle switch */
.toggle-wrap{display:flex;align-items:center;gap:8px}
.toggle-lbl{font-size:.65rem;font-weight:700;color:var(--muted);white-space:nowrap}
.toggle{position:relative;width:44px;height:24px;flex-shrink:0}
.toggle input{opacity:0;width:0;height:0;position:absolute}
.toggle-slider{
    position:absolute;inset:0;background:rgba(255,255,255,0.07);
    border:1px solid var(--border);border-radius:24px;
    cursor:pointer;transition:all .25s;
}
.toggle-slider::before{
    content:'';position:absolute;
    width:18px;height:18px;left:2px;top:2px;
    background:#fff;border-radius:50%;
    transition:all .25s;box-shadow:0 2px 4px rgba(0,0,0,0.3);
}
.toggle input:checked + .toggle-slider{background:rgba(74,222,128,0.25);border-color:rgba(74,222,128,0.5)}
.toggle input:checked + .toggle-slider::before{transform:translateX(20px);background:var(--green)}
.homepage-badge{font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:2px 8px;border-radius:100px;white-space:nowrap}
.badge-on{background:rgba(74,222,128,0.12);color:var(--green);border:1px solid rgba(74,222,128,0.25)}
.badge-off{background:rgba(255,255,255,0.04);color:var(--muted);border:1px solid var(--border2)}

/* Delete button */
.del-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:9px;font-size:.7rem;font-weight:800;border:1px solid rgba(248,113,113,0.22);background:rgba(248,113,113,0.06);color:var(--red);cursor:pointer;transition:all .2s;font-family:var(--font)}
.del-btn:hover{background:rgba(248,113,113,0.14)}

/* ══ EMPTY STATE ══ */
.empty-state{text-align:center;padding:70px 20px;color:var(--muted)}
.empty-icon{font-size:3rem;margin-bottom:16px;opacity:.4}
.empty-title{font-size:1.1rem;font-weight:800;color:var(--text);margin-bottom:6px}
.empty-sub{font-size:.82rem}

/* ══ DELETE MODAL ══ */
.modal-ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(14px);z-index:2000;align-items:center;justify-content:center;padding:20px}
.modal-ov.open{display:flex}
.modal-box{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:28px;max-width:400px;width:100%;box-shadow:0 24px 80px rgba(0,0,0,0.6)}
.del-icon-big{width:56px;height:56px;border-radius:16px;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.22);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--red);margin:0 auto 14px}
.modal-title{font-size:1.05rem;font-weight:900;color:var(--text);text-align:center;margin-bottom:6px}
.modal-sub{font-size:.8rem;color:var(--muted);text-align:center;margin-bottom:22px}
.modal-btns{display:flex;gap:10px}
.modal-cancel{flex:1;padding:11px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:11px;color:var(--muted);font-weight:800;cursor:pointer;font-family:var(--font);font-size:.85rem;transition:all .2s}
.modal-cancel:hover{border-color:var(--accent);color:var(--text)}
.modal-confirm{flex:1;padding:11px;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.25);border-radius:11px;color:var(--red);font-weight:800;cursor:pointer;font-family:var(--font);font-size:.85rem;transition:all .2s}
.modal-confirm:hover{background:rgba(248,113,113,0.2)}

/* ══ MOBILE ══ */
.mob-topbar{display:none;position:sticky;top:0;z-index:300;background:rgba(7,6,15,0.96);backdrop-filter:blur(16px);border-bottom:1px solid var(--border2);padding:13px 16px;align-items:center;gap:12px}
.mob-menu-btn{width:38px;height:38px;border-radius:10px;background:rgba(139,92,246,0.08);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--accent2);font-size:1rem;cursor:pointer}
.mob-page-title{font-size:1rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1}
.drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(6px);z-index:500}
.drawer{position:fixed;left:0;top:0;bottom:0;width:265px;background:rgba(7,6,15,0.99);border-right:1px solid var(--border);z-index:600;display:flex;flex-direction:column;transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1)}
.drawer.open{transform:translateX(0)}
.drawer-overlay.open{display:block}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:18px 16px;border-bottom:1px solid var(--border2)}
.drawer-brand{font-size:.8rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.drawer-close{width:32px;height:32px;border-radius:8px;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:var(--red);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem}
.drawer-nav{flex:1;overflow-y:auto;padding:8px 10px}
.d-sec{font-size:.6rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 8px 5px}
.d-link{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;margin-bottom:2px}
.d-link:hover,.d-link.active{background:rgba(139,92,246,0.1);color:var(--accent2)}
.d-link i{width:18px;text-align:center}
.drawer-bottom{padding:12px 10px;border-top:1px solid var(--border2)}
.d-logout{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:700;color:var(--red);text-decoration:none}
.d-logout:hover{background:rgba(248,113,113,0.08)}

/* Toast */
.toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:rgba(21,18,42,0.96);border:1px solid var(--border);border-radius:100px;padding:10px 22px;font-size:.8rem;font-weight:700;color:var(--text);z-index:9999;opacity:0;transition:all .3s cubic-bezier(.34,1.56,.64,1);backdrop-filter:blur(14px);white-space:nowrap;pointer-events:none}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
.toast.ok{border-color:rgba(74,222,128,0.4);color:var(--green)}
.toast.err{border-color:rgba(248,113,113,0.4);color:var(--red)}

/* Cursor removed to prevent overlapping circle issue */

@media(max-width:768px){
    .sidebar{display:none}.main{margin-left:0;padding:14px 14px 90px}
    .mob-topbar{display:flex!important}
    .stats-row{grid-template-columns:1fr 1fr}
    .fb-card{grid-template-columns:44px 1fr;gap:10px}
    .fb-actions{flex-direction:row;align-items:center;grid-column:1/-1}
    .fb-avatar,.fb-av-ph{width:40px;height:40px}
}
@media(max-width:480px){
    .stats-row{grid-template-columns:1fr}
    .toggle-lbl{display:none}
}
</style>
</head>
<body>
<div id="sp"></div>
<div class="toast" id="toast"></div>

<!-- MOBILE DRAWER -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="sideDrawer">
    <div class="drawer-head">
        <div class="drawer-brand">Arigato Admin</div>
        <div class="drawer-close" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></div>
    </div>
    <nav class="drawer-nav">
        <div class="d-sec">Overview</div>
        <a href="dashboard.php"      class="d-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="analytics.php"      class="d-link"><i class="fa-solid fa-chart-line"></i> Analytics</a>
        <div class="d-sec">Content</div>
        <a href="upload_prompt.php"  class="d-link"><i class="fa-solid fa-upload"></i> Upload Prompt</a>
        <a href="manage_prompts.php" class="d-link"><i class="fa-solid fa-list-check"></i> Manage Prompts</a>
        <a href="potd_manager.php"   class="d-link"><i class="fa-solid fa-sun"></i> POTD Manager</a>
        <div class="d-sec">Blog</div>
        <a href="blog_admin.php"     class="d-link"><i class="fa-solid fa-pen-nib"></i> Blog Admin</a>
        <a href="blog_create.php"    class="d-link"><i class="fa-solid fa-plus"></i> New Post</a>
        <div class="d-sec">Community</div>
        <a href="feedback_admin.php" class="d-link active"><i class="fa-solid fa-comments"></i> Feedbacks</a>
        <div class="d-sec">Users</div>
        <a href="user_management.php" class="d-link"><i class="fa-solid fa-users"></i> Users</a>
        <div class="d-sec">Tools</div>
        <a href="index.php" class="d-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
    </nav>
    <div class="drawer-bottom">
        <a href="login.php?logout=1" class="d-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<!-- MOBILE TOPBAR -->
<div class="mob-topbar">
    <div class="mob-menu-btn" onclick="openDrawer()"><i class="fa-solid fa-bars"></i></div>
    <div class="mob-page-title"><i class="fa-solid fa-comments" style="-webkit-text-fill-color:var(--accent2);margin-right:6px"></i>Feedbacks</div>
    <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;background:rgba(34,211,238,0.08);color:var(--cyan);border:1px solid rgba(34,211,238,0.2);" target="_blank"><i class="fa-solid fa-house"></i> Site</a>
</div>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sb-logo">
        <div class="sb-brand"><i class="fa-solid fa-shield-halved"></i> <span>Arigato Admin</span></div>
    </div>
    <div class="sb-admin">
        <?php $sav = !empty($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : ''; ?>
        <?php if($sav): ?><img src="<?= $sav ?>" class="sb-av" alt="">
        <?php else: ?><div class="sb-av-ph"><?= strtoupper(substr($admin_name,0,1)) ?></div><?php endif; ?>
        <div><div class="sb-uname"><?= htmlspecialchars($admin_name) ?></div><div class="sb-role">Admin</div></div>
    </div>
    <nav class="sb-nav">
        <div class="sb-sec">Overview</div>
        <a href="dashboard.php"       class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
        <a href="analytics.php"       class="sb-link"><i class="fa-solid fa-chart-line"></i> <span>Analytics</span></a>
        <div class="sb-sec">Content</div>
        <a href="upload_prompt.php"   class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
        <a href="manage_prompts.php"  class="sb-link"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
        <a href="prompt_links.php"    class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
        <a href="potd_manager.php"    class="sb-link"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
        <div class="sb-sec">Blog</div>
        <a href="blog_admin.php"      class="sb-link"><i class="fa-solid fa-pen-nib"></i> <span>Blog Admin</span></a>
        <a href="blog_create.php"     class="sb-link"><i class="fa-solid fa-plus"></i> <span>New Post</span></a>
        <div class="sb-sec">Community</div>
        <a href="feedback_admin.php"  class="sb-link active"><i class="fa-solid fa-comments"></i> <span>Feedbacks</span></a>
        <div class="sb-sec">Users</div>
        <a href="user_management.php" class="sb-link"><i class="fa-solid fa-users"></i> <span>Users</span></a>
        <div class="sb-sec">Tools</div>
        <a href="index.php"           class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Site</span></a>
    </nav>
    <div class="sb-bottom">
        <a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main">
    <div class="topbar">
        <div class="tb-title"><i class="fa-solid fa-comments"></i> Feedback Manager</div>
        <div class="tb-time"><i class="fa-regular fa-clock"></i> <?= date('D, d M Y | h:i A') ?> IST</div>
    </div>

    <!-- STATS -->
    <div class="stats-row">
        <div class="scard s-purple">
            <div class="sc-icon"><i class="fa-solid fa-comments"></i></div>
            <div class="sc-val"><?= $total_fb ?></div>
            <div class="sc-lbl">Total Feedbacks</div>
        </div>
        <div class="scard s-green">
            <div class="sc-icon"><i class="fa-solid fa-eye"></i></div>
            <div class="sc-val"><?= $visible_fb ?></div>
            <div class="sc-lbl">Shown on Homepage</div>
        </div>
        <div class="scard s-yellow">
            <div class="sc-icon"><i class="fa-solid fa-star"></i></div>
            <div class="sc-val"><?= $avg_rating ?></div>
            <div class="sc-lbl">Avg Rating / 10</div>
        </div>
    </div>

    <!-- FEEDBACK LIST -->
    <?php if (empty($feedbacks)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-comment-slash"></i></div>
        <div class="empty-title">No feedbacks yet</div>
        <div class="empty-sub">Users haven't submitted any feedback. Share the <a href="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/feedback.php" style="color:var(--accent2)">feedback page</a> link!</div>
    </div>
    <?php else: ?>
    <div class="fb-grid" id="fbGrid">
    <?php
    $emojis = ['😭','😢','😟','😕','🙂','😊','😄','😁','🤩','🔥','⭐'];
    foreach ($feedbacks as $fb):
        $r      = max(0, min(10, (int)$fb['rating']));
        $em     = $emojis[$r];
        $gender = strtolower(trim($fb['gender'] ?? ''));
        $gi     = match(true) {
            in_array($gender, ['male','m'])   => '<i class="fa-solid fa-mars gi-m"></i>',
            in_array($gender, ['female','f']) => '<i class="fa-solid fa-venus gi-f"></i>',
            default                           => '<i class="fa-solid fa-genderless gi-a"></i>',
        };
        $av_src = $fb['profile_image'] ?? $fb['avatar'] ?? '';
        $uname  = htmlspecialchars($fb['username'] ?? 'Deleted User');
        $seed   = urlencode($fb['username'] ?? 'user');
        $fb_on  = (bool)$fb['show_on_homepage'];
        $date   = date('d M Y', strtotime($fb['submitted_at']));
    ?>
    <div class="fb-card <?= $fb_on ? 'homepage-on' : '' ?>" id="fbCard<?= $fb['id'] ?>">
        <!-- Avatar -->
        <?php if ($av_src): ?>
        <img src="<?= htmlspecialchars($av_src) ?>" class="fb-avatar" alt="<?= $uname ?>"
             onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=<?= $seed ?>'">
        <?php else: ?>
        <div class="fb-av-ph"><?= strtoupper(substr($fb['username'] ?? 'U', 0, 1)) ?></div>
        <?php endif; ?>

        <!-- Content -->
        <div class="fb-content">
            <div class="fb-user-row">
                <span class="fb-username"><?= $uname ?></span>
                <span class="fb-gender"><?= $gi ?></span>
                <span class="fb-date"><i class="fa-regular fa-calendar"></i> <?= $date ?></span>
            </div>
            <div class="fb-rating"><?= $em ?> &nbsp;<?= $r ?> / 10</div>
            <div class="fb-text"><?= htmlspecialchars($fb['feedback_text']) ?></div>
        </div>

        <!-- Actions -->
        <div class="fb-actions">
            <div class="toggle-wrap">
                <span class="toggle-lbl">Homepage</span>
                <label class="toggle">
                    <input type="checkbox"
                           id="tog<?= $fb['id'] ?>"
                           <?= $fb_on ? 'checked' : '' ?>
                           onchange="toggleHomepage(<?= $fb['id'] ?>, this.checked)">
                    <span class="toggle-slider"></span>
                </label>
                <span class="homepage-badge <?= $fb_on ? 'badge-on' : 'badge-off' ?>" id="badge<?= $fb['id'] ?>">
                    <?= $fb_on ? 'ON' : 'OFF' ?>
                </span>
            </div>
            <button class="del-btn" onclick="confirmDelete(<?= $fb['id'] ?>, '<?= $uname ?>')">
                <i class="fa-solid fa-trash"></i> Delete
            </button>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<!-- DELETE MODAL -->
<div class="modal-ov" id="deleteModal">
    <div class="modal-box">
        <div class="del-icon-big"><i class="fa-solid fa-trash"></i></div>
        <div class="modal-title">Delete Feedback?</div>
        <div class="modal-sub" id="modalSub">This action cannot be undone.</div>
        <div class="modal-btns">
            <button class="modal-cancel" onclick="closeModal()">Cancel</button>
            <button class="modal-confirm" id="confirmDelBtn">Delete</button>
        </div>
    </div>
</div>

<script>
// ── Custom Cursor ──────────────────────────────────────────────────────────────
const dot  = document.getElementById('c-dot');
const ring = document.getElementById('c-ring');
if (dot && ring) {
    let rx=0,ry=0;
    document.addEventListener('mousemove', e => {
        dot.style.left=e.clientX+'px'; dot.style.top=e.clientY+'px';
        rx+=(e.clientX-rx)*.12; ry+=(e.clientY-ry)*.12;
        ring.style.left=rx+'px'; ring.style.top=ry+'px';
    });
    document.querySelectorAll('a,button,label,.toggle').forEach(el=>{
        el.addEventListener('mouseenter',()=>document.body.classList.add('c-hover'));
        el.addEventListener('mouseleave',()=>document.body.classList.remove('c-hover'));
    });
    document.addEventListener('mousedown',()=>document.body.classList.add('c-click'));
    document.addEventListener('mouseup',  ()=>document.body.classList.remove('c-click'));
    (function animate(){requestAnimationFrame(animate);ring.style.left=rx+'px';ring.style.top=ry+'px';rx+=(parseFloat(dot.style.left||0)-rx)*.12;ry+=(parseFloat(dot.style.top||0)-ry)*.12;})();
}

// ── Scroll Progress ─────────────────────────────────────────────────────────
const sp=document.getElementById('sp');
window.addEventListener('scroll',()=>{
    const pct=window.scrollY/(document.body.scrollHeight-window.innerHeight)*100;
    sp.style.width=pct+'%';
});

// ── Mobile Drawer ───────────────────────────────────────────────────────────
function openDrawer()  { document.getElementById('sideDrawer').classList.add('open'); document.getElementById('drawerOverlay').classList.add('open'); }
function closeDrawer() { document.getElementById('sideDrawer').classList.remove('open'); document.getElementById('drawerOverlay').classList.remove('open'); }

// ── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg, type='ok') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show ' + type;
    setTimeout(() => t.className = 'toast', 2500);
}

// ── Toggle Homepage ──────────────────────────────────────────────────────────
function toggleHomepage(id, isOn) {
    const card  = document.getElementById('fbCard' + id);
    const badge = document.getElementById('badge' + id);
    fetch('feedback_admin.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'ajax_action=toggle&id=' + id + '&val=' + (isOn ? 1 : 0)
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            if (isOn) {
                card.classList.add('homepage-on');
                badge.textContent = 'ON';
                badge.className = 'homepage-badge badge-on';
                showToast('✓ Shown on homepage', 'ok');
            } else {
                card.classList.remove('homepage-on');
                badge.textContent = 'OFF';
                badge.className = 'homepage-badge badge-off';
                showToast('Hidden from homepage', 'ok');
            }
        } else {
            showToast('Error: ' + (d.error || 'try again'), 'err');
            // Revert toggle
            document.getElementById('tog' + id).checked = !isOn;
        }
    })
    .catch(() => showToast('Network error', 'err'));
}

// ── Delete ───────────────────────────────────────────────────────────────────
let deleteTargetId = null;
function confirmDelete(id, name) {
    deleteTargetId = id;
    document.getElementById('modalSub').textContent = 'Delete feedback by "' + name + '"? This cannot be undone.';
    document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
    document.getElementById('deleteModal').classList.remove('open');
    deleteTargetId = null;
}
document.getElementById('confirmDelBtn').addEventListener('click', function() {
    if (!deleteTargetId) return;
    const id = deleteTargetId;
    closeModal();
    fetch('feedback_admin.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'ajax_action=delete&id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            const card = document.getElementById('fbCard' + id);
            card.style.transition = 'all .35s ease';
            card.style.opacity = '0';
            card.style.transform = 'translateX(-20px)';
            setTimeout(() => card.remove(), 360);
            showToast('🗑 Feedback deleted', 'ok');
        } else {
            showToast('Delete failed', 'err');
        }
    })
    .catch(() => showToast('Network error', 'err'));
});

// Close modal on backdrop click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
