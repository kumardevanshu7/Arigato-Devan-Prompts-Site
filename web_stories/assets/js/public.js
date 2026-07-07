(function() {
  var grid = document.getElementById('ws-grid');
  var toolbar = document.getElementById('ws-view-toolbar');
  if (!grid || !toolbar) return;

  var storageKey = 'ws_grid_cols';
  var desktopMin = 900;
  var allowed = [3, 4, 5, 6, 7];
  var defaultCols = 5;
  var buttons = Array.prototype.slice.call(toolbar.querySelectorAll('.ws-view-btn'));

  function isDesktop() {
    return window.matchMedia('(min-width: ' + desktopMin + 'px)').matches;
  }

  function normalizeCols(n) {
    n = parseInt(n, 10);
    return allowed.indexOf(n) !== -1 ? n : defaultCols;
  }

  function setActive(cols) {
    cols = normalizeCols(cols);
    grid.setAttribute('data-cols', String(cols));
    buttons.forEach(function(btn) {
      var on = parseInt(btn.getAttribute('data-cols'), 10) === cols;
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  function applyView() {
    if (!isDesktop()) {
      grid.setAttribute('data-cols', '2');
      return;
    }
    var saved = localStorage.getItem(storageKey);
    setActive(saved ? normalizeCols(saved) : defaultCols);
  }

  buttons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!isDesktop()) return;
      var cols = normalizeCols(btn.getAttribute('data-cols'));
      localStorage.setItem(storageKey, String(cols));
      setActive(cols);
    });
  });

  var mq = window.matchMedia('(min-width: ' + desktopMin + 'px)');
  if (mq.addEventListener) {
    mq.addEventListener('change', applyView);
  } else if (mq.addListener) {
    mq.addListener(applyView);
  }

  applyView();
})();
