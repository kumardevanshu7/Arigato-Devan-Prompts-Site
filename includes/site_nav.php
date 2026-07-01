<?php
/**
 * Shared main-site navbar — digital store design system.
 * Set $nav_active before include: 'home' | 'gallery' | 'blogs' | etc.
 */
if (!function_exists('sessionAvatar')) {
    function sessionAvatar() {
        return !empty($_SESSION['profile_image'])
            ? $_SESSION['profile_image']
            : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($_SESSION['username'] ?? 'user');
    }
}
$nav_active = $nav_active ?? '';
$curPage    = basename($_SERVER['PHP_SELF'] ?? '');

if (!isset($nav_counts) && isset($pdo)) {
    try {
        $nc = $pdo->query("SELECT
            SUM(CASE WHEN prompt_type = 'secret' THEN 1 ELSE 0 END) as secret_code,
            SUM(CASE WHEN prompt_type = 'unreleased' THEN 1 ELSE 0 END) as unreleased,
            SUM(CASE WHEN prompt_type = 'insta_viral' THEN 1 ELSE 0 END) as insta_viral,
            SUM(CASE WHEN prompt_type = 'already_uploaded' THEN 1 ELSE 0 END) as already_uploaded,
            SUM(CASE WHEN prompt_type = 'direct' THEN 1 ELSE 0 END) as direct
        FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL)")->fetch(PDO::FETCH_ASSOC);
        $nav_counts = $nc ?: [];
    } catch (Exception $e) {
        $nav_counts = [];
    }
}
$nav_counts = $nav_counts ?? [];
?>
<div id="navStickyWrap">
    <header class="store-header">
        <div class="store-header-inner">

            <a href="index.php" class="store-logo-img" title="Home">
                <img src="toplogo/logo01.webp" alt="Arigato Devan Prompts Logo" height="36">
                <span class="store-logo-text" id="brandTypewriter" aria-label="arigato prompt">
                    <span class="logo-prefix">arigato</span><span class="logo-dot">.</span><span class="logo-suffix" id="brandSuffix">prompt</span><span class="logo-cursor" aria-hidden="true">|</span>
                </span>
            </a>

            <nav class="store-nav">
                <a href="digital_store/index.php" class="shop-glowing-btn">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    Shop
                </a>
                <a href="gallery.php" class="<?= $nav_active === 'gallery' ? 'gal-nav-active' : '' ?>">Gallery</a>
                <a href="blogs.php" class="<?= $nav_active === 'blogs' ? 'gal-nav-active' : '' ?>">Blogs</a>
                <a href="progress.php" class="gal-icon-link" title="Our Journey">
                    <i class="fa-solid fa-chart-line"></i>
                </a>
                <a href="faq.php" class="gal-icon-link" title="FAQ">
                    <i class="fa-solid fa-circle-question"></i>
                </a>

                <div class="gal-dropdown">
                    <button type="button" class="gal-dropdown-btn" aria-haspopup="true" aria-expanded="false">
                        <i class="fa-solid fa-film"></i> Reels Type
                        <i class="fa-solid fa-chevron-down" style="font-size:0.62rem;"></i>
                    </button>
                    <div class="gal-dropdown-menu">
                        <a href="secret_code.php">
                            <i class="fa-solid fa-lock"></i> Secret Code Reels
                            <?= empty($nav_counts['secret_code']) ? '<span class="dd-pill soon">SOON</span>' : ($curPage === 'secret_code.php' ? '<span class="dd-pill">ACTIVE</span>' : '') ?>
                        </a>
                        <a href="unreleased.php">
                            <i class="fa-solid fa-star"></i> Unreleased Reels
                            <?= empty($nav_counts['unreleased']) ? '<span class="dd-pill soon">SOON</span>' : ($curPage === 'unreleased.php' ? '<span class="dd-pill">ACTIVE</span>' : '') ?>
                        </a>
                        <a href="insta_viral.php">
                            <i class="fa-brands fa-instagram"></i> Insta Viral Reels
                            <?= empty($nav_counts['insta_viral']) ? '<span class="dd-pill soon">SOON</span>' : ($curPage === 'insta_viral.php' ? '<span class="dd-pill">ACTIVE</span>' : '') ?>
                        </a>
                        <a href="already_uploaded.php">
                            <i class="bx bx-history"></i> Already Uploaded
                            <?= empty($nav_counts['already_uploaded']) ? '<span class="dd-pill soon">SOON</span>' : ($curPage === 'already_uploaded.php' ? '<span class="dd-pill">ACTIVE</span>' : '') ?>
                        </a>
                        <a href="direct_prompts.php">
                            <i class="fa-solid fa-hand-pointer"></i> Direct Prompts
                            <?= empty($nav_counts['direct']) ? '<span class="dd-pill soon">SOON</span>' : ($curPage === 'direct_prompts.php' ? '<span class="dd-pill">ACTIVE</span>' : '') ?>
                        </a>
                    </div>
                </div>

                <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener" class="gal-insta-link">
                    <i class="fa-brands fa-instagram"></i>
                    @arigato.devan
                    <span class="pulse-dot"></span>
                    <span class="gal-insta-count">15K+</span>
                </a>
            </nav>

            <div class="store-header-right">
                <button type="button" class="gal-mobile-menu-btn" id="galMobileMenuBtn" aria-label="Open menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="gal-profile-link">
                        <img src="<?= htmlspecialchars(sessionAvatar()) ?>" alt="Profile" referrerpolicy="no-referrer">
                        <span class="gal-btn-label">Profile</span>
                    </a>
                    <a href="login.php?logout=1" class="gal-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span class="gal-btn-label">Logout</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="store-signin-btn" aria-label="Sign in with Google">
                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="15" height="15" alt="">
                        <span class="gal-btn-label">Sign in with Google</span>
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </header>
</div>

