(function () {
  'use strict';

  function toast(msg, type) {
    var el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.className = 'toast show ' + (type || 'ok');
    setTimeout(function () { el.className = 'toast'; }, 2500);
  }

  function post(action, body) {
    return fetch('happy_users_admin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'ajax_action=' + action + '&' + body
    }).then(function (r) { return r.json(); });
  }

  function isImage(file) {
    return file && ((file.type && file.type.indexOf('image/') === 0) || /\.(jpe?g|png|webp|gif)$/i.test(file.name || ''));
  }

  function fileKey(file) {
    return [file.name, file.size, file.lastModified].join('|');
  }

  // ── Upload tab — accumulate many files across picks / drops ─────────────────
  var zone = document.getElementById('uploadZone');
  var input = document.getElementById('fileInput');
  var preview = document.getElementById('previewRow');
  var submitBtn = document.getElementById('submitBtn');
  var clearBtn = document.getElementById('clearBtn');
  var fileCount = document.getElementById('fileCount');
  var picked = [];

  function syncInput() {
    if (!input) return;
    var dt = new DataTransfer();
    picked.forEach(function (f) { dt.items.add(f); });
    input.files = dt.files;
  }

  function addFiles(fileList) {
    if (!fileList || !fileList.length) return;
    var seen = {};
    picked.forEach(function (f) { seen[fileKey(f)] = true; });
    Array.prototype.forEach.call(fileList, function (f) {
      if (!isImage(f)) return;
      var key = fileKey(f);
      if (!seen[key]) {
        seen[key] = true;
        picked.push(f);
      }
    });
    syncInput();
    refreshPreview();
  }

  function clearPicked() {
    picked = [];
    syncInput();
    refreshPreview();
  }

  function refreshPreview() {
    if (!preview || !submitBtn) return;
    preview.innerHTML = '';
    if (!picked.length) {
      if (fileCount) fileCount.textContent = '';
      submitBtn.disabled = true;
      if (clearBtn) clearBtn.style.display = 'none';
      return;
    }
    if (fileCount) {
      fileCount.textContent = picked.length + ' file(s) ready — sab ek saath upload hongi';
    }
    submitBtn.disabled = false;
    if (clearBtn) clearBtn.style.display = 'inline-flex';
    picked.forEach(function (file) {
      var img = document.createElement('img');
      img.className = 'preview-thumb';
      img.title = file.name;
      img.src = URL.createObjectURL(file);
      img.onload = function () { URL.revokeObjectURL(img.src); };
      preview.appendChild(img);
    });
  }

  if (zone && input) {
    zone.addEventListener('dragover', function (e) {
      e.preventDefault();
      zone.classList.add('dragover');
    });
    zone.addEventListener('dragleave', function () {
      zone.classList.remove('dragover');
    });
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      zone.classList.remove('dragover');
      if (e.dataTransfer && e.dataTransfer.files.length) {
        addFiles(e.dataTransfer.files);
      }
    });
    input.addEventListener('change', function () {
      if (input.files && input.files.length) {
        addFiles(input.files);
      }
    });
    if (clearBtn) {
      clearBtn.addEventListener('click', clearPicked);
    }

    var form = document.getElementById('uploadForm');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!picked.length) {
          toast('Koi file select nahi hui', 'err');
          return;
        }
        var fd = new FormData();
        fd.append('action', 'upload');
        var csrf = form.querySelector('input[name="csrf_token"]');
        if (csrf) {
          fd.append('csrf_token', csrf.value);
        }
        picked.forEach(function (file) {
          fd.append('screenshots[]', file, file.name);
        });
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
        fetch('happy_users_admin.php?tab=upload', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        })
          .then(function (res) {
            window.location.href = res.url || 'happy_users_admin.php?tab=upload';
          })
          .catch(function () {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-upload"></i> Upload All to Happy Users';
            toast('Upload failed — try again', 'err');
          });
      });
    }
  }

  // ── Manage tab ────────────────────────────────────────────────────────────
  var grid = document.getElementById('manageGrid');
  if (!grid) return;

  var dragged = null;

  function saveOrder() {
    var ids = Array.prototype.map.call(
      grid.querySelectorAll('.hu-item'),
      function (el) { return el.dataset.id; }
    ).join(',');
    post('reorder', 'order=' + encodeURIComponent(ids))
      .then(function (d) {
        if (d.ok) toast('Order saved', 'ok');
        else toast(d.error || 'Reorder failed', 'err');
      })
      .catch(function () { toast('Network error', 'err'); });
  }

  grid.querySelectorAll('.hu-item').forEach(function (item) {
    item.addEventListener('dragstart', function (e) {
      dragged = item;
      item.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    item.addEventListener('dragend', function () {
      item.classList.remove('dragging');
      dragged = null;
      saveOrder();
    });
    item.addEventListener('dragover', function (e) {
      e.preventDefault();
      if (!dragged || dragged === item) return;
      var rect = item.getBoundingClientRect();
      var after = e.clientY > rect.top + rect.height / 2;
      if (after) {
        item.parentNode.insertBefore(dragged, item.nextSibling);
      } else {
        item.parentNode.insertBefore(dragged, item);
      }
    });
  });

  grid.querySelectorAll('.hu-toggle').forEach(function (tog) {
    tog.addEventListener('change', function () {
      var id = tog.dataset.id;
      var on = tog.checked ? 1 : 0;
      var card = document.getElementById('huItem' + id);
      post('toggle', 'id=' + id + '&val=' + on)
        .then(function (d) {
          if (!d.ok) {
            tog.checked = !tog.checked;
            toast(d.error || 'Update failed', 'err');
            return;
          }
          if (card) {
            card.classList.toggle('hidden-item', !on);
            var badge = card.querySelector('.hu-badge');
            if (on && badge) badge.remove();
            if (!on && !badge) {
              var b = document.createElement('span');
              b.className = 'hu-badge';
              b.textContent = 'Hidden';
              card.insertBefore(b, card.firstChild);
            }
          }
          toast(on ? 'Visible on page' : 'Hidden from page', 'ok');
        })
        .catch(function () {
          tog.checked = !tog.checked;
          toast('Network error', 'err');
        });
    });
  });

  grid.querySelectorAll('.hu-btn.del').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openDeleteModal(btn.dataset.id);
    });
  });

  var deleteModal = document.getElementById('huDeleteModal');
  var deleteThumb = document.getElementById('huDeleteThumb');
  var deleteCancel = document.getElementById('huDeleteCancel');
  var deleteConfirm = document.getElementById('huDeleteConfirm');
  var deleteTargetId = null;

  function openDeleteModal(id) {
    if (!deleteModal) return;
    deleteTargetId = id;
    var card = document.getElementById('huItem' + id);
    if (deleteThumb && card) {
      var img = card.querySelector('.hu-item-media img');
      deleteThumb.src = img ? img.getAttribute('src') : '';
      deleteThumb.alt = 'Screenshot to delete';
    }
    deleteModal.classList.add('is-open');
    deleteModal.setAttribute('aria-hidden', 'false');
  }

  function closeDeleteModal() {
    if (!deleteModal) return;
    deleteModal.classList.remove('is-open');
    deleteModal.setAttribute('aria-hidden', 'true');
    deleteTargetId = null;
    if (deleteThumb) deleteThumb.removeAttribute('src');
  }

  function runDelete() {
    if (!deleteTargetId) return;
    var id = deleteTargetId;
    closeDeleteModal();
    post('delete', 'id=' + id)
      .then(function (d) {
        if (!d.ok) {
          toast(d.error || 'Delete failed', 'err');
          return;
        }
        var card = document.getElementById('huItem' + id);
        if (card) {
          card.style.transition = 'opacity .3s, transform .3s';
          card.style.opacity = '0';
          card.style.transform = 'scale(.9)';
          setTimeout(function () { card.remove(); }, 300);
        }
        toast('Screenshot deleted', 'ok');
      })
      .catch(function () { toast('Network error', 'err'); });
  }

  if (deleteCancel) {
    deleteCancel.addEventListener('click', closeDeleteModal);
  }
  if (deleteConfirm) {
    deleteConfirm.addEventListener('click', runDelete);
  }
  if (deleteModal) {
    deleteModal.addEventListener('click', function (e) {
      if (e.target === deleteModal) closeDeleteModal();
    });
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && deleteModal && deleteModal.classList.contains('is-open')) {
      closeDeleteModal();
    }
  });
})();
