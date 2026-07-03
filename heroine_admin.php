<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';
require_once 'includes/image_helpers.php';
require_once 'includes/heroines_orbit.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$edit_id    = (int) ($_GET['edit'] ?? 0);
$editing    = null;

if ($edit_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM heroines WHERE id = ?');
    $stmt->execute([$edit_id]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'shuffle_orbit') {
        $active = $pdo->query(
            "SELECT * FROM heroines WHERE is_active = 1 AND circle_image != '' ORDER BY sort_order ASC, name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        if (empty($active)) {
            $_SESSION['heroine_err'] = 'Add at least one live heroine with a circle photo first.';
        } else {
            $stats = heroines_redistribute_landing_photos($pdo, $active, true);
            $picCount = (int) $stats['pic_count'];
            $totalCircles = $stats['laptop'] + $stats['tablet'] + $stats['mobile'];
            $_SESSION['heroine_msg'] = $picCount . ' photo(s) shuffled equally across all landing circles — '
                . 'Laptop: ' . $stats['laptop'] . ', Tablet: ' . $stats['tablet'] . ', Mobile: ' . $stats['mobile']
                . ' (' . $totalCircles . ' total). Positions you set in the editor are kept.';
        }
        header('Location: heroine_admin.php');
        exit();
    }

    if ($action === 'reset_orbit') {
        heroines_clear_orbit_map($pdo);
        $active = $pdo->query(
            "SELECT * FROM heroines WHERE is_active = 1 AND circle_image != '' ORDER BY sort_order ASC, name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($active)) {
            heroines_redistribute_landing_photos($pdo, $active, false);
        }
        $_SESSION['heroine_msg'] = 'Photos reset to stable equal fill on laptop, tablet & mobile (circle positions unchanged).';
        header('Location: heroine_admin.php');
        exit();
    }

    if ($action === 'delete') {
        $del_id = (int) ($_POST['id'] ?? 0);
        if ($del_id > 0) {
            $row = $pdo->prepare('SELECT circle_image, card_image FROM heroines WHERE id = ?');
            $row->execute([$del_id]);
            if ($img = $row->fetch(PDO::FETCH_ASSOC)) {
                foreach (['circle_image', 'card_image'] as $k) {
                    if (!empty($img[$k]) && is_file($img[$k])) {
                        @unlink($img[$k]);
                    }
                }
            }
            $pdo->prepare('DELETE FROM heroines WHERE id = ?')->execute([$del_id]);
            $_SESSION['heroine_msg'] = 'Heroine deleted.';
        }
        header('Location: heroine_admin.php');
        exit();
    }

    $id        = (int) ($_POST['id'] ?? 0);
    $name      = trim($_POST['name'] ?? '');
    $type      = ($_POST['heroine_type'] ?? 'ai') === 'real' ? 'real' : 'ai';
    $times     = max(0, min(99999, (int) ($_POST['times_used'] ?? 0)));
    $country   = trim($_POST['country'] ?? '') ?: null;
    $insta_user = trim($_POST['instagram_username'] ?? '') ?: null;
    $insta_url  = trim($_POST['instagram_url'] ?? '') ?: null;
    $sort      = max(0, (int) ($_POST['sort_order'] ?? 0));
    $active    = isset($_POST['is_active']) ? 1 : 0;
    $errors    = [];

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    $circle_path = $editing && $id === $edit_id ? ($editing['circle_image'] ?? '') : '';
    $card_path   = $editing && $id === $edit_id ? ($editing['card_image'] ?? '') : '';

    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM heroines WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $circle_path = $existing['circle_image'];
            $card_path   = $existing['card_image'];
        }
    }

    if (!empty($_FILES['circle_image']['name'])) {
        $up = heroine_upload_image($_FILES['circle_image'], 'circle', 400, 400);
        if ($up) {
            if ($circle_path && is_file($circle_path)) {
                @unlink($circle_path);
            }
            $circle_path = $up;
        } else {
            $errors[] = 'Circle picture upload failed.';
        }
    }

    if (!empty($_FILES['card_image']['name'])) {
        $up = heroine_upload_image($_FILES['card_image'], 'card', 800, 900);
        if ($up) {
            if ($card_path && is_file($card_path)) {
                @unlink($card_path);
            }
            $card_path = $up;
        } else {
            $errors[] = 'Normal picture upload failed.';
        }
    }

    if ($id <= 0 && ($circle_path === '' || $card_path === '')) {
        if ($circle_path === '') {
            $errors[] = 'Circle picture is required.';
        }
        if ($card_path === '') {
            $errors[] = 'Normal picture is required.';
        }
    }

    if (empty($errors)) {
        if ($id > 0) {
            $pdo->prepare(
                'UPDATE heroines SET name=?, heroine_type=?, circle_image=?, card_image=?, times_used=?, country=?, instagram_username=?, instagram_url=?, sort_order=?, is_active=? WHERE id=?'
            )->execute([$name, $type, $circle_path, $card_path, $times, $country, $insta_user, $insta_url, $sort, $active, $id]);
            $_SESSION['heroine_msg'] = 'Heroine updated successfully.';
        } else {
            $pdo->prepare(
                'INSERT INTO heroines (name, heroine_type, circle_image, card_image, times_used, country, instagram_username, instagram_url, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([$name, $type, $circle_path, $card_path, $times, $country, $insta_user, $insta_url, $sort, $active]);
            $_SESSION['heroine_msg'] = 'Heroine added successfully.';
        }
        header('Location: heroine_admin.php');
        exit();
    }
    $_SESSION['heroine_err'] = implode(' ', $errors);
    header('Location: heroine_admin.php' . ($id > 0 ? '?edit=' . $id : ''));
    exit();
}

