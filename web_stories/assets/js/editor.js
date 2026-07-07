/**
 * Web Stories editor — live preview state (phone + thumbnails).
 */
(function() {
  var form = document.getElementById('ws-editor-form');
  if (!form) return;

  var slidePanels = document.getElementById('ws-slide-panels');
  var slidesRail = document.getElementById('ws-slides-wrap');
  var thumbsList = document.getElementById('ws-thumbs-list');
  if (!slidePanels || !slidesRail || !thumbsList) return;

  var phoneBg = document.getElementById('ws-phone-bg');
  var phoneTitle = document.getElementById('ws-phone-title');
  var phoneBody = document.getElementById('ws-phone-body');
  var phoneCta = document.getElementById('ws-phone-cta');
  if (phoneCta) {
    phoneCta.addEventListener('click', function() {
      var url = phoneCta.getAttribute('data-href');
      if (url) window.open(url, '_blank', 'noopener,noreferrer');
    });
  }
  var phoneSafe = document.getElementById('ws-phone-safe');
  var pageLabel = document.getElementById('ws-page-label');
  var pageCountBadge = document.getElementById('ws-page-count');
  var deleteSlideBtn = document.getElementById('ws-delete-slide');
  var phoneGrad = document.getElementById('ws-phone-grad');
  var phoneLogoTop = document.getElementById('ws-phone-logo-top');
  var phoneLogoBottom = document.getElementById('ws-phone-logo-bottom');
  var editorData = document.getElementById('ws-editor-data');
  var pubLogo = editorData ? editorData.getAttribute('data-pub-logo') || '' : '';
  var pubName = editorData ? editorData.getAttribute('data-pub-name') || '' : '';
  var fontPresets = {};
  try {
    fontPresets = JSON.parse(editorData ? editorData.getAttribute('data-font-presets') || '{}' : '{}');
  } catch (e) { fontPresets = {}; }

  var activeIdx = 0;
  var pendingBg = {};
  var pendingPoster = null;

  function q(sel, root) {
    return (root || document).querySelector(sel);
  }
  function qa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  var modalEl = document.getElementById('ws-modal');
  var modalTitle = document.getElementById('ws-modal-title');
  var modalMsg = document.getElementById('ws-modal-msg');
  var modalIcon = document.getElementById('ws-modal-icon');
  var modalActions = document.getElementById('ws-modal-actions');
  var modalResolve = null;

  function closeModal(result) {
    if (!modalEl) return;
    modalEl.hidden = true;
    document.body.style.overflow = '';
    if (modalResolve) {
      var fn = modalResolve;
      modalResolve = null;
      fn(result);
    }
  }

  function openModal(opts) {
    opts = opts || {};
    if (!modalEl || !modalActions) return Promise.resolve(false);
    return new Promise(function(resolve) {
      modalResolve = resolve;
      if (modalTitle) modalTitle.textContent = opts.title || 'Notice';
      if (modalMsg) modalMsg.textContent = opts.message || '';
      if (modalIcon) {
        var isDanger = !!opts.danger;
        modalIcon.className = 'ws-modal-icon ' + (isDanger ? 'danger' : 'info');
        modalIcon.innerHTML = isDanger
          ? '<i class="fa-solid fa-trash-can"></i>'
          : '<i class="fa-solid fa-circle-info"></i>';
      }
      modalActions.innerHTML = '';
      if (opts.type === 'confirm') {
        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'ws-btn ghost';
        cancelBtn.textContent = opts.cancelLabel || 'Cancel';
        cancelBtn.addEventListener('click', function() { closeModal(false); });
        var okBtn = document.createElement('button');
        okBtn.type = 'button';
        okBtn.className = 'ws-btn' + (opts.danger ? ' danger' : ' primary');
        okBtn.textContent = opts.confirmLabel || 'OK';
        okBtn.addEventListener('click', function() { closeModal(true); });
        modalActions.appendChild(cancelBtn);
        modalActions.appendChild(okBtn);
      } else {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ws-btn primary';
        btn.textContent = opts.confirmLabel || 'OK';
        btn.addEventListener('click', function() { closeModal(true); });
        modalActions.appendChild(btn);
      }
      modalEl.hidden = false;
      document.body.style.overflow = 'hidden';
      var focusBtn = q('.ws-btn', modalActions);
      if (focusBtn) focusBtn.focus();
    });
  }

  function wsAlert(message, title) {
    return openModal({ type: 'alert', title: title || 'Notice', message: message });
  }

  function wsConfirm(message, title, danger) {
    return openModal({
      type: 'confirm',
      title: title || 'Confirm',
      message: message,
      danger: !!danger,
      confirmLabel: danger ? 'Delete' : 'OK',
      cancelLabel: 'Cancel'
    });
  }

  if (modalEl) {
    modalEl.addEventListener('click', function(e) {
      if (e.target === modalEl) closeModal(false);
    });
    document.addEventListener('keydown', function(e) {
      if (modalEl.hidden) return;
      if (e.key === 'Escape') closeModal(false);
    });
  }

  var animClasses = ['ws-anim-fade-in', 'ws-anim-fly-in-bottom', 'ws-anim-zoom-in', 'ws-anim-drop'];

  function applyAnim(el, anim) {
    if (!el) return;
    animClasses.forEach(function(c) { el.classList.remove(c); });
    if (!anim) return;
    var safe = String(anim).replace(/[^a-z0-9-]/g, '');
    if (!safe) safe = 'fade-in';
    void el.offsetWidth;
    el.classList.add('ws-anim-' + safe);
  }

  function getSlideBlock(i) {
    return slidePanels.querySelector('.ws-slide-data[data-index="' + i + '"]');
  }

  function slideCount() {
    return slidePanels.querySelectorAll('.ws-slide-data').length;
  }

  function updatePageMeta() {
    var n = slideCount();
    if (pageCountBadge) pageCountBadge.textContent = String(n);
    if (pageLabel) pageLabel.textContent = 'PAGE ' + (activeIdx + 1) + ' OF ' + n;
    if (deleteSlideBtn) deleteSlideBtn.disabled = n <= 1;
  }

  function reindexSlides() {
    var items = qa('.ws-thumb-item', thumbsList);
    var panels = qa('.ws-slide-data', slidePanels);
    var oldPending = Object.assign({}, pendingBg);
    pendingBg = {};

    items.forEach(function(item, i) {
      item.setAttribute('data-idx', i);
      var thumb = q('.ws-slide-thumb', item);
      if (thumb) thumb.setAttribute('data-idx', i);
      var num = q('.num', item);
      if (num) num.textContent = String(i + 1);

      var panel = panels[i];
      if (!panel) return;
      var oldIdx = parseInt(panel.getAttribute('data-index'), 10);
      panel.setAttribute('data-index', i);
      panel.setAttribute('data-idx', i);
      if (oldPending[oldIdx]) pendingBg[i] = oldPending[oldIdx];

      qa('[name]', panel).forEach(function(el) {
        var n = el.name;
        if (!n) return;
        if (n.indexOf('slides[') === 0) {
          el.name = n.replace(/slides\[\d+\]/, 'slides[' + i + ']');
        } else if (n.indexOf('slide_bg_') === 0) {
          el.name = 'slide_bg_' + i;
        }
      });
    });

    if (activeIdx >= items.length) activeIdx = Math.max(0, items.length - 1);
    updatePageMeta();
  }

  function selectSlide(i) {
    activeIdx = i;
    qa('.ws-tab').forEach(function(t) {
      t.classList.toggle('active', t.getAttribute('data-tab') === 'slide');
    });
    qa('.ws-thumb-item', thumbsList).forEach(function(item) {
      var idx = parseInt(item.getAttribute('data-idx'), 10) || 0;
      var thumb = q('.ws-slide-thumb', item);
      if (thumb) thumb.classList.toggle('active', idx === activeIdx);
    });
    qa('.ws-props .ws-panel').forEach(function(p) {
      if (p.classList.contains('ws-panel-slide')) {
        p.classList.toggle('active', parseInt(p.getAttribute('data-idx'), 10) === activeIdx);
      } else {
        p.classList.remove('active');
      }
    });
    syncPreview();
  }

  function deleteSlide(idx) {
    if (slideCount() <= 1) {
      wsAlert('You need at least one page in your story.', 'Cannot delete');
      return;
    }
    wsConfirm(
      'Page ' + (idx + 1) + ' will be removed. Save the story to keep this change.',
      'Delete this page?',
      true
    ).then(function(ok) {
      if (!ok) return;

      var items = qa('.ws-thumb-item', thumbsList);
      var panels = qa('.ws-slide-data', slidePanels);
      if (items[idx]) items[idx].remove();
      if (panels[idx]) panels[idx].remove();
      delete pendingBg[idx];

      if (activeIdx >= slideCount()) activeIdx = slideCount() - 1;
      reindexSlides();
      selectSlide(activeIdx);
    });
  }

  function addSlide() {
    var count = slideCount();
    if (count >= 30) {
      wsAlert('Google Web Stories allow a maximum of 30 pages per story.', 'Page limit');
      return;
    }
    var i = count;
    var thumbTpl = document.getElementById('ws-thumb-template');
    if (thumbTpl) {
      var wrap = document.createElement('div');
      wrap.innerHTML = thumbTpl.innerHTML.replace(/__IDX__/g, i).replace(/__NUM__/g, i + 1);
      thumbsList.appendChild(wrap.firstElementChild);
    }

    var panel = document.createElement('div');
    panel.className = 'ws-panel ws-panel-slide ws-slide-data';
    panel.setAttribute('data-tab', 'slide');
    panel.setAttribute('data-index', i);
    panel.setAttribute('data-idx', i);
    var designTpl = document.getElementById('ws-design-template');
    var mainHtml = document.getElementById('ws-slide-template').innerHTML.replace(/__IDX__/g, String(i));
    var designHtml = designTpl ? designTpl.innerHTML.replace(/__IDX__/g, String(i)) : '';
    panel.innerHTML = mainHtml.replace('<!--ws-design-->', designHtml);
    slidePanels.appendChild(panel);
    bindSlide(panel);

    selectSlide(i);
    updatePageMeta();
    thumbsList.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function hexToRgb(hex) {
    hex = (hex || '#000000').replace('#', '');
    if (hex.length === 3) hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    return [parseInt(hex.substr(0, 2), 16), parseInt(hex.substr(2, 2), 16), parseInt(hex.substr(4, 2), 16)];
  }

  function gradCss(opacity, color) {
    if (!opacity || opacity <= 0) return 'none';
    var rgb = hexToRgb(color);
    var a = opacity / 100;
    var mid = (opacity * 0.45) / 100;
    return 'linear-gradient(to top, rgba(' + rgb.join(',') + ',' + a + ') 0%, rgba(' + rgb.join(',') + ',' + mid + ') 38%, transparent 72%)';
  }

  function paintLogo(show, style, position) {
    if (phoneLogoTop) {
      phoneLogoTop.hidden = true;
      phoneLogoTop.innerHTML = '';
    }
    if (!phoneLogoBottom) return;
    if (!show || !pubLogo) {
      phoneLogoBottom.hidden = true;
      phoneLogoBottom.innerHTML = '';
      return;
    }
    phoneLogoBottom.className = 'ws-phone-logo style-' + style + ' pos-' + position;
    var imgHtml = '<img src="' + pubLogo + '" alt="">';
    if (style === 'ring') {
      phoneLogoBottom.innerHTML = '<div class="ws-prev-logo-ring">' + imgHtml + '</div><span>' + pubName + '</span>';
    } else {
      phoneLogoBottom.innerHTML = imgHtml + '<span>' + pubName + '</span>';
    }
    phoneLogoBottom.hidden = false;
  }

  function applyFontClass(fontKey) {
    if (!phoneSafe) return;
    Object.keys(fontPresets).forEach(function(k) {
      phoneSafe.classList.remove('ws-font-' + k);
    });
    phoneSafe.classList.add('ws-font-' + (fontPresets[fontKey] ? fontKey : 'editorial'));
  }

  function assetUrl(path) {
    if (!path) return '';
    if (path.indexOf('data:') === 0 || path.indexOf('http') === 0 || path.indexOf('blob:') === 0) {
      return path;
    }
    return '../../' + path.replace(/^\//, '');
  }

  function getBgUrl(i) {
    if (pendingBg[i]) return pendingBg[i];
    var block = getSlideBlock(i);
    if (!block) return '';
    var hidden = q('input[name*="[existing_bg]"]', block);
    return hidden && hidden.value ? assetUrl(hidden.value) : '';
  }

  function getColor(i) {
    var block = getSlideBlock(i);
    if (!block) return '#2F4156';
    var picker = q('input[name*="[bg_color]"]', block);
    return (picker && picker.value) || '#2F4156';
  }

  function paintSurface(el, i) {
    if (!el) return;
    var url = getBgUrl(i);
    var color = getColor(i);
    if (url) {
      el.style.backgroundImage = 'url(' + url + ')';
      el.style.backgroundSize = 'cover';
      el.style.backgroundPosition = 'center';
      el.style.backgroundRepeat = 'no-repeat';
      el.style.backgroundColor = color;
    } else {
      el.style.backgroundImage = 'none';
      el.style.backgroundColor = color;
    }
  }

  function setUploadZoneState(zone, url, fileName) {
    if (!zone) return;
    var empty = q('.ws-upload-empty', zone);
    var filled = q('.ws-upload-filled', zone);
    var preview = q('.ws-local-img-preview', zone);
    var fname = q('.ws-upload-fname', zone);
    if (url) {
      if (empty) empty.hidden = true;
      if (filled) filled.hidden = false;
      if (preview) preview.src = url;
      if (fname && fileName) fname.textContent = fileName;
    } else {
      if (empty) empty.hidden = false;
      if (filled) filled.hidden = true;
      if (preview) preview.removeAttribute('src');
      if (fname) fname.textContent = '';
    }
  }

  function initUploadZone(zone, callbacks) {
    var input = q('.ws-upload-input', zone);
    if (!input || zone._wsBound) return;
    zone._wsBound = true;
    callbacks = callbacks || {};

    zone.addEventListener('click', function(e) {
      if (e.target.closest('.ws-upload-btn') || e.target.closest('.ws-upload-empty')) {
        e.preventDefault();
        input.click();
      }
    });

    input.addEventListener('change', function() {
      if (!input.files || !input.files[0]) return;
      var file = input.files[0];
      if (!file.type.match(/^image\//)) {
        wsAlert('Please choose a JPG, PNG, WebP, or GIF image.', 'Invalid file');
        input.value = '';
        return;
      }
      var reader = new FileReader();
      reader.onload = function(ev) {
        var dataUrl = ev.target.result;
        setUploadZoneState(zone, dataUrl, file.name);
        if (callbacks.onPick) callbacks.onPick(file, dataUrl);
      };
      reader.readAsDataURL(file);
    });
  }

  function syncThumbs() {
    qa('.ws-thumb-item', thumbsList).forEach(function(item) {
      var i = parseInt(item.getAttribute('data-idx'), 10) || 0;
      var th = q('.ws-slide-thumb', item);
      if (!th) return;
      th.classList.toggle('active', i === activeIdx);
      var fill = q('.fill', th);
      if (!fill) return;
      paintSurface(fill, i);
      var block = getSlideBlock(i);
      var title = block ? (q('input[name*="[title]"]', block).value || '') : '';
      var label = q('.fill-label', fill);
      if (!label) {
        label = document.createElement('span');
        label.className = 'fill-label';
        fill.appendChild(label);
      }
      label.textContent = title ? title.substring(0, 36) : ('Page ' + (i + 1));
      fill.classList.toggle('has-image', !!getBgUrl(i));
    });
    updatePageMeta();
  }

  function syncPreview() {
    var block = getSlideBlock(activeIdx);
    if (!block) return;

    paintSurface(phoneBg, activeIdx);

    var title = q('input[name*="[title]"]', block).value || '';
    var body = q('textarea[name*="[body_text]"]', block).value || '';
    var align = q('select[name*="[text_align]"]', block).value || 'left';
    var anim = q('select[name*="[animate_in]"]', block);
    var animVal = (anim && anim.value) || 'fade-in';
    var ctaL = q('input[name*="[cta_label]"]', block).value || '';
    var ctaU = q('input[name*="[cta_url]"]', block).value || '';

    phoneTitle.textContent = title;
    phoneTitle.style.display = title ? 'block' : 'none';
    phoneBody.textContent = body;
    phoneBody.style.display = body ? 'block' : 'none';
    var valignEl = q('select[name*="[text_valign]"]', block);
    var valignVal = (valignEl && valignEl.value) || 'bottom';
    var showLogoEl = q('input[name*="[show_logo]"]', block);
    var logoStyleEl = q('select[name*="[logo_style]"]', block);
    var logoPosEl = q('select[name*="[logo_position]"]', block);
    var logoPosVal = (logoPosEl && logoPosEl.value) || 'bottom-center';
    var showLogoOn = !!(showLogoEl && showLogoEl.checked);

    var safeCls = 'ws-phone-safe';
    if (align === 'center') safeCls += ' center';
    else if (align === 'right') safeCls += ' right';
    safeCls += ' valign-' + valignVal;
    if (showLogoOn && logoPosVal.indexOf('bottom-') === 0 && valignVal === 'bottom') {
      safeCls += ' above-logo';
    }
    phoneSafe.className = safeCls;

    var fontEl = q('select[name*="[font_preset]"]', block);
    applyFontClass((fontEl && fontEl.value) || 'editorial');

    var gradEl = q('input[name*="[gradient_opacity]"]', block);
    var gradOp = gradEl ? parseInt(gradEl.value, 10) || 0 : 0;
    var gradColEl = q('input[name*="[gradient_color]"]', block);
    var gradCol = (gradColEl && gradColEl.value) || '#000000';
    if (phoneGrad) {
      phoneGrad.style.background = gradCss(gradOp, gradCol);
      phoneGrad.style.opacity = gradOp > 0 ? '1' : '0';
    }

    paintLogo(showLogoOn, (logoStyleEl && logoStyleEl.value) || 'circle', logoPosVal);

    if (ctaL && ctaU) {
      phoneCta.textContent = ctaL;
      phoneCta.setAttribute('data-href', ctaU);
      phoneCta.style.display = 'inline-block';
      phoneCta.style.cursor = 'pointer';
      applyAnim(phoneCta, 'fade-in');
    } else {
      phoneCta.removeAttribute('data-href');
      phoneCta.style.display = 'none';
      phoneCta.style.cursor = '';
      animClasses.forEach(function(c) { phoneCta.classList.remove(c); });
    }

    applyAnim(phoneTitle, title ? animVal : null);
    applyAnim(phoneBody, body ? animVal : null);

    var url = getBgUrl(activeIdx);
    var zone = q('.ws-upload-zone', block);
    if (zone) {
      var fname = '';
      var inp = q('.ws-upload-input', zone);
      if (inp && inp.files && inp.files[0]) {
        fname = inp.files[0].name;
      } else if (url) {
        var existing = q('.ws-upload-fname', zone);
        fname = existing && existing.textContent ? existing.textContent : url.split('/').pop();
      }
      setUploadZoneState(zone, url, fname);
    }

    syncThumbs();
  }

  function bindAllColorPickers(block) {
    qa('.ws-color-wrap', block).forEach(function(wrap) {
      var picker = q('input[type=color]', wrap);
      var val = q('.ws-color-val', wrap);
      if (!picker || !val || picker._wsColorBound) return;
      picker._wsColorBound = true;
      picker.addEventListener('input', function() {
        val.textContent = picker.value;
        syncPreview();
      });
    });
  }

  function bindGradientRange(block) {
    var range = q('.ws-grad-range', block);
    if (!range || range._wsGradBound) return;
    range._wsGradBound = true;
    range.addEventListener('input', function() {
      var lbl = block.querySelector('[data-for="' + range.id + '"]');
      if (lbl) lbl.textContent = range.value + '%';
      syncPreview();
    });
  }

  function bindColorPicker(block) {
    bindAllColorPickers(block);
  }

  function bindSlide(block) {
    var idx = parseInt(block.getAttribute('data-index'), 10) || 0;
    block.addEventListener('input', syncPreview);
    block.addEventListener('change', function(e) {
      if (e.target.matches('select, input[type="color"], input[type="checkbox"]')) syncPreview();
    });
    bindColorPicker(block);
    bindGradientRange(block);

    var zone = q('.ws-upload-zone', block);
    if (zone) {
      initUploadZone(zone, {
        onPick: function(file, dataUrl) {
          pendingBg[idx] = dataUrl;
          syncPreview();
        }
      });
    }
  }

  qa('.ws-slide-data', slidePanels).forEach(bindSlide);

  slidesRail.addEventListener('click', function(e) {
    if (e.target.closest('.ws-thumb-del')) {
      e.preventDefault();
      e.stopPropagation();
      var item = e.target.closest('.ws-thumb-item');
      if (!item) return;
      deleteSlide(parseInt(item.getAttribute('data-idx'), 10) || 0);
      return;
    }
    var thumb = e.target.closest('.ws-slide-thumb');
    if (!thumb) return;
    selectSlide(parseInt(thumb.getAttribute('data-idx'), 10) || 0);
  });

  var addBtn = document.getElementById('ws-add-slide');
  if (addBtn) addBtn.addEventListener('click', addSlide);

  if (deleteSlideBtn) {
    deleteSlideBtn.addEventListener('click', function() {
      deleteSlide(activeIdx);
    });
  }

  qa('.ws-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      var target = tab.getAttribute('data-tab');
      qa('.ws-tab').forEach(function(t) { t.classList.toggle('active', t === tab); });
      qa('.ws-props .ws-panel').forEach(function(p) {
        var pTab = p.getAttribute('data-tab');
        if (target === 'seo') {
          p.classList.toggle('active', pTab === 'seo');
        } else if (p.classList.contains('ws-panel-slide')) {
          p.classList.toggle('active', parseInt(p.getAttribute('data-idx'), 10) === activeIdx);
        } else {
          p.classList.remove('active');
        }
      });
    });
  });

  var seoPanel = q('.ws-panel[data-tab="seo"]');
  var posterZone = seoPanel ? q('.ws-upload-zone', seoPanel) : null;
  if (posterZone) {
    initUploadZone(posterZone, {
      onPick: function(file, dataUrl) {
        pendingPoster = dataUrl;
        syncGooglePreview();
      }
    });
  }

  function syncGooglePreview() {
    var t = q('#seo-title');
    var d = q('#seo-desc');
    var box = q('#google-preview');
    if (!t || !box) return;
    var titleEl = q('.title', box);
    var descEl = q('.desc', box);
    if (titleEl) titleEl.textContent = t.value || 'Story title';
    if (descEl) descEl.textContent = d.value || 'Meta description for Google Search & Discover.';

    var posterUrl = pendingPoster;
    if (!posterUrl) {
      var hidden = q('input[name="existing_poster"]');
      if (hidden && hidden.value) posterUrl = assetUrl(hidden.value);
    }
    var img = q('img', box);
    if (posterUrl) {
      if (!img) {
        img = document.createElement('img');
        img.alt = '';
        box.insertBefore(img, box.firstChild);
      }
      img.src = posterUrl;
      img.style.display = 'block';
    } else if (img) {
      img.style.display = 'none';
    }
  }
  qa('#seo-title,#seo-desc').forEach(function(el) {
    el.addEventListener('input', syncGooglePreview);
  });

  qa('.ws-panel-slide').forEach(function(p, i) {
    p.classList.toggle('active', i === 0);
  });

  form.addEventListener('submit', function(e) {
    var titleInput = q('#seo-title');
    var title = titleInput ? titleInput.value.trim() : '';
    if (!title) {
      e.preventDefault();
      var seoTab = q('.ws-tab[data-tab="seo"]');
      if (seoTab) seoTab.click();
      setTimeout(function() {
        if (titleInput) {
          titleInput.focus();
          wsAlert('Add your story title in the SEO tab before saving.', 'Title required');
        }
      }, 50);
      return false;
    }
  });

  syncGooglePreview();
  updatePageMeta();
  syncPreview();
})();
