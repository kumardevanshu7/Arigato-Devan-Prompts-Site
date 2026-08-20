<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';
require_once 'slug_helper.php';

function nm_table_exists_boot(PDO $pdo, string $table): bool
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

$id = (int)($_GET['id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');
if ($id <= 0 && $slug === '') { header('Location: curated_ai_prompts.php'); exit(); }
if (!nm_table_exists_boot($pdo, 'curated_prompts')) { header('Location: curated_ai_prompts.php'); exit(); }

if ($slug !== '') {
    $stmt = $pdo->prepare('SELECT * FROM curated_prompts WHERE slug = ? AND is_visible = 1');
    $stmt->execute([$slug]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM curated_prompts WHERE id = ? AND is_visible = 1');
    $stmt->execute([$id]);
}
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { header('Location: curated_ai_prompts.php'); exit(); }
$id = (int) $p['id'];

$is_legacy_route = basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'curated_prompt.php';
if ($is_legacy_route && !empty($p['slug']) && empty($_POST['ajax'])) {
    header('Location: ' . nm_prompt_url($p), true, 301);
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
$user_vote = null;
if ($user_id) {
    $vs = $pdo->prepare('SELECT voted_for FROM curated_votes WHERE user_id = ? AND prompt_id = ?');
    $vs->execute([$user_id, $id]);
    $user_vote = $vs->fetchColumn() ?: null;
}

$both_failed  = $p['chatgpt_failed'] && $p['gemini_failed'];
$one_failed   = ($p['chatgpt_failed'] xor $p['gemini_failed']);
$auto_winner  = null;
$auto_unlocked = false;

if ($both_failed) {
    $auto_unlocked = true;
} elseif ($one_failed) {
    $auto_winner = $p['chatgpt_failed'] ? 'gemini' : 'chatgpt';
    $auto_unlocked = true;
}

$is_unlocked = $auto_unlocked || !empty($user_vote);

function nm_like_count(PDO $pdo, int $prompt_id): int
{
    try {
        $st = $pdo->prepare('SELECT COUNT(*) FROM curated_likes WHERE prompt_id = ?');
        $st->execute([$prompt_id]);
        return (int) $st->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function nm_unlock_like(PDO $pdo, int $user_id, int $prompt_id): void
{
    try {
        $pdo->prepare("INSERT IGNORE INTO curated_likes (user_id, prompt_id, like_type) VALUES (?, ?, 'unlock')")
            ->execute([$user_id, $prompt_id]);
    } catch (PDOException $e) {
    }
}

function nm_has_column(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $q = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $q->execute([$column]);
        $cache[$key] = (bool) $q->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

function nm_table_exists(PDO $pdo, string $table): bool
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

// Backfill unlock-like for users who already voted
if ($user_id && $user_vote) {
    nm_unlock_like($pdo, $user_id, $id);
}

$vote_counts = ['chatgpt' => 0, 'gemini' => 0];
$vc = $pdo->prepare("SELECT voted_for, COUNT(*) as cnt FROM curated_votes WHERE prompt_id = ? GROUP BY voted_for");
$vc->execute([$id]);
while ($r = $vc->fetch(PDO::FETCH_ASSOC)) $vote_counts[$r['voted_for']] = (int)$r['cnt'];
$total_votes = $vote_counts['chatgpt'] + $vote_counts['gemini'];

// Likes — unlock + manual can each add 1 per user (max 2 per user)
$like_count = nm_like_count($pdo, $id);
$is_liked = false;
if ($user_id) {
    try {
        if (nm_has_column($pdo, 'curated_likes', 'like_type')) {
            $lk = $pdo->prepare("SELECT id FROM curated_likes WHERE user_id = ? AND prompt_id = ? AND like_type = 'manual'");
            $lk->execute([$user_id, $id]);
        } else {
            $lk = $pdo->prepare("SELECT id FROM curated_likes WHERE user_id = ? AND prompt_id = ?");
            $lk->execute([$user_id, $id]);
        }
        $is_liked = !!$lk->fetch();
    } catch (PDOException $e) {
        $is_liked = false;
    }
}

// AJAX vote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'vote') {
    header('Content-Type: application/json');
    if (!$user_id) { echo json_encode(['ok' => false, 'msg' => 'login']); exit(); }
    if ($auto_unlocked) { echo json_encode(['ok' => false, 'msg' => 'Already unlocked']); exit(); }
    $vote = in_array($_POST['vote'] ?? '', ['chatgpt','gemini']) ? $_POST['vote'] : '';
    if (!$vote) { echo json_encode(['ok' => false, 'msg' => 'Invalid vote']); exit(); }
    try {
        $pdo->prepare('INSERT INTO curated_votes (user_id, prompt_id, voted_for) VALUES (?, ?, ?)')->execute([$user_id, $id, $vote]);
        nm_unlock_like($pdo, $user_id, $id);
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => 'Already voted']); exit();
    }
    $vc2 = $pdo->prepare("SELECT voted_for, COUNT(*) as cnt FROM curated_votes WHERE prompt_id = ? GROUP BY voted_for");
    $vc2->execute([$id]);
    $nc = ['chatgpt' => 0, 'gemini' => 0];
    while ($r2 = $vc2->fetch(PDO::FETCH_ASSOC)) $nc[$r2['voted_for']] = (int)$r2['cnt'];
    echo json_encode([
        'ok' => true,
        'prompt_text' => $p['prompt_text'],
        'voted_for' => $vote,
        'counts' => $nc,
        'like_count' => nm_like_count($pdo, $id),
    ]);
    exit();
}

// AJAX like (manual only — unlock-like is separate and permanent)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'like') {
    header('Content-Type: application/json');
    if (!$user_id) { echo json_encode(['ok' => false, 'msg' => 'login']); exit(); }
    try {
        if (nm_has_column($pdo, 'curated_likes', 'like_type')) {
            $ex = $pdo->prepare("SELECT id FROM curated_likes WHERE user_id = ? AND prompt_id = ? AND like_type = 'manual'");
            $ex->execute([$user_id, $id]);
            if ($ex->fetch()) {
                $pdo->prepare("DELETE FROM curated_likes WHERE user_id = ? AND prompt_id = ? AND like_type = 'manual'")->execute([$user_id, $id]);
                $liked = false;
            } else {
                $pdo->prepare("INSERT INTO curated_likes (user_id, prompt_id, like_type) VALUES (?, ?, 'manual')")->execute([$user_id, $id]);
                $liked = true;
            }
        } else {
            $ex = $pdo->prepare("SELECT id FROM curated_likes WHERE user_id = ? AND prompt_id = ?");
            $ex->execute([$user_id, $id]);
            if ($ex->fetch()) {
                $pdo->prepare("DELETE FROM curated_likes WHERE user_id = ? AND prompt_id = ?")->execute([$user_id, $id]);
                $liked = false;
            } else {
                $pdo->prepare("INSERT INTO curated_likes (user_id, prompt_id) VALUES (?, ?)")->execute([$user_id, $id]);
                $liked = true;
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => 'likes_unavailable']);
        exit();
    }
    echo json_encode(['ok' => true, 'liked' => $liked, 'count' => nm_like_count($pdo, $id)]);
    exit();
}

// AJAX save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'save') {
    header('Content-Type: application/json');
    if (!$user_id) { echo json_encode(['ok' => false, 'msg' => 'login']); exit(); }
    try {
        if (nm_has_column($pdo, 'saved_prompts', 'source')) {
            $already = $pdo->prepare("SELECT id FROM saved_prompts WHERE user_id = ? AND prompt_id = ? AND source = 'curated'");
            $already->execute([$user_id, $id]);
            if ($already->fetch()) {
                $pdo->prepare("DELETE FROM saved_prompts WHERE user_id = ? AND prompt_id = ? AND source = 'curated'")->execute([$user_id, $id]);
                echo json_encode(['ok' => true, 'saved' => false]);
            } else {
                $pdo->prepare("INSERT INTO saved_prompts (user_id, prompt_id, source) VALUES (?, ?, 'curated')")->execute([$user_id, $id]);
                echo json_encode(['ok' => true, 'saved' => true]);
            }
        } else {
            $already = $pdo->prepare("SELECT id FROM saved_prompts WHERE user_id = ? AND prompt_id = ?");
            $already->execute([$user_id, $id]);
            if ($already->fetch()) {
                $pdo->prepare("DELETE FROM saved_prompts WHERE user_id = ? AND prompt_id = ?")->execute([$user_id, $id]);
                echo json_encode(['ok' => true, 'saved' => false]);
            } else {
                $pdo->prepare("INSERT INTO saved_prompts (user_id, prompt_id) VALUES (?, ?)")->execute([$user_id, $id]);
                echo json_encode(['ok' => true, 'saved' => true]);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => 'save_unavailable']);
    }
    exit();
}

$is_saved = false;
if ($user_id) {
    try {
        if (nm_has_column($pdo, 'saved_prompts', 'source')) {
            $sv = $pdo->prepare("SELECT id FROM saved_prompts WHERE user_id = ? AND prompt_id = ? AND source = 'curated'");
            $sv->execute([$user_id, $id]);
        } else {
            $sv = $pdo->prepare("SELECT id FROM saved_prompts WHERE user_id = ? AND prompt_id = ?");
            $sv->execute([$user_id, $id]);
        }
        $is_saved = !!$sv->fetch();
    } catch (PDOException $e) {
        $is_saved = false;
    }
}


$cat_colors = ['boys' => '#3b82f6', 'girls' => '#ec4899', 'couple' => '#a855f7', 'family' => '#22c55e', 'creativity' => '#a855f7'];
$cat_color = $cat_colors[$p['category']] ?? '#F5709D';

$meta_desc = trim($p['meta_description'] ?? '');
if ($meta_desc === '') {
    $meta_desc = htmlspecialchars($p['title']) . ' — Curated AI Prompts | Arigato Devan';
} else {
    $meta_desc = htmlspecialchars($meta_desc);
}
$meta_keywords = trim($p['meta_keywords'] ?? '');
$nm_slug = trim($p['slug'] ?? '');
$is_local = nm_is_local();
$base_path = $is_local ? nm_local_base() . '/' : '/';
$canonical = nm_prompt_canonical($p);
$_page_canonical = $canonical;
$og_img = 'https://arigatodevan.com/' . ltrim($p['thumbnail_image'] ?? '', '/');
$about_text = trim($p['meta_description'] ?? '');

$chatgpt_logo = 'https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg';
$gemini_logo = 'https://www.google.com/favicon.ico';
?>
<!DOCTYPE html>
<html lang="en" class="theme-nogoda">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#2F4156">
    <base href="<?= $base_path ?>">
    <title><?= htmlspecialchars($p['title']) ?> — Curated AI Prompts | Arigato Devan</title>
    <meta name="description" content="<?= $meta_desc ?>">
    <?php if ($meta_keywords !== ''): ?><meta name="keywords" content="<?= htmlspecialchars($meta_keywords) ?>"><?php endif; ?>
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= htmlspecialchars($p['title']) ?> — Curated AI Prompts | Arigato Devan">
    <meta property="og:description" content="<?= $meta_desc ?>">
    <meta property="og:image" content="<?= htmlspecialchars($og_img) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($p['title']) ?> — Curated AI Prompts">
    <meta name="twitter:description" content="<?= $meta_desc ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_img) ?>">
    <?php include_once 'includes/theme_head.php'; ?>
    <?php include_once 'gtag.php'; ?>
<style>
.nmp { max-width: 760px; margin: 0 auto; padding: 36px 24px 90px; }

.nmp-back { display: inline-flex; align-items: center; gap: 6px; font-size: .78rem; font-weight: 600;
    color: var(--pal-teal); text-decoration: none; margin-bottom: 28px; transition: color .2s; }
.nmp-back:hover { color: var(--pal-navy); }

/* Header */
.nmp-head { text-align: center; margin-bottom: 36px; }
.nmp-cat-tag { display: inline-block; padding: 5px 16px; border-radius: 8px; font-size: .62rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .08em; color: #fff; margin-bottom: 14px; }
.nmp-title { font-family: 'Playfair Display', serif; font-size: clamp(1.6rem, 4.5vw, 2.2rem); font-weight: 900;
    color: var(--pal-navy); margin-bottom: 8px; line-height: 1.2; }
.nmp-tags { font-size: .8rem; color: var(--pal-teal); font-weight: 500; }

.nmp-vs-label { text-align: center; font-weight: 800; font-size: .85rem; color: var(--pal-navy); margin-bottom: 20px;
    display: flex; align-items: center; justify-content: center; gap: 10px; }
.nmp-vs-label::before, .nmp-vs-label::after { content: ''; flex: 1; max-width: 60px; height: 1.5px; background: var(--pal-sky); }

/* Battle grid */
.nmp-battle { display: grid; grid-template-columns: 1fr 44px 1fr; align-items: stretch; margin-bottom: 28px; }

.nmp-side { display: flex; flex-direction: column; align-items: center; }

/* Side head wrapper — fixed height so crown doesn't shift layout */
.nmp-side-top { height: 52px; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }

/* Crown — sits above the label, centered, doesn't affect other side's layout */
.nmp-crown { font-size: 1.25rem; line-height: 1; color: #e6a817;
    filter: drop-shadow(0 2px 6px rgba(0,0,0,.18)); animation: crownBounce 1.5s ease infinite;
    display: inline-flex; align-items: center; justify-content: center; }
.nmp-results-title .fa-crown { color: #e6a817; margin-right: 5px; font-size: .85rem; }
@keyframes crownBounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }

.nmp-side-head { display: flex; align-items: center; justify-content: center; gap: 7px; padding: 4px 0 8px;
    font-size: .74rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
.nmp-side-head img { width: 18px; height: 18px; border-radius: 4px; }
.nmp-side-head svg { width: 18px; height: 18px; }

.nmp-frame { position: relative; width: 100%; aspect-ratio: 9/16; border-radius: 16px; overflow: hidden;
    border: 2px solid var(--pal-sky); background: var(--pal-sky); }
.nmp-frame img { width: 100%; height: 100%; object-fit: cover; }
.nmp-frame.is-preview { cursor: zoom-in; }
.nmp-frame.is-preview::after {
    content: '\f00e';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    right: 10px;
    bottom: 10px;
    z-index: 2;
    width: 30px;
    height: 30px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    color: var(--pal-navy);
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid var(--pal-sky);
    box-shadow: 0 4px 14px rgba(47, 65, 86, 0.14);
    opacity: 0;
    transform: scale(0.92);
    transition: opacity 0.2s ease, transform 0.2s ease;
    pointer-events: none;
}
.nmp-frame.is-preview:hover::after,
.nmp-frame.is-preview:focus-visible::after { opacity: 1; transform: scale(1); }
.nmp-frame.is-preview:hover img { transform: scale(1.03); }
.nmp-frame.is-preview img { transition: transform 0.35s ease; }

.nmp-preview {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.25s ease, visibility 0.25s ease;
}
.nmp-preview.is-open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}
.nmp-preview-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(47, 65, 86, 0.72);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}
.nmp-preview-panel {
    position: relative;
    z-index: 1;
    max-width: 92vw;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    width: auto;
}
.nmp-preview-label {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #fff;
}
.nmp-preview-media {
    position: relative;
    line-height: 0;
    max-width: min(420px, 92vw);
    max-height: calc(92vh - 110px);
}
.nmp-preview-img {
    display: block;
    width: auto;
    height: auto;
    max-width: min(420px, 92vw);
    max-height: calc(92vh - 110px);
    border-radius: 18px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
    background: #111;
}
.nmp-preview-close {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 38px;
    height: 38px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.95);
    color: var(--pal-navy);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}
