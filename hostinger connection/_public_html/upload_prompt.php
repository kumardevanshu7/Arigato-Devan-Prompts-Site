<?php
session_start();
require_once "db.php";

// Protect page (Admin Only)
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $_SESSION["error_msg"] =
        "You do not have permission to access the upload page.";
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Prompt — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include_once "gtag.php"; ?>
<style>
:root{--bg:#07060f;--surface:#0f0d1e;--border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);--accent:#8b5cf6;--accent2:#c084fc;--pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;--yellow:#fbbf24;--orange:#fb923c;--red:#f87171;--text:#e2e0ff;--muted:#9490bb;--font:'Inter',sans-serif}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:var(--font);overflow-x:hidden;min-height:100vh}
#sp{position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,var(--accent),var(--pink),var(--cyan));z-index:9999;box-shadow:0 0 10px var(--accent)}
#pc{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.3}
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
.sb-logout{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:700;color:var(--red);text-decoration:none;transition:all .2s}
.sb-logout:hover{background:rgba(248,113,113,0.1)}
.main{margin-left:220px;min-height:100vh;padding:28px 32px 80px;position:relative;z-index:1}
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:22px;flex-wrap:wrap}
.tb-title{font-size:1.5rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1}
.form-wrap{max-width:820px;margin:0 auto}
.card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:18px;backdrop-filter:blur(8px)}
.section-label{font-size:.68rem;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;display:flex;align-items:center;gap:7px}
.section-label i{color:var(--accent2)}
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:.72rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px}
.form-input{width:100%;padding:11px 16px;background:rgba(7,6,15,0.9);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:var(--font);font-size:.88rem;font-weight:500;outline:none;transition:all .2s;box-sizing:border-box}
.form-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(139,92,246,0.1)}
.form-input::placeholder{color:var(--muted)}
textarea.form-input{resize:vertical;min-height:100px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
/* TYPE SELECTOR */
.type-selector{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:4px}
.type-card{border:1px solid var(--border);border-radius:14px;padding:14px 8px;text-align:center;cursor:pointer;font-family:var(--font);font-weight:800;font-size:.8rem;transition:all .2s;background:rgba(15,13,30,0.6);position:relative;color:var(--muted)}
.type-card:hover{transform:translateY(-2px);border-color:rgba(139,92,246,0.3);color:var(--text)}
.type-card input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.type-card .type-icon{font-size:1.3rem;display:block;margin-bottom:6px}
.type-card.selected-secret{background:rgba(248,113,113,0.1);border-color:rgba(248,113,113,0.4);color:var(--red);box-shadow:0 0 0 2px rgba(248,113,113,0.1)}
.type-card.selected-unreleased{background:rgba(251,191,36,0.08);border-color:rgba(251,191,36,0.35);color:var(--yellow);box-shadow:0 0 0 2px rgba(251,191,36,0.08)}
.type-card.selected-viral{background:rgba(34,211,238,0.07);border-color:rgba(34,211,238,0.3);color:var(--cyan);box-shadow:0 0 0 2px rgba(34,211,238,0.07)}
.type-card.selected-uploaded{background:rgba(96,165,250,0.07);border-color:rgba(96,165,250,0.3);color:#60a5fa;box-shadow:0 0 0 2px rgba(96,165,250,0.07)}
/* TAG INPUT */
.tag-input-container{display:flex;flex-wrap:wrap;gap:7px;align-items:center;padding:9px 14px;background:rgba(7,6,15,0.9);border:1px solid var(--border);border-radius:12px;min-height:44px;cursor:text;transition:border-color .2s}
.tag-input-container:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px rgba(139,92,246,0.1)}
#tag-input-field{background:transparent;border:none;outline:none;color:var(--text);font-family:var(--font);font-size:.85rem;min-width:140px;flex:1}
#tag-input-field::placeholder{color:var(--muted)}
.tag-pill{background:rgba(139,92,246,0.12);border:1px solid rgba(139,92,246,0.25);color:var(--accent2);padding:3px 10px;border-radius:100px;font-size:.72rem;font-weight:800;display:flex;align-items:center;gap:5px}
.tag-pill .fa-xmark{cursor:pointer;opacity:.6;transition:opacity .2s}.tag-pill .fa-xmark:hover{opacity:1;color:var(--red)}
#tag-suggestions{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.tag-sug{padding:3px 10px;border:1px solid var(--border2);border-radius:100px;font-size:.68rem;font-weight:700;color:var(--muted);cursor:pointer;transition:all .2s;background:transparent}
.tag-sug:hover{border-color:var(--accent);color:var(--accent2);background:rgba(139,92,246,0.07)}
/* BWI SELECTOR */
.bwi-selector{display:flex;gap:10px;flex-wrap:wrap}
.bwi-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--border);border-radius:12px;padding:10px 20px;cursor:pointer;font-family:var(--font);font-weight:800;font-size:.85rem;transition:all .2s;color:var(--muted);background:rgba(15,13,30,0.6)}
.bwi-btn input[type=radio]{display:none}
.bwi-banana-opt.bwi-selected{background:rgba(251,191,36,0.1);color:var(--yellow);border-color:rgba(251,191,36,0.35);box-shadow:0 0 0 2px rgba(251,191,36,0.08)}
.bwi-chatgpt-opt.bwi-selected{background:rgba(74,222,128,0.08);color:var(--green);border-color:rgba(74,222,128,0.3);box-shadow:0 0 0 2px rgba(74,222,128,0.06)}
/* UNLOCK CODE */
#unlock-code-group{background:rgba(248,113,113,0.04);border:1px solid rgba(248,113,113,0.15);border-radius:12px;padding:14px;margin-bottom:18px}
#unlock-code-group .form-label{color:var(--red)}
#unlock-code-input{letter-spacing:.3em;text-transform:uppercase;font-weight:900;font-size:1.1rem;text-align:center;background:rgba(248,113,113,0.06);border-color:rgba(248,113,113,0.2)}
/* REEL LINK */
#reel-link-group{background:rgba(251,191,36,0.04);border:1px solid rgba(251,191,36,0.15);border-radius:12px;padding:14px;margin-bottom:18px}
/* TRIAL TOGGLE */
#trial-toggle-label{display:inline-flex;align-items:center;gap:10px;padding:12px 18px;border-radius:12px;border:1px solid rgba(139,92,246,0.2);background:rgba(139,92,246,0.05);color:var(--accent2);cursor:pointer;font-weight:800;font-size:.85rem;transition:all .2s;margin-bottom:10px}
#trial-toggle-label input[type=checkbox]{width:18px;height:18px;accent-color:var(--accent);cursor:pointer}
#trial-info-box{font-size:.78rem;color:var(--muted);margin-bottom:12px;line-height:1.5}
/* ASSETS */
#assets-toggle-label{display:inline-flex;align-items:center;gap:10px;padding:11px 16px;border-radius:11px;border:1px solid var(--border);background:rgba(15,13,30,0.6);color:var(--muted);cursor:pointer;font-weight:800;font-size:.82rem;transition:all .2s;margin-bottom:12px}
#assets-toggle-label input[type=checkbox]{accent-color:var(--accent)}
#assets-fields{display:none}
.file-upload-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.file-upload-btn{background:rgba(139,92,246,0.12);color:var(--accent2);border:1px solid rgba(139,92,246,0.3);border-radius:10px;padding:9px 16px;font-weight:800;font-size:.8rem;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:all .2s}
.file-upload-btn:hover{background:rgba(139,92,246,0.2)}
.file-upload-name{font-size:.78rem;color:var(--muted);font-weight:600}
/* EXTRA PROMPTS */
.ep-section{border:1px solid var(--border2);border-radius:12px;padding:16px;margin-bottom:14px;background:rgba(7,6,15,0.4)}
.ep-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.extra-add-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:10px;font-size:.78rem;font-weight:800;border:1px solid var(--border);color:var(--muted);cursor:pointer;font-family:var(--font);transition:all .2s;background:transparent;margin-right:8px}
.extra-add-btn:hover{border-color:var(--accent);color:var(--accent2)}
.ep-remove-btn{padding:6px 12px;border-radius:9px;font-size:.73rem;font-weight:800;border:1px solid rgba(248,113,113,0.22);background:rgba(248,113,113,0.07);color:var(--red);cursor:pointer;font-family:var(--font);transition:all .2s}
.ep-remove-btn:hover{background:rgba(248,113,113,0.14)}
/* SUBMIT */
.submit-btn{width:100%;padding:14px;background:linear-gradient(135deg,rgba(139,92,246,0.85),rgba(192,132,252,0.7));border:1px solid rgba(139,92,246,0.5);border-radius:13px;color:#fff;font-weight:900;font-size:1rem;cursor:pointer;font-family:var(--font);transition:all .2s;letter-spacing:.03em}
.submit-btn:hover{background:linear-gradient(135deg,rgba(139,92,246,0.98),rgba(192,132,252,0.85));box-shadow:0 6px 24px rgba(139,92,246,0.35);transform:translateY(-1px)}
.asset-preview-thumb img{width:80px;height:80px;object-fit:cover;border-radius:10px;border:1px solid var(--border2)}
#asset-previews{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:rgba(139,92,246,0.4);border-radius:10px}
.mob-nav{display:none;position:fixed;bottom:0;left:0;right:0;background:rgba(7,6,15,0.97);border-top:1px solid var(--border);z-index:500;padding:8px 0 max(8px,env(safe-area-inset-bottom));flex-direction:row;justify-content:space-around;align-items:center}
.mn-link{display:flex;flex-direction:column;align-items:center;gap:3px;font-size:.6rem;font-weight:700;color:var(--muted);text-decoration:none;padding:4px 8px;min-width:48px;transition:all .2s}
.mn-link.active,.mn-link:hover{color:var(--accent2)}.mn-link i{font-size:1.1rem}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-admin{padding:10px;justify-content:center}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px;padding:20px 16px 80px}}
@media(max-width:700px){.type-selector{grid-template-columns:1fr 1fr}.form-row{grid-template-columns:1fr}}
@media(max-width:600px){.sidebar{display:none}.main{margin-left:0;padding:14px 14px 80px}.mob-nav{display:flex}.type-selector{grid-template-columns:1fr 1fr}}
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
    <a href="analytics.php" class="d-link2 "><i class="fa-solid fa-chart-line"></i> Analytics</a>
    <div class="d-sec2">Content</div>
    <a href="upload_prompt.php" class="d-link2 active"><i class="fa-solid fa-upload"></i> Upload Prompt</a>
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
  <div class="mob-page-title"><i class="fa-solid fa-upload" style="-webkit-text-fill-color:var(--accent2);margin-right:6px"></i>Upload Prompt</div>
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
    <a href="upload_prompt.php" class="sb-link active"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
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
    <div class="tb-title"><i class="fa-solid fa-upload" style="color:var(--accent2);-webkit-text-fill-color:var(--accent2)"></i> Upload Prompt</div>
    <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:9px;font-size:.75rem;font-weight:800;border:1px solid rgba(139,92,246,0.22);background:rgba(139,92,246,0.07);color:var(--accent2);text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
  </div>

  <div class="form-wrap">
  <form method="POST" action="upload.php" enctype="multipart/form-data">

    <!-- PROMPT TYPE -->
    <div class="card">
      <div class="section-label"><i class="fa-solid fa-tag"></i> Prompt Type</div>
      <div class="type-selector">
        <label class="type-card" id="card-secret">
          <input type="radio" name="prompt_type" value="secret" checked onchange="onTypeChange('secret')">
          <i class="fa-solid fa-lock type-icon"></i> Secret Code
        </label>
        <label class="type-card" id="card-unreleased">
          <input type="radio" name="prompt_type" value="unreleased" onchange="onTypeChange('unreleased')">
          <i class="fa-solid fa-star type-icon"></i> Unreleased
        </label>
        <label class="type-card" id="card-viral">
          <input type="radio" name="prompt_type" value="insta_viral" onchange="onTypeChange('insta_viral')">
          <i class="fa-brands fa-instagram type-icon"></i> Insta Viral
        </label>
        <label class="type-card" id="card-uploaded">
          <input type="radio" name="prompt_type" value="already_uploaded" onchange="onTypeChange('already_uploaded')">
          <i class="fa-solid fa-clock-rotate-left type-icon"></i> Already Uploaded
        </label>
      </div>
    </div>

    <!-- TRIAL + TITLE -->
    <div class="card">
      <div class="section-label"><i class="fa-solid fa-circle-info"></i> Basic Info</div>

      <label id="trial-toggle-label">
        <input type="checkbox" name="is_trial" id="is_trial" onchange="toggleTrialUI(this)">
        <i class="fa-solid fa-eye-slash"></i> Trial Reel Mode
      </label>
      <div id="trial-info-box"><i class="fa-solid fa-eye" style="color:var(--green)"></i> <strong style="color:var(--green)">Visible</strong> &mdash; Prompt will appear normally on the site.</div>

      <div class="form-group">
        <label class="form-label">Title <span style="color:var(--red)">*</span></label>
        <input type="text" id="title" name="title" class="form-input" placeholder="Enter prompt title..." required>
      </div>

      <div class="form-group">
        <label class="form-label">Tags <span style="color:var(--red)">*</span> <span style="color:var(--muted);font-weight:600;text-transform:none;letter-spacing:0">(press Enter or comma)</span></label>
        <div class="tag-input-container" onclick="document.getElementById('tag-input-field').focus()">
          <input type="text" id="tag-input-field" placeholder="Add tag...">
          <input type="hidden" id="tag" name="tag">
        </div>
        <div id="tag-suggestions">
          <?php
          try { $stmt=$pdo->query("SELECT tag FROM prompts"); $all_tags=[]; while($row=$stmt->fetch()){$tarr=explode(",",$row["tag"]);foreach($tarr as $t){$t=trim(strtolower($t));if($t&&strlen($t)>1)$all_tags[$t]=true;}} $all_tags=array_keys($all_tags);sort($all_tags); foreach(array_slice($all_tags,0,30) as $t): ?><span class="tag-sug" onclick="addTag('<?= htmlspecialchars($t) ?>')"><?= htmlspecialchars($t) ?></span><?php endforeach; } catch(Exception $e) {}
          ?>
        </div>
      </div>
    </div>

    <!-- BEST WORKS IN -->
    <div class="card">
      <div class="section-label"><i class="fa-solid fa-robot"></i> Best Works In</div>
      <div class="bwi-selector">
        <label class="bwi-btn bwi-banana-opt" onclick="setBwi('nano_banana',this)">
          <input type="radio" name="best_works_in" value="nano_banana">
          <i class="fa-solid fa-banana"></i> Nano Banana AI
        </label>
        <label class="bwi-btn bwi-chatgpt-opt" onclick="setBwi('chatgpt',this)">
          <input type="radio" name="best_works_in" value="chatgpt">
          <i class="fa-solid fa-robot"></i> ChatGPT
        </label>
      </div>
    </div>

    <!-- PROMPT TEXT -->
    <div class="card">
      <div class="section-label"><i class="fa-solid fa-pen"></i> Prompt Content</div>
      <div class="form-group">
        <label class="form-label">Prompt Text <span style="color:var(--red)">*</span></label>
        <textarea id="prompt_text" name="prompt_text" class="form-input" rows="6" placeholder="Enter the main prompt text here..." required></textarea>
      </div>

      <!-- EXTRA PROMPTS -->
      <div id="ep2-section" style="display:none" class="ep-section">
        <div class="ep-header">
          <div class="section-label" style="margin-bottom:0"><i class="fa-solid fa-plus"></i> Extra Prompt 2</div>
          <button type="button" class="ep-remove-btn" onclick="removeEP(2)"><i class="fa-solid fa-xmark"></i> Remove</button>
        </div>
        <div class="form-group">
          <label class="form-label">EP2 Title</label>
          <input type="text" id="ep2_title" name="extra_prompt_2_title" class="form-input" placeholder="Optional variant title">
        </div>
        <div class="form-group">
          <label class="form-label">EP2 Text</label>
          <textarea id="ep2_text" name="extra_prompt_2_text" class="form-input" rows="4" placeholder="Second prompt variant..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">EP2 Cover Image</label>
          <div class="file-upload-row">
            <label class="file-upload-btn"><input type="file" name="extra_prompt_2_image" id="ep2_image" accept="image/*" style="display:none" onchange="document.getElementById('ep2-fname').textContent=this.files[0]?.name||'No file'"><i class="fa-solid fa-image"></i> Choose Image</label>
            <span id="ep2-fname" class="file-upload-name">No file chosen</span>
          </div>
        </div>
      </div>

      <div id="ep3-section" style="display:none" class="ep-section">
        <div class="ep-header">
          <div class="section-label" style="margin-bottom:0"><i class="fa-solid fa-plus"></i> Extra Prompt 3</div>
          <button type="button" class="ep-remove-btn" onclick="removeEP(3)"><i class="fa-solid fa-xmark"></i> Remove</button>
        </div>
        <div class="form-group">
          <label class="form-label">EP3 Title</label>
          <input type="text" id="ep3_title" name="extra_prompt_3_title" class="form-input" placeholder="Optional variant title">
        </div>
        <div class="form-group">
          <label class="form-label">EP3 Text</label>
          <textarea id="ep3_text" name="extra_prompt_3_text" class="form-input" rows="4" placeholder="Third prompt variant..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">EP3 Cover Image</label>
          <div class="file-upload-row">
            <label class="file-upload-btn"><input type="file" name="extra_prompt_3_image" id="ep3_image" accept="image/*" style="display:none" onchange="document.getElementById('ep3-fname').textContent=this.files[0]?.name||'No file'"><i class="fa-solid fa-image"></i> Choose Image</label>
            <span id="ep3-fname" class="file-upload-name">No file chosen</span>
          </div>
        </div>
      </div>

      <div id="ep-add-btns" style="display:flex;gap:10px;flex-wrap:wrap">
        <button type="button" id="ep-add2-btn" class="extra-add-btn" onclick="addEP(2)"><i class="fa-solid fa-plus"></i> Add Prompt 2</button>
      </div>
    </div>

    <!-- UNLOCK CODE -->
    <div id="unlock-code-group">
      <label class="form-label"><i class="fa-solid fa-key"></i> Access Code (6 chars)</label>
      <input type="text" id="unlock-code-input" name="unlock_code" class="form-input" maxlength="6" pattern="[A-Za-z0-9]{6}" placeholder="_ _ _ _ _ _">
    </div>

    <!-- REEL LINK -->
    <div id="reel-link-group" style="display:none;border:1px solid rgba(251,191,36,0.15);border-radius:12px;padding:14px;margin-bottom:18px;background:rgba(251,191,36,0.03)">
      <label class="form-label" style="color:var(--yellow)"><i class="fa-brands fa-instagram"></i> Reel Link (Instagram)</label>
      <input type="url" id="reel_link" name="reel_link" class="form-input" placeholder="https://www.instagram.com/reel/...">
    </div>

    <!-- ASSETS -->
    <div class="card">
      <div class="section-label"><i class="fa-solid fa-images"></i> Assets (Optional)</div>
      <label id="assets-toggle-label">
        <input type="checkbox" name="has_assets" id="has_assets" onchange="toggleAssets(this)">
        <i class="fa-solid fa-paperclip"></i> Include Extra Asset Images
      </label>
      <div id="assets-fields">
        <div class="form-group">
          <label class="form-label">Asset Title</label>
          <input type="text" name="asset_title" class="form-input" placeholder="e.g. Reference shots">
        </div>
        <div class="form-group">
          <label class="form-label">Asset Images (max 2)</label>
          <div class="file-upload-row">
            <label class="file-upload-btn"><input type="file" name="asset_images[]" accept="image/*" multiple style="display:none" onchange="handleAssetFiles(this);document.getElementById('asset-file-display').textContent=this.files.length+' file(s)'"><i class="fa-solid fa-images"></i> Choose Images</label>
            <span id="asset-file-display" class="file-upload-name">No files chosen</span>
          </div>
          <div id="asset-previews"></div>
        </div>
      </div>
    </div>

    <!-- COVER IMAGE + SUBMIT -->
    <div class="card">
      <div class="section-label"><i class="fa-solid fa-image"></i> Cover Image &amp; Final</div>
      <div class="form-row">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Cover Image <span style="color:var(--red)">*</span></label>
          <div class="file-upload-row">
            <label class="file-upload-btn">
              <input type="file" id="image" name="image" accept="image/*" required style="display:none" onchange="document.getElementById('file-name-display').textContent=this.files[0]?.name||'No file chosen'">
              <i class="fa-solid fa-image"></i> Choose Cover
            </label>
            <span id="file-name-display" class="file-upload-name">No file chosen</span>
          </div>
        </div>
        <div class="form-group" id="reel-link-col" style="margin-bottom:0;display:none">
          <label class="form-label">Instagram Reel URL</label>
          <input type="url" name="reel_link_col" class="form-input" placeholder="https://instagram.com/reel/...">
        </div>
      </div>
      <div style="margin-top:20px">
        <button type="submit" class="submit-btn"><i class="fa-solid fa-upload"></i> Upload Prompt</button>
      </div>
    </div>

  </form>
  </div>
