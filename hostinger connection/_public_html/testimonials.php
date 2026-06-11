<?php
session_start();
require_once "db.php";

// Fetch all approved testimonials
$testimonials = [];
try {
    $stmt = $pdo->query("
        SELECT f.feedback_text, f.rating, f.submitted_at,
               u.username, u.avatar, u.profile_image, u.gender
        FROM feedbacks f
        LEFT JOIN users u ON f.user_id = u.id
        WHERE f.show_on_homepage = 1
        ORDER BY f.submitted_at DESC
    ");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $testimonials = []; }

$emojis = ['😭','😢','😟','😕','🙂','😊','😄','😁','🤩','🔥','⭐'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Testimonials — Arigato Devan Prompts</title>
<meta name="description" content="See what real users say about Arigato Devan Prompts — honest feedback from our community.">
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,700&family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
    font-family: 'Inter', sans-serif;
    background: #f5f5f5;
    color: #1a1a2e;
    min-height: 100vh;
    overflow-x: hidden;
}

/* ── Background ── */
.testi-bg {
    position: fixed; inset: 0; z-index: 0;
    background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 35%, #fdf4ff 65%, #fff8f0 100%);
}
.testi-bg::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
        radial-gradient(circle at 15% 25%, rgba(165,180,252,0.18) 0%, transparent 50%),
        radial-gradient(circle at 85% 70%, rgba(251,191,36,0.12) 0%, transparent 45%),
        radial-gradient(circle at 50% 50%, rgba(192,132,252,0.08) 0%, transparent 60%);
}
/* Dot grid */
.testi-bg::after {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(99,102,241,0.08) 1px, transparent 1px);
    background-size: 28px 28px;
}

