<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

// Time range filter (default: 30 days)
$range_days = isset($_GET['range']) ? (int)$_GET['range'] : 30;
if (!in_array($range_days, [7, 14, 30, 90, 365])) {
    $range_days = 30;
}

// Active tab routing (default: dashboard)
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'dashboard';
if (!in_array($active_tab, ['dashboard', 'prompts', 'blogs', 'seo', 'gsc', 'users'], true)) {
    $active_tab = 'dashboard';
}

function sqAll($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function sqOne($pdo, $sql, $params = [], $default = 0) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $v = $stmt->fetchColumn();
        return ($v !== false && $v !== null) ? $v : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function nd_type_label(string $type): string {
    $type = trim($type);
    if ($type === '') {
        $type = 'General';
    }
    return ucwords(str_replace('_', ' ', $type));
}

function nd_type_class(string $type): string {
    $k = strtolower(trim($type));
    if ($k === '') {
        $k = 'general';
    }
    $map = [
        'already_uploaded' => 'nd-tag-sky',
        'insta_viral' => 'nd-tag-pink',
        'unreleased' => 'nd-tag-amber',
        'secret' => 'nd-tag-violet',
        'solo' => 'nd-tag-green',
        'general' => 'nd-tag-slate',
    ];
    if (isset($map[$k])) {
        return $map[$k];
    }
    $palette = ['nd-tag-teal', 'nd-tag-rose', 'nd-tag-indigo', 'nd-tag-orange', 'nd-tag-lime'];
    return $palette[abs(crc32($k)) % count($palette)];
}

function fillDailyTrends($pdo, $sql, $days_count = 30) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$days_count]);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $raw = [];
    }
    $map = [];
    foreach ($raw as $r) {
        $map[$r['d']] = (int)$r['c'];
    }
    $days = [];
    $vals = [];
    for ($i = $days_count - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $days[] = date('d M', strtotime($d));
        $vals[] = $map[$d] ?? 0;
    }
    return ['labels' => $days, 'values' => $vals];
}

// Core Stats
$total_prompts = (int)sqOne($pdo, "SELECT COUNT(*) FROM prompts");
$total_likes   = (int)sqOne($pdo, "SELECT COALESCE(SUM(likes_count),0) FROM prompts");
$total_views   = (int)sqOne($pdo, "SELECT COALESCE(SUM(view_count),0) FROM prompts");
$total_copies  = (int)sqOne($pdo, "SELECT COALESCE(SUM(copy_count),0) FROM prompts");
$total_users   = (int)sqOne($pdo, "SELECT COUNT(*) FROM users");
$total_unlocks = (int)sqOne($pdo, "SELECT COUNT(*) FROM unlocked_prompts");
$total_saves   = (int)sqOne($pdo, "SELECT COUNT(*) FROM saved_prompts");
$total_blogs   = (int)sqOne($pdo, "SELECT COUNT(*) FROM blogs WHERE is_published=1");
$total_blog_views = (int)sqOne($pdo, "SELECT COALESCE(SUM(view_count),0) FROM blogs WHERE is_published=1");

// Recent Periodic Stats
$weekly_p  = (int)sqOne($pdo, "SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$weekly_u  = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$weekly_un = (int)sqOne($pdo, "SELECT COUNT(*) FROM unlocked_prompts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

$monthly_p  = (int)sqOne($pdo, "SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)", [$range_days]);
$monthly_u  = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)", [$range_days]);
$monthly_un = (int)sqOne($pdo, "SELECT COUNT(*) FROM unlocked_prompts WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)", [$range_days]);

// Conversion & Unlock Rate
$conv_rate = $total_views > 0 ? round(($total_unlocks / $total_views) * 100, 2) : 0;
$copy_rate = $total_views > 0 ? round(($total_copies / $total_views) * 100, 2) : 0;

