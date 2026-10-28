/* ==========================================================================
   Lectura - Main App JS
   Theme, toasts, copy, confirmations, client-side filtering
   ========================================================================== */
(function () {
  'use strict';

  /* ---------- Theme management ---------- */
  var THEME_KEY = 'theme';

  function getPreferredTheme() {
    var stored = localStorage.getItem(THEME_KEY);
    if (stored === 'light' || stored === 'dark') return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function applyTheme(mode) {
    var effective = mode;
    if (mode !== 'light' && mode !== 'dark') {
      effective = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-bs-theme', effective);
    document.documentElement.setAttribute('data-lc-theme', mode);
    // Update meta theme-color
    var metas = document.querySelectorAll('meta[name="theme-color"]');
    if (metas.length) {
      metas.forEach(function (m) {
        m.setAttribute('content', effective === 'dark' ? '#071827' : '#12355B');
      });
    }
  }

  function initTheme() {
    var stored = null;
    try { stored = localStorage.getItem(THEME_KEY); } catch (e) {}
    var mode = stored || document.documentElement.getAttribute('data-lc-theme') || 'light';
    applyTheme(mode);

    var btn = document.getElementById('themeToggle');
    if (btn) {
      btn.addEventListener('click', function () {
        var current = null;
        try { current = localStorage.getItem(THEME_KEY); } catch (e) {}
        current = current || document.documentElement.getAttribute('data-lc-theme') || 'auto';
        var next = current === 'auto' ? 'light' : (current === 'light' ? 'dark' : 'auto');
        try { localStorage.setItem(THEME_KEY, next); } catch (e) {}
        applyTheme(next);
        showToast(next === 'auto' ? (window.LC_I18N && LC_I18N.themeAuto) || 'Auto' : (next === 'dark' ? (window.LC_I18N && LC_I18N.themeDark) || 'Dark' : (window.LC_I18N && LC_I18N.themeLight) || 'Light'));
        // Persist for logged-in user if endpoint available
        persistTheme(next);
      });
    }

    // React to system change when in auto
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
      if ((localStorage.getItem(THEME_KEY) || 'auto') === 'auto') applyTheme('auto');
    });
  }

  function persistTheme(theme) {
    var auth = document.querySelector('meta[name="lc-auth"]');
    if (!auth || auth.getAttribute('content') !== '1') return;
    try {
      fetch((window.LC_BASE || '') + '/settheme.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: JSON.stringify({ theme: theme })
      }).catch(function () {});
    } catch (e) {}
  }

  /* ---------- Toasts ---------- */
  function ensureToastContainer() {
    var c = document.getElementById('lcToastContainer');
    if (!c) {
      c = document.createElement('div');
      c.id = 'lcToastContainer';
      c.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      c.setAttribute('aria-live', 'polite');
      c.setAttribute('aria-atomic', 'true');
      document.body.appendChild(c);
    }
    return c;
  }

  function showToast(message, type) {
    type = type || 'success';
    var container = ensureToastContainer();
    var el = document.createElement('div');
    el.className = 'toast lc-toast align-items-center border-0';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.setAttribute('aria-atomic', 'true');

    var icon = type === 'success' ? 'bi-check-circle-fill text-success'
      : type === 'error' ? 'bi-exclamation-triangle-fill text-danger'
      : 'bi-info-circle-fill text-primary';

    el.innerHTML = '<div class="d-flex"><div class="toast-body d-flex align-items-center gap-2">' +
      '<i class="bi ' + icon + '"></i><span></span></div>' +
      '<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    el.querySelector('span').textContent = message;
    container.appendChild(el);

    if (window.bootstrap && bootstrap.Toast) {
      var t = new bootstrap.Toast(el, { delay: 3200 });
      t.show();
      el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    } else {
      setTimeout(function () { el.remove(); }, 3200);
    }
  }

  /* ---------- Copy to clipboard ---------- */
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand('copy');
        resolve();
      } catch (e) {
        reject(e);
      } finally {
        ta.remove();
      }
    });
  }

  function initCopyButtons() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-copy]');
      if (!btn) return;
      e.preventDefault();
      var targetSel = btn.getAttribute('data-copy');
      var text = '';
      if (targetSel && targetSel.charAt(0) === '#') {
        var target = document.querySelector(targetSel);
        text = target ? (target.value || target.textContent) : '';
      } else {
        text = targetSel || '';
      }
      copyText(text).then(function () {
        showToast((window.LC_I18N && LC_I18N.copied) || 'Copied!');
      }).catch(function () {
        showToast('Copy failed', 'error');
      });
    });
  }

  /* ---------- Confirm modals ---------- */
  function initConfirm() {
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!form.matches('[data-confirm]')) return;
      if (form.dataset.confirmed === '1') return;
      e.preventDefault();
      var message = form.getAttribute('data-confirm') || 'Are you sure?';
      if (window.confirm(message)) {
        form.dataset.confirmed = '1';
        form.submit();
      }
    });
  }

  /* ---------- Submit loading state ---------- */
  function initSubmitLoading() {
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (form.matches('[data-no-loading]')) return;
      var btn = form.querySelector('button[type="submit"]:not([data-no-loading])');
      if (btn && !btn.classList.contains('is-loading')) {
        // Delay slightly so validation can cancel
        setTimeout(function () {
          if (form.checkValidity && form.checkValidity()) btn.classList.add('is-loading');
        }, 0);
      }
    });
  }

  /* ---------- Client-side item filtering (course page) ---------- */
  function initItemFilter() {
    var search = document.getElementById('itemSearch');
    var chips = document.querySelectorAll('[data-filter-type]');
    var items = document.querySelectorAll('[data-item-card]');
    if (!items.length) return;

    var activeType = 'all';
    var query = '';

    function apply() {
      var visible = 0;
      items.forEach(function (item) {
        var type = item.getAttribute('data-type') || 'other';
        var title = (item.getAttribute('data-title') || '').toLowerCase();
        var matchesType = activeType === 'all' || type === activeType;
        var matchesQuery = !query || title.indexOf(query) !== -1;
        var show = matchesType && matchesQuery;
        item.classList.toggle('d-none', !show);
        if (show) visible++;
      });
      var empty = document.getElementById('filterEmpty');
      if (empty) empty.classList.toggle('d-none', visible !== 0);
      var countEl = document.getElementById('visibleCount');
      if (countEl) countEl.textContent = visible;
    }

    if (search) {
      search.addEventListener('input', function () {
        query = this.value.trim().toLowerCase();
        apply();
      });
    }

    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        chips.forEach(function (c) { c.classList.remove('active'); c.setAttribute('aria-pressed', 'false'); });
        chip.classList.add('active');
        chip.setAttribute('aria-pressed', 'true');
        activeType = chip.getAttribute('data-filter-type');
        apply();
      });
    });
  }

  /* ---------- Auto-dismiss flash alerts ---------- */
  function initFlash() {
    document.querySelectorAll('.alert-dismissible[data-auto-dismiss]').forEach(function (alert) {
      setTimeout(function () {
        if (window.bootstrap && bootstrap.Alert) bootstrap.Alert.getOrCreateInstance(alert).close();
      }, 5000);
    });
  }

  /* ---------- Tooltips ---------- */
  function initTooltips() {
    if (!window.bootstrap) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      new bootstrap.Tooltip(el);
    });
  }

  /* ---------- Password visibility ---------- */
  function initPasswordToggle() {
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = document.querySelector(btn.getAttribute('data-toggle-password'));
        if (!target) return;
        var isPassword = target.type === 'password';
        target.type = isPassword ? 'text' : 'password';
        var icon = btn.querySelector('i');
        if (icon) {
          icon.classList.toggle('bi-eye', !isPassword);
          icon.classList.toggle('bi-eye-slash', isPassword);
        }
        btn.setAttribute('aria-label', isPassword
          ? ((window.LC_I18N && LC_I18N.hidePassword) || 'Hide password')
          : ((window.LC_I18N && LC_I18N.showPassword) || 'Show password'));
        btn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
      });
    });
  }

  /* ---------- Password strength ---------- */
  function initStrength() {
    var input = document.querySelector('[data-strength-input]');
    if (!input) return;
    var meter = document.querySelector('[data-strength-meter]');
    var label = document.querySelector('[data-strength-label]');
    input.addEventListener('input', function () {
      var v = input.value;
      var score = 0;
      if (v.length >= 8) score++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      if (v.length === 0) score = 0;
      if (meter) meter.setAttribute('data-score', String(score));
      if (label) {
        var names = (window.LC_I18N && LC_I18N.strength) || ['Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        label.textContent = names[score] || '';
      }
    });
  }

  /* ---------- Theme options on the settings page ---------- */
  function initThemeOptions() {
    document.querySelectorAll('[data-theme-option]').forEach(function (input) {
      input.addEventListener('change', function () {
        if (!input.checked) return;
        try { localStorage.setItem(THEME_KEY, input.value); } catch (e) {}
        applyTheme(input.value);
      });
    });
  }

  /* ---------- Dismissible announcements ---------- */
  function initAnnouncementDismiss() {
    document.querySelectorAll('[data-dismiss-announcement]').forEach(function (btn) {
      var id = btn.getAttribute('data-dismiss-announcement');
      var key = 'lc-dismissed-announcement-' + id;
      var card = btn.closest('.announcement-card');
      if (!card) return;
      try {
        if (localStorage.getItem(key) === '1') {
          card.remove();
          return;
        }
      } catch (e) {}
      btn.addEventListener('click', function () {
        try { localStorage.setItem(key, '1'); } catch (e) {}
        card.remove();
      });
    });
  }

  /* ---------- Boot ---------- */
  document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initCopyButtons();
    initConfirm();
    initSubmitLoading();
    initItemFilter();
    initFlash();
    initTooltips();
    initPasswordToggle();
    initStrength();
    initThemeOptions();
    initAnnouncementDismiss();
  });

  // Expose API
  window.Lectura = {
    showToast: showToast,
    copyText: copyText,
    applyTheme: applyTheme,
    initAnnouncementDismiss: initAnnouncementDismiss,
    initItemFilter: initItemFilter,
    initFlash: initFlash
  };
})();