$heroines = $pdo->query('SELECT * FROM heroines ORDER BY sort_order ASC, name ASC')->fetchAll(PDO::FETCH_ASSOC);
$orbit_fill_mode = heroines_orbit_fill_mode($pdo);
$orbit_slot_total = heroines_orbit_slot_count();
$active_circle_count = (int) $pdo->query(
    "SELECT COUNT(*) FROM heroines WHERE is_active = 1 AND circle_image != ''"
)->fetchColumn();
$msg = $_SESSION['heroine_msg'] ?? '';
$err = $_SESSION['heroine_err'] ?? '';
unset($_SESSION['heroine_msg'], $_SESSION['heroine_err']);

$form = $editing ?: [
    'id' => 0,
    'name' => '',
    'heroine_type' => 'ai',
    'circle_image' => '',
    'card_image' => '',
    'times_used' => 0,
    'country' => '',
    'instagram_username' => '',
    'instagram_url' => '',
    'sort_order' => 0,
    'is_active' => 1,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Heroine — Arigato Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include_once 'gtag.php'; ?>
<style>
:root{--bg:#07060f;--surface:#0f0d1e;--border:rgba(139,92,246,0.18);--border2:rgba(139,92,246,0.08);--accent:#8b5cf6;--accent2:#c084fc;--pink:#f472b6;--cyan:#22d3ee;--green:#4ade80;--yellow:#fbbf24;--red:#f87171;--text:#e2e0ff;--muted:#9490bb;--font:'Inter',sans-serif}
*{margin:0;padding:0;box-sizing:border-box}body{background:var(--bg);color:var(--text);font-family:var(--font);min-height:100vh}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:220px;background:rgba(7,6,15,0.98);border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column}
.sb-logo{padding:20px 18px 14px;border-bottom:1px solid var(--border2)}
.sb-brand{font-size:.72rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;display:flex;align-items:center;gap:8px}
.sb-admin{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border2)}
.sb-av-ph{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--pink));display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff}
.sb-uname{font-size:.78rem;font-weight:800}.sb-role{font-size:.6rem;font-weight:700;color:var(--accent2);text-transform:uppercase}
.sb-nav{flex:1;overflow-y:auto;padding:10px 8px}
.sb-nav::-webkit-scrollbar{width:2px}
.sb-nav::-webkit-scrollbar-thumb{background:var(--accent);border-radius:10px}
.sb-sec{font-size:.58rem;font-weight:900;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 10px 5px}
.sb-link{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;border:1px solid transparent;margin-bottom:1px}
.sb-link:hover{background:rgba(139,92,246,0.08);color:var(--text)}
.sb-link.active{background:rgba(139,92,246,0.15);color:var(--accent2);border-color:var(--border)}
.sb-link i{width:16px;text-align:center;flex-shrink:0}
.sb-bottom{padding:12px 8px;border-top:1px solid var(--border2)}
.sb-logout{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:700;color:var(--red);text-decoration:none}
.main{margin-left:220px;padding:28px 32px 80px}
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:22px;flex-wrap:wrap}
.tb-title{font-size:1.4rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;flex:1}
.tb-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;background:rgba(34,211,238,0.08);color:var(--cyan);border:1px solid rgba(34,211,238,0.2)}
.card{background:rgba(15,13,30,0.7);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:18px}
.card-title{font-size:.9rem;font-weight:900;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.card-title i{color:var(--accent2)}
.flash-ok{background:rgba(74,222,128,0.07);border:1px solid rgba(74,222,128,0.22);color:var(--green);padding:11px 16px;border-radius:12px;font-size:.83rem;font-weight:700;margin-bottom:16px}
.flash-err{background:rgba(248,113,113,0.07);border:1px solid rgba(248,113,113,0.22);color:var(--red);padding:11px 16px;border-radius:12px;font-size:.83rem;font-weight:700;margin-bottom:16px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{margin-bottom:14px}
.form-group.full{grid-column:1/-1}
.form-label{display:block;font-size:.68rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}
.form-input,.form-select{width:100%;padding:10px 14px;background:rgba(15,13,30,0.8);border:1px solid var(--border);border-radius:11px;color:var(--text);font-family:var(--font);font-size:.85rem;outline:none}
.form-input:focus,.form-select:focus{border-color:var(--accent)}
.form-hint{font-size:.72rem;color:var(--muted);margin-top:5px}
.type-row{display:flex;gap:10px;flex-wrap:wrap}
.type-opt{flex:1;min-width:120px}
.type-opt input{display:none}
.type-box{display:flex;align-items:center;justify-content:center;gap:6px;padding:12px;border:1px solid var(--border);border-radius:12px;font-size:.8rem;font-weight:700;cursor:pointer;color:var(--muted)}
.type-opt input:checked+.type-box{background:rgba(139,92,246,0.12);border-color:var(--accent2);color:var(--accent2)}
.slider-row{display:flex;align-items:center;gap:14px}
.slider-row input[type=range]{flex:1;accent-color:var(--accent2)}
.slider-val{min-width:48px;text-align:center;font-weight:900;color:var(--accent2)}
.preview-row{display:flex;gap:16px;flex-wrap:wrap;margin-top:8px}
.preview-thumb{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--border)}
.preview-card{width:90px;height:110px;border-radius:18px;object-fit:cover;border:2px solid var(--border)}
.btn-submit{width:100%;padding:13px;border:none;border-radius:12px;background:linear-gradient(135deg,rgba(139,92,246,0.85),rgba(244,114,182,0.65));color:#fff;font-weight:900;font-size:.9rem;cursor:pointer;font-family:var(--font)}
.btn-submit:hover{box-shadow:0 6px 24px rgba(139,92,246,0.35)}
.btn-orbit-edit{display:inline-flex;align-items:center;gap:8px;padding:12px 18px;border-radius:12px;border:1px solid rgba(34,211,238,0.3);background:rgba(34,211,238,0.1);color:var(--cyan);font-weight:900;font-size:.85rem;text-decoration:none;font-family:var(--font)}
.btn-orbit-edit:hover{box-shadow:0 6px 22px rgba(34,211,238,0.2)}
.btn-orbit-shuffle{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 18px;border:none;border-radius:12px;background:linear-gradient(135deg,rgba(244,114,182,0.85),rgba(245,112,157,0.75));color:#fff;font-weight:900;font-size:.85rem;cursor:pointer;font-family:var(--font)}
.btn-orbit-shuffle:hover{box-shadow:0 6px 22px rgba(244,114,182,0.35)}
.btn-orbit-reset{display:inline-flex;align-items:center;gap:6px;padding:10px 14px;border-radius:10px;border:1px solid rgba(139,92,246,0.25);background:rgba(139,92,246,0.08);color:var(--accent2);font-weight:700;font-size:.78rem;cursor:pointer;font-family:var(--font)}
.orbit-panel{background:rgba(244,114,182,0.06);border:1px solid rgba(244,114,182,0.22);border-radius:14px;padding:16px;margin-bottom:18px}
.orbit-panel-title{font-size:.82rem;font-weight:900;color:var(--pink);margin-bottom:8px;display:flex;align-items:center;gap:8px}
.orbit-panel-desc{font-size:.78rem;color:var(--muted);line-height:1.55;margin-bottom:14px}
.orbit-panel-status{font-size:.72rem;color:var(--accent2);font-weight:700;margin-top:12px}
.orbit-panel-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.dtable{width:100%;border-collapse:collapse;font-size:.78rem}
.dtable th{background:rgba(139,92,246,0.07);color:var(--accent2);font-weight:800;font-size:.62rem;text-transform:uppercase;padding:9px 12px;text-align:left;border-bottom:1px solid var(--border)}
.dtable td{padding:10px 12px;border-bottom:1px solid var(--border2);color:var(--muted);vertical-align:middle}
.h-thumb{width:40px;height:40px;border-radius:50%;object-fit:cover}
.h-card-thumb{width:36px;height:44px;border-radius:8px;object-fit:cover}
.act-btn{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:8px;font-size:.68rem;font-weight:800;text-decoration:none;border:1px solid;margin-right:4px}
.act-edit{background:rgba(139,92,246,0.1);color:var(--accent2);border-color:rgba(139,92,246,0.25)}
.act-del{background:rgba(248,113,113,0.08);color:var(--red);border-color:rgba(248,113,113,0.22);cursor:pointer;font-family:var(--font)}
.badge-ai,.badge-real{display:inline-block;padding:2px 8px;border-radius:999px;font-size:.58rem;font-weight:900;text-transform:uppercase}
.badge-ai{background:rgba(34,211,238,0.1);color:var(--cyan)}
.badge-real{background:rgba(244,114,182,0.1);color:var(--pink)}
.check-row{display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--muted)}
.file-upload-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:10px 14px;background:rgba(15,13,30,0.8);border:1px solid var(--border);border-radius:11px}
.file-upload-btn{background:rgba(139,92,246,0.12);color:var(--accent2);border:1px solid rgba(139,92,246,0.3);border-radius:10px;padding:9px 16px;font-weight:800;font-size:.8rem;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:all .2s;white-space:nowrap}
.file-upload-btn:hover{background:rgba(139,92,246,0.2);border-color:rgba(139,92,246,0.45)}
.file-upload-name{font-size:.78rem;color:var(--muted);font-weight:600;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.file-upload-name.has-file{color:var(--text)}
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
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-admin{padding:10px;justify-content:center}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px;padding:20px 16px 80px}.form-grid{grid-template-columns:1fr}}
@media(max-width:768px){.sidebar{display:none!important}.main{margin-left:0!important;padding:14px 14px 80px!important}.mob-topbar{display:flex!important}.topbar{display:none!important}}
</style>
</head>
<body>

