<?php
session_start();
require_once "db.php";

// Admin only
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

$prompts = $pdo
    ->query(
        "SELECT id, slug, title, image_path, prompt_type, likes_count, is_trial FROM prompts ORDER BY created_at DESC",
    )
    ->fetchAll(PDO::FETCH_ASSOC);
$total = count($prompts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prompt Share Links — Arigato Admin</title>
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
.card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:20px;backdrop-filter:blur(8px)}
.srch-inp{width:100%;padding:10px 16px;background:rgba(15,13,30,0.8);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:var(--font);font-size:.85rem;outline:none;transition:all .2s;box-sizing:border-box;margin-bottom:14px}
.srch-inp:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(139,92,246,0.1)}
.srch-inp::placeholder{color:var(--muted)}
.dtable{width:100%;border-collapse:collapse;font-size:.78rem}
.dtable th{background:rgba(139,92,246,0.07);color:var(--accent2);font-weight:800;font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;padding:9px 13px;text-align:left;border-bottom:1px solid var(--border)}
.dtable td{padding:10px 13px;border-bottom:1px solid var(--border2);color:var(--muted);vertical-align:middle}
.dtable tr:last-child td{border-bottom:none}.dtable tr:hover td{background:rgba(139,92,246,0.03)}
.p-thumb{width:42px;height:42px;border-radius:9px;object-fit:cover;border:1px solid var(--border2)}
.type-badge{display:inline-flex;align-items:center;padding:2px 9px;border-radius:100px;font-size:.6rem;font-weight:900;border:1px solid;text-transform:uppercase}
.tb-scp{background:rgba(248,113,113,0.08);color:var(--red);border-color:rgba(248,113,113,0.22)}
.tb-urp{background:rgba(251,191,36,0.08);color:var(--yellow);border-color:rgba(251,191,36,0.22)}
.tb-ivp{background:rgba(34,211,238,0.06);color:var(--cyan);border-color:rgba(34,211,238,0.18)}
.tb-aup{background:rgba(96,165,250,0.06);color:#60a5fa;border-color:rgba(96,165,250,0.18)}
.tb-trial{background:rgba(74,222,128,0.08);color:var(--green);border-color:rgba(74,222,128,0.2)}
.copy-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:9px;font-size:.73rem;font-weight:800;border:1px solid rgba(139,92,246,0.25);background:rgba(139,92,246,0.07);color:var(--accent2);cursor:pointer;transition:all .2s;font-family:var(--font)}
.copy-btn:hover{background:rgba(139,92,246,0.15)}
.pagination{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:12px 0 4px;border-top:1px solid var(--border2);margin-top:10px;font-size:.75rem;font-weight:700;color:var(--muted)}
.pag-btns{display:flex;gap:5px;flex-wrap:wrap}
.pag-btn{padding:5px 11px;border-radius:8px;border:1px solid var(--border2);background:transparent;color:var(--muted);cursor:pointer;font-family:var(--font);font-size:.72rem;font-weight:700;transition:all .2s}
.pag-btn:hover{border-color:var(--accent);color:var(--accent2)}.pag-btn.active{background:rgba(139,92,246,0.15);color:var(--accent2);border-color:var(--border)}.pag-btn:disabled{opacity:.35;cursor:default}
/* MOBILE CARDS */
.mob-cards{display:none;flex-direction:column;gap:10px}
.mob-card{background:rgba(15,13,30,0.7);border:1px solid var(--border2);border-radius:13px;padding:13px;display:flex;align-items:center;gap:12px}
.mob-card-info{flex:1;min-width:0}
.mob-card-title{font-weight:800;font-size:.85rem;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.mob-card-meta{display:flex;align-items:center;gap:6px;margin-top:4px;flex-wrap:wrap}
.mob-nav{display:none;position:fixed;bottom:0;left:0;right:0;background:rgba(7,6,15,0.97);border-top:1px solid var(--border);z-index:500;padding:8px 0 max(8px,env(safe-area-inset-bottom));flex-direction:row;justify-content:space-around;align-items:center}
.mn-link{display:flex;flex-direction:column;align-items:center;gap:3px;font-size:.6rem;font-weight:700;color:var(--muted);text-decoration:none;padding:4px 8px;min-width:48px;transition:all .2s}
.mn-link:hover{color:var(--accent2)}.mn-link i{font-size:1.1rem}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:rgba(139,92,246,0.4);border-radius:10px}
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-admin{padding:10px;justify-content:center}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px;padding:20px 16px 80px}}
@media(max-width:600px){.sidebar{display:none}.main{margin-left:0;padding:14px 14px 80px}.mob-nav{display:flex}.desk-table{display:none}.mob-cards{display:flex}}
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
    <a href="prompt_links.php" class="sb-link active"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
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
    <div class="tb-title"><i class="fa-solid fa-link" style="color:var(--accent2);-webkit-text-fill-color:var(--accent2)"></i> Prompt Share Links</div>
    <span style="font-size:.75rem;background:rgba(139,92,246,0.1);border:1px solid var(--border);color:var(--accent2);border-radius:100px;padding:5px 14px;font-weight:800"><?= $total ?> Prompts</span>
    <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:9px;font-size:.75rem;font-weight:800;border:1px solid rgba(139,92,246,0.22);background:rgba(139,92,246,0.07);color:var(--accent2);text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
  </div>
  <p style="font-size:.8rem;color:var(--muted);margin-bottom:16px"><i class="fa-solid fa-circle-info" style="color:var(--accent2)"></i> Click Copy Link to get the shareable URL for any prompt.</p>

  <div class="card">
    <input type="text" id="pl-search" class="srch-inp" placeholder="Search by title or slug..." oninput="filterTable(this.value)">

    <!-- DESKTOP TABLE -->
    <div style="overflow-x:auto" class="desk-table">
    <table class="dtable" id="pl-table">
      <thead><tr><th>#</th><th>Cover</th><th>Title</th><th>Type</th><th>Likes</th><th>Copy Link</th></tr></thead>
      <tbody>
      <?php
      $type_badge_map=['secret'=>['cls'=>'tb-scp','lbl'=>'SCP'],'unreleased'=>['cls'=>'tb-urp','lbl'=>'URP'],'insta_viral'=>['cls'=>'tb-ivp','lbl'=>'IVP'],'already_uploaded'=>['cls'=>'tb-aup','lbl'=>'AUP']];
      foreach($prompts as $idx=>$p):
        $ptype=$p['prompt_type']??'secret';
        $binfo=$type_badge_map[$ptype]??$type_badge_map['secret'];
        $searchStr=strtolower(($p['slug']??'').' '.($p['title']??''));
      ?>
      <tr data-search="<?= htmlspecialchars($searchStr) ?>">
        <td style="color:var(--muted);font-size:.7rem;font-weight:700"><?= $idx+1 ?></td>
        <td><img loading="lazy" src="<?= htmlspecialchars($p['image_path']??'') ?>" class="p-thumb" alt=""></td>
        <td style="max-width:220px">
          <div style="font-weight:800;font-size:.83rem;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['title']??'') ?></div>
          <div style="font-size:.65rem;color:var(--muted);margin-top:2px;font-family:monospace"><?= htmlspecialchars($p['slug']??'') ?></div>
        </td>
        <td>
          <span class="type-badge <?= $binfo['cls'] ?>"><?= $binfo['lbl'] ?></span>
          <?php if(!empty($p['is_trial'])): ?><span class="type-badge tb-trial" style="margin-left:4px">TRIAL</span><?php endif; ?>
        </td>
        <td style="font-weight:800;color:var(--red)"><i class="fa-solid fa-heart" style="font-size:.65rem"></i> <?= (int)($p['likes_count']??0) ?></td>
        <td><button class="copy-btn" onclick="copyLink('<?= addslashes($p['slug']??'') ?>',<?= (int)$p['id'] ?>,this)"><i class="fa-solid fa-copy"></i> Copy Link</button></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <!-- MOBILE CARDS -->
    <div class="mob-cards" id="mob-cards">
    <?php foreach($prompts as $idx=>$p):
      $ptype=$p['prompt_type']??'secret';
      $binfo=$type_badge_map[$ptype]??$type_badge_map['secret'];
      $searchStr=strtolower(($p['slug']??'').' '.($p['title']??''));
    ?>
    <div class="mob-card" data-search="<?= htmlspecialchars($searchStr) ?>">
      <img loading="lazy" src="<?= htmlspecialchars($p['image_path']??'') ?>" style="width:46px;height:46px;border-radius:10px;object-fit:cover;border:1px solid var(--border2);flex-shrink:0" alt="">
      <div class="mob-card-info">
        <div class="mob-card-title"><?= htmlspecialchars($p['title']??'') ?></div>
        <div class="mob-card-meta">
          <span class="type-badge <?= $binfo['cls'] ?>"><?= $binfo['lbl'] ?></span>
          <?php if(!empty($p['is_trial'])): ?><span class="type-badge tb-trial">TRIAL</span><?php endif; ?>
          <span style="font-size:.65rem;color:var(--muted)"><i class="fa-solid fa-heart" style="color:var(--red)"></i> <?= (int)($p['likes_count']??0) ?></span>
        </div>
      </div>
      <button class="copy-btn" onclick="copyLink('<?= addslashes($p['slug']??'') ?>',<?= (int)$p['id'] ?>,this)" style="padding:8px 12px;flex-shrink:0"><i class="fa-solid fa-copy"></i></button>
    </div>
    <?php endforeach; ?>
    </div>

    <p id="pl-empty" style="display:none;text-align:center;color:var(--muted);padding:20px 0;font-size:.85rem"><i class="fa-solid fa-magnifying-glass"></i> No prompts match.</p>
    <div class="pagination">
      <div id="pl-info" style="color:var(--muted)"></div>
      <div class="pag-btns" id="pl-pagination"></div>
    </div>
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

