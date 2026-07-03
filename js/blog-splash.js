(function () {
  'use strict';

  var LOADING_MS = 2500;
  var HOLD_MS = 1000;
  var CHAR_MS = 52;
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
          duration: 0.45,
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

  function dropCurtain(splash) {
    return new Promise(function (resolve) {
      splash.style.display = 'flex';
      if (typeof gsap !== 'undefined') {
        gsap.fromTo(
          splash,
          { yPercent: -100 },
          {
            yPercent: 0,
            duration: 0.35,
            ease: 'power3.out',
            onComplete: resolve
          }
        );
      } else {
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

  async function runReverse(splash, href) {
    var suffix = document.getElementById('splash-suffix');
    var fill = document.getElementById('splash-bar-fill');
    var label = document.getElementById('splash-loading-label');
    if (!suffix) {
      window.location.href = href;
      return;
    }

    lockScroll();
    if (label) label.textContent = 'RETURNING TO PROMPTS';

    setSuffixClass(suffix, WORD_BLOG);
    suffix.textContent = WORD_BLOG;

    await dropCurtain(splash);
    resetBar(fill, true);

    await Promise.all([
      morphSuffix(suffix, WORD_BLOG, WORD_PROMPT),
      sleep(LOADING_MS)
    ]);

    window.location.href = href;
  }

  function isBackLink(href) {
    if (!href || href.charAt(0) === '#') return false;
    return href === 'index.php'
      || href === 'index'
      || href.indexOf('index.php') !== -1
      || href === 'gallery.php'
      || href.indexOf('gallery.php') !== -1
      || href === './'
      || href === '/';
  }

  function initBlogSplash() {
    var splash = document.getElementById('blog-splash-screen');
    if (!splash) return;

    var safety = setTimeout(function () {
      splash.style.setProperty('display', 'none', 'important');
      unlockScroll();
    }, LOADING_MS + 1500);

    function done() {
      clearTimeout(safety);
    }

    var referrer = document.referrer;
    var isFromMainSite = referrer === '' || referrer.indexOf('index') !== -1 || referrer.indexOf('blog') === -1;

    if (isFromMainSite) {
      lockScroll();
      runForward(splash).then(done);
    } else {
      splash.style.setProperty('display', 'none', 'important');
      unlockScroll();
      done();
    }

    document.addEventListener('click', function (e) {
      var link = e.target.closest('a');
      if (!link) return;

      var href = link.getAttribute('href');
      if (!isBackLink(href)) return;

      e.preventDefault();
      runReverse(splash, href);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBlogSplash);
  } else {
    initBlogSplash();
  }
})();
