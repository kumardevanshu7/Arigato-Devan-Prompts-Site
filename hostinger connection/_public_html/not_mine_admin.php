<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';
require_once 'slug_helper.php';
$image_helpers_ready = false;
if (is_file(__DIR__ . '/includes/image_helpers.php')) {
    require_once 'includes/image_helpers.php';
    $image_helpers_ready = true;
}

function nm_table_exists_admin(PDO $pdo, string $table): bool
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

$admin_name = $_SESSION['username'] ?? 'Admin';
$msg = '';
$err = '';
$uploaded_share = null;

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    verify_csrf();
    if (!$image_helpers_ready) {
        $err = 'Image helpers missing on server. Upload includes/image_helpers.php first.';
    }
    if (!$err && !nm_table_exists_admin($pdo, 'not_mine_prompts')) {
        $err = 'not_mine_prompts table missing in live DB. Run DB SQL first.';
    }

    $category       = in_array($_POST['category'] ?? '', ['boys','girls','couple','family','creativity']) ? $_POST['category'] : '';
    $title          = trim($_POST['title'] ?? '');
    $raw_tags       = trim($_POST['tags'] ?? '');
    $prompt_text    = trim($_POST['prompt_text'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $meta_keywords  = trim($_POST['meta_keywords'] ?? '');
    $chatgpt_failed = !empty($_POST['chatgpt_failed']) ? 1 : 0;
    $gemini_failed  = !empty($_POST['gemini_failed']) ? 1 : 0;

    if (!$category || !$title || !$prompt_text) {
        $err = 'Category, Title, and Prompt Text are required.';
    } else {
        $tags_arr = array_filter(array_map('trim', explode(',', $raw_tags)));
        $tags_arr = array_slice($tags_arr, 0, 2);
        $tags_str = implode(', ', $tags_arr);

        $thumb = not_mine_upload_image($_FILES['thumbnail'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'thumb', 600, 800);
        if (!$thumb) {
            $err = 'Thumbnail image is required.';
        } else {
            $chatgpt_img = null;
            if (!$chatgpt_failed) {
                $chatgpt_img = not_mine_upload_image($_FILES['chatgpt_image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'cgpt', 900, 1600);
                if (!$chatgpt_img) {
                    $err = 'ChatGPT result image is required (or tick the checkbox).';
                }
            }
            $gemini_img = null;
            if (!$err && !$gemini_failed) {
                $gemini_img = not_mine_upload_image($_FILES['gemini_image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'gem', 900, 1600);
                if (!$gemini_img) {
                    $err = 'Gemini result image is required (or tick the checkbox).';
                }
            }

            if (!$err) {
                $slug = uniqueNotMineSlug($pdo, $title);
                $stmt = $pdo->prepare(
                    'INSERT INTO not_mine_prompts (category, title, slug, tags, prompt_text, meta_description, meta_keywords, thumbnail_image, chatgpt_image, chatgpt_failed, gemini_image, gemini_failed)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$category, $title, $slug, $tags_str, $prompt_text, $meta_description ?: null, $meta_keywords, $thumb, $chatgpt_img, $chatgpt_failed, $gemini_img, $gemini_failed]);
                $new_id = (int) $pdo->lastInsertId();
                $uploaded_share = [
                    'title' => $title,
                    'slug' => $slug,
                    'id' => $new_id,
                    'url' => nm_prompt_share_url(['id' => $new_id, 'slug' => $slug]),
                ];
                $msg = 'Prompt uploaded successfully!';
            }
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $del_id = (int) ($_POST['del_id'] ?? 0);
    if ($del_id > 0) {
        $row = $pdo->prepare('SELECT thumbnail_image, chatgpt_image, gemini_image FROM not_mine_prompts WHERE id = ?');
        $row->execute([$del_id]);
        if ($item = $row->fetch(PDO::FETCH_ASSOC)) {
            foreach (['thumbnail_image', 'chatgpt_image', 'gemini_image'] as $col) {
                if (!empty($item[$col]) && is_file($item[$col])) {
                    @unlink($item[$col]);
                }
            }
        }
        $pdo->prepare('DELETE FROM not_mine_prompts WHERE id = ?')->execute([$del_id]);
        $pdo->prepare('DELETE FROM not_mine_votes WHERE prompt_id = ?')->execute([$del_id]);
        $pdo->prepare('DELETE FROM not_mine_likes WHERE prompt_id = ?')->execute([$del_id]);
        $msg = 'Prompt deleted.';
    }
}

// Handle toggle visibility
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $tid = (int) ($_POST['tid'] ?? 0);
    $val = (int) ($_POST['val'] ?? 0) ? 1 : 0;
    $pdo->prepare('UPDATE not_mine_prompts SET is_visible = ? WHERE id = ?')->execute([$val, $tid]);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit();
}

$prompts = [];
if (nm_table_exists_admin($pdo, 'not_mine_prompts')) {
    try {
        $prompts = $pdo->query('SELECT * FROM not_mine_prompts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $prompts = [];
    }
}
$csrf = generate_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Not Mine — Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
<?php include_once 'gtag.php'; ?>
<style>
:root {
    --bg: #0b0b10;
    --surface: #141419;
    --surface-2: #1a1a22;
    --border: #252530;
    --border-hover: #35354a;
    --text: #ededf0;
    --text-2: #c5c5d0;
    --muted: #72728a;
    --accent: #F5709D;
    --accent-soft: #11FFC9;
    --accent-dim: rgba(245,112,157,0.08);
    --accent-dim2: rgba(245,112,157,0.16);
    --accent-glow: 0 0 20px rgba(245,112,157,0.2);
    --accent-gradient: linear-gradient(135deg, #F5709D, #11FFC9);
    --green: #34d399;
    --radius: 14px;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Inter', -apple-system, sans-serif;
    line-height: 1.5;
    -webkit-font-smoothing: antialiased;
}

/* ──── Sidebar ──── */
.sidebar {
    position: fixed; top: 0; left: 0; bottom: 0; width: 250px;
    background: var(--surface); border-right: 1px solid var(--border);
    padding: 28px 16px; overflow-y: auto; z-index: 100;
    display: flex; flex-direction: column;
}
.sb-brand { display: flex; align-items: center; gap: 10px; font-weight: 900; font-size: 1.05rem;
    color: var(--accent); margin-bottom: 32px; padding: 0 8px; }
.sb-brand i { font-size: 1.1rem; }
.sb-sec { font-size: .6rem; font-weight: 700; color: var(--muted); text-transform: uppercase;
    letter-spacing: .14em; margin: 24px 0 10px; padding: 0 8px; }
.sb-link { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px;
    font-size: .82rem; font-weight: 600; color: var(--muted); text-decoration: none;
    transition: all .2s; margin-bottom: 2px; border: 1px solid transparent; }
.sb-link:hover { background: var(--surface-2); color: var(--text-2); }
.sb-link.active { background: var(--accent-dim2); color: var(--accent-soft); border-color: rgba(245,112,157,0.15);
    box-shadow: var(--accent-glow); }
.sb-link i { width: 18px; text-align: center; flex-shrink: 0; font-size: .82rem; }

/* ──── Main ──── */
.main { margin-left: 250px; padding: 40px 48px 100px; max-width: 920px; }

.page-header { margin-bottom: 36px; }
.page-header h1 { font-size: 1.6rem; font-weight: 900; letter-spacing: -.02em; margin-bottom: 6px; }
.page-header h1 i { color: var(--accent); margin-right: 4px; }
.page-sub { color: var(--muted); font-size: .85rem; font-weight: 500; }

/* ──── Toast ──── */
.toast { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-radius: var(--radius);
    font-size: .82rem; font-weight: 700; margin-bottom: 24px; }
.toast-ok { background: rgba(52,211,153,0.08); color: var(--green); border: 1px solid rgba(52,211,153,0.15); }
.toast-err { background: var(--accent-dim2); color: var(--accent-soft); border: 1px solid rgba(245,112,157,0.15); }

/* ──── Form Card ──── */
.form-card {
    background: var(--surface); border: 1px solid var(--border); border-radius: 18px;
    padding: 36px; margin-bottom: 48px; box-shadow: 0 4px 24px rgba(0,0,0,.2);
}
.form-card h2 { font-size: 1.1rem; font-weight: 800; margin-bottom: 28px; display: flex; align-items: center; gap: 10px;
    padding-bottom: 18px; border-bottom: 1px solid var(--border); }
.form-card h2 i { color: var(--accent); font-size: 1rem; }

.form-row { margin-bottom: 24px; }
.form-label { display: block; font-size: .72rem; font-weight: 700; color: var(--muted); text-transform: uppercase;
    letter-spacing: .08em; margin-bottom: 8px; }

.form-input, .form-select, .form-textarea {
    width: 100%; padding: 12px 16px; background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 12px; color: var(--text); font-family: inherit; font-size: .88rem;
    transition: border-color .2s, box-shadow .2s;
}
.form-input::placeholder, .form-textarea::placeholder { color: var(--muted); }
.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
.form-textarea { min-height: 140px; resize: vertical; line-height: 1.6; }

/* Category pills */
.cat-pills { display: flex; gap: 10px; flex-wrap: wrap; }
.cat-pill { display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 22px; border: 2px solid var(--border); border-radius: 999px; background: var(--surface-2);
    color: var(--text-2); font-weight: 700; font-size: .82rem; cursor: pointer; transition: all .25s; }
.cat-pill i { font-size: .88rem; opacity: .9; width: 1em; text-align: center; }
.cat-pill:hover { filter: brightness(1.08); }
.cat-pill.active { font-weight: 800; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
input[type="radio"].cat-radio { display: none; }

.cat-pill-boys { background: rgba(59,130,246,0.12); border-color: rgba(96,165,250,0.22); color: #93c5fd; }
.cat-pill-boys i { color: #60a5fa; }
.cat-pill-boys:hover { background: rgba(59,130,246,0.18); border-color: rgba(96,165,250,0.35); }
.cat-pill-boys.active { background: rgba(59,130,246,0.24); border-color: #60a5fa; color: #dbeafe; box-shadow: 0 0 20px rgba(59,130,246,0.18); }

.cat-pill-girls { background: rgba(236,72,153,0.12); border-color: rgba(244,114,182,0.22); color: #f9a8d4; }
.cat-pill-girls i { color: #f472b6; }
.cat-pill-girls:hover { background: rgba(236,72,153,0.18); border-color: rgba(244,114,182,0.35); }
.cat-pill-girls.active { background: rgba(236,72,153,0.24); border-color: #f472b6; color: #fce7f3; box-shadow: 0 0 20px rgba(236,72,153,0.18); }

.cat-pill-couple { background: rgba(168,85,247,0.12); border-color: rgba(192,132,252,0.22); color: #d8b4fe; }
.cat-pill-couple i { color: #c084fc; }
.cat-pill-couple:hover { background: rgba(168,85,247,0.18); border-color: rgba(192,132,252,0.35); }
.cat-pill-couple.active { background: rgba(168,85,247,0.24); border-color: #c084fc; color: #ede9fe; box-shadow: 0 0 20px rgba(168,85,247,0.18); }

.cat-pill-family { background: rgba(52,211,153,0.12); border-color: rgba(110,231,183,0.22); color: #6ee7b7; }
.cat-pill-family i { color: #34d399; }
.cat-pill-family:hover { background: rgba(52,211,153,0.18); border-color: rgba(110,231,183,0.35); }
.cat-pill-family.active { background: rgba(52,211,153,0.24); border-color: #34d399; color: #d1fae5; box-shadow: 0 0 20px rgba(52,211,153,0.18); }

.cat-pill-creativity { background: rgba(250,204,21,0.12); border-color: rgba(250,204,21,0.28); color: #fde68a; }
.cat-pill-creativity i { color: #facc15; }
.cat-pill-creativity:hover { background: rgba(250,204,21,0.18); border-color: rgba(250,204,21,0.38); }
.cat-pill-creativity.active { background: rgba(250,204,21,0.24); border-color: #facc15; color: #fef9c3; box-shadow: 0 0 20px rgba(250,204,21,0.18); }

/* File upload zone */
.file-zone { border: 2px dashed var(--border); border-radius: 16px; padding: 32px 24px; text-align: center;
    color: var(--muted); font-size: .82rem; font-weight: 500; cursor: pointer; transition: all .25s;
    position: relative; background: var(--surface-2); }
.file-zone:hover { border-color: var(--accent); background: var(--accent-dim); }
.file-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.file-zone .preview { max-height: 180px; border-radius: 12px; margin-top: 14px; box-shadow: 0 4px 16px rgba(0,0,0,.3); }
.file-zone i { font-size: 1.8rem; margin-bottom: 10px; display: block; opacity: .6; }
.file-zone div { font-weight: 600; }

/* Checkbox row */
.check-row { display: flex; align-items: center; gap: 10px; margin-top: 12px; padding: 10px 14px;
    background: var(--surface-2); border-radius: 10px; border: 1px solid var(--border); }
.check-row input[type="checkbox"] { accent-color: var(--accent); width: 18px; height: 18px; flex-shrink: 0; }
.check-row label { font-size: .78rem; color: var(--text-2); font-weight: 600; cursor: pointer; }

/* Validation error */
.field-error { display: flex; align-items: center; gap: 6px; margin-top: 10px; font-size: .75rem; font-weight: 700;
    color: var(--accent-soft); animation: shake .4s; }
@keyframes shake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-6px)} 75%{transform:translateX(6px)} }

/* Tag input */
.tag-input-wrap { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 10px 14px;
    background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; min-height: 48px;
    cursor: text; transition: border-color .2s, box-shadow .2s; }
.tag-input-wrap:focus-within { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
.tag-input { background: none; border: none; outline: none; color: var(--text); font-family: inherit; font-size: .88rem;
    flex: 1; min-width: 100px; padding: 2px 0; }
.tag-input::placeholder { color: var(--muted); }
.tag-chip { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; background: var(--accent-dim2);
    border: 1px solid rgba(245,112,157,0.2); border-radius: 8px; font-size: .78rem; font-weight: 700; color: var(--accent-soft); }
.tag-chip-x { cursor: pointer; font-size: .9rem; opacity: .6; transition: opacity .15s; line-height: 1; }
.tag-chip-x:hover { opacity: 1; }
.tag-hint { font-size: .7rem; color: var(--muted); margin-top: 6px; font-weight: 600; }

/* Submit button */
.btn-submit { display: inline-flex; align-items: center; gap: 10px; padding: 14px 36px; background: var(--accent-gradient);
    color: #2F4156; border: none; border-radius: 14px; font-weight: 800; font-size: .9rem; cursor: pointer;
    transition: all .25s; letter-spacing: .02em; box-shadow: var(--accent-glow); }
.btn-submit:hover { filter: brightness(1.06); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(245,112,157,0.28); }
.btn-submit:active { transform: translateY(0); }

/* ──── Manage List ──── */
.list-card { background: var(--surface); border: 1px solid var(--border); border-radius: 18px;
    overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.2); }
.list-card h2 { padding: 22px 28px; font-size: 1.05rem; font-weight: 800; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between; }
.list-card h2 .count-badge { font-size: .7rem; padding: 3px 10px; background: var(--accent-dim2); color: var(--accent-soft);
    border-radius: 99px; font-weight: 800; }
.list-table { width: 100%; border-collapse: collapse; }
.list-table th { text-align: left; font-size: .65rem; font-weight: 700; color: var(--muted); text-transform: uppercase;
    letter-spacing: .08em; padding: 14px 18px; border-bottom: 1px solid var(--border); background: var(--surface-2); }
.list-table td { padding: 14px 18px; border-bottom: 1px solid var(--border); font-size: .82rem; vertical-align: middle; }
.list-table tr:last-child td { border-bottom: none; }
.list-table tr:hover td { background: var(--surface-2); }
.list-thumb { width: 52px; height: 52px; border-radius: 12px; object-fit: cover;
    border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,.2); }
.cat-badge { padding: 4px 12px; border-radius: 999px; font-size: .68rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .04em; }
.cat-boys { background: rgba(59,130,246,0.1); color: #60a5fa; }
.cat-girls { background: rgba(236,72,153,0.1); color: #f472b6; }
.cat-couple { background: rgba(168,85,247,0.1); color: #c084fc; }
.cat-family { background: rgba(52,211,153,0.1); color: var(--green); }
.cat-creativity { background: rgba(250,204,21,0.1); color: #eab308; }
.status-ok { color: var(--green); font-size: .9rem; }
.status-fail { font-size: .68rem; font-weight: 800; color: var(--accent-soft); background: var(--accent-dim2);
    padding: 3px 8px; border-radius: 6px; }
.toggle-vis { width: 40px; height: 22px; border-radius: 100px; border: 1.5px solid var(--border); background: var(--surface-2);
    cursor: pointer; position: relative; transition: all .3s; appearance: none; }
.toggle-vis:checked { background: var(--accent-gradient); border-color: transparent; box-shadow: var(--accent-glow); }
.toggle-vis::after { content: ''; position: absolute; top: 2px; left: 3px; width: 15px; height: 15px;
    border-radius: 50%; background: #fff; transition: left .25s cubic-bezier(.4,0,.2,1); box-shadow: 0 1px 4px rgba(0,0,0,.2); }
.toggle-vis:checked::after { left: 19px; }
.btn-del { background: var(--surface-2); border: 1px solid var(--border); color: var(--muted); padding: 7px 12px;
    border-radius: 8px; font-size: .75rem; font-weight: 700; cursor: pointer; transition: all .2s; }
.btn-del:hover { background: var(--accent-dim2); border-color: rgba(245,112,157,0.3); color: var(--accent-soft); }
.empty-list { text-align: center; padding: 50px; color: var(--muted); }
.empty-list i { font-size: 2rem; margin-bottom: 10px; display: block; opacity: .5; }

/* ──── Two-column upload (ChatGPT + Gemini side by side) ──── */
.upload-pair { display: grid; grid-template-columns: 1fr 1fr; column-gap: 48px; row-gap: 0; align-items: stretch; margin-bottom: 28px; }
.upload-pair .form-row { margin-bottom: 0; display: flex; flex-direction: column; height: 100%; min-width: 0; }
.upload-pair .form-label { flex-shrink: 0; min-height: 36px; display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.upload-pair .form-label .ai-logo-img { width: 14px; height: 14px; object-fit: contain; border-radius: 3px; flex-shrink: 0; }
.upload-pair .form-label .ai-banana { color: #facc15; font-size: .86rem; width: 14px; text-align: center; flex-shrink: 0; }
.upload-pair .file-zone { flex: 1 1 auto; min-height: 130px; margin-bottom: 18px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.upload-pair .file-zone .ai-logo-img { width: 22px; height: 22px; object-fit: contain; border-radius: 4px; margin-bottom: 10px; display: block; opacity: .92; }
.upload-pair .file-zone .ai-banana { font-size: 1.5rem; color: #facc15; margin-bottom: 8px; display: block; opacity: .9; }
.upload-pair .check-row { flex-shrink: 0; margin-top: auto; }
.form-submit-wrap { margin-top: 28px; }

.share-box {
    background: rgba(17, 255, 201, 0.06);
    border: 1px solid rgba(17, 255, 201, 0.22);
    border-radius: 16px;
    padding: 18px 20px;
    margin-bottom: 24px;
}
.share-box h3 {
    font-size: .88rem;
    font-weight: 800;
    margin-bottom: 10px;
    color: var(--accent-soft);
    display: flex;
    align-items: center;
    gap: 8px;
}
.share-box p { font-size: .78rem; color: var(--text-2); margin-bottom: 12px; }
.share-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: stretch; }
.share-input {
    flex: 1;
    min-width: 200px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: var(--surface-2);
    color: var(--text);
    font-family: ui-monospace, Consolas, monospace;
    font-size: .78rem;
}
.copy-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 12px;
    border: 1px solid rgba(245, 112, 157, 0.25);
    background: var(--accent-dim2);
    color: var(--accent-soft);
    font-weight: 800;
    font-size: .8rem;
    cursor: pointer;
    font-family: inherit;
    white-space: nowrap;
}
.copy-link-btn:hover { filter: brightness(1.06); }
.copy-link-btn.copied { color: var(--green); border-color: rgba(52, 211, 153, 0.3); background: rgba(52, 211, 153, 0.08); }

/* ──── Form sections & submit ──── */
.form-section-label { display: none; font-size: .68rem; font-weight: 800; color: var(--muted); text-transform: uppercase;
    letter-spacing: .1em; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }

/* ──── Mobile top bar ──── */
.mob-bar { display: none; position: sticky; top: 0; z-index: 200;
    background: rgba(11,11,16,0.92); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border); padding: 14px 16px; padding-top: max(14px, env(safe-area-inset-top));
    align-items: center; justify-content: space-between; gap: 12px; }
.mob-bar-back { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px;
    border-radius: 11px; background: var(--surface-2); border: 1px solid var(--border); color: var(--text-2);
    text-decoration: none; font-size: .9rem; flex-shrink: 0; }
.mob-bar-title { font-size: .92rem; font-weight: 900; color: var(--text); display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; }
.mob-bar-title i { color: var(--accent-soft); flex-shrink: 0; }
.mob-bar-view { display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; border-radius: 11px;
    font-size: .72rem; font-weight: 800; text-decoration: none; color: var(--accent-soft);
    background: var(--accent-dim2); border: 1px solid rgba(245,112,157,0.2); flex-shrink: 0; }

/* ──── Mobile prompt cards ──── */
.prompt-cards { display: none; }
.prompt-card-m { background: var(--surface-2); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
.prompt-card-m + .prompt-card-m { margin-top: 12px; }
.pcm-top { display: flex; gap: 14px; padding: 14px; align-items: center; }
.prompt-card-m .list-thumb { width: 64px; height: 64px; border-radius: 14px; flex-shrink: 0; }
.pcm-info { flex: 1; min-width: 0; }
.pcm-title { font-weight: 800; font-size: .95rem; margin-bottom: 8px; line-height: 1.25;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pcm-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.pcm-tag { font-size: .62rem; font-weight: 800; padding: 4px 10px; border-radius: 99px; text-transform: uppercase; letter-spacing: .03em; }
.pcm-tag.ok { background: rgba(52,211,153,0.12); color: var(--green); }
.pcm-tag.fail { background: var(--accent-dim2); color: var(--accent-soft); }
.pcm-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 12px 14px; border-top: 1px solid var(--border); background: rgba(0,0,0,0.15); }
.pcm-vis { display: flex; align-items: center; gap: 10px; font-size: .75rem; font-weight: 700; color: var(--muted); }
.btn-del-m { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 10px;
    background: transparent; border: 1px solid rgba(245,112,157,0.25); color: var(--accent-soft);
    font-size: .72rem; font-weight: 700; cursor: pointer; font-family: inherit; }
.list-desktop { display: table; }

@media (max-width: 900px) {
    .upload-pair { grid-template-columns: 1fr; row-gap: 0; column-gap: 0; }
    .upload-pair .form-row { height: auto; }
    .upload-pair .check-row { margin-top: 0; }
}

@media (max-width: 768px) {
    .sidebar { display: none; }
    .mob-bar { display: flex; }

    .main {
        margin-left: 0; padding: 0 0 calc(88px + env(safe-area-inset-bottom, 0px));
        max-width: none;
    }

    .page-header { display: none; }

    .toast { margin: 12px 16px 0; font-size: .78rem; padding: 12px 14px; border-radius: 12px; }

    .form-card {
        margin: 12px 16px 16px; padding: 0; border-radius: 18px;
        overflow: visible; box-shadow: 0 8px 32px rgba(0,0,0,.25);
    }
    .form-card h2 {
        font-size: .95rem; margin: 0; padding: 18px 16px;
        border-bottom: 1px solid var(--border); background: var(--surface-2);
    }
    #uploadForm { padding: 16px; }

    .form-row { margin-bottom: 22px; }
    .form-label { font-size: .68rem; margin-bottom: 10px; }
    .form-input, .form-select, .form-textarea { font-size: 16px; padding: 13px 14px; border-radius: 12px; }
    .form-textarea { min-height: 120px; }

    .cat-pills { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .cat-pills label { display: block; }
    .cat-pill { display: flex; width: 100%; padding: 14px 12px; font-size: .8rem; border-radius: 12px; gap: 8px; }

    .file-zone { padding: 28px 16px; border-radius: 14px; }
    .file-zone i { font-size: 1.6rem; margin-bottom: 8px; }
    .file-zone .preview { max-height: 200px; width: auto; max-width: 100%; }

    .check-row { margin-top: 0; padding: 12px 14px; border-radius: 12px; }
    .check-row label { font-size: .78rem; line-height: 1.4; }

    .form-section-label { display: block; margin-top: 4px; }

    .upload-pair {
        margin: 0 -16px 0; padding: 0 16px;
        background: var(--surface-2); border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
    }
    .upload-pair .form-row {
        padding: 18px 0; margin-bottom: 0;
        border-bottom: 1px solid var(--border);
    }
    .upload-pair .form-row:last-child { border-bottom: none; }
    .upload-pair .form-label { min-height: auto; margin-bottom: 10px; font-size: .68rem; }
    .upload-pair .file-zone {
        min-height: 120px; margin-bottom: 14px; flex: none;
        background: var(--bg); border-color: var(--border-hover);
    }
    .upload-pair .check-row { margin-top: 0; background: var(--bg); }

    .form-submit-wrap {
        position: fixed; left: 0; right: 0; bottom: 0; z-index: 150; margin-top: 0;
        padding: 14px 16px calc(14px + env(safe-area-inset-bottom, 0px));
        background: rgba(11,11,16,0.94); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
        border-top: 1px solid var(--border); box-shadow: 0 -8px 32px rgba(0,0,0,.35);
    }
    .form-submit-wrap .btn-submit {
        width: 100%; justify-content: center; padding: 16px 24px;
        font-size: .92rem; border-radius: 14px; margin: 0;
    }

    .list-card {
        margin: 0 16px 24px; border-radius: 18px; overflow: hidden;
        box-shadow: 0 8px 32px rgba(0,0,0,.25);
    }
    .list-card h2 {
        padding: 18px 16px; font-size: .92rem; background: var(--surface-2);
    }
    .list-desktop { display: none; }
    .prompt-cards { display: block; padding: 12px 16px 16px; }

    .empty-list { padding: 40px 20px; }
}
</style>
</head>
<body>

<div class="mob-bar">
    <a href="dashboard.php" class="mob-bar-back" aria-label="Back to Dashboard"><i class="fa-solid fa-arrow-left"></i></a>
    <div class="mob-bar-title"><i class="fa-solid fa-ban"></i> Upload Prompt</div>
    <a href="not_mine.php" class="mob-bar-view" target="_blank"><i class="fa-solid fa-eye"></i> View</a>
</div>

<aside class="sidebar">
    <div class="sb-brand"><i class="fa-solid fa-ban"></i> Not Mine</div>
    <div class="sb-sec">Not Mine</div>
    <a href="not_mine_admin.php" class="sb-link active"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="not_mine_manage.php" class="sb-link"><i class="fa-solid fa-table-list"></i> <span>Manage Prompts</span></a>
    <a href="not_mine_links.php" class="sb-link"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="not_mine.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Page</span></a>
    <div class="sb-sec">Back</div>
    <a href="dashboard.php" class="sb-link"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
    <a href="index.php" class="sb-link" target="_blank"><i class="fa-solid fa-house"></i> <span>View Site</span></a>
</aside>

<div class="main">
    <div class="page-header">
        <h1><i class="fa-solid fa-ban"></i> Not Mine — Upload Prompts</h1>
        <p class="page-sub">Upload prompts from other creators. Compare ChatGPT vs Gemini results.</p>
    </div>

    <?php if ($msg): ?><div class="toast toast-ok"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="toast toast-err"><i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

    <?php if ($uploaded_share): ?>
    <div class="share-box" id="uploadShareBox">
        <h3><i class="fa-solid fa-link"></i> Share Link Ready</h3>
        <p><strong><?= htmlspecialchars($uploaded_share['title']) ?></strong> — copy karke share karo:</p>
        <div class="share-row">
            <input type="text" class="share-input" id="uploadShareUrl" readonly value="<?= htmlspecialchars($uploaded_share['url']) ?>">
            <button type="button" class="copy-link-btn" onclick="nmCopyShare('uploadShareUrl', this)"><i class="fa-solid fa-copy"></i> Copy Link</button>
            <a href="not_mine_links.php" class="copy-link-btn" style="text-decoration:none"><i class="fa-solid fa-list"></i> All Links</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="form-card" id="upload-form">
        <h2><i class="fa-solid fa-cloud-arrow-up"></i> Upload Not Your Prompt</h2>
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="action" value="upload">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="form-row">
                <label class="form-label">Category</label>
                <div class="cat-pills" id="catPills">
                    <?php foreach ([
                        'boys'       => ['icon' => 'fa-solid fa-mars', 'label' => 'Boys'],
                        'girls'      => ['icon' => 'fa-solid fa-venus', 'label' => 'Girls'],
                        'couple'     => ['icon' => 'fa-solid fa-heart', 'label' => 'Couple'],
                        'family'     => ['icon' => 'fa-solid fa-people-group', 'label' => 'Family'],
                        'creativity' => ['icon' => 'fa-solid fa-lightbulb', 'label' => 'Creativity'],
                    ] as $val => $cat): ?>
                    <label>
                        <input type="radio" name="category" value="<?= $val ?>" class="cat-radio">
                        <span class="cat-pill cat-pill-<?= $val ?>"><i class="<?= $cat['icon'] ?>"></i> <?= $cat['label'] ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <p class="field-error" id="catError" style="display:none"><i class="fa-solid fa-triangle-exclamation"></i> Please select a category</p>
            </div>

            <div class="form-row">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-input" placeholder="e.g. Aesthetic Boy in Rain" required maxlength="200">
            </div>

            <div class="form-row">
                <label class="form-label">Tags (max 2, press Enter to add)</label>
                <div class="tag-input-wrap" id="tagWrap">
                    <div class="tag-chips" id="tagChips"></div>
                    <input type="text" class="tag-input" id="tagInput" placeholder="Type a tag and press Enter..." maxlength="50">
                </div>
                <input type="hidden" name="tags" id="tagsHidden">
                <p class="tag-hint" id="tagHint">0 / 2 tags</p>
            </div>

            <div class="form-row">
                <label class="form-label">Prompt Text</label>
                <textarea name="prompt_text" class="form-textarea" placeholder="Paste the full prompt here..." required></textarea>
            </div>

            <div class="form-row">
                <label class="form-label">SEO Description <span style="font-weight:500;text-transform:none;letter-spacing:0;color:var(--muted)">— shows in Google + page bottom</span></label>
                <textarea name="meta_description" class="form-textarea" style="min-height:90px" maxlength="500" placeholder="Short description for search engines. Also shown at the bottom under “About this prompt”."></textarea>
                <p class="tag-hint">2–3 lines. Example: Compare ChatGPT vs Gemini for this aesthetic boy portrait prompt. Vote to unlock the full text.</p>
            </div>

            <div class="form-row">
                <label class="form-label">SEO Keywords</label>
                <input type="text" name="meta_keywords" class="form-input" maxlength="500" placeholder="e.g. ai boy prompt, chatgpt portrait, gemini vs chatgpt">
                <p class="tag-hint">Comma-separated keywords for meta tags.</p>
            </div>

            <div class="form-row">
                <label class="form-label">Main Thumbnail</label>
                <div class="file-zone" id="zoneThumb">
                    <i class="fa-solid fa-image"></i>
                    <div>Click or drop thumbnail here</div>
                    <input type="file" name="thumbnail" accept="image/*" required>
                    <img class="preview" id="prevThumb" style="display:none">
                </div>
            </div>

            <p class="form-section-label">AI Results — ChatGPT vs Gemini</p>
            <div class="upload-pair">
                <div class="form-row">
                    <label class="form-label"><img src="https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg" alt="" class="ai-logo-img"> ChatGPT Result (9:16)</label>
                    <div class="file-zone" id="zoneCgpt">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg" alt="" class="ai-logo-img">
                        <div>Upload ChatGPT result</div>
                        <input type="file" name="chatgpt_image" accept="image/*" id="cgptFile">
                        <img class="preview" id="prevCgpt" style="display:none">
                    </div>
                    <div class="check-row">
                        <input type="checkbox" name="chatgpt_failed" id="cgptFail" value="1">
                        <label for="cgptFail">ChatGPT couldn't generate</label>
                    </div>
                </div>

                <div class="form-row">
                    <label class="form-label"><i class="fa-solid fa-banana ai-banana"></i> Gemini Result (9:16)</label>
                    <div class="file-zone" id="zoneGem">
                        <i class="fa-solid fa-banana ai-banana"></i>
                        <div>Upload Gemini result</div>
                        <input type="file" name="gemini_image" accept="image/*" id="gemFile">
                        <img class="preview" id="prevGem" style="display:none">
                    </div>
                    <div class="check-row">
                        <input type="checkbox" name="gemini_failed" id="gemFail" value="1">
                        <label for="gemFail">Gemini couldn't generate</label>
                    </div>
                </div>
            </div>

            <div class="form-submit-wrap">
                <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Submit Prompt</button>
            </div>
        </form>
    </div>

</div>

<script>
// Category selection
document.querySelectorAll('.cat-radio').forEach(function(r) {
    r.addEventListener('change', function() {
        document.querySelectorAll('.cat-pill').forEach(function(p) { p.classList.remove('active'); });
        if (r.checked) r.parentElement.querySelector('.cat-pill').classList.add('active');
        document.getElementById('catError').style.display = 'none';
    });
});

// Tag chip input
var tags = [];
var tagInput = document.getElementById('tagInput');
var tagChips = document.getElementById('tagChips');
var tagsHidden = document.getElementById('tagsHidden');
var tagHint = document.getElementById('tagHint');
var MAX_TAGS = 2;

function renderTags() {
    tagChips.innerHTML = '';
    tags.forEach(function(t, i) {
        var chip = document.createElement('span');
        chip.className = 'tag-chip';
        chip.innerHTML = t + ' <span class="tag-chip-x" data-i="' + i + '">&times;</span>';
        tagChips.appendChild(chip);
    });
    tagsHidden.value = tags.join(', ');
    tagHint.textContent = tags.length + ' / ' + MAX_TAGS + ' tags';
    if (tags.length >= MAX_TAGS) {
        tagInput.style.display = 'none';
    } else {
        tagInput.style.display = '';
        tagInput.placeholder = tags.length === 0 ? 'Type a tag and press Enter...' : 'Add one more...';
    }
}

tagInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        var val = tagInput.value.trim().replace(/,/g, '');
        if (val && tags.length < MAX_TAGS && tags.indexOf(val) === -1) {
            tags.push(val);
            tagInput.value = '';
            renderTags();
        }
    }
    if (e.key === 'Backspace' && tagInput.value === '' && tags.length > 0) {
        tags.pop();
        renderTags();
    }
});

tagChips.addEventListener('click', function(e) {
    var x = e.target.closest('.tag-chip-x');
    if (x) {
        tags.splice(parseInt(x.dataset.i), 1);
        renderTags();
    }
});

document.getElementById('tagWrap').addEventListener('click', function() { tagInput.focus(); });
renderTags();

// Form validation
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    var catChecked = document.querySelector('.cat-radio:checked');
    if (!catChecked) {
        e.preventDefault();
        var catErr = document.getElementById('catError');
        catErr.style.display = 'flex';
        catErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }
});

// File previews
function previewFile(input, imgId) {
    var img = document.getElementById(imgId);
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { img.src = e.target.result; img.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
document.querySelector('#zoneThumb input[type="file"]').addEventListener('change', function() { previewFile(this, 'prevThumb'); });
document.getElementById('cgptFile').addEventListener('change', function() { previewFile(this, 'prevCgpt'); });
document.getElementById('gemFile').addEventListener('change', function() { previewFile(this, 'prevGem'); });

document.getElementById('cgptFail').addEventListener('change', function() {
    document.getElementById('cgptFile').disabled = this.checked;
    document.getElementById('zoneCgpt').style.opacity = this.checked ? '0.35' : '1';
});
document.getElementById('gemFail').addEventListener('change', function() {
    document.getElementById('gemFile').disabled = this.checked;
    document.getElementById('zoneGem').style.opacity = this.checked ? '0.35' : '1';
});

function toggleVis(id, checked) {
    fetch('not_mine_admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=toggle&tid=' + id + '&val=' + (checked ? 1 : 0)
    });
}

function nmCopyShare(inputId, btn) {
    var input = document.getElementById(inputId);
    var text = input ? input.value : '';
    if (!text) return;
    function onOk() {
        var orig = btn.innerHTML;
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        setTimeout(function() { btn.classList.remove('copied'); btn.innerHTML = orig; }, 1600);
    }
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(onOk).catch(function() {
            input.select();
            try { document.execCommand('copy'); onOk(); } catch (e) { window.prompt('Copy link:', text); }
        });
    } else {
        input.select();
        try { document.execCommand('copy'); onOk(); } catch (e) { window.prompt('Copy link:', text); }
    }
}
</script>
</body>
</html>
