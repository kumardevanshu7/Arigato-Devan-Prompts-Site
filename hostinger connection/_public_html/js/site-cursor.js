(function () {
  'use strict';

  if ('ontouchstart' in window) return;
  if (window.matchMedia('(max-width: 768px), (hover: none), (pointer: coarse)').matches) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var HOTSPOT_X = 5;
  var HOTSPOT_Y = 4;

  var root = document.createElement('div');
  root.id = 'cc-cursor';
  root.setAttribute('aria-hidden', 'true');

  var arrow = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  arrow.id = 'cc-arrow';
  arrow.setAttribute('viewBox', '0 0 30 30');
  arrow.setAttribute('width', '30');
  arrow.setAttribute('height', '30');
  arrow.innerHTML =
    '<path class="cc-fill" d="M5 4 L5 22.5 L10.4 16.8 L14.2 26.2 L17.2 24.6 L13.1 15.2 L22.5 15.2 Z"/>' +
    '<path class="cc-stroke" d="M5 4 L5 22.5 L10.4 16.8 L14.2 26.2 L17.2 24.6 L13.1 15.2 L22.5 15.2 Z"/>';

  root.appendChild(arrow);

  var visible = false;

  function canUse() {
    var body = document.body;
    if (!body) return false;
    if (body.classList.contains('no-site-cursor') || body.classList.contains('preel-feed-page')) {
      return false;
    }
    return true;
  }

  function place(x, y) {
    arrow.style.left = x - HOTSPOT_X + 'px';
    arrow.style.top = y - HOTSPOT_Y + 'px';
  }

  function mount() {
    if (!canUse() || root.parentNode) return;
    document.body.appendChild(root);
    document.documentElement.classList.add('cc-cursor-on');
  }

  function unmount() {
    document.documentElement.classList.remove('cc-cursor-on');
    document.body.classList.remove('cc-hover', 'cc-active', 'cc-hidden');
    if (root.parentNode) root.parentNode.removeChild(root);
  }

  function onMove(e) {
    if (!canUse()) {
      unmount();
      return;
    }
    mount();
    place(e.clientX, e.clientY);
    if (!visible) {
      document.body.classList.remove('cc-hidden');
      visible = true;
    }
  }

  function onOver(e) {
    if (!canUse()) return;
    var el = e.target.closest(
      'a,button,[role="button"],[onclick],input,textarea,select,label,[tabindex]:not([tabindex="-1"])'
    );
    document.body.classList.toggle('cc-hover', !!el);
  }

  document.addEventListener('mousemove', onMove, { passive: true });
  document.addEventListener('mouseover', onOver, { passive: true });
  document.addEventListener('mousedown', function () {
    if (canUse()) document.body.classList.add('cc-active');
  });
  document.addEventListener('mouseup', function () {
    document.body.classList.remove('cc-active');
  });
  document.addEventListener('mouseleave', function () {
    document.body.classList.add('cc-hidden');
    visible = false;
  });
  document.addEventListener('mouseenter', function () {
    document.body.classList.remove('cc-hidden');
  });

  document.addEventListener('DOMContentLoaded', function () {
    if (!canUse()) return;
    mount();
    place(-100, -100);
  });
})();
