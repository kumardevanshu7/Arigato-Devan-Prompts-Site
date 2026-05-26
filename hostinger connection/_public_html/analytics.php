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

// ── Core stats ──
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

// ── Chart data ──
$top_prompts    = sqAll($pdo, "SELECT title, likes_count FROM prompts ORDER BY likes_count DESC LIMIT 10");
$top_unlocked   = sqAll($pdo, "SELECT p.title, COUNT(u.id) as c FROM unlocked_prompts u JOIN prompts p ON p.id=u.prompt_id GROUP BY p.id,p.title ORDER BY c DESC LIMIT 10");
$type_breakdown = sqAll($pdo, "SELECT prompt_type, COUNT(*) as cnt FROM prompts GROUP BY prompt_type ORDER BY cnt DESC");

// ── 30-day trends ──
$ug  = fill30($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC");
$pg  = fill30($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC");
$spd = fill30($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM saved_prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC");
$upd = fill30($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM unlocked_prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC");

// ── Blog reads ──
$top_blogs = sqAll($pdo, "SELECT title, COALESCE(view_count,0) as views FROM blogs WHERE is_published=1 ORDER BY views DESC LIMIT 10");

// ── New users by hour (IST, UTC+5:30) ──
$ubh_raw = sqAll($pdo, "SELECT HOUR(CONVERT_TZ(created_at,'+00:00','+05:30')) as h, COUNT(*) as c FROM users GROUP BY h ORDER BY h ASC");
$hmap = array_fill(0, 24, 0);
foreach ($ubh_raw as $r) $hmap[(int)$r['h']] = (int)$r['c'];

// ── Retention (requires last_active column) ──
$coh1   = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 1 DAY)");
$r1cnt  = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 1 DAY) AND last_active > DATE_ADD(created_at,INTERVAL 1 DAY)");
$coh7   = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 7 DAY)");
$r7cnt  = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 7 DAY) AND last_active > DATE_ADD(created_at,INTERVAL 7 DAY)");
$coh30  = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 30 DAY)");
$r30cnt = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 30 DAY) AND last_active > DATE_ADD(created_at,INTERVAL 30 DAY)");
$ret_d1  = $coh1  > 0 ? round($r1cnt*100/$coh1,1)   : 0;
$ret_d7  = $coh7  > 0 ? round($r7cnt*100/$coh7,1)   : 0;
$ret_d30 = $coh30 > 0 ? round($r30cnt*100/$coh30,1) : 0;

// ── New vs Returning (last 7 days) ──
$new_7    = (int)$weekly_u;
$return_7 = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(),INTERVAL 7 DAY) AND last_active >= DATE_SUB(NOW(),INTERVAL 7 DAY)");

// ── Top saved prompts ──
$top_saved = sqAll($pdo, "SELECT p.title, COUNT(sp.id) as c FROM saved_prompts sp JOIN prompts p ON p.id=sp.prompt_id GROUP BY p.id,p.title ORDER BY c DESC LIMIT 10");

// ── Unlock-to-view ratio ──
$unlock_view = sqAll($pdo, "SELECT p.title, COUNT(up.id) as unlocks, COALESCE(p.view_count,0) as views FROM unlocked_prompts up JOIN prompts p ON p.id=up.prompt_id GROUP BY p.id,p.title ORDER BY unlocks DESC LIMIT 10");

// ── Prompt age vs performance ──
$age_perf = sqAll($pdo, "SELECT p.title, DATEDIFF(NOW(),p.created_at) as age, COUNT(up.id) as unlocks, p.likes_count FROM prompts p LEFT JOIN unlocked_prompts up ON p.id=up.prompt_id GROUP BY p.id,p.title,p.created_at,p.likes_count ORDER BY unlocks DESC LIMIT 12");

