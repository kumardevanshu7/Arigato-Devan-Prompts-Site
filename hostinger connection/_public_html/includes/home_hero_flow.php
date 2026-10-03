<?php
/**
 * Home Hero Card Flow Component (Clean Minimalist Framer Aesthetic)
 * Customized per User Specifications:
 * - Page Background: Pure White (#FFFFFF)
 * - Font style: Clean geometric (Urbanist / Outfit)
 * - "NanoBanana": Multi-phase typewriter ("Use Nano 🍌 2" -> "& also pro Model" -> "NanoBanana")
 * - "ChatGPT": Multi-phase typewriter ("Use Image 2.0" -> "ChatGPT")
 * - Buttons: "Explore Prompts" and "Direct Prompts"
 * - 5 Circular Dock Icons: Circles static, icons float gently inside
 * - Like & Follow popup modals with beautiful simple English & emojis
 * - Clean rounded cards with ZERO double layer & "View Prompt ↗" centered at bottom
 * - Bottom pills: About Us, Contact Us, Privacy Policy, Terms & Conditions (NO site link)
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/../db.php';
}

// Fetch Col 1 (Upward Flow)
$col1_cards = [];
try {
    $c1_stmt = $pdo->query("
        SELECT f.id as flow_id, f.sort_order, f.column_pos,
               p.id, p.title, p.prompt_type, p.image_path, p.slug, p.likes_count, 'prompt' as source_type
        FROM home_card_flow f
        JOIN prompts p ON f.prompt_id = p.id
        WHERE f.column_pos = 'col1' AND f.is_active = 1 AND f.source_type = 'prompt'
        ORDER BY f.sort_order ASC, f.id DESC
    ");
    $col1_cards = $c1_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Fetch Col 2 (Downward Flow)
$col2_cards = [];
try {
    $c2_stmt = $pdo->query("
        SELECT f.id as flow_id, f.sort_order, f.column_pos,
               p.id, p.title, p.prompt_type, p.image_path, p.slug, p.likes_count, 'prompt' as source_type
        FROM home_card_flow f
        JOIN prompts p ON f.prompt_id = p.id
        WHERE f.column_pos = 'col2' AND f.is_active = 1 AND f.source_type = 'prompt'
        ORDER BY f.sort_order ASC, f.id DESC
    ");
    $col2_cards = $c2_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Fallback: If admin hasn't configured enough cards, pull top prompts automatically
if (empty($col1_cards) || count($col1_cards) < 3) {
    try {
        $fb1 = $pdo->query("
            SELECT 0 as flow_id, 0 as sort_order, 'col1' as column_pos,
                   p.id, p.title, p.prompt_type, p.image_path, p.slug, p.likes_count, 'prompt' as source_type
            FROM prompts p
            WHERE (p.is_trial = 0 OR p.is_trial IS NULL)
            ORDER BY p.likes_count DESC, p.id DESC
            LIMIT 4
        ")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($fb1)) $col1_cards = $fb1;
    } catch (Exception $e) {}
}

if (empty($col2_cards) || count($col2_cards) < 3) {
    try {
        $fb2 = $pdo->query("
            SELECT 0 as flow_id, 0 as sort_order, 'col2' as column_pos,
                   p.id, p.title, p.prompt_type, p.image_path, p.slug, p.likes_count, 'prompt' as source_type
            FROM prompts p
            WHERE (p.is_trial = 0 OR p.is_trial IS NULL)
            ORDER BY p.id DESC
            LIMIT 4 OFFSET 4
        ")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($fb2)) $col2_cards = $fb2;
    } catch (Exception $e) {}
}
?>

<section class="viva-hero" id="homeHeroFlow">
    <div class="viva-hero-container">
        <!-- LEFT CONTENT AREA -->
        <div class="viva-left">
            <div class="viva-text-stack">
                <h1 class="viva-title">
                    Couple<br>
                    Romantic Prompts<br>
                    <div class="viva-title-sub-row">
                        <div class="capsules-line-1">
                            <span>with</span>
                            <span class="badge-nano-banana" id="capsuleNanoBanana" role="button" tabindex="0" title="Tap to reveal models">
                                <span class="capsule-text">NanoBanana</span>
                            </span>
                        </div>
                        <div class="capsules-line-2">
                            <span class="viva-title-amp">&amp;</span>
                            <span class="badge-chatgpt" id="capsuleChatGPT" role="button" tabindex="0" title="Tap to reveal models">
                                <span class="capsule-text">ChatGPT</span>
                            </span>
                        </div>
                    </div>
                </h1>

                <p class="viva-description">
                    Instant, real-time AI prompts crafted to preserve facial consistency and romantic vibes for your Instagram Reels.
                </p>

                <!-- Action Buttons ("Direct Prompts" per user instruction) -->
                <div class="viva-actions">
                    <a href="gallery.php" class="viva-btn viva-btn-dark">
                        <span>Explore Prompts</span>
                        <span class="viva-arrow-bubble"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                    </a>
                    <a href="direct_prompts.php" class="viva-btn viva-btn-light">
                        <span>Direct Prompts</span>
                        <span class="viva-arrow-bubble"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                    </a>
                </div>
            </div>

            <!-- Floating Vertical Icon Strip (5 Circles Fixed in Place, Icons Float Inside) -->
            <div class="viva-icon-strip" aria-label="Quick Actions">
                <!-- 1. Gallery Icon -->
                <a href="gallery.php" class="viva-dock-icon dock-gallery" title="Explore Gallery">
                    <i class="fa-solid fa-images"></i>
                </a>

                <!-- 2. Contact Me Icon -->
                <a href="contact.php" class="viva-dock-icon dock-contact" title="Contact Us">
                    <i class="fa-solid fa-envelope"></i>
                </a>

                <!-- 3. Instagram Icon -->
                <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener" class="viva-dock-icon dock-insta" title="Follow on Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>

                <!-- 4. Like Heart Icon (Opens popup) -->
                <button type="button" class="viva-dock-icon dock-like" id="vivaLikeBtn" title="Show Some Love" aria-label="Like Prompts">
                    <i class="fa-solid fa-heart"></i>
                </button>

                <!-- 5. Follow Icon (Opens popup) -->
                <button type="button" class="viva-dock-icon dock-follow" id="vivaFollowBtn" title="Follow Community" aria-label="Follow Us">
                    <i class="fa-solid fa-user-plus"></i>
                </button>
            </div>

            <!-- Bottom Row: Important Page Links (Light Pink, Green, Yellow, Orange Pastels) -->
            <div class="viva-bottom-bar">
                <div class="viva-page-links">
                    <a href="about.php" class="viva-page-pill pill-pink">About Us</a>
                    <a href="contact.php" class="viva-page-pill pill-green">Contact Us</a>
                    <a href="privacy.php" class="viva-page-pill pill-yellow">Privacy Policy</a>
                    <a href="terms.php" class="viva-page-pill pill-orange">Terms &amp; Conditions</a>
                </div>
            </div>
        </div>

        <!-- RIGHT FLOWING CARDS AREA -->
        <div class="viva-right" aria-label="Live prompt preview showcase">
            <div class="viva-flow-stage">
                <!-- COLUMN 1: Flows UPWARD -->
                <div class="viva-flow-col viva-col-up">
                    <div class="viva-track viva-track-up">
                        <?php 
                        $col1_loop = array_merge($col1_cards, $col1_cards);
                        foreach ($col1_loop as $idx => $card):
                            $pUrl = !empty($card['slug']) ? 'prompt.php?slug=' . urlencode($card['slug']) : 'prompt.php?id=' . (int)$card['id'];
                        ?>
                        <!-- Single clean rounded card (ZERO double layer!) -->
                        <a href="<?= $pUrl ?>" class="viva-card" title="<?= htmlspecialchars($card['title']) ?>">
                            <img src="<?= htmlspecialchars($card['image_path']) ?>" alt="<?= htmlspecialchars($card['title']) ?>" class="viva-card-img" loading="lazy">
                            <div class="viva-card-overlay" aria-hidden="true"></div>

                            <!-- Clean bottom center View Prompt button -->
                            <div class="viva-card-bottom-center">
                                <span class="viva-card-view-btn">
                                    <span>View Prompt</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- COLUMN 2: Flows DOWNWARD -->
                <div class="viva-flow-col viva-col-down">
                    <div class="viva-track viva-track-down">
                        <?php 
                        $col2_loop = array_merge($col2_cards, $col2_cards);
                        foreach ($col2_loop as $idx => $card):
                            $pUrl = !empty($card['slug']) ? 'prompt.php?slug=' . urlencode($card['slug']) : 'prompt.php?id=' . (int)$card['id'];
                        ?>
                        <!-- Single clean rounded card (ZERO double layer!) -->
                        <a href="<?= $pUrl ?>" class="viva-card" title="<?= htmlspecialchars($card['title']) ?>">
                            <img src="<?= htmlspecialchars($card['image_path']) ?>" alt="<?= htmlspecialchars($card['title']) ?>" class="viva-card-img" loading="lazy">
                            <div class="viva-card-overlay" aria-hidden="true"></div>

                            <!-- Clean bottom center View Prompt button -->
                            <div class="viva-card-bottom-center">
                                <span class="viva-card-view-btn">
                                    <span>View Prompt</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Like Prompt Popup Modal -->
<div id="viva-like-modal" class="viva-modal-overlay" style="display:none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="vivaLikeTitle">
    <div class="viva-modal-box">
        <button type="button" class="viva-modal-close" onclick="closeVivaModal('viva-like-modal')" aria-label="Close modal">&times;</button>
        <div class="viva-modal-icon-badge modal-heart-badge">
            <i class="fa-solid fa-heart"></i>
        </div>
        <h3 class="viva-modal-title" id="vivaLikeTitle">Show Some Love! ❤️✨</h3>
        <p class="viva-modal-desc">
            Love these AI couple prompts? Don't forget to like your favorite prompts on our website and drop love on our Instagram reels! Every like keeps the creative inspiration alive. 💖🥂
        </p>
        <div class="viva-modal-actions">
            <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener" class="viva-modal-btn viva-modal-btn-insta">
                <i class="fa-brands fa-instagram"></i>
                <span>Like on Instagram</span>
            </a>
            <a href="gallery.php" class="viva-modal-btn viva-modal-btn-primary">
                <i class="fa-solid fa-fire"></i>
                <span>Explore &amp; Like Prompts</span>
            </a>
        </div>
    </div>
</div>

<!-- Follow Community Popup Modal -->
<div id="viva-follow-modal" class="viva-modal-overlay" style="display:none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="vivaFollowTitle">
    <div class="viva-modal-box">
        <button type="button" class="viva-modal-close" onclick="closeVivaModal('viva-follow-modal')" aria-label="Close modal">&times;</button>
        <div class="viva-modal-icon-badge modal-follow-badge">
            <i class="fa-solid fa-user-plus"></i>
        </div>
        <h3 class="viva-modal-title" id="vivaFollowTitle">Join the Arigato Community! 🌟🚀</h3>
        <p class="viva-modal-desc">
            Sign in to your account on our site to unlock and bookmark your favorite prompts in one tap, and make sure to follow <strong>@arigato.devan</strong> on Instagram for fresh, viral prompt drops every week! 💫🎉
        </p>
        <div class="viva-modal-actions">
            <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener" class="viva-modal-btn viva-modal-btn-insta">
                <i class="fa-brands fa-instagram"></i>
                <span>Follow @arigato.devan</span>
            </a>
            <a href="login.php" class="viva-modal-btn viva-modal-btn-primary">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span>Log In / Sign Up</span>
            </a>
        </div>
    </div>
</div>

<script>
function openVivaModal(id) {
    var m = document.getElementById(id);
    if (m) {
        m.style.display = 'flex';
        m.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
}
function closeVivaModal(id) {
    var m = document.getElementById(id);
    if (m) {
        m.style.display = 'none';
        m.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
}

// Multi-phase Typewriter Engine with Continuous Looping (2.1s Gap per user request)
var TYPE_SPEED_MS = 38;       // Smooth typing speed per character
var DELETE_SPEED_MS = 22;     // Smooth deletion speed per character
var DEFAULT_HOLD_MS = 2100;   // 2.1 seconds gap per user instruction

function startCapsuleTypewriterLoop(capsuleEl, phrases, initialDelay) {
    if (!capsuleEl || !phrases || !phrases.length) return;
    if (capsuleEl._twTimeout) clearTimeout(capsuleEl._twTimeout);

    capsuleEl._twId = (capsuleEl._twId || 0) + 1;
    var myId = capsuleEl._twId;
    var textEl = capsuleEl.querySelector('.capsule-text') || capsuleEl;
    var stepIndex = 0;

    function backspace(callback) {
        if (capsuleEl._twId !== myId) return;
        var current = textEl.textContent || '';
        var chars = Array.from(current);
        if (chars.length > 0) {
            chars.pop();
            textEl.textContent = chars.join('');
            capsuleEl._twTimeout = setTimeout(function() {
                backspace(callback);
            }, DELETE_SPEED_MS);
        } else {
            if (callback) callback();
        }
    }

    function typeString(str, callback) {
        if (capsuleEl._twId !== myId) return;
        var chars = Array.from(str);
        var idx = 0;
        textEl.textContent = '';
        function step() {
            if (capsuleEl._twId !== myId) return;
            if (idx < chars.length) {
                textEl.textContent += chars[idx];
                idx++;
                capsuleEl._twTimeout = setTimeout(step, TYPE_SPEED_MS);
            } else {
                if (callback) callback();
            }
        }
        step();
    }

    function nextCycle() {
        if (capsuleEl._twId !== myId) return;
        stepIndex = (stepIndex + 1) % phrases.length;
        var item = phrases[stepIndex];
        var nextText = item.text;
        var holdMs = (typeof item.hold !== 'undefined') ? item.hold : DEFAULT_HOLD_MS;

        backspace(function() {
            if (capsuleEl._twId !== myId) return;
            typeString(nextText, function() {
                if (capsuleEl._twId !== myId) return;
                capsuleEl._twTimeout = setTimeout(nextCycle, holdMs);
            });
        });
    }

    // Allow user to click to immediately skip to next phrase and keep looping
    capsuleEl.addEventListener('click', function(e) {
        e.preventDefault();
        if (capsuleEl._twTimeout) clearTimeout(capsuleEl._twTimeout);
        nextCycle();
    });

    // Start automated cycle after initialDelay
    capsuleEl._twTimeout = setTimeout(nextCycle, initialDelay || DEFAULT_HOLD_MS);
}

document.addEventListener('DOMContentLoaded', function() {
    // Like Modal
    var likeBtn = document.getElementById('vivaLikeBtn');
    if (likeBtn) {
        likeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openVivaModal('viva-like-modal');
        });
    }

    // Follow Modal
    var followBtn = document.getElementById('vivaFollowBtn');
    if (followBtn) {
        followBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openVivaModal('viva-follow-modal');
        });
    }

    // Close on overlay click
    var overlays = document.querySelectorAll('.viva-modal-overlay');
    overlays.forEach(function(ov) {
        ov.addEventListener('click', function(e) {
            if (e.target === ov) closeVivaModal(ov.id);
        });
    });

    // Continuous 2.1s loop: NanoBanana ("Pro" with capital P)
    var nbCapsule = document.getElementById('capsuleNanoBanana');
    if (nbCapsule) {
        var nbPhrases = [
            { text: "NanoBanana", hold: 2100 },
            { text: "Use Nano 🍌 2", hold: 2100 },
            { text: "also 🍌 Pro", hold: 2100 }
        ];
        startCapsuleTypewriterLoop(nbCapsule, nbPhrases, 2100);
    }

    // Continuous 2.1s loop: ChatGPT (offset by 2800ms for natural stagger)
    var gptCapsule = document.getElementById('capsuleChatGPT');
    if (gptCapsule) {
        var gptPhrases = [
            { text: "ChatGPT", hold: 2100 },
            { text: "Use Image 2.0", hold: 2100 }
        ];
        startCapsuleTypewriterLoop(gptCapsule, gptPhrases, 2800);
    }
});
</script>
