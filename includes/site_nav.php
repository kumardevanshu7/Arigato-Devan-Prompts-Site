<?php
/**
 * Shared main-site navbar — digital store design system.
 * Set $nav_active before include: 'home' | 'gallery' | 'blogs' | etc.
 */
if (!function_exists('sessionAvatar')) {
    function sessionAvatar(?string $assetBase = null): string
    {
        $img = '';
        if (!empty($_SESSION['profile_image'])) {
            $img = (string) $_SESSION['profile_image'];
        } elseif (!empty($_SESSION['avatar'])) {
            $img = (string) $_SESSION['avatar'];
        }
        if ($img !== '') {
            if (preg_match('#^https?://#i', $img)) {
                return $img;
            }
            $base = $assetBase ?? '';
            return $base . ltrim($img, '/');
        }
        return 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($_SESSION['username'] ?? 'user');
    }
}
$nav_active = $nav_active ?? '';
$nav_base = $nav_base ?? '';
$nb = function (string $path) use ($nav_base): string {
    return htmlspecialchars($nav_base . $path, ENT_QUOTES, 'UTF-8');
};
$curPage    = basename($_SERVER['PHP_SELF'] ?? '');

if (!isset($nav_counts) && isset($pdo)) {
    try {
        $nc = $pdo->query("SELECT
            SUM(CASE WHEN prompt_type = 'secret' THEN 1 ELSE 0 END) as secret_code,
            SUM(CASE WHEN prompt_type = 'unreleased' THEN 1 ELSE 0 END) as unreleased,
            SUM(CASE WHEN prompt_type = 'already_uploaded' THEN 1 ELSE 0 END) as already_uploaded,
            SUM(CASE WHEN prompt_type = 'direct' THEN 1 ELSE 0 END) as direct,
            SUM(CASE WHEN prompt_type = 'solo' THEN 1 ELSE 0 END) as solo
        FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL)")->fetch(PDO::FETCH_ASSOC);
        $nav_counts = $nc ?: [];
    } catch (Exception $e) {
        $nav_counts = [];
    }
}
$nav_counts = $nav_counts ?? [];
$nav_brand_words = $nav_brand_words ?? ['devan', 'prompt', 'myra'];
?>
<div id="navStickyWrap">
    <header class="store-header">
        <div class="store-header-inner">

            <a href="<?= $nb('index.php') ?>" class="store-logo-img" title="Home">
                <img src="<?= $nb('toplogo/logo01.webp') ?>" alt="Arigato Devan Prompts Logo" height="36">
                <span class="store-logo-text" id="brandTypewriter" aria-label="arigato devan">
                    <span class="logo-prefix">arigato</span><span class="logo-dot">.</span><span class="logo-suffix" id="brandSuffix">devan</span><span class="logo-cursor" aria-hidden="true">|</span>
                </span>
            </a>

            <nav class="store-nav">
                <a href="<?= $nb('gallery.php') ?>" class="<?= $nav_active === 'gallery' ? 'gal-nav-active' : '' ?>">Gallery</a>
                <a href="<?= $nb('blogs.php') ?>" class="<?= $nav_active === 'blogs' ? 'gal-nav-active' : '' ?>">Blogs</a>
                <a href="<?= $nb('progress.php') ?>" class="gal-icon-link" title="Our Journey">
                    <i class="fa-solid fa-chart-line"></i>
                </a>

                <div class="gal-dropdown">
                    <button type="button" class="gal-dropdown-btn" aria-haspopup="true" aria-expanded="false">
                        <i class="fa-solid fa-film"></i> Reels Type
                        <i class="fa-solid fa-chevron-down" style="font-size:0.62rem;"></i>
                    </button>
                    <div class="gal-dropdown-menu">
                        <a href="<?= $nb('secret_code.php') ?>">
                            <i class="fa-solid fa-lock"></i> Secret Code Reels
                            <?= empty($nav_counts['secret_code']) ? '<span class="dd-pill soon">SOON</span>' : ($curPage === 'secret_code.php' ? '<span class="dd-pill">ACTIVE</span>' : '') ?>
                        </a>
                        <a href="<?= $nb('unreleased.php') ?>">
                            <i class="fa-solid fa-star"></i> Unreleased Reels
                            <?= empty($nav_counts['unreleased']) ? '<span class="dd-pill soon">SOON</span>' : ($curPage === 'unreleased.php' ? '<span class="dd-pill">ACTIVE</span>' : '') ?>
                        </a>
                        <a href="<?= $nb('already_uploaded.php') ?>">
                            <i class="bx bx-history"></i> Already Uploaded
                            <?= empty($nav_counts['already_uploaded']) ? '<span class="dd-pill soon">SOON</span>' : ($curPage === 'already_uploaded.php' ? '<span class="dd-pill">ACTIVE</span>' : '') ?>
                        </a>
                        <a href="<?= $nb('direct_prompts.php') ?>">
                            <i class="fa-solid fa-hand-pointer"></i> Direct Prompts
                            <?= empty($nav_counts['direct']) ? '<span class="dd-pill soon">SOON</span>' : ($curPage === 'direct_prompts.php' ? '<span class="dd-pill">ACTIVE</span>' : '') ?>
                        </a>
                        <a href="<?= $nb('solo_prompts.php') ?>">
                            <i class="fa-solid fa-user"></i> Solo Prompts
                            <?= empty($nav_counts['solo']) ? '<span class="dd-pill soon">SOON</span>' : ($curPage === 'solo_prompts.php' ? '<span class="dd-pill">ACTIVE</span>' : '') ?>
                        </a>
                        <a href="<?= $nb('curated_ai_prompts.php') ?>" class="gal-nm-link">
                            <i class="fa-solid fa-wand-magic-sparkles nm-gradient-icon"></i> <span class="nm-gradient-text">Curated AI Prompts</span>
                        </a>
                        <a href="<?= $nb('all_codes.php') ?>" class="dd-all-codes">
                            <i class="fa-solid fa-code"></i> All Secret Codes
                            <?= $curPage === 'all_codes.php' ? '<span class="dd-pill">ACTIVE</span>' : '' ?>
                        </a>
                    </div>
                </div>

                <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener" class="gal-insta-link">
                    <i class="fa-brands fa-instagram"></i>
                    @arigato.devan
                    <span class="pulse-dot"></span>
                    <span class="gal-insta-count">17K+</span>
                </a>
            </nav>

            <div class="store-header-right">
                <!-- Custom Multi-Language Switcher Dropdown (Never translate the menu itself) -->
                <div class="gal-lang-dropdown notranslate" translate="no" id="galLangDropdown">
                    <button type="button" class="gal-lang-btn notranslate" translate="no" id="galLangBtn" aria-label="Switch Language" aria-expanded="false" title="Translate Website">
                        <i class="fa-solid fa-globe notranslate" translate="no"></i>
                        <span class="gal-lang-current notranslate" translate="no" id="galLangCurrent">EN</span>
                        <i class="fa-solid fa-chevron-down notranslate" translate="no" style="font-size:0.55rem;opacity:0.7;"></i>
                    </button>
                    <div class="gal-lang-menu notranslate" translate="no" id="galLangMenu">
                        <div class="gal-lang-menu-head notranslate" translate="no">Language</div>
                        <button type="button" class="gal-lang-opt notranslate is-active" translate="no" data-lang="en"><span class="lang-flag">🇺🇸</span> English <span class="lang-code">EN</span></button>
                        <button type="button" class="gal-lang-opt notranslate" translate="no" data-lang="es"><span class="lang-flag">🇪🇸</span> Español <span class="lang-code">ES</span></button>
                        <button type="button" class="gal-lang-opt notranslate" translate="no" data-lang="hi"><span class="lang-flag">🇮🇳</span> Hindi (हिन्दी) <span class="lang-code">HI</span></button>
                    </div>
                </div>

                <button type="button" class="gal-mobile-menu-btn" id="galMobileMenuBtn" aria-label="Open menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= $nb('profile.php') ?>" class="gal-profile-link">
                        <img src="<?= htmlspecialchars(sessionAvatar($nav_base)) ?>" alt="Profile" referrerpolicy="no-referrer">
                        <span class="gal-btn-label">Profile</span>
                    </a>
                    <a href="<?= $nb('login.php?logout=1') ?>" class="gal-logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span class="gal-btn-label">Logout</span>
                    </a>
                <?php else: ?>
                    <a href="<?= $nb('login.php') ?>" class="store-signin-btn" aria-label="Sign in with Google">
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
        <a href="<?= $nb('gallery.php') ?>"><i class="fa-solid fa-images"></i> Gallery</a>
        <a href="<?= $nb('blogs.php') ?>"><i class="fa-solid fa-pen-nib"></i> Blogs</a>
        <a href="<?= $nb('progress.php') ?>"><i class="fa-solid fa-chart-line"></i> Our Journey</a>
        <button type="button" class="gal-mobile-section-btn" id="galMobileReelsBtn">
            <i class="fa-solid fa-film"></i> Reels Type <i class="fa-solid fa-chevron-down" style="margin-left:auto;font-size:0.7rem;"></i>
        </button>
        <div class="gal-mobile-sub" id="galMobileReelsSub">
            <a href="<?= $nb('secret_code.php') ?>">Secret Code Reels</a>
            <a href="<?= $nb('unreleased.php') ?>">Unreleased Reels</a>
            <a href="<?= $nb('already_uploaded.php') ?>">Already Uploaded</a>
            <a href="<?= $nb('direct_prompts.php') ?>">Direct Prompts</a>
            <a href="<?= $nb('solo_prompts.php') ?>">Solo Prompts</a>
            <a href="<?= $nb('curated_ai_prompts.php') ?>" class="gal-nm-link"><i class="fa-solid fa-wand-magic-sparkles nm-gradient-icon"></i> <span class="nm-gradient-text">Curated AI Prompts</span></a>
            <a href="<?= $nb('all_codes.php') ?>">All Secret Codes</a>
        </div>
        <a href="https://www.instagram.com/arigato.devan/" target="_blank" rel="noopener">
            <i class="fa-brands fa-instagram"></i> @arigato.devan
        </a>
        <div class="gal-mobile-lang-box notranslate" translate="no">
            <div class="gal-mobile-lang-title notranslate" translate="no"><i class="fa-solid fa-globe"></i> Select Language</div>
            <div class="gal-mobile-lang-grid notranslate" translate="no">
                <button type="button" class="gal-m-lang-btn notranslate is-active" translate="no" data-lang="en">🇺🇸 English</button>
                <button type="button" class="gal-m-lang-btn notranslate" translate="no" data-lang="es">🇪🇸 Español</button>
                <button type="button" class="gal-m-lang-btn notranslate" translate="no" data-lang="hi">🇮🇳 Hindi</button>
            </div>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?= $nb('profile.php') ?>"><i class="fa-solid fa-user"></i> Profile</a>
        <a href="<?= $nb('login.php?logout=1') ?>" style="color:#e11d48;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <?php else: ?>
        <a href="<?= $nb('login.php') ?>"><i class="fa-solid fa-right-to-bracket"></i> Sign in</a>
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

    /* Brand title typewriter: arigato.prompt ↔ arigato.devan (↔ heroines on some pages) */
    var suffixEl = document.getElementById('brandSuffix');
    var brandWrap = document.getElementById('brandTypewriter');
    if (suffixEl && brandWrap) {
        var words = <?= json_encode(array_values($nav_brand_words), JSON_UNESCAPED_UNICODE) ?>;
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

    /* Multi-Language Auto-Translate Logic (EN, ES, HI) */
    (function() {
        function getSavedLang() {
            var m = document.cookie.match(/(?:^|;\s*)googtrans=\/en\/([a-z]{2})/i);
            return m ? m[1].toLowerCase() : 'en';
        }

        function updateLangUI(lang) {
            var label = (lang === 'es' ? 'ES' : (lang === 'hi' ? 'HI' : 'EN'));
            var cur = document.getElementById('galLangCurrent');
            if (cur) cur.textContent = label;
            document.querySelectorAll('.gal-lang-opt').forEach(function(el) {
                el.classList.toggle('is-active', el.getAttribute('data-lang') === lang);
            });
            document.querySelectorAll('.gal-m-lang-btn').forEach(function(el) {
                el.classList.toggle('is-active', el.getAttribute('data-lang') === lang);
            });
        }

        function switchLanguage(lang) {
            lang = (lang || 'en').toLowerCase();
            var host = window.location.hostname;
            if (lang === 'en') {
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + host;
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
                document.cookie = "googtrans=/en/en; path=/; domain=" + host;
                document.cookie = "googtrans=/en/en; path=/";
            } else {
                var val = '/en/' + lang;
                document.cookie = "googtrans=" + val + "; path=/; domain=" + host;
                document.cookie = "googtrans=" + val + "; path=/";
            }
            var combo = document.querySelector('.goog-te-combo');
            if (combo) {
                combo.value = lang;
                combo.dispatchEvent(new Event('change'));
            } else {
                window.location.reload();
            }
            updateLangUI(lang);
        }

        var langDropdown = document.getElementById('galLangDropdown');
        var langBtn = document.getElementById('galLangBtn');
        if (langBtn && langDropdown) {
            langBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var isOpen = langDropdown.classList.toggle('active');
                langBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
            document.addEventListener('click', function(e) {
                if (!langDropdown.contains(e.target)) {
                    langDropdown.classList.remove('active');
                    langBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }
        document.querySelectorAll('.gal-lang-opt, .gal-m-lang-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var lang = btn.getAttribute('data-lang');
                switchLanguage(lang);
                if (langDropdown) {
                    langDropdown.classList.remove('active');
                    if (langBtn) langBtn.setAttribute('aria-expanded', 'false');
                }
            });
        });

        // Actively suppress Google Translate top bar and enforce 0px body top
        function suppressGoogleBanner() {
            var frames = document.querySelectorAll('iframe.skiptranslate, iframe.goog-te-banner-frame, .VIpgJd-ZVi9I-OR9QNe-HandL');
            frames.forEach(function(el) {
                el.style.setProperty('display', 'none', 'important');
                el.style.setProperty('visibility', 'hidden', 'important');
                el.style.setProperty('height', '0', 'important');
            });
            if (document.body.style.top && document.body.style.top !== '0px') {
                document.body.style.setProperty('top', '0px', 'important');
            }
            if (document.documentElement.style.top && document.documentElement.style.top !== '0px') {
                document.documentElement.style.setProperty('top', '0px', 'important');
            }
        }
        setInterval(suppressGoogleBanner, 200);
        updateLangUI(getSavedLang());
    })();
})();
</script>

