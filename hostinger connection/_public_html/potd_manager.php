<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

// Handle custom POTD form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_custom_potd"])) {
    $c_title = trim($_POST["custom_title"] ?? "");
    $c_text  = trim($_POST["custom_text"] ?? "");
    $c_img   = trim($_POST["custom_image"] ?? "");
    if ($c_title && $c_text) {
        $stmt = $pdo->prepare("INSERT INTO potd_custom (title, prompt_text, image_url) VALUES (?, ?, ?)");
        $stmt->execute([$c_title, $c_text, $c_img]);
        $_SESSION["potd_msg"] = "Custom POTD added!";
    } else {
        $_SESSION["potd_err"] = "Title and Prompt Text are required.";
    }
    header("Location: potd_manager.php");
    exit();
}

// Fetch existing prompts (sorted by likes)
$prompts = $pdo->query("SELECT id, title, image_path, prompt_type, likes_count, is_featured FROM prompts ORDER BY likes_count DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch custom POTD entries
$customs = $pdo->query("SELECT * FROM potd_custom ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Flash messages
$msg = $_SESSION["potd_msg"] ?? "";
$err = $_SESSION["potd_err"] ?? "";
unset($_SESSION["potd_msg"], $_SESSION["potd_err"]);

// Find which one is currently active
$active_existing_id = null;
$active_custom_id = null;
foreach ($prompts as $p) { if ($p["is_featured"]) $active_existing_id = (int)$p["id"]; }
foreach ($customs as $c) { if ($c["is_active"]) $active_custom_id = (int)$c["id"]; }

$type_map = [
    "secret"           => ["icon"=>"fa-solid fa-lock",           "label"=>"Secret Code",      "bg"=>"#ffe3e3", "color"=>"#d03030"],
    "unreleased"       => ["icon"=>"fa-solid fa-star",           "label"=>"Unreleased",        "bg"=>"#fff4cc", "color"=>"#7a5800"],
    "insta_viral"      => ["icon"=>"fa-brands fa-instagram",     "label"=>"Insta Viral",       "bg"=>"#e3f7ff", "color"=>"#004f7a"],
    "already_uploaded" => ["icon"=>"fa-solid fa-clock-rotate-left","label"=>"Already Uploaded","bg"=>"#e6f2ff", "color"=>"#00509e"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POTD Manager — Arigato Admin</title>
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
.card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:18px;backdrop-filter:blur(8px);transition:border-color .3s}
.card:hover{border-color:rgba(139,92,246,0.3)}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border2);gap:10px;flex-wrap:wrap}
.card-title{font-size:.88rem;font-weight:900;display:flex;align-items:center;gap:8px;color:var(--text)}
.card-title i{color:var(--accent2)}
.srch-inp{width:100%;padding:10px 16px;background:rgba(15,13,30,0.8);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:var(--font);font-size:.85rem;outline:none;transition:all .2s;box-sizing:border-box;margin-bottom:14px}
.srch-inp:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(139,92,246,0.1)}
.srch-inp::placeholder{color:var(--muted)}
.dtable{width:100%;border-collapse:collapse;font-size:.78rem}
.dtable th{background:rgba(139,92,246,0.07);color:var(--accent2);font-weight:800;font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;padding:9px 13px;text-align:left;border-bottom:1px solid var(--border)}
.dtable td{padding:10px 13px;border-bottom:1px solid var(--border2);color:var(--muted);vertical-align:middle}
.dtable tr:last-child td{border-bottom:none}.dtable tr:hover td{background:rgba(139,92,246,0.03)}
.dtable tr.row-active td{background:rgba(74,222,128,0.07);box-shadow:inset 0 0 30px rgba(74,222,128,0.05)}
.dtable tr.row-active{outline:1px solid rgba(74,222,128,0.18);outline-offset:-1px;border-radius:8px;filter:drop-shadow(0 0 6px rgba(74,222,128,0.12))}
.p-thumb{width:42px;height:42px;border-radius:9px;object-fit:cover;border:1px solid var(--border2)}
.type-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 9px;border-radius:100px;font-size:.6rem;font-weight:900;border:1px solid;text-transform:uppercase}
.tb-scp{background:rgba(248,113,113,0.08);color:var(--red);border-color:rgba(248,113,113,0.22)}
.tb-urp{background:rgba(251,191,36,0.08);color:var(--yellow);border-color:rgba(251,191,36,0.22)}
.tb-ivp{background:rgba(34,211,238,0.06);color:var(--cyan);border-color:rgba(34,211,238,0.18)}
.tb-aup{background:rgba(96,165,250,0.06);color:#60a5fa;border-color:rgba(96,165,250,0.18)}
.potd-toggle{width:40px;height:22px;border-radius:100px;border:1px solid;background:rgba(255,255,255,0.04);cursor:pointer;position:relative;transition:all .3s;outline:none;appearance:none;-webkit-appearance:none}
.potd-toggle:checked{background:var(--yellow);border-color:rgba(251,191,36,0.5);box-shadow:0 0 10px rgba(251,191,36,0.3)}
.potd-toggle::after{content:'';position:absolute;top:3px;left:3px;width:14px;height:14px;border-radius:50%;background:#fff;transition:left .2s}
.potd-toggle:checked::after{left:21px}
.active-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:.62rem;font-weight:900;background:rgba(251,191,36,0.1);color:var(--yellow);border:1px solid rgba(251,191,36,0.25)}
.del-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:8px;font-size:.7rem;font-weight:800;border:1px solid rgba(248,113,113,0.22);background:rgba(248,113,113,0.07);color:var(--red);cursor:pointer;transition:all .2s;font-family:var(--font)}
.del-btn:hover{background:rgba(248,113,113,0.14)}
/* FLASH */
.flash-ok{background:rgba(74,222,128,0.07);border:1px solid rgba(74,222,128,0.22);color:var(--green);padding:11px 16px;border-radius:12px;font-size:.83rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.flash-err{background:rgba(248,113,113,0.07);border:1px solid rgba(248,113,113,0.22);color:var(--red);padding:11px 16px;border-radius:12px;font-size:.83rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
/* FORM */
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:.7rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:7px}
.form-input{width:100%;padding:10px 15px;background:rgba(15,13,30,0.8);border:1px solid var(--border);border-radius:11px;color:var(--text);font-family:var(--font);font-size:.85rem;outline:none;transition:all .2s;box-sizing:border-box}
.form-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(139,92,246,0.1)}
.form-input::placeholder{color:var(--muted)}
textarea.form-input{resize:vertical;min-height:80px}
.btn-submit{width:100%;padding:12px;background:linear-gradient(135deg,rgba(139,92,246,0.8),rgba(192,132,252,0.6));border:1px solid rgba(139,92,246,0.4);border-radius:12px;color:#fff;font-weight:900;font-size:.88rem;cursor:pointer;font-family:var(--font);transition:all .2s}
.btn-submit:hover{background:linear-gradient(135deg,rgba(139,92,246,0.95),rgba(192,132,252,0.75));box-shadow:0 4px 20px rgba(139,92,246,0.3)}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:rgba(139,92,246,0.4);border-radius:10px}
.mob-nav{display:none;position:fixed;bottom:0;left:0;right:0;background:rgba(7,6,15,0.97);border-top:1px solid var(--border);z-index:500;padding:8px 0 max(8px,env(safe-area-inset-bottom));flex-direction:row;justify-content:space-around;align-items:center}
.mn-link{display:flex;flex-direction:column;align-items:center;gap:3px;font-size:.6rem;font-weight:700;color:var(--muted);text-decoration:none;padding:4px 8px;min-width:48px;transition:all .2s}
.mn-link:hover{color:var(--accent2)}.mn-link i{font-size:1.1rem}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-admin{padding:10px;justify-content:center}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px;padding:20px 16px 80px}}
@media(max-width:600px){.sidebar{display:none}.main{margin-left:0;padding:14px 14px 80px}.mob-nav{display:flex}}
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
    <a href="manage_prompts.php" class="sb-link"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
    <a href="prompt_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="potd_manager.php" class="sb-link active"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
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
    <div class="tb-title"><i class="fa-solid fa-sun" style="color:var(--yellow);-webkit-text-fill-color:var(--yellow)"></i> POTD Manager</div>
    <span style="font-size:.75rem;background:rgba(251,191,36,0.1);border:1px solid rgba(251,191,36,0.25);color:var(--yellow);border-radius:100px;padding:5px 14px;font-weight:800"><i class="fa-solid fa-star"></i> Prompt of the Day</span>
    <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:9px;font-size:.75rem;font-weight:800;border:1px solid rgba(139,92,246,0.22);background:rgba(139,92,246,0.07);color:var(--accent2);text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
  </div>

  <?php if($msg): ?><div class="flash-ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="flash-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- EXISTING PROMPTS -->
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-wand-magic-sparkles"></i> Set Prompt as POTD</div>
      <?php if($active_existing_id): ?><div class="active-badge"><i class="fa-solid fa-star"></i> Active: Prompt #<?= $active_existing_id ?></div><?php endif; ?>
    </div>
    <input type="text" id="pm-search" class="srch-inp" placeholder="Search by prompt title..." oninput="filterPotdTable(this.value)">
    <div style="overflow-x:auto">
    <table class="dtable" id="pm-table-existing">
      <thead><tr><th>Cover</th><th>Title</th><th>Type</th><th><i class="fa-solid fa-star"></i> Likes</th><th>POTD Toggle</th></tr></thead>
      <tbody>
      <?php foreach($prompts as $p):
        $isActive=(int)$p['id']===$active_existing_id;
        $ptype=$p['prompt_type']??'secret';
        $tm=$type_map[$ptype]??$type_map['secret'];
        $badgeClsMap=['secret'=>'tb-scp','unreleased'=>'tb-urp','insta_viral'=>'tb-ivp','already_uploaded'=>'tb-aup'];
        $badgeCls=$badgeClsMap[$ptype]??'tb-scp';
      ?>
      <tr id="row-existing-<?= (int)$p['id'] ?>" data-search="<?= htmlspecialchars(strtolower($p['title']??'')) ?>" class="<?= $isActive?'row-active':'' ?>">
        <td><img loading="lazy" src="<?= htmlspecialchars($p['image_path']??'') ?>" class="p-thumb" alt=""></td>
        <td style="font-weight:700;color:var(--text);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['title']??'') ?></td>
        <td><span class="type-badge <?= $badgeCls ?>"><i class="<?= $tm['icon'] ?>"></i> <?= $tm['label'] ?></span></td>
        <td style="font-weight:800;color:var(--red)"><?= (int)$p['likes_count'] ?></td>
        <td><input type="checkbox" class="potd-toggle" <?= $isActive?'checked':'' ?> onchange="togglePotd('existing',<?= (int)$p['id'] ?>,this)" title="Set as POTD"></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <p id="pm-empty-existing" style="display:none;text-align:center;color:var(--muted);padding:16px 0;font-size:.85rem"><i class="fa-solid fa-magnifying-glass"></i> No prompts match.</p>
  </div>

  <!-- CUSTOM POTD -->
  <?php if(!empty($customs)): ?>
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-pen-nib"></i> Custom POTD Entries</div>
      <?php if($active_custom_id): ?><div class="active-badge"><i class="fa-solid fa-star"></i> Active Custom #<?= $active_custom_id ?></div><?php endif; ?>
    </div>
    <div style="overflow-x:auto">
    <table class="dtable" id="pm-table-custom">
      <thead><tr><th>Image</th><th>Title</th><th>Source</th><th>Delete</th><th>POTD Toggle</th></tr></thead>
      <tbody>
      <?php foreach($customs as $c):
        $cActive=(int)$c['id']===$active_custom_id;
      ?>
      <tr id="row-custom-<?= (int)$c['id'] ?>" data-search="<?= htmlspecialchars(strtolower($c['title']??'')) ?>" class="<?= $cActive?'row-active':'' ?>" style="transition:opacity .3s">
        <td><?php if(!empty($c['image_url'])): ?><img loading="lazy" src="<?= htmlspecialchars($c['image_url']) ?>" class="p-thumb" alt=""><?php else: ?><div style="width:42px;height:42px;border-radius:9px;background:rgba(139,92,246,0.08);border:1px solid var(--border2);display:flex;align-items:center;justify-content:center"><i class="fa-solid fa-image" style="color:var(--muted)"></i></div><?php endif; ?></td>
        <td style="font-weight:700;color:var(--text)"><?= htmlspecialchars($c['title']??'') ?></td>
        <td><span style="font-size:.68rem;color:var(--muted);font-weight:700"><i class="fa-solid fa-pen-nib"></i> Custom</span></td>
        <td><button class="del-btn" onclick="deleteCustomPotd(<?= (int)$c['id'] ?>,this)"><i class="fa-solid fa-trash"></i> Delete</button></td>
        <td><input type="checkbox" class="potd-toggle" <?= $cActive?'checked':'' ?> onchange="togglePotd('custom',<?= (int)$c['id'] ?>,this)"></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- ADD CUSTOM POTD FORM -->
  <div class="card">
    <div class="card-head"><div class="card-title"><i class="fa-solid fa-plus"></i> Add Custom POTD</div></div>
    <form method="POST" action="potd_manager.php">
      <input type="hidden" name="add_custom_potd" value="1">
      <div class="form-group">
        <label class="form-label">Title <span style="color:var(--red)">*</span></label>
        <input type="text" name="custom_title" class="form-input" placeholder="e.g. Today's Special Prompt" required>
      </div>
      <div class="form-group">
        <label class="form-label">Prompt Text <span style="color:var(--red)">*</span></label>
        <textarea name="custom_text" class="form-input" placeholder="Enter the full prompt text here..." required></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Image URL <span style="color:var(--muted)">(optional)</span></label>
        <input type="text" name="custom_image" class="form-input" placeholder="https://example.com/image.jpg">
      </div>
      <button type="submit" class="btn-submit"><i class="fa-solid fa-plus"></i> Add Custom POTD</button>
    </form>
  </div>