</main>

<nav class="mob-nav">
  <a href="dashboard.php" class="mn-link"><i class="fa-solid fa-gauge-high"></i><span>Home</span></a>
  <a href="manage_prompts.php" class="mn-link"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Prompts</span></a>
  <a href="user_management.php" class="mn-link"><i class="fa-solid fa-users"></i><span>Users</span></a>
  <a href="analytics.php" class="mn-link"><i class="fa-solid fa-chart-line"></i><span>Stats</span></a>
  <a href="upload_prompt.php" class="mn-link active"><i class="fa-solid fa-plus"></i><span>Upload</span></a>
</nav>

<script>
window.addEventListener('scroll',()=>{const h=document.documentElement;document.getElementById('sp').style.width=(h.scrollTop/(h.scrollHeight-h.clientHeight)*100)+'%';},{passive:true});
(function(){const c=document.getElementById('pc');if(!c)return;const ctx=c.getContext('2d');let W,H,pts=[];function rs(){W=c.width=window.innerWidth;H=c.height=window.innerHeight}rs();window.addEventListener('resize',rs);class P{constructor(){this.reset()}reset(){this.x=Math.random()*W;this.y=Math.random()*H;this.vx=(Math.random()-.5)*.3;this.vy=(Math.random()-.5)*.3;this.r=Math.random()*1.2+.3;this.a=Math.random()*.35+.1;const cols=['139,92,246','244,114,182','34,211,238'];this.col=cols[Math.floor(Math.random()*cols.length)]}update(){this.x+=this.vx;this.y+=this.vy;if(this.x<0||this.x>W||this.y<0||this.y>H)this.reset()}draw(){ctx.beginPath();ctx.arc(this.x,this.y,this.r,0,Math.PI*2);ctx.fillStyle=`rgba(${this.col},${this.a})`;ctx.fill()}}for(let i=0;i<40;i++)pts.push(new P());function loop(){ctx.clearRect(0,0,W,H);pts.forEach(p=>{p.update();p.draw()});requestAnimationFrame(loop)}loop();})();