<div class="gal-mobile-overlay" id="galMobileOverlay" aria-hidden="true"></div>
<aside class="gal-mobile-drawer" id="galMobileDrawer" aria-hidden="true">
    <div class="gal-mobile-drawer-head">
        <span>Menu</span>
        <button type="button" class="gal-mobile-close" id="galMobileClose" aria-label="Close menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <nav class="gal-mobile-nav">
        <a href="digital_store/index.php"><i class="fa-solid fa-shop"></i> Shop</a>
        <a href="gallery.php"><i class="fa-solid fa-images"></i> Gallery</a>
        <a href="blogs.php"><i class="fa-solid fa-pen-nib"></i> Blogs</a>
        <a href="progress.php"><i class="fa-solid fa-chart-line"></i> Our Journey</a>
        <a href="faq.php"><i class="fa-solid fa-circle-question"></i> FAQ</a>
        <button type="button" class="gal-mobile-section-btn" id="galMobileReelsBtn">
            <i class="fa-solid fa-film"></i> Reels Type <i class="fa-solid fa-chevron-down" style="margin-left:auto;font-size:0.7rem;"></i>
        </button>
        <div class="gal-mobile-sub" id="galMobileReelsSub">
            <a href="secret_code.php">Secret Code Reels</a>
            <a href="unreleased.php">Unreleased Reels</a>
            <a href="insta_viral.php">Insta Viral Reels</a>
            <a href="already_uploaded.php">Already Uploaded</a>
            <a href="direct_prompts.php">Direct Prompts</a>
        </div>
        <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener">
            <i class="fa-brands fa-instagram"></i> @arigato.devan
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
        <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
        <a href="login.php?logout=1" style="color:#e11d48;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <?php else: ?>
        <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Sign in</a>
        <?php endif; ?>
    </nav>