// ── Power users (5+ unlocks) ──
$power_users = sqAll($pdo, "SELECT u.username, u.email, COUNT(up.id) as cnt FROM users u JOIN unlocked_prompts up ON u.id=up.user_id GROUP BY u.id,u.username,u.email HAVING cnt >= 5 ORDER BY cnt DESC LIMIT 15");

// ── Churn risk (active 8–30 days ago, not in last 7 days) ──
$churn_users = sqAll($pdo, "SELECT username, email, last_active FROM users WHERE last_active >= DATE_SUB(NOW(),INTERVAL 30 DAY) AND last_active < DATE_SUB(NOW(),INTERVAL 7 DAY) ORDER BY last_active ASC LIMIT 10");

// ── Dead prompts (0 unlocks in last 30 days) ──
$dead_prompts = sqAll($pdo, "SELECT p.title, p.created_at, p.likes_count FROM prompts p WHERE p.id NOT IN (SELECT DISTINCT prompt_id FROM unlocked_prompts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)) ORDER BY p.likes_count DESC LIMIT 10");

// ── Spike days (50+ signups) ──
$spike_days = sqAll($pdo, "SELECT DATE(CONVERT_TZ(created_at,'+00:00','+05:30')) as d, COUNT(*) as cnt FROM users GROUP BY d HAVING cnt >= 50 ORDER BY d DESC LIMIT 5");

// ── Milestones ──
$ms_all     = [50, 100, 150, 200, 300, 500, 750, 1000];
$next_ms    = null;
foreach ($ms_all as $m) { if ((int)$total_users < $m) { $next_ms = $m; break; } }
$reached_ms = array_values(array_filter($ms_all, fn($m) => (int)$total_users >= $m));

