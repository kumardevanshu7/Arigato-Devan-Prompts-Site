<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
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
.type-selector{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:4px}
.type-card{border:1px solid var(--border);border-radius:14px;padding:14px 8px;text-align:center;cursor:pointer;font-family:var(--font);font-weight:800;font-size:.8rem;transition:all .2s;background:rgba(15,13,30,0.6);position:relative;color:var(--muted)}
.type-card:hover{transform:translateY(-2px);border-color:rgba(139,92,246,0.3);color:var(--text)}
.type-card input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.type-card .type-icon{font-size:1.3rem;display:block;margin-bottom:6px}
.type-card.selected-secret{background:rgba(248,113,113,0.1);border-color:rgba(248,113,113,0.4);color:var(--red);box-shadow:0 0 0 2px rgba(248,113,113,0.1)}
.type-card.selected-unreleased{background:rgba(251,191,36,0.08);border-color:rgba(251,191,36,0.35);color:var(--yellow);box-shadow:0 0 0 2px rgba(251,191,36,0.08)}
.type-card.selected-viral{background:rgba(34,211,238,0.07);border-color:rgba(34,211,238,0.3);color:var(--cyan);box-shadow:0 0 0 2px rgba(34,211,238,0.07)}
.type-card.selected-uploaded{background:rgba(96,165,250,0.07);border-color:rgba(96,165,250,0.3);color:#60a5fa;box-shadow:0 0 0 2px rgba(96,165,250,0.07)}
.type-card.selected-direct{background:rgba(244,63,94,0.07);border-color:rgba(244,63,94,0.3);color:#f43f5e;box-shadow:0 0 0 2px rgba(244,63,94,0.07)}
.type-card.selected-solo{background:rgba(74,222,128,0.08);border-color:rgba(74,222,128,0.35);color:var(--green);box-shadow:0 0 0 2px rgba(74,222,128,0.08)}
.tap-card{border:1px solid var(--border);border-radius:10px;padding:10px 14px;text-align:center;cursor:pointer;font-family:var(--font);font-weight:800;font-size:.8rem;transition:all .2s;background:rgba(15,13,30,0.6);position:relative;color:var(--muted);flex:1;min-width:60px;}
.tap-card:hover{transform:translateY(-2px);border-color:rgba(244,63,94,0.3);color:var(--text);}
.tap-card input[type=radio]{position:absolute;opacity:0;width:0;height:0;}
.tap-card.sel-tap{background:rgba(244,63,94,0.1);border-color:rgba(244,63,94,0.4);color:#f43f5e;box-shadow:0 0 0 2px rgba(244,63,94,0.1);}
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
/* SOLO BEFORE / AFTER */
#solo-media-card{display:none}
.solo-main-upload{display:grid;grid-template-columns:minmax(0,1fr) 54px minmax(0,1fr);gap:14px;align-items:stretch}
.solo-upload-panel{border:1px solid var(--border);border-radius:14px;padding:16px;background:rgba(7,6,15,.45)}
.solo-upload-panel.after{border-color:rgba(74,222,128,.25);background:rgba(74,222,128,.035)}
.solo-upload-title{display:flex;align-items:center;gap:7px;color:var(--accent2);font-size:.76rem;font-weight:900;text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px}
.solo-upload-panel.after .solo-upload-title{color:var(--green)}
.solo-image-preview{display:none;margin-top:13px;overflow:hidden;border:1px solid var(--border);border-radius:12px;background:#07060f}
.solo-image-preview.show{display:block}
.solo-image-preview img{display:block;width:100%;aspect-ratio:9/16;max-height:360px;object-fit:cover}
.solo-arrow{display:flex;align-items:center;justify-content:center;color:var(--green);font-size:1.5rem}
.solo-help{font-size:.72rem;line-height:1.55;color:var(--muted);margin-top:9px}
.solo-example{border:1px solid var(--border2);border-radius:14px;padding:16px;margin-top:12px;background:rgba(7,6,15,.38)}
.solo-example-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}
.solo-example-title{font-size:.76rem;font-weight:900;color:var(--text)}
.solo-example-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.solo-example-side{min-width:0}
.solo-example-preview{display:none;margin-top:10px;overflow:hidden;border:1px solid var(--border2);border-radius:11px;background:#07060f}
.solo-example-preview.show{display:block}
.solo-example-preview img{display:block;width:100%;aspect-ratio:9/16;max-height:300px;object-fit:cover}
.solo-example-remove{border:1px solid rgba(248,113,113,.25);background:rgba(248,113,113,.07);color:var(--red);border-radius:8px;padding:6px 10px;font-weight:800;font-size:.7rem;cursor:pointer}
.solo-add-example{margin-top:14px;display:inline-flex;align-items:center;gap:7px;border:1px dashed rgba(74,222,128,.35);background:rgba(74,222,128,.06);color:var(--green);border-radius:11px;padding:10px 15px;font-family:var(--font);font-size:.78rem;font-weight:900;cursor:pointer}
.solo-add-example:disabled{opacity:.45;cursor:not-allowed}
.solo-count{font-size:.7rem;color:var(--muted);margin-left:8px}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:rgba(139,92,246,0.4);border-radius:10px}
.mob-nav{display:none;position:fixed;bottom:0;left:0;right:0;background:rgba(7,6,15,0.97);border-top:1px solid var(--border);z-index:500;padding:8px 0 max(8px,env(safe-area-inset-bottom));flex-direction:row;justify-content:space-around;align-items:center}
.mn-link{display:flex;flex-direction:column;align-items:center;gap:3px;font-size:.6rem;font-weight:700;color:var(--muted);text-decoration:none;padding:4px 8px;min-width:48px;transition:all .2s}
.mn-link.active,.mn-link:hover{color:var(--accent2)}.mn-link i{font-size:1.1rem}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-admin{padding:10px;justify-content:center}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px;padding:20px 16px 80px}}
@media(max-width:700px){.type-selector{grid-template-columns:1fr 1fr}.form-row{grid-template-columns:1fr}.solo-main-upload{grid-template-columns:1fr}.solo-arrow{transform:rotate(90deg);min-height:34px}.solo-example-grid{grid-template-columns:1fr}}
@media(max-width:600px){.sidebar{display:none}.main{margin-left:0;padding:14px 14px 80px}.mob-nav{display:flex}.type-selector{grid-template-columns:1fr 1fr}}
/* MOBILE TOPBAR */
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
body::before, body::after { display: none !important; background-image: none !important; }
</style>
</head>
<body class="no-site-cursor">
<div id="sp"></div>
<canvas id="pc"></canvas>
<!-- MOBILE TOP BAR -->
<div class="mob-topbar">
  <div class="mob-menu-btn" onclick="openDrawer()"><i class="fa-solid fa-bars"></i></div>
  <div class="mob-page-title"><i class="fa-solid fa-upload" style="-webkit-text-fill-color:var(--accent2);margin-right:6px"></i>Upload Prompt</div>
  <a href="index.php" class="mob-home-btn" target="_blank"><i class="fa-solid fa-house"></i> Site</a>
</div>

<?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-upload" style="color:var(--accent2);-webkit-text-fill-color:var(--accent2)"></i> Upload Prompt</div>
    <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:9px;font-size:.75rem;font-weight:800;border:1px solid rgba(139,92,246,0.22);background:rgba(139,92,246,0.07);color:var(--accent2);text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
  </div>

  <?php if (!empty($_SESSION["error_msg"])): ?>
    <div class="flash flash-err" style="background:rgba(248,113,113,0.1); border:1px solid var(--red); color:var(--red); padding:10px 15px; border-radius:10px; margin-bottom:15px; font-size:0.85rem; font-weight:700;"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION["error_msg"]) ?></div>
    <?php unset($_SESSION["error_msg"]); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION["success_msg"])): ?>
    <div class="flash flash-ok" style="background:rgba(74,222,128,0.1); border:1px solid var(--green); color:var(--green); padding:10px 15px; border-radius:10px; margin-bottom:15px; font-size:0.85rem; font-weight:700;"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION["success_msg"]) ?></div>
    <?php unset($_SESSION["success_msg"]); ?>
  <?php endif; ?>

  <div class="form-wrap">
  <form method="POST" action="upload.php" enctype="multipart/form-data" class="needs-validation" novalidate>
    <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">

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
        <label class="type-card" id="card-uploaded">
          <input type="radio" name="prompt_type" value="already_uploaded" onchange="onTypeChange('already_uploaded')">
          <i class="fa-solid fa-clock-rotate-left type-icon"></i> Already Uploaded
        </label>
        <label class="type-card" id="card-direct">
          <input type="radio" name="prompt_type" value="direct" onchange="onTypeChange('direct')">
          <i class="fa-solid fa-hand-pointer type-icon"></i> Direct Prompt
        </label>
        <label class="type-card" id="card-solo">
          <input type="radio" name="prompt_type" value="solo" onchange="onTypeChange('solo')">
          <i class="fa-solid fa-user type-icon"></i> SOLO
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
          try { $stmt=$pdo->query("SELECT tag FROM prompts"); $all_tags=[]; while($row=$stmt->fetch()){$tarr=explode(",",$row["tag"]);foreach($tarr as $t){$t=trim(strtolower($t));if($t&&strlen($t)>1)$all_tags[$t]=true;}} $all_tags=array_keys($all_tags);sort($all_tags); foreach($all_tags as $t): ?><span class="tag-sug" onclick="addTag('<?= htmlspecialchars($t) ?>')">+<?= htmlspecialchars($t) ?></span><?php endforeach; } catch(Exception $e) {}
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

      <!-- EXTRA PROMPTS (Up to 10 variants) -->
      <?php
      $ep_ordinals = [
          2 => 'Second',
          3 => 'Third',
          4 => 'Fourth',
          5 => 'Fifth',
          6 => 'Sixth',
          7 => 'Seventh',
          8 => 'Eighth',
          9 => 'Ninth',
          10 => 'Tenth',
      ];
      for ($ep = 2; $ep <= 10; $ep++):
          $ord = $ep_ordinals[$ep] ?? "Variant {$ep}";
      ?>
      <div id="ep<?= $ep ?>-section" style="display:none" class="ep-section">
        <div class="ep-header">
          <div class="section-label" style="margin-bottom:0"><i class="fa-solid fa-plus"></i> Extra Prompt <?= $ep ?></div>
          <button type="button" class="ep-remove-btn" onclick="removeEP(<?= $ep ?>)"><i class="fa-solid fa-xmark"></i> Remove</button>
        </div>
        <div class="form-group">
          <label class="form-label">EP<?= $ep ?> Title</label>
          <input type="text" id="ep<?= $ep ?>_title" name="extra_prompt_<?= $ep ?>_title" class="form-input" placeholder="Optional variant title">
        </div>
        <div class="form-group">
          <label class="form-label">EP<?= $ep ?> Text</label>
          <textarea id="ep<?= $ep ?>_text" name="extra_prompt_<?= $ep ?>_text" class="form-input" rows="4" placeholder="<?= $ord ?> prompt variant..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">EP<?= $ep ?> Cover Image</label>
          <div class="file-upload-row">
            <label class="file-upload-btn"><input type="file" name="extra_prompt_<?= $ep ?>_image" id="ep<?= $ep ?>_image" accept="image/*" style="display:none" onchange="document.getElementById('ep<?= $ep ?>-fname').textContent=this.files[0]?.name||'No file'"><i class="fa-solid fa-image"></i> Choose Image</label>
            <span id="ep<?= $ep ?>-fname" class="file-upload-name">No file chosen</span>
          </div>
        </div>
      </div>
      <?php endfor; ?>

      <div id="ep-add-btns" style="display:flex;gap:10px;flex-wrap:wrap">
        <button type="button" id="ep-add2-btn" class="extra-add-btn" onclick="addEP(2)"><i class="fa-solid fa-plus"></i> Add Prompt 2</button>
      </div>
    </div>

    <!-- UNLOCK CODE -->
    <div id="unlock-code-group">
      <label class="form-label"><i class="fa-solid fa-key"></i> Access Code (6 chars)</label>
      <input type="text" id="unlock-code-input" name="unlock_code" class="form-input" maxlength="6" pattern="[A-Za-z0-9]{6}" placeholder="_ _ _ _ _ _">
    </div>

    <!-- DIRECT TAPS -->
    <div id="direct-taps-group" style="display:none;border:1px solid rgba(244,63,94,0.15);border-radius:12px;padding:14px;margin-bottom:18px;background:rgba(244,63,94,0.03)">
      <label class="form-label" style="color:#f43f5e;margin-bottom:10px;display:block;"><i class="fa-solid fa-heart"></i> Heart Taps Required</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <label class="tap-card sel-tap" id="tap-09">
          <input type="radio" name="direct_taps" value="09" checked onchange="onTapChange('09')">
          <span style="font-size:1.1rem;display:block;margin-bottom:2px;"><i class="fa-solid fa-heart"></i></span>09
        </label>
        <label class="tap-card" id="tap-11">
          <input type="radio" name="direct_taps" value="11" onchange="onTapChange('11')">
          <span style="font-size:1.1rem;display:block;margin-bottom:2px;"><i class="fa-solid fa-heart"></i></span>11
        </label>
        <label class="tap-card" id="tap-19">
          <input type="radio" name="direct_taps" value="19" onchange="onTapChange('19')">
          <span style="font-size:1.1rem;display:block;margin-bottom:2px;"><i class="fa-solid fa-heart"></i></span>19
        </label>
        <label class="tap-card" id="tap-21">
          <input type="radio" name="direct_taps" value="21" onchange="onTapChange('21')">
          <span style="font-size:1.1rem;display:block;margin-bottom:2px;"><i class="fa-solid fa-heart"></i></span>21
        </label>
        <label class="tap-card" id="tap-37">
          <input type="radio" name="direct_taps" value="37" onchange="onTapChange('37')">
          <span style="font-size:1.1rem;display:block;margin-bottom:2px;"><i class="fa-solid fa-heart"></i></span>37
        </label>
        <label class="tap-card" id="tap-77">
          <input type="radio" name="direct_taps" value="77" onchange="onTapChange('77')">
          <span style="font-size:1.1rem;display:block;margin-bottom:2px;"><i class="fa-solid fa-heart"></i></span>77
        </label>
      </div>
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
        <input type="checkbox" name="has_assets" id="has_assets" value="1" onchange="toggleAssets(this)">
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

    <!-- HOW TO USE THIS PROMPT -->
    <div class="card" id="how-to-use-card">
      <div class="section-label"><i class="fa-solid fa-list-check"></i> How to use this prompt? <span style="font-weight:600;color:var(--muted);text-transform:none;letter-spacing:0;font-size:.78rem;">(step-by-step instructions with sample pics)</span></div>
      <p style="font-size:.82rem;color:var(--muted);margin:-8px 0 14px 0;">Add actionable step-by-step tips. You can upload up to 5 sample pics per point (e.g. Upload Image 1 as boy + Image 2 as girl) or reuse previously uploaded pics with 1 click.</p>
      
      <div id="how-to-use-container" style="display:flex;flex-direction:column;gap:12px;margin-bottom:12px;"></div>

      <button type="button" class="btn-add-step" id="btn-add-how-step" onclick="addHowStep()" style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.06);border:1px dashed var(--border);color:var(--text);font-weight:700;font-size:0.82rem;padding:9px 18px;border-radius:10px;cursor:pointer;transition:all .2s ease;">
        <i class="fa-solid fa-plus" style="color:#10b981;"></i> Add Next Step
      </button>
    </div>

    <!-- ABOUT + SEO -->
    <div class="card">
      <div class="section-label"><i class="fa-solid fa-magnifying-glass-chart"></i> About &amp; SEO (applies to all prompt types)</div>
      <div class="form-group">
        <label class="form-label">About This Prompt <span style="color:var(--muted);font-weight:600;text-transform:none;letter-spacing:0">— shown on the prompt page (max 200 words)</span></label>
        <textarea name="about_prompt" id="about-prompt-input" class="form-input" rows="6" maxlength="2500" placeholder="Write a natural editorial note about this prompt — what it does, who it is for, and how to use it." oninput="updateAboutWordCount(this)"></textarea>
        <div style="font-size:.72rem;color:var(--muted);font-weight:700;margin-top:6px"><span id="about-word-count">0</span> / 200 words</div>
      </div>
      <div class="form-group">
        <label class="form-label">SEO Description <span style="color:var(--muted);font-weight:600;text-transform:none;letter-spacing:0">— shows in Google search results (max 160 chars)</span></label>
        <textarea name="description" class="form-input" rows="3" maxlength="160" placeholder="Short description for search engines. Leave blank to auto-generate."></textarea>
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">SEO Keywords <span style="color:var(--muted);font-weight:600;text-transform:none;letter-spacing:0">— max 10 phrases</span></label>
        <input type="text" name="meta_keywords" class="form-input" maxlength="500" placeholder="e.g. ai solo prompt, chatgpt portrait, viral reel prompt">
      </div>
    </div>

    <!-- SOLO BEFORE / AFTER -->
    <div class="card" id="solo-media-card">
      <div class="section-label"><i class="fa-solid fa-arrow-right-arrow-left"></i> SOLO Before &amp; After</div>
      <div class="solo-main-upload">
        <div class="solo-upload-panel">
          <div class="solo-upload-title"><i class="fa-solid fa-image"></i> Before Image</div>
          <label class="file-upload-btn">
            <input type="file" name="solo_before_image" id="solo-before-image" accept="image/*" style="display:none" onchange="previewSoloMain(this,'solo-before-name','solo-before-preview')">
            <i class="fa-solid fa-upload"></i> Choose Before
          </label>
          <div class="file-upload-name" id="solo-before-name" style="margin-top:9px">No file chosen</div>
          <div class="solo-image-preview" id="solo-before-preview"><img alt="Before image preview"></div>
          <p class="solo-help">Upload the original/reference photo.</p>
        </div>
        <div class="solo-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
        <div class="solo-upload-panel after">
          <div class="solo-upload-title"><i class="fa-solid fa-wand-magic-sparkles"></i> After / Result Image</div>
          <label class="file-upload-btn">
            <input type="file" name="solo_after_image" id="solo-after-image" accept="image/*" style="display:none" onchange="previewSoloMain(this,'solo-after-name','solo-after-preview')">
            <i class="fa-solid fa-upload"></i> Choose Result
          </label>
          <div class="file-upload-name" id="solo-after-name" style="margin-top:9px">No file chosen</div>
          <div class="solo-image-preview" id="solo-after-preview"><img alt="After result preview"></div>
          <p class="solo-help">This result image will also become the public gallery cover.</p>
        </div>
      </div>

      <div style="margin-top:22px;padding-top:18px;border-top:1px solid var(--border2)">
        <div class="section-label" style="margin-bottom:4px"><i class="fa-solid fa-images"></i> Set Examples (Optional)</div>
        <p class="solo-help" style="margin:0">Each example contains one before and one after image. Maximum 5 examples.</p>
        <div id="solo-examples"></div>
        <button type="button" class="solo-add-example" id="solo-add-example" onclick="addSoloExample()">
          <i class="fa-solid fa-plus"></i> Add Example
        </button>
        <span class="solo-count" id="solo-example-count">0 / 5</span>
      </div>
    </div>

    <!-- COVER IMAGE + SUBMIT -->
    <div class="card">
      <div class="section-label"><i class="fa-solid fa-image"></i> Cover Image &amp; Final</div>
      <div class="form-row" id="standard-cover-row">
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
        <button type="submit" class="submit-btn" id="upload-submit-btn"><i class="fa-solid fa-upload"></i> Upload Prompt</button>
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
const directTapsGroup=document.getElementById('direct-taps-group');
const reelLinkGroup=document.getElementById('reel-link-group');
const reelLinkInput=document.getElementById('reel_link');
const soloMediaCard=document.getElementById('solo-media-card');
const soloBeforeInput=document.getElementById('solo-before-image');
const soloAfterInput=document.getElementById('solo-after-image');
const standardCoverRow=document.getElementById('standard-cover-row');
const standardCoverInput=document.getElementById('image');
const uploadSubmitBtn=document.getElementById('upload-submit-btn');
let tags=[];

