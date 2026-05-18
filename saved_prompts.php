<?php
session_start();
require_once "db.php";

// Must be logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Onboarding check
if (empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

// Fetch all prompts this user has explicitly saved
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.image_path, p.prompt_type, p.likes_count,
           p.tag, p.prompt_text,
           IF(l.id IS NOT NULL, 1, 0) as is_liked
    FROM saved_prompts sp
    JOIN prompts p ON p.id = sp.prompt_id
    LEFT JOIN likes l ON l.prompt_id = p.id AND l.user_id = :uid
    WHERE sp.user_id = :uid2
    ORDER BY sp.created_at DESC
");
$stmt->execute([":uid" => $user_id, ":uid2" => $user_id]);
$saved = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($saved);

$type_map = [
    "secret" => [
        "emoji" => "🔒",
        "label" => "Secret Code",
        "bg" => "#ffe3e3",
        "color" => "#d03030",
    ],
    "unreleased" => [
        "emoji" => "🌙",
        "label" => "Unreleased",
        "bg" => "#fff4cc",
        "color" => "#7a5800",
    ],
    "insta_viral" => [
        "emoji" => "🔥",
        "label" => "Insta Viral",
        "bg" => "#e3f7ff",
        "color" => "#004f7a",
    ],
    "already_uploaded" => [
        "emoji" => "📤",
        "label" => "Already Uploaded",
        "bg" => "#e6f2ff",
        "color" => "#00509e",
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Prompts &mdash; Arigato Devan Prompts</title>
    <link rel="stylesheet" href="style.css?v=1778100000">
    <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
        <link rel="preconnect" href="https://unpkg.com" crossorigin>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-color); }
        .sp-wrap { max-width: 1100px; margin: 0 auto; padding: 32px 24px 100px; }
        .sp-hero { margin-bottom: 28px; }
        .sp-title { font-size: 2rem; font-weight: 900; display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
        .sp-sub { color: #7D7887; font-weight: 600; font-size: .9rem; }
        .sp-empty { text-align: center; padding: 80px 20px; }
        .sp-empty-icon { font-size: 4rem; margin-bottom: 16px; }
        .sp-empty h2 { font-size: 1.6rem; font-weight: 900; margin-bottom: 8px; }
        .sp-empty p { color: #888; font-weight: 600; margin-bottom: 24px; }
        .sp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        @media (max-width: 600px) { .sp-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } .sp-wrap { padding: 20px 14px 80px; } }
    </style>
    <?php include_once "gtag.php"; ?>
</head>
<body>

<!-- Background -->
<div class="scroll-bg-container" aria-hidden="true">
    <?php for ($i = 1; $i <= 4; $i++): ?>
    <div class="bg-layer" style="background-image:url('landingpics/lan<?= $i ?>.webp');"></div>
    <?php endfor; ?>
</div>
<div class="bg-creamy-overlay" aria-hidden="true"></div>

<header>
    <div class="logo-area" id="logo-container" style="cursor:pointer;">
        <div class="logo-flipper">
            <div class="logo-front">
                <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo" id="profile-logo">
            </div>
            <div class="logo-back">
                <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt="">
            </div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="index.php">HOME</a>
        <a href="gallery.php">GALLERY</a>
    </nav>
    <div class="header-right">
        <div class="header-divider"></div>
        <a href="profile.php" style="color:var(--text-color)">
            <?= renderAvatar(
                $_SESSION["profile_image"] ?? "",
                "admin-avatar",
                "Profile",
            ) ?>
        </a>
        <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
    </div>
</header>

<div class="sp-wrap">
    <div class="sp-hero">
        <div class="sp-title">
            <i class="fa-solid fa-bookmark" style="color:var(--primary-dark);"></i>
            Saved Prompts
        </div>
        <p class="sp-sub">
            All prompts you've unlocked — <?= $total ?> saved so far
        </p>
    </div>

    <?php if ($total === 0): ?>
    <div class="sp-empty">
        <div class="sp-empty-icon">🔖</div>
        <h2>No Saved Prompts Yet</h2>
        <p>Unlock prompts on the site and they'll appear here!</p>
        <a href="index.php" class="comic-btn-small"><i class="fa-solid fa-arrow-left"></i> Browse Prompts</a>
    </div>
    <?php else: ?>
    <div class="sp-grid gallery-grid">
        <?php foreach ($saved as $p):

            $pt = $p["prompt_type"] ?? "secret";
            $tinfo = $type_map[$pt] ?? $type_map["secret"];
            $tags = array_map("trim", explode(",", strtolower($p["tag"])));
            ?>
        <div class="card sp-card"
             data-id="<?= $p["id"] ?>"
             data-image="<?= htmlspecialchars($p["image_path"]) ?>"
             data-title="<?= htmlspecialchars($p["title"]) ?>"
             data-prompt-type="<?= htmlspecialchars($pt) ?>"
             data-unlocked="true"
             data-saved="true"
             data-prompt-text="<?= htmlspecialchars($p["prompt_text"]) ?>"
             data-tags="<?= htmlspecialchars(implode(",", $tags)) ?>"
             data-reel="">

            <img src="<?= htmlspecialchars(
                $p["image_path"],
            ) ?>" class="card-bg-image" alt="<?= htmlspecialchars(
    $p["title"],
) ?>" loading="lazy">

            <span class="card-type-badge <?= [
                "secret" => "scp",
                "unreleased" => "urp",
                "insta_viral" => "ivp",
                "already_uploaded" => "aup",
            ][$pt] ?? "scp" ?>" style="font-size:.55rem;padding:2px 6px;">
                <?= $tinfo["emoji"] ?> <?= $tinfo["label"] ?>
            </span>

            <div class="card-lock-icon" style="background:var(--primary-color);">
                <i class="fa-solid fa-check" style="font-size:14px;"></i>
            </div>

            <div class="card-click-trigger"></div>
            <div class="card-content-overlay">
                <div class="card-title"><?= htmlspecialchars(
                    $p["title"],
                ) ?></div>
                <div class="card-like-display"
                     data-liked="<?= $p["is_liked"] ? "true" : "false" ?>"
                     data-prompt-id="<?= $p["id"] ?>">
                    <i class="fa-solid fa-heart <?= $p["is_liked"]
                        ? "liked-heart"
                        : "" ?>"></i>
                    <span class="like-count"><?= (int) $p[
                        "likes_count"
                    ] ?></span>
                </div>
            </div>
        </div>
        <?php
        endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal (same as other pages) -->
<div id="unlock-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content split-view">
        <button class="close-modal"><i class="fa-solid fa-xmark"></i></button>
        <div class="modal-left">
            <img id="modal-image" src="" alt="Prompt">
        </div>
        <div class="modal-right">
            <h2 id="modal-title">PROMPT</h2>
            <div id="modal-want-code" style="display:none;"><p>Get the code from our Instagram reel!</p><a id="modal-reel-link" href="#" target="_blank"><i class="fa-brands fa-instagram"></i> View Reel</a></div>
            <div id="modal-unlock-area" style="display:none;"></div>
            <div id="modal-unlocked-area" style="display:none;flex-direction:column;text-align:left;height:100%;">
                <h3 style="margin-bottom:10px;color:var(--text-color);font-size:1rem;"><i class="fa-solid fa-scroll"></i> THE PROMPT:</h3>
                <div class="unlocked-text" id="modal-unlocked-text" style="font-family:monospace;font-size:0.95rem;font-weight:500;background:var(--bg-color);padding:15px;border-radius:12px;border:var(--border-width) solid var(--text-color);flex-grow:1;margin-bottom:15px;overflow-y:auto;max-height:200px;white-space:pre-wrap;word-break:break-word;color:var(--text-color);box-shadow:var(--shadow-comic);"></div>
                <div style="display:flex;gap:10px;flex-wrap:nowrap;width:100%;">
                    <button class="copy-btn" id="modal-copy-btn" style="flex:1;padding:12px;background:var(--primary-color);color:var(--text-color);border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);white-space:nowrap;"><i class="fa-solid fa-copy"></i> COPY</button>
                    <button id="modal-sp-remove-btn" data-prompt-id="" style="flex:1;padding:12px;background:#ffd6d6;color:#a01515;border:var(--border-width) solid var(--text-color);border-radius:12px;font-weight:800;cursor:pointer;text-transform:uppercase;box-shadow:var(--shadow-comic);transition:all 0.2s;font-family:var(--font-main);white-space:nowrap;"><i class="fa-solid fa-trash-can"></i> REMOVE</button>
                    <?php if (isset($_SESSION["user_id"])): ?>
                    <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="" style="flex-shrink:0;min-width:70px;padding:12px 0;background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:12px;cursor:pointer;box-shadow:var(--shadow-comic);transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="fa-solid fa-heart" style="font-size:1.1rem;color:#FF4444;"></i>
                        <span id="modal-like-count" style="font-weight:900;color:#FF4444;font-size:0.95rem;">0</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>
    <div>&copy; 2026 ARIGATO DEVAN. KEEP CREATING.</div>
    <div class="footer-links"><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
</footer>

<!-- Remove-Saved Confirm Popup -->
<div id="sp-confirm-remove" style="display:none;position:fixed;inset:0;background:rgba(45,42,53,.5);backdrop-filter:blur(8px);z-index:3500;align-items:center;justify-content:center;">
    <div style="background:var(--card-bg);border:var(--border-width) solid var(--text-color);border-radius:24px;padding:32px 28px;max-width:400px;width:90%;box-shadow:8px 8px 0 var(--text-color);text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:12px;color:#dc2743;"><i class="fa-solid fa-trash-can"></i></div>
        <h3 style="font-size:1.3rem;font-weight:900;margin-bottom:10px;">Remove this prompt?</h3>
        <p style="font-weight:600;color:#555;margin-bottom:24px;">This will remove it from your saved list. You can save it again later.</p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <button id="sp-cancel-btn" style="flex:1;padding:14px;background:var(--bg-color);border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);">Cancel</button>
            <button id="sp-confirm-btn" style="flex:1;padding:14px;background:#ffd6d6;border:var(--border-width) solid var(--text-color);border-radius:14px;font-family:var(--font-main);font-weight:800;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-comic);color:#a01515;">Yes, Remove</button>
        </div>
    </div>
</div>

<script>const isLoggedIn = <?= isset($_SESSION["user_id"])
    ? "true"
    : "false" ?>;</script>
<script defer src="script.js?v=2026051206"></script>
<script>
(function () {
    const popup = document.getElementById('sp-confirm-remove');
    const cancelBtn = document.getElementById('sp-cancel-btn');
    const confirmBtn = document.getElementById('sp-confirm-btn');
    const grid = document.querySelector('.sp-grid');
    const wrap = document.querySelector('.sp-wrap');
    const subEl = document.querySelector('.sp-sub');

    let pendingPromptId = null;
    let pendingCard = null;

    function closePopup() {
        popup.style.display = 'none';
        pendingPromptId = null;
        pendingCard = null;
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Yes, Remove';
    }

    function updateCounter() {
        if (!subEl) return;
        const remaining = grid ? grid.querySelectorAll('.sp-card').length : 0;
        subEl.textContent = "All prompts you've saved \u2014 " + remaining + " saved so far";
        if (remaining === 0 && wrap) {
            // Render empty state inline
            const emptyHtml = '<div class="sp-empty">'
                + '<div class="sp-empty-icon">\ud83d\udd16</div>'
                + '<h2>No Saved Prompts Yet</h2>'
                + '<p>Unlock prompts on the site and save them \u2014 they\'ll appear here!</p>'
                + '<a href="index.php" class="comic-btn-small"><i class="fa-solid fa-arrow-left"></i> Browse Prompts</a>'
                + '</div>';
            if (grid) grid.remove();
            const container = document.createElement('div');
            container.innerHTML = emptyHtml;
            wrap.appendChild(container.firstElementChild);
        }
    }

    // Long-press logic for Mobile (Unsave)
    let pressTimer;
    document.querySelectorAll('.sp-card').forEach(function(card) {
        card.addEventListener('touchstart', function(e) {
            // Don't trigger if they are clicking a button like the like button inside the card (if any)
            if (e.target.closest('button') || e.target.closest('a')) return;
            
            pressTimer = setTimeout(function() {
                pendingPromptId = card.dataset.id;
                pendingCard = card;
                if (navigator.vibrate) navigator.vibrate(50);
                popup.style.display = 'flex';
            }, 600); // 600ms hold
        }, {passive: true});

        card.addEventListener('touchend', function(e) {
            clearTimeout(pressTimer);
        });
        card.addEventListener('touchmove', function(e) {
            clearTimeout(pressTimer);
        });
        
        // Disable context menu on long press on mobile to prevent default image save popup
        card.addEventListener('contextmenu', function(e) {
            if (window.innerWidth <= 900) {
                e.preventDefault();
            }
        });
    });

    // Sync modal remove button promptId when any sp-card is clicked
    document.querySelectorAll('.sp-card').forEach(function(card) {
        card.addEventListener('click', function() {
            const modalRemoveBtn = document.getElementById('modal-sp-remove-btn');
            if (modalRemoveBtn) modalRemoveBtn.dataset.promptId = this.dataset.id;
        });
    });

    // Modal Remove button
    const modalRemoveBtn = document.getElementById('modal-sp-remove-btn');
    if (modalRemoveBtn) {
        modalRemoveBtn.addEventListener('click', function() {
            const promptId = this.dataset.promptId;
            if (!promptId) return;
            pendingPromptId = promptId;
            pendingCard = document.querySelector('.sp-card[data-id="' + promptId + '"]');
            // Close the modal first
            const modal = document.getElementById('unlock-modal');
            if (modal) modal.style.display = 'none';
            popup.style.display = 'flex';
        });
    }

    if (cancelBtn) cancelBtn.addEventListener('click', closePopup);
    popup.addEventListener('click', function (e) {
        if (e.target === popup) closePopup();
    });

    if (confirmBtn) confirmBtn.addEventListener('click', function () {
        if (!pendingPromptId || !pendingCard) { closePopup(); return; }
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Removing...';

        const fd = new FormData();
        fd.append('action', 'unsave');
        fd.append('prompt_id', pendingPromptId);

        fetch('save_prompt.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.saved === false) {
                    const card = pendingCard;
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(function () {
                        card.remove();
                        updateCounter();
                    }, 300);
                    closePopup();
                } else {
                    if (typeof showComicAlert === 'function') {
                        showComicAlert(data.message || 'Could not remove. Try again.', 'error');
                    } else {
                        alert(data.message || 'Could not remove. Try again.');
                    }
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Yes, Remove';
                }
            })
            .catch(function () {
                if (typeof showComicAlert === 'function') {
                    showComicAlert('Network error. Try again.', 'error');
                } else {
                    alert('Network error. Try again.');
                }
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Yes, Remove';
            });
    });
})();
</script>
</body>
</html>