<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="sideDrawer">
  <div class="drawer-head"><div class="drawer-brand">Arigato Admin</div><div class="drawer-close" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></div></div>
  <div class="drawer-user">
    <div class="d-av-ph2"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
    <div><div class="d-uname"><?= htmlspecialchars($admin_name) ?></div><div class="d-role2">Admin</div></div>
  </div>
  <nav class="drawer-nav2">
    <div class="d-sec2">Overview</div>
    <a href="dashboard.php" class="d-link2"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="analytics.php" class="d-link2"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    <div class="d-sec2">Content</div>
    <a href="upload_prompt.php" class="d-link2"><i class="fa-solid fa-upload"></i> Upload Prompt</a>
    <a href="manage_prompts.php" class="d-link2"><i class="fa-solid fa-list-check"></i> Manage Prompts</a>
    <a href="prompt_links.php" class="d-link2"><i class="fa-solid fa-link"></i> Prompt Links</a>
    <a href="potd_manager.php" class="d-link2"><i class="fa-solid fa-sun"></i> POTD Manager</a>
    <a href="trending_settings.php" class="d-link2"><i class="fa-solid fa-fire-flame-curved"></i> Trending Settings</a>
    <div class="d-sec2">Blog</div>
    <a href="blog_admin.php" class="d-link2"><i class="fa-solid fa-pen-nib"></i> Blog Admin</a>
    <a href="blog_create.php" class="d-link2"><i class="fa-solid fa-plus"></i> New Post</a>
    <div class="d-sec2">Community</div>
    <a href="heroine_admin.php" class="d-link2 active"><i class="fa-solid fa-venus"></i> My Heroine</a>
    <a href="feedback_admin.php" class="d-link2"><i class="fa-solid fa-comments"></i> Feedbacks</a>
    <div class="d-sec2">Happy Users</div>
    <a href="happy_users_admin.php?tab=upload" class="d-link2"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Screenshots</a>
    <a href="happy_users_admin.php?tab=manage" class="d-link2"><i class="fa-solid fa-images"></i> Manage Pics</a>
    <div class="d-sec2">Users</div>
    <a href="user_management.php" class="d-link2"><i class="fa-solid fa-users"></i> Users</a>
    <div class="d-sec2">Tools</div>
    <a href="my_heroines.php" class="d-link2" target="_blank"><i class="fa-solid fa-eye"></i> View Heroines</a>
    <a href="index.php" class="d-link2" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
  </nav>
  <div class="drawer-bot"><a href="login.php?logout=1" class="d-out"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