</main>

<nav class="mob-nav">
  <a href="dashboard.php" class="mn-link"><i class="fa-solid fa-gauge-high"></i><span>Home</span></a>
  <a href="manage_prompts.php" class="mn-link"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Prompts</span></a>
  <a href="user_management.php" class="mn-link"><i class="fa-solid fa-users"></i><span>Users</span></a>
  <a href="analytics.php" class="mn-link"><i class="fa-solid fa-chart-line"></i><span>Stats</span></a>
  <a href="upload_prompt.php" class="mn-link" style="color:var(--accent2)"><i class="fa-solid fa-plus"></i><span>Upload</span></a>
</nav>

<script>
window.addEventListener('scroll',()=>{const h=document.documentElement;document.getElementById('sp').style.width=(h.scrollTop/(h.scrollHeight-h.clientHeight)*100)+'%';},{passive:true});
(function(){const c=document.getElementById('pc');if(!c)return;const ctx=c.getContext('2d');let W,H,pts=[];function rs(){W=c.width=window.innerWidth;H=c.height=window.innerHeight}rs();window.addEventListener('resize',rs);class P{constructor(){this.reset()}reset(){this.x=Math.random()*W;this.y=Math.random()*H;this.vx=(Math.random()-.5)*.3;this.vy=(Math.random()-.5)*.3;this.r=Math.random()*1.2+.3;this.a=Math.random()*.35+.1;const cols=['139,92,246','244,114,182','34,211,238'];this.col=cols[Math.floor(Math.random()*cols.length)]}update(){this.x+=this.vx;this.y+=this.vy;if(this.x<0||this.x>W||this.y<0||this.y>H)this.reset()}draw(){ctx.beginPath();ctx.arc(this.x,this.y,this.r,0,Math.PI*2);ctx.fillStyle=`rgba(${this.col},${this.a})`;ctx.fill()}}for(let i=0;i<50;i++)pts.push(new P());function loop(){ctx.clearRect(0,0,W,H);pts.forEach(p=>{p.update();p.draw()});requestAnimationFrame(loop)}loop();})();