function onTypeChange(type){
  document.querySelectorAll('.type-card').forEach(c=>c.className='type-card');
  const isSolo=type==='solo';
  soloMediaCard.style.display=isSolo?'block':'none';
  standardCoverRow.style.display=isSolo?'none':'grid';
  soloBeforeInput.required=isSolo;
  soloAfterInput.required=isSolo;
  standardCoverInput.required=!isSolo;
  if(isSolo){
    uploadSubmitBtn.innerHTML='<i class="fa-solid fa-floppy-disk"></i> Save SOLO Prompt';
  } else {
    uploadSubmitBtn.innerHTML='<i class="fa-solid fa-upload"></i> Upload Prompt';
  }
  
  if(type==='secret'){document.getElementById('card-secret').className='type-card selected-secret';codeGroup.style.display='block';codeInput.required=true;reelLinkGroup.style.display='block';reelLinkInput.required=true;if(directTapsGroup)directTapsGroup.style.display='none';}
  else if(type==='unreleased'){document.getElementById('card-unreleased').className='type-card selected-unreleased';codeGroup.style.display='none';codeInput.required=false;codeInput.value='';reelLinkGroup.style.display='none';reelLinkInput.required=false;reelLinkInput.value='';if(directTapsGroup)directTapsGroup.style.display='none';}
  else if(type==='already_uploaded'){document.getElementById('card-uploaded').className='type-card selected-uploaded';codeGroup.style.display='none';codeInput.required=false;codeInput.value='';reelLinkGroup.style.display='none';reelLinkInput.required=false;reelLinkInput.value='';if(directTapsGroup)directTapsGroup.style.display='none';}
  else if(type==='direct'){document.getElementById('card-direct').className='type-card selected-direct';codeGroup.style.display='none';codeInput.required=false;codeInput.value='';reelLinkGroup.style.display='none';reelLinkInput.required=false;reelLinkInput.value='';if(directTapsGroup)directTapsGroup.style.display='block';}
  else if(type==='solo'){document.getElementById('card-solo').className='type-card selected-solo';codeGroup.style.display='none';codeInput.required=false;codeInput.value='';reelLinkGroup.style.display='none';reelLinkInput.required=false;reelLinkInput.value='';if(directTapsGroup)directTapsGroup.style.display='block';}
}

