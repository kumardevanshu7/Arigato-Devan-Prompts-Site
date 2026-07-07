/**
 * Styled upload zones — settings & static forms.
 */
(function() {
  function q(sel, root) {
    return (root || document).querySelector(sel);
  }
  function qa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function setUploadZoneState(zone, url, fileName) {
    var empty = q('.ws-upload-empty', zone);
    var filled = q('.ws-upload-filled', zone);
    var preview = q('.ws-local-img-preview', zone);
    var fname = q('.ws-upload-fname', zone);
    if (url) {
      if (empty) empty.hidden = true;
      if (filled) filled.hidden = false;
      if (preview) preview.src = url;
      if (fname && fileName) fname.textContent = fileName;
    }
  }

  function initUploadZone(zone) {
    var input = q('.ws-upload-input', zone);
    if (!input || zone._wsBound) return;
    zone._wsBound = true;

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
        alert('Please choose an image file.');
        input.value = '';
        return;
      }
      var reader = new FileReader();
      reader.onload = function(ev) {
        setUploadZoneState(zone, ev.target.result, file.name);
      };
      reader.readAsDataURL(file);
    });
  }

  qa('.ws-upload-zone').forEach(initUploadZone);
})();
