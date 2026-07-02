/**
 * Sitemap Generator — Admin JS
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Requires: admin/view/javascript/ockit/assets/js/ok-common.js
 *   — okNotify(), initTabs(), okPost(), okCopyCode()
 *   — global handler for .ok-copy-btn (data-target / data-notify)
 */
(function () {
  'use strict';

  var L = {};   // sgI18n — set in DOMContentLoaded
  var A = {};   // sgAjax — set in DOMContentLoaded

  // ─── POST wrapper ────────────────────────────────────────────────────────────

  function sgPost(url, body, cb) {
    okPost(url, body, cb, function () {
      okNotify(L.generate_error || 'Network error', 'error');
    });
  }

  // ─── Generation mode toggle ──────────────────────────────────────────────────

  document.addEventListener('change', function (e) {
    if (e.target.name !== 'module_oc_kit_sitemap_generator_generation_mode') return;
    var isDynamic = e.target.value === 'dynamic';
    document.querySelectorAll('.sg-dynamic-only').forEach(function (row) {
      row.classList.toggle('ok-hidden', !isDynamic);
    });
  });

  // ─── Image options toggle ────────────────────────────────────────────────────

  document.addEventListener('change', function (e) {
    var el = e.target;
    if (el.id === 'sg-toggle-images') {
      var opts = document.getElementById('sg-image-options');
      if (opts) opts.classList.toggle('ok-hidden', !el.checked);
    }
    if (el.name === 'module_oc_kit_sitemap_generator_image_type') {
      var ro = document.getElementById('sg-resize-options');
      if (ro) ro.classList.toggle('ok-hidden', el.value !== 'resized');
    }
  });

  // ─── Schedule toggle ──────────────────────────────────────────────────────────

  document.addEventListener('change', function (e) {
    if (e.target.id !== 'sg-cron-schedule') return;
    document.querySelectorAll('.sg-custom-cron').forEach(function (row) {
      row.classList.toggle('ok-hidden', e.target.value !== 'custom');
    });
  });

  // ─── Generate: all maps ───────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#sg-btn-generate');
    if (!btn) return;
    startGenerate(null, btn);
  });

  // ─── Generate: single map ─────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.sg-btn-gen-map');
    if (!btn) return;
    startGenerate(btn.dataset.mapId, btn);
  });

  function startGenerate(mapId, triggerBtn) {
    var progressWrap = document.getElementById('sg-generate-progress');
    var progressText = document.getElementById('sg-progress-text');

    if (progressWrap) progressWrap.classList.remove('ok-hidden');
    if (progressText) progressText.textContent = L.generating || 'Generating...';
    if (triggerBtn)   triggerBtn.disabled = true;

    var body = {};
    if (mapId) body.map_id = mapId;

    sgPost(A.generate, body, function (data) {
      if (triggerBtn) triggerBtn.disabled = false;
      if (progressWrap) progressWrap.classList.add('ok-hidden');

      if (data.success) {
        var r = data.result || {};
        var msg = (L.generate_success || '%d URLs in %d file(s)')
          .replace('%d', r.urls_total || 0)
          .replace('%d', r.files_total || 0);
        okNotify(msg, 'success');

        // Reload the files list
        var filesList = document.getElementById('sg-files-list');
        if (filesList && r.files && r.files.length) {
          var rows = r.files.map(function (f) {
            return '<tr><td><a href="' + f.url + '" target="_blank" class="sg-file-link">' + f.filename + '</a></td>'
              + '<td>' + f.size_kb + ' KB</td>'
              + '<td>' + f.mtime + '</td></tr>';
          }).join('');
          filesList.innerHTML = '<table class="ok-table ok-table-sm">'
            + '<thead><tr><th>' + (L.col_files || 'File') + '</th><th>KB</th><th>' + (L.col_date || 'Date') + '</th></tr></thead>'
            + '<tbody>' + rows + '</tbody></table>';
        }
      } else {
        var r2     = data.result || {};
        var errors = r2.errors && r2.errors.length ? r2.errors.join('; ') : null;
        var errMsg = data.error || errors || 'Error';
        okNotify(errMsg, 'error');
      }
    });
  }

  // ─── Generate resizes ─────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#sg-btn-generate-resizes');
    if (!btn) return;

    var status = document.getElementById('sg-resizes-status');
    btn.disabled = true;
    if (status) { status.style.display = ''; status.textContent = L.resizes_running || 'Processing...'; }

    sgPost(A.generate_resizes, {}, function (data) {
      btn.disabled = false;
      if (data.success) {
        var r   = data.result || {};
        var msg = (L.resizes_done || 'Done: %p products, %c created, %k cached')
          .replace('%p', r.processed || 0)
          .replace('%c', r.created   || 0)
          .replace('%k', r.cached    || 0);
        if (status) { status.style.display = ''; status.textContent = msg; }
        okNotify(msg, 'success');
      } else {
        var err = data.error || (data.result && data.result.error) || (L.resizes_error || 'Error');
        if (status) { status.style.display = ''; status.textContent = err; }
        okNotify(err, 'error');
      }
    });
  });

  // ─── Delete files ──────────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#sg-btn-delete-files');
    if (!btn) return;
    if (!confirm(L.confirm_delete_files || 'Delete all generated files?')) return;

    sgPost(A.deleteFiles, {}, function (data) {
      if (data.success) {
        var filesList = document.getElementById('sg-files-list');
        if (filesList) {
          filesList.innerHTML = '<p class="ok-text-muted">' + (L.no_files || '') + '</p>';
        }
        okNotify(L.success || 'Done', 'success');
      } else {
        okNotify(data.error || L.generate_error || 'Error', 'error');
      }
    });
  });

  // ─── Map form: add ─────────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#sg-btn-add-map');
    if (!btn) return;
    resetMapForm();
    var title = document.getElementById('sg-map-form-title');
    if (title) title.textContent = L.add_map || '';
    showMapForm();
  });

  // ─── Map form: edit ────────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.sg-btn-edit-map');
    if (!btn) return;
    var row = document.getElementById('sg-map-row-' + btn.dataset.mapId);
    if (!row) return;
    var map;
    try { map = JSON.parse(row.dataset.map); } catch (ex) { return; }

    resetMapForm();
    document.getElementById('sg-map-id').value              = map.map_id      || '';
    var langEl = document.getElementById('sg-map-language-id');
    if (langEl) langEl.value                                = map.language_id  || 0;
    document.getElementById('sg-map-url-prefix').value      = map.url_prefix   || '';
    document.getElementById('sg-map-filename').value        = map.filename     || '';
    document.getElementById('sg-map-hreflang-locale').value = map.hreflang_locale || '';
    document.getElementById('sg-map-is-default').checked   = !!+map.is_default;
    document.getElementById('sg-map-status').checked       = !!+map.status;

    var title = document.getElementById('sg-map-form-title');
    if (title) title.textContent = L.edit_map || '';
    showMapForm();
  });

  // ─── Map form: cancel ──────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    if (!e.target.closest('#sg-btn-cancel-map')) return;
    hideMapForm();
  });

  // ─── Map form: save ────────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#sg-btn-save-map');
    if (!btn) return;

    var langEl = document.getElementById('sg-map-language-id');
    if (!langEl || !langEl.value || langEl.value === '0') {
      okNotify(L.error_language_required || 'Select a language.', 'error');
      return;
    }
    var filenameEl = document.getElementById('sg-map-filename');
    if (!filenameEl || !filenameEl.value.trim()) {
      okNotify(L.error_filename_required || 'Filename is required.', 'error');
      return;
    }

    var selectedOpt = langEl.options[langEl.selectedIndex];
    var langCode    = selectedOpt ? (selectedOpt.dataset.code || '') : '';
    var isNew       = !document.getElementById('sg-map-id').value;

    var body = {
      map_id:          document.getElementById('sg-map-id').value,
      language_id:     langEl.value,
      language_code:   langCode,
      url_prefix:      document.getElementById('sg-map-url-prefix').value,
      filename:        filenameEl.value.trim(),
      hreflang_locale: document.getElementById('sg-map-hreflang-locale').value,
      is_default:      document.getElementById('sg-map-is-default').checked  ? 1 : 0,
      status:          document.getElementById('sg-map-status').checked       ? 1 : 0
    };

    btn.disabled = true;
    sgPost(A.saveMap, body, function (data) {
      btn.disabled = false;
      if (data.success) {
        okNotify(L.success || 'Saved', 'success');
        hideMapForm();
        upsertMapRow(data.map, isNew);
      } else {
        okNotify(data.error || L.generate_error || 'Error', 'error');
      }
    });
  });

  // ─── Map row helpers ───────────────────────────────────────────────────────────

  function buildMapRow(map) {
    var prefix      = map.url_prefix ? '/' + map.url_prefix + '/' : '/';
    var storeUrl    = A.storeUrl || '';
    var mapJson     = JSON.stringify(map).replace(/'/g, '&#39;');
    var defBadge    = +map.is_default
      ? '<span class="ok-badge ok-badge-success">x-default</span>' : '';
    var statusBadge = +map.status
      ? '<span class="ok-badge ok-badge-success">ON</span>'
      : '<span class="ok-badge ok-badge-default">OFF</span>';
    var lastGen = map.last_generated_at
      ? esc(map.last_generated_at) : (L.not_generated || '—');

    return '<tr id="sg-map-row-' + map.map_id + '" data-map=\'' + mapJson + '\'>'
      + '<td>' + esc(map.language_code || '') + '</td>'
      + '<td><code>' + esc(prefix) + '</code></td>'
      + '<td><a href="' + storeUrl + '/' + esc(map.filename) + '.xml" target="_blank">'
      +   esc(map.filename) + '.xml</a></td>'
      + '<td>' + esc(map.hreflang_locale || '') + '</td>'
      + '<td>' + defBadge + '</td>'
      + '<td>' + statusBadge + '</td>'
      + '<td>' + (map.urls_count || '—') + '</td>'
      + '<td>' + lastGen + '</td>'
      + '<td class="ok-flex ok-gap-4">'
      +   '<button type="button" class="ok-btn ok-btn-primary ok-btn-xs sg-btn-gen-map" data-map-id="' + map.map_id + '">'
      +     '<i class="fa fa-refresh"></i> ' + esc(L.btn_generate_map || '') + '</button>'
      +   '<button type="button" class="ok-btn ok-btn-default ok-btn-xs sg-btn-edit-map" data-map-id="' + map.map_id + '">'
      +     '<i class="fa fa-pencil"></i></button>'
      +   '<button type="button" class="ok-btn ok-btn-danger ok-btn-xs sg-btn-delete-map" data-map-id="' + map.map_id + '">'
      +     '<i class="fa fa-trash"></i></button>'
      + '</td>'
      + '</tr>';
  }

  function upsertMapRow(map, isNew) {
    // If x-default changed — clear the badge on all other rows first
    if (+map.is_default) {
      document.querySelectorAll('#sg-maps-table tbody tr').forEach(function (tr) {
        var cell = tr.cells[4]; // x-default column (0-based)
        if (cell) cell.innerHTML = '';
      });
    }

    var rowHtml = buildMapRow(map);

    if (!isNew) {
      // Edit: replace the existing row in-place
      var existing = document.getElementById('sg-map-row-' + map.map_id);
      if (existing) {
        existing.outerHTML = rowHtml;
      }
    } else {
      // Add: append to existing table or replace empty-state
      var table   = document.getElementById('sg-maps-table');
      var emptyEl = document.getElementById('sg-maps-empty');

      if (table) {
        var tbody = table.querySelector('tbody');
        if (tbody) tbody.insertAdjacentHTML('beforeend', rowHtml);
      } else {
        // Build full table replacing empty-state element
        var tableHtml = '<table class="ok-table" id="sg-maps-table">'
          + '<thead><tr>'
          + '<th>' + esc(L.col_language       || '') + '</th>'
          + '<th>' + esc(L.col_url_prefix     || '') + '</th>'
          + '<th>' + esc(L.col_filename       || '') + '</th>'
          + '<th>' + esc(L.col_hreflang       || '') + '</th>'
          + '<th>' + esc(L.col_xdefault       || '') + '</th>'
          + '<th>' + esc(L.col_status         || '') + '</th>'
          + '<th>' + esc(L.col_urls           || '') + '</th>'
          + '<th>' + esc(L.col_last_generated || '') + '</th>'
          + '<th>' + esc(L.col_actions        || '') + '</th>'
          + '</tr></thead>'
          + '<tbody>' + rowHtml + '</tbody>'
          + '</table>';
        if (emptyEl) {
          emptyEl.outerHTML = tableHtml;
        }
      }
    }

    // Flash the saved row
    var savedRow = document.getElementById('sg-map-row-' + map.map_id);
    if (savedRow) savedRow.classList.add('ok-row-saved');
  }

  // ─── Map: delete ───────────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.sg-btn-delete-map');
    if (!btn) return;
    if (!confirm(L.confirm_delete_map || 'Delete?')) return;

    var mapId = btn.dataset.mapId;
    sgPost(A.deleteMap, { map_id: mapId }, function (data) {
      if (data.success) {
        var row = document.getElementById('sg-map-row-' + mapId);
        if (row) row.remove();
        okNotify(L.success || 'Deleted', 'success');
      } else {
        okNotify(data.error || L.generate_error || 'Error', 'error');
      }
    });
  });

  // ─── Cron key regen ────────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#sg-btn-regen-key');
    if (!btn) return;
    if (!confirm(L.confirm_regen_key || 'Regenerate?')) return;

    sgPost(A.regenKey, {}, function (data) {
      if (data.success) {
        var urlEl = document.getElementById('sg-cron-url');
        if (urlEl && data.cron_url) urlEl.textContent = data.cron_url;
        var keyEl = document.getElementById('sg-cron-key');
        if (keyEl && data.cron_key) keyEl.value = data.cron_key;
        okNotify(L.success || 'Done', 'success');
      } else {
        okNotify(data.error || L.generate_error || 'Error', 'error');
      }
    });
  });

  // ─── Logs tab: load on first view ──────────────────────────────────────────────

  var logsLoaded = false;

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ok-tabs-sidebar-item[data-tab="layout-logs"]');
    if (!btn) return;
    if (!logsLoaded) loadLogs();
  });

  function loadLogs() {
    var container = document.getElementById('sg-logs-container');
    if (!container) return;
    container.innerHTML = '<p class="ok-text-muted sg-logs-empty"><i class="fa fa-spinner fa-spin"></i></p>';

    sgPost(A.logs, {}, function (data) {
      logsLoaded = true;
      var rows = data.rows || [];
      if (!rows.length) {
        container.innerHTML = '<p class="ok-text-muted sg-logs-empty">' + (L.logs_empty || '') + '</p>';
        return;
      }

      var triggerLabels = { manual: L.log_manual, cron: L.log_cron, http: L.log_http };
      var statusLabels  = { success: L.log_success, error: L.log_error, running: L.log_running };
      var statusClass   = { success: 'sg-log-success', error: 'sg-log-error', running: 'sg-log-running' };

      var html = '<table class="ok-table"><thead><tr>'
        + '<th>' + (L.col_date         || 'Date')         + '</th>'
        + '<th>' + (L.col_triggered_by || 'Triggered by') + '</th>'
        + '<th>' + (L.col_urls         || 'URLs')         + '</th>'
        + '<th>' + (L.col_files        || 'Files')        + '</th>'
        + '<th>' + (L.col_duration     || 'Duration')     + '</th>'
        + '<th>' + (L.col_status       || 'Status')       + '</th>'
        + '<th>' + (L.col_error        || 'Error')        + '</th>'
        + '</tr></thead><tbody>';

      rows.forEach(function (r) {
        var trigger = triggerLabels[r.triggered_by] || r.triggered_by;
        var status  = statusLabels[r.status]        || r.status;
        var sCls    = statusClass[r.status]         || '';
        var dur     = r.duration_ms ? (r.duration_ms / 1000).toFixed(1) + 's' : '—';
        var err     = r.error_message
          ? '<span title="' + esc(r.error_message) + '" class="sg-log-error-detail">'
            + esc(r.error_message.substring(0, 40)) + (r.error_message.length > 40 ? '…' : '') + '</span>'
          : '—';

        html += '<tr>'
          + '<td class="sg-log-date">' + esc(r.started_at || '') + '</td>'
          + '<td>' + esc(trigger) + '</td>'
          + '<td>' + (r.urls_count || '—') + '</td>'
          + '<td>' + (r.files_count || '—') + '</td>'
          + '<td class="sg-log-duration">' + dur + '</td>'
          + '<td class="' + sCls + '">' + esc(status) + '</td>'
          + '<td>' + err + '</td>'
          + '</tr>';
      });

      html += '</tbody></table>';
      container.innerHTML = html;
    });
  }

  function esc(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // ─── Logs: clear ───────────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#sg-btn-clear-logs');
    if (!btn) return;
    if (!confirm(L.confirm_clear_logs || 'Clear?')) return;

    sgPost(A.clearLogs, {}, function (data) {
      if (data.success) {
        okNotify(L.success || 'Done', 'success');
        logsLoaded = false;
        var container = document.getElementById('sg-logs-container');
        if (container) {
          container.innerHTML = '<p class="ok-text-muted sg-logs-empty">' + (L.logs_empty || '') + '</p>';
        }
      } else {
        okNotify(data.error || L.generate_error || 'Error', 'error');
      }
    });
  });

  // ─── License: activate ─────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#sg-btn-activate');
    if (!btn) return;

    var keyEl = document.getElementById('sg-license-key')
             || document.querySelector('[name="module_oc_kit_sitemap_generator_license_key"]');
    var key      = keyEl ? keyEl.value.trim() : '';
    var origHtml = btn.innerHTML;

    btn.disabled  = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

    sgPost(A.activate, { license_key: key }, function (data) {
      btn.disabled  = false;
      btn.innerHTML = origHtml;

      if (data.success) {
        okNotify(data.message || L.success || 'Activated', 'success');
        setTimeout(function () { location.reload(); }, 800);
      } else {
        okNotify((data.message || data.error) || L.generate_error || 'Error', 'error');
      }
    });
  });

  // ─── Map form helpers ──────────────────────────────────────────────────────────

  function showMapForm() {
    var form = document.getElementById('sg-map-form');
    if (!form) return;
    form.classList.remove('ok-hidden');
    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function hideMapForm() {
    var form = document.getElementById('sg-map-form');
    if (form) form.classList.add('ok-hidden');
    resetMapForm();
  }

  function resetMapForm() {
    var idEl = document.getElementById('sg-map-id');
    if (idEl) idEl.value = '';
    var langEl = document.getElementById('sg-map-language-id');
    if (langEl) langEl.value = '0';
    var up = document.getElementById('sg-map-url-prefix');
    if (up) up.value = '';
    var fn = document.getElementById('sg-map-filename');
    if (fn) fn.value = '';
    var hl = document.getElementById('sg-map-hreflang-locale');
    if (hl) hl.value = '';
    var def = document.getElementById('sg-map-is-default');
    if (def) def.checked = false;
    var st = document.getElementById('sg-map-status');
    if (st) st.checked = true;
  }

  // ─── Save settings (AJAX) ────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#sg-btn-save-settings');
    if (!btn) return;

    var form = document.getElementById('form-sg');
    if (!form || !A.save) return;

    var data = okFormData(form);

    var label = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

    sgPost(A.save, data, function (res) {
      btn.disabled = false;
      btn.innerHTML = label;
      if (res && res.success) {
        okNotify(res.message || L.success || 'Saved', 'success');
      } else {
        okNotify((res && res.error) || 'Error', 'error');
      }
    });
  });

  // ─── DOMContentLoaded ─────────────────────────────────────────────────────────

  document.addEventListener('DOMContentLoaded', function () {
    L = window.sgI18n || {};
    A = window.sgAjax || {};
    initTabs('#sg-layout-sidebar', '.ok-tabs-sidebar-item', '.ok-layout-panel');
  });

}());
