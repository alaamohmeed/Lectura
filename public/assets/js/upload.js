/* ==========================================================================
   Lectura - Upload JS
   Drag & drop zone, client-side validation, XHR upload with progress
   ========================================================================== */
(function () {
  'use strict';

  function initUpload() {
    var form = document.getElementById('uploadForm');
    if (!form) return;

    var dropzone = document.getElementById('dropzone');
    var fileInput = document.getElementById('fileInput');
    var fileList = document.getElementById('fileList');
    var progressWrap = document.getElementById('uploadProgress');
    var progressBar = document.getElementById('uploadProgressBar');
    var progressText = document.getElementById('uploadProgressText');
    var submitBtn = document.getElementById('uploadSubmit');
    var typeSelect = document.getElementById('itemType');
    var fileRequiredMark = document.getElementById('fileRequiredMark');
    var dueWrap = document.getElementById('dueDateWrap');
    var dueInput = document.getElementById('dueDate');

    var selectedFiles = [];
    var maxSize = parseInt(form.dataset.maxSize || '10485760', 10);
    var allowedExt = (form.dataset.allowedExt || 'pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip')
      .split(',').map(function (s) { return s.trim().toLowerCase(); });

    function isAssignment() {
      return typeSelect && typeSelect.value === 'assignment';
    }

    function syncFileRequirement() {
      if (fileRequiredMark) fileRequiredMark.classList.toggle('d-none', isAssignment());
    }

    /* Show/hide due date based on type */
    function syncDue() {
      if (!typeSelect || !dueWrap) return;
      var isAssignment = typeSelect.value === 'assignment';
      dueWrap.classList.toggle('d-none', !isAssignment);
      if (dueInput) {
        dueInput.required = isAssignment;
        if (!isAssignment) dueInput.value = '';
      }
    }
    if (typeSelect) {
      typeSelect.addEventListener('change', function () {
        syncDue();
        syncFileRequirement();
      });
      syncDue();
      syncFileRequirement();
    }

    /* ---------- File selection ---------- */
    function humanSize(bytes) {
      var units = ['B', 'KB', 'MB', 'GB'];
      var i = 0;
      while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
      return (i === 0 ? bytes : bytes.toFixed(1)) + ' ' + units[i];
    }

    function extOf(name) {
      var parts = name.split('.');
      return parts.length > 1 ? parts.pop().toLowerCase() : '';
    }

    function renderFiles() {
      if (!fileList) return;
      fileList.innerHTML = '';
      selectedFiles.forEach(function (file, idx) {
        var item = document.createElement('div');
        item.className = 'file-item';
        item.innerHTML =
          '<i class="bi bi-file-earmark-fill text-primary fs-5" aria-hidden="true"></i>' +
          '<div class="min-w-0">' +
            '<div class="file-item-name lc-wrap"></div>' +
            '<div class="file-item-size"></div>' +
          '</div>' +
          '<button type="button" class="remove-file" aria-label="' +
            (window.LC_I18N && LC_I18N.removeFile ? LC_I18N.removeFile : 'Remove') + '">' +
            '<i class="bi bi-x-lg"></i>' +
          '</button>';
        item.querySelector('.file-item-name').textContent = file.name;
        item.querySelector('.file-item-size').textContent = humanSize(file.size);
        item.querySelector('.remove-file').addEventListener('click', function () {
          selectedFiles.splice(idx, 1);
          renderFiles();
        });
        fileList.appendChild(item);
      });
    }

    function addFiles(fileListObj) {
      var errors = [];
      Array.prototype.forEach.call(fileListObj, function (file) {
        var ext = extOf(file.name);
        if (allowedExt.indexOf(ext) === -1) {
          errors.push((window.LC_I18N && LC_I18N.badType ? LC_I18N.badType : 'File type not allowed: ') + file.name);
          return;
        }
        if (file.size > maxSize) {
          errors.push((window.LC_I18N && LC_I18N.tooLarge ? LC_I18N.tooLarge : 'File too large: ') + file.name);
          return;
        }
        // Avoid duplicates by name+size
        var dup = selectedFiles.some(function (f) { return f.name === file.name && f.size === file.size; });
        if (!dup) selectedFiles.push(file);
      });

      if (errors.length) {
        errors.forEach(function (msg) {
          if (window.Lectura) window.Lectura.showToast(msg, 'error');
        });
      }
      renderFiles();
    }

    if (fileInput) {
      fileInput.addEventListener('change', function () {
        addFiles(fileInput.files);
        fileInput.value = '';
      });
    }

    if (dropzone) {
      ['dragenter', 'dragover'].forEach(function (evt) {
        dropzone.addEventListener(evt, function (e) {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.add('dragover');
        });
      });
      ['dragleave', 'drop'].forEach(function (evt) {
        dropzone.addEventListener(evt, function (e) {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.remove('dragover');
        });
      });
      dropzone.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files) addFiles(e.dataTransfer.files);
      });
      dropzone.addEventListener('click', function () { if (fileInput) fileInput.click(); });
      dropzone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          if (fileInput) fileInput.click();
        }
      });
    }

    /* ---------- Submit via XHR for progress ---------- */
    form.addEventListener('submit', function (e) {
      if (!selectedFiles.length && !isAssignment()) {
        e.preventDefault();
        if (window.Lectura) {
          window.Lectura.showToast((window.LC_I18N && LC_I18N.noFiles) || 'Please select at least one file', 'error');
        }
        return;
      }
      if (!form.checkValidity()) {
        return; // Let browser show validation messages
      }
      e.preventDefault();

      var fd = new FormData(form);
      fd.delete('files[]');
      selectedFiles.forEach(function (f) { fd.append('files[]', f, f.name); });

      var xhr = new XMLHttpRequest();
      xhr.open('POST', form.action || window.location.href, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      if (progressWrap) progressWrap.classList.add('active');
      if (submitBtn) {
        submitBtn.classList.add('is-loading');
        submitBtn.disabled = true;
      }

      xhr.upload.addEventListener('progress', function (ev) {
        if (!ev.lengthComputable) return;
        var pct = Math.round((ev.loaded / ev.total) * 100);
        if (progressBar) {
          progressBar.style.width = pct + '%';
          progressBar.setAttribute('aria-valuenow', String(pct));
        }
        if (progressText) progressText.textContent = pct + '%';
      });

      xhr.addEventListener('load', function () {
        var res = null;
        try { res = JSON.parse(xhr.responseText); } catch (err) {}
        if (xhr.status >= 200 && xhr.status < 300 && res && res.success) {
          if (progressBar) progressBar.style.width = '100%';
          window.location.href = res.redirect;
          return;
        }
        // Error
        if (progressWrap) progressWrap.classList.remove('active');
        if (submitBtn) { submitBtn.classList.remove('is-loading'); submitBtn.disabled = false; }
        var msg = (res && res.error) || (window.LC_I18N && LC_I18N.uploadError) || 'Upload failed';
        if (window.Lectura) window.Lectura.showToast(msg, 'error');
      });

      xhr.addEventListener('error', function () {
        if (progressWrap) progressWrap.classList.remove('active');
        if (submitBtn) { submitBtn.classList.remove('is-loading'); submitBtn.disabled = false; }
        if (window.Lectura) window.Lectura.showToast((window.LC_I18N && LC_I18N.uploadError) || 'Upload failed', 'error');
      });

      xhr.send(fd);
    });
  }

  document.addEventListener('DOMContentLoaded', initUpload);
})();