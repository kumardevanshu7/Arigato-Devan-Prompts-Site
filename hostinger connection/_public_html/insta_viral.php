<?php
session_start();
require_once 'db.php';
if (isset($_SESSION['user_id']) && empty($_SESSION['onboarding_complete'])) {
    header("Location: onboarding.php"); exit();
}

// Fetch Insta Viral prompts by prompt_type
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked 
        FROM prompts p 
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ? 
        WHERE p.prompt_type = 'insta_viral'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $insta_viral = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $insta_viral = $pdo->query("SELECT *, 0 as is_unlocked FROM prompts WHERE prompt_type='insta_viral' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Insta Viral Reels &mdash; PromptVerse</title>
<meta name="description" content="Insta Viral Reels &mdash; Coming Soon on PromptVerse.">
<link rel="stylesheet" href="style.css?v=1778100000">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

<style>
.coming-soon-wrap {
    min-height: 70vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 60px 24px;
    position: relative;
    z-index: 2;
}
.cs-icon {
    font-size: 4.5rem;
    background: var(--primary-color);
    border: var(--border-width) solid var(--text-color);
    border-radius: 50%;
    width: 110px;
    height: 110px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-comic);
    margin-bottom: 28px;
    animation: pulse-icon 2s ease-in-out infinite;
}
@keyframes pulse-icon {
    0%, 100% { transform: scale(1) rotate(-3deg); }
    50% { transform: scale(1.08) rotate(3deg); }
}
.cs-title {
    font-size: 3rem;
    font-weight: 900;
    letter-spacing: -1px;
    margin-bottom: 12px;
    line-height: 1.1;
}
.cs-sub {
    font-size: 1.1rem;
    color: #666;
    font-weight: 600;
    max-width: 480px;
    line-height: 1.6;
    margin-bottom: 36px;
}
.cs-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--secondary-color);
    border: var(--border-width) solid var(--text-color);
    border-radius: 40px;
    padding: 12px 28px;
    font-weight: 900;
    font-size: 1rem;
    box-shadow: var(--shadow-comic);
    margin-bottom: 24px;
}
.cs-notify-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}
.cs-insta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
    color: #fff;
    border: var(--border-width) solid var(--text-color);
    border-radius: 14px;
    font-family: var(--font-main);
    font-weight: 800;
    font-size: 0.95rem;
    text-decoration: none;
    box-shadow: var(--shadow-comic);
    transition: all 0.2s;
}
.cs-insta-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-comic-hover);
}
</style>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body class="page-gallery">

<!-- Wallpaper Background -->
<div class="scroll-bg-container">
    <div class="bg-layer active" style="background-image:url('https://i.pinimg.com/736x/4d/e2/71/4de271ae9997273cf3fdd47098fa69a3.jpg')"></div>
    <div class="bg-layer" style="background-image:url('https://i.pinimg.com/1200x/76/50/aa/7650aa986d34ca65bb52f261f954149b.jpg')"></div>
    <div class="bg-layer" style="background-image:url('https://i.pinimg.com/1200x/64/c4/c5/64c4c528ee5812610d58ee2c98bbb76f.jpg')"></div>
    <div class="bg-layer" style="background-image:url('https://i.pinimg.com/736x/f9/fd/75/f9fd75e5aa551b89ac88a863921f2f75.jpg')"></div>
    <div class="bg-layer" style="background-image:url('https://i.pinimg.com/736x/a5/15/6a/a5156a264e06ebb47997cf59e66bee31.jpg')"></div>
    <div class="bg-creamy-overlay"></div>
</div>

