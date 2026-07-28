(function () {
  'use strict';

  var grid = document.getElementById('card-stack');
  var tagInner = document.getElementById('tag-scroll-inner');
  if (!grid || !tagInner) return;

  var countBadge = document.getElementById('gallery-count-badge');
  var noResults = document.getElementById('gallery-no-results');
  var pagination = document.querySelector('.gal-pagination');
  var activeTag = (new URLSearchParams(window.location.search).get('tag') || 'all').toLowerCase();
  var loading = false;

  function promptPageUrl(card) {
    if (card.dataset.slug) {
      if (card.dataset.promptType === 'solo') {
        return 'prompts/solo/' + encodeURIComponent(card.dataset.slug);
      }
      return 'prompt.php?slug=' + encodeURIComponent(card.dataset.slug);
    }
    return 'prompt.php?id=' + card.dataset.id;
  }

  function bindGalleryCardClicks(root) {
    (root || grid).querySelectorAll('.prompt-card').forEach(function (card) {
      if (card.dataset.galBound === '1') return;
      card.dataset.galBound = '1';
      card.addEventListener('click', function (e) {
        if (e.target.closest('.card-like-display')) return;
        e.preventDefault();
        var url = promptPageUrl(card);
        document.body.style.transition = 'opacity 0.15s ease';
        document.body.style.opacity = '0';
        setTimeout(function () { window.location.href = url; }, 150);
      });
      card.addEventListener('mouseenter', function () {
        var url = promptPageUrl(card);
        if (!document.querySelector('link[rel="prefetch"][href="' + url + '"]')) {
          var l = document.createElement('link');
          l.rel = 'prefetch';
          l.href = url;
          document.head.appendChild(l);
        }
      }, { once: true });
    });
  }

  function getCards() {
    return Array.from(grid.querySelectorAll('.prompt-card'));
  }

  function setActiveTagBtn(tag) {
    tagInner.querySelectorAll('.tag-filter-btn').forEach(function (btn) {
      var btnTag = (btn.dataset.tag || 'all').toLowerCase();
      btn.classList.toggle('active', btnTag === tag);
    });
  }

  function updateUrl(tag) {
    var url = new URL(window.location.href);
    if (!tag || tag === 'all') {
      url.searchParams.delete('tag');
    } else {
      url.searchParams.set('tag', tag);
    }
    url.searchParams.delete('page');
    window.history.replaceState({ galTag: tag }, '', url.pathname + url.search + url.hash);
  }

  function togglePagination(show) {
    if (pagination) {
      pagination.style.display = show ? '' : 'none';
    }
  }

  function showEmptyState(show) {
    if (noResults) {
      noResults.classList.toggle('show', show);
    }
  }

  function afterCardsUpdate(total, empty) {
    bindGalleryCardClicks(grid);
    if (countBadge) countBadge.textContent = total;
    showEmptyState(!!empty);
    if (window.gallerySearchRefresh) {
      window.gallerySearchRefresh();
    }
    if (typeof window.revealCardSkeletons === 'function') {
      window.revealCardSkeletons(grid);
    }
  }

  function loadTag(tag) {
    if (loading) return;
    tag = (tag || 'all').toLowerCase();
    activeTag = tag;
    loading = true;
    grid.classList.add('is-loading');
    setActiveTagBtn(tag);
    updateUrl(tag);
    togglePagination(tag === 'all');

    var url = 'gallery.php?ajax=cards&tag=' + encodeURIComponent(tag);
    fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) throw new Error('filter failed');
        grid.innerHTML = data.html || '';
        if (!data.html && data.empty) {
          grid.innerHTML = '<p class="grid-empty-msg" style="grid-column:1/-1;text-align:center;padding:32px;color:var(--pal-teal,#567c8d);">No prompts for this tag yet.</p>';
        }
        afterCardsUpdate(data.total, data.empty);
        var section = document.getElementById('card-stack');
        if (section) {
          section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      })
      .catch(function () {
        window.location.href = tag === 'all' ? 'gallery.php' : 'gallery.php?tag=' + encodeURIComponent(tag);
      })
      .finally(function () {
        loading = false;
        grid.classList.remove('is-loading');
      });
  }

  tagInner.addEventListener('click', function (e) {
    var btn = e.target.closest('.tag-filter-btn');
    if (!btn || loading) return;
    e.preventDefault();
    var tag = (btn.dataset.tag || 'all').toLowerCase();
    if (tag === activeTag) return;
    loadTag(tag);
  });

  window.addEventListener('popstate', function () {
    var tag = (new URLSearchParams(window.location.search).get('tag') || 'all').toLowerCase();
    if (tag !== activeTag) {
      loadTag(tag);
    }
  });

  window.galleryGetActiveTag = function () { return activeTag; };
  window.galleryGetCards = getCards;
  window.galleryBindCardClicks = function () { bindGalleryCardClicks(grid); };

  bindGalleryCardClicks(grid);
  togglePagination(activeTag === 'all' && !!pagination);
})();
