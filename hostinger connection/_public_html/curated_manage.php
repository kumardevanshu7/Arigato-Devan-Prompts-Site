<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';
require_once 'slug_helper.php';

function nm_table_exists_manage(PDO $pdo, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    try {
        $q = $pdo->prepare('SHOW TABLES LIKE ?');
        $q->execute([$table]);
        $cache[$table] = (bool) $q->fetchColumn();
    } catch (PDOException $e) {
        $cache[$table] = false;
    }
    return $cache[$table];
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$msg = '';
$err = '';
if (!empty($_GET['saved'])) {
    $msg = 'Prompt updated successfully.';
}
$csrf = generate_csrf();

$image_helpers_ready = false;
if (is_file(__DIR__ . '/includes/image_helpers.php')) {
    require_once __DIR__ . '/includes/image_helpers.php';
    $image_helpers_ready = true;
}

function nm_unlink_image(?string $path): void
{
    if ($path && is_file($path)) {
        @unlink($path);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_prompt') {
    verify_csrf();
    $edit_id = (int) ($_POST['edit_id'] ?? 0);
    if (!$image_helpers_ready) {
        $err = 'Image helpers missing. Upload includes/image_helpers.php first.';
    } elseif ($edit_id <= 0) {
        $err = 'Invalid prompt.';
    } else {
        $cur = $pdo->prepare('SELECT * FROM curated_prompts WHERE id = ?');
        $cur->execute([$edit_id]);
        $row = $cur->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $err = 'Prompt not found.';
        } else {
            $category = in_array($_POST['category'] ?? '', ['boys', 'girls', 'couple', 'family', 'creativity'], true) ? $_POST['category'] : '';
            $title = trim($_POST['title'] ?? '');
            $raw_tags = trim($_POST['tags'] ?? '');
            $prompt_text = trim($_POST['prompt_text'] ?? '');
            $meta_description = trim($_POST['meta_description'] ?? '');
            $meta_keywords = trim($_POST['meta_keywords'] ?? '');
            $chatgpt_failed = !empty($_POST['chatgpt_failed']) ? 1 : 0;
            $gemini_failed = !empty($_POST['gemini_failed']) ? 1 : 0;

            if (!$category || !$title || !$prompt_text) {
                $err = 'Category, title, and prompt text are required.';
            } else {
                $tags_arr = array_slice(array_filter(array_map('trim', explode(',', $raw_tags))), 0, 2);
                $tags_str = implode(', ', $tags_arr);

                $thumb = $row['thumbnail_image'];
                $new_thumb = curated_upload_image($_FILES['thumbnail'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'thumb', 600, 800);
                if ($new_thumb) {
                    nm_unlink_image($thumb);
                    $thumb = $new_thumb;
                }

                $chatgpt_img = $row['chatgpt_image'];
                if ($chatgpt_failed) {
                    nm_unlink_image($chatgpt_img);
                    $chatgpt_img = null;
                } else {
                    $new_cgpt = curated_upload_image($_FILES['chatgpt_image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'cgpt', 900, 1600);
                    if ($new_cgpt) {
                        nm_unlink_image($chatgpt_img);
                        $chatgpt_img = $new_cgpt;
                    }
                    if (!$chatgpt_img) {
                        $err = 'ChatGPT result image is required (or mark as failed).';
                    }
                }

                $gemini_img = $row['gemini_image'];
                if (!$err && $gemini_failed) {
                    nm_unlink_image($gemini_img);
                    $gemini_img = null;
                } elseif (!$err) {
                    $new_gem = curated_upload_image($_FILES['gemini_image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'gem', 900, 1600);
                    if ($new_gem) {
                        nm_unlink_image($gemini_img);
                        $gemini_img = $new_gem;
                    }
                    if (!$gemini_img) {
                        $err = 'Gemini result image is required (or mark as failed).';
                    }
                }

                if (!$err) {
                    $slug = ($title !== $row['title'] || empty($row['slug']))
                        ? uniqueCuratedSlug($pdo, $title, $edit_id)
                        : $row['slug'];
                    $pdo->prepare(
                        'UPDATE curated_prompts SET category = ?, title = ?, slug = ?, tags = ?, prompt_text = ?, meta_description = ?, meta_keywords = ?,
                         thumbnail_image = ?, chatgpt_image = ?, chatgpt_failed = ?, gemini_image = ?, gemini_failed = ? WHERE id = ?'
                    )->execute([
                        $category, $title, $slug, $tags_str, $prompt_text, $meta_description ?: null, $meta_keywords,
                        $thumb, $chatgpt_img, $chatgpt_failed, $gemini_img, $gemini_failed, $edit_id,
                    ]);
                    header('Location: curated_manage.php?edit=' . $edit_id . '&saved=1');
                    exit();
                }
            }
        }
    }
}

$edit_id = (int) ($_GET['edit'] ?? $_POST['edit_id'] ?? 0);
$edit_row = null;
if ($edit_id > 0 && nm_table_exists_manage($pdo, 'curated_prompts')) {
    $es = $pdo->prepare('SELECT * FROM curated_prompts WHERE id = ?');
    $es->execute([$edit_id]);
    $edit_row = $es->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $del_id = (int)($_POST['del_id'] ?? 0);
    if ($del_id > 0) {
        $row = $pdo->prepare('SELECT thumbnail_image, chatgpt_image, gemini_image FROM curated_prompts WHERE id = ?');
        $row->execute([$del_id]);
        if ($item = $row->fetch(PDO::FETCH_ASSOC)) {
            foreach (['thumbnail_image', 'chatgpt_image', 'gemini_image'] as $col) {
                if (!empty($item[$col]) && is_file($item[$col])) @unlink($item[$col]);
            }
        }
        $pdo->prepare('DELETE FROM curated_prompts WHERE id = ?')->execute([$del_id]);
        $pdo->prepare('DELETE FROM curated_votes WHERE prompt_id = ?')->execute([$del_id]);
        $pdo->prepare('DELETE FROM curated_likes WHERE prompt_id = ?')->execute([$del_id]);
        $msg = 'Prompt deleted.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $tid = (int)($_POST['tid'] ?? 0);
    $val = (int)($_POST['val'] ?? 0) ? 1 : 0;
    $pdo->prepare('UPDATE curated_prompts SET is_visible = ? WHERE id = ?')->execute([$val, $tid]);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit();
}

$prompts = [];
if (nm_table_exists_manage($pdo, 'curated_prompts')) {
    try {
        $prompts = $pdo->query('SELECT * FROM curated_prompts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $prompts = [];
    }
}

$f_cat = strtolower(trim($_GET['cat'] ?? 'all'));
$f_gpt = strtolower(trim($_GET['gpt'] ?? 'all'));
$f_gem = strtolower(trim($_GET['gem'] ?? 'all'));
$f_vis = strtolower(trim($_GET['vis'] ?? 'all'));
$f_q   = trim($_GET['q'] ?? '');

$filtered = array_values(array_filter($prompts, function ($p) use ($f_cat, $f_gpt, $f_gem, $f_vis, $f_q) {
    if ($f_cat !== 'all' && ($p['category'] ?? '') !== $f_cat) return false;
    if ($f_gpt === 'ok' && !empty($p['chatgpt_failed'])) return false;
    if ($f_gpt === 'failed' && empty($p['chatgpt_failed'])) return false;
    if ($f_gem === 'ok' && !empty($p['gemini_failed'])) return false;
    if ($f_gem === 'failed' && empty($p['gemini_failed'])) return false;
    if ($f_vis === 'visible' && empty($p['is_visible'])) return false;
    if ($f_vis === 'hidden' && !empty($p['is_visible'])) return false;
    if ($f_q !== '') {
        $hay = strtolower(($p['title'] ?? '') . ' ' . ($p['tags'] ?? ''));
        if (strpos($hay, strtolower($f_q)) === false) return false;
    }
    return true;
}));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Curated AI Prompts — Manage Prompts</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--bg:#0b0b10;--surface:#141419;--surface-2:#1a1a22;--border:#252530;--text:#ededf0;--muted:#72728a;--accent:#F5709D;--soft:#11FFC9;--green:#34d399}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:Inter,system-ui,sans-serif}
.sidebar{position:fixed;inset:0 auto 0 0;width:250px;background:var(--surface);border-right:1px solid var(--border);padding:28px 16px}
.sb-brand{display:flex;align-items:center;gap:10px;font-weight:900;color:var(--accent);margin-bottom:30px}
.sb-sec{font-size:.6rem;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin:20px 0 10px;padding:0 8px}
.sb-link{display:flex;gap:10px;align-items:center;padding:10px 12px;border-radius:10px;color:var(--muted);text-decoration:none}
.sb-link.active{background:rgba(245,112,157,.16);color:var(--soft);border:1px solid rgba(245,112,157,.2)}
.main{margin-left:250px;padding:40px 48px 80px;max-width:1100px}
.head{margin-bottom:22px}.head h1{font-size:1.45rem;font-weight:900}
.toast{margin-bottom:16px;padding:12px 14px;border-radius:10px;background:rgba(52,211,153,.09);border:1px solid rgba(52,211,153,.2);color:var(--green);font-weight:700;font-size:.82rem}
.list-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden}
.list-card h2{padding:18px 22px;border-bottom:1px solid var(--border);font-size:1rem;font-weight:800}
.filters{
  display:grid;
  grid-template-columns:2fr repeat(4,minmax(150px,1fr)) auto;
  gap:10px;
  padding:14px;
  border-bottom:1px solid var(--border);
  background:var(--surface-2);
  align-items:center;
}
.filters input,.filters select{
  width:100%;
  height:42px;
  padding:0 12px;
  background:rgba(11,11,16,.65);
  border:1px solid #2f3140;
  border-radius:12px;
  color:var(--text);
  font-size:.8rem;
  font-weight:600;
}
.filters input::placeholder{color:#8e91a8;font-weight:500}
.filters input:focus,.filters select:focus{
  outline:none;
  border-color:#f472b6;
  box-shadow:0 0 0 3px rgba(245,112,157,.12);
}
.filters select{
  appearance:none;
  -webkit-appearance:none;
  -moz-appearance:none;
  padding-right:34px;
  color-scheme:dark;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='m5 7 5 6 5-6' stroke='%23a1a1bb' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat:no-repeat;
  background-position:right 11px center;
  background-size:14px;
}
.filters select option{
  background:#11131a;
  color:#e9ecfa;
}
.filters-actions{display:flex;gap:8px}
.filters .btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  height:42px;
  padding:0 14px;
  border-radius:12px;
  background:linear-gradient(135deg,#F5709D,#11FFC9);
  color:#1f2937;
  text-decoration:none;
  font-weight:900;
  font-size:.76rem;
  border:none;
  white-space:nowrap;
  cursor:pointer;
}
.filters .btn.secondary{
  background:rgba(255,255,255,.04);
  color:#c7cbdf;
  border:1px solid #313449;
  font-weight:700;
}
.filters .btn.secondary:hover{border-color:#4b4f6d;color:#e6e8f5}
.list-table{width:100%;border-collapse:collapse}
.list-table th{font-size:.65rem;color:var(--muted);letter-spacing:.08em;text-transform:uppercase;text-align:left;padding:12px 14px;background:var(--surface-2)}
.list-table td{padding:12px 14px;border-top:1px solid var(--border);font-size:.82rem}
.list-thumb{width:50px;height:50px;border-radius:10px;object-fit:cover;border:1px solid var(--border)}
.cat-badge{padding:4px 10px;border-radius:999px;font-size:.65rem;font-weight:800;text-transform:uppercase}
.cat-boys{background:rgba(59,130,246,.12);color:#60a5fa}.cat-girls{background:rgba(236,72,153,.12);color:#f472b6}.cat-couple{background:rgba(168,85,247,.12);color:#c084fc}.cat-family{background:rgba(52,211,153,.12);color:#34d399}.cat-creativity{background:rgba(250,204,21,.12);color:#eab308}
.toggle-vis{width:40px;height:22px;border-radius:100px;border:1.5px solid var(--border);background:var(--surface-2);cursor:pointer;position:relative;appearance:none}
.toggle-vis:checked{background:linear-gradient(135deg,#F5709D,#11FFC9);border-color:transparent}.toggle-vis::after{content:'';position:absolute;top:2px;left:3px;width:15px;height:15px;border-radius:50%;background:#fff;transition:left .2s}.toggle-vis:checked::after{left:19px}
.btn-del{background:var(--surface-2);border:1px solid var(--border);color:var(--muted);padding:7px 10px;border-radius:8px;cursor:pointer}
.btn-del:hover{background:rgba(245,112,157,.14);color:var(--soft)}
.btn-edit{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);color:var(--muted);text-decoration:none;margin-right:6px}
.btn-edit:hover,.btn-edit.active{background:rgba(17,255,201,.1);color:var(--soft);border-color:rgba(17,255,201,.25)}
.edit-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px;margin-bottom:24px}
.edit-card h2{font-size:1.05rem;font-weight:800;margin-bottom:8px;display:flex;align-items:center;gap:10px}
.edit-card h2 i{color:var(--accent)}
.edit-url{font-size:.78rem;color:var(--muted);margin-bottom:22px;word-break:break-all}
.edit-url a{color:var(--soft);text-decoration:none;font-weight:700}
.edit-url a:hover{text-decoration:underline}
.form-row{margin-bottom:20px}
.form-label{display:block;font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px}
.form-input,.form-textarea{width:100%;padding:12px 14px;border-radius:12px;border:1px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:.85rem}
.form-textarea{min-height:130px;resize:vertical;line-height:1.6}
.form-input:focus,.form-textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(245,112,157,.12)}
.form-hint{font-size:.7rem;color:var(--muted);margin-top:6px}
.cat-pills{display:flex;gap:10px;flex-wrap:wrap}
.cat-pill{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border:2px solid var(--border);border-radius:999px;background:var(--surface-2);color:#c5c5d0;font-weight:700;font-size:.8rem;cursor:pointer}
.cat-radio{display:none}
.cat-pill.active{border-color:var(--soft);color:var(--soft);background:rgba(17,255,201,.08)}
.tag-wrap{display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:10px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:12px;min-height:48px;cursor:text}
.tag-wrap:focus-within{border-color:var(--accent)}
.tag-input{background:none;border:none;outline:none;color:var(--text);font-size:.85rem;flex:1;min-width:100px}
.tag-chip{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;background:rgba(245,112,157,.14);border-radius:8px;font-size:.76rem;font-weight:700;color:var(--soft)}
.tag-chip-x{cursor:pointer;opacity:.7}
.upload-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.file-zone{border:2px dashed var(--border);border-radius:14px;padding:18px 14px;text-align:center;color:var(--muted);font-size:.76rem;position:relative;background:var(--surface-2);min-height:150px;display:flex;flex-direction:column;align-items:center;justify-content:center}
.file-zone input{position:absolute;inset:0;opacity:0;cursor:pointer}
.file-zone img.preview{max-height:120px;border-radius:10px;margin-top:10px;object-fit:cover}
.file-zone.dim{opacity:.4}
.check-row{display:flex;align-items:center;gap:10px;margin-top:10px;padding:10px 12px;background:rgba(0,0,0,.15);border-radius:10px;border:1px solid var(--border)}
.check-row label{font-size:.76rem;font-weight:600;color:#c5c5d0;cursor:pointer}
.edit-actions{display:flex;gap:10px;margin-top:24px;flex-wrap:wrap}
.edit-actions .btn{display:inline-flex;align-items:center;justify-content:center;height:42px;padding:0 18px;border-radius:12px;background:linear-gradient(135deg,#F5709D,#11FFC9);color:#1f2937;text-decoration:none;font-weight:900;font-size:.8rem;border:none;cursor:pointer}
.edit-actions .btn.secondary{background:rgba(255,255,255,.04);color:#c7cbdf;border:1px solid #313449;font-weight:700}
.toast-err{margin-bottom:16px;padding:12px 14px;border-radius:10px;background:rgba(245,112,157,.1);border:1px solid rgba(245,112,157,.2);color:var(--soft);font-weight:700;font-size:.82rem}
@media(max-width:1200px){.filters{grid-template-columns:repeat(3,minmax(0,1fr))}.filters-actions{grid-column:1/-1}.upload-grid{grid-template-columns:1fr}}
@media(max-width:900px){.sidebar{display:none}.main{margin:0;padding:16px}.filters{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:620px){.filters-actions{width:100%}.filters .btn{flex:1}}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sb-brand"><i class="fa-solid fa-wand-magic-sparkles"></i> Curated AI Prompts</div>
  <div class="sb-sec">Curated AI Prompts</div>
  <a href="curated_admin.php" class="sb-link"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
  <a href="curated_manage.php" class="sb-link active"><i class="fa-solid fa-table-list"></i> <span>Manage Prompts</span></a>
  <a href="curated_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
  <a href="curated_ai_prompts.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Page</span></a>
  <div class="sb-sec">Back</div>
  <a href="dashboard.php" class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
</aside>

<div class="main">
  <div class="head"><h1><i class="fa-solid fa-table-list"></i> Manage Prompts (<?= count($filtered) ?> / <?= count($prompts) ?>)</h1></div>
  <?php if ($msg): ?><div class="toast"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="toast-err"><i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <?php if ($edit_row): ?>
  <div class="edit-card">
    <h2><i class="fa-solid fa-pen-to-square"></i> Edit Prompt — <?= htmlspecialchars($edit_row['title']) ?></h2>
    <p class="edit-url">URL: <a href="<?= htmlspecialchars(nm_prompt_url($edit_row)) ?>" target="_blank"><?= htmlspecialchars(nm_prompt_url($edit_row)) ?></a></p>
    <form method="POST" enctype="multipart/form-data" id="editForm">
      <input type="hidden" name="action" value="edit_prompt">
      <input type="hidden" name="edit_id" value="<?= (int) $edit_row['id'] ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

      <div class="form-row">
        <label class="form-label">Category</label>
        <div class="cat-pills">
          <?php foreach ([
              'boys' => 'Boys', 'girls' => 'Girls', 'couple' => 'Couple',
              'family' => 'Family', 'creativity' => 'Creativity',
          ] as $val => $label): ?>
          <label>
            <input type="radio" name="category" value="<?= $val ?>" class="cat-radio" <?= ($edit_row['category'] ?? '') === $val ? 'checked' : '' ?>>
            <span class="cat-pill<?= ($edit_row['category'] ?? '') === $val ? ' active' : '' ?>"><?= $label ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-row">
        <label class="form-label" for="edit_title">Title</label>
        <input type="text" id="edit_title" name="title" class="form-input" maxlength="200" required value="<?= htmlspecialchars($edit_row['title'] ?? '') ?>">
      </div>

      <div class="form-row">
        <label class="form-label">Tags (max 2)</label>
        <div class="tag-wrap" id="tagWrap">
          <div id="tagChips"></div>
          <input type="text" class="tag-input" id="tagInput" placeholder="Type tag and press Enter">
        </div>
        <input type="hidden" name="tags" id="tagsHidden" value="<?= htmlspecialchars($edit_row['tags'] ?? '') ?>">
        <p class="form-hint" id="tagHint">0 / 2 tags</p>
      </div>

      <div class="form-row">
        <label class="form-label" for="edit_prompt_text">Prompt Text</label>
        <textarea id="edit_prompt_text" name="prompt_text" class="form-textarea" required><?= htmlspecialchars($edit_row['prompt_text'] ?? '') ?></textarea>
      </div>

      <div class="form-row">
        <label class="form-label" for="meta_description">SEO Description</label>
        <textarea id="meta_description" name="meta_description" class="form-textarea" style="min-height:90px" maxlength="500" placeholder="Google meta + page bottom About this prompt"><?= htmlspecialchars($edit_row['meta_description'] ?? '') ?></textarea>
      </div>

      <div class="form-row">
        <label class="form-label" for="meta_keywords">SEO Keywords</label>
        <input id="meta_keywords" type="text" name="meta_keywords" class="form-input" maxlength="500" value="<?= htmlspecialchars($edit_row['meta_keywords'] ?? '') ?>" placeholder="comma, separated, keywords">
      </div>

      <div class="form-row">
        <label class="form-label">Images — leave empty to keep current</label>
        <div class="upload-grid">
          <div>
            <p class="form-label" style="margin-bottom:8px">Thumbnail</p>
            <div class="file-zone" id="zoneThumb">
              <div>Replace thumbnail</div>
              <input type="file" name="thumbnail" accept="image/*">
              <img class="preview" id="prevThumb" src="<?= htmlspecialchars($edit_row['thumbnail_image'] ?? '') ?>" alt="">
            </div>
          </div>
          <div>
            <p class="form-label" style="margin-bottom:8px">ChatGPT Result</p>
            <div class="file-zone<?= !empty($edit_row['chatgpt_failed']) ? ' dim' : '' ?>" id="zoneCgpt">
              <div>Replace ChatGPT image</div>
              <input type="file" name="chatgpt_image" accept="image/*" id="cgptFile" <?= !empty($edit_row['chatgpt_failed']) ? 'disabled' : '' ?>>
              <?php if (!empty($edit_row['chatgpt_image']) && empty($edit_row['chatgpt_failed'])): ?>
              <img class="preview" id="prevCgpt" src="<?= htmlspecialchars($edit_row['chatgpt_image']) ?>" alt="">
              <?php else: ?>
              <img class="preview" id="prevCgpt" style="display:none" alt="">
              <?php endif; ?>
            </div>
            <div class="check-row">
              <input type="checkbox" name="chatgpt_failed" id="cgptFail" value="1" <?= !empty($edit_row['chatgpt_failed']) ? 'checked' : '' ?>>
              <label for="cgptFail">ChatGPT couldn't generate</label>
            </div>
          </div>
          <div>
            <p class="form-label" style="margin-bottom:8px">Gemini Result</p>
            <div class="file-zone<?= !empty($edit_row['gemini_failed']) ? ' dim' : '' ?>" id="zoneGem">
              <div>Replace Gemini image</div>
              <input type="file" name="gemini_image" accept="image/*" id="gemFile" <?= !empty($edit_row['gemini_failed']) ? 'disabled' : '' ?>>
              <?php if (!empty($edit_row['gemini_image']) && empty($edit_row['gemini_failed'])): ?>
              <img class="preview" id="prevGem" src="<?= htmlspecialchars($edit_row['gemini_image']) ?>" alt="">
              <?php else: ?>
              <img class="preview" id="prevGem" style="display:none" alt="">
              <?php endif; ?>
            </div>
            <div class="check-row">
              <input type="checkbox" name="gemini_failed" id="gemFail" value="1" <?= !empty($edit_row['gemini_failed']) ? 'checked' : '' ?>>
              <label for="gemFail">Gemini couldn't generate</label>
            </div>
          </div>
        </div>
      </div>

      <div class="edit-actions">
        <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i>&nbsp;Save Changes</button>
        <a href="curated_manage.php" class="btn secondary">Cancel</a>
        <a href="<?= htmlspecialchars(nm_prompt_url($edit_row)) ?>" class="btn secondary" target="_blank"><i class="fa-solid fa-eye"></i>&nbsp;View Page</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="list-card">
    <h2>All Curated AI Prompts</h2>
    <form method="GET" class="filters">
      <input type="text" name="q" value="<?= htmlspecialchars($f_q) ?>" placeholder="Search title or tags">
      <select name="cat">
        <option value="all" <?= $f_cat === 'all' ? 'selected' : '' ?>>Category: All</option>
        <option value="boys" <?= $f_cat === 'boys' ? 'selected' : '' ?>>Category: Boys</option>
        <option value="girls" <?= $f_cat === 'girls' ? 'selected' : '' ?>>Category: Girls</option>
        <option value="couple" <?= $f_cat === 'couple' ? 'selected' : '' ?>>Category: Couple</option>
        <option value="family" <?= $f_cat === 'family' ? 'selected' : '' ?>>Category: Family</option>
        <option value="creativity" <?= $f_cat === 'creativity' ? 'selected' : '' ?>>Category: Creativity</option>
      </select>
      <select name="gpt">
        <option value="all" <?= $f_gpt === 'all' ? 'selected' : '' ?>>GPT: All</option>
        <option value="ok" <?= $f_gpt === 'ok' ? 'selected' : '' ?>>GPT: OK</option>
        <option value="failed" <?= $f_gpt === 'failed' ? 'selected' : '' ?>>GPT: Failed</option>
      </select>
      <select name="gem">
        <option value="all" <?= $f_gem === 'all' ? 'selected' : '' ?>>Gemini: All</option>
        <option value="ok" <?= $f_gem === 'ok' ? 'selected' : '' ?>>Gemini: OK</option>
        <option value="failed" <?= $f_gem === 'failed' ? 'selected' : '' ?>>Gemini: Failed</option>
      </select>
      <select name="vis">
        <option value="all" <?= $f_vis === 'all' ? 'selected' : '' ?>>Visibility: All</option>
        <option value="visible" <?= $f_vis === 'visible' ? 'selected' : '' ?>>Visibility: Visible</option>
        <option value="hidden" <?= $f_vis === 'hidden' ? 'selected' : '' ?>>Visibility: Hidden</option>
      </select>
      <div class="filters-actions">
        <button type="submit" class="btn"><i class="fa-solid fa-filter"></i>&nbsp;Apply</button>
        <a href="curated_manage.php" class="btn secondary">Reset</a>
      </div>
    </form>
    <?php if (empty($filtered)): ?>
      <div style="padding:30px;color:var(--muted)">No prompts uploaded yet.</div>
    <?php else: ?>
    <table class="list-table">
      <thead><tr><th>Thumb</th><th>Title</th><th>Category</th><th>GPT</th><th>Gemini</th><th>Visible</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($filtered as $p): ?>
        <tr>
          <td><img src="<?= htmlspecialchars($p['thumbnail_image']) ?>" class="list-thumb" alt=""></td>
          <td style="font-weight:700;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['title']) ?></td>
          <td><span class="cat-badge cat-<?= htmlspecialchars($p['category']) ?>"><?= htmlspecialchars(ucfirst($p['category'])) ?></span></td>
          <td><?= $p['chatgpt_failed'] ? '<span style="color:#fda4af;font-weight:700;font-size:.72rem">FAILED</span>' : '<i class="fa-solid fa-check" style="color:#34d399"></i>' ?></td>
          <td><?= $p['gemini_failed'] ? '<span style="color:#fda4af;font-weight:700;font-size:.72rem">FAILED</span>' : '<i class="fa-solid fa-check" style="color:#34d399"></i>' ?></td>
          <td><input type="checkbox" class="toggle-vis" <?= $p['is_visible'] ? 'checked' : '' ?> onchange="toggleVis(<?= (int)$p['id'] ?>, this.checked)"></td>
          <td>
            <a href="curated_manage.php?edit=<?= (int) $p['id'] ?>" class="btn-edit<?= $edit_id === (int) $p['id'] ? ' active' : '' ?>" title="Edit prompt"><i class="fa-solid fa-pen"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this prompt?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="del_id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <button type="submit" class="btn-del"><i class="fa-solid fa-trash-can"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<script>
function toggleVis(id, checked) {
  fetch('curated_manage.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=toggle&tid=' + id + '&val=' + (checked ? 1 : 0)
  });
}

<?php if ($edit_row): ?>
(function () {
  document.querySelectorAll('.cat-radio').forEach(function (r) {
    r.addEventListener('change', function () {
      document.querySelectorAll('.cat-pill').forEach(function (p) { p.classList.remove('active'); });
      if (r.checked) r.parentElement.querySelector('.cat-pill').classList.add('active');
    });
  });

  var tags = (document.getElementById('tagsHidden').value || '').split(',').map(function (t) { return t.trim(); }).filter(Boolean).slice(0, 2);
  var tagInput = document.getElementById('tagInput');
  var tagChips = document.getElementById('tagChips');
  var tagsHidden = document.getElementById('tagsHidden');
  var tagHint = document.getElementById('tagHint');

  function renderTags() {
    tagChips.innerHTML = '';
    tags.forEach(function (t, i) {
      var chip = document.createElement('span');
      chip.className = 'tag-chip';
      chip.innerHTML = t + ' <span class="tag-chip-x" data-i="' + i + '">&times;</span>';
      tagChips.appendChild(chip);
    });
    tagsHidden.value = tags.join(', ');
    tagHint.textContent = tags.length + ' / 2 tags';
    tagInput.style.display = tags.length >= 2 ? 'none' : '';
  }

  tagInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      var val = tagInput.value.trim().replace(/,/g, '');
      if (val && tags.length < 2 && tags.indexOf(val) === -1) {
        tags.push(val);
        tagInput.value = '';
        renderTags();
      }
    }
    if (e.key === 'Backspace' && tagInput.value === '' && tags.length) {
      tags.pop();
      renderTags();
    }
  });

  tagChips.addEventListener('click', function (e) {
    var x = e.target.closest('.tag-chip-x');
    if (x) {
      tags.splice(parseInt(x.dataset.i, 10), 1);
      renderTags();
    }
  });

  document.getElementById('tagWrap').addEventListener('click', function () { tagInput.focus(); });
  renderTags();

  function previewFile(input, imgId) {
    var img = document.getElementById(imgId);
    if (!img || !input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      img.src = e.target.result;
      img.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }

  var thumbInput = document.querySelector('#zoneThumb input[type="file"]');
  if (thumbInput) thumbInput.addEventListener('change', function () { previewFile(this, 'prevThumb'); });

  var cgptFile = document.getElementById('cgptFile');
  if (cgptFile) cgptFile.addEventListener('change', function () { previewFile(this, 'prevCgpt'); });

  var gemFile = document.getElementById('gemFile');
  if (gemFile) gemFile.addEventListener('change', function () { previewFile(this, 'prevGem'); });

  var cgptFail = document.getElementById('cgptFail');
  if (cgptFail) cgptFail.addEventListener('change', function () {
    cgptFile.disabled = this.checked;
    document.getElementById('zoneCgpt').classList.toggle('dim', this.checked);
  });

  var gemFail = document.getElementById('gemFail');
  if (gemFail) gemFail.addEventListener('change', function () {
    gemFile.disabled = this.checked;
    document.getElementById('zoneGem').classList.toggle('dim', this.checked);
  });
})();
<?php endif; ?>
</script>
</body>
</html>