<header>
    <div class="logo-area" id="logo-container"  style="cursor:pointer">
        <div class="logo-flipper">
            <div class="logo-front"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo" id="profile-logo"></div>
            <div class="logo-back"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt=""></div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="index.php">HOME</a>
        <a href="gallery.php">GALLERY</a>
        <a href="blogs.php">BLOGS</a>
        <a href="progress.php" title="Our Journey" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-chart-line nav-progress-icon"></i></a>
        <div class="nav-dropdown">
            <button class="nav-dropdown-btn"><i class="fa-solid fa-film"></i> Reels Type <i class="fa-solid fa-chevron-down dd-arrow"></i></button>
            <?php $curPage = basename($_SERVER['PHP_SELF']); ?>
            <div class="nav-dropdown-menu">
                <a href="secret_code.php" <?= $curPage == 'secret_code.php' ? 'style="background:var(--primary-color)"' : '' ?>><i class="fa-solid fa-lock"></i> Secret Code Reels <?= empty($nav_counts['secret_code']) ? '<span class="dd-tag soon">SOON</span>' : ($curPage == 'secret_code.php' ? '<span class="dd-tag">ACTIVE</span>' : '') ?></a>
                <a href="unreleased.php" <?= $curPage == 'unreleased.php' ? 'style="background:var(--primary-color)"' : '' ?>><i class="fa-solid fa-star"></i> Unreleased Reels <?= empty($nav_counts['unreleased']) ? '<span class="dd-tag soon">SOON</span>' : ($curPage == 'unreleased.php' ? '<span class="dd-tag">ACTIVE</span>' : '') ?></a>
                <a href="insta_viral.php" <?= $curPage == 'insta_viral.php' ? 'style="background:var(--primary-color)"' : '' ?>><i class="fa-brands fa-instagram"></i> Insta Viral Reels <?= empty($nav_counts['insta_viral']) ? '<span class="dd-tag soon">SOON</span>' : ($curPage == 'insta_viral.php' ? '<span class="dd-tag">ACTIVE</span>' : '') ?></a>
                <a href="already_uploaded.php" <?= $curPage == 'already_uploaded.php' ? 'style="background:var(--primary-color)"' : '' ?>><i class="bx bx-history"></i> Already Uploaded <?= empty($nav_counts['already_uploaded']) ? '<span class="dd-tag soon">SOON</span>' : ($curPage == 'already_uploaded.php' ? '<span class="dd-tag">ACTIVE</span>' : '') ?></a>
            </div>
        </div>
        <a href="https://www.instagram.com/arigato.devan/" target="_blank" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;font-family:var(--font-main);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            <span style="font-weight:600;">@arigato.devan</span><span class="pulse-dot"></span><span style="font-weight:800;font-size:1.1rem;">13K+</span>
        </a>
    </nav>
    <div class="header-right">
        <div class="header-divider"></div>
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if($_SESSION['role']==='admin'): ?>
                <div style="display:flex;align-items:center;gap:8px;"><a href="profile.php" title="Edit Profile"><?= renderAvatar($_SESSION['profile_image']??'', 'admin-avatar', 'Admin') ?></a><a href="dashboard.php" style="color:var(--text-color);font-weight:800;">ADMIN</a></div>
            <?php else: ?>
                <a href="profile.php" style="color:var(--text-color)"><?= renderAvatar($_SESSION['profile_image']??'', 'admin-avatar', 'Profile') ?></a>
            <?php endif; ?>
            <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
        <?php else: ?>
            <a href="login.php" class="comic-btn" style="font-size:.85rem;padding:9px 18px;text-decoration:none;color:var(--text-color);background:var(--primary-color);">LOGIN</a>
        <?php endif; ?>
    </div>
</header>

<?php if(empty($insta_viral)): ?>
<div class="coming-soon-wrap">
    <div class="cs-icon"><i class="fa-brands fa-instagram" style="font-size:2.5rem;"></i></div>
    <div class="cs-badge"><i class="fa-solid fa-clock"></i> Coming Very Soon</div>
    <h1 class="cs-title">Insta Viral<br><span class="highlight">Reels</span></h1>
    <p class="cs-sub">We're curating the hottest, most viral AI couple prompt reels from Instagram &mdash; just for you. Stay tuned, this is going to be <strong>huge</strong>. &#9889;</p>
    <div class="cs-notify-row">
        <a href="https://www.instagram.com/arigato.devan/" target="_blank" class="cs-insta-btn">
            <i class="fa-brands fa-instagram"></i> Follow @arigato.devan
        </a>
        <a href="index.php" class="comic-btn" style="text-decoration:none;padding:12px 22px;">
            <i class="fa-solid fa-arrow-left"></i> Explore Prompts
        </a>
    </div>