</div>

<div class="mob-topbar">
  <div class="mob-menu-btn" onclick="openDrawer()"><i class="fa-solid fa-bars"></i></div>
  <div class="mob-page-title"><i class="fa-solid fa-venus" style="-webkit-text-fill-color:var(--pink);margin-right:6px"></i>My Heroine</div>
  <a href="my_heroines.php" class="mob-home-btn" target="_blank"><i class="fa-solid fa-eye"></i> Preview</a>
</div>

<aside class="sidebar">
  <div class="sb-logo"><div class="sb-brand"><i class="fa-solid fa-shield-halved"></i> <span>Arigato Admin</span></div></div>
  <div class="sb-admin">
    <div class="sb-av-ph"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
    <div><div class="sb-uname"><?= htmlspecialchars($admin_name) ?></div><div class="sb-role">Admin</div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-sec">Overview</div>
    <a href="dashboard.php" class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
    <a href="analytics.php" class="sb-link"><i class="fa-solid fa-chart-line"></i> <span>Analytics</span></a>
    <div class="sb-sec">Content</div>
    <a href="upload_prompt.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="manage_prompts.php" class="sb-link"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
    <a href="prompt_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="potd_manager.php" class="sb-link"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
    <a href="trending_settings.php" class="sb-link"><i class="fa-solid fa-fire-flame-curved"></i> <span>Trending Settings</span></a>
    <div class="sb-sec">Blog</div>
    <a href="blog_admin.php" class="sb-link"><i class="fa-solid fa-pen-nib"></i> <span>Blog Admin</span></a>
    <a href="blog_create.php" class="sb-link"><i class="fa-solid fa-plus"></i> <span>New Post</span></a>
    <div class="sb-sec">Community</div>
    <a href="heroine_admin.php" class="sb-link active"><i class="fa-solid fa-venus"></i> <span>My Heroine</span></a>
    <a href="feedback_admin.php" class="sb-link"><i class="fa-solid fa-comments"></i> <span>Feedbacks</span></a>
    <div class="sb-sec">Happy Users</div>
    <a href="happy_users_admin.php?tab=upload" class="sb-link"><i class="fa-solid fa-cloud-arrow-up"></i> <span>Upload Screenshots</span></a>
    <a href="happy_users_admin.php?tab=manage" class="sb-link"><i class="fa-solid fa-images"></i> <span>Manage Pics</span></a>
    <div class="sb-sec">Users</div>
    <a href="user_management.php" class="sb-link"><i class="fa-solid fa-users"></i> <span>Users</span></a>
    <div class="sb-sec">Tools</div>
    <a href="my_heroines.php" class="sb-link" target="_blank"><i class="fa-solid fa-eye"></i> <span>View Heroines</span></a>
    <a href="index.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Site</span></a>
  </nav>
  <div class="sb-bottom"><a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-venus"></i> My Heroine</div>
    <a href="my_heroines.php" target="_blank" class="tb-btn"><i class="fa-solid fa-eye"></i> Preview Page</a>
  </div>

  <?php if ($msg): ?><div class="flash-ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="flash-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-title"><i class="fa-solid fa-<?= $form['id'] ? 'pen' : 'plus' ?>"></i> <?= $form['id'] ? 'Edit Heroine' : 'Add New Heroine' ?></div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Circle Picture</label>
          <div class="file-upload-row">
            <label class="file-upload-btn">
              <input type="file" name="circle_image" id="circleImageInput" accept="image/*" style="display:none" <?= $form['id'] ? '' : 'required' ?>>
              <i class="fa-solid fa-image"></i> Choose Image
            </label>
            <span class="file-upload-name" id="circleFileName">No file chosen</span>
          </div>
          <div class="form-hint">Landing page circular preview</div>
          <?php if (!empty($form['circle_image'])): ?>
            <div class="preview-row"><img src="<?= htmlspecialchars($form['circle_image']) ?>" class="preview-thumb" alt=""></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Normal Picture</label>
          <div class="file-upload-row">
            <label class="file-upload-btn">
              <input type="file" name="card_image" id="cardImageInput" accept="image/*" style="display:none" <?= $form['id'] ? '' : 'required' ?>>
              <i class="fa-solid fa-image"></i> Choose Image
            </label>
            <span class="file-upload-name" id="cardFileName">No file chosen</span>
          </div>
          <div class="form-hint">Card profile display image</div>
          <?php if (!empty($form['card_image'])): ?>
            <div class="preview-row"><img src="<?= htmlspecialchars($form['card_image']) ?>" class="preview-card" alt=""></div>
          <?php endif; ?>
        </div>

        <div class="form-group full">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($form['name']) ?>" required maxlength="120">
        </div>

        <div class="form-group full">
          <label class="form-label">Type</label>
          <div class="type-row">
            <label class="type-opt">
              <input type="radio" name="heroine_type" value="ai" <?= ($form['heroine_type'] ?? 'ai') === 'ai' ? 'checked' : '' ?>>
              <div class="type-box"><i class="fa-solid fa-wand-magic-sparkles"></i> AI Girl</div>
            </label>
            <label class="type-opt">
              <input type="radio" name="heroine_type" value="real" <?= ($form['heroine_type'] ?? '') === 'real' ? 'checked' : '' ?>>
              <div class="type-box"><i class="fa-solid fa-user"></i> Real Person</div>
            </label>
          </div>
        </div>

        <div class="form-group full">
          <label class="form-label">How Many Times Used</label>
          <div class="slider-row">
            <input type="range" id="timesSlider" min="0" max="500" value="<?= min(500, (int) $form['times_used']) ?>" oninput="document.getElementById('timesVal').textContent=this.value;document.getElementById('timesNum').value=this.value">
            <span class="slider-val" id="timesVal"><?= (int) $form['times_used'] ?></span>
          </div>
          <div class="form-hint">Slider up to 500 — use number field for higher counts</div>
          <input type="number" name="times_used" class="form-input" style="margin-top:8px" min="0" max="99999" value="<?= (int) $form['times_used'] ?>" id="timesNum">
        </div>

        <div class="form-group">
          <label class="form-label">Country <span style="opacity:.6">(Optional)</span></label>
          <input type="text" name="country" class="form-input" value="<?= htmlspecialchars($form['country'] ?? '') ?>" maxlength="80" placeholder="e.g. India">
        </div>
        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" name="sort_order" class="form-input" min="0" value="<?= (int) ($form['sort_order'] ?? 0) ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Instagram Username <span style="opacity:.6">(Optional)</span></label>
          <input type="text" name="instagram_username" class="form-input" value="<?= htmlspecialchars($form['instagram_username'] ?? '') ?>" placeholder="@username">
        </div>
        <div class="form-group">
          <label class="form-label">Instagram Profile Link <span style="opacity:.6">(Optional)</span></label>
          <input type="url" name="instagram_url" class="form-input" value="<?= htmlspecialchars($form['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/...">
        </div>

        <div class="form-group full">
          <label class="check-row">
            <input type="checkbox" name="is_active" value="1" <?= !empty($form['is_active']) ? 'checked' : '' ?>>
            Show on public My Heroines page
          </label>
        </div>
      </div>

      <button type="submit" class="btn-submit" style="margin-top:8px">
        <i class="fa-solid fa-floppy-disk"></i> Save Heroine
      </button>
      <?php if ($form['id']): ?>
        <a href="heroine_admin.php" style="display:block;text-align:center;margin-top:12px;font-size:.8rem;color:var(--muted)">Cancel edit</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card">
    <div class="orbit-panel">
      <div class="orbit-panel-title"><i class="fa-solid fa-circle-nodes"></i> Edit Circles on Landing Page</div>
      <p class="orbit-panel-desc">
        Manually drag, resize &amp; place circles on the <strong>My Heroines</strong> landing screen.
        Separate layouts for <strong>laptop</strong>, <strong>tablet</strong> &amp; <strong>mobile</strong>.
      </p>
      <div class="orbit-panel-actions">
        <a href="heroine_orbit_editor.php" class="btn-orbit-edit"><i class="fa-solid fa-pen-ruler"></i> Open Circle Editor</a>
      </div>
    </div>

    <div class="orbit-panel">
      <div class="orbit-panel-title"><i class="fa-solid fa-shuffle"></i> Landing Circle Fill</div>
      <p class="orbit-panel-desc">
        Randomly mix photos <strong>equally</strong> across every circle on
        <strong>laptop, tablet &amp; mobile</strong>. Your circle positions from the editor stay — only photos change.
        Click again anytime for a new mix.
      </p>
      <div class="orbit-panel-actions">
        <form method="POST" style="display:inline">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
          <input type="hidden" name="action" value="shuffle_orbit">
          <button type="submit" class="btn-orbit-shuffle" <?= $active_circle_count < 1 ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>>
            <i class="fa-solid fa-shuffle"></i> Shuffle &amp; Fill Equally
          </button>
        </form>
        <?php if ($orbit_fill_mode === 'equal_shuffle'): ?>
        <form method="POST" style="display:inline" onsubmit="return confirm('Reset photos to stable equal mix on all screens? (positions stay the same)')">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
          <input type="hidden" name="action" value="reset_orbit">
          <button type="submit" class="btn-orbit-reset"><i class="fa-solid fa-rotate-left"></i> Reset to Default</button>
        </form>
        <?php endif; ?>
      </div>
      <p class="orbit-panel-status">
        <?php if ($orbit_fill_mode === 'equal_shuffle'): ?>
          <i class="fa-solid fa-circle-check" style="color:var(--green)"></i>
          Equal shuffle is ON — <?= (int) $active_circle_count ?> live photo(s), equally mixed on laptop + tablet + mobile.
        <?php else: ?>
          Default mode — photos auto-fill all landing circles equally (stable shuffle).
        <?php endif; ?>
      </p>
    </div>

    <div class="card-title"><i class="fa-solid fa-list"></i> All Heroines (<?= count($heroines) ?>)</div>
    <?php if (empty($heroines)): ?>
      <p style="color:var(--muted);font-size:.85rem">No heroines yet. Add your first profile above.</p>
    <?php else: ?>
    <div style="overflow-x:auto">
      <table class="dtable">
        <thead>
          <tr>
            <th>Circle</th>
            <th>Card</th>
            <th>Name</th>
            <th>Type</th>
            <th>Used</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($heroines as $h): ?>
          <tr>
            <td><img src="<?= htmlspecialchars($h['circle_image']) ?>" class="h-thumb" alt=""></td>
            <td><img src="<?= htmlspecialchars($h['card_image']) ?>" class="h-card-thumb" alt=""></td>
            <td style="color:var(--text);font-weight:700"><?= htmlspecialchars($h['name']) ?></td>
            <td><span class="badge-<?= $h['heroine_type'] === 'real' ? 'real' : 'ai' ?>"><?= $h['heroine_type'] === 'real' ? 'Real' : 'AI' ?></span></td>
            <td><?= (int) $h['times_used'] ?></td>
            <td><?= $h['is_active'] ? '<span style="color:var(--green)">Live</span>' : '<span style="color:var(--muted)">Hidden</span>' ?></td>
            <td>
              <a href="heroine_admin.php?edit=<?= (int) $h['id'] ?>" class="act-btn act-edit"><i class="fa-solid fa-pen"></i> Edit</a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this heroine?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $h['id'] ?>">
                <button type="submit" class="act-btn act-del"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</main>

