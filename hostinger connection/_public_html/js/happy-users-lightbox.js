(function () {
  'use strict';

  var masonry = document.querySelector('.hu-masonry');
  var lightbox = document.getElementById('huLightbox');
  if (!masonry || !lightbox) return;

  if (lightbox.parentNode !== document.body) {
    document.body.appendChild(lightbox);
  }

  var pins = Array.prototype.slice.call(masonry.querySelectorAll('.hu-pin'));
  if (!pins.length) return;

  var images = pins.map(function (pin) {
    var img = pin.querySelector('img');
    return img ? img.getAttribute('src') : '';
  }).filter(Boolean);

  var lbImg = lightbox.querySelector('.hu-lb-img');
  var lbPrev = lightbox.querySelector('.hu-lb-prev');
  var lbNext = lightbox.querySelector('.hu-lb-next');
  var lbClose = lightbox.querySelector('.hu-lb-close');
  var lbBackdrop = lightbox.querySelector('.hu-lb-backdrop');
  var lbCounter = lightbox.querySelector('.hu-lb-counter');
  var current = 0;

  var touchStartX = 0;
  var touchStartY = 0;
  var touchActive = false;

  function show(index) {
    if (!images.length) return;
    current = (index + images.length) % images.length;
    lbImg.src = images[current];
    lbImg.alt = 'Happy user screenshot ' + (current + 1);
    if (lbCounter) {
      lbCounter.textContent = (current + 1) + ' / ' + images.length;
    }
    if (lbPrev) lbPrev.disabled = images.length <= 1;
    if (lbNext) lbNext.disabled = images.length <= 1;
  }

  function open(index) {
    show(index);
    lightbox.classList.add('is-open');
    lightbox.setAttribute('aria-hidden', 'false');
    document.body.classList.add('hu-lightbox-open');
  }

  function close() {
    lightbox.classList.remove('is-open');
    lightbox.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('hu-lightbox-open');
    lbImg.removeAttribute('src');
  }

  function prev() {
    if (images.length <= 1) return;
    show(current - 1);
  }

  function next() {
    if (images.length <= 1) return;
    show(current + 1);
  }

  pins.forEach(function (pin, i) {
    pin.setAttribute('role', 'button');
    pin.setAttribute('tabindex', '0');
    pin.setAttribute('aria-label', 'View screenshot ' + (i + 1));
    pin.addEventListener('click', function () { open(i); });
    pin.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        open(i);
      }
    });
  });

  if (lbClose) lbClose.addEventListener('click', close);
  if (lbBackdrop) lbBackdrop.addEventListener('click', close);
  if (lbPrev) lbPrev.addEventListener('click', function (e) { e.stopPropagation(); prev(); });
  if (lbNext) lbNext.addEventListener('click', function (e) { e.stopPropagation(); next(); });

  document.addEventListener('keydown', function (e) {
    if (!lightbox.classList.contains('is-open')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') prev();
    if (e.key === 'ArrowRight') next();
  });

  lightbox.addEventListener('touchstart', function (e) {
    if (!lightbox.classList.contains('is-open') || !e.touches.length) return;
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
    touchActive = true;
  }, { passive: true });

  lightbox.addEventListener('touchmove', function (e) {
    if (!touchActive || !e.touches.length) return;
    var dx = Math.abs(e.touches[0].clientX - touchStartX);
    var dy = Math.abs(e.touches[0].clientY - touchStartY);
    if (dx > dy && dx > 10) {
      e.preventDefault();
    }
  }, { passive: false });

  lightbox.addEventListener('touchend', function (e) {
    if (!touchActive) return;
    touchActive = false;
    var touch = e.changedTouches[0];
    if (!touch) return;
    var dx = touch.clientX - touchStartX;
    var dy = touch.clientY - touchStartY;
    if (Math.abs(dx) < 48 || Math.abs(dx) < Math.abs(dy)) return;
    if (dx < 0) next();
    else prev();
  }, { passive: true });
})();