</div>
<?php else: ?>
<div class="container" style="padding-top:40px;position:relative;z-index:2;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px;">
        <div class="badge" style="margin:0;transform:rotate(-1deg);background:linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);color:white;border-color:white;"><i class="fa-brands fa-instagram"></i> INSTA VIRAL</div>
        <h1 style="font-size:2rem;font-weight:900;">Trending <span class="highlight">Now</span></h1>
    </div>
    <p style="color:#666;font-weight:600;margin-bottom:20px;">Solve the Math Challenge to unlock the viral prompt!</p>
    <?php
    // Collect sub-tags (excluding 'viral' itself)
    $iv_sub_tags = [];
    foreach($insta_viral as $ivp) {
        $tarr2 = array_map('trim', explode(',', strtolower($ivp['tag'])));
        foreach($tarr2 as $t) {
            if(!empty($t) && $t !== 'viral') $iv_sub_tags[] = $t;
        }
    }
    $iv_sub_tags = array_unique($iv_sub_tags);
    sort($iv_sub_tags);
    ?>
    <?php if(!empty($iv_sub_tags)): ?>
    <div class="tag-filter-container" style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin:0 0 28px;">
        <button class="iv-filter-btn active" data-tag="all" style="background:linear-gradient(135deg,#f09433,#dc2743);color:white;padding:8px 18px;border-radius:20px;font-weight:800;border:2px solid var(--text-color);cursor:pointer;font-family:var(--font-main);font-size:0.85rem;transition:all 0.2s;">All</button>
        <?php foreach($iv_sub_tags as $t): ?>
            <button class="iv-filter-btn" data-tag="<?= htmlspecialchars($t) ?>" style="background:var(--bg-color);padding:8px 18px;border-radius:20px;font-weight:800;border:2px solid var(--text-color);cursor:pointer;font-family:var(--font-main);font-size:0.85rem;transition:all 0.2s;text-transform:capitalize;"><?= htmlspecialchars(ucfirst($t)) ?></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="gallery-grid" id="iv-grid">
        <?php foreach($insta_viral as $p):
            $tags_arr = array_map('trim', explode(',', strtolower($p['tag'])));
        ?>
        <div class="card"
             data-id="<?= $p['id'] ?>"
             data-image="<?= htmlspecialchars($p['image_path']) ?>"
             data-title="<?= htmlspecialchars($p['title']) ?>"
             data-reel="<?= htmlspecialchars($p['reel_link'] ?? '') ?>"
             data-unlocked="false"
             data-prompt-type="insta_viral"
             data-tags="<?= htmlspecialchars(implode(',', $tags_arr)) ?>">

            <img src="<?= htmlspecialchars($p['image_path']) ?>" class="card-bg-image" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy">
            <div class="card-type-badge ivp">IVP</div>

            <div class="card-content">
                <h3 class="card-title"><?= htmlspecialchars($p['title']) ?></h3>
                <div class="card-footer">
                    <div class="likes"><i class="fa-solid fa-heart"></i> <?= number_format($p['likes_count']) ?></div>
                    <button class="lock-icon" title="Unlock Prompt"><i class="fa-solid fa-lock"></i></button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

    <!-- Math Challenge Modal -->
    <div id="unlock-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content split-view">
            <button class="close-modal">&times;</button>
            <div class="modal-left">
                <img src="" id="modal-image" alt="Prompt Preview">
            </div>
            <div class="modal-right">
                <h2 id="modal-title">UNLOCK THE VIRAL PROMPT</h2>

                <!-- Math Challenge Area -->
                <div id="math-challenge-area">
                    <div style="background:linear-gradient(135deg,#f09433,#dc2743);color:white;padding:14px 18px;border-radius:14px;border:var(--border-width) solid var(--text-color);margin-bottom:18px;box-shadow:var(--shadow-comic);">
                        <p style="font-weight:700;font-size:0.85rem;margin-bottom:6px;opacity:0.9;"><i class="fa-solid fa-calculator"></i> MATH CHALLENGE</p>
                        <p id="math-question" style="font-size:1.5rem;font-weight:900;letter-spacing:1px;"></p>
                    </div>
                    <p style="font-size:0.85rem;font-weight:700;color:#666;margin-bottom:10px;">Solve it to unlock the viral prompt <i class="fa-brands fa-instagram"></i></p>
                    <input type="number" id="math-answer-input" placeholder="Your Answer" style="width:100%;padding:12px 16px;border:var(--border-width) solid var(--text-color);border-radius:12px;font-size:1.1rem;font-weight:800;font-family:var(--font-main);background:var(--bg-color);color:var(--text-color);margin-bottom:10px;box-sizing:border-box;">
                    <p id="math-error-msg" style="color:#dc2743;font-weight:800;font-size:0.9rem;display:none;margin-bottom:8px;"><i class="fa-solid fa-xmark"></i> Wrong answer! Try again.</p>
                    <button id="math-submit-btn" style="width:100%;padding:14px;background:linear-gradient(135deg,#f09433,#dc2743);color:white;border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:900;font-size:1rem;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);">
                        <i class="fa-solid fa-unlock"></i> UNLOCK NOW
                    </button>
                </div>

                <!-- Revealed Prompt Area -->
                <div id="modal-unlocked-area" style="display:none;flex-direction:column;text-align:left;">
                    <div style="background:linear-gradient(135deg,#f09433,#dc2743);color:white;padding:8px 14px;border-radius:10px;font-weight:800;font-size:0.8rem;margin-bottom:12px;display:inline-block;"><i class="fa-brands fa-instagram"></i> VIRAL PROMPT UNLOCKED!</div>
                    <h3 style="margin-bottom:10px;color:var(--text-color);font-size:1rem;"><i class="fa-solid fa-scroll"></i> THE PROMPT:</h3>
                    <div class="unlocked-text" id="modal-unlocked-text" style="font-family:monospace;font-size:0.95rem;font-weight:500;background:var(--bg-color);padding:15px;border-radius:12px;border:var(--border-width) solid var(--text-color);flex-grow:1;margin-bottom:15px;overflow-y:auto;max-height:200px;white-space:pre-wrap;word-break:break-all;color:var(--text-color);box-shadow:var(--shadow-comic);"></div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button class="copy-btn" id="modal-copy-btn" style="flex:1;min-width:120px;padding:12px;background:var(--primary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);"><i class="fa-solid fa-copy"></i> COPY</button>
                        <button class="save-prompt-btn" id="modal-save-btn" data-prompt-id="" style="flex:1;min-width:120px;padding:12px;background:var(--secondary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);"><i class="fa-solid fa-bookmark"></i> SAVE</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Login-to-Save Popup -->
    <div id="login-save-popup" style="display:none;position:fixed;inset:0;background:rgba(45,42,53,.5);backdrop-filter:blur(8px);z-index:3000;align-items:center;justify-content:center;">
        <div style="background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:36px 32px;max-width:400px;width:90%;box-shadow:8px 8px 0 var(--text-color);text-align:center;">
            <div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-lock"></i></div>
            <h3 style="font-size:1.4rem;font-weight:900;margin-bottom:10px;">Login Required</h3>
            <p style="font-weight:600;color:#555;margin-bottom:24px;">Login is mandatory to save your prompt.</p>

