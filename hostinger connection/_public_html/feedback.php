<?php
session_start();
require_once "db.php";

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Onboarding check
if (empty($_SESSION['onboarding_complete'])) {
    header("Location: onboarding.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$success_msg = '';
$error_msg   = '';

// Fetch user info
$uStmt = $pdo->prepare("SELECT username, avatar, profile_image, gender FROM users WHERE id = ?");
$uStmt->execute([$user_id]);
$user = $uStmt->fetch(PDO::FETCH_ASSOC);
$username     = htmlspecialchars($user['username'] ?? 'Friend');
$gender       = strtolower(trim($user['gender'] ?? ''));
// Avatar: prefer uploaded avatar over Google profile_image
$profile_img  = (!empty($user['avatar']) ? $user['avatar'] : ($user['profile_image'] ?? ''));

// Gender icon
$gender_icon = match(true) {
    in_array($gender, ['male', 'm'])   => '<i class="fa-solid fa-mars"  style="color:#38bdf8"></i>',
    in_array($gender, ['female', 'f']) => '<i class="fa-solid fa-venus" style="color:#f472b6"></i>',
    default                            => '<i class="fa-solid fa-genderless" style="color:#a78bfa"></i>',
};

// Check existing feedback
$fbStmt = $pdo->prepare("SELECT * FROM feedbacks WHERE user_id = ?");
$fbStmt->execute([$user_id]);
$existing = $fbStmt->fetch(PDO::FETCH_ASSOC);

$cooldown_days_left = 0;
$already_submitted  = false;
if ($existing) {
    $submitted_at = new DateTime($existing['submitted_at']);
    $now          = new DateTime();
    $diff_days    = (int)$now->diff($submitted_at)->days;
    if ($diff_days < 7) {
        $already_submitted  = true;
        $cooldown_days_left = 7 - $diff_days;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_submitted) {
    $feedback_text = trim($_POST['feedback_text'] ?? '');
    $rating        = (int)($_POST['rating'] ?? 0);

    // Word count validation
    $word_count = str_word_count($feedback_text);
    if ($word_count < 3) {
        $error_msg = "Please write at least 3 words.";
    } elseif ($word_count > 20) {
        $error_msg = "Please keep it under 20 words.";
    } elseif ($rating < 0 || $rating > 10) {
        $error_msg = "Please select a valid rating.";
    } else {
        try {
            if ($existing) {
                // cooldown expired — update
                $upd = $pdo->prepare("UPDATE feedbacks SET feedback_text=?, rating=?, show_on_homepage=0, submitted_at=NOW() WHERE user_id=?");
                $upd->execute([$feedback_text, $rating, $user_id]);
            } else {
                $ins = $pdo->prepare("INSERT INTO feedbacks (user_id, feedback_text, rating) VALUES (?,?,?)");
                $ins->execute([$user_id, $feedback_text, $rating]);
            }
            $success_msg = "submitted";
        } catch (PDOException $e) {
            $error_msg = "Something went wrong. Please try again.";
        }
    }
}

// Avatar helper (inline)
function avatarUrl($profile_img, $username) {
    $seed = urlencode($username ?: 'user');
    $fallback = "https://api.dicebear.com/7.x/avataaars/svg?seed=$seed";
    if (empty($profile_img)) return $fallback;
    return htmlspecialchars($profile_img);
}
$avatar_url = avatarUrl($profile_img, $username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Share Feedback — Arigato Devan</title>
<meta name="robots" content="noindex">
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ══════════════════════════════════════════════════
   ROOT & RESET
══════════════════════════════════════════════════ */
:root {
    --cream:   #eef2ff;
    --cream2:  #e0e7ff;
    --ink:     #1a1410;
    --ink2:    #3730a3;
    --muted:   #6366f1;
    --border:  rgba(99,102,241,0.18);
    --accent:  #818cf8;
    --gold:    #a5b4fc;
    --red-soft:#e05555;
    --serif:   'Cormorant Garamond', Georgia, serif;
    --sans:    'Inter', sans-serif;
    --card-r:  28px;
    --shadow:  0 24px 60px rgba(99,102,241,0.15), 0 8px 24px rgba(99,102,241,0.1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { height: 100%; }
body {
    min-height: 100%;
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 40%, #ede9fe 70%, #dbeafe 100%);
    font-family: var(--sans);
    color: var(--ink);
    overflow-x: hidden;
    position: relative;
}

/* ══════════════════════════════════════════════════
   BACKGROUND TEXTURE
══════════════════════════════════════════════════ */
.bg-lines {
    position: fixed; inset: 0; z-index: 0; pointer-events: none;
    background-image:
        repeating-linear-gradient(
            90deg,
            transparent,
            transparent calc(100% / 8 - 1px),
            rgba(60,45,30,0.045) calc(100% / 8 - 1px),
            rgba(60,45,30,0.045) calc(100% / 8)
        );
}
.bg-blob {
    position: fixed; border-radius: 50%; pointer-events: none; z-index: 0;
    filter: blur(100px); opacity: 0.28;
}
.bg-blob-1 {
    width: 55vw; height: 55vw;
    top: -20%; left: -15%;
    background: radial-gradient(circle, #e8d5f5, #f3e8ff);
    animation: blobFloat1 18s ease-in-out infinite;
}
.bg-blob-2 {
    width: 40vw; height: 40vw;
    bottom: -15%; right: -10%;
    background: radial-gradient(circle, #fde8c0, #fff3e0);
    animation: blobFloat2 22s ease-in-out infinite;
}
@keyframes blobFloat1 {
    0%,100%{transform:translate(0,0) scale(1);}
    40%{transform:translate(4%,6%) scale(1.06);}
    70%{transform:translate(-3%,3%) scale(0.97);}
}
@keyframes blobFloat2 {
    0%,100%{transform:translate(0,0) scale(1);}
    50%{transform:translate(-5%,-5%) scale(1.08);}
}

/* ══════════════════════════════════════════════════
   BACKGROUND WATERMARK SIDES
══════════════════════════════════════════════════ */
.bg-side-word {
    position: fixed;
    top: 50%;
    font-family: var(--serif);
    font-size: clamp(80px, 12vw, 150px);
    font-weight: 700;
    font-style: italic;
    color: transparent;
    -webkit-text-stroke: 2px rgba(99,102,241,0.18);
    white-space: nowrap;
    pointer-events: none;
    z-index: 0;
    text-shadow: 0 0 60px rgba(99,102,241,0.12), 0 0 120px rgba(99,102,241,0.06);
    user-select: none;
    letter-spacing: .06em;
    writing-mode: vertical-rl;
    text-orientation: mixed;
}
.bg-side-left {
    left: 8px;
    transform: translateY(-50%) rotate(180deg);
}
.bg-side-right {
    right: 8px;
    transform: translateY(-50%);
}
@media (max-width: 600px) {
    .bg-side-word { font-size: 60px; }
    .bg-side-left { left: 2px; }
    .bg-side-right { right: 2px; }
}

/* ══════════════════════════════════════════════════
   BACK LINK
══════════════════════════════════════════════════ */
.back-link {
    position: fixed; top: 20px; left: 24px; z-index: 100;
    display: inline-flex; align-items: center; gap: 7px;
    font-family: var(--sans); font-size: .78rem; font-weight: 700;
    color: var(--muted); text-decoration: none;
    background: rgba(250,248,243,0.85); backdrop-filter: blur(10px);
    border: 1px solid var(--border); border-radius: 100px;
    padding: 8px 16px;
    transition: all .2s;
}
.back-link:hover { color: var(--ink); transform: translateX(-2px); }

/* ══════════════════════════════════════════════════
   PAGE WRAPPER
══════════════════════════════════════════════════ */
.page-wrap {
    height: 100vh;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 0 20px;
    position: relative; z-index: 1;
    overflow: hidden;
}

/* ══════════════════════════════════════════════════
   SECTION LABEL
══════════════════════════════════════════════════ */
.section-label {
    display: inline-flex; align-items: center; gap: 6px;
    font-family: var(--sans); font-size: .65rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .18em;
    color: var(--muted);
    border: 1px solid var(--border); border-radius: 100px;
    padding: 5px 14px; margin-bottom: 18px;
    background: rgba(255,255,255,0.7); backdrop-filter: blur(8px);
}

/* ══════════════════════════════════════════════════
   MAIN CARD
══════════════════════════════════════════════════ */
.feedback-card {
    width: 100%; max-width: 520px;
    background: rgba(255, 255, 255, 0.78);
    backdrop-filter: blur(28px) saturate(180%);
    -webkit-backdrop-filter: blur(28px) saturate(180%);
    border: 1.5px solid rgba(165,180,252,0.4);
    border-radius: var(--card-r);
    box-shadow: var(--shadow);
    padding: 28px 32px 24px;
    position: relative; overflow: hidden;
    animation: cardIn .55s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes cardIn {
    from { opacity:0; transform:translateY(32px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
/* subtle inner glow top */
.feedback-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.9), transparent);
}

/* ══════════════════════════════════════════════════
   AVATAR — RAINBOW BORDER
══════════════════════════════════════════════════ */
.avatar-wrap {
    display: flex; flex-direction: column; align-items: center;
    margin-bottom: 14px;
}
/* Rainbow ring — only the RING rotates, not the image */
.avatar-ring {
    width: 72px; height: 72px;
    border-radius: 50%;
    position: relative;
    margin-bottom: 10px;
    flex-shrink: 0;
}
.avatar-ring::before {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    background: conic-gradient(
        #f87171, #fb923c, #fbbf24, #4ade80,
        #38bdf8, #a78bfa, #f472b6, #f87171
    );
    animation: rainbowRingRotate 3s linear infinite;
    z-index: 0;
}
@keyframes rainbowRingRotate {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.avatar-inner {
    width: 100%; height: 100%; border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.95);
    overflow: hidden; background: #f3f3f3;
    position: relative; z-index: 1;
}
.avatar-inner img {
    width: 100%; height: 100%; object-fit: cover; display: block;
    border-radius: 50%;
}
.user-name-row {
    display: flex; align-items: center; gap: 8px;
}
.user-name {
    font-family: var(--serif);
    font-size: 1.45rem; font-weight: 700;
    color: var(--ink); line-height: 1;
}
.gender-icon { font-size: 1rem; }

/* ══════════════════════════════════════════════════
   CARD HEADING
══════════════════════════════════════════════════ */
.card-heading {
    text-align: center; margin-bottom: 14px;
}
.card-title {
    font-family: var(--serif);
    font-size: 1.65rem; font-weight: 700; font-style: italic;
    color: var(--ink); line-height: 1.2;
    margin-bottom: 4px;
}
.card-sub {
    font-size: .75rem; color: var(--muted); font-weight: 500;
    line-height: 1.5;
}

/* ══════════════════════════════════════════════════
   TEXTAREA + WORD COUNT
══════════════════════════════════════════════════ */
.field-wrap { position: relative; margin-bottom: 6px; }
/* Mood tags */
.mood-tags {
    display: flex; flex-wrap: wrap; gap: 7px;
    margin-bottom: 12px;
}
.mood-tag {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 5px 12px;
    background: rgba(255,255,255,0.8);
    border: 1.5px solid rgba(165,180,252,0.5);
    border-radius: 100px;
    font-size: .72rem; font-weight: 700;
    color: #4338ca; cursor: pointer;
    transition: all .18s;
    user-select: none;
}
.mood-tag:hover, .mood-tag.active {
    background: rgba(165,180,252,0.3);
    border-color: #818cf8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99,102,241,0.2);
}
.feedback-textarea {
    width: 100%;
    min-height: 88px;
    background: rgba(255,255,255,0.75);
    border: 1.5px solid rgba(165,180,252,0.35);
    border-radius: 14px;
    padding: 12px 14px;
    font-family: var(--sans);
    font-size: .9rem; color: #1e1b4b;
    resize: none; outline: none;
    transition: border-color .2s, box-shadow .2s;
    line-height: 1.6;
}
.feedback-textarea::placeholder { color: #a5b4fc; }
.feedback-textarea:focus {
    border-color: rgba(129,140,248,0.7);
    box-shadow: 0 0 0 3px rgba(129,140,248,0.15);
}
.word-count-row {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 14px;
}
.word-count {
    font-size: .7rem; font-weight: 700; color: var(--muted);
    transition: color .2s;
}
.word-count.over  { color: var(--red-soft); }
.word-count.good  { color: #22c55e; }
.word-hint {
    font-size: .66rem; color: var(--muted); font-style: italic;
}

/* ══════════════════════════════════════════════════
   EMOJI RATING
══════════════════════════════════════════════════ */
.rating-section {
    margin-bottom: 14px;
    opacity: 0; transform: translateY(10px);
    transition: opacity .35s ease, transform .35s ease;
    pointer-events: none;
}
.rating-section.visible {
    opacity: 1; transform: translateY(0);
    pointer-events: auto;
}
.rating-label-top {
    font-size: .72rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .1em; color: var(--muted); margin-bottom: 12px;
    text-align: center;
}
.emoji-row {
    display: flex; justify-content: space-between;
    gap: 4px; flex-wrap: nowrap;
}
.emoji-btn {
    flex: 1; min-width: 0;
    background: rgba(250,248,243,0.9);
    border: 1.5px solid var(--border);
    border-radius: 12px;
    padding: 8px 2px 6px;
    font-size: 1.3rem; line-height: 1;
    cursor: pointer;
    display: flex; flex-direction: column; align-items: center; gap: 2px;
    transition: all .18s ease;
    position: relative;
}
.emoji-btn:hover {
    transform: translateY(-4px) scale(1.15);
    border-color: rgba(192,132,252,0.5);
    background: rgba(192,132,252,0.07);
    z-index: 2;
}
.emoji-btn.selected {
    transform: translateY(-6px) scale(1.22);
    border-color: var(--accent);
    background: rgba(192,132,252,0.12);
    box-shadow: 0 8px 20px rgba(192,132,252,0.25);
    z-index: 3;
}
.emoji-num {
    font-size: .5rem; font-weight: 800;
    color: var(--muted); letter-spacing: .02em;
}
.emoji-btn.selected .emoji-num { color: var(--accent); }
.rating-desc {
    text-align: center; margin-top: 12px;
    font-family: var(--serif); font-style: italic;
    font-size: 1.05rem; color: var(--ink2);
    min-height: 24px;
    transition: all .2s;
}

/* ══════════════════════════════════════════════════
   SUBMIT BUTTON
══════════════════════════════════════════════════ */
.submit-btn {
    width: 100%;
    padding: 16px;
    background: var(--ink);
    color: #fff;
    border: none; border-radius: 14px;
    font-family: var(--sans); font-size: .92rem; font-weight: 800;
    letter-spacing: .05em; text-transform: uppercase;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 9px;
    transition: all .22s ease;
    position: relative; overflow: hidden;
}
.submit-btn::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(192,132,252,0.2), transparent);
    opacity: 0; transition: opacity .22s;
}
.submit-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(26,20,16,0.25); }
.submit-btn:hover::after { opacity: 1; }
.submit-btn:active { transform: translateY(0); }
.submit-btn:disabled {
    opacity: 0.45; cursor: not-allowed; transform: none; box-shadow: none;
}

/* ══════════════════════════════════════════════════
   ERROR MESSAGE
══════════════════════════════════════════════════ */
.error-msg {
    background: rgba(224,85,85,0.08);
    border: 1px solid rgba(224,85,85,0.25);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: .8rem; font-weight: 700; color: var(--red-soft);
    margin-bottom: 18px;
    display: flex; align-items: center; gap: 8px;
}

/* ══════════════════════════════════════════════════
   SUCCESS STATE
══════════════════════════════════════════════════ */
.success-state {
    text-align: center; padding: 10px 0;
    animation: fadeIn .4s ease both;
}
@keyframes fadeIn { from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:none;} }
.success-icon {
    width: 72px; height: 72px;
    background: linear-gradient(135deg, #4ade80, #22c55e);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(74,222,128,0.3);
    animation: successPop .5s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes successPop {
    from{transform:scale(0);} to{transform:scale(1);}
}
.success-title {
    font-family: var(--serif);
    font-size: 2rem; font-weight: 700; font-style: italic;
    color: var(--ink); margin-bottom: 8px;
}
.success-sub {
    font-size: .85rem; color: var(--muted); line-height: 1.6;
    margin-bottom: 28px;
}
.success-note {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: .75rem; font-weight: 700;
    color: var(--muted); background: var(--cream2);
    border: 1px solid var(--border); border-radius: 100px;
    padding: 6px 16px;
}
.back-home-btn {
    display: inline-flex; align-items: center; gap: 8px;
    margin-top: 20px;
    padding: 13px 28px;
    background: var(--ink); color: #fff;
    border: none; border-radius: 12px;
    font-family: var(--sans); font-size: .85rem; font-weight: 800;
    text-decoration: none; letter-spacing: .04em;
    transition: all .2s;
}
.back-home-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(26,20,16,0.22); }

/* ══════════════════════════════════════════════════
   COOLDOWN STATE
══════════════════════════════════════════════════ */
.cooldown-state {
    text-align: center; padding: 10px 0;
    animation: fadeIn .4s ease both;
}
.cooldown-circle-wrap { margin: 0 auto 24px; width: 140px; height: 140px; position: relative; }
.cooldown-svg { width: 140px; height: 140px; transform: rotate(-90deg); }
.cooldown-track {
    fill: none; stroke: rgba(60,45,30,0.1); stroke-width: 8;
}
.cooldown-prog {
    fill: none; stroke: url(#coolGrad); stroke-width: 8;
    stroke-linecap: round;
    stroke-dasharray: 395;
    transition: stroke-dashoffset .8s ease;
}
.cooldown-inner {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
}
.cooldown-days {
    font-family: var(--serif); font-size: 2.4rem; font-weight: 700;
    color: var(--ink); line-height: 1;
}
.cooldown-unit {
    font-size: .68rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .12em; color: var(--muted); margin-top: 2px;
}
.cooldown-title {
    font-family: var(--serif);
    font-size: 1.75rem; font-weight: 700; font-style: italic;
    color: var(--ink); margin-bottom: 8px;
}
.cooldown-sub {
    font-size: .82rem; color: var(--muted); line-height: 1.6;
    margin-bottom: 20px; max-width: 340px; margin-left: auto; margin-right: auto;
}
.prev-feedback-box {
    background: var(--cream2);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 20px;
    text-align: left;
}
.prev-fb-label {
    font-size: .62rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .14em; color: var(--muted); margin-bottom: 6px;
}
.prev-fb-text {
    font-family: var(--serif); font-style: italic;
    font-size: 1rem; color: var(--ink2); line-height: 1.6;
}
.prev-fb-rating {
    margin-top: 8px; font-size: .82rem; color: var(--muted); font-weight: 600;
}

/* ══════════════════════════════════════════════════
   MOBILE
══════════════════════════════════════════════════ */
@media (max-width: 600px) {
    .feedback-card { padding: 20px 16px 18px; border-radius: 20px; max-width: 95vw; }
    .card-title { font-size: 1.35rem; }
    .emoji-btn { font-size: .95rem; padding: 5px 1px 4px; border-radius: 8px; }
    .emoji-num { font-size: .38rem; }
    .avatar-ring { width: 58px; height: 58px; }
    .user-name { font-size: 1.1rem; }
    .back-link { top: 12px; left: 12px; font-size: .68rem; padding: 5px 10px; }
    .page-wrap { height: 100dvh; overflow: hidden; }
    .feedback-textarea { min-height: 72px; }
}
</style>
</head>
<body>
<div class="bg-lines"></div>
<div class="bg-blob bg-blob-1"></div>
<div class="bg-blob bg-blob-2"></div>
<div class="bg-side-word bg-side-left">Feedback</div>
<div class="bg-side-word bg-side-right">Feedback</div>

<a href="index.php" class="back-link">
    <i class="fa-solid fa-arrow-left"></i> Back to Home
</a>

<div class="page-wrap">
    <div class="section-label">
        <i class="fa-solid fa-comment-dots"></i>
        Share Your Thoughts
    </div>

    <div class="feedback-card" id="mainCard">

        <?php if ($success_msg === 'submitted'): ?>
        <!-- ── SUCCESS STATE ── -->
        <div class="success-state">
            <div class="avatar-wrap">
                <div class="avatar-ring">
                    <div class="avatar-inner">
                        <img src="<?= $avatar_url ?>" alt="<?= $username ?>" onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=<?= urlencode($username) ?>'">
                    </div>
                </div>
                <div class="user-name-row">
                    <span class="user-name"><?= $username ?></span>
                    <span class="gender-icon"><?= $gender_icon ?></span>
                </div>
            </div>
            <div class="success-icon">✓</div>
            <div class="success-title">Thank you, <?= $username ?>!</div>
            <div class="success-sub">
                Your feedback means a lot. <br>
                Arigato Devan ko better banane mein help ki tumne. 💜
            </div>
            <div class="success-note">
                <i class="fa-solid fa-clock"></i>
                Next feedback in 7 days
            </div>
            <br>
            <a href="index.php" class="back-home-btn">
                <i class="fa-solid fa-house"></i> Back to Home
            </a>
        </div>

        <?php elseif ($already_submitted): ?>
        <!-- ── COOLDOWN STATE ── -->
        <div class="cooldown-state">
            <div class="avatar-wrap">
                <div class="avatar-ring">
                    <div class="avatar-inner">
                        <img src="<?= $avatar_url ?>" alt="<?= $username ?>" onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=<?= urlencode($username) ?>'">
                    </div>
                </div>
                <div class="user-name-row">
                    <span class="user-name"><?= $username ?></span>
                    <span class="gender-icon"><?= $gender_icon ?></span>
                </div>
            </div>

            <div class="cooldown-circle-wrap">
                <svg class="cooldown-svg" viewBox="0 0 140 140" id="cooldownSvg">
                    <defs>
                        <linearGradient id="coolGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%"   stop-color="#a78bfa"/>
                            <stop offset="100%" stop-color="#f472b6"/>
                        </linearGradient>
                    </defs>
                    <circle class="cooldown-track" cx="70" cy="70" r="63"/>
                    <circle class="cooldown-prog"  cx="70" cy="70" r="63"
                        id="cooldownProg"
                        style="stroke-dashoffset: <?= 395 - (395 * $cooldown_days_left / 7) ?>"/>
                </svg>
                <div class="cooldown-inner">
                    <div class="cooldown-days"><?= $cooldown_days_left ?></div>
                    <div class="cooldown-unit">day<?= $cooldown_days_left !== 1 ? 's' : '' ?> left</div>
                </div>
            </div>

            <div class="cooldown-title">Already shared! 🎉</div>
            <div class="cooldown-sub">
                Tumhara feedback record ho gaya hai.<br>
                <?= $cooldown_days_left ?> din baad phir se share kar sakte ho.
            </div>

            <?php if ($existing): ?>
            <div class="prev-feedback-box">
                <div class="prev-fb-label">Your last feedback</div>
                <div class="prev-fb-text">"<?= htmlspecialchars($existing['feedback_text']) ?>"</div>
                <?php
                $emojis = ['😭','😢','😟','😕','🙂','😊','😄','😁','🤩','🔥','⭐'];
                $r = (int)($existing['rating'] ?? 0);
                $clamp = max(0, min(10, $r));
                ?>
                <div class="prev-fb-rating"><?= $emojis[$clamp] ?> Rating: <?= $clamp ?>/10</div>
            </div>
            <?php endif; ?>

            <a href="index.php" class="back-home-btn">
                <i class="fa-solid fa-house"></i> Back to Home
            </a>
        </div>

        <?php else: ?>
        <!-- ── FORM STATE ── -->

        <div class="avatar-wrap">
            <div class="avatar-ring">
                <div class="avatar-inner">
                    <img src="<?= $avatar_url ?>" alt="<?= $username ?>" onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=<?= urlencode($username) ?>'">
                </div>
            </div>
            <div class="user-name-row">
                <span class="user-name"><?= $username ?></span>
                <span class="gender-icon"><?= $gender_icon ?></span>
            </div>
        </div>

        <div class="card-heading">
            <div class="card-title">How's your experience?</div>
            <div class="card-sub">Share what you think — honest feedback helps us grow.</div>
        </div>

        <?php if ($error_msg): ?>
        <div class="error-msg">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($error_msg) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="feedbackForm">
            <!-- Mood Tags -->
            <div class="mood-tags" id="moodTags">
                <span class="mood-tag" onclick="toggleMood(this)">🔥 Loved the prompts</span>
                <span class="mood-tag" onclick="toggleMood(this)">✨ Clean UI</span>
                <span class="mood-tag" onclick="toggleMood(this)">💡 Need more features</span>
                <span class="mood-tag" onclick="toggleMood(this)">⚡ Super fast</span>
                <span class="mood-tag" onclick="toggleMood(this)">❤️ Keep going!</span>
            </div>
            <div class="field-wrap">
                <textarea
                    class="feedback-textarea"
                    name="feedback_text"
                    id="feedbackText"
                    placeholder="Your thoughts in 3–20 words..."
                    maxlength="300"
                ><?= htmlspecialchars($_POST['feedback_text'] ?? '') ?></textarea>
            </div>

            <div class="word-count-row">
                <span class="word-count" id="wordCount">0 / 20 words</span>
                <span class="word-hint">min 3 · max 20 words</span>
            </div>

            <!-- EMOJI RATING -->
            <div class="rating-section" id="ratingSection">
                <div class="rating-label-top">
                    <i class="fa-solid fa-star" style="color:#fbbf24;font-size:.65rem"></i>
                    Rate your experience
                </div>
                <div class="emoji-row" id="emojiRow">
                    <?php
                    $emojis_list = ['😭','😢','😟','😕','🙂','😊','😄','😁','🤩','🔥','⭐'];
                    foreach ($emojis_list as $i => $em):
                    ?>
                    <button type="button" class="emoji-btn" data-rating="<?= $i ?>" onclick="selectRating(<?= $i ?>)">
                        <?= $em ?>
                        <span class="emoji-num"><?= $i ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <div class="rating-desc" id="ratingDesc">Tap to rate</div>
                <input type="hidden" name="rating" id="ratingInput" value="-1">
            </div>

            <button type="submit" class="submit-btn" id="submitBtn" disabled>
                <i class="fa-solid fa-paper-plane"></i>
                Send Feedback
            </button>
        </form>
        <?php endif; ?>

    </div><!-- .feedback-card -->
</div><!-- .page-wrap -->

<script>
const ratingDescs = [
    "0 — That bad? We're so sorry 😔",
    "1 — Really rough. We'll work on it.",
    "2 — Not great. Your input helps!",
    "3 — Below expectations. Noted.",
    "4 — Could be better. Fair enough.",
    "5 — Decent! Room to improve.",
    "6 — Good! We're getting there.",
    "7 — Pretty good! Glad you like it.",
    "8 — Great experience! Thank you!",
    "9 — Loved it! You made our day 🙌",
    "10 — Perfect! You're amazing! ⭐"
];

let selectedRating = -1;

function countWords(str) {
    const s = str.trim();
    if (!s) return 0;
    return s.split(/\s+/).filter(w => w.length > 0).length;
}

function toggleMood(el) {
    el.classList.toggle('active');
    // Append/remove tag text to textarea
    const txt = document.getElementById('feedbackText');
    const tag = el.textContent.trim().split(' ').slice(1).join(' '); // remove emoji
    if (el.classList.contains('active')) {
        const cur = txt.value.trim();
        txt.value = cur ? cur + ' ' + tag : tag;
    }
    updateWordCount();
}

function updateWordCount() {
    const txt  = document.getElementById('feedbackText').value;
    const wc   = countWords(txt);
    const el   = document.getElementById('wordCount');
    const rating = document.getElementById('ratingSection');

    el.textContent = wc + ' / 20 words';
    el.classList.remove('over','good');

    if (wc > 20) {
        el.classList.add('over');
    } else if (wc >= 3) {
        el.classList.add('good');
        rating.classList.add('visible');
    } else {
        rating.classList.remove('visible');
    }

    checkSubmit();
}

function selectRating(r) {
    selectedRating = r;
    document.getElementById('ratingInput').value = r;
    document.querySelectorAll('.emoji-btn').forEach((btn, i) => {
        btn.classList.toggle('selected', i === r);
    });
    document.getElementById('ratingDesc').textContent = ratingDescs[r];
    checkSubmit();
}

function checkSubmit() {
    const txt = document.getElementById('feedbackText').value;
    const wc  = countWords(txt);
    const ok  = wc >= 3 && wc <= 20 && selectedRating >= 0;
    document.getElementById('submitBtn').disabled = !ok;
}

document.getElementById('feedbackText')?.addEventListener('input', updateWordCount);
updateWordCount();

// Form submit loading + confetti on success
document.getElementById('feedbackForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Sending...';
});

// ── CONFETTI on success ──
(function(){
    if (!document.querySelector('.success-state')) return;
    const canvas = document.createElement('canvas');
    canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;';
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    const colors = ['#818cf8','#a5b4fc','#c084fc','#f472b6','#34d399','#fbbf24','#60a5fa'];
    const pieces = Array.from({length:120}, () => ({
        x: Math.random() * canvas.width,
        y: Math.random() * -canvas.height,
        r: Math.random() * 6 + 3,
        d: Math.random() * 120 + 60,
        color: colors[Math.floor(Math.random() * colors.length)],
        tilt: Math.random() * 10 - 5,
        tiltAngle: 0,
        tiltSpeed: Math.random() * 0.1 + 0.04
    }));
    let angle = 0, frame = 0;
    function draw() {
        ctx.clearRect(0,0,canvas.width,canvas.height);
        angle += 0.01;
        pieces.forEach(p => {
            p.tiltAngle += p.tiltSpeed;
            p.y += (Math.cos(angle + p.d) + 2);
            p.x += Math.sin(angle) * 1.2;
            p.tilt = Math.sin(p.tiltAngle) * 12;
            ctx.beginPath();
            ctx.lineWidth = p.r;
            ctx.strokeStyle = p.color;
            ctx.moveTo(p.x + p.tilt + p.r/2, p.y);
            ctx.lineTo(p.x + p.tilt, p.y + p.tilt + p.r/2);
            ctx.stroke();
            if (p.y > canvas.height) { p.y = -10; p.x = Math.random() * canvas.width; }
        });
        frame++;
        if (frame < 220) requestAnimationFrame(draw);
        else canvas.remove();
    }
    draw();
})();
</script>
</body>
</html>