// Chart Trends
$trend_users   = fillDailyTrends($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY d ASC", $range_days);
$trend_unlocks = fillDailyTrends($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM unlocked_prompts WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY d ASC", $range_days);
$trend_prompts = fillDailyTrends($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM prompts WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY d ASC", $range_days);

// Category / Type Distribution
$type_breakdown = sqAll($pdo, "SELECT COALESCE(NULLIF(TRIM(prompt_type),''), 'General') as ptype, COUNT(*) as cnt FROM prompts GROUP BY ptype ORDER BY cnt DESC");

// Top Prompts Detailed List
$all_prompts = sqAll($pdo, "
    SELECT p.id, p.title, p.prompt_type, p.image_path, p.likes_count, p.view_count, p.copy_count, p.slug, p.created_at,
           COUNT(u.id) as unlock_count
    FROM prompts p
    LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id
    GROUP BY p.id
    ORDER BY unlock_count DESC, p.view_count DESC
    LIMIT 100
");

// Top Blogs Performance List
$top_blogs = sqAll($pdo, "SELECT id, title, slug, view_count, created_at, tags FROM blogs WHERE is_published=1 ORDER BY view_count DESC LIMIT 20");

// Power Users List
$power_users = sqAll($pdo, "
    SELECT u.id, u.username, u.email, u.avatar, u.profile_image, u.streak_count, u.last_active, u.created_at, COUNT(up.id) as unlock_cnt
    FROM users u
    JOIN unlocked_prompts up ON u.id = up.user_id
    GROUP BY u.id
    ORDER BY unlock_cnt DESC
    LIMIT 20
");

// User Retention Metrics
$coh7   = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
$r7cnt  = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) AND last_active > DATE_ADD(created_at, INTERVAL 7 DAY)");
$ret_d7 = $coh7 > 0 ? round(($r7cnt * 100) / $coh7, 1) : 0;

$coh30   = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$r30cnt  = (int)sqOne($pdo, "SELECT COUNT(*) FROM users WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND last_active > DATE_ADD(created_at, INTERVAL 30 DAY)");
$ret_d30 = $coh30 > 0 ? round(($r30cnt * 100) / $coh30, 1) : 0;

// Churn Risk Users
$churn_users = sqAll($pdo, "SELECT username, email, last_active FROM users WHERE last_active >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND last_active < DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY last_active ASC LIMIT 10");

// Dead Prompts (0 unlocks in last 30 days)
$dead_prompts = sqAll($pdo, "
    SELECT p.id, p.title, p.created_at, p.likes_count, p.view_count, p.prompt_type
    FROM prompts p
    WHERE p.id NOT IN (SELECT DISTINCT prompt_id FROM unlocked_prompts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))
    ORDER BY p.view_count DESC
    LIMIT 10
");

// Prompt SEO, AI Engine & GSC Checklist Coverage Analytics
$seo_raw_prompts = sqAll($pdo, "
    SELECT p.id, p.title, p.prompt_type, p.image_path, p.slug, p.created_at,
           p.about_prompt, p.description, p.meta_keywords, p.best_works_in,
           p.view_count, p.likes_count, p.gsc_status, p.gsc_indexed_at
    FROM prompts p
    ORDER BY p.created_at DESC
");

$seo_total_count = count($seo_raw_prompts);
$seo_about_cnt = 0;
$seo_desc_cnt = 0;
$seo_kw_cnt = 0;
$seo_bwi_cnt = 0;
$seo_bwi_chatgpt_cnt = 0;
$seo_bwi_gemini_cnt = 0;
$seo_full_cnt = 0;
$seo_partial_cnt = 0;
$seo_zero_cnt = 0;

// GSC Checklist Counters
$gsc_pending_cnt = 0;
$gsc_already_cnt = 0;
$gsc_now_cnt = 0;

$seo_prompt_list = [];
foreach ($seo_raw_prompts as $sp) {
    $ab_text = trim((string)($sp['about_prompt'] ?? ''));
    $de_text = trim((string)($sp['description'] ?? ''));
    $kw_text = trim((string)($sp['meta_keywords'] ?? ''));
    $bwi_val = trim((string)($sp['best_works_in'] ?? ''));
    $gsc_st  = trim((string)($sp['gsc_status'] ?? ''));
    $gsc_at  = trim((string)($sp['gsc_indexed_at'] ?? ''));

    $has_ab = ($ab_text !== '');
    $has_de = ($de_text !== '');
    $has_kw = ($kw_text !== '');
    $has_bwi = ($bwi_val !== '');

    $ab_words = $has_ab ? count(preg_split('/\s+/', $ab_text)) : 0;
    $de_chars = mb_strlen($de_text);
    $kw_items = array_filter(array_map('trim', explode(',', $kw_text)));
    $kw_count = count($kw_items);

    $seo_score = ($has_ab ? 1 : 0) + ($has_de ? 1 : 0) + ($has_kw ? 1 : 0);

    if ($has_ab) $seo_about_cnt++;
    if ($has_de) $seo_desc_cnt++;
    if ($has_kw) $seo_kw_cnt++;
    if ($has_bwi) {
        $seo_bwi_cnt++;
        if ($bwi_val === 'chatgpt') $seo_bwi_chatgpt_cnt++;
        elseif ($bwi_val === 'nano_banana') $seo_bwi_gemini_cnt++;
    }

    if ($seo_score === 3) {
        $seo_full_cnt++;
        $status_type = 'full';
    } elseif ($seo_score > 0) {
        $seo_partial_cnt++;
        $status_type = 'partial';
    } else {
        $seo_zero_cnt++;
        $status_type = 'zero';
    }

    // GSC counting
    if ($gsc_st === 'already_indexed') {
        $gsc_already_cnt++;
    } elseif ($gsc_st === 'indexed_now') {
        $gsc_now_cnt++;
    } else {
        $gsc_pending_cnt++;
    }

    // Compute live canonical URL for GSC
    $slug_clean = trim((string)($sp['slug'] ?? ''));
    $p_type_clean = trim((string)($sp['prompt_type'] ?? ''));
    if ($p_type_clean === 'solo' && $slug_clean !== '') {
        $canonical_url = 'https://arigatodevan.com/prompts/solo/' . $slug_clean;
    } elseif ($slug_clean !== '') {
        $canonical_url = 'https://arigatodevan.com/prompts/' . $slug_clean;
    } else {
        $canonical_url = 'https://arigatodevan.com/prompt.php?id=' . (int)$sp['id'];
    }

    $gsc_inspect_url = 'https://search.google.com/search-console/inspect?resource_id=' . urlencode('https://arigatodevan.com/') . '&id=' . urlencode($canonical_url);

    $seo_prompt_list[] = [
        'id' => (int)$sp['id'],
        'title' => $sp['title'] ?? 'Untitled',
        'prompt_type' => $sp['prompt_type'] ?? 'secret',
        'image_path' => $sp['image_path'] ?? '',
        'slug' => $sp['slug'] ?? '',
        'likes_count' => (int)($sp['likes_count'] ?? 0),
        'created_at' => $sp['created_at'] ?? '',
        'has_about' => $has_ab,
        'about_words' => $ab_words,
        'has_desc' => $has_de,
        'desc_chars' => $de_chars,
        'has_kw' => $has_kw,
        'kw_count' => $kw_count,
        'has_bwi' => $has_bwi,
        'bwi' => $bwi_val,
        'seo_score' => $seo_score,
        'status_type' => $status_type,
        'canonical_url' => $canonical_url,
        'gsc_inspect_url' => $gsc_inspect_url,
        'gsc_status' => $gsc_st,
        'gsc_indexed_at' => $gsc_at,
        'gsc_date_formatted' => !empty($gsc_at) ? date('M j, Y \a\t g:i A', strtotime($gsc_at)) : ''
    ];
}

$seo_full_pct = $seo_total_count > 0 ? round(($seo_full_cnt / $seo_total_count) * 100, 1) : 0;
$seo_about_pct = $seo_total_count > 0 ? round(($seo_about_cnt / $seo_total_count) * 100, 1) : 0;
$seo_desc_pct = $seo_total_count > 0 ? round(($seo_desc_cnt / $seo_total_count) * 100, 1) : 0;
$seo_kw_pct = $seo_total_count > 0 ? round(($seo_kw_cnt / $seo_total_count) * 100, 1) : 0;
$seo_bwi_pct = $seo_total_count > 0 ? round(($seo_bwi_cnt / $seo_total_count) * 100, 1) : 0;

$gsc_checked_cnt = $gsc_already_cnt + $gsc_now_cnt;
$gsc_checked_pct = $seo_total_count > 0 ? round(($gsc_checked_cnt / $seo_total_count) * 100, 1) : 0;

// Admin Info
$admin_name = $_SESSION["username"] ?? "Admin";
$admin_avatar = $_SESSION["profile_image"] ?? "toplogo/logo01.webp";
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard &ndash; Arigato Devan</title>
    <link rel="icon" href="favicon/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="favicon/apple-touch-icon.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --nd-bg: #f3f5f8;
            --nd-surface: #ffffff;
            --nd-border: #e8ecf2;
            --nd-text-main: #111827;
            --nd-text-sec: #64748b;
            --nd-text-muted: #94a3b8;
            
            /* Pastel Theme Colors matching Reference */
            --nd-lime: #d4f938;
            --nd-lime-dark: #b8de24;
            --nd-lime-text: #0f172a;
            
            --nd-card-purple: #ebe5fc;
            --nd-card-purple-text: #5b21b6;
            --nd-card-blue: #dcf0fe;
            --nd-card-blue-text: #0369a1;
            --nd-card-green: #dcfce7;
            --nd-card-green-text: #15803d;
            --nd-card-teal: #0d7973;
            
            --nd-radius-sm: 12px;
            --nd-radius-md: 18px;
            --nd-radius-lg: 24px;
            --nd-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--nd-bg);
            color: var(--nd-text-main);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            padding: 16px;
            gap: 16px;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            width: 100%;
            max-width: 100%;
        }

        /* ── Left Sidebar (Floating White Card) ── */
        .nd-sidebar {
            width: 240px;
            background: var(--nd-surface);
            border-radius: var(--nd-radius-lg);
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            box-shadow: var(--nd-shadow);
            border: 1px solid var(--nd-border);
            height: calc(100vh - 32px);
            position: sticky;
            top: 16px;
        }

        .nd-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 8px 24px;
            border-bottom: 1px solid var(--nd-border);
            text-decoration: none;
            color: var(--nd-text-main);
        }
        .nd-brand-icon {
            width: 36px;
            height: 36px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            overflow: hidden;
            flex-shrink: 0;
        }
        .nd-brand-icon img,
        .nd-brand-logo {
            width: 36px;
            height: 36px;
            object-fit: cover;
            display: block;
        }
        .nd-brand-name {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .nd-nav-short { display: none; }

        .nd-nav {
            flex: 1;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
            overflow-y: auto;
        }

        .nd-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            border-radius: var(--nd-radius-sm);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--nd-text-sec);
            transition: all 0.15s ease;
        }
        .nd-nav-item i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        .nd-nav-item:hover {
            color: var(--nd-text-main);
            background: #f8fafc;
        }
        .nd-nav-item.active {
            background: var(--nd-lime);
            color: var(--nd-lime-text);
            font-weight: 700;
        }
        .nd-nav-item.active i {
            color: var(--nd-lime-text);
        }

        .nd-sidebar-user {
            padding-top: 16px;
            border-top: 1px solid var(--nd-border);
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .nd-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nd-user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }
        .nd-user-name {
            font-size: 0.86rem;
            font-weight: 700;
            color: var(--nd-text-main);
            line-height: 1.2;
        }
        .nd-user-role {
            font-size: 0.72rem;
            color: var(--nd-text-muted);
            font-weight: 500;
        }
        .nd-btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.84rem;
            font-weight: 600;
            color: #ef4444;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: var(--nd-radius-sm);
            background: #fef2f2;
            transition: all 0.15s;
        }
        .nd-btn-logout:hover {
            background: #fee2e2;
        }

        /* ── Main Content Area ── */
        .nd-main {
            flex: 1;
            min-width: 0;
            width: 100%;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow-x: hidden;
        }

        /* ── Top App Bar ── */
        .nd-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .nd-page-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--nd-text-main);
            letter-spacing: -0.02em;
        }
        .nd-page-subtitle {
            font-size: 0.84rem;
            color: var(--nd-text-sec);
            font-weight: 500;
            margin-top: 2px;
        }

        .nd-topbar-lead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex: 1;
            min-width: 0;
            flex-wrap: wrap;
        }
        .nd-topbar-tools {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            flex-shrink: 0;
        }

        /* Date Range Selector Pills */
        .nd-pill-group {
            display: inline-flex;
            background: #ffffff;
            border: 1px solid var(--nd-border);
            padding: 4px;
            border-radius: 999px;
            gap: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .nd-pill-btn {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--nd-text-sec);
            text-decoration: none;
            transition: all 0.15s;
        }
        .nd-pill-btn:hover {
            color: var(--nd-text-main);
        }
        .nd-pill-btn.active {
            background: #0f172a;
            color: #ffffff;
        }
        .nd-pill-short { display: none; }
        .nd-chart-box {
            height: 280px;
            position: relative;
        }
        .nd-chart-box-sm {
            height: 180px;
            position: relative;
            margin-bottom: 14px;
        }

        .nd-icon-btn {
            width: 38px;
            height: 38px;
            background: #ffffff;
            border: 1px solid var(--nd-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--nd-text-sec);
            text-decoration: none;
            font-size: 0.9rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            transition: all 0.15s;
        }
        .nd-icon-btn:hover {
            color: var(--nd-text-main);
            border-color: #cbd5e1;
        }

        /* ── 4 Top KPI Highlights Cards ── */
        .nd-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .nd-kpi-card {
            border-radius: var(--nd-radius-md);
            padding: 22px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-shadow: var(--nd-shadow);
            border: 1px solid transparent;
            transition: transform 0.15s ease;
        }
        .nd-kpi-card:hover {
            transform: translateY(-2px);
        }

        .nd-kpi-purple {
            background: var(--nd-card-purple);
            color: var(--nd-card-purple-text);
        }
        .nd-kpi-blue {
            background: var(--nd-card-blue);
            color: var(--nd-card-blue-text);
        }
        .nd-kpi-green {
            background: var(--nd-card-green);
            color: var(--nd-card-green-text);
        }
        .nd-kpi-teal {
            background: linear-gradient(135deg, #0f766e, #0c5e58);
            color: #ffffff;
        }

        .nd-kpi-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            opacity: 0.85;
            margin-bottom: 12px;
        }
        .nd-kpi-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .nd-kpi-val {
            font-size: 1.95rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }
        .nd-kpi-sub {
            font-size: 0.75rem;
            font-weight: 600;
            opacity: 0.75;
        }

        .nd-btn-kpi-cta {
            margin-top: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: var(--nd-lime);
            color: var(--nd-lime-text);
            padding: 8px 14px;
            border-radius: var(--nd-radius-sm);
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s;
        }
        .nd-btn-kpi-cta:hover {
            background: var(--nd-lime-dark);
        }

        /* ── Middle Section: Chart & Analytics Overview ── */
        .nd-grid-chart {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 18px;
        }

        .nd-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            min-width: 0;
            width: 100%;
        }

        .nd-card {
            background: var(--nd-surface);
            border-radius: var(--nd-radius-lg);
            padding: 24px;
            border: 1px solid var(--nd-border);
            box-shadow: var(--nd-shadow);
            display: flex;
            flex-direction: column;
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
        }

        .nd-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .nd-card-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--nd-text-main);
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            flex: 1;
        }
        .nd-card-title span {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .nd-btn-lime {
            background: var(--nd-lime);
            color: var(--nd-lime-text);
            padding: 6px 14px;
            border-radius: var(--nd-radius-sm);
            font-size: 0.78rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .nd-btn-lime:hover {
            background: var(--nd-lime-dark);
        }

        .nd-btn-outline {
            background: #ffffff;
            color: var(--nd-text-sec);
            padding: 6px 12px;
            border-radius: var(--nd-radius-sm);
            font-size: 0.78rem;
            font-weight: 600;
            border: 1px solid var(--nd-border);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .nd-btn-outline:hover {
            background: #f8fafc;
            color: var(--nd-text-main);
        }

        /* Interactive Action List */
        .nd-action-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .nd-action-item {
            background: #f8fafc;
            border: 1px solid var(--nd-border);
            border-radius: var(--nd-radius-md);
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: var(--nd-text-main);
            transition: all 0.15s ease;
        }
        .nd-action-item:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            transform: translateX(2px);
        }
        .nd-action-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nd-action-icon {
            width: 36px;
            height: 36px;
            background: #ffffff;
            border: 1px solid var(--nd-border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: var(--nd-text-sec);
        }
        .nd-action-title {
            font-size: 0.86rem;
            font-weight: 700;
            color: var(--nd-text-main);
        }
        .nd-action-desc {
            font-size: 0.74rem;
            color: var(--nd-text-muted);
            font-weight: 500;
        }

        /* ── Filter Bar for Tables ── */
        .nd-filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .nd-filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .nd-search-input {
            flex: 1;
            min-width: 220px;
            background: #f8fafc;
            border: 1px solid var(--nd-border);
            border-radius: var(--nd-radius-sm);
            padding: 8px 14px;
            font-family: inherit;
            font-size: 0.84rem;
            outline: none;
            transition: all 0.15s;
        }
        .nd-search-input:focus {
            background: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .nd-select-filter {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
        }
        .nd-dd {
            position: relative;
            min-width: 200px;
        }
        .nd-dd-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: #fff;
            border: 1px solid var(--nd-border);
            border-radius: 999px;
            padding: 9px 14px;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--nd-text-main);
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }
        .nd-dd-btn i { color: var(--nd-text-muted); font-size: 0.7rem; transition: transform .15s; }
        .nd-dd.is-open .nd-dd-btn {
            border-color: #d4f938;
            box-shadow: 0 0 0 3px rgba(212, 249, 56, .35);
        }
        .nd-dd.is-open .nd-dd-btn i { transform: rotate(180deg); }
        .nd-dd-menu {
            display: none;
            position: absolute;
            z-index: 40;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            min-width: 220px;
            background: #fff;
            border: 1px solid var(--nd-border);
            border-radius: 16px;
            padding: 6px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .12);
            max-height: 260px;
            overflow-y: auto;
        }
        .nd-dd.is-open .nd-dd-menu { display: block; }
        .nd-dd-opt {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            padding: 9px 12px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--nd-text-sec);
            cursor: pointer;
        }
        .nd-dd-opt:hover { background: #f8fafc; color: var(--nd-text-main); }
        .nd-dd-opt.is-on {
            background: var(--nd-lime);
            color: var(--nd-lime-text);
            font-weight: 800;
        }
        .nd-dd-count {
            font-size: 0.68rem;
            font-weight: 800;
            color: var(--nd-text-muted);
            background: #f1f5f9;
            border-radius: 999px;
            padding: 1px 7px;
        }
        .nd-dd-opt.is-on .nd-dd-count { background: rgba(15,23,42,.08); color: var(--nd-lime-text); }

        /* ── Data Tables ── */
        .nd-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
            margin: 0 -4px;
            padding-bottom: 4px;
        }
        .nd-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
            text-align: left;
        }
        .nd-title-clip {
            font-weight: 700;
            color: var(--nd-text-main);
            max-width: 280px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .nd-email {
            font-size: 0.7rem;
            color: var(--nd-text-muted);
            word-break: break-word;
        }
        .nd-table th {
            padding: 12px 14px;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--nd-text-sec);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--nd-border);
            background: #f8fafc;
        }
        .nd-table th:first-child { border-radius: var(--nd-radius-sm) 0 0 var(--nd-radius-sm); }
        .nd-table th:last-child { border-radius: 0 var(--nd-radius-sm) var(--nd-radius-sm) 0; }
        
        .nd-table td {
            padding: 14px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--nd-text-main);
            font-weight: 500;
            vertical-align: middle;
        }
        .nd-table tr:hover td {
            background: #fafbfc;
        }

        .nd-tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            white-space: nowrap;
            background: #f1f5f9;
            color: var(--nd-text-sec);
        }
        .nd-tag-sky { background: #e0f2fe; color: #0369a1; }
        .nd-tag-pink { background: #fce7f3; color: #be185d; }
        .nd-tag-amber { background: #fef3c7; color: #b45309; }
        .nd-tag-violet { background: #ede9fe; color: #6d28d9; }
        .nd-tag-green { background: #dcfce7; color: #15803d; }
        .nd-tag-teal { background: #ccfbf1; color: #0f766e; }
        .nd-tag-rose { background: #ffe4e6; color: #e11d48; }
        .nd-tag-indigo { background: #e0e7ff; color: #3730a3; }
        .nd-tag-orange { background: #ffedd5; color: #c2410c; }
        .nd-tag-lime { background: #ecfccb; color: #3f6212; }
        .nd-tag-slate { background: #f1f5f9; color: #475569; }

        .nd-prompt-thumb {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            object-fit: cover;
            background: #e2e8f0;
            flex-shrink: 0;
        }

        /* ── Prompt SEO Tracker Styles ── */
        .nd-seo-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        @media (min-width: 1440px) {
            .nd-seo-kpi-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }
        .nd-seo-kpi-card {
            background: #f8fafc;
            border: 1px solid var(--nd-border);
            border-radius: var(--nd-radius-md);
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
            transition: all 0.2s ease;
        }
        .nd-seo-kpi-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            transform: translateY(-2px);
        }
        .nd-seo-kpi-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .nd-seo-kpi-title {
            font-size: 0.73rem;
            font-weight: 700;
            color: var(--nd-text-sec);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .nd-seo-kpi-badge {
            font-size: 0.72rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.06);
            color: var(--nd-text-main);
            flex-shrink: 0;
        }
        .nd-seo-kpi-val {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--nd-text-main);
            line-height: 1.1;
        }
        .nd-seo-kpi-total {
            font-size: 0.85rem;
            color: var(--nd-text-muted);
            font-weight: 600;
            margin-left: 2px;
        }
        .nd-seo-progress-bg {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin: 8px 0 6px;
        }
        .nd-seo-progress-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.4s ease;
        }
        .nd-seo-kpi-sub {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--nd-text-muted);
        }
        .nd-seo-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .nd-seo-chip-ok {
            background: #dcfce7;
            color: #15803d;
        }
        .nd-seo-chip-miss {
            background: #fee2e2;
            color: #b91c1c;
        }
        .nd-seo-bwi-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 800;
            white-space: nowrap;
        }
        .nd-seo-bwi-chatgpt {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .nd-seo-bwi-gemini {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .nd-seo-bwi-none {
            background: #f1f5f9;
            color: #64748b;
            border: 1px dashed #cbd5e1;
        }
        .nd-seo-score-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 800;
            white-space: nowrap;
        }
        .nd-seo-score-3 {
            background: #dcfce7;
            color: #15803d;
        }
        .nd-seo-score-2 {
            background: #fef3c7;
            color: #b45309;
        }
        .nd-seo-score-1 {
            background: #ffedd5;
            color: #c2410c;
        }
        .nd-seo-score-0 {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* ── GSC Indexing Checklist Styles ── */
        .nd-gsc-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        @media (min-width: 1200px) {
            .nd-gsc-kpi-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        .nd-gsc-kpi-card {
            background: #f8fafc;
            border: 1px solid var(--nd-border);
            border-radius: var(--nd-radius-md);
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
            transition: all 0.2s ease;
        }
        .nd-gsc-kpi-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            transform: translateY(-2px);
        }
        /* ── Strict Container & Table Layout Fixed for 100% Elastic Shrink ── */
        #gscDataTable {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
        }
        #gscDataTable th,
        #gscDataTable td {
            box-sizing: border-box;
            vertical-align: middle;
        }
        #gscDataTable th:nth-child(1),
        #gscDataTable td:nth-child(1) {
            width: auto;
            min-width: 0;
            overflow: hidden;
        }
        #gscDataTable th:nth-child(2),
        #gscDataTable td:nth-child(2) {
            width: 50px;
            text-align: center;
            white-space: nowrap;
        }
        #gscDataTable th:nth-child(3),
        #gscDataTable td:nth-child(3) {
            width: 36%;
            min-width: 0;
            overflow: hidden;
        }
        #gscDataTable th:nth-child(4),
        #gscDataTable td:nth-child(4) {
            width: 135px;
            text-align: right;
            white-space: nowrap;
        }
        @media (min-width: 1400px) {
            #gscDataTable th:nth-child(4),
            #gscDataTable td:nth-child(4) {
                width: 250px;
            }
        }
        .nd-gsc-url-box {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 4px 8px;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }
        .nd-gsc-url-text {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.70rem;
            color: #334155;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
            min-width: 0;
        }
        .nd-gsc-btn-copy {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 3px 6px;
            font-size: 0.67rem;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            transition: all 0.15s ease;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .nd-gsc-btn-copy:hover {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }
        .nd-gsc-actions {
            display: inline-flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        @media (min-width: 1400px) {
            .nd-gsc-actions {
                flex-direction: row;
                align-items: center;
                gap: 6px;
                justify-content: flex-end;
            }
        }
        .nd-gsc-label-full { display: inline; }
        .nd-gsc-label-short { display: none; }

        @media (max-width: 1300px) {
            .nd-gsc-label-full { display: none !important; }
            .nd-gsc-label-short { display: inline !important; }
            #gscDataTable th:nth-child(4),
            #gscDataTable td:nth-child(4) {
                width: 95px !important;
            }
            .nd-gsc-btn-already,
            .nd-gsc-btn-now {
                padding: 4px 6px !important;
                font-size: 0.70rem !important;
                gap: 4px !important;
                width: 100% !important;
            }
        }
        .nd-gsc-btn-already {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            border-radius: 8px;
            font-size: 0.69rem;
            font-weight: 700;
            padding: 4px 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            white-space: nowrap;
            flex-shrink: 0;
            width: 100%;
            box-sizing: border-box;
            transition: all 0.15s ease;
        }
        .nd-gsc-btn-already:hover {
            background: #16a34a;
            color: #ffffff;
            border-color: #16a34a;
            transform: scale(1.02);
        }
        .nd-gsc-btn-now {
            background: #ede9fe;
            color: #6d28d9;
            border: 1px solid #c4b5fd;
            border-radius: 8px;
            font-size: 0.69rem;
            font-weight: 700;
            padding: 4px 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            white-space: nowrap;
            flex-shrink: 0;
            width: 100%;
            box-sizing: border-box;
            transition: all 0.15s ease;
        }
        .nd-gsc-btn-now:hover {
            background: #7c3aed;
            color: #ffffff;
            border-color: #7c3aed;
            transform: scale(1.02);
        }
        .nd-gsc-locked-wrap {
            display: inline-flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 3px;
            width: 100%;
            box-sizing: border-box;
            white-space: nowrap;
        }
        .nd-gsc-badge-already {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            font-size: 0.69rem;
            font-weight: 800;
            padding: 3px 8px;
            width: fit-content;
            white-space: nowrap;
        }
        .nd-gsc-badge-now {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #ede9fe;
            color: #6d28d9;
            border: 1px solid #ddd6fe;
            border-radius: 999px;
            font-size: 0.69rem;
            font-weight: 800;
            padding: 3px 8px;
            width: fit-content;
            white-space: nowrap;
        }
        .nd-gsc-timestamp {
            font-size: 0.66rem;
            color: var(--nd-text-muted);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 3px;
            white-space: nowrap;
        }
        @media (max-width: 900px) {
            .nd-gsc-kpi-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Display Visibility Helpers ── */
        .nd-desktop-only { display: block; }
        .nd-mobile-only { display: none; }
        .nd-desktop-widescreen-only { display: block; }
        .nd-desktop-notice { display: none; }

        /* ── Mobile Feed Cards (Clean App-Like Experience) ── */
        .nd-mobile-cards-wrap {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 4px;
        }
        .nd-mobile-prompt-card {
            background: #ffffff;
            border: 1px solid var(--nd-border);
            border-radius: 16px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
            transition: all 0.15s ease;
        }
        .nd-mp-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }
        .nd-mp-lead {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            flex: 1;
        }
        .nd-mp-thumb {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: #0f172a;
        }
        .nd-mp-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--nd-text-main);
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .nd-mp-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 3px;
            font-size: 0.7rem;
            color: var(--nd-text-muted);
        }
        .nd-mp-stats-strip {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 8px 4px;
            gap: 4px;
            text-align: center;
        }
        .nd-mp-stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 0;
        }
        .nd-mp-stat-label {
            font-size: 0.62rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .nd-mp-stat-val {
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--nd-text-main);
            margin-top: 1px;
        }

        /* Mobile Blog Card */
        .nd-mobile-blog-card {
            background: #ffffff;
            border: 1px solid var(--nd-border);
            border-radius: 14px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }
        .nd-mb-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--nd-text-main);
            line-height: 1.35;
        }
        .nd-mb-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        /* Mobile User Card */
        .nd-mobile-user-card {
            background: #ffffff;
            border: 1px solid var(--nd-border);
            border-radius: 14px;
            padding: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }

        /* Mobile Churn Card */
        .nd-mobile-churn-card {
            background: #ffffff;
            border: 1px solid #fee2e2;
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        /* Mobile Dead Prompts Card */
        .nd-mobile-dead-card {
            background: #ffffff;
            border: 1px solid #fef3c7;
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        /* ── Responsive Layout ── */
        @media (max-width: 1200px) {
            .nd-kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .nd-grid-chart {
                grid-template-columns: 1fr;
            }
        }

        /* Tablet: icon-only rail */
        @media (max-width: 1080px) and (min-width: 721px) {
            body { padding: 12px; gap: 12px; }
            .nd-sidebar {
                width: 78px;
                padding: 18px 10px;
                align-items: center;
            }
            .nd-brand {
                padding: 0 0 18px;
                justify-content: center;
                border-bottom: 1px solid var(--nd-border);
            }
            .nd-brand-name,
            .nd-nav-item span,
            .nd-user-name,
            .nd-user-role,
            .nd-btn-logout span { display: none; }
            .nd-nav {
                width: 100%;
                align-items: stretch;
            }
            .nd-nav-item {
                justify-content: center;
                padding: 12px 8px;
                gap: 0;
            }
            .nd-sidebar-user {
                width: 100%;
                align-items: center;
            }
            .nd-user-info { justify-content: center; }
            .nd-btn-logout {
                justify-content: center;
                padding: 10px;
            }
            .nd-back-dash span { display: none; }
            .nd-back-dash { padding: 10px; }
        }

        /* Widescreen Notice for SEO & GSC on Mobile/Tablet */
        @media (max-width: 768px) {
            .nd-desktop-widescreen-only { display: none !important; }
            .nd-desktop-notice {
                display: flex !important;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 38px 20px;
                background: #ffffff;
                border: 1px solid var(--nd-border);
                border-radius: 22px;
                box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
                margin: 8px 0 24px;
                gap: 14px;
            }
            .nd-desktop-notice-icon {
                width: 64px;
                height: 64px;
                border-radius: 18px;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                color: #d4f938;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.75rem;
                box-shadow: 0 8px 20px -4px rgba(15, 23, 42, 0.25);
            }
            .nd-desktop-notice-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #f1f5f9;
                color: #475569;
                font-size: 0.72rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                padding: 4px 12px;
                border-radius: 999px;
            }
            .nd-desktop-notice-title {
                font-family: 'Plus Jakarta Sans', sans-serif;
                font-size: 1.25rem;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.3;
                margin: 0;
            }
            .nd-desktop-notice-desc {
                font-size: 0.84rem;
                color: #64748b;
                line-height: 1.55;
                max-width: 320px;
                margin: 0;
            }
            .nd-desktop-notice-btn {
                margin-top: 4px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: var(--nd-lime);
                color: var(--nd-lime-text);
                font-weight: 800;
                font-size: 0.84rem;
                padding: 12px 20px;
                border-radius: 12px;
                text-decoration: none;
                box-shadow: 0 4px 14px rgba(212, 249, 56, 0.35);
            }
        }

        /* Phone: sleek stacked layout — clean mobile UI with real breathing room */
        @media (max-width: 720px) {
            .nd-desktop-only { display: none !important; }
            .nd-mobile-only { display: flex !important; }

            body {
                flex-direction: column;
                padding: 12px;
                padding-bottom: max(16px, env(safe-area-inset-bottom));
                gap: 14px;
            }
            .nd-main { gap: 16px; }
            .nd-sidebar {
                width: 100%;
                height: auto;
                position: static;
                padding: 16px 14px 14px;
                gap: 0;
            }
            .nd-brand {
                padding: 0 2px 14px;
                gap: 10px;
            }
            .nd-nav {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
                flex: none;
                overflow: visible;
                padding: 14px 0;
            }
            .nd-nav-item {
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 6px;
                padding: 12px 4px 10px;
                font-size: 0.68rem;
                font-weight: 700;
                text-align: center;
                min-width: 0;
            }
            .nd-nav-item i { font-size: 1rem; width: auto; }
            .nd-nav-full { display: none; }
            .nd-nav-short {
                display: block;
                line-height: 1.2;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .nd-sidebar-user {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding-top: 12px;
            }
            .nd-btn-logout {
                padding: 9px 12px;
                flex-shrink: 0;
            }
            .nd-btn-logout span { display: none; }
            .nd-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }
            .nd-kpi-card { padding: 14px 12px; }
            .nd-kpi-val { font-size: 1.35rem; margin-bottom: 3px; }
            .nd-kpi-header { font-size: 0.68rem; margin-bottom: 8px; }
            .nd-kpi-sub { font-size: 0.7rem; }
            .nd-btn-kpi-cta { margin-top: 10px; padding: 7px 10px; font-size: 0.75rem; }
            .nd-page-title {
                font-size: 1.28rem;
                line-height: 1.25;
                margin: 0;
                width: 100%;
                word-break: break-word;
            }
            .nd-page-subtitle {
                font-size: 0.76rem;
                line-height: 1.4;
                color: var(--nd-text-sec);
                margin-top: 4px;
                width: 100%;
            }
            .nd-topbar {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                width: 100%;
            }
            .nd-topbar-lead {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                justify-content: flex-start;
                gap: 10px;
                width: 100%;
            }
            .nd-topbar-tools {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
                width: 100%;
            }
            .nd-topbar-tools .nd-tag-pill {
                font-size: 0.72rem;
                padding: 5px 10px;
                white-space: normal;
                line-height: 1.3;
            }
            .nd-topbar-tools .nd-btn-lime {
                font-size: 0.75rem;
                padding: 6px 12px;
            }
            .nd-pill-group {
                width: 100%;
                display: flex;
                justify-content: stretch;
            }
            .nd-pill-btn {
                flex: 1;
                text-align: center;
                padding: 8px 4px;
                font-size: 0.72rem;
            }
            .nd-pill-full { display: none; }
            .nd-pill-short { display: inline; }
            .nd-card { padding: 14px 12px; }
            .nd-card-title { font-size: 0.92rem; align-items: flex-start; }
            .nd-card-head { margin-bottom: 12px; gap: 8px; }
            .nd-grid-2,
            .nd-grid-chart {
                grid-template-columns: 1fr;
                gap: 14px;
            }
            .nd-chart-box { height: 200px; }
            .nd-chart-box-sm { height: 160px; }
            
            /* Clean Full-Width Filter Controls with Clear Spacing */
            .nd-filter-bar {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 14px !important;
                margin-bottom: 18px !important;
                width: 100% !important;
            }
            .nd-search-input {
                width: 100% !important;
                padding: 11px 16px !important;
                font-size: 0.84rem !important;
                border-radius: 12px !important;
                margin: 0 !important;
            }
            .nd-filter-group {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
                width: 100% !important;
            }
            .nd-dd {
                width: 100% !important;
                min-width: 100% !important;
                margin: 0 0 4px 0 !important;
            }
            .nd-dd:last-child {
                margin-bottom: 0 !important;
            }
            .nd-dd-btn {
                width: 100% !important;
                padding: 11px 16px !important;
                border-radius: 12px !important;
                font-size: 0.82rem !important;
            }
            .nd-dd-menu {
                width: 100% !important;
                min-width: 100% !important;
                border-radius: 14px !important;
            }
            .nd-hide-sm { display: none !important; }
            .nd-title-clip {
                max-width: none;
                white-space: normal;
                overflow: visible;
                text-overflow: unset;
                line-height: 1.35;
            }
            .nd-prompt-thumb { width: 32px; height: 32px; }
            .nd-action-item { padding: 12px; }
            .nd-action-desc { display: none; }
            .nd-back-dash { margin: 4px 0 10px; }
            .nd-back-dash-top span { display: none; }
            .nd-back-dash-top { width: 36px; height: 36px; padding: 0; border-radius: 50%; }
        }

        @media (max-width: 420px) {
            .nd-kpi-sub { display: none; }
        }

        .nd-back-dash {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 0 0 12px;
            padding: 10px 14px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 800;
            color: var(--nd-lime-text);
            background: var(--nd-lime);
            border: 1px solid var(--nd-lime);
        }
        .nd-back-dash-top {
            margin: 0;
            padding: 8px 12px;
            white-space: nowrap;
        }
        .nd-splash {
            position: fixed;
            inset: 0;
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(160deg, #f3f5f8 0%, #fff 48%, #eef6d4 100%);
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .nd-splash.is-out { transform: translateY(-100%); pointer-events: none; }
        .nd-splash-inner {
            width: min(380px, 88vw);
            text-align: center;
        }
        .nd-splash-logo {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            object-fit: cover;
            margin: 0 auto 18px;
            display: block;
            background: #0f172a;
        }
        .nd-splash-type {
            margin: 0 0 22px;
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
            font-size: clamp(1.7rem, 5vw, 2.3rem);
            font-weight: 700;
            color: #111827;
            min-height: 1.25em;
        }
        .nd-splash-word {
            background: linear-gradient(90deg, #6D2D52, #F5709D, #11FFC9, #2FA6C6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .nd-splash-cursor {
            display: inline-block;
            margin-left: 2px;
            color: #567C8D;
            font-style: normal;
            animation: nd-blink 0.9s step-end infinite;
        }
        @keyframes nd-blink { 50% { opacity: 0; } }
        .nd-splash-bar {
            height: 6px;
            border-radius: 999px;
            background: #e8ecf2;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .nd-splash-fill {
            width: 0;
            height: 100%;
            border-radius: inherit;
            background: var(--nd-lime);
            animation: nd-fill 4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        @keyframes nd-fill { to { width: 100%; } }
        .nd-splash-label {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            color: #94a3b8;
            text-transform: uppercase;
        }
        body.nd-splash-lock { overflow: hidden; }

        @media (max-width: 1080px) and (min-width: 721px) {
            .nd-back-dash span { display: none; }
            .nd-back-dash { padding: 10px; }
        }
        @media (max-width: 720px) {
            .nd-back-dash { margin: 4px 0 10px; }
            .nd-back-dash-top span { display: none; }
            .nd-back-dash-top { width: 36px; height: 36px; padding: 0; border-radius: 50%; }
        }
    </style>
</head>
<body class="nd-splash-lock">
<div id="nd-splash" class="nd-splash" role="status" aria-live="polite">
    <div class="nd-splash-inner">
        <img src="toplogo/logo01.webp" alt="" class="nd-splash-logo">
        <p class="nd-splash-type" aria-label="arigato.intel">
            arigato.<span class="nd-splash-word" id="nd-splash-word"></span><span class="nd-splash-cursor" aria-hidden="true">|</span>
        </p>
        <div class="nd-splash-bar" aria-hidden="true"><div class="nd-splash-fill"></div></div>
        <div class="nd-splash-label">Loading live intelligence</div>
    </div>
</div>
    <aside class="nd-sidebar">
        <a href="dashboard.php" class="nd-brand">
            <div class="nd-brand-icon">
                <img src="toplogo/logo01.webp" alt="Arigato" class="nd-brand-logo" width="36" height="36">
            </div>
            <div class="nd-brand-name">Arigato Studio</div>
        </a>

        <nav class="nd-nav" aria-label="Analytics sections">
            <a href="analytics.php?tab=dashboard<?= $range_days !== 30 ? '&range=' . $range_days : '' ?>" class="nd-nav-item <?= $active_tab === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
                <i class="fa-solid fa-table-columns"></i>
                <span class="nd-nav-full">Dashboard</span>
                <span class="nd-nav-short">Home</span>
            </a>
            <a href="analytics.php?tab=prompts" class="nd-nav-item <?= $active_tab === 'prompts' ? 'active' : '' ?>" title="Top Prompts">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span class="nd-nav-full">Top Prompts</span>
                <span class="nd-nav-short">Prompts</span>
            </a>
            <a href="analytics.php?tab=blogs" class="nd-nav-item <?= $active_tab === 'blogs' ? 'active' : '' ?>" title="Blog Insights">
                <i class="fa-solid fa-newspaper"></i>
                <span class="nd-nav-full">Blog Insights</span>
                <span class="nd-nav-short">Blogs</span>
            </a>
            <a href="analytics.php?tab=seo" class="nd-nav-item <?= $active_tab === 'seo' ? 'active' : '' ?>" title="SEO & AI Engine">
                <i class="fa-solid fa-magnifying-glass-chart"></i>
                <span class="nd-nav-full">SEO &amp; Content</span>
                <span class="nd-nav-short">SEO</span>
            </a>
            <a href="analytics.php?tab=gsc" class="nd-nav-item <?= $active_tab === 'gsc' ? 'active' : '' ?>" title="GSC Checklist">
                <i class="fa-brands fa-google"></i>
                <span class="nd-nav-full">GSC Checklist</span>
                <span class="nd-nav-short">GSC</span>
            </a>
            <a href="analytics.php?tab=users" class="nd-nav-item <?= $active_tab === 'users' ? 'active' : '' ?>" title="Users & Retention">
                <i class="fa-solid fa-users"></i>
                <span class="nd-nav-full">Users & Retention</span>
                <span class="nd-nav-short">Users</span>
            </a>
        </nav>
        <a href="dashboard.php" class="nd-back-dash" title="Back to old dashboard">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Old Dashboard</span>
        </a>

        <div class="nd-sidebar-user">
            <div class="nd-user-info">
                <img src="<?= htmlspecialchars($admin_avatar) ?>" class="nd-user-avatar" alt="Admin">
                <div>
                    <div class="nd-user-name"><?= htmlspecialchars($admin_name) ?></div>
                    <div class="nd-user-role">Super Admin</div>
                </div>
            </div>
            <a href="login.php?logout=1" class="nd-btn-logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Log Out</span>
            </a>
        </div>
    </aside>

    <!-- ── Main Dashboard Workspace ── -->
    <main class="nd-main">

        <?php if ($active_tab === 'dashboard'): ?>
        <!-- ========================================== -->
        <!-- TAB 1: DASHBOARD OVERVIEW                  -->
        <!-- ========================================== -->
        <header class="nd-topbar">
            <div class="nd-topbar-lead">
                <div>
                    <h1 class="nd-page-title">Dashboard Overview</h1>
                    <div class="nd-page-subtitle"><?= date('l, jS F Y') ?> &bull; Live Intelligence &amp; Study Engine</div>
                </div>
                <div class="nd-topbar-tools">
                    <a href="analytics_report.php?format=excel&period=<?= $range_days <= 7 ? 'weekly' : 'monthly' ?>" class="nd-btn-lime" title="Download Formatted Excel Sheet">
                        <i class="fa-solid fa-file-excel"></i>
                        <span>Excel Report</span>
                    </a>
                    <a href="dashboard.php" class="nd-back-dash nd-back-dash-top" title="Back to old dashboard">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Old Dashboard</span>
                    </a>
                </div>
            </div>
            <div class="nd-pill-group">
                <a href="analytics.php?tab=dashboard&range=7" class="nd-pill-btn <?= $range_days === 7 ? 'active' : '' ?>"><span class="nd-pill-full">7 Days</span><span class="nd-pill-short">7D</span></a>
                <a href="analytics.php?tab=dashboard&range=14" class="nd-pill-btn <?= $range_days === 14 ? 'active' : '' ?>"><span class="nd-pill-full">14 Days</span><span class="nd-pill-short">14D</span></a>
                <a href="analytics.php?tab=dashboard&range=30" class="nd-pill-btn <?= $range_days === 30 ? 'active' : '' ?>"><span class="nd-pill-full">30 Days</span><span class="nd-pill-short">30D</span></a>
                <a href="analytics.php?tab=dashboard&range=90" class="nd-pill-btn <?= $range_days === 90 ? 'active' : '' ?>"><span class="nd-pill-full">90 Days</span><span class="nd-pill-short">90D</span></a>
                <a href="analytics.php?tab=dashboard&range=365" class="nd-pill-btn <?= $range_days === 365 ? 'active' : '' ?>"><span class="nd-pill-full">Year</span><span class="nd-pill-short">1Y</span></a>
            </div>
        </header>

        <!-- 4 Top KPI Highlights Cards -->
        <section class="nd-kpi-grid">
            <div class="nd-kpi-card nd-kpi-purple">
                <div>
                    <div class="nd-kpi-header">
                        <span class="nd-kpi-dot"></span>
                        <span>Prompts Library</span>
                    </div>
                    <div class="nd-kpi-val"><?= number_format($total_prompts) ?></div>
                </div>
                <div class="nd-kpi-sub">
                    <i class="fa-regular fa-eye"></i> <?= number_format($total_views) ?> views &bull; +<?= $weekly_p ?> this week
                </div>
            </div>

            <div class="nd-kpi-card nd-kpi-blue">
                <div>
                    <div class="nd-kpi-header">
                        <span class="nd-kpi-dot"></span>
                        <span>Total Unlocks</span>
                    </div>
                    <div class="nd-kpi-val"><?= number_format($total_unlocks) ?></div>
                </div>
                <div class="nd-kpi-sub">
                    <i class="fa-regular fa-copy"></i> <?= number_format($total_copies) ?> copies &bull; +<?= $weekly_un ?> this week
                </div>
            </div>

            <div class="nd-kpi-card nd-kpi-green">
                <div>
                    <div class="nd-kpi-header">
                        <span class="nd-kpi-dot"></span>
                        <span>Unlock Rate / Conversion</span>
                    </div>
                    <div class="nd-kpi-val"><?= $conv_rate ?>%</div>
                </div>
                <div class="nd-kpi-sub">
                    <i class="fa-solid fa-users"></i> <?= number_format($total_users) ?> users (+<?= $weekly_u ?> new)
                </div>
            </div>

            <div class="nd-kpi-card nd-kpi-teal">
                <div>
                    <div class="nd-kpi-header" style="color:#d4f938;">
                        <span class="nd-kpi-dot" style="background:#d4f938;"></span>
                        <span>Platform Engine</span>
                    </div>
                    <div class="nd-kpi-val" style="font-size:1.55rem;"><?= number_format($total_likes) ?> Likes</div>
                    <div class="nd-kpi-sub" style="color:rgba(255,255,255,0.85);">
                        D7 Retention: <?= $ret_d7 ?>% &bull; <?= $total_blogs ?> Published Blogs
                    </div>
                </div>
                <a href="analytics_report.php?format=print&period=monthly" target="_blank" class="nd-btn-kpi-cta">
                    <i class="fa-solid fa-chart-pie"></i> Print Full Report
                </a>
            </div>
        </section>

        <!-- Middle Section: Main Chart & Action Breakdowns -->
        <section class="nd-grid-chart">
            <div class="nd-card">
                <div class="nd-card-head">
                    <div>
                        <div class="nd-card-title">
                            <span>Activity Trends (Last <?= $range_days ?> Days)</span>
                        </div>
                        <div style="font-size:0.75rem; color:var(--nd-text-muted); margin-top:2px;">
                            Comparing User Signups vs Prompt Unlocks daily
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:0.75rem; font-weight:700; color:#5b21b6;"><i class="fa-solid fa-square" style="color:#a78bfa;"></i> Signups</span>
                        <span style="font-size:0.75rem; font-weight:700; color:#065f46;"><i class="fa-solid fa-square" style="color:#34d399;"></i> Unlocks</span>
                    </div>
                </div>
                <div class="nd-chart-box">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="nd-card">
                <div class="nd-card-head">
                    <div class="nd-card-title">
                        <span>Category Share</span>
                    </div>
                    <span style="font-size:0.75rem; font-weight:700; color:var(--nd-text-muted);"><?= count($type_breakdown) ?> Types</span>
                </div>
                <div class="nd-chart-box-sm">
                    <canvas id="categoryDoughnut"></canvas>
                </div>
                <div class="nd-action-list">
                    <?php 
                    $top_type = $type_breakdown[0] ?? ['ptype' => 'General', 'cnt' => 0];
                    $type_pct = $total_prompts > 0 ? round(($top_type['cnt'] / $total_prompts) * 100, 1) : 0;
                    ?>
                    <div class="nd-action-item">
                        <div class="nd-action-item-left">
                            <div class="nd-action-icon"><i class="fa-solid fa-fire" style="color:#f59e0b;"></i></div>
                            <div>
                                <div class="nd-action-title">Top: <?= htmlspecialchars($top_type['ptype']) ?></div>
                                <div class="nd-action-desc"><?= number_format($top_type['cnt']) ?> prompts (<?= $type_pct ?>% share)</div>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="font-size:0.75rem; color:var(--nd-text-muted);"></i>
                    </div>
                    <div class="nd-action-item">
                        <div class="nd-action-item-left">
                            <div class="nd-action-icon"><i class="fa-solid fa-bookmark" style="color:#3b82f6;"></i></div>
                            <div>
                                <div class="nd-action-title">Saved to Bookmarks</div>
                                <div class="nd-action-desc"><?= number_format($total_saves) ?> prompts bookmarked</div>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="font-size:0.75rem; color:var(--nd-text-muted);"></i>
                    </div>
                </div>
            </div>
        </section>

        <!-- Optimization Hub (Dead Prompts & Churn Risk) -->
        <section class="nd-grid-2">
            <div class="nd-card">
                <div class="nd-card-head">
                    <div class="nd-card-title">
                        <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i>
                        <span>Churn Risk Users (Inactive 7-30 Days)</span>
                    </div>
                </div>
                <!-- Desktop Table View -->
                <div class="nd-table-wrap nd-desktop-only">
                    <table class="nd-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th class="nd-hide-sm">Email</th>
                                <th>Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($churn_users)): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--nd-text-muted);">No churn risk detected.</td></tr>
                            <?php else: ?>
                                <?php foreach ($churn_users as $cu): ?>
                                <tr>
                                    <td style="font-weight:700;"><?= htmlspecialchars($cu['username']) ?></td>
                                    <td class="nd-hide-sm" style="color:var(--nd-text-muted);"><?= htmlspecialchars($cu['email'] ?: '-') ?></td>
                                    <td style="color:#ef4444; font-weight:600;"><?= !empty($cu['last_active']) ? date('M j, Y', strtotime($cu['last_active'])) : '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Mobile Card View -->
                <div class="nd-mobile-only" style="flex-direction:column; gap:8px;">
                    <?php if (empty($churn_users)): ?>
                        <div style="text-align:center; color:var(--nd-text-muted); padding:16px 0; font-size:0.82rem;">No churn risk detected.</div>
                    <?php else: ?>
                        <?php foreach ($churn_users as $cu): ?>
                        <div class="nd-mobile-churn-card">
                            <div style="min-width:0; flex:1;">
                                <div style="font-weight:700; font-size:0.85rem; color:#0f172a;"><?= htmlspecialchars($cu['username']) ?></div>
                                <div style="font-size:0.72rem; color:var(--nd-text-muted); margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($cu['email'] ?: 'No email') ?></div>
                            </div>
                            <div style="font-size:0.74rem; font-weight:700; color:#ef4444; text-align:right; white-space:nowrap; flex-shrink:0;">
                                <i class="fa-regular fa-clock"></i> <?= !empty($cu['last_active']) ? date('M j, Y', strtotime($cu['last_active'])) : '-' ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="nd-card">
                <div class="nd-card-head">
                    <div class="nd-card-title">
                        <i class="fa-solid fa-bolt" style="color:#f59e0b;"></i>
                        <span>Zero Unlocks (Optimization Queue)</span>
                    </div>
                </div>
                <!-- Desktop Table View -->
                <div class="nd-table-wrap nd-desktop-only">
                    <table class="nd-table">
                        <thead>
                            <tr>
                                <th>Prompt Title</th>
                                <th>Views</th>
                                <th class="nd-hide-sm">Category</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dead_prompts)): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--nd-text-muted);">All prompts active.</td></tr>
                            <?php else: ?>
                                <?php foreach ($dead_prompts as $dp): ?>
                                <tr>
                                    <td class="nd-title-clip">
                                        <?= htmlspecialchars($dp['title']) ?>
                                    </td>
                                    <td><?= number_format($dp['view_count']) ?></td>
                                    <td class="nd-hide-sm"><span class="nd-tag-pill <?= nd_type_class((string) ($dp['prompt_type'] ?? '')) ?>"><?= htmlspecialchars(nd_type_label((string) ($dp['prompt_type'] ?? ''))) ?></span></td>
                                    <td>
                                        <a href="edit_prompt.php?id=<?= $dp['id'] ?>" class="nd-btn-outline" style="padding:3px 7px; font-size:0.72rem;">
                                            Optimize
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Mobile Card View -->
                <div class="nd-mobile-only" style="flex-direction:column; gap:8px;">
                    <?php if (empty($dead_prompts)): ?>
                        <div style="text-align:center; color:var(--nd-text-muted); padding:16px 0; font-size:0.82rem;">All prompts active.</div>
                    <?php else: ?>
                        <?php foreach ($dead_prompts as $dp): ?>
                        <div class="nd-mobile-dead-card">
                            <div style="min-width:0; flex:1;">
                                <div style="font-weight:700; font-size:0.84rem; color:#0f172a; line-height:1.3; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($dp['title']) ?></div>
                                <div style="display:flex; align-items:center; gap:8px; margin-top:4px; font-size:0.7rem; color:var(--nd-text-muted);">
                                    <span class="nd-tag-pill <?= nd_type_class((string) ($dp['prompt_type'] ?? '')) ?>" style="padding:2px 6px; font-size:0.62rem;"><?= htmlspecialchars(nd_type_label((string) ($dp['prompt_type'] ?? ''))) ?></span>
                                    <span><i class="fa-regular fa-eye"></i> <?= number_format($dp['view_count']) ?> views</span>
                                </div>
                            </div>
                            <a href="edit_prompt.php?id=<?= $dp['id'] ?>" class="nd-btn-outline" style="padding:4px 8px; font-size:0.72rem; flex-shrink:0;">
                                Optimize
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php elseif ($active_tab === 'prompts'): ?>
        <!-- ========================================== -->
        <!-- TAB 2: TOP PROMPTS PERFORMANCE            -->
        <!-- ========================================== -->
        <header class="nd-topbar">
            <div class="nd-topbar-lead">
                <div>
                    <h1 class="nd-page-title">Top Prompts Performance</h1>
                    <div class="nd-page-subtitle"><?= number_format($total_prompts) ?> Total Prompts &bull; <?= number_format($total_unlocks) ?> Total Unlocks &bull; <?= $conv_rate ?>% Avg Conversion</div>
                </div>
                <div class="nd-topbar-tools">
                    <a href="upload_prompt.php" class="nd-btn-lime">
                        <i class="fa-solid fa-plus"></i>
                        <span>New Prompt</span>
                    </a>
                </div>
            </div>
        </header>

        <section class="nd-card">
            <div class="nd-card-head">
                <div class="nd-card-title">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color:#3b82f6;"></i>
                    <span>Prompts Library &amp; Unlock Metrics</span>
                </div>
            </div>

            <!-- Table Filter Toolbar -->
            <div class="nd-filter-bar">
                <input type="text" id="promptSearchInput" class="nd-search-input" placeholder="Search prompts by title or keyword..." onkeyup="filterPromptsTable()">
                
                <div class="nd-filter-group">
                    <select id="promptTypeFilter" class="nd-select-filter" onchange="filterPromptsTable()">
                        <option value="">All Categories / Types</option>
                        <?php foreach ($type_breakdown as $t): ?>
                            <option value="<?= htmlspecialchars(strtolower($t['ptype'])) ?>"><?= htmlspecialchars($t['ptype']) ?> (<?= $t['cnt'] ?>)</option>
                        <?php endforeach; ?>
                    </select>

                    <select id="promptSortFilter" class="nd-select-filter" onchange="sortPromptsTable()">
                        <option value="unlocks">Sort by Most Unlocked</option>
                        <option value="views">Sort by Most Viewed</option>
                        <option value="likes">Sort by Most Liked</option>
                        <option value="copies">Sort by Most Copied</option>
                    </select>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="nd-table-wrap nd-desktop-only">
                <table class="nd-table" id="promptsDataTable">
                    <thead>
                        <tr>
                            <th>Prompt Title</th>
                            <th>Category</th>
                            <th class="nd-hide-sm">Views</th>
                            <th class="nd-hide-sm">Copies</th>
                            <th class="nd-hide-sm">Likes</th>
                            <th>Unlocks</th>
                            <th class="nd-hide-sm">Conversion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_prompts as $p): 
                            $p_conv = $p['view_count'] > 0 ? round(($p['unlock_count'] / $p['view_count']) * 100, 1) : 0;
                            $thumb = !empty($p['image_path']) ? $p['image_path'] : 'toplogo/logo01.webp';
                        ?>
                        <tr data-title="<?= htmlspecialchars(strtolower($p['title'])) ?>"
                            data-type="<?= htmlspecialchars(strtolower($p['prompt_type'] ?: 'general')) ?>"
                            data-unlocks="<?= (int)$p['unlock_count'] ?>"
                            data-views="<?= (int)$p['view_count'] ?>"
                            data-likes="<?= (int)$p['likes_count'] ?>"
                            data-copies="<?= (int)$p['copy_count'] ?>">
                            
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <img loading="lazy" src="<?= htmlspecialchars($thumb) ?>" class="nd-prompt-thumb" alt="" onerror="this.src='toplogo/logo01.webp'">
                                    <div>
                                        <div class="nd-title-clip" title="<?= htmlspecialchars($p['title']) ?>">
                                            <?= htmlspecialchars($p['title']) ?>
                                        </div>
                                        <div style="font-size:0.72rem; color:var(--nd-text-muted);">
                                            <?= date('M j, Y', strtotime($p['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="nd-tag-pill <?= nd_type_class((string) ($p['prompt_type'] ?? '')) ?>"><?= htmlspecialchars(nd_type_label((string) ($p['prompt_type'] ?? ''))) ?></span>
                            </td>
                            <td class="nd-hide-sm"><?= number_format($p['view_count']) ?></td>
                            <td class="nd-hide-sm"><?= number_format($p['copy_count']) ?></td>
                            <td class="nd-hide-sm"><?= number_format($p['likes_count']) ?></td>
                            <td>
                                <span style="font-weight:700; color:#059669;"><?= number_format($p['unlock_count']) ?></span>
                            </td>
                            <td class="nd-hide-sm">
                                <span style="font-weight:700; color:<?= $p_conv >= 10 ? '#059669' : ($p_conv >= 3 ? '#d97706' : '#94a3b8') ?>;">
                                    <?= $p_conv ?>%
                                </span>
                            </td>
                            <td>
                                <a href="edit_prompt.php?id=<?= $p['id'] ?>" class="nd-btn-outline" style="padding:4px 8px; font-size:0.72rem;" title="Edit Prompt">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Feed Cards View -->
            <div class="nd-mobile-cards-wrap nd-mobile-only" id="promptsMobileCards">
                <?php foreach ($all_prompts as $p): 
                    $p_conv = $p['view_count'] > 0 ? round(($p['unlock_count'] / $p['view_count']) * 100, 1) : 0;
                    $thumb = !empty($p['image_path']) ? $p['image_path'] : 'toplogo/logo01.webp';
                ?>
                <div class="nd-mobile-prompt-card"
                     data-title="<?= htmlspecialchars(strtolower($p['title'])) ?>"
                     data-type="<?= htmlspecialchars(strtolower($p['prompt_type'] ?: 'general')) ?>"
                     data-unlocks="<?= (int)$p['unlock_count'] ?>"
                     data-views="<?= (int)$p['view_count'] ?>"
                     data-likes="<?= (int)$p['likes_count'] ?>"
                     data-copies="<?= (int)$p['copy_count'] ?>">
                    <div class="nd-mp-header">
                        <div class="nd-mp-lead">
                            <img loading="lazy" src="<?= htmlspecialchars($thumb) ?>" class="nd-mp-thumb" alt="" onerror="this.src='toplogo/logo01.webp'">
                            <div style="min-width:0;">
                                <div class="nd-mp-title"><?= htmlspecialchars($p['title']) ?></div>
                                <div class="nd-mp-meta">
                                    <span class="nd-tag-pill <?= nd_type_class((string) ($p['prompt_type'] ?? '')) ?>" style="padding:2px 7px; font-size:0.65rem;"><?= htmlspecialchars(nd_type_label((string) ($p['prompt_type'] ?? ''))) ?></span>
                                    <span>&bull; <?= date('M j, Y', strtotime($p['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="edit_prompt.php?id=<?= $p['id'] ?>" class="nd-btn-outline" style="padding:5px 10px; font-size:0.74rem; flex-shrink:0;">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                    </div>
                    <div class="nd-mp-stats-strip">
                        <div class="nd-mp-stat-item">
                            <span class="nd-mp-stat-label">Views</span>
                            <span class="nd-mp-stat-val"><?= number_format($p['view_count']) ?></span>
                        </div>
                        <div class="nd-mp-stat-item">
                            <span class="nd-mp-stat-label">Unlocks</span>
                            <span class="nd-mp-stat-val" style="color:#059669;"><?= number_format($p['unlock_count']) ?></span>
                        </div>
                        <div class="nd-mp-stat-item">
                            <span class="nd-mp-stat-label">Copies</span>
                            <span class="nd-mp-stat-val"><?= number_format($p['copy_count']) ?></span>
                        </div>
                        <div class="nd-mp-stat-item">
                            <span class="nd-mp-stat-label">Likes</span>
                            <span class="nd-mp-stat-val" style="color:#e11d48;"><?= number_format($p['likes_count']) ?></span>
                        </div>
                        <div class="nd-mp-stat-item">
                            <span class="nd-mp-stat-label">Conv</span>
                            <span class="nd-mp-stat-val" style="color:<?= $p_conv >= 10 ? '#059669' : ($p_conv >= 3 ? '#d97706' : '#94a3b8') ?>;"><?= $p_conv ?>%</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php elseif ($active_tab === 'blogs'): ?>
        <!-- ========================================== -->
        <!-- TAB 3: BLOG INSIGHTS                       -->
        <!-- ========================================== -->
        <header class="nd-topbar">
            <div class="nd-topbar-lead">
                <div>
                    <h1 class="nd-page-title">Blog Reads &amp; Performance</h1>
                    <div class="nd-page-subtitle"><?= $total_blogs ?> Published Posts &bull; <?= number_format($total_blog_views) ?> Total Article Reads</div>
                </div>
                <div class="nd-topbar-tools">
                    <a href="blog_create.php" class="nd-btn-lime">
                        <i class="fa-solid fa-plus"></i>
                        <span>Write Post</span>
                    </a>
                </div>
            </div>
        </header>

        <section class="nd-card">
            <div class="nd-card-head">
                <div class="nd-card-title">
                    <i class="fa-solid fa-newspaper" style="color:#8b5cf6;"></i>
                    <span>Published Articles Leaderboard</span>
                </div>
            </div>

            <div class="nd-filter-bar">
                <input type="text" id="blogSearchInput" class="nd-search-input" placeholder="Filter blogs by title..." onkeyup="filterBlogsTable()">
            </div>

            <!-- Desktop Table View -->
            <div class="nd-table-wrap nd-desktop-only">
                <table class="nd-table" id="blogsDataTable">
                    <thead>
                        <tr>
                            <th>Article Title</th>
                            <th>Reads</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_blogs as $b): ?>
                        <tr data-title="<?= htmlspecialchars(strtolower($b['title'])) ?>">
                            <td>
                                <div class="nd-title-clip">
                                    <?= htmlspecialchars($b['title']) ?>
                                </div>
                                <div style="font-size:0.7rem; color:var(--nd-text-muted);">
                                    <?= date('M j, Y', strtotime($b['created_at'])) ?>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight:700; color:#5b21b6;"><?= number_format($b['view_count']) ?></span>
                            </td>
                            <td>
                                <a href="blog_edit.php?id=<?= $b['id'] ?>" class="nd-btn-outline" style="padding:4px 8px; font-size:0.72rem;">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                                <?php if (!empty($b['slug'])): ?>
                                    <a href="blog.php?slug=<?= urlencode($b['slug']) ?>" target="_blank" class="nd-btn-outline" style="padding:4px 8px; font-size:0.72rem;">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Feed Cards View -->
            <div class="nd-mobile-cards-wrap nd-mobile-only" id="blogsMobileCards">
                <?php foreach ($top_blogs as $b): ?>
                <div class="nd-mobile-blog-card" data-title="<?= htmlspecialchars(strtolower($b['title'])) ?>">
                    <div>
                        <div class="nd-mb-title"><?= htmlspecialchars($b['title']) ?></div>
                        <div style="font-size:0.72rem; color:var(--nd-text-muted); margin-top:3px;">
                            <i class="fa-regular fa-calendar"></i> <?= date('M j, Y', strtotime($b['created_at'])) ?>
                        </div>
                    </div>
                    <div class="nd-mb-footer">
                        <span style="font-size:0.78rem; font-weight:700; color:#5b21b6; background:#f5f3ff; border:1px solid #ddd6fe; padding:3px 10px; border-radius:999px;">
                            <i class="fa-regular fa-eye"></i> <?= number_format($b['view_count']) ?> Reads
                        </span>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <a href="blog_edit.php?id=<?= $b['id'] ?>" class="nd-btn-outline" style="padding:5px 10px; font-size:0.74rem;">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <?php if (!empty($b['slug'])): ?>
                                <a href="blog.php?slug=<?= urlencode($b['slug']) ?>" target="_blank" class="nd-btn-outline" style="padding:5px 10px; font-size:0.74rem;">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php elseif ($active_tab === 'seo'): ?>
        <!-- ========================================== -->
        <!-- TAB 4: SEO & CONTENT TRACKER              -->
        <!-- ========================================== -->
        <header class="nd-topbar">
            <div class="nd-topbar-lead">
                <div>
                    <h1 class="nd-page-title">Prompt SEO &amp; AI Engine Tracker</h1>
                    <div class="nd-page-subtitle">Track About notes, Meta descriptions, Keywords, and AI Engines</div>
                </div>
                <div class="nd-topbar-tools">
                    <span class="nd-tag-pill nd-tag-green" style="font-size:0.75rem; padding:6px 12px;">
                        <i class="fa-solid fa-shield-halved"></i> <strong><?= $seo_full_cnt ?> / <?= $seo_total_count ?></strong> (<?= $seo_full_pct ?>%) 3/3 SEO Ready
                    </span>
                    <span class="nd-tag-pill nd-tag-amber" style="font-size:0.75rem; padding:6px 12px;">
                        <i class="fa-solid fa-microchip"></i> <strong><?= $seo_bwi_cnt ?> / <?= $seo_total_count ?></strong> (<?= $seo_bwi_pct ?>%) Engine Tagged
                    </span>
                </div>
            </div>
        </header>

        <!-- Mobile Desktop-Only Notice Card -->
        <div class="nd-desktop-notice">
            <div class="nd-desktop-notice-icon">
                <i class="fa-solid fa-laptop-code"></i>
            </div>
            <div class="nd-desktop-notice-badge">
                <i class="fa-solid fa-display"></i> Widescreen Recommended
            </div>
            <h2 class="nd-desktop-notice-title">Desktop Recommended View</h2>
            <p class="nd-desktop-notice-desc">
                The <strong>Prompt SEO &amp; AI Engine Tracker</strong> contains wide audit matrices and inspection tools designed specifically for laptop and desktop screens. Please open this page on your computer for the full analytics experience.
            </p>
            <a href="analytics.php?tab=dashboard" class="nd-desktop-notice-btn">
                <i class="fa-solid fa-arrow-left"></i> Go to Dashboard Overview
            </a>
        </div>

        <!-- Desktop Widescreen Container -->
        <div class="nd-desktop-widescreen-only">
            <!-- 5 SEO & Engine Metric Cards -->
            <div class="nd-seo-kpi-grid">
                <div class="nd-seo-kpi-card" style="background:#ecfdf5; border-color:#a7f3d0;">
                    <div class="nd-seo-kpi-head">
                        <div class="nd-seo-kpi-title" style="color:#047857;"><i class="fa-solid fa-circle-check"></i> Fully Optimized (3/3)</div>
                        <div class="nd-seo-kpi-badge" style="background:#d1fae5; color:#065f46;"><?= $seo_full_pct ?>%</div>
                    </div>
                    <div class="nd-seo-kpi-val" style="color:#065f46;"><?= $seo_full_cnt ?><span class="nd-seo-kpi-total">/<?= $seo_total_count ?></span></div>
                    <div class="nd-seo-progress-bg">
                        <div class="nd-seo-progress-fill" style="width:<?= $seo_full_pct ?>%; background:#10b981;"></div>
                    </div>
                    <div class="nd-seo-kpi-sub">Has About, Meta Desc &amp; Keywords</div>
                </div>

                <div class="nd-seo-kpi-card" style="background:#f5f3ff; border-color:#ddd6fe;">
                    <div class="nd-seo-kpi-head">
                        <div class="nd-seo-kpi-title" style="color:#6d28d9;"><i class="fa-solid fa-align-left"></i> About Note</div>
                        <div class="nd-seo-kpi-badge" style="background:#ede9fe; color:#5b21b6;"><?= $seo_about_pct ?>%</div>
                    </div>
                    <div class="nd-seo-kpi-val" style="color:#5b21b6;"><?= $seo_about_cnt ?><span class="nd-seo-kpi-total">/<?= $seo_total_count ?></span></div>
                    <div class="nd-seo-progress-bg">
                        <div class="nd-seo-progress-fill" style="width:<?= $seo_about_pct ?>%; background:#8b5cf6;"></div>
                    </div>
                    <div class="nd-seo-kpi-sub">150-200 word editorial notes</div>
                </div>

                <div class="nd-seo-kpi-card" style="background:#f0f9ff; border-color:#bae6fd;">
                    <div class="nd-seo-kpi-head">
                        <div class="nd-seo-kpi-title" style="color:#0369a1;"><i class="fa-solid fa-tags"></i> Meta Description</div>
                        <div class="nd-seo-kpi-badge" style="background:#e0f2fe; color:#0369a1;"><?= $seo_desc_pct ?>%</div>
                    </div>
                    <div class="nd-seo-kpi-val" style="color:#0369a1;"><?= $seo_desc_cnt ?><span class="nd-seo-kpi-total">/<?= $seo_total_count ?></span></div>
                    <div class="nd-seo-progress-bg">
                        <div class="nd-seo-progress-fill" style="width:<?= $seo_desc_pct ?>%; background:#0284c7;"></div>
                    </div>
                    <div class="nd-seo-kpi-sub">Google search snippet text</div>
                </div>

                <div class="nd-seo-kpi-card" style="background:#fffbeb; border-color:#fde68a;">
                    <div class="nd-seo-kpi-head">
                        <div class="nd-seo-kpi-title" style="color:#b45309;"><i class="fa-solid fa-key"></i> SEO Keywords</div>
                        <div class="nd-seo-kpi-badge" style="background:#fef3c7; color:#92400e;"><?= $seo_kw_pct ?>%</div>
                    </div>
                    <div class="nd-seo-kpi-val" style="color:#92400e;"><?= $seo_kw_cnt ?><span class="nd-seo-kpi-total">/<?= $seo_total_count ?></span></div>
                    <div class="nd-seo-progress-bg">
                        <div class="nd-seo-progress-fill" style="width:<?= $seo_kw_pct ?>%; background:#f59e0b;"></div>
                    </div>
                    <div class="nd-seo-kpi-sub">Targeted search query tags</div>
                </div>

                <div class="nd-seo-kpi-card" style="background:#f0fdfa; border-color:#99f6e4;">
                    <div class="nd-seo-kpi-head">
                        <div class="nd-seo-kpi-title" style="color:#0f766e;"><i class="fa-solid fa-bolt"></i> Best Works In</div>
                        <div class="nd-seo-kpi-badge" style="background:#ccfbf1; color:#115e59;"><?= $seo_bwi_pct ?>%</div>
                    </div>
                    <div class="nd-seo-kpi-val" style="color:#115e59;"><?= $seo_bwi_cnt ?><span class="nd-seo-kpi-total">/<?= $seo_total_count ?></span></div>
                    <div class="nd-seo-progress-bg">
                        <div class="nd-seo-progress-fill" style="width:<?= $seo_bwi_pct ?>%; background:#0d9488;"></div>
                    </div>
                    <div class="nd-seo-kpi-sub" style="display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin-top:2px;">
                        <span><i class="fa-solid fa-robot"></i> ChatGPT: <strong><?= $seo_bwi_chatgpt_cnt ?></strong></span> &bull; 
                        <span><i class="fa-solid fa-wand-magic-sparkles"></i> Gemini: <strong><?= $seo_bwi_gemini_cnt ?></strong></span>
                    </div>
                </div>
            </div>

            <!-- SEO Table Card -->
            <section class="nd-card">
                <!-- Filter Toolbar -->
                <div class="nd-filter-bar">
                    <input type="text" id="seoSearchInput" class="nd-search-input" placeholder="Search prompts by title..." onkeyup="filterSeoTable()">
                    
                    <div class="nd-filter-group">
                        <select id="seoStatusFilter" class="nd-select-filter" onchange="filterSeoTable()">
                            <option value="">All Status (<?= $seo_total_count ?>)</option>
                            <option value="full">Fully Optimized 3/3 (<?= $seo_full_cnt ?>)</option>
                            <option value="partial">Partially Filled (<?= $seo_partial_cnt ?>)</option>
                            <option value="zero">Missing SEO (<?= $seo_zero_cnt ?>)</option>
                        </select>

                        <select id="seoBwiFilter" class="nd-select-filter" onchange="filterSeoTable()">
                            <option value="">All AI Engines (<?= $seo_total_count ?>)</option>
                            <option value="chatgpt">ChatGPT (<?= $seo_bwi_chatgpt_cnt ?>)</option>
                            <option value="nano_banana">Gemini / Nano (<?= $seo_bwi_gemini_cnt ?>)</option>
                            <option value="none">Engine Not Set (<?= $seo_total_count - $seo_bwi_cnt ?>)</option>
                        </select>

                        <select id="seoTypeFilter" class="nd-select-filter" onchange="filterSeoTable()">
                            <option value="">All Prompt Types</option>
                            <option value="secret">Secret Code</option>
                            <option value="unreleased">Unreleased</option>
                            <option value="already_uploaded">Already Uploaded</option>
                            <option value="direct">Direct Prompt</option>
                            <option value="solo">SOLO</option>
                        </select>

                        <select id="seoSortFilter" class="nd-select-filter" onchange="sortSeoTable(this.value)">
                            <option value="score_asc">Sort: Needs Attention First (0/3 → 3/3)</option>
                            <option value="score_desc">Sort: Highest Score First (3/3 → 0/3)</option>
                            <option value="newest">Sort: Newest Uploads</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="nd-table-wrap">
                    <table class="nd-table" id="seoDataTable">
                        <thead>
                            <tr>
                                <th>Prompt</th>
                                <th>About Note</th>
                                <th>Meta Description</th>
                                <th>Keywords</th>
                                <th>Best Works In</th>
                                <th>SEO Score</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seo_prompt_list as $sp):
                                $bwi_badge_html = match($sp['bwi']) {
                                    'chatgpt' => '<span class="nd-seo-bwi-tag nd-seo-bwi-chatgpt"><i class="fa-solid fa-robot"></i> ChatGPT</span>',
                                    'nano_banana' => '<span class="nd-seo-bwi-tag nd-seo-bwi-gemini"><i class="fa-solid fa-wand-magic-sparkles"></i> Gemini</span>',
                                    default => '<span class="nd-seo-bwi-tag nd-seo-bwi-none"><i class="fa-solid fa-circle-question"></i> Not Set</span>'
                                };
                                $score_badge_html = match($sp['seo_score']) {
                                    3 => '<span class="nd-seo-score-tag nd-seo-score-3"><i class="fa-solid fa-circle-check"></i> 3/3 Full</span>',
                                    2 => '<span class="nd-seo-score-tag nd-seo-score-2"><i class="fa-solid fa-circle-exclamation"></i> 2/3 Partial</span>',
                                    1 => '<span class="nd-seo-score-tag nd-seo-score-1"><i class="fa-solid fa-circle-exclamation"></i> 1/3 Partial</span>',
                                    default => '<span class="nd-seo-score-tag nd-seo-score-0"><i class="fa-solid fa-circle-xmark"></i> 0/3 Empty</span>'
                                };
                            ?>
                            <tr data-title="<?= htmlspecialchars(strtolower($sp['title'])) ?>"
                                data-status="<?= $sp['status_type'] ?>"
                                data-bwi="<?= htmlspecialchars($sp['bwi'] ?: 'none') ?>"
                                data-type="<?= htmlspecialchars($sp['prompt_type']) ?>"
                                data-score="<?= $sp['seo_score'] ?>"
                                data-date="<?= strtotime($sp['created_at']) ?>">
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <img loading="lazy" src="<?= htmlspecialchars($sp['image_path']) ?>" class="nd-prompt-thumb" alt="">
                                        <div style="min-width:0;">
                                            <div class="nd-title-clip" style="max-width:200px;" title="<?= htmlspecialchars($sp['title']) ?>">
                                                <?= htmlspecialchars($sp['title']) ?>
                                            </div>
                                            <div style="display:flex; align-items:center; gap:6px; margin-top:2px;">
                                                <span class="nd-tag-pill <?= nd_type_class($sp['prompt_type']) ?>" style="font-size:0.65rem; padding:2px 7px;">
                                                    <?= nd_type_label($sp['prompt_type']) ?>
                                                </span>
                                                <span style="font-size:0.68rem; color:var(--nd-text-muted);">
                                                    <?= date('M j, Y', strtotime($sp['created_at'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($sp['has_about']): ?>
                                        <span class="nd-seo-chip nd-seo-chip-ok" title="Editorial note present">
                                            <i class="fa-solid fa-check"></i> <?= $sp['about_words'] ?> words
                                        </span>
                                    <?php else: ?>
                                        <span class="nd-seo-chip nd-seo-chip-miss" title="Missing editorial note">
                                            <i class="fa-solid fa-xmark"></i> Missing
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sp['has_desc']): ?>
                                        <span class="nd-seo-chip nd-seo-chip-ok" title="Meta description present">
                                            <i class="fa-solid fa-check"></i> <?= $sp['desc_chars'] ?> chars
                                        </span>
                                    <?php else: ?>
                                        <span class="nd-seo-chip nd-seo-chip-miss" title="Missing meta description">
                                            <i class="fa-solid fa-xmark"></i> Missing
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sp['has_kw']): ?>
                                        <span class="nd-seo-chip nd-seo-chip-ok" title="Keywords present">
                                            <i class="fa-solid fa-check"></i> <?= $sp['kw_count'] ?> tags
                                        </span>
                                    <?php else: ?>
                                        <span class="nd-seo-chip nd-seo-chip-miss" title="Missing SEO keywords">
                                            <i class="fa-solid fa-xmark"></i> Missing
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $bwi_badge_html ?>
                                </td>
                                <td>
                                    <?= $score_badge_html ?>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:5px;">
                                        <a href="edit_prompt.php?id=<?= $sp['id'] ?>" class="nd-btn-outline" style="padding:4px 8px; font-size:0.72rem;" title="Edit prompt SEO">
                                            <i class="fa-solid fa-pen"></i> Edit
                                        </a>
                                        <?php if (!empty($sp['slug'])): ?>
                                            <a href="prompts/<?= htmlspecialchars($sp['slug']) ?>" target="_blank" class="nd-btn-outline" style="padding:4px 7px; font-size:0.72rem;" title="View public page">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="prompt.php?id=<?= $sp['id'] ?>" target="_blank" class="nd-btn-outline" style="padding:4px 7px; font-size:0.72rem;" title="View public page">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <?php elseif ($active_tab === 'gsc'): ?>
        <!-- ========================================== -->
        <!-- TAB 5: GSC INDEXING CHECKLIST             -->
        <!-- ========================================== -->
        <header class="nd-topbar">
            <div class="nd-topbar-lead">
                <div>
                    <h1 class="nd-page-title">Search Console Checklist</h1>
                    <div class="nd-page-subtitle">Verify Google Search indexing status, copy URLs, and inspect in GSC</div>
                </div>
                <div class="nd-topbar-tools">
                    <span class="nd-tag-pill nd-tag-sky" style="font-size:0.75rem; padding:6px 12px;">
                        <i class="fa-solid fa-list-check"></i> <strong id="gscTotalCheckedCount"><?= $gsc_checked_cnt ?> / <?= $seo_total_count ?></strong> (<?= $gsc_checked_pct ?>%) Processed
                    </span>
                </div>
            </div>
        </header>

        <!-- Mobile Desktop-Only Notice Card -->
        <div class="nd-desktop-notice">
            <div class="nd-desktop-notice-icon">
                <i class="fa-solid fa-laptop-code"></i>
            </div>
            <div class="nd-desktop-notice-badge">
                <i class="fa-solid fa-display"></i> Widescreen Recommended
            </div>
            <h2 class="nd-desktop-notice-title">Desktop Recommended View</h2>
            <p class="nd-desktop-notice-desc">
                The <strong>Search Console Indexing Checklist</strong> contains wide inspection tables and Google Console verification actions designed for desktop screens. Please open this section on your computer for optimal workflow.
            </p>
            <a href="analytics.php?tab=dashboard" class="nd-desktop-notice-btn">
                <i class="fa-solid fa-arrow-left"></i> Go to Dashboard Overview
            </a>
        </div>

        <!-- Desktop Widescreen Container -->
        <div class="nd-desktop-widescreen-only">
            <!-- 3 GSC KPI Metric Cards -->
            <div class="nd-gsc-kpi-grid">
                <div class="nd-gsc-kpi-card" style="background:#fffbeb; border-color:#fde68a;">
                    <div class="nd-seo-kpi-head">
                        <div class="nd-seo-kpi-title" style="color:#b45309;"><i class="fa-solid fa-hourglass-half"></i> Pending Check</div>
                        <div class="nd-seo-kpi-badge" id="gscPendingBadge" style="background:#fef3c7; color:#92400e;"><?= $seo_total_count > 0 ? round(($gsc_pending_cnt / $seo_total_count) * 100, 1) : 0 ?>%</div>
                    </div>
                    <div class="nd-seo-kpi-val" style="color:#92400e;" id="gscPendingVal"><?= $gsc_pending_cnt ?><span class="nd-seo-kpi-total">/<?= $seo_total_count ?></span></div>
                    <div class="nd-seo-progress-bg">
                        <div class="nd-seo-progress-fill" id="gscPendingProgress" style="width:<?= $seo_total_count > 0 ? round(($gsc_pending_cnt / $seo_total_count) * 100, 1) : 0 ?>%; background:#f59e0b;"></div>
                    </div>
                    <div class="nd-seo-kpi-sub">Needs inspection in Search Console</div>
                </div>

                <div class="nd-gsc-kpi-card" style="background:#ecfdf5; border-color:#a7f3d0;">
                    <div class="nd-seo-kpi-head">
                        <div class="nd-seo-kpi-title" style="color:#047857;"><i class="fa-solid fa-circle-check"></i> Already Indexed</div>
                        <div class="nd-seo-kpi-badge" id="gscAlreadyBadge" style="background:#d1fae5; color:#065f46;"><?= $seo_total_count > 0 ? round(($gsc_already_cnt / $seo_total_count) * 100, 1) : 0 ?>%</div>
                    </div>
                    <div class="nd-seo-kpi-val" style="color:#065f46;" id="gscAlreadyVal"><?= $gsc_already_cnt ?><span class="nd-seo-kpi-total">/<?= $seo_total_count ?></span></div>
                    <div class="nd-seo-progress-bg">
                        <div class="nd-seo-progress-fill" id="gscAlreadyProgress" style="width:<?= $seo_total_count > 0 ? round(($gsc_already_cnt / $seo_total_count) * 100, 1) : 0 ?>%; background:#10b981;"></div>
                    </div>
                    <div class="nd-seo-kpi-sub">Verified live in Google SERP</div>
                </div>

                <div class="nd-gsc-kpi-card" style="background:#f5f3ff; border-color:#ddd6fe;">
                    <div class="nd-seo-kpi-head">
                        <div class="nd-seo-kpi-title" style="color:#6d28d9;"><i class="fa-solid fa-rocket"></i> Requested Indexing</div>
                        <div class="nd-seo-kpi-badge" id="gscNowBadge" style="background:#ede9fe; color:#5b21b6;"><?= $seo_total_count > 0 ? round(($gsc_now_cnt / $seo_total_count) * 100, 1) : 0 ?>%</div>
                    </div>
                    <div class="nd-seo-kpi-val" style="color:#5b21b6;" id="gscNowVal"><?= $gsc_now_cnt ?><span class="nd-seo-kpi-total">/<?= $seo_total_count ?></span></div>
                    <div class="nd-seo-progress-bg">
                        <div class="nd-seo-progress-fill" id="gscNowProgress" style="width:<?= $seo_total_count > 0 ? round(($gsc_now_cnt / $seo_total_count) * 100, 1) : 0 ?>%; background:#8b5cf6;"></div>
                    </div>
                    <div class="nd-seo-kpi-sub">Submitted manually in Search Console</div>
                </div>
            </div>

            <!-- GSC Checklist Table Card -->
            <section class="nd-card">
                <!-- Filter & Search Toolbar -->
                <div class="nd-filter-bar">
                    <input type="text" id="gscSearchInput" class="nd-search-input" placeholder="Search by prompt title or link..." onkeyup="filterGscTable()">
                    
                    <div class="nd-filter-group">
                        <select id="gscStatusFilter" class="nd-select-filter" onchange="filterGscTable()">
                            <option value="">All GSC Status (<?= $seo_total_count ?>)</option>
                            <option value="pending">Pending Check (<?= $gsc_pending_cnt ?>)</option>
                            <option value="already_indexed">Already Indexed (<?= $gsc_already_cnt ?>)</option>
                            <option value="indexed_now">Requested Indexing (<?= $gsc_now_cnt ?>)</option>
                        </select>

                        <select id="gscTypeFilter" class="nd-select-filter" onchange="filterGscTable()">
                            <option value="">All Prompt Types</option>
                            <option value="secret">Secret Code</option>
                            <option value="unreleased">Unreleased</option>
                            <option value="already_uploaded">Already Uploaded</option>
                            <option value="direct">Direct Prompt</option>
                            <option value="solo">SOLO</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="nd-table-wrap">
                    <table class="nd-table" id="gscDataTable">
                        <thead>
                            <tr>
                                <th>Prompt</th>
                                <th>Likes</th>
                                <th>Live Page URL</th>
                                <th>GSC Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seo_prompt_list as $sp):
                                $g_status = $sp['gsc_status'];
                                $g_is_locked = in_array($g_status, ['already_indexed', 'indexed_now'], true);
                                $row_filter_status = $g_is_locked ? $g_status : 'pending';
                            ?>
                            <tr id="gsc-row-<?= $sp['id'] ?>"
                                data-title="<?= htmlspecialchars(strtolower($sp['title'] . ' ' . $sp['canonical_url'])) ?>"
                                data-status="<?= $row_filter_status ?>"
                                data-type="<?= htmlspecialchars($sp['prompt_type']) ?>">
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                        <img loading="lazy" src="<?= htmlspecialchars($sp['image_path']) ?>" class="nd-prompt-thumb" style="width:34px; height:34px; border-radius:8px; object-fit:cover; flex-shrink:0;" alt="">
                                        <div style="min-width:0; flex:1; overflow:hidden;">
                                            <div class="nd-title-clip" style="max-width:100%; min-width:0;" title="<?= htmlspecialchars($sp['title']) ?>">
                                                <?= htmlspecialchars($sp['title']) ?>
                                            </div>
                                            <div style="display:flex; align-items:center; gap:5px; margin-top:2px;">
                                                <span class="nd-tag-pill <?= nd_type_class($sp['prompt_type']) ?>" style="font-size:0.62rem; padding:1px 6px;">
                                                    <?= nd_type_label($sp['prompt_type']) ?>
                                                </span>
                                                <span style="font-size:0.66rem; color:var(--nd-text-muted); white-space:nowrap;">
                                                    <?= date('M j, Y', strtotime($sp['created_at'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size:0.75rem; font-weight:700; color:#e11d48; display:inline-flex; align-items:center; gap:3px;">
                                        <i class="fa-solid fa-heart"></i> <?= number_format($sp['likes_count']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="nd-gsc-url-box">
                                        <span class="nd-gsc-url-text" title="<?= htmlspecialchars($sp['canonical_url']) ?>">
                                            <?= htmlspecialchars($sp['canonical_url']) ?>
                                        </span>
                                        <button type="button" class="nd-gsc-btn-copy" onclick="copyPromptUrl('<?= htmlspecialchars($sp['canonical_url'], ENT_QUOTES) ?>', this)" title="Copy full URL to clipboard">
                                            <i class="fa-solid fa-copy"></i> <span>Copy</span>
                                        </button>
                                    </div>
                                </td>
                                <td id="gsc-action-cell-<?= $sp['id'] ?>">
                                    <?php if ($g_is_locked): ?>
                                        <div class="nd-gsc-locked-wrap">
                                            <?php if ($g_status === 'already_indexed'): ?>
                                                <span class="nd-gsc-badge-already">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    <span class="nd-gsc-label-full">Already Indexed</span>
                                                    <span class="nd-gsc-label-short">Indexed</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="nd-gsc-badge-now">
                                                    <i class="fa-solid fa-rocket"></i>
                                                    <span class="nd-gsc-label-full">Requested Indexing</span>
                                                    <span class="nd-gsc-label-short">Requested</span>
                                                </span>
                                            <?php endif; ?>
                                            <div class="nd-gsc-timestamp">
                                                <i class="fa-regular fa-calendar-check"></i> <?= htmlspecialchars($sp['gsc_date_formatted']) ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="nd-gsc-actions">
                                            <button type="button" class="nd-gsc-btn-already" onclick="markGscStatus(<?= $sp['id'] ?>, 'already_indexed', this)">
                                                <i class="fa-solid fa-circle-check"></i>
                                                <span class="nd-gsc-label-full">Already Indexed</span>
                                                <span class="nd-gsc-label-short">Indexed</span>
                                            </button>
                                            <button type="button" class="nd-gsc-btn-now" onclick="markGscStatus(<?= $sp['id'] ?>, 'indexed_now', this)">
                                                <i class="fa-solid fa-rocket"></i>
                                                <span class="nd-gsc-label-full">I Indexed Now</span>
                                                <span class="nd-gsc-label-short">Request</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <?php elseif ($active_tab === 'users'): ?>
        <!-- ========================================== -->
        <!-- TAB 6: USERS & RETENTION                  -->
        <!-- ========================================== -->
        <header class="nd-topbar">
            <div class="nd-topbar-lead">
                <div>
                    <h1 class="nd-page-title">Users &amp; Retention Hub</h1>
                    <div class="nd-page-subtitle"><?= number_format($total_users) ?> Registered Users &bull; D7 Retention: <?= $ret_d7 ?>% &bull; D30 Retention: <?= $ret_d30 ?>%</div>
                </div>
                <div class="nd-topbar-tools">
                    <a href="user_management.php" class="nd-btn-lime">
                        <i class="fa-solid fa-users-gear"></i>
                        <span>Manage Users</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Cohort Retention Stats Cards -->
        <section class="nd-kpi-grid">
            <div class="nd-kpi-card nd-kpi-green">
                <div>
                    <div class="nd-kpi-header">
                        <span class="nd-kpi-dot"></span>
                        <span>Day 7 Retention</span>
                    </div>
                    <div class="nd-kpi-val"><?= $ret_d7 ?>%</div>
                </div>
                <div class="nd-kpi-sub">
                    <?= $r7cnt ?> / <?= $coh7 ?> users active after 7 days
                </div>
            </div>

            <div class="nd-kpi-card nd-kpi-blue">
                <div>
                    <div class="nd-kpi-header">
                        <span class="nd-kpi-dot"></span>
                        <span>Day 30 Retention</span>
                    </div>
                    <div class="nd-kpi-val"><?= $ret_d30 ?>%</div>
                </div>
                <div class="nd-kpi-sub">
                    <?= $r30cnt ?> / <?= $coh30 ?> users active after 30 days
                </div>
            </div>

            <div class="nd-kpi-card nd-kpi-purple">
                <div>
                    <div class="nd-kpi-header">
                        <span class="nd-kpi-dot"></span>
                        <span>Total Members</span>
                    </div>
                    <div class="nd-kpi-val"><?= number_format($total_users) ?></div>
                </div>
                <div class="nd-kpi-sub">
                    +<?= $weekly_u ?> new signups this week
                </div>
            </div>

            <div class="nd-kpi-card nd-kpi-teal">
                <div>
                    <div class="nd-kpi-header" style="color:#d4f938;">
                        <span class="nd-kpi-dot" style="background:#d4f938;"></span>
                        <span>Power Users</span>
                    </div>
                    <div class="nd-kpi-val" style="font-size:1.55rem;"><?= count($power_users) ?> Active</div>
                    <div class="nd-kpi-sub" style="color:rgba(255,255,255,0.85);">
                        Unlocked <?= number_format($total_unlocks) ?> total prompts
                    </div>
                </div>
                <a href="user_management.php" class="nd-btn-kpi-cta">
                    <i class="fa-solid fa-user-plus"></i> View All
                </a>
            </div>
        </section>

        <!-- Power Users Leaderboard -->
        <section class="nd-card">
            <div class="nd-card-head">
                <div class="nd-card-title">
                    <i class="fa-solid fa-users" style="color:#10b981;"></i>
                    <span>Active Power Users Leaderboard</span>
                </div>
            </div>

            <div class="nd-filter-bar">
                <input type="text" id="userSearchInput" class="nd-search-input" placeholder="Search users by username or email..." onkeyup="filterUsersTable()">
            </div>

            <!-- Desktop Table View -->
            <div class="nd-table-wrap nd-desktop-only">
                <table class="nd-table" id="usersDataTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Unlocks</th>
                            <th class="nd-hide-sm">Streak</th>
                            <th class="nd-hide-sm">Last Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($power_users as $u):
                            $u_av = trim((string) ($u['avatar'] ?? ''));
                            $u_pi = trim((string) ($u['profile_image'] ?? ''));
                            if ($u_av === '' || preg_match('#^https?://#i', $u_av)) {
                                if ($u_pi !== '' && !preg_match('#^https?://#i', $u_pi)) {
                                    $u_av = $u_pi;
                                }
                            }
                            $u_initial = strtoupper(substr((string) ($u['username'] ?: 'U'), 0, 1));
                        ?>
                        <tr data-name="<?= htmlspecialchars(strtolower($u['username'] . ' ' . $u['email'])) ?>">
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if ($u_av !== '' && !preg_match('#^https?://#i', $u_av)): ?>
                                    <img src="<?= htmlspecialchars($u_av) ?>" style="width:28px; height:28px; border-radius:50%; object-fit:cover;" alt="">
                                    <?php else: ?>
                                    <div style="width:28px;height:28px;border-radius:50%;background:#e2e8f0;color:#0f172a;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:800;flex-shrink:0;"><?= htmlspecialchars($u_initial) ?></div>
                                    <?php endif; ?>
                                    <div style="min-width:0;">
                                        <div style="font-weight:700; font-size:0.82rem;"><?= htmlspecialchars($u['username']) ?></div>
                                        <div class="nd-email"><?= htmlspecialchars($u['email'] ?: 'No email') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight:700; color:#059669;"><?= number_format($u['unlock_cnt']) ?></span>
                            </td>
                            <td class="nd-hide-sm">
                                <span style="font-weight:600; color:#f59e0b;"><i class="fa-solid fa-fire"></i> <?= (int)$u['streak_count'] ?>d</span>
                            </td>
                            <td class="nd-hide-sm">
                                <span style="font-size:0.74rem; color:var(--nd-text-muted);">
                                    <?= !empty($u['last_active']) ? date('d M', strtotime($u['last_active'])) : 'Recent' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Feed Cards View -->
            <div class="nd-mobile-cards-wrap nd-mobile-only" id="usersMobileCards">
                <?php foreach ($power_users as $u):
                    $u_av = trim((string) ($u['avatar'] ?? ''));
                    $u_pi = trim((string) ($u['profile_image'] ?? ''));
                    if ($u_av === '' || preg_match('#^https?://#i', $u_av)) {
                        if ($u_pi !== '' && !preg_match('#^https?://#i', $u_pi)) {
                            $u_av = $u_pi;
                        }
                    }
                    $u_initial = strtoupper(substr((string) ($u['username'] ?: 'U'), 0, 1));
                ?>
                <div class="nd-mobile-user-card" data-name="<?= htmlspecialchars(strtolower($u['username'] . ' ' . $u['email'])) ?>">
                    <div style="display:flex; align-items:center; gap:10px; min-width:0; flex:1;">
                        <?php if ($u_av !== '' && !preg_match('#^https?://#i', $u_av)): ?>
                            <img src="<?= htmlspecialchars($u_av) ?>" style="width:34px; height:34px; border-radius:50%; object-fit:cover; flex-shrink:0;" alt="">
                        <?php else: ?>
                            <div style="width:34px; height:34px; border-radius:50%; background:#e2e8f0; color:#0f172a; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:800; flex-shrink:0;"><?= htmlspecialchars($u_initial) ?></div>
                        <?php endif; ?>
                        <div style="min-width:0;">
                            <div style="font-weight:700; font-size:0.85rem; color:#0f172a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($u['username']) ?></div>
                            <div class="nd-email" style="font-size:0.72rem; color:var(--nd-text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($u['email'] ?: 'No email') ?></div>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:3px; flex-shrink:0;">
                        <span style="font-weight:800; font-size:0.82rem; color:#059669; background:#ecfdf5; border:1px solid #a7f3d0; padding:2px 8px; border-radius:999px;">
                            <?= number_format($u['unlock_cnt']) ?> Unlocks
                        </span>
                        <span style="font-size:0.68rem; font-weight:600; color:#f59e0b;">
                            <i class="fa-solid fa-fire"></i> <?= (int)$u['streak_count'] ?>d streak
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Churn Risk Users Card -->
        <section class="nd-card">
            <div class="nd-card-head">
                <div class="nd-card-title">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i>
                    <span>Churn Risk Users (Inactive 7-30 Days)</span>
                </div>
            </div>
            <!-- Desktop Table View -->
            <div class="nd-table-wrap nd-desktop-only">
                <table class="nd-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th class="nd-hide-sm">Email</th>
                            <th>Last Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($churn_users)): ?>
                            <tr><td colspan="3" style="text-align:center; color:var(--nd-text-muted);">No churn risk detected.</td></tr>
                        <?php else: ?>
                            <?php foreach ($churn_users as $cu): ?>
                            <tr>
                                <td style="font-weight:700;"><?= htmlspecialchars($cu['username']) ?></td>
                                <td class="nd-hide-sm" style="color:var(--nd-text-muted);"><?= htmlspecialchars($cu['email'] ?: '-') ?></td>
                                <td style="color:#ef4444; font-weight:600;"><?= !empty($cu['last_active']) ? date('M j, Y', strtotime($cu['last_active'])) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Mobile Card View -->
            <div class="nd-mobile-only" style="flex-direction:column; gap:8px;">
                <?php if (empty($churn_users)): ?>
                    <div style="text-align:center; color:var(--nd-text-muted); padding:16px 0; font-size:0.82rem;">No churn risk detected.</div>
                <?php else: ?>
                    <?php foreach ($churn_users as $cu): ?>
                    <div class="nd-mobile-churn-card">
                        <div style="min-width:0; flex:1;">
                            <div style="font-weight:700; font-size:0.85rem; color:#0f172a;"><?= htmlspecialchars($cu['username']) ?></div>
                            <div style="font-size:0.72rem; color:var(--nd-text-muted); margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($cu['email'] ?: 'No email') ?></div>
                        </div>
                        <div style="font-size:0.74rem; font-weight:700; color:#ef4444; text-align:right; white-space:nowrap; flex-shrink:0;">
                            <i class="fa-regular fa-clock"></i> <?= !empty($cu['last_active']) ? date('M j, Y', strtotime($cu['last_active'])) : '-' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php endif; ?>

    </main>

    <!-- ── Interactive JavaScript Charts & Live Table Filters ── -->
    <script>
    // --- 1. Activity Trends Chart (Double Bars / Smooth) ----------------------
    const trendEl = document.getElementById('trendChart');
    if (trendEl) {
        const trendCtx = trendEl.getContext('2d');
        const trendLabels = <?= json_encode($trend_users['labels']) ?>;
        const userSignups = <?= json_encode($trend_users['values']) ?>;
        const promptUnlocks = <?= json_encode($trend_unlocks['values']) ?>;

        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        label: 'User Signups',
                        data: userSignups,
                        backgroundColor: '#a78bfa',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.8
                    },
                    {
                        label: 'Prompt Unlocks',
                        data: promptUnlocks,
                        backgroundColor: '#34d399',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: '700' },
                        bodyFont: { family: 'Inter', size: 12 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 10 }, color: '#94a3b8' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 10 }, color: '#94a3b8', precision: 0 }
                    }
                }
            }
        });
    }

    // --- 2. Category Doughnut Chart ------------------------------------------
    const catEl = document.getElementById('categoryDoughnut');
    if (catEl) {
        const catCtx = catEl.getContext('2d');
        const typeLabels = <?= json_encode(array_column($type_breakdown, 'ptype')) ?>;
        const typeData = <?= json_encode(array_column($type_breakdown, 'cnt')) ?>;
        const chartColors = ['#a78bfa', '#38bdf8', '#34d399', '#fbbf24', '#f472b6', '#cbd5e1'];

        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeData,
                    backgroundColor: chartColors.slice(0, typeLabels.length),
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 8,
                        cornerRadius: 8,
                        bodyFont: { family: 'Plus Jakarta Sans', size: 11 }
                    }
                }
            }
        });
    }

    // --- 3. Live Client-Side Filtering for Prompts Table & Cards -----------
    function filterPromptsTable() {
        const query = (document.getElementById('promptSearchInput')?.value || '').toLowerCase().trim();
        const typeFilter = (document.getElementById('promptTypeFilter')?.value || '').toLowerCase().trim();
        
        // Filter desktop table rows
        const rows = document.querySelectorAll('#promptsDataTable tbody tr');
        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            const type = row.getAttribute('data-type') || '';
            const matchesQuery = query === '' || title.includes(query);
            const matchesType = typeFilter === '' || type === typeFilter;
            row.style.display = (matchesQuery && matchesType) ? '' : 'none';
        });

        // Filter mobile feed cards
        const cards = document.querySelectorAll('#promptsMobileCards .nd-mobile-prompt-card');
        cards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            const type = card.getAttribute('data-type') || '';
            const matchesQuery = query === '' || title.includes(query);
            const matchesType = typeFilter === '' || type === typeFilter;
            card.style.display = (matchesQuery && matchesType) ? '' : 'none';
        });
    }

    function sortPromptsTable() {
        const sortBy = document.getElementById('promptSortFilter')?.value || 'unlocks';
        
        // Sort desktop table
        const tbody = document.querySelector('#promptsDataTable tbody');
        if (tbody) {
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                let valA = parseInt(a.getAttribute('data-' + sortBy) || 0, 10);
                let valB = parseInt(b.getAttribute('data-' + sortBy) || 0, 10);
                return valB - valA;
            });
            rows.forEach(row => tbody.appendChild(row));
        }

        // Sort mobile feed cards
        const cardsWrap = document.getElementById('promptsMobileCards');
        if (cardsWrap) {
            const cards = Array.from(cardsWrap.querySelectorAll('.nd-mobile-prompt-card'));
            cards.sort((a, b) => {
                let valA = parseInt(a.getAttribute('data-' + sortBy) || 0, 10);
                let valB = parseInt(b.getAttribute('data-' + sortBy) || 0, 10);
                return valB - valA;
            });
            cards.forEach(card => cardsWrap.appendChild(card));
        }
    }

    // --- 4. Live Filtering for Blogs Table & Cards ---------------------------
    function filterBlogsTable() {
        const query = (document.getElementById('blogSearchInput')?.value || '').toLowerCase().trim();
        
        // Desktop table rows
        const rows = document.querySelectorAll('#blogsDataTable tbody tr');
        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            row.style.display = (query === '' || title.includes(query)) ? '' : 'none';
        });

        // Mobile cards
        const cards = document.querySelectorAll('#blogsMobileCards .nd-mobile-blog-card');
        cards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            card.style.display = (query === '' || title.includes(query)) ? '' : 'none';
        });
    }

    // --- 5. Live Filtering for Users Table & Cards ---------------------------
    function filterUsersTable() {
        const query = (document.getElementById('userSearchInput')?.value || '').toLowerCase().trim();
        
        // Desktop table rows
        const rows = document.querySelectorAll('#usersDataTable tbody tr');
        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            row.style.display = (query === '' || name.includes(query)) ? '' : 'none';
        });

        // Mobile cards
        const cards = document.querySelectorAll('#usersMobileCards .nd-mobile-user-card');
        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            card.style.display = (query === '' || name.includes(query)) ? '' : 'none';
        });
    }

    // --- 6. Live Filtering for SEO Table -----------------------------------
    function filterSeoTable() {
        const query = (document.getElementById('seoSearchInput')?.value || '').toLowerCase().trim();
        const status = document.getElementById('seoStatusFilter')?.value || '';
        const bwi = document.getElementById('seoBwiFilter')?.value || '';
        const type = document.getElementById('seoTypeFilter')?.value || '';
        const rows = document.querySelectorAll('#seoDataTable tbody tr');

        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            const rowStatus = row.getAttribute('data-status') || '';
            const rowBwi = row.getAttribute('data-bwi') || '';
            const rowType = row.getAttribute('data-type') || '';

            const matchesQuery = (query === '' || title.includes(query));
            const matchesStatus = (status === '' || rowStatus === status);
            const matchesBwi = (bwi === '' || rowBwi === bwi);
            const matchesType = (type === '' || rowType === type);

            row.style.display = (matchesQuery && matchesStatus && matchesBwi && matchesType) ? '' : 'none';
        });
    }

    // --- 7. Live Sorting for SEO Table -------------------------------------
    function sortSeoTable(sortBy) {
        const tbody = document.querySelector('#seoDataTable tbody');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            if (sortBy === 'score_asc') {
                return parseInt(a.getAttribute('data-score') || 0, 10) - parseInt(b.getAttribute('data-score') || 0, 10);
            } else if (sortBy === 'score_desc') {
                return parseInt(b.getAttribute('data-score') || 0, 10) - parseInt(a.getAttribute('data-score') || 0, 10);
            } else if (sortBy === 'newest') {
                return parseInt(b.getAttribute('data-date') || 0, 10) - parseInt(a.getAttribute('data-date') || 0, 10);
            }
            return 0;
        });

        rows.forEach(row => tbody.appendChild(row));
    }

    // --- 8. Copy Prompt URL to Clipboard ----------------------------------
    function copyPromptUrl(url, btn) {
        if (!url) return;
        const fallbackCopy = (text) => {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
        };

        const onCopied = () => {
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check" style="color:#16a34a;"></i> <span style="color:#16a34a;">Copied!</span>';
            setTimeout(() => { btn.innerHTML = origHtml; }, 2000);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(onCopied).catch(() => {
                fallbackCopy(url);
                onCopied();
            });
        } else {
            fallbackCopy(url);
            onCopied();
        }
    }

    // --- 9. Mark GSC Status (Already Indexed / I Indexed Now) -------------
    async function markGscStatus(promptId, status, btn) {
        if (!promptId || !status) return;
        const cell = document.getElementById('gsc-action-cell-' + promptId);
        if (!cell) return;

        const originalBtnHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        try {
            const fd = new FormData();
            fd.append('prompt_id', promptId);
            fd.append('status', status);

            const res = await fetch('update_gsc_status.php', {
                method: 'POST',
                body: fd
            }).then(r => r.json());

            if (res.success) {
                const isAlready = (res.status === 'already_indexed');
                const badgeClass = isAlready ? 'nd-gsc-badge-already' : 'nd-gsc-badge-now';
                const badgeFull = isAlready ? 'Already Indexed' : 'Requested Indexing';
                const badgeShort = isAlready ? 'Indexed' : 'Requested';
                const dateFormatted = res.indexed_at_formatted || 'Just now';

                cell.innerHTML = `
                    <div class="nd-gsc-locked-wrap">
                        <span class="${badgeClass}">
                            <i class="${badgeIcon}"></i>
                            <span class="nd-gsc-label-full">${badgeFull}</span>
                            <span class="nd-gsc-label-short">${badgeShort}</span>
                        </span>
                        <div class="nd-gsc-timestamp">
                            <i class="fa-regular fa-calendar-check"></i> ${dateFormatted}
                        </div>
                    </div>
                `;

                // Update row data-status
                const row = document.getElementById('gsc-row-' + promptId);
                if (row) {
                    row.setAttribute('data-status', res.status);
                }

                // Dynamically update counter KPI badges if present
                const pendingEl = document.getElementById('gscPendingVal');
                const alreadyEl = document.getElementById('gscAlreadyVal');
                const nowEl = document.getElementById('gscNowVal');
                
                if (pendingEl) {
                    let pVal = parseInt(pendingEl.textContent, 10) || 0;
                    if (pVal > 0) pendingEl.innerHTML = (pVal - 1) + '<span class="nd-seo-kpi-total">/<?= (int)$seo_total_count ?></span>';
                }
                if (isAlready && alreadyEl) {
                    let aVal = parseInt(alreadyEl.textContent, 10) || 0;
                    alreadyEl.innerHTML = (aVal + 1) + '<span class="nd-seo-kpi-total">/<?= (int)$seo_total_count ?></span>';
                } else if (!isAlready && nowEl) {
                    let nVal = parseInt(nowEl.textContent, 10) || 0;
                    nowEl.innerHTML = (nVal + 1) + '<span class="nd-seo-kpi-total">/<?= (int)$seo_total_count ?></span>';
                }
            } else {
                alert(res.message || 'Could not update GSC status.');
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        } catch (e) {
            alert('Network error while saving GSC status.');
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }
    }

    // --- 10. Live Filtering for GSC Table ----------------------------------
    function filterGscTable() {
        const query = (document.getElementById('gscSearchInput')?.value || '').toLowerCase().trim();
        const status = document.getElementById('gscStatusFilter')?.value || '';
        const type = document.getElementById('gscTypeFilter')?.value || '';
        const rows = document.querySelectorAll('#gscDataTable tbody tr');

        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            const rowStatus = row.getAttribute('data-status') || '';
            const rowType = row.getAttribute('data-type') || '';

            const matchesQuery = (query === '' || title.includes(query));
            const matchesStatus = (status === '' || rowStatus === status);
            const matchesType = (type === '' || rowType === type);

            row.style.display = (matchesQuery && matchesStatus && matchesType) ? '' : 'none';
        });
    }
    </script>
