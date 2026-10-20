/* Monitor Infus — dashboard: countdown, sinkronisasi server, alarm */
(function (window, $, App) {
  'use strict';

  var cfg = window.INFUS_DASHBOARD || {};
  var esc = App.escape;

  var state = {
    sessions: [],
    idleBeds: [],
    offsetMs: 0,            // waktu server - waktu perangkat
    warning: 30,
    alarm: 2,
    syncSeconds: 30,
    repeatSeconds: 3,
    acked: {},              // id -> true (hanya di memori; reload = alarm aktif kembali)
    everAlarmed: {},        // id -> true (untuk bunyi segera saat pertama masuk ambang)
    lastBeepAt: 0,
    query: '',
    filter: 'all',
    view: 'grid',
    syncFailed: false
  };

  var $root, $grid, $tbody, $idle, $banner, $summary, $empty, $syncInfo;

  /* ---------- Waktu ---------- */
  function serverNow() { return Date.now() + state.offsetMs; }

  function compute(s) {
    var end = Date.parse(s.estimated_end_at);
    var remainingSec = Math.max(0, (end - serverNow()) / 1000);
    var minutes = Math.ceil(remainingSec / 60);
    var display;
    if (minutes <= 0) display = 'empty';
    else if (minutes <= state.alarm) display = 'alarm';
    else if (minutes <= state.warning) display = 'warning';
    else display = 'safe';
    var totalSec = Math.max(1, s.duration_minutes * 60);
    var progress = Math.min(100, Math.max(0, 100 * (1 - remainingSec / totalSec)));
    return { minutes: minutes, remainingSec: remainingSec, display: display, progress: progress, end: end };
  }

  function fmtTime(ms) {
    var d = new Date(ms);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  }
  function fmtDateTime(ms) {
    var d = new Date(ms);
    return d.toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
  }
  function fmtNum(n) {
    return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 2 });
  }

  var LABELS = { safe: 'Aman', warning: 'Segera habis', alarm: 'Alarm', empty: 'Habis' };
  var ICONS = { safe: 'mdi-check-circle-outline', warning: 'mdi-clock-alert-outline', alarm: 'mdi-bell-ring-outline', empty: 'mdi-water-off-outline' };

  function badgeHtml(display) {
    return '<span class="app-badge app-badge-' + display + '"><i class="mdi ' + ICONS[display] + '" aria-hidden="true"></i>' + LABELS[display] + '</span>';
  }

  /* ---------- Filter ---------- */
  function visible(s, c) {
    if (state.query) {
      var q = state.query.toLowerCase();
      if (s.bed_code.toLowerCase().indexOf(q) === -1 && s.room_name.toLowerCase().indexOf(q) === -1) return false;
    }
    if (state.filter === 'all') return true;
    if (state.filter === 'critical') return c.display === 'alarm' || c.display === 'empty';
    return c.display === state.filter;
  }

  /* ---------- Render struktur (saat data berubah) ---------- */
  function cardHtml(s) {
    var updateUrl = cfg.urls.update.replace('__ID__', s.id);
    var completeUrl = cfg.urls.complete.replace('__ID__', s.id);
    var stopUrl = cfg.urls.stop.replace('__ID__', s.id);
    return (
      '<article class="bed-card" data-id="' + s.id + '" aria-live="polite">' +
        '<div class="bed-card-head">' +
          '<div>' +
            '<div class="bed-card-code"><i class="mdi mdi-bed-outline" aria-hidden="true"></i>' + esc(s.bed_code) + '</div>' +
            '<div class="bed-card-room">' + esc(s.room_name) + '</div>' +
          '</div>' +
          '<span class="js-badge"></span>' +
        '</div>' +
        '<div class="bed-card-time">' +
          '<span class="bed-card-minutes js-minutes">–</span><span class="bed-card-unit">menit</span>' +
          '<div class="bed-card-end">habis <strong class="js-end">' + esc(fmtTime(Date.parse(s.estimated_end_at))) + '</strong></div>' +
        '</div>' +
        '<div class="bed-card-progress" role="progressbar" aria-label="Progres infus"><span class="js-progress" style="width:0%"></span></div>' +
        '<div class="bed-card-params">' +
          '<div>Faktor tetes<span>' + fmtNum(s.drop_factor) + ' <small>tts/mL</small></span></div>' +
          '<div>Volume<span>' + fmtNum(s.volume_ml) + ' <small>mL</small></span></div>' +
          '<div>TPM<span>' + fmtNum(s.tpm) + '</span></div>' +
        '</div>' +
        (s.notes ? '<div class="bed-card-notes">' + esc(s.notes) + '</div>' : '') +
        '<div class="bed-card-alarm" role="status">' +
          '<i class="mdi mdi-bell-ring" aria-hidden="true"></i><span class="js-alarm-text">Infus hampir habis</span>' +
          '<button type="button" class="app-btn app-btn-sm app-btn-danger js-ack"><i class="mdi mdi-bell-off-outline" aria-hidden="true"></i>Akui Alarm</button>' +
          '<span class="ack-note">Alarm diakui</span>' +
        '</div>' +
        '<div class="bed-card-actions">' +
          '<button type="button" class="app-btn app-btn-sm app-btn-safe" data-post-url="' + esc(completeUrl) + '" data-confirm-text="Tandai sesi infus bed ' + esc(s.bed_code) + ' sebagai selesai?"><i class="mdi mdi-check-circle-outline" aria-hidden="true"></i>Selesai</button>' +
          '<button type="button" class="app-btn app-btn-sm app-btn-secondary" data-modal-url="' + esc(updateUrl) + '"><i class="mdi mdi-pencil-outline" aria-hidden="true"></i>Ubah</button>' +
          '<button type="button" class="app-btn app-btn-sm app-btn-ghost app-btn-icon" title="Hentikan sesi" aria-label="Hentikan sesi" data-post-url="' + esc(stopUrl) + '" data-confirm-text="Hentikan sesi infus bed ' + esc(s.bed_code) + '?"><i class="mdi mdi-stop-circle-outline" aria-hidden="true"></i></button>' +
        '</div>' +
      '</article>'
    );
  }

  function rowHtml(s) {
    var updateUrl = cfg.urls.update.replace('__ID__', s.id);
    var completeUrl = cfg.urls.complete.replace('__ID__', s.id);
    var stopUrl = cfg.urls.stop.replace('__ID__', s.id);
    var start = Date.parse(s.started_at);
    return (
      '<tr data-id="' + s.id + '">' +
        '<td class="cell-strong">' + esc(s.bed_code) + '</td>' +
        '<td>' + esc(s.room_name) + '</td>' +
        '<td class="row-minutes num"><span class="js-minutes">–</span> <small class="app-muted" style="font-weight:400">mnt</small></td>' +
        '<td class="js-badge"></td>' +
        '<td class="num">' + fmtNum(s.drop_factor) + '</td>' +
        '<td class="num">' + fmtNum(s.volume_ml) + '</td>' +
        '<td class="num">' + fmtNum(s.tpm) + '</td>' +
        '<td class="num">' + esc(fmtDateTime(start)) + '</td>' +
        '<td class="num">' + esc(fmtDateTime(Date.parse(s.estimated_end_at))) + '</td>' +
        '<td class="cell-actions">' +
          '<button type="button" class="app-btn app-btn-sm app-btn-danger js-ack" style="display:none"><i class="mdi mdi-bell-off-outline" aria-hidden="true"></i>Akui</button>' +
          '<button type="button" class="app-btn app-btn-sm app-btn-safe" data-post-url="' + esc(completeUrl) + '" data-confirm-text="Tandai sesi infus bed ' + esc(s.bed_code) + ' sebagai selesai?"><i class="mdi mdi-check-circle-outline" aria-hidden="true"></i>Selesai</button>' +
          '<button type="button" class="app-btn app-btn-sm app-btn-secondary app-btn-icon" title="Ubah" aria-label="Ubah" data-modal-url="' + esc(updateUrl) + '"><i class="mdi mdi-pencil-outline" aria-hidden="true"></i></button>' +
          '<button type="button" class="app-btn app-btn-sm app-btn-ghost app-btn-icon" title="Hentikan" aria-label="Hentikan" data-post-url="' + esc(stopUrl) + '" data-confirm-text="Hentikan sesi infus bed ' + esc(s.bed_code) + '?"><i class="mdi mdi-stop-circle-outline" aria-hidden="true"></i></button>' +
        '</td>' +
      '</tr>'
    );
  }

  function renderStructure() {
    var cards = '', rows = '';
    state.sessions.forEach(function (s) {
      cards += cardHtml(s);
      rows += rowHtml(s);
    });
    $grid.html(cards);
    $tbody.html(rows);

    var idle = '';
    state.idleBeds.forEach(function (b) {
      var url = cfg.urls.create + (cfg.urls.create.indexOf('?') === -1 ? '?' : '&') + 'bed_id=' + b.id;
      idle += '<span class="app-idle-chip"><span>' + esc(b.code) + ' <small>· ' + esc(b.room_name) + '</small></span>' +
        '<button type="button" class="app-btn app-btn-sm app-btn-ghost" title="Mulai infus di ' + esc(b.code) + '" aria-label="Mulai infus di ' + esc(b.code) + '" data-modal-url="' + esc(url) + '"><i class="mdi mdi-plus" aria-hidden="true"></i></button></span>';
    });
    $idle.html(idle);
    $idle.closest('.app-idle').toggle(state.idleBeds.length > 0);

    update();
  }

  /* ---------- Update nilai dinamis (tiap detik) ---------- */
  function update() {
    var counts = { safe: 0, warning: 0, alarm: 0, empty: 0 };
    var ringing = [];
    var shown = 0;
    var newlyAlarmed = false;

    state.sessions.forEach(function (s) {
      var c = compute(s);
      counts[c.display]++;

      var isCritical = c.display === 'alarm' || c.display === 'empty';
      if (isCritical) {
        if (!state.everAlarmed[s.id]) { state.everAlarmed[s.id] = true; newlyAlarmed = true; }
        if (!state.acked[s.id]) ringing.push(s);
      } else {
        delete state.everAlarmed[s.id];
        delete state.acked[s.id]; // bila waktu diubah sehingga keluar dari ambang
      }

      var vis = visible(s, c);
      if (vis) shown++;

      var $card = $grid.find('[data-id="' + s.id + '"]');
      var $row = $tbody.find('[data-id="' + s.id + '"]');

      $card.toggle(vis);
      $row.toggle(vis);

      $card.attr('class', 'bed-card is-' + c.display + (isCritical && !state.acked[s.id] ? ' is-ringing' : '') + (state.acked[s.id] ? ' is-acked' : ''));
      $row.attr('class', 'is-' + c.display);

      $card.find('.js-minutes').text(c.minutes);
      $row.find('.js-minutes').text(c.minutes);
      $card.find('.js-badge').html(badgeHtml(c.display));
      $row.find('.js-badge').html(badgeHtml(c.display));
      $card.find('.js-progress').css('width', c.progress.toFixed(1) + '%');
      $card.find('.js-alarm-text').text(c.display === 'empty' ? 'Waktu habis terlewati' : 'Habis ≤ ' + state.alarm + ' menit');
      $row.find('.js-ack').toggle(isCritical && !state.acked[s.id]);
    });

    // Ringkasan
    $summary.html(
      '<span><strong>' + state.sessions.length + '</strong> infus aktif</span>' +
      '<span><span class="dot dot-safe"></span><strong>' + counts.safe + '</strong> aman</span>' +
      '<span><span class="dot dot-warning"></span><strong>' + counts.warning + '</strong> segera habis</span>' +
      '<span><span class="dot dot-danger"></span><strong>' + (counts.alarm + counts.empty) + '</strong> kritis</span>'
    );

    // Banner alarm
    if (ringing.length) {
      var codes = ringing.map(function (s) { return s.bed_code; }).join(', ');
      var soundNote = '';
      if (!App.sound.enabled) soundNote = ' — suara belum diaktifkan';
      else if (!App.sound.isReady()) soundNote = ' — klik di halaman untuk mengaktifkan suara';
      $banner.addClass('is-visible').find('.js-banner-text').text(
        ringing.length + ' bed memerlukan perhatian: ' + codes + soundNote
      );
    } else {
      $banner.removeClass('is-visible');
    }

    // Empty state
    $empty.toggle(state.sessions.length === 0);
    $root.find('.js-no-match').toggle(state.sessions.length > 0 && shown === 0);

    // Alarm suara: segera saat masuk ambang, lalu terus berulang setiap repeatSeconds sampai diakui
    if (ringing.length) {
      var now = Date.now();
      if (newlyAlarmed || now - state.lastBeepAt >= state.repeatSeconds * 1000) {
        if (App.sound.beep(4)) state.lastBeepAt = now;
        else if (newlyAlarmed) state.lastBeepAt = 0;
      }
    } else {
      state.lastBeepAt = 0;
    }
  }

  /* ---------- Sinkronisasi server ---------- */
  function applyPayload(p, requestStart) {
    var rtt = requestStart ? (Date.now() - requestStart) / 2 : 0;
    state.offsetMs = p.server_timestamp_ms + rtt - Date.now();
    state.warning = p.warning_minutes;
    state.alarm = p.alarm_minutes;
    state.syncSeconds = p.sync_seconds || state.syncSeconds;
    state.repeatSeconds = p.alarm_repeat_seconds || state.repeatSeconds;
    state.sessions = p.sessions || [];
    state.idleBeds = p.idle_beds || [];

    // Bersihkan ack untuk sesi yang sudah tidak aktif
    var ids = {};
    state.sessions.forEach(function (s) { ids[s.id] = true; });
    Object.keys(state.acked).forEach(function (id) { if (!ids[id]) delete state.acked[id]; });
    Object.keys(state.everAlarmed).forEach(function (id) { if (!ids[id]) delete state.everAlarmed[id]; });

    renderStructure();
  }

  function sync() {
    var start = Date.now();
    $.ajax({ url: cfg.urls.data, dataType: 'json', cache: false })
      .done(function (p) {
        state.syncFailed = false;
        $syncInfo.text('Sinkron ' + fmtTime(Date.now())).removeClass('text-danger');
        applyPayload(p, start);
      })
      .fail(function () {
        state.syncFailed = true;
        $syncInfo.text('Gagal sinkron — data mungkin tidak terbaru').addClass('text-danger');
      });
  }

  /* ---------- Ruangan (filter tersimpan per perangkat) ---------- */
  var ROOM_KEY = 'infus.room';

  function roomUrl(roomId) {
    var base = cfg.urls.index;
    if (!roomId) return base;
    return base + (base.indexOf('?') === -1 ? '?' : '&') + 'room=' + encodeURIComponent(roomId);
  }

  function initRoom() {
    var urlHasRoom = /[?&]room=/.test(window.location.search);
    var saved = null;
    try { saved = window.localStorage.getItem(ROOM_KEY); } catch (e) { /* ignore */ }

    // Buka tanpa parameter: pakai ruangan yang terakhir dipilih di perangkat ini.
    if (!urlHasRoom && saved && !cfg.roomId) {
      var exists = $root.find('.js-room option[value="' + saved + '"]').length > 0;
      if (exists) { window.location.replace(roomUrl(saved)); return false; }
      try { window.localStorage.removeItem(ROOM_KEY); } catch (e) { /* ignore */ }
    }

    // Simpan pilihan saat ini (termasuk "Semua ruangan" = kosong).
    if (urlHasRoom) {
      try {
        if (cfg.roomId) window.localStorage.setItem(ROOM_KEY, String(cfg.roomId));
        else window.localStorage.removeItem(ROOM_KEY);
      } catch (e) { /* ignore */ }
    }

    $root.on('change', '.js-room', function () {
      var v = $(this).val();
      try {
        if (v) window.localStorage.setItem(ROOM_KEY, v);
        else window.localStorage.removeItem(ROOM_KEY);
      } catch (e) { /* ignore */ }
      // "Semua ruangan" tetap mengirim room=0 agar tidak dialihkan ke ruangan tersimpan.
      window.location.href = v ? roomUrl(v) : roomUrl('0');
    });
    return true;
  }

  /* ---------- Init ---------- */
  $(function () {
    $root = $('#dashboard');
    if (!$root.length) return;
    if (!initRoom()) return;
    $grid = $root.find('.js-grid');
    $tbody = $root.find('.js-tbody');
    $idle = $root.find('.js-idle');
    $banner = $root.find('.js-alarm-banner');
    $summary = $root.find('.js-summary');
    $empty = $root.find('.js-empty');
    $syncInfo = $root.find('.js-sync-info');

    // Tampilan tersimpan
    try { state.view = window.localStorage.getItem('infus.view') || 'grid'; } catch (e) { /* ignore */ }
    setView(state.view);

    applyPayload(cfg.payload, null);

    window.setInterval(update, 1000);
    window.setInterval(sync, (cfg.payload.sync_seconds || 30) * 1000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) sync(); });

    $(document).on('app:refresh', function () { sync(); });
    $(document).on('app:sound', function () { update(); });

    $root.on('input', '.js-search', function () { state.query = $(this).val().trim(); update(); });
    $root.on('click', '.js-filter button', function () {
      state.filter = $(this).data('filter');
      $root.find('.js-filter button').removeClass('is-active').attr('aria-pressed', 'false');
      $(this).addClass('is-active').attr('aria-pressed', 'true');
      update();
    });
    $root.on('click', '.js-view button', function () { setView($(this).data('view')); });

    $root.on('click', '.js-ack', function () {
      var id = $(this).closest('[data-id]').data('id');
      state.acked[id] = true;
      update();
    });
    $root.on('click', '.js-ack-all', function () {
      state.sessions.forEach(function (s) {
        var c = compute(s);
        if (c.display === 'alarm' || c.display === 'empty') state.acked[s.id] = true;
      });
      update();
    });
  });

  function setView(v) {
    state.view = v === 'table' ? 'table' : 'grid';
    try { window.localStorage.setItem('infus.view', state.view); } catch (e) { /* ignore */ }
    $root.toggleClass('view-table', state.view === 'table');
    $root.find('.js-view button').each(function () {
      var on = $(this).data('view') === state.view;
      $(this).toggleClass('is-active', on).attr('aria-pressed', on ? 'true' : 'false');
    });
  }

})(window, window.jQuery, window.App);