function onTapChange(val){
  document.querySelectorAll('.tap-card').forEach(c=>c.classList.remove('sel-tap'));
  const el = document.getElementById('tap-'+val);
  if(el) el.classList.add('sel-tap');
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

  // Autocomplete Filter for Tags
  tagInputField.addEventListener('input', function() {
    const val = this.value.trim().toLowerCase();
    const suggestions = document.querySelectorAll('.tag-sug');
    suggestions.forEach(s => {
      if(val === '' || s.textContent.toLowerCase().includes(val)) {
        s.style.display = 'inline-block';
      } else {
        s.style.display = 'none';
      }
    });
  });
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
  if(selectedType==='solo'){
    if(!soloBeforeInput.files.length){e.preventDefault();alert('Please choose the SOLO before image.');soloBeforeInput.click();return;}
    if(!soloAfterInput.files.length){e.preventDefault();alert('Please choose the SOLO after/result image.');soloAfterInput.click();return;}
    const pairs=Array.from(document.querySelectorAll('.solo-example'));
    for(const pair of pairs){
      const before=pair.querySelector('input[name="solo_example_before[]"]');
      const after=pair.querySelector('input[name="solo_example_after[]"]');
      if((before.files.length&&!after.files.length)||(!before.files.length&&after.files.length)){
        e.preventDefault();alert('Every SOLO example needs both a before and an after image.');return;
      }
    }
  }
  hiddenTagInput.value=tags.join(',');
});