<script>
function openDrawer() {
  document.getElementById('sideDrawer').classList.add('open');
  document.getElementById('drawerOverlay').classList.add('open');
}
function closeDrawer() {
  document.getElementById('sideDrawer').classList.remove('open');
  document.getElementById('drawerOverlay').classList.remove('open');
}

(function () {
  function bindFileInput(inputId, labelId) {
    var input = document.getElementById(inputId);
    var label = document.getElementById(labelId);
    if (!input || !label) return;
    input.addEventListener('change', function () {
      var name = input.files[0] ? input.files[0].name : 'No file chosen';
      label.textContent = name;
      label.classList.toggle('has-file', !!input.files[0]);
    });
  }
  bindFileInput('circleImageInput', 'circleFileName');
  bindFileInput('cardImageInput', 'cardFileName');

  var slider = document.getElementById('timesSlider');
  var num = document.getElementById('timesNum');
  var val = document.getElementById('timesVal');
  if (!slider || !num) return;
  slider.addEventListener('input', function () {
    num.value = slider.value;
    if (val) val.textContent = slider.value;
  });
  num.addEventListener('input', function () {
    var v = Math.min(500, Math.max(0, parseInt(num.value, 10) || 0));
    slider.value = v;
    if (val) val.textContent = num.value;
  });
})();
</script>
</body>
</html>
