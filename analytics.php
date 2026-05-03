<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }

// Stat cards
$total_prompts = $pdo->query("SELECT COUNT(*) FROM prompts")->fetchColumn();
$total_likes   = $pdo->query("SELECT SUM(likes_count) FROM prompts")->fetchColumn() ?: 0;
$total_users   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$weekly_p      = $pdo->query("SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
$monthly_p     = $pdo->query("SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
$weekly_u      = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
$monthly_u     = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
$most_liked    = $pdo->query("SELECT title,likes_count FROM prompts ORDER BY likes_count DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Bar chart: likes per prompt (top 10)
$top_prompts = $pdo->query("SELECT title, likes_count FROM prompts ORDER BY likes_count DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$bar_labels  = json_encode(array_column($top_prompts, 'title'));
$bar_data    = json_encode(array_column($top_prompts, 'likes_count'));

// Line chart: users joined per day (last 30 days)
$user_growth_raw = $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC")->fetchAll(PDO::FETCH_ASSOC);
$ug_labels = json_encode(array_column($user_growth_raw, 'd'));
$ug_data   = json_encode(array_column($user_growth_raw, 'c'));

// Line chart: prompts added per day (last 30 days)
$prompt_growth_raw = $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC")->fetchAll(PDO::FETCH_ASSOC);
$pg_labels = json_encode(array_column($prompt_growth_raw, 'd'));
$pg_data   = json_encode(array_column($prompt_growth_raw, 'c'));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analytics "” PromptVerse Admin</title>
<link rel="stylesheet" href="style.css?v=1777723415">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>
<style>
body{background:var(--bg-color)}
.an-wrap{max-width:1300px;margin:0 auto;padding:32px 36px 100px}
.an-title{font-size:2.2rem;font-weight:900;margin-bottom:6px}
.an-sub{color:#7D7887;font-weight:600;margin-bottom:32px;font-size:.95rem}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:18px;margin-bottom:40px}
.s-card{background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:20px;padding:22px 18px;text-align:center;box-shadow:var(--shadow-comic);transition:all .2s}
.s-card:hover{transform:translateY(-4px) rotate(-1deg);box-shadow:var(--shadow-comic-hover)}
.s-card.a1{background:var(--primary-color)}
.s-card.a2{background:var(--secondary-color)}
.s-card.a3{background:#d4eaff}
.s-card.a4{background:#ffe3f0}
.s-card.a5{background:#d9f5e5}
.s-card.a6{background:#fff3cd;grid-column:span 2}
.s-val{font-size:2.4rem;font-weight:900;color:var(--text-color);line-height:1;margin-bottom:6px}
.s-label{font-size:.78rem;font-weight:800;color:var(--text-color);text-transform:uppercase;letter-spacing:1px}
.s-sub{font-size:.73rem;color:#666;margin-top:4px;font-weight:600}
.charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-bottom:32px}
.chart-card{background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:22px;padding:26px;box-shadow:var(--shadow-comic)}
.chart-card h3{font-size:1.1rem;font-weight:900;margin-bottom:20px;padding-bottom:12px;border-bottom:2px dashed var(--border-color)}
.chart-full{grid-column:1/-1}
canvas{max-height:280px}
@media(max-width:900px){.charts-grid{grid-template-columns:1fr}.s-card.a6{grid-column:span 1}}
@media(max-width:600px){.an-wrap{padding:22px 18px 80px}.an-title{font-size:1.6rem}}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>
<header>
  <div class="logo-area" onclick="window.location.href='index.php'" style="cursor:pointer">
    <div class="logo-flipper">
      <div class="logo-front"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo" id="profile-logo"></div>
      <div class="logo-back"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt=""></div>
    </div>
    <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
  </div>
  <nav class="nav-links">
    <a href="index.php">HOME</a>
    <a href="dashboard.php">DASHBOARD</a>
    <a href="analytics.php" class="active"><i class="fa-solid fa-chart-simple"></i> ANALYTICS</a>
  </nav>
  <div class="header-right">
    <div class="header-divider"></div>
    <div style="display:flex; align-items:center; gap:8px;"><a href="profile.php" title="Edit Profile"><img src="<?= htmlspecialchars($_SESSION['profile_image'] ?? '') ?>" class="admin-avatar" alt="Admin" referrerpolicy="no-referrer" style="transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1) rotate(-5deg)'" onmouseout="this.style.transform=''"></a><a href="dashboard.php" style="color:var(--text-color); font-weight:800;">ADMIN</a></div>
    <a href="login.php?logout=1" class="logout"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> LOGOUT</a>
  </div>
</header>

<div class="an-wrap">
  <div class="an-title"><i class="fa-solid fa-chart-simple"></i> Analytics Dashboard</div>
  <div class="an-sub">Real-time data from your database — updated on every page load.</div>

  <!-- Stat Cards -->
  <div class="stat-grid">
    <div class="s-card a1"><div class="s-val"><?=$total_prompts?></div><div class="s-label">Total Prompts</div></div>
    <div class="s-card a2"><div class="s-val"><?=number_format($total_likes)?></div><div class="s-label">Total Likes</div></div>
    <div class="s-card a3"><div class="s-val"><?=$total_users?></div><div class="s-label">Total Users</div></div>
    <div class="s-card a4"><div class="s-val">+<?=$weekly_p?></div><div class="s-label">Prompts</div><div class="s-sub">This Week</div></div>
    <div class="s-card a5"><div class="s-val">+<?=$weekly_u?></div><div class="s-label">New Users</div><div class="s-sub">This Week</div></div>
    <?php if($most_liked):?>
    <div class="s-card a6">
      <div class="s-val" style="font-size:1.15rem;font-weight:900;"><i class="fa-solid fa-star"></i> <?=htmlspecialchars($most_liked['title'])?></div>
      <div class="s-label">Top Performing Prompt — <?=$most_liked['likes_count']?> <i class="fa-solid fa-heart"></i></div>
    </div>
    <?php endif;?>
    <div class="s-card" style="background:#ede9ff"><div class="s-val">+<?=$monthly_p?></div><div class="s-label">Prompts</div><div class="s-sub">This Month</div></div>
    <div class="s-card" style="background:#e8f9ef"><div class="s-val">+<?=$monthly_u?></div><div class="s-label">New Users</div><div class="s-sub">This Month</div></div>
  </div>

  <!-- Charts -->
  <div class="charts-grid">
    <div class="chart-card chart-full">
      <h3><i class="fa-solid fa-heart"></i> Likes per Prompt (Top 10)</h3>
      <canvas id="barChart"></canvas>
    </div>
    <div class="chart-card">
      <h3><i class="fa-solid fa-user"></i> User Growth (Last 30 Days)</h3>
      <canvas id="userLineChart"></canvas>
    </div>
    <div class="chart-card">
      <h3><i class="fa-solid fa-box"></i> Prompt Uploads (Last 30 Days)</h3>
      <canvas id="promptLineChart"></canvas>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const defaults = { font: { family: 'Outfit, sans-serif', weight: '700' } };
  Chart.defaults.font.family = 'Outfit, sans-serif';
  Chart.defaults.font.weight = '700';
  Chart.defaults.color = '#2D2A35';

  // Bar "“ Likes per prompt
  new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
      labels: <?=$bar_labels?>,
      datasets: [{ label: 'Likes', data: <?=$bar_data?>,
        backgroundColor: ['#E6D7FF','#FFF1B8','#d4eaff','#ffe3f0','#d9f5e5','#C6ADFA','#FFE066','#b3d9ff','#ffb3cc','#a3e8c2'],
        borderColor: '#2D2A35', borderWidth: 2, borderRadius: 10 }]
    },
    options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, ticks:{stepSize:1}, grid:{color:'#EAE3F2'} }, x:{ grid:{display:false}, ticks:{maxRotation:30} } } }
  });

  // Line "“ User Growth
  new Chart(document.getElementById('userLineChart'), {
    type: 'line',
    data: {
      labels: <?=$ug_labels?>,
      datasets: [{ label:'New Users', data:<?=$ug_data?>,
        borderColor:'#C6ADFA', backgroundColor:'rgba(198,173,250,.18)', borderWidth:3,
        pointBackgroundColor:'#C6ADFA', pointRadius:5, pointHoverRadius:7, fill:true, tension:.4 }]
    },
    options: { responsive:true, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'#EAE3F2'}}, x:{grid:{display:false}} } }
  });

  // Line "“ Prompt Growth
  new Chart(document.getElementById('promptLineChart'), {
    type: 'line',
    data: {
      labels: <?=$pg_labels?>,
      datasets: [{ label:'Prompts Uploaded', data:<?=$pg_data?>,
        borderColor:'#FFE066', backgroundColor:'rgba(255,224,102,.2)', borderWidth:3,
        pointBackgroundColor:'#FFE066', pointRadius:5, pointHoverRadius:7, fill:true, tension:.4 }]
    },
    options: { responsive:true, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'#EAE3F2'}}, x:{grid:{display:false}} } }
  });
});
</script>
</body></html>








