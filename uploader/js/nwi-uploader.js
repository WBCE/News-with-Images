/*
 * news_img – schlanker Vanilla-JS Bild-Uploader (kein jQuery, kein Bootstrap).
 *
 * Erwartet im DOM:
 *   #nwi-dropzone     Drag&Drop-Zone (enthaelt das <input type=file>)
 *   #nwi-file-input   Dateiauswahl
 *   #nwi-files        <ul> fuer die Datei-/Fortschrittsliste
 *   #nwi-status       <ul> fuer Gesamt-Statusmeldungen
 *
 * Erwartet als globale Variablen (aus dem Template):
 *   NWI_UPLOAD_URL, NWI_COMPLETE_MESSAGE, NWI_IMAGE_MAX_SIZE, NWI_MAX_SIZE_MESSAGE
 *
 * Pro Datei ein eigener POST (FormData-Feld "file") an NWI_UPLOAD_URL.
 * Upload-Fortschritt via XMLHttpRequest.upload (fetch kann das nicht).
 */
(function () {
  'use strict';

  var dropzone   = document.getElementById('nwi-dropzone');
  var input      = document.getElementById('nwi-file-input');
  var filesList  = document.getElementById('nwi-files');
  var statusList = document.getElementById('nwi-status');
  if (!dropzone || !input) { return; }

  var maxSize = (typeof NWI_IMAGE_MAX_SIZE !== 'undefined') ? parseInt(NWI_IMAGE_MAX_SIZE, 10) : 0;
  var seq     = 0;   // laufende Zeilen-ID
  var active  = 0;   // laufende Uploads

  // --- Drag & Drop -----------------------------------------------------------
  ['dragenter', 'dragover'].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) {
      e.preventDefault(); e.stopPropagation();
      dropzone.classList.add('active');
    });
  });
  ['dragleave', 'dragend', 'drop'].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) {
      e.preventDefault(); e.stopPropagation();
      dropzone.classList.remove('active');
    });
  });
  dropzone.addEventListener('drop', function (e) {
    if (e.dataTransfer) { handleFiles(e.dataTransfer.files); }
  });
  input.addEventListener('change', function () {
    handleFiles(input.files);
    input.value = ''; // gleiche Datei erneut waehlbar machen
  });

  // --- Verarbeitung ----------------------------------------------------------
  function handleFiles(fileList) {
    Array.prototype.slice.call(fileList || []).forEach(function (file) {
      if (file.type && file.type.indexOf('image/') !== 0) { return; } // nur Bilder
      uploadFile(file);
    });
  }

  function uploadFile(file) {
    removeEmptyHint();
    var row = createRow('nwiFile' + (++seq), file.name);
    filesList.insertBefore(row.li, filesList.firstChild);

    // Clientseitiger Groessen-Check (der Server prueft zusaetzlich)
    if (maxSize > 0 && file.size > maxSize) {
      setStatus(row, 'error', (typeof NWI_MAX_SIZE_MESSAGE !== 'undefined') ? NWI_MAX_SIZE_MESSAGE : 'File too large');
      return;
    }

    active++;
    var form = new FormData();
    form.append('file', file, file.name);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', NWI_UPLOAD_URL, true);

    xhr.upload.addEventListener('progress', function (e) {
      if (e.lengthComputable) {
        setProgress(row, Math.round((e.loaded / e.total) * 100));
      }
    });
    xhr.addEventListener('load', function () {
      var resp = null;
      try { resp = JSON.parse(xhr.responseText); } catch (err) {}
      if (xhr.status >= 200 && xhr.status < 300 && resp && resp.status === 'ok') {
        setProgress(row, 100);
        setStatus(row, 'success', '✓'); // ✓
      } else {
        var msg = (resp && resp.message) ? resp.message : ('Error (' + xhr.status + ')');
        setStatus(row, 'error', msg);
      }
      finish();
    });
    xhr.addEventListener('error', function () {
      setStatus(row, 'error', 'Network error');
      finish();
    });
    xhr.send(form);

    function finish() {
      if (--active === 0 && typeof NWI_COMPLETE_MESSAGE !== 'undefined') {
        addStatusMessage(NWI_COMPLETE_MESSAGE);
      }
    }
  }

  // --- DOM-Helfer ------------------------------------------------------------
  function removeEmptyHint() {
    var empty = filesList && filesList.querySelector('.nwi-uploader-empty');
    if (empty) { empty.parentNode.removeChild(empty); }
  }

  function createRow(id, filename) {
    var li = document.createElement('li');
    li.className = 'nwi-uploader-file';
    li.id = id;

    var name = document.createElement('span');
    name.className = 'nwi-uploader-file-name';
    name.textContent = filename;

    var status = document.createElement('span');
    status.className = 'nwi-uploader-file-status is-uploading';
    status.textContent = '0%';

    var head = document.createElement('div');
    head.className = 'nwi-uploader-file-head';
    head.appendChild(name);
    head.appendChild(status);

    var track = document.createElement('div');
    track.className = 'nwi-uploader-progress';
    var bar = document.createElement('div');
    bar.className = 'nwi-uploader-progress-bar';
    bar.style.width = '0%';
    track.appendChild(bar);

    li.appendChild(head);
    li.appendChild(track);
    return { li: li, bar: bar, track: track, status: status };
  }

  function setProgress(row, percent) {
    row.bar.style.width = percent + '%';
    if (percent < 100) {
      row.status.textContent = percent + '%';
      row.status.className = 'nwi-uploader-file-status is-uploading';
    }
  }

  function setStatus(row, type, message) {
    row.status.textContent = message;
    row.status.className = 'nwi-uploader-file-status is-' + type;
    if (type === 'error') {
      row.bar.style.width = '100%';
      row.track.classList.add('is-error');
    } else if (type === 'success') {
      row.track.classList.add('is-success');
    }
  }

  function addStatusMessage(message) {
    if (!statusList) { return; }
    var li = document.createElement('li');
    li.className = 'nwi-uploader-status-item';
    li.textContent = message;
    statusList.insertBefore(li, statusList.firstChild);
  }
})();