<style>
.goog-te-banner-frame,
.goog-te-banner-frame.skiptranslate,
iframe.goog-te-banner-frame,
iframe.skiptranslate,
iframe[class*="skiptranslate"],
iframe[id*=":1.container"],
iframe[id*=":2.container"],
.VIpgJd-ZVi9I-OR9QNe-HandL,
.VIpgJd-yAWNEb-L7lbkb,
.VIpgJd-yAWNEb-VIpgJd-fmcmS-sn54Q,
#goog-gt-tt,
.goog-tooltip,
.goog-te-balloon-frame {
    display: none !important;
    visibility: hidden !important;
    height: 0 !important;
    width: 0 !important;
    opacity: 0 !important;
    pointer-events: none !important;
}
html, body {
    top: 0px !important;
    position: static !important;
}
body.translated-ltr,
body.translated-rtl {
    top: 0px !important;
    margin-top: 0px !important;
}
#google_translate_element { display: none !important; }
.goog-tooltip, .goog-tooltip:hover { display: none !important; }
.goog-text-highlight { background-color: transparent !important; box-shadow: none !important; }
font[style] { background: transparent !important; box-shadow: none !important; }
</style>

<div id="google_translate_element" style="display:none;"></div>
<script type="text/javascript">
function googleTranslateElementInit() {
    if (window.google && google.translate) {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,es,hi',
            autoDisplay: false
        }, 'google_translate_element');
    }
}
</script>
<script type="text/javascript" defer src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