// ── JSON for charts ──
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
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analytics &mdash; Arigato Devan Prompts Admin</title>
<link rel="stylesheet" href="style.css?v=2026052201">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>
<style>
body{background:var(--bg-color)}
.an-wrap{max-width:1300px;margin:0 auto;padding:32px 36px 100px}
.an-title{font-size:2.2rem;font-weight:900;margin-bottom:6px}
.an-sub{color:#7D7887;font-weight:600;margin-bottom:32px;font-size:.95rem}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:14px;margin-bottom:36px}
.s-card{background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:20px;padding:18px 14px;text-align:center;box-shadow:var(--shadow-comic);transition:all .2s}
.s-card:hover{transform:translateY(-4px) rotate(-1deg);box-shadow:var(--shadow-comic-hover)}
.s-val{font-size:2rem;font-weight:900;color:var(--text-color);line-height:1;margin-bottom:6px}
.s-label{font-size:.72rem;font-weight:800;color:var(--text-color);text-transform:uppercase;letter-spacing:1px}
.s-sub{font-size:.7rem;color:#666;margin-top:4px;font-weight:600}
.sec-hdr{font-size:1.15rem;font-weight:900;margin:32px 0 14px;display:flex;align-items:center;gap:9px;border-bottom:2px solid var(--border-color);padding-bottom:10px}
.alert-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin-bottom:28px}
.alert-box{border-radius:18px;padding:16px 18px;border:var(--border-width) solid var(--text-color);box-shadow:var(--shadow-comic)}
.alert-box h4{font-size:.88rem;font-weight:900;margin:0 0 10px;display:flex;align-items:center;gap:7px}
.alert-box li,.alert-box p{font-size:.81rem;font-weight:600;color:#555;line-height:1.55;margin:0}
.alert-box ul{margin:0;padding-left:18px}
.alert-spike{background:#fff3cd}.alert-dead{background:#ffe3e3}.alert-churn{background:#f0eeff}.alert-ms{background:#d9f5e5}
.ms-pill{display:inline-flex;align-items:center;gap:4px;background:#2ecc71;color:#fff;border:2px solid #1a8a4a;border-radius:16px;padding:3px 10px;font-size:.69rem;font-weight:900;margin:2px}
.ms-next{background:#fff;border:2px dashed #999;color:#555;border-radius:16px;padding:3px 10px;font-size:.69rem;font-weight:900;margin:2px;display:inline-flex;align-items:center;gap:4px}
.charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:24px}
.chart-card{background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:22px;padding:22px;box-shadow:var(--shadow-comic)}
.chart-card h3{font-size:.95rem;font-weight:900;margin-bottom:16px;padding-bottom:10px;border-bottom:2px dashed var(--border-color);display:flex;align-items:center;gap:7px}
.chart-full{grid-column:1/-1}
canvas{max-height:260px}
.ret-wrap{display:flex;flex-direction:column;gap:18px;padding:6px 0}
.ret-row{display:flex;flex-direction:column;gap:6px}
.ret-label{display:flex;justify-content:space-between;font-size:.82rem;font-weight:800}
.ret-track{height:16px;background:#f0eeff;border-radius:20px;overflow:hidden;border:1.5px solid var(--border-color)}
.ret-fill{height:100%;border-radius:20px}
.tables-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:24px}
.tbl-card{background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:22px;overflow:hidden;box-shadow:var(--shadow-comic)}
.tbl-card h3{font-size:.92rem;font-weight:900;padding:14px 18px;border-bottom:2px dashed var(--border-color);display:flex;align-items:center;gap:7px;margin:0}
.tbl-card table{width:100%;border-collapse:collapse}
.tbl-card th{padding:9px 14px;font-size:.66rem;font-weight:900;text-transform:uppercase;letter-spacing:.5px;text-align:left;background:var(--bg-color);border-bottom:2px solid var(--border-color)}
.tbl-card td{padding:8px 14px;font-size:.81rem;font-weight:600;border-bottom:1px solid var(--border-color)}
.tbl-card tr:last-child td{border-bottom:none}
.tbl-card tr:hover td{background:var(--bg-color)}
.tbl-empty{text-align:center;padding:22px;color:#aaa;font-weight:600;font-size:.83rem}
@media(max-width:900px){.charts-grid,.tables-grid,.alert-row{grid-template-columns:1fr}}
@media(max-width:600px){.an-wrap{padding:22px 14px 80px}.an-title{font-size:1.6rem}.stat-grid{grid-template-columns:repeat(2,1fr)}}
</style>
    <link rel='preload' href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' as='style' onload='this.onload=null;this.rel="stylesheet"'>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <?php include_once "gtag.php"; ?>
</head>
<body>
<header>
  <div class="logo-area"  style="cursor:pointer">
    <div class="logo-flipper">
      <div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo" id="profile-logo"></div>
      <div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div>
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
    <div style="display:flex; align-items:center; gap:8px;"><a href="profile.php" title="Edit Profile"><img loading="lazy" src="<?= htmlspecialchars(
        $_SESSION["profile_image"] ?? "",
    ) ?>" class="admin-avatar" alt="Admin" referrerpolicy="no-referrer" style="transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1) rotate(-5deg)'" onmouseout="this.style.transform=''"></a><a href="dashboard.php" style="color:var(--text-color); font-weight:800;">ADMIN</a></div>
    <a href="login.php?logout=1" class="logout"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> LOGOUT</a>
  </div>
</header>

<div class="an-wrap">
  <div class="an-title"><i class="fa-solid fa-chart-simple"></i> Analytics Dashboard</div>
  <div class="an-sub">Real-time data &mdash; updated on every page load.</div>

  <!-- ── STAT GRID ── -->
  <div class="stat-grid">
    <div class="s-card" style="background:var(--primary-color)"><div class="s-val"><?= $total_prompts ?></div><div class="s-label">Prompts</div></div>
    <div class="s-card" style="background:var(--secondary-color)"><div class="s-val"><?= number_format($total_likes) ?></div><div class="s-label">Likes</div></div>
    <div class="s-card" style="background:#d4eaff"><div class="s-val"><?= $total_users ?></div><div class="s-label">Users</div></div>
    <div class="s-card" style="background:#ffd6a5"><div class="s-val"><?= number_format($total_unlocks) ?></div><div class="s-label">Unlocks</div></div>
    <div class="s-card" style="background:#ffe3f0"><div class="s-val"><?= number_format($total_saves) ?></div><div class="s-label">Saves</div></div>
    <div class="s-card" style="background:#e0f7fa"><div class="s-val"><?= number_format((int)$total_views) ?></div><div class="s-label">Views</div></div>
    <div class="s-card" style="background:#fce4ec"><div class="s-val"><?= number_format((int)$total_copies) ?></div><div class="s-label">Copies</div></div>
    <div class="s-card" style="background:#f3e5f5"><div class="s-val"><?= number_format((int)$total_shares) ?></div><div class="s-label">Shares</div></div>
    <div class="s-card" style="background:#ede9ff"><div class="s-val">+<?= $weekly_p ?></div><div class="s-label">Prompts</div><div class="s-sub">This Week</div></div>
    <div class="s-card" style="background:#e8f9ef"><div class="s-val">+<?= $weekly_u ?></div><div class="s-label">Users</div><div class="s-sub">This Week</div></div>
    <div class="s-card" style="background:#fff3cd"><div class="s-val"><?= $avg_journey ?>d</div><div class="s-label">Avg Journey</div><div class="s-sub">Signup → Unlock</div></div>
    <?php if ($most_liked): ?>
    <div class="s-card" style="background:#fff1b8;grid-column:span 2">
      <div class="s-val" style="font-size:.9rem;font-weight:900"><i class="fa-solid fa-star"></i> <?= htmlspecialchars($most_liked["title"]) ?></div>
      <div class="s-label">Top Prompt &mdash; <?= $most_liked["likes_count"] ?> <i class="fa-solid fa-heart"></i></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── ALERTS & MILESTONES ── -->
  <div class="sec-hdr"><i class="fa-solid fa-bell"></i> Alerts &amp; Milestones</div>
  <div class="alert-row">

    <div class="alert-box alert-ms">
      <h4><i class="fa-solid fa-trophy"></i> User Milestones</h4>
      <div>
        <?php foreach ($reached_ms as $m): ?>
          <span class="ms-pill"><i class="fa-solid fa-check"></i> <?= $m ?></span>
        <?php endforeach; ?>
        <?php if ($next_ms): ?>
          <span class="ms-next"><i class="fa-solid fa-bullseye"></i> <?= $next_ms ?> next</span>
        <?php endif; ?>
        <?php if (empty($reached_ms) && !$next_ms): ?>
          <span style="font-size:.82rem;font-weight:600;color:#555">No milestones yet.</span>
        <?php endif; ?>
      </div>
      <div style="margin-top:10px;font-size:.81rem;font-weight:700;color:#555"><?= $total_users ?> / <?= $next_ms ?? '—' ?> users</div>
    </div>

    <div class="alert-box alert-spike">
      <h4><i class="fa-solid fa-bolt"></i> Spike Days (50+ signups)</h4>
      <?php if ($spike_days): ?>
        <ul><?php foreach ($spike_days as $s): ?><li><?= htmlspecialchars($s['d']) ?> &mdash; <strong><?= $s['cnt'] ?></strong> signups</li><?php endforeach; ?></ul>
      <?php else: ?><p style="color:#888">No spike days yet.</p><?php endif; ?>
    </div>

    <div class="alert-box alert-dead">
      <h4><i class="fa-solid fa-skull"></i> Dead Prompts (0 unlocks / 30d)</h4>
      <?php if ($dead_prompts): ?>
        <ul><?php foreach (array_slice($dead_prompts, 0, 5) as $d): ?><li><?= htmlspecialchars($d['title']) ?> <span style="color:#bbb">(<?= $d['likes_count'] ?> ♥)</span></li><?php endforeach; ?></ul>
        <?php if (count($dead_prompts) > 5): ?><div style="font-size:.73rem;color:#aaa;font-weight:700;margin-top:5px">+<?= count($dead_prompts)-5 ?> more</div><?php endif; ?>
      <?php else: ?><p style="color:#888">All prompts active! 🎉</p><?php endif; ?>
    </div>

    <div class="alert-box alert-churn">
      <h4><i class="fa-solid fa-user-slash"></i> Churn Risk (active 8–30d ago)</h4>
      <?php if ($churn_users): ?>
        <ul><?php foreach (array_slice($churn_users, 0, 5) as $cu): ?><li><?= htmlspecialchars($cu['username']) ?> <span style="color:#bbb">(<?= date('d M', strtotime($cu['last_active'])) ?>)</span></li><?php endforeach; ?></ul>
        <?php if (count($churn_users) > 5): ?><div style="font-size:.73rem;color:#aaa;font-weight:700;margin-top:5px">+<?= count($churn_users)-5 ?> more</div><?php endif; ?>
      <?php else: ?><p style="color:#888">No churn risk. ✅</p><?php endif; ?>
    </div>

  </div>

  <!-- ── ENGAGEMENT TRENDS ── -->
  <div class="sec-hdr"><i class="fa-solid fa-chart-line"></i> Engagement Trends (Last 30 Days)</div>
  <div class="charts-grid">
    <div class="chart-card"><h3><i class="fa-solid fa-bookmark"></i> Saves per Day</h3><canvas id="savesChart"></canvas></div>
    <div class="chart-card"><h3><i class="fa-solid fa-unlock"></i> Unlocks per Day</h3><canvas id="unlocksPerDayChart"></canvas></div>
    <div class="chart-card"><h3><i class="fa-solid fa-user-plus"></i> User Growth</h3><canvas id="userLineChart"></canvas></div>
    <div class="chart-card"><h3><i class="fa-solid fa-box"></i> Prompt Uploads</h3><canvas id="promptLineChart"></canvas></div>
  </div>

  <!-- ── CONTENT & AUDIENCE ── -->
  <div class="sec-hdr"><i class="fa-solid fa-users"></i> Content &amp; Audience</div>
  <div class="charts-grid">
    <div class="chart-card"><h3><i class="fa-solid fa-book-open"></i> Blog Reads (Top 10)</h3><canvas id="blogChart"></canvas></div>
    <div class="chart-card"><h3><i class="fa-solid fa-clock"></i> New Users by Hour (IST)</h3><canvas id="hourChart"></canvas></div>
    <div class="chart-card">
      <h3><i class="fa-solid fa-repeat"></i> User Retention</h3>
      <div class="ret-wrap">
        <div class="ret-row">
          <div class="ret-label"><span>Day-1 Retention</span><span><?= $ret_d1 ?>%</span></div>
          <div class="ret-track"><div class="ret-fill" style="width:<?= $ret_d1 ?>%;background:#C6ADFA"></div></div>
        </div>
        <div class="ret-row">
          <div class="ret-label"><span>Day-7 Retention</span><span><?= $ret_d7 ?>%</span></div>
          <div class="ret-track"><div class="ret-fill" style="width:<?= $ret_d7 ?>%;background:#FFE066"></div></div>
        </div>
        <div class="ret-row">
          <div class="ret-label"><span>Day-30 Retention</span><span><?= $ret_d30 ?>%</span></div>
          <div class="ret-track"><div class="ret-fill" style="width:<?= $ret_d30 ?>%;background:#ff9e91"></div></div>
        </div>
        <p style="font-size:.72rem;color:#aaa;margin-top:10px;font-weight:600">Requires <code>last_active</code> column in users table.</p>
      </div>
    </div>
    <div class="chart-card"><h3><i class="fa-solid fa-users-between-lines"></i> New vs Returning (7d)</h3><canvas id="newReturnChart"></canvas></div>
    <div class="chart-card chart-full"><h3><i class="fa-solid fa-heart"></i> Likes per Prompt (Top 10)</h3><canvas id="barChart"></canvas></div>
    <div class="chart-card chart-full"><h3><i class="fa-solid fa-unlock-keyhole"></i> Most Unlocked Prompts (Top 10)</h3><canvas id="unlockChart"></canvas></div>
    <div class="chart-card chart-full"><h3><i class="fa-solid fa-chart-pie"></i> Prompt Type Breakdown</h3><canvas id="typeChart"></canvas></div>
  </div>

  <!-- ── DETAILED TABLES ── -->
  <div class="sec-hdr"><i class="fa-solid fa-table"></i> Detailed Data</div>
  <div class="tables-grid">

    <div class="tbl-card">
      <h3><i class="fa-solid fa-bookmark"></i> Top Saved Prompts</h3>
      <?php if ($top_saved): ?>
      <table><thead><tr><th>#</th><th>Title</th><th>Saves</th></tr></thead><tbody>
      <?php foreach ($top_saved as $i => $r): ?>
        <tr><td style="color:#bbb;font-weight:900"><?= $i+1 ?></td><td><?= htmlspecialchars($r['title']) ?></td><td><strong><?= $r['c'] ?></strong></td></tr>
      <?php endforeach; ?>
      </tbody></table>
      <?php else: ?><div class="tbl-empty">No saves yet.</div><?php endif; ?>
    </div>

    <div class="tbl-card">
      <h3><i class="fa-solid fa-eye"></i> Unlock-to-View Ratio</h3>
      <?php if ($unlock_view): ?>
      <table><thead><tr><th>Title</th><th>Views</th><th>Unlocks</th><th>Ratio</th></tr></thead><tbody>
      <?php foreach ($unlock_view as $r):
        $ratio = $r['views'] > 0 ? round($r['unlocks']/$r['views']*100,1) : ($r['unlocks'] > 0 ? 100 : 0); ?>
        <tr><td><?= htmlspecialchars($r['title']) ?></td><td><?= $r['views'] ?></td><td><?= $r['unlocks'] ?></td><td><strong><?= $ratio ?>%</strong></td></tr>
      <?php endforeach; ?>
      </tbody></table>
      <?php else: ?><div class="tbl-empty">No data yet.</div><?php endif; ?>
    </div>

    <div class="tbl-card">
      <h3><i class="fa-solid fa-bolt"></i> Power Users (5+ unlocks)</h3>
      <?php if ($power_users): ?>
      <table><thead><tr><th>#</th><th>User</th><th>Email</th><th>Unlocks</th></tr></thead><tbody>
      <?php foreach ($power_users as $i => $r): ?>
        <tr><td style="color:#bbb;font-weight:900"><?= $i+1 ?></td><td><?= htmlspecialchars($r['username']) ?></td><td style="color:#888;font-size:.75rem"><?= htmlspecialchars($r['email']) ?></td><td><strong><?= $r['cnt'] ?></strong></td></tr>
      <?php endforeach; ?>
      </tbody></table>
      <?php else: ?><div class="tbl-empty">No power users yet.</div><?php endif; ?>
    </div>

    <div class="tbl-card">
      <h3><i class="fa-solid fa-hourglass-half"></i> Prompt Age vs Performance</h3>
      <?php if ($age_perf): ?>
      <table><thead><tr><th>Title</th><th>Age (d)</th><th>Unlocks</th><th>Likes</th></tr></thead><tbody>
      <?php foreach ($age_perf as $r): ?>
        <tr><td><?= htmlspecialchars($r['title']) ?></td><td><?= $r['age'] ?></td><td><?= $r['unlocks'] ?></td><td><?= $r['likes_count'] ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
      <?php else: ?><div class="tbl-empty">No data yet.</div><?php endif; ?>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  Chart.defaults.font.family = 'Outfit, sans-serif';
  Chart.defaults.font.weight = '700';
  Chart.defaults.color = '#2D2A35';
  const C = ['#E6D7FF','#FFF1B8','#d4eaff','#ffe3f0','#d9f5e5','#C6ADFA','#FFE066','#b3d9ff','#ffb3cc','#a3e8c2'];
  const lo = { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'#EAE3F2'}},x:{grid:{display:false}}} };

  const mkLine = (id, labels, data, color, alpha) =>
    new Chart(document.getElementById(id), {
      type:'line',
      data:{labels, datasets:[{data, borderColor:color, backgroundColor:`rgba(${alpha},.18)`, borderWidth:3, pointBackgroundColor:color, pointRadius:4, fill:true, tension:.4}]},
      options:lo
    });

  mkLine('savesChart',    <?= $spd['l'] ?>, <?= $spd['d'] ?>, '#ff69b4', '255,105,180');
  mkLine('unlocksPerDayChart', <?= $upd['l'] ?>, <?= $upd['d'] ?>, '#f0a500', '240,165,0');
  mkLine('userLineChart', <?= $ug['l'] ?>,  <?= $ug['d'] ?>,  '#C6ADFA', '198,173,250');
  mkLine('promptLineChart', <?= $pg['l'] ?>, <?= $pg['d'] ?>, '#FFE066', '255,224,102');

  new Chart(document.getElementById('blogChart'), {
    type:'bar',
    data:{labels:<?= $blog_labels ?>, datasets:[{data:<?= $blog_data ?>, backgroundColor:C, borderColor:'#2D2A35', borderWidth:2, borderRadius:8}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#EAE3F2'}},x:{grid:{display:false},ticks:{maxRotation:35}}}}
  });

  new Chart(document.getElementById('hourChart'), {
    type:'bar',
    data:{labels:[...Array(24).keys()].map(h=>h+':00'), datasets:[{data:<?= json_encode(array_values($hmap)) ?>, backgroundColor:'rgba(198,173,250,.75)', borderColor:'#2D2A35', borderWidth:1, borderRadius:6}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#EAE3F2'}},x:{grid:{display:false}}}}
  });

  new Chart(document.getElementById('newReturnChart'), {
    type:'doughnut',
    data:{labels:['New (7d)','Returning (7d)'], datasets:[{data:[<?= $new_7 ?>,<?= $return_7 ?>], backgroundColor:['#C6ADFA','#FFE066'], borderColor:'#2D2A35', borderWidth:2}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{family:'Outfit, sans-serif',weight:'700'},padding:16}}}}
  });

  new Chart(document.getElementById('barChart'), {
    type:'bar',
    data:{labels:<?= $bar_labels ?>, datasets:[{data:<?= $bar_data ?>, backgroundColor:C, borderColor:'#2D2A35', borderWidth:2, borderRadius:10}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'#EAE3F2'}},x:{grid:{display:false},ticks:{maxRotation:30}}}}
  });

  new Chart(document.getElementById('unlockChart'), {
    type:'bar',
    data:{labels:<?= $ul_labels ?>, datasets:[{data:<?= $ul_data ?>, backgroundColor:['#ffd6a5','#ffc8a0','#ffba9b','#ffac96','#ff9e91','#ff908c','#ff8287','#ff7482','#ff667d','#ff5878'], borderColor:'#2D2A35', borderWidth:2, borderRadius:10}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'#EAE3F2'}},x:{grid:{display:false},ticks:{maxRotation:30}}}}
  });

  new Chart(document.getElementById('typeChart'), {
    type:'doughnut',
    data:{labels:<?= $type_labels ?>, datasets:[{data:<?= $type_data ?>, backgroundColor:['#E6D7FF','#FFF1B8','#d4eaff','#d9f5e5'], borderColor:'#2D2A35', borderWidth:2}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{family:'Outfit, sans-serif',weight:'700'},padding:16}}}}
  });
});
</script>
</body></html>
