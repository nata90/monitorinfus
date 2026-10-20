/* Monitor Infus — utilitas umum (modal AJAX, notifikasi, suara alarm) */
(function (window, $) {
  'use strict';

  var App = window.App = window.App || {};

  App.csrf = function () {
    return {
      param: $('meta[name="csrf-param"]').attr('content'),
      token: $('meta[name="csrf-token"]').attr('content')
    };
  };

  App.escape = function (s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  };

  /* ---------- Notifikasi inline ---------- */
  App.notify = function (message, type) {
    type = type || 'success';
    var icons = { success: 'mdi-check-circle-outline', danger: 'mdi-alert-circle-outline', warning: 'mdi-alert-outline', info: 'mdi-information-outline' };
    var $el = $(
      '<div class="app-alert alert fade show app-alert-' + type + '" role="alert">' +
        '<i class="mdi ' + (icons[type] || icons.info) + '" aria-hidden="true"></i>' +
        '<div class="app-alert-body">' + App.escape(message) + '</div>' +
        '<button type="button" class="app-alert-close" data-bs-dismiss="alert" aria-label="Tutup"><i class="mdi mdi-close" aria-hidden="true"></i></button>' +
      '</div>'
    );
    $('.app-main .app-container').prepend($el);
    window.setTimeout(function () { $el.alert && $el.alert('close'); }, 5000);
  };

  /* ---------- Modal AJAX ---------- */
  var modalEl = null, modal = null;

  function getModal() {
    if (!modalEl) {
      modalEl = document.getElementById('appModal');
      if (modalEl && window.bootstrap) {
        modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
      }
    }
    return modal;
  }

  App.openModal = function (url) {
    var m = getModal();
    if (!m) { window.location.href = url; return; }
    var $content = $(modalEl).find('.modal-content');
    $content.html('<div class="modal-body text-center app-muted py-5"><i class="mdi mdi-loading mdi-spin" style="font-size:28px"></i></div>');
    m.show();
    $.ajax({ url: url, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .done(function (html) { $content.html(html); })
      .fail(function () {
        $content.html('<div class="modal-body"><div class="app-alert app-alert-danger"><i class="mdi mdi-alert-circle-outline"></i><div class="app-alert-body">Gagal memuat form. Coba lagi.</div></div></div>');
      });
  };

  App.closeModal = function () {
    var m = getModal();
    if (m) m.hide();
  };

  // Submit form di dalam modal via AJAX
  $(document).on('submit', '#appModal form[data-ajax-form]', function (e) {
    // yii.activeForm mencegah submit saat validasi klien gagal / masih berjalan
    if (e.isDefaultPrevented()) return;
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('[type="submit"]').prop('disabled', true);

    $.ajax({
      url: $form.attr('action'),
      method: 'POST',
      data: $form.serialize(),
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).done(function (res, status, xhr) {
      var ct = xhr.getResponseHeader('content-type') || '';
      if (ct.indexOf('application/json') !== -1 && res && res.success) {
        App.closeModal();
        App.notify(res.message || 'Tersimpan.', 'success');
        $(document).trigger('app:refresh', [res]);
      } else {
        $(modalEl).find('.modal-content').html(res);
      }
    }).fail(function () {
      $btn.prop('disabled', false);
      App.notify('Terjadi kesalahan saat menyimpan. Coba lagi.', 'danger');
    });
  });

  // Tautan/tombol pembuka modal
  $(document).on('click', '[data-modal-url]', function (e) {
    e.preventDefault();
    App.openModal($(this).data('modal-url'));
  });

  /* ---------- POST aksi (selesai / hentikan) ---------- */
  App.postAction = function (url, confirmText) {
    if (confirmText && !window.confirm(confirmText)) {
      return $.Deferred().reject().promise();
    }
    var csrf = App.csrf();
    var data = {};
    data[csrf.param] = csrf.token;
    return $.ajax({
      url: url, method: 'POST', data: data,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).done(function (res) {
      App.notify((res && res.message) || 'Berhasil.', 'success');
      $(document).trigger('app:refresh', [res]);
    }).fail(function () {
      App.notify('Aksi gagal diproses.', 'danger');
    });
  };

  $(document).on('click', '[data-post-url]', function (e) {
    e.preventDefault();
    App.postAction($(this).data('post-url'), $(this).data('confirm-text'));
  });

  /* ---------- Suara alarm (file web/static/infus-habis.mp3 via Web Audio API) ---------- */
  var STORAGE_KEY = 'infus.sound.enabled';
  var ctx = null;
  var alarmBuffer = null;   // AudioBuffer hasil decode mp3
  var alarmLoading = null;  // Promise pemuatan agar fetch tidak diulang
  var alarmSource = null;   // sumber yang sedang berbunyi (dihentikan sebelum putar ulang)

  // Base URL web/ diturunkan dari lokasi skrip ini (…/web/js/app.js) agar benar di subfolder apa pun.
  var BASE_URL = (function () {
    if (window.INFUS_BASE_URL) return window.INFUS_BASE_URL;
    var s = document.currentScript || $('script[src*="/js/app.js"]').last()[0];
    return s && s.src ? s.src.replace(/\/js\/app\.js(\?.*)?$/, '') : '';
  })();
  var ALARM_URL = BASE_URL + '/static/infus-habis.mp3';

  App.sound = {
    enabled: false,

    init: function () {
      var saved = null;
      try { saved = window.localStorage.getItem(STORAGE_KEY); } catch (e) { /* ignore */ }
      this.enabled = saved === '1';
      this.renderToggle();

      var self = this;
      $(document).on('click', '#soundToggle', function () { self.toggle(); });

      // Browser memblokir audio sebelum ada interaksi: coba resume saat interaksi pertama.
      var resumeOnce = function () {
        if (self.enabled) { self.ensureContext(); self.loadAlarm(); }
        document.removeEventListener('pointerdown', resumeOnce);
        document.removeEventListener('keydown', resumeOnce);
      };
      document.addEventListener('pointerdown', resumeOnce);
      document.addEventListener('keydown', resumeOnce);

      if (this.enabled) this.loadAlarm();
    },

    ensureContext: function () {
      var AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) return null;
      if (!ctx) ctx = new AC();
      if (ctx.state === 'suspended') ctx.resume();
      return ctx;
    },

    /** Muat & decode file alarm sekali; mengembalikan Promise<AudioBuffer|null>. */
    loadAlarm: function () {
      var c = this.ensureContext();
      if (!c) return Promise.resolve(null);
      if (alarmBuffer) return Promise.resolve(alarmBuffer);
      if (alarmLoading) return alarmLoading;
      alarmLoading = fetch(ALARM_URL, { credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.arrayBuffer(); })
        .then(function (data) {
          return new Promise(function (resolve, reject) {
            // Bentuk callback agar berjalan juga di Safari lama.
            c.decodeAudioData(data, resolve, reject);
          });
        })
        .then(function (buf) { alarmBuffer = buf; return buf; })
        .catch(function (err) {
          if (window.console) console.warn('Gagal memuat suara alarm (' + ALARM_URL + ')', err);
          alarmLoading = null;
          return null;
        });
      return alarmLoading;
    },

    toggle: function () {
      this.enabled = !this.enabled;
      try { window.localStorage.setItem(STORAGE_KEY, this.enabled ? '1' : '0'); } catch (e) { /* ignore */ }
      if (this.enabled) {
        this.ensureContext();
        var self = this;
        this.loadAlarm().then(function (buf) { if (buf) self.beep(1); }); // konfirmasi singkat
      } else if (alarmSource) {
        try { alarmSource.stop(); } catch (e) { /* ignore */ }
        alarmSource = null;
      }
      this.renderToggle();
      $(document).trigger('app:sound', [this.enabled]);
    },

    renderToggle: function () {
      var $btn = $('#soundToggle');
      if (!$btn.length) return;
      $btn.toggleClass('is-on', this.enabled)
        .attr('aria-pressed', this.enabled ? 'true' : 'false')
        .attr('title', this.enabled ? 'Suara alarm aktif — klik untuk mematikan' : 'Aktifkan suara alarm');
      $btn.find('.mdi').attr('class', 'mdi ' + (this.enabled ? 'mdi-volume-high' : 'mdi-volume-off'));
      $btn.find('.app-sound-label').text(this.enabled ? 'Suara Aktif' : 'Aktifkan Suara');
    },

    /** true bila suara aktif dan AudioContext sudah boleh berbunyi (sudah ada interaksi pengguna). */
    isReady: function () {
      return this.enabled && !!ctx && ctx.state === 'running';
    },

    /**
     * Putar file alarm (web/static/infus-habis.mp3).
     * count = 1 → potongan pendek sebagai konfirmasi; selain itu diputar penuh.
     * Putaran sebelumnya dihentikan dulu agar tidak tumpang-tindih saat diulang.
     * Mengembalikan false bila belum bisa berbunyi (suara nonaktif, konteks belum
     * aktif, atau file belum selesai dimuat — akan dicoba lagi pada tick berikutnya).
     */
    beep: function (count) {
      if (!this.enabled) return false;
      var c = this.ensureContext();
      if (!c || c.state !== 'running') return false;
      if (!alarmBuffer) { this.loadAlarm(); return false; }

      if (alarmSource) {
        try { alarmSource.stop(); } catch (e) { /* ignore */ }
        alarmSource = null;
      }

      var src = c.createBufferSource();
      src.buffer = alarmBuffer;
      var gain = c.createGain();
      gain.gain.setValueAtTime(1.0, c.currentTime);
      src.connect(gain).connect(c.destination);
      src.onended = function () { if (alarmSource === src) alarmSource = null; };
      src.start(0);
      if (count === 1) src.stop(c.currentTime + Math.min(0.6, alarmBuffer.duration));
      alarmSource = src;
      return true;
    }
  };

  $(function () { App.sound.init(); });

})(window, window.jQuery);
