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
            gap: 12px;
            flex: 1;
            min-width: 0;
        }
        .nd-topbar-tools {
            display: flex;
            align-items: center;
            gap: 8px;
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
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .nd-search-input {
            flex: 1;
            min-width: 0;
            width: 100%;
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
        }

        /* Phone: sleek stacked layout — no overlap, real breathing room */
        @media (max-width: 720px) {
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
                grid-template-columns: repeat(4, minmax(0, 1fr));
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
                gap: 12px;
            }
            .nd-kpi-card { padding: 16px 14px; }
            .nd-kpi-val { font-size: 1.45rem; margin-bottom: 4px; }
            .nd-kpi-header { font-size: 0.68rem; margin-bottom: 10px; }
            .nd-kpi-sub { font-size: 0.72rem; }
            .nd-btn-kpi-cta { margin-top: 12px; padding: 8px 12px; }
            .nd-page-title { font-size: 1.32rem; }
            .nd-page-subtitle { font-size: 0.78rem; margin-top: 4px; }
            .nd-topbar {
                flex-direction: column;
                align-items: stretch;
                gap: 14px;
            }
            .nd-topbar-lead { align-items: flex-start; }
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
            .nd-card { padding: 16px 14px; }
            .nd-card-title { font-size: 0.95rem; align-items: flex-start; }
            .nd-card-head { margin-bottom: 14px; gap: 10px; }
            .nd-grid-2,
            .nd-grid-chart {
                grid-template-columns: 1fr;
                gap: 14px;
            }
            .nd-chart-box { height: 210px; }
            .nd-chart-box-sm { height: 160px; }
            .nd-filter-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                margin-bottom: 14px;
            }
            .nd-select-filter { width: 100%; }
            .nd-hide-sm { display: none !important; }
            .nd-title-clip {
                max-width: none;
                white-space: normal;
                overflow: visible;
                text-overflow: unset;
                line-height: 1.35;
            }
            .nd-table { font-size: 0.8rem; }
            .nd-table th,
            .nd-table td { padding: 12px 10px; }
            .nd-table th:first-child,
            .nd-table td:first-child {
                position: sticky;
                left: 0;
                background: #fff;
                z-index: 1;
                box-shadow: 4px 0 8px -4px rgba(15, 23, 42, .12);
            }
            .nd-table th:first-child { background: #f8fafc; z-index: 2; }
            .nd-table td > div { min-width: 0; }
            .nd-prompt-thumb { width: 32px; height: 32px; }
            .nd-action-item { padding: 12px; }
            .nd-action-desc { display: none; }
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
            <a href="analytics.php" class="nd-nav-item active" title="Dashboard">
                <i class="fa-solid fa-table-columns"></i>
                <span class="nd-nav-full">Dashboard</span>
                <span class="nd-nav-short">Home</span>
            </a>
            <a href="#section-prompts" class="nd-nav-item" title="Top Prompts">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span class="nd-nav-full">Top Prompts</span>
                <span class="nd-nav-short">Prompts</span>
            </a>
            <a href="#section-blogs" class="nd-nav-item" title="Blog Insights">
                <i class="fa-solid fa-newspaper"></i>
                <span class="nd-nav-full">Blog Insights</span>
                <span class="nd-nav-short">Blogs</span>
            </a>
            <a href="#section-users" class="nd-nav-item" title="Users & Retention">
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

        <!-- Top Bar Header -->
        <header class="nd-topbar">
            <div class="nd-topbar-lead">
                <div>
                    <h1 class="nd-page-title">Dashboard</h1>
                    <div class="nd-page-subtitle"><?= date('l, jS F Y') ?> &bull; Live Intelligence & Study Engine</div>
                </div>
                <div class="nd-topbar-tools">
                    <a href="analytics_report.php?format=csv&period=<?= $range_days <= 7 ? 'weekly' : 'monthly' ?>" class="nd-btn-lime" title="Download Complete CSV Report">
                        <i class="fa-solid fa-file-arrow-down"></i>
                        <span>CSV</span>
                    </a>
                    <a href="dashboard.php" class="nd-back-dash nd-back-dash-top" title="Back to old dashboard">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Old Dashboard</span>
                    </a>
                </div>
            </div>
            <div class="nd-pill-group">
                <a href="analytics.php?range=7" class="nd-pill-btn <?= $range_days === 7 ? 'active' : '' ?>"><span class="nd-pill-full">7 Days</span><span class="nd-pill-short">7D</span></a>
                <a href="analytics.php?range=14" class="nd-pill-btn <?= $range_days === 14 ? 'active' : '' ?>"><span class="nd-pill-full">14 Days</span><span class="nd-pill-short">14D</span></a>
                <a href="analytics.php?range=30" class="nd-pill-btn <?= $range_days === 30 ? 'active' : '' ?>"><span class="nd-pill-full">30 Days</span><span class="nd-pill-short">30D</span></a>
                <a href="analytics.php?range=90" class="nd-pill-btn <?= $range_days === 90 ? 'active' : '' ?>"><span class="nd-pill-full">90 Days</span><span class="nd-pill-short">90D</span></a>
                <a href="analytics.php?range=365" class="nd-pill-btn <?= $range_days === 365 ? 'active' : '' ?>"><span class="nd-pill-full">Year</span><span class="nd-pill-short">1Y</span></a>
            </div>
        </header>

        <!-- ── 4 Top KPI Highlights Cards ── -->
        <section class="nd-kpi-grid">
            
            <!-- Card 1: Total Prompts (Soft Lavender) -->
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

            <!-- Card 2: Prompt Unlocks (Soft Baby Blue) -->
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

            <!-- Card 3: Conversion & Users (Soft Mint Green) -->
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

            <!-- Card 4: Platform Health Pro Card (Emerald Teal Gradient) -->
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

        <!-- ── Middle Section: Main Chart & Action Breakdowns ── -->
        <section class="nd-grid-chart">
            
            <!-- Left Chart Card: Activity Trends -->
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
                        <a href="analytics_report.php?format=csv&period=<?= $range_days <= 7 ? 'weekly' : 'monthly' ?>" class="nd-btn-outline" style="padding:4px 10px; font-size:0.74rem;">Export</a>
                    </div>
                </div>

                <div class="nd-chart-box">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Right Quick Breakdown Card -->
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

        <!-- ── Section: Comprehensive Top Prompts Table with Live Filter & Search ── -->
        <section class="nd-card" id="section-prompts">
            <div class="nd-card-head">
                <div>
                    <div class="nd-card-title">
                        <i class="fa-solid fa-wand-magic-sparkles" style="color:#3b82f6;"></i>
                        <span>Prompts Performance & Intelligence</span>
                    </div>
                    <div style="font-size:0.75rem; color:var(--nd-text-muted); margin-top:2px;">
                        Interactive table with live search, category filters, and performance metrics
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <a href="upload_prompt.php" class="nd-btn-lime">
                        <i class="fa-solid fa-plus"></i> New Prompt
                    </a>
                </div>
            </div>

            <!-- Table Filter Toolbar -->
            <div class="nd-filter-bar">
                <input type="text" id="promptSearchInput" class="nd-search-input" placeholder="🔍 Search prompts by title or keyword..." onkeyup="filterPromptsTable()">
                
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

            <!-- Table -->
            <div class="nd-table-wrap">
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
                                    <img src="<?= htmlspecialchars($thumb) ?>" class="nd-prompt-thumb" alt="" onerror="this.src='toplogo/logo01.webp'">
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
        </section>

        <!-- ── Section: Blog Insights & Power Users (2-Columns) ── -->
        <section class="nd-grid-2" id="section-blogs">
            
            <!-- Blog Performance Table -->
            <div class="nd-card">
                <div class="nd-card-head">
                    <div class="nd-card-title">
                        <i class="fa-solid fa-newspaper" style="color:#8b5cf6;"></i>
                        <span>Blog Reads & Views</span>
                    </div>
                    <a href="blog_create.php" class="nd-btn-outline" style="padding:4px 10px; font-size:0.74rem;">
                        <i class="fa-solid fa-plus"></i> Write Post
                    </a>
                </div>

                <div class="nd-filter-bar">
                    <input type="text" id="blogSearchInput" class="nd-search-input" placeholder="🔍 Filter blogs by title..." onkeyup="filterBlogsTable()">
                </div>

                <div class="nd-table-wrap">
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
                                        <i class="fa-solid fa-pen"></i>
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
            </div>

            <!-- Power Users & Retention Leaderboard -->
            <div class="nd-card" id="section-users">
                <div class="nd-card-head">
                    <div class="nd-card-title">
                        <i class="fa-solid fa-users" style="color:#10b981;"></i>
                        <span>Active Power Users</span>
                    </div>
                    <a href="user_management.php" class="nd-btn-outline" style="padding:4px 10px; font-size:0.74rem;">
                        View All Users
                    </a>
                </div>

                <div class="nd-filter-bar">
                    <input type="text" id="userSearchInput" class="nd-search-input" placeholder="🔍 Search users by username or email..." onkeyup="filterUsersTable()">
                </div>

                <div class="nd-table-wrap">
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
            </div>

        </section>

        <!-- ── Section: Optimization Hub (Dead Prompts & Churn Risk) ── -->
        <section class="nd-grid-2">
            
            <!-- Churn Risk Users -->
            <div class="nd-card">
                <div class="nd-card-head">
                    <div class="nd-card-title">
                        <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i>
                        <span>Churn Risk Users (Inactive 7-30 Days)</span>
                    </div>
                </div>
                <div class="nd-table-wrap">
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
                                <tr><td colspan="3" style="text-align:center; color:var(--nd-text-muted);">No churn risk detected! 🎉</td></tr>
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
            </div>

            <!-- Zero-Unlock Prompts to Optimize -->
            <div class="nd-card">
                <div class="nd-card-head">
                    <div class="nd-card-title">
                        <i class="fa-solid fa-bolt" style="color:#f59e0b;"></i>
                        <span>Zero Unlocks (Optimization Queue)</span>
                    </div>
                </div>
                <div class="nd-table-wrap">
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
                                <tr><td colspan="4" style="text-align:center; color:var(--nd-text-muted);">All prompts active! 🚀</td></tr>
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
            </div>

        </section>

    </main>

    <!-- ── Interactive JavaScript Charts & Live Table Filters ── -->
    <script>
    // --- 1. Activity Trends Chart (Double Bars / Smooth) ----------------------
    const trendCtx = document.getElementById('trendChart').getContext('2d');
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

    // --- 2. Category Doughnut Chart ------------------------------------------
    const catCtx = document.getElementById('categoryDoughnut').getContext('2d');
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

    // --- 3. Live Client-Side Filtering for Prompts Table --------------------
    function filterPromptsTable() {
        const query = document.getElementById('promptSearchInput').value.toLowerCase().trim();
        const typeFilter = document.getElementById('promptTypeFilter').value.toLowerCase().trim();
        const rows = document.querySelectorAll('#promptsDataTable tbody tr');

        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            const type = row.getAttribute('data-type') || '';
            
            const matchesQuery = query === '' || title.includes(query);
            const matchesType = typeFilter === '' || type === typeFilter;

            if (matchesQuery && matchesType) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function sortPromptsTable() {
        const sortBy = document.getElementById('promptSortFilter').value;
        const tbody = document.querySelector('#promptsDataTable tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            let valA = parseInt(a.getAttribute('data-' + sortBy) || 0, 10);
            let valB = parseInt(b.getAttribute('data-' + sortBy) || 0, 10);
            return valB - valA;
        });

        rows.forEach(row => tbody.appendChild(row));
    }

    // --- 4. Live Filtering for Blogs Table -----------------------------------
    function filterBlogsTable() {
        const query = document.getElementById('blogSearchInput').value.toLowerCase().trim();
        const rows = document.querySelectorAll('#blogsDataTable tbody tr');
        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            row.style.display = (query === '' || title.includes(query)) ? '' : 'none';
        });
    }

    // --- 5. Live Filtering for Users Table -----------------------------------
    function filterUsersTable() {
        const query = document.getElementById('userSearchInput').value.toLowerCase().trim();
        const rows = document.querySelectorAll('#usersDataTable tbody tr');
        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            row.style.display = (query === '' || name.includes(query)) ? '' : 'none';
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
