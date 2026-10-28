/* ==========================================================================
   Lectura - Countdown JS
   Live countdown chips for assignments; updates every 30s
   ========================================================================== */
(function () {
  'use strict';

  var TIME = (window.LC_I18N && window.LC_I18N.time) || {};
  var omitOneTwo = TIME.omitOneTwo === '1' || TIME.omitOneTwo === 1;

  // Arabic counted-noun forms come from the language file as "1|2|3-10|11+".
  function unit(n, forms) {
    var f = String(forms || '').split('|');
    if (f.length < 4) return '';
    if (n === 1) return f[0];
    if (n === 2) return f[1];
    if (n >= 3 && n <= 10) return f[2];
    return f[3];
  }

  // Arabic omits "1" and "2" before the dual/singular form.
  function counted(n, forms) {
    var word = unit(n, forms);
    if (!word) return String(n);
    return omitOneTwo && (n === 1 || n === 2) ? word : n + ' ' + word;
  }

  function formatCountdown(diffMs) {
    var overdue = diffMs < 0;
    var abs = Math.abs(diffMs);
    var d = Math.floor(abs / 86400000);
    var h = Math.floor((abs % 86400000) / 3600000);
    var m = Math.floor((abs % 3600000) / 60000);

    var prefix = overdue ? (TIME.overdue || 'Overdue by ') : (TIME.left || '');
    var and = TIME.and || ' ';
    var suffix = overdue ? '' : (TIME.suffix || '');
    var days = TIME.days || 'day|days|days|days';
    var hours = TIME.hours || 'hour|hours|hours|hours';
    var minutes = TIME.minutes || 'minute|minutes|minutes|minutes';

    if (d > 0) {
      var dayText = prefix + counted(d, days);
      if (h > 0) dayText += and + counted(h, hours);
      return dayText + suffix;
    }
    if (h > 0) {
      var hourText = prefix + counted(h, hours);
      if (m > 0) hourText += and + counted(m, minutes);
      return hourText + suffix;
    }
    if (m > 0) {
      return prefix + counted(m, minutes) + suffix;
    }
    return overdue ? (TIME.overdueOnly || 'Overdue') : (TIME.now || 'Due now');
  }

  function update() {
    var now = Date.now();
    document.querySelectorAll('[data-countdown]').forEach(function (el) {
      var due = el.getAttribute('data-countdown');
      if (!due) return;
      var dueMs = new Date(due.replace(' ', 'T') + (due.indexOf('Z') === -1 && due.indexOf('+') === -1 ? 'Z' : '')).getTime();
      if (isNaN(dueMs)) return;
      var diff = dueMs - now;
      el.textContent = formatCountdown(diff);

      var chip = el.closest('.due-chip') || el;
      chip.classList.remove('due-soon', 'overdue');
      if (diff < 0) chip.classList.add('overdue');
      else if (diff <= 86400000) chip.classList.add('due-soon');
    });
  }

  window.LecturaCountdown = { update: update };

  document.addEventListener('DOMContentLoaded', function () {
    if (!document.querySelector('[data-countdown]')) return;
    update();
    setInterval(update, 30000);
  });
})();