const tagInputContainer=document.querySelector('.tag-input-container');
const tagInputField=document.getElementById('tag-input-field');
const hiddenTagInput=document.getElementById('tag');
const codeGroup=document.getElementById('unlock-code-group');
const codeInput=document.getElementById('unlock-code-input');
const reelLinkGroup=document.getElementById('reel-link-group');
const reelLinkInput=document.getElementById('reel_link');
let tags=[];

function onTypeChange(type){
  document.querySelectorAll('.type-card').forEach(c=>c.className='type-card');
  if(type==='secret'){document.getElementById('card-secret').className='type-card selected-secret';codeGroup.style.display='block';codeInput.required=true;reelLinkGroup.style.display='block';reelLinkInput.required=true;}
  else if(type==='unreleased'){document.getElementById('card-unreleased').className='type-card selected-unreleased';codeGroup.style.display='none';codeInput.required=false;codeInput.value='';reelLinkGroup.style.display='none';reelLinkInput.required=false;reelLinkInput.value='';}
  else if(type==='insta_viral'){document.getElementById('card-viral').className='type-card selected-viral';codeGroup.style.display='none';codeInput.required=false;codeInput.value='';reelLinkGroup.style.display='none';reelLinkInput.required=false;reelLinkInput.value='';}
  else if(type==='already_uploaded'){document.getElementById('card-uploaded').className='type-card selected-uploaded';codeGroup.style.display='none';codeInput.required=false;codeInput.value='';reelLinkGroup.style.display='none';reelLinkInput.required=false;reelLinkInput.value='';}
}
onTypeChange('secret');

