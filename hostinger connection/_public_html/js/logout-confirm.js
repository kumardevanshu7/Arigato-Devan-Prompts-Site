(function () {
  'use strict';

  var overlay = document.getElementById('logoutConfirmOverlay');
  if (!overlay) return;

  var closeBtn = document.getElementById('logoutConfirmClose');
  var stayBtn = document.getElementById('logoutConfirmStay');
  var goLink = document.getElementById('logoutConfirmGo');
  var pendingHref = 'login.php?logout=1';

  function openModal(href) {
    pendingHref = href || 'login.php?logout=1';
    if (goLink) goLink.setAttribute('href', pendingHref);
    overlay.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-open');
    document.body.classList.add('logout-confirm-open');
    if (stayBtn) stayBtn.focus();
  }

  function closeModal() {
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('logout-confirm-open');
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[href*="logout=1"]');
    if (!link || link.id === 'logoutConfirmGo') return;
    e.preventDefault();
    openModal(link.getAttribute('href') || 'login.php?logout=1');
  });

  if (stayBtn) stayBtn.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeModal();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
      closeModal();
    }
  });
})();