function togglePotd(type,id,checkbox){
  const active=checkbox.checked?1:0;
  fetch('potd_toggle.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`type=${type}&id=${id}&active=${active}`})
  .then(r=>r.json()).then(d=>{
    if(d.success){
      // Deactivate all rows
      document.querySelectorAll('#pm-table-existing tbody tr, #pm-table-custom tbody tr').forEach(r=>{
        r.classList.remove('row-active');
        const cb=r.querySelector('.potd-toggle');if(cb)cb.checked=false;
      });
      // Activate toggled row
      if(active){const row=document.getElementById('row-'+type+'-'+id);if(row){row.classList.add('row-active');checkbox.checked=true}}
    } else {checkbox.checked=!checkbox.checked;alert('Could not update POTD. Please try again.')}
  }).catch(()=>{checkbox.checked=!checkbox.checked;alert('Network error.')});
}

function deleteCustomPotd(id,btn){
  if(!confirm('Delete this custom POTD entry?'))return;
  fetch('potd_delete_custom.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id})
  .then(r=>r.json()).then(d=>{
    if(d.success){const row=document.getElementById('row-custom-'+id);if(row){row.style.opacity='0';setTimeout(()=>row.remove(),300)}}
    else alert('Could not delete. Please try again.');
  }).catch(()=>alert('Network error.'));
}

function filterPotdTable(query){
  const q=query.toLowerCase();
  let vis1=0,vis2=0;
  document.querySelectorAll('#pm-table-existing tbody tr').forEach(r=>{const m=!q||(r.dataset.search||'').includes(q);r.style.display=m?'':'none';if(m)vis1++;});
  document.querySelectorAll('#pm-table-custom tbody tr').forEach(r=>{const m=!q||(r.dataset.search||'').includes(q);r.style.display=m?'':'none';if(m)vis2++;});
  const e=document.getElementById('pm-empty-existing');if(e)e.style.display=vis1===0?'block':'none';
}
</script>
</html>

