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

    <div class="home-trust-row" aria-label="Site policies and information">
        <span class="home-trust-label"><i class="fa-solid fa-shield-halved"></i> Verified &amp; Transparent</span>
        <div class="home-trust-links">
            <a href="about.php"><i class="fa-solid fa-user"></i> About Us</a>
            <a href="contact.php"><i class="fa-solid fa-envelope"></i> Contact Us</a>
            <a href="privacy.php"><i class="fa-solid fa-lock"></i> Privacy Policy</a>
            <a href="terms.php"><i class="fa-solid fa-file-contract"></i> Terms &amp; Conditions</a>
        </div>
    </div>

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

    <section class="home-seo-about">
        <p class="hero-label" style="justify-content:center;">Why Arigato Devan</p>
        <h2>Everything About Our <em>AI Couple Prompts</em> — Gemini, ChatGPT &amp; Nano Banana</h2>

        <p>Arigato Devan is a home for <strong>couple prompts for AI</strong> — a fast-growing library built for one simple reason: most AI photo prompts online are copy-pasted, untested, and end up giving you two random strangers instead of an actual couple photo of you and your partner. Every prompt on this site is written, tested, and refined so that when you upload your own photo, your <strong>face stays exactly the same</strong> — no distortion, no changed features, no random AI-generated faces. That one detail is what most people searching for <strong>couple prompts</strong>, <strong>ai couple prompts</strong>, or a <strong>couple prompts generator</strong> actually care about, and it's the thing we focus on with every single prompt we publish.</p>

        <h3>Couple Prompts for Every AI Platform You Already Use</h3>
        <p>You don't need any special app or paid software to use what's on this site. Our prompts are written specifically for the tools most people already have on their phone — <strong>Gemini Nano Banana</strong> and <strong>ChatGPT Image 2.0</strong>. If you've been searching for <strong>gemini couple prompts</strong>, <strong>couple prompts for gemini ai</strong>, <strong>simple couple prompts for gemini ai</strong>, <strong>couple prompts for chatgpt</strong>, or <strong>chat gpt couple prompts</strong>, you'll find categorised, ready-to-copy versions for both platforms, so you can pick whichever AI tool you're comfortable with and get the same high-quality, realistic result.</p>

        <h3>Every Mood, Occasion &amp; Style — Not Just "Romantic"</h3>
        <p>"Couple prompt" doesn't mean one thing to everyone, so we don't treat it like it does. Looking for something soft and everyday? Try our <strong>cute couple prompts</strong> and <strong>love couple prompts</strong>. Planning a shaadi post or festival upload? We've built out <strong>indian wedding couple prompts</strong>, <strong>indian wedding couple prompts free</strong>, <strong>wedding couple prompts</strong>, and seasonal drops like <strong>navratri couple prompts for gemini</strong> — with more festivals (Karwa Chauth, Diwali, Holi, Teej, Valentine's Day) added throughout the year. Want a prompt personalised with your names on it? That's exactly what our <strong>ai couple prompts with name</strong> category is for. Whatever the occasion, if you can describe it, we're probably already building a prompt for it.</p>

        <h3>Built to Actually Rank — And Actually Work</h3>
        <p>Behind the scenes, every prompt is tagged, categorised, and written with real search intent in mind — that's why whether you land here searching <strong>couple prompts for ai</strong>, <strong>couple prompts ai</strong>, <strong>couple prompts for ai gemini</strong>, or <strong>best couple prompts for gemini</strong>, you'll find a prompt that matches what you were actually looking for, not generic filler content. Beyond the main gallery, we also run a dedicated <strong>Curated AI Prompts</strong> section of carefully refined and enhanced prompts inspired by various creative sources, a <strong>Prompt of the Day</strong> feature so you never run out of fresh ideas, and a blog where we break down prompting techniques, new AI features, and step-by-step guides — all built around the same couple-photography niche.</p>

        <p>New prompts are added every week, existing ones are updated as Gemini and ChatGPT's image models improve, and nothing is left stale. You can copy any prompt for free without logging in — an account is only needed if you want to save your favourites, unlock premium prompts faster, or comment on our blog posts. That's the whole idea: a genuinely useful, constantly-updated <strong>couple prompt</strong> resource, not a one-time list that goes out of date in a month.</p>

        <div class="home-seo-tags" aria-label="Popular searches">
            <span>couple prompt for gemini ai</span>
            <span>couple prompt</span>
            <span>couple prompt gemini</span>
            <span>trending couple prompt</span>
            <span>gemini couple prompt</span>
            <span>couple prompt for gemini</span>
            <span>couple prompt chatgpt</span>
            <span>romantic couple prompt</span>
            <span>couple prompt chatgpt indian</span>
            <span>chatgpt couple prompt</span>
            <span>couple prompt generator</span>
            <span>ai couple prompts</span>
            <span>indian wedding couple prompts</span>
        </div>
    </section>
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
