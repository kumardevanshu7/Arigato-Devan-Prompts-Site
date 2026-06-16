/* ============================================
   DIGITAL STORE — store.js
   Slider + UI Interactions
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {

  // ============ IMAGE SLIDER ============
  const track     = document.getElementById('sliderTrack');
  const prevBtn   = document.getElementById('sliderPrev');
  const nextBtn   = document.getElementById('sliderNext');
  const dots      = document.querySelectorAll('.slider-dot');

  if (!track) return; // Only run on product page

  let current    = 0;
  let total      = dots.length;
  let isDragging = false;
  let startX     = 0;
  let dragDelta  = 0;
  let autoTimer  = null;

  function goTo(index) {
    if (index < 0)     index = total - 1;
    if (index >= total) index = 0;
    current = index;
    track.style.transform = `translateX(-${current * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === current));
  }

  // Button navigation
  if (prevBtn) prevBtn.addEventListener('click', () => { goTo(current - 1); resetAuto(); });
  if (nextBtn) nextBtn.addEventListener('click', () => { goTo(current + 1); resetAuto(); });

  // Dot navigation
  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => { goTo(i); resetAuto(); });
  });

  // Keyboard navigation
  document.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft')  { goTo(current - 1); resetAuto(); }
    if (e.key === 'ArrowRight') { goTo(current + 1); resetAuto(); }
  });

  // Touch / Swipe support
  track.addEventListener('touchstart', (e) => {
    startX     = e.touches[0].clientX;
    isDragging = true;
  }, { passive: true });

  track.addEventListener('touchmove', (e) => {
    if (!isDragging) return;
    dragDelta = e.touches[0].clientX - startX;
  }, { passive: true });

  track.addEventListener('touchend', () => {
    if (!isDragging) return;
    isDragging = false;
    if (dragDelta < -40)      goTo(current + 1);
    else if (dragDelta > 40)  goTo(current - 1);
    dragDelta = 0;
    resetAuto();
  });

  // Mouse drag support (desktop)
  track.addEventListener('mousedown', (e) => {
    startX = e.clientX;
    isDragging = true;
    track.style.cursor = 'grabbing';
  });

  document.addEventListener('mousemove', (e) => {
    if (!isDragging) return;
    dragDelta = e.clientX - startX;
  });

  document.addEventListener('mouseup', () => {
    if (!isDragging) return;
    isDragging = false;
    track.style.cursor = '';
    if (dragDelta < -40)      goTo(current + 1);
    else if (dragDelta > 40)  goTo(current - 1);
    dragDelta = 0;
    resetAuto();
  });

  // Auto-slide every 4s
  function startAuto() {
    autoTimer = setInterval(() => { goTo(current + 1); }, 4000);
  }

  function resetAuto() {
    clearInterval(autoTimer);
    startAuto();
  }

  startAuto();

  // ============ SCROLL REVEAL ============
  const fadeEls = document.querySelectorAll('.fade-up');
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    fadeEls.forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(24px)';
      el.style.transition = 'opacity 0.6s cubic-bezier(0.4,0,0.2,1), transform 0.6s cubic-bezier(0.4,0,0.2,1)';
      observer.observe(el);
    });
  }

});
