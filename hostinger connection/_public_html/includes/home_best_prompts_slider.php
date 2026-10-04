<?php
/**
 * Best Prompts Card Slider for Index Page
 * Displays handpicked cards toggled as "Main Page Card" from prompts & curated_prompts.
 * Every card receives a randomized aesthetic light pastel color from a 45+ palette on each refresh.
 */
if (empty($main_page_cards)) {
    return;
}
require_once __DIR__ . '/../slug_helper.php';
require_once __DIR__ . '/prompt_cards.php';

// Curated 50 aesthetic light/pastel color palette
$light_palette = get_prompt_card_palette();

// Randomize color palette order on every page refresh
shuffle($light_palette);
?>
<section class="home-best-prompts-section" id="bestPromptsSection" aria-label="Best Prompts">
    <div class="home-bp-head">
        <div class="home-bp-title-wrap">
            <span class="home-bp-eyebrow"><i class="fa-solid fa-fire"></i> Best Prompts</span>
            <h2 class="home-bp-title">Handpicked Trending Ideas</h2>
        </div>
        <div class="home-bp-nav">
            <button type="button" class="home-bp-arrow prev" id="bpSlidePrev" aria-label="Previous prompt" title="Previous"><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" class="home-bp-arrow next" id="bpSlideNext" aria-label="Next prompt" title="Next"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </div>

    <div class="home-bp-slider-wrap">
        <div class="home-bp-track" id="bpSliderTrack">
            <?php foreach ($main_page_cards as $idx => $card):
                // Dynamically assign random light color
                $card_bg = $light_palette[$idx % count($light_palette)];

                // Destination link
                if (($card['source_type'] ?? '') === 'curated') {
                    $card_link = function_exists('nm_prompt_url') ? nm_prompt_url($card) : 'curated_prompt.php?id=' . (int)$card['id'];
                } else {
                    $c_slug = trim($card['slug'] ?? '');
                    if (function_exists('nm_is_local') && !nm_is_local() && $c_slug !== '') {
                        $card_link = '/prompts/' . rawurlencode($c_slug);
                    } else {
                        $card_link = 'prompt.php?id=' . (int)$card['id'];
                    }
                }

                // Badges
                $raw_tags = array_filter(array_map('trim', explode(',', $card['tag'] ?? '')));
                $primary_badge = !empty($raw_tags) ? htmlspecialchars(ucfirst($raw_tags[0])) : (ucfirst($card['prompt_type'] ?? 'Couple'));

                // Engine / Type badge
                $bwi = strtolower($card['best_works_in'] ?? '');
                if (($card['source_type'] ?? '') === 'curated') {
                    $engine_badge = 'Curated Drop';
                } elseif ($bwi === 'gemini') {
                    $engine_badge = 'Gemini Nano';
                } elseif ($bwi === 'chatgpt') {
                    $engine_badge = 'ChatGPT 2.0';
                } else {
                    $engine_badge = 'Viral Reel';
                }

                // Subtitle / snippet
                $snippet = '';
                if (!empty($card['about_prompt'])) {
                    $snippet = trim(strip_tags($card['about_prompt']));
                } elseif (!empty($card['description'])) {
                    $snippet = trim(strip_tags($card['description']));
                } elseif (!empty($card['meta_description'])) {
                    $snippet = trim(strip_tags($card['meta_description']));
                }
                if ($snippet === '') {
                    $snippet = 'Ultra-realistic AI photo prompt with cinematic lighting and facial fidelity.';
                }

                $img_src = !empty($card['image_path']) ? htmlspecialchars($card['image_path']) : 'uploads/img_placeholder.webp';
            ?>
            <a href="<?= htmlspecialchars($card_link) ?>" class="bpc-card" style="background: <?= htmlspecialchars($card_bg) ?>;" data-card-id="<?= (int)$card['id'] ?>" data-source="<?= htmlspecialchars($card['source_type'] ?? 'regular') ?>">
                <div class="bpc-top">
                    <div class="bpc-badges-row">
                        <div class="bpc-pills-wrap">
                            <span class="bpc-pill bpc-pill-cat"><?= $primary_badge ?></span>
                            <span class="bpc-pill bpc-pill-engine"><?= htmlspecialchars($engine_badge) ?></span>
                        </div>
                        <span class="bpc-top-icon" aria-hidden="true">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </span>
                    </div>

                    <h3 class="bpc-title"><?= htmlspecialchars($card['title']) ?></h3>
                    <p class="bpc-sub"><?= htmlspecialchars($snippet) ?></p>
                </div>

                <div class="bpc-img-wrap">
                    <img class="bpc-img" src="<?= $img_src ?>" alt="<?= htmlspecialchars($card['title']) ?>" loading="<?= $idx < 4 ? 'eager' : 'lazy' ?>">
                    <span class="bpc-glass-btn">
                        <span>View Prompt</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
