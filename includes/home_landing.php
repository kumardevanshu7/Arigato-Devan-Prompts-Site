<?php
/** Logged-out homepage landing — store design */
?>
<section class="home-landing">
    <?php if (!empty($testimonials)): ?>
    <?php $testi_count = count($testimonials); ?>
    <div class="home-testi-wrap">
        <div class="home-testi-head">
            <p class="hero-label">What our users say</p>
            <h2 class="home-testi-title">Loved by <em>creators</em></h2>
        </div>

        <div class="home-testi-carousel" data-count="<?= (int)$testi_count ?>">
            <?php if ($testi_count > 1): ?>
            <button type="button" class="home-testi-nav home-testi-prev" id="homeTestiPrev" aria-label="Previous testimonial"><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" class="home-testi-nav home-testi-next" id="homeTestiNext" aria-label="Next testimonial"><i class="fa-solid fa-chevron-right"></i></button>
            <?php endif; ?>

            <div class="home-testi-track" id="miniTestiTrack" data-count="<?= (int)$testi_count ?>">
            <?php foreach ($testimonials as $ti => $t2):
                $r2 = max(0, min(10, (int)$t2['rating']));
                $tname2 = htmlspecialchars($t2['username'] ?? 'User');
                $initial = strtoupper(mb_substr($t2['username'] ?? 'U', 0, 1));
                $shorttext = mb_strlen($t2['feedback_text']) > 110 ? mb_substr($t2['feedback_text'], 0, 110) . '…' : $t2['feedback_text'];
                $avatar = !empty($t2['profile_image']) ? htmlspecialchars($t2['profile_image']) : '';
            ?>
                <article class="home-testi-card" data-index="<?= (int)$ti ?>">
                    <div class="home-testi-quote" aria-hidden="true"><i class="fa-solid fa-quote-left"></i></div>
                    <div class="home-testi-rating">
                        <span class="home-testi-score"><?= $r2 ?><small>/10</small></span>
                        <span class="home-testi-stars" aria-label="Rating <?= $r2 ?> out of 10">
                            <?php for ($s = 1; $s <= 5; $s++):
                                $filled = $r2 >= $s * 2;
                                $half = !$filled && $r2 >= ($s * 2 - 1);
                            ?>
                            <i class="fa-solid fa-star<?= $half ? '-half-stroke' : ($filled ? '' : ' home-testi-star-empty') ?>"></i>
                            <?php endfor; ?>
                        </span>
                    </div>
                    <blockquote><?= htmlspecialchars($shorttext) ?></blockquote>
                    <footer class="home-testi-user">
                        <?php if ($avatar): ?>
                            <img class="home-testi-avatar" src="<?= $avatar ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
                        <?php else: ?>
                            <span class="home-testi-avatar home-testi-avatar-letter"><?= $initial ?></span>
                        <?php endif; ?>
                        <span class="home-testi-name"><?= $tname2 ?></span>
                    </footer>
                </article>
            <?php endforeach; ?>
            </div>
        </div>

        <?php if ($testi_count > 1): ?>
        <div class="home-testi-dots" id="homeTestiDots" role="tablist" aria-label="Testimonial slides">
            <?php foreach ($testimonials as $ti => $t2): ?>
            <button type="button" class="home-testi-dot<?= $ti === 0 ? ' active' : '' ?>" data-index="<?= (int)$ti ?>" aria-label="Show testimonial <?= (int)$ti + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    (function() {
        var track = document.getElementById('miniTestiTrack');
        var dotsWrap = document.getElementById('homeTestiDots');
        if (!track) return;

        var cards = track.querySelectorAll('.home-testi-card');
        var dots = dotsWrap ? dotsWrap.querySelectorAll('.home-testi-dot') : [];
        var prev = document.getElementById('homeTestiPrev');
        var next = document.getElementById('homeTestiNext');
        var active = 0;

        function goTo(i) {
            if (!cards.length) return;
            active = (i + cards.length) % cards.length;
            var card = cards[active];
            var left = card.offsetLeft - (track.clientWidth - card.offsetWidth) / 2;
            track.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
            dots.forEach(function(d, di) { d.classList.toggle('active', di === active); });
        }

        if (prev) prev.addEventListener('click', function() { goTo(active - 1); });
        if (next) next.addEventListener('click', function() { goTo(active + 1); });

        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                goTo(parseInt(dot.dataset.index, 10) || 0);
            });
        });

        if (cards.length > 1) {
            setInterval(function() { goTo(active + 1); }, 6000);
        }

        var scrollTimer;
        track.addEventListener('scroll', function() {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(function() {
                var center = track.scrollLeft + track.clientWidth / 2;
                var best = 0;
                var bestDist = Infinity;
                cards.forEach(function(card, i) {
                    var cardCenter = card.offsetLeft + card.offsetWidth / 2;
                    var dist = Math.abs(center - cardCenter);
                    if (dist < bestDist) { bestDist = dist; best = i; }
                });
                active = best;
                dots.forEach(function(d, di) { d.classList.toggle('active', di === active); });
            }, 80);
        }, { passive: true });
    })();
    </script>
    <?php endif; ?>

    <div class="home-stickers">
        <span class="home-sticker"><i class="fa-solid fa-wand-magic-sparkles"></i> New</span>
        <span class="home-sticker"><i class="fa-solid fa-fire"></i> Hot</span>
        <span class="home-sticker"><i class="fa-solid fa-robot"></i> AI-Powered</span>
    </div>

    <p class="hero-label" style="justify-content:center;">Premium AI Couple Prompts</p>
    <h1>Create Viral<br><em>Couple AI</em> Content</h1>
    <p class="home-sub">Powered by <strong>Gemini Nano 2</strong> + <strong>ChatGPT Image 2.0</strong></p>

    <div class="home-note">
        <p id="comic-note-text">No need to login — you can copy any prompt for free! Just click <strong>Explore</strong>. Login is only for liking &amp; saving prompts.</p>
        <a href="gallery.php" class="home-btn-outline"><i class="fa-solid fa-compass"></i> Explore Prompts &rarr;</a>
    </div>
    <script>
    (function(){
        var msgs = [
            'No need to login — you can copy any prompt for free! Just click <strong>Explore</strong>. Login is only for liking &amp; saving prompts.',
            'Login ki zaroorat nahi — bina login ke bhi koi bhi prompt copy kar sakte ho! Bas <strong>Explore</strong> click karo. Login sirf like &amp; save ke liye hai.'
        ];
        var i = 0, el = document.getElementById('comic-note-text');
        if (!el) return;
        setInterval(function(){
            el.style.opacity = '0';
            setTimeout(function(){ i = (i+1) % msgs.length; el.innerHTML = msgs[i]; el.style.opacity = '1'; }, 300);
        }, 7000);
    })();
    </script>

    <div class="home-cta-row">
        <a href="login.php" class="home-btn-primary" id="hero-login-btn">
            <i class="fa-brands fa-google"></i> Login with Google
        </a>
    </div>

    <div class="home-stats">
        <div><span class="home-stat-num"><?= $sp_users ?>+</span><span class="home-stat-label">Happy Users</span></div>
        <span class="home-stat-dot">✦</span>
        <div><span class="home-stat-num"><?= $sp_prompts ?>+</span><span class="home-stat-label">AI Prompts</span></div>
        <span class="home-stat-dot">✦</span>
        <div><span class="home-stat-num"><?= $sp_unlocks ?>+</span><span class="home-stat-label">Unlocks</span></div>
    </div>

    <div class="home-steps-wrap">
        <p class="hero-label" style="justify-content:center;margin-bottom:12px;">How It Works</p>
        <?php $_steps_page = 'homepage'; include_once 'steps_guide.php'; ?>
    </div>

    <?php if ($featuredPrompt): ?>
    <div class="home-featured-locked">
        <span class="home-featured-badge">Prompt of the Day</span>
        <img loading="lazy" src="<?= htmlspecialchars($featuredPrompt['image_path']) ?>" alt="Featured Prompt">
        <p style="font-family:'Playfair Display',serif;font-weight:700;margin:12px 0;"><?= htmlspecialchars($featuredPrompt['title']) ?></p>
        <a href="login.php" class="home-btn-primary"><i class="fa-solid fa-lock-open"></i> Login to Unlock</a>
    </div>
    <?php endif; ?>

    <div class="home-compare">
        <h2><i class="fa-solid fa-scale-balanced"></i> What you get</h2>
        <div class="home-compare-grid">
            <div class="home-cmp-card with">
                <h3>With Login</h3>
                <ul>
                    <li><i class="fa-solid fa-check"></i><span>Save your prompts permanently</span></li>
                    <li><i class="fa-solid fa-check"></i><span>No need to unlock again after refresh</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Only <strong>20 taps</strong> to unlock prompts</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Access &amp; purchase premium couple prompts</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Can comment on blog posts</span></li>
                </ul>
            </div>
            <div class="home-cmp-card without">
                <h3>Without Login</h3>
                <ul>
                    <li><i class="fa-solid fa-xmark"></i><span>Cannot save prompts permanently</span></li>
                    <li><i class="fa-solid fa-xmark"></i><span>Need to unlock again after refresh</span></li>
                    <li><i class="fa-solid fa-xmark"></i><span><strong>90 taps</strong> required to unlock</span></li>
                    <li><i class="fa-solid fa-xmark"></i><span>Cannot access or purchase premium prompts</span></li>
                    <li><i class="fa-solid fa-xmark"></i><span>Cannot comment on blog posts</span></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<div class="marquee-strip">
    <div class="marquee-track">
        <?php
        $ticker_items = ['Couple Prompts are here', 'Ultra-realistic AI prompts', 'Unlock viral content ideas', 'Create stunning couple scenes', 'Your next viral reel starts here', 'Premium prompts. Real emotions.', 'More drops every week'];
        $ticker_html = '';
        foreach ($ticker_items as $item) {
            $ticker_html .= '<span class="marquee-item">' . htmlspecialchars($item) . ' <span class="marquee-dot">✦</span></span>';
        }
        echo $ticker_html . $ticker_html;
        ?>
    </div>
</div>