// Pagination
const PER_PAGE=12;let currentPage=1;
function getAllRows(){
  const isMob=window.innerWidth<=600;
  return isMob?Array.from(document.querySelectorAll('#mob-cards .mob-card')):Array.from(document.querySelectorAll('#pl-table tbody tr'));
}
function getFilteredRows(){
  const q=(document.getElementById('pl-search').value||'').toLowerCase().trim();
  return getAllRows().filter(r=>!q||(r.dataset.search||'').includes(q));
}
function renderPage(page){
  currentPage=page;
  const rows=getFilteredRows();const total=rows.length;
  const pages=Math.max(1,Math.ceil(total/PER_PAGE));
  if(page>pages)page=pages;
  const start=(page-1)*PER_PAGE,end=start+PER_PAGE;
  getAllRows().forEach(r=>r.style.display='none');
  rows.forEach((r,i)=>r.style.display=(i>=start&&i<end)?'':'none');
  document.getElementById('pl-empty').style.display=total===0?'block':'none';
  document.getElementById('pl-info').textContent=total===0?'':`Showing ${Math.min(start+1,total)}–${Math.min(end,total)} of ${total}`;
  renderPagination(page,pages);
}
function renderPagination(page,pages){
  const c=document.getElementById('pl-pagination');
  if(pages<=1){c.innerHTML='';return}
  let h=`<button class="pag-btn" onclick="renderPage(${page-1})" ${page===1?'disabled':''}>Prev</button>`;
  for(let i=1;i<=pages;i++)h+=`<button class="pag-btn ${i===page?'active':''}" onclick="renderPage(${i})">${i}</button>`;
  h+=`<button class="pag-btn" onclick="renderPage(${page+1})" ${page===pages?'disabled':''}>Next</button>`;
  c.innerHTML=h;
}
function filterTable(q){renderPage(1)}
window.addEventListener('load',()=>renderPage(1));
window.addEventListener('resize',()=>renderPage(currentPage));

function copyLink(slug,id,btn){
  var link=slug?window.location.origin+'/prompts/'+slug:window.location.origin+'/prompt.php?id='+id;
  navigator.clipboard.writeText(link).then(function(){
    var orig=btn.innerHTML;
    btn.innerHTML='<i class="fa-solid fa-check"></i> Copied!';
    btn.style.color='var(--green)';btn.style.borderColor='rgba(74,222,128,0.3)';btn.style.background='rgba(74,222,128,0.07)';
    setTimeout(function(){btn.innerHTML=orig;btn.style.color='';btn.style.borderColor='';btn.style.background='';},1500);
  }).catch(function(){window.prompt('Copy link:',link)});
}
</script>
</html>