</aside>
<script>
(function() {
    /* Desktop Reels dropdown — click only, not hover */
    document.querySelectorAll('.gal-dropdown').forEach(function(dd) {
        var btn = dd.querySelector('.gal-dropdown-btn');
        if (!btn) return;
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var wasOpen = dd.classList.contains('is-open');
            document.querySelectorAll('.gal-dropdown.is-open').forEach(function(d) {
                d.classList.remove('is-open');
                var b = d.querySelector('.gal-dropdown-btn');
                if (b) b.setAttribute('aria-expanded', 'false');
            });
            if (!wasOpen) {
                dd.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });
    document.addEventListener('click', function() {
        document.querySelectorAll('.gal-dropdown.is-open').forEach(function(d) {
            d.classList.remove('is-open');
            var b = d.querySelector('.gal-dropdown-btn');
            if (b) b.setAttribute('aria-expanded', 'false');
        });
    });
    document.querySelectorAll('.gal-dropdown-menu').forEach(function(menu) {
        menu.addEventListener('click', function(e) { e.stopPropagation(); });
    });

    /* Mobile drawer */
    var btn = document.getElementById('galMobileMenuBtn');
    var drawer = document.getElementById('galMobileDrawer');
    var overlay = document.getElementById('galMobileOverlay');
    var closeBtn = document.getElementById('galMobileClose');
    var reelsBtn = document.getElementById('galMobileReelsBtn');
    var reelsSub = document.getElementById('galMobileReelsSub');
    if (!btn || !drawer) return;
    function openMenu() {
        drawer.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeMenu() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    btn.addEventListener('click', openMenu);
    if (closeBtn) closeBtn.addEventListener('click', closeMenu);
    if (overlay) overlay.addEventListener('click', closeMenu);
    if (reelsBtn && reelsSub) {
        reelsBtn.addEventListener('click', function() { reelsSub.classList.toggle('open'); });
    }

    /* Brand title typewriter: arigato.prompt ↔ arigato.devan */
    var suffixEl = document.getElementById('brandSuffix');
    var brandWrap = document.getElementById('brandTypewriter');
    if (suffixEl && brandWrap) {
        var words = ['prompt', 'devan'];
        var wordIdx = 0;
        var typing = false;

        function setAria(word) {
            brandWrap.setAttribute('aria-label', 'arigato ' + word);
        }

        function wait(ms) {
            return new Promise(function(resolve) { setTimeout(resolve, ms); });
        }

        function typeWord(word) {
            return new Promise(function(resolve) {
                var i = 0;
                suffixEl.textContent = '';
                typing = true;
                (function tick() {
                    if (i < word.length) {
                        suffixEl.textContent += word.charAt(i++);
                        setTimeout(tick, 85);
                    } else {
                        typing = false;
                        setAria(word);
                        resolve();
                    }
                })();
            });
        }

        function deleteWord() {
            return new Promise(function(resolve) {
                typing = true;
                (function tick() {
                    var cur = suffixEl.textContent;
                    if (cur.length > 0) {
                        suffixEl.textContent = cur.slice(0, -1);
                        setTimeout(tick, 45);
                    } else {
                        typing = false;
                        resolve();
                    }
                })();
            });
        }

        (async function loop() {
            setAria(words[0]);
            while (true) {
                await wait(4000);
                await deleteWord();
                wordIdx = (wordIdx + 1) % words.length;
                await typeWord(words[wordIdx]);
            }
        })();
    }

    /* Back to top — one per page */
    if (!document.getElementById('back-to-top')) {
        var topBtn = document.createElement('button');
        topBtn.type = 'button';
        topBtn.id = 'back-to-top';
        topBtn.setAttribute('aria-label', 'Back to top');
        topBtn.innerHTML = '<i class="fa-solid fa-chevron-up"></i>';
        document.body.appendChild(topBtn);
        window.addEventListener('scroll', function() {
            topBtn.classList.toggle('visible', window.scrollY > 380);
        }, { passive: true });
        topBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
})();
</script>