function renderTags(){
  document.querySelectorAll('.tag-pill').forEach(el=>el.remove());
  tags.forEach((tag,index)=>{
    const pill=document.createElement('span');pill.className='tag-pill';
    pill.innerHTML=`${tag} <i class="fa-solid fa-xmark" onclick="removeTag(${index})"></i>`;
    tagInputContainer.insertBefore(pill,tagInputField);
  });
  hiddenTagInput.value=tags.join(',');
}
function addTag(tag){
  tag=tag.trim().replace(/[^a-zA-Z0-9 ]/g,'').replace(/\s+/g,' ');
  tag=tag.replace(/\b\w/g,c=>c.toUpperCase());
  if(tag&&!tags.includes(tag)){tags.push(tag);renderTags();}
  tagInputField.value='';
}
window.removeTag=function(index){tags.splice(index,1);renderTags();}
tagInputField.addEventListener('keydown',function(e){
  if(e.key==='Enter'||e.key===','){e.preventDefault();addTag(this.value);}
  else if(e.key==='Backspace'&&this.value===''&&tags.length>0){tags.pop();renderTags();}
});

document.querySelector('form').addEventListener('submit',function(e){
  if(tagInputField.value.trim())addTag(tagInputField.value.trim());
  if(tags.length===0){e.preventDefault();alert('Please add at least one tag!');tagInputField.focus();return;}
  const selectedType=document.querySelector('input[name="prompt_type"]:checked')?.value;
  if(selectedType==='secret'){
    if(!codeInput.value.trim()||codeInput.value.trim().length!==6){e.preventDefault();alert('Access Code must be exactly 6 characters!');codeInput.focus();return;}
    if(!reelLinkInput.value.trim()){e.preventDefault();alert('Reel Link is required for Secret Code type!');reelLinkInput.focus();return;}
  }
  hiddenTagInput.value=tags.join(',');
});

