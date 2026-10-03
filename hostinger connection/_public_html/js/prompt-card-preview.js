/**
 * Arigato Devan — 3D Perspective Coverflow Prompt Card Preview Lightbox
 * Silky-smooth 60fps card role rotation engine with generous airy card spacing.
 */
(function () {
  'use strict';

  var modal = null;
  var track = null;
  var slotElements = [];
  var activeSlotIndex = 0; // Index in slotElements (0 to 4) that is currently at center

  var modalTitle = null;
  var modalActionBtn = null;
  var modalPrevBtn = null;
  var modalNextBtn = null;
  var modalFooter = null;

  var currentCards = [];
  var currentCardIndex = -1; // Index in currentCards array
  var isSwitching = false;

  function getVisibleCards() {
    var all = document.querySelectorAll('.prompt-card');
    var list = [];
    for (var i = 0; i < all.length; i++) {
      var c = all[i];
      if (c.classList.contains('skeleton')) continue;
      if (c.style.display === 'none' || c.hidden) continue;
      list.push(c);
    }
    return list;
  }

  function getCardUrl(card) {
    if (!card) return '#';
    if (card.dataset.slug) {
      if (card.dataset.promptType === 'solo') {
        return 'prompts/solo/' + encodeURIComponent(card.dataset.slug);
      }
      return 'prompt.php?slug=' + encodeURIComponent(card.dataset.slug);
    }
    return 'prompt.php?id=' + (card.dataset.id || '');
  }

  function getCardData(card) {
    if (!card) return null;
    var imgSrc = card.dataset.image;
    if (!imgSrc) {
      var img = card.querySelector('.card-image-wrap img');
      if (img) imgSrc = img.getAttribute('src');
    }
    var title = card.dataset.title || '';
    if (!title) {
      var titleEl = card.querySelector('.card-title');
      if (titleEl) title = titleEl.textContent;
    }
    var badgeEl = card.querySelector('.card-badge');
    var badgeText = badgeEl ? badgeEl.textContent.trim() : '';
    var badgeClass = badgeEl ? badgeEl.className : 'card-badge';
    var targetUrl = getCardUrl(card);

    return {
      card: card,
      image: imgSrc || '',
      title: title,
      badgeText: badgeText,
      badgeClass: badgeClass,
      url: targetUrl
    };
  }

  function getSlotClass(offset) {
    // offset: 0=active, 1=next, 2=far-right, 3=far-left, 4=prev
    if (offset === 0) return 'pip-slot pip-slot-active';
    if (offset === 1) return 'pip-slot pip-slot-next';
    if (offset === 2) return 'pip-slot pip-slot-far-right';
    if (offset === 3) return 'pip-slot pip-slot-far-left';
    if (offset === 4) return 'pip-slot pip-slot-prev';
    return 'pip-slot';
  }

  function populateCardElement(el, data) {
    if (!el) return;
    if (!data || !data.image) {
      el.style.display = 'none';
      return;
    }
    el.style.display = '';
    var img = el.querySelector('.pip-card-img');
    if (img) {
      img.src = data.image;
      img.alt = data.title || 'Prompt';
    }
    var badge = el.querySelector('.pip-card-badge');
    if (badge) {
      if (data.badgeText) {
        badge.textContent = data.badgeText;
        badge.className = 'pip-card-badge pip-badge ' + data.badgeClass;
        badge.style.display = '';
      } else {
        badge.style.display = 'none';
      }
    }
  }

  function updateCenterFooter(data) {
    if (!data || !modalFooter) return;
    modalFooter.style.opacity = '0.3';
    modalFooter.style.transform = 'translateX(-50%) translateY(4px)';

    setTimeout(function () {
      if (modalTitle) modalTitle.textContent = data.title;
      if (modalActionBtn) {
        modalActionBtn.href = data.url;
        modalActionBtn.onclick = function (e) {
          e.preventDefault();
          document.body.style.transition = 'opacity 0.15s ease';
          document.body.style.opacity = '0';
          setTimeout(function () { window.location.href = data.url; }, 150);
        };
      }
      modalFooter.style.opacity = '1';
      modalFooter.style.transform = 'translateX(-50%) translateY(0)';
    }, 140);
  }

  function setupAllSlots() {
    var N = currentCards.length;
    if (N === 0) return;

    activeSlotIndex = 0;

    for (var i = 0; i < 5; i++) {
      var el = slotElements[i];
      var offset = (i - activeSlotIndex + 5) % 5;
      el.className = getSlotClass(offset);
      el.style.transition = 'none';

      var promptIdx = 0;
      if (offset === 0) promptIdx = currentCardIndex;
      else if (offset === 1) promptIdx = (currentCardIndex + 1) % N;
      else if (offset === 2) promptIdx = (currentCardIndex + 2) % N;
      else if (offset === 3) promptIdx = (currentCardIndex - 2 + N) % N;
      else if (offset === 4) promptIdx = (currentCardIndex - 1 + N) % N;

      if (N <= 1 && offset !== 0) {
        el.style.display = 'none';
      } else if (N < 4 && (offset === 2 || offset === 3)) {
        el.style.display = 'none';
      } else {
        populateCardElement(el, getCardData(currentCards[promptIdx]));
      }

      void el.offsetWidth; // Reflow
      el.style.transition = '';
    }

    if (N <= 1) {
      if (modalPrevBtn) modalPrevBtn.style.display = 'none';
      if (modalNextBtn) modalNextBtn.style.display = 'none';
    } else {
      if (modalPrevBtn) modalPrevBtn.style.display = 'flex';
      if (modalNextBtn) modalNextBtn.style.display = 'flex';
    }

    updateCenterFooter(getCardData(currentCards[currentCardIndex]));
  }

  function showNextCard() {
    var N = currentCards.length;
    if (isSwitching || N <= 1) return;
    isSwitching = true;

    // Advance current prompt
    currentCardIndex = (currentCardIndex + 1) % N;

    // Advance active slot pointer
    activeSlotIndex = (activeSlotIndex + 1) % 5;

    // The element wrapping around behind the scenes from Far Left to Far Right
    var wrapElemIndex = (activeSlotIndex + 2) % 5;
    var wrapPromptIndex = (currentCardIndex + 2) % N;

    var wrapElem = slotElements[wrapElemIndex];
    if (wrapElem) {
      wrapElem.style.transition = 'none';
      populateCardElement(wrapElem, getCardData(currentCards[wrapPromptIndex]));
      wrapElem.className = getSlotClass(2); // Far Right position
      void wrapElem.offsetWidth; // Force reflow
      wrapElem.style.transition = '';
    }

    // Animate all other 4 card elements to their new slot positions
    for (var i = 0; i < 5; i++) {
      if (i === wrapElemIndex) continue;
      var offset = (i - activeSlotIndex + 5) % 5;
      slotElements[i].className = getSlotClass(offset);
    }

    updateCenterFooter(getCardData(currentCards[currentCardIndex]));

    setTimeout(function () {
      isSwitching = false;
    }, 420);
  }

  function showPrevCard() {
    var N = currentCards.length;
    if (isSwitching || N <= 1) return;
    isSwitching = true;

    // Retreat current prompt
    currentCardIndex = (currentCardIndex - 1 + N) % N;

    // Retreat active slot pointer
    activeSlotIndex = (activeSlotIndex - 1 + 5) % 5;

    // The element wrapping around from Far Right to Far Left
    var wrapElemIndex = (activeSlotIndex - 2 + 5) % 5;
    var wrapPromptIndex = (currentCardIndex - 2 + N) % N;

    var wrapElem = slotElements[wrapElemIndex];
    if (wrapElem) {
      wrapElem.style.transition = 'none';
      populateCardElement(wrapElem, getCardData(currentCards[wrapPromptIndex]));
      wrapElem.className = getSlotClass(3); // Far Left position
      void wrapElem.offsetWidth; // Force reflow
      wrapElem.style.transition = '';
    }

    // Animate all other 4 card elements to their new slot positions
    for (var i = 0; i < 5; i++) {
      if (i === wrapElemIndex) continue;
      var offset = (i - activeSlotIndex + 5) % 5;
      slotElements[i].className = getSlotClass(offset);
    }

    updateCenterFooter(getCardData(currentCards[currentCardIndex]));

    setTimeout(function () {
      isSwitching = false;
    }, 420);
  }

  function createPreviewModal() {
    if (document.getElementById('prompt-preview-modal')) {
      modal = document.getElementById('prompt-preview-modal');
      track = modal.querySelector('.pip-carousel-track');
      slotElements = Array.prototype.slice.call(modal.querySelectorAll('.pip-slot'));
      modalTitle = modal.querySelector('.pip-title');
      modalActionBtn = modal.querySelector('.pip-action-btn');
      modalPrevBtn = modal.querySelector('.pip-prev');
      modalNextBtn = modal.querySelector('.pip-next');
      modalFooter = modal.querySelector('.pip-footer');
      return;
    }

    modal = document.createElement('div');
    modal.id = 'prompt-preview-modal';
    modal.className = 'pip-modal';
    modal.setAttribute('aria-hidden', 'true');

    var slotsHtml = '';
    for (var i = 0; i < 5; i++) {
      slotsHtml += [
        '<div class="pip-slot" data-slot-id="' + i + '">',
        '  <div class="pip-card-frame">',
        '    <img class="pip-card-img" src="" alt="">',
        '    <span class="pip-card-badge"></span>',
        '    <button type="button" class="pip-close-btn" aria-label="Close preview">',
        '      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
        '    </button>',
        '    <div class="pip-card-overlay"></div>',
        '  </div>',
        '</div>'
      ].join('');
    }

    modal.innerHTML = [
      '<div class="pip-backdrop"></div>',
      '<div class="pip-stage">',
      '  <button type="button" class="pip-nav-btn pip-prev" aria-label="Previous prompt">',
      '    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">',
      '      <polyline points="15 18 9 12 15 6"></polyline>',
      '    </svg>',
      '  </button>',
      '  <button type="button" class="pip-nav-btn pip-next" aria-label="Next prompt">',
      '    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">',
      '      <polyline points="9 18 15 12 9 6"></polyline>',
      '    </svg>',
      '  </button>',
      '  <div class="pip-carousel-track">' + slotsHtml + '</div>',
      '  <div class="pip-footer">',
      '    <div class="pip-info">',
      '      <h3 class="pip-title"></h3>',
      '    </div>',
      '    <a href="#" class="pip-action-btn">',
      '      <span>View Prompt</span>',
      '      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>',
      '    </a>',
      '  </div>',
      '</div>'
    ].join('');

    document.body.appendChild(modal);

    track = modal.querySelector('.pip-carousel-track');
    slotElements = Array.prototype.slice.call(modal.querySelectorAll('.pip-slot'));
    modalTitle = modal.querySelector('.pip-title');
    modalActionBtn = modal.querySelector('.pip-action-btn');
    modalPrevBtn = modal.querySelector('.pip-prev');
    modalNextBtn = modal.querySelector('.pip-next');
    modalFooter = modal.querySelector('.pip-footer');

    // Close on backdrop click
    modal.querySelector('.pip-backdrop').addEventListener('click', closePreview);

    // Prev / Next button clicks
    modalPrevBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      showPrevCard();
    });
    modalNextBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      showNextCard();
    });

    // Delegated click handler on card slots
    slotElements.forEach(function (slotEl, idx) {
      slotEl.addEventListener('click', function (e) {
        if (e.target.closest('.pip-close-btn')) {
          e.stopPropagation();
          closePreview();
          return;
        }

        var offset = (idx - activeSlotIndex + 5) % 5;
        if (offset === 1) {
          e.stopPropagation();
          showNextCard();
        } else if (offset === 4) {
          e.stopPropagation();
          showPrevCard();
        } else if (offset === 2) {
          e.stopPropagation();
          showNextCard();
        } else if (offset === 3) {
          e.stopPropagation();
          showPrevCard();
        }
      });
    });

    // Keyboard navigation
    document.addEventListener('keydown', function (e) {
      if (!modal || !modal.classList.contains('is-open')) return;
      if (e.key === 'Escape') {
        closePreview();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        showNextCard();
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        showPrevCard();
      }
    });

    // Touch Swipe gestures (Mobile)
    var touchStartX = 0;
    var touchEndX = 0;
    var touchStartY = 0;
    var touchEndY = 0;

    modal.addEventListener('touchstart', function (e) {
      if (!modal.classList.contains('is-open') || !e.changedTouches || !e.changedTouches.length) return;
      touchStartX = e.changedTouches[0].clientX;
      touchStartY = e.changedTouches[0].clientY;
    }, { passive: true });

    modal.addEventListener('touchend', function (e) {
      if (!modal.classList.contains('is-open') || !e.changedTouches || !e.changedTouches.length) return;
      touchEndX = e.changedTouches[0].clientX;
      touchEndY = e.changedTouches[0].clientY;
      var diffX = touchEndX - touchStartX;
      var diffY = touchEndY - touchStartY;
      if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY)) {
        if (diffX < 0) {
          showNextCard();
        } else {
          showPrevCard();
        }
      }
    }, { passive: true });
  }

  function openPreview(card) {
    if (!card) return;
    createPreviewModal();

    currentCards = getVisibleCards();
    currentCardIndex = currentCards.indexOf(card);
    if (currentCardIndex === -1) {
      currentCards.push(card);
      currentCardIndex = currentCards.length - 1;
    }

    setupAllSlots();

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('pip-modal-open');
  }

  function closePreview() {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('pip-modal-open');
  }

  window.openPromptImagePreview = openPreview;
  window.closePromptImagePreview = closePreview;

  // Global delegated click handler (Capturing phase)
  document.addEventListener('click', function (e) {
    // 1. Like button click -> let like logic handle it
    if (e.target.closest('.card-like-display') || e.target.closest('.like-btn')) {
      return;
    }

    // 2. Click on Prompt CTA button -> Navigate to prompt page
    var promptBtn = e.target.closest('.card-prompt-btn');
    if (promptBtn) {
      var card = promptBtn.closest('.prompt-card');
      if (card) {
        e.preventDefault();
        e.stopPropagation();
        var url = getCardUrl(card);
        document.body.style.transition = 'opacity 0.15s ease';
        document.body.style.opacity = '0';
        setTimeout(function () { window.location.href = url; }, 150);
      }
      return;
    }

    // 3. Click anywhere else on prompt-card -> Open Image Preview Modal
    var promptCard = e.target.closest('.prompt-card');
    if (promptCard) {
      if (window.isSwiping) return;
      e.preventDefault();
      e.stopPropagation();
      openPreview(promptCard);
      return;
    }
  }, true);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createPreviewModal);
  } else {
    createPreviewModal();
  }
})();