function updateEpAddBtn(){
  const addBtns=document.getElementById('ep-add-btns');
  if(!addBtns) return;
  addBtns.innerHTML='';
  let nextNum=2;
  for(let i=2;i<=10;i++){
    const sec=document.getElementById('ep'+i+'-section');
    if(sec && sec.style.display!=='none'){
      nextNum=i+1;
    }
  }
  if(nextNum<=10){
    const btn=document.createElement('button');
    btn.type='button';
    btn.id='ep-add'+nextNum+'-btn';
    btn.className='extra-add-btn';
    btn.innerHTML='<i class="fa-solid fa-plus"></i> Add Prompt '+nextNum;
    btn.onclick=function(){addEP(nextNum)};
    addBtns.appendChild(btn);
  }
}

function addEP(num){
  const sec=document.getElementById('ep'+num+'-section');
  if(sec) sec.style.display='block';
  updateEpAddBtn();
}

function removeEP(num){
  for(let i=10;i>=num;i--){
    const sec=document.getElementById('ep'+i+'-section');
    if(sec && sec.style.display!=='none'){
      sec.style.display='none';
      const t=document.getElementById('ep'+i+'_text');if(t)t.value='';
      const title=document.getElementById('ep'+i+'_title');if(title)title.value='';
      const img=document.getElementById('ep'+i+'_image');if(img)img.value='';
      const fname=document.getElementById('ep'+i+'-fname');if(fname)fname.textContent='No file chosen';
    }
  }
  updateEpAddBtn();
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
let soloExampleIndex=0;
function readSoloPreview(input,preview){
  const file=input.files[0];
  const img=preview?.querySelector('img');
  if(!file||!preview||!img){if(preview)preview.classList.remove('show');return;}
  const reader=new FileReader();
  reader.onload=e=>{img.src=e.target.result;preview.classList.add('show');};
  reader.readAsDataURL(file);
}
function previewSoloMain(input,nameId,previewId){
  document.getElementById(nameId).textContent=input.files[0]?.name||'No file chosen';
  readSoloPreview(input,document.getElementById(previewId));
}
function previewSoloExample(input){
  const side=input.closest('.solo-example-side');
  if(!side)return;
  side.querySelector('.solo-example-fname').textContent=input.files[0]?.name||'No file chosen';
  readSoloPreview(input,side.querySelector('.solo-example-preview'));
}
function addSoloExample(){
  const wrap=document.getElementById('solo-examples');
  if(wrap.querySelectorAll('.solo-example').length>=5)return;
  soloExampleIndex++;
  const item=document.createElement('div');
  item.className='solo-example';
  item.innerHTML=`
    <div class="solo-example-head">
      <div class="solo-example-title"><i class="fa-solid fa-layer-group" style="color:var(--green);margin-right:6px"></i>Example <span class="solo-example-number"></span></div>
      <button type="button" class="solo-example-remove" onclick="removeSoloExample(this)"><i class="fa-solid fa-trash"></i> Remove</button>
    </div>
    <div class="solo-example-grid">
      <div class="solo-example-side">
        <label class="form-label">Before Picture</label>
        <label class="file-upload-btn">
          <input type="file" name="solo_example_before[]" accept="image/*" style="display:none" onchange="previewSoloExample(this)">
          <i class="fa-solid fa-image"></i> Choose Before
        </label>
        <div class="file-upload-name solo-example-fname" style="margin-top:7px">No file chosen</div>
        <div class="solo-example-preview"><img alt="Example before preview"></div>
      </div>
      <div class="solo-example-side">
        <label class="form-label">After Picture</label>
        <label class="file-upload-btn">
          <input type="file" name="solo_example_after[]" accept="image/*" style="display:none" onchange="previewSoloExample(this)">
          <i class="fa-solid fa-wand-magic-sparkles"></i> Choose After
        </label>
        <div class="file-upload-name solo-example-fname" style="margin-top:7px">No file chosen</div>
        <div class="solo-example-preview"><img alt="Example after preview"></div>
      </div>
    </div>`;
  wrap.appendChild(item);
  renumberSoloExamples();
}
function removeSoloExample(btn){
  btn.closest('.solo-example').remove();
  renumberSoloExamples();
}
function renumberSoloExamples(){
  const items=Array.from(document.querySelectorAll('#solo-examples .solo-example'));
  items.forEach((item,i)=>item.querySelector('.solo-example-number').textContent=i+1);
  document.getElementById('solo-example-count').textContent=items.length+' / 5';
  document.getElementById('solo-add-example').disabled=items.length>=5;
}
function toggleTrialUI(cb){
  if(!cb) return;
  const label=document.getElementById('trial-toggle-label');const info=document.getElementById('trial-info-box');
  if(!label || !info) return;
  if(cb.checked){
    label.style.background='rgba(249,115,22,0.1)';label.style.borderColor='rgba(249,115,22,0.3)';label.style.color='var(--orange)';
    info.innerHTML='<i class="fa-solid fa-eye-slash" style="color:var(--orange)"></i> <strong style="color:var(--orange)">Trial Mode ON</strong> &mdash; Hidden from gallery &amp; listings. Share via direct link from Prompt Links.';
  } else {
    label.style.background='rgba(139,92,246,0.05)';label.style.borderColor='rgba(139,92,246,0.2)';label.style.color='var(--accent2)';
    info.innerHTML='<i class="fa-solid fa-eye" style="color:var(--green)"></i> <strong style="color:var(--green)">Visible</strong> &mdash; Prompt will appear normally on the site.';
  }
}
function openDrawer(){document.getElementById('sideDrawer').classList.add('open');document.getElementById('drawerOverlay').classList.add('open');}
function closeDrawer(){document.getElementById('sideDrawer').classList.remove('open');document.getElementById('drawerOverlay').classList.remove('open');}
function countWords(text){
  return (text||'').trim().split(/\s+/).filter(Boolean).length;
}
function updateAboutWordCount(el){
  const countEl=document.getElementById('about-word-count');
  if(!countEl||!el)return;
  let words=(el.value||'').trim().split(/\s+/).filter(Boolean);
  if(words.length>200){
    el.value=words.slice(0,200).join(' ');
    words=words.slice(0,200);
  }
  countEl.textContent=words.length;
  countEl.style.color=words.length>=200?'var(--red)':'';
}
function handleTextareaPasteClean(e) {
  var text = (e.clipboardData || window.clipboardData).getData('text');
  if (!text) return;
  var cleaned = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').replace(/\n{3,}/g, '\n\n').trim();
  e.preventDefault();
  var start = this.selectionStart;
  var end = this.selectionEnd;
  var val = this.value;
  this.value = val.substring(0, start) + cleaned + val.substring(end);
  this.selectionStart = this.selectionEnd = start + cleaned.length;
  this.dispatchEvent(new Event('input'));
}
var pText = document.getElementById('prompt_text');
if (pText) pText.addEventListener('paste', handleTextareaPasteClean);

var activeStepIdxForPic = null;

function triggerStepPicUpload(stepIdx) {
  activeStepIdxForPic = stepIdx;
  var fileInput = document.getElementById('stepPicFileInput');
  if (fileInput) {
    fileInput.value = '';
    fileInput.click();
  }
}

function handleStepPicSelected(input) {
  if (!input.files || !input.files[0] || activeStepIdxForPic === null) return;
  var file = input.files[0];
  var row = document.querySelector(`.how-step-row[data-step-idx="${activeStepIdxForPic}"]`);
  var pointText = '';
  if (row) {
    var ta = row.querySelector('.how-step-input');
    if (ta) pointText = ta.value.trim();
  }

  var formData = new FormData();
  formData.append('action', 'upload');
  formData.append('image', file);
  formData.append('point_text', pointText);

  var slots = row ? row.querySelector('.how-step-slots') : null;
  var loadingBox = document.createElement('div');
  loadingBox.className = 'how-step-pic-box is-loading';
  loadingBox.style.cssText = 'position:relative;width:58px;height:58px;border-radius:10px;border:1.5px dashed #10b981;background:rgba(16,185,129,0.1);display:flex;align-items:center;justify-content:center;color:#10b981;font-size:0.8rem;flex-shrink:0;';
  loadingBox.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
  if (slots) slots.appendChild(loadingBox);

  fetch('ajax_step_pic.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (loadingBox) loadingBox.remove();
    if (data.success && data.image_path) {
      appendStepPicBox(activeStepIdxForPic, data.image_path);
    } else {
      alert(data.message || 'Image upload failed.');
    }
  })
  .catch(err => {
    if (loadingBox) loadingBox.remove();
    alert('Upload error. Please try again.');
  });
}

