/* ==========================================================================
   Lectura - Share JS
   WhatsApp share links, group copy-and-open flow, copy message buttons
   ========================================================================== */
(function () {
  'use strict';

  function readText(btn) {
    var sel = btn.getAttribute('data-whatsapp');
    if (!sel) return '';
    if (sel.charAt(0) === '#') {
      var el = document.querySelector(sel);
      return el ? (el.value || el.textContent) : '';
    }
    return sel;
  }

  function fallbackCopy(text) {
    var area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();
    var ok = false;
    try {
      ok = document.execCommand('copy');
    } catch (err) {
      ok = false;
    }
    area.remove();
    return ok ? Promise.resolve() : Promise.reject(new Error('copy failed'));
  }

  function copyText(text) {
    if (window.Lectura && typeof window.Lectura.copyText === 'function') {
      return window.Lectura.copyText(text);
    }
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return fallbackCopy(text);
  }

  function showToast(message, type) {
    if (window.Lectura && typeof window.Lectura.showToast === 'function') {
      window.Lectura.showToast(message, type);
    }
  }

  function initShare() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-whatsapp]');
      if (!btn) return;
      e.preventDefault();

      var text = readText(btn);
      var groupUrl = btn.getAttribute('data-whatsapp-group');

      if (groupUrl) {
        // Open the configured group synchronously so popup blockers allow it,
        // then copy the message for the user to paste into the group.
        window.open(groupUrl, '_blank', 'noopener,noreferrer');
        copyText(text).then(function () {
          showToast((window.LC_I18N && LC_I18N.groupCopied) || 'Message copied. Paste it into the group.');
        }).catch(function () {
          showToast((window.LC_I18N && LC_I18N.copyFailed) || 'Could not copy the message. Copy it manually.', 'error');
        });
        return;
      }

      var url = 'https://wa.me/?text=' + encodeURIComponent(text);
      window.open(url, '_blank', 'noopener');
    });

    // Native share (optional)
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-native-share]');
      if (!btn || !navigator.share) return;
      e.preventDefault();
      var sel = btn.getAttribute('data-native-share');
      var el = sel && sel.charAt(0) === '#' ? document.querySelector(sel) : null;
      var text = el ? (el.value || el.textContent) : '';
      navigator.share({ text: text }).catch(function () {});
    });
  }

  document.addEventListener('DOMContentLoaded', initShare);
})();