<footer>
    <div>&copy; 2026 ARIGATO DEVAN. KEEP CREATING.</div>
    <div class="footer-links"><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
</footer>

<script>const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;</script>
<script src="script.js?v=177853384400519"></script>
<script>
// Scrolling background
const bgLayers = document.querySelectorAll('.bg-layer');
if (bgLayers.length > 0) {
    window.addEventListener('scroll', () => {
        const scrollPos = window.scrollY;
        let activeIndex = Math.floor(scrollPos / 500);
        if (activeIndex >= bgLayers.length) activeIndex = bgLayers.length - 1;
        bgLayers.forEach((layer, index) => {
            if (index === activeIndex) layer.classList.add('active');
            else layer.classList.remove('active');
        });
    });
}

// =====================
// MATH CHALLENGE LOGIC
// =====================
const modal = document.getElementById('unlock-modal');
const modalImage = document.getElementById('modal-image');
const modalTitle = document.getElementById('modal-title');
const mathArea = document.getElementById('math-challenge-area');
const unlockedArea = document.getElementById('modal-unlocked-area');
const mathQuestion = document.getElementById('math-question');
const mathInput = document.getElementById('math-answer-input');
const mathError = document.getElementById('math-error-msg');
const mathSubmitBtn = document.getElementById('math-submit-btn');
const modalUnlockedText = document.getElementById('modal-unlocked-text');
const modalCopyBtn = document.getElementById('modal-copy-btn');
const modalSaveBtn = document.getElementById('modal-save-btn');

let correctAnswer = 0;
let currentPromptText = '';
let currentPromptId = '';

function generateMathQuestion() {
    // Two random 4-digit numbers for a 7-digit sum challenge
    const a = Math.floor(Math.random() * 9000) + 1000;
    const b = Math.floor(Math.random() * 9000) + 1000;
    correctAnswer = a + b;
    mathQuestion.textContent = `${a.toLocaleString()} + ${b.toLocaleString()} = ?`;
}

// Open modal when card or lock-icon clicked
document.querySelectorAll('.card, .lock-icon').forEach(el => {
    el.addEventListener('click', function(e) {
        e.stopPropagation();
        const card = this.closest('.card') || this.parentElement.closest('.card');
        if (!card) return;

        const promptId   = card.dataset.id || '';
        const image      = card.dataset.image || '';
        const title      = card.dataset.title || 'VIRAL PROMPT';
        const promptText = card.dataset.promptText || '';

        currentPromptText = promptText;
        currentPromptId   = promptId;

        // Reset modal state
        modalImage.src = image;
        modalTitle.textContent = title.toUpperCase();
        mathArea.style.display = 'block';
        unlockedArea.style.display = 'none';
        mathInput.value = '';
        mathError.style.display = 'none';
        if(modalSaveBtn) modalSaveBtn.dataset.promptId = promptId;

        generateMathQuestion();

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });
});