/* ============================================================
   BEST PROMPTS CARD SLIDER STYLES
   ============================================================ */
.home-best-prompts-section {
    margin: 12px 0 24px;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}
.home-bp-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    padding: 0 4px;
}
.home-bp-title-wrap {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.home-bp-eyebrow {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--pal-teal, #567C8D);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.home-bp-eyebrow i {
    color: #f59e0b;
    font-size: 0.75rem;
}
.home-bp-title {
    font-size: clamp(1.35rem, 3.5vw, 1.75rem);
    font-weight: 900;
    color: var(--pal-navy, #2F4156);
    letter-spacing: -0.02em;
    margin: 0;
    line-height: 1.15;
    font-family: 'Inter', -apple-system, sans-serif;
}
.home-bp-nav {
    display: flex;
    align-items: center;
    gap: 8px;
}
.home-bp-arrow {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #111827;
    color: #ffffff;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.22s ease;
    box-shadow: 0 4px 14px rgba(17, 24, 39, 0.18);
}
.home-bp-arrow:hover {
    background: #2F4156;
    transform: translateY(-1px) scale(1.05);
}
.home-bp-arrow:active {
    transform: translateY(0) scale(0.96);
}

/* Slider Track */
.home-bp-slider-wrap {
    position: relative;
    width: 100%;
    overflow: hidden;
}
.home-bp-track {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    padding: 6px 2px 18px;
    scrollbar-width: none;
}
.home-bp-track::-webkit-scrollbar {
    display: none;
}

/* Card item */
.bpc-card {
    flex: 0 0 260px;
    max-width: 270px;
    min-width: 245px;
    height: 415px;
    scroll-snap-align: start;
    border-radius: 24px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    text-decoration: none !important;
    position: relative;
    overflow: hidden;
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.28s ease;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-sizing: border-box;
}
.bpc-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 32px rgba(0, 0, 0, 0.12);
}

/* Top section */
.bpc-top {
    padding: 16px 16px 4px;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}
.bpc-badges-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
}
.bpc-pills-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.bpc-pill {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    line-height: 1.3;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(255, 255, 255, 0.92);
    color: #111827;
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
}
.bpc-top-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    flex-shrink: 0;
    background: #111827;
    color: #ffffff;
}
.bpc-title {
    font-size: 1.25rem;
    font-weight: 900;
    line-height: 1.18;
    margin: 0 0 4px;
    letter-spacing: -0.015em;
    color: #111827;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.bpc-sub {
    font-size: 0.76rem;
    line-height: 1.35;
    margin: 0 0 6px;
    color: rgba(17, 24, 39, 0.72);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Bottom section: Hero Photo (Increased height & snug gap) */
.bpc-img-wrap {
    margin: 4px 12px 12px;
    border-radius: 18px;
    height: 250px;
    flex: 1;
    position: relative;
    overflow: hidden;
    background: #e2e8f0;
}
.bpc-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.bpc-card:hover .bpc-img {
    transform: scale(1.06);
}

/* Glassmorphism Action Pill */
.bpc-glass-btn {
    position: absolute;
    bottom: 12px;
    left: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    border-radius: 999px;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    background: rgba(17, 24, 39, 0.42);
    border: 1px solid rgba(255, 255, 255, 0.42);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.28);
    transition: all 0.2s ease;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5);
}
.bpc-card:hover .bpc-glass-btn {
    background: rgba(17, 24, 39, 0.6);
    transform: translateX(2px);
}
.bpc-glass-btn i {
    font-size: 0.72rem;
    transition: transform 0.2s ease;
}
.bpc-card:hover .bpc-glass-btn i {
    transform: translateX(3px);
}

@media (max-width: 600px) {
    .bpc-card {
        flex: 0 0 235px;
        min-width: 220px;
        height: 385px;
        border-radius: 20px;
    }
    .bpc-img-wrap {
        height: 225px;
        margin: 4px 10px 10px;
    }
    .bpc-title {
        font-size: 1.15rem;
    }
    .home-bp-arrow {
        width: 34px;
        height: 34px;
        font-size: 0.8rem;
    }
}
</style>

