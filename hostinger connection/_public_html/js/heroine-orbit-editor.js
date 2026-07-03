(function () {
  'use strict';

  var boot = window.HEROINE_ORBIT_EDITOR;
  if (!boot) return;

  var viewports = ['laptop', 'tablet', 'mobile'];
  var state = JSON.parse(JSON.stringify(boot.layouts));
  var activeVp = 'laptop';
  var selectedUid = null;

  var canvas = document.getElementById('orbitEditorCanvas');
  var canvasWrap = document.getElementById('orbitEditorCanvasWrap');
  var tabBtns = document.querySelectorAll('[data-orbit-tab]');
  var sidePanel = document.getElementById('orbitEditorSide');
  var saveForm = document.getElementById('orbitSaveForm');
  var saveInput = document.getElementById('orbitLayoutsJson');

  var drag = null;
  var resize = null;

  function uid() {
    return 'c' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
  }

  function parsePct(val) {
    return parseFloat(String(val).replace('%', '')) || 0;
  }

  function heroineById(id) {
    id = parseInt(id, 10);
    for (var i = 0; i < boot.heroines.length; i++) {
      if (parseInt(boot.heroines[i].id, 10) === id) return boot.heroines[i];
    }
    return null;
  }

  function circlesFor(vp) {
    if (!state[vp]) state[vp] = [];
    return state[vp];
  }

  function setTab(vp) {
    activeVp = vp;
    tabBtns.forEach(function (btn) {
      btn.classList.toggle('active', btn.getAttribute('data-orbit-tab') === vp);
    });
    canvas.classList.remove('is-tablet', 'is-mobile');
    if (vp === 'tablet') canvas.classList.add('is-tablet');
    if (vp === 'mobile') canvas.classList.add('is-mobile');
    var labels = { laptop: 'Laptop / Desktop preview', tablet: 'Tablet preview (768px)', mobile: 'Mobile preview (390px)' };
    document.getElementById('orbitCanvasLabel').textContent = labels[vp] || vp;
    selectedUid = null;
    render();
  }

  function circleRotate(circle) {
    return typeof circle.rotate === 'number' ? circle.rotate : 0;
  }

  function applyCircleTransform(el, circle) {
    el.style.transform = 'translate(-50%, -50%) rotate(' + circleRotate(circle) + 'deg)';
  }

  function renderSide() {
    var circles = circlesFor(activeVp);
    var circle = null;
    if (selectedUid) {
      circles.forEach(function (c) {
        if (c.uid === selectedUid) circle = c;
      });
    }

    if (!circle) {
      sidePanel.innerHTML =
        '<h3><i class="fa-solid fa-circle-dot"></i> Circle</h3>' +
        '<p class="orbit-editor-empty">Click a circle on the preview to select it. Drag to move, corner dot to resize.</p>' +
        '<p class="orbit-editor-empty" style="margin-top:10px"><strong>' + circles.length + '</strong> circle(s) on this screen.</p>';
      return;
    }

    var hOpts = '<option value="">— No photo —</option>';
    boot.heroines.forEach(function (h) {
      var sel = parseInt(circle.heroine_id, 10) === parseInt(h.id, 10) ? ' selected' : '';
      hOpts += '<option value="' + h.id + '"' + sel + '>' + escapeHtml(h.name) + '</option>';
    });

    var rot = circleRotate(circle);

    sidePanel.innerHTML =
      '<h3><i class="fa-solid fa-sliders"></i> Selected Circle</h3>' +
      '<div class="orbit-editor-field"><label>Photo</label><select id="oeHeroine">' + hOpts + '</select></div>' +
      '<div class="orbit-editor-field"><label>Size (px)</label><input type="range" id="oeSize" min="28" max="160" value="' + circle.size + '"><div class="orbit-editor-range-val" id="oeSizeVal">' + circle.size + 'px</div></div>' +
      '<div class="orbit-editor-field"><label>Rotation</label><input type="range" id="oeRotate" min="0" max="360" value="' + rot + '"><div class="orbit-editor-range-val" id="oeRotateVal">' + rot + '°</div></div>' +
      '<div class="orbit-editor-field"><label>Opacity</label><input type="range" id="oeOpacity" min="15" max="100" value="' + Math.round(circle.opacity * 100) + '"><div class="orbit-editor-range-val" id="oeOpacityVal">' + Math.round(circle.opacity * 100) + '%</div></div>' +
      '<div class="orbit-editor-field"><label>Position</label><div class="orbit-editor-range-val">Left ' + parsePct(circle.left).toFixed(1) + '% · Top ' + parsePct(circle.top).toFixed(1) + '%</div></div>' +
      '<button type="button" class="btn-oe btn-oe-danger" id="oeDelete" style="width:100%;margin-top:8px"><i class="fa-solid fa-trash"></i> Delete Circle</button>';

    document.getElementById('oeHeroine').addEventListener('change', function () {
      circle.heroine_id = this.value ? parseInt(this.value, 10) : 0;
      if (!circle.heroine_id) delete circle.heroine_id;
      render();
    });
    document.getElementById('oeSize').addEventListener('input', function () {
      circle.size = parseInt(this.value, 10);
      document.getElementById('oeSizeVal').textContent = circle.size + 'px';
      positionCircleEl(circle);
    });
    document.getElementById('oeRotate').addEventListener('input', function () {
      circle.rotate = parseInt(this.value, 10);
      document.getElementById('oeRotateVal').textContent = this.value + '°';
      var el = canvas.querySelector('[data-uid="' + circle.uid + '"]');
      if (el) applyCircleTransform(el, circle);
    });
    document.getElementById('oeOpacity').addEventListener('input', function () {
      circle.opacity = parseInt(this.value, 10) / 100;
      document.getElementById('oeOpacityVal').textContent = this.value + '%';
      positionCircleEl(circle);
    });
    document.getElementById('oeDelete').addEventListener('click', function () {
      state[activeVp] = circles.filter(function (c) { return c.uid !== selectedUid; });
      selectedUid = null;
      render();
    });
  }

  function escapeHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
  }

  function positionCircleEl(circle) {
    var el = canvas.querySelector('[data-uid="' + circle.uid + '"]');
    if (!el) return;
    el.style.left = parsePct(circle.left) + '%';
    el.style.top = parsePct(circle.top) + '%';
    el.style.width = circle.size + 'px';
    el.style.height = circle.size + 'px';
    el.style.opacity = circle.opacity;
    el.style.setProperty('--orbit-bg', circle.bg || '#FFE4EC');
    applyCircleTransform(el, circle);
  }

  function render() {
    var circles = circlesFor(activeVp);
    canvas.querySelectorAll('.orbit-editor-circle').forEach(function (n) { n.remove(); });

    circles.forEach(function (circle) {
      if (!circle.uid) circle.uid = uid();
      var el = document.createElement('div');
      el.className = 'orbit-editor-circle' + (circle.uid === selectedUid ? ' is-selected' : '');
      el.setAttribute('data-uid', circle.uid);
      el.style.left = parsePct(circle.left) + '%';
      el.style.top = parsePct(circle.top) + '%';
      el.style.width = circle.size + 'px';
      el.style.height = circle.size + 'px';
      el.style.opacity = circle.opacity;
      el.style.setProperty('--orbit-bg', circle.bg || '#FFE4EC');
      applyCircleTransform(el, circle);

      var h = circle.heroine_id ? heroineById(circle.heroine_id) : null;
      if (h && h.circle_image) {
        var img = document.createElement('img');
        img.src = h.circle_image;
        img.alt = '';
        el.appendChild(img);
      } else {
        var ph = document.createElement('span');
        ph.className = 'ph-icon';
        ph.innerHTML = '<i class="fa-solid fa-user"></i>';
        el.appendChild(ph);
      }

      var handle = document.createElement('span');
      handle.className = 'orbit-editor-resize';
      el.appendChild(handle);

      el.addEventListener('pointerdown', onCircleDown);
      handle.addEventListener('pointerdown', onResizeDown);
      canvas.appendChild(el);
    });

    renderSide();
  }

  function canvasPoint(evt) {
    var rect = canvas.getBoundingClientRect();
    return {
      x: ((evt.clientX - rect.left) / rect.width) * 100,
      y: ((evt.clientY - rect.top) / rect.height) * 100,
      px: evt.clientX - rect.left,
      py: evt.clientY - rect.top
    };
  }

  function onCircleDown(evt) {
    if (evt.target.classList.contains('orbit-editor-resize')) return;
    evt.preventDefault();
    evt.stopPropagation();
    var uidVal = evt.currentTarget.getAttribute('data-uid');
    selectedUid = uidVal;
    var circle = circlesFor(activeVp).find(function (c) { return c.uid === uidVal; });
    if (!circle) return;
    var pt = canvasPoint(evt);
    drag = { uid: uidVal, offX: pt.x - parsePct(circle.left), offY: pt.y - parsePct(circle.top) };
    canvas.querySelectorAll('.orbit-editor-circle').forEach(function (n) {
      n.classList.toggle('is-selected', n.getAttribute('data-uid') === uidVal);
      n.classList.remove('is-dragging');
    });
    evt.currentTarget.classList.add('is-dragging');
    evt.currentTarget.setPointerCapture(evt.pointerId);
    renderSide();
  }

  function onResizeDown(evt) {
    evt.preventDefault();
    evt.stopPropagation();
    var el = evt.target.closest('.orbit-editor-circle');
    var uidVal = el.getAttribute('data-uid');
    selectedUid = uidVal;
    var circle = circlesFor(activeVp).find(function (c) { return c.uid === uidVal; });
    if (!circle) return;
    resize = { uid: uidVal, startSize: circle.size, startX: evt.clientX, startY: evt.clientY };
    canvas.querySelectorAll('.orbit-editor-circle').forEach(function (n) {
      n.classList.toggle('is-selected', n.getAttribute('data-uid') === uidVal);
    });
    evt.target.setPointerCapture(evt.pointerId);
    renderSide();
  }

  canvas.addEventListener('pointermove', function (evt) {
    if (drag) {
      var circle = circlesFor(activeVp).find(function (c) { return c.uid === drag.uid; });
      if (!circle) return;
      var pt = canvasPoint(evt);
      circle.left = Math.max(2, Math.min(98, pt.x - drag.offX)).toFixed(1) + '%';
      circle.top = Math.max(2, Math.min(98, pt.y - drag.offY)).toFixed(1) + '%';
      positionCircleEl(circle);
      renderSide();
    }
    if (resize) {
      var c2 = circlesFor(activeVp).find(function (x) { return x.uid === resize.uid; });
      if (!c2) return;
      var delta = Math.max(evt.clientX - resize.startX, evt.clientY - resize.startY);
      c2.size = Math.max(28, Math.min(160, resize.startSize + delta));
      positionCircleEl(c2);
      renderSide();
    }
  });

  canvas.addEventListener('pointerup', function () {
    drag = null;
    resize = null;
    canvas.querySelectorAll('.is-dragging').forEach(function (n) { n.classList.remove('is-dragging'); });
  });

  canvas.addEventListener('click', function (evt) {
    if (evt.target === canvas || evt.target.closest('.orbit-editor-mock')) {
      selectedUid = null;
      render();
    }
  });

  document.getElementById('oeAddCircle').addEventListener('click', function () {
    var circles = circlesFor(activeVp);
    var firstH = boot.heroines[0] ? parseInt(boot.heroines[0].id, 10) : 0;
    circles.push({
      uid: uid(),
      left: '50%',
      top: '50%',
      size: 56,
      opacity: 0.88,
      rotate: 0,
      bg: '#FFE4EC',
      delay: '0s',
      heroine_id: firstH || undefined
    });
    selectedUid = circles[circles.length - 1].uid;
    render();
  });

  document.getElementById('oeLoadDefaults').addEventListener('click', function () {
    if (!confirm('Replace current tab circles with built-in defaults? Unsaved changes on this tab will be lost.')) return;
    state[activeVp] = JSON.parse(JSON.stringify(boot.defaults[activeVp] || []));
    state[activeVp].forEach(function (c, i) {
      if (!c.uid) c.uid = 'c' + (i + 1);
    });
    selectedUid = null;
    render();
  });

  tabBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      setTab(btn.getAttribute('data-orbit-tab'));
    });
  });

  if (saveForm) {
    saveForm.addEventListener('submit', function () {
      saveInput.value = JSON.stringify(state);
    });
  }

  setTab('laptop');
})();