// Close modal
document.querySelector('.close-modal').addEventListener('click', () => {
    modal.style.display = 'none';
    document.body.style.overflow = '';
});
modal.addEventListener('click', e => {
    if (e.target === modal) { modal.style.display = 'none'; document.body.style.overflow = ''; }
});

// Submit math answer
mathSubmitBtn.addEventListener('click', checkMathAnswer);
mathInput.addEventListener('keydown', e => { if(e.key === 'Enter') checkMathAnswer(); });

function checkMathAnswer() {
    const userAnswer = parseInt(mathInput.value.trim(), 10);
    if (isNaN(userAnswer)) { mathError.style.display = 'block'; mathError.innerHTML = '<i class="fa-solid fa-xmark"></i> Please enter a number!'; return; }

    if (userAnswer === correctAnswer) {
        mathError.style.display = 'none';
        // Fetch prompt text via AJAX if not cached on card
        if (currentPromptText) {
            revealPrompt(currentPromptText);
        } else {
            fetch('get_prompt.php?id=' + encodeURIComponent(currentPromptId))
                .then(r => r.json())
                .then(data => {
                    if (data.prompt_text) revealPrompt(data.prompt_text);
                    else { mathError.innerHTML = '<i class="fa-solid fa-xmark"></i> Could not load prompt.'; mathError.style.display = 'block'; }
                })
                .catch(() => { mathError.innerHTML = '<i class="fa-solid fa-xmark"></i> Network error.'; mathError.style.display = 'block'; });
        }
    } else {
        mathError.innerHTML = '<i class="fa-solid fa-xmark"></i> Wrong answer! Try again.';
        mathError.style.display = 'block';
        mathInput.value = '';
        mathInput.focus();
        // Shake effect
        mathInput.style.animation = 'none';
        setTimeout(() => { mathInput.style.animation = 'shake 0.4s'; }, 10);
        generateMathQuestion();
    }
}

function revealPrompt(text) {
    mathArea.style.display = 'none';
    unlockedArea.style.display = 'flex';
    modalUnlockedText.textContent = text;
    // Success emoji rain
    spawnEmojis();
}

function spawnEmojis() {
    const emojis = ['&mdash;','\u2721','\uD83D\uDCA5','\uD83C\uDF1F&mdash;','&mdash;','&mdash;','&mdash;','\uD83C\uDF1F&mdash;'];
    for(let i = 0; i < 20; i++) {
        const span = document.createElement('span');
        span.textContent = emojis[Math.floor(Math.random() * emojis.length)];
        span.style.cssText = `position:fixed;top:-40px;left:${Math.random()*100}vw;font-size:${1.5+Math.random()*1.5}rem;pointer-events:none;z-index:9999;animation:fall ${1.5+Math.random()*2}s ease forwards;`;
        document.body.appendChild(span);
        setTimeout(() => span.remove(), 4000);
    }
}

// Copy button
if(modalCopyBtn) {
    modalCopyBtn.addEventListener('click', () => {
        const text = modalUnlockedText.textContent;
        navigator.clipboard.writeText(text).then(() => {
            modalCopyBtn.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
            setTimeout(() => { modalCopyBtn.innerHTML = '<i class="fa-solid fa-copy"></i> COPY'; }, 2000);
        });
    });
}
</script>
<style>
@keyframes fall {
    0%   { transform: translateY(0) rotate(0deg); opacity:1; }
    100% { transform: translateY(110vh) rotate(360deg); opacity:0; }
}
@keyframes shake {
    0%,100% { transform: translateX(0); }
    20%,60% { transform: translateX(-8px); }
    40%,80% { transform: translateX(8px); }
}
</style>

<script>
// Insta Viral filter
document.querySelectorAll('.iv-filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.iv-filter-btn').forEach(b => {
            b.classList.remove('active');
            b.style.background = 'var(--bg-color)';
            b.style.color = 'var(--text-color)';
        });
        btn.classList.add('active');
        btn.style.background = 'linear-gradient(135deg,#f09433,#dc2743)';
        btn.style.color = 'white';
        const tag = btn.dataset.tag;
        document.querySelectorAll('#iv-grid .card').forEach(card => {
            const tags = (card.dataset.tags || '').split(',').map(t => t.trim());
            card.style.display = (tag === 'all' || tags.includes(tag)) ? '' : 'none';
        });
    });
});
</script></body></html>