/* ── Back link ── */
.back-link {
    position: fixed; top: 20px; left: 24px; z-index: 100;
    display: inline-flex; align-items: center; gap: 7px;
    font-size: .78rem; font-weight: 700; color: #6b7280;
    text-decoration: none;
    background: rgba(255,255,255,0.88); backdrop-filter: blur(12px);
    border: 1px solid rgba(0,0,0,0.08); border-radius: 100px;
    padding: 8px 16px; transition: all .2s;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.back-link:hover { color: #1a1a2e; transform: translateX(-3px); }

/* ── Page Layout ── */
.page-root {
    position: relative; z-index: 1;
    min-height: 100vh;
    display: flex; flex-direction: column;
    align-items: center;
    padding: 60px 24px 80px;
}

/* ── Header ── */
.testi-header {
    text-align: center;
    margin-bottom: 60px;
    max-width: 600px;
}
.testi-eyebrow {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: .62rem; font-weight: 900; text-transform: uppercase;
    letter-spacing: .22em; color: #e05555;
    margin-bottom: 16px;
}
.testi-eyebrow-line {
    display: inline-block; width: 20px; height: 2px;
    background: #e05555; border-radius: 2px;
}
.testi-h1 {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(2.2rem, 5vw, 3.4rem);
    font-weight: 700; line-height: 1.15;
    color: #1a1a2e; margin-bottom: 16px;
}
.testi-sub {
    font-size: .88rem; color: #6b7280; font-weight: 500;
    line-height: 1.7; max-width: 460px; margin: 0 auto;
}

/* ── Main Layout: avatars left, card right ── */
.testi-main {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 40px;
    width: 100%;
    max-width: 860px;
    align-items: center;
    min-height: 380px;
}

/* ── Avatar Column ── */
.avatar-col {
    display: flex;
    flex-direction: column;
    gap: 18px;
    align-items: center;
}
.av-item {
    width: 80px; height: 80px;
    border-radius: 18px;
    overflow: hidden;
    cursor: pointer;
    transition: all .35s cubic-bezier(.34,1.56,.64,1);
    border: 3px solid transparent;
    filter: grayscale(1) brightness(0.75);
    position: relative;
    flex-shrink: 0;
}
.av-item.active {
    filter: grayscale(0) brightness(1);
    border-color: #e05555;
    transform: scale(1.12) translateX(6px);
    box-shadow: 0 10px 32px rgba(224,85,85,0.25);
    border-radius: 22px;
}
.av-item img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
}

/* ── Quote Card ── */
.quote-card {
    background: #fff;
    border-radius: 28px;
    padding: 44px 44px 36px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.08), 0 4px 16px rgba(0,0,0,0.04);
    position: relative;
    overflow: hidden;
    transition: opacity .3s ease, transform .3s ease;
    min-height: 280px;
}
.quote-card.fade-out {
    opacity: 0; transform: translateX(12px);
}
.quote-card.fade-in {
    animation: cardFadeIn .38s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes cardFadeIn {
    from { opacity: 0; transform: translateX(-14px); }
    to   { opacity: 1; transform: translateX(0); }
}

/* Decorative big quote mark */
.deco-quote {
    position: absolute;
    top: 20px; right: 28px;
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 8rem; font-weight: 700;
    color: rgba(0,0,0,0.04);
    line-height: 1; pointer-events: none;
    user-select: none;
}

/* Quote body */
.quote-text {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(1.35rem, 2.5vw, 1.75rem);
    font-weight: 600; line-height: 1.45;
    color: #1a1a2e;
    margin-bottom: 14px;
    position: relative; z-index: 1;
    max-width: 520px;
}
.quote-sub {
    font-size: .85rem; color: #6b7280; font-weight: 500;
    line-height: 1.6; margin-bottom: 28px;
}

/* Name + rating row */
.quote-footer {
    display: flex; align-items: center;
    justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.quote-author {
    display: flex; flex-direction: column;
}
.author-name {
    font-weight: 800; font-size: .92rem; color: #1a1a2e;
}
.author-role {
    font-size: .72rem; color: #9ca3af; font-weight: 500;
    margin-top: 1px;
}
.quote-divider {
    width: 100%; height: 1px;
    background: repeating-linear-gradient(90deg, #e5e7eb 0, #e5e7eb 6px, transparent 6px, transparent 12px);
    margin: 20px 0 16px;
}
.quote-rating {
    display: flex; align-items: center; gap: 4px;
}
.rating-emoji { font-size: 1.35rem; }
.rating-num {
    font-weight: 900; font-size: .82rem; color: #6b7280;
    margin-left: 4px;
}

/* Progress dots */
.testi-dots {
    display: flex; gap: 8px; margin-top: 36px;
    justify-content: center;
}
.testi-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #e5e7eb; cursor: pointer;
    transition: all .3s;
}
.testi-dot.active {
    background: #e05555;
    width: 28px; border-radius: 4px;
}

/* Empty state */
.empty-state {
    text-align: center; padding: 80px 24px;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.4rem; color: #9ca3af;
}

/* ── Mobile ── */
@media (max-width: 680px) {
    .testi-main {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .avatar-col {
        flex-direction: row;
        overflow-x: auto;
        width: 100%;
        padding-bottom: 4px;
        scrollbar-width: none;
        justify-content: flex-start;
    }
    .avatar-col::-webkit-scrollbar { display: none; }
    .av-item {
        width: 64px; height: 64px;
        flex-shrink: 0;
    }
    .av-item.active {
        transform: scale(1.1) translateY(-4px);
    }
    .quote-card { padding: 28px 22px 24px; }
    .testi-h1 { font-size: 2rem; }
    .deco-quote { font-size: 5rem; top: 10px; right: 16px; }
}
</style>
</head>
<body>
<div class="testi-bg"></div>

<a href="index.php" class="back-link">
    <i class="fa-solid fa-arrow-left"></i> Back to Home
</a>

<div class="page-root">

    <!-- Header -->
    <div class="testi-header">
        <div class="testi-eyebrow">
            <span class="testi-eyebrow-line"></span>
            What users say
            <span class="testi-eyebrow-line"></span>
        </div>
        <h1 class="testi-h1">Honest Feedback<br>From Valued People</h1>
        <p class="testi-sub">Real feedback from people who use Arigato Devan Prompts every day. Their words reflect the impact of our work.</p>
    </div>

    <?php if (empty($testimonials)): ?>
    <div class="empty-state">
        <i class="fa-regular fa-comment-dots" style="font-size:3rem;margin-bottom:16px;display:block;"></i>
        No testimonials yet. Be the first to share feedback!<br>
        <a href="feedback.php" style="display:inline-block;margin-top:18px;padding:12px 28px;background:#1a1a2e;color:#fff;border-radius:12px;text-decoration:none;font-weight:700;font-size:.88rem;">Share Feedback</a>
    </div>
    <?php else: ?>

    <!-- Main Testimonial Section -->
    <div class="testi-main" id="testiMain">

        <!-- Left: Avatar Column -->
        <div class="avatar-col" id="avatarCol">
            <?php foreach ($testimonials as $i => $t): ?>
            <?php
            $av = !empty($t['avatar']) ? $t['avatar'] : ($t['profile_image'] ?? '');
            $seed = urlencode($t['username'] ?? 'user');
            $fallback = "https://api.dicebear.com/7.x/avataaars/svg?seed={$seed}";
            ?>
            <div class="av-item <?= $i === 0 ? 'active' : '' ?>"
                 id="av-<?= $i ?>"
                 onclick="switchTo(<?= $i ?>)"
                 title="<?= htmlspecialchars($t['username'] ?? 'User') ?>">
                <img src="<?= $av ? htmlspecialchars($av) : $fallback ?>"
                     alt="<?= htmlspecialchars($t['username'] ?? 'User') ?>"
                     onerror="this.src='<?= $fallback ?>'">
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Right: Quote Card -->
        <div class="quote-card fade-in" id="quoteCard">
            <div class="deco-quote">&ldquo;</div>
            <?php
            $t0 = $testimonials[0];
            $r0 = max(0, min(10, (int)($t0['rating'] ?? 0)));
            $em0 = $emojis[$r0];
            $g0 = strtolower(trim($t0['gender'] ?? ''));
            $gi0 = in_array($g0,['male','m']) ? ' ♂' : (in_array($g0,['female','f']) ? ' ♀' : '');
            ?>
            <p class="quote-text" id="quoteText"><?= htmlspecialchars($t0['feedback_text']) ?></p>
            <div class="quote-divider"></div>
            <div class="quote-footer">
                <div class="quote-author">
                    <span class="author-name" id="authorName"><?= htmlspecialchars($t0['username'] ?? 'User') ?><?= $gi0 ?></span>
                    <span class="author-role">Arigato User</span>
                </div>
                <div class="quote-rating" id="quoteRating">
                    <span class="rating-emoji"><?= $em0 ?></span>
                    <span class="rating-num"><?= $r0 ?>/10</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Dots nav -->
    <div class="testi-dots" id="testiDots">
        <?php foreach ($testimonials as $i => $t): ?>
        <div class="testi-dot <?= $i === 0 ? 'active' : '' ?>" onclick="switchTo(<?= $i ?>)" id="dot-<?= $i ?>"></div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>

</div><!-- .page-root -->

<script>
const testimonials = <?= json_encode(array_map(function($t) {
    return [
        'username'      => $t['username'] ?? 'User',
        'feedback_text' => $t['feedback_text'],
        'rating'        => (int)($t['rating'] ?? 0),
        'gender'        => $t['gender'] ?? '',
        'avatar'        => !empty($t['avatar']) ? $t['avatar'] : ($t['profile_image'] ?? ''),
    ];
}, $testimonials)) ?>;

const emojis = ['😭','😢','😟','😕','🙂','😊','😄','😁','🤩','🔥','⭐'];
let current = 0;
let autoTimer = null;

function switchTo(idx) {
    if (idx === current) return;

    // Deactivate old avatar + dot
    document.getElementById('av-' + current)?.classList.remove('active');
    document.getElementById('dot-' + current)?.classList.remove('active');

    current = idx;

    // Activate new avatar + dot
    document.getElementById('av-' + idx)?.classList.add('active');
    document.getElementById('dot-' + idx)?.classList.add('active');

    // Animate card out then in
    const card = document.getElementById('quoteCard');
    card.classList.remove('fade-in');
    card.classList.add('fade-out');

    setTimeout(() => {
        const t = testimonials[idx];
        const r = Math.max(0, Math.min(10, t.rating));
        const g = (t.gender || '').toLowerCase().trim();
        const gi = ['male','m'].includes(g) ? ' ♂' : (['female','f'].includes(g) ? ' ♀' : '');

        document.getElementById('quoteText').textContent = t.feedback_text;
        document.getElementById('authorName').textContent = t.username + gi;
        document.getElementById('quoteRating').innerHTML =
            `<span class="rating-emoji">${emojis[r]}</span><span class="rating-num">${r}/10</span>`;

        // Scroll avatar into view on mobile
        document.getElementById('av-' + idx)?.scrollIntoView({behavior:'smooth', block:'nearest', inline:'center'});

        card.classList.remove('fade-out');
        card.classList.add('fade-in');
    }, 280);

    // Reset auto timer
    resetAutoTimer();
}

function resetAutoTimer() {
    clearInterval(autoTimer);
    if (testimonials.length > 1) {
        autoTimer = setInterval(() => {
            const next = (current + 1) % testimonials.length;
            switchTo(next);
        }, 5000);
    }
}

// Start auto-cycle
resetAutoTimer();

// Pause on hover
const card = document.getElementById('quoteCard');
if (card) {
    card.addEventListener('mouseenter', () => clearInterval(autoTimer));
    card.addEventListener('mouseleave', resetAutoTimer);
}
</script>
</body>
</html>
