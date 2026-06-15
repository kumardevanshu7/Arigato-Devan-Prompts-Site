<?php
session_start();
require_once "db.php";
require_once "slug_helper.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}
$id = (int) ($_GET["id"] ?? 0);
if (!$id) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $tag = trim($_POST["tag"] ?? "");
    $prompt_text  = trim($_POST["prompt_text"] ?? "");
    $description   = trim($_POST["description"] ?? "");
    $reel_link = trim($_POST["reel_link"] ?? "");
    $bwi_raw = trim($_POST["best_works_in"] ?? "");
    $best_works_in = in_array($bwi_raw, ["nano_banana", "chatgpt"]) ? $bwi_raw : null;
    $has_assets = isset($_POST["has_assets"]) && $_POST["has_assets"] === "1";
    $asset_title = $has_assets ? trim($_POST["asset_title"] ?? "") : null;
    $asset_images_json = $_POST["current_asset_images"] ?? null;
    if (!$has_assets) { $asset_images_json = null; }
    if ($has_assets && isset($_FILES["asset_images"]) && !empty($_FILES["asset_images"]["name"][0])) {
        $asset_dir = "uploads/assets/";
        if (!is_dir($asset_dir)) { mkdir($asset_dir, 0755, true); }
        $asset_paths = [];
        $allowed_ext = ["jpg","jpeg","png","gif","webp"];
        foreach ($_FILES["asset_images"]["tmp_name"] as $i => $tmp) {
            if ($i >= 2) break;
            if ($_FILES["asset_images"]["error"][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES["asset_images"]["name"][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) continue;
            $afname = "uploads/assets/" . uniqid("asset_") . "." . $ext;
            if (move_uploaded_file($tmp, $afname)) { $asset_paths[] = $afname; }
        }
        if (!empty($asset_paths)) { $asset_images_json = json_encode($asset_paths); }
    }

    $prompt_type = trim($_POST["prompt_type"] ?? "secret");
    $valid_types = ["secret", "unreleased", "insta_viral", "already_uploaded"];
    if (!in_array($prompt_type, $valid_types)) {
        $prompt_type = "secret";
    }
    $is_secret = $prompt_type === "secret";
    $is_trial = isset($_POST['is_trial']) ? 1 : 0;

    // Only validate code for secret type
    if ($is_secret) {
        $unlock_code = strtoupper(trim($_POST["unlock_code"] ?? ""));
        if (!$title || !$tag || !$prompt_text || strlen($unlock_code) !== 6) {
            $_SESSION["edit_error"] =
                "All fields required. Code must be 6 chars.";
            header("Location: edit_prompt.php?id=$id");
            exit();
        }
    } else {
        $unlock_code = "XXXXXX"; // dummy placeholder for non-code types
        if (!$title || !$tag || !$prompt_text) {
            $_SESSION["edit_error"] =
                "Title, tags and prompt text are required.";
            header("Location: edit_prompt.php?id=$id");
            exit();
        }
    }

    $image_path = $_POST["current_image"];
    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] === UPLOAD_ERR_OK
    ) {
        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $fn = "uploads/" . uniqid("prompt_") . "." . $ext;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $fn)) {
            $image_path = $fn;
        }
    }

    // Handle extra prompts (2 and 3)
    $extra_prompts_data = [];
    $allowed_ext_ep = ["jpg","jpeg","png","gif","webp"];
    for ($ep = 2; $ep <= 3; $ep++) {
        $ep_text = trim($_POST["extra_prompt_{$ep}_text"] ?? '');
        if (empty($ep_text)) continue;
        $ep_title = trim($_POST["extra_prompt_{$ep}_title"] ?? '');
        $ep_image_path = trim($_POST["extra_prompt_{$ep}_current_image"] ?? '');
        if (isset($_FILES["extra_prompt_{$ep}_image"]) && $_FILES["extra_prompt_{$ep}_image"]["error"] === UPLOAD_ERR_OK) {
            $ep_ext = strtolower(pathinfo($_FILES["extra_prompt_{$ep}_image"]["name"], PATHINFO_EXTENSION));
            if (in_array($ep_ext, $allowed_ext_ep)) {
                $ep_fname = "uploads/" . uniqid("ep_") . "." . $ep_ext;
                if (move_uploaded_file($_FILES["extra_prompt_{$ep}_image"]["tmp_name"], $ep_fname)) {
                    $ep_image_path = $ep_fname;
                }
            }
        }
        $extra_prompts_data[] = ['title' => $ep_title ?: null, 'prompt_text' => $ep_text, 'image_path' => $ep_image_path ?: null];
    }
    $extra_prompts_json = !empty($extra_prompts_data) ? json_encode($extra_prompts_data) : null;

    $updated_slug = uniqueSlug($pdo, $title, $id);
    $pdo->prepare(
        "UPDATE prompts SET title=?, slug=?, tag=?, prompt_text=?, unlock_code=?, reel_link=?, image_path=?, prompt_type=?, best_works_in=?, asset_title=?, asset_images=?, description=?, extra_prompts=?, is_trial=? WHERE id=?",
    )->execute([
        $title,
        $updated_slug,
        $tag,
        $prompt_text,
        $unlock_code,
        $reel_link,
        $image_path,
        $prompt_type,
        $best_works_in,
        $asset_title,
        $asset_images_json,
        $description ?: null,
        $extra_prompts_json,
        $is_trial,
        $id,
    ]);

    $_SESSION["success_msg"] =
        '<i class="fa-solid fa-check"></i> Prompt updated!';
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM prompts WHERE id=?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    header("Location: dashboard.php");
    exit();
}
$edit_error = $_SESSION["edit_error"] ?? "";
unset($_SESSION["edit_error"]);
$current_tags = array_map("trim", explode(",", strtolower($p["tag"])));
$is_secret = $p["prompt_type"] === "secret";
$current_prompt_type = $p["prompt_type"] ?? "secret";
$current_bwi = $p["best_works_in"] ?? "";
$current_asset_title = $p["asset_title"] ?? "";
$current_asset_images = $p["asset_images"] ?? "";
$has_current_assets = !empty($current_asset_title) || !empty($current_asset_images);
$current_extra_arr  = json_decode($p['extra_prompts'] ?? '[]', true) ?: [];
$ep2_data = $current_extra_arr[0] ?? null;
$ep3_data = $current_extra_arr[1] ?? null;
?>
<?php $admin_name = $_SESSION['username'] ?? 'Admin'; ?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Prompt &mdash; Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include_once "gtag.php"; ?>
<style>
/* ── Admin Dark Variables ── */
:root{
  --adm-bg:#07060f;--adm-surface:#0f0d1e;--adm-surface2:#15122a;
  --adm-border:rgba(139,92,246,0.18);--adm-border2:rgba(139,92,246,0.08);
  --adm-accent:#8b5cf6;--adm-accent2:#c084fc;
  --adm-pink:#f472b6;--adm-red:#f87171;--adm-green:#4ade80;
  --adm-text:#e2e0ff;--adm-muted:#9490bb;
  --adm-font:'Inter',sans-serif;--adm-mono:'JetBrains Mono',monospace;
  /* Form variables mapped to dark theme */
  --bg-color:#0f0d1e;--card-bg:#15122a;--text-color:#e2e0ff;
  --border-color:rgba(139,92,246,0.35);--border-width:1.5px;
  --primary-color:#8b5cf6;--primary-dark:#a78bfa;
  --shadow-comic:4px 4px 0 rgba(139,92,246,0.3);
  --shadow-comic-hover:5px 5px 0 rgba(139,92,246,0.5);
  --font-main:var(--adm-font);
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--adm-bg);color:var(--adm-text);font-family:var(--adm-font);overflow-x:hidden;min-height:100vh}
/* SIDEBAR */
.sidebar{position:fixed;left:0;top:0;bottom:0;width:220px;background:rgba(7,6,15,0.98);border-right:1px solid var(--adm-border);z-index:200;display:flex;flex-direction:column}
.sb-logo{padding:20px 18px 14px;border-bottom:1px solid var(--adm-border2)}
.sb-brand{font-size:.72rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:flex;align-items:center;gap:8px}
.sb-brand i{-webkit-text-fill-color:#a78bfa;font-size:1rem}
.sb-admin{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--adm-border2)}
.sb-av{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--adm-accent);flex-shrink:0}
.sb-av-ph{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--adm-accent),var(--adm-pink));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;color:#fff;flex-shrink:0}
.sb-uname{font-size:.78rem;font-weight:800;color:var(--adm-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sb-role{font-size:.6rem;font-weight:700;color:var(--adm-accent2);text-transform:uppercase;letter-spacing:.1em}
.sb-nav{flex:1;overflow-y:auto;padding:10px 8px}
.sb-nav::-webkit-scrollbar{width:2px}
.sb-nav::-webkit-scrollbar-thumb{background:var(--adm-accent);border-radius:10px}
.sb-sec{font-size:.58rem;font-weight:900;color:var(--adm-muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 10px 5px}
.sb-link{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:600;color:var(--adm-muted);text-decoration:none;transition:all .2s;border:1px solid transparent;margin-bottom:1px}
.sb-link:hover{background:rgba(139,92,246,0.08);color:var(--adm-text)}
.sb-link.active{background:rgba(139,92,246,0.15);color:var(--adm-accent2);border-color:var(--adm-border)}
.sb-link i{width:16px;text-align:center;flex-shrink:0}
.sb-bottom{padding:12px 8px;border-top:1px solid var(--adm-border2)}
.sb-logout{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;font-size:.78rem;font-weight:700;color:var(--adm-red);text-decoration:none;transition:all .2s}
.sb-logout:hover{background:rgba(248,113,113,0.1)}
/* MAIN */
.main{margin-left:220px;min-height:100vh;padding:28px 32px 80px;position:relative}
/* TOPBAR */
.topbar{display:flex;align-items:center;gap:14px;margin-bottom:24px;flex-wrap:wrap}
.tb-title{font-size:1.4rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--adm-accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1;display:flex;align-items:center;gap:10px}
.tb-title i{-webkit-text-fill-color:var(--adm-accent2);font-size:1.2rem}
.tb-btn{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;border:1px solid;transition:all .2s;cursor:pointer;font-family:var(--adm-font)}
.tb-back{background:rgba(139,92,246,0.07);color:var(--adm-accent2);border-color:rgba(139,92,246,0.2)}
.tb-back:hover{background:rgba(139,92,246,0.15)}
/* MOBILE TOPBAR */
.mob-topbar{display:none;position:sticky;top:0;z-index:300;background:rgba(7,6,15,0.97);backdrop-filter:blur(16px);border-bottom:1px solid var(--adm-border2);padding:13px 16px;align-items:center;gap:12px}
.mob-menu-btn{width:38px;height:38px;border-radius:10px;background:rgba(139,92,246,0.08);border:1px solid var(--adm-border);display:flex;align-items:center;justify-content:center;color:var(--adm-accent2);font-size:1rem;cursor:pointer;flex-shrink:0}
.mob-page-title{font-size:1rem;font-weight:900;background:linear-gradient(135deg,#fff,var(--adm-accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex:1}
.mob-back-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:10px;font-size:.75rem;font-weight:800;text-decoration:none;background:rgba(139,92,246,0.08);color:var(--adm-accent2);border:1px solid rgba(139,92,246,0.2);flex-shrink:0}
/* MOBILE DRAWER */
.drawer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(6px);z-index:500}
.drawer{position:fixed;left:0;top:0;bottom:0;width:265px;background:rgba(7,6,15,0.99);border-right:1px solid var(--adm-border);z-index:600;display:flex;flex-direction:column;transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1)}
.drawer.open{transform:translateX(0)}
.drawer-overlay.open{display:block}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:18px 16px;border-bottom:1px solid var(--adm-border2)}
.drawer-brand{font-size:.8rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.drawer-close{width:32px;height:32px;border-radius:8px;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:var(--adm-red);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem;font-family:var(--adm-font)}
.drawer-user{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--adm-border2)}
.d-av{width:40px;height:40px;border-radius:50%;border:2px solid var(--adm-accent);object-fit:cover;flex-shrink:0}
.d-av-ph{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--adm-accent),var(--adm-pink));display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;flex-shrink:0}
.d-uname{font-size:.85rem;font-weight:800;color:var(--adm-text)}
.d-role{font-size:.65rem;color:var(--adm-accent2);font-weight:700;text-transform:uppercase}
.drawer-nav{flex:1;overflow-y:auto;padding:8px 10px}
.d-sec{font-size:.6rem;font-weight:900;color:var(--adm-muted);letter-spacing:.15em;text-transform:uppercase;padding:10px 8px 5px}
.d-link{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:600;color:var(--adm-muted);text-decoration:none;transition:all .2s;margin-bottom:2px}
.d-link:hover,.d-link.active{background:rgba(139,92,246,0.1);color:var(--adm-accent2)}
.d-link i{width:18px;text-align:center}
.drawer-bottom{padding:12px 10px;border-top:1px solid var(--adm-border2)}
.d-logout{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;font-size:.85rem;font-weight:700;color:var(--adm-red);text-decoration:none}
.d-logout:hover{background:rgba(248,113,113,0.08)}
/* FORM STYLES */
.edit-wrap{max-width:820px;margin:0 auto}
.edit-page-title{font-size:1.5rem;font-weight:900;margin-bottom:6px;display:flex;align-items:center;gap:10px;color:var(--adm-text)}
.edit-page-sub{color:var(--adm-muted);font-weight:600;margin-bottom:28px;font-size:.9rem}
.edit-card{background:var(--adm-surface);border:1px solid var(--adm-border);border-radius:20px;padding:32px;box-shadow:0 8px 32px rgba(0,0,0,0.3)}
.edit-card h2{font-size:1.1rem;font-weight:900;margin-bottom:24px;padding-bottom:14px;border-bottom:1px solid var(--adm-border2);color:var(--adm-text)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-weight:700;margin-bottom:7px;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;color:var(--adm-muted)}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:11px 15px;border:1px solid var(--adm-border);border-radius:10px;font-family:var(--adm-font);font-size:.9rem;font-weight:500;background:rgba(255,255,255,0.04);color:var(--adm-text);outline:none;transition:all .2s;box-sizing:border-box}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:var(--adm-accent);box-shadow:0 0 0 3px rgba(139,92,246,0.15);background:rgba(139,92,246,0.05)}
.form-group textarea{resize:vertical;min-height:110px}
.form-group select option{background:var(--adm-surface)}
.img-preview{display:flex;align-items:center;gap:14px;padding:12px;background:rgba(255,255,255,0.03);border:1px dashed var(--adm-border);border-radius:10px;margin-bottom:10px}
.img-preview img{width:60px;height:60px;object-fit:cover;border-radius:10px;border:1px solid var(--adm-border)}
.img-preview span{font-size:.85rem;font-weight:600;color:var(--adm-muted)}
.file-upload-wrapper{display:flex;align-items:center;gap:14px;background:rgba(255,255,255,0.03);padding:10px 15px;border:1px solid var(--adm-border);border-radius:10px}
.file-upload-btn{background:rgba(139,92,246,0.12);color:var(--adm-accent2);padding:7px 14px;border:1px solid rgba(139,92,246,0.3);border-radius:8px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;font-size:.85rem;font-family:var(--adm-font)}
.file-upload-btn:hover{background:rgba(139,92,246,0.2)}
.file-upload-name{font-weight:600;color:var(--adm-muted);font-size:.85rem}
.flash-error{background:rgba(248,113,113,0.08);color:var(--adm-red);padding:14px;border:1px solid rgba(248,113,113,0.25);border-radius:12px;font-weight:700;margin-bottom:18px}
.btn-row{display:flex;gap:14px;margin-top:8px}
.btn-cancel{display:inline-flex;align-items:center;justify-content:center;padding:12px 22px;background:rgba(255,255,255,0.04);color:var(--adm-muted);border:1px solid var(--adm-border);border-radius:12px;font-family:var(--adm-font);font-weight:700;font-size:.9rem;text-decoration:none;transition:all .2s;flex:1;text-align:center}
.btn-cancel:hover{border-color:var(--adm-accent);color:var(--adm-text)}
.btn-primary-adm{display:inline-flex;align-items:center;justify-content:center;padding:12px 22px;background:rgba(139,92,246,0.15);color:var(--adm-accent2);border:1px solid rgba(139,92,246,0.35);border-radius:12px;font-family:var(--adm-font);font-weight:800;font-size:.9rem;cursor:pointer;transition:all .2s;flex:2}
.btn-primary-adm:hover{background:rgba(139,92,246,0.25);box-shadow:0 0 20px rgba(139,92,246,0.2)}
.type-info-box{padding:12px 16px;border-radius:10px;font-size:.83rem;font-weight:700;margin-top:8px;border:1px solid var(--adm-border);display:flex;align-items:center;gap:8px;color:var(--adm-muted);background:rgba(255,255,255,0.02)}
.type-info-box.secret{background:rgba(248,113,113,0.06);border-color:rgba(248,113,113,0.25);color:var(--adm-red)}
.type-info-box.unreleased{background:rgba(251,191,36,0.06);border-color:rgba(251,191,36,0.25);color:#fbbf24}
.type-info-box.viral{background:rgba(74,222,128,0.06);border-color:rgba(74,222,128,0.25);color:var(--adm-green)}
.bwi-selector{display:flex;gap:10px;flex-wrap:wrap}
.bwi-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--adm-border);border-radius:12px;padding:10px 18px;cursor:pointer;font-family:var(--adm-font);font-weight:700;font-size:.9rem;transition:all .2s;user-select:none;background:rgba(255,255,255,0.03);color:var(--adm-muted)}
.bwi-btn input[type=radio]{display:none}
.bwi-banana-opt.bwi-selected{background:rgba(251,191,36,0.15);border-color:rgba(251,191,36,0.4);color:#fbbf24;box-shadow:0 0 12px rgba(251,191,36,0.1)}
.bwi-chatgpt-opt.bwi-selected{background:rgba(74,222,128,0.12);border-color:rgba(74,222,128,0.35);color:var(--adm-green);box-shadow:0 0 12px rgba(74,222,128,0.1)}
.type-selector{display:flex;gap:8px;flex-wrap:wrap}
.e-type-card{flex:1;min-width:100px;border:1px solid var(--adm-border);border-radius:14px;padding:12px 8px;text-align:center;cursor:pointer;font-family:var(--adm-font);font-weight:700;font-size:.82rem;transition:all .2s;background:rgba(255,255,255,0.03);color:var(--adm-muted);position:relative}
.e-type-card:hover{border-color:var(--adm-accent);color:var(--adm-text);transform:translateY(-2px)}
.e-type-card input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.e-type-card.sel-secret{background:rgba(248,113,113,0.1);border-color:rgba(248,113,113,0.4);color:var(--adm-red)}
.e-type-card.sel-unreleased{background:rgba(251,191,36,0.08);border-color:rgba(251,191,36,0.35);color:#fbbf24}
.e-type-card.sel-viral{background:rgba(34,211,238,0.07);border-color:rgba(34,211,238,0.3);color:#22d3ee}
.e-type-card.sel-uploaded{background:rgba(74,222,128,0.07);border-color:rgba(74,222,128,0.3);color:var(--adm-green)}
.extra-prompt-box{background:rgba(192,132,252,0.05);border:1px dashed rgba(192,132,252,0.35);border-radius:14px;padding:18px;margin-top:10px;margin-bottom:10px}
.extra-prompt-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.extra-prompt-num{font-weight:800;color:var(--adm-accent2);font-size:.88rem}
.extra-remove-btn{background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.25);color:var(--adm-red);border-radius:8px;padding:5px 12px;font-weight:700;font-size:.82rem;cursor:pointer;font-family:var(--adm-font)}
.extra-add-btn{display:inline-flex;align-items:center;gap:6px;background:rgba(139,92,246,0.07);border:1px dashed rgba(139,92,246,0.3);color:var(--adm-accent2);border-radius:10px;padding:9px 18px;font-weight:700;font-size:.88rem;cursor:pointer;margin-top:10px;font-family:var(--adm-font);transition:background .2s}
.extra-add-btn:hover{background:rgba(139,92,246,0.14)}
.assets-toggle-label{display:inline-flex;align-items:center;gap:10px;cursor:pointer;background:rgba(34,211,238,0.05);padding:12px 18px;border-radius:10px;border:1px dashed rgba(34,211,238,0.3);color:#22d3ee;font-weight:700;font-size:.88rem;transition:all .2s;user-select:none}
.assets-toggle-label:hover{background:rgba(34,211,238,0.09)}
.assets-toggle-label input[type=checkbox]{width:16px!important;height:16px!important;margin:0!important;padding:0!important;box-shadow:none!important;border:none!important;accent-color:#22d3ee;cursor:pointer;flex-shrink:0}
.assets-fields-box{background:rgba(255,255,255,0.02);border:1px solid rgba(34,211,238,0.2);border-radius:14px;padding:18px;margin-top:12px}
.asset-previews{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
.asset-preview-thumb{width:80px;height:80px;border-radius:10px;overflow:hidden;border:1px solid var(--adm-border)}
.asset-preview-thumb img{width:100%;height:100%;object-fit:cover}
/* SCROLLBAR */
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:var(--adm-bg)}
::-webkit-scrollbar-thumb{background:rgba(139,92,246,0.4);border-radius:10px}
/* RESPONSIVE */
@media(max-width:900px){.sidebar{width:58px}.sb-uname,.sb-role,.sb-sec,.sb-link span,.sb-brand span{display:none}.sb-admin{padding:10px;justify-content:center}.sb-link{padding:10px;justify-content:center}.main{margin-left:58px}}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:14px 14px 80px}.mob-topbar{display:flex!important}}
@media(max-width:640px){.form-row{grid-template-columns:1fr}.edit-card{padding:18px 14px}}
body::before, body::after { display: none !important; background-image: none !important; }
</style>
</head>
<body>

<!-- MOBILE DRAWER -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="sideDrawer">
  <div class="drawer-head">
    <div class="drawer-brand">Arigato Admin</div>
    <div class="drawer-close" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></div>
  </div>
  <div class="drawer-user">
    <?php $sav2=!empty($_SESSION['profile_image'])?htmlspecialchars($_SESSION['profile_image']):''; ?>
    <?php if($sav2): ?><img src="<?= $sav2 ?>" class="d-av" alt="" referrerpolicy="no-referrer">
    <?php else: ?><div class="d-av-ph"><?= strtoupper(substr($admin_name,0,1)) ?></div><?php endif; ?>
    <div><div class="d-uname"><?= htmlspecialchars($admin_name) ?></div><div class="d-role">Admin</div></div>
  </div>
  <nav class="drawer-nav">
    <div class="d-sec">Overview</div>
    <a href="dashboard.php" class="d-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="analytics.php" class="d-link"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    <div class="d-sec">Content</div>
    <a href="upload_prompt.php" class="d-link"><i class="fa-solid fa-upload"></i> Upload Prompt</a>
    <a href="manage_prompts.php" class="d-link active"><i class="fa-solid fa-list-check"></i> Manage Prompts</a>
    <a href="prompt_links.php" class="d-link"><i class="fa-solid fa-link"></i> Prompt Links</a>
    <a href="potd_manager.php" class="d-link"><i class="fa-solid fa-sun"></i> POTD Manager</a>
    <div class="d-sec">Blog</div>
    <a href="blog_admin.php" class="d-link"><i class="fa-solid fa-pen-nib"></i> Blog Admin</a>
    <a href="blog_create.php" class="d-link"><i class="fa-solid fa-plus"></i> New Post</a>
    <div class="d-sec">Community</div>
    <a href="feedback_admin.php" class="d-link"><i class="fa-solid fa-comments"></i> Feedbacks</a>
    <div class="d-sec">Users</div>
    <a href="user_management.php" class="d-link"><i class="fa-solid fa-users"></i> Users</a>
    <div class="d-sec">Tools</div>
    <a href="index.php" class="d-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
  </nav>
  <div class="drawer-bottom">
    <a href="login.php?logout=1" class="d-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </div>
</div>

<!-- MOBILE TOPBAR -->
<div class="mob-topbar">
  <div class="mob-menu-btn" onclick="openDrawer()"><i class="fa-solid fa-bars"></i></div>
  <div class="mob-page-title"><i class="fa-solid fa-pencil" style="-webkit-text-fill-color:var(--adm-accent2);margin-right:6px"></i>Edit Prompt</div>
  <a href="dashboard.php" class="mob-back-btn"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-brand"><i class="fa-solid fa-shield-halved"></i> <span>Arigato Admin</span></div>
  </div>
  <div class="sb-admin">
    <?php $sav=!empty($_SESSION['profile_image'])?htmlspecialchars($_SESSION['profile_image']):''; ?>
    <?php if($sav): ?><img src="<?= $sav ?>" class="sb-av" alt="" referrerpolicy="no-referrer">
    <?php else: ?><div class="sb-av-ph"><?= strtoupper(substr($admin_name,0,1)) ?></div><?php endif; ?>
    <div><div class="sb-uname"><?= htmlspecialchars($admin_name) ?></div><div class="sb-role">Admin</div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-sec">Overview</div>
    <a href="dashboard.php" class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
    <a href="analytics.php" class="sb-link"><i class="fa-solid fa-chart-line"></i> <span>Analytics</span></a>
    <div class="sb-sec">Content</div>
    <a href="upload_prompt.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="manage_prompts.php" class="sb-link active"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
    <a href="prompt_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="potd_manager.php" class="sb-link"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
    <div class="sb-sec">Blog</div>
    <a href="blog_admin.php" class="sb-link"><i class="fa-solid fa-pen-nib"></i> <span>Blog Admin</span></a>
    <a href="blog_create.php" class="sb-link"><i class="fa-solid fa-plus"></i> <span>New Post</span></a>
    <div class="sb-sec">Community</div>
    <a href="feedback_admin.php" class="sb-link"><i class="fa-solid fa-comments"></i> <span>Feedbacks</span></a>
    <div class="sb-sec">Users</div>
    <a href="user_management.php" class="sb-link"><i class="fa-solid fa-users"></i> <span>Users</span></a>
    <div class="sb-sec">Tools</div>
    <a href="index.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Site</span></a>
  </nav>
  <div class="sb-bottom">
    <a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="tb-title"><i class="fa-solid fa-pencil"></i> Edit Prompt</div>
    <a href="dashboard.php" class="tb-btn tb-back"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
  </div>


<div class="edit-wrap">
  <div class="edit-page-title"><i class="fa-solid fa-pencil"></i> Edit Prompt</div>
  <div class="edit-page-sub">Editing: <strong><?= htmlspecialchars(
      $p["title"],
  ) ?></strong></div>
  <?php if (
      $edit_error
  ): ?><div class="flash-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars(
    $edit_error,
) ?></div><?php endif; ?>
  <div class="edit-card">
    <h2>Prompt Details</h2>
    <form method="POST" action="edit_prompt.php?id=<?= $id ?>" enctype="multipart/form-data">
      <input type="hidden" name="current_image" value="<?= htmlspecialchars(
          $p["image_path"],
      ) ?>">

      <!-- Prompt Type Selector -->
      <div class="form-group">
        <label>Prompt Type</label>
        <div class="type-selector">
          <label class="e-type-card <?= $current_prompt_type === "secret"
              ? "sel-secret"
              : "" ?>" id="e-card-secret">
            <input type="radio" name="prompt_type" value="secret" <?= $current_prompt_type ===
            "secret"
                ? "checked"
                : "" ?> onchange="onEditTypeChange('secret')">
            <span style="font-size:1.4rem;display:block;margin-bottom:4px;"><i class="fa-solid fa-lock"></i></span><span>Secret Code</span>
          </label>
          <label class="e-type-card <?= $current_prompt_type === "unreleased"
              ? "sel-unreleased"
              : "" ?>" id="e-card-unreleased">
            <input type="radio" name="prompt_type" value="unreleased" <?= $current_prompt_type ===
            "unreleased"
                ? "checked"
                : "" ?> onchange="onEditTypeChange('unreleased')">
            <span style="font-size:1.4rem;display:block;margin-bottom:4px;"><i class="fa-solid fa-star"></i></span><span>Unreleased</span>
          </label>
          <label class="e-type-card <?= $current_prompt_type === "insta_viral"
              ? "sel-viral"
              : "" ?>" id="e-card-viral">
            <input type="radio" name="prompt_type" value="insta_viral" <?= $current_prompt_type ===
            "insta_viral"
                ? "checked"
                : "" ?> onchange="onEditTypeChange('insta_viral')">
            <span style="font-size:1.4rem;display:block;margin-bottom:4px;"><i class="fa-brands fa-instagram"></i></span><span>Insta Viral</span>
          </label>
          <label class="e-type-card <?= $current_prompt_type ===
          "already_uploaded"
              ? "sel-uploaded"
              : "" ?>" id="e-card-uploaded">
            <input type="radio" name="prompt_type" value="already_uploaded" <?= $current_prompt_type ===
            "already_uploaded"
                ? "checked"
                : "" ?> onchange="onEditTypeChange('already_uploaded')">
            <span style="font-size:1.4rem;display:block;margin-bottom:4px;"><i class="fa-solid fa-clock-rotate-left"></i></span><span>Already Uploaded</span>
          </label>
        </div>
      </div>

      <!-- Trial Reel Mode Toggle -->
      <div class="form-group" style="margin-top:4px;">
        <label style="display:block;font-weight:800;margin-bottom:7px;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">Trial Reel Mode</label>
        <label class="assets-toggle-label" id="trial-toggle-label" style="background:<?= ($p['is_trial'] ?? 0) ? '#fff3e0' : '#f5f5f5' ?>;border-color:<?= ($p['is_trial'] ?? 0) ? '#f97316' : '#ccc' ?>;color:<?= ($p['is_trial'] ?? 0) ? '#c2410c' : '#666' ?>;">
          <input type="checkbox" name="is_trial" id="is_trial" value="1" onchange="toggleTrialUI(this)" <?= ($p['is_trial'] ?? 0) ? 'checked' : '' ?>>
          <span><i class="fa-solid fa-flask"></i> Trial Mode &mdash; Hidden from site, direct link only</span>
        </label>
        <div style="margin-top:8px;font-size:.78rem;color:#888;font-weight:600;padding:8px 12px;background:#fafafa;border-radius:8px;border:1px dashed #ddd;" id="trial-info-box">
          <?php if ($p['is_trial'] ?? 0): ?>
          <i class="fa-solid fa-eye-slash" style="color:#f97316;"></i> <strong style="color:#c2410c;">Trial Mode ON</strong> &mdash; Hidden from gallery &amp; listings. Share via direct link from Prompt Links.
          <?php else: ?>
          <i class="fa-solid fa-eye" style="color:#22c55e;"></i> <strong style="color:#15803d;">Visible</strong> &mdash; Appears normally on the site.
          <?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex:1;"><label for="e-title">Title *</label><input type="text" id="e-title" name="title" value="<?= htmlspecialchars(
            $p["title"],
        ) ?>" required></div>
      </div>

      <div class="form-group">
          <label>Tags (Type and press Enter, comma, or click a suggestion)</label>
          <div class="tag-input-container" style="display:flex; flex-wrap:wrap; gap:8px; padding:10px; border:var(--border-width) solid var(--text-color); border-radius:12px; background:#fff; min-height:50px; cursor:text;" onclick="document.getElementById('tag-input-field').focus()">
              <input type="text" id="tag-input-field" placeholder="secret, couple, neon..." style="border:none; outline:none; background:transparent; flex-grow:1; min-width:150px; font-family:var(--font-main); font-size:1rem; padding:4px;">
          </div>
          <input type="hidden" id="e-tag" name="tag" value="<?= htmlspecialchars(
              $p["tag"],
          ) ?>" required>

          <div id="tag-suggestions" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:8px;">
              <?php
              $stmt = $pdo->query("SELECT tag FROM prompts");
              $all_tags = [];
              while ($row = $stmt->fetch()) {
                  $tarr = explode(",", $row["tag"]);
                  foreach ($tarr as $t) {
                      $t = trim($t);
                      if (!empty($t)) {
                          $all_tags[] = strtolower($t);
                      }
                  }
              }
              $unique_tags = array_unique($all_tags);
              $core_tags = [];
              $unique_tags = array_unique(
                  array_merge($core_tags, $unique_tags),
              );
              sort($unique_tags);
              foreach ($unique_tags as $ut) {
                  echo '<span class="tag-suggestion" onclick="addTag(\'' .
                      htmlspecialchars($ut) .
                      '\')" style="background:var(--secondary-color); padding:4px 10px; border-radius:20px; font-size:0.85rem; font-weight:800; cursor:pointer; border:2px solid var(--text-color);">+' .
                      htmlspecialchars($ut) .
                      "</span>";
              }
              ?>
          </div>
      </div>

      <div class="form-group">
        <label>Best Works In <span style="font-weight:600;color:#888;text-transform:none;font-size:.85rem;">(optional)</span></label>
        <div class="bwi-selector">
          <label class="bwi-btn bwi-banana-opt <?= $current_bwi === 'nano_banana' ? 'bwi-selected' : '' ?>" onclick="setBwi('nano_banana',this)">
            <input type="radio" name="best_works_in" value="nano_banana" <?= $current_bwi === 'nano_banana' ? 'checked' : '' ?>>
            ?? Nano Banana
          </label>
          <label class="bwi-btn bwi-chatgpt-opt <?= $current_bwi === 'chatgpt' ? 'bwi-selected' : '' ?>" onclick="setBwi('chatgpt',this)">
            <input type="radio" name="best_works_in" value="chatgpt" <?= $current_bwi === 'chatgpt' ? 'checked' : '' ?>>
            ? ChatGPT
          </label>
        </div>
      </div>

      <div class="form-group"><label for="e-prompt">Prompt Text *</label><textarea id="e-prompt" name="prompt_text" rows="6" required><?= htmlspecialchars(
          $p["prompt_text"],
      ) ?></textarea></div>

      <div class="form-group">
        <label for="e-desc">SEO Description <span style="font-weight:600;color:#888;text-transform:none;font-size:.85rem;">(optional — shown in Google search results)</span></label>
        <textarea id="e-desc" name="description" rows="3" maxlength="160" placeholder="Short description for Google search results (max 160 chars). Leave blank to auto-generate."><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
        <div style="font-size:.78rem;color:#888;font-weight:600;margin-top:4px;"><span id="desc-char-count"><?= strlen($p['description'] ?? '') ?></span>/160 characters</div>
      </div>

      <!-- Extra Prompts -->
      <div class="form-group">
        <label>Extra Prompts <span style="font-weight:600;color:#888;text-transform:none;font-size:.85rem;">(optional — up to 2 more variants for this card)</span></label>

        <div id="ep2-section" style="<?= $ep2_data ? '' : 'display:none;' ?>">
          <div class="extra-prompt-box">
            <div class="extra-prompt-header">
              <span class="extra-prompt-num"><i class="fa-solid fa-2"></i> Prompt 2</span>
              <button type="button" class="extra-remove-btn" onclick="removeEP(2)"><i class="fa-solid fa-xmark"></i> Remove</button>
            </div>
            <input type="hidden" name="extra_prompt_2_current_image" value="<?= htmlspecialchars($ep2_data['image_path'] ?? '') ?>">
            <div class="form-group" style="margin-bottom:12px;">
              <label>Prompt 2 Title <span style="font-weight:600;color:#888;text-transform:none;">(optional)</span></label>
              <input type="text" name="extra_prompt_2_title" value="<?= htmlspecialchars($ep2_data['title'] ?? '') ?>" placeholder="e.g. Rainy Day Version">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
              <label>Prompt 2 Text</label>
              <textarea name="extra_prompt_2_text" id="ep2_text" rows="4"><?= htmlspecialchars($ep2_data['prompt_text'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label>Prompt 2 Image <span style="font-weight:600;color:#888;text-transform:none;">(leave blank to keep current)</span></label>
              <?php if (!empty($ep2_data['image_path'])): ?>
              <div style="margin-bottom:8px;"><img loading="lazy" src="<?= htmlspecialchars($ep2_data['image_path']) ?>" style="width:55px;height:75px;object-fit:cover;border-radius:8px;border:2px solid var(--text-color);"></div>
              <?php endif; ?>
              <div class="file-upload-wrapper">
                <label for="ep2_image" class="file-upload-btn" style="background:var(--secondary-color);white-space:nowrap;"><i class="fa-solid fa-image"></i> <?= $ep2_data ? 'Change' : 'Choose' ?> Image</label>
                <span class="file-upload-name" id="ep2-fname">No file chosen</span>
                <input type="file" id="ep2_image" name="extra_prompt_2_image" accept="image/*" style="display:none;" onchange="document.getElementById('ep2-fname').textContent=this.files[0]?this.files[0].name:'No file chosen'">
              </div>
            </div>
          </div>
        </div>

        <div id="ep3-section" style="<?= $ep3_data ? '' : 'display:none;' ?>">
          <div class="extra-prompt-box">
            <div class="extra-prompt-header">
              <span class="extra-prompt-num"><i class="fa-solid fa-3"></i> Prompt 3</span>
              <button type="button" class="extra-remove-btn" onclick="removeEP(3)"><i class="fa-solid fa-xmark"></i> Remove</button>
            </div>
            <input type="hidden" name="extra_prompt_3_current_image" value="<?= htmlspecialchars($ep3_data['image_path'] ?? '') ?>">
            <div class="form-group" style="margin-bottom:12px;">
              <label>Prompt 3 Title <span style="font-weight:600;color:#888;text-transform:none;">(optional)</span></label>
              <input type="text" name="extra_prompt_3_title" value="<?= htmlspecialchars($ep3_data['title'] ?? '') ?>" placeholder="e.g. Sunset Version">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
              <label>Prompt 3 Text</label>
              <textarea name="extra_prompt_3_text" id="ep3_text" rows="4"><?= htmlspecialchars($ep3_data['prompt_text'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label>Prompt 3 Image <span style="font-weight:600;color:#888;text-transform:none;">(leave blank to keep current)</span></label>
              <?php if (!empty($ep3_data['image_path'])): ?>
              <div style="margin-bottom:8px;"><img loading="lazy" src="<?= htmlspecialchars($ep3_data['image_path']) ?>" style="width:55px;height:75px;object-fit:cover;border-radius:8px;border:2px solid var(--text-color);"></div>
              <?php endif; ?>
              <div class="file-upload-wrapper">
                <label for="ep3_image" class="file-upload-btn" style="background:var(--secondary-color);white-space:nowrap;"><i class="fa-solid fa-image"></i> <?= $ep3_data ? 'Change' : 'Choose' ?> Image</label>
                <span class="file-upload-name" id="ep3-fname">No file chosen</span>
                <input type="file" id="ep3_image" name="extra_prompt_3_image" accept="image/*" style="display:none;" onchange="document.getElementById('ep3-fname').textContent=this.files[0]?this.files[0].name:'No file chosen'">
              </div>
            </div>
          </div>
        </div>

        <div id="ep-add-btns">
          <button type="button" id="ep-add2-btn" class="extra-add-btn" style="<?= $ep2_data ? 'display:none;' : '' ?>" onclick="addEP(2)"><i class="fa-solid fa-plus"></i> Add Prompt 2</button>
          <?php if ($ep2_data): ?>
          <button type="button" id="ep-add3-btn" class="extra-add-btn" style="<?= $ep3_data ? 'display:none;' : '' ?>" onclick="addEP(3)"><i class="fa-solid fa-plus"></i> Add Prompt 3</button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Code field &mdash; only shown for secret -->
      <div class="form-row" id="code-field-row" style="<?= $is_secret
          ? ""
          : "display:none;" ?>">
        <div class="form-group">
          <label for="e-code">Access Code (6 chars) *</label>
          <input type="text" id="e-code" name="unlock_code" maxlength="6" value="<?= htmlspecialchars(
              $p["unlock_code"] ?? "",
          ) ?>" style="text-transform:uppercase;letter-spacing:3px;font-weight:900" <?= $is_secret
    ? "required"
    : "" ?>>
        </div>
      </div>

      <!-- Reel link standalone -->
      <div class="form-group" id="reel-link-group" style="<?= $is_secret
          ? ""
          : "display:none;" ?>">
        <label for="e-reel">Reel Link <span style="font-weight:600;color:var(--text-color);">(Required for Secret Code)</span></label>
        <input type="url" id="e-reel" name="reel_link" value="<?= htmlspecialchars(
            $p["reel_link"] ?? "",
        ) ?>" placeholder="https://instagram.com/reel/..." <?= $is_secret
    ? "required"
    : "" ?>>
      </div>

      <!-- Assets Toggle -->
      <div class="form-group">
        <label>Assets <span style="font-weight:600;color:#888;text-transform:none;font-size:.85rem;">(optional — reference images shown after unlock)</span></label>
        <label class="assets-toggle-label" id="assets-toggle-label">
            <input type="checkbox" name="has_assets" id="has_assets" value="1" onchange="toggleAssets(this)" <?= $has_current_assets ? 'checked' : '' ?>>
            <span><i class="fa-solid fa-paperclip"></i> Include Assets</span>
        </label>
        <div id="assets-fields" style="<?= $has_current_assets ? 'display:block;' : 'display:none;' ?>">
            <div class="assets-fields-box">
                <input type="hidden" name="current_asset_images" value="<?= htmlspecialchars($current_asset_images) ?>">
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="margin-top:0;">Assets Title</label>
                    <input type="text" name="asset_title" id="edit-asset-title" value="<?= htmlspecialchars($current_asset_title) ?>" placeholder="e.g. Reference Photos">
                </div>
                <?php if (!empty($current_asset_images)): ?>
                <div style="margin-bottom:12px;">
                    <label style="font-size:.8rem;color:#888;font-weight:700;text-transform:uppercase;">Current Assets</label>
                    <div class="asset-previews">
                        <?php foreach (json_decode($current_asset_images, true) ?? [] as $aimg): ?>
                        <div class="asset-preview-thumb"><img loading="lazy" src="<?= htmlspecialchars($aimg) ?>" alt="asset"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Replace Images <span style="font-weight:600;color:#888;text-transform:none;">(leave blank to keep current, max 2)</span></label>
                    <div class="file-upload-wrapper">
                        <label for="e-asset-images" class="file-upload-btn" style="background:var(--secondary-color);">
                            <i class="fa-solid fa-paperclip"></i> Choose Files
                        </label>
                        <span class="file-upload-name" id="e-asset-fname">No files chosen</span>
                        <input type="file" id="e-asset-images" name="asset_images[]" accept="image/*" multiple style="display:none;" onchange="handleEditAssetFiles(this)">
                    </div>
                    <div class="asset-previews" id="e-asset-previews"></div>
                </div>
            </div>
        </div>
      </div>

      <div class="form-group">
        <label>Cover Image (leave blank to keep current)</label>
        <div class="img-preview">
          <img loading="lazy" src="<?= htmlspecialchars(
              $p["image_path"],
          ) ?>" alt="Current cover">
          <span>Current cover image</span>
        </div>
        <div class="file-upload-wrapper">
          <label for="e-img" class="file-upload-btn">
            <i class="fa-solid fa-upload"></i> Replace Image
          </label>
          <span class="file-upload-name" id="e-fname">No file chosen</span>
          <input type="file" id="e-img" name="image" accept="image/*" style="display:none" onchange="document.getElementById('e-fname').textContent=this.files[0]?.name||'No file chosen'">
        </div>
      </div>

      <div class="btn-row">
        <a href="dashboard.php" class="btn-cancel"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
        <button type="submit" class="btn-primary-adm">Save Changes <i class="fa-solid fa-check"></i></button>
      </div>
    </form>
  </div>
</div>

<script>
        const tagInputContainer = document.querySelector('.tag-input-container');
        const tagInputField = document.getElementById('tag-input-field');
        const hiddenTagInput = document.getElementById('e-tag');
        const codeRow = document.getElementById('code-field-row');
        const codeInput = document.getElementById('e-code');
        const reelLinkGroup = document.getElementById('reel-link-group');
        const reelLinkInput = document.getElementById('e-reel');

        // Initialize from PHP
        let tags = <?= json_encode(array_values($current_tags)) ?>;

        function renderTags() {
            document.querySelectorAll('.tag-pill').forEach(el => el.remove());
            tags.forEach((tag, index) => {
                const pill = document.createElement('span');
                pill.className = 'tag-pill';
                pill.style.cssText = 'background:var(--primary-color); padding:4px 10px; border-radius:20px; font-size:0.85rem; font-weight:800; border:2px solid var(--text-color); display:flex; align-items:center; gap:6px;';
                pill.innerHTML = `${tag} <i class="fa-solid fa-xmark" style="cursor:pointer;" onclick="removeTag(${index})"></i>`;
                tagInputContainer.insertBefore(pill, tagInputField);
            });
            hiddenTagInput.value = tags.join(',');
            checkSecretTag();
        }

        function addTag(tag) {
            tag = tag.trim().replace(/[^a-zA-Z0-9 ]/g, '').replace(/\s+/g, ' ');
            tag = tag.replace(/\b\w/g, c => c.toUpperCase());
            if (tag && !tags.includes(tag)) {
                tags.push(tag);
                renderTags();
            }
            tagInputField.value = '';
        }

        window.removeTag = function(index) {
            tags.splice(index, 1);
            renderTags();
        }

        tagInputField.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addTag(this.value);
            } else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) {
                tags.pop();
                renderTags();
            }
        });

        function onEditTypeChange(type) {
            const classMap = {secret:'sel-secret',unreleased:'sel-unreleased',insta_viral:'sel-viral',already_uploaded:'sel-uploaded'};
            const idMap = {secret:'e-card-secret',unreleased:'e-card-unreleased',insta_viral:'e-card-viral',already_uploaded:'e-card-uploaded'};
            ['e-card-secret','e-card-unreleased','e-card-viral','e-card-uploaded'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.className = 'e-type-card';
            });
            const card = document.getElementById(idMap[type]);
            if (card) card.classList.add(classMap[type]);
            checkSecretTag();
            if (type !== 'secret') {
                reelLinkInput.value = '';
            }
        }

        function checkSecretTag() {
            const selectedType = document.querySelector('input[name="prompt_type"]:checked')?.value;
            if (selectedType === 'secret') {
                codeRow.style.display = 'flex';
                codeInput.required = true;
                codeInput.disabled = false;
                reelLinkGroup.style.display = 'block';
                reelLinkInput.required = true;
            } else {
                codeRow.style.display = 'none';
                codeInput.required = false;
                codeInput.disabled = true;
                reelLinkGroup.style.display = 'none';
                reelLinkInput.required = false;
            }
        }

        // Initial render
        renderTags();

        function setBwi(val, el) {
            document.querySelectorAll('.bwi-btn').forEach(b => b.classList.remove('bwi-selected'));
            el.classList.add('bwi-selected');
            el.querySelector('input[type=radio]').checked = true;
        }

        function toggleAssets(cb) {
            document.getElementById('assets-fields').style.display = cb.checked ? 'block' : 'none';
            document.getElementById('assets-toggle-label').style.background = cb.checked ? '#dceeff' : '';
        }
        // Init toggle visual
        (function(){ const cb = document.getElementById('has_assets'); if(cb && cb.checked) document.getElementById('assets-toggle-label').style.background='#dceeff'; })();

        // Description char counter
        document.getElementById('e-desc').addEventListener('input', function() {
            document.getElementById('desc-char-count').textContent = this.value.length;
        });

        function addEP(num) {
            document.getElementById('ep'+num+'-section').style.display = 'block';
            document.getElementById('ep-add'+num+'-btn').style.display = 'none';
            if (num === 2) {
                const addBtns = document.getElementById('ep-add-btns');
                let b3 = document.getElementById('ep-add3-btn');
                if (!b3) {
                    b3 = document.createElement('button');
                    b3.type='button'; b3.id='ep-add3-btn'; b3.className='extra-add-btn';
                    b3.innerHTML='? Add Prompt 3'; b3.onclick=function(){ addEP(3); };
                    addBtns.appendChild(b3);
                } else { b3.style.display=''; }
            }
        }
        function removeEP(num) {
            document.getElementById('ep'+num+'-section').style.display='none';
            const t=document.getElementById('ep'+num+'_text'); if(t) t.value='';
            const im=document.getElementById('ep'+num+'_image'); if(im) im.value='';
            const fn=document.getElementById('ep'+num+'-fname'); if(fn) fn.textContent='No file chosen';
            const ab=document.getElementById('ep-add'+num+'-btn'); if(ab) ab.style.display='';
            if(num===2){ removeEP(3); const b3=document.getElementById('ep-add3-btn'); if(b3) b3.style.display='none'; }
        }

        function handleEditAssetFiles(input) {
            const files = Array.from(input.files).slice(0, 2);
            document.getElementById('e-asset-fname').textContent = files.map(f=>f.name).join(', ') || 'No files chosen';
            const prev = document.getElementById('e-asset-previews');
            prev.innerHTML = '';
            files.forEach(f => {
                const r = new FileReader();
                r.onload = e => {
                    const d = document.createElement('div');
                    d.className = 'asset-preview-thumb';
                    d.innerHTML = `<img loading="lazy" src="${e.target.result}">`;
                    prev.appendChild(d);
                };
                r.readAsDataURL(f);
            });
        }

        function toggleTrialUI(cb) {
            const label = document.getElementById('trial-toggle-label');
            const info = document.getElementById('trial-info-box');
            if (cb.checked) {
                label.style.background = 'rgba(251,191,36,0.12)';
                label.style.borderColor = 'rgba(251,191,36,0.4)';
                label.style.color = '#fbbf24';
                info.innerHTML = '<i class="fa-solid fa-eye-slash" style="color:#fbbf24;"></i> <strong style="color:#fbbf24;">Trial Mode ON</strong> &mdash; Hidden from gallery &amp; listings. Share via direct link from Prompt Links.';
            } else {
                label.style.background = 'rgba(34,211,238,0.05)';
                label.style.borderColor = 'rgba(34,211,238,0.3)';
                label.style.color = '#22d3ee';
                info.innerHTML = '<i class="fa-solid fa-eye" style="color:#4ade80;"></i> <strong style="color:#4ade80;">Visible</strong> &mdash; Appears normally on the site.';
            }
        }

        // Mobile drawer
        function openDrawer() {
            document.getElementById('sideDrawer').classList.add('open');
            document.getElementById('drawerOverlay').classList.add('open');
        }
        function closeDrawer() {
            document.getElementById('sideDrawer').classList.remove('open');
            document.getElementById('drawerOverlay').classList.remove('open');
        }
</script>
</main>
</body></html>