function appendStepPicBox(stepIdx, imagePath) {
  var row = document.querySelector(`.how-step-row[data-step-idx="${stepIdx}"]`);
  if (!row) return;
  var slots = row.querySelector('.how-step-slots');
  if (!slots) return;

  var currentBoxes = slots.querySelectorAll('.how-step-pic-box:not(.is-loading)');
  if (currentBoxes.length >= 5) {
    alert('Maximum 5 pictures allowed per step.');
    return;
  }

  var box = document.createElement('div');
  box.className = 'how-step-pic-box';
  box.style.cssText = 'position:relative;width:58px;height:58px;border-radius:10px;border:1.5px solid #38bdf8;background:#0f172a;overflow:hidden;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,0.3);';
  box.innerHTML = `
    <img src="${imagePath}" style="width:100%;height:100%;object-fit:cover;display:block;">
    <input type="hidden" name="how_step_images[${stepIdx}][]" value="${imagePath}">
    <button type="button" onclick="removeStepPic(this)" style="position:absolute;top:2px;right:2px;width:18px;height:18px;border-radius:50%;background:rgba(239,68,68,0.92);color:#fff;border:none;font-size:11px;line-height:1;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s ease;" title="Remove picture">&times;</button>
  `;
  slots.appendChild(box);
  updateStepPicButtons(row);
}