function addEP(num){
  document.getElementById('ep'+num+'-section').style.display='block';
  document.getElementById('ep-add'+num+'-btn').style.display='none';
  if(num===2){
    const addBtns=document.getElementById('ep-add-btns');
    const btn=document.createElement('button');btn.type='button';btn.id='ep-add3-btn';btn.className='extra-add-btn';
    btn.innerHTML='<i class="fa-solid fa-plus"></i> Add Prompt 3';btn.onclick=function(){addEP(3)};addBtns.appendChild(btn);
  }
}
function removeEP(num){
  document.getElementById('ep'+num+'-section').style.display='none';
  document.getElementById('ep'+num+'_text').value='';
  const img=document.getElementById('ep'+num+'_image');if(img)img.value='';
  const fname=document.getElementById('ep'+num+'-fname');if(fname)fname.textContent='No file chosen';
  const addBtn=document.getElementById('ep-add'+num+'-btn');if(addBtn)addBtn.style.display='';
  if(num===2){removeEP(3);const b=document.getElementById('ep-add3-btn');if(b)b.remove();}
}
function setBwi(val,el){
  document.querySelectorAll('.bwi-btn').forEach(b=>b.classList.remove('bwi-selected'));
  el.classList.add('bwi-selected');el.querySelector('input[type=radio]').checked=true;
}
function toggleAssets(cb){document.getElementById('assets-fields').style.display=cb.checked?'block':'none';}
function handleAssetFiles(input){
  const files=Array.from(input.files).slice(0,2);
  if(input.files.length>2)alert('Max 2 images allowed. Only first 2 will be used.');
  const previews=document.getElementById('asset-previews');previews.innerHTML='';
  files.forEach(f=>{const reader=new FileReader();reader.onload=e=>{const div=document.createElement('div');div.className='asset-preview-thumb';div.innerHTML=`<img loading="lazy" src="${e.target.result}" alt="preview">`;previews.appendChild(div)};reader.readAsDataURL(f)});
}
function toggleTrialUI(cb){
  const label=document.getElementById('trial-toggle-label');const info=document.getElementById('trial-info-box');
  if(cb.checked){label.style.background='rgba(249,115,22,0.1)';label.style.borderColor='rgba(249,115,22,0.3)';label.style.color='var(--orange)';info.innerHTML='<i class="fa-solid fa-eye-slash" style="color:var(--orange)"></i> <strong style="color:var(--orange)">Trial Mode ON</strong> &mdash; Hidden from gallery &amp; listings. Share via direct link from Prompt Links.';}
  else{label.style.background='rgba(139,92,246,0.05)';label.style.borderColor='rgba(139,92,246,0.2)';label.style.color='var(--accent2)';info.innerHTML='<i class="fa-solid fa-eye" style="color:var(--green)"></i> <strong style="color:var(--green)">Visible</strong> &mdash; Prompt will appear normally on the site.';}
}
function openDrawer(){document.getElementById('sideDrawer').classList.add('open');document.getElementById('drawerOverlay').classList.add('open');}
function closeDrawer(){document.getElementById('sideDrawer').classList.remove('open');document.getElementById('drawerOverlay').classList.remove('open');}
</script>
</html>