.nmp-preview-nav {
    display: flex;
    align-items: center;
    gap: 14px;
}
.nmp-preview-nav button {
    width: 36px;
    height: 36px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.28);
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    cursor: pointer;
}
.nmp-preview-nav button:disabled { opacity: 0.35; cursor: default; }
.nmp-preview-counter {
    font-size: 0.72rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    min-width: 52px;
    text-align: center;
}
body.nmp-preview-open { overflow: hidden; }

.nmp-fail-ph { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;
    background: linear-gradient(145deg, #fef2f2 0%, #fecaca 100%); text-align: center; padding: 20px; }
.nmp-fail-ph p { font-size: .78rem; font-weight: 700; color: #991b1b; line-height: 1.5; text-align: center; }
.nmp-fail-ph i { font-size: 1.4rem; display: block; margin-bottom: 8px; color: var(--nogoda-pink, #F5709D); }
.nmp-fail-msg { position: relative; display: block; min-height: 2.6em; }
.nmp-fail-msg span { display: block; transition: opacity .5s ease; }
.nmp-fail-msg .fail-en { position: absolute; left: 0; right: 0; top: 0; opacity: 0; }
.nmp-fail-msg.show-en .fail-hi { opacity: 0; }
.nmp-fail-msg.show-en .fail-en { opacity: 1; position: relative; }

.nmp-vs-col { display: flex; align-items: center; justify-content: center; }
.nmp-vs-badge { writing-mode: vertical-lr; font-size: 1.1rem; font-weight: 900;
    letter-spacing: .12em; text-transform: uppercase;
    background: var(--nogoda-gradient-h); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }

/* Vote section */
.nmp-vote-section { text-align: center; margin-bottom: 28px; padding: 24px; background: var(--bg-card, #fff);
    border: 1.5px solid var(--border, #C8D9E6); border-radius: 20px; box-shadow: var(--shadow-sm); }
.nmp-vote-msg { font-size: .85rem; font-weight: 700; color: var(--pal-navy); margin-bottom: 16px; }
.nmp-vote-msg a { color: var(--nogoda-pink, #F5709D); text-decoration: underline; }
.nmp-vote-row { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; }
.nmp-vbtn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px;
    border: 2px solid var(--pal-sky); border-radius: 14px;
    background: var(--pal-white); color: var(--pal-navy); font-weight: 700; font-size: .85rem;
    cursor: pointer; transition: all .25s; font-family: 'Inter','Outfit',sans-serif; }
.nmp-vbtn img, .nmp-vbtn svg { width: 20px; height: 20px; border-radius: 4px; }
.nmp-vbtn:hover { border-color: var(--nogoda-pink, #F5709D); color: var(--nogoda-pink, #F5709D); transform: translateY(-3px);
    box-shadow: var(--nogoda-glow); }
.nmp-vbtn.voted { background: var(--nogoda-gradient); color: var(--pal-navy); border-color: transparent; pointer-events: none; }
.nmp-vbtn:disabled { opacity: .45; pointer-events: none; }

/* Results */
.nmp-results { margin-bottom: 28px; background: var(--bg-card, #fff); border: 1.5px solid var(--border, #C8D9E6);
    border-radius: 20px; padding: 22px 24px; box-shadow: var(--shadow-sm); }
.nmp-results-title { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em;
    color: var(--pal-teal); margin-bottom: 16px; }
.nmp-poll { display: flex; flex-direction: column; gap: 14px; }
.nmp-poll-row { display: flex; flex-direction: column; gap: 6px; }
.nmp-poll-head { display: flex; align-items: center; justify-content: space-between; }
.nmp-poll-label { display: flex; align-items: center; gap: 7px; font-size: .82rem; font-weight: 700; color: var(--pal-navy); }
.nmp-poll-label img, .nmp-poll-label svg { width: 18px; height: 18px; border-radius: 4px; }
.nmp-poll-pct { font-size: .9rem; font-weight: 900; color: var(--pal-navy); }
.nmp-poll-track { height: 12px; background: var(--pal-sky, #C8D9E6); border-radius: 99px; overflow: hidden; }
.nmp-poll-fill { height: 100%; border-radius: 99px; transition: width .6s cubic-bezier(.4,0,.2,1); }
.nmp-poll-fill.fill-gpt { background: linear-gradient(90deg, var(--nogoda-plum, #6D2D52), var(--nogoda-pink, #F5709D)); }
.nmp-poll-fill.fill-gem { background: linear-gradient(90deg, var(--nogoda-cyan, #2FA6C6), var(--nogoda-teal, #11FFC9)); }
.nmp-poll-votes { font-size: .68rem; font-weight: 600; color: var(--pal-teal); }

/* Prompt terminal */
.nmp-terminal { background: #1a1b2e; border-radius: 20px; overflow: hidden; margin-bottom: 24px;
    border: 1.5px solid #2d2e42; box-shadow: 0 12px 40px rgba(0,0,0,.2); }
.nmp-term-bar { display: flex; align-items: center; gap: 7px; padding: 14px 18px; background: #13142a;
    border-bottom: 1px solid #2d2e42; }
.nmp-term-dot { width: 11px; height: 11px; border-radius: 50%; }
.nmp-term-dot.r { background: #ff5f57; }
.nmp-term-dot.y { background: #febc2e; }
.nmp-term-dot.g { background: #28c840; }
.nmp-term-title { margin-left: auto; font-size: .65rem; font-weight: 700; color: #555577; text-transform: uppercase;
    letter-spacing: .12em; }
.nmp-term-body { padding: 24px 24px 20px; }
.nmp-term-text { font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', 'Consolas', monospace;
    font-size: .82rem; line-height: 1.8; color: #cdd6f4; white-space: pre-wrap; word-break: break-word; }
.nmp-term-text .prompt-marker { color: #a6e3a1; font-weight: 700; }

.nmp-copy { display: inline-flex; align-items: center; gap: 7px; margin: 0 24px 18px; padding: 10px 22px;
    border-radius: 10px; background: #2d2e42; color: #a6e3a1; font-weight: 700; font-size: .78rem;
    cursor: pointer; border: 1.5px solid #3d3e55; transition: all .25s; font-family: 'Inter',sans-serif;
    letter-spacing: .06em; text-transform: uppercase; }
.nmp-copy:hover { background: #3d3e55; color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.2); }
.nmp-copy i { font-size: .85rem; }

.nmp-locked { text-align: center; padding: 40px 20px; }
.nmp-locked i { font-size: 1.6rem; color: #f38ba8; margin-bottom: 10px; display: block; }
.nmp-locked p { font-weight: 700; font-size: .85rem; color: #555577; }

/* Actions row */
.nmp-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.nmp-act { display: inline-flex; align-items: center; gap: 7px; padding: 11px 24px;
    border: 1.5px solid var(--pal-sky); border-radius: 14px;
    background: var(--pal-white); color: var(--pal-navy); font-weight: 700; font-size: .82rem;
    cursor: pointer; transition: all .25s; font-family: 'Inter','Outfit',sans-serif; text-decoration: none;
    box-shadow: var(--shadow-sm); }
.nmp-act:hover { border-color: var(--pal-navy); transform: translateY(-2px); box-shadow: var(--shadow-md); }
.nmp-act.liked-on { color: #e11d48; border-color: #fecdd3; background: #fff1f2; }
.nmp-act.liked-on i { color: #e11d48; }
.nmp-act.saved-on { background: #fef3c7; border-color: #f59e0b; color: #92400e; }

.nmp-about {
    margin-top: 40px;
    padding: 24px 26px;
    background: var(--bg-card, #fff);
    border: 1.5px solid var(--border, #C8D9E6);
    border-radius: 20px;
    box-shadow: var(--shadow-sm);
}
.nmp-about h2 {
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--pal-teal);
    margin-bottom: 12px;
}
.nmp-about p {
    font-size: .88rem;
    line-height: 1.75;
    color: var(--pal-navy);
    margin: 0;
}
.nmp-about-kw {
    margin-top: 14px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.nmp-about-kw span {
    font-size: .68rem;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 999px;
    background: var(--pal-sky, #C8D9E6);
    color: var(--pal-navy);
}

@media (max-width: 600px) {
    .nmp { padding: 24px 16px 70px; }
    .nmp-battle { grid-template-columns: 1fr 24px 1fr; }
    .nmp-vs-badge { font-size: .75rem; }
    .nmp-frame { border-radius: 12px; }
    .nmp-term-body { padding: 16px 18px; }
    .nmp-term-text { font-size: .74rem; }
    .nmp-vbtn { padding: 10px 18px; font-size: .78rem; }
    .nmp-act { padding: 9px 18px; font-size: .78rem; }
}
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="page-store theme-nogoda page-info">

<?php $nav_active = ''; include 'includes/site_nav.php'; ?>

<div class="nmp">
    <a href="curated_ai_prompts.php?cat=<?= $p['category'] ?>" class="nmp-back"><i class="fa-solid fa-arrow-left"></i> Back to Curated AI Prompts</a>

    <div class="nmp-head">
        <span class="nmp-cat-tag" style="background:<?= $cat_color ?>"><?= ucfirst($p['category']) ?></span>
        <h1 class="nmp-title"><?= htmlspecialchars($p['title']) ?></h1>
        <?php if ($p['tags']): ?><p class="nmp-tags"><?= htmlspecialchars($p['tags']) ?></p><?php endif; ?>
    </div>

    <p class="nmp-vs-label">Same Prompt, Different Results</p>

    <!-- Battle -->
    <div class="nmp-battle">
        <!-- ChatGPT -->
        <div class="nmp-side">
            <div class="nmp-side-top">
                <?php if ($auto_winner === 'chatgpt' || (!$auto_winner && !$auto_unlocked && $user_vote === 'chatgpt')): ?>
                    <span class="nmp-crown"><i class="fa-solid fa-crown" aria-hidden="true"></i></span>
                <?php endif; ?>
                <div class="nmp-side-head" style="color:#10a37f">
                    <img src="<?= $chatgpt_logo ?>" alt="ChatGPT"> ChatGPT
                </div>
            </div>
            <div class="nmp-frame<?= $p['chatgpt_failed'] ? '' : ' is-preview' ?>"
                <?php if (!$p['chatgpt_failed']): ?>
                role="button" tabindex="0" aria-label="Preview ChatGPT result"
                data-preview-src="<?= htmlspecialchars($p['chatgpt_image']) ?>" data-preview-label="ChatGPT"
                <?php endif; ?>>
                <?php if ($p['chatgpt_failed']): ?>
                    <div class="nmp-fail-ph"><p><i class="fa-solid fa-circle-xmark"></i><span class="nmp-fail-msg"><span class="fail-hi">ChatGPT nahi bana paya<br>after 3-5 tries</span><span class="fail-en">ChatGPT couldn't generate<br>after 3-5 tries</span></span></p></div>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($p['chatgpt_image']) ?>" alt="ChatGPT result">
                <?php endif; ?>
            </div>
        </div>

        <div class="nmp-vs-col"><span class="nmp-vs-badge">VS</span></div>

        <!-- Gemini -->
        <div class="nmp-side">
            <div class="nmp-side-top">
                <?php if ($auto_winner === 'gemini' || (!$auto_winner && !$auto_unlocked && $user_vote === 'gemini')): ?>
                    <span class="nmp-crown"><i class="fa-solid fa-crown" aria-hidden="true"></i></span>
                <?php endif; ?>
                <div class="nmp-side-head" style="color:#4285f4">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Gemini
                </div>
            </div>
            <div class="nmp-frame<?= $p['gemini_failed'] ? '' : ' is-preview' ?>"
                <?php if (!$p['gemini_failed']): ?>
                role="button" tabindex="0" aria-label="Preview Gemini result"
                data-preview-src="<?= htmlspecialchars($p['gemini_image']) ?>" data-preview-label="Gemini"
                <?php endif; ?>>
                <?php if ($p['gemini_failed']): ?>
                    <div class="nmp-fail-ph"><p><i class="fa-solid fa-circle-xmark"></i><span class="nmp-fail-msg"><span class="fail-hi">Gemini nahi bana paya<br>after 3-5 tries</span><span class="fail-en">Gemini couldn't generate<br>after 3-5 tries</span></span></p></div>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($p['gemini_image']) ?>" alt="Gemini result">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Vote / Results -->
    <?php if ($auto_unlocked): ?>
        <?php if ($auto_winner): ?>
        <div class="nmp-results">
            <p class="nmp-results-title"><i class="fa-solid fa-crown" aria-hidden="true"></i> <?= $auto_winner === 'chatgpt' ? 'ChatGPT' : 'Gemini' ?> wins — other couldn't generate</p>
            <div class="nmp-poll">
                <div class="nmp-poll-row">
                    <div class="nmp-poll-head">
                        <span class="nmp-poll-label"><img src="<?= $chatgpt_logo ?>" alt=""> ChatGPT</span>
                        <span class="nmp-poll-pct"><?= $auto_winner === 'chatgpt' ? '100%' : '0%' ?></span>
                    </div>
                    <div class="nmp-poll-track"><div class="nmp-poll-fill fill-gpt" style="width:<?= $auto_winner === 'chatgpt' ? '100' : '0' ?>%"></div></div>
                </div>
                <div class="nmp-poll-row">
                    <div class="nmp-poll-head">
                        <span class="nmp-poll-label"><svg viewBox="0 0 24 24" style="width:16px;height:16px"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Gemini</span>
                        <span class="nmp-poll-pct"><?= $auto_winner === 'gemini' ? '100%' : '0%' ?></span>
                    </div>
                    <div class="nmp-poll-track"><div class="nmp-poll-fill fill-gem" style="width:<?= $auto_winner === 'gemini' ? '100' : '0' ?>%"></div></div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <p style="text-align:center;color:var(--pal-teal);font-weight:700;font-size:.78rem;margin-bottom:20px;">
            Both AI failed — prompt is already unlocked
        </p>
        <?php endif; ?>
    <?php elseif ($user_vote): ?>
        <?php
            $gpt_pct = $total_votes > 0 ? round(($vote_counts['chatgpt'] / $total_votes) * 100) : 50;
            $gem_pct = 100 - $gpt_pct;
        ?>
        <div class="nmp-results">
            <p class="nmp-results-title">Vote Results</p>
            <div class="nmp-poll">
                <div class="nmp-poll-row">
                    <div class="nmp-poll-head">
                        <span class="nmp-poll-label"><img src="<?= $chatgpt_logo ?>" alt=""> ChatGPT</span>
                        <span class="nmp-poll-pct"><?= $gpt_pct ?>%</span>
                    </div>
                    <div class="nmp-poll-track"><div class="nmp-poll-fill fill-gpt" style="width:<?= $gpt_pct ?>%"></div></div>
                    <span class="nmp-poll-votes"><?= $vote_counts['chatgpt'] ?> votes</span>
                </div>
                <div class="nmp-poll-row">
                    <div class="nmp-poll-head">
                        <span class="nmp-poll-label"><svg viewBox="0 0 24 24" style="width:16px;height:16px"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Gemini</span>
                        <span class="nmp-poll-pct"><?= $gem_pct ?>%</span>
                    </div>
                    <div class="nmp-poll-track"><div class="nmp-poll-fill fill-gem" style="width:<?= $gem_pct ?>%"></div></div>
                    <span class="nmp-poll-votes"><?= $vote_counts['gemini'] ?> votes</span>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="nmp-vote-section" id="voteArea">
            <p class="nmp-vote-msg">Vote for the better result to unlock the prompt</p>
            <div class="nmp-vote-row">
                <?php if (!$p['chatgpt_failed']): ?>
                <button class="nmp-vbtn" onclick="castVote('chatgpt')" id="voteGpt">
                    <img src="<?= $chatgpt_logo ?>" alt=""> ChatGPT Wins
                </button>
                <?php endif; ?>
                <?php if (!$p['gemini_failed']): ?>
                <button class="nmp-vbtn" onclick="castVote('gemini')" id="voteGem">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Gemini Wins
                </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Prompt terminal -->
    <div class="nmp-terminal" id="promptCard">
        <div class="nmp-term-bar">
            <span class="nmp-term-dot r"></span>
            <span class="nmp-term-dot y"></span>
            <span class="nmp-term-dot g"></span>
            <span class="nmp-term-title">prompt</span>
        </div>
        <?php if ($is_unlocked): ?>
            <div class="nmp-term-body">
                <div class="nmp-term-text" id="promptText"><span class="prompt-marker">❯ </span><?= nl2br(htmlspecialchars($p['prompt_text'])) ?></div>
            </div>
            <button class="nmp-copy" onclick="copyPrompt()"><i class="fa-solid fa-copy"></i> <span id="copyLabel">COPY</span></button>
        <?php else: ?>
            <div class="nmp-locked"><i class="fa-solid fa-lock"></i><p>Vote above to unlock this prompt</p></div>
        <?php endif; ?>
    </div>

    <!-- Actions: Like + Share + Save -->
    <div class="nmp-actions">
        <button class="nmp-act <?= $is_liked ? 'liked-on' : '' ?>" id="likeBtn" onclick="toggleLike()">
            <i class="fa-solid fa-heart"></i> <span id="likeLabel"><?= $like_count ?></span>
        </button>
        <button class="nmp-act" onclick="sharePrompt()"><i class="fa-solid fa-share-nodes"></i> Share</button>
        <button class="nmp-act <?= $is_saved ? 'saved-on' : '' ?>" id="saveBtn" onclick="toggleSave()">
            <i class="fa-solid fa-bookmark"></i> <span id="saveLabel"><?= $is_saved ? 'Saved' : 'Save' ?></span>
        </button>
    </div>

    <?php if ($about_text !== ''): ?>
    <section class="nmp-about" aria-label="About this prompt">
        <h2>About this prompt</h2>
        <p><?= nl2br(htmlspecialchars($about_text)) ?></p>
        <?php
        $kw_list = array_filter(array_map('trim', explode(',', $meta_keywords)));
        if (!empty($kw_list)):
        ?>
        <div class="nmp-about-kw" aria-label="Keywords">
            <?php foreach ($kw_list as $kw): ?>
            <span><?= htmlspecialchars($kw) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<div class="nmp-preview" id="nmpPreview" aria-hidden="true">
    <div class="nmp-preview-backdrop" id="nmpPreviewBackdrop"></div>
    <div class="nmp-preview-panel">
        <button type="button" class="nmp-preview-close" id="nmpPreviewClose" aria-label="Close preview">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <p class="nmp-preview-label" id="nmpPreviewLabel"></p>
        <div class="nmp-preview-media">
            <img class="nmp-preview-img" id="nmpPreviewImg" alt="">
        </div>
        <div class="nmp-preview-nav">
            <button type="button" id="nmpPreviewPrev" aria-label="Previous image"><i class="fa-solid fa-chevron-left"></i></button>
            <span class="nmp-preview-counter" id="nmpPreviewCounter"></span>
            <button type="button" id="nmpPreviewNext" aria-label="Next image"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
var promptId = <?= $id ?>;
var promptFetchUrl = <?= json_encode(nm_prompt_url($p)) ?>;
var isLoggedIn = <?= $user_id ? 'true' : 'false' ?>;

function requireLogin() {
    window.location.href = 'login.php';
}

function castVote(choice) {
    if (!isLoggedIn) { requireLogin(); return; }
    var btns = document.querySelectorAll('.nmp-vbtn');
    btns.forEach(function(b) { b.disabled = true; });
    fetch(promptFetchUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ajax=vote&vote=' + choice
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (!d.ok) {
            if (d.msg === 'login') { requireLogin(); return; }
            alert(d.msg); btns.forEach(function(b) { b.disabled = false; }); return;
        }
        var total = d.counts.chatgpt + d.counts.gemini;
        var gptPct = total > 0 ? Math.round((d.counts.chatgpt / total) * 100) : 50;
        var gemPct = 100 - gptPct;
        var gSvg = '<svg viewBox="0 0 24 24" style="width:16px;height:16px"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>';
        document.getElementById('voteArea').innerHTML =
            '<div class="nmp-results"><p class="nmp-results-title">Vote Results</p><div class="nmp-poll">' +
            '<div class="nmp-poll-row"><div class="nmp-poll-head"><span class="nmp-poll-label"><img src="https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg" style="width:16px;height:16px;border-radius:3px" alt=""> ChatGPT</span><span class="nmp-poll-pct">' + gptPct + '%</span></div>' +
            '<div class="nmp-poll-track"><div class="nmp-poll-fill fill-gpt" style="width:' + gptPct + '%"></div></div><span class="nmp-poll-votes">' + d.counts.chatgpt + ' votes</span></div>' +
            '<div class="nmp-poll-row"><div class="nmp-poll-head"><span class="nmp-poll-label">' + gSvg + ' Gemini</span><span class="nmp-poll-pct">' + gemPct + '%</span></div>' +
            '<div class="nmp-poll-track"><div class="nmp-poll-fill fill-gem" style="width:' + gemPct + '%"></div></div><span class="nmp-poll-votes">' + d.counts.gemini + ' votes</span></div>' +
            '</div></div>';

        var card = document.getElementById('promptCard');
        var termBar = '<div class="nmp-term-bar"><span class="nmp-term-dot r"></span><span class="nmp-term-dot y"></span><span class="nmp-term-dot g"></span><span class="nmp-term-title">prompt</span></div>';
        card.innerHTML = termBar +
            '<div class="nmp-term-body"><div class="nmp-term-text" id="promptText"><span class="prompt-marker">❯ </span>' +
            d.prompt_text.replace(/\n/g, '<br>') + '</div></div>' +
            '<button class="nmp-copy" onclick="copyPrompt()"><i class="fa-solid fa-copy"></i> <span id="copyLabel">COPY</span></button>';

        if (typeof d.like_count !== 'undefined') {
            var likeLbl = document.getElementById('likeLabel');
            if (likeLbl) likeLbl.textContent = d.like_count;
        }

        if (typeof window.playUnlockSound === 'function') window.playUnlockSound();
    });
}

function copyPrompt() {
    var el = document.getElementById('promptText');
    if (!el) return;
    var text = el.innerText.replace(/^❯\s*/, '').trim();
    var lbl = document.getElementById('copyLabel');

    function onSuccess() {
        lbl.textContent = 'COPIED!';
        lbl.parentElement.style.background = '#a6e3a1';
        lbl.parentElement.style.color = '#1e1e2e';
        lbl.parentElement.style.borderColor = '#a6e3a1';
        setTimeout(function() {
            lbl.textContent = 'COPY';
            lbl.parentElement.style.background = '';
            lbl.parentElement.style.color = '';
            lbl.parentElement.style.borderColor = '';
        }, 2000);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(onSuccess).catch(function() { fallbackCopy(text, onSuccess); });
    } else {
        fallbackCopy(text, onSuccess);
    }
}

function fallbackCopy(text, cb) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); cb(); } catch(e) { alert('Copy failed — please copy manually'); }
    document.body.removeChild(ta);
}

function toggleLike() {
    if (!isLoggedIn) { requireLogin(); return; }
    fetch(promptFetchUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ajax=like'
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (!d.ok) { if (d.msg === 'login') requireLogin(); return; }
        var btn = document.getElementById('likeBtn');
        var lbl = document.getElementById('likeLabel');
        if (d.liked) { btn.classList.add('liked-on'); } else { btn.classList.remove('liked-on'); }
        lbl.textContent = d.count;
    });
}

function sharePrompt() {
    var url = window.location.href;
    if (navigator.share) { navigator.share({ title: document.title, url: url }); }
    else { navigator.clipboard.writeText(url).then(function() { alert('Link copied!'); }); }
}

function toggleSave() {
    if (!isLoggedIn) { requireLogin(); return; }
    fetch(promptFetchUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ajax=save'
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (!d.ok) { if (d.msg === 'login') requireLogin(); return; }
        var btn = document.getElementById('saveBtn');
        var lbl = document.getElementById('saveLabel');
        if (d.saved) { btn.classList.add('saved-on'); lbl.textContent = 'Saved'; }
        else { btn.classList.remove('saved-on'); lbl.textContent = 'Save'; }
    });
}

document.querySelectorAll('.nmp-fail-msg').forEach(function(el) {
    setTimeout(function() { el.classList.add('show-en'); }, 3000);
});

(function () {
    var preview = document.getElementById('nmpPreview');
    var previewImg = document.getElementById('nmpPreviewImg');
    var previewLabel = document.getElementById('nmpPreviewLabel');
    var previewCounter = document.getElementById('nmpPreviewCounter');
    var previewClose = document.getElementById('nmpPreviewClose');
    var previewBackdrop = document.getElementById('nmpPreviewBackdrop');
    var previewPrev = document.getElementById('nmpPreviewPrev');
    var previewNext = document.getElementById('nmpPreviewNext');
    var frames = Array.prototype.slice.call(document.querySelectorAll('.nmp-frame.is-preview[data-preview-src]'));
    if (!preview || !frames.length) return;

    var items = frames.map(function (frame) {
        return {
            src: frame.getAttribute('data-preview-src'),
            label: frame.getAttribute('data-preview-label') || 'Preview'
        };
    }).filter(function (item) { return !!item.src; });

    var current = 0;

    function show(index) {
        if (!items.length) return;
        current = (index + items.length) % items.length;
        previewImg.src = items[current].src;
        previewImg.alt = items[current].label + ' result';
        previewLabel.textContent = items[current].label;
        previewCounter.textContent = (current + 1) + ' / ' + items.length;
        var single = items.length <= 1;
        previewPrev.disabled = single;
        previewNext.disabled = single;
    }

    function open(index) {
        show(index);
        preview.classList.add('is-open');
        preview.setAttribute('aria-hidden', 'false');
        document.body.classList.add('nmp-preview-open');
    }

    function closePreview() {
        preview.classList.remove('is-open');
        preview.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('nmp-preview-open');
        previewImg.removeAttribute('src');
    }

    frames.forEach(function (frame, index) {
        frame.addEventListener('click', function () { open(index); });
        frame.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                open(index);
            }
        });
    });

    if (previewClose) previewClose.addEventListener('click', closePreview);
    if (previewBackdrop) previewBackdrop.addEventListener('click', closePreview);
    if (previewPrev) previewPrev.addEventListener('click', function () { show(current - 1); });
    if (previewNext) previewNext.addEventListener('click', function () { show(current + 1); });

    document.addEventListener('keydown', function (e) {
        if (!preview.classList.contains('is-open')) return;
        if (e.key === 'Escape') closePreview();
        if (e.key === 'ArrowLeft') show(current - 1);
        if (e.key === 'ArrowRight') show(current + 1);
    });
})();
</script>
</body>
</html>