function removeStepPic(btn) {
  var box = btn.closest('.how-step-pic-box');
  if (!box) return;
  var row = box.closest('.how-step-row');
  box.remove();
  if (row) updateStepPicButtons(row);
}

function updateStepPicButtons(row) {
  var slots = row.querySelector('.how-step-slots');
  var addBtn = row.querySelector('.btn-add-step-pic');
  if (!slots || !addBtn) return;
  var count = slots.querySelectorAll('.how-step-pic-box:not(.is-loading)').length;
  if (count >= 5) {
    addBtn.style.display = 'none';
  } else {
    addBtn.style.display = 'inline-flex';
  }
}

// Previous Pics Modal
var currentModalStepIdx = null;

function openPreviousPicsModal(stepIdx) {
  currentModalStepIdx = stepIdx;
  var modal = document.getElementById('previousPicsModal');
  if (!modal) return;
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  var search = document.getElementById('prevPicSearchInput');
  if (search) search.value = '';
  loadPreviousPicsList();
}

function closePreviousPicsModal() {
  var modal = document.getElementById('previousPicsModal');
  if (modal) modal.style.display = 'none';
  document.body.style.overflow = '';
}

function loadPreviousPicsList(query = '') {
  var grid = document.getElementById('previousPicsGrid');
  if (!grid) return;
  grid.innerHTML = '<div style="padding:30px;text-align:center;color:var(--muted);"><i class="fa-solid fa-spinner fa-spin"></i> Loading previous pics...</div>';

  fetch('ajax_step_pic.php?action=list&q=' + encodeURIComponent(query))
  .then(res => res.json())
  .then(data => {
    if (data.success && data.pics) {
      renderPreviousPics(data.pics);
    } else {
      grid.innerHTML = '<div style="padding:30px;text-align:center;color:var(--muted);">Failed to load pictures.</div>';
    }
  })
  .catch(() => {
    grid.innerHTML = '<div style="padding:30px;text-align:center;color:var(--muted);">Error loading pictures.</div>';
  });
}

