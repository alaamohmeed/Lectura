/* ==========================================================================
   Lectura - Live updates
   Polls a tiny version endpoint and refreshes the current page's main content
   in place when content changes. No full page reload.
   ========================================================================== */
(function () {
  'use strict';

  var versionMeta = document.querySelector('meta[name="lc-live-version"]');
  var pageMeta = document.querySelector('meta[name="lc-live-page"]');
  if (!versionMeta || !pageMeta || !window.fetch || !window.DOMParser) return;

  var endpoint = (window.LC_BASE || '') + '/api/live.php';
  var currentVersion = parseInt(versionMeta.getAttribute('content'), 10) || 0;
  var pollDelay = 5000;
  var hiddenDelay = 20000;
  var retryDelay = 15000;
  var timer = null;
  var polling = false;
  var refreshing = false;
  var stopped = false;

  function i18n(key, fallback) {
    return (window.LC_I18N && window.LC_I18N[key]) || fallback;
  }

  function setVersion(value) {
    currentVersion = parseInt(value, 10) || 0;
    versionMeta.setAttribute('content', String(currentVersion));
  }

  function schedule(delay) {
    if (stopped) return;
    clearTimeout(timer);
    timer = setTimeout(poll, delay || (document.hidden ? hiddenDelay : pollDelay));
  }

  function poll() {
    if (stopped) return;
    if (document.hidden) {
      schedule(hiddenDelay);
      return;
    }
    if (polling || refreshing) {
      schedule(1000);
      return;
    }

    polling = true;
    var url = endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + 'since=' + encodeURIComponent(currentVersion);

    fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (res) {
      if (res.status === 401 || res.status === 403) {
        stopped = true;
        return null;
      }
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    }).then(function (data) {
      polling = false;
      if (stopped) return;
      if (!data || !data.ok) {
        schedule(retryDelay);
        return;
      }

      var version = parseInt(data.version, 10) || 0;
      if (version !== currentVersion) {
        refresh(version).then(function () {
          if (!stopped) schedule();
        }, function (err) {
          if (!stopped) schedule(err && err.auth ? hiddenDelay : retryDelay);
        });
      } else {
        schedule();
      }
    }).catch(function () {
      polling = false;
      if (!stopped) schedule(retryDelay);
    });
  }

  function captureState() {
    var state = {
      scrollY: window.pageYOffset || document.documentElement.scrollTop || 0,
      search: null,
      filter: null,
      focusSearch: false,
      selectionStart: 0,
      selectionEnd: 0,
      openCollapses: []
    };

    var search = document.getElementById('itemSearch');
    if (search) {
      state.search = search.value;
      state.focusSearch = document.activeElement === search;
      if (state.focusSearch) {
        state.selectionStart = search.selectionStart || 0;
        state.selectionEnd = search.selectionEnd || 0;
      }
    }

    var activeChip = document.querySelector('[data-filter-type].active');
    if (activeChip) {
      state.filter = activeChip.getAttribute('data-filter-type');
    }

    document.querySelectorAll('.accordion-collapse.show').forEach(function (el) {
      if (el.id) state.openCollapses.push(el.id);
    });

    return state;
  }

  function restoreState(state) {
    var search = document.getElementById('itemSearch');
    if (search && state.search !== null) {
      search.value = state.search;
      search.dispatchEvent(new Event('input', { bubbles: true }));
    }

    if (state.filter && state.filter !== 'all') {
      var chips = document.querySelectorAll('[data-filter-type]');
      for (var i = 0; i < chips.length; i++) {
        if (chips[i].getAttribute('data-filter-type') === state.filter) {
          chips[i].click();
          break;
        }
      }
    }

    if (window.bootstrap && window.bootstrap.Collapse && state.openCollapses.length) {
      state.openCollapses.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
          window.bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show();
        }
      });
    }

    if (search && state.focusSearch) {
      try { search.focus({ preventScroll: true }); } catch (e) { search.focus(); }
      try { search.setSelectionRange(state.selectionStart, state.selectionEnd); } catch (e) {}
    }

    window.requestAnimationFrame(function () {
      window.scrollTo(0, state.scrollY);
    });
  }

  function reinitialize() {
    if (window.Lectura) {
      if (typeof window.Lectura.initAnnouncementDismiss === 'function') {
        window.Lectura.initAnnouncementDismiss();
      }
      if (typeof window.Lectura.initItemFilter === 'function') {
        window.Lectura.initItemFilter();
      }
      if (typeof window.Lectura.initFlash === 'function') {
        window.Lectura.initFlash();
      }
    }
    if (window.LecturaCountdown && typeof window.LecturaCountdown.update === 'function') {
      window.LecturaCountdown.update();
    }
  }

  function notifyUpdated() {
    var message = i18n('liveUpdated', 'Content updated');
    var status = document.getElementById('lcLiveStatus');
    if (status) {
      status.textContent = '';
      setTimeout(function () { status.textContent = message; }, 50);
    }
    if (window.Lectura && typeof window.Lectura.showToast === 'function') {
      window.Lectura.showToast(message);
    }
  }

  function refresh(versionHint) {
    if (refreshing) return Promise.resolve();
    refreshing = true;

    var url = window.location.pathname + window.location.search;

    return fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Lectura-Live': '1' }
    }).then(function (res) {
      if (res.status === 401 || res.status === 403) {
        stopped = true;
        var authError = new Error('Authentication required');
        authError.auth = true;
        throw authError;
      }
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.text();
    }).then(function (html) {
      var doc = new DOMParser().parseFromString(html, 'text/html');
      var newMain = doc.querySelector('main.main-content');
      var oldMain = document.querySelector('main.main-content');
      var newVersionMeta = doc.querySelector('meta[name="lc-live-version"]');

      if (!newMain || !oldMain || !newVersionMeta) {
        stopped = true;
        return;
      }

      var state = captureState();
      oldMain.innerHTML = newMain.innerHTML;

      var newTitle = doc.querySelector('title');
      if (newTitle && newTitle.textContent.trim() !== '') {
        document.title = newTitle.textContent.trim();
      }

      setVersion(newVersionMeta.getAttribute('content') || versionHint);
      reinitialize();
      restoreState(state);
      notifyUpdated();
    }).catch(function (err) {
      if (err && err.auth) stopped = true;
      throw err;
    }).then(function () {
      refreshing = false;
    }, function (err) {
      refreshing = false;
      throw err;
    });
  }

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      clearTimeout(timer);
      return;
    }
    if (!stopped) {
      clearTimeout(timer);
      poll();
    }
  });

  window.addEventListener('online', function () {
    if (!stopped) {
      clearTimeout(timer);
      poll();
    }
  });

  // Small jitter prevents every open tab from polling in lockstep.
  schedule(2000 + Math.floor(Math.random() * 2000));
})();
