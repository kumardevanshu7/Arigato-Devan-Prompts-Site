<?php
/**
 * analytics_report.php — Downloadable weekly / monthly analytics report.
 *
 * ?period=weekly|monthly   window length (7 or 30 days)
 * ?format=csv|print        csv download, or a print-friendly page (Ctrl+P -> Save as PDF)
 *
 * Period windows use the same DATE_SUB(NOW(), INTERVAL n DAY) form as
 * analytics.php so the numbers in the report always match the dashboard.
 */
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

$period = (($_GET['period'] ?? 'weekly') === 'monthly') ? 'monthly' : 'weekly';
$format = in_array(strtolower($_GET['format'] ?? 'print'), ['excel', 'xls', 'csv'], true) ? 'excel' : 'print';
$days   = ($period === 'monthly') ? 30 : 7;   // whitelisted int, safe to interpolate
$prev   = $days * 2;

$period_label = ($period === 'monthly') ? 'Monthly' : 'Weekly';
$range_from   = date('Y-m-d', strtotime("-" . ($days - 1) . " days"));
$range_to     = date('Y-m-d');

function rAll(PDO $pdo, string $sql): array {
    try { return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
    catch (Exception $e) { return []; }
}
function rOne(PDO $pdo, string $sql, $default = 0) {
    try { $v = $pdo->query($sql)->fetchColumn(); return ($v !== false && $v !== null) ? $v : $default; }
    catch (Exception $e) { return $default; }
}

/** Daily counts keyed by Y-m-d for one of the whitelisted tables. */
function dailyCounts(PDO $pdo, string $table, int $days): array {
    if (!in_array($table, ['users', 'prompts', 'unlocked_prompts', 'saved_prompts'], true)) return [];
    $rows = rAll($pdo, "SELECT DATE(created_at) d, COUNT(*) c FROM {$table}
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                        GROUP BY DATE(created_at)");
    $map = [];
    foreach ($rows as $r) $map[$r['d']] = (int)$r['c'];
    return $map;
}

/** Percentage change, or null when there is no baseline to compare against. */
function pctChange(int $now, int $before): ?float {
    if ($before === 0) return null;
    return round((($now - $before) * 100) / $before, 1);
}
function fmtChange(?float $pct): string {
    if ($pct === null) return 'n/a';
    return ($pct > 0 ? '+' : '') . $pct . '%';
}

// ---------- Period metrics (current vs previous window) ----------
$metrics = [];
$metric_defs = [
    'New signups'         => 'users',
    'New prompts'         => 'prompts',
    'Prompt unlocks'      => 'unlocked_prompts',
    'Prompts saved'       => 'saved_prompts',
];
foreach ($metric_defs as $label => $table) {
    $now_v = (int)rOne($pdo, "SELECT COUNT(*) FROM {$table}
                              WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)");
    $prev_v = (int)rOne($pdo, "SELECT COUNT(*) FROM {$table}
                               WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$prev} DAY)
                                 AND created_at <  DATE_SUB(NOW(), INTERVAL {$days} DAY)");
    $metrics[$label] = ['now' => $now_v, 'prev' => $prev_v, 'pct' => pctChange($now_v, $prev_v)];
}

// Unique users who unlocked at least one prompt in the window.
$active_now = (int)rOne($pdo, "SELECT COUNT(DISTINCT user_id) FROM unlocked_prompts
                               WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                                 AND user_id IS NOT NULL");
$active_prev = (int)rOne($pdo, "SELECT COUNT(DISTINCT user_id) FROM unlocked_prompts
                                WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$prev} DAY)
                                  AND created_at <  DATE_SUB(NOW(), INTERVAL {$days} DAY)
                                  AND user_id IS NOT NULL");
$metrics['Active users (unlocked something)'] = [
    'now' => $active_now, 'prev' => $active_prev, 'pct' => pctChange($active_now, $active_prev),
];

// Returning users seen in the window.
$returning = (int)rOne($pdo, "SELECT COUNT(*) FROM users
                              WHERE created_at < DATE_SUB(NOW(), INTERVAL {$days} DAY)
                                AND last_active >= DATE_SUB(NOW(), INTERVAL {$days} DAY)");

// ---------- Daily breakdown ----------
$d_users   = dailyCounts($pdo, 'users', $days);
$d_prompts = dailyCounts($pdo, 'prompts', $days);
$d_unlocks = dailyCounts($pdo, 'unlocked_prompts', $days);
$d_saves   = dailyCounts($pdo, 'saved_prompts', $days);

$daily = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $daily[] = [
        'date'    => $d,
        'label'   => date('D, d M', strtotime($d)),
        'users'   => $d_users[$d]   ?? 0,
        'prompts' => $d_prompts[$d] ?? 0,
        'unlocks' => $d_unlocks[$d] ?? 0,
        'saves'   => $d_saves[$d]   ?? 0,
    ];
}
$best_day = null;
foreach ($daily as $row) {
    if ($best_day === null || $row['unlocks'] > $best_day['unlocks']) $best_day = $row;
}

// ---------- Tables ----------
$top_unlocked = rAll($pdo, "SELECT p.title, COUNT(up.id) c
                            FROM unlocked_prompts up
                            JOIN prompts p ON p.id = up.prompt_id
                            WHERE up.created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                            GROUP BY p.id, p.title
                            ORDER BY c DESC LIMIT 15");

$new_prompts = rAll($pdo, "SELECT title, prompt_type, created_at, COALESCE(likes_count,0) likes
                           FROM prompts
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                           ORDER BY created_at DESC");

$active_users = rAll($pdo, "SELECT u.username, u.email, COUNT(up.id) c
                            FROM unlocked_prompts up
                            JOIN users u ON u.id = up.user_id
                            WHERE up.created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                            GROUP BY u.id, u.username, u.email
                            ORDER BY c DESC LIMIT 15");

$type_split = rAll($pdo, "SELECT prompt_type, COUNT(*) cnt
                          FROM prompts
                          WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                          GROUP BY prompt_type ORDER BY cnt DESC");

// ---------- All-time snapshot ----------
// likes / views / copies / shares are running counters with no timestamp, so they
// can only be reported as lifetime totals — never as a per-period figure.
$snapshot = [
    'Total prompts'      => (int)rOne($pdo, "SELECT COUNT(*) FROM prompts"),
    'Total users'        => (int)rOne($pdo, "SELECT COUNT(*) FROM users"),
    'Total unlocks'      => (int)rOne($pdo, "SELECT COUNT(*) FROM unlocked_prompts"),
    'Total saves'        => (int)rOne($pdo, "SELECT COUNT(*) FROM saved_prompts"),
    'Total likes'        => (int)rOne($pdo, "SELECT COALESCE(SUM(likes_count),0) FROM prompts"),
    'Total views'        => (int)rOne($pdo, "SELECT COALESCE(SUM(view_count),0) FROM prompts"),
    'Total copies'       => (int)rOne($pdo, "SELECT COALESCE(SUM(copy_count),0) FROM prompts"),
    'Total shares'       => (int)rOne($pdo, "SELECT COALESCE(SUM(share_count),0) FROM prompts"),
];

$generated = date('d M Y, g:i A');
$filename  = 'arigato-' . $period . '-report-' . date('Y-m-d');

// =====================================================================
// RICH FORMATTED EXCEL / CSV EXPORT
// =====================================================================
if ($format === 'excel' || $format === 'csv') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' . "\n";
    echo '<head>' . "\n";
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . "\n";
    echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>' . htmlspecialchars($period_label) . ' Analytics</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->' . "\n";
    echo '<style>' . "\n";
    echo 'body, table, td, th { font-family: Calibri, "Segoe UI", Arial, sans-serif; font-size: 10pt; color: #1e293b; }' . "\n";
    echo '.title-banner { background-color: #6d28d9; color: #ffffff; font-size: 14pt; font-weight: bold; text-align: left; height: 36pt; vertical-align: middle; padding: 10px; }' . "\n";
    echo '.meta-cell { background-color: #f5f3ff; color: #5b21b6; font-size: 9.5pt; height: 20pt; vertical-align: middle; padding: 6px; }' . "\n";
    echo '.section-hdr { background-color: #0f172a; color: #ffffff; font-size: 11pt; font-weight: bold; height: 26pt; vertical-align: middle; padding: 6px 10px; }' . "\n";
    echo '.tbl-th { background-color: #f1f5f9; color: #334155; font-size: 9.5pt; font-weight: bold; border: 0.5pt solid #cbd5e1; height: 22pt; vertical-align: middle; text-align: left; padding: 6px 10px; }' . "\n";
    echo '.tbl-th-num { background-color: #f1f5f9; color: #334155; font-size: 9.5pt; font-weight: bold; border: 0.5pt solid #cbd5e1; height: 22pt; vertical-align: middle; text-align: right; padding: 6px 10px; }' . "\n";
    echo '.tbl-td { border: 0.5pt solid #e2e8f0; height: 20pt; vertical-align: middle; padding: 6px 10px; mso-number-format: "\@"; }' . "\n";
    echo '.tbl-td-num { border: 0.5pt solid #e2e8f0; height: 20pt; vertical-align: middle; text-align: right; padding: 6px 10px; mso-number-format: "#,##0"; }' . "\n";
    echo '.tbl-td-date { border: 0.5pt solid #e2e8f0; height: 20pt; vertical-align: middle; text-align: left; padding: 6px 10px; mso-number-format: "\@"; font-weight: 600; color: #334155; }' . "\n";
    echo '.zebra { background-color: #f8fafc; }' . "\n";
    echo '.up { color: #15803d; font-weight: bold; }' . "\n";
    echo '.down { color: #b91c1c; font-weight: bold; }' . "\n";
    echo '.flat { color: #64748b; font-weight: 600; }' . "\n";
    echo '.foot-note { font-size: 8.5pt; color: #64748b; font-style: italic; }' . "\n";
    echo '</style>' . "\n";
    echo '</head>' . "\n";
    echo '<body>' . "\n";
    echo '<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:100%;">' . "\n";
    
    // Column widths to prevent Excel ### overflows
    echo '<colgroup>' . "\n";
    echo '<col style="width: 220pt;">' . "\n";
    echo '<col style="width: 120pt;">' . "\n";
    echo '<col style="width: 120pt;">' . "\n";
    echo '<col style="width: 100pt;">' . "\n";
    echo '<col style="width: 100pt;">' . "\n";
    echo '</colgroup>' . "\n";

    // Header Banner
    echo '<tr><td colspan="5" class="title-banner">ARIGATO LABS &mdash; ' . strtoupper($period_label) . ' ANALYTICS REPORT</td></tr>' . "\n";
    echo '<tr><td colspan="5" class="meta-cell">Period: Last ' . $days . ' Days (' . date('d M Y', strtotime($range_from)) . ' to ' . date('d M Y', strtotime($range_to)) . ') &nbsp;|&nbsp; Generated: ' . $generated . ' IST &nbsp;|&nbsp; Licensed from Arigato Labs</td></tr>' . "\n";
    echo '<tr><td colspan="5" style="height:12pt;"></td></tr>' . "\n";

    // 1. SUMMARY SECTION
    echo '<tr><td colspan="5" class="section-hdr">1. EXECUTIVE SUMMARY</td></tr>' . "\n";
    echo '<tr>' . "\n";
    echo '<th class="tbl-th">Metric KPI</th>' . "\n";
    echo '<th class="tbl-th-num">Current (' . $days . 'd)</th>' . "\n";
    echo '<th class="tbl-th-num">Previous (' . $days . 'd)</th>' . "\n";
    echo '<th class="tbl-th-num">Growth / Change</th>' . "\n";
    echo '<th class="tbl-th">Status</th>' . "\n";
    echo '</tr>' . "\n";

    $i = 0;
    foreach ($metrics as $label => $m) {
        $z = ($i++ % 2 === 1) ? ' zebra' : '';
        $cls = 'flat';
        $status_txt = 'Neutral';
        if ($m['pct'] !== null && $m['pct'] > 0) { $cls = 'up'; $status_txt = 'Growth'; }
        elseif ($m['pct'] !== null && $m['pct'] < 0) { $cls = 'down'; $status_txt = 'Decline'; }

        echo '<tr>' . "\n";
        echo '<td class="tbl-td' . $z . '" style="font-weight:600;">' . htmlspecialchars($label) . '</td>' . "\n";
        echo '<td class="tbl-td-num' . $z . '">' . number_format($m['now']) . '</td>' . "\n";
        echo '<td class="tbl-td-num' . $z . '">' . number_format($m['prev']) . '</td>' . "\n";
        echo '<td class="tbl-td-num' . $z . ' ' . $cls . '">' . htmlspecialchars(fmtChange($m['pct'])) . '</td>' . "\n";
        echo '<td class="tbl-td' . $z . ' ' . $cls . '">' . $status_txt . '</td>' . "\n";
        echo '</tr>' . "\n";
    }
    echo '<tr>' . "\n";
    echo '<td class="tbl-td" style="font-weight:600;">Returning users active in period</td>' . "\n";
    echo '<td class="tbl-td-num">' . number_format($returning) . '</td>' . "\n";
    echo '<td class="tbl-td-num" style="color:#94a3b8;">&mdash;</td>' . "\n";
    echo '<td class="tbl-td-num" style="color:#94a3b8;">&mdash;</td>' . "\n";
    echo '<td class="tbl-td" style="color:#64748b;">Active Users</td>' . "\n";
    echo '</tr>' . "\n";
    if ($best_day && $best_day['unlocks'] > 0) {
        echo '<tr>' . "\n";
        echo '<td class="tbl-td zebra" style="font-weight:600;">Peak Performance Day</td>' . "\n";
        echo '<td colspan="4" class="tbl-td zebra" style="font-weight:700; color:#5b21b6;">' . htmlspecialchars($best_day['label']) . ' (' . number_format($best_day['unlocks']) . ' prompt unlocks)</td>' . "\n";
        echo '</tr>' . "\n";
    }
    echo '<tr><td colspan="5" style="height:14pt;"></td></tr>' . "\n";

    // 2. DAILY BREAKDOWN
    echo '<tr><td colspan="5" class="section-hdr">2. DAILY ACTIVITY BREAKDOWN</td></tr>' . "\n";
    echo '<tr>' . "\n";
    echo '<th class="tbl-th">Date</th>' . "\n";
    echo '<th class="tbl-th-num">New Signups</th>' . "\n";
    echo '<th class="tbl-th-num">New Prompts</th>' . "\n";
    echo '<th class="tbl-th-num">Prompt Unlocks</th>' . "\n";
    echo '<th class="tbl-th-num">Bookmarks / Saves</th>' . "\n";
    echo '</tr>' . "\n";

    $i = 0;
    foreach ($daily as $r) {
        $z = ($i++ % 2 === 1) ? ' zebra' : '';
        echo '<tr>' . "\n";
        echo '<td class="tbl-td-date' . $z . '">' . htmlspecialchars($r['label']) . ' (' . htmlspecialchars($r['date']) . ')</td>' . "\n";
        echo '<td class="tbl-td-num' . $z . '">' . number_format($r['users']) . '</td>' . "\n";
        echo '<td class="tbl-td-num' . $z . '">' . number_format($r['prompts']) . '</td>' . "\n";
        echo '<td class="tbl-td-num' . $z . '" style="font-weight:700; color:#047857;">' . number_format($r['unlocks']) . '</td>' . "\n";
        echo '<td class="tbl-td-num' . $z . '">' . number_format($r['saves']) . '</td>' . "\n";
        echo '</tr>' . "\n";
    }
    echo '<tr><td colspan="5" style="height:14pt;"></td></tr>' . "\n";

    // 3. TOP PROMPTS
    echo '<tr><td colspan="5" class="section-hdr">3. TOP PERFORMING PROMPTS BY UNLOCKS</td></tr>' . "\n";
    echo '<tr>' . "\n";
    echo '<th class="tbl-th" style="width:50pt;">Rank</th>' . "\n";
    echo '<th colspan="3" class="tbl-th">Prompt Title</th>' . "\n";
    echo '<th class="tbl-th-num">Unlocks</th>' . "\n";
    echo '</tr>' . "\n";

    if (empty($top_unlocked)) {
        echo '<tr><td colspan="5" class="tbl-td" style="color:#64748b; font-style:italic;">No prompt unlocks recorded in this period.</td></tr>' . "\n";
    } else {
        foreach ($top_unlocked as $idx => $r) {
            $z = ($idx % 2 === 1) ? ' zebra' : '';
            echo '<tr>' . "\n";
            echo '<td class="tbl-td' . $z . '" style="font-weight:700; color:#64748b; text-align:center;">#' . ($idx + 1) . '</td>' . "\n";
            echo '<td colspan="3" class="tbl-td' . $z . '" style="font-weight:600;">' . htmlspecialchars($r['title']) . '</td>' . "\n";
            echo '<td class="tbl-td-num' . $z . '" style="font-weight:700; color:#047857;">' . number_format($r['c']) . '</td>' . "\n";
            echo '</tr>' . "\n";
        }
    }
    echo '<tr><td colspan="5" style="height:14pt;"></td></tr>' . "\n";

    // 4. NEW PROMPTS PUBLISHED
    echo '<tr><td colspan="5" class="section-hdr">4. NEW PROMPTS PUBLISHED IN PERIOD</td></tr>' . "\n";
    echo '<tr>' . "\n";
    echo '<th colspan="2" class="tbl-th">Prompt Title</th>' . "\n";
    echo '<th class="tbl-th">Category / Type</th>' . "\n";
    echo '<th class="tbl-th-num">Publish Date</th>' . "\n";
    echo '<th class="tbl-th-num">Likes Count</th>' . "\n";
    echo '</tr>' . "\n";

    if (empty($new_prompts)) {
        echo '<tr><td colspan="5" class="tbl-td" style="color:#64748b; font-style:italic;">No new prompts were published in this period.</td></tr>' . "\n";
    } else {
        foreach ($new_prompts as $idx => $r) {
            $z = ($idx % 2 === 1) ? ' zebra' : '';
            echo '<tr>' . "\n";
            echo '<td colspan="2" class="tbl-td' . $z . '">' . htmlspecialchars($r['title']) . '</td>' . "\n";
            echo '<td class="tbl-td' . $z . '"><span style="font-weight:600;">' . htmlspecialchars($r['prompt_type']) . '</span></td>' . "\n";
            echo '<td class="tbl-td-date' . $z . '">' . date('d M Y', strtotime($r['created_at'])) . '</td>' . "\n";
            echo '<td class="tbl-td-num' . $z . '" style="color:#e11d48; font-weight:700;">' . number_format($r['likes']) . '</td>' . "\n";
            echo '</tr>' . "\n";
        }
    }
    echo '<tr><td colspan="5" style="height:14pt;"></td></tr>' . "\n";

    // 5. ALL-TIME SNAPSHOT
    echo '<tr><td colspan="5" class="section-hdr">5. ALL-TIME LIFETIME PLATFORM TOTALS</td></tr>' . "\n";
    echo '<tr>' . "\n";
    echo '<th colspan="3" class="tbl-th">All-Time Platform Metric</th>' . "\n";
    echo '<th colspan="2" class="tbl-th-num">Lifetime Count</th>' . "\n";
    echo '</tr>' . "\n";

    $i = 0;
    foreach ($snapshot as $label => $val) {
        $z = ($i++ % 2 === 1) ? ' zebra' : '';
        echo '<tr>' . "\n";
        echo '<td colspan="3" class="tbl-td' . $z . '" style="font-weight:600;">' . htmlspecialchars($label) . '</td>' . "\n";
        echo '<td colspan="2" class="tbl-td-num' . $z . '" style="font-weight:700; color:#5b21b6;">' . number_format($val) . '</td>' . "\n";
        echo '</tr>' . "\n";
    }
    echo '<tr><td colspan="5" class="foot-note" style="padding-top:6px;">* All-time snapshot metrics represent continuous platform cumulative counters.</td></tr>' . "\n";
    echo '<tr><td colspan="5" style="height:14pt;"></td></tr>' . "\n";

    // Footer signature
    echo '<tr><td colspan="5" style="background:#f8fafc; border:0.5pt solid #cbd5e1; padding:8px 12px; font-size:8.5pt; color:#475569; text-align:center;">Licensed PDF &amp; Spreadsheet Report &bull; Arigato Labs &bull; Official Platform Analytics &bull; arigatodevan.com</td></tr>' . "\n";

    echo '</table>' . "\n";
    echo '</body>' . "\n";
    echo '</html>' . "\n";
    exit();
}

// =====================================================================
// PRINT / PDF
// =====================================================================
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($period_label) ?> Analytics Report — Licensed from Arigato Labs</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
<link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  *{box-sizing:border-box}
  body{margin:0;padding:34px 30px 60px;background:#f4f5f7;color:#14161c;
       font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif}
  .sheet{max-width:900px;margin:0 auto;background:#fff;border:1px solid #e2e5ea;
         border-radius:12px;padding:38px 40px 44px}

  .bar{display:flex;gap:10px;justify-content:flex-end;max-width:900px;margin:0 auto 16px}
  .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:9px;
       font-size:.82rem;font-weight:700;text-decoration:none;cursor:pointer;border:1px solid #d3d7de;
       background:#fff;color:#2b2f3a}
  .btn:hover{background:#f0f2f5}
  .btn-primary{background:#6d28d9;border-color:#6d28d9;color:#fff}
  .btn-primary:hover{background:#5b21b6}

  .rep-head{border-bottom:2px solid #14161c;padding-bottom:18px;margin-bottom:26px}
  .brand{font-size:.7rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#6d28d9}
  .rep-title{font-size:1.85rem;font-weight:800;margin:8px 0 6px;letter-spacing:-.02em}
  .rep-meta{font-size:.82rem;color:#5b616e;line-height:1.7}

  h2{font-size:.72rem;font-weight:800;letter-spacing:.13em;text-transform:uppercase;
     color:#6d28d9;margin:34px 0 12px;padding-bottom:7px;border-bottom:1px solid #e2e5ea}

  .kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
  .kpi{border:1px solid #e2e5ea;border-radius:10px;padding:14px 16px;background:#fafbfc}
  .kpi-lbl{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6b7280}
  .kpi-val{font-size:1.6rem;font-weight:800;margin:5px 0 3px;letter-spacing:-.02em}
  .kpi-chg{font-size:.73rem;font-weight:700}
  .up{color:#047857}.down{color:#b91c1c}.flat{color:#6b7280}

  table{width:100%;border-collapse:collapse;font-size:.8rem;margin-top:4px}
  th{text-align:left;font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;
     color:#4b5563;background:#f5f6f8;padding:8px 11px;border-bottom:1px solid #dfe3e8}
  td{padding:8px 11px;border-bottom:1px solid #eef0f3;color:#2b2f3a}
  tr:last-child td{border-bottom:none}
  td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}
  .rank{color:#9ca3af;font-weight:700;width:34px}
  .empty{color:#6b7280;font-style:italic;padding:12px 11px;font-size:.8rem}
  .note{font-size:.74rem;color:#6b7280;line-height:1.65;margin-top:9px}
  .foot{margin-top:38px;padding-top:16px;border-top:1px solid #e2e5ea;
        font-size:.72rem;color:#8a909c;text-align:center}

  @media print{
    body{background:#fff;padding:0}
    .sheet{border:none;border-radius:0;padding:0;max-width:none}
    .bar{display:none}
    h2{break-after:avoid}
    table{break-inside:auto}
    tr{break-inside:avoid}
    .kpi{break-inside:avoid}
  }
  @page{margin:16mm}
  @media (max-width:640px){
    body{padding:16px 12px 40px}
    .sheet{padding:22px 18px 28px}
    .kpis{grid-template-columns:1fr 1fr}
    .rep-title{font-size:1.4rem}
  }
</style>
</head>
<body>

<div class="bar">
  <a href="analytics.php" class="btn">&larr; Back to Analytics</a>
  <a href="analytics_report.php?period=<?= h($period) ?>&amp;format=excel" class="btn" style="background:#f0fdf4; border-color:#86efac; color:#15803d; font-weight:700;"><i class="fa-solid fa-file-excel"></i> Download Excel Sheet</a>
  <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save as PDF</button>
</div>

<div class="sheet">

  <div class="rep-head">
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:8px;">
      <img src="favicon/apple-touch-icon.png" alt="Arigato Devan Logo" style="width:40px; height:40px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); flex-shrink:0;">
      <div>
        <div class="brand">Licensed PDF Report &middot; Arigato Labs</div>
        <div class="rep-title" style="margin:2px 0 0;"><?= h($period_label) ?> Analytics Report</div>
      </div>
    </div>
    <div class="rep-meta" style="margin-top:8px;">
      <?= h(date('d M Y', strtotime($range_from))) ?> &ndash; <?= h(date('d M Y', strtotime($range_to))) ?>
      &nbsp;&middot;&nbsp; last <?= (int)$days ?> days<br>
      Generated <?= h($generated) ?> IST
    </div>
  </div>

  <h2>Summary</h2>
  <div class="kpis">
    <?php foreach ($metrics as $label => $m):
      $cls = 'flat';
      if ($m['pct'] !== null && $m['pct'] > 0) $cls = 'up';
      elseif ($m['pct'] !== null && $m['pct'] < 0) $cls = 'down';
    ?>
    <div class="kpi">
      <div class="kpi-lbl"><?= h($label) ?></div>
      <div class="kpi-val"><?= number_format($m['now']) ?></div>
      <div class="kpi-chg <?= $cls ?>">
        <?= h(fmtChange($m['pct'])) ?>
        <span style="color:#9ca3af;font-weight:600">vs <?= number_format($m['prev']) ?> prev</span>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="kpi">
      <div class="kpi-lbl">Returning users active</div>
      <div class="kpi-val"><?= number_format($returning) ?></div>
      <div class="kpi-chg flat">signed up before this period</div>
    </div>
  </div>
  <?php if ($best_day && $best_day['unlocks'] > 0): ?>
    <p class="note">Busiest day was <strong><?= h($best_day['label']) ?></strong> with <?= number_format($best_day['unlocks']) ?> unlocks.</p>
  <?php endif; ?>

  <h2>Daily Breakdown</h2>
  <table>
    <thead><tr>
      <th>Date</th><th class="num">Signups</th><th class="num">New prompts</th>
      <th class="num">Unlocks</th><th class="num">Saves</th>
    </tr></thead>
    <tbody>
    <?php foreach ($daily as $r): ?>
      <tr>
        <td><?= h($r['label']) ?></td>
        <td class="num"><?= number_format($r['users']) ?></td>
        <td class="num"><?= number_format($r['prompts']) ?></td>
        <td class="num"><?= number_format($r['unlocks']) ?></td>
        <td class="num"><?= number_format($r['saves']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <h2>Top Prompts by Unlocks</h2>
  <?php if (!$top_unlocked): ?>
    <div class="empty">No unlocks recorded in this period.</div>
  <?php else: ?>
    <table>
      <thead><tr><th class="rank">#</th><th>Prompt</th><th class="num">Unlocks</th></tr></thead>
      <tbody>
      <?php foreach ($top_unlocked as $i => $r): ?>
        <tr>
          <td class="rank"><?= $i + 1 ?></td>
          <td><?= h($r['title']) ?></td>
          <td class="num"><?= number_format($r['c']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <h2>Prompts Published This Period</h2>
  <?php if (!$new_prompts): ?>
    <div class="empty">No new prompts were published in this period.</div>
  <?php else: ?>
    <table>
      <thead><tr><th>Title</th><th>Type</th><th>Published</th><th class="num">Likes</th></tr></thead>
      <tbody>
      <?php foreach ($new_prompts as $r): ?>
        <tr>
          <td><?= h($r['title']) ?></td>
          <td><?= h($r['prompt_type']) ?></td>
          <td><?= h(date('d M Y', strtotime($r['created_at']))) ?></td>
          <td class="num"><?= number_format($r['likes']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if ($type_split): ?>
      <p class="note">Type split:
        <?php $bits = []; foreach ($type_split as $t) $bits[] = h($t['prompt_type']) . ' (' . (int)$t['cnt'] . ')';
        echo implode(' &middot; ', $bits); ?>
      </p>
    <?php endif; ?>
  <?php endif; ?>

  <h2>Most Active Users</h2>
  <?php if (!$active_users): ?>
    <div class="empty">No logged-in user unlocks in this period.</div>
  <?php else: ?>
    <table>
      <thead><tr><th class="rank">#</th><th>User</th><th>Email</th><th class="num">Unlocks</th></tr></thead>
      <tbody>
      <?php foreach ($active_users as $i => $r): ?>
        <tr>
          <td class="rank"><?= $i + 1 ?></td>
          <td><?= h($r['username']) ?></td>
          <td><?= h($r['email']) ?></td>
          <td class="num"><?= number_format($r['c']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <h2>All-Time Snapshot</h2>
  <table>
    <thead><tr><th>Metric</th><th class="num">Value</th></tr></thead>
    <tbody>
    <?php foreach ($snapshot as $label => $val): ?>
      <tr><td><?= h($label) ?></td><td class="num"><?= number_format($val) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p class="note">These are lifetime totals, not this period's. Likes, views, copies and shares are stored as running counters without a timestamp, so they cannot be broken down by date.</p>

  <div class="foot">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
      <span style="font-weight:700; color:#475569;"><i class="fa-solid fa-shield-halved" style="color:#6d28d9;"></i> Licensed PDF Report from Arigato Labs</span>
      <span>arigatodevan.com &bull; Generated <?= h($generated) ?> IST</span>
    </div>
  </div>

</div>
</body>
</html>