<script>
(function () {
  var splash = document.getElementById('nd-splash');
  if (!splash) return;
  if (/analytics\.php/i.test(document.referrer || '')) {
    splash.remove();
    document.body.classList.remove('nd-splash-lock');
    return;
  }
  var word = document.getElementById('nd-splash-word');
  var text = 'intel';
  var i = 0;
  function type() {
    if (!word) return;
    if (i <= text.length) {
      word.textContent = text.slice(0, i);
      i += 1;
      setTimeout(type, 90);
    }
  }
  setTimeout(type, 280);
  setTimeout(function () {
    splash.classList.add('is-out');
    document.body.classList.remove('nd-splash-lock');
    setTimeout(function () { splash.remove(); }, 520);
  }, 4000);
})();
    </script>
<script>
(function () {
  function prettyLabel(text) {
    return String(text || '').replace(/_/g, ' ');
  }
  function enhanceSelect(sel) {
    if (!sel || sel.dataset.enhanced) return;
    sel.dataset.enhanced = '1';
    var wrap = document.createElement('div');
    wrap.className = 'nd-dd';
    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(sel);
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'nd-dd-btn';
    var menu = document.createElement('div');
    menu.className = 'nd-dd-menu';
    wrap.appendChild(btn);
    wrap.appendChild(menu);

    function selectedText() {
      var opt = sel.options[sel.selectedIndex];
      return prettyLabel(opt ? opt.textContent : '');
    }
    function render() {
      btn.innerHTML = '<span>' + selectedText() + '</span><i class="fa-solid fa-chevron-down"></i>';
      menu.innerHTML = '';
      Array.from(sel.options).forEach(function (opt, idx) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'nd-dd-opt' + (idx === sel.selectedIndex ? ' is-on' : '');
        var raw = prettyLabel(opt.textContent);
        var m = raw.match(/^(.*)\s+\((\d+)\)\s*$/);
        if (m) {
          item.innerHTML = '<span>' + m[1] + '</span><span class="nd-dd-count">' + m[2] + '</span>';
        } else {
          item.textContent = raw;
        }
        item.addEventListener('click', function () {
          sel.selectedIndex = idx;
          sel.dispatchEvent(new Event('change'));
          render();
          wrap.classList.remove('is-open');
        });
        menu.appendChild(item);
      });
    }
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      document.querySelectorAll('.nd-dd.is-open').forEach(function (other) {
        if (other !== wrap) other.classList.remove('is-open');
      });
      wrap.classList.toggle('is-open');
    });
    render();
  }
  document.querySelectorAll('.nd-select-filter').forEach(enhanceSelect);
  document.addEventListener('click', function () {
    document.querySelectorAll('.nd-dd.is-open').forEach(function (el) {
      el.classList.remove('is-open');
    });
  });
})();
</script>
</body>
</html>
