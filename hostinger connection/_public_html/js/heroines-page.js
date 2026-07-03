(function () {
  var btn = document.getElementById('heroesRevealBtn');
  var panel = document.getElementById('heroinesCardsPanel');
  if (!btn || !panel) return;

  function revealCards(scroll) {
    document.body.classList.add('heroes-revealed');
    panel.removeAttribute('hidden');
    panel.setAttribute('aria-hidden', 'false');
    btn.setAttribute('aria-expanded', 'true');
    if (scroll !== false) {
      requestAnimationFrame(function () {
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }
  }

  btn.addEventListener('click', function () {
    revealCards(true);
    if (history.replaceState) {
      history.replaceState(null, '', '#heroines-cards');
    }
  });

  if (window.location.hash === '#heroines-cards' || /[?&]view=cards/.test(window.location.search)) {
    revealCards(false);
  }
})();
