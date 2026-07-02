<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

$stmt = $pdo->prepare("
    SELECT p.id, p.slug, p.title, p.image_path, p.prompt_type, p.likes_count,
           p.tag, p.prompt_text, p.reel_link, p.created_at,
           p.best_works_in, p.asset_title, p.asset_images,
           1 AS is_unlocked, 1 AS is_saved,
           IF(l.id IS NOT NULL, 1, 0) AS is_liked
    FROM saved_prompts sp
    JOIN prompts p ON p.id = sp.prompt_id
    LEFT JOIN likes l ON l.prompt_id = p.id AND l.user_id = :uid
    WHERE sp.user_id = :uid2
    ORDER BY sp.created_at DESC
");
$stmt->execute([":uid" => $user_id, ":uid2" => $user_id]);
$saved = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($saved);

$stats = [
    "secret"           => 0,
    "unreleased"       => 0,
    "insta_viral"      => 0,
    "already_uploaded" => 0,
    "direct"           => 0,
];
foreach ($saved as $p) {
    $pt = $p["prompt_type"] ?? "secret";
    if (isset($stats[$pt])) {
        $stats[$pt]++;
    }
}

$type_chips = [
    "secret"           => ["icon" => "fa-lock",   "label" => "Secret"],
    "insta_viral"      => ["icon" => "fa-fire",   "label" => "Viral"],
    "unreleased"       => ["icon" => "fa-moon",   "label" => "Unreleased"],
    "already_uploaded" => ["icon" => "fa-upload", "label" => "Uploaded"],
    "direct"           => ["icon" => "fa-bolt",   "label" => "Direct"],
];

require_once "includes/prompt_cards.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#2F4156">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Prompts &mdash; Arigato Devan Prompts</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <?php include_once "includes/theme_head.php"; ?>
    <link rel="stylesheet" href="css/gallery-extras.css?v=20260706">
    <link rel="stylesheet" href="css/saved-prompts-page.css?v=20260729">
    <?php include_once "includes/card_skeleton_assets.php"; ?>
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-store page-saved theme-nogoda">

<?php $nav_active = "profile"; include "includes/site_nav.php"; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

<main class="page-main">

    <div class="sp-page-head">
        <div>
            <p class="hero-label"><i class="fa-solid fa-bookmark" style="margin-right:6px;"></i> Your Collection</p>
            <h1>Saved <em>Prompts</em></h1>
            <p class="sp-page-sub">All prompts you've saved &mdash; <span id="sp-counter"><?= $total ?></span> so far</p>
        </div>
        <div class="sp-page-stat">
            <span class="page-hero-num" id="sp-stat-num"><?= $total ?></span>
            <span class="page-hero-label">saved</span>
        </div>
    </div>

    <?php if ($total > 0): ?>
    <div class="sp-type-row">
        <?php foreach ($type_chips as $key => $chip):
            if (($stats[$key] ?? 0) < 1) continue;
        ?>
        <span class="sp-type-chip">
            <i class="fa-solid <?= $chip["icon"] ?>"></i>
            <?= $chip["label"] ?> <?= $stats[$key] ?>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($total === 0): ?>
    <div class="sp-empty" id="sp-empty-state">
        <div class="sp-empty-icon"><i class="fa-solid fa-bookmark"></i></div>
        <h2>No Saved Prompts Yet</h2>
        <p>Unlock prompts on the site and save them &mdash; they'll appear here!</p>
        <a href="gallery.php" class="home-btn-primary"><i class="fa-solid fa-images"></i> Browse Gallery</a>
    </div>
    <?php else: ?>
    <?php render_prompt_grid($saved, ["grid_id" => "sp-card-stack"]); ?>
    <?php endif; ?>

</main>

<!-- Prompt Modal -->
<div id="unlock-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content split-view">
        <button class="close-modal">&times;</button>
        <div class="modal-left">
            <img loading="lazy" src="" id="modal-image" alt="Prompt Preview">
        </div>
        <div class="modal-right">
            <h2 id="modal-title">Prompt</h2>
            <div class="want-code-section" id="modal-want-code" style="display:none;">
                <p class="want-code-text">Need Secret Code?</p>
                <a href="all_codes.php" id="modal-reel-link" class="comic-btn-small">
                    <i class="fa-solid fa-code"></i> All Codes Here
                </a>
            </div>
            <div class="modal-unlock-area" id="modal-unlock-area" style="display:none;"></div>
            <div class="modal-unlocked-area" id="modal-unlocked-area" style="display:none;flex-direction:column;text-align:left;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap;">
                    <h3 style="color:var(--text-primary);font-size:0.9rem;margin:0;font-family:'Playfair Display',serif;font-weight:700;">
                        <i class="fa-solid fa-scroll"></i> The Prompt:
                    </h3>
                    <div id="modal-bwi-badge"></div>
                </div>
                <div class="unlocked-text" id="modal-unlocked-text"></div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="copy-btn" id="modal-copy-btn" style="flex:1;min-width:120px;"><i class="fa-solid fa-copy"></i> Copy</button>
                    <button id="modal-sp-remove-btn" data-prompt-id="" style="flex:1;min-width:120px;padding:12px 16px;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:12px;font-family:'Inter',sans-serif;font-weight:600;font-size:0.88rem;cursor:pointer;">
                        <i class="fa-solid fa-trash-can"></i> Remove
                    </button>
                </div>
                <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="">
                    <i class="fa-solid fa-heart"></i> <span id="modal-like-count">0</span>
                </button>
            </div>
            <div id="modal-assets-area" style="display:none;margin-top:16px;border-top:1px solid var(--border);padding-top:14px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;font-weight:700;font-size:.88rem;color:var(--text-primary);font-family:'Inter',sans-serif;">
                    <i class="fa-solid fa-paperclip"></i> <span id="modal-asset-title">Assets</span>
                </div>
                <div id="modal-asset-images" style="display:flex;gap:10px;flex-wrap:wrap;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Remove confirm -->
<div id="sp-confirm-remove" class="sp-confirm-overlay">
    <div class="sp-confirm-card">
        <div class="sp-confirm-icon"><i class="fa-solid fa-trash-can"></i></div>
        <h3>Remove this prompt?</h3>
        <p>It will be removed from your saved list. You can save it again anytime.</p>
        <div class="sp-confirm-actions">
            <button type="button" class="sp-confirm-cancel" id="sp-cancel-btn">Cancel</button>
            <button type="button" class="sp-confirm-yes" id="sp-confirm-btn">Yes, Remove</button>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>const isLoggedIn = true;</script>
<script defer src="script.js?v=20260707"></script>
<script>
(function () {
    const popup     = document.getElementById("sp-confirm-remove");
    const cancelBtn = document.getElementById("sp-cancel-btn");
    const confirmBtn = document.getElementById("sp-confirm-btn");
    const grid      = document.getElementById("sp-card-stack");
    const counter   = document.getElementById("sp-counter");
    const statNum   = document.getElementById("sp-stat-num");
    const main      = document.querySelector(".page-main");

    let pendingPromptId = null;
    let pendingCard = null;

    function cardSelector(id) {
        return '.prompt-card[data-id="' + id + '"]';
    }

    function openPopup() {
        if (popup) popup.classList.add("open");
    }

    function closePopup() {
        if (popup) popup.classList.remove("open");
        pendingPromptId = null;
        pendingCard = null;
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = "Yes, Remove";
        }
    }

    function updateCounter() {
        const remaining = grid ? grid.querySelectorAll(".prompt-card").length : 0;
        if (counter) counter.textContent = remaining;
        if (statNum) statNum.textContent = remaining;

        if (remaining === 0 && main) {
            if (grid) grid.remove();
            const typeRow = document.querySelector(".sp-type-row");
            if (typeRow) typeRow.remove();
            if (!document.getElementById("sp-empty-state")) {
                const empty = document.createElement("div");
                empty.className = "sp-empty";
                empty.id = "sp-empty-state";
                empty.innerHTML =
                    '<div class="sp-empty-icon"><i class="fa-solid fa-bookmark"></i></div>' +
                    '<h2>No Saved Prompts Yet</h2>' +
                    '<p>Unlock prompts on the site and save them &mdash; they\'ll appear here!</p>' +
                    '<a href="gallery.php" class="home-btn-primary"><i class="fa-solid fa-images"></i> Browse Gallery</a>';
                main.appendChild(empty);
            }
        }
    }

    function bindCard(card) {
        let pressTimer;

        card.addEventListener("touchstart", function (e) {
            if (e.target.closest("button") || e.target.closest("a")) return;
            pressTimer = setTimeout(function () {
                pendingPromptId = card.dataset.id;
                pendingCard = card;
                if (navigator.vibrate) navigator.vibrate(50);
                openPopup();
            }, 600);
        }, { passive: true });

        card.addEventListener("touchend", function () { clearTimeout(pressTimer); });
        card.addEventListener("touchmove", function () { clearTimeout(pressTimer); });

        card.addEventListener("contextmenu", function (e) {
            if (window.innerWidth <= 900) e.preventDefault();
        });

        card.addEventListener("click", function () {
            const modalRemoveBtn = document.getElementById("modal-sp-remove-btn");
            if (modalRemoveBtn) modalRemoveBtn.dataset.promptId = this.dataset.id;
        });
    }

    document.querySelectorAll(".prompt-card").forEach(bindCard);

    const modalRemoveBtn = document.getElementById("modal-sp-remove-btn");
    if (modalRemoveBtn) {
        modalRemoveBtn.addEventListener("click", function () {
            const promptId = this.dataset.promptId;
            if (!promptId) return;
            pendingPromptId = promptId;
            pendingCard = document.querySelector(cardSelector(promptId));
            const modal = document.getElementById("unlock-modal");
            if (modal) modal.style.display = "none";
            openPopup();
        });
    }

    if (cancelBtn) cancelBtn.addEventListener("click", closePopup);
    if (popup) {
        popup.addEventListener("click", function (e) {
            if (e.target === popup) closePopup();
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener("click", function () {
            if (!pendingPromptId || !pendingCard) { closePopup(); return; }
            confirmBtn.disabled = true;
            confirmBtn.textContent = "Removing...";

            const fd = new FormData();
            fd.append("action", "unsave");
            fd.append("prompt_id", pendingPromptId);

            fetch("save_prompt.php", { method: "POST", body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.saved === false) {
                        const card = pendingCard;
                        card.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                        card.style.opacity = "0";
                        card.style.transform = "scale(0.92)";
                        setTimeout(function () {
                            card.remove();
                            updateCounter();
                        }, 300);
                        closePopup();
                    } else {
                        alert(data.message || "Could not remove. Try again.");
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = "Yes, Remove";
                    }
                })
                .catch(function () {
                    alert("Network error. Try again.");
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = "Yes, Remove";
                });
        });
    }
})();
</script>
</body>
</html>