function renderPreviousPics(pics) {
  var grid = document.getElementById('previousPicsGrid');
  if (!grid) return;
  if (!pics || pics.length === 0) {
    grid.innerHTML = `
      <div style="padding:40px 20px;text-align:center;color:var(--muted);">
        <i class="fa-solid fa-images" style="font-size:2rem;margin-bottom:10px;opacity:0.4;"></i>
        <div style="font-weight:700;">No previous step pictures found.</div>
        <p style="font-size:0.8rem;margin:6px 0 0 0;">Upload sample pictures in any step, and they will automatically show up here for instant reuse!</p>
      </div>
    `;
    return;
  }

  var html = '';
  pics.forEach(p => {
    var pt = p.point_text ? p.point_text : '(No text recorded)';
    var escPt = pt.replace(/"/g, '&quot;');
    html += `
      <div class="prev-pic-card" style="display:flex;align-items:center;gap:12px;padding:12px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:12px;">
        <img src="${p.image_path}" style="width:62px;height:62px;border-radius:10px;object-fit:cover;border:1.5px solid rgba(255,255,255,0.1);flex-shrink:0;">
        <div style="flex:1;min-width:0;">
          <div style="font-size:0.84rem;font-weight:600;color:var(--text);margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;" title="${escPt}">${pt}</div>
          <div style="font-size:0.72rem;color:var(--muted);"><i class="fa-solid fa-repeat"></i> Used ${p.times_used} times</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
          <button type="button" onclick="selectPreviousPic('${p.image_path}', '${escPt}', true)" style="padding:6px 12px;background:#38bdf8;color:#0f172a;font-weight:700;font-size:0.75rem;border-radius:7px;border:none;cursor:pointer;white-space:nowrap;" title="Insert this pic and auto-fill point text">
            <i class="fa-solid fa-check-double"></i> Use Pic &amp; Text
          </button>
          <button type="button" onclick="selectPreviousPic('${p.image_path}', '', false)" style="padding:5px 12px;background:rgba(255,255,255,0.08);color:var(--text);font-weight:600;font-size:0.72rem;border-radius:7px;border:1px solid var(--border);cursor:pointer;white-space:nowrap;" title="Insert only this pic without changing text">
            Use Pic Only
          </button>
        </div>
      </div>
    `;
  });
  grid.innerHTML = html;
}

function selectPreviousPic(imagePath, pointText, fillText) {
  if (currentModalStepIdx === null) return;
  var row = document.querySelector(`.how-step-row[data-step-idx="${currentModalStepIdx}"]`);
  if (!row) return;

  if (fillText && pointText && pointText !== '(No text recorded)') {
    var ta = row.querySelector('.how-step-input');
    if (ta) {
      ta.value = pointText;
      ta.dispatchEvent(new Event('input'));
    }
  }

  appendStepPicBox(currentModalStepIdx, imagePath);
  closePreviousPicsModal();
}

function addHowStep(val = '', images = []) {
  const container = document.getElementById('how-to-use-container');
  if (!container) return;
  const count = container.querySelectorAll('.how-step-row').length;
  const idx = count;
  const row = document.createElement('div');
  row.className = 'how-step-row';
  row.setAttribute('data-step-idx', idx);
  row.style.cssText = 'background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:12px;padding:14px;display:flex;flex-direction:column;gap:10px;margin-bottom:12px;';
  row.innerHTML = `
    <div style="display:flex;align-items:flex-start;gap:10px;">
      <span class="how-step-badge" style="display:inline-flex;align-items:center;justify-content:center;min-width:68px;padding:9px 10px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:10px;font-size:0.75rem;font-weight:800;color:var(--text);flex-shrink:0;">Step ${idx + 1}</span>
      <textarea name="how_step_text[]" class="form-input how-step-input" rows="2" placeholder="e.g. Upload your reference image (Image 1 as boy and Image 2 as girl)..." style="flex:1;resize:vertical;">${val}</textarea>
      <button type="button" class="btn-remove-step" onclick="removeHowStep(this)" style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);color:#ef4444;border-radius:10px;width:38px;height:42px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:all .15s ease;" title="Remove this step"><i class="fa-solid fa-trash-can"></i></button>
    </div>
    <div class="how-step-pics-container" style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;padding:8px 12px;background:rgba(0,0,0,0.18);border-radius:10px;border:1px dashed rgba(255,255,255,0.12);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--muted);display:flex;align-items:center;gap:6px;">
        <i class="fa-solid fa-camera"></i> Sample Pics (Max 5):
      </div>
      <div class="how-step-slots" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;"></div>
      <button type="button" class="btn-add-step-pic" onclick="triggerStepPicUpload(${idx})" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.35);color:#10b981;font-size:0.75rem;font-weight:700;border-radius:8px;cursor:pointer;transition:all 0.15s ease;">
        <i class="fa-solid fa-plus"></i> Add Sample Pic
      </button>
      <button type="button" class="btn-reuse-step-pic" onclick="openPreviousPicsModal(${idx})" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:rgba(56,189,248,0.15);border:1px solid rgba(56,189,248,0.35);color:#38bdf8;font-size:0.75rem;font-weight:700;border-radius:8px;cursor:pointer;transition:all 0.15s ease;margin-left:auto;">
        <i class="fa-solid fa-clock-rotate-left"></i> Previous pics use karo
      </button>
    </div>
  `;
  container.appendChild(row);

  if (Array.isArray(images)) {
    images.forEach(img => {
      appendStepPicBox(idx, img);
    });
  }

  const ta = row.querySelector('textarea');
  if (ta && val === '') ta.focus();
}

function removeHowStep(btn) {
  const container = document.getElementById('how-to-use-container');
  if (!container) return;
  const row = btn.closest('.how-step-row');
  if (row) {
    row.remove();
    reindexHowSteps();
  }
}

function reindexHowSteps() {
  const container = document.getElementById('how-to-use-container');
  if (!container) return;
  const rows = container.querySelectorAll('.how-step-row');
  if (rows.length === 0) {
    addHowStep();
    return;
  }
  rows.forEach((r, idx) => {
    r.setAttribute('data-step-idx', idx);
    const badge = r.querySelector('.how-step-badge');
    if (badge) badge.textContent = `Step ${idx + 1}`;
    const addBtn = r.querySelector('.btn-add-step-pic');
    if (addBtn) addBtn.setAttribute('onclick', `triggerStepPicUpload(${idx})`);
    const reuseBtn = r.querySelector('.btn-reuse-step-pic');
    if (reuseBtn) reuseBtn.setAttribute('onclick', `openPreviousPicsModal(${idx})`);
    const inputs = r.querySelectorAll('input[type="hidden"]');
    inputs.forEach(inp => {
      inp.name = `how_step_images[${idx}][]`;
    });
  });
}

document.addEventListener('DOMContentLoaded', function() {
  if (document.querySelectorAll('.how-step-row').length === 0) {
    addHowStep();
  }
});
</script>

<!-- Hidden Step Pic File Input -->
<input type="file" id="stepPicFileInput" accept="image/png,image/jpeg,image/webp,image/gif" style="display:none" onchange="handleStepPicSelected(this)">

<!-- Previous Pics Modal -->
<div id="previousPicsModal" style="display:none;position:fixed;inset:0;z-index:999999;background:rgba(15,23,42,0.85);backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:20px;">
  <div style="position:relative;background:#1e293b;border:1px solid #334155;border-radius:16px;width:100%;max-width:680px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #334155;background:rgba(0,0,0,0.2);">
      <div style="font-weight:800;font-size:0.95rem;color:#f8fafc;display:flex;align-items:center;gap:8px;">
        <i class="fa-solid fa-clock-rotate-left" style="color:#38bdf8;"></i> Previous Step Pics Library
      </div>
      <button type="button" onclick="closePreviousPicsModal()" style="background:none;border:none;color:#94a3b8;font-size:1.4rem;cursor:pointer;line-height:1;" aria-label="Close">&times;</button>
    </div>
    
    <div style="padding:14px 20px;border-bottom:1px solid #334155;background:rgba(0,0,0,0.1);">
      <div style="position:relative;">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b;font-size:0.85rem;"></i>
        <input type="text" id="prevPicSearchInput" placeholder="Search by point text..." style="width:100%;padding:9px 12px 9px 36px;border-radius:10px;border:1px solid #334155;background:#0f172a;color:#fff;font-size:0.82rem;box-sizing:border-box;" oninput="loadPreviousPicsList(this.value)">
      </div>
    </div>

    <div id="previousPicsGrid" style="padding:16px 20px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;flex:1;max-height:55vh;"></div>
    
    <div style="padding:12px 20px;border-top:1px solid #334155;display:flex;justify-content:flex-end;background:rgba(0,0,0,0.2);">
      <button type="button" onclick="closePreviousPicsModal()" style="padding:8px 16px;border-radius:8px;background:rgba(255,255,255,0.08);border:1px solid #475569;color:#f8fafc;font-size:0.8rem;font-weight:600;cursor:pointer;">Cancel</button>
    </div>
  </div>
</div>
</html>




