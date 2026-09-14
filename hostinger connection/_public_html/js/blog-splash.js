(function () {
  'use strict';

  var LOADING_MS = 1500;
  var HOLD_MS = 350;
  var CHAR_MS = 35;
  var WORD_PROMPT = 'prompt';
  var WORD_BLOG = 'blog';

  function sleep(ms) {
    return new Promise(function (resolve) {
      setTimeout(resolve, ms);
    });
  }

  function lockScroll() {
    document.documentElement.classList.add('no-scroll');
    document.body.classList.add('no-scroll', 'blog-splash-active');
  }

  function unlockScroll() {
    document.documentElement.classList.remove('no-scroll');
    document.body.classList.remove('no-scroll', 'blog-splash-active');
  }

  function setSuffixClass(el, word) {
    el.classList.remove('splash-suffix--prompt', 'splash-suffix--blog');
    el.classList.add(word === WORD_BLOG ? 'splash-suffix--blog' : 'splash-suffix--prompt');
  }

  async function morphSuffix(el, fromWord, toWord) {
    setSuffixClass(el, fromWord);
    el.textContent = fromWord;
    await sleep(HOLD_MS);

    var cur = fromWord;
    while (cur.length > 0) {
      cur = cur.slice(0, -1);
      el.textContent = cur;
      await sleep(CHAR_MS);
    }

    setSuffixClass(el, toWord);
    cur = '';
    for (var i = 0; i < toWord.length; i++) {
      cur += toWord[i];
      el.textContent = cur;
      await sleep(CHAR_MS);
    }
  }

  function resetBar(fillEl, reverse) {
    if (!fillEl) return;
    fillEl.classList.remove('is-running', 'is-reverse');
    fillEl.style.width = reverse ? '100%' : '0%';
    void fillEl.offsetWidth;
    fillEl.classList.add(reverse ? 'is-reverse' : 'is-running');
  }

  function hideSplash(splash) {
    return new Promise(function (resolve) {
      if (typeof gsap !== 'undefined') {
        gsap.to(splash, {
          yPercent: -100,
          duration: 0.35,
          ease: 'power3.inOut',
          onComplete: function () {
            splash.style.setProperty('display', 'none', 'important');
            gsap.set(splash, { clearProps: 'transform' });
            unlockScroll();
            resolve();
          }
        });
      } else {
        splash.style.setProperty('display', 'none', 'important');
        unlockScroll();
        resolve();
      }
    });
  }

  async function runForward(splash) {
    var suffix = document.getElementById('splash-suffix');
    var fill = document.getElementById('splash-bar-fill');
    var label = document.getElementById('splash-loading-label');
    if (!suffix) return;

    lockScroll();
    if (label) label.textContent = 'LOADING CREATIVE REALM';

    setSuffixClass(suffix, WORD_PROMPT);
    suffix.textContent = WORD_PROMPT;
    resetBar(fill, false);

    await Promise.all([
      morphSuffix(suffix, WORD_PROMPT, WORD_BLOG),
      sleep(LOADING_MS)
    ]);

    await hideSplash(splash);
  }

  function isBlogUrl(url) {
    if (!url) return false;
    try {
      var parsed = new URL(url, window.location.href);
      var path = parsed.pathname.toLowerCase();
      return path.indexOf('blog') !== -1;
    } catch (e) {
      return url.toLowerCase().indexOf('blog') !== -1;
    }
  }

  function checkShouldShowBlogSplash() {
    if (typeof window.__shouldShowBlogSplash === 'boolean') {
      return window.__shouldShowBlogSplash;
    }

    // 1. Never show on page reload or back/forward history navigation
    try {
      var nav = (typeof performance !== 'undefined' && performance.getEntriesByType)
        ? performance.getEntriesByType('navigation')
        : [];
      if (nav && nav.length > 0) {
        var t = nav[0].type;
        if (t === 'reload' || t === 'back_forward') return false;
      } else if (window.performance && window.performance.navigation) {
        var pt = window.performance.navigation.type;
        if (pt === 1 || pt === 2) return false;
      }
    } catch (e) {}

    // 2. Check referrer: if already inside blog section, never show
    var ref = document.referrer || '';
    if (isBlogUrl(ref)) {
      return false;
    }

    // 3. Explicit transition flag set when clicking a blog link from any non-blog page
    try {
      if (sessionStorage.getItem('arigato_enter_blog') === '1') {
        sessionStorage.removeItem('arigato_enter_blog');
        return true;
      }
    } catch (e) {}

    // 4. Same origin referrer from another non-blog section of the site
    if (ref) {
      try {
        var refUrl = new URL(ref, window.location.href);
        if (refUrl.host.toLowerCase() === window.location.host.toLowerCase() && !isBlogUrl(ref)) {
          return true;
        }
      } catch (e) {}
    }

    // Default: do not show
    return false;
  }

  function initBlogSplash() {
    var splash = document.getElementById('blog-splash-screen');
    if (!splash) return;

    // Handle bfcache (browser back/forward cache)
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        splash.style.setProperty('display', 'none', 'important');
        unlockScroll();
      }
    });

    var shouldShow = checkShouldShowBlogSplash();
    window.__shouldShowBlogSplash = shouldShow;

    if (!shouldShow) {
      document.documentElement.classList.add('no-blog-splash');
      splash.style.setProperty('display', 'none', 'important');
      unlockScroll();
      return;
    }

    // Safety timeout in case of animation delay
    var safety = setTimeout(function () {
      splash.style.setProperty('display', 'none', 'important');
      unlockScroll();
    }, LOADING_MS + 1000);

    lockScroll();
    runForward(splash).then(function () {
      clearTimeout(safety);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBlogSplash);
  } else {
    initBlogSplash();
  }
})();