<script>
(function() {
    var wrap = document.getElementById('bestPromptsSection');
    var track = document.getElementById('bpSliderTrack');
    var prevBtn = document.getElementById('bpSlidePrev');
    var nextBtn = document.getElementById('bpSlideNext');
    if (!track) return;

    var autoInterval = null;
    var isUserInteracting = false;
    var userResumeTimeout = null;
    var isHovered = false;
    var isDragging = false;
    var isVisible = true;

    // Dynamically calculate one card step (card width + track gap)
    function getCardStep() {
        var card = track.querySelector('.bpc-card');
        if (card) {
            var gap = 16;
            try {
                var computedGap = parseFloat(window.getComputedStyle(track).gap);
                if (!isNaN(computedGap) && computedGap > 0) gap = computedGap;
            } catch(e) {}
            return card.offsetWidth + gap;
        }
        return 280;
    }

    function slideNext() {
        var step = getCardStep();
        var maxScroll = track.scrollWidth - track.clientWidth;
        if (track.scrollLeft >= maxScroll - 12) {
            track.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            track.scrollBy({ left: step, behavior: 'smooth' });
        }
    }

    function slidePrev() {
        var step = getCardStep();
        if (track.scrollLeft <= 12) {
            var maxScroll = track.scrollWidth - track.clientWidth;
            track.scrollTo({ left: maxScroll, behavior: 'smooth' });
        } else {
            track.scrollBy({ left: -step, behavior: 'smooth' });
        }
    }

    // Auto-slide every 1.5 seconds when user is NOT interacting
    function startAutoSlide() {
        stopAutoSlide();
        if (isUserInteracting || isHovered || isDragging || !isVisible) return;
        autoInterval = setInterval(function() {
            if (!isUserInteracting && !isHovered && !isDragging && isVisible) {
                slideNext();
            }
        }, 1500);
    }

    function stopAutoSlide() {
        if (autoInterval) {
            clearInterval(autoInterval);
            autoInterval = null;
        }
    }

    function pauseTemporarily(cooldownMs) {
        isUserInteracting = true;
        stopAutoSlide();
        if (userResumeTimeout) clearTimeout(userResumeTimeout);
        userResumeTimeout = setTimeout(function() {
            isUserInteracting = false;
            startAutoSlide();
        }, cooldownMs || 2500);
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function(e) {
            e.preventDefault();
            slidePrev();
            pauseTemporarily(3000);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();
            slideNext();
            pauseTemporarily(3000);
        });
    }

    // Pause on mouse hover (desktop)
    if (wrap) {
        wrap.addEventListener('mouseenter', function() {
            isHovered = true;
            stopAutoSlide();
        });
        wrap.addEventListener('mouseleave', function() {
            isHovered = false;
            if (!isUserInteracting && !isDragging) {
                startAutoSlide();
            }
        });
    }

    // Touch support (mobile swiping)
    track.addEventListener('touchstart', function() {
        isUserInteracting = true;
        stopAutoSlide();
    }, { passive: true });

    track.addEventListener('touchend', function() {
        pauseTemporarily(2500);
    }, { passive: true });

    track.addEventListener('touchcancel', function() {
        pauseTemporarily(2000);
    }, { passive: true });

    // Desktop mouse drag to scroll
    var startX, scrollLeft;
    var hasMoved = false;

    track.addEventListener('mousedown', function(e) {
        isDragging = true;
        hasMoved = false;
        startX = e.pageX - track.offsetLeft;
        scrollLeft = track.scrollLeft;
        track.style.cursor = 'grabbing';
        track.style.scrollSnapType = 'none';
        stopAutoSlide();
    });

    window.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            track.style.cursor = '';
            track.style.scrollSnapType = 'x mandatory';
            pauseTemporarily(2500);
        }
    });

    track.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        var x = e.pageX - track.offsetLeft;
        var walk = (x - startX);
        if (Math.abs(walk) > 5) {
            hasMoved = true;
            e.preventDefault();
            track.scrollLeft = scrollLeft - walk * 1.25;
        }
    });

    // Prevent accidental link opening while dragging
    track.addEventListener('click', function(e) {
        if (hasMoved) {
            e.preventDefault();
            e.stopPropagation();
            hasMoved = false;
        }
    }, true);

    // Pause when tab/window is hidden, resume when active
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoSlide();
        } else {
            startAutoSlide();
        }
    });

    // IntersectionObserver: only auto-slide when slider is currently on screen
    if ('IntersectionObserver' in window && wrap) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                isVisible = entry.isIntersecting;
                if (isVisible) {
                    startAutoSlide();
                } else {
                    stopAutoSlide();
                }
            });
        }, { threshold: 0.15 });
        observer.observe(wrap);
    } else {
        startAutoSlide();
    }
})();
</script>
